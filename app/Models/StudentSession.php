<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * StudentSession Model
 * Represents student enrollment in a specific academic session
 */
class StudentSession extends Model
{
    protected $table = 'student_session';
    
    public $timestamps = false;
    
    protected $fillable = [
        'student_id',
        'session_id',
        'class_id',
        'section_id',
    ];

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get student attendance records
     */
    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class, 'student_session_id');
    }
}

