<template>
    <div>

        <!--mes, año y cambiar de mes-->
        <div class="d-flex justify-between mx-20">

            <!--Mes-->
            <p class="text capitalize" data-size="15" data-weight="600">{{monthName}}, {{year}}</p>

            <!--Flechas cambiar de mes-->
            <p class="text"><i class="fas fa-chevron-left mr-20 pointer" v-on:click="changeMonth(-1)"></i> <i class="fas fa-chevron-right pointer" v-on:click="changeMonth(1)"></i></p>

        </div>

        <!--Calendario en sí-->
        <div class="calendar-item mt-20">

            <div class="day-box text" v-for="day in calendar.days" v-on:click="selectDay(day)" v-bind:class="{'selected': day.day ===  selectedDate.day}">

                <div class="day d-flex column pointer" v-bind:class="{'opacity-3': day.isOut}">
                    {{day.dayNumber}}
                </div>

                <i v-if="day.hasTasks" class="fas fa-circle task-circle" v-bind:class="{'opacity-3': day.isOut}"></i>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: "CalendarItemComponent",
    props:['basicData', 'selectedDate', 'tasks'],
    data(){
        return{
            dateCursor: moment(),
            monthsNames: ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],
            weekDaysNamesShort: ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'],
            formatDates: 'YYYY-MM-DD',
            monthName: 'Cargando...',
            year: 'Cargando...',

            calendar: {
                days: []
            }
        }
    },
    mounted() {
        this.renderCalendar()
    },
    methods:{
        changeMonth(value) {
            this.dateCursor.add(value, 'month');
            this.renderCalendar(this.dateCursor);
        },
        renderCalendar(date) {
            let calendar = {
                days: []
            }

            let dateObject = moment(date);

            let firstDay = dateObject.clone().startOf('month');
            let lastDay = dateObject.clone().endOf('month');

            let cursorDay = firstDay.clone();

            for (let day = firstDay.format('D'); day <= lastDay.format('D'); day++) {
                calendar.days.push(this.prepareDay(cursorDay.clone(), false));
                cursorDay.add(1, 'day');
            }

            let prevMonth = firstDay.clone().subtract(1, 'day');
            let dayOfWeekFirstDay = firstDay.day();

            if (dayOfWeekFirstDay == 0) dayOfWeekFirstDay = 6; else dayOfWeekFirstDay -= 1;

            for (let end = (dayOfWeekFirstDay - 1); end >= 0; end--) {
                calendar.days.unshift(this.prepareDay(prevMonth.clone(), true));
                prevMonth.subtract(1, 'day');
            }

            // -> días de después
            let nextMonth = lastDay.clone().add(1, 'day');
            let nextMonthHelp = lastDay.clone().add(2, 'day');

            let dayOfWeekLastDay = lastDay.day();
            if (dayOfWeekLastDay == 0) dayOfWeekLastDay = 6; else dayOfWeekLastDay -= 1;


            for (let start = (dayOfWeekLastDay * 1 + 1); start <= 6; start++) {
                calendar.days.push(this.prepareDay(nextMonth.clone(), true));
                nextMonth.add(1, 'day');
            }

            this.loadTitleCalendar();

            this.calendar = calendar;
        },
        prepareDay(date, isOut) {


            let hasTasks = false;

            //Compruebo si hay alguna tarea de ese día
            this.tasks.filter((task) => {
                let equalDay = moment(task.finalDate).isSame(date,'day')

                let equalMonth = moment(task.finalDate).isSame(date,'month')

                let equalYear = moment(task.finalDate).isSame(date,'year')


                if( equalDay && equalMonth && equalYear )
                    hasTasks = true
            })


            return {
                day: date,
                dayNumber: date.format('DD'),
                hasTasks: hasTasks,
                isOut: isOut
            };
        },
        loadTitleCalendar() {
            let date = moment(this.dateCursor);
            this.monthName = this.monthsNames[date.format('M') * 1 - 1];
            this.year = date.format('YYYY');
        },
        selectDay(day){
            this.$emit('selectDate', day)
        }
    }
}
</script>

<style scoped>

</style>
