<script>
import { useRateLimit } from '@/Composables/useRateLimit';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

export default {
    name: 'Register',
    components: {
        GuestLayout,
        InputError,
        InputLabel,
        PrimaryButton,
        TextInput,
        Head,
        Link
    },
    setup() {
        const form = useForm({
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            role: 'user',
        });

        // Temporizador de rate limit para prevenir spam en el registro
        const { lockSeconds } = useRateLimit(
            form,
            'email',
            (secs) => `Demasiados intentos de registro desde esta red. Por favor, espera ${secs} segundos.`
        );

        return {
            form,
            lockSeconds
        };
    },
    computed: {
        // Valida la longitud mínima requerida
        hasMinLength() {
            return this.form.password.length >= 8;
        },
        // Valida si contiene mayúsculas y minúsculas
        hasMixedCase() {
            return /[a-z]/.test(this.form.password) && /[A-Z]/.test(this.form.password);
        },
        // Valida si contiene al menos un número
        hasNumber() {
            return /[0-9]/.test(this.form.password);
        },
        // Valida si contiene caracteres especiales
        hasSymbol() {
            return /[^A-Za-z0-9]/.test(this.form.password);
        },
        // Calcula la puntuación acumulativa de la contraseña
        passwordStrength() {
            let score = 0;
            if (this.hasMinLength) score += 25;
            if (this.hasMixedCase) score += 25;
            if (this.hasNumber) score += 25;
            if (this.hasSymbol) score += 25;
            return score;
        },
        // Retorna el color de la barra según la fuerza
        strengthColor() {
            const score = this.passwordStrength;
            if (score < 50) return 'bg-rose-500';
            if (score < 100) return 'bg-amber-500';
            return 'bg-emerald-500';
        },
        // Retorna el texto evaluativo correspondiente
        strengthText() {
            const score = this.passwordStrength;
            if (score === 0) return 'Vacía';
            if (score < 50) return 'Débil';
            if (score < 100) return 'Moderada';
            return 'Excelente';
        }
    },
    methods: {
        // Registra el usuario enviando el formulario al endpoint correspondiente
        submit() {
            this.form.post(route('register'), {
                onFinish: () => this.form.reset('password', 'password_confirmation'),
            });
        }
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Registro de Seguridad" />

        <div class="mb-8 text-center">
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Crear Cuenta</h2>
            <p class="text-sm text-slate-400 mt-2 font-medium">Habilita la autenticación multifactor</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Nombre Completo -->
            <div>
                <InputLabel for="name" value="Nombre Completo" class="text-slate-700 font-semibold text-xs uppercase tracking-wider" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1.5 block w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-3 text-sm shadow-sm transition-all duration-200"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Ej. Juan Pérez"
                    :disabled="lockSeconds > 0"
                />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <!-- Correo Electrónico -->
            <div>
                <InputLabel for="email" value="Correo Electrónico" class="text-slate-700 font-semibold text-xs uppercase tracking-wider" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1.5 block w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-3 text-sm shadow-sm transition-all duration-200"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="correo@ejemplo.com"
                    :disabled="lockSeconds > 0"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Selector de Roles -->
            <div>
                <InputLabel for="role" value="Rol de Demostración" class="text-slate-700 font-semibold text-xs uppercase tracking-wider" />
                <select
                    id="role"
                    v-model="form.role"
                    class="mt-1.5 block w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-3 text-sm shadow-sm transition-all duration-200"
                    required
                    :disabled="lockSeconds > 0"
                >
                    <option value="user">Usuario Estándar</option>
                    <option value="admin">Administrador de Seguridad</option>
                    <option value="guest">Usuario Invitado</option>
                </select>
                <InputError class="mt-1" :message="form.errors.role" />
            </div>

            <!-- Contraseña -->
            <div>
                <InputLabel for="password" value="Contraseña" class="text-slate-700 font-semibold text-xs uppercase tracking-wider" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1.5 block w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-3 text-sm shadow-sm transition-all duration-200"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    :disabled="lockSeconds > 0"
                />
                <InputError class="mt-1" :message="form.errors.password" />

                <!-- Indicador de seguridad de contraseña -->
                <div v-if="form.password" class="mt-3 space-y-2 bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 font-medium">Fortaleza:</span>
                        <span class="font-bold" :class="{'text-rose-500': passwordStrength < 50, 'text-amber-500': passwordStrength >= 50 && passwordStrength < 100, 'text-emerald-500': passwordStrength === 100}">
                            {{ strengthText }}
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 h-1 rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-300" :class="strengthColor" :style="{ width: `${passwordStrength}%` }"></div>
                    </div>
                    <!-- Pautas de Contraseña Fuerte -->
                    <div class="grid grid-cols-2 gap-1.5 text-[10px] mt-1 text-slate-500 font-medium">
                        <div class="flex items-center space-x-1">
                            <span :class="hasMinLength ? 'text-emerald-500' : 'text-slate-300'">●</span>
                            <span :class="{'text-slate-800 font-semibold': hasMinLength}">Mínimo 8 caracteres</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span :class="hasMixedCase ? 'text-emerald-500' : 'text-slate-300'">●</span>
                            <span :class="{'text-slate-800 font-semibold': hasMixedCase}">Mayús. y minús.</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span :class="hasNumber ? 'text-emerald-500' : 'text-slate-300'">●</span>
                            <span :class="{'text-slate-800 font-semibold': hasNumber}">Un número</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span :class="hasSymbol ? 'text-emerald-500' : 'text-slate-300'">●</span>
                            <span :class="{'text-slate-800 font-semibold': hasSymbol}">Un símbolo (!@#...)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirmación de Contraseña -->
            <div>
                <InputLabel for="password_confirmation" value="Confirmar Contraseña" class="text-slate-700 font-semibold text-xs uppercase tracking-wider" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1.5 block w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-3 text-sm shadow-sm transition-all duration-200"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    :disabled="lockSeconds > 0"
                />
                <InputError class="mt-1" :message="form.errors.password_confirmation" />
            </div>

            <!-- Botón de Envío de Registro -->
            <div class="pt-3">
                <PrimaryButton class="w-full justify-center py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-100 font-bold transition-all duration-200" :class="{ 'opacity-25': form.processing || lockSeconds > 0 }" :disabled="form.processing || lockSeconds > 0">
                    {{ lockSeconds > 0 ? `Bloqueado (${lockSeconds}s)` : 'Registrar Cuenta' }}
                </PrimaryButton>
            </div>

            <!-- Enlace para Volver a Iniciar Sesión -->
            <div class="text-center pt-2">
                <span class="text-xs text-slate-400 font-medium">¿Ya tienes cuenta? </span>
                <Link
                    :href="route('login')"
                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors duration-150"
                >
                    Inicia sesión aquí
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
