<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * StaffTimeRangeAssignment Model
 * Represents assignments of timing ranges to staff members
 */
class StaffTimeRangeAssignment extends Model
{
    use HasFactory;

    protected $table = 'staff_time_range_assignments';

    protected $fillable = [
        'staff_id',
        'time_range_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'staff_id' => 'integer',
        'time_range_id' => 'integer',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    /**
     * Get the staff member
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Get the timing range
     */
    public function timingRange()
    {
        return $this->belongsTo(BiometricTimingSetup::class, 'time_range_id');
    }

    /**
     * Get active assignments for a staff member
     */
    public static function getActiveAssignments($staffId)
    {
        return self::where('staff_id', $staffId)
            ->where('is_active', 1)
            ->with('timingRange')
            ->get();
    }

    /**
     * Get assigned timing range IDs for a staff member
     */
    public static function getAssignedRangeIds($staffId)
    {
        return self::where('staff_id', $staffId)
            ->where('is_active', 1)
            ->pluck('time_range_id')
            ->toArray();
    }

    /**
     * Check if staff has any timing assignments
     */
    public static function hasAssignments($staffId)
    {
        return self::where('staff_id', $staffId)
            ->where('is_active', 1)
            ->exists();
    }
}
