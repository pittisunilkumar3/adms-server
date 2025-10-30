<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * StaffAttendance Model
 * Represents staff attendance records
 */
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
        'time_range_id',
        'check_in_time',
        'check_out_time',
        'biometric_device_data',
        'remark',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'biometric_attendence' => 'boolean',
        'is_authorized_range' => 'boolean',
        'is_active' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'date',
    ];

    /**
     * Get the staff member
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Get the time range
     */
    public function timeRange()
    {
        return $this->belongsTo(BiometricTimingSetup::class, 'time_range_id');
    }

    /**
     * Check if attendance already exists for staff on a specific date
     *
     * @param int $staff_id
     * @param string $date
     * @return bool
     */
    public static function existsForDate($staff_id, $date)
    {
        return self::where('staff_id', $staff_id)
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
        if (self::existsForDate($data['staff_id'], $data['date'])) {
            return false;
        }

        // Create new record
        self::create($data);
        return true;
    }
}

