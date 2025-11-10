# Student Program View Fixes

## Issues to Fix

1. **Interactive sections not showing** - Currently showing random quiz questions instead of the manually created interactive sections
2. **Sidebar missing exam/certification** - Final exam and certificate links need to be more prominent
3. **Content not updating** - Need to properly load interactive sections from the database

---

## Changes Needed in `student-program-view.php`

### **1. Change How Interactive Questions Load**

**Find this section (around line 118-130):**
```php
if ($currentContent) {
    $quiz = getChapterQuiz($conn, $currentContent['chapter_id']);
    if ($quiz) {
        $questions = quizQuestion_getByQuiz($conn, $quiz['quiz_id']);
        if (!empty($questions)) {
            $currentContent['quiz_question'] = $questions[array_rand($questions)];
        }
    }
    $is_completed = !empty($userStoryProgress[$currentContent['story_id']]);
}
```

**Replace with:**
```php
if ($currentContent) {
    // Load interactive sections for this story
    $interactiveSections = interactiveSection_getByStory($conn, $currentContent['story_id']);
    if (!empty($interactiveSections)) {
        // Get first incomplete section
        $currentSection = null;
        foreach ($interactiveSections as $section) {
            $sectionQuestions = interactiveQuestion_getBySection($conn, $section['section_id']);
            if (!empty($sectionQuestions)) {
                $currentSection = $section;
                $currentSection['questions'] = $sectionQuestions;
                break;
            }
        }
        if ($currentSection && !empty($currentSection['questions'])) {
            $currentContent['interactive_section'] = $currentSection;
            // Get first question from section
            $currentContent['quiz_question'] = $currentSection['questions'][0];
            // Convert to expected format
            $currentContent['quiz_question']['options'] = questionOption_getByQuestion($conn, $currentContent['quiz_question']['question_id']);
        }
    }
    $is_completed = !empty($userStoryProgress[$currentContent['story_id']]);
}
```

### **2. Update the Quiz Display Section**

**Find this section (around line 215-240, the quiz section HTML):**
```php
<?php elseif (!empty($currentContent['quiz_question'])): ?>
  <?php $question = $currentContent['quiz_question']; ?>
  <div id="quizSection" class="bg-gradient-to-br from-orange-50 to-yellow-50 rounded-xl p-6 border-2 border-orange-300">
```

**Replace the entire quiz section with:**
```php
<?php elseif (!empty($currentContent['interactive_section']) && !empty($currentContent['quiz_question'])): ?>
  <?php 
  $section = $currentContent['interactive_section'];
  $question = $currentContent['quiz_question']; 
  ?>
  <div id="quizSection" class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-6 border-2 border-purple-300">
    <div class="flex items-center gap-2 mb-4">
      <i class="ph ph-chat-circle-dots text-3xl text-purple-600"></i>
      <h3 class="text-xl font-bold text-purple-900">Interactive Section</h3>
    </div>
    <p class="text-gray-800 font-medium mb-4 text-lg"><?= htmlspecialchars($question['question_text']) ?></p>
    <form id="quizForm" class="space-y-3">
      <?php foreach ($question['options'] as $index => $option): ?>
        <label class="flex items-center gap-3 p-4 bg-white rounded-lg border-2 border-gray-200 hover:border-purple-400 cursor-pointer transition-all">
          <input type="radio" name="answer" value="<?= $option['option_id'] ?>" class="w-5 h-5 text-purple-600 focus:ring-purple-500" required>
          <span class="text-gray-800"><?= htmlspecialchars($option['option_text']) ?></span>
        </label>
      <?php endforeach; ?>
      <input type="hidden" name="question_id" value="<?= $question['question_id'] ?>">
      <input type="hidden" name="story_id" value="<?= $currentContent['story_id'] ?>">
      <input type="hidden" name="chapter_id" value="<?= $currentContent['chapter_id'] ?>">
      <div class="flex gap-3 mt-6">
        <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold shadow-lg transition-colors">
          <i class="ph ph-check-circle mr-2"></i>Submit Answer
        </button>
        <button type="button" id="retryBtn" class="hidden px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors" onclick="retryQuestion()">
          <i class="ph ph-arrow-clockwise mr-2"></i>Retry
        </button>
      </div>
    </form>
    <div id="answerFeedback" class="hidden mt-4 p-4 rounded-lg"></div>
  </div>
<?php endif; ?>
```

