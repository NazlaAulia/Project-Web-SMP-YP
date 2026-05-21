let dataNilai = [];
let uploadSection = null;
let btnCetakSemua = null;

const filterKelasWaliLabel = document.querySelector('label[for="filterKelasWali"]');
const printBtn = document.getElementById("printBtn");
const fileInput = document.getElementById("fileInput");
const uploadBtn = document.getElementById("uploadBtn");
const downloadTemplateBtn = document.getElementById("downloadTemplateBtn");
const searchInput = document.getElementById("searchInput");
const messageBox = document.getElementById("messageBox");
const nilaiTableBody = document.getElementById("nilaiTableBody");

const modeNilai = document.getElementById("modeNilai");
const filterKelasWali = document.getElementById("filterKelas");
uploadSection = document.querySelector(".import-card");

const filterWaliKelasBox = document.getElementById("filterWaliKelasBox");
const filterKelasWaliGroup = document.getElementById("filterKelasWaliGroup");

const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");

const daftarMapelCsv = [
  { id_mapel: 1, nama_mapel: "BIN" },
  { id_mapel: 2, nama_mapel: "B. JAWA" },
  { id_mapel: 3, nama_mapel: "PKN" },
  { id_mapel: 4, nama_mapel: "INFOR" },
  { id_mapel: 5, nama_mapel: "MAT" },
  { id_mapel: 6, nama_mapel: "BIG" },
  { id_mapel: 7, nama_mapel: "IPA" },
  { id_mapel: 8, nama_mapel: "IPS" },
  { id_mapel: 9, nama_mapel: "BK" },
  { id_mapel: 10, nama_mapel: "INFO/BK" },
  { id_mapel: 11, nama_mapel: "PAI/BHQ" },
  { id_mapel: 12, nama_mapel: "PJOK" }
];

function showMessage(text, type = "success") {
  if (!messageBox) return;
  messageBox.textContent = text;
  messageBox.className = `message-box ${type}`;
}

function clearMessage() {
  if (!messageBox) return;
  messageBox.textContent = "";
  messageBox.className = "message-box";
}

function normalisasiSemester(value) {
  const semesterText = String(value).trim().toLowerCase();
  if (semesterText === "ganjil") return 1;
  if (semesterText === "genap") return 2;
  return Number(value);
}

function tampilSemester(value) {
  const semester = Number(value);
  if (semester === 1) return "Ganjil";
  if (semester === 2) return "Genap";
  return value || "-";
}

function escapeHtml(str) {
  if (!str) return "";
  return str.replace(/[&<>]/g, function(m) {
    if (m === '&') return '&amp;';
    if (m === '<') return '&lt;';
    if (m === '>') return '&gt;';
    return m;
  });
}

function updateRekap() {
  const totalSiswaEl = document.getElementById("totalSiswa");
  const rataRataEl = document.getElementById("rataRata");
  const nilaiTertinggiEl = document.getElementById("nilaiTertinggi");
  const nilaiTerendahEl = document.getElementById("nilaiTerendah");
  const totalHadirEl = document.getElementById("totalHadir");
  const totalAlfaEl = document.getElementById("totalAlfa");

  if (!totalSiswaEl || !rataRataEl || !nilaiTertinggiEl || !nilaiTerendahEl || !totalHadirEl || !totalAlfaEl) {
    return;
  }

  if (dataNilai.length === 0) {
    totalSiswaEl.textContent = "0";
    rataRataEl.textContent = "0";
    nilaiTertinggiEl.textContent = "0";
    nilaiTerendahEl.textContent = "0";
    totalHadirEl.textContent = "0";
    totalAlfaEl.textContent = "0";
    return;
  }

  const nilaiList = dataNilai.map(item => Number(item.nilai_angka));
  const totalNilai = nilaiList.reduce((sum, nilai) => sum + nilai, 0);
  const rataRata = totalNilai / dataNilai.length;
  const nilaiTertinggi = Math.max(...nilaiList);
  const nilaiTerendah = Math.min(...nilaiList);
  const totalHadir = dataNilai.reduce((sum, item) => sum + Number(item.hadir), 0);
  const totalAlfa = dataNilai.reduce((sum, item) => sum + Number(item.alfa), 0);

  totalSiswaEl.textContent = dataNilai.length;
  rataRataEl.textContent = rataRata.toFixed(2);
  nilaiTertinggiEl.textContent = nilaiTertinggi;
  nilaiTerendahEl.textContent = nilaiTerendah;
  totalHadirEl.textContent = totalHadir;
  totalAlfaEl.textContent = totalAlfa;
}

