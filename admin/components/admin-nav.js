document.addEventListener("DOMContentLoaded", async () => {
    const navMount = document.getElementById("admin-nav-root");
    if (!navMount) return;

    // Pakai path absolut supaya tidak salah folder
    const navPath = document.body.getAttribute("data-nav-path") || "/admin/components/admin-nav.html";

    try {
        // Tambahan ?v=999 supaya browser tidak pakai cache navbar lama
        const response = await fetch(navPath + "?v=999");

        if (!response.ok) {
            throw new Error("Navbar tidak ditemukan: " + navPath);
        }

        const html = await response.text();
        navMount.innerHTML = html;

        setActiveMenu();
        initSidebar();
        initLogoutModal();
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

function initLogoutModal() {
    const logoutBtn = document.getElementById("logoutBtn");
    const logoutModal = document.getElementById("logoutModal");
    const cancelLogout = document.getElementById("cancelLogout");

    if (!logoutBtn || !logoutModal || !cancelLogout) return;

    logoutBtn.addEventListener("click", function (e) {
        e.preventDefault();
        logoutModal.classList.add("active");
    });

    cancelLogout.addEventListener("click", function () {
        logoutModal.classList.remove("active");
    });

    logoutModal.addEventListener("click", function (e) {
        if (e.target === logoutModal) {
            logoutModal.classList.remove("active");
        }
    });
}