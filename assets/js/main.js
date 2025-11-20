$(document).ready(function() {
    // Initialize login dialog
    $('#loginDialog').dialog({
        autoOpen: false,
        modal: true,
        width: 450,
        draggable: false,
        resizable: false,
        title: 'Login Options',
        closeText: 'Close'
    });
    
    // Open login dialog
    $('#loginBtn').click(function() {
        $('#loginDialog').dialog('open');
    });
    
    // Redirect to registration page
    $('#registerBtn').click(function() {
        window.location.href = 'register.php';
    });
});
