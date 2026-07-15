<template>
    <div class="content-white">

        <!--Contenedor padre para distribución-->
        <form v-on:submit.prevent="updateContact" class="form register-pos" v-if="contact && contact.name">

            <!--División de inputs-->
            <div class="top-part">

                <!--Detalles de contacto-->
                <div class="inputs-part">

                    <div class="mobile-item">

                        <!--Imagen de contacto-->
                        <div v-bind:class="{ wrong: errors.profileImage }" class="form-group profile-image my-20">
                            <div class="d-flex column">
                                <img :src="profileImage ? profileImage : '/assets/contact_images/default.jpg'"  alt="Imagen de contacto">
                                <div class="custom-button mx-auto" data-bg="blanco" data-size="small" v-if="!isInputsDisabled" v-on:click="openDialog">Cambiar</div>
                            </div>

                            <input id="profileImage" type="file" style="display: none" v-on:change="pickupImage">
                            <span v-if="errors.profileImage" class="error">{{ errors.profileImage }}</span>
                        </div>


                        <div class="text" data-size="20" data-weight="700">Detalles de contacto</div>

                        <!--Nombre-->
                        <div class="form-group d-flex column">

                            <!--primer-->
                            <div v-bind:class="{ wrong: errors.name.first}" class="form-group w-100">
                                <p class="my-auto"><label>Nombre</label> <span data-color="rojo">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['name']['first']" data-size="12" v-model="contact.name.first" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.name.first" class="error">{{ errors.name.first }}</span>
                            </div>

                            <!--segundo-->
                            <div v-bind:class="{ wrong: errors.name.second}" class="form-group w-100">
                                <label>Segundo nombre</label>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['name']['second']" data-size="12" v-model="contact.name.second" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.name.second" class="error">{{ errors.name.second }}</span>
                            </div>
                        </div>
                    </div>



                    <div class="text desktop-item" data-size="20" data-weight="700">Detalles de contacto</div>



                    <!--Imagen y nombre para escritorio-->
                    <div class="half-space desktop-item">

                        <!--Imagen-->
                        <div v-bind:class="{ wrong: errors.profileImage}" class="form-group profile-image my-20">
                            <div class="d-flex column">
                                <img :src="profileImage ? profileImage : '/assets/contact_images/default.jpg'"  alt="Imagen de contacto">
                                <div class="custom-button mx-auto px-20" data-bg="blanco" data-size="regular" v-if="!isInputsDisabled" v-on:click="openDialog">Cambiar</div>
                            </div>

                            <input id="profileImage" type="file" style="display: none" v-on:change="pickupImage">
                            <span v-if="errors.profileImage" class="error">{{ errors.profileImage }}</span>
                        </div>


                        <!--Nombre-->
                        <div class="form-group d-flex column">

                            <!--primer-->
                            <div v-bind:class="{ wrong: errors.name.first}" class="form-group w-100">
                                <p class="my-auto"><label>Nombre</label> <span data-color="rojo">*</span></p>
                                <div class="input-group">
                                    <input v-if="contact && contact.name" v-on:focus="delete errors['name']['first']" data-size="12" v-model="contact.name.first" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.name.first" class="error">{{ errors.name.first }}</span>
                            </div>

                            <!--segundo-->
                            <div v-bind:class="{ wrong: errors.name.second}" class="form-group w-100">
                                <label>Segundo nombre</label>
                                <div class="input-group">
                                    <input v-if="contact && contact.name" v-on:focus="delete errors['name']['second']" data-size="12" v-model="contact.name.second" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.name.second" class="error">{{ errors.name.second }}</span>
                            </div>
                        </div>

                    </div>


                    <!--Apellidos-->
                    <div class="half-space">

                        <!--primero-->
                        <div v-bind:class="{ wrong: errors.surname.first}" class="form-group">
                            <p class="my-auto"><label>Primer apellido</label> <span data-color="rojo">*</span></p>
                            <div class="input-group">
                                <input v-if="contact && contact.surname" v-on:focus="delete errors['surname']['first']" data-size="12" v-model="contact.surname.first" :disabled="isInputsDisabled" type="text">
                            </div>
                            <span v-if="errors.surname.first" class="error">{{ errors.surname.first }}</span>
                        </div>

                        <!--segundo-->
                        <div v-bind:class="{ wrong: errors.surname.second}" class="form-group">
                            <label>Segundo apellido</label>
                            <div class="input-group">
                                <input v-if="contact && contact.surname" v-on:focus="delete errors['surname']['second']" data-size="12" v-model="contact.surname.second" :disabled="isInputsDisabled" type="text">
                            </div>
                            <span v-if="errors.surname.second" class="error">{{ errors.surname.second }}</span>
                        </div>

                    </div>

                    <!--DNI y email-->
                    <div class="half-space">

                        <!--DNI-->
                        <div v-bind:class="{ wrong: errors.DNI}" class="form-group">
                            <label>DNI/NIE</label>
                            <div class="input-group" >
                                <input v-on:focus="delete errors['DNI']" data-size="12" v-model="contact.DNI" :disabled="isInputsDisabled" type="text" name="email">
                            </div>
                            <span v-if="errors.DNI" class="error">{{ errors.DNI }}</span>
                        </div>


                        <!--email-->
                        <div v-bind:class="{ wrong: errors.email}" class="form-group">
                            <label>Email</label>
                            <div class="input-group" >
                                <input v-on:focus="delete errors['email']" data-size="12" v-model="contact.email" :disabled="isInputsDisabled" type="email" name="email">
                            </div>
                            <span v-if="errors.email" class="error">{{ errors.email }}</span>
                        </div>
                    </div>

                    <!--Telefono y nombre empresa-->
                    <div class="half-space">

                        <!--tlf-->
                        <div v-bind:class="{ wrong: errors.phone}" class="form-group">
                            <p class="my-auto"><label>Teléfono</label> <span data-color="rojo">*</span></p>
                            <div class="input-group" >
                                <input v-on:focus="delete errors['phone']" data-size="12" v-model="contact.phone" :disabled="isInputsDisabled" type="text">
                            </div>
                            <span v-if="errors.phone" class="error">{{ errors.phone }}</span>
                        </div>

                        <!--nombre empresa-->
                        <div v-bind:class="{ wrong: errors.companyName}" class="form-group">
                            <label>Nombre empresa</label>
                            <div class="input-group" >
                                <input v-on:focus="delete errors['companyName']" data-size="12" v-model="contact.companyName" :disabled="isInputsDisabled" type="text">
                            </div>
                            <span v-if="errors.companyName" class="error">{{ errors.companyName }}</span>
                        </div>

                    </div>

                    <!--Cuenta y cargo-->
                    <div class="half-space">

                        <!--Cuenta-->
                        <!--<div v-bind:class="{ wrong: errors.billingInfo.addressFirst}" class="form-group" v-on:click.stop="">
                            <label>Cuentas</label>


                            Cuentas ya seleccionadas
                            <div v-if="contact.accounts && contact.accounts.length > 0" v-for="(selectedAccount, selectedAccountInd) in contact.accounts" class="d-flex justify-between my-5">

                                <div class="text ml-5 ellipsis pointer" data-size="13" v-on:click="actionLink('/accounts/' + selectedAccount)">
                                    <i class="fa-solid fa-building mr-10" v-if="accounts && accounts.length > 0 && contact.accounts && selectedAccount"></i> {{ getAccountName(selectedAccount) }}
                                </div>

                                <div class="my-auto pointer" data-color="rojo" v-if="!isInputsDisabled" v-on:click="delAccount(selectedAccountInd)">
                                    <i class="fa-solid fa-x"></i>
                                </div>
                            </div>


                            Buscador
                            <div class="input-group">
                                <input data-size="12" v-model="searchAccountText" :disabled="isInputsDisabled" v-on:focus="accountsFocused = true" type="text">
                                <i class="fa-regular fa-magnifying-glass ml-10 my-auto text"></i>
                            </div>

                            Desplegable con todas las cuentas encontradas
                            <div class="select-div mt-10" v-if="!isInputsDisabled && filteredAccounts.length > 0 && accountsFocused">
                                <div v-for="account in filteredAccounts">
                                    <div class="my-5 d-flex pointer d-flex column"  v-if="contact.accounts.indexOf(account._id) === -1" v-on:click="addAccount(account)">
                                        <p class="text d-flex my-auto" data-size="12"><i class="fa-solid fa-building my-auto mr-10"></i>{{ account.name }}</p>

                                        <div class="separator my-5"></div>
                                    </div>
                                </div>
                            </div>

                            <span v-if="errors.account" class="error">{{ errors.account }}</span>
                        </div>-->

                        <!--cargo-->
                        <div v-bind:class="{ wrong: errors.position}" class="form-group">
                            <label>Cargo</label>
                            <div class="input-group" >
                                <input v-on:focus="delete errors['position']" data-size="12" v-model="contact.position" :disabled="isInputsDisabled" type="text">
                            </div>
                            <span v-if="errors.position" class="error">{{ errors.position }}</span>
                        </div>
                    </div>


                    <!--Datos relacionados-->
                    <div v-if="(contact.tasks && contact.tasks.length > 0)" class="text mt-20" data-size="18" data-weight="700">Datos relacionados</div>


                    <!--Tareas-->
                    <div class="text mt-20" data-size="15" data-weight="500" v-if="contact.tasks && contact.tasks.length > 0"><i class="fas fa-list-check mr-5"></i> Tareas</div>

                    <div class="d-flex my-10" v-for="task in contact.tasks">
                        <div class="d-flex justify-center w-100">

                            <div class="initials twoBlues small mr-20" data-size="18" data-bg="blanco" data-mode="outline" data-weight="700">{{ getInitials(task.subject) }}</div>

                            <div class="d-flex column my-auto ellipsis">
                                <p class="ellipsis" data-color="azul" data-weight="700" data-size="15">{{ task.subject }}</p>
                            </div>

                            <!--Barra progreso-->
                            <div class="d-flex w-25 mx-20" data-force="true">
                                <div class="progress-bar my-auto">
                                    <div :class="'prog-' + getTaskProgress(task).toFixed(0)"></div>
                                </div>
                                <p class="text ml-10 my-auto" data-size="10" data-weight="500">{{ getTaskProgress(task).toFixed(0) }}%</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small" v-on:click="actionLink(`/tasks/${task._id}`)"><i class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>


                    <!--Oportunidades-->
                    <div class="text mt-20" data-size="15" data-weight="500" v-if="contactOpportunities && contactOpportunities.length > 0"><i class="fas fa-file-circle-question mr-5"></i> Oportunidades</div>

                    <div class="my-10" v-if="contactOpportunities && contactOpportunities.length > 0">
                        <div class="d-flex justify-center w-100 my-10" v-for="opportunity in contactOpportunities">

                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700">{{
                                    getInitials(opportunity.name) }}</div>

                            <div class="d-flex column my-auto ellipsis">
                                <p class="ellipsis" data-color="azul" data-weight="700" data-size="15">{{
                                        opportunity.name }}</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                 v-on:click="actionLink(`/opportunities/${opportunity._id}`)"><i
                                class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>


                    <!--Correos relacionados-->
                    <div class="text mt-20" data-size="15" data-weight="500"
                         v-if="contact.mails && contact.mails.length > 0"><i
                        class="far fa-envelopes-bulk mr-5"></i> Correos</div>

                    <div class="d-flex my-10" v-for="mail in contact.mails">
                        <div class="d-flex justify-center w-100">

                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700"
                                 v-if="mail.subject">{{ getInitials(mail.subject) }}</div>

                            <div class="d-flex column my-auto ellipsis">
                                <p class="ellipsis" data-color="azul" data-weight="700" data-size="15"
                                   v-if="mail.subject">{{ mail.subject }}</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                 v-on:click="actionLink(`/tools?section=massiveEmail&email=${mail._id}`)"><i
                                class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>


                    <!--Usuario creador-->
                    <div v-if="contact.createdBy" class="my-20">
                        <p class="text opacity-5" data-size="13" data-weight="600"><i class="fas fa-user mr-5"></i> Creador del contacto</p>

                        <div class="separator my-0"></div>

                        <div class="d-flex justify-center mr-20 my-10 w-100">
                            <div class="initials verySmall mr-20 my-auto" data-style="initials" v-bind:class="{image: contact.createdBy.profileImage}">
                                <img :src="'/assets/profile_images/' + contact.createdBy.profileImage" class="profile-image">
                            </div>

                            <div class="d-flex column my-auto">
                                <p class="text opacity-5" data-weight="600" data-size="14">{{ contact.createdBy.firstName }}</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="tiny" v-on:click="actionLink((this.basicData.userLogged._id === contact.createdBy._id ? '/profile' : `/users/${contact.createdBy._id}`))"><i class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>

                </div>

                <!--Separator vertical-->
                <div class="separator" data-position="vertical"></div>

                <!--Dirección-->
                <div class="inputs-part">
                    <div class="text" data-size="20" data-weight="700">Dirección</div>

                    <!--Comunidad-->
                    <div v-bind:class="{ wrong: errors.billingInfo.community}" class="form-group">
                        <label>Comunidad</label>
                        <div class="input-group">
                            <input v-on:focus="delete errors['billingInfo']['community']" data-size="12" v-model="contact.billingInfo.community" type="text">
                        </div>
                        <span v-if="errors.billingInfo.community" class="error">{{ errors.billingInfo.community }}</span>
                    </div>

                    <!--Provincia y localidad-->
                    <div class="half-space">

                        <!--Provincia-->
                        <div v-bind:class="{ wrong: errors.billingInfo.province}" class="form-group">
                            <label>Provincia</label>
                            <div class="input-group">
                                <input v-on:focus="delete errors['billingInfo']['province']" data-size="12" v-model="contact.billingInfo.province" type="text">
                            </div>
                            <span v-if="errors.billingInfo.province" class="error">{{ errors.billingInfo.province }}</span>
                        </div>

                        <!--Localidad-->
                        <div v-bind:class="{ wrong: errors.billingInfo.locality}" class="form-group">
                            <label>Localidad</label>
                            <div class="input-group">
                                <input v-on:focus="delete errors['billingInfo']['locality']" data-size="12" v-model="contact.billingInfo.locality" type="text">
                            </div>
                            <span v-if="errors.billingInfo.locality" class="error">{{ errors.billingInfo.locality }}</span>
                        </div>
                    </div>

                    <!--Dirección Línea 2 y cod postal-->
                    <div class="half-space">

                        <!--Dirección-->
                        <div v-bind:class="{ wrong: errors.billingInfo.address}" class="form-group ">
                            <label>Dirección</label>
                            <div class="input-group">
                                <input v-on:focus="delete errors['billingInfo']['address']" data-size="12" v-model="contact.billingInfo.address" :disabled="isInputsDisabled" type="text">
                            </div>
                            <span v-if="errors.billingInfo.address" class="error">{{ errors.billingInfo.address }}</span>
                        </div>

                        <!--Código postal-->
                        <div v-bind:class="{ wrong: errors.billingInfo.postal}" class="form-group ">
                            <label>Código postal</label>
                            <div class="input-group">
                                <input v-on:focus="delete errors['billingInfo']['postal']" data-size="12" v-model="contact.billingInfo.postal" :disabled="isInputsDisabled" type="text">
                            </div>
                            <span v-if="errors.billingInfo.postal" class="error">{{ errors.billingInfo.postal }}</span>
                        </div>
                    </div>

                    <div class="separator mt-30"></div>


                    <!--Campos personalizados-->

                    <!--Header-->
                    <div class="d-flex">

                        <div class="text desktop-item" data-size="20" data-weight="700">Campos personalizados</div>

                        <div class="text mobile-item my-20" data-size="18" data-weight="700">Campos personalizados</div>

                        <div class="custom-button ml-auto my-auto mr-20" data-size="medium" data-bg="amarillo" v-if="!isInputsDisabled" v-on:click="addCustomField"><i class="fas fa-plus"></i></div>
                    </div>

                    <custom-fields-component class="my-20" v-for="(field, fieldInd) in contact.customFields" :field="field" :fieldInd="fieldInd" :isReadOnly="isInputsDisabled" @delCustomField="delCustomField" type="cont"></custom-fields-component>

                </div>
            </div>



            <!--separador-->
            <div class="separator desktop-item"></div>

            <div class="contact-detail-actions">
                <!--Boton editar-->
                <div class="btn-part" v-if="!isUpdatingContact && !isEditing">
                    <button class="custom-button mr-10" data-size="big" data-bg="rojo" v-on:click.prevent="actionLink('/contacts')">Volver</button>
                    <button class="custom-button" data-size="big" v-if="!isReadOnly" v-on:click="isEditing = true">Editar</button>
                </div>

                <!--Botón actualizar-->
                <div class="btn-part" v-if="!isUpdatingContact && isEditing">
                    <button class="custom-button mr-10" data-size="big" data-bg="rojo" v-on:click.prevent="restartContact">Cancelar</button>
                    <button class="custom-button" data-size="big" v-if="!isReadOnly">Actualizar<i class="fas fa-chevron-right ml-10"></i></button>
                </div>

                <div class="btn-part" v-if="isUpdatingContact">
                    <button class="custom-button" data-size="big" v-if="!isReadOnly"> <i class="fa-solid fa-spinner-third fa-spin mr-5"></i> Espera un momento</button>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    name: "ContactDetailsComponent",
    props:['basicData'],
    data(){
        return{
            contact: '',
            contactDefect: '',
            errors:{
                name:{
                    first: '',
                    second:''
                },
                surname:{
                    first: '',
                    second:''
                },
                billingInfo:''
            },
            GEO:{
                communities:'',
                provinces:'',
                localities:'',
                addresses: ''
            },
            searchAddressText:'',
            searchAccountText:'',
            accounts:[],
            contactOpportunities:[],
            urlImage: '',
            imageFile: '',
            accountsFocused: false,
            isUpdatingContact: false,
            isEditing: false
        }
    },
    mounted() {
        //Saco el contacto
        this.fetchContact()

        //Obtengo las cuentas relacionadas
        //this.searchAccounts()

        //Obtengo las comunidades
        this.getCommunities()

        if (this.contactOpportunities.length === 0)
            this.getContactOpportunities()
    },
    watch:{
        "basicData.userLogged"(){
            //Obtengo las cuentas relacionadas
            if (this.contactOpportunities.length === 0)
                this.getContactOpportunities()
        },
        "contact.billingInfo.community"(){
            this.getProvinces()
        },
        "contact.billingInfo.province"(){
            this.getLocalities()
        },
        "contact.profileImage"(){
            if(this.contact && this.contact.profileImage)
                this.urlImage = '/assets/contact_images/' + this.contact.profileImage;
        }
    },
    methods:{
        async fetchContact(){

            await axios.get(`/api/contacts/${this.$route.params.id}`)
                .then((res) => {
                    this.contact = res.data.contact
                    this.contactDefect = JSON.parse(JSON.stringify(res.data.contact))

                    this.filterContact()
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async getContactOpportunities() {
            await axios.get(`/api/contacts/getContactOpportunities/${this.$route.params.id}`)
                .then((res) => {
                    this.contactOpportunities = res.data.opportunities;
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        restartContact(){
            this.contact = JSON.parse(JSON.stringify(this.contactDefect))
            this.isEditing = false

            this.filterContact()
        },
        async getCommunities(){

            await axios.get('/api/GEO/getCommunities')
                .then((res) => {
                    this.GEO.communities = res.data.communities;
                })
                .catch((err) => {
                    console.log(err)
                });

        },
        async getProvinces(){

            await axios.get(`/api/GEO/getProvinces/${this.contact.billingInfo.community}`)
                .then((res) => {
                    this.GEO.provinces = res.data.provinces;
                })
                .catch((err) => {
                    console.log(err)
                });
        },
        async getLocalities(){

            await axios.get(`/api/GEO/getLocalities/${this.contact.billingInfo.province}`)
                .then((res) => {
                    this.GEO.localities = res.data.localities.sort((a, b) => a.mun_name.localeCompare(b.mun_name));;
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async searchAddress(){
            await axios.get(`http://apiv1.geoapi.es/qcalles?QUERY=${this.searchAddressText}&type=JSON&key=7bf1d1b4d597394430abc701411697c9a95aceca96efccb0e81cbd2451d3c6ce`)
                .then((res) => {
                    this.GEO.addresses = res.data.data;
                })
                .catch((err) => {
                    console.log(err)
                });
        },
        selectCommunity(event){
            delete this.errors['billingInfo']['community']

            let communityCode = event.target.value

            if (communityCode !== ''){

                //Busco cual es para sacar el nombre
                let community = this.GEO.communities.find((community) => {
                    return community.acom_name === communityCode
                })

                this.opportunity.billingInfo.community = community.acom_name;
            }else{
                this.opportunity.billingInfo.community = '';
            }


            //Deselecciono la provincia y la localidad
            this.opportunity.billingInfo.province = '';

            this.opportunity.billingInfo.locality = '';

            //Borro los registros
            this.GEO.localities='';

            this.getProvinces();
        },
        selectProvince(event){
            delete this.errors['billingInfo']['province']

            let provinceCode = event.target.value

            if (provinceCode !== ''){
                //Busco cual es para sacar el nombre
                let province = this.GEO.provinces.find((province) => {
                    return province.prov_name === provinceCode
                })

                this.opportunity.billingInfo.province = province.prov_name;
            }else{
                this.opportunity.billingInfo.province = '';
            }


            this.opportunity.billingInfo.locality = '';

            this.getLocalities();
        },
        selectLocality(event){
            delete this.errors['billingInfo']['locality']

            let localityCode = event.target.value

            if (localityCode !== ''){
                //Busco cual es para sacar el nombre
                let locality = this.GEO.localities.find((locality) => {
                    return locality.mun_name === localityCode
                })

                this.opportunity.billingInfo.locality = locality.mun_name;
            }else{
                this.opportunity.billingInfo.locality = '';
            }

        },
        selectAddress(address){

            this.contact.billingInfo.addressFirst = address.TVIA + address.NVIAC + ' (' + address.NENTSI50 + ')';

            this.searchAddressText = '';
            this.GEO.addresses = '';
        },
        async searchAccounts(){

            await axios.post(`/api/contacts/getAccountsRelated/${this.basicData.userLogged._id}`, { userList: this.basicData.userList })
                .then((res) => {
                    this.accounts = res.data.accounts;
                })
                .catch((err) => {
                    console.log(err)
                });
        },
        openDialog() {
            $('#profileImage').click();
        },
        pickupImage() {
            let input = $('#profileImage');
            if (input.prop('files')) {
                let file = input.prop('files')[0];
                if (file['type'] === 'image/jpeg' || file['type'] === 'image/png') {
                    this.imageFile = file;
                    this.urlImage = URL.createObjectURL(this.imageFile);
                } else {
                    Swal.fire({
                        title: 'Eh... Vaya',
                        text: 'Lo que has tratado de subir no es una imagen. Tu imagen debe estar en formato .JPG o .PNG.',
                        icon: 'info'
                    })
                }
            }
        },
        addAccount(account){
            this.contact.accounts.push(account._id)

            this.searchAccountText = '';
        },
        delAccount(accountInd){

            this.contact.accounts.splice(accountInd, 1);
        },
        getAccountName(acc){

            let name = '';

            this.accounts.find((account) => {
                if(account._id === acc){
                    name = account.name
                }
            })

            return name;
        },
        filterContact(){

            if (this.contact && this.contact.billingInfo.community === null) this.contact.billingInfo.community = ''

            if (this.contact && this.contact.billingInfo.province === null) this.contact.billingInfo.province = ''

            if (this.contact && this.contact.billingInfo.locality === null)
                this.contact.billingInfo.locality = ''
        },
        updateContact(){

            if (this.isUpdatingContact === false){


                this.isUpdatingContact = true;

                //reseteo los errores
                this.errors = {
                    name:{
                        first: '',
                        second:''
                    },
                    surname:{
                        first: '',
                        second:''
                    },
                    billingInfo:{}
                }

                let hasErrors = false;

                //Validaciones

                //INFORMACIÓN BÁSICA

                //Nombre
                if (this.contact.name.first === ''){
                    this.errors.name.first = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //Primer apellido
                if (this.contact.surname.first === ''){
                    this.errors.surname.first = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //DNI
                let regexDNI = /^[0-9]{8}[A-Za-z]$|^[XYZ][0-9]{7}[A-Za-z]$/;

                if ( this.contact.DNI && !regexDNI.test(this.contact.DNI)){
                    this.errors.DNI = this.getErrorMessage('malformedDNI');
                    hasErrors = true;
                }

                //Email
                let regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

                if ( this.contact.email && !regexEmail.test(this.contact.email)){
                    this.errors.email = this.getErrorMessage('malformedEmail');
                    hasErrors = true;
                }


                //Telefono
                if (this.contact.phone === ''){
                    this.errors.phone = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                if (this.contact.phone && this.contact.phone.length !== 9){
                    this.errors.phone = this.getErrorMessage('malformedPhone');
                    hasErrors = true;
                }



                //Compruebo los campos personalizados, si esque se han añadido
                if (this.contact.customFields.length > 0){

                    this.contact.customFields.forEach((customField) => {

                        if (customField.title === ''){
                            customField.errors = this.getErrorMessage('isEmpty');
                            hasErrors = true;
                        }
                    })
                }


                //BILLING INFO

                //Codigo postal
                if (this.contact.billingInfo.postal && this.contact.billingInfo.postal.length !== 5){
                    this.errors.billingInfo.postal = 'El zip tiene que tener 5 digitos';
                    hasErrors = true;
                }

                if (this.contact.billingInfo.postal && isNaN(this.contact.billingInfo.postal)){
                    this.errors.billingInfo.postal = this.getErrorMessage('onlyNumbers');
                    hasErrors = true;
                }


                if (!hasErrors){

                    let data = new FormData();

                    data.append('contact', JSON.stringify(this.contact));
                    data.append('imageFile', this.imageFile);

                    //Busco los campos personalizados que sean tipo imagen
                    this.contact.customFields.forEach((field, fieldInd) => {

                        if (field.type === 'image')
                            data.append(('customFieldFile' + fieldInd), field.value);

                    })

                    axios.post('/api/contacts/update', data )
                        .then((res) => {

                            Swal.fire({
                                icon: 'success',
                                title: 'Contacto actualizado!',
                                text: ' El contacto ha sido actualizado correctamente',
                                timerProgressBar:true,
                                timer: 1500
                            })

                            //this.$router.push('/contacts')
                            this.isEditing = false

                            this.isUpdatingContact = false;

                        })
                        .catch((err) => {
                            console.log(err)
                            this.isUpdatingContact = false;
                        })
                }else{
                    this.isUpdatingContact = false;
                }

            }

        },
        getErrorMessage(type){

            let message = '';

            switch (type){
                case 'isEmpty':
                    message = 'Este campo no puede estar vacio'
                    break;

                case 'malformedEmail':
                    message = 'El email esta mal formado';
                    break;

                case 'malformedPhone':
                    message = 'El número de telefono esta mal formado';
                    break;

                default:
                    message = 'Hay errores en el formulario'
                    break;
            }

            return message;
        },
        addCustomField(){

            let customField = {
                title: '',
                type: 'text',
                value: ''
            }

            this.contact.customFields.push(customField);
        },
        delCustomField(fieldInd){
            this.contact.customFields.splice(fieldInd, 1);
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
        getTaskProgress(task){

            if (task.isCompleted){
                return 100
            }else{

                if (task.subTasks && task.subTasks.length > 0){
                    let completedTaskNum = 0;

                    task.subTasks.forEach((subTask) => {
                        if (subTask.isCompleted) completedTaskNum++
                    })

                    return ((completedTaskNum / task.subTasks.length) * 100)
                }else {
                    return 0
                }
            }
        },
        actionLink(route){
            this.$router.push(route)
        }
    },
    computed:{
        profileImage() {
            return this.urlImage;
        },
        filteredAccounts(){

            let accounts = []

            if (this.searchAccountText === ''){
                accounts = this.accounts;
            }else{
                this.accounts.filter((account) => {

                    let AccountFiltered = account.name.replace(' ', '').toLowerCase().normalize('NFC');

                    if (AccountFiltered.includes(this.searchAccountText.replace(' ', '').toLowerCase().normalize('NFC')))
                        accounts.push(account);
                })
            }

            //Ordeno
            accounts = accounts.sort((a,b) => a.name.localeCompare(b.name))

            return accounts;
        },
        isReadOnly(){
            if (!this.basicData.userLogged)
                return true
            else (this.basicData.userLogged)
                return this.basicData.userLogged.permissions.includes('READONLY')
        },
        isInputsDisabled(){
            if (!this.basicData.userLogged)
                return true
            else (this.basicData.userLogged)
            return this.basicData.userLogged.permissions.includes('READONLY') || !this.isEditing
        }
    }
}
</script>

<style scoped>

</style>
