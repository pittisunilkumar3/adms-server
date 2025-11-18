<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StaffAttendance;
use App\Models\StudentAttendance;
use App\Models\BiometricTimingSetup;

/**
 * Consolidated Attendance Controller
 * Handles all attendance-related operations:
 * - Device handshake
 * - Receiving attendance data from biometric devices (staff AND students)
 * - Displaying attendance records
 * - User type detection (staff vs student)
 */
class AttendanceController extends Controller
{
    /**
     * Display attendance records (both staff and students)
     * GET /attendance
     */
    public function index()
    {
        // Get staff attendance with staff details
        $staffAttendances = DB::table('staff_attendance')
            ->select(
                'staff_attendance.id',
                'staff_attendance.date',
                'staff_attendance.created_at',
                'staff_attendance.biometric_attendence',
                'staff_attendance.is_authorized_range',
                'staff_attendance.remark',
                DB::raw("'staff' as user_type"),
                DB::raw("CONCAT(staff.name, ' ', staff.surname) as user_name"),
                'staff.employee_id as user_identifier'
            )
            ->join('staff', 'staff_attendance.staff_id', '=', 'staff.id')
            ->where('staff_attendance.biometric_attendence', 1);

        // Get student attendance with student details
        $studentAttendances = DB::table('student_attendences')
            ->select(
                'student_attendences.id',
                'student_attendences.date',
                'student_attendences.created_at',
                'student_attendences.biometric_attendence',
                'student_attendences.is_authorized_range',
                'student_attendences.remark',
                DB::raw("'student' as user_type"),
                DB::raw("CONCAT(students.firstname, ' ', students.middlename, ' ', students.lastname) as user_name"),
                'students.admission_no as user_identifier'
            )
            ->join('student_session', 'student_attendences.student_session_id', '=', 'student_session.id')
            ->join('students', 'student_session.student_id', '=', 'students.id')
            ->where('student_attendences.biometric_attendence', 1);

        // Union both queries and paginate
        $attendances = $staffAttendances
            ->union($studentAttendances)
            ->orderBy('created_at', 'DESC')
            ->paginate(15);

        return view('devices.attendance', compact('attendances'));
    }

