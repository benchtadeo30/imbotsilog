window.toggleSidebar = function () {
    const sidebar = document.getElementById("sidebar");
    if (!sidebar) return;

    const isOpen = sidebar.classList.toggle("open");
    const backdrop = document.querySelector(".sidebar-backdrop");
    if (backdrop) backdrop.classList.toggle("show", isOpen);
    document.querySelectorAll('.navbar .menu-btn[aria-controls="sidebar"]').forEach((button) => {
        button.setAttribute("aria-expanded", String(isOpen));
    });
};

document.querySelectorAll("#sidebar nav a").forEach((link) => {
    const linkPage = new URL(link.href, window.location.href).pathname.split("/").pop();
    const currentPage = window.location.pathname.split("/").pop();
    if (linkPage === currentPage) {
        link.setAttribute("aria-current", "page");
    }
});

document.addEventListener("keydown", (event) => {
    const sidebar = document.getElementById("sidebar");
    if (event.key === "Escape" && sidebar && sidebar.classList.contains("open")) {
        window.toggleSidebar();
    }
});
