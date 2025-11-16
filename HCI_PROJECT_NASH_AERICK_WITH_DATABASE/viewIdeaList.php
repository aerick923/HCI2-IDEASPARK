<?php
session_start();

$profilePhoto = $_SESSION['profile_photo'] ?? 'IMAGES/default-avatar.png';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$host = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "ideaspark_db";
$conn = new mysqli($host, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// AJAX requests
if (isset($_POST['action'])) {
    $idea_id = intval($_POST['idea_id']);
    $user_id = $_SESSION['user_id'];

    if ($_POST['action'] === 'pin') {
        $current = $conn->query("SELECT pinned FROM ideas WHERE id=$idea_id AND user_id=$user_id")->fetch_assoc();
        $newStatus = $current['pinned'] ? 0 : 1;
        $conn->query("UPDATE ideas SET pinned=$newStatus WHERE id=$idea_id AND user_id=$user_id");
        echo json_encode(['pinned' => $newStatus]);
        exit();
    }

    if ($_POST['action'] === 'delete') {
        $conn->query("DELETE FROM ideas WHERE id=$idea_id AND user_id=$user_id");
        echo json_encode(['deleted' => true]);
        exit();
    }

    if ($_POST['action'] === 'edit') {
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $category = $conn->real_escape_string($_POST['category']);
        $conn->query("UPDATE ideas SET title='$title', description='$description', category='$category' WHERE id=$idea_id AND user_id=$user_id");
        echo json_encode(['updated' => true]);
        exit();
    }
}

// Fetch ideas
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM ideas WHERE user_id='$user_id'");
$ideas = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['pinned'] = (int)$row['pinned']; // cast
        $row['date_created'] = date('c', strtotime($row['date_created'])); // ISO for JS
        $ideas[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Ideas | IdeaSpark</title>
<link rel="stylesheet" href="viewIdeaList.css">
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
            <h1 class="page-title">YOUR IDEAS</h1>
            <img src="<?= htmlspecialchars($profilePhoto) ?>" id="mainAvatar" alt="User Avatar" class="user-avatar">
        </header>

        <div class="filter-controls">
            <label for="categorySelect">Category:</label>
            <select id="categorySelect">
                <option value="all">All</option>
                <option value="music">Music</option>
                <option value="work">Work</option>
                <option value="personal">Personal</option>
                <option value="hobby">Hobby</option>
            </select>

            <label for="sortSelect">Sort by:</label>
            <select id="sortSelect">
                <option value="default">Default</option>
                <option value="date">Date</option>
                <option value="az">Title A–Z</option>
                <option value="za">Title Z–A</option>
                <option value="pinned">Pinned First</option>
            </select>
        </div>

        <div id="ideasContainer" class="ideas-container"></div>
        <footer class="footer">
            <p>© 2025 IdeaSpark Inc. All rights reserved.</p>
        </footer>
    </main>
</div>

<div id="modal-overlay" class="hidden">
    <div class="modal-box">
        <p id="modal-message"></p>
        <div class="modal-buttons">
            <button id="modal-confirm">Yes</button>
            <button id="modal-cancel">Cancel</button>
        </div>
    </div>
</div>

<script>
const ideasContainer = document.getElementById("ideasContainer");
const categorySelect = document.getElementById("categorySelect");
const sortSelect = document.getElementById("sortSelect");
const hamburger = document.getElementById("hamburger-icon");
const sidebar = document.getElementById("sidebar");
const mainContent = document.querySelector(".main-content");
const mainAvatar = document.getElementById("mainAvatar");
const modalOverlay = document.getElementById("modal-overlay");
const modalMessage = document.getElementById("modal-message");
const modalConfirm = document.getElementById("modal-confirm");
const modalCancel = document.getElementById("modal-cancel");

hamburger.addEventListener("click", () => {
    sidebar.classList.toggle("sidebar-active");
    mainContent.classList.toggle("shifted");
});

mainAvatar.src = localStorage.getItem("profileImage") || mainAvatar.src;

// PHP ideas
let ideas = <?php echo json_encode($ideas); ?>;
ideas = ideas.map(i => ({ ...i, pinned: Number(i.pinned) }));

function showToast(message) {
    const toast = document.createElement("div");
    toast.className = "toast";
    toast.innerText = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function renderIdeas() {
    ideasContainer.innerHTML = "";
    let filtered = [...ideas];

    if (categorySelect.value !== "all") {
        filtered = filtered.filter(i => i.category.toLowerCase() === categorySelect.value.toLowerCase());
    }

    // Sorting: pinned first, then newest date
    if(sortSelect.value === "date") {
        filtered.sort((a,b) => new Date(b.date_created) - new Date(a.date_created));
    } else if(sortSelect.value === "az") {
        filtered.sort((a,b) => a.title.localeCompare(b.title));
    } else if(sortSelect.value === "za") {
        filtered.sort((a,b) => b.title.localeCompare(a.title));
    } else if(sortSelect.value === "pinned") {
        filtered.sort((a,b) => (b.pinned - a.pinned) || (new Date(b.date_created) - new Date(a.date_created)));
    }

    if (!filtered.length) {
        ideasContainer.innerHTML = "<p>No ideas found.</p>";
        return;
    }

    filtered.forEach(idea => {
        const card = document.createElement("div");
        card.className = `idea-card ${idea.pinned ? 'pinned' : ''} ${idea.category.toLowerCase()}-bg`;
        card.innerHTML = `
            <h3 class="idea-title" contenteditable="false">${idea.title}</h3>
            <p class="idea-text" contenteditable="false">${idea.description}</p>
            <p><strong>Category:</strong>
                <select class="idea-category" disabled>
                    <option value="music" ${idea.category==='music'?'selected':''}>Music</option>
                    <option value="work" ${idea.category==='work'?'selected':''}>Work</option>
                    <option value="personal" ${idea.category==='personal'?'selected':''}>Personal</option>
                    <option value="hobby" ${idea.category==='hobby'?'selected':''}>Hobby</option>
                </select>
            </p>
            <p><strong>Date:</strong> ${new Date(idea.date_created).toLocaleString()}</p>
            <div class="button-group">
                <button class="pin-btn">${idea.pinned ? 'Unpin' : 'Pin'}</button>
                <button class="edit-btn">Edit</button>
                <button class="delete-btn">Delete</button>
            </div>
        `;

        const pinBtn = card.querySelector(".pin-btn");
        const editBtn = card.querySelector(".edit-btn");
        const deleteBtn = card.querySelector(".delete-btn");
        const titleField = card.querySelector(".idea-title");
        const textField = card.querySelector(".idea-text");
        const catSelect = card.querySelector(".idea-category");

        pinBtn.addEventListener("click", () => {
            fetch("", {
                method:"POST",
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:`action=pin&idea_id=${idea.id}`
            }).then(res=>res.json()).then(data=> {
                idea.pinned = Number(data.pinned);
                renderIdeas();
            });
        });

        editBtn.addEventListener("click", () => {
            if(editBtn.innerText === "Edit") {
                titleField.contentEditable = "true";
                textField.contentEditable = "true";
                catSelect.disabled = false;
                titleField.focus();
                editBtn.innerText = "Save";
            } else {
                const newTitle = titleField.innerText.trim();
                const newDesc = textField.innerText.trim();
                const newCat = catSelect.value;
                fetch("", {
                    method:"POST",
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:`action=edit&idea_id=${idea.id}&title=${encodeURIComponent(newTitle)}&description=${encodeURIComponent(newDesc)}&category=${encodeURIComponent(newCat)}`
                }).then(res=>res.json()).then(data=>{
                    if(data.updated){
                        idea.title = newTitle;
                        idea.description = newDesc;
                        idea.category = newCat;
                        titleField.contentEditable = "false";
                        textField.contentEditable = "false";
                        catSelect.disabled = true;
                        editBtn.innerText = "Edit";
                        showToast("Idea edited successfully!");
                        renderIdeas();
                    }
                });
            }
        });

        deleteBtn.addEventListener("click", () => {
            modalMessage.innerText = `Delete idea "${idea.title}"? This cannot be undone.`;
            modalOverlay.classList.remove("hidden");

            modalConfirm.onclick = () => {
                fetch("", {
                    method:"POST",
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:`action=delete&idea_id=${idea.id}`
                }).then(res=>res.json()).then(data=>{
                    if(data.deleted){
                        ideas = ideas.filter(i=>i.id!==idea.id);
                        showToast("Idea deleted successfully!");
                        renderIdeas();
                    }
                });
                modalOverlay.classList.add("hidden");
            };

            modalCancel.onclick = () => {
                modalOverlay.classList.add("hidden");
            };
        });

        ideasContainer.appendChild(card);
    });
}

categorySelect.addEventListener("change", renderIdeas);
sortSelect.addEventListener("change", renderIdeas);

renderIdeas();
</script>
</body>
</html>
