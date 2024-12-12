<?php
include '../../../bdc/conex.php'; // Ajusta la ruta según sea necesario
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $response_text = trim($_POST['response_text']);
    $response_date = date('Y-m-d H:i:s');
    $is_private = 0; // Asumimos que las respuestas de los usuarios no son privadas

    try {
        // Verificar que el ticket existe y que el usuario tiene acceso
        $sql = "SELECT st.* FROM support_tickets st
                WHERE st.ticket_id = :ticket_id AND 
                (st.create_by_user_id = :user_id OR st.assigned_to_user_id = :user_id OR :user_role = 'support')";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_role', $user_role, PDO::PARAM_STR);
        $stmt->execute();
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            echo "No tienes permiso para responder a este ticket o no existe.";
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
