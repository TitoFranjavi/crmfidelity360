<template>
    <!--Móvil-->
    <div v-if="mobile" class="d-flex justify-between my-10">
        <div class="text" data-size="13" data-weight="600">{{ label }}</div>

        <div class="custom-select no-hover" @click.stop="toggleOpen()" :class="{ 'seeing': isOpen }">

            <div class="ml-10" data-color="azul" data-size="13">
                {{ activeTitle }}<i class="fas fa-chevron-down ml-10" />
            </div>

            <div class="select-content form" style="width: 300px">

                <div class="form-group d-flex">
                    <p class="w-20 my-auto text">Inicial</p>

                    <div class="input-group ml-10 w-70">
                        <input
                            :value="modelValue.start"
                            type="date"
                            @click.stop
                            @change="e => update('start', e.target.value)"
                        />
                    </div>

                    <div class="my-auto mx-10 text pointer" @click.stop="update('start', '')"><i class="fas fa-x" /></div>
                </div>

                <div class="form-group d-flex">
                    <p class="w-20 my-auto text">Final</p>

                    <div class="input-group ml-10 w-70">
                        <input
                            :value="modelValue.end"
                            type="date"
                            @click.stop
                            @change="e => update('end', e.target.value)"
                        />
                    </div>

                    <div class="my-auto mx-10 text pointer" @click.stop="update('end', '')"><i class="fas fa-x" /></div>
                </div>
            </div>
        </div>
    </div>
    <!--Ordenador-->
    <div v-else :class="['d-flex my-40 select-none custom-select no-hover', { 'seeing': isOpen }]" style="cursor: default">
        <div class="text">{{ label }}:</div>

        <div class="pointer">
            <div class="ml-10" data-color="azul" @click.stop="toggleOpen()">
                {{ activeTitle }}<i class="fas fa-chevron-down ml-10" />
            </div>

            <div class="select-content center form" ref="dropdown">
                <div class="form-group d-flex">
                    <p class="w-20 my-auto text">Inicial</p>
                    <div class="input-group ml-10 w-70">
                        <input
                            data-size="12"
                            :value="modelValue.start"
                            type="date"
                            @click.stop
                            @change="e => update('start', e.target.value)"
                        />
                    </div>
                    <div class="my-auto mx-10 text pointer" @click.stop="update('start', '')">
                        <i class="fas fa-x" />
                    </div>
                </div>

                <div class="form-group d-flex">
                    <p class="w-20 my-auto text">Final</p>
                    <div class="input-group ml-10 w-70">
                        <input
                            data-size="12"
                            :value="modelValue.end"
                            type="date"
                            @click.stop
                            @change="e => update('end', e.target.value)"
                        />
                    </div>
                    <div class="my-auto mx-10 text pointer" @click.stop="update('end', '')">
                        <i class="fas fa-x" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FilterDates',
    props: {
        label: { type: String, required: true },
        modelValue: { type: Object, default: () => ({ start: '', end: '' }) },
        mobile: { type: Boolean, default: false },
    },
    emits: ['update:modelValue'],
    data() {
        return {
            isOpen: false,
        }
    },
    mounted() {
        this._outsideClick = this.handleOutsideClick.bind(this)
        this._dropdownOpened = (e) => this.handleDropdownOpened(e.detail)

        document.addEventListener('click', this._outsideClick)
        window.addEventListener('dropdown-opened', this._dropdownOpened)
    },
    beforeUnmount() {
        document.removeEventListener('click', this._outsideClick)
        window.removeEventListener('dropdown-opened', this._dropdownOpened)
    },
    methods: {
        handleOutsideClick(e) {
            if (!this.$el.contains(e.target)) {
                this.isOpen = false
            }
        },
        handleDropdownOpened(openedEl) {
            if (openedEl !== this.$el) {
                this.isOpen = false
            }
        },
        toggleOpen() {
            this.isOpen = !this.isOpen
            if (this.isOpen) {
                window.dispatchEvent(new CustomEvent('dropdown-opened', { detail: this.$el }))
            }
        },
        update(field, value) {
            this.$emit('update:modelValue', { ...this.modelValue, [field]: value })
        },
    },
    computed: {
        activeTitle() {
            const { start, end } = this.modelValue
            const fmt = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('es-ES') : null
            if (start && end) return `${fmt(start)} — ${fmt(end)}`
            if (start) return `Desde ${fmt(start)}`
            if (end)   return `Hasta ${fmt(end)}`
            return 'Todos'
        },
    },
}
</script>
