document.addEventListener('DOMContentLoaded', function() {
    const ticketData = document.getElementById('ticket-data');

    if (ticketData) {
        const success = ticketData.getAttribute('data-success');
        const ticketId = ticketData.getAttribute('data-ticket-id');

        if (success === '1' && ticketId) {
            Swal.fire({
                title: '¡Éxito!',
                text: `Su ticket fue creado de forma correcta con el N° ${ticketId}.`,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Visualizar',
                cancelButtonText: 'Cerrar',
                heightAuto: false, // Agrega esta línea
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirigir a la página de visualización del ticket
                    window.location.href = `../user-tickets/view-ticket.php?ticket_id=${ticketId}`;
                }
            });
        } else if (success === '0') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un problema al crear su ticket. Por favor, intente nuevamente.',
                heightAuto: false, // Agrega esta línea
            });
        }
    }
});
