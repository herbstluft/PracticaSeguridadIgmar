<script>
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import InputLabel from '@/Components/InputLabel.vue';

export default {
    name: 'AdminDashboard',
    components: {
        InputLabel
    },
    props: {
        users: {
            type: [Array, Object],
            default: () => []
        },
        filters: {
            type: Object,
            default: () => ({})
        },
        securityLogs: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            isModalOpen: false,
            selectedUser: null,
            modalMode: 'role', // 'role' o 'mfa'
            form: {
                user_id: null,
                role: 'user',
                password: ''
            },
            processing: false,
            errorMessage: '',
            successMessage: '',
            searchQuery: this.filters?.search || '',
            searchTimeout: null
        };
    },
    watch: {
        searchQuery(newVal) {
            this.performSearch(newVal);
        }
    },
    computed: {
        userList() {
            // Devuelve el listado plano de usuarios, ya sea que venga paginado (Object) o como arreglo (Array)
            return Array.isArray(this.users) ? this.users : (this.users.data || []);
        },
        isPaginated() {
            return !Array.isArray(this.users) && this.users.links && this.users.links.length > 3;
        }
    },
    methods: {
        openEditModal(user) {
            this.selectedUser = user;
            this.form.user_id = user.id;
            this.form.role = user.role;
            this.form.password = '';
            this.modalMode = 'role';
            this.errorMessage = '';
            this.successMessage = '';
            this.isModalOpen = true;
        },
        openMfaResetModal(user) {
            this.selectedUser = user;
            this.form.user_id = user.id;
            this.form.role = user.role;
            this.form.password = '';
            this.modalMode = 'mfa';
            this.errorMessage = '';
            this.successMessage = '';
            this.isModalOpen = true;
        },
        closeModal() {
            if (this.processing) return;
            this.isModalOpen = false;
            this.selectedUser = null;
            this.form.user_id = null;
            this.form.role = 'user';
            this.form.password = '';
            this.modalMode = 'role';
            this.errorMessage = '';
            this.successMessage = '';
        },
        async submitAction() {
            if (this.processing) return;
            
            this.processing = true;
            this.errorMessage = '';
            this.successMessage = '';

            const url = this.modalMode === 'role'
                ? route('admin.users.change-role')
                : route('admin.users.reset-mfa');

            try {
                const response = await axios.post(url, this.form);
                this.successMessage = response.data.message;
                
                // Mostrar el mensaje de éxito brevemente antes de cerrar el modal y recargar
                setTimeout(() => {
                    this.isModalOpen = false;
                    this.processing = false;
                    this.closeModal();
                    
                    // Recargar parcialmente los datos para reflejar el cambio en la vista y bitácora
                    router.reload({ only: ['users', 'securityLogs'] });
                }, 1500);
            } catch (error) {
                this.processing = false;
                if (error.response && error.response.data && error.response.data.message) {
                    this.errorMessage = error.response.data.message;
                } else {
                    const actionName = this.modalMode === 'role' ? 'cambiar el rol' : 'restablecer el MFA';
                    this.errorMessage = `Ocurrió un error inesperado al intentar ${actionName}.`;
                }
            }
        },
        performSearch(val) {
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            this.searchTimeout = setTimeout(() => {
                router.get(route('dashboard'), { search: val }, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['users']
                });
            }, 300);
        },
        goToPage(url) {
            if (!url) return;
            router.get(url, {}, {
                preserveState: true,
                preserveScroll: true,
                only: ['users']
            });
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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-bold text-slate-900 text-lg">Listado de Usuarios Registrados</h3>
                <div class="relative w-full sm:w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Buscar por nombre o correo..."
                        class="block w-full pl-10 pr-4 py-2 bg-white border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-xs shadow-sm transition-all duration-200"
                    />
                </div>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto rounded-2xl border border-slate-100">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 text-slate-500 text-xs font-semibold uppercase tracking-wider border-b border-slate-100">
                                <th class="p-4">Usuario</th>
                                <th class="p-4">Correo Electrónico</th>
                                <th class="p-4">Rol</th>
                                <th class="p-4">Doble Factor (OTP Correo)</th>
                                <th class="p-4">MFA (App)</th>
                                <th class="p-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-600 bg-white">
                            <tr v-for="u in userList" :key="u.email" class="hover:bg-slate-50/50 transition-colors duration-150">
                                <td class="p-4 flex items-center space-x-3">
                                    <div class="w-9 h-9 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-xl flex items-center justify-center font-bold text-sm shadow-inner">
                                        {{ u.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="font-bold text-slate-800">{{ u.name }}</span>
                                </td>
                                <td class="p-4 font-mono text-xs text-slate-500">{{ u.email }}</td>
                                <td class="p-4">
                                    <span class="inline-block px-2.5 py-0.5 text-[9px] uppercase font-extrabold tracking-wider rounded-md border"
                                        :class="{
                                            'bg-indigo-50 text-indigo-700 border-indigo-100': u.role === 'admin',
                                            'bg-emerald-50 text-emerald-700 border-emerald-100': u.role === 'user',
                                            'bg-amber-50 text-amber-700 border-amber-100': u.role === 'guest'
                                        }"
                                    >
                                        {{ u.role }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border"
                                        :class="u.email_otp === 'Activo' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100'"
                                    >
                                        {{ u.email_otp }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border" :class="u.mfa === 'Activo' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100'">
                                        {{ u.mfa }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end items-center space-x-2">
                                        <template v-if="u.email !== $page.props.auth.user.email">
                                            <button
                                                @click="openEditModal(u)"
                                                class="text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 px-3 py-1.5 rounded-xl transition-all duration-200"
                                            >
                                                Editar Rol
                                            </button>
                                            <button
                                                v-if="u.role === 'admin' || u.mfa === 'Activo'"
                                                @click="openMfaResetModal(u)"
                                                class="text-xs font-bold text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-100 px-3 py-1.5 rounded-xl transition-all duration-200"
                                            >
                                                Restablecer MFA
                                            </button>
                                        </template>
                                        <span v-else class="text-xs text-slate-400 font-medium italic pr-3">Tu Cuenta</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div v-if="userList.length === 0" class="py-12 text-center text-xs text-slate-400 font-medium bg-white">
                        No se encontraron usuarios registrados con los criterios de búsqueda.
                    </div>
                </div>

                <!-- Controles de Paginación -->
                <div v-if="isPaginated" class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-100 pt-5">
                    <div class="text-xs text-slate-500 font-medium">
                        Mostrando del <b>{{ (users.current_page - 1) * users.per_page + 1 }}</b> al <b>{{ Math.min(users.current_page * users.per_page, users.total) }}</b> de <b>{{ users.total }}</b> usuarios
                    </div>
                    <div class="flex items-center space-x-1">
                        <button
                            v-for="link in users.links"
                            :key="link.label"
                            @click="goToPage(link.url)"
                            :disabled="!link.url || link.active"
                            class="px-3 py-2 text-xs font-bold rounded-xl border transition-all duration-200"
                            :class="{
                                'bg-indigo-600 border-indigo-600 text-white shadow-md shadow-indigo-100': link.active,
                                'bg-white border-slate-200 text-slate-700 hover:bg-slate-50': !link.active && link.url,
                                'bg-slate-50 border-slate-100 text-slate-300 cursor-not-allowed': !link.url
                            }"
                            v-html="link.label"
                        ></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmación (Cambio de Rol / Restablecer MFA) -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl max-w-md w-full overflow-hidden transition-all duration-300 transform scale-100">
                <!-- Encabezado del Modal -->
                <div class="px-6 py-5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-extrabold text-slate-900 text-lg">
                        {{ modalMode === 'role' ? 'Cambiar Rol de Usuario' : 'Restablecer MFA del Usuario' }}
                    </h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 transition-colors duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Cuerpo del Modal -->
                <form @submit.prevent="submitAction" class="p-6 space-y-4">
                    <div>
                        <p class="text-sm text-slate-500 font-medium">
                            {{ modalMode === 'role' ? 'Estás modificando el rol de:' : 'Estás restableciendo la seguridad de:' }}
                        </p>
                        <p class="font-bold text-slate-900 text-base mt-0.5">{{ selectedUser.name }}</p>
                        <p class="text-xs text-slate-400 font-mono">{{ selectedUser.email }}</p>
                    </div>

                    <!-- Selector de nuevo Rol -->
                    <div v-if="modalMode === 'role'" class="space-y-1.5">
                        <label for="new_role" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Nuevo Rol</label>
                        <select
                            id="new_role"
                            v-model="form.role"
                            class="block w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-3 text-sm shadow-sm transition-all duration-200"
                            required
                        >
                            <option value="user">Usuario Estándar</option>
                            <option value="admin">Administrador de Seguridad</option>
                            <option value="guest">Usuario Invitado</option>
                        </select>
                    </div>

                    <!-- Advertencia de Restablecimiento de MFA -->
                    <div v-if="modalMode === 'mfa'" class="p-4 bg-amber-50 border border-amber-100 rounded-2xl text-amber-800 space-y-2">
                        <div class="flex items-start space-x-2">
                            <span class="text-lg leading-none mt-0.5">⚠</span>
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-amber-850">Atención - Acción Crítica</h4>
                                <p class="text-[11px] text-amber-700 mt-1 font-medium leading-relaxed">
                                    Esta acción eliminará la clave secreta de Google Authenticator, los códigos de respaldo actuales y desactivará el MFA del usuario.
                                </p>
                                <p class="text-[11px] text-amber-700 mt-2 font-medium leading-relaxed">
                                    En su próximo inicio de sesión, el usuario **estará obligado a escanear un nuevo código QR** para registrar su aplicación Authenticator desde cero.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Confirmación de Contraseña del Admin -->
                    <div class="space-y-1.5">
                        <label for="admin_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Confirmar Contraseña Admin</label>
                        <input
                            id="admin_password"
                            type="password"
                            v-model="form.password"
                            class="block w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-3 text-sm shadow-sm transition-all duration-200"
                            placeholder="Introduce tu contraseña"
                            required
                        />
                        <p v-if="errorMessage" class="text-xs text-rose-500 font-bold mt-1.5 flex items-center space-x-1">
                            <span>⚠</span>
                            <span>{{ errorMessage }}</span>
                        </p>
                        <p v-if="successMessage" class="text-xs text-emerald-600 font-bold mt-1.5 flex items-center space-x-1">
                            <span>✓</span>
                            <span>{{ successMessage }}</span>
                        </p>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex items-center space-x-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeModal"
                            class="w-1/2 py-3 border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-colors duration-200 text-sm focus:outline-none"
                            :disabled="processing"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="w-1/2 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-100 hover:shadow-indigo-200 transition-all duration-200 text-sm focus:outline-none flex items-center justify-center space-x-1.5"
                            :class="{ 'opacity-50 cursor-not-allowed': processing }"
                            :disabled="processing"
                        >
                            <span v-if="processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span>{{ modalMode === 'role' ? 'Guardar Cambios' : 'Confirmar Restablecimiento' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
