# Enhanced Student Program View

## Overview

The student program view has been completely redesigned to provide an immersive learning experience with:
- **Sidebar navigation** showing all chapters, stories, and quizzes
- **Main content area** displaying story content with interactive quiz questions
- **Progressive unlocking** where students must answer quiz questions correctly to proceed
- **Progress tracking** with visual indicators and completion percentages

---

## Features

### 1. **Sidebar Navigation** (Left Panel)

#### Structure:
```
├─ Progress Bar
└─ Course Content
    ├─ Chapter 1
    │   ├─ Story 1
    │   ├─ Story 2
    │   └─ Chapter Quiz
    ├─ Chapter 2
    │   ├─ Story 1
    │   └─ Chapter Quiz
    └─ ...
```

#### Features:
- **Collapsible chapters** - Click to expand/collapse
- **Visual indicators**:
  - 📁 Folder icon for chapters
  - ▶️ Play icon for stories
  - 📝 Exam icon for quizzes
- **Active highlighting** - Current story is highlighted
- **Sticky positioning** - Sidebar stays visible while scrolling

---

### 2. **Main Content Area** (Right Panel)

#### Story Content Display:

**A. Story Title**
- Large, bold heading with chapter context

**B. Arabic Synopsis** (ملخص القصة)
- Displayed in a **blue gradient box**
- Right-to-left (RTL) text direction
- Border accent for visual emphasis

**C. English Synopsis**
- Displayed in a **green gradient box**
- Left-to-right text
- Matches Arabic synopsis styling

**D. Video Player**
- Full-width YouTube embed
- 16:9 aspect ratio (responsive)
- Auto-plays when loaded
- Supports all YouTube URL formats

**E. Interactive Quiz Question**
- **Single random question** pulled from the chapter's quiz
- Multiple-choice format with radio buttons
- **Submit Answer** button
- **Answer validation**:
  - ✅ **Correct**: Green success message + "Next Story" button appears
  - ❌ **Wrong**: Red error message + "Retry" button appears
- **Progression Lock**: Students CANNOT proceed until they answer correctly

---

## User Flow

### For Students:

1. **Navigate to enrolled program**
   - URL: `student-program-view.php?program_id=X`

2. **View sidebar** with all chapters and stories

3. **Click on a story** to load it in the main content area

4. **Read/Watch content**:
   - Arabic synopsis
   - English synopsis
   - Video

5. **Answer quiz question**:
   - Select an answer option
   - Click "Submit Answer"
   - **If correct**: ✅ Proceed to next story
   - **If wrong**: ❌ Retry button appears, "Next Story" stays hidden

6. **Retry if wrong**:
   - Click "Retry" to reset the form
   - Select a different answer
   - Submit again

7. **Proceed to next story** when correct answer is given

---

## Database Schema Used

### Tables:

**1. `chapter_stories`**
```sql
- story_id (PK)
- chapter_id (FK)
- title
- synopsis_arabic
- synopsis_english
- video_url
- story_order
```

**2. `chapter_quizzes`**
```sql
- quiz_id (PK)
- chapter_id (FK)
- program_id (FK) -- NEW: Added for foreign key
- title
```

**3. `quiz_questions`**
```sql
- quiz_question_id (PK)
- quiz_id (FK)
- question_text
- question_order
```

**4. `quiz_question_options`**
```sql
- quiz_option_id (PK)
- quiz_question_id (FK)
- option_text
- is_correct (BOOLEAN)
- option_order
```

**5. `student_story_progress`** (tracking)
```sql
- progress_id (PK)
- student_id (FK)
- story_id (FK)
- is_completed (BOOLEAN)
- completion_date
- last_accessed
```

**6. `student_quiz_attempts`** (logging)
```sql
- attempt_id (PK)
- student_id (FK)
- quiz_id (FK)
- score
- max_score
- is_passed (BOOLEAN)
- attempt_date
```

---

## File Structure

### PHP Files:

