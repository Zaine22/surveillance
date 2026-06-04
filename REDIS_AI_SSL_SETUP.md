# Redis AI SSL/TLS Connection Setup

This document describes how to configure secure SSL/TLS connections to the AI Redis server.

## Overview

The surveillance application connects to an external Redis server for AI task management. This connection has been configured to use SSL/TLS encryption with certificate-based authentication for enhanced security.

## Configuration

### Environment Variables

The following environment variables control the Redis AI SSL/TLS connection:

```env
# AI Redis Configuration
REDIS_AI_SCHEME=tls                              # Use 'tls' for secure connection, 'tcp' for plain
REDIS_AI_HOST=220.130.187.241                    # Redis server hostname/IP
REDIS_AI_PORT=6379                               # Redis server port
REDIS_AI_PASSWORD=null                           # Redis password (if required)
REDIS_AI_DB=0                                    # Redis database number
REDIS_AI_SSL_CA_CERT=/etc/ssl/certs/ca.crt      # Path to CA certificate file
REDIS_AI_SSL_VERIFY_PEER=true                   # Verify server certificate
REDIS_AI_SSL_VERIFY_PEER_NAME=true              # Verify server hostname matches certificate
```

### Certificate Setup

1. **Obtain the CA Certificate**
   - Get the CA certificate file from your Redis server administrator
   - The certificate should be in PEM format

2. **Install the Certificate**
   ```bash
   # Copy certificate to the system SSL directory
   sudo cp ca.crt /etc/ssl/certs/ca.crt
   
   # Set proper permissions
   sudo chmod 644 /etc/ssl/certs/ca.crt
   sudo chown root:root /etc/ssl/certs/ca.crt
   ```

3. **Verify Certificate**
   ```bash
   # Check certificate details
   openssl x509 -in /etc/ssl/certs/ca.crt -text -noout
   
   # Verify certificate is readable
   cat /etc/ssl/certs/ca.crt
   ```

## Database Configuration

The Redis AI connection is configured in `config/database.php`:

```php
'ai' => [
    'scheme'   => env('REDIS_AI_SCHEME', 'tcp'),
    'host'     => env('REDIS_AI_HOST'),
    'password' => env('REDIS_AI_PASSWORD'),
    'port'     => env('REDIS_AI_PORT'),
    'database' => env('REDIS_AI_DB', 0),
    'ssl'      => [
        'cafile'           => env('REDIS_AI_SSL_CA_CERT'),
        'verify_peer'      => env('REDIS_AI_SSL_VERIFY_PEER', true),
        'verify_peer_name' => env('REDIS_AI_SSL_VERIFY_PEER_NAME', true),
    ],
],
```

## Services Using Redis AI Connection

The following services use the Redis AI connection:

1. **AiDispatchService** (`app/Services/AiDispatchService.php`)
   - Dispatches AI tasks to Redis
   - Uses `HSET` to store task data
   - Uses `XADD` to add tasks to the stream

2. **AiResultConsumeService** (`app/Services/AiResultConsumeService.php`)
   - Consumes AI task results from Redis
   - Uses `XREAD` to read from the result stream
   - Uses `HGETALL` to retrieve task data

## Testing the Connection

A test script is provided to verify the SSL/TLS connection:

```bash
php test_redis_ai_ssl.php
```

The test script will:
1. Verify the certificate file exists
2. Test basic Redis operations (PING, SET, GET)
3. Test stream operations (XADD, XREAD)
4. Test hash operations (HSET, HGETALL)
5. Clean up test data

### Expected Output

