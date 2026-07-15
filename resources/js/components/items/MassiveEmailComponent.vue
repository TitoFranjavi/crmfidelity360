<template>
    <div v-show="basicData && basicData.userList && accounts" v-on:click="closeAll">

        <!--Si no se seleccionado ninguna opción extra-->
        <div v-if="!extraToolSelectedCode">
            <!--Header usuarios seleccionados-->
            <div class="d-flex my-10">

                <!--Seleccionador-->
                <div class="w-70 relPos pt-50" data-gap="5">

                    <div class="d-flex justify-between align-center f-wrap p-20" data-gap="10">
                        <!--Agentes seleccionados-->
                        <div class="resume-card" v-on:click.stop="toggleSelectedDisplay('agents')" >

                            <!--icono-->
                            <div class="icon">
                                <i class="far fa-users"></i>
                            </div>


                            <div :class="['info']">

                                <p class="number">{{ agentsSelectedCount }}<span class="total">/{{ basicData.userList.length }}</span></p>

                                <p class="text">
                                    agentes
                                </p>

                            </div>

                        </div>

                        <!--Cuentas seleccionadas-->
                        <div class="resume-card" v-on:click.stop="toggleSelectedDisplay('accounts')" >

                            <!--icono-->
                            <div class="icon">
                                <i class="far fa-buildings"></i>
                            </div>

                            <div :class="['info']">

                                <p class="number">{{ accountsSelectedCount }}<span class="total">/{{ accounts.length }}</span></p>

                                <p class="text">
                                    cuentas
                                </p>

                            </div>

                        </div>

                        <!--Contactos seleccionadas-->
                        <div class="resume-card" v-on:click.stop="toggleSelectedDisplay('contacts')" >

                            <!--icono-->
                            <div class="icon">
                                <i class="far fa-address-book"></i>
                            </div>

                            <div :class="['info']">

                                <p class="number">{{ contactsSelectedCount }}<span class="total">/{{ contacts.length }}</span></p>

                                <p class="text">
                                    contactos
                                </p>

                            </div>

                        </div>

                        <!--Correos añadidos-->
                        <div class="resume-card" v-on:click.stop="toggleSelectedDisplay('emails')" >

                            <!--icono-->
                            <div class="icon">
                                <i class="far fa-at"></i>
                            </div>


                            <div :class="['info']">

                                <p class="number">{{ emailsSelectedCount }}</p>

                                <p class="text">
                                    correos externos
                                </p>

                            </div>

                        </div>

                        <!--Seleccionador destinatarios-->
                        <transition name="fade-vertical">

                            <div v-if="recipientDisplaySelected" v-on:click.stop="" class="recipients-box form">
                                <p class="text" data-size="13">{{ recipientDisplaySelected === 'agents' ? 'Selecciona entre tus agentes' : (recipientDisplaySelected === 'accounts' ? 'Selecciona entre tus cuentas' : 'Añade correos externos') }}</p>

                                <!--Buscador-->
                                <form class="d-flex" @submit.prevent="addExternalEmail">
                                    <!--Buscador-->
                                    <div class="form-group">
                                        <div class="input-group">
                                            <input type="text" class="w-260-px" :placeholder="recipientDisplaySelected === 'agents' ? 'Busca por nombre, correo, teléfono, DNI' : (recipientDisplaySelected === 'accounts' ? 'Busca por nombre, CIF, correo, teléfono' : 'Escribe un nuevo correo')" v-model="searchText">
                                        </div>


                                    </div>

                                    <!--Selecc. todos -->
                                    <div class="d-flex ml-10" v-if="recipientDisplaySelected !== 'emails'">
                                        <!--Check-->
                                        <div class="custom-checkbox my-auto mr-10"  v-on:click="toggleSelectAll">
                                            <div v-bind:class="{ selected: recipientDisplaySelected === 'agents' ? allAgentsSelected : (recipientDisplaySelected === 'accounts' ? allAccountsSelected : allContactsSelected) }"></div>
                                        </div>
                                        <p class="my-auto">Seleccionar todos</p>
                                    </div>

                                    <!--Añadir externo-->
                                    <div class="d-flex ml-10" v-if="recipientDisplaySelected === 'emails'">
                                        <button class="custom-button my-10" data-size="medium" data-bg="azul"><i class="fa-solid fa-plus"></i></button>
                                    </div>
                                </form>


                                <!--Listado-->
                                <div>

                                    <!--Agentes-->
                                    <div class="user-list" v-if="recipientDisplaySelected === 'agents'">
                                        <div v-if="filteredList.length === 0" class="opacity-5">¡No hay ningún agente!</div>

                                        <div v-for="user in filteredList" class="user" v-on:click="toggleSelectEmail(user.email, 'agent')">

                                            <div class="d-flex">

                                                <div class="my-auto w-10">
                                                    <img :src="'/assets/profile_images/' + user.profileImage" alt="Imagen usuario">
                                                </div>

                                                <!--label, nombre, correo-->
                                                <div class="content d-flex mx-10 w-80">

                                                    <div class="w-70">
                                                        <p class="text w-250-px-max ellipsis" :data-color="emailsSelected.includes(user.email) ? 'azul' : ''">{{ user.firstName }} {{ user.lastName }}</p>

                                                        <p class="text opacity-3 ellipsis" data-size="8">{{ user.email }}</p>
                                                    </div>

                                                    <div class="w-30">
                                                        <p class="text opacity-3 ellipsis" data-size="8"><i class="fa-solid fa-phone mr-5"></i>{{ user.phone }}</p>

                                                        <p class="text opacity-3 ellipsis" data-size="8"><i class="fa-solid fa-id-card mr-5"></i>{{ user.dni }}</p>
                                                    </div>
                                                </div>

                                                <!--botón seleccionar responsable-->
                                                <div class="text pointer mx-auto my-auto d-flex justify-center align-center w-10">
                                                    <i class="far fa-arrow-pointer" :data-color="emailsSelected.includes(user.email) ? 'azul' : ''"></i>
                                                </div>
                                            </div>

                                            <!--Separador-->
                                            <div class="separator mt-5 mb-0"></div>

                                        </div>
                                    </div>

                                    <!--Cuentas-->
                                    <div class="user-list" v-if="recipientDisplaySelected === 'accounts'">
                                        <div v-if="filteredList.length === 0" class="opacity-5">¡No hay ninguna cuentas!</div>

                                        <div v-for="acc in filteredList" class="user" v-on:click="toggleSelectEmail(acc.email, 'account')">

                                            <div class="d-flex">

                                                <!--label, nombre, correo-->
                                                <div class="content d-flex mx-10 w-80">

                                                    <div class="w-80">
                                                        <p class="text w-400-px-max ellipsis" :data-color="emailsSelected.includes(acc.email) ? 'azul' : ''">{{ acc.name }}</p>

                                                        <p class="text opacity-3 ellipsis" data-size="8">{{ acc.email }}</p>
                                                    </div>

                                                    <div class="w-20">
                                                        <p class="text opacity-3 ellipsis" data-size="8"><i class="fa-solid fa-id-card mr-5"></i>{{ acc.CIF }}</p>
                                                    </div>
                                                </div>

                                                <!--botón seleccionar responsable-->
                                                <div class="text pointer mx-auto my-auto d-flex justify-center align-center w-10">
                                                    <i class="far fa-arrow-pointer" :data-color="emailsSelected.includes(acc.email) ? 'azul' : ''"></i>
                                                </div>
                                            </div>

                                            <!--Separador-->
                                            <div class="separator mt-5 mb-0"></div>

                                        </div>
                                    </div>

                                    <!--Contactos-->
                                    <div class="user-list" v-if="recipientDisplaySelected === 'contacts'">
                                        <div v-if="filteredList.length === 0" class="opacity-5">¡No hay ningún contacto!</div>

                                        <div v-for="contact in filteredList" class="user" v-on:click="toggleSelectEmail(contact.email, 'contact')">

                                            <div class="d-flex">

                                                <!--label, nombre, correo-->
                                                <div class="content d-flex mx-10 w-80">

                                                    <div class="w-70">
                                                        <p class="text w-400-px-max ellipsis" :data-color="emailsSelected.includes(contact.email) ? 'azul' : ''">{{ contact.name.first }} {{ contact.name.second }} {{ contact.surname.first }}</p>

                                                        <p class="text opacity-3 ellipsis" data-size="8">{{ contact.email }}</p>
                                                    </div>

                                                    <div class="w-30">
                                                        <p class="text opacity-3 ellipsis" data-size="8"><i class="fa-solid fa-phone mr-5"></i>{{ contact.phone }}</p>
                                                    </div>
                                                </div>

                                                <!--botón seleccionar responsable-->
                                                <div class="text pointer mx-auto my-auto d-flex justify-center align-center w-10">
                                                    <i class="far fa-arrow-pointer" :data-color="emailsSelected.includes(contact.email) ? 'azul' : ''"></i>
                                                </div>
                                            </div>

                                            <!--Separador-->
                                            <div class="separator mt-5 mb-0"></div>

                                        </div>
                                    </div>

                                    <!--Correo-->
                                    <div v-if="recipientDisplaySelected === 'emails'" v-for="email in filteredExternalEmails" class="d-flex my-10" v-on:click="toggleSelectEmail(email.email, 'email')">
                                        <p class="mr-20 my-auto w-100">{{ email.email }}</p>

                                        <button class="custom-button my-auto" data-size="medium" data-bg="rojo" @click="deleteExternalEmail(email.email)"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>

                        </transition>
                    </div>

                    <!--total emails y listas guardadas-->
                    <div class="absPos top w-100 d-flex justify-between px-20">

                        <!--total emails-->
                        <div class="d-flex justify-between align-center text px-15 py-5 round" data-bg="gris" data-round="15">
                            Total emails : <span class="ml-10" data-size="20">{{ emailsSelected.length }}</span>
                        </div>


                        <!--listas guardadas-->
                        <div class="relPos d-flex">

                            <!--Si hay algún correo seleccionado-->
                            <div v-if="emailsSelected.length > 0" class="custom-button ml-auto my-auto mr-10 bounce" data-size="small" data-bg="amarillo" @click="saveEmailList"><i class="far fa-plus"></i></div>

                            <!--titulo-->
                            <div class="d-flex justify-between align-center text px-15 py-5 round pointer" data-bg="gris" data-round="15" v-on:click="isSeeingSavedLists = !isSeeingSavedLists">

                                <i class="far fa-save mr-10"></i>

                                <p>Listas guardadas</p>

                            </div>

                            <!--<div class="d-flex justify-between align-center absPos text px-15 py-5 round pointer" data-bg="gris" data-round="15" >
                            </div>-->
                        </div>

                    </div>

                </div>


                <!--Resumen más herramientas-->
                <div class="p-30 pt-50 w-30 round d-grid align-center" data-column="2" data-round="15">

                    <!--Historial correos-->
                    <div class="resume-card w-auto"data-bg="azul-opacidad" v-on:click="toggleSelectExtraTool('history')" >

                        <div class="icon p-5">
                            <i class="far fa-clock-rotate-left" data-size="20"></i>
                        </div>


                        <div :class="['info']" class="d-flex align-center">
                            <p class="text">
                                Historial correos
                            </p>
                        </div>

                    </div>

                    <!--Correo bienvenida-->
                    <div v-if="basicData && basicData.userLogged && basicData.userLogged.label === 'Usuario subdominio'" class="resume-card w-auto" data-bg="azul-opacidad" v-on:click="toggleSelectExtraTool('welcome')" >

                        <div class="icon p-5">
                            <i class="far fa-hand-wave" data-size="20"></i>
                        </div>


                        <div :class="['info']" class="d-flex align-center">
                            <p class="text">
                                Correo bienvenida
                            </p>

                        </div>

                    </div>

                </div>
            </div>


            <!--Contenido correo-->
            <div class="form d-flex">

                <!--Correo-->
                <quill-component class="w-70" :email="email" :errors="errors" type="send" @submitEmail="submitEmail"></quill-component>

                <!--Docs. adjuntos-->
                <div class="w-30 p-30">

                    <div class="text my-10" data-size="17">

                        <!--Header-->
                        <div class="d-flex">
                            <p>Documentos adjuntos</p>

                            <p class="ml-auto"><button class="custom-button my-auto ml-auto" data-size="medium" data-bg="azul" @click="openDialog"><i class="fa-solid fa-paperclip"></i></button></p>

                            <input id="docInput" type="file" style="display: none" multiple v-on:change="pickupImage">
                        </div>

                        <!--Contenido-->
                        <div class="div-content">
                            <template v-for="(doc, docInd) in email.docs" :key="getDocStoredValue(doc) || doc.defaultTitle || docInd">
                                <div v-if="doc.isExisting && getDocStoredValue(doc)" class="d-flex justify-between align-center my-10 p-10 round" data-bg="gris" data-round="10">
                                    <div class="d-flex align-center w-75 noWidth">
                                        <i class="far mr-10" :class="doc.icon || getFileIcon(getDocExtension(doc))"></i>
                                        <div class="noWidth">
                                            <p class="ellipsis" data-size="13">{{ getDocDisplayTitle(doc) }}</p>
                                            <p class="ellipsis opacity-5" data-size="9">{{ getDocStoredValue(doc) }}</p>
                                        </div>
                                    </div>

                                    <div class="d-flex align-center" data-gap="6">
                                        <button type="button" class="custom-button" data-size="small" data-bg="azul" @click.stop="seeDoc(doc)">
                                            <i class="far fa-eye"></i>
                                        </button>

                                        <a
                                            v-if="canPreviewDoc(doc)"
                                            class="custom-button"
                                            data-size="small"
                                            data-bg="principal"
                                            :href="getDocPreviewUrl(doc)"
                                            :download="getDocDisplayTitle(doc)"
                                            @click.stop
                                        >
                                            <i class="fa-solid fa-download"></i>
                                        </a>

                                        <button type="button" class="custom-button" data-size="small" data-bg="rojo" @click.stop="delDoc(docInd)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <doc-component
                                    v-else
                                    :doc="doc"
                                    :docInd="docInd"
                                    :isReadOnly="false"
                                    @delDoc="delDoc"
                                ></doc-component>
                            </template>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <transition name="fade-vertical">
            <div v-if="extraToolSelectedCode" class="px-40 extra-tool-container round w-100" data-round="30" data-bg="azul-opacidad">

                <div class="d-flex f-wrap justify-between">

                    <!--Header extra tool-->
                    <div class="w-100 mt-25 d-flex justify-between">
                        <p class="text" data-size="20" data-weight="500">{{ extraTools.find(tool => tool.code === extraToolSelectedCode).title }}</p>

                        <div class="d-flex justify-between">
                            <button v-if="extraToolSelectedCode === 'welcome'" class="custom-button mx-10" data-size="medium" data-bg="principal"  @click="setWelcomeTemplate">Establecer plantilla</button>

                            <button class="custom-button" data-size="medium" data-bg="rojo"  @click="extraToolSelectedCode = null; emailSendedSelectedId = null">Volver</button>
                        </div>
                    </div>

                    <!--Historial correos-->
                    <div v-if="extraToolSelectedCode === 'history'" class="email-grid w-100">
                        <!--Listado de correos seleccionados-->
                        <div v-if="emailSendedSelectedId === null" v-for="email in emails" class="p-10 break-word" v-on:click.stop="toggleEmailSendedSelected(email._id)">
                            <div class="email-preview-container" >
                                <div class="email-preview-scale">
                                    <div class="email-html-preview" v-html="email.message"></div>
                                </div>

                                <div class="email-info">

                                    <!--Info hover card-->
                                    <div class="py-10 px-15">
                                        <div class="d-flex align-center my-12">
                                            <p class="w-20-px d-flex justify-center"><i class="far fa-calendar"></i></p>
                                            <p class="ml-10" data-size="10">{{ getSpanishDate(email.createdAt) }}</p>
                                        </div>

                                        <div class="d-flex align-center my-12">
                                            <p class="w-20-px d-flex justify-center"><i class="far fa-paperclip"></i></p>
                                            <p class="ml-10" data-size="10">{{ email.docs.length }} doc{{ email.docs.length === 1 ? '' : 's'}}. adjunto{{ email.docs.length === 1 ? '' : 's'}}</p>
                                        </div>

                                        <div class="d-flex align-center my-12">
                                            <p class="w-20-px d-flex justify-center"><i class="far fa-users"></i></p>
                                            <p class="ml-10" data-size="10">{{ email.recipients.length }} usuario{{ email.recipients.length === 1 ? '' : 's'}}</p>
                                        </div>

                                        <div class="d-flex align-center my-12">
                                            <p class="w-20-px d-flex justify-center"><i class="far fa-arrow-pointer"></i></p>
                                            <p class="ml-10" data-size="10">{{ getClicksCount(email.recipients) }} enlace{{ getClicksCount(email.recipients) === 1 ? '' : 's'}} clicado{{ getClicksCount(email.recipients) === 1 ? '' : 's'}}</p>
                                        </div>

                                        <div class="d-flex align-center my-12">
                                            <p class="w-20-px d-flex justify-center"><i class="far fa-envelope-open"></i></p>
                                            <p class="ml-10" data-size="10">{{ getOpenCount(email.recipients) }} correo{{ getOpenCount(email.recipients) === 1 ? '' : 's'}} abierto{{ getOpenCount(email.recipients) === 1 ? '' : 's'}}</p>
                                        </div>

                                    </div>

                                </div>
                            </div>

                            <!--Asunto-->
                            <div class="text mt-5 w-260-px px-10 ellipsis">{{ email.subject }}</div>
                        </div>

                        <!--Correo enviado seleccionado-->
                        <div v-else-if="emailSendedSelected" class="d-flex w-100">

                            <!--Correo-->
                            <div class="w-70 p-15">

                                <!--asunto-->
                                <div class="m-10 d-flex justify-between align-center">
                                    <p class="w-100 ellipsis" data-size="18">{{ emailSendedSelected.subject }}</p>

                                    <button class="custom-button ml-20" data-size="small" data-bg="rojo"  @click="emailSendedSelectedId = null">Volver a listado</button>
                                </div>

                                <!--mensaje-->
                                <div class="w-100 email-preview break-word" v-html="emailSendedSelected.message"></div>

                                <!--documentos adjuntos-->
                                <div class="mt-20">

                                    <!--nº docs-->
                                    <p class="mt-20 mb-10">{{ emailSendedSelected.docs.length }} documento{{ emailSendedSelected.docs.length === 1 ? '' : 's' }} adjunto{{ emailSendedSelected.docs.length === 1 ? '' : 's' }}</p>

                                    <!--listado documentos-->
                                    <div class="d-flex f-wrap" data-gap="20">
                                        <div v-for="doc in emailSendedSelected.docs" :key="getDocStoredValue(doc) || getDocDisplayTitle(doc)">
                                            <!--IMG-->
                                            <div v-if="isImage(getDocExtension(doc)) && canPreviewDoc(doc)" v-on:click="seeDoc(doc)" class="preview-doc">

                                                <img
                                                    class="w-100"
                                                    :src="getDocPreviewUrl(doc)"
                                                    alt="Vista previa"
                                                />

                                                <!-- Capa con degradado y botones -->
                                                <div class="overlay">

                                                    <!--título-->
                                                    <p class="ellipsis px-10" data-size="10">{{ getDocDisplayTitle(doc) }}</p>

                                                    <!--descarga-->
                                                    <a
                                                        v-if="canPreviewDoc(doc)"
                                                        :href="getDocPreviewUrl(doc)"
                                                        :download="getDocDisplayTitle(doc)"
                                                        v-on:click.stop=""
                                                        class="mr-10"
                                                        data-color="principal"
                                                    >
                                                        <i class="fa-solid fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>


                                            <!--PDF-->
                                            <div v-else-if="isPdf(getDocExtension(doc)) && canPreviewDoc(doc)" v-on:click="seeDoc(doc)" class="preview-doc">

                                                <pdf-preview-component :src="getDocPreviewUrl(doc)"></pdf-preview-component>

                                                <!-- Capa con degradado y botones -->
                                                <div class="overlay">
                                                    <!--título-->
                                                    <p class="ellipsis px-10" data-size="10">{{ getDocDisplayTitle(doc) }}</p>

                                                    <a
                                                        v-if="canPreviewDoc(doc)"
                                                        :href="getDocPreviewUrl(doc)"
                                                        :download="getDocDisplayTitle(doc)"
                                                        v-on:click.stop=""
                                                        class="mr-10"
                                                        data-color="principal"
                                                    >
                                                        <i class="fa-solid fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>


                                            <!--Otros-->
                                            <div v-else v-on:click="seeDoc(doc)" class="preview-doc" data-bg="gris-principal">

                                                <div class="w-100 h-100 d-flex justify-center align-center">
                                                    <i class="far opacity-8" data-size="30" :class="doc.icon || getFileIcon(getDocExtension(doc))"></i>
                                                </div>

                                                <!-- Capa con degradado y botones -->
                                                <div class="overlay">
                                                    <!--título-->
                                                    <p class="ellipsis px-10" data-size="10">{{ getDocDisplayTitle(doc) }}</p>

                                                    <a
                                                        v-if="canPreviewDoc(doc)"
                                                        :href="getDocPreviewUrl(doc)"
                                                        :download="getDocDisplayTitle(doc)"
                                                        v-on:click.stop=""
                                                        class="mr-10"
                                                        data-color="principal"
                                                    >
                                                        <i class="fa-solid fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="w-30 p-15">

                                <!--info-->
                                <div class="mb-20">
                                    <p class="italic" data-size="20" data-weight="600">Info <i class="far fa-circle-info ml-10"></i></p>


                                    <!--Fecha-->
                                    <p><span class="opacity-7">Fecha:</span> {{ getSpanishDate(emailSendedSelected.createdAt) }}</p>

                                    <!--Adjuntos-->
                                    <!--<div><span class="opacity-7">Nº documentos adjuntos:</span> {{emailSendedSelected.docs.length}}</div>-->
                                </div>

                                <!--tracking-->
                                <div v-if="!$route.query.email || ($route.query.email && basicData && basicData.userLogged._id === emailSendedSelected.createdBy)">
                                    <p class="italic" data-size="20" data-weight="600">Tracking <i class="far fa-truck-fast ml-10"></i></p>

                                    <!--Usuarios seleccionados-->
                                    <email-card-component title="Usuarios seleccionados" faIcon="far fa-users" :emails="emailSendedSelected.recipients" :activeCard="activeCard" @setActive="setActive" ></email-card-component>

                                    <!--Correos con enlaces clicados-->
                                    <email-card-component title="Enlaces clicados" faIcon="far fa-arrow-pointer" :emails="emailsSelectedClicked" :activeCard="activeCard" @setActive="setActive" />

                                    <!--Correos entregados correctamente-->
                                    <email-card-component title="Correos entregados" iconComponent="MailCheck" :emails="emailsSelectedDelivered" :activeCard="activeCard" @setActive="setActive" />

                                    <!--Correos sin entregar-->
                                    <email-card-component title="Correos sin entregar" iconComponent="MailX" :emails="emailsSelectedNotDelivered"  :activeCard="activeCard"@setActive="setActive" />

                                    <!--Correos abiertos por personas-->
                                    <email-card-component title="Correos abiertos por usuario" faIcon="far fa-envelope-open" :emails="emailsSelectedOpenByUser" :activeCard="activeCard" @setActive="setActive" />

                                    <!--Correos abiertos por google-->
                                    <email-card-component title="Correos abiertos por google" faIcon="far fa-robot" :emails="emailsSelectedOpenByGoogle" :activeCard="activeCard" @setActive="setActive" />
                                </div>

                            </div>
                        </div>
                    </div>


                    <!--Correo de bienvenida-->
                    <div v-if="extraToolSelectedCode === 'welcome'" class="d-flex flex-column w-100">
                        <quill-component v-if="basicData && basicData.userLogged" class="w-100" :email="emailExtra"
                                         :errors="errors" type="submit" @submitEmail="submitEmail" @saveEmail="saveEmail"></quill-component>
                    </div>


                    <!--Correo de renovación-->
                    <div v-if="extraToolSelectedCode === 'renovation'" class="d-flex flex-column">
                        Historial de renovación
                    </div>

                </div>
            </div>
        </transition>

        <!--Desplegable-->
        <div class="form-group" v-if="isSeeingSavedLists">

            <div class="floating-box round" data-round="30">


                <div class="register-pos round w-50 h-70" style="border:1px solid black;" data-round="30">

                    <div class="top-part">

                        <div class="inputs-part basic-data px-80 w-100 clip">

                            <!--Añadir o borrar-->
                            <div class="d-flex justify-between">
                                <p class="text" data-size="15" data-weight="700">Listas guardadas</p>
                            </div>

                            <!--Listado listas creadas-->
                            <div class="h-90">

                                <!--headers-->
                                <div class="d-flex justify-between mt-5 mb-2 opacity-3">
                                    <p>Nombre</p>
                                    <p>Cantidad correos</p>
                                </div>

                                <!--listado-->
                                <div class="h-90 scroll-y">
                                    <div v-for="(list, index) in this.basicData.userLogged.emailSavedLists" v-on:click="chargeSavedList(list)" class="d-flex justify-between align-center my-10 px-15 py-5 round pointer" data-round="10" data-bg="gris">
                                        <p class="w-70 pr-15 ellipsis">{{ list.name }}</p>
                                        <p class="w-20 text-center">{{ list.emails.length }}</p>
                                        <i class="w-10 text-center far fa-trash" data-color="rojo" v-on:click.stop="deleteSavedList(index)"></i>
                                    </div>
                                </div>


                            </div>

                        </div>

                    </div>

                    <!--Separator-->
                    <div class="separator"></div>

                    <!--botones-->
                    <div class="d-flex justify-end">
                        <!--Botón guardar-->
                        <button class="custom-button mr-20" data-size="regular" data-bg="rojo"
                                v-on:click.prevent="isSeeingSavedLists = false">Cancelar</button>
                    </div>
                </div>

            </div>

        </div>
    </div>
