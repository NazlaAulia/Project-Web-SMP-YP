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

function buatKey($id, $hari, $nomor_jp)
{
    return $id . '|' . $hari . '|' . $nomor_jp;
}

function bobotKesulitan($tingkat)
{
    if ($tingkat === 'sulit') return 1;
    if ($tingkat === 'sedang') return 2;
    return 3;
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
    if ($id_guru === null) {
        return false;
    }

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
    if ($id_guru === null) {
        return;
    }

    foreach ($block as $jp) {
        $key = buatKey($id_guru, $jp['hari'], $jp['nomor_jp']);
        $guru_terpakai[$key] = true;
    }
}

function cariEarliestFreeJP($jp_per_hari, $kelas_terpakai, $id_kelas, $hari)
{
    if (!isset($jp_per_hari[$hari])) {
        return null;
    }

    foreach ($jp_per_hari[$hari] as $jp) {
        $key = buatKey($id_kelas, $hari, $jp['nomor_jp']);

        if (!isset($kelas_terpakai[$key])) {
            return (int)$jp['nomor_jp'];
        }
    }

    return null;
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

            if ((int)$jp_berikutnya['nomor_jp'] !== (int)$jp_sekarang['nomor_jp'] + 1) {
                $valid = false;
                break;
            }

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

function hitungJPClassHari($kelas_terpakai, $id_kelas, $hari)
{
    $total = 0;
    $prefix = $id_kelas . '|' . $hari . '|';

    foreach ($kelas_terpakai as $key => $value) {
        if (strpos($key, $prefix) === 0) {
            $total++;
        }
    }

    return $total;
}

function hitungSisaJPTasks($tasks)
{
    $total = 0;

    foreach ($tasks as $task) {
        if (!$task['terpasang']) {
            $total += (int)$task['jp_per_pertemuan'];
        }
    }

    return $total;
}

function adaTaskUkuran($tasks, $ukuran)
{
    foreach ($tasks as $task) {
        if (!$task['terpasang'] && (int)$task['jp_per_pertemuan'] === (int)$ukuran) {
            return true;
        }
    }

    return false;
}

function mapelSudahDiHari($mapel_hari_terpakai, $id_kelas, $id_mapel, $hari)
{
    $key = $id_kelas . '|' . $id_mapel . '|' . $hari;
    return isset($mapel_hari_terpakai[$key]);
}

function pilihGuruTersedia($guru_tersedia, $guru_terpakai, $beban_guru, $block)
{
    if (empty($guru_tersedia)) {
        return null;
    }

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

        if (!blockBentrokGuru($guru_terpakai, $id_guru, $block)) {
            return $id_guru;
        }
    }

    return false;
}

