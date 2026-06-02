<script>
import { useRateLimit } from '@/Composables/useRateLimit';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

export default {
    name: 'Verify2fa',
    components: {
        GuestLayout,
        InputError,
        InputLabel,
        PrimaryButton,
        TextInput,
        Head
    },
    data() {
        return {
            useBackupCode: false
        };
    },
    setup() {
        const form = useForm({
            code: '',
        });

        // Limita los intentos fallidos de 2FA con un temporizador en vivo
        const { lockSeconds } = useRateLimit(
            form,
            'code',
            (secs) => `Demasiados intentos. Intenta de nuevo en ${secs} segundos.`
        );

        return {
            form,
            lockSeconds
        };
    },
    computed: {
        // Alterna el marcador de entrada según el modo de validación
        inputPlaceholder() {
            return this.useBackupCode ? 'XXXX-XXXX' : '000000';
        },
        // Alterna el tamaño de la entrada
        inputMaxLength() {
            return this.useBackupCode ? 9 : 6;
        }
    },
    methods: {
        // Cambia el flujo entre código TOTP dinámico y códigos estáticos de respaldo
        toggleMode() {
            this.useBackupCode = !this.useBackupCode;
            this.form.code = '';
            this.form.clearErrors();
        },
        // Valida el código 2FA en el backend
        submit() {
            this.form.post(route('verify-2fa.store'));
        }
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Verificación de Autenticador" />

        <div class="mb-8 text-center">
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                {{ useBackupCode ? 'Código de Respaldo' : 'Código MFA' }}
            </h2>
            <p class="text-sm text-slate-400 mt-2 font-medium">
                {{ useBackupCode ? 'Ingresa uno de tus códigos de recuperación de un solo uso' : 'Ingresa el código de 6 dígitos de tu aplicación Google Authenticator' }}
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="code" class="text-center text-slate-700 font-semibold text-xs uppercase tracking-wider" :value="useBackupCode ? 'Código de Respaldo (Ej. ABCD-1234)' : 'Código de Verificación'" />
                
                <TextInput
                    id="code"
                    type="text"
                    class="mt-2.5 block w-full text-center font-mono text-2xl font-bold bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-3"
                    :class="{ 'tracking-[12px]': !useBackupCode, 'tracking-[4px]': useBackupCode }"
                    v-model="form.code"
                    :maxlength="inputMaxLength"
                    required
                    autofocus
                    autocomplete="off"
                    :placeholder="inputPlaceholder"
                    :disabled="form.processing || lockSeconds > 0"
                />

                <InputError class="mt-2 text-center" :message="form.errors.code" />
            </div>

            <div class="pt-2 space-y-4">
                <PrimaryButton class="w-full justify-center py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-100 font-bold transition-all duration-200" :class="{ 'opacity-25': form.processing || form.code.length < 6 || lockSeconds > 0 }" :disabled="form.processing || form.code.length < 6 || lockSeconds > 0">
                    {{ lockSeconds > 0 ? `Bloqueado (${lockSeconds}s)` : 'Iniciar Sesión' }}
                </PrimaryButton>

                <div class="text-center">
                    <button
                        type="button"
                        @click="toggleMode"
                        class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors duration-150 focus:outline-none"
                    >
                        {{ useBackupCode ? 'Usar código dinámico de Google Authenticator' : '¿Perdiste tu dispositivo? Usar código de respaldo' }}
                    </button>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
