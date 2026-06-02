<script>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import QRCode from 'qrcode';

export default {
    name: 'Setup2fa',
    components: {
        GuestLayout,
        InputError,
        InputLabel,
        PrimaryButton,
        TextInput,
        Head
    },
    props: {
        secretKey: String,
        qrCodeUrl: String,
        backupCodes: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            form: useForm({
                code: '',
            })
        };
    },
    mounted() {
        // Genera el código QR de manera local en el canvas cuando se monta el componente
        if (this.$refs.qrCanvas && this.qrCodeUrl) {
            QRCode.toCanvas(this.$refs.qrCanvas, this.qrCodeUrl, {
                width: 200,
                margin: 1,
                color: {
                    dark: '#0f172a',
                    light: '#ffffff'
                }
            }, (error) => {
                if (error) console.error('Error generando código QR:', error);
            });
        }
    },
    methods: {
        // Envía el primer código dinámico del autenticador para confirmar y activar el 2FA
        submit() {
            this.form.post(route('verify-2fa.store'));
        }
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Configurar Autenticador" />

        <div class="mb-6 text-center">
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Activar MFA</h2>
            <p class="text-sm text-slate-400 mt-2 font-medium">
                Vincula tu cuenta con Google Authenticator
            </p>
        </div>

        <div class="space-y-6">
            <!-- Paso 1: Escanear QR -->
            <div class="flex flex-col items-center bg-slate-50 p-6 rounded-2xl border border-slate-100">
                <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest mb-4">
                    1. Escanea el código QR
                </span>
                
                <div class="p-3.5 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center">
                    <canvas ref="qrCanvas"></canvas>
                </div>
            </div>

            <!-- Sección de Códigos de Respaldo -->
            <div v-if="backupCodes && backupCodes.length > 0" class="p-5 bg-amber-50/70 border border-amber-100 rounded-2xl space-y-3">
                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-widest block">
                    ¡IMPORTANTE! Códigos de Respaldo
                </span>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                    Guarda estos códigos en un lugar seguro. Si pierdes acceso a tu dispositivo, podrás usarlos para entrar a tu cuenta. **Cada código sirve una sola vez**.
                </p>
                <div class="grid grid-cols-3 gap-2 text-center font-mono text-xs font-bold text-slate-950 pt-1">
                    <div v-for="c in backupCodes" :key="c" class="bg-white border border-amber-200/50 py-1.5 px-2 rounded-lg shadow-sm select-all">
                        {{ c }}
                    </div>
                </div>
            </div>

            <!-- Paso 2: Entrada Manual en caso de falla -->
            <div class="text-[11px] text-center text-slate-400 font-medium">
                <span class="block">¿No puedes escanear el código?</span>
                <span>Ingresa esta clave en tu aplicación:</span>
                <div class="mt-1.5 font-mono text-xs font-bold text-slate-900 select-all bg-slate-50 py-1.5 px-3 rounded-lg border border-slate-200 inline-block tracking-wider">
                    {{ secretKey }}
                </div>
            </div>

            <!-- Paso 3: Confirmación de sincronización -->
            <form @submit.prevent="submit" class="border-t border-slate-100 pt-6 space-y-4">
                <span class="block text-[10px] font-bold text-indigo-600 uppercase tracking-widest text-center mb-1">
                    2. Confirma la Sincronización
                </span>

                <div>
                    <InputLabel for="code" class="text-center text-slate-700 font-semibold text-xs uppercase tracking-wider" value="Código de 6 dígitos" />
                    
                    <TextInput
                        id="code"
                        type="text"
                        class="mt-2 block w-full text-center tracking-[12px] font-mono text-2xl font-bold bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-3"
                        v-model="form.code"
                        maxlength="6"
                        required
                        autofocus
                        autocomplete="off"
                        placeholder="000000"
                    />

                    <InputError class="mt-2 text-center" :message="form.errors.code" />
                </div>

                <div class="pt-2">
                    <PrimaryButton class="w-full justify-center py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-100 font-bold transition-all duration-200" :class="{ 'opacity-25': form.processing || form.code.length !== 6 }" :disabled="form.processing || form.code.length !== 6">
                        Confirmar y Activar
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
