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
    const kelas = localStorage.getItem("kelas_siswa") || "";
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
  const semester = document.getElementById("semester");
  const nilaiSection = document.querySelector(".nilai-section");

  if (!semester) return;

  semester.addEventListener("change", async () => {
    nilaiSection.style.transition = "0.3s ease";
    nilaiSection.style.transform = "scale(0.98)";
    nilaiSection.style.opacity = "0.7";

    await loadDataNilai();

    setTimeout(() => {
      nilaiSection.style.transform = "scale(1)";
      nilaiSection.style.opacity = "1";
    }, 180);
  });
}

function aktifkanExportAnimasi() {
  const exportBtn = document.getElementById("exportBtn");

  if (!exportBtn) return;

  exportBtn.addEventListener("click", async () => {
    exportBtn.classList.add("pulse-click");

    setTimeout(() => {
      exportBtn.classList.remove("pulse-click");
    }, 350);

    await exportNilaiPdf();
  });
}

async function exportNilaiPdf() {
  try {
    const { jsPDF } = window.jspdf;

    const nama = document.getElementById("namaText")?.textContent || "-";
    const kelas = document.getElementById("kelasText")?.textContent || "-";
    const semester = document.getElementById("semester")?.value || "-";

    const rataRata = document.getElementById("rataRataCounter")?.dataset.target || "-";
    const nilaiTertinggi = document.getElementById("nilaiTertinggiCounter")?.dataset.target || "-";
    const mapelTertinggi = document.getElementById("mapelTertinggiText")?.textContent || "-";

    const doc = new jsPDF("p", "mm", "a4");

    const pageWidth = doc.internal.pageSize.getWidth();

    // ===== HEADER SEKOLAH =====
    doc.setFont("helvetica", "bold");
    doc.setFontSize(14);
    doc.text("SMP YP 17 SURABAYA", pageWidth / 2, 18, { align: "center" });

    doc.setFontSize(10);
    doc.setFont("helvetica", "normal");
    doc.text("Sistem Informasi Akademik", pageWidth / 2, 24, { align: "center" });

    doc.setLineWidth(0.8);
    doc.line(20, 30, 190, 30);

    doc.setLineWidth(0.3);
    doc.line(20, 32, 190, 32);

    // ===== IDENTITAS SISWA =====
    doc.setFontSize(10);
    doc.setFont("helvetica", "normal");

    let y = 42;

    doc.text("Nama Siswa", 20, y);
    doc.text(": " + nama, 55, y);

    doc.text("Kelas", 125, y);
    doc.text(": " + kelas, 155, y);

    y += 7;

    doc.text("Semester", 20, y);
    doc.text(": " + semester, 55, y);

    doc.text("Tahun Pelajaran", 125, y);
    doc.text(": " + ambilTahunPelajaran(semester), 155, y);

    y += 7;

    doc.text("Nama Sekolah", 20, y);
    doc.text(": SMP YP 17 Surabaya", 55, y);

    y += 7;

    doc.text("Alamat Sekolah", 20, y);
    doc.text(": Surabaya", 55, y);

    // ===== JUDUL TABEL =====
    y += 14;

    doc.setFont("helvetica", "bold");
    doc.setFontSize(11);
    doc.text("A. Nilai Akhir Semester", 20, y);

    // ===== DATA NILAI =====
    const nilaiRows = ambilDataNilaiDariTabel();

    const tableBody = nilaiRows.length > 0
      ? nilaiRows.map((row, index) => [
          index + 1,
          row.nama,
          row.kelas,
          row.nilaiRataRata,
          row.predikat,
          row.absensi
        ])
      : [[1, nama, kelas, rataRata, "-", "-"]];

    doc.autoTable({
      startY: y + 5,
      head: [[
        "No",
        "Nama Siswa",
        "Kelas",
        "Nilai Rata-Rata",
        "Predikat",
        "Absensi"
      ]],
      body: tableBody,
      theme: "grid",
      styles: {
        font: "helvetica",
        fontSize: 9,
        cellPadding: 2,
        valign: "middle",
        halign: "center",
        lineColor: [60, 60, 60],
        lineWidth: 0.2
      },
      headStyles: {
        fillColor: [230, 230, 230],
        textColor: [0, 0, 0],
        fontStyle: "bold"
      },
      columnStyles: {
        0: { cellWidth: 12 },
        1: { cellWidth: 55, halign: "left" },
        2: { cellWidth: 20 },
        3: { cellWidth: 30 },
        4: { cellWidth: 25 },
        5: { cellWidth: 40, halign: "left" }
      }
    });

    let afterTableY = doc.lastAutoTable.finalY + 10;

    // ===== RINGKASAN =====
    doc.setFont("helvetica", "bold");
    doc.setFontSize(11);
    doc.text("B. Ringkasan Nilai", 20, afterTableY);

    doc.autoTable({
      startY: afterTableY + 5,
      body: [
        ["Rata-Rata Nilai", rataRata],
        ["Nilai Tertinggi", nilaiTertinggi],
        ["Mata Pelajaran Tertinggi", mapelTertinggi]
      ],
      theme: "grid",
      styles: {
        font: "helvetica",
        fontSize: 9,
        cellPadding: 2,
        lineColor: [60, 60, 60],
        lineWidth: 0.2
      },
      columnStyles: {
        0: { cellWidth: 60, fontStyle: "bold" },
        1: { cellWidth: 110 }
      }
    });

    afterTableY = doc.lastAutoTable.finalY + 12;

    // ===== CATATAN =====
    doc.setFont("helvetica", "bold");
    doc.text("Catatan:", 20, afterTableY);

    doc.setFont("helvetica", "normal");
    doc.rect(20, afterTableY + 3, 170, 20);
    doc.text("Terus tingkatkan semangat belajar dan pertahankan prestasi.", 23, afterTableY + 10);

    // ===== TANDA TANGAN =====
    const ttdY = afterTableY + 38;

    doc.text("Mengetahui,", 20, ttdY);
    doc.text("Kepala Sekolah", 20, ttdY + 6);

    doc.text("Surabaya, " + formatTanggalIndonesia(new Date()), 130, ttdY);
    doc.text("Wali Kelas", 130, ttdY + 6);

    doc.setFont("helvetica", "bold");
    doc.text("( Kepala Sekolah )", 20, ttdY + 32);
    doc.text("( Wali Kelas )", 130, ttdY + 32);

    // ===== SAVE PDF =====
    const namaFile = `Nilai_Akhir_Semester_${nama.replace(/\s+/g, "_")}.pdf`;
    doc.save(namaFile);

  } catch (error) {
    console.error("Error export PDF:", error);
    alert("Gagal membuat PDF. Pastikan library jsPDF sudah dimasukkan di HTML.");
  }
}

function ambilDataNilaiDariTabel() {
  const rows = document.querySelectorAll("#nilaiTableBody tr");
  const data = [];

  rows.forEach((tr) => {
    const td = tr.querySelectorAll("td");

    if (td.length < 7) return;

    data.push({
      peringkat: td[0]?.innerText.trim() || "-",
      nama: td[1]?.innerText.trim() || "-",
      kelas: td[2]?.innerText.trim() || "-",
      nilaiRataRata: td[3]?.innerText.trim() || "-",
      predikat: td[5]?.innerText.trim() || "-",
      absensi: td[6]?.innerText.trim() || "-"
    });
  });

  return data;
}

function ambilTahunPelajaran(semesterText) {
  const match = semesterText.match(/\d{4}\/\d{4}/);
  return match ? match[0] : "-";
}

function formatTanggalIndonesia(date) {
  const bulan = [
    "Januari",
    "Februari",
    "Maret",
    "April",
    "Mei",
    "Juni",
    "Juli",
    "Agustus",
    "September",
    "Oktober",
    "November",
    "Desember"
  ];

  const tanggal = date.getDate();
  const namaBulan = bulan[date.getMonth()];
  const tahun = date.getFullYear();

  return `${tanggal} ${namaBulan} ${tahun}`;
}