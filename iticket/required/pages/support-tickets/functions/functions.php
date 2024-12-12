<?php
include '../../bdc/conex.php'; // Conexión a la base de datos
date_default_timezone_set('America/Argentina/Buenos_Aires');
$conn->exec("SET time_zone = '-03:00'");


session_start();

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['coordinator', 'support', 'admin'])) {
    header("Location: ../../../index.php");
    exit();
}

// Obtener información de sesión
$username = $_SESSION['username'];
$name = $_SESSION['name'] ?? 'Usuario';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$unidad_usuario = isset($_SESSION['unidad']) ? $_SESSION['unidad'] : 'Aca va la unidad';

// Configuración de paginación
$limit = 5; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$page = max($page, 1); 
$offset = ($page - 1) * $limit;

// Manejo de búsqueda
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}
$filter_unit = isset($_GET['filter_unit']) ? $_GET['filter_unit'] : 'todos';

// Validar el filtro
$valid_filters = ['todos', 'unidad', 'pendiente'];
if (!in_array($filter_unit, $valid_filters)) {
    $filter_unit = 'todos';
}

try {
    $params = [];

    // Construir el WHERE clause basado en el rol y el filtro seleccionado
    if ($role === 'admin') {
        // Administradores pueden ver todos los tickets
        $whereClause = "WHERE 1=1";

        if ($filter_unit == 'unidad') {
            // Si el administrador selecciona 'Unidad', filtrar por su unidad
            $whereClause .= " AND EXISTS (
                SELECT 1 FROM ticket_unit_assignments tua
                WHERE tua.ticket_id = st.ticket_id
                  AND tua.unidad = :unidad_usuario
                  AND tua.unassigned_at IS NULL
            )";
            $params[':unidad_usuario'] = $unidad_usuario;
        } elseif ($filter_unit == 'pendiente') {
            // Si el administrador selecciona 'Pendiente', filtrar por estado 'Pendiente'
            $whereClause .= " AND st.status = 'Pendiente'";
        }
    } else {
        // Usuarios no administradores solo ven tickets asignados a su unidad
        $whereClause = "WHERE EXISTS (
            SELECT 1 FROM ticket_unit_assignments tua
            WHERE tua.ticket_id = st.ticket_id
              AND tua.unidad = :unidad_usuario
              AND tua.unassigned_at IS NULL
        )";
        $params[':unidad_usuario'] = $unidad_usuario;

        if ($filter_unit == 'pendiente') {
            // Si el usuario selecciona 'Pendiente', filtrar también por estado 'Pendiente'
            $whereClause .= " AND st.status = 'Pendiente'";
        }
    }

    // Aplicar búsqueda si se proporcionó
    if ($search !== '') {
        $whereClause .= " AND (st.description LIKE :search OR st.ticket_id = :ticket_id)";
        $params[':search'] = '%' . $search . '%';
        $params[':ticket_id'] = (int)$search;
    }

    // Contar total de tickets
    $countQuery = "SELECT COUNT(*) FROM support_tickets st $whereClause";
    $stmt = $conn->prepare($countQuery);
    // Vincular parámetros
    foreach ($params as $key => $value) {
        if ($key === ':ticket_id') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }
    $stmt->execute();
    $total_tickets = $stmt->fetchColumn();

    // Calcular total de páginas
    $total_pages = ceil($total_tickets / $limit);

    // Obtener tickets para la página actual
    $query = "SELECT st.*, c.name as creator_name
              FROM support_tickets st
              LEFT JOIN users c ON st.create_by_user_id = c.user_id
              $whereClause
              ORDER BY st.creation_date DESC
              LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($query);
    // Vincular parámetros
    foreach ($params as $key => $value) {
        if ($key === ':ticket_id') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejo de errores
    echo "Error: " . $e->getMessage();
    exit();
}

$last_ticket_id = 0;
if (count($tickets) > 0) {
    $last_ticket_id = $tickets[0]['ticket_id']; // Suponiendo que están ordenados de más reciente a más antiguo
}
