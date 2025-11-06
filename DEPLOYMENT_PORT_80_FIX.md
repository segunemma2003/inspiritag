# Quick Fix: Port 80 Already in Use

## Problem

During deployment, you're getting this error:

```
Error response from daemon: failed to bind host port for 0.0.0.0:80: address already in use
```

This means something else is already using port 80 on your server.

## Quick Solution

### Option 1: Run the Fix Script (Recommended)

```bash
# Make script executable (if not already)
chmod +x fix-port-80.sh

# Run the fix script
./fix-port-80.sh

# Then retry deployment
cd /var/www/inspirtag
docker-compose up -d
```

### Option 2: Manual Fix

**Step 1: Find what's using port 80**

```bash
sudo lsof -i :80
# or
sudo netstat -tulpn | grep :80
```

**Step 2: Stop the conflicting service**

If it's system nginx:

```bash
sudo systemctl stop nginx
sudo systemctl disable nginx
```

If it's system apache:

```bash
sudo systemctl stop apache2
sudo systemctl disable apache2
```

If it's a Docker container:

```bash
# Find the container
docker ps | grep 80

# Stop it
docker stop <container-name>
docker rm <container-name>
```

If it's an old inspirtag container:

```bash
cd /var/www/inspirtag
docker-compose down
```

**Step 3: Verify port is free**

```bash
sudo lsof -i :80
# Should return nothing
```

**Step 4: Retry deployment**

```bash
cd /var/www/inspirtag
docker-compose up -d
```

## Prevention

Always stop existing services before deploying:

```bash
# Before every deployment
cd /var/www/inspirtag
docker-compose down
sudo systemctl stop nginx apache2 2>/dev/null || true
docker-compose up -d
```

## Common Causes

1. **System nginx/apache** - Installed on the host, not in Docker
2. **Old Docker containers** - Previous deployment containers still running
3. **Other applications** - Other web servers or services using port 80

## Still Having Issues?

1. Check if port 80 is actually free:

    ```bash
    sudo lsof -i :80
    sudo netstat -tulpn | grep :80
    ```

2. Check all Docker containers:

    ```bash
    docker ps -a
    ```

3. Check system services:

    ```bash
    sudo systemctl list-units --type=service | grep -E 'nginx|apache'
    ```

4. If nothing shows up but port is still in use, check for processes:
    ```bash
    sudo ps aux | grep -E 'nginx|apache|httpd'
    ```

## Need More Help?

See the full troubleshooting section in `DEPLOYMENT_GUIDE.md` - Issue 0: Port 80 Already in Use
