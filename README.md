# AdHub — Agency-Client Marketing Campaign Manager

A full PHP + Bootstrap 5 web application for managing marketing campaigns between agencies and their clients.

---

## Tech Stack

- **Backend**: PHP 8+ with PDO (MySQL)
- **Frontend**: Bootstrap 5 (CDN), custom CSS, vanilla JS
- **Database**: MySQL / MariaDB
- **Charts**: Chart.js (analytics page)

---

## Installation

### 1. Requirements
- Apache or Nginx with PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- `mod_rewrite` enabled (Apache)

### 2. Copy files
Place the `/adhub` folder inside your web server root:
```
/var/www/html/adhub/        (Linux/Apache)
C:\xampp\htdocs\adhub\      (XAMPP Windows)
/Applications/MAMP/htdocs/adhub/  (MAMP Mac)
```

### 3. Set up the database
```bash
mysql -u root -p < /path/to/adhub/setup.sql
```
Or import `setup.sql` via phpMyAdmin.

### 4. Configure the DB connection
Edit `/adhub/includes/db.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'adhub_db');
```

### 5. Set uploads permissions
```bash
chmod 755 /path/to/adhub/uploads/
```

### 6. Open in browser
```
http://localhost/adhub/
```

---

## Demo Login Credentials

| Role   | Email                | Password   |
|--------|----------------------|------------|
| Admin  | admin@adhub.com      | password   |
| Client | client@adhub.com     | password   |
| Client | sarah@techco.com     | password   |

---

## Folder Structure

```
/adhub
├── index.php               ← Entry point (redirects by role)
├── setup.sql               ← Database schema + seed data
├── .htaccess               ← Apache config (security, rewrites)
│
├── /assets
│   ├── /css/style.css      ← All custom styles (CSS variables, layout, components)
│   └── /js/script.js       ← All custom JS (sidebar, toasts, validation, etc.)
│
├── /includes
│   ├── db.php              ← PDO database connection
│   ├── header.php          ← HTML <head>, session start, auth guard
│   ├── navbar.php          ← Top navigation bar
│   ├── sidebar.php         ← Role-aware left sidebar
│   └── footer.php          ← Footer bar + JS loading
│
├── /auth
│   ├── login.php           ← Multi-role login form + logic
│   ├── register.php        ← Client registration
│   └── logout.php          ← Session destroy + redirect
│
├── /admin
│   ├── dashboard.php       ← Stats, recent campaigns, approvals
│   ├── campaigns.php       ← Full CRUD: create, edit, delete campaigns
│   ├── assets.php          ← File upload manager per campaign
│   ├── reports.php         ← Performance data entry + table
│   └── analytics.php       ← Charts: trends, spend, status donut
│
├── /client
│   ├── dashboard.php       ← Client overview: progress, pending approvals
│   ├── campaigns.php       ← Read-only campaign cards with progress
│   └── approvals.php       ← Approve or request revision on campaigns
│
└── /uploads                ← Uploaded asset files (auto-created)
    └── index.php           ← Blocks directory listing
```

---

## Features

### Admin
- Dashboard with live stats from database
- Campaign CRUD (modal-based, no page reload)
- Asset upload with drag-and-drop
- Reports data entry and performance table
- Analytics with Chart.js (line, donut, bar charts)
- Role-based sidebar navigation

### Client
- Dashboard showing assigned campaigns + approval status
- Campaign cards with progress bars
- Approval page: approve or request revision with notes
- Pending approvals highlighted with warning border

### Auth
- Secure login with `password_verify()`
- Session regeneration on login
- Role-based redirects (admin → /admin/, client → /client/)
- Registration creates client accounts only

---

## Security Notes
- Passwords hashed with `PASSWORD_DEFAULT` (bcrypt)
- All user input sanitized with `htmlspecialchars()`
- PDO prepared statements throughout (no SQL injection)
- Session ID regenerated on login
- Uploads directory blocks directory listing
- `.htaccess` blocks access to `/includes/` and sensitive files

---

## Customization
- **Colors / branding**: Edit CSS variables at the top of `assets/css/style.css`
- **Add pages**: Copy a page file, set `$page_title` and `$require_role`, include sidebar/navbar/footer
- **DB connection**: Only `includes/db.php` needs changing for different credentials
