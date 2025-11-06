# Deployment Guide - api.inspirtag.com with SSL

## Overview

This guide provides step-by-step instructions for deploying the API to `api.inspirtag.com` with SSL certificate (HTTPS) enabled.

**Server IP**: `38.180.244.178`  
**Domain**: `api.inspirtag.com`  
**SSL**: Let's Encrypt (Free)

---

## Prerequisites

-   ✅ Server with Docker and Docker Compose installed
-   ✅ Domain `inspirtag.com` registered
-   ✅ SSH access to server
-   ✅ Ports 80 and 443 open in firewall
-   ✅ Root or sudo access on server

---

## Step 1: DNS Configuration

### 1.1 Add A Record

1. Log in to your domain registrar (where you bought `inspirtag.com`)

    - Examples: GoDaddy, Namecheap, Cloudflare, etc.

2. Navigate to **DNS Management** or **DNS Settings**

3. Add a new **A Record**:

    ```
    Type: A
    Name/Host: api
    Value/IP: 38.180.244.178
    TTL: 3600 (or default)
    Proxy: Disabled (if using Cloudflare, disable proxy initially for SSL setup)
    ```

4. **Result**: `api.inspirtag.com` → `38.180.244.178`

### 1.2 Verify DNS Propagation

Wait 5-60 minutes for DNS to propagate, then verify:

```bash
# Check DNS resolution
nslookup api.inspirtag.com
# or
dig api.inspirtag.com

# Should return: 38.180.244.178
```

**Online DNS Checker**: https://www.whatsmydns.net/#A/api.inspirtag.com

---

## Step 2: Server Setup

### 2.1 SSH into Server

```bash
ssh root@38.180.244.178
# or
ssh your-user@38.180.244.178
```

### 2.2 Navigate to Project Directory

```bash
cd /var/www/inspirtag
# or wherever your project is located
```

### 2.3 Ensure Docker is Running

```bash
# Check Docker status
docker ps

# Check Docker Compose
docker-compose ps
```

---

## Step 3: Install SSL Certificate (Let's Encrypt)

### 3.1 Install Certbot

```bash
# Update package list
sudo apt update

# Install certbot
sudo apt install certbot -y

# Verify installation
certbot --version
```

### 3.2 Stop Nginx Container (Temporarily)

```bash
# Stop nginx container to free port 80 for Let's Encrypt validation
cd /var/www/inspirtag
docker-compose stop nginx
```

### 3.3 Generate SSL Certificate

```bash
# Generate certificate for api.inspirtag.com
# Replace email@example.com with your actual email
sudo certbot certonly --standalone \
  -d api.inspirtag.com \
  --email admin@inspirtag.com \
  --agree-tos \
  --non-interactive

# If successful, certificates will be saved to:
# /etc/letsencrypt/live/api.inspirtag.com/fullchain.pem
# /etc/letsencrypt/live/api.inspirtag.com/privkey.pem
```

**Important**:

-   Ensure DNS is pointing to the server before running this command
-   Port 80 must be open and accessible
-   The domain must resolve to the server IP

### 3.4 Verify Certificates

```bash
# Check if certificates exist
ls -la /etc/letsencrypt/live/api.inspirtag.com/

# Should show:
# - fullchain.pem
# - privkey.pem
```

---

## Step 4: Configure Nginx with SSL

### 4.1 Update Nginx SSL Configuration

The SSL configuration file `docker/nginx-ssl.conf` should already exist. Verify it contains:

```nginx
# HTTP server - redirect to HTTPS
server {
    listen 80;
    server_name api.inspirtag.com 38.180.244.178;

    # For Let's Encrypt validation
    location /.well-known/acme-challenge/ {
        root /var/www/html/public;
    }

    # Redirect all other traffic to HTTPS
    location / {
        return 301 https://$server_name$request_uri;
    }
}

# HTTPS server
server {
    listen 443 ssl http2;
    server_name api.inspirtag.com;
    root /var/www/html/public;
    index index.php index.html;

    # SSL certificates (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/api.inspirtag.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.inspirtag.com/privkey.pem;

    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Allow file uploads up to 50MB
    client_max_body_size 50M;

    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    # Handle Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
}
```

