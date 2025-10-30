<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class iclockController extends Controller
{
    /**
     * Device handshake endpoint
     * Responds to biometric device connection requests
     */
    public function handshake(Request $request)
    {
        $r = "GET OPTION FROM: {$request->input('SN')}\r\n" .
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

        return $r;
    }

    /**
     * Receive attendance records from biometric devices
     * Stores attendance data in staff_attendance table
     */
    public function receiveRecords(Request $request)
    {
        try {
            $arr = preg_split('/\\r\\n|\\r|,|\\n/', $request->getContent());
            $tot = 0;

            // Ignore operation logs
            if($request->input('table') == "OPERLOG"){
                foreach ($arr as $rey) {
                    if(isset($rey)){
                        $tot++;
                    }
                }
                return "OK: ".$tot;
            }

            // Process attendance records
            foreach ($arr as $rey) {
                if(empty($rey)){
                    continue;
                }
                $data = explode("\t",$rey);

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
            return "OK: ".$tot;
        } catch (\Throwable $e) {
            report($e);
            return "ERROR: ".$tot."\n";
        }
    }

}
