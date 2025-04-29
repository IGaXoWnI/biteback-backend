<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckExpiredReservations extends Command
{
    protected $signature = 'reservations:check-expired';
    protected $description = 'Cancel expired reservations and restore box quantities';

    public function handle()
    {
        $this->info('Checking for expired reservations...');
        $count = 0;

        // Get all active reservations
        $reservations = Reservation::where('status', 'reserved')
            ->with('box')
            ->get();

        foreach ($reservations as $reservation) {
            $box = $reservation->box;

            // Skip if box doesn't exist or has no pickup time
            if (!$box || !$box->pickup_time) {
                continue;
            }

            // Parse the pickup time
            $times = explode('-', $box->pickup_time);
            if (count($times) != 2) {
                continue;
            }

            $startTime = trim($times[0]);
            $endTime = trim($times[1]);

            // Check if reservation was made today or earlier
            $reservationDate = Carbon::parse($reservation->created_at)->format('Y-m-d');
            $today = now()->format('Y-m-d');

            // Determine if the reservation has expired
            $expired = false;

            // If reservation from a previous day, it's expired
            if ($reservationDate < $today) {
                $expired = true;
            }
            // If reservation is from today, check if time has passed
            else if ($reservationDate === $today) {
                $endTimeToday = Carbon::parse("$today $endTime:00");

                if (now()->gt($endTimeToday)) {
                    $expired = true;
                }
            }

            // If expired, cancel the reservation
            if ($expired) {
                $box->increment('quantity_available');
                $reservation->update(['status' => 'canceled']);
                $count++;
            }
        }

        $this->info("Canceled $count expired reservations");
        return 0;
    }
}
