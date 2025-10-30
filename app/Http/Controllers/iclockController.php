<?php

namespace App\Http\Controllers;
use App\Models\StaffAttendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class iclockController extends Controller
{

   public function __invoke(Request $request)
   {

   }

    // handshake
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
        //$r = "GET OPTION FROM:%s{$request->SN}\nStamp=".strtotime('now')."\nOpStamp=1565089939\nErrorDelay=30\nDelay=10\nTransTimes=00:00;14:05\nTransInterval=1\nTransFlag=1111000000\nTimeZone=7\nRealtime=1\nEncrypt=0\n";
    // implementasi https://docs.nufaza.com/docs/devices/zkteco_attendance/push_protocol/
    // setting timezone
    // request absensi
    public function receiveRecords(Request $request)
    {
        try {
            // $post_content = $request->getContent();
            //$arr = explode("\n", $post_content);
            $arr = preg_split('/\\r\\n|\\r|,|\\n/', $request->getContent());
            //$tot = count($arr);
            $tot = 0;
            //operation log
            if($request->input('table') == "OPERLOG"){
                // $tot = count($arr) - 1;
                foreach ($arr as $rey) {
                    if(isset($rey)){
                        $tot++;
                    }
                }
                return "OK: ".$tot;
            }
            //attendance
            foreach ($arr as $rey) {
                // $data = preg_split('/\s+/', trim($rey));
                if(empty($rey)){
                    continue;
                }
                    // $data = preg_split('/\s+/', trim($rey));
                    $data = explode("\t",$rey);

                    // Parse the timestamp to extract date
                    $timestamp = $data[1];
                    $date = Carbon::parse($timestamp)->format('Y-m-d');

                    // Map employee_id to staff_id
                    $staff_id = $data[0]; // employee_id from device = staff_id in database

                    // Prepare biometric device data as JSON
                    $biometric_device_data = json_encode([
                        'sn' => $request->input('SN'),
                        'table' => $request->input('table'),
                        'stamp' => $request->input('Stamp'),
                        'timestamp' => $timestamp,
                        'status1' => $this->validateAndFormatInteger($data[2] ?? null),
                        'status2' => $this->validateAndFormatInteger($data[3] ?? null),
                        'status3' => $this->validateAndFormatInteger($data[4] ?? null),
                        'status4' => $this->validateAndFormatInteger($data[5] ?? null),
                        'status5' => $this->validateAndFormatInteger($data[6] ?? null),
                    ]);

                    // Prepare staff_attendance record
                    $attendanceData = [
                        'date' => $date,
                        'staff_id' => $staff_id,
                        'staff_attendance_type_id' => 1, // Default to 1 (Present) - can be configured
                        'biometric_attendence' => 1, // Mark as biometric attendance
                        'is_authorized_range' => 1, // Default to authorized - can be validated later
                        'biometric_device_data' => $biometric_device_data,
                        'remark' => 'Auto-recorded from biometric device',
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now()->format('Y-m-d'),
                    ];

                    // Insert or update attendance record (one record per staff per day)
                    DB::table('staff_attendance')->updateOrInsert(
                        [
                            'staff_id' => $staff_id,
                            'date' => $date
                        ],
                        $attendanceData
                    );

                    $tot++;
                // dd(DB::getQueryLog());
            }
            return "OK: ".$tot;
        } catch (\Throwable $e) {
            report($e);
            return "ERROR: ".$tot."\n";
        }
    }

    private function validateAndFormatInteger($value)
    {
        return isset($value) && $value !== '' ? (int)$value : null;
        // return is_numeric($value) ? (int) $value : null;
    }

}
