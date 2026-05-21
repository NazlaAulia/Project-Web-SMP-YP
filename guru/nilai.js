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

function aturTampilanWaliKelas() {
  const mode = modeNilai ? modeNilai.value : "mapel";
  
  if (!uploadSection) {
    uploadSection = document.querySelector(".import-card");
  }
  
  const btnCetakGroup = document.getElementById("btnCetakSemuaRaporGroup");
  
  if (mode === "wali") {
    if (uploadSection) {
      uploadSection.style.display = "none";
    }
    
    // Tampilkan tombol cetak di sebelah kanan filter
    if (btnCetakGroup) {
      btnCetakGroup.style.display = "flex";
    }
    
    // Pastikan event listener untuk tombol cetak
    const btnCetak = document.getElementById("btnCetakSemuaRapor");
    if (btnCetak) {
      btnCetak.onclick = function() {
        const idKelas = filterKelasWali ? filterKelasWali.value : "";
        if (!idKelas) {
          alert("Pilih kelas wali terlebih dahulu.");
          return;
        }
        const url = `cetak_nilai_wali.html?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&id_kelas=${idKelas}`;
        window.open(url, "_blank");
      };
    }
  } else {
    if (uploadSection) {
      uploadSection.style.display = "block";
    }
    
    // Sembunyikan tombol cetak di mode mapel
    if (btnCetakGroup) {
      btnCetakGroup.style.display = "none";
    }
  }
}

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
  
window.cetakRaporPerSiswa = function(idSiswa) {
  const idKelas = filterKelasWali ? filterKelasWali.value : "";
  if (!idKelas) {
    alert("Pilih kelas wali terlebih dahulu.");
    return;
  }
  const url = `cetak_nilai_wali.html?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&id_kelas=${idKelas}&id_siswa=${idSiswa}`;
  window.open(url, "_blank");
};

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

