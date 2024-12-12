<?php
include '../../bdc/conex.php'; 
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: /login.php");
    exit;
}

if (!in_array($_SESSION['role'], ['support', 'admin'])) {
    header("Location: ../user/"); 
    exit;
}

// Asegurarse de que el nombre está disponible en la sesión
$username = $_SESSION['username'];
$unidad_usuario = $_SESSION['unidad'];
$role = $_SESSION['role'];
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Usuario'; 

// Obtener unidades disponibles
$stmtUnidades = $conn->prepare("SELECT DISTINCT unidad FROM users");
$stmtUnidades->execute();
$unidades = $stmtUnidades->fetchAll(PDO::FETCH_COLUMN);

// Obtener usuarios disponibles
$stmtUsuarios = $conn->prepare("SELECT user_id, name FROM users");
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

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

// Obtener unidades disponibles nuevamente para el selectbox
$stmtUnidades = $conn->prepare("SELECT DISTINCT unidad FROM users");
$stmtUnidades->execute();
$unidades = $stmtUnidades->fetchAll(PDO::FETCH_COLUMN);

// Definir los nombres amigables para las unidades
$unidadesAmigables = [
    "u_helpdesk" => "Unidad HelpDesk",
    "u_desarrollo" => "Unidad de Desarrollo",
    "u_soporte" => "Unidad de Soporte",
    "u_seguridad" => "Unidad de Seguridad",
    "s_tecnologia" => "Servicio de Tecnología",
];

// Filtrar la unidad 'usuario' y preparar las unidades filtradas
$unidadesFiltradas = [];
foreach ($unidades as $unidad) {
    if ($unidad !== 'usuario' && isset($unidadesAmigables[$unidad])) {
        $unidadesFiltradas[$unidad] = $unidadesAmigables[$unidad];
    }
}

