<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('close')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <h2 class="text-lg font-semibold text-slate-800">Banco de Imágenes</h2>
        <div class="flex items-center gap-3">
          <label class="cursor-pointer flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Subir imagen
            <input type="file" accept="image/*" class="hidden" @change="uploadFile" :disabled="uploading" />
          </label>
          <button type="button" @click="$emit('close')" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Upload progress -->
      <div v-if="uploading" class="px-6 py-2 bg-amber-50 border-b border-amber-100 flex items-center gap-2 text-sm text-amber-700">
        <svg class="w-4 h-4 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        Optimizando y subiendo imagen...
      </div>

      <!-- Grid -->
      <div class="flex-1 overflow-y-auto p-6">
        <div v-if="localFiles.length === 0" class="flex flex-col items-center justify-center h-48 text-slate-400">
          <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
          </svg>
          <p class="font-medium">No hay imágenes en el banco</p>
          <p class="text-sm mt-1">Sube tu primera imagen usando el botón de arriba</p>
        </div>

        <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
          <div
            v-for="file in localFiles"
            :key="file.id"
            :class="selectedId === file.id ? 'ring-2 ring-amber-500 ring-offset-2' : 'hover:ring-2 hover:ring-slate-300 hover:ring-offset-1'"
            class="relative group rounded-lg overflow-hidden bg-slate-100 aspect-square transition-all"
          >
            <!-- Image (clickable to select) -->
            <img
              :src="file.url"
              :alt="file.alt_text || ''"
              class="w-full h-full object-cover cursor-pointer"
              @click="selectFile(file)"
            />

            <!-- Overlay on hover -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors pointer-events-none"></div>

            <!-- Selected checkmark -->
            <div v-if="selectedId === file.id" class="absolute top-1.5 right-1.5 pointer-events-none">
              <div class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center">
                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              </div>
            </div>

            <!-- Delete button (visible on hover) -->
            <button
              type="button"
              @click.stop="confirmDeleteFile(file)"
              class="absolute top-1.5 left-1.5 w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600 z-10"
              title="Eliminar imagen"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>

            <!-- Filename tooltip on hover -->
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-1.5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
              <p class="text-white text-xs truncate">{{ file.original_name }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer actions -->
      <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
        <p v-if="selectedId" class="text-sm text-slate-600">1 imagen seleccionada</p>
        <p v-else class="text-sm text-slate-400">Haz clic en una imagen para seleccionarla</p>
        <div class="flex gap-3">
          <button type="button" @click="$emit('close')" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 transition-colors">
            Cancelar
          </button>
          <button type="button" @click="confirm" :disabled="!selectedId"
            class="px-4 py-2 rounded-lg text-sm bg-amber-500 hover:bg-amber-600 text-white font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
            {{ mode === 'insert' ? 'Insertar imagen' : 'Seleccionar como destacada' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete confirm modal (inside same overlay stack) -->
    <div v-if="deleteTarget" class="absolute inset-0 flex items-center justify-center p-4 z-10">
      <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
        <h3 class="text-base font-semibold text-slate-900 mb-2">Eliminar imagen</h3>
        <p class="text-sm text-slate-600 mb-5">¿Eliminar "<strong>{{ deleteTarget.original_name }}</strong>"? Esta acción no se puede deshacer.</p>
        <div class="flex justify-end gap-3">
          <button type="button" @click="deleteTarget = null" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Cancelar</button>
          <button type="button" @click="deleteFile" :disabled="deleting" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors">
            {{ deleting ? 'Eliminando...' : 'Eliminar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  show: Boolean,
  files: { type: Array, default: () => [] },
  mode: { type: String, default: 'featured' },
  initialSelectedId: { type: Number, default: null },
})

const emit = defineEmits(['close', 'select', 'uploaded', 'deleted'])

const localFiles = ref([...props.files])
const selectedId = ref(props.initialSelectedId)
const uploading = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

watch(() => props.files, (v) => { localFiles.value = [...v] })
watch(() => props.show, (v) => { if (v) selectedId.value = props.initialSelectedId })

function selectFile(file) {
  selectedId.value = selectedId.value === file.id ? null : file.id
}

function confirm() {
  const file = localFiles.value.find(f => f.id === selectedId.value)
  if (file) emit('select', file)
  emit('close')
}

function confirmDeleteFile(file) {
  deleteTarget.value = file
}

async function deleteFile() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await axios.delete(route('admin.media-library.destroy', deleteTarget.value.id), {
      headers: { 'X-Inertia': false },
    })
    const id = deleteTarget.value.id
    localFiles.value = localFiles.value.filter(f => f.id !== id)
    if (selectedId.value === id) selectedId.value = null
    emit('deleted', id)
    deleteTarget.value = null
  } finally {
    deleting.value = false
  }
}

async function uploadFile(event) {
  const file = event.target.files[0]
  if (!file) return
  uploading.value = true

  const form = new FormData()
  form.append('file', file)

  try {
    const res = await axios.post(route('admin.media-library.store'), form, {
      headers: { 'Content-Type': 'multipart/form-data', 'X-Inertia': false },
    })
    const newFile = res.data.file
    localFiles.value.unshift(newFile)
    selectedId.value = newFile.id
    emit('uploaded', newFile)
  } finally {
    uploading.value = false
    event.target.value = ''
  }
}
</script>