</template>

<script>

export default {
    name: 'MassiveEmailComponent',
    props:['basicData'],
    data(){
        return{
            email:{
                subject: '',
                message: '',
                docs: [],
                recipients: []
            },
            emailExtra:{
                subject: '',
                message: '',
                docs: []
            },
            accounts: [],
            contacts: [],
            searchText: '',
            recipientDisplaySelected: null,
            isSeeingSavedLists: false,
            selectedSavedList: null,
            errors:{},
            extraTools: [
                {
                    code: 'history',
                    title: 'Historial correos'
                },
                {
                    code: 'welcome',
                    title: 'Correo bienvenida'
                },
                {
                    code: 'renovation',
                    title: 'Correo renovación'
                },
            ],
            extraToolSelectedCode: null,
            emailSendedSelectedId: null,
            emails: null,
            datadisDraftLoaded: false,
            activeCard: null
        }
    },
    created(){
        //Saco las cuentas de los usuarios
        this.fetchAccounts()

        //Saco los contactos de los usuarios
        this.fetchContacts()

        //Saco el historial de correos de esa persona
        if (!this.emails){
            if (this.$route.query.email){
                this.fetchAllEmails()

                this.extraToolSelectedCode = 'history'
                this.emailSendedSelectedId = this.$route.query.email
            }else{
                this.fetchEmails()
            }
        }

    },
    watch: {
        basicData: {
            immediate: true,
            deep: true,
            handler(newVal) {
                if (newVal && newVal.userLogged && newVal.userList && !this.accounts.length) {
                    this.fetchAccounts();
                    this.fetchContacts();

                    if (!this.emails) {
                        if (this.$route.query.email) {
                            this.fetchAllEmails();

                            this.extraToolSelectedCode = 'history';
                            this.emailSendedSelectedId = this.$route.query.email;
                        } else {
                            this.fetchEmails();
                        }
                    }
                }

                this.loadDatadisDraftIfNeeded();
            }
        },

        accounts(newVal) {
            if (newVal && newVal.length > 0 && this.emails !== null) {
                this.loadPreselectedEmails();
            }

            this.loadDatadisDraftIfNeeded();
        },

        emails(newVal) {
            if (newVal && this.accounts.length > 0) {
                this.loadPreselectedEmails();
            }

            this.loadDatadisDraftIfNeeded();
        },

        "$route.query": {
            deep: true,
            handler() {
                this.datadisDraftLoaded = false;
                this.loadDatadisDraftIfNeeded();
            }
        }
    },
    methods:{

        isDatadisDraftRoute() {
            return this.$route?.query?.from === "datadis" && this.$route?.query?.withDraft === "true";
        },

        loadDatadisDraftIfNeeded() {
            const key = "datadisMassiveEmailDraft";

            if (this.datadisDraftLoaded) return;
            if (!this.isDatadisDraftRoute()) return;
            if (!this.basicData?.userList) return;

            const rawDraft =
                sessionStorage.getItem(key)
                || localStorage.getItem(key)
                || localStorage.getItem("massiveEmailDraft");

            if (!rawDraft) return;

            let draft = null;

            try {
                draft = JSON.parse(rawDraft);
            } catch (e) {
                sessionStorage.removeItem(key);
                localStorage.removeItem(key);
                localStorage.removeItem("massiveEmailDraft");
                return;
            }

            if (!draft || draft.from !== "datadis") return;

            this.extraToolSelectedCode = null;
            this.emailSendedSelectedId = null;

            this.email.subject = draft.subject || "";
            this.email.message = draft.message || "";

            this.email.docs = Array.isArray(draft.docs)
                ? draft.docs
                    .filter(doc => doc && (doc.value || doc.fileName))
                    .map(doc => {
                        const value = doc.value || doc.fileName;
                        const title = doc.title || doc.defaultTitle || value;

                        return {
                            title,
                            defaultTitle: doc.defaultTitle || title,
                            value,

                            // No es archivo nuevo subido desde input.
                            fileValue: null,

                            // Marca para pintarlo sin romper doc-component y para re-adjuntarlo al enviar.
                            isExisting: true,
                            source: "datadis",

                            icon: doc.icon || this.getFileIcon((value || "").split(".").pop().toLowerCase()),
                            errors: doc.errors || {}
                        };
                    })
                : [];

            this.email.recipients = [];

            const alreadySelected = new Set();
            const recipients = Array.isArray(draft.recipients) ? draft.recipients : [];

            recipients.forEach(recipient => {
                const email = recipient?.email?.trim();

                if (!email) return;

                const emailKey = email.toLowerCase();

                if (alreadySelected.has(emailKey)) return;

                this.email.recipients.push({
                    email,
                    type: this.getDraftRecipientType(email, recipient.type)
                });

                alreadySelected.add(emailKey);
            });

            this.errors = {};
            this.datadisDraftLoaded = true;

            sessionStorage.removeItem(key);
            localStorage.removeItem(key);
            localStorage.removeItem("massiveEmailDraft");
        },

        getDraftRecipientType(email, fallbackType = "email") {
            const inAgents = this.basicData?.userList?.find(user => user.email === email);
            const inAccounts = this.accounts?.find(acc => acc.email === email);
            const inContacts = this.contacts?.find(contact => contact.email === email);

            if (inAgents) return "agent";
            if (inAccounts) return "account";
            if (inContacts) return "contact";

            return fallbackType || "email";
        },
        async fetchAccounts(){
            await axios.post(`/api/accounts/getRelatedAccounts/${this.basicData.userLogged._id}`, {userList: JSON.stringify(this.basicData.userList)})
                .then((res) => {
                    this.accounts = res.data.relatedAccounts.filter(c => c.email && c.email.trim() !== '');
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async fetchContacts(){
            await axios.post(`/api/opportunities/getRelatedContacts/${this.basicData.userLogged._id}`, {userList: this.basicData.userList})
                .then((res) => {
                    this.contacts = res.data.contacts.filter(c => c.email && c.email.trim() !== '');
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async fetchEmails(){
            await axios.get(`/api/tools/getEmails/${this.basicData.userLogged._id}`)
                .then((res) => {
                    this.emails = res.data
                } )
                .catch((err) => {
                    console.log(err)
                })
        },
        async fetchAllEmails(){
            await axios.get(`/api/tools/getAllEmails`)
                .then((res) => {
                    this.emails = res.data
                } )
                .catch((err) => {
                    console.log(err)
                })
        },
        async submitEmail(){

            //Validaciones
            let hasErrors = false;

            //Asunto
            if (this.email.subject === ''){
                this.errors.subject = 'El asunto no puede estar vacío';
                hasErrors = true;
            }

            //Mensaje
            if (this.email.message === ''){
                this.errors.message = 'El mensaje no puede estar vacío';
                hasErrors = true;
            }

            //Correos seleccionados
            if (this.email.recipients.length === 0){
                this.errors.recipients = 'Tienes que seleccionar al menos un correo';
                hasErrors = true;

                Swal.fire({
                    icon: 'error',
                    title: '¡Sin usuarios seleccionados!',
                    text: 'Tienes que seleccionar al menos un correo para enviar el mensaje, puedes hacerlo pinchando en las tarjetas de agentes, cuentas y correos externos, en la parte superior de la pantalla',
                    timer: 3500,
                    timerProgressBar: true
                })
            }


            if (!hasErrors){

                let data = new FormData();

                data.append('userLogged', JSON.stringify(this.basicData.userLogged));
                data.append('enterprise', JSON.stringify(this.basicData.enterprise));

                this.email.docs = this.email.docs.map(doc => ({
                    ...doc,
                    title: doc.title || doc.defaultTitle || doc.value || doc.fileName || 'Documento adjunto',
                    defaultTitle: doc.defaultTitle || doc.title || doc.value || doc.fileName || 'Documento adjunto'
                }));

                data.append('email', JSON.stringify(this.email));

                try {
                    // Archivos nuevos: mantiene el comportamiento anterior.
                    // Archivos Datadis ya guardados: se descargan desde /assets/emails y se re-adjuntan como Blob
                    // para que el backend antiguo siga recibiendo docFile0, docFile1, etc.
                    for (let docInd = 0; docInd < this.email.docs.length; docInd++) {
                        await this.appendDocToFormData(data, this.email.docs[docInd], docInd);
                    }
                } catch (e) {
                    console.error('Error preparando adjuntos:', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error con adjuntos',
                        text: e?.message || 'No se pudo preparar uno de los documentos adjuntos.'
                    });
                    return;
                }

                await axios.post('/api/tools/massiveEmail', data)
                    .then((res) => {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Enviando correos!',
                            text: 'Los correos están siendo enviados de manera asincrona, ya puedes cerrar esta ventana y seguir trabajando',
                            timer: 1500,
                            timerProgressBar:true
                        })

                        //Limpio to
                        this.searchText = '';

                        this.email = {
                            subject: '',
                            message: '',
                            docs: [],
                            recipients: []
                        }

                        //Vuelvo a sacar el historial
                        this.fetchEmails()


                    })
                    .catch((err) => {
                        console.log(err)
                    })

            }
        },
        async saveEmail(type){

            //Validaciones
            let hasErrors = false;

            //Asunto
            if (this.emailExtra.subject === ''){
                this.errors.subject = 'El asunto no puede estar vacío';
                hasErrors = true;
            }

            //Mensaje
            if (this.emailExtra.message === ''){
                this.errors.message = 'El mensaje no puede estar vacío';
                hasErrors = true;
            }


            if (!hasErrors){

                let data = new FormData();

                data.append('userLogged', JSON.stringify(this.basicData.userLogged));
                data.append('email', JSON.stringify(this.emailExtra));

                await axios.post('/api/user/email/welcome', data)
                    .then((res) => {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Correo actualizado!',
                            timer: 1500,
                            timerProgressBar:true
                        })

                        console.log(res)
                    })
                    .catch((err) => {
                        console.log(err)
                    })

            }

            switch (type){
                case 'welcome':

                    break;

                case 'renovation':

                    break;
            }
        },
        toggleSelectedDisplay(type){
            if (type === this.recipientDisplaySelected)
                this.recipientDisplaySelected = null
            else{
                this.recipientDisplaySelected = type;
            }

            this.searchText = ''
        },
        toggleSelectEmail(email, type){
            //Busco si está, si lo está lo elimino, sino lo añado
                let emailsDirect = this.email.recipients.map(email => email.email);

            if (emailsDirect.includes(email))
                this.email.recipients = this.email.recipients.filter(emailNow => emailNow.email !== email);
            else
                this.email.recipients.push({email: email, type: type});
        },
        toggleSelectAll(){
            switch (this.recipientDisplaySelected){
                case 'agents':

                    //Si están todos elimino de email recipients todos los que esten en basicData userlist
                    if (this.allAgentsSelected){
                        // Filtrar para eliminar los correos que están en `basicData.userList` de `email.recipients`
                        let emailsDirect = this.email.recipients.map(email => email.email);

                        this.email.recipients = this.email.recipients.filter(email => !this.basicData.userList.some(user => user.email === email.email));
                    }else{
                        //Meto los correos que no estén todavia seleccionados
                        let emailsDirect = this.email.recipients.map(email => email.email);

                        this.basicData.userList.forEach(user => {
                            if (!emailsDirect.includes(user.email))
                                this.email.recipients.push({email: user.email, type: 'agent'});
                        })
                    }


                    break;

                case 'accounts':

                    //Si están todos elimino de email recipients todos los que esten en basicData userlist
                    if (this.allAccountsSelected){

                        this.email.recipients = this.email.recipients.filter(email => !this.accounts.some(acc => acc.email === email.email));
                    }else{
                        //Meto los correos que no estén todavia seleccionados
                        let emailsDirect = this.email.recipients.map(email => email.email);

                        //Filtro las cuentas y si hay mas de una con el mismo email dejo solo una con cada email
                        let accountsReduced = this.accounts.filter((account, index, self) =>
                                index === self.findIndex((t) => (
                                    t.email === account.email // Compara los correos
                                ))
                        );

                        accountsReduced.forEach(acc => {
                            if (!emailsDirect.includes(acc.email))
                                this.email.recipients.push({email: acc.email, type: 'account'});
                        })
                    }

                    break;

                case 'contacts':

                    //Si están todos elimino de email recipients todos los que esten en basicData userlist
                    if (this.allContactsSelected){
                        this.email.recipients = this.email.recipients.filter(email => !this.contacts.some(contact => contact.email === email.email));
                    }else{
                        //Meto los correos que no estén todavia seleccionados
                        let emailsDirect = this.email.recipients.map(email => email.email);

                        //Filtro las cuentas y si hay mas de una con el mismo email dejo solo una con cada email
                        let contactsReduced = this.contacts.filter((contact, index, self) =>
                                index === self.findIndex((t) => (
                                    t.email === contact.email // Compara los correos
                                ))
                        );

                        contactsReduced.forEach(contact => {
                            if (!emailsDirect.includes(contact.email))
                                this.email.recipients.push({email: contact.email, type: 'contact'});
                        })
                    }

                    break;
            }
        },
        toggleSelectExtraTool(selected){
            if(this.extraToolSelectedCode === selected)
                this.extraToolSelectedCode = null
            else{

                switch (selected){

                    case 'welcome':

                        if (!!this.basicData.userLogged.welcomeEmail)
                            this.emailExtra = this.basicData.userLogged.welcomeEmail

                        break;

                    case 'renovation':

                        if (!!this.basicData.userLogged.renovationEmail)
                            this.emailExtra = this.basicData.userLogged.renovationEmail

                        break;
                }

                this.extraToolSelectedCode = selected;
            }

            this.errors = {}

        },
        addExternalEmail(){

            //Validación correo
            let regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

            if (regex.test(this.searchText)){

                //Si está en otros no deja añadirlo, dice que se ha seleccionado de donde esté
                let emailsDirect = this.email.recipients.map(email => email.email);

                let includedYet = emailsDirect.includes(this.searchText)

                //Compruebo si está dentro de agentes o usuarios
                let inAgents = this.basicData.userList.find(user => user.email === this.searchText);
                let inAccounts = this.accounts.find(acc => acc.email === this.searchText);

                if (includedYet){
                    Swal.fire({
                        icon: 'warning',
                        title: 'El correo ya está seleccionado',
                        text: 'El correo ya ha sido seleccionado previamente en ' + (inAgents ? 'agentes' : (inAccounts ? 'cuentas' : 'correos externos')),
                        timer: 1500,
                        timerProgressBar:true
                    })
                }else{
                    //Compruebo si está dentro de alguno de los otros
                    if (inAgents)
                        this.email.recipients.push({email: this.searchText, type: 'agent'});
                    else if(inAccounts)
                        this.email.recipients.push({email: this.searchText, type: 'account'});
                    else
                        this.email.recipients.push({email: this.searchText, type: 'email'});

                    this.searchText = ''

                    //Si tiene error de recipients lo borro
                    if (this.errors.recipients) delete this.errors.recipients
                }

            }else{
                Swal.fire({
                    icon: 'error',
                    title: 'Correo no válido',
                    text: 'El correo electrónico que has introducido no es válido.',
                    timer: 1500,
                    timerProgressBar: true
                });
            }

        },
        deleteExternalEmail(emailToDelete){
            this.email.recipients = this.email.recipients.filter(recipient => recipient.email !== emailToDelete);
        },
        openDialog() {
            $('#docInput').click();
        },
        pickupImage() {

            let input = $('#docInput');
            if (input.prop('files')) {
                let files = input.prop('files');

                //Para cada uno de los archivos seleccionados
                for (let file of files) {

                    //Saco el icono
                    let getIconForFileType = (fileType) => {
                        // Recorre el array de tipos e iconos
                        for (let typeInfo of this.$storage.FILE_ICONS) {
                            if (typeInfo.types.includes(fileType)) {
                                return typeInfo.icon; // Devuelve el icono correspondiente
                            }
                        }
                        return 'fa-file'; // Devuelve un icono genérico si no encuentra ninguno
                    };

                    // Obtener el tipo MIME del archivo
                    let fileType = file.type || 'application/octet-stream';

                    //Añado el doc al pedido
                    let docInfo = {
                        title: '',
                        defaultTitle: file.name,
                        value: '', //Aqui se va a guardar el nombre del archivo
                        fileValue: file, //Aqui se va a meter el archivo en sí
                        icon: getIconForFileType(fileType),
                        errors: {}
                    }

                    //Meto los docs
                    this.email.docs.push(docInfo);
                }
            }
        },
        delDoc(ind) {
            //Compruebo si hay que borrarlo dela bbdd( como uso el mismo componente para crear y editar puede o no que se haya guardado)
            this.email.docs.splice(ind, 1);
        },
        setWelcomeTemplate(){
            this.emailExtra.subject = '¡Gracias por confiar en nosotros! ✨';
            this.emailExtra.message = "<p>Queremos darte la bienvenida a<strong> <em>" + this.basicData.enterprise.name + " CRM</em></strong> y agradecerte por confiar en nosotros como tu equipo de gestión energética.</p><p><br></p><p>Hemos creado un espacio personalizado para ti en nuestra plataforma, desde donde podremos colaborar y facilitar todos los procesos relacionados con tus servicios energéticos, de forma ágil, profesional y segura.</p><p><br></p><p>Desde ahora, contarás con el respaldo de nuestro equipo técnico y humano para lo que necesites. Estamos encantados de tenerte con nosotros y esperamos ayudarte a alcanzar tus objetivos con total transparencia y eficacia.</p><p><br></p><p>Gracias por ser parte de <em>" + this.basicData.enterprise.name + "</em></p><p><br></p><p>Un saludo,</p><p>El equipo de <strong>" + this.basicData.enterprise.name + "</strong></p>";

        },
        closeAll(){
            this.recipientDisplaySelected = null
        },
        getSpanishDate(date){
            return new Intl.DateTimeFormat('es-ES', {
                year: 'numeric', month: 'long', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            }).format(new Date(date.replace(' ', 'T')));
        },
        getClicksCount(recipients){
            return recipients.filter(user => user.clicked_at !== null).length
        },
        getOpenCount(recipients){
            return recipients.filter(user => (user.opened_at !== null && !user.opened_at.proxy)).length
        },
        toggleEmailSendedSelected(email){
            if(this.emailSendedSelectedId === email)
                this.emailSendedSelectedId= null
            else{
                this.emailSendedSelectedId= email
            }
        },
        isImage(extension) {
            return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension);
        },
        isPdf(extension) {
            return extension === 'pdf';
        },
        getFileIcon(extension) {
            switch (extension) {
                case 'doc':
                case 'docx': return 'fa-file-word';
                case 'xls':
                case 'xlsx':
                case 'csv': return 'fa-file-excel';
                case 'txt':
                case 'json': return 'fa-file-lines';
                case 'zip':
                case 'rar':
                case '7z': return 'fa-file-zipper';
                case 'ppt':
                case 'pptx': return 'fa-file-powerpoint';
                case 'pdf': return 'fa-file-pdf';
                default: return 'fa-file'; // genérico
            }
        },
        getDocStoredValue(doc) {
            return String(doc?.value || doc?.fileName || '').trim();
        },
        getDocDisplayTitle(doc) {
            return doc?.title || doc?.defaultTitle || doc?.fileName || doc?.value || 'Documento adjunto';
        },
        getDocExtension(doc) {
            const value = this.getDocStoredValue(doc) || this.getDocDisplayTitle(doc);
            return String(value).split('?')[0].split('.').pop().toLowerCase();
        },
        getDocPreviewUrl(doc) {
            const storedValue = this.getDocStoredValue(doc);

            if (storedValue) {
                if (/^(https?:)?\/\//.test(storedValue) || storedValue.startsWith('/')) {
                    return storedValue;
                }

                return `/assets/emails/${storedValue}`;
            }

            if (doc?.fileValue instanceof File || doc?.fileValue instanceof Blob) {
                return URL.createObjectURL(doc.fileValue);
            }

            return '';
        },
        canPreviewDoc(doc) {
            return !!this.getDocPreviewUrl(doc);
        },
        async appendDocToFormData(data, doc, docInd) {
            if (doc.fileValue instanceof File || doc.fileValue instanceof Blob) {
                data.append('docFile' + docInd, doc.fileValue);
                return;
            }

            const storedValue = this.getDocStoredValue(doc);
            if (!storedValue) return;

            const url = this.getDocPreviewUrl(doc);
            const fileName = this.getDocDisplayTitle(doc);

            const res = await fetch(url);

            if (!res.ok) {
                throw new Error(`No se pudo recuperar el adjunto ${fileName}`);
            }

            const blob = await res.blob();
            data.append('docFile' + docInd, blob, fileName);
        },
        seeDoc(doc){
            const url = this.getDocPreviewUrl(doc);

            if (!url) {
                Swal.fire({
                    icon: 'error',
                    title: 'Documento no disponible',
                    text: 'No se ha encontrado el archivo adjunto.'
                });
                return;
            }

            window.open(url, '_blank');
        },
        setActive(title) {
            this.activeCard = this.activeCard === title ? null : title
        },
        loadPreselectedEmails() {
            const emails = JSON.parse(localStorage.getItem('emailsTemporaly')) || [];
            const alreadySelected = this.email.recipients.map(r => r.email);

            emails.forEach(email => {
                if (alreadySelected.includes(email)) return;

                const inAgents = this.basicData.userList.find(user => user.email === email);
                const inAccounts = this.accounts.find(acc => acc.email === email);

                if (inAgents) {
                    this.email.recipients.push({ email, type: 'agent' });
                } else if (inAccounts) {
                    this.email.recipients.push({ email, type: 'account' });
                } else {
                    this.email.recipients.push({ email, type: 'email' });
                }
            });

            // Una vez usados, los borramos
            localStorage.removeItem('emailsTemporaly');
        },
        saveEmailList(){

            //si ha seleccionado alguna lista antes
            if (this.selectedSavedList && this.selectedSavedList !== ''){
                Swal.fire({
                    icon: 'question',
                    text: '¿Quieres actualizar la lista "' + this.selectedSavedList + '" o crear una nueva?',
                    confirmButtonText: 'Actualizar',
                    denyButtonText: 'Crear nueva',
                    cancelButtonText: 'Cancelar',
                    showDenyButton: true
                }).then((res) => {

                    //actualizar
                    if (res.isConfirmed){

                        //Miro si hay una lista con los mismos emails y sino lo guardo
                        if (!this.basicData.userLogged.emailSavedLists)
                            this.basicData.userLogged.emailSavedLists = [];

                        const uniqueRecipients = Array.from(
                            this.email.recipients.reduce((map, r) => {
                                const email = r.email?.trim();
                                // si no tiene email válido o ya existe, lo ignoramos
                                if (!email || map.has(email)) return map;
                                // guardamos la primera aparición
                                map.set(email, r);
                                return map;
                            }, new Map()).values()
                        );

                        let name = this.selectedSavedList

                        const lists = this.basicData.userLogged.emailSavedLists;
                        const idx = lists.findIndex(list => list.name === name);

                        if (idx >= 0) {
                            // si existe, reemplaza solo los emails
                            lists[idx].emails = uniqueRecipients;
                        } else {
                            // si no existe, lo agregas
                            lists.push({ name, emails: uniqueRecipients });
                        }


                        //Guardo en la bbdd
                        let data = new FormData();
                        data.append('user', JSON.stringify(this.basicData.userLogged));

                        axios.post('/api/user/update', data)
                            .then((res) => {

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Listad guardada',
                                    html: `La lista ha sido guardada correctamente!`
                                }).then((res) => {
                                    this.isSeeingSavedLists = false
                                })
                            })
                            .catch((err) => {
                                this.errors = err.response.data.errors;
                            })
                    }else if(res.isDenied){
                        this.createNewSavedList()
                    }

                })
            }else{
                this.createNewSavedList()
            }


        },
        createNewSavedList(){
            Swal.fire({
                icon: 'question',
                text: '¿Cómo quieres que se llame la lista?',
                input: 'text',
                confirmButtonText: 'Guardar lista',
                cancelButtonText: 'Cancelar',
                showCancelButton: true
            }).then((res) => {
                const name = res.value?.trim();
                if (res.isConfirmed && name) {
                    if (!this.basicData.userLogged.emailSavedLists) {
                        this.basicData.userLogged.emailSavedLists = [];
                    }

                    const exists = this.basicData.userLogged.emailSavedLists
                        .some(list => list.name.toLowerCase() === name.toLowerCase());

                    if (exists) {
                        return Swal.fire({
                            icon: 'error',
                            title: 'Nombre duplicado',
                            text: 'Ya tienes una lista con ese nombre. Por favor, elige otro.'
                        });
                    }

                    const uniqueRecipients = Array.from(
                        this.email.recipients.reduce((map, r) => {
                            const email = r.email?.trim();
                            if (!email || map.has(email)) return map;
                            map.set(email, r);
                            return map;
                        }, new Map()).values()
                    );

                    this.basicData.userLogged.emailSavedLists.push({
                        name,
                        emails: uniqueRecipients
                    });

                    this.selectedSavedList = name;

                    const data = new FormData();
                    data.append('user', JSON.stringify(this.basicData.userLogged));
                    axios.post('/api/user/update', data)
                        .then(() => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Lista guardada',
                                html: 'La lista ha sido creada correctamente.'
                            }).then(() => {
                                this.isSeeingSavedLists = false;
                            });
                        })
                        .catch(err => {
                            this.errors = err.response.data.errors;
                        });
                }
            });
        },

        deleteSavedList(index){
            Swal.fire({
                icon: 'warning',
                text: '¿Seguro que quieres borrar esta lista?',
                confirmButtonText: 'Sí',
                cancelButtonText: 'No',
                showCancelButton: true
            }).then((res) => {

                if (res.isConfirmed && res.value !== ''){

                    this.basicData.userLogged.emailSavedLists.splice(index, 1);

                    //Guardo en la bbdd
                    let data = new FormData();
                    data.append('user', JSON.stringify(this.basicData.userLogged));

                    axios.post('/api/user/update', data)
                        .then((res) => {
                            console.log('lista guardada borrada correctamente')
                        })
                        .catch((err) => {
                            this.errors = err.response.data.errors;
                        })
                }

            })
        },
        chargeSavedList(list){

            //Elimino los emails que ya había seleccionado
            this.email.recipients = [];

            //Cargo los de la lista
            list.emails.forEach(email => {
                this.email.recipients.push({email: email.email, type: email.type});
            });

            //Guardo la lista que se ha seleccionado
            this.selectedSavedList = list.name;

            //Cierro
            this.isSeeingSavedLists = false
        }
    },
    computed:{
        agentsSelectedCount(){
            if (!this.basicData || !this.basicData.userList) return 0;

            let emailsDirect = JSON.parse(JSON.stringify(this.email.recipients)).map(email => email.email);

            //Compruebo cuantos de los correos de usuarios hay seleccionados
            return this.basicData.userList.filter(user => emailsDirect.includes(user.email)).length;
        },
        accountsSelectedCount(){

            if (!this.accounts) return 0;

            let emailsDirect = JSON.parse(JSON.stringify(this.email.recipients)).map(email => email.email);

            //Compruebo cuantos de los correos de usuarios hay seleccionados
            return this.accounts.filter(user => emailsDirect.includes(user.email)).length;
        },
        contactsSelectedCount(){

            if (!this.contacts) return 0;

            let emailsDirect = JSON.parse(JSON.stringify(this.email.recipients)).map(email => email.email);

            //Compruebo cuantos de los correos de usuarios hay seleccionados
            return this.contacts.filter(user => emailsDirect.includes(user.email)).length;
        },
        emailsSelectedCount(){
            return JSON.parse(JSON.stringify(this.email.recipients)).filter(email => email.type === 'email').length;
        },
        allAgentsSelected(){

            if (!this.basicData || !this.basicData.userList) return false;

            let emailsDirect = JSON.parse(JSON.stringify(this.email.recipients)).map(email => email.email);

            //Compruebo cuantos correos están metidos
            let countAgentSelected = this.basicData.userList.filter(user => emailsDirect.includes(user.email)).length;

            return this.basicData.userList.length === countAgentSelected;
        },
        allAccountsSelected(){

            if (!this.accounts) return false;

            //Filtro las cuentas y si hay mas de una con el mismo email dejo solo una con cada email
            let accountsReduced = this.accounts.filter((account, index, self) =>
                    index === self.findIndex((t) => (
                        t.email === account.email // Compara los correos
                    ))
            );

            let emailsDirect = JSON.parse(JSON.stringify(this.email.recipients)).map(email => email.email);

            //Compruebo cuantos correos están metidos
            let countAccountsSelected = accountsReduced.filter(acc => emailsDirect.includes(acc.email)).length;

            return accountsReduced.length === countAccountsSelected
        },
        allContactsSelected(){

            if (!this.contacts) return false;

            //Filtro las cuentas y si hay mas de una con el mismo email dejo solo una con cada email
            let contactsReduced = this.contacts.filter((contact, index, self) =>
                    index === self.findIndex((t) => (
                        t.email === contact.email // Compara los correos
                    ))
            );

            let emailsDirect = JSON.parse(JSON.stringify(this.email.recipients)).map(email => email.email);

            //Compruebo cuantos correos están metidos
            let countAccountsSelected = contactsReduced.filter(acc => emailsDirect.includes(acc.email)).length;

            return contactsReduced.length === countAccountsSelected
        },
        filteredList() {
            let list = [];
            let searchTerm = this.searchText.toLowerCase().trim();

            let options = {};
            let fuseAgents, fuseAccounts, fuseContacts = [];

            switch (this.recipientDisplaySelected) {
                case 'agents':

                    if (searchTerm === ''){
                        list = this.basicData.userList;
                    }else{
                        // Opciones para filtrado con Fuse
                        let options = {
                            includeScore: true,       // Incluir puntajes de coincidencia
                            threshold: 0.2,           // Umbral de coincidencia (más estricto)
                            keys: ['fullName', 'firstName', 'lastName', 'email', 'phone', 'dni'], // Campos a buscar
                            tokenize: true,           // Permite que las palabras se tokenicen
                            matchAllTokens: false,    // No requiere que todos los tokens coincidan
                            ignoreLocation: true,     // Ignorar la ubicación de los términos en el campo
                        };

                        let agentsFullName = this.basicData.userList.map(agent => ({
                            ...agent,
                            fullName: `${agent.firstName} ${agent.lastName}`.toLowerCase() // Concatenar nombre y apellido
                        }));

                        fuseAgents = new Fuse(agentsFullName, options);

                        list = fuseAgents.search(searchTerm).map(result => result.item);

                        console.log(list)
                    }

                    break;

                case 'accounts':

                    if (searchTerm === ''){
                        list = this.accounts;
                    }else{
                        // Opciones para filtrado con Fuse
                        options = {
                            includeScore: true,        // Incluir puntajes de coincidencia
                            threshold: 0.3,            // Umbral de coincidencia (0 es exacto, 1 es más permisivo)
                            keys: ['name', 'email', 'phone', 'CIF'],  // Campos a buscar
                            tokenize: true,
                            matchAllTokens: true
                        };

                        fuseAccounts = new Fuse(this.accounts, options); // Usar fuseAccounts para las cuentas
                        list = fuseAccounts.search(searchTerm).map(result => result.item); // Buscar en cuentas
                    }


                    break;

                case 'contacts':

                    if (searchTerm === ''){
                        list = this.contacts;
                    }else{
                        // Opciones para filtrado con Fuse
                        options = {
                            includeScore: true,        // Incluir puntajes de coincidencia
                            threshold: 0.3,            // Umbral de coincidencia (0 es exacto, 1 es más permisivo)
                            keys: [
                                'name.first',
                                'name.second',
                                'surname.first',
                                'surname.second',
                                'email',
                                'phone',
                            ],  // Campos a buscar
                            tokenize: true,
                            matchAllTokens: true
                        };

                        fuseContacts = new Fuse(this.contacts, options); // Usar fuseAccounts para los contactos
                        list = fuseContacts.search(searchTerm).map(result => result.item); // Buscar en contactos
                    }

                    break;
            }

            return list;
        },
        filteredExternalEmails(){
            let recipients = JSON.parse(JSON.stringify(this.email.recipients))

            return recipients.filter(emailNow => emailNow.type === 'email')
        },
        emailsSelected(){
            return JSON.parse(JSON.stringify(this.email.recipients)).map(email => email.email);
        },
        emailSendedSelected(){
            if (!this.emailSendedSelectedId || !this.emails) return null;

            return this.emails.find(email => email._id === this.emailSendedSelectedId)
        },
        emailsSelectedClicked(){
            if (!this.emailSendedSelected) return null;

            return this.emailSendedSelected.recipients.filter(email => email.clicked_at !== null)
        },
        emailsSelectedDelivered(){
            if (!this.emailSendedSelected) return null;

            return this.emailSendedSelected.recipients.filter(email => email.delivered_at !== null)
        },
        emailsSelectedNotDelivered(){
            if (!this.emailSendedSelected) return null;

            return this.emailSendedSelected.recipients.filter(email => email.delivered_at === null)
        },
        emailsSelectedOpenByUser(){
            if (!this.emailSendedSelected) return null;

            return this.emailSendedSelected.recipients.filter(email => (email.opened_at !== null && !email.opened_at.proxy))
        },
        emailsSelectedOpenByGoogle(){
            if (!this.emailSendedSelected) return null;

            return this.emailSendedSelected.recipients.filter(email => (email.opened_at !== null && email.opened_at.proxy))
        },
    }
}
</script>

<style scoped>

::v-deep(.email-preview #content) {
    width: 100% !important;
}

</style>