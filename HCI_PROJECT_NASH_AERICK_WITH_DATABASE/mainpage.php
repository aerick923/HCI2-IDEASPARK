<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: index.php");

$host = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "ideaspark_db";
$conn = new mysqli($host, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $category = $conn->real_escape_string($_POST['category']);
    $date = $_POST['ideaDate'] ?: date("Y-m-d H:i:s");
    $user_id = $_SESSION['user_id'];

    // INSERT with pinned = 0 explicitly
    $conn->query("INSERT INTO ideas (user_id, title, description, category, date_created, pinned) 
                  VALUES ('$user_id', '$title', '$description', '$category', '$date', 0)");
    $success = true;
}

$profilePhoto = $_SESSION['profile_photo'] ?? 'IMAGES/default-avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Main Page | IdeaSpark</title>
<link rel="stylesheet" href="mainpage.css">
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
        <header class="hero">
            <div class="header-overlay">
                <div class="user-info">
                    <i class="fa-solid fa-bell"></i>
                    <img src="<?= htmlspecialchars($profilePhoto) ?>" alt="User Avatar" class="user-avatar" id="mainAvatar">
                </div>
            </div>
            <div class="hero-text">
                <h1>IDEAS<br>ARE SEED</h1>
            </div>
        </header>

        <section class="idea-section">
            <div class="idea-input-card">
                <p class="subtitle">Let the Idea Flow</p>
                <?php if (isset($success) && $success) echo "<p style='color: green; font-weight:bold;'>✅ Your idea has been submitted successfully!</p>"; ?>
                <form action="mainpage.php" method="POST">
                    <input type="text" name="title" placeholder="Enter a title..." required>
                    <textarea name="description" placeholder="Write your idea here..." required></textarea>
                    <input type="date" name="ideaDate">
                    <select name="category">
                        <option value="music">Music</option>
                        <option value="work">Work</option>
                        <option value="personal">Personal</option>
                        <option value="hobby">Hobby</option>
                    </select>
                    <button type="submit" class="submit-button">Submit</button>
                </form>
            </div>

            <div class="idea-image">
                <h2>INPUT YOUR DAILY IDEAS</h2>
                <img src="IMAGES/lightbulb.gif" alt="Lightbulb Graphic">
            </div>
        </section>

        <footer class="footer">
            <p>&copy; 2025 IdeaSpark Inc. All rights reserved.</p>
        </footer>
    </main>
</div>

<script>
const hamburger = document.getElementById("hamburger-icon");
const sidebar = document.getElementById("sidebar");
const mainContent = document.querySelector(".main-content");

hamburger.addEventListener("click", () => {
    sidebar.classList.toggle("sidebar-active");
    mainContent.classList.toggle("shifted");
});

// Highlight current page
document.querySelectorAll(".sidebar-nav a").forEach(link => {
    if(link.getAttribute("href") === window.location.pathname.split("/").pop()){
        link.parentElement.classList.add("active-link");
    }
});
</script>
</body>
</html>
