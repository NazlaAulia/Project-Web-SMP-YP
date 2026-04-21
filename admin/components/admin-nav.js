document.addEventListener("DOMContentLoaded", async () => {
    const navMount = document.getElementById("admin-nav-root");
    if (!navMount) return;

    try {
        const response = await fetch("components/admin-nav.html");
        if (!response.ok) throw new Error("File nav tidak ditemukan");
        
        const html = await response.text();
        navMount.innerHTML = html;

        // Jalankan fungsi setelah HTML masuk ke DOM
        setActiveAdminMenu();
        initAdminSidebar();
    } catch (error) {
        console.error("Gagal memuat komponen navigator admin:", error);
    }
});

function setActiveAdminMenu() {
    // Ambil identifier halaman dari body
    const currentPage = document.body.getAttribute("data-page");
    if (!currentPage) return;

    // 1. Handle Main Menu
    const navItems = document.querySelectorAll(".nav-item[data-page]");
    navItems.forEach(item => {
        if (item.getAttribute("data-page") === currentPage) {
            item.classList.add("active");
        }
    });

    // 2. Handle Sub Menu
    const subItems = document.querySelectorAll(".sub-menu a[data-subpage]");
    subItems.forEach(item => {
        if (item.getAttribute("data-subpage") === currentPage) {
            item.classList.add("active-submenu");

            // Otomatis buka parent <details> jika ada
            const parentDetails = item.closest("details");
            if (parentDetails) {
                parentDetails.open = true;
                // Tambahkan class active ke trigger details-nya juga supaya warnanya berubah
                const summary = parentDetails.querySelector(".nav-item");
                if (summary) summary.classList.add("active-parent");
            }
        }
    });
}

function initAdminSidebar() {
    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    // Fungsi tutup sidebar
    const closeSidebar = () => {
        sidebar.classList.remove("active");
        sidebarOverlay.classList.remove("active");
        document.body.style.overflow = ""; // Enable scroll
    };

    if (menuToggle && sidebar && sidebarOverlay) {
        menuToggle.addEventListener("click", (e) => {
            e.stopPropagation();
            sidebar.classList.toggle("active");
            sidebarOverlay.classList.toggle("active");
            
            // Mencegah scroll body saat menu mobile buka
            if(sidebar.classList.contains("active")) {
                document.body.style.overflow = "hidden";
            } else {
                document.body.style.overflow = "";
            }
        });

        // Klik di luar sidebar untuk menutup
        sidebarOverlay.addEventListener("click", closeSidebar);
        
        // Klik pada item menu mobile juga menutup sidebar (opsional)
        const navLinks = sidebar.querySelectorAll(".nav-item:not(summary), .sub-menu a");
        navLinks.forEach(link => {
            link.addEventListener("click", closeSidebar);
        });
    }
}