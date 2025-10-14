# 🎯 Centralized Program Handler - Al-Ghaya LMS

## Overview

The Al-Ghaya LMS now uses a **single centralized program handler** to manage all program, chapter, story, and interactive section operations. This eliminates function redeclaration errors and provides a clean, maintainable architecture.

---

## 📁 File Structure

### ✅ **NEW STRUCTURE:**
```
php/
├── program-handler.php     # 🎯 CENTRALIZED - All program functions here
├── program-helpers.php     # 🔗 WRAPPER - Just includes program-handler.php
├── dbConnection.php        # Database connection
└── functions.php           # General utility functions
```

### ❌ **OLD STRUCTURE (REMOVED):**
```
❌ program-handler.php + program-helpers.php (with duplicate functions)
```

---

## 🔧 **How It Works**

### **1. Single Source of Truth**
- **`php/program-handler.php`** contains ALL program-related functions
- No more duplicate functions between files
- No more PHP redeclaration errors
- Consistent database schema usage

### **2. Namespaced Functions**
New functions use clear prefixes to avoid conflicts:

```php
// PROGRAM FUNCTIONS
program_create($conn, $data)
program_update($conn, $program_id, $data)
program_getById($conn, $program_id, $teacher_id)
program_verifyOwnership($conn, $program_id, $teacher_id)

// CHAPTER FUNCTIONS  
chapter_add($conn, $program_id, $title, $content, $question)
chapter_update($conn, $chapter_id, $title, $content, $question)
chapter_delete($conn, $chapter_id)
chapter_getByProgram($conn, $program_id)

// STORY FUNCTIONS
story_create($conn, $data)
story_delete($conn, $story_id)
story_getById($conn, $story_id)

// INTERACTIVE SECTION FUNCTIONS
section_create($conn, $story_id)
section_delete($conn, $section_id)
section_getQuestions($conn, $section_id)
```

### **3. Legacy Compatibility**
Old function names still work for backward compatibility:

```php
// OLD NAMES (still supported)
getProgram($conn, $program_id, $teacher_id)        // → program_getById()
addChapter($conn, $program_id, $title)             // → chapter_add()
getChapters($conn, $program_id)                    // → chapter_getByProgram()
getChapterStories($conn, $chapter_id)              // → chapter_getStories()
// ... and many more
```

---

## 🚀 **Usage Examples**

### **Including the Handler**
```php
// In any PHP file that needs program functions
require_once 'php/program-handler.php';
// OR (for backward compatibility)
require_once 'php/program-helpers.php';
```

### **Creating a Program**
```php
$data = [
    'teacherID' => $teacher_id,
    'title' => 'My Program',
    'description' => 'Program description',
    'difficulty_label' => 'Student',
    'category' => 'beginner',
    'price' => 99.99,
    'status' => 'draft',
    'thumbnail' => 'thumbnail.jpg',
    'overview_video_url' => 'https://youtube.com/watch?v=xyz'
];

$program_id = program_create($conn, $data);
if ($program_id) {
    echo "Program created with ID: $program_id";
}
```

### **Adding a Chapter**
```php
$chapter_id = chapter_add($conn, $program_id, 'Chapter 1', 'Chapter content', 'Chapter question');
if ($chapter_id) {
    echo "Chapter created with ID: $chapter_id";
}
```

### **Creating a Story**
```php
$story_data = [
    'chapter_id' => $chapter_id,
    'title' => 'Story Title',
    'synopsis_arabic' => 'Arabic synopsis',
    'synopsis_english' => 'English synopsis',
    'video_url' => 'https://youtube.com/watch?v=abc'
];

$story_id = story_create($conn, $story_data);
if ($story_id) {
    echo "Story created with ID: $story_id";
}
```

### **AJAX Request Handling**
The handler automatically processes AJAX requests:

```javascript
// JavaScript AJAX call
fetch('php/program-handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'create_chapter',
        program_id: 123,
        title: 'New Chapter'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Chapter created:', data.chapter_id);
    }
});
```

---

## ✅ **Benefits of Centralized Approach**

### **1. No More Redeclaration Errors**
- ✅ Each function exists in only one place
- ✅ No conflicts between files
- ✅ Clean, error-free PHP execution

### **2. Consistent Database Schema**
- ✅ All functions use correct table names (`chapterstories`, `programchapters`, etc.)
- ✅ All functions use correct column names (`chapterid`, `storyid`, etc.)
- ✅ No more "Unknown column" errors

### **3. Easier Maintenance**
- ✅ One file to modify for all program functionality
- ✅ Consistent error handling across all functions
- ✅ Centralized validation and security checks

### **4. Better Organization**
- ✅ Functions grouped by purpose (program, chapter, story, section)
- ✅ Clear naming conventions with prefixes
- ✅ Easy to find and modify specific functionality

### **5. Backward Compatibility**
- ✅ Existing code continues to work without changes
- ✅ Legacy function names redirect to new implementations
- ✅ Smooth migration path for future updates

---

## 🔒 **Security & Validation**

### **Built-in Security Features:**
- ✅ **Teacher Authentication** - Validates user is logged in as teacher
- ✅ **Program Ownership** - Verifies teacher owns the program before operations
- ✅ **Input Validation** - Validates all form inputs
- ✅ **SQL Injection Prevention** - Uses prepared statements throughout
- ✅ **Story Count Limits** - Enforces 1-3 stories per chapter
- ✅ **Section Limits** - Enforces 1-3 interactive sections per story

