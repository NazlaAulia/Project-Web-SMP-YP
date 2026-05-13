<?php
include '../koneksi.php';
header('Content-Type: application/json');
$id = (int)$_GET['id'];
if ($id > 0) {
    $query = "UPDATE pendaftaran SET wa_sent = 1 WHERE id_pendaftaran = $id";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
}
?>