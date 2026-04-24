let dataNilai = [];

const fileInput = document.getElementById("fileInput");
const uploadBtn = document.getElementById("uploadBtn");
const exportBtn = document.getElementById("exportBtn");
const downloadTemplateBtn = document.getElementById("downloadTemplateBtn");
const searchInput = document.getElementById("searchInput");
const messageBox = document.getElementById("messageBox");
const nilaiTableBody = document.getElementById("nilaiTableBody");

function showMessage(text, type = "success") {
  messageBox.textContent = text;
  messageBox.className = `message-box ${type}`;
}

function clearMessage() {
  messageBox.textContent = "";
  messageBox.className = "message-box";
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

  const nilaiList = dataNilai.map(item => item.nilai_angka);
  const totalNilai = nilaiList.reduce((sum, nilai) => sum + nilai, 0);
  const rataRata = totalNilai / dataNilai.length;

  const nilaiTertinggi = Math.max(...nilaiList);
  const nilaiTerendah = Math.min(...nilaiList);

  const totalHadir = dataNilai.reduce((sum, item) => sum + item.hadir, 0);
  const totalAlfa = dataNilai.reduce((sum, item) => sum + item.alfa, 0);

  totalSiswaEl.textContent = dataNilai.length;
  rataRataEl.textContent = rataRata.toFixed(2);
  nilaiTertinggiEl.textContent = nilaiTertinggi;
  nilaiTerendahEl.textContent = nilaiTerendah;
  totalHadirEl.textContent = totalHadir;
  totalAlfaEl.textContent = totalAlfa;
}

function renderTable(filteredData = dataNilai) {
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

    const id_siswa = row[idSiswaIndex];
    const id_mapel = row[idMapelIndex];
    const semester = row[semesterIndex];
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

uploadBtn.addEventListener("click", () => {
  clearMessage();

  const file = fileInput.files[0];

  if (!file) {
    showMessage("Pilih file CSV terlebih dahulu.", "error");
    return;
  }

  if (!file.name.endsWith(".csv")) {
    showMessage("File harus berformat .csv", "error");
    return;
  }

  const reader = new FileReader();

  reader.onload = function (e) {
    try {
      const text = e.target.result;
      dataNilai = parseCSV(text);

      renderTable();
      updateRekap();

      showMessage(`Berhasil upload ${dataNilai.length} data nilai.`, "success");
    } catch (error) {
      showMessage(error.message, "error");
    }
  };

  reader.readAsText(file);
});

searchInput.addEventListener("input", (e) => {
  const keyword = e.target.value.toLowerCase();

  const filtered = dataNilai.filter(item =>
    String(item.id_siswa).toLowerCase().includes(keyword) ||
    String(item.id_mapel).toLowerCase().includes(keyword) ||
    String(item.semester).toLowerCase().includes(keyword)
  );

  renderTable(filtered);
});

downloadTemplateBtn.addEventListener("click", () => {
  const template =
    "id_siswa,id_mapel,semester,nilai_angka,hadir,izin,sakit,alfa\n" +
    "1,5,Ganjil,88,20,0,1,0\n" +
    "2,5,Ganjil,76,18,1,1,0\n" +
    "3,5,Ganjil,92,21,0,0,0\n";

  const blob = new Blob([template], {
    type: "text/csv;charset=utf-8;"
  });

  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");

  link.href = url;
  link.download = "template_nilai_siswa.csv";
  link.click();

  URL.revokeObjectURL(url);
});

exportBtn.addEventListener("click", () => {
  if (dataNilai.length === 0) {
    showMessage("Belum ada data untuk diexport.", "error");
    return;
  }

  const tahunAjaran = document.getElementById("tahunAjaran").value;
  const kelas = document.getElementById("kelas").value;
  const mapel = document.getElementById("mapel").value;

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