<?php
require_once 'koneksi.php';

function redirectBack($message, $status = 'success') {
    header("Location: fiturguru.html?import_status=" . urlencode($status) . "&import_message=" . urlencode($message));
    exit;
}

function buatUsernameGuru($nama, $nip, $conn) {
    $namaBersih = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nama));
    $suffix = substr($nip, -3);
    $base = $namaBersih . $suffix;

    if ($base === '') {
        $base = 'guru' . $suffix;
    }

    $username = $base;
    $i = 1;

    while (true) {
        $stmt = $conn->prepare("SELECT id_user FROM user WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            return $username;
        }

        $stmt->close();
        $username = $base . $i;
        $i++;
    }
}

if (!isset($conn) || $conn->connect_error) {
    redirectBack("Koneksi database gagal.", "error");
}

if (!isset($_FILES['file_guru']) || $_FILES['file_guru']['error'] !== UPLOAD_ERR_OK) {
    redirectBack("File import belum dipilih.", "error");
}

$fileTmp = $_FILES['file_guru']['tmp_name'];
$fileName = $_FILES['file_guru']['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($ext !== 'csv') {
    redirectBack("Gunakan file CSV dari template Excel.", "error");
}

$handle = fopen($fileTmp, 'r');
if (!$handle) {
    redirectBack("File CSV gagal dibaca.", "error");
}

$header = fgetcsv($handle, 0, ';');

if (!$header) {
    fclose($handle);
    redirectBack("File CSV kosong.", "error");
}

$header = array_map(function ($item) {
    $item = preg_replace('/^\xEF\xBB\xBF/', '', $item);
    return strtolower(trim($item));
}, $header);

$required = ['nip', 'nama', 'email', 'jenis_kelamin', 'mapel'];

foreach ($required as $kolom) {
    if (!in_array($kolom, $header)) {
        fclose($handle);
        redirectBack("Kolom wajib tidak ada: " . $kolom, "error");
    }
}

$index = array_flip($header);

$berhasil = 0;
$dilewati = 0;
$errorRows = [];

$conn->begin_transaction();

try {
  while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $firstCell = trim($row[0] ?? '');

    if ($firstCell === '' || strpos($firstCell, '#') === 0) {
        continue;
    }

    $nip = trim($row[$index['nip']] ?? '');

        $nama = trim($row[$index['nama']] ?? '');
        $email = trim($row[$index['email']] ?? '');
        $jenisKelamin = strtoupper(trim($row[$index['jenis_kelamin']] ?? ''));
        $namaMapel = trim($row[$index['mapel']] ?? '');

        if ($nip === '' || $nama === '' || $email === '' || $jenisKelamin === '' || $namaMapel === '') {
            $dilewati++;
            $errorRows[] = "Data tidak lengkap untuk NIP: " . ($nip ?: "-");
            continue;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $dilewati++;
            $errorRows[] = "Email tidak valid untuk NIP: " . $nip;
            continue;
        }

        if (!in_array($jenisKelamin, ['L', 'P'])) {
            $dilewati++;
            $errorRows[] = "Jenis kelamin harus L atau P untuk NIP: " . $nip;
            continue;
        }

        $cekGuru = $conn->prepare("SELECT id_guru FROM guru WHERE nip = ? OR email = ? LIMIT 1");
        if (!$cekGuru) {
            throw new Exception("Prepare cek guru gagal: " . $conn->error);
        }

        $cekGuru->bind_param("ss", $nip, $email);
        $cekGuru->execute();
        $cekGuru->store_result();

        if ($cekGuru->num_rows > 0) {
            $cekGuru->close();
            $dilewati++;
            continue;
        }

        $cekGuru->close();

        $stmtMapel = $conn->prepare("SELECT id_mapel FROM mapel WHERE LOWER(nama_mapel) = LOWER(?) LIMIT 1");
        if (!$stmtMapel) {
            throw new Exception("Prepare cek mapel gagal: " . $conn->error);
        }

        $stmtMapel->bind_param("s", $namaMapel);
        $stmtMapel->execute();
        $resultMapel = $stmtMapel->get_result();
        $mapel = $resultMapel->fetch_assoc();
        $stmtMapel->close();

        if (!$mapel) {
            $dilewati++;
            $errorRows[] = "Mapel tidak ditemukan untuk NIP " . $nip . ": " . $namaMapel;
            continue;
        }

        $idMapel = (int)$mapel['id_mapel'];

        $stmtGuru = $conn->prepare("
            INSERT INTO guru (nip, nama, email, jenis_kelamin, id_mapel)
            VALUES (?, ?, ?, ?, ?)
        ");

        if (!$stmtGuru) {
            throw new Exception("Prepare insert guru gagal: " . $conn->error);
        }

        $stmtGuru->bind_param("ssssi", $nip, $nama, $email, $jenisKelamin, $idMapel);

        if (!$stmtGuru->execute()) {
            throw new Exception("Gagal insert guru NIP " . $nip . ": " . $stmtGuru->error);
        }

        $idGuru = $stmtGuru->insert_id;
        $stmtGuru->close();

        $username = buatUsernameGuru($nama, $nip, $conn);
        $passwordHash = password_hash($nip, PASSWORD_DEFAULT);
        $roleIdGuru = 2;

        $stmtUser = $conn->prepare("
            INSERT INTO user (username, password, role_id, id_guru)
            VALUES (?, ?, ?, ?)
        ");

        if (!$stmtUser) {
            throw new Exception("Prepare insert user gagal: " . $conn->error);
        }

        $stmtUser->bind_param("ssii", $username, $passwordHash, $roleIdGuru, $idGuru);

        if (!$stmtUser->execute()) {
            throw new Exception("Gagal membuat akun user untuk NIP " . $nip . ": " . $stmtUser->error);
        }

        $stmtUser->close();

        $berhasil++;
    }

    fclose($handle);
    $conn->commit();

    $message = "Import selesai. Berhasil: {$berhasil}. Dilewati: {$dilewati}.";

    if (!empty($errorRows)) {
       $message .= "\n\nCatatan:\n" . implode("\n", array_slice($errorRows, 0, 8));

    }

    redirectBack($message, "success");
} catch (Exception $e) {
    if (is_resource($handle)) {
        fclose($handle);
    }

    $conn->rollback();
    redirectBack("Import gagal: " . $e->getMessage(), "error");
}
?>
