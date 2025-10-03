# Performance Monitoring & Management

## 🚀 Performance Commands

### 1. Performance Monitoring

```bash
# Real-time performance dashboard
php artisan performance:dashboard

# Auto-refreshing dashboard (every 5 seconds)
php artisan performance:dashboard --refresh

# Detailed performance metrics
php artisan performance:monitor --detailed
```

### 2. Database Health

```bash
# Database health check
php artisan db:health-check

# Fix common database issues
php artisan db:health-check --fix
```

### 3. Cache Management

```bash
# Cache status
php artisan cache:manage status

# Clear all cache
php artisan cache:manage clear

# Clear specific cache pattern
php artisan cache:manage clear --pattern="user_*"

# Warm up cache
php artisan cache:manage warm

# Cache monitoring
php artisan cache:manage monitor

# Cache statistics
php artisan cache:manage stats
```

### 4. Queue Management

```bash
# Queue status
php artisan queue:manage status

# Clear queue
php artisan queue:manage clear

# Clear specific queue
php artisan queue:manage clear --queue=notifications

# Queue monitoring
php artisan queue:manage monitor

# Queue statistics
php artisan queue:manage stats

# Test queue
php artisan queue:manage test
```

### 5. System Health

```bash
# System health check
php artisan system:health-check

# Detailed system metrics
php artisan system:health-check --detailed
```

### 6. Performance Testing

```bash
# Performance test with default settings
php artisan performance:test

# Custom performance test
php artisan performance:test --users=5000 --posts=500 --duration=120 --concurrent=20
```

## 📊 Monitoring Dashboard

### Real-time Dashboard

The performance dashboard provides real-time monitoring of:

-   System overview and status
-   Database performance metrics
-   Cache performance and hit rates
-   Queue status and job counts
-   User activity and engagement
-   Content metrics and growth
-   Performance alerts and warnings

### Auto-refreshing Dashboard

```bash
php artisan performance:dashboard --refresh
```

This command refreshes the dashboard every 5 seconds, providing live monitoring of system performance.

## 🔧 Scheduled Monitoring

### Automatic Monitoring

The system automatically runs monitoring tasks:

-   **Cache Warming**: Every 5 minutes
-   **Performance Monitoring**: Every 10 minutes
-   **Database Health Check**: Every 30 minutes
-   **System Health Check**: Every hour
-   **Cache Cleanup**: Every 6 hours
-   **Queue Monitoring**: Every 15 minutes
-   **Performance Testing**: Daily at 2 AM

### Manual Monitoring

```bash
# Run all monitoring tasks
php artisan schedule:run

# Check scheduled tasks
php artisan schedule:list
```

## 📈 Performance Metrics

### Database Performance

-   **Connection Time**: < 50ms
-   **Query Performance**: < 100ms per query
-   **Index Usage**: Optimized with strategic indexes
-   **Relationship Queries**: Eager loading implemented

### Cache Performance

-   **Write Time**: < 10ms per operation
-   **Read Time**: < 5ms per operation
-   **Hit Rate**: > 80% target
-   **Memory Usage**: Optimized with TTL

### Queue Performance

-   **Job Processing**: < 30s per job
-   **Queue Size**: Monitored and managed
-   **Failed Jobs**: < 1% failure rate
-   **Worker Status**: Active monitoring

### System Performance

-   **Memory Usage**: < 80% of limit
-   **CPU Usage**: < 70% average
-   **Disk Space**: < 90% usage
-   **Load Average**: < 4.0

## 🚨 Performance Alerts

### Automatic Alerts

The system automatically detects and alerts on:

-   Slow database queries (> 100ms)
-   High memory usage (> 80% of limit)
-   Failed queue jobs
-   Low cache hit rate (< 80%)
-   High system load (> 4.0)
-   Disk space issues (> 90%)

### Alert Management

```bash
# Check current alerts
php artisan performance:dashboard

# View detailed alerts
php artisan system:health-check --detailed
```

## 🔍 Troubleshooting

### Common Issues

#### 1. Slow Database Queries

```bash
# Check database health
php artisan db:health-check

# Optimize queries
php artisan cache:warm-up
```

#### 2. High Memory Usage

```bash
# Check system resources
php artisan system:health-check

# Clear cache
php artisan cache:manage clear
```

#### 3. Queue Issues

```bash
# Check queue status
php artisan queue:manage status

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

#### 4. Cache Issues

```bash
# Check cache status
php artisan cache:manage status

# Warm up cache
php artisan cache:manage warm

# Clear cache
php artisan cache:manage clear
```

### Performance Optimization

#### 1. Database Optimization

-   Ensure all indexes are created
-   Monitor slow query log
-   Use eager loading for relationships
-   Implement query result caching

#### 2. Cache Optimization

-   Warm up critical caches
-   Monitor cache hit rates
-   Implement cache invalidation
-   Use appropriate TTL values

#### 3. Queue Optimization

-   Monitor queue sizes
-   Process failed jobs
-   Scale queue workers
-   Implement job batching

#### 4. System Optimization

-   Monitor resource usage
-   Scale infrastructure
-   Optimize configuration
-   Implement load balancing

## 📊 Performance Reports

### Daily Reports

-   System health summary
-   Performance metrics
-   Alert notifications
-   Optimization recommendations

### Weekly Reports

-   Performance trends
-   Capacity planning
-   Cost optimization
-   Scaling recommendations

### Monthly Reports

-   Performance analysis
-   Growth metrics
-   Infrastructure costs
-   Future planning

## 🚀 Scaling Recommendations

### For 100,000+ Users

1. **Database Scaling**

    - Read replicas for heavy read operations
    - Connection pooling
    - Query optimization

2. **Cache Scaling**

    - Redis cluster for high availability
    - Cache warming strategies
    - Memory optimization

3. **Queue Scaling**

    - Multiple queue workers
    - Job prioritization
    - Dead letter queue handling

4. **Application Scaling**
    - Load balancing
    - Horizontal scaling
    - CDN integration
    - API rate limiting

### Performance Targets

-   **API Response Time**: < 200ms average
-   **Database Queries**: < 50ms per query
-   **Cache Hit Rate**: > 90%
-   **Concurrent Users**: 10,000+ simultaneous
-   **Throughput**: 100,000+ requests/hour

## 🔧 Maintenance

### Daily Maintenance

```bash
# Run performance monitoring
php artisan performance:monitor

# Check system health
php artisan system:health-check

# Monitor queues
php artisan queue:manage monitor
```

### Weekly Maintenance

```bash
# Performance testing
php artisan performance:test

# Database health check
php artisan db:health-check --fix

# Cache optimization
php artisan cache:manage stats
```

### Monthly Maintenance

```bash
# Full system health check
php artisan system:health-check --detailed

# Performance analysis
php artisan performance:dashboard

# Capacity planning review
```

## 📚 Additional Resources

### Documentation

-   [Performance Optimization Guide](PERFORMANCE_OPTIMIZATION.md)
-   [API Documentation](API_DOCUMENTATION.md)
-   [Database Schema](database/migrations/)

### Monitoring Tools

-   Laravel Telescope (development)
-   RedisInsight (cache monitoring)
-   MySQL Performance Schema (database)
-   System monitoring tools

### Best Practices

-   Regular performance testing
-   Proactive monitoring
-   Automated alerting
-   Continuous optimization
-   Capacity planning
-   Disaster recovery
