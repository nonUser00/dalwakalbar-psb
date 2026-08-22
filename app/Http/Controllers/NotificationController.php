<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getAdminNotifications(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([]);
        }

        return response()->json([
            'notifications' => $user->notifications()->take(10)->get(),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function getPsbNotifications(Request $request)
    {
        $pendaftar = auth()->guard('pendaftar')->user();
        if (! $pendaftar) {
            return response()->json([]);
        }

        return response()->json([
            'notifications' => $pendaftar->notifications()->take(10)->get(),
            'unread_count' => $pendaftar->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user() ?? auth()->guard('pendaftar')->user();
        if ($user) {
            $notification = $user->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
            }
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user() ?? auth()->guard('pendaftar')->user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json(['success' => true]);
    }
}
