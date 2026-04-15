<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "osbebslk_sekolahyp";
$dbuser = "osbebslk_aliyahzz";
$dbpass = "semangatgaes";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Koneksi database gagal: " . $conn->connect_error
    ]);
    exit;
}

$id_siswa = isset($_GET['id_siswa']) ? (int) $_GET['id_siswa'] : 0;

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "id_siswa tidak valid"
    ]);
    exit;
}

/*
  Ambil data siswa + kelas + data peringkat
  SESUAIKAN nama tabel peringkat/kolom kalau di database kamu beda
*/
$sql = "SELECT 
            s.id_siswa,
            s.nama,
            s.id_kelas,
            k.nama_kelas,
            p.rank,
            p.nilai_rata_rata,
            p.posisi_sebelumnya,
            p.status
        FROM siswa s
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN peringkat p ON p.id_siswa = s.id_siswa
        WHERE s.id_siswa = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

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
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan"
    ]);
    exit;
}

/*
  Ambil ranking 1 kelas
*/
$id_kelas = (int) $row['id_kelas'];

$sqlRank = "SELECT 
                s.id_siswa,
                s.nama,
                k.nama_kelas,
                p.rank,
                p.nilai_rata_rata,
                p.status
            FROM siswa s
            LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
            INNER JOIN peringkat p ON p.id_siswa = s.id_siswa
            WHERE s.id_kelas = ?
            ORDER BY p.rank ASC";

$stmtRank = $conn->prepare($sqlRank);

if (!$stmtRank) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare query ranking gagal: " . $conn->error
    ]);
    exit;
}

$stmtRank->bind_param("i", $id_kelas);

if (!$stmtRank->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Execute query ranking gagal: " . $stmtRank->error
    ]);
    exit;
}

$resultRank = $stmtRank->get_result();
$ranking = [];

while ($r = $resultRank->fetch_assoc()) {
    $ranking[] = [
        "rank" => (int) ($r["rank"] ?? 0),
        "nama" => $r["nama"] ?? "",
        "kelas" => $r["nama_kelas"] ?? "",
        "nilai" => (float) ($r["nilai_rata_rata"] ?? 0),
        "status" => $r["status"] ?? "↔"
    ];
}

echo json_encode([
    "success" => true,
    "siswa" => [
        "id_siswa" => (int) $row["id_siswa"],
        "nama" => $row["nama"] ?? "",
        "kelas" => $row["nama_kelas"] ?? "",
        "rank" => (int) ($row["rank"] ?? 0),
        "nilai" => (float) ($row["nilai_rata_rata"] ?? 0),
        "posisi_sebelumnya" => (int) ($row["posisi_sebelumnya"] ?? 0),
        "status" => $row["status"] ?? "↔"
    ],
    "ranking" => $ranking
]);

$stmt->close();
$stmtRank->close();
$conn->close();
?>