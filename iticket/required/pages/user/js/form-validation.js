document.addEventListener('DOMContentLoaded', function() {
    console.log('form-validation.js ha sido cargado y DOMContentLoaded se ha disparado');

    // Manejar la visualización del campo "interno"
    const internoSection = document.getElementById('interno-section');
    const internoField = document.getElementById('interno-field');
    const tieneInternoRadios = document.querySelectorAll('input[name="tiene_interno"]');

    tieneInternoRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.value === 'si') {
                internoField.classList.remove('hidden');
            } else {
                internoField.classList.add('hidden'); 
                document.getElementById('interno').value = ''; // Limpiar el campo si se oculta
            }
        });
    });

    // Validación del campo "interno" para solo números y máximo 5 dígitos
    const internoInput = document.getElementById('interno');

    internoInput.addEventListener('input', function(e) {
        // Permitir solo números
        this.value = this.value.replace(/\D/g, '');

        // Limitar a 5 caracteres
        if (this.value.length > 5) {
            this.value = this.value.slice(0, 5);
        }
    });

    // Validación del formulario
    const supportForm = document.getElementById('supportForm');

    supportForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const asunto = document.getElementById('asunto').value.trim();
        const sector = document.getElementById('sector').value.trim();
        const descripcion = document.getElementById('descripcion').value.trim();
        const tieneInterno = document.querySelector('input[name="tiene_interno"]:checked') ? document.querySelector('input[name="tiene_interno"]:checked').value : null;
        const interno = document.getElementById('interno').value.trim();

        // Validación de campos requeridos
        if (!asunto || !sector || !descripcion || tieneInterno === null) {
            Swal.fire({
                icon: 'error',
                title: 'Campos requeridos',
                text: 'Por favor, completa todos los campos requeridos.'
            });
            return;
        }

        // Validación de "descripcion" longitud
        if (descripcion.length < 15 || descripcion.length > 200) {
            Swal.fire({
                heightAuto: false, 
                icon: 'error',
                title: 'Error en la descripción',
                text: 'La descripción debe tener entre 15 y 200 caracteres.'
            },
        
        );
            
            return;
        }

        // Si el usuario tiene interno, validar el campo
        if (tieneInterno === 'si') {
            if (!interno) {
                Swal.fire({
                    heightAuto: false,
                    icon: 'error',
                    title: 'Error en el interno',
                    text: 'Por favor, ingresa tu número de interno.'
                });
                return;
            }

            if (interno.length > 5) {
                Swal.fire({
                    heightAuto: false,
                    icon: 'error',
                    title: 'Error en el interno',
                    text: 'El número de interno debe tener un máximo de 5 dígitos.'
                });
                return;
            }
        }

        // Si todas las validaciones pasan, enviar el formulario
        supportForm.submit();
    });

    // Contador de caracteres para "descripcion"
    const descripcionInput = document.getElementById('descripcion');
    const descripcionCounter = document.getElementById('descripcion-counter');

    descripcionInput.addEventListener('input', function() {
        const currentLength = this.value.length;
        descripcionCounter.textContent = `${currentLength}/200`;
    });
});
