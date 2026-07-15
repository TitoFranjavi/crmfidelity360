<template>
    <div class="d-flex column">

        <div class="contact four pointer" v-bind:class="{'seeing' : contactSelectedToSee._id === contact._id}"><!--v-on:dblclick="actionLink('/contacts/' + contact._id)" v-on:click.prevent="seeContactInfo(contact)"-->
            <!--checkbox-->
            <div class="custom-checkbox pointer" v-on:click.stop.prevent="toggleSelectContact(contact)" v-bind:class="{'selected': contactsSelected.indexOf(contact._id) != -1}"></div>

            <!--iniciales-->
            <div class="d-flex justify-center">
                <div class="d-flex justify-center mr-10">
                    <div class="initials" data-style="initials" v-if="!contact.profileImage && contact.name && contact.name.first">{{ getInitials(contact.name.first) }}</div>
                    <div class="initials" data-style="initials" v-bind:class="{image: contact.profileImage}" v-else>
                        <img :src="'/assets/contact_images/' + contact.profileImage" class="profile-image">
                    </div>
                </div>
            </div>

            <!--Nombre y apellidos-->
            <div class="d-flex column ellipsis">
                <a v-on:click.prevent="" v-on:dblclick="actionLink('/contacts/' + contact._id)" :href="'/contacts/' + contact._id" class="ellipsis" data-color="azul" data-weight="600">{{ contact.name.first }} {{ contact.name.second }}</a>
                <p class="text ellipsis" data-size="10">{{ contact.surname.first }} {{ contact.surname.second }}</p>
                <p class="text" data-size="10" v-if="!contact.surname.first && !contact.surname.second">-</p>
            </div>

            <!--Cuenta y cargo-->
            <div class="d-flex column ellipsis">
                <p class="ellipsis" data-color="azul" data-weight="600">{{ account }}</p>
                <p class="text ellipsis" data-size="10">{{ contact.position ? contact.position :'-' }}</p>
            </div>

            <!--Email y telefono-->
            <div class="d-flex column ellipsis">
                <p class="ellipsis" data-color="azul" data-weight="600">{{ contact.email ? contact.email : '-' }}</p>
                <p class="text ellipsis" data-size="10">{{ contact.phone ? contact.phone : '-' }}</p>
            </div>

            <!--Fec. creación-->
            <div class="d-flex column ellipsis">
                <p class="ellipsis" data-color="azul" data-weight="600">{{ getPrettyDate(contact.createdAt) }}</p>
            </div>

            <!--Botones-->
            <div class="d-flex">

                <div class="mx-10 text pointer" v-on:click.stop="actionLink('/contacts/' + contact._id)"><i class="far fa-eye"></i></div>

                <div class="mx-10 text pointer" v-if="!isReadOnly" v-on:click.stop.prevent="toggleArchiveContact(contact)"><i class="far" v-bind:class="isSeeingArchived ? 'fa-box-open' : 'fa-box-archive'"></i></div>

                <div class="mx-10 text pointer" v-on:click.stop.prevent="seeContactInfo(contact)"><i class="far fa-eyes rotate-horizontal" ></i></div>

                <div class="mx-10 text pointer" v-if="!isReadOnly" data-color="rojo" v-on:click.stop.prevent="deleteContact(contact)"><i class="far fa-trash"></i></div>
            </div>

        </div>

        <!--Info para mas pequeño-->
        <div class="show-middle d-flex justify-around" v-if="contactSelectedToSee._id === contact._id">

            <!--Info básica-->
            <div class="d-flex column w-50 pl-80 pr-20">

                <!--Correo-->
                <div class="d-flex justify-between my-10">
                    <div class="text mr-10" data-weight="600">Correo</div>

                    <div class="text ellipsis" data-weight="300" v-if="contactSelectedToSee.email">{{ contactSelectedToSee.email }}</div>

                    <div class="text opacity-5" v-else>-</div>
                </div>

                <!--Telefono-->
                <div class="d-flex justify-between my-10">
                    <div class="text mr-10" data-weight="600">Teléfono</div>

                    <div class="text ellipsis" data-weight="300" v-if="contactSelectedToSee.phone">{{ contactSelectedToSee.phone }}</div>

                    <div class="text opacity-5" v-else>-</div>
                </div>

            </div>

            <!--Info facturación-->
            <div class="d-flex column w-50 pl-20 pr-20 ">
                <!--Municipio-->
                <div class="d-flex justify-between my-10 " >
                    <div class="text mr-10" data-weight="600">Comunidad</div>

                    <div class="text ellipsis" data-weight="300" v-if="contactSelectedToSee.billingInfo.community">{{ contactSelectedToSee.billingInfo.community.name }}</div>

                    <div class="text opacity-5" v-else>-</div>
                </div>

                <!--Provincia-->
                <div class="d-flex justify-between my-10" >
                    <div class="text" data-weight="600">Provincia</div>

                    <div class="text ellipsis" data-weight="300" v-if="contactSelectedToSee.billingInfo.province">{{ contactSelectedToSee.billingInfo.province.name }}</div>

                    <div class="text opacity-5" v-else>-</div>
                </div>


                <!--Localidad-->
                <div class="d-flex justify-between my-10" >
                    <div class="text" data-weight="600">Localidad</div>

                    <div class="text ellipsis" data-weight="300" v-if="contactSelectedToSee.billingInfo.locality">{{ contactSelectedToSee.billingInfo.locality.name }}</div>

                    <div class="text opacity-5" v-else>-</div>
                </div>

            </div>

        </div>

        <!--Linea separadora-->
        <div class="separator my-5"></div>
    </div>
</template>

<script>

export default {
    name: "ContactCardComponent",
    props:['contact', 'contactSelectedToSee', 'contactsSelected', 'isSeeingArchived', 'isReadOnly'],
    methods:{
        getPrettyDate(date){
            let dateNow = new Date(date);
            let day = String(dateNow.getDate()).padStart(2, '0'); // Asegura que el día tenga dos dígitos
            let month = String(dateNow.getMonth() + 1).padStart(2, '0'); // Asegura que el mes tenga dos dígitos y se suma 1 porque en JavaScript los meses van de 0 a 11
            let year = dateNow.getFullYear();
            return `${day}/${month}/${year}`;
        },
        toggleArchiveContact(contact){
            this.$emit('toggleArchiveContact', contact)
        },
        deleteContact(contact){
            this.$emit('deleteContact', contact)
        },
        seeContactInfo(contact){
            this.$emit('seeContactInfo', contact)
        },
        toggleSelectContact(contact){
            this.$emit('toggleSelectContact', contact)
        },
        getInitials(name){

            if (name){
                let nameSplited = name.split(/\s+/)

                let initials = nameSplited[0][0];

                if (nameSplited[1])
                    initials += nameSplited[1][0];

                return initials
            }
        },
        actionLink(route){
            this.$router.push(route)
        },
        openInNewTab(url) {
            window.open(url, '_blank');
        }
    },
    computed:{
        account(){

            if (this.contact.accounts.length === 0 || this.contact.accounts[0] === null)
                return '-'
            else if (this.contact.accounts.length === 1){
                return this.contact.accounts[0].name
            }else
                return (this.contact.accounts.length + ' cuentas')

        }
    }
}
</script>

<style scoped>

</style>
