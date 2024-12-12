<?php
include 'required/bdc/conex.php';

session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];  // Guardar el rol en la sesión
            $_SESSION['unidad'] = $user['unidad'];
            $_SESSION['logged_in'] = true;

            // Redirigir según el rol del usuario
            if ($user['role'] == 'user') {
                header("Location: required/pages/user/");
                exit();
            } else if (in_array($user['role'], ['support', 'admin', 'coordinator'])) {
                header("Location: required/pages/support-dashboard/");
                exit();
            }
        } else {
            echo "<script>document.addEventListener('DOMContentLoaded', function() { showToast(); });</script>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar - iTicket</title>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.2/dist/tailwind.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Righteous&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="required/favicon.ico">
    <link rel="stylesheet" href="required/login_required/css/style.css">
</head>

<body class="bg-gray-900 text-gray-800">
    <!-- TOAST Credenciales incorrectas -->
    <div id="toast" class="hidden fixed bottom-5 left-1/2 transform -translate-x-1/2 bg-red-500 text-white px-6 py-3 rounded shadow-lg text-center">
        Credenciales incorrectas, por favor intente nuevamente.
    </div>
    <!-- TOAST Credenciales incorrectas -->
    <form action="index.php" method="POST" class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-3 sm:p-5 rounded-lg shadow-lg w-full max-w-custom">
            <h2 class="text-3xl sm:text-4xl text-center text-gray-700 mb-0">iticket</h2>
            <p class="text-xs text-center text-gray-600 mb-4">Desarrollado por la Unidad de Soporte Técnico</p>
            <div class="w-2/3 border-t border-gray-400 mx-auto mb-3"></div>
            <p class="text-lg text-center text-gray-600 mb-4">Iniciar sesión</p>
            <div>
                <input type="text" id="username" name="username" required class="border border-gray-400 bg-white rounded p-2 w-full no-outline text-gray-600" placeholder="Usuario" autocomplete="off">
            </div>
            <div class="mt-4 relative">
                <input type="password" id="password" name="password" required class="border border-gray-400 bg-white rounded p-2 w-full no-outline text-gray-600 pr-10" placeholder="Contraseña" autocomplete="off">
                <span onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer">
                    <i id="eyeIcon" class='bx bx-show text-sm sm:text-base' style="color: gray; font-size: 24px; opacity: 70%"></i>
                </span>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-gray-800 hover:bg-gray-800 w-full text-white font-semibold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Ingresar
                </button>
            </div>
        </div>
    </form>
    <script src="required/login_required/js/script.js"></script>
</body>

</html>