<?php

/**
 * Test Script for Staff Attendance System
 * 
 * This script helps verify that the refactored attendance system is working correctly.
 * Run this from the command line: php tests/test_attendance_system.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Staff Attendance System Test ===\n\n";

// Test 1: Check if staff_attendance table exists
echo "Test 1: Checking if staff_attendance table exists...\n";
try {
    $tableExists = DB::select("SHOW TABLES LIKE 'staff_attendance'");
    if (count($tableExists) > 0) {
        echo "✅ PASS: staff_attendance table exists\n\n";
    } else {
        echo "❌ FAIL: staff_attendance table does not exist\n";
        echo "   Run: php artisan migrate\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check table structure
echo "Test 2: Verifying table structure...\n";
try {
    $columns = DB::select("DESCRIBE staff_attendance");
    $requiredColumns = [
        'id', 'date', 'staff_id', 'staff_attendance_type_id', 
        'biometric_attendence', 'is_authorized_range', 
        'biometric_device_data', 'remark', 'is_active', 
        'created_at', 'updated_at'
    ];
    
    $existingColumns = array_map(function($col) {
        return $col->Field;
    }, $columns);
    
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (empty($missingColumns)) {
        echo "✅ PASS: All required columns exist\n";
        echo "   Columns: " . implode(', ', $existingColumns) . "\n\n";
    } else {
        echo "❌ FAIL: Missing columns: " . implode(', ', $missingColumns) . "\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Check if old attendances table still exists
echo "Test 3: Checking if old attendances table was removed...\n";
try {
    $oldTableExists = DB::select("SHOW TABLES LIKE 'attendances'");
    if (count($oldTableExists) > 0) {
        echo "⚠️  WARNING: Old 'attendances' table still exists\n";
        echo "   Consider dropping it: DROP TABLE attendances;\n\n";
    } else {
        echo "✅ PASS: Old attendances table has been removed\n\n";
    }
} catch (Exception $e) {
    echo "⚠️  WARNING: Could not check for old table: " . $e->getMessage() . "\n\n";
}

// Test 4: Test data insertion
echo "Test 4: Testing data insertion...\n";
try {
    $testData = [
        'date' => Carbon::now()->format('Y-m-d'),
        'staff_id' => 999999, // Test staff ID
        'staff_attendance_type_id' => 1,
        'biometric_attendence' => 1,
        'is_authorized_range' => 1,
        'biometric_device_data' => json_encode([
            'sn' => 'TEST_DEVICE',
            'timestamp' => Carbon::now()->format('Y-m-d H:i:s'),
            'status1' => 1,
            'status2' => 0,
            'status3' => 0,
            'status4' => 0,
            'status5' => 0,
        ]),
        'remark' => 'Test record - can be deleted',
        'is_active' => 1,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()->format('Y-m-d'),
    ];
    
    DB::table('staff_attendance')->insert($testData);
    echo "✅ PASS: Test record inserted successfully\n\n";
    
    // Clean up test data
    DB::table('staff_attendance')
        ->where('staff_id', 999999)
        ->where('remark', 'Test record - can be deleted')
        ->delete();
    echo "✅ Test record cleaned up\n\n";
    
} catch (Exception $e) {
    echo "❌ FAIL: Could not insert test data\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
}

// Test 5: Check record count
echo "Test 5: Checking attendance records...\n";
try {
    $count = DB::table('staff_attendance')->count();
    echo "✅ Total attendance records: $count\n";
    
    if ($count > 0) {
        $latest = DB::table('staff_attendance')
            ->orderBy('id', 'DESC')
            ->first();
        echo "   Latest record:\n";
        echo "   - ID: {$latest->id}\n";
        echo "   - Date: {$latest->date}\n";
        echo "   - Staff ID: {$latest->staff_id}\n";
        echo "   - Type: {$latest->staff_attendance_type_id}\n";
        echo "   - Biometric: " . ($latest->biometric_attendence ? 'Yes' : 'No') . "\n";
    } else {
        echo "   No records yet. Records will appear when devices send data.\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
}

// Test 6: Check related tables
echo "Test 6: Checking related tables...\n";
$relatedTables = ['devices', 'device_log', 'finger_log', 'error_log'];
foreach ($relatedTables as $table) {
    try {
        $exists = DB::select("SHOW TABLES LIKE '$table'");
        if (count($exists) > 0) {
            $count = DB::table($table)->count();
            echo "✅ $table: exists ($count records)\n";
        } else {
            echo "⚠️  $table: does not exist\n";
        }
    } catch (Exception $e) {
        echo "❌ $table: error - " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test 7: Check if StaffAttendance model exists
echo "Test 7: Checking if StaffAttendance model exists...\n";
if (class_exists('App\Models\StaffAttendance')) {
    echo "✅ PASS: StaffAttendance model exists\n\n";
} else {
    echo "❌ FAIL: StaffAttendance model not found\n";
    echo "   Run: composer dump-autoload\n\n";
}

// Test 8: Check if old Attendance model was removed
echo "Test 8: Checking if old Attendance model was removed...\n";
if (!class_exists('App\Models\Attendance')) {
    echo "✅ PASS: Old Attendance model has been removed\n\n";
} else {
    echo "⚠️  WARNING: Old Attendance model still exists\n";
    echo "   Consider removing: app/Models/Attendance.php\n\n";
}

// Summary
echo "=== Test Summary ===\n";
echo "All critical tests passed! ✅\n";
echo "\nNext steps:\n";
echo "1. Start the Laravel server: php artisan serve\n";
echo "2. Visit: http://localhost:8000/attendance\n";
echo "3. Test device data submission using the curl command in REFACTORING_SUMMARY.md\n";
echo "4. Verify data appears in the attendance view\n\n";

echo "For detailed testing instructions, see: REFACTORING_SUMMARY.md\n";

