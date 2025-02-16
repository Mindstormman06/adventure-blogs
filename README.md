# Adventure Blog

Adventure Blog is a simple, customizable, and secure blog platform that allows users to create and share posts. It includes features like user registration, authentication, post creation, email verification for account updates, and more. This project is built using PHP, MySQL, and other front-end technologies.

## Features

- **User Authentication**: Secure login, registration, and password hashing.
- **Post Creation**: Users can create posts with text, images, audio, and video.
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
git clone https://github.com/Mindstormman06/adventure-blog.git
cd adventure-blog
```

### 2. Set Up the Database

Create a new database in MySQL and import the SQL schema:

```sql
CREATE DATABASE adventure_blog;
```

Then, import the provided SQL file (`database_schema.sql`) or manually create the necessary tables.

### 3. Configure Database Settings

Open `config.php` and configure your database connection details:

```php
<?php
$host = 'localhost';   // Database host
$dbname = 'adventure_blog';  // Database name
$username = 'root';     // Database username
$password = '';         // Database password
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// Set the PDO instance for database connection
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, $username, $password, $options);
?>
```

### 4. Configure Email

For email functionality (password resets, email verification), configure your email settings in `email_config.php`, and update the imports at the top of `update_profile.php` & `register.php` to use `email_config.php`

```php
    'smtp_host' => 'smtp.gmail.com',
    'smtp_username' => 'example@example.com',
    'smtp_password' => 'exam plpa sswo rd01', // 16-character app password
    'smtp_port' => 587,
    'from_email' => 'example@example.com',
    'from_name' => 'Example Verifier',
```

### 5. Set Up Apache or Nginx

Ensure your web server is configured to serve PHP files correctly. If you're using Apache, ensure `mod_rewrite` is enabled for clean URLs.

### 6. Set Permissions

Make sure that all necessary files have appropriate read/write permissions for the web server.

### 7. Start Using the Blog

Once you've set everything up, you can start the blog on your local server or deploy it live. The blog should be accessible via your local server's URL (`localhost` or similar).

## Usage

- **Login**: Users can log in with their registered username and password.
- **Create Posts**: After logging in, users can create posts with images, videos, and text.
- **Edit Profile**: Users can update their profile information (email, username, password).
- **Verify Email**: When updating the email address, users will receive a verification email.
- **Admin Controls**: Admins can manage posts and user roles through the admin interface.

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

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

### Tips for Customizing:

- Feel free to update the `config.php` to match your server and database configuration.
- Customize the CSS and HTML structure to fit your design preferences.
- Add any additional features or integrations you'd like, such as a comment system, tags, or search functionality.

---

Feel free to adjust the instructions and descriptions to match the specifics of your project, such as the database schema, email configuration, and any extra features you might have added.
