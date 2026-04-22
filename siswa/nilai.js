window.addEventListener("load", async () => {
  aktifkanFilterAnimasi();
  aktifkanExportAnimasi();
  await loadDataNilai();
});

/* ===== LOAD DATA NILAI DARI MYSQL ===== */
async function loadDataNilai() {
  try {
    const kelas = document.getElementById("kelas").value;
    const semester = document.getElementById("semester").value;

   const response = await fetch(
  `get_nilai.php?kelas=${encodeURIComponent(kelas)}&semester=${encodeURIComponent(semester)}`
);

    const text = await response.text();
    console.log("RAW get_nilai:", text);

    const result = JSON.parse(text);
    console.log("JSON get_nilai:", result);

    if (!result.success) {
      alert(result.message || "Gagal mengambil data nilai.");
      return;
    }

    isiHeader(result.siswa);
    isiStatistik(result.ringkasan);
    isiTabel(result.tabel);

    if (result.siswa.kelas) {
      document.getElementById("kelas").innerHTML = `<option value="${result.siswa.kelas}">${result.siswa.kelas}</option>`;
      document.getElementById("kelas").value = result.siswa.kelas;
    }

    jalankanCounter();
    tampilkanBox();
    tampilkanRow();
  } catch (error) {
    console.error("Error loadDataNilai:", error);
    alert("Terjadi kesalahan saat mengambil data nilai.");
  }
}

/* ===== ISI HEADER SISWA ===== */
function isiHeader(siswa) {
  const nama = siswa.nama || "-";
  const kelas = siswa.kelas || "-";
  const avatar = nama.charAt(0).toUpperCase();

  document.getElementById("namaText").textContent = nama;
  document.getElementById("kelasText").textContent = kelas;
  document.getElementById("avatarText").textContent = avatar;
}

/* ===== ISI BOX STATISTIK ===== */
function isiStatistik(ringkasan) {
  const rataRata = parseFloat(ringkasan.rata_rata || 0);
  const selisih = parseFloat(ringkasan.selisih || 0);
  const nilaiTertinggi = parseFloat(ringkasan.nilai_tertinggi || 0);

  const rataRataCounter = document.getElementById("rataRataCounter");
  const selisihCounter = document.getElementById("selisihCounter");
  const nilaiTertinggiCounter = document.getElementById("nilaiTertinggiCounter");

  rataRataCounter.textContent = "0";
  selisihCounter.textContent = "0";
  nilaiTertinggiCounter.textContent = "0";

  rataRataCounter.dataset.target = rataRata;
  selisihCounter.dataset.target = selisih;
  nilaiTertinggiCounter.dataset.target = nilaiTertinggi;

  document.getElementById("kelasStatText").textContent = `Kelas ${ringkasan.kelas || "-"}`;
  document.getElementById("mapelTertinggiText").textContent = ringkasan.mapel_tertinggi || "-";
}

/* ===== ISI TABEL ===== */
function isiTabel(rows) {
  const tbody = document.getElementById("nilaiTableBody");
  tbody.innerHTML = "";

  if (!rows || rows.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align:center;">Data nilai tidak ditemukan</td>
      </tr>
    `;
    return;
  }

  rows.forEach((row) => {
    const statusClass = row.status_arah === "down" ? "status-down" : "status-up";
    const iconClass = row.status_arah === "down" ? "fa-arrow-down" : "fa-arrow-up";

    tbody.innerHTML += `
      <tr>
        <td><span class="rank-badge">${row.rank}</span></td>
        <td>${row.nama}</td>
        <td>${row.kelas}</td>
        <td>${row.nilai_rata_rata}</td>
        <td>
          <span class="status-icon ${statusClass}">
            <i class="fa-solid ${iconClass}"></i>
          </span>
        </td>
        <td>${row.predikat}</td>
        <td>${row.absensi}</td>
      </tr>
    `;
  });
}

/* ===== ANIMASI BOX ===== */
function tampilkanBox() {
  const boxes = document.querySelectorAll(".stat-box");

  boxes.forEach((box, index) => {
    box.style.opacity = "0";
    box.style.transform = "translateY(18px)";

    setTimeout(() => {
      box.classList.add("show-box");
    }, 180 * index);
  });
}

/* ===== ANIMASI BARIS TABEL ===== */
function tampilkanRow() {
  const rows = document.querySelectorAll("#nilaiTableBody tr");

  rows.forEach((row, index) => {
    row.style.opacity = "0";
    row.style.transform = "translateY(18px)";

    setTimeout(() => {
      row.classList.add("show-row");
    }, 250 + index * 140);
  });
}

/* ===== COUNTER ANGKA ===== */
function jalankanCounter() {
  const counters = document.querySelectorAll(".counter");

  counters.forEach((counter) => {
    const target = parseFloat(counter.dataset.target || 0);
    const decimal = String(target).includes(".");
    let current = 0;
    const increment = target / 40 || 1;

    function updateCounter() {
      current += increment;

      if (current >= target) {
        counter.textContent = target;
      } else {
        counter.textContent = decimal ? current.toFixed(1) : Math.floor(current);
        requestAnimationFrame(updateCounter);
      }
    }

    updateCounter();
  });
}

/* ===== ANIMASI FILTER + RELOAD DATA ===== */
function aktifkanFilterAnimasi() {
  const kelas = document.getElementById("kelas");
  const semester = document.getElementById("semester");
  const nilaiSection = document.querySelector(".nilai-section");

  [kelas, semester].forEach((select) => {
    select.addEventListener("change", async () => {
      nilaiSection.style.transition = "0.3s ease";
      nilaiSection.style.transform = "scale(0.98)";
      nilaiSection.style.opacity = "0.7";

      await loadDataNilai();

      setTimeout(() => {
        nilaiSection.style.transform = "scale(1)";
        nilaiSection.style.opacity = "1";
      }, 180);
    });
  });
}

/* ===== ANIMASI TOMBOL EXPORT ===== */
function aktifkanExportAnimasi() {
  const exportBtn = document.getElementById("exportBtn");

  if (exportBtn) {
    exportBtn.addEventListener("click", () => {
      exportBtn.classList.add("pulse-click");

      setTimeout(() => {
        exportBtn.classList.remove("pulse-click");
      }, 350);

      alert("Tombol export siap dihubungkan ke backend PDF/Excel.");
    });
  }
}