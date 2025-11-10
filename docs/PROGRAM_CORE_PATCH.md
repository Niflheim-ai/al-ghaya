# Program Core Patch Instructions

## Add Interactive Sections Saving to Story Handlers

You need to modify **`php/program-core.php`** to save interactive sections when creating or updating stories.

### Step 1: Find the `create_story` case

Look for this line (around line 250-260):
```php
case 'create_story':
```

### Step 2: Replace the entire `create_story` case with:

```php
case 'create_story':
    require_once __DIR__ . '/story-sections-handler.php';
    
    $program_id = intval($_POST['programID'] ?? 0);
    $chapter_id = intval($_POST['chapter_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $synopsis_arabic = trim($_POST['synopsis_arabic'] ?? '');
    $synopsis_english = trim($_POST['synopsis_english'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $sections_data = $_POST['sections'] ?? [];
    
    // Validate required fields
    if (empty($title) || empty($synopsis_arabic) || empty($synopsis_english) || empty($video_url)) {
        $_SESSION['error_message'] = 'All fields are required for the story.';
        header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
        exit;
    }
    
    // Validate at least 1 interactive section
    if (empty($sections_data) || count($sections_data) < 1) {
        $_SESSION['error_message'] = 'At least 1 interactive section is required.';
        header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
        exit;
    }
    
    // Validate maximum 3 sections
    if (count($sections_data) > 3) {
        $_SESSION['error_message'] = 'Maximum of 3 interactive sections allowed per story.';
        header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
        exit;
    }
    
    if (!$program_id) {
        $_SESSION['error_message'] = 'Program ID is required.';
        header('Location: ../pages/teacher/teacher-programs.php');
        exit;
    }
    
    $existingStories = chapter_getStories($conn, $chapter_id);
    if (count($existingStories) >= 3) {
        $_SESSION['error_message'] = 'Maximum of 3 stories per chapter allowed.';
        header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    try {
        // Create story
        $story_id = story_create($conn, [
            'chapter_id' => $chapter_id,
            'title' => $title,
            'synopsis_arabic' => $synopsis_arabic,
            'synopsis_english' => $synopsis_english,
            'video_url' => $video_url
        ]);
        
        if (!$story_id) {
            throw new Exception('Failed to create story');
        }
        
        // Save interactive sections
        if (!saveStoryInteractiveSections($conn, $story_id, $sections_data)) {
            throw new Exception('Failed to save interactive sections');
        }
        
        $conn->commit();
        $_SESSION['success_message'] = 'Story and interactive sections created successfully!';
        header('Location: ../pages/teacher/teacher-programs.php?action=edit_chapter&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Create story error: ' . $e->getMessage());
        $_SESSION['error_message'] = 'Failed to save story: ' . $e->getMessage();
        header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
        exit;
    }
```

### Step 3: Find the `update_story` case

Look for this line (around line 280-290):
```php
case 'update_story':
```

### Step 4: Replace the entire `update_story` case with:

```php
case 'update_story':
    require_once __DIR__ . '/story-sections-handler.php';
    
    $program_id = intval($_POST['programID'] ?? 0);
    $chapter_id = intval($_POST['chapter_id'] ?? 0);
    $story_id = intval($_POST['story_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $synopsis_arabic = trim($_POST['synopsis_arabic'] ?? '');
    $synopsis_english = trim($_POST['synopsis_english'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $sections_data = $_POST['sections'] ?? [];
    
    // Validate required fields
    if (!$story_id || empty($title) || empty($synopsis_arabic) || empty($synopsis_english) || empty($video_url)) {
        $_SESSION['error_message'] = 'All fields are required for the story update.';
        header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . '&story_id=' . $story_id);
        exit;
    }
    
    // Validate at least 1 interactive section
    if (empty($sections_data) || count($sections_data) < 1) {
        $_SESSION['error_message'] = 'At least 1 interactive section is required.';
        header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . '&story_id=' . $story_id);
        exit;
    }
    
    // Validate maximum 3 sections
    if (count($sections_data) > 3) {
        $_SESSION['error_message'] = 'Maximum of 3 interactive sections allowed per story.';
        header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . '&story_id=' . $story_id);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    try {
        // Update story
        $data = [
            'title' => $title,
            'synopsis_arabic' => $synopsis_arabic,
            'synopsis_english' => $synopsis_english,
            'video_url' => $video_url
        ];
        
        if (!story_update($conn, $story_id, $data)) {
            throw new Exception('Failed to update story');
        }
        
        // Save interactive sections (this deletes old ones and creates new)
        if (!saveStoryInteractiveSections($conn, $story_id, $sections_data)) {
            throw new Exception('Failed to save interactive sections');
        }
        
        $conn->commit();
        $_SESSION['success_message'] = 'Story and interactive sections updated successfully!';
        header('Location: ../pages/teacher/teacher-programs.php?action=edit_chapter&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Update story error: ' . $e->getMessage());
        $_SESSION['error_message'] = 'Failed to update story: ' . $e->getMessage();
        header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . '&story_id=' . $story_id);
        exit;
    }
```

## Summary of Changes

1. **Added** `require_once __DIR__ . '/story-sections-handler.php';` at the beginning of both cases
2. **Added** `$sections_data = $_POST['sections'] ?? [];` to capture the form data
3. **Added** validation for interactive sections (min 1, max 3)
4. **Wrapped** story creation/update in database transactions
5. **Called** `saveStoryInteractiveSections($conn, $story_id, $sections_data)` to save the sections
6. **Added** proper error handling with rollback on failure

## Testing

After making these changes:

1. Create a new story with 1-3 interactive sections
2. Verify the sections are saved to the database
3. Edit an existing story and update its interactive sections
4. Verify the old sections are replaced with new ones
5. Check error handling by trying to save without sections or with more than 3

## Debugging

If sections aren't saving:

1. Check PHP error logs: `tail -f /path/to/php-error.log`
2. Look for error_log messages starting with "Saving interactive sections"
3. Verify the `$_POST['sections']` data is being received correctly
4. Check database permissions for INSERT/DELETE operations
