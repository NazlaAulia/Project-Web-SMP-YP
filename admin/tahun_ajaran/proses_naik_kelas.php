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

$conn->begin_transaction();

try {
    $conn->query("UPDATE tahun_ajaran SET status = 'nonaktif'");

    $stmt = $conn->prepare("
        UPDATE tahun_ajaran
        SET status = 'aktif'
        WHERE id_tahun_ajaran = ?
    ");
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    $stmtWali = $conn->prepare("
        UPDATE kelas
        SET id_wali_kelas = ?
        WHERE id_kelas = ?
    ");

    foreach ($wali_kelas as $id_kelas => $id_guru) {
        $id_kelas = (int) $id_kelas;
        $id_guru = (int) $id_guru;

        if ($id_kelas > 0 && $id_guru > 0) {
            $stmtWali->bind_param("ii", $id_guru, $id_kelas);
            $stmtWali->execute();
        }
    }

    $stmtWali->close();

    $stmt = $conn->prepare("
        UPDATE siswa s
        JOIN kelas k ON s.id_kelas = k.id_kelas
        SET 
            s.status = 'lulus',
            s.id_tahun_ajaran = ?
        WHERE k.tingkat = 9
          AND s.status = 'aktif'
    ");
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE siswa s
        JOIN kelas k_lama ON s.id_kelas = k_lama.id_kelas
        JOIN kelas k_baru
            ON k_baru.tingkat = 9
           AND RIGHT(k_baru.nama_kelas, 1) = RIGHT(k_lama.nama_kelas, 1)
        SET 
            s.id_kelas = k_baru.id_kelas,
            s.id_tahun_ajaran = ?
        WHERE k_lama.tingkat = 8
          AND s.status = 'aktif'
    ");
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE siswa s
        JOIN kelas k_lama ON s.id_kelas = k_lama.id_kelas
        JOIN kelas k_baru
            ON k_baru.tingkat = 8
           AND RIGHT(k_baru.nama_kelas, 1) = RIGHT(k_lama.nama_kelas, 1)
        SET 
            s.id_kelas = k_baru.id_kelas,
            s.id_tahun_ajaran = ?
        WHERE k_lama.tingkat = 7
          AND s.status = 'aktif'
    ");
    $stmt->bind_param("i", $id_tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    $kelas7 = [];
    $resultKelas7 = $conn->query("
        SELECT id_kelas
        FROM kelas
        WHERE tingkat = 7
        ORDER BY nama_kelas ASC
    ");

    while ($row = $resultKelas7->fetch_assoc()) {
        $kelas7[] = (int) $row['id_kelas'];
    }

    if (count($kelas7) === 0) {
        throw new Exception("Data kelas tingkat 7 belum tersedia.");
    }

    $resultSiswaBaru = $conn->query("
        SELECT id_siswa
        FROM siswa
        WHERE status = 'baru'
          AND id_kelas IS NULL
        ORDER BY nama ASC, id_siswa ASC
    ");

    $stmtSiswaBaru = $conn->prepare("
        UPDATE siswa
        SET 
            id_kelas = ?,
            id_tahun_ajaran = ?,
            status = 'aktif'
        WHERE id_siswa = ?
    ");

    $i = 0;

    while ($siswa = $resultSiswaBaru->fetch_assoc()) {
        $id_siswa = (int) $siswa['id_siswa'];
        $id_kelas_baru = $kelas7[$i % count($kelas7)];

        $stmtSiswaBaru->bind_param(
            "iii",
            $id_kelas_baru,
            $id_tahun_ajaran,
            $id_siswa
        );

        $stmtSiswaBaru->execute();
        $i++;
    }

    $stmtSiswaBaru->close();

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