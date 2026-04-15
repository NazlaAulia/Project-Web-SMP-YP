fetch('navbar.html')
  .then(response => response.text())
  .then(data => {
    document.getElementById('navbar-container').innerHTML = data;

    const currentPage = document.body.getAttribute('data-page');
    const activeLink = document.querySelector(`.sidebar-menu a[data-page="${currentPage}"]`);

    if (activeLink) {
      activeLink.classList.add('active');
      activeLink.parentElement.classList.add('active');
    }
  })
  .catch(error => {
    console.error('Navbar gagal dimuat:', error);
  });