// Convertir las unidades filtradas a JSON para usarlas en JavaScript
$unidadesJson = json_encode($unidadesFiltradas);
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
    <script src="https://kit.fontawesome.com/a5039e743d.js" crossorigin="anonymous"></script>
    <!-- Agregar estos scripts antes de cerrar el body -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        .completed {
            text-decoration: line-through;
            color: grey;
        }
    </style>
    <style>
        .chart-container {
            width: 100%;
            max-width: 600px;
            /* Ajusta este valor según el ancho de tu sidebar */
            height: 400px;
            /* Altura fija para mantener la proporción */
            margin: 0 auto;
            /* Centrar el gráfico */
        }

        /* Ajustar el canvas para que ocupe todo el contenedor */
        #stats-chart {
            width: 100% !important;
            height: 100% !important;
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
                            <i class="fas fa-layer-group text-gray-500 group-hover:text-gray-600"></i>
                            <span class="text-gray-500 group-hover:text-gray-600 font-medium text-sm">Panel</span>
                        </a>
                    </li>
                    <li>
                        <a href="../support-tickets/" class="flex items-center justify-center md:justify-start space-x-2 p-2 text-gray-600 rounded transition-colors group">
                            <i class="fas fa-ticket-alt text-gray-500 group-hover:text-gray-600"></i>
                            <span class="text-gray-500 group-hover:text-gray-600 text-sm">Tickets</span>
                        </a>
                    </li>
                    <li>
                        <?php if ($role == 'admin'): ?>
                            <a href="../stadistics" class="flex items-center justify-center md:justify-start space-x-2 p-2 pt-0 pb-0 text-gray-600 rounded transition-colors group">
                                <i class="fas fa-chart-pie text-gray-600 group-hover:text-gray-600"></i>
                                <span class="text-gray-600 group-hover:text-gray-600 font-medium text-sm">Estadisticas</span>
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
            <h1 class="text-xl font-regular text-gray-700">Estadisticas de Soporte Técnico</h1>
        </div>

        <!-- CONTENIDO ACA ADENTRO -->
        <div class="p-5">
            <form id="stats-form" class="bg-white p-5 rounded shadow-md">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Selección de Unidad o Usuario -->
                    <div>
                        <label for="filter-type" class="block text-gray-700 font-regular mb-2">Filtrar por:</label>
                        <select id="filter-type" name="filter_type" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                            <option value="unidad">Unidad</option>
                            <option value="usuario">Usuario</option>
                        </select>
                    </div>
                    <!-- Input para Unidad o Usuario -->
                    <div id="filter-value-container">
                        <label for="filter-value" class="block text-gray-700 font-regular mb-2" id="filter-value-label">Unidad:</label>
                        <select id="filter-value" name="filter_value" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                            <?php foreach ($unidadesFiltradas as $codigo => $nombre): ?>
                                <option value="<?php echo htmlspecialchars($codigo); ?>"><?php echo htmlspecialchars($nombre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Rango de fechas -->
                    <div>
                        <label for="start-date" class="block text-gray-700 font-regular mb-2">Fecha Inicio:</label>
                        <input type="date" id="start-date" name="start_date" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div>
                        <label for="end-date" class="block text-gray-700 font-regular mb-2">Fecha Fin:</label>
                        <input type="date" id="end-date" name="end_date" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <!-- Tipo de gráfico -->
                    <div>
                        <label for="chart-type" class="block text-gray-700 font-regular mb-2">Tipo de Gráfico:</label>
                        <select id="chart-type" name="chart_type" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                            <option value="bar">Barra</option>
                            <option value="line">Línea</option>
                            <option value="pie">Pastel</option>
                        </select>
                    </div>
                </div>
                <!-- Botón para generar estadísticas -->
                <div>
                    <button type="submit" class="bg-gray-700 text-white px-4 py-2 rounded mt-4">Generar Estadísticas</button>
                </div>
            </form>

            <!-- Contenedor para el gráfico -->
            <div class="mt-6">
                <div class="chart-container">
                    <canvas id="stats-chart"></canvas>
                </div>
            </div>

            <!-- Botón para descargar el gráfico en PDF (oculto inicialmente) -->
            <div class="mt-4">
                <button id="download-pdf" class="bg-green-500 text-white px-4 py-2 rounded hidden">Generar PDF Estadisticas</button>
            </div>
        </div>
        <!-- FIN DEL CONTENIDO -->

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


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterType = document.getElementById('filter-type');
            const filterValueContainer = document.getElementById('filter-value-container');
            const filterValueLabel = document.getElementById('filter-value-label');
            const filterValueSelect = document.getElementById('filter-value');
            const statsForm = document.getElementById('stats-form');
            const chartCanvas = document.getElementById('stats-chart');
            let chartInstance = null;

            // Variables para almacenar el filtro actual
            let currentFilterType = 'unidad';
            let currentFilterValue = '';
            let currentStartDate = '';
            let currentEndDate = '';

            // Obtener las unidades y usuarios desde PHP
            const unidades = <?php echo $unidadesJson; ?>;
            const usuarios = <?php echo json_encode($usuarios); ?>;

            // Función para formatear fechas
            function formatDate(dateStr) {
                if (!dateStr) return '';
                const [year, month, day] = dateStr.split('-');
                return `${day}/${month}/${year}`;
            }

            // Función para agregar líneas de texto al PDF
            function addTextLines(pdf, lines, startX, startY, lineHeight, fontSize) {
                pdf.setFontSize(fontSize);
                let y = startY;
                lines.forEach(line => {
                    pdf.text(line, startX, y);
                    y += lineHeight;
                });
                return y; // Retorna la nueva posición Y
            }

            // Actualizar opciones según filtro seleccionado
            filterType.addEventListener('change', function() {
                const selectedType = this.value;
                filterValueSelect.innerHTML = '';

                if (selectedType === 'unidad') {
                    filterValueLabel.textContent = 'Unidad:';
                    for (const unidad in unidades) {
                        const option = document.createElement('option');
                        option.value = unidad; // Código de la unidad
                        option.textContent = unidades[unidad]; // Nombre amigable
                        filterValueSelect.appendChild(option);
                    }
                } else {
                    filterValueLabel.textContent = 'Usuario:';
                    usuarios.forEach(function(usuario) {
                        const option = document.createElement('option');
                        option.value = usuario.user_id;
                        option.textContent = usuario.name;
                        filterValueSelect.appendChild(option);
                    });
                }
            });

            // Inicializar opciones
            filterType.dispatchEvent(new Event('change'));

            // Manejar envío del formulario
            statsForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(statsForm);
                // Actualizar las variables del filtro actual
                currentFilterType = formData.get('filter_type');
                currentFilterValue = formData.get('filter_value');
                currentStartDate = formData.get('start_date');
                currentEndDate = formData.get('end_date');

                fetch('get_stats.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Generar el gráfico
                        if (chartInstance) {
                            chartInstance.destroy();
                        }

                        const ctx = chartCanvas.getContext('2d');
                        const chartType = formData.get('chart_type');
                        const labels = data.labels;
                        const chartData = data.data;

                        chartInstance = new Chart(ctx, {
                            type: chartType,
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Cantidad de Tickets',
                                    data: chartData,
                                    backgroundColor: [
                                        'rgba(54, 162, 235, 0.5)',
                                        'rgba(255, 206, 86, 0.5)',
                                        'rgba(75, 192, 192, 0.5)',
                                        'rgba(153, 102, 255, 0.5)',
                                        'rgba(255, 99, 132, 0.5)'
                                    ],
                                    borderColor: [
                                        'rgba(54, 162, 235, 1)',
                                        'rgba(255, 206, 86, 1)',
                                        'rgba(75, 192, 192, 1)',
                                        'rgba(153, 102, 255, 1)',
                                        'rgba(255, 99, 132, 1)'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });

                        // Mostrar el botón de Descargar PDF
                        document.getElementById('download-pdf').classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });

            // Manejar descarga en PDF
            document.getElementById('download-pdf').addEventListener('click', function() {
                // Verificar si hay un gráfico generado
                if (!chartInstance) {
                    alert('Por favor, genera un gráfico primero.');
                    return;
                }

                // Acceder a jsPDF desde la librería UMD
                const {
                    jsPDF
                } = window.jspdf;
                const pdf = new jsPDF('landscape');

                // Definir el tamaño de la fuente y el espaciado entre líneas
                const fontSize = 12; // Tamaño de fuente ajustado
                const lineHeight = 10; // Espaciado entre líneas
                const startX = 10; // Posición X inicial
                let yPosition = 20; // Posición Y inicial

                // Preparar las líneas de texto para el PDF
                let filterInfoLines = [];

                // Agregar información sobre el filtro seleccionado
                if (currentFilterType === 'unidad') {
                    filterInfoLines.push(`Estadísticas de la ${unidades[currentFilterValue]}`);
                } else if (currentFilterType === 'usuario') {
                    // Encontrar el nombre del usuario por su ID
                    const usuario = usuarios.find(u => u.user_id == currentFilterValue);
                    const nombreUsuario = usuario ? usuario.name : 'Usuario Desconocido';
                    filterInfoLines.push(`Estadísticas de ${nombreUsuario}`);
                }

                // Agregar información sobre el rango de fechas si se ha seleccionado
                if (currentStartDate && currentEndDate) {
                    filterInfoLines.push(`Rango de fechas: ${formatDate(currentStartDate)} a ${formatDate(currentEndDate)}`);
                } else if (currentStartDate) {
                    filterInfoLines.push(`Desde fecha: ${formatDate(currentStartDate)}`);
                } else if (currentEndDate) {
                    filterInfoLines.push(`Hasta fecha: ${formatDate(currentEndDate)}`);
                }

                // Agregar texto al PDF usando la función reutilizable
                yPosition = addTextLines(pdf, filterInfoLines, startX, yPosition, lineHeight, fontSize);

                // Agregar espacio adicional debajo del texto
                const extraSpace = 10; // Espaciado adicional en Y
                yPosition += extraSpace;

                // Capturar el gráfico como imagen y agregarlo al PDF en la nueva posición Y
                html2canvas(document.querySelector('.chart-container')).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const imgProps = pdf.getImageProperties(imgData);
                    const pdfWidth = pdf.internal.pageSize.getWidth() - 180; // Ajustar márgenes laterales
                    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

                    pdf.addImage(imgData, 'PNG', startX, yPosition, pdfWidth, pdfHeight);
                    pdf.save('estadisticas.pdf');
                }).catch(error => {
                    console.error('Error al generar el PDF:', error);
                });
            });
        });
    </script>

</body>

</html>