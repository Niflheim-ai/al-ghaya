# FIX_AUDIT_LOG.md

Comprehensive Audit and Hardening Log for Al-Ghaya LMS

Date: 2025-10-26
Branch: main

Scope
- OAuth2 and login flows (authorized.php, login-api.php)
- Email delivery (mail-config.php usage, functions.php)
- Program/Chapter/Story handlers (program-handler.php)
- Security, SQL injection, credentials management
- Consistency with database schema (sql/al_ghaya_lms.sql)

Summary of Critical Fixes
1) OAuth2 redirect and credentials standardization
- Standardized redirect URI to APP_URL + /php/authorized.php
- Removed hardcoded fallback Google client ID/secret
- Ensured both http://localhost and http://localhost:8080 URIs can be registered in Google Console

2) Credentials and mail configuration
- Removed hardcoded SMTP credentials in functions.php (createTeacherAccount)
- Switched to centralized createMailer()/sendTeacherWelcomeEmail() from mail-config.php
- Sender now sourced from MAIL_FROM_ADDRESS/MAIL_FROM_NAME

3) Schema alignment and legacy name fixes (program-handler.php)
- storyinteractivesections → story_interactive_sections
- interactivequestions → interactive_questions
- sectionid → section_id; sectionorder → section_order
- chapter_stories columns corrected: synopsis_arabic, synopsis_english, video_url
- Quoted all array indices: $_POST['programID'], $chapter['programID']

4) SQL injection hardening
- Converted raw SQL with interpolated user input to prepared statements in functions.php:
  - createAccount(): email existence check
  - fetchProgramData(): title/category lookup

5) DB connection consistency
- Replaced direct new mysqli() with shared connection via dbConnection.php in createTeacherAccount

6) Misc improvements
- Consistent JSON responses in program-handler AJAX endpoints
- Added error logging at failure points

Files Changed
- php/authorized.php
  - Use Config::get('APP_URL') . '/php/authorized.php' for redirect
  - Remove hardcoded Google client fallback values
- php/login-api.php
  - Use Config::get('APP_URL') . '/php/authorized.php' for redirect
  - Remove dynamic host guessing where possible
- php/functions.php
  - createAccount(): use prepared statements
  - fetchProgramData(): use prepared statement
  - createTeacherAccount(): use getDbConnection(), createMailer(), sendTeacherWelcomeEmail()
- php/program-handler.php
  - Normalize table/column names per schema
  - Quote array keys
  - Improve JSON outputs and error handling

Testing Performed
- OAuth login flow end-to-end (authorization → callback → session)
- Teacher creation email path (SMTP fallback and OAuth2 when refresh token present)
- Program create/update, chapter add/delete, story create/delete
- Interactive section create/delete with limits

Post-Deployment Steps
- In Google Cloud Console, add Authorized redirect URIs:
  - http://localhost:8080/al-ghaya/php/authorized.php
  - http://localhost/al-ghaya/php/authorized.php
- Ensure .env does not contain bracketed keys; use plain key=value
- Run php/test-email-setup.php once to validate email delivery, then delete it

Notes
- Consider adding CSRF tokens for program-handler POST actions
- Add rate limiting for auth endpoints
- Consider unit tests for handlers
