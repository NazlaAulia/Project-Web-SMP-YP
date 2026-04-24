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

    const idSiswa = localStorage.getItem("id_siswa") || "";
    const semesterText = document.getElementById("semester")?.value || "";

    if (!idSiswa) {
      alert("ID siswa tidak ditemukan.");
      return;
    }

    const response = await fetch(
      `get_detail_nilai_export.php?id_siswa=${encodeURIComponent(idSiswa)}&semester=${encodeURIComponent(semesterText)}`,
      {
        method: "GET",
        credentials: "same-origin"
      }
    );

    const result = await response.json();

    if (!result.success) {
      alert(result.message || "Gagal mengambil data export.");
      return;
    }

    const siswa = result.siswa || {};
    const detailNilai = result.detail_nilai || [];

    const doc = new jsPDF("p", "mm", "a4");
    const pageWidth = doc.internal.pageSize.getWidth();

    // ===== LOAD LOGO =====
    let logoData = null;
    try {
      logoData = await loadImageAsDataURL("../img/logo.webp");
    } catch (e) {
      console.warn("Logo gagal dimuat:", e);
    }

    // ===== BORDER LUAR =====
    doc.setDrawColor(0, 0, 0);
    doc.setLineWidth(0.5);
    doc.rect(10, 10, 190, 277);

    // ===== HEADER BOX =====
    doc.setLineWidth(0.4);
    doc.rect(14, 14, 182, 34);

    if (logoData) {
      doc.addImage(logoData, "PNG", 18, 17, 22, 22);
    }

    doc.setFont("helvetica", "bold");
    doc.setFontSize(11);
    doc.text("YAYASAN PENDIDIKAN TUNAS PERTIWI", pageWidth / 2 + 8, 21, { align: "center" });

    doc.setFontSize(18);
    doc.text("SMP YP 17 SURABAYA", pageWidth / 2 + 8, 29, { align: "center" });

    doc.setFont("helvetica", "normal");
    doc.setFontSize(9);
    doc.text("Jl. Randu No.17, Sidotopo Wetan,", pageWidth / 2 + 8, 35, { align: "center" });
    doc.text("Kec. Kenjeran, Surabaya, Jawa Timur 60128", pageWidth / 2 + 8, 40, { align: "center" });
    doc.text("Telepon : (031) 376 3721", pageWidth / 2 + 8, 45, { align: "center" });

    // ===== DATA SISWA =====
    let y = 58;

    doc.setFont("helvetica", "normal");
    doc.setFontSize(10);

    doc.text("Nama Siswa", 16, y);
    doc.text(`: ${siswa.nama || "-"}`, 50, y);

    doc.text("Kelas", 130, y);
    doc.text(`: ${siswa.kelas || "-"}`, 152, y);

    y += 7;
    doc.text("NIS", 16, y);
    doc.text(`: ${siswa.nis || "-"}`, 50, y);

    doc.text("NISN", 130, y);
    doc.text(`: ${siswa.nisn || "-"}`, 152, y);

    y += 7;
    doc.text("Nama Sekolah", 16, y);
    doc.text(": SMP YP 17 Surabaya", 50, y);

    y += 7;
    doc.text("Tahun Ajaran", 16, y);
    doc.text(`: ${formatTahunAjaranLengkap(semesterText, siswa.tahun_ajaran)}`, 50, y);

    // ===== JUDUL =====
    y += 12;
    doc.setFont("helvetica", "bold");
    doc.setFontSize(11);
    doc.text("A. Nilai Akhir Semester", 16, y);

    // ===== TABEL NILAI PER MAPEL =====
    const bodyNilai = detailNilai.length
      ? detailNilai.map((item, index) => [
          index + 1,
          item.mapel || "-",
          item.nilai || "-",
          item.keterangan || "-"
        ])
      : [[1, "-", "-", "-"]];

    doc.autoTable({
      startY: y + 4,
      head: [[
        "No",
        "Mata Pelajaran",
        "Nilai",
        "Keterangan"
      ]],
      body: bodyNilai,
      theme: "grid",
      margin: { left: 16, right: 16 },
      styles: {
        font: "helvetica",
        fontSize: 9,
        cellPadding: 2.5,
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
        0: { cellWidth: 14 },
        1: { cellWidth: 90, halign: "left" },
        2: { cellWidth: 30 },
        3: { cellWidth: 40 }
      }
    });

    let afterTableY = doc.lastAutoTable.finalY + 10;

    // ===== CATATAN =====
    doc.setFont("helvetica", "bold");
    doc.setFontSize(10);
    doc.text("Catatan :", 16, afterTableY);

    doc.setFont("helvetica", "normal");
    doc.rect(16, afterTableY + 3, 168, 18);
    doc.text("Pertahankan semangat belajar dan terus tingkatkan prestasi.", 18, afterTableY + 10);

    // ===== TANDA TANGAN =====
    const ttdY = afterTableY + 34;

    doc.text("Mengetahui,", 16, ttdY);
    doc.text("Kepala Sekolah", 16, ttdY + 6);

    doc.text(`Surabaya, ${formatTanggalIndonesia(new Date())}`, 126, ttdY);
    doc.text("Wali Kelas", 126, ttdY + 6);

    doc.setFont("helvetica", "bold");
    doc.text("(............................)", 16, ttdY + 30);
    doc.text("(............................)", 126, ttdY + 30);

    const namaFile = `Nilai_Semester_${(siswa.nama || "Siswa").replace(/\s+/g, "_")}.pdf`;
    doc.save(namaFile);

  } catch (error) {
    console.error("Error export PDF:", error);
    alert("Gagal membuat PDF.");
  }
}

function formatTahunAjaranLengkap(semesterText, tahunAjaranBackend) {
  const match = semesterText.match(/\d{4}\/\d{4}/);
  const tahunAjaran = match ? match[0] : (tahunAjaranBackend || "-");

  if (/genap/i.test(semesterText)) {
    return `${tahunAjaran} - Genap`;
  }

  if (/ganjil/i.test(semesterText)) {
    return `${tahunAjaran} - Ganjil`;
  }

  return tahunAjaran;
}

function formatTanggalIndonesia(date) {
  const bulan = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
  ];

  return `${date.getDate()} ${bulan[date.getMonth()]} ${date.getFullYear()}`;
}

function loadImageAsDataURL(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = "anonymous";

    img.onload = function () {
      const canvas = document.createElement("canvas");
      canvas.width = img.width;
      canvas.height = img.height;

      const ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0);

      resolve(canvas.toDataURL("image/png"));
    };

    img.onerror = reject;
    img.src = src;
  });
}