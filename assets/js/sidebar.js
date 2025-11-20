$(document).ready(function() {
    const sidebar = $('#sidebar');
    const toggleBtn = $('#toggleSidebar');
    const sidebarLinks = $('.sidebar-link');
    
    // Toggle sidebar
    toggleBtn.click(function() {
        sidebar.toggleClass('collapsed expanded');
        
        const icon = $(this).find('i');
        if (sidebar.hasClass('collapsed')) {
            icon.removeClass('fa-times').addClass('fa-bars');
        } else {
            icon.removeClass('fa-bars').addClass('fa-times');
        }
    });
    
    // Tooltip functionality for collapsed sidebar
    sidebarLinks.each(function() {
        const link = $(this);
        const tooltip = link.attr('data-tooltip');
        let tooltipEl = null;
        
        link.hover(
            function() {
                if (sidebar.hasClass('collapsed')) {
                    tooltipEl = $('<div class="tooltip">' + tooltip + '</div>');
                    $('body').append(tooltipEl);
                    
                    const linkPos = link.offset();
                    const linkHeight = link.outerHeight();
                    
                    tooltipEl.css({
                        top: linkPos.top + (linkHeight / 2) - (tooltipEl.outerHeight() / 2),
                        left: linkPos.left + link.outerWidth() + 10
                    });
                    
                    setTimeout(function() {
                        tooltipEl.addClass('show');
                    }, 100);
                }
            },
            function() {
                if (tooltipEl) {
                    tooltipEl.remove();
                    tooltipEl = null;
                }
            }
        );
    });
});
