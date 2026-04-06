const data = [
  { rank: 10, nama: "Budi Santoso", kelas: "9C", nilai: 95.5 },
  { rank: 11, nama: "Siti Aminah", kelas: "9C", nilai: 94.2 },
  { rank: 12, nama: "Adinda Eka Athiyyah Zahra", kelas: "9C", nilai: 72.2 }
];

const tableBody = document.getElementById("tableBody");

if (tableBody) {
  data.forEach((item, index) => {
    const row = document.createElement("tr");

    row.innerHTML = `
      <td>${item.rank}</td>
      <td>${item.nama}</td>
      <td>${item.kelas}</td>
      <td>${item.nilai}</td>
    `;

    if (item.nama.includes("Adinda")) {
      row.style.background = "#1ca34a";
      row.style.color = "white";
    }

    row.style.opacity = 0;
    setTimeout(() => {
      row.style.transition = "0.5s";
      row.style.opacity = 1;
    }, index * 200);

    tableBody.appendChild(row);
  });
}