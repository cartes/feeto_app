<template>
  <div class="tiptap-wrapper border border-slate-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-amber-500 focus-within:border-amber-500">
    <!-- Toolbar -->
    <div class="tiptap-toolbar bg-slate-50 border-b border-slate-200 p-2 flex flex-wrap gap-1">
      <!-- Text formatting -->
      <button type="button" @click="editor.chain().focus().toggleBold().run()"
        :class="editor?.isActive('bold') ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Negrita (Ctrl+B)">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
      </button>
      <button type="button" @click="editor.chain().focus().toggleItalic().run()"
        :class="editor?.isActive('italic') ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Cursiva (Ctrl+I)">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg>
      </button>
      <button type="button" @click="editor.chain().focus().toggleUnderline().run()"
        :class="editor?.isActive('underline') ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Subrayado (Ctrl+U)">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>
      </button>
      <button type="button" @click="editor.chain().focus().toggleStrike().run()"
        :class="editor?.isActive('strike') ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Tachado">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6.85 7.08C6.85 4.37 9.45 3 12.24 3c1.64 0 3 .49 3.9 1.28.77.65 1.46 1.73 1.46 3.24h-3.01c0-.31-.05-.59-.15-.85-.29-.86-1.2-1.28-2.25-1.28-1.86 0-2.34.98-2.34 1.7 0 .48.25.88.74 1.21.28.18.77.37 1.45.57H7.39c-.21-.34-.54-.89-.54-1.79zM21 12v-2H3v2h9.62c1.15.45 1.96.75 1.96 1.97 0 1-.81 1.67-2.28 1.67-1.54 0-2.93-.71-2.93-2.64H6.37c0 .09-.01.17-.01.26C6.36 17.06 9.37 19 13.08 19c3.74 0 5.78-1.7 5.78-4.3 0-.98-.26-1.89-.92-2.7H21z"/></svg>
      </button>

      <div class="w-px bg-slate-200 mx-1 self-stretch"></div>

      <!-- Headings -->
      <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
        :class="editor?.isActive('heading', { level: 2 }) ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn font-bold text-xs px-2" title="Título H2">H2</button>
      <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
        :class="editor?.isActive('heading', { level: 3 }) ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn font-bold text-xs px-2" title="Título H3">H3</button>
      <button type="button" @click="editor.chain().focus().toggleHeading({ level: 4 }).run()"
        :class="editor?.isActive('heading', { level: 4 }) ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn font-bold text-xs px-2" title="Título H4">H4</button>

      <div class="w-px bg-slate-200 mx-1 self-stretch"></div>

      <!-- Lists -->
      <button type="button" @click="editor.chain().focus().toggleBulletList().run()"
        :class="editor?.isActive('bulletList') ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Lista con viñetas">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
      </button>
      <button type="button" @click="editor.chain().focus().toggleOrderedList().run()"
        :class="editor?.isActive('orderedList') ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Lista numerada">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
      </button>
      <button type="button" @click="editor.chain().focus().toggleBlockquote().run()"
        :class="editor?.isActive('blockquote') ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Cita">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>
      </button>
      <button type="button" @click="editor.chain().focus().toggleCodeBlock().run()"
        :class="editor?.isActive('codeBlock') ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Bloque de código">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>
      </button>

      <div class="w-px bg-slate-200 mx-1 self-stretch"></div>

      <!-- Alignment -->
      <button type="button" @click="editor.chain().focus().setTextAlign('left').run()"
        :class="editor?.isActive({ textAlign: 'left' }) ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Alinear a la izquierda">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"/></svg>
      </button>
      <button type="button" @click="editor.chain().focus().setTextAlign('center').run()"
        :class="editor?.isActive({ textAlign: 'center' }) ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Centrar">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/></svg>
      </button>
      <button type="button" @click="editor.chain().focus().setTextAlign('right').run()"
        :class="editor?.isActive({ textAlign: 'right' }) ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Alinear a la derecha">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 21h18v-2H3v2zm6-4h12v-2H9v2zm-6-4h18v-2H3v2zm6-4h12V7H9v2zM3 3v2h18V3H3z"/></svg>
      </button>

      <div class="w-px bg-slate-200 mx-1 self-stretch"></div>

      <!-- Link -->
      <button type="button" @click="setLink"
        :class="editor?.isActive('link') ? 'bg-slate-200 text-slate-900' : 'text-slate-600 hover:bg-slate-100'"
        class="toolbar-btn" title="Insertar enlace">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
      </button>
      <button type="button" v-if="editor?.isActive('link')" @click="editor.chain().focus().unsetLink().run()"
        class="toolbar-btn text-slate-600 hover:bg-slate-100" title="Quitar enlace">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 7h-4v2h4c1.65 0 3 1.35 3 3s-1.35 3-3 3h-4v2h4c2.76 0 5-2.24 5-5s-2.24-5-5-5zm-6 8H7c-1.65 0-3-1.35-3-3s1.35-3 3-3h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-2zm-3-4h8v2H8v-2zM3 3.41L1.59 4.82 4.77 8H7v2h4V7H8.59L5.82 4.23 3 1.41zm18.41 18L20 23l-3-3h-1v-2h-4v2h-.18L9.18 17H7v-2H4.18L1.41 12.23 3 10.82 5.82 13H7v2h2v-2h1.41l9.18 9.18L22.59 21z"/></svg>
      </button>

      <!-- Image from media library -->
      <button type="button" @click="$emit('open-media-picker', 'insert')"
        class="toolbar-btn text-slate-600 hover:bg-slate-100" title="Insertar imagen del banco">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
      </button>

      <div class="w-px bg-slate-200 mx-1 self-stretch"></div>

      <!-- Undo / Redo -->
      <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor?.can().undo()"
        class="toolbar-btn text-slate-600 hover:bg-slate-100 disabled:opacity-30" title="Deshacer (Ctrl+Z)">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>
      </button>
      <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor?.can().redo()"
        class="toolbar-btn text-slate-600 hover:bg-slate-100 disabled:opacity-30" title="Rehacer (Ctrl+Y)">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>
      </button>
    </div>

    <!-- Editor Content -->
    <editor-content :editor="editor" class="tiptap-content" />
  </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import Underline from '@tiptap/extension-underline'
