# File Sync with Cron + Polling - Deployment Instructions

## Overview

This solution eliminates timeout issues by using a cron job on FileServer to automatically sync files to CleanFileServer, while MainWeb polls and downloads when ready.

## Architecture

```
FileServer (34.81.79.232)
    ↓ (Cron every 5 min)
CleanFileServer (35.194.240.94 / 192.168.0.10)
    ↓ (Polling + Download)
MainWeb (220.130.187.241)
```

---

## Step 1: Setup FileServer Cron Job

### 1.1 Upload Cron Script

```bash
# From your Mac, upload the script to FileServer
scp fileserver_sync_cron.sh user3@34.81.79.232:/home/user3/

# SSH to FileServer
ssh user3@34.81.79.232

# Make script executable
chmod +x /home/user3/fileserver_sync_cron.sh
```

### 1.2 Add to Crontab

```bash
# On FileServer, edit crontab
crontab -e

# Add this line (runs every 5 minutes):
*/5 * * * * /home/user3/fileserver_sync_cron.sh >> /home/user3/sync_cron.log 2>&1
```

### 1.3 Verify Cron is Running

```bash
# Check crontab
crontab -l

# Wait 5 minutes, then check log
tail -f /home/user3/sync_cron.log
```

---

## Step 2: Deploy MainWeb Code

### 2.1 Push Code to Git

```bash
# From your Mac
cd /Users/zaine/Documents/Code/OSBAY/survillance

git add app/Services/CleanFileServerPollingService.php \
        app/Services/DataSyncOrchestratorService.php \
        app/Console/Commands/RetryAllFailedSyncsCommand.php \
        fileserver_sync_cron.sh \
        DEPLOYMENT_INSTRUCTIONS.md

git commit -m "Add cron-based file sync with polling"
git push
```

### 2.2 Deploy to Production

```bash
# SSH to production
ssh -i /Users/zaine/Desktop/ssh/user3key -p 9622 user3@220.130.187.241

# Pull latest code
cd /var/www/surveillance
git pull

# Restart Horizon
php artisan horizon:terminate
```

---

## Step 3: Update Job to Use New Method

### Option A: Update Existing Job

In `app/Jobs/SyncCrawlerFileJob.php`, change:

```php
// OLD:
$this->orchestrator->syncUnscannedFileToMainWeb($this->item);

// NEW:
$this->orchestrator->syncFromCleanFileServerWithPolling($this->item);
```

### Option B: Keep Both Methods

Keep `syncUnscannedFileToMainWeb()` for backward compatibility and use the new method for new tasks.

---

## Step 4: Test the Complete Flow

### 4.1 Test Cron Sync

```bash
# On FileServer, create a test file
ssh user3@34.81.79.232
echo "test" > /home/rsyncbot/unscann-files/test_file.txt

# Wait 5 minutes, then check CleanFileServer
ssh user3@35.194.240.94
ls -la /home/rsyncbot/clean-files/test_file.txt
# Should exist!
```

### 4.2 Test Polling

```bash
# On production, test the polling service
php artisan tinker

$service = app(\App\Services\CleanFileServerPollingService::class);
$exists = $service->fileExists('test_file.txt');
var_dump($exists); // Should be true

exit
```

### 4.3 Test Complete Flow

Create a crawler task and monitor the logs:

```bash
# On production
tail -f storage/logs/laravel.log | grep -i polling
```

---

## Configuration

### Add to `.env` (Production)

```env
# CleanFileServer Configuration
CLEAN_FILE_SERVER_HOST=35.194.240.94
CLEAN_FILE_SERVER_USER=user3
CLEAN_FILE_SERVER_SSH_KEY=/var/www/.ssh/id_rsa
```

### Add to `config/services.php`

```php
'clean_file_server' => [
    'host' => env('CLEAN_FILE_SERVER_HOST', '35.194.240.94'),
    'user' => env('CLEAN_FILE_SERVER_USER', 'user3'),
    'ssh_key' => env('CLEAN_FILE_SERVER_SSH_KEY', '/var/www/.ssh/id_rsa'),
],
```

---

## Monitoring

### Check Cron Logs

```bash
# On FileServer
tail -f /home/user3/sync_cron.log
```

### Check Sync Status

```bash
# On production
php artisan tinker

# Check recent sync records
\App\Models\DataSyncRecord::where('status', 'waiting')
    ->orWhere('status', 'transferring')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['id', 'status', 'source_path', 'created_at']);

exit
```

---

## Troubleshooting

### Cron Not Running

```bash
# Check cron service
sudo systemctl status cron

# Check cron logs
grep CRON /var/log/syslog
```

### Polling Timeout

- Default: 10 minutes (600 seconds)
- Increase if needed in `syncFromCleanFileServerWithPolling()`
- Check if cron is running on FileServer

### SSH Issues

```bash
# Test SSH from MainWeb to CleanFileServer
sudo -u www-data ssh -i /var/www/.ssh/id_rsa user3@35.194.240.94 "pwd"
```

---

## Retry Failed Syncs

```bash
# Retry all failed syncs from today
php artisan sync:retry-all --today

# Retry specific record
php artisan sync:retry {record_id}
```

---

## Benefits

✅ **No Timeout Issues** - Cron syncs in background  
✅ **Automatic Retry** - Cron runs every 5 minutes  
✅ **Fast Downloads** - MainWeb downloads from local network  
✅ **Simple & Reliable** - No complex SSH chains  
✅ **Scalable** - Can handle large files  

---

## Rollback

If issues occur, revert to old method:

```bash
# In SyncCrawlerFileJob.php
$this->orchestrator->syncUnscannedFileToMainWeb($this->item);
```

Stop the cron:

```bash
# On FileServer
crontab -e
# Comment out or remove the cron line
```
