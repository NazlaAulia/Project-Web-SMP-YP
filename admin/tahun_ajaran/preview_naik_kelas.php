<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../koneksi.php';

try {
    $data = [];

    $query = "
        SELECT 
            s.id_siswa,
            s.nama,
            s.status,
            k.nama_kelas,
            k.tingkat
        FROM siswa s
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE s.status IN ('aktif', 'baru')
        ORDER BY 
            FIELD(s.status, 'aktif', 'baru'),
            k.tingkat ASC,
            k.nama_kelas ASC,
            s.nama ASC
    ";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception($conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $kelasLama = $row['nama_kelas'] ?: 'Belum ada kelas';
        $kelasBaru = '-';
        $keterangan = '';

        if ($row['status'] === 'baru') {
            $kelasBaru = '7A / 7B / 7C';
            $keterangan = 'Siswa baru akan ditempatkan ke kelas 7 secara merata';
        } else {
            $tingkat = (int) $row['tingkat'];
            $huruf = substr($kelasLama, -1);

            if ($tingkat === 7) {
                $kelasBaru = '8' . $huruf;
                $keterangan = 'Naik ke kelas 8';
            } elseif ($tingkat === 8) {
                $kelasBaru = '9' . $huruf;
                $keterangan = 'Naik ke kelas 9';
            } elseif ($tingkat === 9) {
                $kelasBaru = 'Lulus';
                $keterangan = 'Menjadi alumni/lulus';
            } else {
                $kelasBaru = $kelasLama;
                $keterangan = 'Tidak berubah';
            }
        }

        $data[] = [
            'nama' => $row['nama'],
            'kelas_lama' => $kelasLama,
            'kelas_baru' => $kelasBaru,
            'keterangan' => $keterangan
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Preview gagal: ' . $e->getMessage()
    ]);
}