**Main View:**
- `pages/student/student-program-view.php` - Main program view with sidebar and content
- `pages/student/student-program-view-original.php` - Original view for unenrolled students

**Backend Handlers:**
- `php/quiz-handler.php` - Quiz CRUD operations (teacher side)
- `php/quiz-answer-handler.php` - **NEW**: Answer validation and progression logic (student side)
- `php/program-core.php` - Core program functions
- `php/functions.php` - General helper functions

**Helper Files:**
- `php/youtube-embed-helper.php` - YouTube URL conversion

---

## API Endpoints

### `quiz-answer-handler.php`

#### 1. Check Answer
```javascript
POST /php/quiz-answer-handler.php
Content-Type: application/json

{
  "action": "check_answer",
  "question_id": 123,
  "option_id": 456,
  "story_id": 789
}

// Response
{
  "success": true,
  "correct": true,
  "message": "Excellent! You answered correctly..."
}
```

#### 2. Mark Story Complete
```javascript
POST /php/quiz-answer-handler.php

{
  "action": "mark_story_complete",
  "story_id": 789
}
```

#### 3. Get Progress
```javascript
POST /php/quiz-answer-handler.php

{
  "action": "get_progress",
  "program_id": 1
}

// Response
{
  "success": true,
  "total_stories": 12,
  "completed_stories": 5,
  "completion_percentage": 41.7
}
```

---

## JavaScript Functions

### Core Functions:

**1. `toggleChapter(chapterId)`**
- Expands/collapses chapter in sidebar
- Rotates chevron icon
- Shows/hides stories and quizzes

**2. Quiz Form Submission**
```javascript
quizForm.addEventListener('submit', function(e) {
  e.preventDefault();
  // Validates answer
  // Shows feedback (correct/wrong)
  // Enables/disables progression
});
```

**3. `retryQuestion()`**
- Resets the quiz form
- Clears selected answers
- Hides feedback messages
- Shows submit button again

---

## Styling & UI

