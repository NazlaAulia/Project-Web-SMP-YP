const mainLoginForm = document.getElementById("mainLoginForm");
const loginBtn = document.getElementById("loginBtn");

if (mainLoginForm) {
  mainLoginForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(mainLoginForm);

    if (loginBtn) {
      loginBtn.disabled = true;
    }

    fetch("guru/login.php", {
      method: "POST",
      body: formData
    })
      .then(response => response.json())
      .then(result => {
        if (loginBtn) {
          loginBtn.disabled = false;
        }

        if (result.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: result.message,
            timer: 1000,
            showConfirmButton: false
          }).then(() => {
            window.location.href = result.redirect;
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Terjadi Kesalahan",
            text: result.message
          });
        }
      })
      .catch(error => {
        if (loginBtn) {
          loginBtn.disabled = false;
        }

        console.error(error);

        Swal.fire({
          icon: "error",
          title: "Terjadi Kesalahan",
          text: "Response server tidak valid."
        });
      });
  });
}