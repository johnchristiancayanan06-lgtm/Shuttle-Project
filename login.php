<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EASTWEST Shuttle - Login</title>
    <link rel="icon" href="styles/logo.webp" type="image/webp">
    <style>
        body {
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; margin: 0; font-family: Arial, sans-serif;
            background: url('styles/bg.webp') no-repeat center center/cover;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95); padding: 2rem;
            border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            width: 350px; text-align: center;
        }
        .company-name { font-weight: bold; margin-top: 10px; color: #333; font-size: 1.2rem; }
        .form-group { text-align: left; margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #555; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .login-btn { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="styles/logo.webp" alt="Logo" style="width:80px;">
        <div class="company-name">EASTWEST SHUTTLE SYSTEM</div>
        <h2>Login</h2>
        <?php if(isset($_GET['error'])): ?>
            <div class="error-message">Invalid Username or Password</div>
        <?php endif; ?>
<form action="login_process.php" method="POST">
    <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
    </div>

    <button type="submit" class="login-btn">LOGIN</button>
</form>
    </div>
</body>
</html>
