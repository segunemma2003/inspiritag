#!/bin/bash
# Test script for Forgot Password flow

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
API_URL="http://localhost"  # Change to your server IP if testing remotely
TEST_EMAIL="test@example.com"
NEW_PASSWORD="NewSecurePass123!"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Forgot Password Flow Test${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Step 1: Request Password Reset OTP
echo -e "${YELLOW}Step 1: Requesting password reset OTP...${NC}"
echo "Endpoint: POST $API_URL/api/forgot-password"
echo "Email: $TEST_EMAIL"
echo ""

RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST "$API_URL/api/forgot-password" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$TEST_EMAIL\"
  }")

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

echo "Response Code: $HTTP_CODE"
echo "Response Body:"
echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_CODE" == "200" ]; then
    echo -e "${GREEN}✅ OTP request successful!${NC}"
    echo ""
    echo -e "${YELLOW}📧 Check your email or server logs for the OTP${NC}"
    echo ""
    echo "To get OTP from logs, run:"
    echo "  docker-compose logs app | grep 'Password reset OTP' | tail -1"
    echo ""
    echo -e "${YELLOW}Or check Laravel log:${NC}"
    echo "  docker-compose exec app tail -50 /var/www/html/storage/logs/laravel.log | grep OTP"
    echo ""

    # Try to extract OTP from logs automatically
    echo -e "${YELLOW}Attempting to extract OTP from logs...${NC}"
    OTP=$(docker-compose logs app 2>/dev/null | grep "Password reset OTP" | tail -1 | grep -oE '[0-9]{6}' | tail -1)

    if [ -n "$OTP" ]; then
        echo -e "${GREEN}Found OTP: $OTP${NC}"
        echo ""

        # Ask user if they want to continue with password reset
        read -p "Do you want to continue with password reset using this OTP? (y/n) " -n 1 -r
        echo ""

        if [[ $REPLY =~ ^[Yy]$ ]]; then
            echo ""
            echo -e "${YELLOW}Step 2: Resetting password with OTP...${NC}"
            echo "Endpoint: POST $API_URL/api/reset-password"
            echo "Email: $TEST_EMAIL"
            echo "OTP: $OTP"
            echo "New Password: $NEW_PASSWORD"
            echo ""

            RESET_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST "$API_URL/api/reset-password" \
              -H "Content-Type: application/json" \
              -d "{
                \"email\": \"$TEST_EMAIL\",
                \"otp\": \"$OTP\",
                \"password\": \"$NEW_PASSWORD\",
                \"password_confirmation\": \"$NEW_PASSWORD\"
              }")

            RESET_HTTP_CODE=$(echo "$RESET_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
            RESET_BODY=$(echo "$RESET_RESPONSE" | sed '/HTTP_CODE:/d')

            echo "Response Code: $RESET_HTTP_CODE"
            echo "Response Body:"
            echo "$RESET_BODY" | jq '.' 2>/dev/null || echo "$RESET_BODY"
            echo ""

            if [ "$RESET_HTTP_CODE" == "200" ]; then
                echo -e "${GREEN}✅✅✅ Password reset successful! ✅✅✅${NC}"
                echo ""
                echo -e "${YELLOW}Next steps:${NC}"
                echo "1. Try logging in with new password:"
                echo "   Email: $TEST_EMAIL"
                echo "   Password: $NEW_PASSWORD"
                echo ""
                echo "2. Test login endpoint:"
                echo "   curl -X POST $API_URL/api/login \\"
                echo "     -H \"Content-Type: application/json\" \\"
                echo "     -d '{\"email\":\"$TEST_EMAIL\",\"password\":\"$NEW_PASSWORD\"}'"
            else
                echo -e "${RED}❌ Password reset failed${NC}"
            fi
        fi
    else
        echo -e "${YELLOW}⚠️ Could not automatically extract OTP from logs${NC}"
        echo "Please check logs manually and use the test commands below"
    fi

elif [ "$HTTP_CODE" == "403" ]; then
    echo -e "${RED}❌ Email not verified${NC}"
    echo "The email must be verified before resetting password"
    echo ""
    echo "To test with verified user:"
    echo "1. Register a new user"
    echo "2. Verify their email with OTP"
    echo "3. Then test forgot password"

elif [ "$HTTP_CODE" == "422" ]; then
    echo -e "${RED}❌ Validation error${NC}"
    echo "Check if the email exists in the database"

else
    echo -e "${RED}❌ Request failed${NC}"
fi

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Manual Test Commands${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

echo "1. Request OTP:"
echo "curl -X POST $API_URL/api/forgot-password \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{\"email\":\"$TEST_EMAIL\"}'"
echo ""

echo "2. Get OTP from logs:"
echo "docker-compose logs app | grep 'Password reset OTP' | tail -1"
echo ""

echo "3. Reset password (replace OTP_HERE with actual OTP):"
echo "curl -X POST $API_URL/api/reset-password \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{\"email\":\"$TEST_EMAIL\",\"otp\":\"OTP_HERE\",\"password\":\"$NEW_PASSWORD\",\"password_confirmation\":\"$NEW_PASSWORD\"}'"
echo ""

echo "4. Test login with new password:"
echo "curl -X POST $API_URL/api/login \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{\"email\":\"$TEST_EMAIL\",\"password\":\"$NEW_PASSWORD\"}'"
echo ""

echo -e "${BLUE}========================================${NC}"
echo "Test complete!"
echo -e "${BLUE}========================================${NC}"

