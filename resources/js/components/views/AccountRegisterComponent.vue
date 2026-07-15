<template>
    <div class="content-white">
        <!--Contenedor padre para distribución-->
        <form v-on:submit.prevent="createAccount" class="form register-pos" v-on:click="isPrincAccFocused = false">

            <!--División de inputs-->
            <div class="top-part">

                <!--Detalles de cuenta-->
                <div class="inputs-part">

                    <div class="mobile-item">

                        <!--Imagen de contacto-->
                        <div v-bind:class="{ wrong: errors.profileImage}" class="form-group profile-image my-20">
                            <div class="d-flex column">
                                <img :src="profileImage ? profileImage : '/assets/account_images/default.jpg'"  alt="Imagen de cuenta">
                                <div class="custom-button mx-auto" data-bg="blanco" data-size="small" v-on:click="openDialog">Cambiar</div>
                            </div>

                            <input id="profileImage" type="file" style="display: none" v-on:change="pickupImage">
                            <span v-if="errors.profileImage" class="error">{{ errors.profileImage }}</span>
                        </div>

                        <div class="text" data-size="20" data-weight="700">Detalles de cuenta</div>

                        <!--Nombre-->
                        <div class="form-group d-flex column">

                            <!--nombre-->
                            <div v-bind:class="{ wrong: errors.name}" class="form-group">
                                <p class="my-auto"><label>Nombre</label> <span data-color="rojo">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['name']" data-size="12" v-model="account.name" type="text">
                                </div>
                                <span v-if="errors.name" class="error">{{ errors.name }}</span>
                            </div>

                            <!--CIF/NIF/Pasaporte-->
                            <div v-bind:class="{ wrong: errors.CIF}" class="form-group">
                                <p class="my-auto"><label>CIF/NIF/Pasaporte</label> <span data-color="rojo">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['CIF']" data-size="12" v-model="account.CIF" type="text">
                                </div>
                                <span v-if="errors.CIF" class="error">{{ errors.CIF }}</span>
                            </div>

                            <!--NIF del representante en caso de ser CIF-->
                            <div v-if="/^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/i.test(account.CIF)" class="form-group w-100">
                                <p class="my-auto"><label>NIF Representante</label></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['NIFRepresentative']" data-size="12" v-model="account.NIFRepresentative" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.CIF" class="error">{{ errors.NIFRepresentative }}</span>
                            </div>

                            <!--Nombre del representante en caso de ser CIF-->
                            <div v-if="/^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/i.test(account.CIF)" class="form-group w-100">
                                <p class="my-auto"><label>Nombre Representante</label></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['NameRepresentative']" data-size="12" v-model="account.NameRepresentative" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.CIF" class="error">{{ errors.NameRepresentative }}</span>
                            </div>
                        </div>
                    </div>


                    <div class="text desktop-item" data-size="20" data-weight="700">Detalles de cuenta</div>


                    <!--Imagen, nombre y CIF escritorio-->
                    <div class="half-space desktop-item">

                        <!--Imagen-->
                        <div v-bind:class="{ wrong: errors.profileImage}" class="form-group profile-image my-20">
                            <div class="d-flex column">
                                <img :src="profileImage ? profileImage : '/assets/account_images/default.jpg'"  alt="Imagen de cuenta">
                                <div class="custom-button mx-auto px-20" data-bg="blanco" data-size="regular" v-on:click="openDialog">Cambiar</div>
                            </div>

                            <input id="profileImage" type="file" style="display: none" v-on:change="pickupImage">
                            <span v-if="errors.profileImage" class="error">{{ errors.profileImage }}</span>
                        </div>


                        <!--Nombre y CIF-->
                        <div class="form-group d-flex column">

                            <!--Nombre-->
                            <div v-bind:class="{ wrong: errors.name}" class="form-group w-100">
                                <p class="my-auto"><label>Nombre</label> <span data-color="rojo">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['name']" data-size="12" v-model="account.name" type="text">
                                </div>
                                <span v-if="errors.name" class="error">{{ errors.name }}</span>
                            </div>

                            <!--CIF/NIF-->
                            <div v-bind:class="{ wrong: errors.CIF}" class="form-group w-100">
                                <p class="my-auto"><label>CIF/NIF/Pasaporte</label> <span data-color="rojo">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['CIF']" data-size="12" v-model="account.CIF" type="text">
                                </div>
                                <span v-if="errors.CIF" class="error">{{ errors.CIF }}</span>
                            </div>

                            <!--NIF del representante en caso de ser CIF-->
                            <div v-if="/^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/i.test(account.CIF)" class="form-group w-100">
                                <p class="my-auto"><label>NIF Representante</label></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['NIFRepresentative']" data-size="12" v-model="account.NIFRepresentative" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.CIF" class="error">{{ errors.NIFRepresentative }}</span>
                            </div>

                            <!--NIF del representante en caso de ser CIF-->
                            <div v-if="/^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/i.test(account.CIF)" class="form-group w-100">
                                <p class="my-auto"><label>Nombre Representante</label></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['NameRepresentative']" data-size="12" v-model="account.NameRepresentative" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.CIF" class="error">{{ errors.NameRepresentative }}</span>
                            </div>
                        </div>

                    </div>


                    <!--Cuenta principal-->
                    <div v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'" v-bind:class="{ wrong: errors.principalAcc}" class="form-group" v-on:click.stop="">
                        <label>Cuenta principal</label>
                        <div class="input-group" v-if="account.principalAcc === ''">
                            <input v-on:click="isPrincAccFocused = true" data-size="12" v-model="searchAccountText" type="text">
                            <i class="fa-regular fa-magnifying-glass ml-10 my-auto text"></i>
                        </div>

                        <!--Desplegable con todas las cuentas encontradas-->
                        <div class="select-div mt-10" v-if="account.principalAcc === '' && filteredAccounts.length > 0 && isPrincAccFocused">
                            <div class="my-5 d-flex pointer d-flex column" v-for="account in filteredAccounts" v-on:click="selectAccount(account)">
                                <p class="text d-flex my-auto" data-size="12"><i class="fa-solid fa-building my-auto mr-10"></i>{{ account.name }}</p>

                                <div class="separator my-5"></div>
                            </div>
                        </div>


                        <!--Cuenta ya seleccionada-->
                        <div v-if="account.principalAcc !== ''" class="d-flex justify-between">

                            <div class="text ml-5 ellipsis" data-size="13">
                                <i class="fa-solid fa-building mr-10"></i> {{ accountSelected.name }}
                            </div>

                            <div class="my-auto pointer" data-color="rojo" v-on:click="account.principalAcc = ''">
                                <i class="fa-solid fa-x"></i>
                            </div>

                        </div>

                        <span v-if="errors.principalAcc" class="error">{{ errors.principalAcc }}</span>
                    </div>


                    <!--Telefono y email-->
                    <div class="half-space">

                        <!--Movil y fijo-->
                        <div :class="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? 'half-space w-45-max' : 'form-group'">

                            <!--movil-->
                            <div v-bind:class="{ wrong: errors.phone}" class="form-group">
                                <p class="my-auto"><label>Movil</label> <span data-color="rojo" v-if="basicData && basicData.userSubdomain && basicData.userSubdomain.settings.accountPhone">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['phone']" data-size="12" v-model="account.phone" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.phone" class="error">{{ errors.phone }}</span>
                            </div>

                            <!--fijo-->
                            <div v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'" v-bind:class="{ wrong: errors.landLinePhone}" class="form-group">
                                <p class="my-auto"><label>Fijo</label></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['landLinePhone']" data-size="12" v-model="account.landLinePhone" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.landLinePhone" class="error">{{ errors.landLinePhone }}</span>
                            </div>
                        </div>

                        <!--email-->
                        <div v-bind:class="{ wrong: errors.email}" class="form-group">
                            <p class="my-auto"><label>Email</label> <span data-color="rojo" v-if="basicData && basicData.userSubdomain && basicData.userSubdomain.settings.accountEmail">*</span></p>
                            <div class="input-group">
                                <input v-on:focus="delete errors['email']" data-size="12" v-model="account.email" type="email">
                            </div>
                            <span v-if="errors.email" class="error">{{ errors.email }}</span>
                        </div>
                    </div>


                    <!--Tipo de cuenta y sector-->
                    <div class="half-space" v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'">
                        <!--Tipo de cuenta-->
                        <custom-select-component @addElement="addSelectType" @delElement="delSelectType" @selectElement="selectElement" class="mt1"
                                                 type="acc"
                                                 title="Tipo de cuenta"
                                                 :options="sectors"
                                                 :addedOptions="selectValues.acc"
                                                 :selected="account.accType"
                                                 :errors="errors"></custom-select-component>

                        <!--Sector-->
                        <custom-select-component @addElement="addSelectType" @delElement="delSelectType" @selectElement="selectElement" class="mt1"
                                                 type="sector"
                                                 title="Sector"
                                                 :options="sectors"
                                                 :addedOptions="selectValues.sector"
                                                 :selected="account.sector"
                                                 :errors="errors"></custom-select-component>
                    </div>


                    <!--Origen y web-->
                    <div class="half-space" v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'">

                        <!--origen-->
                        <!--<custom-select-component @addElement="addSelectType" @delElement="delSelectType" @selectElement="selectElement" class="mt1"
                                                 type="origin"
                                                 title="Origen"
                                                 :options="origins"
                                                 :addedOptions="selectValues.origin"
                                                 :selected="account.origin"
                                                 :errors="errors"></custom-select-component>-->

                        <!--Oportunidad-->
                        <div v-bind:class="{ wrong: errors.opportunity}" class="form-group">
                            <label>Oportunidad</label>
                            <div class="input-group">
                                <select v-model="account.opportunity" :disabled>
                                    <option value="">Selecciona una oportunidad</option>
                                    <option v-if="opportunities" v-for="opp in opportunities" :value="opp._id">{{ opp.name }}</option>
                                </select>
                            </div>
                            <span v-if="errors.opportunity" class="error">{{ errors.opportunity }}</span>
                        </div>


                        <!--web-->
                        <div v-bind:class="{ wrong: errors.website}" class="form-group">
                            <label>Sitio web</label>
                            <div class="input-group">
                                <input v-on:focus="delete errors['website']" data-size="12" v-model="account.website" type="text">
                            </div>
                            <span v-if="errors.website" class="error">{{ errors.website }}</span>
                        </div>
                    </div>


                    <!--observaciones de cuenta-->
                    <div class="form-group">
                        <label>Observaciones</label>
                        <div class="input-group">
                            <textarea class="h-100-px-min w-100-px-min" data-size="12" v-model="account.observations" :disabled="isReadOnly" type="text"></textarea>
                        </div>
                    </div>


                    <!--Asignar cuenta a-->
                    <div class="form-group mt-20">
                        <div class="text desktop-item mb-10" data-size="18" data-weight="700">Propietarios de la cuenta</div>

                        <user-list-component :basicData="basicData" v-model:userListSelected="account.usersIds" :account="true" :editing="true" @toggleSelectUserInOrders="toggleSelectUserInOrders"></user-list-component>

                        <p v-if="basicData.userList.length === 0" class="text opacity-3" data-size="10">No tienes usuarios para asignar</p>
                    </div>
                </div>

                <!--Separator vertical-->
                <div class="separator" data-position="vertical"></div>


                <!--Dirección de facturación-->
                <div class="inputs-part">
                    <template v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'">

                        <div class="text" data-size="20" data-weight="700">Dirección de facturación</div>

                        <!--Comunidad-->
                        <div v-bind:class="{ wrong: errors.billingInfo.community}" class="form-group">
                            <p class="my-auto"><label>Comunidad</label></p>
                            <div class="input-group">
                                <input v-on:focus="delete errors['billingInfo']['community']" data-size="12" v-model="account.billingInfo.community" type="text">
                            </div>
                            <span v-if="errors.billingInfo.community" class="error">{{ errors.billingInfo.community }}</span>
                        </div>


                        <!--Provincia y localidad-->
                        <div class="half-space">

                            <!--Provincia-->
                            <div v-bind:class="{ wrong: errors.billingInfo.province}" class="form-group">
                                <p class="my-auto"><label>Provincia</label> <span data-color="rojo" v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.accountProvince">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['billingInfo']['province']" data-size="12" v-model="account.billingInfo.province" type="text">
                                </div>
                                <span v-if="errors.billingInfo.province" class="error">{{ errors.billingInfo.province }}</span>
                            </div>

                            <!--Localidad-->
                            <div v-bind:class="{ wrong: errors.billingInfo.locality}" class="form-group">
                                <p class="my-auto"><label>Localidad</label> <span data-color="rojo" v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.accountLocality">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['billingInfo']['locality']" data-size="12" v-model="account.billingInfo.locality" type="text">
                                </div>
                                <span v-if="errors.billingInfo.locality" class="error">{{ errors.billingInfo.locality }}</span>
                            </div>
                        </div>

                        <!--Dirección y cod zip-->
                        <div class="half-space">
                            <!--Dirección-->
                            <div v-bind:class="{ wrong: errors.billingInfo.address}" class="form-group ">
                                <p class="my-auto"><label>Dirección</label> <span data-color="rojo" v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.accountAddress">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['billingInfo']['address']" data-size="12" v-model="account.billingInfo.address" type="text">
                                </div>
                                <span v-if="errors.billingInfo.address" class="error">{{ errors.billingInfo.address }}</span>
                            </div>

                            <!--cod zip-->
                            <div v-bind:class="{ wrong: errors.billingInfo.zipCode}" class="form-group ">
                                <p class="my-auto"><label>Código postal</label> <span data-color="rojo"v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.accountPostal">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['billingInfo']['zipCode']" data-size="12" v-model="account.billingInfo.zipCode" type="text">
                                </div>
                                <span v-if="errors.billingInfo.zipCode" class="error">{{ errors.billingInfo.zipCode }}</span>
                            </div>

                        </div>


                        <div class="separator mt-30"></div>
                    </template>


                    <!--Campos personalizados-->

                    <!--Header-->
                    <template v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'">

                        <div class="d-flex">

                            <div class="text desktop-item" data-size="20" data-weight="700">Campos personalizados</div>

                            <div class="text mobile-item my-20" data-size="18" data-weight="700">Campos personalizados</div>

                            <div class="custom-button ml-auto my-auto mr-20" data-size="medium" data-bg="amarillo" v-on:click="addCustomField"><i class="fas fa-plus"></i></div>
                        </div>

                        <custom-fields-component class="my-10" v-for="(field, fieldInd) in account.customFields" :field="field" :fieldInd="fieldInd" @delCustomField="delCustomField" @addCustomFields="addCustomFields" type="acc"></custom-fields-component>

                        <p v-if="account.customFields.length === 0" data-size="11" class="text my-10 opacity-5">No hay ningún campo personalizado añadido</p>

                        <div class="separator my-20"></div>
                    </template>


                    <!--Pedidos-->

                        <!--Header-->
                        <div class="d-flex mt-30">

                            <div class="text desktop-item" data-size="20" data-weight="700">Contratos</div>

                            <div class="text mobile-item my-20" data-size="18" data-weight="700">Contratos</div>

                            <div class="custom-button ml-auto my-auto mr-20" v-if="canManage('contracts.create')" data-size="medium" data-bg="amarillo" v-on:click="addOrder"><i class="fas fa-plus"></i></div>
                        </div>


                        <!--Barra de busqueda-->
                        <div class="search-bar my-15">

                            <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                            <input type="text" placeholder="Buscar un contrato..." v-model="searchOrderText">
                        </div>


                        <!--Listado pedidos-->
                        <order-item-component v-for="(order, orderInd) in filteredOrders" :basicData="basicData" :order="order" :orderInd="orderInd" @selectOrder="selectOrder" @delOrder="delOrder" @duplicateOrder="duplicateOrder" :canDelete="canDelete('accounts.delete')"></order-item-component>

                        <p v-if="account.orders.length === 0" data-size="11" class="text my-10 opacity-5">No hay ningún contrato añadido</p>


                        <!--Info superpuesta pedido seleccionado-->
                        <div class="form">
                            <order-details-item-component class="my-20" v-if="selectedOrder" :order="selectedOrder" :account="account" :selectValues="selectValues" :basicData="basicData" canDelete="true" canCreate="true" fromCreate="true" @addElement="addSelectType" @delElement="delSelectType" @selectElement="selectElement" @closeWindow="closeWindow" @createOrder="createOrder" @deleteOrder="deleteOrder"></order-details-item-component>
                        </div>
                </div>
            </div>


            <!--separador-->
            <div class="separator"></div>

            <!--Botón guardar-->
            <div class="btn-part" v-if="!isCreatingAcc">
                <button class="custom-button mr-10" data-size="big" data-bg="rojo" v-on:click.prevent="actionLink('/accounts')">Cancelar</button>
                <button class="custom-button" v-if="!isReadOnly" data-size="big">Guardar <i class="fas fa-chevron-right ml-10"></i></button>
            </div>

            <div class="btn-part" v-else>
                <button class="custom-button" data-size="big"> <i class="fa-solid fa-spinner-third fa-spin mr-5"></i> Espere un momento</button>
            </div>
        </form>
    </div>
