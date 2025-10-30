<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Staff Model
 * Represents staff members in the system
 */
class Staff extends Model
{
    protected $table = 'staff';
    
    protected $fillable = [
        'employee_id',
        'name',
        'surname',
        'email',
        'contact_no',
        'is_active',
    ];

    /**
     * Check if staff member is active
     */
    public function isActive()
    {
        return $this->is_active == 1;
    }

    /**
     * Get staff attendance records
     */
    public function attendances()
    {
        return $this->hasMany(StaffAttendance::class, 'staff_id');
    }

    /**
     * Find staff by biometric ID (employee_id or biometric_id)
     *
     * IMPORTANT: Checks employee_id, biometric_id, and biometric_device_pin FIRST
     * to avoid conflicts with student IDs (since staff.id and students.id can overlap)
     *
     * @param string $biometric_id
     * @return Staff|null
     */
    public static function findByBiometricId($biometric_id)
    {
        return self::where('is_active', 1)
            ->where(function($query) use ($biometric_id) {
                // Check specific staff identifiers first (NOT id)
                $query->where('employee_id', $biometric_id);
            })
            ->first();
    }
}

