$(document).ready(function() {
    const themeToggle = $('#themeToggle');
    const html = document.documentElement;
    
    // Initialize theme on page load
    initializeTheme();
    
    function initializeTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        applyTheme(savedTheme);
    }
    
    function applyTheme(theme) {
        if (theme === 'dark') {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            updateIconDisplay(true);
        } else {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            updateIconDisplay(false);
        }
    }
    
    function updateIconDisplay(isDark) {
        const icons = themeToggle.find('i');
        icons.each(function() {
            $(this).toggleClass('hidden');
        });
    }
    
    // Toggle theme on button click
    themeToggle.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const isDark = html.classList.contains('dark');
        const newTheme = isDark ? 'light' : 'dark';
        applyTheme(newTheme);
    });
    
    // Update time every minute
    function updateTime() {
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        $('#currentTime').text(hours + ':' + minutes);
    }
    
    updateTime();
    setInterval(updateTime, 60000);
});
