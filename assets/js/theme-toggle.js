document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;
    const icon = themeToggle.querySelector('i');

    // Check for saved theme preference or default to dark theme
    const currentTheme = localStorage.getItem('theme') || 'dark';
    body.classList.toggle('light-mode', currentTheme === 'light');
    updateIcon(currentTheme === 'light');

    themeToggle.addEventListener('click', () => {
        body.classList.toggle('light-mode');
        const isLightMode = body.classList.contains('light-mode');
        localStorage.setItem('theme', isLightMode ? 'light' : 'dark');
        updateIcon(isLightMode);
    });

    function updateIcon(isLightMode) {
        icon.classList.remove(isLightMode ? 'fa-moon' : 'fa-sun');
        icon.classList.add(isLightMode ? 'fa-sun' : 'fa-moon');
    }
});
