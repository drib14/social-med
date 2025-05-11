<?php
session_start();
require_once 'config.php';
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);

    $check_email_sql = "SELECT id FROM users WHERE email = ?";
    $check_email_stmt = $conn->prepare($check_email_sql);
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $check_email_result = $check_email_stmt->get_result();

    if ($check_email_result->num_rows > 0) {
        $token = bin2hex(random_bytes(64));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sql = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $token, $expires, $email);

        if ($stmt->execute()) {
            $mail = new PHPMailer(); // Instantiate without namespace

            try {
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = 'tls'; // Use 'tls' for older versions if STARTTLS isn't fully supported
                $mail->Port = SMTP_PORT;
                $mail->setFrom(SMTP_USER, 'Your Social Media');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $resetLink = "http://localhost:8000/reset-password.php?token=" . urlencode($token);
                $mail->Body = "Click the link below to reset your password:<br><a href='" . $resetLink . "'>Reset Password</a><br>This link will expire in 1 hour.";
                $mail->send();
                $success = "Reset instructions sent to your email";
            } catch (Exception $e) { // Use the global Exception class
                $error = "Error sending email: " . $mail->ErrorInfo;
            }
        } else {
            $error = "Database error updating reset token.";
        }
    } else {
        $success = "Reset instructions sent to your email if the account exists.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="auth-container">
        <h2>Forgot Password</h2>
        <?php
        if (isset($error)) echo "<p class='error'>$error</p>";
        if (isset($success)) echo "<p class='success'>$success</p>";
        ?>
        <form method="POST" action="">
            <div class="form-group">
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit">Send Reset Link</button>
        </form>
        <p><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>