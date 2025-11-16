<?php
session_start();

// Database configuration
$host = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "ideaspark_db";

// Create connection
$conn = new mysqli($host, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process signup form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    // Check password match
    if ($password !== $confirm) {
        echo "<script>alert('Passwords do not match.'); window.location='signup.php';</script>";
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already registered.'); window.location='signup.php';</script>";
        exit();
    }
    $stmt->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $first_name, $last_name, $email, $hashedPassword);

    if ($stmt->execute()) {
        echo "<script>alert('Signup successful! Please login.'); window.location='index.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
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
  <title>Sign Up | IdeaSpark</title>
  <link rel="stylesheet" href="signup.css">
</head>
<body>
  <div class="auth-container reverse">
    <div class="auth-form">
      <h2>Create Account</h2>
      <p class="subtitle">Sign up to get started</p>

      <form action="signup.php" method="POST">
        <label for="firstname">First Name</label>
        <input type="text" name="first_name" id="firstname" placeholder="Enter your first name" required>

        <label for="lastname">Last Name</label>
        <input type="text" name="last_name" id="lastname" placeholder="Enter your last name" required>

        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="Enter your email" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter your password" required>

        <label for="confirm">Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm" placeholder="Confirm your password" required>

        <button type="submit">Sign Up</button>

        <p class="switch">Already have an account?
          <a href="index.php">Login</a>
        </p>
      </form>
    </div>

    <div class="auth-image">
      <img src="IMAGES/logo.png" alt="Sign up illustration">
    </div>
  </div>
</body>
</html>