function cariKandidatTerbaik($tasks, $jp_per_hari, $kelas_terpakai, $guru_terpakai, $mapel_hari_terpakai, $guru_by_mapel, $beban_guru, $id_kelas, $hari, $target_harian)
{
    $earliestFree = cariEarliestFreeJP($jp_per_hari, $kelas_terpakai, $id_kelas, $hari);

    if ($earliestFree === null) {
        return null;
    }

    $jp_terisi_hari_ini = hitungJPClassHari($kelas_terpakai, $id_kelas, $hari);
    $sisa_target = $target_harian - $jp_terisi_hari_ini;

    if ($sisa_target <= 0) {
        return null;
    }

    $kandidat = [];

    foreach ($tasks as $taskIndex => $task) {
        if ($task['terpasang']) {
            continue;
        }

        $jumlah_jp = (int)$task['jp_per_pertemuan'];

        if ($jumlah_jp > $sisa_target) {
            continue;
        }

        $blocks = ambilBlockJP($jp_per_hari, $hari, $jumlah_jp);

        foreach ($blocks as $block) {
            $jp_mulai = (int)$block[0]['nomor_jp'];

            if (blockBentrokKelas($kelas_terpakai, $id_kelas, $block)) {
                continue;
            }

            $guru_tersedia = $guru_by_mapel[$task['id_mapel']] ?? [];
            $id_guru = pilihGuruTersedia($guru_tersedia, $guru_terpakai, $beban_guru, $block);

            if ($id_guru === false) {
                continue;
            }

            $duplikat_hari = mapelSudahDiHari(
                $mapel_hari_terpakai,
                $id_kelas,
                $task['id_mapel'],
                $hari
            );

            $score = 0;

            // Paling penting: isi dari JP paling awal yang kosong
            $score += abs($jp_mulai - $earliestFree) * 1000;

            // Jangan terlalu sering mapel sama di hari sama, tapi masih boleh kalau terpaksa
            if ($duplikat_hari) {
                $score += 600;
            }

            // Mapel sulit/prioritas pagi didorong ke pagi
            if ($task['prioritas_pagi'] == 1 || $task['tingkat_kesulitan'] === 'sulit') {
                $score += $jp_mulai * 10;
            } elseif ($task['tingkat_kesulitan'] === 'ringan') {
                $score += abs($jp_mulai - 6) * 8;
            } else {
                $score += $jp_mulai * 5;
            }

            // Kalau sisa target tinggal 1, utamakan task 1 JP
            if ($sisa_target === 1 && $jumlah_jp !== 1) {
                $score += 10000;
            }

            // Kalau setelah dipasang sisa 1 JP tapi tidak ada task 1 JP, hindari
            if (($sisa_target - $jumlah_jp) === 1 && !adaTaskUkuran($tasks, 1)) {
                $score += 2500;
            }

            $score += bobotKesulitan($task['tingkat_kesulitan']) * 20;

            $kandidat[] = [
                'score' => $score,
                'task_index' => $taskIndex,
                'task' => $task,
                'block' => $block,
                'id_guru' => $id_guru
            ];
        }
    }

    if (empty($kandidat)) {
        return null;
    }

    usort($kandidat, function ($a, $b) {
        if ($a['score'] === $b['score']) {
            return $a['task']['id_mapel'] <=> $b['task']['id_mapel'];
        }

        return $a['score'] <=> $b['score'];
    });

    return $kandidat[0];
}

function cariKandidatGanjilUntukHari($tasks, $jp_per_hari, $kelas_terpakai, $guru_terpakai, $mapel_hari_terpakai, $guru_by_mapel, $beban_guru, $id_kelas, $hari)
{
    $earliestFree = cariEarliestFreeJP($jp_per_hari, $kelas_terpakai, $id_kelas, $hari);

    if ($earliestFree === null) {
        return null;
    }

    $kandidat = [];

    foreach ($tasks as $taskIndex => $task) {
        if ($task['terpasang']) {
            continue;
        }

        $jumlah_jp = (int)$task['jp_per_pertemuan'];

        // Ambil mapel dengan durasi ganjil: 1 JP atau 3 JP
        if ($jumlah_jp % 2 === 0) {
            continue;
        }

        $blocks = ambilBlockJP($jp_per_hari, $hari, $jumlah_jp);

        foreach ($blocks as $block) {
            $jp_mulai = (int)$block[0]['nomor_jp'];

            if (blockBentrokKelas($kelas_terpakai, $id_kelas, $block)) {
                continue;
            }

            $guru_tersedia = $guru_by_mapel[$task['id_mapel']] ?? [];
            $id_guru = pilihGuruTersedia($guru_tersedia, $guru_terpakai, $beban_guru, $block);

            if ($id_guru === false) {
                continue;
            }

            $duplikat_hari = mapelSudahDiHari(
                $mapel_hari_terpakai,
                $id_kelas,
                $task['id_mapel'],
                $hari
            );

            $score = 0;

            // Ganjil harus diletakkan sedekat mungkin dengan JP awal kosong
            $score += abs($jp_mulai - $earliestFree) * 1000;

            // PJOK 3 JP lebih bagus dipakai di hari ganjil karena membantu menggenapkan 11 JP
            if ($jumlah_jp === 3) {
                $score -= 100;
            }

            if ($duplikat_hari) {
                $score += 700;
            }

            if ($task['tingkat_kesulitan'] === 'ringan') {
                $score += abs($jp_mulai - 5) * 5;
            } else {
                $score += $jp_mulai * 5;
            }

            $kandidat[] = [
                'score' => $score,
                'task_index' => $taskIndex,
                'task' => $task,
                'block' => $block,
                'id_guru' => $id_guru
            ];
        }
    }

    if (empty($kandidat)) {
        return null;
    }

    usort($kandidat, function ($a, $b) {
        return $a['score'] <=> $b['score'];
    });

    return $kandidat[0];
}

