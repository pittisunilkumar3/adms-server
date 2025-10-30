<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * StudentTimeRangeAssignment Model
 * Represents assignments of timing ranges to students
 */
class StudentTimeRangeAssignment extends Model
{
    use HasFactory;

    protected $table = 'student_time_range_assignments';

    protected $fillable = [
        'student_session_id',
        'time_range_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'student_session_id' => 'integer',
        'time_range_id' => 'integer',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    /**
     * Get the student session
     */
    public function studentSession()
    {
        return $this->belongsTo(StudentSession::class, 'student_session_id');
    }

    /**
     * Get the timing range
     */
    public function timingRange()
    {
        return $this->belongsTo(BiometricTimingSetup::class, 'time_range_id');
    }

    /**
     * Get active assignments for a student session
     */
    public static function getActiveAssignments($studentSessionId)
    {
        return self::where('student_session_id', $studentSessionId)
            ->where('is_active', 1)
            ->with('timingRange')
            ->get();
    }

    /**
     * Get assigned timing range IDs for a student session
     */
    public static function getAssignedRangeIds($studentSessionId)
    {
        return self::where('student_session_id', $studentSessionId)
            ->where('is_active', 1)
            ->pluck('time_range_id')
            ->toArray();
    }

    /**
     * Check if student has any timing assignments
     */
    public static function hasAssignments($studentSessionId)
    {
        return self::where('student_session_id', $studentSessionId)
            ->where('is_active', 1)
            ->exists();
    }
}
