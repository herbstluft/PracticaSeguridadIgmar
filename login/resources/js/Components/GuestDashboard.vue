<script>
export default {
    name: 'GuestDashboard',
    computed: {
        // Obtiene el usuario autenticado desde el estado de Inertia
        user() {
            return this.$page.props.auth.user;
        }
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Banner de Notificación de Invitado -->
        <div class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 flex flex-col md:flex-row items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-amber-50 border border-amber-100 text-amber-700 rounded-full flex items-center justify-center font-bold text-2xl shadow-inner animate-pulse">
                    !
                </div>
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Modo Invitado (Lectura Limitada)</h3>
                    <p class="text-sm text-slate-500 mt-1">Hola {{ user.name }}, has iniciado sesión bajo un rol limitado de Demostración.</p>
                </div>
            </div>
        </div>

        <!-- Limitaciones y Detalles -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 space-y-4">
                <h4 class="font-bold text-slate-900 text-lg border-b border-slate-100 pb-3.5">Limitaciones de tu Perfil</h4>
                
                <ul class="space-y-3 text-sm text-slate-600">
                    <li class="flex items-center space-x-2.5">
                        <span class="text-amber-500 font-bold">✕</span>
                        <span>Sin acceso a la bitácora global de eventos de seguridad.</span>
                    </li>
                    <li class="flex items-center space-x-2.5">
                        <span class="text-amber-500 font-bold">✕</span>
                        <span>Sin permisos para modificar el estado del sistema o agregar IPs.</span>
                    </li>
                    <li class="flex items-center space-x-2.5">
                        <span class="text-amber-500 font-bold">✕</span>
                        <span>Permisos de lectura limitados en el entorno de desarrollo.</span>
                    </li>
                </ul>
            </div>

            <div class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 space-y-4">
                <h4 class="font-bold text-slate-900 text-lg border-b border-slate-100 pb-3.5">Información del Entorno Sandbox</h4>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Este panel sirve para demostrar el comportamiento del sistema de login y autenticación multifactor (3 pasos) cuando un usuario con privilegios restringidos (invitado) inicia sesión de forma exitosa.
                </p>
                <div class="p-4 bg-slate-50/50 border border-slate-100 rounded-2xl">
                    <span class="text-xs font-bold text-amber-700">Recomendación:</span>
                    <p class="text-xs text-slate-500 mt-1">
                        Cierra sesión e inicia con la cuenta de Administrador (<strong class="text-slate-800">admin@example.com</strong>) para visualizar las herramientas de SIEM y bitácoras avanzadas.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
