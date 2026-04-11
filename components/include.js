document.addEventListener("DOMContentLoaded", function () {
  const navbarContainer = document.getElementById("navbar-container");

  if (!navbarContainer) return;

  fetch("components/navbar.html")
    .then(response => response.text())
    .then(data => {
      navbarContainer.innerHTML = data;

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
        });

        overlay.addEventListener("click", function () {
          menu.classList.remove("active");
          overlay.classList.remove("active");
        });
      }

      // dropdown akademik khusus mobile
      if (dropdown && dropdownLink) {
        dropdownLink.addEventListener("click", function (e) {
          if (window.innerWidth <= 768) {
            e.preventDefault();
            dropdown.classList.toggle("active");
          }
        });
      }
    })
    .catch(err => console.error("Gagal load navbar:", err));

    // Tambahkan ini di dalam DOMContentLoaded di file java.js kamu
const footerCont = document.getElementById("footer-container");
if (footerCont) {
    fetch("components/footer.html")
        .then(res => res.text())
        .then(html => {
            footerCont.innerHTML = html;
        });
}
});