// 1. KONFIGURASI FIREBASE (Ganti dengan milikmu!)
const firebaseConfig = {
    apiKey: "AIzaSy...",
    authDomain: "proyek-kamu.firebaseapp.com",
    projectId: "proyek-kamu",
    storageBucket: "proyek-kamu.appspot.com",
    messagingSenderId: "123456789",
    appId: "1:123:web:abc"
};

// 2. INISIALISASI
firebase.initializeApp(firebaseConfig);
const db = firebase.firestore();

// 3. FUNGSI AMBIL DATA REAL-TIME
function renderRanking() {
    const tbody = document.querySelector("#table-peringkat tbody");

    // Menggunakan onSnapshot agar jika nilai di Firebase berubah, web otomatis update tanpa refresh
    db.collection("siswa").orderBy("rataRata", "desc")
    .onSnapshot((querySnapshot) => {
        tbody.innerHTML = ""; // Bersihkan tabel
        let rank = 1;

        querySnapshot.forEach((doc) => {
            const siswa = doc.data();
            const row = document.createElement("tr");

            // LOGIKA HIGHLIGHT: Jika nama sama dengan user yang login
            if (siswa.nama === "Adinda Eka Athiyyah Zahra") {
                row.classList.add("user-row-highlight");
            }

            // Tentukan icon status
            let statusIcon = "◀▶";
            let statusClass = "status-stable";
            if(siswa.status === "naik") { statusIcon = "▲"; statusClass = "status-up"; }
            else if(siswa.status === "turun") { statusIcon = "▼"; statusClass = "status-down"; }

            row.innerHTML = `
                <td>${rank}</td>
                <td>${siswa.nama}</td>
                <td>${siswa.kelas}</td>
                <td>${siswa.rataRata}</td>
                <td class="${statusClass}">${statusIcon}</td>
            `;

            tbody.appendChild(row);
            rank++;
        });
    });
}

// Jalankan fungsi saat web dibuka
document.addEventListener("DOMContentLoaded", () => {
    renderRanking();
    
    // Animasi muncul
    const content = document.getElementById('content-area');
    content.style.opacity = "1";
    content.style.transform = "translateY(0)";
});