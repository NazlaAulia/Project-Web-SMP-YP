fetch('./navbar.html')
  .then(response => response.text())
  .then(data => {
    document.getElementById('navbar-container').innerHTML = data;

    const currentPage = document.body.getAttribute('data-page');
    const activeLink = document.querySelector(`.sidebar-menu a[data-page="${currentPage}"]`);

    if (activeLink) {
      activeLink.classList.add('active');
    }

    const params = new URLSearchParams(window.location.search);
    const idSiswa = params.get('id_siswa') || localStorage.getItem('id_siswa');

    if (idSiswa) {
      localStorage.setItem('id_siswa', idSiswa);

      document.querySelectorAll('.sidebar-menu a').forEach(link => {
        const href = link.getAttribute('href');

        if (href && !href.startsWith('../')) {
          const url = new URL(href, window.location.href);
          url.searchParams.set('id_siswa', idSiswa);
          link.setAttribute('href', url.pathname.split('/').pop() + '?id_siswa=' + idSiswa);
        }
      });
    }
  })
  .catch(error => {
    console.error('Navbar gagal dimuat:', error);
  });