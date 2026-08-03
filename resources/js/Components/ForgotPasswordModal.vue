<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

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
    initialEmail: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'back-to-login']);

const statusMessage = ref('');

const form = useForm({
    email: props.initialEmail,
});

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            statusMessage.value = '';
            if (props.initialEmail) {
                form.email = props.initialEmail;
            }
        }
    }
);

const submit = () => {
    statusMessage.value = '';
    form.post(route('password.email'), {
        onSuccess: (page) => {
            statusMessage.value = page.props.flash?.status || 'Hemos enviado el código y enlace de recuperación a tu correo electrónico.';
        },
    });
};

const close = () => {
    emit('close');
    form.reset();
    form.clearErrors();
    statusMessage.value = '';
};

const handleBackToLogin = () => {
    close();
    emit('back-to-login');
};
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">Recuperar Contraseña</h2>
                    <p class="text-sm text-gray-500 mt-1">Ingresa tu correo para enviarte un código y enlace de recuperación</p>
                </div>
                <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div v-if="statusMessage" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="font-semibold">¡Correo enviado con éxito!</p>
                    <p class="mt-0.5 text-emerald-700">{{ statusMessage }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <InputLabel for="recovery_email" value="Correo electrónico" class="text-gray-700 font-semibold mb-1.5" />
                    <TextInput
                        id="recovery_email"
                        type="email"
                        class="block w-full border-gray-200 focus:border-tech-orange focus:ring-tech-orange rounded-xl shadow-sm h-12"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="ej: usuario@empresa.com"
                    />
                    <InputError class="mt-2 text-sm text-red-600 font-medium" :message="form.errors.email" />
                </div>

                <div class="space-y-3 pt-2">
                    <PrimaryButton
                        class="w-full h-12 justify-center bg-tech-orange hover:bg-[#CC6200] text-white font-bold rounded-xl shadow-lg shadow-tech-orange/20 transition-all active:scale-[0.98] text-base"
                        :class="{ 'opacity-25 pointer-events-none': form.processing }"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Verificando y enviando...
                        </span>
                        <span v-else>Enviar código y enlace</span>
                    </PrimaryButton>

                    <button
                        type="button"
                        @click="handleBackToLogin"
                        class="w-full py-2 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors text-center block"
                    >
                        ← Volver a iniciar sesión
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
