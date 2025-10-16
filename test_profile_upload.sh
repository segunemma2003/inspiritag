#!/bin/bash

API_URL="http://38.180.244.178/api"
EMAIL="youngpresido94@gmail.com"
PASSWORD="password"

echo "🔐 Logging in with $EMAIL..."

# Step 1: Login to get token
LOGIN_RESP=$(curl -s -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

echo "Login response: $LOGIN_RESP"

# Extract token
TOKEN=$(echo "$LOGIN_RESP" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data['data']['token'])" 2>/dev/null)

if [ -z "$TOKEN" ]; then
    echo "❌ Failed to get token from login response"
    echo "Response: $LOGIN_RESP"
    exit 1
fi

echo "✅ Got token: ${TOKEN:0:20}..."

# Step 2: Download a test image
echo "📥 Downloading test image..."
curl -L "https://picsum.photos/400/400.jpg" -o test_profile.jpg

# Get file size
SIZE=$(stat -f%z test_profile.jpg 2>/dev/null || stat -c%s test_profile.jpg)
echo "📏 Image size: $SIZE bytes"

# Step 3: Upload profile picture
echo "📤 Uploading profile picture..."
UPLOAD_RESP=$(curl -s -X PUT "$API_URL/users/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -F "profile_picture=@test_profile.jpg" \
  -F "full_name=Test User Updated")

echo "Upload response: $UPLOAD_RESP"

# Extract profile picture URL
PROFILE_PIC=$(echo "$UPLOAD_RESP" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data['data']['profile_picture'])" 2>/dev/null)

echo ""
if [ "$PROFILE_PIC" != "null" ] && [ -n "$PROFILE_PIC" ]; then
    echo "✅ SUCCESS! Profile picture uploaded:"
    echo "🖼️  URL: $PROFILE_PIC"
    echo ""
    echo "Open this URL in your browser:"
    echo "$PROFILE_PIC"
else
    echo "❌ FAILED! Profile picture is null or empty"
    echo "Full response: $UPLOAD_RESP"
fi

# Cleanup
rm -f test_profile.jpg
