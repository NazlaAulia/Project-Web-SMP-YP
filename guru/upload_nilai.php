<?php
header("Content-Type: application/json; charset=utf-8");

require_once "koneksi.php";

function kirim_json($status, $message, $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));
    exit;
}

$id_guru = isset($_POST["id_guru"]) ? (int) $_POST["id_guru"] : 0;
$role_id = isset($_POST["role_id"]) ? (int) $_POST["role_id"] : 0;
$data_nilai_json = $_POST["data_nilai"] ?? "";

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid.");
}

if ($data_nilai_json === "") {
    kirim_json("error", "Data nilai kosong.");
}

// ========== AMBIL TAHUN AJARAN AKTIF ==========
$queryTahun = $conn->query("SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1");
$tahunAktif = $queryTahun->fetch_assoc();
$id_tahun_aktif = $tahunAktif ? $tahunAktif['id_tahun_ajaran'] : 0;

if ($id_tahun_aktif == 0) {
    kirim_json("error", "Tidak ada tahun ajaran aktif.");
}

$data_nilai = json_decode($data_nilai_json, true);

if (!is_array($data_nilai)) {
    kirim_json("error", "Format data nilai tidak valid.");
}

if (count($data_nilai) === 0) {
    kirim_json("error", "Tidak ada data nilai yang valid untuk disimpan.");
}

$inserted = 0;
$updated = 0;
$skipped = 0;
$errorRows = [];

$conn->begin_transaction();

try {
    foreach ($data_nilai as $index => $row) {
        $baris = $index + 2;
        
        // Debug: log data row
        error_log("Processing row " . $baris . ": " . json_encode($row));
        
        $id_siswa = isset($row["id_siswa"]) ? (int) $row["id_siswa"] : 0;
        $id_mapel = isset($row["id_mapel"]) ? (int) $row["id_mapel"] : 0;
        $semester = isset($row["semester"]) ? (int) $row["semester"] : 0;
        $nilai_angka = isset($row["nilai_angka"]) ? (int) $row["nilai_angka"] : 0;
        $hadir = isset($row["hadir"]) ? (int) $row["hadir"] : 0;
        $izin = isset($row["izin"]) ? (int) $row["izin"] : 0;
        $sakit = isset($row["sakit"]) ? (int) $row["sakit"] : 0;
        $alfa = isset($row["alfa"]) ? (int) $row["alfa"] : 0;
        
        // Validasi lebih fleksibel
        if ($id_siswa <= 0) {
            $skipped++;
            $errorRows[] = "Baris {$baris}: ID siswa tidak valid (nilai: {$id_siswa})";
            continue;
        }
        
        if ($id_mapel <= 0) {
            $skipped++;
            $errorRows[] = "Baris {$baris}: ID mapel tidak valid (nilai: {$id_mapel})";
            continue;
        }
        
        if ($semester <= 0 || $semester > 2) {
            $skipped++;
            $errorRows[] = "Baris {$baris}: Semester tidak valid (nilai: {$semester}), harus 1 (Ganjil) atau 2 (Genap)";
            continue;
        }
        
        if ($nilai_angka < 0 || $nilai_angka > 100) {
            $skipped++;
            $errorRows[] = "Baris {$baris}: Nilai tidak valid (nilai: {$nilai_angka}), harus antara 0-100";
            continue;
        }
        
        // CEK SISWA
        $cekSiswa = $conn->prepare("SELECT id_siswa FROM siswa WHERE id_siswa = ? LIMIT 1");
        if (!$cekSiswa) {
            throw new Exception("Query cek siswa gagal: " . $conn->error);
        }
        
        $cekSiswa->bind_param("i", $id_siswa);
        $cekSiswa->execute();
        $resultSiswa = $cekSiswa->get_result();
        
        if ($resultSiswa->num_rows === 0) {
            $skipped++;
            $errorRows[] = "Baris {$baris}: ID siswa {$id_siswa} tidak ditemukan di database";
            continue;
        }
        
        // CEK MAPEL (opsional, jika perlu)
        $cekMapel = $conn->prepare("SELECT id_mapel FROM mapel WHERE id_mapel = ? LIMIT 1");
        if ($cekMapel) {
            $cekMapel->bind_param("i", $id_mapel);
            $cekMapel->execute();
            $resultMapel = $cekMapel->get_result();
            
            if ($resultMapel->num_rows === 0) {
                $skipped++;
                $errorRows[] = "Baris {$baris}: ID mapel {$id_mapel} tidak ditemukan di database";
                continue;
            }
        }
        
        // CEK APAKAH SUDAH ADA NILAI
        $cekNilai = $conn->prepare("
            SELECT id_nilai
            FROM nilai
            WHERE id_siswa = ? 
              AND id_mapel = ? 
              AND semester = ?
              AND id_tahun_ajaran = ?
            LIMIT 1
        ");
        
        if (!$cekNilai) {
            throw new Exception("Query cek nilai gagal: " . $conn->error);
        }
        
        $cekNilai->bind_param("iiii", $id_siswa, $id_mapel, $semester, $id_tahun_aktif);
        $cekNilai->execute();
        $resultNilai = $cekNilai->get_result();
        
        if ($resultNilai && $resultNilai->num_rows > 0) {
            // UPDATE nilai yang sudah ada
            $nilaiLama = $resultNilai->fetch_assoc();
            $id_nilai = (int) $nilaiLama["id_nilai"];
            
            $update = $conn->prepare("
                UPDATE nilai
                SET nilai_angka = ?,
                    hadir = ?,
                    izin = ?,
                    sakit = ?,
                    alfa = ?
                WHERE id_nilai = ?
            ");
            
            if (!$update) {
                throw new Exception("Query update nilai gagal: " . $conn->error);
            }
            
            $update->bind_param(
                "iiiiii",
                $nilai_angka,
                $hadir,
                $izin,
                $sakit,
                $alfa,
                $id_nilai
            );
            
            if (!$update->execute()) {
                throw new Exception("Gagal update nilai baris {$baris}: " . $update->error);
            }
            
            $updated++;
            error_log("Updated nilai for siswa {$id_siswa}, mapel {$id_mapel}, semester {$semester}");
        } else {
            // INSERT nilai baru
            $insert = $conn->prepare("
                INSERT INTO nilai
                    (id_siswa, id_mapel, semester, nilai_angka, hadir, izin, sakit, alfa, id_tahun_ajaran)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$insert) {
                throw new Exception("Query insert nilai gagal: " . $conn->error);
            }
            
            $insert->bind_param(
                "iiiiiiiii",
                $id_siswa,
                $id_mapel,
                $semester,
                $nilai_angka,
                $hadir,
                $izin,
                $sakit,
                $alfa,
                $id_tahun_aktif
            );
            
            if (!$insert->execute()) {
                throw new Exception("Gagal insert nilai baris {$baris}: " . $insert->error);
            }
            
            $inserted++;
            error_log("Inserted nilai for siswa {$id_siswa}, mapel {$id_mapel}, semester {$semester}");
        }
    }
    
    $conn->commit();
    
    $message = "Simpan nilai berhasil.";
    if ($inserted > 0) $message .= " Data baru: {$inserted}.";
    if ($updated > 0) $message .= " Diperbarui: {$updated}.";
    if ($skipped > 0) $message .= " Dilewati: {$skipped}.";
    
    kirim_json("success", $message, [
        "inserted" => $inserted,
        "updated" => $updated,
        "skipped" => $skipped,
        "errors" => $errorRows
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in upload_nilai: " . $e->getMessage());
    kirim_json("error", "Terjadi kesalahan: " . $e->getMessage());
}
?>