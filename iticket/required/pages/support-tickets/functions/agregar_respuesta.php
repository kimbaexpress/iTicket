<?php
include '../../../bdc/conex.php';
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

$conn->exec("SET time_zone = 'America/Argentina/Buenos_Aires'");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $response_text = trim($_POST['response_text']);
    $response_date = date('Y-m-d H:i:s');
    $is_private = 0; 

    try {
        // Verificar que el ticket existe
        $stmt = $conn->prepare("SELECT * FROM support_tickets WHERE ticket_id = :ticket_id");
        $stmt->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $stmt->execute();
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            echo "El ticket no existe.";
            exit();
        }

        $has_access = false;

        if ($ticket['create_by_user_id'] == $user_id || $user_role == 'support' || $user_role == 'admin') {
            $has_access = true;
        } else {
            // Verificar si el usuario está asignado al ticket en ticket_assignments
            $stmtAssignment = $conn->prepare("SELECT * FROM ticket_assignments WHERE ticket_id = :ticket_id AND user_id = :user_id");
            $stmtAssignment->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
            $stmtAssignment->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmtAssignment->execute();
            $assignment = $stmtAssignment->fetch(PDO::FETCH_ASSOC);

            if ($assignment) {
                $has_access = true;
            }
        }

        if (!$has_access) {
            echo "No tienes permiso para responder a este ticket.";
            exit();
        }

        // Insertar la respuesta
        $stmt = $conn->prepare("INSERT INTO ticket_responses (ticket_id, responder_id, response_text, response_date, is_private)
                                VALUES (:ticket_id, :responder_id, :response_text, :response_date, :is_private)");
        $stmt->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $stmt->bindValue(':responder_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':response_text', $response_text, PDO::PARAM_STR);
        $stmt->bindValue(':response_date', $response_date, PDO::PARAM_STR);
        $stmt->bindValue(':is_private', $is_private, PDO::PARAM_INT);
        $stmt->execute();

        // Redireccionar de vuelta al ticket
        header("Location: ../view-ticket.php?ticket_id=$ticket_id");
        exit();
    } catch (PDOException $e) {
        echo "Error al agregar la respuesta: " . $e->getMessage();
        exit();
    }
} else {
    echo "Solicitud inválida.";
    exit();
}
?>
