<template>
    <div class="content-white" v-on:click="hideCustomSelects">

        <!--Contenedor padre para distribución-->
        <form v-on:submit.prevent="updateAccount" class="form register-pos" v-if="account !== ''">

            <!--División de inputs-->
            <div class="top-part">

                <!--Detalles de cuenta-->
                <div class="inputs-part">

                    <div class="text" data-size="20" data-weight="700">Detalles de cuenta</div>

                    <!--Imagen, nombre y CIF escritorio-->
                    <div class="half-space">

                        <!--Imagen-->
                        <div v-bind:class="{ wrong: errors.profileImage }" class="form-group profile-image my-20">
                            <div class="d-flex column">
                                <img :src="profileImage ? profileImage : '/assets/account_images/default.jpg'"
                                    alt="Imagen de cuenta">
                                <div class="custom-button mx-auto px-20" data-bg="blanco" data-size="regular"
                                    v-if="!isInputsDisabled" v-on:click="openDialog">Cambiar</div>
                            </div>

                            <input id="profileImage" type="file" style="display: none" v-on:change="pickupImage">
                            <span v-if="errors.profileImage" class="error">{{ errors.profileImage }}</span>
                        </div>


                        <!--Nombre y CIF-->
                        <div class="form-group d-flex column">

                            <!--Nombre-->
                            <div v-bind:class="{ wrong: errors.name }" class="form-group w-100">
                                <p class="my-auto"><label>Nombre</label> <span data-color="rojo">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['name']" data-size="12" v-model="account.name"
                                        :disabled="isInputsDisabled || !canEditNameAndCIF" type="text">
                                </div>
                                <span v-if="errors.name" class="error">{{ errors.name }}</span>
                            </div>

                            <!--CIF/NIF-->
                            <div v-bind:class="{ wrong: errors.CIF }" class="form-group w-100">
                                <p class="my-auto"><label>CIF/NIF/Pasaporte</label> <span data-color="rojo">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['CIF']" data-size="12" v-model="account.CIF"
                                        :disabled="isInputsDisabled || !canEditNameAndCIF" type="text">
                                </div>
                                <span v-if="errors.CIF" class="error">{{ errors.CIF }}</span>
                            </div>

                            <!--NIF del representante en caso de ser CIF-->
                            <div v-if="/^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/i.test(account.CIF)"
                                class="form-group w-100">
                                <p class="my-auto"><label>NIF Representante</label></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['NIFRepresentative']" data-size="12"
                                        v-model="account.NIFRepresentative" :disabled="isInputsDisabled || !canEditNameAndCIF" type="text">
                                </div>
                                <span v-if="errors.CIF" class="error">{{ errors.NIFRepresentative }}</span>
                            </div>

                            <!--Nombre del representante en caso de ser CIF-->
                            <div v-if="/^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/i.test(account.CIF)"
                                class="form-group w-100">
                                <p class="my-auto"><label>Nombre Representante</label></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['NameRepresentative']" data-size="12"
                                        v-model="account.NameRepresentative" :disabled="isInputsDisabled || !canEditNameAndCIF" type="text">
                                </div>
                                <span v-if="errors.CIF" class="error">{{ errors.NameRepresentative }}</span>
                            </div>
                        </div>

                    </div>


                    <!--Cuenta principal-->
                    <!-- <div v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'" v-bind:class="{ wrong: errors.principalAcc }" class="form-group" v-on:click.stop="">
                        <label>Cuenta principal</label>
                        <div class="input-group" v-if="account.principalAcc === ''">
                            <input v-on:click="isPrincAccFocused = true" data-size="12" v-model="searchAccountText"
                                :disabled="isInputsDisabled" type="text">
                            <i v-if="!isInputsDisabled" class="fa-regular fa-magnifying-glass ml-10 my-auto text"></i>
                        </div> -->

                        <!--Desplegable con todas las cuentas encontradas-->
                        <!-- <div class="select-div mt-10"
                            v-if="!isInputsDisabled && account.principalAcc === '' && filteredAccounts.length > 0 && isPrincAccFocused">
                            <div class="my-5 d-flex pointer d-flex column" v-for="account in filteredAccounts"
                                v-on:click="selectAccount(account)">
                                <p class="text d-flex my-auto" data-size="12"><i
                                        class="fa-solid fa-building my-auto mr-10"></i>{{ account.name }}</p>

                                <div class="separator my-5"></div>
                            </div>
                        </div> -->

                        <!--Cuenta ya seleccionada-->
                        <!-- <div v-if="account.principalAcc !== '' && accountSelected" class="d-flex justify-between">

                            <div class="text ml-5 ellipsis" data-size="13">
                                <i class="fa-solid fa-building mr-10"></i> {{ accountSelected.name }}
                            </div>

                            <div class="my-auto pointer" data-color="rojo" v-on:click="account.principalAcc = ''">
                                <i class="fa-solid fa-x"></i>
                            </div>
                        </div>

                        <span v-if="errors.principalAcc" class="error">{{ errors.principalAcc }}</span>
                    </div> -->


                    <!--telefono y email-->
                    <div class="half-space">

                        <!--Movil y fijo-->
                        <div :class="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? 'half-space w-45-max' : 'form-group'">

                            <!--movil-->
                            <div v-bind:class="{ wrong: errors.phone }" class="form-group">
                                <p class="my-auto"><label>Movil</label> <span data-color="rojo"
                                        v-if="basicData && basicData.userSubdomain && basicData.userSubdomain.settings.accountPhone">*</span>
                                </p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['phone']" data-size="12" v-model="account.phone"
                                        :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.phone" class="error">{{ errors.phone }}</span>
                            </div>

                            <!--fijo-->
                            <div v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'" v-bind:class="{ wrong: errors.landLinePhone }" class="form-group">
                                <p class="my-auto"><label>Fijo</label></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['landLinePhone']" data-size="12"
                                        v-model="account.landLinePhone" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.landLinePhone" class="error">{{ errors.landLinePhone }}</span>
                            </div>
                        </div>


                        <!--email-->
                        <div v-bind:class="{ wrong: errors.email }" class="form-group">

                            <p class="my-auto"><label>Email</label> <span data-color="rojo"
                                    v-if="basicData && basicData.userSubdomain && basicData.userSubdomain.settings.accountEmail">*</span>
                            </p>

                            <div class="d-flex" data-gap="5">
                                <div class="input-group w-100">
                                    <input v-on:focus="delete errors['email']" data-size="12" v-model="account.email"
                                        :disabled="isInputsDisabled" type="email">
                                </div>
                                <a class="my-auto opacity-5 text px-5" :href="'mailto:' + account.email"><i
                                        class="fa-solid fa-envelope"></i></a>
                            </div>
                            <span v-if="errors.email" class="error">{{ errors.email }}</span>
                        </div>
                    </div>


                    <!--Tipo de cuenta y sector-->
                    <!-- <div class="half-space" v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'"> -->
                        <!--Tipo de cuenta-->
                        <!-- <custom-select-component @addElement="addSelectType" @delElement="delSelectType"
                            @selectElement="selectElement" class="mt1" type="acc" title="Tipo de cuenta"
                            :options="sectors" :addedOptions="selectValues.acc" :isInputsDisabled="isInputsDisabled"
                            :selected="account.accType" :errors="errors"></custom-select-component> -->

                        <!--Sector-->
                        <!-- <custom-select-component @addElement="addSelectType" @delElement="delSelectType"
                            @selectElement="selectElement" class="mt1" type="sector" title="Sector" :options="sectors"
                            :addedOptions="selectValues.sector" :isInputsDisabled="isInputsDisabled"
                            :selected="account.sector" :errors="errors"></custom-select-component>
                    </div> -->


                    <!--Origen y web-->
                    <div class="half-space" v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'">

                        <!--origen-->
                        <!--<custom-select-component @addElement="addSelectType" @delElement="delSelectType"
                            @selectElement="selectElement" class="mt1" type="origin" title="Origen" :options="origins"
                            :addedOptions="selectValues.origin" :isInputsDisabled="isInputsDisabled"
                            :selected="account.origin" :errors="errors"></custom-select-component>-->

                        <!--Oportunidad-->
                        <!-- <div v-bind:class="{ wrong: errors.opportunity}" class="form-group">
                            <label>Oportunidad</label>
                            <div class="input-group">
                                <select v-model="account.opportunity" :disabled="isInputsDisabled">
                                    <option value="">Selecciona una oportunidad</option>
                                    <option v-if="opportunities" v-for="opp in opportunities" :value="opp._id">{{ opp.name }}</option>
                                </select>
                            </div>
                            <span v-if="errors.opportunity" class="error">{{ errors.opportunity }}</span>
                        </div> -->


                        <!--web-->
                        <div v-bind:class="{ wrong: errors.website }" class="form-group">
                            <label>Sitio web</label>
                            <div class="input-group">
                                <input v-on:focus="delete errors['website']" data-size="12" v-model="account.website"
                                    :disabled="isInputsDisabled" type="text">
                            </div>
                            <span v-if="errors.website" class="error">{{ errors.website }}</span>
                        </div>

                    </div>

                    <!--observaciones de cuenta-->
                    <div class="form-group">
                        <label>Observaciones</label>
                        <div class="input-group">
                            <textarea class="h-100-px-min w-100-px-min" data-size="12" v-model="account.observations"
                                :disabled="isInputsDisabled" type="text"></textarea>
                        </div>
                    </div>


                    <!--Datos relacionados-->
                    <div v-if="(account.opportunity && accountOpportunity && accountOpportunity._id === account.opportunity) || (account.tasks && account.tasks.length > 0) || (account.events && account.events.length > 0)"
                        class="text mt-20" data-size="18" data-weight="700">Datos relacionados</div>


                    <!--Contactos-->
                    <div class="text mt-20" data-size="15" data-weight="500"
                         v-if="account.contacts && account.contacts.length > 0"><i class="far fa-address-book mr-5"></i>
                        Contactos</div>

                    <div class="d-flex my-10" v-for="contact in account.contacts">
                        <div class="d-flex justify-center w-100">

                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700"
                                v-if="contact.name">{{ getInitials(contact.name.first + contact.name.second) }}</div>

                            <div class="d-flex column my-auto ellipsis">
                                <p class="ellipsis" data-color="azul" data-weight="700" data-size="15"
                                    v-if="contact.name">{{ contact.name.first }} {{ contact.name.second }} {{
                                        contact.surname.second }} {{ contact.surname.second }}</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                v-on:click="actionLink(`/contacts/${contact._id}`)"><i
                                    class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>


                    <!--Oportunidad relacionada-->
                    <div class="text mt-20" data-size="15" data-weight="500" v-if="account.opportunity && account.opportunity && accountOpportunity._id === account.opportunity"><i
                            class="far fa-file-circle-question mr-5"></i> Oportunidad</div>

                    <div class="d-flex my-10" v-if="account.opportunity && accountOpportunity && accountOpportunity._id === account.opportunity">
                        <div class="d-flex justify-center w-100">

                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700">{{
                                getInitials(accountOpportunity.name) }}</div>

                            <div class="d-flex column my-auto ellipsis">
                                <p class="ellipsis" data-color="azul" data-weight="700" data-size="15">{{
                                    accountOpportunity.name }}</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                v-on:click="actionLink(`/opportunities/${accountOpportunity._id}`)"><i
                                    class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>


                    <!--Tareas-->
                    <div class="text mt-20" data-size="15" data-weight="500"
                         v-if="account.tasks && account.tasks.length > 0"><i class="far fa-list-check mr-5"></i> Tareas
                    </div>

                    <div class="d-flex my-10" v-for="task in account.tasks">
                        <div class="d-flex justify-center w-100">

                            <div class="initials twoBlues small mr-20" data-size="18" data-bg="blanco"
                                data-mode="outline" data-weight="700">{{ getInitials(task.subject) }}</div>

                            <div class="d-flex column my-auto ellipsis">
                                <p class="ellipsis" data-color="azul" data-weight="700" data-size="15">{{ task.subject
                                    }}</p>
                            </div>

                            <!--Barra progreso-->
                            <div class="d-flex w-25 mx-20" data-force="true">
                                <div class="progress-bar my-auto">
                                    <div :class="'prog-' + getTaskProgress(task).toFixed(0)"></div>
                                </div>
                                <p class="text ml-10 my-auto" data-size="10" data-weight="500">{{
                                    getTaskProgress(task).toFixed(0) }}%</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                v-on:click="actionLink(`/tasks/${task._id}`)"><i class="fas fa-arrow-right-long"></i>
                            </div>
                        </div>
                    </div>

                    <!--Eventos-->
                    <div class="text mt-20" data-size="15" data-weight="500"
                         v-if="account.events && account.events.length > 0"><i class="far fa-calendar mr-5"></i> Eventos
                    </div>

                    <!--Listado de eventos-->
                    <div class="d-flex my-10 pointer" v-for="event in account.events"
                        v-on:dblclick="actionLink('/calendar/' + event._id)">
                        <div class="d-flex justify-center w-100">

                            <div class="initials small mr-20" data-size="18" data-weight="700" :data-bg="event.color">{{
                                getInitials(event.subject) }}</div>

                            <div class="d-flex column my-auto ellipsis">
                                <p class="ellipsis" :data-color="event.color" data-weight="700" data-size="15">{{
                                    event.subject }}</p>

                                <!--Intervalo fechas inicial - final-->
                                <p class="text opacity-5" data-size="10">{{ formatDate(event.date.start) }} - {{
                                    formatDate(event.date.end) }}</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                :data-bg="event.color" v-on:click="actionLink(`/calendar/${event._id}`)"><i
                                    class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>


                    <!--Contactos-->
                    <div class="text mt-20" data-size="15" data-weight="500"
                         v-if="account.contacts && account.contacts.length > 0"><i class="far fa-address-book mr-5"></i>
                        Contactos</div>

                    <div class="d-flex my-10" v-for="contact in account.contacts">
                        <div class="d-flex justify-center w-100">

                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700"
                                 v-if="contact.name">{{ getInitials(contact.name.first + contact.name.second) }}</div>

                            <div class="d-flex column my-auto ellipsis">
                                <p class="ellipsis" data-color="azul" data-weight="700" data-size="15"
                                   v-if="contact.name">{{ contact.name.first }} {{ contact.name.second }} {{
                                        contact.surname.second }} {{ contact.surname.second }}</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                 v-on:click="actionLink(`/contacts/${contact._id}`)"><i
                                class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>


                    <!--Correos relacionados-->
                    <div class="text mt-20" data-size="15" data-weight="500"
                         v-if="account.mails && account.mails.length > 0"><i
                        class="far fa-envelopes-bulk mr-5"></i> Correos</div>

                    <div class="d-flex my-10" v-for="mail in account.mails">
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
                    <!--<div v-if="account.createdBy" class="my-20">

                        <div class="d-flex justify-between">
                            <p class="text opacity-5" data-size="13" data-weight="600"><i class="fas fa-user mr-5"></i>
                                Creador de la cuenta</p>

                            <p class="text opacity-5 pointer" data-size="10" data-weight="600"
                                v-if="!isInputsDisabled && isEditing" v-on:click="isChangingUser = !isChangingUser">
                                Cambiar</p>
                        </div>

                        <div class="separator my-0"></div>

                        <div v-if="!isChangingUser" class="d-flex justify-center mr-20 my-10 w-100">
                            <div class="initials verySmall mr-20 my-auto" data-style="initials"
                                v-bind:class="{ image: account.createdBy.profileImage }">
                                <img :src="'/assets/profile_images/' + account.createdBy.profileImage"
                                    class="profile-image">
                            </div>

                            <div class="d-flex column my-auto">
                                <p class="text opacity-5" data-weight="600" data-size="14">{{
                                    account.createdBy.firstName }}</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="tiny"
                                v-on:click="actionLink((this.basicData.userLogged._id === account.createdBy._id ? '/profile' : `/users/${account.createdBy._id}`))">
                                <i class="fas fa-arrow-right-long"></i>
                            </div>
                        </div>

                        <div v-if="isChangingUser" class="form-group mt-10">

                            <div class="form-group">
                                <div class="input-group">
                                    <select v-model="account.owner">
                                        <option v-for="user in basicData.userList" :value="user._id">{{ user.firstName
                                            }} {{ user.lastName }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="user-list">
                                usuario logeado
                                <div class="user"  v-bind:class="{selected: account.owner === basicData.userLogged._id || account.createdBy._id === basicData.userLogged._id }" v-on:click="toggleUser(basicData.userLogged)">

                                    <div class="d-flex">

                                        imagen perfil
                                        <div class="my-auto w-10">
                                            <img :src="'/assets/profile_images/' + basicData.userLogged.profileImage" alt="Imagen usuario">
                                        </div>

                                        label, nombre, correo
                                        <div class="content d-flex column mx-10 w-80">

                                            <p class="text opacity-3 upper ellipsis" data-size="8">{{ basicData.userLogged.label }}</p>

                                            <p class="text ellipsis" :data-color="(account.owner === basicData.userLogged._id || (account.createdBy._id === basicData.userLogged._id && !account.owner)) ? 'azul' : ''">{{ basicData.userLogged.firstName }} {{ basicData.userLogged.lastName }}</p>

                                            <p class="text opacity-3 ellipsis" data-size="8">{{ basicData.userLogged.email }}</p>

                                        </div>

                                        botón seleccionar responsable
                                        <div class="text pointer mx-auto my-auto w-10">
                                            <i class="fas fa-arrow-pointer" :data-color="(account.owner === basicData.userLogged._id || (account.createdBy._id === basicData.userLogged._id && !account.owner)) ? 'azul' : ''"></i>
                                        </div>
                                    </div>

                                    Separador
                                    <div class="separator mt-5 mb-0"></div>
                                </div>

                                cada usuario
                                <div class="user" v-for="user in basicData.userList" v-bind:class="{selected: account.owner === user._id || account.createdBy._id === user._id }" v-on:click="toggleUser(user)">

                                    <div class="d-flex">

                                        imagen perfil
                                        <div class="my-auto w-10">
                                            <img :src="'/assets/profile_images/' + user.profileImage" alt="Imagen usuario">
                                        </div>

                                        label, nombre, correo
                                        <div class="content d-flex column mx-10 w-80">

                                            <p class="text opacity-3 upper ellipsis" data-size="8">{{ user.label }}</p>

                                            <p class="text ellipsis" :data-color="(account.owner === user._id || (account.createdBy._id === user._id && !account.owner)) ? 'azul' : ''">{{ user.firstName }} {{ user.lastName }}</p>

                                            <p class="text opacity-3 ellipsis" data-size="8">{{ user.email }}</p>

                                        </div>

                                        botón seleccionar responsable
                                        <div class="text pointer mx-auto my-auto w-10">
                                            <i class="fas fa-arrow-pointer" :data-color="(account.owner === user._id || (account.createdBy._id === user._id && !account.owner)) ? 'azul' : ''"></i>
                                        </div>
                                    </div>

                                    Separador
                                    <div class="separator mt-5 mb-0"></div>
                                </div>
                            </div>

                            <p v-if="basicData.userList.length === 0" class="text opacity-3" data-size="10">No tienes
                                usuarios para asignar</p>
                        </div>
                    </div>-->


                    <!--Usuarios propietarios-->
                    <div class="form-group mt-20 owners-section desktop-item">
                        <div class="text mb-10" data-size="18" data-weight="700">Propietarios de la cuenta</div>

                        <div>
                            <user-list-component
                                :basicData="basicData"
                                v-model:userListSelected="account.usersIds"
                                :account="true"
                                :editing="isEditing && !isInputsDisabled"
                                @toggleSelectUserInOrders="toggleSelectUserInOrders">
                            </user-list-component>
                        </div>

                        <p v-if="!basicData.userList || basicData.userList.length === 0" class="text opacity-3" data-size="10">No tienes usuarios para asignar</p>
                    </div>
                </div>

                <!--Separator vertical-->
                <div class="separator" data-position="vertical"></div>


                <!--Dirección de facturación-->
                <div class="inputs-part">
<!--                    <template v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'">-->

                        <div class="text" data-size="20" data-weight="700">Dirección de facturación</div>

                        <!--Comunidad-->
                        <div v-bind:class="{ wrong: errors.billingInfo.community }" class="form-group">
                            <p class="my-auto"><label>Comunidad</label></p>
                            <div class="input-group">
                                <!--<select name="" id="" v-on:change="selectCommunity" v-model="account.billingInfo.community" :disabled="isInputsDisabled">
                                    <option value="">Selecciona una comunidad</option>
                                    <option v-for="community in GEO.communities" :value="community.acom_name" >{{ community.acom_name}}</option>
                                </select>-->
                                <input v-on:focus="delete errors['billingInfo']['community']" data-size="12"
                                    v-model="account.billingInfo.community" :disabled="isInputsDisabled" type="text">
                            </div>
                            <span v-if="errors.billingInfo.community" class="error">{{ errors.billingInfo.community
                                }}</span>
                        </div>


                        <!--Provincia y localidad-->
                        <div class="half-space">

                            <!--Provincia-->
                            <div v-bind:class="{ wrong: errors.billingInfo.province }" class="form-group">
                                <p class="my-auto"><label>Provincia</label> <span data-color="rojo" v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.accountProvince">*</span></p>
                                <div class="input-group">
                                    <!--<select name="" id="" v-on:change="selectProvince" v-model="account.billingInfo.province" :disabled="isInputsDisabled">
                                        <option value="">Selecciona una provincia</option>
                                        <option v-for="province in GEO.provinces" :value="province.prov_name" >{{ province.prov_name }}</option>
                                    </select>-->
                                    <input v-on:focus="delete errors['billingInfo']['province']" data-size="12"
                                        v-model="account.billingInfo.province" :disabled="isInputsDisabled" type="text">

                                </div>
                                <span v-if="errors.billingInfo.province" class="error">{{ errors.billingInfo.province
                                    }}</span>
                            </div>

                            <!--Localidad-->
                            <div v-bind:class="{ wrong: errors.billingInfo.locality }" class="form-group">
                                <p class="my-auto"><label>Localidad</label> <span data-color="rojo" v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.accountLocality">*</span></p>
                                <div class="input-group">
                                    <!--<select name="" id="" v-on:change="selectLocality" v-model="account.billingInfo.locality" :disabled="isInputsDisabled">
                                        <option value="">Selecciona una localidad</option>
                                        <option v-for="locality in GEO.localities" :value="locality.mun_name" >{{ locality.mun_name }}</option>
                                    </select>-->
                                    <input v-on:focus="delete errors['billingInfo']['locality']" data-size="12"
                                        v-model="account.billingInfo.locality" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.billingInfo.locality" class="error">{{ errors.billingInfo.locality }}</span>
                            </div>
                        </div>


                        <!--Dirección y cod zip-->
                        <div class="half-space">
                            <!--Dirección-->
                            <div v-bind:class="{ wrong: errors.billingInfo.address }" class="form-group ">
                                <p class="my-auto"><label>Dirección</label> <span data-color="rojo" v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.accountAddress">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['billingInfo']['address']" data-size="12"
                                        v-model="account.billingInfo.address" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.billingInfo.address" class="error">{{ errors.billingInfo.address
                                    }}</span>
                            </div>

                            <!--cod postal-->
                            <div v-bind:class="{ wrong: errors.billingInfo.zipCode }" class="form-group ">
                                <p class="my-auto"><label>Código postal</label> <span data-color="rojo" v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.accountPostal">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['billingInfo']['zipCode']" data-size="12"
                                        v-model="account.billingInfo.zipCode" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="errors.billingInfo.zipCode" class="error">{{ errors.billingInfo.zipCode
                                    }}</span>
                            </div>
                        </div>

                        <div class="separator mt-30"></div>
