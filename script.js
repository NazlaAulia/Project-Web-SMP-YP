// ================= SLIDER HERO =================
let slides = document.querySelectorAll(".slide");
let index = 0;

function showSlide(){
    // Cek dhisik slides-e ono opo gak
    if (slides.length === 0) return; 

    slides.forEach(slide => slide.classList.remove("active"));

    index++;
    if(index >= slides.length){
        index = 0;
    }

    // Pastikno slides[index] gak kosong
    if (slides[index]) {
        slides[index].classList.add("active");
    }
}

// Jalankan auto slide mung nek pancen ono slide-e
if (slides.length > 0) {
    setInterval(showSlide, 3000);
}


// ================= COUNTER =================
let counters = document.querySelectorAll(".counter");

if (counters.length > 0) {
    counters.forEach(counter => {
        const target = +counter.getAttribute("data-target");
        let count = 0;

        function updateCount(){
            const increment = Math.ceil(target / 200);

            if(count < target){
                count += increment;
                counter.innerText = count;
                requestAnimationFrame(updateCount);
            } else {
                counter.innerText = target + "+";
            }
        }

        updateCount();
    });
}


// ================= ALUMNI SLIDER =================
let alumniIndex = 0;
const container = document.querySelector('.alumni-container');
const cards = document.querySelectorAll('.alumni-card');
const dots = document.querySelectorAll('.dot');

function updateSlider() {
    // Saringan: Nek container gak ketemu, ojo diterusno
    if (!container) return; 

    container.style.transform = `translateX(-${alumniIndex * 100}%)`;

    if (dots.length > 0) {
        dots.forEach((dot, i) => {
            if (dot) dot.classList.toggle('active', i === alumniIndex);
        });
    }
}

function currentAlumni(index){
    alumniIndex = index;
    updateSlider();
}

// Auto slide alumni mung nek ono kartu alumni-ne
if (cards.length > 0) {
    setInterval(() => {
        alumniIndex++;
        if (alumniIndex >= cards.length) {
            alumniIndex = 0;
        }
        updateSlider();
    }, 5000);
}