</template>

<script>

export default {
    name: "AccountRegisterComponent",
    props:['basicData'],
    data(){
        return{
            account:{
                name:'',
                website:'',
                principalAcc:'',
                accType:'',
                sector:'',
                CIF: '',
                //origin: '',
                opportunity: '',
                annualIncome:'',
                calification: '',
                phone: '',
                landLinePhone: '',
                email: '',
                observations: '',
                billingInfo:{
                    address: '',
                    community: '',
                    province: '',
                    locality: '',
                    zipCode: ''
                },
                customFields:[],
                owner: '',
                usersIds:[],
                orders:[]
            },
            contacts: '',
            isPrincAccFocused: false,
            errors:{
                billingInfo:[]
            },
            origins : [],
            accTypes:[],
            sectors:[],
            searchAccountText:'',
            accounts:[],
            opportunities: [],
            GEO:{
                communities:'',
                provinces:'',
                localities:'',
                addresses: ''
            },
            searchAddressText: '',
            selectValues: '',
            isSeeingOrder: false,
            selectedOrder: '',
            selectedOrderInd: '',
            searchOrderText: '',
            urlImage: '',
            imageFile: '',
            isCreatingAcc: false
        }
    },
    mounted() {
        //Obtengo las comunidades
        this.getCommunities()

        //Saco los valores para los selects
        this.fetchSelectValues()

        //Obtengo las cuentas relacionadas
        if (this.basicData.userLogged && this.accounts.length === 0){
            this.searchAccounts()
        }

        //Saco los contactos
        if (this.basicData.userLogged && this.opportunities.length === 0)
            this.fetchUserOpps()


        //Si está la cookie de cuenta para crear oportunidad lo establezco y la borro
        if (localStorage.getItem('temporalyCreateAcc'))
            this.setAccFromOpp()



        /*if (this.basicData.userLogged && !this.account.owner){

            this.basicData.userList.push(this.basicData.userLogged)

            this.account.owner = this.basicData.userLogged._id
        }*/
    },
    watch:{
        "basicData.userLogged"(){
            //Obtengo las cuentas relacionadas
            this.searchAccounts()

            if (this.opportunities.length === 0)
                this.fetchUserOpps()

            /*if (!this.account.owner){

                this.basicData.userList.push(this.basicData.userLogged)

                this.account.owner = this.basicData.userLogged._id
            }*/
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

        normalizeControlField(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/[\s.-]/g, '');
        },

        validateSpanishDocumentIfApplies(value) {
            const doc = this.normalizeControlField(value);

            if (!doc) {
                return {
                    valid: true,
                    skipped: true,
                    message: null
                };
            }

            const nifLetters = 'TRWAGMYFPDXBNJZSQVHLCKE';

            // DNI / NIF
            if (/^\d{8}[A-Z]$/.test(doc)) {
                const number = parseInt(doc.substring(0, 8), 10);
                const expectedLetter = nifLetters[number % 23];

                return {
                    valid: doc[8] === expectedLetter,
                    skipped: false,
                    message: doc[8] === expectedLetter
                        ? null
                        : 'DNI/CIF/Pasaporte no válido'
                };
            }

            // NIE
            if (/^[XYZ]\d{7}[A-Z]$/.test(doc)) {
                const prefixes = {
                    X: '0',
                    Y: '1',
                    Z: '2'
                };

                const number = parseInt(prefixes[doc[0]] + doc.substring(1, 8), 10);
                const expectedLetter = nifLetters[number % 23];

                return {
                    valid: doc[8] === expectedLetter,
                    skipped: false,
                    message: doc[8] === expectedLetter
                        ? null
                        : 'DNI/CIF/Pasaporte no válido'
                };
            }

            // CIF
            if (/^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/.test(doc)) {
                const controlLetters = 'JABCDEFGHI';
                const digits = doc.substring(1, 8);
                const control = doc[8];

                let evenSum = 0;
                let oddSum = 0;

                for (let i = 0; i < digits.length; i++) {
                    const digit = parseInt(digits[i], 10);

                    if ((i + 1) % 2 === 0) {
                        evenSum += digit;
                    } else {
                        const doubled = digit * 2;
                        oddSum += Math.floor(doubled / 10) + (doubled % 10);
                    }
                }

                const total = evenSum + oddSum;
                const controlDigit = (10 - (total % 10)) % 10;
                const controlLetter = controlLetters[controlDigit];

                const mustBeLetter = /^[KPQS]$/.test(doc[0]);
                const mustBeDigit = /^[ABEH]$/.test(doc[0]);

                let isValid = false;

                if (mustBeLetter) {
                    isValid = control === controlLetter;
                } else if (mustBeDigit) {
                    isValid = control === String(controlDigit);
                } else {
                    isValid = control === String(controlDigit) || control === controlLetter;
                }

                return {
                    valid: isValid,
                    skipped: false,
                    message: isValid ? null : 'DNI/CIF/Pasaporte no válido'
                };
            }

            // Posible pasaporte/documento extranjero.
            // No permitimos texto libre tipo nombres.
            if (!/^[A-Z0-9]{5,20}$/.test(doc)) {
                return {
                    valid: false,
                    skipped: false,
                    message: 'DNI/CIF/Pasaporte no válido'
                };
            }

            if (!/\d/.test(doc)) {
                return {
                    valid: false,
                    skipped: false,
                    message: 'DNI/CIF/Pasaporte no válido'
                };
            }

            if (
                /^\d{8}$/.test(doc) ||
                /^[XYZ]\d{7}$/.test(doc) ||
                /^[ABCDEFGHJKLMNPQRSUVW]\d{7}$/.test(doc)
            ) {
                return {
                    valid: false,
                    skipped: false,
                    message: 'DNI/CIF/Pasaporte no válido'
                };
            }

            return {
                valid: true,
                skipped: true,
                message: null
            };
        },

        async createAccount(){

            if (this.isCreatingAcc === false){

                this.isCreatingAcc = true

                //reseteo los errores
                this.errors = {
                    billingInfo: []
                }

                let hasErrors = false;

                //Validaciones

                //INFORMACIÓN BÁSICA

                //Nombre
                if (this.account.name === ''){
                    this.errors.name = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }


                //CIF/NIF
                if (this.account.CIF === ''){
                    this.errors.CIF = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                const documentValidation = this.validateSpanishDocumentIfApplies(this.account.CIF);

                if (!documentValidation.valid) {
                    this.errors.CIF = 'DNI/CIF/Pasaporte no válido';
                    hasErrors = true;
                }

                /*let regexNifCif = /^(?:[0-9]{8}[A-Z]|[XYZ][0-9]{7}[A-Z]|[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]|[A-Z][0-9]{7}[A-Z])$/;

                if (this.account.CIF && !regexNifCif.test(this.account.CIF)){
                    this.errors.CIF = 'CIF/NIF no válido';
                    hasErrors = true;
                }*/

                //Telefono
                if (this.account.phone === '' && this.basicData.userSubdomain.settings.accountPhone){
                    this.errors.phone = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //Deshabilitada comprobacion por numeros extranjeros de mas de 9 numeros
                // if (this.account.phone && this.account.phone.length !== 9){
                //     this.errors.phone = this.getErrorMessage('malformedPhone');
                //     hasErrors = true;
                // }

                //Telefono fijo
                if (this.account.landLinePhone && this.account.landLinePhone.length !== 9){
                    this.errors.landLinePhone = this.getErrorMessage('malformedPhone');
                    hasErrors = true;
                }

                //Ingresos anuales
                if (this.account.annualIncome && isNaN(this.account.annualIncome)){
                    this.errors.annualIncome = this.getErrorMessage('onlyNumbers');
                    hasErrors = true;
                }


                //Correo
                if (this.account.email === '' && this.basicData.userSubdomain.settings.accountEmail){
                    this.errors.email = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }


                //email
                let regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

                if ( this.account.email && !regexEmail.test(this.account.email)){
                    this.errors.email = this.getErrorMessage('malformedEmail');
                    hasErrors = true;
                }



                //INFORMACIÓN FACTURACIÓN

                //Comunidad
                /*if (!this.account.billingInfo.community){
                    this.errors.billingInfo.community = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }*/

                //Provincia
                if (!this.account.billingInfo.province && this.basicData.userSubdomain.settings.accountProvince){
                    this.errors.billingInfo.province = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //Localidad
                if (!this.account.billingInfo.locality && this.basicData.userSubdomain.settings.accountLocality){
                    this.errors.billingInfo.locality = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //Dirección
                if (!this.account.billingInfo.address&& this.basicData.userSubdomain.settings.accountAddress){
                    this.errors.billingInfo.address = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }


                //Codigo postal
                if (!this.account.billingInfo.zipCode && this.basicData.userSubdomain.settings.accountPostal){
                    this.errors.billingInfo.zipCode = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                if (this.account.billingInfo.zipCode && this.account.billingInfo.zipCode.length !== 5){
                    this.errors.billingInfo.zipCode = 'El zip tiene que tener 5 digitos';
                    hasErrors = true;
                }

                if (this.account.billingInfo.zipCode && isNaN(this.account.billingInfo.zipCode)){
                    this.errors.billingInfo.zipCode = this.getErrorMessage('onlyNumbers');
                    hasErrors = true;
                }




                //Campos personalizados
                if (this.account.customFields.length > 0){

                    this.account.customFields.forEach((customField) => {

                        if (customField.title === ''){
                            customField.errors = this.getErrorMessage('isEmpty');
                            hasErrors = true;
                        }
                    })
                }


                //Pedidos
                if (this.account.orders.length > 0){
                    this.account.orders.forEach((order, orderInd) => {

                        order.errors = {
                            commissions: {subdomain: null, breakdown: []},
                            decommissions: {subdomain: null, breakdown: []},
                        }

                        if (order.newStatus.code !== 'bo' && order.newStatus.code !== 'an' && order.newStatus.code !== 's') {

                            //Título
                            if (!order.name){
                                order.errors.name = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            //Dirección de suministro
                            if (!order.direc && this.basicData.userSubdomain.settings.orderAddress){
                                order.errors.direc = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            if ((order.newStatus.code === 'a' || order.newStatus.code === 'b') && !order.activationDate){
                                order.errors.activationDate = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            if (order.newStatus.code === 'b' && !order.lowDate){
                                order.errors.lowDate = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }


                            //Poblacion
                            if (!order.town && this.basicData.userSubdomain.settings.orderTown){
                                order.errors.town = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }


                            //Provincia
                            if (!order.province && this.basicData.userSubdomain.settings.orderProvince){
                                order.errors.province = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            //Código postal
                            if (!order.zip && this.basicData.userSubdomain.settings.orderPostal){
                                order.errors.zip = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            if (order.zip && order.zip.length !== 5){
                                order.errors.zip = 'El zip tiene que tener 5 digitos';
                                hasErrors = true;
                            }

                            if (order.zip && isNaN(order.zip)){
                                order.errors.zip = this.getErrorMessage('onlyNumbers');
                                hasErrors = true;
                            }


                            //Tipo de producto
                            if (!order.productType){
                                order.errors.productType = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }


                            //Producto ( si es luz o gas se selecciona toda la ramificación de abajo y sino se puede añadir)
                            if (!order.product){
                                order.errors.product = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }


                            //SI ES CONTRATO DE LUZ O DE GAS

                            //Tarifa
                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && !order.fee){
                                order.errors.fee = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            //Comercializadora
                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && !order.marketer){
                                order.errors.marketer = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            //IBAN
                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && order.newStatus.code !== 'an' && !order.IBAN  && this.basicData.userSubdomain.settings.orderIBAN){
                                order.errors.IBAN = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            // Limpiar espacios
                            const ibanClean = (order.IBAN || '').replace(/\s/g, '').toUpperCase();

                            //  permitir ES0000
                            if (ibanClean === 'ES0000') {
                                delete order.errors.IBAN;
                            }
                            //  bloquear ES0000 con más cosas
                            else if (/^ES0{4}/.test(ibanClean)) {
                                order.errors.IBAN = 'IBAN no válido';
                                hasErrors = true;
                            }
                            //  validación normal
                            else if (order.IBAN && order.IBAN.length !== 29){
                                order.errors.IBAN = 'IBAN no válido';
                                hasErrors = true
                            }

                            //CUPS
                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && !order.CUPS){
                                order.errors.CUPS = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            if (order.CUPS && order.CUPS.length !== 20){
                                order.errors.CUPS = 'CUPS no válido';
                                hasErrors = true
                            }



                            //Si las comisiones son normales, no carterizadas
                            if (!order.installmentCommissions){
                                // Comisiones
                                if (this.validateCommissionBlock(order.commissions, order.errors.commissions)) {
                                    hasErrors = true;
                                }

                                // Comisiones de dual electricidad
                                if (order.commissions?.electricity) {
                                    order.errors.commissions.electricity = {subdomain: null, breakdown: []};

                                    if (this.validateCommissionBlock(order.commissions.electricity, order.errors.commissions.electricity)) {
                                        hasErrors = true;
                                    }
                                }

                                // Comisiones de dual gas
                                if (order.commissions?.gas) {
                                    order.errors.commissions.gas = {subdomain: null, breakdown: []};

                                    if (this.validateCommissionBlock(order.commissions.gas, order.errors.commissions.gas)) {
                                        hasErrors = true;
                                    }
                                }

                            }
                            else{ //Si son carterizadas
                                order.errors.installmentCommissions = [];

                                order.installmentCommissions.forEach((installment) => {
                                    const installmentErrors = { date: null, subdomain: null, breakdown: {} };

                                    // Fecha
                                    if (!installment.date || installment.date === '') {
                                        installmentErrors.date = 'La fecha debe estar rellena';
                                        hasErrors = true;
                                    }

                                    // Comisiones
                                    if (this.validateCommissionBlock(installment.commissions, installmentErrors)) {
                                        hasErrors = true;
                                    }

                                    order.errors.installmentCommissions.push(installmentErrors);
                                });
                            }

                            //Decomisiones (solo si es uno de los estados)
                            const decommissionRequiredStatuses = [
                                'b',
                                'pendiente_de_retrocomisin',
                            ];

                            const isDecommissionRequired = decommissionRequiredStatuses.includes(order.newStatus?.code);

                            //Decomisiones
                            if (this.validateCommissionBlock(order.decommissions, order.errors.decommissions,isDecommissionRequired)) {
                                hasErrors = true;
                            }

                            // Decomisiones de dual electricidad
                            if (order.decommissions?.electricity) {
                                order.errors.decommissions.electricity = {subdomain: null, breakdown: []};

                                if (this.validateCommissionBlock(order.decommissions.electricity, order.errors.decommissions.electricity,isDecommissionRequired)) {
                                    hasErrors = true;
                                }
                            }

                            // Decomisiones de dual gas
                            if (order.decommissions?.gas) {
                                order.errors.decommissions.gas = {subdomain: null, breakdown: []};

                                if (this.validateCommissionBlock(order.decommissions.gas, order.errors.decommissions.gas,isDecommissionRequired)) {
                                    hasErrors = true;
                                }
                            }

                            //si tiene estado de verificacion cambio de potencia y no tiene puestos
                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && order.verifications && order.verifications.includes('pc') && !order.currentPotencyVerification ){
                                order.errors.currentPotencyVerification = 'No puede estar vacía';
                                hasErrors = true
                            }

                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && order.verifications && order.verifications.includes('pc') && !!order.currentPotencyVerification && isNaN(order.currentPotencyVerification)){
                                order.errors.currentPotencyVerification = 'Debe ser numerico';
                                hasErrors = true
                            }

                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && order.verifications && order.verifications.includes('pc') && !order.requestedPotencyVerification ){
                                order.errors.requestedPotencyVerification = 'No puede estar vacía';
                                hasErrors = true
                            }

                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && order.verifications && order.verifications.includes('pc') && !!order.requestedPotencyVerification && isNaN(order.requestedPotencyVerification)){
                                order.errors.requestedPotencyVerification = 'Debe ser numerico';
                                hasErrors = true
                            }


                            //Verificaciones si es alta nueva
                            if (order.verifications && order.verifications.includes('nw')){

                                if (order.productType === 'cl') {

                                    //Si tarifa es 2.0TD
                                    if (order.fee === 'Tarifa 2.0TD'){

                                        //miro p1 y p2
                                        for (let i = 1; i <= 2; i++){

                                            if (!!order.newRegistrationPeriods && order.newRegistrationPeriods['p' + i] === ''){
                                                order.errors['periodVerification' + i] = 'No puede estar vacía';
                                                hasErrors = true
                                            }

                                            if (!!order.newRegistrationPeriods && isNaN(order.newRegistrationPeriods['p' + i])){
                                                order.errors['periodVerification' + i] = 'Debe ser numerico';
                                                hasErrors = true
                                            }
                                        }

                                    }else{

                                        //miro de p1 a p6
                                        for (let i = 1; i <= 6; i++){

                                            if (!!order.newRegistrationPeriods && order.newRegistrationPeriods['p' + i] === ''){
                                                order.errors['periodVerification' + i] = 'No puede estar vacía';
                                                hasErrors = true
                                            }

                                            if (!!order.newRegistrationPeriods && isNaN(order.newRegistrationPeriods['p' + i])){
                                                order.errors['periodVerification' + i] = 'Debe ser numerico';
                                                hasErrors = true
                                            }
                                        }

                                    }

                                }else if (order.productType === 'cg'){

                                    //Precio fijo
                                    if (!order.newRegistrationPrices.fixedPrice || order.newRegistrationPrices.fixedPrice === ''){
                                        order.errors.fixedPrice = 'No puede estar vacía';
                                        hasErrors = true
                                    }

                                    if (!!order.newRegistrationPrices.fixedPrice && isNaN(order.newRegistrationPrices.fixedPrice)){
                                        order.errors.fixedPrice = 'Debe ser numerico';
                                        hasErrors = true
                                    }



                                    //Precio variable
                                    if (!order.newRegistrationPrices.variablePrice || order.newRegistrationPrices.variablePrice === ''){
                                        order.errors.variablePrice = 'No puede estar vacía';
                                        hasErrors = true
                                    }

                                    if (!!order.variablePrice && isNaN(order.variablePrice)){
                                        order.errors.variablePrice = 'Debe ser numerico';
                                        hasErrors = true
                                    }

                                }


                            }


                            //Si el contrato es dual
                            if (order.productType === 'cd'){

                                //Comercializadora
                                if (!order.marketer){
                                    order.errors.marketer = this.getErrorMessage('isEmpty');
                                    hasErrors = true
                                }

                                //Tarifa de gas
                                if (!order.feeSecondary){
                                    order.errors.feeSecondary = this.getErrorMessage('isEmpty');
                                    hasErrors = true
                                }

                                //Producto de gas
                                if (!order.productSecondary){
                                    order.errors.productSecondary = this.getErrorMessage('isEmpty');
                                    hasErrors = true
                                }

                                //CUPS de gas
                                if (!order.CUPSSecondary){
                                    order.errors.CUPSSecondary = this.getErrorMessage('isEmpty');
                                    hasErrors = true
                                }

                                if (order.CUPSSecondary && order.CUPSSecondary.length !== 20){
                                    order.errors.CUPSSecondary = 'CUPS no válido';
                                    hasErrors = true
                                }
                            }

                        }

                        //Si no hay errores restablezco el objeto
                        if(!hasErrors){
                            order.errors = {};
                        }


                    })
                }


                //Si no hay errores guardo, sino los muestro
                if (!hasErrors){

                    this.account.orders.forEach((order) => {

                    order.docs?.forEach((doc) => {
                        if (doc.title === '' && doc.defaultTitle) {
                            doc.title = doc.defaultTitle;
                        }
                    });

                    // Actualizar fecha
                    order.lastUpdate = moment().format('YYYY-MM-DD HH:mm:ss');
                });

                    let data = new FormData();




                    data.append('account', JSON.stringify(this.account));
                    data.append('userLogged', JSON.stringify(this.basicData.userLogged));
                    data.append('imageFile', this.imageFile);
                    data.append('enterprise', JSON.stringify(this.basicData.enterprise));
                    data.append('userSubdomain', JSON.stringify(this.basicData.userSubdomain));
                    data.append('colors', JSON.stringify(this.getAllColorVariables()));

                    //Busco los campos personalizados que sean tipo imagen
                    this.account.customFields.forEach((field, fieldInd) => {

                        if (field.type === 'image'){
                            data.append(('customFieldFile' + fieldInd), field.value);
                        }

                    })



                    axios.post('/api/accounts', data)
                    .then(async (res) => {

                        console.log('RESPUESTA BACKEND:', res.data);

                        const createdAccount =
                        res.data?.account ||
                        res.data?.data ||
                        res.data;



                        let docsUploadError = false;


                        if (Array.isArray(this.account?.orders) && this.account.orders.length > 0) {


                        for (const localOrder of this.account.orders) {

                            const docsFiles = [];



                            if (Array.isArray(localOrder?.docs) && localOrder.docs.length > 0) {
                            localOrder.docs.forEach((doc, index) => {

                                if (doc?.fileValue instanceof File) {
                                docsFiles.push(doc.fileValue);
                                }
                            });
                            }


                            if (docsFiles.length === 0) continue;

                            // 🔎 Buscamos el _id REAL del order creado
                            // Normalizamos por si viene como {$oid: "..."}
                            const backendOrder = createdAccount?.orders?.find(o =>
                            o.clientTmpId && o.clientTmpId === localOrder.clientTmpId
                            );

                            const realOrderId =
                            backendOrder?._id?.$oid ||
                            backendOrder?._id ||
                            null;


                            if (!realOrderId) {
                            console.warn('No se pudo encontrar _id real para este order');
                            docsUploadError = true;
                            continue;
                            }

                            const fd = new FormData();

                            localOrder.docs.forEach(doc => {
                                if (doc?.fileValue instanceof File) {
                                    fd.append('files[]', doc.fileValue);
                                    fd.append('titles[]', doc.title || '');
                                }
                            });

                            try {
                            await axios.post(`/api/orders/${realOrderId}/upload-document`, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
                            } catch (e) {
                                docsUploadError = true;
                            }
                        }
                        }

                        this.isCreatingAcc = false;

                        if (docsUploadError) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Aviso',
                            text: 'La cuenta se ha guardado, pero algunos documentos no se han adjuntado correctamente.'
                        });
                        } else {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Cuenta creada!',
                            text: 'La cuenta se ha creado correctamente',
                            timerProgressBar: true,
                            timer: 1500
                        });
                        }

                        this.$router.push('/accounts');

                    })
                    .catch((err) => {

                        const data = err.response?.data || {};

                        if (data.cupsError) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'CUPS ya existente',
                                text: 'No será posible crear este contrato porque el CUPS ya está asociado a otro contrato.',
                                confirmButtonText: 'Aceptar'
                            });

                            this.isCreatingAcc = false;
                            return;
                        }

                        if (data?.cifError) {
                            this.errors.CIF = err.response.data.cifError;

                            Swal.fire({
                                icon: 'warning',
                                title: `¡${this.errors.CIF}!`,
                                confirmButtonText: 'Aceptar'
                            });
                        }

                        if (data?.error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Datos no válidos',
                                text: data.error,
                                confirmButtonText: 'Entendido'
                            });
                        }

                        this.isCreatingAcc = false;
                    });

                    } else {

                        Swal.fire({
                            icon: 'error',
                            title: 'Comprueba la cuenta',
                            html: 'Hay errores en el formulario, revisa bien la <strong style="color: red;">cuenta</strong> y los <strong style="color: red;">pedidos</strong> relacionados con la cuenta'
                        });

                        this.isCreatingAcc = false;
                    }

            }
        },
        validateCommissionValue(value, required = false) {
            if (value === null || value === undefined || value === '') {
                //Solo para decomisiones
                if (required) {
                    return {
                        isValid: false,
                        value: null,
                        error: 'Establece una decomisión para la baja',
                    };
                }

                return {
                    isValid: true,
                    value: null,
                    error: null,
                };
            }

            const normalizedValue = value.toString().replace(',', '.').trim();
            const parsedValue = Number(normalizedValue);

            if (Number.isNaN(parsedValue) || !Number.isFinite(parsedValue)) {
                return {
                    isValid: false,
                    value: null,
                    error: `La ${required ? 'de' : ''}comisión debe ser de tipo numérico`,
                };
            }

            if (parsedValue < 0) {
                return {
                    isValid: false,
                    value: null,
                    error: `La ${required ? 'de' : ''}comisión debe ser un número positivo`,
                };
            }

            return {
                isValid: true,
                value: parsedValue,
                error: null,
            };
        },
        validateCommissionBlock(commissionBlock, errorsBlock, required = false) {
            let blockHasErrors = false;

            if (!commissionBlock) {
                return false;
            }

            errorsBlock.subdomain = null;
            errorsBlock.breakdown = {};

            const subdomainValidation = this.validateCommissionValue(commissionBlock.subdomain, required);

            if (!subdomainValidation.isValid) {
                errorsBlock.subdomain = subdomainValidation.error;
                blockHasErrors = true;
            } else {
                commissionBlock.subdomain = subdomainValidation.value;
            }

            if (commissionBlock.breakdown?.length) {
                commissionBlock.breakdown.forEach((item, index) => {
                    const commissionValidation = this.validateCommissionValue(item.commission, required);

                    if (!commissionValidation.isValid) {
                        errorsBlock.breakdown[item.id] = commissionValidation.error;
                        blockHasErrors = true;
                        return;
                    }

                    item.commission = commissionValidation.value;
                });
            }

            return blockHasErrors;
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
        async fetchUserOpps(){

            await axios.post(`/api/opportunities/indexWithoutPagination/${this.basicData.userLogged._id}`, { userList: JSON.stringify(this.basicData.userList) })
                .then((res) => {
                    this.opportunities = res.data.opportunities;
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        selectAccount(account){
            this.account.principalAcc = account._id

            this.searchAccountText = '';
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

            await axios.get(`/api/GEO/getProvinces/${this.account.billingInfo.community}`)
                .then((res) => {
                    this.GEO.provinces = res.data.provinces;
                })
                .catch((err) => {
                    console.log(err)
                });
        },
        async getLocalities(){

            await axios.get(`/api/GEO/getLocalities/${this.account.billingInfo.province}`)
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

                this.account.billingInfo.community = community.acom_name;
            }else{
                this.account.billingInfo.community = '';
            }


            //Deselecciono la provincia y la localidad
            this.account.billingInfo.province = '';

            this.account.billingInfo.locality = '';

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

                this.account.billingInfo.province = province.prov_name;
            }else{
                this.account.billingInfo.province = '';
            }


            this.account.billingInfo.locality = '';

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

                this.account.billingInfo.locality = locality.mun_name;
            }else{
                this.account.billingInfo.locality = '';
            }
        },
        async searchAddress(){
            await axios.get(`https://apiv1.geoapi.es/qcalles?QUERY=${this.searchAddressText}&type=JSON&key=7bf1d1b4d597394430abc701411697c9a95aceca96efccb0e81cbd2451d3c6ce`)
                .then((res) => {
                    this.GEO.addresses = res.data.data;
                })
                .catch((err) => {
                    console.log(err)
                });
        },
        selectAddress(address){

            this.account.billingInfo.addressFirst = address.TVIA + ' ' + address.NVIAC + ' (' + address.NENTSI50 + ')';

            this.searchAddressText = '';
            this.GEO.addresses = '';
        },

        async checkCIF(){

            await axios.get('/api/accounts/checkCIF', {
                params: { account: this.account }
            })
                .then((res) => {

                    let acc = res.data.account

                    if (acc){

                        Swal.fire({
                            icon: 'warning',
                            title: '¡Ya existe una cuenta con este CIF!',
                            cancelButtonText: 'Volver',
                            showCancelButton: true,
                            confirmButtonText: 'Ver cuenta'
                        }).then((res) => {
                            if (res.isConfirmed){

                                this.$router.push('/accounts/' + acc._id)
                            }

                        })
                    }
                })
                .catch((err) => {
                    console.log(err)
                })

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

                case 'onlyNumbers':
                    message = 'Solo puede contener dígitos';
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
                fileType: '',
                value: ''
            }

            this.account.customFields.push(customField);
        },
        delCustomField(fieldInd){
            this.account.customFields.splice(fieldInd, 1);
        },
        addCustomFields(files){
            //Creo un campo personalizado más por cada archivo
            files.forEach((file) => {

                let customField = {
                    title: '',
                    type: 'image',
                    fileType: file['type'].split("/")[0],
                    value: file
                }

                this.account.customFields.push(customField);
            })
        },
        addOrder(){
            const isFidelitySubdomain = this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2';

            let customField = {
                clientTmpId: 'tmp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),

                name: isFidelitySubdomain ? (this.account.name || '') : '',
                direc: '',
                zip: '',
                town: '',
                province: '',
                source: '',
                observations: '',
                incidence: '',
                transferDate: '',
                processingDate: '',
                activationDate: '',
                lowDate: '',
                liquidationStatus: 'nl',
                productType: '',
                marketer: '',
                fee: '',
                product: '',
                commissions: {
                    subdomain: null,
                    breakdown: []
                },
                CUPS: '',
                consumption: '',
                IBAN: '',
                accountCIF: isFidelitySubdomain ? (this.account.CIF || '') : '',
                docs: [],
                statuses: [],
                newStatus: {
                    code:
                        this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2' &&
                        !this.canManage('contracts.processor')
                            ? 'bo'
                            : 'p',
                    date: '',
                    creator: this.basicData.userLogged._id
                },
                usersIds: (
                    Array.isArray(this.account.usersIds) && this.account.usersIds.length > 0
                        ? (this.basicData.userLogged._id === '683d658761231bd1080b4802'
                            ? [...this.account.usersIds]
                            : [this.account.usersIds[0]])
                        : []
                ),
                errors: {}
            }

        this.account.orders.push(customField);

            // Abro el contenedor con el pedido
        this.selectedOrder = this.account.orders[this.account.orders.length - 1]
        this.selectedOrderInd = this.account.orders.length - 1;
        },
                selectOrder(ind){
        this.selectedOrder = this.account.orders[ind]
        this.selectedOrderInd = ind;
        },
        createOrder(orderModified){

            const validate = orderModified?.validate ?? false;

            if (orderModified?.order) {
                orderModified = orderModified.order;
            }

            this.account.orders[this.selectedOrderInd] = orderModified;

            // Reset selección
            this.selectedOrder = '';
            this.selectedOrderInd = '';

            // Ejecutar guardado
            this.createAccount(validate);
        },
        deleteOrder() {
            this.account.orders.splice(this.selectedOrderInd, 1)

            this.selectedOrder = '';
            this.selectedOrderInd = '';
        },
        delOrder(ind){

            Swal.fire({
                icon: 'warning',
                title: 'Estás seguro?',
                text: 'Si guardas los cambios de la cuenta no se podrá recuperar el pedido',
                confirmButtonText: 'Sí',
                showCancelButton: true,
                cancelButtonText: 'No'
            }).then((res) => {
                if (res.isConfirmed){
                    this.account.orders.splice(ind, 1);
                }
            })
        },
        duplicateOrder(order) {
            let orderDuplicated = {...order};

            const baseName = orderDuplicated.name.replace(/ - copia(?: \d+)?$/, '');
            const copyRegex = new RegExp(`^${baseName} - copia(?: (\\d+))?$`);

            let maxCopyNumber = 0;

            // Check existing orders for the highest copy number
            for (let existingOrder of this.account.orders) {
                const match = existingOrder.name.match(copyRegex);
                if (match) {
                    const currentNumber = match[1] ? parseInt(match[1], 10) : 1;
                    if (currentNumber > maxCopyNumber) {
                        maxCopyNumber = currentNumber;
                    }
                }
            }

            // Create the new copy with an incremented number
            if (maxCopyNumber > 0) {
                orderDuplicated.name = `${baseName} - copia ${maxCopyNumber + 1}`;
            } else {
                orderDuplicated.name = `${baseName} - copia`;
            }

            //Hago cambios necesarios en el contrato
            orderDuplicated.transferDate = moment().format('DD/MM/YY');
            orderDuplicated.processingDate = ''
            orderDuplicated.activationDate = ''
            orderDuplicated.lowDate = ''
            orderDuplicated.newStatus.code =
            orderDuplicated.newStatus.code =
                this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2' &&
                !this.canManage('contracts.processor')
                    ? 'bo'
                    : 'p';
            orderDuplicated.liquidationStatus = 'nl'
            //orderDuplicated.marketer = ''
            //orderDuplicated.fee = ''
            //orderDuplicated.product = ''
            //orderDuplicated.productType = ''
            //orderDuplicated.CUPS = ''
            orderDuplicated.consumption = ''
            delete orderDuplicated.consumptionData
            orderDuplicated.commissions = {subdomain: null, breakdown: []}
            orderDuplicated.observations = ''
            delete orderDuplicated._id
            orderDuplicated.statuses = []

            this.account.orders.push(orderDuplicated);
        },
        fetchSelectValues(){

            axios.get(`/api/select`)
                .then((res) => {
                    this.selectValues = res.data.selectValues;

                    //Si no se hay todavia un registro para el usuario se crea un array temporal
                    if (!this.selectValues){
                        this.selectValues = {
                            'acc' : [],
                            'sector' : [],
                            'origin' : [],
                            'orderSources': [],
                            'marketerProducts': []
                        }
                    }
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        addSelectType(data){

            let type = data.type;
            let value = data.value;

            axios.post(`/api/select`, { type: type, value: value })
                .then((res) => {
                    //Añado al array para tenerlo en cliente
                    this.selectValues[type].push(value)

                    //Selecciono el valor y borro
                    if (type === 'acc')
                        this.account.accType = value
                    else if (type === 'sector')
                        this.account.sector = value
                    else
                        this.account.origin = value
                })
                .catch((err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ya existe',
                        text:'El elemento que estas intentando crear ya existe'
                    })
                })
        },
        delSelectType(data){

            let type = data.type;
            let value = data.value;

            //Si se ha seleccionado uno y no la opción de eliminar en blanco
            if (value){

                Swal.fire({
                    icon: 'warning',
                    title: '¿Estás seguro?',
                    text: 'Seguro que quieres borrarlo? Tu acción no podrá revertirse',
                    confirmButtonText: 'Sí',
                    showCancelButton: true,
                    cancelButtonText: 'No'
                }).then((res) => {
                    if (res.isConfirmed){


                        //Borro
                        axios.delete(`/api/select`,{
                            params:{
                                type: type,
                                value: value
                            }
                        })
                            .then((res) => {
                                //Saco el valor de cliente
                                let index = this.selectValues[type].indexOf(value);

                                if (index !== -1) this.selectValues[type].splice(index, 1);


                                //Si la cuenta tiene seleccionada esa opción la desmarco y borro de local
                                if (type === 'acc'){
                                    if (this.account.accType === value) this.account.accType = ''
                                }else if(type === 'sector'){
                                    if (this.account.sector === value) this.account.sector = ''
                                }else {
                                    if (this.account.origin === value) this.account.origin = ''
                                }


                            })
                            .catch((err) => {
                                console.log(err)
                            })
                    }
                })
            }
        },
        selectElement(data){
            let type = data.type;
            let value = data.value;

            let arrInd = type

            if (type === 'acc') arrInd = 'accType'

            this.account[arrInd] = value
        },
        closeWindow(orderModified){
            //Compruebo si tiene al menos un campo relleno, sino se borra
            if (this.areAllFieldsEmpty(orderModified)){
                this.account.orders.splice(this.selectedOrderInd, 1) //lo borro
            }else{
                this.account.orders[this.selectedOrderInd] = orderModified;
            }

            this.selectedOrder = ''
            this.selectedOrderInd = '';
        },
        areAllFieldsEmpty(obj, exceptions = ['liquidationStatus', 'newStatus', 'transferDate', 'errors']){
            return Object.keys(obj).every(key => {
                if (exceptions.includes(key)) {
                    return true; // Ignorar el campo si está en la lista de excepciones
                }

                const field = obj[key];
                if (Array.isArray(field)) {
                    return field.length === 0; // Verificar si el array está vacío
                } else if (typeof field === 'object' && field !== null) {
                    return Object.keys(field).length === 0; // Verificar si el objeto está vacío
                } else {
                    return field === '';       // Verificar si el campo no-array está vacío
                }
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
        toggleUser(user){
            this.account.owner = this.account.owner === user._id ? '' : user._id
        },
        canDelete(code) {
            const user = this.basicData?.userLogged;
            const subdomain = this.basicData?.userSubdomain;

            if (!user || !subdomain) return false;

            const label = user.label;
            const labelsPermissions = subdomain.labels_permissions;

            if (!label || !labelsPermissions) return false;

            const permissionsByLabel = labelsPermissions[label];
            if (!permissionsByLabel) return false;

            const [resource, action] = code.split('.');

            if (!permissionsByLabel[resource]) return false;

            return permissionsByLabel[resource].includes(action);
        }
        ,
        setAccFromOpp(){

            let data = JSON.parse(localStorage.getItem('temporalyCreateAcc'));
            if (!data) return;

            this.account.name = data.name
            this.account.CIF = data.CIF
            this.account.customFields = data.customFields
            this.account.email = data.email
            this.account.phone = data.phone
            this.account.account = data.account
            this.account.landLinePhone = data.landLinePhone
            this.account.sector = data.sector
            this.account.source = data.source
            this.account.website = data.website
            this.account.usersIds = data.usersIds || []
            this.account.billingInfo = data.billingInfo
            this.account.billingInfo.zipCode = data.billingInfo.postal
            delete this.account.billingInfo.postal



            //Si tiene datos de dirección
            if (this.account.billingInfo){

                if (this.account.billingInfo.community)
                    this.getProvinces()

                if (this.account.billingInfo.province)
                    this.getLocalities()
            }

            if ((data.order.productType && data.order.productType !== 'cb'  ) || data.order.marketer || data.order.fee || data.order.product || data.order.CUPS){

                if (data.order.productType !== 'sp') {
                      //Añado el contrato
                    this.addOrder()

                    //Meto datos
                    this.account.orders[0].productType = data.order.productType === 'sp' ? '' : data.order.productType
                    this.account.orders[0].marketer = data.order.marketer
                    this.account.orders[0].fee = data.order.fee
                    this.account.orders[0].product = data.order.product
                    this.account.orders[0].CUPS = data.order.CUPS
                    this.account.orders[0].name = data.name + (this.account.orders[0].CUPS ? (' - ' + this.account.orders[0].CUPS.slice(-6)) : '')
                    this.account.orders[0].province = data.order.province;
                    this.account.orders[0].town = data.order.town;
                    this.account.orders[0].direc = data.order.direc;
                    this.account.orders[0].zip = data.order.zip;
                    if (data.order?.extras) this.account.orders[0].extras = data.order?.extras;
                    if (data.order?.feeSecondary) this.account.orders[0].feeSecondary = data.order?.feeSecondary;
                    if (data.order?.productSecondary) this.account.orders[0].productSecondary = data.order?.productSecondary;
                    if (data.order?.CUPSSecondary) this.account.orders[0].CUPSSecondary = data.order?.CUPSSecondary;
                    if (data.order?.consumptionSecondary) this.account.orders[0].consumptionSecondary = data.order?.consumptionSecondary;

                    this.selectedOrder = ''
                    this.selectedOrderInd = ''
                }
            }

            this.account.opportunity = data._id

            // Elimina el dato tras usarlo
            localStorage.removeItem('temporalyCreateAcc');

        },
        toggleSelectUserInOrders(id){

            //Añado el usuario a los contratos
            this.account.orders.forEach((order) => {
                let index = (order.usersIds.indexOf(id));

                if (index !== -1)
                    order.usersIds.splice(index,1)
                else{
                    //Para cualquier otro si tiene algún otro usuario seleccionado lo quito ( para que solo haya 1 seleccionado)
                    if (this.basicData.userLogged._id !== '683d658761231bd1080b4802' && !order.usersIds.some(u => u !== '65cb57489c2c285441086a43'))
                        order.usersIds = order.usersIds.filter(u => u === '65cb57489c2c285441086a43');

                    order.usersIds.push(id)
                }
            })

        },
        getAllColorVariables() {
            const colorMap = {};

            for (const sheet of document.styleSheets) {
                // Evita errores con hojas externas (CORS)
                try {
                    if (!sheet.cssRules) continue;

                    for (const rule of sheet.cssRules) {
                        if (rule.selectorText === ':root') {
                            const style = rule.style;

                            for (const prop of style) {
                                if (prop.startsWith('--color-')) {
                                    const key = prop.replace('--color-', '');
                                    const value = style.getPropertyValue(prop).trim();
                                    colorMap[key] = value;
                                }
                            }
                        }
                    }
                } catch (e) {
                    // Hoja de estilos externa no accesible por CORS, la ignoramos
                    continue;
                }
            }

            return colorMap;
        },
        actionLink(route){
            this.$router.push(route)
        }
    },
    computed:{
        profileImage() {
            return this.urlImage;
        },
        accountSelected(){

            return this.accounts.find((account) => {
                return account._id === this.account.principalAcc
            })
        },
        filteredOrders(){

            //Si no hay devuelve un array vacio
            if (!this.account.orders || this.account.orders.length === 0) return []


            let ordersFiltered = []

let orders = this.account.orders
            //filtro
            for (let key in orders) {

                let order = orders[key];

                let OrderSearch = [order.name].join('').replace(' ', '').toLowerCase();

                if (OrderSearch.includes(this.searchOrderText.replace(' ', '').toLowerCase())) ordersFiltered.push(order)
            }

            return ordersFiltered
        },
        filteredAccounts(){

            let accounts = []

            if (this.searchAccountText === '') {
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
    }
}
</script>

<style scoped>

</style>
