<script>
import { useRateLimit } from '@/Composables/useRateLimit';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

export default {
    name: 'VerifyOtp',
    components: {
        GuestLayout,
        InputError,
        InputLabel,
        PrimaryButton,
        Head
    },
    props: {
        email: String,
        expiresIn: Number,
        devOtpCode: String,
    },
    data() {
        return {
            digits: ['', '', '', '', '', ''],
            timerSeconds: this.expiresIn !== undefined && this.expiresIn !== null ? this.expiresIn : 60,
            timerActive: true,
            countdownInterval: null,
            resendForm: useForm({
                email: this.email
            })
        };
    },
    setup(props) {
        const form = useForm({
            email: props.email,
            code: '',
        });

        // Limita los intentos fallidos con un timer en vivo en el campo de entrada
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
        // Convierte el tiempo restante a formato MM:SS
        timerText() {
            const mins = Math.floor(this.timerSeconds / 60);
            const secs = this.timerSeconds % 60;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
    },
    watch: {
        // Detecta actualizaciones de props desde el servidor (p.ej., reenvíos)
        expiresIn(newVal) {
            if (newVal !== undefined && newVal !== null) {
                this.timerSeconds = newVal;
                if (newVal > 0) {
                    this.timerActive = true;
                    if (!this.countdownInterval) {
                        this.countdownInterval = setInterval(this.decrementTimer, 1000);
                    }
                } else {
                    this.timerActive = false;
                    if (this.countdownInterval) {
                        clearInterval(this.countdownInterval);
                        this.countdownInterval = null;
                    }
                }
            }
        }
    },
    mounted() {
        this.countdownInterval = setInterval(this.decrementTimer, 1000);
        if (this.$refs.digitInputs && this.$refs.digitInputs[0]) {
            this.$refs.digitInputs[0].focus();
        }
    },
    beforeUnmount() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
    },
    methods: {
        // Reduce los segundos y expira el código si llega a cero
        decrementTimer() {
            if (this.timerSeconds > 0) {
                this.timerSeconds--;
            } else {
                this.timerActive = false;
                if (this.countdownInterval) {
                    clearInterval(this.countdownInterval);
                    this.countdownInterval = null;
                }
                router.visit(route('login'), {
                    data: { status: 'El código OTP ha expirado. Por favor, inicia sesión de nuevo.' }
                });
            }
        },
        // Salta automáticamente al siguiente input al escribir
        handleInput(index, event) {
            const val = event.target.value;
            if (!/^[0-9]$/.test(val)) {
                this.digits[index] = '';
                return;
            }

            this.digits[index] = val;
            this.form.code = this.digits.join('');

            if (index < 5 && val) {
                this.$refs.digitInputs[index + 1].focus();
            }
        },
        // Controla la navegación del retroceso en los campos
        handleKeyDown(index, event) {
            if (event.key === 'Backspace') {
                if (!this.digits[index] && index > 0) {
                    this.digits[index - 1] = '';
                    this.$refs.digitInputs[index - 1].focus();
                } else {
                    this.digits[index] = '';
                }
                this.form.code = this.digits.join('');
            }
        },
        // Soporte para pegar el código completo de 6 dígitos
        handlePaste(event) {
            const pasteData = event.clipboardData.getData('text');
            if (/^[0-9]{6}$/.test(pasteData)) {
                const chars = pasteData.split('');
                for (let i = 0; i < 6; i++) {
                    this.digits[i] = chars[i];
                    if (this.$refs.digitInputs[i]) {
                        this.$refs.digitInputs[i].value = chars[i];
                    }
                }
                this.form.code = pasteData;
                this.$refs.digitInputs[5].focus();
            }
            event.preventDefault();
        },
        // Valida el OTP en el backend
        submit() {
            this.form.post(route('verify-otp.store'));
        },
        // Solicita un nuevo código OTP al servidor
        resendCode() {
            this.resendForm.post(route('resend-otp'), {
                onSuccess: () => {
                    this.timerSeconds = 60;
                    this.timerActive = true;
                    if (this.countdownInterval) clearInterval(this.countdownInterval);
                    this.countdownInterval = setInterval(this.decrementTimer, 1000);
                    this.digits = ['', '', '', '', '', ''];
                    this.form.code = '';
                    if (this.$refs.digitInputs && this.$refs.digitInputs[0]) {
                        this.$refs.digitInputs[0].focus();
                    }
                }
            });
        }
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Verificación OTP" />

        <div class="mb-8 text-center">
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Verificación OTP</h2>
            <p class="text-sm text-slate-400 mt-2 font-medium">
                Hemos enviado un código a tu correo
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Entradas de 6 dígitos -->
            <div>
                <InputLabel class="text-center mb-3 text-slate-700 font-semibold text-xs uppercase tracking-wider" value="Ingresa el código OTP de 6 dígitos" />
                <div class="flex justify-center space-x-2" @paste="handlePaste">
                    <input
                        v-for="(digit, idx) in digits"
                        :key="idx"
                        ref="digitInputs"
                        type="text"
                        maxlength="1"
                        class="w-12 h-14 text-center text-2xl font-bold border border-slate-200 bg-slate-50 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-all duration-150"
                        :value="digit"
                        :disabled="!timerActive || form.processing || lockSeconds > 0"
                        @input="handleInput(idx, $event)"
                        @keydown="handleKeyDown(idx, $event)"
                    />
                </div>
                <InputError class="mt-3 text-center" :message="form.errors.code" />
            </div>

            <!-- Temporizador y botón de reenvío -->
            <div class="flex flex-col items-center justify-center space-y-3 pt-2">
                <div class="flex items-center space-x-2 text-xs text-slate-400 font-medium">
                    <span>Vencimiento del enlace y código:</span>
                    <span class="font-bold font-mono text-slate-900" :class="{'text-rose-500': timerSeconds < 60}">
                        {{ timerText }}
                    </span>
                </div>

                <!-- Botón de reenvío -->
                <button
                    type="button"
                    @click="resendCode"
                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors duration-150 focus:outline-none"
                    :disabled="resendForm.processing || lockSeconds > 0"
                >
                    {{ resendForm.processing ? 'Reenviando...' : 'Reenviar código de verificación' }}
                </button>
                <InputError :message="resendForm.errors.email" />
            </div>

            <div class="flex justify-center pt-2">
                <PrimaryButton class="w-full justify-center py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-100 font-bold transition-all duration-200" :class="{ 'opacity-25': form.processing || !timerActive || form.code.length !== 6 || lockSeconds > 0 }" :disabled="form.processing || !timerActive || form.code.length !== 6 || lockSeconds > 0">
                    {{ lockSeconds > 0 ? `Bloqueado (${lockSeconds}s)` : 'Validar OTP' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
