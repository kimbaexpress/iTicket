<?php
include '../../../bdc/conex.php'; // Ajusta la ruta según sea necesario
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
if (!isset($_SESSION['username'])) {
    header("Location: ../../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Obtener 'ticket_id' y 'status' desde POST
if (isset($_POST['ticket_id']) && isset($_POST['status'])) {
    $ticket_id = $_POST['ticket_id'];
    $new_status = $_POST['status'];
} else {
    echo "Datos inválidos.";
    exit();
}

// Validar y sanitizar inputs
$ticket_id = filter_var($ticket_id, FILTER_VALIDATE_INT);
$allowed_statuses = ['Pendiente', 'En Proceso', 'Resuelto'];
if (!in_array($new_status, $allowed_statuses)) {
    echo "Estado inválido.";
    exit();
}

try {
    // Verificar si el usuario tiene permiso para actualizar el estado
    $sql = "SELECT assigned_to_user_id FROM support_tickets WHERE ticket_id = :ticket_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
    $stmt->execute();
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo "El ticket no existe.";
        exit();
    }

    $assigned_to_user_id = $ticket['assigned_to_user_id'];

    if ($user_role === 'admin' || $user_role === 'support' || $assigned_to_user_id == $user_id) {
        // El usuario tiene permiso, proceder a actualizar el estado
        $update_sql = "UPDATE support_tickets SET status = :new_status WHERE ticket_id = :ticket_id";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bindParam(':new_status', $new_status, PDO::PARAM_STR);
        $update_stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $update_stmt->execute();

        // Redirigir de vuelta a la vista del ticket
        header("Location: ../view-ticket.php?ticket_id=" . $ticket_id);
        exit();
    } else {
        echo "No tienes permiso para actualizar el estado de este ticket.";
        exit();
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>
