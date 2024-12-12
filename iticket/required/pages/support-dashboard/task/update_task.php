<?php
include '../../../bdc/conex.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];
    $unidad_usuario = $_SESSION['unidad'];

    // Verificar que la tarea pertenece a la unidad del usuario
    $stmtCheck = $conn->prepare("SELECT * FROM unit_tasks WHERE task_id = :task_id AND unidad = :unidad");
    $stmtCheck->bindParam(':task_id', $task_id);
    $stmtCheck->bindParam(':unidad', $unidad_usuario);
    $stmtCheck->execute();
    $task = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Tarea no encontrada.']);
        exit;
    }

    if (isset($_POST['completed'])) {
        // Actualizar el estado de completado
        $completed = $_POST['completed'] == 1 ? 1 : 0;
        $stmtUpdate = $conn->prepare("UPDATE unit_tasks SET completed = :completed, updated_at = NOW() WHERE task_id = :task_id");
        $stmtUpdate->bindParam(':completed', $completed);
        $stmtUpdate->bindParam(':task_id', $task_id);
        if ($stmtUpdate->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la tarea.']);
        }
    } elseif (isset($_POST['text'])) {
        // Actualizar el texto de la tarea
        $text = trim($_POST['text']);
        if ($text === '') {
            echo json_encode(['success' => false, 'message' => 'La tarea no puede estar vacía.']);
            exit;
        }
        $stmtUpdate = $conn->prepare("UPDATE unit_tasks SET description = :description, updated_at = NOW() WHERE task_id = :task_id");
        $stmtUpdate->bindParam(':description', $text);
        $stmtUpdate->bindParam(':task_id', $task_id);
        if ($stmtUpdate->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la tarea.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    }
    exit;
}
?>
