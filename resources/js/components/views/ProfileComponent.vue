<template>
    <div class="content-white profile" v-if="userToModify && basicData.enterprise">

        <!--Imagen superior-->
        <div class="img-base" :data-bg="basicData.enterprise.color">
            <div class="img-div">
                <img
                    class="general-icon w-50"
                    data-size="big"
                    data-style="square"
                    data-weight="500"
                    :src="
                        '/assets/enterprises/' +
                        basicData.enterprise.asset_folder +
                        '/logos/logo-light.png'
                    "
                    alt="Imagen CRM"
                />
            </div>
        </div>

        <form class="form" v-on:submit.prevent="">
            <!--Parte imagen y botón editar/guardar-->

            <!--Movil-->
            <div class="mobile-item">
                <div class="info-top d-flex justify-between">
                    <div class="d-flex column align-center justify-center relPos">
                        <img
                            :alt="userToModify.firstName"
                            class="img-profile"
                            data-size="myProfile"
                            :src="image"
                        />
                        <input
                            ref="profileImg"
                            id="profileImg"
                            style="display: none"
                            v-on:change="pickupImage"
                            class="b"
                            type="file"
                            accept="image/png, image/jpeg, image/jpg"
                        />
                        <div
                            v-if="isEditing"
                            class="pointer custom-button"
                            data-size="medium"
                            data-position="bottom"
                            data-bg="principal"
                            v-on:click="openDialog"
                        >
                            Cambiar
                        </div>
                    </div>

                    <!--Botón editar/guardar-->
                    <div class="my-auto d-flex justify-end">
                        <button
                            class="custom-button pointer mr-10"
                            data-size="medium"
                            data-bg="verde"
                            v-on:click="openQR"
                        >
                            <i class="fas fa-qrcode"></i>
                        </button>
                        <!--Cerrar sesión-->
                        <button
                            class="custom-button pointer mr-10"
                            data-size="medium"
                            data-bg="rojo"
                            v-on:click="logout"
                        >
                            <i class="fas fa-right-from-bracket"></i>
                        </button>
                        <button
                            class="custom-button pointer"
                            data-size="medium"
                            data-bg="blanco"
                            v-if="isEditing"
                            v-on:click="cancelChanges"
                        >
                            <i class="fas fa-xmark"></i>
                        </button>
                        <button
                            class="custom-button pointer ml-10"
                            data-size="medium"
                            v-if="isEditing"
                            v-on:click="saveProfile"
                        >
                            <i class="fas fa-floppy-disk"></i>
                        </button>
                        <button
                            class="custom-button pointer"
                            data-size="medium"
                            v-else
                            v-on:click="isEditing = true"
                        >
                            <i class="fas fa-pen"></i>
                        </button>
                    </div>
                </div>

                <!--Info-->
                <div class="mt-40 mb-50">
                    <p class="text" data-size="20" data-weight="700">
                        Tu perfil
                    </p>

                    <p class="text" data-size="13">
                        Actualiza tus datos de acceso y demás parámetros de tu
                        perfil
                    </p>
                </div>
            </div>

            <!--Escritorio-->
            <div class="desktop-item">
                <div class="info-top d-flex w-100">
                    <div
                        class="w-20 d-flex column align-center justify-center relPos"
                    >
                        <img
                            :alt="userToModify.firstName"
                            class="img-profile"
                            data-size="myProfile"
                            :src="image"
                        />
                        <input
                            ref="profileImg"
                            id="profileImg"
                            style="display: none"
                            v-on:change="pickupImage"
                            class="b"
                            type="file"
                            accept="image/png, image/jpeg, image/jpg"
                        />
                        <div
                            v-if="isEditing"
                            class="pointer custom-button"
                            data-size="regular"
                            data-position="bottom"
                            data-bg="principal"
                            v-on:click="openDialog"
                        >
                            Cambiar
                        </div>
                    </div>

                    <!--Info-->
                    <div class="w-50 my-auto">
                        <p class="text" data-size="22" data-weight="700">
                            Perfil
                        </p>

                        <p class="text" data-size="15">
                            Actualiza tus datos de acceso y demás parámetros de
                            tu perfil
                        </p>
                    </div>

                    <!--Botón editar/guardar-->
                    <div class="w-30 my-auto d-flex justify-end">

                        <button
                            class="custom-button pointer mr-10"
                            data-size="regular"
                            data-bg="verde"
                            v-on:click="openQR"
                        >
                            Mi QR
                        </button>

                        <button
                            class="custom-button pointer mr-10"
                            data-size="regular"
                            data-bg="azul"
                            v-on:click="isSeeingDocs = !isSeeingDocs"
                        >
                            Documentos
                        </button>
                        <button
                            class="custom-button pointer mr-10"
                            data-size="regular"
                            data-bg="rojo"
                            v-on:click="logout"
                        >
                            Cerrar sesión
                        </button>
                        <button
                            class="custom-button pointer"
                            data-size="regular"
                            data-bg="blanco"
                            v-if="isEditing"
                            v-on:click="cancelChanges"
                        >
                            Cancelar
                        </button>
                        <button
                            class="custom-button pointer ml-10"
                            data-size="regular"
                            v-if="isEditing"
                            v-on:click="saveProfile"
                        >
                            Guardar
                        </button>
                        <button
                            class="custom-button pointer"
                            data-size="regular"
                            v-else
                            v-on:click="isEditing = true"
                        >
                            Editar
                        </button>
                    </div>
                </div>
            </div>

            <!--División campos-->
            <div class="fields mt-30">
                <!--Nombre-->
                <div
                    v-bind:class="{ wrong: errors.firstName }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Nombre</p>

                        <div class="input-group ml-10 w-50">
                            <input
                                v-on:focus="delete errors.firstName"
                                v-model="userToModify.firstName"
                                :disabled="!isEditing"
                                type="text"
                                name="newPassword"
                            />
                        </div>
                    </div>

                    <span v-if="errors.firstName" class="error">{{
                        errors.firstName
                    }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--Apellidos-->
                <div
                    v-bind:class="{ wrong: errors.lastName }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Apellidos</p>

                        <div class="input-group ml-10 w-50">
                            <input
                                v-on:focus="delete errors.lastName"
                                v-model="userToModify.lastName"
                                :disabled="!isEditing"
                                type="text"
                                name="newPassword"
                            />
                        </div>
                    </div>

                    <span v-if="errors.lastName" class="error">{{
                        errors.lastName
                    }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--Correo-->
                <div
                    v-bind:class="{ wrong: errors.email }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Email</p>

                        <div class="input-group ml-10 w-50">
                            <input
                                v-on:focus="delete errors.email"
                                v-model="userToModify.email"
                                :disabled="!isEditing"
                                type="text"
                                name="newPassword"
                            />
                        </div>
                    </div>

                    <span v-if="errors.email" class="error">{{
                        errors.email
                    }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--Telefono-->
                <div
                    v-bind:class="{ wrong: errors.phone }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Teléfono</p>

                        <div class="input-group ml-10 w-50">
                            <input
                                v-on:focus="delete errors.phone"
                                v-model="userToModify.phone"
                                :disabled="!isEditing"
                                type="text"
                                name="newPassword"
                            />
                        </div>
                    </div>

                    <span v-if="errors.phone" class="error">{{
                        errors.phone
                    }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--dni-->
                <div
                    v-bind:class="{ wrong: errors.dni }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">DNI/CIF</p>

                        <div class="input-group ml-10 w-50">
                            <input
                                v-on:focus="delete errors.dni"
                                v-model="userToModify.dni"
                                :disabled="!isEditing"
                                type="text"
                                name="newPassword"
                            />
                        </div>
                    </div>

                    <span v-if="errors.dni" class="error">{{
                        errors.dni
                    }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--género-->
                <div
                    v-bind:class="{ wrong: errors.gender }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Género</p>

                        <div class="input-group ml-10 w-50">
                            <select
                                v-model="userToModify.gender"
                                v-on:focus="delete errors.gender"
                                :disabled="!isEditing"
                            >
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                                <option value="O">Otro</option>
                            </select>
                        </div>
                    </div>
                    <span v-if="errors.gender" class="error">{{
                        errors.gender
                    }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--Dirección-->
                <div
                    v-bind:class="{ wrong: errors.address }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Dirección</p>

                        <div class="input-group ml-10 w-50">
                            <input
                                v-on:focus="delete errors.address"
                                v-model="userToModify.address"
                                :disabled="!isEditing"
                                type="text"
                                name="newPassword"
                            />
                        </div>
                    </div>

                    <span v-if="errors.address" class="error">{{
                        errors.address
                    }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--Código postal-->
                <div
                    v-bind:class="{ wrong: errors.postal }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Código postal</p>

                        <div class="input-group ml-10 w-50">
                            <input
                                v-on:focus="delete errors.postal"
                                v-model="userToModify.postal"
                                :disabled="!isEditing"
                                type="text"
                                name="newPassword"
                            />
                        </div>
                    </div>

                    <span v-if="errors.postal" class="error">{{
                        errors.postal
                    }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--Población-->
                <div
                    v-bind:class="{ wrong: errors.locality }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Población</p>

                        <div class="input-group ml-10 w-50">
                            <input
                                v-on:focus="delete errors.locality"
                                v-model="userToModify.locality"
                                :disabled="!isEditing"
                                type="text"
                                name="newPassword"
                            />
                        </div>
                    </div>

                    <span v-if="errors.locality" class="error">{{
                        errors.locality
                    }}</span>

                    <div class="separator my-15"></div>
                </div>

                <!--Provincia-->
                <div
                    v-bind:class="{ wrong: errors.province }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Provincia</p>

                        <div class="input-group ml-10 w-50">
                            <input
                                v-on:focus="delete errors.province"
                                v-model="userToModify.province"
                                :disabled="!isEditing"
                                type="text"
                                name="newPassword"
                            />
                        </div>
                    </div>

                    <span v-if="errors.province" class="error">{{
                        errors.province
                    }}</span>

                </div>

                <div class="separator my-15"></div>

            </div>

            <!--Cambio contraseña-->
            <div class="d-flex justify-between align-center">
                <div class="text my-30" data-size="17" data-weight="700">
                    Cambio de contraseña <i class="fas fa-lock ml-10"></i>
                </div>

                <div
                    v-if="!isOldPasswordCorrect"
                    class="custom-button"
                    data-size="regular"
                    data-bg="azul"
                    v-on:click.stop="checkOldPassword"
                >
                    Comprobar
                </div>
                <div
                    v-else
                    class="custom-button"
                    data-size="regular"
                    data-bg="azul"
                    v-on:click="changePassword"
                >
                    Cambiar
                </div>
            </div>

            <!--Antigua-->
            <div
                v-if="!isOldPasswordCorrect"
                v-bind:class="{ wrong: password.errors.old }"
                class="form-group my-0"
            >
                <div class="d-flex justify-between align-center">
                    <p class="text input-name">Antigua contraseña</p>

                    <div class="d-flex column w-50 ml-10">
                        <div class="input-group">
                            <input
                                v-on:focus="delete password.errors.old"
                                v-model="password.old"
                                :disabled="isOldPasswordCorrect"
                                type="text"
                                name="newPassword"
                            />
                        </div>

                        <span v-if="password.errors.old" class="error">{{
                            password.errors.old
                        }}</span>
                    </div>
                </div>

                <div class="separator my-15"></div>
            </div>

            <!--Nueva contraseña-->
            <div class="fields" v-else>
                <div
                    v-bind:class="{ wrong: password.errors.new }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Contraseña nueva</p>

                        <div class="d-flex column w-50 ml-10">
                            <div class="input-group">
                                <input
                                    v-on:focus="delete password.errors.new"
                                    v-model="password.new"
                                    type="text"
                                    name="newPassword"
                                />
                            </div>

                            <span v-if="password.errors.new" class="error">{{
                                password.errors.new
                            }}</span>
                        </div>
                    </div>

                    <div class="separator my-15"></div>
                </div>

                <!--Repetir contraseña nueva-->
                <div
                    v-bind:class="{ wrong: password.errors.repeat }"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Repite contraseña nueva</p>

                        <div class="d-flex column w-50 ml-10">
                            <div class="input-group">
                                <input
                                    v-on:focus="delete password.errors.repeat"
                                    v-model="password.repeat"
                                    type="text"
                                    name="newPassword"
                                />
                            </div>

                            <span v-if="password.errors.repeat" class="error">{{
                                password.errors.repeat
                            }}</span>
                        </div>
                    </div>

                    <div class="separator my-15"></div>
                </div>
            </div>

            <!--Rango de comisiones-->
            <template v-if="!isEditing && basicData.userLogged && basicData.userLogged.label === 'Usuario subdominio'">
                <div class="d-flex justify-between mt-30 mb-20">
                    <p class="text" data-size="17" data-weight="700">Rangos de comisiones <i class="fas fa-percent ml-10"></i></p>
                    <div class="custom-button" data-size="medium" data-bg="principal" data-mode="translucent" @click="showCommissionRanges = true">Ver rangos</div>
                </div>
                <div class="separator my-15"></div>
            </template>

            <!--Más opciones-->
            <div v-if="basicData.userLogged.label === 'Usuario subdominio'">
                <div class="text my-30" data-size="17" data-weight="700">
                    Más opciones <i class="fas fa-gear ml-10"></i>
                </div>

                <!-- Email de notificaciones -->
                <div
                    v-if="basicData.userLogged.label === 'Usuario subdominio'"
                    class="form-group my-0"
                >
                    <div class="d-flex justify-between align-center">
                        <p class="text input-name">Email notificaciones</p>

                        <div v-if="enterpriseToModify" class="input-group ml-10 w-50">
                            <input
                                v-model="enterpriseToModify.notification_email"
                                :disabled="!isEditing"
                                type="email"
                                placeholder="Email para notificaciones"
                            />
                        </div>
                    </div>

                    <div class="separator my-15"></div>
                </div>

                <div class="d-flex" data-gap="30">
                    <div
                        v-for="section in settingsSections"
                        :key="section.title"
                        class="flex-1"
                    >
                        <p class="text opacity-6">{{ section.title }}</p>

                        <template v-for="option in section.options" :key="option.key || option.id">
                            <div v-if="!option.hidden" class="d-flex my-5">
                                <div class="custom-checkbox my-auto mr-10" @click="toggleSetting(option)">
                                    <div :class="{ selected: getSettingValue(option), }" />
                                </div>

                                <p class="my-auto mr-15" data-color="principal" data-size="10">
                                    {{ option.label }}
                                </p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!--Suscripción-->
            <div class="d-flex mt-50" data-gap="10" v-if="basicData && basicData?.userLogged?.label === 'Usuario subdominio' && activePlan && basicData.subscription">
                <!--Suscripción activa-->
                <div class="d-flex column justify-center align-center w-30 my-50 w-fit-content">
                    <div class="subscription-card selected">

                        <h1 class="text-center">{{ activePlan.name }}</h1>

                        <div class="price text-center">
                            <span class="amount">
                                €{{ activePlan[basicData.subscription.isAnnual ? 'annualPrice' : 'price'] }}
                            </span>

                            <span class="period">
                                {{ basicData.subscription.isAnnual ? '/año' : '/mes' }}
                            </span>
                        </div>

                        <div class="features">
                            <!--Incluye-->
                            <div v-for="included in activePlan.included" :key="included.title">
                                <i class="fa-regular fa-check"></i> {{ included.title }}
                            </div>

                            <!--No incluye-->
                            <div
                                v-for="notIncluded in activePlan.notIncluded"
                                :key="notIncluded.title"
                                class="opacity-5"
                            >
                                - {{ notIncluded.title }}
                            </div>
                        </div>
                    </div>

                    <p class="opacity-5 text-center mt-25" data-size="10">
                        Precio sin IVA.
                    </p>
                </div>

                <!--Suscripción y stripe-->
                <div class="d-flex column justify-center align-center w-70">

                    <!--Parte stripe-->
                    <div v-if="hasStripeData" class="subscription-stripe-card w-100 round p-25 mb-20" data-round="20">

                        <div class="d-flex justify-between align-center w-100" data-gap="25">

                            <!-- Estado -->
                            <div class="d-flex align-center w-30" data-gap="18">
                                <div class="stripe-status-icon" data-size="15" :class="stripeStatusClass">
                                    <i :class="stripeStatusIcon"></i>
                                </div>

                                <div>
                                    <div class="stripe-status-title" :class="stripeStatusTextClass" style="background-color: transparent !important">
                                        {{ stripeStatusTitle }}
                                    </div>

                                    <div class="text" data-size="10">
                                        {{ stripeStatusDescription }}
                                    </div>

                                    <div v-if="stripeStatusBadge" class="stripe-status-badge mt-5" :class="stripeStatusClass">
                                        {{ stripeStatusBadge }}
                                    </div>
                                </div>
                            </div>

                            <!-- Facturación -->
                            <div class="w-25 stripe-card-section">
                                <div class="stripe-label">FACTURACIÓN</div>

                                <div class="text" data-size="17" data-weight="700">
                                    {{ subscriptionBillingLabel }}
                                </div>

                                <div class="text" data-size="12">
                                    {{ subscriptionRenewalText }}
                                </div>
                            </div>

                            <!-- Última factura -->
                            <div class="w-25 stripe-card-section">
                                <div class="stripe-label">
                                    {{ stripe.status === 'paused' ? 'PRÓXIMA FACTURA' : 'ÚLTIMA FACTURA' }}
                                </div>

                                <div class="text" data-size="17" data-weight="700" :class="invoiceStatusClass">
                                    {{ invoiceStatusText }}
                                </div>

                                <div class="text" data-size="14">
                                    {{ invoiceDateText }}
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="w-20 d-flex column align-center" data-gap="8">

                                <div v-if="showPrimaryStripeButton" class="custom-button w-100" data-size="small" data-bg="principal" data-mode="translucent" data- v-on:click="handlePrimaryStripeAction">
                                    <i :class="primaryStripeButtonIcon"></i>
                                    {{ primaryStripeButtonText }}
                                </div>

                                <div v-for="button in secondaryStripeButtons" :key="button.key" class="custom-button w-100" data-size="small"
                                     data-bg="principal" data-mode="translucent" v-on:click="handleStripeAction(button.action)">
                                    <i :class="button.icon"></i>
                                    {{ button.text }}
                                </div>

                            </div>
                        </div>
                    </div>

                    <!--Si quieres añadir extras-->
                    <div v-if="activePlan" class="d-flex justify-center align-center w-100 round px-15 py-10 mb-20"
                        data-round="20" data-gap="20" data-bg="azul-claro">
                        <!--Icono-->
                        <div class="w-5 d-flex justify-center">
                            <i class="far fa-arrow-up" data-size="30"></i>
                        </div>

                        <!--Texto medio-->
                        <div class="w-70">
                            <div data-size="20" data-weight="600">
                                Amplíe su plan cuando lo necesite
                            </div>
                            <div>
                                Añada usuarios, escaneos o monitorizaciones extra en cualquier momento
                            </div>
                        </div>

                        <!--Gestionar extras-->
                        <div class="w-25 d-flex justify-center">
                            <div
                                class="custom-button"
                                data-size="regular"
                                data-bg="principal"
                                data-mode="translucent"
                                v-on:click="showExtrasModal = true"
                            >
                                <i class="far fa-circle-plus"></i> Gestionar extras
                            </div>
                        </div>
                    </div>

                    <!--Modal rangos de comisión-->
                    <div v-if="showCommissionRanges" class="extras-modal-overlay" @click="showCommissionRanges = false">
                        <div class="extras-modal h-100" @click.stop>

                            <!-- Header -->
                            <div class="extras-modal-header">
                                <div>
                                    <div class="extras-modal-title">Gestionar rangos de comisión</div>
                                    <div class="extras-modal-subtitle">Añade, reordena o elimina rangos de comisión.</div>
                                </div>

                                <button class="extras-modal-close" type="button" @click="showCommissionRanges = false">
                                    <i class="far fa-xmark" />
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="extras-modal-body">
                                <div class="d-flex justify-between">
                                    <div class="custom-button w-fit-content" data-size="medium" data-bg="principal" data-mode="translucent" @click="addCommissionRange">
                                        <i class="far fa-plus mr-5"/>Añadir rango
                                    </div>
                                    <div class="d-flex" data-gap="5">
                                        <div class="custom-button w-fit-content" data-size="medium" data-bg="principal" data-mode="translucent" @click="resetCommissionRanges">
                                            <i class="far fa-arrow-rotate-left mr-5"/>Restablecer
                                        </div>
                                        <div class="custom-button w-fit-content" data-size="medium" @click="updateCommissionRanges">
                                            <i class="far fa-save mr-5"/>Actualizar
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex" data-gap="10">
                                    <draggable v-model="commissionRanges" item-key="id" class="d-flex column" >
                                        <template #item="{ element: range }">
                                            <div class="d-flex status-card align-center px-20 justify-between pointer" data-gap="40"
                                                 :data-bg="commissionRangeSelected === range ? 'celeste' : ''"
                                                 @click="selectCommissionRange(range)">
                                                <p class="text" :data-color="commissionRangeSelected === range ? 'blanco' : ''" data-weight="600" data-size="18">{{ range.name }}</p>
                                                <p class="text" :data-color="commissionRangeSelected === range ? 'blanco' : ''" data-size="18">{{ range.percentage }} %</p>
                                            </div>
                                        </template>
                                    </draggable>
                                    <div v-if="commissionRangeSelected" class="p-15 form-group d-flex column mt-40 flex-1" data-gap="10">
                                        <div class="d-flex align-center" data-gap="10">
                                            <p class="text w-25 text-end" data-size="16">Nombre: </p>
                                            <input v-model="commissionRangeSelected.name" class="input-group text w-75" data-size="14">
                                        </div>
                                        <div class="d-flex align-center" data-gap="10">
                                            <p class="text w-25 text-end" data-size="16">Porcentaje: </p>
                                            <div class="input-group text w-75">
                                                <input v-model="commissionRangeSelected.percentage" data-size="14" class="w-100" />%
                                            </div>
                                        </div>
                                        <div class="custom-button w-100 mt-10 text-center" data-size="medium" data-bg="principal" data-mode="translucent" @click="deleteCommissionRange">
                                            <i class="far fa-trash text pointer mr-5"/>Borrar rango
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--Modal extras-->
                    <div v-if="showExtrasModal" class="extras-modal-overlay">

                        <div class="extras-modal">

                            <!-- Header -->
                            <div class="extras-modal-header">
                                <div>
                                    <div class="extras-modal-title">
                                        Gestionar extras
                                    </div>

                                    <div class="extras-modal-subtitle">
                                        Añada recursos adicionales a su plan cuando lo necesite.
                                    </div>
                                </div>

                                <button class="extras-modal-close" type="button" v-on:click="closeExtrasModal">
                                    <i class="far fa-xmark"></i>
                                </button>
                            </div>

                            <!-- Tabs -->
                            <div class="extras-tabs">
                                <button
                                    v-for="tab in extrasTabs.filter(plan => !plan.plansExcluded.includes(activePlan.id))"
                                    :key="tab.key"
                                    type="button"
                                    class="extras-tab"
                                    :class="{ active: activeExtrasTab === tab.key }"
                                    v-on:click="setExtrasTab(tab.key)"
                                >
                                    <i :class="tab.icon"></i>
                                    {{ tab.title }}
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="extras-modal-body">

                                <!-- ESCANEOS -->
                                <div v-if="activeExtrasTab === 'scans'" class="extras-tab-content">

                                    <!-- Resumen -->
                                    <div class="extras-summary-card">
                                        <div class="extras-summary-icon">
                                            <i class="far fa-file-magnifying-glass"></i>
                                        </div>

                                        <div class="extras-summary-info">
                                            <div class="extras-summary-title">
                                                Escaneos disponibles
                                            </div>

                                            <div class="extras-summary-text">
                                                Estudios comprados pendientes de usar.
                                            </div>
                                        </div>

                                        <div class="extras-summary-number">
                                            <strong>{{ scansExtra.remaining || 0 }}</strong>
                                            <span>estudios</span>
                                        </div>
                                    </div>

                                    <!-- Opciones compra -->
                                    <div class="extras-buy-grid">

                                        <div
                                            v-for="extra in scanExtrasList"
                                            :key="extra.id"
                                            class="extras-buy-card"
                                            :class="{ highlighted: extra.highlighted }"
                                        >
                                            <div class="extras-buy-card-top">
                                                <div>
                                                    <div class="extras-buy-title">
                                                        {{ extra.title }}
                                                    </div>

                                                    <div class="extras-buy-price">
                                                        {{ formatMoney(extra.price) }}

                                                        <span v-if="extra.id === 'unit'">
                                                            por estudio
                                                        </span>

                                                        <span v-else>
                                                            {{ formatMoney(extra.price / extra.amount) }}/estudio
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="extras-buy-icon">
                                                    <i :class="extra.icon"></i>
                                                </div>
                                            </div>

                                            <div v-if="extra.badge" class="extras-badge">
                                                {{ extra.badge }}
                                            </div>

                                            <div class="extras-buy-features">
                                                <div
                                                    v-for="feature in extra.features"
                                                    :key="feature"
                                                >
                                                    <i class="far fa-check"></i>
                                                    {{ feature }}
                                                </div>
                                            </div>

                                            <template v-if="extra.id === 'unit'">
                                                <div class="extras-quantity-box">
                                                    <button type="button" v-on:click="decreaseScansUnitQuantity">
                                                        <i class="far fa-minus"></i>
                                                    </button>

                                                    <input
                                                        type="number"
                                                        :min="extra.min_quantity"
                                                        :max="extra.max_quantity"
                                                        :value="scansUnitQuantity"
                                                        v-on:input="setScansUnitQuantity"
                                                    >

                                                    <button type="button" v-on:click="increaseScansUnitQuantity">
                                                        <i class="far fa-plus"></i>
                                                    </button>
                                                </div>

                                                <div class="extras-card-total">
                                                    <span>Total</span>
                                                    <strong>{{ formatMoney(scansUnitTotal) }}</strong>
                                                </div>

                                                <button
                                                    type="button"
                                                    class="extras-primary-button"
                                                    v-on:click="buyOneTimeExtra('scans', extra.id, scansUnitQuantity)"
                                                >
                                                    Comprar {{ scansUnitQuantity }} estudios
                                                </button>
                                            </template>

                                            <template v-else>
                                                <div class="extras-card-total">
                                                    <span>Total</span>
                                                    <strong>{{ formatMoney(extra.price) }}</strong>
                                                </div>

                                                <button type="button" class="extras-primary-button" v-on:click="buyOneTimeExtra('scans', extra.id, 1)">
                                                    Comprar {{ extra.title.toLowerCase() }}
                                                </button>
                                            </template>
                                        </div>

                                    </div>

                                    <!-- Historial -->
                                    <div class="extras-history">
                                        <div class="extras-section-header">
                                            <div>
                                                <div class="extras-section-title">
                                                    Historial de compras de escaneos
                                                </div>

                                                <div class="extras-section-subtitle">
                                                    Compras realizadas y estudios añadidos a su cuenta.
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="scansPurchases.length" class="extras-history-table">
                                            <div class="extras-history-row extras-history-head">
                                                <div>Fecha</div>
                                                <div>Tipo</div>
                                                <div>Cantidad</div>
                                                <div>Precio</div>
                                                <div>Recibo</div>
                                            </div>

                                            <div v-for="purchase in scansPurchases" :key="purchase.stripe_checkout_session_id || purchase.purchasedAt" class="extras-history-row">
                                                <div>{{ formatExtraDate(purchase.purchasedAt) }}</div>

                                                <div>
                                                    <strong>{{ purchase.title || purchase.type }}</strong>
                                                </div>

                                                <div>+{{ purchase.amount }} estudios</div>

                                                <div>{{ formatMoney(purchase.price_paid) }}</div>

                                                <div>
                                                    <a v-if="purchase.receipt_url" :href="purchase.receipt_url" target="_blank" class="custom-button" data-size="small" data-bg="principal" data-mode="translucent">
                                                        Ver recibo
                                                    </a>

                                                    <span v-else class="extras-muted">—</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else class="extras-empty-history">
                                            <i class="far fa-receipt"></i>
                                            <div>
                                                Todavía no hay compras de escaneos.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- USUARIOS -->
                                <div v-if="activeExtrasTab === 'users'" class="extras-tab-content">

                                    <!-- Resumen -->
                                    <div class="extras-summary-card">
                                        <div class="extras-summary-icon">
                                            <i class="far fa-users"></i>
                                        </div>

                                        <div class="extras-summary-info">
                                            <div class="extras-summary-title">
                                                Usuarios extra contratados
                                            </div>

                                            <div class="extras-summary-text">
                                                Usuarios adicionales añadidos a su suscripción mensual.
                                            </div>
                                        </div>

                                        <div class="extras-summary-number">
                                            <strong>{{ usersExtra.amount || 0 }}</strong>
                                            <span>usuarios</span>
                                        </div>
                                    </div>

                                    <!-- Opciones compra -->
                                    <div class="extras-buy-grid">

                                        <div
                                            v-for="extra in usersExtrasList"
                                            :key="extra.id"
                                            class="extras-buy-card"
                                            :class="{ highlighted: extra.highlighted }"
                                        >
                                            <div class="extras-buy-card-top">
                                                <div>
                                                    <div class="extras-buy-title">
                                                        {{ extra.title }}
                                                    </div>

                                                    <div class="extras-buy-price">
                                                        {{ formatMoney(extra.price) }}/mes

                                                        <span>
                                                            {{ formatMoney(extra.price / extra.amount) }}/usuario
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="extras-buy-icon">
                                                    <i :class="extra.icon"></i>
                                                </div>
                                            </div>

                                            <div v-if="extra.badge" class="extras-badge">
                                                {{ extra.badge }}
                                            </div>

                                            <div class="extras-buy-features">
                                                <div
                                                    v-for="feature in extra.features"
                                                    :key="feature"
                                                >
                                                    <i class="far fa-check"></i>
                                                    {{ feature }}
                                                </div>
                                            </div>

                                            <div class="extras-card-total">
                                                <span>Total mensual</span>
                                                <strong>{{ formatMoney(extra.price) }}/mes</strong>
                                            </div>

                                            <button
                                                type="button"
                                                class="extras-primary-button"
                                                v-on:click="buyRecurringExtra('users', extra.id, 1)"
                                            >
                                                Añadir {{ extra.title.toLowerCase() }}
                                            </button>
                                        </div>

                                    </div>

                                    <!-- Extras contratados -->
                                    <div class="extras-history">
                                        <div class="extras-section-header">
                                            <div>
                                                <div class="extras-section-title">
                                                    Usuarios extra activos
                                                </div>

                                                <div class="extras-section-subtitle">
                                                    Packs recurrentes añadidos a su suscripción.
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="usersRecurringItems.length" class="extras-history-table">
                                            <div class="extras-history-row extras-history-head">
                                                <div>Fecha</div>
                                                <div>Tipo</div>
                                                <div>Cantidad</div>
                                                <div>Precio mensual</div>
                                                <div>Acción</div>
                                            </div>

                                            <div
                                                v-for="item in usersRecurringItems"
                                                :key="item.id || item.stripe_subscription_item_id || item.createdAt"
                                                class="extras-history-row"
                                            >
                                                <div>{{ formatExtraDate(item.createdAt) }}</div>

                                                <div>
                                                    <strong>{{ item.title || item.type }}</strong>
                                                </div>

                                                <div>
                                                    <template v-if="item.quantity && item.quantity > 1">
                                                        x{{ item.quantity }} · +{{ item.amount }} usuarios
                                                    </template>

                                                    <template v-else>
                                                        +{{ item.amount }} usuarios
                                                    </template>
                                                </div>

                                                <div>{{ formatMoney(item.total_price || item.price || 0) }}/mes</div>

                                                <div>
                                                    <button
                                                        type="button"
                                                        class="custom-button"
                                                        data-size="small"
                                                        data-bg="rojo"
                                                        data-mode="translucent"
                                                        v-on:click="cancelRecurringExtra('users', item)"
                                                    >
                                                        Cancelar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else class="extras-empty-history">
                                            <i class="far fa-receipt"></i>
                                            <div>
                                                Todavía no hay usuarios extra contratados.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- MONITORIZACIONES -->
                                <div v-if="activeExtrasTab === 'monitoring'" class="extras-tab-content">

                                    <!-- Resumen -->
                                    <div class="extras-summary-card">
                                        <div class="extras-summary-icon">
                                            <i class="far fa-chart-line"></i>
                                        </div>

                                        <div class="extras-summary-info">
                                            <div class="extras-summary-title">
                                                Monitorizaciones extra contratadas
                                            </div>

                                            <div class="extras-summary-text">
                                                Monitorizaciones adicionales añadidas a su suscripción mensual.
                                            </div>
                                        </div>

                                        <div class="extras-summary-number">
                                            <strong>{{ monitoringExtra.amount || 0 }}</strong>
                                            <span>monitorizaciones</span>
                                        </div>
                                    </div>

                                    <!-- Opciones compra -->
                                    <div class="extras-buy-grid">

                                        <div
                                            v-for="extra in monitoringExtrasList"
                                            :key="extra.id"
                                            class="extras-buy-card"
                                            :class="{ highlighted: extra.highlighted }"
                                        >
                                            <div class="extras-buy-card-top">
                                                <div>
                                                    <div class="extras-buy-title">
                                                        {{ extra.title }}
                                                    </div>

                                                    <div class="extras-buy-price">
                                                        {{ formatMoney(extra.price) }}/mes

                                                        <span>
                                                            {{ formatMoney(extra.price / extra.amount) }}/monitorización
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="extras-buy-icon">
                                                    <i :class="extra.icon"></i>
                                                </div>
                                            </div>

                                            <div v-if="extra.badge" class="extras-badge">
                                                {{ extra.badge }}
                                            </div>

                                            <div class="extras-buy-features">
                                                <div
                                                    v-for="feature in extra.features"
                                                    :key="feature"
                                                >
                                                    <i class="far fa-check"></i>
                                                    {{ feature }}
                                                </div>
                                            </div>

                                            <div class="extras-card-total">
                                                <span>Total mensual</span>
                                                <strong>{{ formatMoney(extra.price) }}/mes</strong>
                                            </div>

                                            <button
                                                type="button"
                                                class="extras-primary-button"
                                                v-on:click="buyRecurringExtra('monitoring', extra.id, 1)"
                                            >
                                                Añadir {{ extra.title.toLowerCase() }}
                                            </button>
                                        </div>

                                    </div>

                                    <!-- Extras contratados -->
                                    <div class="extras-history">
                                        <div class="extras-section-header">
                                            <div>
                                                <div class="extras-section-title">
                                                    Monitorizaciones extra activas
                                                </div>

                                                <div class="extras-section-subtitle">
                                                    Packs recurrentes añadidos a su suscripción.
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="monitoringRecurringItems.length" class="extras-history-table">
                                            <div class="extras-history-row extras-history-head">
                                                <div>Fecha</div>
                                                <div>Tipo</div>
                                                <div>Cantidad</div>
                                                <div>Precio mensual</div>
                                                <div>Acción</div>
                                            </div>

                                            <div
                                                v-for="item in monitoringRecurringItems"
                                                :key="item.id || item.stripe_subscription_item_id || item.createdAt"
                                                class="extras-history-row"
                                            >
                                                <div>{{ formatExtraDate(item.createdAt) }}</div>

                                                <div>
                                                    <strong>{{ item.title || item.type }}</strong>
                                                </div>

                                                <div>
                                                    <template v-if="item.quantity && item.quantity > 1">
                                                        x{{ item.quantity }} · +{{ item.amount }} monitorizaciones
                                                    </template>

                                                    <template v-else>
                                                        +{{ item.amount }} monitorizaciones
                                                    </template>
                                                </div>

                                                <div>{{ formatMoney(item.total_price || item.price || 0) }}/mes</div>

                                                <div>
                                                    <button
                                                        v-if="!item.cancel_at_period_end"
                                                        type="button"
                                                        class="custom-button"
                                                        data-size="small"
                                                        data-bg="rojo"
                                                        data-mode="translucent"
                                                        v-on:click="cancelRecurringExtra('monitoring', item)"
                                                    >
                                                        Cancelar
                                                    </button>

                                                    <span v-else class="extras-muted">
                        Se cancelará el {{ formatExtraDate(item.cancelAt) }}
                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else class="extras-empty-history">
                                            <i class="far fa-receipt"></i>
                                            <div>
                                                Todavía no hay monitorizaciones extra contratadas.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- LLAMADAS -->
                                <div v-if="activeExtrasTab === 'calls'" class="extras-tab-content">

                                    <!-- Resumen -->
                                    <div class="extras-summary-card">
                                        <div class="extras-summary-icon">
                                            <i class="far fa-phone"></i>
                                        </div>

                                        <div class="extras-summary-info">
                                            <div class="extras-summary-title">
                                                Minutos de llamadas disponibles
                                            </div>

                                            <div class="extras-summary-text">
                                                Minutos comprados pendientes de usar.
                                            </div>
                                        </div>

                                        <div class="extras-summary-number">
                                            <strong>{{ callsExtra.remaining || 0 }}</strong>
                                            <span>minutos</span>
                                        </div>
                                    </div>

                                    <!-- Opciones compra -->
                                    <div class="extras-buy-grid">
                                        <div
                                            v-for="extra in callsExtrasList"
                                            :key="extra.id"
                                            class="extras-buy-card"
                                            :class="{ highlighted: extra.highlighted }"
                                        >
                                            <div class="extras-buy-card-top">
                                                <div>
                                                    <div class="extras-buy-title">
                                                        {{ extra.title }}
                                                    </div>

                                                    <div class="extras-buy-price">
                                                        {{ formatMoney(extra.price) }}
                                                        <span v-if="extra.amount > 1">
                                                            {{ formatMoney(extra.price / extra.amount) }}/minuto
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="extras-buy-icon">
                                                    <i :class="extra.icon"></i>
                                                </div>
                                            </div>

                                            <div v-if="extra.badge" class="extras-badge">
                                                {{ extra.badge }}
                                            </div>

                                            <div class="extras-buy-features">
                                                <div
                                                    v-for="feature in extra.features"
                                                    :key="feature"
                                                >
                                                    <i class="far fa-check"></i>
                                                    {{ feature }}
                                                </div>
                                            </div>

                                            <div class="extras-card-total">
                                                <span>Total</span>
                                                <strong>{{ formatMoney(extra.price) }}</strong>
                                            </div>

                                            <button type="button" class="extras-primary-button" v-on:click="buyOneTimeExtra('calls', extra.id, 1)">
                                                Comprar {{ extra.title.toLowerCase() }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Historial -->
                                    <div class="extras-history">
                                        <div class="extras-section-header">
                                            <div>
                                                <div class="extras-section-title">
                                                    Historial de compras de llamadas
                                                </div>

                                                <div class="extras-section-subtitle">
                                                    Compras realizadas y minutos añadidos a su cuenta.
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="callsPurchases.length" class="extras-history-table">
                                            <div class="extras-history-row extras-history-head">
                                                <div>Fecha</div>
                                                <div>Tipo</div>
                                                <div>Cantidad</div>
                                                <div>Precio</div>
                                                <div>Recibo</div>
                                            </div>

                                            <div v-for="purchase in callsPurchases" :key="purchase.stripe_checkout_session_id || purchase.purchasedAt" class="extras-history-row">
                                                <div>{{ formatExtraDate(purchase.purchasedAt) }}</div>

                                                <div>
                                                    <strong>{{ purchase.title || purchase.type }}</strong>
                                                </div>

                                                <div>+{{ purchase.amount }} minutos</div>

                                                <div>{{ formatMoney(purchase.price_paid) }}</div>

                                                <div>
                                                    <a v-if="purchase.receipt_url" :href="purchase.receipt_url" target="_blank" class="custom-button" data-size="small" data-bg="principal" data-mode="translucent">
                                                        Ver recibo
                                                    </a>

                                                    <span v-else class="extras-muted">—</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else class="extras-empty-history">
                                            <i class="far fa-receipt"></i>
                                            <div>
                                                Todavía no hay compras de minutos de llamadas.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!--Incluido en el plan-->
                    <div v-if="planUsage" class="w-100">
                        <p class="text italic" data-size="18" data-weight="700">
                            Con tu plan
                        </p>

                        <div v-for="type in planUsage" :key="type.key" class="my-10">
                            <div class="d-flex justify-between text">
                                <p data-size="13">{{ type.title }}</p>

                                <p data-size="13">
                                    {{ type.used }}/
                                    <i class="fas fa-infinity" v-if="type.max === null"></i>
                                    <span v-else>{{ type.max }}</span>
                                </p>
                            </div>

                            <div class="progress-bar subscription">
                                <div :class="progressClass(type)"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Extras -->
                    <div v-if="extrasUsage" class="w-100 mt-50">
                        <p class="text italic" data-size="18" data-weight="700">
                            Extras
                        </p>

                        <div v-for="type in extrasUsage" :key="type.key" class="my-10">
                            <div class="d-flex justify-between text">
                                <p data-size="13">{{ type.title }}</p>

                                <p data-size="13">
                                    {{ type.used }}/
                                    <i class="fas fa-infinity" v-if="type.max === null"></i>
                                    <span v-else>{{ type.max }}</span>
                                </p>
                            </div>

                            <div class="progress-bar subscription">
                                <div :class="progressClass(type)"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Excesos -->
                    <div v-if="excessesUsage.length" class="subscription-excesses">
                        <h3>Excesos</h3>

                        <div v-for="excess in excessesUsage" :key="excess.key" class="subscription-excess-card">
                            <div class="subscription-excess-icon">
                                <i class="far fa-triangle-exclamation"></i>
                            </div>

                            <div class="subscription-excess-info">
                                <div class="subscription-excess-title">
                                    {{ excess.title }}
                                </div>

                                <div class="subscription-excess-text">
                                    {{ excess.text }}
                                </div>
                            </div>

                            <div class="subscription-excess-amount">
                                {{ excess.amount }} {{ excess.unit }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="basicData && basicData?.userLogged?.label === 'Usuario subdominio'">
                <p class="text mt-30 mb-20" data-size="17" data-weight="700">
                    Suscripción <i class="fas fa-euro-sign ml-10"></i>
                </p>
                <div class="d-flex justify-center align-center" data-gap="20">
                    <p class="text">Todavía no tienes una suscripción.</p>
                    <div
                        class="custom-button"
                        data-size="medium"
                        data-style="gradient"
                        @click="actionLink('/zoco-one')"
                    >
                        Suscríbete ahora
                    </div>
                </div>
            </div>

            <!--Documentos de usuario-->
            <div class="docs no-scroll" v-if="isSeeingDocs">
                <div class="d-flex justify-between">
                    <p class="text" data-size="15" data-weight="700">
                        Docs. adjuntos
                    </p>

                    <div class="custom-select no-hover seeing">
                        <div
                            class="custom-button ml-auto my-auto"
                            data-size="small"
                            data-bg="amarillo"
                            v-if="isEditing"
                            v-on:click="isSeeingTypeFile = !isSeeingTypeFile"
                        >
                            <i class="fas fa-plus"></i>
                        </div>

                        <div
                            v-if="isSeeingTypeFile"
                            class="select-content form w-260-px"
                        >
                            <div
                                class="text"
                                v-on:click="manageOpenDialog('private')"
                            >
                                <i class="fas fa-lock ml-4 mr-10"></i>
                                Documento privado
                            </div>

                            <div
                                class="text"
                                v-on:click="manageOpenDialog('public')"
                            >
                                <i class="fas fa-lock-open ml-4 mr-10"></i>
                                Documento público
                            </div>
                        </div>
                    </div>
                </div>

                <input
                    id="userFiles"
                    type="file"
                    style="display: none"
                    multiple
                    v-on:change="pickupDoc"
                />

                <div class="div-content">
                    <doc-component
                        v-for="(doc, docInd) in userToModify.docs"
                        :doc="doc"
                        :docInd="docInd"
                        :publicOrPrivate="true"
                        :isReadOnly="!isEditing"
                        directory="profile_images"
                        @delDoc="delDoc(docInd)"
                    ></doc-component>
                </div>

                <div class="separator"></div>

                <div class="d-flex justify-end">
                    <button
                        class="custom-button mr-10"
                        data-size="small"
                        data-bg="rojo"
                        v-on:click.prevent="isSeeingDocs = false"
                    >
                        Cerrar
                    </button>
                </div>
            </div>

            <!-- QR del comparador -->
            <div class="docs no-scroll qr-modal-mobile" v-if="isSeeingQR">

                <div class="d-flex justify-between qr-modal-mobile-header">
                    <p class="text" data-size="15" data-weight="700">
                        Tu QR del comparador
                    </p>
                </div>

                <div
                    class="div-content d-flex qr-modal-mobile-content"
                    style="
                        gap:40px;
                        align-items:flex-start;
                        flex-wrap:wrap;
                    "
                >

                    <div
                        class="qr-modal-mobile-top"
                        style="
                            width:100%;
                            display:flex;
                            flex-direction:column;
                            align-items:center;
                            justify-content:center;
                        "
                    >

                        <canvas ref="qrCanvas"></canvas>

                        <p class="text mt-15 qr-modal-mobile-link" style="font-size:12px;text-align:center;word-break:break-all;max-width:220px;">
                            https://{{ basicData.enterprise.url }}/comparator?ref={{ userToModify._id }}
                        </p>
                        <div class="d-flex mt-10 qr-actions">
                            <div class="custom-button" data-size="small" @click="copyComparatorLink">
                                Copiar link
                            </div>
                            <div class="custom-button ml-10" data-size="small" @click="downloadComparatorQR">
                                Descargar QR
                            </div>
                        </div>

                    </div>

                    <div class="qr-modal-mobile-list" style="flex:1; min-width:260px;">

                        <p class="text mb-15" data-weight="600">
                            Comercializadoras visibles en tu comparador
                        </p>

                            <div
                                v-for="marketer in marketers
                                    .filter(m => true)
                                    .sort((a,b) => a.name.localeCompare(b.name))"
                                :key="marketer._id"
                                class="form-group my-10"
                            >

                                <div
                                    class="d-flex align-center justify-between"
                                    :style="{
                                        opacity: isMarketerVisible(marketer._id) ? 1 : 0.5
                                    }"
                                >

                                    <div class="d-flex align-center">

                                        <div
                                            @click="isEditing && toggleMarketer(marketer._id)"
                                            :class="{'pointer': isEditing}"
                                            class="mr-10"
                                        >
                                            <i
                                                :class="[
                                                    isMarketerVisible(marketer._id)
                                                        ? 'far fa-eye text'
                                                        : 'far fa-eye-slash'
                                                ]"
                                            ></i>
                                        </div>

                                        <div class="w-35-px d-flex justify-center mr-10">
                                            <img
                                                :src="`/assets/marketers_logo/${marketer.logo}`"
                                                class="h-25-px-max w-30-px-max contain-image"
                                            />
                                        </div>

                                        <p class="text">{{ marketer.name }}</p>

                                    </div>

                                    <div
                                        v-if="isMarketerVisible(marketer._id)"
                                        @click="toggleProductsDropdown(marketer._id)"
                                        class="pointer ml-10"
                                    >
                                        <i
                                            :class="[
                                                isProductsDropdownOpen(marketer._id)
                                                    ? 'fas fa-chevron-up'
                                                    : 'fas fa-chevron-down'
                                            ]"
                                        ></i>
                                    </div>

                                </div>

                                <div
                                    v-if="isMarketerVisible(marketer._id) && isProductsDropdownOpen(marketer._id)"
                                    class="mt-10 ml-35"
                                    style="border-left:1px solid #e5e7eb; padding-left:14px;"
                                >

                                    <p class="text mb-8" style="font-size:12px;" data-weight="600">
                                        Productos visibles
                                    </p>

                                    <div
                                        v-for="(product, productIndex) in getMarketerProducts(marketer)"
                                        :key="getProductKey(marketer, product, productIndex)"
                                        class="d-flex align-center my-6"
                                        :style="{ opacity: isProductVisible(marketer, product, productIndex) ? 1 : 0.45 }"
                                    >

                                        <div
                                            @click.stop="isEditing && !isProductBlockedBySubdomain(marketer, product, productIndex) && toggleProduct(marketer, product, productIndex)"
                                            :class="{
                                                'pointer': isEditing && !isProductBlockedBySubdomain(marketer, product, productIndex),
                                                'blocked-by-subdomain': isProductBlockedBySubdomain(marketer, product, productIndex)
                                            }"
                                            class="mr-10"
                                            :title="isProductBlockedBySubdomain(marketer, product, productIndex) ? 'Bloqueado por el subdominio' : ''"
                                        >
                                            <i
                                                :class="[
                                                    isProductVisible(marketer, product, productIndex)
                                                        ? 'far fa-eye text'
                                                        : 'far fa-eye-slash'
                                                ]"
                                            ></i>
                                        </div>

                                        <p class="text" style="font-size:13px;">
                                            {{ getProductName(product) }}
                                        </p>

                                    </div>

                                    <p
                                        v-if="getMarketerProducts(marketer).length === 0"
                                        class="text"
                                        style="font-size:12px; opacity:.6;"
                                    >
                                        Esta comercializadora no tiene productos cargados.
                                    </p>

                                </div>

                            </div>

            </div>

                </div>

                <div class="separator"></div>

                <div class="d-flex justify-end">
                    <button
                        class="custom-button mr-10"
                        data-size="small"
                        data-bg="rojo"
                        v-on:click.prevent="isSeeingQR = false"
                    >
                        Cerrar
                    </button>
                </div>

            </div>

        </form>
    </div>
</template>

<script>
import QRCode from "qrcode";
import draggable from 'vuedraggable';
export default {
    name: "ProfileComponent",
    props: ["basicData"],
    components: {
        draggable
    },
    data() {
        return {
            urlImage: "",
            userToModify: {},
            enterpriseToModify: {},
            commissionRanges: [],
            commissionRangeSelected: null,
            isEditing: false,
            isSeeingQR: false,
            password: {
                old: "",
                new: "",
                repeat: "",
                errors: {},
            },
            isOldPasswordCorrect: false,
            errors: {},
            isSeeingDocs: false,
            isSeeingTypeFile: false,
            privacyType: null,
            marketers: [],
            loadingMarketers: false,
            showCommissionRanges: false,

            //extras
            showExtrasModal: false,
            expandedProductMarketers: [],
            inheritedComparatorHiddenProducts: [],
            activeExtrasTab: 'scans',
            isLoadingExtraCheckout: false,
            availableExtras: null,
            scansUnitQuantity: 10,
            extrasTabs: [
                {
                    key: 'scans',
                    title: 'Escaneos',
                    icon: 'far fa-file-magnifying-glass',
                    plansExcluded: [2]
                },
                {
                    key: 'users',
                    title: 'Usuarios',
                    icon: 'far fa-users',
                    plansExcluded: [2]
                },
                {
                    key: 'monitoring',
                    title: 'Monitorizaciones',
                    icon: 'far fa-chart-line',
                    plansExcluded: [2]
                },
                {
                    key: 'calls',
                    title: 'Minutos llamadas',
                    icon: 'far fa-phone',
                    plansExcluded: []
                },
            ]
        };
    },
    created() {
        //this.getSubscription();
    },
    mounted() {
        if (this.basicData.userLogged && this.basicData.userLogged._id)
            this.userToModify = JSON.parse(
                JSON.stringify(this.basicData.userLogged)
            );

         if (!this.userToModify.settings) {
            this.userToModify.settings = {};
        }

        if (typeof this.userToModify.settings.showProductPricesByPeriods === "undefined") {
            this.userToModify.settings.showProductPricesByPeriods = false;
        }
        if (typeof this.userToModify.settings.orderCupsValidation === "undefined") {
            this.userToModify.settings.orderCupsValidation = true;
        }
        if (this.basicData.enterprise && this.basicData.enterprise.commissionRanges){
            this.commissionRanges = JSON.parse(JSON.stringify(this.basicData.enterprise.commissionRanges));
        }

        this.enterpriseToModify = JSON.parse(JSON.stringify(this.basicData.enterprise));

        this.$nextTick(() => {

            const url = "https://" + this.basicData?.enterprise?.url + "/comparator?ref=" + this.userToModify._id;

            QRCode.toCanvas(this.$refs.qrCanvas, url, {
                width: 170
            });

        });

        this.fetchMarketers();
        if(!this.userToModify.marketers){
            this.userToModify.marketers = []
        }

        if(!this.userToModify.comparatorMarketers){
            this.userToModify.comparatorMarketers = []
        }

        if(!this.userToModify.comparatorHiddenProducts){
            this.userToModify.comparatorHiddenProducts = []
        }

        this.syncInheritedComparatorHiddenProducts()

        this.userToModify.comparatorHiddenProducts =
            this.normalizeComparatorHiddenProductKeys(
                this.userToModify.comparatorHiddenProducts
            )

        this.fetchExtras();
    },
    watch: {
        "basicData.userLogged"() {
            if (this.basicData.userLogged) {
                this.userToModify = JSON.parse(
                    JSON.stringify(this.basicData.userLogged)
                );

                if (!this.userToModify.settings) {
                    this.userToModify.settings = {};
                }

                if (typeof this.userToModify.settings.showProductPricesByPeriods === "undefined") {
                    this.userToModify.settings.showProductPricesByPeriods = false;
                }
                if (typeof this.userToModify.settings.orderCupsValidation === "undefined") {
                    this.userToModify.settings.orderCupsValidation = true;
                }

                if(!this.userToModify.comparatorHiddenProducts){
                    this.userToModify.comparatorHiddenProducts = []
                }

                this.syncInheritedComparatorHiddenProducts()
            }
        },
        "basicData.enterprise"(){
            if (this.basicData.enterprise) {
                this.enterpriseToModify = JSON.parse(
                    JSON.stringify(this.basicData.enterprise)
                );
                this.commissionRanges = JSON.parse(JSON.stringify(this.basicData.enterprise.commissionRanges))
            }
        },
        "basicData.subscription"() {
            if (this.basicData.subscription)
                if (this.basicData.subscription.plan_id === 2)
                    this.activeExtrasTab = 'calls';
        },
        isSeeingQR(newValue) {
            this.toggleBackgroundScrollForQr(newValue);
        }
    },
    methods: {
        toggleBackgroundScrollForQr(lock) {
            if (window.innerWidth > 810) return;

            const body = document.body;
            const html = document.documentElement;

            if (lock) {
                this.prevBodyOverflow = body.style.overflow;
                this.prevHtmlOverflow = html.style.overflow;
                body.style.overflow = "hidden";
                html.style.overflow = "hidden";
            } else {
                body.style.overflow = this.prevBodyOverflow || "";
                html.style.overflow = this.prevHtmlOverflow || "";
            }
        },
        cancelChanges() {
            this.isEditing = false;

            this.userToModify = JSON.parse(
                JSON.stringify(this.basicData.userLogged)
            );
        },
        saveProfile() {
            this.errors = {};
            let hasErrors = false;

            //Nombre
            this.firstName = this.userToModify.firstName.trim();
            if (this.isEmpty(this.userToModify.firstName)) {
                this.errors.firstName = this.getErrorMessage("isEmpty");
                hasErrors = true;
            }

            //Apellidos
            this.userToModify.lastName = this.userToModify.lastName.trim();
            if (this.isEmpty(this.userToModify.lastName)) {
                this.errors.lastName = this.getErrorMessage("isEmpty");
                hasErrors = true;
            }

            //Género
            this.userToModify.gender = this.userToModify.gender.trim();
            if (this.isEmpty(this.userToModify.gender)) {
                this.errors.gender = this.getErrorMessage("isEmpty");
                hasErrors = true;
            }

            //DNI
            this.userToModify.dni = this.userToModify.dni.trim();
            let regex = /^\d{8}[A-Z]$/;
            if (this.isEmpty(this.userToModify.dni)) {
                this.errors.dni = this.getErrorMessage("isEmpty");
                hasErrors = true;
            } /*else {
                if(!regex.test(this.userToModify.dni)){
                    this.errors.dni = 'Esta cadena no es del tipo DNI'
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
            if (this.isEmpty(this.userToModify.email)) {
                this.errors.email = this.getErrorMessage("isEmpty");
                hasErrors = true;
            }
            regex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
            if (!regex.test(this.userToModify.email)) {
                this.errors.email = "Esta cadena no es del tipo email";
                hasErrors = true;
            }

            //teléfono
            this.userToModify.phone = this.userToModify.phone.trim();
            if (this.isEmpty(this.userToModify.phone)) {
                this.errors.phone = this.getErrorMessage("isEmpty");
                hasErrors = true;
            }
            if (!this.hasOnlyNumbers(this.userToModify.phone)) {
                this.errors.phone = this.getErrorMessage("hasOnlyNumbers");
                hasErrors = true;
            }

            //Dirección
            this.userToModify.address = this.userToModify.address.trim();

            //Código Postal
            this.userToModify.postal = this.userToModify.postal.trim();
            if (!!this.userToModify.postal && this.userToModify.postal !== '' && !this.hasOnlyNumbers(this.userToModify.postal)) {
                this.errors.postal = this.getErrorMessage("hasOnlyNumbers");
                hasErrors = true;
            }

            //Docs
            this.userToModify.docs.forEach((doc) => {
                if (doc.title === "" && doc.defaultTitle)
                    doc.title = doc.defaultTitle;
            });

            if (hasErrors) {
                Swal.fire({
                    icon: "error",
                    title: "Comprueba todo...",
                    text: "Parece que hay fallos en algún campo del formulario. Compruébalos y vuélvelo a intentar",
                });
            } else {

                this.userToModify.comparatorHiddenProducts =
                    this.userToModify.comparatorHiddenProducts.filter(key => {
                        return !key.toString().includes('[object Object]')
                    })

                let data = new FormData();
                data.append("user", JSON.stringify(this.userToModify));
                data.append("enterprise", JSON.stringify(this.enterpriseToModify));

                //Meto los documentos de los pedidos
                if (this.userToModify.docs.length > 0) {
                    this.userToModify.docs.forEach((doc, docInd) => {
                        data.append("docFile" + docInd, doc.fileValue);
                    });
                }

                axios
                    .post("/api/user/update", data)
                    .then((res) => {
                        Swal.fire({
                            icon: "success",
                            title: "Perfil actualizado",
                            html: `Tu perfil ha sido actualizado correctamente!`,
                        }).then((res) => {
                            this.isEditing = false;
                            this.isSeeingTypeFile = false;
                            this.isSeeingDocs = false;
                        });
                    })
                    .catch((err) => {
                        console.log(err);
                        this.errors = err;
                    });
            }
        },
        isEmpty(value) {
            return value === "";
        },
        hasOnlyNumbers(value) {
            return value !== "" && /^\d+$/.test(value);
        },
        getErrorMessage(code, meta) {
            switch (code) {
                case "hasOnlyNumbers":
                    return "Este campo solo puede contener números";
                case "isEmpty":
                    return "No puedes dejarlo vacío";
                case "isDecimal":
                    return "Debe ser un número entero o decimal";
                case "isBetween":
                    return "Debe estar entre " + meta.min + " y " + meta.max;
                case "isDuplicated":
                    return "Este campo se encuentra duplicado";
                case "noSelected":
                    return "Tienes que seleccionar algún campo";
                default:
                    return "Hay un problema que no sé cuál es...";
            }
        },
        copyComparatorLink(){

            const link = "https://" + this.basicData?.enterprise?.url + "/comparator?ref=" + this.userToModify._id

            navigator.clipboard.writeText(link)

            Swal.fire({
                icon: "success",
                title: "Link copiado",
                timer: 1200,
                showConfirmButton: false
            })

        },
        downloadComparatorQR() {

            const qrCanvas = this.$refs.qrCanvas

            if (!qrCanvas) {
                Swal.fire({
                    icon: "error",
                    title: "No se ha podido descargar",
                    text: "No hemos encontrado el QR para descargarlo."
                })
                return
            }

            const fileName = `qr-comparador-${this.userToModify._id}.png`

            const triggerDownload = (url) => {
                const link = document.createElement("a")
                link.href = url
                link.download = fileName
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
            }

            if (qrCanvas.toBlob) {
                qrCanvas.toBlob((blob) => {
                    if (!blob) {
                        Swal.fire({
                            icon: "error",
                            title: "No se ha podido descargar",
                            text: "No hemos podido generar la imagen del QR."
                        })
                        return
                    }

                    const url = URL.createObjectURL(blob)
                    triggerDownload(url)
                    URL.revokeObjectURL(url)
                }, "image/png")
                return
            }

            const dataUrl = qrCanvas.toDataURL("image/png")
            triggerDownload(dataUrl)

        },
        openDialog() {
            $("#profileImg").click();
        },
        pickupImage() {
            let input = $("#profileImg");

            if (input.prop("files")) {
                let file = input.prop("files")[0];

                if (
                    file["type"] === "image/jpeg" ||
                    file["type"] === "image/png" ||
                    file["type"] === "image/png"
                ) {
                    this.imageFile = file;

                    this.urlImage = URL.createObjectURL(this.imageFile);

                    this.updateImage();
                } else {
                    Swal.fire({
                        title: "Eh... Vaya",
                        text: "Lo que has tratado de subir no es una imagen. Tu imagen debe estar en formato .JPG o .PNG.",
                        icon: "info",
                    });
                }
            }
        },
        updateImage() {
            let config = {
                headers: { "content-type": "multipart/form-data" },
            };

            let data = new FormData();
            data.append("file", this.imageFile);

            this.isLoading = true;
            axios
                .post(
                    `/api/user/updateImage/${this.userToModify._id}`,
                    data,
                    config
                )
                .then((res) => {
                    Swal.fire({
                        title: "¡Eureka!",
                        text: "La imagen de perfil se actualizó con éxito",
                        icon: "success",
                    });

                    this.isLoading = false;

                    this.$emit("updateUser", res.data.user);

                    this.imageFile = "";
                })
                .catch((err) => {
                    this.errors = err.response.data.errors;
                });
        },
        checkOldPassword() {
            //Validaciones
            let hasErrors = false;

            if (!this.password.old) {
                this.password.errors.old = this.getErrorMessage("isEmpty");
                hasErrors = true;
            }

            if (!hasErrors) {
                axios
                    .post(`/api/user/checkOldPassword`, {
                        id: this.basicData.userLogged._id,
                        password: this.password.old,
                    })
                    .then((res) => {
                        this.isOldPasswordCorrect = res.data.isCorrect;
                    })
                    .catch((err) => {
                        this.password.errors.old =
                            "La contraseña no es correcta";
                    });
            }
        },
        changePassword() {
            //Validaciones
            let hasErrors = false;

            //Compruebo que no este ninguna de las contraseñas vacias
            if (!this.password.new) {
                this.password.errors.new = this.getErrorMessage("isEmpty");
                hasErrors = true;
            }

            if (!this.password.repeat) {
                this.password.errors.repeat = this.getErrorMessage("isEmpty");
                hasErrors = true;
            }

            //Compruebo que concuerden las dos contraseñas
            if (
                this.password.new &&
                this.password.repeat &&
                this.password.new !== this.password.repeat
            ) {
                this.password.errors.repeat = "Las contraseñas no coinciden";
                hasErrors = true;
            }

            if (!hasErrors) {
                axios
                    .put(`/api/user/changePassword`, {
                        id: this.basicData.userLogged._id,
                        password: this.password.new,
                    })
                    .then((res) => {
                        Swal.fire({
                            icon: "success",
                            title: "Contraseña actualizada",
                            timer: 1500,
                            timerProgressBar: true,
                        });

                        //Pongo el progreso desde el principio
                        this.password = {
                            old: "",
                            new: "",
                            repeat: "",
                            errors: {},
                        };

                        this.isOldPasswordCorrect = false;
                    })
                    .catch((err) => {
                        console.log(err);
                    });
            }
        },
        selectCommissionRange(range) {
            if(this.commissionRangeSelected === range) this.commissionRangeSelected = null;
            else this.commissionRangeSelected = range
        },
        addCommissionRange() {
            this.commissionRanges.push({})
        },
        deleteCommissionRange() {
            this.commissionRanges = this.commissionRanges.filter(r => r !== this.commissionRangeSelected)
        },
        resetCommissionRanges() {
            this.commissionRangeSelected = null;
            this.commissionRanges = JSON.parse(JSON.stringify(this.basicData.enterprise.commissionRanges))
        },
        updateCommissionRanges(){
            //Compruebo que todos los rangos tienen nombre y valor válido
            const hasInvalidCommissions = this.commissionRanges.some((range) => {
                const hasValidName =
                    range?.name != null && String(range.name).trim() !== '';

                const rawPercentage =
                    range?.percentage != null
                        ? String(range.percentage).trim().replace(',', '.')
                        : '';

                const parsedPercentage = Number(rawPercentage);

                const hasValidPercentage =
                    rawPercentage !== '' && Number.isFinite(parsedPercentage);

                return !hasValidName || !hasValidPercentage;
            });

            if (hasInvalidCommissions) {
                Swal.fire({
                    icon: 'error',
                    title: 'Fallos en los rangos de comisiones',
                    text: 'Por favor, corrige los errores antes de guardar los cambios.',
                })
                return;
            }

            axios.post(`/api/global/updateCommissionRanges`, { id: this.basicData.enterprise._id, commissionRanges: this.commissionRanges })
              .then((res) => {
                  Swal.fire({
                      icon: 'success',
                      title: "Rangos de comisiones actualizados",
                      timer: 1500,
                      timerProgressBar: true
                  })
              })
              .catch((err) => {
                  console.log(err)
                  Swal.fire({
                      icon: 'error',
                      title: "Hubo un error",
                      text: "Los rangos de comisiones no se pudieron actualizar",
                  })
              })
        },
        logout() {
            axios
                .get(`/api/auth/logout`)
                .then((res) => {
                    localStorage.removeItem("userLogged");

                    window.location.href = "/portal";

                    //Borro los filtros al cerrar sesión
                    this.$cookies.remove("filters");
                    sessionStorage.clear();
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        async fetchMarketers(){

            this.loadingMarketers = true

            const params = {
                from: 'profileComparator'
            }

            if (this.userToModify?._id) {
                params.assignContractTo = this.userToModify._id
                params.ref = this.userToModify._id
            }

            await axios.get('/api/marketers', { params })
                .then((res) => {
                    this.marketers = res.data.marketers || []
                })
                .catch((err) => {
                    console.log(err)
                })

            this.loadingMarketers = false
        },
        toggleMarketer(marketerId){

            if(!this.userToModify.comparatorMarketers){
                this.userToModify.comparatorMarketers = []
            }

            if(!this.userToModify.comparatorHiddenProducts){
                this.userToModify.comparatorHiddenProducts = []
            }

            if(!this.userToModify.marketers){
                this.userToModify.marketers = []
            }

            const isSelected = this.userToModify.comparatorMarketers
                ?.map(id => id.toString())
                .includes(marketerId.toString())

            if(isSelected){

                this.userToModify.comparatorMarketers =
                    this.userToModify.comparatorMarketers.filter(
                        id => id.toString() !== marketerId.toString()
                    )

                this.expandedProductMarketers =
                    this.expandedProductMarketers.filter(
                        id => id.toString() !== marketerId.toString()
                    )

            }else{

                const hasAccess = this.userToModify.marketers
                    .map(id => id.toString())
                    .includes(marketerId.toString())

                if(this.userToModify.label === 'Usuario subdominio' || hasAccess){
                    this.userToModify.comparatorMarketers.push(marketerId)
                }

            }

        },

        isMarketerVisible(marketerId) {
            if (!this.userToModify.comparatorMarketers) {
                return false
            }

            return this.userToModify.comparatorMarketers
                .map(id => id.toString())
                .includes(marketerId.toString())
        },

        toggleProductsDropdown(marketerId) {
            if (!this.expandedProductMarketers) {
                this.expandedProductMarketers = []
            }

            const id = marketerId.toString()

            const isOpen = this.expandedProductMarketers
                .map(item => item.toString())
                .includes(id)

            if (isOpen) {
                this.expandedProductMarketers =
                    this.expandedProductMarketers.filter(
                        item => item.toString() !== id
                    )
            } else {
                this.expandedProductMarketers.push(id)
            }
        },

        isProductsDropdownOpen(marketerId) {
            if (!this.expandedProductMarketers) {
                return false
            }

            return this.expandedProductMarketers
                .map(item => item.toString())
                .includes(marketerId.toString())
        },

        getMarketerProducts(marketer) {

            let products = []

            const addProducts = (items) => {

                if (!items) {
                    return
                }

                if (Array.isArray(items)) {
                    products = products.concat(items)
                    return
                }

                if (typeof items !== 'object') {
                    return
                }

                const possibleProductLists = [
                    items.products,
                    items.productList,
                    items.fees,
                    items.tariffs,
                    items.rates,
                    items.offers,
                    items.items,
                    items.data
                ]

                const foundList = possibleProductLists.find(list => Array.isArray(list))

                if (foundList) {
                    products = products.concat(foundList)
                    return
                }

                Object.values(items).forEach(value => {
                    if (Array.isArray(value)) {
                        products = products.concat(value)
                    }
                })
            }

            if (marketer.commissions && typeof marketer.commissions === 'object') {
                Object.values(marketer.commissions).forEach(productTypeData => {
                    addProducts(productTypeData)
                })
            }

            addProducts(marketer.products)
            addProducts(marketer.productList)
            addProducts(marketer.fees)

            return products
        },

        getProductKey(marketer, product, productIndex = null) {

            const normalizeId = (value) => {
                if (!value) {
                    return ''
                }

                if (typeof value === 'string' || typeof value === 'number') {
                    return value.toString()
                }

                if (value.$oid) {
                    return value.$oid.toString()
                }

                if (value.oid) {
                    return value.oid.toString()
                }

                return ''
            }

            const marketerId = normalizeId(marketer._id) || marketer._id?.toString()

            let productId = ''

            if (typeof product === 'string' || typeof product === 'number') {
                productId = product.toString()
            } else {
                const possibleIds = [
                    product._id,
                    product.id,
                    product.productId,
                    product.product_id,
                    product.name,
                    product.product,
                    product.productName,
                    product.product_name,
                    product.title,
                    product.label,
                    product.nombre,
                    product.rateName,
                    product.tariffName,
                    product.tariff,
                    product.tarifa,
                    product.rate,
                    product.feeName,
                    product.fee
                ]

                for (const id of possibleIds) {
                    const normalized = normalizeId(id)

                    if (normalized) {
                        productId = normalized
                        break
                    }
                }
            }

            if (!productId) {
                productId = 'product'
            }

            return marketerId + '|' + productId.toString() + '|' + productIndex
        },

        getProductName(product) {
            if (typeof product === 'string' || typeof product === 'number') {
                return product.toString()
            }

            return (
                product.name ||
                product.product ||
                product.productName ||
                product.product_name ||
                product.title ||
                product.label ||
                product.nombre ||
                product.rateName ||
                product.tariffName ||
                product.tariff ||
                product.tarifa ||
                product.rate ||
                product.feeName ||
                product.fee ||
                product._key ||
                'Producto sin nombre'
            )
        },

        normalizeComparatorHiddenProductKeys(list = []) {

            if (!Array.isArray(list)) {
                return []
            }

            return Array.from(new Set(
                list
                    .map(key => key ? key.toString() : '')
                    .filter(key => key && !key.includes('[object Object]'))
                    .map(key => {
                        const parts = key.split('|')

                        if (parts.length >= 2) {
                            return parts[0] + '|' + parts[1]
                        }

                        return key
                    })
            ))

        },

        syncInheritedComparatorHiddenProducts() {

            this.inheritedComparatorHiddenProducts = []

            if (
                this.basicData?.userLogged?.label === 'Usuario subdominio' &&
                this.userToModify?._id &&
                this.basicData.userLogged?._id &&
                this.userToModify._id.toString() !== this.basicData.userLogged._id.toString()
            ) {
                this.inheritedComparatorHiddenProducts =
                    this.normalizeComparatorHiddenProductKeys(
                        this.basicData.userLogged.comparatorHiddenProducts || []
                    )
            }

        },

        isProductHiddenInList(marketer, product, productIndex = null, list = []) {

            const keyWithIndex = this.getProductKey(marketer, product, productIndex)

            const keyParts = keyWithIndex.split('|')

            if (keyParts.length < 2) {
                return false
            }

            const keyWithoutIndex = keyParts[0] + '|' + keyParts[1]

            const normalizedList = this.normalizeComparatorHiddenProductKeys(list)

            return normalizedList.includes(keyWithoutIndex)

        },

        isProductBlockedBySubdomain(marketer, product, productIndex = null) {

            return this.isProductHiddenInList(
                marketer,
                product,
                productIndex,
                this.inheritedComparatorHiddenProducts
            )

        },

        isProductVisible(marketer, product, productIndex = null) {
            if (!this.userToModify.comparatorHiddenProducts) {
                this.userToModify.comparatorHiddenProducts = []
            }

            if (this.isProductBlockedBySubdomain(marketer, product, productIndex)) {
                return false
            }

            return !this.isProductHiddenInList(
                marketer,
                product,
                productIndex,
                this.userToModify.comparatorHiddenProducts
            )
        },

        toggleProduct(marketer, product, productIndex = null) {
            if (!this.userToModify.comparatorHiddenProducts) {
                this.userToModify.comparatorHiddenProducts = []
            }

            if (this.isProductBlockedBySubdomain(marketer, product, productIndex)) {
                return
            }

            const keyWithIndex = this.getProductKey(marketer, product, productIndex)
            const keyParts = keyWithIndex.split('|')

            if (keyParts.length < 2) {
                return
            }

            const keyWithoutIndex = keyParts[0] + '|' + keyParts[1]

            const isHidden = this.isProductHiddenInList(
                marketer,
                product,
                productIndex,
                this.userToModify.comparatorHiddenProducts
            )

            if (isHidden) {
                this.userToModify.comparatorHiddenProducts =
                    this.normalizeComparatorHiddenProductKeys(
                        this.userToModify.comparatorHiddenProducts
                    ).filter(key => key !== keyWithoutIndex)
            } else {
                this.userToModify.comparatorHiddenProducts =
                    this.normalizeComparatorHiddenProductKeys(
                        this.userToModify.comparatorHiddenProducts
                    )

                this.userToModify.comparatorHiddenProducts.push(keyWithoutIndex)
            }

            this.saveComparatorVisibilitySilently()
        },

        saveComparatorVisibilitySilently() {
            if (!this.userToModify.comparatorMarketers) {
                this.userToModify.comparatorMarketers = []
            }

            if (!this.userToModify.comparatorHiddenProducts) {
                this.userToModify.comparatorHiddenProducts = []
            }

            this.userToModify.comparatorHiddenProducts =
                this.normalizeComparatorHiddenProductKeys(
                    this.userToModify.comparatorHiddenProducts
                )

            let data = new FormData()

            data.append("user", JSON.stringify(this.userToModify))
            data.append("enterprise", JSON.stringify(this.enterpriseToModify))

            axios
                .post("/api/user/update", data)
                .then((res) => {
                    console.log("Visibilidad del comparador guardada", {
                        comparatorMarketers: JSON.parse(JSON.stringify(this.userToModify.comparatorMarketers || [])),
                        comparatorHiddenProducts: JSON.parse(JSON.stringify(this.userToModify.comparatorHiddenProducts || []))
                    })
                })
                .catch((err) => {
                    console.error("Error guardando visibilidad del comparador", err)
                })
        },

        generateQR() {

            const link = "https://" + this.basicData?.enterprise?.url + "/comparator?ref=" + this.userToModify._id

            this.$nextTick(() => {

                if(this.$refs.qrCanvas){

                    QRCode.toCanvas(this.$refs.qrCanvas, link, {
                        width: 200
                    })

                }

            })

        },
        openQR(){

            this.isSeeingQR = true

            this.generateQR()

        },
        async getSubscription() {
            await axios.get(`/api/stripe/subscription`).then((response) => {
                if (response.data) {
                    //Obtengo el estado
                    let status;
                    switch (response.data.status) {
                        case "active":
                            status = {
                                label: "Activo",
                                color: "success",
                                icon: "fa-circle-check",
                            };
                            break;
                        case "canceled":
                            status = {
                                label: "Cancelado",
                                color: "rojo",
                                icon: "fa-circle-xmark",
                            };
                            break;
                        case "unpaid":
                            status = {
                                label: "Fallo al pagar",
                                color: "rojo",
                                icon: "fa-circle-exclamation",
                            };
                            break;
                        default:
                            status = {
                                label: "Error",
                                color: "rojo",
                                icon: "fa-circle-exclamation",
                            };
                    }

                    //Obtengo la fecha de renovación
                    let dueDate = moment
                        .unix(response.data.items.data[0].current_period_end)
                        .format("LL");
                    //Obtengo el periodo de facturación
                    let interval =
                        response.data.items.data[0].plan.interval === "month"
                            ? "Mensual"
                            : "Anual";
                    //Obtengo la situación de la cancelación
                    let canceled = response.data.cancel_at_period_end;

                    this.subscription = { status, dueDate, interval, canceled };
                }
            });
        },
        async updateSubscription(newStatus) {
            let title, successTitle;
            switch (newStatus) {
                case "resume":
                    title =
                        "¿Estás seguro que quieres reanudar tu suscripción?";
                    successTitle =
                        "Tu suscripción ha sido reanudada correctamente.";
                    break;
                case "cancel":
                    title =
                        "¿Estás seguro que quieres cancelar tu suscripción?";
                    successTitle =
                        "Tu suscripción ha sido cancelada correctamente.";
                    break;
            }

            Swal.fire({
                icon: "warning",
                title,
                confirmButtonText: "Vale",
                showCancelButton: true,
            }).then((response) => {
                if (response.isConfirmed) {
                    axios
                        .post("/api/stripe/updateSubscription", { newStatus })
                        .then((response) => {
                            Swal.fire({
                                title: successTitle,
                                icon: "success",
                                confirmButtonText: "Vale",
                                timerProgressBar: true,
                                timer: 1500,
                            });
                        });
                    this.getSubscription();
                }
            });
        },
        async downloadSubscriptionInvoice() {
            await axios
                .get("/api/stripe/subscriptionInvoice")
                .then((response) => {
                    window.open(response.data, "_blank");
                });
        },
        async changeSubscriptionInterval() {
            Swal.fire({
                icon: "warning",
                title: "¿Estás seguro que quieres cambiar la periodicidad de tu suscripción?",
                confirmButtonText: "Vale",
                showCancelButton: true,
            }).then((response) => {
                if (response.isConfirmed) {
                    let interval =
                        this.subscription.interval === "Mensual"
                            ? "year"
                            : "month";
                    axios
                        .post("/api/stripe/changeSubscriptionInterval", {
                            interval,
                        })
                        .then((response) => {
                            Swal.fire({
                                title: "Periodicidad cambiada satisfactoriamente.",
                                icon: "success",
                                confirmButtonText: "Vale",
                                timerProgressBar: true,
                                timer: 1500,
                            });
                        });
                    this.getSubscription();
                }
            });
        },
        manageOpenDialog(privacyType) {
            this.privacyType = privacyType;
            this.isSeeingTypeFile = false;
            this.openDialogDoc();
        },
        openDialogDoc() {
            $("#userFiles").click();
        },
        pickupDoc() {
            let input = $("#userFiles");
            if (input.prop("files")) {
                let files = input.prop("files");

                //Para cada uno de los archivos seleccionados
                for (let file of files) {
                    //Añado el doc al pedido
                    let docInfo = {
                        title: "",
                        defaultTitle: file.name,
                        value: "", //Aqui se va a guardar el nombre del archivo
                        fileValue: file, //Aqui se va a meter el archivo en sí,
                        privacyType: this.privacyType,
                        creator: this.basicData.userLogged._id,
                        errors: {},
                    };

                    //Meto el doc en el pedido
                    this.userToModify.docs.push(docInfo);
                }
            }
        },
        delDoc(ind) {
            //Compruebo si hay que borrarlo dela bbdd( como uso el mismo componente para crear y editar puede o no que se haya guardado)
            this.userToModify.docs.splice(ind, 1);
        },
        actionLink(route) {
            this.$router.push(route);
        },
        toggleContractEmail() {
            //Si no existe
            if (!this.userToModify.contractEmail) {
                this.userToModify.contractEmail = {
                    subject: "Nuevo contrato creado 📄",
                    message:
                        "<p>Hola <strong><em>" +
                        this.userToModify.firstName +
                        " " +
                        this.userToModify.lastName +
                        "</em></strong>,</p><p><br></p><p>Te informamos que se ha creado un nuevo contrato en tu cuenta de <strong><em>" +
                        this.basicData.enterprise.name +
                        " CRM</em></strong>.</p><p><br></p><p>Puedes acceder a tu cuenta para revisar los detalles del contrato y gestionar tus servicios energéticos de manera eficiente.</p><p><br></p><p>Si tienes alguna pregunta o necesitas asistencia, no dudes en ponerte en contacto con nuestro equipo de soporte.</p><p><br></p><p>Gracias por confiar en <em>" +
                        this.basicData.enterprise.name +
                        "</em> como tu socio en la gestión energética.</p><p><br></p><p>Un saludo,</p><p>El equipo de <strong>" +
                        this.basicData.enterprise.name +
                        "</strong></p>",
                    docs: [],
                    active: true,
                };
            } else {
                //Si esta activado
                if (this.userToModify.contractEmail.active)
                    this.userToModify.contractEmail.active = false;
                else {
                    this.userToModify.contractEmail.active = true;

                    //Si no tiene nada puesto le meto la plantilla
                    if (
                        !this.userToModify.contractEmail.subject ||
                        this.userToModify.contractEmail.subject === ""
                    )
                        this.userToModify.contractEmail.subject =
                            "Nuevo contrato creado 📄";

                    if (
                        !this.userToModify.contractEmail.message ||
                        this.userToModify.contractEmail.message === ""
                    )
                        this.userToModify.contractEmail.message =
                            "<p>Hola <strong><em>" +
                            this.userToModify.firstName +
                            " " +
                            this.userToModify.lastName +
                            "</em></strong>,</p><p><br></p><p>Te informamos que se ha creado un nuevo contrato en tu cuenta de <strong><em>" +
                            this.basicData.enterprise.name +
                            " CRM</em></strong>.</p><p><br></p><p>Puedes acceder a tu cuenta para revisar los detalles del contrato y gestionar tus servicios energéticos de manera eficiente.</p><p><br></p><p>Si tienes alguna pregunta o necesitas asistencia, no dudes en ponerte en contacto con nuestro equipo de soporte.</p><p><br></p><p>Gracias por confiar en <em>" +
                            this.basicData.enterprise.name +
                            "</em> como tu socio en la gestión energética.</p><p><br></p><p>Un saludo,</p><p>El equipo de <strong>" +
                            this.basicData.enterprise.name +
                            "</strong></p>";
                }
            }
        },
        getSettingValue(option) {
            if (option.type === 'custom') {
                return option.value();
            }

            if (option.type === 'user') {
                return this.userToModify[option.key];
            }

            return this.userToModify?.settings?.[option.key];
        },
        toggleSetting(option) {
            if (!this.isEditing) return;

            if (option.type === 'custom') {
                option.toggle();
                return;
            }

            if (option.type === 'user') {
                this.userToModify[option.key] = !this.userToModify[option.key];
                return;
            }

            this.userToModify.settings[option.key] =
                !this.userToModify.settings[option.key];
        },
        toggleActiveWelcomeEmail() {
            //Si no existe
            if (!this.userToModify.welcomeEmail) {
                this.userToModify.welcomeEmail = {
                    subject: "¡Gracias por confiar en nosotros! ✨",
                    message:
                        "<p>Queremos darte la bienvenida a<strong> <em>" +
                        this.basicData.enterprise.name +
                        " CRM</em></strong> y agradecerte por confiar en nosotros como tu equipo de gestión energética.</p><p><br></p><p>Hemos creado un espacio personalizado para ti en nuestra plataforma, desde donde podremos colaborar y facilitar todos los procesos relacionados con tus servicios energéticos, de forma ágil, profesional y segura.</p><p><br></p><p>Desde ahora, contarás con el respaldo de nuestro equipo técnico y humano para lo que necesites. Estamos encantados de tenerte con nosotros y esperamos ayudarte a alcanzar tus objetivos con total transparencia y eficacia.</p><p><br></p><p>Gracias por ser parte de <em>" +
                        this.basicData.enterprise.name +
                        "</em></p><p><br></p><p>Un saludo,</p><p>El equipo de <strong>" +
                        this.basicData.enterprise.name +
                        "</strong></p>",
                    docs: [],
                    active: true,
                };
            } else {
                //Si esta activado
                if (this.userToModify.welcomeEmail.active)
                    this.userToModify.welcomeEmail.active = false;
                else {
                    this.userToModify.welcomeEmail.active = true;

                    //Si no tiene nada puesto le meto la plantilla
                    if (
                        !this.userToModify.welcomeEmail.subject ||
                        this.userToModify.welcomeEmail.subject === ""
                    )
                        this.userToModify.welcomeEmail.subject =
                            "¡Gracias por confiar en nosotros! ✨";

                    if (
                        !this.userToModify.welcomeEmail.message ||
                        this.userToModify.welcomeEmail.message === ""
                    )
                        this.userToModify.welcomeEmail.message =
                            "<p>Queremos darte la bienvenida a<strong> <em>" +
                            this.basicData.enterprise.name +
                            " CRM</em></strong> y agradecerte por confiar en nosotros como tu equipo de gestión energética.</p><p><br></p><p>Hemos creado un espacio personalizado para ti en nuestra plataforma, desde donde podremos colaborar y facilitar todos los procesos relacionados con tus servicios energéticos, de forma ágil, profesional y segura.</p><p><br></p><p>Desde ahora, contarás con el respaldo de nuestro equipo técnico y humano para lo que necesites. Estamos encantados de tenerte con nosotros y esperamos ayudarte a alcanzar tus objetivos con total transparencia y eficacia.</p><p><br></p><p>Gracias por ser parte de <em>" +
                            this.basicData.enterprise.name +
                            "</em></p><p><br></p><p>Un saludo,</p><p>El equipo de " +
                            this.basicData.enterprise.name +
                            "</strong></p>";
                }
            }
        },
        progressClass(type) {
            if (type.max === null) return 'prog-100';

            if (!type.max || type.max <= 0) return 'prog-0';

            const progress = Math.ceil((type.used / type.max) * 100);

            return 'prog-' + Math.min(progress, 100);
        },
        formatDate(dateValue, withTime = false) {
            if (!dateValue) return '—';

            let date = null;

            if (typeof dateValue === 'string' || typeof dateValue === 'number') {
                date = new Date(dateValue);
            }

            else if (dateValue?.$date?.$numberLong) {
                date = new Date(Number(dateValue.$date.$numberLong));
            }

            else if (dateValue?.$date) {
                date = new Date(dateValue.$date);
            }

            else if (dateValue?.seconds) {
                date = new Date(dateValue.seconds * 1000);
            }

            else if (dateValue instanceof Date) {
                date = dateValue;
            }

            if (!date || isNaN(date.getTime())) {
                return '—';
            }

            return date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                ...(withTime && {
                    hour: '2-digit',
                    minute: '2-digit'
                })
            });
        },
        handlePrimaryStripeAction() {
            const action = {
                past_due: 'payment_method',
                unpaid: 'payment_method',
                incomplete: 'complete',
                paused: 'resume',
                canceled: 'resubscribe'
            }[this.stripeStatus];

            this.handleStripeAction(action);
        },
        handleStripeAction(action) {
            switch (action) {
                case 'manage':
                case 'payment_method':
                case 'cancel':
                    this.openStripePortal();
                    break;

                case 'retry':
                case 'invoice':
                    this.openLastInvoice();
                    break;

                case 'pdf':
                    this.downloadLastInvoicePdf();
                    break;

                case 'resubscribe':
                    this.resubscribe();
                    break;
            }
        },

        //Acciones suscripción activa
        async openStripePortal() {
            try {
                const response = await axios.post('/api/stripe/portalSession');
                if (response.data?.url) {
                    window.location.href = response.data.url;
                }
            } catch (error) {
                console.error(error);
            }
        },
        openLastInvoice() {
            if (!this.stripe?.last_invoice_url) return;

            window.open(this.stripe.last_invoice_url, '_blank');
        },
        downloadLastInvoicePdf() {
            if (!this.stripe?.last_invoice_pdf) return;

            window.open(this.stripe.last_invoice_pdf, '_blank');
        },

        //Acciones suscripción cancelada
        async resubscribe() {
            if (this.isLoading) return;

            this.isLoading = true;

            try {
                const response = await axios.post('/api/stripe/checkout', {
                    plan: this.stripe?.plan_id,
                    isAnnual: this.stripe?.billing === 'annual'
                });

                if (response.data?.url) {
                    window.location.href = response.data.url;
                }
            } catch (err) {
                console.log(err);
            } finally {
                this.isLoading = false;
            }
        },

        //extras
        async fetchExtras() {
            try {
                const response = await axios.get('/api/stripe/extras');
                this.availableExtras = response.data.extras;
            } catch (error) {
                console.log(error);
            }
        },
        openExtrasModal() {
            this.activeExtrasTab = 'scans';
            this.scansUnitQuantity = this.scanUnitExtra?.min_quantity || 5;
            this.showExtrasModal = true;
        },
        closeExtrasModal() {
            this.showExtrasModal = false;
        },
        setExtrasTab(tab) {
            this.activeExtrasTab = tab;
        },
        increaseScansUnitQuantity() {
            const max = this.scanUnitExtra?.max_quantity || 3999996;

            this.scansUnitQuantity = Math.min(this.scansUnitQuantity + 1, max);
        },
        decreaseScansUnitQuantity() {
            const min = this.scanUnitExtra?.min_quantity || 5;

            this.scansUnitQuantity = Math.max(this.scansUnitQuantity - 1, min);
        },
        setScansUnitQuantity(event) {
            const min = this.scanUnitExtra?.min_quantity || 10;
            const max = this.scanUnitExtra?.max_quantity || 3999996;

            let value = parseInt(event.target.value, 10);

            if (Number.isNaN(value)) {
                value = min;
            }

            value = Math.max(min, value);
            value = Math.min(max, value);

            this.scansUnitQuantity = value;
            event.target.value = value;
        },
        formatMoney(value) {
            const number = Number(value || 0);

            return number.toLocaleString('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' €';
        },
        formatExtraDate(dateValue) {
            return this.formatDate(dateValue);
        },
        async buyOneTimeExtra(category, type, quantity = 1) {
            if (this.isLoadingExtraCheckout) return;

            this.isLoadingExtraCheckout = true;

            try {
                const response = await axios.post('/api/stripe/oneTimeExtraCheckout', {
                    category,
                    type,
                    quantity
                });

                if (response.data?.url) {
                    window.location.href = response.data.url;
                }
            } catch (error) {
                console.log(error);

                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo iniciar la compra',
                    text: 'Inténtelo de nuevo en unos segundos.',
                    confirmButtonText: 'Vale'
                });
            } finally {
                this.isLoadingExtraCheckout = false;
            }
        },
        async buyRecurringExtra(category, type, quantity = 1) {
            if (this.isLoadingExtraCheckout) return;

            const result = await Swal.fire({
                icon: 'warning',
                title: '¿Añadir este extra?',
                html: `
            <p>Este extra se añadirá a su suscripción actual.</p>
            <p>El importe se incluirá en la facturación recurrente.</p>
        `,
                showCancelButton: true,
                confirmButtonText: 'Sí, añadir',
                cancelButtonText: 'Cancelar',
                didOpen: () => {
                    document.querySelector('.swal2-container').style.zIndex = '1000000';
                }
            });

            if (!result.isConfirmed) return;

            this.isLoadingExtraCheckout = true;

            try {
                const response = await axios.post('/api/stripe/addRecurringExtra', {
                    category,
                    type,
                    quantity
                });

                Swal.fire({
                    icon: 'success',
                    title: 'Extra añadido',
                    text: 'El extra se ha añadido correctamente a su suscripción.',
                    timer: 1500,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    didOpen: () => {
                        document.querySelector('.swal2-container').style.zIndex = '1000000';
                    }
                });

                if (response.data?.extra) {
                    if (!this.basicData.subdomainEnterprise.subscription.extras.recurring[category]) {
                        this.basicData.subdomainEnterprise.subscription.extras.recurring[category] = {};
                    }

                    this.basicData.subdomainEnterprise.subscription.extras.recurring[category] = response.data.extra;
                }

            } catch (error) {
                console.log(error);

                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo añadir el extra',
                    text: error.response?.data?.message || error.response?.data?.error || 'Inténtelo de nuevo en unos segundos.',
                    confirmButtonText: 'Vale',
                    didOpen: () => {
                        document.querySelector('.swal2-container').style.zIndex = '1000000';
                    }
                });
            } finally {
                this.isLoadingExtraCheckout = false;
            }
        },
        async cancelRecurringExtra(category, item) {
            const currentQuantity = Number(item.quantity || 1);

            let quantityToCancel = 1;

            if (currentQuantity > 1) {
                const result = await Swal.fire({
                    icon: 'warning',
                    title: '¿Cuántos packs quiere cancelar?',
                    html: `
                        <p>Actualmente tiene <strong>x${currentQuantity}</strong> de <strong>${item.title || item.type}</strong>.</p>
                        <p>Seleccione cuántos packs quiere cancelar ahora.</p>

                        <input
                            id="cancel-extra-quantity"
                            type="number"
                            min="1"
                            max="${currentQuantity}"
                            value="1"
                            class="swal2-input"
                            style="max-width: 160px;"
                        >

                        <p style="font-size: 13px; opacity: .75;">
                            Si cancela todos, este extra quedará eliminado.
                        </p>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Volver',
                    preConfirm: () => {
                        const value = parseInt(document.getElementById('cancel-extra-quantity').value, 10);

                        if (Number.isNaN(value) || value < 1 || value > currentQuantity) {
                            Swal.showValidationMessage(`Debe indicar una cantidad entre 1 y ${currentQuantity}`);
                            return false;
                        }

                        return value;
                    },
                    didOpen: () => {
                        document.querySelector('.swal2-container').style.zIndex = '1000000';
                    }
                });

                if (!result.isConfirmed) return;

                quantityToCancel = result.value;
            }

            const unitAmount = currentQuantity > 1
                ? item.amount / currentQuantity
                : item.amount;

            const totalAmountToCancel = unitAmount * quantityToCancel;

            const confirm = await Swal.fire({
                icon: 'warning',
                title: 'Confirmar cancelación',
                html: `
                    <p>Va a cancelar <strong>${quantityToCancel}</strong> pack(s) de <strong>${item.title || item.type}</strong>.</p>
                    <p>Su límite se reducirá en <strong>${totalAmountToCancel}</strong> ${
                            category === 'users' ? 'usuarios' : 'monitorizaciones'
                        }.</p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'Volver',
                didOpen: () => {
                    document.querySelector('.swal2-container').style.zIndex = '1000000';
                }
            });

            if (!confirm.isConfirmed) return;

            try {
                const response = await axios.post('/api/stripe/cancelRecurringExtra', {
                    category,
                    item_id: item.id,
                    quantity: quantityToCancel
                });

                Swal.fire({
                    icon: 'success',
                    title: 'Extra actualizado',
                    text: 'El extra se ha actualizado correctamente.',
                    timer: 1500,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    didOpen: () => {
                        document.querySelector('.swal2-container').style.zIndex = '1000000';
                    }
                });

                if (response.data?.extra) {
                    this.basicData.subdomainEnterprise.subscription.extras.recurring[category] = response.data.extra;
                }

            } catch (error) {
                console.log(error);

                const data = error.response?.data || {};

                if (data.users_to_remove) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No puede cancelar este extra todavía',
                        html: `
                            <p>Actualmente tiene <strong>${data.current_users}</strong> usuarios activos.</p>
                            <p>Al cancelar este extra, su límite bajaría a <strong>${data.limit_after_cancel}</strong> usuarios.</p>
                            <p>Debe eliminar o desactivar al menos <strong>${data.users_to_remove}</strong> usuarios antes de cancelar.</p>
                        `,
                        confirmButtonText: 'Entendido',
                        didOpen: () => {
                            document.querySelector('.swal2-container').style.zIndex = '1000000';
                        }
                    });

                    return;
                }

                if (data.monitoring_to_remove) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No puede cancelar este extra todavía',
                        html: `
                            <p>Actualmente tiene <strong>${data.current_monitoring}</strong> monitorizaciones activas.</p>
                            <p>Al cancelar este extra, su límite bajaría a <strong>${data.limit_after_cancel}</strong> monitorizaciones.</p>
                            <p>Debe eliminar al menos <strong>${data.monitoring_to_remove}</strong> monitorizaciones antes de cancelar.</p>
                        `,
                        confirmButtonText: 'Entendido',
                        didOpen: () => {
                            document.querySelector('.swal2-container').style.zIndex = '1000000';
                        }
                    });

                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo cancelar el extra',
                    text: data.error || data.message || 'Inténtelo de nuevo en unos segundos.',
                    confirmButtonText: 'Vale',
                    didOpen: () => {
                        document.querySelector('.swal2-container').style.zIndex = '1000000';
                    }
                });
            }
        }
    },
    computed: {
        image() {
            return this.urlImage
                ? this.urlImage
                : `/assets/profile_images/${this.userToModify.profileImage}`;
        },
        settingsSections() {
            return [
                {
                    title: 'General',
                    options: [
                        {
                            id: 'agentsCanSeeZoco',
                            type: 'user',
                            key: 'agentsCanSeeZoco',
                            label: 'Permitir a usuarios tramitar con Zoco Energía',
                            hidden:
                                this.basicData.userLogged.email ===
                                'soporte@zocoenergia.com',
                        },
                        {
                            id: 'welcomeEmail',
                            type: 'custom',
                            label: 'Email de bienvenida para nuevos usuarios',
                            value: () =>
                                !!this.userToModify.welcomeEmail &&
                                this.userToModify.welcomeEmail.active,
                            toggle: () => this.toggleActiveWelcomeEmail(),
                        },
                        {
                            key: 'contractEmail',
                            label: 'Email de aviso con la creación de un nuevo contrato',
                        },
                        {
                            key: 'denyDuplicateCups',
                            label: 'No permitir crear contratos con CUPS ya existentes',
                        },
                        {
                            key: 'orderRenewalReminderByMarketer',
                            label: 'Fecha de recordatorio de renovación administrada por comercializadora',
                        },
                        {
                            key: 'manualCommissions',
                            label: 'Usar las comisiones especificadas por rango en cada producto'
                        }
                    ],
                },
                {
                    title: 'Cuentas',
                    options: [
                        {
                            key: 'accountEmail',
                            label: 'Obligatoriedad del email',
                        },
                        {
                            key: 'accountPhone',
                            label: 'Obligatoriedad del teléfono',
                        },
                        {
                            key: 'accountAddress',
                            label: 'Obligatoriedad de la dirección',
                        },
                        {
                            key: 'accountProvince',
                            label: 'Obligatoriedad de la provincia',
                        },
                        {
                            key: 'accountLocality',
                            label: 'Obligatoriedad de la localidad',
                        },
                        {
                            key: 'accountPostal',
                            label: 'Obligatoriedad del código postal',
                        },
                    ],
                },
                {
                    title: 'Contratos',
                    options: [
                        {
                            key: 'orderIBAN',
                            label: 'Obligatoriedad del IBAN',
                        },
                        {
                            key: 'orderAddress',
                            label: 'Obligatoriedad de la dirección',
                        },
                        {
                            key: 'orderProvince',
                            label: 'Obligatoriedad de la provincia',
                        },
                        {
                            key: 'orderTown',
                            label: 'Obligatoriedad de la población',
                        },
                        {
                            key: 'orderPostal',
                            label: 'Obligatoriedad del código postal',
                        },
                        {
                            key: 'orderMarketerChange',
                            label: 'Campo cambio de comercializadora en verificaciones',
                        },
                        {
                            key: 'orderRenovation',
                            label: 'Campo renovación en verificaciones',
                        },
                        {
                            key: 'orderDisabledName',
                            label: 'Nombre de contrato igual al de la cuenta',
                        },
                        {
                            key: 'contractsIds',
                            label: 'Mostrar id en listado de contratos',
                        },
                        {
                            key: 'showProductPricesByPeriods',
                            label: 'Mostrar precios de producto por periodos',
                        },
                        {
                            key: 'orderCupsValidation',
                            label: 'Validación de CUPS',
                        },
                    ],
                },
            ];
        },
        activePlan() {

            if (!this.basicData.zocoPlans || !this.basicData.subscription) return null;

            return this.basicData.zocoPlans.find(
                plan => plan.id === this.basicData.subscription.plan_id
            );
        },
        planUsage() {
            const subscription = this.basicData.subscription;

            if (!this.activePlan || !subscription) return null;

            const included = subscription.included || {};
            const usage = subscription.usage || {};

            const recurring = subscription.extras?.recurring || {};
            const oneTime = subscription.extras?.one_time || {};

            const usersBase = included.users ?? null;
            const scansBase = included.scans ?? null;
            const monitoringBase = included.monitoring ?? null;
            const callsBase = included.calls ?? null;

            const usersUsed = this.basicData.userList?.filter(user => user.isActive !== false).length || 0;
            const scansUsed = usage.scans || 0;
            const monitoringUsed = this.basicData.subdomainEnterprise?.monitoredCups?.length || 0;
            const callsUsed = usage.calls || 0;

            // Compruebo si existen extras
            const hasExtraUsers = (recurring.users?.amount || 0) > 0;
            const hasExtraMonitoring = (recurring.monitoring?.amount || 0) > 0;
            const hasExtraScans = (oneTime.scans?.amount || 0) > 0;
            const hasExtraCalls = (oneTime.calls?.amount || 0) > 0;

            return {
                users: {
                    key: 'users',
                    title: 'Usuarios',
                    used: usersBase === null
                        ? usersUsed
                        : hasExtraUsers
                            ? Math.min(usersUsed, usersBase)
                            : usersUsed,
                    max: usersBase,
                },

                scans: {
                    key: 'scans',
                    title: 'Escaneos',
                    used: scansBase === null
                        ? scansUsed
                        : hasExtraScans
                            ? Math.min(scansUsed, scansBase)
                            : scansUsed,
                    max: scansBase,
                },

                monitoring: {
                    key: 'monitoring',
                    title: 'Monitorizaciones',
                    used: monitoringBase === null
                        ? monitoringUsed
                        : hasExtraMonitoring
                            ? Math.min(monitoringUsed, monitoringBase)
                            : monitoringUsed,
                    max: monitoringBase,
                },

                calls: {
                    key: 'calls',
                    title: 'Minutos',
                    used: callsBase === null
                        ? callsUsed
                        : hasExtraCalls
                            ? Math.min(callsUsed, callsBase)
                            : callsUsed,
                    max: callsBase,
                },
            };
        },
        extrasUsage() {
            const subscription = this.basicData.subscription;

            if (!this.activePlan || !subscription) return null;

            const included = subscription.included || {};
            const recurring = subscription.extras?.recurring || {};
            const oneTime = subscription.extras?.one_time || {};

            const result = {};

            /**
             * Usuarios extra recurrentes
             * Nueva estructura:
             * recurring.users = {
             *     amount: 0,
             *     monthly_price: 0,
             *     items: []
             * }
             */
            const usersExtra = recurring.users || {};
            const extraUsersAmount = usersExtra.amount || 0;

            if (extraUsersAmount > 0) {
                const usersUsed = this.basicData.userList?.filter(user => user.isActive !== false).length || 0;
                const includedUsers = included.users ?? null;

                const baseUsersUsed = includedUsers === null
                    ? usersUsed
                    : Math.min(usersUsed, includedUsers);

                result.users = {
                    key: 'extra-users',
                    title: 'Usuarios extra',
                    used: Math.max(usersUsed - baseUsersUsed, 0),
                    max: extraUsersAmount,
                    monthly_price: usersExtra.monthly_price || 0,
                    items: usersExtra.items || [],
                };
            }

            /**
             * Monitorizaciones extra recurrentes
             * Nueva estructura:
             * recurring.monitoring = {
             *     amount: 0,
             *     monthly_price: 0,
             *     items: []
             * }
             */
            const monitoringExtra = recurring.monitoring || {};
            const extraMonitoringAmount = monitoringExtra.amount || 0;

            if (extraMonitoringAmount > 0) {
                const monitoringUsed = this.basicData.subdomainEnterprise?.monitoredCups?.length || 0;
                const includedMonitoring = included.monitoring ?? null;

                const baseMonitoringUsed = includedMonitoring === null
                    ? monitoringUsed
                    : Math.min(monitoringUsed, includedMonitoring);

                result.monitoring = {
                    key: 'extra-monitoring',
                    title: 'Monitorizaciones extra',
                    used: Math.max(monitoringUsed - baseMonitoringUsed, 0),
                    max: extraMonitoringAmount,
                    monthly_price: monitoringExtra.monthly_price || 0,
                    items: monitoringExtra.items || [],
                };
            }

            /**
             * Escaneos extra puntuales
             */
            const extraScansAmount = oneTime.scans?.amount || 0;
            const extraScansRemaining = oneTime.scans?.remaining || 0;

            if (extraScansAmount > 0) {
                result.scans = {
                    key: 'extra-scans',
                    title: 'Escaneos extra',
                    used: Math.max(extraScansAmount - extraScansRemaining, 0),
                    max: extraScansAmount,
                };
            }

            /**
             * Llamadas extra puntuales
             */
            const extraCallsAmount = oneTime.calls?.amount || 0;
            const extraCallsRemaining = oneTime.calls?.remaining || 0;

            if (extraCallsAmount > 0) {
                result.calls = {
                    key: 'extra-calls',
                    title: 'Minutos extra',
                    used: Math.max(extraCallsAmount - extraCallsRemaining, 0),
                    max: extraCallsAmount,
                };
            }

            return Object.keys(result).length > 0 ? result : null;
        },
        excessesUsage() {
            const excesses = this.subscription?.excesses || {};
            const result = [];

            if ((excesses.calls || 0) > 0) {
                result.push({
                    key: 'calls-excess',
                    title: 'Minutos excedidos',
                    amount: excesses.calls,
                    unit: 'min',
                    text: 'Minutos consumidos fuera del plan y sin saldo extra disponible.'
                });
            }

            return result;
        },

        //Parte stripe
        hasStripeData() {
            return !!this.basicData?.subdomainEnterprise;
        },
        stripe() {
            return this.basicData?.subdomainEnterprise?.stripe || {};
        },
        subscription() {
            return this.basicData?.subdomainEnterprise?.subscription || {};
        },
        stripeStatus() {
            return this.stripe?.status || 'inactive';
        },
        stripeStatusClass() {
            if (this.isCancelScheduled) return 'status-warning';

            return {
                active: 'status-active',
                trialing: 'status-active',
                past_due: 'status-warning',
                unpaid: 'status-danger',
                canceled: 'status-danger',
                incomplete: 'status-pending',
                incomplete_expired: 'status-danger',
                paused: 'status-paused',
                inactive: 'status-muted'
            }[this.stripeStatus] || 'status-muted';
        },
        stripeStatusTextClass() {
            return this.stripeStatusClass;
        },
        stripeStatusIcon() {
            if (this.isCancelScheduled) return 'far fa-clock';

            return {
                active: 'far fa-check',
                trialing: 'far fa-check',
                past_due: 'far fa-triangle-exclamation',
                unpaid: 'far fa-circle-exclamation',
                canceled: 'far fa-xmark',
                incomplete: 'far fa-clock',
                incomplete_expired: 'far fa-clock',
                paused: 'far fa-pause',
                inactive: 'far fa-circle-info'
            }[this.stripeStatus] || 'far fa-circle-info';
        },
        stripeStatusTitle() {
            if (this.isCancelScheduled) {
                return 'Cancelación programada';
            }

            return {
                active: 'Suscripción activa',
                trialing: 'Periodo de prueba activo',
                past_due: 'Pago pendiente',
                unpaid: 'Suscripción impagada',
                canceled: 'Suscripción cancelada',
                incomplete: 'Suscripción incompleta',
                incomplete_expired: 'Suscripción expirada',
                paused: 'Suscripción pausada',
                inactive: 'Sin suscripción activa'
            }[this.stripeStatus] || 'Estado desconocido';
        },
        stripeStatusDescription() {
            const periodEnd = this.formatDate(this.stripe?.current_period_end);
            const canceledAt = this.formatDate(this.stripe?.canceled_at);

            if (this.isCancelScheduled) {
                return `Se cancelará el ${periodEnd}`;
            }

            return {
                active: `Próxima renovación el ${periodEnd}`,
                trialing: `Periodo de prueba hasta el ${periodEnd}`,
                past_due: 'Actualice el método de pago para evitar la cancelación.',
                unpaid: 'El pago no se ha completado. Revise la facturación.',
                canceled: canceledAt !== '—'
                    ? `Cancelada el ${canceledAt}`
                    : 'La suscripción ha sido cancelada.',
                incomplete: 'Complete el proceso para activar su suscripción.',
                incomplete_expired: 'La suscripción no se completó a tiempo.',
                paused: periodEnd !== '—'
                    ? `Su suscripción está pausada hasta el ${periodEnd}`
                    : 'Su suscripción está pausada.',
                inactive: 'No hay una suscripción activa actualmente.'
            }[this.stripeStatus] || 'Revise el estado de su suscripción.';
        },
        stripeStatusBadge() {
            return {
                past_due: 'Acción requerida',
                unpaid: 'Pago requerido',
                incomplete: 'Pendiente',
                incomplete_expired: 'Expirada',
                paused: 'En pausa'
            }[this.stripeStatus] || null;
        },
        isCancelScheduled() {
            return this.stripeStatus === 'active' && this.stripe?.cancel_at_period_end === true;
        },
        subscriptionBillingLabel() {
            const subscription = this.subscription || {};

            if (
                subscription.billing === 'yearly' ||
                subscription.billing === 'annual' ||
                subscription.isAnnual === true
            ) {
                return 'Anual';
            }

            return 'Mensual';
        },
        subscriptionRenewalText() {
            const periodEnd = this.formatDate(this.stripe?.current_period_end);
            const canceledAt = this.formatDate(this.stripe?.canceled_at);
            const nextPaymentAttempt = this.formatDate(this.stripe?.next_payment_attempt, true);

            if (this.isCancelScheduled) {
                return `Finaliza el ${periodEnd}`;
            }

            if (this.stripeStatus === 'past_due') {
                return nextPaymentAttempt !== '—'
                    ? `Próximo intento: ${nextPaymentAttempt}`
                    : 'Pago pendiente';
            }

            if (this.stripeStatus === 'unpaid') {
                return 'Pago pendiente';
            }

            if (this.stripeStatus === 'canceled') {
                return canceledAt !== '—'
                    ? `Cancelada el ${canceledAt}`
                    : 'Cancelada';
            }

            if (this.stripeStatus === 'paused') {
                return periodEnd !== '—' ? `Se reanudará el ${periodEnd}` : 'Pausada';
            }

            if (this.stripeStatus === 'inactive') {
                return 'Sin renovación activa';
            }

            return periodEnd !== '—'
                ? `Renovación ${periodEnd}`
                : 'Renovación no disponible';
        },
        invoiceStatusText() {
            if (['past_due', 'unpaid'].includes(this.stripeStatus)) {
                return 'Fallida';
            }

            if (this.stripeStatus === 'incomplete') {
                return 'Pendiente';
            }

            if (this.stripeStatus === 'canceled') {
                if (this.stripe?.last_invoice_status === 'paid') return 'Pagada';
                if (this.stripe?.last_invoice_status === 'open') return 'Pendiente';
                return this.stripe?.last_invoice_status || '—';
            }

            if (this.stripeStatus === 'paused') {
                return this.formatDate(this.stripe?.current_period_end);
            }

            if (this.stripe?.last_invoice_status === 'paid') {
                return 'Pagada';
            }

            return this.stripe?.last_invoice_status || '—';
        },
        invoiceStatusClass() {
            if (
                this.stripe?.last_invoice_status === 'paid' ||
                ['active', 'trialing'].includes(this.stripeStatus)
            ) {
                return 'status-active-text';
            }

            if (['past_due', 'unpaid', 'canceled', 'incomplete_expired'].includes(this.stripeStatus)) {
                return 'status-danger-text';
            }

            if (this.stripeStatus === 'incomplete') {
                return 'status-pending-text';
            }

            return '';
        },
        invoiceDateText() {
            const invoiceDate = this.stripe?.last_payment_paid_at
                || this.stripe?.last_payment_failed_at
                || this.stripe?.last_invoice_created;

            const date = this.formatDate(invoiceDate);

            let amountValue = null;

            if (['past_due', 'unpaid'].includes(this.stripeStatus)) {
                amountValue = this.stripe?.last_invoice_amount_due
                    ?? this.stripe?.last_invoice_amount_remaining
                    ?? null;
            } else if (this.stripe?.last_invoice_status === 'paid') {
                amountValue = this.stripe?.last_invoice_amount_paid
                    ?? this.stripe?.last_invoice_amount_due
                    ?? null;
            } else if (this.stripe?.last_invoice_status === 'open') {
                amountValue = this.stripe?.last_invoice_amount_due
                    ?? this.stripe?.last_invoice_amount_remaining
                    ?? null;
            }

            const currency = (this.stripe?.last_invoice_currency || 'eur').toUpperCase();

            const amount = amountValue !== null && amountValue > 0
                ? ` · ${(amountValue / 100).toFixed(2)} ${currency === 'EUR' ? '€' : currency}`
                : '';

            return date !== '—' ? `${date}${amount}` : '—';
        },
        showPrimaryStripeButton() {
            return ['past_due', 'unpaid', 'incomplete', 'paused', 'canceled'].includes(this.stripeStatus);
        },
        primaryStripeButtonText() {
            return {
                past_due: 'Actualizar pago',
                unpaid: 'Pagar ahora',
                incomplete: 'Completar suscripción',
                paused: 'Reanudar suscripción',
                canceled: 'Volver a suscribirme'
            }[this.stripeStatus] || null;
        },
        primaryStripeButtonIcon() {
            return {
                past_due: 'far fa-credit-card',
                unpaid: 'far fa-credit-card',
                incomplete: 'far fa-circle-check',
                paused: 'far fa-play',
                canceled: 'far fa-rotate-right'
            }[this.stripeStatus] || 'far fa-arrow-right';
        },
        secondaryStripeButtons() {
            const buttons = [];

            if (['active', 'trialing'].includes(this.stripeStatus)) {
                buttons.push(
                    {
                        key: 'manage',
                        text: 'Gestionar suscripción',
                        icon: 'far fa-gear',
                        action: 'manage'
                    },
                    {
                        key: 'invoice',
                        text: 'Ver última factura',
                        icon: 'far fa-file-invoice',
                        action: 'invoice'
                    },
                    {
                        key: 'pdf',
                        text: 'Descargar PDF',
                        icon: 'far fa-download',
                        action: 'pdf'
                    }
                );
            }

            if (['past_due', 'unpaid'].includes(this.stripeStatus)) {
                buttons.push(
                    {
                        key: 'retry',
                        text: 'Reintentar pago',
                        icon: 'far fa-rotate-right',
                        action: 'retry'
                    }
                );
            }

            if (this.stripeStatus === 'paused') {
                buttons.push({
                    key: 'cancel',
                    text: 'Cancelar suscripción',
                    icon: 'far fa-xmark',
                    action: 'cancel'
                });
            }

            return buttons;
        },

        //extras
        scansExtra() {
            return this.subscription?.extras?.one_time?.scans || {
                amount: 0,
                remaining: 0
            };
        },
        extrasPurchases() {
            return this.subscription?.extras_purchases || [];
        },

            //escaneos
            scansPurchases() {
                return this.extrasPurchases
                    .filter(purchase => purchase.category === 'scans')
                    .slice()
                    .reverse();
            },
            scanExtras() {
                return this.availableExtras?.one_time?.scans || {};
            },
            scanExtrasList() {
                return Object.values(this.scanExtras);
            },
            scanUnitExtra() {
                return this.scanExtras.unit || null;
            },
            scansUnitTotal() {
                if (!this.scanUnitExtra) return 0;

                return this.scansUnitQuantity * this.scanUnitExtra.price;
            },

            //llamadas
            callsExtras() {
                return this.availableExtras?.one_time?.calls || {};
            },
            callsExtrasList() {
                return Object.values(this.callsExtras);
            },
            callsExtra() {
                return this.subscription?.extras?.one_time?.calls || {
                    amount: 0,
                    remaining: 0
                };
            },
            callsPurchases() {
            return this.extrasPurchases
                .filter(purchase => purchase.category === 'calls')
                .slice()
                .reverse();
        },

            //usuarios
            usersExtra() {
                return this.subscription?.extras?.recurring?.users || {
                    amount: 0,
                    monthly_price: 0,
                    items: []
                };
            },
            usersRecurringItems() {
                return this.usersExtra?.items || [];
            },
            usersExtras() {
                return this.availableExtras?.recurring?.users || {};
            },
            usersExtrasList() {
                return Object.values(this.usersExtras);
            },

            //monitorizaciones
            monitoringExtra() {
                return this.basicData?.subdomainEnterprise?.subscription?.extras?.recurring?.monitoring || {
                    amount: 0,
                    monthly_price: 0,
                    items: []
                };
            },
            monitoringRecurringItems() {
                return this.monitoringExtra?.items || [];
            },
            monitoringExtras() {
                return this.availableExtras?.recurring?.monitoring || {};
            },
            monitoringExtrasList() {
                return Object.values(this.monitoringExtras);
            }
    },
};
</script>

<style scoped>
    @media (max-width: 810px) {
        .qr-modal-mobile {
            width: 92vw !important;
            max-width: 92vw !important;
            max-height: 84vh;
            padding: 16px;
            border-radius: 24px;
        }

        .qr-modal-mobile .qr-modal-mobile-content {
            display: block !important;
            gap: 0 !important;
        }

        .qr-modal-mobile .div-content {
            max-height: 62vh;
            overflow-y: auto;
            padding-right: 6px;
        }

        .qr-modal-mobile-header {
            margin-bottom: 14px;
        }

        .qr-modal-mobile-top {
            margin-top: 6px;
            margin-bottom: 22px;
        }

        .qr-modal-mobile-top canvas {
            width: 210px !important;
            height: 210px !important;
        }

        .qr-modal-mobile-link {
            max-width: calc(100% - 24px) !important;
            font-size: 10px !important;
            padding: 0 8px;
        }

        .qr-modal-mobile-list {
            width: 100%;
            min-width: 0 !important;
        }
        .img-base .img-div {
            width: 100% !important;
            height: auto !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            overflow: visible !important;
        }

        .img-base .img-div img.general-icon {
            width: 150px !important;
            max-width: 80vw !important;
            height: auto !important;
            max-height: 80px !important;
            object-fit: contain !important;
        }
    }
</style>
