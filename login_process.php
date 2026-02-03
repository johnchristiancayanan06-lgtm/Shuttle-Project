<?php
session_start();

// 1. Database Connection
$conn = new mysqli("localhost", "root", "", "shuttle");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_input = trim($_POST['username']);
    $pass_input = trim($_POST['password']);

    // 2. Query the user
    $stmt = $conn->prepare("SELECT id, username, password, role FROM accounts WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $user_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        // 3. DIRECT COMPARISON (Plain Text)
        // This checks if the input matches exactly what is typed in the DB varchar column
        if ($pass_input === $row['password']) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role']; 
            
            header("Location: dashboard.php");
            exit();
        }
    }

    // 4. Failure: Redirect back with error
    header("Location: login.php?error=1");
    exit();
}
$conn->close();
?>