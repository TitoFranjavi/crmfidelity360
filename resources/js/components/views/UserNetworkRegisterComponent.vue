<template>
    <div class="content-white">

        <form v-on:submit.prevent="" class="form register-pos">

            <!--División de inputs-->
            <div class="top-part">

                <!--Detalles-->
                <div class="inputs-part">

                    <div v-if="showCreationTypeSelector" class="text" data-size="20" data-weight="700">Tipo de creación</div>

                    <!--slider-->
                    <div v-if="showCreationTypeSelector" class="slider my-20 mx-auto">
                        <div v-on:click="createUserData.selected = type.code" :class="{ 'selected': type.code === createUserData.selected }" v-for="type in createUserData.types">
                            <div>{{ type.title }}</div>
                        </div>
                    </div>



                    <div class="text mt-20" data-size="15" data-weight="700">{{ createUserData.selected === 0 ? 'Detalles del usuario' : 'Detalles de la key' }}</div>

                    <!--Duración y label-->
                    <div class="half-space">

                        <!--Duración key-->
                        <div v-bind:class="{ wrong: errors.serial}" class="form-group" v-if="createUserData.selected === 1"> <!--o que sea demo-->
                            <label>Duración key</label>
                            <div class="input-group" v-bind:class="{error: errors.serial}">
                                <select v-on:change="delete errors['serial']" v-model="serial.expiration">
                                    <option value="7">1 semana</option>
                                    <option value="15">15 días</option>
                                    <option value="31">1 mes</option>
                                    <option value="93">3 meses</option>
                                    <option value="365">1 año</option>
                                    <option value="730">2 años</option>
                                    <option value="3650">Para siempre</option>
                                </select>
                            </div>
                            <span v-if="errors.serial" class="error">{{ errors.serial }}</span>
                        </div>


                        <!--Label usuario-->
                        <div v-bind:class="{ wrong: errors?.label}" class="form-group">
                            <label>Etiqueta</label>
                            <div class="input-group" v-bind:class="{error: errors?.label}">
                                <select v-on:change="changeUserLabel" v-model="serial.label">
                                    <option :value="label" v-for="label in filteredLabels">{{ label }}</option>
                                </select>
                            </div>
                            <span v-if="errors?.label" class="error">{{ errors?.label }}</span>
                        </div>
                    </div>

                    <!-- INFO DE LA ETIQUETA -->
                    <div class="form-group mt-20" v-if="labelDescriptions[serial.label]">
                        <div class="label-info-card">
                            <p class="text" data-size="16" data-weight="700">
                                {{ labelDescriptions[serial.label].title }}
                            </p>

                            <p class="text opacity-6 mt-5" data-size="12">
                                {{ labelDescriptions[serial.label].desc }}
                            </p>

                            <div class="label-warning mt-15">
                                <p class="text opacity-5" data-size="11">
                                    ℹ️ Los permisos asociados a esta etiqueta se pueden modificar desde la
                                    <b>herramienta de permisos</b>.
                                </p>
                            </div>
                        </div>
                    </div>



                    <!--Usuario demo-->
                    <div class="form-group mt-20">
                        <label class="mb-10">Demo</label>

                        <!--Checkbox-->
                        <div class="d-flex my-20">

                            <!--Select-->
                            <div class="custom-checkbox my-auto mr-10" v-on:click="toggleDemoUser">
                                <div v-bind:class="{selected: serial.label === 'Usuario demo'}"></div>
                            </div>

                            <!--Info-->
                            <div>
                                <!--code-->
                                <p class="text" data-size="15">Usuario Demo</p>

                                <!--descripcion-->
                                <p class="text opacity-5" data-size="10">Selecciona para que este usuario sea demo y tenga la cuenta tenga un tiempo de duración</p>
                            </div>

                        </div>

                        <!--Tiempo duración-->
                        <div class="d-flex my-20" v-if="serial.label === 'Usuario demo'">

                            <!--Select-->
                            <div class="form-group">
                                <label>Duración cuenta</label>
                                <div class="input-group">
                                    <select v-model="durationSelect" v-on:change="setUserDemoDuration">
                                        <option :value="duration.value" v-for="duration in demoUserDurationOptions">{{ duration.title }}</option>
                                    </select>
                                </div>
                            </div>

                            <!--Si es personalizado-->
                            <div v-if="durationSelect === 'p'" v-bind:class="{ wrong: errors.userDuration}" class="form-group ml-10">
                                <label>Fecha final</label>
                                <div class="input-group" v-bind:class="{error: errors.userDuration}">
                                    <input data-size="12" v-model="customFinalDate" v-on:change="setFinalDuration" onkeydown="return false;" type="date">
                                </div>
                                <span v-if="errors.userDuration" class="error">{{ errors.userDuration }}</span>
                            </div>

                        </div>
                    </div>

                    <!--Listado de usuarios-->
                    <div class="form-group mt-20">
                        <label class="mb-20">Usuarios responsables</label>

                        <!--Lista de usuarios que quiero que sean responsables del usuario que voy a crear-->
                        <user-list-component :basicData="basicData" v-model:userListSelected="serial.responsibles" :editing="true"></user-list-component>

                        <p v-if="basicData.userList.length === 0" class="text opacity-3" data-size="10">No tienes usuarios para asignar</p>
                    </div>
                </div>

                <!--Separator vertical-->
                <div class="separator" data-position="vertical"></div>


                <!--Datos del usuario-->
                <div class="inputs-part">

                    <!--Creación directa-->
                    <form autocomplete="off">
                        <!-- Campo oculto para evitar autocompletado automático -->
                        <input type="password" style="display: none;" autocomplete="new-password">

                        <div v-if="createUserData.selected === 0">

                            <!-- Nombre y Apellidos -->
                            <div class="half-space">
                                <div v-bind:class="{ wrong: errors.firstName}" class="form-group">
                                    <p class="my-auto"><label>Nombre</label> <span data-color="rojo">*</span></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete errors['firstName']" data-size="12" v-model="serial.userData.firstName" type="text" :name="null" :id="null" autocomplete="off">
                                    </div>
                                    <span v-if="errors.firstName" class="error">{{ errors.firstName }}</span>
                                </div>

                                <div v-bind:class="{ wrong: errors.lastName}" class="form-group">
                                    <p class="my-auto"><label>Apellidos</label> <span data-color="rojo">*</span></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete errors['lastName']" data-size="12" v-model="serial.userData.lastName" type="text" :name="null" :id="null" autocomplete="off">
                                    </div>
                                    <span v-if="errors.lastName" class="error">{{ errors.lastName }}</span>
                                </div>
                            </div>

                            <!-- Correo y Teléfono -->
                            <div class="half-space">
                                <div v-bind:class="{ wrong: errors.email}" class="form-group">
                                    <p class="my-auto"><label>Correo</label> <span data-color="rojo">*</span></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete errors['email']" data-size="12" v-model="serial.userData.email" type="text" :name="null" :id="null" autocomplete="off">
                                    </div>
                                    <span v-if="errors.email" class="error">{{ errors.email }}</span>
                                </div>

                                <div v-bind:class="{ wrong: errors.phone}" class="form-group">
                                    <p class="my-auto"><label>Teléfono</label></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete errors['phone']" data-size="12" v-model="serial.userData.phone" type="text" :name="null" :id="null" autocomplete="off">
                                    </div>
                                    <span v-if="errors.phone" class="error">{{ errors.phone }}</span>
                                </div>
                            </div>

                            <!-- DNI/CIF y Género -->
                            <div class="half-space">
                                <div v-bind:class="{ wrong: errors.DNI}" class="form-group">
                                    <p class="my-auto"><label>DNI/CIF</label> <span data-color="rojo">*</span></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete errors['DNI']" data-size="12" v-model="serial.userData.DNI" type="text" :name="null" :id="null" autocomplete="off">
                                    </div>
                                    <span v-if="errors.DNI" class="error">{{ errors.DNI }}</span>
                                </div>

                                <div v-bind:class="{ wrong: errors.gender}" class="form-group">
                                    <p class="my-auto"><label>Género</label> <span data-color="rojo">*</span></p>
                                    <div class="input-group">
                                        <select :name="null" v-model="serial.userData.gender">
                                            <option value="M">Masculino</option>
                                            <option value="F">Femenino</option>
                                            <option value="O">Otro</option>
                                        </select>
                                    </div>
                                    <span v-if="errors.gender" class="error">{{ errors.gender }}</span>
                                </div>
                            </div>


                            <!-- Dirección y Código Postal -->
                            <div class="half-space">
                                <div v-bind:class="{ wrong: errors.address}" class="form-group">
                                    <p class="my-auto"><label>Dirección</label></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete errors['address']" data-size="12" v-model="serial.userData.address" type="text" :name="null" :id="null" autocomplete="off">
                                    </div>
                                    <span v-if="errors.address" class="error">{{ errors.address }}</span>
                                </div>

                                <div v-bind:class="{ wrong: errors.zip}" class="form-group">
                                    <p class="my-auto"><label>Código postal</label></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete errors['zip']" data-size="12" v-model="serial.userData.zip" type="text" :name="null" :id="null" autocomplete="off">
                                    </div>
                                    <span v-if="errors.zip" class="error">{{ errors.zip }}</span>
                                </div>
                            </div>

                            <!--Provincia y población-->
                            <div class="half-space">
                                <!--Provincia-->
                                <div v-bind:class="{ wrong: errors.province}" class="form-group">
                                    <p class="my-auto"><label>Provincia</label></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete errors['province']" data-size="12" v-model="serial.userData.province" type="text" autocomplete="nope">
                                    </div>
                                    <span v-if="errors.province" class="error">{{ errors.province }}</span>
                                </div>

                                <!--Población-->
                                <div v-bind:class="{ wrong: errors.locality}" class="form-group">
                                    <p class="my-auto"><label>Población</label></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete errors['zip']" data-size="12" v-model="serial.userData.locality" type="text" autocomplete="nope">
                                    </div>
                                    <span v-if="errors.locality" class="error">{{ errors.locality }}</span>
                                </div>
                            </div>

                            <!-- Contraseña y Repetir Contraseña -->
                            <div class="half-space">
                                <div v-bind:class="{ wrong: errors.password}" class="form-group">
                                    <p class="my-auto"><label>Contraseña</label> <span data-color="rojo">*</span></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete errors['password']" data-size="12" v-model="serial.userData.password" :type="seePassword ? 'text' : 'password'" :name="null" :id="null" autocomplete="new-password" placeholder="Contraseña">
                                        <div v-on:click="seePassword = !seePassword">
                                            <i class="text fas pointer" :class="seePassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </div>
                                    </div>
                                    <span v-if="errors.password" class="error">{{ errors.password }}</span>
                                </div>

                                <div v-bind:class="{ wrong: errors.repeatPassword}" class="form-group">
                                    <p class="my-auto"><label>Repetir contraseña</label> <span data-color="rojo">*</span></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete errors['repeatPassword']" data-size="12" v-model="serial.userData.repeatPassword" :type="seePassword ? 'text' : 'password'" :name="null" :id="null" autocomplete="new-password" placeholder="Repite la contraseña">
                                    </div>
                                    <span v-if="errors.repeatPassword" class="error">{{ errors.repeatPassword }}</span>
                                </div>
                            </div>

                        </div>
                    </form>


                    <!--Key-->
                    <div class="d-flex column mt-50" v-if="createUserData.selected === 1">
                        <div v-if="generated === ''" class="d-flex align-center w-100" data-gap-h="20">
                            <div class="grow h-50 loading round" data-round="15"></div>
                            <p class="text" data-size="20" data-weight="300">-</p>
                            <div class="grow h-50 loading round" data-round="15"></div>
                            <p class="text" data-size="20" data-weight="300">-</p>
                            <div class="grow h-50 loading round" data-round="15"></div>
                        </div>

                        <div v-else>

                            <div class="desktop-item">
                                <div class="d-flex align-center w-100" data-gap-h="20">
                                    <p class="grow text" data-size="40" data-weight="600" data-align="center">{{ formattedKey(0) }}</p>
                                    <p class="text" data-size="20" data-weight="300">-</p>
                                    <p class="grow text" data-size="40" data-weight="600" data-align="center">{{ formattedKey(1) }}</p>
                                    <p class="text" data-size="20" data-weight="300">-</p>
                                    <p class="grow text" data-size="40" data-weight="600" data-align="center">{{ formattedKey(2) }}</p>
                                </div>
                            </div>

                            <div class="mobile-item">
                                <div class="d-flex align-center w-100" data-gap-h="20">
                                    <p class="grow text" data-size="25" data-weight="600" data-align="center">{{ formattedKey(0) }}</p>
                                    <p class="text" data-size="20" data-weight="300">-</p>
                                    <p class="grow text" data-size="25" data-weight="600" data-align="center">{{ formattedKey(1) }}</p>
                                    <p class="text" data-size="20" data-weight="300">-</p>
                                    <p class="grow text" data-size="25" data-weight="600" data-align="center">{{ formattedKey(2) }}</p>
                                </div>
                            </div>
                        </div>

                        <p class="text my-30" data-size="10" data-weight="300">Pulsa en generar para crear un serial
                            con los datos configurados. El serial tendrá un solo uso, así que ten
                            cuidado con quién lo compartes.</p>
                    </div>


                </div>
            </div>

            <!--separador-->
            <div class="separator"></div>

            <!--Botón guardar-->
            <div class="btn-part">
                <button class="custom-button mr-10" data-size="big" data-bg="rojo" v-on:click.prevent="actionLink('/users')">Cancelar</button>
                <button v-if="createUserData.selected === 0" class="custom-button" data-size="big" v-on:click="createUserDirectly">Crear usuario <i class="fas fa-chevron-right ml-10"></i></button>
                <button v-if="createUserData.selected === 1" class="custom-button" data-size="big" v-on:click="generateSerial">Generar key <i class="fas fa-chevron-right ml-10"></i></button>
            </div>
        </form>
    </div>
