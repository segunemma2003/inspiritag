#!/bin/bash

echo "🔍 Testing Deployment Status"
echo "============================"

echo "1. Testing basic connectivity to VPS..."
ping -c 3 [SERVER_IP]

echo ""
echo "2. Testing port 80 connectivity..."
nc -zv [SERVER_IP] 80

echo ""
echo "3. Testing port 8000 (alternative)..."
nc -zv [SERVER_IP] 8000

echo ""
echo "4. Testing with curl (verbose)..."
curl -v --connect-timeout 10 http://[SERVER_IP]/health

echo ""
echo "5. Testing API endpoint..."
curl -v --connect-timeout 10 http://[SERVER_IP]/api/categories

echo ""
echo "6. Checking if it's a firewall issue..."
nmap -p 80,8000 [SERVER_IP]
