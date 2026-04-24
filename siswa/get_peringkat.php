<?php
session_start();
header('Content-Type: application/json');

require_once '../koneksi.php';

function konversiSemesterKeAngka($semesterText) {
    $semesterText = trim((string)$semesterText);

    if ($semesterText === '') return 2;
    if (stripos($semesterText, 'genap') !== false) return 2;
    if (stripos($semesterText, 'ganjil') !== false) return 1;
    if (is_numeric($semesterText)) return (int)$semesterText;

    return 2;
}

$id_siswa = 0;

if (isset($_SESSION['id_siswa']) && (int)$_SESSION['id_siswa'] > 0) {
    $id_siswa = (int) $_SESSION['id_siswa'];
} elseif (isset($_GET['id_siswa']) && (int)$_GET['id_siswa'] > 0) {
    $id_siswa = (int) $_GET['id_siswa'];
}

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak ditemukan. Silakan login ulang."
    ]);
    exit;
}

$kelasFilter = trim($_GET['kelas'] ?? '');
$semesterText = trim($_GET['semester'] ?? '');

if (empty($semesterText)) {
    $semesterText = "2025/2026 - Genap";
}

$semester = konversiSemesterKeAngka($semesterText);
$semesterSebelumnya = $semester == 2 ? 1 : 0;

/* Ambil data siswa login */
$sqlSiswa = "
    SELECT 
        s.id_siswa,
        s.nama,
        s.id_kelas,
        k.nama_kelas
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.id_siswa = ?
    LIMIT 1
";

$stmt = $conn->prepare($sqlSiswa);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare query siswa gagal: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $id_siswa);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Execute query siswa gagal: " . $stmt->error
    ]);
    exit;
}

$result = $stmt->get_result();
$siswaLogin = $result->fetch_assoc();

if (!$siswaLogin) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan"
    ]);
    exit;
}

$kelasAktif = !empty($kelasFilter) ? $kelasFilter : $siswaLogin['nama_kelas'];

/*
  Ambil rata-rata nilai siswa hanya dalam kelas yang sama.
  Ranking dihitung dari AVG(nilai.nilai_angka), bukan dari tabel peringkat.
*/
$sqlRank = "
    SELECT 
        s.id_siswa,
        s.nama,
        k.nama_kelas,
        ROUND(AVG(n.nilai_angka), 2) AS nilai_rata_rata
    FROM siswa s
    INNER JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN nilai n 
        ON n.id_siswa = s.id_siswa 
        AND n.semester = ?
    WHERE k.nama_kelas = ?
    GROUP BY s.id_siswa, s.nama, k.nama_kelas
    HAVING nilai_rata_rata IS NOT NULL
    ORDER BY nilai_rata_rata DESC, s.nama ASC
";

$stmtRank = $conn->prepare($sqlRank);

if (!$stmtRank) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare query ranking gagal: " . $conn->error
    ]);
    exit;
}

$stmtRank->bind_param("is", $semester, $kelasAktif);

if (!$stmtRank->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Execute query ranking gagal: " . $stmtRank->error
    ]);
    exit;
}

$resultRank = $stmtRank->get_result();

$ranking = [];
$rank = 1;
$rankSiswaLogin = 0;
$nilaiSiswaLogin = 0;

while ($r = $resultRank->fetch_assoc()) {
    $id = (int) $r["id_siswa"];
    $nilai = (float) $r["nilai_rata_rata"];

    $item = [
        "id_siswa" => $id,
        "rank" => $rank,
        "nama" => $r["nama"] ?? "",
        "kelas" => $r["nama_kelas"] ?? "",
        "nilai" => $nilai,
        "status" => "-"
    ];

    if ($id === $id_siswa) {
        $rankSiswaLogin = $rank;
        $nilaiSiswaLogin = $nilai;
    }

    $ranking[] = $item;
    $rank++;
}

/*
  Optional: hitung status naik/turun berdasarkan semester sebelumnya.
  Kalau semester sebelumnya tidak ada, status tetap "-".
*/
if ($semesterSebelumnya > 0) {
    $sqlPrev = "
        SELECT 
            s.id_siswa,
            ROUND(AVG(n.nilai_angka), 2) AS nilai_rata_rata
        FROM siswa s
        INNER JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN nilai n 
            ON n.id_siswa = s.id_siswa 
            AND n.semester = ?
        WHERE k.nama_kelas = ?
        GROUP BY s.id_siswa
        HAVING nilai_rata_rata IS NOT NULL
        ORDER BY nilai_rata_rata DESC, s.nama ASC
    ";

    $stmtPrev = $conn->prepare($sqlPrev);

    if ($stmtPrev) {
        $stmtPrev->bind_param("is", $semesterSebelumnya, $kelasAktif);
        $stmtPrev->execute();
        $resultPrev = $stmtPrev->get_result();

        $rankSebelumnya = [];
        $prevRank = 1;

        while ($p = $resultPrev->fetch_assoc()) {
            $rankSebelumnya[(int)$p["id_siswa"]] = $prevRank;
            $prevRank++;
        }

        foreach ($ranking as &$item) {
            $id = (int) $item["id_siswa"];

            if (!isset($rankSebelumnya[$id])) {
                $item["status"] = "-";
            } else {
                $rankLama = $rankSebelumnya[$id];
                $rankBaru = $item["rank"];

                if ($rankBaru < $rankLama) {
                    $item["status"] = "naik";
                } elseif ($rankBaru > $rankLama) {
                    $item["status"] = "turun";
                } else {
                    $item["status"] = "tetap";
                }
            }
        }

        unset($item);
        $stmtPrev->close();
    }
}

echo json_encode([
    "success" => true,
    "siswa" => [
        "id_siswa" => (int) $siswaLogin["id_siswa"],
        "nama" => $siswaLogin["nama"] ?? "",
        "kelas" => $siswaLogin["nama_kelas"] ?? "",
        "rank" => $rankSiswaLogin,
        "nilai" => $nilaiSiswaLogin
    ],
    "ranking" => $ranking
]);

$stmt->close();
$stmtRank->close();
$conn->close();
?>