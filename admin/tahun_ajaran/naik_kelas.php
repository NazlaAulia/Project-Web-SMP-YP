<?php
include '../koneksi.php';

// Ambil tahun ajaran aktif (untuk dropdown awal)
$query_ta = "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC";
$result_ta = mysqli_query($conn, $query_ta);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naik Kelas - SMP YP 17 Surabaya</title>

    <link rel="icon" type="image/x-icon" href="../datasiswa/images.webp">

    <link rel="stylesheet" href="../components/admin-nav.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="naik_kelas.css">
    <style>
        /* Style tambahan untuk tombol Buat Kelas Baru dan modal */
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .btn-tambah-kelas {
            background: #064e4b;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-tambah-kelas:hover {
            background: #0f7a76;
        }
        /* Modal tambah kelas */
        .modal-kelas {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .modal-kelas.active {
            display: flex;
        }
        .modal-kelas .modal-box {
            background: white;
            width: 500px;
            max-width: 90%;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 20px 35px rgba(0,0,0,0.2);
        }
        .modal-kelas h3 {
            margin-bottom: 20px;
            color: #064e4b;
        }
        .modal-kelas .form-group {
            margin-bottom: 16px;
        }
        .modal-kelas label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 13px;
        }
        .modal-kelas input, .modal-kelas select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 12px;
            font-size: 14px;
        }
        .modal-kelas .btn-group {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }
        .modal-kelas .btn-batal {
            background: #e0e0e0;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            cursor: pointer;
        }
        .modal-kelas .btn-simpan {
            background: #064e4b;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            cursor: pointer;
        }
    </style>
