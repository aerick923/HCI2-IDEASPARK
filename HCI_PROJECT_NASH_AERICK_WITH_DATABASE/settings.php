<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: index.php");

// Use user's profile photo if available
$profilePhoto = $_SESSION['profile_photo'] ?? 'IMAGES/default-avatar.png';

// Handle logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings | IdeaSpark</title>
<link rel="stylesheet" href="settings.css">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="page-wrapper">
    <span class="hamburger-icon" id="hamburger-icon">&#9776;</span>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-nav-wrapper">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="editProfile.php"><img src="IMAGES/Navbar/profile.png" alt="Profile"></a></li>
                    <li><a href="mainpage.php"><img src="IMAGES/Navbar/addidea.png" alt="Add Idea"></a></li>
                    <li><a href="viewIdeaList.php"><img src="IMAGES/Navbar/list.png" alt="View Idea List"></a></li>
                    <li><a href="searchIdeas.php"><img src="IMAGES/Navbar/search.png" alt="Search Idea"></a></li>
                </ul>
            </nav>
            <div class="sidebar-bottom">
                <a href="settings.php"><img src="IMAGES/Navbar/settings.png" alt="Settings"></a>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <header class="hero">
            <h1>SETTINGS</h1>
            <div class="user-info">
                <img src="<?= htmlspecialchars($profilePhoto) ?>" alt="User Avatar" class="user-avatar" id="mainAvatar">
            </div>
        </header>

        <section class="settings-section">
            <!-- About Us -->
            <div class="settings-card">
                <h2>About Us</h2>
                <p>IdeaSpark is a creative notepad app designed to help you capture, organize, and explore your ideas. Built by a passionate team of beginners learning web development.</p>
            </div>

            <!-- Report a Problem -->
            <div class="settings-card">
                <h2>Report a Problem</h2>
                <form id="reportForm">
                    <textarea id="reportText" placeholder="Describe your issue..." required></textarea>
                    <button type="submit">Submit</button>
                </form>
            </div>

            <!-- Logout -->
            <div class="settings-card">
                <h2>Log Out</h2>
                <form method="POST"><button name="logout">Log Out</button></form>
            </div>
        </section>
    </main>
</div>

<script>
// Hamburger toggle
const hamburger = document.getElementById('hamburger-icon');
const sidebar = document.getElementById('sidebar');
const mainContent = document.querySelector('.main-content');
hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('sidebar-active');
    mainContent.classList.toggle('shifted');
});

// Highlight active page
document.querySelectorAll(".sidebar-nav a").forEach(link => {
    if (link.getAttribute("href") === window.location.pathname.split("/").pop()) {
        link.parentElement.classList.add('active-link');
    }
});

// Report form submission
const reportForm = document.getElementById('reportForm');
reportForm.addEventListener('submit', e => {
    e.preventDefault();
    const text = document.getElementById('reportText').value.trim();
    if (!text) { alert("Please write something."); return; }

    let reports = JSON.parse(localStorage.getItem("reports")) || [];
    reports.push({ text, date: new Date().toLocaleString() });
    localStorage.setItem("reports", JSON.stringify(reports));
    
    alert("Thank you! Your report has been submitted.");
    reportForm.reset();
});
</script>
</body>
</html>
