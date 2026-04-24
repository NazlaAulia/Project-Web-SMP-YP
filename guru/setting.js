function setupSettingsAnimation() {
    document.addEventListener("click", function (event) {
        const item = event.target.closest(".click-animate");

        if (!item) return;

        item.classList.remove("settings-active");

        void item.offsetWidth;

        item.classList.add("settings-active");
    });
}

setupSettingsAnimation();