<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\NotificationRequest;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

// The signed-in user's in-app notifications (e.g. App\Notifications\ProjectCompleted), newest first.
class NotificationController extends Controller
{
    /**
     * `?unread=1` lists only unread ones; the SPA reads `meta.total` of that list as the unread count.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->when($request->boolean('unread'), fn ($query) => $query->whereNull('read_at'))
            ->paginate();

        return NotificationResource::collection($notifications)->response();
    }

    public function update(NotificationRequest $request, DatabaseNotification $notification): JsonResponse
    {
        // Other people's notifications are not found.
        abort_unless($notification->notifiable()->is($request->user()), 404);

        $request->validated('read') ? $notification->markAsRead() : $notification->markAsUnread();

        return (new NotificationResource($notification))->response();
    }
}
