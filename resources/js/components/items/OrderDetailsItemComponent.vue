<template>
    <div>

        <div class="form-group" v-if="orderToModify && orderToModify.errors">

            <div class="floating-box round" data-round="30">

                <div class="register-pos round" data-round="30">

                    <!--si se esta viendo la info del pedido-->
                    <div class="top-part">

                        <!--Datos basicos-->
                        <div class="inputs-part basic-data px-100">

                            <!--Header-->
                            <div class="d-flex justify-between">

                                <p class="text" data-size="15" data-weight="700">Datos básicos</p>

                                <div v-if="!hideFidelityVisualNoise" class="d-flex align-center mr-10">

                                    <div class="custom-checkbox my-auto" v-on:click="toggleReminder">
                                        <div v-bind:class="{ selected: orderToModify.isReminderOn }"></div>
                                    </div>
                                    <p class="text my-auto ml-5" data-size="10">Recordatorio renovación</p>

                                    <div
                                        v-if="orderToModify.isReminderOn"
                                        :class="[
                                        { wrong: orderToModify.errors.renewalDate },
                                        !renewalReminderByMarketer ? 'ml-4 input-group' : 'ml-4'
                                    ]"
                                    >
                                        <!-- Caso 1: El usuario PUEDE configurar el recordatorio -->
                                        <select
                                            v-if="!renewalReminderByMarketer"
                                            v-model="orderToModify.renewalOption"
                                            :disabled="!isEditing || isInputsDisabled"
                                            class="text-sm truncate"
                                        >
                                            <option :value="undefined" disabled hidden>Selecciona recordatorio…</option>
                                            <option value="15d">15 días antes de que se cumpla un año desde la fecha de activación</option>
                                            <option value="1m">1 mes después de la fecha de activación</option>
                                            <option value="4m">4 meses después de la fecha de activación</option>
                                            <option value="6m">6 meses después de la fecha de activación</option>
                                            <option value="11m">11 meses después de la fecha de activación</option>
                                        </select>

                                        <!-- Caso 2: Lo gestiona la comercializadora -->
                                        <p
                                            v-else
                                            class="text mt-5"
                                            data-size="9"
                                        >
                                        <span v-if="!orderToModify.activationDate">
                                        Pendiente de fecha de activación.
                                        </span>
                                            <span v-else-if="orderToModify.renewalDate">
                                        Fecha estimada: {{ getPrettyDate(orderToModify.renewalDate) }}.
                                        </span>
                                            <span v-else>
                                        Fecha estimada pendiente.
                                        </span>
                                        </p>
                                    </div>

                                </div>

                                <div v-if="!hideFidelityVisualNoise" class="d-flex">

                                    <!--check-->
                                    <div class="custom-checkbox my-auto" v-on:click="toggleDraftOrder">
                                        <div v-bind:class="{ selected: isDraftOrder }"></div>
                                    </div>

                                    <p class="text my-auto ml-5" data-size="10">Borrador</p>
                                </div>
                            </div>

                            <div v-if="isEditing && account && !isInputsDisabled" @click="copyAccountData" class="text ml-10 pointer" data-size="12"><i class="far fa-copy" /> Copiar datos de la cuenta</div>

                            <!--Nombre-->
                            <div v-bind:class="{ wrong: orderToModify.errors.name }" class="form-group">
                                <p class="my-auto"><label>Nombre</label> <span data-color="rojo">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete orderToModify['errors']['name']" data-size="10"
                                           v-model="orderToModify.name" :disabled="isInputsDisabled || isContractNameDisabled" type="text">
                                </div>
                                <span v-if="orderToModify.errors.name" class="error">{{ orderToModify.errors.name }}</span>
                            </div>

                            <!--Dirección-->
                            <div v-bind:class="{ wrong: orderToModify.errors.direc }" class="form-group">
                                <p class="my-auto"><label>Dirección de suministro</label> <span data-color="rojo" v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.orderAddress">*</span>
                                </p>
                                <div class="input-group">
                                    <input v-on:focus="delete orderToModify['errors']['direc']" data-size="10"
                                           v-model="orderToModify.direc" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="orderToModify.errors.direc" class="error">{{ orderToModify.errors.direc }}</span>
                            </div>

                            <!--Población-->
                            <div v-bind:class="{ wrong: orderToModify.errors.town }" class="form-group">
                                <p class="my-auto"><label>Población</label> <span data-color="rojo" v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.orderTown">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete orderToModify['errors']['town']" data-size="10"
                                           v-model="orderToModify.town" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="orderToModify.errors.town" class="error">{{ orderToModify.errors.town }}</span>
                            </div>

                            <div class="d-grid" data-column="2">
                                <!--Provincia-->
                                <div v-bind:class="{ wrong: orderToModify.errors.province }" class="form-group">
                                    <p class="my-auto"><label>Provincia</label> <span data-color="rojo" v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.orderProvince">*</span></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete orderToModify['errors']['province']" data-size="10"
                                               v-model="orderToModify.province" :disabled="isInputsDisabled" type="text">
                                    </div>
                                    <span v-if="orderToModify.errors.province" class="error">{{
                                            orderToModify.errors.province }}</span>
                                </div>

                                <!--CP-->
                                <div v-bind:class="{ wrong: orderToModify.errors.zip }" class="form-group">
                                    <p class="my-auto"><label>Código postal</label> <span data-color="rojo" v-if="this.basicData && this.basicData.userSubdomain && this.basicData.userSubdomain.settings.orderPostal">*</span></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete orderToModify['errors']['zip']" data-size="10"
                                               v-model="orderToModify.zip" :disabled="isInputsDisabled" type="text">
                                    </div>
                                    <span v-if="orderToModify.errors.zip" class="error">{{ orderToModify.errors.zip
                                        }}</span>
                                </div>
                            </div>

                            <!--DNI/NIF (solo Fidelity)-->
                            <div v-if="basicData && basicData.userSubdomain && basicData.userSubdomain._id === '6909faa9232c09035a03f3b2'"
                                 v-bind:class="{ wrong: orderToModify.errors.accountCIF }" class="form-group">
                                <p class="my-auto"><label>DNI/NIF</label></p>
                                <div class="input-group">
                                    <input v-on:focus="delete orderToModify['errors']['accountCIF']" data-size="10"
                                           v-model.trim="orderToModify.accountCIF" :disabled="isInputsDisabled || areFidelityAccountFieldsLocked" type="text">
                                </div>
                                <span v-if="orderToModify.errors.accountCIF" class="error">{{ orderToModify.errors.accountCIF }}</span>
                            </div>


                            <!--IBAN-->
                            <div v-bind:class="{ wrong: orderToModify.errors.IBAN }" class="form-group">
                                <p class="my-auto"><label>IBAN</label> <span data-color="rojo" v-if="basicData && basicData.userSubdomain && basicData.userSubdomain.settings.orderIBAN">*</span>
                                </p>
                                <div class="input-group">
                                    <input v-on:focus="delete orderToModify['errors']['IBAN']" data-size="10"
                                           v-model="orderToModify.IBAN" @blur="checkIBAN" :disabled="isInputsDisabled"
                                           type="text">
                                </div>
                                <span v-if="orderToModify.errors.IBAN" class="error">{{ orderToModify.errors.IBAN }}</span>
                            </div>

                            <!--Tipo de cliente-->
                            <div v-if="!hideFidelityVisualNoise" class="form-group">
                                <p class="my-auto">
                                    <label>Tipo de cliente</label>
                                </p>

                                <div class="input-group customer-type-select-wrapper">
                                    <select
                                        v-model="orderToModify.customerType"
                                        :disabled="isInputsDisabled"
                                        data-size="10"
                                        class="customer-type-select"
                                    >
                                        <option value="">Sin indicar</option>
                                        <option value="residential">Residencial</option>
                                        <option value="pyme">PYME</option>
                                    </select>
                                </div>
                            </div>

                            <!--Fuente de la venta-->
                            <custom-select-component  v-if="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'"
                                                      @addElement="addElement" @delElement="delElement"
                                                      @selectElement="selectElement" class="mt1" type="orderSources" errorType="source"
                                                      title="Fuente de la venta" :options="sources" :addedOptions="selectValues.orderSources"
                                                      :selected="orderToModify.source" :isInputsDisabled="isInputsDisabled"
                                                      :errors="orderToModify.errors"></custom-select-component>

                            <!--CNAE (para Efutura solo)-->
                            <div v-if="this.basicData.userSubdomain._id === '68d260e6bc9e8c38f8003df2'" v-bind:class="{ wrong: orderToModify.errors.CNAE }" class="form-group">
                                <p class="my-auto"><label>Actividad de suministro o CNAE</label> <span data-color="rojo">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete orderToModify['errors']['CNAE']" data-size="10"
                                           v-model="orderToModify.CNAE" :disabled="isInputsDisabled" type="text">
                                </div>
                                <span v-if="orderToModify.errors.CNAE" class="error">{{ orderToModify.errors.CNAE }}</span>
                            </div>

                            <!--Teléfono (Para Efutura solo) (opcional)-->
                            <div v-if="this.basicData.userSubdomain._id === '68d260e6bc9e8c38f8003df2'" class="form-group">
                                <p class="my-auto"><label>Teléfono</label></p>
                                <div class="input-group">
                                    <input data-size="10"
                                           v-model="orderToModify.phone" :disabled="isInputsDisabled" type="text">
                                </div>
                            </div>

                            <!--Email (Para Efutura solo) (opcional)-->
                            <div v-if="this.basicData.userSubdomain._id === '68d260e6bc9e8c38f8003df2'" class="form-group">
                                <p class="my-auto"><label>Email</label></p>
                                <div class="input-group">
                                    <input data-size="10"
                                           v-model="orderToModify.email" :disabled="isInputsDisabled" type="text">
                                </div>
                            </div>

                            <!--Observaciones privadas (para Efutura solo)-->
                            <div v-if="this.basicData.userSubdomain._id === '68d260e6bc9e8c38f8003df2' && this.basicData.userLogged.permissions.includes('contracts.processor')" class="form-group">
                                <label>Observaciones privadas</label>
                                <div class="input-group h-100">
                                <textarea data-size="10" v-model="orderToModify.privateObservations" :disabled="isInputsDisabled"
                                          type="text"></textarea>
                                </div>
                            </div>

                            <!--Informe incidencia-->
                            <div class="form-group" v-if="statusSelected.code === 'i'">

                                <div class="d-flex justify-between">
                                    <label>Informe incidencia</label>

                                    <div v-if="canManage('contracts.processor')" class="principal mt-auto pointer" data-color="azul"
                                         data-size="10" v-on:click="resendIncidenceMail">Reenviar correo</div>
                                </div>

                                <div class="input-group h-100">
                                <textarea data-size="10" v-model="orderToModify.incidence" :disabled="isInputsDisabled"
                                          type="text"></textarea>
                                </div>
                            </div>

                            <!--Observaciones-->
                            <div v-if="!hideFidelityVisualNoise" class="form-group">
                                <label>Observaciones</label>
                                <div class="input-group h-100">
                                <textarea data-size="10" v-model="orderToModify.observations"
                                          :disabled="isInputsDisabled" type="text"></textarea>
                                </div>
                            </div>

                            <!--Si es de Witro-->
                            <div v-if="orderToModify.whatsPhone" class="form-group">
                                <label>Telefono creador WhatsApp</label>
                                <a :href="'https://wa.me/' + orderToModify.whatsPhone" target="_blank"
                                   style="text-decoration: none;">
                                    <div class="d-flex align-center mt-10 pointer">
                                        <i class="fa-brands fa-whatsapp" data-size="20" style="color: #25d366;"></i>
                                        <p class="ml-10" data-size="13">{{ orderToModify.whatsPhone }}</p>
                                    </div>
                                </a>
                            </div>

                            <!--Email y telefono de la cuenta-->
                            <div class="half-space" v-if="basicData && basicData.userSubdomain && basicData.userSubdomain._id === '6909faa9232c09035a03f3b2'">

                                <!--movil-->
                                <div class="form-group">
                                    <p class="my-auto"><label>Movil cuenta</label></p>
                                    <div class="input-group">
                                        <input data-size="12" v-model="account.phone" :disabled="true" type="text">
                                    </div>
                                </div>

                                <!--email-->
                                <div class="form-group">

                                    <p class="my-auto"><label>Email cuenta</label></p>

                                    <div class="d-flex" data-gap="5">
                                        <div class="input-group w-100">
                                            <input data-size="12" v-model="account.email" :disabled="true" type="email">
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!--Usuarios asignados al contrato-->
                            <div class="form-group mt-20 owners-section desktop-item">

                                <div class="text mb-10" data-size="18" data-weight="700">Propietarios del contrato</div>

                                <div>
                                    <user-list-component :basicData="basicData" v-model:userListSelected="orderToModify.usersIds" :userListToSelect="account?.usersIds" :editing="isEditing"></user-list-component>
                                    <p v-if="basicData.userList.length === 0" class="text opacity-3" data-size="10">No tienes usuarios para asignar</p>
                                </div>
                            </div>

                        </div>

                        <!--separador vertical-->
                        <div class="separator" data-position="vertical"></div>

                        <!--Producto y docs adjuntados-->
                        <div class="inputs-part px-100 d-flex column">

                            <!--Producto-->
                            <div class="product">

                                <!--ID contrato Fidelity-->
                                    <div
                                        v-if="isFidelitySubdomain && orderToModify.identifier"
                                        class="text opacity-6"
                                        data-size="0"
                                        data-weight="600"
                                    >
                                        ID contrato: {{ orderToModify.identifier }}
                                    </div>

                                <div class="d-flex justify-between align-center">
                                    <p class="text" data-size="15" data-weight="700">Producto</p>

                                    <!--Versiones-->
                                    <div v-if="versions.length > 1" class="d-flex align-center">
                                        <div class="input-group my-auto">
                                            <select
                                                :value="versionSelected"
                                                @change="selectVersion"
                                                data-size="10"
                                            >
                                                <option
                                                    v-for="(v, i) in versions"
                                                    :key="v._id"
                                                    :value="i"
                                                >
                                                    {{ i === 0 ? 'Versión actual' : 'Versión ' + (versions.length - i) }}
                                                    – {{ getOrderDate(v)?.format('DD/MM/YYYY') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <!--Fecha de traspaso-->
                                    <div class="text opacity-6" data-size="10" data-weight="500">Fec. de traspaso: {{ orderToModify.transferDate }}</div>
                                </div>

                                <!--Tramitar con otro-->
                                <div class="d-flex justify-between"
                                     v-if="!isDownZoco && canSeeZoco">

                                    <div class="d-flex my-5">

                                        <div class="custom-checkbox my-auto mr-10" v-if="!isInputsDisabled" v-on:click="toggleSelectAsignation">
                                            <div v-bind:class="{ selected: orderToModify.assignedTo }"></div>
                                        </div>

                                        <div class="my-auto mr-15 d-flex justify-between align-center">
                                            <p data-color="principal" data-size="10">Tramitar con </p>

                                            <!--Select usuarios con los que tramitar-->
                                            <div v-bind:class="{ wrong: orderToModify.errors.assignedTo }" class="form-group ml-15">
                                                <div class="input-group">
                                                    <select v-if="!isInputsDisabled && !orderToModify.assignedTo" v-model="assignedToSelected">
                                                        <option v-for="agent in agentsToAssign" :value="agent.code">{{ agent.title }}</option>
                                                    </select>

                                                    <div v-else class="text opacity-5" data-size="10">{{ assignedToSelected ? agentsToAssign.find(agent => agent.code === assignedToSelected).title : 'Por asignar' }}</div>
                                                </div>
                                                <span v-if="orderToModify.errors.assignedTo" class="error">{{ orderToModify.errors.assignedTo }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!--Fechas-->
                                <div class="d-grid" data-column="3">

                                    <!--Fecha de tramitación-->
                                    <div
                                        v-if="!hideFidelityVisualNoise"
                                        v-bind:class="{ wrong: orderToModify.errors.processingDate }"
                                        class="form-group"
                                    >
                                        <label>Fec. de tramitación</label>
                                        <div class="input-group">
                                            <input v-if="canManage('contracts.processor')"
                                                   v-on:focus="delete orderToModify['errors']['processingDate']" data-size="10"
                                                   v-model="orderToModify.processingDate" :disabled="isInputsDisabled || isSensitiveFieldsDisabled"
                                                   @blur="validateDateNotGreaterThanToday(orderToModify, 'processingDate')"
                                                   :max="getTodayDateString()"
                                                   type="date">
                                            <div v-else class="text opacity-5" data-size="10">{{
                                                    orderToModify.processingDate ?
                                                        getPrettyDate(orderToModify.processingDate) : 'Por asignar' }}</div>
                                        </div>
                                        <span v-if="orderToModify.errors.processingDate" class="error">{{
                                                orderToModify.errors.processingDate }}</span>
                                    </div>

                                    <!--Fecha de activación-->
                                    <div v-bind:class="{ wrong: orderToModify.errors.activationDate }" class="form-group"
                                         v-if="activationDateStatuses.includes(statusSelected.code)">
                                        <p class="my-auto"><label>Fec. de activación</label> <span
                                            data-color="rojo">*</span></p>

                                        <div class="input-group">
                                            <input v-if="canManage('contracts.processor')"
                                                   v-on:focus="delete orderToModify['errors']['activationDate']" data-size="10"
                                                   v-model="orderToModify.activationDate" :disabled="isInputsDisabled || isSensitiveFieldsDisabled"   @change="calculateRenewalDate"
                                                   @blur="validateDateNotGreaterThanToday(orderToModify, 'activationDate')"
                                                   :max="getTodayDateString()"
                                                   type="date">
                                            <div v-else class="text opacity-5" data-size="10">{{
                                                    orderToModify.activationDate ?
                                                        getPrettyDate(orderToModify.activationDate) : 'Por asignar' }}</div>
                                        </div>
                                        <span v-if="orderToModify.errors.activationDate" class="error">{{
                                                orderToModify.errors.activationDate }}</span>
                                    </div>

                                    <!--f. baja-->
                                    <div v-if="statusSelected.code === 'b' || statusSelected.code === 'pendiente_de_retrocomisin' || statusSelected.code === 'baja_anticipada_retrocomisionada'"
                                         v-bind:class="{ wrong: orderToModify.errors.activationDate }" class="form-group">
                                        <label>Fec. de baja</label>
                                        <div class="input-group">
                                            <input v-if="canManage('contracts.processor')"
                                                   v-on:focus="delete orderToModify['errors']['lowDate']" data-size="10"
                                                   v-model="orderToModify.lowDate"
                                                   @blur="validateDateNotGreaterThanToday(orderToModify, 'lowDate')"
                                                   :max="getTodayDateString()"
                                                   :disabled="isInputsDisabled || isSensitiveFieldsDisabled" type="date">
                                            <div v-else class="text opacity-5" data-size="10">{{ orderToModify.lowDate ?
                                                getPrettyDate(orderToModify.lowDate) : 'Por asignar' }}</div>
                                        </div>
                                        <span v-if="orderToModify.errors.lowDate" class="error">{{
                                                orderToModify.errors.lowDate }}</span>
                                    </div>
                                </div>


                                <!--Estado y estado liquidacion-->
                                <div class="d-grid" data-column="2">

                                    <!--Estado-->
                                    <div v-bind:class="{ wrong: orderToModify.errors.status }" class="form-group">
                                        <label>Estado</label>
                                        <div class="input-group">
                                            <select v-if="!isInputsDisabled && !isSensitiveFieldsDisabled && canManage('contracts.processor')" :value="statusSelected.code" v-on:change="selectStatus">
                                                <option v-for="status in filteredStatuses" :value="status.code">{{
                                                        status.title }}</option>
                                            </select>

                                            <div v-else class="text opacity-5" data-size="10">{{ statusSelected ?
                                                statusSelected.title : 'Por asignar'
                                                }}</div>
                                        </div>
                                        <span v-if="orderToModify.errors.status" class="error">{{
                                                orderToModify.errors.status }}</span>
                                    </div>

                                    <!--Estado liquidación-->
                                    <div v-if="canManage('contracts.processor') && basicData && basicData.userSubdomain._id !== '6909faa9232c09035a03f3b2'" v-bind:class="{ wrong: orderToModify.errors.status }"
                                         class="form-group">
                                        <label>Estado de liquidación</label>
                                        <div class="input-group">
                                            <select :disabled="isInputsDisabled || isSensitiveFieldsDisabled" v-model="orderToModify.liquidationStatus">
                                                <option v-for="status in liquidationStatuses" :value="status.code">{{
                                                        status.title }}</option>
                                            </select>
                                        </div>
                                        <span v-if="orderToModify.errors.liquidationStatus" class="error">{{
                                                orderToModify.errors.liquidationStatus
                                            }}</span>
                                    </div>

                                </div>


                                <!--Tipo de producto y comercializadora-->
                                <div class="d-grid" :data-column="orderToModify.productType === 'cd' ? 2 : 1">
                                    <div class="form-group" :class="{ wrong: orderToModify.errors.productType }">
                                        <p class="my-auto">
                                            <label>Tipo de producto</label>
                                            <span data-color="rojo">*</span>
                                        </p>
                                        <div class="input-group">
                                            <select v-model="orderToModify.productType" :disabled="isInputsDisabled" @change="changeProductType">
                                                <option value="">Selecciona uno</option>
                                                <option v-for="type in productTypesWithoutSp" :key="type.code" :value="type.code">
                                                    {{ type.title }}
                                                </option>
                                            </select>
                                        </div>
                                        <span v-if="orderToModify.errors.productType" class="error">
                                            {{ orderToModify.errors.productType }}
                                        </span>
                                    </div>

                                    <div v-if="orderToModify.productType === 'cd'" v-bind:class="{ wrong: orderToModify.errors.marketer }" class="form-group">
                                        <p class="my-auto"><label>Comercializadora</label> <span data-color="rojo">*</span>
                                        </p>
                                        <div class="input-group">
                                            <select v-model="orderToModify.marketer" :disabled="isInputsDisabled"
                                                    v-on:change="changeMarketer">
                                                <option value="">Selecciona una</option>
                                                <option v-for="marketer in filteredMarketers" :value="marketer.name">{{
                                                        marketer.name }}</option>
                                            </select>
                                        </div>
                                        <span v-if="orderToModify.errors.marketer" class="error">{{
                                                orderToModify.errors.marketer }}</span>
                                    </div>
                                </div>


                                <!--Comercializadora y tarifa-->
                                <div class="d-grid" :data-column="orderToModify.productType === 'cd' ? 1 : 2">

                                    <!--Comercializadora-->
                                    <div v-if="orderToModify.productType !== 'cd' && orderToModify.productType && !marketerProductsOthers[productTypesWithoutSp.find(pt => pt.code === orderToModify.productType)?.productToSee]" v-bind:class="{ wrong: orderToModify.errors.marketer }" class="form-group">
                                        <p class="my-auto"><label>Comercializadora</label> <span data-color="rojo">*</span></p>
                                        <div class="input-group">
                                            <select v-model="orderToModify.marketer" :disabled="isInputsDisabled" v-on:change="changeMarketer">
                                                <option value="">Selecciona una</option>
                                                <option v-for="marketer in filteredMarketers" :value="marketer.name">{{
                                                        marketer.name }}</option>
                                            </select>
                                        </div>
                                        <span v-if="orderToModify.errors.marketer" class="error">{{
                                                orderToModify.errors.marketer }}</span>
                                    </div>


                                    <!--Si es dual muestro label de parte de luz-->
                                    <div class="text my-10 d-flex align-center" data-size="18" v-if="orderToModify.productType === 'cd' && orderToModify.marketer !== ''">
                                        <i class="fa-regular fa-lightbulb mr-10"></i> Luz
                                    </div>


                                    <!--Tarifa-->
                                    <div v-if="((orderToModify.productType !== 'cd' && orderToModify.productType !== 'sa') && orderToModify.productType && !marketerProductsOthers[$storage.PRODUCT_TYPES.find(pt => pt.code === orderToModify.productType)?.productToSee]) && orderToModify.marketer !== ''"
                                         v-bind:class="{ wrong: orderToModify.errors.fee }" class="form-group">
                                        <p class="my-auto"><label>Tarifa</label> <span data-color="rojo">*</span></p>
                                        <div class="input-group">
                                            <select v-model="orderToModify.fee" :disabled="isInputsDisabled" v-on:change="changeFee">
                                                <option value="">Selecciona una</option>
                                                <option v-for="fee in filteredFees" :value="fee.name">{{ fee.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <span v-if="orderToModify.errors.fee" class="error">{{ orderToModify.errors.fee
                                            }}</span>
                                    </div>

                                </div>




                                <!--PRODUCTO-->

                                <!--si no es contrato de luz ni de gas-->
                                <div v-if="orderToModify.productType && !!marketerProductsOthers[$storage.PRODUCT_TYPES.find(pt => pt.code === orderToModify.productType)?.productToSee]"
                                     v-bind:class="{ wrong: orderToModify.errors.product }" class="form-group">
                                    <p class="my-auto"><label>Producto</label> <span data-color="rojo">*</span></p>
                                    <div class="input-group">
                                        <select v-model="orderToModify.product" :disabled="isInputsDisabled" v-on:change="delete orderToModify.errors.product">
                                            <option value="">Selecciona una</option>
                                            <option
                                                v-for="marketer in marketerProductsOthers[$storage.PRODUCT_TYPES.find(pt => pt.code === orderToModify.productType)?.productToSee]"
                                                :value="marketer">{{ marketer }}</option>
                                        </select>
                                    </div>
                                    <span v-if="orderToModify.errors.product" class="error">{{ orderToModify.errors.product }}</span>
                                </div>


                                <div v-else-if="orderToModify.marketer && !marketerProductsOthers[$storage.PRODUCT_TYPES.find(pt => pt.code === orderToModify.productType)?.productToSee]" class="d-grid" :data-column="orderToModify.productType === 'cd' ? 2 : 1" data-gap="20">

                                    <!--Tarifa-->
                                    <div v-if="(orderToModify.productType === 'cd') && orderToModify.marketer !== ''"
                                         v-bind:class="{ wrong: orderToModify.errors.fee }" class="form-group">
                                        <p class="my-auto"><label>Tarifa</label> <span data-color="rojo">*</span></p>
                                        <div class="input-group">
                                            <select v-model="orderToModify.fee" :disabled="isInputsDisabled" v-on:change="changeFee">
                                                <option value="">Selecciona una</option>
                                                <option v-for="fee in filteredFees" :value="fee.name">{{ fee.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <span v-if="orderToModify.errors.fee" class="error">{{ orderToModify.errors.fee
                                            }}</span>
                                    </div>


                                    <!--Producto-->
                                    <div v-if="orderToModify.fee || orderToModify.productType === 'sa'" v-bind:class="{ wrong: orderToModify.errors.product }" class="form-group">
                                        <p class="my-auto"><label>Producto</label> <span data-color="rojo">*</span></p>
                                        <div class="input-group">
                                            <select v-model="orderToModify.product" :disabled="isInputsDisabled" v-on:change="changeOrderProduct">
                                                <option value="">Selecciona una</option>
                                                <option v-for="product in filteredMarketerProducts" :value="product.name">{{ product.name }}</option>
                                            </select>
                                        </div>
                                        <span v-if="orderToModify.errors.product" class="error">{{ orderToModify.errors.product
                                            }}</span>
                                    </div>
                                </div>


                                <!--SOLO SI ES CONTRATO LUZ O GAS-->

                                <!--CUPS-->
                                <div v-bind:class="{ wrong: orderToModify.errors.CUPS }" class="form-group"
                                     v-if="!marketerProductsOthers[$storage.PRODUCT_TYPES.find(pt => pt.code === orderToModify.productType)?.productToSee] && ( (orderToModify.productType === 'cd' && orderToModify.marketer !== '')  || (!!orderToModify.productType && orderToModify.productType === 'cl' || orderToModify.productType === 'cg'))">
                                    <p class="my-auto"><label>CUPS</label> <span data-color="rojo">*</span></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete orderToModify['errors']['CUPS']" data-size="10" v-model.trim="orderToModify.CUPS" v-on:input="checkAPIConsumption"  @blur="checkCUPS('CUPS')" :disabled="isInputsDisabled" type="text">
                                    </div>
                                    <span v-if="orderToModify.errors.CUPS" class="error">{{ orderToModify.errors.CUPS }}</span>
                                </div>

                                <!--Datos de contacto del contrato (solo Fidelity)-->
                                <div
                                    v-if="basicData && basicData.userSubdomain && basicData.userSubdomain._id === '6909faa9232c09035a03f3b2'"
                                    class="d-grid"
                                    data-column="2"
                                    data-gap="20"
                                >

                                    <div v-bind:class="{ wrong: orderToModify.errors.contractEmail }" class="form-group">
                                        <p class="my-auto"><label>Correo para contratación</label></p>
                                        <div class="input-group">
                                            <input
                                                v-on:focus="delete orderToModify['errors']['contractEmail']"
                                                data-size="10"
                                                v-model.trim="orderToModify.contractEmail"
                                                :disabled="isInputsDisabled"
                                                type="email"
                                            >
                                        </div>
                                        <span v-if="orderToModify.errors.contractEmail" class="error">{{ orderToModify.errors.contractEmail }}</span>
                                    </div>

                                    <div v-bind:class="{ wrong: orderToModify.errors.verificationPhone }" class="form-group">
                                        <p class="my-auto"><label>Teléfono para verificación/SMS</label></p>
                                        <div class="input-group">
                                            <input
                                                v-on:focus="delete orderToModify['errors']['verificationPhone']"
                                                data-size="10"
                                                v-model.trim="orderToModify.verificationPhone"
                                                :disabled="isInputsDisabled"
                                                type="text"
                                            >
                                        </div>
                                        <span v-if="orderToModify.errors.verificationPhone" class="error">{{ orderToModify.errors.verificationPhone }}</span>
                                    </div>
                                </div>


                                <!--Consumo y potencias-->
                                <div class="d-grid" data-column="2" data-gap="20" v-if="!marketerProductsOthers[$storage.PRODUCT_TYPES.find(pt => pt.code === orderToModify.productType)?.productToSee] && ( (orderToModify.productType === 'cd' && orderToModify.marketer !== '')  || (!!orderToModify.productType && orderToModify.productType === 'cl' || orderToModify.productType === 'cg'))">
                                    <!--Consumo anual-->
                                    <div v-bind:class="{ wrong: orderToModify.errors.consumption }" class="form-group">
                                        <label>Consumo</label>
                                        <div class="input-group">
                                            <input v-on:focus="delete orderToModify['errors']['consumption']"
                                                   v-on:blur="calcCommission()" data-size="10"
                                                   v-model.number="orderToModify.consumption"
                                                   :disabled="isInputsDisabled || !canEditConsumption" type="number"
                                                   step="0.01" min="0">
                                        </div>
                                        <span v-if="orderToModify.errors.consumption" class="error">
                                        {{ orderToModify.errors.consumption }}
                                    </span>
                                    </div>

                                    <!--Potencia contratada-->
                                    <div v-bind:class="{ wrong: orderToModify.errors.hiredPotency }" class="form-group" v-if="orderToModify.productType !== 'cg'">
                                        <label>Potencia</label>
                                        <div class="input-group">
                                            <input v-on:focus="delete orderToModify['errors']['hiredPotency']"
                                                   v-on:blur="calcCommission()" data-size="10"
                                                   v-model.number="orderToModify.hiredPotency"
                                                   :disabled="isInputsDisabled || !canEditPower" type="number"
                                                   step="0.001" min="0">
                                        </div>
                                        <span v-if="orderToModify.errors.hiredPotency" class="error">
                                        {{ orderToModify.errors.hiredPotency }}
                                    </span>
                                    </div>
                                </div>


                                <!--Fees-->
                                <label v-if="!!orderToModify.potencyFees && parseStringToNumber(productSelected?.fees?.power?.maximum) > 0">Fee de potencia ({{productSelected?.fees?.power?.minimum}} - {{productSelected?.fees?.power?.maximum}} €/kW mes)</label>
                                <div class="d-grid" data-column="3" data-gap-h="20" v-if="!!orderToModify.potencyFees">

                                    <div v-if="canManage('contracts.fees') && parseStringToNumber(productSelected?.fees?.power?.maximum) > 0" :class="[{ wrong: orderToModify.errors['potencyFee' + potencyFeeInd] }, 'form-group']"
                                         v-for="(potencyFee, potencyFeeInd) in (productSelected?.fees?.power?.unique ? 1 : ['Tarifa 3.0TD', 'Tarifa 6.1TD'].includes(orderToModify.fee) ? 6 : 2)">
                                        <label v-if="!productSelected?.fees?.power?.unique" class="ml-5">{{`P${potencyFeeInd+ 1}`}}</label>
                                        <div class="input-group">
                                            <input v-model="orderToModify.potencyFees['potencyFee' + potencyFeeInd]" placeholder="€ /kW mes" @change="event => applyPotencyFeeChange(event, potencyFeeInd)"/>
                                        </div>
                                        <span v-if="orderToModify.errors['potencyFee' + potencyFeeInd]" class="error">
                                            {{ orderToModify.errors['potencyFee' + potencyFeeInd] }}
                                        </span>
                                    </div>
                                </div>

                                <label v-if="!!orderToModify.energyFees && parseStringToNumber(productSelected?.fees?.energy?.maximum) > 0">Fee de energía ({{productSelected?.fees?.energy?.minimum}} - {{productSelected?.fees?.energy?.maximum}} €/MWh)</label>
                                <div class="d-grid" data-column="3" data-gap-h="20" v-if="!!orderToModify.energyFees">
                                    <div v-if="canManage('contracts.fees') && parseStringToNumber(productSelected?.fees?.energy?.maximum) > 0" :class="[{ wrong: orderToModify.errors['energyFee' + energyFeeInd] }, 'form-group']"
                                         v-for="(energyFee, energyFeeInd) in (productSelected?.fees?.energy?.unique ? 1 : ['Tarifa 3.0TD', 'Tarifa 6.1TD'].includes(orderToModify.fee) ? 6 : 3)">
                                        <label v-if="!productSelected?.fees?.energy?.unique" class="ml-5">{{`P${energyFeeInd + 1}`}}</label>
                                        <div class="input-group">
                                            <input v-model="orderToModify.energyFees['energyFee' + energyFeeInd]" placeholder="€ /MWh" @change="event => applyEnergyFeeChange(event, energyFeeInd)"/>
                                        </div>
                                        <span v-if="orderToModify.errors['energyFee' + energyFeeInd]" class="error">
                                            {{ orderToModify.errors['energyFee' + energyFeeInd] }}
                                        </span>
                                    </div>
                                </div>

                                <label v-if="'fixedFee' in orderToModify && parseStringToNumber(productSelected?.fees?.fixed?.maximum) > 0">Fee término fijo ({{productSelected?.fees?.fixed?.minimum}} - {{productSelected?.fees?.fixed?.maximum}} €/MWh)</label>
                                <div class="d-grid" data-column="3" data-gap-h="20" v-if="'fixedFee' in orderToModify">
                                    <div v-if="canManage('contracts.fees') && parseStringToNumber(productSelected?.fees?.fixed?.maximum) > 0" :class="[{ wrong: orderToModify.errors['fixedFee'] }, 'form-group']">
                                        <div class="input-group">
                                            <input v-model="orderToModify.fixedFee" placeholder="€ /kW mes" @change="event => applyFixedFeeChange(event)"/>
                                        </div>
                                        <span v-if="orderToModify.errors['fixedFee']" class="error">
                                            {{ orderToModify.errors['fixedFee'] }}
                                        </span>
                                    </div>
                                </div>

                                <label v-if="'variableFee' in orderToModify && parseStringToNumber(productSelected?.fees?.variable?.maximum) > 0">Fee término variable ({{productSelected?.fees?.variable?.minimum}} - {{productSelected?.fees?.variable?.maximum}} €/MWh)</label>
                                <div class="d-grid" data-column="3" data-gap-h="20" v-if="'variableFee' in orderToModify">
                                    <div v-if="canManage('contracts.fees') && parseStringToNumber(productSelected?.fees?.variable?.maximum) > 0" :class="[{ wrong: orderToModify.errors['variableFee'] }, 'form-group']">
                                        <div class="input-group">
                                            <input v-model="orderToModify.variableFee" placeholder="€ /MWh" @change="event => applyVariableFeeChange(event)"/>
                                        </div>
                                        <span v-if="orderToModify.errors['variableFee']" class="error">
                                            {{ orderToModify.errors['variableFee'] }}
                                        </span>
                                    </div>
                                </div>
                            <div
                                v-if="orderToModify?.consumptionData"
                                class="d-flex align-center pointer position-relative"
                                @click="showConsumption = !showConsumption"
                            >

                                <p class="ml-10">Ver consumos y potencias</p>

                                <i
                                    class="fa-solid position-absolute"
                                    data-color="principal"
                                    :class="showConsumption ? 'fa-chevron-up' : 'fa-chevron-down'"
                                ></i>

                            </div>
                            <transition name="accordion">
                            <div v-if="showConsumption && orderToModify?.consumptionData">
                                <!--Distribución de consumo por periodos-->
                                <div v-if="orderToModify.consumptionData && orderToModify.consumptionData.consumption && orderToModify.consumptionData.consumption.length > 0">

                                    <div class="d-flex">
                                        <p class="opacity-5 text" data-size="10">Desglose de consumos</p>

                                        <div class="ml-20" v-if="!isInputsDisabled"><i class="fa-solid fa-trash pointer"
                                                                                       data-color="rojo" v-on:click="delete orderToModify.consumptionData"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-around">

                                        <!--Periodo 1-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.consumption[0] !== undefined">
                                            <label>Periodo 1</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.consumption[0]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                            </div>
                                        </div>

                                        <!--Periodo 2-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.consumption[1] !== undefined">
                                            <label>Periodo 2</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.consumption[1]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                            </div>
                                        </div>

                                        <!--Periodo 3-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.consumption[2] !== undefined">
                                            <label>Periodo 3</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.consumption[2]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                            </div>
                                        </div>

                                    </div>


                                    <div class="d-flex justify-around">

                                        <!--Periodo 4-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.consumption[3] !== undefined">
                                            <label>Periodo 4</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.consumption[3]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                            </div>
                                        </div>

                                        <!--Periodo 5-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.consumption[4] !== undefined">
                                            <label>Periodo 5</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.consumption[4]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                            </div>
                                        </div>

                                        <!--Periodo 6-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.consumption[5] !== undefined">
                                            <label>Periodo 6</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.consumption[5]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!--Distribución de potencia por periodos-->
                                <div v-if="orderToModify.consumptionData && orderToModify.consumptionData.hiredPotency && orderToModify.consumptionData.hiredPotency.length > 0">

                                    <div class="d-flex">
                                        <p class="opacity-5 text" data-size="10">Desglose de potencias</p>

                                        <div class="ml-20" v-if="!isInputsDisabled"><i class="fa-solid fa-trash pointer"
                                                                                       data-color="rojo" v-on:click="delete orderToModify.consumptionData"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-around">

                                        <!--Periodo 1-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.hiredPotency[0] !== undefined">
                                            <label>Periodo 1</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.hiredPotency[0]" :disabled="isInputsDisabled || !canEditPower" data-size="10" type="number" step="0.001" min="0">
                                            </div>
                                        </div>

                                        <!--Periodo 2-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.hiredPotency[1] !== undefined">
                                            <label>Periodo 2</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.hiredPotency[1]" :disabled="isInputsDisabled || !canEditPower" data-size="10" type="number" step="0.001" min="0">
                                            </div>
                                        </div>

                                        <!--Periodo 3-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.hiredPotency[2] !== undefined">
                                            <label>Periodo 3</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.hiredPotency[2]" :disabled="isInputsDisabled || !canEditPower" data-size="10" type="number" step="0.001" min="0">
                                            </div>
                                        </div>

                                    </div>


                                    <div class="d-flex justify-around">

                                        <!--Periodo 4-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.hiredPotency[3] !== undefined">
                                            <label>Periodo 4</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.hiredPotency[3]" :disabled="isInputsDisabled || !canEditPower" data-size="10" type="number" step="0.001" min="0">
                                            </div>
                                        </div>

                                        <!--Periodo 5-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.hiredPotency[4] !== undefined">
                                            <label>Periodo 5</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.hiredPotency[4]" :disabled="isInputsDisabled || !canEditPower" data-size="10" type="number" step="0.001" min="0">
                                            </div>
                                        </div>

                                        <!--Periodo 6-->
                                        <div class="form-group"
                                             v-if="orderToModify.consumptionData.hiredPotency[5] !== undefined">
                                            <label>Periodo 6</label>
                                            <div class="input-group">
                                                <input v-model.number="orderToModify.consumptionData.hiredPotency[5]" :disabled="isInputsDisabled || !canEditPower" data-size="10" type="number" step="0.001" min="0">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                </div>
                                </transition>


                            <div
                                v-if="basicData?.userSubdomain?.settings?.showProductPricesByPeriods && orderToModify?.pricesProduct?.prices"
                                class="d-flex align-center pointer position-relative mt-20"
                                @click="showPrices = !showPrices"
                            >

                                <p class="ml-10">Ver precios por periodos</p>

                                <i
                                    class="fa-solid position-absolute"
                                    data-color="principal"
                                    :class="showPrices ? 'fa-chevron-up' : 'fa-chevron-down'"
                                ></i>

                            </div>

                            <transition name="accordion">
                            <div v-if="showPrices && basicData?.userSubdomain?.settings?.showProductPricesByPeriods && orderToModify?.pricesProduct?.prices">

                                <!-- POTENCIA -->
                                <div v-if="orderToModify?.pricesProduct?.prices?.power">

                                    <div class="d-flex">
                                        <p class="opacity-5 text" data-size="10">Desglose de precios potencia</p>
                                    </div>

                                    <div class="d-flex justify-around">

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.power?.P1 !== undefined">
                                            <label>Periodo 1</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.power?.P1 }}</p>
                                            </div>
                                        </div>

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.power?.P2 !== undefined">
                                            <label>Periodo 2</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.power?.P2 }}</p>
                                            </div>
                                        </div>

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.power?.P3 !== undefined">
                                            <label>Periodo 3</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.power?.P3 }}</p>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="d-flex justify-around">

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.power?.P4 !== undefined">
                                            <label>Periodo 4</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.power?.P4 }}</p>
                                            </div>
                                        </div>

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.power?.P5 !== undefined">
                                            <label>Periodo 5</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.power?.P5 }}</p>
                                            </div>
                                        </div>

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.power?.P6 !== undefined">
                                            <label>Periodo 6</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.power?.P6 }}</p>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- CONSUMO -->
                                <div v-if="orderToModify?.pricesProduct?.prices?.consume">

                                    <div class="d-flex">
                                        <p class="opacity-5 text" data-size="10">Desglose de precios consumo</p>
                                    </div>

                                    <div class="d-flex justify-around">

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.consume?.P1 !== undefined">
                                            <label>Periodo 1</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.consume?.P1 }}</p>
                                            </div>
                                        </div>

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.consume?.P2 !== undefined">
                                            <label>Periodo 2</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.consume?.P2 }}</p>
                                            </div>
                                        </div>

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.consume?.P3 !== undefined">
                                            <label>Periodo 3</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.consume?.P3 }}</p>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="d-flex justify-around">

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.consume?.P4 !== undefined">
                                            <label>Periodo 4</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.consume?.P4 }}</p>
                                            </div>
                                        </div>

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.consume?.P5 !== undefined">
                                            <label>Periodo 5</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.consume?.P5 }}</p>
                                            </div>
                                        </div>

                                        <div class="form-group" v-if="orderToModify?.pricesProduct?.prices?.consume?.P6 !== undefined">
                                            <label>Periodo 6</label>
                                            <div class="input-group">
                                                <p>{{ orderToModify?.pricesProduct?.prices?.consume?.P6 }}</p>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                            </transition>


                                <!--Parte comisiones luz si es dual ( sin carterizadas )-->
                                <div v-if="orderToModify.productType === 'cd' && orderToModify.marketer">

                                    <!--Comisiones-->
                                    <div class="d-grid" data-column="2">

                                        <!--Comisión de venta-->
                                        <div v-if="userHierarchy.length > 0 && agentCommissionElectricity && userHierarchy.find(u => u._id === agentCommission.userId)"
                                             v-bind:class="{ wrong: orderToModify.errors.commissions?.electricity?.breakdown?.[agentCommissionElectricity.id] }" class="form-group">
                                            <label class="line-clamp-1 hidden">Comisión de {{getFullName(userHierarchy.find(u => u._id === agentCommissionElectricity.userId))}}</label>
                                            <div class="input-group">
                                                <p v-if="!canManage('contracts.manageCommissions')">{{agentCommissionElectricity.commission}}</p>
                                                <input v-else @focus="delete orderToModify.errors.commissions?.electricity?.breakdown?.[agentCommissionElectricity.id]"
                                                       data-size="10" v-model.trim="agentCommissionElectricity.commission"
                                                       :disabled="isInputsDisabled" type="text">
                                            </div>
                                            <span v-if="orderToModify.errors.commissions?.electricity?.breakdown?.[agentCommissionElectricity.id]" class="error">
                                                {{ orderToModify.errors.commissions?.electricity?.breakdown?.[agentCommissionElectricity.id] }}
                                            </span>
                                        </div>

                                        <!--Comisión subdominio privada-->
                                        <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!orderToModify.assignedTo || (!!orderToModify.assignedTo && (basicData.userLogged && basicData.userLogged._id === orderToModify.assignedTo)))"
                                             :class="{ wrong: orderToModify.errors.commissions?.electricity?.subdomain }" class="form-group">
                                            <label>Comisión {{ basicData.enterprise.name }}</label>
                                            <div class="input-group">
                                                <input v-on:focus="delete orderToModify['errors']['commissions']?.['electricity']?.['subdomain']"
                                                       @blur="calcCommission(orderToModify.commissions.electricity.subdomain, 'electricity')"
                                                       data-size="10" v-model.trim="orderToModify.commissions.electricity.subdomain"
                                                       :disabled="isInputsDisabled || !canManage('contracts.manageCommissions')" type="text">
                                            </div>
                                            <span v-if="orderToModify.errors.commissions?.electricity?.subdomain" class="error">{{ orderToModify.errors.commissions?.electricity?.subdomain }}</span>
                                        </div>

                                    </div>

                                    <!--Desglose de jerarquía de comisiones-->
                                    <div
                                        v-if="filteredCommissionHierarchyElectricity.length > 0"
                                        class="d-flex align-center pointer position-relative"
                                        @click="showCommissionHierarchyElectricity = !showCommissionHierarchyElectricity"
                                    >
                                        <p class="ml-10">Ver comisiones</p>

                                        <i
                                            class="fa-solid position-absolute ml-5"
                                            data-color="principal"
                                            :class="showCommissionHierarchyElectricity ? 'fa-chevron-up' : 'fa-chevron-down'"
                                        />
                                    </div>

                                    <transition name="accordion">
                                        <div v-if="showCommissionHierarchyElectricity && filteredCommissionHierarchyElectricity.length > 0">
                                            <div class="d-flex column">
                                                <div class="form-group my-5" v-for="item in filteredCommissionHierarchyElectricity" :key="item.userId" :class="{ wrong: orderToModify.errors.commissions?.electricity?.breakdown?.[item.userId] }">
                                                    <label class="line-clamp-1 hidden w-300-px-max">{{ getFullName(userHierarchy.find(u => u._id === item.userId)) }}</label>
                                                    <div class="input-group w-100-px">
                                                        <p v-if="!canManage('contracts.manageCommissions')">{{item.commission}}</p>
                                                        <input v-else @focus="delete orderToModify.errors.commissions?.electricity?.breakdown?.[item.userId]"
                                                               data-size="10" v-model.trim="item.commission"
                                                               :disabled="isInputsDisabled" type="text">
                                                    </div>
                                                    <span v-if="orderToModify.errors.commissions?.electricity?.breakdown?.[item.userId]" class="error">
                                                        {{ orderToModify.errors.commissions?.electricity?.breakdown?.[item.userId] }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>

                                    <!--Decomisiones-->
                                    <template v-if="statusRequiresDecommissions(statusSelected.code)">
                                        <div class="d-grid" data-column="2">

                                            <!--Decomisión de venta-->
                                            <div v-if="userHierarchy.length > 0 && agentDecommissionElectricity && userHierarchy.find(u => u._id === agentCommission.userId)"
                                                 :class="{ wrong: orderToModify.errors.decommissions?.electricity?.breakdown?.[agentDecommissionElectricity.id] }" class="form-group">
                                                <label class="line-clamp-1 hidden">Decomisión de {{getFullName(userHierarchy.find(u => u._id === agentDecommissionElectricity.userId))}}</label>
                                                <div class="input-group">
                                                    <p v-if="!canManage('contracts.manageCommissions')">{{agentDecommissionElectricity.commission}}</p>
                                                    <input v-else @focus="delete orderToModify.errors.decommissions?.electricity?.breakdown?.[agentDecommissionElectricity.id]"
                                                           data-size="10" v-model="agentDecommissionElectricity.commission"
                                                           :disabled="isInputsDisabled" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.decommissions?.electricity?.breakdown?.[agentDecommissionElectricity.id]" class="error">
                                                    {{orderToModify.errors.decommissions?.electricity?.breakdown?.[agentDecommissionElectricity.id]}}
                                                </span>
                                            </div>

                                            <!--Decomisión subdominio privada-->
                                            <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!this.orderToModify.assignedTo || (!!this.orderToModify.assignedTo && (this.basicData.userLogged && this.basicData.userLogged._id === this.orderToModify.assignedTo)))"
                                                 :class="{ wrong: orderToModify.errors.decommissions?.electricity?.subdomain }"
                                                 class="form-group">
                                                <label>Decomisión {{ basicData.enterprise.name }}</label>
                                                <div class="input-group">
                                                    <input v-on:focus="delete orderToModify.errors.decommissions?.electricity?.subdomain"
                                                           data-size="10" v-model="orderToModify.decommissions.electricity.subdomain"
                                                           @blur="calcCommission(orderToModify.decommissions.electricity.subdomain,'electricity','decommissions')"
                                                           :disabled="isInputsDisabled || !canManage('contracts.manageCommissions')" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.decommissions?.electricity?.subdomain" class="error">
                                                    {{orderToModify.errors.decommissions?.electricity?.subdomain}}
                                                </span>
                                            </div>

                                        </div>

                                        <!--Desglose de jerarquía de decomisiones-->
                                        <div
                                            v-if="filteredDecommissionHierarchyElectricity.length > 0"
                                            class="d-flex align-center pointer position-relative"
                                            @click="showDecommissionHierarchyElectricity = !showDecommissionHierarchyElectricity"
                                        >
                                            <p class="ml-10">Ver decomisiones</p>

                                            <i
                                                class="fa-solid position-absolute ml-5"
                                                data-color="principal"
                                                :class="showDecommissionHierarchyElectricity ? 'fa-chevron-up' : 'fa-chevron-down'"
                                            />
                                        </div>

                                        <transition name="accordion">
                                            <div v-if="showDecommissionHierarchyElectricity && filteredDecommissionHierarchyElectricity.length > 0">
                                                <div class="d-flex column">
                                                    <div class="form-group my-5" v-for="item in filteredDecommissionHierarchyElectricity" :key="item.userId" :class="{ wrong: orderToModify.errors.decommissions?.electricity?.breakdown?.[item.userId] }">
                                                        <label class="line-clamp-1 hidden w-300-px-max">{{ getFullName(userHierarchy.find(u => u._id === item.userId)) }}</label>
                                                        <div class="input-group w-100-px">
                                                            <p v-if="!canManage('contracts.manageCommissions')">{{item.commission}}</p>
                                                            <input v-else @focus="delete orderToModify.errors.decommissions?.electricity?.breakdown?.[item.userId]"
                                                                   data-size="10" v-model.trim="item.commission"
                                                                   :disabled="isInputsDisabled" type="text">
                                                        </div>
                                                        <span v-if="orderToModify.errors.decommissions?.electricity?.breakdown?.[item.userId]" class="error">
                                                            {{ orderToModify.errors.decommissions?.electricity?.breakdown?.[item.userId] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>
                                    </template>
                                </div>

                                <!--PARTE GAS SI ES DUAL-->
                                <div v-if="orderToModify.productType === 'cd' && orderToModify.product !== ''">

                                    <!--Si es dual muestro label de parte de luz-->
                                    <div class="text my-10 d-flex align-center" data-size="18" v-if="orderToModify.productType === 'cd'">
                                        <i class="fa-regular fa-fire-flame-simple mr-10"></i> Gas
                                    </div>


                                    <!--Tarifa y producto de gas-->
                                    <div class="d-grid" data-column="2" data-gap="20">


                                        <!--Tarifa-->
                                        <div v-bind:class="{ wrong: orderToModify.errors.feeSecondary }" class="form-group">
                                            <p class="my-auto"><label>Tarifa</label> <span data-color="rojo">*</span></p>
                                            <div class="input-group">
                                                <select v-model="orderToModify.feeSecondary" :disabled="isInputsDisabled"
                                                        v-on:change="changeFeeSecondary">
                                                    <option value="">Selecciona una</option>
                                                    <option v-for="fee in filteredFeesSecondary" :value="fee.name">{{ fee.name }}
                                                    </option>
                                                </select>
                                            </div>
                                            <span v-if="orderToModify.errors.feeSecondary" class="error">{{ orderToModify.errors.feeSecondary }}</span>
                                        </div>


                                        <!--Producto-->
                                        <div v-if="orderToModify.feeSecondary" v-bind:class="{ wrong: orderToModify.errors.productSecondary }" class="form-group">
                                            <p class="my-auto"><label>Producto</label> <span data-color="rojo">*</span></p>
                                            <div class="input-group">
                                                <select v-model="orderToModify.productSecondary" :disabled="isInputsDisabled"
                                                        v-on:change="changeOrderProductSecondary">
                                                    <option value="">Selecciona una</option>
                                                    <option v-for="product in filteredMarketerProductsSecondary" :value="product.name">{{ product.name }}</option>
                                                </select>
                                            </div>
                                            <span v-if="orderToModify.errors.productSecondary" class="error">{{ orderToModify.errors.productSecondary }}</span>
                                        </div>
                                    </div>


                                    <!--CUPS-->
                                    <div v-bind:class="{ wrong: orderToModify.errors.CUPSSecondary }" class="form-group">
                                        <p class="my-auto"><label>CUPS</label> <span data-color="rojo">*</span></p>
                                        <div class="input-group">
                                            <input v-on:focus="delete orderToModify['errors']['CUPSSecondary']" data-size="10"
                                                   v-model.trim="orderToModify.CUPSSecondary" v-on:input="checkAPIConsumptionSecondary" @blur="checkCUPS('CUPSSecondary')"
                                                   :disabled="isInputsDisabled" type="text">
                                        </div>
                                        <span v-if="orderToModify.errors.CUPSSecondary" class="error">{{ orderToModify.errors.CUPSSecondary }}</span>
                                    </div>


                                    <!--Consumo y potencias-->
                                    <div class="d-grid" data-column="2" data-gap="20">
                                        <!--Consumo anual-->
                                        <div v-bind:class="{ wrong: orderToModify.errors.consumptionSecondary }" class="form-group">
                                            <label>Consumo</label>
                                            <div class="input-group">
                                                <input v-on:focus="delete orderToModify['errors']['consumptionSecondary']"
                                                       v-on:blur="calcCommission()" data-size="10"
                                                       v-model.number="orderToModify.consumptionSecondary"
                                                       :disabled="isInputsDisabled || !canEditConsumption" type="number"
                                                       step="0.01" min="0">
                                            </div>
                                            <span v-if="orderToModify.errors.consumptionSecondary" class="error">
                                        {{ orderToModify.errors.consumptionSecondary }}
                                    </span>
                                        </div>
                                    </div>


                                    <!--Fees (TODO)-->
                                    <!--<div class="d-grid" data-column="3" data-gap="20" v-if="!!orderToModify.energyFees && !!orderToModify.potencyFees">

                                        <div v-if="((orderToModify.marketer === 'IberEléctrica' || orderToModify.marketer === 'Unieléctrica' || orderToModify.marketer === 'VM') && orderToModify.product !== '') && ((this.basicData.userSubdomain._id === '68d260e6bc9e8c38f8003df2' && this.basicData.userLogged.permissions.includes('GESCON')) || (this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'))"
                                             v-bind:class="{ wrong: orderToModify.errors['energyFee' + energyFeeInd] }"
                                             v-for="(energyFee, energyFeeInd) in (orderToModify.marketer === 'VM') ? ((orderToModify.fee === 'Tarifa 3.0TD' || orderToModify.fee === 'Tarifa 6.1TD') ? 6 : 3) : 1"
                                             class="form-group">
                                            <label>Fee de energía P{{ energyFeeInd + 1 }} <span
                                                data-size="8">(€/MWh)</span></label>
                                            <div class="input-group">
                                                <select v-model="orderToModify.energyFees['energyFee' + energyFeeInd]"
                                                        v-on:change="event => selectEnergyFee(event, energyFeeInd)">
                                                    <option value="">Selecciona uno</option>
                                                    <option v-for="fee in energyFeeOptions" :value="fee">{{ fee }}</option>
                                                </select>
                                            </div>
                                            <span v-if="orderToModify.errors['energyFee' + energyFeeInd]" class="error">{{
                                                    orderToModify.errors['energyFee'
                                                    + energyFeeInd] }}</span>
                                        </div>


                                        <div v-if="((orderToModify.marketer === 'IberEléctrica' && orderToModify.product !== '') || (orderToModify.marketer === 'Unieléctrica' && orderToModify.product === 'Flexipyme') || (orderToModify.marketer === 'VM' && orderToModify.product !== '')) && ((this.basicData.userSubdomain._id === '68d260e6bc9e8c38f8003df2' && this.basicData.userLogged.permissions.includes('GESCON')) || (this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2'))"
                                             v-bind:class="{ wrong: orderToModify.errors['potencyFee' + potencyFeeInd] }"
                                             v-for="(potencyFee, potencyFeeInd) in (orderToModify.marketer === 'VM') ? ((orderToModify.fee === 'Tarifa 3.0TD' || orderToModify.fee === 'Tarifa 6.1TD') ? 6 : 2) : 1"
                                             class="form-group">
                                            <label>Fee de potencia P{{ potencyFeeInd + 1 }} <span
                                                data-size="8">(€/MWh)</span></label>
                                            <div class="input-group">
                                                <select v-model="orderToModify.potencyFees['potencyFee' + potencyFeeInd]"
                                                        v-on:change="event => selectPotencyFee(event, potencyFeeInd)">
                                                    <option value="">Selecciona uno</option>
                                                    <option v-for="fee in potencyFeeOptions" :value="fee">{{ fee }}</option>
                                                </select>
                                            </div>
                                            <span v-if="orderToModify.errors['potencyFee' + potencyFeeInd]" class="error">{{
                                                    orderToModify.errors['potencyFee' + potencyFeeInd] }}</span>
                                        </div>
                                    </div>-->

                                    <!--<div v-if="(orderToModify.marketer === 'IberEléctrica' || orderToModify.marketer === 'Unieléctrica' || orderToModify.marketer === 'VM') && orderToModify.product !== ''" v-bind:class="{ wrong: orderToModify.errors.marketerPercentage}" class="form-group" >
                                                <label>% comercializadora</label>
                                                <div class="input-group">
                                                    <input v-on:focus="delete orderToModify['errors']['marketerPercentage']" data-size="10" v-model="orderToModify.marketerPercentage" v-on:input="calculateConsumptionAndPotency" :disabled="isInputsDisabled || !canManage('GESCON')" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.marketerPercentage" class="error">{{ orderToModify.errors.marketerPercentage }}</span>
                                            </div>-->

                                    <!--Distribución de consumo por periodos-->
                                    <div
                                        v-if="orderToModify.consumptionDataSecondary && orderToModify.consumptionDataSecondary.consumption && orderToModify.consumptionDataSecondary.consumption.length > 0">

                                        <div class="d-flex">
                                            <p class="opacity-5 text" data-size="10">Desglose de consumos</p>

                                            <div class="ml-20" v-if="!isInputsDisabled"><i class="fa-solid fa-trash pointer"
                                                                                           data-color="rojo" v-on:click="delete orderToModify.consumptionData"></i>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-around">

                                            <!--Periodo 1-->
                                            <div class="form-group"
                                                 v-if="orderToModify.consumptionDataSecondary.consumption[0] !== undefined">
                                                <label>Periodo 1</label>
                                                <div class="input-group">
                                                    <input v-model.number="orderToModify.consumptionDataSecondary.consumption[0]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                                </div>
                                            </div>

                                            <!--Periodo 2-->
                                            <div class="form-group"
                                                 v-if="orderToModify.consumptionDataSecondary.consumption[1] !== undefined">
                                                <label>Periodo 2</label>
                                                <div class="input-group">
                                                    <input v-model.number="orderToModify.consumptionDataSecondary.consumption[1]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                                </div>
                                            </div>

                                            <!--Periodo 3-->
                                            <div class="form-group"
                                                 v-if="orderToModify.consumptionDataSecondary.consumption[2] !== undefined">
                                                <label>Periodo 3</label>
                                                <div class="input-group">
                                                    <input v-model.number="orderToModify.consumptionDataSecondary.consumption[2]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                                </div>
                                            </div>

                                        </div>


                                        <div class="d-flex justify-around">

                                            <!--Periodo 4-->
                                            <div class="form-group"
                                                 v-if="orderToModify.consumptionDataSecondary.consumption[3] !== undefined">
                                                <label>Periodo 4</label>
                                                <div class="input-group">
                                                    <input v-model.number="orderToModify.consumptionDataSecondary.consumption[3]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                                </div>
                                            </div>

                                            <!--Periodo 5-->
                                            <div class="form-group"
                                                 v-if="orderToModify.consumptionDataSecondary.consumption[4] !== undefined">
                                                <label>Periodo 5</label>
                                                <div class="input-group">
                                                    <input v-model.number="orderToModify.consumptionDataSecondary.consumption[4]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                                </div>
                                            </div>

                                            <!--Periodo 6-->
                                            <div class="form-group"
                                                 v-if="orderToModify.consumptionDataSecondary.consumption[5] !== undefined">
                                                <label>Periodo 6</label>
                                                <div class="input-group">
                                                    <input v-model.number="orderToModify.consumptionDataSecondary.consumption[5]" :disabled="isInputsDisabled || !canEditConsumption" data-size="10" type="number" step="0.01" min="0">
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <!--Parte comisiones gas si es dual ( sin carterizadas )-->
                                    <div v-if="orderToModify.productType === 'cd' && orderToModify.marketer">

                                        <!--Comisiones-->
                                        <div class="d-grid" data-column="2">

                                            <!--Comisión de venta-->
                                            <div v-if="userHierarchy.length > 0 && agentCommissionGas && userHierarchy.find(u => u._id === agentCommission.userId)"
                                                 v-bind:class="{ wrong: orderToModify.errors.commissions?.gas?.breakdown?.[agentCommissionGas.id] }" class="form-group">
                                                <label class="line-clamp-1 hidden">Comisión de {{getFullName(userHierarchy.find(u => u._id === agentCommissionGas.userId))}}</label>
                                                <div class="input-group">
                                                    <p v-if="!canManage('contracts.manageCommissions')">{{agentCommissionGas.commission}}</p>
                                                    <input v-else @focus="delete orderToModify.errors.commissions?.gas?.breakdown?.[agentCommissionGas.id]"
                                                           data-size="10" v-model.trim="agentCommissionGas.commission"
                                                           :disabled="isInputsDisabled" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.commissions?.gas?.breakdown?.[agentCommissionGas.id]" class="error">
                                                    {{ orderToModify.errors.commissions?.gas?.breakdown?.[agentCommissionGas.id] }}
                                                </span>
                                            </div>

                                            <!--Comisión subdominio privada-->
                                            <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!orderToModify.assignedTo || (!!orderToModify.assignedTo && (basicData.userLogged && basicData.userLogged._id === orderToModify.assignedTo)))"
                                                 v-bind:class="{ wrong: orderToModify.errors.commissions?.gas?.subdomain }" class="form-group">
                                                <label>Comisión {{ basicData.enterprise.name }}</label>
                                                <div class="input-group">
                                                    <input v-on:focus="delete orderToModify['errors']['commissions.gas.subdomain']"
                                                           @blur="calcCommission(orderToModify.commissions.gas.subdomain, 'gas')"
                                                           data-size="10" v-model.trim="orderToModify.commissions.gas.subdomain"
                                                           :disabled="isInputsDisabled || !canManage('contracts.manageCommissions')" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.commissions?.gas?.subdomain" class="error">{{ orderToModify.errors.commissions?.gas?.subdomain }}</span>
                                            </div>

                                        </div>

                                        <!--Desglose de jerarquía de comisiones-->
                                        <div
                                            v-if="filteredCommissionHierarchyGas.length"
                                            class="d-flex align-center pointer position-relative"
                                            @click="showCommissionHierarchyGas = !showCommissionHierarchyGas"
                                        >
                                            <p class="ml-10">Ver comisiones</p>

                                            <i
                                                class="fa-solid position-absolute ml-5"
                                                data-color="principal"
                                                :class="showCommissionHierarchyGas ? 'fa-chevron-up' : 'fa-chevron-down'"
                                            />
                                        </div>

                                        <transition name="accordion">
                                            <div v-if="showCommissionHierarchyGas && filteredCommissionHierarchyGas.length">
                                                <div class="d-flex column mx-10">
                                                    <div class="form-group my-5" v-for="item in filteredCommissionHierarchyGas" :key="item.userId" :class="{ wrong: orderToModify.errors.commissions?.gas?.breakdown?.[item.userId] }">
                                                        <label class="line-clamp-1 hidden w-300-px-max">{{ getFullName(userHierarchy.find(u => u._id === item.userId)) }}</label>
                                                        <div class="input-group w-100-px">
                                                            <p v-if="!canManage('contracts.manageCommissions')">{{item.commission}}</p>
                                                            <input v-else @focus="delete orderToModify.errors.commissions?.gas?.breakdown?.[item.userId]"
                                                                   data-size="10" v-model.trim="item.commission"
                                                                   :disabled="isInputsDisabled" type="text">
                                                        </div>
                                                        <span v-if="orderToModify.errors.commissions?.gas?.breakdown?.[item.userId]" class="error">
                                                            {{ orderToModify.errors.commissions?.gas?.breakdown?.[item.userId] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>

                                        <!--Decomisiones-->
                                        <template v-if="statusRequiresDecommissions(statusSelected.code)">
                                            <div class="d-grid" data-column="2">

                                                <!--Decomisión de venta-->
                                                <div v-if="userHierarchy.length > 0 && agentDecommissionGas && userHierarchy.find(u => u._id === agentCommission.userId)"
                                                     v-bind:class="{ wrong: orderToModify.errors.decommissions?.gas?.breakdown?.[agentDecommissionGas.id] }" class="form-group">
                                                    <label class="line-clamp-1 hidden">Decomisión de {{getFullName(userHierarchy.find(u => u._id === agentDecommissionGas.userId))}}</label>
                                                    <div class="input-group">
                                                        <p v-if="!canManage('contracts.manageCommissions')">{{agentDecommissionGas.commission}}</p>
                                                        <input v-else @focus="delete orderToModify.errors.decommissions?.gas?.breakdown?.[agentDecommissionGas.id]"
                                                               data-size="10" v-model="agentDecommissionGas.commission"
                                                               :disabled="isInputsDisabled" type="text">
                                                    </div>
                                                    <span v-if="orderToModify.errors.decommissions?.gas?.breakdown?.[agentDecommissionGas.id]" class="error">
                                                        {{orderToModify.errors.decommissions?.gas?.breakdown?.[agentDecommissionGas.id]}}
                                                    </span>
                                                </div>

                                                <!--Decomisión subdominio privada-->
                                                <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!this.orderToModify.assignedTo || (!!this.orderToModify.assignedTo && (this.basicData.userLogged && this.basicData.userLogged._id === orderToModify.assignedTo)))"
                                                     v-bind:class="{ wrong: orderToModify.errors.decommissions?.gas?.subdomain }"
                                                     class="form-group">
                                                    <label>Decomisión {{ basicData.enterprise.name }}</label>
                                                    <div class="input-group">
                                                        <input v-on:focus="delete orderToModify.errors.decommissions?.gas?.subdomain"
                                                               data-size="10" v-model="orderToModify.decommissions.gas.subdomain"
                                                               @blur="calcCommission(orderToModify.decommissions.gas.subdomain,'gas','decommissions')"
                                                               :disabled="isInputsDisabled || !canManage('contracts.manageCommissions')" type="text">
                                                    </div>
                                                    <span v-if="orderToModify.errors.decommissions?.gas?.subdomain" class="error">
                                                        {{orderToModify.errors.decommissions?.gas?.subdomain}}
                                                    </span>
                                                </div>

                                            </div>

                                            <!--Desglose de jerarquía de decomisiones-->
                                            <div
                                                v-if="filteredDecommissionHierarchyGas.length"
                                                class="d-flex align-center pointer position-relative"
                                                @click="showDecommissionHierarchyGas = !showDecommissionHierarchyGas"
                                            >
                                                <p class="ml-10">Ver decomisiones</p>

                                                <i
                                                    class="fa-solid position-absolute ml-5"
                                                    data-color="principal"
                                                    :class="showDecommissionHierarchyGas ? 'fa-chevron-up' : 'fa-chevron-down'"
                                                />
                                            </div>

                                            <transition name="accordion">
                                                <div v-if="showDecommissionHierarchyGas && filteredDecommissionHierarchyGas.length">
                                                    <div class="d-flex column mx-10">
                                                        <div class="form-group my-5" v-for="item in filteredDecommissionHierarchyGas" :key="item.userId" :class="{ wrong: orderToModify.errors.decommissions?.gas?.breakdown?.[item.userId] }">
                                                            <label class="line-clamp-1 hidden w-300-px-max">{{ getFullName(userHierarchy.find(u => u._id === item.userId)) }}</label>
                                                            <div class="input-group w-100-px">
                                                                <p v-if="!canManage('contracts.manageCommissions')">{{item.commission}}</p>
                                                                <input v-else @focus="delete orderToModify.errors.decommissions?.gas?.breakdown?.[item.userId]"
                                                                       data-size="10" v-model.trim="item.commission"
                                                                       :disabled="isInputsDisabled" type="text">
                                                            </div>
                                                            <span v-if="orderToModify.errors.decommissions?.gas?.breakdown?.[item.userId]" class="error">
                                                                {{ orderToModify.errors.decommissions?.gas?.breakdown?.[item.userId] }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </transition>
                                        </template>
                                    </div>
                                </div>


                                <!--Productos extras-->
                                <div class="form-group">
                                    <label>Productos extra</label>

                                    <div class="input-group column h-100">

                                        <!--Buscador-->
                                        <div class="mb-2 d-flex items-center">
                                            <i class="fa-regular fa-magnifying-glass text my-auto mr-10"/>
                                            <input type="text" v-model="extraSearchText" class="form-control" placeholder="Buscar extra..."/>
                                        </div>

                                        <!--Separador-->
                                        <div class="separator my-5"></div>

                                        <!--Cada producto-->
                                        <div class="h-100 h-150-px-max scroll-y">
                                            <div class="d-flex my-2 " v-for="extra in extraProductsToSelect">

                                                <!--check-->
                                                <div class="custom-checkbox my-auto" @click="!isInputsDisabled && toggleSelectExtraProduct(extra)">
                                                    <div v-bind:class="{ selected: orderToModify.extras && orderToModify.extras.includes(extra.id.$oid) }"></div>
                                                </div>

                                                <p class="text my-auto ml-5" data-size="10">{{ extra.name }}</p>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!--Nº pedido comercializadora-->
                                <div v-if="orderToModify.marketer"
                                     v-bind:class="{ wrong: orderToModify.errors.marketerOrderNumber }" class="form-group">
                                    <p class="my-auto"><label>Nº pedido {{ orderToModify.marketer }}</label></p>
                                    <div class="input-group">
                                        <input v-on:focus="delete orderToModify['errors']['marketerOrderNumber']"
                                               data-size="10" v-model="orderToModify.marketerOrderNumber"
                                               :disabled="isInputsDisabled" type="text">
                                    </div>
                                    <span v-if="orderToModify.errors.marketerOrderNumber" class="error">{{ orderToModify.errors.marketerOrderNumber }}</span>
                                </div>


                                <!--Verificación de contrato-->
                                <div v-if="orderToModify.productType">

                                    <label v-if="verificationContracts.filter(verification => $storage.PRODUCT_TYPES.find(productType => productType.code === orderToModify.productType).verificationsAvailable.includes(verification.code)).length > 0">Verificaciones de contrato</label>

                                    <div class="d-flex f-wrap">
                                        <div class="d-flex my-5 d-wrap"
                                             v-for="verification in verificationContracts.filter(verification => {
                                              const isAvailableForProductType = $storage.PRODUCT_TYPES.find(
                                                productType => productType.code === orderToModify.productType
                                              ).verificationsAvailable.includes(verification.code);

                                              // Special handling for 'mc' and 're' codes - they need user subdomain settings
                                              if (verification.code === 'mc') {
                                                return isAvailableForProductType && basicData?.userSubdomain?.settings.orderMarketerChange;
                                              } else if (verification.code === 're') {
                                                return isAvailableForProductType && basicData?.userSubdomain?.settings.orderRenovation;
                                              } else {
                                                return isAvailableForProductType;
                                              }
                                            })">

                                            <div class="custom-checkbox my-auto mr-10"
                                                 :class="{ 'opacity-3': isEditing && isInputsDisabled }"
                                                 @click="(!isReadOnly && isEditing && !isInputsDisabled) ? toggleSelectVerification(verification) : null">
                                                <div :class="{ selected: (!!orderToModify.verifications && orderToModify.verifications.includes(verification.code)) }">
                                                </div>
                                            </div>

                                            <p class="my-auto mr-15" data-color="principal" data-size="10">{{
                                                    verification.name }}</p>
                                        </div>
                                    </div>

                                    <!--Datos cambio de potencia-->
                                    <div v-if="(!!orderToModify.verifications && orderToModify.verifications.includes('pc'))"
                                         class="d-flex mt-10 mb-15">

                                        <div class="d-grid" data-column="2" data-gap="20">

                                            <!--Actual-->
                                            <div v-bind:class="{ wrong: orderToModify.errors.currentPotencyVerification }"
                                                 class="form-group my-0"
                                                 v-if="orderToModify.productType === 'cl' || orderToModify.productType === 'cg'">
                                                <div class="input-group">
                                                    <input
                                                        v-on:focus="delete orderToModify['errors']['currentPotencyVerification']"
                                                        data-size="10" v-model="orderToModify.currentPotencyVerification"
                                                        :disabled="isInputsDisabled" placeholder="Actual" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.currentPotencyVerification"
                                                      class="error">{{
                                                        orderToModify.errors.currentPotencyVerification }}</span>
                                            </div>

                                            <!--Solicitada-->
                                            <div v-bind:class="{ wrong: orderToModify.errors.requestedPotencyVerification }"
                                                 class="form-group my-0"
                                                 v-if="orderToModify.productType === 'cl' || orderToModify.productType === 'cg'">
                                                <div class="input-group">
                                                    <input
                                                        v-on:focus="delete orderToModify['errors']['requestedPotencyVerification']"
                                                        data-size="10" v-model="orderToModify.requestedPotencyVerification"
                                                        :disabled="isInputsDisabled" placeholder="Solicitada" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.requestedPotencyVerification"
                                                      class="error">{{
                                                        orderToModify.errors.requestedPotencyVerification }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!--Datos alta nueva-->
                                    <div v-if="(!!orderToModify.verifications && orderToModify.verifications.includes('nw'))">

                                        <div class="d-grid mt-10 mb-15" data-column="3" v-if="orderToModify.productType === 'cl' && !!orderToModify.fee">

                                            <div v-for="periodVerification in orderToModify.fee === 'Tarifa 2.0TD' ? 2 : 6"
                                                 v-bind:class="{ wrong: orderToModify.errors['periodVerification' + periodVerification] }"
                                                 class="form-group my-0">
                                                <p class="opacity-5 text" data-size="8">P{{ periodVerification }}</p>
                                                <div class="input-group">
                                                    <input
                                                        v-on:focus="delete orderToModify['errors']['periodVerification' + periodVerification]"
                                                        data-size="10"
                                                        v-model="orderToModify.newRegistrationPeriods['p' + periodVerification]"
                                                        :disabled="isInputsDisabled" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors['periodVerification' + periodVerification]"
                                                      class="error">{{
                                                        orderToModify.errors['periodVerification' + periodVerification]
                                                    }}</span>
                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <!--PARTE COMISIONES/DECO-->
                                <div v-if="orderToModify.productType !== 'cd'">

                                    <!--Select comision carterizada y porcentaje-->
                                    <div class="d-flex justify-between">

                                        <!--Cambiar entre comisiones carterizadas y normal-->
                                        <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!orderToModify.assignedTo || (!!orderToModify.assignedTo && (basicData.userLogged && basicData.userLogged._id === orderToModify.assignedTo)))" class="d-flex my-5">

                                            <div class="custom-checkbox my-auto mr-10" v-on:click="toggleInstallmentCommissions">
                                                <div v-bind:class="{ selected: orderToModify.installmentCommissions }"></div>
                                            </div>

                                            <p class="my-auto mr-15" data-color="principal" data-size="10">Comisión carterizada</p>
                                        </div>


                                        <!--Añadir intervalo-->
                                        <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!orderToModify.assignedTo || (!!orderToModify.assignedTo && (basicData.userLogged && basicData.userLogged._id === orderToModify.assignedTo))) && orderToModify.installmentCommissions && !isInputsDisabled" class="d-flex">
                                            <div class="custom-button my-auto mx-auto" data-size="medium" data-bg="amarillo" v-on:click="addInstallmentCommission"><i class="fas fa-plus"></i></div>
                                        </div>

                                    </div>

                                    <template v-if="!orderToModify.installmentCommissions">

                                        <!--Comisiones-->
                                        <div class="d-grid" data-column="2">

                                            <!--Comisión de venta-->
                                            <div v-if="userHierarchy.length > 0 && agentCommission && userHierarchy.find(u => u._id === agentCommission.userId)"
                                                 v-bind:class="{ wrong: orderToModify.errors.commissions?.breakdown?.[agentCommission.id] }" class="form-group">
                                                <label class="line-clamp-1 hidden">Comisión de {{getFullName(userHierarchy.find(u => u._id === agentCommission.userId))}}</label>
                                                <div class="input-group">
                                                    <p v-if="!canManage('contracts.manageCommissions')">{{agentCommission.commission}}</p>
                                                    <input v-else @focus="delete orderToModify.errors.commissions?.breakdown?.[agentCommission.id]"
                                                           data-size="10" v-model.trim="agentCommission.commission"
                                                           :disabled="isInputsDisabled" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.commissions?.breakdown?.[agentCommission.id]" class="error">
                                                    {{ orderToModify.errors.commissions?.breakdown?.[agentCommission.id] }}
                                                </span>
                                            </div>

                                            <!--Comisión subdominio privada-->
                                            <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!orderToModify.assignedTo || (!!orderToModify.assignedTo && (basicData.userLogged && basicData.userLogged._id === orderToModify.assignedTo)))"
                                                 v-bind:class="{ wrong: orderToModify.errors.commissions?.subdomain }" class="form-group">
                                                <label>Comisión {{ basicData.enterprise.name }}</label>
                                                <div class="input-group">
                                                    <input v-on:focus="delete orderToModify['errors']['commissions']?.['subdomain']"
                                                           @blur="calcCommission(orderToModify.commissions.subdomain)"
                                                           data-size="10" v-model.trim="orderToModify.commissions.subdomain"
                                                           :disabled="isInputsDisabled || !canManage('contracts.manageCommissions')" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.commissions?.subdomain" class="error">{{
                                                        orderToModify.errors.commissions?.subdomain
                                                    }}</span>
                                            </div>

                                        </div>

                                        <!--Desglose de jerarquía de comisiones-->
                                        <div
                                            v-if="filteredCommissionHierarchy.length"
                                            class="d-flex align-center pointer position-relative"
                                            @click="showCommissionHierarchy = !showCommissionHierarchy"
                                        >
                                            <p class="ml-10">Ver comisiones</p>

                                            <i
                                                class="fa-solid position-absolute ml-5"
                                                data-color="principal"
                                                :class="showCommissionHierarchy ? 'fa-chevron-up' : 'fa-chevron-down'"
                                            />
                                        </div>

                                        <transition name="accordion">
                                            <div v-if="showCommissionHierarchy && filteredCommissionHierarchy.length">
                                                <div class="d-flex column mx-10">
                                                    <div class="form-group my-5" v-for="item in filteredCommissionHierarchy" :key="item.userId" :class="{ wrong: orderToModify.errors.commissions?.breakdown?.[item.userId] }">
                                                        <label class="line-clamp-1 hidden w-300-px-max">{{ getFullName(userHierarchy.find(u => u._id === item.userId)) }}</label>
                                                        <div class="input-group w-100-px">
                                                            <p v-if="!canManage('contracts.manageCommissions')">{{item.commission}}</p>
                                                            <input v-else @focus="delete orderToModify.errors.commissions?.breakdown?.[item.userId]"
                                                                   data-size="10" v-model.trim="item.commission"
                                                                   :disabled="isInputsDisabled" type="text">
                                                        </div>
                                                        <span v-if="orderToModify.errors.commissions?.breakdown?.[item.userId]" class="error">
                                                            {{ orderToModify.errors.commissions?.breakdown?.[item.userId] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>
                                    </template>


                                    <!--Comisiones carterizadas-->
                                    <div v-else>

                                        <!--Total a pagar-->
                                        <div>
                                            <div class="d-grid" data-column="2">
                                                <!--Comisión de venta-->
                                                <div v-if="userHierarchy.length > 0 && agentCommission && userHierarchy.find(u => u._id === agentCommission.userId)"
                                                     v-bind:class="{ wrong: orderToModify.errors.commissions?.breakdown?.[agentCommission.id] }" class="form-group">
                                                    <label class="line-clamp-1">Comisión de {{getFullName(userHierarchy.find(u => u._id === agentCommission.userId))}} total</label>
                                                    <div class="input-group">
                                                        <input v-on:focus="delete orderToModify.errors.commissions?.breakdown?.[agentCommission.id]"
                                                               data-size="10" v-model.trim="agentCommission.commission"
                                                               disabled type="text">
                                                    </div>
                                                    <span v-if="orderToModify.errors.commissions?.breakdown?.[agentCommission.id]" class="error">
                                                        {{ orderToModify.errors.commissions?.breakdown?.[agentCommission.id] }}
                                                    </span>
                                                </div>

                                                <!--Comisión subdominio privada-->
                                                <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!orderToModify.assignedTo || (!!orderToModify.assignedTo && (basicData.userLogged && basicData.userLogged._id === orderToModify.assignedTo)))"
                                                     v-bind:class="{ wrong: orderToModify.errors.commissions?.subdomain }" class="form-group">
                                                    <label>Comisión {{ basicData.enterprise.name }} total</label>
                                                    <div class="input-group">
                                                        <input v-on:focus="delete orderToModify['errors']['commissions']?.['subdomain']"
                                                               data-size="10" v-model.trim="orderToModify.commissions.subdomain"
                                                               disabled type="text">
                                                    </div>
                                                    <span v-if="orderToModify.errors.commissions?.subdomain" class="error">
                                                        {{orderToModify.errors.commissions?.subdomain}}
                                                    </span>
                                                </div>
                                            </div>

                                            <!--Desglose de jerarquía de comisiones-->
                                            <div
                                                v-if="filteredCommissionHierarchy.length"
                                                class="d-flex align-center pointer position-relative"
                                                @click="showCommissionHierarchy = !showCommissionHierarchy"
                                            >
                                                <p class="ml-10">Ver comisiones</p>

                                                <i
                                                    class="fa-solid position-absolute ml-5"
                                                    data-color="principal"
                                                    :class="showCommissionHierarchy ? 'fa-chevron-up' : 'fa-chevron-down'"
                                                />
                                            </div>

                                            <transition name="accordion">
                                                <div v-if="showCommissionHierarchy && filteredCommissionHierarchy.length">
                                                    <div class="d-flex column mx-10">
                                                        <div class="form-group my-5" v-for="item in filteredCommissionHierarchy" :key="item.userId" :class="{ wrong: orderToModify.errors.commissions?.breakdown?.[item.userId] }">
                                                            <label class="line-clamp-1 hidden w-300-px-max">{{ getFullName(userHierarchy.find(u => u._id === item.userId)) }}</label>
                                                            <div class="input-group w-100-px">
                                                                <p v-if="!canManage('contracts.manageCommissions')">{{item.commission}}</p>
                                                                <input v-else @focus="delete orderToModify.errors.commissions?.breakdown?.[item.userId]"
                                                                       data-size="10" v-model.trim="item.commission"
                                                                       :disabled="isInputsDisabled" type="text">
                                                            </div>
                                                            <span v-if="orderToModify.errors.commissions?.breakdown?.[item.userId]" class="error">
                                                                {{ orderToModify.errors.commissions?.breakdown?.[item.userId] }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </transition>
                                        </div>

                                        <!--Labels-->
                                        <div class="d-flex mt-10">

                                        <div class="d-grid w-100" data-column="3">
                                            <p class="opacity-6">Fecha</p>
                                            <p class="opacity-6 line-clamp-1">Com. de {{getFullName(userHierarchy.find(u => u._id === agentCommission.userId))}}</p>
                                            <p class="opacity-6 line-clamp-1" v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!orderToModify.assignedTo || (!!orderToModify.assignedTo && (basicData.userLogged && basicData.userLogged._id === orderToModify.assignedTo)))">Com. {{ basicData.enterprise.name }}</p>
                                        </div>

                                        <div class="w-35-px" /> <!--Separador para que se vea bien -->
                                        </div>

                                        <template v-for="(installmentCommission, installmentCommissionInd) in orderToModify.installmentCommissions">
                                            <div class="d-flex">

                                                <div class="d-grid w-100" data-column="3">
                                                    <!--Fecha-->
                                                    <div v-bind:class="{ wrong: orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.date }" class="form-group">
                                                        <div class="input-group">
                                                            <input v-if="canManage('contracts.manageCommissions')"
                                                                   v-on:focus="delete orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.date" data-size="10"
                                                                   v-on:change="reorderInstallmentCommissions"
                                                                   v-model="installmentCommission.date" :disabled="isInputsDisabled"
                                                                   type="date">
                                                            <div v-else class="text opacity-5" data-size="10">{{
                                                                    installmentCommission.date ?
                                                                        getPrettyDate(installmentCommission.date) : 'Por asignar' }}</div>
                                                        </div>
                                                        <span v-if="orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.date" class="error">{{
                                                                orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.date }}</span>
                                                    </div>

                                                    <!--Comisión de venta-->
                                                    <div v-if="userHierarchy.length > 0 && installmentCommission.commissions.breakdown?.length"
                                                         v-bind:class="{ wrong: orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.breakdown?.[_getAgentCommissionFromBreakdown(installmentCommission.commissions.breakdown).id] }"
                                                         class="form-group">
                                                        <div class="input-group">
                                                            <p v-if="!canManage('contracts.manageCommissions')">{{_getAgentCommissionFromBreakdown(installmentCommission.commissions.breakdown)}}</p>
                                                            <input v-else @focus="delete orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.breakdown?.[_getAgentCommissionFromBreakdown(installmentCommission.commissions.breakdown).id]"
                                                                   data-size="10"
                                                                   @blur="calcInstallmentCommissionsTotal"
                                                                   :value="_getAgentCommissionFromBreakdown(installmentCommission.commissions.breakdown).commission"
                                                                   @input="e => _setAgentCommissionFromBreakdown(installmentCommission.commissions.breakdown, { ..._getAgentCommissionFromBreakdown(installmentCommission.commissions.breakdown), commission: e.target.value })"
                                                                   :disabled="isInputsDisabled" type="text">
                                                        </div>
                                                        <span v-if="orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.breakdown?.[_getAgentCommissionFromBreakdown(installmentCommission.commissions.breakdown).id]" class="error">
                                                            {{ orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.breakdown?.[_getAgentCommissionFromBreakdown(installmentCommission.commissions.breakdown).id] }}
                                                        </span>
                                                    </div>

                                                    <!--Comisión subdominio privada-->
                                                    <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!orderToModify.assignedTo || (!!orderToModify.assignedTo && (basicData.userLogged && basicData.userLogged._id === orderToModify.assignedTo)))"
                                                         v-bind:class="{ wrong: orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.subdomain }" class="form-group">
                                                        <div class="input-group">
                                                            <input v-on:focus="delete orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.subdomain"
                                                                   @blur="onInstallmentCommissionChange(installmentCommission.commissions.subdomain, installmentCommissionInd)"
                                                                   data-size="10" v-model.trim="installmentCommission.commissions.subdomain"
                                                                   :disabled="isInputsDisabled || !canManage('contracts.manageCommissions')" type="text">
                                                        </div>
                                                        <span v-if="orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.subdomain" class="error">
                                                            {{orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.subdomain}}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!--Borrar comisión-->
                                                <div class="ml-20 d-flex" v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!orderToModify.assignedTo || (!!orderToModify.assignedTo && (basicData.userLogged && basicData.userLogged._id === orderToModify.assignedTo)))">
                                                    <i class="fa-solid fa-trash pointer m-auto" data-color="rojo" v-on:click="deleteInstallmentCommission(installmentCommissionInd)"></i>
                                                </div>

                                            </div>

                                            <!--Desglose de jerarquía de comisiones-->
                                            <div
                                                v-if="_filteredHierarchyFromBreakdown(installmentCommission.commissions.breakdown).length"
                                                class="d-flex align-center pointer position-relative"
                                                @click="showInstallmentCommissionHierarchy === installmentCommissionInd ? showInstallmentCommissionHierarchy = null : showInstallmentCommissionHierarchy = installmentCommissionInd"
                                            >
                                                <p class="ml-10">Ver comisiones</p>
                                                <i
                                                    class="fa-solid position-absolute ml-5"
                                                    data-color="principal"
                                                    :class="showInstallmentCommissionHierarchy === installmentCommissionInd ? 'fa-chevron-up' : 'fa-chevron-down'"
                                                />
                                            </div>

                                            <transition name="accordion">
                                                <div v-if="showInstallmentCommissionHierarchy === installmentCommissionInd && _filteredHierarchyFromBreakdown(installmentCommission.commissions.breakdown).length">
                                                    <div class="d-flex column mx-10">
                                                        <div class="form-group my-5"
                                                             v-for="item in _filteredHierarchyFromBreakdown(installmentCommission.commissions.breakdown)"
                                                             :key="item.userId"
                                                             :class="{ wrong: orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.breakdown?.[item.userId] }">
                                                            <label class="line-clamp-1 hidden w-300-px-max">{{ getFullName(userHierarchy.find(u => u._id === item.userId)) }}</label>
                                                            <div class="input-group w-100-px">
                                                                <p v-if="!canManage('contracts.manageCommissions')">{{item.commission}}</p>
                                                                <input v-else @focus="delete orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.breakdown?.[item.userId]"
                                                                       data-size="10" v-model.trim="item.commission"
                                                                       :disabled="isInputsDisabled"
                                                                       @blur="calcInstallmentCommissionsTotal"
                                                                       type="text" />
                                                            </div>
                                                            <span v-if="orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.breakdown?.[item.userId]" class="error">
                                                                {{ orderToModify.errors?.installmentCommissions?.[installmentCommissionInd]?.breakdown?.[item.userId] }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </transition>
                                        </template>

                                    </div>


                                    <!--Decomisiones-->
                                    <template v-if="statusRequiresDecommissions(statusSelected.code)">
                                        <div class="d-grid" data-column="2">

                                            <!--Decomisión de venta-->
                                            <div v-if="userHierarchy.length > 0 && agentDecommission && userHierarchy.find(u => u._id === agentCommission.userId)"
                                                 v-bind:class="{ wrong: orderToModify.errors.decommissions?.breakdown?.[agentDecommission.id] }" class="form-group">
                                                <label class="line-clamp-1 hidden">Decomisión de {{getFullName(userHierarchy.find(u => u._id === agentDecommission.userId))}}</label>
                                                <div class="input-group">
                                                    <p v-if="!canManage('contracts.manageCommissions')">{{agentDecommission.commission}}</p>
                                                    <input v-else @focus="delete orderToModify.errors.decommissions?.breakdown?.[agentDecommission.id]"
                                                           data-size="10" v-model="agentDecommission.commission"
                                                           :disabled="isInputsDisabled" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.decommissions?.breakdown?.[agentDecommission.id]" class="error">
                                                    {{orderToModify.errors.decommissions?.breakdown?.[agentDecommission.id]}}
                                                </span>
                                            </div>

                                            <!--Decomisión subdominio privada-->
                                            <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!this.orderToModify.assignedTo || (!!this.orderToModify.assignedTo && (this.basicData.userLogged && this.basicData.userLogged._id === this.orderToModify.assignedTo)))"
                                                 v-bind:class="{ wrong: orderToModify.errors.decommissions?.subdomain }"
                                                 class="form-group">
                                                <label>Decomisión {{ basicData.enterprise.name }}</label>
                                                <div class="input-group">
                                                    <input v-on:focus="delete orderToModify.errors.decommissions?.subdomain"
                                                           data-size="10" v-model="orderToModify.decommissions.subdomain"
                                                           @blur="calcCommission(orderToModify.decommissions.subdomain,null,'decommissions')"
                                                           :disabled="isInputsDisabled || !canManage('contracts.manageCommissions')" type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.decommissions?.subdomain" class="error">
                                                    {{orderToModify.errors.decommissions?.subdomain}}
                                                </span>
                                            </div>
                                        </div>

                                        <!--Desglose de jerarquía de decomisiones-->
                                        <div
                                            v-if="filteredDecommissionHierarchy.length"
                                            class="d-flex align-center pointer position-relative"
                                            @click="showDecommissionHierarchy = !showDecommissionHierarchy"
                                        >
                                            <p class="ml-10">Ver decomisiones</p>

                                            <i
                                                class="fa-solid position-absolute ml-5"
                                                data-color="principal"
                                                :class="showDecommissionHierarchy ? 'fa-chevron-up' : 'fa-chevron-down'"
                                            />
                                        </div>

                                        <transition name="accordion">
                                            <div v-if="showDecommissionHierarchy && filteredDecommissionHierarchy.length">
                                                <div class="d-flex column mx-10">
                                                    <div class="form-group my-5" v-for="item in filteredDecommissionHierarchy" :key="item.userId" :class="{ wrong: orderToModify.errors.decommissions?.breakdown?.[item.userId] }">
                                                        <label class="line-clamp-1 hidden w-300-px-max">{{ getFullName(userHierarchy.find(u => u._id === item.userId)) }}</label>
                                                        <div class="input-group w-100-px">
                                                            <p v-if="!canManage('contracts.manageCommissions')">{{item.commission}}</p>
                                                            <input v-else @focus="delete orderToModify.errors.decommissions?.breakdown?.[item.userId]"
                                                                   data-size="10" v-model.trim="item.commission"
                                                                   :disabled="isInputsDisabled" type="text">
                                                        </div>
                                                        <span v-if="orderToModify.errors.decommissions?.breakdown?.[item.userId]" class="error">
                                                            {{ orderToModify.errors.decommissions?.breakdown?.[item.userId] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>

                                    </template>
                                </div>

                                <div v-else>


                                    <!--Comisiones-->
                                    <div class="d-grid" data-column="2">

                                        <!--Comisión de venta total-->
                                        <div v-if="userHierarchy.length > 0 && agentCommission && userHierarchy.find(u => u._id === agentCommission.userId)"
                                             v-bind:class="{ wrong: orderToModify.errors.commissions?.breakdown?.[agentCommission.id] }" class="form-group">
                                            <label class="line-clamp-1 hidden">Comisión de {{getFullName(userHierarchy.find(u => u._id === agentCommission.userId))}} total</label>
                                            <div class="input-group">
                                                <input v-on:focus="delete orderToModify.errors.commissions?.breakdown?.[agentCommission.id]"
                                                       data-size="10" v-model.trim="agentCommission.commission"
                                                       disabled type="text">
                                            </div>
                                            <span v-if="orderToModify.errors.commissions?.breakdown?.[agentCommission.id]" class="error">
                                                {{ orderToModify.errors.commissions?.breakdown?.[agentCommission.id] }}
                                            </span>
                                        </div>

                                        <!--Comisión subdominio privada-->
                                        <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!orderToModify.assignedTo || (!!orderToModify.assignedTo && (basicData.userLogged && basicData.userLogged._id === orderToModify.assignedTo)))"
                                             v-bind:class="{ wrong: orderToModify.errors.commissions?.subdomain }" class="form-group">
                                            <label>Comisión {{ basicData.enterprise.name }} total</label>
                                            <div class="input-group">
                                                <input v-on:focus="delete orderToModify['errors']['commissions']?.['subdomain']"
                                                       @blur="calcCommission(orderToModify.commissions.subdomain)"
                                                       data-size="10" v-model.trim="orderToModify.commissions.subdomain"
                                                       disabled type="text">
                                            </div>
                                            <span v-if="orderToModify.errors.commissions?.subdomain" class="error">{{orderToModify.errors.commissions?.subdomain }}</span>
                                        </div>

                                    </div>

                                    <!--Desglose de jerarquía de comisiones-->
                                    <div
                                        v-if="filteredCommissionHierarchy.length"
                                        class="d-flex align-center pointer position-relative"
                                        @click="showCommissionHierarchy = !showCommissionHierarchy"
                                    >
                                        <p class="ml-10">Ver comisiones</p>

                                        <i
                                            class="fa-solid position-absolute ml-5"
                                            data-color="principal"
                                            :class="showCommissionHierarchy ? 'fa-chevron-up' : 'fa-chevron-down'"
                                        />
                                    </div>

                                    <transition name="accordion">
                                        <div v-if="showCommissionHierarchy && filteredCommissionHierarchy.length">
                                            <div class="d-flex column mx-10">
                                                <div class="form-group my-5" v-for="item in filteredCommissionHierarchy" :key="item.userId" :class="{ wrong: orderToModify.errors.commissions?.breakdown?.[item.userId] }">
                                                    <label class="line-clamp-1 hidden w-300-px-max">{{ getFullName(userHierarchy.find(u => u._id === item.userId)) }}</label>
                                                    <div class="input-group w-100-px">
                                                        <input @focus="delete orderToModify.errors.commissions?.breakdown?.[item.userId]"
                                                               data-size="10" v-model.trim="item.commission"
                                                               disabled type="text">
                                                    </div>
                                                    <span v-if="orderToModify.errors.commissions?.breakdown?.[item.userId]" class="error">
                                                        {{ orderToModify.errors.commissions?.breakdown?.[item.userId] }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>


                                    <!--Decomisiones-->
                                    <template v-if="statusRequiresDecommissions(statusSelected.code)">
                                        <div class="d-grid" data-column="2">

                                            <!--Decomisión de venta total-->
                                            <div v-if="userHierarchy.length > 0 && agentDecommission && userHierarchy.find(u => u._id === agentCommission.userId)"
                                                 v-bind:class="{ wrong: orderToModify.errors.decommissions?.breakdown?.[agentDecommission.id] }" class="form-group">
                                                <label class="line-clamp-1 hidden">Decomisión de {{getFullName(userHierarchy.find(u => u._id === agentDecommission.userId))}} total</label>
                                                <div class="input-group">
                                                    <input v-on:focus="delete orderToModify.errors.decommissions?.breakdown?.[agentDecommission.id]"
                                                           data-size="10" v-model="agentDecommission.commission"
                                                           disabled type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.decommissions?.breakdown?.[agentDecommission.id]" class="error">
                                                    {{orderToModify.errors.decommissions?.breakdown?.[agentDecommission.id]}}
                                                </span>
                                            </div>

                                            <!--Decomisión subdominio privada-->
                                            <div v-if="canManage('contracts.manageCommissions') && /*Si es de otro subdominio y se asigna a Zoco*/ (!this.orderToModify.assignedTo || (!!this.orderToModify.assignedTo && (this.basicData.userLogged && this.basicData.userLogged._id === orderToModify.assignedTo)))"
                                                 v-bind:class="{ wrong: orderToModify.errors.decommissions?.subdomain }"
                                                 class="form-group">
                                                <label>Decomisión {{ basicData.enterprise.name }} total</label>
                                                <div class="input-group">
                                                    <input v-on:focus="delete orderToModify.errors.decommissions?.subdomain"
                                                           data-size="10" v-model="orderToModify.decommissions.subdomain"
                                                           disabled type="text">
                                                </div>
                                                <span v-if="orderToModify.errors.decommissions?.subdomain" class="error">
                                                    {{orderToModify.errors.decommissions?.subdomain}}
                                                </span>
                                            </div>

                                        </div>

                                        <!--Desglose de jerarquía de decomisiones-->
                                        <div
                                            v-if="filteredDecommissionHierarchy.length"
                                            class="d-flex align-center pointer position-relative"
                                            @click="showDecommissionHierarchy = !showDecommissionHierarchy"
                                        >
                                            <p class="ml-10">Ver decomisiones</p>

                                            <i
                                                class="fa-solid position-absolute ml-5"
                                                data-color="principal"
                                                :class="showDecommissionHierarchy ? 'fa-chevron-up' : 'fa-chevron-down'"
                                            />
                                        </div>

                                        <transition name="accordion">
                                            <div v-if="showDecommissionHierarchy && filteredDecommissionHierarchy.length">
                                                <div class="d-flex column mx-10">
                                                    <div class="form-group my-5" v-for="item in filteredDecommissionHierarchy" :key="item.userId" :class="{ wrong: orderToModify.errors.decommissions?.breakdown?.[item.userId] }">
                                                        <label class="line-clamp-1 hidden w-300-px-max">{{ getFullName(userHierarchy.find(u => u._id === item.userId)) }}</label>
                                                        <div class="input-group w-100-px">
                                                            <input @focus="delete orderToModify.errors.decommissions?.breakdown?.[item.userId]"
                                                                   data-size="10" v-model.trim="item.commission"
                                                                   disabled type="text">
                                                        </div>
                                                        <span v-if="orderToModify.errors.decommissions?.breakdown?.[item.userId]" class="error">
                                                            {{ orderToModify.errors.decommissions?.breakdown?.[item.userId] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>
                                    </template>
                                </div>


                            </div>


                            <!--DOCUMENTOS-->
                            <div class="docs" v-if="isSeeingDocs">

                                <div class="d-flex justify-between">
                                    <p class="text" data-size="15" data-weight="700">Docs. adjuntos</p>

                                    <div class="custom-button ml-auto my-auto" data-size="small" data-bg="amarillo"
                                         v-if="this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2' ? true : !isInputsDisabled" v-on:click="openDialog"><i class="fas fa-plus"></i></div>
                                </div>

                                <input :id="'docFile' + this.docInd" type="file" style="display: none" multiple
                                       v-on:change="pickupImage">

                                <div class="div-content">
                                    <doc-component v-for="(doc, docInd) in orderToModify.docs" :doc="doc" :docInd="docInd"
                                                   :isReadOnly="this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2' ? false : isInputsDisabled" @delDoc="delDoc"></doc-component>
                                </div>

                                <div class="separator"></div>


                                <div class="d-flex justify-end">
                                    <button class="custom-button mr-10" data-size="small" data-bg="rojo"
                                            v-on:click.prevent="isSeeingDocs = false">Cerrar</button>
                                </div>

                            </div>
                            <!--DAR DE BAJA-->
                            <div class="docs" v-if="showLowModal">

                                <div class="d-flex justify-between">
                                    <p class="text" data-size="15" data-weight="700">Dar de baja anticipada</p>
                                </div>

                                <div class="separator"></div>

                                <!-- Fecha baja -->
                                <div class="form-group">
                                    <label>Fecha de baja</label>
                                    <div class="input-group">
                                        <input type="date" v-model="lowForm.lowDate">
                                    </div>
                                </div>

                                <!-- Retro -->
                                <div class="d-flex align-center mt-10 mb-10">
                                    <div class="custom-checkbox mr-10"
                                         @click="lowForm.hasRetro = !lowForm.hasRetro">
                                        <div :class="{ selected: lowForm.hasRetro }"></div>
                                    </div>
                                    <p class="text" data-size="10">Procede retrocomisión</p>
                                </div>

                                <div v-if="lowForm.hasRetro">

                                    <div class="form-group">
                                        <label>Tipo de ajuste</label>
                                        <div class="input-group">
                                            <select v-model="lowForm.retroType">
                                                <option value="percent">Por porcentaje</option>
                                                <option value="fixed">Cantidad fija</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>
                                            {{ lowForm.retroType === 'percent'
                                            ? 'Porcentaje (%)'
                                            : 'Cantidad (€)' }}
                                        </label>
                                        <div class="input-group">
                                            <input type="number"
                                                   v-model.number="lowForm.retroValue"
                                                   step="0.01">
                                        </div>
                                    </div>

                                </div>

                                <div class="separator"></div>

                                <div class="d-flex justify-end">
                                    <button class="custom-button mr-10"
                                            data-size="small"
                                            data-bg="rojo"
                                            @click="showLowModal = false">
                                        Cancelar
                                    </button>

                                    <button class="custom-button"
                                            data-size="small"
                                            data-bg="principal"
                                            @click="applyLow">
                                        Aplicar
                                    </button>
                                </div>

                            </div>

                            <!--Propietarios del contrato móvil-->
                            <div class="form-group mt-20 owners-section owners-section-mobile-end">
                                <div class="mobile-item owners-toggle" @click="showOwnersBottomOnMobile = !showOwnersBottomOnMobile">
                                    <div class="d-flex align-center justify-between">
                                        <p class="text" data-size="14" data-weight="700">Propietarios del contrato</p>
                                        <i class="fa-solid" :class="showOwnersBottomOnMobile ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </div>
                                </div>

                                <div :class="{ 'd-none': !showOwnersBottomOnMobile }" class="mobile-item owners-list-mobile">
                                    <user-list-component
                                        :basicData="basicData"
                                        v-model:userListSelected="orderToModify.usersIds"
                                        :userListToSelect="account?.usersIds"
                                        :editing="isEditing"
                                    ></user-list-component>
                                    <p v-if="basicData.userList.length === 0" class="text opacity-3" data-size="10">
                                        No tienes usuarios para asignar
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--Separator-->
                    <div class="separator"></div>

                    <div class="d-flex justify-between">

                        <div>
                            <button v-if="$route.path !== '/'" class="custom-button mr-10" data-size="regular"
                                    :data-bg="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? 'amarillo' : 'success'" v-on:click.prevent="history.visible = true">
                                {{ this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? 'Ver histórico' : 'Chat de seguimiento' }}
                            </button>

                            <button class="custom-button mr-10" data-size="regular" :data-bg="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? 'azul' : 'rojo'" v-on:click.prevent="isSeeingDocs = !isSeeingDocs">
                                {{ this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? 'Documentos' : 'Gestionar archivos' }}
                            </button>

                            <!--Si el contrato está monitorizado con datadis-->
                            <button v-if="basicData.subdomainEnterprise?.monitoredCups?.includes(orderToModify.CUPS) ?? false" class="custom-button mr-10" data-size="regular" data-bg="morado" v-on:click.prevent="actionLink('/tools?section=datadis&CUPS=' + orderToModify.CUPS)">
                                <i class="fas fa-lightbulb-cfl mr-10" aria-hidden="true" /> Suministro monitorizado
                            </button>

                            <button v-if="(
                                        basicData.userLogged &&
                                        (canManage('contracts.calls') || basicData.userLogged._id === '68edfbf7c84a190aeb0a6672') &&
                                        (
                                            // Caso antiguo (CallSID con estado)
                                            (orderToModify.naturgyCallSID && naturgyCallStatus && naturgyCallStatus.color)

                                            // Caso nuevo (YA TIENE GRABACIÓN)
                                            || orderToModify.naturgyCallRecordingUrl

                                            // Caso firmado sin llamada
                                            || (!orderToModify.naturgyCallSID && statusSelected.code === 'f')
                                        ) &&
                                        (orderToModify && orderToModify.marketer?.trim().toLowerCase() === 'naturgy')
                                    )"
                                class="custom-button mr-10"
                                data-size="regular"
                                :data-bg="orderToModify.naturgyCallRecordingUrl
                                            ? 'success'
                                            : (orderToModify.naturgyCallSID && naturgyCallStatus)
                                                ? naturgyCallStatus.color
                                                : 'naturgyColor'
                                        "
                                data-color="blanco"
                                v-on:click.prevent="handleCall">
                                {{
                                    orderToModify.naturgyCallRecordingUrl
                                        ? 'Llamada completada'
                                        : (orderToModify.naturgyCallSID && naturgyCallStatus && naturgyCallStatus.color)
                                            ? naturgyCallStatus.title
                                            : 'Llamada de verificación'
                                }}
                            </button>
                            <button
                                v-if="statusSelected.code === 'pendiente_de_retrocomisin' && canManage('contracts.processor')"
                                class="custom-button mr-10"
                                data-size="regular"
                                data-bg="rojo"
                                @click.prevent="showLowModal = true"
                            >
                                Baja Anticipada
                            </button>
                        </div>



                        <div class="btn-part">

                            <!--Visualización-->
                            <div v-if="!isEditing">
                                <button v-if="canShowRenewalCopyButton" class="custom-button mr-10" data-size="regular" data-bg="principal" v-on:click.prevent="createRenewalCopy">
                                    Renovar
                                </button>
                                <button v-if="account && !$route.path.includes('accounts')" class="custom-button mr-10" data-size="regular" data-bg="azul"
                                        v-on:click.prevent="actionLink('/accounts/' + account._id + '?fromAccounts')">Ver cuenta</button>
                                <button class="custom-button mr-10" data-size="regular" data-bg="rojo"
                                        v-on:click.prevent="closeWindow">{{ $route.path.includes('accounts') ? 'Volver a cuenta' : 'Volver' }}</button>
                                <button
                                    class="custom-button"
                                    data-size="regular"
                                    v-if="!isReadOnly && canManage('contracts.edit')"
                                    @click="isEditing = true"
                                >
                                    Editar
                                </button>


                            </div>

                            <!--Edición-->
                            <div v-if="!isCreatingOrder && isEditing">
                                <button
                                    v-if="canValidateDraft()"
                                    class="custom-button mr-20"
                                    data-size="regular"
                                    data-bg="principal"
                                    @click.prevent="validateOrder(); toggleEditingContract('substract', order._id)"
                                >
                                    Validar
                                </button>
                                <button v-if="account" class="custom-button mr-20" data-size="regular" data-bg="azul"
                                        v-on:click.prevent="handleClick(); toggleEditingContract('add', order._id)">Ver cuenta</button>

                                <button class="custom-button mr-20" data-size="regular" data-bg="rojo"
                                        v-on:click.prevent="toggleCancelOrder(); toggleEditingContract('substract', order._id)">Cancelar</button>
                                <button class="custom-button mr-20" data-size="regular"
                                        v-on:click.prevent="createOrder(); toggleEditingContract('substract', order._id)">{{ orderToModify._id ? 'Actualizar' : 'Guardar' }}</button>
                            </div>

                            <!--Cargando-->
                            <div v-if="isCreatingOrder">
                                <button class="custom-button" data-size="regular"> <i
                                    class="fa-solid fa-spinner-third fa-spin mr-5"></i>
                                    Espere un momento</button>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!--si se esta viendo el histórico-->
        <div class="floating-box z-11" v-bind:class="{ 'seeing': history.visible, 'd-none': !history.visible, 'top h-100-dvh': width < 810 }" @click.self="history.visible = false">

            <div class="register-pos w-70 h-80 round px-40 py-20" data-round="20" data-border-color="principal">

                <div class="d-flex column h-100">

                    <div class="d-flex justify-between">
                        <div class="d-flex column mobile-item">
                            <p class="text" data-size="20" data-weight="700">Histórico</p>
                            <div class="d-flex" data-gap="10">
                                <div class="custom-button pointer h-fit-content" :data-bg="history.statuses ? 'principal' : 'ventana-lateral'" :data-color="history.statuses ? 'white' : 'gris-oscuro'" data-size="small" @click="history.statuses = !history.statuses">
                                    <i class="far fa-timeline"/>
                                    Estados
                                </div>
                                <div class="custom-button pointer h-fit-content" :data-bg="history.messages ? 'principal' : 'ventana-lateral'" :data-color="history.messages ? 'white' : 'gris-oscuro'" data-size="small" @click="history.messages = !history.messages">
                                    <i class="far fa-message"/>
                                    Mensajes
                                </div>
                                <div class="custom-button pointer h-fit-content" :data-bg="history.files ? 'principal' : 'ventana-lateral'" :data-color="history.files ? 'white' : 'gris-oscuro'" data-size="small" @click="history.files = !history.files">
                                    <i class="far fa-file"/>
                                    Archivos
                                </div>
                            </div>
                        </div>
                        <div class="d-flex desktop-item" data-gap="10">
                            <p class="text mr-40" data-size="20" data-weight="700">Histórico</p>
                            <div class="custom-button pointer h-fit-content" :data-bg="history.statuses ? 'principal' : 'ventana-lateral'" :data-color="history.statuses ? 'white' : 'gris-oscuro'" data-size="small" @click="history.statuses = !history.statuses">
                                <i class="far fa-timeline"/>
                                Estados
                            </div>
                            <div class="custom-button pointer h-fit-content" :data-bg="history.messages ? 'principal' : 'ventana-lateral'" :data-color="history.messages ? 'white' : 'gris-oscuro'" data-size="small" @click="history.messages = !history.messages">
                                <i class="far fa-message"/>
                                Mensajes
                            </div>
                            <div class="custom-button pointer h-fit-content" :data-bg="history.files ? 'principal' : 'ventana-lateral'" :data-color="history.files ? 'white' : 'gris-oscuro'" data-size="small" @click="history.files = !history.files">
                                <i class="far fa-file"/>
                                Archivos
                            </div>
                        </div>
                        <i class="text fas fa-close pointer" data-size="20" @click="history.visible = false"/>
                    </div>

                    <div class="flex-1 mt-15 scroll-y" id="messages-container">
                        <div class="my-5" v-for="history in filteredHistory" v-if="filteredHistory">

                            <div :class="[{'ml-auto' : history.creator.id === basicData.userLogged._id},'dashboard-card column w-45 py-10']"
                                 :data-bg="history.creator.id === basicData.userLogged._id ? 'celeste' : ''"
                                 :data-color="history.creator.id === basicData.userLogged._id ? 'blanco' : ''"
                            >

                                <div class="d-flex align-center justify-between">
                                    <div class="d-flex align-center" data-gap="5">
                                        <img class="w-25-px h-25-px round cover-img" data-round="10" :src="'/assets/profile_images/' + history.creator.profileImage" alt="Imagen usuario">
                                        <p class="line-clamp-1" data-size="12">{{ history.creator.name ? history.creator.name : 'Desconocido' }}</p>
                                    </div>
                                    <p :class="[{'opacity-5' : history.creator.id !== basicData.userLogged._id}]" data-size="10"><i class="fa-solid fa-calendar"></i> {{getPrettyDateHistory(history.date) }}</p>
                                </div>

                                <div v-if="history.type === 'status'" class="mt-10 mobile-item" data-gap="10">
                                    <p>Se cambió el contrato al estado</p>
                                    <div data-bg="blanco" class="round w-fit-content" data-round="15">
                                        <div class="custom-button text-center w-90-px w-180-px-max mr-auto" data-size="small" data-color="principal" :data-mode="!isHex(history.data.color) ? 'translucent' : null" :data-bg="!isHex(history.data.color) ? history.data.color : null"
                                             :style="isHex(history.data.color) ? {
                                            backgroundColor: hexToRgba(history.data.color, 0.1),
                                            border: `1px solid ${history.data.color}`
                                        } : {}">
                                            <p class="w-140-px-max ellipsis" style="line-height: 1">{{ history.data.title }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="history.type === 'status'" class="d-flex align-center mt-10 desktop-item" data-gap="10">
                                    <span>Se cambió el contrato al estado</span>
                                    <div data-bg="blanco" class="round" data-round="15">
                                        <div class="custom-button text-center w-90-px w-180-px-max mr-auto" data-size="small" data-color="principal" :data-mode="!isHex(history.data.color) ? 'translucent' : null" :data-bg="!isHex(history.data.color) ? history.data.color : null"
                                             :style="isHex(history.data.color) ? {
                                            backgroundColor: hexToRgba(history.data.color, 0.1),
                                            border: `1px solid ${history.data.color}`
                                        } : {}">
                                            <p class="w-140-px-max ellipsis" style="line-height: 1">{{ history.data.title }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-else-if="history.type === 'message'" class="mt-10">
                                    {{history.data.message}}
                                </div>

                                <div v-else-if="history.type === 'file'" class="d-flex align-center mt-10" data-gap="8">
                                    <i class="far fa-file"/>
                                    <span>Se adjuntó el archivo <strong>{{ history.data.title }}</strong>.</span>
                                </div>


                            </div>
                        </div>
                    </div>

                    <!--Mensajes-->
                    <form @submit.prevent="createNewMessage">
                        <div class="dashboard-card w-100">
                            <input class="w-100 text mr-15" style="border: none; background: transparent" v-model="newMessage" placeholder="Aquí va el mensaje" />
                            <button type="submit" class="custom-button pointer" data-size="small" :disabled="sendingMessage">
                                <i v-if="sendingMessage" class="fa-regular fa-spinner fa-spin"></i>
                                <i v-else class="far fa-paper-plane-top" />
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <div class="docs" v-if="showLowModal">

            <div class="d-flex justify-between">
                <p class="text" data-size="15" data-weight="700">Dar de baja</p>

                <button class="custom-button"
                        data-size="small"
                        data-bg="rojo"
                        @click="showLowModal = false">
                    Cerrar
                </button>
            </div>

            <div class="separator my-15"></div>

            <!-- Fecha baja -->
            <div class="form-group">
                <label>Fecha de baja</label>
                <div class="input-group">
                    <input type="date" v-model="lowForm.lowDate">
                </div>
            </div>

            <!-- Retro -->
            <div class="d-flex align-center mt-10 mb-10">
                <div class="custom-checkbox mr-10"
                     @click="lowForm.hasRetro = !lowForm.hasRetro">
                    <div :class="{ selected: lowForm.hasRetro }"></div>
                </div>
                <p class="text" data-size="10">Procede retrocomisión</p>
            </div>

            <div v-if="lowForm.hasRetro">

                <div class="form-group">
                    <label>Tipo de ajuste</label>
                    <div class="input-group">
                        <select v-model="lowForm.retroType">
                            <option value="percent">Por porcentaje</option>
                            <option value="fixed">Cantidad fija</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        {{ lowForm.retroType === 'percent'
                        ? 'Porcentaje (%)'
                        : 'Cantidad (€)' }}
                    </label>
                    <div class="input-group">
                        <input type="number"
                               v-model.number="lowForm.retroValue"
                               step="0.01">
                    </div>
                </div>

            </div>

            <div class="separator"></div>

            <div class="d-flex justify-end">
                <button class="custom-button mr-10"
                        data-size="small"
                        data-bg="rojo"
                        @click="showLowModal = false">
                    Cancelar
                </button>

                <button class="custom-button"
                        data-size="small"
                        data-bg="principal"
                        @click="applyLow">
                    Aplicar
                </button>
            </div>

        </div>
    </div>
</template>

<script>

import {getOrderDate} from '@/utils/order';
import {obtainSubdomainUser} from "@/utils/utilities";
import {startCall} from '@/composables/useCall';
import {buildEmptyBreakdown, calculateCommission} from "@/utils/calcCommission";
import {useWindowSize} from "@vueuse/core";

export default {
    name: "OrderDetailsItemComponent",
    props: ['basicData', 'order', 'account', 'selectValues', 'isCreatingOrder', 'isReadOnly', 'ordersEditing', 'originalOrders', 'isEditingFromOther'],
    data() {
        return {
            lowForm: {
                lowDate: null,
                hasRetro: false,
                retroType: 'percent',
                retroValue: 0
            },
            showLowModal: false,
            showConsumption: false,
            orderToModify: [],
            sources: [],
            showPrices: false,
            marketers: [],
            rates: {
                electricity: ['Tarifa 2.0TD', 'Tarifa 3.0TD', 'Tarifa 6.1TD'],
                gas: ['Tarifa RL1', 'Tarifa RL2', 'Tarifa RL3', 'Tarifa RL4', 'Tarifa RL5', 'Tarifa RL6']
            },
            marketerProductsOthers: {
                'n': ['Sin excedentes', 'Con excedentes', 'Compartido'],//normales
                'crm': ['Contrato de CRM', 'Contrato de colaborador'],
                'electricCar': ['Cargador Hibrido', 'Cargador Electrico 100%'],
                'electricityMeter': ['Menos de 450kW', 'Más de 450kW']
            },//no gas no luz
            marketerProductsToAdd: [],
            liquidationStatuses: [
                {
                    code: 'nl',
                    title: 'No liquidado'
                },
                {
                    code: 'al',
                    title: 'Liquidado agente'
                },
                {
                    code: 'cl',
                    title: 'Liquidado comerc.'
                },
                {
                    code: 'tl',
                    title: 'Total liquidado'
                },
                {
                    code: 'ad',
                    title: 'Decomisionado agente'
                },
                {
                    code: 'md',
                    title: 'Decomisionado comercializadora'
                },
                {
                    code: 'tm',
                    title: 'Total decomisionado'
                },
            ],
            consumptionData: [],
            users: [],
            verificationContracts: [
                {
                    name: 'Alta nueva',
                    code: 'nw'
                },
                {
                    name: 'Cambio de potencia',
                    code: 'pc'
                },
                {
                    name: 'Cambio de titularidad',
                    code: 'tc'
                },
                {
                    name: 'Batería virtual',
                    code: 'vb'
                },
                {
                    name: 'Cambio de comercializadora',
                    code: 'mc'
                },
                {
                    name: 'Renovación',
                    code: 're'
                }
            ],
            fromContracts: false,
            history: {
                visible: false,
                messages: true,
                statuses: true,
                files: true,
            },
            isSeeingDocs: false,
            isEditing: false,
            feeSelected: null,
            versionSelected: 0,
            commissionRanges: null,
            zocoCommissionRanges: null,
            userHierarchy: [],
            naturgyCallStatus: null,
            productArchived: false,
            maxPower: null,
            minPower: null,
            maxConsumption: null,
            minConsumption: null,
            maxConsumptionSecondary: null,
            minConsumptionSecondary: null,
            newMessage: '',
            sendingMessage: false,
            extraCount: 0,
            extraSearchText: '',
            assignedToSelected: null,
            showOwnersBottomOnMobile: false,
            SIPSController: null,
            emptyBreakdown: [],
            showCommissionHierarchy: false,
            showCommissionHierarchyElectricity: false,
            showCommissionHierarchyGas: false,
            showInstallmentCommissionHierarchy: null,
            showDecommissionHierarchy: false,
            showDecommissionHierarchyElectricity: false,
            showDecommissionHierarchyGas: false,
            activationDateStatuses: [
                'a',
                'b',
                'pendiente_de_liquidar',
                'pendiente_de_liquidar_adelantado',
                'pendiente_de_retrocomisin',
                'liquidado',
                'pago_adelantado',
                'baja_anticipada_retrocomisionada',
                '_activo',
                'activo',
                'activado_ac'
            ]
        }
    },
    watch: {
        versions: {
            immediate: true,
            handler(v) {
                if (!Array.isArray(v) || !v.length) return;

                const routeId = this.$route.query.id;
                const fallbackId = this.order?._id;

                const idToMatch = routeId || fallbackId;
                if (!idToMatch) return;

                const idx = v.findIndex(x => String(x._id) === String(idToMatch));
                this.versionSelected = idx >= 0 ? idx : 0;
            }
        },

        order: {
            immediate: true,
            handler(newOrder) {
                if (!newOrder) return;

                this.orderToModify = newOrder;

                if (
                        this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2' &&
                        !this.canManage('contracts.processor') &&
                        !this.orderToModify._id
                    ) {
                        this.orderToModify.newStatus = { code: 'bo' };
                    }

                if (!this.orderToModify.errors) this.orderToModify.errors = {
                    commissions: {subdomain: null, breakdown: []},
                    decommissions: {subdomain: null, breakdown: []},
                };
                if (!this.orderToModify.newStatus) this.orderToModify.newStatus = { code: '' };
                if (!Array.isArray(this.orderToModify.statuses)) this.orderToModify.statuses = [];

                if (this.orderToModify.customerType === undefined) {
                    this.orderToModify.customerType = '';
                }

                if (!this.orderToModify.transferDate)
                    this.orderToModify.transferDate = moment().format('DD/MM/YY');

                this.applyFidelityAccountAutoFields();

                if (this.orderToModify.eventId)
                    this.orderToModify.isReminderOn = true;
            }
        },
        'account.name': {
            handler() {
                this.applyFidelityAccountAutoFields();
            }
        },
        'account.CIF': {
            handler() {
                this.applyFidelityAccountAutoFields();
            }
        },
        isViewingVersion: {
            immediate: true,
            handler(isVersion) {
                if (isVersion) {
                    this.isEditing = false;
                }
            }
        },
        isEditingFromOther: {
            immediate: true,
            handler(isEditing) {
                if (isEditing) {
                    this.isEditing = this.isEditingFromOther;
                }
            }
        },
        'orderToModify.usersIds': {
            async handler(newVal) {
                if (!newVal?.length) return;

                await this.fetchUserHierarchy(newVal[0]);

                const expectedBreakdown = buildEmptyBreakdown(
                    this.userHierarchy,
                    this.orderToModify?.assignedTo === '65cb57489c2c285441086a43'
                );

                const currentBreakdown = this.orderToModify?.commissions?.breakdown;

                // Si la estructura coincide, no tocamos nada (conservamos valores existentes)
                if (this.isSameBreakdownStructure(currentBreakdown, expectedBreakdown)) {
                    return;
                }

                // Si no coincide (o no existe), regeneramos
                this.emptyBreakdown = expectedBreakdown;
                this.orderToModify.commissions = {
                    subdomain: this.orderToModify?.commissions?.subdomain ?? null,
                    breakdown: expectedBreakdown.map(item => ({ ...item }))
                };

                if(this.orderToModify?.productType === 'cd'){
                    this.orderToModify.commissions = {
                        ...this.orderToModify.commissions,
                        electricity: {
                            subdomain: this.orderToModify?.commissions?.subdomain ?? null,
                            breakdown: expectedBreakdown.map(item => ({ ...item }))
                        },
                        gas: {
                            subdomain: this.orderToModify?.commissions?.subdomain ?? null,
                            breakdown: expectedBreakdown.map(item => ({ ...item }))
                        },
                    }
                }
            },
            immediate: true,
            deep: true
        },
        'statusSelected.code': {
            immediate: true,
            handler(newCode) {
                if (this.statusRequiresDecommissions(newCode)) {
                    this.createDecommissionsStructure()
                }
            }
        }
    },

    created() {
        this.orderToModify = this.order;

        if (!this.orderToModify || typeof this.orderToModify !== 'object') {
            this.orderToModify = {};
        }

        this.applyFidelityAccountAutoFields({ forceNew: true });

        this.assignedToSelected = this.orderToModify.assignedTo;
        this.fetchSubdomainCommissionRanges('65cb57489c2c285441086a43');

        if(this.orderToModify?.assignedTo === '65cb57489c2c285441086a43'){
            const subdomainUser = obtainSubdomainUser(this.orderToModify?.usersIds?.[0], this.basicData?.userListComplete)
            this.fetchSubdomainCommissionRanges(subdomainUser?._id)
        }

        //Obtengo las comercializadoras con sus respectivos productos, etc
        if (this.orderToModify.assignedTo && this.orderToModify.assignedTo !== '65cb57489c2c285441086a43')
            this.fetchMarketersAvailableDownSubdomain()
        else {
            this.fetchMarketers()
        }

    },
    mounted() {

        if (!this.orderToModify.errors)
            this.orderToModify.errors = {}

        if (!!this.orderToModify.eventId)
            this.orderToModify.isReminderOn = true;

        if (!this.orderToModify.transferDate && this.orderToModify.processingDate)
            this.orderToModify.transferDate = this.orderToModify.processingDate

        if (this.order && !this.orderToModify.transferDate)
            this.orderToModify.transferDate = moment().format('DD/MM/YY');

        if (this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2') {
            this.applyFidelityAccountAutoFields({ forceNew: true });

            if (!this.orderToModify._id) {
                if (!this.orderToModify.contractEmail && this.account?.email)
                    this.orderToModify.contractEmail = this.account.email;

                if (!this.orderToModify.verificationPhone && this.account?.phone)
                    this.orderToModify.verificationPhone = this.account.phone;
            }
        }

        //si no tiene ningún tipo de estado de liquidación asignado se le asigna por defecto
        if (this.order && !this.orderToModify.liquidationStatus)
            this.orderToModify.liquidationStatus = 'nl';


        //Formateo la fecha si esta como antes
        this.formatOldDate()

        //Ordeno cada uno de los productos
        this.fromContracts = !!this.$route.query.id

        //Si esta to vacío, es decir, se abre nuevo se pone editando
        if (this.areAllFieldsEmpty(this.orderToModify) || !this.orderToModify._id)
            this.isEditing = true;


        //Compruebo para ver si tengo que añadir los terminos de potencia si no los tiene
        if (!!this.orderToModify.verifications && this.orderToModify.verifications.includes('nw')) {

            if (!this.orderToModify.newRegistrationPeriods && this.orderToModify.productType === 'cl')
                this.orderToModify.newRegistrationPeriods = {
                    p1: '',
                    p2: '',
                    p3: '',
                    p4: '',
                    p5: '',
                    p6: ''
                }

        }

        //Añado el userId de la cuenta al contrato
        if (this.orderToModify?.usersIds?.length === 0 && this.account?.usersIds?.length > 0) {
            this.orderToModify.usersIds = [this.account.usersIds[0]];
        }


        //Saco el estado de la llamada de verificación de Naturgy con Twilio si se ha realizado
        if (!!this.orderToModify.naturgyCallSID)
            this.fetchNaturgyVerificationStatus()

        //Si es accountDetails y se esta editando
        if (this.$route.path.includes('accounts') && !this.$route.path.includes('register') && this.ordersEditing && this.ordersEditing.includes(this.order._id))
            this.isEditing = true;
    },
    methods: {
        getOrderDate,
        applyFidelityAccountAutoFields(options = {}) {
            if (!this.account || !this.orderToModify) return;

            const shouldForce = !!options.forceNew && !this.orderToModify._id;
            const shouldSyncName = !!this.basicData?.userSubdomain?.settings?.orderDisabledName
                || (this.isFidelitySubdomain && (shouldForce || this.areFidelityAccountFieldsLocked || !this.orderToModify.name));

            if (shouldSyncName && this.account.name) {
                this.orderToModify.name = this.account.name;
            }

            if (this.isFidelitySubdomain && (shouldForce || this.areFidelityAccountFieldsLocked || !this.orderToModify.accountCIF) && this.account.CIF) {
                this.orderToModify.accountCIF = this.account.CIF;
            }
        },
        canValidateDraft() {
            if (
                this.basicData?.userSubdomain?._id !== '6909faa9232c09035a03f3b2' ||
                this.canManage('contracts.processor')
            ) return false;

            const code = this.orderToModify?.newStatus?.code || this.statusSelected?.code;


            return ['bo', 'auditoria_ko', 'incidencia_colaborador'].includes(code);
        },
        validateOrder() {
            this.createOrder(true);
        },
        applyLow() {
            if (!this.lowForm.lowDate) return

    this.orderToModify.lowDate = this.lowForm.lowDate
    this.orderToModify.status = 'pendiente_de_retrocomisin'

            const applyFactor = (value, factor) => Math.round(value * factor * 100) / 100
            let baseElectricity, baseGas, baseSubdomain

    if (!this.lowForm.hasRetro || !this.lowForm.retroValue) {

                //Restablezco las decomisiones
                this.createDecommissionsStructure();

        this.showLowModal = false
        return
    }

    if (this.lowForm.retroType === 'percent') {

                const percent = this.parseStringToNumber(this.lowForm.retroValue) / 100

                baseElectricity = applyFactor(this.orderToModify.commissions.electricity?.subdomain || 0, percent)
                baseGas = applyFactor(this.orderToModify.commissions.gas?.subdomain || 0, percent)
                baseSubdomain = applyFactor(this.orderToModify.commissions.subdomain || 0, percent)
            } else {

                baseSubdomain = Math.round(this.parseStringToNumber(this.lowForm.retroValue) * 100) / 100
            }

            //En caso de dual y retrocomisión fija, solo tenemos un valor, por lo que lo uso como el total
            if (this.orderToModify.productType === 'cd' && this.lowForm.retroType === 'percent') {
                this.calcCommission(baseElectricity, 'electricity', 'decommissions')
                this.calcCommission(baseGas, 'gas', 'decommissions')
            } else {
                this.calcCommission(baseSubdomain, null, 'decommissions')
            }

            this.showLowModal = false
        },
        async handleSendSms() {

            try {

                const isFidelity = this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2';
                let phone = isFidelity
                    ? (this.orderToModify?.verificationPhone || this.account?.phone)
                    : this.account?.phone;

                const phoneSource = isFidelity && this.orderToModify?.verificationPhone
                    ? 'del contrato'
                    : 'de la cuenta';

                console.log("Teléfono disponible:", phone);

                if (phone) {

                    const result = await Swal.fire({
                        title: 'Enviar SMS',
                        html: `<p>¿Enviar SMS al teléfono ${phoneSource}?</p><strong>${phone}</strong>`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, enviar',
                        cancelButtonText: 'Usar otro número'
                    });

                    if (!result.isConfirmed) {

                        const { value: newPhone } = await Swal.fire({
                            title: 'Introduce otro número',
                            input: 'text',
                            inputPlaceholder: 'Ej: 600123123',
                            showCancelButton: true,
                            confirmButtonText: 'Enviar'
                        });

                        if (!newPhone) return;

                        phone = newPhone;

                        if (isFidelity) {
                            this.orderToModify.verificationPhone = newPhone;
                        }
                    }

                } else {

                    const { value: newPhone } = await Swal.fire({
                        title: 'La cuenta no tiene teléfono',
                        input: 'text',
                        inputPlaceholder: 'Introduce un número',
                        inputValidator: (value) => {
                            if (!value) return 'Debes introducir un número';
                        }
                    });

                    if (!newPhone) return;

                    phone = newPhone;

                    if (isFidelity) {
                        this.orderToModify.verificationPhone = newPhone;
                    }
                }

                console.log("Enviando SMS a:", phone);

                Swal.fire({
                    title: 'Enviando SMS...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                await axios.post('/api/orders/twilio/send-sms', {
                    phone: phone,
                    order: this.orderToModify
                });

                Swal.fire({
                    title: 'SMS enviado',
                    icon: 'success'
                });

            } catch (error) {

                console.error("Error SMS:", error);

                Swal.fire({
                    title: 'Error enviando SMS',
                    text: error.response?.data?.error || 'Error inesperado',
                    icon: 'error'
                });
            }
        },
        canEditOrder() {
            return !this.isReadOnly && this.versionSelected === 0;
        },
        calculateRenewalDate() {
            // Si el recordatorio está desactivado, limpiar y salir
            if (!this.orderToModify.isReminderOn) {
                delete this.orderToModify.renewalDate;
                return;
            }

            const actStr = this.orderToModify.activationDate;
            if (!actStr) {
                // Sin fecha de activación no se puede calcular
                delete this.orderToModify.renewalDate;
                return;
            }

            // Parsear fecha local YYYY-MM-DD
            const [y, m, d] = actStr.split('-').map(Number);
            if (!y || !m || !d) return;
            const activationDate = new Date(y, m - 1, d);

            // Helper para sumar meses sin overflow


            const fmt = (d) => {
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            };

            // =====================================================
            // CASO 1: SETTINGS NO ACTIVO → MODO MANUAL
            // =====================================================
            if (!this.renewalReminderByMarketer) {
                this.calculateRenewalDateByUser(activationDate);
                return;
            }

            // =====================================================
            // CASO 2: SETTINGS ACTIVO → MODO COMERCIALIZADORA
            // =====================================================
            const marketerName = this.orderToModify.marketer;
            if (!marketerName) {
                // Sin comercializadora → modo manual
                this.calculateRenewalDateByUser(activationDate);
                return;
            }

            const marketer = this.marketers.find(m => m.name === marketerName);
            if (!marketer) {
                this.calculateRenewalDateByUser(activationDate);
                return;
            }

            const data = this.getProductData();
            if (!data) {
                this.calculateRenewalDateByUser(activationDate);
                return;
            }

            const feeInfo = data.feeInfo;

            const isPyme = feeInfo?.type?.pyme;
            const isRes  = feeInfo?.type?.residencial;

            let days = null;

            if (isPyme) {
                days = Number(marketer.surplus?.rCommissionPyme);
            } else if (isRes) {
                days = Number(marketer.surplus?.rCommissionRes);
            }

            if (!days || isNaN(days)) {
                this.calculateRenewalDateByUser(activationDate);
                return;
            }

            if (!days) {
                this.calculateRenewalDateByUser(activationDate);
                return;
            }

            const reminderDate = new Date(activationDate);
            reminderDate.setDate(reminderDate.getDate() + days);

            this.orderToModify.renewalDate = fmt(reminderDate);
        },
        calculateRenewalDateByUser(activationDate) {
            const opt = this.orderToModify.renewalOption;
            if (!opt) {
                delete this.orderToModify.renewalDate;
                return;
            }

            const addMonthsClamped = (date, months) => {
                const day = date.getDate();
                const y = date.getFullYear();
                const m = date.getMonth();
                const lastOfTarget = new Date(y, m + months + 1, 0).getDate();
                const safeDay = Math.min(day, lastOfTarget);
                return new Date(y, m + months, safeDay);
            };

            const fmt = (d) => {
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            };

            let base;
            switch (opt) {
                case '15d': {
                    const plus1y = addMonthsClamped(activationDate, 12);
                    base = new Date(plus1y.getFullYear(), plus1y.getMonth(), plus1y.getDate() - 15);
                    break;
                }
                case '1m':  base = addMonthsClamped(activationDate, 1);  break;
                case '4m':  base = addMonthsClamped(activationDate, 4);  break;
                case '6m':  base = addMonthsClamped(activationDate, 6);  break;
                case '11m': base = addMonthsClamped(activationDate, 11); break;
                default: return;
            }

            this.orderToModify.renewalDate = fmt(base);
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
        getPrettyDate(date, format = null) {
            return format ? moment(date,'DD/MM/YY').format('DD/MM/YYYY') : moment(date).format('DD/MM/YY');
        },
        getPrettyDateHistory(date) {
            let dateNow = new Date(date);

            let day = String(dateNow.getDate()).padStart(2, '0');          // Día con dos dígitos
            let month = String(dateNow.getMonth() + 1).padStart(2, '0');   // Mes con dos dígitos
            let year = dateNow.getFullYear();

            let hours = String(dateNow.getHours()).padStart(2, '0');       // Horas con dos dígitos
            let minutes = String(dateNow.getMinutes()).padStart(2, '0');   // Minutos con dos dígitos
            let seconds = String(dateNow.getSeconds()).padStart(2, '0');   // Segundos con dos dígitos

            return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
        },
        checkIBAN() {
            if (!this.orderToModify.IBAN || this.orderToModify.IBAN.trim() === '') {
                return;
            }

            this.orderToModify.IBAN = this.normalizeControlField(this.orderToModify.IBAN);

            const validation = this.validateIban(this.orderToModify.IBAN);

            if (!validation.valid) {
                this.orderToModify.errors.IBAN = validation.message;
                return;
            }

            delete this.orderToModify.errors.IBAN;

            // Excepción: ES0000 no se formatea como ES00 00
            if (this.orderToModify.IBAN === 'ES0000') {
                return;
            }

            this.orderToModify.IBAN = this.orderToModify.IBAN
                .match(/.{1,4}/g)
                .join(' ');
        },

        normalizeControlField(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/[\s.-]/g, '');
        },

        validateIban(value) {
            const iban = this.normalizeControlField(value);

            if (!iban) {
                return {
                    valid: true,
                    message: null
                };
            }

            // Excepción de negocio: debe seguir funcionando
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

            // Excepción de negocio
            if (cups === 'ES0000') {
                return {
                    valid: true,
                    skipped: true,
                    message: null
                };
            }

            if (!cups.startsWith('ES')) {
                return {
                    valid: false,
                    message: 'El CUPS debe empezar por ES.'
                };
            }

            if (cups.length !== 20 && cups.length !== 22) {
                return {
                    valid: false,
                    message: 'El CUPS debe tener 20 o 22 caracteres.'
                };
            }

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

        checkCUPS(field = 'CUPS') {
            const settings = this.basicData?.userSubdomain?.settings || {};

            const shouldValidateCups =
                typeof settings.orderCupsValidation === 'undefined' ||
                settings.orderCupsValidation !== false;

            if (!shouldValidateCups) {
                if (this.orderToModify?.errors?.[field]) {
                    delete this.orderToModify.errors[field];
                }

                return;
            }

            if (!this.orderToModify[field] || this.orderToModify[field].trim() === '') {
                return;
            }

            this.orderToModify[field] = this.normalizeControlField(this.orderToModify[field]);

            if (this.orderToModify[field] === 'ES0000') {
                delete this.orderToModify.errors[field];
                return;
            }

            const validation = this.validateCups(this.orderToModify[field]);

            if (!validation.valid) {
                this.orderToModify.errors[field] = validation.message;
                return;
            }

            delete this.orderToModify.errors[field];
        },

        async fetchMarketers() {

            await axios.get('/api/marketers', {
                params: { assignContractTo: this.orderToModify.assignedTo }
            })
                .then((res) => {
                    this.marketers = res.data.marketers;
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async fetchMarketersAvailableDownSubdomain() {

            await axios.get('/api/marketers')
                .then((res) => {
                    //Saco el usuario al que se asigna el contrato para ver las comercializadoras disponibles
                    let userAssigned = this.basicData.userListComplete.find(user => user._id === this.orderToModify.assignedTo);

                    //Filtro las comercializadoras que tiene activado con el que estoy tramitando ( compruebo si la tiene el usuario )
                    this.marketers = res.data.marketers.filter(marketer => userAssigned.marketers.includes(marketer._id))
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async checkAPIConsumption() {
            if(this.SIPSController){
                this.SIPSController.abort();
            }

            const controller = new AbortController()
            this.SIPSController = controller;

            if (!this.orderToModify.CUPS) {
                return;
            }

            if (this.orderToModify.CUPS === 'ES0000') {
                delete this.orderToModify.errors.CUPS;
                this.orderToModify.consumption = '';
                this.orderToModify.consumptionData = null;
                this.orderToModify.hiredPotency = '';
                return;
            }

            // Ahora permitimos CUPS de 20 o 22 caracteres
            if (this.orderToModify.CUPS.length > 22) {
                this.orderToModify.CUPS = this.orderToModify.CUPS.slice(0, 22);
            }

            // Si no empieza por ES, no hacemos nada más.
            // Evita llamadas/API/lógica pesada mientras el usuario escribe algo inválido.
            if (!this.orderToModify.CUPS.startsWith('ES')) {
                return;
            }

            // Si todavía no tiene mínimo 20 caracteres, tampoco hacemos nada.
            if (this.orderToModify.CUPS.length < 20) {
                return;
            }


            if (this.orderToModify.CUPS.length > 20) //&& this.orderToModify.CUPS.endsWith('0F') || this.orderToModify.CUPS.endsWith('RZ')
                this.orderToModify.CUPS = this.orderToModify.CUPS.slice(0, 20);

            if (this.orderToModify.CUPS.length === 20 && (!this.orderToModify.verifications || (!!this.orderToModify.verifications && !this.orderToModify.verifications.includes('nw')))) {
                let requestUrl;
                if (this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cd') {
                    requestUrl = '/api/orders/getAPIConsumption';
                } else if (this.orderToModify.productType === 'cg') {
                    requestUrl = '/api/sips/getGasConsumption';
                }


                this.orderToModify.consumption = null;
                this.orderToModify.consumptionData = null;
                this.orderToModify.hiredPotency = null;

                axios.get(requestUrl, {
                    params: { CUPS: this.orderToModify.CUPS },
                    signal: controller.signal,
                })
                    .then((res) => {
                        this.consumptionData = res.data.consumptionData

                        this.orderToModify.consumptionData = this.consumptionData;

                        //asigno los datos de consumo al contrato
                        this.orderToModify.consumption = Math.floor(this.consumptionData['consumption'].reduce((acc, value) => acc + value, 0));

                        //cojo la potencia contratada más alta y la asigno al contrato
                        this.orderToModify.hiredPotency = Math.max(...res.data.consumptionData.hiredPotency)

                        if (this.orderToModify.hiredPotency && (this.maxPower || this.minPower)) {
                            if (!this.maxPower) this.maxPower = Infinity;
                            if (!this.minPower) this.minPower = 0;
                            if (!(this.orderToModify.hiredPotency <= this.maxPower && this.orderToModify.hiredPotency >= this.minPower)) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error de potencia contratada',
                                    text: 'El CUPS introducido no cumple con la potencia contratada en el contrato. Por favor, revise el CUPS e inténtelo de nuevo.'
                                });
                            }
                        }

                        if (this.orderToModify.consumption && (this.maxConsumption || this.minConsumption)) {
                            !this.maxConsumption ? this.maxConsumption = Infinity : null;
                            !this.minConsumption ? this.minConsumption = 0 : null;
                            if (!(this.orderToModify.consumption >= this.minConsumption && this.orderToModify.consumption <= this.maxConsumption)) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error de consumo anual',
                                    text: 'El CUPS introducido no cumple con el consumo anual en el contrato. Por favor, revise el CUPS e inténtelo de nuevo.'
                                });
                            }
                        }

                        this.orderToModify.hiredPotency = this.orderToModify.hiredPotency.toFixed(3)


                        /*if (this.orderToModify.product && this.orderToModify.product !== 'Indexado') { //ASIGNO DIRECTAMENTE EL CONSUMO Y POTENCIA

                            //asigno los datos de consumo al contrato
                            this.orderToModify.consumption = Math.floor(this.consumptionData['consumption'][0] + this.consumptionData['consumption'][1] + this.consumptionData['consumption'][2] + this.consumptionData['consumption'][3] + this.consumptionData['consumption'][4] + this.consumptionData['consumption'][5]);

                            //cojo la potencia contratada más alta y la asigno al contrato
                            this.orderToModify.hiredPotency = Math.max(...res.data.consumptionData.hiredPotency)

                            this.orderToModify.hiredPotency = this.orderToModify.hiredPotency.toFixed(3)

                        }else{ //EL CONSUMO Y POTENCIA SE SACA CALCULANDOLO CON LOS FEES Y EL PORCENTAJE


                            // CONSUMO/ENERGÍA
                            if (this.orderToModify.consumptionFee && this.orderToModify.marketerPercentage) {

                                let yearConsumption = Math.floor(this.consumptionData['consumption'][0] + this.consumptionData['consumption'][1] + this.consumptionData['consumption'][2] + this.consumptionData['consumption'][3] + this.consumptionData['consumption'][4] + this.consumptionData['consumption'][5]);

                                //Multiplico consumo sips por fee de consumo
                                this.orderToModify.consumption = yearConsumption * this.orderToModify.consumptionFee;

                                //Saco el porcentaje
                                this.orderToModify.consumption = this.orderToModify.consumption * (this.orderToModify.marketerPercentage/100);

                            }


                            // POTENCIA
                            if (this.orderToModify.potencyFee && this.orderToModify.marketerPercentage) {

                                let totalPotency = 0;

                                //Multiplica potencia de cada periodo por fee
                                this.consumptionData.hiredPotency.forEach((potencyNow) => {
                                    totalPotency += (potencyNow * this.orderToModify.potencyFee)
                                })

                                this.orderToModify.hiredPotency = totalPotency * (this.orderToModify.marketerPercentage/100)
                            }

                        }*/
                    })
                    .catch(err => console.log(err))
                    .finally(() => {
                        if (this.SIPSController === controller) {
                            this.SIPSController = null
                        }

                        this.calcCommission()
                    })
            }
        },
        async fetchSubdomainCommissionRanges(id) {
            await axios.get(`/api/enterprises/${id}`).then((res) => {
                if(id === '65cb57489c2c285441086a43'){ //Zoco
                    this.zocoCommissionRanges = res.data.data.commissionRanges;
                }else{
                    this.commissionRanges = res.data.data.commissionRanges;
                }


            })
        },
        async checkAPIConsumptionSecondary() {

            if (!this.orderToModify.CUPSSecondary) {
                return;
            }

            if (this.orderToModify.CUPSSecondary === 'ES0000') {
                delete this.orderToModify.errors.CUPSSecondary;
                this.orderToModify.consumptionSecondary = '';
                this.orderToModify.consumptionDataSecondary = null;
                return;
            }

            // Ahora permitimos CUPS de 20 o 22 caracteres
            if (this.orderToModify.CUPSSecondary.length > 22) {
                this.orderToModify.CUPSSecondary = this.orderToModify.CUPSSecondary.slice(0, 22);
            }

            // Si no empieza por ES, no hacemos nada más
            if (!this.orderToModify.CUPSSecondary.startsWith('ES')) {
                return;
            }

            // Si todavía no tiene mínimo 20 caracteres, tampoco hacemos nada
            if (this.orderToModify.CUPSSecondary.length < 20) {
                return;
            }



            if (this.orderToModify.CUPSSecondary.length > 20) //&& this.orderToModify.CUPS.endsWith('0F') || this.orderToModify.CUPS.endsWith('RZ')
                this.orderToModify.CUPSSecondary = this.orderToModify.CUPSSecondary.slice(0, 20);

            if (this.orderToModify.CUPSSecondary.length === 20 && (!this.orderToModify.verifications || (!!this.orderToModify.verifications && !this.orderToModify.verifications.includes('nw')))) {

                this.orderToModify.consumptionSecondary = null;
                this.orderToModify.consumptionDataSecondary = null;

                axios.get('/api/sips/getGasConsumption', {
                    params: { CUPS: this.orderToModify.CUPSSecondary }
                })
                    .then((res) => {
                        this.consumptionDataSecondary = res.data.consumptionData

                        this.orderToModify.consumptionDataSecondary = this.consumptionData;

                        //asigno los datos de consumo al contrato
                        this.orderToModify.consumptionSecondary = Math.floor(this.consumptionDataSecondary['consumption'].reduce((acc, value) => acc + value, 0));

                        if (this.orderToModify.consumptionSecondary && (this.maxConsumptionSecondary || this.minConsumptionSecondary)) {
                            !this.maxConsumptionSecondary ? this.maxConsumptionSecondary = Infinity : null;
                            !this.minConsumptionSecondary ? this.minConsumptionSecondary = 0 : null;
                            if (!(this.orderToModify.consumptionSecondary >= this.minConsumptionSecondary && this.orderToModify.consumptionSecondary <= this.maxConsumptionSecondary)) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error de consumo anual',
                                    text: 'El CUPS introducido no cumple con el consumo anual en el contrato. Por favor, revise el CUPS e inténtelo de nuevo.'
                                });
                            }
                        }
                    })
                    .catch(err => console.log(err))
                    .finally(() => {
                        this.calcCommission()
                    })
            }
        },
        /*calculateConsumptionAndPotency(){

            // CONSUMO/ENERGÍA
            if (this.orderToModify.consumptionFee && this.orderToModify.marketerPercentage && this.consumptionData['consumption'].length > 0) {

                let yearConsumption = Math.floor(this.consumptionData['consumption'][0] + this.consumptionData['consumption'][1] + this.consumptionData['consumption'][2] + this.consumptionData['consumption'][3] + this.consumptionData['consumption'][4] + this.consumptionData['consumption'][5]);

                //Multiplico consumo sips por fee de consumo
                this.orderToModify.consumption = yearConsumption * this.orderToModify.consumptionFee;

                //Saco el porcentaje
                this.orderToModify.consumption = this.orderToModify.consumption * (this.orderToModify.marketerPercentage/100);

            }


            // POTENCIA
            if (this.orderToModify.potencyFee && this.orderToModify.marketerPercentage && this.consumptionData.hiredPotency.length > 0) {

                let totalPotency = 0;

                //Multiplica potencia de cada periodo por fee
                this.consumptionData.hiredPotency.forEach((potencyNow) => {
                    totalPotency += (potencyNow * this.orderToModify.potencyFee)
                })

                this.orderToModify.hiredPotency = totalPotency * (this.orderToModify.marketerPercentage/100)
            }

        },*/
        addElement(data) {

            let type = data.type;
            let value = data.value;

            axios.post(`/api/select`, { type: type, value: value })
                .then((res) => {
                    //Añado al array para tenerlo en cliente
                    this.selectValues[type].push(value)

                    //Selecciono el valor y borro
                    if (data.type === 'orderSources')
                        this.orderToModify.source = value
                    else
                        this.orderToModify.product = value
                })
                .catch((err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ya existe',
                        text: 'El elemento que estas intentando crear ya existe'
                    })
                })
        },
        delElement(data) {

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

                                if (data.type === 'orderSources' && this.orderToModify.source === value) this.orderToModify.source = ''
                                else if (this.orderToModify.product === value) this.orderToModify.product = ''


                            })
                            .catch((err) => {
                                console.log(err)
                            })
                    }
                })
            }
        },
        selectElement(data) {
            let value = data.value;

            if (data.type === 'orderSources')
                this.orderToModify.source = value
            else
                this.orderToModify.product = value
        },
        delDoc(ind) {
            //Compruebo si hay que borrarlo dela bbdd( como uso el mismo componente para crear y editar puede o no que se haya guardado)
            this.orderToModify.docs.splice(ind, 1);
        },
        delContract(contractInd) {
            this.$emit('delContract', contractInd);
        },
        openDialog() {
            $('#docFile' + this.docInd).click();
        },
        addDoc() {

            let docInfo = {
                title: '',
                defaultTitle: '',
                value: '', //Aqui se va a guardar el nombre del archivo
                fileValue: '', //Aqui se va a meter el archivo en sí
                id: 'tmp-' + crypto.randomUUID(), //le creo un id temporal para luego sacarlo en el controlador
                errors: {}
            }

            this.orderToModify.docs.push(docInfo)
        },
        pickupImage() {

            let input = $('#docFile' + this.docInd);
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

                    //Añado el doc al contrato
                    let docInfo = {
                        title: '',
                        defaultTitle: file.name,
                        value: '', //Aqui se va a guardar el nombre del archivo
                        fileValue: file, //Aqui se va a meter el archivo en sí
                        icon: getIconForFileType(fileType),
                        id: 'tmp-' + crypto.randomUUID(),
                        errors: {}
                    }

                    //Meto el doc en el pedido
                    this.orderToModify.docs.push(docInfo);

                    //Si estamos en cuentas mando directamente los docs a la cuenta ( había error no los cogía bien )
                    if (this.$route.path.includes('accounts') && !this.$route.path.includes('register'))
                        this.$emit('catchOrderFiles', docInfo, this.orderToModify._id)
                }
            }
        },
        toggleCancelOrder(){
            this.resetOrder()
        },
        closeWindow() {
            this.$emit('closeWindow', this.orderToModify)
        },
        resetOrder() {
            this.isEditing = false;

            if (this.orderToModify?._id) {
                const snap = this.originalOrders.find(o => String(o._id) === String(this.order._id));
                this.orderToModify = snap ? JSON.parse(JSON.stringify(snap)) : {};
            } else {
                this.$emit('deleteOrder');
                this.closeWindow()
            }
        },
        async createRenewalCopy() {
            if (!this.canShowRenewalCopyButton) return;

            const res = await Swal.fire({
                icon: 'warning',
                title: '¿Estás seguro?',
                text: '¿Seguro que quieres renovar el contrato?',
                showCancelButton: true,
                confirmButtonText: 'Sí, renovar',
                cancelButtonText: 'Cancelar'
            });

            if (!res.isConfirmed) return;

            try {
                const response = await axios.put(`/api/orders/changeStatus`, {
                    id: this.orderToModify._id,
                    status: 'renovado'
                });

                console.log(response.data);
            } catch (err) {
                console.log(err);
                return;
            }

            this.orderToModify.statuses.push({
                code: 'renovado',
                date: new Date().toISOString(),
                creator: this.basicData.userLogged._id
            });

            this.orderToModify.newStatus.code = 'renovado';

            const copiedOrder = JSON.parse(JSON.stringify(this.orderToModify || {}));
            const currentName = String(copiedOrder.name || '').trim();

            delete copiedOrder._id;
            delete copiedOrder._isTemp;
            delete copiedOrder.pricesProduct;
            delete copiedOrder.lastStatus;
            delete copiedOrder.createdAt;

            copiedOrder.name = currentName ? `${currentName} - copia` : ' - copia';

            copiedOrder.newStatus = { code: 'bo' };
            copiedOrder.liquidationStatus = 'nl';
            copiedOrder.statuses = [];
            copiedOrder.verifications = [];
            copiedOrder.errors = {};

            copiedOrder.productType = 'cl';
            copiedOrder.marketer = '';
            copiedOrder.fee = '';
            copiedOrder.product = '';
            copiedOrder.extras = [];
            copiedOrder.transferDate = '';
            copiedOrder.processingDate = '';
            copiedOrder.activationDate = '';
            copiedOrder.lowDate = '';
            // copiedOrder.CUPS = '';
            copiedOrder.salesCommision = '';
            copiedOrder.asercordCommision = '';
            copiedOrder.salesDecommision = '';
            copiedOrder.asercordDecommision = '';
            copiedOrder.salesCommisionTotal = '';
            copiedOrder.asercordCommisionTotal = '';
            copiedOrder.salesCommisionSecondary = '';
            copiedOrder.asercordCommisionSecondary = '';
            copiedOrder.salesDecommisionSecondary = '';
            copiedOrder.asercordDecommisionSecondary = '';
            copiedOrder.salesDecommisionTotal = '';
            copiedOrder.asercordDecommisionTotal = '';

            if (!Array.isArray(copiedOrder.docs)) {
                copiedOrder.docs = [];
            }

            this.$emit('renewalOrder', copiedOrder);
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
        createOrder(validate = false) {
            if(this.SIPSController){
                this.SIPSController.abort();
                this.SIPSController = null;
            }

            if (!this.renewalReminderByMarketer) {
                if (!this.orderToModify.isReminderOn && !this.orderToModify.renewalOption) {
                    this.orderToModify.isReminderOn = true;
                    this.orderToModify.reminderChanged = true;
                    this.orderToModify.renewalOption = '11m';
                }
            }
            else {
                if (this.orderToModify.isReminderOn === undefined) {
                    this.orderToModify.isReminderOn = true;
                    this.orderToModify.reminderChanged = true;
                }
            }

            // Recalcular siempre antes de validar
            this.calculateRenewalDate();



            // Sólo obligamos a seleccionar opción si NO lo gestiona la comercializadora
            if (this.orderToModify.isReminderOn && !this.renewalReminderByMarketer) {
                if (this.orderToModify.renewalOption === undefined) {
                    this.orderToModify.errors.renewalOption = 'Debes seleccionar una opción de renovación';
                    Swal.fire({
                        icon: 'error',
                        title: 'Opción de renovación no seleccionada',
                        text: 'Debes seleccionar una opción de renovación',
                        confirmButtonColor: 'red'
                    });
                    return;
                }
            }

            this.orderToModify.lastUpdate = moment().format('YYYY-MM-DD HH:mm:ss');
            const isKuvi = this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2';
            const hasNoProcessorPerm = !this.canManage('contracts.processor');

            const lastStatusCode = this.orderToModify.newStatus?.code || this.statusSelected?.code;

            if (validate) {
                if (lastStatusCode === 'auditoria_ko') {
                    this.orderToModify.newStatus.code = 'auditoria_pte_revisar';
                }
                else if (lastStatusCode === 'incidencia_colaborador') {
                    this.orderToModify.newStatus.code = 'incidencia_pte_revisar';
                }
                else if (lastStatusCode === 'bo' && isKuvi && hasNoProcessorPerm) {
                    this.orderToModify.newStatus.code = 'pendiente_de_gestionar';
                }
            }
            this.$emit('createOrder', {order: this.orderToModify, validate: validate});
         },
        updateOrder() {
            this.$emit('updateOrder')
        },
        changeProductType() {
            delete this.orderToModify.errors.productType;


            if (this.orderToModify.productType === 'sp') {
                this.orderToModify.marketer = '';
                this.orderToModify.fee = '';
                this.orderToModify.product = '';
                this.orderToModify.CUPS = '';
                // si tienes otros campos (consumo, fechas…) también:
                delete this.orderToModify.consumptionData;
                this.orderToModify.consumption = '';
                this.orderToModify.hiredPotency = '';
                // y salimos
                return;
            }

            this.orderToModify.product = ''
            this.orderToModify.marketer = ''
            this.orderToModify.fee = ''
            if (this.orderToModify.extras) delete this.orderToModify.extras


            //Borro los fee de energía
            if (!!this.orderToModify.energyFees) {
                this.orderToModify.energyFees.energyFee0 = ''
                this.orderToModify.energyFees.energyFee1 = ''
                this.orderToModify.energyFees.energyFee2 = ''
                this.orderToModify.energyFees.energyFee3 = ''
                this.orderToModify.energyFees.energyFee4 = ''
                this.orderToModify.energyFees.energyFee5 = ''
            }


            //Borro los fee de potencia
            if (!!this.orderToModify.potencyFees) {
                this.orderToModify.potencyFees.potencyFee0 = ''
                this.orderToModify.potencyFees.potencyFee1 = ''
                this.orderToModify.potencyFees.potencyFee2 = ''
                this.orderToModify.potencyFees.potencyFee3 = ''
                this.orderToModify.potencyFees.potencyFee4 = ''
                this.orderToModify.potencyFees.potencyFee5 = ''
            }

            //Compruebo si necesita que meta los periodos y los precios
            if (!!this.orderToModify.verifications && this.orderToModify.verifications.includes('nw')) {

                if (!this.orderToModify.newRegistrationPeriods && this.orderToModify.productType === 'cl')
                    this.orderToModify.newRegistrationPeriods = {
                        p1: '',
                        p2: '',
                        p3: '',
                        p4: '',
                        p5: '',
                        p6: ''
                    }

                if (!this.orderToModify.newRegistrationPrices && this.orderToModify.productType === 'cg')
                    this.orderToModify.newRegistrationPrices = {
                        fixedPrice: '',
                        variablePrice: '',
                    }

            }


            //Compruebo si es dual
            if (this.orderToModify.productType === 'cd') {
                this.orderToModify.feeSecondary = '';
                this.orderToModify.productSecondary = '';
                this.orderToModify.CUPSSecondary = '';
                this.orderToModify.consumptionSecondary = '';
                this.orderToModify.commissions.electricity = {subdomain: null, breakdown: this.emptyBreakdown.map(item => ({ ...item }))};
                this.orderToModify.commissions.gas = {subdomain: null, breakdown: this.emptyBreakdown.map(item => ({ ...item }))};
            }
        },
        changeMarketer() {
            delete this.orderToModify.errors.marketer;

            //Se borra toda la ramificación
            this.orderToModify.fee = ''
            this.orderToModify.product = ''
            if (this.orderToModify.extras) delete this.orderToModify.extras


            //Si esta en estado 'Incidencia' se cambiara el estado a 'Pendiente'
            if (this.orderToModify.newStatus && this.orderToModify.newStatus.code !== '') { // si es newStatus, checkeo ahí

                if (this.orderToModify.newStatus.code === 'i' || this.orderToModify.newStatus.code === 's')
                    this.orderToModify.newStatus.code = 'p'

            } else {
                let lastStatus = this.orderToModify.statuses.reduce((latest, current) => {
                    return new Date(current.date) > new Date(latest.date) ? current : latest;
                });

                if (lastStatus.code === 'i' || this.orderToModify.newStatus.code === 's')
                    this.orderToModify.newStatus.code = 'p'
            }


            //Borro los fee de energía
            if (!!this.orderToModify.energyFees) {
                this.orderToModify.energyFees.energyFee0 = ''
                this.orderToModify.energyFees.energyFee1 = ''
                this.orderToModify.energyFees.energyFee2 = ''
                this.orderToModify.energyFees.energyFee3 = ''
                this.orderToModify.energyFees.energyFee4 = ''
                this.orderToModify.energyFees.energyFee5 = ''
            }


            //Borro los fee de potencia
            if (!!this.orderToModify.potencyFees) {
                this.orderToModify.potencyFees.potencyFee0 = ''
                this.orderToModify.potencyFees.potencyFee1 = ''
                this.orderToModify.potencyFees.potencyFee2 = ''
                this.orderToModify.potencyFees.potencyFee3 = ''
                this.orderToModify.potencyFees.potencyFee4 = ''
                this.orderToModify.potencyFees.potencyFee5 = ''
            }

            if (!!this.orderToModify.marketerOrderNumber)
                delete this.orderToModify.marketerOrderNumber

            this.checkProductArchived("marketer")
        },
        changeFee() {
            delete this.orderToModify.errors.fee;

            //Se borra toda la ramificación
            this.orderToModify.product = ''
            if (this.orderToModify.extras) delete this.orderToModify.extras


            //Borro los fee de energía
            if (!!this.orderToModify.energyFees) {
                this.orderToModify.energyFees.energyFee0 = ''
                this.orderToModify.energyFees.energyFee1 = ''
                this.orderToModify.energyFees.energyFee2 = ''
                this.orderToModify.energyFees.energyFee3 = ''
                this.orderToModify.energyFees.energyFee4 = ''
                this.orderToModify.energyFees.energyFee5 = ''
            }


            //Borro los fee de potencia
            if (!!this.orderToModify.potencyFees) {
                this.orderToModify.potencyFees.potencyFee0 = ''
                this.orderToModify.potencyFees.potencyFee1 = ''
                this.orderToModify.potencyFees.potencyFee2 = ''
                this.orderToModify.potencyFees.potencyFee3 = ''
                this.orderToModify.potencyFees.potencyFee4 = ''
                this.orderToModify.potencyFees.potencyFee5 = ''
            }

            this.checkProductArchived("fee")
        },
        changeFeeSecondary() {
            delete this.orderToModify.errors.feeSecondary;

            //Se borra toda la ramificación
            this.orderToModify.productSecondary = ''
            if (this.orderToModify.extras) delete this.orderToModify.extras



            //Borro los fee de energía
            if (!!this.orderToModify.energyFeesSecondary) {
                this.orderToModify.energyFees.energyFee0Secondary = ''
                this.orderToModify.energyFees.energyFee1Secondary = ''
                this.orderToModify.energyFees.energyFee2Secondary = ''
                this.orderToModify.energyFees.energyFee3Secondary = ''
                this.orderToModify.energyFees.energyFee4Secondary = ''
                this.orderToModify.energyFees.energyFee5Secondary = ''
            }


            //Borro los fee de potencia
            if (!!this.orderToModify.potencyFeesSecondary) {
                this.orderToModify.potencyFees.potencyFee0Secondary = ''
                this.orderToModify.potencyFees.potencyFee1Secondary = ''
                this.orderToModify.potencyFees.potencyFee2Secondary = ''
                this.orderToModify.potencyFees.potencyFee3Secondary = ''
                this.orderToModify.potencyFees.potencyFee4Secondary = ''
                this.orderToModify.potencyFees.potencyFee5Secondary = ''
            }

            //this.checkProductArchived("fee")
        },
        changeOrderProduct() {

            //Si esta en estado 'Incidencia' se cambiara el estado a 'Pendiente'
            if (this.orderToModify.newStatus && this.orderToModify.newStatus.code !== '') { // si es newStatus, checkeo ahí

                if (this.orderToModify.newStatus.code === 'i' || this.orderToModify.newStatus.code === 's')
                    this.orderToModify.newStatus.code = 'p'

            } else {
                let lastStatus = this.orderToModify.statuses.reduce((latest, current) => {
                    return new Date(current.date) > new Date(latest.date) ? current : latest;
                });

                if (lastStatus.code === 'i' || this.orderToModify.newStatus.code === 's')
                    this.orderToModify.newStatus.code = 'p'
            }

            if (this.productSelected?.priceType === 'variable' || this.productSelected?.priceType === 'indexed') {
                if (this.orderToModify.productType === 'cl'){
                    if (!this.orderToModify.energyFees)
                        this.orderToModify.energyFees = {
                            'energyFee0': '',
                            'energyFee1': '',
                            'energyFee2': '',
                            'energyFee3': '',
                            'energyFee4': '',
                            'energyFee5': '',
                        }

                    if (!this.orderToModify.potencyFees)
                        this.orderToModify.potencyFees = {
                            'potencyFee0': '',
                            'potencyFee1': '',
                            'potencyFee2': '',
                            'potencyFee3': '',
                            'potencyFee4': '',
                            'potencyFee5': '',
                        }
                } else {
                    if (!this.orderToModify.fixedFee)
                        this.orderToModify.fixedFee = ''

                    if (!this.orderToModify.variableFee)
                        this.orderToModify.variableFee = ''
                }
            }

            if (this.orderToModify.productType === 'cl') {

                //Asigno filtros de potencia y consumo si existen
                const product = this.marketers.find(marketer => marketer.name === this.orderToModify.marketer)
                    .products['electricity']
                    .find(product => product.name === this.orderToModify.product);

                const feeId = this.marketers.find(marketer => marketer.name === this.orderToModify.marketer)
                    .fees['electricity']
                    .find(fee => fee.name === this.orderToModify.fee)?.id.$oid;

                const fee = product.fees.find(fee => fee.id.$oid === feeId);

                if (fee.minPower) {
                    this.minPower = fee.minPower;
                } else {
                    this.minPower = '';
                }
                if (fee.maxPower) {
                    this.maxPower = fee.maxPower;
                } else {
                    this.maxPower = '';
                }
                if (fee.minConsumption) {
                    this.minConsumption = fee.minConsumption;
                } else {
                    this.minConsumption = '';
                }
                if (fee.maxConsumption) {
                    this.maxConsumption = fee.maxConsumption;
                } else {
                    this.maxConsumption = '';
                }
            }

            if (this.orderToModify.extras) delete this.orderToModify.extras


            this.checkProductArchived();

            this.clearCommissions();
            this.calcCommission();

            this.calculateRenewalDate();
        },
        changeOrderProductSecondary() {

            //Si esta en estado 'Incidencia' se cambiara el estado a 'Pendiente'
            if (this.orderToModify.newStatus && this.orderToModify.newStatus.code !== '') { // si es newStatus, checkeo ahí

                if (this.orderToModify.newStatus.code === 'i' || this.orderToModify.newStatus.code === 's')
                    this.orderToModify.newStatus.code = 'p'

            }
            else {
                let lastStatus = this.orderToModify.statuses.reduce((latest, current) => {
                    return new Date(current.date) > new Date(latest.date) ? current : latest;
                });

                if (lastStatus.code === 'i' || this.orderToModify.newStatus.code === 's')
                    this.orderToModify.newStatus.code = 'p'
            }

            if (this.orderToModify.extras) delete this.orderToModify.extras

            /*if ((this.orderToModify.marketer === 'IberEléctrica' || this.orderToModify.marketer === 'Unieléctrica' || this.orderToModify.marketer === 'VM') && this.orderToModify.product !== '') {
                if (!this.orderToModify.energyFees)
                    this.orderToModify.energyFees = {
                        'energyFee0': '',
                        'energyFee1': '',
                        'energyFee2': '',
                        'energyFee3': '',
                        'energyFee4': '',
                        'energyFee5': '',
                    }

                if (!this.orderToModify.potencyFees)
                    this.orderToModify.potencyFees = {
                        'potencyFee0': '',
                        'potencyFee1': '',
                        'potencyFee2': '',
                        'potencyFee3': '',
                        'potencyFee4': '',
                        'potencyFee5': '',
                    }
            }

            if (this.orderToModify.productType == 'cl') {

                //Asigno filtros de potencia y consumo si existen
                const product = this.marketers.find(marketer => marketer.name === this.orderToModify.marketer)
                    .products['electricity']
                    .find(product => product.name === this.orderToModify.product);

                const feeId = this.marketers.find(marketer => marketer.name === this.orderToModify.marketer)
                    .fees['electricity']
                    .find(fee => fee.name === this.orderToModify.fee)?.id.$oid;

                const fee = product.fees.find(fee => fee.id.$oid === feeId);

                if (fee.minPower) {
                    this.minPower = fee.minPower;
                } else {
                    this.minPower = '';
                }
                if (fee.maxPower) {
                    this.maxPower = fee.maxPower;
                } else {
                    this.maxPower = '';
                }
                if (fee.minConsumption) {
                    this.minConsumption = fee.minConsumption;
                } else {
                    this.minConsumption = '';
                }
                if (fee.maxConsumption) {
                    this.maxConsumption = fee.maxConsumption;
                } else {
                    this.maxConsumption = '';
                }
            }

            //this.checkProductArchived();

            this.checkOrderComission();*/

            this.clearCommissions();

            this.calculateRenewalDate();
        },
        async fetchUserHierarchy(id){
            await axios.get(`/api/session/getAllSuperiors/${id}`).then((res) => {
                this.userHierarchy = res.data;
            }).catch((err) => console.log(err))
        },
        calcCommission(baseCommission = null, type = null, mode = 'commissions', installmentIndex = null) {
            const marketer = this.marketers.find(m => m.name === this.orderToModify.marketer);
            if (!marketer) return;

            //Cálculo para comisiones carterizadas
            if (type === 'installment' && installmentIndex !== null) {
                this.orderToModify.installmentCommissions[installmentIndex].commissions = calculateCommission({
                    userListTop: this.userHierarchy,
                    assignedToZoco: this.orderToModify?.assignedTo === '65cb57489c2c285441086a43',
                    marketer: marketer?._id,
                    commissionRanges: this.commissionRanges ?? this.basicData?.enterprise?.commissionRanges,
                    commissionRangesZoco: this.zocoCommissionRanges,
                    baseCommission: this.parseStringToNumber(baseCommission)
                });

                return;
            }

            let productsRelations = {
                cl: 'electricity',
                cg: 'gas',
                cd: 'dual',
                ct: 'telephony',
                sa: 'alarm',
                a: 'selfSupply'
            }

            // Obtener productInfo y feeInfo
            const productTypeKey = productsRelations[this.orderToModify.productType]

            //PRODUCTO
            let productInfo = null;

            //Dual
            if (productTypeKey === 'dual')
                productInfo = marketer.products[productTypeKey].find(p => p.electricity === this.orderToModify.product && p.gas === this.orderToModify.productSecondary);
            else
                productInfo = marketer.products[productTypeKey].find(p => p.name === this.orderToModify.product);

            if (!productInfo) {
                this.clearCommissions();
                console.log('NOT PRODUCT');
                return;
            }


            //FEE
            let feeId = null
            let feeInfo = null

            //Dual
            if (this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg') {
                feeId = marketer.fees[productTypeKey].find(f => f.name === this.orderToModify.fee)?.id.$oid;
                feeInfo = productInfo.fees.find(f => f.id.$oid === feeId);
            } else {
                if (this.orderToModify.productType === 'cd')
                    feeInfo = productInfo.fees.find(f => (f.electricity.name === this.orderToModify.fee && f.gas.name === this.orderToModify.feeSecondary));
                else if (this.orderToModify.productType === 'sa')
                    feeInfo = productInfo;
                else
                    feeInfo = productInfo.fees.find(f => (f.name === this.orderToModify.fee));
            }

            if (!feeInfo) {
                this.clearCommissions();
                console.log('NOT FEE');
                return;
            }

            let extras = [];

            if(this.orderToModify?.extras?.length > 0){
                const marketer = this.marketers.find(marketer =>
                    marketer.name === this.orderToModify.marketer
                );

                if (marketer) {
                    extras = marketer.extras
                        .filter(extra => this.orderToModify.extras.includes(extra.id['$oid']))
                        .map(extra => extra.commission);
                }
            }

            const baseParams = {
                userListTop: this.userHierarchy,
                assignedToZoco: this.orderToModify?.assignedTo === '65cb57489c2c285441086a43',
                marketer: marketer?._id,
                commissionRanges: this.commissionRanges ?? this.basicData?.enterprise?.commissionRanges,
                commissionRangesZoco: this.zocoCommissionRanges,
                commissionType: feeInfo?.commissionType,
                productType: this.orderToModify.productType,
                energyData: {
                    annual: this.parseStringToNumber(this.orderToModify?.consumption),
                    byPeriods: this.orderToModify?.consumptionData?.consumption
                },
                powerData: {
                    max: this.orderToModify?.hiredPotency,
                    byPeriods: this.orderToModify?.consumptionData?.hiredPotency
                },
                fees: {
                    power: Object.values(this.orderToModify?.potencyFees ?? {}),
                    energy: Object.values(this.orderToModify?.energyFees ?? {}),
                    fixed: this.orderToModify?.fixedFee,
                    variable: this.orderToModify?.variableFee,
                },
                extras,
                manualCommissions: this.basicData?.userSubdomain?.settings?.manualCommissions,
            };

            //Si es dual llamo a calculateCommission 2 veces, una por tipo
            if (this.orderToModify.productType === 'cd') {
                let electricityResult = this.orderToModify[mode]?.electricity ?? { subdomain: 0, breakdown: this.emptyBreakdown.map(item => ({ ...item })) };
                let gasResult = this.orderToModify[mode]?.gas ?? { subdomain: 0, breakdown: this.emptyBreakdown.map(item => ({ ...item })) };

                if (!type || type === 'electricity') {
                    electricityResult = calculateCommission({
                        ...baseParams,
                        commissions: feeInfo.electricity.commissions,
                        commissionType: feeInfo.electricity.commissionType,
                        ...(type === 'electricity' && baseCommission !== null && { baseCommission: this.parseStringToNumber(baseCommission) })
                    });
                }

                if (!type || type === 'gas') {
                    gasResult = calculateCommission({
                        ...baseParams,
                        commissions: feeInfo.gas.commissions,
                        commissionType: feeInfo.gas.commissionType,
                        ...(type === 'gas' && baseCommission !== null && { baseCommission: this.parseStringToNumber(baseCommission) })
                    });
                }

                const breakdownMap = new Map();
                [...electricityResult.breakdown, ...gasResult.breakdown].forEach(item => {
                    if (breakdownMap.has(item.userId)) {
                        breakdownMap.get(item.userId).commission = Math.round((breakdownMap.get(item.userId).commission + item.commission) * 100) / 100;
                    } else {
                        breakdownMap.set(item.userId, { ...item });
                    }
                });

                this.orderToModify[mode] = {
                    subdomain: Math.round((electricityResult.subdomain + gasResult.subdomain) * 100) / 100,
                    breakdown: Array.from(breakdownMap.values()),
                    electricity: electricityResult,
                    gas: gasResult
                };
            } else {
                this.orderToModify[mode] = calculateCommission({
                    ...baseParams,
                    commissions: feeInfo.commissions,
                    ...(!type && baseCommission !== null && { baseCommission: this.parseStringToNumber(baseCommission) })
                });
            }
        },
        createDecommissionsStructure() {
            const commissions = this.orderToModify.commissions;

            const cloneSupply = (supply) => ({
                ...supply,
                subdomain: null,
                breakdown: supply.breakdown?.map(item => ({
                    ...item,
                    commission: null
                })) ?? []
            });

            this.orderToModify.decommissions = {
                ...cloneSupply(commissions),
                ...(commissions.electricity && { electricity: cloneSupply(commissions.electricity) }),
                ...(commissions.gas && { gas: cloneSupply(commissions.gas) }),
            }
        },
        statusRequiresDecommissions(code) {
            return code === 'b' || code === 'pendiente_de_retrocomisin';
        },
        toggleInstallmentCommissions(){
            if (!this.canManage('contracts.manageCommissions')) return;

            if (this.isInputsDisabled) return

            if (!!this.orderToModify.installmentCommissions){
                Swal.fire({
                    icon:'warning',
                    title: '¿Estás seguro?',
                    text: 'Si borras las comisiones carterizadas no podrás volver a recuperarlo',
                    showCancelButton: true,
                    confirmButtonText: 'Si',
                    cancelButtonText: 'No'
                }).then((res) => {
                    if (res.isConfirmed){
                        delete this.orderToModify.installmentCommissions
                    }

                })
            }else{

                this.orderToModify.installmentCommissions = [
                    {
                        date: '',
                        commissions: {
                            subdomain: null,
                            breakdown: this.emptyBreakdown.map(item => ({ ...item }))
                        }
                    }
                ]
            }
        },
        addInstallmentCommission(){
            this.orderToModify.installmentCommissions.unshift({
                date: '',
                commissions: {
                    subdomain: null,
                    breakdown: this.emptyBreakdown.map(item => ({ ...item }))
                }
            })
        },
        deleteInstallmentCommission(commissionInd){
            if (this.orderToModify.installmentCommissions.length > 1){
                this.orderToModify.installmentCommissions.splice(commissionInd, 1);
                this.calcInstallmentCommissionsTotal();
            } else{
                Swal.fire({
                    icon: 'error',
                    text: '¡No puedes dejar el contrato sin ninguna comisión!',
                    timer: 2000,
                    timerProgressBar: true,
                })
            }
        },
        onInstallmentCommissionChange(value, index) {
            this.calcCommission(value, 'installment', 'commissions', index)
            this.calcInstallmentCommissionsTotal()
        },
        calcInstallmentCommissionsTotal(){
            const total = {
                subdomain: 0,
                breakdown: this.emptyBreakdown.map(item => ({ ...item, commission: 0 }))
            }

            this.orderToModify.installmentCommissions.forEach(installmentCommission => {
                total.subdomain += this.parseStringToNumber(installmentCommission.commissions.subdomain) || 0

                installmentCommission.commissions.breakdown.forEach(item => {
                    const totalItem = total.breakdown.find(b => b.userId === item.id)
                    if (totalItem) totalItem.commission += this.parseStringToNumber(item.commission) || 0
                })
            })

            this.orderToModify.commissions = total;
        },
        reorderInstallmentCommissions(){

            if (!Array.isArray(this.orderToModify?.installmentCommissions)) return;

            this.orderToModify.installmentCommissions = this.orderToModify.installmentCommissions.sort((a, b) => {
                const da = new Date(a.date);
                const db = new Date(b.date);
                return db - da;
            });
        },
        clearCommissions() {
            this.orderToModify.commissions = {subdomain: null, breakdown: this.emptyBreakdown.map(item => ({ ...item }))};

            if(this.orderToModify.productType === 'cd'){
                this.orderToModify.commissions.electricity = {subdomain: null, breakdown: this.emptyBreakdown.map(item => ({ ...item }))};
                this.orderToModify.commissions.gas = {subdomain: null, breakdown: this.emptyBreakdown.map(item => ({ ...item }))};
            }
        },
        formatOldDate() {

            // Verificar si la fecha cumple con el formato "DD/MM/YY"
            let regex = /^\d{2}\/\d{2}\/\d{2}$/;
            if (regex.test(this.orderToModify.processingDate)) {
                // Convertir la fecha al formato "YYYY-MM-DD"
                let parts = this.orderToModify.processingDate.split('/');
                let year = parts[2];
                let month = parts[1];
                let day = parts[0];

                // Asignar el nuevo valor al campo de fecha
                this.orderToModify.processingDate = '20' + year + '-' + month + '-' + day;
            }
        },
        selectStatus(event) {
            this.orderToModify.newStatus.code = event.target.value;

            if (this.orderToModify.newStatus.code === 'an' || this.orderToModify.newStatus.code === 's') {
                this.orderToModify.commissions = {subdomain: 0, breakdown: this.emptyBreakdown.map(item => ({ ...item }))}
            }
        },
        activeEditing() {
            this.$emit('activeEditing')
        },
        toggleDraftOrder() {

            if (
                this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2' &&
                !this.canManage('contracts.processor')
            ) {
                return;
            }

            if (!this.isInputsDisabled) {

                if (this.orderToModify.newStatus && this.orderToModify.newStatus.code !== '') {

                    if (this.orderToModify.newStatus.code !== 'bo')
                        this.orderToModify.newStatus.code = 'bo'
                    else
                        this.orderToModify.newStatus.code = 'p'

                } else {
                    let lastStatus = this.orderToModify.statuses.reduce((latest, current) => {
                        return new Date(current.date) > new Date(latest.date) ? current : latest;
                    });

                    if (lastStatus.code !== 'bo')
                        this.orderToModify.newStatus.code = 'bo'
                    else
                        this.orderToModify.newStatus.code = 'p'
                }

                this.orderToModify.errors = {}
            }
        },
        toggleReminder() {
            if (this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2') return;

            if (!this.isInputsDisabled) {
                this.orderToModify.isReminderOn = !this.orderToModify.isReminderOn;
                this.orderToModify.reminderChanged = true;
                this.calculateRenewalDate();
            }
        },
        async resendIncidenceMail() {

            await axios.post('/api/orders/sendIncidenceMail', { account: this.account, order: this.orderToModify, enterprise: this.basicData.enterprise },)
                .then((res) => {
                    console.log(res)
                })
                .catch((err) => {
                    console.log(err)
                })

        },
        toggleSelectVerification(verification) {

            if (!this.orderToModify.verifications)
                this.orderToModify.verifications = [];

            if (!this.orderToModify.newRegistrationPeriods)
                this.orderToModify.newRegistrationPeriods = {
                    p1: '',
                    p2: '',
                    p3: '',
                    p4: '',
                    p5: '',
                    p6: ''
                };

            if (!this.orderToModify.newRegistrationPrices)
                this.orderToModify.newRegistrationPrices = {
                    fixedPrice: '',
                    variablePrice: '',
                };

            if (!this.orderToModify.currentPotencyVerification && verification.code === 'pc')
                this.orderToModify.currentPotencyVerification = '';

            if (!this.orderToModify.requestedPotencyVerification && verification.code === 'pc')
                this.orderToModify.requestedPotencyVerification = '';

            const conflictingCodes = verification.code === 'nw' ? ['pc', 'tc'] :
                verification.code === 'pc' ? ['nw'] :
                    ['nw'];

            conflictingCodes.forEach(conflictCode => {
                let indexExist = this.orderToModify.verifications.indexOf(conflictCode);
                if (indexExist !== -1) {
                    this.orderToModify.verifications.splice(indexExist, 1);
                }
            });

            let index = this.orderToModify.verifications.indexOf(verification.code);

            if (index === -1) {
                this.orderToModify.verifications.push(verification.code);
            } else {
                this.orderToModify.verifications.splice(index, 1);
            }
        },
        toggleSelectAsignation() {
            if (!this.isInputsDisabled && this.assignedToSelected) {

                !this.orderToModify.assignedTo ? (this.orderToModify.assignedTo = this.assignedToSelected) : (delete this.orderToModify.assignedTo)

                //Saco las comercializadoras de nuevo, si esta assignedTo y es otro distinto a Zoco filtro las comercializadoras del propio subdominio habilitadas para el
                // usuario de por debajo del subdomino ( por ahora solo para fotón )
                if (this.orderToModify.assignedTo && this.orderToModify.assignedTo !== '65cb57489c2c285441086a43')
                    this.fetchMarketersAvailableDownSubdomain()
                else {
                    this.fetchMarketers()
                }


                //Tengo que tener en cuenta cuando no es sacar las comercializadoras de un subdominio, sino por ejemplo para lo de foton saco las que tenga el usuario asignado habilitado

                //Reinicio los datos de comercializadora y vinculados
                this.orderToModify.marketer = ''
                this.orderToModify.fee = ''
                this.orderToModify.product = ''

                //Borro los datos que no pueden tocar ellos
                this.orderToModify.processingDate = ''//fec. tramitación
                this.orderToModify.activationDate = ''//fec. activación
                if (!!this.orderToModify.lowDate) delete this.orderToModify.lowDate//fec. baja
                this.orderToModify.newStatus.code = 'p'// estado
                this.orderToModify.liquidationStatus = 'nl'// estado liquidación
                this.orderToModify.consumption = ''//consumo
                this.orderToModify.hiredPotency = '' //potencia
                this.orderToModify.commissions = { subdomain: 0, breakdown: this.emptyBreakdown.map(item => ({ ...item })) }
                this.orderToModify.decommissions = { subdomain: 0, breakdown: this.emptyBreakdown.map(item => ({ ...item })) }
                delete this.orderToModify.installmentCommissions // Comisiones carterizadas
                if (!!this.orderToModify.consumptionData) delete this.orderToModify.consumptionData //Desgloses

            }
        },
        applyPotencyFeeChange(event, potencyFeeInd) {

            let newValue = this.parseStringToNumber(event.target.value);

            //Valido el campo entre los límites del producto
            const minValue = this.parseStringToNumber(this.productSelected?.fees?.power?.minimum);
            const maxValue = this.parseStringToNumber(this.productSelected?.fees?.power?.maximum);


            if(newValue < minValue || newValue > maxValue){
                newValue = Math.min(Math.max(newValue, minValue), maxValue);
                Swal.fire({
                    icon: 'error',
                    title: 'Fee inválido',
                    text: `El valor debe estar entre ${minValue} y ${maxValue} € / kW mes` ,
                })
            }

            //Una vez validado, si el producto es unique, asigna el fee en todos los periodos, si no pues en el suyo
            if(this.productSelected?.fees?.power?.unique){
                Object.keys(this.orderToModify.potencyFees).forEach(key => {
                    this.orderToModify.potencyFees[key] = newValue;
                });
                Object.keys(this.orderToModify.errors)
                    .filter(key => key.startsWith('potencyFee'))
                    .forEach(key => delete this.orderToModify.errors[key]);
            }else{
                this.orderToModify.potencyFees['potencyFee' + potencyFeeInd] = newValue;
                delete this.orderToModify.errors['potencyFee' + potencyFeeInd]
            }

            this.calcCommission()
        },
        applyEnergyFeeChange(event, energyFeeInd) {

            let newValue = this.parseStringToNumber(event.target.value);

            //Valido el campo entre los límites del producto
            const minValue = this.parseStringToNumber(this.productSelected?.fees?.energy?.minimum);
            const maxValue = this.parseStringToNumber(this.productSelected?.fees?.energy?.maximum);


            if(newValue < minValue || newValue > maxValue){
                newValue = Math.min(Math.max(newValue, minValue), maxValue);
                Swal.fire({
                    icon: 'error',
                    title: 'Fee inválido',
                    text: `El valor debe estar entre ${minValue} y ${maxValue} € / MWh` ,
                })
            }

            //Una vez validado, si el producto es unique, asigna el fee en todos los periodos, si no pues en el suyo
            if(this.productSelected?.fees?.energy?.unique){
                Object.keys(this.orderToModify.energyFees).forEach(key => {
                    this.orderToModify.energyFees[key] = newValue;
                });
                Object.keys(this.orderToModify.errors)
                    .filter(key => key.startsWith('energyFee'))
                    .forEach(key => delete this.orderToModify.errors[key]);
            }else{
                this.orderToModify.energyFees['energyFee' + energyFeeInd] = newValue;
                delete this.orderToModify.errors['energyFee' + energyFeeInd]
            }

            this.calcCommission()
        },
        applyFixedFeeChange(event) {
            let newValue = this.parseStringToNumber(event.target.value);

            //Valido el campo entre los límites del producto
            const minValue = this.parseStringToNumber(this.productSelected?.fees?.fixed?.minimum);
            const maxValue = this.parseStringToNumber(this.productSelected?.fees?.fixed?.maximum);

            if(newValue < minValue || newValue > maxValue){
                newValue = Math.min(Math.max(newValue, minValue), maxValue);
                Swal.fire({
                    icon: 'error',
                    title: 'Fee inválido',
                    text: `El valor debe estar entre ${minValue} y ${maxValue} € / MWh` ,
                })
            }

            this.orderToModify.fixedFee = newValue;
            delete this.orderToModify.errors['fixedFee']

            this.calcCommission()
        },
        applyVariableFeeChange(event) {
            let newValue = this.parseStringToNumber(event.target.value);

            //Valido el campo entre los límites del producto
            const minValue = this.parseStringToNumber(this.productSelected?.fees?.variable?.minimum);
            const maxValue = this.parseStringToNumber(this.productSelected?.fees?.variable?.maximum);

            if(newValue < minValue || newValue > maxValue){
                newValue = Math.min(Math.max(newValue, minValue), maxValue);
                Swal.fire({
                    icon: 'error',
                    title: 'Fee inválido',
                    text: `El valor debe estar entre ${minValue} y ${maxValue} € / MWh` ,
                })
            }

            this.orderToModify.variableFee = newValue;
            delete this.orderToModify.errors['variableFee']

            this.calcCommission()
        },
        selectVersion(e) {
            const index = Number(e.target.value);
            const selected = this.versions[index];
            if (!selected?._id) return;

            // Detectar desde dónde estamos
            if (this.$route.path.startsWith('/accounts/')) {
                // Vista accounts
                this.$router.push({
                    path: this.$route.path,
                    query: { id: selected._id }
                });
            } else {
                // Vista contracts
                this.$router.push({
                    path: '/contracts',
                    query: { _id: selected._id }
                });
            }
        },
        async handleCall() {
            if (!this.orderToModify.naturgyCallSID && !this.orderToModify.naturgyCallRecordingUrl)
                this.startVerificationCall()
            else {
                Swal.fire({
                    title: '¿Qué deseas hacer?',
                    icon: 'question',
                    showConfirmButton: this.showConfirmButton,
                    showCancelButton: this.showCancelButton,
                    showDenyButton: (this.basicData.userLogged && (this.basicData.userLogged._id === 'soporte@zocoenergia.com' || this.basicData.userLogged.email === 'franperez@segenet.es' || this.basicData.userLogged._id === '68edfbf7c84a190aeb0a6672')),
                    confirmButtonText: 'Volver a llamar',
                    denyButtonText: 'Ir a grabación',
                    cancelButtonText: 'Descargar y adjuntar',
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.startVerificationCall();
                    } else if (result.isDenied) {
                        // Acción del segundo botón (deny)
                        if (this.orderToModify.naturgyCallSID) {
                            window.open(
                                `https://console.twilio.com/us1/monitor/logs/calls?frameUrl=%2Fconsole%2Fvoice%2Fcalls%2Flogs%2F${this.orderToModify.naturgyCallSID}%3Fx-target-region%3Dus1%26__override_layout__%3Dembed%26bifrost%3Dtrue`,
                                '_blank'
                            );
                        } else if (this.orderToModify.naturgyCallRecordingUrl) {
                            window.open(this.orderToModify.naturgyCallRecordingUrl, '_blank');
                        }
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // Acción del tercer botón
                        // Ejemplo: descargar y adjuntar al contrato
                        axios.post('/api/twilio/downloadNaturgyCall', { order: this.orderToModify, account: this.account })
                            .then((res) => {

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Llamada adjuntada',
                                    text: 'La llamada ha sido adjuntada a los documentos del contrato correctamente',
                                    timerProgressBar: true,
                                    timer: 1500
                                }).then((result) => {
                                    //Recargo cuenta
                                    this.$emit('reloadAcc')
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
                        enterpriseId: this.basicData?.subdomainEnterprise?._id,
                        orderId: this.orderToModify._id
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
                    //Compruebo que tenga CUPS
                    if (!!this.orderToModify.CUPS && this.orderToModify.CUPS !== '') {

                        Swal.fire({
                            icon: 'question',
                            text: '¿A quién va dirigida la llamada?',
                            input: "text",
                            //inputLabel: "Nombre propietario",
                            confirmButtonText: 'Llamar'
                        }).then((res) => {

                            if (!!res.value && res.value !== '') {

                                axios.post('/api/twilio/startCall', { order: this.orderToModify, account: this.account, name: res.value, enterprise: this.basicData.subdomainEnterprise })
                                    .then((res) => {
                                        this.orderToModify.naturgyCallSID = res.data.naturgyCallSID;
                                        this.naturgyCallStatus = this.$storage.TWILIO_CALL_STATUSES.find(status => status.code === 'ringing');
                                    })
                                    .catch((err) => {
                                        console.log(err)
                                    })

                            }
                        })

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'CUPS faltante',
                            text: 'Es necesario un CUPS para realizar la llamada',
                            confirmButtonText: 'Ok',
                            timerProgressBar: true,
                            timer: 1500
                        })
                    }
                }
                else if(res.isDenied){
                    startCall('+34'+this.account.phone, this.orderToModify.name, this.orderToModify._id, true)
                }
            })

        },
        async fetchNaturgyVerificationStatus() {
            axios.post('/api/twilio/getCallStatus', { order: this.orderToModify })
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
        checkProductArchived(type) {
            //Si el producto actual está archivado, avisar al usuario de que no podrá volver a seleccionarlo
            if (this.productArchived) {
                Swal.fire({
                    icon: "warning",
                    title: "Producto archivado",
                    text: "Si cambias el producto no podrás volver a seleccionar el actual",
                    confirmButtonText: 'Cambiar',
                    confirmButtonColor: 'red',
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar',
                    focusCancel: true
                }).then((res) => {

                    //Si acepta, cambiar el producto a no archivado.
                    if (res.isConfirmed) {
                        this.productArchived = false;
                        // Si cancela, devolver el valor del orderToModify al original
                    } else {
                        switch (type) {
                            case "marketer":
                                this.orderToModify.marketer = this.order.marketer;
                                this.orderToModify.fee = this.order.fee;
                                this.orderToModify.product = this.order.product;
                                break;
                            case "fee":
                                this.orderToModify.fee = this.order.fee;
                                this.orderToModify.product = this.order.product;
                                break;
                            default:
                                this.orderToModify.product = this.order.product;
                        }
                    }
                })
            }
        },
        copyAccountData(){
            this.orderToModify.name = this.account.name;
            this.orderToModify.direc = this.account.billingInfo.address;
            this.orderToModify.town = this.account.billingInfo.locality;
            this.orderToModify.province = this.account.billingInfo.province;
            this.orderToModify.zip = this.account.billingInfo.zipCode;
            this.orderToModify.accountCIF = this.account.CIF;
            if (this.basicData.userSubdomain._id === '68d260e6bc9e8c38f8003df2') {
                this.orderToModify.phone = this.account.phone;
                this.orderToModify.email = this.account.email;
            }
        },
        handleClick(){
            if (!this.$route.path.includes('accounts'))
                this.actionLink('/accounts/' + this.account._id);
            else
                this.closeWindow();

        },
        toggleEditingContract(type, id){
            this.$emit('toggleEditingContract', type, id)
        },
        createNewMessage(){

            if (this.sendingMessage || !this.orderToModify._id) return;

            this.sendingMessage = true;

            axios.post('/api/orders/createNewMessage', {order: this.orderToModify._id, message: this.newMessage, enterprise: this.basicData.enterprise}).then((res) => {
                this.newMessage = "";

                if(!!this.orderToModify.messages){
                    this.orderToModify.messages.push(res.data.data);
                }else{
                    this.orderToModify.messages = [res.data.data];
                }

                //Scroll hasta abajo después de añadir el nuevo mensaje
                this.$nextTick(() => {
                    const container = document.getElementById("messages-container");
                    container.scrollTo({ top: container.scrollHeight, behavior: "smooth" });
                });
            }).finally(() => {
                this.sendingMessage = false;
            })
        },
        isHex(color) {
            return /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(color);
        },
        hexToRgba(hex, alpha = 1) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },
        getProductData(){
            // Obtener la comercializadora
            const marketerInfo = this.marketers.find(m => m.name === this.orderToModify.marketer);

            if(!marketerInfo) return

            let productsRelations = {
                cl: 'electricity',
                cg: 'gas',
                cd: 'dual',
                ct: 'telephony',
                sa: 'alarm',
                a: 'selfSupply'
            }

            // Obtener productInfo y feeInfo
            const productTypeKey = productsRelations[this.orderToModify.productType]


            //PRODUCTO
            let productInfo = null;

            if (productTypeKey === 'dual')
                productInfo = marketerInfo.products[productTypeKey].find(p => p.electricity === this.orderToModify.product && p.gas === this.orderToModify.productSecondary);
            else
                productInfo = marketerInfo.products[productTypeKey].find(p => p.name === this.orderToModify.product);


            if (!productInfo) {
                this.clearCommissions();
                console.log('NOT PRODUCT');
                return;
            }


            //FEE
            let feeOid = null
            let feeInfo = null


            if (this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg'){

                feeOid = marketerInfo.fees[productTypeKey].find(f => f.name === this.orderToModify.fee)?.id.$oid;
                feeInfo = productInfo.fees.find(f => f.id.$oid === feeOid);

            }else{

                if (this.orderToModify.productType === 'cd') //Tengo que buscar en el producto la tarifa de luz que seleccionamos que este relacionada con la tarifa de gas que seleccionamos ( pq por ejemplo puede haber más de 1 Tarifa 2.0TD y no puede ser la primera que vea y ya)
                    feeInfo = productInfo.fees.find(f => (f.electricity.name === this.orderToModify.fee && f.gas.name === this.orderToModify.feeSecondary));
                else if (this.orderToModify.productType === 'sa')
                    feeInfo = productInfo;
                else
                    feeInfo = productInfo.fees.find(f => (f.name === this.orderToModify.fee));
            }


            if (!feeInfo) {
                this.clearCommissions();
                console.log('NOT FEE');
                return;
            }

            return {marketerInfo, productInfo, feeInfo}
        },
        toggleSelectExtraProduct(extra){

            //Si no existe lo creo
            if(!this.orderToModify.extras) this.orderToModify.extras = []

            let extraId = extra.id?.$oid || extra.id;

            let index = this.orderToModify.extras.indexOf(extraId);

            if (index === -1)
                this.orderToModify.extras.push(extraId);
            else
                this.orderToModify.extras.splice(index, 1);

            //Recalculo la comisión
            this.calcCommission();
        },
        isSameBreakdownStructure(current, expected) {
            if (!Array.isArray(current) || !Array.isArray(expected)) return false;
            if (current.length !== expected.length) return false;

            const currentIds = current.map(item => item.userId).sort();
            const expectedIds = expected.map(item => item.userId).sort();

            return currentIds.every((id, i) => id === expectedIds[i]);
        },
        parseStringToNumber(number) {
            if (typeof number === "number") {
                return number;
            } else if (typeof number === "string") {
                return number === "" ? 0 : parseFloat(number.replace(",", "."));
            } else {
                return 0
            }
        },
        getFullName(user){
            return `${user?.firstName} ${user?.lastName}`
        },
        _getAgentCommissionFromBreakdown(breakdown) {
            if (!breakdown?.length) return ''
            if (this.canManage('contracts.manageCommissions')) return breakdown[0] ?? {}
            const userId = this.basicData.userLogged?._id
            return breakdown.find(item => item.userId === userId) ?? {}
        },
        _setAgentCommissionFromBreakdown(breakdown, value) {
            if (!breakdown?.length) return
            if (this.canManage('contracts.manageCommissions')) {
                if (breakdown[0]) breakdown[0] = value
                return
            }
            const userId = this.basicData.userLogged?._id
            const userLevel = breakdown.find(item => item.userId === userId)
            if (userLevel) Object.assign(userLevel, value)
        },
        _filteredHierarchyFromBreakdown(breakdown) {
            if (!breakdown?.length) return []
            if (this.canManage('contracts.manageCommissions')) return breakdown.slice(1)
            const userId = this.basicData.userLogged?._id
            const userIndex = breakdown.findIndex(item => item.userId === userId)
            if (userIndex === -1) return []
            return breakdown.slice(userIndex + 1)
        },
        getTodayDateString() {
            const date = new Date();

            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        },

        validateDateNotGreaterThanToday(object, field) {
            const value = object[field];
            const today = this.getTodayDateString();

            if (value && value > today) {
                object[field] = '';

                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha no válida',
                    text: 'No puede introducir una fecha superior al día de hoy.'
                });
            }
        },
        actionLink(route) {
            this.$router.push(route)
        }
    },
    computed: {
        isFidelitySubdomain() {
            return this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2';
        },
        hideFidelityVisualNoise() {
            return this.isFidelitySubdomain;
        },
        canEditFidelityAutoAccountFields() {
            return this.canManage('users.admiWhiHier');
        },
        areFidelityAccountFieldsLocked() {
            return this.isFidelitySubdomain && !!this.account && !this.canEditFidelityAutoAccountFields;
        },
        isContractNameDisabled() {
            return !!this.basicData?.userSubdomain?.settings?.orderDisabledName || this.areFidelityAccountFieldsLocked;
        },
        versions() {
            const orders = this.account?.orders;
            if (!Array.isArray(orders)) return [];

            const current = this.orderToModify;
            if (!current || !current._id || current._isTemp) return [];

            const cups = (current.CUPS || '').trim();
            if (!cups) return [];

            const sameCups = orders.filter(o =>
                (o.CUPS || '').trim() === cups &&
                o._id &&
                !String(o._id).startsWith('tmp_')
            );

            if (sameCups.length <= 1) return [];

            return sameCups
                .slice()
                .sort((a, b) => getOrderDate(b).toDate() - getOrderDate(a).toDate());
        },
        agentCommission: {
            get() { return this._getAgentCommissionFromBreakdown(this.orderToModify?.commissions?.breakdown) },
            set(value) { this._setAgentCommissionFromBreakdown(this.orderToModify?.commissions?.breakdown, value) }
        },
        agentCommissionElectricity: {
            get() { return this._getAgentCommissionFromBreakdown(this.orderToModify?.commissions?.electricity?.breakdown) },
            set(value) { this._setAgentCommissionFromBreakdown(this.orderToModify?.commissions?.electricity?.breakdown, value) }
        },
        agentCommissionGas: {
            get() { return this._getAgentCommissionFromBreakdown(this.orderToModify?.commissions?.gas?.breakdown) },
            set(value) { this._setAgentCommissionFromBreakdown(this.orderToModify?.commissions?.gas?.breakdown, value) }
        },
        filteredCommissionHierarchy() {
            return this._filteredHierarchyFromBreakdown(this.orderToModify?.commissions?.breakdown)
        },
        filteredCommissionHierarchyElectricity() {
            return this._filteredHierarchyFromBreakdown(this.orderToModify?.commissions?.electricity?.breakdown)
        },
        filteredCommissionHierarchyGas() {
            return this._filteredHierarchyFromBreakdown(this.orderToModify?.commissions?.gas?.breakdown)
        },
        agentDecommission: {
            get() { return this._getAgentCommissionFromBreakdown(this.orderToModify?.decommissions?.breakdown) },
            set(value) { this._setAgentCommissionFromBreakdown(this.orderToModify?.decommissions?.breakdown, value) }
        },
        agentDecommissionElectricity: {
            get() { return this._getAgentCommissionFromBreakdown(this.orderToModify?.decommissions?.electricity?.breakdown) },
            set(value) { this._setAgentCommissionFromBreakdown(this.orderToModify?.decommissions?.electricity?.breakdown, value) }
        },
        agentDecommissionGas: {
            get() { return this._getAgentCommissionFromBreakdown(this.orderToModify?.decommissions?.gas?.breakdown) },
            set(value) { this._setAgentCommissionFromBreakdown(this.orderToModify?.decommissions?.gas?.breakdown, value) }
        },

        filteredDecommissionHierarchy() {
            return this._filteredHierarchyFromBreakdown(this.orderToModify?.decommissions?.breakdown)
        },
        filteredDecommissionHierarchyElectricity() {
            return this._filteredHierarchyFromBreakdown(this.orderToModify?.decommissions?.electricity?.breakdown)
        },
        filteredDecommissionHierarchyGas() {
            return this._filteredHierarchyFromBreakdown(this.orderToModify?.decommissions?.gas?.breakdown)
        },
        user() {
            return this.$store.state.user
        },
        isViewingVersion() {
            if (!this.$route.query.id) return false;

            return String(this.$route.query.id) !== String(this.order?._id);
        },
        renewalReminderByMarketer() {
            const sub = this.basicData && this.basicData.userSubdomain;
            return !!(sub?.settings?.orderRenewalReminderByMarketer);
        },
        productTypesWithoutSp() {
            return this.$storage.PRODUCT_TYPES.filter(type => type.code !== 'sp');
        },
        statusSelected() {
            if (
                !this.orderToModify ||
                !this.basicData?.userSubdomain ||
                !Array.isArray(this.basicData.userSubdomain.statuses)
            ) {
                return {};
            }

            const newStatusCode = this.orderToModify.newStatus?.code;

            let recentStatusCode = null;

            if (Array.isArray(this.orderToModify.statuses) && this.orderToModify.statuses.length) {
                const recentStatus = this.orderToModify.statuses.reduce((latest, current) => {
                    return new Date(current.date) > new Date(latest.date) ? current : latest;
                });
                recentStatusCode = recentStatus?.code;
            }

            const codeToFind = newStatusCode || recentStatusCode;

            if (!codeToFind) return {};

            return (
                this.basicData.userSubdomain.statuses.find(
                    status => status.code === codeToFind
                ) || {}
            );
        },
        productSelected() {
            if (
                !this.marketers?.length ||
                !this.orderToModify?.product ||
                !this.orderToModify?.fee ||
                !this.orderToModify?.marketer ||
                !this.orderToModify?.productType
            ) {
                return {};
            }

            const marketer = this.marketers.find(
                marketer => marketer.name === this.orderToModify.marketer
            );

            if (!marketer) return {};

            const product = this.filteredMarketerProducts.find(
                product => product.name === this.orderToModify.product
            );

            if (!product?.fees?.length) return {};

            const feeType = (
                this.orderToModify.productType === 'cl' ||
                this.orderToModify.productType === 'cd'
            )
                ? 'electricity'
                : 'gas';

            const selectedFee = marketer.fees?.[feeType]?.find(
                fee => fee.name === this.orderToModify.fee
            );

            if (!selectedFee?.id?.$oid) return {};

            return product.fees.find(
                fee => fee.id?.$oid === selectedFee.id.$oid
            ) || {};
        },
        pendingStatusCode() {
            const statuses = this.basicData?.userSubdomain?.statuses;

            if (!Array.isArray(statuses)) return 'p';

            const pendingStatus = statuses.find(
                status => String(status?.title || '').trim().toLowerCase() === 'pendiente'
            );

            return pendingStatus?.code || 'p';
        },
        canEditConsumption() {
            // Editable siempre con el permiso "Gestionar consumos"; si no, solo en borrador
            const currentCode = this.orderToModify?.newStatus?.code || this.statusSelected?.code;

            return (
                this.canManage('contracts.consumos') ||
                currentCode === 'bo'
            );
        },
        canEditPower() {
            // Editable siempre con el permiso "Gestionar potencias"; si no, solo en borrador
            const currentCode = this.orderToModify?.newStatus?.code || this.statusSelected?.code;

            return (
                this.canManage('contracts.potencias') ||
                currentCode === 'bo'
            );
        },
        isRenewalStatus() {
            const title = String(this.statusSelected?.title || '').trim().toLowerCase();
            return title === 'renovar';
        },
        canShowRenewalCopyButton() {
            return (!this.isEditing
                && !!this.orderToModify?._id
                && this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2'
                && this.canManage('contracts.create')
                && this.isRenewalStatus
            );
        },
        liquidationStatusSelected() {
            return this.liquidationStatuses.find((status) => {
                return status.code === this.orderToModify.liquidationStatus
            })
        },
        filteredMarketers() {
            if (!this.marketers.length > 0 || !this.orderToModify.productType) return []

            let marketers = this.marketers.filter((marketer) => {
                return ((this.orderToModify.productType === 'cl' && marketer['products']['electricity'].length > 0) || (this.orderToModify.productType === 'cg' && marketer['products']['gas'].length > 0) || (this.orderToModify.productType === 'cd' && (marketer['products']['dual']?.length ?? 0) > 0) || (this.orderToModify.productType === 'ct' && (marketer['products']['telephony']?.length ?? 0) > 0) || (this.orderToModify.productType === 'sa' && (marketer['products']['alarm']?.length ?? 0) > 0) || (this.orderToModify.productType === 'a' && (marketer['products']['selfSupply']?.length ?? 0) > 0)) &&
                    (!marketer.archived || this.orderToModify.marketer === marketer.name )
                //Filtrar en caso de no ser tramitado con Zoco
            }).filter(marketer => {
                const isSubdomainUser = this.basicData.userLogged.label === 'Usuario subdominio'

                //En caso de ser asignado a Zoco, mostrar aquellos que tengan el subdominio
                if (this.orderToModify.assignedTo)
                    return true //no se están poniendo nuevos a subdominios


                //En caso de no ser asignado a Zoco, mostrar aquellos que tenga el usuario si no es subdominio
                if (!isSubdomainUser)
                    return this.basicData.userLogged.marketers.includes(marketer._id)


                // En caso de ser subdominio mostrar todos
                return true
            })

            marketers = marketers.sort((a, b) => {
                if (a.name < b.name) return -1;
                if (a.name > b.name) return 1;
                return 0;
            })

            return marketers
        },
        filteredFees() {
            if (!this.marketers.length > 0 || !this.orderToModify.productType || !this.orderToModify.marketer) return []

            let marketer = (this.marketers.find((marketer) => {return (marketer.name === this.orderToModify.marketer)}))

            //Si no encontró la comercializadora, devuelve array vacío
            if(!marketer) return []

            //Saco el nombre en bbdd del tipo de producto
            let productType = this.$storage.PRODUCT_TYPES.find(pt => pt.code === this.orderToModify.productType).inDatabase

            //Si es contrato dual tengo que ver las tarifas de las que haya una relación creada
            if(this.orderToModify.productType === 'cd'){

                return [
                    ...new Map(
                        marketer.products.dual
                            .flatMap(dual => dual.fees)
                            .map(fee => [fee.electricity.name, { name: fee.electricity.name }])
                    ).values()
                ];

            }else{

                if (productType === 'electricity' || productType === 'gas')
                    return marketer['fees'][productType]
                else
                    return this.$storage.FEES[productType].filter(fee => {
                        return marketer['products'][productType].some(product => {
                            return product.fees.some(feeProduct => feeProduct.name === fee && !feeProduct.archived)
                        })
                    }).map(fee => ({ name: fee }))
            }

        },
        filteredFeesSecondary(){
            if (!this.marketers.length > 0 || !this.orderToModify.productType || !this.orderToModify.marketer || !this.orderToModify.product) return []

            let marketer = (this.marketers.find((marketer) => {
                return (marketer.name === this.orderToModify.marketer)
            }))

            //Si no encontró la comercializadora, devuelve array vacío
            if(!marketer) return []

            //Tengo que sacar las tarifas de los productos relacionados al producto de luz seleccionado
            return [
                ...new Set(
                    marketer.products.dual
                        .filter(dual => dual.electricity === this.orderToModify.product)
                        .flatMap(dual => dual.fees)
                        .map(fee => fee.gas?.name)
                )
            ].map(name => ({name}))
        },
        filteredMarketerProducts() {
            if (!this.marketers.length > 0 || !this.orderToModify.productType || !this.orderToModify.marketer || (!this.orderToModify.fee && ! this.orderToModify.productType === 'sa')) return []

            let marketer = (this.marketers.find((marketer) => {return (marketer.name === this.orderToModify.marketer)}))

            //Si no encontró la comercializadora, devuelve array vacío
            if(!marketer) return []

            let productType = this.$storage.PRODUCT_TYPES.find(pt => pt.code === this.orderToModify.productType).inDatabase

            if (this.orderToModify.productType === 'cd'){

                //Saco los productos de luz que tengan la tarifa seleccionada
                return  [
                    ...new Set(
                        marketer.products.dual.filter(dual =>
                            dual.fees.some(fee => fee.electricity?.name === this.orderToModify.fee)
                        ).map(dual => dual.electricity)
                    )
                ].map(name => ({ name }))
            }
            else{

                if (productType === 'electricity' || productType === 'gas')
                    return marketer.products[productType].filter((product) => {
                        return product.fees.find(fee => {
                            if (fee?.archived && product.name === this.orderToModify.product)
                                this.productArchived = true;

                            //No incluir los fees que no cumplen el rango de potencia contratado

                            if (this.orderToModify.hiredPotency !== null && this.orderToModify.hiredPotency !== '') {

                                const minPower = Number(fee.minPower);
                                const maxPower = Number(fee.maxPower);
                                const power = Number(this.orderToModify.hiredPotency);

                                // Validar mínimo: solo si fee.minPower existe (no es null/undefined)
                                if (fee.minPower != null && power < minPower) {
                                    return false;
                                }

                                // Validar máximo: solo si fee.maxPower existe y no es 0 (o según tu lógica)
                                if (fee.maxPower != null && fee.maxPower > 0 && power > maxPower) {
                                    return false;
                                }
                            }
                            if ((!!fee.minConsumption || !!fee.maxConsumption) && !!this.orderToModify.consumption) {

                                // Si existe un mínimo y no se cumple → excluir
                                if (!!fee.minConsumption && this.orderToModify.consumption < fee.minConsumption) return false;

                                // Si existe un máximo y no se cumple → excluir
                                if (!!fee.maxConsumption && this.orderToModify.consumption > fee.maxConsumption) return false;
                            }
                            return fee.id.$oid === marketer.fees[(this.orderToModify.productType === 'cl'|| this.orderToModify.productType === 'cd') ? 'electricity' : 'gas'].find(feeNow => feeNow.name === this.orderToModify.fee).id.$oid && (!fee?.archived || product.name === this.orderToModify.product)
                        })
                    })
                else if (productType === 'alarm')
                    return marketer.products[productType].filter((product) => {

                        if (product.archived && product.name === this.orderToModify.product)
                            this.productArchived = true;

                        return (!product.archived || product.name === this.orderToModify.product)
                    })
                else
                    return marketer.products[productType].filter((product) => {
                        return product.fees.find(fee => {
                            if (fee?.archived && product.name === this.orderToModify.product)
                                this.productArchived = true;

                            //Busco si el producto tiene la tarifa seleccionada
                            let feeActived = product.fees.find(feeNow => feeNow.name === this.orderToModify.fee)

                            return  feeActived && (!fee?.archived || product.name === this.orderToModify.product)
                        })
                    })
            }
        },
        filteredMarketerProductsSecondary() {
            if (!this.marketers.length > 0 || !this.orderToModify.productType || !this.orderToModify.marketer || !this.orderToModify.feeSecondary) return []

            let marketer = (this.marketers.find((marketer) => {
                return (marketer.name === this.orderToModify.marketer)
            }))

            //Si no encontró la comercializadora, devuelve array vacío
            if(!marketer) return []

            //Saco los productos de gas
            return  [
                ...new Set(
                    marketer.products.dual.filter(dual =>
                        dual.fees.some(fee => fee.gas?.name === this.orderToModify.feeSecondary)
                    ).map(dual => dual.gas)
                )
            ].map(name => ({ name }))
        },
        filteredStatuses() {
            if (
                !this.basicData?.userSubdomain ||
                !Array.isArray(this.basicData.userSubdomain.statuses) ||
                !this.orderToModify
            ) {
                return [];
            }

            const newStatusCode = this.orderToModify.newStatus?.code;

            let recentStatusCode = null;

            if (Array.isArray(this.orderToModify.statuses) && this.orderToModify.statuses.length) {
                const recentStatus = this.orderToModify.statuses.reduce((latest, current) => {
                    return new Date(current.date) > new Date(latest.date) ? current : latest;
                });
                recentStatusCode = recentStatus?.code;
            }

            return this.basicData.userSubdomain.statuses.filter(status => {
                let managedArchived = true;

                if (
                    status.archived &&
                    status.code !== newStatusCode &&
                    status.code !== recentStatusCode
                ) {
                    managedArchived = false;
                }

                return (
                    managedArchived &&
                    (
                        status.limitedTo.length === 0 ||
                        status.limitedTo.includes(this.basicData.userLogged?._id)
                    )
                );
            });
        },
        filteredLiquidationStatuses() {

            let liquidationStatuses = [...this.liquidationStatuses]

            liquidationStatuses = liquidationStatuses.filter((status) => {
                return (status.limitedTo.length === 0 || status.limitedTo.includes(this.basicData.userLogged._id))
            })

            return liquidationStatuses
        },
        isDraftOrder() {
            let isdraft = false

            if (this.orderToModify.newStatus && this.orderToModify.newStatus.code !== '') {
                isdraft = this.orderToModify.newStatus.code === 'bo'
            } else {
                let lastStatus = this.orderToModify?.statuses?.reduce((latest, current) => {
                    return new Date(current.date) > new Date(latest.date) ? current : latest;
                });

                isdraft = lastStatus.code === 'bo'
            }

            return isdraft
        },
        filteredHistory() {

            if (!this.orderToModify) return []

            //Método para obtener el usuario
            const resolveUser = (id) => {

                if (!id) {
                    return {
                        id: null,
                        name: 'Desconocido',
                        profileImage: null
                    };
                }

                // Si el usuario es el logueado
                if (id === this.basicData.userLogged._id) {
                    return {
                        id: id,
                        name: `${this.basicData.userLogged.firstName} ${this.basicData.userLogged.lastName}`,
                        profileImage: this.basicData.userLogged.profileImage || null
                    };
                }

                // Buscar en lista de usuarios
                let u = this.basicData?.subdomainUserList?.find(u => u._id === id);

                if (!u) {
                    return {
                        id: null,
                        name: 'Desconocido',
                        profileImage: null
                    };
                }

                return {
                    id: u._id,
                    name: `${u.firstName} ${u.lastName}`,
                    profileImage: u.profileImage || null
                };
            };

            let filteredHistory = [];

            //Estados
            if (this.history.statuses && this.orderToModify.statuses) {
                let statuses = JSON.parse(JSON.stringify(this.orderToModify.statuses));

                statuses.forEach((status) => {

                    let statusData = this.basicData.userSubdomain.statuses.find(
                        statusNow => status.code === statusNow.code
                    );

                    status.color = statusData?.color;
                    status.title = statusData?.title;

                    let user = resolveUser(status.creator);

                    filteredHistory.push({
                        type: 'status',
                        date: status.date,
                        creator: user,
                        data: status
                    });
                });
            }

            //Mensajes
            if (this.history.messages && this.orderToModify.messages) {
                this.orderToModify.messages.forEach(msg => {

                    let user = resolveUser(msg.creator);

                    filteredHistory.push({
                        type: 'message',
                        date: msg.date,
                        creator: user,
                        data: {
                            message: msg.message
                        }
                    });
                });
            }

            //Archivos
            if (this.history.files && this.orderToModify.docs) {
                this.orderToModify.docs.forEach(file => {

                    //Solo los documentos subidos quedan reflejados (tienen fecha registrada)
                    if (!file || !file.date) return;

                    let user = resolveUser(file?.creator);

                    filteredHistory.push({
                        type: 'file',
                        date: file.date,
                        creator: user,
                        data: {
                            title: file.title || file.defaultTitle || 'documento',
                        }
                    });
                });
            }

            //ordenar por fecha
            filteredHistory.sort((a, b) => new Date(a.date) - new Date(b.date));

            return filteredHistory;
        },
        isInputsDisabled() {
            const user = this.basicData?.userLogged;
            const subdomain = this.basicData?.userSubdomain;
            const order = this.orderToModify;

            if (!user || !subdomain || !order) return true;
            if (!this.isEditing) return true;
            if (user.permissions.includes('READONLY')) return true;

            const currentCode = this.orderToModify?.newStatus?.code || this.statusSelected?.code;

            if (order.assignedTo && order.assignedTo === this.basicData.userLogged._id) return false;
            if (!order.assignedTo && this.canManage('contracts.processor')) return false;

            let allowedStatuses = [];
            //Si es kuvi
            if (subdomain._id === '6909faa9232c09035a03f3b2') {
                allowedStatuses = ['bo'];
            }else{
                allowedStatuses = ['bo', 'p', 'i'];
            }

            return !allowedStatuses.includes(currentCode);
        },
        isSensitiveFieldsDisabled() {
            const subdomain = this.basicData?.userSubdomain;
            const order = this.orderToModify;

            if (!subdomain || !order) return true;

            if (!order.assignedTo) return false;

            return order.assignedTo !== this.basicData.userLogged._id;
        },
        extraProductsToSelect(){
            if (!this.orderToModify.productType || !this.orderToModify.marketer || !this.orderToModify.product || (this.orderToModify.productType === 'cd' && !this.orderToModify.productSecondary) ) return []

            //Miro si el producto tiene extras  aplicables
            let data = this.getProductData()

            if (!data) return

            let extras = [];
            let info = null;

            //Saco la info del fee/producto seleccionado
            if (this.orderToModify.productType === 'sa')
                info = data.productInfo
            else
                info = data.feeInfo


            //Saco los extras del fee/producto seleccionado
            extras = info.extras

            //Si no tiene ninguno
            if ((!extras || extras.length === 0) && (!data.marketerInfo.extras || data.marketerInfo.extras.length === 0)) return []

            let searchText = (this.extraSearchText || '').toLowerCase().trim();

            //Saco los extras ( filtro si el extra es residencial o pyme y el producto tiene alguna de estas o si es pap y esta habilitado para el producto )
            return data.marketerInfo.extras.filter(extra => {

                //Si no tiene habilitado el mismo tipo que el producto seleccionado salimos
                if (!this.$storage.PRODUCT_TYPES.map((tp) => {return tp.code}).includes(this.orderToModify.productType)) return false;

                let valid = false;

                //pyme
                if(info.type.pyme && extra.to.pyme) valid = true;

                //residencial
                if(info.type.residencial && extra.to.residential) valid = true;

                //Producto a producto
                if(extra.to.product && extras && extras.includes(extra.id.$oid)) valid = true;

                if (!valid) return false;

                //Busco por nombre
                if (searchText)
                    return extra.name?.toLowerCase().includes(searchText);

                return true;
            })
        },
        isDownZoco() {
            //Compruebo si esta debajo de Zoco
            let downZoco = false;
            let userNowToSee = this.basicData.userLogged;

            do {

                if (!!userNowToSee && userNowToSee.responsibles && userNowToSee.responsibles.length > 0) {

                    userNowToSee = this.basicData.userListComplete.find((user) => user._id === userNowToSee.responsibles[0])

                    if (userNowToSee.responsibles[0] === '65cb57489c2c285441086a43') downZoco = true
                }

            } while (!downZoco && !!userNowToSee.responsibles && userNowToSee.responsibles.length !== 0)

            return downZoco
        },
        canSeeZoco() {
            //Compruebo si el usuario subdominio tiene marcado que se pueda ver o no
            return this.basicData.userSubdomain.agentsCanSeeZoco;
        },
        agentsToAssign() {
            let canAssignTo = [];

            //Si puede ver tramitar con otros ( por ahora solo para Fotón )
            if (this.basicData.userLogged.label !== 'Usuario subdominio') {
                let subdomainId = this.basicData.userSubdomain._id;

                //Si esta por debajo del subdominio saco sus canAssignTo, sino busco al usuario superior que este por debajo del subdominio
                if (this.basicData.userLogged.responsibles.includes(subdomainId)) {
                    if (!this.basicData.userLogged.canAssignTo) return
                    canAssignTo = this.basicData.userListComplete.filter(user => this.basicData.userLogged.canAssignTo.includes(user._id)).map(user => ({title: user.firstName + ' ' + user.lastName, code: user._id}))
                }else {
                    let userDownSubdomain = this.$utilities.obtainUserDownSubdomain(this.basicData.userLogged._id, this.basicData.userListComplete, this.basicData.userSubdomain._id)

                    if(userDownSubdomain) {
                        if (!userDownSubdomain.canAssignTo) return
                        canAssignTo = this.basicData.userListComplete.filter(user => userDownSubdomain.canAssignTo.includes(user._id)).map(user => ({title: user.firstName + ' ' + user.lastName, code: user._id}))
                    }
                }
            }

            //Si el subdominio tiene activado tramitar con Zoco
            if (this.basicData.userSubdomain.agentsCanSeeZoco) canAssignTo.push({ title: 'Zoco Energía', code: '65cb57489c2c285441086a43' })

            return canAssignTo
        },
        showConfirmButton() {
            if (!this.orderToModify) return false;
            return this.orderToModify?.naturgyCallRecordingUrl || this.naturgyCallStatus?.code !== 'ringing'
        },

        showCancelButton() {
            if (!this.orderToModify) return false;
            return this.orderToModify?.naturgyCallRecordingUrl || (this.orderToModify?.naturgyCallSID && this.naturgyCallStatus && !['no-answer', 'canceled', 'failed', 'busy', 'queued', 'ringing'].includes(this.naturgyCallStatus.code))
        },
    },
    setup() {
        const { width } = useWindowSize()
        return { width }
    },
}
</script>
<style>
.accordion-enter-active,
.accordion-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.accordion-enter-from,
.accordion-leave-to {
    max-height: 0;
    opacity: 0;
}

.customer-type-select-wrapper {
    position: relative;
}

.customer-type-select {
    width: 100%;
    padding-right: 40px;

    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;

    cursor: pointer;
}

.customer-type-select-wrapper::after {
    content: "";
    position: absolute;
    right: 16px;
    top: 50%;

    width: 8px;
    height: 8px;

    border-right: 1.8px solid #003f7f;
    border-bottom: 1.8px solid #003f7f;

    transform: translateY(-65%) rotate(45deg);
    pointer-events: none;
}

.customer-type-select:disabled {
    cursor: not-allowed;
}

.accordion-enter-to,
.accordion-leave-from {
    max-height: 500px;
    opacity: 1;
}
</style>
