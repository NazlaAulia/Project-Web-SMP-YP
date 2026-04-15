document.addEventListener("DOMContentLoaded", function () {
  const navbarContainer = document.getElementById("navbar-container");
  if (!navbarContainer) return;

  fetch("components/navbar.html")
    .then(response => response.text())
    .then(data => {
      navbarContainer.innerHTML = data;

      const links = document.querySelectorAll(".menu a");
      links.forEach(link => {
        if (link.href === window.location.href) {
          link.classList.add("active");
        }
      });

      const toggleBtn = document.getElementById("btn-toggle");
      const closeBtn = document.getElementById("btn-close"); // 1. Tambah variabel close
      const menu = document.querySelector(".menu");
      const overlay = document.querySelector(".menu-overlay");
      const dropdown = document.querySelector(".dropdown");
      const dropdownLink = document.querySelector(".dropdown > a");

      // Fungsi nggo nutup menu (ben gak nulis bola-bali)
      function closeMobileMenu() {
        menu.classList.remove("active");
        overlay.classList.remove("active");
        if (dropdown) dropdown.classList.remove("active");
      }

      // Tombol hamburger (buka/toggle)
      if (toggleBtn && menu && overlay) {
        toggleBtn.addEventListener("click", function (e) {
          e.preventDefault();
          menu.classList.toggle("active");
          overlay.classList.toggle("active");
        });

        // 2. Tambah event nggo tombol silang (close)
        if (closeBtn) {
          closeBtn.addEventListener("click", function () {
            closeMobileMenu();
          });
        }

        overlay.addEventListener("click", function () {
          closeMobileMenu();
        });
      }

      // Dropdown akademik khusus mobile
      if (dropdown && dropdownLink) {
        dropdownLink.addEventListener("click", function (e) {
          if (window.innerWidth <= 768) {
            e.preventDefault();
            e.stopPropagation();
            dropdown.classList.toggle("active");
          }
        });

        document.addEventListener("click", function (e) {
          if (window.innerWidth <= 768 && !dropdown.contains(e.target)) {
            dropdown.classList.remove("active");
          }
        });
      }
    })
    .catch(err => console.error("Gagal load navbar:", err));

  const footerCont = document.getElementById("footer-container");
  if (footerCont) {
    fetch("components/footer.html")
      .then(res => res.text())
      .then(html => {
        footerCont.innerHTML = html;
      });
  }
});