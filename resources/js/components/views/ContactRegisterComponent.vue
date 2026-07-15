<template>
    <div class="content-white" v-on:click="accountsFocused = false">

        <!--Contenedor padre para distribución-->
        <form v-on:submit.prevent="createContact" class="form register-pos">

            <!--División de inputs-->
            <div class="top-part">

                <!--Detalles de contacto-->
                <div class="inputs-part">

                    <div class="mobile-item">

                        <!--Imagen de contacto-->
                        <div v-bind:class="{ wrong: errors.profileImage}" class="form-group profile-image my-20">
                            <div class="d-flex column">
                                <img :src="profileImage ? profileImage : '/assets/profile_images/default.jpg'"  alt="Imagen de contacto">
                                <div class="custom-button mx-auto" data-bg="blanco" data-size="small" v-on:click="openDialog">Cambiar</div>
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
                                    <input v-on:focus="delete errors['name']['first']" data-size="12" v-model="contact.name.first" type="text">
                                </div>
                                <span v-if="errors.name.first" class="error">{{ errors.name.first }}</span>
                            </div>

                            <!--segundo-->
                            <div v-bind:class="{ wrong: errors.name.second}" class="form-group w-100">
                                <label>Segundo nombre</label>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['name']['second']" data-size="12" v-model="contact.name.second" type="text">
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
                                <img :src="profileImage ? profileImage : '/assets/profile_images/default.jpg'"  alt="Imagen de contacto">
                                <div class="custom-button mx-auto px-20" data-bg="blanco" data-size="regular" v-on:click="openDialog">Cambiar</div>
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
                                    <input v-on:focus="delete errors['name']['first']" data-size="12" v-model="contact.name.first" type="text">
                                </div>
                                <span v-if="errors.name.first" class="error">{{ errors.name.first }}</span>
                            </div>

                            <!--segundo-->
                            <div v-bind:class="{ wrong: errors.name.second}" class="form-group w-100">
                                <label>Segundo nombre</label>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['name']['second']" data-size="12" v-model="contact.name.second" type="text">
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
                                <input v-on:focus="delete errors['surname']['first']" data-size="12" v-model="contact.surname.first" type="text">
                            </div>
                            <span v-if="errors.surname.first" class="error">{{ errors.surname.first }}</span>
                        </div>

                        <!--segundo-->
                        <div v-bind:class="{ wrong: errors.surname.second}" class="form-group">
                            <label>Segundo apellido</label>
                            <div class="input-group">
                                <input v-on:focus="delete errors['surname']['second']" data-size="12" v-model="contact.surname.second" type="text">
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
                                <input v-on:focus="delete errors['DNI']" data-size="12" v-model="contact.DNI" type="text" name="email">
                            </div>
                            <span v-if="errors.DNI" class="error">{{ errors.DNI }}</span>
                        </div>


                        <!--email-->
                        <div v-bind:class="{ wrong: errors.email}" class="form-group">
                            <label>Email</label>
                            <div class="input-group" >
                                <input v-on:focus="delete errors['email']" data-size="12" v-model="contact.email" type="email" name="email">
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
                                <input v-on:focus="delete errors['phone']" data-size="12" v-model="contact.phone" type="text">
                            </div>
                            <span v-if="errors.phone" class="error">{{ errors.phone }}</span>
                        </div>

                        <!--nombre empresa-->
                        <div v-bind:class="{ wrong: errors.companyName}" class="form-group">
                            <label>Nombre empresa</label>
                            <div class="input-group" >
                                <input v-on:focus="delete errors['companyName']" data-size="12" v-model="contact.companyName" type="text">
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
                            <div v-if="contact.accounts.length > 0" v-for="(selectedAccount, selectedAccountInd) in contact.accounts" class="d-flex justify-between my-5">

                                <div class="text ml-5 ellipsis" data-size="13">
                                    <i class="fa-solid fa-building mr-10"></i> {{ getAccountName(selectedAccount).name }}
                                </div>

                                <div class="my-auto pointer" data-color="rojo" v-on:click="delAccount(selectedAccountInd)">
                                    <i class="fa-solid fa-x"></i>
                                </div>
                            </div>


                            Buscador
                            <div class="input-group">
                                <input data-size="12" v-model="searchAccountText" v-on:focus="accountsFocused = true" type="text">
                                <i class="fa-regular fa-magnifying-glass ml-10 my-auto text"></i>
                            </div>

                            Desplegable con todas las cuentas encontradas
                            <div class="select-div mt-10" v-if="filteredAccounts.length > 0 && accountsFocused">
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
                                <input v-on:focus="delete errors['position']" data-size="12" v-model="contact.position" type="text">
                            </div>
                            <span v-if="errors.position" class="error">{{ errors.position }}</span>
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


                    <!--dirección y cod. postal-->
                    <div class="half-space">

                        <!--Dirección-->
                        <div v-bind:class="{ wrong: errors.billingInfo.address}" class="form-group ">
                            <label>Dirección</label>
                            <div class="input-group">
                                <input v-on:focus="delete errors['billingInfo']['address']" data-size="12" v-model="contact.billingInfo.address" type="text">
                            </div>
                            <span v-if="errors.billingInfo.address" class="error">{{ errors.billingInfo.address }}</span>
                        </div>


                        <!--Código postal-->
                        <div v-bind:class="{ wrong: errors.billingInfo.postal}" class="form-group ">
                            <label>Código postal</label>
                            <div class="input-group">
                                <input v-on:focus="delete errors['billingInfo']['postal']" data-size="12" v-model="contact.billingInfo.postal" type="text">
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

                            <div class="custom-button ml-auto my-auto mr-20" data-size="medium" data-bg="amarillo" v-on:click="addCustomField"><i class="fas fa-plus"></i></div>
                        </div>

                        <custom-fields-component class="my-20" v-for="(field, fieldInd) in contact.customFields" :field="field" :fieldInd="fieldInd" @delCustomField="delCustomField" type="cont"></custom-fields-component>
                </div>
            </div>



            <!--separador-->
            <div class="separator"></div>

            <!--Botón guardar-->
            <div class="btn-part" v-if="!isCreatingContact">
                <button class="custom-button mr-10" data-size="big" data-bg="rojo" v-on:click.prevent="actionLink('/contacts')">Cancelar</button>
                <button class="custom-button" data-size="big" v-if="!isReadOnly">Guardar <i class="fas fa-chevron-right ml-10"></i></button>
            </div>

            <div class="btn-part" v-else>
                <button class="custom-button" data-size="big" v-if="!isReadOnly"> <i class="fa-solid fa-spinner-third fa-spin mr-5"></i> Espera un momento</button>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    name: "ContactRegisterComponent",
    props:['basicData'],
    data(){
        return{
            contact:{
                name:{
                    first: '',
                    second: ''
                },
                surname:{
                    first: '',
                    second: ''
                },
                DNI: '',
                email: '',
                phone: '',
                companyName:'',
                accounts: [],
                position: '',
                billingInfo:{
                    community: '',
                    province: '',
                    locality: '',
                    address:'',
                    postal: ''
                },
                customFields:[]
            },
            errors:{
                name:{
                    first: '',
                    second:''
                },
                surname:{
                    first: '',
                    second:''
                },
                billingInfo:{}
            },
            searchAddressText:'',
            GEO:{
                communities:'',
                provinces:'',
                localities:'',
                addresses: ''
            },
            searchAccountText:'',
            accounts:[],
            urlImage: '',
            imageFile: '',
            accountsFocused: false,
            isCreatingContact: false
        }
    },
    mounted() {
        //Obtengo las comunidades
        this.getCommunities()

        //Obtengo las cuentas relacionadas
        //this.searchAccounts()
    },
    watch:{
        "basicData.userLogged"(){
            //Obtengo las cuentas relacionadas
            //this.searchAccounts()
        }
    },
    methods:{
        createContact(){


            if (this.isCreatingContact === false){


                this.isCreatingContact = true;

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

                //INFORMACIÓN FACTURACIÓN

                //Dirección

                /*
                //Codigo postal
                if (this.contact.billingInfo.address === ''){
                    this.errors.billingInfo.address = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //Codigo postal
                if (this.contact.billingInfo.postal === ''){
                    this.errors.billingInfo.postal = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }



               */

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
                    data.append('userLogged', JSON.stringify(this.basicData.userLogged));
                    data.append('imageFile', this.imageFile);

                    //Busco los campos personalizados que sean tipo imagen
                    this.contact.customFields.forEach((field, fieldInd) => {
                        if (field.type === 'image')
                            data.append(('customFieldFile' + fieldInd), field.value);

                    })


                    axios.post('/api/contacts',data)
                        .then((res) => {

                            Swal.fire({
                                icon: 'success',
                                title: 'Contacto creado!',
                                text: ' El contacto ha sido creado correctamente',
                                timer: 1500,
                                timerProgressBar: true
                            })

                            //this.$router.push('/contacts')

                            this.isCreatingContact = false;
                        })
                        .catch((err) => {
                            console.log(err)
                            this.isCreatingContact = false;
                        })
                }else{
                    this.isCreatingContact = false;
                }
            }



        },
        getErrorMessage(type){

            let message = '';

            switch (type){
                case 'isEmpty':
                    message = 'Este campo no puede estar vacio'
                    break;

                case 'malformedDNI':
                    message = 'El DNI esta mal formado';
                    break;

                case 'malformedEmail':
                    message = 'El email esta mal formado';
                    break;

                case 'malformedPhone':
                    message = 'El número de telefono esta mal formado';
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
        selectCommunity(event){
            delete this.errors['billingInfo']['community']

            let communityCode = event.target.value

            if (communityCode !== ''){

                //Busco cual es para sacar el nombre
                let community = this.GEO.communities.find((community) => {
                    return community.acom_name === communityCode
                })

                this.contact.billingInfo.community = community.acom_name;
            }else{
                this.contact.billingInfo.community = '';
            }


            //Deselecciono la provincia y la localidad
            this.contact.billingInfo.province = '';

            this.contact.billingInfo.locality = '';

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

                this.contact.billingInfo.province = province.prov_name;
            }else{
                this.contact.billingInfo.province = '';
            }


            this.contact.billingInfo.locality = '';

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

                this.contact.billingInfo.locality = locality.mun_name;
            }else{
                this.contact.billingInfo.locality = '';
            }

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
        async searchAccounts(){

            await axios.post(`/api/contacts/getAccountsRelated/${this.basicData.userLogged._id}`, { userList: this.basicData.userList })
                .then((res) => {
                    this.accounts = res.data.accounts;
                })
                .catch((err) => {
                    console.log(err)
                });
        },
        addAccount(account){
            this.contact.accounts.push(account._id)

            this.searchAccountText = '';
        },
        delAccount(accountInd){

            this.contact.accounts.splice(accountInd, 1);
        },
        selectAddress(address){

            this.contact.billingInfo.addressFirst = address.TVIA + address.NVIAC + ' (' + address.NENTSI50 + ')';

            this.searchAddressText = '';
            this.GEO.addresses = '';
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
        getAccountName(acc){

            return this.accounts.find((account) => {
                return account._id === acc
            })
        },
        addCustomField(){

            let customField = {
                title: '',
                type: 'text',
                fileType: '',
                value: ''
            }

            this.contact.customFields.push(customField);
        },
        delCustomField(fieldInd){
            this.contact.customFields.splice(fieldInd, 1);
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
        }
    }
}
</script>

<style scoped>

</style>
