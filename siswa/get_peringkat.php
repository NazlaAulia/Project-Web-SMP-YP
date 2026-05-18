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

// Endpoint untuk mengambil daftar tahun ajaran
if (isset($_GET['action']) && $_GET['action'] === 'get_tahun_ajaran') {
    $queryTA = mysqli_query($conn, "SELECT id_tahun_ajaran, tahun_ajaran, status FROM tahun_ajaran ORDER BY id_tahun_ajaran DESC");
    $tahunAjaranList = [];
    while ($ta = mysqli_fetch_assoc($queryTA)) {
        $tahunAjaranList[] = $ta;
    }
    echo json_encode([
        "success" => true,
        "tahun_ajaran_list" => $tahunAjaranList
    ]);
    exit;
}

$id_siswa = 0;

if (isset($_GET['id_siswa']) && (int)$_GET['id_siswa'] > 0) {
    $id_siswa = (int) $_GET['id_siswa'];
    $_SESSION['id_siswa'] = $id_siswa;
} elseif (isset($_SESSION['id_siswa']) && (int)$_SESSION['id_siswa'] > 0) {
    $id_siswa = (int) $_SESSION['id_siswa'];
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
$tahunAjaran = isset($_GET['tahun_ajaran']) ? (int)$_GET['tahun_ajaran'] : 0;

if (empty($semesterText)) {
    $semesterText = "Genap";
}

$semester = konversiSemesterKeAngka($semesterText);

// Ambil data siswa login
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
$stmt->bind_param("i", $id_siswa);
$stmt->execute();
$result = $stmt->get_result();
$siswaLogin = $result->fetch_assoc();

if (!$siswaLogin) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan"
    ]);
    exit;
}

$kelasAktif = $siswaLogin['nama_kelas'];

// Ambil ranking berdasarkan semester dan tahun ajaran
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
$stmtRank->bind_param("is", $semester, $kelasAktif);
$stmtRank->execute();
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

// Cari tahun ajaran sebelumnya
$tahunSebelumnya = null;
if ($tahunAjaran > 0) {
    $queryPrevTA = mysqli_query($conn, "
        SELECT id_tahun_ajaran, tahun_ajaran 
        FROM tahun_ajaran 
        WHERE id_tahun_ajaran < $tahunAjaran 
        ORDER BY id_tahun_ajaran DESC LIMIT 1
    ");
    if ($queryPrevTA && mysqli_num_rows($queryPrevTA) > 0) {
        $prevTA = mysqli_fetch_assoc($queryPrevTA);
        $tahunSebelumnya = $prevTA['id_tahun_ajaran'];
    }
}

// Jika ada tahun ajaran sebelumnya, ambil ranking dari semester yang sama
if ($tahunSebelumnya !== null) {
    $sqlPrevRank = "
        SELECT 
            s.id_siswa,
            ROUND(AVG(n.nilai_angka), 2) AS nilai_rata_rata,
            @prev_rank := @prev_rank + 1 AS rank_prev
        FROM siswa s
        INNER JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN nilai n 
            ON n.id_siswa = s.id_siswa 
            AND n.semester = ?
        CROSS JOIN (SELECT @prev_rank := 0) AS vars
        WHERE k.nama_kelas = ?
        GROUP BY s.id_siswa, s.nama
        HAVING nilai_rata_rata IS NOT NULL
        ORDER BY nilai_rata_rata DESC, s.nama ASC
    ";
    
    $stmtPrev = $conn->prepare($sqlPrevRank);
    $stmtPrev->bind_param("is", $semester, $kelasAktif);
    $stmtPrev->execute();
    $resultPrev = $stmtPrev->get_result();
    
    $rankSebelumnya = [];
    while ($p = $resultPrev->fetch_assoc()) {
        $rankSebelumnya[(int)$p["id_siswa"]] = $p["rank_prev"];
    }
    
    foreach ($ranking as &$item) {
        $id = (int) $item["id_siswa"];
        
        if (!isset($rankSebelumnya[$id])) {
            $item["status"] = "baru";
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