<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * BiometricTimingSetup Model
 * Represents timing ranges for biometric attendance
 */
class BiometricTimingSetup extends Model
{
    use HasFactory;

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
        'time_start' => 'datetime:H:i:s',
        'time_end' => 'datetime:H:i:s',
        'grace_period_minutes' => 'integer',
        'attendance_type_id' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get all active timing ranges ordered by priority
     */
    public static function getActiveRanges()
    {
        return self::where('is_active', 1)
            ->orderBy('priority', 'asc')
            ->orderBy('time_start', 'asc')
            ->get();
    }

    /**
     * Find matching timing range for a given timestamp
     * 
     * @param string $timestamp The punch timestamp
     * @param array $assignedRangeIds Array of timing range IDs assigned to the user
     * @return array|null Returns timing info or null if no match
     */
    public static function findMatchingRange($timestamp, $assignedRangeIds = [])
    {
        $punchTime = Carbon::parse($timestamp);
        $timeOnly = $punchTime->format('H:i:s');
        
        // Get active ranges, filtered by assigned ranges if provided
        $query = self::where('is_active', 1);
        
        if (!empty($assignedRangeIds)) {
            $query->whereIn('id', $assignedRangeIds);
        }
        
        $ranges = $query->orderBy('priority', 'asc')
                       ->orderBy('time_start', 'asc')
                       ->get();

        foreach ($ranges as $range) {
            $startTime = $range->time_start;
            $endTime = $range->time_end;
            
            // Handle overnight ranges (e.g., 22:00 - 06:00)
            if ($startTime > $endTime) {
                // Check if time is after start time OR before end time
                if ($timeOnly >= $startTime || $timeOnly <= $endTime) {
                    return self::calculateTimingResult($range, $punchTime, $timeOnly);
                }
            } else {
                // Normal range (e.g., 09:00 - 17:00)
                if ($timeOnly >= $startTime && $timeOnly <= $endTime) {
                    return self::calculateTimingResult($range, $punchTime, $timeOnly);
                }
            }
        }

        return null; // No matching range found
    }

    /**
     * Calculate timing result (on-time, late, etc.)
     */
    private static function calculateTimingResult($range, $punchTime, $timeOnly)
    {
        $startTime = $range->time_start;
        $gracePeriod = $range->grace_period_minutes;
        
        // Calculate grace period end time
        $graceEndTime = Carbon::createFromFormat('H:i:s', $startTime)
            ->addMinutes($gracePeriod)
            ->format('H:i:s');
        
        $isLate = false;
        $minutesLate = 0;
        $remark = '';
        
        if ($range->range_type === 'checkin') {
            if ($timeOnly > $graceEndTime) {
                $isLate = true;
                $punchCarbon = Carbon::createFromFormat('H:i:s', $timeOnly);
                $graceCarbon = Carbon::createFromFormat('H:i:s', $graceEndTime);
                $minutesLate = $punchCarbon->diffInMinutes($graceCarbon);
                $remark = "Late by {$minutesLate} minutes";
            } else {
                $remark = "On-time check-in";
            }
        } else {
            $remark = "Check-out recorded";
        }

        return [
            'range' => $range,
            'attendance_type_id' => $range->attendance_type_id,
            'is_authorized' => true,
            'is_late' => $isLate,
            'minutes_late' => $minutesLate,
            'remark' => $remark,
            'range_name' => $range->range_name,
            'range_type' => $range->range_type,
        ];
    }

    /**
     * Get timing ranges assigned to a staff member
     */
    public static function getStaffAssignedRanges($staffId)
    {
        return self::join('staff_time_range_assignments', 'biometric_timing_setup.id', '=', 'staff_time_range_assignments.time_range_id')
            ->where('staff_time_range_assignments.staff_id', $staffId)
            ->where('staff_time_range_assignments.is_active', 1)
            ->where('biometric_timing_setup.is_active', 1)
            ->pluck('biometric_timing_setup.id')
            ->toArray();
    }

    /**
     * Get timing ranges assigned to a student
     */
    public static function getStudentAssignedRanges($studentSessionId)
    {
        return self::join('student_time_range_assignments', 'biometric_timing_setup.id', '=', 'student_time_range_assignments.time_range_id')
            ->where('student_time_range_assignments.student_session_id', $studentSessionId)
            ->where('student_time_range_assignments.is_active', 1)
            ->where('biometric_timing_setup.is_active', 1)
            ->pluck('biometric_timing_setup.id')
            ->toArray();
    }
}
