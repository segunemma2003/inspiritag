#!/bin/bash

API_URL="http://38.180.244.178/api"
TOKEN="46|9B9u3ZiepnaGyRf2iIW0SaeKlDww7zlOYvBMUBb451f7bfe1"

# Download a real image (800x600)
/usr/bin/curl -L "https://picsum.photos/800/600.jpg" -o real.jpg

# Get file size
SIZE=$(stat -f%z real.jpg 2>/dev/null || stat -c%s real.jpg)

echo "Downloaded image: $SIZE bytes"

# Step 1: Get presigned URL
RESP=$(/usr/bin/curl -s -X POST "$API_URL/posts/upload-url" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"filename\":\"real.jpg\",\"content_type\":\"image/jpeg\",\"file_size\":$SIZE}")

# Extract URLs
URL=$(echo "$RESP" | /usr/bin/python3 -c "import sys, json; print(json.load(sys.stdin)['data']['upload_url'])" 2>/dev/null)
PATH=$(echo "$RESP" | /usr/bin/python3 -c "import sys, json; print(json.load(sys.stdin)['data']['file_path'])" 2>/dev/null)

echo "Got presigned URL"

# Step 2: Upload to S3
/usr/bin/curl -s -X PUT "$URL" -H "Content-Type: image/jpeg" -T real.jpg
echo "Uploaded to S3"

# Step 3: Create post
POST=$(/usr/bin/curl -s -X POST "$API_URL/posts/create-from-s3" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"file_path\":\"$PATH\",\"caption\":\"Real 800x600 image!\",\"category_id\":1,\"tags\":[\"real\",\"test\"]}")

# Extract media URL
MEDIA=$(echo "$POST" | /usr/bin/python3 -c "import sys, json; print(json.load(sys.stdin)['data']['post']['media_url'])" 2>/dev/null)

echo ""
echo "✅ SUCCESS!"
echo "📷 Image URL: $MEDIA"
echo ""
echo "Open this URL in your browser:"
echo "$MEDIA"

/bin/rm -f real.jpg
