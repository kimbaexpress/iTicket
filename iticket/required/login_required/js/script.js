function togglePasswordVisibility() {
    var passwordInput = document.getElementById('password');
    var eyeIcon = document.getElementById('eyeIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.className = 'bx bx-hide'; // Cambiar a icono ocultar
    } else {
        passwordInput.type = 'password';
        eyeIcon.className = 'bx bx-show'; // Cambiar a icono mostrar
    }
}
function showToast() {
const toast = document.getElementById('toast');
toast.classList.remove('hidden');
toast.classList.add('block');
setTimeout(() => {
    toast.classList.add('hidden');
    toast.classList.remove('block');
}, 3000); // El toast se oculta después de 3 segundos
}