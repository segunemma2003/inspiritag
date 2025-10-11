#!/bin/bash

# Configuration
API_URL="http://38.180.244.178/api"
TOKEN="46|9B9u3ZiepnaGyRf2iIW0SaeKlDww7zlOYvBMUBb451f7bfe1"
CATEGORY_ID=1

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Create a temporary test file with actual image data (1x1 pixel PNG)
TEMP_FILE=$(mktemp /tmp/test-image.XXXXXX.jpg)
echo "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==" | base64 -d > "$TEMP_FILE"
FILE_SIZE=$(stat -f%z "$TEMP_FILE" 2>/dev/null || stat -c%s "$TEMP_FILE" 2>/dev/null)

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Testing Presigned URL Upload Flow${NC}"
echo -e "${BLUE}Remote Server: 38.180.244.178${NC}"
echo -e "${BLUE}========================================${NC}\n"

echo -e "${YELLOW}📝 Test Configuration:${NC}"
echo -e "API URL: $API_URL"
echo -e "Temp File: $TEMP_FILE"
echo -e "File Size: $FILE_SIZE bytes\n"

# Test server connectivity first
echo -e "${BLUE}Pre-flight: Testing server connectivity...${NC}"
if curl -s --connect-timeout 5 http://38.180.244.178/health > /dev/null; then
    echo -e "${GREEN}✅ Server is reachable${NC}\n"
else
    echo -e "${RED}❌ Cannot reach server at 38.180.244.178${NC}"
    echo -e "${YELLOW}Please check:${NC}"
    echo "  1. Server is running"
    echo "  2. Port 80 is open"
    echo "  3. Docker containers are up"
    rm -f "$TEMP_FILE"
    exit 1
fi

# Step 1: Get presigned URL
echo -e "${BLUE}Step 1: Getting presigned URL...${NC}"
echo -e "${YELLOW}Request:${NC}"
echo "POST $API_URL/posts/upload-url"
echo "Authorization: Bearer $TOKEN"
echo "Body: {\"filename\":\"test-image.jpg\",\"content_type\":\"image/jpeg\",\"file_size\":$FILE_SIZE}"
echo ""

PRESIGNED_RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "$API_URL/posts/upload-url" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"filename\": \"test-image.jpg\",
    \"content_type\": \"image/jpeg\",
    \"file_size\": $FILE_SIZE
  }")

HTTP_STATUS=$(echo "$PRESIGNED_RESPONSE" | grep -o "HTTP_STATUS:[0-9]*" | cut -d: -f2)
RESPONSE_BODY=$(echo "$PRESIGNED_RESPONSE" | sed 's/HTTP_STATUS:[0-9]*//g')

echo -e "${YELLOW}Response (Status: $HTTP_STATUS):${NC}"
echo "$RESPONSE_BODY" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE_BODY"
echo ""

if [ "$HTTP_STATUS" != "200" ]; then
    echo -e "${RED}❌ Failed to get presigned URL (HTTP $HTTP_STATUS)${NC}"
    echo -e "${YELLOW}Debug Info:${NC}"
    echo "Full Response: $PRESIGNED_RESPONSE"
    rm -f "$TEMP_FILE"
    exit 1
fi

# Extract upload URL and file path
UPLOAD_URL=$(echo "$RESPONSE_BODY" | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['upload_url'])" 2>/dev/null)
FILE_PATH=$(echo "$RESPONSE_BODY" | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['file_path'])" 2>/dev/null)

if [ -z "$UPLOAD_URL" ] || [ "$UPLOAD_URL" == "None" ]; then
    echo -e "${RED}❌ Failed to extract upload URL from response${NC}"
    rm -f "$TEMP_FILE"
    exit 1
fi

echo -e "${GREEN}✅ Presigned URL obtained successfully${NC}"
echo -e "${YELLOW}Upload URL:${NC} ${UPLOAD_URL:0:80}..."
echo -e "${YELLOW}File Path:${NC} $FILE_PATH"
echo ""

