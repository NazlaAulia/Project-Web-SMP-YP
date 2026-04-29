const namaKelasEl = document.getElementById("namaKelas");
const namaWaliEl = document.getElementById("namaWali");
const tanggalCetakEl = document.getElementById("tanggalCetak");
const printContentEl = document.getElementById("printContent");
const btnPrint = document.getElementById("btnPrint");

const params = new URLSearchParams(window.location.search);

const idGuru = params.get("id_guru");
const roleId = params.get("role_id");
const idKelas = params.get("id_kelas");

function tampilSemester(semester) {
  return Number(semester) === 1 ? "Ganjil" : "Genap";
}

function formatTanggalIndonesia() {
  return new Date().toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric"
  });
}

function renderCetakNilai(data) {
  if (namaKelasEl) namaKelasEl.textContent = data.kelas?.nama_kelas || "-";
  if (namaWaliEl) namaWaliEl.textContent = data.wali?.nama || "-";
  if (tanggalCetakEl) tanggalCetakEl.textContent = formatTanggalIndonesia();

  const siswa = data.siswa || [];

  if (!printContentEl) return;

  if (siswa.length === 0) {
    printContentEl.innerHTML = `
      <div class="empty-state">
        Belum ada data nilai untuk kelas ini.
      </div>
    `;
    return;
  }

  printContentEl.innerHTML = siswa.map(item => {
    const rows = (item.nilai || []).map((nilai, index) => {
      return `
        <tr>
          <td>${index + 1}</td>
          <td>${nilai.nama_mapel || "-"}</td>
          <td>${tampilSemester(nilai.semester)}</td>
          <td>${nilai.nilai_angka}</td>
          <td>${nilai.hadir}</td>
          <td>${nilai.izin}</td>
          <td>${nilai.sakit}</td>
          <td>${nilai.alfa}</td>
        </tr>
      `;
    }).join("");

    return `
      <article class="student-card">
        <div class="student-head">
          <h2>${item.nama_siswa}</h2>
          <span>Kelas ${item.nama_kelas}</span>
        </div>

        <table>
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
          </tbody>
        </table>
      </article>
    `;
  }).join("");
}

function loadCetakNilai() {
  if (!idGuru || !roleId || !idKelas) {
    printContentEl.innerHTML = `
      <div class="empty-state">
        Parameter cetak tidak lengkap.
      </div>
    `;
    return;
  }

  fetch(`cetak_nilai_wali.php?id_guru=${idGuru}&role_id=${roleId}&id_kelas=${idKelas}`)
    .then(res => res.json())
    .then(result => {
      if (result.status === "success") {
        renderCetakNilai(result);
      } else {
        printContentEl.innerHTML = `
          <div class="empty-state">
            ${result.message}
          </div>
        `;
      }
    })
    .catch(err => {
      console.error(err);

      printContentEl.innerHTML = `
        <div class="empty-state">
          Gagal memuat data cetak nilai.
        </div>
      `;
    });
}

if (btnPrint) {
  btnPrint.addEventListener("click", function () {
    window.print();
  });
}

loadCetakNilai();