// ========== FUNGSI UNTUK MODE WALI KELAS ==========
function aturTampilanWaliKelas() {
  const mode = modeNilai ? modeNilai.value : "mapel";
  
  if (!uploadSection) {
    uploadSection = document.querySelector(".import-card");
  }
  
  if (mode === "wali") {
    if (uploadSection) {
      uploadSection.style.display = "none";
    }
    
    const filterGrid = document.querySelector(".nilai-filter-grid");
    if (filterGrid && !document.getElementById("btnCetakSemuaRapor")) {
      const btn = document.createElement("button");
      btn.id = "btnCetakSemuaRapor";
      btn.className = "btn btn-primary";
      btn.innerHTML = '<i class="bi bi-printer"></i> Cetak Semua Rapor Kelas';
      btn.style.marginLeft = "auto";
      btn.onclick = function() {
        const idKelas = filterKelasWali ? filterKelasWali.value : "";
        if (!idKelas) {
          alert("Pilih kelas wali terlebih dahulu.");
          return;
        }
        const url = `cetak_nilai_wali.html?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&id_kelas=${idKelas}`;
        window.open(url, "_blank");
      };
      filterGrid.appendChild(btn);
    }
  } else {
    if (uploadSection) {
      uploadSection.style.display = "block";
    }
    const btnExist = document.getElementById("btnCetakSemuaRapor");
    if (btnExist) {
      btnExist.remove();
    }
  }
}

// ========== RENDER TABLE (LENGKAP DENGAN MODE WALI) ==========
function renderTable(filteredData = dataNilai) {
  if (!nilaiTableBody) return;
  
  if (filteredData.length === 0) {
    nilaiTableBody.innerHTML = `<tr><td colspan="11" class="empty-state">Belum ada data yang sesuai.</td></tr>`;
    return;
  }

  const siswaMap = {};
  filteredData.forEach(item => {
    const key = `${item.id_siswa}-${item.semester}`;
    if (!siswaMap[key]) {
      siswaMap[key] = {
        id_siswa: item.id_siswa,
        nama_siswa: item.nama_siswa || "-",
        nama_kelas: item.nama_kelas || "-",
        semester: item.semester,
        semester_text: item.semester_text || tampilSemester(item.semester),
        total_nilai: 0,
        jumlah_mapel: 0,
        hadir: Number(item.hadir || 0),
        izin: Number(item.izin || 0),
        sakit: Number(item.sakit || 0),
        alfa: Number(item.alfa || 0)
      };
    }
    siswaMap[key].total_nilai += Number(item.nilai_angka || 0);
    siswaMap[key].jumlah_mapel += 1;
  });

  const dataRingkas = Object.values(siswaMap);
  nilaiTableBody.innerHTML = dataRingkas.map((item, index) => {
    const nilaiTampil = item.jumlah_mapel > 0 ? (item.total_nilai / item.jumlah_mapel).toFixed(2) : "0";
    return `
      <tr>
        <td class="center">${index + 1}</td>
        <td>${item.id_siswa}</td>
        <td><strong>${escapeHtml(item.nama_siswa)}</strong></td>
        <td>${escapeHtml(item.nama_kelas)}</td>
        <td class="center">${item.semester_text}</td>
        <td class="center">${nilaiTampil}</td>
        <td class="center">${item.hadir}</td>
        <td class="center">${item.izin}</td>
        <td class="center">${item.sakit}</td>
        <td class="center">${item.alfa}</td>
        <td class="center">
          <button type="button" class="btn-cetak-row" onclick="cetakNilaiSiswa(${item.id_siswa})">
            <i class="bi bi-printer"></i> Cetak Nilai
          </button>
        </td>
      </tr>
    `;
  }).join("");
}
  
