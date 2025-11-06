# Deployment Checklist - api.inspirtag.com

Quick reference checklist for deploying the API to `api.inspirtag.com` with SSL.

## Pre-Deployment

-   [ ] Server IP: `38.180.244.178` is accessible
-   [ ] Domain `inspirtag.com` is registered
-   [ ] SSH access to server configured
-   [ ] Docker and Docker Compose installed on server
-   [ ] Project code is on server at `/var/www/inspirtag` (or your path)

## DNS Configuration

-   [ ] Added A record: `api` → `38.180.244.178`
-   [ ] DNS TTL set to 3600 (or default)
-   [ ] Verified DNS propagation: `nslookup api.inspirtag.com`
-   [ ] DNS resolves to correct IP (wait 5-60 minutes if needed)

## SSL Certificate Setup

-   [ ] Installed Certbot: `sudo apt install certbot -y`
-   [ ] Stopped nginx container: `docker-compose stop nginx`
-   [ ] Generated SSL certificate: `sudo certbot certonly --standalone -d api.inspirtag.com --email admin@inspirtag.com --agree-tos --non-interactive`
-   [ ] Verified certificates exist: `ls -la /etc/letsencrypt/live/api.inspirtag.com/`
-   [ ] Certificates include: `fullchain.pem` and `privkey.pem`

## Configuration Files

-   [ ] `docker/nginx-ssl.conf` exists and configured for `api.inspirtag.com`
-   [ ] `docker-compose.yml` uses `nginx-ssl.conf` (not `nginx.conf`)
-   [ ] `docker-compose.yml` mounts SSL certificates: `/etc/letsencrypt:/etc/letsencrypt:ro`
-   [ ] `.env` file has `APP_URL=https://api.inspirtag.com`
-   [ ] `.env` file has `APP_ENV=production`
-   [ ] `.env` file has `APP_DEBUG=false`

## Deployment Steps

-   [ ] Restarted nginx: `docker-compose restart nginx`
-   [ ] Verified nginx config: `docker-compose exec nginx nginx -t`
-   [ ] Cleared Laravel cache: `docker-compose exec app php artisan config:clear`
-   [ ] Cleared route cache: `docker-compose exec app php artisan route:clear`
-   [ ] Cleared view cache: `docker-compose exec app php artisan view:clear`

## Testing

-   [ ] HTTP redirects to HTTPS: `curl -I http://api.inspirtag.com/health`
-   [ ] HTTPS health check works: `curl https://api.inspirtag.com/health`
-   [ ] API endpoint accessible: `curl https://api.inspirtag.com/api/categories`
-   [ ] SSL certificate valid: `openssl s_client -connect api.inspirtag.com:443`
-   [ ] No SSL errors in browser
-   [ ] Authentication endpoints work with HTTPS

## App Store Connect

-   [ ] Updated webhook URL: `https://api.inspirtag.com/api/webhooks/apple/subscription`
-   [ ] Enabled Production notifications
-   [ ] Enabled Sandbox notifications
-   [ ] Saved changes in App Store Connect

## SSL Auto-Renewal

-   [ ] Tested renewal: `sudo certbot renew --dry-run`
-   [ ] Added cron job or systemd timer for auto-renewal
-   [ ] Verified renewal script restarts nginx after renewal

## Firewall

-   [ ] Port 80 (HTTP) is open
-   [ ] Port 443 (HTTPS) is open
-   [ ] Verified with: `sudo ufw status` or `sudo iptables -L`

## Documentation

-   [ ] Updated `APPLE_STORE_CONNECT_SETUP_GUIDE.md` with new webhook URL
-   [ ] Updated `SUBSCRIPTION_API_DOCUMENTATION.md` with new API URL
-   [ ] Updated `FRONTEND_SUBSCRIPTION_FLOW.md` with new API base URL
-   [ ] Updated any other documentation referencing old URL/IP

## Final Verification

-   [ ] All API endpoints work via HTTPS
-   [ ] Frontend can connect to API
-   [ ] Apple webhooks can reach the server
-   [ ] No mixed content warnings
-   [ ] SSL certificate shows valid in browser
-   [ ] Server logs show no errors
-   [ ] Health check endpoint returns "healthy"

## Post-Deployment

-   [ ] Monitored server for 24 hours
-   [ ] Set up SSL expiration alerts
-   [ ] Backed up SSL certificates
-   [ ] Documented any custom configurations
-   [ ] Team notified of new API URL

---

## Quick Commands Reference

```bash
# Check DNS
nslookup api.inspirtag.com

# Generate SSL certificate
sudo certbot certonly --standalone -d api.inspirtag.com --email admin@inspirtag.com --agree-tos --non-interactive

# Test HTTPS
curl https://api.inspirtag.com/health

# Restart services
cd /var/www/inspirtag && docker-compose restart

# Check nginx config
docker-compose exec nginx nginx -t

# View logs
docker-compose logs nginx
docker-compose logs app

# Test SSL certificate
openssl s_client -connect api.inspirtag.com:443 -servername api.inspirtag.com
```

---

**Date Deployed**: ******\_\_\_******  
**Deployed By**: ******\_\_\_******  
**Notes**: ******\_\_\_******
