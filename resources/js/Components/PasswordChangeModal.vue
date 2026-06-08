<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import PasswordInput from '@/Components/PasswordInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.force-change'), {
        onSuccess: () => {
            form.reset();
        },
        onError: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-lg flex items-center justify-center z-[9999] p-4 animate-fade-in" @keydown.esc.prevent>
        <div class="bg-white rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.15)] p-8 max-w-md w-full border border-slate-100/50 flex flex-col relative transform transition-all scale-100">
            <!-- Icon/Branding -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-orange-50 mb-6 border border-orange-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-[#FF7A00]">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </div>

            <!-- Title & Description -->
            <h2 class="text-2xl font-bold text-center text-slate-800 mb-2">Actualiza tu contraseña</h2>
            <p class="text-sm text-slate-500 text-center mb-6 leading-relaxed">
                Por motivos de seguridad, debes actualizar la contraseña temporal asignada a tu cuenta antes de continuar navegando.
            </p>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- New Password -->
                <div>
                    <InputLabel for="force_password" value="Nueva contraseña" class="font-semibold text-slate-700 mb-1" />
                    <PasswordInput
                        id="force_password"
                        v-model="form.password"
                        placeholder="Mínimo 8 caracteres"
                        class="mt-1 block w-full rounded-xl border-slate-200 focus:border-[#FF7A00] focus:ring-[#FF7A00]"
                        required
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password" class="mt-1 text-xs" />
                </div>

                <!-- Password Confirmation -->
                <div>
                    <InputLabel for="force_password_confirmation" value="Confirmar nueva contraseña" class="font-semibold text-slate-700 mb-1" />
                    <PasswordInput
                        id="force_password_confirmation"
                        v-model="form.password_confirmation"
                        placeholder="Repite la contraseña"
                        class="mt-1 block w-full rounded-xl border-slate-200 focus:border-[#FF7A00] focus:ring-[#FF7A00]"
                        required
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password_confirmation" class="mt-1 text-xs" />
                </div>

                <!-- Action Buttons -->
                <div class="pt-2 flex flex-col gap-3">
                    <button
                        type="submit"
                        class="w-full bg-[#FF7A00] hover:bg-[#e06c00] text-white font-bold py-3.5 px-4 rounded-xl shadow-md shadow-orange-500/20 hover:shadow-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-[#FF7A00] focus:ring-offset-2 flex items-center justify-center gap-2"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                        <span>Actualizar Contraseña</span>
                    </button>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="w-full text-center text-sm font-semibold text-slate-400 hover:text-slate-600 transition py-2"
                    >
                        Cerrar sesión
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>
