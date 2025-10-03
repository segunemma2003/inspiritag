#!/bin/bash

# Inspirtag VPS Setup Script
# Run this script ONCE on your VPS to prepare it for deployments

echo "🚀 Setting up Inspirtag on VPS..."

# Update system
echo "📦 Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install Docker
echo "🐳 Installing Docker..."
sudo apt install -y apt-transport-https ca-certificates curl software-properties-common
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io

# Install Docker Compose
echo "🔧 Installing Docker Compose..."
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Start Docker service
echo "▶️ Starting Docker service..."
sudo systemctl start docker
sudo systemctl enable docker

# Add user to docker group
sudo usermod -aG docker $USER

# Create project directory
echo "📁 Creating project directory..."
sudo mkdir -p /var/www/your-project
sudo chown $USER:$USER /var/www/your-project

# Clone repository (you'll need to provide your repo URL)
echo "📥 Cloning repository..."
cd /var/www/your-project
# git clone https://github.com/yourusername/your-project.git .

# Install MySQL (if not using Docker for database)
echo "🗄️ Installing MySQL..."
sudo apt install -y mysql-server mysql-client

# Configure MySQL
echo "⚙️ Configuring MySQL..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS inspirtag;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'inspirtag'@'localhost' IDENTIFIED BY '[SERVER_PASSWORD]';"
sudo mysql -e "GRANT ALL PRIVILEGES ON inspirtag.* TO 'inspirtag'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Create environment file
echo "📝 Creating environment file..."
cat > /var/www/inspirtag/.env << EOF
APP_NAME=Inspirtag
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://[SERVER_IP]

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inspirtag
DB_USERNAME=inspirtag
DB_PASSWORD=[SERVER_PASSWORD]

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@inspirtag.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="\${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="\${PUSHER_HOST}"
VITE_PUSHER_PORT="\${PUSHER_PORT}"
VITE_PUSHER_SCHEME="\${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="\${PUSHER_APP_CLUSTER}"

FIREBASE_SERVER_KEY=
EOF

# Set up Nginx (optional, if not using Docker)
echo "🌐 Setting up Nginx..."
sudo apt install -y nginx

# Create Nginx configuration
sudo tee /etc/nginx/sites-available/inspirtag << EOF
server {
    listen 80;
    server_name [SERVER_IP];
    root /var/www/inspirtag/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# Enable site
sudo ln -s /etc/nginx/sites-available/inspirtag /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# Install PHP 8.3 and extensions
echo "🐘 Installing PHP 8.3..."
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-xml php8.3-curl php8.3-mbstring php8.3-zip php8.3-gd php8.3-redis php8.3-bcmath

# Install Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Set up cron for Laravel scheduler
echo "⏰ Setting up Laravel scheduler..."
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/inspirtag && php artisan schedule:run >> /dev/null 2>&1") | crontab -

echo "✅ VPS setup completed!"
echo ""
echo "📋 Next steps:"
echo "1. Add your GitHub repository secrets:"
echo "   - VPS_HOST: [SERVER_IP]"
echo "   - VPS_USER: root"
echo "   - VPS_PASSWORD: [SERVER_PASSWORD]"
echo ""
echo "2. Configure your .env file with:"
echo "   - AWS credentials for S3"
echo "   - Firebase server key"
echo "   - Generate APP_KEY: php artisan key:generate"
echo ""
echo "3. Push to main branch to trigger deployment!"
