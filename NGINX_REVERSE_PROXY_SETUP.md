# Nginx Reverse Proxy Setup - Multiple Sites on Server

## Overview

When you have other sites running on system nginx, we need to configure it as a reverse proxy that forwards requests for `api.inspirtag.com` to the Docker nginx container.

**Architecture**:

```
Internet → System Nginx (Port 80/443) → Docker Nginx (Port 8080) → Laravel App
```

---

## Solution: Configure System Nginx as Reverse Proxy

### Step 1: Change Docker Nginx Port

Update `docker-compose.yml` to use port 8080 instead of 80:

```yaml
nginx:
    image: nginx:alpine
    container_name: inspirtag-nginx
    restart: unless-stopped
    ports:
        - "8080:80" # Changed from "80:80" to "8080:80"
        - "8443:443" # Changed from "443:443" to "8443:443" (if using HTTPS in Docker)
    volumes:
        - ./:/var/www/html
        - ./docker/nginx-ssl.conf:/etc/nginx/conf.d/default.conf
        - /etc/letsencrypt:/etc/letsencrypt:ro
    depends_on:
        - app
    networks:
        - inspirtag-network
```

**Or simpler approach** - Only expose port 8080 and let system nginx handle SSL:

```yaml
nginx:
    image: nginx:alpine
    container_name: inspirtag-nginx
    restart: unless-stopped
    ports:
        - "8080:80" # Only expose HTTP, system nginx handles SSL
    volumes:
        - ./:/var/www/html
        - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf # Use HTTP config
    depends_on:
        - app
    networks:
        - inspirtag-network
```

### Step 2: Create System Nginx Configuration

Create a new nginx config file for `api.inspirtag.com`:

```bash
sudo nano /etc/nginx/sites-available/api.inspirtag.com
```

Add this configuration:

```nginx
# HTTP server - redirect to HTTPS
server {
    listen 80;
    server_name api.inspirtag.com;

    # For Let's Encrypt validation
    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    # Redirect all other traffic to HTTPS
    location / {
        return 301 https://$server_name$request_uri;
    }
}

# HTTPS server - proxy to Docker nginx
server {
    listen 443 ssl http2;
    server_name api.inspirtag.com;

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

    # Proxy settings
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Forwarded-Port $server_port;

    # Allow large file uploads
    client_max_body_size 50M;
    proxy_max_temp_file_size 0;
    proxy_buffering off;
    proxy_request_buffering off;

    # Timeouts
    proxy_connect_timeout 300;
    proxy_send_timeout 300;
    proxy_read_timeout 300;

    # Proxy to Docker nginx (running on port 8080)
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_redirect off;
    }

    # Health check (optional - can proxy or handle directly)
    location /health {
        proxy_pass http://127.0.0.1:8080/health;
        access_log off;
    }
}
```

### Step 3: Enable the Site

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/api.inspirtag.com /etc/nginx/sites-enabled/

# Test nginx configuration
sudo nginx -t

# If test passes, reload nginx
sudo systemctl reload nginx
```

### Step 4: Update Docker Nginx Config

Since system nginx handles SSL, update `docker/nginx.conf` to only listen on HTTP:

```nginx
server {
    listen 80;
    server_name api.inspirtag.com localhost;
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

### Step 5: Generate SSL Certificate (on Host)

Since system nginx handles SSL, generate certificate on the host:

```bash
# Stop system nginx temporarily
sudo systemctl stop nginx

# Generate certificate
sudo certbot certonly --standalone -d api.inspirtag.com --email admin@inspirtag.com --agree-tos --non-interactive

# Start nginx again
sudo systemctl start nginx
```

### Step 6: Restart Services

```bash
# Restart Docker containers
cd /var/www/inspirtag
docker-compose restart

# Reload system nginx
sudo systemctl reload nginx
```

---

## Alternative: Simpler Setup (HTTP Only in Docker)

If you want to keep it simpler, you can have Docker nginx only handle HTTP and system nginx handles SSL:

### docker-compose.yml

```yaml
nginx:
    image: nginx:alpine
    container_name: inspirtag-nginx
    restart: unless-stopped
    ports:
        - "8080:80" # Only HTTP, system nginx handles SSL
    volumes:
        - ./:/var/www/html
        - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
        - app
    networks:
        - inspirtag-network
```

### System Nginx Config (Simplified)

```nginx
server {
    listen 443 ssl http2;
    server_name api.inspirtag.com;

    ssl_certificate /etc/letsencrypt/live/api.inspirtag.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.inspirtag.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

---

## Testing

```bash
# Test Docker nginx directly (should work)
curl http://localhost:8080/health

# Test through system nginx (should work)
curl https://api.inspirtag.com/health

# Test API endpoint
curl https://api.inspirtag.com/api/categories
```

---

## Troubleshooting

### Docker nginx not accessible

```bash
# Check if Docker container is running
docker ps | grep inspirtag-nginx

# Check Docker nginx logs
docker logs inspirtag-nginx

# Test direct connection
curl http://127.0.0.1:8080/health
```

### System nginx proxy errors

```bash
# Check system nginx error logs
sudo tail -f /var/log/nginx/error.log

# Test nginx configuration
sudo nginx -t

# Check if port 8080 is accessible from host
netstat -tulpn | grep 8080
```

### SSL certificate issues

```bash
# Verify certificate exists
ls -la /etc/letsencrypt/live/api.inspirtag.com/

# Test certificate
sudo openssl x509 -in /etc/letsencrypt/live/api.inspirtag.com/fullchain.pem -text -noout
```

---

## Summary

1. ✅ Docker nginx runs on port 8080 (HTTP only)
2. ✅ System nginx handles SSL termination (port 443)
3. ✅ System nginx proxies requests to Docker nginx
4. ✅ Other sites continue working on system nginx
5. ✅ `api.inspirtag.com` accessible via HTTPS

**Benefits**:

-   No port conflicts
-   System nginx continues serving other sites
-   Centralized SSL management
-   Docker container isolated
