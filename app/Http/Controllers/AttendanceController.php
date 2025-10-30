<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Consolidated Attendance Controller
 * Handles all attendance-related operations:
 * - Device handshake
 * - Receiving attendance data from biometric devices
 * - Displaying attendance records
 */
class AttendanceController extends Controller
{
    /**
     * Display attendance records
     * GET /attendance
     */
    public function index()
    {
        $attendances = DB::table('staff_attendance')
            ->select(
                'staff_attendance.id',
                'staff_attendance.date',
                'staff_attendance.staff_id',
                'staff_attendance.staff_attendance_type_id',
                'staff_attendance.biometric_attendence',
                'staff_attendance.is_authorized_range',
                'staff_attendance.biometric_device_data',
                'staff_attendance.remark',
                'staff_attendance.created_at'
            )
            ->orderBy('staff_attendance.id', 'DESC')
            ->paginate(15);

        return view('devices.attendance', compact('attendances'));
    }

    /**
     * Device handshake endpoint
     * Responds to biometric device connection requests
     * GET /iclock/cdata
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
                    "Encrypt=0";

        return response($response, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Receive attendance records from biometric devices
     * Stores attendance data in staff_attendance table
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

            // Process attendance records
            foreach ($arr as $rey) {
                if (empty($rey)) {
                    continue;
                }
                
                $data = explode("\t", $rey);
                $timestamp = $data[1];
                $date = Carbon::parse($timestamp)->format('Y-m-d');
                $staff_id = $data[0];

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

                $attendanceData = [
                    'date' => $date,
                    'staff_id' => $staff_id,
                    'staff_attendance_type_id' => 1,
                    'biometric_attendence' => 1,
                    'is_authorized_range' => 1,
                    'biometric_device_data' => $biometric_device_data,
                    'remark' => 'Auto-recorded from biometric device',
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now()->format('Y-m-d'),
                ];

                DB::table('staff_attendance')->updateOrInsert(
                    ['staff_id' => $staff_id, 'date' => $date],
                    $attendanceData
                );

                $tot++;
            }
            
            return response("OK: " . $tot, 200)
                ->header('Content-Type', 'text/plain');
                
        } catch (\Throwable $e) {
            report($e);
            return response("ERROR: 0\n", 500)
                ->header('Content-Type', 'text/plain');
        }
    }
}

