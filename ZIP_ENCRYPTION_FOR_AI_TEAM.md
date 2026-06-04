# ZIP Encryption & Decryption Guide for AI Team

## Overview

All crawler result files sent to the AI system are **encrypted with two-layer AES-256** password protection. This document explains the encryption process and how to decrypt the files.

---

## What You Receive

**API Payload:**
```json
{
  "dir_path": "2026_05_22_133650_520cc",
  "image_type": "element",
  "dynamic_key": "a1b2c3d4e5f6789abcdef..."
}
```

**File Location:**
- **Encrypted Zip:** `/mnt/task/{dir_path}.zip`
- **Example:** `/mnt/task/2026_05_22_133650_520cc.zip`
- **Dynamic Key:** Provided in task params for Layer 1 decryption

---

## Encryption Specifications

| Property | Value |
|----------|-------|
| **Encryption Type** | Two-Layer (Nested ZIP) |
| **Layer 1 (Inner)** | AES-256 with Dynamic Key |
| **Layer 2 (Outer)** | AES-256 with Static Key |
| **Algorithm** | AES-256 (Advanced Encryption Standard, 256-bit) |
| **Method** | `ZipArchive::EM_AES_256` |
| **Format** | Standard ZIP encryption |
| **Static Password** | `surveillance123@#` |
| **Dynamic Key** | SHA-256 hash (unique per file) |
| **Compatibility** | Compatible with most modern tools (pyzipper, 7zip) |

---

## How Laravel Encrypts Files (Two-Layer)

```
1. Download zip → /tmpzip/{dir_path}.zip
2. Unzip → /tmpzip/{dir_path}/
3. Layer 1: Encrypt with dynamic key → temp_inner.zip
4. Layer 2: Encrypt with static key → /mnt/task/{dir_path}.zip
5. Send task to AI with dir_path, image_type, and dynamic_key
```

**Dynamic Key Generation:**
```
dynamic_key = SHA256(filename + timestamp + salt)
Example: a1b2c3d4e5f6789abcdef0123456789...
```

---

## How to Decrypt Files (Two-Layer)

### **Step 1: Decrypt Outer Layer (Static Key)**
```
Password: surveillance123@#
File: /mnt/task/{dir_path}.zip
Result: /mnt/task/{dir_path}_encrypted.zip (inner layer)
```

### **Step 2: Decrypt Inner Layer (Dynamic Key)**
```
Password: {dynamic_key from AI task params}
File: /mnt/task/{dir_path}_encrypted.zip
Result: Extracted files in /mnt/task/{dir_path}_encrypted/
```

### **Example:**
```
Outer file: /mnt/task/2026_05_22_133650_520cc.zip
Static Key: surveillance123@#

After Step 1: /mnt/task/2026_05_22_133650_520cc_encrypted.zip
Dynamic Key: a1b2c3d4e5f6789... (from task params)

After Step 2: /mnt/task/2026_05_22_133650_520cc_encrypted/ (extracted folder)
```

---

## AI Response Format

After decrypting and processing files, AI returns data with **file paths** (not URLs):

**AI Response Example:**
```json
{
  "victim": [{
    "image": "task/2026_05_22_133855_520cc/spec_019e4e2b.../03_content_images/image_056.jpg",
    "victims": [{
      "user_name": "victim_2_6",
      "facial_area": {"x": 0, "y": 0, "w": 119, "h": 119},
      "similarity": 0.708439653676256
    }]
  }],
  "age": [{
    "underage_probability": 0.5,
    "message": "successful",
    "success": true,
    "path": "/home/victor/MOHW/task/2026_05_22_133855_520cc/spec_019e4e2b.../03_content_images/image_047.jpg",
    "facial_area": {"x": 30, "y": 22, "w": 28, "h": 28}
  }],
  "nsfw": []
}
```

**Important:**
- AI returns **file paths**, not URLs
- Laravel will construct URLs from these paths
- Paths use `task/` prefix (not `/mnt/task/`)

---

## File Structure

### **Encrypted Backup (Permanent):**
```
/mnt/task/
└── 2026_05_22_133650_520cc.zip (Two-layer AES-256 encrypted)
```

### **Extracted Files (Temporary - 30 days):**
```
/tmpzip/
└── 2026_05_22_133650_520cc/
    └── spec_019e4e2b-a8e7-70d7-bd7f-a5a25df7fc1b_667/
        └── 03_content_images/
            └── image_001.jpg
```

### **AI Returns Paths (Laravel Constructs URLs):**
```json
{
  "image": "task/2026_05_22_133650_520cc/spec_019e4e2b.../03_content_images/image_001.jpg"
}
```

Laravel converts to URL:
```
http://220.130.187.241:9680/tmpzip/2026_05_22_133650_520cc/spec_019e4e2b.../03_content_images/image_001.jpg
```

---

## Important Notes

