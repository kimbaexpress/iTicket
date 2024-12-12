$(document).ready(function() {
    // Abrir la sidebar
    $('#menu-toggle').click(function() {
        $('#sidebar').removeClass('-translate-x-full');
        $('body').addClass('overflow-hidden');
    });

    // Cerrar la sidebar
    $('#close-sidebar').click(function() {
        $('#sidebar').addClass('-translate-x-full');
        $('body').removeClass('overflow-hidden');
    });
});

