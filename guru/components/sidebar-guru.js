document.addEventListener("DOMContentLoaded", async () => {
  const sidebarContainer = document.getElementById("sidebar-container");

  if (!sidebarContainer) return;

  try {
    const response = await fetch("components/sidebarguru.html")
    const sidebarHTML = await response.text();
    sidebarContainer.innerHTML = sidebarHTML;

    setActiveMenu();
  } catch (error) {
    console.error("Gagal memuat sidebar:", error);
  }
});

function setActiveMenu() {
  const currentPage = window.location.pathname.split("/").pop();
  const navLinks = document.querySelectorAll(".sidebar .nav-link");

  navLinks.forEach(link => {
    const page = link.getAttribute("data-page");

    if (page === currentPage) {
      link.classList.add("active");
    } else {
      link.classList.remove("active");
    }
  });
}