document.addEventListener("DOMContentLoaded", async () => {
  const sidebarContainer = document.getElementById("sidebar-container");

  if (!sidebarContainer) return;

  try {
    const response = await fetch("components/sidebarguru.html");

    if (!response.ok) {
      throw new Error("File sidebar tidak ditemukan");
    }

    const sidebarHTML = await response.text();
    sidebarContainer.innerHTML = sidebarHTML;

    setActiveMenu();

    if (typeof setupMobileSidebar === "function") {
      setupMobileSidebar();
    }

    setupSmoothMenuMove();
    setupLogoutPopup();
  } catch (error) {
    console.error("Gagal memuat sidebar:", error);
  }
});

function setActiveMenu() {
  const currentPage = window.location.pathname.split("/").pop() || "guru.html";
  const navLinks = document.querySelectorAll(".sidebar .nav-link");

  navLinks.forEach((link) => {
    const page = link.getAttribute("data-page");

    if (page === currentPage) {
      link.classList.add("active");
    } else {
      link.classList.remove("active");
    }
  });
}

function setupSmoothMenuMove() {
  const navLinks = document.querySelectorAll(".sidebar .nav-link");

  navLinks.forEach((link) => {
    link.addEventListener("click", function () {
      navLinks.forEach((item) => item.classList.remove("active"));
      this.classList.add("active");
    });
  });
}

function setupLogoutPopup() {
  const logoutLink = document.querySelector(".sidebar .logout");
  const logoutPopup = document.getElementById("logoutPopup");
  const cancelLogout = document.getElementById("cancelLogout");
  const confirmLogout = document.getElementById("confirmLogout");

  if (!logoutLink || !logoutPopup || !cancelLogout || !confirmLogout) return;

  logoutLink.addEventListener("click", function (e) {
    e.preventDefault();
    logoutPopup.classList.add("show");
  });

  cancelLogout.addEventListener("click", function () {
    logoutPopup.classList.remove("show");
  });

  confirmLogout.addEventListener("click", function () {
    window.location.href = logoutLink.getAttribute("href");
  });

  logoutPopup.addEventListener("click", function (e) {
    if (e.target === logoutPopup) {
      logoutPopup.classList.remove("show");
    }
  });
}

/* =========================
   TAMBAHKAN DI BAWAH INI
========================= */
function setupMobileSidebar() {
  const mobileMenuBtn = document.getElementById("mobileMenuBtn");
  const sidebar = document.querySelector(".sidebar");
  const sidebarOverlay = document.getElementById("sidebarOverlay");

  if (!mobileMenuBtn || !sidebar || !sidebarOverlay) return;

  mobileMenuBtn.addEventListener("click", function () {
    sidebar.classList.toggle("show");
    sidebarOverlay.classList.toggle("show");
  });

  sidebarOverlay.addEventListener("click", function () {
    sidebar.classList.remove("show");
    sidebarOverlay.classList.remove("show");
  });

  const navLinks = document.querySelectorAll(".sidebar .nav-link");
  navLinks.forEach((link) => {
    link.addEventListener("click", function () {
      sidebar.classList.remove("show");
      sidebarOverlay.classList.remove("show");
    });
  });
}