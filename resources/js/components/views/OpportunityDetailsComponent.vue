<template>
    <div class="content-white">
        <!--Contenedor padre para distribución-->
        <form
            v-on:submit.prevent="updateOpportunity"
            @keydown.enter.prevent="onFormEnter"
            class="form register-pos"
        >
            <div class="creation-bar my-10">
                <div class="slider w-auto">
                    <div
                        v-for="type in createUserData.types"
                        :key="type.code"
                        @click="isEditing && onSelectType(type.code)"
                        :class="{
                            selected: type.code === createUserData.selected,
                            disabledToggle: !isEditing,
                        }"
                    >
                        <div>{{ type.title }}</div>
                    </div>
                </div>
            </div>

            <!--División de inputs-->
            <div class="top-part" style="height: 62vh">
                <!--Detalles de oportunidad-->
                <div
                    class="inputs-part"
                    :class="{
                        disabledSection: isContractOnly,
                        'opp-account-section-hidden': isContractOnly,
                    }"
                >
                    <div
                        class="text desktop-item"
                        data-size="20"
                        data-weight="700"
                    >
                        Detalles de oportunidad
                    </div>

                    <p class="opp-section-title mobile-item">Datos de cuenta</p>

                    <!--título y cuenta-->
                    <div class="half-space">
                        <!--titulo-->
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
                                    :disabled="isInputsDisabled"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.name" class="error">{{
                                errors.name
                            }}</span>
                        </div>

                        <!--CIF/NIF-->
                        <div
                            v-bind:class="{ wrong: errors.CIF }"
                            class="form-group"
                        >
                            <p class="my-auto">
                                <label>CIF/NIF</label>
                            </p>
                            <div class="input-group">
                                <input
                                    v-on:focus="delete errors['CIF']"
                                    data-size="12"
                                    v-model="opportunity.CIF"
                                    :disabled="isInputsDisabled"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.CIF" class="error">{{
                                errors.CIF
                            }}</span>
                        </div>
                    </div>

                    <!--Telefono y correo electrónico-->
                    <div class="half-space">
                        <!--telefono-->
                        <div
                            v-bind:class="{ wrong: errors.phone }"
                            class="form-group"
                        >
                            <label>Teléfono</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="delete errors['phone']"
                                    data-size="12"
                                    v-model="opportunity.phone"
                                    :disabled="isInputsDisabled"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.phone" class="error">{{
                                errors.phone
                            }}</span>
                        </div>

                        <!--email-->
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
                                    :disabled="isInputsDisabled"
                                    type="email"
                                    name="email"
                                />
                            </div>
                            <span v-if="errors.email" class="error">{{
                                errors.email
                            }}</span>
                        </div>
                    </div>

                    <!--sitio web y sector-->
                    <div class="half-space">
                        <!--sitio web-->
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
                                    :disabled="isInputsDisabled"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.website" class="error">{{
                                errors.website
                            }}</span>
                        </div>

                        <!--sector-->
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
                            :isInputsDisabled="isInputsDisabled"
                            :errors="errors"
                        ></custom-select-component>
                    </div>

                    <!--fuente y estado-->
                    <div class="half-space">
                        <!--fuente posible cliente-->
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
                            :isInputsDisabled="isInputsDisabled"
                            :errors="errors"
                        ></custom-select-component>

                        <!--estado posible cliente-->
                        <div class="form-group">
                            <label>Estado</label>
                            <div class="input-group">
                                <select
                                    v-model="opportunity.status"
                                    :disabled="isInputsDisabled"
                                >
                                    <option value="">Selecciona uno</option>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Contactado">
                                        Contactado
                                    </option>
                                    <option value="Contactado Whatssap">
                                        Contactado Whatssap
                                    </option>
                                    <option value="Presupuesto Enviado">
                                        Presupuesto Enviado
                                    </option>
                                    <option value="Pre. Bot">
                                        Pre. Bot
                                    </option>
                                    <option value="En seguimiento">
                                        En seguimiento
                                    </option>
                                    <option value="Aceptado">Aceptado</option>
                                    <option value="Fallido">Fallido</option>
                                    <option value="Falso">Falso</option>
                                    <option value="Repetido">Repetido</option>
                                    <option value="No Contesta">
                                        No contesta
                                    </option>
                                    <option value="Mensaje Enviado">
                                        Mensaje Enviado
                                    </option>
                                    <option value="Checklist terminado">
                                        Checklist terminado
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!--Persona de contacto y cargo en la empresa-->
                    <div class="half-space">
                        <!--Persona de contacto-->
                        <div
                            v-bind:class="{ wrong: errors.contact }"
                            class="form-group"
                            v-if="opportunity.contact"
                        >
                            <p class="opacity-6 d-flex justify-between">
                                Persona de contacto
                                <label
                                    v-if="!isInputsDisabled"
                                    class="pointer my-auto"
                                    @click="
                                        opportunity.contact.isFromContacts =
                                            !opportunity.contact.isFromContacts;
                                        opportunity.contact.value = '';
                                    "
                                    ><i
                                        class="far"
                                        :class="{
                                            'fa-user-plus':
                                                !opportunity.contact
                                                    .isFromContacts,
                                            'fa-user-minus':
                                                opportunity.contact
                                                    .isFromContacts,
                                        }"
                                    ></i
                                ></label>
                            </p>
                            <div class="input-group">
                                <input
                                    v-if="!opportunity.contact.isFromContacts"
                                    v-on:focus="delete errors['contact']"
                                    data-size="12"
                                    v-model="opportunity.contact.value"
                                    :disabled="isInputsDisabled"
                                    type="text"
                                />

                                <select
                                    v-else
                                    v-model="opportunity.contact.value"
                                    :disabled="isInputsDisabled"
                                >
                                    <option value="">Selecciona uno</option>
                                    <option
                                        v-for="contact in contacts"
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

                        <!--Cargo en la empresa-->
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
                                    :disabled="isInputsDisabled"
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
                            <div
                                class="d-flex align-center"
                                data-gap="5"
                                v-if="!isInputsDisabled"
                            >
                                <input
                                    type="date"
                                    v-model="selectedObservationDate"
                                    data-size="11"
                                />
                                <button
                                    type="button"
                                    class="custom-button"
                                    data-size="small"
                                    data-bg="amarillo"
                                    @click.prevent="insertDateInObservations"
                                >
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
                                :disabled="isInputsDisabled"
                            ></textarea>
                        </div>
                    </div>

                    <div class="separator mt-30"></div>

                    <!--DIRECCIÓN DE FACTURACIÓN-->
                    <div v-if="opportunity.billingInfo">
                        <div
                            class="text mt-50"
                            data-size="17"
                            data-weight="600"
                        >
                            Dirección de facturación
                        </div>

                        <!--Comunidad-->
                        <div
                            v-bind:class="{
                                wrong: errors.billingInfo.community,
                            }"
                            class="form-group"
                        >
                            <label>Comunidad</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="
                                        delete errors['billingInfo'][
                                            'community'
                                        ]
                                    "
                                    data-size="12"
                                    v-model="opportunity.billingInfo.community"
                                    :disabled="isInputsDisabled"
                                    type="text"
                                />
                            </div>
                            <span
                                v-if="errors.billingInfo.community"
                                class="error"
                                >{{ errors.billingInfo.community }}</span
                            >
                        </div>

                        <!--Provincia y localidad-->
                        <div class="half-space">
                            <!--Provincia-->
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
                                            delete errors['billingInfo'][
                                                'province'
                                            ]
                                        "
                                        data-size="12"
                                        v-model="
                                            opportunity.billingInfo.province
                                        "
                                        :disabled="isInputsDisabled"
                                        type="text"
                                    />
                                </div>
                                <span
                                    v-if="errors.billingInfo.province"
                                    class="error"
                                    >{{ errors.billingInfo.province }}</span
                                >
                            </div>

                            <!--Localidad-->
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
                                            delete errors['billingInfo'][
                                                'locality'
                                            ]
                                        "
                                        data-size="12"
                                        v-model="
                                            opportunity.billingInfo.locality
                                        "
                                        :disabled="isInputsDisabled"
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

                        <!--Telefono y fax-->
                        <div class="half-space">
                            <!--Dirección-->
                            <div
                                v-bind:class="{
                                    wrong: errors.billingInfo.address,
                                }"
                                class="form-group"
                            >
                                <label>Dirección</label>
                                <div class="input-group">
                                    <input
                                        v-on:focus="
                                            delete errors['billingInfo'][
                                                'address'
                                            ]
                                        "
                                        data-size="12"
                                        v-model="
                                            opportunity.billingInfo.address
                                        "
                                        :disabled="isInputsDisabled"
                                        type="text"
                                    />
                                </div>
                                <span
                                    v-if="errors.billingInfo.address"
                                    class="error"
                                    >{{ errors.billingInfo.address }}</span
                                >
                            </div>

                            <!--Cod. postal-->
                            <div
                                v-bind:class="{
                                    wrong: errors.billingInfo.postal,
                                }"
                                class="form-group"
                            >
                                <label>Código postal</label>
                                <div class="input-group">
                                    <input
                                        v-on:focus="
                                            delete errors['billingInfo'][
                                                'postal'
                                            ]
                                        "
                                        data-size="12"
                                        v-model="opportunity.billingInfo.postal"
                                        :disabled="isInputsDisabled"
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
                    </div>

                    <div class="separator"></div>

                    <!--CAMPOS PERSONALIZADOS-->
                    <!--Header-->
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
                            v-if="!isInputsDisabled"
                            v-on:click="addCustomField"
                        >
                            <i class="fas fa-plus"></i>
                        </div>
                    </div>

                    <custom-fields-component
                        class="my-20"
                        v-for="(field, fieldInd) in opportunity.customFields"
                        :field="field"
                        :fieldInd="fieldInd"
                        :isReadOnly="isInputsDisabled"
                        @delCustomField="delCustomField"
                        @addCustomFields="addCustomFields"
                        type="opp"
                    ></custom-fields-component>

                    <div class="separator mt-30"></div>

                    <!--DATOS RELACIONADOS-->
                    <div class="desktop-item">
                        <div
                            class="text mt-50"
                            data-size="17"
                            data-weight="600"
                            v-if="
                                opportunityAccounts &&
                                opportunityAccounts.length > 0
                            "
                        >
                            Datos relacionados
                        </div>

                        <!--Cuenta relacionada-->
                        <div
                            class="text mt-20"
                            data-size="15"
                            data-weight="500"
                            v-if="
                                opportunityAccounts &&
                                opportunityAccounts.length > 0
                            "
                        >
                            <i class="fas fa-buildings mr-5"></i> Cuentas
                        </div>

                        <div
                            class="my-10"
                            v-if="
                                opportunityAccounts &&
                                opportunityAccounts.length > 0
                            "
                        >
                            <div
                                class="d-flex justify-center w-100 my-10"
                                v-for="account in opportunityAccounts"
                            >
                                <div
                                    class="initials twoBlues small mr-20"
                                    data-size="18"
                                    data-weight="700"
                                >
                                    {{ getInitials(account.name) }}
                                </div>

                                <div class="d-flex column my-auto ellipsis">
                                    <p
                                        class="ellipsis"
                                        data-color="azul"
                                        data-weight="700"
                                        data-size="15"
                                    >
                                        {{ account.name }}
                                    </p>
                                </div>

                                <div
                                    class="custom-button ml-auto my-auto"
                                    data-style="twoBlue"
                                    data-size="small"
                                    v-on:click="
                                        actionLink(`/accounts/${account._id}`)
                                    "
                                >
                                    <i class="fas fa-arrow-right-long"></i>
                                </div>
                            </div>
                        </div>

                        <!--Contacto-->
                        <div
                            class="text mt-20"
                            data-size="15"
                            data-weight="500"
                            v-if="
                                opportunity.contact &&
                                contacts.length > 0 &&
                                contactRelated
                            "
                        >
                            <i class="fas fa-address-book mr-5"></i> Contacto
                        </div>

                        <div
                            class="d-flex my-10"
                            v-if="
                                opportunity.contact &&
                                contacts.length > 0 &&
                                contactRelated
                            "
                        >
                            <div class="d-flex justify-center w-100">
                                <div
                                    class="initials twoBlues small mr-20"
                                    data-size="18"
                                    data-weight="700"
                                >
                                    {{
                                        getInitials(
                                            contactRelated.name.first +
                                                " " +
                                                (contactRelated.name.second
                                                    ? contactRelated.name.second
                                                    : contactRelated.surname
                                                          .first),
                                        )
                                    }}
                                </div>

                                <div class="d-flex column my-auto ellipsis">
                                    <p
                                        class="ellipsis"
                                        data-color="azul"
                                        data-weight="700"
                                        data-size="15"
                                    >
                                        {{
                                            contactRelated.name.first +
                                            " " +
                                            contactRelated.name.second +
                                            " " +
                                            contactRelated.surname.first +
                                            " " +
                                            contactRelated.surname.second
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="custom-button ml-auto my-auto"
                                    data-style="twoBlue"
                                    data-size="small"
                                    v-on:click="
                                        actionLink(
                                            `/contacts/${contactRelated._id}`,
                                        )
                                    "
                                >
                                    <i class="fas fa-arrow-right-long"></i>
                                </div>
                            </div>
                        </div>

                        <!--Correos relacionados-->
                        <div
                            class="text mt-20"
                            data-size="15"
                            data-weight="500"
                            v-if="
                                opportunity.mails &&
                                opportunity.mails.length > 0
                            "
                        >
                            <i class="far fa-envelopes-bulk mr-5"></i> Correos
                        </div>

                        <div
                            class="d-flex my-10"
                            v-for="mail in opportunity.mails"
                        >
                            <div class="d-flex justify-center w-100">
                                <div
                                    class="initials twoBlues small mr-20"
                                    data-size="18"
                                    data-weight="700"
                                    v-if="mail.subject"
                                >
                                    {{ getInitials(mail.subject) }}
                                </div>

                                <div class="d-flex column my-auto ellipsis">
                                    <p
                                        class="ellipsis"
                                        data-color="azul"
                                        data-weight="700"
                                        data-size="15"
                                        v-if="mail.subject"
                                    >
                                        {{ mail.subject }}
                                    </p>
                                </div>

                                <div
                                    class="custom-button ml-auto my-auto"
                                    data-style="twoBlue"
                                    data-size="small"
                                    v-on:click="
                                        actionLink(
                                            `/tools?section=massiveEmail&email=${mail._id}`,
                                        )
                                    "
                                >
                                    <i class="fas fa-arrow-right-long"></i>
                                </div>
                            </div>
                        </div>

                        <!--Usuario creador-->

                        <user-list-component
                            class="mt-10"
                            :basicData="basicData"
                            v-model:userListSelected="opportunity.usersIds"
                            :account="true"
                            :editing="isEditing"
                        />
                    </div>
                </div>

                <!--Separator vertical-->
                <div class="separator" data-position="vertical"></div>

                <!--Dirección de facturación-->
                <div
                    class="inputs-part"
                    v-if="opportunity && opportunity.order"
                >
                    <div
                        class="text desktop-item"
                        data-size="20"
                        data-weight="700"
                    >
                        Detalles de posible contrato
                    </div>

                    <p class="opp-section-title mobile-item">
                        Datos de contrato
                    </p>

                    <div
                        v-if="isContractOnly"
                        :class="['form-group', { wrong: errors.order.name }]"
                    >
                        <p class="my-auto">
                            <label>Nombre</label>
                            <span data-color="rojo">*</span>
                        </p>
                        <div class="input-group">
                            <input
                                v-on:focus="delete errors.order['name']"
                                data-size="12"
                                v-model="opportunity.order.name"
                                type="text"
                            />
                        </div>
                        <span v-if="errors.order.name" class="error">{{
                            errors.order.name
                        }}</span>
                    </div>

                    <!--Tipo de producto y comercializadora-->
                    <div
                        class="d-grid"
                        :data-column="
                            opportunity.order.productType === 'cd' ? 2 : 1
                        "
                    >
                        <!--Tipo de producto-->
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
                                    :disabled="isInputsDisabled"
                                    v-on:change="changeProductType"
                                >
                                    <option value="">Selecciona uno</option>
                                    <option
                                        v-for="type in productTypesWithoutSp"
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

                        <!--Comercializadora-->
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
                                    :disabled="isInputsDisabled"
                                    v-on:change="changeMarketer"
                                >
                                    <option value="">Selecciona una</option>
                                    <option
                                        v-for="marketer in filteredMarketers"
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

                    <!--Comercializadora y tarifa-->
                    <div
                        class="d-grid"
                        :data-column="
                            opportunity.order.productType === 'cd' ? 1 : 2
                        "
                    >
                        <!--Comercializadora-->
                        <div
                            v-if="
                                opportunity.order.productType !== 'cd' &&
                                opportunity.order.productType &&
                                !marketerProductsOthers[
                                    productTypesWithoutSp.find(
                                        (pt) =>
                                            pt.code ===
                                            opportunity.order.productType,
                                    )?.productToSee
                                ]
                            "
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

                        <!--Si es dual muestro label de parte de luz-->
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

                        <!--Tarifa-->
                        <div
                            v-if="
                                opportunity.order.productType !== 'cd' &&
                                opportunity.order.productType !== 'sa' &&
                                opportunity.order.productType &&
                                !marketerProductsOthers[
                                    productTypesWithoutSp.find(
                                        (pt) =>
                                            pt.code ===
                                            opportunity.order.productType,
                                    )?.productToSee
                                ] &&
                                opportunity.order.marketer !== ''
                            "
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

                    <!--PRODUCTO-->

                    <!--si no es contrato de luz ni de gas-->
                    <div
                        v-if="
                            opportunity.order.productType &&
                            !!marketerProductsOthers[
                                productTypesWithoutSp.find(
                                    (pt) =>
                                        pt.code ===
                                        opportunity.order.productType,
                                )?.productToSee
                            ]
                        "
                        v-bind:class="{ wrong: errors.product }"
                        class="form-group"
                    >
                        <p class="my-auto"><label>Producto</label></p>
                        <div class="input-group">
                            <select
                                v-model="opportunity.order.product"
                                :disabled="isInputsDisabled"
                                v-on:change="delete errors.product"
                            >
                                <option value="">Selecciona una</option>
                                <option
                                    v-for="marketer in marketerProductsOthers[
                                        productTypesWithoutSp.find(
                                            (productType) =>
                                                productType.code ===
                                                opportunity.order.productType,
                                        ).productToSee
                                    ]"
                                    :value="marketer"
                                >
                                    {{ marketer }}
                                </option>
                            </select>
                        </div>
                        <span v-if="errors.product" class="error">{{
                            errors.product
                        }}</span>
                    </div>

                    <div
                        v-else-if="
                            opportunity.order.marketer &&
                            !marketerProductsOthers[
                                productTypesWithoutSp.find(
                                    (pt) =>
                                        pt.code ===
                                        opportunity.order.productType,
                                )?.productToSee
                            ]
                        "
                        class="d-grid"
                        :data-column="
                            opportunity.order.productType === 'cd' ? 2 : 1
                        "
                        data-gap="20"
                    >
                        <!--Tarifa-->
                        <div
                            v-if="
                                opportunity.order.productType === 'cd' &&
                                opportunity.order.marketer !== ''
                            "
                            v-bind:class="{ wrong: errors.fee }"
                            class="form-group"
                        >
                            <p class="my-auto">
                                <label>Tarifa</label>
                                <span data-color="rojo">*</span>
                            </p>
                            <div class="input-group">
                                <select
                                    v-model="opportunity.order.fee"
                                    :disabled="isInputsDisabled"
                                    v-on:change="changeFee"
                                >
                                    <option value="">Selecciona una</option>
                                    <option
                                        v-for="fee in filteredFees"
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

                        <!--Producto-->
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
                                    :disabled="isInputsDisabled"
                                >
                                    <option value="">Selecciona una</option>
                                    <option
                                        v-for="product in filteredMarketerProducts"
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

                        <div class="ev-budget-note mb-20" v-if="!isEditing">
                            <div class="d-flex justify-between">
                                <span>Presupuesto guardado</span>
                                <strong>{{
                                    opportunity.order.evChargerBudget
                                        ?.budgetSavedAt
                                        ? formatBudgetDate(
                                              opportunity.order.evChargerBudget
                                                  .budgetSavedAt,
                                          )
                                        : "Sin fecha"
                                }}</strong>
                            </div>
                        </div>

                        <div
                            class="form-group"
                            v-bind:class="{
                                wrong:
                                    errors.order &&
                                    errors.order.evChargerBudget &&
                                    errors.order.evChargerBudget.chargerModel,
                            }"
                        >
                            <label>Modelo de cargador</label>
                            <div class="input-group">
                                <select
                                    v-model="
                                        opportunity.order.evChargerBudget
                                            .chargerModel
                                    "
                                    :disabled="isInputsDisabled"
                                    v-on:change="applyEvChargerDefaultsByModel"
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
                                v-if="
                                    errors.order &&
                                    errors.order.evChargerBudget &&
                                    errors.order.evChargerBudget.chargerModel
                                "
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
                                        v-model="
                                            opportunity.order.evChargerBudget
                                                .chargerBrand
                                        "
                                        :disabled="isInputsDisabled"
                                        type="text"
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
                                        disabled
                                        type="text"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="half-space">
                            <div class="form-group">
                                <label>Potencia del cargador</label>
                                <div class="input-group">
                                    <select
                                        v-model="
                                            opportunity.order.evChargerBudget
                                                .chargerPower
                                        "
                                        :disabled="isInputsDisabled"
                                    >
                                        <option value="">Selecciona una</option>
                                        <option value="3,7kW">3,7kW</option>
                                        <option value="7,4kW">7,4kW</option>
                                        <option value="9,2kW">9,2kW</option>
                                        <option value="11kW">11kW</option>
                                        <option value="22kW">22kW</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tipo de instalación</label>
                                <div class="input-group">
                                    <select
                                        v-model="
                                            opportunity.order.evChargerBudget
                                                .installationType
                                        "
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                    :disabled="isInputsDisabled"
                                />
                            </div>
                        </div>

                        <div
                            class="text mt-30 mb-10"
                            data-size="15"
                            data-weight="600"
                        >
                            Costes de instalación guardados
                        </div>

                        <p class="text opacity-5 mb-20" data-size="11">
                            Al cambiar la distancia se calculan automáticamente
                            el cableado y el tubo hasta 70 m. La mano de obra y
                            el boletín respetan el importe indicado manualmente.
                        </p>

                        <div class="half-space">
                            <div class="form-group">
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                            isInputsDisabled ||
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
                                    :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                            isInputsDisabled ||
                                            !opportunity.order.evChargerBudget
                                                .needsCivilWork
                                        "
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
                                    :disabled="isInputsDisabled"
                                ></textarea>
                            </div>
                        </div>

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
                                v-if="!isInputsDisabled"
                            >
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <p
                            v-if="
                                !opportunity.order.evChargerBudget
                                    .optionalItems ||
                                !opportunity.order.evChargerBudget.optionalItems
                                    .length
                            "
                            class="text opacity-3 mb-20"
                            data-size="10"
                        >
                            No hay opcionales guardados.
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
                                    v-if="!isInputsDisabled"
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
                                            :disabled="isInputsDisabled"
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
                                            :disabled="isInputsDisabled"
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
                                            :disabled="isInputsDisabled"
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
                                            :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                        :disabled="isInputsDisabled"
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
                                    :disabled="isInputsDisabled"
                                ></textarea>
                            </div>
                        </div>

                        <div class="ev-budget-summary mt-20">
                            <div class="d-flex justify-between">
                                <span>Cargador después de descuento</span
                                ><strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.chargerSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div class="d-flex justify-between">
                                <span>Mano de obra</span
                                ><strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.laborSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div class="d-flex justify-between">
                                <span>Distancia del cableado</span
                                ><strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.cableSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div class="d-flex justify-between">
                                <span>Tubo / manguera</span
                                ><strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.modulationCableSubtotal,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div class="d-flex justify-between">
                                <span>Boletín / certificado y legalización</span
                                ><strong
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
                                ><strong
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
                                <span>Obra civil</span
                                ><strong
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
                                <span>Opcionales</span
                                ><strong
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
                                <span>Base imponible</span
                                ><strong
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
                                <span>Descuento {{ evChargerBudgetTotals.globalDiscount }}%</span
                                ><strong style="color:#e53e3e"
                                    >-{{ formatCurrency(evChargerBudgetTotals.discountAmount) }} €</strong
                                >
                            </div>
                            <div class="d-flex justify-between">
                                <span
                                    >IVA
                                    {{
                                        opportunity.order.evChargerBudget
                                            .chargerVatPercentage || 21
                                    }}%</span
                                ><strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.vat,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                            <div class="d-flex justify-between total">
                                <span>Total</span
                                ><strong
                                    >{{
                                        formatCurrency(
                                            evChargerBudgetTotals.total,
                                        )
                                    }}
                                    €</strong
                                >
                            </div>
                        </div>

                        <div
                            class="d-flex justify-end mt-20"
                            v-if="!isReadOnly"
                            data-gap="10"
                        >
                            <button
                                class="custom-button"
                                data-size="medium"
                                data-bg="azul"
                                type="button"
                                v-on:click.prevent="sendEvChargerBudget"
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
                                v-on:click.prevent="saveEvChargerBudget"
                                :disabled="isSavingEvChargerBudget"
                            >
                                <span v-if="isSavingEvChargerBudget">
                                    Guardando presupuesto...
                                    <i
                                        class="fa-solid fa-spinner-third fa-spin ml-10"
                                    ></i>
                                </span>
                                <span v-else>
                                    Guardar presupuesto
                                    <i class="fas fa-save ml-10"></i>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!--CUPS-->
                    <div
                        v-bind:class="{ wrong: errors.CUPS }"
                        class="form-group"
                        v-if="
                            !marketerProductsOthers[
                                productTypesWithoutSp.find(
                                    (pt) =>
                                        pt.code ===
                                        opportunity.order.productType,
                                )?.productToSee
                            ] &&
                            ((opportunity.order.productType === 'cd' &&
                                opportunity.order.marketer !== '') ||
                                (!!opportunity.order.productType &&
                                    opportunity.order.productType === 'cl') ||
                                opportunity.order.productType === 'cg')
                        "
                    >
                        <p class="my-auto"><label>CUPS</label></p>
                        <div class="input-group">
                            <input
                                v-on:focus="delete errors['CUPS']"
                                data-size="10"
                                v-model="opportunity.order.CUPS"
                                :disabled="isInputsDisabled"
                                v-on:input="checkCUPS"
                                type="text"
                            />
                        </div>
                        <span v-if="errors.CUPS" class="error">{{
                            errors.CUPS
                        }}</span>
                    </div>

                    <!--PARTE GAS SI ES DUAL-->
                    <div
                        v-if="
                            opportunity.order.productType === 'cd' &&
                            opportunity.order.product !== ''
                        "
                    >
                        <!--Si es dual muestro label de parte de luz-->
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

                        <!--Tarifa y producto de gas-->
                        <div class="d-grid" data-column="2" data-gap="20">
                            <!--Tarifa-->
                            <div
                                v-bind:class="{ wrong: errors.feeSecondary }"
                                class="form-group"
                            >
                                <p class="my-auto">
                                    <label>Tarifa</label>
                                    <span data-color="rojo">*</span>
                                </p>
                                <div class="input-group">
                                    <select
                                        v-model="opportunity.order.feeSecondary"
                                        :disabled="isInputsDisabled"
                                        v-on:change="changeFeeSecondary"
                                    >
                                        <option value="">Selecciona una</option>
                                        <option
                                            v-for="fee in filteredFeesSecondary"
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

                            <!--Producto-->
                            <div
                                v-if="opportunity.order.feeSecondary"
                                v-bind:class="{
                                    wrong: errors.productSecondary,
                                }"
                                class="form-group"
                            >
                                <p class="my-auto">
                                    <label>Producto</label>
                                    <span data-color="rojo">*</span>
                                </p>
                                <div class="input-group">
                                    <select
                                        v-model="
                                            opportunity.order.productSecondary
                                        "
                                        :disabled="isInputsDisabled"
                                    >
                                        <option value="">Selecciona una</option>
                                        <option
                                            v-for="product in filteredMarketerProductsSecondary"
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

                        <!--CUPS-->
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
                                    :disabled="isInputsDisabled"
                                    type="text"
                                />
                            </div>
                            <span v-if="errors.CUPSSecondary" class="error">{{
                                errors.CUPSSecondary
                            }}</span>
                        </div>
                    </div>

                    <!--Productos extras-->
                    <div class="form-group">
                        <label>Productos extra</label>

                        <div class="input-group column h-100">
                            <!--Buscador-->
                            <div class="mb-2 d-flex items-center">
                                <i
                                    class="fa-regular fa-magnifying-glass text my-auto mr-10"
                                />
                                <input
                                    type="text"
                                    v-model="extraSearchText"
                                    class="form-control"
                                    placeholder="Buscar extra..."
                                />
                            </div>

                            <!--Separador-->
                            <div class="separator my-5"></div>

                            <!--Cada producto-->
                            <div class="h-100 h-150-px-max scroll-y">
                                <div
                                    class="d-flex my-2"
                                    v-for="extra in extraProductsToSelect"
                                >
                                    <!--check-->
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
                                                        extra.id.$oid,
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

                    <div class="separator mt-30"></div>

                    <!--FACTURACIÓN-->
                    <div class="text mt-50" data-size="17" data-weight="600">
                        Dirección de suministro
                    </div>

                    <!--Provincia y localidad-->
                    <div class="half-space">
                        <!--Provincia-->
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

                        <!--Localidad-->
                        <div
                            v-bind:class="{ wrong: errors.order.town }"
                            class="form-group"
                        >
                            <label>Localidad</label>
                            <div class="input-group">
                                <input
                                    v-on:focus="
                                        delete errors['order']['address']
                                    "
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

                    <!--direc y cod postal-->
                    <div class="half-space">
                        <!--Dirección-->
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

                        <!--Cod. postal-->
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

                <!--DATOS RELACIONADOS-->
                <div class="mobile-item">
                    <div class="text mt-50" data-size="17" data-weight="600">
                        Datos relacionados
                    </div>

                    <!--Usuario creador-->
                    <div v-if="opportunity.createdBy" class="my-20">
                        <p
                            class="text opacity-5"
                            data-size="13"
                            data-weight="600"
                        >
                            <i class="fas fa-user mr-5"></i> Creador de la
                            oportunidad
                        </p>

                        <div class="separator my-0"></div>

                        <div class="d-flex justify-center mr-20 my-10 w-100">
                            <div
                                class="initials verySmall mr-20 my-auto"
                                data-style="initials"
                                v-bind:class="{
                                    image: opportunity.createdBy.profileImage,
                                }"
                            >
                                <img
                                    :src="
                                        '/assets/profile_images/' +
                                        opportunity.createdBy.profileImage
                                    "
                                    class="profile-image"
                                />
                            </div>

                            <div class="d-flex column my-auto">
                                <p
                                    class="text opacity-5"
                                    data-weight="600"
                                    data-size="14"
                                >
                                    {{ opportunity.createdBy.firstName }}
                                </p>
                            </div>

                            <div
                                class="custom-button ml-auto my-auto"
                                data-style="twoBlue"
                                data-size="tiny"
                                v-on:click="
                                    actionLink(
                                        this.basicData.userLogged._id ===
                                            opportunity.createdBy._id
                                            ? '/profile'
                                            : `/users/${opportunity.createdBy._id}`,
                                    )
                                "
                            >
                                <i class="fas fa-arrow-right-long"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!--separador-->
            <div class="separator"></div>

            <!--Botón editar-->
            <div
                class="btn-part justify-between opportunity-detail-actions"
                v-if="!isUpdating && !isEditing"
            >
                <div class="d-flex align-center" data-gap="10">
                    <!-- Navegación anterior / siguiente -->
                    <div
                        class="d-flex align-center"
                        data-gap="5"
                        v-if="opportunityNavIds.length > 1"
                    >
                        <button
                            class="custom-button"
                            data-size="small"
                            data-bg="principal"
                            data-mode="outline"
                            :disabled="!prevOpportunityId"
                            :class="{ 'opacity-3': !prevOpportunityId }"
                            v-on:click.prevent="navigatePrev"
                            title="Oportunidad anterior"
                        >
                            <i class="fas fa-chevron-left"></i>
                        </button>

                        <span
                            class="text opacity-5"
                            data-size="12"
                            v-if="currentNavIndex >= 0"
                        >
                            {{ currentNavIndex + 1 }} /
                            {{ opportunityNavIds.length }}
                        </span>

                        <button
                            class="custom-button"
                            data-size="small"
                            data-bg="principal"
                            data-mode="outline"
                            :disabled="!nextOpportunityId"
                            :class="{ 'opacity-3': !nextOpportunityId }"
                            v-on:click.prevent="navigateNext"
                            title="Oportunidad siguiente"
                        >
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <button
                        class="custom-button"
                        data-size="regular"
                        data-bg="principal"
                        data-mode="translucent"
                        v-on:click.prevent="createAccFromOpp"
                    >
                        {{ isContractOnly ? "Crear contrato" : "Crear cuenta" }}
                    </button>
                    <button
                        class="custom-button"
                        data-size="regular"
                        data-bg="amarillo"
                        @click.prevent="history.visible = true"
                    >
                        <i class="far fa-message mr-5"></i>
                        Chat
                        <span
                            v-if="opportunity.messages?.length"
                            class="ml-5 text"
                            data-size="10"
                        >
                            ({{ opportunity.messages.length }})
                        </span>
                    </button>
                    <button
                        class="custom-button"
                        data-size="regular"
                        data-bg="azul"
                        v-on:click.prevent="isSeeingDocs = !isSeeingDocs"
                    >
                        Documentos
                    </button>
                    <button
                            v-if="basicData?.userSubdomain?._id === '65cb57489c2c285441086a43'"
                            class="custom-button"
                            data-size="regular"
                            data-bg="principal"
                            type="button"
                            v-on:click.prevent="copyInstallersFormLink"
                        >
                            Copiar enlace checking para el instalador
                            <i class="far fa-copy ml-10"></i>
                        </button>
                    <div
                        v-if="opportunity.evChargerBudgetLogId"
                        class="mt-10 d-flex justify-end"
                    >
                        <button
                            class="custom-button"
                            data-size="medium"
                            type="button"
                            @click.prevent="
                                actionLink(
                                    '/tools?section=logs&logId=' +
                                        opportunity.evChargerBudgetLogId,
                                )
                            "
                        >
                            <i class="far fa-file-chart-column mr-5"></i>
                            Log del formulario
                        </button>
                    </div>
                </div>

                <div>
                    <button
                        class="custom-button mr-10"
                        data-size="regular"
                        data-bg="rojo"
                        v-on:click.prevent="actionLink('/opportunities')"
                    >
                        Volver
                    </button>
                    <button
                        class="custom-button"
                        data-size="regular"
                        v-if="!isReadOnly"
                        v-on:click="isEditing = true"
                    >
                        Editar
                    </button>
                </div>
            </div>

            <!--Botón guardar-->
            <div
                class="btn-part justify-end opportunity-detail-actions"
                v-if="!isUpdating && isEditing"
            >
                <div>
                    <button
                        class="custom-button mr-10"
                        data-size="regular"
                        data-bg="rojo"
                        v-on:click.prevent="restartOpp"
                    >
                        Cancelar
                    </button>
                    <button
                        class="custom-button mr-10"
                        data-size="regular"
                        data-bg="azul"
                        v-if="!isReadOnly"
                        v-on:click.prevent="updateOpportunityAndStay"
                    >
                        Actualizar <i class="fas fa-save ml-10"></i>
                    </button>
                    <button
                        class="custom-button"
                        data-size="regular"
                        v-if="!isReadOnly"
                    >
                        Guardar <i class="fas fa-chevron-right ml-10"></i>
                    </button>
                </div>
            </div>

            <!--Actualizando-->
            <div
                class="btn-part opportunity-detail-actions is-loading"
                v-if="isUpdating"
            >
                <button class="custom-button" data-size="regular">
                    <i class="fa-solid fa-spinner-third fa-spin mr-5"></i>
                    Espere un momento
                </button>
            </div>

            <!--DOCUMENTOS-->
            <div class="docs" v-if="isSeeingDocs">
                <div class="d-flex justify-between">
                    <p class="text" data-size="15" data-weight="700">
                        Docs. adjuntos
                    </p>

                    <div
                        class="custom-button ml-auto my-auto"
                        data-size="small"
                        data-bg="amarillo"
                        v-if="!isInputsDisabled"
                        v-on:click="openDialog"
                    >
                        <i class="fas fa-plus"></i>
                    </div>
                </div>

                <input
                    id="oppDocFile"
                    type="file"
                    style="display: none"
                    multiple
                    v-on:change="pickupDocs"
                />

                <div class="div-content">
                    <doc-component
                        v-for="(doc, docInd) in opportunity.docs"
                        :key="doc.id || doc.value || docInd"
                        :doc="doc"
                        :docInd="docInd"
                        :isReadOnly="isInputsDisabled"
                        @delDoc="delDoc"
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
        </form>

        <div
            class="floating-box z-11"
            :class="{ seeing: history.visible, 'd-none': !history.visible }"
            @click.self="history.visible = false"
        >
            <div
                class="register-pos w-70 h-80 round px-40 py-20"
                data-round="20"
                data-border-color="principal"
            >
                <div class="d-flex column h-100">
                    <div class="d-flex justify-between">
                        <p class="text mr-40" data-size="20" data-weight="700">
                            Chat de seguimiento
                        </p>
                        <i
                            class="text fas fa-close pointer"
                            data-size="20"
                            @click="history.visible = false"
                        />
                    </div>

                    <div
                        class="flex-1 mt-15 scroll-y"
                        id="opp-messages-container"
                    >
                        <div class="my-5" v-for="msg in filteredHistory">
                            <div
                                :class="[
                                    {
                                        'ml-auto':
                                            msg.creator.id ===
                                            basicData.userLogged._id,
                                    },
                                    'dashboard-card column w-45 py-10',
                                ]"
                                :data-bg="
                                    msg.creator.id === basicData.userLogged._id
                                        ? 'celeste'
                                        : ''
                                "
                                :data-color="
                                    msg.creator.id === basicData.userLogged._id
                                        ? 'blanco'
                                        : ''
                                "
                            >
                                <div
                                    class="d-flex align-center justify-between"
                                >
                                    <div
                                        class="d-flex align-center"
                                        data-gap="5"
                                    >
                                        <img
                                            class="w-25-px h-25-px round cover-img"
                                            data-round="10"
                                            :src="
                                                '/assets/profile_images/' +
                                                msg.creator.profileImage
                                            "
                                            alt="Avatar"
                                        />
                                        <p class="line-clamp-1" data-size="12">
                                            {{ msg.creator.name }}
                                        </p>
                                    </div>
                                    <p
                                        :class="[
                                            {
                                                'opacity-5':
                                                    msg.creator.id !==
                                                    basicData.userLogged._id,
                                            },
                                        ]"
                                        data-size="10"
                                    >
                                        <i class="fa-solid fa-calendar"></i>
                                        {{ getPrettyDateHistory(msg.date) }}
                                    </p>
                                </div>

                                <div class="mt-10">{{ msg.data.message }}</div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="createNewMessage">
                        <div class="dashboard-card w-100">
                            <input
                                class="w-100 text mr-15"
                                style="border: none; background: transparent"
                                v-model="newMessage"
                                placeholder="Aquí va el mensaje"
                            />
                            <button
                                type="submit"
                                class="custom-button pointer"
                                data-size="small"
                                :disabled="sendingMessage"
                            >
                                <i
                                    v-if="sendingMessage"
                                    class="fa-regular fa-spinner fa-spin"
                                ></i>
                                <i v-else class="far fa-paper-plane-top" />
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
const ZOCO_ID = "65cb57489c2c285441086a43";

