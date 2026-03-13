// ================= SLIDER =================

let slides = document.querySelectorAll(".slide");
let index = 0;

function showSlide(){

slides.forEach(slide =>{
slide.classList.remove("active");
});

index++;

if(index >= slides.length){
index = 0;
}

slides[index].classList.add("active");

}

setInterval(showSlide,3000);


// ================= COUNTER =================

let counters = document.querySelectorAll(".counter");

counters.forEach(counter => {

const target = +counter.getAttribute("data-target");

let count = 0;

function updateCount(){

const increment = target / 120;

if(count < target){

count += increment;

counter.innerText = Math.floor(count);

requestAnimationFrame(updateCount);

}
else{

counter.innerText = target + "+";

}

}

updateCount();

});

window.onload = function() {
    let alumniIndex = 0;
    const container = document.querySelector('.alumni-container');
    const cards = document.querySelectorAll('.alumni-card');
    const dots = document.querySelectorAll('.dot');

    function updateSlider() {
        // Rumus gesernya
        container.style.transform = `translateX(-${alumniIndex * 100}%)`;
        
        // Update titik (dots)
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === alumniIndex);
        });
    }

    // Fungsi buat ganti otomatis
    function autoSlide() {
        alumniIndex++;
        if (alumniIndex >= cards.length) {
            alumniIndex = 0;
        }
        updateSlider();
    }

    // Jalan otomatis tiap 5 detik
    let timer = setInterval(autoSlide, 5000);

    // Kalau dot diklik
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            alumniIndex = index;
            updateSlider();
            // Reset timer biar nggak langsung geser pas baru diklik
            clearInterval(timer);
            timer = setInterval(autoSlide, 5000);
        });
    });
};