// CETAK RAPOR PER SISWA (WALI KELAS)
window.cetakRaporPerSiswa = function(idSiswa) {
  const idKelas = filterKelasWali ? filterKelasWali.value : "";
  if (!idKelas) {
    alert("Pilih kelas wali terlebih dahulu.");
    return;
  }
  const url = `cetak_nilai_wali.html?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&id_kelas=${idKelas}&id_siswa=${idSiswa}`;
  window.open(url, "_blank");
};

// CETAK NILAI SISWA (GURU MAPEL)
function cetakNilaiSiswa(idSiswa) {
  const idKelas = filterKelasWali ? filterKelasWali.value : "";
  if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
    return;
  }
  if (!idKelas) {
    alert("Pilih kelas terlebih dahulu.");
    return;
  }
  const url = `cetak_nilai_wali.html?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&id_kelas=${idKelas}&id_siswa=${idSiswa}`;
  window.open(url, "_blank");
}

// ========== PARSE CSV ==========
function bersihkanHeader(value) {
  return String(value || "").replace(/^\uFEFF/, "").trim().toLowerCase().replace(/\s+/g, " ");
}

function ambilAngka(value, defaultValue = 0) {
  const text = String(value ?? "").trim();
  if (text === "") return defaultValue;
  const angka = Number(text.replace(",", "."));
  return isNaN(angka) ? defaultValue : angka;
}

function deteksiDelimiter(line) {
  const jumlahKoma = (line.match(/,/g) || []).length;
  const jumlahTitikKoma = (line.match(/;/g) || []).length;
  return jumlahTitikKoma > jumlahKoma ? ";" : ",";
}

// ========== FORMAT FLEKSIBEL UNTUK TEMPLAT GURU ==========
function parseCSVFormatTemplateGuru(lines, headers, delimiter) {
  console.log("--- Checking format template guru ---");
  
  // Cari kolom yang wajib ada
  const idSiswaIndex = headers.findIndex(h => h.includes("id_siswa") || h.includes("id siswa"));
  const namaSiswaIndex = headers.findIndex(h => h.includes("nama_siswa") || h.includes("nama siswa"));
  const idMapelIndex = headers.findIndex(h => h.includes("id_mapel") || h.includes("id mapel"));
  const namaMapelIndex = headers.findIndex(h => h.includes("nama_mapel") || h.includes("nama mapel"));
  const semesterIndex = headers.findIndex(h => h.includes("semester"));
  const nilaiIndex = headers.findIndex(h => h.includes("nilai_angka") || h.includes("nilai") || h.includes("nilai angka"));
  const hadirIndex = headers.findIndex(h => h.includes("hadir"));
  const izinIndex = headers.findIndex(h => h.includes("izin"));
  const sakitIndex = headers.findIndex(h => h.includes("sakit"));
  const alfaIndex = headers.findIndex(h => h.includes("alfa"));
  
  console.log("Detected columns:", { idSiswaIndex, namaSiswaIndex, idMapelIndex, namaMapelIndex, semesterIndex, nilaiIndex, hadirIndex, izinIndex, sakitIndex, alfaIndex });
  
  if (idSiswaIndex < 0 || idMapelIndex < 0 || semesterIndex < 0 || nilaiIndex < 0) {
    console.log("Missing required columns for template guru");
    return null;
  }
  
  const result = [];
  for (let i = 1; i < lines.length; i++) {
    if (!lines[i].trim()) continue;
    const row = parseCSVLine(lines[i], delimiter);
    
    const id_siswa = Number(row[idSiswaIndex]);
    const nama_siswa = namaSiswaIndex >= 0 ? row[namaSiswaIndex] : "";
    const id_mapel = Number(row[idMapelIndex]);
    const nama_mapel = namaMapelIndex >= 0 ? row[namaMapelIndex] : "";
    const semester = normalisasiSemester(row[semesterIndex]);
    let nilai_angka = ambilAngka(row[nilaiIndex], NaN);
    
    // Jika nilai masih NaN, coba ambil dari kolom lain
    if (isNaN(nilai_angka)) {
      const kolomNilaiLain = headers.findIndex(h => h.includes("nilai") && !h.includes("_angka"));
      if (kolomNilaiLain >= 0) {
        nilai_angka = ambilAngka(row[kolomNilaiLain], NaN);
      }
    }
    
    const hadir = hadirIndex >= 0 ? ambilAngka(row[hadirIndex], 0) : 0;
    const izin = izinIndex >= 0 ? ambilAngka(row[izinIndex], 0) : 0;
    const sakit = sakitIndex >= 0 ? ambilAngka(row[sakitIndex], 0) : 0;
    const alfa = alfaIndex >= 0 ? ambilAngka(row[alfaIndex], 0) : 0;
    
    if (!id_siswa || !id_mapel || !semester || isNaN(nilai_angka)) {
      console.log(`Skipping row ${i}: id_siswa=${id_siswa}, id_mapel=${id_mapel}, semester=${semester}, nilai=${nilai_angka}`);
      continue;
    }
    
    result.push({ id_siswa, nama_siswa, id_mapel, nama_mapel, semester, nilai_angka, hadir, izin, sakit, alfa });
  }
  
  console.log(`Template guru result: ${result.length} valid rows`);
  return result;
}

