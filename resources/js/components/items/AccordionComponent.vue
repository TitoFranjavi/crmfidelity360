<template>
    <div class="accordion">
        <div
            @click="toggle"
        >
            <slot name="header"></slot>
        </div>

        <transition name="fade">
            <div v-if="modelValue">
                <slot name="body"></slot>
            </div>
        </transition>
    </div>
</template>

<script>
export default {
    props: {
        modelValue: {
            type: Boolean,
            default: false
        }
    },
    methods: {
        toggle() {
            this.$emit('update:modelValue', !this.modelValue);
        }
    }
}
</script>


<style scoped>
.accordion {
    overflow: hidden;
}
.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    max-height: 0;
}
</style>
