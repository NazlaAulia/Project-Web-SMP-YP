<?php
require_once __DIR__ . '/../koneksi.php';

$result = $conn->query("
    SELECT *
    FROM pengumuman
    ORDER BY tanggal DESC, id_pengumuman DESC
");

if (!$result) {
    die("Query gagal: " . $conn->error);
}

$editData = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = $conn->prepare("SELECT * FROM pengumuman WHERE id_pengumuman = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $editResult = $stmt->get_result();
    $editData = $editResult->fetch_assoc();

    $stmt->close();
}

$statusMessage = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman - SMP YP 17 Surabaya</title>

    <link rel="icon" type="image/x-icon" href="/admin/datasiswa/images.webp">

    <link rel="stylesheet" href="/admin/components/admin-nav.css?v=99999">
    <link rel="stylesheet" href="/admin/pengumuman/pengumuman.css?v=99999">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body data-page="informasi" data-subpage="pengumuman" data-nav-path="/admin/components/admin-nav.html">

<div class="container">
    <div id="admin-nav-root"></div>

    <main class="main-content">
        <div class="page-wrap">

            <header class="page-header">
                <div>
                    <h1>Pengumuman</h1>
                    <p>Kelola informasi dan pengumuman yang tampil di halaman website sekolah.</p>
                </div>
            </header>

            <?php if ($statusMessage === 'sukses') : ?>
                <div class="alert success">Data pengumuman berhasil disimpan.</div>
            <?php elseif ($statusMessage === 'hapus') : ?>
                <div class="alert success">Data pengumuman berhasil dihapus.</div>
            <?php elseif ($statusMessage === 'gagal') : ?>
                <div class="alert error">Terjadi kesalahan. Silakan coba lagi.</div>
            <?php endif; ?>

            <section class="form-card">
                <h2><?= $editData ? 'Edit Pengumuman' : 'Tambah Pengumuman'; ?></h2>

                <form action="simpan_pengumuman.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_pengumuman" value="<?= $editData ? (int) $editData['id_pengumuman'] : ''; ?>">
                    <input type="hidden" name="gambar_lama" value="<?= $editData ? htmlspecialchars($editData['gambar'] ?? '') : ''; ?>">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Judul</label>
                            <input 
                                type="text" 
                                name="judul" 
                                required
                                value="<?= $editData ? htmlspecialchars($editData['judul']) : ''; ?>"
                                placeholder="Contoh: Jadwal Ujian Tengah Semester"
                            >
                        </div>

                        <div class="form-group">
                            <label>Tanggal</label>
                            <input 
                                type="date" 
                                name="tanggal" 
                                required
                                value="<?= $editData ? htmlspecialchars($editData['tanggal']) : date('Y-m-d'); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori" required>
                                <?php
                                $kategoriList = ['Akademik', 'Kegiatan', 'Info PPDB', 'Informasi'];
                                $kategoriAktif = $editData['kategori'] ?? 'Informasi';

                                foreach ($kategoriList as $kategori) :
                                ?>
                                    <option value="<?= $kategori; ?>" <?= $kategoriAktif === $kategori ? 'selected' : ''; ?>>
                                        <?= $kategori; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" required>
                                <?php $statusAktif = $editData['status'] ?? 'tampil'; ?>
                                <option value="tampil" <?= $statusAktif === 'tampil' ? 'selected' : ''; ?>>Tampil</option>
                                <option value="sembunyi" <?= $statusAktif === 'sembunyi' ? 'selected' : ''; ?>>Sembunyi</option>
                            </select>
                        </div>

                        <div class="form-group full">
                            <label>Isi Pengumuman</label>
                            <textarea name="isi" rows="5" required placeholder="Tulis isi pengumuman..."><?= $editData ? htmlspecialchars($editData['isi']) : ''; ?></textarea>
                        </div>

                        <div class="form-group full">
                            <label>Gambar</label>
                            <input type="file" name="gambar" accept="image/*">

                            <?php if ($editData && !empty($editData['gambar'])) : ?>
                                <div class="current-image">
                                    <span>Gambar saat ini:</span>
                                    <img src="<?= htmlspecialchars($editData['gambar']); ?>" alt="Gambar pengumuman">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <?php if ($editData) : ?>
                            <a href="utama.php" class="btn-secondary">Batal Edit</a>
                        <?php endif; ?>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Simpan Pengumuman
                        </button>
                    </div>
                </form>
            </section>

            <section class="table-card">
                <div class="table-header">
                    <h2>Data Pengumuman</h2>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($result->num_rows > 0) : ?>
                                <?php while ($row = $result->fetch_assoc()) : ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($row['gambar'])) : ?>
                                                <img class="thumb" src="<?= htmlspecialchars($row['gambar']); ?>" alt="Gambar">
                                            <?php else : ?>
                                                <span class="no-image">Tidak ada</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <strong><?= htmlspecialchars($row['judul']); ?></strong>
                                            <p><?= htmlspecialchars(mb_strimwidth(strip_tags($row['isi']), 0, 90, '...')); ?></p>
                                        </td>

                                        <td><?= date('d M Y', strtotime($row['tanggal'])); ?></td>

                                        <td>
                                            <span class="badge kategori">
                                                <?= htmlspecialchars($row['kategori'] ?? 'Informasi'); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if (($row['status'] ?? 'tampil') === 'tampil') : ?>
                                                <span class="badge tampil">Tampil</span>
                                            <?php else : ?>
                                                <span class="badge sembunyi">Sembunyi</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="action-buttons">
                                                <a href="utama.php?edit=<?= (int) $row['id_pengumuman']; ?>" class="btn-edit">
                                                    Edit
                                                </a>

                                              <button
    type="button"
    class="btn-danger btn-open-delete"
    data-url="hapus_pengumuman.php?id=<?= (int) $row['id_pengumuman']; ?>"
>
    Hapus
</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="empty-cell">Belum ada pengumuman.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>
</div>

<div class="delete-modal-overlay" id="deleteModal">
    <div class="delete-modal-box">
        <div class="delete-modal-icon">
            <i class="fas fa-trash"></i>
        </div>

        <h3>Hapus Pengumuman?</h3>
        <p>Data pengumuman ini akan dihapus permanen dari sistem.</p>

        <div class="delete-modal-actions">
            <button type="button" class="btn-cancel-delete" id="cancelDeleteBtn">
                Batal
            </button>

            <a href="#" class="btn-confirm-delete" id="confirmDeleteBtn">
                Ya, Hapus
            </a>
        </div>
    </div>
</div>

<script src="/admin/components/admin-nav.js?v=99999"></script>


<script>
const deleteModal = document.getElementById("deleteModal");
const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");
const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
const openDeleteButtons = document.querySelectorAll(".btn-open-delete");

openDeleteButtons.forEach((button) => {
    button.addEventListener("click", () => {
        const deleteUrl = button.getAttribute("data-url");

        confirmDeleteBtn.setAttribute("href", deleteUrl);
        deleteModal.classList.add("active");
    });
});

cancelDeleteBtn.addEventListener("click", () => {
    deleteModal.classList.remove("active");
    confirmDeleteBtn.setAttribute("href", "#");
});

deleteModal.addEventListener("click", (event) => {
    if (event.target === deleteModal) {
        deleteModal.classList.remove("active");
        confirmDeleteBtn.setAttribute("href", "#");
    }
});
</script>
</body>
</html>
