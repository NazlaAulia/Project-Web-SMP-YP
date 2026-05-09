<?php
require_once '../koneksi.php';

function redirectBack($message) {
    echo "<script>
        alert(" . json_encode($message) . ");
        window.location.href = 'data.html';
    </script>";
    exit;
}

function buatUsername($nama, $nisn, $conn) {
    $namaBersih = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nama));
    $suffix = substr($nisn, -3);
    $base = $namaBersih . $suffix;

    if ($base === '') {
        $base = 'siswa' . $suffix;
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
    redirectBack("Koneksi database gagal.");
}

if (!isset($_FILES['file_siswa']) || $_FILES['file_siswa']['error'] !== UPLOAD_ERR_OK) {
    redirectBack("File import belum dipilih.");
}

$fileTmp = $_FILES['file_siswa']['tmp_name'];
$fileName = $_FILES['file_siswa']['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($ext !== 'csv') {
    redirectBack("Untuk sementara gunakan file CSV dari Excel. Save As Excel ke format CSV.");
}

$handle = fopen($fileTmp, 'r');
if (!$handle) {
    redirectBack("File CSV gagal dibaca.");
}

$header = fgetcsv($handle, 0, ';');

if (!$header) {
    fclose($handle);
    redirectBack("File CSV kosong.");
}

$header = array_map(function ($item) {
    $item = preg_replace('/^\xEF\xBB\xBF/', '', $item);
    return strtolower(trim($item));
}, $header);

$required = [
    'nisn',
    'nama',
    'jenis_kelamin',
    'tanggal_lahir',
    'alamat',
    'kelas',
    'tahun_ajaran'
];

foreach ($required as $kolom) {
    if (!in_array($kolom, $header)) {
        fclose($handle);
        redirectBack("Kolom wajib tidak ada: " . $kolom);
    }
}

$index = array_flip($header);

$berhasil = 0;
$dilewati = 0;
$errorRows = [];

$conn->begin_transaction();

try {
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $nisn = trim($row[$index['nisn']] ?? '');
        $nama = trim($row[$index['nama']] ?? '');
        $jenisKelamin = strtoupper(trim($row[$index['jenis_kelamin']] ?? ''));
        $tanggalLahir = trim($row[$index['tanggal_lahir']] ?? '');
        $alamat = trim($row[$index['alamat']] ?? '');
        $namaKelas = strtoupper(trim($row[$index['kelas']] ?? ''));
        $tahunAjaran = trim($row[$index['tahun_ajaran']] ?? '');

        if (
            $nisn === '' ||
            $nama === '' ||
            $jenisKelamin === '' ||
            $tanggalLahir === '' ||
            $alamat === '' ||
            $namaKelas === '' ||
            $tahunAjaran === ''
        ) {
            $dilewati++;
            $errorRows[] = "Data tidak lengkap untuk NISN: " . ($nisn ?: "-");
            continue;
        }

        if (!in_array($jenisKelamin, ['L', 'P'])) {
            $dilewati++;
            $errorRows[] = "Jenis kelamin tidak valid untuk NISN: " . $nisn;
            continue;
        }

        $tanggalObj = DateTime::createFromFormat('Y-m-d', $tanggalLahir);
        if (!$tanggalObj || $tanggalObj->format('Y-m-d') !== $tanggalLahir) {
            $dilewati++;
            $errorRows[] = "Format tanggal lahir harus YYYY-MM-DD untuk NISN: " . $nisn;
            continue;
        }

        $cek = $conn->prepare("SELECT id_siswa FROM siswa WHERE nisn = ? LIMIT 1");
        $cek->bind_param("s", $nisn);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $cek->close();
            $dilewati++;
            continue;
        }

        $cek->close();

        $stmtKelas = $conn->prepare("SELECT id_kelas FROM kelas WHERE UPPER(nama_kelas) = ? LIMIT 1");
        $stmtKelas->bind_param("s", $namaKelas);
        $stmtKelas->execute();
        $resultKelas = $stmtKelas->get_result();
        $kelas = $resultKelas->fetch_assoc();
        $stmtKelas->close();

        if (!$kelas) {
            $dilewati++;
            $errorRows[] = "Kelas tidak ditemukan untuk NISN " . $nisn . ": " . $namaKelas;
            continue;
        }

        $stmtTahun = $conn->prepare("SELECT id_tahun_ajaran FROM tahun_ajaran WHERE tahun_ajaran = ? LIMIT 1");
        $stmtTahun->bind_param("s", $tahunAjaran);
        $stmtTahun->execute();
        $resultTahun = $stmtTahun->get_result();
        $tahun = $resultTahun->fetch_assoc();
        $stmtTahun->close();

        if (!$tahun) {
            $dilewati++;
            $errorRows[] = "Tahun ajaran tidak ditemukan untuk NISN " . $nisn . ": " . $tahunAjaran;
            continue;
        }

        $idKelas = (int)$kelas['id_kelas'];
        $idTahun = (int)$tahun['id_tahun_ajaran'];

        $stmtSiswa = $conn->prepare("
            INSERT INTO siswa (
                nisn,
                nama,
                jenis_kelamin,
                tanggal_lahir,
                alamat,
                id_kelas,
                id_tahun_ajaran,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif')
        ");

        if (!$stmtSiswa) {
            throw new Exception("Prepare insert siswa gagal: " . $conn->error);
        }

        $stmtSiswa->bind_param(
            "sssssii",
            $nisn,
            $nama,
            $jenisKelamin,
            $tanggalLahir,
            $alamat,
            $idKelas,
            $idTahun
        );

        if (!$stmtSiswa->execute()) {
            throw new Exception("Gagal insert siswa NISN " . $nisn . ": " . $stmtSiswa->error);
        }

        $idSiswa = $stmtSiswa->insert_id;
        $stmtSiswa->close();

        $username = buatUsername($nama, $nisn, $conn);
        $passwordHash = password_hash($nisn, PASSWORD_DEFAULT);
        $roleIdSiswa = 3;

        $stmtUser = $conn->prepare("
            INSERT INTO user (username, password, role_id, id_siswa)
            VALUES (?, ?, ?, ?)
        ");

        if (!$stmtUser) {
            throw new Exception("Prepare insert user gagal: " . $conn->error);
        }

        $stmtUser->bind_param("ssii", $username, $passwordHash, $roleIdSiswa, $idSiswa);

        if (!$stmtUser->execute()) {
            throw new Exception("Gagal membuat user untuk NISN " . $nisn . ": " . $stmtUser->error);
        }

        $stmtUser->close();

        $berhasil++;
    }

    fclose($handle);
    $conn->commit();

    $message = "Import selesai. Berhasil: {$berhasil}. Dilewati: {$dilewati}.";

    if (!empty($errorRows)) {
        $message .= "\\n\\nCatatan:\\n" . implode("\\n", array_slice($errorRows, 0, 8));
    }

    redirectBack($message);
} catch (Exception $e) {
    fclose($handle);
    $conn->rollback();
    redirectBack("Import gagal: " . $e->getMessage());
}
?>
