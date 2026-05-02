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

function formatJam($jam)
{
    return substr($jam, 0, 5);
}

function bobotKesulitan($tingkat)
{
    if ($tingkat === 'sulit') return 1;
    if ($tingkat === 'sedang') return 2;
    return 3;
}

function buatKey($id, $hari, $nomor_jp)
{
    return $id . '|' . $hari . '|' . $nomor_jp;
}

function blockBentrokKelas($kelas_terpakai, $id_kelas, $block)
{
    foreach ($block as $jp) {
        $key = buatKey($id_kelas, $jp['hari'], $jp['nomor_jp']);
        if (isset($kelas_terpakai[$key])) {
            return true;
        }
    }

    return false;
}

function blockBentrokGuru($guru_terpakai, $id_guru, $block)
{
    foreach ($block as $jp) {
        $key = buatKey($id_guru, $jp['hari'], $jp['nomor_jp']);
        if (isset($guru_terpakai[$key])) {
            return true;
        }
    }

    return false;
}

function tandaiKelasTerpakai(&$kelas_terpakai, $id_kelas, $block)
{
    foreach ($block as $jp) {
        $key = buatKey($id_kelas, $jp['hari'], $jp['nomor_jp']);
        $kelas_terpakai[$key] = true;
    }
}

function tandaiGuruTerpakai(&$guru_terpakai, $id_guru, $block)
{
    foreach ($block as $jp) {
        $key = buatKey($id_guru, $jp['hari'], $jp['nomor_jp']);
        $guru_terpakai[$key] = true;
    }
}

function ambilBlockJP($jp_per_hari, $hari, $jumlah_jp)
{
    $hasil = [];

    if (!isset($jp_per_hari[$hari])) {
        return $hasil;
    }

    $list = $jp_per_hari[$hari];
    $total = count($list);

    for ($i = 0; $i <= $total - $jumlah_jp; $i++) {
        $block = array_slice($list, $i, $jumlah_jp);
        $valid = true;

        for ($j = 0; $j < count($block) - 1; $j++) {
            $jp_sekarang = $block[$j];
            $jp_berikutnya = $block[$j + 1];

            // Nomor JP harus berurutan
            if ((int)$jp_berikutnya['nomor_jp'] !== (int)$jp_sekarang['nomor_jp'] + 1) {
                $valid = false;
                break;
            }

            // Jam harus nyambung, supaya tidak melompati waktu istirahat
            if ($jp_sekarang['jam_selesai'] !== $jp_berikutnya['jam_mulai']) {
                $valid = false;
                break;
            }
        }

        if ($valid) {
            $hasil[] = $block;
        }
    }

    return $hasil;
}

