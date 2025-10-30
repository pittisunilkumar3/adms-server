# Multiple Time Range Assignment Support

## Overview
The attendance system now supports **multiple time range assignments** for both staff and students. This allows a single staff member or student to have multiple valid time ranges throughout the day.

---

## ✨ What's New?

### Previous Behavior (Single Time Range)
- System would only check the **first** assigned time range
- If punch was outside that range, it would be rejected
- **Problem**: Staff with multiple shifts couldn't punch in different time ranges

### New Behavior (Multiple Time Ranges)
- System retrieves **ALL** assigned time ranges for the user
- Loops through all assigned ranges to find a match
- If punch matches **ANY** assigned range, it's accepted
- **Benefit**: Staff can work multiple shifts and punch in any of their assigned time ranges

---

## 🎯 How It Works

### For Staff Members

#### Step 1: Retrieve All Assigned Time Ranges
```php
// Get ALL assigned time ranges for this staff member
$assignedTimeRanges = DB::table('staff_time_range_assignments')
    ->where('staff_id', $staff_id)
    ->where('is_active', 1)
    ->pluck('time_range_id')
    ->toArray();
```

#### Step 2: Get Time Range Details
```php
// Get all assigned time ranges with their details
$timeRanges = BiometricTimingSetup::whereIn('id', $assignedTimeRanges)
    ->where('is_active', 1)
    ->orderBy('priority', 'asc')
    ->get();
```

#### Step 3: Find Matching Time Range
```php
// Loop through all assigned time ranges to find which one matches the punch time
foreach ($timeRanges as $range) {
    if ($range->isTimeInRange($punchTime)) {
        $matchedTimeRange = $range;
        break; // Found a match, stop searching
    }
}
```

#### Step 4: Accept or Reject
- **If match found**: Record attendance with matched time_range_id
- **If no match found**: Reject punch and log warning

---

## 📊 Example Scenarios

### Scenario 1: Staff with Multiple Shifts

**Setup:**
```sql
-- Staff ID 6 has 3 assigned time ranges
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active) VALUES
(6, 1, 1),  -- Morning Check-in: 08:00-09:00
(6, 3, 1),  -- Afternoon Check-in: 13:00-14:00
(6, 5, 1);  -- Evening Check-out: 17:00-19:00
```

**Punch Examples:**

| Punch Time | Result | Matched Range | Reason |
|------------|--------|---------------|--------|
| 08:30 AM | ✅ Accepted | Morning Check-in | Within 08:00-09:00 |
| 10:30 AM | ❌ Rejected | None | Not in any assigned range |
| 13:30 PM | ✅ Accepted | Afternoon Check-in | Within 13:00-14:00 |
| 18:00 PM | ✅ Accepted | Evening Check-out | Within 17:00-19:00 |
| 22:00 PM | ❌ Rejected | None | Not in any assigned range |

### Scenario 2: Staff with Overlapping Ranges

**Setup:**
```sql
-- Staff ID 7 has overlapping time ranges (priority matters)
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active) VALUES
(7, 1, 1),  -- Morning Check-in: 08:00-09:00 (Priority 1)
(7, 2, 1);  -- Morning Check-in (Late): 09:00-10:00 (Priority 2)
```

**Punch at 08:30 AM:**
- System checks ranges in priority order (1, 2, 3...)
- Matches **Morning Check-in** (Priority 1) first
- Records attendance with time_range_id = 1

---

## 🔍 Database Queries

### View Staff with Multiple Time Ranges
```sql
SELECT 
    s.id AS staff_id,
    s.name,
    s.employee_id,
    COUNT(stra.id) AS total_ranges,
    GROUP_CONCAT(bts.range_name SEPARATOR ', ') AS assigned_ranges,
    GROUP_CONCAT(CONCAT(bts.time_start, '-', bts.time_end) SEPARATOR ', ') AS time_ranges
FROM staff s
JOIN staff_time_range_assignments stra ON s.id = stra.staff_id
JOIN biometric_timing_setup bts ON stra.time_range_id = bts.id
WHERE stra.is_active = 1
GROUP BY s.id, s.name, s.employee_id
HAVING total_ranges > 1
ORDER BY total_ranges DESC;
```

### View All Assignments for a Specific Staff
```sql
SELECT 
    s.id AS staff_id,
    s.name,
    s.employee_id,
    bts.id AS time_range_id,
    bts.range_name,
    bts.range_type,
    bts.time_start,
    bts.time_end,
    bts.priority,
    stra.is_active
FROM staff_time_range_assignments stra
JOIN staff s ON stra.staff_id = s.id
JOIN biometric_timing_setup bts ON stra.time_range_id = bts.id
WHERE s.id = 6  -- Replace with your staff ID
AND stra.is_active = 1
ORDER BY bts.priority ASC;
```

### Assign Multiple Time Ranges to Staff
```sql
-- Assign multiple time ranges to Staff ID 6
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active, created_at) VALUES
(6, 1, 1, NOW()),  -- Morning Check-in
(6, 3, 1, NOW()),  -- Afternoon Check-in
(6, 5, 1, NOW());  -- Evening Check-out
```

### Remove a Time Range Assignment
```sql
-- Deactivate a specific assignment
UPDATE staff_time_range_assignments 
SET is_active = 0 
WHERE staff_id = 6 AND time_range_id = 3;

-- Or delete it completely
DELETE FROM staff_time_range_assignments 
WHERE staff_id = 6 AND time_range_id = 3;
```

---

