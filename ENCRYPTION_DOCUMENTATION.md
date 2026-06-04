# ZIP Encryption Documentation for AI Team

## Overview

Crawler result files are now encrypted with **AES-256** password protection before being sent to the AI system. This document explains the encryption process and how to decrypt the files on the AI side.

---

## Encryption Specifications

### Algorithm Details
- **Encryption Method:** AES-256 (Advanced Encryption Standard, 256-bit)
- **Implementation:** PHP `ZipArchive` with `ZipArchive::EM_AES_256`
- **Format:** Standard ZIP encryption compatible with most modern tools
- **Password:** Shared secret from configuration (see below)

### File Workflow

```
1. Download → /tmp/{filename}.zip (original)
2. Unzip → /tmp/{foldername}/ (extracted images)
3. Re-zip with encryption → /mnt/task/{filename}.zip (AES-256 encrypted)
4. Send to AI → filename only: "{filename}.zip"
5. Cleanup → /tmp/ files deleted after 30 days
```

---

## Password Configuration

### Laravel Side (Surveillance System)

**Environment Variable (.env):**
```env
ZIP_ENCRYPTION_PASSWORD=your_secure_password_here
```

**Config (config/app.php):**
```php
'zip_encryption_password' => env('ZIP_ENCRYPTION_PASSWORD', 'default_secure_password_2024'),
```

### AI Side

The AI system needs the **same password** to decrypt the files. Store it securely:

**Option 1: Environment Variable**
```bash
export ZIP_ENCRYPTION_PASSWORD="your_secure_password_here"
```

**Option 2: Configuration File**
```python
# config.py
ZIP_ENCRYPTION_PASSWORD = "your_secure_password_here"
```

**Option 3: Secrets Management**
- HashiCorp Vault
- AWS Secrets Manager
- Google Secret Manager
- Azure Key Vault

---

## AI System Integration

### What AI Receives

**API Payload:**
```json
{
  "task_id": "uuid-here",
  "zip_file": "2026_06_04_101234_example.zip",
  "image_type": "element"
}
```

**File Location:**
- AI receives: `"2026_06_04_101234_example.zip"` (filename only)
- Full path: `/mnt/task/2026_06_04_101234_example.zip`
- File is **encrypted** with AES-256

---

## Decryption Methods

### Method 1: Python (Recommended)

**Install pyzipper:**
```bash
pip install pyzipper
```

**Decrypt and Extract:**
```python
import pyzipper
import os

def decrypt_and_extract(zip_filename, extract_to='/tmp/extracted'):
    """
    Decrypt and extract an AES-256 encrypted zip file
    
    Args:
        zip_filename: Name of the zip file (e.g., "2026_06_04_101234_example.zip")
        extract_to: Directory to extract files to
    """
    # Get password from environment
    password = os.getenv('ZIP_ENCRYPTION_PASSWORD', 'default_secure_password_2024')
    
    # Construct full path
    zip_path = f'/mnt/task/{zip_filename}'
    
    # Decrypt and extract
    with pyzipper.AESZipFile(zip_path) as zf:
        zf.pwd = password.encode('utf-8')
        zf.extractall(extract_to)
    
    print(f"Extracted to: {extract_to}")
    return extract_to

# Usage
extract_path = decrypt_and_extract("2026_06_04_101234_example.zip")
```

### Method 2: Command Line (7zip)

**Install 7zip:**
```bash
# Ubuntu/Debian
sudo apt-get install p7zip-full

# CentOS/RHEL
sudo yum install p7zip
```

**Decrypt and Extract:**
```bash
# Extract with password
7z x /mnt/task/2026_06_04_101234_example.zip -p"your_secure_password_here" -o/tmp/extracted

# Or using environment variable
7z x /mnt/task/2026_06_04_101234_example.zip -p"${ZIP_ENCRYPTION_PASSWORD}" -o/tmp/extracted
```

### Method 3: Node.js

**Install adm-zip-encryption:**
```bash
npm install adm-zip-encryption
```

**Decrypt and Extract:**
```javascript
const AdmZip = require('adm-zip-encryption');

function decryptAndExtract(zipFilename, extractTo = '/tmp/extracted') {
    const password = process.env.ZIP_ENCRYPTION_PASSWORD || 'default_secure_password_2024';
    const zipPath = `/mnt/task/${zipFilename}`;
    
    const zip = new AdmZip(zipPath);
    zip.extractAllTo(extractTo, true, password);
    
    console.log(`Extracted to: ${extractTo}`);
    return extractTo;
}

// Usage
decryptAndExtract('2026_06_04_101234_example.zip');
```

---

## Complete AI Processing Example (Python)

