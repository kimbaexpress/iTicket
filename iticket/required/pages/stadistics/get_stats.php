<?php
include '../../bdc/conex.php'; // Ajusta la ruta según tu estructura

// Obtener los datos del formulario
$filterType = $_POST['filter_type'];
$filterValue = $_POST['filter_value'];
$startDate = $_POST['start_date'];
$endDate = $_POST['end_date'];

// Validar y sanitizar los datos según sea necesario

// Construir la consulta SQL
$params = [];
$sql = "SELECT status, COUNT(*) as total FROM support_tickets WHERE 1=1";

if (!empty($startDate)) {
    $sql .= " AND creation_date >= :start_date";
    $params[':start_date'] = $startDate;
}

if (!empty($endDate)) {
    $sql .= " AND creation_date <= :end_date";
    $params[':end_date'] = $endDate;
}

if ($filterType == 'unidad') {
    $sql .= " AND ticket_id IN (
        SELECT ticket_id FROM ticket_unit_assignments WHERE unidad = :unidad
    )";
    $params[':unidad'] = $filterValue;
} else {
    $sql .= " AND ticket_id IN (
        SELECT ticket_id FROM ticket_assignments WHERE user_id = :user_id
    )";
    $params[':user_id'] = $filterValue;
}

$sql .= " GROUP BY status";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar los datos para el gráfico
$labels = [];
$data = [];

foreach ($results as $row) {
    $labels[] = $row['status'];
    $data[] = $row['total'];
}

// Devolver los datos en formato JSON
echo json_encode([
    'labels' => $labels,
    'data' => $data
]);
?>
