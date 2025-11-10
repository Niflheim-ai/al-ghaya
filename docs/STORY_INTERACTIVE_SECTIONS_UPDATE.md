# Story Interactive Sections Update

This document explains the changes made to enable teachers to manually create interactive sections within story forms.

## Changes Overview

### 1. Modified Files

#### `components/story-form.php`
- **Added**: Inline interactive section management directly in the story creation/edit form
- **Features**:
  - Teachers can add 1-3 interactive sections (minimum 1, maximum 3)
  - Each section contains one question with 4 multiple choice options
  - Supports question types: Multiple Choice, Fill in the Blank, Multiple Select
  - Validation ensures at least one interactive section and one correct answer per question
  - Real-time UI feedback for section count limits

#### `php/program-core.php` (TO BE UPDATED)
- **Location**: Lines containing `case 'create_story':` and `case 'update_story':`
- **Required Changes**: Add interactive section processing after story creation/update

### 2. Updated Story Creation Flow

**Old Flow**:
1. Teacher creates story with title, synopsis, and video
2. Story saved to database
3. Interactive sections populated randomly from chapter quiz

**New Flow**:
1. Teacher creates story with title, synopsis, and video
2. Teacher adds 1-3 interactive sections directly in the form
3. Each section has one question with options and marked correct answers
4. Story saved to database
5. Interactive sections and questions saved immediately after story

### 3. Database Operations

When saving a story, the following operations occur:

#### For Create Story:
```php
1. Create story record
2. Get story_id
3. For each interactive section in $_POST['sections']:
   a. Create section record (story_interactive_sections table)
   b. Get section_id
   c. For each question in section:
      - Create question record (interactive_questions table)
      - Get question_id
      - For each option:
        * Create option record (question_options table)
```

#### For Update Story:
```php
1. Update story record
2. Delete all existing interactive sections for this story
3. For each interactive section in $_POST['sections']:
   a. If section_id exists, update; else create new
   b. Delete existing questions for this section
   c. For each question in section:
      - Create new question record
      - For each option:
        * Create new option record
```

### 4. Code to Add to program-core.php

Replace the `case 'create_story':` and `case 'update_story':` sections with:

```php
case 'create_story':
    require_once __DIR__ . '/quiz-handler.php';
    
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
        
        // Create interactive sections
        foreach ($sections_data as $section_index => $section_data) {
            $section_order = intval($section_data['section_order'] ?? ($section_index + 1));
            
            // Create section
            $section_id = interactiveSection_create($conn, $story_id);
            if (!$section_id) {
                throw new Exception('Failed to create interactive section');
            }
            
            // Create questions for this section
            $questions = $section_data['questions'] ?? [];
            foreach ($questions as $q_index => $question_data) {
                $question_text = trim($question_data['text'] ?? '');
                $question_type = $question_data['type'] ?? 'multiple_choice';
                
                if (empty($question_text)) continue;
                
                $question_id = interactiveQuestion_create($conn, $section_id, $question_text, $question_type, $q_index + 1);
                if (!$question_id) {
                    throw new Exception('Failed to create question');
                }
                
                // Create options
                $options = $question_data['options'] ?? [];
                foreach ($options as $opt_index => $option_data) {
                    $option_text = trim($option_data['text'] ?? '');
                    $is_correct = isset($option_data['is_correct']) ? 1 : 0;
                    
                    if (empty($option_text)) continue;
                    
                    if (!questionOption_create($conn, $question_id, $option_text, $is_correct, $opt_index + 1)) {
                        throw new Exception('Failed to create option');
                    }
                }
            }
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

case 'update_story':
    require_once __DIR__ . '/quiz-handler.php';
    
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
        
        // Delete all existing interactive sections and their questions
        story_deleteInteractiveSections($conn, $story_id);
        
        // Create new interactive sections
        foreach ($sections_data as $section_index => $section_data) {
            $section_order = intval($section_data['section_order'] ?? ($section_index + 1));
            
            // Create section
            $section_id = interactiveSection_create($conn, $story_id);
            if (!$section_id) {
                throw new Exception('Failed to create interactive section');
            }
            
            // Create questions for this section
            $questions = $section_data['questions'] ?? [];
            foreach ($questions as $q_index => $question_data) {
                $question_text = trim($question_data['text'] ?? '');
                $question_type = $question_data['type'] ?? 'multiple_choice';
                
                if (empty($question_text)) continue;
                
                $question_id = interactiveQuestion_create($conn, $section_id, $question_text, $question_type, $q_index + 1);
                if (!$question_id) {
                    throw new Exception('Failed to create question');
                }
                
                // Create options
                $options = $question_data['options'] ?? [];
                foreach ($options as $opt_index => $option_data) {
                    $option_text = trim($option_data['text'] ?? '');
                    $is_correct = isset($option_data['is_correct']) ? 1 : 0;
                    
                    if (empty($option_text)) continue;
                    
                    if (!questionOption_create($conn, $question_id, $option_text, $is_correct, $opt_index + 1)) {
                        throw new Exception('Failed to create option');
                    }
                }
            }
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

### 5. POST Data Structure

When the form is submitted, the `$_POST` data looks like:

```php
[
    'action' => 'create_story', // or 'update_story'
    'programID' => 123,
    'chapter_id' => 456,
    'story_id' => 789, // only for update
    'title' => 'Story Title',
    'synopsis_arabic' => 'Arabic synopsis',
    'synopsis_english' => 'English synopsis',
    'video_url' => 'https://youtube.com/...',
    'sections' => [
        0 => [
            'section_order' => 1,
            'section_id' => 10, // only when editing
            'questions' => [
                0 => [
                    'type' => 'multiple_choice',
                    'text' => 'What is the question?',
                    'options' => [
                        0 => ['text' => 'Option 1', 'is_correct' => true],
                        1 => ['text' => 'Option 2'],
                        2 => ['text' => 'Option 3'],
                        3 => ['text' => 'Option 4']
                    ]
                ]
            ]
        ],
        // ... up to 2 more sections
    ]
]
```

### 6. Benefits of New Approach

1. **Relevance**: Interactive sections are specifically created for each story, ensuring questions are relevant to the story content
2. **Control**: Teachers have full control over what questions appear and when
3. **Simplicity**: All story content, including interactive sections, is managed in one place
4. **Validation**: Built-in validation ensures quality (min 1 section, max 3, at least one correct answer)
5. **User Experience**: Immediate feedback and inline editing improves teacher workflow

### 7. Migration Notes

- Existing stories with randomly assigned questions from chapter quizzes will continue to work
- New stories must have manually created interactive sections
- When editing old stories, teachers will need to create interactive sections manually
- The `interactive-sections.php` standalone component is still available for reference but is no longer used in the story creation flow
