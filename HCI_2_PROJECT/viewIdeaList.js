const ideasContainer = document.getElementById("ideasContainer");
const categorySelect = document.getElementById("categorySelect");
const sortSelect = document.getElementById("sortSelect");
const hamburger = document.getElementById("hamburger-icon");
const sidebar = document.getElementById("sidebar");
const mainContent = document.querySelector(".main-content");
const mainAvatar = document.getElementById("mainAvatar");

// Toast helper
const toastContainer = document.getElementById("toast-container");
function showToast(msg) {
  const toast = document.createElement("div");
  toast.className = "toast";
  toast.textContent = msg;
  toastContainer.appendChild(toast);
  setTimeout(()=>toast.remove(), 3000);
}

// Modal helper
const modalOverlay = document.getElementById("modal-overlay");
const modalMessage = document.getElementById("modal-message");
const modalConfirm = document.getElementById("modal-confirm");
const modalCancel = document.getElementById("modal-cancel");
function showModal(message, callback) {
  modalMessage.textContent = message;
  modalOverlay.classList.remove("hidden");
  function cleanup() { modalOverlay.classList.add("hidden"); modalConfirm.onclick=null; modalCancel.onclick=null; }
  modalConfirm.onclick = () => { cleanup(); callback(true); };
  modalCancel.onclick = () => { cleanup(); callback(false); };
}

// Sidebar toggle
if(hamburger && sidebar && mainContent) {
  hamburger.addEventListener("click", () => { sidebar.classList.toggle("sidebar-active"); mainContent.classList.toggle("shifted"); });
}

// Load avatar
if(mainAvatar){ mainAvatar.src = localStorage.getItem("profileImage") || mainAvatar.src || "IMAGES/default-avatar.png"; }

// Highlight active nav
(function(){ try { const currentPage = window.location.pathname.split("/").pop(); document.querySelectorAll(".sidebar-nav a").forEach(link=>{ link.parentElement.classList.toggle("active-link", link.getAttribute("href")===currentPage); }); } catch(e){} })();

// Load ideas
let ideas = JSON.parse(localStorage.getItem("ideas")) || [];

// Render function
function renderIdeas(){
  ideasContainer.innerHTML="";
  let filteredIdeas = ideas.map((idea, idx)=>({idea, idx}));
  const selectedCategory = categorySelect?.value||"all";
  if(selectedCategory!=="all"){ filteredIdeas=filteredIdeas.filter(e=>e.idea.category.toLowerCase()===selectedCategory); }
  const sortValue = sortSelect?.value||"default";
  if(sortValue==="date"){ filteredIdeas.sort((a,b)=>new Date(a.idea.date||0)-new Date(b.idea.date||0)); }
  else if(sortValue==="az"){ filteredIdeas.sort((a,b)=>a.idea.title.localeCompare(b.idea.title)); }
  else if(sortValue==="za"){ filteredIdeas.sort((a,b)=>b.idea.title.localeCompare(a.idea.title)); }
  else if(sortValue==="pinned"){ filteredIdeas.sort((a,b)=> (b.idea.pinned===true)-(a.idea.pinned===true)); }

  filteredIdeas.forEach(({idea, idx: originalIndex})=>{
    const card=document.createElement("div");
    card.classList.add("idea-card");
    if(idea.pinned) card.classList.add("pinned");
    const cat=(idea.category||"").toLowerCase();
    if(cat==="music") card.classList.add("music-bg");
    else if(cat==="work") card.classList.add("work-bg");
    else if(cat==="personal") card.classList.add("personal-bg");
    else if(cat==="hobby") card.classList.add("hobby-bg");

    const safeTitle=(idea.title||"").replace(/"/g,"&quot;");
    const safeText=(idea.text||"").replace(/</g,"&lt;").replace(/>/g,"&gt;");

    card.innerHTML=`
      <input type="text" value="${safeTitle}" class="idea-title" ${idea.editing?"":"readonly"}>
      <textarea class="idea-text" ${idea.editing?"":"readonly"}>${safeText}</textarea>
      <p><strong>Category:</strong> ${idea.category||"—"}</p>
      <p><strong>Date:</strong> ${idea.date||"No date"}</p>
      <div class="button-group">
        ${idea.editing?`<button class="save-btn" data-index="${originalIndex}">Save</button>`:`<button class="edit-btn" data-index="${originalIndex}">Edit</button>`}
        <button class="delete-btn" data-index="${originalIndex}">Delete</button>
        <button class="pin-btn" data-index="${originalIndex}">${idea.pinned?"Unpin":"Pin"}</button>
      </div>
    `;
    ideasContainer.appendChild(card);
  });

  attachEventListeners();
}

// Event handlers
function attachEventListeners(){
  document.querySelectorAll(".edit-btn").forEach(btn=>btn.onclick=(e)=>{
    const i=Number(e.currentTarget.dataset.index);
    if(!Number.isNaN(i) && ideas[i]) { ideas[i].editing=true; renderIdeas(); showToast("Edit enabled"); }
  });

  document.querySelectorAll(".save-btn").forEach(btn=>btn.onclick=(e)=>{
    const i=Number(e.currentTarget.dataset.index);
    const card=e.currentTarget.closest(".idea-card");
    const newTitle=card.querySelector(".idea-title").value.trim();
    const newText=card.querySelector(".idea-text").value.trim();
    if(!newTitle||!newText){ showModal("Title and idea text cannot be empty!",()=>{}); return; }
    if(!Number.isNaN(i) && ideas[i]){
      ideas[i].title=newTitle; ideas[i].text=newText; ideas[i].editing=false;
      saveIdeas(); renderIdeas(); showToast("Idea saved");
    }
  });

  document.querySelectorAll(".delete-btn").forEach(btn=>btn.onclick=(e)=>{
    const i=Number(e.currentTarget.dataset.index);
    if(!Number.isNaN(i) && ideas[i]){
      showModal("Delete this idea?", confirmed=>{
        if(confirmed){ ideas.splice(i,1); saveIdeas(); renderIdeas(); showToast("Idea deleted"); }
      });
    }
  });

  document.querySelectorAll(".pin-btn").forEach(btn=>btn.onclick=(e)=>{
    const i=Number(e.currentTarget.dataset.index);
    if(!Number.isNaN(i) && ideas[i]){
      ideas[i].pinned=!ideas[i].pinned; saveIdeas(); renderIdeas(); showToast(ideas[i].pinned?"Pinned":"Unpinned");
    }
  });
}

// Save
function saveIdeas(){ localStorage.setItem("ideas", JSON.stringify(ideas)); }

// Filters
function updateCategoryDropdownColor(){
  if(!categorySelect) return;
  const color=categorySelect.options[categorySelect.selectedIndex]?.dataset.color||"#777";
  categorySelect.style.backgroundColor=color; categorySelect.style.color="#fff";
}
categorySelect?.addEventListener("change",()=>{ updateCategoryDropdownColor(); renderIdeas(); });
sortSelect?.addEventListener("change",()=>renderIdeas());

// Init
updateCategoryDropdownColor();
renderIdeas();
