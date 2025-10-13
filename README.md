# Al-Ghaya LMS (Learning Management System)

**A Gamified Learning Management System for Arabic and Islamic Studies**

![Al-Ghaya Logo](images/al-ghaya_logoForPrint.svg)

## 🌟 Overview

Al-Ghaya is a comprehensive, gamified Learning Management System designed specifically for Arabic language learning and Islamic studies. The platform provides an engaging, interactive learning experience through storytelling, gamification, and personalized progress tracking.

## 🚀 Recent Enhancements

This development branch includes significant improvements and new features:

### 🎮 Gamification System
- **Points & Levels**: Complete point system with level progression
- **Achievements**: Unlock achievements for various learning milestones
- **Proficiency Tracking**: Automatic progression from Beginner → Intermediate → Advanced
- **Daily Challenges**: Interactive questions with point rewards
- **Streak Tracking**: Maintain learning streaks for bonus points
- **Leaderboards**: Compete with other students

### 📚 Enhanced Learning Experience
- **Interactive Storytelling**: Chapter-based learning with engaging narratives
- **Multiple Question Types**: Multiple choice, true/false, and short answer questions
- **Multimedia Support**: Video and audio content integration
- **Progress Tracking**: Real-time progress monitoring with visual indicators
- **Personalized Recommendations**: AI-driven program suggestions based on proficiency

### 🛠️ Advanced Chapter Management
- **Drag & Drop Reordering**: Easy chapter organization
- **Rich Content Editor**: Support for text, video, and audio content
- **Question Builder**: Advanced assessment creation tools
- **Preview System**: Live chapter preview before publishing
- **Points Configuration**: Customizable reward system per chapter

### 📊 Analytics & Insights
- **Student Progress Analytics**: Detailed learning statistics
- **Program Performance Metrics**: Enrollment and completion rates
- **Engagement Tracking**: Time spent, chapters completed, quiz scores
- **Real-time Dashboards**: Live updates of student activity

## 🎨 Features

### For Students
- 📝 **Personalized Dashboard**: View progress, achievements, and daily challenges
- 🎯 **Goal Tracking**: Set and achieve learning objectives
- 📈 **Progress Visualization**: Clear progress bars and statistics
- 🎆 **Achievement System**: Earn badges and unlock new content
- 📅 **Daily Login Rewards**: Bonus points for consistent learning
- 📱 **Mobile Responsive**: Learn on any device

### For Teachers
- 🏭 **Program Creation**: Build comprehensive learning programs
- 📚 **Chapter Management**: Advanced content creation tools
- 📊 **Analytics Dashboard**: Monitor student progress and engagement
- 🎬 **Multimedia Integration**: Add videos, audio, and interactive content
- 🔄 **Content Organization**: Drag-and-drop chapter reordering
- ⚙️ **Customization**: Configure points, difficulty levels, and prerequisites

### For Administrators
- 📉 **System Analytics**: Comprehensive platform statistics
- 👥 **User Management**: Manage students, teachers, and accounts
- 🏢 **Institution Management**: Multi-tenant support
- 🔒 **Security Controls**: Advanced permissions and access control
- 🔧 **System Configuration**: Customize platform settings

## 🏠 Architecture

### Technology Stack
- **Frontend**: HTML5, CSS3 (Tailwind CSS), JavaScript (Vanilla)
- **Backend**: PHP 7.4+, MySQL 8.0+
- **Authentication**: Google OAuth 2.0, PHPMailer
- **UI Framework**: Tailwind CSS 4.x
- **Dependencies**: Composer for PHP packages, npm for Node.js packages

### Database Schema
The system uses a comprehensive MySQL database with the following key tables:

- **Users & Authentication**: `user`, `teacher`
- **Learning Content**: `programs`, `program_chapters`, `student_program`
- **Gamification**: `point_transactions`, `user_achievements`, `user_streaks`
- **Progress Tracking**: `student_chapter_progress`
- **System**: `notifications`, `system_settings`, `achievement_definitions`

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Composer
- Node.js & npm
- Web server (Apache/Nginx)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Niflheim-ai/al-ghaya.git
   cd al-ghaya
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

4. **Install Node.js dependencies**
   ```bash
   npm install
   ```

