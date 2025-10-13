// Toggle profile dropdown - Fixed version with null checks
document.addEventListener('DOMContentLoaded', function() {
    const profileButton = document.getElementById("profile-menu-button");
    const dropdown = document.getElementById("profile-dropdown");
    
    // Only attach listeners if elements exist
    if (profileButton && dropdown) {
        profileButton.addEventListener("click", function () {
            dropdown.classList.toggle("hidden");
        });
        
        // Close dropdown if clicked outside
        window.addEventListener("click", function(e) {
            if (!profileButton.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add("hidden");
            }
        });
    }
});