// ========== FORMAT FLEKSIBEL UNTUK TEMPLAT WALI ==========
function parseCSVFormatTemplateWali(lines, headers, delimiter) {
  console.log("--- Checking format template wali ---");
  
  const idSiswaIndex = headers.findIndex(h => h.includes("id_siswa") || h.includes("id siswa"));
  const namaSiswaIndex = headers.findIndex(h => h.includes("nama_siswa") || h.includes("nama siswa"));
  const semesterIndex = headers.findIndex(h => h.includes("semester"));
  const hadirIndex = headers.findIndex(h => h.includes("hadir"));
  const izinIndex = headers.findIndex(h => h.includes("izin"));
  const sakitIndex = headers.findIndex(h => h.includes("sakit"));
  const alfaIndex = headers.findIndex(h => h.includes("alfa"));
  
  if (idSiswaIndex < 0 || semesterIndex < 0) {
    console.log("Missing required columns for template wali");
    return null;
  }
  
  const result = [];
  for (let i = 1; i < lines.length; i++) {
    if (!lines[i].trim()) continue;
    const row = parseCSVLine(lines[i], delimiter);
    
    const id_siswa = Number(row[idSiswaIndex]);
    const nama_siswa = namaSiswaIndex >= 0 ? row[namaSiswaIndex] : "";
    const semester = normalisasiSemester(row[semesterIndex]);
    const hadir = hadirIndex >= 0 ? ambilAngka(row[hadirIndex], 0) : 0;
    const izin = izinIndex >= 0 ? ambilAngka(row[izinIndex], 0) : 0;
    const sakit = sakitIndex >= 0 ? ambilAngka(row[sakitIndex], 0) : 0;
    const alfa = alfaIndex >= 0 ? ambilAngka(row[alfaIndex], 0) : 0;
    
    if (!id_siswa || !semester) continue;
    
    // Loop semua mapel yang ada di header
    daftarMapelCsv.forEach(mapel => {
      const mapelIndex = headers.findIndex(h => 
        h === mapel.nama_mapel.toLowerCase() || 
        h === mapel.nama_mapel.toLowerCase().replace(".", "") ||
        h.includes(mapel.nama_mapel.toLowerCase())
      );
      
      if (mapelIndex < 0) return;
      
      const nilaiText = String(row[mapelIndex] ?? "").trim();
      if (nilaiText === "") return;
      
      const nilai_angka = ambilAngka(nilaiText, NaN);
      if (isNaN(nilai_angka)) return;
      
      result.push({ 
        id_siswa, nama_siswa, 
        id_mapel: mapel.id_mapel, 
        nama_mapel: mapel.nama_mapel, 
        semester, 
        nilai_angka, 
        hadir, izin, sakit, alfa 
      });
    });
  }
  
  console.log(`Template wali result: ${result.length} valid rows`);
  return result;
}

