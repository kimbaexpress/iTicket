<?php
include '../../../bdc/conex.php'; 
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

$conn->exec("SET time_zone = 'America/Argentina/Buenos_Aires'");

if (!isset($_SESSION['username'])) {
    header("Location: ../../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$unidad_usuario = $_SESSION['unidad'];
$user_name = $_SESSION['name'];

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
$allowed_statuses = ['Pendiente', 'En Proceso', 'Resuelto', 'Rechazado'];
if (!in_array($new_status, $allowed_statuses)) {
    echo "Estado inválido.";
    exit();
}

try {
    // Verificar si el ticket existe
    $sql = "SELECT status FROM support_tickets WHERE ticket_id = :ticket_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
    $stmt->execute();
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo "El ticket no existe.";
        exit();
    }

    if ($user_role === 'admin' || $user_role === 'support') {
        // El usuario tiene permiso, proceder a actualizar el estado
        $update_sql = "UPDATE support_tickets SET status = :new_status WHERE ticket_id = :ticket_id";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bindParam(':new_status', $new_status, PDO::PARAM_STR);
        $update_stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $update_stmt->execute();

        // Registrar la actividad
        $activity_description = $user_name . " marcó el ticket " . $ticket_id . " como '" . $new_status . "'";
        $stmtActivity = $conn->prepare("INSERT INTO ticket_activities (ticket_id, user_id, unidad, activity_type, description, activity_date)
                                        VALUES (:ticket_id, :user_id, :unidad, 'status_change', :description, NOW())");
        $stmtActivity->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $stmtActivity->bindValue(':user_id', $user_id, PDO::PARAM_INT); // Quien realizó la acción
        $stmtActivity->bindValue(':unidad', $unidad_usuario, PDO::PARAM_STR);
        $stmtActivity->bindValue(':description', $activity_description, PDO::PARAM_STR);
        $stmtActivity->execute();

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
