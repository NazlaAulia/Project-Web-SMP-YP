<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>AI Analisis Belajar - SMP YP 17 Surabaya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #f0f7f5; margin: 0; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .ai-card {
            background: white;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-top: 30px;
        }
        .ai-card h2 {
            color: #064e4b;
            margin-bottom: 10px;
        }
        .mapel-badge {
            display: inline-block;
            background: #e8f3f2;
            padding: 6px 14px;
            border-radius: 40px;
            margin: 5px;
            font-size: 13px;
        }
        .lowest {
            background: #fee2e2;
            color: #991b1b;
            font-weight: bold;
        }
        .loading {
            text-align: center;
            padding: 50px;
            color: #064e4b;
        }
        .response-section {
            background: #f9fafb;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
        }
        .response-section h3 {
            color: #064e4b;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .response-section p {
            margin: 0;
            line-height: 1.6;
            color: #333;
        }
        .saran-box { border-left: 4px solid #064e4b; }
        .tips-box { border-left: 4px solid #f59e0b; }
        .motivasi-box { border-left: 4px solid #22c55e; }
        .refresh-btn {
            background: #064e4b;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 40px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
        }
        .refresh-btn:hover {
            background: #0f7a76;
            transform: translateY(-2px);
        }
        .error-box {
            background: #fee2e2;
            padding: 20px;
            border-radius: 16px;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div id="navbar-container"></div>
    <div class="container">
        <div class="ai-card">
            <h2><i class="fas fa-robot"></i> Analisis Belajar AI</h2>
            <p>AI akan menganalisis nilai belajarmu dan memberikan saran untuk meningkatkan prestasi.</p>
            
            <div id="nilaiRingkasan" style="margin: 20px 0;"></div>
            
            <div id="aiResponse">
                <div class="loading"><i class="fas fa-spinner fa-spin"></i> Sedang menganalisis data nilai...</div>
            </div>
            
            <button class="refresh-btn" id="refreshBtn"><i class="fas fa-sync-alt"></i> Analisis Ulang</button>
        </div>
    </div>

    <script src="navbar.js"></script>
    <script>
        function loadAnalisis() {
            fetch('get_analisis_ai.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Tampilkan ringkasan nilai
                        let html = '<strong>📊 Rata-rata Nilai per Mapel:</strong><br>';
                        data.data.semua_nilai.forEach(mapel => {
                            let cls = mapel.rata_rata < 75 ? 'lowest' : '';
                            html += `<span class="mapel-badge ${cls}">${mapel.nama_mapel}: ${mapel.rata_rata}</span>`;
                        });
                        html += `<p style="margin-top: 15px;"><strong>⚠️ Mapel terendah:</strong> ${data.data.mapel_terendah} (${data.data.nilai_terendah})</p>`;
                        document.getElementById('nilaiRingkasan').innerHTML = html;
                        
                        // Tampilkan respons AI
                        document.getElementById('aiResponse').innerHTML = `
                            <div class="response-section saran-box">
                                <h3><i class="fas fa-book-open"></i> 📚 Saran Belajar</h3>
                                <p>${data.data.saran_belajar}</p>
                            </div>
                            <div class="response-section tips-box">
                                <h3><i class="fas fa-lightbulb"></i> 💡 Tips Meningkatkan Nilai</h3>
                                <p>${data.data.tips}</p>
                            </div>
                            <div class="response-section motivasi-box">
                                <h3><i class="fas fa-heart"></i> 🔥 Motivasi</h3>
                                <p>${data.data.motivasi}</p>
                            </div>
                        `;
                    } else {
                        document.getElementById('aiResponse').innerHTML = `<div class="error-box">❌ ${data.message}</div>`;
                    }
                })
                .catch(err => {
                    document.getElementById('aiResponse').innerHTML = `<div class="error-box">❌ Terjadi kesalahan: ${err.message}</div>`;
                });
        }
        
        document.getElementById('refreshBtn').addEventListener('click', () => {
            document.getElementById('aiResponse').innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Memproses ulang...</div>';
            loadAnalisis();
        });
        
        loadAnalisis();
    </script>
</body>
</html>