```
=== Redis AI SSL/TLS Connection Test ===

Configuration:
  Host: 220.130.187.241
  Port: 6379
  Scheme: tls
  SSL CA Cert: /etc/ssl/certs/ca.crt
  Verify Peer: true
  Verify Peer Name: true

✓ Certificate file found

Testing connection...
1. Testing PING command...
   Response: PONG
   ✓ PING successful

2. Testing SET command...
   ✓ SET successful (key: test:ssl:connection:1234567890)

3. Testing GET command...
   Retrieved value: SSL connection test successful
   ✓ GET successful - value matches

4. Testing XADD command (stream operation)...
   Stream ID: 1234567890-0
   ✓ XADD successful

5. Testing XREAD command...
   ✓ XREAD successful - retrieved 1 message(s)

6. Testing HSET/HGETALL commands...
   Retrieved hash data:
     status: testing
     timestamp: 2026-06-04 14:18:00
     ssl_enabled: true
   ✓ Hash operations successful

7. Cleaning up test keys...
   ✓ Cleanup successful

=== ✅ All tests passed! ===
The Redis AI connection is working correctly with SSL/TLS.
```

## Troubleshooting

### Certificate Not Found Error

```
❌ ERROR: Certificate file not found at: /etc/ssl/certs/ca.crt
```

**Solution:** Ensure the certificate file is placed at the correct location with proper permissions.

### Connection Refused

**Possible causes:**
1. Redis server is not running
2. Firewall blocking the connection
3. Incorrect host/port configuration

**Solution:** Verify network connectivity and firewall rules.

### SSL Handshake Failed

**Possible causes:**
1. Certificate is invalid or expired
2. Certificate doesn't match the server hostname
3. Redis server not configured for TLS

**Solution:** 
- Verify certificate validity: `openssl x509 -in /etc/ssl/certs/ca.crt -text -noout`
- Check Redis server TLS configuration
- Ensure hostname matches certificate CN/SAN

### Peer Verification Failed

**Possible causes:**
1. Self-signed certificate without proper CA chain
2. Hostname mismatch

**Solution:**
- If using self-signed certificates, you may need to disable peer name verification (not recommended for production):
  ```env
  REDIS_AI_SSL_VERIFY_PEER_NAME=false
  ```

## Security Considerations

1. **Certificate Storage**
   - Store certificates in a secure location with restricted permissions
   - Use system directories like `/etc/ssl/certs/` for production

2. **Peer Verification**
   - Always enable peer verification in production (`REDIS_AI_SSL_VERIFY_PEER=true`)
   - Enable peer name verification to prevent MITM attacks (`REDIS_AI_SSL_VERIFY_PEER_NAME=true`)

3. **Certificate Rotation**
   - Monitor certificate expiration dates
   - Plan for certificate rotation before expiration
   - Update the certificate file and restart the application

4. **Access Control**
   - Limit file system access to the certificate file
   - Use Redis password authentication in addition to TLS
   - Implement network-level access controls (firewall rules)

## Production Deployment

1. **Pre-deployment Checklist**
   - [ ] Certificate file installed at correct location
   - [ ] Certificate permissions set correctly (644)
   - [ ] Environment variables configured in `.env`
   - [ ] Test script passes successfully
   - [ ] Redis server configured for TLS
   - [ ] Firewall rules allow connection

2. **Deployment Steps**
   ```bash
   # 1. Copy certificate to production server
   scp ca.crt user@production-server:/tmp/
   
   # 2. Install certificate on production server
   ssh user@production-server
   sudo mv /tmp/ca.crt /etc/ssl/certs/ca.crt
   sudo chmod 644 /etc/ssl/certs/ca.crt
   sudo chown root:root /etc/ssl/certs/ca.crt
   
   # 3. Update .env file with SSL configuration
   # (Edit .env file with proper values)
   
   # 4. Clear configuration cache
   php artisan config:clear
   
   # 5. Test connection
   php test_redis_ai_ssl.php
   
   # 6. Restart application services
   sudo systemctl restart php-fpm
   sudo systemctl restart nginx
   ```

3. **Monitoring**
   - Monitor Redis connection errors in application logs
   - Set up alerts for certificate expiration
   - Monitor SSL/TLS handshake failures

## References

- [Redis TLS Documentation](https://redis.io/docs/manual/security/encryption/)
- [phpredis SSL/TLS Support](https://github.com/phpredis/phpredis#ssl-tls-support)
- [Laravel Redis Configuration](https://laravel.com/docs/redis#configuration)
