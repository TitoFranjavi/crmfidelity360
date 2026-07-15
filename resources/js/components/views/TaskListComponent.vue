<template>
    <div>

        <!--Estilo movil-->
        <div class="mobile-item">

            <div class="content-white">


                <div class="sticky-header-mobile">
                    <!--Título-->
                    <div class="d-flex justify-between">

                        <div class="text my-10" data-size="22" data-weight="700">Tareas</div>

                        <div class="custom-button my-auto mobile-create-btn" data-size="medium" data-bg="principal" data-weight="600" v-on:click="actionLink('/tasks/register')"><i class="fas fa-plus"></i></div>
                    </div>

                    <div class="search-bar">

                        <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                        <input type="text" data-size="14" placeholder="Buscar una tarea..." v-model="searchTaskText">
                    </div>
                </div>

                <!--Filtros-->
                <div class="d-flex column my-20">

                    <p class="text" data-size="13" data-weight="600" v-on:click="isSeeingFilters = !isSeeingFilters">{{ isSeeingFilters ? 'Ocultar filtros' : 'Mostrar filtros' }}</p>


                    <div v-if="isSeeingFilters">

                        <div class="arrow-border arrow-top my-10" data-position="left"></div>

                        <!--Calendario-->
                        <calendar-item-component v-if="tasks" :selectedDate="selectedDate" :tasks="tasks" @selectDate="selectDate"></calendar-item-component>

                        <div class="separator mt-10"></div>
                    </div>
                </div>

                <!--Cargando-->
                <div class="loading-indicator" v-if="!tasks">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p class="text" data-size="14">Cargando tareas...</p>
                </div>

                <!--Listado de tareas-->
                <div class="d-flex column">


                    <!--Cada día-->
                    <div v-for="(dayTask, dayKey) in filteredTasks" class="my-20">


                        <!--Header día-->
                        <div class="d-flex">

                            <i class="fa-light fa-calendar text my-auto"></i>

                            <p class="text my-auto ml-10" data-size="16" data-weight="600">{{ dayKey }}</p>
                        </div>


                        <!--Cada una de las tareas del día-->
                        <div v-for="(task, taskKey) in dayTask" class="w-100 my-15 mx-5" v-bind:class="{completed: task.isCompleted}">

                            <!--Card-->
                            <div class="d-flex justify-between pointer" v-on:dblclick="actionLink('/tasks/'+ task._id)" v-on:click="seeTaskInfo(task)">

                                <!--Asunto y importancia-->
                                <div class="d-flex">

                                    <p><i class="fas fa-circle" :data-color="priorityColor(task.priority)" data-size="10"></i></p>

                                    <div class="text ellipsis ml-10" v-bind:class="{'opacity-5': task.isCompleted}" data-weight="600">{{ task.subject }}</div>
                                </div>

                                <div class="d-flex">
                                    <p v-if="!task.isCompleted && task.subTasks.length > 0" class="text ml-10 my-auto opacity-5" data-size="10" data-weight="500">{{ getTaskProgress(task).toFixed(0) }}%</p>
                                    <p v-else class="text ml-10 my-auto opacity-5" data-size="10" data-weight="500">{{ task.isCompleted ? '100' : '0' }}%</p>

                                    <div class="deploy-btn ml-10" data-round="15" v-bind:class="{'selected': selectedTask._id === task._id}">
                                        <i class="fa-solid" v-bind:class="{'fa-chevron-down': selectedTask._id !== task._id, 'fa-chevron-up': selectedTask._id === task._id}"></i>
                                    </div>
                                </div>


                            </div>

                            <!--Info card-->
                            <div class="d-flex column mt-5" v-if="selectedTask._id === task._id">

                                <!--Info básica-->
                                <div class="d-flex my-5">

                                    <!--Línea vertical-->
                                    <div class="separator h-100 m-0" data-position="vertical"></div>


                                    <div class="ml-10">

                                        <!--Descripción-->
                                        <div class="text opacity-6 " data-size="14">{{ task.desc }}</div>


                                        <!--Subtareas ( si tiene )-->
                                        <div class="my-20" v-if="task.subTasks.length > 0">
                                            <p class="text" data-size="15">Subtareas</p>


                                            <div v-for="(subtask, subTaskInd) in task.subTasks" class="d-flex my-10">

                                                <div class="custom-checkbox" data-style="circle" v-on:click="toggleSubTasktCompleted(selectedTask, subtask, subTaskInd)">
                                                    <div v-bind:class="{selected: subtask.isCompleted}"></div>
                                                </div>

                                                <div class="text subTask ml-5" data-size="14" v-bind:class="{completed: subtask.isCompleted, 'opacity-5': subtask.isCompleted}">
                                                    <span v-bind:class="{'line-through': subtask.isCompleted}">{{ subtask.subject }}</span>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                </div>

                                <!--Botones-->
                                <div class="d-flex column" data-gap="8">

                                    <div v-on:click="actionLink('/tasks/'+ selectedTask._id)" class="custom-button w-100" data-bg="principal" data-mode="outline" data-align="center" data-size="small" data-weight="700"><i class="fas fa-gear mr-5"></i> Editar tarea</div>

                                    <div v-on:click="toggleTaskCompleted(selectedTask)" class="custom-button w-100" data-bg="principal" data-mode="outline" data-align="center" data-size="small" data-weight="700"><i class="fas mr-5" v-bind:class="{'fa-toggle-off': !selectedTask.isCompleted, 'fa-toggle-on' : selectedTask.isCompleted}"></i> {{ selectedTask.isCompleted ? 'Descompletar' : 'Completar' }}</div>

                                    <div v-on:click="deleteTask(selectedTask)" class="custom-button w-100" data-bg="rojo" data-mode="outline" data-align="center" data-size="small" data-weight="700"><i class="fas fa-trash mr-5"></i> Eliminar</div>
                                </div>

                            </div>

                            <div v-if="taskKey < dayTask.length + 1" class="separator my-5"></div>
                        </div>




                    </div>
                </div>
            </div>
        </div>




        <!--Estilo pc-->
        <div class="desktop-item d-flex">

            <div class="content-white contact-selected mr-10">

                <!--Header-->
                <div class="d-flex justify-between align-center">

                    <!--Tareas pendientes-->
                    <p class="text" data-size="25" data-weight="700">¡Tienes {{ totalTaskWithoutComplete }} tareas pendientes!</p>

                    <!--Botones-->
                    <div class="d-flex">
                        <div class="custom-button" data-size="regular" data-bg="principal" v-if="!isReadOnly" v-on:click="actionLink('/tasks/register')">Añadir tarea</div>
                    </div>
                </div>


                <!--Saco cada una de las tareas organizadas por su día y ordenadas por fecha-->
                <div class="tasks mt-20">

                    <!--Cada tarea-->
                    <div v-for="(dayTasks, dayKey) in filteredTasks">

                        <!--Header día-->
                        <div>
                            <p class="text" data-size="18" data-weight="700">{{ dayKey }}</p>

                            <div class="separator mt-0 mb-10"></div>
                        </div>


                        <!--Cada una de las tareas del día-->
                        <div v-for="task in dayTasks" class="d-flex column" v-bind:class="{completed: task.isCompleted}">

                            <div class="task">

                                <!--Bolitas de tareas-->
                                <div class="balls">

                                    <!--Para la tarea principal-->
                                    <div class="text fas" data-size="5" v-bind:class="{'fa-circle': !task.isCompleted, 'fa-check':  task.isCompleted}"></div>

                                    <!--Para cada una de las subtareas-->
                                    <div v-if="!task.isCompleted" v-for="ball in task.subTasks" class="text fas" data-size="5" v-bind:class="{'fa-circle': !ball.isCompleted, 'fa-check':  ball.isCompleted}"></div>
                                </div>

                                <!--Contenido tarea-->
                                <div class="task-content" v-on:click="toggleSelectTask(task)" v-bind:class="{'selected': task._id === selectedTask._id}">

                                    <!--Asunto y hora de tarea-->
                                    <div class="d-flex justify-between">

                                        <div class="text " data-size="18" data-weight="600">
                                            <p><i class="fas fa-circle text" :data-color="priorityColor(task.priority)" data-size="13" v-if="!task.isCompleted"></i> <span class="capitalize ml-10">{{ task.subject }}</span></p>
                                        </div>

                                        <p class="text my-auto" data-size="12">{{ getTaskTime(task.finalDate) }}</p>
                                    </div>

                                    <!--Descripción ( si tiene )-->
                                    <p class="text opacity-5" data-size="12" v-if="!task.isCompleted && task.desc">{{ task.desc }}</p>


                                    <!--Subtareas ( si tiene )-->
                                    <div v-if="!task.isCompleted && task.subTasks.length > 0" class="my-5">

                                        <!--Cada una de las subtareas-->
                                        <div v-for="subTask in task.subTasks.slice(0,3)" class="subTask" v-bind:class="{completed: subTask.isCompleted}">
                                            <p class="text" data-size="10" v-bind:class="{'opacity-5': subTask.isCompleted}">- <span v-bind:class="{'line-through': subTask.isCompleted}">{{ subTask.subject }}</span></p>
                                        </div>


                                        <!--Si hay más de 3 subtareas-->
                                        <p class="text opacity-4 mt-5" data-size="8" v-if="task.subTasks.length > 3"> +{{ task.subTasks.length - 3 }} subtareas más</p>

                                        <!--Barra progreso-->
                                        <div class="d-flex">
                                            <div class="progress-bar my-auto">
                                                <div :class="'prog-' + getTaskProgress(task).toFixed(0)"></div>
                                            </div>
                                            <p class="text ml-10 my-auto" data-size="10" data-weight="500">{{ getTaskProgress(task).toFixed(0) }}%</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Vista para pantalla mediana -->
                            <div class="show-middle" v-if="selectedTask._id === task._id">

                                <!-- Subtareas -->
                                <div v-if="selectedTask.subTasks.length > 0" class="my-40">

                                    <p class="text mb-5" data-size="10" data-weight="600">Subtareas</p>

                                    <!--Cada una de las subtareas-->
                                    <div v-for="(subTask, subTaskInd) in selectedTask.subTasks" class="d-flex my-10">

                                        <!--Checkbox-->
                                        <div class="custom-checkbox" data-style="circle" v-on:click=" !isReadOnly ? toggleSubTasktCompleted(selectedTask, subTask, subTaskInd) : ''">
                                            <div v-bind:class="{selected: subTask.isCompleted}"></div>
                                        </div>

                                        <!--Asunto y descripción-->
                                        <div class="ml-10">
                                            <p class="text mt--3" data-size="10">{{ subTask.subject }}</p>

                                            <p class="text opacity-7" data-size="8">{{ subTask.desc }}</p>
                                        </div>
                                    </div>


                                </div>

                                <!-- Botones -->
                                <div class="mt-auto d-flex column" style="width:79vw">

                                <div class="d-flex justify-between mt-20 w-100">

                                    <!--Usuario creador-->
                                    <div class="d-flex my-5 opacity-5" v-if="selectedTask.createdBy">
                                        <i class="fas fa-user text mr-10"></i>

                                            <p class="text" data-size="10">{{ selectedTask.createdBy.firstName }} {{ selectedTask.createdBy.lastName }}</p>
                                        </div>

                                        <div class="d-flex">

                                            <!--Configurar tarea-->
                                            <div v-on:click="actionLink('/tasks/'+ selectedTask._id)" class="custom-button mr-10" data-size="medium" data-bg="azul" data-mode="outline" data-color="azul">Editar</div>


                                            <!--Alternar marcar y desmarcar todos-->
                                            <div v-on:click="toggleTaskCompleted(selectedTask)" v-if="!isReadOnly" class="custom-button d-flex align-center" data-size="small" data-bg="principal" data-mode="outline" data-color="principal">
                                                <i class="fas " data-size="16" v-bind:class="{'fa-toggle-off': !selectedTask.isCompleted, 'fa-toggle-on' : selectedTask.isCompleted}"></i>
                                            </div>

                                            <!--Eliminar tarea-->
                                            <div v-on:click="deleteTask(selectedTask)" v-if="!isReadOnly" class="custom-button" data-size="medium" data-bg="rojo" data-mode="outline" data-color="rojo"><i class="fas fa-trash"></i></div>

                                        </div>

                                        </div>
                                    </div>

                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <!--Info lateral-->
            <div class="info-content">


                <!--info tarea-->
                <div class="top-box">

                    <!--si hay alguno seleccionado-->
                    <div class="h-100 d-flex column justify-between" v-if="selectedTask">

                        <!--contenido-->
                        <div>
                            <!--Asunto tarea-->
                            <div class="text d-flex" data-size="18" data-weight="600">

                                <p><i class="fas fa-circle" :data-color="priorityColor(selectedTask.priority)" data-size="13"></i></p>

                                <div class="capitalize ml-10">{{ selectedTask.subject }}</div>
                            </div>


                            <!--Descripción-->
                            <p class="text opacity-5 my-10" data-size="12">{{ selectedTask.desc }}</p>


                            <!--Subtareas-->
                            <div v-if="selectedTask.subTasks.length > 0" class="my-40">

                                <p class="text mb-5" data-size="10" data-weight="600">Subtareas</p>

                                <!--Cada una de las subtareas-->
                                <div v-for="(subTask, subTaskInd) in selectedTask.subTasks" class="d-flex my-10">

                                    <!--Checkbox-->
                                    <div class="custom-checkbox" data-style="circle" v-on:click=" !isReadOnly ? toggleSubTasktCompleted(selectedTask, subTask, subTaskInd) : ''">
                                        <div v-bind:class="{selected: subTask.isCompleted}"></div>
                                    </div>

                                    <!--Asunto y descripción-->
                                    <div class="ml-10">
                                        <p class="text mt--3" data-size="10">{{ subTask.subject }}</p>

                                        <p class="text opacity-7" data-size="8">{{ subTask.desc }}</p>
                                    </div>
                                </div>

                            </div>
                        </div>


                        <!--Botones-->
                        <div class="mt-auto d-flex column">

                            <!--Usuario creador-->
                            <div class="d-flex my-5 opacity-5" v-if="selectedTask.createdBy">
                                <i class="fas fa-user text mr-10"></i>

                                <p class="text" data-size="10">{{ selectedTask.createdBy.firstName }} {{ selectedTask.createdBy.lastName }}</p>
                            </div>

                            <div class="d-flex justify-between mt-20">

                                <div class="d-flex">
                                    <!--Configurar tarea-->
                                    <div v-on:click="actionLink('/tasks/'+ selectedTask._id)" class="custom-button mr-10" data-size="medium" data-bg="azul" data-mode="outline" data-color="azul">Editar</div>


                                    <!--Alternar marcar y desmarcar todos-->
                                    <div v-on:click="toggleTaskCompleted(selectedTask)" v-if="!isReadOnly" class="custom-button d-flex align-center" data-size="small" data-bg="principal" data-mode="outline" data-color="principal">
                                        <i class="fas mr-5" data-size="16" v-bind:class="{'fa-toggle-off': !selectedTask.isCompleted, 'fa-toggle-on' : selectedTask.isCompleted}"></i>
                                        Completada
                                    </div>

                                </div>


                                <!--Eliminar tarea-->
                                <div v-on:click="deleteTask(selectedTask)" v-if="!isReadOnly" class="custom-button" data-size="medium" data-bg="rojo" data-mode="outline" data-color="rojo"><i class="fas fa-trash"></i></div>
                            </div>
                        </div>
                    </div>


                    <!--si no se ha seleccionado ninguno-->
                    <div class="h-100 d-flex column justify-between" v-else>

                        <!--contenido-->
                        <div>
                            <!--Asunto tarea-->
                            <div class="text loading w-35 h-25" data-size="18" data-weight="600"></div>


                            <!--Descripción-->
                            <p class="text opacity-5 my-10 loading w-90 h-25" data-size="12"></p>


                            <!--Subtareas-->
                            <div class="my-40">

                                <p class="text mb-5 loading w-35 h-25 mb-15" data-size="10" data-weight="600"></p>

                                <!--Pongo tres subtareas-->
                                <div v-for="task in 3" class="d-flex my-10 w-100">

                                    <!--Asunto y descripción-->
                                    <div class="ml-10 w-100">
                                        <p class="text mt--3 loading w-35 h-15 mb-5" data-size="10"></p>

                                        <p class="text opacity-7 loading w-50 h-15" data-size="8"></p>
                                    </div>
                                </div>

                            </div>
                        </div>


                        <!--Botones-->
                        <div class="mt-auto d-flex justify-between">


                            <div class="d-flex w-50">
                                <!--Configurar tarea-->
                                <div  class="loading w-60 h-30"></div>


                                <!--Alternar marcar y desmarcar todos-->
                                <div class="loading w-20 h-30 ml-10"></div>
                            </div>

                            <!--Eliminar tarea-->
                            <div class="loading w-10 h-30" ></div>

                        </div>
                    </div>

                </div>


                <div class="separator"></div>

                <!--calendario-->
                <div class="bottom-box">
                    <calendar-item-component v-if="tasks" :selectedDate="selectedDate" :tasks="tasks" @selectDate="selectDate"></calendar-item-component>
                </div>
            </div>
        </div>

    </div>
