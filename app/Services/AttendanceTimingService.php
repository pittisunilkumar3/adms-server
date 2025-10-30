<?php

namespace App\Services;

use App\Models\BiometricTimingSetup;
use App\Models\StaffTimeRangeAssignment;
use App\Models\StudentTimeRangeAssignment;
use App\Models\StaffAttendanceType;
use App\Models\AttendanceType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * AttendanceTimingService
 * Handles timing range matching and attendance type determination
 */
class AttendanceTimingService
{
    /**
     * Process staff attendance timing
     * 
     * @param int $staffId
     * @param string $timestamp
     * @return array
     */
    public function processStaffTiming($staffId, $timestamp)
    {
        try {
            // Get assigned timing ranges for this staff member
            $assignedRangeIds = StaffTimeRangeAssignment::getAssignedRangeIds($staffId);
            
            // If no specific assignments, use all active ranges
            if (empty($assignedRangeIds)) {
                Log::info("No specific timing assignments for staff {$staffId}, using all active ranges");
                $assignedRangeIds = [];
            }
            
            // Find matching timing range
            $timingResult = BiometricTimingSetup::findMatchingRange($timestamp, $assignedRangeIds);
            
            if ($timingResult) {
                // Found a matching range
                return [
                    'attendance_type_id' => $timingResult['attendance_type_id'],
                    'is_authorized_range' => 1,
                    'remark' => $this->buildRemark($timingResult, $timestamp),
                    'timing_info' => $timingResult
                ];
            } else {
                // No matching range found - unauthorized time
                Log::warning("No matching timing range found for staff {$staffId} at {$timestamp}");
                
                return [
                    'attendance_type_id' => StaffAttendanceType::TYPE_PRESENT, // Default to present
                    'is_authorized_range' => 0, // Unauthorized
                    'remark' => $this->buildUnauthorizedRemark($timestamp),
                    'timing_info' => null
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Error processing staff timing for staff {$staffId}: " . $e->getMessage());
            
            // Fallback to default values
            return [
                'attendance_type_id' => StaffAttendanceType::TYPE_PRESENT,
                'is_authorized_range' => 1,
                'remark' => 'Auto-recorded from biometric device at ' . $timestamp,
                'timing_info' => null
            ];
        }
    }

    /**
     * Process student attendance timing
     * 
     * @param int $studentSessionId
     * @param string $timestamp
     * @return array
     */
    public function processStudentTiming($studentSessionId, $timestamp)
    {
        try {
            // Get assigned timing ranges for this student
            $assignedRangeIds = StudentTimeRangeAssignment::getAssignedRangeIds($studentSessionId);
            
            // If no specific assignments, use all active ranges
            if (empty($assignedRangeIds)) {
                Log::info("No specific timing assignments for student session {$studentSessionId}, using all active ranges");
                $assignedRangeIds = [];
            }
            
            // Find matching timing range
            $timingResult = BiometricTimingSetup::findMatchingRange($timestamp, $assignedRangeIds);
            
            if ($timingResult) {
                // Found a matching range
                return [
                    'attendance_type_id' => $timingResult['attendance_type_id'],
                    'is_authorized_range' => 1,
                    'remark' => $this->buildRemark($timingResult, $timestamp),
                    'timing_info' => $timingResult
                ];
            } else {
                // No matching range found - unauthorized time
                Log::warning("No matching timing range found for student session {$studentSessionId} at {$timestamp}");
                
                return [
                    'attendance_type_id' => AttendanceType::TYPE_PRESENT, // Default to present
                    'is_authorized_range' => 0, // Unauthorized
                    'remark' => $this->buildUnauthorizedRemark($timestamp),
                    'timing_info' => null
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Error processing student timing for session {$studentSessionId}: " . $e->getMessage());
            
            // Fallback to default values
            return [
                'attendance_type_id' => AttendanceType::TYPE_PRESENT,
                'is_authorized_range' => 1,
                'remark' => 'Auto-recorded from biometric device at ' . $timestamp,
                'timing_info' => null
            ];
        }
    }

    /**
     * Build remark for authorized attendance
     */
    private function buildRemark($timingResult, $timestamp)
    {
        $punchTime = Carbon::parse($timestamp)->format('H:i:s');
        $rangeName = $timingResult['range_name'];
        $rangeType = ucfirst($timingResult['range_type']);
        
        $remark = "{$rangeType} - {$rangeName} at {$punchTime}";
        
        if ($timingResult['is_late'] && $timingResult['minutes_late'] > 0) {
            $remark .= " (Late by {$timingResult['minutes_late']} minutes)";
        }
        
        return $remark;
    }

    /**
     * Build remark for unauthorized attendance
     */
    private function buildUnauthorizedRemark($timestamp)
    {
        $punchTime = Carbon::parse($timestamp)->format('H:i:s');
        return "Unauthorized time punch at {$punchTime}";
    }

    /**
     * Get timing summary for debugging
     */
    public function getTimingSummary($userType, $userId, $timestamp)
    {
        if ($userType === 'staff') {
            $assignedRanges = StaffTimeRangeAssignment::getAssignedRangeIds($userId);
        } else {
            $assignedRanges = StudentTimeRangeAssignment::getAssignedRangeIds($userId);
        }
        
        $allRanges = BiometricTimingSetup::getActiveRanges();
        
        return [
            'user_type' => $userType,
            'user_id' => $userId,
            'timestamp' => $timestamp,
            'punch_time' => Carbon::parse($timestamp)->format('H:i:s'),
            'assigned_range_ids' => $assignedRanges,
            'total_active_ranges' => $allRanges->count(),
            'using_all_ranges' => empty($assignedRanges)
        ];
    }
}
