<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'social_media');

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'jhondribramirez7@gmail.com');
define('SMTP_PASS', 'rjnl hzsg lxar rxcy');
define('SMTP_PORT', 587);

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
