#!/bin/bash
# deploy_manual.sh - Manual deployment script to fix server

echo "🚀 Manual Server Deployment"
echo "==========================="

# Configuration
SERVER_HOST="38.180.244.178"
SERVER_USER="root"  # Update with your actual username
PROJECT_DIR="/var/www/inspirtag"

echo "📋 Deployment Steps:"
echo "1. SSH into your server"
echo "2. Run the following commands:"
echo ""
echo "ssh $SERVER_USER@$SERVER_HOST"
echo "cd $PROJECT_DIR"
echo "git pull origin main"
echo "chmod +x fix_server_docker.sh"
echo "./fix_server_docker.sh"
echo ""
echo "3. Test the server:"
echo "curl -f http://localhost/health"
echo "curl -f http://localhost/api/categories"
echo ""
echo "4. If still not working, check logs:"
echo "docker-compose logs app"
echo "docker-compose logs nginx"
echo ""
echo "5. Force restart all containers:"
echo "docker-compose down --remove-orphans"
echo "docker-compose up -d"
echo ""
echo "📝 Alternative: Use GitHub Actions"
echo "Push your changes to trigger automatic deployment:"
echo "git add ."
echo "git commit -m 'Fix Docker deployment'"
echo "git push origin main"