export default {
    name: "OpportunityDetailsComponent",
    props: ["basicData"],
    data() {
        return {
            history: {
                visible: false,
            },
            opportunityNavIds: [],
            prefetchCache: {},
            newMessage: "",
            sendingMessage: false,
            users: [],
            selectedObservationDate: new Date().toISOString().split("T")[0],
            isContractOnly: this.opportunity?.order?.name === "",
            createUserData: {
                selected: "account",
                types: [
                    { code: "account", title: "Cuenta/Contrato" },
                    { code: "contract", title: "Solo Contrato" },
                ],
            },
            isSendingEvChargerBudget: false,
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
            opportunity: "",
            opportunityDefault: "",
            opportunityAccounts: [],
            errors: {
                billingInfo: {},
                order: {},
            },
            sources: [],
            sectors: [],
            statuses: [],
            searchAddressText: "",
            GEO: {
                communities: "",
                provinces: "",
                localities: "",
                addresses: "",
            },
            imageFile: "",
            urlImage: "",
            selectValues: "",
            accounts: [],
            contacts: [],
            marketers: [],
            marketerProductsOthers: {
                n: ["Sin excedentes", "Con excedentes", "Compartido"], //normales
                ct: [
                    "Fibra",
                    "Fibra + línea móvil",
                    "Línea móvil",
                    "Línea móvil adicional",
                    "Servicios adicionales",
                ], //telefonía
                crm: ["Contrato de CRM", "Contrato de colaborador"],
                electricCar: ["Cargador Hibrido", "Cargador Electrico 100%"],
                electricityMeter: ["Menos de 450kW", "Más de 450kW"],
            }, //no gas no luz
            searchAccountText: "",
            isAccFocused: false,
            isEditing: false,
            isUpdating: false,
            isSavingEvChargerBudget: false,
            extraSearchText: "",
            isSeeingDocs: false,
            isLoadingOpportunity: false,
        };
    },
    created() {
        //Obtengo las comercializadoras con sus respectivos productos, etc
        this.fetchMarketers();

        this.fetchContacts();
    },
    mounted() {
        //Saco la oportunidad
        this.fetchOpportunity();

        //Obtengo las comunidades
        this.getCommunities();

        //Saco los valores para los selects
        this.fetchSelectValues();

        //Saco las cuentas relacionadas
        this.fetchAccounts();
        axios
            .get("/api/user/indexAll")
            .then((res) => {
                this.users = res.data.users;
            })
            .catch((err) => console.log(err));
        const stored = sessionStorage.getItem("opportunityNavIds");
        if (stored) {
            try {
                this.opportunityNavIds = JSON.parse(stored);
            } catch {
                this.opportunityNavIds = [];
            }
        }
        this.loadFromNavCache(String(this.$route.params.id));
    },
    watch: {
        "basicData.userLogged"() {
            //Saco las cuentas relacionadas
            this.fetchAccounts();

            //Saco los contactos relacionados
            this.fetchContacts();
        },
        "opportunity.billingInfo.community"() {
            this.getProvinces();
        },
        "opportunity.billingInfo.province"() {
            this.getLocalities();
        },
        "opportunity.profileImage"() {
            if (this.opportunity && this.opportunity.profileImage)
                this.urlImage =
                    "/assets/opportunity_images/" +
                    this.opportunity.profileImage;
        },
        "opportunity.order.evChargerBudget.hasPhotovoltaic"(value) {
            if (!this.opportunity?.order?.evChargerBudget) return;
            if (!value) {
                this.opportunity.order.evChargerBudget.wantsSurplusOptimization = false;
                this.opportunity.order.evChargerBudget.surplusOptimizationPrice =
                    this.evChargerDefaultCosts.surplusOptimizationPrice;
            }
        },
        "opportunity.order.evChargerBudget.needsCivilWork"(value) {
            if (!this.opportunity?.order?.evChargerBudget) return;
            if (!value) {
                this.opportunity.order.evChargerBudget.civilWorkPrice =
                    this.evChargerDefaultCosts.civilWorkPrice;
                this.opportunity.order.evChargerBudget.civilWorkDescription =
                    "";
            }
        },
        "opportunity.usersIds": {
            deep: true,
            handler() {
                if (this.isLoadingOpportunity) return;
                if (!this.opportunity?.order) return;
                this.opportunity.order.usersIds = Array.isArray(
                    this.opportunity.usersIds,
                )
                    ? [...this.opportunity.usersIds]
                    : [];
            },
        },
        "opportunity.observations"() {
            this.selectedObservationDate = new Date()
                .toISOString()
                .split("T")[0];
        },
        "$route.params.id": {
            immediate: false,
            async handler(newId) {
                if (!newId) return;

                // 1. Reset estado
                this.isEditing = false;
                this.errors = { billingInfo: {}, order: {} };
                this.opportunityAccounts = [];

                // 2. Actualiza IDs de navegación
                const stored = sessionStorage.getItem("opportunityNavIds");
                if (stored) {
                    try {
                        this.opportunityNavIds = JSON.parse(stored);
                    } catch {
                        this.opportunityNavIds = [];
                    }
                }

                // 3. Scroll arriba inmediatamente
                window.scrollTo({ top: 0, behavior: "smooth" });

                // 4. Si hay datos prefetched, mostrar al instante y refrescar en background
                const cached = this.prefetchCache[newId];
                if (cached) {
                    this.applyOpportunityData(
                        JSON.parse(JSON.stringify(cached)),
                    );
                    this.getRelatedAccounts();
                    this.isLoadingOpportunity = false;
                    this.fetchOpportunity(true);
                } else {
                    this.loadFromNavCache(newId);
                    this.fetchOpportunity();
                    this.getRelatedAccounts();
                }
            },
        },
    },
    methods: {
        async sendEvChargerBudget() {
            if (this.isSendingEvChargerBudget) return;

            // Elegir canal de envío
            const { value: sendChannel } = await Swal.fire({
                title: "¿Cómo quieres enviar el presupuesto?",
                input: "radio",
                inputOptions: {
                    email: "Por correo electrónico",
                    whatsapp: "Por WhatsApp",
                },
                inputValue: "email",
                showCancelButton: true,
                confirmButtonText: "Continuar",
                cancelButtonText: "Cancelar",
                inputValidator: (value) => {
                    if (!value) return "Debes elegir una opción";
                },
            });

            if (!sendChannel) return;

            let email = this.opportunity.email;
            let phone = this.opportunity.phone || "";

            if (sendChannel === "email") {
                if (!email || email.trim() === "") {
                    const { value: inputEmail } = await Swal.fire({
                        title: "Email del destinatario",
                        text: "La oportunidad no tiene email. Introduce uno para enviar el presupuesto:",
                        input: "email",
                        inputPlaceholder: "ejemplo@correo.com",
                        showCancelButton: true,
                        confirmButtonText: "Continuar",
                        cancelButtonText: "Cancelar",
                        inputValidator: (value) => {
                            if (!value) return "El email es obligatorio";

                            const regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

                            if (!regex.test(value)) return "El email no es válido";
                        },
                    });

                    if (!inputEmail) return;

                    email = inputEmail;
                }
            }

            if (sendChannel === "whatsapp") {
                const normalizePhone = (value) => {
                    let cleanPhone = String(value || "").replace(/[^\d+]/g, "");

                    if (cleanPhone.startsWith("+")) {
                        cleanPhone = cleanPhone.substring(1);
                    }

                    // Si es un móvil español sin prefijo, añadimos 34
                    if (cleanPhone.length === 9) {
                        cleanPhone = "34" + cleanPhone;
                    }

                    return cleanPhone;
                };

                phone = normalizePhone(phone);

                if (!phone || phone.trim() === "") {
                    const { value: inputPhone } = await Swal.fire({
                        title: "WhatsApp del destinatario",
                        text: "La oportunidad no tiene teléfono. Introduce uno para enviar el presupuesto:",
                        input: "tel",
                        inputPlaceholder: "Ejemplo: 34600000000",
                        showCancelButton: true,
                        confirmButtonText: "Continuar",
                        cancelButtonText: "Cancelar",
                        inputValidator: (value) => {
                            if (!value) return "El teléfono es obligatorio";

                            const normalized = normalizePhone(value);

                            if (!/^[0-9]{11,15}$/.test(normalized)) {
                                return "El teléfono no es válido. Ejemplo: 34600000000";
                            }
                        },
                    });

                    if (!inputPhone) return;

                    phone = normalizePhone(inputPhone);
                }
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


                // Cambiar tambien al 20 % 


                inputValue: "60",
                showCancelButton: true,
                confirmButtonText: "Enviar presupuesto",
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

                sendChannel, // email | whatsapp
                recipientEmail: sendChannel === "email" ? email : null,
                recipientPhone: sendChannel === "whatsapp" ? phone : null,

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
                    text:
                        sendChannel === "email"
                            ? `El presupuesto ha sido enviado por email a ${email}`
                            : `El presupuesto ha sido enviado por WhatsApp a ${phone}`,
                    timer: 2000,
                    timerProgressBar: true,
                });
            } catch (err) {
                console.error(err);

                Swal.fire({
                    icon: "error",
                    title: "Error al enviar",
                    text:
                        sendChannel === "email"
                            ? "No se ha podido enviar el presupuesto por email"
                            : "No se ha podido enviar el presupuesto por WhatsApp",
                });
            } finally {
                this.isSendingEvChargerBudget = false;
            }
        },
        getOpportunityIdForInstallersForm() {
            return String(
                this.opportunity?._id?.$oid ||
                    this.opportunity?._id ||
                    this.$route?.params?.id ||
                    "",
            );
        },

        getInstallersFormLink() {
            const opportunityId = this.getOpportunityIdForInstallersForm();

            if (!opportunityId) return "";

            return `${window.location.origin}/installersForm?id=${encodeURIComponent(opportunityId)}`;
        },

        async copyInstallersFormLink() {
            const link = this.getInstallersFormLink();

            if (!link) {
                Swal.fire({
                    icon: "error",
                    title: "No se pudo copiar",
                    text: "No se ha encontrado el ID de la oportunidad.",
                });

                return;
            }

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(link);
                } else {
                    const textarea = document.createElement("textarea");
                    textarea.value = link;
                    textarea.style.position = "fixed";
                    textarea.style.opacity = "0";
                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();
                    document.execCommand("copy");
                    document.body.removeChild(textarea);
                }

                Swal.fire({
                    icon: "success",
                    title: "Link copiado",
                    text: link,
                    timer: 1500,
                    timerProgressBar: true,
                });
            } catch (err) {
                console.error(err);

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No se ha podido copiar el link.",
                });
            }
        },
        insertDateInObservations() {
            const dateStr =
                this.selectedObservationDate ||
                new Date().toISOString().split("T")[0];
            const [anio, mes, dia] = dateStr.split("-");
            const fecha = `[${dia}/${mes}/${anio}] `;

            const textarea = document.getElementById("observationsTextarea");
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const texto = this.opportunity.observations || "";

            this.opportunity.observations =
                texto.slice(0, start) + fecha + texto.slice(end);

            this.$nextTick(() => {
                textarea.selectionStart = textarea.selectionEnd =
                    start + fecha.length;
                textarea.focus();
            });
        },
        onFormEnter(e) {
            if (e.target.tagName !== "TEXTAREA") {
                e.preventDefault();
            }
        },
        loadFromNavCache(id) {
            try {
                const preview = window.history.state?.opportunityPreview;
                if (!preview) return;

                const previewId =
                    typeof preview._id === "object"
                        ? preview._id.$oid || String(preview._id)
                        : String(preview._id);

                if (previewId !== id) return;

                this.opportunity = preview;
            } catch {
                /* ignorar */
            }
        },
        navigatePrev() {
            if (this.prevOpportunityId)
                this.$router.push("/opportunities/" + this.prevOpportunityId);
        },
        navigateNext() {
            if (this.nextOpportunityId)
                this.$router.push("/opportunities/" + this.nextOpportunityId);
        },
        getEvBillableCableMeters(meters) {
            const numericMeters = this.safeNumber(meters);

            if (numericMeters <= 0) return 0;

            return Math.min(numericMeters, 70) * 4;
        },
        normalizeId(value) {
            if (value && typeof value === "object" && value.$oid) {
                return String(value.$oid);
            }

            return String(value || "");
        },
        parseStringToNumber(value) {
            if (value === null || value === undefined || value === "") return 0;
            if (typeof value === "number")
                return Number.isFinite(value) ? value : 0;

            const normalizedValue = String(value)
                .replace(/\s/g, "")
                .replace(/\./g, "")
                .replace(",", ".");

            const number = Number(normalizedValue);
            return Number.isFinite(number) ? number : 0;
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
            return (
                this.normalizeId(this.basicData?.userSubdomain?._id) === ZOCO_ID
            );
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
        formatBudgetDate(value) {
            if (!value) return "";
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString("es-ES");
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
            return phase || "";
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

            if (!row) {
                budget.installationMetersRange = "";
                budget.installationCablePrice = 0;
                budget.installationTubePrice = 0;

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

                return;
            }

            budget.installationMetersRange = row.meters;

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
            budget.laborPrice = row.labor;

            budget.installationCablePrice =
                this.getEvBillableCableMeters(cableMeters) *
                this.safeNumber(budget.cablePricePerMeter);
            budget.installationTubePrice =
                tubeMeters *
                this.safeNumber(budget.modulationCablePricePerMeter);
        },
        ensureEvChargerBudgetStructure() {
            if (!this.opportunity || !this.opportunity.order) return;
            if (!this.opportunity.order.evChargerBudget)
                this.opportunity.order.evChargerBudget = {};

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
            };
        },
        applyEvChargerDefaultsByModel() {
            this.ensureEvChargerBudgetStructure();

            const budget = this.opportunity.order.evChargerBudget;
            const selected = this.evChargerModels.find(
                (charger) => charger.chargerModel === budget.chargerModel,
            );

            if (!this.errors.order) this.errors.order = { evChargerBudget: {} };
            if (!this.errors.order.evChargerBudget)
                this.errors.order.evChargerBudget = {};

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
            const quantity = this.safeNumber(optional.quantity);
            const price = this.safeNumber(optional.price);
            const discount = this.safeNumber(optional.discount);
            return quantity * price * (1 - discount / 100);
        },
        addEvChargerOptional() {
            if (this.isInputsDisabled) return;
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
            if (this.isInputsDisabled) return;
            this.opportunity.order.evChargerBudget.optionalItems.splice(
                optionalInd,
                1,
            );
        },
        getEvChargerBudgetLines() {
            const budget = this.opportunity?.order?.evChargerBudget || {};
            const lines = [];

            if (this.evChargerBudgetTotals.chargerSubtotal > 0) {
                lines.push({
                    key: "charger",
                    description:
                        `Cargador ${budget.chargerModel || ""} ${budget.chargerPower || ""}`.trim(),
                    quantity: 1,
                    unitPrice: this.safeNumber(budget.chargerInstallationPrice),
                    discount: this.safeNumber(
                        budget.chargerInstallationDiscount,
                    ),
                    amount: this.evChargerBudgetTotals.chargerSubtotal,
                });
            }

            if (this.evChargerBudgetTotals.laborSubtotal > 0) {
                lines.push({
                    key: "labor",
                    description:
                        "Mano de obra, desplazamiento y pequeño material",
                    quantity: 1,
                    unitPrice: this.safeNumber(budget.laborPrice),
                    discount: 0,
                    amount: this.evChargerBudgetTotals.laborSubtotal,
                });
            }

            if (this.evChargerBudgetTotals.cableSubtotal > 0) {
                lines.push({
                    key: "cable",
                    description: "Distancia del cableado",
                    quantity: this.getEvBillableCableMeters(budget.cableMeters),
                    unitPrice: this.safeNumber(budget.cablePricePerMeter),
                    discount: 0,
                    amount: this.evChargerBudgetTotals.cableSubtotal,
                });
            }

            if (this.evChargerBudgetTotals.modulationCableSubtotal > 0) {
                lines.push({
                    key: "modulationCable",
                    description: "Tubo / manguera",
                    quantity: this.safeNumber(budget.modulationCableMeters),
                    unitPrice: this.safeNumber(
                        budget.modulationCablePricePerMeter,
                    ),
                    discount: 0,
                    amount: this.evChargerBudgetTotals.modulationCableSubtotal,
                });
            }

            if (this.evChargerBudgetTotals.certificateSubtotal > 0) {
                lines.push({
                    key: "certificate",
                    description: "Boletín / certificado y legalización",
                    quantity: 1,
                    unitPrice: this.safeNumber(budget.certificatePrice),
                    discount: 0,
                    amount: this.evChargerBudgetTotals.certificateSubtotal,
                });
            }

            if (this.evChargerBudgetTotals.surplusOptimizationSubtotal > 0) {
                lines.push({
                    key: "surplusOptimization",
                    description: "Optimización de excedentes fotovoltaicos",
                    quantity: 1,
                    unitPrice: this.safeNumber(budget.surplusOptimizationPrice),
                    discount: 0,
                    amount: this.evChargerBudgetTotals
                        .surplusOptimizationSubtotal,
                });
            }

            if (
                budget.needsCivilWork &&
                this.evChargerBudgetTotals.civilWorkSubtotal > 0
            ) {
                lines.push({
                    key: "civilWork",
                    description: budget.civilWorkDescription
                        ? `Obra civil necesaria - ${budget.civilWorkDescription}`
                        : "Obra civil necesaria",
                    quantity: 1,
                    unitPrice: this.safeNumber(budget.civilWorkPrice),
                    discount: 0,
                    amount: this.evChargerBudgetTotals.civilWorkSubtotal,
                });
            }

            if (Array.isArray(budget.optionalItems)) {
                budget.optionalItems.forEach((optional, index) => {
                    const amount = this.getOptionalItemTotal(optional);

                    if (amount > 0) {
                        lines.push({
                            key: `optional_${index + 1}`,
                            description: optional.description
                                ? `${optional.name || "Opcional"} - ${optional.description}`
                                : optional.name || "Opcional",
                            quantity: this.safeNumber(optional.quantity),
                            unitPrice: this.safeNumber(optional.price),
                            discount: this.safeNumber(optional.discount),
                            amount,
                        });
                    }
                });
            }

            return lines;
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

            budget.totals = {
                ...this.evChargerBudgetTotals,
                vatPercentage: this.safeNumber(budget.chargerVatPercentage, 21),
            };

            budget.financing = budget.financingEnabled
                ? this.getEvChargerFinancingSummary(this.evChargerBudgetTotals.total)
                : null;

            budget.budgetLines = this.getEvChargerBudgetLines();
            budget.budgetSavedAt = new Date().toISOString();
            budget.budgetType = "electric_car_charger";
        },
        async saveEvChargerBudget() {
            if (this.isSavingEvChargerBudget) return;

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

            this.isSavingEvChargerBudget = true;

            try {
                const payload = {
                    opportunity: this.opportunity,
                    evChargerBudgetTotals: this.evChargerBudgetTotals,
                    basicData: this.basicData,
                    userLogged: this.basicData.userLogged,
                    depositPercentage,
                };

                const formData = new FormData();
                formData.append("payload", JSON.stringify(payload));

                const res = await axios.post(
                    "/api/opportunities/generateEvChargerPDF",
                    formData,
                    {
                        responseType: "blob",
                        headers: { "Content-Type": "multipart/form-data" },
                    },
                );

                // Guardamos referencia al blob ANTES de descargar
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

                // Pregunta si guardar en documentos
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

                    const saveData = new FormData();
                    saveData.append(
                        "opportunity",
                        JSON.stringify(this.opportunity),
                    );
                    saveData.append(
                        "userLogged",
                        JSON.stringify(this.basicData.userLogged),
                    );

                    this.opportunity.docs.forEach((doc, idx) => {
                        if (doc.fileValue instanceof File) {
                            saveData.append(`docFiles[${idx}]`, doc.fileValue);
                        }
                    });

                    await axios.post("/api/opportunities/update", saveData);

                    Swal.fire({
                        icon: "success",
                        title: "¡Guardado!",
                        text: "El presupuesto ha sido guardado en los documentos",
                        timer: 1500,
                        timerProgressBar: true,
                    });

                    await this.fetchOpportunity();
                }
            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No se ha podido descargar el presupuesto del cargador eléctrico",
                });
            } finally {
                this.isSavingEvChargerBudget = false;
            }
        },
        getPrettyDateHistory(date) {
            let d = new Date(date);
            let pad = (n) => String(n).padStart(2, "0");
            return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
        },

        createNewMessage() {
            if (this.sendingMessage || !this.opportunity._id) return;

            this.sendingMessage = true;

            axios
                .post("/api/opportunities/createNewMessage", {
                    opportunityId: this.opportunity._id,
                    message: this.newMessage,
                })
                .then((res) => {
                    this.newMessage = "";

                    if (!!this.opportunity.messages) {
                        this.opportunity.messages.push(res.data.data);
                    } else {
                        this.opportunity.messages = [res.data.data];
                    }

                    this.$nextTick(() => {
                        const container = document.getElementById(
                            "opp-messages-container",
                        );
                        container.scrollTo({
                            top: container.scrollHeight,
                            behavior: "smooth",
                        });
                    });
                })
                .finally(() => {
                    this.sendingMessage = false;
                });
        },
        wipeAccountFieldsForContractOnly() {
            // Campos de cuenta que quieres vaciar
            const accKeys = [
                "name",
                "CIF",
                "phone",
                "email",
                "website",
                "sector",
                "source",
                "status",
                "position",
                "observations",
            ];
            accKeys.forEach((k) => {
                if (this.opportunity) this.opportunity[k] = "";
            });

            // Desvincula cuenta relacionada, si hubiera
            this.opportunity.account = "";

            // Contacto
            if (this.opportunity.contact) {
                this.opportunity.contact.value = "";
                this.opportunity.contact.isFromContacts = false;
            }

            // Dirección de facturación
            this.opportunity.billingInfo = {
                community: "",
                province: "",
                locality: "",
                address: "",
                postal: "",
            };

            // (Opcional) vacía la sección de “Datos relacionados” en el cliente
            this.opportunityAccounts = [];
        },
        onSelectType(code) {
            this.createUserData.selected = code;
            const goContractOnly = code === "contract";

            // Si pasamos a "Solo Contrato" y antes no lo era, limpiamos datos de cuenta
            if (goContractOnly && !this.isContractOnly) {
                this.clearAccountFields();
                this.clearAccountErrors();
            }

            this.isContractOnly = goContractOnly;
        },
        clearAccountFields() {
            // Campos base de cuenta/oportunidad
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

            // Persona de contacto
            if (this.opportunity.contact) {
                this.opportunity.contact.value = "";
                this.opportunity.contact.isFromContacts = false;
            }

            // Dirección de facturación
            if (this.opportunity.billingInfo) {
                this.opportunity.billingInfo.community = "";
                this.opportunity.billingInfo.province = "";
                this.opportunity.billingInfo.locality = "";
                this.opportunity.billingInfo.address = "";
                this.opportunity.billingInfo.postal = "";
            }

            // Listas auxiliares de GEO (para que no queden seleccionables colgando)
            this.GEO.provinces = "";
            this.GEO.localities = "";
            this.searchAddressText = "";
        },
        clearAccountErrors() {
            // Limpia solo los errores de la parte de cuenta/facturación
            if (!this.errors) this.errors = { billingInfo: {}, order: {} };

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
        applyOpportunityData(opp) {
            this.opportunity = opp;

            if (!Array.isArray(this.opportunity.docs))
                this.opportunity.docs = [];
            if (!Array.isArray(this.opportunity.usersIds))
                this.opportunity.usersIds = [];

            this.ensureEvChargerBudgetStructure();

            this.opportunityDefault = JSON.parse(
                JSON.stringify(this.opportunity),
            );

            const hasOrderName = !!(
                this.opportunity?.order?.name &&
                this.opportunity.order.name.trim()
            );
            this.createUserData.selected = hasOrderName
                ? "contract"
                : "account";
            this.isContractOnly = hasOrderName;
        },

        async prefetchAdjacentOpportunities() {
            const ids = [this.prevOpportunityId, this.nextOpportunityId].filter(
                Boolean,
            );

            // Limitar tamaño de caché a las 8 entradas más recientes
            const keys = Object.keys(this.prefetchCache);
            if (keys.length > 8) {
                keys.slice(0, keys.length - 8).forEach(
                    (k) => delete this.prefetchCache[k],
                );
            }

            for (const id of ids) {
                if (id in this.prefetchCache) continue;
                this.prefetchCache[id] = null; // marca como en vuelo
                axios
                    .get(`/api/opportunities/${id}`)
                    .then((res) => {
                        this.prefetchCache[id] = res.data.opportunity;
                    })
                    .catch(() => {
                        delete this.prefetchCache[id];
                    });
            }
        },

        async fetchOpportunity(silent = false) {
            if (!silent) this.isLoadingOpportunity = true;

            await axios
                .get(`/api/opportunities/${this.$route.params.id}`)
                .then(async (res) => {
                    this.applyOpportunityData(res.data.opportunity);

                    if (!this.marketers.length) {
                        await this.fetchMarketers();
                    }

                    this.getRelatedAccounts();

                    // Guardar en caché y prefetchear adyacentes
                    const id = String(this.$route.params.id);
                    this.prefetchCache[id] = JSON.parse(
                        JSON.stringify(res.data.opportunity),
                    );
                    this.$nextTick(() => this.prefetchAdjacentOpportunities());

                    if (!silent) {
                        this.$nextTick(() => {
                            this.isLoadingOpportunity = false;
                        });
                    }
                })
                .catch((err) => {
                    console.log(err);
                    if (!silent) this.isLoadingOpportunity = false;
                });
        },
        async getRelatedAccounts() {
            await axios
                .get(
                    `/api/opportunities/getRelatedAccounts/${this.$route.params.id}`,
                )
                .then((res) => {
                    this.opportunityAccounts = res.data.opportunityAccounts;
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        async getCommunities() {
            await axios
                .get("/api/GEO/getCommunities")
                .then((res) => {
                    this.GEO.communities = res.data.communities;
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        async getProvinces() {
            await axios
                .get(
                    `/api/GEO/getProvinces/${this.opportunity.billingInfo.community}`,
                )
                .then((res) => {
                    this.GEO.provinces = res.data.provinces;
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        async getLocalities() {
            await axios
                .get(
                    `/api/GEO/getLocalities/${this.opportunity.billingInfo.province}`,
                )
                .then((res) => {
                    this.GEO.localities = res.data.localities.sort((a, b) =>
                        a.mun_name.localeCompare(b.mun_name),
                    );
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        selectCommunity(event) {
            delete this.errors["billingInfo"]["community"];

            let communityCode = event.target.value;

            if (communityCode !== "") {
                //Busco cual es para sacar el nombre
                let community = this.GEO.communities.find((community) => {
                    return community.acom_name === communityCode;
                });

                this.opportunity.billingInfo.community = community.acom_name;
            } else {
                this.opportunity.billingInfo.community = "";
            }

            //Deselecciono la provincia y la localidad
            this.opportunity.billingInfo.province = "";

            this.opportunity.billingInfo.locality = "";

            //Borro los registros
            this.GEO.localities = "";

            this.getProvinces();
        },
        selectProvince(event) {
            delete this.errors["billingInfo"]["province"];

            let provinceCode = event.target.value;

            if (provinceCode !== "") {
                //Busco cual es para sacar el nombre
                let province = this.GEO.provinces.find((province) => {
                    return province.prov_name === provinceCode;
                });

                this.opportunity.billingInfo.province = province.prov_name;
            } else {
                this.opportunity.billingInfo.province = "";
            }

            this.opportunity.billingInfo.locality = "";

            this.getLocalities();
        },
        selectLocality(event) {
            delete this.errors["billingInfo"]["locality"];

            let localityCode = event.target.value;

            if (localityCode !== "") {
                //Busco cual es para sacar el nombre
                let locality = this.GEO.localities.find((locality) => {
                    return locality.mun_name === localityCode;
                });

                this.opportunity.billingInfo.locality = locality.mun_name;
            } else {
                this.opportunity.billingInfo.locality = "";
            }
        },

        delDoc(ind) {
            this.opportunity.docs.splice(ind, 1);
        },

        openDialog() {
            $("#oppDocFile").click();
        },

        pickupDocs() {
            const input = $("#oppDocFile");

            if (!input.prop("files")) return;

            const files = input.prop("files");

            for (let file of files) {
                const fileType = file.type || "application/octet-stream";

                const docInfo = {
                    title: "",
                    defaultTitle: file.name,
                    value: "",
                    fileValue: file,
                    icon: this.getIconForFileType(fileType),
                    id: "tmp-" + crypto.randomUUID(),
                    errors: {},
                };

                this.opportunity.docs.push(docInfo);
            }

            input.val("");
        },

        getIconForFileType(fileType) {
            for (let typeInfo of this.$storage.FILE_ICONS) {
                if (typeInfo.types.includes(fileType)) {
                    return typeInfo.icon;
                }
            }

            return "fa-file";
        },

        async searchAddress() {
            await axios
                .get(
                    `http://apiv1.geoapi.es/qcalles?QUERY=${this.searchAddressText}&type=JSON&key=7bf1d1b4d597394430abc701411697c9a95aceca96efccb0e81cbd2451d3c6ce`,
                )
                .then((res) => {
                    this.GEO.addresses = res.data.data;
                })
                .catch((err) => {
                    console.log(err);
                });
        },
        selectAddress(address) {
            this.opportunity.billingInfo.addressFirst =
                address.TVIA + address.NVIAC + " (" + address.NENTSI50 + ")";

            this.searchAddressText = "";
            this.GEO.addresses = "";
        },
        addCustomField() {
            let customField = {
                title: "",
                type: "text",
                value: "",
            };

            this.opportunity.customFields.push(customField);
        },
        delCustomField(fieldInd) {
            this.opportunity.customFields.splice(fieldInd, 1);
        },
        addCustomFields(files) {
            //Creo un campo personalizado más por cada archivo
            files.forEach((file) => {
                let customField = {
                    title: "",
                    type: "image",
                    fileType: file["type"].split("/")[0],
                    value: file,
                };

                this.opportunity.customFields.push(customField);
            });
        },
        async updateOpportunity() {
            this.isUpdating = true;
            let hasErrors = false;

            if (!this.opportunity.order) this.opportunity.order = {};

            // Reset errores
            this.errors = {
                name: null, // para opportunity.name
                CIF: null,
                phone: null,
                email: null,
                billingInfo: { postal: null },
                CUPS: null,
                order: { zip: null, name: null }, // aquí metemos order.name separado
            };

            // =============================
            // VALIDACIONES
            // =============================

            if (!this.isContractOnly) {
                // Nombre de la cuenta/oportunidad
                if (
                    !this.opportunity.name ||
                    this.opportunity.name.trim() === ""
                ) {
                    this.errors.name = this.getErrorMessage("isEmpty");
                    hasErrors = true;
                }

                // CIF/NIF
                if (
                    this.opportunity.CIF &&
                    this.opportunity.CIF.trim() !== ""
                ) {
                    const regexNifCif =
                        /^[0-9]{8}[A-Z]|^[XYZ][0-9]{7}[A-Z]|^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/;

                    if (!regexNifCif.test(this.opportunity.CIF)) {
                        this.errors.CIF = "CIF/NIF no válido";
                        hasErrors = true;
                    }
                }

                // Teléfono
                if (
                    this.opportunity.phone &&
                    this.opportunity.phone.length !== 9
                ) {
                    this.errors.phone = this.getErrorMessage("malformedPhone");
                    hasErrors = true;
                }

                // Email
                const regexEmail =
                    /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                if (
                    this.opportunity.email &&
                    !regexEmail.test(this.opportunity.email)
                ) {
                    this.errors.email = this.getErrorMessage("malformedEmail");
                    hasErrors = true;
                }

                // CP facturación
                if (this.opportunity?.billingInfo?.postal) {
                    const postal = this.opportunity.billingInfo.postal;
                    if (postal.length !== 5) {
                        this.errors.billingInfo.postal =
                            "El zip tiene que tener 5 dígitos";
                        hasErrors = true;
                    } else if (isNaN(postal)) {
                        this.errors.billingInfo.postal =
                            this.getErrorMessage("onlyNumbers");
                        hasErrors = true;
                    }
                }
            } else {
                if (this.isContractOnly) {
                    this.wipeAccountFieldsForContractOnly();
                }
                if (
                    !this.opportunity.order.name ||
                    this.opportunity.order.name.trim() === ""
                ) {
                    this.errors.order.name = this.getErrorMessage("isEmpty");
                    hasErrors = true;
                }
            }

            if (this.opportunity.order.zip) {
                const zip = this.opportunity.order.zip;
                if (zip.length !== 5) {
                    this.errors.order.zip = "El zip tiene que tener 5 dígitos";
                    hasErrors = true;
                } else if (isNaN(zip)) {
                    this.errors.order.zip = this.getErrorMessage("onlyNumbers");
                    hasErrors = true;
                }
            }

            if (
                this.opportunity.order.CUPS &&
                this.opportunity.order.CUPS.length !== 20
            ) {
                this.errors.CUPS = "CUPS no válido";
                hasErrors = true;
            }

            if (!this.opportunity.order.marketer)
                this.opportunity.order.marketer = "Sin Comercializadora";
            if (!this.opportunity.order.fee)
                this.opportunity.order.fee = "Sin Tarifa";
            if (!this.opportunity.order.productType)
                this.opportunity.order.productType = "sp";

            // =============================
            // SUBMIT
            // =============================
            if (hasErrors) {
                this.isUpdating = false;
                return;
            }

            // Si no es solo contrato → limpiar order.name
            if (!this.isContractOnly) {
                this.opportunity.order.name = "";
            }

            this.prepareEvChargerBudgetForSave();

            const data = new FormData();
            data.append("opportunity", JSON.stringify(this.opportunity));
            data.append(
                "userLogged",
                JSON.stringify(this.basicData.userLogged),
            );

            this.opportunity.customFields.forEach((field, idx) => {
                if (field.type === "image" && field.value instanceof File) {
                    data.append(`customFieldFile${idx}`, field.value);
                }
            });

            if (Array.isArray(this.opportunity.docs)) {
                this.opportunity.docs.forEach((doc, idx) => {
                    if (doc.fileValue instanceof File) {
                        data.append(`docFiles[${idx}]`, doc.fileValue);
                    }
                });
            }

            try {
                await axios.post("/api/opportunities/update", data);
                Swal.fire({
                    icon: "success",
                    title: "Oportunidad actualizada!",
                    text: "La oportunidad ha sido actualizada correctamente",
                    timerProgressBar: true,
                    timer: 1500,
                });
                this.isEditing = false;
                // Limpiar caché de esta oportunidad para que la próxima carga sea fresca
                const updatedId = String(
                    this.opportunity?._id?.$oid || this.opportunity?._id || "",
                );
                if (updatedId) delete this.prefetchCache[updatedId];
                this.$router.push("/opportunities");
            } catch (err) {
                console.error(err);
                if (err.response?.data?.cifError) {
                    this.errors.CIF = err.response.data.cifError;
                }
            } finally {
                this.isUpdating = false;
            }
        },
        async updateOpportunityAndStay() {
            this.isUpdating = true;
            let hasErrors = false;

            if (!this.opportunity.order) this.opportunity.order = {};

            this.errors = {
                name: null,
                CIF: null,
                phone: null,
                email: null,
                billingInfo: { postal: null },
                CUPS: null,
                order: { zip: null, name: null },
            };

            if (!this.isContractOnly) {
                if (
                    !this.opportunity.name ||
                    this.opportunity.name.trim() === ""
                ) {
                    this.errors.name = this.getErrorMessage("isEmpty");
                    hasErrors = true;
                }

                if (
                    this.opportunity.CIF &&
                    this.opportunity.CIF.trim() !== ""
                ) {
                    const regexNifCif =
                        /^[0-9]{8}[A-Z]|^[XYZ][0-9]{7}[A-Z]|^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/;
                    if (!regexNifCif.test(this.opportunity.CIF)) {
                        this.errors.CIF = "CIF/NIF no válido";
                        hasErrors = true;
                    }
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

                if (this.opportunity?.billingInfo?.postal) {
                    const postal = this.opportunity.billingInfo.postal;
                    if (postal.length !== 5) {
                        this.errors.billingInfo.postal =
                            "El zip tiene que tener 5 dígitos";
                        hasErrors = true;
                    } else if (isNaN(postal)) {
                        this.errors.billingInfo.postal =
                            this.getErrorMessage("onlyNumbers");
                        hasErrors = true;
                    }
                }
            } else {
                if (this.isContractOnly) {
                    this.wipeAccountFieldsForContractOnly();
                }
                if (
                    !this.opportunity.order.name ||
                    this.opportunity.order.name.trim() === ""
                ) {
                    this.errors.order.name = this.getErrorMessage("isEmpty");
                    hasErrors = true;
                }
            }

            if (this.opportunity.order.zip) {
                const zip = this.opportunity.order.zip;
                if (zip.length !== 5) {
                    this.errors.order.zip = "El zip tiene que tener 5 dígitos";
                    hasErrors = true;
                } else if (isNaN(zip)) {
                    this.errors.order.zip = this.getErrorMessage("onlyNumbers");
                    hasErrors = true;
                }
            }

            if (
                this.opportunity.order.CUPS &&
                this.opportunity.order.CUPS.length !== 20
            ) {
                this.errors.CUPS = "CUPS no válido";
                hasErrors = true;
            }

            if (!this.opportunity.order.marketer)
                this.opportunity.order.marketer = "Sin Comercializadora";
            if (!this.opportunity.order.fee)
                this.opportunity.order.fee = "Sin Tarifa";
            if (!this.opportunity.order.productType)
                this.opportunity.order.productType = "sp";

            if (hasErrors) {
                this.isUpdating = false;
                return;
            }

            if (!this.isContractOnly) {
                this.opportunity.order.name = "";
            }

            this.prepareEvChargerBudgetForSave();

            const data = new FormData();
            data.append("opportunity", JSON.stringify(this.opportunity));
            data.append(
                "userLogged",
                JSON.stringify(this.basicData.userLogged),
            );

            this.opportunity.customFields.forEach((field, idx) => {
                if (field.type === "image" && field.value instanceof File) {
                    data.append(`customFieldFile${idx}`, field.value);
                }
            });

            if (Array.isArray(this.opportunity.docs)) {
                this.opportunity.docs.forEach((doc, idx) => {
                    if (doc.fileValue instanceof File) {
                        data.append(`docFiles[${idx}]`, doc.fileValue);
                    }
                });
            }

            try {
                await axios.post("/api/opportunities/update", data);
                Swal.fire({
                    icon: "success",
                    title: "Oportunidad actualizada!",
                    text: "La oportunidad ha sido actualizada correctamente",
                    timerProgressBar: true,
                    timer: 1500,
                });
                this.isEditing = false;
                const updatedId = String(
                    this.opportunity?._id?.$oid || this.opportunity?._id || "",
                );
                if (updatedId) delete this.prefetchCache[updatedId];
                await this.fetchOpportunity();
            } catch (err) {
                console.error(err);
                if (err.response?.data?.cifError) {
                    this.errors.CIF = err.response.data.cifError;
                }
            } finally {
                this.isUpdating = false;
            }
        },
        async createAccFromOpp() {
            localStorage.setItem(
                "temporalyCreateAcc",
                JSON.stringify(this.opportunity),
            );

            if (this.isContractOnly) {
                localStorage.setItem("isContractOnly", "true");
                return this.$router.push({ name: "contracts" });
            }

            const userId = this.basicData.userLogged._id;
            const identifier = (this.opportunity.CIF || "").trim();

            if (identifier) {
                try {
                    const { data } = await axios.get(
                        `/api/accounts/checkByUserAndIdentifier/${userId}`,
                        { params: { identifier } },
                    );

                    if (data.exists && data.accountId) {
                        return this.$router.push({
                            name: "accountsDetails",
                            params: { id: data.accountId },
                        });
                    }
                } catch (err) {
                    console.error(err);
                }
            }

            this.$router.push({ name: "accountsRegister" });
        },
        restartOpp() {
            this.opportunity = JSON.parse(
                JSON.stringify(this.opportunityDefault),
            );
            this.isEditing = false;
        },
        getInitials(name) {
            if (name) {
                let nameSplited = name.split(/\s+/);

                let initials = nameSplited[0][0];

                if (nameSplited[1]) initials += nameSplited[1][0];

                return initials;
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

                default:
                    message = "Hay errores en el formulario";
                    break;
            }

            return message;
        },
        async fetchAccounts() {
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
        injectOrderFromOpportunity() {
            const tmp = localStorage.getItem("temporalyCreateAcc");
            if (!tmp) return;
            const opp = JSON.parse(tmp);
            this.addOrder();
            const idx = this.account.orders.length - 1;
            const order = this.account.orders[idx];
            Object.assign(order, {
                productType: opp.order.productType || "",
                marketer: opp.order.marketer || "",
                fee: opp.order.fee || "",
                product: opp.order.product || "",
                CUPS: opp.order.CUPS || "",
                direc: opp.order.direc || "",
                province: opp.order.province || "",
                town: opp.order.town || "",
                zip: opp.order.zip || "",
                name:
                    opp.name +
                    (opp.order.CUPS ? " - " + opp.order.CUPS.slice(-6) : ""),
            });
            this.selectedOrder = order;
            this.selectedOrderInd = idx;
            localStorage.removeItem("temporalyCreateAcc");
        },
        async fetchContacts() {
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
        fetchSelectValues() {
            axios
                .get(`/api/select/`)
                .then((res) => {
                    this.selectValues = res.data.selectValues;

                    //Si no se hay todavia un registro para el usuario se crea un array temporal
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
        addSelectType(data) {
            let type = data.type;
            let value = data.value;

            axios
                .post(`/api/select`, { type: type, value: value })
                .then((res) => {
                    //Añado al array para tenerlo en cliente
                    this.selectValues[type].push(value);

                    //Selecciono el valor y borro
                    if (type === "sector") this.opportunity.sector = value;
                    else if (type === "source") this.opportunity.source = value;
                    else this.opportunity.status = value;
                })
                .catch((err) => {
                    Swal.fire({
                        icon: "error",
                        title: "Ya existe",
                        text: "El elemento que estas intentando crear ya existe",
                    });
                });
        },
        delSelectType(data) {
            let type = data.type;
            let value = data.value;

            //Si se ha seleccionado uno y no la opción de eliminar en blanco
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
                        //Borro
                        axios
                            .delete(`/api/select`, {
                                params: {
                                    type: type,
                                    value: value,
                                },
                            })
                            .then((res) => {
                                //Saco el valor de cliente
                                let index =
                                    this.selectValues[type].indexOf(value);

                                if (index !== -1)
                                    this.selectValues[type].splice(index, 1);

                                //Si la cuenta tiene seleccionada esa opción la desmarco y borro de local
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
            let type = data.type;
            let value = data.value;

            this.opportunity[type] = value;
        },
        selectAccount(account) {
            this.opportunity.account = account._id;

            this.searchAccountText = "";

            this.errors.account = "";
        },
        changeProductType() {
            delete this.errors.productType;

            //Se borra toda la ramificación
            this.opportunity.order.product = "";
            this.opportunity.order.marketer = "";
            this.opportunity.order.fee = "";
            if (this.opportunity.order.extras)
                delete this.opportunity.order.extras;

            if (
                this.opportunity.order.productType !== "cl" &&
                this.opportunity.order.productType !== "cg" &&
                this.opportunity.order.productType !== "cd"
            )
                this.opportunity.order.CUPS = "";

            //Compruebo si es dual
            if (this.opportunity.order.productType === "cd") {
                this.opportunity.order.feeSecondary = "";
                this.opportunity.order.productSecondary = "";
                this.opportunity.order.CUPSSecondary = "";
                this.opportunity.order.consumptionSecondary = "";
            }
        },
        changeMarketer() {
            delete this.errors.marketer;

            //Se borra toda la ramificación
            this.opportunity.order.fee = "";
            this.opportunity.order.product = "";
            if (this.opportunity.order.extras)
                delete this.opportunity.order.extras;
        },
        changeFee() {
            delete this.errors.fee;

            //Se borra toda la ramificación
            this.opportunity.order.product = "";
            if (this.opportunity.order.extras)
                delete this.opportunity.order.extras;
        },
        changeFeeSecondary() {
            delete this.errors.feeSecondary;

            //Se borra toda la ramificación
            this.opportunity.order.productSecondary = "";
            if (this.opportunity.order.extras)
                delete this.opportunity.order.extras;
        },
        checkCUPS() {
            if (this.orderToModify.CUPS.length === 22)
                //&& this.orderToModify.CUPS.endsWith('0F') || this.orderToModify.CUPS.endsWith('RZ')
                this.orderToModify.CUPS = this.orderToModify.CUPS.slice(0, -2);
        },
        getProductData() {
            if (!this.marketers?.length) return null;

            const marketerInfo = this.marketers.find(
                (m) => m.name === this.opportunity?.order?.marketer,
            );

            if (!marketerInfo) return null;
            if (!marketerInfo.products) return null;

            const productsRelations = {
                cl: "electricity",
                cg: "gas",
                cd: "dual",
                ct: "telephony",
                sa: "alarm",
                a: "selfSupply",
            };

            const productTypeKey =
                productsRelations[this.opportunity?.order?.productType];

            if (!productTypeKey || !marketerInfo.products[productTypeKey])
                return null;

            let productInfo = null;

            if (productTypeKey === "dual")
                productInfo = marketerInfo.products[productTypeKey].find(
                    (p) =>
                        p.electricity === this.opportunity.order.product &&
                        p.gas === this.opportunity.order.productSecondary,
                );
            else
                productInfo = marketerInfo.products[productTypeKey].find(
                    (p) => p.name === this.opportunity.order.product,
                );

            if (!productInfo) return null;

            let feeOid = null;
            let feeInfo = null;

            if (
                this.opportunity.order.productType === "cl" ||
                this.opportunity.order.productType === "cg"
            ) {
                feeOid = marketerInfo.fees[productTypeKey]?.find(
                    (f) => f.name === this.opportunity.order.fee,
                )?.id?.$oid;
                feeInfo = productInfo.fees?.find((f) => f.id?.$oid === feeOid);
            } else {
                if (this.opportunity.order.productType === "cd")
                    feeInfo = productInfo.fees?.find(
                        (f) =>
                            f.electricity.name === this.opportunity.order.fee &&
                            f.gas.name === this.opportunity.order.feeSecondary,
                    );
                else if (this.opportunity.order.productType === "sa")
                    feeInfo = productInfo;
                else
                    feeInfo = productInfo.fees?.find(
                        (f) => f.name === this.opportunity.order.fee,
                    );
            }

            if (!feeInfo) return null;

            return { marketerInfo, productInfo, feeInfo };
        },
        toggleSelectExtraProduct(extra) {
            if (this.isInputsDisabled) return;

            //Si no existe lo creo
            if (!this.opportunity.order.extras)
                this.opportunity.order.extras = [];

            let extraId = extra.id?.$oid || extra.id;

            let index = this.opportunity.order.extras.indexOf(extraId);

            if (index === -1) this.opportunity.order.extras.push(extraId);
            else this.opportunity.order.extras.splice(index, 1);
        },
        actionLink(route) {
            this.$router.push(route);
        },
    },
    computed: {
        currentNavIndex() {
            const id = String(this.$route.params.id);
            return this.opportunityNavIds.findIndex((navId) => navId === id);
        },
        prevOpportunityId() {
            return this.currentNavIndex > 0
                ? this.opportunityNavIds[this.currentNavIndex - 1]
                : null;
        },
        nextOpportunityId() {
            return this.currentNavIndex >= 0 &&
                this.currentNavIndex < this.opportunityNavIds.length - 1
                ? this.opportunityNavIds[this.currentNavIndex + 1]
                : null;
        },
        filteredHistory() {
            if (!this.opportunity?.messages) return [];

            const resolveUser = (id) => {
                if (!id)
                    return {
                        id: null,
                        name: "Desconocido",
                        profileImage: "default.jpg",
                    };

                if (id === this.basicData.userLogged._id) {
                    return {
                        id,
                        name: `${this.basicData.userLogged.firstName} ${this.basicData.userLogged.lastName}`,
                        profileImage:
                            this.basicData.userLogged.profileImage ??
                            "default.jpg",
                    };
                }

                const u = this.users.find((u) => u._id === id);
                return u
                    ? {
                          id: u._id,
                          name: `${u.firstName} ${u.lastName}`,
                          profileImage: u.profileImage ?? "default.jpg",
                      }
                    : {
                          id: null,
                          name: "Desconocido",
                          profileImage: "default.jpg",
                      };
            };

            return [...this.opportunity.messages]
                .map((msg) => ({
                    type: "message",
                    date: msg.date,
                    creator: resolveUser(msg.creator),
                    data: { message: msg.message },
                }))
                .sort((a, b) => new Date(a.date) - new Date(b.date));
        },
        accountSelected() {
            if (this.accounts && this.opportunity.account)
                return this.accounts.find((account) => {
                    return account._id === this.opportunity.account;
                });
        },
        contactRelated() {
            if (!this.contacts || !this.opportunity.contact) return null;

            return this.contacts.find(
                (contact) => contact._id === this.opportunity.contact.value,
            );
        },
        selectedProductTypeData() {
            if (!this.opportunity?.order?.productType) return null;
            return (
                this.productTypesWithoutSp.find(
                    (pt) => pt.code === this.opportunity.order.productType,
                ) || null
            );
        },
        selectedProductToSee() {
            return this.selectedProductTypeData?.productToSee || "";
        },
        isElectricCarCharger() {
            return (
                this.basicData?.userSubdomain?._id === "65cb57489c2c285441086a43" &&
                this.selectedProductToSee === "electricCar" &&
                ["Cargador Electrico 100%", "Cargador Hibrido"].includes(
                    this.opportunity?.order?.product,
                )
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

            // Importante: estos importes salen de los inputs.
            // No se vuelven a coger de la tabla por metros para no pisar cambios manuales.
            const laborSubtotal = this.safeNumber(budget.laborPrice);
            const certificateSubtotal = this.safeNumber(
                budget.certificatePrice,
            );

            const round = (v) => Math.round(v * 100) / 100;

            const cableSubtotal = round(
                this.getEvBillableCableMeters(budget.cableMeters) *
                    this.safeNumber(
                        budget.cablePricePerMeter,
                        this.evChargerDefaultCosts.cablePricePerMeter,
                    ) *
                    1.1,
            );

            const modulationCableSubtotal = round(
                Math.min(this.safeNumber(budget.modulationCableMeters), 70) *
                    this.safeNumber(
                        budget.modulationCablePricePerMeter,
                        this.evChargerDefaultCosts.modulationCablePricePerMeter,
                    ) *
                    1.1,
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
        filteredAccounts() {
            let accounts = [];

            if (this.searchAccountText === "") {
                accounts = this.accounts;
            } else {
                this.accounts.filter((account) => {
                    let AccountFiltered = account.name
                        .replace(" ", "")
                        .toLowerCase()
                        .normalize("NFC");

                    if (
                        AccountFiltered.includes(
                            this.searchAccountText
                                .replace(" ", "")
                                .toLowerCase()
                                .normalize("NFC"),
                        )
                    )
                        accounts.push(account);
                });
            }

            //Ordeno
            accounts = accounts.sort((a, b) => a.name.localeCompare(b.name));

            return accounts;
        },
        filteredMarketers() {
            if (
                !this.marketers.length > 0 ||
                !this.opportunity.order.productType
            )
                return [];

            let marketers = this.marketers
                .filter((marketer) => {
                    return (
                        (this.opportunity.order.productType === "cl" &&
                            marketer["products"]["electricity"].length > 0) ||
                        (this.opportunity.order.productType === "cg" &&
                            marketer["products"]["gas"].length > 0) ||
                        (this.opportunity.order.productType === "cd" &&
                            (marketer["products"]["dual"]?.length ?? 0) > 0) ||
                        (this.opportunity.order.productType === "ct" &&
                            (marketer["products"]["telephony"]?.length ?? 0) >
                                0) ||
                        (this.opportunity.order.productType === "sa" &&
                            (marketer["products"]["alarm"]?.length ?? 0) > 0) ||
                        (this.opportunity.order.productType === "a" &&
                            (marketer["products"]["selfSupply"]?.length ?? 0) >
                                0)
                    );
                    //Filtrar en caso de no ser tramitado con Zoco
                })
                .filter((marketer) => {
                    const isSubdomainUser =
                        this.basicData.userLogged.label ===
                        "Usuario subdominio";

                    //En caso de no ser asignado a Zoco, mostrar aquellos que tenga el usuario si no es subdominio
                    if (!isSubdomainUser)
                        return this.basicData.userLogged.marketers.includes(
                            marketer._id,
                        );

                    // En caso de ser subdominio mostrar todos
                    return true;
                });

            marketers = marketers.sort((a, b) => {
                if (a.name < b.name) return -1;
                if (a.name > b.name) return 1;
                return 0;
            });

            return marketers;
        },
        filteredFees() {
            if (
                !this.marketers.length > 0 ||
                !this.opportunity.order.productType ||
                !this.opportunity.order.marketer
            )
                return [];

            let marketer = this.marketers.find((marketer) => {
                return marketer.name === this.opportunity.order.marketer;
            });

            //Si no encontró la comercializadora, devuelve array vacío
            if (!marketer) return [];

            //Saco el nombre en bbdd del tipo de producto
            let productType = this.$storage.PRODUCT_TYPES.find(
                (pt) => pt.code === this.opportunity.order.productType,
            ).inDatabase;

            //Si es contrato dual tengo que ver las tarifas de las que haya una relación creada
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
            } else {
                if (productType === "electricity" || productType === "gas")
                    return marketer["fees"][productType];
                else
                    return this.$storage.FEES[productType]
                        .filter((fee) => {
                            return marketer["products"][productType].some(
                                (product) => {
                                    return product.fees.some((feeProduct) => {
                                        const name1 = feeProduct.name
                                            ?.trim()
                                            .toLowerCase();
                                        const name2 = fee?.trim().toLowerCase();

                                        return (
                                            name1 === name2 &&
                                            !feeProduct.archived
                                        );
                                    });
                                },
                            );
                        })
                        .map((fee) => ({ name: fee }));
            }
        },
        filteredMarketerProducts() {
            if (
                !this.marketers.length > 0 ||
                !this.opportunity.order.productType ||
                !this.opportunity.order.marketer ||
                (!this.opportunity.order.fee &&
                    !this.opportunity.order.productType === "sa")
            )
                return [];

            let marketer = this.marketers.find((marketer) => {
                return marketer.name === this.opportunity.order.marketer;
            });

            //Si no encontró la comercializadora, devuelve array vacío
            if (!marketer) return [];

            let productType = this.$storage.PRODUCT_TYPES.find(
                (pt) => pt.code === this.opportunity.order.productType,
            ).inDatabase;

            if (this.opportunity.order.productType === "cd") {
                //Saco los productos de luz que tengan la tarifa seleccionada
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
            } else {
                if (productType === "electricity" || productType === "gas")
                    return marketer.products[productType].filter((product) => {
                        return product.fees.find((fee) => {
                            if (
                                fee?.archived &&
                                product.name === this.opportunity.order.product
                            )
                                this.productArchived = true;

                            //No incluir los fees que no cumplen el rango de potencia contratado

                            if (
                                (!!fee.minPower || !!fee.maxPower) &&
                                !!this.opportunity.order.hiredPotency
                            ) {
                                // Si existe un mínimo y no se cumple → excluir
                                if (
                                    !!fee.minPower &&
                                    this.opportunity.order.hiredPotency <
                                        fee.minPower
                                )
                                    return false;

                                // Si existe un máximo y no se cumple → excluir
                                if (
                                    !!fee.maxPower &&
                                    this.opportunity.order.hiredPotency >
                                        fee.maxPower
                                )
                                    return false;
                            }
                            if (
                                (!!fee.minConsumption ||
                                    !!fee.maxConsumption) &&
                                !!this.opportunity.order.consumption
                            ) {
                                // Si existe un mínimo y no se cumple → excluir
                                if (
                                    !!fee.minConsumption &&
                                    this.opportunity.order.consumption <
                                        fee.minConsumption
                                )
                                    return false;

                                // Si existe un máximo y no se cumple → excluir
                                if (
                                    !!fee.maxConsumption &&
                                    this.opportunity.order.consumption >
                                        fee.maxConsumption
                                )
                                    return false;
                            }
                            return (
                                fee.id.$oid ===
                                    marketer.fees[
                                        this.opportunity.order.productType ===
                                            "cl" ||
                                        this.opportunity.order.productType ===
                                            "cd"
                                            ? "electricity"
                                            : "gas"
                                    ].find(
                                        (feeNow) =>
                                            feeNow.name ===
                                            this.opportunity.order.fee,
                                    ).id.$oid &&
                                (!fee?.archived ||
                                    product.name ===
                                        this.opportunity.order.product)
                            );
                        });
                    });
                else if (productType === "alarm")
                    return marketer.products[productType].filter((product) => {
                        if (
                            product.archived &&
                            product.name === this.opportunity.order.product
                        )
                            this.productArchived = true;

                        return (
                            !product.archived ||
                            product.name === this.opportunity.order.product
                        );
                    });
                else
                    return marketer.products[productType].filter((product) => {
                        return product.fees.find((fee) => {
                            if (
                                fee?.archived &&
                                product.name === this.opportunity.order.product
                            )
                                this.productArchived = true;

                            //Busco si el producto tiene la tarifa seleccionada
                            let feeActived = product.fees.find(
                                (feeNow) =>
                                    feeNow.name === this.opportunity.order.fee,
                            );

                            return (
                                feeActived &&
                                (!fee?.archived ||
                                    product.name ===
                                        this.opportunity.order.product)
                            );
                        });
                    });
            }
        },
        filteredMarketerProductsSecondary() {
            if (
                !this.marketers.length > 0 ||
                !this.opportunity.order.productType ||
                !this.opportunity.order.marketer ||
                !this.opportunity.order.feeSecondary
            )
                return [];

            let marketer = this.marketers.find((marketer) => {
                return marketer.name === this.opportunity.order.marketer;
            });

            //Si no encontró la comercializadora, devuelve array vacío
            if (!marketer) return [];

            //Saco los productos de gas
            return [
                ...new Set(
                    marketer.products.dual
                        .filter(
                            (dual) =>
                                dual.electricity ===
                                this.opportunity.order.product,
                        )
                        .flatMap((dual) => dual.fees)
                        .map((fee) => fee.gas?.name),
                ),
            ].map((name) => ({ name }));
        },
        isReadOnly() {
            if (!this.basicData.userLogged) return true;
            else this.basicData.userLogged;
            return this.basicData.userLogged.permissions.includes("READONLY");
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
            )
                return [];

            //Miro si el producto tiene extras aplicables
            let data = this.getProductData();

            // Si no encuentra producto o fee, salimos
            if (!data) return [];

            let extras = [];
            let info = null;

            //Saco la info del fee/producto seleccionado
            if (this.opportunity.order.productType === "sa")
                info = data.productInfo;
            else info = data.feeInfo;

            //Saco los extras del fee/producto seleccionado
            extras = info.extras;

            //Si no tiene ninguno
            if (
                (!extras || extras.length === 0) &&
                (!data.marketerInfo.extras ||
                    data.marketerInfo.extras.length === 0)
            )
                return [];

            let searchText = (this.extraSearchText || "").toLowerCase().trim();

            //Saco los extras ( filtro si el extra es residencial o pyme y el producto tiene alguna de estas o si es pap y esta habilitado para el producto )
            return data.marketerInfo.extras.filter((extra) => {
                //Si no tiene habilitado el mismo tipo que el producto seleccionado salimos
                if (
                    !extra.productTypes.includes(
                        this.opportunity.order.productType,
                    )
                )
                    return false;

                let valid = false;

                //pyme
                if (info.type.pyme && extra.to.pyme) valid = true;

                //residencial
                if (info.type.residencial && extra.to.residential) valid = true;

                //Producto a producto
                if (
                    extra.to.product &&
                    extras &&
                    extras.includes(extra.id.$oid)
                )
                    valid = true;

                if (!valid) return false;

                //Busco por nombre
                if (searchText)
                    return extra.name?.toLowerCase().includes(searchText);

                return true;
            });
        },
        filteredFeesSecondary() {
            if (
                !this.marketers.length > 0 ||
                !this.opportunity.order.productType ||
                !this.opportunity.order.marketer ||
                !this.opportunity.order.product
            )
                return [];

            let marketer = this.marketers.find((marketer) => {
                return marketer.name === this.opportunity.order.marketer;
            });

            //Si no encontró la comercializadora, devuelve array vacío
            if (!marketer) return [];

            //Tengo que sacar las tarifas de los productos relacionados al producto de luz seleccionado
            return [
                ...new Set(
                    marketer.products.dual
                        .filter(
                            (dual) =>
                                dual.electricity ===
                                this.opportunity.order.product,
                        )
                        .flatMap((dual) => dual.fees)
                        .map((fee) => fee.gas?.name),
                ),
            ].map((name) => ({ name }));
        },
        isInputsDisabled() {
            if (!this.basicData.userLogged) return true;
            else this.basicData.userLogged;
            return (
                this.basicData.userLogged.permissions.includes("READONLY") ||
                !this.isEditing
            );
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
.disabledToggle {
    pointer-events: none;
    opacity: 0.6;
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
