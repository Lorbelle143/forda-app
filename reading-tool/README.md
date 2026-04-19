# ReadEase - PHP + MySQL Reading Tool

A complete web application for reading practice with Text-to-Speech, voice recording, and teacher feedback.

## Features

- **Text-to-Speech** with word-by-word highlighting (Web Speech API)
- **Voice Recording** using MediaRecorder API
- **Teacher Feedback** system for submitted recordings
- **Role-based access** (Admin / Student)
- **Leveled reading materials** (Beginner / Intermediate / Advanced)
- Responsive, mobile-friendly design

## Requirements

- PHP 8.0+ (or PHP 7.4+ with minor adjustments)
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite (XAMPP recommended)
- Modern browser (Chrome/Edge recommended for TTS + recording)

## Setup Instructions

### 1. Install XAMPP

Download and install [XAMPP](https://www.apachefriends.org/) for your OS.

### 2. Copy Files

Copy the `reading-tool/` folder to your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\reading-tool\   (Windows)
/opt/lampp/htdocs/reading-tool/ (Linux)
/Applications/XAMPP/htdocs/reading-tool/ (macOS)
```

### 3. Create the Database

1. Start Apache and MySQL in XAMPP Control Panel
2. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
3. Click **Import** tab
4. Choose `reading-tool/database.sql`
5. Click **Go**

Or run via command line:
```bash
mysql -u root -p < database.sql
```

### 4. Configure Database Connection

Edit `includes/db.php` if your MySQL credentials differ:

```php
$host     = 'localhost';
$dbname   = 'reading_tool';
$username = 'root';
$password = '';  // Change if you have a MySQL password
```

### 5. Set Folder Permissions

Ensure the uploads folder is writable:

```bash
chmod 755 uploads/recordings/
```

On Windows/XAMPP this is usually automatic.

### 6. Create Admin Accounts

Visit: [http://localhost/reading-tool/setup_admins.php](http://localhost/reading-tool/setup_admins.php)

This creates all 5 admin accounts with proper password hashing. **Delete the file after running it.**

### 7. Open the App

Visit: [http://localhost/reading-tool/](http://localhost/reading-tool/)

## Default Accounts

Run `setup_admins.php` after importing the database to create the 5 admin accounts.

| Role    | Email                      | Password      |
|---------|----------------------------|---------------|
| Admin 1 | lorbelleganzan@gmail.com   | Admin@Group1  |
| Admin 2 | admin.group2@gmail.com     | Admin@Group2  |
| Admin 3 | admin.group3@gmail.com     | Admin@Group3  |
| Admin 4 | admin.group4@gmail.com     | Admin@Group4  |
| Admin 5 | admin.group5@gmail.com     | Admin@Group5  |
| Student | student@gmail.com          | student123    |

> You can change the names, emails, and passwords inside `setup_admins.php` before running it.
> **Delete `setup_admins.php` after running it.**

## Project Structure

```
reading-tool/
├── index.php                  Landing page
├── login.php                  Login & Register (tabbed)
├── logout.php                 Session logout
├── dashboard.php              Student dashboard
├── admin/
│   ├── dashboard.php          Admin stats & overview
│   ├── materials.php          Add/Edit/Delete reading materials
│   ├── recordings.php         Review recordings & give feedback
│   └── users.php              Manage user accounts & roles
├── student/
│   ├── read.php               Reading page with TTS + recording
│   ├── my-recordings.php      Student's recordings & feedback
│   └── submit_recording.php   API endpoint for audio upload
├── includes/
│   ├── db.php                 PDO database connection
│   ├── auth.php               Session & auth helpers
│   ├── header.php             Shared HTML header + nav
│   └── footer.php             Shared HTML footer
├── assets/
│   ├── css/style.css          All styles (no external CDN)
│   └── js/app.js              TTS, recording, UI logic
├── uploads/
│   └── recordings/            Uploaded audio files
└── database.sql               Full schema + sample data
```

## Browser Compatibility

| Feature         | Chrome | Firefox | Edge | Safari |
|-----------------|--------|---------|------|--------|
| Text-to-Speech  | ✅     | ✅      | ✅   | ✅     |
| Word Highlight  | ✅     | ⚠️*     | ✅   | ⚠️*    |
| Audio Recording | ✅     | ✅      | ✅   | ✅     |

*Firefox/Safari may not support `onboundary` event for word highlighting, but TTS still works.

## Deploying to 000webhost / Shared Hosting

1. Upload all files via FTP or File Manager
2. Create a MySQL database in your hosting control panel
3. Import `database.sql` via phpMyAdmin
4. Update `includes/db.php` with your hosting DB credentials
5. Ensure `uploads/recordings/` is writable (chmod 755)

## Security Notes

- All DB queries use PDO prepared statements
- Passwords hashed with `password_hash()` (bcrypt)
- File uploads validated by MIME type
- PHP execution blocked in uploads folder via `.htaccess`
- XSS prevention with `htmlspecialchars()` throughout
- Session regeneration on login

## License

MIT License — free to use and modify.