function parseCSV(text) {
  console.log("=== START PARSE CSV ===");
  console.log("Text length:", text.length);
  
  let lines = text.trim().split(/\r?\n/);
  console.log("Total lines:", lines.length);
  console.log("First line:", lines[0]);
  
  if (lines.length < 2) throw new Error("File CSV kosong atau tidak valid.");
  lines[0] = lines[0].replace(/^\uFEFF/, "");
  if (lines[0].trim().toLowerCase() === "sep=,") lines = lines.slice(1);
  if (lines[0].trim().toLowerCase() === "sep=;") lines = lines.slice(1);
  
  const delimiter = deteksiDelimiter(lines[0]);
  console.log("Detected delimiter:", delimiter);
  
  const headers = parseCSVLine(lines[0], delimiter).map(header => bersihkanHeader(header));
  console.log("Headers:", headers);
  
  // Coba berbagai format
  const formatBiasa = parseCSVFormatBiasa(lines, headers, delimiter);
  if (formatBiasa !== null && formatBiasa.length > 0) {
    console.log("Using format biasa, rows:", formatBiasa.length);
    return formatBiasa;
  }
  
  const formatTemplateGuru = parseCSVFormatTemplateGuru(lines, headers, delimiter);
  if (formatTemplateGuru !== null && formatTemplateGuru.length > 0) {
    console.log("Using format template guru, rows:", formatTemplateGuru.length);
    return formatTemplateGuru;
  }
  
  const formatWali = parseCSVFormatWali(lines, headers, delimiter);
  if (formatWali !== null && formatWali.length > 0) {
    console.log("Using format wali, rows:", formatWali.length);
    return formatWali;
  }
  
  const formatTemplateWali = parseCSVFormatTemplateWali(lines, headers, delimiter);
  if (formatTemplateWali !== null && formatTemplateWali.length > 0) {
    console.log("Using format template wali, rows:", formatTemplateWali.length);
    return formatTemplateWali;
  }
  
  console.log("No valid format detected!");
  console.log("Headers found:", headers);
  throw new Error("Format CSV tidak dikenali. Pastikan menggunakan template yang benar atau periksa kembali kolom header CSV Anda.");
}

function parseCSVFormatBiasa(lines, headers, delimiter) {
  console.log("--- Checking format biasa ---");
  
  const requiredHeaders = ["id_siswa", "id_mapel", "semester", "nilai_angka", "hadir", "izin", "sakit", "alfa"];
  const valid = requiredHeaders.every(header => headers.includes(header));
  console.log("Required headers present:", valid);
  
  if (!valid) {
    console.log("Missing headers. Required:", requiredHeaders);
    console.log("Actual headers:", headers);
    return null;
  }

  const idSiswaIndex = headers.indexOf("id_siswa");
  const namaSiswaIndex = headers.indexOf("nama_siswa");
  const idMapelIndex = headers.indexOf("id_mapel");
  const namaMapelIndex = headers.indexOf("nama_mapel");
  const semesterIndex = headers.indexOf("semester");
  const nilaiAngkaIndex = headers.indexOf("nilai_angka");
  const hadirIndex = headers.indexOf("hadir");
  const izinIndex = headers.indexOf("izin");
  const sakitIndex = headers.indexOf("sakit");
  const alfaIndex = headers.indexOf("alfa");

  const result = [];
  for (let i = 1; i < lines.length; i++) {
    if (!lines[i].trim()) continue;
    const row = parseCSVLine(lines[i], delimiter);
    
    const id_siswa = Number(row[idSiswaIndex]);
    const nama_siswa = namaSiswaIndex >= 0 ? row[namaSiswaIndex] : "";
    const id_mapel = Number(row[idMapelIndex]);
    const nama_mapel = namaMapelIndex >= 0 ? row[namaMapelIndex] : "";
    const semester = normalisasiSemester(row[semesterIndex]);
    const nilai_angka = ambilAngka(row[nilaiAngkaIndex], NaN);
    const hadir = ambilAngka(row[hadirIndex], 0);
    const izin = ambilAngka(row[izinIndex], 0);
    const sakit = ambilAngka(row[sakitIndex], 0);
    const alfa = ambilAngka(row[alfaIndex], 0);
    
    if (!id_siswa || !id_mapel || !semester || isNaN(nilai_angka)) continue;
    result.push({ id_siswa, nama_siswa, id_mapel, nama_mapel, semester, nilai_angka, hadir, izin, sakit, alfa });
  }
  
  console.log(`Format biasa result: ${result.length} valid rows`);
  return result;
}

