<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: mainpage.php");
    exit();
}

// Database configuration
$host = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "ideaspark_db";

// Create connection
$conn = new mysqli($host, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];

            header("Location: mainpage.php");
            exit();
        } else {
            echo "<script>alert('Incorrect password.'); window.location='index.php';</script>";
        }
    } else {
        echo "<script>alert('No user found with this email.'); window.location='index.php';</script>";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | IdeaSpark</title>
<link rel="stylesheet" href="login.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-image">
        <img src="IMAGES/Logo.png" alt="Logo" class="logo" />
    </div>

    <div class="auth-form">
        <h2>Welcome Back!</h2>
        <p class="subtitle">Login to continue</p>

        <form action="index.php" method="POST">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="Enter your email" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required>

            <button type="submit">Login</button>

            <p class="switch">Don’t have an account? 
                <a href="signup.php">Sign up</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>
