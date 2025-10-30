#!/bin/bash

# ============================================================================
# STAFF vs STUDENT IDENTIFICATION FIX - TEST SCRIPT
# ============================================================================
# Tests that staff and students are correctly identified after the fix
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
echo -e "${BLUE}║         STAFF vs STUDENT IDENTIFICATION FIX - VERIFICATION TEST           ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# ============================================================================
# TEST 1: Staff Attendance (using employee_id)
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 1: Staff Attendance (employee_id = 9000)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
STAFF_EMPLOYEE_ID="9000"

echo -e "${YELLOW}Testing staff punch with employee_id:${NC}"
echo "Employee ID: $STAFF_EMPLOYEE_ID"
echo "Timestamp: $TIMESTAMP"
echo ""

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "$STAFF_EMPLOYEE_ID	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q "OK:"; then
    echo -e "${GREEN}✅ PASS: Staff attendance recorded${NC}"
else
    echo -e "${RED}❌ FAIL: Staff attendance failed${NC}"
fi
echo ""

# Verify in database
echo -e "${YELLOW}Verifying in database:${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "SELECT id, date, staff_id, biometric_attendence, created_at FROM staff_attendance WHERE staff_id = 1 ORDER BY id DESC LIMIT 1;" 2>/dev/null
echo ""

# ============================================================================
# TEST 2: Student Attendance (using admission_no)
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 2: Student Attendance (admission_no = 202401)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
STUDENT_ADMISSION_NO="202401"

echo -e "${YELLOW}Testing student punch with admission_no:${NC}"
echo "Admission No: $STUDENT_ADMISSION_NO"
echo "Timestamp: $TIMESTAMP"
echo ""

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "$STUDENT_ADMISSION_NO	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q "OK:"; then
    echo -e "${GREEN}✅ PASS: Student attendance recorded${NC}"
else
    echo -e "${RED}❌ FAIL: Student attendance failed${NC}"
fi
echo ""

# Verify in database
echo -e "${YELLOW}Verifying in database:${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "SELECT id, date, student_session_id, biometric_attendence, created_at FROM student_attendences WHERE student_session_id = 14577 ORDER BY id DESC LIMIT 1;" 2>/dev/null
echo ""

# ============================================================================
# TEST 3: Another Staff (employee_id = 20242001)
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 3: Another Staff (employee_id = 20242001)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
STAFF_EMPLOYEE_ID="20242001"

echo -e "${YELLOW}Testing staff punch:${NC}"
echo "Employee ID: $STAFF_EMPLOYEE_ID"
echo "Timestamp: $TIMESTAMP"
echo ""

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "$STAFF_EMPLOYEE_ID	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q "OK:"; then
    echo -e "${GREEN}✅ PASS: Staff attendance recorded${NC}"
else
    echo -e "${RED}❌ FAIL: Staff attendance failed${NC}"
fi
echo ""

# ============================================================================
# TEST 4: Another Student (admission_no = 202402)
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 4: Another Student (admission_no = 202402)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
STUDENT_ADMISSION_NO="202402"

echo -e "${YELLOW}Testing student punch:${NC}"
echo "Admission No: $STUDENT_ADMISSION_NO"
echo "Timestamp: $TIMESTAMP"
echo ""

RESPONSE=$(curl -s -X POST "$BASE_URL/iclock/cdata?SN=$DEVICE_SN&table=ATTLOG&Stamp=9999" \
  -H "Content-Type: text/plain" \
  --data-binary "$STUDENT_ADMISSION_NO	$TIMESTAMP	0	0	0	0	0")

echo -e "${GREEN}Response:${NC} $RESPONSE"
echo ""

if echo "$RESPONSE" | grep -q "OK:"; then
    echo -e "${GREEN}✅ PASS: Student attendance recorded${NC}"
else
    echo -e "${RED}❌ FAIL: Student attendance failed${NC}"
fi
echo ""

# ============================================================================
# TEST 5: Check Laravel Logs for User Identification
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 5: Check Laravel Logs${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${YELLOW}Recent log entries (last 20 lines):${NC}"
if [ -f "adms-server-ZKTeco/storage/logs/laravel.log" ]; then
    tail -20 adms-server-ZKTeco/storage/logs/laravel.log | grep -E "(STAFF|STUDENT|NOT FOUND)" --color=always
else
    echo -e "${RED}Log file not found${NC}"
fi
echo ""

# ============================================================================
# TEST 6: Database Summary
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}TEST 6: Database Summary${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${YELLOW}Recent Staff Attendance (biometric):${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "SELECT sa.id, sa.date, s.employee_id, s.name, s.surname, sa.created_at FROM staff_attendance sa JOIN staff s ON sa.staff_id = s.id WHERE sa.biometric_attendence = 1 ORDER BY sa.id DESC LIMIT 5;" 2>/dev/null
echo ""

echo -e "${YELLOW}Recent Student Attendance (biometric):${NC}"
/c/xampp/mysql/bin/mysql -u root amt -e "SELECT sa.id, sa.date, st.admission_no, st.firstname, st.lastname, sa.created_at FROM student_attendences sa JOIN student_session ss ON sa.student_session_id = ss.id JOIN students st ON ss.student_id = st.id WHERE sa.biometric_attendence = 1 ORDER BY sa.id DESC LIMIT 5;" 2>/dev/null
echo ""

# ============================================================================
# SUMMARY
# ============================================================================
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}SUMMARY${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${GREEN}✅ Fix Applied:${NC}"
echo "   • Staff lookup now uses: employee_id, biometric_id, biometric_device_pin"
echo "   • Student lookup now uses: admission_no, biometric_id, biometric_device_pin"
echo "   • Database ID (staff.id, students.id) is NO LONGER checked"
echo "   • This prevents ID conflicts between staff and students"
echo ""

echo -e "${YELLOW}Expected Behavior:${NC}"
echo "   • Staff punches with employee_id → staff_attendance table"
echo "   • Student punches with admission_no → student_attendences table"
echo "   • Logs show which table was matched"
echo ""

echo -e "${YELLOW}Device Configuration Required:${NC}"
echo "   • Staff must be enrolled with their employee_id (e.g., 9000, 20242001)"
echo "   • Students must be enrolled with their admission_no (e.g., 202401, 202402)"
echo "   • DO NOT use database IDs (staff.id or students.id) for enrollment"
echo ""

echo -e "${BLUE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

