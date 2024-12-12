<?php
include '../../bdc/conex.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../../../index.php");
    exit();
}

// Obtener información de sesión
$username = $_SESSION['username'];
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Usuario';
$user_id = $_SESSION['user_id'];

// Configuración de paginación
$limit = 8; // Tickets por página
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$page = max($page, 1); // Asegura que la página sea al menos 1
$offset = ($page - 1) * $limit;

// Manejo de búsqueda
$search = '';
$whereClause = 'WHERE st.create_by_user_id = :user_id';

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if ($search !== '') {
        $whereClause .= ' AND (st.title LIKE :search OR st.ticket_id = :ticket_id)';
    }
}

try {
    // Contar total de tickets del usuario
    $countQuery = "SELECT COUNT(*) FROM support_tickets st $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    if ($search !== '') {
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':ticket_id', (int)$search, PDO::PARAM_INT);
    }
    $stmt->execute();
    $total_tickets = $stmt->fetchColumn();

    // Calcular total de páginas
    $total_pages = ceil($total_tickets / $limit);

    // Obtener tickets para la página actual con asignaciones agrupadas
    $query = "  SELECT st.*, GROUP_CONCAT(u.name SEPARATOR ', ') AS assigned_to_names
    FROM support_tickets st LEFT JOIN ticket_assignments ta ON st.ticket_id = ta.ticket_id
    LEFT JOIN users u ON ta.user_id = u.user_id $whereClause
    GROUP BY st.ticket_id ORDER BY st.creation_date DESC LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    if ($search !== '') {
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':ticket_id', (int)$search, PDO::PARAM_INT);
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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iTicket | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../../favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Righteous&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <body class="bg-gray-100 flex h-screen">
        <div class="flex h-full">
            <!-- Sidebar -->
            <div id="sidebar" class="fixed inset-0 transform -translate-x-full transition-transform duration-300 ease-in-out z-50 md:relative md:translate-x-0 md:inset-auto md:h-full md:w-55 bg-white p-5 flex flex-col">
                <!-- Contenido superior -->
                <div class="flex flex-col items-center md:items-start">
                    <!-- Botón de cierre para móviles -->
                    <div class="flex justify-end w-full md:hidden">
                        <button id="close-sidebar" class="text-gray-600 focus:outline-none">
                            <i class="fas fa-bars text-2xl"></i>
                        </button>
                    </div>
                    <!-- Logo y mensaje de bienvenida -->
                    <h2 class="text-4xl text-gray-700 text-center md:text-left">iticket</h2>
                    <p class="text-xs text-gray-500 mt-0 text-center md:text-left">Bienvenido, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
                    <hr class="border-t border-gray-300 mt-2 mb-0 w-4/5 md:w-full">
                    <!-- Enlaces de navegación -->
                    <ul class="space-y-2 mt-4">
                        <li>
                            <a href="../user/" class="flex items-center justify-center md:justify-start space-x-2 p-2 pt-0 pb-0 text-gray-600 rounded transition-colors group">
                                <i class="fas fa-layer-group text-gray-500 group-hover:text-gray-600"></i>
                                <span class="text-gray-500 group-hover:text-gray-600 font-medium text-sm">Nuevo Ticket</span>

                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex items-center justify-center md:justify-start space-x-2 p-2 text-gray-600 rounded transition-colors group">
                                <i class="fas fa-ticket-alt text-gray-600 group-hover:text-gray-600"></i>
                                <span class="text-gray-600 group-hover:text-gray-600 text-sm">Mis Tickets</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <!-- Botón Cerrar sesión -->
                <div class="mt-auto mb-4 flex justify-center md:justify-start">
                    <a href="../../functions/logout.php" class="flex items-center space-x-2 group">
                        <i class="fas fa-sign-out-alt text-gray-500 group-hover:text-gray-600"></i>
                        <span class="text-gray-500 group-hover:text-gray-600">Cerrar sesión</span>
                    </a>
                </div>
            </div>

        </div>
        <div id="main-content" class="flex-1 flex flex-col">
            <!-- Encabezado -->
            <div class="bg-white flex items-center p-5 border-b">
                <!-- Ícono de menú para móviles -->
                <button id="menu-toggle" class="text-gray-600 focus:outline-none mr-3 md:hidden">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <h1 class="text-xl font-regular text-gray-700">Mis Tickets</h1>
            </div>
            <!-- DEBAJO DE PANEL MIS TICKETS -->
            <!-- Barra de búsqueda -->
            <div class="p-5 pb-0">
                <form method="GET" action="" class="mb-4">
                    <div class="flex items-center">
                        <input type="text" name="search" placeholder="Buscar por título o N° de ticket" value="<?php echo htmlspecialchars($search); ?>" class="w-full p-2 border border-gray-300 rounded-l">
                        <button type="submit" class="bg-gray-700 text-white p-2 rounded-r hover:bg-gray-800">
                            Buscar
                        </button>
                    </div>
                </form>
            </div>
            <!-- Tickets en formato de tarjeta -->
            <div class="p-5 overflow-auto pt-0">
                <?php if (count($tickets) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($tickets as $ticket): ?>
                            <a href="view-ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="p-2">
                                <div class="bg-white rounded-lg shadow-md p-5 flex items-center">
                                    <!-- Classification -->
                                    <div class="w-16 flex-shrink-0 text-center">
                                        <?php
                                        $classification = strtolower($ticket['classification']);
                                        $colorClass = '';
                                        switch ($classification) {
                                            case 'urgente':
                                                $colorClass = 'bg-red-500';
                                                break;
                                            case 'alta':
                                                $colorClass = 'bg-yellow-500';
                                                break;
                                            case 'media':
                                                $colorClass = 'bg-blue-500';
                                                break;
                                            case 'baja':
                                                $colorClass = 'bg-green-500';
                                                break;
                                            default:
                                                $colorClass = 'bg-gray-500';
                                        }
                                        ?>
                                        <span class="inline-block w-3 h-3 rounded-full <?php echo $colorClass; ?>"></span>
                                    </div>

                                    <!-- Initial of User Name -->
                                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                        <span class="text-xl font-bold text-gray-600">
                                            <?php echo strtoupper(substr($name, 0, 1)); ?>
                                        </span>
                                    </div>

                                    <!-- Ticket Details -->
                                    <div class="ml-4 flex-1">
                                        <h1 class="text-lg font-regular text-gray-700">
                                            <?php echo htmlspecialchars($ticket['title']); ?> #<?php echo $ticket['ticket_id']; ?>
                                        </h1>
                                        <p class="text-gray-600">
                                            <?php
                                            if (!empty($ticket['assigned_to_names'])) {
                                                echo 'Asignado a: ' . htmlspecialchars($ticket['assigned_to_names']);
                                            } else {
                                                echo 'Aún no fue asignado.';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <!-- Status -->
                                    <div class="text-right">
                                        <span class="block font-regular">
                                            <?php echo htmlspecialchars($ticket['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">No tienes tickets registrados.</p>
                <?php endif; ?>

            </div>
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-left mt-6 ml-6">
                    <nav class="inline-flex space-x-1">
                        <!-- Botón de página anterior -->
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-3 py-1 bg-white border border-gray-300 text-gray-500 hover:bg-gray-100">Anterior</a>
                        <?php endif; ?>

                        <!-- Números de página -->
                        <?php
                        $range = 2; // Rango de páginas a mostrar alrededor de la página actual
                        $start = max(1, $page - $range);
                        $end = min($total_pages, $page + $range);
                        ?>
                        <?php if ($start > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="px-3 py-1 bg-white border border-gray-300 text-gray-500 hover:bg-gray-100">1</a>
                            <?php if ($start > 2): ?>
                                <span class="px-3 py-1">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-3 py-1 bg-gray-800 border border-gray-300 text-white"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="px-3 py-1 bg-white border border-gray-300 text-gray-500 hover:bg-gray-100"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($end < $total_pages): ?>
                            <?php if ($end < $total_pages - 1): ?>
                                <span class="px-3 py-1">...</span>
                            <?php endif; ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="px-3 py-1 bg-white border border-gray-300 text-gray-500 hover:bg-gray-100"><?php echo $total_pages; ?></a>
                        <?php endif; ?>

                        <!-- Botón de página siguiente -->
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-3 py-1 bg-white border border-gray-300 text-gray-500 hover:bg-gray-100">Siguiente</a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>

        </div>
        <script src="js/toggle-menu.js" defer></script>
    </body>

</html>