### 4.2 Update Docker Compose

Ensure `docker-compose.yml` nginx service is configured to:

1. Use SSL config file
2. Mount SSL certificates
3. Expose ports 80 and 443

```yaml
nginx:
    image: nginx:alpine
    container_name: inspirtag-nginx
    restart: unless-stopped
    ports:
        - "80:80"
        - "443:443"
    volumes:
        - ./:/var/www/html
        - ./docker/nginx-ssl.conf:/etc/nginx/conf.d/default.conf # Use SSL config
        - /etc/letsencrypt:/etc/letsencrypt:ro # Mount SSL certificates (read-only)
    depends_on:
        - app
    networks:
        - inspirtag-network
```

**Note**: The docker-compose.yml should already have these settings. Verify they're correct.

---

## Step 5: Update Environment Variables

### 5.1 Update .env File

Edit the `.env` file on the server:

```bash
cd /var/www/inspirtag
nano .env
```

Update or add:

```env
APP_URL=https://api.inspirtag.com
APP_ENV=production
APP_DEBUG=false
```

### 5.2 Clear Laravel Cache

```bash
# Clear all caches
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

---

## Step 6: Restart Services

### 6.1 Restart Docker Containers

```bash
cd /var/www/inspirtag

# Restart nginx with SSL configuration
docker-compose restart nginx

# Or restart all services
docker-compose restart
```

### 6.2 Verify Nginx Configuration

```bash
# Test nginx configuration
docker-compose exec nginx nginx -t

# Should output: "nginx: configuration file /etc/nginx/nginx.conf test is successful"
```

### 6.3 Check Container Logs

```bash
# Check nginx logs
docker-compose logs nginx

# Check app logs
docker-compose logs app
```

---

## Step 7: Test Deployment

### 7.1 Test HTTP (Should Redirect to HTTPS)

```bash
curl -I http://api.inspirtag.com/health
# Should return: HTTP/1.1 301 Moved Permanently
# Location: https://api.inspirtag.com/health
```

### 7.2 Test HTTPS

```bash
# Test health endpoint
curl https://api.inspirtag.com/health
# Should return: healthy

# Test with verbose output to see SSL details
curl -v https://api.inspirtag.com/health
```

### 7.3 Test API Endpoint

```bash
# Test a public API endpoint
curl https://api.inspirtag.com/api/categories

# Test with authentication
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://api.inspirtag.com/api/subscription/status
```

### 7.4 Verify SSL Certificate

```bash
# Check SSL certificate details
openssl s_client -connect api.inspirtag.com:443 -servername api.inspirtag.com

# Or use online tool:
# https://www.ssllabs.com/ssltest/analyze.html?d=api.inspirtag.com
```

---

## Step 8: Setup SSL Auto-Renewal

Let's Encrypt certificates expire every 90 days. Set up auto-renewal:

### 8.1 Test Certificate Renewal

```bash
# Test renewal (dry run)
sudo certbot renew --dry-run
```

### 8.2 Setup Auto-Renewal Cron Job

```bash
# Edit crontab
sudo crontab -e

# Add this line (runs twice daily at midnight and noon)
0 0,12 * * * certbot renew --quiet && cd /var/www/inspirtag && docker-compose restart nginx
```

### 8.3 Alternative: Systemd Timer (Recommended)

Create a systemd service for renewal:

```bash
# Create renewal script
sudo nano /usr/local/bin/renew-ssl.sh
```

Add:

```bash
#!/bin/bash
certbot renew --quiet
cd /var/www/inspirtag
docker-compose restart nginx
```

Make executable:

```bash
sudo chmod +x /usr/local/bin/renew-ssl.sh
```

Create systemd timer:

```bash
sudo nano /etc/systemd/system/ssl-renewal.service
```

Add:

```ini
[Unit]
Description=SSL Certificate Renewal
After=network.target

