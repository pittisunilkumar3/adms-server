<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * StudentAttendance Model
 * Represents student attendance records
 */
class StudentAttendance extends Model
{
    protected $table = 'student_attendences';
    
    protected $fillable = [
        'date',
        'student_session_id',
        'attendence_type_id',
        'biometric_attendence',
        'is_authorized_range',
        'time_range_id',
        'check_in_time',
        'check_out_time',
        'biometric_device_data',
        'remark',
        'created_at',
    ];

    public $timestamps = false;

    /**
     * Get the student session
     */
    public function studentSession()
    {
        return $this->belongsTo(StudentSession::class, 'student_session_id');
    }

    /**
     * Get the time range
     */
    public function timeRange()
    {
        return $this->belongsTo(BiometricTimingSetup::class, 'time_range_id');
    }

    /**
     * Check if attendance already exists for student on a specific date
     * 
     * @param int $student_session_id
     * @param string $date
     * @return bool
     */
    public static function existsForDate($student_session_id, $date)
    {
        return self::where('student_session_id', $student_session_id)
            ->where('date', $date)
            ->exists();
    }

    /**
     * Create attendance record if it doesn't exist for the date
     * 
     * @param array $data
     * @return bool
     */
    public static function createIfNotExists($data)
    {
        // Check if already exists
        if (self::existsForDate($data['student_session_id'], $data['date'])) {
            return false;
        }

        // Create new record
        self::create($data);
        return true;
    }
}

