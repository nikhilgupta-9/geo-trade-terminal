<?php

declare(strict_types=1);

/**
 * Laravel-style auto migrations, adapted for a host with no `artisan` and
 * no shell access: every numbered file in sql/migrations/ is applied at
 * most once, tracked in a `schema_migrations` table, and this runs on
 * every page load via db() in config/database.php. Add a new numbered
 * .sql file (0004_..., 0005_..., always higher than the last) and it
 * applies itself automatically next time anyone hits the site — no
 * manual phpMyAdmin import, ever again.
 *
 * Cost per request when nothing is pending: one CREATE TABLE IF NOT
 * EXISTS, one SELECT, one glob() — a few milliseconds, acceptable for
 * shared hosting since there's no queue worker to run this out-of-band.
 */
function run_pending_migrations(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );

    $appliedCount = (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    $isFirstRunEver = $appliedCount === 0;

    $files = glob(__DIR__ . '/../sql/migrations/*.sql') ?: [];
    sort($files);

    $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

    // Baseline: 0001 (schema) and 0002 (admin/HS-code/duty-rate seed) were
    // very likely already applied by hand through phpMyAdmin before this
    // migration system existed. If admin_users already has rows on the
    // very first run of this system, mark 0001/0002 as already applied
    // instead of re-running them -- 0002's duty_rates INSERT has no
    // unique constraint to dedupe against, so re-running it would
    // duplicate history rows.
    if ($isFirstRunEver) {
        $hasExistingAdminRow = (bool) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'admin_users'"
        )->fetchColumn() && (bool) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();

        if ($hasExistingAdminRow) {
            $markBaseline = $pdo->prepare('INSERT IGNORE INTO schema_migrations (migration) VALUES (:m)');
            foreach ($files as $file) {
                $name = basename($file);
                if (str_starts_with($name, '0001_') || str_starts_with($name, '0002_')) {
                    $markBaseline->execute(['m' => $name]);
                    $applied[] = $name;
                }
            }
        }
    }

    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $applied, true)) {
            continue;
        }

        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("Could not read migration file: {$name}");
        }

        $statements = array_filter(array_map('trim', split_sql_statements($sql)), static fn (string $s) => $s !== '');

        // Wrapped in a transaction so a mid-file failure can't leave
        // partial rows behind without the file being marked applied --
        // without this, a broken statement halfway through a seed file
        // would commit everything before it, and the retry on the next
        // request would re-run from the top and duplicate those rows.
        // (MySQL DDL such as CREATE TABLE still auto-commits regardless;
        // this protects DML-only migrations, which is what seed files are.)
        $pdo->beginTransaction();
        try {
            foreach ($statements as $statement) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $ex) {
                    // Ignore duplicate column (1060) or duplicate key (1061) if an ALTER TABLE partially ran
                    if (in_array((int) ($ex->errorInfo[1] ?? 0), [1060, 1061], true)) {
                        continue;
                    }
                    throw $ex;
                }
            }
            $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:m)')->execute(['m' => $name]);
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException("Migration {$name} failed: " . $e->getMessage(), previous: $e);
        }
    }
}

/**
 * Splits a .sql file's contents into individual statements on `;`,
 * ignoring any `;` that appears inside a single-quoted string, a
 * double-quoted string, a backtick-quoted identifier, or a `-- ` line
 * comment -- so seed data containing ordinary punctuation (rationale
 * text, news summaries, etc.) never gets mis-split. Also strips
 * full-line `-- comment` content from each statement. Does not handle
 * `/* ... *\/` block comments or stored-procedure DELIMITER blocks --
 * none of our migrations use either.
 */
function split_sql_statements(string $sql): array
{
    $statements = [];
    $current = '';
    $length = strlen($sql);
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($inLineComment) {
            if ($char === "\n") {
                $inLineComment = false;
                $current .= $char;
            }
            continue;
        }

        if (! $inSingle && ! $inDouble && ! $inBacktick && $char === '-' && $next === '-') {
            $inLineComment = true;
            $i++; // consume both dashes
            continue;
        }

        // Handle backslash escaping inside single-quoted strings: \' and \\
        if ($inSingle && $char === '\\') {
            $current .= $char . $next;
            $i++;
            continue;
        }

        // Handle backslash escaping inside double-quoted strings: \" and \\
        if ($inDouble && $char === '\\') {
            $current .= $char . $next;
            $i++;
            continue;
        }

        if (! $inDouble && ! $inBacktick && $char === "'") {
            if ($inSingle && $next === "'") { // escaped '' inside a single-quoted string
                $current .= "''";
                $i++;
                continue;
            }
            $inSingle = ! $inSingle;
            $current .= $char;
            continue;
        }

        if (! $inSingle && ! $inBacktick && $char === '"') {
            if ($inDouble && $next === '"') {
                $current .= '""';
                $i++;
                continue;
            }
            $inDouble = ! $inDouble;
            $current .= $char;
            continue;
        }

        if (! $inSingle && ! $inDouble && $char === '`') {
            $inBacktick = ! $inBacktick;
            $current .= $char;
            continue;
        }

        if (! $inSingle && ! $inDouble && ! $inBacktick && $char === ';') {
            $statements[] = $current;
            $current = '';
            continue;
        }

        $current .= $char;
    }

    if (trim($current) !== '') {
        $statements[] = $current;
    }

    return $statements;
}
