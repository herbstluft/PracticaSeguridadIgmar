<script>
export default {
    name: "GuestDashboard",
    computed: {
        // Obtiene el usuario autenticado desde el estado de Inertia
        user() {
            return this.$page.props.auth.user;
        },
    },
};
</script>

<template>
    <div class="space-y-6">
        <!-- Banner de Notificación de Invitado -->
        <div
            class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 flex flex-col md:flex-row items-center justify-between"
        >
            <div class="flex items-center space-x-4">
                <div
                    class="w-16 h-16 bg-amber-50 border border-amber-100 text-amber-700 rounded-full flex items-center justify-center font-bold text-2xl shadow-inner animate-pulse"
                >
                    !
                </div>
                <div>
                    <h3 class="text-xl font-bold text-slate-900">
                        Modo Invitado
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Hola {{ user.name }}, has iniciado sesion como invitado.
                    </p>
                </div>
            </div>
        </div>

        <!-- Limitaciones y Detalles -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div
                class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 space-y-4"
            >
                <h4
                    class="font-bold text-slate-900 text-lg border-b border-slate-100 pb-3.5"
                >
                    Usuario Invitado
                </h4>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Este panel sirve para demostrar el comportamiento del
                    sistema de login y autenticación de un solo factor porque
                    eres invitado.
                </p>
            </div>
        </div>
    </div>
</template>
