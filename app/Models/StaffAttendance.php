<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $table = 'staff_attendance';

    protected $fillable = [
        'date',
        'staff_id',
        'staff_attendance_type_id',
        'biometric_attendence',
        'is_authorized_range',
        'biometric_device_data',
        'remark',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'biometric_attendence' => 'boolean',
        'is_authorized_range' => 'boolean',
        'is_active' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'date',
    ];
}

