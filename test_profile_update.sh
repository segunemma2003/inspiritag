#!/bin/bash
# Test script for Profile Management

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
API_URL="http://localhost"  # Change to your server IP if testing remotely
AUTH_TOKEN=""  # You'll need to provide this

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Profile Management Test${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Check if token is provided
if [ -z "$AUTH_TOKEN" ]; then
    echo -e "${YELLOW}⚠️  No auth token provided${NC}"
    echo "Please provide your auth token:"
    echo "  1. Login to get token:"
    echo "     curl -X POST $API_URL/api/login -H \"Content-Type: application/json\" -d '{\"email\":\"your@email.com\",\"password\":\"your_password\"}'"
    echo ""
    echo "  2. Copy the token from the response"
    echo ""
    echo "  3. Export it: export AUTH_TOKEN='your_token_here'"
    echo ""
    echo "  4. Run this script again"
    echo ""

    # Try to get from environment
    if [ -n "$1" ]; then
        AUTH_TOKEN="$1"
        echo -e "${GREEN}Using provided token${NC}"
    else
        exit 1
    fi
fi

# Test 1: Get current profile
echo -e "${YELLOW}Test 1: Getting current profile...${NC}"
echo "Endpoint: GET $API_URL/api/me"
echo ""

PROFILE_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X GET "$API_URL/api/me" \
  -H "Authorization: Bearer $AUTH_TOKEN")

HTTP_CODE=$(echo "$PROFILE_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$PROFILE_RESPONSE" | sed '/HTTP_CODE:/d')

echo "Response Code: $HTTP_CODE"
echo "Response Body:"
echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_CODE" == "200" ]; then
    echo -e "${GREEN}✅ Profile retrieved successfully!${NC}"

    # Extract current username
    CURRENT_USERNAME=$(echo "$BODY" | jq -r '.data.user.username' 2>/dev/null)
    CURRENT_EMAIL=$(echo "$BODY" | jq -r '.data.user.email' 2>/dev/null)

    echo -e "${BLUE}Current Username: $CURRENT_USERNAME${NC}"
    echo -e "${BLUE}Current Email: $CURRENT_EMAIL${NC}"
else
    echo -e "${RED}❌ Failed to get profile${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}========================================${NC}"

# Test 2: Update profile (text only)
echo ""
echo -e "${YELLOW}Test 2: Updating profile (text fields only)...${NC}"
echo "Endpoint: PUT $API_URL/api/users/profile"
echo ""

UPDATE_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X PUT "$API_URL/api/users/profile" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Test User Updated",
    "bio": "This is my updated bio - Testing API 🚀",
    "profession": "Software Developer",
    "interests": ["Technology", "Programming", "API Development"]
  }')

HTTP_CODE=$(echo "$UPDATE_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$UPDATE_RESPONSE" | sed '/HTTP_CODE:/d')

echo "Response Code: $HTTP_CODE"
echo "Response Body:"
echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_CODE" == "200" ]; then
    echo -e "${GREEN}✅ Profile updated successfully!${NC}"
else
    echo -e "${RED}❌ Profile update failed${NC}"
fi

echo ""
echo -e "${YELLOW}========================================${NC}"

# Test 3: Update with profile picture (if test image exists)
echo ""
echo -e "${YELLOW}Test 3: Updating profile with picture...${NC}"

if [ -f "test.jpg" ]; then
    echo "Found test image: test.jpg"
    echo "Endpoint: PUT $API_URL/api/users/profile"
    echo ""

    PIC_UPDATE_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X PUT "$API_URL/api/users/profile" \
      -H "Authorization: Bearer $AUTH_TOKEN" \
      -F "bio=Updated with new profile picture!" \
      -F "profile_picture=@test.jpg")

    HTTP_CODE=$(echo "$PIC_UPDATE_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
    BODY=$(echo "$PIC_UPDATE_RESPONSE" | sed '/HTTP_CODE:/d')

    echo "Response Code: $HTTP_CODE"
    echo "Response Body:"
    echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
    echo ""

    if [ "$HTTP_CODE" == "200" ]; then
        echo -e "${GREEN}✅ Profile picture uploaded successfully!${NC}"

        # Extract new profile picture URL
        NEW_PIC_URL=$(echo "$BODY" | jq -r '.data.profile_picture' 2>/dev/null)
        if [ -n "$NEW_PIC_URL" ] && [ "$NEW_PIC_URL" != "null" ]; then
            echo -e "${BLUE}New profile picture URL: $NEW_PIC_URL${NC}"
        fi
    else
        echo -e "${RED}❌ Profile picture upload failed${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  test.jpg not found, skipping profile picture test${NC}"
    echo "To test profile picture upload:"
    echo "  1. Add a test image named 'test.jpg' in the current directory"
    echo "  2. Run this script again"
fi

echo ""
echo -e "${YELLOW}========================================${NC}"

# Test 4: Get updated profile
echo ""
echo -e "${YELLOW}Test 4: Verifying profile updates...${NC}"
echo "Endpoint: GET $API_URL/api/me"
echo ""

VERIFY_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X GET "$API_URL/api/me" \
  -H "Authorization: Bearer $AUTH_TOKEN")

HTTP_CODE=$(echo "$VERIFY_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$VERIFY_RESPONSE" | sed '/HTTP_CODE:/d')

echo "Response Code: $HTTP_CODE"
echo "Updated Profile:"
echo "$BODY" | jq '.data.user | {full_name, username, bio, profession, interests, profile_picture}' 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_CODE" == "200" ]; then
    echo -e "${GREEN}✅ Profile verification successful!${NC}"
else
    echo -e "${RED}❌ Profile verification failed${NC}"
fi

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Manual Test Commands${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

echo "1. Get current profile:"
echo "curl -X GET $API_URL/api/me \\"
echo "  -H \"Authorization: Bearer YOUR_TOKEN\""
echo ""

echo "2. Update profile (text only):"
echo "curl -X PUT $API_URL/api/users/profile \\"
echo "  -H \"Authorization: Bearer YOUR_TOKEN\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{\"full_name\":\"Your Name\",\"bio\":\"Your bio\",\"profession\":\"Your Profession\",\"interests\":[\"Interest1\",\"Interest2\"]}'"
echo ""

echo "3. Update profile with picture:"
echo "curl -X PUT $API_URL/api/users/profile \\"
echo "  -H \"Authorization: Bearer YOUR_TOKEN\" \\"
echo "  -F \"full_name=Your Name\" \\"
echo "  -F \"bio=Your bio\" \\"
echo "  -F \"profile_picture=@/path/to/image.jpg\""
echo ""

echo "4. Update only profile picture:"
echo "curl -X PUT $API_URL/api/users/profile \\"
echo "  -H \"Authorization: Bearer YOUR_TOKEN\" \\"
echo "  -F \"profile_picture=@/path/to/image.jpg\""
echo ""

echo "5. Get available interests:"
echo "curl -X GET $API_URL/api/interests \\"
echo "  -H \"Authorization: Bearer YOUR_TOKEN\""
echo ""

echo -e "${BLUE}========================================${NC}"
echo "Test complete!"
echo -e "${BLUE}========================================${NC}"

