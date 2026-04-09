document.addEventListener("DOMContentLoaded", async function () {
  const navbarContainer = document.getElementById("navbar-container");
  const footerContainer = document.getElementById("footer-container");

  if (navbarContainer) {
    const response = await fetch("components/navbar.html");
    const data = await response.text();
    navbarContainer.innerHTML = data;
  }

  if (footerContainer) {
    const response = await fetch("components/footer.html");
    const data = await response.text();
    footerContainer.innerHTML = data;
  }

  document.dispatchEvent(new Event("componentsLoaded"));
});