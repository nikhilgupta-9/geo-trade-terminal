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
 *
 * SQL files are split on `;` and executed statement-by-statement on the
 * same PDO connection (not as one multi-statement query), so a seed file
 * can safely use session variables like `SET @x = LAST_INSERT_ID();`
 * across statements. This means a literal `;` inside a string value in a
 * migration file would incorrectly split the statement — none of our
 * migrations do that, but keep it in mind when writing new ones.
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
    // duplicate duty-rate history rows.
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

        foreach (explode(';', $sql) as $statement) {
            // Drop full-line `-- comment` fragments, then skip anything
            // left blank (pure comment blocks, trailing newline after the
            // final `;`).
            $statement = trim(preg_replace('/^\s*--.*$/m', '', $statement) ?? '');
            if ($statement === '') {
                continue;
            }

            $pdo->exec($statement);
        }

        $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:m)')->execute(['m' => $name]);
    }
}