</template>

<script>

export default {
    name: "UserNetworkRegisterComponent",
    props:['basicData'],
    data(){
        return{
            generated: '',
            searchUser: '',
            searchDevice: '',
            errors: {},
            serial: {
                key: '',
                expiration: '365',
                responsibles: this.getDefaultResponsibles(),
                permissions:[],
                label: 'Usuario',
                replace: false,
                userData:{
                    firstName: '',
                    lastName: '',
                    email: '',
                    phone: '',
                    DNI: '',
                    gender: 'M',
                    address: '',
                    zip: '',
                    province: '',
                    locality: '',
                    password: '',
                    repeatPassword: ''
                }
            },
            seePassword: false,
            createUserData: {
                selected: 0,
                types: [
                    {
                        title: 'Creación directa',
                        code: 0
                    },
                    {
                        title: 'Creación por código',
                        code: 1
                    },
                ]
            },
            demoUserDurationOptions:[
                {
                    title: '1 día',
                    value: '1'
                },
                {
                    title: '1 semana',
                    value: '7'
                },
                {
                    title: '15 días',
                    value: '15'
                },
                {
                    title: '1 mes',
                    value: '31'
                },
                {
                    title: 'Personalizado',
                    value: 'p'
                }
            ],
            durationSelect: '31',
            customFinalDate: ''
        }
    },

    mounted() {
        if (this.isFidelitySubdomain) {
            this.createUserData.selected = 0;
        }

        if (this.basicData?.userLogged?.label !== 'Usuario subdominio') {
            this.serial.label = 'Usuario';
        }
    },

    watch: {
        'basicData.userSubdomain._id': {
            immediate: true,
            handler(newId) {
                if (newId === '6909faa9232c09035a03f3b2') {
                    this.createUserData.selected = 0;
                }
            }
        }
    },

    methods:{

        canManage(code) {
            const user = this.basicData?.userLogged;
            const subdomain = this.basicData?.userSubdomain;

            if (!user || !subdomain) return false;

            const label = user.label;
            const labelsPermissions = subdomain.labels_permissions;

            if (!label || !labelsPermissions) return false;
            if (!labelsPermissions[label]) return false;
            if (!code || !code.includes('.')) return false;

            const [module, action] = code.split('.');
            const modulePermissions = labelsPermissions[label][module];

            return Array.isArray(modulePermissions) && modulePermissions.includes(action);
        },

        getDefaultResponsibles() {

            if (
                this.canManage('users.admiWhiHier') &&
                this.basicData?.userSubdomain?._id
            ) {
                return [this.basicData.userSubdomain._id];
            }

            return [];
        },
        generateSerial() {

            //validaciones
            let hasErrors = false

                //Fecha de expedición demo
                if (this.serial.label === 'Usuario demo' && this.serial.demoExpiration < 0){
                    this.errors.userDuration = 'La fecha final debe ser posterior a la actual'
                    hasErrors = true;
                }


            if (!hasErrors)
                axios.post('/api/serial', {data: this.serial}).then((res) => {
                this.generated = res.data.key;

                this.searchUser = '';
                this.searchDevice = '';
                this.serial = {
                    key: '',
                    expiration: '365',
                    responsibles: [],
                    permissions:[],
                    label: 'Usuario',
                    replace: false,
                    userData:{
                        firstName: '',
                        lastName: '',
                        email: '',
                        phone: '',
                        DNI: '',
                        gender: 'M',
                        address: '',
                        zip: '',
                        password: '',
                        repeatPassword: ''
                    }
                };
            })

        },
        async createUserDirectly(){

            console.log('error')

            //Validaciones
            this.errors = {};
            let hasErrors = false


            //Nombre
            if (!this.serial.userData.firstName){
                this.errors.firstName = this.getErrorMessage('isEmpty')
                hasErrors = true;
            }

            //Apellidos
            if (!this.serial.userData.lastName){
                this.errors.lastName = this.getErrorMessage('isEmpty')
                hasErrors = true;
            }

            if (this.serial.userData.zip !== '' && !this.hasOnlyNumbers(this.serial.userData.zip)){
                this.errors.zip = this.getErrorMessage('hasOnlyNumbers')
                hasErrors = true;
            }

            //Correo
            if (!this.serial.userData.email){
                this.errors.email = this.getErrorMessage('isEmpty')
                hasErrors = true;
            }

            let regex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
            if(!regex.test(this.serial.userData.email)){
                this.errors.email = 'Esta cadena no es del tipo email'
                hasErrors = true;
            }

            //Telefono
            /*if (!this.serial.userData.phone){
                this.errors.phone = this.getErrorMessage('isEmpty')
                hasErrors = true;
            }*/

            if (this.serial.userData.phone && this.serial.userData.phone.length !== 9){
                this.errors.phone = 'El telefono esta mal formado'
                hasErrors = true;
            }

            if (this.serial.userData.phone && !this.hasOnlyNumbers(this.serial.userData.phone)){
                this.errors.phone = this.getErrorMessage('hasOnlyNumbers')
                hasErrors = true;
            }

            //DNI
            if (!this.serial.userData.DNI){
                this.errors.DNI = this.getErrorMessage('isEmpty')
                hasErrors = true;
            }


            //Contraseña
            if (this.serial.userData.password === ''){
                this.errors.password = this.getErrorMessage('isEmpty');
                hasErrors = true;
            }

            //Repite la contraseña
            if (this.serial.userData.repeatPassword === ''){
                this.errors.repeatPassword = this.getErrorMessage('isEmpty');
                hasErrors = true;
            }

            if (this.serial.userData.password !== this.serial.userData.repeatPassword){
                this.errors.repeatPassword = 'Las contraseñas no coinciden';
                hasErrors = true;
            }

            //Fecha de expedición demo
            if (this.serial.label === 'Usuario demo' && this.serial.demoExpiration < 0){
                this.errors.userDuration = 'La fecha final debe ser posterior a la actual'
                hasErrors = true;
            }

            //Le asigno el commissionType
            this.serial.commissionType = 'range';

            //si no hay errores
            if (!hasErrors){

                let data = new FormData();

                data.append('user', JSON.stringify(this.serial));
                data.append('userSubdomain', JSON.stringify(this.basicData.userSubdomain));
                data.append('enterprise', JSON.stringify(this.basicData.subdomainEnterprise));

                await axios.post('/api/user/createDirectly', data)
                    .then((res) => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Usuario creado',
                            text: res.data.message,
                            timerProgressBar: true,
                            timer: 1500
                        })

                        this.$router.push('/users')
                    })
                    .catch((err) => {
                        this.errors = err.response?.data?.errors ?? {};

                        if (err.response?.data?.limit) {
                            Swal.fire({
                                icon: 'error',
                                title: '¡Limite superado!',
                                text: err.response.data.limit,
                                timerProgressBar: true,
                                timer: 2000
                            })
                        }
                    })
            }
        },
        getErrorMessage(code, meta) {
            switch (code) {
                case 'hasOnlyNumbers':
                    return 'Este campo solo puede contener números';
                case 'isEmpty':
                    return 'No puedes dejarlo vacío';
                case 'isDecimal':
                    return 'Debe ser un número entero o decimal';
                case 'isBetween':
                    return 'Debe estar entre ' + meta.min + ' y ' + meta.max;
                case 'isDuplicated':
                    return 'Este campo se encuentra duplicado';
                case 'noSelected':
                    return 'Tienes que seleccionar algún campo';
                default:
                    return 'Hay un problema que no sé cuál es...';
            }
        },
        hasOnlyNumbers(value) {
            return (value !== '' && /^\d+$/.test(value));
        },
        formattedKey(index) {
            let serial = this.generated.match(new RegExp(`.{1,4}`, 'g'));
            return serial[index];
        },
        toggleSelectPermission(permission){

            let index = (this.serial.permissions.indexOf(permission.code));

            if (index !== -1)
                this.serial.permissions.splice(index,1);
            else
                this.serial.permissions.push(permission.code);
        },
        toggleDemoUser(){

            if (this.serial.label !== 'Usuario demo'){

                //Establezco usuario demo
                this.serial.label = 'Usuario demo'

                //añado tiempo en días de duración de cuenta demo ( mes por defecto)
                this.serial.demoExpiration = '31'
                this.serial.demoStartDate = moment().unix()


            }else{

                //Le pongo usuario normal
                this.serial.label = 'Usuario'

                //Quito los demás añadidos
                if (!!this.serial.demoExpiration){
                    delete this.serial.demoExpiration
                    delete this.serial.demoStartDate
                }
            }
        },
        changeUserLabel(event){
            delete this.errors['label']

            //si se pone usuario demo se le añade tiempo expiración, sino se quita si tiene
            if (event.target.value === 'Usuario demo'){

                this.serial.demoExpiration = '31'
                this.serial.demoStartDate = moment().unix()

            }else
                if (!!this.serial.demoExpiration){
                    delete this.serial.demoExpiration
                    delete this.serial.demoStartDate
                }

        },
        setUserDemoDuration(event){
            if (event.target.value !== 'p')
                this.serial.demoExpiration = event.target.value
        },
        setFinalDuration(event){

            delete this.errors.userDuration

            // Fecha actual
            let today = new Date();

            // Fecha objetivo (19 de diciembre de 2024)
            let targetDate = new Date(event.target.value);

            // Calcular la diferencia en milisegundos
            let diffInTime = targetDate - today;

            // Convertir la diferencia de milisegundos a días
            this.serial.demoExpiration = Math.ceil(diffInTime / (1000 * 3600 * 24)).toString();
        },
        actionLink(route){
            this.$router.push(route)
        }
    },
        computed: {
        isFidelitySubdomain() {
            return this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2'
        },

        showCreationTypeSelector() {
            const subdomainId = this.basicData?.userSubdomain?._id;
            if (!subdomainId) return false;
            return subdomainId !== '6909faa9232c09035a03f3b2';
        },

        labelDescriptions() {
        return {
        // Base
        'Usuario': {
        title: 'Usuario',
        desc: 'Acceso comercial básico: cuentas y contratos (ver/crear/editar/borrar), ver documentos, productos y herramientas comerciales.'
        },

        'Usuario demo': {
        title: 'Usuario demo',
        desc: 'Acceso mínimo para demostraciones: cuentas y contratos básicos. Sin gestión avanzada. Pensado para usuarios temporales con caducidad.'
        },

        // Subdominio / Operativa
        'Usuario subdominio': {
        title: 'Usuario subdominio',
        desc: 'Control total del subdominio: gestión completa de cuentas, contratos, documentos, usuarios, productos, liquidaciones y herramientas avanzadas.'
        },

        'Tramitador': {
        title: 'Tramitador',
        desc: 'Perfil completo de tramitación: puede gestionar contratos y documentación como un perfil avanzado. Se usa para operativa interna.'
        },

        // Drive
        'Usuario Drive': {
        title: 'Usuario Drive',
        desc: 'Usuario con acceso documental completo: puede ver/subir/editar/borrar documentos (Drive). Mantiene acceso comercial estándar.'
        },

        'Usuario drive': {
        title: 'Usuario Drive',
        desc: 'Usuario con acceso documental completo: puede ver/subir/editar/borrar documentos (Drive). Mantiene acceso comercial estándar.'
        },

        // Comerciales
        'Comercial': {
        title: 'Comercial',
        desc: 'Perfil comercial: gestión de cuentas y contratos + gestión de red (crear/editar/borrar usuarios y carga masiva).'
        },

        'Comercial Drive': {
        title: 'Comercial Drive',
        desc: 'Perfil comercial con Drive: todo lo de Comercial + gestión documental completa (subir/editar/borrar documentos).'
        },

        // Administración
        'Administrador': {
        title: 'Administrador',
        desc: 'Gestión avanzada: cuentas y contratos con opciones masivas, acceso a liquidaciones y herramientas avanzadas. Perfil de administración.'
        },

        'Jefe administrador': {
        title: 'Jefe administrador',
        desc: 'Administrador con Drive: todo lo del Administrador + gestión documental completa (Drive) y control amplio de herramientas.'
        },

        // Técnico
        'Desarrollador': {
        title: 'Desarrollador',
        desc: 'Acceso técnico completo para mantenimiento y soporte: módulos avanzados, herramientas, logs y configuración.'
        },

        // Máximo
        'Súper usuario': {
        title: 'Súper usuario',
        desc: 'Acceso total sin restricciones: combinación de administración + Drive + comercial. Usar solo en casos concretos.'
        },

        // Cliente
        'Cliente': {
        title: 'Cliente',
        desc: 'Acceso limitado orientado a consulta. Normalmente solo visualiza información y no gestiona partes internas.'
        }
        }
        },

        selectedLabelInfo() {
            if (!this.serial?.label) return null;

            const raw = this.serial.label;
            const direct = this.labelDescriptions[raw];
            if (direct) return direct;

            const key = Object.keys(this.labelDescriptions).find(
            k => k.toLowerCase() === String(raw).toLowerCase()
            );

            return key ? this.labelDescriptions[key] : null;
        },

        filteredLabels() {
            if (!this.$storage?.LABELS) return [];
            const loggedLabel = this.basicData?.userLogged?.label;
            if (loggedLabel !== 'Usuario subdominio') {
                return ['Usuario'];
            }
            return [...new Set(
                this.$storage.LABELS.filter(label => {
                if (
                    this.basicData?.userLogged?.email !== 'franperez@segenet.es' &&
                    (label === 'Usuario subdominio' || label === 'Tramitador')
                ) {
                    return false;
                }
                return true;
                })
            )];
            }
            ,

        filteredPermissions(){

            if (!this.basicData.userLogged) return []

            if (this.basicData.userLogged._id === '65cb57489c2c285441086a43' || this.basicData.userLogged._id === '65d704c63d2a9cbfd79e549a') return this.$storage.PERMISSIONS

            //Saco solo los permisos que tiene el usuario con sesión iniciada
            return this.$storage.PERMISSIONS.filter((permission) => {
                return this.basicData.userLogged.permissions.includes(permission.code)
            })
        },
    }
}
</script>

<style scoped>

</style>
