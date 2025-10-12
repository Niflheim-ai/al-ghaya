document.addEventListener("DOMContentLoaded", function() {
    const button = document.getElementById("lang-button");
    const dropdown = document.getElementById("lang-dropdown");

    button.addEventListener("click", function(e) {
        e.stopPropagation();
        dropdown.classList.toggle("hidden");
    });

    window.addEventListener("click", function(e) {
        if (!button.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add("hidden");
        }
    });

    dropdown.querySelectorAll("a").forEach(btn => {
        btn.addEventListener("click", () => {
            document.getElementById("selected-lang").innerText = btn.innerText;
            dropdown.classList.add("hidden");
        });
    });
});