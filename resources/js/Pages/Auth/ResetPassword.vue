<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Restablecer Contraseña" />

        <div class="mb-6 text-center">
            <h2 class="text-2xl font-black text-gray-900">Restablecer Contraseña</h2>
            <p class="text-sm text-gray-500 mt-1">Ingresa tu nueva contraseña para actualizar el acceso a tu cuenta.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="token" value="Código / Token de verificación" class="font-semibold text-gray-700" />

                <TextInput
                    id="token"
                    type="text"
                    class="mt-1 block w-full bg-gray-50 font-mono text-sm border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-11"
                    v-model="form.token"
                    required
                    readonly
                />

                <InputError class="mt-2" :message="form.errors.token" />
            </div>

            <div>
                <InputLabel for="email" value="Correo electrónico" class="font-semibold text-gray-700" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-11"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Nueva contraseña" class="font-semibold text-gray-700" />

                <PasswordInput
                    id="password"
                    class="mt-1 block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-11"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel
                    for="password_confirmation"
                    value="Confirmar nueva contraseña"
                    class="font-semibold text-gray-700"
                />

                <PasswordInput
                    id="password_confirmation"
                    class="mt-1 block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-11"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="pt-4">
                <PrimaryButton
                    class="w-full h-12 justify-center bg-tech-orange hover:bg-[#CC6200] text-white font-bold rounded-xl shadow-lg shadow-tech-orange/20 transition-all text-base"
                    :class="{ 'opacity-25 pointer-events-none': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Guardando nueva contraseña...</span>
                    <span v-else>Restablecer Contraseña</span>
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
