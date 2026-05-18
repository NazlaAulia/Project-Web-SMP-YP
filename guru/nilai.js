let dataNilai = [];
let uploadSection = null;
let currentMode = "mapel"; // 'mapel' atau 'wali'
let waliKelasList = [];
let mapelDiajarList = [];
let kelasMapelList = [];

const filterKelasLabel = document.getElementById("filterKelasLabel");
const filterTitle = document.getElementById("filterTitle");
const filterDesc = document.getElementById("filterDesc");
const tableDesc = document.getElementById("tableDesc");
const printBtn = document.getElementById("printBtn");
const fileInput = document.getElementById("fileInput");
const uploadBtn = document.getElementById("uploadBtn");
const downloadTemplateBtn = document.getElementById("downloadTemplateBtn");
const searchInput = document.getElementById("searchInput");
const messageBox = document.getElementById("messageBox");
const nilaiTableBody = document.getElementById("nilaiTableBody");
const filterKelas = document.getElementById("filterKelas");
const filterWaliKelasBox = document.getElementById("filterWaliKelasBox");
const tabPeranContainer = document.getElementById("tabPeranContainer");

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

// ========== FUNGSI ATUR TAMPILAN BERDASARKAN MODE ==========
function aturTampilanMode() {
  uploadSection = document.querySelector(".import-card");
  
  if (currentMode === "wali") {
    // Mode wali kelas: sembunyikan upload, tampilkan tombol cetak semua
    if (uploadSection) uploadSection.style.display = "none";
    if (filterTitle) filterTitle.textContent = "Cetak Rapor Siswa";
    if (filterDesc) filterDesc.textContent = "Pilih kelas wali untuk mencetak rapor siswa.";
    if (tableDesc) tableDesc.textContent = "Daftar siswa di kelas wali Anda.";
    if (filterKelasLabel) filterKelasLabel.textContent = "Kelas Wali";
    
    // Tambah tombol cetak semua rapor
    const filterGrid = document.querySelector(".nilai-filter-grid");
    if (filterGrid && !document.getElementById("btnCetakSemuaRapor")) {
      const btn = document.createElement("button");
      btn.id = "btnCetakSemuaRapor";
      btn.className = "btn btn-primary";
      btn.innerHTML = '<i class="bi bi-printer"></i> Cetak Semua Rapor Kelas';
      btn.style.marginLeft = "auto";
      btn.onclick = function() {
        const idKelas = filterKelas ? filterKelas.value : "";
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
    // Mode mapel: tampilkan upload
    if (uploadSection) uploadSection.style.display = "block";
    if (filterTitle) filterTitle.textContent = "Filter Data Nilai";
    if (filterDesc) filterDesc.textContent = "Pilih kelas untuk melihat data nilai mata pelajaran Anda.";
    if (tableDesc) tableDesc.textContent = "Preview data nilai.";
    if (filterKelasLabel) filterKelasLabel.textContent = "Kelas Mapel";
    
    const btnExist = document.getElementById("btnCetakSemuaRapor");
    if (btnExist) btnExist.remove();
  }
}

// ========== RENDER TABLE (WALI MODE vs MAPEL MODE) ==========
function renderTable(filteredData = dataNilai) {
  if (!nilaiTableBody) return;
  
  if (currentMode === "wali") {
    // Mode wali kelas: tampilkan daftar siswa + tombol cetak rapor
    const siswaUnik = {};
    filteredData.forEach(item => {
      if (!siswaUnik[item.id_siswa]) {
        siswaUnik[item.id_siswa] = {
          id_siswa: item.id_siswa,
          nama_siswa: item.nama_siswa || "-",
          nama_kelas: item.nama_kelas || "-"
        };
      }
    });
    
    const daftarSiswa = Object.values(siswaUnik);
    
    if (daftarSiswa.length === 0) {
      nilaiTableBody.innerHTML = `<tr><td colspan="5" class="empty-state">Tidak ada siswa di kelas ini.</td></tr>`;
      return;
    }
    
    nilaiTableBody.innerHTML = `
      <thead>
        <tr>
          <th>No</th>
          <th>ID Siswa</th>
          <th>Nama Siswa</th>
          <th>Kelas</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        ${daftarSiswa.map((siswa, index) => `
          <tr>
            <td class="center">${index + 1}</td>
            <td>${siswa.id_siswa}</td>
            <td><strong>${escapeHtml(siswa.nama_siswa)}</strong></td>
            <td>${escapeHtml(siswa.nama_kelas)}</td>
            <td>
              <button class="btn-cetak-row" onclick="cetakRaporPerSiswa(${siswa.id_siswa})">
                <i class="bi bi-printer"></i> Cetak Rapor
              </button>
            </td>
          </tr>
        `).join('')}
      </tbody>
    `;
    return;
  }
  
  // ========== MODE GURU MAPEL ==========
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

// ========== CETAK FUNCTIONS ==========
window.cetakRaporPerSiswa = function(idSiswa) {
  const idKelas = filterKelas ? filterKelas.value : "";
  if (!idKelas) {
    alert("Pilih kelas wali terlebih dahulu.");
    return;
  }
  const url = `cetak_nilai_wali.html?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&id_kelas=${idKelas}&id_siswa=${idSiswa}`;
  window.open(url, "_blank");
};

function cetakNilaiSiswa(idSiswa) {
  const idKelas = filterKelas ? filterKelas.value : "";
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

// ========== LOAD PERAN GURU DARI SERVER ==========
function loadPeranGuru() {
  if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
    return;
  }
  
  fetch(`get_peran_guru.php?id_guru=${idGuruLogin}`)
    .then(res => res.json())
    .then(result => {
      if (result.status === "success") {
        const data = result.data;
        waliKelasList = data.wali_kelas || [];
        mapelDiajarList = data.mapel_diajar || [];
        kelasMapelList = data.kelas_mapel || [];
        
        const isWali = data.is_wali;
        const isGuruMapel = data.is_guru_mapel;
        
        if (isWali && isGuruMapel) {
          // Punya dua peran: tampilkan tab
          if (tabPeranContainer) tabPeranContainer.style.display = "block";
          setupTabPeran();
          currentMode = "mapel"; // default
          aturTampilanMode();
          isiDropdownKelasMapel(kelasMapelList);
        } else if (isWali) {
          // Cuma wali kelas
          currentMode = "wali";
          aturTampilanMode();
          isiDropdownWaliKelas(waliKelasList);
        } else if (isGuruMapel) {
          // Cuma guru mapel
          currentMode = "mapel";
          aturTampilanMode();
          isiDropdownKelasMapel(kelasMapelList);
        } else {
          showMessage("Anda tidak memiliki akses sebagai guru mapel atau wali kelas.", "error");
          return;
        }
        
        loadNilaiDatabase();
      } else {
        showMessage(result.message, "error");
      }
    })
    .catch(err => {
      console.error("Gagal load peran guru:", err);
      showMessage("Gagal memuat data peran guru.", "error");
    });
}

function setupTabPeran() {
  const tabMapelBtn = document.getElementById("tabMapelBtn");
  const tabWaliBtn = document.getElementById("tabWaliBtn");
  
  if (tabMapelBtn) {
    tabMapelBtn.onclick = () => {
      currentMode = "mapel";
      tabMapelBtn.classList.add("active");
      tabWaliBtn.classList.remove("active");
      aturTampilanMode();
      isiDropdownKelasMapel(kelasMapelList);
      loadNilaiDatabase();
    };
  }
  
  if (tabWaliBtn) {
    tabWaliBtn.onclick = () => {
      currentMode = "wali";
      tabWaliBtn.classList.add("active");
      tabMapelBtn.classList.remove("active");
      aturTampilanMode();
      isiDropdownWaliKelas(waliKelasList);
      loadNilaiDatabase();
    };
  }
}

// ========== DROPDOWN ==========
function isiDropdownWaliKelas(waliKelas) {
  if (!filterKelas) return;
  const nilaiSebelumnya = filterKelas.value;
  filterKelas.innerHTML = "";
  if (waliKelas.length === 0) {
    filterKelas.innerHTML = '<option value="">Tidak ada kelas wali</option>';
    return;
  }
  waliKelas.forEach(kelas => {
    filterKelas.innerHTML += `<option value="${kelas.id_kelas}">Kelas ${kelas.nama_kelas}</option>`;
  });
  if (nilaiSebelumnya && waliKelas.some(k => k.id_kelas == nilaiSebelumnya)) {
    filterKelas.value = nilaiSebelumnya;
  }
}

function isiDropdownKelasMapel(kelasMapel) {
  if (!filterKelas) return;
  const nilaiSebelumnya = filterKelas.value;
  filterKelas.innerHTML = '<option value="">Semua Kelas</option>';
  kelasMapel.forEach(kelas => {
    filterKelas.innerHTML += `<option value="${kelas.id_kelas}">Kelas ${kelas.nama_kelas}</option>`;
  });
  if (nilaiSebelumnya && kelasMapel.some(k => k.id_kelas == nilaiSebelumnya)) {
    filterKelas.value = nilaiSebelumnya;
  }
}

// ========== LOAD DATA NILAI ==========
function loadNilaiDatabase() {
  if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
    return;
  }

  const idKelas = filterKelas ? filterKelas.value : "";

  if (currentMode === "wali" && !idKelas) {
    if (nilaiTableBody) {
      nilaiTableBody.innerHTML = `<tr><td colspan="5" class="empty-state">Pilih kelas wali terlebih dahulu.</td></tr>`;
    }
    return;
  }

  let url = `get_nilai.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&mode=${currentMode}`;
  if (idKelas) url += `&id_kelas=${idKelas}`;

  fetch(url)
    .then(res => res.json())
    .then(result => {
      if (result.status === "success") {
        dataNilai = result.data || [];
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

// ========== PARSE CSV (SAMA KAYAK SEBELUMNYA) ==========
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

function parseCSVFormatBiasa(lines, headers, delimiter) {
  const requiredHeaders = ["id_siswa", "id_mapel", "semester", "nilai_angka", "hadir", "izin", "sakit", "alfa"];
  const valid = requiredHeaders.every(header => headers.includes(header));
  if (!valid) return null;

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
  let lines = text.trim().split(/\r?\n/);
  if (lines.length < 2) throw new Error("File CSV kosong atau tidak valid.");
  lines[0] = lines[0].replace(/^\uFEFF/, "");
  if (lines[0].trim().toLowerCase() === "sep=,") lines = lines.slice(1);
  if (lines[0].trim().toLowerCase() === "sep=;") lines = lines.slice(1);
  const delimiter = deteksiDelimiter(lines[0]);
  const headers = parseCSVLine(lines[0], delimiter).map(header => bersihkanHeader(header));
  const formatBiasa = parseCSVFormatBiasa(lines, headers, delimiter);
  if (formatBiasa !== null && formatBiasa.length > 0) return formatBiasa;
  const formatWali = parseCSVFormatWali(lines, headers, delimiter);
  if (formatWali !== null && formatWali.length > 0) return formatWali;
  throw new Error("Belum ada nilai yang bisa disimpan. Isi dulu kolom nilai mapel di file CSV, lalu simpan ulang sebagai CSV.");
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
        showMessage(error.message, "error");
      }
    };
    reader.readAsText(file);
  });
}

if (searchInput) {
  searchInput.addEventListener("input", filterSearchNilai);
}

if (filterKelas) {
  filterKelas.addEventListener("change", loadNilaiDatabase);
}

if (downloadTemplateBtn) {
  downloadTemplateBtn.addEventListener("click", () => {
    if (!idGuruLogin || roleIdLogin !== "2") {
      alert("Silakan login sebagai guru terlebih dahulu.");
      window.location.href = "../login.html";
      return;
    }
    const idKelas = filterKelas ? filterKelas.value : "";
    let templateUrl = `download_template_nilai.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&mode=${currentMode}`;
    if (currentMode === "wali" && idKelas) templateUrl += `&id_kelas=${idKelas}`;
    const link = document.createElement("a");
    link.href = templateUrl;
    link.download = "template_import_nilai_siswa.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });
}

if (printBtn) {
  printBtn.addEventListener("click", function() {
    alert("Gunakan tombol Cetak Nilai pada baris siswa yang ingin dicetak.");
  });
}

// ========== START ==========
loadPeranGuru();