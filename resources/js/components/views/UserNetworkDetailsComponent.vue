<template>
    <div class="content-white profile" v-if="userToModify">

        <!--Imagen superior-->
        <div class="img-base" :data-bg="basicData.enterprise.color">
            <div class="img-div">
                <img class="general-icon" data-size="big" data-style="square" data-weight="500" :src="'/assets/enterprises/' + basicData.enterprise.asset_folder + '/logos/logo-light.png'" alt="Imagen CRM">
            </div>
        </div>

        <form class="form" v-on:submit.prevent="">

            <!--Parte imagen y botón editar/guardar-->

            <!--Movil-->
            <div class="mobile-item">
                <div class="info-top d-flex justify-between">

                    <div class=" d-flex column align-center justify-center relPos">
                        <img :alt="userToModify.firstName" class="img-profile" data-size="myProfile" :src="image">
                        <input ref="profileImg" id="profileImg" style="display:none" v-on:change="pickupImage" class="b" type="file" accept="image/png, image/jpeg, image/jpg">
                        <div v-if="isEditing" class="pointer custom-button" data-size="medium" data-position="bottom" data-bg="principal" v-on:click="openDialog">Cambiar</div>
                    </div>

                    <!--Botón editar/guardar-->
                    <div class=" my-auto d-flex justify-end">

                        <button class="custom-button pointer" data-size="medium" data-bg="blanco" v-if="isEditing" v-on:click="cancelChanges"><i class="fas fa-xmark"></i></button>
                        <button class="custom-button pointer ml-10" data-size="medium" v-if="!isReadOnly && isEditing" v-on:click="saveProfile"><i class="fas fa-floppy-disk"></i></button>
                        <button class="custom-button pointer" data-size="medium" v-else-if="!isReadOnly" v-on:click="isEditing = true"><i class="fas fa-pen"></i></button>
                    </div>
                </div>

                <!--Info-->
                <div class="mt-40 mb-50">

                    <p class="text" data-size="20" data-weight="700" :data-color="(!userToModify.isActive && (!userToModify.temporalActive || (!!userToModify.temporalActive && new Date(userToModify.temporalActive) < new Date())) && !userToModify.inactivable) ? 'rojo' : 'azul'">Perfil de {{ userToModify.firstName }} {{ userToModify.lastName }}</p>

                    <p class="text" data-size="13">Actualiza los datos de acceso y demás parámetros de su perfil</p>
                </div>
            </div>


            <!--Escritorio-->
            <div class="desktop-item">
                <div class="info-top d-flex w-100">

                    <div class="w-20 d-flex column align-center justify-center relPos">
                        <img :alt="userToModify.firstName" class="img-profile" data-size="myProfile" :src="image">
                        <input ref="profileImg" id="profileImg" style="display:none" v-on:change="pickupImage" class="b" type="file" accept="image/png, image/jpeg, image/jpg">
                        <div v-if="isEditing" class="pointer custom-button" data-size="regular" data-position="bottom" data-bg="principal" v-on:click="openDialog">Cambiar</div>
                    </div>

                    <!--Info-->
                    <div class="w-50 my-auto">

                        <div class="d-flex">
                            <p class="text" data-size="22" data-weight="700" :data-color="(!userToModify.isActive && (!userToModify.temporalActive || (!!userToModify.temporalActive && new Date(userToModify.temporalActive) < new Date())) && !userToModify.inactivable) ? 'rojo' : 'azul'">Perfil de {{ userToModify.firstName }} {{ userToModify.lastName }}</p>

                            <i v-if="!userToModify.isActive && (!userToModify.temporalActive || (!!userToModify.temporalActive && new Date(userToModify.temporalActive) < new Date())) && !userToModify.inactivable" class="fa-solid fa-triangle-exclamation fa-bounce ml-10 my-auto" data-size="15" style="color: #ff0000;"></i>
                        </div>

                        <p class="text" data-size="15">Actualiza los datos de acceso y demás parámetros de su perfil</p>
                    </div>


                    <!--Botón editar/guardar-->
                    <div class="w-30 my-auto d-flex justify-end">

                    <!-- Documentos (no depende de edición) -->
                    <button
                        class="custom-button pointer mx-10"
                        data-size="regular"
                        data-bg="azul"
                        @click="isSeeingDocs = !isSeeingDocs"
                    >
                        Documentos
                    </button>

                    <!-- Cancelar (solo cuando estás editando) -->
                    <button
                        class="custom-button pointer mx-10"
                        data-size="regular"
                        data-bg="rojo"
                        v-if="isEditing"
                        @click="cancelChanges"
                    >
                        Cancelar
                    </button>

                    <!-- Guardar (editando + permiso) -->
                    <button
                        class="custom-button pointer mx-10"
                        data-size="regular"
                        v-if="!isReadOnly && isEditing && canManage('users.edit')"
                        @click="saveProfile"
                    >
                        Guardar
                    </button>

                    <!-- Editar (no editando + permiso) -->
                    <button
                        class="custom-button pointer"
                        data-size="regular"
                        v-else-if="!isReadOnly && canManage('users.edit')"
                        @click="isEditing = true"
                    >
                        Editar
                    </button>

                </div>

                </div>
            </div>


            <!--División campos-->
            <div class="fields mt-30">

                <!--Nombre-->
                <div v-bind:class="{ wrong: errors.firstName}" class="form-group my-0">

                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Nombre</p>

                        <div class="input-group ml-10 w-50">
                            <input v-on:focus="delete errors.firstName" v-model="userToModify.firstName" :disabled="!isEditing" type="text" name="newPassword">
                        </div>
                    </div>

                    <span v-if="errors.firstName" class="error">{{ errors.firstName }}</span>

                    <div class="separator my-15"></div>
                </div>


                <!--Apellidos-->
                <div v-bind:class="{ wrong: errors.lastName}" class="form-group my-0">

                    <div class="d-flex justify-between align-center">

                        <p class="text input-name">Apellidos</p>

                        <div class="input-group ml-10 w-50">
                            <input v-on:focus="delete errors.lastName" v-model="userToModify.lastName" :disabled="!isEditing" type="text" name="newPassword">
                        </div>
                    </div>

                    <span v-if="errors.lastName" class="error">{{ errors.lastName }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--Correo-->
                <div v-bind:class="{ wrong: errors.email}" class="form-group my-0">

                    <div class="d-flex justify-between align-center">

                        <p class="text input-name">Correo</p>

                        <div class="input-group ml-10 w-50">
                            <input v-on:focus="delete errors.email" v-model="userToModify.email" :disabled="!isEditing" type="text" name="newPassword">
                        </div>
                    </div>

                    <span v-if="errors.email" class="error">{{ errors.email }}</span>

                    <div class="separator my-15"></div>
                </div>


                <!--Telefono-->
                <div v-bind:class="{ wrong: errors.phone}" class="form-group my-0">

                    <div class="d-flex justify-between align-center">

                        <p class="text input-name">Teléfono</p>


                        <div class="input-group ml-10 w-50">
                            <input v-on:focus="delete errors.phone" v-model="userToModify.phone" :disabled="!isEditing" type="text" name="newPassword">
                        </div>
                    </div>

                    <div v-if="isEditing" class="custom-button w-20 my-15 ml-auto" data-size="small" data-bg="azul" data-mode="translucent" @click="addSecondaryPhone">
                        Añadir teléfono secundario
                    </div>

                    <div v-if="user.secondaryPhones?.length > 0 || userToModify.secondaryPhones?.length > 0" class="d-flex justify-between mt-20">

                        <p class="text input-name">Teléfonos secundarios</p>

                        <div class="w-50 d-flex column align-end" data-gap="10">
                            <div v-for="(secondaryPhone, index) of userToModify.secondaryPhones" class="d-flex align-center w-100" data-gap="5">
                                <div class="input-group ml-10 w-100">
                                    <input v-on:focus="delete errors.secondaryPhones" v-model="userToModify.secondaryPhones[index]" :disabled="!isEditing" type="text" name="secondaryPhone">
                                </div>
                                <i v-if="isEditing" class="far fa-close" @click="deleteSecondaryPhone(index)"/>
                            </div>
                        </div>
                    </div>

                    <span v-if="errors.secondaryPhones" class="error">{{ errors.secondaryPhones }}</span>

                    <span v-if="errors.phone" class="error">{{ errors.phone }}</span>

                    <div class="separator my-15"></div>
                </div>


                <!--dni-->
                <div v-bind:class="{ wrong: errors.dni}" class="form-group my-0">

                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">DNI/CIF</p>

                        <div class="input-group ml-10 w-50">
                            <input v-on:focus="delete errors.dni" v-model="userToModify.dni" :disabled="!isEditing" type="text" name="newPassword">
                        </div>
                    </div>

                    <span v-if="errors.dni" class="error">{{ errors.dni }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--género-->
                <div v-bind:class="{ wrong: errors.gender}" class="form-group my-0">

                    <div class="d-flex justify-between align-center">

                        <p class="text input-name">Género</p>

                        <div class="input-group ml-10 w-50">
                            <select v-model="userToModify.gender" v-on:focus="delete errors.gender" :disabled="!isEditing">
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                                <option value="O">Otro</option>
                            </select>
                        </div>
                    </div>
                    <span v-if="errors.gender" class="error">{{ errors.gender }}</span>

                    <div class="separator my-15"></div>
                </div>


                <!--Dirección-->
                <div v-bind:class="{ wrong: errors.address}" class="form-group my-0">

                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Dirección</p>

                        <div class="input-group ml-10 w-50">
                            <input v-on:focus="delete errors.address" v-model="userToModify.address" :disabled="!isEditing" type="text" name="newPassword">
                        </div>
                    </div>

                    <span v-if="errors.address" class="error">{{ errors.address }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--Código postal-->
                <div v-bind:class="{ wrong: errors.postal}" class="form-group my-0">

                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Código postal</p>

                        <div class="input-group ml-10 w-50">
                            <input v-on:focus="delete errors.postal" v-model="userToModify.postal" :disabled="!isEditing" type="text" name="newPassword">
                        </div>
                    </div>

                    <span v-if="errors.postal" class="error">{{ errors.postal }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--Población-->
                <div v-bind:class="{ wrong: errors.locality}" class="form-group my-0">

                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Población</p>

                        <div class="input-group ml-10 w-50">
                            <input v-on:focus="delete errors.locality" v-model="userToModify.locality" :disabled="!isEditing" type="text" name="newPassword">
                        </div>
                    </div>

                    <span v-if="errors.locality" class="error">{{ errors.locality }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--Provincia-->
                <div v-bind:class="{ wrong: errors.province}" class="form-group my-0">

                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Provincia</p>

                        <div class="input-group ml-10 w-50">
                            <input v-on:focus="delete errors.province" v-model="userToModify.province" :disabled="!isEditing" type="text" name="newPassword">
                        </div>
                    </div>

                    <span v-if="errors.province" class="error">{{ errors.province }}</span>

                    <div class="separator my-15"></div>
                </div>
            </div>


            <!--Permisos, contraseña y tipo de comisión-->
            <div class="d-grid" data-column="2">

                <!--Permisos y contraseña-->
                <div class="form-group mt-20 p-17">

                    <!--Etiqueta-->
                    <div>
                        <p class="text mb-10" data-size="18" data-weight="600">Etiqueta</p>

                        <div v-bind:class="{ wrong: errors.label }" class="form-group my-0">
                            <div class="d-flex justify-between align-center">
                                <div class="input-group ml-10 w-50">
                                    <select
                                        v-model="userToModify.label"
                                        @change="changeUserLabel"
                                        :disabled="!isEditing"
                                    >
                                        <option
                                            v-for="label in filteredLabels"
                                            :key="label"
                                            :value="label"
                                        >
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <span v-if="errors.label" class="error">{{ errors.label }}</span>
                        </div>

                        <!-- info -->
                        <div
                            class="form-group mt-20"
                            v-if="labelDescriptions[userToModify.label]"
                        >
                            <div class="label-info-card">
                                <p class="text" data-size="16" data-weight="700">
                                    {{ labelDescriptions[userToModify.label].title }}
                                </p>

                                <p class="text opacity-6 mt-5" data-size="12">
                                    {{ labelDescriptions[userToModify.label].desc }}
                                </p>

                                <div class="label-warning mt-15">
                                    <p class="text opacity-5" data-size="11">
                                        ℹ️ Los permisos asociados a esta etiqueta se gestionan desde la
                                        <b>herramienta de permisos</b>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Tramitar con otros ( por ahora solo Fotón )-->
                    <div v-if="isDirectAgentSubdomain && basicData && basicData.userSubdomain._id === '68edfbf7c84a190aeb0a6672'">
                        <p class="text mb-10" data-size="18" data-weight="600">Tramitar con:</p>

                        <multiple-select-component :options="filteredOptions" v-model:selectedOptions="userToModify.canAssignTo" :canEdit="isEditing"></multiple-select-component>
                    </div>


                    <!--Contraseña-->
                    <div>
                        <p class="text mt-80 mb-10" data-size="18" data-weight="600">Contraseña</p>

                        <div v-bind:class="{ wrong: errors.password}" class="form-group my-0">

                            <div class="d-flex justify-between align-center">

                                <div class="input-group w-100">
                                    <input v-on:focus="delete errors.password" v-model="newPassword" :disabled="!isEditing" type="text" name="newPassword">
                                </div>
                            </div>

                            <span v-if="errors.password" class="error">{{ errors.password }}</span>

                        </div>
                    </div>


                    <!--Otras opciones-->
                    <div>
                        <p class="text mt-80 mb-10" data-size="18" data-weight="600">Otras opciones</p>

                        <!-- Usuario activo -->
                        <div class="d-flex my-5">

                            <div class="custom-checkbox my-auto mr-10" v-on:click="isEditing && toggleUserActive()">
                                <div v-bind:class="{ selected: userToModify.isActive }"></div>
                            </div>

                            <p class="my-auto mr-15" data-color="principal" data-size="10">
                                Usuario activo
                            </p>

                        </div>

                        <!--Cuenta deshabilitada-->
                        <div v-if="!userToModify.isActive && !userToModify.inactivable" class="form-group my-0">

                            <p class="text mt-20 mb-10" data-size="15" data-weight="500" data-color="rojo">Cuenta deshabilitada</p>

                            <div v-bind:class="{ wrong: errors.temporalActive }" class="form-group w-fit-content">
                                <p class="my-auto">Fecha límite</p>

                                <div class="input-group">
                                    <input v-on:focus="delete errors['temporalActive']" data-size="10"
                                           v-model="userToModify.temporalActive" :disabled="!isEditing"
                                           type="date">
                                </div>
                                <span v-if="errors.temporalActive" class="error">{{ errors.temporalActive }}</span>
                            </div>
                        </div>

                        <!--Hacer cuenta inhabilitable-->
                        <div class="d-flex my-5" v-if="basicData.userLogged && basicData.userLogged._id === '65cb57489c2c285441086a43'">

                            <div class="custom-checkbox my-auto mr-10" v-on:click="isEditing && toggleInactivable()">
                                <div v-bind:class="{ selected: userToModify.inactivable }"></div>
                            </div>

                            <p class="my-auto mr-15" data-color="principal" data-size="10">Hacer cuenta inhabilitable</p>
                        </div>

                        <!--Comisiones por ptos en vez de por €-->
                        <div class="d-flex my-5">

                            <div class="custom-checkbox my-auto mr-10" v-on:click="isEditing && toggleCommInPoints()">
                                <div v-bind:class="{ selected: userToModify.commInPoints }"></div>
                            </div>

                            <p class="my-auto mr-15" data-color="principal" data-size="10">Ver comisiones en puntos</p>
                        </div>

                        <!--No recibir emails de cambio de estado-->
                        <div class="d-flex my-5">

                            <div class="custom-checkbox my-auto mr-10" v-on:click="isEditing && toggleSendStatusEmails()">
                                <div v-bind:class="{ selected: userToModify.notSendStatusEmails }"></div>
                            </div>

                            <p class="my-auto mr-15" data-color="principal" data-size="10">No recibir correos de cambio de estado</p>
                        </div>

                        <!--Autofacturas-->
                        <div class="d-flex my-5">

                            <div class="custom-checkbox my-auto mr-10" @click="isEditing && (userToModify.selfInvoicing = !userToModify.selfInvoicing)">
                                <div :class="{ selected: userToModify.selfInvoicing }"></div>
                            </div>

                            <p class="my-auto mr-15" data-color="principal" data-size="10">Autofacturas</p>
                        </div>



                        <!--Listado de usuarios-->
                        <div class="form-group mt-20">
                            <label class="mb-20">Usuarios responsables</label>

                            <!--Lista de usuarios que quiero que sean responsables del usuario que voy a crear-->
                            <user-list-component :basicData="basicData" v-model:userListSelected="userToModify.responsibles" :userListToExclude="[userToModify]" :requireOneSelected="true" :principalUserId="originalResponsibleId" :editing="isEditing"></user-list-component>

                            <p v-if="basicData.userList.length === 0" class="text opacity-3" data-size="10">No tienes usuarios para asignar</p>
                        </div>
                    </div>
                </div>



                <div class="form-group mt-20 p-17">

                    <!--Tipo de comisión-->
                    <div v-if="userToModify.commissions">
                        <div class="d-flex justify-between">
                            <p class="text  mb-10" data-size="18" data-weight="600"> Comisiones {{ userToModify.label === 'Usuario subdominio' ? 'Zoco' : '' }}</p>


                            <div v-if="isEditing" class="input-group">
                                <select v-model="commissionsType" @change="resetCommissions()" :disabled="!isEditing">
                                    <option value="range">Rangos</option>
                                    <option value="percentage">Porcentajes</option>
                                    <option value="fixed">Fijo</option>
                                    <option v-if="basicData && basicData.userSubdomain && basicData.userSubdomain._id === '67e26f1dc20d526af10eda92'" value="contracts">Contratos</option>
                                </select>
                            </div>
                        </div>

                        <span v-if="errors.commissions" class="error">{{ errors.commissions }}</span>
                        <div class="form-group my-20">
                            <div v-if="isEditing" class="d-flex justify-between align-center">

                                <div class="d-flex align-center">
                                    <div @click="toggleAllMarketers('a')" :class="{'pointer': isEditing}">
                                        <i :class="[userToModify.marketers.length > 0 ? 'fa-eye' : 'fa-eye-slash opacity-6','far text']" />
                                    </div>
                                    <p class="text input-name ml-10">Todas las comercializadoras</p>
                                </div>

                                <div v-if="commissionsType && commissionsType !== 'contracts'" class="input-group ml-10 w-50">
                                    <select v-if="commissionsType === 'range'" :disabled="!isEditing" @change="updateCommissions($event)">
                                        <option value="" selected disabled>Selecciona una opción</option>
                                        <option v-for="option in basicData?.enterprise?.commissionRanges" :value="option._id">{{option.name}}</option>
                                    </select>
                                    <template v-else-if="commissionsType === 'percentage'">
                                        <input :disabled="!isEditing" @change="updateCommissions($event)"/><span class="text">%</span>
                                    </template>
                                    <template v-else-if="commissionsType === 'fixed'">
                                        <input :disabled="!isEditing" @change="updateCommissions($event)"/>
                                        <span class="text ml-10" :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                                    </template>
                                </div>
                            </div>

                            <div class="separator my-30"></div>
                        </div>

                        <div v-bind:class="{ wrong: errors.commissions}" v-for="marketer of visibleMarketers" class="form-group my-20">

                            <div :class="[userToModify.marketers.includes(marketer['_id']) ? '' : 'opacity-6','d-flex justify-between align-center']">

                                <div class="d-flex align-center">
                                    <div @click="toggleMarketer(marketer['_id'])" :class="{'pointer': isEditing}">
                                        <i :class="[userToModify.marketers.includes(marketer['_id']) ? 'fa-eye' : 'fa-eye-slash','far text']" />
                                    </div>
                                    <div class="w-35-px d-flex justify-center relPos">
                                        <img :src="`/assets/marketers_logo/${marketer['logo']}`"
                                             class="h-25-px-max w-30-px-max contain-image"/>
                                        <img v-if="marketer.isZoco" src="/assets/enterprises/zocoenergia/logos/mini-dark.png" class="absPos h-5-px" style="bottom: -5px; right: 5px" />

                                    </div>
                                    <p class="text input-name">{{ marketer["name"] }}</p>
                                </div>

                                <div class="d-flex" data-gap="10">
                                    <div class="input-group" v-if="userToModify.commissions[marketer['_id']]">
                                        <select v-model="userToModify.commissions[marketer['_id']].type" @change="resetCommissions(marketer['_id'])"
                                                :disabled="!isEditing">
                                            <option value="range">Rangos</option>
                                            <option value="percentage">Porcentajes</option>
                                            <option value="fixed">Fijo</option>
                                            <option v-if="basicData && basicData.userSubdomain && basicData.userSubdomain._id === '67e26f1dc20d526af10eda92'" value="contracts">Contratos</option>
                                        </select>
                                    </div>
                                    <div class="input-group ml-10 w-150-px">
                                        <select v-if="userToModify.commissions[marketer['_id']]?.type === 'range'" v-model="userToModify.commissions[marketer['_id']].value" v-on:focus="delete errors.commisions" :disabled="!isEditing">
                                            <option v-for="option in basicData?.enterprise?.commissionRanges" :value="option._id">{{option.name}}</option>
                                        </select>
                                        <template v-else-if="userToModify.commissions[marketer['_id']]?.type === 'percentage'">
                                            <input v-model="userToModify.commissions[marketer['_id']].value" v-on:focus="delete errors.commisions" :disabled="!isEditing" class="text-end w-100-px"/><span class="text ml-10">%</span>
                                        </template>
                                        <template v-else-if="userToModify.commissions[marketer['_id']]?.type === 'fixed'">
                                            <input v-model="userToModify.commissions[marketer['_id']].value" v-on:focus="delete errors.commisions" :disabled="!isEditing" class="text-end w-100-px"/>
                                            <span class="text ml-10" :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                                        </template>
                                        <!--BTV-->
                                        <template v-else-if="userToModify.commissions[marketer['_id']]?.type === 'contracts'">
                                            <input v-model="userToModify.commissions[marketer['_id']].value" :disabled="!isEditing" class="text-end w-30-px"/><span class="text ml-10">contratos</span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="separator my-15"></div>
                        </div>
                    </div>

                    <div v-else>
                        <p class="text  mb-10" data-size="18" data-weight="600">Comercializadoras</p>
                        <div v-for="marketer of visibleMarketers" class="form-group my-20">

                            <div :class="[userToModify.marketers.includes(marketer['_id']) ? '' : 'opacity-6','d-flex justify-between align-center']">

                                <div class="d-flex align-center">
                                    <div @click="toggleMarketer(marketer['_id'])" :class="{'pointer': isEditing}">
                                        <i :class="[userToModify.marketers.includes(marketer['_id']) ? 'fa-eye' : 'fa-eye-slash','far text']" />
                                    </div>
                                    <div class="w-35-px d-flex justify-center relPos">
                                        <img :src="`/assets/marketers_logo/${marketer['logo']}`" class="h-20-px w-30-px-max contain-img"/>
                                        <img v-if="marketer.isZoco" src="/assets/enterprises/zocoenergia/logos/mini-dark.png" class="absPos h-15-px" style="bottom: 10px; right: 10px" />
                                    </div>
                                    <p class="text input-name">{{ marketer["name"] }}</p>
                                </div>
                            </div>

                            <div class="separator my-15"></div>
                        </div>
                    </div>



                </div>
            </div>

            <!--Documentos de usuario-->
            <div class="docs no-scroll" v-if="isSeeingDocs">

                <div class="d-flex justify-between">
                    <p class="text" data-size="15" data-weight="700">Docs. adjuntos</p>

                    <div class="custom-button ml-auto my-auto" data-size="small" data-bg="amarillo"
                         v-if="isEditing" v-on:click="openDialogDoc"><i class="fas fa-plus"></i></div>
                </div>

                <input id="userFiles" type="file" style="display: none" multiple v-on:change="pickupDoc">

                <div class="div-content">
                    <doc-component v-for="(doc, docInd) in publicUserDocs" :doc="doc" :docInd="docInd"
                                   :isReadOnly="!isEditing" directory="profile_images" @delDoc="delDoc(docInd)"></doc-component>
                </div>
                <div class="form-group" v-if="isEditing">
                <label>Usuario activo</label>

                <div class="input-group">
                    <select v-model="userToModify.isActive">
                        <option :value="true">Activo</option>
                        <option :value="false">Deshabilitado</option>
                    </select>
                </div>
            </div>

                <div class="separator"></div>


                <div class="d-flex justify-end">
                    <button class="custom-button mr-10" data-size="small" data-bg="rojo"
                            v-on:click.prevent="isSeeingDocs = false">Cerrar</button>
                </div>

            </div>

        </form>
    </div>
</template>

<script>
import {forEach} from "lodash";
import MultipleSelectComponent from "../items/MultipleSelectComponent.vue";

export default {
    name: "UserNetworkDetailsComponent",
    components: {MultipleSelectComponent},
    props: ['basicData'],
    data(){
        return{
            originalLabel: null,
            user: '',
            userToModify: '',
            urlImage: '',
            isEditing: false,
            errors:{},
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
            customFinalDate: '',
            marketers: [],
            zocoMarketers: [],
            isSeeingDocs: false,
            newPassword: '',
            commissionsType: null,
            originalResponsibleId: null
        }
    },
    watch:{
        "basicData.userList"(){
            this.fetchUserInfo()
        }
    },
    mounted() {
        if (this.basicData.userList)
            this.fetchUserInfo()

    },
    methods: {
        toggleUserActive(){

            this.userToModify.isActive = !this.userToModify.isActive

            if (this.userToModify.isActive)
                delete this.userToModify.temporalActive
            else
                this.userToModify.temporalActive = ''

        },
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

       fetchUserInfo(){

            const hasHierPermission = this.canManage('users.admiWhiHier')
            const listToUse = (hasHierPermission && this.basicData?.subdomainUserList?.length)
                ? this.basicData.subdomainUserList
                : this.basicData.userList

            this.user = listToUse?.find((userNow) => {
                return userNow._id === this.$route.params.id
            })

            if (!this.user) return

            this.userToModify = JSON.parse(JSON.stringify(this.user))

            if (this.userToModify.temporalActive === '2000-01-01') {
                this.userToModify.temporalActive = ''
            }

            this.originalLabel = this.userToModify.label
            if (!this.userToModify.canAssignTo) this.userToModify.canAssignTo = []


            Promise.all([
               this.getZocoMarketers(),
               this.getMarketers()
            ]).then(() => {

               const currentCommissions = this.userToModify.commissions || {};
               const newCommissions = {};

                const allMarketers = [
                    ...this.marketers,
                    ...this.zocoMarketers
                ];

                for (let marketer of allMarketers) {
                    newCommissions[marketer._id] = currentCommissions[marketer._id] || {
                        type: null,
                        value: null
                    };
                }

               this.userToModify.commissions = newCommissions;
               this.user.commissions = JSON.parse(JSON.stringify(newCommissions));
            });

            if(this.userToModify.label === 'Usuario demo'){

                if (!['1', '7', '15', '31'].includes(this.userToModify.demoExpiration)){

                    this.durationSelect = 'p'

                    this.customFinalDate = moment(this.userToModify.demoStartDate * 1000)
                        .add(this.userToModify.demoExpiration, 'days')
                        .format('YYYY-MM-DD')

                }else{
                    this.durationSelect = this.userToModify.demoExpiration
                }
            }


            this.originalResponsibleId = this.userToModify.responsibles[0]
        },
        async getMarketers(){

            let params = {};

            if (this.userToModify.label !== 'Usuario subdominio') {
                params.user = this.userToModify;
            }

            await axios.get("/api/marketers", {
                params //Si es usuario subdominio no le paso el usuario para que así me salgan las de Zoco que son las que se van a usar para los principales
            }).then((res) => {
                this.marketers = res.data.marketers;
            }).catch((error) => console.log(error))
        },
        async getZocoMarketers(){
            await axios.get("/api/marketers",{
                params: { assignContractTo: '65cb57489c2c285441086a43' }
            }).then((res) => {
                this.zocoMarketers = res.data.marketers;
            }).catch((error) => console.log(error))
        },
        cancelChanges(){
            this.isEditing = false

            this.userToModify = JSON.parse(JSON.stringify(this.user));
        },
        saveProfile(){


            this.errors = {};
            let hasErrors = false;

            //Nombre
            this.firstName = this.userToModify.firstName.trim();
            if (this.isEmpty(this.userToModify.firstName)){
                this.errors.firstName = this.getErrorMessage('isEmpty')
                hasErrors = true;
            }

            //Apellidos
            this.userToModify.lastName = this.userToModify.lastName.trim();
            if (this.isEmpty(this.userToModify.lastName)){
                this.errors.lastName = this.getErrorMessage('isEmpty')
                hasErrors = true;
            }

            //Género
            this.userToModify.gender = this.userToModify.gender.trim();
            if (this.isEmpty(this.userToModify.gender)){
                this.errors.gender = this.getErrorMessage('isEmpty')
                hasErrors = true;
            }

            //DNI
            this.userToModify.dni = this.userToModify.dni.trim();
            //let regex = /^\d{8}[A-Z]$/;
            if (this.isEmpty(this.userToModify.dni)) {
                this.errors.dni = this.getErrorMessage('isEmpty')
                hasErrors = true;
            } /*else {
                if(!regex.test(this.userToModify.dni)){
                    this.errors.dni = 'Esta cadena no es del tipo eDNI'
                    hasErrors = true;
                } else {
                    let letra = 'TRWAGMYFPDXBNJZSQVHLCKET';
                    let numero = parseInt(this.userToModify.dni.substring(0,8), 10);
                    if(this.userToModify.dni.substring(8,9) !== letra[numero % 23]){
                        this.errors.dni = 'DNI no valido'
                        hasErrors = true;
                    }
                }
            }*/


            //email
            this.userToModify.email = this.userToModify.email.trim();
            if (this.isEmpty(this.userToModify.email)){
                this.errors.email = this.getErrorMessage('isEmpty')
                hasErrors = true;
            }
            let regex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
            if(!regex.test(this.userToModify.email)){
                this.errors.email = 'Esta cadena no es del tipo correo electrónico'
                hasErrors = true;
            }

            //teléfono
            this.userToModify.phone = this.userToModify.phone.trim();
            /*if (this.isEmpty(this.userToModify.phone)){
                this.errors.phone = this.getErrorMessage('isEmpty')
                hasErrors = true;
            }*/
            if (this.userToModify.phone && !this.hasOnlyNumbers(this.userToModify.phone)){
                this.errors.phone = this.getErrorMessage('hasOnlyNumbers')
                hasErrors = true;
            }

            //teléfonos secundarios
            if(this.userToModify.secondaryPhones){
                if(this.userToModify.secondaryPhones.length > 0){
                    forEach(this.userToModify.secondaryPhones, (phone, ind) => {
                        //Borro espacios innecesarios
                        this.userToModify.secondaryPhones = this.userToModify.secondaryPhones.map(phone => phone.trim());

                        //Elimino aquellos teléfonos vacíos
                        this.userToModify.secondaryPhones = this.userToModify.secondaryPhones.filter(phone => !this.isEmpty(phone));

                        //Valido
                        this.userToModify.secondaryPhones.forEach(phone => {
                            if (!this.hasOnlyNumbers(phone)) {
                                this.errors.secondaryPhones = this.getErrorMessage('hasOnlyNumbers');
                                hasErrors = true;
                            }
                        });

                        //Borro el array si no tiene ninguno
                        if (this.userToModify.secondaryPhones.length === 0) {
                            delete this.userToModify.secondaryPhones;
                        }
                    })
                }else{
                    delete this.userToModify.secondaryPhones;
                }
            }

            //Dirección
            this.userToModify.address = this.userToModify.address.trim();

            //Código Postal
            this.userToModify.postal = this.userToModify.postal.trim();
            if (!!this.userToModify.postal && this.userToModify.postal !== '' && !this.hasOnlyNumbers(this.userToModify.postal)){
                this.errors.postal = this.getErrorMessage('hasOnlyNumbers')
                hasErrors = true;
            }

            //Comisiones
            if(this.userToModify.commissions){
                for(let commission of Object.keys(this.userToModify.commissions)){
                    let commissionType = this.userToModify.commissions[commission];
                    if(commissionType === null){
                        delete this.userToModify.commissions[commission];
                    }
                }

                //Si no tiene ninguna comision asignada, borro el objeto de comisiones
                if(Object.keys(this.userToModify.commissions).length === 0){
                    delete this.userToModify.commissions;
                }
            }

            //Fecha de expedición demo
            if (this.userToModify.label === 'Usuario demo' && this.userToModify.demoExpiration < 0){
                this.errors.userDuration = 'La fecha final debe ser posterior a la actual'
                hasErrors = true;
            }

            if(hasErrors){
                Swal.fire({
                    icon:'error',
                    title: 'Comprueba todo...',
                    text: 'Parece que hay fallos en algún campo del formulario. Compruébalos y vuélvelo a intentar'
                })
            }else{

                if (this.userToModify.commissions){
                    let commissionsEntries = Object.entries(this.userToModify.commissions);
                    this.userToModify.commissions = Object.fromEntries(commissionsEntries)
                }

                if (
                    this.basicData?.userLogged?.label !== 'Usuario subdominio' &&
                    this.userToModify.label !== this.originalLabel &&
                    this.userToModify.label !== 'Usuario'
                ) {
                    this.userToModify.label = this.originalLabel;
                }

                let data = new FormData();
                data.append('user', JSON.stringify(this.userToModify));

                //Meto los documentos de los pedidos
                if (this.userToModify.docs.length > 0){
                    this.userToModify.docs.forEach((doc, docInd) => {
                        data.append(('docFile' + docInd), doc.fileValue);
                    })
                }

                data.append('newPassword', this.newPassword);




                axios.post('/api/user/update', data)
                    .then((res) => {

                        Swal.fire({
                            icon: 'success',
                            title: 'Perfil actualizado',
                            html: `El perfil ha sido actualizado correctamente!`
                        }).then((res) => {
                            this.isSeeingDocs = false;
                            this.$router.push('/users')
                        })
                    })
                    .catch((err) => {
                        this.errors = err.response.data.errors;
                    })
            }
        },
        isEmpty(value){
            return value === '';
        },
        hasOnlyNumbers(value) {
            return (value !== '' && /^\d+$/.test(value));
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
        openDialog(){
            $('#profileImg').click()
        },
        pickupImage() {
            let input = $('#profileImg');

            if (input.prop('files')) {
                let file = input.prop('files')[0];

                if (file['type'] === 'image/jpeg' || file['type'] === 'image/png' || file['type'] === 'image/png') {
                    this.imageFile = file;

                    this.urlImage = URL.createObjectURL(this.imageFile);

                    this.updateImage();
                } else {
                    Swal.fire({
                        title: 'Eh... Vaya',
                        text: 'Lo que has tratado de subir no es una imagen. Tu imagen debe estar en formato .JPG o .PNG.',
                        icon: 'info'
                    })
                }
            }
        },
        openDialogDoc() {
            $('#userFiles').click();
        },
        pickupDoc() {

            let input = $('#userFiles');
            if (input.prop('files')) {
                let files = input.prop('files');

                //Para cada uno de los archivos seleccionados
                for (let file of files) {

                    //Añado el doc al pedido
                    let docInfo = {
                        title: '',
                        defaultTitle: file.name,
                        value: '', //Aqui se va a guardar el nombre del archivo
                        fileValue: file, //Aqui se va a meter el archivo en sí,
                        privacyType: 'public',
                        creator: this.basicData.userLogged._id,
                        errors: {}
                    }

                    //Meto el doc en el pedido
                    this.userToModify.docs.push(docInfo);
                }
            }
        },
        delDoc(ind) {
            //Compruebo si hay que borrarlo dela bbdd( como uso el mismo componente para crear y editar puede o no que se haya guardado)
            this.userToModify.docs.splice(ind, 1);
        },
        updateImage() {
            let config = {
                headers: {'content-type': 'multipart/form-data'}
            }

            let data = new FormData();
            data.append('file', this.imageFile);

            this.isLoading = true;
            axios.post(`/api/user/updateImage/${this.userToModify._id}`, data, config).then((res) => {
                Swal.fire({
                    title: '¡Eureka!',
                    text: 'La imagen de perfil se actualizó con éxito',
                    icon: 'success'
                })

                this.isLoading = false;

                this.$emit('updateUser', res.data.user);

                this.imageFile = '';
            }).catch((err) => {
                this.errors = err.response.data.errors;
            })
        },
        toggleSelectPermission(permission){

            let index = (this.userToModify.permissions.indexOf(permission.code));

            if (index !== -1)
                this.userToModify.permissions.splice(index,1);
            else
                this.userToModify.permissions.push(permission.code);
        },
        changeUserLabel(event){

            delete this.errors['label']

            //si se pone usuario demo se le añade tiempo expiración, sino se quita si tiene
            if (event.target.value === 'Usuario demo'){

                this.userToModify.demoExpiration = '31'
                this.userToModify.demoStartDate = moment().unix()

            }else
                if (!!this.userToModify.demoExpiration){
                    delete this.userToModify.demoExpiration
                    delete this.userToModify.demoStartDate
                }

        },
        setUserDemoDuration(event){
            if (event.target.value !== 'p'){
                this.userToModify.demoExpiration = event.target.value
                this.userToModify.demoStartDate = moment().unix()
            }

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
            this.userToModify.demoExpiration = Math.ceil(diffInTime / (1000 * 3600 * 24)).toString();
            this.userToModify.demoStartDate = moment().unix()
        },
        logout(){

            axios.get(`/api/auth/logout`)
                .then((res) => {
                    this.$router.push('/')
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        resetCommissions(marketerId = null){
            if(marketerId){
                this.userToModify.commissions[marketerId].value = null;
            }else {
                for (const marketer of this.visibleMarketers) {
                    const marketerCommission = this.userToModify.commissions[marketer._id];

                    //Actualizo el valor solo si cambia el tipo de comision
                    if (marketerCommission.type !== this.commissionsType) {
                        marketerCommission.value = null;
                    }

                    marketerCommission.type = this.commissionsType;
                }
            }
        },
        toggleMarketer(marketer){
            //Compruebo que está editando y que puede editar
            if(!(this.isEditing)){
                return;
            }

            //Actualizo en this.userToModify y añado/borro el marketer
            if(this.userToModify.marketers.includes(marketer)){
                this.userToModify.marketers.splice(this.userToModify.marketers.indexOf(marketer),1);
            }else{
                this.userToModify.marketers.push(marketer);
            }
        },
        toggleAllMarketers() {
            if (this.userToModify.marketers.length === 0) {
                this.visibleMarketers.forEach(marketer => {this.userToModify.marketers.push(marketer._id)} );
            } else {
                this.userToModify.marketers = [];
            }
        },
        updateCommissions(event){
            const value = event.target.value;

            for (let marketer of this.visibleMarketers) {
                this.userToModify.commissions[marketer._id].value = value;
            }
        },
        toggleInactivable(){
            if (!this.userToModify.inactivable)
                this.userToModify.inactivable = true
            else
                delete this.userToModify.inactivable
        },
        toggleCommInPoints() {
            if (!this.userToModify.commInPoints)
                this.userToModify.commInPoints = true
            else
                delete this.userToModify.commInPoints
        },
        toggleSendStatusEmails() {
            if (!this.userToModify.notSendStatusEmails)
                this.userToModify.notSendStatusEmails = true
            else
                delete this.userToModify.notSendStatusEmails
        },
        addSecondaryPhone(){
            if(this.userToModify.secondaryPhones){
                this.userToModify.secondaryPhones.push('');
            }else{
                this.userToModify.secondaryPhones = [''];
            }
        },
        deleteSecondaryPhone(index){
            this.userToModify.secondaryPhones.splice(index,1);
        }
    },
    computed:{
        labelDescriptions() {
            return {
                'Usuario': {
                    title: 'Usuario',
                    desc: 'Acceso comercial básico: cuentas y contratos, documentos, productos y herramientas comerciales.'
                },

                'Usuario demo': {
                    title: 'Usuario demo',
                    desc: 'Acceso mínimo para demostraciones. Usuario temporal con caducidad.'
                },

                'Usuario subdominio': {
                    title: 'Usuario subdominio',
                    desc: 'Control total del subdominio: usuarios, contratos, documentos, liquidaciones y herramientas.'
                },

                'Tramitador': {
                    title: 'Tramitador',
                    desc: 'Perfil interno de tramitación con acceso completo a contratos y documentación.'
                },

                'Usuario Drive': {
                    title: 'Usuario Drive',
                    desc: 'Acceso completo a documentos (Drive) manteniendo permisos comerciales estándar.'
                },

                'Usuario drive': {
                    title: 'Usuario Drive',
                    desc: 'Acceso completo a documentos (Drive) manteniendo permisos comerciales estándar.'
                },

                'Comercial': {
                    title: 'Comercial',
                    desc: 'Perfil comercial con gestión de cuentas, contratos y red de usuarios.'
                },

                'Comercial Drive': {
                    title: 'Comercial Drive',
                    desc: 'Perfil comercial con Drive: comercial + gestión documental completa.'
                },

                'Administrador': {
                    title: 'Administrador',
                    desc: 'Perfil de administración con accesos masivos y herramientas avanzadas.'
                },

                'Jefe administrador': {
                    title: 'Jefe administrador',
                    desc: 'Administrador con Drive y control ampliado del sistema.'
                },

                'Desarrollador': {
                    title: 'Desarrollador',
                    desc: 'Acceso técnico completo: mantenimiento, logs y configuración.'
                },

                'Súper usuario': {
                    title: 'Súper usuario',
                    desc: 'Acceso total al sistema sin restricciones.'
                },

                'Cliente': {
                    title: 'Cliente',
                    desc: 'Acceso limitado orientado a consulta.'
                }
            }
        },
        filteredLabels() {
            if (!this.$storage?.LABELS || !this.userToModify) return [];

            const loggedLabel = this.basicData?.userLogged?.label;

            if (loggedLabel !== 'Usuario subdominio') {
                return [...new Set(['Usuario', this.originalLabel])];
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
        },
        selectedLabelInfo() {
            if (!this.selectedLabel) return null;

            const direct = this.labelDescriptions[this.selectedLabel];
            if (direct) return direct;

            const key = Object.keys(this.labelDescriptions).find(
                k => k.toLowerCase() === this.selectedLabel.toLowerCase()
            );

            return key ? this.labelDescriptions[key] : null;
        },
        image(){
            return this.urlImage ? this.urlImage : `/assets/profile_images/${this.userToModify.profileImage}`
        },
        filteredPermissions(){
            //Saco solo los permisos que tiene el usuario con sesión iniciada
            return this.$storage.PERMISSIONS.filter((permission) => {
                return this.basicData.userLogged.permissions.includes(permission.code)
            })
        },
        isReadOnly(){
            if (!this.basicData.userLogged)
                return true
            else (this.basicData.userLogged)
                return this.basicData.userLogged.permissions.includes('READONLY')
        },
        isDirectAgentSubdomain(){

            //Para usuarios que se pueda establecer a todos comisión
            if (this.basicData && this.basicData.userSubdomain && (this.basicData.userSubdomain._id === '6837043b40f975f6bb0a8432' || this.basicData.userSubdomain._id === '67e26f1dc20d526af10eda92'))
                return true;


            //Compruebo si es agente directo de Subdominio( debajo de visualizador ), si es Subdominio o si es admin
            if(this.user){

                let isDownSubdomain = false;

                if (this.user.label !== 'Usuario subdominio' && !this.user.responsibles.includes('65fd4c2f05efc4aa4a050dc2') && this.user._id !== '65d704c63d2a9cbfd79e549a' && this.user.label !== 'Tramitador'){
                    this.user.responsibles.forEach((responsible) => {

                        let nowUser = this.basicData.userListComplete.find(user => user._id === responsible)

                        if (nowUser.label === 'Usuario subdominio' || nowUser.label === 'Tramitador')
                            isDownSubdomain = true;
                    })
                }


                //PRODUCCIÓN ( si es usuario subdominio  o debajo de un usuario subdominio )
                return (this.user.label === 'Usuario subdominio' || this.user.label === 'Tramitador' || isDownSubdomain || this.user.responsibles.includes('65fd4c2f05efc4aa4a050dc2') /*Justo por debajo de Subdominio ( Visualizador )*/|| this.user._id === '65d704c63d2a9cbfd79e549a' /*Admin*/)

                //LOCAL
                //return (this.user.responsibles.includes('65fc2c029e5f5622e308cea5') /*Justo por debajo de Subdominio ( Visualizador )*/ || this.user._id === '65fc2c029e5f5622e308cea4' /*Subdominio*/ || this.user._id === '65fc2c029e5f5622e308cea3' /*Admin*/)

            }else
                return false
        },
        filteredOptions(){

            if (!this.basicData || !this.basicData.userSubdomain._id === '68edfbf7c84a190aeb0a6672') return []

            //Saco los que están por debajo del subdominio menos el que estoy mirando
            return this.basicData.userListComplete.filter(user => user.responsibles.includes(this.basicData.userSubdomain._id) && user._id !== this.userToModify._id).map(user => ({ title: user.firstName + ' ' + user.lastName, code: user._id }))
        },
        visibleMarketers(){
            const sortedMarketers = this.marketers.sort((a,b) => a.name.localeCompare(b.name));
            //Obtengo las comercializadoras que tiene el usuario
            if(this.basicData.userLogged && !(this.basicData.userLogged.label === 'Usuario subdominio' || this.basicData.userLogged.label === 'desarrollador')){
             sortedMarketers.filter(marketer => this.basicData.userLogged.marketers.includes(marketer._id))
            }

            return [...sortedMarketers, ...this.visibleZocoMarketers].sort((a,b) => a.name.localeCompare(b.name));
        },
        visibleZocoMarketers(){
            const sortedMarketers = this.zocoMarketers.map(marketer => ({ ...marketer, marketer, name: `${marketer.name} (ZOCO)`,isZoco: true })).sort((a,b) => a.name.localeCompare(b.name));

            //Obtengo las comercializadoras del subdominio
            if(this.basicData.userSubdomain?.marketers?.length > 0){
                return sortedMarketers.filter(marketer => this.basicData.userSubdomain.marketers.includes(marketer._id))
            }
            return [];
        },
        publicUserDocs(){
            if (!this.userToModify || !this.userToModify.docs) return []
            return this.userToModify.docs.filter(doc => doc.privacyType === 'public')
        }
    }
}
</script>

<style scoped>

</style>
