<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../koneksi.php';

if (!isset($conn) || !$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database tidak ditemukan.'
    ]);
    exit;
}

// ===============================
// MASTER HARI & JAM
// ===============================
$master_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

$master_jam = [
    '07:00-08:00',
    '08:00-09:00',
    '09:00-10:00',
    '10:00-11:00',
    '11:00-12:00'
];

// Gabungkan hari dan jam jadi slot
$slots = [];

foreach ($master_hari as $hari) {
    foreach ($master_jam as $jam) {
        $slots[] = [
            'hari' => $hari,
            'jam'  => $jam
        ];
    }
}

try {
    $conn->begin_transaction();

    // ===============================
    // AMBIL DATA KELAS
    // ===============================
    $kelas = [];

    $qKelas = $conn->query("
        SELECT id_kelas, nama_kelas 
        FROM kelas 
        ORDER BY tingkat ASC, nama_kelas ASC
    ");

    if (!$qKelas) {
        throw new Exception('Gagal mengambil data kelas: ' . $conn->error);
    }

    while ($row = $qKelas->fetch_assoc()) {
        $kelas[] = $row;
    }

    if (empty($kelas)) {
        throw new Exception('Data kelas masih kosong.');
    }

    // ===============================
    // AMBIL DATA MAPEL
    // ===============================
    $mapel = [];

    $qMapel = $conn->query("
        SELECT id_mapel, nama_mapel 
        FROM mapel 
        ORDER BY id_mapel ASC
    ");

    if (!$qMapel) {
        throw new Exception('Gagal mengambil data mapel: ' . $conn->error);
    }

    while ($row = $qMapel->fetch_assoc()) {
        $mapel[] = $row;
    }

    if (empty($mapel)) {
        throw new Exception('Data mata pelajaran masih kosong.');
    }

    // ===============================
    // AMBIL DATA GURU PER MAPEL
    // ===============================
    $guru_by_mapel = [];
    $beban_guru = [];

    $qGuru = $conn->query("
        SELECT id_guru, nama, id_mapel 
        FROM guru 
        WHERE id_mapel IS NOT NULL
        ORDER BY id_guru ASC
    ");

    if (!$qGuru) {
        throw new Exception('Gagal mengambil data guru: ' . $conn->error);
    }

    while ($row = $qGuru->fetch_assoc()) {
        $id_mapel = (int)$row['id_mapel'];

        if (!isset($guru_by_mapel[$id_mapel])) {
            $guru_by_mapel[$id_mapel] = [];
        }

        $guru_by_mapel[$id_mapel][] = [
            'id_guru' => (int)$row['id_guru'],
            'nama'    => $row['nama']
        ];

        $beban_guru[(int)$row['id_guru']] = 0;
    }

    // ===============================
    // BERSIHKAN DATA LAMA
    // ===============================
    // Karena request_jadwal punya foreign key ke jadwal,
    // request lama harus dibersihkan dulu sebelum jadwal lama dihapus.
    $jumlah_request_lama = 0;

    $qReq = $conn->query("SELECT COUNT(*) AS total FROM request_jadwal");
    if ($qReq) {
        $jumlah_request_lama = (int)$qReq->fetch_assoc()['total'];
    }

    if (!$conn->query("DELETE FROM request_jadwal")) {
        throw new Exception('Gagal menghapus request jadwal lama: ' . $conn->error);
    }

    if (!$conn->query("DELETE FROM jadwal")) {
        throw new Exception('Gagal menghapus jadwal lama: ' . $conn->error);
    }

    // Reset auto increment agar id_jadwal rapi dari awal lagi
    $conn->query("ALTER TABLE jadwal AUTO_INCREMENT = 1");

    // ===============================
    // PREPARE INSERT JADWAL
    // ===============================
    $stmtInsert = $conn->prepare("
        INSERT INTO jadwal 
        (id_guru, id_kelas, id_mapel, hari, jam)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmtInsert) {
        throw new Exception('Gagal prepare insert jadwal: ' . $conn->error);
    }

    // Penanda agar tidak bentrok
    $kelas_terpakai = [];
    $guru_terpakai = [];

    $jumlah_berhasil = 0;
    $gagal = [];

    // ===============================
    // PROSES GENERATE JADWAL
    // ===============================
    foreach ($kelas as $index_kelas => $data_kelas) {
        $id_kelas = (int)$data_kelas['id_kelas'];
        $nama_kelas = $data_kelas['nama_kelas'];

        foreach ($mapel as $index_mapel => $data_mapel) {
            $id_mapel = (int)$data_mapel['id_mapel'];
            $nama_mapel = $data_mapel['nama_mapel'];

            $berhasil_dipasang = false;

            // Rotasi slot supaya jadwal tidak selalu mulai dari Senin jam pertama
            $slot_count = count($slots);
            $start_index = ($index_kelas * 3 + $index_mapel * 2) % $slot_count;

            $slot_urut = [];

            for ($i = 0; $i < $slot_count; $i++) {
                $slot_urut[] = $slots[($start_index + $i) % $slot_count];
            }

            foreach ($slot_urut as $slot) {
                $hari = $slot['hari'];
                $jam  = $slot['jam'];

                $key_kelas = $id_kelas . '|' . $hari . '|' . $jam;

                // Kelas tidak boleh punya 2 mapel di jam yang sama
                if (isset($kelas_terpakai[$key_kelas])) {
                    continue;
                }

                $guru_tersedia = $guru_by_mapel[$id_mapel] ?? [];

                // Kalau belum ada guru untuk mapel ini, tetap buat jadwal dengan guru NULL
                if (empty($guru_tersedia)) {
                    $id_guru_null = null;

                    $stmtInsert->bind_param(
                        "iiiss",
                        $id_guru_null,
                        $id_kelas,
                        $id_mapel,
                        $hari,
                        $jam
                    );

                    if (!$stmtInsert->execute()) {
                        throw new Exception('Gagal insert jadwal tanpa guru: ' . $stmtInsert->error);
                    }

                    $kelas_terpakai[$key_kelas] = true;
                    $jumlah_berhasil++;
                    $berhasil_dipasang = true;

                    break;
                }

                // Urutkan guru berdasarkan beban paling sedikit
                usort($guru_tersedia, function ($a, $b) use ($beban_guru) {
                    $bebanA = $beban_guru[$a['id_guru']] ?? 0;
                    $bebanB = $beban_guru[$b['id_guru']] ?? 0;

                    if ($bebanA === $bebanB) {
                        return $a['id_guru'] <=> $b['id_guru'];
                    }

                    return $bebanA <=> $bebanB;
                });

                foreach ($guru_tersedia as $guru) {
                    $id_guru = (int)$guru['id_guru'];
                    $key_guru = $id_guru . '|' . $hari . '|' . $jam;

                    // Guru tidak boleh mengajar 2 kelas di jam yang sama
                    if (isset($guru_terpakai[$key_guru])) {
                        continue;
                    }

                    $stmtInsert->bind_param(
                        "iiiss",
                        $id_guru,
                        $id_kelas,
                        $id_mapel,
                        $hari,
                        $jam
                    );

                    if (!$stmtInsert->execute()) {
                        throw new Exception('Gagal insert jadwal: ' . $stmtInsert->error);
                    }

                    $kelas_terpakai[$key_kelas] = true;
                    $guru_terpakai[$key_guru] = true;
                    $beban_guru[$id_guru] = ($beban_guru[$id_guru] ?? 0) + 1;

                    $jumlah_berhasil++;
                    $berhasil_dipasang = true;

                    break 2;
                }
            }

            if (!$berhasil_dipasang) {
                $gagal[] = [
                    'kelas' => $nama_kelas,
                    'mapel' => $nama_mapel
                ];
            }
        }
    }

    $stmtInsert->close();

    $conn->commit();

    $message = 'Generate jadwal berhasil. ' . $jumlah_berhasil . ' jadwal dibuat.';

    if ($jumlah_request_lama > 0) {
        $message .= ' ' . $jumlah_request_lama . ' request jadwal lama dibersihkan.';
    }

    if (!empty($gagal)) {
        $message .= ' Ada ' . count($gagal) . ' jadwal yang belum bisa dipasang.';
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'total_dibuat' => $jumlah_berhasil,
        'total_gagal' => count($gagal),
        'gagal' => $gagal
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

if (isset($conn)) {
    $conn->close();
}
?>