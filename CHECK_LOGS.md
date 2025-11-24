# How to Check Server Logs

## Quick Commands (SSH into your server first)

### 1. Check Laravel Logs (Most Important)
```bash
docker-compose -p inspirtag exec app tail -100 /var/www/html/storage/logs/laravel.log
```

### 2. Check for Recent Errors Only
```bash
docker-compose -p inspirtag exec app tail -200 /var/www/html/storage/logs/laravel.log | grep -i "error\|exception\|fatal"
```

### 3. Check create-from-s3 Specific Errors
```bash
docker-compose -p inspirtag exec app tail -200 /var/www/html/storage/logs/laravel.log | grep -i "createfroms3\|create-from-s3\|S3Service" | tail -20
```

### 4. Check Real-time Logs (follow mode)
```bash
docker-compose -p inspirtag exec app tail -f /var/www/html/storage/logs/laravel.log
```

### 5. Check Last 50 Error Entries
```bash
docker-compose -p inspirtag exec app grep -i "production.ERROR" /var/www/html/storage/logs/laravel.log | tail -20
```

### 6. Check Nginx Error Logs
```bash
docker-compose -p inspirtag logs nginx --tail=50 | grep -i error
```

### 7. Check PHP Error Logs (if configured)
```bash
docker-compose -p inspirtag exec app tail -30 /var/log/php_errors.log 2>/dev/null || echo "No PHP error log"
```

### 8. Check Application Container Logs
```bash
docker-compose -p inspirtag logs app --tail=50
```

## Search for Specific Error

To search for errors from a specific time (e.g., last 10 minutes):
```bash
docker-compose -p inspirtag exec app tail -1000 /var/www/html/storage/logs/laravel.log | grep "$(date '+%Y-%m-%d %H:%M')"
```

## Export Logs to Local File

```bash
docker-compose -p inspirtag exec app cat /var/www/html/storage/logs/laravel.log > laravel_errors.log
```

## Using the Helper Script

If you upload `check_server_logs.sh` to your server:
```bash
chmod +x check_server_logs.sh
./check_server_logs.sh
```

