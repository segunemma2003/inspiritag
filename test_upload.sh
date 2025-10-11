# Create and run the test script
cat > upload_real.sh << 'EOF'
#!/bin/bash

API_URL="http://38.180.244.178/api"
TOKEN="46|9B9u3ZiepnaGyRf2iIW0SaeKlDww7zlOYvBMUBb451f7bfe1"

echo "📥 Downloading real image from internet..."
curl -s "https://picsum.photos/800/600" -o real_image.jpg

FILE_SIZE=$(stat -f%z real_image.jpg 2>/dev/null || stat -c%s real_image.jpg)
echo "✅ Downloaded: $FILE_SIZE bytes"

echo ""
echo "🔄 Step 1: Getting presigned URL..."
PRESIGNED=$(curl -s -X POST "$API_URL/posts/upload-url" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"filename\":\"real_image.jpg\",\"content_type\":\"image/jpeg\",\"file_size\":$FILE_SIZE}")

UPLOAD_URL=$(echo "$PRESIGNED" | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['upload_url'])" 2>/dev/null)
FILE_PATH=$(echo "$PRESIGNED" | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['file_path'])" 2>/dev/null)

echo "✅ Got presigned URL"

echo ""
echo "📤 Step 2: Uploading to S3..."
curl -s -X PUT "$UPLOAD_URL" -H "Content-Type: image/jpeg" -T real_image.jpg
echo "✅ Uploaded to S3"

echo ""
echo "💾 Step 3: Creating post record..."
POST_RESPONSE=$(curl -s -X POST "$API_URL/posts/create-from-s3" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"file_path\":\"$FILE_PATH\",\"caption\":\"Real image test - 800x600\",\"category_id\":1,\"tags\":[\"real\",\"test\"]}")

echo "$POST_RESPONSE" | python3 -m json.tool

MEDIA_URL=$(echo "$POST_RESPONSE" | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['post']['media_url'])" 2>/dev/null)
POST_ID=$(echo "$POST_RESPONSE" | python3 -c "import sys, json; print(json.load(sys.stdin)['data']['post']['id'])" 2>/dev/null)

echo ""
echo "=========================================="
echo "✅ SUCCESS! Real image uploaded!"
echo "=========================================="
echo ""
echo "📝 Post ID: $POST_ID"
echo "🔗 Media URL: $MEDIA_URL"
echo ""
echo "👉 Open this
