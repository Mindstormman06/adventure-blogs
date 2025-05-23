# Adventure Blog

Adventure Blog is a simple, customizable, and secure blog platform that allows users to create and share posts. It includes features like user registration, authentication, post creation, email verification for account updates, and more. This project is built using PHP, MySQL, and other front-end technologies.

## Features

- **User Authentication**: Secure login, registration, password hashing, and account verification.
- **Post Creation**: Users can create posts with text, images, audio, video, and location.
- **Email Verification**: Users must verify their email address before updating it.
- **Profile Management**: Users can view and update their profile information.
- **Admin Controls**: Admins can manage user roles and delete posts.

## Requirements

- **PHP** (version 7.4 or higher)
- **MySQL** (or MariaDB)
- **Apache/Nginx** for local or live hosting
- **PHPMailer** for email functionality (used for sending verification emails)
- **Parsedown** for Markdown support

## Installation

### 1. Clone the Repository

Clone this repository to your local machine:

```bash
git clone https://github.com/Mindstormman06/adventure-blogs.git
cd adventure-blog
```

### 2. Install requirements

```bash
composer install
```

### 3. Set Up the Database

Create a new database in MySQL and import the SQL schema:

```sql
CREATE DATABASE adventure_blog;
```

Then, import the provided SQL file (`database_schema.sql`) or manually create the necessary tables.

### 4. Configure Database Settings

Create `config.php` in the project folder and configure your database connection details:

```php
<?php
$host = 'localhost';
$dbname = 'example';
$username = 'example';
$password = 'example';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
```

### 5. Configure Email

For email functionality (password resets, email verification), create `email_config.php` configure your email settings.

```php
    'smtp_host' => 'smtp.gmail.com',
    'smtp_username' => 'example@example.com',
    'smtp_password' => 'exam plpa sswo rd01', // 16-character app password
    'smtp_port' => 587,
    'from_email' => 'example@example.com',
    'from_name' => 'Example Verifier',
```

### 6. Set Up Apache or Nginx

Ensure your web server is configured to serve PHP files correctly. If you're using Apache, ensure `mod_rewrite` is enabled for clean URLs.

### 7. Set Permissions

Make sure that all necessary files have appropriate read/write permissions for the web server.

### 8. Start Using the Blog

Once you've set everything up, you can start the blog on your local server or deploy it live. The blog should be accessible via your local server's URL (`localhost` or similar).

## Development

To contribute to the project or make your own improvements, follow these steps:

1. Fork the repository.
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/adventure-blog.git
   ```
3. Create a new branch for your feature or bug fix:
   ```bash
   git checkout -b feature/your-feature
   ```
4. Make your changes, then commit them:
   ```bash
   git add .
   git commit -m "Description of the changes"
   ```
5. Push your changes to your fork:
   ```bash
   git push origin feature/your-feature
   ```
6. Submit a pull request with a description of the changes you made.

## License

This project is licensed under the GPL-3.0 license - see the [LICENSE](LICENSE) file for details.

---

