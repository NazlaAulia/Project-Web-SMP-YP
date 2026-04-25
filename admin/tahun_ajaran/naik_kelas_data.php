<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../koneksi.php';

try {
    $tahunAjaran = [];
    $result = $conn->query("
        SELECT id_tahun_ajaran, tahun_ajaran, status
        FROM tahun_ajaran
        ORDER BY id_tahun_ajaran DESC
    ");

    while ($row = $result->fetch_assoc()) {
        $tahunAjaran[] = $row;
    }

    $kelas = [];
    $result = $conn->query("
        SELECT id_kelas, nama_kelas, tingkat, id_wali_kelas
        FROM kelas
        ORDER BY tingkat ASC, nama_kelas ASC
    ");

    while ($row = $result->fetch_assoc()) {
        $kelas[] = $row;
    }

    $guru = [];
    $result = $conn->query("
        SELECT id_guru, nama, nip
        FROM guru
        ORDER BY nama ASC
    ");

    while ($row = $result->fetch_assoc()) {
        $guru[] = $row;
    }

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
        'message' => 'Gagal mengambil data naik kelas.'
    ]);
}