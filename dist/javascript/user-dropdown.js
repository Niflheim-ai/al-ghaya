// Toggle profile dropdown
document.getElementById("profile-menu-button").addEventListener("click", function () {
    const dropdown = document.getElementById("profile-dropdown");
    dropdown.classList.toggle("hidden");
});

// Close dropdown if clicked outside
window.addEventListener("click", function(e) {
    const button = document.getElementById("profile-menu-button");
    const dropdown = document.getElementById("profile-dropdown");
    if (!button.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add("hidden");
    }
});