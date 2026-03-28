// ================= SLIDER HERO =================
let slides = document.querySelectorAll(".slide");
let index = 0;

function showSlide(){
    slides.forEach(slide => slide.classList.remove("active"));

    index++;
    if(index >= slides.length){
        index = 0;
    }

    slides[index].classList.add("active");
}

setInterval(showSlide, 3000);


// ================= COUNTER =================
let counters = document.querySelectorAll(".counter");

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


// ================= ALUMNI SLIDER =================
let alumniIndex = 0;
const container = document.querySelector('.alumni-container');
const cards = document.querySelectorAll('.alumni-card');
const dots = document.querySelectorAll('.dot');

function updateSlider() {
    container.style.transform = `translateX(-${alumniIndex * 100}%)`;

    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === alumniIndex);
    });
}

function currentAlumni(index){
    alumniIndex = index;
    updateSlider();
}

// auto slide
setInterval(() => {
    alumniIndex++;
    if (alumniIndex >= cards.length) {
        alumniIndex = 0;
    }
    updateSlider();
}, 5000);