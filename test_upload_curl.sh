#!/bin/bash
# test_upload_curl.sh

BASE_URL="http://38.180.244.178"
EMAIL="testuser1760092397@example.com"
PASSWORD="password123"

echo "🚀 Presigned Upload Test Suite (cURL)"
echo "Base URL: $BASE_URL"
echo "====================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local status=$1
    local message=$2
    case $status in
        "success")
            echo -e "${GREEN}✅ $message${NC}"
            ;;
        "error")
            echo -e "${RED}❌ $message${NC}"
            ;;
        "info")
            echo -e "${BLUE}ℹ️  $message${NC}"
            ;;
        "warning")
            echo -e "${YELLOW}⚠️  $message${NC}"
            ;;
    esac
}

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    print_status "warning" "jq is not installed. Installing via brew..."
    if command -v brew &> /dev/null; then
        brew install jq
    else
        print_status "error" "jq is required but not installed. Please install jq manually."
        exit 1
    fi
fi

# Check if curl is installed
if ! command -v curl &> /dev/null; then
    print_status "error" "curl is not installed. Please install curl."
    exit 1
fi

print_status "info" "Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

# Check if login response is valid JSON
if ! echo "$LOGIN_RESPONSE" | jq . >/dev/null 2>&1; then
    print_status "error" "Invalid JSON response from login endpoint"
    echo "$LOGIN_RESPONSE"
    exit 1
fi

TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token // empty')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    print_status "error" "Login failed"
    echo "$LOGIN_RESPONSE" | jq .
    exit 1
fi

print_status "success" "Login successful"

# Get categories
print_status "info" "Fetching available categories..."
CATEGORIES_RESPONSE=$(curl -s -X GET "$BASE_URL/api/categories" \
  -H "Authorization: Bearer $TOKEN")

if echo "$CATEGORIES_RESPONSE" | jq . >/dev/null 2>&1; then
    echo "$CATEGORIES_RESPONSE" | jq -r '.data[]? | "   ID: \(.id) - \(.name)"'
else
    print_status "warning" "Could not fetch categories"
fi

# Create a test image file
print_status "info" "Creating test image..."
TEST_IMAGE_PATH="/tmp/test_image.jpg"

# Try to create image using ImageMagick
if command -v convert &> /dev/null; then
    convert -size 800x600 xc:skyblue \
        -pointsize 48 -fill white -gravity center \
        -annotate +0-50 "Test Image" \
        -pointsize 24 -annotate +0+0 "Presigned Upload Test" \
        -pointsize 16 -annotate +0+50 "$(date)" \
        "$TEST_IMAGE_PATH"
    print_status "success" "Created test image using ImageMagick"
elif command -v python3 &> /dev/null; then
    # Fallback to Python if ImageMagick is not available
    python3 -c "
from PIL import Image, ImageDraw, ImageFont
import datetime

# Create image
img = Image.new('RGB', (800, 600), color='lightblue')
draw = ImageDraw.Draw(img)

# Try to use a font, fallback to default
try:
    font_large = ImageFont.truetype('/System/Library/Fonts/Arial.ttf', 48)
    font_medium = ImageFont.truetype('/System/Library/Fonts/Arial.ttf', 24)
    font_small = ImageFont.truetype('/System/Library/Fonts/Arial.ttf', 16)
except:
    font_large = ImageFont.load_default()
    font_medium = ImageFont.load_default()
    font_small = ImageFont.load_default()

# Draw text
text1 = 'Test Image'
text2 = 'Presigned Upload Test'
text3 = str(datetime.datetime.now())

# Get text dimensions for centering
w1, h1 = draw.textsize(text1, font=font_large)
w2, h2 = draw.textsize(text2, font=font_medium)
w3, h3 = draw.textsize(text3, font=font_small)

