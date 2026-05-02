document.addEventListener("DOMContentLoaded", () => {
  const navLinks = document.querySelectorAll(".nav-link");

  navLinks.forEach((link) => {
    link.addEventListener("click", function () {
      this.classList.add("pulse-click");
      setTimeout(() => {
        this.classList.remove("pulse-click");
      }, 350);
    });
  });

  isiHeaderDariLocalStorage();
  loadJadwal();
});

// hahhhh
function isiHeaderDariLocalStorage() {
  const nama = localStorage.getItem("nama_siswa") || "Siswa";
  const kelas = localStorage.getItem("kelas_siswa") || "-";
  const avatar = nama && nama !== "-" ? nama.charAt(0).toUpperCase() : "-";

  const kelasBadge = document.getElementById("kelasBadge");
  const profileName = document.getElementById("profileName");
  const profileAvatar = document.getElementById("profileAvatar");

  if (kelasBadge) kelasBadge.textContent = kelas;
  if (profileName) profileName.textContent = nama;
  if (profileAvatar) profileAvatar.textContent = avatar;
}

async function loadJadwal() {
  try {
    const idSiswa = localStorage.getItem("id_siswa") || "";

    const response = await fetch(`get_jadwal.php?id_siswa=${encodeURIComponent(idSiswa)}`, {
      method: "GET",
      credentials: "same-origin"
    });

    const text = await response.text();

    console.log("RAW RESPONSE:", text);

    const result = JSON.parse(text);
    console.log("JSON RESULT:", result);

    if (!result.success) {
      renderError(result.message || "Gagal memuat data.");
      return;
    }

    if (result.siswa && result.siswa.nama) {
      localStorage.setItem("nama_siswa", result.siswa.nama);
    }

    if (result.siswa && result.siswa.kelas) {
      localStorage.setItem("kelas_siswa", result.siswa.kelas);
    }

    renderProfil(result.siswa);
    renderRingkasan(result.ringkasan);
    renderUpdate(result.update_terbaru);
    renderTabel(result.jadwal_minggu, result.siswa.kelas);
  } catch (error) {
    renderError("Terjadi kesalahan saat mengambil data jadwal.");
    console.error("ERROR FETCH / JSON:", error);
  }
}


function renderProfil(siswa) {
  document.getElementById("kelasBadge").textContent = siswa.kelas || "-";
  document.getElementById("profileName").textContent = siswa.nama || "-";
  document.getElementById("profileAvatar").textContent = siswa.inisial || "S";

  const semesterText = document.getElementById("semesterText");
  if (semesterText) {
    semesterText.textContent = `Sesuai dengan Kelas ${siswa.kelas}, Semester Ini`;
  }

  const kelasSelect = document.getElementById("kelasSelect");
  if (kelasSelect) {
    kelasSelect.innerHTML = `<option>${siswa.kelas || "-"}</option>`;
  }

  const semesterSelect = document.getElementById("semesterSelect");
  if (semesterSelect) {
    semesterSelect.innerHTML =
      `<option>${siswa.tahun_ajaran || "-"} - ${siswa.semester || "-"}</option>`;
  }

  document.getElementById("judulTabel").textContent =
    `Jadwal Pelajaran Kelas ${siswa.kelas} (Minggu Ini)`;
}

function renderRingkasan(ringkasan) {
  if (!ringkasan) return;

  const hariIni = document.getElementById("hariIni");
  const totalPelajaran = document.getElementById("totalPelajaran");
  const pelajaranUtama = document.getElementById("pelajaranUtama");

  if (hariIni) {
    hariIni.textContent = ringkasan.hari_ini || "-";
  }

  if (totalPelajaran) {
    totalPelajaran.textContent = `${ringkasan.total_pelajaran || 0} Jam`;
  }

  if (pelajaranUtama) {
    pelajaranUtama.textContent = ringkasan.pelajaran_utama || "-";
  }
}

function renderUpdate(updateList) {
  const container = document.getElementById("updateJadwal");

  if (!updateList || updateList.length === 0) {
    container.innerHTML = "<p>- Tidak ada jadwal hari ini</p>";
    return;
  }

  container.innerHTML = updateList
    .map(item => `<p>- Jam ${item.jam_mulai} ${item.mapel}</p>`)
    .join("");
}

function renderTabel(jadwalList, kelas) {
  const tbody = document.getElementById("jadwalTableBody");

  if (!jadwalList || jadwalList.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="4" class="empty-state">Belum ada jadwal untuk kelas ${kelas}.</td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = jadwalList.map((item) => {
    let statusClass = "status-waiting";

    if (item.status && item.status.toLowerCase() === "selesai") {
      statusClass = "status-done";
    } else if (item.status && item.status.toLowerCase() === "berlangsung") {
      statusClass = "status-live";
    }

    return `
      <tr class="show-row">
        <td>
          <strong>${item.hari}</strong><br>
          ${item.jam}
        </td>
        <td>${item.mata_pelajaran}</td>
        <td>${item.guru}</td>
        <td>
          <span class="status-badge ${statusClass}">${item.status || "Mendatang"}</span>
        </td>
      </tr>
    `;
  }).join("");
}

function renderError(message) {
  document.getElementById("jadwalTableBody").innerHTML = `
    <tr>
      <td colspan="4" class="empty-state">${message}</td>
    </tr>
  `;
}