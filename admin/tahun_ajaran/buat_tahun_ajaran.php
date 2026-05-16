<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../koneksi.php';

try {
    // START TRANSACTION
    $conn->begin_transaction();
    
    // 1. Ambil tahun ajaran terakhir
    $result = $conn->query("
        SELECT tahun_ajaran
        FROM tahun_ajaran
        ORDER BY tahun_ajaran DESC
        LIMIT 1
    ");

    if (!$result || $result->num_rows === 0) {
        $tahunBaru = '2025/2026';
    } else {
        $last = $result->fetch_assoc()['tahun_ajaran'];

        if (!preg_match('/^(\d{4})\/(\d{4})$/', $last, $match)) {
            throw new Exception('Format tahun ajaran terakhir tidak valid.');
        }

        $awal = (int) $match[1] + 1;
        $akhir = (int) $match[2] + 1;
        $tahunBaru = $awal . '/' . $akhir;
    }

    // 2. Cek apakah tahun ajaran sudah ada
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

    // 3. Insert tahun ajaran baru
    $stmt = $conn->prepare("
        INSERT INTO tahun_ajaran (tahun_ajaran, status)
        VALUES (?, 'nonaktif')
    ");
    $stmt->bind_param("s", $tahunBaru);
    $stmt->execute();
    
    $id_tahun_baru = $conn->insert_id;
    
    // ============ 4. COPY KELAS DARI TAHUN SEBELUMNYA ============
    $kelasTersalin = 0;
    
    // Cari tahun ajaran sebelumnya (yang status aktif atau terbaru selain yang baru)
    $queryPrev = "
        SELECT id_tahun_ajaran 
        FROM tahun_ajaran 
        WHERE tahun_ajaran != '$tahunBaru'
        ORDER BY id_tahun_ajaran DESC 
        LIMIT 1
    ";
    $prevResult = $conn->query($queryPrev);
    
    if ($prevResult && $prevResult->num_rows > 0) {
        $prevYear = $prevResult->fetch_assoc();
        $id_tahun_lama = $prevYear['id_tahun_ajaran'];
        
        // Ambil semua kelas dari tahun sebelumnya (langsung dari tabel kelas)
        $queryKelas = "
            SELECT id_kelas, nama_kelas, tingkat, kapasitas, id_wali_kelas
            FROM kelas
            WHERE id_tahun_ajaran = ?
        ";
        $stmtKelas = $conn->prepare($queryKelas);
        $stmtKelas->bind_param("i", $id_tahun_lama);
        $stmtKelas->execute();
        $kelasResult = $stmtKelas->get_result();
        
        while ($kelas = $kelasResult->fetch_assoc()) {
            // Insert kelas baru untuk tahun ajaran baru (copy semua data)
            $insertKelas = $conn->prepare("
                INSERT INTO kelas (nama_kelas, tingkat, kapasitas, id_wali_kelas, id_tahun_ajaran)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertKelas->bind_param(
                "siiii", 
                $kelas['nama_kelas'], 
                $kelas['tingkat'], 
                $kelas['kapasitas'], 
                $kelas['id_wali_kelas'], 
                $id_tahun_baru
            );
            $insertKelas->execute();
            $kelasTersalin++;
        }
    }
    
    // 5. Jika tahun pertama atau belum ada kelas, buat default
    if ($kelasTersalin == 0) {
        $defaultClasses = [
            ['7A', 7, 30], ['7B', 7, 30], ['7C', 7, 30],
            ['8A', 8, 30], ['8B', 8, 30], ['8C', 8, 30],
            ['9A', 9, 30], ['9B', 9, 30], ['9C', 9, 30]
        ];
        
        foreach ($defaultClasses as $class) {
            $insertKelas = $conn->prepare("
                INSERT INTO kelas (nama_kelas, tingkat, kapasitas, id_tahun_ajaran)
                VALUES (?, ?, ?, ?)
            ");
            $insertKelas->bind_param("siii", $class[0], $class[1], $class[2], $id_tahun_baru);
            $insertKelas->execute();
            $kelasTersalin++;
        }
    }
    
    // COMMIT transaction
    $conn->commit();
    
    $message = "Tahun ajaran $tahunBaru berhasil dibuat dengan $kelasTersalin kelas.";
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'tahun_ajaran' => $tahunBaru,
        'jumlah_kelas' => $kelasTersalin
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>