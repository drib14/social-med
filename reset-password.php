<?php
session_start();
require_once 'config.php';

$token = null;
if (isset($_GET['token'])) {
    $token_raw = $conn->real_escape_string($_GET['token']);
    $token = urldecode($token_raw);
}

$validToken = false;
if ($token) {
    $sql = "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $validToken = true;
    } else {
        $_SESSION['error'] = "Invalid or expired reset token";
    }
} else {
    $_SESSION['error'] = "Invalid reset link";
    header("Location: forgot-password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || empty($_POST['token'])) {
        $_SESSION['error'] = "Invalid request.";
        header("Location: forgot-password.php");
        exit();
    }
    $token_post_raw = $conn->real_escape_string($_POST['token']);
    $token_post = urldecode($token_post_raw);

    if ($token !== $token_post) {
        $_SESSION['error'] = "Invalid token provided.";
    } else {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $_SESSION['error'] = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters long.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL
                    WHERE reset_token = ? AND reset_expires > NOW()";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $hashed_password, $token);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Password updated successfully. You can now <a href='login.php'>login</a>.";
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['error'] = "Error updating password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="auth-container">
        <h2>Reset Password</h2>
        <?php
        if (isset($_SESSION['error'])) {
            echo "<p class='error'>" . $_SESSION['error'] . "</p>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo "<p class='success'>" . $_SESSION['success'] . "</p>";
            unset($_SESSION['success']);
        }
        ?>
        <?php if ($validToken): ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                <div class="form-group">
                    <input type="password" name="password" placeholder="New Password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <button type="submit">Reset Password</button>
            </form>
        <?php elseif (isset($_SESSION['error'])): ?>
            <p>Please request a new password reset link.</p>
        <?php else: ?>
            <p>Invalid reset link. Please request a new password reset.</p>
        <?php endif; ?>
    </div>
</body>
</html>