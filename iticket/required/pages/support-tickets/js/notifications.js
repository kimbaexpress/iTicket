document.addEventListener('DOMContentLoaded', function () {
    // Function to check new tickets
    function checkNewTickets() {
        fetch('functions/check_new_tickets.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.messages.length > 0) {
                        showAlert(data.messages);
                    }
                } else {
                    console.error('Error:', data.message);
                    showError('Error fetching new tickets.');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                showError('Network error. Please check your connection.');
            });
    }

    // Function to show alert
    function showAlert(messages) {
        const alertContainer = document.getElementById('alert-container');
        alertContainer.innerHTML = ''; // Clear previous alerts

        messages.forEach(message => {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'bg-blue-500 text-white px-4 py-2 rounded mb-2 cursor-pointer';
            alertDiv.textContent = message;
            alertDiv.addEventListener('click', function () {
                location.reload();
            });
            alertContainer.appendChild(alertDiv);
        });
    }

    // Function to show error
    function showError(errorMessage) {
        const alertContainer = document.getElementById('alert-container');
        alertContainer.innerHTML = ''; // Clear previous alerts

        const alertDiv = document.createElement('div');
        alertDiv.className = 'bg-red-500 text-white px-4 py-2 rounded mb-2';
        alertDiv.textContent = errorMessage;
        alertContainer.appendChild(alertDiv);
    }

    // Check new tickets every 10 seconds
    setInterval(checkNewTickets, 10000); // 10000 ms = 10 seconds

    // Check when the page loads
    checkNewTickets();
});
