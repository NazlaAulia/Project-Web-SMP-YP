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
    setupMobileSidebar();
    setupSmoothMenuMove();
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