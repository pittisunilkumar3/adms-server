-- ============================================================================
-- MULTIPLE TIME RANGE ASSIGNMENT MANAGEMENT
-- ============================================================================
-- This script helps you manage multiple time range assignments for staff/students
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. VIEW CURRENT ASSIGNMENTS
-- ----------------------------------------------------------------------------

-- View all staff with their assigned time ranges
SELECT 
    s.id AS staff_id,
    s.name,
    s.employee_id,
    COUNT(stra.id) AS total_ranges,
    GROUP_CONCAT(bts.range_name ORDER BY bts.priority SEPARATOR ' | ') AS assigned_ranges,
    GROUP_CONCAT(CONCAT(bts.time_start, '-', bts.time_end) ORDER BY bts.priority SEPARATOR ' | ') AS time_ranges
FROM staff s
LEFT JOIN staff_time_range_assignments stra ON s.id = stra.staff_id AND stra.is_active = 1
LEFT JOIN biometric_timing_setup bts ON stra.time_range_id = bts.id AND bts.is_active = 1
GROUP BY s.id, s.name, s.employee_id
ORDER BY total_ranges DESC, s.name;

-- View staff with MULTIPLE time ranges (more than 1)
SELECT 
    s.id AS staff_id,
    s.name,
    s.employee_id,
    COUNT(stra.id) AS total_ranges,
    GROUP_CONCAT(bts.range_name ORDER BY bts.priority SEPARATOR ' | ') AS assigned_ranges
FROM staff s
JOIN staff_time_range_assignments stra ON s.id = stra.staff_id
JOIN biometric_timing_setup bts ON stra.time_range_id = bts.id
WHERE stra.is_active = 1 AND bts.is_active = 1
GROUP BY s.id, s.name, s.employee_id
HAVING total_ranges > 1
ORDER BY total_ranges DESC;

-- View detailed assignments for a specific staff member
-- Replace 6 with your staff_id
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
    bts.is_active AS range_active,
    stra.is_active AS assignment_active,
    stra.created_at AS assigned_on
FROM staff_time_range_assignments stra
JOIN staff s ON stra.staff_id = s.id
JOIN biometric_timing_setup bts ON stra.time_range_id = bts.id
WHERE s.id = 6  -- Change this to your staff ID
ORDER BY bts.priority ASC;

-- ----------------------------------------------------------------------------
-- 2. ASSIGN MULTIPLE TIME RANGES TO STAFF
-- ----------------------------------------------------------------------------

-- Example 1: Assign 2 time ranges to Staff ID 6 (Morning + Evening)
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active, created_at) VALUES
(6, 1, 1, NOW()),  -- Morning Check-in: 08:00-09:00
(6, 5, 1, NOW())   -- Evening Check-out: 17:00-19:00
ON DUPLICATE KEY UPDATE is_active = 1, updated_at = NOW();

-- Example 2: Assign 3 time ranges to Staff ID 7 (Morning + Afternoon + Evening)
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active, created_at) VALUES
(7, 1, 1, NOW()),  -- Morning Check-in
(7, 3, 1, NOW()),  -- Afternoon Check-in
(7, 5, 1, NOW())   -- Evening Check-out
ON DUPLICATE KEY UPDATE is_active = 1, updated_at = NOW();

-- Example 3: Assign ALL available time ranges to Staff ID 8 (Flexible staff)
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active, created_at)
SELECT 8, id, 1, NOW()
FROM biometric_timing_setup
WHERE is_active = 1
ON DUPLICATE KEY UPDATE is_active = 1, updated_at = NOW();

-- ----------------------------------------------------------------------------
-- 3. MODIFY EXISTING ASSIGNMENTS
-- ----------------------------------------------------------------------------

-- Add a new time range to existing staff assignments
-- Add Afternoon range to Staff ID 6
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active, created_at) VALUES
(6, 3, 1, NOW())  -- Afternoon Check-in
ON DUPLICATE KEY UPDATE is_active = 1, updated_at = NOW();

-- Deactivate a specific time range assignment (without deleting)
UPDATE staff_time_range_assignments 
SET is_active = 0, updated_at = NOW()
WHERE staff_id = 6 AND time_range_id = 3;

-- Reactivate a deactivated assignment
UPDATE staff_time_range_assignments 
SET is_active = 1, updated_at = NOW()
WHERE staff_id = 6 AND time_range_id = 3;

-- Remove a specific time range assignment completely
DELETE FROM staff_time_range_assignments 
WHERE staff_id = 6 AND time_range_id = 3;