### **Static Key (Layer 2):**
- **Value:** `surveillance123@#`
- **Case-sensitive:** Must match exactly
- **Encoding:** UTF-8
- **Purpose:** Outer layer encryption (known by AI team)

### **Dynamic Key (Layer 1):**
- **Source:** Provided in task params
- **Format:** SHA-256 hash (64 characters)
- **Purpose:** Inner layer encryption (unique per file)
- **Example:** `a1b2c3d4e5f6789abcdef0123456789...`

### **File Locations:**
- **Encrypted zips:** `/mnt/task/{dir_path}.zip`
- **Extracted files:** `/tmpzip/{dir_path}/`
- **URL path:** `/tmpzip/` (web access path)

### **Cleanup:**
- Files in `/tmpzip/` are deleted after 30 days
- Encrypted zips in `/mnt/task/` are kept permanently
- Laravel can auto-restore files from encrypted backup if needed

---

## Testing Two-Layer Decryption

### **Test Layer 2 (Outer - Static Key):**
```bash
# Decrypt outer layer to /mnt/task/
7z x /mnt/task/2026_05_22_133650_520cc.zip \
   -p"surveillance123@#" \
   -o/mnt/task/

# Check for inner {dir_path}_encrypted.zip
ls -la /mnt/task/2026_05_22_133650_520cc_encrypted.zip
```

### **Test Layer 1 (Inner - Dynamic Key):**
```bash
# Decrypt inner layer with dynamic key from task params
DYNAMIC_KEY="a1b2c3d4e5f6789..."  # From AI task params

7z x /mnt/task/2026_05_22_133650_520cc_encrypted.zip \
   -p"${DYNAMIC_KEY}" \
   -o/mnt/task/

# Check extracted files
ls -la /mnt/task/2026_05_22_133650_520cc_encrypted/
```

---

## Troubleshooting

### **Error: "Wrong password" (Layer 2)**
- Verify static password is exactly: `surveillance123@#`
- Check for trailing spaces
- Ensure UTF-8 encoding

### **Error: "Wrong password" (Layer 1)**
- Verify dynamic_key from task params is correct
- Check that key is complete (64 characters for SHA-256)
- Ensure no extra spaces or line breaks

### **Error: "Unsupported encryption method"**
- Use `pyzipper` (Python) or `7zip` (CLI)
- Standard `unzip` does not support AES-256

### **Error: "File not found"**
- Check path: `/mnt/task/{dir_path}.zip`
- Verify file permissions
- Ensure Laravel created the file successfully

### **Error: "Inner zip not found"**
- Ensure Layer 2 decryption completed successfully
- Check `/tmp/outer/encrypted.zip` exists
- Verify static password was correct

---

## Summary

### **What Laravel Does:**
1. Downloads and unzips files to `/tmpzip/`
2. Generates unique dynamic key per file (SHA-256 hash)
3. Creates two-layer encrypted backup in `/mnt/task/`:
   - **Layer 1 (Inner):** Dynamic key (unique per file)
   - **Layer 2 (Outer):** Static key (`surveillance123@#`)
4. Sends task to AI with `dir_path`, `image_type`, and `dynamic_key`
5. Cleans up `/tmpzip/` after 30 days

### **What AI Should Do:**
1. Receive `dir_path`, `image_type`, and `dynamic_key` from task params
2. **Decrypt Layer 2 (outer)** using static key: `surveillance123@#`
   - File: `/mnt/task/{dir_path}.zip`
   - Extract to: `/mnt/task/` → Creates `/mnt/task/{dir_path}_encrypted.zip`
3. **Decrypt Layer 1 (inner)** using `dynamic_key` from task params
   - File: `/mnt/task/{dir_path}_encrypted.zip`
   - Extract to: `/mnt/task/` → Creates `/mnt/task/{dir_path}_encrypted/`
4. Process images from `/mnt/task/{dir_path}_encrypted/`
5. Return results with **file paths** (not URLs)
   - Use format: `task/{dir_path}/{image_path}`
   - Laravel will construct URLs from these paths

### **Key Information:**
- **Static Key (Layer 2):** `surveillance123@#` (fixed, known by AI)
- **Dynamic Key (Layer 1):** Unique per file, sent in task params
- **Encryption:** Two-layer AES-256 (nested ZIP)
- **Encrypted files:** `/mnt/task/{dir_path}.zip`
- **Extract to:** `/mnt/task/` (AI only knows /mnt/task/)
- **URL format:** `http://220.130.187.241:9680/tmpzip/{dir_path}/...`

### **Security Benefits:**
- **Two layers of protection:** Must compromise both keys to access data
- **Dynamic keys:** Each file has unique inner encryption
- **Static key:** AI team knows it, doesn't change
- **Key rotation:** Can change static key without affecting old files (dynamic keys stored)

---

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify static password: `surveillance123@#`
3. Verify dynamic_key from task params
4. Test decryption with 7zip or pyzipper
5. Contact Laravel team if encryption fails
