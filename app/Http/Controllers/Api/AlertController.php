<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TariffAlert;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->query('user_id') ?? session('active_user_id') ?? Auth::id() ?? 2;
        $status = $request->query('status');

        $query = TariffAlert::where('user_id', $userId)
            ->with(['hsCode', 'revision.circular'])
            ->orderBy('id', 'desc');

        if ($status && in_array(strtoupper($status), ['UNREAD', 'READ', 'DISMISSED'])) {
            $query->where('status', strtoupper($status));
        }

        $alerts = $query->limit(50)->get();
        $unreadCount = TariffAlert::where('user_id', $userId)->where('status', 'UNREAD')->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'data' => $alerts,
        ]);
    }

    public function markAsRead(TariffAlert $alert): JsonResponse
    {
        $alert->update(['status' => 'READ']);

        return response()->json([
            'success' => true,
            'message' => 'Alert marked as read.',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = session('active_user_id', Auth::id() ?? 1);

        TariffAlert::where('user_id', $userId)
            ->where('status', 'UNREAD')
            ->update(['status' => 'READ']);

        return response()->json([
            'success' => true,
            'message' => 'All alerts marked as read.',
        ]);
    }

    public function dismiss(TariffAlert $alert): JsonResponse
    {
        $alert->update(['status' => 'DISMISSED']);

        return response()->json([
            'success' => true,
            'message' => 'Alert dismissed.',
        ]);
    }
}