<!--                    </template>-->


                    <!--Campos personalizados-->

                    <!--Header-->
                    <template v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'">

                        <div class="d-flex">

                            <div class="text desktop-item" data-size="20" data-weight="700">Campos personalizados</div>

                            <div class="text mobile-item my-20" data-size="18" data-weight="700">Campos personalizados</div>

                            <div class="custom-button ml-auto my-auto mr-20" data-size="medium" data-bg="amarillo"
                                v-if="!isInputsDisabled" v-on:click="addCustomField"><i class="fas fa-plus"></i></div>
                        </div>

                        <custom-fields-component class="my-10" v-for="(field, fieldInd) in account.customFields"
                            :isReadOnly="isInputsDisabled" :field="field" :fieldInd="fieldInd" type="acc"
                            @delCustomField="delCustomField" @addCustomFields="addCustomFields"></custom-fields-component>


                        <p v-if="account.customFields && account.customFields.length === 0" data-size="11"
                            class="text my-10 opacity-5">No hay ningún campo personalizado añadido</p>

                        <div class="separator my-20"></div>
                    </template>

                    <!--Pedidos-->

                    <!--Header-->
                    <div class="d-flex mt-30">

                        <div class="text desktop-item" data-size="20" data-weight="700">Contratos</div>

                        <div class="text mobile-item my-20" data-size="18" data-weight="700">Contratos</div>

                        <div class="custom-button ml-auto my-auto mr-20" data-size="medium" data-bg="amarillo"
                            v-on:click="addOrder" v-if="canAddContractsToAccount"><i class="fas fa-plus"></i></div>
                    </div>


                    <!--Barra de busqueda-->
                    <div class="search-bar my-15">
                        <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                        <input type="text" placeholder="Buscar un contrato..." v-model="searchOrderText">
                    </div>


                    <!--Listado pedidos-->
                    <div v-for="(item, orderInd) in ordersToShow" :key="(item.order._id?.$oid || item.order._id || orderInd) + '-group'" class="order-group mb-10">

                        <order-item-component :basicData="basicData"
                            :order="item.order" :orderInd="orderInd" :isReadOnly="isInputsDisabled" :canDelete="isEditing && !isInputsDisabled"
                            :versionsCount="item.versions.length + 1" :versionsErrors="versionsErrorsByCUPS[item.order.CUPS]"
                            :hasVersions="item.versions.length > 0" :versionsExpanded="!!expandedGroups[item.cups]"
                            @selectOrder="selectOrder(item.order._id?.$oid || item.order._id)" @delOrder="delOrder(item.order._id?.$oid || item.order._id)"
                            @duplicateOrder="duplicateOrder" @toggleVersions="toggleVersions(item.cups)"></order-item-component>

                        <!--Desplegable con las versiones anteriores del contrato-->
                        <div v-if="item.versions.length > 0 && expandedGroups[item.cups]" class="versions-list ml-20 mt-5">
                            <order-item-component v-for="(version, vInd) in item.versions" :key="version._id?.$oid || version._id"
                                :basicData="basicData" :order="version" :orderInd="vInd" :isReadOnly="isInputsDisabled"
                                :canDelete="isEditing && !isInputsDisabled"
                                @selectOrder="selectOrder(version._id?.$oid || version._id)" @delOrder="delOrder(version._id?.$oid || version._id)"
                                @duplicateOrder="duplicateOrder"></order-item-component>
                        </div>
                    </div>

                    <p v-if="!account.orders || account.orders.length === 0" data-size="11" class="text my-10 opacity-5">No hay ningún contrato añadido</p>

                    <!--Info superpuesta pedido seleccionado-->
                    <order-details-item-component class="my-20" v-if="selectedOrder" :order="selectedOrder"
                        :account="account" :selectValues="selectValues" :basicData="basicData"
                        :isInputsDisabled="isOrderDetailsDisabled" :isReadOnly="isReadonly" :isEditingFromOther="isEditingOrder" :ordersEditing="ordersEditing" :originalOrders="originalOrders"
                        canCreate="true" @addElement="addSelectType" @delElement="delSelectType"
                        @selectElement="selectElement" @closeWindow="closeWindow" @createOrder="createOrder"
                        @deleteOrder="deleteOrder" @activeEditing="activeEditing" @toggleEditMode="toggleEditMode"
                        @reloadAcc="fetchAccount" @toggleEditingContract="toggleEditingContract" @catchOrderFiles="catchOrderFiles"
                        @renewalOrder="renewalOrder"></order-details-item-component>
                </div>


                <!--Propietario-->
                <div class="form-group mt-20 owners-section mobile-item">
                    <!--Toggle móvil-->
                    <div class="owners-toggle" v-on:click="isSeeingOwnersMobile = !isSeeingOwnersMobile">
                        <div class="d-flex align-center justify-between">
                            <div class="text" data-size="18" data-weight="700">Propietarios de la cuenta</div>
                            <i class="fa-solid" v-bind:class="isSeeingOwnersMobile ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </div>
                    </div>

                    <div class="owners-list-mobile" v-show="isSeeingOwnersMobile">
                        <user-list-component
                            :basicData="basicData"
                            v-model:userListSelected="account.usersIds"
                            :account="true"
                            :editing="isEditing && !isInputsDisabled"
                            @toggleSelectUserInOrders="toggleSelectUserInOrders">
                        </user-list-component>
                    </div>

                    <p v-if="!basicData.userList || basicData.userList.length === 0" class="text opacity-3" data-size="10">No tienes usuarios para asignar</p>
                </div>
            </div>


            <!--separador-->
            <div class="separator desktop-item"></div>

            <!--Botón editar-->
            <div class="account-detail-actions">
                <!--Botón editar-->
                <div class="btn-part" v-if="!isUpdating && !isEditing">

                    <div>
                        <button v-if="basicData.userLogged && (canManage('contracts.calls') ||basicData.userLogged._id === '68edfbf7c84a190aeb0a6672') && ((naturgyCallOrders && naturgyCallOrders.length > 0) || account.naturgyCallSID || account.naturgyCallRecordingUrl)" class="custom-button mr-10" data-size="regular"
                                :data-bg="account.naturgyCallRecordingUrl
                                            ? 'success'
                                            : (account.naturgyCallSID && naturgyCallStatus)? naturgyCallStatus.color: 'naturgyColor'"
                                data-color="blanco"
                                v-on:click.prevent="handleCall">
                            {{
                                account.naturgyCallRecordingUrl
                                    ? 'Llamada completada'
                                    : (account.naturgyCallSID && naturgyCallStatus && naturgyCallStatus.color)
                                        ? naturgyCallStatus.title
                                        : 'Llamada de verificación'
                            }}
                        </button>
                    </div>

                    <button class="custom-button mr-10" data-size="regular" data-bg="rojo"
                            v-on:click.prevent="('fromAccounts' in $route.query) ? actionLink('/contracts') : actionLink('/accounts')">Volver</button>
                    <button class="custom-button" data-size="regular" v-if="!isReadonly && !isCheckingAccountContracts && canOpenAccountEditMode"
                            v-on:click="isEditing = true">Editar</button>
                    <!--<div class="custom-button" data-size="big" v-if="!isReadonly && basicData && basicData.userLogged._id !== '65cb57489c2c285441086a43'" @click.stop=""><i class="fa-solid fa-spinner-third fa-spin mr-5"></i> En mantenimiento</div>-->
                </div>

                <div class="btn-part" v-if="!isUpdating && isEditing">
                    <button class="custom-button mr-10" data-size="regular" data-bg="rojo"
                            v-on:click.prevent="restartAcc">Cancelar</button><!--v-on:click.prevent="!!$route.query.id ? actionLink('/') : actionLink('/accounts')"-->
                    <button class="custom-button" data-size="regular" v-if="canShowSaveButton">Guardar <i
                        class="fas fa-chevron-right ml-10"></i></button>
                </div>

                <div class="btn-part" v-if="isUpdating">
                    <button class="custom-button" data-size="regular"> <i class="fa-solid fa-spinner-third fa-spin mr-5"></i>
                        Espere un momento</button>
                </div>
            </div>
        </form>
    </div>

