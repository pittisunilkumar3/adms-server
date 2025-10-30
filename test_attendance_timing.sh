#!/bin/bash

# Test script for attendance timing implementation
# This script tests various timing scenarios with the biometric attendance system

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://localhost"
DEVICE_SN="TEST123"

echo -e "${BLUE}🧪 Testing Attendance Timing Implementation${NC}"
echo "=============================================="
echo ""

# Test 1: Setup test timing ranges
echo -e "${YELLOW}📋 Test 1: Setting up test timing ranges...${NC}"

# Insert test timing ranges via MySQL
/c/xampp/mysql/bin/mysql -u root amt << 'EOF'
-- Clear existing test data
DELETE FROM biometric_timing_setup WHERE range_name LIKE 'TEST_%';

-- Insert test timing ranges
INSERT INTO biometric_timing_setup (range_name, range_type, time_start, time_end, grace_period_minutes, attendance_type_id, is_active, priority, created_at, updated_at) VALUES
('TEST_Morning_Checkin', 'checkin', '08:00:00', '10:00:00', 15, 1, 1, 1, NOW(), NOW()),
('TEST_Late_Checkin', 'checkin', '10:01:00', '11:00:00', 0, 2, 1, 2, NOW(), NOW()),
('TEST_Evening_Checkout', 'checkout', '17:00:00', '19:00:00', 0, 1, 1, 3, NOW(), NOW());

-- Show created ranges
SELECT id, range_name, range_type, time_start, time_end, grace_period_minutes, attendance_type_id FROM biometric_timing_setup WHERE range_name LIKE 'TEST_%';
EOF

echo -e "${GREEN}✅ Test timing ranges created${NC}"
echo ""

# Test 2: Test on-time arrival (8:30 AM - within morning checkin range)
echo -e "${YELLOW}⏰ Test 2: On-time arrival (8:30 AM)${NC}"
TIMESTAMP="2024-10-30 08:30:00"

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "1	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"

# Check database result
echo -e "${YELLOW}Database result:${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "SELECT staff_attendance_type_id, is_authorized_range, remark, created_at FROM staff_attendance WHERE staff_id = 1 ORDER BY id DESC LIMIT 1;" 2>/dev/null
echo ""

# Test 3: Late arrival (8:50 AM - late but within grace period)
echo -e "${YELLOW}⏰ Test 3: Late arrival within grace period (8:50 AM)${NC}"
TIMESTAMP="2024-10-30 08:50:00"

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "1	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"

# Check database result
echo -e "${YELLOW}Database result:${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "SELECT staff_attendance_type_id, is_authorized_range, remark, created_at FROM staff_attendance WHERE staff_id = 1 ORDER BY id DESC LIMIT 1;" 2>/dev/null
echo ""

# Test 4: Very late arrival (9:30 AM - beyond grace period)
echo -e "${YELLOW}⏰ Test 4: Very late arrival (9:30 AM)${NC}"
TIMESTAMP="2024-10-30 09:30:00"

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "1	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"

# Check database result
echo -e "${YELLOW}Database result:${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "SELECT staff_attendance_type_id, is_authorized_range, remark, created_at FROM staff_attendance WHERE staff_id = 1 ORDER BY id DESC LIMIT 1;" 2>/dev/null
echo ""

# Test 5: Late checkin range (10:30 AM)
echo -e "${YELLOW}⏰ Test 5: Late checkin range (10:30 AM)${NC}"
TIMESTAMP="2024-10-30 10:30:00"

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "1	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"

# Check database result
echo -e "${YELLOW}Database result:${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "SELECT staff_attendance_type_id, is_authorized_range, remark, created_at FROM staff_attendance WHERE staff_id = 1 ORDER BY id DESC LIMIT 1;" 2>/dev/null
echo ""

# Test 6: Unauthorized time (12:00 PM - no matching range)
echo -e "${YELLOW}⏰ Test 6: Unauthorized time (12:00 PM)${NC}"
TIMESTAMP="2024-10-30 12:00:00"

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "1	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"

# Check database result
echo -e "${YELLOW}Database result:${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "SELECT staff_attendance_type_id, is_authorized_range, remark, created_at FROM staff_attendance WHERE staff_id = 1 ORDER BY id DESC LIMIT 1;" 2>/dev/null
echo ""

# Test 7: Evening checkout (5:30 PM)
echo -e "${YELLOW}⏰ Test 7: Evening checkout (5:30 PM)${NC}"
TIMESTAMP="2024-10-30 17:30:00"

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "1	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"

# Check database result
echo -e "${YELLOW}Database result:${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "SELECT staff_attendance_type_id, is_authorized_range, remark, created_at FROM staff_attendance WHERE staff_id = 1 ORDER BY id DESC LIMIT 1;" 2>/dev/null
echo ""

# Summary
echo -e "${BLUE}📊 Test Summary${NC}"
echo "==============="
echo ""
echo -e "${YELLOW}All attendance records for staff ID 1:${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "SELECT id, DATE(created_at) as date, TIME(created_at) as time, staff_attendance_type_id, is_authorized_range, LEFT(remark, 50) as remark FROM staff_attendance WHERE staff_id = 1 ORDER BY created_at DESC LIMIT 10;" 2>/dev/null
echo ""

echo -e "${GREEN}🎉 Testing Complete!${NC}"
echo ""
echo -e "${YELLOW}Expected Results:${NC}"
echo "- 8:30 AM: attendance_type_id=1, is_authorized_range=1 (On-time)"
echo "- 8:50 AM: attendance_type_id=1, is_authorized_range=1 (Late within grace)"
echo "- 9:30 AM: attendance_type_id=1, is_authorized_range=1 (Late beyond grace)"
echo "- 10:30 AM: attendance_type_id=2, is_authorized_range=1 (Late checkin range)"
echo "- 12:00 PM: attendance_type_id=1, is_authorized_range=0 (Unauthorized)"
echo "- 5:30 PM: attendance_type_id=1, is_authorized_range=1 (Checkout)"
echo ""

# Cleanup
echo -e "${YELLOW}🧹 Cleaning up test data...${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "DELETE FROM biometric_timing_setup WHERE range_name LIKE 'TEST_%';" 2>/dev/null
echo -e "${GREEN}✅ Cleanup complete${NC}"
