<template>
    <div class="d-flex column">


        <div class="contact accounts pointer"> <!--v-on:dblclick="actionLink('/accounts/' + account._id)" v-bind:class="{'seeing' : accountSelectedToSee._id === account._id}" v-on:click.prevent="seeAccountInfo(account)"-->
            <!--checkbox-->
            <div class="custom-checkbox pointer" v-on:click.stop.prevent="toggleSelectAccount(account)" v-bind:class="{'selected': accountsSelected.some(accountStored => accountStored._id === account._id)}"></div>

            <!--iniciales-->
            <div class="d-flex justify-center">
                <div class="initials" data-style="initials"  v-if="!account.profileImage && account.name">{{ getInitials(account.name) }}</div>
                <div class="initials" data-style="initials" v-bind:class="{image: account.profileImage}" v-else>
                    <img :src="'/assets/account_images/' + account.profileImage" class="profile-image">
                </div>
            </div>

            <!--Nombre y nombre cuenta principal-->
            <div class="d-flex column">
                <a v-on:click.prevent="" v-on:dblclick="actionLink('/accounts/' + account._id)" :href="'/accounts/' + account._id" class="text ellipsis" data-color="azul" data-weight="600">{{ account.name ? account.name : '-' }}</a>
                <p class="text ellipsis" data-size="10">{{ (account.principalAcc && account.principalAcc.name) ? account.principalAcc.name : '-' }}</p>
            </div>

            <!--Agente-->
            <div class="d-flex column">
                <p class="text ellipsis" data-color="azul" data-weight="600">{{ account.agentFullName ? account.agentFullName : '-' }}</p>
            </div>

            <!--CIF-->
            <div class="d-flex column">
                <p class="text ellipsis" data-color="azul" data-weight="600">{{ account.CIF ? account.CIF :'-' }}</p>
            </div>

            <!--Telefono-->
            <div class="d-flex column">
                <p class="text ellipsis" data-color="azul" data-weight="600">{{ account.phone ? account.phone :'-' }}</p>
            </div>

            <!--Email-->
            <div class="d-flex column">
                <p class="text ellipsis" data-color="azul" data-weight="600">{{ account.email ? account.email : '-' }}</p>
            </div>


            <!--Fec. creación-->
            <div class="d-flex column">
                <p class="text ellipsis" data-color="azul" data-weight="600">{{ getPrettyDate(account.createdAt) }}</p>
            </div>

            <!--Botones-->
            <div class="d-flex" v-if="!isReadOnly">
                <div class="mx-10 text pointer" v-on:click.stop="actionLink('/accounts/' + account._id)"><i class="far fa-eye"></i></div>

                <div class="mx-10 text pointer" v-on:click.stop.prevent="toggleArchiveAccount(account)"><i class="far" v-bind:class="account.archived ? 'fa-box-open' : 'fa-box-archive'"></i></div>

                <div class="mx-10 text pointer" v-on:click.stop.prevent="seeAccountInfo(account)"><i class="far fa-eyes rotate-horizontal" ></i></div>

                <div class="mx-10 text pointer" data-color="rojo" v-if="!isReadOnly && canDelete" v-on:click.stop.prevent="deleteAccount(account)"><i class="far fa-trash"></i></div>
            </div>

        </div>

        <!--Info para mas pequeño-->
        <div class="show-middle d-flex justify-around" v-if="accountSelectedToSee._id === account._id">

            <!--Info básica-->
            <div class="d-flex column w-50 pl-80 pr-20">

                <!--Telefono-->
                <div class="d-flex justify-between my-10">
                    <div class="text mr-10" data-weight="600">Correo</div>

                    <div class="text ellipsis" data-weight="300" v-if="accountSelectedToSee.phone">{{ accountSelectedToSee.phone }}</div>

                    <div class="text opacity-5" v-else>-</div>
                </div>

                <!--Sitio web-->
                <div class="d-flex justify-between my-10">
                    <div class="text mr-10" data-weight="600">Sitio web</div>

                    <div class="text ellipsis" data-weight="300" v-if="accountSelectedToSee.website">{{ accountSelectedToSee.website }}</div>

                    <div class="text opacity-5" v-else>-</div>
                </div>

                <!--Tipo de cuenta-->
                <div class="d-flex justify-between my-10">
                    <div class="text mr-10" data-weight="600">Tipo de cuenta</div>

                    <div class="text ellipsis" data-weight="300" v-if="accountSelectedToSee.accType">{{ accountSelectedToSee.accType }}</div>

                    <div class="text opacity-5" v-else>-</div>
                </div>

            </div>

            <!--Info facturación-->
            <div class="d-flex column w-50 pl-20 pr-20 ">
                <!--Municipio-->
                <div class="d-flex justify-between my-10 " >
                    <div class="text mr-10" data-weight="600">Comunidad</div>

                    <div class="text ellipsis" data-weight="300" v-if="accountSelectedToSee.billingInfo">{{ (accountSelectedToSee.billingInfo.community && accountSelectedToSee.billingInfo.community.name) ? accountSelectedToSee.billingInfo.community.name : '-' }}</div>

                    <div class="text opacity-5" v-else>-</div>
                </div>

                <!--Provincia-->
                <div class="d-flex justify-between my-10" >
                    <div class="text" data-weight="600">Provincia</div>

                    <div class="text ellipsis" data-weight="300" v-if="accountSelectedToSee.billingInfo">{{ (accountSelectedToSee.billingInfo.province && accountSelectedToSee.billingInfo.province.name) ?  accountSelectedToSee.billingInfo.province.name : '-' }}</div>

                    <div class="text opacity-5" v-else>-</div>
                </div>


                <!--Localidad-->
                <div class="d-flex justify-between my-10">
                    <div class="text" data-weight="600">Localidad</div>

                    <div class="text ellipsis" data-weight="300" v-if="accountSelectedToSee.billingInfo">{{ (accountSelectedToSee.billingInfo.locality && accountSelectedToSee.billingInfo.locality.name) ? accountSelectedToSee.billingInfo.locality.name : '-' }}</div>

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
    name: "AccountCardComponent",
    props:['account', 'accountSelectedToSee', 'accountsSelected', 'isSeeingArchived', 'isReadOnly', 'canDelete'],
    methods:{
        toggleArchiveAccount(account){
            this.$emit('toggleArchiveAccount', account)
        },
        deleteAccount(account){
            this.$emit('deleteAccount', account)
        },
        seeAccountInfo(account){
            this.$emit('seeAccountInfo', account)
        },
        toggleSelectAccount(account){
            this.$emit('toggleSelectAccount', account)
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
        getPrettyDate(date){
            let dateNow = new Date(date);
            let day = String(dateNow.getDate()).padStart(2, '0'); // Asegura que el día tenga dos dígitos
            let month = String(dateNow.getMonth() + 1).padStart(2, '0'); // Asegura que el mes tenga dos dígitos y se suma 1 porque en JavaScript los meses van de 0 a 11
            let year = dateNow.getFullYear();
            return `${day}/${month}/${year}`;
        },
        actionLink(route){
            this.$router.push(route);
        }
    }
}
</script>

<style scoped>

</style>