// ========== PARSE CSV - FIX DELIMITER DAN SEMESTER KOSONG ==========
function parseCSV(text) {
  console.log("=== START PARSE CSV ===");
  
  let lines = text.trim().split(/\r?\n/);
  if (lines.length < 2) throw new Error("File CSV kosong atau tidak valid.");
  
  // Hapus BOM jika ada
  lines[0] = lines[0].replace(/^\uFEFF/, "");
  
  // Hapus baris SEP= jika ada
  if (lines[0].trim().toLowerCase().startsWith("sep=")) lines = lines.slice(1);
  
  // Deteksi delimiter (koma atau titik koma)
  const delimiter = lines[0].includes(';') ? ';' : ',';
  console.log("Delimiter terdeteksi:", delimiter === ';' ? 'TITIK KOMA (;) ⚠️' : 'KOMA (,) ✅');
  
  // Fungsi parse baris CSV (handle quotes)
  function parseRow(row) {
    const result = [];
    let current = "";
    let inQuotes = false;
    for (let i = 0; i < row.length; i++) {
      const char = row[i];
      if (char === '"') {
        inQuotes = !inQuotes;
      } else if (char === delimiter && !inQuotes) {
        result.push(current.trim());
        current = "";
      } else {
        current += char;
      }
    }
    result.push(current.trim());
    return result.map(v => v.replace(/^"|"$/g, ''));
  }
  
  // Baca header
  let headers = parseRow(lines[0]);
  let headerLower = headers.map(h => h.toLowerCase().trim());
  console.log("Headers:", headers);
  
  // Cari index kolom (case insensitive)
  let idxIdSiswa = -1, idxNama = -1, idxSemester = -1;
  let idxHadir = -1, idxIzin = -1, idxSakit = -1, idxAlfa = -1;
  let idxIdMapel = -1, idxNilai = -1;
  
  for (let i = 0; i < headerLower.length; i++) {
    const h = headerLower[i];
    if (h === 'id_siswa' || h === 'idsiswa' || h === 'id siswa' || h === 'nis' || h === 'id') idxIdSiswa = i;
    if (h === 'nama_siswa' || h === 'namasiswa' || h === 'nama siswa' || h === 'nama' || h === 'name') idxNama = i;
    if (h === 'semester' || h === 'smt' || h === 'sem') idxSemester = i;
    if (h === 'hadir' || h === 'kehadiran') idxHadir = i;
    if (h === 'izin' || h === 'ijin') idxIzin = i;
    if (h === 'sakit') idxSakit = i;
    if (h === 'alfa' || h === 'alpha' || h === 'alpa') idxAlfa = i;
    if (h === 'id_mapel' || h === 'idmapel' || h === 'id mapel' || h === 'kode mapel') idxIdMapel = i;
    if (h === 'nilai_angka' || h === 'nilaiangka' || h === 'nilai angka' || h === 'nilai' || h === 'score') idxNilai = i;
  }
  
  // Validasi kolom wajib
  if (idxIdSiswa === -1) throw new Error("Kolom ID Siswa tidak ditemukan");
  if (idxNama === -1) throw new Error("Kolom Nama tidak ditemukan");
  if (idxIdMapel === -1) throw new Error("Kolom ID Mapel tidak ditemukan");
  if (idxNilai === -1) throw new Error("Kolom Nilai tidak ditemukan");
  
  // Kolom semester opsional, jika tidak ada default ke 1
  const hasSemesterColumn = idxSemester !== -1;
  console.log("Kolom semester ditemukan:", hasSemesterColumn ? "YA" : "TIDAK (akan default ke 1)");
  
  console.log(`Mapping: id_siswa=${idxIdSiswa}, nama=${idxNama}, semester=${idxSemester}, id_mapel=${idxIdMapel}, nilai=${idxNilai}`);
  
  const result = [];
  let rowNumber = 1;
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim();
    if (line === "") continue;
    
    rowNumber = i + 1;
    const row = parseRow(line);
    
    if (row.length <= Math.max(idxIdSiswa, idxNama, idxIdMapel, idxNilai)) {
      console.log(`Row ${rowNumber}: kolom tidak cukup (${row.length} kolom), skip`);
      continue;
    }
    
    const id_siswa = parseInt(row[idxIdSiswa]);
    const nama_siswa = row[idxNama];
    
    // Semester: jika kolom ada tapi kosong/null, default ke 1
    let semester = 1;
    if (hasSemesterColumn && idxSemester < row.length && row[idxSemester] && row[idxSemester].trim() !== "") {
      semester = parseInt(row[idxSemester]);
    } else if (!hasSemesterColumn) {
      semester = 1; // default semester 1
    }
    
    const hadir = idxHadir !== -1 && idxHadir < row.length ? parseInt(row[idxHadir] || 0) : 0;
    const izin = idxIzin !== -1 && idxIzin < row.length ? parseInt(row[idxIzin] || 0) : 0;
    const sakit = idxSakit !== -1 && idxSakit < row.length ? parseInt(row[idxSakit] || 0) : 0;
    const alfa = idxAlfa !== -1 && idxAlfa < row.length ? parseInt(row[idxAlfa] || 0) : 0;
    const id_mapel = parseInt(row[idxIdMapel]);
    const nilai_angka = parseFloat(row[idxNilai]);
    
    // Validasi data
    if (isNaN(id_siswa) || id_siswa <= 0) {
      console.log(`Row ${rowNumber}: ID Siswa tidak valid (${row[idxIdSiswa]})`);
      continue;
    }
    
    if (isNaN(semester) || (semester !== 1 && semester !== 2)) {
      console.log(`Row ${rowNumber}: Semester tidak valid (${row[idxSemester] || 'kosong'}), default ke 1`);
      semester = 1;
    }
    
    if (isNaN(id_mapel) || id_mapel < 1 || id_mapel > 12) {
      console.log(`Row ${rowNumber}: ID Mapel harus 1-12 (${row[idxIdMapel]})`);
      continue;
    }
    
    if (isNaN(nilai_angka) || nilai_angka < 0 || nilai_angka > 100) {
      console.log(`Row ${rowNumber}: Nilai harus 0-100 (${row[idxNilai]})`);
      continue;
    }
    
    const mapel = daftarMapelCsv.find(m => m.id_mapel === id_mapel);
    
    result.push({
      id_siswa: id_siswa,
      nama_siswa: nama_siswa,
      semester: semester,
      hadir: hadir,
      izin: izin,
      sakit: sakit,
      alfa: alfa,
      id_mapel: id_mapel,
      nama_mapel: mapel ? mapel.nama_mapel : "Mapel " + id_mapel,
      nilai_angka: nilai_angka
    });
  }
  
  console.log(`Parsed ${result.length} data valid`);
  
  if (result.length === 0) {
    throw new Error("Tidak ada data nilai yang valid. Periksa format CSV Anda.");
  }
  
  return result;
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

// ========== TAMPILKAN NAMA FILE ==========
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
        dataNilai = parseCSV(text);
        
        if (dataNilai.length === 0) {
          showMessage("Tidak ada data valid yang bisa diimport.", "error");
          return;
        }
        
        renderTable();
        updateRekap();
        showMessage("Sedang menyimpan data nilai ke database...", "success");
        
        simpanNilaiKeDatabase()
          .then(result => {
            if (result.status === "success") {
              showMessage(`Simpan nilai berhasil. Data baru: ${result.inserted || 0}, diperbarui: ${result.updated || 0}, dilewati: ${result.skipped || 0}.`, "success");
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
    reader.readAsText(file, "UTF-8");
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