<?php
include '../../../bdc/conex.php';
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

$unidad_usuario = $_SESSION['unidad'];

if ($unidad_usuario === 'u_helpdesk' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = $_POST['ticket_id'];
    $assigned_units = isset($_POST['assigned_units']) ? $_POST['assigned_units'] : [];

    // Validar unidades
    $unidades_validas = ['u_helpdesk', 'u_soporte', 'u_desarrollo', 'u_seguridad'];
    foreach ($assigned_units as $unidad) {
        if (!in_array($unidad, $unidades_validas)) {
            echo "Error: Unidad no válida para asignar.";
            exit();
        }
    }

    try {
        // Generar la fecha y hora actual en PHP
        $current_time = date('Y-m-d H:i:s');

        // Obtener unidades actualmente asignadas activas
        $stmtCurrentUnits = $conn->prepare("SELECT unidad FROM ticket_unit_assignments WHERE ticket_id = :ticket_id AND unassigned_at IS NULL");
        $stmtCurrentUnits->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $stmtCurrentUnits->execute();
        $current_units = $stmtCurrentUnits->fetchAll(PDO::FETCH_COLUMN);

        // Calcular unidades a agregar y eliminar
        $units_to_add = array_diff($assigned_units, $current_units);
        $units_to_remove = array_diff($current_units, $assigned_units);

        // Desasignar unidades y agentes asociados
        if (!empty($units_to_remove)) {
            foreach ($units_to_remove as $unidad) {
                // Desasignar unidad
                $stmtUnassign = $conn->prepare("UPDATE ticket_unit_assignments SET unassigned_at = :current_time WHERE ticket_id = :ticket_id AND unidad = :unidad AND unassigned_at IS NULL");
                $stmtUnassign->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                $stmtUnassign->bindValue(':unidad', $unidad, PDO::PARAM_STR);
                $stmtUnassign->bindValue(':current_time', $current_time);
                $stmtUnassign->execute();

                // Desasignar agentes de la unidad
                $stmtDeleteAgents = $conn->prepare("DELETE FROM ticket_assignments WHERE ticket_id = :ticket_id AND user_id IN (SELECT user_id FROM users WHERE unidad = :unidad)");
                $stmtDeleteAgents->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                $stmtDeleteAgents->bindValue(':unidad', $unidad, PDO::PARAM_STR);
                $stmtDeleteAgents->execute();
            }
        }

        // Asignar nuevas unidades
        if (!empty($units_to_add)) {
            foreach ($units_to_add as $unidad) {
                // Verificar si existe un registro desasignado previamente
                $stmtCheck = $conn->prepare("SELECT * FROM ticket_unit_assignments WHERE ticket_id = :ticket_id AND unidad = :unidad AND unassigned_at IS NOT NULL");
                $stmtCheck->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                $stmtCheck->bindValue(':unidad', $unidad, PDO::PARAM_STR);
                $stmtCheck->execute();
                $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    // Reasignar actualizando assigned_at y unassigned_at
                    $stmtReassign = $conn->prepare("UPDATE ticket_unit_assignments SET assigned_at = :current_time, unassigned_at = NULL WHERE ticket_id = :ticket_id AND unidad = :unidad");
                    $stmtReassign->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                    $stmtReassign->bindValue(':unidad', $unidad, PDO::PARAM_STR);
                    $stmtReassign->bindValue(':current_time', $current_time);
                    $stmtReassign->execute();
                } else {
                    // Insertar nueva asignación
                    $stmtInsert = $conn->prepare("INSERT INTO ticket_unit_assignments (ticket_id, unidad, assigned_at) VALUES (:ticket_id, :unidad, :assigned_at)");
                    $stmtInsert->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':unidad', $unidad, PDO::PARAM_STR);
                    $stmtInsert->bindValue(':assigned_at', $current_time);
                    $stmtInsert->execute();
                }
            }
        }

        // Actualizar el campo updated_at en support_tickets
        $stmtUpdateTicket = $conn->prepare("UPDATE support_tickets SET updated_at = :current_time WHERE ticket_id = :ticket_id");
        $stmtUpdateTicket->bindValue(':current_time', $current_time);
        $stmtUpdateTicket->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $stmtUpdateTicket->execute();

        // Redirigir después de la asignación
        header("Location: ../index.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}
?>
