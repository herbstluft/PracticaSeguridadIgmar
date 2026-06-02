<script>
import { useRateLimit } from '@/Composables/useRateLimit';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

export default {
    name: 'Login',
    components: {
        Checkbox,
        GuestLayout,
        InputError,
        InputLabel,
        PrimaryButton,
        TextInput,
        Head,
        Link
    },
    props: {
        canResetPassword: {
            type: Boolean,
        },
        status: {
            type: String,
        },
    },
    setup() {
        const form = useForm({
            email: '',
            password: '',
            remember: false,
        });

        // Temporizador en vivo para la limitación de tasa (Rate Limiting)
        const { lockSeconds } = useRateLimit(
            form,
            'email',
            (secs) => `Demasiados intentos de inicio de sesión. Por favor, intenta de nuevo en ${secs} segundos.`
        );

        return {
            form,
            lockSeconds
        };
    },
    methods: {
        // Procesa el inicio de sesión del Paso 1 (Credenciales)
        submit() {
            this.form.post(route('login'), {
                onFinish: () => this.form.reset('password'),
            });
        }
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar Sesión" />

        <div class="mb-8 text-center">
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Acceso Seguro</h2>
            <p class="text-sm text-slate-400 mt-2 font-medium">Completa las credenciales de tu cuenta</p>
        </div>

        <div v-if="status" class="mb-4 font-semibold text-sm text-emerald-600 bg-emerald-50/70 p-3.5 rounded-xl border border-emerald-100">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Correo Electrónico -->
            <div>
                <InputLabel for="email" value="Correo Electrónico" class="text-slate-700 font-semibold text-xs uppercase tracking-wider" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1.5 block w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-3 text-sm shadow-sm transition-all duration-200"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="correo@ejemplo.com"
                    :disabled="lockSeconds > 0"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Contraseña -->
            <div>
                <div class="flex justify-between items-center">
                    <InputLabel for="password" value="Contraseña" class="text-slate-700 font-semibold text-xs uppercase tracking-wider" />
                </div>
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1.5 block w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-3 text-sm shadow-sm transition-all duration-200"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    :disabled="lockSeconds > 0"
                />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <!-- Recordar sesión y recuperar contraseña -->
            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center cursor-pointer select-none">
                    <Checkbox name="remember" v-model:checked="form.remember" :disabled="lockSeconds > 0" />
                    <span class="ms-2 text-slate-500 font-medium">Recordarme en este equipo</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="font-semibold text-indigo-600 hover:text-indigo-800 transition-colors duration-150"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <!-- Botón de acción principal -->
            <div class="pt-2">
                <PrimaryButton class="w-full justify-center py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-100 font-bold transition-all duration-200" :class="{ 'opacity-25': form.processing || lockSeconds > 0 }" :disabled="form.processing || lockSeconds > 0">
                    {{ lockSeconds > 0 ? `Bloqueado (${lockSeconds}s)` : 'Ingresar' }}
                </PrimaryButton>
            </div>

            <!-- Enlace de registro -->
            <div class="text-center pt-2">
                <span class="text-xs text-slate-400 font-medium">¿No tienes cuenta? </span>
                <Link
                    :href="route('register')"
                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors duration-150"
                >
                    Regístrate aquí
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
