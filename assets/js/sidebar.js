$(document).ready(function() {
    const sidebar = $('#sidebar');
    const toggleBtn = $('#toggleSidebar');
    const sidebarLinks = $('.sidebar-link');
    const isMobile = () => $(window).width() <= 768;
    
    // Initialize sidebar state
    if (isMobile()) {
        sidebar.removeClass('expanded').addClass('collapsed');
    }
    
    // Toggle sidebar
    if (toggleBtn.length > 0) {
        toggleBtn.on('click', function() {
            if (isMobile()) {
                // Mobile: drawer behavior
                sidebar.toggleClass('collapsed expanded');
                $('body').toggleClass('sidebar-open');
            } else {
                // Desktop: collapse/expand
                sidebar.toggleClass('collapsed expanded');
            }
            
            const icon = $(this).find('i');
            if (sidebar.hasClass('collapsed')) {
                icon.removeClass('fa-times').addClass('fa-bars');
            } else {
                icon.removeClass('fa-bars').addClass('fa-times');
            }
        });
    }
    
    // Close sidebar on link click (mobile)
    if (sidebarLinks.length > 0) {
        sidebarLinks.on('click', function() {
            if (isMobile() && sidebar.length > 0) {
                sidebar.removeClass('expanded').addClass('collapsed');
                $('body').removeClass('sidebar-open');
                if (toggleBtn.length > 0) {
                    toggleBtn.find('i').removeClass('fa-times').addClass('fa-bars');
                }
            }
        });
    }
    
    // Tooltip functionality for collapsed sidebar
    if (sidebarLinks.length > 0) {
        sidebarLinks.each(function() {
            const link = $(this);
            const tooltip = link.attr('data-tooltip');
            let tooltipEl = null;
            
            link.on('mouseenter', function() {
                if (!isMobile() && sidebar.length > 0 && sidebar.hasClass('collapsed')) {
                    tooltipEl = $('<div class="tooltip">' + tooltip + '</div>');
                    $('body').append(tooltipEl);
                    
                    const linkPos = link.offset();
                    const linkHeight = link.outerHeight();
                    
                    if (linkPos && tooltipEl.length > 0) {
                        tooltipEl.css({
                            top: linkPos.top + (linkHeight / 2) - (tooltipEl.outerHeight() / 2),
                            left: linkPos.left + link.outerWidth() + 10
                        });
                        
                        setTimeout(function() {
                            if (tooltipEl && tooltipEl.length > 0) {
                                tooltipEl.addClass('show');
                            }
                        }, 100);
                    }
                }
            }).on('mouseleave', function() {
                if (tooltipEl && tooltipEl.length > 0) {
                    tooltipEl.remove();
                }
                tooltipEl = null;
            });
        });
    }
    
    // Handle window resize
    $(window).resize(function() {
        if (!isMobile()) {
            sidebar.removeClass('collapsed').addClass('expanded');
            $('body').removeClass('sidebar-open');
            toggleBtn.find('i').removeClass('fa-times').addClass('fa-bars');
        }
    });
});
