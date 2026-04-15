const tableBody = document.getElementById("tableBody");
const namaText = document.getElementById("namaText");
const kelasText = document.getElementById("kelasText");
const avatarText = document.getElementById("avatarText");

const peringkatSaatIni = document.getElementById("peringkatSaatIni");
const kelasCard = document.getElementById("kelasCard");
const nilaiRataRata = document.getElementById("nilaiRataRata");
const posisiSebelumnya = document.getElementById("posisiSebelumnya");

const tableInfo = document.querySelector(".table-info");
const paginationWrap = document.querySelector(".pagination");

let data = [];
let namaLogin = "";
let kelasLogin = "";
let currentPage = 1;
const perPage = 10;

function getIdSiswa() {
  const params = new URLSearchParams(window.location.search);
  const idFromUrl = params.get("id_siswa");

  if (idFromUrl) {
    localStorage.setItem("id_siswa", idFromUrl);
    return idFromUrl;
  }

  return localStorage.getItem("id_siswa");
}

function updateSidebarLinks(idSiswa) {
  if (!idSiswa) return;

  const sidebarLinks = document.querySelectorAll(".sidebar-menu a");

  sidebarLinks.forEach((link) => {
    const href = link.getAttribute("href");
    if (!href || href.startsWith("http") || href.startsWith("#")) return;

    const baseUrl = href.split("?")[0];
    link.setAttribute("href", `${baseUrl}?id_siswa=${idSiswa}`);
  });
}

async function loadPeringkat() {
  try {
    const idSiswa = getIdSiswa();
    console.log("id_siswa =", idSiswa);

    if (!idSiswa) {
      alert("id_siswa tidak ditemukan. Silakan login ulang.");
      return;
    }

    updateSidebarLinks(idSiswa);

    const response = await fetch(`get_peringkat.php?id_siswa=${idSiswa}`, {
      method: "GET"
    });

    const result = await response.json();
    console.log("HASIL get_peringkat.php =", result);

    if (!result.success) {
      alert(result.message || "Gagal mengambil data peringkat");
      return;
    }

    const siswa = result.siswa;
    data = result.ranking || [];

    namaLogin = siswa.nama;
    kelasLogin = siswa.kelas;

    console.log("Nama dari PHP =", namaLogin);
    console.log("Kelas dari PHP =", kelasLogin);

    if (namaText) namaText.textContent = siswa.nama;
    if (kelasText) kelasText.textContent = siswa.kelas;
    if (avatarText) avatarText.textContent = siswa.nama.charAt(0).toUpperCase();

    if (peringkatSaatIni) peringkatSaatIni.textContent = `#${siswa.rank}`;
    if (kelasCard) kelasCard.textContent = `Kelas ${siswa.kelas}`;
    if (nilaiRataRata) nilaiRataRata.textContent = siswa.nilai;
    if (posisiSebelumnya) {
      const arrow = getStatusArrow(siswa.status);
      posisiSebelumnya.textContent = `#${siswa.posisi_sebelumnya} ${arrow}`;
    }

    renderPagination();
    renderTable();
    renderPodium();
  } catch (error) {
    console.error("Error:", error);
    alert("Terjadi kesalahan saat mengambil data dari server");
  }
}

function getStatusArrow(status) {
  if (status === "naik") return "↑";
  if (status === "turun") return "↓";
  if (status === "tetap") return "↔";
  if (status === "↑" || status === "↓" || status === "↔") return status;
  return "↔";
}

function renderTable() {
  if (!tableBody) return;

  tableBody.innerHTML = "";

  const start = (currentPage - 1) * perPage;
  const end = start + perPage;
  const pageData = data.slice(start, end);

  pageData.forEach((item) => {
    const row = document.createElement("tr");
    const isLoginUser = item.nama === namaLogin;

    row.innerHTML = `
      <td>${item.rank}</td>
      <td>${item.nama}</td>
      <td>${item.kelas}</td>
      <td>${item.nilai}</td>
      <td>${getStatusArrow(item.status)}</td>
    `;

    if (isLoginUser) {
      row.classList.add("active-row");
    }

    tableBody.appendChild(row);
  });

  if (tableInfo) {
    const total = data.length;
    const from = total === 0 ? 0 : start + 1;
    const to = Math.min(end, total);
    tableInfo.textContent = `Menampilkan ${from}-${to} dari ${total} siswa`;
  }
}

function renderPagination() {
  if (!paginationWrap) return;

  const totalPages = Math.ceil(data.length / perPage) || 1;
  paginationWrap.innerHTML = `Halaman: `;

  const prevBtn = document.createElement("button");
  prevBtn.type = "button";
  prevBtn.textContent = "<";
  prevBtn.disabled = currentPage === 1;
  prevBtn.addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      renderPagination();
      renderTable();
    }
  });
  paginationWrap.appendChild(prevBtn);

  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.textContent = i;
    if (i === currentPage) {
      btn.classList.add("active-page");
    }

    btn.addEventListener("click", () => {
      currentPage = i;
      renderPagination();
      renderTable();
    });

    paginationWrap.appendChild(btn);
  }

  const nextBtn = document.createElement("button");
  nextBtn.type = "button";
  nextBtn.textContent = ">";
  nextBtn.disabled = currentPage === totalPages;
  nextBtn.addEventListener("click", () => {
    if (currentPage < totalPages) {
      currentPage++;
      renderPagination();
      renderTable();
    }
  });
  paginationWrap.appendChild(nextBtn);
}

function renderPodium() {
  const podiumList = document.querySelector(".podium-list");
  if (!podiumList) return;

  const top3 = [...data].sort((a, b) => a.rank - b.rank).slice(0, 3);

  const medalClass = {
    1: "gold",
    2: "silver",
    3: "bronze"
  };

  const medalText = {
    1: "Medali Emas",
    2: "Medali Perak",
    3: "Medali Perunggu"
  };

  podiumList.innerHTML = "";

  top3.forEach((item) => {
    const div = document.createElement("div");
    div.className = "podium-item";

    div.innerHTML = `
      <div class="podium-rank ${medalClass[item.rank] || ""}">${item.rank}</div>
      <div>
        <strong>${item.nama}</strong><br>
        <span>${item.nilai} - ${medalText[item.rank] || "Peserta Terbaik"}</span>
      </div>
    `;

    podiumList.appendChild(div);
  });
}

document.addEventListener("DOMContentLoaded", loadPeringkat);