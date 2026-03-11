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