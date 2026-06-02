<script>
export default {
    name: 'AdminDashboard',
    props: {
        users: {
            type: Array,
            default: () => []
        },
        securityLogs: {
            type: Array,
            default: () => []
        }
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Banner Superior / Estado de Ciberseguridad -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Estado del Sistema</span>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-2">Seguro</h3>
                </div>
                <div class="mt-4 flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                    <span class="text-xs text-slate-500 font-medium">Protección Activa IPS/MFA</span>
                </div>
            </div>

            <div class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Tasa de Bloqueos (IP)</span>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-2">0.2%</h3>
                </div>
                <div class="mt-4 text-xs text-slate-400 font-medium">
                    Último bloqueo: IP aleatoria registrada
                </div>
            </div>

            <div class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Obligatoriedad 2FA</span>
                    <h3 class="text-3xl font-extrabold text-emerald-600 mt-2">100%</h3>
                </div>
                <div class="mt-4 text-xs text-slate-500 font-medium">
                    Enforzado para Roles Admin/User
                </div>
            </div>
        </div>

        <!-- Actividad del Sistema y Bitácoras -->
        <div class="bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-slate-900 text-lg">Bitácora Global de Eventos de Seguridad (SIEM)</h3>
                <span class="px-2.5 py-1 text-xs font-bold bg-indigo-50 text-indigo-700 rounded-full border border-indigo-100">Monitoreo Activo</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                     <thead>
                        <tr class="bg-slate-50/50 text-slate-500 text-xs font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="p-4">Dirección IP</th>
                            <th class="p-4">Usuario</th>
                            <th class="p-4">Evento</th>
                            <th class="p-4">Estado</th>
                            <th class="p-4 text-right">Tiempo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                        <tr v-for="log in securityLogs" :key="log.id" class="hover:bg-slate-50/50 transition-colors duration-150">
                            <td class="p-4 font-mono text-xs font-semibold text-indigo-600">{{ log.ip }}</td>
                            <td class="p-4 font-semibold text-slate-800">{{ log.email }}</td>
                            <td class="p-4 text-slate-500 font-medium">{{ log.event }}</td>
                            <td class="p-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                    :class="{
                                        'bg-rose-50 text-rose-700 border-rose-100': log.status.includes('Bloqueado') || log.status === 'Rechazado',
                                        'bg-amber-50 text-amber-700 border-amber-100': log.status === 'Advertencia' || log.status === 'Pendiente',
                                        'bg-emerald-50 text-emerald-700 border-emerald-100': log.status === 'Autorizado' || log.status === 'Exitoso'
                                    }"
                                >
                                    {{ log.status }}
                                </span>
                            </td>
                            <td class="p-4 text-right text-xs text-slate-400 font-medium">{{ log.time }}</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="securityLogs.length === 0" class="py-12 text-center text-xs text-slate-400 font-medium">
                    Sin eventos registrados en la bitácora SIEM.
                </div>
            </div>
        </div>

        <!-- Listado de Usuarios Registrados -->
        <div class="bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/70 overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-bold text-slate-900 text-lg">Listado de Usuarios Registrados</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div v-for="u in users" :key="u.email" class="p-4 bg-slate-50/40 border border-slate-100 rounded-2xl flex items-center justify-between shadow-sm">
                        <div>
                            <p class="font-bold text-slate-800 text-sm">{{ u.name }}</p>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">{{ u.email }}</p>
                            <span class="inline-block mt-2.5 px-2.5 py-0.5 text-[9px] uppercase font-extrabold tracking-wider rounded-md border"
                                :class="{
                                    'bg-indigo-50 text-indigo-700 border-indigo-100': u.role === 'admin',
                                    'bg-emerald-50 text-emerald-700 border-emerald-100': u.role === 'user',
                                    'bg-amber-50 text-amber-700 border-amber-100': u.role === 'guest'
                                }"
                            >
                                {{ u.role }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border" :class="u.mfa === 'Activo' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100'">
                                MFA: {{ u.mfa }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
