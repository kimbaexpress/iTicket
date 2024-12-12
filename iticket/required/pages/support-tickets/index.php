<?php
include 'functions/functions.php';
include 'functions/header.php';
$role = $_SESSION['role'];
$unidades_validas = ['u_helpdesk', 'u_soporte', 'u_desarrollo', 'u_seguridad', 's_tecnologia'];
?>
<!-- Contenido -->
<div id="main-content" class="flex-1 flex flex-col">
    <!-- Encabezado -->
    <div class="bg-white flex items-center p-5 border-b">
        <!-- Ícono de menú para móviles -->
        <button id="menu-toggle" class="text-gray-600 focus:outline-none mr-3 md:hidden">
            <i class="fas fa-bars text-2xl"></i>
        </button>
        <h1 class="text-xl font-regular text-gray-700">Tickets</h1>
    </div>
    <!-- DEBAJO DE PANEL MIS TICKETS -->
    <!-- Barra de búsqueda -->
    <!-- Barra de búsqueda -->
    <div class="p-5 pb-0 relative">
        <form method="GET" action="" class="mb-4">
            <div class="flex items-center">
                <?php if ($role == 'admin'): ?>
                    <input type="text" name="search" placeholder="Buscar por título o N° de ticket" value="<?php echo htmlspecialchars($search); ?>" class="p-3 h-10 border border-gray-300 rounded-l flex-grow focus:outline-none focus:ring-0 focus:border-gray-300">
                    <select name="filter_unit" class="h-10 pl-2 border-t border-b border-gray-300 border-l-0 bg-white focus:outline-none focus:ring-0 focus:border-gray-300 leading-none">
                        <option value="todos" <?php echo ($filter_unit == 'todos') ? 'selected' : ''; ?>>Todos</option>
                        <option value="unidad" <?php echo ($filter_unit == 'unidad') ? 'selected' : ''; ?>>Unidad</option>
                        <option value="pendiente" <?php echo ($filter_unit == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    </select>
                    <button type="submit" class="h-10 pl-2 pr-2 bg-gray-700 text-white border border-gray-300 border-l-0 rounded-r hover:bg-gray-800 focus:outline-none focus:ring-0 focus:border-gray-700 leading-none">
                        Buscar
                    </button>
                <?php else: ?>
                    <!-- Si tienes una versión para usuarios no admin, agrega el nuevo filtro aquí también -->
                <?php endif; ?>
            </div>
        </form>
        <div id="alert-container" class="w-1/2 mt-2 ml-5 z-50"></div>
    </div>
    <!-- Tickets en formato de tarjeta -->
    <div class="p-5 overflow-auto pt-0">
        <?php if (count($tickets) > 0): ?>
            <div class="space-y-4" id="tickets-container">
                <?php foreach ($tickets as $ticket):
                    $ticket_id = $ticket['ticket_id'];
                    // Obtener unidades asignadas al ticket (solo las activas)
                    $queryUnits = "SELECT unidad FROM ticket_unit_assignments WHERE ticket_id = :ticket_id AND unassigned_at IS NULL";
                    $stmtUnits = $conn->prepare($queryUnits);
                    $stmtUnits->bindValue(':ticket_id', $ticket_id, PDO::PARAM_INT);
                    $stmtUnits->execute();
                    $ticket_units = $stmtUnits->fetchAll(PDO::FETCH_COLUMN);


                    // Almacenar las unidades asignadas previamente en un input oculto
                    $assigned_units_str = implode(',', $ticket_units);

                ?>
                    <?php
                    $status = strtolower($ticket['status'] ?? ''); // Usamos ?? para evitar el null

                    $opacityClass = ($status === 'resuelto' || $status === 'rechazado') ? 'opacity-50' : '';

                    ?>
                    <!-- Ticket Card -->
                    <div class="bg-white rounded-lg shadow-md p-5 flex flex-col md:flex-row items-start md:items-center cursor-pointer <?php echo $opacityClass; ?>" onclick="location.href='view-ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>'">
                        <!-- Left and Middle sections: Classification, User Initial, Ticket Details, and Assignment -->
                        <div class="flex items-center w-full md:w-auto">
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

                            <!-- Inicial del Nombre del Creador -->
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                <span class="text-xl font-bold text-gray-600">
                                    <?php echo strtoupper(substr($ticket['creator_name'], 0, 1)); ?>
                                </span>
                            </div>

                            <!-- Ticket Details and Assignment -->
                            <div class="ml-4 flex-1">
                                <h1 class="text-lg font-regular text-gray-700">
                                    <?php
                                    // Obtener y sanitizar la descripción
                                    $description = htmlspecialchars($ticket['description']);

                                    // Establecer el límite de caracteres
                                    $max_chars = 35;

                                    // Verificar si la descripción excede el límite
                                    if (mb_strlen($description) > $max_chars) {
                                        // Truncar la descripción y añadir "..."
                                        $description = mb_substr($description, 0, $max_chars) . '...';
                                    }

                                    // Mostrar la descripción truncada seguida del número de ticket
                                    echo $description . ' #' . $ticket['ticket_id'];
                                    ?>
                                </h1>

                                <p class="text-gray-600">
                                    <?php
                                    // Define un arreglo asociativo para las unidades con nombres amigables
                                    $unidadesAmigables = [
                                        'u_helpdesk' => 'Unidad Help Desk',
                                        'u_soporte' => 'Unidad Soporte',
                                        'u_desarrollo' => 'Unidad Desarrollo',
                                        'u_seguridad' => 'Unidad Seguridad',
                                        's_tecnologia' => 'Servicio de Tecnologia, comunicaciones y desarrollo'
                                    ];

                                    if (!empty($ticket_units)) {
                                        // Convertir los códigos de unidad a nombres amigables
                                        $unidadesAsignadas = array_map(function ($codigo) use ($unidadesAmigables) {
                                            return $unidadesAmigables[$codigo] ?? $codigo;  // Devuelve el nombre amigable o el código si no está definido
                                        }, $ticket_units);

                                        echo 'Unidades asignadas: ' . implode(', ', $unidadesAsignadas);
                                    } else {
                                        echo 'No asignado a ninguna unidad.';
                                    }
                                    ?>
                                </p>
                                <!-- ASIGNAR UNIDAD UNICAMENTE VISIBLE PARA U_HELPDESK-->
                                <?php if ($unidad_usuario === 'u_helpdesk' || $role === 'admin'): ?>
                                    <form method="POST" action="functions/handle_units.php" class="mt-2 relative" onsubmit="event.stopPropagation();">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                        <input type="hidden" name="previous_units" value="<?php echo htmlspecialchars($assigned_units_str); ?>">
                                        <!-- Menú desplegable personalizado -->
                                        <div class="dropdown w-64" onclick="event.stopPropagation();">
                                            <button type="button" class="border border-gray-100 rounded p-1 w-full text-left text-gray-500 pl-4" onclick="toggleDropdown(event, 'unit_list_<?php echo $ticket['ticket_id']; ?>'); event.stopPropagation();">
                                                -- Asignar Unidad --
                                            </button>
                                            <div id="unit_list_<?php echo $ticket['ticket_id']; ?>" class="dropdown-content absolute bg-white border border-gray-300 rounded mt-1 w-full z-10 p-2" onclick="event.stopPropagation();">
                                                <?php
                                                // Iterar sobre el arreglo de unidades para crear los checkboxes
                                                foreach ($unidades_validas as $codigo): ?>
                                                    <?php $nombreAmigable = $unidadesAmigables[$codigo]; ?>
                                                    <label class="block px-4 py-2 hover:bg-gray-100" onclick="event.stopPropagation();">
                                                        <input type="checkbox" name="assigned_units[]" value="<?php echo $codigo; ?>"
                                                            <?php echo in_array($codigo, $ticket_units) ? 'checked' : ''; ?>
                                                            onclick="event.stopPropagation();">
                                                        <?php echo htmlspecialchars($nombreAmigable); ?>
                                                    </label>
                                                <?php endforeach; ?>
                                                <!-- Botón Aceptar -->
                                                <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white px-2 py-1 rounded mt-2 w-full" onclick="event.stopPropagation();">Aceptar</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                                <!-- ASGINAR AGENTE A TICKET UNA VEZ ASIGNADA LA UNIDAD -->
                                <!-- ASIGNAR AGENTE A TICKET UNA VEZ ASIGNADA LA UNIDAD -->
                                <?php if (in_array($unidad_usuario, $ticket_units)): ?>
                                    <form method="POST" action="functions/assign_agent.php" class="mt-2 relative" onsubmit="event.stopPropagation();">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                        <!-- Menú desplegable personalizado -->
                                        <div class="dropdown w-64" onclick="event.stopPropagation();">
                                            <button type="button" class="border border-gray-100 rounded p-1 w-full text-left text-gray-500 pl-4" onclick="toggleDropdown(event, 'agent_list_<?php echo $ticket['ticket_id']; ?>'); event.stopPropagation();">
                                                -- Asignar Agente --
                                            </button>
                                            <div id="agent_list_<?php echo $ticket['ticket_id']; ?>" class="dropdown-content absolute bg-white border border-gray-300 rounded mt-1 w-full z-10 p-2" onclick="event.stopPropagation();">
                                                <?php
                                                // Obtener agentes de la unidad del usuario
                                                $queryAgents = "SELECT user_id, name FROM users WHERE unidad = :unidad AND role IN ('support', 'admin', 'coordinator')";
                                                $stmtAgents = $conn->prepare($queryAgents);
                                                $stmtAgents->bindValue(':unidad', $unidad_usuario, PDO::PARAM_STR);
                                                $stmtAgents->execute();
                                                $agents = $stmtAgents->fetchAll(PDO::FETCH_ASSOC);

                                                // Obtener agentes asignados al ticket
                                                $queryAssignedAgents = "SELECT user_id FROM ticket_assignments WHERE ticket_id = :ticket_id";
                                                $stmtAssignedAgents = $conn->prepare($queryAssignedAgents);
                                                $stmtAssignedAgents->bindValue(':ticket_id', $ticket['ticket_id'], PDO::PARAM_INT);
                                                $stmtAssignedAgents->execute();
                                                $assigned_agent_ids = $stmtAssignedAgents->fetchAll(PDO::FETCH_COLUMN);

                                                foreach ($agents as $agent): ?>
                                                    <label class="block px-4 py-2 hover:bg-gray-100" onclick="event.stopPropagation();">
                                                        <input type="checkbox" name="assigned_to_user_ids[]" value="<?php echo $agent['user_id']; ?>"
                                                            <?php echo in_array($agent['user_id'], $assigned_agent_ids) ? 'checked' : ''; ?>
                                                            onclick="event.stopPropagation();">
                                                        <?php echo htmlspecialchars($agent['name']); ?>
                                                    </label>
                                                <?php endforeach; ?>
                                                <!-- Botón Asignar Agente -->
                                                <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white px-2 py-1 rounded mt-2 w-full" onclick="event.stopPropagation();">Aceptar</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <p class="text-gray-600 mt-2">
                                    <?php
                                    // Obtener nombres de agentes asignados al ticket
                                    $queryAssignedAgents = "SELECT u.name FROM ticket_assignments ta JOIN users u ON ta.user_id = u.user_id WHERE ta.ticket_id = :ticket_id";
                                    $stmtAssignedAgents = $conn->prepare($queryAssignedAgents);
                                    $stmtAssignedAgents->bindValue(':ticket_id', $ticket['ticket_id'], PDO::PARAM_INT);
                                    $stmtAssignedAgents->execute();
                                    $assigned_agent_names = $stmtAssignedAgents->fetchAll(PDO::FETCH_COLUMN);

                                    if (!empty($assigned_agent_names)) {
                                        echo 'Agentes asignados: ' . implode(', ', $assigned_agent_names);
                                    } else {
                                        echo 'No hay agentes asignados.';
                                    }
                                    ?>
                                </p>

                                <!-- FIN -->
                            </div>

                        </div>

                        <!-- Status -->
                        <div class="mt-2 md:mt-0 md:ml-auto md:text-right">
                            <span class="block font-regular">
                                <?php echo htmlspecialchars($ticket['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-600">Todavía no hay Tickets registrados.</p>
        <?php endif; ?>
    </div>

    <?php include 'functions/pagination.php' ?>
    <script>
        function toggleDropdown(event, ticketId) {
            event.stopPropagation();
            var dropdown = document.getElementById('agent_list_' + ticketId);
            dropdown.classList.toggle('show');
        }

        // Cerrar el menú desplegable si se hace clic fuera de él
        window.addEventListener('click', function(event) {
            var dropdowns = document.getElementsByClassName('dropdown-content');
            for (var i = 0; i < dropdowns.length; i++) {
                var dropdown = dropdowns[i];
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.remove('show');
                }
            }
        });
    </script>
    <script src="js/toggle-menu.js" defer></script>
    <script>
        function toggleDropdown(event, dropdownId) {
            event.stopPropagation();
            var dropdown = document.getElementById(dropdownId);
            dropdown.classList.toggle('show');
        }

        // Cerrar el menú desplegable si se hace clic fuera de él
        window.addEventListener('click', function(event) {
            var dropdowns = document.getElementsByClassName('dropdown-content');
            for (var i = 0; i < dropdowns.length; i++) {
                var dropdown = dropdowns[i];
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.remove('show');
                }
            }
        });
    </script>

    <script src="js/notifications.js" defer></script>
    </body>

    </html>