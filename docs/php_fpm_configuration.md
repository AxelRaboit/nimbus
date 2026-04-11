# PHP-FPM Configuration for Nimbus

Configuration guide for PHP-FPM to support file transfers up to **10 GB**.

## 📋 Overview

Nimbus uses the **TUS protocol** for chunked uploads, which bypasses standard PHP limits. However, certain parameters need to be increased to support large transfers.

## 🔧 Parameters to Configure

### 1. Upload & Files

These parameters control the maximum upload size.

```ini
# /etc/php/X.X/fpm/php.ini

upload_max_filesize = 10G           # Max file size for a single upload
post_max_size = 10G                 # Max POST data size
max_file_uploads = 100              # Max simultaneous uploads
```

**Explanations:**
- `upload_max_filesize`: Hard limit for a single upload
- `post_max_size`: Total POST data limit (must be ≥ upload_max_filesize)
- `max_file_uploads`: Allows multiple simultaneous uploads

### 2. Memory & Execution

Increases timeouts and memory for large uploads.

```ini
memory_limit = 512M                 # Or 1G if server is well-provisioned
max_execution_time = 3600           # 1 hour (in seconds)
max_input_time = 3600               # 1 hour (in seconds)
```

**Explanations:**
- `memory_limit`: Memory allocated per PHP process
- `max_execution_time`: Maximum script execution time
- `max_input_time`: Maximum time to receive POST data

### 3. PHP-FPM Timeouts

Configuration of the PHP-FPM pool for long-running requests.

```ini
# /etc/php/X.X/fpm/pool.d/www.conf

request_terminate_timeout = 3600    # Request timeout (seconds)
request_slowlog_timeout = 60        # Log requests taking > 60s
slowlog = /var/log/php-fpm.log.slow # Slow log file
```

**Explanations:**
- `request_terminate_timeout`: Kills worker if it takes too long
- `request_slowlog_timeout`: Logs slow requests for analysis

### 4. Connections & PHP-FPM Workers

Sizes the worker pool based on server capacity.

```ini
# /etc/php/X.X/fpm/pool.d/www.conf

pm = dynamic                        # Mode: static, dynamic, ondemand
pm.max_children = 50                # Maximum number of workers
pm.start_servers = 10               # Workers to spawn at start
pm.min_spare_servers = 5            # Minimum idle workers
pm.max_spare_servers = 20           # Maximum idle workers
pm.max_requests = 5000              # Recycle worker after N requests
pm.max_requests_grace_period = 30   # Graceful shutdown timeout (sec)
```

**Explanations:**
- `pm.max_children`: Adjust based on CPU cores (cores × 2-4)
- `pm.max_requests`: Prevents memory leaks
- `dynamic` mode: Better for variable workloads
- `static` mode: Better for predictable performance

### 5. Buffers & Sockets

Configures buffers for large transfers.

```ini
# /etc/php/X.X/fpm/php.ini

default_socket_timeout = 3600       # Socket timeout (seconds)
realpath_cache_size = 4M            # Cache for real paths
realpath_cache_ttl = 600            # Cache TTL (seconds)
```

### 6. Global Variables

Useful if upload forms have many fields.

```ini
max_input_vars = 5000               # Max POST/GET variables
```

### 7. Logging (Optional)

Configuration for debugging issues.

```ini
# /etc/php/X.X/fpm/php.ini

error_reporting = E_ALL & ~E_DEPRECATED  # All errors except deprecation warnings
display_errors = Off                      # Disable in production
log_errors = On
error_log = /var/log/php-fpm.log

# /etc/php/X.X/fpm/pool.d/www.conf
access.log = /var/log/php-fpm-access.log
access.format = "%R - %u %t \"%m %r\" %s"
```

**Explanations:**
- `E_ALL & ~E_DEPRECATED`: Reports all errors except deprecation notices (useful for production with third-party dependencies)
- `display_errors = Off`: Never display errors to users
- `log_errors = On`: Always log errors to file

## ⚡ Minimal Production Configuration

If you must choose essential parameters only:

```ini
# /etc/php/X.X/fpm/php.ini
upload_max_filesize = 10G
post_max_size = 10G
memory_limit = 512M
max_execution_time = 3600
max_input_time = 3600

# /etc/php/X.X/fpm/pool.d/www.conf
request_terminate_timeout = 3600
pm.max_children = 50
pm.max_requests = 5000
```

## 📍 File Locations

| File | Description |
|------|-------------|
| `/etc/php/X.X/fpm/php.ini` | Main PHP configuration |
| `/etc/php/X.X/fpm/pool.d/www.conf` | PHP-FPM pool configuration |
| `/var/log/php-fpm.log` | PHP-FPM error logs |
| `/var/log/php-fpm-access.log` | Access logs (optional) |

*Replace `X.X` with your PHP version (e.g., `8.3`, `8.2`)*

## 🔍 Verify Current Configuration

```bash
# Check active parameters
php -i | grep upload_max_filesize
php -i | grep post_max_size
php -i | grep memory_limit
php -i | grep max_execution_time

# Check PHP version
php -v

# Find php.ini location
php -i | grep "Loaded Configuration File"

# Check PHP-FPM version
php-fpm -v
```

## ♻️ Apply Configuration Changes

After modifying configuration files:

```bash
# Validate syntax
php -l /etc/php/X.X/fpm/php.ini

# Restart PHP-FPM
sudo systemctl restart php-fpm
# Or for a specific version:
sudo systemctl restart phpX.X-fpm

# Check status
sudo systemctl status php-fpm
```

## 📊 Sizing Recommendations

### For a 2-core / 4GB RAM server
```ini
pm.max_children = 6
memory_limit = 256M
```

### For a 4-core / 8GB RAM server
```ini
pm.max_children = 10
memory_limit = 512M
```

### For an 8-core / 16GB RAM server
```ini
pm.max_children = 20
memory_limit = 1G
```

## ⚠️ Important Points

1. **TUS Uploads**: Nimbus uses TUS which bypasses PHP limits through chunking. Even with `upload_max_filesize=2M`, you can upload 10GB in chunks.

2. **Timeouts**: The 3600 seconds (1h) allows slow uploads. Adjust based on your network bandwidth.

3. **Memory**: Per-worker memory must not exceed: `memory_limit × pm.max_children < available RAM`

4. **Monitoring**: Monitor slow logs and adjust `pm.max_children` based on load.

## 🐛 Troubleshooting

### "PHP Fatal error: Maximum execution time exceeded"
→ Increase `max_execution_time` and `request_terminate_timeout`

### "POST Content-Length exceeds the limit"
→ Verify `post_max_size ≥ upload_max_filesize`

### "413 Payload Too Large"
→ Check Nginx/Apache configuration in addition to PHP

### PHP-FPM workers crashing
→ Check logs: `tail -f /var/log/php-fpm.log`
→ Increase `request_terminate_timeout`

## 📚 Resources

- [PHP Configuration Documentation](https://www.php.net/manual/en/ini.php)
- [PHP-FPM Configuration](https://www.php.net/manual/en/install.fpm.configuration.php)
- [TUS Protocol](https://tus.io/)
