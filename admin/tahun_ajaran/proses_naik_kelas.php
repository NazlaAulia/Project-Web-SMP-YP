<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak valid.']);
    exit;
}

$id_tahun_ajaran = isset($_POST['id_tahun_ajaran']) ? (int) $_POST['id_tahun_ajaran'] : 0;
$wali_kelas = isset($_POST['wali_kelas']) && is_array($_POST['wali_kelas']) ? $_POST['wali_kelas'] : [];
$kapasitas_data = isset($_POST['kapasitas']) && is_array($_POST['kapasitas']) ? $_POST['kapasitas'] : [];

if ($id_tahun_ajaran <= 0) {
    echo json_encode(['success' => false, 'message' => 'Tahun ajaran baru wajib dipilih.']);
    exit;
}

if (empty($wali_kelas)) {
    echo json_encode(['success' => false, 'message' => 'Wali kelas wajib dipilih.']);
    exit;
}

// ========== CEK PENDAFTAR YANG MASIH MENUNGGU DI TAHUN AJARAN LAMA ==========
$query_tahun_lama = "SELECT id_tahun_ajaran, tahun_ajaran FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1";
$result_tahun_lama = mysqli_query($conn, $query_tahun_lama);
$tahun_lama = mysqli_fetch_assoc($result_tahun_lama);

if ($tahun_lama) {
    $id_tahun_lama = $tahun_lama['id_tahun_ajaran'];
    $tahun_ajaran_lama = $tahun_lama['tahun_ajaran'];
    
    $query_cek = "SELECT COUNT(*) as total FROM pendaftaran WHERE id_tahun_ajaran = $id_tahun_lama AND status = 'menunggu'";
    $result_cek = mysqli_query($conn, $query_cek);
    $data_cek = mysqli_fetch_assoc($result_cek);
    $jumlah_menunggu = $data_cek['total'];
    
    if ($jumlah_menunggu > 0) {
        $query_nama = "SELECT id_pendaftaran, nama_lengkap FROM pendaftaran WHERE id_tahun_ajaran = $id_tahun_lama AND status = 'menunggu' LIMIT 10";
        $result_nama = mysqli_query($conn, $query_nama);
        $daftar_menunggu = [];
        while ($row = mysqli_fetch_assoc($result_nama)) {
            $daftar_menunggu[] = $row;
        }
        
        $pesan_error = "Tidak dapat melanjutkan proses naik kelas. Masih ada <strong>$jumlah_menunggu pendaftar</strong> yang statusnya 'MENUNGGU' di tahun ajaran <strong>$tahun_ajaran_lama</strong>.<br><br>";
        $pesan_error .= "<strong>Daftar pendaftar yang belum diproses:</strong><br><ul>";
        foreach ($daftar_menunggu as $dm) {
            $pesan_error .= "<li>{$dm['nama_lengkap']} - <a href='/admin/admin_pendaftaran.php?id_tahun={$id_tahun_lama}&filter=menunggu' target='_blank'>Proses Sekarang</a></li>";
        }
        if ($jumlah_menunggu > 10) {
            $pesan_error .= "<li><em>... dan " . ($jumlah_menunggu - 10) . " pendaftar lainnya</em></li>";
        }
        $pesan_error .= "</ul><br>Silakan proses terlebih dahulu (terima atau tolak) semua pendaftar tersebut sebelum melanjutkan naik kelas. Anda bisa menggunakan tombol <strong>'Proses Semua Pendaftar'</strong> di halaman pendaftaran untuk mempercepat.";
        
        echo json_encode(['success' => false, 'message' => $pesan_error]);
        exit;
    }
}
// =======================================================================

$kkm = 75;
$maksMapelTidakLulus = 2;
$maksAlfa = 10;
$maksIzinSakit = 30;

$conn->begin_transaction();