## 📝 Logging

### Success Log (Match Found)
```
[INFO] Staff punch matched assigned time range
{
    "staff_id": 6,
    "punch_time": "08:30:00",
    "matched_range": "Morning Check-in",
    "range_start": "08:00:00",
    "range_end": "09:00:00",
    "total_assigned_ranges": 3
}
```

### Rejection Log (No Match)
```
[WARNING] Staff punch REJECTED - outside all assigned time ranges
{
    "staff_id": 6,
    "punch_time": "10:30:00",
    "assigned_ranges": ["Morning Check-in", "Afternoon Check-in", "Evening Check-out"],
    "total_assigned_ranges": 3,
    "timestamp": "2025-10-30 10:30:00"
}
```

---

## 🔧 Configuration Examples

### Example 1: Full-Time Staff (Morning + Evening)
```sql
-- Staff works morning and evening shifts
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active) VALUES
(10, 1, 1),  -- Morning Check-in: 08:00-09:00
(10, 5, 1);  -- Evening Check-out: 17:00-19:00
```

### Example 2: Part-Time Staff (Afternoon Only)
```sql
-- Staff works only afternoon shift
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active) VALUES
(11, 3, 1);  -- Afternoon Check-in: 13:00-14:00
```

### Example 3: Flexible Staff (All Shifts)
```sql
-- Staff can work any shift
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active) VALUES
(12, 1, 1),  -- Morning Check-in: 08:00-09:00
(12, 2, 1),  -- Morning Check-in (Late): 09:00-10:00
(12, 3, 1),  -- Afternoon Check-in: 13:00-14:00
(12, 4, 1),  -- Afternoon Check-out: 15:00-16:00
(12, 5, 1),  -- Evening Check-out: 17:00-19:00
(12, 6, 1);  -- Evening Check-out (Late): 19:00-20:00
```

---

## ⚠️ Important Notes

### 1. Priority Matters
- When multiple ranges overlap, the system uses **priority** (lower number = higher priority)
- The first matching range (by priority) is used
- Example: If ranges 1 and 2 both match, range with lower priority number wins

### 2. Unique Constraint
- The table has a **UNIQUE KEY** on `(staff_id, time_range_id)`
- You cannot assign the same time range twice to the same staff member
- Attempting to do so will result in a database error

### 3. Active Status
- Only `is_active = 1` assignments are considered
- Deactivated assignments (`is_active = 0`) are ignored
- This allows temporary disabling without deleting records

### 4. Duplicate Prevention Still Works
- Duplicate prevention is based on: `staff_id + date + time_range_id`
- If staff punches multiple times in the same range on the same date, only the first is recorded
- Staff can punch in different ranges on the same date (e.g., morning check-in + evening check-out)

---

## 🧪 Testing

### Test Case 1: Multiple Valid Punches
```sql
-- Setup: Staff 6 has 3 time ranges
-- Punch 1: 08:30 (Morning) - Should be accepted
-- Punch 2: 13:30 (Afternoon) - Should be accepted
-- Punch 3: 18:00 (Evening) - Should be accepted

-- Expected Result: 3 attendance records with different time_range_id values
SELECT * FROM staff_attendance 
WHERE staff_id = 6 
AND date = CURDATE()
ORDER BY created_at;
```

### Test Case 2: Invalid Punch Between Ranges
```sql
-- Setup: Staff 6 has ranges at 08:00-09:00 and 13:00-14:00
-- Punch: 10:30 (Between ranges) - Should be rejected

-- Expected Result: No attendance record, warning in logs
-- Check logs: grep "REJECTED" storage/logs/laravel.log
```

### Test Case 3: Duplicate in Same Range
```sql
-- Setup: Staff 6 has Morning range (08:00-09:00)
-- Punch 1: 08:30 - Should be accepted
-- Punch 2: 08:45 - Should be rejected (duplicate)

-- Expected Result: Only 1 attendance record
SELECT COUNT(*) FROM staff_attendance 
WHERE staff_id = 6 
AND date = CURDATE()
AND time_range_id = 1;
-- Should return: 1
```

---

## 📈 Benefits

1. **Flexibility**: Staff can work multiple shifts per day
2. **Accuracy**: System matches punch to correct time range automatically
3. **Scalability**: Supports any number of time range assignments
4. **Priority-Based**: Handles overlapping ranges intelligently
5. **Audit Trail**: Logs show which range was matched and total assigned ranges
6. **Duplicate Prevention**: Still prevents duplicate punches in same range

---

## 🔄 Migration from Single to Multiple Ranges

If you previously had staff with single time range assignments, no changes are needed. The system is **backward compatible**:

- Staff with 1 assigned range: Works exactly as before
- Staff with multiple ranges: New functionality automatically applies
- Staff with no assigned ranges: Falls back to auto-matching any active range

---

## 📚 Files Modified

- `app/Http/Controllers/AttendanceController.php`
  - `insertStaffAttendance()` method (lines 252-403)
  - `insertStudentAttendance()` method (lines 404-544)

---

## 🎯 Summary

The system now supports **multiple time range assignments** per staff/student:

✅ Retrieves ALL assigned time ranges (not just first one)
✅ Loops through all ranges to find a match
✅ Uses priority order for overlapping ranges
✅ Accepts punch if it matches ANY assigned range
✅ Rejects punch if it doesn't match ANY assigned range
✅ Logs which range was matched and total assigned ranges
✅ Maintains duplicate prevention per range
✅ Backward compatible with single-range assignments

