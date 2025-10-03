#!/bin/bash

# Quick deployment script for testing
echo "🚀 Quick Deploy Script"
echo "======================"

# Stop existing containers
echo "🛑 Stopping existing containers..."
docker-compose down

# Start with new configuration
echo "🐳 Starting containers with new config..."
docker-compose up -d

# Wait for services
echo "⏳ Waiting for services to start..."
sleep 15

# Check status
echo "📊 Container status:"
docker-compose ps

# Test API
echo "🧪 Testing API..."
curl -f http://localhost/api/categories && echo "✅ API is working!" || echo "❌ API test failed"

echo "🎉 Deployment complete!"
echo "🌐 Your API is available at: http://[SERVER_IP]/api"
