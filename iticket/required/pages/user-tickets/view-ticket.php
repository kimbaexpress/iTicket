<?php
include '../../bdc/conex.php'; // Ajusta la ruta según sea necesario
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$ticket_id = $_GET['ticket_id'] ?? 0; // Asegúrate de validar y sanitizar este valor correctamente

try {
    // Modificar la consulta para incluir la verificación de soporte o asignación
    $sql = "SELECT st.*, u.name as creator_name, u.role as creator_role
            FROM support_tickets st
            JOIN users u ON st.create_by_user_id = u.user_id
            WHERE st.ticket_id = :ticket_id AND (st.create_by_user_id = :user_id OR :user_role = 'support' OR :user_role = 'admin')";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_role', $user_role, PDO::PARAM_STR);
    $stmt->execute();

    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo "No tienes permiso para ver este ticket o no existe.";
        exit();
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Obtener las respuestas del ticket
try {
    $stmt = $conn->prepare("SELECT tr.*, u.name as responder_name
                            FROM ticket_responses tr
                            INNER JOIN users u ON tr.responder_id = u.user_id
                            WHERE tr.ticket_id = :ticket_id
                            ORDER BY tr.response_date ASC");
    $stmt->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
    $stmt->execute();
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error al obtener las respuestas: " . $e->getMessage();
    exit();
}

// Determine if the user can update the status
$can_update_status = false;
if ($user_role === 'support' || $user_role === 'admin' ) {
    $can_update_status = true;
}

// Fetch the current status of the ticket
$current_status = $ticket['status'];
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
                            <a href="../user-tickets/index.php" class="flex items-center justify-center md:justify-start space-x-2 p-2 text-gray-600 rounded transition-colors group">
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
                <h1 class="text-xl font-regular text-gray-700">Visualización Ticket</h1>
            </div>
            <!-- DEBAJO DE PANEL MIS TICKETS -->
            <!-- Contenido principal -->
            <div class="overflow-y-auto max-h-200" id="contentArea">
                <div class="container mx-auto p-5 pt-4 pb-0">

                    <h1 class="text-2xl font-semibold mb-4"><?php echo htmlspecialchars($ticket['title']); ?> #<?php echo htmlspecialchars($ticket_id); ?></h1>
                    <div class="bg-white rounded-lg shadow-md p-5 mb-4">
                        <!-- Información del creador del ticket -->
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                                <span class="text-xl font-bold text-gray-600">
                                    <?php echo strtoupper(substr($ticket['creator_name'], 0, 1)); ?>
                                </span>
                            </div>
                            <div class="ml-4">
                                <p class="text-gray-800 font-semibold"><?php echo htmlspecialchars($ticket['creator_name']); ?></p>
                                <p class="text-gray-600 text-sm"><?php echo date('d/m/Y H:i', strtotime($ticket['creation_date'])); ?></p>
                            </div>
                        </div>
                        <!-- Descripción del ticket -->
                        <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p><br>
                        <p class="text-gray-800">Sector: <?php echo htmlspecialchars($ticket['sector']); ?><br> </p>
                        <p class="text-gray-800">Interno: <?php echo htmlspecialchars($ticket['internal_number']); ?><br> </p>

                    </div>
                </div>

                <!-- Respuestas del ticket -->
                <div class="container mx-auto p-5 pt-0 pb-0">
                    <?php if (count($responses) > 0): ?>
                        <?php foreach ($responses as $response): ?>
                            <div class="bg-white rounded-lg shadow-md p-5 mb-4">
                                <!-- Información del respondedor -->
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                                        <span class="text-xl font-bold text-gray-600">
                                            <?php echo strtoupper(substr($response['responder_name'], 0, 1)); ?>
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-gray-800 font-semibold"><?php echo htmlspecialchars($response['responder_name']); ?></p>
                                        <p class="text-gray-600 text-sm"><?php echo date('d/m/Y H:i', strtotime($response['response_date'])); ?></p>
                                    </div>
                                </div>
                                <!-- Texto de la respuesta -->
                                <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($response['response_text'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-600 ml-4">No hay respuestas aún.</p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Formulario para agregar una respuesta -->
            <div class="container mx-auto p-5">
                <div class="bg-white rounded-lg shadow-md p-5 mb-4">
                    <h1 class="text-xl font-regular mb-4">Agregar una respuesta</h1>

                    <form action="functions/agregar_respuesta.php" method="POST">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                        <div class="mb-4">
                            <textarea name="response_text" rows="4" required class="w-full p-2 border border-gray-300 rounded" placeholder="Escribe tu respuesta aquí..."></textarea>
                        </div>

                        <div class="flex flex-row"> <!-- COLOCAR SELECT CAMBIADOR DE STATUS A LA DERECHA DEL BOTON ENVIAR RSTA -->
                        <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">
                            Enviar Respuesta
                        </button>
                    </form>

                    <?php if ($can_update_status): ?>
                        <form action="functions/actualizar_estado.php" method="POST">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                            <select name="status" class="w-38 bg-white border border-gray-300 rounded px-4 py-2 leading-tight focus:outline-none focus:border-blue-500 ml-2" onchange="this.form.submit()">
                                <option value="Pendiente" <?php if ($current_status == 'Pendiente') echo 'selected'; ?>>Pendiente</option>
                                <option value="En Proceso" <?php if ($current_status == 'En Proceso') echo 'selected'; ?>>En Proceso</option>
                                <option value="Resuelto" <?php if ($current_status == 'Resuelto') echo 'selected'; ?>>Resuelto</option>
                            </select>
                        </form>
                    <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- DEBAJO DE PANEL MIS TICKETS -->
        </div>
        <script>
    window.onload = function() {
        var contentArea = document.getElementById('contentArea');
        contentArea.scrollTop = contentArea.scrollHeight;
    };
</script>
<script src="js/toggle-menu.js" defer></script>
    </body>

</html>