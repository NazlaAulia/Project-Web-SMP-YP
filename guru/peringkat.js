let dataSiswa = [];

// Pagination state
let currentPage = 1;
const rowsPerPage = 10;

const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");

const totalUnggulEl = document.getElementById("totalUnggul");
const totalBaikEl = document.getElementById("totalBaik");
const totalPerhatianEl = document.getElementById("totalPerhatian");
const filterSemester = document.getElementById("filterSemester");
const filterAngkatan = document.getElementById("filterAngkatan");

// Pagination Elements
const prevPageBtn = document.getElementById("prevPageBtn");
const nextPageBtn = document.getElementById("nextPageBtn");
const paginationNumbers = document.getElementById("paginationNumbers");

function renderSummary(summary) {
    if (!summary) return;

    if (totalUnggulEl) totalUnggulEl.textContent = summary.unggul || 0;
    if (totalBaikEl) totalBaikEl.textContent = summary.baik || 0;
    if (totalPerhatianEl) totalPerhatianEl.textContent = summary.perhatian || 0;
}

function renderPeringkatPaginated(data) {
    const tbody = document.getElementById("rankingBody");
    if (!tbody) return;

    const totalItems = data.length;
    const totalPages = Math.ceil(totalItems / rowsPerPage);
    
    // Validate current page
    if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
    }
    if (currentPage < 1) {
        currentPage = 1;
    }
    
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    const paginatedData = data.slice(startIndex, endIndex);

    if (paginatedData.length === 0 && data.length > 0) {
        currentPage = 1;
        renderPeringkatPaginated(data);
        return;
    }

    if (paginatedData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align:center; color:#667784;">
                    Data peringkat tidak ditemukan.
                </td>
            </tr>
        `;
        updatePaginationControls(totalPages, totalItems);
        return;
    }

    tbody.innerHTML = paginatedData.map(siswa => {
        let colorClass = siswa.nilai >= 90 ? "bg-excellent" : (siswa.nilai >= 75 ? "bg-good" : "bg-warning");
        const globalNo = (currentPage - 1) * rowsPerPage + paginatedData.indexOf(siswa) + 1;

        return `
            <tr>
                <td>${globalNo}</td>
                <td><strong>${escapeHtml(siswa.nama)}</strong></td>
                <td>${siswa.kelas}</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-fill ${colorClass}" style="width: ${siswa.nilai}%">
                            ${siswa.nilai}%
                        </div>
                    </div>
                </td>
                <td>
                    <span class="status-badge ${colorClass}">
                        ${siswa.status}
                    </span>
                </td>
            </tr>
        `;
    }).join("");
    
    updatePaginationControls(totalPages, totalItems);
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function updatePaginationControls(totalPages, totalItems) {
    // Update tombol prev/next
    if (prevPageBtn) {
        prevPageBtn.disabled = currentPage === 1 || totalItems === 0;
    }
    
    if (nextPageBtn) {
        nextPageBtn.disabled = currentPage === totalPages || totalItems === 0 || totalPages === 0;
    }
    
    // Generate nomor halaman dengan kotak
    generatePageNumbers(totalPages);
}

function generatePageNumbers(totalPages) {
    if (!paginationNumbers) return;
    
    console.log("generatePageNumbers - totalPages:", totalPages, "currentPage:", currentPage);
    
    if (totalPages <= 1) {
        paginationNumbers.innerHTML = '';
        return;
    }
    
    let html = '';
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);
    
    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    if (startPage > 1) {
        html += `<div class="page-number" data-page="1">1</div>`;
        if (startPage > 2) {
            html += `<div class="page-number-dots">...</div>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 'active' : '';
        html += `<div class="page-number ${activeClass}" data-page="${i}">${i}</div>`;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<div class="page-number-dots">...</div>`;
        }
        html += `<div class="page-number" data-page="${totalPages}">${totalPages}</div>`;
    }
    
    paginationNumbers.innerHTML = html;
    
    // Tambahkan event listener untuk setiap nomor halaman
    document.querySelectorAll('.page-number').forEach(el => {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            console.log("Nomor halaman diklik:", page);
            if (page && page !== currentPage) {
                currentPage = page;
                filterSearchPeringkat();
            }
        });
    });
}

function goToPrevPage() {
    if (currentPage > 1) {
        currentPage--;
        filterSearchPeringkat();
    }
}

function goToNextPage() {
    const filteredData = getCurrentFilteredData();
    const totalPages = Math.ceil(filteredData.length / rowsPerPage);
    
    if (currentPage < totalPages) {
        currentPage++;
        filterSearchPeringkat();
    }
}

function getCurrentFilteredData() {
    const searchInput = document.getElementById("searchRanking");
    const keyword = searchInput ? searchInput.value.trim().toLowerCase() : "";
    
    return dataSiswa.filter(siswa => {
        return `
            ${siswa.rank}
            ${siswa.nama}
            ${siswa.kelas}
            ${siswa.nilai}
            ${siswa.status}
        `.toLowerCase().includes(keyword);
    });
}

function filterSearchPeringkat() {
    const searchInput = document.getElementById("searchRanking");
    const keyword = searchInput ? searchInput.value.trim().toLowerCase() : "";
    
    const filteredData = dataSiswa.filter(siswa => {
        return `
            ${siswa.rank || ''}
            ${siswa.nama || ''}
            ${siswa.kelas || ''}
            ${siswa.nilai || ''}
            ${siswa.status || ''}
        `.toLowerCase().includes(keyword);
    });
    
    console.log("filterSearchPeringkat - data length:", filteredData.length, "currentPage:", currentPage);
    
    const totalPages = Math.ceil(filteredData.length / rowsPerPage);
    if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
    }
    if (currentPage < 1) {
        currentPage = 1;
    }
    
    renderPeringkatPaginated(filteredData);
}

function getCurrentFilteredData() {
    const searchInput = document.getElementById("searchRanking");
    const keyword = searchInput ? searchInput.value.trim().toLowerCase() : "";
    
    return dataSiswa.filter(siswa => {
        return `
            ${siswa.rank || ''}
            ${siswa.nama || ''}
            ${siswa.kelas || ''}
            ${siswa.nilai || ''}
            ${siswa.status || ''}
        `.toLowerCase().includes(keyword);
    });
}

function setupSearchPeringkat() {
    const searchInput = document.getElementById("searchRanking");
    if (!searchInput) return;
    
    searchInput.addEventListener("input", () => {
        currentPage = 1;
        filterSearchPeringkat();
    });
}

function setupPaginationButtons() {
    if (prevPageBtn) {
        // Hapus event listener lama jika ada
        const newPrevBtn = prevPageBtn.cloneNode(true);
        prevPageBtn.parentNode.replaceChild(newPrevBtn, prevPageBtn);
        window.prevPageBtn = newPrevBtn;
        
        newPrevBtn.addEventListener("click", function(e) {
            e.preventDefault();
            console.log("Tombol Sebelumnya diklik, currentPage:", currentPage);
            if (currentPage > 1) {
                currentPage--;
                filterSearchPeringkat();
            }
        });
    }
    
    if (nextPageBtn) {
        // Hapus event listener lama jika ada
        const newNextBtn = nextPageBtn.cloneNode(true);
        nextPageBtn.parentNode.replaceChild(newNextBtn, nextPageBtn);
        window.nextPageBtn = newNextBtn;
        
        newNextBtn.addEventListener("click", function(e) {
            e.preventDefault();
            const filteredData = getCurrentFilteredData();
            const totalPages = Math.ceil(filteredData.length / rowsPerPage);
            console.log("Tombol Selanjutnya diklik, currentPage:", currentPage, "totalPages:", totalPages);
            
            if (currentPage < totalPages) {
                currentPage++;
                filterSearchPeringkat();
            }
        });
    }
}

function loadPeringkatDatabase() {
    if (!idGuruLogin || roleIdLogin !== "2") {
        alert("Silakan login sebagai guru terlebih dahulu.");
        window.location.href = "../login.html";
        return;
    }

    const semester = filterSemester ? filterSemester.value : "Semua";
    const angkatan = filterAngkatan ? filterAngkatan.value : "0";
    
    fetch(`get_peringkat.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&semester=${encodeURIComponent(semester)}&angkatan=${angkatan}`)
        .then(res => res.json())
        .then(result => {
            console.log("Data peringkat database:", result);

            if (result.status === "success") {
                dataSiswa = result.data || [];
                currentPage = 1; // Reset ke halaman pertama

                renderSummary(result.summary);
                filterSearchPeringkat();
            } else {
                alert(result.message);
            }
        })
        .catch(err => {
            console.error("Gagal load peringkat:", err);
            alert("Gagal memuat data peringkat.");
        });
}

// Event Listeners
if (filterSemester) {
    filterSemester.addEventListener("change", () => {
        currentPage = 1;
        loadPeringkatDatabase();
    });
}

if (filterAngkatan) {
    filterAngkatan.addEventListener("change", () => {
        currentPage = 1;
        loadPeringkatDatabase();
    });
}

document.addEventListener("DOMContentLoaded", function () {
    setupSearchPeringkat();
    setupPaginationButtons();
    loadPeringkatDatabase();
});