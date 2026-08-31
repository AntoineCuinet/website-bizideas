import './stimulus_bootstrap.js';

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('theme-toggle');
    const collaToggle = document.getElementById('collaborators-toggle');
    const collaMenu = document.querySelector('.header-dropdown-menu');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }

    if (collaToggle && collaMenu) {
        collaToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            collaMenu.classList.toggle('show');
            collaToggle.classList.toggle('active');
        });
        document.addEventListener('click', (e) => {
            if (!collaMenu.contains(e.target) && !collaToggle.contains(e.target)) {
                collaMenu.classList.remove('show');
                collaToggle.classList.remove('active');
            }
        });
    }
});