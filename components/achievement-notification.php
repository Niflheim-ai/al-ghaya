<!-- Achievement Notification System -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .achievement-toast {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3) !important;
    }
    
    .achievement-toast .swal2-icon {
        border-color: white !important;
        color: white !important;
    }
    
    .achievement-toast .swal2-title {
        color: white !important;
        font-weight: bold !important;
    }
    
    .achievement-toast .swal2-html-container {
        color: rgba(255, 255, 255, 0.95) !important;
    }
    
    .achievement-icon-display {
        width: 80px;
        height: 80px;
        margin: 0 auto 10px;
        animation: bounce 0.6s ease-in-out;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }
    
    .confetti-animation {
        position: fixed;
        width: 10px;
        height: 10px;
        background: #f0f;
        animation: confetti-fall 3s linear forwards;
        z-index: 99999;
    }
    
    @keyframes confetti-fall {
        to {
            transform: translateY(100vh) rotate(360deg);
            opacity: 0;
        }
    }
</style>

<script>
// Define functions first
function showAchievementNotification(achievement) {
    console.log('Showing achievement:', achievement);
    
    createConfetti();
    playAchievementSound();
    
    // Determine asset path based on current location
    let assetPath = '../../assets/';
    const currentPath = window.location.pathname;
    
    // Adjust path if needed
    if (currentPath.includes('/pages/student/')) {
        assetPath = '../../assets/';
    } else if (currentPath.includes('/student/')) {
        assetPath = '../assets/';
    }
    
    const iconHtml = achievement.icon 
        ? `<img src="${assetPath}achievements/${achievement.icon}" class="achievement-icon-display" alt="${achievement.name}">`
        : '<i class="ph ph-trophy" style="font-size: 80px; color: gold;"></i>';
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
        icon: 'success',
        title: '🎉 Achievement Unlocked!',
        html: `
            <div style="padding: 10px;">
                ${iconHtml}
                <h3 style="margin: 10px 0; font-size: 20px; font-weight: bold; color: white;">${achievement.name}</h3>
                <p style="margin: 5px 0; font-size: 14px; opacity: 0.95; color: white;">${achievement.description}</p>
            </div>
        `,
        customClass: {
            popup: 'achievement-toast'
        },
        showClass: {
            popup: 'swal2-show',
            backdrop: 'swal2-backdrop-show',
            icon: 'swal2-icon-show'
        },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
}

function createConfetti() {
    const colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#43e97b', '#38f9d7'];
    
    for (let i = 0; i < 50; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti-animation';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.animationDelay = Math.random() * 0.5 + 's';
        confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
        document.body.appendChild(confetti);
        
        setTimeout(() => confetti.remove(), 3000);
    }
}

function playAchievementSound() {
    // Optional: Add achievement sound
    // const audio = new Audio('../../assets/sounds/achievement.mp3');
    // audio.play().catch(e => console.log('Sound play failed:', e));
}

// Show achievements from session (e.g., from login)
<?php if (isset($_SESSION['new_achievements']) && !empty($_SESSION['new_achievements'])): ?>
    const sessionAchievements = <?= json_encode($_SESSION['new_achievements']) ?>;
    console.log('Session achievements to show:', sessionAchievements);
    
    // Wait for page to fully load, then show achievements
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            sessionAchievements.forEach((achievement, index) => {
                setTimeout(() => {
                    showAchievementNotification(achievement);
                }, index * 500);
            });
        }, 1000);
    });
    
    <?php unset($_SESSION['new_achievements']); ?>
<?php endif; ?>

// Achievement Notification System - Poll for new achievements
let achievementCheckInterval;

function checkNewAchievements() {
    const basePath = window.location.pathname.includes('/pages/student/') ? '../../php/' : '../php/';
    
    fetch(basePath + 'check-new-achievements.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.achievements.length > 0) {
                console.log('New achievements found:', data.achievements);
                data.achievements.forEach((achievement, index) => {
                    setTimeout(() => {
                        showAchievementNotification(achievement);
                    }, index * 500);
                });
            }
        })
        .catch(error => {
            console.error('Error checking achievements:', error);
        });
}

// Check for achievements every 5 seconds
achievementCheckInterval = setInterval(checkNewAchievements, 5000);

// Check after 2 seconds (after session achievements)
setTimeout(checkNewAchievements, 2000);

// Clean up interval when page unloads
window.addEventListener('beforeunload', () => {
    clearInterval(achievementCheckInterval);
});
</script>