#!/bin/bash
# fix-nginx-conflict.sh
# Fix port 80 conflict when system nginx is running

set -e

echo "🔍 Checking for system nginx..."
echo ""

# Check if system nginx is running
if systemctl is-active --quiet nginx 2>/dev/null; then
    echo "⚠️  System nginx is currently running"
    echo ""
    
    # Show nginx status
    echo "📊 Current nginx status:"
    sudo systemctl status nginx --no-pager -l | head -10 || true
    
    echo ""
    echo "🛑 Stopping system nginx..."
    sudo systemctl stop nginx
    
    echo "🚫 Disabling system nginx from auto-starting..."
    sudo systemctl disable nginx
    
    echo ""
    echo "✅ System nginx stopped and disabled"
    echo ""
    
    # Verify it's stopped
    if systemctl is-active --quiet nginx 2>/dev/null; then
        echo "⚠️  WARNING: nginx is still running!"
        echo "   Trying to force stop..."
        sudo pkill -9 nginx || true
        sleep 2
    fi
    
    echo "🔍 Verifying nginx is stopped..."
    if ! systemctl is-active --quiet nginx 2>/dev/null; then
        echo "✅ System nginx is now stopped"
    else
        echo "❌ System nginx is still running. You may need to manually stop it."
    fi
    
elif systemctl is-enabled --quiet nginx 2>/dev/null; then
    echo "ℹ️  System nginx is not running but is enabled"
    echo "🚫 Disabling system nginx from auto-starting..."
    sudo systemctl disable nginx
    echo "✅ System nginx disabled"
else
    echo "ℹ️  System nginx is not installed or not managed by systemd"
fi

echo ""
echo "🔍 Checking for nginx processes..."
NGINX_PROCESSES=$(ps aux | grep -E '[n]ginx' | grep -v grep || true)

if [ -n "$NGINX_PROCESSES" ]; then
    echo "⚠️  Found nginx processes still running:"
    echo "$NGINX_PROCESSES"
    echo ""
    read -p "Kill these processes? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "🛑 Killing nginx processes..."
        sudo pkill -9 nginx || true
        sleep 1
        echo "✅ Nginx processes killed"
    fi
else
    echo "✅ No nginx processes found"
fi

echo ""
echo "🔍 Checking port 80..."
if command -v lsof &> /dev/null; then
    PORT_80_USAGE=$(sudo lsof -i :80 2>/dev/null || true)
    if [ -n "$PORT_80_USAGE" ]; then
        echo "⚠️  Port 80 is still in use:"
        echo "$PORT_80_USAGE"
    else
        echo "✅ Port 80 is now free!"
    fi
elif command -v netstat &> /dev/null; then
    PORT_80_USAGE=$(sudo netstat -tulpn 2>/dev/null | grep :80 || true)
    if [ -n "$PORT_80_USAGE" ]; then
        echo "⚠️  Port 80 is still in use:"
        echo "$PORT_80_USAGE"
    else
        echo "✅ Port 80 is now free!"
    fi
fi

echo ""
echo "📋 Summary:"
echo "   - System nginx: Stopped and disabled"
echo "   - Port 80: Should be free for Docker nginx"
echo ""
echo "🚀 Next steps:"
echo "   1. Navigate to your project: cd /var/www/inspirtag"
echo "   2. Start Docker containers: docker-compose up -d"
echo ""
echo "💡 Note: Docker nginx will now handle all web traffic"
echo "   System nginx will not start automatically on reboot"

