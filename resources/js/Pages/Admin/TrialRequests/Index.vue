<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PasswordInput from '@/Components/PasswordInput.vue';

const props = defineProps({
    requests: Object,
    counts: Object,
    current_status: String,
});

const approvingId = ref(null);
const rejectingId = ref(null);
const deletingId = ref(null);

const approveForm = useForm({
    admin_password: '',
});

const rejectForm = useForm({
    rejection_reason: '',
});

const deleteForm = useForm({});

const startApprove = (id) => {
    approvingId.value = id;
    approveForm.reset();
};

const startReject = (id) => {
    rejectingId.value = id;
    rejectForm.reset();
};

const startDelete = (id) => {
    deletingId.value = id;
};

const cancelAction = () => {
    approvingId.value = null;
    rejectingId.value = null;
    deletingId.value = null;
};

const confirmDelete = (id) => {
    deleteForm.delete(route('admin.trial-requests.destroy', id), {
        onSuccess: () => { deletingId.value = null; },
    });
};

const canDeleteRequest = (req) => {
    return req.status === 'rejected' || isTrialExpired(req);
};

const isTrialExpired = (req) => {
    if (req.status !== 'approved' || !req.tenant) return false;
    if (!req.tenant.subscription_ends_at) return false;
    return new Date(req.tenant.subscription_ends_at) < new Date();
};

const approve = (id) => {
    approveForm.post(route('admin.trial-requests.approve', id), {
        onSuccess: () => {
            approvingId.value = null;
        },
    });
};

const reject = (id) => {
    rejectForm.post(route('admin.trial-requests.reject', id), {
        onSuccess: () => {
            rejectingId.value = null;
        },
    });
};

const filterByStatus = (status) => {
    router.get(route('admin.trial-requests.index'), { status }, { preserveState: false });
};

const statusLabel = {
    pending: 'Pendiente',
    approved: 'Aprobado',
    rejected: 'Rechazado',
};

