# Quick Setup: System Nginx with Docker

## ✅ What's Fixed

Your `docker-compose.yml` has been updated to:

-   Use port **8080** instead of 80 (no conflict with system nginx)
-   Use HTTP-only config (system nginx handles SSL)

## 🚀 Next Steps on Your Server

### Step 1: Deploy Docker Containers

```bash
cd /var/www/inspirtag  # or your project path
docker-compose down     # Stop any existing containers
docker-compose up -d    # Start with new configuration
```

This should now work without port conflicts!

### Step 2: Verify Docker Nginx is Running

```bash
# Check if container is running
docker ps | grep inspirtag-nginx

# Test Docker nginx directly
curl http://localhost:8080/health
# Should return: healthy
```

### Step 3: Configure System Nginx (Reverse Proxy)

Create system nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/api.inspirtag.com
```

Paste this configuration:

```nginx
# HTTP - redirect to HTTPS
server {
    listen 80;
    server_name api.inspirtag.com;

    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    location / {
        return 301 https://$server_name$request_uri;
    }
}

# HTTPS - proxy to Docker nginx
server {
    listen 443 ssl http2;
    server_name api.inspirtag.com;

    # SSL certificates (generate with certbot)
    ssl_certificate /etc/letsencrypt/live/api.inspirtag.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.inspirtag.com/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Proxy settings
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;

    client_max_body_size 50M;

    # Proxy to Docker nginx on port 8080
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_redirect off;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/api.inspirtag.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 4: Generate SSL Certificate

```bash
# Stop nginx temporarily
sudo systemctl stop nginx

# Generate certificate
sudo certbot certonly --standalone \
    -d api.inspirtag.com \
    --email admin@inspirtag.com \
    --agree-tos \
    --non-interactive

# Start nginx
sudo systemctl start nginx
```

### Step 5: Test Everything

```bash
# Test Docker nginx
curl http://localhost:8080/health

# Test through system nginx (if DNS is configured)
curl https://api.inspirtag.com/health
```

## 📋 Summary

-   ✅ Docker nginx: Running on port **8080** (no conflicts)
-   ✅ System nginx: Proxies `api.inspirtag.com` → Docker nginx
-   ✅ SSL: Handled by system nginx
-   ✅ Other sites: Continue working on system nginx

## 🔧 Troubleshooting

**Docker containers won't start?**

```bash
docker-compose down
docker-compose up -d
docker-compose logs nginx
```

**System nginx config error?**

```bash
sudo nginx -t
sudo tail -f /var/log/nginx/error.log
```

**Port 8080 not accessible?**

```bash
# Check if Docker nginx is running
docker ps | grep nginx

# Check port
netstat -tulpn | grep 8080
```

## 📚 Full Documentation

See `NGINX_REVERSE_PROXY_SETUP.md` for complete details.