-- Remove ALL time range assignments for a staff member
DELETE FROM staff_time_range_assignments 
WHERE staff_id = 6;

-- Deactivate ALL assignments for a staff member (without deleting)
UPDATE staff_time_range_assignments 
SET is_active = 0, updated_at = NOW()
WHERE staff_id = 6;

-- ----------------------------------------------------------------------------
-- 4. BULK OPERATIONS
-- ----------------------------------------------------------------------------

-- Assign the same time ranges to multiple staff members
-- Assign Morning + Evening to Staff IDs 10, 11, 12
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active, created_at) VALUES
(10, 1, 1, NOW()), (10, 5, 1, NOW()),
(11, 1, 1, NOW()), (11, 5, 1, NOW()),
(12, 1, 1, NOW()), (12, 5, 1, NOW())
ON DUPLICATE KEY UPDATE is_active = 1, updated_at = NOW();

-- Copy time range assignments from one staff to another
-- Copy all assignments from Staff 6 to Staff 13
INSERT INTO staff_time_range_assignments (staff_id, time_range_id, is_active, created_at)
SELECT 13, time_range_id, is_active, NOW()
FROM staff_time_range_assignments
WHERE staff_id = 6
ON DUPLICATE KEY UPDATE is_active = VALUES(is_active), updated_at = NOW();

-- ----------------------------------------------------------------------------
-- 5. STUDENT TIME RANGE ASSIGNMENTS (Same logic as staff)
-- ----------------------------------------------------------------------------

-- View all students with their assigned time ranges
SELECT 
    ss.id AS student_session_id,
    st.firstname,
    st.lastname,
    st.admission_no,
    COUNT(stra.id) AS total_ranges,
    GROUP_CONCAT(bts.range_name ORDER BY bts.priority SEPARATOR ' | ') AS assigned_ranges
FROM student_session ss
JOIN students st ON ss.student_id = st.id
LEFT JOIN student_time_range_assignments stra ON ss.id = stra.student_session_id AND stra.is_active = 1
LEFT JOIN biometric_timing_setup bts ON stra.time_range_id = bts.id AND bts.is_active = 1
GROUP BY ss.id, st.firstname, st.lastname, st.admission_no
ORDER BY total_ranges DESC;

-- Assign multiple time ranges to a student
INSERT INTO student_time_range_assignments (student_session_id, time_range_id, is_active, created_at) VALUES
(1116, 1, 1, NOW()),  -- Morning Check-in
(1116, 5, 1, NOW())   -- Evening Check-out
ON DUPLICATE KEY UPDATE is_active = 1, updated_at = NOW();

-- ----------------------------------------------------------------------------
-- 6. VALIDATION & TESTING QUERIES
-- ----------------------------------------------------------------------------

-- Check if a specific punch time would match any assigned ranges for a staff
-- Replace 6 with staff_id and '08:30:00' with punch time
SELECT 
    s.id AS staff_id,
    s.name,
    bts.id AS time_range_id,
    bts.range_name,
    bts.time_start,
    bts.time_end,
    bts.priority,
    CASE 
        WHEN '08:30:00' BETWEEN bts.time_start AND bts.time_end THEN 'MATCH ✓'
        ELSE 'NO MATCH ✗'
    END AS match_status
FROM staff_time_range_assignments stra
JOIN staff s ON stra.staff_id = s.id
JOIN biometric_timing_setup bts ON stra.time_range_id = bts.id
WHERE s.id = 6
AND stra.is_active = 1
AND bts.is_active = 1
ORDER BY bts.priority ASC;

-- Find staff who have NO time range assignments
SELECT 
    s.id,
    s.name,
    s.employee_id
FROM staff s
LEFT JOIN staff_time_range_assignments stra ON s.id = stra.staff_id AND stra.is_active = 1
WHERE stra.id IS NULL
ORDER BY s.name;

-- Find staff who have overlapping time ranges
SELECT 
    s.id AS staff_id,
    s.name,
    bts1.range_name AS range1,
    bts1.time_start AS start1,
    bts1.time_end AS end1,
    bts2.range_name AS range2,
    bts2.time_start AS start2,
    bts2.time_end AS end2
FROM staff_time_range_assignments stra1
JOIN staff_time_range_assignments stra2 ON stra1.staff_id = stra2.staff_id AND stra1.id < stra2.id
JOIN staff s ON stra1.staff_id = s.id
JOIN biometric_timing_setup bts1 ON stra1.time_range_id = bts1.id
JOIN biometric_timing_setup bts2 ON stra2.time_range_id = bts2.id
WHERE stra1.is_active = 1 
AND stra2.is_active = 1
AND bts1.is_active = 1
AND bts2.is_active = 1
AND (
    (bts1.time_start BETWEEN bts2.time_start AND bts2.time_end)
    OR (bts1.time_end BETWEEN bts2.time_start AND bts2.time_end)
    OR (bts2.time_start BETWEEN bts1.time_start AND bts1.time_end)
    OR (bts2.time_end BETWEEN bts1.time_start AND bts1.time_end)
)
ORDER BY s.name, bts1.priority, bts2.priority;

