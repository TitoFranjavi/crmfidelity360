<template>
    <div>

        <!--Listado de estados-->
        <div class="d-flex">
            <div class="w-65 p-15">
                <draggable v-if="basicData?.userSubdomain" v-model="basicData.userSubdomain.statuses" item-key="code"  :disabled="!isEditing">
                    <template #item="{ element: status }">
                        <div v-if="!status.archived" class="status-card w-70 w-600-px-min" :class="{'selected': statusSelectedCode === status.code, 'pointer': isEditing}" v-on:click="(isEditing && !status.default) ? toggleSelected(status) : null">
                            <!--Icono-->
                            <p class="icon" data-size="17">
                                <i class="fa-solid fa-bars" v-if="isEditing"></i>
                            </p>
                            <!--Título estado-->
                            <p class="title">{{ status.title }}</p>
                            <!--Eliminar o obligatoriedad-->
                            <div :class="{ delete: !status.default, mandatory: status.default }">
                                <p v-if="!status.default && isEditing" v-on:click.stop="deleteStatus(status)">Eliminar</p>
                                <i v-else-if="status.default && isEditing" class="fa-solid fa-lock" v-on:click="triggerLockAnimation($event)"></i>
                            </div>
                            <!--Color-->
                            <div class="status-color">
                                <p :data-bg="!isHex(status.color) ? status.color : null"
                                   :style="isHex(status.color) ? { backgroundColor: status.color } : {}"></p>
                            </div>
                        </div>
                    </template>
                </draggable>

                <!--Línea separadora-->
                <div v-if="archivedStatuses.length > 0" class="separator"></div>

                <!--Estados archivados-->
                <p v-if="archivedStatuses.length > 0" class="italic text" data-size="10">Estados archivados</p>

                <div v-for="status in archivedStatuses" class="status-card w-70 w-600-px-min" :class="{'selected': statusSelectedCode === status.code, 'pointer': isEditing}">

                    <!--Icono-->
                    <p class="icon" data-size="17">
                    </p>
                    <!--Título estado-->
                    <p class="title">{{ status.title }}</p>
                    <!--Icono archivado-->
                    <div class="archived">
                        <i v-if="isEditing" v-on:click="unarchiveStatus(status)" class="fa-solid fa-box-archive"></i>
                    </div>
                    <!--Color-->
                    <div class="status-color">
                        <p :data-bg="!isHex(status.color) ? status.color : null"
                           :style="isHex(status.color) ? { backgroundColor: status.color } : {}"></p>
                    </div>

                </div>

            </div>


            <!--Edición estado y color picker-->
            <div class="w-35 p-15">

                <!--Botón editar-->
                <div class="d-flex" v-if="!isEditing">
                    <button class="custom-button ml-auto" data-size="medium" data-bg="principal" v-on:click="isEditing = true">Editar</button>
                </div>
                <div class="d-flex" v-else>
                    <button class="custom-button ml-auto mr-10" data-size="medium" data-bg="principal" v-on:click="addStatus">Añadir</button>
                    <button class="custom-button mr-10" data-size="medium" data-bg="rojo" v-on:click="cancelChanges">Cancelar</button>
                    <button class="custom-button" data-size="medium" data-bg="principal" v-on:click="saveChanges">Guardar</button>
                </div>

                <div class="form mt-20" v-if="statusSelectedCode !== null">
                    <!--Cambio nombre-->
                    <div class="form-group">
                        <p class="my-auto"><label>Nombre</label> <span data-color="rojo">*</span></p>
                        <div class="input-group">
                            <input data-size="10" v-model="statusSelected.title" v-on:blur="setCodeIfNotDefault" type="text">
                        </div>
                    </div>

                    <div class="d-flex my-5">
    <div
        class="custom-checkbox my-auto mr-10"
        v-on:click="!statusSelected.default && (statusSelected.sendEmail = !statusSelected.sendEmail)"
    >
        <div
            v-bind:class="{
                selected: statusSelected.sendEmail
            }"
        ></div>
    </div>

    <p
        class="my-auto mr-15"
        data-color="principal"
        data-size="10"
        :class="{ 'opacity-6': statusSelected.default }"
    >
        Enviar correo de cambio de estado
    </p>
</div>

                    <!--Color picker-->
                    <div class="form-group ">
                        <p class="my-auto"><label>Color</label> <span data-color="rojo">*</span></p>
                        <div class="d-flex justify-center" ref="colorPickerWrapper">
                            <div id="color-picker"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>


<script>
import draggable from 'vuedraggable';
import Pickr from '@simonwep/pickr';
import '@simonwep/pickr/dist/themes/nano.min.css'; // O 'monolith.min.css' o 'classic.min.css'


