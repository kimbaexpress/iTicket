<?php
include '../../../bdc/conex.php';
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$unidad_usuario = $_SESSION['unidad'] ?? null;

// Obtener el último timestamp registrado en la base de datos
$stmt = $conn->prepare("SELECT last_check FROM users WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$last_check = $stmt->fetchColumn();

// Si last_check es null, establecer una fecha por defecto reciente
if (!$last_check) {
    $last_check = date('Y-m-d H:i:s', strtotime('-1 day'));
}

$current_time = date('Y-m-d H:i:s');

// Inicializar array de mensajes
$messages = [];

// Conectar a la base de datos
try {
    // 1. Para usuarios de 'u_helpdesk': Nuevos tickets creados
    if ($unidad_usuario === 'u_helpdesk') {
        $sql = "SELECT COUNT(*) FROM support_tickets WHERE creation_date > :last_check";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':last_check', $last_check, PDO::PARAM_STR);
        $stmt->execute();
        $new_tickets = $stmt->fetchColumn();

        if ($new_tickets > 0) {
            $messages[] = "Se encontraron nuevos tickets para asignar a una unidad, haga clic para refrescar";
        }
    }

    // 2. Para unidades asignadas: Nuevos tickets asignados a la unidad del usuario
    if ($unidad_usuario && $unidad_usuario !== 'u_helpdesk') {
        $sql = "SELECT COUNT(*) FROM ticket_unit_assignments
                WHERE unidad = :unidad_usuario AND assigned_at > :last_check AND unassigned_at IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':unidad_usuario', $unidad_usuario, PDO::PARAM_STR);
        $stmt->bindValue(':last_check', $last_check, PDO::PARAM_STR);
        $stmt->execute();
        $new_unit_tickets = $stmt->fetchColumn();

        if ($new_unit_tickets > 0) {
            $messages[] = "Se asignó un nuevo ticket para su unidad, haga clic para refrescar";
        }
    }

    // 3. Para agentes: Nuevos tickets asignados al agente
    $sql = "SELECT COUNT(*) FROM ticket_assignments
            WHERE user_id = :user_id AND assignment_date > :last_check";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':last_check', $last_check, PDO::PARAM_STR);
    $stmt->execute();
    $new_agent_tickets = $stmt->fetchColumn();

    if ($new_agent_tickets > 0) {
        $messages[] = "Se asignó un nuevo ticket para usted, haga clic para refrescar";
    }

    // No actualizar last_check aquí

    // Devolver los mensajes como JSON
    if (!empty($messages)) {
        echo json_encode(['status' => 'success', 'messages' => $messages]);
    } else {
        echo json_encode(['status' => 'success', 'messages' => []]);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al consultar la base de datos']);
}
