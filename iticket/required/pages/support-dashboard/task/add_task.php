<?php
include '../../../bdc/conex.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
    $task = $_POST['task'];
    $unidad_usuario = $_SESSION['unidad'];

    // Validar y sanitizar la entrada
    $task = trim($task);
    if ($task === '') {
        echo json_encode(['success' => false, 'message' => 'La tarea no puede estar vacía.']);
        exit;
    }

    // Insertar la tarea en la base de datos
    $stmt = $conn->prepare("INSERT INTO unit_tasks (unidad, description, completed, created_at) VALUES (:unidad, :description, 0, NOW())");
    $stmt->bindParam(':unidad', $unidad_usuario);
    $stmt->bindParam(':description', $task);
    if ($stmt->execute()) {
        $task_id = $conn->lastInsertId();
        echo json_encode(['success' => true, 'task_id' => $task_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar la tarea.']);
    }
    exit;
}
?>
