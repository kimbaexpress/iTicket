document.addEventListener('DOMContentLoaded', function() {
    // Función para verificar nuevos tickets
    function checkNewTickets() {
        fetch('functions/check_new_tickets.php')
            .then(response => response.text())
            .then(text => {
                console.log('Response Text:', text); // Depuración
                try {
                    const data = JSON.parse(text);
                    if (data.status === 'success' && data.messages.length > 0) {
                        showAlert(data.messages);
                    }
                } catch (error) {
                    console.error('Error parsing JSON:', error);
                }
            })
            .catch(error => console.error('Fetch Error:', error));
    }
    // Función para mostrar la alerta
    function showAlert(messages) {
        const alertContainer = document.getElementById('alert-container');
        alertContainer.innerHTML = ''; // Limpiar alertas anteriores
    
        messages.forEach(message => {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'bg-blue-500 text-white px-4 py-2 rounded mb-2 cursor-pointer';
            alertDiv.textContent = message;
            alertDiv.addEventListener('click', function() {
                // Actualizar last_check en el servidor
                fetch('functions/update_last_check.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            location.reload();
                        } else {
                            console.error('Error al actualizar last_check:', data.message);
                            location.reload(); // Opcionalmente, recargar de todos modos
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        location.reload(); // Opcionalmente, recargar de todos modos
                    });
            });
            alertContainer.appendChild(alertDiv);
        });
    }
    
    

    // Verificar nuevos tickets cada 10 segundos
    setInterval(checkNewTickets, 10000); // 10000 ms = 10 segundos

    // Verificar al cargar la página
    checkNewTickets();
});
/*
Explicación del Código:

    Evento DOMContentLoaded: Espera a que el DOM esté cargado antes de ejecutar el script.
    Función checkNewTickets: Realiza una solicitud fetch al script PHP y procesa la respuesta.
    Función showAlert: Muestra las alertas en el contenedor alert-container. Cada alerta es clickeable y, al hacer clic, recarga la página.
    Intervalo de Verificación: Utiliza setInterval para ejecutar checkNewTickets cada 30 segundos.
*/