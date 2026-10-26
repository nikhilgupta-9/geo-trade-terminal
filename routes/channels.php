<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// 1. Public Channel: Live Tariff Ticker Stream
Broadcast::channel('terminal.live-ticker', function () {
    return true;
});

// 2. Private Channel: User-Specific Duty Shift Alerts
Broadcast::channel('user.{id}.alerts', function (User $user, int $id) {
    return (int) $user->id === (int) $id;
});

// 3. Private Channel: Admin Review Queue for Quarantined Tariff Revisions
Broadcast::channel('admin.review-queue', function (User $user) {
    return $user->isAdmin();
});
