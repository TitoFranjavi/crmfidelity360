<template>
    <div class="content-white">
        <form
            v-on:submit.prevent="createopportunity"
            @keydown.enter.prevent="onFormEnter"
            class="form register-pos"
            v-on:click="isAccFocused = false"
        >
            <div class="creation-bar my-10">
                <div class="slider w-auto">
                    <div
                        v-for="type in createUserData.types"
                        :key="type.code"
                        @click="onSelectType(type.code)"
                        :class="{
                            selected: type.code === createUserData.selected,
                        }"
                    >
                        <div>{{ type.title }}</div>
                    </div>
                </div>
            </div>

            <div class="top-part">
                <div
                    class="inputs-part"
                    :class="{ disabledSection: isContractOnly }"
                >
                    <div
                        class="text desktop-item"
                        data-size="20"
                        data-weight="700"
                    >
                        Detalles de oportunidad
                    </div>

                    <div class="half-space">
                        <div
                            v-bind:class="{ wrong: errors.name }"
                            class="form-group"
                        >
                            <p class="my-auto">
                                <label>Nombre</label>
                                <span data-color="rojo">*</span>
                            </p>
                            <div class="input-group">
                                <input
                                    v-on:focus="delete errors['name']"
                                    data-size="12"
                                    v-model="opportunity.name"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.name" class="error">{{
                                errors.name
                            }}</span>
                        </div>

                        <div
                            v-bind:class="{ wrong: errors.CIF }"
                            class="form-group"
                        >
                            <p class="my-auto"><label>CIF/NIF</label></p>
                            <div class="input-group">
                                <input
                                    v-on:focus="delete errors['CIF']"
                                    data-size="12"
                                    v-model="opportunity.CIF"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.CIF" class="error">{{
                                errors.CIF
                            }}</span>
                        </div>
                    </div>

                    <div class="half-space">
                        <div class="half-space w-45-max">
                            <div
                                v-bind:class="{ wrong: errors.phone }"
                                class="form-group"
                            >
                                <p class="my-auto"><label>Móvil</label></p>
                                <div class="input-group">
                                    <input
                                        v-on:focus="delete errors['phone']"
                                        data-size="12"
                                        v-model="opportunity.phone"
                                        :disabled="isReadOnly"
                                        type="text"
                                    />
                                </div>
                                <span v-if="errors.phone" class="error">{{
                                    errors.phone
                                }}</span>
                            </div>

                            <div
                                v-bind:class="{ wrong: errors.landLinePhone }"
                                class="form-group"
                            >
                                <p class="my-auto"><label>Fijo</label></p>
                                <div class="input-group">
                                    <input
                                        v-on:focus="
                                            delete errors['landLinePhone']
                                        "
                                        data-size="12"
                                        v-model="opportunity.landLinePhone"
                                        :disabled="isReadOnly"
                                        type="text"
                                    />
                                </div>
                                <span
                                    v-if="errors.landLinePhone"
                                    class="error"
                                    >{{ errors.landLinePhone }}</span
                                >
                            </div>
                        </div>

                        <div
                            v-bind:class="{ wrong: errors.email }"
                            class="form-group"
                        >
                            <label>Email</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="delete errors['email']"
                                    data-size="12"
                                    v-model="opportunity.email"
                                    type="email"
                                    name="email"
                                />
                            </div>
                            <span v-if="errors.email" class="error">{{
                                errors.email
                            }}</span>
                        </div>
                    </div>

                    <div class="half-space">
                        <div
                            v-bind:class="{ wrong: errors.website }"
                            class="form-group"
                        >
                            <label>Sitio web</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="delete errors['website']"
                                    data-size="12"
                                    v-model="opportunity.website"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.website" class="error">{{
                                errors.website
                            }}</span>
                        </div>

                        <custom-select-component
                            @addElement="addSelectType"
                            @delElement="delSelectType"
                            @selectElement="selectElement"
                            class="mt1"
                            type="sector"
                            toTop="true"
                            title="Sector"
                            :options="sectors"
                            :addedOptions="selectValues.sector"
                            :selected="opportunity.sector"
                            :errors="errors"
                        ></custom-select-component>
                    </div>

                    <div class="half-space">
                        <custom-select-component
                            @addElement="addSelectType"
                            @delElement="delSelectType"
                            @selectElement="selectElement"
                            class="mt1"
                            type="source"
                            toTop="true"
                            title="Origen de oportunidad"
                            :options="sources"
                            :addedOptions="selectValues.source"
                            :selected="opportunity.source"
                            :errors="errors"
                        ></custom-select-component>

                        <div class="form-group">
                            <label>Estado</label>
                            <div class="input-group">
                                <select v-model="opportunity.status" :disabled="isInputsDisabled">
                                    <option value="">Selecciona uno</option>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Contactado">Contactado</option>
                                    <option value="Contactado Whatssap">Contactado Whatssap</option>
                                    <option value="Presupuesto Enviado">Presupuesto Enviado</option>
                                    <option value="Pre. Bot">Pre. Bot</option>
                                    <option value="En seguimiento">En seguimiento</option>
                                    <option value="Aceptado">Aceptado</option>
                                    <option value="Fallido">Fallido</option>
                                    <option value="Falso">Falso</option>
                                    <option value="Repetido">Repetido</option>
                                    <option value="No Contesta">No contesta</option>
                                    <option value="Mensaje Enviado">Mensaje Enviado</option>
                                    <option value="Checklist terminado">Checklist terminado</option>
                                    <option value="Stand-by">Stand-by</option>
                                    <option value="Perdidos">Perdidos</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="half-space">
                        <div
                            v-bind:class="{ wrong: errors.contact }"
                            class="form-group"
                        >
                            <p class="opacity-6 d-flex justify-between">
                                Persona de contacto
                                <label
                                    class="pointer my-auto"
                                    @click="
                                        opportunity.contact.isFromContacts =
                                            !opportunity.contact.isFromContacts;
                                        opportunity.contact.value = '';
                                    "
                                >
                                    <i
                                        class="far"
                                        :class="{
                                            'fa-user-plus':
                                                !opportunity.contact
                                                    .isFromContacts,
                                            'fa-user-minus':
                                                opportunity.contact
                                                    .isFromContacts,
                                        }"
                                    ></i>
                                </label>
                            </p>
                            <div class="input-group">
                                <input
                                    v-if="!opportunity.contact.isFromContacts"
                                    v-on:focus="delete errors['contact']"
                                    data-size="12"
                                    v-model="opportunity.contact.value"
                                    type="text"
                                />

                                <select
                                    v-else
                                    v-model="opportunity.contact.value"
                                >
                                    <option value="">Selecciona uno</option>
                                    <option
                                        v-for="contact in contacts"
                                        :key="contact._id"
                                        :value="contact._id"
                                    >
                                        {{ contact.name.first }}
                                        {{ contact.name.second }}
                                        {{ contact.surname.first }}
                                        {{ contact.surname.second }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="errors.contact" class="error">{{
                                errors.contact
                            }}</span>
                        </div>

                        <div
                            v-bind:class="{ wrong: errors.position }"
                            class="form-group"
                        >
                            <label>Cargo en la empresa</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="delete errors['position']"
                                    data-size="12"
                                    v-model="opportunity.position"
                                    type="text"
                                    name="cargo empresa"
                                />
                            </div>
                            <span v-if="errors.position" class="error">{{
                                errors.position
                            }}</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-between align-center mb-5">
                            <label>Observaciones</label>
                            <div class="d-flex align-center" data-gap="5">
                                <input type="date" v-model="selectedObservationDate" data-size="11">
                                <button type="button" class="custom-button" data-size="small" data-bg="amarillo" @click.prevent="insertDateInObservations">
                                    <i class="far fa-calendar"></i>
                                </button>
                            </div>
                        </div>
                        <div class="input-group">
                            <textarea
                                id="observationsTextarea"
                                class="h-100-px-min w-100-px-min"
                                data-size="12"
                                v-model="opportunity.observations"
                                :disabled="isReadOnly"
                            ></textarea>
                        </div>
                    </div>

                    <div class="separator mt-30"></div>

                    <div class="text mt-50" data-size="17" data-weight="600">
                        Dirección de facturación
                    </div>

                    <div
                        v-bind:class="{ wrong: errors.billingInfo.community }"
                        class="form-group"
                    >
                        <label>Comunidad</label>
                        <div class="input-group">
                            <input
                                v-on:focus="
                                    delete errors['billingInfo']['community']
                                "
                                data-size="12"
                                v-model="opportunity.billingInfo.community"
                                type="text"
                            />
                        </div>
                        <span
                            v-if="errors.billingInfo.community"
                            class="error"
                            >{{ errors.billingInfo.community }}</span
                        >
                    </div>

                    <div class="half-space">
                        <div
                            v-bind:class="{
                                wrong: errors.billingInfo.province,
                            }"
                            class="form-group"
                        >
                            <label>Provincia</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="
                                        delete errors['billingInfo']['province']
                                    "
                                    data-size="12"
                                    v-model="opportunity.billingInfo.province"
                                    type="text"
                                />
                            </div>
                            <span
                                v-if="errors.billingInfo.province"
                                class="error"
                                >{{ errors.billingInfo.province }}</span
                            >
                        </div>

                        <div
                            v-bind:class="{
                                wrong: errors.billingInfo.locality,
                            }"
                            class="form-group"
                        >
                            <label>Localidad</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="
                                        delete errors['billingInfo']['locality']
                                    "
                                    data-size="12"
                                    v-model="opportunity.billingInfo.locality"
                                    type="text"
                                />
                            </div>
                            <span
                                v-if="errors.billingInfo.locality"
                                class="error"
                                >{{ errors.billingInfo.locality }}</span
                            >
                        </div>
                    </div>

                    <div class="half-space">
                        <div
                            v-bind:class="{ wrong: errors.billingInfo.address }"
                            class="form-group"
                        >
                            <label>Dirección</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="
                                        delete errors['billingInfo']['address']
                                    "
                                    data-size="12"
                                    v-model="opportunity.billingInfo.address"
                                    type="text"
                                />
                            </div>
                            <span
                                v-if="errors.billingInfo.address"
                                class="error"
                                >{{ errors.billingInfo.address }}</span
                            >
                        </div>

                        <div
                            v-bind:class="{ wrong: errors.billingInfo.postal }"
                            class="form-group"
                        >
                            <label>Código postal</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="
                                        delete errors['billingInfo']['postal']
                                    "
                                    data-size="12"
                                    v-model="opportunity.billingInfo.postal"
                                    type="text"
                                />
                            </div>
                            <span
                                v-if="errors.billingInfo.postal"
                                class="error"
                                >{{ errors.billingInfo.postal }}</span
                            >
                        </div>
                    </div>

                    <div class="separator mt-30"></div>

                    <div class="d-flex mt-50 mb-20">
                        <div
                            class="text desktop-item"
                            data-size="17"
                            data-weight="600"
                        >
                            Campos personalizados
                        </div>
                        <div
                            class="text mobile-item my-20"
                            data-size="18"
                            data-weight="700"
                        >
                            Campos personalizados
                        </div>
                        <div
                            class="custom-button ml-auto my-auto mr-20"
                            data-size="medium"
                            data-bg="amarillo"
                            v-on:click="addCustomField"
                        >
                            <i class="fas fa-plus"></i>
                        </div>
                    </div>

                    <custom-fields-component
                        class="my-20"
                        v-for="(field, fieldInd) in opportunity.customFields"
                        :key="fieldInd"
                        :field="field"
                        :fieldInd="fieldInd"
                        @delCustomField="delCustomField"
                        @addCustomFields="addCustomFields"
                        type="opp"
                    ></custom-fields-component>

                    <div class="separator mt-30"></div>

                    <div class="form-group mt-20">
                        <div
                            class="text desktop-item mb-10"
                            data-size="18"
                            data-weight="700"
                        >
                            Propietarios de la oportunidad
                        </div>

                        <user-list-component
                            :basicData="basicData"
                            v-model:userListSelected="opportunity.usersIds"
                            :account="true"
                            :editing="true"
                        ></user-list-component>

                        <p
                            v-if="basicData.userList.length === 0"
                            class="text opacity-3"
                            data-size="10"
                        >
                            No tienes usuarios para asignar
                        </p>
                    </div>
                </div>

                <div class="separator" data-position="vertical"></div>

                <div class="inputs-part">
                    <div class="text" data-size="20" data-weight="700">
                        Detalles de posible contrato
                    </div>

                    <div
                        v-if="isContractOnly"
                        :class="['form-group', { wrong: errors.name }]"
                    >
                        <p class="my-auto">
                            <label>Nombre</label>
                            <span data-color="rojo">*</span>
                        </p>
                        <div class="input-group">
                            <input
                                v-on:focus="delete errors['name']"
                                data-size="12"
                                v-model="opportunity.order.name"
                                type="text"
                            />
                        </div>
                        <span v-if="errors.name" class="error">{{
                            errors.name
                        }}</span>
                    </div>

                    <div
                        class="d-grid"
                        :data-column="
                            opportunity.order.productType === 'cd' ? 2 : 1
                        "
                    >
                        <div
                            v-bind:class="{ wrong: errors.productType }"
                            class="form-group"
                        >
                            <p class="my-auto">
                                <label>Tipo de producto</label>
                            </p>
                            <div class="input-group">
                                <select
                                    v-model="opportunity.order.productType"
                                    :disabled="isReadOnly"
                                    v-on:change="changeProductType"
                                >
                                    <option value="">Selecciona uno</option>
                                    <option
                                        v-for="type in productTypesWithoutSp"
                                        :key="type.code"
                                        :value="type.code"
                                    >
                                        {{ type.title }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="errors.productType" class="error">{{
                                errors.productType
                            }}</span>
                        </div>

                        <div
                            v-if="opportunity.order.productType === 'cd'"
                            v-bind:class="{ wrong: errors.marketer }"
                            class="form-group"
                        >
                            <p class="my-auto">
                                <label>Comercializadora</label>
                            </p>
                            <div class="input-group">
                                <select
                                    v-model="opportunity.order.marketer"
                                    :disabled="isReadOnly"
                                    v-on:change="changeMarketer"
                                >
                                    <option value="">Selecciona una</option>
                                    <option
                                        v-for="marketer in filteredMarketers"
                                        :key="marketer._id || marketer.name"
                                        :value="marketer.name"
                                    >
                                        {{ marketer.name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="errors.marketer" class="error">{{
                                errors.marketer
                            }}</span>
                        </div>
                    </div>

                    <div
                        class="d-grid"
                        :data-column="
                            opportunity.order.productType === 'cd' ? 1 : 2
                        "
                    >
                        <div
                            v-if="showContractMarketerSelect"
                            v-bind:class="{ wrong: errors.marketer }"
                            class="form-group"
                        >
                            <p class="my-auto">
                                <label>Comercializadora</label>
                            </p>
                            <div class="input-group">
                                <select
                                    v-model="opportunity.order.marketer"
                                    :disabled="isInputsDisabled"
                                    v-on:change="changeMarketer"
                                >
                                    <option value="">Selecciona una</option>
                                    <option
                                        v-for="marketer in filteredMarketers"
                                        :key="marketer._id || marketer.name"
                                        :value="marketer.name"
                                    >
                                        {{ marketer.name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="errors.marketer" class="error">{{
                                errors.marketer
                            }}</span>
                        </div>

                        <div
                            class="text my-10 d-flex align-center"
                            data-size="18"
                            v-if="
                                opportunity.order.productType === 'cd' &&
                                opportunity.order.marketer !== ''
                            "
                        >
                            <i class="fa-regular fa-lightbulb mr-10"></i> Luz
                        </div>

                        <div
                            v-if="showMainFeeSelect"
                            v-bind:class="{ wrong: errors.fee }"
                            class="form-group"
                        >
                            <p class="my-auto"><label>Tarifa</label></p>
                            <div class="input-group">
                                <select
                                    v-model="opportunity.order.fee"
                                    :disabled="isInputsDisabled"
                                    v-on:change="changeFee"
                                >
                                    <option value="">Selecciona una</option>
                                    <option
                                        v-for="fee in filteredFees"
                                        :key="fee.id?.$oid || fee.name"
                                        :value="fee.name"
                                    >
                                        {{ fee.name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="errors.fee" class="error">{{
                                errors.fee
                            }}</span>
                        </div>
                    </div>

                    <div
                        v-if="showOtherProductSelect"
                        v-bind:class="{ wrong: errors.product }"
                        class="form-group"
                    >
                        <p class="my-auto"><label>Producto</label></p>
                        <div class="input-group">
                            <select
                                v-model="opportunity.order.product"
                                :disabled="isReadOnly"
                                v-on:change="changeOtherProduct"
                            >
                                <option value="">Selecciona una</option>
                                <option
                                    v-for="product in otherProductsByType"
                                    :key="product"
                                    :value="product"
                                >
                                    {{ product }}
                                </option>
                            </select>
                        </div>
                        <span v-if="errors.product" class="error">{{
                            errors.product
                        }}</span>
                    </div>

                    <div
                        v-else-if="
                            opportunity.order.marketer && !isOtherProductType
                        "
                        class="d-grid"
                        :data-column="
                            opportunity.order.productType === 'cd' ? 2 : 1
                        "
                        data-gap="20"
                    >
                        <div
                            v-if="
                                opportunity.order.productType === 'cd' &&
                                opportunity.order.marketer !== ''
                            "
                            v-bind:class="{ wrong: errors.fee }"
                            class="form-group"
                        >
                            <p class="my-auto"><label>Tarifa</label></p>
                            <div class="input-group">
                                <select
                                    v-model="opportunity.order.fee"
                                    :disabled="isReadOnly"
                                    v-on:change="changeFee"
                                >
                                    <option value="">Selecciona una</option>
                                    <option
                                        v-for="fee in filteredFees"
                                        :key="fee.id?.$oid || fee.name"
                                        :value="fee.name"
                                    >
                                        {{ fee.name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="errors.fee" class="error">{{
                                errors.fee
                            }}</span>
                        </div>

                        <div
                            v-if="
                                opportunity.order.fee ||
                                opportunity.order.productType === 'sa'
                            "
                            v-bind:class="{ wrong: errors.product }"
                            class="form-group"
                        >
                            <p class="my-auto"><label>Producto</label></p>
                            <div class="input-group">
                                <select
                                    v-model="opportunity.order.product"
                                    :disabled="isReadOnly"
                                    v-on:change="calcCommission"
                                >
                                    <option value="">Selecciona una</option>
                                    <option
                                        v-for="product in filteredMarketerProducts"
                                        :key="product.id?.$oid || product.name"
                                        :value="product.name"
                                    >
                                        {{ product.name }}
                                    </option>
                                </select>
                            </div>
                            <span v-if="errors.product" class="error">{{
                                errors.product
                            }}</span>
                        </div>
                    </div>

                    <div v-if="isElectricCarCharger">
                        <div class="separator mt-30"></div>

                        <div
                            class="text mt-30 mb-20"
                            data-size="17"
                            data-weight="600"
                        >
                            Presupuesto cargador eléctrico
                        </div>

                        <div
                            class="form-group"
                            v-bind:class="{
                                wrong: errors.order.evChargerBudget
                                    .chargerModel,
                            }"
                        >
                            <label>Modelo de cargador</label>
                            <div class="input-group">
                                <select
                                    v-model="
                                        opportunity.order.evChargerBudget
                                            .chargerModel
                                    "
                                    :disabled="isReadOnly"
                                    v-on:change="applyEvChargerDefaultsByModel"
                                    v-on:focus="
                                        delete errors.order.evChargerBudget
                                            .chargerModel
                                    "
                                >
                                    <option value="">Selecciona uno</option>
                                    <option
                                        v-for="charger in evChargerModels"
                                        :key="charger.chargerModel"
                                        :value="charger.chargerModel"
                                    >
                                        {{ charger.brand }} -
                                        {{ charger.name }} · PVP
                                        {{ formatCurrency(charger.pvp) }}€ + IVA
                                    </option>
                                </select>
                            </div>
                            <span
                                v-if="errors.order.evChargerBudget.chargerModel"
                                class="error"
                            >
                                {{ errors.order.evChargerBudget.chargerModel }}
                            </span>
                        </div>

                        <div class="half-space">
                            <div class="form-group">
                                <label>Marca</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        :value="
                                            opportunity.order.evChargerBudget
                                                .chargerBrand
                                        "
                                        type="text"
                                        disabled
                                    />
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Fase</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        :value="
                                            getPhaseLabel(
                                                opportunity.order
                                                    .evChargerBudget
                                                    .chargerPhase,
                                            )
                                        "
                                        type="text"
                                        disabled
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="half-space">
                            <div
                                v-bind:class="{
                                    wrong: errors.order.evChargerBudget
                                        .chargerPower,
                                }"
                                class="form-group"
                            >
                                <label>Potencia del cargador</label>
                                <div class="input-group">
                                    <select
                                        v-model="
                                            opportunity.order.evChargerBudget
                                                .chargerPower
                                        "
                                        :disabled="isReadOnly"
                                        v-on:focus="
                                            delete errors.order.evChargerBudget
                                                .chargerPower
                                        "
                                    >
                                        <option value="">Selecciona una</option>
                                        <option value="3,7kW">3,7kW</option>
                                        <option value="7,4kW">7,4kW</option>
                                        <option value="9,2kW">9,2kW</option>
                                        <option value="11kW">11kW</option>
                                        <option value="22kW">22kW</option>
                                    </select>
                                </div>
                                <span
                                    v-if="
                                        errors.order.evChargerBudget
                                            .chargerPower
                                    "
                                    class="error"
                                >
                                    {{
                                        errors.order.evChargerBudget
                                            .chargerPower
                                    }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Tipo de instalación</label>
                                <div class="input-group">
                                    <select
                                        v-model="
                                            opportunity.order.evChargerBudget
                                                .installationType
                                        "
                                        :disabled="isReadOnly"
                                    >
                                        <option value="">Selecciona una</option>
                                        <option value="Vivienda unifamiliar">
                                            Vivienda unifamiliar
                                        </option>
                                        <option value="Garaje comunitario">
                                            Garaje comunitario
                                        </option>
                                        <option value="Empresa">Empresa</option>
                                        <option value="Parking exterior">
                                            Parking exterior
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="half-space">
                            <div class="form-group">
                                <label>PVP cargador sin IVA</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .chargerInstallationPrice
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :disabled="isReadOnly"
                                        placeholder="Ej: 725,03"
                                    />
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Descuento cargador (%)</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .chargerInstallationDiscount
                                        "
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        :disabled="isReadOnly"
                                        placeholder="Ej: 12"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="half-space">
                            <div class="form-group">
                                <label>IVA aplicado (%)</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .chargerVatPercentage
                                        "
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        :disabled="isReadOnly"
                                        placeholder="21"
                                    />
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Descuento total s/base imponible (%)</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .globalDiscount
                                        "
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        :disabled="isReadOnly"
                                        placeholder="Ej: 5"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Potencia contratada</label>
                            <div class="input-group">
                                <input
                                    data-size="12"
                                    v-model="
                                        opportunity.order.evChargerBudget
                                            .contractedPower
                                    "
                                    type="text"
                                    placeholder="Ej: 5,75 kW"
                                    :disabled="isReadOnly"
                                />
                            </div>
                        </div>

                        <div class="separator mt-30"></div>

                        <div
                            class="text mt-30 mb-10"
                            data-size="15"
                            data-weight="600"
                        >
                            Costes de instalación
                        </div>

                        <p class="text opacity-5 mb-20" data-size="11">
                            Al cambiar la distancia se calculan automáticamente
                            el cableado y el tubo/manguera hasta 70 m. Mano de
                            obra, boletín y obra civil respetan el precio
                            escrito manualmente.
                        </p>

                        <div class="half-space">
                            <div
                                v-bind:class="{
                                    wrong: errors.order.evChargerBudget
                                        .cableMeters,
                                }"
                                class="form-group"
                            >
                                <label>Distancia del cableado</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .cableMeters
                                        "
                                        type="number"
                                        min="0"
                                        max="70"
                                        :disabled="isReadOnly"
                                        v-on:change="
                                            applyEvInstallationDefaultsByMeters
                                        "
                                        v-on:blur="
                                            applyEvInstallationDefaultsByMeters
                                        "
                                        v-on:focus="
                                            delete errors.order.evChargerBudget
                                                .cableMeters
                                        "
                                    />
                                </div>
                                <span
                                    v-if="
                                        errors.order.evChargerBudget.cableMeters
                                    "
                                    class="error"
                                >
                                    {{
                                        errors.order.evChargerBudget.cableMeters
                                    }}
                                </span>
                                <p class="text opacity-5 mt-5" data-size="10">
                                    Tramo de referencia:
                                    {{
                                        opportunity.order.evChargerBudget
                                            .installationMetersRange ||
                                        opportunity.order.evChargerBudget
                                            .cableMeters
                                    }}
                                    m
                                </p>
                            </div>

                            <div class="form-group">
                                <label>Precio metro cableado</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .cablePricePerMeter
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :disabled="isReadOnly"
                                        placeholder="Ej: 11"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="half-space">
                            <div class="form-group">
                                <label>Metros tubo / manguera</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .modulationCableMeters
                                        "
                                        type="number"
                                        min="0"
                                        max="70"
                                        :disabled="isReadOnly"
                                        v-on:change="
                                            applyEvInstallationDefaultsByMeters
                                        "
                                        v-on:blur="
                                            applyEvInstallationDefaultsByMeters
                                        "
                                    />
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Precio metro tubo / manguera</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .modulationCablePricePerMeter
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :disabled="isReadOnly"
                                        placeholder="Ej: 2.2"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="half-space">
                            <div class="form-group">
                                <label
                                    >Mano de obra, desplazamiento y pequeño
                                    material</label
                                >
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .laborPrice
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :disabled="isReadOnly"
                                        placeholder="Ej: 275"
                                    />
                                </div>
                            </div>

                            <div class="form-group">
                                <label
                                    >Boletín / certificado y legalización</label
                                >
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .certificatePrice
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :disabled="isReadOnly"
                                        placeholder="Ej: 165"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="half-space">
                            <div class="form-group">
                                <label>¿Tiene instalación fotovoltaica?</label>
                                <div class="input-group">
                                    <select
                                        v-model="
                                            opportunity.order.evChargerBudget
                                                .hasPhotovoltaic
                                        "
                                        :disabled="isReadOnly"
                                    >
                                        <option :value="false">No</option>
                                        <option :value="true">Sí</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Optimización de excedentes</label>
                                <div class="input-group">
                                    <select
                                        v-model="
                                            opportunity.order.evChargerBudget
                                                .wantsSurplusOptimization
                                        "
                                        :disabled="
                                            isReadOnly ||
                                            !opportunity.order.evChargerBudget
                                                .hasPhotovoltaic
                                        "
                                    >
                                        <option :value="false">No</option>
                                        <option :value="true">Sí</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div
                            class="form-group"
                            v-if="
                                opportunity.order.evChargerBudget
                                    .hasPhotovoltaic &&
                                opportunity.order.evChargerBudget
                                    .wantsSurplusOptimization
                            "
                        >
                            <label
                                >Precio optimización de excedentes
                                fotovoltaicos</label
                            >
                            <div class="input-group">
                                <input
                                    data-size="12"
                                    v-model.number="
                                        opportunity.order.evChargerBudget
                                            .surplusOptimizationPrice
                                    "
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="isReadOnly"
                                    placeholder="Ej: 95"
                                />
                            </div>
                        </div>

                        <div class="half-space">
                            <div class="form-group">
                                <label>¿Necesita obra civil?</label>
                                <div class="input-group">
                                    <select
                                        v-model="
                                            opportunity.order.evChargerBudget
                                                .needsCivilWork
                                        "
                                        :disabled="isReadOnly"
                                    >
                                        <option :value="false">No</option>
                                        <option :value="true">Sí</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Precio obra civil</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .civilWorkPrice
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :disabled="
                                            isReadOnly ||
                                            !opportunity.order.evChargerBudget
                                                .needsCivilWork
                                        "
                                        placeholder="Ej: 59"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="
                                opportunity.order.evChargerBudget.needsCivilWork
                            "
                            class="form-group"
                        >
                            <label>Descripción de obra civil</label>
                            <div class="input-group">
                                <textarea
                                    class="h-100-px-min w-100-px-min"
                                    data-size="12"
                                    v-model="
                                        opportunity.order.evChargerBudget
                                            .civilWorkDescription
                                    "
                                    :disabled="isReadOnly"
                                    placeholder="Describe canalización, rozas, distancia, dificultades de acceso, etc."
                                ></textarea>
                            </div>
                        </div>

                        <div class="separator mt-30"></div>

                        <div class="d-flex mt-30 mb-20">
                            <div
                                class="text my-auto"
                                data-size="17"
                                data-weight="600"
                            >
                                Opcionales del presupuesto
                            </div>

                            <button
                                class="custom-button ml-auto my-auto"
                                data-size="medium"
                                data-bg="amarillo"
                                type="button"
                                v-on:click.prevent="addEvChargerOptional"
                                v-if="!isReadOnly"
                            >
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <p
                            v-if="
                                !opportunity.order.evChargerBudget.optionalItems
                                    .length
                            "
                            class="text opacity-3 mb-20"
                            data-size="10"
                        >
                            No hay opcionales añadidos. Pulsa + para añadir uno
                            manualmente.
                        </p>

                        <div
                            class="ev-optional-card mb-20"
                            v-for="(optional, optionalInd) in opportunity.order
                                .evChargerBudget.optionalItems"
                            :key="optionalInd"
                        >
                            <div class="d-flex mb-10">
                                <div
                                    class="text my-auto"
                                    data-size="13"
                                    data-weight="600"
                                >
                                    Opcional {{ optionalInd + 1 }}
                                </div>

                                <button
                                    class="custom-button ml-auto my-auto"
                                    data-size="small"
                                    data-bg="rojo"
                                    type="button"
                                    v-on:click.prevent="
                                        delEvChargerOptional(optionalInd)
                                    "
                                    v-if="!isReadOnly"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                            <div class="half-space">
                                <div class="form-group">
                                    <label>Nombre / concepto</label>
                                    <div class="input-group">
                                        <input
                                            data-size="12"
                                            v-model="optional.name"
                                            type="text"
                                            placeholder="Ej: kit schuko"
                                            :disabled="isReadOnly"
                                        />
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <div class="input-group">
                                        <input
                                            data-size="12"
                                            v-model.number="optional.quantity"
                                            type="number"
                                            min="0"
                                            step="1"
                                            :disabled="isReadOnly"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="half-space">
                                <div class="form-group">
                                    <label>Precio unidad</label>
                                    <div class="input-group">
                                        <input
                                            data-size="12"
                                            v-model.number="optional.price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="Ej: 95"
                                            :disabled="isReadOnly"
                                        />
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Descuento (%)</label>
                                    <div class="input-group">
                                        <input
                                            data-size="12"
                                            v-model.number="optional.discount"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            :disabled="isReadOnly"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Descripción</label>
                                <div class="input-group">
                                    <textarea
                                        class="h-100-px-min w-100-px-min"
                                        data-size="12"
                                        v-model="optional.description"
                                        placeholder="Descripción opcional para mostrar en el presupuesto"
                                        :disabled="isReadOnly"
                                    ></textarea>
                                </div>
                            </div>

                            <div
                                class="text text-right opacity-6"
                                data-size="10"
                            >
                                Total opcional:
                                {{
                                    formatCurrency(
                                        getOptionalItemTotal(optional),
                                    )
                                }}
                                €
                            </div>
                        </div>

                        <div class="half-space">
                            <div class="form-group">
                                <label>Fecha preferida instalación</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model="
                                            opportunity.order.evChargerBudget
                                                .preferredInstallationDate
                                        "
                                        type="date"
                                        :disabled="isReadOnly"
                                    />
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Forma de pago</label>
                                <div class="input-group">
                                    <input
                                        data-size="12"
                                        v-model="
                                            opportunity.order.evChargerBudget
                                                .paymentMethod
                                        "
                                        type="text"
                                        :disabled="isReadOnly"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="separator mt-30"></div>

                        <div
                            class="text mt-30 mb-10"
                            data-size="15"
                            data-weight="600"
                        >
                            Financiación
                        </div>

                        <p class="text opacity-5 mb-20" data-size="11">
                            Activa esta opción si el cliente quiere financiar el
                            presupuesto. La cuota se calcula con el importe total
                            y el coeficiente de la tabla de financiación.
                        </p>

                        <div class="half-space">
                            <div class="form-group">
                                <label>¿Quiere financiación?</label>
                                <div class="input-group">
                                    <select
                                        v-model="
                                            opportunity.order.evChargerBudget
                                                .financingEnabled
                                        "
                                        :disabled="isReadOnly"
                                    >
                                        <option :value="false">No</option>
                                        <option :value="true">Sí</option>
                                    </select>
                                </div>
                            </div>

                            <div
                                class="form-group"
                                v-if="
                                    opportunity.order.evChargerBudget
                                        .financingEnabled
                                "
                            >
                                <label>Plazo de financiación</label>
                                <div class="input-group">
                                    <select
                                        v-model.number="
                                            opportunity.order.evChargerBudget
                                                .financingMonths
                                        "
                                        :disabled="isReadOnly"
                                    >
                                        <option
                                            v-for="plan in evChargerFinancingPlans"
                                            :key="plan.months"
                                            :value="plan.months"
                                        >
                                            {{ formatFinancingPlanLabel(plan) }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="evChargerFinancingSummary"
                            class="ev-budget-financing-summary mt-10"
                        >
                            <div class="d-flex justify-between">
                                <span>Importe financiado</span>
                                <strong>
                                    {{ formatCurrency(evChargerFinancingSummary.amount) }} €
                                </strong>
                            </div>
                            <div class="d-flex justify-between">
                                <span>Plazo</span>
                                <strong>{{ evChargerFinancingSummary.months }} meses</strong>
                            </div>
                            <div class="d-flex justify-between">
                                <span>CAP / TIN</span>
                                <strong>
                                    {{ evChargerFinancingSummary.cap }}% /
                                    {{ evChargerFinancingSummary.tin }}%
                                </strong>
                            </div>
                            <div class="d-flex justify-between total">
                                <span>Cuota mensual</span>
                                <strong>
                                    {{ formatCurrency(evChargerFinancingSummary.monthlyFee) }} €/mes
                                </strong>
                            </div>
                            <div class="d-flex justify-between">
                                <span>Total pagado financiado</span>
                                <strong>
                                    {{ formatCurrency(evChargerFinancingSummary.totalPaid) }} €
                                </strong>
                            </div>
                            <div class="d-flex justify-between">
                                <span>Coste financiación</span>
                                <strong>
                                    {{ formatCurrency(evChargerFinancingSummary.cost) }} €
                                </strong>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Observaciones para el presupuesto</label>
                            <div class="input-group">
                                <textarea
                                    class="h-100-px-min w-100-px-min"
                                    data-size="12"
                                    v-model="
                                        opportunity.order.evChargerBudget
                                            .installationNotes
                                    "
                                    :disabled="isReadOnly"
                                    placeholder="Indica cualquier detalle de la instalación, acceso al garaje, contador, cuadro eléctrico, distancia aproximada, etc."
                                ></textarea>
                            </div>
                        </div>

                        <div class="ev-budget-summary mt-20">
                            <div class="d-flex justify-between">
                                <span>Cargador después de descuento</span>
                                <strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.chargerSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div class="d-flex justify-between">
                                <span>Mano de obra</span>
                                <strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.laborSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div class="d-flex justify-between">
                                <span>Distancia del cableado</span>
                                <strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.cableSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div class="d-flex justify-between">
                                <span>Tubo / manguera</span>
                                <strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.modulationCableSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div class="d-flex justify-between">
                                <span
                                    >Boletín / certificado y legalización</span
                                >
                                <strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.certificateSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div
                                class="d-flex justify-between"
                                v-if="
                                    evChargerBudgetTotals.surplusOptimizationSubtotal >
                                    0
                                "
                            >
                                <span
                                    >Optimización de excedentes
                                    fotovoltaicos</span
                                >
                                <strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.surplusOptimizationSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div
                                class="d-flex justify-between"
                                v-if="
                                    opportunity.order.evChargerBudget
                                        .needsCivilWork
                                "
                            >
                                <span>Obra civil</span>
                                <strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.civilWorkSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div
                                class="d-flex justify-between"
                                v-if="
                                    evChargerBudgetTotals.optionalSubtotal > 0
                                "
                            >
                                <span>Opcionales</span>
                                <strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.optionalSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>

                            <div class="separator my-10"></div>

                            <div class="d-flex justify-between">
                                <span>Base imponible</span>
                                <strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.subtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div
                                class="d-flex justify-between"
                                v-if="evChargerBudgetTotals.discountAmount > 0"
                            >
                                <span>Descuento {{ evChargerBudgetTotals.globalDiscount }}%</span>
                                <strong style="color:#e53e3e">-{{ formatCurrency(evChargerBudgetTotals.discountAmount) }} €</strong>
                            </div>
                            <div class="d-flex justify-between">
                                <span
                                    >IVA
                                    {{
                                        opportunity.order.evChargerBudget
                                            .chargerVatPercentage
                                    }}%</span
                                >
                                <strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.vat,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div class="d-flex justify-between total">
                                <span>Total</span>
                                <strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.total,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                        </div>

                        <div class="d-flex justify-end mt-20" data-gap="10">
                            <button
                                class="custom-button"
                                data-size="medium"
                                data-bg="azul"
                                type="button"
                                v-on:click.prevent="sendEvChargerBudget"
                                v-if="!isReadOnly"
                                :disabled="isSendingEvChargerBudget"
                            >
                                <span v-if="isSendingEvChargerBudget">
                                    Enviando...
                                    <i
                                        class="fa-solid fa-spinner-third fa-spin ml-10"
                                    ></i>
                                </span>
                                <span v-else>
                                    Enviar presupuesto
                                    <i class="fas fa-envelope ml-10"></i>
                                </span>
                            </button>

                            <button
                                class="custom-button"
                                data-size="medium"
                                type="button"
                                v-on:click.prevent="generateEvChargerBudget"
                                v-if="!isReadOnly"
                                :disabled="isGeneratingEvChargerBudget"
                            >
                                <span v-if="isGeneratingEvChargerBudget">
                                    Generando presupuesto...
                                    <i
                                        class="fa-solid fa-spinner-third fa-spin ml-10"
                                    ></i>
                                </span>
                                <span v-else>
                                    Generar presupuesto cargador
                                    <i class="fas fa-file-pdf ml-10"></i>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div
                        v-bind:class="{ wrong: errors.CUPS }"
                        class="form-group"
                        v-if="showCupsField"
                    >
                        <p class="my-auto"><label>CUPS</label></p>
                        <div class="input-group">
                            <input
                                v-on:focus="delete errors['CUPS']"
                                data-size="10"
                                v-model="opportunity.order.CUPS"
                                :disabled="isReadOnly"
                                v-on:input="checkCUPS"
                                type="text"
                            />
                        </div>
                        <span v-if="errors.CUPS" class="error">{{
                            errors.CUPS
                        }}</span>
                    </div>

                    <div
                        v-if="
                            opportunity.order.productType === 'cd' &&
                            opportunity.order.product !== ''
                        "
                    >
                        <div
                            class="text my-10 d-flex align-center"
                            data-size="18"
                            v-if="opportunity.order.productType === 'cd'"
                        >
                            <i
                                class="fa-regular fa-fire-flame-simple mr-10"
                            ></i>
                            Gas
                        </div>

                        <div class="d-grid" data-column="2" data-gap="20">
                            <div
                                v-bind:class="{ wrong: errors.feeSecondary }"
                                class="form-group"
                            >
                                <p class="my-auto"><label>Tarifa</label></p>
                                <div class="input-group">
                                    <select
                                        v-model="opportunity.order.feeSecondary"
                                        :disabled="isReadOnly"
                                        v-on:change="changeFeeSecondary"
                                    >
                                        <option value="">Selecciona una</option>
                                        <option
                                            v-for="fee in filteredFeesSecondary"
                                            :key="fee.name"
                                            :value="fee.name"
                                        >
                                            {{ fee.name }}
                                        </option>
                                    </select>
                                </div>
                                <span
                                    v-if="errors.feeSecondary"
                                    class="error"
                                    >{{ errors.feeSecondary }}</span
                                >
                            </div>

                            <div
                                v-if="opportunity.order.feeSecondary"
                                v-bind:class="{
                                    wrong: errors.productSecondary,
                                }"
                                class="form-group"
                            >
                                <p class="my-auto"><label>Producto</label></p>
                                <div class="input-group">
                                    <select
                                        v-model="
                                            opportunity.order.productSecondary
                                        "
                                        :disabled="isReadOnly"
                                        v-on:change="calcCommission"
                                    >
                                        <option value="">Selecciona una</option>
                                        <option
                                            v-for="product in filteredMarketerProductsSecondary"
                                            :key="product.name"
                                            :value="product.name"
                                        >
                                            {{ product.name }}
                                        </option>
                                    </select>
                                </div>
                                <span
                                    v-if="errors.productSecondary"
                                    class="error"
                                    >{{ errors.productSecondary }}</span
                                >
                            </div>
                        </div>

                        <div
                            v-bind:class="{ wrong: errors.CUPSSecondary }"
                            class="form-group"
                        >
                            <p class="my-auto"><label>CUPS</label></p>
                            <div class="input-group">
                                <input
                                    v-on:focus="delete errors.CUPSSecondary"
                                    data-size="10"
                                    v-model.trim="
                                        opportunity.order.CUPSSecondary
                                    "
                                    :disabled="isReadOnly"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.CUPSSecondary" class="error">{{
                                errors.CUPSSecondary
                            }}</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Productos extra</label>

                        <div class="input-group column h-100">
                            <div class="mb-2 d-flex items-center">
                                <i
                                    class="fa-regular fa-magnifying-glass text my-auto mr-10"
                                ></i>
                                <input
                                    type="text"
                                    v-model="extraSearchText"
                                    class="form-control"
                                    placeholder="Buscar extra..."
                                />
                            </div>

                            <div class="separator my-5"></div>

                            <div class="h-100 h-150-px-max scroll-y">
                                <div
                                    class="d-flex my-2"
                                    v-for="extra in extraProductsToSelect"
                                    :key="
                                        extra.id?.$oid || extra.id || extra.name
                                    "
                                >
                                    <div
                                        class="custom-checkbox my-auto"
                                        v-on:click="
                                            toggleSelectExtraProduct(extra)
                                        "
                                    >
                                        <div
                                            v-bind:class="{
                                                selected:
                                                    opportunity.order.extras &&
                                                    opportunity.order.extras.includes(
                                                        extra.id.$oid ||
                                                            extra.id,
                                                    ),
                                            }"
                                        ></div>
                                    </div>

                                    <p class="text my-auto ml-5" data-size="10">
                                        {{ extra.name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comisiones oportunidad -->
                    <div
                        v-if="
                            opportunity.order &&
                            opportunity.order.commissions &&
                            opportunity.order.productType &&
                            opportunity.order.productType !== 'sp' &&
                            this.basicData.userSubdomain ===
                                '65cb57489c2c285441086a43'
                        "
                        class="mt-20"
                    >
                        <div class="separator mt-30 mb-20"></div>

                        <div
                            class="text mb-10"
                            data-size="17"
                            data-weight="600"
                        >
                            Comisiones
                        </div>

                        <!-- COMISIONES NO DUAL -->
                        <template v-if="opportunity.order.productType !== 'cd'">
                            <div class="d-grid" data-column="2" data-gap="20">
                                <!-- Comisión agente -->
                                <div
                                    v-if="opportunityAgentCommission"
                                    class="form-group"
                                >
                                    <label class="line-clamp-1 hidden">
                                        Comisión de
                                        {{
                                            getOpportunityCommissionUserName(
                                                opportunityAgentCommission.userId,
                                            )
                                        }}
                                    </label>

                                    <div class="input-group">
                                        <input
                                            data-size="10"
                                            v-model.trim="
                                                opportunityAgentCommission.commission
                                            "
                                            :disabled="
                                                isReadOnly ||
                                                !canManage(
                                                    'contracts.manageCommissions',
                                                )
                                            "
                                            type="text"
                                        />
                                    </div>
                                </div>

                                <!-- Comisión empresa/subdominio -->
                                <div
                                    v-if="
                                        canManage('contracts.manageCommissions')
                                    "
                                    class="form-group"
                                >
                                    <label>
                                        Comisión {{ basicData.enterprise.name }}
                                    </label>

                                    <div class="input-group">
                                        <input
                                            data-size="10"
                                            v-model.trim="
                                                opportunity.order.commissions
                                                    .subdomain
                                            "
                                            @blur="
                                                calcCommission(
                                                    opportunity.order
                                                        .commissions.subdomain,
                                                )
                                            "
                                            :disabled="
                                                isReadOnly ||
                                                !canManage(
                                                    'contracts.manageCommissions',
                                                )
                                            "
                                            type="text"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Ver jerarquía -->
                            <div
                                v-if="
                                    opportunityFilteredCommissionHierarchy.length
                                "
                                class="d-flex align-center pointer position-relative"
                                @click="
                                    showCommissionHierarchy =
                                        !showCommissionHierarchy
                                "
                            >
                                <p class="ml-10">Ver comisiones</p>

                                <i
                                    class="fa-solid position-absolute ml-5"
                                    data-color="principal"
                                    :class="
                                        showCommissionHierarchy
                                            ? 'fa-chevron-up'
                                            : 'fa-chevron-down'
                                    "
                                />
                            </div>

                            <transition name="accordion">
                                <div
                                    v-if="
                                        showCommissionHierarchy &&
                                        opportunityFilteredCommissionHierarchy.length
                                    "
                                >
                                    <div class="d-flex column mx-10">
                                        <div
                                            class="form-group my-5"
                                            v-for="item in opportunityFilteredCommissionHierarchy"
                                            :key="item.userId"
                                        >
                                            <label
                                                class="line-clamp-1 hidden w-300-px-max"
                                            >
                                                {{
                                                    getOpportunityCommissionUserName(
                                                        item.userId,
                                                    )
                                                }}
                                            </label>

                                            <div class="input-group w-100-px">
                                                <input
                                                    data-size="10"
                                                    v-model.trim="
                                                        item.commission
                                                    "
                                                    :disabled="
                                                        isReadOnly ||
                                                        !canManage(
                                                            'contracts.manageCommissions',
                                                        )
                                                    "
                                                    type="text"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </transition>
                        </template>

                        <!-- COMISIONES DUAL: LUZ + GAS -->
                        <template v-else>
                            <div class="d-grid" data-column="2" data-gap="20">
                                <!-- Comisión total empresa/subdominio -->
                                <div class="form-group">
                                    <label>
                                        Comisión
                                        {{ basicData.enterprise.name }} total
                                    </label>

                                    <div class="input-group">
                                        <input
                                            data-size="10"
                                            v-model.trim="
                                                opportunity.order.commissions
                                                    .subdomain
                                            "
                                            disabled
                                            type="text"
                                        />
                                    </div>
                                </div>

                                <!-- Comisión total agente -->
                                <div
                                    v-if="opportunityAgentCommission"
                                    class="form-group"
                                >
                                    <label>
                                        Comisión de
                                        {{
                                            getOpportunityCommissionUserName(
                                                opportunityAgentCommission.userId,
                                            )
                                        }}
                                        total
                                    </label>

                                    <div class="input-group">
                                        <input
                                            data-size="10"
                                            v-model.trim="
                                                opportunityAgentCommission.commission
                                            "
                                            disabled
                                            type="text"
                                        />
                                    </div>
                                </div>

                                <!-- Comisión luz -->
                                <div
                                    v-if="
                                        opportunity.order.commissions
                                            .electricity
                                    "
                                    class="form-group"
                                >
                                    <label>Comisión luz</label>

                                    <div class="input-group">
                                        <input
                                            data-size="10"
                                            v-model.trim="
                                                opportunity.order.commissions
                                                    .electricity.subdomain
                                            "
                                            @blur="
                                                calcCommission(
                                                    opportunity.order
                                                        .commissions.electricity
                                                        .subdomain,
                                                    'electricity',
                                                )
                                            "
                                            :disabled="
                                                isReadOnly ||
                                                !canManage(
                                                    'contracts.manageCommissions',
                                                )
                                            "
                                            type="text"
                                        />
                                    </div>
                                </div>

                                <!-- Comisión gas -->
                                <div
                                    v-if="opportunity.order.commissions.gas"
                                    class="form-group"
                                >
                                    <label>Comisión gas</label>

                                    <div class="input-group">
                                        <input
                                            data-size="10"
                                            v-model.trim="
                                                opportunity.order.commissions
                                                    .gas.subdomain
                                            "
                                            @blur="
                                                calcCommission(
                                                    opportunity.order
                                                        .commissions.gas
                                                        .subdomain,
                                                    'gas',
                                                )
                                            "
                                            :disabled="
                                                isReadOnly ||
                                                !canManage(
                                                    'contracts.manageCommissions',
                                                )
                                            "
                                            type="text"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Ver jerarquía total -->
                            <div
                                v-if="
                                    opportunityFilteredCommissionHierarchy.length
                                "
                                class="d-flex align-center pointer position-relative"
                                @click="
                                    showCommissionHierarchy =
                                        !showCommissionHierarchy
                                "
                            >
                                <p class="ml-10">Ver comisiones total</p>

                                <i
                                    class="fa-solid position-absolute ml-5"
                                    data-color="principal"
                                    :class="
                                        showCommissionHierarchy
                                            ? 'fa-chevron-up'
                                            : 'fa-chevron-down'
                                    "
                                />
                            </div>

                            <transition name="accordion">
                                <div
                                    v-if="
                                        showCommissionHierarchy &&
                                        opportunityFilteredCommissionHierarchy.length
                                    "
                                >
                                    <div class="d-flex column mx-10">
                                        <div
                                            class="form-group my-5"
                                            v-for="item in opportunityFilteredCommissionHierarchy"
                                            :key="item.userId"
                                        >
                                            <label
                                                class="line-clamp-1 hidden w-300-px-max"
                                            >
                                                {{
                                                    getOpportunityCommissionUserName(
                                                        item.userId,
                                                    )
                                                }}
                                            </label>

                                            <div class="input-group w-100-px">
                                                <input
                                                    data-size="10"
                                                    v-model.trim="
                                                        item.commission
                                                    "
                                                    disabled
                                                    type="text"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </transition>
                        </template>
                    </div>

                    <div class="separator mt-30"></div>

                    <div class="text mt-50" data-size="17" data-weight="600">
                        Dirección de suministro
                    </div>

                    <div class="half-space">
                        <div
                            v-bind:class="{ wrong: errors.order.province }"
                            class="form-group"
                        >
                            <label>Provincia</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="
                                        delete errors['order']['province']
                                    "
                                    data-size="12"
                                    v-model="opportunity.order.province"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.order.province" class="error">{{
                                errors.order.province
                            }}</span>
                        </div>

                        <div
                            v-bind:class="{ wrong: errors.order.town }"
                            class="form-group"
                        >
                            <label>Localidad</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="delete errors['order']['town']"
                                    data-size="12"
                                    v-model="opportunity.order.town"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.order.town" class="error">{{
                                errors.order.town
                            }}</span>
                        </div>
                    </div>

                    <div class="half-space">
                        <div
                            v-bind:class="{ wrong: errors.order.direc }"
                            class="form-group"
                        >
                            <label>Dirección</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="delete errors['order']['direc']"
                                    data-size="12"
                                    v-model="opportunity.order.direc"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.order.direc" class="error">{{
                                errors.order.direc
                            }}</span>
                        </div>

                        <div
                            v-bind:class="{ wrong: errors.order.zip }"
                            class="form-group"
                        >
                            <label>Código postal</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="delete errors['order']['zip']"
                                    data-size="12"
                                    v-model="opportunity.order.zip"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.order.zip" class="error">{{
                                errors.order.zip
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="separator"></div>

            <div class="btn-part">
                <button
                    class="custom-button mr-10"
                    data-size="big"
                    data-bg="rojo"
                    v-on:click.prevent="actionLink('/opportunities')"
                >
                    Cancelar
                </button>
                <button
                    class="custom-button"
                    data-size="big"
                    v-if="!isReadOnly"
                >
                    Guardar <i class="fas fa-chevron-right ml-10"></i>
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import {
    buildEmptyBreakdown,
    calculateCommission,
} from "@/utils/calcCommission";

