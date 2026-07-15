<template>
    <div class="d-flex column">

        <div class="contact four pointer" v-on:dblclick="actionLink('/opportunities/' + opportunity._id)"
            v-on:click.prevent="seeOpportunityInfo(opportunity)"
            v-bind:class="{ 'seeing': opportunitySelectedToSee._id === opportunity._id }">
            <!--checkbox-->
            <div class="custom-checkbox pointer" v-on:click.stop.prevent="toggleSelectOpportunity(opportunity)"
                v-bind:class="{ 'selected': opportunitiesSelected.indexOf(opportunity._id) != -1 }"></div>

            <!--iniciales-->
            <div class="d-flex justify-center">
                <div class="d-flex justify-center mr-10">
                    <div class="initials" data-style="initials" v-if="opportunity.name">{{ getInitials(opportunity.name)
                        }}</div>
                </div>
            </div>

            <!--Nombre y CIF-->
            <div class="d-flex column">
                <a v-on:click.prevent=""
                v-on:dblclick="actionLink('/opportunities/' + opportunity._id)"
                :href="'/opportunities/' + opportunity._id"
                class="ellipsis"
                data-color="azul"
                data-weight="600">

                    <i
                        v-if="opportunity.source === 'openComparator'"
                        class="fa-solid fa-globe mr-5"
                        style="color:#3B82F6"
                        title="Oportunidad del comparador">
                    </i>

                    {{ (opportunity.name && opportunity.name.trim() !== '')
                        ? opportunity.name
                        : (opportunity.order?.name || 'Sin nombre') }}

                </a>

                <p class="text ellipsis" data-size="10">
                    {{
                        (opportunity.order?.name && opportunity.order.name.trim() !== '' && opportunity.order?.CUPS)
                            ? opportunity.order.CUPS.slice(-6)
                            : (opportunity.CIF ? opportunity.CIF : '-')
                    }}
                </p>
            </div>





            <!--Email y telefono-->
            <div class="d-flex column">
                <p class="ellipsis" data-color="azul" data-weight="600">{{ opportunity.email ? opportunity.email : '-'
                    }}</p>
                <p class="text ellipsis" data-size="10">{{ opportunity.phone ? opportunity.phone : '-' }}</p>
            </div>

            <!--Estado-->
            <div class="d-flex column hide-middle">
                <p class="ellipsis" data-color="azul" data-weight="600">{{ opportunity.status ? opportunity.status : '-'
                    }}</p>
            </div>

            <!--Fec. creación-->
            <div class="d-flex column">
                <p class="ellipsis" data-color="azul" data-weight="600">{{ getPrettyDate(opportunity.createdAt) }}</p>
            </div>

            <!--Botones-->
            <div class="d-flex">

                <div class="mx-10 text pointer" v-on:click.stop="actionLink('/opportunities/' + opportunity._id)"><i
                        class="far fa-gear"></i></div>

                <div class="mx-10 text pointer" v-if="!isReadOnly"
                    v-on:click.stop.prevent="toggleArchiveOpportunity(opportunity)"><i class="far"
                        v-bind:class="isSeeingArchived ? 'fa-box-open' : 'fa-box-archive'"></i></div>

                <div class="mx-10 text pointer" data-color="rojo" v-if="!isReadOnly"
                    v-on:click.stop.prevent="deleteOpportunity(opportunity)"><i class="far fa-trash"></i></div>
            </div>
        </div>

        <!--Info para mas pequeño-->
        <div class="show-middle d-flex justify-around" v-if="opportunitySelectedToSee._id === opportunity._id">

            <!--Info básica-->
            <div class="d-flex column w-50 pl-80 pr-20">

                <!--Fuente-->
                <div class="d-flex justify-between my-10">
                    <div class="text mr-10" data-weight="600">Fuente</div>

                    <div class="text" data-weight="300">{{ opportunity.source.title ? (opportunity.source.title ===
                        'Personalizado' ? opportunity.source.custom : opportunity.source.title) : '-' }}</div>
                </div>

                <!--Sitio web-->
                <div class="d-flex justify-between my-10">
                    <div class="text mr-10" data-weight="600">Sitio web</div>

                    <div class="text" data-weight="300" v-if="opportunitySelectedToSee.website">{{
                        opportunitySelectedToSee.website }}</div>

                    <div class="text opacity-5" v-else>-</div>
                </div>

            </div>

            <!--Info facturación-->
            <div class="d-flex column w-50 pl-20 pr-20 ">
                <!--Comunidad-->
                <div class="d-flex justify-between my-10 ">
                    <div class="text mr-10" data-weight="600">Comunidad</div>

                    <div class="text" data-weight="300">{{ opportunitySelectedToSee.billingInfo.community.name ?
                        opportunitySelectedToSee.billingInfo.community.name : '-' }}</div>
                </div>

                <!--Provincia-->
                <div class="d-flex justify-between my-10">
                    <div class="text" data-weight="600">Provincia</div>

                    <div class="text" data-weight="300">{{ opportunitySelectedToSee.billingInfo.province.name ?
                        opportunitySelectedToSee.billingInfo.province.name : '-' }}</div>
                </div>


                <!--Localidad-->
                <div class="d-flex justify-between my-10">
                    <div class="text" data-weight="600">Localidad</div>

                    <div class="text" data-weight="300">{{ opportunitySelectedToSee.billingInfo.locality.name ?
                        opportunitySelectedToSee.billingInfo.locality.name : '-' }}</div>
                </div>
            </div>

        </div>

        <!--Linea separadora-->
        <div class="separator my-5"></div>
    </div>
</template>

<script>
export default {
    name: "opportunityCardComponent",
    props: ['opportunity', 'opportunitySelectedToSee', 'opportunitiesSelected', 'isSeeingArchived', 'isReadOnly'],
    methods: {
        getPrettyDate(date) {
            let dateNow = new Date(date);
            let day = String(dateNow.getDate()).padStart(2, '0'); // Asegura que el día tenga dos dígitos
            let month = String(dateNow.getMonth() + 1).padStart(2, '0'); // Asegura que el mes tenga dos dígitos y se suma 1 porque en JavaScript los meses van de 0 a 11
            let year = dateNow.getFullYear();
            return `${day}/${month}/${year}`;
        },
        toggleArchiveOpportunity(opportunity) {
            this.$emit('toggleArchiveOpportunity', opportunity)
        },
        deleteOpportunity(opportunity) {
            this.$emit('deleteOpportunity', opportunity)
        },
        seeOpportunityInfo(opportunity) {
            this.$emit('seeOpportunityInfo', opportunity)
        },
        toggleSelectOpportunity(opportunity) {
            this.$emit('toggleSelectOpportunity', opportunity)
        },
        getDisplayName(opp) {
            const name = (opp?.name || '').trim();
            const orderName = (opp?.order?.name || '').trim();

            if (name !== '') return name;
            if (orderName !== '') {
                return opp?.order?.CUPS
                    ? `${orderName} - ${opp.order.CUPS.slice(-6)}`
                    : orderName;
            }
            return 'Sin nombre';
        },
        getInitials(name) {

            if (name) {
                let nameSplited = name.split(/\s+/)

                let initials = nameSplited[0][0];

                if (nameSplited[1])
                    initials += nameSplited[1][0];

                return initials
            }
        },
        actionLink(route) {
            this.$router.push(route)
        },
        openInNewTab(url) {
            window.open(url, '_blank');
        }
    }
}
</script>

<style scoped></style>
