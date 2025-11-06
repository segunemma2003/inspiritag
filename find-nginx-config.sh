#!/bin/bash
# find-nginx-config.sh
# Find where nginx configuration files are located

echo "🔍 Finding nginx configuration location..."
echo ""

# Check if nginx is installed
if ! command -v nginx &> /dev/null; then
    echo "❌ nginx is not installed or not in PATH"
    exit 1
fi

echo "✅ nginx is installed"
echo ""

# Find nginx binary location
NGINX_BIN=$(which nginx)
echo "📍 Nginx binary: $NGINX_BIN"

# Test nginx configuration to find config file
echo ""
echo "🔍 Testing nginx configuration..."
NGINX_TEST=$(nginx -t 2>&1)
echo "$NGINX_TEST"

# Extract config file path
CONFIG_FILE=$(echo "$NGINX_TEST" | grep -oP "file \K[^\s]+" | head -1)
if [ -n "$CONFIG_FILE" ]; then
    echo ""
    echo "📄 Main config file: $CONFIG_FILE"
    CONFIG_DIR=$(dirname "$CONFIG_FILE")
    echo "📁 Config directory: $CONFIG_DIR"
fi

# Check common locations
echo ""
echo "🔍 Checking common nginx directories..."

COMMON_DIRS=(
    "/etc/nginx"
    "/usr/local/nginx/conf"
    "/opt/nginx/conf"
    "/usr/local/etc/nginx"
)

for dir in "${COMMON_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "✅ Found: $dir"
        echo "   Contents:"
        ls -la "$dir" 2>/dev/null | head -10 || echo "   (cannot list)"
        echo ""
    fi
done

# Check for sites-available/sites-enabled pattern
echo "🔍 Looking for sites-available/sites-enabled..."
if [ -d "/etc/nginx/sites-available" ]; then
    echo "✅ /etc/nginx/sites-available exists"
elif [ -d "/etc/nginx/conf.d" ]; then
    echo "✅ /etc/nginx/conf.d exists (alternative location)"
    echo "   You can place config files here instead"
elif [ -d "/usr/local/nginx/conf/sites-available" ]; then
    echo "✅ /usr/local/nginx/conf/sites-available exists"
fi

echo ""
echo "💡 Recommendation:"
if [ -d "/etc/nginx/conf.d" ]; then
    echo "   Use: /etc/nginx/conf.d/api.inspirtag.com.conf"
elif [ -d "/etc/nginx" ]; then
    echo "   Create: /etc/nginx/sites-available/ (if using Debian/Ubuntu style)"
    echo "   Or use: /etc/nginx/conf.d/api.inspirtag.com.conf"
else
    echo "   Check the config directory shown above"
fi

