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
        $tahunBaru = '2026/2027';
        $isFirstYear = true;
    } else {
        $last = $result->fetch_assoc()['tahun_ajaran'];
        $isFirstYear = false;

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
    
    if (!$isFirstYear) {
        // Ambil tahun sebelumnya
        $queryPrev = "SELECT id_tahun_ajaran FROM tahun_ajaran 
                     WHERE tahun_ajaran = ?";
        $stmtPrev = $conn->prepare($queryPrev);
        $stmtPrev->bind_param("s", $last);
        $stmtPrev->execute();
        $prevResult = $stmtPrev->get_result();
        $prevYear = $prevResult->fetch_assoc();
        $id_tahun_lama = $prevYear['id_tahun_ajaran'];
        
        // Ambil semua kelas dari tahun sebelumnya
        $queryKelas = "
            SELECT k.id_kelas, k.nama_kelas, k.tingkat, k.kapasitas
            FROM kelas k
            INNER JOIN kelas_tahun kt ON k.id_kelas = kt.id_kelas
            WHERE kt.id_tahun_ajaran = ?
        ";
        $stmtKelas = $conn->prepare($queryKelas);
        $stmtKelas->bind_param("i", $id_tahun_lama);
        $stmtKelas->execute();
        $kelasResult = $stmtKelas->get_result();
        
        while ($kelas = $kelasResult->fetch_assoc()) {
            // Hubungkan kelas yang sudah ada dengan tahun ajaran baru
            $insertKT = $conn->prepare("
                INSERT INTO kelas_tahun (id_kelas, id_tahun_ajaran)
                VALUES (?, ?)
            ");
            $insertKT->bind_param("ii", $kelas['id_kelas'], $id_tahun_baru);
            $insertKT->execute();
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
            // Cek apakah kelas sudah ada di tabel kelas
            $cekKelas = $conn->prepare("SELECT id_kelas FROM kelas WHERE nama_kelas = ?");
            $cekKelas->bind_param("s", $class[0]);
            $cekKelas->execute();
            $resultKelas = $cekKelas->get_result();
            
            if ($resultKelas->num_rows > 0) {
                $existingKelas = $resultKelas->fetch_assoc();
                $id_kelas = $existingKelas['id_kelas'];
            } else {
                // Buat kelas baru
                $insertKelas = $conn->prepare("
                    INSERT INTO kelas (nama_kelas, tingkat, kapasitas)
                    VALUES (?, ?, ?)
                ");
                $insertKelas->bind_param("sii", $class[0], $class[1], $class[2]);
                $insertKelas->execute();
                $id_kelas = $conn->insert_id;
            }
            
            // Hubungkan dengan tahun ajaran baru
            $insertKT = $conn->prepare("
                INSERT INTO kelas_tahun (id_kelas, id_tahun_ajaran)
                VALUES (?, ?)
            ");
            $insertKT->bind_param("ii", $id_kelas, $id_tahun_baru);
            $insertKT->execute();
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