document.addEventListener("componentsLoaded", function () {
  const toggleBtn = document.getElementById("btn-toggle");
  const menu = document.querySelector(".menu");
  const overlay = document.querySelector(".menu-overlay");
  const dropdownLink = document.querySelector(".dropdown > a");
  const dropdown = document.querySelector(".dropdown");

  if (toggleBtn && menu && overlay) {
    toggleBtn.addEventListener("click", function () {
      menu.classList.toggle("active");
      overlay.classList.toggle("active");
    });

    overlay.addEventListener("click", function () {
      menu.classList.remove("active");
      overlay.classList.remove("active");
    });
  }

  if (dropdownLink && dropdown && window.innerWidth <= 768) {
    dropdownLink.addEventListener("click", function (e) {
      e.preventDefault();
      dropdown.classList.toggle("active");
    });
  }
});