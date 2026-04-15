let dataNilai = [];
const kkm = 75;

const fileInput = document.getElementById("fileInput");
const uploadBtn = document.getElementById("uploadBtn");
const exportBtn = document.getElementById("exportBtn");
const downloadTemplateBtn = document.getElementById("downloadTemplateBtn");
const applyWeightBtn = document.getElementById("applyWeightBtn");
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

function getWeights() {
  const tugas = Number(document.getElementById("bobotTugas").value);
  const uts = Number(document.getElementById("bobotUTS").value);
  const uas = Number(document.getElementById("bobotUAS").value);
  return { tugas, uts, uas };
}

function validateWeights() {
  const { tugas, uts, uas } = getWeights();
  const total = tugas + uts + uas;

  if (total !== 100) {
    showMessage("Total bobot harus tepat 100%.", "error");
    return false;
  }

  clearMessage();
  return true;
}

function getPredikat(nilaiAkhir) {
  if (nilaiAkhir >= 90) return "A";
  if (nilaiAkhir >= 80) return "B";
  if (nilaiAkhir >= 70) return "C";
  if (nilaiAkhir >= 60) return "D";
  return "E";
}

function hitungNilaiAkhir(tugas, uts, uas) {
  const bobot = getWeights();
  return (
    (tugas * bobot.tugas) / 100 +
    (uts * bobot.uts) / 100 +
    (uas * bobot.uas) / 100
  );
}

function updateRekap() {
  const totalSiswaEl = document.getElementById("totalSiswa");
  const rataRataEl = document.getElementById("rataRata");
  const nilaiTertinggiEl = document.getElementById("nilaiTertinggi");
  const nilaiTerendahEl = document.getElementById("nilaiTerendah");
  const jumlahTuntasEl = document.getElementById("jumlahTuntas");
  const jumlahBelumTuntasEl = document.getElementById("jumlahBelumTuntas");

  if (dataNilai.length === 0) {
    totalSiswaEl.textContent = "0";
    rataRataEl.textContent = "0";
    nilaiTertinggiEl.textContent = "0";
    nilaiTerendahEl.textContent = "0";
    jumlahTuntasEl.textContent = "0";
    jumlahBelumTuntasEl.textContent = "0";
    return;
  }

  const nilaiAkhirList = dataNilai.map(item => item.nilaiAkhir);
  const total = nilaiAkhirList.reduce((sum, n) => sum + n, 0);
  const rataRata = total / dataNilai.length;
  const tertinggi = Math.max(...nilaiAkhirList);
  const terendah = Math.min(...nilaiAkhirList);
  const tuntas = dataNilai.filter(item => item.nilaiAkhir >= kkm).length;
  const belum = dataNilai.length - tuntas;

  totalSiswaEl.textContent = dataNilai.length;
  rataRataEl.textContent = rataRata.toFixed(2);
  nilaiTertinggiEl.textContent = tertinggi.toFixed(2);
  nilaiTerendahEl.textContent = terendah.toFixed(2);
  jumlahTuntasEl.textContent = tuntas;
  jumlahBelumTuntasEl.textContent = belum;
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
      const status = item.nilaiAkhir >= kkm ? "Tuntas" : "Belum Tuntas";
      const statusClass = item.nilaiAkhir >= kkm ? "status-tuntas" : "status-belum";

      return `
        <tr>
          <td>${index + 1}</td>
          <td>${item.nisn}</td>
          <td>${item.nama}</td>
          <td>${item.tugas}</td>
          <td>${item.uts}</td>
          <td>${item.uas}</td>
          <td>${item.nilaiAkhir.toFixed(2)}</td>
          <td>${item.predikat}</td>
          <td class="${statusClass}">${status}</td>
        </tr>
      `;
    })
    .join("");
}

