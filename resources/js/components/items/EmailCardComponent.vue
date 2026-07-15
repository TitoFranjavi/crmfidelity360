<template>
    <div class="relPos">
        <div class="dashboard-card align-center my-20 w-100 p-15 pointer" @click="setActive">
            <!-- Icono -->
            <div class="d-flex justify-center align-center w-20">
                <div class="icon mr-0 relPos">
                    <!-- FontAwesome icon -->
                    <i v-if="faIcon" :class="faIcon" />

                    <!-- Lucide or other Vue component icon -->
                    <component
                        class="absPos"
                        v-else-if="iconComponent"
                        :is="iconComponent"
                        v-bind="iconProps"
                        data-color="principal"
                        :size="20"
                    />
                </div>
            </div>

            <!-- Título -->
            <div class="info w-70">
                <div class="w-100 px-5" style="line-height: 0.9">
                    <p class="value" data-size="25" data-weight="600" style="line-height: 0.9">{{ emails.length }}</p>
                    <span class="ellipsis opacity-5" data-size="8" style="line-height: 0.9">{{ title }}</span>
                </div>
            </div>

            <!-- Flecha -->
            <div class="w-5 w-10 d-flex justify-end">
                <i class="fa-regular " :class="{'fa-chevron-down' : !isOpen,'fa-chevron-up' : isOpen }"></i>
            </div>
        </div>

        <!--Listado-->
        <div v-if="isOpen" class="emails-info">
            <!--Buscador-->
            <div class="form d-flex">
                <!--Buscador-->
                <div class="form-group w-100">
                    <div class="input-group w-100">
                        <input type="text" class="w-100" placeholder="Busca un correo" v-model="searchEmail">
                    </div>
                </div>
            </div>

            <!--Listado-->
            <div v-for="email in emailsFiltered" class="my-10">
                {{ email.email }}
            </div>
        </div>
    </div>
</template>

<script>

export default {
    props: ['title', 'faIcon', 'iconComponent', 'iconProps', 'emails', 'activeCard'],
    data(){
        return {
            searchEmail: ''
        }
    },
    methods:{
        setActive(){
            this.$emit('setActive', this.title)
        }
    },
    computed:{
        emailsFiltered(){
            if (!this.emails) return []

            if (!this.searchEmail || this.searchEmail === '') return this.emails

            return this.emails.filter(email => email.email.toLowerCase().includes(this.searchEmail.toLowerCase()))
        },
        isOpen() {
            return this.activeCard === this.title
        }
    }
}
</script>

<style scoped>

</style>
