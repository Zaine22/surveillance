# Crawler Redis API Migration Guide

## Overview

This guide explains the new Crawler Redis API Gateway and how to migrate from direct Redis connections to HTTPS API calls.

## What Changed?

### Before (Direct Redis Connection)
- Surveillance app connected directly to Redis server at `34.80.34.114:6379`
- Used `Redis::connection('crawler')` for all operations
- Required Redis port (6379) to be exposed

### After (API Gateway)
- New Laravel API Gateway service on `https://34.80.34.114` (port 443)
- Surveillance app can use either:
  - **Original method**: Direct Redis connection (existing code unchanged)
  - **New method**: HTTPS API calls (new functions added)

## Architecture

```
┌─────────────────────┐
│ Surveillance App    │
│                     │
│ Option 1:           │
│ dispatch()          │──────┐
│ consume()           │      │ Direct Redis
│                     │      │ (Original)
└─────────────────────┘      │
                             ▼
┌─────────────────────┐  ┌──────────────┐
│ Surveillance App    │  │ Redis Server │
│                     │  │ 192.168.0.9  │
│ Option 2:           │  └──────────────┘
│ dispatchViaApi()    │      ▲
│ consumeViaApi()     │      │
│                     │      │
└─────────────────────┘      │
         │                   │
         │ HTTPS (443)       │
         ▼                   │
┌─────────────────────┐      │
│ API Gateway         │      │
│ 34.80.34.114:443    │──────┘
│ (Laravel 12)        │
└─────────────────────┘
```

## New Files Created

### Surveillance App (`/Users/zaine/Documents/Code/OSBAY/survillance`)

1. **app/Services/CrawlerApiClient.php** - HTTP client for API calls
2. **app/Exceptions/CrawlerApiException.php** - Custom exception
3. **Updated config/services.php** - Added `crawler_api` configuration
4. **Updated .env.example** - Added API configuration variables

### API Gateway (`/Users/zaine/Documents/Code/OSBAY/crawler-redis-api`)

Complete Laravel 12 project with:
- API endpoints for dispatch, results, acknowledge, health
- API key authentication
- Redis stream management
- Full documentation (README.md, DEPLOYMENT.md)

## How to Use

### Option 1: Keep Using Original Code (No Changes Required)

Your existing code continues to work:

```php
// Dispatch tasks (original method)
$crawlerDispatchService->dispatch($item);
$crawlerDispatchService->dispatchPauseItems($item);

// Consume results (original method)
$crawlerResultConsumeService->consume();
```

### Option 2: Use New API Methods

New methods available for API-based operations:

```php
// Dispatch tasks via API
$crawlerDispatchService->dispatchViaApi($item);
$crawlerDispatchService->dispatchPauseItemsViaApi($item);

// Consume results via API
$crawlerResultConsumeService->consumeViaApi();
```

## Configuration

### Surveillance App (.env)

```env
# Original Redis connection (keep for existing code)
REDIS_CRAWLER_HOST=34.80.34.114
REDIS_CRAWLER_PORT=6379
REDIS_CRAWLER_PASSWORD=null
REDIS_CRAWLER_DB=0

# New API Gateway configuration (for new API methods)
CRAWLER_API_URL=https://34.80.34.114/api/v1
CRAWLER_API_KEY=your-api-key-here
CRAWLER_API_TIMEOUT=30
```

## API Endpoints

### Base URL
```
https://34.80.34.114/api/v1
```

### Authentication
All endpoints require API key in header:
```
X-API-Key: your-api-key-here
```

### Available Endpoints

1. **POST /api/v1/crawler/dispatch** - Dispatch crawler task
2. **GET /api/v1/crawler/results** - Get crawler results
3. **POST /api/v1/crawler/acknowledge** - Acknowledge processed messages
4. **GET /health** - Health check (no auth required)

See `/Users/zaine/Documents/Code/OSBAY/crawler-redis-api/README.md` for detailed API documentation.

## Migration Steps

### For Surveillance App Team

**No immediate action required!** Your existing code works as-is.

When ready to migrate to API:

1. **Update .env file**:
   ```bash
   cp .env.example .env
   # Add CRAWLER_API_* variables
   ```

2. **Get API key** from deployment team

3. **Update code** to use new methods:
   ```php
   // Change from:
   $service->dispatch($item);
   
   // To:
   $service->dispatchViaApi($item);
   ```

### For Client's PHP Service

1. **Get API key** from deployment team
2. **Use API endpoints** directly:
   ```php
   $response = Http::withHeaders([
       'X-API-Key' => 'your-api-key',
   ])->post('https://34.80.34.114/api/v1/crawler/dispatch', [
       'task_item_id' => '123',
       'keywords' => ['keyword1'],
       'crawl_location' => 'https://example.com',
       'type' => 'patrol',
   ]);
   ```

## Deployment

### API Gateway Deployment

See `/Users/zaine/Documents/Code/OSBAY/crawler-redis-api/DEPLOYMENT.md` for complete deployment instructions.

Quick steps:
1. Upload project to server `34.80.34.114`
2. Install dependencies: `composer install --no-dev`
3. Configure `.env` with Redis connection and API keys
4. Set up Nginx with SSL certificate
5. Test endpoints

### Surveillance App Deployment

No changes required for existing functionality. To enable API features:

1. Add API configuration to `.env`
2. Deploy updated code
3. Test new API methods (optional)

## Benefits of API Gateway

✅ **Security**: HTTPS encryption, API key authentication  
✅ **Flexibility**: Easy to add rate limiting, logging, monitoring  
✅ **Client-friendly**: Standard REST API for client's PHP service  
✅ **Backward compatible**: Original code continues to work  
✅ **Future-proof**: Easy to add new features without changing Redis  

## Troubleshooting

### API Connection Issues

```bash
# Test API health
curl https://34.80.34.114/health

# Test with API key
curl -H "X-API-Key: your-key" https://34.80.34.114/api/v1/health
```

### Check Logs

```bash
# Surveillance app
tail -f storage/logs/laravel.log

# API Gateway
tail -f /var/www/crawler-redis-api/storage/logs/laravel.log
```

## Support

- **API Documentation**: `/Users/zaine/Documents/Code/OSBAY/crawler-redis-api/README.md`
- **Deployment Guide**: `/Users/zaine/Documents/Code/OSBAY/crawler-redis-api/DEPLOYMENT.md`
- **API Client Code**: `app/Services/CrawlerApiClient.php`

## Summary

- ✅ **Existing code unchanged** - continues to work with direct Redis
- ✅ **New API methods added** - `dispatchViaApi()`, `consumeViaApi()`
- ✅ **API Gateway created** - Laravel 12 service with HTTPS
- ✅ **Client can use API** - Standard REST endpoints on port 443
- ✅ **Fully documented** - README, deployment guide, this migration guide