function parseCSV(text) {
  const lines = text.trim().split("\n");
  if (lines.length < 2) {
    throw new Error("File CSV kosong atau tidak valid.");
  }

  const headers = lines[0].split(",").map(h => h.trim().toLowerCase());
  const requiredHeaders = ["nisn", "nama", "tugas", "uts", "uas"];

  const valid = requiredHeaders.every(header => headers.includes(header));
  if (!valid) {
    throw new Error("Header CSV harus: nisn,nama,tugas,uts,uas");
  }

  const nisnIndex = headers.indexOf("nisn");
  const namaIndex = headers.indexOf("nama");
  const tugasIndex = headers.indexOf("tugas");
  const utsIndex = headers.indexOf("uts");
  const uasIndex = headers.indexOf("uas");

  const result = [];

  for (let i = 1; i < lines.length; i++) {
    const row = lines[i].split(",").map(item => item.trim());

    if (row.length < headers.length) continue;

    const nisn = row[nisnIndex];
    const nama = row[namaIndex];
    const tugas = Number(row[tugasIndex]);
    const uts = Number(row[utsIndex]);
    const uas = Number(row[uasIndex]);

    if (!nisn || !nama || isNaN(tugas) || isNaN(uts) || isNaN(uas)) {
      continue;
    }

    const nilaiAkhir = hitungNilaiAkhir(tugas, uts, uas);
    const predikat = getPredikat(nilaiAkhir);

    result.push({
      nisn,
      nama,
      tugas,
      uts,
      uas,
      nilaiAkhir,
      predikat
    });
  }

  return result;
}

uploadBtn.addEventListener("click", () => {
  clearMessage();

  if (!validateWeights()) return;

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
      showMessage(`Berhasil upload ${dataNilai.length} data siswa.`, "success");
    } catch (error) {
      showMessage(error.message, "error");
    }
  };

  reader.readAsText(file);
});

applyWeightBtn.addEventListener("click", () => {
  if (!validateWeights()) return;

  if (dataNilai.length > 0) {
    dataNilai = dataNilai.map(item => {
      const nilaiAkhir = hitungNilaiAkhir(item.tugas, item.uts, item.uas);
      return {
        ...item,
        nilaiAkhir,
        predikat: getPredikat(nilaiAkhir)
      };
    });

    renderTable();
    updateRekap();
    showMessage("Bobot berhasil diterapkan dan nilai diperbarui.", "success");
  } else {
    showMessage("Bobot berhasil disimpan.", "success");
  }
});

searchInput.addEventListener("input", (e) => {
  const keyword = e.target.value.toLowerCase();

  const filtered = dataNilai.filter(item =>
    item.nama.toLowerCase().includes(keyword) ||
    item.nisn.toLowerCase().includes(keyword)
  );

  renderTable(filtered);
});

downloadTemplateBtn.addEventListener("click", () => {
  const template = "nisn,nama,tugas,uts,uas\n12345,Andi,80,85,90\n12346,Budi,78,88,84";
  const blob = new Blob([template], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);

  const link = document.createElement("a");
  link.href = url;
  link.download = "template_nilai.csv";
  link.click();

  URL.revokeObjectURL(url);
});

exportBtn.addEventListener("click", () => {
  if (dataNilai.length === 0) {
    showMessage("Belum ada data untuk diexport.", "error");
    return;
  }

  const tahunAjaran = document.getElementById("tahunAjaran").value;
  const semester = document.getElementById("semester").value;
  const kelas = document.getElementById("kelas").value;
  const mapel = document.getElementById("mapel").value;

  let csvContent = "Tahun Ajaran," + tahunAjaran + "\n";
  csvContent += "Semester," + semester + "\n";
  csvContent += "Kelas," + kelas + "\n";
  csvContent += "Mata Pelajaran," + mapel + "\n\n";
  csvContent += "No,NISN,Nama,Tugas,UTS,UAS,Nilai Akhir,Predikat,Status\n";

  dataNilai.forEach((item, index) => {
    const status = item.nilaiAkhir >= kkm ? "Tuntas" : "Belum Tuntas";
    csvContent += `${index + 1},${item.nisn},${item.nama},${item.tugas},${item.uts},${item.uas},${item.nilaiAkhir.toFixed(2)},${item.predikat},${status}\n`;
  });

  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);

  const link = document.createElement("a");
  link.href = url;
  link.download = `nilai_${kelas}_${mapel}_${semester}.csv`;
  link.click();

  URL.revokeObjectURL(url);
});