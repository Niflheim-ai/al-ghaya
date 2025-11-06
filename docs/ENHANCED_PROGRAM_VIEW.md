# Enhanced Student Program View

## Overview

The enhanced student program view provides a completely redesigned interface for enrolled students, featuring a sidebar navigation system, detailed progress tracking, and security features to ensure proper content completion.

## Key Features

### 1. **Dual Interface Design**
- **Non-enrolled students**: Maintain the original design with program overview, enrollment button, and locked content preview
- **Enrolled students**: New sidebar-based interface with navigation and content display

### 2. **Sidebar Navigation**
- **Collapsible sidebar** with toggle functionality
- **Progress bar** showing overall program completion
- **Chapter hierarchy** with expandable/collapsible stories
- **Lock/unlock indicators** based on completion requirements
- **Current content highlighting**
- **Mobile-responsive** with overlay functionality

### 3. **Content Security Features**
- **Video completion tracking** using YouTube API
- **Interactive section validation** before allowing progress
- **Sequential unlocking** of chapters and stories
- **Progress persistence** across sessions

### 4. **Enhanced Content Display**
- **Full-width content area** when sidebar is collapsed
- **YouTube video integration** with completion detection
- **Interactive sections** with immediate feedback
- **Next content button** (locked until requirements are met)

## File Structure

```
┌── pages/student/
│   ├── student-program-view.php           # Main enhanced view
│   └── student-program-view-original.php   # Original design for non-enrolled
├── php/
│   └── functions.php                       # Enhanced with new functions
├── sql/
│   └── enhanced_program_view_migration.sql # Database migration
└── docs/
    └── ENHANCED_PROGRAM_VIEW.md            # This documentation
```

## Database Changes

### New Tables

1. **`chapter_stories`** - Stories within chapters
2. **`student_chapter_progress`** - Chapter completion tracking
3. **`student_story_progress`** - Story completion tracking
4. **`interactive_attempts`** - Interactive section attempts
5. **`video_watch_sessions`** - Video watching sessions

### New Views

1. **`v_chapter_progress`** - Easy chapter progress checking
2. **`v_story_progress`** - Easy story progress checking

### Database Triggers

- **`update_program_completion_after_chapter`** - Automatically updates program completion percentage when chapters are completed

## PHP Functions Added

### Core Functions
- `fetchChaptersWithStories($conn, $programID)` - Get chapters with their stories and progress
- `getTeacherInfo($conn, $programID)` - Get teacher information
- `getStudentProgress($conn, $studentID, $programID)` - Get overall student progress

### Content Functions
- `getStoryContent($conn, $storyID, $studentID)` - Get story content with progress
- `getChapterContent($conn, $chapterID, $studentID)` - Get chapter content with progress
- `getFirstAvailableContent($conn, $programID, $studentID)` - Get first unlocked content

### Progress Tracking Functions
- `markVideoWatched($conn, $studentID, $contentID, $contentType)` - Mark video as watched
- `submitInteractiveAnswers($conn, $studentID, $contentID, $answers)` - Handle interactive submissions

## Frontend Features

### Responsive Design
- **Desktop**: Fixed sidebar with resizable content area
- **Mobile**: Overlay sidebar with backdrop
- **Tablet**: Adaptive layout based on screen size

### JavaScript Functionality
- **Sidebar toggle** with smooth animations
- **Chapter/story expansion** with arrow indicators
- **YouTube API integration** for video completion tracking
- **AJAX submissions** for progress updates
- **Dynamic content loading** via URL parameters

### CSS Features
- **Smooth transitions** for all interactive elements
- **Hover effects** on clickable items
- **Progress indicators** with animated bars
- **Lock/unlock visual states**
- **Current content highlighting**

## Security Implementation

### Video Completion Requirements
- Integration with YouTube IFrame Player API
- Tracks video end events
- Prevents progression until video is watched
- Visual overlay for locked content

### Interactive Section Validation
- Server-side answer validation
- Attempt tracking and limiting
- Immediate feedback on submissions
- Progress blocking until correct answers

