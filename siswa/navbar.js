function initNavbar() {
  const currentPage = window.location.pathname.split("/").pop();

  document.querySelectorAll(".sidebar-menu a").forEach(link => {
    const href = link.getAttribute("href");

    if (href === currentPage) {
      link.classList.add("active");
    }
  });

  const hamburgerBtn = document.getElementById("hamburgerBtn");
  const sidebar = document.querySelector(".sidebar");

  if (hamburgerBtn && sidebar) {
  hamburgerBtn.addEventListener("click", function () {
    sidebar.classList.toggle("show-menu");

    const icon = hamburgerBtn.querySelector("i");

    if (icon) {
      icon.classList.toggle("fa-bars");
      icon.classList.toggle("fa-xmark");
    }
  });
}
  const logoutBtn = document.getElementById("logoutBtn");
  const logoutModal = document.getElementById("logoutModal");
  const cancelLogout = document.getElementById("cancelLogout");
  const confirmLogout = document.getElementById("confirmLogout");

  if (logoutBtn && logoutModal) {
    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault();
      logoutModal.classList.add("show");
    });
  }

  if (cancelLogout && logoutModal) {
    cancelLogout.addEventListener("click", function () {
      logoutModal.classList.remove("show");
    });
  }

  if (confirmLogout) {
    confirmLogout.addEventListener("click", function () {
      window.location.href = "keluar.php";
    });
  }

  if (logoutModal) {
    logoutModal.addEventListener("click", function (e) {
      if (e.target === logoutModal) {
        logoutModal.classList.remove("show");
      }
    });
  }
}

fetch("navbar.html")
  .then(response => response.text())
  .then(data => {
    document.getElementById("navbar-container").innerHTML = data;
    initNavbar();
  });