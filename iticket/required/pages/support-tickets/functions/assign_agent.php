<?php
include '../../../bdc/conex.php';
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

$conn->exec("SET time_zone = 'America/Argentina/Buenos_Aires'");

$unidad_usuario = $_SESSION['unidad'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = $_POST['ticket_id'];
    $assigned_to_user_ids = isset($_POST['assigned_to_user_ids']) ? $_POST['assigned_to_user_ids'] : [];

    // Validar que el ticket está asignado a la unidad del usuario
    $query = "SELECT * FROM ticket_unit_assignments WHERE ticket_id = :ticket_id AND unidad = :unidad AND unassigned_at IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
    $stmt->bindValue(':unidad', $unidad_usuario, PDO::PARAM_STR);
    $stmt->execute();
    $ticket_unit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket_unit) {
        echo "Error: No tienes permiso para asignar agentes a este ticket.";
        exit();
    }

    // Validar agentes
    $queryAgents = "SELECT user_id FROM users WHERE unidad = :unidad AND role IN ('support', 'admin', 'coordinator')";
    $stmtAgents = $conn->prepare($queryAgents);
    $stmtAgents->bindValue(':unidad', $unidad_usuario, PDO::PARAM_STR);
    $stmtAgents->execute();
    $agent_ids = $stmtAgents->fetchAll(PDO::FETCH_COLUMN);

    foreach ($assigned_to_user_ids as $assigned_user_id) {
        if (!in_array($assigned_user_id, $agent_ids)) {
            echo "Error: Usuario no válido para asignar.";
            exit();
        }
    }

    // Obtener agentes actualmente asignados del usuario
    $queryAssignedAgents = "SELECT user_id FROM ticket_assignments WHERE ticket_id = :ticket_id AND user_id IN (SELECT user_id FROM users WHERE unidad = :unidad)";
    $stmtAssignedAgents = $conn->prepare($queryAssignedAgents);
    $stmtAssignedAgents->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
    $stmtAssignedAgents->bindValue(':unidad', $unidad_usuario, PDO::PARAM_STR);
    $stmtAssignedAgents->execute();
    $current_assigned_agent_ids = $stmtAssignedAgents->fetchAll(PDO::FETCH_COLUMN);

    // Calcular agentes a agregar y eliminar
    $agents_to_add = array_diff($assigned_to_user_ids, $current_assigned_agent_ids);
    $agents_to_remove = array_diff($current_assigned_agent_ids, $assigned_to_user_ids);

    // Eliminar asignaciones existentes para los agentes a remover
    if (!empty($agents_to_remove)) {
        $deleteQuery = "DELETE FROM ticket_assignments WHERE ticket_id = :ticket_id AND user_id = :user_id";
        $stmtDelete = $conn->prepare($deleteQuery);
        foreach ($agents_to_remove as $removed_user_id) {
            $stmtDelete->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
            $stmtDelete->bindValue(':user_id', $removed_user_id, PDO::PARAM_INT);
            $stmtDelete->execute();

            // Registrar la actividad de desasignación
            // Obtener el nombre del agente desasignado
            $stmtUser = $conn->prepare("SELECT name FROM users WHERE user_id = :user_id");
            $stmtUser->bindValue(':user_id', $removed_user_id, PDO::PARAM_INT);
            $stmtUser->execute();
            $removed_user_name = $stmtUser->fetchColumn();

            $activity_description = $user_name . " desasignó el ticket " . $ticket_id . " de " . $removed_user_name;
            $stmtActivity = $conn->prepare("INSERT INTO ticket_activities (ticket_id, user_id, unidad, activity_type, description, activity_date)
                                            VALUES (:ticket_id, :user_id, :unidad, 'unassignment', :description, NOW())");
            $stmtActivity->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
            $stmtActivity->bindValue(':user_id', $user_id, PDO::PARAM_INT); // Quien realizó la acción
            $stmtActivity->bindValue(':unidad', $unidad_usuario, PDO::PARAM_STR);
            $stmtActivity->bindValue(':description', $activity_description, PDO::PARAM_STR);
            $stmtActivity->execute();
        }
    }

    // Insertar nuevas asignaciones
    if (!empty($agents_to_add)) {
        $insertQuery = "INSERT INTO ticket_assignments (ticket_id, user_id, assignment_date) VALUES (:ticket_id, :user_id, NOW())";
        $stmtInsert = $conn->prepare($insertQuery);
        foreach ($agents_to_add as $assigned_user_id) {
            $stmtInsert->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
            $stmtInsert->bindValue(':user_id', $assigned_user_id, PDO::PARAM_INT);
            $stmtInsert->execute();

            // Registrar la actividad de asignación
            // Obtener el nombre del agente asignado
            $stmtUser = $conn->prepare("SELECT name FROM users WHERE user_id = :user_id");
            $stmtUser->bindValue(':user_id', $assigned_user_id, PDO::PARAM_INT);
            $stmtUser->execute();
            $assigned_user_name = $stmtUser->fetchColumn();

            $activity_description = $user_name . " asignó el ticket " . $ticket_id . " a " . $assigned_user_name;
            $stmtActivity = $conn->prepare("INSERT INTO ticket_activities (ticket_id, user_id, unidad, activity_type, description, activity_date)
                                            VALUES (:ticket_id, :user_id, :unidad, 'assignment', :description, NOW())");
            $stmtActivity->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
            $stmtActivity->bindValue(':user_id', $user_id, PDO::PARAM_INT); // Quien realizó la acción
            $stmtActivity->bindValue(':unidad', $unidad_usuario, PDO::PARAM_STR);
            $stmtActivity->bindValue(':description', $activity_description, PDO::PARAM_STR);
            $stmtActivity->execute();
        }
    }

    // Redirigir
    header("Location: ../index.php");
    exit();
}
?>
