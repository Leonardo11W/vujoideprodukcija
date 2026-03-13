<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NotificationsController extends Controller
{
    public function notificationList(Request $request)
    {
        $user = auth()->user();
        $user->last_notification_seen = now();
        $user->save();

        $type = isset($request->type) ? $request->type : null;
        if ($type == 'mark_as_read') {
            if (count($user->unreadNotifications) > 0) {
                $user->unreadNotifications->markAsRead();
            }
        }
        $page = 1;
        $limit = 100;
        $notifications = $user->Notifications->sortByDesc('created_at')->forPage($page, $limit);
        $all_unread_count = isset($user->unreadNotifications) ? $user->unreadNotifications->count() : 0;

        $items = NotificationResource::collection($notifications);
        $response = [
            'notification_data' => $items,
            'all_unread_count' => $all_unread_count,
            'message' => __('messages.mark_read'),
            'status' => true,
        ];

        return $response;
    }

    /**
     * Format time elapsed as "25m Ago" or "5d Ago"
     */
    private function formatTimeAgo($date)
    {
        if (!$date) {
            return '--';
        }

        $now = Carbon::now();
        $notificationDate = Carbon::parse($date);
        $diffInMinutes = $now->diffInMinutes($notificationDate);
        $diffInHours = $now->diffInHours($notificationDate);
        $diffInDays = $now->diffInDays($notificationDate);
        $diffInWeeks = $now->diffInWeeks($notificationDate);
        $diffInMonths = $now->diffInMonths($notificationDate);

        if ($diffInMinutes < 60) {
            return $diffInMinutes . 'm Ago';
        } elseif ($diffInHours < 24) {
            return $diffInHours . 'h Ago';
        } elseif ($diffInDays < 7) {
            return $diffInDays . 'd Ago';
        } elseif ($diffInWeeks < 4) {
            return $diffInWeeks . 'w Ago';
        } elseif ($diffInMonths < 12) {
            return $diffInMonths . 'mo Ago';
        } else {
            return $now->diffInYears($notificationDate) . 'y Ago';
        }
    }

    /**
     * Extract notification type from notification data
     */
    private function extractNotificationType($notification)
    {
        $rawData = is_array($notification->data) ? $notification->data : json_decode($notification->data, true);
        $data = $rawData['data'] ?? $rawData;
        
        // Check for type in data
        $type = $data['type'] ?? $rawData['type'] ?? null;
        if ($type) {
            $type = strtolower($type);
            // Map common types
            if (strpos($type, 'payment') !== false || strpos($type, 'payout') !== false) {
                return 'payment';
            } elseif (strpos($type, 'booking') !== false || strpos($type, 'appointment') !== false) {
                return 'booking';
            } elseif (strpos($type, 'offer') !== false || strpos($type, 'discount') !== false || strpos($type, 'promo') !== false) {
                return 'offer';
            } elseif (strpos($type, 'review') !== false || strpos($type, 'feedback') !== false) {
                return 'feedback';
            }
        }

        // Check notification_group
        $notificationGroup = $data['notification_group'] ?? $rawData['notification_group'] ?? null;
        if ($notificationGroup) {
            $group = strtolower($notificationGroup);
            if ($group === 'shop' || $group === 'order') {
                return 'order';
            } elseif ($group === 'booking') {
                return 'booking';
            }
        }

        // Default based on notification class type
        $notificationType = class_basename($notification->type);
        if (strpos($notificationType, 'Payment') !== false) {
            return 'payment';
        } elseif (strpos($notificationType, 'Booking') !== false) {
            return 'booking';
        }

        return 'general';
    }

    public function managerNotificationList(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $page = (int)$request->input('page', 1);
        $limit = (int)$request->input('limit', 50);

        // Get all notifications for the user
        $allNotifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get();

        // Group notifications by time periods
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->subDays(7)->startOfDay();
        $monthStart = $now->copy()->subDays(30)->startOfDay();

        $newNotifications = [];
        $thisWeekNotifications = [];
        $thisMonthNotifications = [];

        foreach ($allNotifications as $notification) {
            $createdAt = Carbon::parse($notification->created_at);
            $rawData = is_array($notification->data) ? $notification->data : json_decode($notification->data, true);
            
            // Handle nested data structure (data might be nested under 'data' key)
            $data = $rawData['data'] ?? $rawData;
            
            // Extract notification details
            $title = $data['subject'] ?? $data['type'] ?? $rawData['subject'] ?? $rawData['type'] ?? 'Notification';
            $subtitle = $data['text'] ?? $data['notification_message'] ?? $data['message'] 
                ?? $rawData['text'] ?? $rawData['notification_message'] ?? $rawData['message'] ?? '';
            
            // Clean HTML tags from subtitle
            $subtitle = strip_tags($subtitle);
            $subtitle = html_entity_decode($subtitle, ENT_QUOTES, 'UTF-8');

            // Convert UUID to integer-like ID (use hash of UUID for consistency)
            // Take first 8 hex chars and convert to integer, ensuring it's positive
            $uuidParts = explode('-', $notification->id);
            $hexString = substr($uuidParts[0] . $uuidParts[1], 0, 8);
            $notificationId = abs((int)hexdec($hexString));
            
            // Ensure it's a reasonable integer (not too large)
            if ($notificationId > 2147483647) {
                $notificationId = $notificationId % 1000000; // Use modulo to keep it reasonable
            }

            $notificationItem = [
                'notification_id' => $notificationId,
                'notification_title' => $title,
                'notification_subtitle' => $subtitle,
                'notification_time' => $this->formatTimeAgo($notification->created_at),
                'notification_type' => $this->extractNotificationType($notification),
                'notification_is_read' => !is_null($notification->read_at),
            ];

            // Categorize by time period
            if ($createdAt->gte($todayStart)) {
                $newNotifications[] = $notificationItem;
            } elseif ($createdAt->gte($weekStart)) {
                $thisWeekNotifications[] = $notificationItem;
            } elseif ($createdAt->gte($monthStart)) {
                $thisMonthNotifications[] = $notificationItem;
            }
        }

        // Build grouped response
        $groupedData = [];

        if (!empty($newNotifications)) {
            $groupedData[] = [
                'group_label' => 'New',
                'notifications' => $newNotifications
            ];
        }

        if (!empty($thisWeekNotifications)) {
            $groupedData[] = [
                'group_label' => 'This Week',
                'notifications' => $thisWeekNotifications
            ];
        }

        if (!empty($thisMonthNotifications)) {
            $groupedData[] = [
                'group_label' => 'This Month',
                'notifications' => $thisMonthNotifications
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Notification fetched successfully',
            'data' => $groupedData
        ], 200);
    }

    public function markNotificationRead(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $request->validate([
            'notification_id' => 'required|integer'
        ]);

        // Find the notification - need to search through all notifications
        // since we're using integer ID but notifications use UUID
        $notification = null;
        $allNotifications = $user->notifications()->get();
        
        foreach ($allNotifications as $notif) {
            $uuidParts = explode('-', $notif->id);
            $hexString = substr($uuidParts[0] . $uuidParts[1], 0, 8);
            $notifId = abs((int)hexdec($hexString));
            if ($notifId > 2147483647) {
                $notifId = $notifId % 1000000;
            }
            
            if ($notifId == $request->notification_id) {
                $notification = $notif;
                break;
            }
        }

        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        // Mark as read if not already read
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read successfully.'
        ], 200);
    }
}
