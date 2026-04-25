<?php
header("Content-Type: application/json; charset=utf-8");

require_once "koneksi.php";

function kirim_json($status, $message, $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));
    exit;
}

$id_guru = isset($_POST["id_guru"]) ? (int) $_POST["id_guru"] : 0;
$role_id = isset($_POST["role_id"]) ? (int) $_POST["role_id"] : 0;
$data_nilai_json = $_POST["data_nilai"] ?? "";

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid.");
}

if ($data_nilai_json === "") {
    kirim_json("error", "Data nilai kosong.");
}

$data_nilai = json_decode($data_nilai_json, true);

if (!is_array($data_nilai)) {
    kirim_json("error", "Format data nilai tidak valid.");
}

$inserted = 0;
$updated = 0;
$skipped = 0;
$errorRows = [];

$conn->begin_transaction();

try {
    foreach ($data_nilai as $index => $row) {
        $baris = $index + 2;

        $id_siswa = isset($row["id_siswa"]) ? (int) $row["id_siswa"] : 0;
        $id_mapel = isset($row["id_mapel"]) ? (int) $row["id_mapel"] : 0;
        $semester = isset($row["semester"]) ? (int) $row["semester"] : 0;
        $nilai_angka = isset($row["nilai_angka"]) ? (int) $row["nilai_angka"] : -1;
        $hadir = isset($row["hadir"]) ? (int) $row["hadir"] : -1;
        $izin = isset($row["izin"]) ? (int) $row["izin"] : -1;
        $sakit = isset($row["sakit"]) ? (int) $row["sakit"] : -1;
        $alfa = isset($row["alfa"]) ? (int) $row["alfa"] : -1;

        if (
            $id_siswa <= 0 ||
            $id_mapel <= 0 ||
            $semester <= 0 ||
            $nilai_angka < 0 ||
            $hadir < 0 ||
            $izin < 0 ||
            $sakit < 0 ||
            $alfa < 0
        ) {
            $skipped++;
            $errorRows[] = "Baris {$baris}: data tidak lengkap / tidak valid.";
            continue;
        }

        if ($nilai_angka > 100) {
            $skipped++;
            $errorRows[] = "Baris {$baris}: nilai tidak boleh lebih dari 100.";
            continue;
        }

        $cekSiswa = $conn->prepare("SELECT id_siswa FROM siswa WHERE id_siswa = ? LIMIT 1");
        if (!$cekSiswa) {
            throw new Exception("Query cek siswa gagal: " . $conn->error);
        }

        $cekSiswa->bind_param("i", $id_siswa);
        $cekSiswa->execute();
        $resultSiswa = $cekSiswa->get_result();

        if ($resultSiswa->num_rows === 0) {
            $skipped++;
            $errorRows[] = "Baris {$baris}: ID siswa {$id_siswa} tidak ditemukan.";
            continue;
        }

        $cekMapel = $conn->prepare("SELECT id_mapel FROM mapel WHERE id_mapel = ? LIMIT 1");
        if (!$cekMapel) {
            throw new Exception("Query cek mapel gagal: " . $conn->error);
        }

        $cekMapel->bind_param("i", $id_mapel);
        $cekMapel->execute();
        $resultMapel = $cekMapel->get_result();

        if ($resultMapel->num_rows === 0) {
            $skipped++;
            $errorRows[] = "Baris {$baris}: ID mapel {$id_mapel} tidak ditemukan.";
            continue;
        }

        $cekNilai = $conn->prepare("
            SELECT id_nilai
            FROM nilai
            WHERE id_siswa = ? AND id_mapel = ? AND semester = ?
            LIMIT 1
        ");

        if (!$cekNilai) {
            throw new Exception("Query cek nilai gagal: " . $conn->error);
        }

        $cekNilai->bind_param("iii", $id_siswa, $id_mapel, $semester);
        $cekNilai->execute();
        $resultNilai = $cekNilai->get_result();

        if ($resultNilai->num_rows > 0) {
            $nilaiLama = $resultNilai->fetch_assoc();
            $id_nilai = (int) $nilaiLama["id_nilai"];

            $update = $conn->prepare("
                UPDATE nilai
                SET nilai_angka = ?,
                    hadir = ?,
                    izin = ?,
                    sakit = ?,
                    alfa = ?
                WHERE id_nilai = ?
            ");

            if (!$update) {
                throw new Exception("Query update nilai gagal: " . $conn->error);
            }

            $update->bind_param(
                "iiiiii",
                $nilai_angka,
                $hadir,
                $izin,
                $sakit,
                $alfa,
                $id_nilai
            );

            if (!$update->execute()) {
                throw new Exception("Gagal update nilai baris {$baris}.");
            }

            $updated++;
        } else {
            $insert = $conn->prepare("
                INSERT INTO nilai
                    (id_siswa, id_mapel, semester, nilai_angka, hadir, izin, sakit, alfa)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$insert) {
                throw new Exception("Query insert nilai gagal: " . $conn->error);
            }

            $insert->bind_param(
                "iiiiiiii",
                $id_siswa,
                $id_mapel,
                $semester,
                $nilai_angka,
                $hadir,
                $izin,
                $sakit,
                $alfa
            );

            if (!$insert->execute()) {
                throw new Exception("Gagal insert nilai baris {$baris}.");
            }

            $inserted++;
        }
    }

    $conn->commit();

    kirim_json("success", "Import nilai berhasil.", [
        "inserted" => $inserted,
        "updated" => $updated,
        "skipped" => $skipped,
        "errors" => $errorRows
    ]);
} catch (Exception $e) {
    $conn->rollback();
    kirim_json("error", $e->getMessage());
}
?>