</head>
<body data-page="tahun_ajaran" data-nav-path="../components/admin-nav.html">

    <div class="container">
        <div id="admin-nav-root"></div>

        <main class="main-content">
            <div class="page-wrap">

                <header class="page-header">
                    <div>
                        <h1>Naik Kelas</h1>
                        <p>Proses tahun ajaran baru, set wali kelas, luluskan kelas 9, dan tempatkan siswa baru.</p>
                    </div>
                </header>

                <section class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div>
                            <h3 id="jumlahSiswaBaru">0</h3>
                            <p>Siswa baru menunggu kelas</p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h3 id="jumlahSiswaAktif">0</h3>
                            <p>Siswa aktif</p>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="summary-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div>
                            <h3 id="jumlahKelas9">0</h3>
                            <p>Kelas 9 yang akan lulus</p>
                        </div>
                    </div>
                </section>

                <section class="table-card">
                    <form id="naikKelasForm">
                        <div class="form-group full">
                            <label for="id_tahun_ajaran">Tahun Ajaran Baru</label>
                            <div class="tahun-ajaran-row">
                                <select id="id_tahun_ajaran" name="id_tahun_ajaran" required>
                                    <option value="">Memuat tahun ajaran...</option>
                                    <?php while($ta = mysqli_fetch_assoc($result_ta)): ?>
                                        <option value="<?= $ta['id_tahun_ajaran'] ?>"><?= htmlspecialchars($ta['tahun_ajaran']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="button" class="btn-create-year" id="buatTahunBtn">
                                    <i class="fas fa-plus"></i> Buat Tahun Berikutnya
                                </button>
                            </div>
                        </div>

                        <div class="section-heading flex-between">
                            <div>
                                <h2>Set Wali Kelas Baru</h2>
                                <p>Pilih wali kelas untuk setiap kelas sebelum proses naik kelas dijalankan.</p>
                            </div>
                            <button type="button" class="btn-tambah-kelas" id="btnTambahKelas">
                                <i class="fas fa-plus-circle"></i> Buat Kelas Baru
                            </button>
                        </div>

                        <div class="form-grid" id="waliKelasGrid">
                            <div class="empty-cell">Memuat data kelas dan guru...</div>
                        </div>

                        <div class="info-box">
                            <i class="fas fa-circle-info"></i>
                            <div>
                                <strong>Pastikan data sudah di-backup.</strong>
                                <span>
                                    Setelah diproses, hanya siswa aktif yang memenuhi syarat yang akan naik kelas.
                                    Siswa yang tidak memenuhi syarat tetap berada di kelas lama.
                                    Siswa kelas 9 yang memenuhi syarat akan berstatus lulus,
                                    sedangkan siswa baru akan masuk kelas 7A/7B/7C secara urut merata.
                                </span>
                            </div>
                        </div>

                        <div class="preview-section">
                            <div class="section-heading">
                                <h2>Preview Kenaikan Kelas</h2>
                                <p>Daftar ini menampilkan hasil perhitungan naik kelas berdasarkan nilai dan kehadiran.</p>
                            </div>

                            <div class="preview-summary" id="previewSummary">
                                Memuat preview kenaikan kelas...
                            </div>

                            <div class="preview-toolbar">
                                <div class="preview-search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="searchPreview" placeholder="Cari nama siswa, kelas, atau status...">
                                </div>
                            </div>

                            <div class="preview-table-wrap">
                                <table class="preview-table">
                                    <thead>
                                        <tr>
                                            <th>Nama</th>
                                            <th>Kelas Lama</th>
                                            <th>Kelas Baru</th>
                                            <th>Rata-rata</th>
                                            <th>Mapel &lt; KKM</th>
                                            <th>Izin</th>
                                            <th>Sakit</th>
                                            <th>Alfa</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewNaikBody">
                                        <tr>
                                            <td colspan="9" class="empty-cell">Memuat data preview...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="preview-pagination">
                                <div class="preview-pagination-info" id="previewPaginationInfo">
                                    Menampilkan 0 sampai 0 dari 0 siswa
                                </div>
                                <div class="preview-pagination-btns" id="previewPaginationBtns"></div>
                            </div>
                        </div>

                        <div class="action-row">
                            <a href="export_excel.php" class="btn-secondary">
                                <i class="fas fa-file-excel"></i> Unduh Excel
                            </a>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-arrow-up"></i> Proses Naik Kelas
                            </button>
                        </div>

                        <div id="formMessage" class="form-message"></div>
                    </form>
                </section>
            </div>
        </main>
    </div>

    <!-- Modal konfirmasi naik kelas -->
    <div class="custom-popup-overlay" id="confirmPopup">
        <div class="custom-popup-box">
            <div class="custom-popup-header">
                <h3>Konfirmasi Naik Kelas</h3>
                <button type="button" class="custom-popup-close" id="closeConfirmPopup">&times;</button>
            </div>
            <div class="custom-popup-body">
                <div class="confirm-input-group">
                    <label for="confirmText">Ketik NAIKKELAS untuk melanjutkan</label>
                    <input type="text" id="confirmText" placeholder="NAIKKELAS">
                </div>
                <div id="confirmMessage" class="confirm-message"></div>
            </div>
            <div class="custom-popup-footer">
                <button type="button" class="custom-btn-secondary" id="cancelConfirmBtn">Batal</button>
                <button type="button" class="custom-btn-primary" id="confirmProcessBtn">Ya, Proses</button>
            </div>
        </div>
    </div>

    <!-- Modal buat tahun ajaran berikutnya -->
    <div class="custom-popup-overlay" id="tahunAjaranPopup">
        <div class="custom-popup-box">
            <div class="custom-popup-header">
                <h3>Buat Tahun Ajaran Berikutnya?</h3>
                <button type="button" class="custom-popup-close" id="closeTahunAjaranPopup">&times;</button>
            </div>
            <div class="custom-popup-body">
                <p>Sistem akan membuat tahun ajaran berikutnya otomatis dari data terakhir di database.</p>
                <div id="tahunAjaranMessage" class="confirm-message"></div>
            </div>
            <div class="custom-popup-footer">
                <button type="button" class="custom-btn-secondary" id="cancelTahunAjaranBtn">Batal</button>
                <button type="button" class="custom-btn-primary" id="confirmTahunAjaranBtn">Ya, Buat</button>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH KELAS BARU -->
    <div id="modalTambahKelas" class="modal-kelas">
        <div class="modal-box">
            <h3><i class="fas fa-plus-circle"></i> Tambah Kelas Baru</h3>
            <form id="formTambahKelas">
                <div class="form-group">
                    <label>Nama Kelas (contoh: 7D, 8E, 9F)</label>
                    <input type="text" name="nama_kelas" id="nama_kelas" required placeholder="7D">
                </div>
                <div class="form-group">
                    <label>Tingkat</label>
                    <select name="tingkat" id="tingkat" required>
                        <option value="">-- Pilih --</option>
                        <option value="7">7 (Kelas 7)</option>
                        <option value="8">8 (Kelas 8)</option>
                        <option value="9">9 (Kelas 9)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Wali Kelas</label>
                    <select name="id_wali_kelas" id="id_wali_kelas" required>
                        <option value="">-- Pilih Guru --</option>
                        <?php
                        $guru = mysqli_query($conn, "SELECT id_guru, nama FROM guru ORDER BY nama");
                        while($g = mysqli_fetch_assoc($guru)) {
                            echo "<option value='{$g['id_guru']}'>{$g['nama']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kapasitas Siswa</label>
                    <input type="number" name="kapasitas" id="kapasitas" value="30" min="1" max="60" required>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn-batal" id="btnBatalModal">Batal</button>
                    <button type="submit" class="btn-simpan">Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../components/admin-nav.js"></script>
    <script src="naik_kelas.js"></script>
    <script>
        // Script tambahan untuk modal kelas
        const modalTambah = document.getElementById('modalTambahKelas');
        const btnTambah = document.getElementById('btnTambahKelas');
        const btnBatalModal = document.getElementById('btnBatalModal');
        const formTambah = document.getElementById('formTambahKelas');

        if (btnTambah) {
            btnTambah.addEventListener('click', function() {
                modalTambah.classList.add('active');
            });
        }
        if (btnBatalModal) {
            btnBatalModal.addEventListener('click', function() {
                modalTambah.classList.remove('active');
            });
        }
        // Tutup modal klik overlay
        modalTambah.addEventListener('click', function(e) {
            if (e.target === modalTambah) modalTambah.classList.remove('active');
        });
        // Submit form tambah kelas via AJAX
        if (formTambah) {
            formTambah.addEventListener('submit', function(e) {
                e.preventDefault();
                const idTahun = document.getElementById('id_tahun_ajaran').value;
                if (!idTahun) {
                    alert('Pilih tahun ajaran terlebih dahulu.');
                    return;
                }
                const formData = new FormData(formTambah);
                formData.append('id_tahun_ajaran', idTahun);
                
                fetch('ajax_tambah_kelas.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Kelas berhasil ditambahkan!');
                        modalTambah.classList.remove('active');
                        // Reload data wali kelas grid di halaman (panggil fungsi dari naik_kelas.js jika ada)
                        if (typeof loadWaliKelasGrid === 'function') loadWaliKelasGrid();
                        else location.reload();
                    } else {
                        alert('Gagal: ' + data.message);
                    }
                })
                .catch(err => {
                    alert('Terjadi kesalahan: ' + err);
                });
            });
        }
    </script>
</body>
</html>