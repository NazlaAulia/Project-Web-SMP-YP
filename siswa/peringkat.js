const namaLogin = localStorage.getItem("nama") || "Adinda Eka Athiyyah Zahra";
const kelasLogin = localStorage.getItem("kelas") || "9C";

const data = [
  { rank: 10, nama: "Budi Santoso", kelas: "9C", nilai: 95.5, status: "↑" },
  { rank: 11, nama: "Siti Aminah", kelas: "9C", nilai: 94.2, status: "↓" },
  { rank: 12, nama: namaLogin, kelas: kelasLogin, nilai: 72.2, status: "↔" }
];

const tableBody = document.getElementById("tableBody");

if (tableBody) {
  tableBody.innerHTML = "";

  data.forEach((item, index) => {
    const row = document.createElement("tr");

    row.innerHTML = `
      <td>${item.rank}</td>
      <td>${item.nama}</td>
      <td>${item.kelas}</td>
      <td>${item.nilai}</td>
    `;

    if (item.nama === namaLogin) {
      row.style.background = "#1ca34a";
      row.style.color = "white";
    }

    row.style.opacity = "0";
    setTimeout(() => {
      row.style.transition = "0.5s";
      row.style.opacity = "1";
    }, index * 200);

    tableBody.appendChild(row);
  });
}

/* isi nama + kelas di header kalau elemennya ada */
const namaText = document.getElementById("namaText");
const kelasText = document.getElementById("kelasText");
const avatarText = document.getElementById("avatarText");

if (namaText) {
  namaText.textContent = namaLogin;
}

if (kelasText) {
  kelasText.textContent = kelasLogin;
}

if (avatarText && namaLogin) {
  avatarText.textContent = namaLogin.charAt(0).toUpperCase();
}

// ===== PAGINATION =====
const perPage = 2;
let currentPage = 1;

function renderTable() {
  tableBody.innerHTML = "";

  const start = (currentPage - 1) * perPage;
  const end = start + perPage;
  const pageData = data.slice(start, end);

  pageData.forEach((item) => {
    const row = document.createElement("tr");

    row.innerHTML = `
      <td>${item.rank}</td>
      <td>${item.nama}</td>
      <td>${item.kelas}</td>
      <td>${item.nilai}</td>
      <td>↔</td>
    `;

    if (item.nama.includes("Adinda")) {
      row.classList.add("active-row");
    }

    tableBody.appendChild(row);
  });
}

// tombol pagination
const buttons = document.querySelectorAll(".pagination button");

buttons.forEach((btn) => {
  btn.addEventListener("click", () => {
    const text = btn.textContent;

    if (text === "<") {
      if (currentPage > 1) currentPage--;
    } else if (text === ">") {
      const maxPage = Math.ceil(data.length / perPage);
      if (currentPage < maxPage) currentPage++;
    } else {
      currentPage = parseInt(text);
    }

    // update active button
    buttons.forEach(b => b.classList.remove("active-page"));
    btn.classList.add("active-page");

    renderTable();
  });
});

// render awal
renderTable();