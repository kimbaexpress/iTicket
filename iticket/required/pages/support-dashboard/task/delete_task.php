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

    // Eliminar la tarea
    $stmtDelete = $conn->prepare("DELETE FROM unit_tasks WHERE task_id = :task_id");
    $stmtDelete->bindParam(':task_id', $task_id);
    if ($stmtDelete->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la tarea.']);
    }
    exit;
}
?>
