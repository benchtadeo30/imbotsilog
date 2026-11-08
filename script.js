let index = 0;

/* 🔥 FIX: class selector instead of getElementById */
const slides = document.querySelector(".slides");
const dots = document.querySelectorAll(".dots span");
const totalSlides = slides.children.length;

/* UPDATE SLIDER */
function updateSlide() {
    slides.style.transform = `translateX(-${index * 20}%)`;

    dots.forEach(dot => dot.classList.remove("active"));
    dots[index].classList.add("active");
}


/* DOT CLICK */
function goToSlide(i) {
    index = i;
    updateSlide();
}

/* NEXT */
function nextSlide() {
    index++;
    if (index >= totalSlides) {
        index = 0;
    }
    updateSlide();
}

/* PREV */
function prevSlide() {
    index--;
    if (index < 0) {
        index = totalSlides - 1;
    }
    updateSlide();
}

/* PAUSE ON HOVER */
slides.addEventListener("mouseenter", () => {
    clearInterval(auto);
});

slides.addEventListener("mouseleave", () => {
    auto = setInterval(nextSlide, 3000);
});

/* INIT */
updateSlide();

/* AUTO SLIDE */
let auto = setInterval(nextSlide, 3000);


function openHighlights() {
    document.getElementById("highlightsModal").style.display = "block";
}

function closeHighlights() {
    document.getElementById("highlightsModal").style.display = "none";
}

/* CLOSE kapag click sa labas */
window.onclick = function(event) {
    const modal = document.getElementById("highlightsModal");
    if (event.target === modal) {
        modal.style.display = "none";
    }
}

/* SIDEBAR */
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const backdrop = document.querySelector(".sidebar-backdrop");

    if (!sidebar) return;

    sidebar.classList.toggle("open");
    const isOpen = sidebar.classList.contains("open");
    if (backdrop) {
        backdrop.classList.toggle("show", isOpen);
    }
    document.querySelectorAll('.navbar .menu-btn[aria-controls="sidebar"]').forEach(button => {
        button.setAttribute("aria-expanded", String(isOpen));
    });
}

document.querySelectorAll("#sidebar nav a").forEach(link => {
    const linkPage = new URL(link.href, window.location.href).pathname.split("/").pop();
    const currentPage = window.location.pathname.split("/").pop();
    if (linkPage === currentPage) link.setAttribute("aria-current", "page");
});

document.addEventListener("keydown", event => {
    const sidebar = document.getElementById("sidebar");
    if (event.key === "Escape" && sidebar && sidebar.classList.contains("open")) {
        toggleSidebar();
    }
});