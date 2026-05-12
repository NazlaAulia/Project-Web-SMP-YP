<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Metode tidak valid.'
    ]);
    exit;
}

$id_tahun_ajaran = isset($_POST['id_tahun_ajaran']) ? (int) $_POST['id_tahun_ajaran'] : 0;
$wali_kelas = isset($_POST['wali_kelas']) && is_array($_POST['wali_kelas']) ? $_POST['wali_kelas'] : [];
$kapasitas_data = isset($_POST['kapasitas']) && is_array($_POST['kapasitas']) ? $_POST['kapasitas'] : [];

if ($id_tahun_ajaran <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Tahun ajaran baru wajib dipilih.'
    ]);
    exit;
}

if (empty($wali_kelas)) {
    echo json_encode([
        'success' => false,
        'message' => 'Wali kelas wajib dipilih.'
    ]);
    exit;
}

/*
    Aturan kenaikan kelas:
    - Rata-rata nilai minimal 75
    - Mapel di bawah KKM maksimal 2
    - Alfa maksimal 10
    - Total izin + sakit maksimal 30
    - Siswa tidak punya nilai dianggap tidak naik kelas
*/
$kkm = 75;
$maksMapelTidakLulus = 2;
$maksAlfa = 10;
$maksIzinSakit = 30;

$conn->begin_transaction();

try {
    // ========== UPDATE KAPASITAS KELAS ==========
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
    $stmt = $conn->prepare("
        UPDATE tahun_ajaran
        SET status = 'aktif'
        WHERE id_tahun_ajaran = ?
    ");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    // Update wali kelas
    $stmtWali = $conn->prepare("UPDATE kelas SET id_wali_kelas = ? WHERE id_kelas = ?");
    if (!$stmtWali) throw new Exception($conn->error);
    foreach ($wali_kelas as $id_kelas => $id_guru) {
        $id_kelas = (int) $id_kelas;
        $id_guru = (int) $id_guru;
        if ($id_kelas > 0 && $id_guru > 0) {
            $stmtWali->bind_param("ii", $id_guru, $id_kelas);
            $stmtWali->execute();
        }
    }
    $stmtWali->close();

    // Buat temporary table untuk siswa yang layak naik
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
                WHEN 
                    COUNT(n.id_siswa) > 0
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
        GROUP BY s.id_siswa, k.tingkat, k.nama_kelas
    ";
    if (!$conn->query($createTempQuery)) throw new Exception($conn->error);

    // Kelas 9 yang layak -> lulus
    $stmt = $conn->prepare("
        UPDATE siswa s
        JOIN tmp_siswa_layak_naik t ON s.id_siswa = t.id_siswa
        SET s.status = 'lulus', s.id_tahun_ajaran = ?
        WHERE t.tingkat = 9 AND t.layak_naik = 1 AND s.status = 'aktif'
    ");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    // Kelas 8 layak -> naik ke kelas 9 (huruf sama)
    $stmt = $conn->prepare("
        UPDATE siswa s
        JOIN tmp_siswa_layak_naik t ON s.id_siswa = t.id_siswa
        JOIN kelas k_lama ON s.id_kelas = k_lama.id_kelas
        JOIN kelas k_baru ON k_baru.tingkat = 9 AND RIGHT(k_baru.nama_kelas, 1) = RIGHT(k_lama.nama_kelas, 1)
        SET s.id_kelas = k_baru.id_kelas, s.id_tahun_ajaran = ?
        WHERE t.tingkat = 8 AND t.layak_naik = 1 AND s.status = 'aktif'
    ");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    // Kelas 7 layak -> naik ke kelas 8 (huruf sama)
    $stmt = $conn->prepare("
        UPDATE siswa s
        JOIN tmp_siswa_layak_naik t ON s.id_siswa = t.id_siswa
        JOIN kelas k_lama ON s.id_kelas = k_lama.id_kelas
        JOIN kelas k_baru ON k_baru.tingkat = 8 AND RIGHT(k_baru.nama_kelas, 1) = RIGHT(k_lama.nama_kelas, 1)
        SET s.id_kelas = k_baru.id_kelas, s.id_tahun_ajaran = ?
        WHERE t.tingkat = 7 AND t.layak_naik = 1 AND s.status = 'aktif'
    ");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    // Siswa aktif tidak layak naik -> hanya update tahun ajaran
    $stmt = $conn->prepare("
        UPDATE siswa s
        JOIN tmp_siswa_layak_naik t ON s.id_siswa = t.id_siswa
        SET s.id_tahun_ajaran = ?
        WHERE t.layak_naik = 0 AND s.status = 'aktif'
    ");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    // Tempatkan siswa baru ke kelas 7 round robin
    $kelas7 = [];
    $resultKelas7 = $conn->query("SELECT id_kelas FROM kelas WHERE tingkat = 7 ORDER BY nama_kelas ASC");
    if (!$resultKelas7) throw new Exception($conn->error);
    while ($row = $resultKelas7->fetch_assoc()) {
        $kelas7[] = (int) $row['id_kelas'];
    }
    if (count($kelas7) === 0) {
        throw new Exception("Data kelas tingkat 7 belum tersedia.");
    }

    $resultSiswaBaru = $conn->query("
        SELECT id_siswa
        FROM siswa
        WHERE status = 'baru' AND id_kelas IS NULL
        ORDER BY nama ASC, id_siswa ASC
    ");
    if (!$resultSiswaBaru) throw new Exception($conn->error);

    $stmtSiswaBaru = $conn->prepare("
        UPDATE siswa
        SET id_kelas = ?, id_tahun_ajaran = ?, status = 'aktif'
        WHERE id_siswa = ?
    ");
    if (!$stmtSiswaBaru) throw new Exception($conn->error);

    $i = 0;
    while ($siswa = $resultSiswaBaru->fetch_assoc()) {
        $id_kelas_baru = $kelas7[$i % count($kelas7)];
        $stmtSiswaBaru->bind_param("iii", $id_kelas_baru, $id_tahun_ajaran, $siswa['id_siswa']);
        $stmtSiswaBaru->execute();
        $i++;
    }
    $stmtSiswaBaru->close();

    $conn->query("DROP TEMPORARY TABLE IF EXISTS tmp_siswa_layak_naik");
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Proses naik kelas berhasil dijalankan. Siswa yang memenuhi syarat naik kelas, siswa yang tidak memenuhi syarat tetap di kelas lama.'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Proses naik kelas gagal: ' . $e->getMessage()
    ]);
}
?>