# Enhanced Program Creation System - Al-Ghaya LMS

This document describes the comprehensive program creation system that allows teachers to create interactive learning programs with chapters, stories, quizzes, and publishing workflows.

## 🎯 System Overview

The enhanced program system provides:

- **Complete Program Management**: Create, edit, and manage learning programs
- **Chapter Organization**: Structure programs into organized chapters
- **Interactive Stories**: Add stories with Arabic/English content and video integration
- **Dynamic Quizzes**: Chapter-based quizzes with multiple question types
- **Interactive Sections**: Engaging content with multiple choice, fill-in-blanks, and multiple select questions
- **Publishing Workflow**: Admin approval system for program publication
- **Program Library**: Browse and discover programs from other teachers

## 📁 File Structure

### Core Files
- `pages/teacher/teacher-programs-enhanced.php` - Main teacher programs interface
- `php/enhanced-program-functions.php` - Core functions for program operations
- `php/program-handler.php` - Form processing and AJAX endpoints
- `database/program_system_schema.sql` - Complete database schema

### Component Files
- `components/program-details-form.php` - Program creation/editing form
- `components/chapter-content-form.php` - Chapter management interface
- `components/story-form.php` - Story creation with interactive sections
- `components/quiz-form.php` - Quiz creation and management
- `components/quick-access.php` - Updated toolbar with publishing features

## 🗄️ Database Schema

### Core Tables

#### `programs`
```sql
CREATE TABLE programs (
    programID INT PRIMARY KEY AUTO_INCREMENT,
    teacherID INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('Student', 'Aspiring', 'Master') NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    thumbnail VARCHAR(255) DEFAULT 'default-thumbnail.jpg',
    overview_video_url VARCHAR(500),
    difficulty_level ENUM('Student', 'Aspiring', 'Master') NOT NULL,
    status ENUM('draft', 'pending_review', 'published', 'rejected') DEFAULT 'draft',
    dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dateUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### `program_chapters`
```sql
CREATE TABLE program_chapters (
    chapter_id INT PRIMARY KEY AUTO_INCREMENT,
    program_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    chapter_order INT NOT NULL,
    FOREIGN KEY (program_id) REFERENCES programs(programID) ON DELETE CASCADE
);
```

#### `chapter_stories`
```sql
CREATE TABLE chapter_stories (
    story_id INT PRIMARY KEY AUTO_INCREMENT,
    chapter_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    synopsis_arabic TEXT,
    synopsis_english TEXT,
    video_url VARCHAR(500) NOT NULL,
    story_order INT NOT NULL DEFAULT 1,
    FOREIGN KEY (chapter_id) REFERENCES program_chapters(chapter_id) ON DELETE CASCADE
);
```

#### Interactive Content Tables
- `story_interactive_sections` - Container for interactive content (max 3 per story)
- `interactive_questions` - Questions within interactive sections
- `question_options` - Answer options for questions
- `chapter_quizzes` - Chapter-level quizzes (1 per chapter)
- `quiz_questions` - Quiz questions (max 30 per quiz)
- `quiz_question_options` - Quiz answer options

#### Publishing & Progress Tables
- `program_publish_requests` - Publishing workflow management
- `student_enrollments` - Student enrollment tracking
- `student_story_progress` - Progress tracking per story
- `student_quiz_attempts` - Quiz attempt history

## 🚀 Features

### 1. Program Creation Workflow

#### Step 1: Program Details
- **Thumbnail Upload**: 500x400px recommended, JPG/PNG support
- **Program Information**: Title, description with Arabic/English support
- **Difficulty Levels**: Student, Aspiring, Master with visual indicators
- **Pricing**: Philippine Peso (₱) pricing
- **Overview Video**: YouTube integration for program previews

#### Step 2: Chapter Management
- **Dynamic Chapter Addition**: Add/edit/delete chapters
- **Chapter Organization**: Automatic ordering and management
- **Content Overview**: Story and quiz count per chapter

#### Step 3: Story Creation
- **Bilingual Content**: Arabic and English synopses
- **Video Integration**: YouTube video embedding for story progression
- **Interactive Sections**: 1-3 interactive sections per story
- **Question Types**:
  - **Multiple Choice**: Traditional single-answer questions
  - **Fill-in-the-Blanks**: Text completion exercises
  - **Multiple Select**: Multi-answer questions
- **Answer Key Management**: Visual answer key setting with green highlighting

#### Step 4: Quiz Creation
- **Chapter Quizzes**: Mandatory 1 quiz per chapter
- **Question Limit**: Maximum 30 questions per quiz
- **Multiple Choice Only**: Standardized quiz format
- **Answer Management**: Built-in answer key system

### 2. Publishing System

#### Teacher Workflow
1. Create program and content as **Draft**
2. Use **Publish** button to select programs for review
3. Submit selected programs to admin for approval
4. Programs move to **Pending Review** status
5. Receive notification of approval/rejection

#### Admin Workflow
1. Review pending programs
2. Approve or reject with optional feedback
3. Approved programs become **Published** and visible to students
4. Rejected programs return to **Draft** with rejection reason

### 3. Program Library System

#### My Programs Section
- Shows only programs created by the logged-in teacher
- Status indicators: Draft, Pending Review, Published, Rejected
- Quick edit and view options
- Creation date and enrollment statistics

#### Program Library Section
- Displays all published programs from other teachers
- Compact card view for space efficiency
- Teacher attribution
- Quick overview access

### 4. Quick Access Toolbar

#### New Program Button
- Direct link to program creation flow
- Maintains existing functionality

#### Publish Button
- Modal popup showing draft programs
- Multi-select for bulk publishing requests
- Real-time status updates

#### Update Button
- Bulk operations modal
- Options for status updates, pricing changes, category modifications
- Batch processing capabilities

## 🎨 User Interface Features

### Visual Design Elements
- **Difficulty Color Coding**:
  - Student: Black (#374151)
  - Aspiring: Blue (#10375B)
  - Master: Gold (#A58618)

- **Status Indicators**:
  - Draft: Gray badge
  - Pending Review: Yellow badge
  - Published: Green badge
  - Rejected: Red badge

- **Interactive Elements**:
  - Hover effects on all buttons and cards
  - Visual feedback for form interactions
  - Progress indicators for multi-step processes
  - Real-time validation and error handling

### Responsive Design
- Mobile-friendly interface
- Grid layouts that adapt to screen size
- Touch-friendly button sizing
- Optimized for tablets and desktop

## 🔧 Technical Implementation

### Frontend Technologies
- **HTML5**: Semantic markup with accessibility features
- **Tailwind CSS**: Utility-first styling framework
- **JavaScript**: Vanilla JS for interactions and AJAX
- **Phosphor Icons**: Consistent iconography
- **SweetAlert2**: Enhanced alert and confirmation dialogs

### Backend Technologies
- **PHP 8+**: Server-side logic and database operations
- **MySQL**: Relational database with foreign key constraints
- **Sessions**: User authentication and state management
- **File Uploads**: Image handling for thumbnails
- **JSON APIs**: AJAX endpoints for dynamic interactions

### Security Features
- **Authentication**: Teacher role verification
- **Authorization**: Program ownership validation
- **Input Sanitization**: SQL injection prevention
- **CSRF Protection**: Token-based form validation
- **File Upload Security**: Type and size validation
- **URL Validation**: YouTube URL verification

## 🛠️ Setup Instructions

### 1. Database Setup
```sql
-- Run the complete schema
SOURCE database/program_system_schema.sql;
```

### 2. Directory Structure
Ensure these directories exist with proper permissions:
```
uploads/
├── thumbnails/          # Program thumbnails
└── program_content/     # Additional content files
```

### 3. File Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/thumbnails/
chmod 644 components/*.php
chmod 644 pages/teacher/*.php
chmod 644 php/*.php
```

