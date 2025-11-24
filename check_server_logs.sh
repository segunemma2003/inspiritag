#!/bin/bash
# Script to check Laravel logs on the server via Docker

echo "========================================="
echo "Laravel Log Checker"
echo "========================================="
echo ""

# Check recent Laravel logs (last 50 lines)
echo "📋 Recent Laravel logs (last 50 lines):"
echo "----------------------------------------"
docker-compose -p inspirtag exec app tail -50 /var/www/html/storage/logs/laravel.log
echo ""

# Check for errors in the last 100 lines
echo "🔍 Errors in last 100 lines:"
echo "----------------------------------------"
docker-compose -p inspirtag exec app tail -100 /var/www/html/storage/logs/laravel.log | grep -i "error\|exception\|fatal" | tail -20
echo ""

# Check for create-from-s3 specific errors
echo "📝 create-from-s3 related errors:"
echo "----------------------------------------"
docker-compose -p inspirtag exec app tail -200 /var/www/html/storage/logs/laravel.log | grep -i "createfroms3\|create-from-s3\|S3Service" | tail -20
echo ""

# Check PHP error log
echo "🐘 PHP Error Log (if exists):"
echo "----------------------------------------"
docker-compose -p inspirtag exec app tail -30 /var/log/php_errors.log 2>/dev/null || echo "No PHP error log found"
echo ""

# Check Nginx error log
echo "🌐 Nginx Error Log:"
echo "----------------------------------------"
docker-compose -p inspirtag logs nginx --tail=30 2>/dev/null | grep -i error || echo "No Nginx errors found"
echo ""

echo "========================================="
echo "✅ Log check complete"
echo "========================================="

