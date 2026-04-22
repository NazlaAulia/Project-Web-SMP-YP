window.addEventListener("load", async () => {
  isiHeaderDariLocalStorage();
  aktifkanFilterAnimasi();
  aktifkanExportAnimasi();
  await loadDataNilai();
});

function isiHeaderDariLocalStorage() {
  const nama = localStorage.getItem("nama_siswa") || "Siswa";
  const kelas = localStorage.getItem("kelas_siswa") || "-";
  const avatar = nama && nama !== "-" ? nama.charAt(0).toUpperCase() : "-";

  const namaText = document.getElementById("namaText");
  const kelasText = document.getElementById("kelasText");
  const avatarText = document.getElementById("avatarText");

  if (namaText) namaText.textContent = nama;
  if (kelasText) kelasText.textContent = kelas;
  if (avatarText) avatarText.textContent = avatar;
}

async function loadDataNilai() {
  try {
    const kelas = document.getElementById("kelas").value;
    const semester = document.getElementById("semester").value;
    const idSiswa = localStorage.getItem("id_siswa") || "";

    const response = await fetch(
      `get_nilai.php?id_siswa=${encodeURIComponent(idSiswa)}&kelas=${encodeURIComponent(kelas)}&semester=${encodeURIComponent(semester)}`,
      {
        method: "GET",
        credentials: "same-origin"
      }
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

    if (result.siswa && result.siswa.nama) {
      localStorage.setItem("nama_siswa", result.siswa.nama);
    }

    if (result.siswa && result.siswa.kelas) {
      localStorage.setItem("kelas_siswa", result.siswa.kelas);

      document.getElementById("kelas").innerHTML =
        `<option value="${result.siswa.kelas}">${result.siswa.kelas}</option>`;
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

function isiHeader(siswa) {
  if (!siswa) return;

  const nama = siswa.nama || "-";
  const kelas = siswa.kelas || "-";
  const avatar = siswa.inisial || (nama ? nama.charAt(0).toUpperCase() : "S");

  const namaText = document.getElementById("namaText");
  const kelasText = document.getElementById("kelasText");
  const avatarText = document.getElementById("avatarText");

  if (namaText) namaText.textContent = nama;
  if (kelasText) kelasText.textContent = kelas;
  if (avatarText) avatarText.textContent = avatar;
}

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