# Draw centered text
draw.text(((800-w1)//2, (600-h1)//2 - 50), text1, fill='white', font=font_large)
draw.text(((800-w2)//2, (600-h2)//2), text2, fill='white', font=font_medium)
draw.text(((800-w3)//2, (600-h3)//2 + 50), text3, fill='white', font=font_small)

img.save('$TEST_IMAGE_PATH')
print('Created test image using Python PIL')
"
    if [ $? -eq 0 ]; then
        print_status "success" "Created test image using Python PIL"
    else
        print_status "warning" "Could not create image with Python PIL, creating simple file"
        echo "This is a test file for upload testing. Created at: $(date)" > "$TEST_IMAGE_PATH"
    fi
else
    print_status "warning" "ImageMagick and Python PIL not available, creating simple text file"
    echo "This is a test file for upload testing. Created at: $(date)" > "$TEST_IMAGE_PATH"
fi

# Test 1: Basic presigned upload
echo ""
print_status "info" "Test 1: Basic presigned upload"
echo "====================================="

# Get presigned URL
print_status "info" "Getting presigned URL..."
FILE_SIZE=$(stat -c%s "$TEST_IMAGE_PATH" 2>/dev/null || stat -f%z "$TEST_IMAGE_PATH" 2>/dev/null || echo 1024)

UPLOAD_RESPONSE=$(curl -s -X POST "$BASE_URL/api/posts/upload-url" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"filename\": \"test_image.jpg\",
    \"content_type\": \"image/jpeg\",
    \"file_size\": $FILE_SIZE
  }")

if ! echo "$UPLOAD_RESPONSE" | jq . >/dev/null 2>&1; then
    print_status "error" "Invalid JSON response from upload-url endpoint"
    echo "$UPLOAD_RESPONSE"
    exit 1
fi

UPLOAD_URL=$(echo "$UPLOAD_RESPONSE" | jq -r '.data.upload_url // empty')
FILE_PATH=$(echo "$UPLOAD_RESPONSE" | jq -r '.data.file_path // empty')

if [ -z "$UPLOAD_URL" ] || [ "$UPLOAD_URL" = "null" ]; then
    print_status "error" "Failed to get upload URL"
    echo "$UPLOAD_RESPONSE" | jq .
    exit 1
fi

print_status "success" "Got presigned URL"
echo "   Upload URL: ${UPLOAD_URL:0:100}..."
echo "   File path: $FILE_PATH"

# Upload to S3
print_status "info" "Uploading file to S3..."
UPLOAD_RESULT=$(curl -s -X PUT "$UPLOAD_URL" \
  -H "Content-Type: image/jpeg" \
  --data-binary @"$TEST_IMAGE_PATH" \
  -w "%{http_code}" \
  -o /dev/null)

if [[ "$UPLOAD_RESULT" == "200" ]]; then
    print_status "success" "Upload successful"
else
    print_status "error" "Upload failed with HTTP code: $UPLOAD_RESULT"
    exit 1
fi

# Create post
print_status "info" "Creating post..."
POST_RESPONSE=$(curl -s -X POST "$BASE_URL/api/posts/create-from-s3" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"file_path\": \"$FILE_PATH\",
    \"caption\": \"Test post uploaded via presigned URL - $(date)\",
    \"category_id\": 1,
    \"tags\": [\"test\", \"upload\", \"presigned\"],
    \"location\": \"Test Location\"
  }")

if ! echo "$POST_RESPONSE" | jq . >/dev/null 2>&1; then
    print_status "error" "Invalid JSON response from create-from-s3 endpoint"
    echo "$POST_RESPONSE"
    exit 1
fi

SUCCESS=$(echo "$POST_RESPONSE" | jq -r '.success // false')

if [ "$SUCCESS" = "true" ]; then
    print_status "success" "Post created successfully!"
    echo "$POST_RESPONSE" | jq -r '.data.post | "   Post ID: \(.id)\n   Media URL: \(.media_url)\n   Caption: \(.caption)"'
else
    print_status "error" "Failed to create post"
    echo "$POST_RESPONSE" | jq .
    exit 1
fi

# Test 2: Video upload
echo ""
print_status "info" "Test 2: Video upload"
echo "====================================="

# Create a test video file
TEST_VIDEO_PATH="/tmp/test_video.mp4"
print_status "info" "Creating test video file..."
echo "FAKE VIDEO CONTENT FOR TESTING" > "$TEST_VIDEO_PATH"
# Make it larger to simulate a real video
for i in {1..1000}; do
    echo "Video chunk $i - $(date)" >> "$TEST_VIDEO_PATH"
done
print_status "success" "Created test video file"

VIDEO_FILE_SIZE=$(stat -c%s "$TEST_VIDEO_PATH" 2>/dev/null || stat -f%z "$TEST_VIDEO_PATH" 2>/dev/null || echo 1024)

# Get presigned URL for video
print_status "info" "Getting presigned URL for video..."
VIDEO_UPLOAD_RESPONSE=$(curl -s -X POST "$BASE_URL/api/posts/upload-url" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"filename\": \"test_video.mp4\",
    \"content_type\": \"video/mp4\",
    \"file_size\": $VIDEO_FILE_SIZE
  }")

if ! echo "$VIDEO_UPLOAD_RESPONSE" | jq . >/dev/null 2>&1; then
    print_status "error" "Invalid JSON response from upload-url endpoint"
    echo "$VIDEO_UPLOAD_RESPONSE"
    exit 1
fi

VIDEO_UPLOAD_URL=$(echo "$VIDEO_UPLOAD_RESPONSE" | jq -r '.data.upload_url // empty')
VIDEO_FILE_PATH=$(echo "$VIDEO_UPLOAD_RESPONSE" | jq -r '.data.file_path // empty')

if [ -z "$VIDEO_UPLOAD_URL" ] || [ "$VIDEO_UPLOAD_URL" = "null" ]; then
    print_status "error" "Failed to get video upload URL"
    echo "$VIDEO_UPLOAD_RESPONSE" | jq .
    exit 1
fi

print_status "success" "Got video presigned URL"

# Upload video to S3
print_status "info" "Uploading video to S3..."
VIDEO_UPLOAD_RESULT=$(curl -s -X PUT "$VIDEO_UPLOAD_URL" \
  -H "Content-Type: video/mp4" \
  --data-binary @"$TEST_VIDEO_PATH" \
  -w "%{http_code}" \
  -o /dev/null)

if [[ "$VIDEO_UPLOAD_RESULT" == "200" ]]; then
    print_status "success" "Video upload successful"
else
    print_status "error" "Video upload failed with HTTP code: $VIDEO_UPLOAD_RESULT"
    exit 1
fi

# Create video post
print_status "info" "Creating video post..."
VIDEO_POST_RESPONSE=$(curl -s -X POST "$BASE_URL/api/posts/create-from-s3" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"file_path\": \"$VIDEO_FILE_PATH\",
    \"caption\": \"Test video uploaded via presigned URL - $(date)\",
    \"category_id\": 1,
    \"tags\": [\"test\", \"video\", \"upload\", \"presigned\"],
    \"location\": \"Test Location\"
  }")

if ! echo "$VIDEO_POST_RESPONSE" | jq . >/dev/null 2>&1; then
    print_status "error" "Invalid JSON response from create-from-s3 endpoint"
    echo "$VIDEO_POST_RESPONSE"
    exit 1
fi

VIDEO_SUCCESS=$(echo "$VIDEO_POST_RESPONSE" | jq -r '.success // false')

if [ "$VIDEO_SUCCESS" = "true" ]; then
    print_status "success" "Video post created successfully!"
    echo "$VIDEO_POST_RESPONSE" | jq -r '.data.post | "   Post ID: \(.id)\n   Media URL: \(.media_url)\n   Caption: \(.caption)"'
else
    print_status "error" "Failed to create video post"
    echo "$VIDEO_POST_RESPONSE" | jq .
fi

# Summary
echo ""
print_status "info" "Test Results Summary"
echo "======================="

if [ "$SUCCESS" = "true" ] && [ "$VIDEO_SUCCESS" = "true" ]; then
    print_status "success" "All tests passed! Presigned upload is working correctly."
    echo ""
    echo "🎉 Upload functionality is working as expected!"
    echo "   ✅ Image upload via presigned URL"
    echo "   ✅ Video upload via presigned URL"
    echo "   ✅ Post creation from S3"
else
    print_status "error" "Some tests failed. Check the output above for details."
fi

# Cleanup
echo ""
print_status "info" "Cleaning up test files..."
rm -f "$TEST_IMAGE_PATH" "$TEST_VIDEO_PATH"
print_status "success" "Cleanup complete"

echo ""
print_status "info" "Test completed!"
