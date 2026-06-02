<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperación de Contraseña" />

        <div class="mb-8 text-center">
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Recuperar Acceso</h2>
            <p class="text-sm text-slate-400 mt-2 font-medium px-4">
                Ingresa tu correo para recibir un enlace seguro de recuperación
            </p>
        </div>

        <div v-if="status" class="mb-4 font-semibold text-sm text-emerald-600 bg-emerald-50/70 p-3.5 rounded-xl border border-emerald-100">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Email -->
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
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Action Button -->
            <div class="pt-2">
                <PrimaryButton class="w-full justify-center py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-lg shadow-indigo-100 font-bold transition-all duration-200" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Enviar Enlace Seguro
                </PrimaryButton>
            </div>

            <!-- Back to Login -->
            <div class="text-center pt-2">
                <Link
                    :href="route('login')"
                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors duration-150"
                >
                    Regresar al inicio de sesión
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
