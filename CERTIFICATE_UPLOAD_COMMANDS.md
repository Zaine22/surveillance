# Commands to Upload and Install Redis CA Certificate

## Step 1: Upload Certificate to Server
Run this command from your Downloads folder (where ca.crt is located):

```bash
scp -i /Users/zaine/Desktop/ssh/user3key -o IdentitiesOnly=yes -o ConnectTimeout=10 -P 9622 ca.crt user3@220.130.187.241:~/ca.crt
```

## Step 2: SSH into the Server
```bash
ssh -i /Users/zaine/Desktop/ssh/user3key -o IdentitiesOnly=yes -o ConnectTimeout=10 -p 9622 user3@220.130.187.241
```

## Step 3: Move Certificate to System Directory (on the server)
```bash
sudo mv ~/ca.crt /etc/ssl/certs/ca.crt
```

## Step 4: Set Proper Permissions (on the server)
```bash
sudo chmod 644 /etc/ssl/certs/ca.crt
sudo chown root:root /etc/ssl/certs/ca.crt
```

## Step 5: Verify Certificate Installation (on the server)
```bash
# Check if file exists and is readable
ls -la /etc/ssl/certs/ca.crt

# View certificate details
openssl x509 -in /etc/ssl/certs/ca.crt -text -noout
```

## Step 6: Exit Server and Test Connection (from your local machine)
```bash
# Exit the SSH session
exit

# Run the test script from your project directory
cd /Users/zaine/Documents/Code/OSBAY/surveillance
php test_redis_ai_ssl.php
```

---

## Quick Copy-Paste Commands

**From Downloads folder:**
```bash
scp -i /Users/zaine/Desktop/ssh/user3key -o IdentitiesOnly=yes -o ConnectTimeout=10 -P 9622 ca.crt user3@220.130.187.241:~/ca.crt
```

**Then SSH and run these commands on the server:**
```bash
ssh -i /Users/zaine/Desktop/ssh/user3key -o IdentitiesOnly=yes -o ConnectTimeout=10 -p 9622 user3@220.130.187.241

sudo mv ~/ca.crt /etc/ssl/certs/ca.crt
sudo chmod 644 /etc/ssl/certs/ca.crt
sudo chown root:root /etc/ssl/certs/ca.crt
ls -la /etc/ssl/certs/ca.crt
exit
```

**Finally, test from your local machine:**
```bash
cd /Users/zaine/Documents/Code/OSBAY/surveillance
php test_redis_ai_ssl.php
```