### **Error Handling:**
- ✅ **Comprehensive Logging** - All errors logged with context
- ✅ **User-Friendly Messages** - Clear error messages for users
- ✅ **Graceful Failures** - Operations fail safely without breaking the system
- ✅ **Transaction Support** - Database operations use transactions where needed

---

## 📊 **Supported Operations**

### **Program Operations**
- ✅ Create Program
- ✅ Update Program
- ✅ Get Program by ID
- ✅ Get Programs by Teacher
- ✅ Verify Program Ownership
- ✅ Upload Program Thumbnails

### **Chapter Operations**
- ✅ Add Chapter to Program
- ✅ Update Chapter
- ✅ Delete Chapter (with cascading deletes)
- ✅ Get Chapters by Program
- ✅ Get Chapter by ID
- ✅ Get Chapter Stories
- ✅ Get Chapter Quiz

### **Story Operations**
- ✅ Create Story (with 1-3 limit per chapter)
- ✅ Delete Story (prevents deleting last story)
- ✅ Get Story by ID
- ✅ Get Story Interactive Sections
- ✅ Bilingual Support (Arabic + English)
- ✅ YouTube URL Validation

### **Interactive Section Operations**
- ✅ Create Interactive Section (1-3 per story)
- ✅ Delete Interactive Section
- ✅ Get Section Questions
- ✅ Support for Multiple Question Types

### **AJAX API Operations**
- ✅ JSON Response Handling
- ✅ Real-time Chapter Creation
- ✅ Real-time Story Management
- ✅ Interactive Section Management
- ✅ YouTube URL Validation API

---

## 🧪 **Testing the Implementation**

### **1. Test Program Creation**
```php
// Test creating a program
$data = [
    'teacherID' => 1,
    'title' => 'Test Program',
    'description' => 'This is a test program',
    'difficulty_label' => 'Student',
    'category' => 'beginner',
    'price' => 0,
    'status' => 'draft',
    'thumbnail' => 'default.jpg',
    'overview_video_url' => ''
];

$program_id = program_create($conn, $data);
echo $program_id ? "✅ Program created: $program_id" : "❌ Program creation failed";
```

### **2. Test Chapter Creation**
```php
// Test adding a chapter
$chapter_id = chapter_add($conn, $program_id, 'Test Chapter');
echo $chapter_id ? "✅ Chapter created: $chapter_id" : "❌ Chapter creation failed";
```

### **3. Test Story Creation**
```php
// Test creating a story
$story_data = [
    'chapter_id' => $chapter_id,
    'title' => 'Test Story',
    'synopsis_arabic' => 'قصة تجريبية',
    'synopsis_english' => 'A test story',
    'video_url' => 'https://youtube.com/watch?v=test'
];

$story_id = story_create($conn, $story_data);
echo $story_id ? "✅ Story created: $story_id" : "❌ Story creation failed";
```

---

## 🔧 **Migration Guide**

### **For Existing Code:**
1. **No Changes Required** - All existing function calls will continue to work
2. **Recommended Updates** - Gradually migrate to new function names for clarity
3. **Include Changes** - Change `require_once 'program-helpers.php'` to `require_once 'program-handler.php'`

### **For New Code:**
1. **Use New Function Names** - e.g., `program_create()` instead of `createProgram()`
2. **Follow Naming Convention** - Use prefixed function names for clarity
3. **Leverage Built-in Validation** - Use the centralized validation features

---

## 🎯 **Best Practices**

### **1. Function Usage**
```php
// ✅ RECOMMENDED (new style)
$program = program_getById($conn, $program_id, $teacher_id);
$chapters = chapter_getByProgram($conn, $program_id);
$stories = chapter_getStories($conn, $chapter_id);

// ✅ ACCEPTABLE (legacy style)
$program = getProgram($conn, $program_id, $teacher_id);
$chapters = getChapters($conn, $program_id);
$stories = getChapterStories($conn, $chapter_id);
```

### **2. Error Handling**
```php
// ✅ Always check return values
$program_id = program_create($conn, $data);
if (!$program_id) {
    error_log("Failed to create program");
    // Handle error appropriately
}
```

### **3. Ownership Verification**
```php
// ✅ Always verify ownership before operations
if (!program_verifyOwnership($conn, $program_id, $teacher_id)) {
    throw new Exception("Access denied");
}
```

---

## 🚨 **Important Notes**

1. **Single Include** - Only include `program-handler.php` OR `program-helpers.php`, not both
2. **Database Schema** - All functions use the correct database schema (no underscores in table/column names)
3. **Transaction Safety** - Delete operations use transactions for data integrity
4. **Validation Rules** - Story limits (1-3 per chapter) and section limits (1-3 per story) are enforced
5. **Legacy Support** - Old function names are maintained for backward compatibility

---

## 🎉 **Status: Production Ready**

The centralized program handler is now ready for production use with:
- ✅ **Zero redeclaration errors**
- ✅ **Correct database schema compatibility**
- ✅ **Comprehensive error handling**
- ✅ **Full backward compatibility**
- ✅ **Enhanced security features**
- ✅ **Complete AJAX API support**

Your Al-Ghaya LMS components should now work flawlessly with this centralized approach! 🚀