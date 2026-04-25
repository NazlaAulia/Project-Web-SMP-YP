let dataNilai = [];

const fileInput = document.getElementById("fileInput");
const uploadBtn = document.getElementById("uploadBtn");
const exportBtn = document.getElementById("exportBtn");
const downloadTemplateBtn = document.getElementById("downloadTemplateBtn");
const searchInput = document.getElementById("searchInput");
const messageBox = document.getElementById("messageBox");
const nilaiTableBody = document.getElementById("nilaiTableBody");

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
        <td colspan="9" class="empty-state">Belum ada data yang sesuai.</td>
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
          <td>${item.id_mapel}</td>
          <td>${item.semester}</td>
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
  const lines = text.trim().split(/\r?\n/);

  if (lines.length < 2) {
    throw new Error("File CSV kosong atau tidak valid.");
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
      "Header CSV harus: id_siswa,id_mapel,semester,nilai_angka,hadir,izin,sakit,alfa"
    );
  }

  const idSiswaIndex = headers.indexOf("id_siswa");
  const idMapelIndex = headers.indexOf("id_mapel");
  const semesterIndex = headers.indexOf("semester");
  const nilaiAngkaIndex = headers.indexOf("nilai_angka");
  const hadirIndex = headers.indexOf("hadir");
  const izinIndex = headers.indexOf("izin");
  const sakitIndex = headers.indexOf("sakit");
  const alfaIndex = headers.indexOf("alfa");

  const result = [];

  for (let i = 1; i < lines.length; i++) {
    const row = lines[i].split(",").map(item => item.trim());

    if (row.length < headers.length) continue;

    const id_siswa = Number(row[idSiswaIndex]);
    const id_mapel = Number(row[idMapelIndex]);
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
      id_mapel,
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

function loadNilaiDatabase() {
  if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
    return;
  }

  fetch(`get_nilai.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}`)
    .then(res => res.json())
    .then(result => {
      if (result.status === "success") {
        dataNilai = result.data || [];
        renderTable();
        updateRekap();
      } else {
        console.warn(result.message);
      }
    })
    .catch(err => {
      console.error("Gagal load nilai:", err);
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
  searchInput.addEventListener("input", (e) => {
    const keyword = e.target.value.toLowerCase();

    const filtered = dataNilai.filter(item =>
      String(item.id_siswa).toLowerCase().includes(keyword) ||
      String(item.id_mapel).toLowerCase().includes(keyword) ||
      String(item.semester).toLowerCase().includes(keyword)
    );

    renderTable(filtered);
  });
}
if (downloadTemplateBtn) {
  downloadTemplateBtn.addEventListener("click", () => {
    const template =
      "id_siswa,nama_siswa,id_mapel,nama_mapel,semester,nilai_angka,hadir,izin,sakit,alfa\n" +
      "1293,Aulia Rahma,3,PKN,1,88,20,0,1,0\n" +
      "1294,Bagus Pratama,3,PKN,1,76,18,1,1,0\n" +
      "1295,Citra Lestari,3,PKN,1,92,21,0,0,0\n";

    const blob = new Blob([template], {
      type: "text/csv;charset=utf-8;"
    });

    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.href = url;
    link.download = "template_import_nilai_siswa.csv";
    link.click();

    URL.revokeObjectURL(url);
  });
}

if (exportBtn) {
  exportBtn.addEventListener("click", () => {
    if (dataNilai.length === 0) {
      showMessage("Belum ada data untuk diexport.", "error");
      return;
    }

    const tahunAjaranEl = document.getElementById("tahunAjaran");
    const kelasEl = document.getElementById("kelas");
    const mapelEl = document.getElementById("mapel");

    const tahunAjaran = tahunAjaranEl ? tahunAjaranEl.value : "-";
    const kelas = kelasEl ? kelasEl.value : "-";
    const mapel = mapelEl ? mapelEl.value : "-";

    let csvContent = "Tahun Ajaran," + tahunAjaran + "\n";
    csvContent += "Kelas," + kelas + "\n";
    csvContent += "Mata Pelajaran," + mapel + "\n\n";
    csvContent += "No,ID Siswa,ID Mapel,Semester,Nilai Angka,Hadir,Izin,Sakit,Alfa\n";

    dataNilai.forEach((item, index) => {
      csvContent += `${index + 1},${item.id_siswa},${item.id_mapel},${item.semester},${item.nilai_angka},${item.hadir},${item.izin},${item.sakit},${item.alfa}\n`;
    });

    const blob = new Blob([csvContent], {
      type: "text/csv;charset=utf-8;"
    });

    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.href = url;
    link.download = `nilai_${kelas}_${mapel}.csv`;
    link.click();

    URL.revokeObjectURL(url);
  });
}

loadNilaiDatabase();