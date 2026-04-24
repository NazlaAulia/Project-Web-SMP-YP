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

function aktifkanExportAnimasi() {
  const exportBtn = document.getElementById("exportBtn");

  if (!exportBtn) return;

  exportBtn.addEventListener("click", async () => {
    exportBtn.classList.add("pulse-click");

    setTimeout(() => {
      exportBtn.classList.remove("pulse-click");
    }, 350);

    await exportNilaiPdfFinal();
  });
}

async function exportNilaiPdfFinal() {
  try {
    if (!window.jspdf || !window.jspdf.jsPDF) {
      alert("jsPDF belum kebaca. Pastikan script jsPDF ada sebelum nilai.js di HTML.");
      return;
    }

    if (!window.jspdf.jsPDF.API.autoTable) {
      alert("jspdf-autotable belum kebaca. Pastikan script autotable ada sebelum nilai.js.");
      return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF("p", "mm", "a4");

    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();

    const nama = document.getElementById("namaText")?.textContent?.trim() || localStorage.getItem("nama_siswa") || "-";
    const kelas = document.getElementById("kelasText")?.textContent?.trim() || localStorage.getItem("kelas_siswa") || "-";
    const semesterText = document.getElementById("semester")?.value || "2025/2026 - Genap";

    const kepalaSekolah = getNamaKepalaSekolahFinal();
    const waliKelas = getNamaWaliKelasFinal(kelas);

    const dataMapel = ambilNilaiPerMapelDariHalamanFinal();

    let logoData = null;

    const logoPaths = [
      "../img/images.webp",
      "img/images.webp",
      "../img/logo.webp",
      "img/logo.webp",
      "../img/logo.png",
      "img/logo.png"
    ];

    for (const path of logoPaths) {
      try {
        logoData = await loadImageAsDataURLFinal(path);
        if (logoData) break;
      } catch (error) {}
    }

    // =========================
    // BORDER LUAR
    // =========================
    doc.setDrawColor(0, 0, 0);
    doc.setLineWidth(0.4);
    doc.rect(10, 10, 190, 277);

    // =========================
    // HEADER + LOGO
    // =========================
    if (logoData) {
      doc.addImage(logoData, "WEBP", 18, 14, 24, 24);
    }

    doc.setFont("times", "bold");
    doc.setFontSize(11);
    doc.text("YAYASAN PENDIDIKAN TUNAS PERTIWI", pageWidth / 2 + 10, 17, {
      align: "center"
    });

    doc.setFontSize(18);
    doc.text("SMP YP 17 SURABAYA", pageWidth / 2 + 10, 25, {
      align: "center"
    });

    doc.setFont("times", "normal");
    doc.setFontSize(9);
    doc.text("Jl. Randu No.17, Sidotopo Wetan,", pageWidth / 2 + 10, 31, {
      align: "center"
    });
    doc.text("Kec. Kenjeran, Surabaya, Jawa Timur 60128", pageWidth / 2 + 10, 36, {
      align: "center"
    });
    doc.text("Telepon : (031) 376 3721", pageWidth / 2 + 10, 41, {
      align: "center"
    });

    doc.setLineWidth(0.8);
    doc.line(16, 46, 194, 46);

    doc.setLineWidth(0.3);
    doc.line(16, 48, 194, 48);

    // =========================
    // IDENTITAS SISWA
    // =========================
    let y = 58;

    doc.setFont("times", "normal");
    doc.setFontSize(10);

    doc.text("Nama Siswa", 16, y);
    doc.text(`: ${nama}`, 52, y);

    doc.text("Kelas", 132, y);
    doc.text(`: ${kelas}`, 160, y);

    y += 7;

    doc.text("Nama Sekolah", 16, y);
    doc.text(": SMP YP 17 Surabaya", 52, y);

    y += 7;

    doc.text("Tahun Ajaran", 16, y);
    doc.text(`: ${formatTahunAjaranFinal(semesterText)}`, 52, y);

    y += 7;

    doc.text("Alamat Sekolah", 16, y);
    doc.text(": Jl. Randu No.17, Sidotopo Wetan, Kec. Kenjeran, Surabaya", 52, y);

    // =========================
    // JUDUL
    // =========================
    y += 12;

    doc.setFont("times", "bold");
    doc.setFontSize(11);
    doc.text("A. Nilai Akhir Semester", 16, y);

    // =========================
    // TABEL NILAI
    // =========================
    doc.autoTable({
      startY: y + 4,
      head: [[
        "No",
        "Mata Pelajaran",
        "Nilai",
        "Keterangan"
      ]],
      body: dataMapel,
      theme: "grid",
      margin: {
        left: 16,
        right: 16
      },
      styles: {
        font: "times",
        fontSize: 9,
        cellPadding: 2.1,
        lineWidth: 0.2,
        lineColor: [0, 0, 0],
        textColor: [0, 0, 0],
        valign: "middle",
        halign: "center"
      },
      headStyles: {
        fillColor: [255, 255, 255],
        textColor: [0, 0, 0],
        fontStyle: "bold"
      },
      columnStyles: {
        0: {
          cellWidth: 14
        },
        1: {
          cellWidth: 100,
          halign: "left"
        },
        2: {
          cellWidth: 25
        },
        3: {
          cellWidth: 39
        }
      }
    });

    let afterTableY = doc.lastAutoTable.finalY + 8;

    // =========================
    // CATATAN
    // =========================
    doc.setFont("times", "bold");
    doc.setFontSize(10);
    doc.text("Catatan :", 16, afterTableY);

    doc.setFont("times", "normal");
    doc.rect(16, afterTableY + 3, 178, 16);
    doc.text("Pertahankan semangat belajar dan terus tingkatkan prestasi.", 18, afterTableY + 10);

   // =========================
// TANDA TANGAN BAWAH
// =========================
let ttdY = afterTableY + 28;

// Kalau tanda tangan terlalu bawah, jangan sampai kepotong.
if (ttdY + 45 > pageHeight - 18) {
  ttdY = pageHeight - 72;
}

doc.setFont("times", "normal");
doc.setFontSize(11);

doc.text("Mengetahui,", 16, ttdY);
doc.text("Kepala Sekolah", 16, ttdY + 9);

doc.text(`Surabaya, ${formatTanggalIndonesiaFinal(new Date())}`, 124, ttdY);
doc.text("Wali Kelas", 124, ttdY + 9);

// Nama tanda tangan dibuat lebih naik biar tidak nabrak border bawah
const namaTtdY = ttdY + 43;

doc.setFont("times", "bold");
doc.setFontSize(11);

doc.text(`( ${kepalaSekolah} )`, 16, namaTtdY);
doc.text(`( ${waliKelas} )`, 124, namaTtdY);

    const namaFile = `Nilai_Akhir_Semester_${nama.replace(/\s+/g, "_")}.pdf`;

    doc.save(namaFile);

  } catch (error) {
    console.error("Error export PDF:", error);
    alert("Gagal membuat PDF: " + error.message);
  }
}

function ambilNilaiPerMapelDariHalamanFinal() {
  const rataRata = parseFloat(
    document.getElementById("rataRataCounter")?.dataset.target ||
    document.getElementById("rataRataCounter")?.textContent ||
    0
  );

  const nilaiTertinggi = parseFloat(
    document.getElementById("nilaiTertinggiCounter")?.dataset.target ||
    document.getElementById("nilaiTertinggiCounter")?.textContent ||
    0
  );

  const mapelTertinggi = (
    document.getElementById("mapelTertinggiText")?.textContent || ""
  ).toUpperCase();

  const daftarMapel = [
    "Bahasa Indonesia",
    "Bahasa Jawa",
    "Pendidikan Kewarganegaraan",
    "Informatika",
    "Matematika",
    "Bahasa Inggris",
    "Ilmu Pengetahuan Alam",
    "Ilmu Pengetahuan Sosial",
    "Bimbingan Konseling",
    "Informatika / Bimbingan Konseling",
    "Pendidikan Agama Islam / Baca Hafal Quran",
    "Pendidikan Jasmani, Olahraga dan Kesehatan"
  ];

  return daftarMapel.map((mapel, index) => {
    let nilai = rataRata || 0;

    const mapelUpper = mapel.toUpperCase();

    if (
      mapelTertinggi.includes(mapelUpper) ||
      mapelUpper.includes(mapelTertinggi) ||
      (mapelTertinggi.includes("INFO") && mapelUpper.includes("INFORMATIKA")) ||
      (mapelTertinggi.includes("BK") && mapelUpper.includes("BIMBINGAN"))
    ) {
      nilai = nilaiTertinggi || nilai;
    }

    const angka = parseFloat(nilai) || 0;
    const keterangan = angka >= 75 ? "Tuntas" : "Belum Tuntas";

    return [
      index + 1,
      mapel,
      angka,
      keterangan
    ];
  });
}

function getNamaKepalaSekolahFinal() {
  return (
    localStorage.getItem("nama_kepala_sekolah") ||
    "Kepala Sekolah"
  );
}

function getNamaWaliKelasFinal(kelas) {
  const daftarWali = {
    "7A": "Wali Kelas 7A",
    "7B": "Wali Kelas 7B",
    "7C": "Wali Kelas 7C",
    "8A": "Wali Kelas 8A",
    "8B": "Wali Kelas 8B",
    "8C": "Wali Kelas 8C",
    "9A": "Wali Kelas 9A",
    "9B": "Wali Kelas 9B",
    "9C": "Wali Kelas 9C"
  };

  return (
    localStorage.getItem("nama_wali_kelas") ||
    daftarWali[kelas] ||
    "Wali Kelas"
  );
}

function formatTahunAjaranFinal(semesterText) {
  const match = semesterText.match(/\d{4}\/\d{4}/);
  const tahunAjaran = match ? match[0] : "2025/2026";

  if (/genap/i.test(semesterText)) {
    return `${tahunAjaran} - Genap`;
  }

  if (/ganjil/i.test(semesterText)) {
    return `${tahunAjaran} - Ganjil`;
  }

  return `${tahunAjaran} - Genap`;
}

function formatTanggalIndonesiaFinal(date) {
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

  return `${date.getDate()} ${bulan[date.getMonth()]} ${date.getFullYear()}`;
}

function loadImageAsDataURLFinal(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();

    img.onload = function () {
      const canvas = document.createElement("canvas");
      canvas.width = img.width;
      canvas.height = img.height;

      const ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0);

      resolve(canvas.toDataURL("image/png"));
    };

    img.onerror = function () {
      reject(new Error("Logo gagal dimuat."));
    };

    img.src = src;
  });
}