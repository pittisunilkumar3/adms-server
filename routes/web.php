<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

/*
|--------------------------------------------------------------------------
| Attendance System Routes
|--------------------------------------------------------------------------
| Simple routes for biometric attendance system:
| - View attendance records
| - Device handshake and data reception
|--------------------------------------------------------------------------
*/

// View attendance records
Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

// Device communication endpoints (no middleware, no authentication)
Route::get('/iclock/cdata', [AttendanceController::class, 'handshake']);
Route::post('/iclock/cdata', [AttendanceController::class, 'store']);

// Root redirect
Route::get('/', function () {
    return redirect('/attendance');
});