```python
import pyzipper
import os
from pathlib import Path

class CrawlerResultProcessor:
    def __init__(self):
        self.password = os.getenv('ZIP_ENCRYPTION_PASSWORD', 'default_secure_password_2024')
        self.base_path = '/mnt/task'
        self.extract_base = '/tmp/ai_processing'
    
    def process_task(self, task_data):
        """
        Process an AI task with encrypted zip file
        
        Args:
            task_data: Dict with 'task_id', 'zip_file', 'image_type'
        """
        task_id = task_data['task_id']
        zip_filename = task_data['zip_file']
        image_type = task_data['image_type']
        
        print(f"Processing task: {task_id}")
        print(f"Zip file: {zip_filename}")
        
        # Step 1: Decrypt and extract
        extract_path = self.decrypt_zip(zip_filename, task_id)
        
        # Step 2: Process images
        results = self.process_images(extract_path, image_type)
        
        # Step 3: Cleanup (optional - can keep for caching)
        # self.cleanup(extract_path)
        
        return results
    
    def decrypt_zip(self, zip_filename, task_id):
        """Decrypt and extract zip file"""
        zip_path = Path(self.base_path) / zip_filename
        extract_path = Path(self.extract_base) / task_id
        
        # Create extraction directory
        extract_path.mkdir(parents=True, exist_ok=True)
        
        # Decrypt and extract
        with pyzipper.AESZipFile(str(zip_path)) as zf:
            zf.pwd = self.password.encode('utf-8')
            zf.extractall(str(extract_path))
        
        print(f"Extracted to: {extract_path}")
        return extract_path
    
    def process_images(self, extract_path, image_type):
        """Process extracted images"""
        results = []
        
        # Find all image files
        for img_path in Path(extract_path).rglob('*.jpg'):
            # Your AI processing logic here
            result = self.analyze_image(img_path, image_type)
            results.append(result)
        
        return results
    
    def analyze_image(self, img_path, image_type):
        """Analyze a single image (implement your AI logic)"""
        # Placeholder for AI analysis
        return {
            'image_path': str(img_path),
            'image_type': image_type,
            'predictions': []
        }
    
    def cleanup(self, extract_path):
        """Clean up extracted files"""
        import shutil
        if Path(extract_path).exists():
            shutil.rmtree(extract_path)
            print(f"Cleaned up: {extract_path}")

# Usage
processor = CrawlerResultProcessor()

# Receive task from Laravel
task_data = {
    'task_id': 'uuid-here',
    'zip_file': '2026_06_04_101234_example.zip',
    'image_type': 'element'
}

results = processor.process_task(task_data)
```

---

## Important Notes

### Security
1. **Password must match exactly** - Case-sensitive
2. **Keep password secure** - Use environment variables or secrets management
3. **Never commit password** to version control
4. **Rotate password periodically** - Update both Laravel and AI systems

### Compatibility
1. **AES-256 support required** - Not all unzip tools support this
2. **Python's pyzipper** is recommended for best compatibility
3. **Standard ZIP format** - Compatible with most modern tools
4. **Test decryption** before deploying to production

### File Locations
1. **Encrypted zips:** `/mnt/task/{filename}.zip`
2. **AI receives:** Filename only (not full path)
3. **AI constructs path:** `/mnt/task/` + filename
4. **Extract to:** Any temporary location (e.g., `/tmp/ai_processing/`)

---

## Troubleshooting

### Error: "Wrong password"
- Verify password matches exactly between Laravel and AI
- Check for trailing spaces or special characters
- Ensure password is properly encoded (UTF-8)

### Error: "Unsupported encryption method"
- Install pyzipper (Python) or 7zip (CLI)
- Standard unzip may not support AES-256
- Use recommended tools listed above

### Error: "File not found"
- Verify file path: `/mnt/task/{filename}.zip`
- Check file permissions
- Ensure file was successfully created by Laravel

### Error: "Corrupted zip file"
- Check if encryption completed successfully
- Verify file size is not zero
- Check Laravel logs for encryption errors

---

## Testing

### Test Encryption (Laravel Side)
```bash
# Check if encrypted zip was created
ls -lh /mnt/task/

# Try to unzip without password (should fail)
unzip /mnt/task/2026_06_04_101234_example.zip
# Expected: "need PK compat. v5.1 (can do v4.6)"
```

### Test Decryption (AI Side)
```python
# Test decryption
import pyzipper

zip_path = '/mnt/task/2026_06_04_101234_example.zip'
password = b'your_secure_password_here'

with pyzipper.AESZipFile(zip_path) as zf:
    zf.pwd = password
    file_list = zf.namelist()
    print(f"Files in zip: {file_list}")
    
    # Extract first file as test
    if file_list:
        content = zf.read(file_list[0])
        print(f"Successfully decrypted: {file_list[0]}")
```

---

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check encryption logs for errors
3. Verify password configuration on both sides
4. Test with sample encrypted zip file

---

## Version History

- **v1.0** (2026-06-04): Initial implementation with AES-256 encryption
