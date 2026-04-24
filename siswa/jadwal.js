async function loadJadwal() {
  try {
    const response = await fetch("get_jadwal.php", {
      method: "GET",
      credentials: "same-origin"
    });

    const text = await response.text();
    console.log("RAW RESPONSE:", text);

    const result = JSON.parse(text);
    console.log("JSON RESULT:", result);

    if (!result.success) {
      renderError(result.message || "Gagal memuat data.");
      return;
    }

    localStorage.setItem("nama_siswa", result.siswa.nama || "Siswa");
    localStorage.setItem("kelas_siswa", result.siswa.kelas || "-");

    renderProfil(result.siswa);
    renderRingkasan(result.ringkasan);
    renderUpdate(result.update_terbaru);
    renderTabel(result.jadwal_minggu, result.siswa.kelas);
  } catch (error) {
    renderError("Terjadi kesalahan saat mengambil data jadwal.");
    console.error("ERROR FETCH / JSON:", error);
  }
}