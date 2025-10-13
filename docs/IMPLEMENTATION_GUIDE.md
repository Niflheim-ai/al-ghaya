# Enhanced Program System Implementation Guide

## 🚀 Quick Start Guide

This guide will help you implement the enhanced program creation system that works with your existing Al-Ghaya LMS database.

### Step 1: Database Migration

1. **Backup your existing database first!**
```sql
mysqldump -u your_username -p al_ghaya_lms > al_ghaya_backup.sql
```

2. **Run the migration script:**
```sql
source database/2025_10_program_system_migration.sql;
```

This migration:
- ✅ **Preserves all existing data**
- ✅ **Adds new columns to existing tables**
- ✅ **Creates new tables for enhanced features**
- ✅ **Sets up proper foreign key relationships**
- ✅ **Creates useful views and triggers**

### Step 2: File Updates

The following files have been updated to work with your existing system:

#### Core Files Updated:
- `php/create-program.php` - Enhanced to support new features
- `php/enhanced-program-functions.php` - New functions for enhanced features
- `php/program-handler.php` - AJAX handler for program operations
- `components/quick-access.php` - Enhanced toolbar with publish/update features

#### New Components (Optional):
- `pages/teacher/teacher-programs-enhanced.php` - Full enhanced interface
- `components/program-details-form.php` - Enhanced program creation form
- `components/chapter-content-form.php` - Chapter management interface
- `components/story-form.php` - Story creation with interactive sections

### Step 3: Directory Setup

Create the required directories:
```bash
mkdir -p uploads/thumbnails
chmod 755 uploads/thumbnails
```

## 🎯 Current Features (Working with Existing System)

### ✅ **Immediately Available Features:**

1. **Enhanced Program Creation**
   - New Program button creates programs instantly
   - Compatible with existing teacher-programs.php page
   - Supports thumbnail uploads
   - Difficulty levels: Student/Aspiring/Master
   - Philippine Peso pricing

2. **Publishing System**
   - Publish button shows draft programs
   - Bulk publish functionality
   - Programs change status to 'published'

3. **Program Library**
   - My Programs: Shows teacher's own programs
   - Program Library: Shows all published programs
   - Status indicators (Draft/Published)

### 🔧 **Enhanced Features (After Full Migration):**

1. **Chapter Management**
   - Dynamic chapter addition
   - Chapter ordering
   - Story and quiz tracking

2. **Interactive Stories**
   - Arabic/English synopses
   - YouTube video integration
   - Interactive sections (1-3 per story)
   - Multiple question types

3. **Quiz System**
   - One quiz per chapter (mandatory)
   - Maximum 30 questions
   - Multiple choice format

4. **Admin Publishing Workflow**
   - Pending review status
   - Admin approval/rejection
   - Review messages

## 📋 Implementation Steps

### Phase 1: Basic Enhancement (Immediate)

1. **Run the database migration**
2. **Test the New Program button**
3. **Test the Publish functionality**
4. **Verify existing programs still work**

### Phase 2: Full Enhancement (Optional)

1. **Enable the enhanced programs page**
2. **Implement story and quiz creation**
3. **Set up admin approval workflow**
4. **Add student progress tracking**

## 🛠️ Configuration

### Database Configuration

Your existing database connection in `php/dbConnection.php` will work without changes.

### File Permissions
```bash
# Make sure PHP can write to uploads directory
chown -R www-data:www-data uploads/
chmod -R 755 uploads/

# Make sure PHP files are executable
chmod 644 php/*.php
chmod 644 components/*.php
```

### PHP Requirements
- PHP 7.4 or higher (your current version should work)
- MySQL/MariaDB (your current database)
- GD extension for image handling (usually included)

## 🔍 Testing Your Implementation

### Test 1: New Program Creation
1. Go to teacher programs page
2. Click "New Program" button
3. Should create a program and redirect to editing page

### Test 2: Publishing
1. Create a program and save it as draft
2. Click "Publish" button in toolbar
3. Select the program and publish
4. Verify status changes to 'published'

### Test 3: Program Library
1. Publish a program
2. View it in the Program Library section
3. Verify teacher attribution shows correctly

## 🐛 Troubleshooting

### Common Issues:

**1. New Program button doesn't work**
- Check browser console for JavaScript errors
- Verify `php/create-program.php` is accessible
- Check server error logs

**2. Database errors**
- Verify migration ran successfully
- Check foreign key constraints
- Ensure proper character encoding (UTF-8)

**3. Upload directory errors**
- Check directory exists: `uploads/thumbnails/`
- Verify permissions: `chmod 755 uploads/thumbnails/`
- Check PHP upload settings in php.ini

**4. Existing programs not showing**
- Check teacher table relationships
- Verify session management works
- Check user role assignments

### Error Logging

Check these locations for errors:
- PHP error log: `/var/log/php_errors.log`
- Apache error log: `/var/log/apache2/error.log`
- Browser console for JavaScript errors

## 📊 Database Schema Changes

### Existing Tables Enhanced:
- `programs` - Added difficulty_label, overview_video_url, currency, rejection_reason, datePublished
- `program_chapters` - Added has_quiz, story_count, quiz_question_count

### New Tables Added:
- `program_stories` - Stories within chapters
- `story_interactive_sections` - Interactive content containers
- `interactive_questions` - Questions within interactive sections
- `question_options` - Answer options for questions
- `chapter_quizzes` - One quiz per chapter
- `quiz_questions` - Quiz questions (max 30)
- `quiz_question_options` - Quiz answer options
- `program_publish_requests` - Publishing workflow
- `student_program_enrollments` - Student enrollment tracking
- `student_story_progress` - Progress tracking
- `student_quiz_attempts` - Quiz attempt history

## 🎉 Success Indicators

You'll know the implementation is working when:
- ✅ New Program button creates programs instantly
- ✅ Programs show with proper status indicators
- ✅ Publish button lists draft programs
- ✅ Publishing changes program status
- ✅ Program Library shows published programs from all teachers
- ✅ Existing programs continue to work normally

## 🚀 Next Steps

1. **Test thoroughly** with your existing data
2. **Train teachers** on new features
3. **Monitor performance** with enhanced features
4. **Consider implementing** full enhanced features (stories, quizzes)
5. **Set up admin approval workflow** if desired

## 📞 Support

If you encounter issues:
1. Check the troubleshooting section
2. Review server error logs
3. Test with a single program first
4. Verify database migration completed successfully

## 📝 Migration Rollback

If you need to rollback:
```sql
-- Restore from backup
mysql -u your_username -p al_ghaya_lms < al_ghaya_backup.sql
```

**Note**: Only rollback if there are serious issues. The migration is designed to be backward compatible.