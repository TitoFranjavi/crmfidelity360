<template>
    <div class="d-flex column">

        <!--Info principal-->
        <div class="contact three pointer my-5 pl-0" data-gap="15" v-on:dblclick="actionLink('/users/' + user._id)" v-on:click="seeUserInfo(user)" v-bind:class="{'seeing' : userSelectedToSee._id === user._id}">
            <!--Línea horizontal-->
            <div><div v-if="level > 0" class="hierarchy-line" data-position="horizontal" data-border-color="azul-claro"></div></div>

            <!--Jerarquía si existe-->
            <div @click.stop="">
                <i v-if="user.hierarchy.length > 0" :class="['fas',openHierarchy ? 'fa-chevron-down' : 'fa-chevron-right']" @click="openHierarchy = !openHierarchy" data-color="principal"></i>
            </div>

            <!--Foto de perfil-->
            <div class="d-flex justify-center">
                <div class="d-flex justify-center">
                    <div class="initials" data-style="initials" v-bind:class="{image: user.profileImage}">
                        <img :src="'/assets/profile_images/' + user.profileImage" class="profile-image">
                    </div>
                </div>
            </div>

            <!--Nombre y apellidos-->
            <div class="d-flex column">
                <p class="ellipsis" :data-color="(user.isActive === false && (!user.temporalActive || new Date(user.temporalActive) < new Date()) && !user.inactivable) ? 'rojo' : 'azul'"  data-weight="600">{{ user.firstName ? user.firstName : '-' }}</p>
                <p class="text ellipsis" data-size="10">{{ user.lastName ? user.lastName : '-' }}</p>
            </div>

            <!--Correo y telefono-->
            <div class="d-flex column">
                <p class="ellipsis" :data-color="(basicData && basicData.userLogged && basicData.userLogged._id === '65cb57489c2c285441086a43' && !user.isActive && (!user.temporalActive || (!!user.temporalActive && new Date(user.temporalActive) < new Date())) && !user.inactivable) ? 'rojo' : 'azul'" data-weight="600">{{ user.email ? user.email : '-' }}</p>
                <p class="text ellipsis" data-size="10">{{ user.phone ? user.phone : '-' }}</p>
            </div>

            <!--Botones-->
            <div class="d-flex justify-end">
                <div class="mx-10 text pointer" v-on:click.stop="actionLink('/users/' + user._id)"><i class="far fa-gear"></i></div>

                <div class="mx-10 text pointer" data-color="rojo" v-if="!isReadOnly && canManage('users.delete')" v-on:click.stop="deleteUser(user)"><i class="far fa-trash"></i></div>
            </div>

        </div>

    </div>
    <!--Genero subordinados de manera recursiva-->
    <div v-if="user.hierarchy.length > 0" v-show="openHierarchy" :class="[`ml-${70 - parseInt(level) * 1}`,'d-flex']">
        <div class="hierarchy-line mb-10" data-border-color="azul-claro"></div>
        <div class="w-100">
        <user-card-component v-for="subordinate in user.hierarchy" :key="subordinate._id" :user="subordinate" :level="parseInt(level) + 1" :userSelectedToSee="userSelectedToSee" :usersSelected="usersSelected" :isReadOnly="isReadOnly" @deleteUser="deleteUser" @seeUserInfo="seeUserInfo" @toggleSelectUser="toggleSelectUser"></user-card-component>
        </div>
    </div>
</template>

<script>
export default {
    name: "UserCardComponent",
    props:['user', 'level', 'userSelectedToSee', 'usersSelected', 'isReadOnly', 'deleteUser', 'basicData'],
    emits:['seeUserInfo', 'toggleSelectUser', 'deleteUser'],
    data(){
        return {
            openHierarchy: true,
        }
    },
    methods:{
        canManage(code) {
            const user = this.basicData?.userLogged;
            const subdomain = this.basicData?.userSubdomain;

            if (!user || !subdomain) return false;

            const label = user.label;
            const labelsPermissions = subdomain.labels_permissions;

            if (!label || !labelsPermissions) return false;
            if (!labelsPermissions[label]) return false;
            if (!code || !code.includes('.')) return false;

            const [module, action] = code.split('.');

            const modulePermissions = labelsPermissions[label][module];

            return Array.isArray(modulePermissions) && modulePermissions.includes(action);
        }
        ,
        seeUserInfo(user){
            this.$emit('seeUserInfo', user)
        },
        toggleSelectUser(user){
            this.$emit('toggleSelectUser', user)
        },
        deleteUser(user){
            this.$emit('deleteUser', user)
        },
        actionLink(route){
            this.$router.push(route)
        }
    },
}
</script>

<style scoped>

</style>
