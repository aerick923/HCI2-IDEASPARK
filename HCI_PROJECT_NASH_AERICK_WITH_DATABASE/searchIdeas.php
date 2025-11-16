<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: index.php");
$profilePhoto = $_SESSION['profile_photo'] ?? 'IMAGES/default-avatar.png';

// DB connection
$conn = new mysqli("localhost","root","","ideaspark_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM ideas WHERE user_id='$user_id' ORDER BY date_created DESC");

$ideas = [];
while ($row = $result->fetch_assoc()) $ideas[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Ideas | IdeaSpark</title>
<link rel="stylesheet" href="searchIdeas.css">
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

    <main class="main-content">
        <header class="header">
            <h1>SEARCH YOUR IDEAS</h1>
            <img src="<?= htmlspecialchars($profilePhoto) ?>" id="mainAvatar" alt="User Avatar" class="user-avatar">
        </header>

        <section class="search-section">
            <div class="search-box-wrapper">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="idea-search-input" placeholder="Search your ideas..." autocomplete="off">
                </div>
                <ul id="search-dropdown" class="search-dropdown"></ul>
            </div>
        </section>

        <section id="search-results" class="search-results">
            <p>Start typing above to search your ideas!</p>
        </section>

        <footer class="footer">
            <p>© 2025 IdeaSpark Inc. All rights reserved.</p>
        </footer>
    </main>
</div>

<script>
const hamburger = document.getElementById('hamburger-icon');
const sidebar = document.getElementById('sidebar');
const mainContent = document.querySelector('.main-content');
const mainAvatar = document.getElementById('mainAvatar');
hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('sidebar-active');
    mainContent.classList.toggle('shifted');
});
mainAvatar.src = localStorage.getItem("profileImage") || "IMAGES/default-avatar.png";
document.querySelectorAll(".sidebar-nav a").forEach(link => {
    if(link.getAttribute("href") === window.location.pathname.split("/").pop()) link.parentElement.classList.add("active-link");
});

const allIdeas = <?php echo json_encode($ideas); ?>;
const input = document.getElementById("idea-search-input");
const results = document.getElementById("search-results");
const dropdown = document.getElementById("search-dropdown");
let previousSearches = JSON.parse(localStorage.getItem("previousSearches")) || [];

function renderIdeas(list){
    if(!list.length) results.innerHTML="<p>No ideas found.</p>";
    else results.innerHTML = list.map(i=>`
        <div class="result-card ${i.pinned ? 'pinned' : ''}">
            <h3>${i.title}</h3>
            <p><strong>Category:</strong> ${i.category}</p>
            <p><strong>Date:</strong> ${i.date_created}</p>
            <p>${i.description}</p>
        </div>`).join("");
}

function searchIdeas(){
    const query = input.value.toLowerCase().trim();
    const filtered = allIdeas.filter(i=>i.title.toLowerCase().includes(query));
    renderIdeas(filtered);

    const filteredPrev = previousSearches.filter(p=>p.toLowerCase().includes(query));
    dropdown.innerHTML = filteredPrev.map(p=>`<li>${p}</li>`).join('');
    dropdown.style.display = filteredPrev.length ? 'block':'none';
}

input.addEventListener('input', searchIdeas);
input.addEventListener('keydown', e=>{
    if(e.key==='Enter'){
        const query = input.value.trim();
        if(query && !previousSearches.includes(query)){
            previousSearches.push(query);
            localStorage.setItem("previousSearches", JSON.stringify(previousSearches));
        }
        dropdown.style.display='none';
        searchIdeas();
        input.value='';
    }
});

dropdown.addEventListener('click', e=>{
    if(e.target.tagName==='LI'){
        input.value = e.target.textContent;
        searchIdeas();
        dropdown.style.display='none';
    }
});
</script>
</body>
</html>
