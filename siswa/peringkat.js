const tableBody = document.getElementById("tableBody");
const namaText = document.getElementById("namaText");
const kelasText = document.getElementById("kelasText");
const avatarText = document.getElementById("avatarText");

const peringkatSaatIni = document.getElementById("peringkatSaatIni");
const kelasCard = document.getElementById("kelasCard");
const nilaiRataRata = document.getElementById("nilaiRataRata");

let data = [];
let namaLogin = "";
let idLogin = "";

function isiHeaderDariLocalStorage() {
  const nama = localStorage.getItem("nama_siswa") || "Siswa";
  const kelas = localStorage.getItem("kelas_siswa") || "-";
  const avatar = nama && nama !== "-" ? nama.charAt(0).toUpperCase() : "-";

  if (namaText) namaText.textContent = nama;
  if (kelasText) kelasText.textContent = kelas;
  if (avatarText) avatarText.textContent = avatar;
}

async function loadTahunAjaran() {
  try {
    const response = await fetch(`get_peringkat.php?action=get_tahun_ajaran`, {
      method: "GET",
      credentials: "same-origin"
    });
    
    const result = await response.json();
    
    if (result.success && result.tahun_ajaran_list) {
      const tahunSelect = document.getElementById("tahunAjaran");
      if (tahunSelect) {
        tahunSelect.innerHTML = result.tahun_ajaran_list.map(ta => 
          `<option value="${ta.id_tahun_ajaran}" ${ta.status === 'aktif' ? 'selected' : ''}>
            ${ta.tahun_ajaran}
          </option>`
        ).join("");
        
        tahunSelect.addEventListener("change", () => loadPeringkat());
      }
    }
  } catch (error) {
    console.error("Error loading tahun ajaran:", error);
  }
}

async function loadPeringkat() {
  try {
    const kelas = localStorage.getItem("kelas_siswa") || "";
    const idSiswa = localStorage.getItem("id_siswa") || "";
    const tahunAjaran = document.getElementById("tahunAjaran")?.value || "";
    const semester = document.getElementById("semester")?.value || "2";

    const response = await fetch(
      `get_peringkat.php?id_siswa=${encodeURIComponent(idSiswa)}&kelas=${encodeURIComponent(kelas)}&semester=${encodeURIComponent(semester)}&tahun_ajaran=${encodeURIComponent(tahunAjaran)}`,
      {
        method: "GET",
        credentials: "same-origin"
      }
    );

    const text = await response.text();
    console.log("RAW get_peringkat:", text);

    const result = JSON.parse(text);
    console.log("JSON get_peringkat:", result);

    if (!result.success) {
      alert(result.message || "Gagal mengambil data peringkat");
      return;
    }

    const siswa = result.siswa || {};
    data = result.ranking || [];

    namaLogin = siswa.nama || "";
    idLogin = String(siswa.id_siswa || "");

    if (siswa.nama) {
      localStorage.setItem("nama_siswa", siswa.nama);
    }

    if (siswa.kelas) {
      localStorage.setItem("kelas_siswa", siswa.kelas);
    }

    if (namaText) namaText.textContent = siswa.nama || "-";
    if (kelasText) kelasText.textContent = siswa.kelas || "-";
    if (avatarText) avatarText.textContent = (siswa.nama || "S").charAt(0).toUpperCase();

    if (peringkatSaatIni) peringkatSaatIni.textContent = siswa.rank ? `#${siswa.rank}` : "#-";
    if (kelasCard) kelasCard.textContent = `Kelas ${siswa.kelas || "-"}`;
    if (nilaiRataRata) nilaiRataRata.textContent = siswa.nilai || "-";

    renderTable();
  } catch (error) {
    console.error("Error:", error);
    alert("Terjadi kesalahan saat mengambil data dari server");
  }
}

function getStatusBadge(status) {
  if (status === "naik") return '<span class="status-badge status-up">↑ Naik</span>';
  if (status === "turun") return '<span class="status-badge status-down">↓ Turun</span>';
  if (status === "tetap") return '<span class="status-badge status-steady">↔ Tetap</span>';
  if (status === "baru") return '<span class="status-badge status-new">● Baru</span>';
  return '<span class="status-badge status-na">-</span>';
}

function renderTable() {
  if (!tableBody) return;

  tableBody.innerHTML = "";

  if (data.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" style="text-align:center;">Data peringkat tidak ditemukan</td>
      </tr>
    `;
    return;
  }

  data.forEach((item) => {
    const row = document.createElement("tr");

    const isLoginUser =
      String(item.id_siswa) === idLogin || item.nama === namaLogin;

    const statusValue = item.status || "-";
    
    row.innerHTML = `
      <td>${item.rank}</td>
      <td>${item.nama}</td>
      <td>${item.kelas}</td>
      <td>${item.nilai}</td>
      <td>${getStatusBadge(statusValue)}</td>
    `;

    if (isLoginUser) {
      row.classList.add("active-row");
    }

    tableBody.appendChild(row);
  });
}

function aktifkanFilter() {
  const semester = document.getElementById("semester");
  const tahunAjaran = document.getElementById("tahunAjaran");

  if (semester) {
    semester.addEventListener("change", async () => {
      await loadPeringkat();
    });
  }
  
  if (tahunAjaran) {
    tahunAjaran.addEventListener("change", async () => {
      await loadPeringkat();
    });
  }
}

document.addEventListener("DOMContentLoaded", async () => {
  isiHeaderDariLocalStorage();
  await loadTahunAjaran();
  aktifkanFilter();
  await loadPeringkat();
});