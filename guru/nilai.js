let dataNilai = [];

const filterKelasWaliLabel = document.querySelector('label[for="filterKelasWali"]');
const printBtn = document.getElementById("printBtn");
const fileInput = document.getElementById("fileInput");
const uploadBtn = document.getElementById("uploadBtn");
const downloadTemplateBtn = document.getElementById("downloadTemplateBtn");
const searchInput = document.getElementById("searchInput");
const messageBox = document.getElementById("messageBox");
const nilaiTableBody = document.getElementById("nilaiTableBody");

const modeNilai = document.getElementById("modeNilai");
const filterKelasWali = document.getElementById("filterKelasWali");
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

function updateRekap() {
  const totalSiswaEl = document.getElementById("totalSiswa");
  const rataRataEl = document.getElementById("rataRata");
  const nilaiTertinggiEl = document.getElementById("nilaiTertinggi");
  const nilaiTerendahEl = document.getElementById("nilaiTerendah");
  const totalHadirEl = document.getElementById("totalHadir");
  const totalAlfaEl = document.getElementById("totalAlfa");

  if (
    !totalSiswaEl ||
    !rataRataEl ||
    !nilaiTertinggiEl ||
    !nilaiTerendahEl ||
    !totalHadirEl ||
    !totalAlfaEl
  ) {
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

function renderTable(filteredData = dataNilai) {
  if (!nilaiTableBody) return;

  if (filteredData.length === 0) {
    nilaiTableBody.innerHTML = `
      <tr>
        <td colspan="11" class="empty-state">Belum ada data yang sesuai.</td>
      </tr>
    `;
    return;
  }

  const mode = modeNilai ? modeNilai.value : "mapel";
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

  nilaiTableBody.innerHTML = dataRingkas
    .map((item, index) => {
      const hadirTampil = item.hadir;
      const izinTampil = item.izin;
      const sakitTampil = item.sakit;
      const alfaTampil = item.alfa;

      return `
        <tr>
          <td>${index + 1}</td>
          <td>${item.id_siswa}</td>
          <td><strong>${item.nama_siswa}</strong></td>
          <td>${item.nama_kelas}</td>
          <td>${item.semester_text}</td>
          <td>${nilaiTampil}</td>
          <td>${hadirTampil}</td>
          <td>${izinTampil}</td>
          <td>${sakitTampil}</td>
          <td>${alfaTampil}</td>
          <td>
            <button
              type="button"
              class="btn-cetak-row"
              onclick="cetakNilaiSiswa(${item.id_siswa})"
            >
              <i class="bi bi-printer"></i>
              Cetak Nilai
            </button>
          </td>
        </tr>
      `;
    })
    .join("");
}

function cetakNilaiSiswa(idSiswa) {
  const mode = modeNilai ? modeNilai.value : "mapel";
  const idKelas = filterKelasWali ? filterKelasWali.value : "";

  if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
    return;
  }

  if (mode !== "wali") {
    alert("Cetak rekap nilai siswa hanya tersedia pada mode Wali Kelas.");
    return;
  }

  if (!idKelas) {
    alert("Pilih kelas wali terlebih dahulu.");
    return;
  }

  const url =
    `cetak_nilai_wali.html?id_guru=${idGuruLogin}` +
    `&role_id=${roleIdLogin}` +
    `&id_kelas=${idKelas}` +
    `&id_siswa=${encodeURIComponent(idSiswa)}`;

  window.open(url, "_blank");
}
/* =========================
   PARSE CSV AMAN
   Bisa baca:
   1. Format mapel biasa
   2. Format wali kelas mapel menyamping
========================= */

function bersihkanHeader(value) {
  return String(value || "")
    .replace(/^\uFEFF/, "")
    .trim()
    .toLowerCase()
    .replace(/\s+/g, " ");
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
  const requiredHeaders = [
    "id_siswa",
    "id_mapel",
    "semester",
    "nilai_angka",
    "hadir",
    "izin",
    "sakit",
    "alfa"
  ];

  const valid = requiredHeaders.every(header => headers.includes(header));

  if (!valid) {
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

    if (
      !id_siswa ||
      !id_mapel ||
      !semester ||
      isNaN(nilai_angka)
    ) {
      continue;
    }

    result.push({
      id_siswa,
      nama_siswa,
      id_mapel,
      nama_mapel,
      semester,
      nilai_angka,
      hadir,
      izin,
      sakit,
      alfa
    });
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

  if (
    idSiswaIndex < 0 ||
    namaSiswaIndex < 0 ||
    semesterIndex < 0 ||
    hadirIndex < 0 ||
    izinIndex < 0 ||
    sakitIndex < 0 ||
    alfaIndex < 0
  ) {
    return null;
  }

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

    if (!id_siswa || !semester) {
      continue;
    }

    daftarMapelCsv.forEach(mapel => {
      const namaMapelHeader = bersihkanHeader(mapel.nama_mapel);
      const mapelIndex = headers.indexOf(namaMapelHeader);

      if (mapelIndex < 0) return;

      const nilaiText = String(row[mapelIndex] ?? "").trim();

      if (nilaiText === "") return;

      const nilai_angka = ambilAngka(nilaiText, NaN);

      if (isNaN(nilai_angka)) return;

      result.push({
        id_siswa,
        nama_siswa,
        id_mapel: mapel.id_mapel,
        nama_mapel: mapel.nama_mapel,
        semester,
        nilai_angka,
        hadir,
        izin,
        sakit,
        alfa
      });
    });
  }

  return result;
}

function parseCSV(text) {
  let lines = text.trim().split(/\r?\n/);

  if (lines.length < 2) {
    throw new Error("File CSV kosong atau tidak valid.");
  }

  lines[0] = lines[0].replace(/^\uFEFF/, "");

  if (lines[0].trim().toLowerCase() === "sep=,") {
    lines = lines.slice(1);
  }

  if (lines[0].trim().toLowerCase() === "sep=;") {
    lines = lines.slice(1);
  }

  const delimiter = deteksiDelimiter(lines[0]);
  const headers = parseCSVLine(lines[0], delimiter).map(header => bersihkanHeader(header));

  const formatBiasa = parseCSVFormatBiasa(lines, headers, delimiter);

  if (formatBiasa !== null && formatBiasa.length > 0) {
    return formatBiasa;
  }

  const formatWali = parseCSVFormatWali(lines, headers, delimiter);

  if (formatWali !== null && formatWali.length > 0) {
    return formatWali;
  }

  throw new Error(
    "Belum ada nilai yang bisa disimpan. Isi dulu kolom nilai mapel di file CSV, lalu simpan ulang sebagai CSV."
  );
}

/* =========================
   DROPDOWN WALI KELAS
========================= */

function isiDropdownWaliKelas(waliKelas) {
  if (!filterKelasWali) return;

  const nilaiSebelumnya = filterKelasWali.value;

  filterKelasWali.innerHTML = "";

  waliKelas.forEach(kelas => {
    filterKelasWali.innerHTML += `
      <option value="${kelas.id_kelas}">
        Kelas ${kelas.nama_kelas}
      </option>
    `;
  });

  if (nilaiSebelumnya) {
    filterKelasWali.value = nilaiSebelumnya;
  }
}

function isiDropdownKelasMapel(kelasMapel) {
  if (!filterKelasWali) return;

  const nilaiSebelumnya = filterKelasWali.value;

  filterKelasWali.innerHTML = `
    <option value="">Semua Kelas Mapel Saya</option>
  `;

  kelasMapel.forEach(kelas => {
    filterKelasWali.innerHTML += `
      <option value="${kelas.id_kelas}">
        Kelas ${kelas.nama_kelas}
      </option>
    `;
  });

  if (nilaiSebelumnya) {
    filterKelasWali.value = nilaiSebelumnya;
  }
}

function aturTampilanMode() {
  const mode = modeNilai ? modeNilai.value : "mapel";

  if (!filterKelasWaliGroup || !filterKelasWali) return;

  filterKelasWaliGroup.style.display = "flex";
  filterKelasWali.disabled = false;
  filterKelasWaliGroup.classList.remove("filter-disabled");

  if (filterKelasWaliLabel) {
    if (mode === "wali") {
      filterKelasWaliLabel.textContent = "Kelas Wali";
    } else {
      filterKelasWaliLabel.textContent = "Kelas Mapel Saya";
    }
  }
}

/* =========================
   LOAD DATA DATABASE
========================= */

function loadNilaiDatabase() {
  if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
    return;
  }

  const mode = modeNilai ? modeNilai.value : "mapel";
  const idKelas = filterKelasWali ? filterKelasWali.value : "";

  let url = `get_nilai.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&mode=${mode}`;

  if (idKelas) {
    url += `&id_kelas=${idKelas}`;
  }

  fetch(url)
    .then(res => res.json())
    .then(result => {
      if (result.status === "success") {
        dataNilai = result.data || [];

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

  return fetch("upload_nilai.php", {
    method: "POST",
    body: formData
  }).then(res => res.json());
}

/* =========================
   SEARCH
========================= */

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

function amanCsv(value) {
  const text = String(value ?? "");
  return `"${text.replace(/"/g, '""')}"`;
}

/* =========================
   UPLOAD / SIMPAN NILAI
========================= */

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

    reader.onload = function (e) {
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
              const inserted = result.inserted || 0;
              const updated = result.updated || 0;
              const skipped = result.skipped || 0;

              showMessage(
                `Simpan nilai berhasil. Data baru: ${inserted}, diperbarui: ${updated}, dilewati: ${skipped}.`,
                "success"
              );

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

/* =========================
   EVENT SEARCH + FILTER
========================= */

if (searchInput) {
  searchInput.addEventListener("input", filterSearchNilai);
}

if (modeNilai) {
  modeNilai.addEventListener("change", function () {
    aturTampilanMode();
    loadNilaiDatabase();
  });
}

if (filterKelasWali) {
  filterKelasWali.addEventListener("change", loadNilaiDatabase);
}

/* =========================
   DOWNLOAD TEMPLATE CSV
========================= */

if (downloadTemplateBtn) {
  downloadTemplateBtn.addEventListener("click", () => {
    if (!idGuruLogin || roleIdLogin !== "2") {
      alert("Silakan login sebagai guru terlebih dahulu.");
      window.location.href = "../login.html";
      return;
    }

    const mode = modeNilai ? modeNilai.value : "mapel";
    const idKelas = filterKelasWali ? filterKelasWali.value : "";

    let templateUrl = `download_template_nilai.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&mode=${mode}`;

    if (mode === "wali" && idKelas) {
      templateUrl += `&id_kelas=${idKelas}`;
    }

    const link = document.createElement("a");

    link.href = templateUrl;
    link.download = "template_import_nilai_siswa.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });
}

/* =========================
   CETAK NILAI
========================= */
if (printBtn) {
  printBtn.addEventListener("click", function () {
    alert("Gunakan tombol Cetak Nilai pada baris siswa yang ingin dicetak.");
  });
}
loadNilaiDatabase();