<?php
include '../koneksi.php';

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    echo "<script>
        alert('Data tidak lengkap.');
        window.location.href = '/admin/admin_pendaftaran.php';
    </script>";
    exit;
}

$id_pendaftaran = (int) $_GET['id'];
$status = $_GET['status'];

if (!in_array($status, ['diterima', 'ditolak'])) {
    echo "<script>
        alert('Status tidak valid.');
        window.location.href = '/admin/admin_pendaftaran.php';
    </script>";
    exit;
}

/*
    LOGIKA:
    - File ini hanya mengubah status di tabel pendaftaran.
    - Kalau status berubah menjadi 'diterima',
      trigger database otomatis memasukkan data ke tabel siswa.
    - Data siswa masuk sebagai siswa baru, belum masuk kelas 7A/7B/7C.
*/

$stmt = mysqli_prepare($conn, "UPDATE pendaftaran SET status = ? WHERE id_pendaftaran = ?");

if (!$stmt) {
    echo "<script>
        alert('Query gagal: " . addslashes(mysqli_error($conn)) . "');
        window.location.href = '/admin/admin_pendaftaran.php';
    </script>";
    exit;
}

mysqli_stmt_bind_param($stmt, "si", $status, $id_pendaftaran);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    if ($status === 'diterima') {
        echo "<script>
            alert('Pendaftaran diterima. Data siswa otomatis masuk sebagai siswa baru.');
            window.location.href = '/admin/admin_pendaftaran.php';
        </script>";
    } else {
        echo "<script>
            alert('Pendaftaran ditolak.');
            window.location.href = '/admin/admin_pendaftaran.php';
        </script>";
    }

    exit;
} else {
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);

    echo "<script>
        alert('Gagal update status: " . addslashes($error) . "');
        window.location.href = '/admin/admin_pendaftaran.php';
    </script>";
    exit;
}
?>