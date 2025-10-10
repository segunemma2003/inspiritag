#!/bin/bash

echo "🔧 Ensuring containers stay running after deployment..."

# Function to check if containers are running
check_containers() {
    local running_containers=$(docker-compose ps --services --filter "status=running" | wc -l)
    local total_containers=$(docker-compose ps --services | wc -l)
    
    if [ "$running_containers" -eq "$total_containers" ] && [ "$running_containers" -gt 0 ]; then
        return 0
    else
        return 1
    fi
}

# Function to restart containers if needed
restart_if_needed() {
    echo "🔄 Checking container status..."
    docker-compose ps
    
    if ! check_containers; then
        echo "⚠️ Some containers are not running, restarting..."
        docker-compose up -d --force-recreate
        
        # Wait for containers to be healthy
        echo "⏳ Waiting for containers to be healthy..."
        timeout 300 bash -c 'until docker-compose ps | grep -q "healthy"; do
            echo "Waiting for services to be healthy..."
            docker-compose ps
            sleep 5
        done' || {
            echo "❌ Services failed to become healthy"
            docker-compose logs
            return 1
        }
    else
        echo "✅ All containers are running"
    fi
}

# Main execution
echo "🚀 Starting container health check..."

# Initial check and restart if needed
restart_if_needed

# Set up monitoring to restart containers if they go down
echo "🔄 Setting up container monitoring..."

# Create a simple monitoring loop that runs in background
(
    while true; do
        sleep 60  # Check every minute
        
        if ! check_containers; then
            echo "⚠️ Container health check failed, restarting..."
            docker-compose up -d --force-recreate
            sleep 30
        fi
    done
) &

# Store the PID for potential cleanup
echo $! > /tmp/container_monitor.pid

echo "✅ Container monitoring started (PID: $(cat /tmp/container_monitor.pid))"
echo "🎉 Container health check completed!"
