<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../koneksi.php';

try {
    // Ambil parameter id_tahun_ajaran (default: tahun aktif)
    $id_tahun_ajaran_filter = isset($_GET['id_tahun_ajaran']) ? (int)$_GET['id_tahun_ajaran'] : 0;
    if ($id_tahun_ajaran_filter == 0) {
        $ta_aktif = $conn->query("SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status='aktif' LIMIT 1")->fetch_assoc();
        $id_tahun_ajaran_filter = $ta_aktif ? $ta_aktif['id_tahun_ajaran'] : 0;
    }

    // Ambil semua tahun ajaran untuk dropdown
    $tahunAjaran = [];
    $result = $conn->query("
        SELECT id_tahun_ajaran, tahun_ajaran, status
        FROM tahun_ajaran
        ORDER BY id_tahun_ajaran DESC
    ");
    while ($row = $result->fetch_assoc()) {
        $tahunAjaran[] = $row;
    }

    // Ambil kelas berdasarkan tahun ajaran yang dipilih
    $kelas = [];
    if ($id_tahun_ajaran_filter > 0) {
        $stmt = $conn->prepare("
            SELECT id_kelas, nama_kelas, tingkat, id_wali_kelas, kapasitas
            FROM kelas
            WHERE id_tahun_ajaran = ?
            ORDER BY tingkat ASC, nama_kelas ASC
        ");
        $stmt->bind_param("i", $id_tahun_ajaran_filter);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $kelas[] = $row;
        }
        $stmt->close();
    }

    // Ambil semua guru
    $guru = [];
    $result = $conn->query("
        SELECT id_guru, nama, nip
        FROM guru
        ORDER BY nama ASC
    ");
    while ($row = $result->fetch_assoc()) {
        $guru[] = $row;
    }

    // Hitung summary
    $siswaBaru = $conn->query("
        SELECT COUNT(*) AS total
        FROM siswa
        WHERE status = 'baru'
    ")->fetch_assoc()['total'];

    $siswaAktif = $conn->query("
        SELECT COUNT(*) AS total
        FROM siswa
        WHERE status = 'aktif'
    ")->fetch_assoc()['total'];

    $kelas9 = $conn->query("
        SELECT COUNT(*) AS total
        FROM siswa s
        JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE s.status = 'aktif'
          AND k.tingkat = 9
    ")->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'tahun_ajaran' => $tahunAjaran,
        'id_tahun_ajaran_terpilih' => $id_tahun_ajaran_filter,
        'kelas' => $kelas,
        'guru' => $guru,
        'summary' => [
            'siswa_baru' => (int) $siswaBaru,
            'siswa_aktif' => (int) $siswaAktif,
            'kelas_9' => (int) $kelas9
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data naik kelas: ' . $e->getMessage()
    ]);
}
?>