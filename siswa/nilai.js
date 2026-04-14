window.addEventListener("load", () => {
  tampilkanBox();
  tampilkanRow();
  jalankanCounter();
  aktifkanFilterAnimasi();
  aktifkanExportAnimasi();
});

/* ===== ANIMASI BOX ===== */
function tampilkanBox() {
  const boxes = document.querySelectorAll(".stat-box");

  boxes.forEach((box, index) => {
    box.style.opacity = "0";
    box.style.transform = "translateY(18px)";

    setTimeout(() => {
      box.classList.add("show-box");
    }, 180 * index);
  });
}

/* ===== ANIMASI BARIS TABEL ===== */
function tampilkanRow() {
  const rows = document.querySelectorAll("#nilaiTableBody tr");

  rows.forEach((row, index) => {
    row.style.opacity = "0";
    row.style.transform = "translateY(18px)";

    setTimeout(() => {
      row.classList.add("show-row");
    }, 250 + index * 140);
  });
}

/* ===== COUNTER ANGKA ===== */
function jalankanCounter() {
  const counters = document.querySelectorAll(".counter");

  counters.forEach((counter) => {
    const target = parseFloat(counter.dataset.target);
    const decimal = String(target).includes(".");
    let current = 0;
    const increment = target / 40;

    function updateCounter() {
      current += increment;

      if (current >= target) {
        counter.textContent = target;
      } else {
        counter.textContent = decimal
          ? current.toFixed(1)
          : Math.floor(current);
        requestAnimationFrame(updateCounter);
      }
    }

    updateCounter();
  });
}

/* ===== ANIMASI FILTER ===== */
function aktifkanFilterAnimasi() {
  const kelas = document.getElementById("kelas");
  const semester = document.getElementById("semester");
  const nilaiSection = document.querySelector(".nilai-section");

  [kelas, semester].forEach((select) => {
    select.addEventListener("change", () => {
      nilaiSection.style.transition = "0.3s ease";
      nilaiSection.style.transform = "scale(0.98)";
      nilaiSection.style.opacity = "0.7";

      setTimeout(() => {
        nilaiSection.style.transform = "scale(1)";
        nilaiSection.style.opacity = "1";
      }, 180);
    });
  });
}

/* ===== ANIMASI TOMBOL EXPORT ===== */
function aktifkanExportAnimasi() {
  const exportBtn = document.getElementById("exportBtn");

  if (exportBtn) {
    exportBtn.addEventListener("click", () => {
      exportBtn.classList.add("pulse-click");

      setTimeout(() => {
        exportBtn.classList.remove("pulse-click");
      }, 350);

      alert("Tombol export siap dihubungkan ke backend PDF/Excel.");
    });
  }
}