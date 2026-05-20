# Lexicon Keyword Edit Feature - Translation Support

## Overview
This document describes the implementation of translation keyword editing functionality in the lexicon keyword update feature.

## Changes Made

### 1. Request Validation (`UpdateLexiconKeywordRequest.php`)
Added support for translation fields in the update request:

**New Fields:**
- `translations` - Array containing translation data (optional)
- `translations.zh` - Array of Chinese keywords (optional)
- `translations.en` - Array of English keywords (optional)
- `translations.ja` - Array of Japanese keywords (optional)

**Validation Rules:**
```php
'translations' => 'sometimes|array',
'translations.zh' => 'sometimes|array',
'translations.zh.*' => 'required|string|max:255',
'translations.en' => 'sometimes|array',
'translations.en.*' => 'required|string|max:255',
'translations.ja' => 'sometimes|array',
'translations.ja.*' => 'required|string|max:255',
```

### 2. Service Layer (`LexiconKeywordService.php`)
Updated `updateLexiconKeyword()` method to handle translations:

**Key Features:**
- Wrapped update in database transaction for data integrity
- Processes translation updates after main keyword update
- Calls `upsertTranslation()` for each language provided
- Automatically filters duplicate keywords across translations
- Rolls back all changes if any error occurs

**Implementation:**
```php
// Handle translations if provided
if (isset($data['translations']) && is_array($data['translations'])) {
    foreach ($data['translations'] as $language => $keywords) {
        if (!empty($keywords) && is_array($keywords)) {
            $this->upsertTranslation(
                $lexiconKeyword->id,
                $language,
                $keywords
            );
        }
    }
}
```

### 3. Controller (`LexiconKeywordController.php`)
Updated `update()` method to return refreshed data with translations:

**Changes:**
- Removed debug `dd()` statement
- Added `refresh()` and `load('translations')` to get updated data
- Returns complete keyword with all translations

## API Usage

### Request Format
```json
PUT /api/lexicon-keywords/{id}
{
  "keywords": ["keyword1", "keyword2"],
  "status": "enabled",
  "crawl_hit_count": 0,
  "case_count": 0,
  "translations": {
    "zh": ["中文關鍵字1", "中文關鍵字2"],
    "en": ["english keyword1", "english keyword2"],
    "ja": ["日本語キーワード1", "日本語キーワード2"]
  }
}
```

### Response Format
```json
{
  "id": "uuid",
  "keywords": ["keyword1", "keyword2"],
  "translations": {
    "zh": ["中文關鍵字1", "中文關鍵字2"],
    "en": ["english keyword1", "english keyword2"],
    "ja": ["日本語キーワード1", "日本語キーワード2"]
  }
}
```

## Features

### 1. Automatic Duplicate Prevention
- Checks for duplicate keywords across all translations in the same lexicon
- Filters out duplicates before saving
- Case-insensitive comparison

### 2. Transaction Safety
- All updates wrapped in database transaction
- Automatic rollback on any error
- Ensures data consistency

### 3. Flexible Updates
- Can update main keywords only
- Can update translations only
- Can update both simultaneously
- Empty translation arrays are ignored

### 4. Language Support
Currently supports three languages:
- Chinese (zh)
- English (en)
- Japanese (ja)

## Database Structure

The `lexicon_keywords` table uses a parent-child relationship for translations:
- Parent records have `parent_id = NULL`
- Translation records have `parent_id` pointing to parent
- Each translation has a `language` field (zh, en, ja)
- Keywords are stored as JSON arrays

## Error Handling

### Validation Errors
- Returns 422 with validation error messages in Chinese
- Checks for duplicate keywords
- Validates array structure and data types

### Not Found Errors
- Returns 404 if keyword ID doesn't exist
- Returns 404 if parent keyword not found for translations

### Database Errors
- Automatic transaction rollback
- Exception thrown for debugging

## Testing Recommendations

1. **Update main keyword only**
   - Verify translations remain unchanged

2. **Update translations only**
   - Verify main keyword remains unchanged

3. **Update both simultaneously**
   - Verify all changes applied correctly

4. **Duplicate keyword handling**
   - Try adding duplicate keywords
   - Verify they are filtered out

5. **Transaction rollback**
   - Simulate database error
   - Verify no partial updates occur

6. **Empty translations**
   - Send empty arrays
   - Verify they are ignored

## Notes

- The `upsertTranslation()` method handles both creating new translations and updating existing ones
- Translations inherit the lexicon_id from their parent
- Translation records always have status 'enabled' and counts set to 0
- The system normalizes and deduplicates keywords before saving
