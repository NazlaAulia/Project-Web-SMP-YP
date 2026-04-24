const titleRectangle = document.getElementById("titleRectangle");
const requestChip = document.getElementById("requestChip");
const requestFormCard = document.getElementById("requestFormCard");
const guideCard = document.getElementById("guideCard");
const aiBox = document.getElementById("aiBox");

function replayAnimation(element, className) {
    if (!element) return;
    element.classList.remove(className);
    void element.offsetWidth;
    element.classList.add(className);
}

window.addEventListener("load", function () {
    setTimeout(() => replayAnimation(titleRectangle, "rect-animate"), 150);
    setTimeout(() => replayAnimation(requestChip, "chip-pop"), 250);

    if (requestFormCard) requestFormCard.classList.add("section-show");
    if (guideCard) guideCard.classList.add("section-show");
});

if (titleRectangle) {
    titleRectangle.addEventListener("click", function () {
        replayAnimation(titleRectangle, "rect-animate");
    });
}

if (requestChip) {
    requestChip.addEventListener("click", function () {
        replayAnimation(requestChip, "chip-pop");
    });
}

if (aiBox) {
    aiBox.addEventListener("click", function () {
        replayAnimation(aiBox, "chip-pop");
    });
}