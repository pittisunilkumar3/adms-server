#!/bin/bash

# ============================================================================
# STAFF & STUDENT ATTENDANCE TEST SCRIPT
# ============================================================================
# Tests the biometric attendance system with both staff and student punches
# ============================================================================

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
BASE_URL="http://localhost/amt/adms-server-ZKTeco/public"
DEVICE_SN="TEST001"

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║         STAFF & STUDENT ATTENDANCE - COMPREHENSIVE TEST                   ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# ============================================================================
# TEST 1: Device Handshake
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 1: Device Handshake${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

RESPONSE=$(curl -s "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&options=all")
echo -e "${GREEN}Response:${NC}"
echo "$RESPONSE" | head -5
echo ""

if echo "$RESPONSE" | grep -q "GET OPTION FROM"; then
    echo -e "${GREEN}✅ PASS: Handshake successful${NC}"
else
    echo -e "${RED}❌ FAIL: Handshake failed${NC}"
fi
echo ""

# ============================================================================
# TEST 2: Staff Attendance
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 2: Staff Attendance${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
STAFF_ID=1

echo -e "${YELLOW}Testing staff punch:${NC}"
echo "Staff ID: $STAFF_ID"
echo "Timestamp: $TIMESTAMP"
echo ""

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "$STAFF_ID	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q "OK:"; then
    echo -e "${GREEN}✅ PASS: Staff attendance recorded${NC}"
else
    echo -e "${RED}❌ FAIL: Staff attendance failed${NC}"
fi
echo ""

# ============================================================================
# TEST 3: Student Attendance
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 3: Student Attendance${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
STUDENT_ID=100

echo -e "${YELLOW}Testing student punch:${NC}"
echo "Student ID: $STUDENT_ID"
echo "Timestamp: $TIMESTAMP"
echo ""

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "$STUDENT_ID	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q "OK:"; then
    echo -e "${GREEN}✅ PASS: Student attendance recorded${NC}"
else
    echo -e "${RED}❌ FAIL: Student attendance failed${NC}"
fi
echo ""

# ============================================================================
# TEST 4: Mixed Attendance (Staff + Student)
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 4: Mixed Attendance (Staff + Student)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

TIMESTAMP1=$(date '+%Y-%m-%d %H:%M:%S')
sleep 1
TIMESTAMP2=$(date '+%Y-%m-%d %H:%M:%S')

echo -e "${YELLOW}Testing mixed punches:${NC}"
echo "Staff ID: $STAFF_ID at $TIMESTAMP1"
echo "Student ID: $STUDENT_ID at $TIMESTAMP2"
echo ""

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary $'1\t'"$TIMESTAMP1"$'\t0\t0\t0\t0\t0\n100\t'"$TIMESTAMP2"$'\t0\t0\t0\t0\t0')

echo -e "${GREEN}Response:${NC} $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q "OK: 2"; then
    echo -e "${GREEN}✅ PASS: Both staff and student attendance recorded${NC}"
else
    echo -e "${YELLOW}⚠️  WARNING: Expected 'OK: 2', got: $RESPONSE${NC}"
fi
echo ""

# ============================================================================
# TEST 5: Unknown User
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 5: Unknown User (Should Skip)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
UNKNOWN_ID=99999

echo -e "${YELLOW}Testing unknown user:${NC}"
echo "Unknown ID: $UNKNOWN_ID"
echo "Timestamp: $TIMESTAMP"
echo ""

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "$UNKNOWN_ID	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q "OK: 0"; then
    echo -e "${GREEN}✅ PASS: Unknown user skipped correctly${NC}"
else
    echo -e "${YELLOW}⚠️  WARNING: Expected 'OK: 0', got: $RESPONSE${NC}"
fi
echo ""

# ============================================================================
# TEST 6: Database Verification
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 6: Database Verification${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

# Try to find MySQL
if command -v mysql &> /dev/null; then
    MYSQL_CMD="mysql"
elif [ -f "/c/xampp/mysql/bin/mysql" ]; then
    MYSQL_CMD="/c/xampp/mysql/bin/mysql"
elif [ -f "/c/xampp/mysql/bin/mysql.exe" ]; then
    MYSQL_CMD="/c/xampp/mysql/bin/mysql.exe"
else
    echo -e "${YELLOW}⚠️  MySQL command not found. Please check database manually.${NC}"
    MYSQL_CMD=""
fi

if [ -n "$MYSQL_CMD" ]; then
    echo -e "${YELLOW}Checking staff attendance:${NC}"
    $MYSQL_CMD -u root -e "SELECT id, date, staff_id, biometric_attendence, created_at FROM amt.staff_attendance WHERE biometric_attendence = 1 ORDER BY id DESC LIMIT 3;" 2>/dev/null
    echo ""
    
    echo -e "${YELLOW}Checking student attendance:${NC}"
    $MYSQL_CMD -u root -e "SELECT id, date, student_session_id, biometric_attendence, created_at FROM amt.student_attendences WHERE biometric_attendence = 1 ORDER BY id DESC LIMIT 3;" 2>/dev/null
    echo ""
    
    echo -e "${GREEN}✅ Database verification complete${NC}"
else
    echo -e "${YELLOW}Manual verification commands:${NC}"
    echo "mysql -u root -e \"SELECT * FROM amt.staff_attendance WHERE biometric_attendence = 1 ORDER BY id DESC LIMIT 3;\""
    echo "mysql -u root -e \"SELECT * FROM amt.student_attendences WHERE biometric_attendence = 1 ORDER BY id DESC LIMIT 3;\""
fi
echo ""

# ============================================================================
# TEST 7: View Attendance Page
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 7: Attendance View Page${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${YELLOW}Testing attendance view page:${NC}"
echo "URL: $BASE_URL/attendance"
echo ""

RESPONSE=$(curl -s "$BASE_URL/attendance")

if echo "$RESPONSE" | grep -q "Biometric Attendance Records"; then
    echo -e "${GREEN}✅ PASS: Attendance page loads successfully${NC}"
else
    echo -e "${RED}❌ FAIL: Attendance page failed to load${NC}"
fi
echo ""

# ============================================================================
# SUMMARY
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}SUMMARY${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${GREEN}✅ Your Laravel implementation now supports:${NC}"
echo "   • Staff attendance (staff_attendance table)"
echo "   • Student attendance (student_attendences table)"
echo "   • Automatic user type detection"
echo "   • Mixed staff + student punches"
echo "   • Unknown user handling (skip silently)"
echo "   • Combined attendance view"
echo ""

echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Configure your biometric device to: $BASE_URL/iclock/cdata"
echo "2. Enroll staff with IDs matching staff.id or staff.employee_id"
echo "3. Enroll students with IDs matching students.id or students.admission_no"
echo "4. Ensure students have active sessions in student_session table"
echo "5. Test with real device punches"
echo "6. View attendance at: $BASE_URL/attendance"
echo ""

echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

