<?php
include '../../bdc/conex.php'; // Conexion base de datos
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: /login.php");
    exit;
}

if (!in_array($_SESSION['role'], ['support', 'admin'])) {
    header("Location: ../user/");  // Redirige a una página de error o índice
    exit;
}

// Asegurarse de que el nombre está disponible en la sesión
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$unidad_usuario = $_SESSION['unidad'];
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Usuario'; // Fallback a 'Usuario' si no se encontró el nombre

// Obtener los contadores de tickets por estado
if ($unidad_usuario == 'u_helpdesk') {
    // Usuarios de 'u_helpdesk' ven todos los tickets
    $query = "SELECT status, COUNT(*) as total
              FROM support_tickets
              GROUP BY status";
    $stmt = $conn->prepare($query);
} else {
    // Usuarios de otras unidades ven solo los tickets actualmente asignados a su unidad
    $query = "
        SELECT st.status, COUNT(*) as total
        FROM support_tickets st
        WHERE st.ticket_id IN (
            SELECT tua.ticket_id
            FROM ticket_unit_assignments tua
            INNER JOIN (
                SELECT ticket_id, MAX(assigned_at) as latest_assigned_at
                FROM ticket_unit_assignments
                GROUP BY ticket_id
            ) latest ON tua.ticket_id = latest.ticket_id AND tua.assigned_at = latest.latest_assigned_at
            WHERE tua.unidad = :unidad AND tua.unassigned_at IS NULL
        )
        GROUP BY st.status
    ";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':unidad', $unidad_usuario, PDO::PARAM_STR);
}


$stmt->execute();
$status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Inicializar los contadores
$pendientes = 0;
$en_proceso = 0;
$resueltos = 0;
$rechazados = 0;
// Asignar los valores según los resultados
foreach ($status_counts as $row) {
    $status = $row['status'];
    $total = $row['total'];
    if ($status == 'Pendiente') {
        $pendientes = $total;
    } elseif ($status == 'En Proceso') {
        $en_proceso = $total;
    } elseif ($status == 'Resuelto') {
        $resueltos = $total;
    } elseif ($status == 'Rechazado') {
        $rechazados = $total;
    }
    
}
// Obtener las tareas de la unidad desde la base de datos
$stmtTasks = $conn->prepare("SELECT * FROM unit_tasks WHERE unidad = :unidad ORDER BY completed ASC, task_id ASC");
$stmtTasks->bindValue(':unidad', $unidad_usuario, PDO::PARAM_STR);
$stmtTasks->execute();
$tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

?>
<script>
    let tasks = <?php echo json_encode($tasks); ?>;