[Service]
Type=oneshot
ExecStart=/usr/local/bin/renew-ssl.sh
```

Create timer:

```bash
sudo nano /etc/systemd/system/ssl-renewal.timer
```

Add:

```ini
[Unit]
Description=SSL Certificate Renewal Timer

[Timer]
OnCalendar=daily
OnCalendar=0/12:00:00
RandomizedDelaySec=3600
Persistent=true

[Install]
WantedBy=timers.target
```

Enable and start:

```bash
sudo systemctl enable ssl-renewal.timer
sudo systemctl start ssl-renewal.timer
sudo systemctl status ssl-renewal.timer
```

---

## Step 9: Update App Store Connect Webhook

### 9.1 Update Webhook URL

1. Go to [App Store Connect](https://appstoreconnect.apple.com)
2. Navigate to your app → **App Information**
3. Scroll to **Server-to-Server Notification URL**
4. Update to:
    ```
    https://api.inspirtag.com/api/webhooks/apple/subscription
    ```
5. Enable both **Production** and **Sandbox** notifications
6. Save changes

### 9.2 Test Webhook (Optional)

```bash
# Test webhook endpoint (should return 200 or 405 Method Not Allowed for GET)
curl https://api.inspirtag.com/api/webhooks/apple/subscription
```

---

## Step 10: Update Documentation

Update any documentation that references the old URL or IP:

1. **APPLE_STORE_CONNECT_SETUP_GUIDE.md** - Update webhook URL
2. **SUBSCRIPTION_API_DOCUMENTATION.md** - Update example URLs
3. **FRONTEND_SUBSCRIPTION_FLOW.md** - Update API base URL
4. Any API documentation files

---

## Step 11: Firewall Configuration

Ensure ports are open:

```bash
# Check if ports are open
sudo ufw status

# If UFW is active, allow ports
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Or if using iptables
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
```

---

## Troubleshooting

### Issue 0: Port 80 Already in Use (Deployment Error)

**Error Message**:

```
Error response from daemon: failed to bind host port for 0.0.0.0:80: address already in use
```

**Cause**: Another service (nginx, apache, or another Docker container) is using port 80.

**Solution Steps**:

1. **Identify what's using port 80**:

    ```bash
    # Check what's using port 80
    sudo lsof -i :80
    # or
    sudo netstat -tulpn | grep :80
    # or
    sudo ss -tulpn | grep :80
    ```

2. **If it's a system nginx/apache service** (Most Common):

    **For System Nginx** (Your Case):
    ```bash
    # Stop system nginx
    sudo systemctl stop nginx
    
    # Disable it from auto-starting on boot
    sudo systemctl disable nginx
    
    # Verify it's stopped
    sudo systemctl status nginx
    
    # If still running, force kill
    sudo pkill -9 nginx
    ```
    
    **Quick Fix Script** (Recommended):
    ```bash
    # Use the provided script
    chmod +x fix-nginx-conflict.sh
    ./fix-nginx-conflict.sh
    ```
    
    **For System Apache**:
    ```bash
    # Stop system apache
    sudo systemctl stop apache2
    sudo systemctl disable apache2
    ```
    
    **Important**: After stopping system nginx, Docker nginx will handle all web traffic. This is the recommended setup for Docker deployments.

3. **If it's another Docker container**:

    ```bash
    # List all running containers
    docker ps

    # Stop the container using port 80
    docker stop <container-name>

    # Or remove it if not needed
    docker rm <container-name>
    ```

4. **If it's a previous inspirtag-nginx container**:

    ```bash
    # Stop and remove old container
    docker stop inspirtag-nginx
    docker rm inspirtag-nginx

    # Or use docker-compose
    cd /var/www/inspirtag
    docker-compose down
    docker-compose up -d
    ```

5. **Verify port 80 is free**:

    ```bash
    sudo lsof -i :80
    # Should return nothing if port is free
    ```

6. **Retry deployment**:
    ```bash
    cd /var/www/inspirtag
    docker-compose up -d
    ```

**Quick Fix Script**:

```bash
#!/bin/bash
# fix-port-80.sh

echo "🔍 Checking what's using port 80..."
sudo lsof -i :80