5. **Database setup**
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE al_ghaya_lms;
   
   # Import schema
   mysql -u root -p al_ghaya_lms < sql/database_setup.sql
   ```

6. **Configure database connection**
   ```php
   // Edit php/dbConnection.php
   $conn = mysqli_connect("localhost", "your_username", "your_password", "al_ghaya_lms");
   ```

7. **Build assets**
   ```bash
   npm run build
   ```

8. **Start development server**
   ```bash
   npm run start
   # or
   php -S localhost:8000 -t pages
   ```

## 🎮 Gamification System Details

### Point System
- **Daily Login**: 10 points
- **Chapter Completion**: 50 points (configurable)
- **Program Completion**: 200 points
- **Quiz Correct Answer**: 5 points
- **Perfect Quiz Score**: 25 bonus points
- **Streak Bonuses**: 15 points

### Level Progression
- **Level 1**: 0-99 points (Beginner)
- **Level 2**: 100-299 points (Beginner)
- **Level 3**: 300-599 points (Beginner)
- **Level 4**: 600-999 points (Intermediate)
- **Level 5**: 1000-1499 points (Intermediate)
- **Level 6**: 1500-2199 points (Intermediate)
- **Level 7**: 2200-2999 points (Intermediate)
- **Level 8**: 3000-3999 points (Advanced)
- **Level 9**: 4000-5499 points (Advanced)
- **Level 10+**: 5500+ points (Advanced)

### Achievement Types
- **Welcome Aboard**: First login
- **Level Master**: Advance to a new level
- **Knowledge Seeker**: Advance proficiency level
- **Learning Begins**: Enroll in first program
- **Program Master**: Complete a program
- **Point Collector**: Earn milestone points (100, 500, 1000+)
- **Graduate**: Complete all programs in a difficulty level

## 📊 API Endpoints

### Chapter Management
- `GET /pages/teacher/get-chapter.php?chapter_id={id}` - Fetch chapter data
- `POST /pages/teacher/teacher-chapters.php` - Create/update chapters

### Gamification
- Integrated into existing pages (no separate API endpoints)
- Real-time point calculations and level updates

## 🔒 Security Features

- **Authentication**: Secure session management and Google OAuth
- **Authorization**: Role-based access control (Student, Teacher, Admin)
- **Data Validation**: Input sanitization and prepared statements
- **CSRF Protection**: Form token validation
- **Password Security**: Bcrypt hashing with cost factor 12

## 🐛 Bug Fixes in Branches

- ✅ **Resolved merge conflicts** in package.json and composer.json
- ✅ **Fixed program enrollment system** with proper progress tracking
- ✅ **Enhanced chapter creation/editing** with multimedia support
- ✅ **Improved dashboard performance** with optimized queries
- ✅ **Fixed thumbnail display issues** mentioned in recent commits
- ✅ **Added proper error handling** throughout the application
- ✅ **Implemented missing gamification features**

## 📝 Documentation

### Database Views
- `user_leaderboard`: Student rankings by points
- `program_statistics`: Program enrollment and completion data
- `student_dashboard_stats`: Individual student statistics

### Configuration
System settings can be configured through the `system_settings` table:
- Point values for different activities
- Email notification settings
- Registration controls
- Site branding

## 🔮 Upcoming Features (Roadmap)

- **Advanced Analytics**: Machine learning insights
- **Mobile App**: Native iOS/Android applications
- **Live Classes**: Video conferencing integration
- **Social Features**: Student forums and peer interaction
- **Certification**: Digital certificates and badges
- **Multi-language**: Support for multiple UI languages
- **Payment Integration**: Course monetization
- **AI Tutoring**: Personalized learning assistant

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📧 Contact

- **Project Team**: Al-Ghaya Development Team
- **Email**: fmanaois4@gmail.com
- **Website**: In Progress
- **Live Demo**: In Progress

## 🚀 Deployment

### Production Deployment
1. Set environment variables for production database
2. Configure web server (Apache/Nginx)
3. Set up SSL certificates
4. Configure email settings for notifications
5. Set up backup procedures

### Vercel Deployment (Current)
- Deployed at: https://al-ghaya-2.vercel.app
- Automatic deployments from main branch
- Environment variables configured in Vercel dashboard

## 🎆 Acknowledgments

- **Tailwind CSS**: For the beautiful UI components
- **SweetAlert2**: For elegant alert dialogs
- **Font Awesome**: For comprehensive icon library
- **PHPMailer**: For reliable email functionality
- **Google OAuth**: For secure authentication
- **Phosphor Icons**: For modern icon design

---