    /**
     * Device handshake endpoint
     * Responds to biometric device connection requests
      * GET /iclock/cdata
     * EXPLICIT CONTROL: TimeZone=330 sent ONLY when ZKTECO_AUTO_SYNC_TIME=true in .env   
     */
    public function handshake(Request $request)
            {
        $response = "GET OPTION FROM: {$request->input('SN')}\r\n" .
                    "Stamp=9999\r\n" .
                    "OpStamp=" . time() . "\r\n" .
                    "ErrorDelay=60\r\n" .
                    "Delay=30\r\n" .
                    "ResLogDay=18250\r\n" .
                    "ResLogDelCount=10000\r\n" .
                    "ResLogCount=50000\r\n" .
                    "TransTimes=00:00;14:05\r\n" .
                    "TransInterval=1\r\n" .
                    "TransFlag=1111000000\r\n" .
                    "Realtime=1\r\n" .
"Encrypt=0" .
            ($this->shouldSyncDeviceTime() ? "TimeZone=330\r\n" : "");
        return response($response, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Receive attendance records from biometric devices
     * Automatically detects user type (staff or student) and stores in appropriate table
     * POST /iclock/cdata
     */
    public function store(Request $request)
    {
        try {
            $arr = preg_split('/\\r\\n|\\r|,|\\n/', $request->getContent());
            $tot = 0;

            // Ignore operation logs
            if ($request->input('table') == "OPERLOG") {
                foreach ($arr as $rey) {
                    if (isset($rey)) {
                        $tot++;
                    }
                }
                return response("OK: " . $tot, 200)
                    ->header('Content-Type', 'text/plain');
            }

            // Process attendance records - INSERT NEW RECORD FOR EACH PUNCH
            foreach ($arr as $rey) {
                if (empty($rey)) {
                    continue;
                }

                $data = explode("\t", $rey);
                $timestamp = $data[1];
                $date = Carbon::parse($timestamp)->format('Y-m-d');
                $user_id = $data[0]; // This could be staff ID or student ID

                // Prepare biometric device data
                $biometric_device_data = json_encode([
                    'sn' => $request->input('SN'),
                    'table' => $request->input('table'),
                    'stamp' => $request->input('Stamp'),
                    'timestamp' => $timestamp,
                    'status1' => isset($data[2]) && $data[2] !== '' ? (int)$data[2] : null,
                    'status2' => isset($data[3]) && $data[3] !== '' ? (int)$data[3] : null,
                    'status3' => isset($data[4]) && $data[4] !== '' ? (int)$data[4] : null,
                    'status4' => isset($data[5]) && $data[5] !== '' ? (int)$data[5] : null,
                    'status5' => isset($data[6]) && $data[6] !== '' ? (int)$data[6] : null,
                ]);

                // Identify user type and insert into appropriate table
                $userInfo = $this->identifyUserType($user_id);

                if ($userInfo) {
                    if ($userInfo['type'] === 'staff') {
                        // Insert staff attendance
                        $inserted = $this->insertStaffAttendance(
                            $userInfo['id'],
                            $date,
                            $timestamp,
                            $biometric_device_data
                        );
                        if ($inserted) {
                            $tot++;
                        }
                    } elseif ($userInfo['type'] === 'student') {
                        // Insert student attendance
                        $inserted = $this->insertStudentAttendance(
                            $userInfo['student_session_id'],
                            $date,
                            $timestamp,
                            $biometric_device_data
                        );
                        if ($inserted) {
                            $tot++;
                        }
                    }
                }
                // If user not found, skip silently (no error)
            }

            return response("OK: " . $tot, 200)
                ->header('Content-Type', 'text/plain');

        } catch (\Throwable $e) {
            // No logging - return error response silently
            return response("ERROR: 0\n", 500)
                ->header('Content-Type', 'text/plain');
        }
    }

    /**
     * Identify user type (staff or student) based on biometric ID
     *
     * IMPORTANT: Now checks employee_id/admission_no first to avoid ID conflicts
     * Staff and students can have overlapping database IDs (staff.id = students.id)
     *
     * @param string $user_id Biometric ID from device
     * @return array|null Returns ['type' => 'staff'|'student', 'id' => int, ...] or null
     */
    private function identifyUserType($user_id)
    {
        // First, check if it's a staff member (by employee_id, biometric_id, biometric_device_pin)
        $staff = Staff::findByBiometricId($user_id);
        if ($staff) {
            // Log for debugging
            \Log::info("User identified as STAFF", [
                'device_id' => $user_id,
                'staff_id' => $staff->id,
                'employee_id' => $staff->employee_id,
                'name' => $staff->name . ' ' . $staff->surname
            ]);

            return [
                'type' => 'staff',
                'id' => $staff->id,
                'name' => $staff->name . ' ' . $staff->surname,
            ];
        }

        // Second, check if it's a student (by admission_no, biometric_id, biometric_device_pin)
        $student = Student::findByBiometricId($user_id);
        if ($student) {
            // Log for debugging
            \Log::info("User identified as STUDENT", [
                'device_id' => $user_id,
                'student_id' => $student->id,
                'admission_no' => $student->admission_no,
                'student_session_id' => $student->student_session_id,
                'name' => $student->firstname . ' ' . $student->middlename . ' ' . $student->lastname
            ]);

            return [
                'type' => 'student',
                'id' => $student->id,
                'student_session_id' => $student->student_session_id,
                'name' => $student->firstname . ' ' . $student->middlename . ' ' . $student->lastname,
            ];
        }

        // User not found in either table
        \Log::warning("User NOT FOUND in staff or students table", [
            'device_id' => $user_id
        ]);

        return null;
    }

    /**
     * Insert staff attendance record
     * Validates punch time against assigned time range
     * Prevents duplicates based on staff_id, date, and time_range_id
     *
     * @param int $staff_id
     * @param string $date
     * @param string $timestamp
     * @param string $biometric_device_data
     * @return bool
     */
    private function insertStaffAttendance($staff_id, $date, $timestamp, $biometric_device_data)
    {
        $punchTime = Carbon::parse($timestamp)->format('H:i:s');

        // OPTIMIZED: Use single SQL query to find matching time range from assigned ranges
        // This filters at database level instead of looping in PHP
        $matchedTimeRange = DB::table('staff_time_range_assignments as stra')
            ->join('biometric_timing_setup as bts', 'stra.time_range_id', '=', 'bts.id')
            ->where('stra.staff_id', $staff_id)
            ->where('stra.is_active', 1)
            ->where('bts.is_active', 1)
            ->whereRaw('? BETWEEN bts.time_start AND bts.time_end', [$punchTime])
            ->orderBy('bts.priority', 'asc')
            ->select('bts.*')
            ->first();

        $timeRange = null;
        $timeRangeId = null;
        $checkInTime = null;
        $checkOutTime = null;
        $isAuthorizedRange = 0; // Default to unauthorized

        // If no matching assigned time range found, do nothing (reject silently)
        if (!$matchedTimeRange) {
            return false;
        }

        // Punch matched one of the assigned time ranges - AUTHORIZED
        $timeRange = $matchedTimeRange;
        $timeRangeId = $timeRange->id;
        $isAuthorizedRange = 1;

        // Set check-in or check-out time based on range type
        if ($timeRange->range_type === 'checkin') {
            $checkInTime = $timeRange->time_start;
        } elseif ($timeRange->range_type === 'checkout') {
            $checkOutTime = $timeRange->time_end;
        }

        \Log::info("Staff punch matched assigned time range", [
            'staff_id' => $staff_id,
            'punch_time' => $punchTime,
            'matched_range' => $timeRange->range_name,
            'range_start' => $timeRange->time_start,
            'range_end' => $timeRange->time_end,
            'priority' => $timeRange->priority
        ]);

        // Check for duplicate: same staff_id, date, and time_range_id
        $existingRecord = DB::table('staff_attendance')
            ->where('staff_id', $staff_id)
            ->where('date', $date)
            ->where('time_range_id', $timeRangeId)
            ->first();

        // If duplicate exists, skip insertion
        if ($existingRecord) {
            \Log::info("Duplicate staff attendance skipped", [
                'staff_id' => $staff_id,
                'date' => $date,
                'time_range_id' => $timeRangeId,
                'timestamp' => $timestamp
            ]);
            return false;
        }

        $attendanceData = [
            'date' => $date,
            'staff_id' => $staff_id,
            'staff_attendance_type_id' => 1, // 1 = Present
            'biometric_attendence' => 1,
            'is_authorized_range' => $isAuthorizedRange,
            'time_range_id' => $timeRangeId,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'biometric_device_data' => $biometric_device_data,
            'remark' => 'Auto-recorded from biometric device at ' . $timestamp,
            'is_active' => 1,
            'created_at' => Carbon::parse($timestamp),
            'updated_at' => Carbon::parse($timestamp),
        ];

        // Insert new record
        DB::table('staff_attendance')->insert($attendanceData);

        \Log::info("Staff attendance recorded successfully", [
            'staff_id' => $staff_id,
            'date' => $date,
            'time_range_id' => $timeRangeId,
            'is_authorized_range' => $isAuthorizedRange,
            'timestamp' => $timestamp
        ]);

        return true;
    }

    /**
     * Insert student attendance record
     * Validates punch time against assigned time range
     * Prevents duplicates based on student_session_id, date, and time_range_id
     *
     * @param int $student_session_id
     * @param string $date
     * @param string $timestamp
     * @param string $biometric_device_data
     * @return bool
     */
    private function insertStudentAttendance($student_session_id, $date, $timestamp, $biometric_device_data)
    {
        $punchTime = Carbon::parse($timestamp)->format('H:i:s');

        // OPTIMIZED: Use single SQL query to find matching time range from assigned ranges
        // This filters at database level instead of looping in PHP
        $matchedTimeRange = DB::table('student_time_range_assignments as stra')
            ->join('biometric_timing_setup as bts', 'stra.time_range_id', '=', 'bts.id')
            ->where('stra.student_session_id', $student_session_id)
            ->where('stra.is_active', 1)
            ->where('bts.is_active', 1)
            ->whereRaw('? BETWEEN bts.time_start AND bts.time_end', [$punchTime])
            ->orderBy('bts.priority', 'asc')
            ->select('bts.*')
            ->first();

        $timeRange = null;
        $timeRangeId = null;
        $checkInTime = null;
        $checkOutTime = null;
        $isAuthorizedRange = 0; // Default to unauthorized

        // If no matching assigned time range found, do nothing (reject silently)
        if (!$matchedTimeRange) {
            return false;
        }

        // Punch matched one of the assigned time ranges - AUTHORIZED
        $timeRange = $matchedTimeRange;
        $timeRangeId = $timeRange->id;
        $isAuthorizedRange = 1;

        // Set check-in or check-out time based on range type
        if ($timeRange->range_type === 'checkin') {
            $checkInTime = $timeRange->time_start;
        } elseif ($timeRange->range_type === 'checkout') {
            $checkOutTime = $timeRange->time_end;
        }

        \Log::info("Student punch matched assigned time range", [
            'student_session_id' => $student_session_id,
            'punch_time' => $punchTime,
            'matched_range' => $timeRange->range_name,
            'range_start' => $timeRange->time_start,
            'range_end' => $timeRange->time_end,
            'priority' => $timeRange->priority
        ]);

        // Check for duplicate: same student_session_id, date, and time_range_id
        $existingRecord = DB::table('student_attendences')
            ->where('student_session_id', $student_session_id)
            ->where('date', $date)
            ->where('time_range_id', $timeRangeId)
            ->first();

        // If duplicate exists, skip insertion
        if ($existingRecord) {
            \Log::info("Duplicate student attendance skipped", [
                'student_session_id' => $student_session_id,
                'date' => $date,
                'time_range_id' => $timeRangeId,
                'timestamp' => $timestamp
            ]);
            return false;
        }

        $attendanceData = [
            'date' => $date,
            'student_session_id' => $student_session_id,
            'attendence_type_id' => 1, // 1 = Present
            'biometric_attendence' => 1,
            'is_authorized_range' => $isAuthorizedRange,
            'time_range_id' => $timeRangeId,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'biometric_device_data' => $biometric_device_data,
            'remark' => 'Auto-recorded from biometric device at ' . $timestamp,
            'created_at' => Carbon::parse($timestamp),
        ];

        // Insert new record
        DB::table('student_attendences')->insert($attendanceData);

        \Log::info("Student attendance recorded successfully", [
            'student_session_id' => $student_session_id,
            'date' => $date,
            'time_range_id' => $timeRangeId,
            'is_authorized_range' => $isAuthorizedRange,
            'timestamp' => $timestamp
        ]);

        return true;
    }
}

    /**
     * Check if device time sync is enabled - EXPLICIT CONTROL
     * Default: false (disabled) - sync only when explicitly enabled
     * Prevents automatic time changes on device connection
     */
    private function shouldSyncDeviceTime()
    {
        $syncEnabled = env('ZKTECO_AUTO_SYNC_TIME', false);
        \Log::debug('Device time sync check', ['enabled' => $syncEnabled]);
        return (bool)$syncEnabled;
    }

