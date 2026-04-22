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

  loadJadwal();
});

async function loadJadwal() {
  try {
    const response = await fetch("get_jadwal.php");
    const text = await response.text();

    console.log("RAW RESPONSE:", text);

    const result = JSON.parse(text);
    console.log("JSON RESULT:", result);

    if (!result.success) {
      renderError(result.message || "Gagal memuat data.");
      return;
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

  document.getElementById("kelasSelect").innerHTML =
    `<option>${siswa.kelas || "-"}</option>`;

  document.getElementById("semesterSelect").innerHTML =
    `<option>${siswa.tahun_ajaran || "-"} - ${siswa.semester || "-"}</option>`;

  document.getElementById("judulTabel").textContent =
    `Jadwal Pelajaran Kelas ${siswa.kelas} (Minggu Ini)`;
}

function renderRingkasan(ringkasan) {
  document.getElementById("hariIni").textContent = ringkasan.hari_ini || "-";
  document.getElementById("totalPelajaran").textContent = `${ringkasan.total_pelajaran || 0} Jam`;
  document.getElementById("pelajaranUtama").textContent = ringkasan.pelajaran_utama || "-";
}

function renderUpdate(updateList) {
  const container = document.getElementById("updateJadwal");

  if (!updateList || updateList.length === 0) {
    container.innerHTML = "<p>- Tidak ada jadwal hari ini</p>";
    return;
  }

  container.innerHTML = updateList
    .map(item => `<p>- Jam ${item.jam_mulai} ${item.mapel} di ${item.ruangan}</p>`)
    .join("");
}

function renderTabel(jadwalList, kelas) {
  const tbody = document.getElementById("jadwalTableBody");

  if (!jadwalList || jadwalList.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="empty-state">Belum ada jadwal untuk kelas ${kelas}.</td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = jadwalList.map((item, index) => {
    let statusClass = "status-waiting";

    if (item.status.toLowerCase() === "selesai") {
      statusClass = "status-done";
    } else if (item.status.toLowerCase() === "berlangsung") {
      statusClass = "status-live";
    }

    return `
      <tr class="show-row" style="animation-delay:${index * 0.08}s">
        <td>
          <strong>${item.hari}</strong><br>
          ${item.jam_mulai} - ${item.jam_selesai}
        </td>
        <td>${item.mapel}</td>
        <td>${item.guru}</td>
        <td>${item.ruangan}</td>
        <td>
          <span class="status-badge ${statusClass}">${item.status}</span>
        </td>
      </tr>
    `;
  }).join("");
}

function renderError(message) {
  document.getElementById("jadwalTableBody").innerHTML = `
    <tr>
      <td colspan="5" class="empty-state">${message}</td>
    </tr>
  `;
}