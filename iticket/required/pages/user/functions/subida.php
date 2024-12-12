<?php
include '../../../bdc/conex.php'; // Ajusta la ruta según sea necesario
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $asunto = trim($_POST['asunto']);
    $sector = trim($_POST['sector']);
    $descripcion = trim($_POST['descripcion']);
    $interno = isset($_POST['interno']) ? trim($_POST['interno']) : null;
    $status = 'Pendiente';
    $classification = 'baja';
    $creationDate = date('Y-m-d H:i:s');

    try {
        $stmt = $conn->prepare("INSERT INTO support_tickets (create_by_user_id, title, sector, classification, description, internal_number, status, creation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $asunto, $sector, $classification, $descripcion, $interno, 'Pendiente', $creationDate]);
        
        // Obtener el ID del ticket recién creado
        $ticket_id = $conn->lastInsertId();

        // Redireccionar a la página del formulario con el ticket_id
        header('Location: ../index.php?success=1&ticket_id=' . $ticket_id);
        exit();
    } catch (PDOException $e) {
        // Manejo de errores
        header('Location: ../index.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Redireccionar si se accede sin enviar el formulario
    header('Location: ../index.php');
    exit();
}
?>
