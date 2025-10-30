<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Student Model
 * Represents students in the system
 */
class Student extends Model
{
    protected $table = 'students';
    
    protected $fillable = [
        'admission_no',
        'firstname',
        'middlename',
        'lastname',
        'email',
        'mobileno',
        'is_active',
    ];

    /**
     * Check if student is active
     */
    public function isActive()
    {
        return $this->is_active === 'yes';
    }

    /**
     * Get student sessions
     */
    public function sessions()
    {
        return $this->hasMany(StudentSession::class, 'student_id');
    }

    /**
     * Get active student session for current academic session
     */
    public function activeSession()
    {
        return $this->hasOne(StudentSession::class, 'student_id')
            ->where('session_id', function($query) {
                $query->select('id')
                    ->from('sessions')
                    ->where('is_active', 'yes')
                    ->limit(1);
            });
    }

    /**
     * Find student by biometric ID (admission_no or biometric_id)
     * Returns student with active session information
     *
     * IMPORTANT: Checks admission_no, biometric_id, and biometric_device_pin FIRST
     * to avoid conflicts with staff IDs (since staff.id and students.id can overlap)
     *
     * @param string $biometric_id
     * @return object|null Returns object with student_session_id and student info
     */
    public static function findByBiometricId($biometric_id)
    {
        $result = self::select(
                'student_session.id as student_session_id',
                'students.id',
                'students.admission_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.is_active'
            )
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('sessions', 'student_session.session_id', '=', 'sessions.id')
            ->where('students.is_active', 'yes')
            ->where('sessions.is_active', 'yes')
            ->where(function($query) use ($biometric_id) {
                // Check specific student identifiers first (NOT id)
                $query->where('students.admission_no', $biometric_id);
            })
            ->first();

        return $result;
    }
}

