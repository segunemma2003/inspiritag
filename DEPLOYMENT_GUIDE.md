# Inspirtag Deployment Guide

## 🚀 Deployment Setup

### 1. VPS Initial Setup (Run Once)

SSH into your VPS and run the setup script:

```bash
# SSH into your VPS
ssh root@[SERVER_IP]

# Download and run setup script
curl -o vps-setup.sh https://raw.githubusercontent.com/yourusername/inspirtag/main/vps-setup.sh
chmod +x vps-setup.sh
./vps-setup.sh
```

### 2. GitHub Repository Secrets

Go to your GitHub repository → Settings → Secrets and variables → Actions

Add these secrets:

| Secret Name    | Value            | Description         |
| -------------- | ---------------- | ------------------- |
| `VPS_HOST`     | `[SERVER_IP]` | Your VPS IP address |
| `VPS_USER`     | `root`           | VPS username        |
| `VPS_PASSWORD` | `[SERVER_PASSWORD]`     | VPS password        |

### 3. Environment Configuration

On your VPS, edit the `.env` file:

```bash
cd /var/www/inspirtag
nano .env
```

Update these values:

```env
# Generate app key
APP_KEY=base64:your_generated_key_here

# Database (already configured)
DB_PASSWORD=[SERVER_PASSWORD]

# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_s3_bucket_name

# Firebase Configuration
FIREBASE_SERVER_KEY=your_firebase_server_key

# Production URL
APP_URL=http://[SERVER_IP]:8000
```

### 4. First Deployment

#### Option A: Automatic (GitHub Actions)

1. Push your code to the `main` branch
2. GitHub Actions will automatically deploy

#### Option B: Manual

```bash
# SSH into VPS
ssh root@[SERVER_IP]

# Navigate to project
cd /var/www/inspirtag

# Clone repository (first time only)
git clone https://github.com/yourusername/inspirtag.git .

# Run deployment script
chmod +x deploy.sh
./deploy.sh
```

### 5. Subsequent Deployments

#### Automatic Deployments

-   Push to `main` branch
-   GitHub Actions handles everything automatically

#### Manual Deployments

```bash
# SSH into VPS
ssh root@[SERVER_IP]

# Navigate to project
cd /var/www/inspirtag

# Run deployment script
./deploy.sh
```

## 🔧 Deployment Features

### ✅ What the deployment does:

1. **Git Pull** - Gets latest changes from repository
2. **Docker Management** - Builds/restarts containers only when needed
3. **Database Migrations** - Runs Laravel migrations automatically
4. **Cache Management** - Clears and warms up caches
5. **Queue Workers** - Restarts background job processors
6. **Health Checks** - Verifies API is responding
7. **Service Monitoring** - Shows container status

### 🚀 Performance Optimizations:

-   **Smart Rebuilds** - Only rebuilds containers when Docker files change
-   **Efficient Updates** - Uses `git pull` instead of full file transfers
-   **Cache Warming** - Pre-loads critical data
-   **Queue Management** - Handles background jobs properly

## 📊 Monitoring

### Check Deployment Status:

```bash
# Check containers
docker-compose ps

# Check logs
docker-compose logs app

# Check API health
curl http://[SERVER_IP]:8000/api/categories
```

### View GitHub Actions:

-   Go to your repository → Actions tab
-   See deployment history and logs

## 🔄 Rollback (If Needed)

```bash
# SSH into VPS
ssh root@[SERVER_IP]

# Navigate to project
cd /var/www/inspirtag

# Rollback to previous commit
git log --oneline -5  # See recent commits
git reset --hard HEAD~1  # Go back one commit

# Rebuild and restart
docker-compose down
docker-compose up -d --build
```

## 🛠️ Troubleshooting

### Common Issues:

1. **Docker not running:**

    ```bash
    sudo systemctl start docker
    ```

2. **Permission issues:**

    ```bash
    sudo chown -R $USER:$USER /var/www/inspirtag
    ```

3. **Database connection:**

    ```bash
    # Check MySQL status
    sudo systemctl status mysql

    # Restart MySQL
    sudo systemctl restart mysql
    ```

4. **API not responding:**

    ```bash
    # Check container logs
    docker-compose logs app

    # Restart containers
    docker-compose restart
    ```

## 📱 API Endpoints

After successful deployment, your API will be available at:

-   **Base URL:** `http://[SERVER_IP]:8000/api`
-   **Health Check:** `http://[SERVER_IP]:8000/api/categories`
-   **Documentation:** See `API_DOCUMENTATION.md`

## 🔐 Security Notes

-   Change default passwords after setup
-   Configure SSL/TLS certificates
-   Set up firewall rules
-   Regular security updates
-   Monitor access logs

---

**🎉 Your Inspirtag API is now ready for production!**
