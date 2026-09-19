<?php

namespace App\Services;

class GeoLocationService
{
    /**
     * Hitung jarak antara dua koordinat dalam meter (formula Haversine).
     */
    public function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Cek apakah jarak masih di dalam radius yang diizinkan.
     */
    public function withinRadius(float $lat1, float $lon1, float $lat2, float $lon2, int $radiusMeter): bool
    {
        return $this->distanceMeters($lat1, $lon1, $lat2, $lon2) <= $radiusMeter;
    }
}