### **3. Fix the Sidebar - Make Exam/Cert More Visible**

**Find the sidebar section (around line 60-90) and update it:**

**Current exam/cert display is at the bottom. Move it INSIDE the sidebar, after the content list:**

Find this:
```php
          </div>
        </div>
      </aside>
```

Add BEFORE the closing `</div></div>` of the sidebar:
```php
            <!-- Final Exam Section -->
            <?php if ($showExam): ?>
              <div class="p-4 bg-gradient-to-r from-orange-50 to-yellow-50 border-2 border-orange-300 rounded-lg mt-4">
                <div class="flex items-center gap-2 mb-2">
                  <i class="ph ph-exam text-2xl text-orange-600"></i>
                  <h3 class="font-bold text-orange-900">Ready for Exam?</h3>
                </div>
                <p class="text-sm text-gray-700 mb-3">You've completed all stories! Take the final exam to earn your certificate.</p>
                <a href="?program_id=<?= $programID ?>&take_exam=1" class="block w-full text-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold transition-colors">
                  Take Final Exam
                </a>
              </div>
            <?php endif; ?>
            
            <!-- Certificate Section -->
            <?php if ($showCertificate): ?>
              <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg mt-4">
                <div class="flex items-center gap-2 mb-2">
                  <i class="ph ph-certificate text-2xl text-green-600"></i>
                  <h3 class="font-bold text-green-900">Certificate Earned!</h3>
                </div>
                <p class="text-sm text-gray-700 mb-3">Congratulations on completing this program!</p>
                <a href="<?= htmlspecialchars($certificate['certificate_url'] ?? '#') ?>" target="_blank" class="block w-full text-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors">
                  Download Certificate
                </a>
              </div>
            <?php endif; ?>
```

### **4. Update the Quiz Answer Handler**

**Find the quiz form submit JavaScript (around line 280-320):**

Update the fetch URL if needed and ensure it handles interactive questions properly:

```javascript
fetch('../../php/quiz-answer-handler.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    action:'check_interactive_answer',  // Changed from 'check_answer'
    question_id:questionId,
    option_id:selectedAnswer,
    story_id:formData.get('story_id')
  })
})
```

---

## Additional Required Changes

### **5. Update `quiz-answer-handler.php`**

Add a new case to handle interactive section answers:

```php
case 'check_interactive_answer':
    $question_id = intval($_POST['question_id'] ?? 0);
    $option_id = intval($_POST['option_id'] ?? 0);
    $story_id = intval($_POST['story_id'] ?? 0);
    
    if (!$question_id || !$option_id || !$story_id) {
        echo json_encode(['success' => false, 'correct' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    // Check if answer is correct
    $stmt = $conn->prepare("SELECT is_correct FROM question_options WHERE option_id = ?");
    $stmt->bind_param("i", $option_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $isCorrect = $result && $result['is_correct'] == 1;
    
    if ($isCorrect) {
        // Mark story as completed
        markStoryComplete($conn, $studentID, $story_id);
        echo json_encode([
            'success' => true,
            'correct' => true,
            'message' => 'Correct! You can proceed to the next story.'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'correct' => false,
            'message' => 'Incorrect. Please try again.'
        ]);
    }
    exit;
```

---

## Summary of Changes

1. ✅ **Load interactive sections** instead of random quiz questions
2. ✅ **Display interactive questions** from the manually created sections
3. ✅ **Move exam/certificate** to prominent position in sidebar
4. ✅ **Update answer handler** to process interactive section answers
5. ✅ **Fix field names** to match the new database structure (`option_id` vs `quiz_option_id`)

---

## Testing Checklist

After making these changes:

- [ ] View a story as a student
- [ ] Verify interactive section questions appear (not random quiz questions)
- [ ] Answer questions correctly and verify story completes
- [ ] Check sidebar shows exam when all stories complete
- [ ] Take final exam and verify certificate generation
- [ ] Verify certificate link appears in sidebar

---

## Database Field Reference

**Interactive Questions Table:**
- `question_id` (not `quiz_question_id`)
- `question_text`
- `section_id`

**Question Options Table:**
- `option_id` (not `quiz_option_id`)
- `option_text`
- `is_correct`
- `question_id`

Make sure all references match these field names!
