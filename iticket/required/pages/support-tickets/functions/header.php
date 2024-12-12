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

    <style>
        /* Estilos para el menú desplegable añadir a style.css */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            max-height: 220px;
            overflow-y: auto;
        }

        .dropdown-content.show {
            display: block;
        }

        .hidden {
            display: none;
        }
    </style>
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
                            <a href="../support-dashboard/" class="flex items-center justify-center md:justify-start space-x-2 p-2 pt-0 pb-0 text-gray-600 rounded transition-colors group">
                                <i class="fas fa-layer-group text-gray-500 group-hover:text-gray-600"></i>
                                <span class="text-gray-500 group-hover:text-gray-600 font-medium text-sm">Panel</span>

                            </a>
                        </li>
                        <li>
                            <a href="../support-tickets/" class="flex items-center justify-center md:justify-start space-x-2 p-2 text-gray-600 rounded transition-colors group">
                                <i class="fas fa-ticket-alt text-gray-600 group-hover:text-gray-600"></i>
                                <span class="text-gray-600 group-hover:text-gray-600 text-sm">Tickets</span>
                            </a>
                        </li>
                        <?php if ($role == 'admin'): ?>
                            <li>
                                <a href="../stadistics" class="flex items-center justify-center md:justify-start space-x-2 p-2 pt-0 pb-0 text-gray-600 rounded transition-colors group">
                                    <i class="fas fa-chart-pie text-gray-500 group-hover:text-gray-600"></i>
                                    <span class="text-gray-500 group-hover:text-gray-600 font-medium text-sm">Estadisticas</span>
                                </a>
                            </li>
                        <?php endif; ?>
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