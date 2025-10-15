#!/bin/bash
# Test Profile Picture Upload
# Run this on your server after deployment completes

API_URL="http://38.180.244.178"

echo "🧪 Testing Profile Picture Upload"
echo "=================================="
echo ""

# Check if test image exists
if [ ! -f "test.jpg" ]; then
    echo "❌ test.jpg not found"
    echo "Please ensure you have a test image named 'test.jpg' in the current directory"
    exit 1
fi

echo "✅ Found test.jpg"
echo ""

# You'll need to provide a valid auth token
echo "📝 To test, you need an authentication token"
echo ""
echo "Option 1: Use an existing token"
echo "  export AUTH_TOKEN='your-token-here'"
echo "  bash $0"
echo ""
echo "Option 2: Login to get a token"
read -p "Enter your email: " EMAIL
read -sp "Enter your password: " PASSWORD
echo ""

if [ -n "$EMAIL" ] && [ -n "$PASSWORD" ]; then
    echo ""
    echo "🔐 Logging in..."
    LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/api/login" \
      -H "Content-Type: application/json" \
      -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

    AUTH_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token // empty')

    if [ -n "$AUTH_TOKEN" ] && [ "$AUTH_TOKEN" != "null" ]; then
        echo "✅ Login successful!"
        echo "Token: ${AUTH_TOKEN:0:20}..."
    else
        echo "❌ Login failed"
        echo "Response: $LOGIN_RESPONSE"
        exit 1
    fi
elif [ -n "$AUTH_TOKEN" ]; then
    echo "Using provided AUTH_TOKEN"
else
    echo "❌ No authentication method provided"
    exit 1
fi

echo ""
echo "📸 Testing Profile Picture Upload"
echo "=================================="
echo ""

# Test 1: Upload profile picture
echo "Test 1: Uploading profile picture..."
RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X PUT "$API_URL/api/users/profile" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -F "profile_picture=@test.jpg" \
  -F "full_name=Test User Updated" \
  -F "bio=Testing profile picture upload 🚀")

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

echo "HTTP Status: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" == "200" ]; then
    echo "✅✅✅ SUCCESS! Profile picture uploaded!"
    echo ""
    echo "Response:"
    echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
    echo ""

    # Extract new profile picture URL
    NEW_PIC_URL=$(echo "$BODY" | jq -r '.data.profile_picture // empty' 2>/dev/null)
    if [ -n "$NEW_PIC_URL" ] && [ "$NEW_PIC_URL" != "null" ]; then
        echo "🖼️  New Profile Picture URL:"
        echo "$NEW_PIC_URL"
        echo ""

        # Test if URL is accessible
        echo "Testing if image URL is accessible..."
        if curl -s -I "$NEW_PIC_URL" | head -1 | grep -q "200"; then
            echo "✅ Image is accessible!"
        else
            echo "⚠️  Image URL returned non-200 status"
        fi
    fi
else
    echo "❌ FAILED!"
    echo ""
    echo "Response:"
    echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
    echo ""

    # Check logs for error
    echo "Checking server logs..."
    if command -v docker-compose &> /dev/null; then
        echo ""
        docker-compose logs app --tail 20 | grep -i "error\|profile\|picture"
    fi
fi

echo ""
echo "=================================="
echo "Test Complete"
echo "=================================="

