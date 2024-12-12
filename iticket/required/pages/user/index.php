<?php
include '../../bdc/conex.php'; 
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
if (!isset($_SESSION['username'])) {
    header("Location: ../../../index.php");
    exit();
}

$username = $_SESSION['username'];
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Usuario'; // Fallback a 'Usuario' si no se encontró el nombre
$user_id = $_SESSION['user_id'];
$success = isset($_GET['success']) ? $_GET['success'] : null;
$ticket_id = isset($_GET['ticket_id']) ? $_GET['ticket_id'] : null;
$unidad = isset($_SESSION['unidad']) ? $_SESSION['unidad'] : 'Aca va la unidad';
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <p class="text-xs text-gray-500 mt-0 text-center md:text-left">Bienvenido, <?php echo htmlspecialchars($name); ?></p>
                <hr class="border-t border-gray-300 mt-2 mb-0 w-4/5 md:w-full">
                <!-- Enlaces de navegación -->
                <ul class="space-y-2 mt-4">
                    <li>
                        <a href="#" class="flex items-center justify-center md:justify-start space-x-2 p-2 pt-0 pb-0 text-gray-600 rounded transition-colors group">
                            <i class="fas fa-layer-group text-gray-600 group-hover:text-gray-600"></i>
                            <span class="text-gray-600 group-hover:text-gray-600 text-sm">Nuevo Ticket</span>
                        </a>
                    </li>
                    <li>
                        <a href="../user-tickets/" class="flex items-center justify-center md:justify-start space-x-2 p-2 text-gray-600 rounded transition-colors group">
                            <i class="fas fa-ticket-alt text-gray-500 group-hover:text-gray-600"></i>
                            <span class="text-gray-500 group-hover:text-gray-600 font-medium text-sm">Mis Tickets</span>
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

    <!-- INICIO DEL CODIGO DESPUES DE LA SIDEBAR -->

    <div id="main-content" class="flex-1 flex flex-col">
        <!-- Encabezado -->
        <div class="bg-white flex items-center p-5 border-b">
            <!-- Ícono de menú para móviles -->
            <button id="menu-toggle" class="text-gray-600 focus:outline-none mr-3 md:hidden">
                <i class="fas fa-bars text-2xl"></i>
            </button>
            <h1 class="text-xl font-regular text-gray-700">Generar un Nuevo Ticket</h1>
        </div>

        <div class="flex-1 flex flex-col items-center justify-center p-5 bg-gray-100">
            <div class="w-full max-w-4xl p-5 bg-white rounded shadow-lg">
                <form id="supportForm" action="functions/subida.php" method="POST" class="space-y-4">
                    <p class="text-red-500 text-xs">* Asegúrese de completar correctamente los campos del formulario, de lo contrario su ticket quedará rechazado.</p>
                    <div>
                        <label for="solicitante" class="block font-medium text-gray-700">Solicitante<label class="text-red-500"> *</label></label>
                        <input type="text" id="solicitante" name="solicitante" value="<?php echo htmlspecialchars($user_id); ?>" required readonly class="hidden mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <input type="text" id="solicitante_nombre" name="solicitante_nombre" value="<?php echo htmlspecialchars($name); ?>" required readonly class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="asunto" class="block font-medium text-gray-700">Asunto de la Incidencia<label class="text-red-500"> *</label></label>
                        <input type="text" id="asunto" name="asunto" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="sector" class="block font-medium text-gray-700">Sector<label class="text-red-500"> *</label></label>
                        <input type="text" id="sector" name="sector" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Pregunta Condicional para "interno" -->
                    <div id="interno-section" class="mt-4">
                        <p class="block font-medium text-gray-700">¿Usted tiene número de interno?</p>
                        <div class="flex space-x-4 mt-2">
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio" name="tiene_interno" value="si">
                                <span class="ml-2">Sí</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio" name="tiene_interno" value="no" checked>
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <!-- Campo "interno" inicialmente oculto -->
                    <div id="interno-field" class="mt-4 hidden">
                        <label for="interno" class="block font-medium text-gray-700">Número de Interno del Sector</label>
                        <input type="text" id="interno" name="interno" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="descripcion" class="block font-medium text-gray-700 mb-2">Comente de forma detallada el inconveniente<label class="text-red-500"> *</label></label>
                        <textarea id="descripcion" name="descripcion" rows="4" required class="resize-y h-24 max-h-48 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 overflow-auto"></textarea>
                        <div class="text-right text-xs text-gray-500 mt-1" id="descripcion-counter">0/200</div>
                    </div>

                    <div class="flex justify-center">
                        <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Enviar Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Elemento oculto para pasar datos al JavaScript -->
        <?php if ($success == '1' && $ticket_id): ?>
            <div id="ticket-data" data-success="1" data-ticket-id="<?php echo htmlspecialchars($ticket_id); ?>"></div>
        <?php elseif ($success === '0'): ?>
            <div id="ticket-data" data-success="0"></div>
        <?php endif; ?>

        <!-- Incluir el archivo alerts.js -->
        <script src="js/alert.js"></script>
        <script src="js/toggle-menu.js" defer></script>
        <script src="js/form-validation.js"></script>
    </div>

</body>

</html>