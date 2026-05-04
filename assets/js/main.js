// Mobile nav toggle
function toggleNav() {
    const menu = document.getElementById('navMenu');
    if (menu) menu.classList.toggle('open');
}

// Close nav when clicking outside
document.addEventListener('click', function(e) {
    const menu   = document.getElementById('navMenu');
    const toggle = document.querySelector('.nav-toggle');
    if (menu && toggle && !menu.contains(e.target) && !toggle.contains(e.target)) {
        menu.classList.remove('open');
    }
});