function urutkanBlockSesuaiMapel($blocks, $tingkat_kesulitan, $prioritas_pagi)
{
    usort($blocks, function ($a, $b) use ($tingkat_kesulitan, $prioritas_pagi) {
        $aMulai = (int)$a[0]['nomor_jp'];
        $bMulai = (int)$b[0]['nomor_jp'];

        // Mapel sulit / prioritas pagi: JP awal lebih dulu
        if ($prioritas_pagi == 1 || $tingkat_kesulitan === 'sulit') {
            return $aMulai <=> $bMulai;
        }

        // Mapel ringan: jangan terlalu dipaksa pagi, mulai dari tengah
        if ($tingkat_kesulitan === 'ringan') {
            $aScore = abs($aMulai - 5);
            $bScore = abs($bMulai - 5);
            return $aScore <=> $bScore;
        }

        // Mapel sedang: normal
        return $aMulai <=> $bMulai;
    });

    return $blocks;
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
    // AMBIL DATA JAM PELAJARAN
    // ===============================
    $jp_per_hari = [];
    $hari_list = [];

    $qJP = $conn->query("
        SELECT id_jp, hari, nomor_jp, jam_mulai, jam_selesai
        FROM jam_pelajaran
        WHERE aktif = 1
        ORDER BY 
            FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
            nomor_jp ASC
    ");

    if (!$qJP) {
        throw new Exception('Gagal mengambil data jam pelajaran: ' . $conn->error);
    }

    while ($row = $qJP->fetch_assoc()) {
        $hari = $row['hari'];

        if (!isset($jp_per_hari[$hari])) {
            $jp_per_hari[$hari] = [];
            $hari_list[] = $hari;
        }

        $jp_per_hari[$hari][] = [
            'id_jp' => (int)$row['id_jp'],
            'hari' => $row['hari'],
            'nomor_jp' => (int)$row['nomor_jp'],
            'jam_mulai' => $row['jam_mulai'],
            'jam_selesai' => $row['jam_selesai']
        ];
    }

    if (empty($jp_per_hari)) {
        throw new Exception('Data jam_pelajaran masih kosong.');
    }

    // ===============================
    // AMBIL DATA MAPEL + ATURAN MAPEL
    // ===============================
    $mapel = [];

    $qMapel = $conn->query("
        SELECT 
            m.id_mapel,
            m.nama_mapel,
            COALESCE(a.pertemuan_per_minggu, 1) AS pertemuan_per_minggu,
            COALESCE(a.jp_per_pertemuan, 1) AS jp_per_pertemuan,
            COALESCE(a.tingkat_kesulitan, 'sedang') AS tingkat_kesulitan,
            COALESCE(a.prioritas_pagi, 0) AS prioritas_pagi
        FROM mapel m
        LEFT JOIN aturan_mapel a ON m.id_mapel = a.id_mapel
        ORDER BY 
            FIELD(COALESCE(a.tingkat_kesulitan, 'sedang'), 'sulit', 'sedang', 'ringan'),
            COALESCE(a.prioritas_pagi, 0) DESC,
            m.id_mapel ASC
    ");

    if (!$qMapel) {
        throw new Exception('Gagal mengambil data mapel dan aturan mapel: ' . $conn->error);
    }

    while ($row = $qMapel->fetch_assoc()) {
        $mapel[] = [
            'id_mapel' => (int)$row['id_mapel'],
            'nama_mapel' => $row['nama_mapel'],
            'pertemuan_per_minggu' => (int)$row['pertemuan_per_minggu'],
            'jp_per_pertemuan' => (int)$row['jp_per_pertemuan'],
            'tingkat_kesulitan' => $row['tingkat_kesulitan'],
            'prioritas_pagi' => (int)$row['prioritas_pagi']
        ];
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
        $id_guru = (int)$row['id_guru'];

        if (!isset($guru_by_mapel[$id_mapel])) {
            $guru_by_mapel[$id_mapel] = [];
        }

        $guru_by_mapel[$id_mapel][] = [
            'id_guru' => $id_guru,
            'nama' => $row['nama']
        ];

        if (!isset($beban_guru[$id_guru])) {
            $beban_guru[$id_guru] = 0;
        }
    }

    // ===============================
    // BERSIHKAN DATA LAMA
    // ===============================
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

    $conn->query("ALTER TABLE jadwal AUTO_INCREMENT = 1");

    // ===============================
    // PREPARE INSERT JADWAL
    // ===============================
    $stmtInsert = $conn->prepare("
        INSERT INTO jadwal
        (id_guru, id_kelas, id_mapel, hari, jam, jp_mulai, jp_selesai, jumlah_jp)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmtInsert) {
        throw new Exception('Gagal prepare insert jadwal: ' . $conn->error);
    }

    // ===============================
    // PENANDA BENTROK
    // ===============================
    $kelas_terpakai = [];
    $guru_terpakai = [];
    $mapel_hari_terpakai = [];

    $jumlah_berhasil = 0;
    $gagal = [];

    // ===============================
    // PROSES GENERATE JADWAL JP
    // ===============================
    foreach ($kelas as $index_kelas => $data_kelas) {
        $id_kelas = (int)$data_kelas['id_kelas'];
        $nama_kelas = $data_kelas['nama_kelas'];

        foreach ($mapel as $index_mapel => $data_mapel) {
            $id_mapel = (int)$data_mapel['id_mapel'];
            $nama_mapel = $data_mapel['nama_mapel'];
            $pertemuan_per_minggu = max(1, (int)$data_mapel['pertemuan_per_minggu']);
            $jp_per_pertemuan = max(1, (int)$data_mapel['jp_per_pertemuan']);
            $tingkat_kesulitan = $data_mapel['tingkat_kesulitan'];
            $prioritas_pagi = (int)$data_mapel['prioritas_pagi'];

            for ($pertemuan = 1; $pertemuan <= $pertemuan_per_minggu; $pertemuan++) {
                $berhasil_dipasang = false;

                $hari_urut = $hari_list;

                // Rotasi hari agar tidak semua kelas mulai dari Senin
                $hari_count = count($hari_urut);
                $start_hari = ($index_kelas + $index_mapel + $pertemuan) % $hari_count;

                $hari_rotasi = [];
                for ($h = 0; $h < $hari_count; $h++) {
                    $hari_rotasi[] = $hari_urut[($start_hari + $h) % $hari_count];
                }

                // Kalau mapel prioritas pagi/sulit, tetap boleh semua hari,
                // yang diprioritaskan adalah JP awalnya.
                foreach ($hari_rotasi as $hari) {
                    $key_mapel_hari = $id_kelas . '|' . $id_mapel . '|' . $hari;

                    // Usahakan mapel yang sama tidak muncul 2 kali di hari yang sama
                    if (isset($mapel_hari_terpakai[$key_mapel_hari])) {
                        continue;
                    }

                    $blocks = ambilBlockJP($jp_per_hari, $hari, $jp_per_pertemuan);

                    if (empty($blocks)) {
                        continue;
                    }

                    $blocks = urutkanBlockSesuaiMapel($blocks, $tingkat_kesulitan, $prioritas_pagi);

                    foreach ($blocks as $block) {
                        if (blockBentrokKelas($kelas_terpakai, $id_kelas, $block)) {
                            continue;
                        }

                        $guru_tersedia = $guru_by_mapel[$id_mapel] ?? [];

                        // Kalau belum ada guru untuk mapel ini, tetap buat jadwal dengan guru NULL
                        if (empty($guru_tersedia)) {
                            $id_guru_insert = null;

                            $hari_insert = $hari;
                            $jam_insert = formatJam($block[0]['jam_mulai']) . '-' . formatJam($block[count($block) - 1]['jam_selesai']);
                            $jp_mulai = (int)$block[0]['nomor_jp'];
                            $jp_selesai = (int)$block[count($block) - 1]['nomor_jp'];
                            $jumlah_jp = $jp_per_pertemuan;

                            $stmtInsert->bind_param(
                                "iiissiii",
                                $id_guru_insert,
                                $id_kelas,
                                $id_mapel,
                                $hari_insert,
                                $jam_insert,
                                $jp_mulai,
                                $jp_selesai,
                                $jumlah_jp
                            );

                            if (!$stmtInsert->execute()) {
                                throw new Exception('Gagal insert jadwal tanpa guru: ' . $stmtInsert->error);
                            }

                            tandaiKelasTerpakai($kelas_terpakai, $id_kelas, $block);
                            $mapel_hari_terpakai[$key_mapel_hari] = true;

                            $jumlah_berhasil++;
                            $berhasil_dipasang = true;
                            break 2;
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
                            $id_guru_insert = (int)$guru['id_guru'];

                            if (blockBentrokGuru($guru_terpakai, $id_guru_insert, $block)) {
                                continue;
                            }

                            $hari_insert = $hari;
                            $jam_insert = formatJam($block[0]['jam_mulai']) . '-' . formatJam($block[count($block) - 1]['jam_selesai']);
                            $jp_mulai = (int)$block[0]['nomor_jp'];
                            $jp_selesai = (int)$block[count($block) - 1]['nomor_jp'];
                            $jumlah_jp = $jp_per_pertemuan;

                            $stmtInsert->bind_param(
                                "iiissiii",
                                $id_guru_insert,
                                $id_kelas,
                                $id_mapel,
                                $hari_insert,
                                $jam_insert,
                                $jp_mulai,
                                $jp_selesai,
                                $jumlah_jp
                            );

                            if (!$stmtInsert->execute()) {
                                throw new Exception('Gagal insert jadwal: ' . $stmtInsert->error);
                            }

                            tandaiKelasTerpakai($kelas_terpakai, $id_kelas, $block);
                            tandaiGuruTerpakai($guru_terpakai, $id_guru_insert, $block);

                            $mapel_hari_terpakai[$key_mapel_hari] = true;
                            $beban_guru[$id_guru_insert] = ($beban_guru[$id_guru_insert] ?? 0) + $jumlah_jp;

                            $jumlah_berhasil++;
                            $berhasil_dipasang = true;

                            break 3;
                        }
                    }
                }

                if (!$berhasil_dipasang) {
                    $gagal[] = [
                        'kelas' => $nama_kelas,
                        'mapel' => $nama_mapel,
                        'pertemuan_ke' => $pertemuan,
                        'butuh_jp' => $jp_per_pertemuan
                    ];
                }
            }
        }
    }

    $stmtInsert->close();

    $conn->commit();

    $message = 'Generate jadwal berhasil. ' . $jumlah_berhasil . ' jadwal dibuat dengan sistem JP.';

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
    if (isset($conn)) {
        $conn->rollback();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

if (isset($conn)) {
    $conn->close();
}
?>