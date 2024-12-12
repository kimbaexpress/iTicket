<?php
include '../../../bdc/conex.php';
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
$conn->exec("SET time_zone = '-03:00'");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $response_id = isset($_POST['response_id']) ? intval($_POST['response_id']) : 0;
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;

    try {
        // Verificar que la respuesta existe y pertenece al usuario actual
        $stmt = $conn->prepare("SELECT * FROM ticket_responses WHERE response_id = :response_id AND responder_id = :user_id");
        $stmt->bindValue(':response_id', $response_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $response = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$response) {
            echo "No tienes permiso para eliminar esta respuesta.";
            exit();
        }

        // Eliminar la respuesta
        $stmt = $conn->prepare("DELETE FROM ticket_responses WHERE response_id = :response_id");
        $stmt->bindValue(':response_id', $response_id, PDO::PARAM_INT);
        $stmt->execute();

        // Redireccionar de vuelta al ticket
        header("Location: ../view-ticket.php?ticket_id=$ticket_id");
        exit();
    } catch (PDOException $e) {
        echo "Error al eliminar la respuesta: " . $e->getMessage();
        exit();
    }
} else {
    echo "Solicitud inválida.";
    exit();
}
?>
