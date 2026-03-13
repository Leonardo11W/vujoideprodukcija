<?php
namespace Modules\Frontend\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\Models\Booking;
use Modules\Employee\Models\EmployeeRating;

class ReviewController extends Controller
{
    public function submit(Request $request)
    {
        try {
            $request->validate([
                'booking_id' => 'required|exists:bookings,id',
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'nullable|string',
            ]);

            $booking = Booking::find($request->booking_id);
            $user = auth()->user();
            $employee_id = $booking->booking_service->first()->employee_id ?? null;
            if (!$employee_id) {
                return response()->json(['success' => false, 'error' => 'No employee found for this booking.'], 400);
            }

            // Update or create review for this user/employee/booking
            $review = EmployeeRating::updateOrCreate(
                [
                    'employee_id' => $employee_id,
                    'user_id' => $user->id,
                    // Optionally, you can add 'booking_id' if you want to relate review to booking
                ],
                [
                    'review_msg' => $request->review,
                    'rating' => $request->rating
                ]
            );

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            $request->validate([
                'booking_id' => 'required|exists:bookings,id',
            ]);
            $booking = Booking::find($request->booking_id);
            $user = auth()->user();
            $employee_id = $booking->booking_service->first()->employee_id ?? null;
            if (!$employee_id) {
                return response()->json(['success' => false, 'error' => 'No employee found for this booking.'], 400);
            }
            $review = EmployeeRating::where('employee_id', $employee_id)
                ->where('user_id', $user->id)
                ->first();
            if ($review) {
                $review->delete();
            }
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
} 