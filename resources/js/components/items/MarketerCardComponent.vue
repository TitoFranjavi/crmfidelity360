<script>
export default {
    name: "MarketerCardComponent",
    props:['marketer', 'marketers', 'marketerSelectedToSee'],
    data(){
        return {

        }
    },
    methods:{
        seeMarketerInfo(marketer){
            this.$emit('seeMarketerInfo', marketer)
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
            this.$router.push(route);
        }
    }
}
</script>

<template>
    <div class="d-flex column">

        <div class="contact marketers pointer" v-on:dblclick="actionLink('/marketers/' + marketer._id)" v-on:click.prevent="seeMarketerInfo(marketer)" v-bind:class="{'seeing' : marketerSelectedToSee._id === marketer._id}">

            <!--Nombre-->
            <div class="d-flex column">
                <a v-on:click.prevent="" :href="'/marketers/' + marketer._id" class="text ellipsis" data-color="azul" data-weight="600">{{ marketer.name }}</a>
            </div>

            <!--Nº tarifas de luz-->
            <div class="d-flex column">
                <p class="text ellipsis" data-color="azul" data-weight="600">{{ marketer.fees.electricity.length }}</p>
            </div>

            <!--Nº productos de luz-->
            <div class="d-flex column">
                <p class="text ellipsis" data-color="azul" data-weight="600">{{ marketer.products.electricity.length }}</p>
            </div>


            <!--Nº tarifas de gas-->
            <div class="d-flex column">
                <p class="text ellipsis" data-color="azul" data-weight="600">{{ marketer.fees.gas.length }}</p>
            </div>

            <!--Nº productos de gas-->
            <div class="d-flex column">
                <p class="text ellipsis" data-color="azul" data-weight="600">{{ marketer.products.gas.length }}</p>
            </div>


            <!--Botones-->
            <!--<div class="d-flex">
                <div class="mx-10 text pointer" v-on:click.stop="actionLink('/marketers/' + marketer._id)"><i class="far fa-gear"></i></div>
            </div>-->
        </div>

        <!--Linea separadora-->
        <div class="separator my-5"></div>
    </div>
</template>

<style scoped>

</style>
