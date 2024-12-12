iTicket
Descripción

iTicket es un sistema de gestión de tickets implementado en el sector de Sistemas de un hospital. Funciona integrado con un chatbot de WhatsApp, el cual recopila la información necesaria del usuario (solicitante) y luego genera el ticket en la plataforma iTicket. Este sistema facilita la asignación, el seguimiento y la resolución de incidencias internas.

Importante: Este sistema de tickets funciona únicamente a través de WhatsApp.
Funcionamiento Interno
Panel (Dashboard)

El panel principal muestra las estadísticas de la unidad a la que pertenece el usuario:

    Cantidad de tickets pendientes
    Cantidad de tickets en proceso
    Cantidad de tickets resueltos

Además, ofrece un espacio para publicar tareas diarias visibles por todos los usuarios de la misma unidad, así como un registro de actividades recientes (quién asignó o desasignó tickets, horarios de esas acciones, etc.).
Tickets

En la sección de tickets se pueden visualizar las incidencias pendientes de la unidad. Si el usuario tiene rol de administrador, podrá ver todos los tickets del sistema, no solo los de su unidad. Esto permite que, en caso de ausencia del responsable de una unidad, otro administrador pueda tomar su lugar y asignar tickets a cualquier unidad. Sin embargo, no podrá intervenir directamente sobre los usuarios de otras unidades, más que asignándoles tickets.

Al seleccionar un ticket, se mostrará toda la información enviada por el chatbot de WhatsApp:

    Nombre y apellido del solicitante
    Sector donde se encuentra
    Número interno de contacto
    Detalle de la incidencia

Asimismo, existe un apartado para añadir respuestas a modo de guía interna o notas privadas, que no serán enviadas al creador del ticket.
Estadísticas

Los usuarios con rol administrativo pueden acceder a estadísticas detalladas sobre los tickets resueltos o rechazados por unidad y por usuario. Estas estadísticas pueden visualizarse de manera global o filtradas por rangos de fechas.
Usuarios

El sistema cuenta con distintos tipos de usuarios, todos con rol "ADMIN":

    HelpDesk: Recibe los tickets y los asigna a las unidades correspondientes.
    Unidad de Soporte Técnico: Realiza tareas de soporte técnico (cambio de tóners, reparación de PC, problemas de conexión, etc.).
    Unidad de Desarrollo: Asiste en incidencias relacionadas a los sistemas internos (por ejemplo, blanqueo de contraseñas).
    Unidad de Seguridad: Encargada de la seguridad informática del hospital, ofrece ayuda ante intentos de phishing, hackeos y otras amenazas.
    ChatBot (WhatsApp): Usuario obligatorio para el funcionamiento del sistema, ya que es quien envía las solicitudes generadas por WhatsApp a la plataforma iTicket.

Credenciales de Usuarios:

    HelpDesk
        Usuario: helpdesk
        Contraseña: helpdesk

    Soporte
        Usuario: soporte
        Contraseña: soporte

    Desarrollo
        Usuario: desarrollo
        Contraseña: desarrollo

    Seguridad
        Usuario: seguridad
        Contraseña: seguridad

    ChatBot WhatsApp
        Usuario: wspbot
        Contraseña: wspbot

Los usuarios de una misma unidad solo pueden asignar tickets a otros usuarios de esa misma unidad, a excepción de los administrativos, quienes pueden reasignar tickets a cualquier unidad.
Requerimientos para el correcto funcionamiento

    Proyecto ChatBot (disponible en el repositorio)
    Node.js
    Composer

_____________________________________________________________________________________________________________________________________________________________________________________________________

Login (Inicio de sesión)

![1_login](https://github.com/user-attachments/assets/91490ad2-4e01-4f1a-ae03-761c9b6e9537)

Dashboard (Panel de inicio)

![2_dashboard](https://github.com/user-attachments/assets/6569f58b-dd50-4591-b8a3-42c74ae97d10)

Tickets

![3_tickets](https://github.com/user-attachments/assets/3c3a3f0b-00e1-4a66-8c4d-df9fc0277bd9)

View Ticket (Visualización del Ticket)

![4_view_ticket](https://github.com/user-attachments/assets/d13a45fc-d322-473b-8556-89da629aaa97)

Stats (Estadisticas)

![5_stats](https://github.com/user-attachments/assets/409dc638-4ef8-4883-8129-333ebbeae6b1)



