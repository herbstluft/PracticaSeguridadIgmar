# Reporte Completo de Pruebas de Seguridad y Funcionamiento

Este documento contiene el reporte de las validaciones de seguridad aplicadas al sistema de inicio de sesión seguro. Se detalla el comportamiento del sistema, los pasos de verificación y las medidas de protección implementadas, explicadas en un formato descriptivo y fácil de comprender para cualquier persona interesada.

---

## 1. Entorno de Pruebas y Herramientas

Para que las pruebas se consideren válidas y puedan repetirse en el futuro, se utilizó un entorno de desarrollo controlado con las siguientes herramientas explicadas de forma sencilla:

* **Servidor Backend (Laravel & PHP):** El motor principal del sistema en la computadora/servidor que procesa los datos y ejecuta las reglas de seguridad.
* **Base de Datos (MySQL):** El almacén donde se guarda toda la información de los usuarios registrados, sus permisos y las bitácoras de seguridad.
* **Pantallas de Usuario (Vue.js & Inertia.js):** La parte visual y los botones que el usuario ve y con los que interactúa en su navegador de internet.
* **Servidor de Correos de Prueba (Mailtrap):** Una bandeja de entrada simulada que nos permite verificar si el sistema envía correctamente los correos de confirmación y códigos sin mandar correos a buzones reales.
* **Canal de Alertas (Slack):** Una aplicación de chat donde el sistema envía notificaciones instantáneas de seguridad para que el equipo de soporte se entere de inmediato.
* **Aplicación Móvil (Google Authenticator):** La app que el usuario instala en su celular para generar códigos de 6 dígitos que cambian de forma constante.

---

## 2. Tabla Resumen de Resultados

Todas las pruebas de seguridad fueron completadas con éxito, demostrando que el sistema es robusto y protege la información de los usuarios.

| Código | Función Evaluada | Propósito de Seguridad | Resultado |
| :--- | :--- | :--- | :--- |
| **PR-01** | Registro de cuenta nueva | Asignar rol básico y enviar correo de activación | **Exitoso (PASADO)** |
| **PR-02** | Bloqueo por Captcha fallido | Evitar que robots automáticos llenen el sistema | **Exitoso (PASADO)** |
| **PR-03** | Activación por correo | Validar que el usuario sea dueño del correo ingresado | **Exitoso (PASADO)** |
| **PR-04** | Login Paso 1: Datos correctos | Iniciar sesión primaria con captcha resuelto | **Exitoso (PASADO)** |
| **PR-05** | Login Paso 1: Bloqueo por Captcha | Detener el acceso si no se ingresa el código visual | **Exitoso (PASADO)** |
| **PR-06** | Login Paso 2: Código al correo | Validar identidad con un código dinámico (OTP) | **Exitoso (PASADO)** |
| **PR-07** | Login Paso 2: Código inválido | Bloquear el paso ante códigos erróneos o inventados | **Exitoso (PASADO)** |
| **PR-08** | Login Paso 3: Código de celular | Autenticar con código de Google Authenticator (MFA) | **Exitoso (PASADO)** |
| **PR-09** | Panel de Administrador | Permitir accesos totales al Administrador | **Exitoso (PASADO)** |
| **PR-10** | Panel de Invitado / Usuario | Ocultar datos y restringir menús de configuración | **Exitoso (PASADO)** |
| **PR-11** | Búsqueda y Paginación | Buscar personas rápidamente de forma dinámica | **Exitoso (PASADO)** |
| **PR-12** | Cambio de Permisos (Roles) | Modificar el rol de un usuario en el panel | **Exitoso (PASADO)** |
| **PR-13** | Reinicio de MFA | Desactivar la seguridad de celular si el usuario lo pierde | **Exitoso (PASADO)** |
| **PR-14** | Alertas a Slack | Notificar incidentes importantes en tiempo real | **Exitoso (PASADO)** |

---

## 3. Detalle de los Casos de Prueba

### PR-01: Registro de nuevo usuario (Rol Guest)
* **Objetivo:** Comprobar que una cuenta nueva se cree con el rol de menor privilegio posible (Invitado) y envíe un correo para verificar la identidad antes de permitir el acceso.
* **Datos Utilizados:**
  * Nombre: `Juan Pérez`
  * Correo: `juan.perez@example.com`
  * Contraseña: `zW£x8EB'?99TS_$Fq9c4^eh]i7`
  * Captcha: Escribir el código correcto de la pantalla.
* **Pasos del Tester:**
  1. Entrar a la página de registro.
  2. Llenar el formulario con los datos anteriores.
  3. Resolver el Captcha de seguridad.
  4. Presionar el botón "Registrarse".
* **Resultado del Sistema:** El sistema guarda al usuario con el rol `guest` y envía un correo con un enlace temporal. La pantalla redirige al usuario para avisarle que confirme su correo.

