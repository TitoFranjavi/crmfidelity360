<template>
    <div class="content-white">

        <form v-on:submit.prevent="updateTask" class="form register-pos">

            <!--División de inputs-->
            <div class="top-part">

                <!--Detalles de cuenta-->
                <div class="inputs-part">

                    <div class="text" data-size="20" data-weight="700">Detalles de la tarea</div>

                    <!--Asunto-->
                    <div v-bind:class="{ wrong: errors.subject}" class="form-group">
                        <p class="my-auto"><label>Asunto</label> <span data-color="rojo">*</span></p>
                        <div class="input-group">
                            <input v-on:focus="delete errors['subject']" data-size="12" v-model="task.subject" :disabled="isReadOnly" type="text">
                        </div>
                        <span v-if="errors.subject" class="error">{{ errors.subject }}</span>
                    </div>

                    <!--Descripción-->
                    <div v-bind:class="{ wrong: errors.desc}" class="form-group">
                        <label>Descripción</label>
                        <div class="input-group">
                            <input v-on:focus="delete errors['desc']" data-size="12" v-model="task.desc" :disabled="isReadOnly" type="text">
                        </div>
                        <span v-if="errors.desc" class="error">{{ errors.desc }}</span>
                    </div>

                    <div class="half-space">

                        <!--Prioridad-->
                        <div v-bind:class="{ wrong: errors.priority}" class="form-group">
                            <label>Prioridad</label>
                            <div class="input-group">
                                <select v-on:change="delete errors['priority']" v-model="task.priority" :disabled="isReadOnly">
                                    <option value="">Selecciona una prioridad</option>
                                    <option v-for="priority in priorities" :value="priority.code">{{ priority.title }}</option>
                                </select>
                            </div>
                            <span v-if="errors.priority" class="error">{{ errors.priority }}</span>
                        </div>


                        <!--Estado-->
                        <div v-bind:class="{ wrong: errors.status}" class="form-group">
                            <label>Estado</label>
                            <div class="input-group">
                                <select v-on:change="delete errors['status']" v-model="task.status" :disabled="isReadOnly">
                                    <option value="">Selecciona un estado</option>
                                    <option v-for="status in statuses" :value="status.code">{{ status.title }}</option>
                                </select>
                            </div>
                            <span v-if="errors.status" class="error">{{ errors.status }}</span>
                        </div>
                    </div>

                    <div class="half-space">

                        <!--Cuenta-->
                        <div v-bind:class="{ wrong: errors.account}" class="form-group">
                            <label>Cuenta</label>
                            <div class="input-group">
                                <select v-model="task.account" :disabled="isReadOnly">
                                    <option value="">Selecciona una cuenta</option>
                                    <option v-if="accountsGrouped" v-for="account in accountsGrouped" :value="account._id">{{ account.name }}</option>
                                </select>
                            </div>
                            <span v-if="errors.account" class="error">{{ errors.account }}</span>
                        </div>


                        <!--Contacto-->
                        <div v-bind:class="{ wrong: errors.contact}" class="form-group">
                            <label>Contacto</label>
                            <div class="input-group">
                                <select v-model="task.contact" :disabled="isReadOnly">
                                    <option value="">Selecciona un contacto</option>
                                    <option v-if="contactsGrouped" v-for="contact in contactsGrouped" :value="contact._id">{{ contact.name.first + (contact.name.second ? (' ' + contact.name.second) : '')  }}</option>
                                </select>
                            </div>
                            <span v-if="errors.contact" class="error">{{ errors.contact }}</span>
                        </div>
                    </div>


                    <!--Fecha vencimiento-->
                    <div v-bind:class="{ wrong: errors.finalDate}" class="form-group">
                        <p class="my-auto"><label>Fecha vencimiento</label> <span data-color="rojo">*</span></p>
                        <div class="input-group">
                            <input v-on:focus="delete errors['finalDate']" data-size="12" v-model="task.finalDate" :disabled="isReadOnly" type="datetime-local">
                        </div>
                        <span v-if="errors.finalDate" class="error">{{ errors.finalDate }}</span>
                    </div>
                </div>

                <!--Separator vertical-->
                <div class="separator" data-position="vertical"></div>


                <!--Dirección de facturación-->
                <div class="inputs-part">


                    <div class="d-flex">
                        <div class="text my-auto" data-size="20" data-weight="700">Subtareas</div>

                        <div class="custom-button ml-auto my-auto mr-20" data-size="medium" data-bg="amarillo" v-if="!isReadOnly" v-on:click="addSubTask"><i class="fas fa-plus"></i></div>
                    </div>


                    <!--Descripción corta-->
                    <p class="text my-20" data-size="13">Añade subtareas que irás realizando poco a poco para terminar completando la tarea por completo</p>



                    <!--Listado de subtareas-->
                    <div class="mt-30">
                        <sub-task-component v-for="(subTask , subTaskInd) in task.subTasks" :subTask="subTask" :subTaskInd="subTaskInd" :isReadOnly="isReadOnly" :errors="errors" @deleteSubTask="deleteSubTask"></sub-task-component>
                    </div>

                </div>
            </div>

            <!--separador-->
            <div class="separator"></div>

            <!--Botón actualizar-->
            <div class="btn-part">
                <button class="custom-button mr-10" data-size="regular" data-bg="rojo" v-on:click.prevent="actionLink('/tasks')">Cancelar</button>
                <button class="custom-button" data-size="regular" v-if="!isReadOnly">Actualizar <i class="fas fa-chevron-right ml-10"></i></button>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    name: "TaskDetailsComponent",
    props:['basicData'],
    data(){
        return{
            task: '',
            contacts: '',
            accounts: '',
            errors:{},
            statuses:[
                {
                    code:'ne',
                    title: 'No empezado'
                },
                {
                    code:'ep',
                    title: 'En progreso'
                },
                {
                    code:'c',
                    title: 'Completado'
                }
            ],
            priorities:[
                {
                    code: 'i',
                    title: 'Imprescindible'
                },
                {
                    code: 'a',
                    title: 'Alto'
                },
                {
                    code: 'm',
                    title: 'Medio'
                },
                {
                    code: 'b',
                    title: 'Bajo'
                },


            ],
        }
    },
    mounted() {
        this.fetchTask()

        if (this.basicData.userLogged && this.basicData.userLogged._id){
            if (this.contacts.length === 0)
                this.fetchAllContacts()

            if (this.accounts.length === 0)
                this.fetchAllAccounts()
        }

    },
    watch:{
        'basicData.userLogged'(){
            this.fetchAllContacts()
            this.fetchAllAccounts()
        }
    },
    methods:{
        fetchTask(){

            axios.get(`/api/tasks/show/${this.$route.params.id}`)
                .then((res) => {
                    this.task = res.data.task

                    this.filterTask()
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        fetchAllContacts(){

            axios.post(`/api/contacts/indexWithoutPagination/${this.basicData.userLogged._id}`, { userList: JSON.stringify(this.basicData.userList) })
                .then((res) => {
                    this.contacts = res.data.contacts
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        fetchAllAccounts(){

            axios.post(`/api/accounts/indexWithoutPagination/${this.basicData.userLogged._id}`,{userList: JSON.stringify(this.basicData.userList)})
                .then((res) => {
                    this.accounts = res.data.accounts
                })
                .catch((err) => {
                    console.log(err)
                })

        },
        filterTask(){
            if (this.task.status === null) this.task.status = ''

            if (this.task.priority === null) this.task.priority = ''

            if (this.task.contact === null) this.task.contact = ''

            if (this.task.account === null) this.task.account = ''
        },
        addSubTask(){

            let newSubtask = {
                subject: '',
                desc: '',
                isCompleted: false
            }

            this.task.subTasks.push(newSubtask)
        },
        deleteSubTask(index){
            this.task.subTasks.splice(index ,1);
        },
        async updateTask(){


            //reinicio errores
            this.errors = {}

            let hasErrors = false;

            //Asunto
            if (this.task.subject === ''){
                this.errors.subject = this.getErrorMessage('isEmpty');
                hasErrors = true;
            }


            //Fecha vencimiento
            if (this.task.finalDate === ''){
                this.errors.finalDate = this.getErrorMessage('isEmpty');
                hasErrors = true;
            }

            /*if (new Date(this.task.finalDate) < new Date()){
                this.errors.finalDate = this.getErrorMessage('fecLess');
                hasErrors = true;
            }*/


            //Compruebo las subtareas
            this.task.subTasks.forEach((task, counter) => {

                if (task.subject === ''){
                    this.task.subTasks[counter].errors = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }
            })

            if (!hasErrors){

                await axios.put(`/api/tasks/update/${this.task._id}`, {task: this.task})
                    .then((res) => {

                        Swal.fire({
                            icon: 'success',
                            title: 'Actualizado correctamente',
                            text: 'Los cambios han sido aplicados correctamente a la tarea',
                            timerProgressBar:true,
                            timer: 1500
                        })

                        //this.$router.push('/tasks')
                        this.isEditing = true
                    })
                    .catch((err) => {
                        console.log(err)
                    })

            }else{
                Swal.fire({
                    icon: "error",
                    title: "Revisa el formulario",
                    text: "Hay errores en tu tarea, revisala!"
                })
            }
        },
        getErrorMessage(type){

            let message = '';

            switch (type){
                case 'isEmpty':
                    message = 'Este campo no puede estar vacio'
                    break;

                case 'fecLess':
                    message = 'La fecha debe de ser posterior a la actual';
                    break;

                case 'onlyNumbers':
                    message = 'Solo puede contener dígitos';
                    break;

                default:
                    message = 'Hay errores en el formulario'
                    break;
            }

            return message;
        },
        actionLink(route){
            this.$router.push(route)
        }
    },
    computed:{
        contactsGrouped(){
            if (this.contacts !== ''){

                let groupedContacts = [...this.contacts.archived, ...this.contacts.notArchived]

                groupedContacts.sort((a, b) => {
                    const nameFirstComparison = (a.name.first || '').localeCompare(b.name.first || '');
                    if (nameFirstComparison !== 0) {
                        return nameFirstComparison;
                    }

                    const nameSecondComparison = (a.name.second || '').localeCompare(b.name.second || '');
                    if (nameSecondComparison !== 0) {
                        return nameSecondComparison;
                    }

                    const surnameFirstComparison = (a.surname.first || '').localeCompare(b.surname.first || '');
                    if (surnameFirstComparison !== 0) {
                        return surnameFirstComparison;
                    }

                    return (a.surname.second || '').localeCompare(b.surname.second || '');
                });

                return groupedContacts
            }
        },
        accountsGrouped(){
            if(this.accounts !== ''){

                let groupedAccs = [...this.accounts.archived, ...this.accounts.notArchived];

                groupedAccs.sort((a, b) => a.name.localeCompare(b.name));

                return groupedAccs
            }

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
