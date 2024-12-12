<?php
include '../../../bdc/conex.php';
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit();
}
$conn->exec("SET time_zone = '+00:00'"); 

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$unidad_usuario = $_SESSION['unidad'] ?? null;

$current_time = date('Y-m-d H:i:s');
$time_window_start = date('Y-m-d H:i:s', strtotime('-15 seconds'));

$messages = [];

try {

    if ($unidad_usuario === 'u_helpdesk' || $role === 'admin') {
        $sql = "SELECT COUNT(*) FROM support_tickets WHERE creation_date BETWEEN :time_window_start AND :current_time";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':time_window_start', $time_window_start, PDO::PARAM_STR);
        $stmt->bindValue(':current_time', $current_time, PDO::PARAM_STR);
        $stmt->execute();
        $new_tickets = $stmt->fetchColumn();

        if ($new_tickets > 0) {
            $messages[] = "Se encontraron nuevos tickets para asignar a una unidad, haga clic para refrescar";
        }
    }

    if ($unidad_usuario && $unidad_usuario !== 'u_helpdesk') {
        $sql = "SELECT COUNT(*) FROM ticket_unit_assignments
                WHERE unidad = :unidad_usuario AND assigned_at BETWEEN :time_window_start AND :current_time AND unassigned_at IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':unidad_usuario', $unidad_usuario, PDO::PARAM_STR);
        $stmt->bindValue(':time_window_start', $time_window_start, PDO::PARAM_STR);
        $stmt->bindValue(':current_time', $current_time, PDO::PARAM_STR);
        $stmt->execute();
        $new_unit_tickets = $stmt->fetchColumn();

        if ($new_unit_tickets > 0) {
            $messages[] = "Se asignó un nuevo ticket para su unidad, haga clic para refrescar";
        }
    }

    $sql = "SELECT COUNT(*) FROM ticket_assignments
            WHERE user_id = :user_id AND assignment_date BETWEEN :time_window_start AND :current_time";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':time_window_start', $time_window_start, PDO::PARAM_STR);
    $stmt->bindValue(':current_time', $current_time, PDO::PARAM_STR);
    $stmt->execute();
    $new_agent_tickets = $stmt->fetchColumn();

    if ($new_agent_tickets > 0) {
        $messages[] = "Se asignó un nuevo ticket para usted, haga clic para refrescar";
    }

    echo json_encode(['status' => 'success', 'messages' => $messages]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al consultar la base de datos']);
}