---

### PR-02: Registro fallido por Captcha incorrecto
* **Objetivo:** Proteger el formulario de registro contra ataques automáticos de bots creadores de spam.
* **Datos Utilizados:**
  * Datos personales válidos de registro.
  * Captcha: `ERROR123` (Código equivocado a propósito).
* **Pasos del Tester:**
  1. Rellenar los campos requeridos en la página de registro.
  2. Digitar un texto incorrecto en la casilla del Captcha.
  3. Hacer clic en "Registrarse".
* **Resultado del Sistema:** El sistema canceló la creación del usuario. La pantalla mostró un mensaje indicando que el código no coincide y el sistema actualizó automáticamente la imagen del captcha para seguridad.

---

### PR-03: Enlace de correo de activación de cuenta
* **Objetivo:** Asegurar que el usuario active su cuenta a través de un enlace cifrado, único y seguro que le llega a su bandeja de entrada.
* **Datos Utilizados:** Enlace web temporal enviado de forma automática tras el registro exitoso.
* **Pasos del Tester:**
  1. Abrir la bandeja de correo del usuario registrado.
  2. Buscar el correo de activación y dar clic en el botón de confirmación.
* **Resultado del Sistema:** Al dar clic, el sistema verifica la firma electrónica del enlace. Si es válida, marca la cuenta como activa en la base de datos y permite que el usuario inicie sesión.

---

### PR-04: Login Paso 1 - Datos correctos y Captcha
* **Objetivo:** Iniciar la primera fase del inicio de sesión verificando los datos básicos del usuario y el código captcha.
* **Datos Utilizados:**
  * Correo: `juan.perez@example.com`
  * Contraseña: `zW£x8EB'?99TS_$Fq9c4^eh]i7`
  * Captcha: Código visual correcto de la pantalla.
* **Pasos del Tester:**
  1. Abrir la página de inicio de sesión.
  2. Digitar el correo, la clave y el captcha de la imagen.
  3. Presionar "Iniciar sesión".
* **Resultado del Sistema:** El sistema confirma que los datos de acceso son correctos pero no permite entrar todavía al menú. Envía al usuario a la pantalla del paso 2 (OTP).

---

### PR-05: Login Paso 1 - Bloqueo por Captcha incorrecto
* **Objetivo:** Evitar que un atacante intente adivinar la contraseña de un usuario mediante ataques repetitivos de fuerza bruta.
* **Datos Utilizados:**
  * Credenciales de acceso válidas.
  * Captcha: `0000` (Código inválido).
* **Pasos del Tester:**
  1. Rellenar usuario y clave correctos.
  2. Escribir un captcha inválido.
  3. Presionar el botón de inicio de sesión.
* **Resultado del Sistema:** El sistema bloquea el paso inmediatamente en la pantalla. No realiza ninguna consulta a la bandeja de correos ni despacha códigos de acceso.

---

### PR-06: Login Paso 2 - Código de un solo uso (OTP) por correo
* **Objetivo:** Comprobar la identidad del usuario a través de un código dinámico que expira a los pocos minutos enviado al correo.
* **Datos Utilizados:** Código de 6 dígitos enviado por el sistema al correo registrado del usuario.
* **Pasos del Tester:**
  1. Abrir el correo del usuario y copiar el código dinámico enviado.
  2. Ingresar el código en el recuadro del sistema.
  3. Presionar el botón "Verificar".
* **Resultado del Sistema:** El backend comprueba que el código ingresado coincide y que aún está a tiempo. Permite el acceso definitivo y redirige al panel de control (Dashboard).

---

### PR-07: Login Paso 2 - Rechazo de código OTP incorrecto
* **Objetivo:** Evitar que intrusos accedan a la cuenta ingresando números de seguridad al azar en el paso de correo.
* **Datos Utilizados:**
  * CódigoOTP: `999999` (Código equivocado).
* **Pasos del Tester:**
  1. Llegar al paso 2 de verificación.
  2. Ingresar un código inventado o vencido.
  3. Hacer clic en "Verificar".
* **Resultado del Sistema:** El backend rechaza el acceso, conserva la sesión en estado bloqueado y muestra una alerta en la pantalla que indica que el código es incorrecto o ya venció.

---

### PR-08: Login Paso 3 - Doble Factor (Google Authenticator)
* **Objetivo:** Garantizar la autenticación multifactor (MFA) para los usuarios que tengan activa la seguridad mediante la app de celular.
* **Datos Utilizados:** Código de 6 dígitos que cambia constantemente, visible en la app Google Authenticator del teléfono.
* **Pasos del Tester:**
  1. Superar el paso 1 (clave) y el paso 2 (código de correo).
  2. Abrir la app en el celular, ver el código temporal de 6 dígitos e ingresarlo.
  3. Dar clic en "Verificar".
