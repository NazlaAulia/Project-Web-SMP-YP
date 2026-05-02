const namaKelasEl = document.getElementById("namaKelas");
const namaWaliEl = document.getElementById("namaWali");
const tanggalCetakEl = document.getElementById("tanggalCetak");
const tanggalTtdEl = document.getElementById("tanggalTtd");
const totalSiswaCetakEl = document.getElementById("totalSiswaCetak");
const ttdWaliEl = document.getElementById("ttdWali");
const printContent = document.getElementById("printContent");
const btnPrint = document.getElementById("btnPrint");

const params = new URLSearchParams(window.location.search);

const idGuru = params.get("id_guru");
const roleId = params.get("role_id");
const idKelas = params.get("id_kelas");
const idSiswa = params.get("id_siswa") || "";
const keywordCetak = (params.get("q") || "").toLowerCase().trim();

function formatTanggalIndonesia(date = new Date()) {
  return date.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric"
  });
}

function tampilSemester(value) {
  const semester = Number(value);

  if (semester === 1) return "Ganjil";
  if (semester === 2) return "Genap";

  return value || "-";
}

function hitungRataRata(nilai) {
  if (!nilai || nilai.length === 0) return 0;

  const total = nilai.reduce((sum, item) => {
    return sum + Number(item.nilai_angka || 0);
  }, 0);

  return total / nilai.length;
}

function renderSiswa(siswa) {
  const rataRata = hitungRataRata(siswa.nilai).toFixed(2);

  const rows = siswa.nilai.map((item, index) => {
    return `
      <tr>
        <td>${index + 1}</td>
        <td>${item.nama_mapel || "-"}</td>
        <td>${tampilSemester(item.semester)}</td>
        <td>${item.nilai_angka}</td>
        <td>${item.hadir}</td>
        <td>${item.izin}</td>
        <td>${item.sakit}</td>
        <td>${item.alfa}</td>
      </tr>
    `;
  }).join("");

  return `
    <article class="student-card">
      <div class="student-head">
        <div>
          <h2>${siswa.nama_siswa}</h2>
          <p>ID Siswa: ${siswa.id_siswa}</p>
        </div>

        <div class="kelas-badge">Kelas ${siswa.nama_kelas}</div>
      </div>

      <div class="table-wrap">
        <table class="nilai-print-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Mata Pelajaran</th>
              <th>Semester</th>
              <th>Nilai</th>
              <th>Hadir</th>
              <th>Izin</th>
              <th>Sakit</th>
              <th>Alfa</th>
            </tr>
          </thead>

          <tbody>
            ${rows}
            <tr class="rata-row">
              <td colspan="3">Rata-rata Nilai</td>
              <td colspan="5">${rataRata}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </article>
  `;
}

function renderCetak(data) {
let siswaList = data.siswa || [];

/* PENGAMAN: kalau URL membawa id_siswa, tampilkan siswa itu saja */
if (idSiswa !== "") {
  siswaList = siswaList.filter(siswa =>
    String(siswa.id_siswa) === String(idSiswa)
  );
}

if (keywordCetak !== "") {
  siswaList = siswaList.filter(siswa =>
    String(siswa.nama_siswa || "").toLowerCase().includes(keywordCetak) ||
    String(siswa.id_siswa || "").toLowerCase().includes(keywordCetak)
  );
}

  if (namaKelasEl) namaKelasEl.textContent = data.kelas?.nama_kelas || "-";
  if (namaWaliEl) namaWaliEl.textContent = data.wali?.nama || "-";
  if (ttdWaliEl) ttdWaliEl.textContent = data.wali?.nama || "-";
  if (tanggalCetakEl) tanggalCetakEl.textContent = formatTanggalIndonesia();
  if (tanggalTtdEl) tanggalTtdEl.textContent = formatTanggalIndonesia();
  if (totalSiswaCetakEl) totalSiswaCetakEl.textContent = siswaList.length;

  if (!printContent) return;

  if (siswaList.length === 0) {
    printContent.innerHTML = `
      <div class="empty-state">
        Data siswa tidak ditemukan untuk pencarian ini.
      </div>
    `;
    return;
  }

  printContent.innerHTML = siswaList.map(renderSiswa).join("");
}

function loadCetakNilai() {
  if (!idGuru || !roleId || !idKelas) {
    if (printContent) {
      printContent.innerHTML = `
        <div class="empty-state">
          Data cetak tidak valid.
        </div>
      `;
    }
    return;
  }

let url =
  `cetak_nilai_wali.php?id_guru=${idGuru}` +
  `&role_id=${roleId}` +
  `&id_kelas=${idKelas}`;

if (idSiswa !== "") {
  url += `&id_siswa=${encodeURIComponent(idSiswa)}`;
}

  fetch(url)
    .then(res => {
      if (!res.ok) {
        throw new Error("File cetak_nilai_wali.php tidak ditemukan atau server error.");
      }

      return res.text();
    })
    .then(text => {
      let result;

      try {
        result = JSON.parse(text);
      } catch (error) {
        console.error("Respon PHP bukan JSON:", text);
        throw new Error("PHP cetak mengirim respon tidak valid. Cek file cetak_nilai_wali.php.");
      }

      if (result.status === "success") {
        renderCetak(result);
      } else {
        if (printContent) {
          printContent.innerHTML = `
            <div class="empty-state">
              ${result.message}
            </div>
          `;
        }
      }
    })
    .catch(err => {
      console.error("Error cetak:", err);

      if (printContent) {
        printContent.innerHTML = `
          <div class="empty-state">
            ${err.message}
          </div>
        `;
      }
    });
}

if (btnPrint) {
  btnPrint.addEventListener("click", function () {
    window.print();
  });
}

loadCetakNilai();