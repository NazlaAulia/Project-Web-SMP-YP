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
      </table>
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

// ========== FUNGSI PARSE CSV ==========
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

function parseCSVLine(line, delimiter = ",") {
  const result = [];
  let current = "";
  let insideQuote = false;
  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    const nextChar = line[i + 1];
    if (char === '"' && nextChar === '"') {
      current += '"';
      i++;
      continue;
    }
    if (char === '"') {
      insideQuote = !insideQuote;
      continue;
    }
    if (char === delimiter && !insideQuote) {
      result.push(current.trim());
      current = "";
      continue;
    }
    current += char;
  }
  result.push(current.trim());
  return result;
}

// ========== PARSE CSV UTAMA ==========
function parseCSV(text) {
  console.log("=== START PARSE CSV ===");
  
  let lines = text.trim().split(/\r?\n/);
  if (lines.length < 2) throw new Error("File CSV kosong atau tidak valid.");
  
  lines[0] = lines[0].replace(/^\uFEFF/, "");
  if (lines[0].trim().toLowerCase().startsWith("sep=")) lines = lines.slice(1);
  
  const delimiter = deteksiDelimiter(lines[0]);
  console.log("Delimiter:", delimiter);
  
  let headers = parseCSVLine(lines[0], delimiter);
  headers = headers.map(h => bersihkanHeader(h));
  console.log("Headers:", headers);
  
  const result = [];
  
  for (let i = 1; i < lines.length; i++) {
    if (!lines[i].trim()) continue;
    
    const row = parseCSVLine(lines[i], delimiter);
    if (row.length < 3) continue;
    
    // Ambil kolom dasar (asumsi format: id_siswa, nama_siswa, semester, ...)
    const id_siswa = Number(row[0] || 0);
    const nama_siswa = row[1] || "";
    const semester = normalisasiSemester(row[2] || 1);
    const hadir = ambilAngka(row[3], 0);
    const izin = ambilAngka(row[4], 0);
    const sakit = ambilAngka(row[5], 0);
    const alfa = ambilAngka(row[6], 0);
    
    if (!id_siswa) {
      console.log(`Skipping row ${i}: no valid student ID`);
      continue;
    }
    
    // Cek apakah ini format wali (ada kolom mapel di header)
    const mapelHeaders = daftarMapelCsv.map(m => m.nama_mapel.toLowerCase());
    const hasMapelColumns = mapelHeaders.some(mh => headers.includes(mh));
    
    if (hasMapelColumns) {
      // Format wali kelas: setiap baris = satu siswa dengan nilai semua mapel
      for (let j = 0; j < daftarMapelCsv.length; j++) {
        const mapel = daftarMapelCsv[j];
        const mapelHeader = mapel.nama_mapel.toLowerCase();
        const colIndex = headers.indexOf(mapelHeader);
        
        if (colIndex >= 0 && colIndex < row.length) {
          const nilai_angka = ambilAngka(row[colIndex], NaN);
          if (!isNaN(nilai_angka) && nilai_angka >= 0 && nilai_angka <= 100) {
            result.push({
              id_siswa, nama_siswa,
              id_mapel: mapel.id_mapel,
              nama_mapel: mapel.nama_mapel,
              semester,
              nilai_angka,
              hadir, izin, sakit, alfa
            });
          }
        }
      }
    } else {
      // Format biasa: setiap baris = satu mapel
      const id_mapel = Number(row[7] || 0);
      let nilai_angka = ambilAngka(row[8], NaN);
      
      // Jika kolom 8 tidak ada, coba kolom lain
      if (isNaN(nilai_angka) && row.length > 9) {
        nilai_angka = ambilAngka(row[9], NaN);
      }
      
      if (id_mapel && !isNaN(nilai_angka) && nilai_angka >= 0 && nilai_angka <= 100) {
        const mapel = daftarMapelCsv.find(m => m.id_mapel === id_mapel);
        result.push({
          id_siswa, nama_siswa,
          id_mapel: id_mapel,
          nama_mapel: mapel ? mapel.nama_mapel : "Mapel " + id_mapel,
          semester,
          nilai_angka,
          hadir, izin, sakit, alfa
        });
      }
    }
  }
  
  console.log(`Parsed ${result.length} nilai records`);
  
  if (result.length === 0) {
    throw new Error("Tidak ada data nilai yang valid. Periksa format CSV Anda. Pastikan file berisi ID Siswa, Nama, Semester, dan Nilai.");
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