* **Resultado del Sistema:** El sistema comprueba que el código coincide con el algoritmo matemático sincronizado con el celular del usuario y otorga el acceso final al panel de control.

---

### PR-09: Dashboard - Vista y privilegios de Administrador
* **Objetivo:** Comprobar que una cuenta con rol de Administrador tenga visibilidad total de los controles del sistema.
* **Datos Utilizados:** Cuenta de usuario activa con rol configurado como `admin`.
* **Pasos del Tester:**
  1. Iniciar sesión en el sistema usando una cuenta de administrador.
  2. Navegar al Dashboard principal.
* **Resultado del Sistema:** El administrador visualiza la barra de búsqueda de usuarios, la lista con todos los registros, los controles de roles, los logs de auditoría y los botones de configuración de seguridad.

---

### PR-10: Dashboard - Restricciones del rol Guest/User
* **Objetivo:** Respetar la privacidad de datos ocultando información de otros usuarios a cuentas sin permisos especiales.
* **Datos Utilizados:** Cuenta de usuario activa con rol configurado como `guest` (Invitado) o `user` (Usuario normal).
* **Pasos del Tester:**
  1. Iniciar sesión usando una cuenta con rol básico.
  2. Navegar al Dashboard principal.
* **Resultado del Sistema:** El sistema oculta por completo la lista global de usuarios, el buscador de personas y los registros de auditoría de otros usuarios, mostrando únicamente el perfil personal de su cuenta.

---

### PR-11: Buscador y paginador en panel de Administrador
* **Objetivo:** Permitir al administrador buscar usuarios y cambiar de página de forma ágil y fluida.
* **Datos Utilizados:**
  * Barra de búsqueda: `Juan`
  * Navegar a la página 2.
* **Pasos del Tester:**
  1. Entrar como Administrador al panel.
  2. Digitar el nombre en el buscador.
  3. Presionar los botones de paginación para avanzar de hoja.
* **Resultado del Sistema:** La lista se actualiza al instante con los resultados de la búsqueda sin tener que recargar toda la ventana del navegador.

---

### PR-12: Cambio de rol de usuario por Administrador
* **Objetivo:** Permitir que los permisos de los usuarios sean actualizados de forma segura a través del panel administrativo.
* **Datos Utilizados:**
  * Usuario destino: ID del usuario seleccionado.
  * Nuevo Rol: `admin` (Administrador).
* **Pasos del Tester:**
  1. Entrar como administrador al panel de gestión.
  2. Seleccionar un usuario de la lista y cambiar su menú de rol a Administrador.
  3. Confirmar la operación.
* **Resultado del Sistema:** El sistema actualiza el registro en la base de datos y la pantalla del usuario destino se adapta de inmediato para mostrar las opciones correspondientes a su nuevo rol.

---

### PR-13: Restablecimiento seguro de MFA por Administrador
* **Objetivo:** Permitir al administrador desactivar la verificación de celular de un usuario en caso de pérdida, robo o reemplazo del dispositivo.
* **Datos Utilizados:** ID del usuario bloqueado.
* **Pasos del Tester:**
  1. Entrar como Administrador al panel.
  2. Ubicar al usuario que tiene problemas con su autenticación de celular.
  3. Dar clic en el botón "Restablecer MFA" de su registro.
* **Resultado del Sistema:** El sistema remueve el enlace de la aplicación de seguridad para esa cuenta de usuario. Esto le permite al usuario iniciar sesión de forma básica y volver a vincular un celular nuevo en su próximo acceso.

---

### PR-14: Envío automático de logs y alertas a Slack
* **Objetivo:** Enviar avisos inmediatos del sistema a un canal centralizado de Slack ante cualquier acción importante o de riesgo para la seguridad.
* **Datos Utilizados:** Notificación del sistema que detalla el tipo de evento ocurrido, la fecha, el usuario y la dirección IP.
* **Pasos del Tester:**
  1. Hacer una acción crítica (ej. iniciar sesión con éxito, fallar un inicio de sesión o cambiar un rol).
  2. Abrir la aplicación de chat Slack en el canal de alertas configurado.
* **Resultado del Sistema:** Se envía un mensaje al canal de Slack al instante de manera automática confirmando que se detectó la actividad y aportando los datos de auditoría.

---

## 4. Conclusiones
El sistema de seguridad analizado cuenta con una arquitectura robusta contra accesos no autorizados y fraudes de robots. Se confirmó el funcionamiento idóneo de los tres pasos de verificación (contraseña con captcha, código al correo y código de celular). El sistema respeta el aislamiento de permisos por roles y genera registros locales y remotos (Slack) para que los administradores mantengan el control de la seguridad al instante.
