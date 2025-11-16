const whiteSection = document.getElementById("white-section");

window.addEventListener("scroll", () => {
    if (window.scrollY > 50) {
        whiteSection.classList.add("active");
    } else {
        whiteSection.classList.remove("active");
    }
});
