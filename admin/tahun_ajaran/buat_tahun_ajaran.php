<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../koneksi.php';

try {
    $result = $conn->query("
        SELECT tahun_ajaran
        FROM tahun_ajaran
        ORDER BY tahun_ajaran DESC
        LIMIT 1
    ");

    if (!$result || $result->num_rows === 0) {
        $tahunBaru = '2026/2027';
    } else {
        $last = $result->fetch_assoc()['tahun_ajaran'];

        if (!preg_match('/^(\d{4})\/(\d{4})$/', $last, $match)) {
            throw new Exception('Format tahun ajaran terakhir tidak valid.');
        }

        $awal = (int) $match[1] + 1;
        $akhir = (int) $match[2] + 1;
        $tahunBaru = $awal . '/' . $akhir;
    }

    $cek = $conn->prepare("
        SELECT id_tahun_ajaran
        FROM tahun_ajaran
        WHERE tahun_ajaran = ?
        LIMIT 1
    ");
    $cek->bind_param("s", $tahunBaru);
    $cek->execute();
    $cekResult = $cek->get_result();

    if ($cekResult->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Tahun ajaran ' . $tahunBaru . ' sudah ada.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO tahun_ajaran (tahun_ajaran, status)
        VALUES (?, 'nonaktif')
    ");
    $stmt->bind_param("s", $tahunBaru);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Tahun ajaran ' . $tahunBaru . ' berhasil dibuat.',
        'tahun_ajaran' => $tahunBaru
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
