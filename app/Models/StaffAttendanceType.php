<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * StaffAttendanceType Model
 * Represents staff attendance types (Present, Late, Absent, etc.)
 */
class StaffAttendanceType extends Model
{
    use HasFactory;

    protected $table = 'staff_attendance_type';

    protected $fillable = [
        'type',
        'key_value',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'string', // Note: This table uses string 'yes'/'no' instead of boolean
    ];

    /**
     * Get all active attendance types
     */
    public static function getActiveTypes()
    {
        return self::where('is_active', 'yes')->get();
    }

    /**
     * Get attendance type by ID
     */
    public static function getTypeById($id)
    {
        return self::where('id', $id)
            ->where('is_active', 'yes')
            ->first();
    }

    /**
     * Get attendance type by key value
     */
    public static function getTypeByKey($keyValue)
    {
        return self::where('key_value', $keyValue)
            ->where('is_active', 'yes')
            ->first();
    }

    /**
     * Common attendance type constants
     */
    const TYPE_PRESENT = 1;
    const TYPE_LATE = 2;
    const TYPE_ABSENT = 3;
    const TYPE_HALF_DAY = 4;
    const TYPE_HOLIDAY = 5;
}
