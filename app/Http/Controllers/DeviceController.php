<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StaffAttendance;

class DeviceController extends Controller
{
    public function Attendance() {
       // Fetch staff attendance records with staff information
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
}
