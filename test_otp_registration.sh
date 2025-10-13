#!/bin/bash

# Test OTP Registration Flow
API_URL="http://localhost:8000/api"

echo "=========================================="
echo "Testing OTP Registration Flow"
echo "=========================================="
echo ""

# Step 1: Register
echo "Step 1: Registering new user..."
REGISTER_RESPONSE=$(curl -s -X POST "$API_URL/register" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Test User",
    "email": "testuser@example.com",
    "username": "testuser_'$(date +%s)'",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "terms_accepted": true
  }')

echo "Response:"
echo "$REGISTER_RESPONSE" | python3 -m json.tool
echo ""

# Step 2: Check logs for OTP
echo "Step 2: Checking logs for OTP..."
echo "Last 50 lines of log (look for the OTP code):"
tail -50 storage/logs/laravel.log | grep -A5 -B5 "OTP\|otp\|123456" || echo "Check full log at: storage/logs/laravel.log"
echo ""

echo "=========================================="
echo "Next Steps:"
echo "=========================================="
echo "1. Find the 6-digit OTP in the logs above"
echo "2. Run this command to verify (replace 123456 with your OTP):"
echo ""
echo "curl -X POST $API_URL/verify-otp \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -d '{\"email\": \"testuser@example.com\", \"otp\": \"123456\"}'"
echo ""
echo "3. Then try to login:"
echo ""
echo "curl -X POST $API_URL/login \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -d '{\"email\": \"testuser@example.com\", \"password\": \"Password123!\"}'"
echo ""

