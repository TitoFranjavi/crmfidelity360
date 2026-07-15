<template>
    <div>

        <!--Estilo movil-->
        <div class="mobile-item">
            <div class="content-white">

                <!--Título-->
                <div class="d-flex justify-between">

                    <div class="text my-10" data-size="22" data-weight="700">Calendario</div>

                    <div class="custom-button my-auto" data-size="small" data-bg="amarillo" data-weight="600" v-on:click="actionLink('/calendar/register')"><i class="fas fa-plus"></i></div>
                </div>


                <!--mes y selección calendario-->
                <div class="d-flex justify-between align-center">
                    <div v-if="bigCalendar.titleData" v-on:click="isOpenenedSmallCalendar = !isOpenenedSmallCalendar" class="text my-30" data-weight="600">{{ bigCalendar.titleData.monthName }} de {{bigCalendar.titleData.year}} <i class="fas" :class=" isOpenenedSmallCalendar ? 'fa-chevron-up' : 'fa-chevron-down'"></i></div>

                    <!--seleccionar tipo de display-->
                    <select class="custom-button" data-size="small" data-bg="blanco" data-color="principal" v-model="displaySelected" v-on:change="renderBigCalendar">
                        <option :value="displayType.code" v-for="displayType in displayTypes">{{ displayType.title }}</option>
                    </select>
                </div>

                <!--calendario días seleccionable-->
                <div v-if="isOpenenedSmallCalendar">

                    <div class="separator my-20"></div>

                    <events-calendar-item-component :calendar="smallCalendar" :selectedDate="selectedDay" @changeMonth="changeMonthSmallCalendar" @selectDate="selectDate"></events-calendar-item-component>

                    <div class="separator my-20"></div>
                </div>



                <!--Calendario en sí-->
                <div class="calendar">

                    <!--si es día-->
                    <div v-if="displaySelected === 'day'" class="day">

                        <!--Header-->
                        <div class="days-header d-flex" v-if="bigCalendar.day">

                            <div class="day column justify-center" v-bind:class="{today: compareMomentDates(bigCalendar.day.day, today)}">
                                <!--Número-->
                                <p class="text number-day text-center" data-size="18" data-weight="700">{{ bigCalendar.day.dayNumber }}</p>

                                <!--Día de la semana-->
                                <p class="text opacity-6" data-size="10">{{ bigCalendar.day.dayOfWeek }}</p>
                            </div>

                        </div>

                        <!--Contenido-->
                        <div class=" mt-20">

                            <!--Los eventos que empiezan antes, terminan después o duran el día entero-->
                            <div class="events-allday mt-20 mb-30">

                                <div v-if="bigCalendar.day && loadedCharged && !isSignedIn" v-for="event in bigCalendar.day.eventsAllDay" v-on:click="toggleSelectEvent(event)" :data-bg="event.color">
                                    <!--Título-->
                                    {{ event.subject }}
                                </div>

                                <!--eventos calendario google-->
                                <div v-if="bigCalendar.day && loadedCharged && isSignedIn" v-for="event in bigCalendar.day.eventsAllDay" v-on:click="toggleSelectEvent(event)" :style="`background-color: ${!!event.colorId ? this.googleCalendarColors[event.colorId] : this.googleCalendarColors[7]}; color: white !important;`">
                                    <!--Título-->
                                    {{ event.summary }}
                                </div>
                            </div>


                            <div class="day-content">
                                <!--Horas-->
                                <div class="hours mr-10">
                                    <div class="axe-hour" v-for=" i in 24">
                                        {{ getAxeHours(i - 1) }}
                                    </div>
                                </div>

                                <!--Distribución calendario-->
                                <div class="content w-100">

                                    <!--Calculo cada 3 cuartos de hora para dividir las horas ( saco la final que es el i * 4 cuartos de hora y la principal el final menos 4 cuartosd de hora)-->
                                    <div class="axe" v-for=" i in 24" :style="`grid-row:${(i * 4) - 4 == 0 ? 1: (i * 4) - 3}/${(i * 4) + 1};`"></div>

                                    <!--Eventos calendario normal--> <!--Le sumo uno al row start y end pq para hacer las lineas por hora le he tenido que suma una pq no puede empezar desde 0-->
                                    <div v-if="bigCalendar.day && loadedCharged && !isSignedIn" v-for="event in bigCalendar.day.events" v-on:click="toggleSelectEvent(event)" class="event" :style="`grid-row-start: ${event.rowStart + 1}; grid-row-end: ${event.rowEnd + 1}; margin-left:${event.eventsInTime * 30}px; padding:${event.rowEnd - event.rowStart > 8 ? '10' : '0'}px;`" v-bind:class="{small: (event.rowEnd - event.rowStart < 8)}" :data-bg="event.color">

                                        <!--Asunto-->
                                        <p class="text ev-subj" data-weight="600">{{ event.subject }}</p>

                                        <!--Descripción-->
                                        <p class="text ev-desc" data-size="13">{{ event.desc }}</p>

                                        <!--Hora-->
                                        <p class="ev-hour opacity-7" data-size="10">{{ getHourFormated(event.date.start) }} - {{ getHourFormated(event.date.end) }}</p>
                                    </div>


                                    <!--Eventos calendario google--> <!--Le sumo uno al row start y end pq para hacer las lineas por hora le he tenido que suma una pq no puede empezar desde 0-->
                                    <div v-if="bigCalendar.day && loadedCharged && isSignedIn" v-for="event in bigCalendar.day.events" v-on:click="toggleSelectEvent(event)" class="event" :style="`grid-row-start: ${event.rowStart + 1}; grid-row-end: ${event.rowEnd + 1}; margin-left:${event.eventsInTime * 30}px; padding:${event.rowEnd - event.rowStart > 8 ? '10' : '0'}px; background-color: ${!!event.colorId ? this.googleCalendarColors[event.colorId] : this.googleCalendarColors[7]}; color: white !important; z-index: ${event.eventsInTime + 1};`" v-bind:class="{small: (event.rowEnd - event.rowStart < 8)}">

                                        <!--Asunto-->
                                        <p class="text ev-subj" data-weight="600">{{ event.summary }}</p>

                                        <!--Descripción-->
                                        <p class="text ev-desc" data-size="13">{{ event.description }}</p>

                                        <!--Hora-->
                                        <p class="ev-hour opacity-7" data-size="10">{{ getHourFormated(!!event.start.dateTime ? event.start.dateTime : event.start.date) }} - {{ getHourFormated(!!event.end.dateTime ? event.end.dateTime : event.end.date) }}</p>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!--si es semana-->
                    <div v-else-if="displaySelected === 'week'" class="week">


                        <!--Header días--> <!--Los meto en un array-->
                        <div class="days-header-list ml-30 px-10 mb-30">
                            <div class="day-header d-flex" v-for="(day, dayInd) in bigCalendar.daysHeader">

                                <div class="day column justify-center" v-bind:class="{today: compareMomentDates(day.day.day, today)}">

                                    <p class="text number-day text-center" data-size="18" data-weight="700">{{ day.dayNumber }}</p>


                                    <p class="text text-center opacity-6" data-size="10">{{ getIniOfDay(day.dayOfWeek) }}</p>
                                </div>
                            </div>
                        </div>

                        <!--Eventos de más de un día y día completo-->
                        <div class="days-header-list  ml-30 px-10">

                            <!--calendario normal-->
                            <div v-if="loadedCharged && !isSignedIn" class="event-header pointer" v-for="(event, i) in bigCalendar.eventsTop" v-on:click="toggleSelectEvent(event.event)" :data-bg="event.event.color" :style="`grid-column: ${event.columnStart}/${event.columnEnd}; grid-row-start: ${i+1}`">
                                <div class="text ellipsis">{{ event.event.subject }}</div>
                            </div>

                            <!--calendario google-->
                            <div v-if="loadedCharged && isSignedIn" class="event-header pointer" v-for="(event, i) in bigCalendar.eventsTop" v-on:click="toggleSelectEvent(event.event)" :style="`grid-column: ${event.columnStart}/${event.columnEnd}; grid-row-start: ${i+1}; background-color: ${!!event.colorId ? this.googleCalendarColors[event.colorId] : this.googleCalendarColors[7]}; color: white !important;`">
                                <div class="text ellipsis">{{ event.event.summary }}</div>
                            </div>
                        </div>

                        <!--Eventos en conjunto-->
                        <div class="week-content mt-30">

                            <!--Horas-->
                            <div class="hours p-5">
                                <div class="axe-hour mx-auto" v-for=" i in 24" data-size="10" data-weight="600">
                                    {{ getAxeHours(i - 1) }}
                                </div>
                            </div>


                            <!--Cada uno de los días-->
                            <div class="days-list">
                                <div v-if="bigCalendar.days" v-for="day in bigCalendar.days" class="day">

                                    <!--Calculo cada 3 cuartos de hora para dividir las horas ( saco la final que es el i * 4 cuartos de hora y la principal el final menos 4 cuartosd de hora)-->
                                    <div class="axe" v-for=" i in 24" :style="`grid-row:${(i * 4) - 4 == 0 ? 1: (i * 4) - 3}/${(i * 4) + 1};`"></div>

                                    <!--Eventos--> <!--Le sumo uno al row start y end pq para hacer las lineas por hora le he tenido que suma una pq no puede empezar desde 0-->
                                    <div v-if="loadedCharged && !isSignedIn" v-for="event in day.events" v-on:click="toggleSelectEvent(event)" class="event" :style="`grid-row-start: ${event.rowStart + 1}; grid-row-end: ${event.rowEnd + 1}; margin-left:${event.eventsInTime * 5}px; padding:${event.rowEnd - event.rowStart > 8 ? '5' : '0'}px;`" v-bind:class="{small: (event && event.rowStart && (event.rowEnd - event.rowStart < 8))}" :data-bg="event.color">

                                        <!--Asunto-->
                                        <p class="text ev-subj ellipsis" data-weight="500" data-size="12">{{ event.subject }}</p>
                                    </div>

                                    <!--Eventos--> <!--Le sumo uno al row start y end pq para hacer las lineas por hora le he tenido que suma una pq no puede empezar desde 0-->
                                    <div v-if="loadedCharged && isSignedIn" v-for="event in day.events" v-on:click="toggleSelectEvent(event)" class="event" :style="`grid-row-start: ${event.rowStart + 1}; grid-row-end: ${event.rowEnd + 1}; margin-left:${event.eventsInTime * 5}px; padding:${event.rowEnd - event.rowStart > 8 ? '5' : '0'}px; background-color: ${!!event.colorId ? this.googleCalendarColors[event.colorId] : this.googleCalendarColors[7]}; color: white !important; z-index: ${event.eventsInTime + 1};`" v-bind:class="{small: (event && event.rowStart && (event.rowEnd - event.rowStart < 8))}">

                                        <!--Asunto-->
                                        <p class="text ev-subj ellipsis" data-weight="500" data-size="12">{{ event.summary }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--si es mes-->
                    <div v-else-if="displaySelected === 'month'" class="month">

                        <!--Header días-->
                        <div class="month-days-header">

                            <div class="text text-center opacity-7" data-size="17" data-weight="600" v-for="day in daysOfWeek.slice(1)">{{ getIniOfDay(day) }}</div>

                            <div class="text text-center opacity-7" data-size="15" data-weight="600">{{ getIniOfDay(daysOfWeek[0]) }}</div>
                        </div>

                        <div class="content">

                            <!--Semanas-->
                            <div class="month-week" v-for="week in bigCalendar">

                                <!--Días de la semana-->
                                <div class="week-day" v-for="day in week.days">

                                    <!--Header-->
                                    <div class="d-flex column justify-center align-center mt-5">
                                        <p class="text pointer" data-size="15" data-weight="600" v-on:click="selectDate(day.day)" v-bind:class="{ 'opacity-3': day.isOut}">{{ day.dayNumber }}</p>
                                    </div>
                                </div>

                                <!--Eventos calendario normal-->
                                <div v-if="loadedCharged && !isSignedIn" class="pointer" v-for="(event, i) in week.events" v-on:click="toggleSelectEvent(event.event)" :data-bg="event.event.color" :style="`grid-column: ${event.columnStart}/${event.columnEnd}; grid-row-start: ${i+2}; padding: 0 10px;`">
                                    <p class="text ev-subj ellipsis" data-weight="500" data-size="12">{{ event.event.subject }}</p>
                                </div>

                                <!--Eventos calendario google-->
                                <div v-if="loadedCharged && isSignedIn" class="pointer" v-for="(event, i) in week.events" v-on:click="toggleSelectEvent(event.event)" :data-bg="event.event.color" :style="`grid-column: ${event.columnStart}/${event.columnEnd}; grid-row-start: ${i+2}; padding: 0 10px; background-color: ${!!event.colorId ? this.googleCalendarColors[event.colorId] : this.googleCalendarColors[7]}; color: white !important; z-index: ${event.eventsInTime + 1}; border-radius: 15px;`">
                                    <p class="text ev-subj ellipsis" data-weight="500" data-size="12">{{ event.event.summary }}</p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!--si es año-->
                    <div v-else class="year">

                        <!--Listado de meses-->
                        <div v-for="(month, monthName) in bigCalendar.months" class="month-box">

                            <div class="month-year">

                                <!--Nombre del mes-->
                                <p class="text" data-size="15" data-weight="600">{{ monthName }}</p>

                                <!--header-->
                                <div class="daysTextHeader">
                                    <div class="opacity-4 text-center" data-size="15">L</div>
                                    <div class="opacity-4 text-center" data-size="15">M</div>
                                    <div class="opacity-4 text-center" data-size="15">X</div>
                                    <div class="opacity-4 text-center" data-size="15">J</div>
                                    <div class="opacity-4 text-center" data-size="15">V</div>
                                    <div class="opacity-4 text-center" data-size="15">S</div>
                                    <div class="opacity-4 text-center" data-size="15">D</div>
                                </div>

                                <!--días-->
                                <div class="days">
                                    <div v-bind:class="{'today': compareMomentDates(day.day, today)}" class="text" data-size="15" v-for="day in month" v-on:click="selectDate(day.day)">{{ day.dayNumber }}</div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!--Estilo pc-->
        <div class="desktop-item d-flex relPos">

            <div class="content-white contact-selected">

                <!--Mensajito de calendario no vinculado con Google calendar-->
                <div class="sign-in-label" v-if="loadedCharged && !isSignedIn">
                    <div class="mr-10 d-flex" data-size="11">

                        <p class="opacity-5">Tu calendario no esta sincronizado con Google Drive</p>

                        <div class="infoBox ml-10">
                            <i class="fa-regular fa-circle-info opacity-5 pointer"></i>

                            <div class="infoText"><p class="" data-size="11">Si inicias sesión se sincronizarán los eventos de tu calendario, si no tus eventos se mantendran en el calendario del crm</p></div>
                        </div>
                    </div>

                    <div class="custom-button ml-20" data-size="small" data-bg="amarillo" v-on:click="signInGoogle">Inicia sesión</div>
                </div>

                <div class="sign-in-label" v-if="loadedCharged && isSignedIn">
                    <div class="opacity-5 mr-10" data-size="11">

                        Calendario sincronizado con Google Calendar

                        <div class="infoBox">
                            <i class="fa-regular fa-circle-info opacity-5 pointer"></i>

                            <div class="infoText"><p class="opacity-6" data-size="11">Tus eventos están siendo sincronizados en Google calendar</p></div>
                        </div>

                    </div>
                </div>

                <!--Header-->
                <div class="d-flex justify-between align-center" style="height: 10%">


                    <!--Eventos pendientes-->
                    <div class="d-flex" >
                        <p class="text mr-30 my-auto" data-size="25" data-weight="700">Calendario</p>

                        <!--Display día, mes, año-->
                        <div v-if="bigCalendar.titleData" class="d-flex my-auto">

                            <!--Flechas cambiar-->
                            <div class="d-flex my-auto mr-10">
                                <i class="fa-solid fa-chevron-left text pointer mx-5" data-size="17" v-on:click="changeBigCalendar( this.displaySelected, -1)"></i>
                                <i class="fa-solid fa-chevron-right text pointer mx-5" data-size="17" v-on:click="changeBigCalendar( this.displaySelected, 1)"></i>
                            </div>

                            <!--Segun el calendario-->
                            <div v-if="displaySelected === 'day' && bigCalendar.day" class="text" data-size="15" data-weight="700">{{bigCalendar.day.dayNumber}} de {{bigCalendar.titleData.monthName}} de {{bigCalendar.titleData.year}}</div>

                            <div v-else-if="displaySelected === 'week'" class="text" data-size="15" data-weight="700">{{bigCalendar.titleData.monthName}} ,{{bigCalendar.titleData.year}}</div>

                            <div v-else-if="displaySelected === 'month'" class="text" data-size="15" data-weight="700">{{bigCalendar.titleData.monthName}} ,{{bigCalendar.titleData.year}}</div>

                            <div v-else class="text" data-size="15" data-weight="700">{{bigCalendar.titleData.year}}</div>

                        </div>
                    </div>

                    <!--Botones-->
                    <div class="d-flex">
                        <!--Visualizar-->
                        <select class="custom-button" data-size="regular" data-bg="blanco" data-color="principal" v-model="displaySelected" v-on:change="changeDisplayType">
                            <option :value="displayType.code" v-for="displayType in displayTypes">{{ displayType.title }}</option>
                        </select>

                        <div class="custom-button ml-20" data-size="regular" data-bg="principal" v-if="!isReadOnly" v-on:click="actionLink('/calendar/register')">Añadir evento</div>
                    </div>
                </div>


                <!--Según como este cogido el tipo de display se vera semana, mes, año, etc-->
                <div class="calendar">

                    <!--Si es día-->
                    <div v-if="displaySelected === 'day'" class="day">


                        <!--Header-->
                        <div class="days-header d-flex" v-if="bigCalendar.day">

                            <!--<div class="mt-4 pointer" v-on:click="changeDate(-1)">
                                <i class="fas fa-chevron-left" data-color="principal"></i>
                            </div>-->

                            <div class="day column justify-center" v-bind:class="{today: compareMomentDates(bigCalendar.day.day, today)}">
                                <!--Número-->
                                <p class="text number-day text-center" data-size="18" data-weight="700">{{ bigCalendar.day.dayNumber }}</p>

                                <!--Día de la semana-->
                                <p class="text opacity-6" data-size="10">{{ bigCalendar.day.dayOfWeek }}</p>
                            </div>

                            <!--<div class="mt-4 pointer" v-on:click="changeDate(1)">
                                <i class="fas fa-chevron-right" data-color="principal" ></i>
                            </div>-->

                        </div>

                        <!--Contenido-->
                        <div class=" mt-20">

                            <!--Los eventos que empiezan antes, terminan después o duran el día entero-->
                            <div class="events-allday mt-20 mb-30">


                                <!--eventos calendario normal-->
                                <div v-if="bigCalendar.day && loadedCharged && !isSignedIn" v-for="event in bigCalendar.day.eventsAllDay" v-on:click="toggleSelectEvent(event)" :data-bg="event.color">

                                    <!--Título-->
                                    {{ event.subject }}
                                </div>

                                <!--eventos calendario google-->
                                <div v-if="bigCalendar.day && loadedCharged && isSignedIn" v-for="event in bigCalendar.day.eventsAllDay" v-on:click="toggleSelectEvent(event)" :style="`background-color: ${!!event.colorId ? this.googleCalendarColors[event.colorId] : this.googleCalendarColors[7]}; color: white !important;`">

                                    <!--Título-->
                                    {{ event.summary }}
                                </div>
                            </div>


                            <div class="day-content">
                                <!--Horas-->
                                <div class="hours mr-10">
                                    <div class="axe-hour" v-for=" i in 24">
                                        {{ getAxeHours(i - 1) }}
                                    </div>
                                </div>


                                <!--Distribución calendario-->
                                <div class="content w-100">

                                    <!--Calculo cada 3 cuartos de hora para dividir las horas ( saco la final que es el i * 4 cuartos de hora y la principal el final menos 4 cuartosd de hora)-->
                                    <div class="axe" v-for=" i in 24" :style="`grid-row:${(i * 4) - 4 == 0 ? 1: (i * 4) - 3}/${(i * 4) + 1};`"></div>

                                    <!--Eventos calendario normal--> <!--Le sumo uno al row start y end pq para hacer las lineas por hora le he tenido que suma una pq no puede empezar desde 0-->
                                    <div v-if="bigCalendar.day && loadedCharged && !isSignedIn" v-for="event in bigCalendar.day.events" v-on:click="toggleSelectEvent(event)" class="event" :style="`grid-row-start: ${event.rowStart + 1}; grid-row-end: ${event.rowEnd + 1}; margin-left:${event.eventsInTime * 100}px; padding:${event.rowEnd - event.rowStart > 8 ? '10' : '0'}px;`" v-bind:class="{small: (event.rowEnd - event.rowStart < 8), 'p-10': !(event.rowEnd - event.rowStart < 8) }" :data-bg="event.color">

                                        <!--Asunto-->
                                        <p class="text ev-subj" data-weight="600">{{ event.subject }}</p>

                                        <!--Descripción-->
                                        <p class="text ev-desc" data-size="13">{{ event.desc }}</p>

                                        <!--Hora-->
                                        <p class="ev-hour opacity-7" data-size="10">{{ getHourFormated(event.date.start) }} - {{ getHourFormated(event.date.end) }}</p>
                                    </div>

                                    <!--Eventos calendario google-->
                                    <div v-if="bigCalendar.day && loadedCharged && isSignedIn" v-for="event in bigCalendar.day.events" v-on:click="toggleSelectEvent(event)" class="event" :style="`grid-row-start: ${event.rowStart + 1}; grid-row-end: ${event.rowEnd + 1}; margin-left:${event.eventsInTime * 100}px; padding:${event.rowEnd - event.rowStart > 8 ? '10' : '0'}px; background-color: ${!!event.colorId ? this.googleCalendarColors[event.colorId] : this.googleCalendarColors[7]}; color: white !important; z-index: ${event.eventsInTime + 1};`" v-bind:class="{small: (event.rowEnd - event.rowStart < 8), 'p-10': !(event.rowEnd - event.rowStart < 8) }" >

                                        <!--Asunto-->
                                        <p class="text ev-subj" data-color="blanco" data-weight="600">{{ event.summary }}</p>

                                        <!--Descripción-->
                                        <p class="text ev-desc" data-size="13">{{ event.description }}</p>

                                        <!--Hora-->
                                        <p class="ev-hour opacity-7" data-size="10">{{ getHourFormated(!!event.start.dateTime ? event.start.dateTime : event.start.date) }} - {{ getHourFormated(!!event.end.dateTime ? event.end.dateTime : event.end.date) }}</p>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>

                    <!--Si es semana-->
                    <div v-else-if="displaySelected === 'week'" class="week">


                        <!--Header días--> <!--Los meto en un array-->
                        <div class="global-days-header-list relPos">

                            <!--<div class="right absPos pointer" data-color="principal" v-on:click="changeDate(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </div>-->


                            <div class="days-header-list ml-60 px-10 mb-30">

                                <div class="day-header d-flex" v-for="day in bigCalendar.daysHeader">

                                    <div class="day column justify-center" v-bind:class="{today: compareMomentDates(day.day.day, today)}">

                                        <p class="text number-day text-center" data-size="18" data-weight="700">{{ day.dayNumber }}</p>


                                        <p class="text opacity-6" data-size="10">{{ day.dayOfWeek }}</p>
                                    </div>
                                </div>

                            </div>


                            <!--<div class="left absPos pointer" data-color="principal" v-on:click="changeDate(1)">
                                <i class="fas fa-chevron-right"></i>
                            </div>-->
                        </div>


                        <!--Eventos de más de un día y día completo-->
                        <div class="days-header-list  ml-60 px-10">
                            <div v-if="loadedCharged && !isSignedIn" class="event-header pointer" v-on:click="toggleSelectEvent(event.event)" :data-bg="event.event.color" :style="`grid-column: ${event.columnStart}/${event.columnEnd}; grid-row-start: ${i+1}`" v-for="(event, i) in bigCalendar.eventsTop">
                                <div class="text ellipsis">{{ event.event.subject }}</div>
                            </div>


                            <!--calendario google-->
                            <div v-if="loadedCharged && isSignedIn" class="event-header pointer" v-for="(event, i) in bigCalendar.eventsTop" v-on:click="toggleSelectEvent(event.event)" :style="`grid-column: ${event.columnStart}/${event.columnEnd}; grid-row-start: ${i+1}; background-color: ${!!event.colorId ? this.googleCalendarColors[event.colorId] : this.googleCalendarColors[7]}; color: white !important;`">
                                <div class="text ellipsis" data-color="blanco">{{ event.event.summary }}</div>
                            </div>
                        </div>

                        <!--Eventos en conjunto-->
                        <div class="week-content mt-30">

                            <!--Horas-->
                            <div class="hours p-10">
                                <div class="axe-hour mx-auto" v-for=" i in 24">
                                    {{ getAxeHours(i - 1) }}
                                </div>
                            </div>

                            <!--Cada uno de los días-->
                            <div class="days-list">

                                <div v-if="bigCalendar.days" v-for="day in bigCalendar.days" class="day">

                                    <!--Calculo cada 3 cuartos de hora para dividir las horas ( saco la final que es el i * 4 cuartos de hora y la principal el final menos 4 cuartosd de hora)-->
                                    <div class="axe" v-for=" i in 24" :style="`grid-row:${(i * 4) - 4 == 0 ? 1: (i * 4) - 3}/${(i * 4) + 1};`"></div>

                                    <!--Eventos calendario normal--> <!--Le sumo uno al row start y end pq para hacer las lineas por hora le he tenido que suma una pq no puede empezar desde 0-->
                                    <div v-if="loadedCharged && !isSignedIn" v-for="event in day.events" v-on:click="toggleSelectEvent(event)" class="event" :style="`grid-row-start: ${event.rowStart + 1}; grid-row-end: ${event.rowEnd + 1}; margin-left:${event.eventsInTime * 20}px; padding:${event.rowEnd - event.rowStart > 8 ? '10' : '0'}px;`" v-bind:class="{small: (event && event.rowStart && (event.rowEnd - event.rowStart < 8)), 'p-10': !(event && event.rowStart && (event.rowEnd - event.rowStart < 8))}" :data-bg="event.color">

                                        <!--Asunto-->
                                        <p class="text ev-subj ellipsis" data-weight="500" data-size="12">{{ event.subject }}</p>
                                    </div>

                                    <!--Eventos calendario google--> <!--Le sumo uno al row start y end pq para hacer las lineas por hora le he tenido que suma una pq no puede empezar desde 0-->
                                    <div v-if="loadedCharged && isSignedIn" v-for="event in day.events" v-on:click="toggleSelectEvent(event)" class="event" :style="`grid-row-start: ${event.rowStart + 1}; grid-row-end: ${event.rowEnd + 1}; margin-left:${event.eventsInTime * 20}px; padding:${event.rowEnd - event.rowStart > 8 ? '10' : '0'}px; ; background-color: ${!!event.colorId ? this.googleCalendarColors[event.colorId] : this.googleCalendarColors[7]}; color: white !important; z-index: ${event.eventsInTime + 1};`" v-bind:class="{small: (event && event.rowStart && (event.rowEnd - event.rowStart < 8)), 'p-10': !(event && event.rowStart && (event.rowEnd - event.rowStart < 8))}">

                                        <!--Asunto-->
                                        <p class="text ev-subj ellipsis" data-weight="500" data-size="12">{{ event.summary }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!--Si es mes-->
                    <div v-else-if="displaySelected === 'month'" class="month">

                        <!--Header días-->
                        <div class="month-days-header">

                            <div class="text text-center opacity-7" data-size="17" data-weight="600" v-for="day in daysOfWeek.slice(1)">{{ day }}</div>

                            <div class="text text-center opacity-7" data-size="15" data-weight="600">{{ daysOfWeek[0] }}</div>
                        </div>

                        <div class="content">

                            <!--Semanas-->
                            <div class="month-week" v-for="week in bigCalendar">

                                <!--Días de la semana-->
                                <div class="week-day" v-for="day in week.days">

                                    <!--Header-->
                                    <div class="d-flex column justify-center align-center mt-5">
                                        <p class="text pointer" data-size="15" data-weight="600"  v-on:click="selectDate(day.day)" v-bind:class="{ 'opacity-3': day.isOut}">{{ day.dayNumber }}</p>
                                    </div>
                                </div>

                                <!--Eventos calendario normal-->
                                <div v-if="loadedCharged && !isSignedIn" class="pointer" v-for="(event, i) in week.events" v-on:click="toggleSelectEvent(event.event)" :data-bg="event.event.color" :style="`grid-column: ${event.columnStart}/${event.columnEnd}; grid-row-start: ${i+2}; padding: 0 10px;`">
                                    <p class="text ev-subj ellipsis" data-weight="500" data-size="12">{{ event.event.subject }}</p>
                                </div>

                                <!--Eventos calendario google-->
                                <div v-if="loadedCharged && isSignedIn" class="pointer" v-for="(event, i) in week.events" v-on:click="toggleSelectEvent(event.event)" :style="`grid-column: ${event.columnStart}/${event.columnEnd}; grid-row-start: ${i+2}; padding: 0 10px; background-color: ${!!event.colorId ? this.googleCalendarColors[event.colorId] : this.googleCalendarColors[7]}; color: white !important; z-index: ${event.eventsInTime + 1}; border-radius: 15px;`">
                                    <p class="text ev-subj ellipsis" data-weight="500" data-size="12">{{ event.event.summary }}</p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!--Si es año-->
                    <div v-else class="year">

                        <!--Listado de meses-->
                        <div v-for="(month, monthName) in bigCalendar.months" class="month-box">

                            <div class="month-year">

                                <!--Nombre del mes-->
                                <p class="text" data-size="10">{{ monthName }}</p>

                                <!--header-->
                                <div class="daysTextHeader">
                                    <div class="opacity-4 text-center">L</div>
                                    <div class="opacity-4 text-center">M</div>
                                    <div class="opacity-4 text-center">X</div>
                                    <div class="opacity-4 text-center">J</div>
                                    <div class="opacity-4 text-center">V</div>
                                    <div class="opacity-4 text-center">S</div>
                                    <div class="opacity-4 text-center">D</div>
                                </div>

                                <!--días-->
                                <div class="days">
                                    <div v-bind:class="{'today': compareMomentDates(day.day, today)}" class="text" v-for="day in month" v-on:click="selectDate(day.day)">{{ day.dayNumber }}</div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="info-content">

                <div class="top-box" v-if="loadedCharged && !isSignedIn">

                    <!--si hay alguno seleccionado-->
                    <div class="h-100 d-flex column justify-between" v-if="selectedEvent">

                        <!--contenido-->
                        <div>
                            <!--Asunto tarea-->
                            <div class="text d-flex capitalize" data-size="18" data-weight="600">{{ selectedEvent.subject }}</div>


                            <!--Descripción-->
                            <p class="text opacity-5 my-10" data-size="12">{{ selectedEvent.desc }}</p>


                        </div>


                        <!--Botones-->
                        <div class="mt-auto d-flex column">

                            <!--Fecha-->
                            <div class="d-flex my-auto">

                                <i class="fas fa-calendar text mr-10 my-auto" data-size="12"></i>

                                <p class="text" data-size="12">{{ getEventDateInfo(selectedEvent.date) }}</p>
                            </div>

                            <!--Usuario creador-->
                            <div class="d-flex my-5 opacity-5" v-if="selectedEvent.createdBy">
                                <i class="fas fa-user text mr-10"></i>

                                <p class="text" data-size="10">{{ selectedEvent.createdBy.firstName }} {{ selectedEvent.createdBy.lastName }}</p>
                            </div>


                            <div class="d-flex justify-between mt-20">
                                <!--Configurar tarea-->
                                <div v-on:click="actionLink('/calendar/'+ ((this.loadedCharged && !this.isSignedIn) ? selectedEvent._id : selectedEvent.id))" class="custom-button mr-10" data-size="medium" data-bg="azul" data-mode="outline" data-color="azul">Editar</div>

                                <!--Eliminar tarea-->
                                <div v-on:click="deleteEvent(selectedEvent)" v-if="!isReadOnly" class="custom-button" data-size="medium" data-bg="rojo" data-mode="outline" data-color="rojo"><i class="fas fa-trash"></i></div>
                            </div>

                        </div>
                    </div>

                    <!--si no hay seleccionado-->
                    <div class="h-100 d-flex column justify-between" v-else>

                        <!--contenido-->
                        <div>
                            <!--Asunto tarea-->
                            <div class="loading w-35 h-25" data-size="18" data-weight="600"></div>


                            <!--Descripción-->
                            <p class="loading w-100 h-100 mt-20" data-size="12"></p>

                        </div>


                        <!--Botones-->
                        <div class="mt-auto d-flex column">

                            <!--Fecha-->
                            <div class="d-flex my-auto">

                                <i class="loading w-5 h-25 mr-10" data-size="12"></i>

                                <p class="loading w-60 h-25" data-size="12"></p>

                            </div>


                            <div class="d-flex justify-between mt-20">
                                <!--Configurar tarea-->
                                <div class="loading w-35 h-35"></div>

                                <!--Eliminar tarea-->
                                <div class="loading w-20 h-35"></div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="top-box" v-if="loadedCharged && isSignedIn">

                    <!--si hay alguno seleccionado-->
                    <div class="h-100 d-flex column justify-between" v-if="selectedEvent">

                        <!--contenido-->
                        <div>
                            <!--Asunto tarea-->
                            <div class="text d-flex" data-size="18" data-weight="600">{{ selectedEvent.summary }}</div>


                            <!--Descripción-->
                            <p class="text opacity-5 my-10" data-size="12">{{ selectedEvent.description }}</p>


                        </div>


                        <!--Botones-->
                        <div class="mt-auto d-flex column">

                            <!--Videollamada meet-->
                            <div class="d-flex pointer mb-10" v-if="!!selectedEvent.hangoutLink">
                                <svg class="my-auto" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="20" height="20" viewBox="0 0 48 48">
                                    <rect width="16" height="16" x="12" y="16" fill="#fff" transform="rotate(-90 20 24)"></rect><polygon fill="#1e88e5" points="3,17 3,31 8,32 13,31 13,17 8,16"></polygon><path fill="#4caf50" d="M37,24v14c0,1.657-1.343,3-3,3H13l-1-5l1-5h14v-7l5-1L37,24z"></path><path fill="#fbc02d" d="M37,10v14H27v-7H13l-1-5l1-5h21C35.657,7,37,8.343,37,10z"></path><path fill="#1565c0" d="M13,31v10H6c-1.657,0-3-1.343-3-3v-7H13z"></path><polygon fill="#e53935" points="13,7 13,17 3,17"></polygon><polygon fill="#2e7d32" points="38,24 37,32.45 27,24 37,15.55"></polygon><path fill="#4caf50" d="M46,10.11v27.78c0,0.84-0.98,1.31-1.63,0.78L37,32.45v-16.9l7.37-6.22C45.02,8.8,46,9.27,46,10.11z"></path>
                                </svg>

                                <a class="text ml-10 custom-button" data-size="small" :href="selectedEvent.hangoutLink" target="_blank" data-color="blanco">Unirme con Google meet</a>
                            </div>

                            <!--Fecha-->
                            <div class="d-flex my-5 opacity-5">

                                <i class="fas fa-calendar text mr-10 my-auto" data-size="12"></i>

                                <p class="text" data-size="10">{{ getEventDateInfoGoogle(!!selectedEvent.start.dateTime ? selectedEvent.start.dateTime : selectedEvent.start.date, !!selectedEvent.end.dateTime ? selectedEvent.end.dateTime : selectedEvent.end.date) }}</p>
                            </div>

                            <!--Ubicación-->
                            <div class="d-flex my-5 opacity-5" v-if="!!selectedEvent.location">

                                <i class="fas fa-location-dot text mr-10 my-auto" data-size="12"></i>

                                <p class="text" data-size="10">{{ selectedEvent.location }}</p>
                            </div>


                            <!--Usuario creador-->
                            <div class="d-flex my-5 opacity-5" v-if="selectedEvent.creator">
                                <i class="fas fa-user text mr-10"></i>

                                <p class="text" data-size="10">{{ selectedEvent.creator.email }}</p>
                            </div>

                            <!--Usuarios invitados-->
                            <div class=" my-5 opacity-5" v-if="selectedEvent.attendees && selectedEvent.attendees.length > 0">

                                <div class="d-flex">
                                    <i class="fas fa-users text mr-10"></i>

                                    <p class="text" data-size="10">Invitados:</p>
                                </div>

                                <div v-for="user in selectedEvent.attendees">
                                    <div class="d-flex" v-if="!user.self">
                                        <p class="text ml-30" data-size="10" >-{{ user.email }}</p>

                                        <p class="ml-10 my-auto" data-size="8" :style="`color: ${googleCalendarUserStatuses[user.responseStatus].color};`"><i class="fa-solid fa-arrow-right"></i> {{ googleCalendarUserStatuses[user.responseStatus].text }}</p>
                                    </div>

                                </div>

                            </div>




                            <div class="d-flex justify-between mt-20">
                                <!--Configurar evento-->
                                <div v-on:click="actionLink('/calendar/' + (this.loadedCharged && !this.isSignedIn ? selectedEvent._id : selectedEvent.id))" class="custom-button mr-10" data-size="medium" data-bg="azul" data-mode="outline" data-color="azul">Editar</div>

                                <!--Eliminar evento-->
                                <div v-on:click="deleteEvent(selectedEvent)" v-if="!isReadOnly" class="custom-button" data-size="medium" data-bg="rojo" data-mode="outline" data-color="rojo"><i class="fas fa-trash"></i></div>
                            </div>

                        </div>
                    </div>

                    <!--si no hay seleccionado-->
                    <div class="h-100 d-flex column justify-between" v-else>

                        <!--contenido-->
                        <div>
                            <!--Asunto tarea-->
                            <div class="loading w-35 h-25" data-size="18" data-weight="600"></div>


                            <!--Descripción-->
                            <p class="loading w-100 h-100 mt-20" data-size="12"></p>

                        </div>


                        <!--Botones-->
                        <div class="mt-auto d-flex column">

                            <!--Fecha-->
                            <div class="d-flex my-auto">

                                <i class="loading w-5 h-25 mr-10" data-size="12"></i>

                                <p class="loading w-60 h-25" data-size="12"></p>

                            </div>


                            <div class="d-flex justify-between mt-20">
                                <!--Configurar tarea-->
                                <div class="loading w-35 h-35"></div>

                                <!--Eliminar tarea-->
                                <div class="loading w-20 h-35"></div>
                            </div>

                        </div>
                    </div>
                </div>


                <div class="separator"></div>

                <!--calendario-->
                <div class="bottom-box">
                    <events-calendar-item-component :calendar="smallCalendar" :selectedDate="selectedDay" @changeMonth="changeMonthSmallCalendar" @selectDate="selectDate"></events-calendar-item-component>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "CalendarComponent",
    props:['basicData'],
    data(){
        return{
            events:'',
            selectedEvent:'',
            selectedDate:'',
            months:['Enero', 'Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
            daysOfWeek:['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'],
            letterDaysOfWeek : [
                {
                    letterDay: 'Lunes',
                    iniDay: 'L'
                },
                {
                    letterDay: 'Martes',
                    iniDay: 'M'
                },
                {
                    letterDay: 'Miercoles',
                    iniDay: 'X'
                },
                {
                    letterDay: 'Jueves',
                    iniDay: 'J'
                },
                {
                    letterDay: 'Viernes',
                    iniDay: 'V'
                },
                {
                    letterDay: 'Sabado',
                    iniDay: 'S'
                },
                {
                    letterDay: 'Domingo',
                    iniDay: 'D'
                },
            ],
            selectedDay: '',
            datesDisplaying:'',
            displaySelected:'day',
            displayTypes:[
                {
                  code: 'day',
                  title: 'Día'
                },
                {
                    code: 'week',
                    title: 'Semana'
                },
                {
                    code: 'month',
                    title: 'Mes'
                },
                {
                    code: 'year',
                    title: 'Año'
                }
            ],
            bigCalendarCursor: moment(),
            bigCalendar: [],
            smallCalendarCursor: moment(),
            smallCalendar: [],
            today: moment().format(),
            isOpenenedSmallCalendar: false,
            client_id: '975102051088-5t4995mr9tvjltpv88qlke9n4jqinqfj.apps.googleusercontent.com',
            isSignedIn: false,
            loadedCharged: false,
            googleData: '',
            googleCalendarColors: {
                1: "#7986CB",  // Lavanda
                2: "#33B679",  // Salvia
                3: "#8E24AA",  // Uva
                4: "#E67C73",  // Flamenco
                5: "#F6BF26",  // Plátano
                6: "#F4511E",  // Mandarina
                7: "#039BE5",  // Pavo real
                8: "#616161",  // Grafito
                9: "#3F51B5",  // Arándano
                10: "#0B8043", // Basilisco
                11: "#D50000", // Tomate
                12: "#F09300", // Camaleón
                13: "#E4C441", // Flamenco claro
                14: "#B39DDB", // Basalto
            },
            googleCalendarUserStatuses: {
                "needsAction": { text: "Pendiente de respuesta", color: "#FFA500" }, // Naranja
                "declined": { text: "Rechazado", color: "#FF0000" }, // Rojo
                "tentative": { text: "Tentativo", color: "#FFFF00" }, // Amarillo
                "accepted": { text: "Aceptado", color: "#008000" }, // Verde
                "none": { text: "Sin respuesta", color: "#808080" } // Gris
            }
        }
    },
    mounted(){

        if (this.basicData.userLogged && !this.loadedCharged) {
            this.checkSignedInGoogle();
        }
    },
    watch:{
        'basicData.userLogged'(){
            this.checkSignedInGoogle();
        }
    },
    methods:{
        async checkSignedInGoogle(){
            await axios.post('/api/google/checkSignedIn', {user: this.basicData.userLogged})
                .then((res) => {
                    this.isSignedIn = res.data.isSignedIn;
                    this.loadedCharged = true;


                    //si esta sincronizado saco el token
                    if (this.isSignedIn)
                        this.refreshToken();
                    else //sino renderizo el calendario con los eventos locales
                        this.fetchAllEvents()
                })
                .catch(err => console.log(err))
        },
        signInGoogle() {
            if (typeof google !== 'undefined' && google.accounts && google.accounts.oauth2) {
                const client = google.accounts.oauth2.initCodeClient({
                    client_id: this.client_id,
                    scope: 'https://www.googleapis.com/auth/calendar',
                    ux_mode: 'popup',
                    callback: (response) => {
                        this.exchangeCodeForTokens(response.code);
                    },
                });
                client.requestCode();
            } else {
                console.error('Google OAuth2 no está disponible o el script no se cargó correctamente.');
            }
        },
        async exchangeCodeForTokens(code) {
            try {

                await axios.get('/api/google/getTokens', {
                    params: { code: code, user: this.basicData.userLogged }
                })
                    .then((res) => {
                        this.googleData = res.data.data

                        //Si tiene eventos en la bbdd los paso a Google drive y los elimino de la bbdd

                            // Obtener eventos locales que estén por delante de la fecha actual
                            const currentDate = new Date();
                            const futureEvents = this.events.filter(event => new Date(event.date.end) > currentDate);

                            //console.log('futureEvents --> ', futureEvents, this.events)

                            // Subir cada evento a Google Calendar
                            for (const event of futureEvents) {
                                //Subo el evento a google calendar
                                this.addEventToGoogleCalendar(event);

                                //lo elimino de la bbdd
                                this.deleteEventFromDB(event)
                            }

                        //Reseteo todo
                        this.events = '';
                        this.selectedEvent= '';

                        let timeRemaining = this.googleData.expires_in - 60;

                        //Establezco una cuenta atrás de un minuto menos del tiempo que va a expirar el token
                        setTimeout(() => this.refreshToken(), timeRemaining * 1000);

                        this.isSignedIn = true;



                        //Listo los eventos
                        this.fetchEvents()



                    })
                    .catch(err => console.log(err))

            } catch (error) {
                console.error('Error al intercambiar el código:', error);
            }
        },
        async refreshToken() {

            await axios.get('/api/google/getNewToken', {
                params: { user: this.basicData.userLogged }
            })
                .then((res) => {
                    this.googleData = res.data.data

                    let timeRemaining = this.googleData.expires_in - 60;

                    //Establezco una cuenta atrás de un minuto menos del tiempo que va a expirar el token
                    setTimeout(() => this.refreshToken(), timeRemaining * 1000);

                    //Listo los eventos
                    this.fetchEvents();
                })
                .catch(err => console.log(err))
        },
        async fetchEvents(){
            await axios.get('/api/google/listEvents', {
                params: { token: this.googleData.access_token }
            })
                .then((res) => {
                    this.googleDataGetted = res.data.events

                    this.events = res.data.events.items

                    //Renderizo el calendario pequeño
                    this.renderSmallCalendarGoogle()

                    //Renderizo el calendario grande
                    this.renderBigCalendarGoogle()

                })
                .catch(err => console.log(err))
        },
        renderSmallCalendarGoogle() {
            this.smallCalendar =  this.renderMonth(this.smallCalendarCursor.format());

            this.smallCalendar.titleData = this.loadTitleCalendar(this.smallCalendarCursor.format())
        },
        renderBigCalendarGoogle(){


            if (this.selectedDate)
                this.bigCalendarCursor = moment(this.selectedDate);

            this.selectedDate = ''



            switch (this.displaySelected){

                case 'day':

                    this.bigCalendar = {
                        day : []
                    };

                    this.bigCalendar.day = this.renderDay(this.bigCalendarCursor.format())


                    //Meto info de ayuda para mostrar eventos
                    this.bigCalendar.day.events.forEach((event) => {
                        let startDate = '';
                        let endDate = '';

                        startDate = !!event.start.dateTime ? event.start.dateTime : event.start.date
                        endDate = !!event.end.dateTime ? event.end.dateTime : event.end.date


                        //saco la hora de empezar
                        let hourStart = moment(startDate).format('HH') * 4;

                        let minutesStart = moment(startDate).format('mm')

                        //Los minutos los voy a acercar a los cuartos de hora pq el calendario de día esta dividido en cuartos de hora
                        if (minutesStart == 0){
                            minutesStart = 0
                        }else if(minutesStart > 0 && minutesStart <=15){
                            minutesStart = 1
                        }else if (minutesStart > 15 && minutesStart <= 30){
                            minutesStart = 2
                        }else if(minutesStart > 30 && minutesStart <= 45){
                            minutesStart = 3
                        }else{
                            minutesStart = 4
                        }

                        let rowStart = hourStart + minutesStart;


                        //Saco la fila en la que termina

                        let hourEnd = moment(endDate).format('HH') * 4;

                        let minutesEnd = moment(endDate).format('mm')

                        if (minutesEnd == 0){
                            minutesEnd = 0
                        }else if(minutesEnd > 0 && minutesEnd <=15){
                            minutesEnd = 1
                        }else if (minutesEnd > 15 && minutesEnd <= 30){
                            minutesEnd = 2
                        }else if(minutesEnd > 30 && minutesEnd <= 45){
                            minutesEnd = 3
                        }else{
                            minutesEnd = 4
                        }

                        let rowEnd = hourEnd + minutesEnd;

                        //Saco el número de eventos que hay ocupando el sitio q tiene q ocupar este
                        let eventsInTime = this.bigCalendar.day.events.reduce((accumulator, eventRed) => {

                            let startDateEventRed = '';
                            let endDateEventRed = '';

                            startDateEventRed = !!eventRed.start.dateTime ? eventRed.start.dateTime : eventRed.start.date
                            endDateEventRed = !!eventRed.end.dateTime ? eventRed.end.dateTime : eventRed.end.date


                            // Si son eventos de antes del evento actual
                            if (moment(startDate).isBetween(moment(startDateEventRed), moment(endDateEventRed)) && event.id !== eventRed.id) {

                                return accumulator + 1;
                            }

                            return accumulator;
                        }, 0);

                        //Le meto la informacion al evento
                        event.rowStart = rowStart;
                        event.rowEnd = rowEnd;
                        event.eventsInTime = eventsInTime;

                    })

                    break;

                case 'week':

                    this.bigCalendar = this.renderWeek(this.bigCalendarCursor.format())

                    //Le meto al calendario los días de la semana para poder ponerlos aparte
                    this.bigCalendar.daysHeader = [];
                    this.bigCalendar.days.forEach((day) => {

                        let dayNow = {
                            dayOfWeek: this.daysOfWeek[moment(day.day).day()],
                            dayNumber: day.dayNumber,
                            day
                        }

                        this.bigCalendar.daysHeader.push(dayNow)
                    })

                    //le meto la información a cada evento de cada día para que me ayude a mostrar los eventos
                    this.bigCalendar.days.forEach((day) => {

                        day.events.forEach((event) => {


                            let startDate = '';
                            let endDate = '';

                            startDate = !!event.start.dateTime ? event.start.dateTime : event.start.date
                            endDate = !!event.end.dateTime ? event.end.dateTime : event.end.date



                            //saco la hora de empezar
                            let hourStart = moment(startDate).format('HH') * 4;

                            let minutesStart = moment(startDate).format('mm')

                            //Los minutos los voy a acercar a los cuartos de hora pq el calendario de día esta dividido en cuartos de hora
                            if (minutesStart == 0){
                                minutesStart = 0
                            }else if(minutesStart > 0 && minutesStart <=15){
                                minutesStart = 1
                            }else if (minutesStart > 15 && minutesStart <= 30){
                                minutesStart = 2
                            }else if(minutesStart > 30 && minutesStart <= 45){
                                minutesStart = 3
                            }else{
                                minutesStart = 4
                            }

                            let rowStart = hourStart + minutesStart;


                            //Saco la fila en la que termina

                            let hourEnd = moment(endDate).format('HH') * 4;

                            let minutesEnd = moment(endDate).format('mm')

                            if (minutesEnd == 0){
                                minutesEnd = 0
                            }else if(minutesEnd > 0 && minutesEnd <=15){
                                minutesEnd = 1
                            }else if (minutesEnd > 15 && minutesEnd <= 30){
                                minutesEnd = 2
                            }else if(minutesEnd > 30 && minutesEnd <= 45){
                                minutesEnd = 3
                            }else{
                                minutesEnd = 4
                            }

                            let rowEnd = hourEnd + minutesEnd;

                            //Saco el número de eventos que hay ocupando el sitio q tiene q ocupar este
                            let eventsInTime = day.events.reduce((accumulator, eventRed) => {

                                let startDateEventRed = '';
                                let endDateEventRed = '';

                                startDateEventRed = !!eventRed.start.dateTime ? eventRed.start.dateTime : eventRed.start.date
                                endDateEventRed = !!eventRed.end.dateTime ? eventRed.end.dateTime : eventRed.end.date

                                // Si son eventos de antes del evento actual
                                if (moment(startDate).isBetween(moment(startDateEventRed), moment(endDateEventRed)) && event.id !== eventRed.id) {
                                    return accumulator + 1;
                                }

                                return accumulator;
                            }, 0);


                            //Le meto la informacion al evento
                            event.rowStart = rowStart;
                            event.rowEnd = rowEnd;
                            event.eventsInTime = eventsInTime;

                        })
                    })


                    //Calculo los días que tengo que poner en el header por bien que ocupen mas de un día o un día entero

                    //variable para guardar eventos de mas de un día
                    this.bigCalendar.eventsTop = [];

                    //Saco el principio y el final de la semana
                    let startOfWeek = this.bigCalendarCursor.clone().startOf('week').add(1, 'day');

                    let endOfweek = this.bigCalendarCursor.clone().endOf('week').add(1, 'day');

                    this.events.filter((event) => {

                        let startDate = '';
                        let endDate = '';

                        startDate = !!event.start.dateTime ? event.start.dateTime : event.start.date
                        endDate = !!event.end.dateTime ? event.end.dateTime : event.end.date

                        let dayEventStart = moment(startDate);
                        let dayEventEnd = moment(endDate);

                        //El evento esta entre la semana
                        //El comienzo de la semana puede estar entre el inicio y final del evento y el final tb
                        // Si evento esta dentro de la semana y dura el día entero o dura más de un día ( es distinto el ( día, mes o año ) de inicio y de final) (aqui)
                        if (( (dayEventStart.isBetween(startOfWeek, endOfweek) || dayEventEnd.isBetween(startOfWeek, endOfweek)) || (startOfWeek.isBetween(dayEventStart, dayEventEnd) || endOfweek.isBetween(dayEventStart, dayEventEnd)) ) && ( (dayEventStart.isSame(dayEventStart.clone().startOf('day')) && dayEventEnd.isSame(dayEventEnd.clone().endOf('day'))) || (!dayEventStart.isSame(dayEventEnd, 'day') || !dayEventStart.isSame(dayEventEnd, 'month') ||  !dayEventStart.isSame(dayEventEnd, 'year')) )){

                            //Saco en que columna empieza y termina para colocarlo con grid
                            let columnStart, columnEnd = '';

                            //Si el evento empieza antes que la semana se pondra desde el lunes
                            if (dayEventStart.isBefore(startOfWeek))
                                columnStart = 1
                            else{
                                columnStart = dayEventStart.day()

                                if (columnStart === 0) columnStart = 7
                            }


                            //Si el evento acaba dps de la semana lo pongo en el domingo el final
                            if (dayEventEnd.isAfter(endOfweek)){
                                columnEnd = 8
                            }else{
                                columnEnd = dayEventEnd.day()

                                if (columnEnd === 0) columnEnd = 8
                                else columnEnd++
                            }



                            let eventTop = {
                                event,
                                columnStart,
                                columnEnd,
                            }


                            this.bigCalendar.eventsTop.push(eventTop)
                        }

                    })


                    break;

                case 'month':

                    this.bigCalendar = this.renderMonth(this.bigCalendarCursor.format())


                    //Separo el mes en semanas
                    let separatedWeeks = []
                    let week = [];

                    this.bigCalendar.days.forEach((day, index) => {
                        week.push(day);

                        if ((index + 1) % 7 === 0) {
                            // Si ya hemos acumulado 7 días, añadir la semana a separatedWeeks
                            separatedWeeks.push(week);
                            // Reiniciar la variable week para la próxima semana
                            week = [];
                        }
                    });


                    //Para cada semana le meto los eventos
                    separatedWeeks.forEach((week, index) => {

                        //Saco el principio y el final de la semana
                        let startOfWeek = moment(week[0].day).clone().startOf('day')
                        let endOfWeek = moment(week[week.length - 1].day).clone().endOf('day')

                        //Organizo los días
                        let daysOrganized = week;

                        separatedWeeks[index] = {
                            days: daysOrganized,
                            events: []
                        };



                        //Compruebo los eventos que hay dentro de la semana
                        this.events.forEach((event) => {


                            let startDate = '';
                            let endDate = '';

                            startDate = !!event.start.dateTime ? event.start.dateTime : event.start.date
                            endDate = !!event.end.dateTime ? event.end.dateTime : event.end.date


                            //Saco el primer y ultimo momento del evento
                            let dayEventStart = moment(startDate);
                            let dayEventEnd = moment(endDate);




                            //Si el evento empieza antes y termina en la semana o despues de la semana
                            if( (dayEventStart.isBetween(startOfWeek, endOfWeek)) || startOfWeek.isBetween(dayEventStart, dayEventEnd)){

                                //Calculo las columnas del grid que va a ocupar
                                let columnStart = 0;
                                let columnEnd = 0;



                                //Si empieza antes de la semana
                                if (dayEventStart.isBefore(startOfWeek))
                                    columnStart = 1
                                else{
                                    columnStart = dayEventStart.day()

                                    if (columnStart === 0) columnStart = 7
                                }


                                //Si termina dps de la semana
                                if (dayEventEnd.isAfter(endOfWeek)){
                                    columnEnd = 8
                                }else{
                                    columnEnd = dayEventEnd.day()

                                    if (columnEnd === 0) columnEnd = 8
                                    else columnEnd++
                                }


                                let eventProcessed = {
                                    columnStart,
                                    columnEnd,
                                    event
                                }


                                separatedWeeks[index].events.push(eventProcessed)
                            }
                        })

                    })


                    this.bigCalendar = separatedWeeks;

                    break;

                case 'year':

                    this.bigCalendar = {
                        months : []
                    };

                    this.bigCalendar.months  = this.renderYear(this.bigCalendarCursor.format())

                    break;
            }

            this.bigCalendar.titleData = this.loadTitleCalendar(this.bigCalendarCursor.format())
        },
        changeDisplayType(){

            if (this.loadedCharged && !this.isSignedIn)
                this.renderBigCalendar()
            else if (this.loadedCharged && this.isSignedIn)
                this.renderBigCalendarGoogle()
        },
        // Método para añadir un evento a Google Calendar
        async addEventToGoogleCalendar(event) {

            await axios.post('/api/google/createEvent', {event: event, token: this.googleData.access_token})
                .then((res) => {
                    console.log(res)
                })
                .catch(err => console.log(err))
        },
        /*DELETETHISMETHOD(){

            console.log('brum brum');

            if (typeof google !== 'undefined' && google.accounts && google.accounts.oauth2) {
                const client = google.accounts.oauth2.initCodeClient({
                    client_id: 'YOUR_GOOGLE_CLIENT_ID',
                    scope: 'https://www.googleapis.com/auth/calendar',
                    ux_mode: 'popup',
                    callback: (response) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', code_receiver_uri, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        // Set custom header for CRSF
                        xhr.setRequestHeader('X-Requested-With', 'XmlHttpRequest');
                        xhr.onload = function() {
                            console.log('Auth code response: ' + xhr.responseText);
                        };
                        xhr.send('code=' + response.code);
                    },
                });



                client.requestCode(); // Asegúrate de que esto se esté ejecutando
            } else {
                console.error('Google OAuth2 no está disponible o el script no se cargó correctamente.');
            }
        },*/
        async fetchAllEvents(){

                await axios.get('/api/calendar')
                .then((res) => {
                    this.events = res.data.events

                    //Filtro los eventos para luego poder sacarlos filtrar por cuartos de hora para el día, etc.
                    this.filterEvents()
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        filterEvents(){

            //Ordeno los eventos por fecha de inicio
            this.events.sort((a, b) => {

                let dateA = moment(a.date.start);
                let dateB = moment(b.date.start);

                // Compara las fechas y devuelve la diferencia
                return dateA.diff(dateB);
            });

            //Renderizo el calendario pequeño
            this.renderSmallCalendar()

            //Renderizo el calendario grande
            this.renderBigCalendar()


        },
        renderBigCalendar(){

            if (this.selectedDate)
                this.bigCalendarCursor = moment(this.selectedDate);

            switch (this.displaySelected){

                case 'day':

                    this.bigCalendar = {
                        day : []
                    };

                    this.bigCalendar.day = this.renderDay(this.bigCalendarCursor.format())

                    //Meto info de ayuda para mostrar eventos
                    this.bigCalendar.day.events.forEach((event) => {

                        //saco la hora de empezar
                        let hourStart = moment(event.date.start).format('HH') * 4;

                        let minutesStart = moment(event.date.start).format('mm')

                        //Los minutos los voy a acercar a los cuartos de hora pq el calendario de día esta dividido en cuartos de hora
                        if (minutesStart == 0){
                            minutesStart = 0
                        }else if(minutesStart > 0 && minutesStart <=15){
                            minutesStart = 1
                        }else if (minutesStart > 15 && minutesStart <= 30){
                            minutesStart = 2
                        }else if(minutesStart > 30 && minutesStart <= 45){
                            minutesStart = 3
                        }else{
                            minutesStart = 4
                        }

                        let rowStart = hourStart + minutesStart;


                        //Saco la fila en la que termina

                        let hourEnd = moment(event.date.end).format('HH') * 4;

                        let minutesEnd = moment(event.date.end).format('mm')

                        if (minutesEnd == 0){
                            minutesEnd = 0
                        }else if(minutesEnd > 0 && minutesEnd <=15){
                            minutesEnd = 1
                        }else if (minutesEnd > 15 && minutesEnd <= 30){
                            minutesEnd = 2
                        }else if(minutesEnd > 30 && minutesEnd <= 45){
                            minutesEnd = 3
                        }else{
                            minutesEnd = 4
                        }

                        let rowEnd = hourEnd + minutesEnd;

                        //Saco el número de eventos que hay ocupando el sitio q tiene q ocupar este
                        let eventsInTime = this.bigCalendar.day.events.reduce((accumulator, eventRed) => {

                            // Si son eventos de antes del evento actual
                            if (moment(event.date.start).isBetween(moment(eventRed.date.start), moment(eventRed.date.end)) && event._id !== eventRed._id) {
                                return accumulator + 1;
                            }

                            return accumulator;
                        }, 0);


                        //Le meto la informacion al evento
                        event.rowStart = rowStart;
                        event.rowEnd = rowEnd;
                        event.eventsInTime = eventsInTime;

                    })

                    break;

                case 'week':

                    this.bigCalendar = this.renderWeek(this.bigCalendarCursor.format())

                    //Le meto al calendario los días de la semana para poder ponerlos aparte
                    this.bigCalendar.daysHeader = [];
                    this.bigCalendar.days.forEach((day) => {

                        let dayNow = {
                            dayOfWeek: this.daysOfWeek[moment(day.day).day()],
                            dayNumber: day.dayNumber,
                            day
                        }

                        this.bigCalendar.daysHeader.push(dayNow)
                    })

                    //le meto la información a cada evento de cada día para que me ayude a mostrar los eventos
                    this.bigCalendar.days.forEach((day) => {

                        day.events.forEach((event) => {

                            //saco la hora de empezar
                            let hourStart = moment(event.date.start).format('HH') * 4;

                            let minutesStart = moment(event.date.start).format('mm')

                            //Los minutos los voy a acercar a los cuartos de hora pq el calendario de día esta dividido en cuartos de hora
                            if (minutesStart == 0){
                                minutesStart = 0
                            }else if(minutesStart > 0 && minutesStart <=15){
                                minutesStart = 1
                            }else if (minutesStart > 15 && minutesStart <= 30){
                                minutesStart = 2
                            }else if(minutesStart > 30 && minutesStart <= 45){
                                minutesStart = 3
                            }else{
                                minutesStart = 4
                            }

                            let rowStart = hourStart + minutesStart;


                            //Saco la fila en la que termina

                            let hourEnd = moment(event.date.end).format('HH') * 4;

                            let minutesEnd = moment(event.date.end).format('mm')

                            if (minutesEnd == 0){
                                minutesEnd = 0
                            }else if(minutesEnd > 0 && minutesEnd <=15){
                                minutesEnd = 1
                            }else if (minutesEnd > 15 && minutesEnd <= 30){
                                minutesEnd = 2
                            }else if(minutesEnd > 30 && minutesEnd <= 45){
                                minutesEnd = 3
                            }else{
                                minutesEnd = 4
                            }

                            let rowEnd = hourEnd + minutesEnd;

                            //Saco el número de eventos que hay ocupando el sitio q tiene q ocupar este
                            let eventsInTime = day.events.reduce((accumulator, eventRed) => {

                                // Si son eventos de antes del evento actual
                                if (moment(event.date.start).isBetween(moment(eventRed.date.start), moment(eventRed.date.end)) && event._id !== eventRed._id) {
                                    return accumulator + 1;
                                }

                                return accumulator;
                            }, 0);


                            //Le meto la informacion al evento
                            event.rowStart = rowStart;
                            event.rowEnd = rowEnd;
                            event.eventsInTime = eventsInTime;

                        })
                    })


                    //Calculo los días que tengo que poner en el header por bien que ocupen mas de un día o un día entero

                        //variable para guardar eventos de mas de un día
                        this.bigCalendar.eventsTop = [];

                        //Saco el principio y el final de la semana
                        let startOfWeek = this.bigCalendarCursor.clone().startOf('week').add(1, 'day');

                        let endOfweek = this.bigCalendarCursor.clone().endOf('week').add(1, 'day');

                        this.events.filter((event) => {

                            let dayEventStart = moment(event.date.start);
                            let dayEventEnd = moment(event.date.end);

                            //El evento esta entre la semana
                            //El comienzo de la semana puede estar entre el inicio y final del evento y el final tb
                            // Si evento esta dentro de la semana y dura el día entero o dura más de un día ( es distinto el ( día, mes o año ) de inicio y de final) (aqui)
                            if (( (dayEventStart.isBetween(startOfWeek, endOfweek) || dayEventEnd.isBetween(startOfWeek, endOfweek)) || (startOfWeek.isBetween(dayEventStart, dayEventEnd) || endOfweek.isBetween(dayEventStart, dayEventEnd)) ) && ( (dayEventStart.isSame(dayEventStart.clone().startOf('day')) && dayEventEnd.isSame(dayEventEnd.clone().endOf('day'))) || (!dayEventStart.isSame(dayEventEnd, 'day') || !dayEventStart.isSame(dayEventEnd, 'month') ||  !dayEventStart.isSame(dayEventEnd, 'year')) )){

                                //Saco en que columna empieza y termina para colocarlo con grid
                                let columnStart, columnEnd = '';

                                //Si el evento empieza antes que la semana se pondra desde el lunes
                                if (dayEventStart.isBefore(startOfWeek))
                                    columnStart = 1
                                else{
                                    columnStart = dayEventStart.day()

                                    if (columnStart === 0) columnStart = 7
                                }


                                //Si el evento acaba dps de la semana lo pongo en el domingo el final
                                if (dayEventEnd.isAfter(endOfweek)){
                                    columnEnd = 8
                                }else{
                                    columnEnd = dayEventEnd.day()

                                    if (columnEnd === 0) columnEnd = 8
                                    else columnEnd++
                                }



                                let eventTop = {
                                    event,
                                    columnStart,
                                    columnEnd,
                                }


                                this.bigCalendar.eventsTop.push(eventTop)
                            }

                        })


                    break;

                case 'month':

                    this.bigCalendar = this.renderMonth(this.bigCalendarCursor.format())


                    //Separo el mes en semanas
                    let separatedWeeks = []
                    let week = [];

                    this.bigCalendar.days.forEach((day, index) => {
                        week.push(day);

                        if ((index + 1) % 7 === 0) {
                            // Si ya hemos acumulado 7 días, añadir la semana a separatedWeeks
                            separatedWeeks.push(week);
                            // Reiniciar la variable week para la próxima semana
                            week = [];
                        }
                    });


                    //Para cada semana le meto los eventos
                    separatedWeeks.forEach((week, index) => {

                        //Saco el principio y el final de la semana
                        let startOfWeek = moment(week[0].day).clone().startOf('day')
                        let endOfWeek = moment(week[week.length - 1].day).clone().endOf('day')

                        //Organizo los días
                        let daysOrganized = week;

                        separatedWeeks[index] = {
                            days: daysOrganized,
                            events: []
                        };



                        //Compruebo los eventos que hay dentro de la semana
                        this.events.forEach((event) => {

                            //Saco el primer y ultimo momento del evento
                            let dayEventStart = moment(event.date.start);
                            let dayEventEnd = moment(event.date.end);



                            //Si el evento empieza antes y termina en la semana o despues de la semana
                            if( (dayEventStart.isBetween(startOfWeek, endOfWeek)) || startOfWeek.isBetween(dayEventStart, dayEventEnd)){

                                    //Calculo las columnas del grid que va a ocupar
                                    let columnStart = 0;
                                    let columnEnd = 0;



                                    //Si empieza antes de la semana
                                    if (dayEventStart.isBefore(startOfWeek))
                                        columnStart = 1
                                    else{
                                        columnStart = dayEventStart.day()

                                        if (columnStart === 0) columnStart = 7
                                    }


                                    //Si termina dps de la semana
                                    if (dayEventEnd.isAfter(endOfWeek)){
                                        columnEnd = 8
                                    }else{
                                        columnEnd = dayEventEnd.day()

                                        if (columnEnd === 0) columnEnd = 8
                                        else columnEnd++
                                    }


                                let eventProcessed = {
                                    columnStart,
                                    columnEnd,
                                    event
                                }

                                separatedWeeks[index].events.push(eventProcessed)
                            }
                        })

                    })


                    this.bigCalendar = separatedWeeks;

                    break;

                case 'year':

                    this.bigCalendar = {
                        months : []
                    };

                    this.bigCalendar.months  = this.renderYear(this.bigCalendarCursor.format())

                    break;
            }

            this.bigCalendar.titleData = this.loadTitleCalendar(this.bigCalendarCursor.format())

            //console.log('this.bigCalendar.titleData --> ',this.bigCalendar.titleData)

        },
        renderSmallCalendar(){
            this.smallCalendar =  this.renderMonth(this.smallCalendarCursor.format());

            this.smallCalendar.titleData = this.loadTitleCalendar(this.smallCalendarCursor.format())
        },
        renderDay(date){
            let dateObject = moment(date);

            if (this.loadedCharged && !this.isSignedIn)//calendario normal
                return this.prepareDay(dateObject.clone(), false)
            else if (this.loadedCharged && this.isSignedIn)//calendario google
                return this.prepareDayGoogle(dateObject.clone(), false)

        },
        renderWeek(date){

            let calendar = {
                days: []
            }

            let dateObject = moment(date);

            let firstDay = dateObject.clone().startOf('week');
            let lastDay = dateObject.clone().endOf('week').add(1, 'day');

            let cursorDay = firstDay.add(1, 'day').clone();

            while (cursorDay.isSameOrBefore(lastDay, 'day')) {

                if (this.loadedCharged && !this.isSignedIn)//calendario normal
                    calendar.days.push(this.prepareDay(cursorDay.clone(), false))
                else if (this.loadedCharged && this.isSignedIn)//calendario google
                    calendar.days.push(this.prepareDayGoogle(cursorDay.clone(), false))



                cursorDay.add(1, 'day');
            }

            return calendar

        },
        renderMonth(date) {
            let calendar = {
                days: []
            }


            let dateObject = moment(date);

            let firstDay = dateObject.clone().startOf('month');
            let lastDay = dateObject.clone().endOf('month');

            let cursorDay = firstDay.clone();

            for (let day = firstDay.format('D'); day <= lastDay.format('D'); day++) {

                //si es calendario normal
                if (this.loadedCharged && !this.isSignedIn)
                    calendar.days.push(this.prepareDay(cursorDay.clone(), false));
                else if (this.loadedCharged && this.isSignedIn)//calendario google
                    calendar.days.push(this.prepareDayGoogle(cursorDay.clone(), false));


                cursorDay.add(1, 'day');
            }


            // -> días de antes
            let prevMonth = firstDay.clone().subtract(1, 'day');
            let dayOfWeekFirstDay = firstDay.day();

            if (dayOfWeekFirstDay == 0) dayOfWeekFirstDay = 6; else dayOfWeekFirstDay -= 1;

            for (let end = (dayOfWeekFirstDay - 1); end >= 0; end--) {
                //si es calendario normal
                if (this.loadedCharged && !this.isSignedIn)
                    calendar.days.unshift(this.prepareDay(prevMonth.clone(), true));
                else if (this.loadedCharged && this.isSignedIn)//calendario google
                    calendar.days.unshift(this.prepareDayGoogle(prevMonth.clone(), true));

                prevMonth.subtract(1, 'day');
            }


            // -> días de después
            let nextMonth = lastDay.clone().add(1, 'day');
            let nextMonthHelp = lastDay.clone().add(2, 'day');

            let dayOfWeekLastDay = lastDay.day();
            if (dayOfWeekLastDay == 0) dayOfWeekLastDay = 6; else dayOfWeekLastDay -= 1;


            for (let start = (dayOfWeekLastDay * 1 + 1); start <= 6; start++) {

                //si es calendario normal
                if (this.loadedCharged && !this.isSignedIn)
                    calendar.days.push(this.prepareDay(nextMonth.clone(), true));
                else if (this.loadedCharged && this.isSignedIn)//calendario google
                    calendar.days.push(this.prepareDayGoogle(nextMonth.clone(), true));


                nextMonth.add(1, 'day');
            }



            return calendar;
        },
        renderYear(date){

            let calendar = {};

            let dateObject = moment(date);

            let firstDay = dateObject.clone().startOf('year');
            let lastDay = dateObject.clone().endOf('year');

            let cursorDay = firstDay.clone();

            // Recorro cada uno de los días del año
            while (cursorDay.isSameOrBefore(lastDay, 'day')) {
                // Obtengo el mes
                let currentMonth = this.months[(cursorDay.format('MM') - 1)];

                // Si el mes no está añadido, lo añado al calendario
                if (!calendar[currentMonth]) {
                    calendar[currentMonth] = [];
                }

                // Añado el día al mes
                if (this.loadedCharged && !this.isSignedIn)
                    calendar[currentMonth].push(this.prepareDay(cursorDay.clone(), false));
                else if (this.loadedCharged && this.isSignedIn)//calendario google
                    calendar[currentMonth].push(this.prepareDayGoogle(cursorDay.clone(), false));


                // Sumo un mes
                cursorDay.add(1, 'day');
            }

            return calendar;
        },
        prepareDay(date, isOut) {


            let day = date.clone();

            let dayEvents = []

            //No me hace falta para el mes pq no los voy a coger de aqui
            if (this.displaySelected !== 'month'){
                dayEvents = this.events.filter(event => {
                    //Meto los que el día son el mismo día
                    return moment(event.date.start).isBetween(day.clone().startOf('day'), day.clone().endOf('day')) && moment(event.date.end).isBetween(day.clone().startOf('day'), day.clone().endOf('day'))
                    //return moment(event.date.start).isSame(day, 'day')
                });
            }

            let eventsAllDay = []

            eventsAllDay = this.events.filter(event => {
                //Meto los empiezan antes, terminan despues, y es de principio a final del dia
                return (moment(event.date.start).isBefore(day.clone().startOf('day')) && day.isBetween(moment(event.date.start), moment(event.date.end))) || (moment(event.date.end).isAfter(day.clone().endOf('day')) && day.isBetween(moment(event.date.start), moment(event.date.end))) || (moment(event.date.start).isSame(day.clone().startOf('day')) && moment(event.date.end).isSame(day.clone().endOf('day')))
                //return day.isBetween(moment(event.date.start), moment(event.date.end))
            });


            return {
                day,
                dayNumber: day.format('DD'),
                dayOfWeek: this.daysOfWeek[day.day()],
                hasEvents: dayEvents.length > 0 || eventsAllDay.length > 0,
                events: dayEvents,
                eventsAllDay: eventsAllDay,
                isOut
            };
        },
        prepareDayGoogle(date, isOut) {


            let day = date.clone();

            let dayEvents = []

            //No me hace falta para el mes pq no los voy a coger de aqui
            if (this.displaySelected !== 'month'){

                dayEvents = this.events.filter(event => {


                    let startDate = '';
                    let endDate = '';


                    /*if (!!event.start && event.start !== undefined && !!event.start.dateTime && event.start.dateTime !== undefined) {
                        console.log(event.start.dateTime)
                        startDate = event.start.dateTime
                    }

                    else if (!!event.start && event.start !== undefined && !!event.start.date && event.start.date !== undefined) {
                        console.log(event.start.date)
                        startDate = event.start.date
                    }

                    else {
                        console.log(event.originalStartTime.dateTime)
                        startDate = event.originalStartTime.dateTime
                    }



                    if (!!event.end && !!event.end.dateTime) {
                        console.log(event.end.dateTime)
                        endDate = event.end.dateTime
                    }

                    else if (!!event.end && event.end !== undefined && !!event.end.date && event.end.date !== undefined) {
                        console.log(event.end.date)
                        endDate = event.end.date
                    }

                    else {
                        console.log(event.originalStartTime.dateTime)
                        endDate = event.originalStartTime.dateTime
                    }*/


                    startDate = (!!event.start && !!event.start.dateTime) ? event.start.dateTime : ((!!event.start && !!event.start.date) ? event.start.date : event.originalStartTime.dateTime)
                    endDate = (!!event.end && !!event.end.dateTime) ? event.end.dateTime : ((!!event.end && !!event.end.date) ? event.end.date : event.originalStartTime.dateTime)

                    //Meto los que el día son el mismo día
                    return moment(startDate).isBetween(day.clone().startOf('day'), day.clone().endOf('day')) && moment(endDate).isBetween(day.clone().startOf('day'), day.clone().endOf('day'))
                    //return moment(event.date.start).isSame(day, 'day')
                });
            }


            let eventsAllDay = []

            eventsAllDay = this.events.filter(event => {

                let startDate = '';
                let endDate = '';

                startDate = (!!event.start && !!event.start.dateTime) ? event.start.dateTime : ((!!event.start && !!event.start.date) ? event.start.date : event.originalStartTime.dateTime)
                endDate = (!!event.end && !!event.end.dateTime) ? event.end.dateTime : ((!!event.end && !!event.end.date) ? event.end.date : event.originalStartTime.dateTime)

                //Meto los empiezan antes, terminan despues, y es de principio a final del dia
                return ((moment(startDate).isSameOrBefore(day.clone().startOf('day')) && day.isBetween(moment(startDate), moment(endDate))) || (moment(endDate).isAfter(day.clone().endOf('day')) && day.isBetween(moment(startDate), moment(endDate))) || (moment(startDate).isSame(day.clone().startOf('day')) && moment(endDate).isSame(day.clone().endOf('day'))) || (moment(startDate).isSame(day.clone().startOf('day')) && moment(endDate).isSame(day.clone().add(1, 'day').startOf('day'))) )
                //return day.isBetween(moment(event.date.start), moment(event.date.end))
            });

            return {
                day,
                dayNumber: day.format('DD'),
                dayOfWeek: this.daysOfWeek[day.day()],
                hasEvents: dayEvents.length > 0 || eventsAllDay.length > 0,
                events: dayEvents,
                eventsAllDay: eventsAllDay,
                isOut
            };
        },
        loadTitleCalendar(date) {


            //console.log('title --> ', date)

            let dateObject = moment(date);

            let titleData = {
                monthName: this.months[dateObject.format('M') * 1 - 1],
                year: dateObject.format('YYYY')
            }

            //console.log('titleData --> ', titleData)

            return titleData
        },
        selectDate(date){

            this.selectedDate = moment(date).format()

            //Pongo el calendario para que se vea un día solo
            if (this.displaySelected = 'year ') this.displaySelected = 'day';

            //Renderizo el calendario
            if (this.loadedCharged && !this.isSignedIn)
                this.renderBigCalendar()
            else if (this.loadedCharged && this.isSignedIn)
                this.renderBigCalendarGoogle()

            //Cierro la vetana por si es movil
            this.isOpenenedSmallCalendar = false;
        },
        changeDate(value){
            /*console.log(this.displaySelected)

            console.log(this.bigCalendar)

            console.log('this.selectedDate, ', this.selectedDate)*/

            let date = !this.selectedDate ? moment() : this.selectedDate.clone()

            switch(this.displaySelected) {
                case 'day':

                    if (value === 1) {
                        this.selectedDate = date.add(1, 'day')
                    }else{
                        this.selectedDate = date.subtract(1, 'day')
                    }

                    break;

                case 'week':

                    if (value === 1) {
                        this.selectedDate = date.add(1, 'week')
                    }else{
                        this.selectedDate = date.subtract(1, 'week')
                    }

                    break;
            }



            //Renderizo el calendario
            if (this.loadedCharged && !this.isSignedIn)
                this.renderBigCalendar()
            else if (this.loadedCharged && this.isSignedIn)
                this.renderBigCalendarGoogle()

            //Cierro la vetana por si es movil
            this.isOpenenedSmallCalendar = false;
        },
        changeMonthSmallCalendar(value){
            //Cambio el mes
            this.smallCalendarCursor.add(value, 'month');

            //Renderizo el calendario
            this.renderSmallCalendar();
        },
        compareMomentDates(date1, date2){

            if (typeof date1 === 'string')
                date1 = moment(date1).startOf('day');
            else
                date1 = date1.startOf('day');

            if (typeof date2 === 'string')
                date2 = moment(date2).startOf('day');
            else
                date2 = date2.startOf('day');


            return date1.isSame(date2)
        },
        changeBigCalendar(type ,value){

            this.bigCalendarCursor.add(value, type);

            //Renderizo el calendario
            if (this.loadedCharged && !this.isSignedIn)
                this.renderBigCalendar()
            else if (this.loadedCharged && this.isSignedIn)
                this.renderBigCalendarGoogle()
        },
        getHourFormated(date){

            let hour = moment(date).format('HH')

            let minutes = moment(date).format('mm')

            if (minutes === 0){
                minutes = '00'
            }else if(minutes > 0 && minutes <=15){
                minutes = '15'
            }else if (minutes > 15 && minutes <= 30){
                minutes = '30'
            }else if(minutes > 30 && minutes <= 45){
                minutes = '45'
            }else{
                minutes = '00'
            }

            return hour + ':' + minutes
        },
        getEventDateInfo(date){

            let dateFormatted = '';

            let dateStart = moment(date.start);

            let dateEnd = moment(date.end);

            //Si es distinto año
            if (!dateStart.isSame(dateEnd, 'year')){
                return dateStart.format('DD') + ' de ' + this.months[dateStart.format('MM') < 10 ? dateEnd.format('MM')[1] : dateEnd.format('MM')] + ' de ' + dateStart.format('YY') + ' - ' + dateEnd.format('DD') + ' de ' + this.months[dateEnd.format('MM') < 10 ? dateEnd.format('MM')[1] : dateEnd.format('MM')] + ' de ' + dateEnd.format('YY') + ' ' + dateStart.format('HH:mm') + ' - ' + dateEnd.format('HH:mm')
            }else if(!dateStart.isSame(dateEnd, 'month')){//si es distinto día
                return dateStart.format('DD') + ' de ' + this.months[dateStart.format('MM') < 10 ? dateEnd.format('MM')[1] : dateEnd.format('MM')] + ' - ' + dateEnd.format('DD') + ' de ' + this.months[dateEnd.format('MM') < 10 ? dateEnd.format('MM')[1] : dateEnd.format('MM')] + ' de ' + dateStart.format('YY') + ' ' + dateStart.format('HH:mm') + ' - ' + dateEnd.format('HH:mm')
            }else{//Distinto día
                return dateStart.format('DD') + ' - ' + dateEnd.format('DD') + ' de ' + this.months[dateEnd.format('MM') < 10 ? dateEnd.format('MM')[1] : dateEnd.format('MM')] + ' de ' + dateStart.format('YY') + ' ' + dateStart.format('HH:mm') + ' - ' + dateEnd.format('HH:mm')
            }
        },
        getEventDateInfoGoogle(startDate, endDate){

            let dateFormatted = '';

            let dateStart = moment(startDate);

            let dateEnd = moment(endDate);

            //Si es distinto año
            if (!dateStart.isSame(dateEnd, 'year')){
                return dateStart.format('DD') + ' de ' + this.months[dateStart.format('MM') < 10 ? dateEnd.format('MM')[1] : dateEnd.format('MM')] + ' de ' + dateStart.format('YY') + ' - ' + dateEnd.format('DD') + ' de ' + this.months[dateEnd.format('MM') < 10 ? dateEnd.format('MM')[1] : dateEnd.format('MM')] + ' de ' + dateEnd.format('YY') + ' ' + dateStart.format('HH:mm') + ' - ' + dateEnd.format('HH:mm')
            }else if(!dateStart.isSame(dateEnd, 'month')){//si es distinto día
                return dateStart.format('DD') + ' de ' + this.months[dateStart.format('MM') < 10 ? dateEnd.format('MM')[1] : dateEnd.format('MM')] + ' - ' + dateEnd.format('DD') + ' de ' + this.months[dateEnd.format('MM') < 10 ? dateEnd.format('MM')[1] : dateEnd.format('MM')] + ' de ' + dateStart.format('YY') + ' ' + dateStart.format('HH:mm') + ' - ' + dateEnd.format('HH:mm')
            }else{//Distinto día
                return dateStart.format('DD') + ' - ' + dateEnd.format('DD') + ' de ' + this.months[dateEnd.format('MM') < 10 ? dateEnd.format('MM')[1] : dateEnd.format('MM')] + ' de ' + dateStart.format('YY') + ' ' + dateStart.format('HH:mm') + ' - ' + dateEnd.format('HH:mm')
            }
        },
        toggleSelectEvent(event){
            if (this.selectedEvent._id && this.selectedEvent._id === event._id){
                this.selectedEvent = '';
            }else{
                this.selectedEvent = event;
            }
        },
        getAxeHours(hour){
            return (hour < 10 ? ('0' + hour) : hour) + ':00';
        },
        deleteEvent(event){
            Swal.fire({
                icon:'warning',
                title: 'Estas seguro?',
                text: 'Si borras el evento no lo podrás recuperar',
                confirmButtonText: 'Sí',
                showConfirmButton: true,
                cancelButtonText: 'No',
                showCancelButton: true
            }).then((res) => {

                if (res.isConfirmed){

                    if (this.loadedCharged && !this.isSignedIn) {
                        axios.delete(`/api/calendar/${event._id}`)
                            .then((res) => {
                                //Borro el evento en cliente
                                this.events = this.events.filter(eventNow => eventNow._id !== event._id)

                                if (event._id === this.selectedEvent._id)
                                    this.selectedEvent = '';

                                //Renderizo el calendario
                                this.renderBigCalendar()
                            })
                            .catch((err) => {
                                console.log(err)
                            })
                    } else {
                        axios.delete(`/api/google/deleteEvent/${event.id}`, {
                            params: { token: this.googleData.access_token }
                        })
                            .then((res) => {
                                //Borro el evento en cliente
                                this.events = '';

                                this.fetchEvents();

                                //Renderizo el calendario
                                this.renderBigCalendarGoogle()
                            })
                            .catch((err) => {
                                console.log(err)
                            })
                    }

                }
            })
        },
        deleteEventFromDB(event){
            axios.delete(`/api/calendar/${event._id}`)
                .then((res) => {
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        getIniOfDay(dayLetter){

            let day = this.letterDaysOfWeek.find((day) => {
                return day.letterDay === dayLetter
            })

            return day.iniDay;
        },
        actionLink(route){
            this.$router.push(route)
        },
    },
    computed:{
        isReadOnly(){
            if (!this.basicData.userLogged)
                return true
            else (this.basicData.userLogged)
                return this.basicData.userLogged.permissions.includes('READONLY')
        }
    }
}
</script>

<style scoped>

</style>
