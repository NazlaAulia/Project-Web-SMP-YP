<?php
/* =========================
   MODE API (AJAX)
========================= */
if (isset($_GET["ajax"]) && $_GET["ajax"] === "dashboard") {

    header("Content-Type: application/json; charset=utf-8");
    require_once "koneksi.php";

    function kirim_json($status, $message, $extra = []) {
        echo json_encode(array_merge([
            "status" => $status,
            "message" => $message
        ], $extra));
        exit;
    }

    $id_guru = isset($_GET["id_guru"]) ? (int) $_GET["id_guru"] : 0;
    $role_id = isset($_GET["role_id"]) ? (int) $_GET["role_id"] : 0;

    if ($role_id !== 2) {
        kirim_json("error", "Akses ditolak.");
    }

    if ($id_guru <= 0) {
        kirim_json("error", "ID guru tidak valid.");
    }

    /* =========================
       MAPEL (SUDAH SESUAI GURU)
    ========================= */
    $mapel = [];

    $qMapel = $conn->prepare("
        SELECT DISTINCT m.id_mapel, m.nama_mapel
        FROM nilai n
        INNER JOIN mapel m ON n.id_mapel = m.id_mapel
        INNER JOIN guru g ON g.id_guru = ?
        WHERE n.id_mapel = g.id_mapel
    ");

    $qMapel->bind_param("i", $id_guru);
    $qMapel->execute();
    $resultMapel = $qMapel->get_result();

    while ($row = $resultMapel->fetch_assoc()) {
        $mapel[] = $row;
    }

    /* =========================
       KEHADIRAN
    ========================= */
    $qKehadiran = $conn->query("
        SELECT
            COALESCE(SUM(hadir), 0) AS total_hadir,
            COALESCE(SUM(izin), 0) AS total_izin,
            COALESCE(SUM(sakit), 0) AS total_sakit,
            COALESCE(SUM(alfa), 0) AS total_alfa
        FROM nilai
    ");

    $rekap = $qKehadiran->fetch_assoc();

    $totalSemua = $rekap["total_hadir"] + $rekap["total_izin"] + $rekap["total_sakit"] + $rekap["total_alfa"];
    $persenHadir = $totalSemua > 0 ? round(($rekap["total_hadir"] / $totalSemua) * 100) : 0;

    /* =========================
       PERINGKAT
    ========================= */
    $peringkat = [];

    $qPeringkat = $conn->query("
        SELECT s.nama, k.nama_kelas, ROUND(AVG(n.nilai_angka),2) AS rata_rata
        FROM nilai n
        LEFT JOIN siswa s ON n.id_siswa = s.id_siswa
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        GROUP BY s.id_siswa
        ORDER BY rata_rata DESC
        LIMIT 2
    ");

    while ($row = $qPeringkat->fetch_assoc()) {
        $peringkat[] = $row;
    }

    /* =========================
       REQUEST JADWAL
    ========================= */
    $requestJadwal = [];

    $qRequest = $conn->prepare("
        SELECT
            r.status,
            j.hari,
            jp.jam_mulai,
            jp.jam_selesai,
            k.nama_kelas,
            m.nama_mapel
        FROM request_jadwal r
        LEFT JOIN jadwal j ON r.id_jadwal = j.id_jadwal
        LEFT JOIN jam_pelajaran jp ON j.id_jam = jp.id_jam
        LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
        LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
        WHERE r.id_guru = ?
        ORDER BY r.id_request DESC
        LIMIT 5
    ");

    $qRequest->bind_param("i", $id_guru);
    $qRequest->execute();
    $resReq = $qRequest->get_result();

    while ($row = $resReq->fetch_assoc()) {
        $requestJadwal[] = $row;
    }

    kirim_json("success", "OK", [
        "mapel" => $mapel,
        "kehadiran" => [
            "persen_hadir" => $persenHadir
        ],
        "peringkat" => $peringkat,
        "request_jadwal" => $requestJadwal
    ]);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Guru</title>

<link rel="stylesheet" href="guru.css">
<link rel="stylesheet" href="components/sidebar.css">
<link rel="stylesheet" href="components/fix-mobile.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body>
<div class="app-container">

<div id="sidebar-container"></div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<main class="main-content">

<header>
<input type="text" id="dashboardSearchInput" placeholder="Cari mapel">
<button id="mobileMenuBtn" class="mobile-menu-btn">
<i class="bi bi-list"></i>
</button>
</header>

<h2>Dashboard Guru</h2>

<div id="dashboardRequestJadwal"></div>

</main>
</div>

<script>
const idGuru = localStorage.getItem("id_guru");
const roleId = localStorage.getItem("role_id");

fetch(`guru.php?ajax=dashboard&id_guru=${idGuru}&role_id=${roleId}`)
.then(res=>res.json())
.then(res=>{
    renderRequest(res.request_jadwal);
});

function renderRequest(data){
    const el = document.getElementById("dashboardRequestJadwal");

    el.innerHTML = data.map(r=>`
        <div>
            <b>${r.nama_mapel}</b>
            <p>${r.nama_kelas} - ${r.hari}</p>
            <small>${r.status}</small>
        </div>
    `).join("");
}
</script>

</body>
</html>