# Setting Up Subdomain for Backend API

## Overview
This guide shows you how to point your backend API to a subdomain (e.g., `api.inspirtag.com`) instead of using an IP address.

---

## Step 1: DNS Configuration

### 1.1 Add A Record to Your Domain

1. Go to your domain registrar (where you bought the domain)
   - Examples: GoDaddy, Namecheap, Cloudflare, etc.

2. Access DNS Management:
   - Find **DNS Settings** or **DNS Management**
   - Look for **A Records** section

3. Add New A Record:
   - **Type**: A
   - **Name/Host**: `api` (or `api.inspirtag` depending on your registrar)
   - **Value/IP**: `38.180.244.178` (your server IP)
   - **TTL**: 3600 (or default)

4. **Example**:
   ```
   Type: A
   Name: api
   Value: 38.180.244.178
   TTL: 3600
   ```

5. **Result**: `api.yourdomain.com` → `38.180.244.178`

### 1.2 Verify DNS Propagation

Wait 5-60 minutes for DNS to propagate, then test:

```bash
# Check if DNS is working
nslookup api.yourdomain.com
# or
dig api.yourdomain.com

# Should return: 38.180.244.178
```

**Online DNS Checker**: https://www.whatsmydns.net/

---

## Step 2: Update Nginx Configuration

### 2.1 Update Nginx Config File

Update `docker/nginx.conf` to accept your subdomain:

**Current** (`docker/nginx.conf`):
```nginx
server {
    listen 80;
    server_name localhost;
    ...
}
```

**Updated** (`docker/nginx.conf`):
```nginx
server {
    listen 80;
    server_name api.yourdomain.com 38.180.244.178;
    root /var/www/html/public;
    index index.php index.html;

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

**Replace `api.yourdomain.com` with your actual subdomain** (e.g., `api.inspirtag.com`)

### 2.2 Add HTTPS Configuration (Recommended)

For Apple webhooks and security, you need HTTPS. Create SSL configuration:

**Option A: Using Let's Encrypt (Free SSL)**

Create `docker/nginx-ssl.conf`:

```nginx
# HTTP server - redirect to HTTPS
server {
    listen 80;
    server_name api.yourdomain.com 38.180.244.178;
    
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
    server_name api.yourdomain.com;
    root /var/www/html/public;
    index index.php index.html;

    # SSL certificates (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.yourdomain.com/privkey.pem;
    
    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

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

---

## Step 3: Install SSL Certificate (Let's Encrypt)

### 3.1 Install Certbot on Server

SSH into your server and run:

```bash
# Install certbot
sudo apt update
sudo apt install certbot -y

# Stop nginx temporarily (if running on host)
sudo systemctl stop nginx 2>/dev/null || true
```

### 3.2 Generate SSL Certificate

```bash
# Generate certificate (replace with your email and domain)
sudo certbot certonly --standalone -d api.yourdomain.com --email your-email@example.com --agree-tos --non-interactive

# Certificates will be saved to:
# /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem
# /etc/letsencrypt/live/api.yourdomain.com/privkey.pem
```

### 3.3 Update Docker Compose to Mount SSL Certificates

Update `docker-compose.yml` nginx service:

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
    - ./docker/nginx-ssl.conf:/etc/nginx/conf.d/default.conf
    - /etc/letsencrypt:/etc/letsencrypt:ro  # Mount SSL certificates
  depends_on:
    - app
  networks:
    - inspirtag-network
```

### 3.4 Auto-Renew SSL Certificate

Set up auto-renewal:

```bash
# Test renewal
sudo certbot renew --dry-run

# Add to crontab (runs twice daily)
sudo crontab -e

# Add this line:
0 0,12 * * * certbot renew --quiet && docker-compose -f /var/www/inspirtag/docker-compose.yml restart nginx
```

---

## Step 4: Update Environment Variables

### 4.1 Update APP_URL

In your `.env` file on the server:

```env
APP_URL=https://api.yourdomain.com
```

Or if using HTTP (not recommended for production):
```env
APP_URL=http://api.yourdomain.com
```

### 4.2 Update Apple Webhook URL

In App Store Connect:
- Set **Server-to-Server Notification URL** to:
  ```
  https://api.yourdomain.com/api/webhooks/apple/subscription
  ```

---

## Step 5: Test Subdomain

### 5.1 Test HTTP (if not using HTTPS redirect)

```bash
curl http://api.yourdomain.com/health
# Should return: healthy
```

### 5.2 Test HTTPS

```bash
curl https://api.yourdomain.com/health
# Should return: healthy
```

### 5.3 Test API Endpoint

```bash
curl https://api.yourdomain.com/api/categories
# Should return categories JSON
```

---

## Step 6: Update Documentation

Update any documentation that references the IP address:

- `APPLE_STORE_CONNECT_SETUP_GUIDE.md` - Update webhook URL
- `SUBSCRIPTION_API_DOCUMENTATION.md` - Update example URLs
- Any API documentation

---

## Quick Setup Script

Create a script to automate the setup:

```bash
#!/bin/bash
# setup_subdomain.sh

DOMAIN="api.yourdomain.com"
EMAIL="your-email@example.com"

echo "🔧 Setting up subdomain: $DOMAIN"

# 1. Install certbot
sudo apt update
sudo apt install certbot -y

# 2. Generate SSL certificate
sudo certbot certonly --standalone -d $DOMAIN --email $EMAIL --agree-tos --non-interactive

# 3. Update nginx config (you'll need to edit manually)
echo "📝 Update docker/nginx.conf with server_name: $DOMAIN"

# 4. Update .env
echo "APP_URL=https://$DOMAIN" >> .env

# 5. Restart services
cd /var/www/inspirtag
docker-compose restart nginx

echo "✅ Setup complete! Test with: curl https://$DOMAIN/health"
```

---

## Alternative: Using Cloudflare (Easier SSL)

If you use Cloudflare:

1. **Add DNS Record**:
   - Type: A
   - Name: `api`
   - IPv4: `38.180.244.178`
   - Proxy: ✅ Proxied (orange cloud)

2. **Enable SSL**:
   - Cloudflare → SSL/TLS
   - Set to **Full** or **Full (strict)**
   - SSL certificate is automatic (Cloudflare handles it)

3. **Update Nginx**:
   - Still configure `server_name api.yourdomain.com`
   - Cloudflare handles SSL termination

**Note**: Apple webhooks should work with Cloudflare SSL, but you may need to verify certificate chain.

---

## Troubleshooting

### DNS Not Working
- Wait longer (up to 48 hours for full propagation)
- Check DNS record is correct
- Use `dig` or `nslookup` to verify

### SSL Certificate Issues
- Ensure port 80 is open for Let's Encrypt validation
- Check domain points to correct IP
- Verify email is correct

### Nginx Not Loading
- Check Docker logs: `docker-compose logs nginx`
- Verify nginx config: `docker-compose exec nginx nginx -t`
- Restart nginx: `docker-compose restart nginx`

### 403 Forbidden
- Check file permissions
- Verify `root` path is correct
- Check Laravel `public` directory exists

---

## Summary

1. ✅ Add A record in DNS: `api` → `38.180.244.178`
2. ✅ Update `docker/nginx.conf` with `server_name api.yourdomain.com`
3. ✅ Install SSL certificate (Let's Encrypt)
4. ✅ Update `.env` with `APP_URL=https://api.yourdomain.com`
5. ✅ Update App Store Connect webhook URL
6. ✅ Restart Docker containers
7. ✅ Test subdomain

**Final URL**: `https://api.yourdomain.com/api/...`