const statusClass = {
    pending: 'bg-amber-100 text-amber-700',
    approved: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-rose-100 text-rose-700',
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('es-CL', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <Head title="Solicitudes de prueba · Admin" />

    <AdminLayout>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Solicitudes de Prueba Gratuita</h1>
                <p class="mt-1 text-sm text-slate-500">Gestiona las solicitudes de acceso al sistema.</p>
            </div>
            <div v-if="counts?.pending > 0" class="flex items-center gap-2 px-3 py-1.5 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                {{ counts.pending }} pendiente{{ counts.pending !== 1 ? 's' : '' }}
            </div>
        </div>

        <!-- Filtros por estado -->
        <div class="flex gap-2 mb-6">
            <button
                v-for="status in ['pending', 'approved', 'rejected']"
                :key="status"
                @click="filterByStatus(status)"
                :class="[
                    'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                    current_status === status
                        ? 'bg-slate-900 text-white'
                        : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'
                ]"
            >
                {{ statusLabel[status] }}
                <span class="ml-1.5 text-xs opacity-70">({{ counts?.[status] ?? 0 }})</span>
            </button>
        </div>

        <!-- Tabla -->
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 overflow-hidden">
            <div v-if="requests?.data?.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="py-3 pl-6 pr-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Solicitante</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Negocio</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Contacto</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Plan</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Estado</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Fecha</th>
                            <th class="py-3 pl-3 pr-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template v-for="req in requests.data" :key="req.id">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-4 pl-6 pr-3">
                                    <p class="text-sm font-semibold text-slate-900">
                                        <span v-if="req.country === 'CO'" class="mr-1" title="Colombia">🇨🇴</span>
                                        <span v-else class="mr-1" title="Chile">🇨🇱</span>
                                        {{ req.name }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ req.email }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="text-sm text-slate-900 font-medium">{{ req.business_name }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ req.business_type }}</p>
                                </td>
                                <td class="px-3 py-4">
                                    <p class="text-sm text-slate-700">{{ req.phone }}</p>
                                    <p v-if="req.city" class="text-xs text-slate-500 mt-0.5">{{ req.city }}</p>
                                    <p v-if="req.users_estimate" class="text-xs text-slate-500">{{ req.users_estimate }} usuarios</p>
                                </td>
                                <td class="px-3 py-4">
                                    <span v-if="req.requested_plan" class="text-xs text-slate-700 capitalize font-medium">{{ req.requested_plan }}</span>
                                    <span v-else class="text-xs text-slate-400">—</span>
                                </td>
                                <td class="px-3 py-4">
                                    <span :class="['inline-flex px-2.5 py-1 rounded-full text-xs font-semibold', statusClass[req.status]]">
                                        {{ statusLabel[req.status] }}
                                    </span>
                                    <div v-if="req.tenant" class="mt-1">
                                        <span class="text-xs text-slate-400">Taller: </span>
                                        <span class="text-xs text-emerald-700 font-medium">{{ req.tenant.name }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-xs text-slate-500 whitespace-nowrap">{{ formatDate(req.created_at) }}</td>
                                <td class="py-4 pl-3 pr-6 text-right space-x-2 whitespace-nowrap">
                                    <template v-if="current_status === 'pending'">
                                        <button
                                            @click="startApprove(req.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-lg transition-colors"
                                        >
                                            Aprobar
                                        </button>
                                        <button
                                            @click="startReject(req.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-rose-50 text-rose-600 text-xs font-semibold rounded-lg ring-1 ring-rose-200 transition-colors"
                                        >
                                            Rechazar
                                        </button>
                                    </template>
                                    <button
                                        v-if="canDeleteRequest(req)"
                                        @click="startDelete(req.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-rose-50 text-rose-700 text-xs font-semibold rounded-lg ring-1 ring-rose-300 transition-colors"
                                    >
                                        {{ req.tenant ? 'Eliminar definitivamente' : 'Eliminar solicitud' }}
                                    </button>
                                </td>
                            </tr>

                            <!-- Panel de aprobación inline -->
                            <tr v-if="approvingId === req.id" class="bg-emerald-50">
                                <td colspan="7" class="px-6 py-4">
                                    <div class="flex items-end gap-4 flex-wrap">
                                        <div class="flex-1 min-w-64">
                                            <p class="text-sm font-semibold text-slate-800 mb-1">Aprobar solicitud de <strong>{{ req.name }}</strong></p>
                                            <p class="text-xs text-slate-500 mb-3">
                                                Se creará el taller "{{ req.business_name }}" y el usuario administrador con el email {{ req.email }}.
                                                Se activará un trial de 14 días con plan Básico.
                                            </p>
                                            <label class="block text-xs font-semibold text-slate-700 mb-1">Contraseña provisional del administrador</label>
                                            <PasswordInput
                                                v-model="approveForm.admin_password"
                                                placeholder="Mínimo 8 caracteres"
                                                class="w-full max-w-xs rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 text-slate-900"
                                                :class="{ 'border-rose-400': approveForm.errors.admin_password }"
                                            />
                                            <p v-if="approveForm.errors.admin_password" class="mt-1 text-xs text-rose-600">{{ approveForm.errors.admin_password }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                @click="approve(req.id)"
                                                :disabled="approveForm.processing"
                                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-60"
                                            >
                                                {{ approveForm.processing ? 'Aprobando…' : 'Confirmar aprobación' }}
                                            </button>
                                            <button @click="cancelAction" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800 font-medium transition-colors">
                                                Cancelar
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Panel de rechazo inline -->
                            <tr v-if="rejectingId === req.id" class="bg-rose-50">
                                <td colspan="7" class="px-6 py-4">
                                    <div class="flex items-end gap-4 flex-wrap">
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-slate-800 mb-1">Rechazar solicitud de <strong>{{ req.name }}</strong></p>
                                            <label class="block text-xs font-semibold text-slate-700 mb-1">Motivo del rechazo <span class="text-slate-400 font-normal">(opcional)</span></label>
                                            <textarea
                                                v-model="rejectForm.rejection_reason"
                                                rows="2"
                                                placeholder="Ej: No cumple con los requisitos mínimos…"
                                                class="w-full max-w-md rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-400 resize-none"
                                            ></textarea>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                @click="reject(req.id)"
                                                :disabled="rejectForm.processing"
                                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-60"
                                            >
                                                {{ rejectForm.processing ? 'Rechazando…' : 'Confirmar rechazo' }}
                                            </button>
                                            <button @click="cancelAction" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800 font-medium transition-colors">
                                                Cancelar
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Panel de eliminación definitiva -->
                            <tr v-if="deletingId === req.id" class="bg-rose-50">
                                <td colspan="7" class="px-6 py-4">
                                    <div class="flex items-center gap-4 flex-wrap">
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-rose-800 mb-1">
                                                {{ req.tenant ? 'Eliminar definitivamente a' : 'Eliminar solicitud de' }}
                                                <strong>{{ req.name }}</strong>
                                            </p>
                                            <p v-if="req.tenant" class="text-xs text-rose-700">
                                                Se borrará el taller "<strong>{{ req.tenant?.name }}</strong>", su usuario ({{ req.email }}) y todos sus datos.
                                                El correo quedará libre para una nueva solicitud de prueba. Esta acción no se puede deshacer.
                                            </p>
                                            <p v-else class="text-xs text-rose-700">
                                                Se borrará esta solicitud rechazada y el correo <strong>{{ req.email }}</strong> quedará libre para una nueva solicitud de prueba.
                                                Esta acción no se puede deshacer.
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                @click="confirmDelete(req.id)"
                                                :disabled="deleteForm.processing"
                                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-60"
                                            >
                                                {{ deleteForm.processing ? 'Eliminando…' : 'Sí, eliminar' }}
                                            </button>
                                            <button @click="cancelAction" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800 font-medium transition-colors">
                                                Cancelar
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Mensaje opcional -->
                            <tr v-if="req.message" class="bg-slate-50/50">
                                <td colspan="7" class="px-6 py-2">
                                    <p class="text-xs text-slate-500 italic">
                                        <span class="font-semibold not-italic text-slate-600">Mensaje: </span>{{ req.message }}
                                    </p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Paginación -->
                <div v-if="requests.last_page > 1" class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-xs text-slate-500">
                        Mostrando {{ requests.from }}–{{ requests.to }} de {{ requests.total }}
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="requests.prev_page_url"
                            :href="requests.prev_page_url"
                            class="px-3 py-1.5 text-xs font-medium text-slate-600 bg-white ring-1 ring-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
                        >
                            ← Anterior
                        </Link>
                        <Link
                            v-if="requests.next_page_url"
                            :href="requests.next_page_url"
                            class="px-3 py-1.5 text-xs font-medium text-slate-600 bg-white ring-1 ring-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
                        >
                            Siguiente →
                        </Link>
                    </div>
                </div>
            </div>

            <div v-else class="py-16 text-center">
                <svg class="mx-auto mb-4 w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm text-slate-400 font-medium">No hay solicitudes {{ statusLabel[current_status]?.toLowerCase() }}s</p>
            </div>
        </div>
    </AdminLayout>
</template>
