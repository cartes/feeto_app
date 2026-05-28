<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
    files: Array,
});

const localFiles = ref([...props.files]);
const uploading = ref(false);
const uploadError = ref(null);
const editingFile = ref(null);
const confirmDelete = ref(null);
const editForm = ref({ alt_text: '', description: '' });
const saving = ref(false);

function openEdit(file) {
    editingFile.value = file;
    editForm.value = { alt_text: file.alt_text || '', description: file.description || '' };
}

async function saveEdit() {
    saving.value = true;
    try {
        await axios.put(route('admin.media-library.update', editingFile.value.id), editForm.value);
        const idx = localFiles.value.findIndex(f => f.id === editingFile.value.id);
        if (idx !== -1) {
            localFiles.value[idx] = { ...localFiles.value[idx], ...editForm.value };
        }
        editingFile.value = null;
    } finally {
        saving.value = false;
    }
}

async function uploadFiles(event) {
    const files = Array.from(event.target.files);
    if (!files.length) return;
    uploading.value = true;
    uploadError.value = null;

    for (const file of files) {
        const form = new FormData();
        form.append('file', file);
        try {
            const res = await axios.post(route('admin.media-library.store'), form, {
                headers: { 'Content-Type': 'multipart/form-data', 'X-Inertia': false },
            });
            localFiles.value.unshift(res.data.file);
        } catch (e) {
            uploadError.value = e.response?.data?.message || 'Error al subir imagen';
        }
    }
    uploading.value = false;
    event.target.value = '';
}

function destroy(file) {
    router.delete(route('admin.media-library.destroy', file.id), {
        onSuccess: () => {
            localFiles.value = localFiles.value.filter(f => f.id !== file.id);
            confirmDelete.value = null;
        },
    });
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function copyUrl(url) {
    navigator.clipboard.writeText(url);
}
</script>

<template>
    <Head title="Banco de Imágenes" />

    <AdminLayout>
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Banco de Imágenes</h1>
                <p class="mt-1 text-sm text-slate-500">Sube, organiza y reutiliza imágenes en tus artículos.</p>
            </div>
            <label class="cursor-pointer inline-flex items-center gap-2 rounded-lg bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-600 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Subir imágenes
                <input type="file" accept="image/*" multiple class="hidden" @change="uploadFiles" :disabled="uploading" />
            </label>
        </div>

        <!-- Upload states -->
        <div v-if="uploading" class="mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200 flex items-center gap-2 text-sm text-amber-700">
            <svg class="w-4 h-4 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Optimizando y subiendo imágenes...
        </div>
        <div v-if="uploadError" class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
            {{ uploadError }}
        </div>

        <!-- Empty state -->
        <div v-if="localFiles.length === 0" class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 flex flex-col items-center justify-center py-20 text-slate-400">
            <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
            <p class="font-medium">No hay imágenes en el banco</p>
            <p class="text-sm mt-1">Sube imágenes usando el botón superior</p>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <div
                v-for="file in localFiles"
                :key="file.id"
                class="group bg-white rounded-xl ring-1 ring-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow"
            >
                <div class="aspect-square relative bg-slate-100">
                    <img :src="file.url" :alt="file.alt_text || ''" class="w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
                        <button type="button" @click="openEdit(file)" title="Editar metadatos"
                            class="w-8 h-8 rounded-full bg-white/90 text-slate-700 flex items-center justify-center hover:bg-white shadow transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <button type="button" @click="copyUrl(file.url)" title="Copiar URL"
                            class="w-8 h-8 rounded-full bg-white/90 text-slate-700 flex items-center justify-center hover:bg-white shadow transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                        <button type="button" @click="confirmDelete = file" title="Eliminar"
                            class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 shadow transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                <div class="p-2">
                    <p class="text-xs text-slate-700 font-medium truncate" :title="file.original_name">{{ file.original_name }}</p>
                    <div class="flex items-center gap-1 mt-0.5">
                        <p class="text-xs text-slate-400">{{ file.width }}×{{ file.height }}</p>
                        <span class="text-slate-200">·</span>
                        <p class="text-xs text-slate-400">{{ formatSize(file.size) }}</p>
                    </div>
                    <p v-if="file.alt_text" class="text-xs text-slate-500 mt-1 truncate italic" :title="file.alt_text">{{ file.alt_text }}</p>
                </div>
            </div>
        </div>

        <!-- Edit modal -->
        <div v-if="editingFile" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="editingFile = null"></div>
            <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-md w-full">
                <h3 class="text-base font-semibold text-slate-900 mb-4">Editar metadatos</h3>
                <div class="mb-4 rounded-lg overflow-hidden bg-slate-100 aspect-video">
                    <img :src="editingFile.url" :alt="editingFile.alt_text || ''" class="w-full h-full object-contain" />
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Texto alternativo (alt)</label>
                        <input type="text" v-model="editForm.alt_text" placeholder="Describe la imagen para SEO y accesibilidad"
                            class="mt-1.5 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" />
                        <p class="mt-1 text-xs text-slate-400">Importante para SEO y lectores de pantalla.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea v-model="editForm.description" rows="2" placeholder="Descripción opcional de la imagen"
                            class="mt-1.5 block w-full rounded-md border-gray-200 text-gray-900 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"></textarea>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" @click="editingFile = null" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Cancelar</button>
                    <button type="button" @click="saveEdit" :disabled="saving"
                        class="px-4 py-2 text-sm bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:opacity-50 transition-colors">
                        Guardar
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete modal -->
        <div v-if="confirmDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="confirmDelete = null"></div>
            <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <h3 class="text-base font-semibold text-slate-900 mb-2">Eliminar imagen</h3>
                <p class="text-sm text-slate-600 mb-5">¿Eliminar "<strong>{{ confirmDelete.original_name }}</strong>"? Esta acción no se puede deshacer.</p>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="confirmDelete = null" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Cancelar</button>
                    <button type="button" @click="destroy(confirmDelete)" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Eliminar</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
