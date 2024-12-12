<?php
// functions/unassign_unit.php
include '../../../bdc/conex.php';
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
$conn->exec("SET time_zone = '-03:00'");

$unidad_usuario = $_SESSION['unidad'];

if ($unidad_usuario === 'u_helpdesk' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = $_POST['ticket_id'];
    $units_to_unassign = isset($_POST['units_to_unassign']) ? $_POST['units_to_unassign'] : [];

    // Validar unidades
    $unidades_validas = ['u_helpdesk', 'u_soporte', 'u_desarrollo', 'u_seguridad'];
    foreach ($units_to_unassign as $unidad) {
        if (!in_array($unidad, $unidades_validas)) {
            echo "Error: Unidad no válida para desasignar.";
            exit();
        }
    }

    try {
        // Desasignar unidades y agentes asociados
        if (!empty($units_to_unassign)) {
            foreach ($units_to_unassign as $unidad) {
                // Desasignar unidad
                $stmtUnassign = $conn->prepare("UPDATE ticket_unit_assignments SET unassigned_at = NOW() WHERE ticket_id = :ticket_id AND unidad = :unidad AND unassigned_at IS NULL");
                $stmtUnassign->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                $stmtUnassign->bindValue(':unidad', $unidad, PDO::PARAM_STR);
                $stmtUnassign->execute();

                // Desasignar agentes de la unidad
                $stmtDeleteAgents = $conn->prepare("DELETE FROM ticket_assignments WHERE ticket_id = :ticket_id AND user_id IN (SELECT user_id FROM users WHERE unidad = :unidad)");
                $stmtDeleteAgents->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                $stmtDeleteAgents->bindValue(':unidad', $unidad, PDO::PARAM_STR);
                $stmtDeleteAgents->execute();
            }
        }

        // Actualizar el campo updated_at en support_tickets
        $stmtUpdateTicket = $conn->prepare("UPDATE support_tickets SET updated_at = NOW() WHERE ticket_id = :ticket_id");
        $stmtUpdateTicket->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $stmtUpdateTicket->execute();

        // Redirigir después de la desasignación
        header("Location: ../index.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}
?>
