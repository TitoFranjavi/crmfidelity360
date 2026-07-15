<template>
    <!--Móvil-->
    <div v-if="mobile" class="d-flex justify-between my-10">
        <div class="text" data-size="13" data-weight="600">{{ label }}</div>

        <div v-if="items.length > 0" class="custom-select no-hover" @click.stop="toggleOpen()" :class="{ 'seeing': isOpen }">
            <div class="ml-10" data-size="13" data-color="azul">
                {{ activeTitle }}<i class="fas fa-chevron-down ml-10" />
            </div>

            <div class="select-content">
                <div class="d-flex align-center" @click.stop="toggleAll()">
                    <div :class="['custom-checkbox mr-10', { 'selected': areAllMarked }]" />
                    <div class="text" data-size="13">Todos</div>
                </div>

                <div v-for="item in filteredItems" :key="itemKey(item)" class="d-flex align-center" @click.stop="toggleItem(item)">
                    <div :class="['custom-checkbox mr-10', { 'selected': isActive(item) }]" />
                    <div class="text" data-size="13">{{ itemText(item) }}</div>
                </div>
            </div>
        </div>

        <div v-else class="ml-10" data-size="13" data-color="azul">0 {{ label.toLowerCase() }}</div>
    </div>
    <!--Ordenador-->
    <div v-else :class="['d-flex my-40 select-none custom-select no-hover',{ 'seeing': isOpen }]" style="cursor: default">
        <div class="text">{{ label }}:</div>

        <div v-if="items.length > 0" class="pointer">
            <div class="ml-10" data-color="azul" @click.stop="toggleOpen()">
                {{ activeTitle }}<i class="fas fa-chevron-down ml-10" />
            </div>

            <div class="select-content center form" ref="dropdown">
                <div v-if="labelSingular" class="form-group">
                    <div class="input-group align-center">
                        <input
                            data-size="12"
                            v-model="search"
                            type="text"
                            :placeholder="`Busca tu ${labelSingular}...`"
                            @click.stop
                        />
                        <i class="far fa-trash text" @click.stop="search = ''" />
                    </div>
                </div>

                <div class="d-flex align-center select-none" @click.stop="toggleAll()">
                    <div :class="['custom-checkbox mr-10', { 'selected': areAllMarked }]" />
                    <div class="text">Todos</div>
                </div>

                <div v-for="item in filteredItems" :key="itemKey(item)" class="d-flex align-center select-none" @click.stop="toggleItem(item)">
                    <div :class="['custom-checkbox mr-10',{ 'selected': isActive(item) }]" />
                    <div class="text">{{ itemText(item) }}</div>
                </div>
            </div>
        </div>

        <div v-else class="ml-10" data-size="13" data-color="azul">0 {{ label.toLowerCase() }}</div>
    </div>
</template>

<script>
export default {
    name: 'FilterDropdown',
    props: {
        label: { type: String, required: true },
        labelSingular: { type: String },
        itemLabel: { type: String, default: 'name' },
        itemValue: { type: String, default: 'id' },
        items: { type: Array, default: () => [] },
        modelValue: { type: Array, default: () => [] },
        single: { type: Boolean, default: false },
        mobile: { type: Boolean, default: false },
    },
    emits: ['update:modelValue'],
    data() {
        return {
            search: '',
            isOpen: false,
            areAllMarked: true,
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
    watch: {
        modelValue(val) {
            if (!this.areAllMarked && val.length === this.items.length) {
                this.areAllMarked = true
                this.$emit('update:modelValue', [])
            }
        }
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
        itemKey(item) {
            return this.isPlain ? item : item.id
        },
        itemText(item) {
            return this.isPlain ? item : item[this.itemLabel]
        },
        isActive(item) {
            if (this.areAllMarked) return true
            if (this.single) {
                return this.isPlain
                    ? this.modelValue === item
                    : this.modelValue === item[this.itemValue]
            }
            return this.isPlain
                ? this.modelValue.includes(item)
                : this.modelValue.includes(item[this.itemValue])
        },
        toggleItem(item) {
            if (this.single) {
                this.$emit('update:modelValue', this.isPlain ? item : item[this.itemValue])
                this.isOpen = false
                return
            }

            // Si están todos marcados visualmente, al pulsar uno se deselecciona ese y se emiten todos los demás
            if (this.areAllMarked) {
                this.areAllMarked = false
                const allExceptItemSelected = this.isPlain
                    ? this.items.filter(i => i !== item)
                    : this.items.map(i => i[this.itemValue]).filter(i => i !== item[this.itemValue])
                this.$emit('update:modelValue', allExceptItemSelected)
                return
            }

            const selected = this.isActive(item)
                ? this.modelValue.filter(i => i !== item[this.itemValue])
                : [...this.modelValue, item[this.itemValue]]

            const plainSelected = this.isActive(item)
                ? this.modelValue.filter(i => i !== item)
                : [...this.modelValue, item]

            this.$emit('update:modelValue', this.isPlain ? plainSelected : selected)
        },
        toggleAll() {
            this.areAllMarked = !this.areAllMarked
            this.$emit('update:modelValue', this.single ? null : [])
        },
    },
    computed: {
        isPlain() {
            return this.items.length === 0 || typeof this.items[0] !== 'object'
        },
        filteredItems() {
            const normalize = str => str.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase()
            const items = this.search
                ? this.items.filter(item => normalize(this.itemText(item)).includes(normalize(this.search)))
                : this.items
            return items.slice().sort((a, b) => this.itemText(a).localeCompare(this.itemText(b)))
        },
        activeTitle() {
            if (this.single) {
                if (this.modelValue === null || this.modelValue === undefined) return 'Todos'
                if (this.isPlain) return this.modelValue
                const item = this.items.find(i => i[this.itemValue] === this.modelValue)
                return item ? item[this.itemLabel] : ''
            }

            const count = this.modelValue.length
            if (count === 0) return 'Todos'
            if (count === 1) {
                if (this.isPlain) return this.modelValue[0]
                const item = this.items.find(i => i[this.itemValue] === this.modelValue[0])
                return item ? item[this.itemLabel] : ''
            }
            return `${count} ${this.label.toLowerCase()}`
        },
    },
}
</script>