### 4. Configuration
Update your database connection settings in `php/dbConnection.php`.

## 📊 Usage Statistics

The system tracks:
- Program creation and modification dates
- Student enrollment numbers
- Progress completion rates
- Quiz attempt statistics
- Publishing request history

## 🐛 Troubleshooting

### Common Issues

1. **Thumbnail Upload Fails**
   - Check file permissions on uploads directory
   - Verify file size limits in PHP configuration
   - Ensure allowed file types (JPG, PNG, GIF)

2. **YouTube Videos Not Loading**
   - Validate URL format
   - Check for proper YouTube URL structure
   - Verify video is public/unlisted (not private)

3. **Database Errors**
   - Check foreign key constraints
   - Verify table relationships
   - Ensure proper character encoding (UTF-8)

4. **Session Issues**
   - Verify PHP session configuration
   - Check session storage permissions
   - Clear browser cookies if needed

### Error Logging
Errors are logged to PHP error log. Check:
- Server error logs
- Application-specific error logs
- Browser console for JavaScript errors

## 🔮 Future Enhancements

### Planned Features
- **Drag-and-Drop**: Reorder chapters and stories
- **Content Duplication**: Copy content between programs
- **Advanced Analytics**: Detailed engagement metrics
- **Content Templates**: Pre-built story and quiz templates
- **Collaborative Editing**: Multiple teachers per program
- **Version Control**: Content revision history
- **Export/Import**: Backup and restore functionality
- **Mobile App**: Native mobile content creation

### Integration Possibilities
- **LTI Compliance**: Learning Tools Interoperability
- **SCORM Support**: Shareable Content Object Reference Model
- **API Endpoints**: Third-party integration
- **Webhook Support**: Real-time notifications
- **Cloud Storage**: External file storage integration

## 📞 Support

For technical support or feature requests:
1. Check the troubleshooting section
2. Review error logs for specific issues
3. Consult the database schema for relationship questions
4. Test with different browsers for compatibility issues

## 📝 License

This enhanced program system is part of the Al-Ghaya LMS project and follows the same licensing terms as the main application.