</template>

<script>
import { getCurrentOrderByCups } from '../../utils/order';
import {startCall} from "../../composables/useCall";
export default {

    name: "AccountDetailsComponent",
    props: ['basicData'],
    data() {
        return {
            orderFiles: [],
            selectingFromUi: false,
            isCheckingAccountContracts: true,
            account: '',
            accountDefect: '',
            errors: {
                billingInfo: []
            },
            isPrincAccFocused: false,
            accTypes: [],
            sectors: [],
            origins: [],
            searchAccountText: '',
            accounts: [],
            opportunities: '',
            GEO: {
                communities: '',
                provinces: '',
                localities: '',
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
            isChangingUser: false,
            isEditing: false,
            isEditingOrder: false,
            isUpdating: false,
            canUpdateAcc: true,
            accountOpportunity: '',
            ordersEditing: [],
            originalOrders: [],
            naturgyCallStatus: null,
            isSeeingOwnersMobile: false,
            expandedGroups: {},
        }
    },
    async mounted() {

        await this.fetchAccount().then(() => {
            const payload = JSON.parse(localStorage.getItem('temporalyCreateAcc') || 'null')

            if (!this.account.opportunity)
                this.account.opportunity = ''

            if (payload && payload.order) {
                // Asegúrate de que existe el array
                this.account.orders = this.account.orders || []

                // Añade al principio un nuevo pedido con los datos del payload
                const newOrder = {
                    ...payload.order,
                    name: `${this.account.name}${payload.order.CUPS ? ' - ' + payload.order.CUPS.slice(-6) : ''}`,
                    newStatus: { code: 'p', date: '', creator: this.basicData.userLogged._id },
                    errors: {},
                    docs: [],
                    statuses: []
                }
                this.account.orders.unshift(newOrder)

                // Selecciona ese pedido y abre la edición
                this.selectedOrder = JSON.parse(JSON.stringify(newOrder))
                this.selectedOrderInd = 0
                this.isEditing = true

                //Compruebo si tiene campos personalizados que sean archivos para establecerle una etiqueta temporal para al guardar pasar a

                // Limpia el flag
                localStorage.removeItem('temporalyCreateAcc')
            }


        })

        if (this.basicData.userLogged) {
            await this.fetchAllOrdersWithoutPaginate()
        }

        this.isCheckingAccountContracts = false

        if (!!this.account.orders){
            //Guardo los contratos originales por si luego los tengo que reestablecer por defecto
            let orders = JSON.parse(JSON.stringify(this.account.orders))
            this.originalOrders = JSON.parse(JSON.stringify(orders))

            if(this.$route.query?.id){
                this.selectOrder(this.$route.query.id)
            }
        }


        this.getCommunities()
        this.fetchSelectValues()
        if (this.basicData.userLogged && this.accounts.length === 0) {
            this.searchAccounts()
        }

        if (this.basicData.userLogged && this.opportunities.length === 0)
            this.fetchUserOpps()

        if (!!this.account.naturgyCallSID)
            this.fetchNaturgyVerificationStatus()
    },
    watch: {
    "basicData.userLogged"() {
        this.searchAccounts()
        this.isCheckingAccountContracts = true
        this.isCheckingAccountContracts = false

        if (this.opportunities.length === 0)
            this.fetchUserOpps()
    },

    "account.billingInfo.community"() {
        this.getProvinces()
    },

    "account.billingInfo.province"() {
        this.getLocalities()
    },

    "account.orders"() {
        if (!this.selectedOrder) return

        if (this.selectingFromUI) return
        if (this.account.orders && this.$route.query.id) {
            this.selectOrderById()
        }
    },

    "account.profileImage"() {
        if (this.account && this.account.profileImage) {
            this.urlImage = '/assets/account_images/' + this.account.profileImage
        }
    },

    "$route.query.id": {
        immediate: true,
        handler(id) {
            if (!id) {
                this.selectedOrder = ''
                this.selectedOrderInd = ''
                return
            }

            if (!this.account?.orders?.length) return

            const index = this.account.orders.findIndex(order => {
                return (order._id?.$oid || order._id) === id
            })

            if (index !== -1) {
                this.selectedOrder = this.account.orders[index]
                this.selectedOrderInd = index
            } else {
                this.selectedOrder = ''
                this.selectedOrderInd = ''
            }
        }}
    },
    methods: {
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
        async fetchOrderById(id) {
            try {
                const res = await axios.get(`/api/orders/${id}`)
                return res.data.order
            } catch (e) {
                console.error('No se pudo cargar el contrato', e)
                return null
            }
        },
        async fetchOpportunity() {

            await axios.get(`/api/opportunities/${this.account.opportunity}`)
                .then((res) => {
                    this.accountOpportunity = res.data.opportunity
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        activeEditing() {
            this.isEditing = true
        },
        hideCustomSelects() {

            this.isPrincAccFocused = false

        },
        restartAcc() {
            this.account = JSON.parse(JSON.stringify(this.accountDefect))
            this.isEditing = false

            this.filterAccount()
        },
        toggleVersions(cups) {
            if (!cups) return;

            this.expandedGroups[cups] = !this.expandedGroups[cups];
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

            // DNI / NIF: 8 números + letra
            if (/^\d{8}[A-Z]$/.test(doc)) {
                const number = parseInt(doc.substring(0, 8), 10);
                const expectedLetter = nifLetters[number % 23];

                return {
                    valid: doc[8] === expectedLetter,
                    skipped: false,
                    message: doc[8] === expectedLetter
                        ? null
                        : 'El NIF tiene formato español, pero la letra de control no es correcta.'
                };
            }

            // NIE: X/Y/Z + 7 números + letra
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
                        : 'El NIE tiene formato español, pero la letra de control no es correcta.'
                };
            }

            // CIF: letra + 7 números + control
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
                    message: isValid
                        ? null
                        : 'El CIF tiene formato español, pero el carácter de control no es correcto.'
                };
            }

            // Si no ha encajado como DNI/NIE/CIF, lo tratamos como posible pasaporte.
            // No permitimos texto libre tipo nombres.
            if (!/^[A-Z0-9]{5,20}$/.test(doc)) {
                return {
                    valid: false,
                    skipped: false,
                    message: 'DNI/CIF/Pasaporte no válido'
                };
            }

            // Evita nombres: JUANPEREZ, MARIA, EMPRESA, etc.
            if (!/\d/.test(doc)) {
                return {
                    valid: false,
                    skipped: false,
                    message: 'DNI/CIF/Pasaporte no válido'
                };
            }

            // Casos que parecen documentos españoles incompletos
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

            // Pasaporte/documento extranjero con estructura mínima válida
            return {
                valid: true,
                skipped: true,
                message: null
            };
        },

        validateIban(value) {
            const iban = this.normalizeControlField(value);

            if (!iban) {
                return {
                    valid: true,
                    message: null
                };
            }

            // Excepción de negocio
            if (iban === 'ES0000') {
                return {
                    valid: true,
                    skipped: true,
                    message: null
                };
            }

            if (!/^[A-Z]{2}\d{2}[A-Z0-9]+$/.test(iban)) {
                return {
                    valid: false,
                    message: 'IBAN no válido.'
                };
            }

            if (!/^ES\d{22}$/.test(iban)) {
                return {
                    valid: false,
                    message: 'El IBAN debe tener estructura española: ES + 22 dígitos.'
                };
            }

            const rearranged = iban.slice(4) + iban.slice(0, 4);
            let numericIban = '';

            for (const char of rearranged) {
                if (/[A-Z]/.test(char)) {
                    numericIban += String(char.charCodeAt(0) - 55);
                } else {
                    numericIban += char;
                }
            }

            let remainder = 0;

            for (const digit of numericIban) {
                remainder = (remainder * 10 + Number(digit)) % 97;
            }

            return {
                valid: remainder === 1,
                message: remainder === 1
                    ? null
                    : 'Los dígitos de control del IBAN no son correctos.'
            };
        },

        validateCups(value) {
            const cups = this.normalizeControlField(value);

            if (!cups) {
                return {
                    valid: true,
                    message: null
                };
            }

            // ES + 16 dígitos + 2 letras de control + opcionalmente 2 caracteres finales
            if (!/^ES\d{16}[A-Z]{2}([A-Z0-9]{2})?$/.test(cups)) {
                return {
                    valid: false,
                    message: 'CUPS no válido.'
                };
            }

            const letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
            const numericPart = cups.substring(2, 18);
            const remainder = BigInt(numericPart) % 529n;

            const expected =
                letters[Number(remainder / 23n)] +
                letters[Number(remainder % 23n)];

            const current = cups.substring(18, 20);

            return {
                valid: current === expected,
                message: current === expected
                    ? null
                    : 'Las letras de control del CUPS no son correctas.'
            };
        },

        async updateAccount(type) {

            if ((type !== 'order' && this.canUpdateAcc && !this.isUpdating) || (type === 'order' && !this.isUpdating)) {


                if (type !== 'order')
                    this.isUpdating = true

                //reseteo los errores
                this.errors = {
                    billingInfo: []
                }


                let hasErrors = false;

                //Validaciones


                //INFORMACIÓN BÁSICA

                //Nombre
                if (this.account.name === '') {
                    this.errors.name = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }


                //CIF/NIF
                if (this.account.CIF === '') {
                    this.errors.CIF = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                const documentValidation = this.validateSpanishDocumentIfApplies(this.account.CIF);

                if (!documentValidation.valid) {
                    this.errors.CIF = 'DNI/CIF/Pasaporte no válido';
                    hasErrors = true;
                }

                //Telefono movil
                if (this.account.phone === '' && (this.basicData.userSubdomain.settings.accountPhone)){
                    this.errors.phone = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //Deshabilitada comprobacion por numeros extranjeros de mas de 9 numeros
                // if (this.account.phone && this.account.phone.length !== 9){
                //     this.errors.phone = this.getErrorMessage('malformedPhone');
                //     hasErrors = true;
                // }

                //Telefono fijo
                if (this.account.landLinePhone && String(this.account.landLinePhone).length !== 9) {
                    this.errors.landLinePhone = this.getErrorMessage('malformedPhone');
                    hasErrors = true;
                }

                //Ingresos anuales
                if (this.account.annualIncome && isNaN(this.account.annualIncome)) {
                    this.errors.annualIncome = this.getErrorMessage('onlyNumbers');
                    hasErrors = true;
                }

                //Correo
                if (this.account.email === '' && (this.basicData.userSubdomain.settings.accountEmail)){
                    this.errors.email = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                let regexEmail = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

                if (this.account.email && !regexEmail.test(this.account.email)) {
                    this.errors.email = this.getErrorMessage('malformedEmail');
                    hasErrors = true;
                }

                //INFORMACIÓN FACTURACIÓN

                //Comunidad
                /*if (!this.account.billingInfo.community){
                    this.errors.billingInfo.community = this.getErrorMessage('onlyNumbers');
                    hasErrors = true;
                }*/

                //Provincia
                if (!this.account.billingInfo.province && this.basicData.userSubdomain.settings.accountProvince) {
                    this.errors.billingInfo.province = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //Localidad
                if (!this.account.billingInfo.locality && this.basicData.userSubdomain.settings.accountLocality) {
                    this.errors.billingInfo.locality = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                //Dirección
                if (!this.account.billingInfo.address && this.basicData.userSubdomain.settings.accountAddress) {
                    this.errors.billingInfo.address = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }


                //Codigo postal
                if (!this.account.billingInfo.zipCode && this.basicData.userSubdomain.settings.accountPostal) {
                    this.errors.billingInfo.zipCode = this.getErrorMessage('isEmpty');
                    hasErrors = true;
                }

                if (this.account.billingInfo.zipCode && this.account.billingInfo.zipCode.length !== 5) {
                    this.errors.billingInfo.zipCode = 'El zip tiene que tener 5 digitos';
                    hasErrors = true;
                }

                if (this.account.billingInfo.zipCode && isNaN(this.account.billingInfo.zipCode)) {
                    this.errors.billingInfo.zipCode = this.getErrorMessage('onlyNumbers');
                    hasErrors = true;
                }


                //Campos personalizados
                if (this.account.customFields && this.account.customFields.length > 0) {

                    this.account.customFields.forEach((customField) => {

                        if (customField.title === '') {
                            customField.errors = this.getErrorMessage('isEmpty');
                            hasErrors = true;
                        }
                    })
                }


                //Contratos
                if (this.account.orders.length > 0) {
                    this.account.orders.forEach((order, orderInd) => {

                        console.log("2️⃣ Antes de validar:");
                            order.docs?.forEach((doc, j) => {
                                console.log(`Order  Doc ${j}:`,
                                    doc.fileValue,
                                    "Es File?",
                                    doc.fileValue instanceof File
                                );
                            });

                        order.errors = {
                            commissions: {subdomain: null, breakdown: []},
                            decommissions: {subdomain: null, breakdown: []},
                        }

                        let lastStatus = ''

                        if (order.newStatus && order.newStatus.code !== '')
                            lastStatus = order.newStatus.code
                        else {

                            let sortedStatuses = order.statuses.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

                            lastStatus = sortedStatuses[0].code

                        }


                        if (lastStatus !== 'bo' && lastStatus !== 'an' && lastStatus !== 's') {

                            //Título
                            if (!order.name) {
                                order.errors.name = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            //Dirección de suministro
                            if (!order.direc && this.basicData.userSubdomain.settings.orderAddress) {
                                order.errors.direc = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            if ((lastStatus === 'a' || lastStatus === 'b') && !order.activationDate) {
                                order.errors.activationDate = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            if (lastStatus === 'b' && !order.lowDate) {
                                order.errors.lowDate = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }


                            //Poblacion
                            if (!order.town && this.basicData.userSubdomain.settings.orderTown) {
                                order.errors.town = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }


                            //Provincia
                            if (!order.province && this.basicData.userSubdomain.settings.orderProvince) {
                                order.errors.province = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            //Código postal
                            if (!order.zip && this.basicData.userSubdomain.settings.orderPostal) {
                                order.errors.zip = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            if (order.zip && order.zip.length !== 5) {
                                order.errors.zip = 'El zip tiene que tener 5 digitos';
                                hasErrors = true;
                            }

                            if (order.zip && isNaN(order.zip)) {
                                order.errors.zip = this.getErrorMessage('onlyNumbers');
                                hasErrors = true;
                            }


                            //Tipo de producto
                            if (!order.productType) {
                                order.errors.productType = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }


                            //Producto ( si es luz o gas se selecciona toda la ramificación de abajo y sino se puede añadir)
                            if (!order.product) {
                                order.errors.product = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }


                            //SI ES CONTRATO DE LUZ O DE GAS

                            //Tarifa
                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && !order.fee) {
                                order.errors.fee = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            //Comercializadora
                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && !order.marketer) {
                                order.errors.marketer = this.getErrorMessage('isEmpty');
                                hasErrors = true
                            }

                            //IBAN
                            if (
                                (order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') &&
                                lastStatus !== 'an' &&
                                !order.IBAN &&
                                this.basicData.userSubdomain.settings.orderIBAN
                            ) {
                                order.errors.IBAN = this.getErrorMessage('isEmpty');
                                hasErrors = true;
                            }

                            const ibanValidation = this.validateIban(order.IBAN);

                            if (order.IBAN && !ibanValidation.valid) {
                                order.errors.IBAN = ibanValidation.message;
                                hasErrors = true;
                            }

                            //CUPS
                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && !order.CUPS) {
                                order.errors.CUPS = this.getErrorMessage('isEmpty');
                                hasErrors = true;
                            }

                            const cupsValidation = this.validateCups(order.CUPS);

                            if (order.CUPS && !cupsValidation.valid) {
                                order.errors.CUPS = cupsValidation.message;
                                hasErrors = true;
                            }

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
                                order.errors.decommissions.electricity = {subdomain: null, breakdown: {}};

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
                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && order.verifications && order.verifications.includes('pc') && !order.currentPotencyVerification) {
                                order.errors.currentPotencyVerification = 'No puede estar vacía';
                                hasErrors = true
                            }

                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && order.verifications && order.verifications.includes('pc') && !!order.currentPotencyVerification && isNaN(order.currentPotencyVerification)) {
                                order.errors.currentPotencyVerification = 'Debe ser numerico';
                                hasErrors = true
                            }

                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && order.verifications && order.verifications.includes('pc') && !order.requestedPotencyVerification) {
                                order.errors.requestedPotencyVerification = 'No puede estar vacía';
                                hasErrors = true
                            }


                            if ((order.productType === 'cl' || order.productType === 'cg' || order.productType === 'cd') && order.verifications && order.verifications.includes('pc') && !!order.requestedPotencyVerification && isNaN(order.requestedPotencyVerification)) {
                                order.errors.requestedPotencyVerification = 'Debe ser numerico';
                                hasErrors = true
                            }


                            //Verificaciones si es alta nueva
                            if (order.verifications && order.verifications.includes('nw')) {

                                if (order.productType === 'cl') {

                                    //Si tarifa es 2.0TD
                                    if (order.fee === 'Tarifa 2.0TD') {

                                        //miro p1 y p2
                                        for (let i = 1; i <= 2; i++) {

                                            if (!!order.newRegistrationPeriods && order.newRegistrationPeriods['p' + i] === '') {
                                                order.errors['periodVerification' + i] = 'No puede estar vacía';
                                                hasErrors = true
                                            }

                                            if (!!order.newRegistrationPeriods && isNaN(order.newRegistrationPeriods['p' + i])) {
                                                order.errors['periodVerification' + i] = 'Debe ser numerico';
                                                hasErrors = true
                                            }
                                        }

                                    } else {

                                        //miro de p1 a p6
                                        for (let i = 1; i <= 6; i++) {

                                            if (!!order.newRegistrationPeriods && order.newRegistrationPeriods['p' + i] === '') {
                                                order.errors['periodVerification' + i] = 'No puede estar vacía';
                                                hasErrors = true
                                            }

                                            if (!!order.newRegistrationPeriods && isNaN(order.newRegistrationPeriods['p' + i])) {
                                                order.errors['periodVerification' + i] = 'Debe ser numerico';
                                                hasErrors = true
                                            }
                                        }

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
                                if (!order.CUPSSecondary) {
                                    order.errors.CUPSSecondary = this.getErrorMessage('isEmpty');
                                    hasErrors = true;
                                }

                                const cupsSecondaryValidation = this.validateCups(order.CUPSSecondary);

                                if (order.CUPSSecondary && !cupsSecondaryValidation.valid) {
                                    order.errors.CUPSSecondary = cupsSecondaryValidation.message;
                                    hasErrors = true;
                                }
                            }
                        }

                        const hasAnyValue = (value) => {
                            if (value === null || value === undefined) {
                                return false;
                            }

                            if (Array.isArray(value)) {
                                return value.some(hasAnyValue);
                            }

                            if (typeof value === 'object') {
                                return Object.values(value).some(hasAnyValue);
                            }

                            if (typeof value === 'string') {
                                return value.trim() !== '';
                            }

                            return true;
                        };

                        //Si no hay errores restablezco el objeto
                        if(!hasAnyValue(order.errors)){
                            order.errors = {};
                        }



                        //Documentos de pedido

                    })
                }



                //Si no hay errores guardo, sino los muestro
                if (!hasErrors) {
                    // LIMPIAR IDs TEMPORALES ANTES DE GUARDAR
                    this.account.orders.forEach(order => {
                        const oid = order._id?.$oid || order._id;

                        if (typeof oid === 'string' && oid.startsWith('tmp_')) {
                            delete order._id;
                        }
                    });


                    let data = new FormData();

                    data.append('account', JSON.stringify(this.account));
                    data.append('enterprise', JSON.stringify(this.basicData.enterprise));
                    data.append('userSubdomain', JSON.stringify(this.basicData.userSubdomain));
                    data.append('colors', JSON.stringify(this.getAllColorVariables()));

                    //Busco los campos personalizados que sean tipo imagen
                    if (this.account.customFields)
                        this.account.customFields.forEach((field, fieldInd) => {
                            if (field.type === 'image')
                                data.append(('customFieldFile' + fieldInd), field.value);
                        })




                   axios.post('/api/accounts/update', data)
                       .then(async (res) => {

                        let docsUploadError = false;

                        const updatedAccount =
                            res.data?.account ||
                            res.data?.data ||
                            res.data;

                        const normalizeId = (id) => {
                            const oid = id?.$oid ?? id;
                            return typeof oid === 'string' ? oid : null;
                        };

                        if (Array.isArray(updatedAccount?.orders)) {

                            for (let i = 0; i < updatedAccount.orders.length; i++) {

                                const backendOrder = updatedAccount.orders[i];
                                const frontendOrder = this.account.orders[i];

                                const orderId = normalizeId(backendOrder?._id);

                                if (!orderId) continue;

                                const docsFiles = [];

                                if (Array.isArray(frontendOrder?.docs)) {
                                    frontendOrder.docs.forEach(doc => {
                                        if (
                                            doc?.fileValue &&
                                            typeof doc.fileValue === 'object' &&
                                            typeof doc.fileValue.size === 'number'
                                        ) {
                                            docsFiles.push(doc.fileValue);
                                        }
                                    });
                                }

                                if (docsFiles.length === 0) continue;

                                const fd = new FormData();
                                frontendOrder.docs.forEach(doc => {
                                    if (
                                        doc?.fileValue &&
                                        typeof doc.fileValue === 'object' &&
                                        typeof doc.fileValue.size === 'number'
                                    ) {
                                        fd.append('files[]', doc.fileValue);
                                        fd.append('titles[]', doc.title || '');
                                    }
                                });
                                try {
                                    await axios.post(
                                        `/api/orders/${orderId}/upload-document`,
                                        fd
                                    );
                                } catch (e) {
                                    docsUploadError = true;
                                    console.error('Error subiendo docs:', e);
                                }
                            }
                        }

                        await this.fetchAccount();


                        this.isUpdating = false;
                        this.canUpdateAcc = true;
                        this.selectedOrder = '';
                        this.selectedOrderInd = '';
                        this.isEditing = false;

                        if (docsUploadError) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Aviso',
                                text: 'Se ha actualizado correctamente, pero algunos documentos no se han adjuntado.'
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cuenta actualizada',
                                timer: 1500
                            });
                        }

                                        })
                    .catch((error) => {
                        this.isUpdating = false;
                        this.canUpdateAcc = true;

                        const status = error.response?.status;
                        const data = error.response?.data || {};

                        if (status === 400 && data.cifError) {
                            this.errors.CIF = data.cifError;

                            Swal.fire({
                                icon: 'error',
                                title: 'No se ha podido guardar',
                                text: data.cifError,
                                confirmButtonText: 'Entendido'
                            });

                            return;
                        }

                        if (status === 422) {
                            if (data.errors?.CIF) {
                                this.errors.CIF = data.errors.CIF;
                            }

                            if (data.errors?.orders) {
                                Object.keys(data.errors.orders).forEach((orderIndex) => {
                                    const orderErrors = data.errors.orders[orderIndex];

                                    if (this.account.orders[orderIndex]) {
                                        if (!this.account.orders[orderIndex].errors) {
                                            this.account.orders[orderIndex].errors = {};
                                        }

                                        Object.keys(orderErrors).forEach((field) => {
                                            this.account.orders[orderIndex].errors[field] = orderErrors[field];
                                        });
                                    }
                                });
                            }

                            Swal.fire({
                                icon: 'error',
                                title: 'Datos no válidos',
                                text: data.message || data.cifError || data.error ||'Revise el DNI/CIF, IBAN o CUPS antes de guardar.',
                                confirmButtonText: 'Entendido'
                            });

                            return;
                        }

                        console.error(error);

                        Swal.fire({
                            icon: 'error',
                            title: 'Error al guardar',
                            text: 'No se ha podido guardar la cuenta. Inténtelo de nuevo.',
                            confirmButtonText: 'Entendido'
                        });
                    })


                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Comprueba la cuenta',
                        text: 'Hay errores en el formulario, revisa bien la cuenta y los pedidos relacionados con la cuenta'
                    })

                    this.isUpdating = false
                    this.canUpdateAcc = true
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
                                    let key = prop.replace('--color-', '');
                                    let value = style.getPropertyValue(prop).trim();
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
        fetchAccount() {
            return axios.get(`/api/accounts/${this.$route.params.id}`)
                .then(res => {
                    this.accountDefect = JSON.parse(JSON.stringify(res.data.account))
                    this.account = res.data.account
                    this.filterAccount()
                    if (this.account.opportunity) this.fetchOpportunity()
                })
                .catch(err => console.log(err))
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
        injectOrderFromOpportunity() {
            const tmp = localStorage.getItem('temporalyCreateAcc')
            if (!tmp) return
            const opp = JSON.parse(tmp)
            this.addOrder()
            const idx = this.account.orders.length - 1
            const order = this.account.orders[idx]
            Object.assign(order, {
                productType: opp.order.productType || '',
                marketer: opp.order.marketer || '',
                fee: opp.order.fee || '',
                product: opp.order.product || '',
                CUPS: opp.order.CUPS || '',
                direc: opp.order.direc || '',
                province: opp.order.province || '',
                town: opp.order.town || '',
                zip: opp.order.zip || '',
                name: opp.name + (opp.order.CUPS ? ' - ' + opp.order.CUPS.slice(-6) : '')
            })
            this.selectedOrder = order
            this.selectedOrderInd = idx
            localStorage.removeItem('temporalyCreateAcc')
        },
        filterAccount() {


            //Si estan nulos reestablezco los siguientes valores

            //GEO
            if (this.account && this.account.billingInfo.community.code === null) {

                this.account.billingInfo.community = {
                    code: '',
                    name: ''
                }
            }

            if (this.account && this.account.billingInfo.province.code === null) {
                this.account.billingInfo.province = {
                    code: '',
                    name: ''
                }
            }

            if (this.account && this.account.billingInfo.locality.code === null) {
                this.account.billingInfo.locality = {
                    code: '',
                    name: ''
                }
            }

            if (this.account && this.account.billingInfo.addressFirst === null) this.account.billingInfo.addressFirst = ''

            //Tipo de cuenta y sector
            if (this.account && this.account.accType === null) this.account.accType = ''

            if (this.account && this.account.sector === null) this.account.sector = ''

            //Cuenta principal
            if (this.account && this.account.principalAcc === null) this.account.principalAcc = ''

            //A cada pedido le meto un newStatus si no lo tiene para poder cambiarlo
            if (this.account.orders) {
                this.account.orders.forEach((order) => {
                    if (!order.newStatus) order.newStatus = { code: '', date: '' }
                })
            }
        },
        selectAccount(account) {
            this.account.principalAcc = account._id

            this.searchAccountText = '';
        },
        async getCommunities() {

            await axios.get('/api/GEO/getCommunities')
                .then((res) => {
                    this.GEO.communities = res.data.communities;
                })
                .catch((err) => {
                    console.log(err)
                });

        },
        async getProvinces() {

            await axios.get(`/api/GEO/getProvinces/${this.account.billingInfo.community}`)
                .then((res) => {
                    this.GEO.provinces = res.data.provinces;
                })
                .catch((err) => {
                    console.log(err)
                });
        },
        getVersionsCount(order) {
            if (!order?.CUPS) return 1;

            return this.account.orders.filter(o =>
                o.CUPS === order.CUPS && !o._isTemp
            ).length;
        },
        async getLocalities() {

            await axios.get(`/api/GEO/getLocalities/${this.account.billingInfo.province}`)
                .then((res) => {
                    this.GEO.localities = res.data.localities.sort((a, b) => a.mun_name.localeCompare(b.mun_name));;
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async searchAddress() {
            await axios.get(`http://apiv1.geoapi.es/qcalles?QUERY=${this.searchAddressText}&type=JSON&key=7bf1d1b4d597394430abc701411697c9a95aceca96efccb0e81cbd2451d3c6ce`)
                .then((res) => {
                    this.GEO.addresses = res.data.data;
                })
                .catch((err) => {
                    console.log(err)
                });
        },
        selectCommunity(event) {
            delete this.errors['billingInfo']['community']

            let communityCode = event.target.value

            if (communityCode !== '') {

                //Busco cual es para sacar el nombre
                let community = this.GEO.communities.find((community) => {
                    return community.acom_name === communityCode
                })

                this.account.billingInfo.community = community.acom_name;
            } else {
                this.account.billingInfo.community = '';
            }


            //Deselecciono la provincia y la localidad
            this.account.billingInfo.province = '';

            this.account.billingInfo.locality = '';

            //Borro los registros
            this.GEO.localities = '';

            this.getProvinces();
        },
        selectProvince(event) {
            delete this.errors['billingInfo']['province']

            let provinceCode = event.target.value

            if (provinceCode !== '') {
                //Busco cual es para sacar el nombre
                let province = this.GEO.provinces.find((province) => {
                    return province.prov_name === provinceCode
                })

                this.account.billingInfo.province = province.prov_name;
            } else {
                this.account.billingInfo.province = '';
            }


            this.account.billingInfo.locality = '';

            this.getLocalities();
        },
        selectLocality(event) {
            delete this.errors['billingInfo']['locality']

            let localityCode = event.target.value

            if (localityCode !== '') {
                //Busco cual es para sacar el nombre
                let locality = this.GEO.localities.find((locality) => {
                    return locality.mun_name === localityCode
                })

                this.account.billingInfo.locality = locality.mun_name;
            } else {
                this.account.billingInfo.locality = '';
            }

        },
        selectAddress(address) {

            this.account.billingInfo.addressFirst = address.TVIA + ' ' + address.NVIAC + ' (' + address.NENTSI50 + ')';

            this.searchAddressText = '';
            this.GEO.addresses = '';
        },
        async searchAccounts() {

            await axios.post(`/api/contacts/getAccountsRelated/${this.basicData.userLogged._id}`, { userList: this.basicData.userList })
                .then((res) => {
                    this.accounts = res.data.accounts;
                })
                .catch((err) => {
                    console.log(err)
                });
        },
        getErrorMessage(type) {

            let message = '';

            switch (type) {
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
        addCustomField() {

            let customField = {
                title: '',
                type: 'text',
                value: ''
            }

            this.account.customFields.push(customField);
        },
        delCustomField(fieldInd) {
            this.account.customFields.splice(fieldInd, 1);
        },
        addCustomFields(files) {
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
        fetchSelectValues() {

            axios.get(`/api/select/`)
                .then((res) => {
                    this.selectValues = res.data.selectValues;

                    //Si no se hay todavia un registro para el usuario se crea un array temporal
                    if (!this.selectValues) {
                        this.selectValues = {
                            'acc': [],
                            'sector': [],
                            'origin': [],
                            'orderSources': [],
                            'marketerProducts': [],
                        }
                    }
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        addSelectType(data) {

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
                        text: 'El elemento que estas intentando crear ya existe'
                    })
                })
        },
        delSelectType(data) {

            let type = data.type;
            let value = data.value;

            //Si se ha seleccionado uno y no la opción de eliminar en blanco
            if (value) {

                Swal.fire({
                    icon: 'warning',
                    title: '¿Estás seguro?',
                    text: 'Seguro que quieres borrarlo? Tu acción no podrá revertirse',
                    confirmButtonText: 'Sí',
                    showCancelButton: true,
                    cancelButtonText: 'No'
                }).then((res) => {
                    if (res.isConfirmed) {

                        //Borro
                        axios.delete(`/api/select`, {
                            params: {
                                type: type,
                                value: value
                            }
                        })
                            .then((res) => {
                                //Saco el valor de cliente
                                let index = this.selectValues[type].indexOf(value);

                                if (index !== -1) this.selectValues[type].splice(index, 1);

                                //Si la cuenta tiene seleccionada esa opción la desmarco y borro de local
                                if (type === 'acc') {
                                    if (this.account.accType === value) this.account.accType = ''
                                } else if (type === 'sector') {
                                    if (this.account.sector === value) this.account.sector = ''
                                } else {
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
        selectElement(data) {
            let type = data.type;
            let value = data.value;

            let arrInd = type

            if (type === 'acc') arrInd = 'accType'

            this.account[arrInd] = value
        },
        addOrder() {
            const isFidelitySubdomain = this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2';

            let customField = {
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
                usersIds: (this.basicData.userLogged._id === '683d658761231bd1080b4802' ? [...this.account.usersIds] : [this.account.usersIds[0]]),
                errors:{}
            }

            this.account.orders.push(customField);

            //Abro el contenedor con el pedido
            this.selectedOrder = JSON.parse(JSON.stringify(this.account.orders[this.account.orders.length - 1]))
            this.selectedOrderInd = this.account.orders.length - 1;
        },
        delOrder(orderId) {

            Swal.fire({
                icon: 'warning',
                title: 'Estás seguro?',
                text: 'Si guardas los cambios de la cuenta no se podrá recuperar el pedido',
                confirmButtonText: 'Sí',
                showCancelButton: true,
                cancelButtonText: 'No'
            }).then((res) => {

                if (res.isConfirmed) {

                    const index = this.account.orders.findIndex(order =>
                        (order._id?.$oid || order._id) === orderId
                    );

                    if (index !== -1) {
                        this.account.orders.splice(index, 1);
                    } else {
                        console.warn('Pedido no encontrado:', orderId);
                    }
                }
            });
        },
        selectOrder(id) {
            let orderInd = this.account.orders.findIndex((order) => {
                return ((!order._id?.$oid ? order._id : order._id.$oid) === id);
            });

            // Verifica si se encontró el índice
            if (orderInd !== -1) {
                // Establece el pedido seleccionado usando el índice encontrado
                this.selectedOrder = this.account.orders[orderInd];
                this.selectedOrderInd = orderInd;
            } else {
                // Maneja el caso en que no se encuentra el pedido
                this.selectedOrder = null;
                console.error("Order not found");
            }

            //Pongo el parametro
            this.$router.push({ query: { id } });

            this.$nextTick(() => {
                this.selectingFromUI = false
            })

        },
        selectOrderById() {
            const id = this.$route.query.id;
            if (!id) return;

            const orderInd = this.account.orders.findIndex(order => {
                return (order._id?.$oid || order._id) === id;
            });

            if (orderInd !== -1) {
                this.selectedOrder = this.account.orders[orderInd];
                this.selectedOrderInd = orderInd;
            } else {
                this.selectedOrder = '';
                this.selectedOrderInd = '';
                console.error('Order not found for id:', id);
            }
        },
        /*createOrderAndSaveAcc(orderModified){
            this.account.orders[this.selectedOrderInd] = orderModified;
            //actualizo la cuenta
            this.updateAccount()
            this.isEditing = false
        },*/
        createOrder(payload) {
            this.account.orders[this.selectedOrderInd] = payload.order;
            this.canUpdateAcc = false;
            this.updateAccount('order');
        },
        closeWindow(orderModified) {

            //Compruebo si tiene al menos un campo relleno, sino se borra
            if (this.areAllFieldsEmpty(orderModified)) {
                this.account.orders.splice(this.selectedOrderInd, 1) //lo borro
            } else {
                this.account.orders[this.selectedOrderInd] = orderModified;
            }

            this.selectedOrder = ''
            this.selectedOrderInd = '';

            this.$router.replace({ query: {} });
        },
        areAllFieldsEmpty(obj, exceptions = ['liquidationStatus', 'newStatus', 'transferDate', 'errors']) {
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
        deleteOrder() {

            this.account.orders.splice(this.selectedOrderInd, 1)

            this.selectedOrder = '';
            this.selectedOrderInd = '';
        },
        getInitials(name) {

            if (name) {
                let nameSplited = name.split(/\s+/)

                let initials = nameSplited[0][0];

                if (nameSplited[1])
                    initials += nameSplited[1][0];

                return initials
            }
        },
        formatDate(date) {
            return moment(date).format('DD/MM/YY HH:mm')
        },
        getTaskProgress(task) {

            if (task.isCompleted) {
                return 100
            } else {

                if (task.subTasks && task.subTasks.length > 0) {
                    let completedTaskNum = 0;

                    task.subTasks.forEach((subTask) => {
                        if (subTask.isCompleted) completedTaskNum++
                    })

                    return ((completedTaskNum / task.subTasks.length) * 100)
                } else {
                    return 0
                }
            }
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
        toggleUser(user) {
            this.account.owner = this.account.owner === user._id ? '' : user._id
        },
        canDelete(code) {

            //Limitado solo a permiso ( solo lo tiene Asercord y admin )
            if (this.basicData.userLogged && this.basicData.userLogged.permissions)
                return this.basicData.userLogged.permissions.includes(code)
        },
        duplicateOrder(order) {
            let orderDuplicated = JSON.parse(JSON.stringify(order));

            delete orderDuplicated.pricesProduct;


            orderDuplicated._id = {
                $oid: 'tmp_' + Date.now() + '_' + Math.random().toString(36).slice(2)
            };
            orderDuplicated._isTemp = true;



            // --- lógica normal ---
            const baseName = orderDuplicated.name.replace(/ - copia(?: \d+)?$/, '');
            const copyRegex = new RegExp(`^${baseName} - copia(?: (\\d+))?$`);

            let maxCopyNumber = 0;
            this.account.orders.forEach(existing => {
                const match = existing.name.match(copyRegex);
                if (match) {
                    const n = match[1] ? parseInt(match[1], 10) : 1;
                    maxCopyNumber = Math.max(maxCopyNumber, n);
                }
            });

            orderDuplicated.name =
                maxCopyNumber > 0
                    ? `${baseName} - copia ${maxCopyNumber + 1}`
                    : `${baseName} - copia`;

            orderDuplicated.transferDate = moment().format('DD/MM/YY');
            orderDuplicated.processingDate = '';
            orderDuplicated.activationDate = '';
            orderDuplicated.lowDate = '';
                orderDuplicated.newStatus.code =
                    this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2' &&
                    !this.canManage('contracts.processor')
                        ? 'bo'
                        : 'p';
            orderDuplicated.liquidationStatus = 'nl';
            orderDuplicated.statuses = [];
            orderDuplicated.errors = {};

            this.account.orders.push(orderDuplicated);
        },
        toggleEditMode() {
            this.isEditing = !this.isEditing;
        },
        toggleSelectUserInOrders(id){

            const DOIVE = '683d658761231bd1080b4802'
            const EXCEPT = '65cb57489c2c285441086a43'

            this.account.orders.forEach(order => {

                let next = [...(order.usersIds || [])]
                const idx = next.indexOf(id)

                if (idx !== -1) {
                    next = next.filter(u => u !== id)
                } else {
                    if (this.basicData.userLogged._id !== DOIVE && next.some(u => u !== EXCEPT)) {
                        next = next.filter(u => u === EXCEPT)
                    }

                    next.push(id)
                }

                order.usersIds = next
            })

        },
        toggleEditingContract(type, contractId){//Función para añadir que un contrato se está editando

            switch (type) {
                case 'add':
                    // Comprueba si está añadido y si no lo mete
                    if (!this.ordersEditing.includes(contractId))
                        this.ordersEditing.push(contractId);

                    break;

                case 'substract':
                    // Si existe lo borro
                    const contractIndex = this.ordersEditing.indexOf(contractId);
                    if (contractIndex !== -1)
                        this.ordersEditing.splice(contractIndex, 1);

                    break;
            }

        },
        catchOrderFiles(fileData, orderId){
            //Guardo en orderFiles los archivos, primero los distribuyo por contrato y luego por id archivo o los meto directamente

            //Si no existe el índice del contrato en el array lo creo
            if (!this.orderFiles[orderId])
                this.orderFiles[orderId] = []

            this.orderFiles[orderId].push(fileData)
        },
        actionLink(route) {
            this.$router.push(route)
        },
        showOrdersAvailable()
        {
            if (!this.account.naturgyCallSID) {
                let htmlList = this.naturgyCallOrders.map(c => `
                <div style="text-align:left; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                  <strong>${c.name}</strong>
                  <span style="font-size:12px; opacity:0.5;">
                    (CUPS: ${c.CUPS.slice(-6)})
                  </span>
                </div>
              `).join('');

                Swal.fire({
                    title: 'Contratos para llamada',
                    html: `<div style="max-height:300px; overflow:auto;">
                        ${htmlList}
                      </div>`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Cancelar',
                    width: 600
                }).then((res) => {
                    if (res.isConfirmed) {
                        this.startVerificationCall()
                    }
                });
            } else {
                this.startVerificationCall()
            }

        },
        handleCall() {
            if (!this.account.naturgyCallSID && !this.account.naturgyCallRecordingUrl)
                this.startVerificationCall()
            else {
                Swal.fire({
                    title: '¿Qué deseas hacer?',
                    icon: 'question',
                    showConfirmButton: this.showConfirmButton,
                    showCancelButton: this.showCancelButton,
                    showDenyButton: (this.basicData.userLogged && (this.basicData.userLogged.email === 'soporte@zocoenergia.com' || this.basicData.userLogged.email === 'franperez@segenet.es')),
                    confirmButtonText: 'Volver a llamar',
                    denyButtonText: 'Ir a grabación',
                    cancelButtonText: 'Descargar y adjuntar',
                }).then((result) => {
                    if (result.isConfirmed) {
                        let htmlList = this.naturgyCallOrders.map(c => `
                        <div style="text-align:left; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                      <strong>${c.name}</strong>
                      <span style="font-size:12px; opacity:0.5;">
                        (CUPS: ${c.CUPS.slice(-6)})
                      </span>
                    </div>
                        `).join('');

                        Swal.fire({
                            title: 'Contratos para llamada',
                            html: `<div style="max-height:300px; overflow:auto;">
                        ${htmlList}
                      </div>`,
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: 'Continuar',
                            cancelButtonText: 'Cancelar',
                            width: 600
                        }).then((res) => {
                            if (res.isConfirmed) {
                                this.startVerificationCall();
                            }
                        });

                    } else if (result.isDenied) {
                        // Acción del segundo botón (deny)
                        window.open(
                            `https://console.twilio.com/us1/monitor/logs/calls?frameUrl=%2Fconsole%2Fvoice%2Fcalls%2Flogs%2F${this.account.naturgyCallSID}%3Fx-target-region%3Dus1%26__override_layout__%3Dembed%26bifrost%3Dtrue`,
                            '_blank'
                        );
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // Acción del tercer botón
                        // Ejemplo: descargar y adjuntar al contrato
                        axios.post('/api/twilio/downloadNaturgyCall', { account: this.account })
                            .then((res) => {
                                //Recargo cuenta
                                this.fetchAccount()

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Llamada adjuntada',
                                    text: 'La llamada ha sido adjuntada a los documentos del contrato correctamente',
                                    timerProgressBar: true,
                                    timer: 1500
                                })
                            })
                            .catch((err) => {
                                console.log(err)
                            })

                    }
                });
            }
        },
        async canStartCall() {
            try {
                const response = await axios.get('/api/twilio/availableCallMinutes', {
                    params: {
                        enterpriseId: this.basicData?.subdomainEnterprise?._id
                    }
                });

                if (response.data.available) return true;

                Swal.fire({
                    icon: 'error',
                    title: 'Sin minutos disponibles',
                    text: 'No dispone de minutos de llamadas disponibles. Compre un pack de minutos para poder realizar llamadas.',
                    confirmButtonText: 'Vale'
                });

                return false;

            } catch (error) {
                console.log(error);

                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo comprobar el saldo',
                    text: 'Inténtelo de nuevo en unos segundos.',
                    confirmButtonText: 'Vale'
                });

                return false;
            }
        },
        async startVerificationCall() {

            const canStart = await this.canStartCall();
            if (!canStart) return;

            Swal.fire({
                icon: 'question',
                text: '¿Qué tipo de llamada quieres realizar?',
                confirmButtonText: 'Bot',
                showDenyButton: true,
                denyButtonText: 'Personal'
            }).then((res) => {
                if(res.isConfirmed){
                    let htmlList = this.naturgyCallOrders.map(c => `
                    <div style="text-align:left; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                      <strong>${c.name}</strong>
                      <span style="font-size:12px; opacity:0.5;">
                        (CUPS: ${c.CUPS.slice(-6)})
                      </span>
                    </div>
                  `).join('');

                    Swal.fire({
                        title: 'Contratos para llamada',
                        html: `<div style="max-height:300px; overflow:auto;">
                        ${htmlList}
                      </div>`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Continuar',
                        cancelButtonText: 'Cancelar',
                        width: 600
                    }).then((res) => {
                        if (res.isConfirmed) {
                            Swal.fire({
                                title: '¿A quién va dirigida la llamada?',
                                html: `
                                    <input id="swal-name" class="swal2-input custom-input" placeholder="Nombre de la persona">
                                    <input id="swal-company" class="swal2-input custom-input" placeholder="Empresa">
                                `,
                                confirmButtonText: 'Llamar',
                                customClass: {
                                    popup: 'swal-wide'
                                },
                                preConfirm: () => {
                                    const name = document.getElementById('swal-name').value;
                                    const company = document.getElementById('swal-company').value;

                                    if (!name || !company) {
                                        Swal.showValidationMessage('Debe rellenar ambos campos');
                                        return false;
                                    }

                                    return { name, company };
                                }
                            }).then((res) => {

                                if (!!res.value && res.value !== '') {

                                    let { name, company } = res.value;

                                    axios.post('/api/twilio/startCall', { orderList: this.naturgyCallOrders, account: this.account, name, company, enterprise: this.basicData.subdomainEnterprise })
                                        .then((res) => {
                                            this.account.naturgyCallSID = res.data.naturgyCallSID;
                                            this.naturgyCallStatus = this.$storage.TWILIO_CALL_STATUSES.find(status => status.code === 'ringing');
                                        })
                                        .catch((err) => {
                                            console.log(err)
                                        })

                                }
                            })
                        }
                    });
                }
                else if(res.isDenied){
                    startCall('+34'+this.account.phone, this.account.name, this.account._id, false)
                }
            })
        },
        async fetchNaturgyVerificationStatus() {
            axios.post('/api/twilio/getCallStatus', { account: this.account })
                .then((res) => {
                    let temporalStatus = res.data.status;
                    this.naturgyCallStatus = this.$storage.TWILIO_CALL_STATUSES.find(status => status.code === temporalStatus);
                })
                .catch((err) => {
                    if (this.basicData.userLogged.email === 'soporte@zocoenergia.com' || this.basicData.userLogged.email === 'franperez@segenet.es') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al recuperar estado',
                            html: 'La llamada no ha sido encontrada, por lo que puede ser que se haya cambiado la cuenta desde la que se hizo la llamada </br></br> Error: </br>' + err.response.data.error
                        })
                    }
                })
        },
        renewalOrder(newOrder) {
            this.account.orders.push(newOrder)
            this.selectedOrderInd = this.account.orders.length - 1;
            this.selectedOrder = this.account.orders[this.selectedOrderInd]
            this.isEditingOrder = true
        }
    },
    computed: {
        ordersToShow() {
            if (!this.account?.orders?.length) return [];

            const drafts = this.account.orders.filter(o => o._isTemp);

            const saved = this.account.orders.filter(o => !o._isTemp);

            const withCUPS = {};
            const withoutCUPS = [];

            saved.forEach(order => {
                if (!order.CUPS) {
                    withoutCUPS.push(order);
                } else {
                    if (!withCUPS[order.CUPS]) withCUPS[order.CUPS] = [];
                    withCUPS[order.CUPS].push(order);
                }
            });

            //Agrupo por CUPS: saco la versión actual (según getCurrentOrderByCups) y dejo el resto
            //como versiones anteriores para el desplegable
            const versionedGroups = Object.entries(withCUPS).map(([cups, group]) => {
                const current = getCurrentOrderByCups(group);

                if (!current) return null;

                const currentId = current._id?.$oid || current._id;

                const otherVersions = group
                    .filter(o => (o._id?.$oid || o._id) !== currentId)
                    .reverse();

                return {
                    order: current,
                    versions: otherVersions,
                    cups
                };
            }).filter(Boolean);

            const draftItems = drafts.map(order => ({ order, versions: [], cups: null }));
            const withoutCUPSItems = withoutCUPS.map(order => ({ order, versions: [], cups: null }));

            let result = [...draftItems, ...versionedGroups, ...withoutCUPSItems];

            if (this.searchOrderText) {
                const q = this.searchOrderText.toLowerCase();
                result = result.filter(item =>
                    item.order.name?.toLowerCase().includes(q) ||
                    item.order.CUPS?.toLowerCase().includes(q)
                );
            }

            return result;
        },
        versionsErrorsByCUPS() {
            const map = {};

            const normalize = v => (v || '').toString().trim().toUpperCase();

            this.account.orders.forEach(order => {
                if (!order?.CUPS) return;

                const related = this.account.orders.filter(o =>
                    normalize(o.CUPS) === normalize(order.CUPS) && !o._isTemp
                );

                map[order.CUPS] =
                    related.length > 1 &&
                    related.some(o =>
                        o.errors && Object.keys(o.errors).length > 0
                    );
            });

            return map;
        },
        profileImage() {
            return this.urlImage;
        },
        accountSelected() {
            if (this.account.principalAcc) {
                return this.accounts.find((account) => {
                    return account._id === this.account.principalAcc
                })
            }
        },
        filteredAccounts() {

            let accounts = []

            if (this.searchAccountText === '') {
                accounts = this.accounts;
            } else {
                this.accounts.filter((account) => {

                    let AccountFiltered = account.name.replace(' ', '').toLowerCase().normalize('NFC');

                    if (AccountFiltered.includes(this.searchAccountText.replace(' ', '').toLowerCase().normalize('NFC')))
                        accounts.push(account);
                })
            }

            //Ordeno
            accounts = accounts.sort((a, b) => a.name.localeCompare(b.name))

            return accounts;
        },
        filteredOrders() {

            //Si no hay devuelve un array vacio
            if (!this.account.orders || this.account.orders.length === 0) return []


            let ordersFiltered = []

            let orders = JSON.parse(JSON.stringify(this.account.orders))

            //filtro
            for (let key in orders) {

                let order = orders[key];

                let OrderSearch = [order.name].join('').replace(' ', '').toLowerCase();

                if (OrderSearch.includes(this.searchOrderText.replace(' ', '').toLowerCase())) ordersFiltered.push(order)
            }

            return ordersFiltered
        },
        isReadonly() {
            if (!this.basicData.userLogged)
                return true
            else (this.basicData.userLogged)
            return this.basicData.userLogged.permissions.includes('READONLY')
        },
        accountContractLockSubdomainId() {
            return '6909faa9232c09035a03f3b2'
        },
        shouldApplyAccountContractLock() {
            return this.basicData?.userSubdomain?._id === this.accountContractLockSubdomainId
        },
        allowedToEditAccountWithContracts() {
            // Bypass: solo usuarios "sin jerarquía" (permiso users.admiWhiHier)
            return this.canManage('users.admiWhiHier')
        },
        currentAccountId() {
            const id = this.account?._id?.$oid || this.account?._id || this.$route?.params?.id
            return typeof id === 'string' ? id : null
        },
        normalizeMongoId() {
            return (id) => {
                const value = id?._id?.$oid || id?._id || id?.$oid || id
                return typeof value === 'string' ? value : null
            }
        },
        accountHasRelatedContracts() {
            if (!this.currentAccountId) return false

            return Array.isArray(this.ordersAll) && this.ordersAll.some(order => {
                return this.normalizeMongoId(order.account) === this.currentAccountId
            })
        },
        isAccountLockedByContracts() {
            return this.shouldApplyAccountContractLock
                && this.accountHasRelatedContracts
                && !this.allowedToEditAccountWithContracts
        },
        canOpenAccountEditMode() {
            if (!this.basicData?.userLogged) return false
            if (this.isReadonly) return false

            return this.canManage('accounts.edit') || this.canManage('contracts.create')
        },
        canAddContractsToAccount() {
            if (!this.basicData?.userLogged) return false
            if (this.isReadonly) return false
            if (!this.isEditing) return false

            return this.canManage('contracts.create')
        },
        isSelectedOrderNew() {
            const id = this.selectedOrder?._id?.$oid || this.selectedOrder?._id

            return !id
                || this.selectedOrder?._isTemp === true
                || (typeof id === 'string' && id.startsWith('tmp_'))
        },
        canSaveNewContractOnLockedAccount() {
            return this.isAccountLockedByContracts
                && this.isSelectedOrderNew
                && this.canAddContractsToAccount
        },
        canShowSaveButton() {
            if (!this.basicData?.userLogged) return false
            if (this.isReadonly) return false
            if (this.isCheckingAccountContracts) return false

            return !this.isInputsDisabled || this.canSaveNewContractOnLockedAccount
        },
        isOrderDetailsDisabled() {
            if (!this.basicData?.userLogged) return true
            if (this.isCheckingAccountContracts) return true
            if (this.isReadonly) return true
            if (!this.isEditing) return true

            return false
        },
        isInputsDisabled() {
            if (!this.basicData?.userLogged) return true
            if (this.isCheckingAccountContracts) return true
            if (this.isReadonly) return true
            if (!this.isEditing) return true

            return false
        },
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
        canEditNameAndCIF() {
            // Bloqueo EXCLUSIVO de Fidelity (subdominio Fidelity + cuenta con contratos + sin users.admiWhiHier)
            return !this.isAccountLockedByContracts;
        },
        naturgyCallOrders() {
            let getLastStatus = (statuses) => {
                return statuses.reduce((latest, current) => {
                    return current.date > latest.date ? current : latest;
                });
            }
            return this.ordersToShow
                .map(item => item.order)
                .filter(orderNow => orderNow.marketer?.trim().toLowerCase() === 'naturgy' && orderNow.statuses.length > 0 && getLastStatus(orderNow.statuses).code === 'f' && orderNow.CUPS && orderNow.CUPS !== '')
        },
        showConfirmButton() {
            if (!this.account) return false;
            return this.account?.naturgyCallRecordingUrl || this.naturgyCallStatus?.code !== 'ringing'
        },

        showCancelButton() {
            if (!this.account) return false;
            return this.account?.naturgyCallRecordingUrl || (this.account?.naturgyCallSID && this.naturgyCallStatus && !['no-answer', 'canceled', 'failed', 'busy', 'queued', 'ringing'].includes(this.naturgyCallStatus.code))
        }
    }
}
</script>

<style>
/* Hace el modal más ancho */
.swal-wide {
    width: 500px !important;
}

/* Inputs más grandes */
.custom-input {
    width: 90% !important;
    padding: 14px !important;
    font-size: 16px !important;
}

/* Placeholder más pequeño */
.custom-input::placeholder {
    font-size: 13px;
    opacity: 0.7;
}

.swal2-html-container {
    overflow: visible !important;
    margin: 0 !important;
}

.swal2-popup {
    padding-bottom: 20px !important;
}

/* Desplegable de versiones anteriores de un contrato */
.versions-list {
    border-left: 2px solid rgba(0, 0, 0, 0.1);
    padding-left: 10px;
}
</style>