function pasangKandidat(&$stmtInsert, &$tasks, $kandidat, $id_kelas, $hari, &$kelas_terpakai, &$guru_terpakai, &$mapel_hari_terpakai, &$beban_guru, &$jumlah_berhasil)
{
    $taskIndex = $kandidat['task_index'];
    $task = $kandidat['task'];
    $block = $kandidat['block'];
    $id_guru_insert = $kandidat['id_guru'];

    $hari_insert = $hari;
    $jam_insert = formatJam($block[0]['jam_mulai']) . '-' . formatJam($block[count($block) - 1]['jam_selesai']);
    $jp_mulai = (int)$block[0]['nomor_jp'];
    $jp_selesai = (int)$block[count($block) - 1]['nomor_jp'];
    $jumlah_jp = (int)$task['jp_per_pertemuan'];
    $id_mapel_insert = (int)$task['id_mapel'];

    $stmtInsert->bind_param(
        "iiissiii",
        $id_guru_insert,
        $id_kelas,
        $id_mapel_insert,
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

    $key_mapel_hari = $id_kelas . '|' . $id_mapel_insert . '|' . $hari;
    $mapel_hari_terpakai[$key_mapel_hari] = true;

    if ($id_guru_insert !== null) {
        $beban_guru[$id_guru_insert] = ($beban_guru[$id_guru_insert] ?? 0) + $jumlah_jp;
    }

    $tasks[$taskIndex]['terpasang'] = true;
    $tasks[$taskIndex]['hari_terpasang'] = $hari;

    $jumlah_berhasil++;
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

    // Target otomatis dari jam_pelajaran
    // Senin-Kamis idealnya 11 JP, Jumat 8 JP
    $target_harian = [];

    foreach ($jp_per_hari as $hari => $list_jp) {
        $target_harian[$hari] = count($list_jp);
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
        throw new Exception('Gagal mengambil data mapel dan aturan_mapel: ' . $conn->error);
    }

    while ($row = $qMapel->fetch_assoc()) {
        $mapel[] = [
            'id_mapel' => (int)$row['id_mapel'],
            'nama_mapel' => $row['nama_mapel'],
            'pertemuan_per_minggu' => max(1, (int)$row['pertemuan_per_minggu']),
            'jp_per_pertemuan' => max(1, (int)$row['jp_per_pertemuan']),
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
    // PENANDA BENTROK GLOBAL
    // ===============================
    $kelas_terpakai = [];
    $guru_terpakai = [];
    $mapel_hari_terpakai = [];

    $jumlah_berhasil = 0;
    $gagal = [];

    // ===============================
    // PROSES GENERATE FULL HARIAN
    // ===============================
    foreach ($kelas as $index_kelas => $data_kelas) {
        $id_kelas = (int)$data_kelas['id_kelas'];
        $nama_kelas = $data_kelas['nama_kelas'];

        // Buat daftar kebutuhan mapel untuk kelas ini
        $tasks = [];

        foreach ($mapel as $data_mapel) {
            for ($p = 1; $p <= $data_mapel['pertemuan_per_minggu']; $p++) {
                $tasks[] = [
                    'id_mapel' => (int)$data_mapel['id_mapel'],
                    'nama_mapel' => $data_mapel['nama_mapel'],
                    'pertemuan_ke' => $p,
                    'jp_per_pertemuan' => (int)$data_mapel['jp_per_pertemuan'],
                    'tingkat_kesulitan' => $data_mapel['tingkat_kesulitan'],
                    'prioritas_pagi' => (int)$data_mapel['prioritas_pagi'],
                    'terpasang' => false,
                    'hari_terpasang' => null
                ];
            }
        }

        // Urutkan task
        usort($tasks, function ($a, $b) {
            if ($a['prioritas_pagi'] !== $b['prioritas_pagi']) {
                return $b['prioritas_pagi'] <=> $a['prioritas_pagi'];
            }

            $bobotA = bobotKesulitan($a['tingkat_kesulitan']);
            $bobotB = bobotKesulitan($b['tingkat_kesulitan']);

            if ($bobotA !== $bobotB) {
                return $bobotA <=> $bobotB;
            }

            return $a['id_mapel'] <=> $b['id_mapel'];
        });

        // ===============================
        // 1. SEBAR TASK GANJIL DULU
        // Hari dengan target 11 JP butuh minimal 1 task ganjil
        // Supaya Senin-Kamis bisa penuh sampai JP 11
        // ===============================
        foreach ($hari_list as $hari) {
            $target = $target_harian[$hari] ?? 0;

            if ($target <= 0) {
                continue;
            }

            // Hanya hari target ganjil, misalnya 11 JP
            if ($target % 2 === 0) {
                continue;
            }

            $kandidatGanjil = cariKandidatGanjilUntukHari(
                $tasks,
                $jp_per_hari,
                $kelas_terpakai,
                $guru_terpakai,
                $mapel_hari_terpakai,
                $guru_by_mapel,
                $beban_guru,
                $id_kelas,
                $hari
            );

            if ($kandidatGanjil !== null) {
                pasangKandidat(
                    $stmtInsert,
                    $tasks,
                    $kandidatGanjil,
                    $id_kelas,
                    $hari,
                    $kelas_terpakai,
                    $guru_terpakai,
                    $mapel_hari_terpakai,
                    $beban_guru,
                    $jumlah_berhasil
                );
            }
        }

        // ===============================
        // 2. ISI NORMAL PER HARI SAMPAI TARGET
        // ===============================
        foreach ($hari_list as $hari) {
            $target = $target_harian[$hari] ?? 0;

            if ($target <= 0) {
                continue;
            }

            $pengaman_loop = 0;

            while (hitungJPClassHari($kelas_terpakai, $id_kelas, $hari) < $target) {
                $pengaman_loop++;

                if ($pengaman_loop > 300) {
                    break;
                }

                if (hitungSisaJPTasks($tasks) <= 0) {
                    break;
                }

                $kandidat = cariKandidatTerbaik(
                    $tasks,
                    $jp_per_hari,
                    $kelas_terpakai,
                    $guru_terpakai,
                    $mapel_hari_terpakai,
                    $guru_by_mapel,
                    $beban_guru,
                    $id_kelas,
                    $hari,
                    $target
                );

                if ($kandidat === null) {
                    break;
                }

                pasangKandidat(
                    $stmtInsert,
                    $tasks,
                    $kandidat,
                    $id_kelas,
                    $hari,
                    $kelas_terpakai,
                    $guru_terpakai,
                    $mapel_hari_terpakai,
                    $beban_guru,
                    $jumlah_berhasil
                );
            }
        }

        // Catat task yang belum terpasang
        foreach ($tasks as $task) {
            if (!$task['terpasang']) {
                $gagal[] = [
                    'kelas' => $nama_kelas,
                    'mapel' => $task['nama_mapel'],
                    'pertemuan_ke' => $task['pertemuan_ke'],
                    'butuh_jp' => $task['jp_per_pertemuan']
                ];
            }
        }
    }

    $stmtInsert->close();

    $conn->commit();

    $message = 'Generate jadwal berhasil. ' . $jumlah_berhasil . ' jadwal dibuat dengan sistem JP full harian.';

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