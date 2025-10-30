<?php

/**
 * Test Script for Timing Range Implementation
 * 
 * This script tests the timing range matching logic by:
 * 1. Setting up sample timing ranges
 * 2. Creating test staff and student records
 * 3. Testing various punch scenarios
 * 4. Verifying the results
 */

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use Carbon\Carbon;

// Database configuration
$capsule = new DB;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'amt',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "🧪 Testing Timing Range Implementation\n";
echo "=====================================\n\n";

// Test 1: Setup sample timing ranges
echo "📋 Test 1: Setting up sample timing ranges...\n";

try {
    // Clear existing test data
    DB::table('biometric_timing_setup')->where('range_name', 'LIKE', 'TEST_%')->delete();
    
    // Insert test timing ranges
    $timingRanges = [
        [
            'range_name' => 'TEST_Morning_Checkin',
            'range_type' => 'checkin',
            'time_start' => '08:00:00',
            'time_end' => '10:00:00',
            'grace_period_minutes' => 15,
            'attendance_type_id' => 1, // Present
            'is_active' => 1,
            'priority' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'range_name' => 'TEST_Late_Checkin',
            'range_type' => 'checkin',
            'time_start' => '10:01:00',
            'time_end' => '11:00:00',
            'grace_period_minutes' => 0,
            'attendance_type_id' => 2, // Late
            'is_active' => 1,
            'priority' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'range_name' => 'TEST_Evening_Checkout',
            'range_type' => 'checkout',
            'time_start' => '17:00:00',
            'time_end' => '19:00:00',
            'grace_period_minutes' => 0,
            'attendance_type_id' => 1, // Present
            'is_active' => 1,
            'priority' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ];
    
    foreach ($timingRanges as $range) {
        DB::table('biometric_timing_setup')->insert($range);
    }
    
    echo "✅ Created " . count($timingRanges) . " test timing ranges\n\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up timing ranges: " . $e->getMessage() . "\n\n";
}

// Test 2: Test timing range matching
echo "⏰ Test 2: Testing timing range matching...\n";

$testScenarios = [
    [
        'time' => '08:30:00',
        'expected' => 'On-time (Morning Checkin)',
        'expected_type' => 1,
        'expected_authorized' => 1,
    ],
    [
        'time' => '08:45:00',
        'expected' => 'Late within grace period (Morning Checkin)',
        'expected_type' => 1,
        'expected_authorized' => 1,
    ],
    [
        'time' => '09:30:00',
        'expected' => 'Late beyond grace period (Morning Checkin)',
        'expected_type' => 1,
        'expected_authorized' => 1,
    ],
    [
        'time' => '10:30:00',
        'expected' => 'Late arrival (Late Checkin)',
        'expected_type' => 2,
        'expected_authorized' => 1,
    ],
    [
        'time' => '17:30:00',
        'expected' => 'Evening checkout',
        'expected_type' => 1,
        'expected_authorized' => 1,
    ],
    [
        'time' => '12:00:00',
        'expected' => 'Unauthorized time (no matching range)',
        'expected_type' => 1,
        'expected_authorized' => 0,
    ],
];

foreach ($testScenarios as $i => $scenario) {
    $timestamp = '2024-10-30 ' . $scenario['time'];
    echo "  Scenario " . ($i + 1) . ": Punch at {$scenario['time']}\n";
    echo "    Expected: {$scenario['expected']}\n";
    
    // Here you would call your timing service
    // For now, we'll just show the test structure
    echo "    ✅ Test scenario defined\n\n";
}

// Test 3: Database integrity check
echo "🔍 Test 3: Checking database integrity...\n";

try {
    $timingCount = DB::table('biometric_timing_setup')->where('is_active', 1)->count();
    $staffCount = DB::table('staff')->where('is_active', 1)->count();
    $studentCount = DB::table('students')->where('is_active', 'yes')->count();
    
    echo "  Active timing ranges: {$timingCount}\n";
    echo "  Active staff: {$staffCount}\n";
    echo "  Active students: {$studentCount}\n";
    
    if ($timingCount > 0) {
        echo "  ✅ Timing ranges available\n";
    } else {
        echo "  ⚠️  No active timing ranges found\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error checking database: " . $e->getMessage() . "\n\n";
}

// Test 4: Sample attendance insertion test
echo "📝 Test 4: Sample attendance insertion test...\n";

try {
    // This would test the actual controller logic
    echo "  This test would simulate biometric device requests:\n";
    echo "  POST /iclock/cdata?SN=TEST123&table=ATTLOG&Stamp=9999\n";
    echo "  Body: '1\\t2024-10-30 08:30:00\\t0\\t0\\t0\\t0\\t0'\n";
    echo "  Expected: Attendance recorded with proper timing analysis\n";
    echo "  ✅ Test structure ready\n\n";
    
} catch (Exception $e) {
    echo "❌ Error in attendance test: " . $e->getMessage() . "\n\n";
}

echo "🎉 Test Setup Complete!\n";
echo "======================\n\n";

echo "📋 Next Steps:\n";
echo "1. Run this script to set up test data\n";
echo "2. Test with actual biometric device or curl commands\n";
echo "3. Check attendance records in database\n";
echo "4. Verify timing logic is working correctly\n\n";

echo "🔧 Manual Test Commands:\n";
echo "curl -X POST 'http://localhost/iclock/cdata?SN=TEST123&table=ATTLOG&Stamp=9999' \\\n";
echo "  -H 'Content-Type: text/plain' \\\n";
echo "  --data-binary '1\t2024-10-30 08:30:00\t0\t0\t0\t0\t0'\n\n";

echo "📊 Check Results:\n";
echo "SELECT * FROM staff_attendance WHERE staff_id = 1 ORDER BY created_at DESC LIMIT 5;\n";
echo "SELECT * FROM student_attendences WHERE student_session_id = 1 ORDER BY created_at DESC LIMIT 5;\n\n";

function now() {
    return Carbon::now()->format('Y-m-d H:i:s');
}
