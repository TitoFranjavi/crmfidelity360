<script>
export default {
    props:['user', 'level'],
    methods:{
        actionLink(route) {
            this.$router.push(route)
        }
    }
}

</script>

<template>
    <div class="d-flex column ml-10">

        <p class="pointer p-5" data-color="principal" v-on:click="actionLink('/users/' + user._id)">{{ user.firstName }} {{ user.lastName }}</p>

        <!-- Renderiza recursivamente los subordinados -->
        <ul class="d-flex" v-if="user.hierarchy && user.hierarchy.length > 0" :style="{ marginLeft: `${level + 25}px` }">

            <!--linea jerarquía-->
            <div class="hierarchy-line" data-border-color="principal"></div>

            <div>
                <hierarchy-item
                    v-for="subordinate in user.hierarchy"
                    :key="subordinate._id"
                    :user="subordinate"
                    :level="level + 1"
                />
            </div>

        </ul>
    </div>
</template>

<style scoped>

</style>
