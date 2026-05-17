<?php
require_once __DIR__ . '/../koneksi.php';
session_start();

// DEBUG: Lihat isi session (akan muncul di error log)
error_log("=== KUNCI JADWAL DEBUG ===");
error_log("SESSION: " . print_r($_SESSION, true));

// Cek apakah session role ada
if (!isset($_SESSION['role'])) {
    http_response_code(403);
    echo json_encode([
        'success'=>false, 
        'message'=>'Session tidak ditemukan. Silakan login ulang.'
    ]);
    exit;
}

// Cek apakah role admin
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success'=>false, 
        'message'=>"Akses ditolak. Role Anda: " . $_SESSION['role']
    ]);
    exit;
}

$query = "SELECT id_tahun_ajaran, jadwal_locked FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $id_tahun_aktif = $row['id_tahun_ajaran'];
    
    if ($row['jadwal_locked'] == 1) {
        echo json_encode(['success'=>false,'message'=>'Jadwal sudah terkunci sebelumnya']);
        exit;
    }
    
    // Mulai transaction
    $conn->begin_transaction();
    
    try {
        // 1. Kunci tahun ajaran
        $stmt = $conn->prepare("UPDATE tahun_ajaran SET jadwal_locked = 1 WHERE id_tahun_ajaran = ?");
        $stmt->bind_param("i", $id_tahun_aktif);
        $stmt->execute();
        $stmt->close();
        
        // 2. Update semua jadwal di tahun ajaran tersebut menjadi 'fix'
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