# Step 2: Upload file to S3
echo -e "${BLUE}Step 2: Uploading file to S3...${NC}"
echo -e "${YELLOW}Request:${NC}"
echo "PUT $UPLOAD_URL"
echo "Content-Type: image/jpeg"
echo "File: $TEMP_FILE ($FILE_SIZE bytes)"
echo ""

UPLOAD_RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X PUT "$UPLOAD_URL" \
  -H "Content-Type: image/jpeg" \
  -T "$TEMP_FILE")

UPLOAD_STATUS=$(echo "$UPLOAD_RESPONSE" | grep -o "HTTP_STATUS:[0-9]*" | cut -d: -f2)
UPLOAD_BODY=$(echo "$UPLOAD_RESPONSE" | sed 's/HTTP_STATUS:[0-9]*//g')

echo -e "${YELLOW}Response (Status: $UPLOAD_STATUS):${NC}"
if [ -n "$UPLOAD_BODY" ]; then
    echo "$UPLOAD_BODY"
else
    echo "(Empty response - this is normal for S3 uploads)"
fi
echo ""

if [ "$UPLOAD_STATUS" != "200" ]; then
    echo -e "${RED}❌ Failed to upload file to S3 (HTTP $UPLOAD_STATUS)${NC}"
    rm -f "$TEMP_FILE"
    exit 1
fi

echo -e "${GREEN}✅ File uploaded to S3 successfully${NC}\n"

# Step 3: Create post record
echo -e "${BLUE}Step 3: Creating post record in database...${NC}"
echo -e "${YELLOW}Request:${NC}"
echo "POST $API_URL/posts/create-from-s3"
echo "Authorization: Bearer $TOKEN"
echo "Body: {\"file_path\":\"$FILE_PATH\",\"caption\":\"Test post via curl\",\"category_id\":$CATEGORY_ID}"
echo ""

POST_RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "$API_URL/posts/create-from-s3" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"file_path\": \"$FILE_PATH\",
    \"caption\": \"Test post uploaded from remote to $API_URL\",
    \"category_id\": $CATEGORY_ID,
    \"tags\": [\"test\", \"remote\", \"production\"],
    \"location\": \"Production Server\"
  }")

POST_STATUS=$(echo "$POST_RESPONSE" | grep -o "HTTP_STATUS:[0-9]*" | cut -d: -f2)
POST_BODY=$(echo "$POST_RESPONSE" | sed 's/HTTP_STATUS:[0-9]*//g')

echo -e "${YELLOW}Response (Status: $POST_STATUS):${NC}"
echo "$POST_BODY" | python3 -m json.tool 2>/dev/null || echo "$POST_BODY"
echo ""

if [ "$POST_STATUS" == "200" ] || [ "$POST_STATUS" == "201" ]; then
    echo -e "${GREEN}✅ Post created successfully!${NC}"
    POST_ID=$(echo "$POST_BODY" | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['post']['id'])" 2>/dev/null)
    if [ -n "$POST_ID" ]; then
        echo -e "${GREEN}📝 Post ID: $POST_ID${NC}"
        echo -e "${GREEN}🔗 View on server: http://38.180.244.178/api/posts/$POST_ID${NC}"
    fi
else
    echo -e "${RED}❌ Failed to create post (HTTP $POST_STATUS)${NC}"
fi

# Cleanup
rm -f "$TEMP_FILE"
echo -e "\n${BLUE}========================================${NC}"
echo -e "${BLUE}Test Complete - Temporary file cleaned up${NC}"
echo -e "${BLUE}========================================${NC}"

# Final verification
echo -e "\n${YELLOW}Quick Verification:${NC}"
echo "Test categories endpoint: curl http://38.180.244.178/api/categories"
curl -s http://38.180.244.178/api/categories | python3 -m json.tool 2>/dev/null | head -20 || echo "Failed to get categories"
