<?php

/**
 * Test various timing scenarios using Laravel Tinker
 */

require_once 'vendor/autoload.php';

// Test scenarios
$testScenarios = [
    [
        'time' => '08:30:00',
        'description' => 'On-time morning check-in',
        'expected_authorized' => 1,
        'expected_type' => 1,
    ],
    [
        'time' => '08:50:00', 
        'description' => 'Late morning check-in within grace period',
        'expected_authorized' => 1,
        'expected_type' => 1,
    ],
    [
        'time' => '09:30:00',
        'description' => 'Late morning check-in (late range)',
        'expected_authorized' => 1,
        'expected_type' => 2,
    ],
    [
        'time' => '12:00:00',
        'description' => 'Unauthorized time (lunch break)',
        'expected_authorized' => 0,
        'expected_type' => 1,
    ],
    [
        'time' => '13:30:00',
        'description' => 'Afternoon check-in',
        'expected_authorized' => 1,
        'expected_type' => 1,
    ],
    [
        'time' => '17:30:00',
        'description' => 'Evening check-out',
        'expected_authorized' => 1,
        'expected_type' => 1,
    ],
];

echo "🧪 Timing Scenarios Test Results\n";
echo "================================\n\n";

foreach ($testScenarios as $i => $scenario) {
    echo "Scenario " . ($i + 1) . ": {$scenario['description']} at {$scenario['time']}\n";
    echo "Expected: authorized={$scenario['expected_authorized']}, type={$scenario['expected_type']}\n";
    
    // Create curl command for testing
    $timestamp = '2024-10-30 ' . $scenario['time'];
    $curlCommand = "curl -X POST 'http://127.0.0.1:8080/iclock/cdata?SN=TEST123&table=ATTLOG&Stamp=9999' " .
                   "-H 'Content-Type: text/plain' " .
                   "-d $'200226\\t{$timestamp}\\t0\\t0\\t0\\t0\\t0'";
    
    echo "Test command: {$curlCommand}\n";
    echo "---\n\n";
}

echo "📋 Manual Testing Instructions:\n";
echo "1. Run each curl command above\n";
echo "2. Check the database results:\n";
echo "   SELECT staff_attendance_type_id, is_authorized_range, remark, created_at \n";
echo "   FROM staff_attendance WHERE staff_id = 6 ORDER BY id DESC LIMIT 1;\n\n";

echo "🎯 Expected Results Summary:\n";
echo "- 08:30: authorized=1, type=1 (On-time morning)\n";
echo "- 08:50: authorized=1, type=1 (Late within grace)\n";
echo "- 09:30: authorized=1, type=2 (Late morning range)\n";
echo "- 12:00: authorized=0, type=1 (Unauthorized)\n";
echo "- 13:30: authorized=1, type=1 (Afternoon on-time)\n";
echo "- 17:30: authorized=1, type=1 (Evening checkout)\n";