echo ""
echo "🛑 Stopping conflicting services..."

# Stop system nginx if running
if systemctl is-active --quiet nginx; then
    echo "Stopping system nginx..."
    sudo systemctl stop nginx
    sudo systemctl disable nginx
fi

# Stop system apache if running
if systemctl is-active --quiet apache2; then
    echo "Stopping system apache..."
    sudo systemctl stop apache2
    sudo systemctl disable apache2
fi

# Stop old Docker containers using port 80
echo "Stopping old Docker containers..."
docker ps --filter "publish=80" --format "{{.Names}}" | xargs -r docker stop
docker ps -a --filter "publish=80" --format "{{.Names}}" | xargs -r docker rm

# Stop inspirtag containers
cd /var/www/inspirtag 2>/dev/null || cd /path/to/your/project
docker-compose down 2>/dev/null || true

echo ""
echo "✅ Port 80 should now be free"
echo "🔍 Verifying..."
sudo lsof -i :80 || echo "✅ Port 80 is free!"

echo ""
echo "🚀 You can now run: docker-compose up -d"
```

**Prevention**: Always stop existing services before deploying:

```bash
# Before deployment, always run:
cd /var/www/inspirtag
docker-compose down
sudo systemctl stop nginx apache2 2>/dev/null || true
docker-compose up -d
```

---

### Issue 1: DNS Not Resolving

**Symptoms**: `nslookup api.inspirtag.com` doesn't return the server IP

**Solutions**:

-   Wait longer (DNS can take up to 48 hours to propagate)
-   Verify A record is correct in DNS settings
-   Check TTL value (lower TTL = faster updates)
-   Use different DNS server: `nslookup api.inspirtag.com 8.8.8.8`

### Issue 2: SSL Certificate Generation Fails

**Symptoms**: `certbot certonly` fails with error

**Common Errors**:

-   **"Connection refused"**: Port 80 not open or nginx still running

    ```bash
    docker-compose stop nginx
    sudo netstat -tulpn | grep :80  # Check if port 80 is free
    ```

-   **"DNS problem"**: Domain not pointing to server

    ```bash
    dig api.inspirtag.com  # Verify DNS
    ```

-   **"Rate limit"**: Too many certificate requests
    -   Wait 1 week or use staging: `--staging` flag

### Issue 3: Nginx Not Starting

**Symptoms**: `docker-compose logs nginx` shows errors

**Solutions**:

```bash
# Test nginx configuration
docker-compose exec nginx nginx -t

# Check if SSL certificates are mounted
docker-compose exec nginx ls -la /etc/letsencrypt/live/api.inspirtag.com/

# Check nginx config syntax
docker-compose exec nginx cat /etc/nginx/conf.d/default.conf
```

### Issue 4: 502 Bad Gateway

**Symptoms**: HTTPS works but returns 502 error

**Solutions**:

```bash
# Check if app container is running
docker-compose ps app

# Check app logs
docker-compose logs app

# Verify PHP-FPM is accessible
docker-compose exec nginx ping app
```

### Issue 5: SSL Certificate Expired

**Symptoms**: Browser shows "Certificate Expired" error

**Solutions**:

```bash
# Manually renew certificate
sudo certbot renew

# Restart nginx
cd /var/www/inspirtag
docker-compose restart nginx

# Verify renewal
sudo certbot certificates
```

### Issue 6: Mixed Content Warnings

**Symptoms**: Browser shows mixed content warnings

**Solutions**:

-   Ensure all API calls use `https://`
-   Update `APP_URL` in `.env` to use `https://`
-   Check Laravel `config/app.php` for URL settings

---

## Verification Checklist

Before considering deployment complete, verify:

-   [ ] DNS A record added: `api` → `38.180.244.178`
-   [ ] DNS propagates correctly (checked with `nslookup` or `dig`)
-   [ ] SSL certificate generated successfully
-   [ ] Certificates exist at `/etc/letsencrypt/live/api.inspirtag.com/`
-   [ ] `docker-compose.yml` uses `nginx-ssl.conf`
-   [ ] SSL certificates mounted in Docker volume
-   [ ] `.env` file has `APP_URL=https://api.inspirtag.com`
-   [ ] Nginx container restarted
-   [ ] HTTP redirects to HTTPS (test with `curl -I http://api.inspirtag.com`)
-   [ ] HTTPS works (test with `curl https://api.inspirtag.com/health`)
-   [ ] API endpoints accessible via HTTPS
-   [ ] SSL certificate auto-renewal configured
-   [ ] App Store Connect webhook URL updated
-   [ ] Firewall allows ports 80 and 443
-   [ ] All documentation updated with new URL

---

## Quick Deployment Script

Create a deployment script for future use:

```bash
#!/bin/bash
# deploy-api-inspirtag.sh

set -e

DOMAIN="api.inspirtag.com"
EMAIL="admin@inspirtag.com"
PROJECT_DIR="/var/www/inspirtag"

echo "🚀 Deploying API to $DOMAIN"

# Step 1: Check DNS
echo "🔍 Checking DNS..."
if ! nslookup $DOMAIN | grep -q "38.180.244.178"; then
    echo "⚠️  WARNING: DNS may not be configured correctly"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Step 2: Install Certbot if needed
if ! command -v certbot &> /dev/null; then
    echo "📦 Installing Certbot..."
    sudo apt update
    sudo apt install certbot -y
fi

# Step 3: Stop nginx
echo "🛑 Stopping nginx..."
cd $PROJECT_DIR
docker-compose stop nginx

# Step 4: Generate SSL certificate
echo "🔐 Generating SSL certificate..."
sudo certbot certonly --standalone -d $DOMAIN --email $EMAIL --agree-tos --non-interactive

# Step 5: Update .env
echo "📝 Updating .env..."
if ! grep -q "APP_URL=https://$DOMAIN" .env; then
    sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN|" .env
fi

# Step 6: Restart services
echo "🔄 Restarting services..."
docker-compose restart

# Step 7: Clear cache
echo "🧹 Clearing cache..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear

# Step 8: Test
echo "✅ Testing deployment..."
sleep 5
if curl -s https://$DOMAIN/health | grep -q "healthy"; then
    echo "✅ Deployment successful! API is live at https://$DOMAIN"
else
    echo "❌ Deployment may have issues. Check logs: docker-compose logs nginx"
fi

echo ""
echo "📋 Next steps:"
echo "1. Update App Store Connect webhook URL: https://$DOMAIN/api/webhooks/apple/subscription"
echo "2. Setup SSL auto-renewal (see Step 8 in deployment guide)"
echo "3. Update frontend API base URL to: https://$DOMAIN"
```

Make executable:

```bash
chmod +x deploy-api-inspirtag.sh
```

Run:

```bash
./deploy-api-inspirtag.sh
```

---

## Production Best Practices

1. **Monitor SSL Certificate Expiry**

    - Set up alerts for certificate expiration
    - Test renewal process regularly

2. **Backup SSL Certificates**

    ```bash
    # Backup certificates
    sudo tar -czf ssl-backup-$(date +%Y%m%d).tar.gz /etc/letsencrypt
    ```

3. **Monitor Server Resources**

    - Set up monitoring for CPU, memory, disk
    - Monitor Docker container health

4. **Log Management**

    - Configure log rotation
    - Monitor error logs regularly

5. **Security Updates**
    - Keep Docker images updated
    - Regularly update system packages
    - Monitor security advisories

---

## Summary

After completing this guide:

✅ **Domain**: `api.inspirtag.com` points to `38.180.244.178`  
✅ **SSL**: HTTPS enabled with Let's Encrypt certificate  
✅ **API**: Accessible at `https://api.inspirtag.com/api/...`  
✅ **Webhooks**: Apple webhooks configured  
✅ **Auto-Renewal**: SSL certificates auto-renew  
✅ **Production Ready**: Secure, monitored, and documented

**API Base URL**: `https://api.inspirtag.com`

---

**Last Updated**: January 2025  
**Version**: 1.0
