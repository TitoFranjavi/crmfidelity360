<template>
    <div>

        <!--mes, año y cambiar de mes-->
        <div class="d-flex justify-between mx-20">

            <!--Mes-->
            <p class="text capitalize" data-size="15" data-weight="600" v-if="calendar.titleData">{{calendar.titleData.monthName}}, {{calendar.titleData.year}}</p>

            <!--Flechas cambiar de mes-->
            <p class="text"><i class="fas fa-chevron-left mr-20 pointer" v-on:click="changeMonth(-1)"></i> <i class="fas fa-chevron-right pointer" v-on:click="changeMonth(1)"></i></p>

        </div>

        <!--Calendario en sí-->
        <div class="calendar-item mt-20">

            <div class="day-box text" v-for="day in calendar.days" v-on:click="selectDay(day.day)" v-bind:class="{'selected': day.day ===  selectedDate.day}">
                <div class="day d-flex column pointer" :data-weight="day.hasEvents ? 700 : 300" v-bind:class="{'opacity-3': day.isOut}">
                    {{day.dayNumber}}
                </div>

                <i v-if="day.hasEvents" class="fas fa-circle task-circle" v-bind:class="{'opacity-3': day.isOut}"></i>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: "EventsCalendarItemComponent",
    props:['calendar', 'selectedDate'],
    methods:{
            changeMonth(value) {
            this.$emit('changeMonth', value)
        },
        selectDay(day){
            this.$emit('selectDate', day)
        }
    }
}
</script>

<style scoped>

</style>