### Color Scheme:
- **Blue** (#3b82f6) - Primary actions, progress
- **Green** (#22c55e) - Correct answers, story icons
- **Orange** (#ea580c) - Quiz questions, warnings
- **Red** (#dc2626) - Wrong answers, errors

### Layout:
- **Responsive grid**: 12 columns on desktop
- **Sidebar**: 3 columns (25% width)
- **Main content**: 9 columns (75% width)
- **Mobile**: Stacked layout (sidebar on top)

### Interactive Elements:
- Hover effects on all clickable items
- Smooth transitions for animations
- Active state highlighting
- Disabled state styling for locked content

---

## Quiz Question Logic

### Selection Algorithm:
1. Load story content
2. Get chapter's quiz from `chapter_quizzes`
3. Fetch all questions from `quiz_questions`
4. **Randomly select ONE question** using `array_rand()`
5. Display with all options from `quiz_question_options`

### Answer Validation:
1. Student submits selected option
2. Backend checks `is_correct` flag in database
3. Returns JSON response:
   - `correct: true` → Show success, enable progression
   - `correct: false` → Show error, show retry button

### Progression Rules:
- **Cannot proceed** to next story without correct answer
- **Retry button** appears on wrong answer
- **Next Story button** only shows after correct answer
- All attempts are logged in `student_quiz_attempts`

---

## Progress Tracking

### Metrics Tracked:
1. **Story Completion**
   - Marked complete when quiz question answered correctly
   - Stored in `student_story_progress`

2. **Quiz Attempts**
   - All attempts logged in `student_quiz_attempts`
   - Includes: score, is_passed, attempt_date

3. **Program Completion Percentage**
   - Calculated as: (completed_stories / total_stories) * 100
   - Auto-updated on each story completion
   - Displayed in sidebar progress bar

---

## Security Features

### Access Control:
- ✅ Session validation (student role required)
- ✅ Enrollment verification (must be enrolled to view)
- ✅ Server-side answer validation (cannot cheat)

### Data Validation:
- All inputs sanitized and validated
- SQL injection prevention (prepared statements)
- XSS protection (htmlspecialchars)

---

## Future Enhancements

### Planned Features:
1. **Chapter Quiz Page**
   - Full quiz with all questions
   - Timer and score tracking
   - Must pass to unlock next chapter

2. **Video Tracking**
   - Track video watch progress
   - Require minimum watch time before quiz

3. **Gamification**
   - Points for correct answers
   - Badges for completing chapters
   - Leaderboard integration

4. **Analytics Dashboard**
   - Student performance metrics
   - Time spent per story
   - Quiz attempt statistics

5. **Adaptive Learning**
   - Difficulty adjustment based on performance
   - Personalized question selection
   - Remedial content recommendations

---

## Testing Checklist

### Basic Functionality:
- [ ] Sidebar loads all chapters, stories, and quizzes
- [ ] Clicking a story loads it in the main area
- [ ] Arabic synopsis displays correctly (RTL)
- [ ] English synopsis displays correctly (LTR)
- [ ] YouTube video embeds and plays
- [ ] Quiz question loads with options

### Quiz Interaction:
- [ ] Can select an answer option
- [ ] Submit button works
- [ ] **Correct answer**: Success message + Next Story button
- [ ] **Wrong answer**: Error message + Retry button
- [ ] Retry button resets form
- [ ] Cannot proceed without correct answer

### Navigation:
- [ ] Chapter collapsing/expanding works
- [ ] Active story is highlighted
- [ ] Next Story button goes to correct story
- [ ] Progress bar updates correctly

### Responsive Design:
- [ ] Works on desktop (1920px)
- [ ] Works on tablet (768px)
- [ ] Works on mobile (375px)
- [ ] Sidebar scrolls independently

---

## Troubleshooting

### Common Issues:

**1. "Feature Coming Soon" appears instead of quiz form**
- **Cause**: `quiz-form.php` component not included
- **Solution**: Ensure `teacher-programs.php` has the `elseif` block for `quiz_form`

**2. Foreign key constraint error when saving quiz**
- **Cause**: Missing `program_id` in `chapter_quizzes` insert
- **Solution**: Updated `quiz_create()` function to include `program_id`

**3. No quiz question appears on story**
- **Check**: Does the chapter have a quiz?
- **Check**: Does the quiz have questions?
- **Solution**: Create quiz via teacher dashboard

**4. Video doesn't load**
- **Check**: Is `youtube-embed-helper.php` included?
- **Check**: Is the YouTube URL valid?
- **Solution**: Verify URL format and helper function

**5. Progress not updating**
- **Check**: Is `student_story_progress` table created?
- **Check**: Database constraints satisfied?
- **Solution**: Run latest SQL migration

---

## Code Examples

### Fetch Navigation Structure:
```php
$navigation = [];
foreach ($chapters as $chapter) {
    $chapterData = [
        'chapter_id' => $chapter['chapter_id'],
        'title' => $chapter['title'],
        'stories' => chapter_getStories($conn, $chapter['chapter_id']),
        'quiz' => getChapterQuiz($conn, $chapter['chapter_id'])
    ];
    $navigation[] = $chapterData;
}
```

### Get Random Quiz Question:
```php
$quiz = getChapterQuiz($conn, $chapter_id);
if ($quiz) {
    $questions = quizQuestion_getByQuiz($conn, $quiz['quiz_id']);
    if (!empty($questions)) {
        $randomQuestion = $questions[array_rand($questions)];
    }
}
```

### Validate Answer:
```javascript
fetch('../../php/quiz-answer-handler.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    action: 'check_answer',
    question_id: questionId,
    option_id: selectedOptionId,
    story_id: storyId
  })
})
.then(response => response.json())
.then(data => {
  if (data.correct) {
    // Show success, enable next story
  } else {
    // Show error, show retry button
  }
});
```

---

## File Changes Summary

### Created Files:
1. **`php/quiz-answer-handler.php`** - Answer validation backend
2. **`docs/STUDENT_PROGRAM_VIEW.md`** - This documentation

### Modified Files:
1. **`pages/student/student-program-view.php`** - Complete redesign with sidebar + quiz
2. **`php/quiz-handler.php`** - Added `program_id` to quiz creation
3. **`pages/teacher/teacher-programs.php`** - Added quiz form component include
4. **`components/quiz-form.php`** - Enhanced with AJAX submission

---

## Git Commits

1. [Fix: Include quiz form component](https://github.com/Niflheim-ai/al-ghaya/commit/b08648dbf3a72de400bc745167cbb276495d683a)
2. [Fix: Convert quiz form to AJAX](https://github.com/Niflheim-ai/al-ghaya/commit/2ec12c165041aaa2827265e2b05a0148639a6f65)
3. [Fix: Add program_id to quiz creation](https://github.com/Niflheim-ai/al-ghaya/commit/104d34c5840d8c0146ab459b5ad71b00ef9e6cd1)
4. [Feature: Enhanced student program view](https://github.com/Niflheim-ai/al-ghaya/commit/865d05df646f7485032a2071629356c208dbd0b6)
5. [Feature: Quiz answer validation handler](https://github.com/Niflheim-ai/al-ghaya/commit/2ca45cc75ef2af4adf892a8c0266c8e263c07edb)

---

## Screenshots

### Desktop View:
```
+----------------+----------------------------------+
|   SIDEBAR      |       MAIN CONTENT              |
| (25% width)    |       (75% width)               |
+----------------+----------------------------------+
| Progress: 25%  | Story Title                     |
|                | -------------------------------  |
| 📁 Chapter 1   | Arabic Synopsis (عربي)            |
|  ▶ Story 1     | -------------------------------  |
|  ▶ Story 2     | English Synopsis                |
|  📝 Quiz       | -------------------------------  |
|                | [YouTube Video Player]          |
| 📁 Chapter 2   | -------------------------------  |
|  ▶ Story 1     | 🧠 Knowledge Check               |
|  📝 Quiz       | Q: What is...?                  |
|                | ( ) Option A                    |
+----------------+ ( ) Option B                    |
                 | (*) Option C                    |
                 | ( ) Option D                    |
                 | [Submit Answer]                 |
                 +----------------------------------+
```

### Mobile View:
```
+----------------------------------+
|         SIDEBAR (Top)            |
| Progress: 25%                    |
| 📁 Chapter 1                     |
|   ▶ Story 1                       |
+----------------------------------+
|       MAIN CONTENT (Below)       |
| Story Title                      |
| Arabic Synopsis                  |
| English Synopsis                 |
| [Video]                          |
| Quiz Question                    |
+----------------------------------+
```

---

## Performance Considerations

### Optimizations:
- **Lazy loading**: Only current story content is loaded
- **Sticky sidebar**: No re-rendering on scroll
- **AJAX validation**: No full page reload on quiz submission
- **Single database query**: Navigation structure loaded once

### Database Queries:
- **On page load**: ~5 queries
  1. Get program details + enrollment
  2. Get all chapters
  3. Get stories for each chapter
  4. Get quiz for each chapter
  5. Get current story content + quiz question

- **On quiz submission**: 2-3 queries
  1. Validate answer
  2. Log attempt
  3. Update progress (if correct)

---

## Best Practices

### For Teachers:
1. **Create quizzes** for all chapters
2. **Add multiple questions** (at least 5) per quiz
3. **Ensure correct answers** are properly marked
4. **Test quiz flow** before publishing

### For Developers:
1. **Always use prepared statements** for SQL
2. **Validate all inputs** server-side
3. **Log errors** to error log, not to users
4. **Use transactions** for multi-step operations
5. **Test responsive design** on all screen sizes

---

## Contact & Support

For issues or questions:
- Check this documentation first
- Review error logs: `php_error_log`
- Test with browser console open
- Check database constraints and foreign keys

---

**Last Updated**: November 7, 2025  
**Version**: 2.0  
**Author**: Al-Ghaya Development Team