### Sequential Content Unlocking
- Database-driven unlock logic
- Prerequisites checking
- Visual lock indicators
- Disabled navigation for locked content

## Installation Instructions

### 1. Database Migration
```sql
-- Run the migration script
source sql/enhanced_program_view_migration.sql;
```

### 2. File Updates
The following files have been updated:
- `pages/student/student-program-view.php` - Completely rewritten
- `php/functions.php` - Enhanced with new functions

### 3. New Files Added
- `pages/student/student-program-view-original.php` - Original design preserved
- `sql/enhanced_program_view_migration.sql` - Database migration

## Configuration Options

New system settings added:
- `video_completion_threshold` - Percentage of video required (default: 80%)
- `max_interactive_attempts` - Maximum attempts per question (default: 3)
- `auto_unlock_next_content` - Auto-unlock next content (default: enabled)
- `require_video_completion` - Require video before progress (default: enabled)
- `require_interactive_completion` - Require interactive completion (default: enabled)

## Usage Examples

### Accessing Enhanced View
```php
// For enrolled students
$isEnrolled = !empty($program['is_enrolled']);
if ($isEnrolled) {
    // Show enhanced sidebar view
    // with chapters, stories, and progress tracking
} else {
    // Show original view with enrollment option
    include 'student-program-view-original.php';
}
```

### Loading Specific Content
```javascript
// Load chapter content
loadContent('chapter', chapterID);

// Load story content
loadContent('story', storyID);

// URLs will be: 
// ?program_id=1&chapter_id=5
// ?program_id=1&story_id=12
```

### Progress Tracking
```javascript
// Mark video as watched (automatic via YouTube API)
function onPlayerStateChange(event) {
    if (event.data == YT.PlayerState.ENDED) {
        markVideoWatched();
    }
}

// Submit interactive answers
function submitInteractive(event) {
    // AJAX submission with validation
    // Updates progress and unlocks next content
}
```

## Browser Compatibility

- **Chrome 80+** ✅
- **Firefox 75+** ✅
- **Safari 13+** ✅
- **Edge 80+** ✅
- **Mobile browsers** ✅ (iOS Safari, Chrome Mobile)

## Performance Considerations

- **Database indexing** for fast progress queries
- **Lazy loading** of content to reduce initial load time
- **Cached progress data** to minimize database calls
- **Optimized JavaScript** with event delegation
- **CSS animations** using hardware acceleration

## Future Enhancements

### Planned Features
1. **Offline support** for downloaded content
2. **Bookmark system** for favorite sections
3. **Note-taking functionality** within content
4. **Discussion forums** per chapter/story
5. **Peer progress comparison**
6. **Advanced analytics dashboard**

### Technical Improvements
1. **WebSocket integration** for real-time progress updates
2. **Service worker implementation** for offline functionality
3. **Advanced video analytics** (pause points, rewatch sections)
4. **Machine learning** for personalized content recommendations

## Troubleshooting

### Common Issues

**Sidebar not showing for enrolled students:**
- Check `$isEnrolled` variable is true
- Verify database enrollment record exists
- Ensure JavaScript is enabled

**Videos not tracking completion:**
- Verify YouTube API script is loaded
- Check for CORS issues with video embedding
- Ensure `markVideoWatched()` function is called

**Progress not updating:**
- Check database triggers are created
- Verify AJAX requests are successful
- Ensure proper error handling in PHP functions

### Debug Mode
Enable debug logging by adding:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Contributing

When contributing to the enhanced program view:

1. **Test both interfaces** (enrolled and non-enrolled)
2. **Verify mobile responsiveness**
3. **Check JavaScript console** for errors
4. **Test progress tracking** functionality
5. **Validate database queries** for performance

## License

This enhancement is part of the Al-Ghaya LMS project and follows the same licensing terms.

---

**Last Updated:** November 6, 2025  
**Version:** 1.0.0  
**Compatibility:** Al-Ghaya LMS v1.0+