try {
    // Update kapasitas kelas
    foreach ($kapasitas_data as $id_kelas => $kapasitas) {
        $id_kelas = (int) $id_kelas;
        $kapasitas = (int) $kapasitas;
        if ($id_kelas > 0 && $kapasitas > 0) {
            $conn->query("UPDATE kelas SET kapasitas = $kapasitas WHERE id_kelas = $id_kelas");
        }
    }

    // Nonaktifkan semua tahun ajaran
    $conn->query("UPDATE tahun_ajaran SET status = 'nonaktif'");

    // Aktifkan tahun ajaran baru
    $stmt = $conn->prepare("UPDATE tahun_ajaran SET status = 'aktif' WHERE id_tahun_ajaran = ?");
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    // Update wali kelas
    $stmtWali = $conn->prepare("UPDATE kelas SET id_wali_kelas = ? WHERE id_kelas = ?");
    foreach ($wali_kelas as $id_kelas => $id_guru) {
        $id_kelas = (int) $id_kelas;
        $id_guru = (int) $id_guru;
        if ($id_kelas > 0 && $id_guru > 0) {
            $stmtWali->bind_param("ii", $id_guru, $id_kelas);
            $stmtWali->execute();
        }
    }
    $stmtWali->close();

    // ========== PERBAIKAN: Hanya siswa yang sudah punya kelas yang dihitung layak naik ==========
    $conn->query("DROP TEMPORARY TABLE IF EXISTS tmp_siswa_layak_naik");
    $createTempQuery = "
        CREATE TEMPORARY TABLE tmp_siswa_layak_naik AS
        SELECT 
            s.id_siswa,
            k.tingkat,
            k.nama_kelas,
            ROUND(AVG(n.nilai_angka), 2) AS rata_rata,
            SUM(CASE WHEN n.nilai_angka < $kkm THEN 1 ELSE 0 END) AS mapel_tidak_lulus,
            COALESCE(SUM(n.izin), 0) AS total_izin,
            COALESCE(SUM(n.sakit), 0) AS total_sakit,
            COALESCE(SUM(n.alfa), 0) AS total_alfa,
            COUNT(n.id_siswa) AS jumlah_nilai,
            CASE
                WHEN COUNT(n.id_siswa) > 0
                    AND AVG(n.nilai_angka) >= $kkm
                    AND SUM(CASE WHEN n.nilai_angka < $kkm THEN 1 ELSE 0 END) <= $maksMapelTidakLulus
                    AND COALESCE(SUM(n.alfa), 0) <= $maksAlfa
                    AND (COALESCE(SUM(n.izin), 0) + COALESCE(SUM(n.sakit), 0)) <= $maksIzinSakit
                THEN 1
                ELSE 0
            END AS layak_naik
        FROM siswa s
        JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN nilai n ON s.id_siswa = n.id_siswa
        WHERE s.status = 'aktif'
          AND s.id_kelas IS NOT NULL   -- HANYA SISWA YANG SUDAH PUNYA KELAS
        GROUP BY s.id_siswa, k.tingkat, k.nama_kelas
    ";
    $conn->query($createTempQuery);

    // Kelas 9 layak -> lulus
    $stmt = $conn->prepare("
        UPDATE siswa s
        JOIN tmp_siswa_layak_naik t ON s.id_siswa = t.id_siswa
        SET s.status = 'lulus', s.id_tahun_ajaran = ?
        WHERE t.tingkat = 9 AND t.layak_naik = 1 AND s.status = 'aktif'
    ");
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    // Kelas 8 layak -> naik ke kelas 9 (huruf sama)
    $stmt = $conn->prepare("
        UPDATE siswa s
        JOIN tmp_siswa_layak_naik t ON s.id_siswa = t.id_siswa
        JOIN kelas k_lama ON s.id_kelas = k_lama.id_kelas
        JOIN kelas k_baru 
            ON k_baru.tingkat = 9
            AND RIGHT(k_baru.nama_kelas, 1) = RIGHT(k_lama.nama_kelas, 1)
            AND k_baru.id_tahun_ajaran = ?
        SET s.id_kelas = k_baru.id_kelas,
            s.id_tahun_ajaran = ?
        WHERE t.tingkat = 8
        AND t.layak_naik = 1
        AND s.status = 'aktif'
    ");
    $stmt->bind_param("ii", $id_tahun_ajaran, $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    // Kelas 7 layak -> naik ke kelas 8 (huruf sama)
    $stmt = $conn->prepare("
        UPDATE siswa s
        JOIN tmp_siswa_layak_naik t ON s.id_siswa = t.id_siswa
        JOIN kelas k_lama ON s.id_kelas = k_lama.id_kelas
        JOIN kelas k_baru 
            ON k_baru.tingkat = 8
            AND RIGHT(k_baru.nama_kelas, 1) = RIGHT(k_lama.nama_kelas, 1)
            AND k_baru.id_tahun_ajaran = ?
        SET s.id_kelas = k_baru.id_kelas,
            s.id_tahun_ajaran = ?
        WHERE t.tingkat = 7
        AND t.layak_naik = 1
        AND s.status = 'aktif'
    ");
    $stmt->bind_param("ii", $id_tahun_ajaran, $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    // Siswa aktif tidak layak -> update tahun ajaran saja
    $stmt = $conn->prepare("
        UPDATE siswa s
        JOIN tmp_siswa_layak_naik t ON s.id_siswa = t.id_siswa
        SET s.id_tahun_ajaran = ?
        WHERE t.layak_naik = 0 AND s.status = 'aktif'
    ");
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

  // ========== TEMPATKAN SISWA BARU KE KELAS 7 ==========

// 1. PASTIKAN KELAS 7 TERSEDIA (BUAT OTOMATIS JIKA BELUM ADA)
$kelas7 = [];
$resultKelas7 = $conn->query("SELECT id_kelas, nama_kelas FROM kelas WHERE tingkat = 7 AND id_tahun_ajaran = $id_tahun_ajaran ORDER BY nama_kelas ASC");

if ($resultKelas7->num_rows === 0) {
    // Buat kelas 7 otomatis jika belum ada
    $defaultKelas7 = ['7A', '7B', '7C'];
    foreach ($defaultKelas7 as $namaKelas) {
        $insertKelas = $conn->prepare("
            INSERT INTO kelas (nama_kelas, tingkat, kapasitas, id_tahun_ajaran)
            VALUES (?, 7, 30, ?)
        ");
        $insertKelas->bind_param("si", $namaKelas, $id_tahun_ajaran);
        $insertKelas->execute();
    }
    // Ambil lagi setelah dibuat
    $resultKelas7 = $conn->query("SELECT id_kelas FROM kelas WHERE tingkat = 7 AND id_tahun_ajaran = $id_tahun_ajaran ORDER BY nama_kelas ASC");
}

while ($row = $resultKelas7->fetch_assoc()) {
    $kelas7[] = (int) $row['id_kelas'];
}

if (count($kelas7) === 0) {
    throw new Exception("Data kelas tingkat 7 belum tersedia untuk tahun ajaran ini.");
}

// 2. AMBIL SISWA BARU (TANPA FILTER STATUS, YANG PENTING BELUM PUNYA KELAS)
$resultSiswaBaru = $conn->query("
    SELECT id_siswa, nama, status 
    FROM siswa 
    WHERE (id_kelas IS NULL OR id_kelas = 0)
    AND status != 'lulus'
    ORDER BY nama ASC, id_siswa ASC
");

$stmtSiswaBaru = $conn->prepare("UPDATE siswa SET id_kelas = ?, id_tahun_ajaran = ?, status = 'aktif' WHERE id_siswa = ?");
$i = 0;
$jumlahSiswaBaru = 0;

while ($siswa = $resultSiswaBaru->fetch_assoc()) {
    $id_kelas_baru = $kelas7[$i % count($kelas7)];
    $stmtSiswaBaru->bind_param("iii", $id_kelas_baru, $id_tahun_ajaran, $siswa['id_siswa']);
    $stmtSiswaBaru->execute();
    $jumlahSiswaBaru++;
    $i++;
}
$stmtSiswaBaru->close();

// Optional: tambah info ke response
// $jumlahSiswaBaru ini bisa ditambahkan ke response json

    $conn->query("DROP TEMPORARY TABLE IF EXISTS tmp_siswa_layak_naik");
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Proses naik kelas berhasil dijalankan.'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Proses naik kelas gagal: ' . $e->getMessage()
    ]);
}
?>