</template>

<script>

export default {
    name: "TaskListComponent",
    props:['basicData'],
    data(){
        return{
            isSeeingFilters: false,
            tasks: '',
            selectedTask:'',
            selectedDate: '',
            searchTaskText: '',
            months:['Enero', 'Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
        }
    },
    mounted() {
            this.fetchAllTasks()
    },
    watch:{
        "basicData.userLogged"(){
            this.fetchAllTasks()
        }
    },
    methods:{
        async fetchAllTasks(){

            await axios.get(`/api/tasks/index/${this.basicData.userLogged._id}`)
                .then((res) => {
                    this.tasks = res.data.tasks

                })
                .catch((err) => {
                    console.log(err);
                })
        },
        priorityColor(priority){

            let color = '';

            switch(priority){
                case 'a':
                    color = 'rojo'
                    break;

                case 'm':
                    color = 'amarillo'
                    break;

                default:
                    color = 'verde'
                    break;
            }

            return color;
        },
        getTaskTime(finalDate){

            let prettyFinalDate = new Date(finalDate)

            let hour = prettyFinalDate.getHours();

            let minutes = prettyFinalDate.getMinutes();

            if (hour < 10)
                hour = '0' + hour;

            if (minutes < 10)
                minutes = '0' + minutes;


            return hour + ':' + minutes
        },
        getTaskProgress(task){

            if (task.isCompleted){
                return 100
            }else{

                let completedTaskNum = 0;

                task.subTasks.forEach((subTask) => {
                    if (subTask.isCompleted) completedTaskNum++
                })

                return ((completedTaskNum / task.subTasks.length) * 100)
            }
        },
        toggleSubTasktCompleted(task,subTask, subTaskInd){

            //Marco la tarea como lo contrario de lo que esta ( marcada -> desmarcar, desmarcada -> marcada)
            subTask.isCompleted = !subTask.isCompleted;


            let allCompleted = true;

            //Compruebo si estan todas las subtareas completas para completar la tarea general
            task.subTasks.forEach((subTask) => {
                if (!subTask.isCompleted) allCompleted = false;
            })

            //Pongo la tarea general como completa o no completa
            task.isCompleted = allCompleted;

            //Lo cambio en la base de datos
            axios.put(`/api/tasks/toggleSubTask`, { task: task, subTask: subTask, subTaskInd: subTaskInd })
                .then((res) => {
                    //console.log(res)
                })
                .catch((err) => {
                    console.log(err)
                })

        },
        toggleTaskCompleted(task){

            //Compruebo si esta completa o no la tarea

            task.isCompleted = !task.isCompleted;

            let isCompleted = task.isCompleted;

            //Recorro cada una de las subtareas y las marco como completas
            task.subTasks.forEach((subTask) => {
                subTask.isCompleted = isCompleted;
            })


            //Lo cambio en la bbdd
            axios.put(`/api/tasks/toggleTask`, { task: task })
                .then((res) => {
                    //console.log(res)
                })
                .catch((err) => {
                    console.log(err)
                })

        },
        toggleSelectTask(task){

            if (this.selectedTask._id && this.selectedTask._id === task._id){
                this.selectedTask = '';
            }else{
                this.selectedTask = task;
            }
        },
        deleteTask(task){

            Swal.fire({
                icon:'warning',
                title: 'Estas seguro?',
                text: 'Si borras la tarea no la podrás recuperar',
                confirmButtonText: 'Sí',
                showConfirmButton: true,
                cancelButtonText: 'No',
                showCancelButton: true
            }).then((res) => {

                if (res.isConfirmed){
                    axios.delete(`/api/tasks/${task._id}`)
                        .then((res) => {
                            //Borro la tarea en cliente
                            this.tasks = this.tasks.filter(taskNow => taskNow._id !== task._id)
                        })
                        .catch((err) => {
                            console.log(err)
                        })
                }
            })
        },
        selectDate(day){

            let dayDate = moment(day.day);

            let isSameSelectedDay = dayDate.isSame( moment(this.selectedDate.day),'day');
            let isSameSelectedMonth = dayDate.isSame( moment(this.selectedDate.day),'day');
            let isSameSelectedYear = dayDate.isSame( moment(this.selectedDate.day),'day');

            //Si es el mismo día que esta seleccionado, lo deselecciono
            if (isSameSelectedDay && isSameSelectedMonth && isSameSelectedYear)
                this.selectedDate = ''
            else
                this.selectedDate = day;



            //Si estoy viendo info de una tarea y no es del mismo dia que he seleccionado la dejo de ver
            if (this.selectedTask !== ''){

                let taskDate = moment(this.selectedTask.finalDate);

                let isSameDay = taskDate.isSame(dayDate ,'day')
                let isSameMonth = taskDate.isSame(dayDate ,'month')
                let isSameYear = taskDate.isSame(dayDate ,'year')

                if (!isSameDay || !isSameMonth || !isSameYear)
                    this.selectedTask = ''
            }

        },
        seeTaskInfo(task){


            //Compruebo si se esta clicando uno que ya se esta viendo
            if (this.selectedTask._id === task._id){
                this.selectedTask = ''
            }else{
                this.selectedTask = task;
            }
        },
        actionLink(route){
            this.$router.push(route)
        }
    },
    computed:{
        totalTaskWithoutComplete(){

            let total = 0;

            if (this.filteredTasks){
                //Recorro cada día
                if (Object.keys(this.filteredTasks).length > 0){
                    Object.values(this.filteredTasks).forEach((day) => {

                        //Recorro cada tarea
                        if (Object.keys(day).length > 0){

                            Object.values(day).forEach((task) => {
                                //Si no esta completa añado una
                                if (!task.isCompleted) total++
                            })
                        }
                    })
                }
            }

            return total;
        },
        filteredTasks(){

            if (!this.tasks) return []

            let tasks = [...this.tasks];

            let filteredWithoutDivision = [];
            let filteredTasksDivided = {};


            tasks.filter((task) => {

                //filtro por busqueda, ( busco por asunto, nombre de contacto y nombre de cuenta)
                let taskSearch = [task.subject].join('').replace(' ', '').toLowerCase();


                let inDate = true;

                //si hay fecha seleccionada compruebo
                if (this.selectedDate !== ''){

                    //Saco el año, el mes y el dia de la fecha seleccionada inicial y final del día  y de la tarea
                    let selectedDateStart = new Date(this.selectedDate.day);

                    selectedDateStart.setHours(0,0,0, 0);

                    let selectedDateEnd = new Date(this.selectedDate.day);

                    selectedDateEnd.setHours(23, 59, 59, 59)

                    inDate = (new Date(task.finalDate) > selectedDateStart && new Date(task.finalDate) < selectedDateEnd);

                }


                if (taskSearch.includes(this.searchTaskText.replace(' ', '').toLowerCase()) && inDate) filteredWithoutDivision.push(task)
            })


            //Filtrar por cuenta o contacto si tiene


            //Las ordeno de más cercanas a más lejanas
            filteredWithoutDivision.sort((a, b) => new Date(a.finalDate) - new Date(b.finalDate));


            //Los divido en días
            filteredWithoutDivision.forEach((task) => {

                //Compruebo si existe ya el día, sino lo creo
                let taskDate = new Date(task.finalDate)

                taskDate = taskDate.getDate()  + ' de ' + this.months[taskDate.getMonth()] + ' ' +taskDate.getFullYear();

                filteredTasksDivided[taskDate] = filteredTasksDivided[taskDate] || [];
                filteredTasksDivided[taskDate].push(task);

            })

            return filteredTasksDivided
        },
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
