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
      const menu = document.querySelector(".menu");
      const overlay = document.querySelector(".menu-overlay");
      const dropdown = document.querySelector(".dropdown");
      const dropdownLink = document.querySelector(".dropdown > a");

      // tombol hamburger
      if (toggleBtn && menu && overlay) {
        toggleBtn.addEventListener("click", function (e) {
          e.preventDefault();
          menu.classList.toggle("active");
          overlay.classList.toggle("active");

          if (!menu.classList.contains("active") && dropdown) {
            dropdown.classList.remove("active");
          }
        });

        overlay.addEventListener("click", function () {
          menu.classList.remove("active");
          overlay.classList.remove("active");

          if (dropdown) {
            dropdown.classList.remove("active");
          }
        });
      }

      // dropdown akademik khusus mobile
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