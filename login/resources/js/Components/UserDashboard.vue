<script>
export default {
    name: "UserDashboard",
    props: {
        personalLogs: {
            type: Array,
            default: () => [],
        },
    },
    computed: {
        // Obtiene el usuario autenticado desde el estado global de Inertia
        user() {
            return this.$page.props.auth.user;
        },
    },
};
</script>

<template>
    <div class="space-y-6">
        <!-- Información del Perfil del Panel -->
        <div
            class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 flex flex-col md:flex-row items-center justify-between"
        >
            <div class="flex items-center space-x-4">
                <div
                    class="w-16 h-16 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-full flex items-center justify-center font-bold text-2xl shadow-inner"
                >
                    {{ user.name.charAt(0) }}
                </div>
                <div>
                    <h3 class="text-xl font-bold text-slate-900">
                        {{ user.name }}
                    </h3>
                    <p class="text-xs text-slate-400 font-mono mt-0.5">
                        {{ user.email }}
                    </p>
                    <span
                        class="inline-block mt-2 px-2.5 py-0.5 text-[10px] uppercase font-bold tracking-widest bg-emerald-50 text-emerald-700 border border-emerald-100 rounded"
                    >
                        Rol: {{ user.role }}
                    </span>
                </div>
            </div>

            <div
                class="mt-4 md:mt-0 text-center md:text-right bg-slate-50/50 p-4 rounded-2xl border border-slate-100"
            >
                <p class="text-xs text-slate-500 font-medium">
                    Vista con Rol Usuario
                </p>
                <div
                    class="flex items-center space-x-2 mt-1 justify-center md:justify-end"
                >
                    <span
                        class="w-2.5 h-2.5 rounded-full bg-emerald-400"
                    ></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Lado Izquierdo: Información de Seguridad del 2FA -->
            <div
                class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 space-y-4"
            >
                <h4
                    class="font-bold text-slate-900 text-lg border-b border-slate-100 pb-3.5"
                >
                    Tu Seguridad 2FA
                </h4>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Esta cuenta esta verificada con 2 pasos de autenticación
                    (Password -> OTP).
                </p>

                <div
                    class="p-4 bg-slate-50/50 border border-slate-100 rounded-2xl space-y-3"
                >
                    <div class="flex items-start space-x-3">
                        <div
                            class="p-2 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-xl"
                        >
                            <!-- Ícono de escudo de verificación -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="w-5 h-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-xs text-slate-400 mt-0.5 font-medium"
                            >
                                El código de verificación es enviado a tu correo electrónico registrado.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-xs text-slate-400 pt-2 leading-relaxed">
                    * Si pierdes acceso a tu correo electrónico,
                    contacta al administrador de seguridad del sistema para
                    restablecer tu perfil de doble factor.
                </div>
            </div>

            <!-- Lado Derecho: Historial de Accesos Personales 
            <div
                class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 space-y-4"
            >
                <h4
                    class="font-bold text-slate-900 text-lg border-b border-slate-100 pb-3.5"
                >
                    Historial de Accesos Personales
                </h4>

                <div class="divide-y divide-slate-100">
                    <div
                        v-for="log in personalLogs"
                        :key="log.id"
                        class="py-3.5 flex justify-between items-center"
                    >
                        <div class="space-y-0.5">
                            <p class="text-sm font-bold text-slate-800">
                                {{ log.device }}
                            </p>
                            <div
                                class="flex items-center space-x-1.5 text-xs text-slate-400 font-medium"
                            >
                                <span>{{ log.location }}</span>
                                <span>•</span>
                                <span>{{ log.time }}</span>
                            </div>
                        </div>
                        <div>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                :class="
                                    log.status === 'Autorizado' ||
                                    log.status === 'Exitoso'
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                                        : 'bg-rose-50 text-rose-700 border-rose-100'
                                "
                            >
                                {{ log.status }}
                            </span>
                        </div>
                    </div>
                    <div
                        v-if="personalLogs.length === 0"
                        class="py-8 text-center text-xs text-slate-400 font-medium"
                    >
                        No hay historial de acceso registrado aún.
                    </div>
                </div> 
            </div> -->
        </div>
    </div>
</template>