function parseCSVFormatWali(lines, headers, delimiter) {
  const idSiswaIndex = headers.indexOf("id_siswa");
  const namaSiswaIndex = headers.indexOf("nama_siswa");
  const semesterIndex = headers.indexOf("semester");
  const hadirIndex = headers.indexOf("hadir");
  const izinIndex = headers.indexOf("izin");
  const sakitIndex = headers.indexOf("sakit");
  const alfaIndex = headers.indexOf("alfa");
  if (idSiswaIndex < 0 || namaSiswaIndex < 0 || semesterIndex < 0 || hadirIndex < 0 || izinIndex < 0 || sakitIndex < 0 || alfaIndex < 0) return null;

  const result = [];
  for (let i = 1; i < lines.length; i++) {
    if (!lines[i].trim()) continue;
    const row = parseCSVLine(lines[i], delimiter);
    const id_siswa = Number(row[idSiswaIndex]);
    const nama_siswa = row[namaSiswaIndex] || "";
    const semester = normalisasiSemester(row[semesterIndex]);
    const hadir = ambilAngka(row[hadirIndex], 0);
    const izin = ambilAngka(row[izinIndex], 0);
    const sakit = ambilAngka(row[sakitIndex], 0);
    const alfa = ambilAngka(row[alfaIndex], 0);
    if (!id_siswa || !semester) continue;
    daftarMapelCsv.forEach(mapel => {
      const namaMapelHeader = bersihkanHeader(mapel.nama_mapel);
      const mapelIndex = headers.indexOf(namaMapelHeader);
      if (mapelIndex < 0) return;
      const nilaiText = String(row[mapelIndex] ?? "").trim();
      if (nilaiText === "") return;
      const nilai_angka = ambilAngka(nilaiText, NaN);
      if (isNaN(nilai_angka)) return;
      result.push({ id_siswa, nama_siswa, id_mapel: mapel.id_mapel, nama_mapel: mapel.nama_mapel, semester, nilai_angka, hadir, izin, sakit, alfa });
    });
  }
  return result;
}

function parseCSV(text) {
  console.log("=== START PARSE CSV ===");
  console.log("Text length:", text.length);
  
  let lines = text.trim().split(/\r?\n/);
  console.log("Total lines:", lines.length);
  console.log("First line:", lines[0]);
  
  if (lines.length < 2) throw new Error("File CSV kosong atau tidak valid.");
  lines[0] = lines[0].replace(/^\uFEFF/, "");
  if (lines[0].trim().toLowerCase() === "sep=,") lines = lines.slice(1);
  if (lines[0].trim().toLowerCase() === "sep=;") lines = lines.slice(1);
  
  const delimiter = deteksiDelimiter(lines[0]);
  console.log("Detected delimiter:", delimiter);
  
  const headers = parseCSVLine(lines[0], delimiter).map(header => bersihkanHeader(header));
  console.log("Headers:", headers);
  
  const formatBiasa = parseCSVFormatBiasa(lines, headers, delimiter);
  if (formatBiasa !== null && formatBiasa.length > 0) {
    console.log("Using format biasa, rows:", formatBiasa.length);
    return formatBiasa;
  }
  
  const formatWali = parseCSVFormatWali(lines, headers, delimiter);
  if (formatWali !== null && formatWali.length > 0) {
    console.log("Using format wali, rows:", formatWali.length);
    return formatWali;
  }
  
  console.log("No valid format detected!");
  throw new Error("Belum ada nilai yang bisa disimpan. Isi dulu kolom nilai mapel di file CSV, lalu simpan ulang sebagai CSV.");
}

// ========== DROPDOWN ==========
function isiDropdownWaliKelas(waliKelas) {
  if (!filterKelasWali) return;
  const nilaiSebelumnya = filterKelasWali.value;
  filterKelasWali.innerHTML = "";
  waliKelas.forEach(kelas => {
    filterKelasWali.innerHTML += `<option value="${kelas.id_kelas}">Kelas ${kelas.nama_kelas}</option>`;
  });
  if (nilaiSebelumnya) filterKelasWali.value = nilaiSebelumnya;
}

