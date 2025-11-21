# Staff Management System - Federal Polytechnic of Oil and Gas

## Overview
A comprehensive staff management system for Federal Polytechnic of Oil and Gas featuring CSV-based pre-verification, multi-role authentication (Admin User and Super Admin), and a complete employee registration workflow with memo communication system.

## Project Architecture

### Technology Stack
- **Frontend**: HTML5, Tailwind CSS (built-in compilation), jQuery 3.x, jQuery UI, FontAwesome
- **Backend**: PHP 8.2, PostgreSQL (Replit) / MySQL (Local XAMPP)
- **Deployment**: WordPress-ready (custom plugin structure)

### Directory Structure
```
/
├── assets/
│   ├── css/          # Compiled Tailwind CSS and custom styles
│   ├── js/           # JavaScript files (jQuery, custom scripts)
│   └── images/       # Logo, slideshow images, profile pictures
├── includes/
│   ├── head.php      # Dashboard header with collapsible sidebar
│   ├── foot.php      # Dashboard footer
│   ├── db.php        # Database connection
│   ├── session.php   # Session management
│   ├── functions.php # Helper functions
│   └── blur_detection.php # Image blur detection for memos
├── api/              # REST API endpoints
├── uploads/          # File upload directories
│   ├── csv/          # CSV file uploads
│   ├── memos/        # Memo attachments
│   ├── passport/     # Registration photos
│   └── profile_pictures/ # Admin profile photos
├── config/
│   └── config.php    # Configuration settings
├── public/           # Public-facing pages
└── [PHP pages]       # Main application files
```

### Database Schema

**Core Tables:**
1. `users` - Super Admin users
2. `admin_users` - Registered Admin users with full profiles
3. `admin_roles` - Admin role assignments
4. `pre_users` - CSV uploaded data for pre-verification
5. `memos` - Memo records with file attachments
6. `memo_recipients` - Memo recipient tracking and read status
7. `sessions` - User session management

### Key Features

#### 1. Authentication & Authorization
- Role-based access control (Super Admin, Admin)
- Session management with security
- Multi-step admin registration
- Position/role-based permissions (Rector, Bursar, Registrar, Establishment Unit)

#### 2. Staff Management
- CSV-based pre-verification system
- Complete admin user management (CRUD)
- User profile editing and management
- Search, filter, and sort capabilities
- CSV export of user data (authorized roles only)

#### 3. Memo Communication System
- Upload memos (Images, PDFs, Word documents)
- Send to all staff or specific users
- Automatic blur detection for image quality
- Text preview for Word documents
- Memo history with filtering
- Read/unread status tracking
- Mark memos as archived

#### 4. Dashboard & UI
- Dark/light theme support
- Responsive design (mobile, tablet, desktop)
- Collapsible sidebar navigation
- Profile picture uploads
- Real-time user counts and statistics

## Recent Changes (Build Session - Nov 2025)

### Memo System Enhancements
- ✅ Added text preview for Word documents (.docx files)
- ✅ Implemented memo preview page with image/PDF/document viewing
- ✅ Created memo history page with advanced filtering
- ✅ Fixed blur detection for image quality assurance
- ✅ Removed duplicate memos using DISTINCT ON queries
- ✅ Separated unread-only inbox from full memo history

### Access Control Expansion
- ✅ Extended CSV export to authorized admin roles: Rector, Bursar, Registrar, Establishment Unit
- ✅ Added Manage Users access for specified roles
- ✅ Implemented role-based sidebar navigation

### Bug Fixes
- ✅ Fixed PHP float-to-int deprecation warnings in blur detection
- ✅ Fixed undefined array key errors in memo pages
- ✅ Fixed duplicate memo displays across admin users
- ✅ Fixed admin session variables (admin_id setting)

## User Preferences
- Built-in Tailwind CSS compilation (no CDN)
- Modular PHP structure (head.php/foot.php)
- WordPress-compatible architecture
- Role-based feature access
- Clean, organized codebase

## Development Notes

### Local Setup (XAMPP)
- See `SETUP_LOCAL_XAMPP.md` for detailed installation steps
- Database export: `fpog_database.sql` (PostgreSQL format)
- For MySQL: Import the SQL file via phpMyAdmin

### Environment Variables (Replit)
- `DATABASE_URL` - Database connection string
- `SESSION_SECRET` - Session encryption secret
- Database credentials (PGHOST, PGUSER, PGPASSWORD, etc.)

### CSV Export Features
- Super Admin: Full access to export all users
- Authorized Admin Roles: Rector, Bursar, Registrar, Establishment Unit
- Exports include: Names, Staff ID, Email, Position, Department, Contact, Bank details, NOK info, Employment details

### Memo System Details
- Files stored in: `uploads/memos/`
- Blur detection uses Laplacian variance algorithm
- Supports: JPG, PNG, PDF, DOC, DOCX (max 10MB)
- Text extraction for DOCX via ZIP archive parsing
- Automatic memo status tracking

## Technologies & Libraries
- jQuery 3.6.4 for DOM manipulation
- jQuery UI for enhanced UI components
- FontAwesome 6.4.0 for icons
- Tailwind CSS for styling
- PDO for database abstraction
- Bootstrap-inspired responsive grid

## Deployment

### Replit (Current)
- Uses PostgreSQL database
- PHP 8.2 development server
- Automatic workflow management

### Local XAMPP
- MySQL/MariaDB database
- Apache web server
- See setup guide for instructions

### Production
- Use HTTPS
- Implement proper SSL certificates
- Regular database backups
- Monitor file upload directory permissions
- Use environment variables for secrets

## Security Considerations
- Password hashing with PHP password_hash()
- Session regeneration on login
- XSS protection via htmlspecialchars()
- SQL injection prevention via prepared statements
- File upload validation and type checking
- Authorization checks on all sensitive operations

## Common Tasks

### Adding a New Admin Role
1. Update `$allowed_roles` array in relevant PHP files
2. Update sidebar navigation checks in `includes/head.php`
3. Test access controls

### Backing Up Database
```bash
# PostgreSQL (Replit)
pg_dump -h $PGHOST -U $PGUSER -d $PGDATABASE > backup.sql

# MySQL (Local)
mysqldump -u root -p fpog_sms > backup.sql
```

### Exporting Users to CSV
- Super Admin: Click "Export" on Manage Users page
- Authorized roles: Same access if position matches allowed roles

### Managing Memos
- Upload: Super Admin only
- View: Admin users receive them
- Archive: Available in Memo History
- Search/Filter: By date, type, read status

## Version History
- **v1.0** (2025-11-21): Initial complete system deployment
- **v1.1** (2025-11-21): Memo system enhancements & role-based access

## Support & Maintenance
- Regular backups recommended
- Monitor file upload directory
- Review session timeout settings
- Update dependencies periodically

---

**Last Updated:** November 21, 2025
**Status:** Production Ready
**Database:** PostgreSQL (Replit) / MySQL (Local)
