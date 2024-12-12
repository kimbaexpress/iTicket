<?php
include '../../../bdc/conex.php';
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

try {
    $stmt = $conn->prepare("UPDATE users SET last_check = :current_time WHERE user_id = :user_id");
    $stmt->bindValue(':current_time', $current_time, PDO::PARAM_STR);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar last_check']);
}
?>