import TextAlign from '@tiptap/extension-text-align'
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Escribe el contenido aquí...' },
})

const emit = defineEmits(['update:modelValue', 'open-media-picker'])

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({ link: false, underline: false }),
    Underline,
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
    Link.configure({ openOnClick: false, HTMLAttributes: { class: 'tiptap-link' } }),
    Image.configure({ HTMLAttributes: { class: 'tiptap-image' } }),
    Placeholder.configure({ placeholder: props.placeholder }),
  ],
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
})

watch(() => props.modelValue, (newVal) => {
  if (editor.value && editor.value.getHTML() !== newVal) {
    editor.value.commands.setContent(newVal || '', false)
  }
})

function setLink() {
  const prev = editor.value.getAttributes('link').href || ''
  const url = window.prompt('URL del enlace:', prev)
  if (url === null) return
  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }
  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

function insertImage(url) {
  editor.value.chain().focus().setImage({ src: url }).run()
}

defineExpose({ insertImage })

onBeforeUnmount(() => editor.value?.destroy())
</script>

<style>
.toolbar-btn {
  @apply p-1.5 rounded transition-colors flex items-center justify-center min-w-[30px];
}

.tiptap-content .ProseMirror {
  @apply min-h-[300px] p-4 outline-none text-slate-800;
}

.tiptap-content .ProseMirror p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  @apply text-slate-400 pointer-events-none float-left h-0;
}

.tiptap-content .ProseMirror h2 { @apply text-2xl font-bold mt-6 mb-3 text-slate-900; }
.tiptap-content .ProseMirror h3 { @apply text-xl font-semibold mt-5 mb-2 text-slate-900; }
.tiptap-content .ProseMirror h4 { @apply text-lg font-semibold mt-4 mb-2 text-slate-900; }
.tiptap-content .ProseMirror p { @apply mb-3 leading-relaxed; }
.tiptap-content .ProseMirror ul { @apply list-disc pl-6 mb-3 space-y-1; }
.tiptap-content .ProseMirror ol { @apply list-decimal pl-6 mb-3 space-y-1; }
.tiptap-content .ProseMirror blockquote { @apply border-l-4 border-amber-500 pl-4 italic text-slate-600 my-4; }
.tiptap-content .ProseMirror pre { @apply bg-slate-100 rounded-lg p-4 font-mono text-sm overflow-x-auto my-4; }
.tiptap-content .ProseMirror code { @apply bg-slate-100 px-1.5 py-0.5 rounded text-sm font-mono; }
.tiptap-content .ProseMirror .tiptap-link { @apply text-amber-600 underline; }
.tiptap-content .ProseMirror .tiptap-image { @apply rounded-xl max-w-full my-4 border border-slate-200 shadow-sm; }
.tiptap-content .ProseMirror hr { @apply border-slate-200 my-6; }
</style>
