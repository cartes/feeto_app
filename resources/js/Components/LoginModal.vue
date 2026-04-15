<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: 'md',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onSuccess: () => {
            close();
        },
    });
};

const close = () => {
    emit('close');
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">Iniciar Sesión</h2>
                    <p class="text-sm text-gray-500 mt-1">Ingresa tus credenciales para continuar</p>
                </div>
                <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel for="modal_email" value="Correo electrónico" class="text-gray-700 font-semibold mb-1.5" />
                    <TextInput
                        id="modal_email"
                        type="email"
                        class="block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-12"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="ej: nombre@empresa.com"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <InputLabel for="modal_password" value="Contraseña" class="text-gray-700 font-semibold" />
                        <a href="#" class="text-xs font-semibold text-tech-orange hover:text-[#e8920d] transition-colors">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                    <TextInput
                        id="modal_password"
                        type="password"
                        class="block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-12"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" id="modal_remember" />
                    <label for="modal_remember" class="ms-2 text-sm text-gray-600 font-medium cursor-pointer selection:bg-transparent">
                        Recordar sesión
                    </label>
                </div>

                <div class="pt-2">
                    <PrimaryButton
                        class="w-full h-12 justify-center bg-tech-orange hover:bg-[#e8920d] text-white font-bold rounded-xl shadow-lg shadow-tech-orange/20 transition-all active:scale-[0.98] text-base"
                        :class="{ 'opacity-25 pointer-events-none': form.processing }"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Iniciando...
                        </span>
                        <span v-else>Entrar a mi cuenta</span>
                    </PrimaryButton>
                </div>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-500">
                    ¿No tienes una cuenta?
                    <a href="#" class="font-bold text-gray-900 hover:text-tech-orange transition-colors">Empieza gratis por 14 días</a>
                </p>
            </div>
        </div>
    </Modal>
</template>
