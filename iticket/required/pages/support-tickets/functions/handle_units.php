<?php
include '../../../bdc/conex.php';
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
$conn->exec("SET time_zone = '-03:00'");

$unidad_usuario = $_SESSION['unidad'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$role = $_SESSION['role'];

if ($unidad_usuario === 'u_helpdesk' || $role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = $_POST['ticket_id'];
    $previous_units = isset($_POST['previous_units']) ? explode(',', $_POST['previous_units']) : [];
    $assigned_units = isset($_POST['assigned_units']) ? $_POST['assigned_units'] : [];

    // Filtrar valores vacíos en previous_units y assigned_units
    $previous_units = array_filter($previous_units, function ($value) {
        return !empty($value);
    });
    $assigned_units = array_filter($assigned_units, function ($value) {
        return !empty($value);
    });

    // Validar unidades
    $unidades_validas = ['u_helpdesk', 'u_soporte', 'u_desarrollo', 'u_seguridad', 's_tecnologia'];
    foreach (array_merge($previous_units, $assigned_units) as $unidad) {
        if (!in_array($unidad, $unidades_validas)) {
            echo "Error: Unidad no válida.";
            exit();
        }
    }

    // Arreglo para nombres amigables de unidades
    $unidadesAmigables = [
        'u_helpdesk' => 'Unidad Help Desk',
        'u_soporte' => 'Unidad Soporte',
        'u_desarrollo' => 'Unidad Desarrollo',
        'u_seguridad' => 'Unidad Seguridad',
        's_tecnologia' => 'Servicio de Tecnologia, comunicaciones y desarrollo'
    ];

    try {
        // Calcular unidades a agregar y eliminar
        $units_to_add = array_diff($assigned_units, $previous_units);
        $units_to_remove = array_diff($previous_units, $assigned_units);

        // Desasignar unidades y agentes asociados
        if (!empty($units_to_remove)) {
            foreach ($units_to_remove as $unidad) {
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

                // Registrar la actividad de desasignación de unidad
                $unidad_nombre = $unidadesAmigables[$unidad] ?? $unidad;
                $activity_description = $user_name . " desasignó el ticket " . $ticket_id . " de la unidad " . $unidad_nombre;
                $stmtActivity = $conn->prepare("INSERT INTO ticket_activities (ticket_id, user_id, unidad, activity_type, description, activity_date)
                                                VALUES (:ticket_id, :user_id, :unidad_usuario, 'unit_unassignment', :description, NOW())");
                $stmtActivity->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                $stmtActivity->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmtActivity->bindValue(':unidad_usuario', $unidad_usuario, PDO::PARAM_STR);
                $stmtActivity->bindValue(':description', $activity_description, PDO::PARAM_STR);
                $stmtActivity->execute();
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
                    $stmtReassign = $conn->prepare("UPDATE ticket_unit_assignments SET assigned_at = NOW(), unassigned_at = NULL WHERE ticket_id = :ticket_id AND unidad = :unidad");
                    $stmtReassign->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                    $stmtReassign->bindValue(':unidad', $unidad, PDO::PARAM_STR);
                    $stmtReassign->execute();
                } else {
                    // Insertar nueva asignación
                    $stmtInsert = $conn->prepare("INSERT INTO ticket_unit_assignments (ticket_id, unidad, assigned_at) VALUES (:ticket_id, :unidad, NOW())");
                    $stmtInsert->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':unidad', $unidad, PDO::PARAM_STR);
                    $stmtInsert->execute();
                }

                // Registrar la actividad de asignación de unidad
                $unidad_nombre = $unidadesAmigables[$unidad] ?? $unidad;
                $activity_description = $user_name . " asignó el ticket " . $ticket_id . " a la unidad " . $unidad_nombre;
                $stmtActivity = $conn->prepare("INSERT INTO ticket_activities (ticket_id, user_id, unidad, activity_type, description, activity_date)
                                                VALUES (:ticket_id, :user_id, :unidad_usuario, 'unit_assignment', :description, NOW())");
                $stmtActivity->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                $stmtActivity->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmtActivity->bindValue(':unidad_usuario', $unidad_usuario, PDO::PARAM_STR);
                $stmtActivity->bindValue(':description', $activity_description, PDO::PARAM_STR);
                $stmtActivity->execute();
            }
        }

        // Actualizar el campo updated_at en support_tickets
        $stmtUpdateTicket = $conn->prepare("UPDATE support_tickets SET updated_at = NOW() WHERE ticket_id = :ticket_id");
        $stmtUpdateTicket->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $stmtUpdateTicket->execute();

        // Redirigir después de la asignación/desasignación
        header("Location: ../index.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}
?>
