let dataNilai = [];

const printBtn = document.getElementById("printBtn");
const fileInput = document.getElementById("fileInput");
const uploadBtn = document.getElementById("uploadBtn");
const exportBtn = document.getElementById("exportBtn");
const downloadTemplateBtn = document.getElementById("downloadTemplateBtn");
const searchInput = document.getElementById("searchInput");
const messageBox = document.getElementById("messageBox");
const nilaiTableBody = document.getElementById("nilaiTableBody");

const modeNilai = document.getElementById("modeNilai");
const filterKelasWali = document.getElementById("filterKelasWali");
const filterWaliKelasBox = document.getElementById("filterWaliKelasBox");

const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");

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
        <td colspan="12" class="empty-state">Belum ada data yang sesuai.</td>
      </tr>
    `;
    return;
  }

  nilaiTableBody.innerHTML = filteredData
    .map((item, index) => {
      return `
        <tr>
          <td>${index + 1}</td>
          <td>${item.id_siswa}</td>
          <td>${item.nama_siswa || "-"}</td>
          <td>${item.nama_kelas || "-"}</td>
          <td>${item.id_mapel}</td>
          <td>${item.nama_mapel || "-"}</td>
          <td>${item.semester_text || tampilSemester(item.semester)}</td>
          <td>${item.nilai_angka}</td>
          <td>${item.hadir}</td>
          <td>${item.izin}</td>
          <td>${item.sakit}</td>
          <td>${item.alfa}</td>
        </tr>
      `;
    })
    .join("");
}

function parseCSV(text) {
  let lines = text.trim().split(/\r?\n/);

  if (lines.length < 2) {
    throw new Error("File CSV kosong atau tidak valid.");
  }

  if (lines[0].trim().toLowerCase() === "sep=,") {
    lines = lines.slice(1);
  }

  const headers = lines[0].split(",").map(header => header.trim().toLowerCase());

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
    throw new Error(
      "Header CSV harus memuat: id_siswa,id_mapel,semester,nilai_angka,hadir,izin,sakit,alfa"
    );
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

    const row = lines[i].split(",").map(item => item.trim());

    const id_siswa = Number(row[idSiswaIndex]);
    const nama_siswa = namaSiswaIndex >= 0 ? row[namaSiswaIndex] : "";
    const id_mapel = Number(row[idMapelIndex]);
    const nama_mapel = namaMapelIndex >= 0 ? row[namaMapelIndex] : "";
    const semester = normalisasiSemester(row[semesterIndex]);
    const nilai_angka = Number(row[nilaiAngkaIndex]);
    const hadir = Number(row[hadirIndex]);
    const izin = Number(row[izinIndex]);
    const sakit = Number(row[sakitIndex]);
    const alfa = Number(row[alfaIndex]);

    if (
      !id_siswa ||
      !id_mapel ||
      !semester ||
      isNaN(nilai_angka) ||
      isNaN(hadir) ||
      isNaN(izin) ||
      isNaN(sakit) ||
      isNaN(alfa)
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

function loadNilaiDatabase() {
  if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
    return;
  }

  const mode = modeNilai ? modeNilai.value : "mapel";
  const idKelas = filterKelasWali ? filterKelasWali.value : "";

  let url = `get_nilai.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&mode=${mode}`;

  if (mode === "wali" && idKelas) {
    url += `&id_kelas=${idKelas}`;
  }

  fetch(url)
    .then(res => res.json())
    .then(result => {
      if (result.status === "success") {
        dataNilai = result.data || [];

        if (result.is_wali_kelas && filterWaliKelasBox) {
          filterWaliKelasBox.style.display = "block";
          isiDropdownWaliKelas(result.wali_kelas || []);
        }

        if (filterKelasWali) {
          filterKelasWali.style.display = mode === "wali" ? "block" : "none";
        }

        renderTable();
        updateRekap();
      } else {
        console.warn(result.message);
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

function eksporDataNilaiCsv() {
  if (dataNilai.length === 0) {
    showMessage("Belum ada data untuk diexport.", "error");
    return;
  }

  const header = [
    "id_siswa",
    "nama_siswa",
    "kelas",
    "id_mapel",
    "nama_mapel",
    "semester",
    "nilai_angka",
    "hadir",
    "izin",
    "sakit",
    "alfa"
  ];

  const rows = dataNilai.map(item => [
    item.id_siswa,
    item.nama_siswa || "-",
    item.nama_kelas || "-",
    item.id_mapel,
    item.nama_mapel || "-",
    item.semester_text || tampilSemester(item.semester),
    item.nilai_angka,
    item.hadir,
    item.izin,
    item.sakit,
    item.alfa
  ]);

  const csvContent = [
    header.join(","),
    ...rows.map(row => row.map(amanCsv).join(","))
  ].join("\n");

  const blob = new Blob(["\uFEFF" + csvContent], {
    type: "text/csv;charset=utf-8;"
  });

  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");

  const mode = modeNilai ? modeNilai.value : "mapel";
  const tanggal = new Date().toISOString().slice(0, 10);

  link.href = url;
  link.download = `data_nilai_${mode}_${tanggal}.csv`;
  link.click();

  URL.revokeObjectURL(url);
}

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
                `Import berhasil. Data baru: ${inserted}, diperbarui: ${updated}, dilewati: ${skipped}.`,
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

if (searchInput) {
  searchInput.addEventListener("input", filterSearchNilai);
}

if (modeNilai) {
  modeNilai.addEventListener("change", loadNilaiDatabase);
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

if (printBtn) {
  printBtn.addEventListener("click", function () {
    const mode = modeNilai ? modeNilai.value : "mapel";
    const idKelas = filterKelasWali ? filterKelasWali.value : "";

    if (!idGuruLogin || roleIdLogin !== "2") {
      alert("Silakan login sebagai guru terlebih dahulu.");
      window.location.href = "../login.html";
      return;
    }

    if (mode !== "wali") {
      alert("Cetak semua nilai siswa hanya tersedia untuk mode Wali Kelas.");
      return;
    }

    if (!idKelas) {
      alert("Pilih kelas wali terlebih dahulu.");
      return;
    }

    window.open(
      `cetak_nilai_wali.html?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&id_kelas=${idKelas}`,
      "_blank"
    );
  });
}

loadNilaiDatabase();