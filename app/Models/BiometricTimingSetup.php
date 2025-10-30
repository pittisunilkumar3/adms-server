<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * BiometricTimingSetup Model
 * Represents biometric timing ranges for check-in and check-out
 */
class BiometricTimingSetup extends Model
{
    protected $table = 'biometric_timing_setup';

    protected $fillable = [
        'range_name',
        'range_type',
        'time_start',
        'time_end',
        'grace_period_minutes',
        'attendance_type_id',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'grace_period_minutes' => 'integer',
        'attendance_type_id' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Find the matching time range for a given timestamp
     * 
     * @param string $timestamp The punch timestamp (e.g., '2025-10-30 09:15:00')
     * @param int|null $assignedTimeRangeId Optional: specific time range ID assigned to user
     * @return BiometricTimingSetup|null
     */
    public static function findMatchingRange($timestamp, $assignedTimeRangeId = null)
    {
        $punchTime = Carbon::parse($timestamp)->format('H:i:s');
        
        $query = self::where('is_active', 1);
        
        // If a specific time range is assigned to the user, use only that range
        if ($assignedTimeRangeId) {
            $query->where('id', $assignedTimeRangeId);
        }
        
        // Find ranges where punch time falls within time_start and time_end
        $query->where(function($q) use ($punchTime) {
            $q->whereRaw('? BETWEEN time_start AND time_end', [$punchTime]);
        });
        
        // Order by priority (lower number = higher priority)
        $query->orderBy('priority', 'asc');
        
        return $query->first();
    }

    /**
     * Get all active check-in ranges
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveCheckInRanges()
    {
        return self::where('is_active', 1)
            ->where('range_type', 'checkin')
            ->orderBy('priority', 'asc')
            ->get();
    }

    /**
     * Get all active check-out ranges
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveCheckOutRanges()
    {
        return self::where('is_active', 1)
            ->where('range_type', 'checkout')
            ->orderBy('priority', 'asc')
            ->get();
    }

    /**
     * Check if a punch time is within this range
     * 
     * @param string $punchTime Time in H:i:s format
     * @return bool
     */
    public function isTimeInRange($punchTime)
    {
        $punch = Carbon::parse($punchTime);
        $start = Carbon::parse($this->time_start);
        $end = Carbon::parse($this->time_end);
        
        return $punch->between($start, $end);
    }

    /**
     * Get the check-in time for this range
     * 
     * @return string|null
     */
    public function getCheckInTime()
    {
        if ($this->range_type === 'checkin') {
            return $this->time_start;
        }
        return null;
    }

    /**
     * Get the check-out time for this range
     * 
     * @return string|null
     */
    public function getCheckOutTime()
    {
        if ($this->range_type === 'checkout') {
            return $this->time_end;
        }
        return null;
    }
}

