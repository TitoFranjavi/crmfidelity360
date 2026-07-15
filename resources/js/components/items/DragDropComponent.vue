<template>
    <div class="w-100">
        <div class="dropzone" :class="{ active: isDragging }" @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false" @drop.prevent="onDrop" @click="$refs.input.click()"
        >
            <input ref="input" type="file" multiple style="display:none" @change="onFileChange" />
            {{ isDragging ? 'Suelta los archivos' : 'Arrastra los archivos aquí o haz clic para seleccionar' }}
        </div>

        <div class="d-flex column mt-16" data-gap="8" v-if="files.length">
            <div class="d-flex align-center py-10 px-14 round" data-round="10" data-gap="10" data-bg="blanco" data-border-color="gris-principal" v-for="(file, i) in files" :key="i">
                <span class="text ellipsis noWidth flex-1" data-size="12">{{ file.name }}</span>
                <span class="text opacity-6 f-shrink-0" data-size="11">{{ formatSize(file.size) }}</span>
                <i class="far fa-x pointer" @click="remove(i)" />
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FileDropzone',

    emits: ['update:files'],

    data() {
        return {
            isDragging: false,
            files: [],
        }
    },

    methods: {
        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B'
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
        },
        onDrop(e) {
            this.isDragging = false
            this.addFiles(Array.from(e.dataTransfer.files))
        },

        onFileChange(e) {
            this.addFiles(Array.from(e.target.files))
            e.target.value = ''
        },

        addFiles(incoming) {
            for (const file of incoming) {
                this.files.push(file)
            }
            this.$emit('update:files', this.files)
        },

        remove(index) {
            this.files.splice(index, 1)
            this.$emit('update:files', this.files)
        },
    },
}
</script>
