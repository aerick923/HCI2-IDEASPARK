<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database config
$host = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "ideaspark_db";

$conn = new mysqli($host, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = $_SESSION['user_id'];

// Fetch current user info
$result = $conn->query("SELECT * FROM users WHERE id=$user_id");
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name  = $conn->real_escape_string($_POST['last_name']);
    $email      = $conn->real_escape_string($_POST['email']);
    $address    = $conn->real_escape_string($_POST['address']);
    $phone      = $conn->real_escape_string($_POST['contactNumber']);
    $password   = $_POST['password'];

    // -------------------------
    // PROFILE PHOTO HANDLING
    // -------------------------
    $profile_photo_sql = '';
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['profile_photo']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['profile_photo']['name']);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmp, $targetFile)) {
            $relativePath = "uploads/$fileName";
            $profile_photo_sql = ", profile_photo='$relativePath'";
            // Update session immediately so other pages see new photo
            $_SESSION['profile_photo'] = $relativePath;
            $user['profile_photo'] = $relativePath;
        } else {
            $error = "Failed to upload profile photo.";
        }
    } else {
        // keep existing photo if any
        if (!empty($user['profile_photo'])) {
            $profile_photo_sql = ", profile_photo='" . $user['profile_photo'] . "'";
            $_SESSION['profile_photo'] = $user['profile_photo'];
        } else {
            $_SESSION['profile_photo'] = 'IMAGES/default-avatar.png';
        }
    }

    // -------------------------
    // PASSWORD HANDLING
    // -------------------------
    $password_sql = '';
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $password_sql = ", password='$hashedPassword'";
    }

    // -------------------------
    // EMAIL CHECK
    // -------------------------
    $check = $conn->query("SELECT * FROM users WHERE email='$email' AND id != $user_id");
    if ($check->num_rows > 0) {
        $error = "Email already in use!";
    } else {
        // UPDATE USER
        $conn->query("UPDATE users SET
            first_name='$first_name',
            last_name='$last_name',
            email='$email',
            address='$address',
            phone='$phone'
            $password_sql
            $profile_photo_sql
            WHERE id=$user_id");

        // update session
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name']  = $last_name;
        $_SESSION['email']      = $email;

        $success = "Profile updated successfully!";
    }

    // Refresh user data
    $result = $conn->query("SELECT * FROM users WHERE id=$user_id");
    $user = $result->fetch_assoc();
}

$conn->close();

// -------------------------
// PROFILE PHOTO DISPLAY
// -------------------------
// Use session first to reflect instant update
$profilePhoto = isset($_SESSION['profile_photo']) ? $_SESSION['profile_photo'] : 'IMAGES/default-avatar.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile | IdeaSpark</title>
<link rel="stylesheet" href="editProfile.css">
<link rel="stylesheet" href="sidebar.css">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="page-wrapper">
    <span class="hamburger-icon" id="hamburger-icon">&#9776;</span>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-nav-wrapper">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="editProfile.php" title="Profile"><img src="IMAGES/Navbar/profile.png" alt="Profile"></a></li>
                    <li><a href="mainpage.php" title="Add Idea"><img src="IMAGES/Navbar/addidea.png" alt="Add Idea"></a></li>
                    <li><a href="viewIdeaList.php" title="View Idea List"><img src="IMAGES/Navbar/list.png" alt="View Idea List"></a></li>
                    <li><a href="searchIdeas.php" title="Search Idea"><img src="IMAGES/Navbar/search.png" alt="Search Idea"></a></li>
                </ul>
            </nav>
            <div class="sidebar-bottom">
                <a href="settings.php" title="Settings"><img src="IMAGES/Navbar/settings.png" alt="Settings"></a>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <h1>Edit Profile</h1>
            <div class="user-profile-header">
                <img src="<?= htmlspecialchars($profilePhoto) . '?t=' . time() ?>" alt="User Avatar" class="user-avatar-header" id="profileAvatar">
                <input type="file" id="profileInput" name="profile_photo" accept="image/*" style="display:none">
                <button type="button" class="btn btn-change-profile" id="changeProfileBtn">Change Photo</button>
            </div>
        </header>

        <section class="profile-form-section">
            <?php if (isset($error)) echo "<div class='toast toast-error show'>" . htmlspecialchars($error) . "</div>"; ?>
            <?php if (isset($success)) echo "<div class='toast toast-success show'>" . htmlspecialchars($success) . "</div>"; ?>

            <form method="POST" action="editProfile.php" enctype="multipart/form-data">
                <div class="form-group-row">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-with-icon">
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        <i class="far fa-envelope input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?= htmlspecialchars($user['address']) ?>">
                </div>

                <div class="form-group">
                    <label for="contactNumber">Phone Number</label>
                    <input type="tel" id="contactNumber" name="contactNumber" value="<?= htmlspecialchars($user['phone']) ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password (leave blank to keep current)</label>
                    <div class="input-with-icon">
                        <input type="password" id="password" name="password" placeholder="Enter new password">
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="mainpage.php" class="btn btn-cancel">CANCEL</a>
                    <button type="submit" class="btn btn-save">SAVE</button>
                </div>
            </form>
        </section>
    </main>
</div>

<script>
const hamburger = document.getElementById('hamburger-icon');
const sidebar = document.getElementById('sidebar');
const mainContent = document.querySelector('.main-content');

hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('sidebar-active');
    mainContent.classList.toggle('shifted');
});

// Highlight current page
const currentPage = window.location.pathname.split("/").pop();
document.querySelectorAll('.sidebar-nav a').forEach(link => {
    if(link.getAttribute('href') === currentPage){
        link.parentElement.classList.add('active-link');
    }
});

// Profile picture handling
const profileInput = document.getElementById('profileInput');
const profileAvatar = document.getElementById('profileAvatar');
const changeProfileBtn = document.getElementById('changeProfileBtn');

changeProfileBtn.addEventListener('click', () => profileInput.click());

profileInput.addEventListener('change', () => {
    const file = profileInput.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(e){
            profileAvatar.src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>