function isiDropdownKelasMapel(kelasMapel) {
  if (!filterKelasWali) return;
  const nilaiSebelumnya = filterKelasWali.value;
  filterKelasWali.innerHTML = "";
  kelasMapel.forEach(kelas => {
    filterKelasWali.innerHTML += `<option value="${kelas.id_kelas}">Kelas ${kelas.nama_kelas}</option>`;
  });
  if (nilaiSebelumnya) filterKelasWali.value = nilaiSebelumnya;
}

function aturTampilanMode() {
  const mode = modeNilai ? modeNilai.value : "mapel";
  
  const filterKelasGroup = document.getElementById("filterKelasGroup");
  const filterKelas = document.getElementById("filterKelas");
  
  if (mode === "wali") {
    if (filterKelasGroup) filterKelasGroup.style.display = "none";
    if (window.waliKelasData && window.waliKelasData.length > 0) {
      if (filterKelas) filterKelas.value = window.waliKelasData[0].id_kelas;
    }
    if (filterKelasWaliLabel) filterKelasWaliLabel.textContent = "Kelas Wali";
  } else {
    if (filterKelasGroup) filterKelasGroup.style.display = "flex";
    if (filterKelasWaliLabel) filterKelasWaliLabel.textContent = "Kelas Mapel Saya";
  }
}

// ========== TAMPILKAN NAMA FILE YANG DIPILIH ==========
const fileInputElem = document.getElementById("fileInput");
const selectedFileNameSpan = document.getElementById("selectedFileName");

if (fileInputElem && selectedFileNameSpan) {
  fileInputElem.addEventListener("change", function(e) {
    if (fileInputElem.files.length > 0) {
      selectedFileNameSpan.textContent = fileInputElem.files[0].name;
    } else {
      selectedFileNameSpan.textContent = "Belum ada file dipilih";
    }
  });
}

// ========== LOAD DATA ==========
function loadNilaiDatabase() {
  if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
    return;
  }

  const mode = modeNilai ? modeNilai.value : "mapel";
  const idKelas = filterKelasWali ? filterKelasWali.value : "";

  if (mode === "wali" && !idKelas) {
    if (nilaiTableBody) {
      nilaiTableBody.innerHTML = `<tr><td colspan="5" class="empty-state">Pilih kelas wali terlebih dahulu.</td></tr>`;
    }
    return;
  }

  let url = `get_nilai.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&mode=${mode}`;
  if (idKelas) url += `&id_kelas=${idKelas}`;

  fetch(url)
    .then(res => res.json())
    .then(result => {
      if (result.status === "success") {
        dataNilai = result.data || [];
        
        if (result.wali_kelas && result.wali_kelas.length > 0) {
          window.waliKelasData = result.wali_kelas;
        }
        
        if (result.is_wali_kelas === false) {
          const modeSelect = document.getElementById("modeNilai");
          if (modeSelect) {
            const waliOption = modeSelect.querySelector('option[value="wali"]');
            if (waliOption) {
              waliOption.style.display = "none";
            }
            modeSelect.value = "mapel";
            modeSelect.dispatchEvent(new Event('change'));
          }
        } else {
          const modeSelect = document.getElementById("modeNilai");
          if (modeSelect) {
            const waliOption = modeSelect.querySelector('option[value="wali"]');
            if (waliOption) {
              waliOption.style.display = "";
            }
          }
        }
        
        aturTampilanWaliKelas();
        if (filterWaliKelasBox) {
          filterWaliKelasBox.style.display = "block";
          if (mode === "wali") {
            isiDropdownWaliKelas(result.wali_kelas || []);
          } else {
            isiDropdownKelasMapel(result.kelas_mapel || []);
          }
        }
        aturTampilanMode();
        renderTable();
        updateRekap();
      } else {
        showMessage(result.message, "error");
      }
    })
    .catch(err => {
      console.error("Gagal load nilai:", err);
      showMessage("Gagal memuat data nilai.", "error");
    });
}

