     // Referencias a elementos
    const modal = document.getElementById('modalUsuario');
    const closeModal = document.getElementById('closeModal');
    const btnCrearUsuario = document.getElementById('btnCrearUsuario');
    const formUsuario = document.getElementById('formUsuario');
    const modalTitle = document.getElementById('modalTitle');
    const usuarioIdInput = document.getElementById('usuario_id');

    // Abrir modal para crear usuario (formulario limpio)
    btnCrearUsuario.addEventListener('click', function() {
        formUsuario.reset();
        usuarioIdInput.value = "";
        modalTitle.textContent = "Crear Usuario";
        modal.style.display = "flex";
    });

    // Cerrar modal al hacer clic en la X o fuera del modal
    closeModal.addEventListener('click', function() {
        modal.style.display = "none";
    });
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });

    // Manejo del submit (crear o actualizar)
    formUsuario.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(formUsuario);
        let url = '/usuarios'; // Ruta para crear
        let method = 'POST';

        if (usuarioIdInput.value) { // Si existe valor, es edición
            url = `/usuarios/${usuarioIdInput.value}`;
            method = 'POST'; // Utilizamos method spoofing agregando _method=PUT
            formData.append('_method', 'PUT');
        }

        fetch(url, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Aquí podrías mostrar mensajes de éxito o error
                location.reload();
            })
            .catch(error => console.error('Error:', error));
    });

    // Cargar datos del usuario en el modal para editar
    document.querySelectorAll('.btn-editar').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch(`/usuarios/${id}`)
                .then(response => response.json())
                .then(data => {
                    usuarioIdInput.value = data.id;
                    document.getElementById('nombre').value = data.nombre;
                    document.getElementById('correo').value = data.correo;
                    document.getElementById('rol').value = data.rol;
                    modalTitle.textContent = "Editar Usuario";
                    modal.style.display = "flex";
                })
                .catch(error => console.error('Error:', error));
        });
    });

    // Eliminar usuario
    document.querySelectorAll('.btn-eliminar').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm("¿Estás seguro de eliminar este usuario?")) {
                fetch(`/usuarios/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        location.reload();
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    });
 