document.addEventListener("DOMContentLoaded", async () => {
    const navMount = document.getElementById("admin-nav-root");
    if (!navMount) return;

    const navPath = document.body.getAttribute("data-nav-path") || "components/admin-nav.html";

    try {
        const response = await fetch(navPath);
        const html = await response.text();
        navMount.innerHTML = html;

        setActiveMenu();
        initSidebar();
    } catch (err) {
        console.error("Navbar gagal dimuat:", err);
    }
});

function setActiveMenu() {
    const page = document.body.getAttribute("data-page");

    document.querySelectorAll("[data-page]").forEach(el => {
        if (el.getAttribute("data-page") === page) {
            el.classList.add("active");
        }
    });

    document.querySelectorAll("[data-subpage]").forEach(el => {
        if (el.getAttribute("data-subpage") === page) {
            el.classList.add("active-submenu");
            const parent = el.closest("details");
            if (parent) parent.open = true;
        }
    });
}

function initSidebar() {
    const btn = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebarOverlay");

    if (!btn || !sidebar || !overlay) return;

    btn.onclick = () => {
        sidebar.classList.toggle("active");
        overlay.classList.toggle("active");
    };

    overlay.onclick = () => {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
    };
}