export default {
    name: "StatusesComponent",
    components: {
        draggable
    },
    props: ['basicData'],
    data(){
        return {
            isEditing: false,
            statusSelectedCode: null,
            statusSelectedColorHex: null,
            statusesCopy: null,
            pickr: null
        }
    },
    watch: {
        'basicData.userSubdomain'(newVal) {
            if (newVal && newVal.statuses && !this.statusesCopy) {
                this.statusesCopy = JSON.parse(JSON.stringify(newVal.statuses));
            }
        },
        statusSelectedCode(newCode) {
            this.$nextTick(() => {
                this.initColorPicker();
            });
        }
    },
    mounted() {
        if (this.basicData && this.basicData.userSubdomain &&!this.statusesCopy)
            this.statusesCopy = JSON.parse(JSON.stringify(this.basicData.userSubdomain.statuses));
    },
    methods:{
        initColorPicker() {
            if (this.pickr) {
                this.pickr.destroyAndRemove();
                this.pickr = null;
            }

            // 🔁 Borra el div #color-picker y vuelve a insertarlo
            const wrapper = this.$refs.colorPickerWrapper;
            if (wrapper) {
                wrapper.innerHTML = '<div id="color-picker"></div>';
            }

            if (!this.statusSelected) return;


            this.$nextTick(() => {
                const colorPickerEl = document.querySelector('#color-picker');
                if (!colorPickerEl) {
                    console.warn('El elemento #color-picker no está en el DOM todavía');
                    return;
                }

                this.pickr = Pickr.create({
                    el: '#color-picker',
                    theme: 'nano',
                    inline: false,
                    disabled: false,
                    showAlways: true,
                    default: this.statusSelectedColorHex || '#000000',
                    components: {
                        preview: true,
                        opacity: true,
                        hue: true,
                        interaction: {
                            hex: true,
                            rgba: true,
                            input: true,
                            save: true,
                            cancel: true
                        }
                    },
                    i18n: {
                        'btn:save': 'Guardar',
                        'btn:cancel': 'Cancelar',
                    }
                });

                this.pickr.on('save', (color) => {
                    this.statusSelected.color = color.toHEXA().toString();
                });

            })

        },
        isHex(color) {
            return /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(color);
        },
        toggleSelected(status){

            console.log('this.statusSelected --> ', this.statusSelected)

            if (this.statusSelected && (this.statusSelected.code === '' || this.statusSelected.title === ''))
                Swal.fire({
                    icon: 'warning',
                    title: 'Rellena el título primero'
                })
            else{

                if (!this.statusSelectedCode)
                    this.statusSelectedCode = status.code
                else{
                    //si se selecciona el mismo
                    if (this.statusSelectedCode !== status.code)
                        this.statusSelectedCode = status.code
                    else{
                        this.statusSelectedCode = null
                        this.statusSelectedColorHex = null
                    }
                }

            }
        },
        cancelChanges(){

            //Reestablezco como estaban
            this.basicData.userSubdomain.statuses = JSON.parse(JSON.stringify(this.statusesCopy))

            this.isEditing = false
            this.statusSelectedCode = null
            this.statusSelectedColorHex = null
        },
        async saveChanges(){

            // Validación 1: estados sin título o sin código
            const missingFields = this.basicData.userSubdomain.statuses.find(s => !s.title?.trim() || !s.code?.trim());
            if (missingFields) {
                Swal.fire({
                    icon: 'error',
                    title: 'Faltan datos',
                    text: 'Todos los estados deben tener un título y un código.'
                });
                return;
            }

            // Validación 2: títulos duplicados
            const titles = this.basicData.userSubdomain.statuses.map(s => s.title.trim().toLowerCase());
            const duplicateTitle = titles.find((t, i) => titles.indexOf(t) !== i);
            if (duplicateTitle) {
                Swal.fire({
                    icon: 'error',
                    title: 'Nombre duplicado',
                    text: `Hay más de un estado con el título "${duplicateTitle}". Usa nombres únicos.`
                });
                return;
            }

            // Validación 3: códigos duplicados
            const codes = this.basicData.userSubdomain.statuses.map(s => s.code.trim().toLowerCase());
            const duplicateCode = codes.find((c, i) => codes.indexOf(c) !== i);
            if (duplicateCode) {
                Swal.fire({
                    icon: 'error',
                    title: 'Código duplicado',
                    text: `Hay más de un estado con el código "${duplicateCode}". Usa códigos únicos.`
                });
                return;
            }


            //Guardar cambios
            await axios.put('/api/tools/statuses', {'user': this.basicData.userSubdomain})
                .then((res) => {

                    this.isEditing = false
                    this.statusSelectedCode = null
                    this.statusSelectedColorHex = null

                    //Establezco la copia en lo nuevo guardado
                    this.statusesCopy = JSON.parse(JSON.stringify(this.basicData.userSubdomain.statuses));

                    Swal.fire({
                        icon: 'success',
                        title: 'Estados actualizados',
                        text: 'Los estados han sido actualizados correctamente',
                        timer: 2500,
                        timerProgressBar: true
                    })
                })
                .catch((err) => {
                    console.log(err)
                })

        },
        addStatus(){

            //Meto el estado nuevo
            this.basicData.userSubdomain.statuses.push({
                "code": "",
                "title": "",
                "color": "#012c68",
                "default": false,
                "archived": false,
                "limitedTo": []
            })

            //Lo selecciono
            this.statusSelectedCode = '';
            this.statusSelectedColorHex = '#012c68';

        },
        async deleteStatus(status){

            Swal.fire({
                icon: 'warning',
                title: '¿Estás seguro?',
                text: 'Una vez guardes los cambios los estados eliminados no podrán ser recuperados',
                showCancelButton: true,
                cancelButtonColor: 'red',
                cancelButtonText: 'No',
                confirmButtonText: 'Sí',
            }).then((res) => {
                if (res.isConfirmed){

                    //Compruebo si hay algún contrato con ese estado ( o bien que esté en ese actualmente o lo tenga en el log )
                    axios.post('/api/orders/checkIfHasStatus', { statusCode: status.code, usersDown: this.basicData.userList, userLogged: this.basicData.userLogged })
                        .then((res) => {

                            //Si no hay ninguno con ese estado
                            if(!res.data){
                                this.basicData.userSubdomain.statuses = this.basicData.userSubdomain.statuses.filter(statusNow => statusNow.code !== status.code)

                                //Deselecciono to lo seleccionado
                                this.statusSelectedCode = null
                                this.statusSelectedColorHex = null
                            }else{

                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Estado existente en cuenta',
                                    text: 'Hay contratos que tienen este estado actualmente o en el historico por lo que no es posible borrarlo. ¿Deseas archivarlo?',
                                    showCancelButton: true,
                                    cancelButtonColor: 'red',
                                    cancelButtonText: 'Cancelar',
                                    confirmButtonText: 'Sí',
                                }).then((res) => {

                                    if (res.isConfirmed){
                                        status.archived = true
                                    }

                                });
                            }



                            //Si lo hay, pregunto que si quiere archivar el estado
                        })
                        .catch((err) => {
                            console.log(err)
                        })

                }
            })



        },
        unarchiveStatus(status){
            Swal.fire({
                icon: 'warning',
                title: '¿Estás seguro de desarchivarlo?',
                text: '',
                showCancelButton: true,
                cancelButtonColor: 'red',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí',
            }).then((res) => {

                if (res.isConfirmed){
                    status.archived = false
                }

            });
        },
        setCodeIfNotDefault() {
            // Solo genera el código si no es uno de los estados por defecto
            if (!this.statusSelected.default) {
                // Generar el código automáticamente a partir del título
                const generatedCode = this.statusSelected.title
                    .trim()
                    .toLowerCase()
                    .replace(/\s+/g, '_')   // Reemplazar espacios por _
                    .replace(/[^\w]/g, '')  // Eliminar caracteres no alfanuméricos

                //Compruebo si hay algún contrato con ese estado, si lo hay no permito el cambio de code
                axios.post('/api/orders/checkIfHasStatus', { statusCode: this.statusSelected.code, usersDown: this.basicData.userList })
                    .then((res) => {

                        //Si no hay ninguno con ese estado, puedo cambiar el código, sino no se cambia
                        if(!res.data){
                            this.statusSelected.code = generatedCode;
                            this.statusSelectedCode = generatedCode;
                        }


                        // Comprobar si ya existe un estado con mismo título o código
                        const exists = this.basicData.userSubdomain.statuses.some(existing =>
                                existing !== this.statusSelected && (
                                    existing.title.trim().toLowerCase() === this.statusSelected.title.trim().toLowerCase() ||
                                    existing.code.trim().toLowerCase() === generatedCode
                                )
                        );


                        // Si ya existe, borrar el nombre y avisar
                        if (exists) {
                            this.statusSelected.title = ''; // <- probablemente quisiste borrar el título, no "name"
                            this.statusSelected.code = '';
                            this.statusSelectedCode = '';

                            Swal.fire({
                                icon: 'error',
                                title: 'Cambia el nombre',
                                text: 'Ya existe un estado con ese nombre o código. Usa uno diferente.'
                            });
                        }

                    })
                    .catch((err) => {
                        console.log(err)
                    })

            }
        },
        triggerLockAnimation(event) {
            const icon = event.currentTarget;

            // Añade clase de animación
            icon.classList.add('shake-lock');

            // Quita la clase tras el tiempo de la animación (300ms)
            setTimeout(() => {
                icon.classList.remove('shake-lock');
            }, 300);
        }
    },
    computed:{
        statusSelected(){
            if (!this.basicData || this.statusSelectedCode === null) return null

            let statusSelected = this.basicData.userSubdomain.statuses.find(status => ((status.code === this.statusSelectedCode) && !status.default))

            //Si no es hexadecimal, es decir, un color con texto saca del scss
            if (!this.isHex(statusSelected.color))
                this.statusSelectedColorHex = getComputedStyle(document.documentElement)
                    .getPropertyValue(`--color-${statusSelected.color}`)
                    .trim();
            else
                this.statusSelectedColorHex = statusSelected.color

            //Saco el estado por el código
            return statusSelected
        },
        archivedStatuses(){

            if (!this.basicData || !this.basicData.userSubdomain) return []

            return this.basicData.userSubdomain.statuses.filter(status => status.archived === true)
        }
    },
    unmounted() {
        if (this.pickr) {
            this.pickr.destroyAndRemove();
            this.pickr = null;
        }
    }
};
</script>

<style scoped>

</style>
