$(document).ready(function() {
    const themeToggle = $('#themeToggle');
    
    // Set initial theme state
    updateThemeDisplay();
    
    // Toggle theme on button click
    themeToggle.click(function() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');
        
        if (isDark) {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
        
        updateThemeDisplay();
    });
    
    function updateThemeDisplay() {
        const isDark = document.documentElement.classList.contains('dark');
        const icon = themeToggle.find('i');
        
        if (isDark) {
            // In dark mode, show sun icon
            icon.removeClass('fa-sun').addClass('fa-moon');
        } else {
            // In light mode, show moon icon
            icon.removeClass('fa-moon').addClass('fa-sun');
        }
    }
    
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