-- ----------------------------------------------------------------------------
-- 7. ATTENDANCE ANALYSIS WITH MULTIPLE RANGES
-- ----------------------------------------------------------------------------

-- View attendance records grouped by time range for a staff member
SELECT 
    sa.date,
    s.name,
    bts.range_name,
    bts.time_start,
    bts.time_end,
    COUNT(*) AS punch_count,
    GROUP_CONCAT(TIME(sa.created_at) ORDER BY sa.created_at SEPARATOR ', ') AS punch_times
FROM staff_attendance sa
JOIN staff s ON sa.staff_id = s.id
LEFT JOIN biometric_timing_setup bts ON sa.time_range_id = bts.id
WHERE sa.staff_id = 6  -- Change to your staff ID
AND sa.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY sa.date, s.name, bts.range_name, bts.time_start, bts.time_end
ORDER BY sa.date DESC, bts.time_start;

-- Count attendance by time range for all staff
SELECT 
    bts.range_name,
    bts.time_start,
    bts.time_end,
    COUNT(DISTINCT sa.staff_id) AS unique_staff,
    COUNT(*) AS total_punches
FROM staff_attendance sa
JOIN biometric_timing_setup bts ON sa.time_range_id = bts.id
WHERE sa.date = CURDATE()
AND sa.biometric_attendence = 1
GROUP BY bts.range_name, bts.time_start, bts.time_end
ORDER BY bts.time_start;

-- Find staff who punched in multiple ranges on the same day
SELECT 
    sa.date,
    s.id AS staff_id,
    s.name,
    COUNT(DISTINCT sa.time_range_id) AS ranges_used,
    GROUP_CONCAT(DISTINCT bts.range_name ORDER BY bts.priority SEPARATOR ' | ') AS ranges
FROM staff_attendance sa
JOIN staff s ON sa.staff_id = s.id
JOIN biometric_timing_setup bts ON sa.time_range_id = bts.id
WHERE sa.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY sa.date, s.id, s.name
HAVING ranges_used > 1
ORDER BY sa.date DESC, ranges_used DESC;

-- ----------------------------------------------------------------------------
-- 8. CLEANUP & MAINTENANCE
-- ----------------------------------------------------------------------------

-- Remove duplicate assignments (keep only the most recent)
DELETE t1 FROM staff_time_range_assignments t1
INNER JOIN staff_time_range_assignments t2 
WHERE t1.staff_id = t2.staff_id 
AND t1.time_range_id = t2.time_range_id
AND t1.id < t2.id;

-- Remove assignments for inactive time ranges
DELETE stra FROM staff_time_range_assignments stra
LEFT JOIN biometric_timing_setup bts ON stra.time_range_id = bts.id
WHERE bts.id IS NULL OR bts.is_active = 0;

-- Remove assignments for inactive staff
DELETE stra FROM staff_time_range_assignments stra
LEFT JOIN staff s ON stra.staff_id = s.id
WHERE s.id IS NULL;

-- ----------------------------------------------------------------------------
-- 9. REPORTING
-- ----------------------------------------------------------------------------

-- Summary report: Staff time range assignment statistics
SELECT 
    COUNT(DISTINCT s.id) AS total_staff,
    COUNT(DISTINCT CASE WHEN stra.id IS NOT NULL THEN s.id END) AS staff_with_assignments,
    COUNT(DISTINCT CASE WHEN stra.id IS NULL THEN s.id END) AS staff_without_assignments,
    AVG(range_count) AS avg_ranges_per_staff
FROM staff s
LEFT JOIN (
    SELECT staff_id, COUNT(*) AS range_count
    FROM staff_time_range_assignments
    WHERE is_active = 1
    GROUP BY staff_id
) stra ON s.id = stra.staff_id;

-- Distribution of time range assignments
SELECT 
    range_count AS ranges_assigned,
    COUNT(*) AS staff_count
FROM (
    SELECT staff_id, COUNT(*) AS range_count
    FROM staff_time_range_assignments
    WHERE is_active = 1
    GROUP BY staff_id
) AS counts
GROUP BY range_count
ORDER BY range_count;

