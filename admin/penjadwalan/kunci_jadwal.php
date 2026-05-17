<?php
// Pastikan session dimulai dengan konfigurasi yang benar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../koneksi.php';

// Coba ambil session dari cookie manual
if (empty($_SESSION) && isset($_COOKIE[session_name()])) {
    // Session mungkin tidak kebaca, coba lagi
    session_start();
}

// DEBUG: Tulis ke file untuk cek
file_put_contents(__DIR__ . '/debug_session.txt', date('Y-m-d H:i:s') . " - " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Jika masih kosong, coba cek user dari database (alternatif)
if (!isset($_SESSION['role']) || empty($_SESSION['role'])) {
    // Coba cek apakah ada user login lewat cookie lain
    if (isset($_COOKIE['user_id']) || isset($_COOKIE['id_user'])) {
        $user_id = $_COOKIE['user_id'] ?? $_COOKIE['id_user'] ?? 0;
        if ($user_id) {
            $q = $conn->query("SELECT role FROM user WHERE id_user = $user_id");
            if ($q && $row = $q->fetch_assoc()) {
                $_SESSION['role'] = $row['role'];
                $_SESSION['user_id'] = $user_id;
            }
        }
    }
}

// Cek final
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Akses ditolak. Role: ' . ($_SESSION['role'] ?? 'kosong') . '. Silakan login ulang sebagai admin.'
    ]);
    exit;
}

// Lanjutkan kunci jadwal...
$query = "SELECT id_tahun_ajaran, jadwal_locked FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $id_tahun_aktif = $row['id_tahun_ajaran'];
    
    if ($row['jadwal_locked'] == 1) {
        echo json_encode(['success'=>false,'message'=>'Jadwal sudah terkunci sebelumnya']);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("UPDATE tahun_ajaran SET jadwal_locked = 1 WHERE id_tahun_ajaran = ?");
        $stmt->bind_param("i", $id_tahun_aktif);
        $stmt->execute();
        $stmt->close();
        
        $stmtFix = $conn->prepare("UPDATE jadwal SET status = 'fix' WHERE id_tahun_ajaran = ?");
        $stmtFix->bind_param("i", $id_tahun_aktif);
        $stmtFix->execute();
        $affectedRows = $stmtFix->affected_rows;
        $stmtFix->close();
        
        $conn->commit();
        
        echo json_encode([
            'success'=>true, 
            'message'=>"Jadwal berhasil dikunci. $affectedRows jadwal diupdate menjadi fix."
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false, 'message'=>'Gagal mengunci: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success'=>false,'message'=>'Tidak ada tahun ajaran aktif']);
}
$conn->close();
?>