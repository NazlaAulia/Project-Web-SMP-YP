<?php
require_once '../koneksi.php';

function redirectBack($message, $status = 'success') {
    header("Location: data.html?import_status=" . urlencode($status) . "&import_message=" . urlencode($message));
    exit;
}

function normalisasiTanggalLahir($tanggal) {
    $tanggal = trim($tanggal);

    if ($tanggal === '') {
        return false;
    }

    $formatList = [
        'd/m/Y', // 10/05/2012
        'd-m-Y', // 10-05-2012
        'd.m.Y', // 10.05.2012
        'Y-m-d'  // 2012-05-10
    ];

    foreach ($formatList as $format) {
        $date = DateTime::createFromFormat('!' . $format, $tanggal);

        if ($date && $date->format($format) === $tanggal) {
            return $date->format('Y-m-d');
        }
    }

    return false;
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
    redirectBack("Koneksi database gagal.", "error");
}

if (!isset($_FILES['file_siswa']) || $_FILES['file_siswa']['error'] !== UPLOAD_ERR_OK) {
    redirectBack("File import belum dipilih.", "error");
}

$fileTmp = $_FILES['file_siswa']['tmp_name'];
$fileName = $_FILES['file_siswa']['name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($ext !== 'csv') {
    redirectBack("Untuk sementara gunakan file CSV dari Excel. Save As Excel ke format CSV.", "error");
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

        // Lewati baris kosong dan baris panduan yang diawali #
        if ($firstCell === '' || strpos($firstCell, '#') === 0) {
            continue;
        }

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

        $tanggalNormal = normalisasiTanggalLahir($tanggalLahir);

        if ($tanggalNormal === false) {
            $dilewati++;
            $errorRows[] = "Format tanggal lahir tidak valid untuk NISN: " . $nisn . ". Gunakan format DD/MM/YYYY, contoh: 10/05/2012";
            continue;
        }

        $tanggalLahir = $tanggalNormal;

        $cek = $conn->prepare("SELECT id_siswa FROM siswa WHERE nisn = ? LIMIT 1");
        if (!$cek) {
            throw new Exception("Prepare cek siswa gagal: " . $conn->error);
        }

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
        if (!$stmtKelas) {
            throw new Exception("Prepare cek kelas gagal: " . $conn->error);
        }

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
        if (!$stmtTahun) {
            throw new Exception("Prepare cek tahun ajaran gagal: " . $conn->error);
        }

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

        $berhasil++;
    }

    fclose($handle);
    $conn->commit();

    $message = "Import selesai. Berhasil: {$berhasil}. Dilewati: {$dilewati}.";

    if (!empty($errorRows)) {
        $message .= "\n\nCatatan:\n" . implode("\n", array_slice($errorRows, 0, 8));
    }

    /*
        Kalau tidak ada satu pun data yang berhasil masuk,
        popup harus dianggap gagal/error.
    */
    if ($berhasil === 0) {
        redirectBack($message, "error");
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