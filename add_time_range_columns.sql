-- Add time range columns to staff_attendance table
ALTER TABLE `staff_attendance` 
ADD COLUMN `time_range_id` INT(11) NULL DEFAULT NULL AFTER `is_authorized_range`,
ADD COLUMN `check_in_time` TIME NULL DEFAULT NULL AFTER `time_range_id`,
ADD COLUMN `check_out_time` TIME NULL DEFAULT NULL AFTER `check_in_time`;

-- Add time range columns to student_attendences table
ALTER TABLE `student_attendences` 
ADD COLUMN `time_range_id` INT(11) NULL DEFAULT NULL AFTER `is_authorized_range`,
ADD COLUMN `check_in_time` TIME NULL DEFAULT NULL AFTER `time_range_id`,
ADD COLUMN `check_out_time` TIME NULL DEFAULT NULL AFTER `check_in_time`;

-- Add foreign key constraints (optional, but recommended)
ALTER TABLE `staff_attendance`
ADD CONSTRAINT `fk_staff_attendance_time_range` 
FOREIGN KEY (`time_range_id`) REFERENCES `biometric_timing_setup` (`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `student_attendences`
ADD CONSTRAINT `fk_student_attendance_time_range` 
FOREIGN KEY (`time_range_id`) REFERENCES `biometric_timing_setup` (`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

