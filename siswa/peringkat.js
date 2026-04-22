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

async function loadPeringkat() {
  try {
    const kelas = document.getElementById("kelas").value;
    const semester = document.getElementById("semester").value;

    const response = await fetch(
      `get_peringkat.php?kelas=${encodeURIComponent(kelas)}&semester=${encodeURIComponent(semester)}`
    );

    const text = await response.text();
    console.log("RAW get_peringkat:", text);

    const result = JSON.parse(text);
    console.log("JSON get_peringkat:", result);

    if (!result.success) {
      alert(result.message || "Gagal mengambil data peringkat");
      return;
    }

    const siswa = result.siswa;
    data = result.ranking || [];

    namaLogin = siswa.nama || "";
    kelasLogin = siswa.kelas || "";

    if (namaText) namaText.textContent = siswa.nama || "-";
    if (kelasText) kelasText.textContent = siswa.kelas || "-";
    if (avatarText) avatarText.textContent = (siswa.nama || "S").charAt(0).toUpperCase();

    if (peringkatSaatIni) peringkatSaatIni.textContent = `#${siswa.rank || 0}`;
    if (kelasCard) kelasCard.textContent = `Kelas ${siswa.kelas || "-"}`;
    if (nilaiRataRata) nilaiRataRata.textContent = siswa.nilai || 0;

    if (posisiSebelumnya) {
      const arrow = getStatusArrow(siswa.status);
      posisiSebelumnya.textContent = `#${siswa.posisi_sebelumnya || 0} ${arrow}`;
    }

    if (siswa.kelas) {
      document.getElementById("kelas").innerHTML = `<option value="${siswa.kelas}">${siswa.kelas}</option>`;
      document.getElementById("kelas").value = siswa.kelas;
    }

    currentPage = 1;
    renderPagination();
    renderTable();
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

  if (pageData.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" style="text-align:center;">Data peringkat tidak ditemukan</td>
      </tr>
    `;
    if (tableInfo) {
      tableInfo.textContent = "Menampilkan 0-0 dari 0 siswa";
    }
    return;
  }

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

function aktifkanFilter() {
  const kelas = document.getElementById("kelas");
  const semester = document.getElementById("semester");

  [kelas, semester].forEach((select) => {
    select.addEventListener("change", async () => {
      await loadPeringkat();
    });
  });
}

document.addEventListener("DOMContentLoaded", async () => {
  aktifkanFilter();
  await loadPeringkat();
});