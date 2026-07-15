<template>
    <div v-if="marketer" @click="hideCustomSelects">
        <!--Contenedor padre para distribución-->
        <form v-if="product.fees[feeSelect]" class="form register-pos">

            <!-- Nombre de la Comercializadora -->
            <div class="d-flex mb-10 align-center f-wrap">
                <img
                    :src="'/assets/marketers_logo/' + marketer.logo"
                    alt="Logo"
                    class="h-50-px-max w-100-px-max contain-img"
                />

                <div
                    class="d-flex align-center text ml-20"
                    data-size="20"
                    data-weight="700"
                    v-on:click.stop=""
                >
                    <p>{{ marketer.name }} -</p>

                    <i class="far text ml-10 mr-5" :class="this.type === 'electricity'? 'fa-bolt': 'fa-fire-flame-simple'"></i>
                    <p class="mx-5">{{ product.name }}</p>
                    <!--<div v-else class="form-group">
                        <div class="input-group">
                            <input type="text" v-model="product.name" />
                        </div>
                    </div>-->
                </div>
            </div>

            <!-- Tarifas -->
            <div class="d-flex justify-between align-center">
                <div class="w-100 d-flex justify-between align-center">
                    <div class="d-flex justify-between align-center">
                        <div
                            class="custom-select"
                            :class="{ seeing: selectFeesActive }"
                        >
                            <div
                                class="text mr-20"
                                data-weight="400"
                                v-on:click.stop="selectFees()"
                            >
                                Tarifa
                                <i
                                    class="far ml-5"
                                    :class="[
                                        selectFeesActive
                                            ? 'fa-chevron-up'
                                            : 'fa-chevron-down',
                                    ]"
                                ></i>
                            </div>

                            <!-- Despegable de Tarifas -->
                            <div v-if="selectFeesActive === true">
                                <div class="select-content left">
                                    <div v-for="(fee, i) in product.fees">
                                        <div class="d-flex justify-between">
                                            <div v-on:click.stop="changeFee(i)">
                                                {{ getFee(fee.id) }}
                                            </div>
                                            <div
                                                v-if="isEdit"
                                                @click="deleteProductFee(fee)"
                                            >
                                                <i
                                                    class="far fa-trash"
                                                    data-color="rojo"
                                                ></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!--botón añadir tarifa-->
                                    <div
                                        class="custom-button d-flex justify-between mt-10"
                                        data-size="small"
                                        data-bg="amarillo"
                                        v-if="isEdit"
                                        v-on:click.stop="
                                            isShowingFeeModal = true
                                        "
                                    >
                                        Añadir Tarifa
                                        <i class="fa-solid fa-plus my-auto"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarifa Activa -->
                        <div class="text">
                            {{ getFee(product.fees[feeSelect].id) }}
                        </div>
                    </div>

                    <!--Modal añadir tarifa-->
                    <Teleport to=".boxBody" v-if="isShowingFeeModal">
                        <div class="floating-box z-100">
                            <div
                                class="register-pos small w-50 h-auto h-98-max round"
                                data-round="20"
                                data-border-color="principal"
                            >
                                <div class="select-content filterMarketers">
                                    <template v-if="!selectedFeeToAdd">
                                        <div
                                            class="orderMarkers"
                                            v-if="filteredFeesToAdd.length > 0"
                                        >
                                            <template
                                                v-for="fee in filteredFeesToAdd"
                                            >
                                                <div
                                                    class="cell pointer"
                                                    v-on:click.stop="
                                                        selectedFeeToAdd = fee
                                                    "
                                                >
                                                    <p data-color="principal">
                                                        {{ fee.name }}
                                                    </p>
                                                </div>
                                            </template>
                                        </div>

                                        <div
                                            class="text-center opacity-5"
                                            v-else
                                        >
                                            ¡No hay más tarifas por añadir!
                                        </div>
                                    </template>

                                    <div class="form" v-else>
                                        <div class="form-group">
                                            <p>Tipo de comisión</p>
                                            <div class="input-group">
                                                <div
                                                    class="w-100 d-grid grid-justify-center"
                                                    data-column="2"
                                                >
                                                    <div>
                                                        <label
                                                            class="mr-10"
                                                            for="basic"
                                                        >Básico</label
                                                        >
                                                        <input
                                                            type="radio"
                                                            name="breckDowns"
                                                            id="basic"
                                                            value="basic"
                                                            v-model="
                                                                newFeeOptions.breakdownType
                                                            "
                                                        />
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="mr-10"
                                                            for="breakdown"
                                                        >Con
                                                            Intervalos</label
                                                        >
                                                        <input
                                                            type="radio"
                                                            name="breakDowns"
                                                            id="breakdown"
                                                            value="breakdown"
                                                            v-model="
                                                                newFeeOptions.breakdownType
                                                            "
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <span
                                                v-if="
                                                    errors.breakDownsError ==
                                                    true
                                                "
                                                data-color="rojo"
                                            >Tienes que seleccionar una
                                                opción</span
                                            >
                                            <!--<div class="input-group" :class="{wrong : errors.typeComError}">
                                                <div class="w-100 d-grid grid-justify-center" data-column="3">
                                                    <div>
                                                        <label class="mr-10" for="c">Consumo</label>
                                                        <input type="radio" name="comisionType" id="c" value="c" v-model="newFeeOptions.commissionType">
                                                    </div>
                                                    <div>
                                                        <label class="mr-10" for="p">Potencia</label>
                                                        <input type="radio" name="comisionType" id="p" value="p" v-model="newFeeOptions.commissionType">
                                                    </div>
                                                    <div>
                                                        <label class="mr-10" for="cyp">Consumo y Potencia</label>
                                                        <input type="radio" name="comisionType" id="cyp" value="cyp" v-model="newFeeOptions.commissionType">
                                                    </div>
                                                </div>
                                            </div>
                                            <span v-if="(errors.typeComError == true)" data-color="rojo">Tienes que seleccionar una opción</span>-->
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="separator"></div>

                                <div class="d-flex justify-end" data-gap="20">
                                    <div
                                        class="custom-button"
                                        data-size="regular"
                                        data-bg="rojo"
                                        v-on:click.stop="closeFeeModal"
                                    >
                                        Cancelar
                                    </div>
                                    <div
                                        v-if="selectedFeeToAdd"
                                        class="custom-button"
                                        data-size="regular"
                                        v-on:click.stop="addProductFee"
                                    >
                                        Crear
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Teleport>

                    <!-- Tipo de Producto -->
                    <div class="mx-20 d-flex form-group">
                        <div class="text my-auto">Tipo de producto:</div>
                        <template v-if="isEdit">
                            <div class="input-group mx-10">
                                <label class="mx-5" for="r"
                                ><i
                                    class="far fa-house mr-5"
                                />Residencial</label
                                >
                                <input
                                    type="checkbox"
                                    id="r"
                                    v-model="
                                        product.fees[feeSelect].type.residencial
                                    "
                                />
                            </div>
                            <div class="input-group mx-10">
                                <label class="mx-5" for="p"
                                ><i
                                    class="far fa-building mr-5"
                                />PYME</label
                                >
                                <input
                                    type="checkbox"
                                    id="p"
                                    v-model="product.fees[feeSelect].type.pyme"
                                />
                            </div>
                        </template>

                        <template v-else>
                            <div
                                class="text mx-5 d-flex align-center"
                                v-if="
                                    product.fees[feeSelect].type.residencial ===
                                        true &&
                                    product.fees[feeSelect].type.pyme === true
                                "
                            >
                                <i
                                    class="far fa-house-building mr-5"
                                />Residencial y PYME
                            </div>
                            <div
                                class="text mx-5 d-flex align-center"
                                v-else-if="
                                    product.fees[feeSelect].type.residencial ===
                                    true
                                "
                            >
                                <i class="far fa-house mr-5" />Residencial
                            </div>
                            <div
                                class="text mx-5 d-flex align-center"
                                v-else-if="
                                    product.fees[feeSelect].type.pyme === true
                                "
                            >
                                <i class="far fa-building mr-5" />PYME
                            </div>
                        </template>
                    </div>

                    <div class="d-flex" data-gap="10">
                        <template v-if="type === 'electricity'">
                            <div
                                v-if="
                                    product.fees[feeSelect]?.surplus &&
                                    product.fees[feeSelect].surplus !==
                                        'none' &&
                                    !isEdit
                                "
                                class="d-flex form-group"
                            >
                                <div class="d-flex mx-20">
                                    <div class="text my-auto">Excedentes:</div>
                                    <div class="text mx-5 d-flex align-center">
                                        <i class="far fa-solar-panel mr-5" />{{
                                            product.fees[feeSelect].surplus ===
                                            "optional"
                                                ? "Opcional"
                                                : "Obligatorio"
                                        }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="isEdit" class="d-flex form-group">
                                <div class="input-group mx-10">
                                    <label class="mx-5" for="surplus"
                                    ><i class="far fa-solar-panel mr-5"
                                    /></label>
                                    <select
                                        v-model="
                                            product.fees[feeSelect].surplus
                                        "
                                    >
                                        <option :value="null" disabled>
                                            Excedentes
                                        </option>
                                        <option value="none">No tiene</option>
                                        <option value="optional">
                                            Opcional
                                        </option>
                                        <option value="required">
                                            Obligatorio
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </template>
                        <div
                            v-if="
                                    product.fees[feeSelect]?.priceType &&
                                    !isEdit
                                "
                            class="d-flex form-group"
                        >
                            <div class="d-flex mx-20">
                                <div class="text my-auto">
                                    Tipo de precio:
                                </div>
                                <div class="text mx-5 d-flex align-center">
                                    <i
                                        :class="[
                                                product.fees[feeSelect]
                                                    .priceType === 'fixed'
                                                    ? 'fa-lock'
                                                    : product.fees[feeSelect]
                                                          .type === 'variable'
                                                    ? 'fa-chart-mixed'
                                                    : 'fa-chart-line',
                                                'far mr-5',
                                            ]"
                                    />
                                    {{
                                        product.fees[feeSelect]
                                            .priceType === "fixed"
                                            ? "Fijo"
                                            : product.fees[feeSelect]
                                                .priceType === "variable"
                                                ? "Fijo-variable"
                                                : "Indexado"
                                    }}
                                </div>
                            </div>
                        </div>
                        <div v-if="isEdit" class="d-flex form-group">
                            <div class="input-group mx-10">
                                <label class="mx-5" for="indexed">
                                    <i
                                        :class="[
                                                product.fees[feeSelect]
                                                    .priceType === 'fixed'
                                                    ? 'fa-lock'
                                                    : product.fees[feeSelect]
                                                          .priceType ===
                                                      'variable'
                                                    ? 'fa-chart-mixed'
                                                    : 'fa-chart-line',
                                                'far mr-5',
                                            ]"
                                    />
                                </label>
                                <select
                                    v-model="
                                            product.fees[feeSelect].priceType
                                        "
                                    @change="togglePriceType"
                                >
                                    <option :value="null" disabled>
                                        Tipo precio
                                    </option>
                                    <option value="fixed">Fijo</option>
                                    <option value="variable">
                                        Fijo-Variable
                                    </option>
                                    <option value="indexed">
                                        Indexado
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div
                            v-if="product.fees[feeSelect]?.archived && !isEdit"
                            class="d-flex form-group"
                        >
                            <div class="input-group mx-10">
                                <label class="mx-5"
                                ><i
                                    class="far fa-box-archive mr-5"
                                />Archivado</label
                                >
                            </div>
                        </div>
                        <div v-if="isEdit" class="d-flex form-group">
                            <div class="input-group mx-10">
                                <label class="mx-5" for="archived"
                                ><i
                                    class="far fa-box-archive mr-5"
                                />Archivado</label
                                >
                                <input
                                    type="checkbox"
                                    id="archived"
                                    v-model="product.fees[feeSelect].archived"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div v-if="canManage('products.edit') && canEdit">
                        <div
                            class="custom-button"
                            data-size="regular"
                            v-if="!isEdit"
                            v-on:click.stop="changeEdit()"
                        >
                            Editar
                        </div>
                        <div
                            class="d-flex justify-between"
                            data-gap="10"
                            v-else-if="isEdit"
                        >
                            <div
                                class="custom-button"
                                data-size="regular"
                                data-bg="rojo"
                                v-on:click.stop="changeEdit(true)"
                            >
                                Cancelar
                            </div>
                            <div
                                class="custom-button"
                                data-size="regular"
                                v-on:click.stop="saveProd()"
                            >
                                Guardar
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="separator"></div>

            <template v-if="isEdit && type === 'electricity'">

                <div class="text ml-20 pb-10" data-size="20" data-weight="400">
                    Potencias y consumo
                </div>
                <div class="d-flex">
                    <p class="mr-10">Potencia Mínima (kW): </p>
                    <div class="form-group w-200-px-max mr-20">
                        <div class="input-group">
                            <input
                                type="text"
                                v-model="product.fees[this.feeSelect].minPower"
                                @input="validateNumeric('minPower')"
                            />
                        </div>
                    </div>
                    <p class="mr-10">Potencia Máxima (kW): </p>
                    <div class="form-group w-200-px-max mr-20">
                        <div class="input-group">
                            <input
                                type="text"
                                v-model="product.fees[this.feeSelect].maxPower"
                                @input="validateNumeric('maxPower')"
                            />
                        </div>
                    </div>
                    <p class="mr-10">Consumo Mínimo (kWh): </p>
                    <div class="form-group w-200-px-max mr-20">
                        <div class="input-group">
                            <input
                                type="text"
                                v-model="product.fees[this.feeSelect].minConsumption"
                                @input="validateNumeric('minConsumption')"
                            />
                        </div>
                    </div>
                    <p class="mr-10">Consumo Máximo (kWh): </p>
                    <div class="form-group w-200-px-max mr-20">
                        <div class="input-group">
                            <input
                                type="text"
                                v-model="product.fees[this.feeSelect].maxConsumption"
                                @input="validateNumeric('maxConsumption')"
                            />
                        </div>
                    </div>
                </div>
            </template>

            <div v-if="isEdit" class="separator"></div>

            <!-- Comentarios -->
            <template v-if="product.fees[feeSelect].comment || isEdit">
                <div class="text ml-20 pb-10" data-size="20" data-weight="400">
                    Observaciones
                </div>
                <p class="break-spaces" data-size="12" v-if="!isEdit">
                    {{ product.fees[this.feeSelect].comment }}
                </p>
                <div class="form-group" v-else>
                    <div class="input-group">
                        <textarea
                            v-model="product.fees[this.feeSelect].comment"
                        ></textarea>
                    </div>
                </div>

                <div class="separator"></div>
            </template>

            <!-- Precios -->
            <div id="prices" v-if="type === 'electricity'">
                <div class="d-flex justify-between align-center form-group">
                    <div class="text ml-20 pb-10" data-size="20" data-weight="400">
                        Precios
                    </div>
                    <div v-if="product.fees[feeSelect].priceType === 'indexed'" class="d-flex align-center" data-gap="10">
                        <div class="custom-button" v-if="isEdit" data-size="medium" data-bg="principal" @click="viewPriceHistoryCreateModal = true">
                            <i class="far fa-plus"></i>
                        </div>
                        <div class="input-group">
                            <select v-model="priceHistorySelected">
                                <option value="">Precio medio</option>
                                <option v-for="priceHistory of priceHistoryData" :value="priceHistory">{{formatpriceHistoryDate(priceHistory)}}</option>
                            </select>
                        </div>
                        <i v-if="isEdit && priceHistorySelected" class="far fa-trash pointer" data-color="rojo" @click="deletePriceHistory"/>
                    </div>
                </div>
                <div class="mobile-item">
                    <div class="d-flex justify-between f-wrap">
                        <!-- Potencia -->
                        <div class="w-100">
                            <!-- Tabla Potencias -->
                            <div>
                                <div class="d-flex justify-center align-end">
                                    Precio Potencia (€ kW día)
                                </div>
                                <div
                                    class="d-grid pl-50"
                                    data-gap="5"
                                    data-column="6"
                                >
                                    <div
                                        class="form-group ellipsis"
                                        v-for="(pPower, period) in product.fees[
                                            this.feeSelect
                                        ].prices.power"
                                    >
                                        <div data-size="15">{{ period }}</div>
                                        <div
                                            v-if="!isEdit"
                                            class="text ellipsis"
                                            data-size="12"
                                            data-weight="400"
                                        >
                                            {{
                                                product.fees[this.feeSelect]
                                                    .prices.power[period] &&
                                                Number(
                                                    product.fees[this.feeSelect]
                                                        .prices.power[period]
                                                ) !== 0
                                                    ? product.fees[
                                                        this.feeSelect
                                                        ].prices.power[period]
                                                    : ""
                                            }}
                                        </div>
                                        <div class="input-group" v-else>
                                            <input
                                                type="text"
                                                :disabled="!isEdit"
                                                v-model="
                                                    product.fees[this.feeSelect]
                                                        .prices.power[period]
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="separator"></div>
                            <!-- Grafica potencia -->
                            <div v-if="!isEdit">
                                <chart-simplebars-component
                                    :series="this.pricesPower"
                                />
                            </div>
                        </div>

                        <!-- Consumo -->
                        <div class="w-100">
                            <div>
                                <div class="d-flex justify-center">
                                    Precios Consumo (€ kWh)
                                </div>
                                <div
                                    class="d-grid pl-50"
                                    data-gap="5"
                                    data-column="6"
                                >
                                    <div
                                        class="form-group ellipsis"
                                        v-for="(pConsume, period) in product
                                            .fees[this.feeSelect].prices
                                            .consume"
                                    >
                                        <div data-size="15">{{ period }}</div>
                                        <div
                                            v-if="!isEdit"
                                            class="text ellipsis"
                                            data-size="12"
                                            data-weight="400"
                                        >
                                            {{
                                                product.fees[this.feeSelect]
                                                    .prices.consume[period] &&
                                                Number(
                                                    product.fees[this.feeSelect]
                                                        .prices.consume[period]
                                                ) !== 0
                                                    ? product.fees[
                                                        this.feeSelect
                                                        ].prices.consume[period]
                                                    : ""
                                            }}
                                        </div>
                                        <div class="input-group" v-else>
                                            <input
                                                type="text"
                                                :disabled="!isEdit"
                                                v-model="
                                                    product.fees[this.feeSelect]
                                                        .prices.consume[period]
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="separator"></div>
                            <!-- Grafica Consumo -->
                            <div v-if="!isEdit">
                                <chart-simplebars-component
                                    :series="this.pricesConsume"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="desktop-item">
                    <div class="d-flex justify-between f-wrap">
                        <!-- Potencia -->
                        <div class="w-48 w-560-px-min">
                            <!-- Tabla Potencias -->
                            <div>
                                <div class="d-flex justify-center align-end">
                                    Precio Potencia (€ kW día)
                                </div>
                                <div
                                    class="d-grid pl-50"
                                    data-gap="5"
                                    data-column="6"
                                >
                                    <div
                                        class="form-group"
                                        v-for="(value, period) in currentPower"
                                    >
                                        <div>{{ period }}</div>
                                        <div
                                            v-if="!isEdit"
                                            class="text ellipsis pl-10"
                                            data-weight="400"
                                        >
                                            {{ formatNumber(value, 6) || ""}}
                                        </div>
                                        <div class="input-group" v-else>
                                            <input
                                                type="text"
                                                :disabled="!isEdit"
                                                v-model="currentPower[period]"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div v-if="product.fees[this.feeSelect].priceType === 'variable' || product.fees[this.feeSelect].priceType === 'indexed'"
                                     class="d-flex justify-center mt-20 form-group" data-gap="15"
                                >
                                    <template v-if="!isEdit">
                                        <div class="text">{{product.fees[this.feeSelect]?.fees?.power?.unique ? 'Fee único' : 'Fee por período'}}:</div>
                                        <div class="text">
                                            Desde {{Number(product.fees[this.feeSelect]?.fees?.power?.minimum) || 0}} hasta {{Number(product.fees[this.feeSelect]?.fees?.power?.maximum) || 0}} € / kW mes
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="d-flex align-center">
                                            <div class="custom-checkbox mr-5" @click.stop="toggleFeeUnique('power')"
                                                 :class="{ selected: product.fees[this.feeSelect]?.fees?.power?.unique }"
                                            />

                                            <div class="text">Fee único (€ / kW mes)</div>
                                        </div>
                                        <div class="input-group">
                                            <input type="text" :disabled="!isEdit" class="w-60-px" placeholder="Mínimo"
                                                   v-model="product.fees[this.feeSelect].fees.power.minimum"
                                            />
                                        </div>
                                        -
                                        <div class="input-group">
                                            <input type="text" :disabled="!isEdit" class="w-60-px" placeholder="Máximo"
                                                   v-model="product.fees[this.feeSelect].fees.power.maximum"
                                            />
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="separator"></div>
                            <!-- Grafica potencia -->
                            <div v-if="!isEdit">
                                <chart-simplebars-component
                                    :series="this.pricesPower"
                                />
                            </div>
                        </div>

                        <div
                            class="separator m-20"
                            data-position="vertical"
                        ></div>

                        <!-- Consumo -->
                        <div class="w-48 w-560-px-min">
                            <div>
                                <div class="d-flex justify-center">
                                    Precios Consumo (€ kWh)
                                </div>
                                <div
                                    class="d-grid pl-50"
                                    data-gap="5"
                                    data-column="6"
                                >
                                    <div
                                        class="form-group"
                                        v-for="(value, period) in currentConsume"
                                    >
                                        <div>{{ period }}</div>
                                        <div
                                            v-if="!isEdit"
                                            class="text ellipsis pl-10"
                                            data-weight="400"
                                        >
                                            {{ formatNumber(value, 6) || "" }}
                                        </div>
                                        <div class="input-group" v-else>
                                            <input
                                                type="text"
                                                :disabled="!isEdit"
                                                v-model="currentConsume[period]"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div v-if="product.fees[this.feeSelect].priceType === 'variable' || product.fees[this.feeSelect].priceType === 'indexed'"
                                     class="d-flex justify-center mt-20 form-group" data-gap="15"
                                >
                                    <template v-if="!isEdit">
                                        <div class="text">{{product.fees[this.feeSelect]?.fees?.energy?.unique ? 'Fee único' : 'Fee por período'}}:</div>
                                        <div class="text">
                                            Desde {{Number(product.fees[this.feeSelect]?.fees?.energy?.minimum) || 0}} hasta {{Number(product.fees[this.feeSelect]?.fees?.energy?.maximum) || 0}} € / MWh
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="d-flex align-center">
                                            <div class="custom-checkbox mr-5" @click.stop="toggleFeeUnique('energy')"
                                                 :class="{ selected: product.fees[this.feeSelect]?.fees?.energy?.unique }"
                                            />

                                            <div class="text">Fee único (€ / MWh)</div>
                                        </div>
                                        <div class="input-group">
                                            <input type="text" :disabled="!isEdit" class="w-60-px" placeholder="Mínimo"
                                                   v-model="product.fees[this.feeSelect].fees.energy.minimum"
                                            />
                                        </div>
                                        -
                                        <div class="input-group">
                                            <input type="text" :disabled="!isEdit" class="w-60-px" placeholder="Máximo"
                                                   v-model="product.fees[this.feeSelect].fees.energy.maximum"
                                            />
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="separator"></div>
                            <!-- Grafica Consumo -->
                            <div v-if="!isEdit">
                                <div v-if="product.fees[feeSelect].withoutAdjustmentServices" class="text mx-auto w-fit-content">
                                    <label class="mx-5" for="withoutAdjustmentServices">
                                        <i class="far fa-scale-balanced mr-5"/>Sin servicios de ajuste
                                    </label>
                                </div>
                                <chart-simplebars-component
                                    :series="this.pricesConsume"
                                />
                            </div>
                            <div v-else>
                                <div class="input-group text mx-auto w-fit-content">
                                    <label class="mx-5" for="withoutAdjustmentServices">
                                        <i class="far fa-scale-balanced mr-5"/>Sin servicios de ajuste
                                    </label>
                                    <input
                                        type="checkbox"
                                        id="withoutAdjustmentServices"
                                        v-model="product.fees[feeSelect].withoutAdjustmentServices"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!--Terminos-->
            <div id="terms" v-else>
                <div class="text ml-20 pb-10" data-size="20" data-weight="400">
                    Términos
                </div>
                <div class="mobile-item">
                    <div class="d-flex justify-between f-wrap">
                        <!-- Potencia -->
                        <div class="w-100">
                            <!-- Tabla Potencias -->
                            <div>
                                <div class="d-flex justify-center align-end">
                                    Precio Potencia (€/kWd)
                                </div>
                                <div
                                    class="d-grid pl-50"
                                    data-gap="5"
                                    data-column="6"
                                >
                                    <div
                                        class="form-group ellipsis"
                                        v-for="(pPower, period) in product.fees[
                                            this.feeSelect
                                        ].prices.power"
                                    >
                                        <div data-size="15">{{ period }}</div>
                                        <div
                                            v-if="!isEdit"
                                            class="text ellipsis"
                                            data-size="12"
                                            data-weight="400"
                                        >
                                            {{
                                                product.fees[this.feeSelect]
                                                    .prices.power[period] &&
                                                Number(
                                                    product.fees[this.feeSelect]
                                                        .prices.power[period]
                                                ) !== 0
                                                    ? product.fees[
                                                        this.feeSelect
                                                        ].prices.power[period]
                                                    : ""
                                            }}
                                        </div>
                                        <div class="input-group" v-else>
                                            <input
                                                type="text"
                                                :disabled="!isEdit"
                                                v-model="
                                                    product.fees[this.feeSelect]
                                                        .prices.power[period]
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="separator"></div>
                            <!-- Grafica potencia -->
                            <div v-if="!isEdit">
                                <chart-simplebars-component
                                    :series="productTerms"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="desktop-item">
                    <div class="d-flex justify-between f-wrap">
                        <!-- Potencia -->
                        <div class="w-48 w-560-px-min mx-auto">
                            <!-- Tabla Potencias -->
                            <div>
                                <div
                                    class="d-grid pl-30"
                                    data-gap="5"
                                    data-column="2"
                                >
                                    <div class="form-group">
                                        <div>Fijo (€ día)</div>
                                        <div v-if="!isEdit" class="text ellipsis pl-10" data-weight="400">
                                            {{ formatNumber(product.fees[this.feeSelect].prices.fixed, 6) || "" }}
                                        </div>
                                        <div class="input-group" v-else>
                                            <input type="text" :disabled="!isEdit"
                                                v-model="product.fees[this.feeSelect].prices.fixed"/>
                                        </div>
                                        <div v-if="product.fees[this.feeSelect].priceType === 'variable' || product.fees[this.feeSelect].priceType === 'indexed'"
                                             class="d-flex justify-center mt-20 form-group" data-gap="15"
                                        >
                                            <template v-if="!isEdit">
                                                <div class="text">
                                                    Desde {{Number(product.fees[this.feeSelect]?.fees?.fixed?.minimum) || 0}} hasta {{Number(product.fees[this.feeSelect]?.fees?.fixed?.maximum) || 0}} € / kW mes
                                                </div>
                                            </template>
                                            <template v-else>
                                                <div class="input-group">
                                                    <input type="text" :disabled="!isEdit" class="w-60-px" placeholder="Mínimo"
                                                           v-model="product.fees[this.feeSelect].fees.fixed.minimum"
                                                    />
                                                </div>
                                                -
                                                <div class="input-group">
                                                    <input type="text" :disabled="!isEdit" class="w-60-px" placeholder="Máximo"
                                                           v-model="product.fees[this.feeSelect].fees.fixed.maximum"
                                                    />
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div>Variable (€ kWh)</div>
                                        <div v-if="!isEdit" class="text ellipsis pl-10" data-weight="400">
                                            {{ formatNumber(product.fees[this.feeSelect].prices.variable, 6) || "" }}
                                        </div>
                                        <div class="input-group" v-else>
                                            <input
                                                type="text"
                                                :disabled="!isEdit"
                                                v-model="
                                                    product.fees[this.feeSelect]
                                                        .prices.variable
                                                "
                                            />
                                        </div>
                                        <div v-if="product.fees[this.feeSelect].priceType === 'variable' || product.fees[this.feeSelect].priceType === 'indexed'"
                                             class="d-flex justify-center mt-20 form-group" data-gap="15"
                                        >
                                            <template v-if="!isEdit">
                                                <div class="text">
                                                    Desde {{Number(product.fees[this.feeSelect]?.fees?.variable?.minimum) || 0}} hasta {{Number(product.fees[this.feeSelect]?.fees?.variable?.maximum) || 0}} € / MWh
                                                </div>
                                            </template>
                                            <template v-else>
                                                <div class="input-group">
                                                    <input type="text" :disabled="!isEdit" class="w-60-px" placeholder="Mínimo"
                                                           v-model="product.fees[this.feeSelect].fees.variable.minimum"
                                                    />
                                                </div>
                                                -
                                                <div class="input-group">
                                                    <input type="text" :disabled="!isEdit" class="w-60-px" placeholder="Máximo"
                                                           v-model="product.fees[this.feeSelect].fees.variable.maximum"
                                                    />
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="separator"></div>
                            <!-- Grafica potencia -->
                            <div v-if="!isEdit">
                                <chart-simplebars-component
                                    :series="productTerms"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="separator"></div>


            <!--Productos extra-->
            <div id="prices" v-if="isEdit && canManage('products.edit') && canEdit">
                <div class="text ml-20 pb-10" data-size="20" data-weight="400">Productos extra</div>

                <div class="d-flex">
                    <!--Disponibles-->
                    <div class="w-50">

                        <p class="text ml-30">Disponibles</p>

                        <div class="p-10">
                            <!--header-->
                            <div class="extraProducts head toSelect mt-10 mx-25 mt-5 mb-15" data-gap="20">
                                <p class="opacity-5 text">Nombre</p>
                                <p class="opacity-5 text">Precio</p>
                                <p class="opacity-5 text">Comisión</p>
                            </div>


                            <!--contenido-->
                            <div v-for="(extra, i) in extraProductsToSelect" @click="toggleSelectExtraProduct(extra)" class="extraProducts toSelect my-5 mx-25 text pointer" data-gap="5">
                                <p class="ellipsis px-10">{{ extra.name }}</p>
                                <p class="px-10">{{ extra.price.amount }} {{ unities[extra.price.unit] }}</p>
                                <p class="px-10">{{ extra.commission }}</p>
                            </div>

                        </div>
                    </div>

                    <!--Seleccionados-->
                    <div class="w-50">
                        <p class="text ml-30">Seleccionados</p>

                        <div class="p-10">
                            <!--header-->
                            <div class="extraProducts head toSelect mt-10 mx-25 mt-5 mb-15" data-gap="20">
                                <p class="opacity-5 text">Nombre</p>
                                <p class="opacity-5 text">Precio</p>
                                <p class="opacity-5 text">Comisión</p>
                            </div>


                            <!--contenido-->
                            <div v-for="(extra, i) in extraProductsSelected" @click="toggleSelectExtraProduct(extra)" class="extraProducts toSelect my-5 mx-25 text pointer" data-gap="5">
                                <p class="ellipsis px-10">{{ extra.name }}</p>
                                <p class="px-10">{{ extra.price.amount }} {{ unities[extra.price.unit] }}</p>
                                <p class="px-10">{{ extra.commission }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="separator"></div>
            </div>


            <!-- Comisiones -->
            <div id="commission">
                <CommissionsTableComponent
                    :canEdit="canManage('products.edit') && canEdit"
                    :type="type"
                    v-model:commissions="product['fees'][feeSelect].commissions"
                    v-model:commissionType="product['fees'][feeSelect].commissionType"
                    v-model:seeCommissionTypes="seeCommissionTypes"
                    :price-type="product['fees'][feeSelect].priceType"
                    :marketer="marketer"
                    :product="product"
                    :isEditing="isEdit"
                    :basicData="basicData"
                    :feeSelect="feeSelect"
                />
            </div>
        </form>
    </div>
    <div v-if="viewPriceHistoryCreateModal" class="modal form" @click="viewPriceHistoryCreateModal = false">
        <div class="w-500-px d-flex column align-center round p-20 justify-center form-group" data-gap="10" data-round="20" data-bg="blanco" data-border-color="principal" @click.stop>
            <div class="input-group">
                <input v-model="priceHistoryMonth" type="month" :max="new Date().toISOString().slice(0, 7)"/>
            </div>
            <div class="custom-button" data-size="regular" @click="createPriceHistory">
                Añadir mes
            </div>
        </div>
    </div>
</template>

<script>
import CommissionsTableComponent from "@/components/items/CommissionsTableComponent.vue";

export default {
    name: "GeneralProductComponent",
    components: {CommissionsTableComponent},
    props: ["basicData"],
    data() {
        return {
            marketer: "",
            product: {},
            productDefault: {},
            type: "",
            errors: {},
            isSeeingCreateProduct: false,
            isSeeingCreateFee: false,
            marketerToReset: "",
            isEditing: false,
            isUpdating: false,
            indexProduct: "",
            feeSelect: "",
            productId: "",
            feeId: "",
            selectFeesActive: false,
            pricesPower: [],
            pricesConsume: [],
            productTerms: [],
            isEdit: false,
            isShowingFeeModal: false,
            selectedFeeToAdd: "",
            selectedFeeId: null,
            newFeeOptions: {
                breakdownType: "basic",
                commissionType: "c",
            },
            seeCommissionTypes: false,
            breakdown: {
                interval: null,
                intervalNumber: null,
                pot1: null,
                pot2: null,
            },
            priceHistorySelected: '',
            viewPriceHistoryCreateModal: false,
            priceHistoryMonth: null,
            unities: {
                'total' : '€',
                'month' : '€/mes',
                'day' : '€/día',
                'year' : '€/año',
            }
        };
    },
    async mounted() {
        this.productId = this.$route.query.productId;
        this.type = this.$route.query.type;

        // Esperamos a que se cargue el marketer y el producto
        await this.fetchMarketer();

        // Sacamos el indice del feeSelect
        this.setFeeInd()
    },

    methods: {
        async fetchMarketer() {
            try {
                const { data } = await axios.get(
                    "/api/marketers/" + this.$route.params.id
                );
                this.marketer = data.marketer;
                this.marketerToReset = { ...this.marketer };

                // bucket según el tipo
                const bucket = this.type;
                const products = this.marketer?.products?.[bucket] || [];

                // productId viene en la query
                const productId = this.$route.query.productId;

                // buscar producto por _id (acepta string o {$oid})
                const product = products.find(
                    (p) => p?._id === productId || p?._id?.$oid === productId
                );

                if (!product) {
                    console.error("Producto no encontrado con id", productId);
                    // decide qué hacer si no existe: volver al listado, mostrar alerta, etc.
                    this.$router.push("/marketers");
                    return;
                }

                // buscar índice del producto por _id
                this.productSelect = products.findIndex(
                    (p) => p?._id === productId || p?._id?.$oid === productId
                );

                if (this.productSelect === -1) {
                    console.error("Producto no encontrado con id", productId);
                    this.$router.push("/marketers");
                    return;
                }

                // asignar producto al estado
                this.product = product;
                this.productDefault = JSON.parse(JSON.stringify(product));

                // anclaje visual si viene refName
                if (this.$route.query.refName) {
                    this.$nextTick(() => {
                        const element = document.getElementById(
                            this.$route.query.refName
                        );
                        if (element) {
                            element.scrollIntoView({ behavior: "smooth" });
                            element.classList.add("blink-border");
                            setTimeout(
                                () => element.classList.remove("blink-border"),
                                2900
                            );
                        }
                    });
                }
            } catch (err) {
                console.error(err);
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
            if (!code || !code.includes('.')) return false;

            const [module, action] = code.split('.');

            const modulePermissions = labelsPermissions[label][module];

            return Array.isArray(modulePermissions) && modulePermissions.includes(action);
        },
        selectFees() {
            //if(!this.isEdit)
            this.selectFeesActive = !this.selectFeesActive;
        },
        setFeeInd(){
            let index = this.product.fees.findIndex(
                (f) => (f.id?.$oid || f.id) === this.$route.query.feeId
            );

            if (index !== -1) {
                console.log("🔹 Seleccionando tarifa por URL:", this.feeSelect);
                this.changeFee(index, this.$route.query.feeId);
            } else {
                console.warn("⚠️ feeId no encontrado:", this.feeSelect);
            }
        },
        deleteProductFee(fee) {
            const bucket = this.type;
            const list = this.marketer?.products?.[bucket] || [];

            // Índice del producto por _id
            const pid = this.product?._id?.$oid || this.product?._id;
            const pIdx = list.findIndex(
                (p) => (p?._id?.$oid || p?._id) === pid
            );
            if (pIdx === -1) return;

            // Índice de la fee a borrar por id
            const fid = fee?.id?.$oid || fee?.id;
            const fees = list[pIdx]?.fees || [];
            const feeSelectedInd = fees.findIndex(
                (f) => (f?.id?.$oid || f?.id) === fid
            );
            if (feeSelectedInd === -1) return;

            const confirmAndCall = async (deleteProd) => {
                try {
                    await axios.put("/api/marketers/deleteProductFee", {
                        marketer: this.marketer,
                        productInd: pIdx, // el backend lo espera por índice
                        feeInd: feeSelectedInd, // idem
                        deleteProd,
                        type: bucket,
                    });


                    if (this.feeSelect === feeSelectedInd) //Si borras la misma que estas viendo te echa
                        this.$router.push("/marketers");
                    else //Sino borro la eliminada para no verla temporalmente
                        this.product.fees.splice(feeSelectedInd, 1);


                    // Vuelvo a calcular el indice del feeSelect
                    this.setFeeInd()



                    // (Opcional) toast rápido
                    Swal.fire({
                        icon: "success",
                        title: "Eliminado",
                        timer: 1000,
                        timerProgressBar: true
                    });
                } catch (err) {
                    console.error("Error borrando en backend:", err);
                    Swal.fire({
                        icon: "error",
                        title: "No se pudo eliminar",
                        text: "Inténtalo de nuevo",
                    });
                }
            };

            if (fees.length === 1) {
                // Se elimina el producto completo
                Swal.fire({
                    icon: "warning",
                    title: "Se eliminará el producto",
                    text: "No quedarán tarifas vinculadas.",
                    confirmButtonText: "Desvincular y eliminar",
                    confirmButtonColor: "red",
                    showCancelButton: true,
                    cancelButtonText: "Cancelar",
                    focusCancel: true,
                }).then((res) => {
                    if (res.isConfirmed) confirmAndCall(true);
                });
            } else {
                // Solo se desvincula la tarifa
                Swal.fire({
                    icon: "warning",
                    title: "¿Estás seguro?",
                    text: "Se desvinculará la tarifa del producto.",
                    confirmButtonText: "Desvincular",
                    confirmButtonColor: "red",
                    showCancelButton: true,
                    cancelButtonText: "Cancelar",
                    focusCancel: true,
                }).then((res) => {
                    if (res.isConfirmed) confirmAndCall(false);
                });
            }
        },

        async addProductFee() {
            let newFee = {
                commissions: [{
                    con1: null,
                    con2: null,
                    pot1: null,
                    pot2: null,
                    multiply: false,
                    base: null,
                    breakdown: [],
                }],
                commissionType: 'f',
                prices: {
                    power: {
                        P1: "",
                        P2: "",
                        P3: "",
                        P4: "",
                        P5: "",
                        P6: "",
                    },
                    consume: {
                        P1: "",
                        P2: "",
                        P3: "",
                        P4: "",
                        P5: "",
                        P6: "",
                    },
                },
                id: this.selectedFeeToAdd.id,
                comment: "",
                type: {
                    pyme: true,
                    residencial: true,
                },
            };

            this.marketer.products[this.type][this.productSelect].fees.push(newFee);


            // Crear un mapa para buscar los nombres por ID
            let nameMap = this.marketer.fees[this.type].reduce((map, fee) => {
                map[fee.id.$oid] = fee.name;
                return map;
            }, {});

            // Ordenar productFees por los nombres en nameMap
            this.marketer.products[this.type][this.productSelect].fees.sort((a, b) => {
                const nameA = nameMap[a.id.$oid] || ""; // Obtén el nombre o una cadena vacía si no existe
                const nameB = nameMap[b.id.$oid] || "";
                return nameA.localeCompare(nameB); // Orden alfabético
            });


            //le vinculo en bbdd la nueva tarifa
            await axios
                .put(`/api/marketers/addProductFee`, {
                    marketer: this.marketer,
                    productInd: this.productSelect,
                    fee: newFee,
                    type: this.type,
                })
                .then((res) => {
                    // Vuelvo a calcular el indice del feeSelect
                    this.setFeeInd()
                })
                .catch((err) => {
                    console.log(err);
                });

            //cierro la venta
            this.closeFeeModal();
        },
        closeFeeModal() {
            this.isShowingFeeModal = false;
            this.selectedFeeToAdd = "";
            this.newFeeOptions = {
                breakdownType: "basic",
                commissionType: "c",
            };
        },
        actionLink(route) {
            this.$router.push(route);
        },
        changeFee(index, feeId) {
            this.feeSelect = index;

            if (this.type === "electricity")
                this.elecPricesChart();
            else if (this.type === "gas")
                this.gasTermsChart();

            this.$router.replace({
                query: {
                    ...this.$route.query,
                    feeId: feeId
                }
            });

            this.selectFeesActive = false;
        },
        elecPricesChart() {
            this.pricesPower = [];
            this.pricesConsume = [];

            for (let period in this.product.fees[this.feeSelect].prices.power) {
                this.pricesPower.push({
                    period: period,
                    value: parseFloat(
                        this.product.fees[this.feeSelect].prices.power[period]
                    ),
                });
            }

            for (let period in this.product.fees[this.feeSelect].prices.consume) {
                this.pricesConsume.push({
                    period: period,
                    value: parseFloat(
                        this.product.fees[this.feeSelect].prices.consume[period]
                    ),
                });
            }
        },
        gasTermsChart() {
            this.productTerms = [
                {
                    period: "Fijo",
                    value: parseFloat(
                        this.product.fees[this.feeSelect].prices.fixed
                    ),
                },
                {
                    period: "Variable",
                    value: parseFloat(
                        this.product.fees[this.feeSelect].prices.variable
                    ),
                },
            ];
        },
        getFee(id) {
            let feeName = "";

            let fees = this.marketer.fees[this.type];

            for (let i = 0; i < fees.length; i++) {
                if (fees[i].id.$oid == id.$oid) {
                    feeName = fees[i].name;
                }
            }

            return feeName;
        },
        changeEdit(cancel) {
            if (cancel) {
                this.product = JSON.parse(JSON.stringify(this.productDefault));
            }
            this.isEdit = !this.isEdit;
        },
        validateNumeric(field) {
            let value = this.product.fees[this.feeSelect][field];

            if (typeof value !== "string") value = value?.toString() || "";

            // SOLO permitir dígitos, comas y puntos
            value = value.replace(/[^0-9.,]/g, "");

            // Guardar ya limpiado
            this.product.fees[this.feeSelect][field] = value;
        },
        normalizeNumber(value) {
            if (!value) return "";
            return value.toString().replace(",", ".");
        },
        async saveProd() {
            const bucket = this.type;

            // ====== TUS CONVERSIONES (igual que ahora) ======
            let prices = this.product.fees[this.feeSelect].prices;

            // Control de comas y puntos
            if (this.type === "electricity") {
                for (let i = 1; i <= 6; i++) {
                    if (typeof prices.power[`P${i}`] === "string") {
                        this.product.fees[this.feeSelect].prices.power[
                            `P${i}`
                            ] = prices.power[`P${i}`].replace(",", ".");
                    }
                    if (typeof prices.consume[`P${i}`] === "string") {
                        this.product.fees[this.feeSelect].prices.consume[
                            `P${i}`
                            ] = prices.consume[`P${i}`].replace(",", ".");
                    }
                }
            } else {
                this.product.fees[this.feeSelect].prices.fixed =
                    this.product.fees[this.feeSelect].prices.fixed
                        .toString()
                        .replace(",", ".");
                this.product.fees[this.feeSelect].prices.variable =
                    this.product.fees[this.feeSelect].prices.variable
                        .toString()
                        .replace(",", ".");
            }

            // ===== CONVERSIONES EN HISTORY SI EXISTE =====
            if (prices.history && typeof prices.history === "object") {
                Object.keys(prices.history).forEach((period) => {
                    const historyEntry = prices.history[period];

                    if (!historyEntry) return;

                    for (let i = 1; i <= 6; i++) {
                        if (typeof historyEntry.power?.[`P${i}`] === "string") {
                            historyEntry.power[`P${i}`] =
                                historyEntry.power[`P${i}`].replace(",", ".");
                        }

                        if (typeof historyEntry.consume?.[`P${i}`] === "string") {
                            historyEntry.consume[`P${i}`] =
                                historyEntry.consume[`P${i}`].replace(",", ".");
                        }
                    }
                });
            }

            let minPower = this.normalizeNumber(this.product.fees[this.feeSelect].minPower);
            let maxPower = this.normalizeNumber(this.product.fees[this.feeSelect].maxPower);
            let minConsumption = this.normalizeNumber(this.product.fees[this.feeSelect].minConsumption);
            let maxConsumption = this.normalizeNumber(this.product.fees[this.feeSelect].maxConsumption);

            this.product.fees[this.feeSelect].minPower = minPower;
            this.product.fees[this.feeSelect].maxPower = maxPower;
            this.product.fees[this.feeSelect].minConsumption = minConsumption;
            this.product.fees[this.feeSelect].maxConsumption = maxConsumption;

            // Comisiones
            const toFloat = (val) => {
                if (val === null || val === undefined || val === "") return null;
                const parsed = parseFloat(val.toString().replace(",", "."));
                return isNaN(parsed) ? null : parsed;
            };

            const fee = this.product.fees[this.feeSelect];

            if (fee.commissions?.length) {
                fee.commissions.forEach((commission) => {
                    commission.pot1 = toFloat(commission.pot1);
                    commission.pot2 = toFloat(commission.pot2);
                    commission.con1 = toFloat(commission.con1);
                    commission.con2 = toFloat(commission.con2);
                    commission.base = toFloat(commission.base);

                    if (commission.breakdown) {
                        Object.keys(commission.breakdown).forEach((key) => {
                            commission.breakdown[key] = toFloat(commission.breakdown[key]);
                        });
                    }
                });
            }

            // Excedentes
            if (!this.product.fees[this.feeSelect].surplus) {
                this.product.fees[this.feeSelect].surplus = null;
            }
            // ====== FIN TUS CONVERSIONES ======

            // Compruebo en caso de ser fijo-variable o indexado si tiene fees introducidos
            if (this.product.fees[this.feeSelect].priceType === 'variable' || this.product.fees[this.feeSelect].priceType === 'indexed') {
                function isValidRange({ minimum, maximum }) {
                    // Convertimos a número
                    const min = Number(minimum);
                    const max = Number(maximum);

                    // Comprobaciones
                    if (minimum === "" || maximum === "") return false;
                    if (isNaN(min) || isNaN(max)) return false;
                    return max >= min;


                }

                const feeFieldsByType = {
                    electricity: ['power', 'energy'],
                    gas: ['fixed', 'variable'],
                };

                const fields = feeFieldsByType[this.type];

                if (fields) {
                    const fees = this.product.fees[this.feeSelect].fees;
                    const allValid = fields.every(field => isValidRange(fees[field]));

                    if (!allValid) {
                        await Swal.fire({
                            icon: "error",
                            title: "Error con los fees",
                            text: "Ha habido un error con los fees. Por favor, revise los valores introducidos.",
                        });
                        return;
                    }
                }

            }

            try {
                // 🔁 SINCRONIZAR EL PRODUCTO EDITADO EN marketer.products[bucket] POR _id
                const list = this.marketer?.products?.[bucket] || [];
                const pid = this.product?._id?.$oid || this.product?._id; // productId actual
                const pIdx = list.findIndex(
                    (p) => (p?._id?.$oid || p?._id) === pid
                );

                // Reemplaza el producto dentro del array del marketer (evita refs reactivas raras)
                const productClean = JSON.parse(JSON.stringify(this.product));
                if (pIdx !== -1) {
                    this.marketer.products[bucket].splice(
                        pIdx,
                        1,
                        productClean
                    );
                } else {
                    this.marketer.products[bucket].push(productClean);
                }

                // Guardar en backend
                await axios.post(`/api/marketers/${this.marketer._id}`, {
                    marketer: JSON.stringify(this.marketer),
                });

                Swal.fire({
                    icon: "success",
                    title: "Se ha guardado correctamente",
                    timer: 1500,
                });

                // Releer para sincronizar y reconstruir charts
                await this.fetchMarketer();
                if (this.type === "electricity") this.elecPricesChart();
                else this.gasTermsChart();

                this.isEdit = !this.isEdit;
            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: "error",
                    title: "Ha ocurrido un error",
                    text: "No se ha guardado correctamente",
                });
            }
        },
        seeFilters(type) {
            //Cerrar selects
            this.hideCustomSelects();

            switch (type) {
                case "commissionType":
                    this.isSeeingFiltersPc.commissionType = true;
                    break;

                case "breakdownType":
                    this.isSeeingFiltersPc.breakdownType = true;
                    break;
            }
        },
        hideCustomSelects() {
            this.seeCommissionTypes = false;
        },
        togglePriceType(){
            //Borro history si no es indexado
            if(this.product.fees[this.feeSelect].priceType !== 'indexed'){
                delete this.product.fees[this.feeSelect].prices.history;
            }

            //Añado fees si no tiene y es variable o indexado
            if(this.product.fees[this.feeSelect].priceType === 'variable' || this.product.fees[this.feeSelect].priceType === 'indexed'){
                if(!this.product.fees[this.feeSelect].fees){
                    if(this.type === 'electricity'){
                        this.product.fees[this.feeSelect].fees = {
                            power: {unique: true, minimum: "", maximum: ""},
                            energy: {unique: true, minimum: "", maximum: ""}
                        };
                    }else{
                        this.product.fees[this.feeSelect].fees = {
                            fixed: {minimum: "", maximum: ""},
                            variable: {minimum: "", maximum: ""}
                        };
                    }
                }
            //Cambio commissionType si está en porcentaje del fee y tipo de precio fijo
            }else{
                if(this.product.commissionType === 'fp'){
                    this.product.commissionType = 'c';
                }
            }
        },
        toggleFeeUnique(type){
            const currentFeeUnique = this.product.fees[this.feeSelect].fees[type].unique;

            this.product.fees[this.feeSelect].fees[type].unique = !currentFeeUnique;
        },
        formatpriceHistoryDate(date){
            return moment(date).format('MMM YYYY');
        },
        createPriceHistory(){
            if(!this.product.fees[this.feeSelect].prices.history){
                this.product.fees[this.feeSelect].prices.history = {};
            }

            if(!this.priceHistoryMonth || Object.keys(this.product.fees[this.feeSelect].prices.history).includes(this.priceHistoryMonth)){
                const message = this.priceHistoryMonth ? 'El mes seleccionado ya existe en el historial de precios' : 'Debes seleccionar un mes';
                Swal.fire({
                    icon: 'error',
                    title: this.priceHistoryMonth ? 'Mes existente' : 'Mes no seleccionado',
                    text: message
                });
                return;
            }

            this.product.fees[this.feeSelect].prices.history[this.priceHistoryMonth] = {
                power: {P1: 0, P2: 0, P3: 0, P4: 0, P5: 0, P6: 0},
                consume: {P1: 0, P2: 0, P3: 0, P4: 0, P5: 0, P6: 0},
            };

            this.priceHistorySelected = this.priceHistoryMonth;
            this.priceHistoryMonth = null;
            this.viewPriceHistoryCreateModal = false;
        },
        deletePriceHistory(){
            delete this.product.fees[this.feeSelect].prices.history[this.priceHistorySelected];
            this.priceHistorySelected = '';
        },
        toggleSelectExtraProduct(extra) {
            if (!this.product.fees[this.feeSelect].extras) this.product.fees[this.feeSelect].extras = []

            if (this.product.fees[this.feeSelect].extras.includes(extra.id.$oid))
                this.product.fees[this.feeSelect].extras = this.product.fees[this.feeSelect].extras.filter(extraNow => extraNow !== extra.id.$oid);
            else
                this.product.fees[this.feeSelect].extras.push(extra.id.$oid);
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
        formatNumber(number, maxFractionDigits = 2) {
            return Intl.NumberFormat("es", {
                minimumFractionDigits: 0, maximumFractionDigits: maxFractionDigits
            }).format(this.parseStringToNumber(number))
        },
    },
    computed: {
        currentPower(){
            if(this.priceHistorySelected){
                return this.product?.fees?.[this.feeSelect]?.prices?.history?.[this.priceHistorySelected]?.power;
            }
            return this.product?.fees?.[this.feeSelect]?.prices?.power || {};
        },
        currentConsume(){
            if(this.priceHistorySelected){
                return this.product?.fees?.[this.feeSelect]?.prices?.history?.[this.priceHistorySelected]?.consume;
            }
            return this.product?.fees?.[this.feeSelect]?.prices?.consume || {};
        },
        isReadOnly() {
            if (!this.basicData.userLogged) return true;
            else this.basicData.userLogged;
            return this.basicData.userLogged.permissions.includes("READONLY");
        },
        isInputsDisabled() {
            if (!this.basicData.userLogged) return true;
            else this.basicData.userLogged;
            return (
                this.basicData.userLogged.permissions.includes("READONLY") ||
                !this.isEditing
            );
        },
        filteredFeesToAdd() {
            if (!this.product) return [];

            return this.marketer.fees[this.type].filter((feeNow) => {
                let isAdded = this.product.fees.findIndex(
                    (feeAddedNow) => feeAddedNow.id.$oid === feeNow.id.$oid
                );

                return isAdded === -1;
            });
        },
        marketerProducts() {
            if (!this.marketer || !this.product) return [];

            let fees = this.marketer.fees[this.type];

            const result = [];

            this.marketer.products[this.type].forEach((product) => {
                product.fees.forEach((fee) => {
                    // Busca el nombre de la tarifa por id
                    const foundFee = fees.find(
                        (feeMarketer) => feeMarketer.id.$oid === fee.id.$oid
                    );

                    // Si la fee no está archivada y existe en el array fees
                    if (foundFee && !fee.archived) {
                        result.push({
                            productName: product.name,
                            feeName: foundFee.name,
                            consumptionBreakdown: fee.consumptionBreakdown,
                            commissionType: product.commissionType,
                        });
                    }
                });
            });

            return result;
        },
        canEdit() {
            //Compruebo si el producto es de zoco y es zoco, en caso negativo devuelvo false
            if (
                this.marketer.createdBy === "65cb57489c2c285441086a43" &&
                this.basicData.userLogged._id !== "65cb57489c2c285441086a43"
            ) {
                return false;
            } else {
                return true;
            }
        },
        priceHistoryData(){
            if (!this.product.fees[this.feeSelect].prices.history) return [];

            //Devuelve el objeto ordenado por claves
            return Object.keys(this.product.fees[this.feeSelect].prices.history).sort((a, b) => b.localeCompare(a));
        },
        extraProductsToSelect() {
            let code = this.type === 'electricity' ? 'cl' : 'cg'

            if(!this.marketer?.extras) return []

            return this.marketer.extras.filter(extra => extra.productTypes.includes(code) && extra.to.product && (!this.product.fees[this.feeSelect].extras || !this.product.fees[this.feeSelect].extras.includes(extra.id.$oid)))
        },
        extraProductsSelected() {
            let code = this.type === 'electricity' ? 'cl' : 'cg'

            if(!this.marketer?.extras) return []

            return this.marketer.extras.filter(extra => extra.productTypes.includes(code) && extra.to.product && this.product.fees[this.feeSelect].extras && this.product.fees[this.feeSelect].extras.includes(extra.id.$oid))
        }
    },
};
</script>

<style scoped></style>
