<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import AdminDashboard from '@/Components/AdminDashboard.vue';
import UserDashboard from '@/Components/UserDashboard.vue';
import GuestDashboard from '@/Components/GuestDashboard.vue';

export default {
    name: 'Dashboard',
    components: {
        AuthenticatedLayout,
        Head,
        AdminDashboard,
        UserDashboard,
        GuestDashboard
    },
    props: {
        users: {
            type: Array,
            default: () => []
        },
        securityLogs: {
            type: Array,
            default: () => []
        },
        personalLogs: {
            type: Array,
            default: () => []
        }
    },
    computed: {
        // Obtiene el usuario autenticado desde las props globales de Inertia
        user() {
            return this.$page.props.auth.user;
        },
        // Define el título del panel según el rol correspondiente
        dashboardTitle() {
            if (this.user.role === 'admin') return 'Panel - Administrador';
            if (this.user.role === 'guest') return 'Panel - Invitado Limitado';
            return 'Panel de Control de Usuario';
        }
    }
};
</script>

<template>
    <Head :title="dashboardTitle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-bold text-2xl text-slate-800 leading-tight">
                    {{ dashboardTitle }}
                </h2>
                <div class="flex items-center space-x-2 text-xs">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-slate-500 font-medium">Sesión Protegida 3FA</span>
                </div>
            </div>
        </template>

        <div class="py-10 bg-gradient-to-tr from-slate-50 via-white to-blue-50/20 min-h-[calc(100vh-65px)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Renderiza el panel correcto según el rol del usuario pasándole los datos reales correspondientes -->
                <AdminDashboard 
                    v-if="user.role === 'admin'" 
                    :users="users" 
                    :security-logs="securityLogs" 
                />
                
                <UserDashboard 
                    v-else-if="user.role === 'user'" 
                    :personal-logs="personalLogs" 
                />
                
                <GuestDashboard 
                    v-else-if="user.role === 'guest'" 
                />
                
                <div v-else class="p-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        Error: Rol de usuario no reconocido.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