</script>

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
    <link rel="stylesheet" href="css/style.css">
    <style>
        .completed {
            text-decoration: line-through;
            color: grey;
        }
    </style>

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
                            <a href="../support-dashboard" class="flex items-center justify-center md:justify-start space-x-2 p-2 pt-0 pb-0 text-gray-600 rounded transition-colors group">
                                <i class="fas fa-layer-group text-gray-600 group-hover:text-gray-600"></i>
                                <span class="text-gray-600 group-hover:text-gray-600 font-medium text-sm">Panel</span>
                            </a>
                        </li>
                        <li>
                            <a href="../support-tickets/" class="flex items-center justify-center md:justify-start space-x-2 p-2 text-gray-600 rounded transition-colors group">
                                <i class="fas fa-ticket-alt text-gray-500 group-hover:text-gray-600"></i>
                                <span class="text-gray-500 group-hover:text-gray-600 text-sm">Tickets</span>
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

        <!-- INICIO DEL CODIGO DESPUES DE LA SIDEBAR -->

        <div id="main-content" class="flex-1 flex flex-col">
            <!-- Encabezado -->
            <div class="bg-white flex items-center p-5 border-b">
                <!-- Ícono de menú para móviles -->
                <button id="menu-toggle" class="text-gray-600 focus:outline-none mr-3 md:hidden">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <h1 class="text-xl font-regular text-gray-700">Panel de Soporte Técnico</h1>
            </div>


            <div class="flex-1 p-5">
                <!-- Primer Fila de Contadores -->
               
                <div id="alert-container" class="mt-2 ml-5"></div>


                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white shadow rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-medium text-gray-900">Pendientes</h3>
                                <p class="text-2xl font-semibold text-gray-400"><?php echo $pendientes; ?></p>
                            </div>
                            <div class="text-gray-500">
                                <!-- Icono SVG aquí -->
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-medium text-gray-900">En Proceso</h3>
                                <p class="text-2xl font-semibold text-gray-400"><?php echo $en_proceso; ?></p>
                            </div>
                            <div class="text-gray-500">
                                <!-- Icono SVG aquí -->
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-medium text-gray-900">Resueltos</h3>
                                <p class="text-2xl font-semibold text-gray-400"><?php echo $resueltos; ?></p>
                            </div>
                            <div class="text-gray-500">
                                <!-- Icono SVG aquí -->
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Segunda Fila para Tareas y Actividades -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tareas Pendientes -->
                    <div class="bg-white shadow rounded-lg p-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Tareas Pendientes</h3>
                        <form id="add-task-form" class="flex items-center">
                            <input type="text" name="task" class="border border-gray-300 p-1 rounded text-sm flex-grow mr-2" placeholder="Agregar nueva tarea" required>
                            <button type="submit" class="bg-gray-700 text-white font-regular py-1 px-2 rounded text-sm">Agregar</button>
                        </form>
                        <ul id="tasks-list" class="mt-4">
                            <!-- Las tareas se cargan acá -->
                        </ul>
                    </div>



                    <!-- Actividades Recientes -->
                    <div class="bg-white shadow rounded-lg p-4" style="max-height: 250px; overflow-y: auto;">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Actividades recientes</h3>
                        <div class="border-t border-gray-300"></div>
                        <ul class="text-sm text-gray-700">
                            <?php
                            // Obtener las actividades recientes
                            if ($unidad_usuario == 'u_helpdesk') {
                                // Usuarios de 'u_helpdesk' ven todas las actividades
                                $stmtActivities = $conn->prepare("SELECT ta.*, u.name as user_name
                                      FROM ticket_activities ta
                                      JOIN users u ON ta.user_id = u.user_id
                                      ORDER BY ta.activity_date DESC
                                      LIMIT 10");
                            } else {
                                // Usuarios de otras unidades ven solo las actividades de su unidad
                                $stmtActivities = $conn->prepare("SELECT ta.*, u.name as user_name
                                      FROM ticket_activities ta
                                      JOIN users u ON ta.user_id = u.user_id
                                      WHERE ta.unidad = :unidad
                                      ORDER BY ta.activity_date DESC
                                      LIMIT 10");
                                $stmtActivities->bindValue(':unidad', $unidad_usuario, PDO::PARAM_STR);
                            }

                            $stmtActivities->execute();
                            $recent_activities = $stmtActivities->fetchAll(PDO::FETCH_ASSOC);

                            ?>

                            <?php if (count($recent_activities) > 0): ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <li class="py-2 border-b border-gray-300">
                                        <?php
                                        $activity_date = date('d/m/Y H:i', strtotime($activity['activity_date']));
                                        echo htmlspecialchars($activity['description']) . " - " . $activity_date;
                                        ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-gray-500 px-4 py-3">No hay actividad reciente.</p>
                            <?php endif; ?>
                        </ul>
                    </div>



                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                // Abrir la sidebar
                $('#menu-toggle').click(function() {
                    $('#sidebar').removeClass('-translate-x-full');
                    $('body').addClass('overflow-hidden');
                });

                // Cerrar la sidebar
                $('#close-sidebar').click(function() {
                    $('#sidebar').addClass('-translate-x-full');
                    $('body').removeClass('overflow-hidden');
                });
            });
        </script>

        <!-- Agrega este script al final de tu archivo, antes de </body> -->
        <script>
            // Ya tenemos 'tasks' inicializada desde PHP
            // Transformamos las tareas para tener el formato necesario
            tasks = tasks.map(task => ({
                id: task.task_id,
                text: task.description,
                completed: task.completed == 1,
                previousIndex: null
            }));

            function addTask(text) {
                // Enviar la nueva tarea al servidor
                fetch('task/add_task.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'task=' + encodeURIComponent(text)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const task = {
                                id: data.task_id,
                                text: text,
                                completed: false,
                                previousIndex: null
                            };
                            tasks.push(task);
                            renderTasks();
                        } else {
                            alert('Error al agregar la tarea.');
                        }
                    });
            }

            function renderTasks() {
                const tasksList = document.getElementById('tasks-list');
                tasksList.innerHTML = '';

                // Separar tareas pendientes y completadas
                const pendingTasks = tasks.filter(task => !task.completed);
                const completedTasks = tasks.filter(task => task.completed);

                // Renderizar tareas pendientes
                pendingTasks.forEach((task) => {
                    const li = createTaskElement(task);
                    tasksList.appendChild(li);
                });

                // Renderizar tareas completadas
                completedTasks.forEach((task) => {
                    const li = createTaskElement(task);
                    tasksList.appendChild(li);
                });

                // Limitar a 5 tareas visibles y agregar scroll si es necesario
                if (tasksList.children.length > 5) {
                    tasksList.style.maxHeight = '250px'; // Ajusta la altura según tus necesidades
                    tasksList.style.overflowY = 'auto';
                } else {
                    tasksList.style.maxHeight = '';
                    tasksList.style.overflowY = '';
                }
            }

            function createTaskElement(task) {
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between mb-2';

                // Checkbox para marcar como completado
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.checked = task.completed;
                checkbox.className = 'mr-2';
                checkbox.addEventListener('change', function() {
                    toggleTaskCompletion(task.id);
                });

                // Texto de la tarea
                const taskText = document.createElement('span');
                taskText.textContent = task.text;
                if (task.completed) {
                    taskText.classList.add('completed');
                }

                // Botón Editar
                const editButton = document.createElement('button');
                editButton.className = 'text-gray-700 mr-2';
                editButton.innerHTML = '<i class="fas fa-edit"></i>';
                editButton.addEventListener('click', function() {
                    editTask(task.id);
                });

                // Botón Eliminar
                const deleteButton = document.createElement('button');
                deleteButton.className = 'text-gray-700';
                deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
                deleteButton.addEventListener('click', function() {
                    deleteTask(task.id);
                });

                const leftDiv = document.createElement('div');
                leftDiv.className = 'flex items-center';
                leftDiv.appendChild(checkbox);
                leftDiv.appendChild(taskText);

                const rightDiv = document.createElement('div');
                rightDiv.appendChild(editButton);
                rightDiv.appendChild(deleteButton);

                li.appendChild(leftDiv);
                li.appendChild(rightDiv);

                return li;
            }

            function toggleTaskCompletion(taskId) {
                const task = tasks.find(t => t.id == taskId);
                if (task) {
                    // Enviar cambio al servidor
                    fetch('task/update_task.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'task_id=' + taskId + '&completed=' + (task.completed ? 0 : 1)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                task.completed = !task.completed;
                                renderTasks();
                            } else {
                                alert('Error al actualizar la tarea.');
                            }
                        });
                }
            }

            function editTask(taskId) {
                const task = tasks.find(t => t.id == taskId);
                if (task) {
                    const newTaskText = prompt('Editar tarea:', task.text);
                    if (newTaskText !== null && newTaskText.trim() !== '') {
                        // Enviar cambio al servidor
                        fetch('task/update_task.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'task_id=' + taskId + '&text=' + encodeURIComponent(newTaskText.trim())
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    task.text = newTaskText.trim();
                                    renderTasks();
                                } else {
                                    alert('Error al actualizar la tarea.');
                                }
                            });
                    }
                }
            }

            function deleteTask(taskId) {
                if (confirm('¿Estás seguro de que deseas eliminar esta tarea?')) {
                    // Enviar solicitud al servidor
                    fetch('task/delete_task.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'task_id=' + taskId
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                tasks = tasks.filter(t => t.id != taskId);
                                renderTasks();
                            } else {
                                alert('Error al eliminar la tarea.');
                            }
                        });
                }
            }

            document.getElementById('add-task-form').onsubmit = function(event) {
                event.preventDefault();
                const taskInput = this.task;
                if (taskInput.value.trim() !== '') {
                    addTask(taskInput.value.trim());
                    taskInput.value = ''; // Limpiar el campo de texto después de agregar
                }
            };

            // Inicializar la lista de tareas
            renderTasks();
        </script>
<!-- Al final de tu archivo, antes de </body> -->

 
    <script src="js/notifications.js" defer></script>
    </body>

</html>