const ZOCO_ID = "65cb57489c2c285441086a43";
export default {
    name: "OportunitiesRegisterComponent",
    props: ["basicData"],
    data() {
        return {
            selectedObservationDate: new Date().toISOString().split("T")[0],
            isContractOnly: false,
            createUserData: {
                selected: "account",
                types: [
                    { code: "account", title: "Cuenta/Contrato" },
                    { code: "contract", title: "Solo Contrato" },
                ],
            },
            evChargerDefaultCosts: {
                vatPercentage: 21,
                laborPrice: 220,
                certificatePrice: 165,
                cablePricePerMeter: 3.3,
                modulationCablePricePerMeter: 0.55,
                defaultCableMeters: "",
                defaultModulationCableMeters: "",
                civilWorkPrice: 59,
                surplusOptimizationPrice: 104.5,
                paymentMethod:
                    "Transferencia bancaria 60% a la aceptación y restante a la finalización",
            },
            evChargerInstallationCostTable: [
                {
                    meters: 5,
                    cable: 15,
                    tube: 2.5,
                    certificate: 165,
                    labor: 220,
                },
                {
                    meters: 10,
                    cable: 30,
                    tube: 5,
                    certificate: 165,
                    labor: 220,
                },
                {
                    meters: 15,
                    cable: 45,
                    tube: 7.5,
                    certificate: 165,
                    labor: 220,
                },
                {
                    meters: 20,
                    cable: 60,
                    tube: 10,
                    certificate: 165,
                    labor: 220,
                },
                {
                    meters: 25,
                    cable: 75,
                    tube: 12.5,
                    certificate: 165,
                    labor: 286,
                },
                {
                    meters: 30,
                    cable: 90,
                    tube: 15,
                    certificate: 165,
                    labor: 286,
                },
                {
                    meters: 35,
                    cable: 105,
                    tube: 17.5,
                    certificate: 165,
                    labor: 286,
                },
                {
                    meters: 40,
                    cable: 120,
                    tube: 20,
                    certificate: 165,
                    labor: 286,
                },
                {
                    meters: 45,
                    cable: 135,
                    tube: 22.5,
                    certificate: 165,
                    labor: 319,
                },
                {
                    meters: 50,
                    cable: 150,
                    tube: 25,
                    certificate: 165,
                    labor: 319,
                },
                {
                    meters: 55,
                    cable: 165,
                    tube: 27.5,
                    certificate: 165,
                    labor: 319,
                },
                {
                    meters: 60,
                    cable: 180,
                    tube: 30,
                    certificate: 165,
                    labor: 319,
                },
                {
                    meters: 65,
                    cable: 195,
                    tube: 32.5,
                    certificate: 165,
                    labor: 319,
                },
                {
                    meters: 70,
                    cable: 210,
                    tube: 35,
                    certificate: 165,
                    labor: 319,
                },
            ],
            isSendingEvChargerBudget: false,
            evChargerFinancingPlans: [
                { months: 12, cap: 2.0, tin: 5.75, coefficient: 0.08767 },
                { months: 18, cap: 2.0, tin: 5.75, coefficient: 0.05928 },
                { months: 24, cap: 2.0, tin: 5.75, coefficient: 0.04509 },
                { months: 30, cap: 2.0, tin: 6.0, coefficient: 0.03670 },
                { months: 36, cap: 2.0, tin: 6.0, coefficient: 0.03103 },
                { months: 48, cap: 2.0, tin: 6.25, coefficient: 0.02407 },
                { months: 60, cap: 2.0, tin: 6.5, coefficient: 0.01996 },
                { months: 72, cap: 2.0, tin: 6.75, coefficient: 0.01727 },
                { months: 84, cap: 2.0, tin: 6.75, coefficient: 0.01527 },
                { months: 96, cap: 2.0, tin: 6.75, coefficient: 0.01378 },
                { months: 108, cap: 2.5, tin: 6.99, coefficient: 0.01281 },
                { months: 120, cap: 2.5, tin: 6.99, coefficient: 0.01190 },
                { months: 132, cap: 2.5, tin: 6.99, coefficient: 0.01115 },
                { months: 144, cap: 2.5, tin: 6.99, coefficient: 0.01054 },
            ],
            evChargerModels: [
                {
                    brand: "WOLTIO",
                    name: "Cargador monofásico Woltio PRO 10m 32A",
                    chargerModel:
                        "WOLTIO - Cargador monofásico Woltio PRO 10m 32A",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 675.0,
                },
                {
                    brand: "WOLTIO",
                    name: "Cargador monofásico Woltio PRO 10m 40A",
                    chargerModel:
                        "WOLTIO - Cargador monofásico Woltio PRO 10m 40A",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 675.0,
                },
                {
                    brand: "WOLTIO",
                    name: "Cargador trifásico Woltio PLUS sin protecciones",
                    chargerModel:
                        "WOLTIO - Cargador trifásico Woltio PLUS sin protecciones",
                    chargerPower: "7,4kW",
                    phase: "trifasico",
                    pvp: 621.43,
                },
                {
                    brand: "OHME",
                    name: "HOME PRO 7,4 / 4G / 5",
                    chargerModel: "OHME - HOME PRO 7,4 / 4G / 5",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 719.0,
                },
                {
                    brand: "OHME",
                    name: "ePodS 7,4 4G T2S",
                    chargerModel: "OHME - ePodS 7,4 4G T2S",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 669.0,
                },
                {
                    brand: "V2C",
                    name: "V2C TRYDAN  7,4 KW T2 5M PROTECCIONES",
                    chargerModel: "V2C - V2C TRYDAN  7,4 KW T2 5M PROTECCIONES",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 777.9,
                },
                {
                    brand: "V2C",
                    name: "V2C TRYDAN  7,4 KW T2 5M",
                    chargerModel: "V2C - V2C TRYDAN  7,4 KW T2 5M",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 646.6,
                },
                {
                    brand: "V2C",
                    name: "CARGADOR V2C PRO 10M 7.4KW + PROTECCIONE",
                    chargerModel:
                        "V2C - CARGADOR V2C PRO 10M 7.4KW + PROTECCIONE",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 891.97,
                },
                {
                    brand: "WALLBOX",
                    name: "PULSAR PLUS 7,4 kW (1ph - 32A)",
                    chargerModel: "WALLBOX - PULSAR PLUS 7,4 kW (1ph - 32A)",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 842.86,
                },
                {
                    brand: "WALLBOX",
                    name: "PULSAR PLUS 11 kW (3ph - 16A)",
                    chargerModel: "WALLBOX - PULSAR PLUS 11 kW (3ph - 16A)",
                    chargerPower: "7,4kW",
                    phase: "trifasico",
                    pvp: 891.43,
                },
                {
                    brand: "WALLBOX",
                    name: "PULSAR PLUS 22 kW (3ph - 32A)",
                    chargerModel: "WALLBOX - PULSAR PLUS 22 kW (3ph - 32A)",
                    chargerPower: "7,4kW",
                    phase: "trifasico",
                    pvp: 905.71,
                },
                {
                    brand: "POLICHARGER",
                    name: "Policharger ON-T1 7,4kW",
                    chargerModel: "POLICHARGER - Policharger ON-T1 7,4kW",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 621.76,
                },
                {
                    brand: "POLICHARGER",
                    name: "Policharger ON-T2 7,4kW",
                    chargerModel: "POLICHARGER - Policharger ON-T2 7,4kW",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 618.71,
                },
                {
                    brand: "POLICHARGER",
                    name: "Policharger NW-T1 con protecciones 7,4kW",
                    chargerModel:
                        "POLICHARGER - Policharger NW-T1 con protecciones 7,4kW",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 749.56,
                },
                {
                    brand: "POLICHARGER",
                    name: "Policharger ON-T23F 22kW",
                    chargerModel: "POLICHARGER - Policharger ON-T23F 22kW",
                    chargerPower: "7,4kW",
                    phase: "trifasico",
                    pvp: 673.49,
                },
                {
                    brand: "POLICHARGER",
                    name: "Policharger NW-T23F con protecciones 22kW",
                    chargerModel:
                        "POLICHARGER - Policharger NW-T23F con protecciones 22kW",
                    chargerPower: "7,4kW",
                    phase: "trifasico",
                    pvp: 925.03,
                },
                {
                    brand: "ZAPTEC",
                    name: "Zaptec Go Asphalt Black",
                    chargerModel: "ZAPTEC - Zaptec Go Asphalt Black",
                    chargerPower: "7,4kW",
                    phase: "",
                    pvp: 740.0,
                },
                {
                    brand: "ZAPTEC",
                    name: "Zaptec Go 2 Asphalt Black AVANZADO",
                    chargerModel: "ZAPTEC - Zaptec Go 2 Asphalt Black AVANZADO",
                    chargerPower: "7,4kW",
                    phase: "",
                    pvp: 1004.29,
                },
                {
                    brand: "ORBIS",
                    name: "ORBIS Viaris Isi Mono. 7,4kw+Mang.T2 5m+Protec",
                    chargerModel:
                        "ORBIS Viaris Isi Mono. 7,4kw+Mang.T2 5m+Protec",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 795.71,
                },
            ],
            opportunity: {
                name: "",
                CIF: "",
                phone: "",
                landLinePhone: "",
                email: "",
                website: "",
                usersIds: [],
                sector: "",
                source: "",
                status: "",
                account: "",
                contact: {
                    value: "",
                    isFromContacts: true,
                },
                position: "",
                billingInfo: {
                    community: "",
                    province: "",
                    locality: "",
                    address: "",
                    postal: "",
                },
                customFields: [],
                order: {
                    name: "",
                    productType: "",
                    marketer: "",
                    fee: "",
                    product: "",
                    CUPS: "",
                    consumption: "",
                    hiredPotency: "",
                    consumptionData: {
                        consumption: [],
                        hiredPotency: [],
                    },
                    energyFees: {},
                    potencyFees: {},
                    feeSecondary: "",
                    productSecondary: "",
                    CUPSSecondary: "",
                    consumptionSecondary: "",
                    direc: "",
                    zip: "",
                    town: "",
                    province: "",
                    extras: [],
                    usersIds: [],
                    commissions: {
                        subdomain: null,
                        breakdown: [],
                    },
                    decommissions: {
                        subdomain: null,
                        breakdown: [],
                    },
                    evChargerBudget: {
                        chargerModel: "",
                        chargerBrand: "",
                        chargerPower: "7,4kW",
                        chargerPhase: "",
                        chargerVatPercentage: 21,
                        installationType: "",
                        contractedPower: "",
                        cableMeters: 5,
                        modulationCableMeters: 5,
                        installationMetersRange: 5,
                        installationCablePrice: 66,
                        installationTubePrice: 2.75,
                        chargerInstallationPrice: "",
                        chargerInstallationDiscount: 0,
                        globalDiscount: 0,
                        laborPrice: 220,
                        certificatePrice: 165,
                        cablePricePerMeter: 3.3,
                        modulationCablePricePerMeter: 0.55,
                        needsCivilWork: false,
                        civilWorkPrice: 59,
                        civilWorkDescription: "",
                        hasPhotovoltaic: false,
                        wantsSurplusOptimization: false,
                        surplusOptimizationPrice: 104.5,
                        optionalItems: [],
                        installationNotes:
                            "La mano de obra incluye hasta 5 horas de trabajo. En caso de que la instalación requiera más tiempo por dificultades técnicas o de acceso, se facturará el tiempo adicional al precio/hora acordado.",
                        preferredInstallationDate: "",
                        paymentMethod:
                            "Transferencia bancaria 60% a la aceptación y restante a la finalización",
                        financingEnabled: false,
                        financingMonths: 36,
                        financing: null,
                    },
                },
                observations: "",
            },
            errors: {
                billingInfo: {},
                order: {
                    evChargerBudget: {},
                },
            },
            sources: [],
            sectors: [],
            statuses: [],
            GEO: {
                communities: "",
                provinces: "",
                localities: "",
                addresses: "",
            },
            urlImage: "",
            selectValues: {
                sector: [],
                source: [],
                status: [],
            },
            accounts: [],
            contacts: [],
            marketers: [],
            zocoCommissionRanges: null,
            userHierarchy: [],
            emptyBreakdown: [],
            marketerProductsOthers: {
                n: ["Sin excedentes", "Con excedentes", "Compartido"],
                crm: ["Contrato de CRM", "Contrato de colaborador"],
                electricCar: ["Cargador Hibrido", "Cargador Electrico 100%"],
                electricityMeter: ["Menos de 450kW", "Más de 450kW"],
            },
            searchAccountText: "",
            searchAddressText: "",
            isAccFocused: false,
            extraSearchText: "",
            productArchived: false,
            isGeneratingEvChargerBudget: false,
            showCommissionHierarchy: false,
        };
    },
    created() {
        this.fetchMarketers();
        this.fetchZocoCommissionRanges();
    },
    mounted() {
        this.fetchSelectValues();
        this.fetchAccounts();
        this.fetchContacts();

        if (this.$cookies.get("temporalCreateOppCookie")) {
            this.setOppFromOpportunity();
        }
    },
    watch: {
        "basicData.userLogged"() {
            this.fetchAccounts();
            this.fetchContacts();
        },
        "opportunity.order.evChargerBudget.needsCivilWork"(value) {
            if (!value) {
                this.opportunity.order.evChargerBudget.civilWorkPrice =
                    this.evChargerDefaultCosts.civilWorkPrice;
                this.opportunity.order.evChargerBudget.civilWorkDescription =
                    "";
            }
        },
        "opportunity.order.evChargerBudget.hasPhotovoltaic"(value) {
            if (!value) {
                this.opportunity.order.evChargerBudget.wantsSurplusOptimization = false;
                this.opportunity.order.evChargerBudget.surplusOptimizationPrice =
                    this.evChargerDefaultCosts.surplusOptimizationPrice;
            }
        },
        isElectricCarCharger(value) {
            if (value) {
                this.ensureEvChargerBudgetStructure();
            } else {
                this.resetEvChargerBudgetIfNotCharger();
            }
        },
        "opportunity.usersIds": {
            async handler(newVal) {
                if (!Array.isArray(newVal)) {
                    this.opportunity.usersIds = [];
                    return;
                }

                if (this.opportunity.order) {
                    this.opportunity.order.usersIds = [...newVal];
                }

                await this.ensureCommissionHierarchy();
                this.calcCommission();
            },
            immediate: true,
            deep: true,
        },

        "opportunity.order.product": function () {
            this.calcCommission();
        },

        "opportunity.order.productSecondary": function () {
            this.calcCommission();
        },

        "opportunity.order.consumption": function () {
            this.calcCommission();
        },

        "opportunity.order.hiredPotency": function () {
            this.calcCommission();
        },

        "opportunity.order.energyFees": {
            deep: true,
            handler() {
                this.calcCommission();
            },
        },

        "opportunity.observations"() {
            this.selectedObservationDate = new Date()
                .toISOString()
                .split("T")[0];
        },

        "opportunity.order.potencyFees": {
            deep: true,
            handler() {
                this.calcCommission();
            },
        },
    },
    methods: {
        insertDateInObservations() {
            const dateStr = this.selectedObservationDate || new Date().toISOString().split('T')[0]
            const [anio, mes, dia] = dateStr.split('-')
            const fecha = `[${dia}/${mes}/${anio}] `

            const textarea = document.getElementById('observationsTextarea')
            const start = textarea.selectionStart
            const end = textarea.selectionEnd
            const texto = this.opportunity.observations || ''

            this.opportunity.observations = texto.slice(0, start) + fecha + texto.slice(end)

            this.$nextTick(() => {
                textarea.selectionStart = textarea.selectionEnd = start + fecha.length
                textarea.focus()
            })
        },
        async sendEvChargerBudget() {
            this.errors.order.evChargerBudget = {};
            let hasErrors = false;

            if (!this.opportunity.order.evChargerBudget.chargerModel) {
                this.errors.order.evChargerBudget.chargerModel =
                    this.getErrorMessage("isEmpty");
                hasErrors = true;
            }

            if (!this.opportunity.order.evChargerBudget.chargerPower) {
                this.errors.order.evChargerBudget.chargerPower =
                    this.getErrorMessage("isEmpty");
                hasErrors = true;
            }

            if (hasErrors) return;

            // Obtener email
            let email = this.opportunity.email;

            if (!email || email.trim() === "") {
                const { value: inputEmail } = await Swal.fire({
                    title: "Email del destinatario",
                    text: "La oportunidad no tiene email. Introduce uno para enviar el presupuesto:",
                    input: "email",
                    inputPlaceholder: "ejemplo@correo.com",
                    showCancelButton: true,
                    confirmButtonText: "Enviar",
                    cancelButtonText: "Cancelar",
                    inputValidator: (value) => {
                        if (!value) return "El email es obligatorio";
                        const regex =
                            /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                        if (!regex.test(value)) return "El email no es válido";
                    },
                });

                if (!inputEmail) return;
                email = inputEmail;
            }

            // Elegir qué importe debe pagar el cliente en el enlace de Stripe del PDF
            const { value: paymentOption } = await Swal.fire({
                title: "¿Qué importe debe pagar el cliente?",
                input: "radio",
                inputOptions: {
                    40: "Pago 40%",
                    60: "Pago 60%",
                    100: "Importe total (100%)",
                },
                inputValue: "60",
                showCancelButton: true,
                confirmButtonText: "Enviar presupuesto",
                cancelButtonText: "Cancelar",
                inputValidator: (value) => {
                    if (!value) return "Debes elegir una opción";
                },
            });

            if (!paymentOption) return;
            const depositPercentage = Number(paymentOption) / 100; // 0.40, 0.60 o 1.0 (total)

            this.prepareEvChargerBudgetForSave();

            const payload = {
                opportunity: this.opportunity,
                evChargerBudgetTotals: this.evChargerBudgetTotals,
                basicData: this.basicData,
                userLogged: this.basicData.userLogged,
                recipientEmail: email,
                depositPercentage,
            };

            const formData = new FormData();
            formData.append("payload", JSON.stringify(payload));

            this.isSendingEvChargerBudget = true;

            try {
                await axios.post(
                    "/api/opportunities/sendEvChargerPDF",
                    formData,
                    {
                        headers: { "Content-Type": "multipart/form-data" },
                    },
                );

                Swal.fire({
                    icon: "success",
                    title: "¡Enviado!",
                    text: `El presupuesto ha sido enviado a ${email}`,
                    timer: 2000,
                    timerProgressBar: true,
                });
            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: "error",
                    title: "Error al enviar",
                    text: "No se ha podido enviar el presupuesto por email",
                });
            } finally {
                this.isSendingEvChargerBudget = false;
            }
        },
        parseStringToNumber(value) {
            if (typeof value === "number") return value;
            if (typeof value === "string") {
                if (value.trim() === "") return 0;
                return parseFloat(value.replace(",", ".")) || 0;
            }
            return 0;
        },
        javascriptonFormEnter(e) {
            if (e.target.tagName !== "TEXTAREA") {
                e.preventDefault();
            }
        },
        canManage(code) {
            const user = this.basicData?.userLogged;
            const subdomain = this.basicData?.userSubdomain;

            if (!user || !subdomain) return false;

            const label = user.label;
            const labelsPermissions = subdomain.labels_permissions;

            if (!label || !labelsPermissions) return false;
            if (!labelsPermissions[label]) return false;
            if (!code || !code.includes(".")) return false;

            const [module, action] = code.split(".");

            const modulePermissions = labelsPermissions[label][module];

            return (
                Array.isArray(modulePermissions) &&
                modulePermissions.includes(action)
            );
        },

        isAssignedToZoco() {
            const ids = Array.isArray(this.opportunity?.order?.usersIds)
                ? this.opportunity.order.usersIds
                : Array.isArray(this.opportunity?.usersIds)
                  ? this.opportunity.usersIds
                  : [];

            return (
                ids.map(String).includes(ZOCO_ID) ||
                String(this.opportunity?.order?.assignedTo || "") === ZOCO_ID ||
                String(this.opportunity?.assignedTo || "") === ZOCO_ID
            );
        },

        getMainCommissionUserId() {
            const ids = Array.isArray(this.opportunity?.usersIds)
                ? this.opportunity.usersIds
                : [];

            return ids.find((id) => String(id) !== ZOCO_ID) || ids[0] || null;
        },

        async fetchUserHierarchy(id) {
            if (!id) return;

            await axios
                .get(`/api/session/getAllSuperiors/${id}`)
                .then((res) => {
                    this.userHierarchy = res.data;
                })
                .catch((err) => console.log(err));
        },

        async fetchZocoCommissionRanges() {
            await axios
                .get("/api/enterprises/65cb57489c2c285441086a43")
                .then((res) => {
                    this.zocoCommissionRanges = res.data.data.commissionRanges;
                })
                .catch((err) => console.log(err));
        },

        async ensureCommissionHierarchy() {
            if (!this.opportunity.order) return;

            if (!Array.isArray(this.opportunity.usersIds)) {
                this.opportunity.usersIds = [];
            }

            this.opportunity.order.usersIds = [...this.opportunity.usersIds];

            const userId = this.getMainCommissionUserId();

            if (userId) {
                await this.fetchUserHierarchy(userId);
            }

            if (this.userHierarchy?.length) {
                this.emptyBreakdown = buildEmptyBreakdown(
                    this.userHierarchy,
                    this.isAssignedToZoco(),
                );
            } else {
                this.emptyBreakdown = [];
            }

            if (!this.opportunity.order.commissions) {
                this.clearCommissions();
            }
        },

        clearCommissions() {
            if (!this.opportunity.order) return;

            const emptyBreakdown = Array.isArray(this.emptyBreakdown)
                ? this.emptyBreakdown.map((item) => ({ ...item }))
                : [];

            this.opportunity.order.commissions = {
                subdomain: null,
                breakdown: emptyBreakdown,
            };

            if (this.opportunity.order.productType === "cd") {
                this.opportunity.order.commissions.electricity = {
                    subdomain: null,
                    breakdown: emptyBreakdown.map((item) => ({ ...item })),
                };

                this.opportunity.order.commissions.gas = {
                    subdomain: null,
                    breakdown: emptyBreakdown.map((item) => ({ ...item })),
                };
            }
        },

        calcCommission(
            baseCommission = null,
            type = null,
            mode = "commissions",
        ) {
            if (!this.opportunity.order) return;

            const order = this.opportunity.order;
            const productData = this.getProductData();

            if (!productData) {
                this.clearCommissions();
                return;
            }

            const { marketerInfo, productInfo, feeInfo } = productData;

            if (!this.emptyBreakdown?.length && this.userHierarchy?.length) {
                this.emptyBreakdown = buildEmptyBreakdown(
                    this.userHierarchy,
                    this.isAssignedToZoco(),
                );
            }

            const baseParams = {
                userListTop: this.userHierarchy,
                assignedToZoco: this.isAssignedToZoco(),
                marketer: marketerInfo?._id,
                commissionRanges: this.basicData?.enterprise?.commissionRanges,
                commissionRangesZoco: this.zocoCommissionRanges,
                commissionType: feeInfo?.commissionType,
                energyData: {
                    annual: this.parseStringToNumber(order?.consumption),
                    byPeriods: order?.consumptionData?.consumption || [],
                },
                powerData: {
                    max: this.parseStringToNumber(order?.hiredPotency),
                    byPeriods: order?.consumptionData?.hiredPotency || [],
                },
                fees: {
                    power: Object.values(order?.potencyFees || {}),
                    energy: Object.values(order?.energyFees || {}),
                },
            };

            if (order.productType === "cd") {
                let electricityResult = order[mode]?.electricity || {
                    subdomain: 0,
                    breakdown: this.emptyBreakdown.map((item) => ({ ...item })),
                };

                let gasResult = order[mode]?.gas || {
                    subdomain: 0,
                    breakdown: this.emptyBreakdown.map((item) => ({ ...item })),
                };

                if (!type || type === "electricity") {
                    electricityResult = calculateCommission({
                        ...baseParams,
                        commissions: feeInfo.electricity,
                        ...(type === "electricity" &&
                            baseCommission !== null && {
                                baseCommission:
                                    this.parseStringToNumber(baseCommission),
                            }),
                    });
                }

                if (!type || type === "gas") {
                    gasResult = calculateCommission({
                        ...baseParams,
                        commissions: feeInfo.gas,
                        ...(type === "gas" &&
                            baseCommission !== null && {
                                baseCommission:
                                    this.parseStringToNumber(baseCommission),
                            }),
                    });
                }

                const breakdownMap = new Map();

                [
                    ...electricityResult.breakdown,
                    ...gasResult.breakdown,
                ].forEach((item) => {
                    if (breakdownMap.has(item.userId)) {
                        const oldItem = breakdownMap.get(item.userId);
                        oldItem.commission =
                            Math.round(
                                (this.parseStringToNumber(oldItem.commission) +
                                    this.parseStringToNumber(item.commission)) *
                                    100,
                            ) / 100;
                    } else {
                        breakdownMap.set(item.userId, { ...item });
                    }
                });

                order[mode] = {
                    subdomain:
                        Math.round(
                            (this.parseStringToNumber(
                                electricityResult.subdomain,
                            ) +
                                this.parseStringToNumber(gasResult.subdomain)) *
                                100,
                        ) / 100,
                    breakdown: Array.from(breakdownMap.values()),
                    electricity: electricityResult,
                    gas: gasResult,
                };

                return;
            }

            order[mode] = calculateCommission({
                ...baseParams,
                commissions: feeInfo,
                ...(!type &&
                    baseCommission !== null && {
                        baseCommission:
                            this.parseStringToNumber(baseCommission),
                    }),
            });
        },

        async prepareOpportunityCommissionsForSave() {
            await this.ensureCommissionHierarchy();
            this.calcCommission();

            if (!this.opportunity.order.commissions) {
                this.clearCommissions();
            }
        },

        getOpportunityCommissionUserName(userId) {
            const users = [
                ...(Array.isArray(this.userHierarchy)
                    ? this.userHierarchy
                    : []),
                ...(Array.isArray(this.basicData?.userList)
                    ? this.basicData.userList
                    : []),
                ...(Array.isArray(this.basicData?.subdomainUserList)
                    ? this.basicData.subdomainUserList
                    : []),
            ];

            const user = users.find(
                (item) => String(item._id || item.id || "") === String(userId),
            );

            if (!user) return "usuario";

            return [
                user.firstName,
                user.secondName,
                user.lastName,
                user.surname,
                user.email,
            ]
                .filter(Boolean)
                .join(" ");
        },

        safeNumber(value, fallback = 0) {
            const number = Number(value);
            return Number.isFinite(number) ? number : fallback;
        },
        formatCurrency(value) {
            return this.safeNumber(value).toLocaleString("es-ES", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
        getEvChargerFinancingSummary(amount = null) {
            const budget = this.opportunity?.order?.evChargerBudget || {};
            const plan = this.selectedEvChargerFinancingPlan;
            const financedAmount =
                amount !== null && amount !== undefined
                    ? this.safeNumber(amount)
                    : this.safeNumber(this.evChargerBudgetTotals?.total);

            if (!budget.financingEnabled || !plan || financedAmount <= 0) {
                return null;
            }

            const round = (value) => Math.round(this.safeNumber(value) * 100) / 100;
            const monthlyFee = round(financedAmount * plan.coefficient);
            const totalPaid = round(monthlyFee * plan.months);
            const cost = round(totalPaid - financedAmount);
            const capAmount = round(financedAmount * (plan.cap / 100));

            return {
                enabled: true,
                amount: round(financedAmount),
                months: plan.months,
                cap: plan.cap,
                tin: plan.tin,
                coefficient: plan.coefficient,
                monthlyFee,
                totalPaid,
                cost,
                capAmount,
            };
        },
        getEvChargerFinancingMonthlyFeeByPlan(plan) {
            if (!plan) return 0;
            return (
                Math.round(
                    this.safeNumber(this.evChargerBudgetTotals?.total) *
                        this.safeNumber(plan.coefficient) *
                        100,
                ) / 100
            );
        },
        formatFinancingPlanLabel(plan) {
            if (!plan) return '';
            const monthlyFee = this.getEvChargerFinancingMonthlyFeeByPlan(plan);
            return `${plan.months} meses · ${this.formatCurrency(monthlyFee)} €/mes · TIN ${String(plan.tin).replace('.', ',')}%`;
        },
        getPhaseLabel(phase) {
            if (phase === "monofasico") return "Monofásico";
            if (phase === "trifasico") return "Trifásico";
            return "";
        },
        getEvChargerBudgetDefaults() {
            return {
                chargerModel: "",
                chargerBrand: "",
                chargerPower: "7,4kW",
                chargerPhase: "",
                chargerVatPercentage: this.evChargerDefaultCosts.vatPercentage,
                installationType: "",
                contractedPower: "",
                cableMeters: this.evChargerDefaultCosts.defaultCableMeters,
                modulationCableMeters:
                    this.evChargerDefaultCosts.defaultModulationCableMeters,
                installationMetersRange: "",
                installationCablePrice: 0,
                installationTubePrice: 0,
                chargerInstallationPrice: "",
                chargerInstallationDiscount: 0,
                globalDiscount: 0,
                laborPrice: this.evChargerDefaultCosts.laborPrice,
                certificatePrice: this.evChargerDefaultCosts.certificatePrice,
                cablePricePerMeter:
                    this.evChargerDefaultCosts.cablePricePerMeter,
                modulationCablePricePerMeter:
                    this.evChargerDefaultCosts.modulationCablePricePerMeter,
                needsCivilWork: false,
                civilWorkPrice: this.evChargerDefaultCosts.civilWorkPrice,
                civilWorkDescription: "",
                hasPhotovoltaic: false,
                wantsSurplusOptimization: false,
                surplusOptimizationPrice:
                    this.evChargerDefaultCosts.surplusOptimizationPrice,
                optionalItems: [],
                installationNotes:
                    "La mano de obra incluye hasta 5 horas de trabajo. En caso de que la instalación requiera más tiempo por dificultades técnicas o de acceso, se facturará el tiempo adicional al precio/hora acordado.",
                preferredInstallationDate: "",
                paymentMethod: this.evChargerDefaultCosts.paymentMethod,
                financingEnabled: false,
                financingMonths: 36,
                financing: null,
                totals: null,
                budgetLines: [],
                budgetType: "",
            };
        },
        getEvBillableCableMeters(meters) {
            const numericMeters = this.safeNumber(meters);

            if (numericMeters <= 0) return 0;

            return Math.min(numericMeters, 70) * 4;
        },
        getEvChargerInstallationCostsByMeters(meters) {
            const numericMeters = this.safeNumber(meters);

            if (
                numericMeters <= 0 ||
                !Array.isArray(this.evChargerInstallationCostTable)
            ) {
                return null;
            }

            const sortedTable = [...this.evChargerInstallationCostTable].sort(
                (a, b) => a.meters - b.meters,
            );

            return (
                sortedTable.find((row) => numericMeters <= row.meters) ||
                sortedTable[sortedTable.length - 1]
            );
        },
        applyEvInstallationDefaultsByMeters() {
            this.ensureEvChargerBudgetStructure();

            const budget = this.opportunity.order.evChargerBudget;

            const clampMeters = (value) => {
                const meters = this.safeNumber(value);

                if (meters <= 0) return 0;

                return Math.min(meters, 70);
            };

            const cableMeters = clampMeters(budget.cableMeters);
            const tubeMeters = clampMeters(
                budget.modulationCableMeters !== "" &&
                    budget.modulationCableMeters !== null &&
                    budget.modulationCableMeters !== undefined
                    ? budget.modulationCableMeters
                    : cableMeters,
            );

            budget.cableMeters = cableMeters;
            budget.modulationCableMeters = tubeMeters;

            const row = this.getEvChargerInstallationCostsByMeters(cableMeters);

            budget.installationMetersRange = row ? row.meters : "";

            if (
                budget.cablePricePerMeter === "" ||
                budget.cablePricePerMeter === null ||
                budget.cablePricePerMeter === undefined
            ) {
                budget.cablePricePerMeter =
                    this.evChargerDefaultCosts.cablePricePerMeter;
            }

            if (
                budget.modulationCablePricePerMeter === "" ||
                budget.modulationCablePricePerMeter === null ||
                budget.modulationCablePricePerMeter === undefined
            ) {
                budget.modulationCablePricePerMeter =
                    this.evChargerDefaultCosts.modulationCablePricePerMeter;
            }

            // Mano de obra se actualiza automáticamente desde la tabla según distancia
            if (row) budget.laborPrice = row.labor;

            budget.installationCablePrice =
                this.getEvBillableCableMeters(cableMeters) *
                this.safeNumber(budget.cablePricePerMeter);
            budget.installationTubePrice =
                tubeMeters *
                this.safeNumber(budget.modulationCablePricePerMeter);
        },
        ensureEvChargerBudgetStructure() {
            if (!this.opportunity || !this.opportunity.order) return;

            if (!this.opportunity.order.evChargerBudget) {
                this.opportunity.order.evChargerBudget = {};
            }

            const current = this.opportunity.order.evChargerBudget;
            const defaults = this.getEvChargerBudgetDefaults();

            this.opportunity.order.evChargerBudget = {
                ...defaults,
                ...current,
                optionalItems: Array.isArray(current.optionalItems)
                    ? current.optionalItems
                    : [],
                budgetLines: Array.isArray(current.budgetLines)
                    ? current.budgetLines
                    : [],
                totals: current.totals || defaults.totals,
            };

            if (!this.errors.order) {
                this.errors.order = {
                    evChargerBudget: {},
                };
            }

            if (!this.errors.order.evChargerBudget) {
                this.errors.order.evChargerBudget = {};
            }
        },
        applyEvChargerDefaultsByModel() {
            this.ensureEvChargerBudgetStructure();

            const budget = this.opportunity.order.evChargerBudget;
            const selected = this.evChargerModels.find(
                (charger) => charger.chargerModel === budget.chargerModel,
            );

            if (!this.errors.order) {
                this.errors.order = {
                    evChargerBudget: {},
                };
            }

            if (!this.errors.order.evChargerBudget) {
                this.errors.order.evChargerBudget = {};
            }

            delete this.errors.order.evChargerBudget.chargerModel;
            delete this.errors.order.evChargerBudget.chargerPower;

            if (!selected) return;

            budget.chargerBrand = selected.brand;
            budget.chargerPower = selected.chargerPower || budget.chargerPower;
            budget.chargerPhase = selected.phase || "";
            budget.chargerInstallationPrice = selected.pvp;

            // Solo rellenamos valores si están vacíos.
            // Así no se pisan importes editados manualmente.
            if (
                budget.chargerVatPercentage === "" ||
                budget.chargerVatPercentage === null ||
                budget.chargerVatPercentage === undefined
            ) {
                budget.chargerVatPercentage =
                    this.evChargerDefaultCosts.vatPercentage;
            }

            if (
                budget.laborPrice === "" ||
                budget.laborPrice === null ||
                budget.laborPrice === undefined
            ) {
                budget.laborPrice = this.evChargerDefaultCosts.laborPrice;
            }

            if (
                budget.certificatePrice === "" ||
                budget.certificatePrice === null ||
                budget.certificatePrice === undefined
            ) {
                budget.certificatePrice =
                    this.evChargerDefaultCosts.certificatePrice;
            }

            if (
                budget.cablePricePerMeter === "" ||
                budget.cablePricePerMeter === null ||
                budget.cablePricePerMeter === undefined
            ) {
                budget.cablePricePerMeter =
                    this.evChargerDefaultCosts.cablePricePerMeter;
            }

            if (
                budget.modulationCablePricePerMeter === "" ||
                budget.modulationCablePricePerMeter === null ||
                budget.modulationCablePricePerMeter === undefined
            ) {
                budget.modulationCablePricePerMeter =
                    this.evChargerDefaultCosts.modulationCablePricePerMeter;
            }

            if (
                budget.cableMeters === "" ||
                budget.cableMeters === null ||
                budget.cableMeters === undefined
            ) {
                budget.cableMeters =
                    this.evChargerDefaultCosts.defaultCableMeters;
            }

            if (
                budget.modulationCableMeters === "" ||
                budget.modulationCableMeters === null ||
                budget.modulationCableMeters === undefined
            ) {
                budget.modulationCableMeters =
                    this.evChargerDefaultCosts.defaultModulationCableMeters;
            }

            if (
                budget.chargerInstallationDiscount === "" ||
                budget.chargerInstallationDiscount === null ||
                budget.chargerInstallationDiscount === undefined
            ) {
                budget.chargerInstallationDiscount = 0;
            }

            if (
                budget.civilWorkPrice === "" ||
                budget.civilWorkPrice === null ||
                budget.civilWorkPrice === undefined
            ) {
                budget.civilWorkPrice =
                    this.evChargerDefaultCosts.civilWorkPrice;
            }

            if (!budget.needsCivilWork) {
                budget.civilWorkDescription = "";
            }

            this.applyEvInstallationDefaultsByMeters();
        },
        getOptionalItemTotal(optional) {
            const item = optional || {};
            const quantity = this.safeNumber(item.quantity);
            const price = this.safeNumber(item.price);
            const discount = this.safeNumber(item.discount);

            return quantity * price * (1 - discount / 100);
        },
        prepareEvChargerBudgetForSave() {
            if (!this.isElectricCarCharger) return;

            this.ensureEvChargerBudgetStructure();

            const budget = this.opportunity.order.evChargerBudget;

            if (!budget.hasPhotovoltaic) {
                budget.wantsSurplusOptimization = false;
                budget.surplusOptimizationPrice =
                    this.evChargerDefaultCosts.surplusOptimizationPrice;
            }

            if (!budget.needsCivilWork) {
                budget.civilWorkPrice =
                    this.evChargerDefaultCosts.civilWorkPrice;
                budget.civilWorkDescription = "";
            }

            const roundMoney = (value) =>
                Math.round(this.safeNumber(value) * 100) / 100;
            const totals = this.evChargerBudgetTotals;
            const budgetLines = [];

            const addLine = (
                key,
                description,
                quantity,
                unitPrice,
                discount,
                amount,
            ) => {
                const qty = this.safeNumber(quantity);
                const price = roundMoney(unitPrice);
                const disc = this.safeNumber(discount);
                const total = roundMoney(amount);

                if (total <= 0 && price <= 0 && qty <= 0) return;

                budgetLines.push({
                    key,
                    description,
                    quantity: qty,
                    unitPrice: price,
                    discount: disc,
                    amount: total,
                });
            };

            addLine(
                "charger",
                `Cargador ${budget.chargerModel || ""} ${budget.chargerPower || ""}`.trim(),
                1,
                budget.chargerInstallationPrice,
                budget.chargerInstallationDiscount,
                totals.chargerSubtotal,
            );

            addLine(
                "labor",
                "Mano de obra, desplazamiento y pequeño material",
                1,
                budget.laborPrice,
                0,
                totals.laborSubtotal,
            );

            addLine(
                "cable",
                "Distancia del cableado",
                this.getEvBillableCableMeters(budget.cableMeters),
                budget.cablePricePerMeter,
                0,
                totals.cableSubtotal,
            );

            addLine(
                "modulationCable",
                "Manguera apantallada para modulación de potencia",
                budget.modulationCableMeters,
                budget.modulationCablePricePerMeter,
                0,
                totals.modulationCableSubtotal,
            );

            addLine(
                "certificate",
                "Certificado instalación eléctrica y legalización",
                1,
                budget.certificatePrice,
                0,
                totals.certificateSubtotal,
            );

            if (budget.hasPhotovoltaic && budget.wantsSurplusOptimization) {
                addLine(
                    "surplusOptimization",
                    "Optimización de excedentes fotovoltaicos",
                    1,
                    budget.surplusOptimizationPrice,
                    0,
                    totals.surplusOptimizationSubtotal,
                );
            }

            if (budget.needsCivilWork) {
                addLine(
                    "civilWork",
                    budget.civilWorkDescription
                        ? `Obra civil necesaria - ${budget.civilWorkDescription}`
                        : "Obra civil necesaria",
                    1,
                    budget.civilWorkPrice,
                    0,
                    totals.civilWorkSubtotal,
                );
            }

            if (Array.isArray(budget.optionalItems)) {
                budget.optionalItems.forEach((optional, index) => {
                    const amount = this.getOptionalItemTotal(optional);

                    addLine(
                        `optional_${index + 1}`,
                        optional.description
                            ? `${optional.name || "Opcional"} - ${optional.description}`
                            : optional.name || `Opcional ${index + 1}`,
                        optional.quantity,
                        optional.price,
                        optional.discount,
                        amount,
                    );
                });
            }

            budget.totals = {
                chargerSubtotal: roundMoney(totals.chargerSubtotal),
                laborSubtotal: roundMoney(totals.laborSubtotal),
                certificateSubtotal: roundMoney(totals.certificateSubtotal),
                cableSubtotal: roundMoney(totals.cableSubtotal),
                modulationCableSubtotal: roundMoney(
                    totals.modulationCableSubtotal,
                ),
                surplusOptimizationSubtotal: roundMoney(
                    totals.surplusOptimizationSubtotal,
                ),
                civilWorkSubtotal: roundMoney(totals.civilWorkSubtotal),
                optionalSubtotal: roundMoney(totals.optionalSubtotal),
                subtotal: roundMoney(totals.subtotal),
                vat: roundMoney(totals.vat),
                total: roundMoney(totals.total),
                vatPercentage: this.safeNumber(budget.chargerVatPercentage, 21),
            };

            budget.financing = budget.financingEnabled
                ? this.getEvChargerFinancingSummary(totals.total)
                : null;

            budget.budgetLines = budgetLines;
            budget.budgetSavedAt = new Date().toISOString();
            budget.budgetType = "electric_car_charger";
        },
        changeOtherProduct() {
            delete this.errors.product;
            this.resetEvChargerBudgetIfNotCharger();

            if (this.isElectricCarCharger) {
                this.ensureEvChargerBudgetStructure();
            }

            this.calcCommission();
        },
        toggleSelectUserInOrders(id) {
            if (!Array.isArray(this.opportunity.usersIds)) {
                this.opportunity.usersIds = [];
            }

            const index = this.opportunity.usersIds.indexOf(id);

            if (index !== -1) {
                this.opportunity.usersIds.splice(index, 1);
            } else {
                if (
                    this.basicData.userLogged._id !== "683d658761231bd1080b4802"
                ) {
                    this.opportunity.usersIds =
                        this.opportunity.usersIds.filter(
                            (u) => u === "65cb57489c2c285441086a43",
                        );
                }

                this.opportunity.usersIds.push(id);
            }

            if (this.opportunity.order) {
                this.opportunity.order.usersIds = [
                    ...this.opportunity.usersIds,
                ];
            }
        },
        onSelectType(code) {
            this.createUserData.selected = code;
            const goContractOnly = code === "contract";

            if (goContractOnly && !this.isContractOnly) {
                this.clearAccountFields();
                this.clearAccountErrors();
            }

            this.isContractOnly = goContractOnly;
        },
        clearAccountFields() {
            this.opportunity.name = "";
            this.opportunity.CIF = "";
            this.opportunity.phone = "";
            this.opportunity.email = "";
            this.opportunity.website = "";
            this.opportunity.sector = "";
            this.opportunity.source = "";
            this.opportunity.status = "";
            this.opportunity.position = "";
            this.opportunity.observations = "";
            this.opportunity.landLinePhone = "";

            if (this.opportunity.contact) {
                this.opportunity.contact.value = "";
                this.opportunity.contact.isFromContacts = false;
            }

            if (this.opportunity.billingInfo) {
                this.opportunity.billingInfo.community = "";
                this.opportunity.billingInfo.province = "";
                this.opportunity.billingInfo.locality = "";
                this.opportunity.billingInfo.address = "";
                this.opportunity.billingInfo.postal = "";
            }

            this.GEO.provinces = "";
            this.GEO.localities = "";
            this.searchAddressText = "";
        },
        clearAccountErrors() {
            if (!this.errors)
                this.errors = {
                    billingInfo: {},
                    order: { evChargerBudget: {} },
                };
            if (!this.errors.order) this.errors.order = { evChargerBudget: {} };
            if (!this.errors.order.evChargerBudget)
                this.errors.order.evChargerBudget = {};

            this.errors.name = null;
            this.errors.CIF = null;
            this.errors.phone = null;
            this.errors.email = null;
            this.errors.website = null;
            this.errors.contact = null;
            this.errors.position = null;

            if (!this.errors.billingInfo) this.errors.billingInfo = {};
            this.errors.billingInfo.community = null;
            this.errors.billingInfo.province = null;
            this.errors.billingInfo.locality = null;
            this.errors.billingInfo.address = null;
            this.errors.billingInfo.postal = null;
        },
        async createopportunity() {
            this.errors = { billingInfo: {}, order: { evChargerBudget: {} } };

            let hasErrors = false;

            if (!this.isContractOnly) {
                const nameValue =
                    (this.opportunity?.name ?? "").trim() ||
                    (this.opportunity?.order?.name ?? "").trim();
                if (nameValue === "") {
                    this.errors.name = this.getErrorMessage("isEmpty");
                    hasErrors = true;
                }

                const regexNifCif =
                    /^[0-9]{8}[A-Z]$|^[XYZ][0-9]{7}[A-Z]$|^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]/;

                if (
                    this.opportunity.CIF &&
                    !regexNifCif.test(this.opportunity.CIF)
                ) {
                    this.errors.CIF = "CIF/NIF no válido";
                    hasErrors = true;
                }

                if (
                    this.opportunity.phone &&
                    this.opportunity.phone.length !== 9
                ) {
                    this.errors.phone = this.getErrorMessage("malformedPhone");
                    hasErrors = true;
                }

                const regexEmail =
                    /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                if (
                    this.opportunity.email &&
                    !regexEmail.test(this.opportunity.email)
                ) {
                    this.errors.email = this.getErrorMessage("malformedEmail");
                    hasErrors = true;
                }

                if (
                    this.opportunity.billingInfo.postal &&
                    this.opportunity.billingInfo.postal.length !== 5
                ) {
                    this.errors.billingInfo.postal =
                        "El zip tiene que tener 5 digitos";
                    hasErrors = true;
                }
                if (
                    this.opportunity.billingInfo.postal &&
                    isNaN(this.opportunity.billingInfo.postal)
                ) {
                    this.errors.billingInfo.postal =
                        this.getErrorMessage("onlyNumbers");
                    hasErrors = true;
                }
            } else {
                const orderName = (this.opportunity?.order?.name ?? "").trim();
                if (orderName === "") {
                    this.errors.name = this.getErrorMessage("isEmpty");
                    hasErrors = true;
                }
            }

            if (
                this.opportunity.order.zip &&
                this.opportunity.order.zip.length !== 5
            ) {
                this.errors.order.zip = "El zip tiene que tener 5 digitos";
                hasErrors = true;
            }
            if (
                this.opportunity.order.zip &&
                isNaN(this.opportunity.order.zip)
            ) {
                this.errors.order.zip = this.getErrorMessage("onlyNumbers");
                hasErrors = true;
            }

            if (
                this.opportunity.order.CUPS &&
                this.opportunity.order.CUPS.length !== 20
            ) {
                this.errors.CUPS = "CUPS no válido";
                hasErrors = true;
            }

            if (
                this.opportunity.order.CUPSSecondary &&
                this.opportunity.order.CUPSSecondary.length !== 20
            ) {
                this.errors.CUPSSecondary = "CUPS no válido";
                hasErrors = true;
            }

            if (this.isElectricCarCharger) {
                this.ensureEvChargerBudgetStructure();

                if (!this.opportunity.order.evChargerBudget.chargerModel) {
                    this.errors.order.evChargerBudget.chargerModel =
                        this.getErrorMessage("isEmpty");
                    hasErrors = true;
                }

                if (!this.opportunity.order.evChargerBudget.chargerPower) {
                    this.errors.order.evChargerBudget.chargerPower =
                        this.getErrorMessage("isEmpty");
                    hasErrors = true;
                }

                if (
                    this.safeNumber(
                        this.opportunity.order.evChargerBudget.cableMeters,
                    ) < 0
                ) {
                    this.errors.order.evChargerBudget.cableMeters =
                        "Los metros no pueden ser negativos";
                    hasErrors = true;
                }

                if (
                    this.safeNumber(
                        this.opportunity.order.evChargerBudget
                            .modulationCableMeters,
                    ) < 0
                ) {
                    this.errors.order.evChargerBudget.modulationCableMeters =
                        "Los metros no pueden ser negativos";
                    hasErrors = true;
                }
            }

            if (
                !this.opportunity.order.marketer ||
                this.opportunity.order.marketer === ""
            ) {
                this.opportunity.order.marketer = "Sin Comercializadora";
            }
            if (
                !this.opportunity.order.fee ||
                this.opportunity.order.fee === ""
            ) {
                this.opportunity.order.fee = "Sin Tarifa";
            }
            if (
                !this.opportunity.order.productType ||
                this.opportunity.order.productType === ""
            ) {
                this.opportunity.order.productType = "sp";
            }

            if (this.opportunity.customFields.length > 0) {
                this.opportunity.customFields.forEach((customField) => {
                    if (customField.title === "") {
                        customField.errors = this.getErrorMessage("isEmpty");
                        hasErrors = true;
                    }
                });
            }

            if (!Array.isArray(this.opportunity.usersIds)) {
                this.opportunity.usersIds = [];
            }

            if (this.opportunity.order) {
                this.opportunity.order.usersIds = [
                    ...this.opportunity.usersIds,
                ];
            }

            if (!hasErrors) {
                this.prepareEvChargerBudgetForSave();
                await this.prepareOpportunityCommissionsForSave();

                const data = new FormData();
                data.append("oportunity", JSON.stringify(this.opportunity));
                data.append(
                    "userLogged",
                    JSON.stringify(this.basicData.userLogged),
                );

                this.opportunity.customFields.forEach((field, fieldInd) => {
                    if (field.type === "image")
                        data.append("customFieldFile" + fieldInd, field.value);
                });

                if (Array.isArray(this.opportunity.docs)) {
                    this.opportunity.docs.forEach((doc, idx) => {
                        if (doc.fileValue instanceof File) {
                            data.append(`docFiles[${idx}]`, doc.fileValue);
                        }
                    });
                }

                axios
                    .post("/api/opportunities", data)
                    .then(() => {
                        Swal.fire({
                            icon: "success",
                            title: "Oportunidad creada!",
                            text: " La oportunidad ha sido creada correctamente",
                            timer: 1500,
                            timerProgressBar: true,
                        });
                        this.$router.push("/opportunities");
                    })
                    .catch((err) => {
                        console.log(err);

                        if (err?.response?.data?.cifError) {
                            this.errors.CIF = err.response.data.cifError;
                        }

                        Swal.fire({
                            icon: "error",
                            title: "No se ha podido guardar",
                            text:
                                err?.response?.data?.message ||
                                err?.response?.data?.error ||
                                "Error desconocido al guardar la oportunidad",
                        });
                    });
            }
        },
        getErrorMessage(type) {
            let message = "";

            switch (type) {
                case "isEmpty":
                    message = "Este campo no puede estar vacio";
                    break;
                case "malformedEmail":
                    message = "El email esta mal formado";
                    break;
                case "malformedPhone":
                    message = "El número de telefono esta mal formado";
                    break;
                case "onlyNumbers":
                    message = "Este campo solo admite números";
                    break;
                default:
                    message = "Hay errores en el formulario";
                    break;
            }

            return message;
        },
        async fetchAccounts() {
            if (!this.basicData?.userLogged?._id) return;

            await axios
                .post(
                    `/api/contacts/getAccountsRelated/${this.basicData.userLogged._id}`,
                    { userList: this.basicData.userList },
                )
                .then((res) => {
                    this.accounts = res.data.accounts;
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        async fetchContacts() {
            if (!this.basicData?.userLogged?._id) return;

            await axios
                .post(
                    `/api/opportunities/getRelatedContacts/${this.basicData.userLogged._id}`,
                    { userList: this.basicData.userList },
                )
                .then((res) => {
                    this.contacts = res.data.contacts;
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        async fetchMarketers() {
            await axios
                .get("/api/marketers")
                .then((res) => {
                    this.marketers = res.data.marketers;
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        addCustomField() {
            const customField = {
                title: "",
                type: "text",
                fileType: "",
                value: "",
            };

            this.opportunity.customFields.push(customField);
        },
        delCustomField(fieldInd) {
            this.opportunity.customFields.splice(fieldInd, 1);
        },
        addCustomFields(files) {
            files.forEach((file) => {
                const customField = {
                    title: "",
                    type: "image",
                    fileType: file["type"].split("/")[0],
                    value: file,
                };

                this.opportunity.customFields.push(customField);
            });
        },
        fetchSelectValues() {
            axios
                .get(`/api/select/`)
                .then((res) => {
                    this.selectValues = res.data.selectValues;

                    if (!this.selectValues) {
                        this.selectValues = {
                            sector: [],
                            source: [],
                            status: [],
                        };
                    }
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        addSelectType(data) {
            const type = data.type;
            const value = data.value;

            axios
                .post(`/api/select`, { type, value })
                .then(() => {
                    this.selectValues[type].push(value);

                    if (type === "sector") {
                        this.opportunity.sector = value;
                    } else if (type === "source") {
                        this.opportunity.source = value;
                    } else {
                        this.opportunity.status = value;
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: "error",
                        title: "Ya existe",
                        text: "El elemento que estas intentando crear ya existe",
                    });
                });
        },
        delSelectType(data) {
            const type = data.type;
            const value = data.value;

            if (value) {
                Swal.fire({
                    icon: "warning",
                    title: "¿Estás seguro?",
                    text: "Seguro que quieres borrarlo? Tu acción no podrá revertirse",
                    confirmButtonText: "Sí",
                    showCancelButton: true,
                    cancelButtonText: "No",
                }).then((res) => {
                    if (res.isConfirmed) {
                        axios
                            .delete(`/api/select`, {
                                params: {
                                    type,
                                    value,
                                },
                            })
                            .then(() => {
                                const index =
                                    this.selectValues[type].indexOf(value);

                                if (index !== -1)
                                    this.selectValues[type].splice(index, 1);

                                if (type === "sector") {
                                    if (this.opportunity.sector === value)
                                        this.opportunity.sector = "";
                                } else if (type === "source") {
                                    if (this.opportunity.source === value)
                                        this.opportunity.source = "";
                                } else {
                                    if (this.opportunity.status === value)
                                        this.opportunity.status = "";
                                }
                            })
                            .catch((err) => {
                                console.log(err);
                            });
                    }
                });
            }
        },
        selectElement(data) {
            const type = data.type;
            const value = data.value;

            this.opportunity[type] = value;
        },
        selectAccount(account) {
            this.opportunity.account = account._id;
            this.searchAccountText = "";
            this.errors.account = "";
        },
        changeProductType() {
            delete this.errors.productType;

            this.opportunity.order.product = "";
            this.opportunity.order.marketer = "";
            this.opportunity.order.fee = "";
            this.opportunity.order.extras = [];

            this.clearCommissions();

            if (
                this.opportunity.order.productType !== "cl" &&
                this.opportunity.order.productType !== "cg" &&
                this.opportunity.order.productType !== "cd"
            ) {
                this.opportunity.order.CUPS = "";
            }

            if (this.opportunity.order.productType === "cd") {
                this.opportunity.order.feeSecondary = "";
                this.opportunity.order.productSecondary = "";
                this.opportunity.order.CUPSSecondary = "";
                this.opportunity.order.consumptionSecondary = "";
            }

            this.resetEvChargerBudgetIfNotCharger();
        },
        changeMarketer() {
            delete this.errors.marketer;

            this.opportunity.order.fee = "";
            this.opportunity.order.product = "";
            this.opportunity.order.extras = [];
            this.clearCommissions();
            this.resetEvChargerBudgetIfNotCharger();
        },
        changeFee() {
            delete this.errors.fee;

            this.opportunity.order.product = "";
            this.opportunity.order.extras = [];
            this.clearCommissions();
            this.resetEvChargerBudgetIfNotCharger();
        },
        changeFeeSecondary() {
            delete this.errors.feeSecondary;

            this.opportunity.order.productSecondary = "";
            this.opportunity.order.extras = [];
            this.clearCommissions();
        },
        checkCUPS() {
            if (this.opportunity.order.CUPS.length === 22) {
                this.opportunity.order.CUPS = this.opportunity.order.CUPS.slice(
                    0,
                    -2,
                );
            }
        },
        actionLink(route) {
            this.$router.push(route);
        },
        setOppFromOpportunity() {
            const data = this.$cookies.get("temporalCreateOppCookie");

            this.opportunity = {
                ...this.opportunity,
                ...data,
                order: {
                    ...this.opportunity.order,
                    ...(data.order || {}),
                    evChargerBudget: {
                        ...this.opportunity.order.evChargerBudget,
                        ...(data.order?.evChargerBudget || {}),
                    },
                },
            };

            this.ensureEvChargerBudgetStructure();
            this.$cookies.remove("temporalCreateOppCookie");
        },
        getProductData() {
            const marketerInfo = this.marketers.find(
                (m) => m.name === this.opportunity.order.marketer,
            );
            if (!marketerInfo) return null;

            const productsRelations = {
                cl: "electricity",
                cg: "gas",
                cd: "dual",
                ct: "telephony",
                sa: "alarm",
                a: "selfSupply",
            };

            const productTypeKey =
                productsRelations[this.opportunity.order.productType];
            if (!productTypeKey || !marketerInfo.products?.[productTypeKey])
                return null;

            let productInfo = null;

            if (productTypeKey === "dual") {
                productInfo = marketerInfo.products[productTypeKey].find(
                    (p) =>
                        p.electricity === this.opportunity.order.product &&
                        p.gas === this.opportunity.order.productSecondary,
                );
            } else {
                productInfo = marketerInfo.products[productTypeKey].find(
                    (p) => p.name === this.opportunity.order.product,
                );
            }

            if (!productInfo) {
                console.log("NOT PRODUCT");
                return null;
            }

            let feeOid = null;
            let feeInfo = null;

            if (
                this.opportunity.order.productType === "cl" ||
                this.opportunity.order.productType === "cg"
            ) {
                const feeSelected = marketerInfo.fees[productTypeKey].find(
                    (f) => f.name === this.opportunity.order.fee,
                );
                feeOid = feeSelected?.id?.$oid;
                feeInfo = productInfo.fees.find((f) => f.id.$oid === feeOid);
            } else {
                if (this.opportunity.order.productType === "cd") {
                    feeInfo = productInfo.fees.find(
                        (f) =>
                            f.electricity.name === this.opportunity.order.fee &&
                            f.gas.name === this.opportunity.order.feeSecondary,
                    );
                } else if (this.opportunity.order.productType === "sa") {
                    feeInfo = productInfo;
                } else {
                    feeInfo = productInfo.fees.find(
                        (f) => f.name === this.opportunity.order.fee,
                    );
                }
            }

            if (!feeInfo) {
                console.log("NOT FEE");
                return null;
            }

            return { marketerInfo, productInfo, feeInfo };
        },
        toggleSelectExtraProduct(extra) {
            if (!this.opportunity.order.extras)
                this.opportunity.order.extras = [];

            const extraId = extra.id?.$oid || extra.id;
            const index = this.opportunity.order.extras.indexOf(extraId);

            if (index === -1) {
                this.opportunity.order.extras.push(extraId);
            } else {
                this.opportunity.order.extras.splice(index, 1);
            }
        },
        addEvChargerOptional() {
            if (this.isReadOnly) return;

            this.ensureEvChargerBudgetStructure();

            this.opportunity.order.evChargerBudget.optionalItems.push({
                name: "",
                description: "",
                quantity: 1,
                price: 0,
                discount: 0,
                automatic: false,
            });
        },
        delEvChargerOptional(optionalInd) {
            if (this.isReadOnly) return;
            this.opportunity.order.evChargerBudget.optionalItems.splice(
                optionalInd,
                1,
            );
        },
        resetEvChargerBudgetIfNotCharger() {
            if (this.isElectricCarCharger) return;

            this.ensureEvChargerBudgetStructure();

            const defaults = this.getEvChargerBudgetDefaults();

            this.opportunity.order.evChargerBudget = {
                ...this.opportunity.order.evChargerBudget,
                ...defaults,
                optionalItems: [],
            };
        },
        async generateEvChargerBudget() {
            this.errors.order.evChargerBudget = {};

            let hasErrors = false;

            if (!this.opportunity.order.evChargerBudget.chargerModel) {
                this.errors.order.evChargerBudget.chargerModel =
                    this.getErrorMessage("isEmpty");
                hasErrors = true;
            }

            if (!this.opportunity.order.evChargerBudget.chargerPower) {
                this.errors.order.evChargerBudget.chargerPower =
                    this.getErrorMessage("isEmpty");
                hasErrors = true;
            }

            if (
                this.opportunity.order.evChargerBudget.cableMeters !== "" &&
                Number(this.opportunity.order.evChargerBudget.cableMeters) < 0
            ) {
                this.errors.order.evChargerBudget.cableMeters =
                    "Los metros no pueden ser negativos";
                hasErrors = true;
            }

            if (
                this.opportunity.order.evChargerBudget.modulationCableMeters !==
                    "" &&
                Number(
                    this.opportunity.order.evChargerBudget
                        .modulationCableMeters,
                ) < 0
            ) {
                this.errors.order.evChargerBudget.modulationCableMeters =
                    "Los metros no pueden ser negativos";
                hasErrors = true;
            }

            if (hasErrors) return;

            const { value: paymentOption } = await Swal.fire({
                title: "¿Qué importe debe pagar el cliente?",
                input: "radio",
                inputOptions: {
                    40: "Pago 40%",
                    60: "Pago 60%",
                    100: "Importe total (100%)",
                },
                inputValue: "60",
                showCancelButton: true,
                confirmButtonText: "Generar presupuesto",
                cancelButtonText: "Cancelar",
                inputValidator: (value) => {
                    if (!value) return "Debes elegir una opción";
                },
            });

            if (!paymentOption) return;
            const depositPercentage = Number(paymentOption) / 100;

            this.prepareEvChargerBudgetForSave();

            const payload = {
                opportunity: this.opportunity,
                evChargerBudgetTotals: this.evChargerBudgetTotals,
                basicData: this.basicData,
                userLogged: this.basicData.userLogged,
                depositPercentage,
            };

            const formData = new FormData();
            formData.append("payload", JSON.stringify(payload));

            this.isGeneratingEvChargerBudget = true;

            try {
                const res = await axios.post(
                    "/api/opportunities/generateEvChargerPDF",
                    formData,
                    {
                        responseType: "blob",
                        headers: { "Content-Type": "multipart/form-data" },
                    },
                );

                const blob = new Blob([res.data], { type: "application/pdf" });
                const url = window.URL.createObjectURL(blob);

                const link = document.createElement("a");
                link.href = url;
                const safeClientName = (this.opportunity.name || "cliente")
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "")
                    .replace(/[^A-Za-z0-9\s]/g, "")
                    .trim()
                    .replace(/\s+/g, "_");

                const hoy = new Date();
                const dia = String(hoy.getDate()).padStart(2, "0");
                const mes = String(hoy.getMonth() + 1).padStart(2, "0");
                const anio = hoy.getFullYear();

                link.download = `PresupuestoCargador_${safeClientName}_${dia}-${mes}-${anio}.pdf`;
                link.click();
                window.URL.revokeObjectURL(url);

                const result = await Swal.fire({
                    icon: "question",
                    title: "¿Guardar en documentos?",
                    text: "¿Quieres guardar este presupuesto en los documentos de la oportunidad?",
                    confirmButtonText: "Sí, guardar",
                    showCancelButton: true,
                    cancelButtonText: "No",
                });

                if (result.isConfirmed) {
                    const fileName = "presupuesto-cargador-electrico.pdf";
                    const file = new File([blob], fileName, {
                        type: "application/pdf",
                    });

                    if (!Array.isArray(this.opportunity.docs)) {
                        this.opportunity.docs = [];
                    }

                    this.opportunity.docs.push({
                        title: "Presupuesto cargador eléctrico",
                        defaultTitle: fileName,
                        value: "",
                        fileValue: file,
                        icon: "fa-file-pdf",
                        id: "tmp-" + crypto.randomUUID(),
                        errors: {},
                    });

                    Swal.fire({
                        icon: "success",
                        title: "¡Listo!",
                        text: "El presupuesto se guardará en los documentos al registrar la oportunidad",
                        timer: 2000,
                        timerProgressBar: true,
                    });
                }
            } catch (err) {
                console.log(err);
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No se ha podido generar el presupuesto del cargador eléctrico",
                });
            } finally {
                this.isGeneratingEvChargerBudget = false;
            }
        },
    },
    computed: {
        selectedEvChargerFinancingPlan() {
            const budget = this.opportunity?.order?.evChargerBudget || {};
            const months = Number(budget.financingMonths || 36);

            return (
                this.evChargerFinancingPlans.find(
                    (plan) => plan.months === months,
                ) || this.evChargerFinancingPlans.find((plan) => plan.months === 36)
            );
        },
        evChargerFinancingSummary() {
            const budget = this.opportunity?.order?.evChargerBudget || {};

            if (!budget.financingEnabled) return null;

            return this.getEvChargerFinancingSummary(
                this.evChargerBudgetTotals.total,
            );
        },
        opportunityAgentCommission() {
            const breakdown =
                this.opportunity?.order?.commissions?.breakdown || [];

            const usersIds = Array.isArray(this.opportunity?.usersIds)
                ? this.opportunity.usersIds.map(String)
                : [];

            return (
                breakdown.find((item) =>
                    usersIds.includes(String(item.userId)),
                ) ||
                breakdown[0] ||
                null
            );
        },
        opportunityFilteredCommissionHierarchy() {
            const breakdown =
                this.opportunity?.order?.commissions?.breakdown || [];

            return breakdown.filter(
                (item) =>
                    item &&
                    item.userId &&
                    item.commission !== null &&
                    item.commission !== undefined,
            );
        },
        accountSelected() {
            return this.accounts.find(
                (account) => account._id === this.opportunity.account,
            );
        },
        filteredAccounts() {
            let accounts = [];

            if (this.searchAccountText === "") {
                accounts = this.accounts;
            } else {
                this.accounts.filter((account) => {
                    const accountFiltered = account.name
                        .replace(" ", "")
                        .toLowerCase()
                        .normalize("NFC");

                    if (
                        accountFiltered.includes(
                            this.searchAccountText
                                .replace(" ", "")
                                .toLowerCase()
                                .normalize("NFC"),
                        )
                    ) {
                        accounts.push(account);
                    }
                });
            }

            accounts = accounts.sort((a, b) => a.name.localeCompare(b.name));

            return accounts;
        },
        selectedProductTypeData() {
            if (!this.opportunity.order.productType) return null;

            return (
                this.productTypesWithoutSp.find(
                    (pt) => pt.code === this.opportunity.order.productType,
                ) || null
            );
        },
        selectedProductToSee() {
            return this.selectedProductTypeData?.productToSee || "";
        },
        isOtherProductType() {
            return (
                !!this.selectedProductToSee &&
                !!this.marketerProductsOthers[this.selectedProductToSee]
            );
        },
        otherProductsByType() {
            if (!this.isOtherProductType) return [];
            return this.marketerProductsOthers[this.selectedProductToSee] || [];
        },
        showOtherProductSelect() {
            return (
                !!this.opportunity.order.productType && this.isOtherProductType
            );
        },
        showContractMarketerSelect() {
            return (
                this.opportunity.order.productType !== "cd" &&
                !!this.opportunity.order.productType &&
                !this.isOtherProductType
            );
        },
        showMainFeeSelect() {
            return (
                this.opportunity.order.productType !== "cd" &&
                this.opportunity.order.productType !== "sa" &&
                !!this.opportunity.order.productType &&
                !this.isOtherProductType &&
                this.opportunity.order.marketer !== ""
            );
        },
        showCupsField() {
            return (
                !this.isOtherProductType &&
                ((this.opportunity.order.productType === "cd" &&
                    this.opportunity.order.marketer !== "") ||
                    this.opportunity.order.productType === "cl" ||
                    this.opportunity.order.productType === "cg")
            );
        },
        isElectricCarCharger() {
            const allowedUserId = "65cb57489c2c285441086a43";

            return (
                this.basicData?.userSubdomain?._id === allowedUserId &&
                this.selectedProductToSee === "electricCar" &&
                ["Cargador Electrico 100%", "Cargador Hibrido"].includes(
                    this.opportunity?.order?.product,
                )
            );
        },
        evChargerSelectedModel() {
            return (
                this.evChargerModels.find(
                    (charger) =>
                        charger.chargerModel ===
                        this.opportunity.order.evChargerBudget.chargerModel,
                ) || null
            );
        },
        evChargerBudgetTotals() {
            const budget = this.opportunity?.order?.evChargerBudget || {};
            const vatRate =
                this.safeNumber(budget.chargerVatPercentage, 21) / 100;

            const chargerPrice = this.safeNumber(
                budget.chargerInstallationPrice,
            );
            const chargerDiscount = this.safeNumber(
                budget.chargerInstallationDiscount,
            );
            const chargerSubtotal = chargerPrice * (1 - chargerDiscount / 100);

            const laborSubtotal = this.safeNumber(budget.laborPrice);
            const certificateSubtotal = this.safeNumber(
                budget.certificatePrice,
            );

            const cableSubtotal =
                this.getEvBillableCableMeters(budget.cableMeters) *
                this.safeNumber(
                    budget.cablePricePerMeter,
                    this.evChargerDefaultCosts.cablePricePerMeter,
                );

            const modulationCableSubtotal =
                Math.min(this.safeNumber(budget.modulationCableMeters), 70) *
                this.safeNumber(
                    budget.modulationCablePricePerMeter,
                    this.evChargerDefaultCosts.modulationCablePricePerMeter,
                );

            const surplusOptimizationSubtotal =
                budget.hasPhotovoltaic && budget.wantsSurplusOptimization
                    ? this.safeNumber(budget.surplusOptimizationPrice)
                    : 0;

            const civilWorkSubtotal = budget.needsCivilWork
                ? this.safeNumber(budget.civilWorkPrice)
                : 0;

            const optionalSubtotal = Array.isArray(budget.optionalItems)
                ? budget.optionalItems.reduce(
                      (total, optional) =>
                          total + this.getOptionalItemTotal(optional),
                      0,
                  )
                : 0;

            const subtotal =
                chargerSubtotal +
                laborSubtotal +
                certificateSubtotal +
                cableSubtotal +
                modulationCableSubtotal +
                surplusOptimizationSubtotal +
                civilWorkSubtotal +
                optionalSubtotal;

            const globalDiscount = this.safeNumber(budget.globalDiscount);
            const discountAmount = Math.round(subtotal * (globalDiscount / 100) * 100) / 100;
            const netSubtotal = subtotal - discountAmount;

            const vat = netSubtotal * vatRate;
            const total = netSubtotal + vat;

            return {
                chargerSubtotal,
                laborSubtotal,
                certificateSubtotal,
                cableSubtotal,
                modulationCableSubtotal,
                surplusOptimizationSubtotal,
                civilWorkSubtotal,
                optionalSubtotal,
                subtotal,
                globalDiscount,
                discountAmount,
                netSubtotal,
                vat,
                total,
            };
        },
        filteredMarketers() {
            if (!this.marketers.length || !this.opportunity.order.productType)
                return [];

            let marketers = this.marketers
                .filter((marketer) => {
                    return (
                        (this.opportunity.order.productType === "cl" &&
                            marketer.products.electricity.length > 0) ||
                        (this.opportunity.order.productType === "cg" &&
                            marketer.products.gas.length > 0) ||
                        (this.opportunity.order.productType === "cd" &&
                            (marketer.products.dual?.length ?? 0) > 0) ||
                        (this.opportunity.order.productType === "ct" &&
                            (marketer.products.telephony?.length ?? 0) > 0) ||
                        (this.opportunity.order.productType === "sa" &&
                            (marketer.products.alarm?.length ?? 0) > 0) ||
                        (this.opportunity.order.productType === "a" &&
                            (marketer.products.selfSupply?.length ?? 0) > 0)
                    );
                })
                .filter((marketer) => {
                    const isSubdomainUser =
                        this.basicData.userLogged.label ===
                        "Usuario subdominio";

                    if (!isSubdomainUser) {
                        return (
                            this.basicData.userLogged.marketers || []
                        ).includes(marketer._id);
                    }

                    return true;
                })
                .filter(marketer => {
                    return !marketer.archived
                })
            ;

            marketers = marketers.sort((a, b) => {
                if (a.name < b.name) return -1;
                if (a.name > b.name) return 1;
                return 0;
            });

            return marketers;
        },
        filteredFees() {
            if (
                !this.marketers.length ||
                !this.opportunity.order.productType ||
                !this.opportunity.order.marketer
            )
                return [];

            const marketer = this.marketers.find(
                (marketer) => marketer.name === this.opportunity.order.marketer,
            );
            if (!marketer) return [];

            const productTypeData = this.$storage.PRODUCT_TYPES.find(
                (pt) => pt.code === this.opportunity.order.productType,
            );
            if (!productTypeData) return [];

            const productType = productTypeData.inDatabase;

            if (this.opportunity.order.productType === "cd") {
                return [
                    ...new Map(
                        marketer.products.dual
                            .flatMap((dual) => dual.fees)
                            .map((fee) => [
                                fee.electricity.name,
                                { name: fee.electricity.name },
                            ]),
                    ).values(),
                ];
            }

            if (productType === "electricity" || productType === "gas") {
                return marketer.fees[productType];
            }

            return this.$storage.FEES[productType]
                .filter((fee) => {
                    return marketer.products[productType].some((product) => {
                        return product.fees.some((feeProduct) => {
                            const name1 = feeProduct.name?.trim().toLowerCase();
                            const name2 = fee?.trim().toLowerCase();

                            return name1 === name2 && !feeProduct.archived;
                        });
                    });
                })
                .map((fee) => ({ name: fee }));
        },
        filteredMarketerProducts() {
            if (
                !this.marketers.length ||
                !this.opportunity.order.productType ||
                !this.opportunity.order.marketer ||
                (!this.opportunity.order.fee &&
                    this.opportunity.order.productType !== "sa")
            ) {
                return [];
            }

            const marketer = this.marketers.find(
                (marketer) => marketer.name === this.opportunity.order.marketer,
            );
            if (!marketer) return [];

            const productTypeData = this.$storage.PRODUCT_TYPES.find(
                (pt) => pt.code === this.opportunity.order.productType,
            );
            if (!productTypeData) return [];

            const productType = productTypeData.inDatabase;

            if (this.opportunity.order.productType === "cd") {
                return [
                    ...new Set(
                        marketer.products.dual
                            .filter((dual) =>
                                dual.fees.some(
                                    (fee) =>
                                        fee.electricity?.name ===
                                        this.opportunity.order.fee,
                                ),
                            )
                            .map((dual) => dual.electricity),
                    ),
                ].map((name) => ({ name }));
            }

            if (productType === "electricity" || productType === "gas") {
                const feesListKey =
                    this.opportunity.order.productType === "cl" ||
                    this.opportunity.order.productType === "cd"
                        ? "electricity"
                        : "gas";
                const selectedFee = marketer.fees[feesListKey].find(
                    (feeNow) => feeNow.name === this.opportunity.order.fee,
                );

                if (!selectedFee?.id?.$oid) return [];

                return marketer.products[productType].filter((product) => {
                    return product.fees.find((fee) => {
                        if (
                            fee?.archived &&
                            product.name === this.opportunity.order.product
                        ) {
                            this.productArchived = true;
                        }

                        if (
                            (!!fee.minPower || !!fee.maxPower) &&
                            !!this.opportunity.order.hiredPotency
                        ) {
                            if (
                                !!fee.minPower &&
                                this.opportunity.order.hiredPotency <
                                    fee.minPower
                            )
                                return false;
                            if (
                                !!fee.maxPower &&
                                this.opportunity.order.hiredPotency >
                                    fee.maxPower
                            )
                                return false;
                        }

                        if (
                            (!!fee.minConsumption || !!fee.maxConsumption) &&
                            !!this.opportunity.order.consumption
                        ) {
                            if (
                                !!fee.minConsumption &&
                                this.opportunity.order.consumption <
                                    fee.minConsumption
                            )
                                return false;
                            if (
                                !!fee.maxConsumption &&
                                this.opportunity.order.consumption >
                                    fee.maxConsumption
                            )
                                return false;
                        }

                        return (
                            fee.id.$oid === selectedFee.id.$oid &&
                            (!fee?.archived ||
                                product.name === this.opportunity.order.product)
                        );
                    });
                });
            }

            if (productType === "alarm") {
                return marketer.products[productType].filter((product) => {
                    if (
                        product.archived &&
                        product.name === this.opportunity.order.product
                    ) {
                        this.productArchived = true;
                    }

                    return (
                        !product.archived ||
                        product.name === this.opportunity.order.product
                    );
                });
            }

            return marketer.products[productType].filter((product) => {
                return product.fees.find((fee) => {
                    if (
                        fee?.archived &&
                        product.name === this.opportunity.order.product
                    ) {
                        this.productArchived = true;
                    }

                    const feeActived = product.fees.find(
                        (feeNow) => feeNow.name === this.opportunity.order.fee,
                    );

                    return (
                        feeActived &&
                        (!fee?.archived ||
                            product.name === this.opportunity.order.product)
                    );
                });
            });
        },
        filteredMarketerProductsSecondary() {
            if (
                !this.marketers.length ||
                !this.opportunity.order.productType ||
                !this.opportunity.order.marketer ||
                !this.opportunity.order.feeSecondary
            ) {
                return [];
            }

            const marketer = this.marketers.find(
                (marketer) => marketer.name === this.opportunity.order.marketer,
            );
            if (!marketer) return [];

            return [
                ...new Set(
                    marketer.products.dual
                        .filter((dual) =>
                            dual.fees.some(
                                (fee) =>
                                    fee.gas?.name ===
                                    this.opportunity.order.feeSecondary,
                            ),
                        )
                        .map((dual) => dual.gas),
                ),
            ].map((name) => ({ name }));
        },
        isReadOnly() {
            if (!this.basicData.userLogged) return true;
            return this.basicData.userLogged.permissions.includes("READONLY");
        },
        isInputsDisabled() {
            return this.isReadOnly;
        },
        productTypesWithoutSp() {
            return this.$storage.PRODUCT_TYPES.filter(
                (type) => type.code !== "sp",
            );
        },
        extraProductsToSelect() {
            if (
                !this.opportunity.order.productType ||
                !this.opportunity.order.marketer ||
                !this.opportunity.order.product ||
                (this.opportunity.order.productType === "cd" &&
                    !this.opportunity.order.productSecondary)
            ) {
                return [];
            }

            const data = this.getProductData();
            if (!data) return [];

            let info = null;

            if (this.opportunity.order.productType === "sa") {
                info = data.productInfo;
            } else {
                info = data.feeInfo;
            }

            if (!info) return [];

            const extras = info.extras;

            if (
                (!extras || extras.length === 0) &&
                (!data.marketerInfo.extras ||
                    data.marketerInfo.extras.length === 0)
            )
                return [];

            const searchText = (this.extraSearchText || "")
                .toLowerCase()
                .trim();

            return data.marketerInfo.extras.filter((extra) => {
                if (
                    !extra.productTypes.includes(
                        this.opportunity.order.productType,
                    )
                )
                    return false;

                let valid = false;

                if (info.type?.pyme && extra.to.pyme) valid = true;
                if (info.type?.residencial && extra.to.residential)
                    valid = true;
                if (
                    extra.to.product &&
                    extras &&
                    extras.includes(extra.id.$oid)
                )
                    valid = true;

                if (!valid) return false;

                if (searchText)
                    return extra.name?.toLowerCase().includes(searchText);

                return true;
            });
        },
        filteredFeesSecondary() {
            if (
                !this.marketers.length ||
                !this.opportunity.order.productType ||
                !this.opportunity.order.marketer ||
                !this.opportunity.order.product
            ) {
                return [];
            }

            const marketer = this.marketers.find(
                (marketer) => marketer.name === this.opportunity.order.marketer,
            );
            if (!marketer) return [];

            return [
                ...new Set(
                    marketer.products.dual
                        .filter(
                            (dual) =>
                                dual.electricity ===
                                this.opportunity.order.product,
                        )
                        .flatMap((dual) => dual.fees)
                        .map((fee) => fee.gas?.name)
                        .filter(Boolean),
                ),
            ].map((name) => ({ name }));
        },
    },
};
</script>

<style scoped>
.disabledSection {
    opacity: 0.6;
    pointer-events: none;
    filter: grayscale(30%);
    transition: opacity 0.3s ease;
}

.ev-budget-note,
.ev-budget-summary,
.ev-optional-card {
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    padding: 15px;
}

.ev-budget-note {
    background: rgba(0, 0, 0, 0.02);
}

.ev-budget-summary {
    background: rgba(0, 0, 0, 0.03);
}

.ev-budget-summary .total {
    font-size: 18px;
    font-weight: 700;
}

.gap-10 {
    gap: 10px;
}

.flex-wrap {
    flex-wrap: wrap;
}

.text-right {
    text-align: right;
}
.ev-budget-financing-summary {
    border: 1px solid rgba(60, 201, 123, 0.35);
    border-radius: 12px;
    padding: 15px;
    background: rgba(60, 201, 123, 0.08);
}

.ev-budget-financing-summary .total {
    font-size: 16px;
    font-weight: 700;
}

</style>