function simpanNilaiKeDatabase() {
  const formData = new FormData();
  formData.append("id_guru", idGuruLogin);
  formData.append("role_id", roleIdLogin);
  formData.append("data_nilai", JSON.stringify(dataNilai));
  return fetch("upload_nilai.php", { method: "POST", body: formData }).then(res => res.json());
}

function filterSearchNilai() {
  if (!searchInput) return;
  const keyword = searchInput.value.toLowerCase();
  const filtered = dataNilai.filter(item =>
    String(item.id_siswa).toLowerCase().includes(keyword) ||
    String(item.nama_siswa || "").toLowerCase().includes(keyword) ||
    String(item.id_mapel).toLowerCase().includes(keyword) ||
    String(item.nama_mapel || "").toLowerCase().includes(keyword) ||
    String(item.nama_kelas || "").toLowerCase().includes(keyword) ||
    String(item.semester_text || tampilSemester(item.semester)).toLowerCase().includes(keyword)
  );
  renderTable(filtered);
}

// ========== EVENT LISTENERS ==========
if (uploadBtn) {
  uploadBtn.addEventListener("click", () => {
    console.log("=== UPLOAD BUTTON CLICKED ===");
    clearMessage();
    
    if (!idGuruLogin || roleIdLogin !== "2") {
      alert("Silakan login sebagai guru terlebih dahulu.");
      window.location.href = "../login.html";
      return;
    }
    
    const file = fileInput.files[0];
    console.log("Selected file:", file ? file.name : "No file");
    
    if (!file) {
      showMessage("Pilih file CSV terlebih dahulu.", "error");
      return;
    }
    
    if (!file.name.toLowerCase().endsWith(".csv")) {
      showMessage("File harus berformat .csv", "error");
      return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
      try {
        const text = e.target.result;
        console.log("File loaded, length:", text.length);
        console.log("First 300 chars:", text.substring(0, 300));
        
        dataNilai = parseCSV(text);
        console.log("Parsed dataNilai length:", dataNilai.length);
        console.log("Sample parsed data:", dataNilai[0]);
        
        if (dataNilai.length === 0) {
          showMessage("Tidak ada data valid yang bisa diimport. Periksa format CSV Anda.", "error");
          return;
        }
        
        renderTable();
        updateRekap();
        showMessage("Sedang menyimpan data nilai ke database...", "success");
        
        simpanNilaiKeDatabase()
          .then(result => {
            console.log("Save result:", result);
            if (result.status === "success") {
              showMessage(`Simpan nilai berhasil. Data baru: ${result.inserted || 0}, diperbarui: ${result.updated || 0}, dilewati: ${result.skipped || 0}.`, "success");
              if (result.errors && result.errors.length > 0) {
                console.warn("Errors:", result.errors);
              }
              loadNilaiDatabase();
            } else {
              showMessage(result.message, "error");
            }
          })
          .catch(err => {
            console.error("Gagal simpan nilai:", err);
            showMessage("Gagal menyimpan nilai ke database.", "error");
          });
      } catch (error) {
        console.error("Parse error:", error);
        showMessage(error.message, "error");
      }
    };
    reader.onerror = function(err) {
      console.error("File read error:", err);
      showMessage("Gagal membaca file.", "error");
    };
    reader.readAsText(file);
  });
}

if (searchInput) {
  searchInput.addEventListener("input", filterSearchNilai);
}

if (modeNilai) {
  modeNilai.addEventListener("change", function() {
    aturTampilanMode();
    aturTampilanWaliKelas();
    loadNilaiDatabase();
  });
}

if (filterKelasWali) {
  filterKelasWali.addEventListener("change", loadNilaiDatabase);
}

if (downloadTemplateBtn) {
  downloadTemplateBtn.addEventListener("click", () => {
    if (!idGuruLogin || roleIdLogin !== "2") {
      alert("Silakan login sebagai guru terlebih dahulu.");
      window.location.href = "../login.html";
      return;
    }

    const idKelas = filterKelasWali ? filterKelasWali.value : "";

    if (!idKelas) {
      alert("Pilih kelas terlebih dahulu.");
      return;
    }

    const templateUrl = `download_template_nilai.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&mode=mapel&id_kelas=${idKelas}`;
    window.location.href = templateUrl;
  });
}

loadNilaiDatabase();