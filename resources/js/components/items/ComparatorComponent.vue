<template>
    <div class="comparator-mobile" :class="{ mobile: !isDesktopView }">
        <div class="separator"></div>
        <div :class="['d-flex mt-20 pb-20', { 'column': !isDesktopView }]">
            <div :class="['dashboard-card column mb-auto', { 'w-40 mr-30': isDesktopView, 'w-100': !isDesktopView }]">
                <!--Seleccionador-->
                <form v-if="viewInputs" class="d-flex column justify-between form h-565-px-min" @submit.prevent="handleSubmit">
                    <div class="d-flex column h-520-px-min relPos">
                        <div class="absPos left mr-10 pointer" data-size="20" @click="viewStoredComparatives = true"><i
                            class="far fa-history" /></div>
                        <h2 class="text text-center my-20">¿Qué quieres comparar?</h2>
                        <!--Seleccionador de opción de entrada-->
                        <div class="d-flex justify-center align-center" data-gap="10">
                            <div class="text pointer" data-size="16" @click="optionSelected = 'bill'">
                                <i :class="[optionSelected === 'bill' ? 'fas' : 'far', 'fa-file-invoice-dollar mr-15']"></i>Factura
                            </div>
                            <div class="separator h-30-px" data-position="vertical" />
                            <div class="text pointer" data-size="16" @click="optionSelected = 'cups'">
                                <i :class="[optionSelected === 'cups' ? 'fas' : 'far', 'fa-meter-bolt mr-5']"></i>CUPS
                            </div>
                            <div class="separator h-30-px" data-position="vertical" />
                            <div class="text pointer" data-size="16" @click="optionSelected = 'manual'">
                                <i :class="[optionSelected === 'manual' ? 'fas' : 'far', 'fa-pen mr-10']"></i>Datos
                            </div>
                        </div>
                        <div class="separator h-2-px w-100 mx-auto"></div>


                        <!--Ordenador-->
                        <template v-if="isDesktopView">
                            <!--Factura-->
                            <div v-if="optionSelected === 'bill'" class="mt-20 d-flex column" style="flex: 1;">
                                <div class="d-flex justify-center align-center" data-gap="10">
                                    <drag-drop-component :desktop="isDesktopView" @update:files="getFilesSelected"/>
                                </div>
                                <!-- Preview de la factura -->
                                <div v-if="previewUrl" class="my-20 px-15">
                                    <div class="round overflow-hidden" style="border: 1px solid #e5e7eb;">
                                        <!-- Imagen -->
                                        <img
                                            v-if="previewKind === 'image'"
                                            :src="previewUrl"
                                            alt="Factura"
                                            style="width: 100%; max-height: 420px; object-fit: contain;"
                                        />
                                        <!-- PDF -->
                                        <embed
                                            v-else-if="previewKind === 'pdf'"
                                            :src="previewUrl"
                                            type="application/pdf"
                                            style="width: 100%; height: 420px;"
                                        />
                                        <!-- Si el tipo no es soportado, no se muestra nada -->
                                    </div>
                                </div>

                                <div class="text mx-15 mt-auto comparator-note">
                                    <p>* Algunos datos obtenidos pueden ser erróneos.</p>
                                    <p>Comprueba que sean correctos o edítalos en caso de error.</p>
                                </div>
                            </div>
                            <!--CUPS-->
                            <div v-if="optionSelected === 'cups'" class="form-group">
                                <div class="d-flex justify-center align-center" data-gap="10">
                                    <p class="text" data-size="14">CUPS:</p>
                                    <div class="input-group w-220-px-max">
                                        <input v-model.trim="cups" name="cups" placeholder="ES0000XXXXXXXXXXXXAB0F" />
                                    </div>
                                </div>
                                <div class="d-flex column align-center mt-40">
                                    <div class="d-flex column w-100">
                                        <div class="d-flex" data-gap="15">
                                            <p class="text mb-10 ml-20" data-size="16">Precios de potencia</p>
                                            <div class="input-group h-30-px mr-35">
                                                <select v-model="powerPricePeriod">
                                                    <option value="day">€ / kW día</option>
                                                    <option value="month">€ / kW mes</option>
                                                    <option value="year">€ / kW año</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-center" data-gap="5">
                                            <div v-for="(_, index) of prices.power" :key="index">
                                                <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                                <div class="input-group w-80-px-max">
                                                    <input
                                                        :placeholder="powerPricePeriod === 'day' ? '€ / kW día' : powerPricePeriod === 'month' ? '€ / kW mes' : '€ / kW año'"
                                                        v-model.trim="prices.power[index]" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="separator w-80 my-25 mx-auto"></div>
                                    </div>
                                    <div class="d-flex column w-100">
                                        <p class="text mb-10 ml-20" data-size="16">Precios de energía (€/kWh)</p>
                                        <div class="d-flex justify-center" data-gap="5">
                                            <div v-for="(_, index) of prices.energy" :key="index">
                                                <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                                <div class="input-group w-80-px-max">
                                                    <input placeholder="€/kWh" v-model.trim="prices.energy[index]" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="separator w-80 my-25 mx-auto"></div>
                                    </div>
                                    <div class="d-flex column w-100">
                                        <p class="text mb-10 ml-20" data-size="16">Excedentes (€/kWh)</p>
                                        <div class="d-flex justify-center" data-gap="5">
                                            <div>
                                                <p class="text ml-10">Batería virtual</p>
                                                <div class="input-group w-150-px-max">
                                                    <input placeholder="€/mes" v-model.trim="surplus.virtualBattery" />
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text ml-10">Cantidad</p>
                                                <div class="input-group w-150-px-max">
                                                    <input placeholder="kWh" v-model.trim="surplus.amount" />
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text ml-10">Precio</p>
                                                <div class="input-group w-150-px-max">
                                                    <input placeholder="€/kWh" v-model.trim="surplus.price" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--Datos manuales-->
                            <div v-if="optionSelected === 'manual'" class="form-group">
                                <div class="d-flex justify-center align-center" data-gap="10">
                                    <p class="text" data-size="14">Tarifa:</p>
                                    <div class="input-group w-220-px-max">
                                        <select v-model="fee">
                                            <option value="">Selecciona una tarifa</option>
                                            <option value="2.0TD">Tarifa 2.0TD</option>
                                            <option value="3.0TD">Tarifa 3.0TD</option>
                                            <option value="6.1TD">Tarifa 6.1TD</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex column align-center mt-40">
                                    <div class="d-flex column w-100">
                                        <p class="text mb-10 ml-20" data-size="16">Potencia contratada (kW)</p>
                                        <div class="d-flex justify-center" data-gap="5">
                                            <div v-for="(_, index) of consumptionData.power" :key="index">
                                                <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                                <div class="input-group w-80-px-max">
                                                    <input placeholder="kW" v-model.trim="consumptionData.power[index]" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="separator w-80 my-25 mx-auto"></div>
                                    </div>
                                    <div class="d-flex column w-100">
                                        <p class="text mb-10 ml-20" data-size="16">Energía consumida (kWh)</p>
                                        <div class="d-flex justify-center" data-gap="5">
                                            <div v-for="(_, index) of consumptionData.consumption" :key="index">
                                                <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                                <div class="input-group w-80-px-max">
                                                    <input placeholder="kWh"
                                                           v-model.trim="consumptionData.consumption[index]" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="separator w-80 my-25 mx-auto"></div>
                                    </div>
                                    <div class="d-flex column w-100">
                                        <div class="d-flex" data-gap="15">
                                            <p class="text mb-10 ml-20" data-size="16">Precios de potencia</p>
                                            <div class="input-group h-30-px">
                                                <select v-model="powerPricePeriod">
                                                    <option value="day">€ / kW día</option>
                                                    <option value="month">€ / kW mes</option>
                                                    <option value="year">€ / kW año</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-center" data-gap="5">
                                            <div v-for="(_, index) of prices.power" :key="index">
                                                <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                                <div class="input-group w-80-px-max">
                                                    <input
                                                        :placeholder="powerPricePeriod === 'day' ? '€ / kW día' : powerPricePeriod === 'month' ? '€ / kW mes' : '€ / kW año'"
                                                        v-model.trim="prices.power[index]" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="separator w-80 my-25 mx-auto"></div>
                                    </div>
                                    <div class="d-flex column w-100">
                                        <p class="text mb-10 ml-20" data-size="16">Precios de energía (€/kWh)</p>
                                        <div class="d-flex justify-center" data-gap="5">
                                            <div v-for="(_, index) of prices.energy" :key="index">
                                                <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                                <div class="input-group w-80-px-max">
                                                    <input placeholder="€/kWh" v-model.trim="prices.energy[index]" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="separator w-80 my-25 mx-auto"></div>
                                    </div>
                                    <div class="d-flex column w-100">
                                        <p class="text mb-10 ml-20" data-size="16">Excedentes (€/kWh)</p>
                                        <div class="d-flex justify-center" data-gap="5">
                                            <div>
                                                <p class="text ml-10">Batería virtual</p>
                                                <div class="input-group w-150-px-max">
                                                    <input placeholder="€/mes" v-model.trim="surplus.virtualBattery" />
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text ml-10">Cantidad</p>
                                                <div class="input-group w-150-px-max">
                                                    <input placeholder="kWh" v-model.trim="surplus.amount" />
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text ml-10">Precio</p>
                                                <div class="input-group w-150-px-max">
                                                    <input placeholder="€/kWh" v-model.trim="surplus.price" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--Selectores-->
                            <div v-if="optionSelected !== ''" class="mt-20 d-flex column" data-gap="15">
                                <!--Selector de comercializadora actual-->
                                <div class="d-flex justify-center" data-gap="10">
                                    <div class="d-flex justify-center align-center form-group" data-gap="10">
                                        <p class="text opacity-6">Comercializadora actual:</p>
                                        <div class="input-group w-220-px-max">
                                            <select v-model="opportunityData.currentMarketer">
                                                <option value="" disabled>Selecciona una</option>
                                                <option v-for="marketer of sortedMarketers">{{marketer.name}}</option>
                                                <option value="other(This never matches)">Otra comercializadora</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!--Selector de tipo de producto-->
                                <div class="d-flex justify-center" data-gap="10">
                                    <div>
                                        <p class="text opacity-6">Tipo de producto:</p>
                                    </div>
                                    <div>
                                        <label class="mr-10 text" for="residencial"><i class="far fa-house mr-5" />Residencial</label>
                                        <input type="checkbox" id="residencial" v-model="filters.residencial">
                                    </div>
                                    <div>
                                        <label class="mr-10 text" for="pyme"><i class="far fa-building mr-5" />PYME</label>
                                        <input type="checkbox" id="pyme" v-model="filters.pyme">
                                    </div>
                                </div>

                                <!--Selector de tipo de precio-->
                                <div class="d-flex justify-center" data-gap="10">
                                    <div>
                                        <p class="text opacity-6">Tipo de precio:</p>
                                    </div>
                                    <div>
                                        <label class="mr-10 text" for="fixedPrice"><i class="far fa-lock mr-5" />Fijo</label>
                                        <input type="checkbox" id="fixedPrice" v-model="filters.fixed">
                                    </div>
                                    <div>
                                        <label class="mr-10 text" for="variablePrice"><i class="far fa-chart-mixed mr-5" />Fijo-Variable</label>
                                        <input type="checkbox" id="variablePrice" v-model="filters.variable">
                                    </div>
                                    <div>
                                        <label class="mr-10 text" for="indexedPrice"><i class="far fa-chart-line mr-5" />Indexado</label>
                                        <input type="checkbox" id="indexedPrice" v-model="filters.indexed">
                                    </div>
                                </div>

                                <!-- Inputs tipo de precio -->
                                <div v-if="filters.variable || filters.indexed" class="d-flex column w-100 mt-20 form">

                                    <div class="d-flex column w-100">
                                        <p class="text mb-10 ml-20" data-size="16">Fees</p>

                                        <div class="form-group d-flex justify-center" data-gap="10">

                                            <div>
                                                <p class="text ml-10">Fee potencia</p>
                                                <div class="input-group w-150-px-max">
                                                    <input placeholder="€ / kW mes" v-model.trim="priceFees.power" />
                                                </div>
                                            </div>

                                            <div>
                                                <p class="text ml-10">Fee energía</p>
                                                <div class="input-group w-150-px-max">
                                                    <input placeholder="€ MWh" v-model.trim="priceFees.energy" />
                                                </div>
                                            </div>

                                        </div>

                                        <div class="separator w-80 my-25 mx-auto"></div>
                                    </div>

                                </div>

                                <button class="custom-button text-center w-100" data-size="regular">Comparar</button>
                            </div>
                        </template>

                        <!--Móvil-->
                        <template v-else>
                            <!--Factura-->
                            <div v-if="optionSelected === 'bill'" class="mt-20 d-flex column" style="flex: 1;">
                                <div class="d-flex justify-center align-center" data-gap="10">
                                    <drag-drop-component :desktop="isDesktopView" @update:files="getFilesSelected"/>
                                </div>
                                <!-- Preview de la factura -->
                                <div v-if="previewUrl" class="my-20 px-15">
                                    <div class="round overflow-hidden" style="border: 1px solid #e5e7eb;">
                                        <!-- Imagen -->
                                        <img
                                            v-if="previewKind === 'image'"
                                            :src="previewUrl"
                                            alt="Factura"
                                            style="width: 100%; max-height: 420px; object-fit: contain;"
                                        />
                                        <!-- PDF -->
                                        <embed
                                            v-else-if="previewKind === 'pdf'"
                                            :src="previewUrl"
                                            type="application/pdf"
                                            style="width: 100%; height: 420px;"
                                        />
                                        <!-- Si el tipo no es soportado, no se muestra nada -->
                                    </div>
                                </div>

                                <div class="text mx-15 mt-auto comparator-note">
                                    <p>* Algunos datos obtenidos pueden ser erróneos.</p>
                                    <p>Comprueba que sean correctos o edítalos en caso de error.</p>
                                </div>
                            </div>
                            <!--CUPS-->
                            <div v-if="optionSelected === 'cups'" class="form-group cups-section">
                                <div class="cups-field">
                                    <p class="text" data-size="14">CUPS</p>
                                    <div class="input-group w-100">
                                        <input v-model.trim="cups" name="cups" placeholder="ES0000XXXXXXXXXXXXAB0F" />
                                    </div>
                                </div>

                                <div class="cups-block">
                                    <div class="cups-block-header d-flex align-center justify-between" data-gap="10">
                                        <p class="text" data-size="16">Precios de potencia</p>
                                        <div class="input-group h-30-px cups-period-select">
                                            <select v-model="powerPricePeriod">
                                                <option value="day">€ / kW día</option>
                                                <option value="month">€ / kW mes</option>
                                                <option value="year">€ / kW año</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="cups-inputs" data-gap="10">
                                        <div v-for="(_, index) of prices.power" :key="index" class="cups-input">
                                            <p class="text" data-size="12">{{ `P${index + 1}` }}</p>
                                            <div class="input-group w-100">
                                                <input
                                                    :placeholder="powerPricePeriod === 'day' ? '€ / kW día' : powerPricePeriod === 'month' ? '€ / kW mes' : '€ / kW año'"
                                                    v-model.trim="prices.power[index]" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="separator w-80 my-15 mx-auto"></div>
                                </div>

                                <div class="cups-block">
                                    <p class="text" data-size="16">Precios de energía (€/kWh)</p>
                                    <div class="cups-inputs" data-gap="10">
                                        <div v-for="(_, index) of prices.energy" :key="index" class="cups-input">
                                            <p class="text" data-size="12">{{ `P${index + 1}` }}</p>
                                            <div class="input-group w-100">
                                                <input placeholder="€/kWh" v-model.trim="prices.energy[index]" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="separator w-80 my-15 mx-auto"></div>
                                </div>

                                <div class="cups-block">
                                    <p class="text" data-size="16">Excedentes (€/kWh)</p>
                                    <div class="cups-inputs single-column" data-gap="10">
                                        <div class="cups-input">
                                            <p class="text" data-size="12">Batería virtual</p>
                                            <div class="input-group w-100">
                                                <input placeholder="€/mes" v-model.trim="surplus.virtualBattery" />
                                            </div>
                                        </div>
                                        <div class="cups-input">
                                            <p class="text" data-size="12">Cantidad</p>
                                            <div class="input-group w-100">
                                                <input placeholder="kWh" v-model.trim="surplus.amount" />
                                            </div>
                                        </div>
                                        <div class="cups-input">
                                            <p class="text" data-size="12">Precio</p>
                                            <div class="input-group w-100">
                                                <input placeholder="€/kWh" v-model.trim="surplus.price" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--Datos manuales-->
                            <div v-if="optionSelected === 'manual'" class="form-group manual-section">
                                <div class="manual-block">
                                    <p class="text" data-size="16">Tarifa</p>
                                    <div class="input-group w-100">
                                        <select v-model="fee">
                                            <option value="">Selecciona una tarifa</option>
                                            <option value="2.0TD">Tarifa 2.0TD</option>
                                            <option value="3.0TD">Tarifa 3.0TD</option>
                                            <option value="6.1TD">Tarifa 6.1TD</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="manual-block">
                                    <p class="text" data-size="16">Potencia contratada (kW)</p>
                                    <div class="manual-inputs" data-gap="6">
                                        <div v-for="(_, index) of consumptionData.power" :key="index" class="manual-input">
                                            <p class="text" data-size="12">{{ `P${index + 1}` }}</p>
                                            <div class="input-group w-100">
                                                <input placeholder="kW" v-model.trim="consumptionData.power[index]" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="manual-block">
                                    <p class="text" data-size="16">Energía consumida (kWh)</p>
                                    <div class="manual-inputs" data-gap="6">
                                        <div v-for="(_, index) of consumptionData.consumption" :key="index" class="manual-input">
                                            <p class="text" data-size="12">{{ `P${index + 1}` }}</p>
                                            <div class="input-group w-100">
                                                <input placeholder="kWh" v-model.trim="consumptionData.consumption[index]" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="manual-block">
                                    <div class="manual-block-header d-flex align-center justify-between" data-gap="10">
                                        <p class="text" data-size="16">Precios de potencia</p>
                                        <div class="input-group h-30-px manual-period-select">
                                            <select v-model="powerPricePeriod">
                                                <option value="day">€ / kW día</option>
                                                <option value="month">€ / kW mes</option>
                                                <option value="year">€ / kW año</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="manual-inputs" data-gap="6">
                                        <div v-for="(_, index) of prices.power" :key="index" class="manual-input">
                                            <p class="text" data-size="12">{{ `P${index + 1}` }}</p>
                                            <div class="input-group w-100">
                                                <input
                                                    :placeholder="powerPricePeriod === 'day' ? '€ / kW día' : powerPricePeriod === 'month' ? '€ / kW mes' : '€ / kW año'"
                                                    v-model.trim="prices.power[index]" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="manual-block">
                                    <p class="text" data-size="16">Precios de energía (€/kWh)</p>
                                    <div class="manual-inputs" data-gap="6">
                                        <div v-for="(_, index) of prices.energy" :key="index" class="manual-input">
                                            <p class="text" data-size="12">{{ `P${index + 1}` }}</p>
                                            <div class="input-group w-100">
                                                <input placeholder="€/kWh" v-model.trim="prices.energy[index]" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="manual-block">
                                    <p class="text" data-size="16">Excedentes (€/kWh)</p>
                                    <div class="manual-inputs single-column" data-gap="6">
                                        <div class="manual-input">
                                            <p class="text" data-size="12">Batería virtual</p>
                                            <div class="input-group w-100">
                                                <input placeholder="€/mes" v-model.trim="surplus.virtualBattery" />
                                            </div>
                                        </div>
                                        <div class="manual-input">
                                            <p class="text" data-size="12">Cantidad</p>
                                            <div class="input-group w-100">
                                                <input placeholder="kWh" v-model.trim="surplus.amount" />
                                            </div>
                                        </div>
                                        <div class="manual-input">
                                            <p class="text" data-size="12">Precio</p>
                                            <div class="input-group w-100">
                                                <input placeholder="€/kWh" v-model.trim="surplus.price" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--Selectores-->
                            <div v-if="optionSelected !== ''" class="mt-20 product-type">
                                <p class="text opacity-6 text-center" data-size="16">Tipo de producto</p>

                                <div class="product-type-options d-flex justify-between" data-gap="10">
                                    <label :class="['product-type-option', { selected: filters.residencial }]" for="residencial">
                                        <input type="checkbox" id="residencial" v-model="filters.residencial">
                                        <span><i class="far fa-house mr-5"></i>Residencial</span>
                                    </label>
                                    <label :class="['product-type-option', { selected: filters.pyme }]" for="pyme">
                                        <input type="checkbox" id="pyme" v-model="filters.pyme">
                                        <span><i class="far fa-building mr-5"></i>PYME</span>
                                    </label>
                                </div>

                                <button class="custom-button text-center w-100" data-size="regular">Comparar</button>
                            </div>
                        </template>

                    </div>
                </form>
                <div v-if="!viewInputs">
                    <div :class="['d-flex justify-between', {'mt-10' : !isDesktopView}]">
                        <div class="d-flex" data-gap="15">
                            <button class="custom-button" data-size="small" @click="resetComparator">
                                <i class="fa-solid fa-arrow-left"></i> Volver
                            </button>
                            <h3 :class="['text ellipsis mr-5', { 'd-none': !isDesktopView }]">{{ optionSelected === 'bill' ?
                                opportunityData.name : cups !== "" ? cups : `Comparativa ${fee}` }}</h3>
                        </div>
                        <button :class="[{ 'cursor-not-allowed': editingInputs }, 'custom-button']" data-bg="azul"
                                data-mode="translucent" data-size="small" data-weight="600" @click="togglePeriod">
                            <i class="fa-light fa-calendar-range"></i> {{ period === "month" ? "Mes" : "Año" }}
                        </button>
                    </div>
                    <div class="separator h-2-px mx-auto"></div>
                    <div class="relPos form">
                        <template v-if="!editingInputs">
                            <p v-if="optionSelected === 'bill'" data-size="16"><span class="opacity-6">CUPS:</span> <span
                                data-weight="600">{{ cups }}</span></p>
                            <p v-if="opportunityData.currentMarketer !== ''" data-size="16"><span class="opacity-6">Comercializadora:</span> <span
                                data-weight="600">{{ opportunityData.currentMarketer }}</span></p>
                            <p data-size="16"><span class="opacity-6"><i class="fa-light fa-sigma"></i> Total:</span> <span
                                data-weight="600">{{ currentTotal.toLocaleString("es-ES", {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}€</span></p>
                            <p data-size="16"><span class="opacity-6"><i class="fa-light fa-utility-pole-double"></i>
                                Tarifa:</span> {{ fee }}</p>
                            <p v-if="optionSelected === 'manual' || totalDays !== this.dates.end?.diff(this.dates.start, 'days')" data-size="16"><span class="opacity-6"><i
                                class="fa-light fa-calendar-range"></i> Días:</span> {{ totalDays }} días</p>
                            <p v-else data-size="16"><span class="opacity-6"><i class="fa-light fa-calendar-range"></i>
                                Fechas:</span> {{ getPrettyDate(dates.start) }} al {{ getPrettyDate(dates.end) }}
                                ({{ totalDays }} días)</p>
                            <p data-size="16"><span class="opacity-6"><i class="fa-light fa-square-bolt"></i> Potencia
                                máxima:</span> {{ maxPower.toLocaleString("es-ES") }} kW</p>
                            <p data-size="16"><span class="opacity-6"><i class="fa-light fa-gauge-circle-bolt"></i>
                                Consumo:</span> {{Math.round(consumptionData.consumption.reduce((acc, value) => acc +
                                parseStringToNumber(value), 0)).toLocaleString("es-ES")}} kWh</p>
                            <p data-size="16"><span class="opacity-6"><i class="fa-light fa-gauge-circle-bolt"></i> Consumo
                                estimado anual:</span> {{ Math.round(manualYearConsumption ? manualYearConsumption : totalConsumption).toLocaleString("es-ES") }} kWh
                            </p>
                        </template>
                        <template v-else>
                            <div class="d-flex mt-15" data-gap="10">
                                <p class="opacity-6" data-size="16">Total:</p>
                                <input class="simple-input w-100-px-max text text-end" data-size="16" v-model="currentTotal" @input="normalizeCurrentTotal" /> €
                            </div>
                            <div class="d-flex align-center text form-group" data-gap="10">
                                <p class="opacity-6" data-size="16">Tarifa:</p>
                                <div class="input-group">
                                    <select v-model="fee">
                                        <option value="">Selecciona una tarifa</option>
                                        <option value="2.0TD">Tarifa 2.0TD</option>
                                        <option value="3.0TD">Tarifa 3.0TD</option>
                                        <option value="6.1TD">Tarifa 6.1TD</option>
                                    </select>
                                </div>
                            </div>
                            <div class="text form-group">
                                <template v-if="optionSelected !== 'manual'">
                                    <div class="d-flex" data-gap="10">
                                        <p class="opacity-6" data-size="16">Fechas:</p>
                                        <div class="input-group">
                                            <select v-model="cupsInterval"
                                                    @change="toggleIntervalConsumption">
                                                <option v-if="optionSelected === 'bill'" value="bill">Factura</option>
                                                <option :value="index" v-for="(interval, index) of cupsIntervalsData">
                                                    {{ interval.startDate }} al {{ interval.endDate }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </template>
                                <div class="d-flex mt-15" data-gap="10">
                                    <p class="opacity-6" data-size="16">Días:</p>
                                    <input class="simple-input w-100-px-max text text-end" data-size="16"
                                           v-model="totalDays" /> días
                                </div>
                                <div class="d-flex mt-15" data-gap="10">
                                    <p class="opacity-6" data-size="16">Consumo anual:</p>
                                    <input class="simple-input w-100-px-max text text-end" data-size="16"
                                           v-model="manualYearConsumption" /> kWh
                                </div>
                            </div>
                        </template>
                        <div class="absPos" style="top: 0; right: 0;">
                            <button v-if="!editingInputs" class="custom-button" data-size="medium" @click="editInputs">
                                <i class="far fa-pen-to-square"></i>
                            </button>
                            <div v-else class="d-flex" data-gap="5">
                                <button class="custom-button" data-size="medium" data-bg="rojo" @click="cancelEditInputs">
                                    <i class="fa-regular fa-xmark"></i>
                                </button>
                                <button class="custom-button" data-size="medium" @click="updateInputs">
                                    <i class="fa-regular fa-floppy-disk"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="separator px-20"></div>
                    <!--Datos de precios-->
                    <div class="d-flex column align-center mt-20 form">
                        <!--Visualización-->
                        <template v-if="!editingInputs">
                            <!--Potencia-->
                            <div class="d-flex column w-100">
                                <!--Total-->
                                <div class="d-flex mb-10 justify-between">
                                    <p class="text opacity-6" data-size="18"><i class="fa-duotone fa-solid fa-square-bolt mr-10"></i>Potencia</p>
                                    <p data-weight="500" data-size="18">{{ formatNumber(currentSubtotal.power?.total ?? 0) }}€</p>
                                </div>
                                <!--Por periodos-->
                                <template v-if="isDesktopView">
                                    <div class="d-grid comparator-table mx-5" v-for="period in powerPeriods" v-show="period.value > 0" :key="period.index">
                                        <p class="opacity-6">{{ `P${period.index + 1}:` }}</p>
                                        <p data-align="right">{{ formatNumber(period.value) }}</p>
                                        <p class="d-flex justify-between">
                                            <span class="mr-5">kW *</span>
                                            <span>{{formatNumber(period.price, 6)}}</span>
                                        </p>
                                        <p>{{formatPowerPricePeriod()}}</p>
                                        <p style="justify-self: end" data-weight="500">{{ formatNumber(period.subtotal) || 0 }}€</p>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="comparator-table-mobile">
                                        <div v-for="period in powerPeriods" :key="period.index" v-show="period.value > 0" class="period-row-mobile">
                                            <div class="period-row-mobile-left">
                                                <span class="period-label">P{{ period.index + 1 }}:</span>
                                                <span class="period-text">
                                                {{ formatNumber(period.value) }} kW * {{ formatNumber(period.price, 6) }} € / kW dia * {{ totalDays }} días
                                            </span>
                                            </div>
                                            <div class="period-total">{{ formatNumber(period.subtotal) || 0 }}€</div>
                                        </div>
                                    </div>
                                </template>
                                <!--Descuento-->
                                <div class="d-flex justify-between mx-5" v-if="parseStringToNumber(prices.powerDiscount) > 0">
                                    <p>
                                        <span class="opacity-6">Descuento: </span>
                                        {{formatNumber((currentSubtotal.power?.total ?? 0) - (currentSubtotal.power?.discount ?? 0))}}€ - {{prices.powerDiscount}}%
                                    </p>
                                    <p style="justify-self: end" data-weight="500">{{ formatNumber(currentSubtotal.power.discount)}}€</p>
                                </div>
                            </div>
                            <!--Energía-->
                            <div class="d-flex column w-100 mt-20">
                                <!--Total-->
                                <div class="d-flex mb-10 justify-between">
                                    <p class="text opacity-6" data-size="18"><i class="fa-duotone fa-solid fa-gauge-circle-bolt mr-10"></i>Consumo</p>
                                    <p data-weight="500" data-size="18">{{ formatNumber(currentSubtotal.energy?.total ?? 0) }}€</p>
                                </div>
                                <!--Por periodos-->
                                <template v-if="isDesktopView">
                                    <div class="d-grid comparator-table mx-5" v-for="period in energyPeriods" :key="period.index" v-show="period.value > 0">
                                        <p class="opacity-6">{{ `P${period.index + 1}:` }}</p>
                                        <p data-align="right">{{ formatNumber(period.value) }}</p>
                                        <p class="d-flex justify-between">
                                            <span class="mr-5">kWh *</span>
                                            <span>{{formatNumber(period.price, 6)}}</span>
                                        </p>
                                        <p> €/kWh</p>
                                        <p style="justify-self: end" data-weight="500">{{ formatNumber(period.subtotal) || 0 }}€</p>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="comparator-table-mobile">
                                        <div v-for="period in energyPeriods" :key="period.index" v-show="period.value > 0" class="period-row-mobile">
                                            <div class="period-row-mobile-left">
                                                <span class="period-label">P{{ period.index + 1 }}:</span>
                                                <span class="period-text">
                                                {{ formatNumber(period.value) }} kWh * {{ formatNumber(period.price, 6) }} €/kWh
                                            </span>
                                            </div>
                                            <div class="period-total">{{ formatNumber(period.subtotal) || 0 }}€</div>
                                        </div>
                                    </div>
                                </template>
                                <!--Descuento-->
                                <div class="d-flex justify-between mx-5" v-if="parseStringToNumber(prices.energyDiscount) > 0">
                                    <p>
                                        <span class="opacity-6">Descuento: </span>
                                        {{formatNumber((currentSubtotal.energy?.total ?? 0) - (currentSubtotal.energy?.discount ?? 0))}}€ - {{prices.energyDiscount}}%
                                    </p>
                                    <p style="justify-self: end" data-weight="500">{{ formatNumber(currentSubtotal.energy.discount)}}€</p>
                                </div>
                            </div>
                            <!--Excedentes-->
                            <div v-if="currentSubtotal.surplus.total !== 0" class="d-flex column w-100 mt-20">
                                <!--Total-->
                                <div class="d-flex mb-10 justify-between">
                                    <p class="text opacity-6" data-size="18"><i class="fa-duotone fa-solid fa-solar-panel mr-10"></i>Excedentes</p>
                                    <p data-weight="500" data-size="18">{{ formatNumber(currentSubtotal.surplus.total) }}€</p>
                                </div>
                                <!--Conceptos-->
                                <div class="d-flex column mx-5" data-gap="5">
                                    <div class="d-flex justify-between">
                                        <p><span class="opacity-6">Batería virtual:</span> {{formatNumber(surplus.virtualBattery)}} €/mes</p>
                                        <p data-weight="500">{{ formatNumber(formatNumber(currentSubtotal.surplus.virtualBattery)) }}€</p>
                                    </div>
                                    <div class="d-flex justify-between">
                                        <p><span class="opacity-6">Excedentes:</span> {{formatNumber(surplus.amount)}} kWh * {{formatNumber(surplus.price)}} €/kWh</p>
                                        <p data-weight="500">{{ formatNumber(currentSubtotal.surplus.compensation) }}€</p>
                                    </div>
                                </div>
                            </div>

                            <!--Fees-->
                            <div
                                v-if="filters.variable || filters.indexed"
                                class="d-flex column w-100 mt-20"
                            >
                                <div class="d-flex mb-10 justify-between">
                                    <p class="text opacity-6" data-size="18">
                                        <i class="fa-solid fa-money-bill-wave mr-10"></i>Fees
                                    </p>
                                </div>

                                <div class="d-flex column mx-5" data-gap="5">
                                    <div class="d-flex justify-between">
                                        <p>
                                            <span class="opacity-6">Fee potencia:</span>
                                            {{ formatNumber(priceFees.power || 0) }} € / kW mes
                                        </p>
                                    </div>

                                    <div class="d-flex justify-between">
                                        <p>
                                            <span class="opacity-6">Fee energía:</span>
                                            {{ formatNumber(priceFees.energy || 0) }} € MWh
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!--Servicio ajuste-->
                            <div class="d-flex column w-100 mt-20">
                                <div class="d-flex mb-10 justify-between">
                                    <p class="text opacity-6" data-size="18">
                                        <i class="fa-solid fa-scale-balanced mr-10"></i>Servicio ajuste
                                    </p>
                                </div>

                                <div class="d-flex column mx-5" data-gap="5">
                                    <div class="d-flex justify-between">
                                        <p>
                                            <span class="opacity-6">Valor:</span>
                                            {{ formatNumber(adjustmentServiceValue || 0) }} € / MWh
                                        </p>
                                    </div>
                                </div>
                            </div>


                            <!--Impuestos-->
                            <div v-if="applyTaxes" class="d-flex column w-100 mt-20">
                                <!--Total-->
                                <div class="d-flex mb-10 justify-between">
                                    <p class="text opacity-6" data-size="18"><i class="fa-duotone fa-solid fa-file-invoice-dollar mr-10"></i>Impuestos</p>
                                    <p data-weight="500" data-size="18">{{ formatNumber(currentSubtotal.taxes.total) }}€</p>
                                </div>
                                <!--Conceptos-->
                                <div class="d-flex column mx-5" data-gap="5">
                                    <div class="d-flex justify-between">
                                        <p><span class="opacity-6">IVA:</span> {{taxes.iva}} %</p>
                                        <p data-weight="500">{{ formatNumber(currentSubtotal.taxes.iva) }}€</p>
                                    </div>
                                    <div class="d-flex justify-between">
                                        <p><span class="opacity-6">Impuesto eléctrico:</span> {{taxes.electricTax}} %</p>
                                        <p data-weight="500">{{ formatNumber(currentSubtotal.taxes.electricTax) }}€</p>
                                    </div>
                                    <div class="d-flex justify-between">
                                        <p><span class="opacity-6">Aportación bono social:</span> {{taxes.socialBonus}} €/día</p>
                                        <p data-weight="500">{{ formatNumber(currentSubtotal.taxes.socialBonus) }}€</p>
                                    </div>
                                    <div class="d-flex justify-between">
                                        <p><span class="opacity-6">Alquiler equipo medida:</span> {{formatNumber(taxes.meterDevice)}} {{formatMeterDevicePricePeriod()}}</p>
                                        <p data-weight="500">{{ formatNumber(currentSubtotal.taxes.meterDevice) }}€</p>
                                    </div>
                                </div>
                            </div>
                            <!--Conceptos Extra-->
                            <div v-if="otherConcepts.length > 0" class="d-flex column w-100 mt-20">
                                <!--Total-->
                                <div class="d-flex mb-10 justify-between">
                                    <p class="text opacity-6" data-size="18"><i class="fa-duotone fa-solid fa-file-lines mr-10"></i>Otros conceptos</p>
                                    <p data-weight="500" data-size="18">{{ formatNumber(currentSubtotal.otherConcepts) }}€</p>
                                </div>
                                <!--Conceptos-->
                                <div class="d-flex column mx-5" data-gap="5">
                                    <div class="d-flex justify-between" v-for="concept of otherConcepts" :key="concept.name">
                                        <div class="d-flex align-center opacity-6" data-gap="5">
                                            <div class="d-flex w-35-px" data-size="12" data-gap="5">
                                                <i v-if="concept.offers" class="far fa-shop" />
                                                <i v-if="concept.electricTax" class="far fa-lightbulb-dollar" />
                                            </div>
                                            <p>{{ concept.name }}</p>
                                        </div>
                                        <p data-weight="500">{{ formatNumber(concept.value) }}€</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <!--Edición-->
                        <template v-else>
                            <div class="d-flex column w-100 form-group">
                                <p class="text mb-10 opacity-6" data-size="16">Potencia contratada (kW)</p>
                                <div class="d-flex justify-center" data-gap="5">
                                    <div v-for="(_, index) of consumptionData.power" :key="index">
                                        <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                        <div class="input-group w-80-px-max">
                                            <input placeholder="kW" v-model.trim="consumptionData.power[index]"
                                                   :disabled="!editingInputs" />
                                        </div>
                                    </div>
                                </div>
                                <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                            </div>
                            <div class="d-flex column w-100 form-group">
                                <p class="text mb-10 opacity-6" data-size="16">Energía consumida (kWh)</p>
                                <div class="d-flex justify-center" data-gap="5">
                                    <div v-for="(_, index) of consumptionData.consumption" :key="index">
                                        <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                        <div class="input-group w-80-px-max">
                                            <input placeholder="kWh" v-model.trim="consumptionData.consumption[index]"
                                                   :disabled="!editingInputs" />
                                        </div>
                                    </div>
                                </div>
                                <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                            </div>
                            <div class="d-flex column w-100 form-group">
                                <div class="d-flex" data-gap="15">
                                    <p class="text mb-10 opacity-6" data-size="16">Precios de potencia</p>
                                    <div class="input-group h-30-px mr-35">
                                        <select v-model="powerPricePeriod" :disabled="!editingInputs">
                                            <option value="day">€ / kW día</option>
                                            <option value="month">€ / kW mes</option>
                                            <option value="year">€ / kW año</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex justify-center" data-gap="5">
                                    <div v-for="(_, index) of prices.power" :key="index">
                                        <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                        <div class="input-group w-80-px-max">
                                            <input
                                                :placeholder="powerPricePeriod === 'day' ? '€ / kW día' : powerPricePeriod === 'month' ? '€ / kW mes' : '€ / kW año'"
                                                v-model.trim="prices.power[index]" :disabled="!editingInputs" />
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-center align-center mt-10" data-gap="5">
                                    <p class="text ml-10">Descuento:</p>
                                    <div class="input-group w-80-px-max">
                                        <input placeholder="%" v-model.trim="prices.powerDiscount"
                                               :disabled="!editingInputs" />
                                    </div>
                                    <p class="text">%</p>
                                </div>
                                <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                            </div>
                            <div class="d-flex column w-100 form-group">
                                <p class="text mb-10 opacity-6" data-size="16">Precios de energía (€/kWh)</p>
                                <div class="d-flex justify-center" data-gap="5">
                                    <div v-for="(_, index) of prices.energy" :key="index">
                                        <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                        <div class="input-group w-80-px-max">
                                            <input placeholder="€/kWh" v-model.trim="prices.energy[index]"
                                                   :disabled="!editingInputs" />
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-center align-center mt-10" data-gap="5">
                                    <p class="text ml-10">Descuento:</p>
                                    <div class="input-group w-80-px-max">
                                        <input placeholder="%" v-model.trim="prices.energyDiscount"
                                               :disabled="!editingInputs" />
                                    </div>
                                    <p class="text">%</p>
                                </div>
                                <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                            </div>
                            <div class="d-flex column w-100 form-group">
                                <p class="text mb-10 opacity-6" data-size="16">Excedentes (€/kWh)</p>
                                <div class="d-flex justify-center" data-gap="5">
                                    <div>
                                        <p class="text ml-10">Batería virtual</p>
                                        <div class="input-group w-150-px-max">
                                            <input placeholder="€/mes" v-model.trim="surplus.virtualBattery"
                                                   :disabled="!editingInputs" />
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text ml-10">Cantidad</p>
                                        <div class="input-group w-150-px-max">
                                            <input placeholder="kWh" v-model.trim="surplus.amount" :disabled="!editingInputs" />
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text ml-10">Precio</p>
                                        <div class="input-group w-150-px-max">
                                            <input placeholder="€/kWh" v-model.trim="surplus.price"
                                                   :disabled="!editingInputs" />
                                        </div>
                                    </div>
                                </div>
                                <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                            </div>

                            <!--Edición de fee-->
                            <div v-if="filters.variable || filters.indexed" class="d-flex column w-100 form-group">
                                <p class="text mb-10 opacity-6" data-size="16">Fees</p>
                                <div class="d-flex justify-center" data-gap="5">
                                    <div>
                                        <p class="text ml-10">Potencia</p>
                                        <div class="input-group w-150-px-max">
                                            <input placeholder="€/kW" v-model.trim="priceFees.power"
                                                   :disabled="!editingInputs" />
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text ml-10">Energía</p>
                                        <div class="input-group w-150-px-max">
                                            <input placeholder="€/kWh" v-model.trim="priceFees.energy"
                                                   :disabled="!editingInputs" />
                                        </div>
                                    </div>
                                </div>
                                <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                            </div>

                            <!--Impuestos y otros conceptos-->
                            <div v-if="applyTaxes || editingInputs" class="d-flex column w-100 form-group" data-gap="5">
                                <div class="d-flex align-center mb-10" data-gap="15">
                                    <p class="text opacity-6" data-size="16">Impuestos</p>
                                    <input v-if="editingInputs" type="checkbox" v-model="applyTaxes" />
                                </div>
                                <div class="d-flex align-center justify-between mx-10">
                                    <p class="text" data-size="14">IVA</p>
                                    <div class="d-flex align-center" data-gap="5">
                                        <div class="input-group w-100-px-max">
                                            <input placeholder="%" v-model.trim="taxes.iva" :disabled="!editingInputs" />
                                        </div>
                                        <span class="w-45-px">%</span>
                                    </div>
                                </div>
                                <div class="d-flex align-center justify-between mx-10">
                                    <p class="text" data-size="14">Impuesto eléctrico</p>
                                    <div class="d-flex align-center" data-gap="5">
                                        <div class="input-group w-100-px-max">
                                            <input placeholder="%" v-model.trim="taxes.electricTax"
                                                   :disabled="!editingInputs" />
                                        </div>
                                        <span class="w-45-px">%</span>
                                    </div>
                                </div>
                                <div class="d-flex align-center justify-between mx-10">
                                    <p class="text" data-size="14">Aportación bono social</p>
                                    <div class="d-flex align-center" data-gap="5">
                                        <div class="input-group w-100-px-max">
                                            <input placeholder="%" v-model.trim="taxes.socialBonus"
                                                   :disabled="!editingInputs" />
                                        </div>
                                        <span class="w-45-px">€/día</span>
                                    </div>
                                </div>
                                <div class="d-flex align-center justify-between mx-10">
                                    <p class="text" data-size="14">Alquiler equipo medida</p>
                                    <div class="d-flex align-center" data-gap="5">
                                        <div class="input-group w-60-px-max">
                                            <input placeholder="€/día" v-model.trim="taxes.meterDevice"
                                                   :disabled="!editingInputs" />
                                        </div>
                                        <div class="input-group h-30-px">
                                            <select v-model="meterDevicePricePeriod" :disabled="!editingInputs">
                                                <option value="day">€/día</option>
                                                <option value="month">€/mes</option>
                                                <option value="year">€/año</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="separator w-80 mt-25 mb-20 mb-0 mx-auto"></div>
                            </div>

                            <!--Servicio ajuste-->
                            <div class="d-flex column w-100 form-group" data-gap="5">
                                <p class="text opacity-6" data-size="16">Servicio ajuste</p>

                                <div class="d-flex align-center justify-between mx-10">
                                    <p class="text" data-size="14">Valor</p>
                                    <div class="d-flex align-center" data-gap="5">
                                        <div class="input-group w-100-px-max">
                                            <input
                                                placeholder="€ / MWh"
                                                v-model.trim="adjustmentServiceValue"
                                                :disabled="!editingInputs"
                                            />
                                        </div>
                                        <span class="w-45-px">€ / MWh</span>
                                    </div>
                                </div>

                                <div class="separator w-80 mt-25 mb-20 mb-0 mx-auto"></div>
                            </div>

                            <div class="d-flex column w-100 form-group" data-gap="5">
                                <!--Otros conceptos-->
                                <p class="text opacity-6" data-size="16">Otros conceptos</p>
                                <div class="form-group d-flex align-center mb-0" data-gap="15">
                                    <div class="d-flex w-100" data-gap="10">
                                        <div class="input-group w-75" style="background-color: white"><input
                                            placeholder="Concepto" v-model="otherConcept.name"></div>
                                        <div class="d-flex items-center w-25" data-gap="5">
                                            <div class="input-group" style="background-color: white"><input
                                                placeholder="Cantidad" v-model="otherConcept.value"></div>
                                            <span data-size="17">€</span>
                                        </div>
                                    </div>
                                    <button class="custom-button" data-size="medium" @click="addOtherConcept"><i
                                        class="fa-solid fa-arrow-turn-down-left"></i></button>
                                </div>
                                <div class="d-flex ml-10 mb-20" data-gap="15">
                                    <div class="d-flex align-center pointer" @click="otherConcept.offers = !otherConcept.offers">

                                        <div class="custom-checkbox mr-10" :class="{ 'selected': otherConcept.offers }">
                                        </div>

                                        <div class="text" data-size="13">Añadir a ofertas</div>
                                    </div>
                                    <div class="d-flex align-center pointer" @click="otherConcept.electricTax = !otherConcept.electricTax">

                                        <div class="custom-checkbox mr-10" :class="{ 'selected': otherConcept.electricTax }">
                                        </div>

                                        <div class="text" data-size="13">Añadir a imp. eléctrico</div>
                                    </div>
                                </div>
                                <!--Listado-->
                                <div v-for="(concept, index) of otherConcepts" :key="concept.id" class="px-20 w-100">
                                    <div class="d-flex align-center w-100" data-gap="30">
                                        <div class="d-flex justify-between w-100" data-gap="15">
                                            <input class="text simple-input" data-size="14" v-model="concept.name" />
                                            <input class="text simple-input" data-size="14" v-model="concept.value" />
                                        </div>
                                        <i class="fa-solid fa-xmark pointer" data-size="20" @click="removeOtherConcept(index)" />
                                    </div>
                                    <div class="d-flex ml-10 mt-10 mb-20" data-gap="15">
                                        <div class="d-flex align-center pointer" @click="concept.offers = !concept.offers">

                                            <div class="custom-checkbox mr-10" :class="{ 'selected': concept.offers }">
                                            </div>

                                            <div class="text" data-size="13">Añadir a ofertas</div>
                                        </div>
                                        <div class="d-flex align-center pointer" @click="concept.electricTax = !concept.electricTax">

                                            <div class="custom-checkbox mr-10" :class="{ 'selected': concept.electricTax }">
                                            </div>

                                            <div class="text" data-size="13">Añadir a imp. eléctrico</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <!--Tabla de ofertas-->
            <div v-if="viewOffers" class="w-60">
                <div :class="['d-flex justify-between align-center form', { 'offers-header' : !isDesktopView}]">
                    <div class="d-flex align-center" data-gap="15">
                        <h2 :class="['text', {'mt-20' : !isDesktopView}]">Ofertas</h2>
                        <div class="custom-button " data-size="small" data-bg="azul" data-mode="translucent"
                             @click="viewFilters = !viewFilters">{{ viewFilters ? 'Ocultar' : 'Mostrar' }} filtros</div>
                        <div v-if="viewFilters" class="custom-button" data-size="small" data-bg="rojo"
                             data-mode="translucent" @click="resetFilters">Restablecer filtros</div>
                    </div>
                    <div class="d-flex align-center" data-gap="15">
                        <div class="custom-button" data-size="regular" data-bg="amarillo" @click="createOffer">
                            Añadir oferta <i class="fa-solid fa-plus"></i>
                        </div>
                        <div class="custom-button" data-size="regular" @click="saveComparative">
                            Guardar <i class="far fa-save" data-size="14" />
                        </div>
                    </div>
                </div>
                <!--Filtros-->
                <div v-if="viewFilters" class="mt-20 px-16">
                    <!--Filtros de comercializadoras-->
                    <p class="text" data-size="16"><i class="fa-regular fa-shop"></i> Comercializadoras</p>
                    <div class="separator"></div>
                    <div class="d-flex f-wrap" data-gap="20">
                        <div class="custom-button d-flex justify-center align-center p-10 h-100-px w-100-px"
                             :data-bg="filters.marketers.length === sortedMarketers.length ? 'azul' : 'ventana-lateral'"
                             data-mode="translucent" data-size="regular" @click="toggleMarketer()">
                            <p class="text" data-weight="600">Todas</p>
                        </div>
                        <template v-for="marketer of sortedMarketers">
                            <div class="custom-button d-flex justify-center align-center p-10 h-100-px w-100-px relPos"
                                 :data-bg="filters.marketers.includes(marketer.name) ? 'azul' : 'ventana-lateral'"
                                 data-mode="translucent" data-size="regular" @click="toggleMarketer(marketer.name)">
                                <img :src="`/assets/marketers_logo/${marketer.logo}`"
                                     class="h-80-max w-80-max contain-img" />
                            </div>
                        </template>
                    </div>
                    <!--Filtros de tipo de producto-->
                    <p class="text mt-40" data-size="16"><i class="fa-regular fa-house-building"></i> Tipo de producto</p>
                    <div class="separator"></div>
                    <div class="d-flex justify-between">
                        <!--Residencial y Pyme-->
                        <div class="d-flex" data-gap="30">
                            <div>
                                <label class="mr-10 text" for="residencial"><i
                                    class="far fa-house mr-5" />Residencial</label>
                                <input type="checkbox" id="residencial" v-model="filters.residencial">
                            </div>
                            <div>
                                <label class="mr-10 text" for="pyme"><i class="far fa-building mr-5" />PYME</label>
                                <input type="checkbox" id="pyme" v-model="filters.pyme">
                            </div>
                        </div>
                        <!--Fijo, fijo-variable y indexado-->
                        <div class="d-flex" data-gap="30">
                            <div>
                                <label class="mr-10 text" for="fixed"><i class="far fa-lock mr-5" />Fijo</label>
                                <input type="checkbox" id="fixed" v-model="filters.fixed">
                            </div>
                            <div>
                                <label class="mr-10 text" for="variable"><i
                                    class="far fa-chart-mixed mr-5" />Fijo-Variable</label>
                                <input type="checkbox" id="variable" v-model="filters.variable">
                            </div>
                            <div>
                                <label class="mr-10 text" for="indexed"><i class="far fa-chart-line mr-5" />Indexado</label>
                                <input type="checkbox" id="indexed" v-model="filters.indexed">
                            </div>
                        </div>
                        <div class="d-flex">
                            <div>
                                <label class="mr-10 text" for="surplus"><i
                                    class="far fa-solar-panel mr-5" />Excedentes</label>
                                <input type="checkbox" id="surplus" v-model="filters.surplus">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex mt-20" data-gap="30">
                        <div>
                            <label class="mr-10 text" for="withoutCommission"><i class="far fa-hand-holding-dollar mr-5" />Productos sin comisión</label>
                            <input type="checkbox" id="withoutCommission" v-model="filters.withoutCommission">
                        </div>
                        <div>
                            <label class="mr-10 text" for="withoutAdjustmentServices"><i class="far fa-scale-balanced  mr-5" />Productos sin servicios de ajuste</label>
                            <input type="checkbox" id="withoutAdjustmentServices" v-model="filters.withoutAdjustmentServices">
                        </div>
                    </div>
                </div>
                <template v-else>
                    <!--pc-->
                    <div v-if="isDesktopView" class="d-flex justify-between align-center mt-20 px-16 form">
                        <div class="d-flex align-center text form-group no-margin" data-gap="10">
                            <div class="input-group">
                                <i class="opacity-6 fa-regular fa-magnifying-glass mr-5"></i>
                                <input type="text" placeholder="Busca una oferta..." v-model="filters.search">
                            </div>
                        </div>
                        <div class="d-flex justify-between" data-gap="25">
                            <p class="text pointer" @click="selectNewOrderType('efficiency')"><i
                                class="fa-regular fa-chart-line-up"></i> Eficiencia <i
                                :class="['fas', filters.radio.sortBy.checked === 2 ? 'fa-sort-down' : (filters.radio.sortBy.checked === 3 ? 'fa-sort-up' : 'fa-sort')]" />
                            </p>
                            <div class="d-flex justify-end w-410-px mr-5" data-gap="20">
                                <p class="text text-center pointer">
                                    <i :class="[{ 'opacity-6': !viewCommissions }, 'far fa-hand-holding-dollar']"
                                       data-size="16" @click="viewCommissions = !viewCommissions"></i>
                                    <span @click="selectNewOrderType('commission')"> Com. <i
                                        :class="['fas', filters.radio.sortBy.checked === 4 ? 'fa-sort-down' : (filters.radio.sortBy.checked === 5 ? 'fa-sort-up' : 'fa-sort')]" /></span>
                                </p>
                                <p class="text w-70-px pointer" @click="selectNewOrderType('total')"><i
                                    class="far fa-euro-sign" data-size="16"></i> Total <i
                                    :class="['fas', filters.radio.sortBy.checked === 0 ? 'fa-sort-down' : (filters.radio.sortBy.checked === 1 ? 'fa-sort-up' : 'fa-sort')]" />
                                </p>
                                <p class="text w-115-px pointer" @click="selectNewOrderType('save')"><i
                                    class="far fa-piggy-bank" data-size="16"></i> Ahorro <i
                                    :class="['fas', filters.radio.sortBy.checked === 6 ? 'fa-sort-down' : (filters.radio.sortBy.checked === 7 ? 'fa-sort-up' : 'fa-sort')]" />
                                </p>
                                <p class="text"><i class="far fa-toolbox" data-size="16"></i> Opc.</p>
                            </div>
                        </div>
                    </div>
                    <!--movil-->
                    <div v-else class="d-flex justify-between align-center mt-20 px-16 form offers-sortbar mobile-sort-controls">
                        <div class="sort-select">
                            <select v-model="mobileSortMetric" @change="applyMobileSort(mobileSortMetric, mobileSortDirection)">
                                <option value="efficiency">Eficiencia</option>
                                <option value="commission">Com.</option>
                                <option value="total">Total</option>
                                <option value="save">Ahorro</option>
                            </select>
                        </div>
                        <div class="sort-select">
                            <select v-model="mobileSortDirection" @change="applyMobileSort(mobileSortMetric, mobileSortDirection)">
                                <option value="desc">Descendente</option>
                                <option value="asc">Ascendente</option>
                            </select>
                        </div>
                    </div>

                    <div class="separator"></div>
                    <!--Ofertas-->
                    <div v-if="filteredOffers.length === 0" class="text p-16" data-size="20">No hay ofertas disponibles.
                    </div>
                    <div v-for="(offer, index) of filteredOffers" :key="offerKey(offer, index)" :class="['dashboard-card column p-16 mb-10 w-100', {'offer-card-row' : !isDesktopView}]" @click="toggleOfferDetails(offer, index)">

                        <div v-if="isDesktopView" class="d-flex justify-between align-center">
                            <div class="d-flex align-center relPos">
                                <img class="my-auto h-25-px-max w-50-px-max contain-img" v-if="offer.marketer !== null"
                                     :src="`/assets/marketers_logo/${marketers.find(marketer => marketer.name === offer.marketer).logo}`"
                                     alt="logo" />
                                <p v-if="offerToEdit !== index || offer.marketer" class="text mx-10 break-word" data-size="16"
                                   data-weight="600">{{ offer.product }}</p>
                                <input v-else class="text ml-10 simple-input w-300-px-max" data-size="16" data-weight="600"
                                       v-model="offer.product" />
                                <div
                                    v-if="offer.withoutAdjustmentServices === true"
                                    class="relPos d-flex align-center adjustment-tooltip-wrapper"
                                >
                                    <i
                                        class="fas fa-scale-balanced ml-auto text"
                                        data-weight="600"
                                        data-size="16"
                                        data-color="amarillo"
                                    />
                                    <div class="adjustment-tooltip-bubble">
                                        Sin servicio de ajuste
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-end w-410-px" data-gap="10">
                                <p :class="[{ 'opacity-0': !viewCommissions }, 'text']" data-size="18" :data-weight="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? '400' : '600'" :data-color="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? 'principal' : 'success'">
                                    {{ formatNumber(offer.commission ?? 0) }}<span :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                                </p>
                                <p class="text w-70-px-min text-end" data-size="18" data-weight="600">
                                    {{ Math.round(offer.total) }}€
                                </p>
                                <div class="d-flex align-center justify-center w-130-px-min">
                                    <p class="text">{{ Math.round(offer.savePercent) }}%</p>
                                    <p class="text ml-5" data-size="20" data-weight="600"
                                       :data-color="offer.savePercent > 0 ? this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? 'success' : 'naranja' : 'rojo'">
                                        {{ Math.round(currentTotal - offer.total) }}€
                                    </p>
                                </div>
                                <div class="d-flex align-center" data-gap="10">
                                    <i class="far fa-circle-info text pointer" data-size="16"
                                       @click.stop="offer.viewPrices = !offer.viewPrices"></i>
                                    <i class="far fa-file-pdf text pointer" data-size="16"
                                       @click.stop="createPDFForm(offer)"></i>
                                    <i class="far fa-user-plus text pointer" data-size="16"
                                       @click.stop="createOpportunity(offer)"></i>
                                </div>
                            </div>
                        </div>
                        <div v-else class="d-flex align-center justify-between w-100">
                            <div class="d-flex align-center relPos offer-card-product">
                                <div class="offer-card-icon d-flex align-center justify-center">
                                    <img v-if="offer.marketer !== null"
                                         class="my-auto h-25-px-max w-50-px-max contain-img"
                                         :src="`/assets/marketers_logo/${marketers.find(marketer => marketer.name === offer.marketer).logo}`"
                                         alt="logo" />
                                    <div v-else class="offer-card-icon-placeholder"></div>
                                </div>

                                <div class="offer-card-title">
                                    <p v-if="offerToEdit !== index || offer.marketer" class="text ellipsis" data-size="16" data-weight="600">
                                        {{ offer.product }}
                                    </p>
                                    <input v-else class="text simple-input w-300-px-max" data-size="16" data-weight="600"
                                           v-model="offer.product" />
                                </div>
                            </div>

                            <div class="d-flex offer-card-right" data-gap="10">
                            <div class="offer-card-col offer-card-commission-col">
                                <p v-if="isDesktopView ? viewCommissions : true" :class="[{ 'w-70-px': isDesktopView }, 'text']" data-size="14" :data-weight="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? '400' : '600'" :data-color="this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? 'principal' : 'success'">
                                    {{ formatNumber(offer.commission ?? 0) }}
                                    <span :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                                </p>
                            </div>
                            <div class="offer-card-col offer-card-total-col">
                                <div :class="['offer-card-total text text-end', { 'w-70-px': isDesktopView } ]" data-size="14" data-weight="600">
                                    {{ offer?.total != null ? Math.round(offer.total) : '' }}€
                                </div>
                            </div>
                            <div class="offer-card-col offer-card-saving-col">
                                <div :class="['offer-card-saving d-flex align-center justify-center', { 'w-115-px': isDesktopView } ]" data-gap="4">
                                    <p class="text">{{ offer?.savePercent != null ? Math.round(offer.savePercent) : '' }}%</p>
                                    <p class="text" data-size="14" data-weight="600"
                                       :data-color="(offer?.savePercent ?? -1) > 0 ? (this.basicData.userSubdomain._id !== '68d260e6bc9e8c38f8003df2' ? 'success' : 'naranja') : 'rojo'">
                                        {{ offer?.total != null ? Math.round(currentTotal - offer.total) : '' }}€
                                    </p>
                                </div>
                            </div>
                            <div class="offer-card-col offer-card-actions-col">
                                <div v-if="isDesktopView" class="d-flex align-center offer-card-actions" data-gap="10">
                                    <i class="far fa-circle-info text pointer" data-size="16"
                                       @click.stop="offer.viewPrices = !offer.viewPrices"></i>
                                    <i v-if="isDesktopView" class="far fa-file-pdf text pointer" data-size="16"
                                       @click.stop="createPDFForm(offer)"></i>
                                    <i class="far fa-user-plus text pointer" data-size="16"
                                       @click.stop="createOpportunity(offer)"></i>
                                </div>
                                <div v-else class="d-flex align-center justify-end offer-card-actions">
                                    <i class="fa-solid fa-chevron-down offer-card-expand-icon"
                                       :class="{ 'fa-rotate-180': offer.viewPrices }"></i>
                                </div>
                            </div>
                        </div>
                        </div>

                        <transition name="accordion" v-on:before-enter="beforeEnter" v-on:enter="enter" v-on:before-leave="beforeLeave" v-on:leave="leave" @click.stop>
                            <div v-if="offer.viewPrices" class="mt-20 w-100">
                                <!--pc-->
                                <template v-if="isDesktopView">
                                    <div class="d-flex mt-20 hidden">
                                        <div class="d-flex column align-center form ml-15">
                                            <div class="d-flex w-100 form-group" data-gap="5">
                                                <p class="text mt-15 w-140-px" data-size="16">Ahorro:</p>
                                                <div class="d-flex column w-90-px-min">
                                                    <p class="text text-center">Potencia</p>
                                                    <p class="text text-center" data-size="14">
                                                        {{ formatNumber(currentSubtotal.power.total - offer.subTotal.power.total) }}€</p>
                                                </div>
                                                <div class="d-flex column w-90-px-min">
                                                    <p class="text text-center">Energía</p>
                                                    <p class="text text-center" data-size="14">
                                                        {{ formatNumber(currentSubtotal.energy.total - offer.subTotal.energy.total) }}€</p>
                                                </div>
                                                <div class="d-flex column w-90-px-min" v-if="surplus.amount">
                                                    <p class="text text-center">Excedentes</p>
                                                    <p class="text text-center" data-size="14">
                                                        {{ formatNumber(currentSubtotal.surplus.total - offer.subTotal.surplus.total) }}€</p>
                                                </div>
                                                <div class="d-flex column w-90-px-min" v-if="surplus.virtualBattery">
                                                    <p class="text text-center">Batería virtual</p>
                                                    <p class="text text-center" data-size="14">
                                                        {{ formatNumber(currentSubtotal.surplus.virtualBattery -
                                                        offer.subTotal.surplus.virtualBattery) }}€</p>
                                                </div>
                                            </div>
                                            <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                                            <div class="d-flex align-center w-100 form-group" data-gap="10">
                                                <p class="text mt-15 w-140-px" data-size="16">Potencia:</p>
                                                <div class="d-flex" data-gap="5">
                                                    <div v-for="(_, i) of offer.prices.power" :key="i">
                                                        <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                        <div class="input-group w-90-px-max">
                                                            <input placeholder="€ / kW día" v-model.trim="offer.prices.power[i]"
                                                                   :disabled="offerToEdit !== index" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-center w-100 form-group" data-gap="10">
                                                <p class="text mt-15 w-140-px" data-size="16">Energía:</p>
                                                <div class="d-flex" data-gap="5">
                                                    <div v-if="offer.priceType !== 'indexed' || period === 'year'" v-for="(_, i) of offer.prices.energy" :key="i">
                                                        <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                        <div class="input-group w-90-px-max">
                                                            <input placeholder="€/kWh" v-model.trim="offer.prices.energy[i]"
                                                                   :disabled="offerToEdit !== index" />
                                                        </div>
                                                    </div>
                                                    <div v-else v-for="(_, i) of offer.prices.energy" :key="`indexed-${i}`">
                                                        <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                        <div class="input-group w-90-px-max">
                                                            <input placeholder="€/kWh"
                                                                :value="offer.prices.history?.[currentMonth]?.consume?.[`P${i + 1}`] ?? offer.prices.energy[i]"
                                                                @input="e => { if (offer.prices.history?.[currentMonth]?.consume) offer.prices.history[currentMonth].consume[`P${i + 1}`] = e.target.value }"
                                                                :disabled="offerToEdit !== index" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div
                                                v-if="offer.withoutAdjustmentServices === true"
                                                class="d-flex align-center w-100 form-group"
                                                data-gap="10"
                                            >
                                                <p class="text mt-15 w-140-px" data-size="16">Total energía:</p>
                                                <div class="d-flex" data-gap="5">
                                                    <div v-for="(_, i) of offer.prices.energy" :key="`total-energy-${i}`">
                                                        <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                        <div class="input-group w-90-px-max">
                                                            <input
                                                                :value="
                                                        parseStringToNumber(offer.prices.energy[i]) === 0
                                                            ? '0'
                                                            : (
                                                                parseStringToNumber(offer.prices.energy[i]) +
                                                                (parseStringToNumber(adjustmentServiceValue || 0) / 1000)
                                                            ).toFixed(6)
                                                    "
                                                                disabled
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!--Fijo-Variable-->
                                            <template v-if="offer.fees">
                                                <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                                                <div class="d-flex w-100 form-group" data-gap="10">
                                                    <p class="text mt-15 w-140-px" data-size="16">Fee potencia:</p>
                                                    <div class="d-flex" data-gap="5">
                                                        <div v-for="(_, i) of offer.prices.power" :key="i">
                                                            <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                            <div class="input-group w-90-px-max">
                                                                <input placeholder="€ / kW mes" v-model.trim="offer.fees.power[i]"
                                                                       :disabled="offerToEdit !== index" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex w-100 form-group" data-gap="10">
                                                    <p class="text mt-15 w-140-px" data-size="16">Fee energía:</p>
                                                    <div class="d-flex" data-gap="5">
                                                        <div v-for="(_, i) of offer.prices.energy" :key="i">
                                                            <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                            <div class="input-group w-90-px-max">
                                                                <input placeholder="€ MWh" v-model.trim="offer.fees.energy[i]"
                                                                       :disabled="offerToEdit !== index" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                                                <div class="d-flex w-100 form-group" data-gap="10">
                                                    <p class="text mt-15 w-140-px" data-size="16">Total potencia:</p>
                                                    <div class="d-flex" data-gap="5">
                                                        <div v-for="(_, i) of offer.prices.power" :key="i">
                                                            <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                            <div class="input-group w-90-px-max">
                                                                <input
                                                                    :value="parseStringToNumber(offer.prices.power[i]) + (parseStringToNumber(offer.fees.power[i]) / 30)"
                                                                    disabled />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex w-100 form-group" data-gap="10">
                                                    <p class="text mt-15 w-140-px" data-size="16">Total energía:</p>
                                                    <div class="d-flex" data-gap="5">
                                                        <div v-if="offer.priceType !== 'indexed' || period === 'year'" v-for="(_, i) of offer.prices.energy" :key="i">
                                                            <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                            <div class="input-group w-90-px-max">
                                                                <input
                                                                    :value="parseStringToNumber(offer.prices.energy[i]) + (parseStringToNumber(offer.fees.energy[i]) / 1000)"
                                                                    disabled />
                                                            </div>
                                                        </div>
                                                        <div v-else v-for="(_, i) of offer.prices.energy" :key="`indexed-${i}`">
                                                            <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                            <div class="input-group w-90-px-max">
                                                                <input
                                                                    :value="parseStringToNumber(offer.prices.history?.[currentMonth]?.consume?.[`P${i + 1}`] ?? offer.prices.energy[i]) + (parseStringToNumber(offer.fees.energy[i]) / 1000)"
                                                                    disabled />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </template>


                                            <!--Excedentes-->
                                            <template v-if="offer.surplus">

                                                <div class="separator"></div>
                                                <div class="d-flex w-100 form-group" data-gap="10">
                                                    <p class="text mt-15 w-140-px" data-size="16">Excedentes:</p>
                                                    <div class="d-flex" data-gap="5">
                                                        <div>
                                                            <p class="text ml-10">Batería virtual</p>
                                                            <div class="input-group w-120-px-max">
                                                                <input placeholder="€/mes"
                                                                       v-model.trim="offer.surplus.virtualBattery"
                                                                       :disabled="offerToEdit !== index" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex" data-gap="5">
                                                        <div>
                                                            <p class="text ml-10">Precio</p>
                                                            <div class="input-group w-90-px-max">
                                                                <input placeholder="€/kWh" v-model.trim="offer.surplus.price"
                                                                       :disabled="offerToEdit !== index" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="mx-auto">
                                            <button v-if="offerToEdit !== index" class="custom-button" data-size="regular"
                                                    @click.stop="editOffer(index)">
                                                <i class="far fa-pen-to-square mr-5"></i>Editar
                                            </button>
                                            <div v-else class="d-flex column justify-center" data-gap="10">
                                                <button class="custom-button" data-size="regular" @click.stop="updateOffer">
                                                    <i class="fa-regular fa-floppy-disk mr-5"></i>Guardar
                                                </button>
                                                <button class="custom-button" data-size="regular" data-bg="rojo"
                                                        @click.stop="cancelEditOffer">
                                                    <i class="fa-regular fa-xmark mr-5"></i>Cancelar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <!--móvil-->
                                <template v-else>
                                    <div class="offer-details">
                                        <div class="offer-details-header">
                                            <p class="offer-details-title">{{ offer.product }}</p>
                                            <div class="offer-details-actions-header d-flex" data-gap="10">
                                                <button v-if="offerToEdit !== index" type="button" class="icon-button" @click.stop="editOffer(index)">
                                                    <i class="far fa-pen-to-square text"></i>
                                                </button>
                                                <template v-else>
                                                    <button class="icon-button" @click.stop="updateOffer">
                                                        <i class="fa-regular fa-floppy-disk text"></i>
                                                    </button>
                                                    <button class="icon-button" @click.stop="cancelEditOffer">
                                                        <i class="fa-regular fa-xmark text"></i>
                                                    </button>
                                                </template>
                                                <button class="icon-button" @click.stop="createPDFForm(offer)">
                                                    <i class="far fa-file-pdf text"></i>
                                                </button>
                                                <button class="icon-button" @click.stop="createOpportunity(offer)">
                                                    <i class="far fa-user-plus text"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="offer-details-savings-overview">
                                            <p class="offer-details-savings-overview-title">Ahorro</p>
                                            <div class="offer-details-savings-overview-grid">
                                                <div class="offer-details-savings-overview-item">
                                                    <p class="offer-details-savings-overview-label">Potencia</p>
                                                    <p class="offer-details-savings-overview-value">
                                                        {{ formatNumber((currentSubtotal.power?.total ?? 0) - (offer.subTotal?.power?.total ?? 0)) }}€
                                                    </p>
                                                </div>
                                                <div class="offer-details-savings-overview-item">
                                                    <p class="offer-details-savings-overview-label">Energía</p>
                                                    <p class="offer-details-savings-overview-value">
                                                        {{ formatNumber((currentSubtotal.energy?.total ?? 0) - (offer.subTotal?.energy?.total ?? 0)) }}€
                                                    </p>
                                                </div>
                                                <div class="offer-details-savings-overview-item" v-if="surplus.amount">
                                                    <p class="offer-details-savings-overview-label">Excedentes</p>
                                                    <p class="offer-details-savings-overview-value">
                                                        {{ formatNumber((currentSubtotal.surplus?.total ?? 0) - (offer.subTotal?.surplus?.total ?? 0)) }}€
                                                    </p>
                                                </div>
                                                <div class="offer-details-savings-overview-item" v-if="surplus.virtualBattery">
                                                    <p class="offer-details-savings-overview-label">Batería virtual</p>
                                                    <p class="offer-details-savings-overview-value">
                                                        {{ formatNumber((currentSubtotal.surplus?.virtualBattery ?? 0) - (offer.subTotal?.surplus?.virtualBattery ?? 0)) }}€
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="offer-details-section">
                                            <p class="offer-details-section-title">Potencia</p>
                                            <div class="offer-details-section-grid">
                                                <div class="offer-details-section-col">
                                                    <div class="offer-details-cell" v-for="(price, i) in offer.prices.power.slice(0, 3)" :key="`mp-${i}`">
                                                        <p class="offer-details-cell-label">{{ `P${i + 1}` }}</p>
                                                        <p class="offer-details-cell-value">
                                                            <template v-if="offerToEdit === index">
                                                                <input class="simple-input" v-model.trim="offer.prices.power[i]" />
                                                            </template>
                                                            <template v-else>
                                                                {{ price }}€
                                                            </template>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="offer-details-section-col">
                                                    <div class="offer-details-cell" v-for="(price, i) in offer.prices.power.slice(3, 6)" :key="`mp2-${i}`">
                                                        <p class="offer-details-cell-label">{{ `P${i + 4}` }}</p>
                                                        <p class="offer-details-cell-value">
                                                            <template v-if="offerToEdit === index">
                                                                <input class="simple-input" v-model.trim="offer.prices.power[i + 3]" />
                                                            </template>
                                                            <template v-else>
                                                                {{ price }}€
                                                            </template>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="offer-details-section">
                                            <p class="offer-details-section-title">Energía</p>
                                            <div class="offer-details-section-grid">
                                                <div class="offer-details-section-col">
                                                    <div class="offer-details-cell" v-for="(price, i) in offer.prices.energy.slice(0, 3)" :key="`me-${i}`">
                                                        <p class="offer-details-cell-label">{{ `P${i + 1}` }}</p>
                                                        <p class="offer-details-cell-value">
                                                            <template v-if="offerToEdit === index">
                                                                <input class="simple-input" v-model.trim="offer.prices.energy[i]" />
                                                            </template>
                                                            <template v-else>
                                                                {{ price }}€
                                                            </template>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="offer-details-section-col">
                                                    <div class="offer-details-cell" v-for="(price, i) in offer.prices.energy.slice(3, 6)" :key="`me2-${i}`">
                                                        <p class="offer-details-cell-label">{{ `P${i + 4}` }}</p>
                                                        <p class="offer-details-cell-value">
                                                            <template v-if="offerToEdit === index">
                                                                <input class="simple-input" v-model.trim="offer.prices.energy[i + 3]" />
                                                            </template>
                                                            <template v-else>
                                                                {{ price }}€
                                                            </template>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="offer-details-excedentes" v-if="surplus.amount || surplus.virtualBattery || (offer.surplus && offer.surplus.price)">
                                            <p class="offer-details-section-title">Excedentes</p>
                                            <div class="offer-details-excedentes-grid">
                                                <div class="excedente-box" v-if="surplus.amount">
                                                    <p class="excedente-label">Excedentes</p>
                                                    <p class="excedente-value">
                                                        <template v-if="offerToEdit === index">
                                                            <!--<input class="simple-input" v-model.trim="offer.surplus.total" />-->
                                                        </template>
                                                        <template v-else>
                                                            {{ formatNumber((currentSubtotal.surplus?.total ?? 0) - (offer.subTotal?.surplus?.total ?? 0)) }}€
                                                        </template>
                                                    </p>
                                                </div>
                                                <div class="excedente-box" v-if="surplus.virtualBattery">
                                                    <p class="excedente-label">Batería virtual</p>
                                                    <p class="excedente-value">
                                                        <template v-if="offerToEdit === index">
                                                            <input class="simple-input" v-model.trim="offer.surplus.virtualBattery" />
                                                        </template>
                                                        <template v-else>
                                                            {{ formatNumber((currentSubtotal.surplus?.virtualBattery ?? 0) - (offer.subTotal?.surplus?.virtualBattery ?? 0)) }}€
                                                        </template>
                                                    </p>
                                                </div>
                                                <div class="excedente-box" v-if="offer.surplus && offer.surplus.price">
                                                    <p class="excedente-label">Precio</p>
                                                    <p class="excedente-value">
                                                        <template v-if="offerToEdit === index">
                                                            <input class="simple-input" v-model.trim="offer.surplus.price" />
                                                        </template>
                                                        <template v-else>
                                                            {{ offer.surplus.price }} €/kWh
                                                        </template>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>


                                    </div>
                                </template>
                            </div>
                        </transition>
                    </div>
                </template>
            </div>
        </div>
        <!--Pantalla de carga-->
        <div class="loader-box" v-if="isLoading" style="position: fixed">
            <div class="d-flex column align-center p-20 round" data-gap="10" data-bg="blanco">
                <div class="text" data-size="20" data-weight="600">
                    {{ loadingMessages[currentMessage] }}
                </div>
                <div class="loader"></div>
            </div>
        </div>
        <!--PDF-->
        <comparator-pdf class="absPos opacity-0" v-if="generatePDF" :basicData="basicData" :topOffers="topOffers"
                        :pdfForm="opportunityData" :fee="fee" :optionSelected="optionSelected" :cupsData="consumptionData"
                        :adjustmentServiceValue= "adjustmentServiceValue"
                        :cupsIntervalsData="cupsIntervalsData" :prices="prices" :currentTotal="currentTotal" :manualTotal="manualTotal"
                        :dates="dates" :period="period" :offer="offerSelected" :powerPricePeriod="powerPricePeriod"
                        :currentSubtotal="currentSubtotal" :cupsInterval="cupsInterval" :offerSelected="offerSelected"
                        :selectedOffers="selectedOffers" :filteredOffers="filteredOffers" @closeForm="onPdfClosed"
                        @loading="pdfIsLoading = $event"  :includeOffersInPdf="includeOffersInPdf" :totalDays="totalDays"
                        :includeCurrentInPdf="!excludeCurrentFromPdf" :currentMonth="currentMonth"/>
        <Teleport to="body">
            <div v-if="viewStoredComparatives" class="floating-box">
                <div class="register-pos w-auto p-30 round comparative-history-scroll" data-round="20"
                     data-border-color="principal">
                    <div class="d-flex justify-between">
                        <p data-color="principal" data-weight="600" data-size="18" class="mb-10">Estudios</p>
                        <i data-size="25" class="pointer fas fa-xmark" @click="viewStoredComparatives = false"></i>
                    </div>
                    <!--Usuarios-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Usuarios</div>

                        <div
                            class="custom-select comparative-user-custom-select"
                            v-if="storedComparativeUsers && storedComparativeUsers.length > 0"
                        >
                            <div
                                class="ml-10 pointer"
                                data-size="13"
                                data-color="azul"
                                @click.stop="showUserFilterDropdown = !showUserFilterDropdown"
                            >
                                {{ getComparativeUserFilterTitle }}
                                <i class="fas fa-chevron-down ml-10"></i>
                            </div>

                            <div
                                v-show="showUserFilterDropdown"
                                class="select-content comparative-user-select-content"
                                @click.stop
                                @mousedown.stop
                            >
                                <div
                                    class="d-flex align-center pointer comparative-user-option"
                                    @click.stop="selectStoredComparativeUser('')"
                                >
                                    <div
                                        class="custom-checkbox mr-10"
                                        v-bind:class="{ 'selected': !selectedComparativeUser }"
                                    ></div>

                                    <div class="text">Todos</div>
                                </div>

                                <div
                                    v-for="user in storedComparativeUsers"
                                    :key="user._id"
                                    class="d-flex align-center pointer comparative-user-option"
                                    @click.stop="selectStoredComparativeUser(user._id)"
                                >
                                    <div
                                        class="custom-checkbox mr-10"
                                        v-bind:class="{ 'selected': selectedComparativeUser === user._id }"
                                    ></div>

                                    <div class="text" data-size="13">{{ user.name }}</div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 usuarios</div>
                    </div>

                    <div class="d-flex mt-30" data-gap="30">
                        <p class="text w-250-px" data-weight="600">Nombre</p>
                        <p class="text w-200-px" data-weight="600">CUPS</p>
                        <p class="text w-150-px" data-weight="600">Consumo</p>
                        <p class="text" data-weight="600">Fecha</p>
                    </div>

                    <div class="separator mt-5"></div>

                    <div
                        v-for="comparative of filteredStoredComparatives"
                        :key="comparative._id"
                        class="d-flex align-center"
                        data-gap="30"
                    >
                        <p class="text w-250-px">{{ comparative.name }}</p>
                        <div class="text w-200-px d-flex align-center" data-gap="8">
                            <span class="ellipsis">{{ comparative.cups }}</span>
                            <i
                                v-if="comparative.hasContract"
                                class="fas fa-circle-check"
                                data-color="success"
                                title="Ya existe un contrato con este CUPS"
                            ></i>
                        </div>
                        <p class="text w-150-px">{{ getComparativeConsumption(comparative) }}</p>
                        <p class="text w-110-px">{{ getPrettyDateComparative(comparative.createdAt) }}</p>

                        <i class="far fa-eye text pointer" @click="loadComparative(comparative)" />

                        <i
                            v-if="canManageComparative(comparative)"
                            class="far fa-trash pointer"
                            data-color="rojo"
                            @click="deleteComparative(comparative['_id'])"
                        />
                    </div>

                    <div
                        v-if="filteredStoredComparatives.length === 0"
                        class="d-flex justify-center mt-30"
                    >
                        <p class="text" data-size="14">No hay estudios guardados para este filtro.</p>
                    </div>
                </div>
            </div>
            <div v-if="viewPDFForm" class="floating-box">
                <div class="register-pos w-auto h-auto h-98-max p-30 round" style="overflow-y: scroll; position: relative"
                     data-round="20" data-border-color="principal">
                    <!-- overlay de carga dentro del modal -->
                    <div v-if="pdfIsLoading" class="pdf-loader">
                        <div class="d-flex column align-center p-20 round" data-gap="10" data-bg="blanco">
                            <div class="text" data-size="20" data-weight="600">
                                {{ 'Generando PDF…' }}
                            </div>
                            <div class="loader"></div>
                        </div>
                    </div>

                    <!--<p class="text">Por favor rellena los campos para generar el PDF</p>-->

                    <form class="d-flex column form" :class="[{'p-30' : !isDesktopView, 'mt-20' : isDesktopView}]" @submit.prevent="onGeneratePDF">

                        <div class="d-flex justify-between">
                            <p data-color="principal" data-weight="600" data-size="18" class="mb-10">Generar PDF</p>
                            <i data-size="25" class="pointer fas fa-xmark text" @click="viewPDFForm = false"></i>
                        </div>

                        <div class="form-group">
                            <p class="my-auto"><label>Comparativa para:</label></p>
                            <div class="input-group">
                                <input data-size="10" v-model="opportunityData.name" type="text">
                            </div>
                        </div>

                        <div class="form-group">
                            <p class="my-auto"><label>CIF:</label></p>
                            <div class="input-group">
                                <input data-size="10" v-model="opportunityData.CIF" type="text">
                            </div>
                        </div>

                        <div class="form-group">
                            <p class="my-auto"><label>Dirección de suministro:</label></p>
                            <div class="input-group">
                                <input data-size="10" v-model="opportunityData.order.direc" type="text">
                            </div>
                        </div>

                        <div class="form-group">
                            <p class="my-auto"><label>Comercializadora actual:</label></p>
                            <div class="input-group">
                                <input data-size="10" v-model="opportunityData.currentMarketer" type="text">
                            </div>
                        </div>

                        <div class="form-group">
                            <p class="my-auto"><label>Comercializadora ofertada:</label></p>
                            <div class="input-group">
                                <input data-size="10" v-model="opportunityData.order.marketer" type="text">
                            </div>
                        </div>

                        <div class="form-group">
                            <p class="my-auto"><label>CUPS:</label></p>
                            <div class="input-group">
                                <input data-size="10" v-model="opportunityData.order.CUPS" type="text">
                            </div>
                        </div>

                        <div class="form-group">
                            <p class="my-auto"><label>Imagen empresa:</label></p>
                            <div class="d-flex">
                                <!-- preview img -->
                                <div class="w-200-px-max h-100-px h-100-px-max round d-flex justify-center align-center hidden"
                                     data-border-color="principal" data-round="15">
                                    <!-- sin seleccionar -->
                                    <div class="w-150-px text-center" v-if="!opportunityData.enterpriseImg">
                                        <i class="fal fa-camera text" data-size="40"></i>
                                    </div>
                                    <!-- seleccionada -->
                                    <img v-else class="h-100-px" :src="imgTemporal" alt="Preview img empresa">
                                </div>

                                <div class="d-flex align-end ml-20">
                                    <button type="button" class="custom-button mt-10" data-size="medium" data-bg="principal"
                                            data-mode="translucent" v-on:click="openInput">
                                        Adjuntar <i class="far fa-paperclip"></i>
                                    </button>

                                    <input type="file" id="inputEnterprise" style="display: none" accept=".png, .jpg, .jpeg"
                                           v-on:change="pickupFile">
                                </div>
                            </div>
                        </div>




                        <div class="form-group">
                            <p class="my-auto"><label>Fecha del estudio:</label></p>
                            <div class="input-group">
                                <input
                                    type="date"
                                    v-model="opportunityData.studyDate"
                                />
                            </div>
                        </div>
                        <div
                            class="form-group"
                        >
                            <p class="my-auto">
                                <label>Observaciones:</label>
                            </p>
                            <div class="input-group">
                            <textarea
                                v-model="opportunityData.observaciones"
                                rows="4"
                                data-size="10"
                                style="resize: vertical;"
                            ></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <p class="my-auto"><label>Email del asesor:</label></p>
                            <div class="input-group">
                                <input data-size="10" v-model="opportunityData.agent" type="email">
                            </div>
                        </div>

                        <div class="form-group">
                            <p class="my-auto"><label>Nombre del asesor:</label></p>
                            <div class="input-group">
                                <input data-size="10" v-model="opportunityData.agentName" type="text">
                            </div>
                        </div>

                        <div class="form-group">
                            <p class="my-auto"><label>Teléfono del asesor:</label></p>
                            <div class="input-group">
                                <input data-size="10" v-model="opportunityData.agentPhone" type="text">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="d-flex align-center" data-gap="10">
                                <input type="checkbox" id="excludeCurrentFromPdf" v-model="excludeCurrentFromPdf" />
                                <label for="excludeCurrentFromPdf" class="text" data-size="14">
                                    Hacer informe sin comparar con el actual
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="custom-button mt-10" data-size="medium">Generar</button>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
import { calculateCommission} from "@/utils/calcCommission";

export default {
    name: 'ComparatorComponent',
    props: ["basicData"],
    data() {
        return {
            excludeCurrentFromPdf: false,
            includeOffersInPdf: true,
            pdfIsLoading: false,
            previewUrl: null,
            previewKind: null,
            tempOffer: null,
            selectedOffers: [],
            pdfBreakdown: null,
            topOffers: [],
            showUserFilterDropdown: false,
            marketers: [],
            storedComparatives: [],
            storedComparativeSelected: null,
            selectedComparativeUser: "",
            optionSelected: "",
            powerPricePeriod: "day",
            files: null,
            opportunityData: {
                name: "",
                agent: "",
                agentName: "",
                agentPhone: "",
                CIF: "",
                currentMarketer: "",
                billingInfo: {
                    community: "",
                    province: "",
                    locality: "",
                    address: "",
                    postal: ""
                },
                studyDate: moment().format("YYYY-MM-DD"),
                order: {
                    productType: 'cl',
                    marketerLogo: "",
                    marketer: '',
                    fee: '',
                    product: '',
                    CUPS: "",
                    direc: "",
                    zip: "",
                    town: "",
                    province: "",
                },
            },
            cups: "",
            fee: "",
            priceType: {
                variable: false,
                indexed: false,
            },

            priceFees: {
                power: "",
                energy: ""
            },

            selectedPriceType: {
                variable: false,
                indexed: false,
                fees: {
                    power: "",
                    energy: ""
                }
            },
            cupsData: [],
            cupsIntervalsData: [],
            consumptionData: {
                power: ["", "", "", "", "", ""],
                consumption: ["", "", "", "", "", ""],
            },
            billData: {
                consumption: ["", "", "", "", "", ""],
                dates: {
                    start: null,
                    end: null,
                },
                totalDays: 0
            },
            prices: {
                power: ["", "", "", "", "", ""],
                energy: ["", "", "", "", "", ""],
                powerDiscount: null,
                energyDiscount: null,
            },
            surplus: {virtualBattery: "", amount: "", price: ""},
            currentTotal: 0,
            currentSubtotal: { power: {total: 0}, energy: {total: 0}, surplus: {total: 0, virtualBattery: 0 }},
            manualTotal: false,
            manualYearConsumption: 0,
            applyTaxes: true,
            taxes: {
                iva: 21,
                electricTax: '5,11',
                meterDevice: 0,
                socialBonus: '0,019121',
            },
            meterDevicePricePeriod: 'day',
            otherConcept: {
                offers: false,
                electricTax: false,
            },
            otherConcepts: [],
            dates: {
                start: null,
                end: null,
            },
            totalDays: 30,
            period: "month",
            cupsInterval: 0,
            offersList: [],
            editingInputs: false,
            inputsDefault: {},
            offerToEdit: null,
            offerDefault: {},
            offerSelected: {},
            adjustmentServiceValue: 0,
            isLoading: false,
            viewStoredComparatives: false,
            viewInputs: true,
            viewFilters: false,
            viewOffers: false,
            viewCommissions: false,
            viewPDFForm: false,
            generatePDF: false,
            filters: {
                radio: {
                    sortBy: {
                        title: "Ordenar por",
                        checked: 7,
                    }
                },
                search: '',
                residencial: true,
                pyme: true,
                fixed: true,
                variable: false,
                indexed: false,
                surplus: false,
                withoutCommission: true,
                withoutAdjustmentServices: true,
                zocoCommissionRanges: null,
                marketers: [],
            },
            loadingMessages: [
                "Analizando tus kilovatios con precisión quirúrgica...",
                "Buscando ahorros entre watios y euros...",
                "¿Sabías que la luz viaja a 300.000 km/s? Nosotros un poco menos, pero casi.",
                "Buscando formas de que tu próxima factura te sorprenda... pero para bien."

            ],
            currentMessage: null,
            windowWidth: window.innerWidth,
            imgTemporal: null,
            showSummary: false,
            inputData: {}
        }
    },

    created() {
        this.fetchStoredComparatives()
        this.fetchMarketers();

        window.addEventListener('resize', this.updateWidth)
    },
    mounted() {
        //Mostrar comisiones por defecto para Efutura
        if(this.basicData.userSubdomain._id === '68d260e6bc9e8c38f8003df2'){
            this.viewCommissions = true;
        }

        if (this.basicData?.userLogged) {
            this.$nextTick(() => {
                const user = this.basicData.userLogged;

                // Email - intenta varios nombres de propiedad
                this.opportunityData.agent = user.email || user.correo || '';

                // Nombre - intenta varias combinaciones
                let name = '';
                if (user.name || user.surname) {
                    name = `${user.name || ''} ${user.surname || ''}`.trim();
                } else if (user.firstName || user.lastName) {
                    name = `${user.firstName || ''} ${user.lastName || ''}`.trim();
                }
                this.opportunityData.agentName = name;

                // Teléfono - intenta varios nombres de propiedad
                this.opportunityData.agentPhone = user.phone || user.telefono || user.telephone || '';
            });
        }
    },
    methods: {
        selectStoredComparativeUser(userId) {
            this.selectedComparativeUser = userId;
            this.showUserFilterDropdown = false;
        },
        normalizeCurrentTotal() {
            this.currentTotal = this.currentTotal
                .replace(',', '.')
                .replace(/[^0-9.]/g, '')
        },
        getMarketerLogoUrl(marketerName) {
            const marketer = this.marketers.find(m => m.name === marketerName);
            return marketer?.logo ? `/assets/marketers_logo/${marketer.logo}` : '';
        },
        async fetchStoredComparatives() {
            await axios.get('/api/comparatives/').then(res => {
                const comparatives = Array.isArray(res.data) ? res.data : [];

                this.storedComparatives = [...comparatives].sort((a, b) => {
                    return new Date(b.createdAt) - new Date(a.createdAt);
                });

                if (
                    this.selectedComparativeUser &&
                    !this.storedComparativeUsers.some(user => user._id === this.selectedComparativeUser)
                ) {
                    this.selectedComparativeUser = "";
                }
            })
        },

            getComparativeUserId(comparative) {
        const user = comparative.createdByUser || comparative.createdBy;

        if (!user) return null;

        if (typeof user === 'object') {
            return String(user._id || user.id || '');
        }

        return String(user);
    },

    getComparativeUserName(comparative) {
    const user = comparative.createdByUser || comparative.createdBy;
    const loggedUser = this.basicData?.userLogged || {};
    const userId = this.getComparativeUserId(comparative);

    const buildName = (userData) => {
        if (!userData) return '';

        const firstName = userData.firstName
            || userData.firstname
            || userData.first_name
            || userData.name
            || '';

        const lastName = userData.lastName
            || userData.lastname
            || userData.last_name
            || userData.surname
            || '';

        return `${firstName} ${lastName}`.trim();
    };

    if (user && typeof user === 'object') {
        return buildName(user) || 'Usuario sin nombre';
    }

    if (userId && String(loggedUser._id) === userId) {
        return buildName(loggedUser) || 'Yo';
    }

    return userId ? `Usuario ${userId.slice(-6)}` : 'Usuario sin nombre';
},

    getComparativeConsumption(comparative) {
        //Si tiene consumo manual asignada
        if(comparative?.manualYearConsumption){
            return `${Math.round(comparative?.manualYearConsumption).toLocaleString('es-ES')} kWh`
        }

        const sources = [
            comparative?.consumptionData?.consumption,
            comparative?.billData?.consumption,
        ];

        for (const values of sources) {
            if (!Array.isArray(values)) continue;

            const total = values.reduce((sum, value) => {
                const parsed = parseFloat(String(value ?? '').replace(',', '.'));
                return sum + (isNaN(parsed) ? 0 : parsed);
            }, 0);

            if (total > 0) {
                return `${Math.round(total).toLocaleString('es-ES')} kWh`;
            }
        }

        return '—';
    },

    canManageComparative(comparative) {
        const loggedUserId = this.basicData?.userLogged?._id;

        return loggedUserId && this.getComparativeUserId(comparative) === String(loggedUserId);
    },

        addOfferToSelected() {
            if (!this.tempOffer) return;
            const exists = this.selectedOffers.some(o =>
                o.marketer === this.tempOffer.marketer &&
                o.product === this.tempOffer.product
            );
            if (exists) return;
            if (this.selectedOffers.length >= 5) {
                Swal.fire({ icon: 'warning', title: 'Solo puedes añadir hasta 5 ofertas.' });
                return;
            }
            this.selectedOffers.push(this.tempOffer);
            this.tempOffer = null;  // limpia el dropdown
        },
        onGeneratePDF() {
            this.pdfIsLoading = true;  // loader dentro del modal
            this.generatePDF = true;   // monta <comparator-pdf> para generar el PDF
        },
        removeOffer(offer) {
            this.selectedOffers = this.selectedOffers.filter(o =>
                !(o.marketer === offer.marketer && o.product === offer.product)
            );
        },
        toggleOfferSelection(offer) {
            const pos = this.selectedOffers.indexOf(offer);

            if (pos === -1) {
                if (this.selectedOffers.length >= 5) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Límite alcanzado',
                        text: 'Solo puedes seleccionar hasta 5 ofertas para comparar.'
                    });
                    return;
                }
                this.selectedOffers.push(offer);
            } else {
                this.selectedOffers.splice(pos, 1);
            }


        },
        toggleOfferDetails(offer, index) {
            if (this.isDesktopView) return; // Móvil: solo aquí
            if (!offer) return;
            if (this.isEditingOffer(offer, index)) return;
            offer.viewPrices = !offer.viewPrices;
        },
        offerKey(offer, index) {
            if (!offer) return `offer-${index}`;
            return offer._tmpId ?? `${offer.marketer ?? 'null'}-${offer.product ?? 'null'}-${index}`;
        },
        isEditingOffer(offer, index) {
            return this.offerToEditId !== null && this.offerToEditId === this.offerKey(offer, index);
        },
        async fetchMarketers() {
            await axios.get('/api/marketers')
                .then((res) => {
                    this.marketers = res.data.marketers;
                    //TODO: Mejorar esto
                    if(this.marketers?.[0]?.createdBy !== '65cb57489c2c285441086a43'){
                        this.fetchZocoMarketers();
                        this.fetchZocoCommissionRanges();
                    }
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async fetchZocoMarketers() {
            await axios.get('/api/marketers', {
                params: { assignContractTo: '65cb57489c2c285441086a43' }
            })
                .then((res) => {
                    const zocoMarketers = res.data.marketers.map(marketer => ({...marketer, isZocoMarketer: true}));
                    this.marketers = [...this.marketers, ...zocoMarketers];
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async fetchZocoCommissionRanges() {
            await axios.get('/api/enterprises/65cb57489c2c285441086a43').then((res) => {
                this.zocoCommissionRanges = res.data.data.commissionRanges;
            })
        },
        async handleSubmit() {
            this.isLoading = true;
            this.currentMessage = Math.floor(Math.random() * this.loadingMessages.length)

            this.intervalId = setInterval(() => {
                this.currentMessage = Math.floor(Math.random() * this.loadingMessages.length)
            }, 4000)

            let error = "";

            //Reseteo el filtro de comercializadoras
            this.filters.marketers = this.sortedMarketers.map((marketer) => marketer.name).filter(marketer => marketer !== this.opportunityData.currentMarketer)

            try {
                if (this.optionSelected === "bill") {
                    //Compruebo que ha subido un archivo y lo proceso
                    const file = this.files[0];
                    if (file) {
                        await this.getOCRData();
                        await this.getCupsData(true);

                        //Si no ha obtenido datos del sips, le asigno los valores de consumo de la factura
                        if (this.cupsData.consumption === undefined)
                            this.cupsData.consumption = this.billData.consumption.map(item => item * 12);


                        //Asigno el intervalo 'factura'
                        this.cupsInterval = 'bill';

                        await this.calc();

                        //Compruebo si ha salido correcta la comparativa, para así contarla como valida para los registros
                        if (this.$utilities.validateComparison(this.billTotalPrice, this.currentTotal)) {
                            axios.post('/api/comparatives/countValidBillComparative', {'userSubdomain' : this.basicData.userSubdomain._id})
                                .then((res) => {
                                    console.log('res -->',res.data)
                                })
                                .catch((err) => {
                                    console.log(err)
                                })
                        }


                    } else {
                        error = "Por favor selecciona un archivo válido."
                        this.generateLog('error', 'Por favor selecciona un archivo válido.',this.optionSelected, 'handleSubmit (isset file)')
                    }
                }
                else if (this.optionSelected === "cups") {
                    //Compruebo que el CUPS tiene el formato correcto
                    let cupsRegex = /^ES\d{16}[a-z]{2}(?:[0-9][a-z])?$/i;
                    if (cupsRegex.test(this.cups)) {
                        //Compruebo que hay precios introducidos
                        if (this.prices.power.some(price => price) && this.prices.energy.some(price => price)) {
                            await this.getCupsData();
                            this.consumptionData.power = this.cupsData.power;
                            this.consumptionData.consumption = this.cupsIntervalsData[0].periods;
                            this.dates.start = moment(this.cupsIntervalsData[0]['startDate'], "DD/MM/YYYY");
                            this.dates.end = moment(this.cupsIntervalsData[0]['endDate'], "DD/MM/YYYY");
                            this.totalDays = this.dates.end.diff(this.dates.start, "days");

                            //Saco el input manual
                            this.inputData['manual'] = {
                                'prices': this.prices,
                                'surplus': this.surplus,
                                'cups': this.cups
                            }

                            this.calc();
                        } else {
                            error = "Por favor rellena los precios de potencia y energía."
                        }
                    } else {
                        error = "Por favor introduce un CUPS válido."
                    }
                }
                else if (this.optionSelected === "manual") {
                    //Compruebo que hay datos de consumo y precios introducidos y que tiene tarifa
                    if (this.consumptionData.power.some(consumption => consumption) && this.consumptionData.consumption.some(consumption => consumption) && this.prices.power.some(price => price) && this.prices.energy.some(price => price)) {

                        //Guardo los datos manuales
                        this.inputData['manual'] = {
                            'consumptionData': this.consumptionData,
                            'prices': this.prices,
                            'surplus': this.surplus,
                            'fee': this.fee,
                            'cups': this.cups
                        }

                        if (this.fee !== "") {
                            this.calc();
                        } else {
                            error = "Por favor introduce una tarifa."
                        }
                    } else {
                        error = "Por favor rellena los campos de potencia y energía."
                    }
                }
                if (error) {
                    await Swal.fire({
                        icon: "warning",
                        title: "Error en los datos",
                        text: error,
                    })
                } else {
                    this.viewOffers = true;
                    this.viewInputs = false;

                    setTimeout(() => {
                        this.autoSaveComparative();
                    }, 300);
                    this.filters.surplus = !!this.surplus?.amount;
                    document.getElementById("content-white").scrollTo({ top: 0, behavior: "smooth" });
                }
            } catch (error) {
                console.error("Hubo un error al cargar los datos", error);

                let errorMessage = "";
                switch (this.optionSelected) {
                    case "bill":
                        errorMessage = "Hubo un error. Comprueba que la factura sea un archivo válido."
                        break;
                    case "cups":
                        errorMessage = "Hubo un error. Es posible que el CUPS no tenga datos."
                        break;
                    case "manual":
                        errorMessage = "Hubo un error. Por favor revisa que los campos estén rellenos."
                        break;
                }

                //Genero el log del error
                this.generateLog('error', errorMessage,this.optionSelected, 'handleSubmit (try catch)')

                await Swal.fire({
                    icon: "error",
                    title: errorMessage
                })

            } finally {
                this.isLoading = false;
                clearInterval(this.intervalId)
            }
        },
        async getOCRData() {
            //Mando los archivos subidos en $refs.bill.files
            //En el servidor, hago la logica para llamar a la API y recibir el texto
            const formData = new FormData();
            Array.from(this.files).forEach(file => {
                formData.append("files[]", file);
            });
            formData.append("userSubdomain", this.basicData.userSubdomain._id);
            const response = await axios.post('/api/tools/getOCRData', formData)
                .catch(async (err) => {
                    if (err.response.data.limit) {
                        await Swal.fire({
                            icon: 'error',
                            title: '¡Has superado el límite!',
                            message: err.response.data.limit,
                            timer: 2000,
                            timerProgressBar: true
                        })
                    }
                })
            const data = response.data;

            //Datos del PDF
            this.inputData['pdf'] = data;

            //Asigno valores para calcular
            this.cups = data.cups?.replace(/ /g, "");
            this.prices.power = Object.values(data.precios_potencias);
            this.prices.powerDiscount = data.descuento_potencia;
            this.powerPricePeriod = data.periodo_precio_potencia ?? 'day';
            this.prices.energy = Object.values(data.precios_energia);
            this.prices.energyDiscount = data.descuento_energia;
            this.consumptionData.power = Object.values(data.potencias_contratadas);
            this.consumptionData.consumption = Object.values(data.energia_consumida);
            this.dates.start = moment(data.periodo_facturacion.fecha_inicio, "DD/MM/YYYY");
            this.dates.end = moment(data.periodo_facturacion.fecha_fin, "DD/MM/YYYY");
            this.taxes.meterDevice = data.otros.alquiler_equipo_medida;
            this.taxes.iva = data.otros.iva;
            this.taxes.socialBonus = data.otros.bono_social ?? this.taxes.socialBonus;
            this.fee = data.tarifa;
            this.billTotalPrice = data.total

            //Compruebo si vienen conceptos extra:
            if(Object.keys(data.conceptos_extra).length > 0){
                Object.entries(data.conceptos_extra).forEach(([name, value]) => {
                    this.otherConcepts.push({name, value, offers: false, electricTax: false});
                })
            }

            //Compruebo si las fechas de facturación concuerdan con como las manejo, si no la cambio un dia antes.
            if (this.dates.end.diff(this.dates.start, "days") !== data.dias_facturacion) {
                this.dates.start = this.dates.start.subtract(1, "day");
            }
            this.totalDays = this.dates.end.diff(this.dates.start, "days");

            //Asigno valores si hay
            this.surplus.amount = data.otros.kwh_excedentes;
            this.surplus.price = data.otros.precio_excedentes;

            //Activo los impuestos
            this.applyTaxes = true;

            //Asigno valores de oportunidad
            this.opportunityData = {
                name: data.titular,
                CIF: data.cif_nif,
                currentMarketer: data.comercializadora,
                billingInfo: {
                    community: data.direccion_titular.comunidad_autonoma,
                    province: data.direccion_titular.provincia,
                    locality: data.direccion_titular.poblacion,
                    address: data.direccion_titular.direccion,
                    postal: data.direccion_titular.codigo_postal
                },
                order: {
                    productType: 'cl',
                    direc: data.direccion_suministro.direccion,
                    zip: data.direccion_suministro.codigo_postal,
                    town: data.direccion_suministro.poblacion,
                    province: data.direccion_suministro.provincia,
                }
            }

            //Asigno los datos de la factura
            this.billData.consumption = Object.values(data.energia_consumida);
            this.billData.dates.start = moment(data.periodo_facturacion.fecha_inicio, "DD/MM/YYYY");
            this.billData.dates.end = moment(data.periodo_facturacion.fecha_fin, "DD/MM/YYYY");
            this.billData.totalDays = this.totalDays;
        },
        async getCupsData(optional) {
            // Remover punto de frontera en caso de tenerlo
            let cups = /^ES\d{16}[a-z]{2}[0-9][a-z]$/i.test(this.cups) ? this.cups?.slice(0, -2) : this.cups;

            try {
                const response = await axios.get('/api/tools/getAPIConsumption', {
                    params: { CUPS: cups }
                });

                if (!response.data.consumptionData && optional) return;

                this.cupsData = response.data.cupsData;
                this.cupsIntervalsData = response.data.consumptionData;

                //Trunco el valor de la energia consumida
                this.cupsData.consumption.forEach((consumption, index) => {
                    this.cupsData.consumption[index] = Math.trunc(consumption);
                });

                this.fee = response.data?.fee ?? this.fee;

                //Saco el input del sips
                this.inputData['sips'] = {
                    'cupsData': this.cupsData,
                    'cupsIntervalsData': this.cupsIntervalsData,
                    'fee': this.fee
                }

            } catch (error) {
                console.error("Error al obtener los datos del CUPS", error)
                this.generateLog('error', "Error al obtener los datos del CUPS", this.optionSelected, 'getCupsData')
            }
        },
        async calc() {
            //Calculo el total a pagar actualmente
            //Ajusto los precios según el periodo introducido
            let powerPrices = this.prices.power;
            switch (this.powerPricePeriod) {
                case "month":
                    powerPrices = powerPrices.map(price => this.parseStringToNumber(price) * 12 / 365)
                    break;
                case "year":
                    powerPrices = powerPrices.map(price => this.parseStringToNumber(price) / 365)
                    break;
            }
            this.currentSubtotal = this.calcTotal({ power: powerPrices, energy: this.prices.energy, surplus: this.surplus, powerDiscount: this.prices.powerDiscount, energyDiscount: this.prices.energyDiscount }, true);

            if(!this.manualTotal){
                this.currentTotal = Object.values(this.currentSubtotal).reduce((acc, value) => {
                    if (typeof value === 'object' && value !== null) {
                        return acc + (value.total ?? 0)
                    }
                    return acc + value
                }, 0);
            }

            //En caso de algún número estar mal, muestro error.
            if (isNaN(this.currentTotal)) {
                Swal.fire({
                    icon: "error",
                    title: "Error en los datos",
                    text: "Alguno de los datos introducidos es incorrecto.",
                })
                this.generateLog('error', 'Alguno de los datos introducidos es incorrecto.',this.optionSelected, 'calc ( isNaN(this.currentTotal) )')
                return;
            }

            //Compruebo si hay ofertas, si no las hay las creo
            if (this.offersList.length === 0) this.createOffers();

            //Calculo el total para las ofertas
            this.offersList.forEach(offer => {
               offer.subTotal = this.calcTotal({
                ...offer.prices,
                surplus: offer.surplus,
                fees: offer.fees,
                withoutAdjustmentServices: offer.withoutAdjustmentServices
            }, false, offer.priceType);
                offer.total = Object.values(offer.subTotal).reduce((acc, value) => {
                    if (typeof value === 'object' && value !== null) {
                        return acc + (value.total ?? 0)
                    }
                    return acc + value
                }, 0);
                offer.savePercent = this.currentTotal === 0 ? -1000 : (this.currentTotal - offer.total) / this.currentTotal * 100;
                //Si la oferta es manual, no calculo la comision
                if(offer.marketer){
                    offer.commission = this.calcCommission(offer.marketerId, offer.feeInfo.commissions, offer.feeInfo.commissionType, offer.fees, offer.isZocoMarketer)
                }
            })


            //Guardo datos extra calculados
            this.inputData['calculated'] = {
                'total': this.currentTotal,
                'subTotal': this.currentSubtotal,
                'days': this.totalDays,
                'maxPower': this.maxPower,
                'consumption': Math.round(this.consumptionData.consumption.reduce((acc, value) => acc + this.parseStringToNumber(value), 0)).toLocaleString("es-ES"),
                'estimatedConsumptionAnual': this.totalConsumption
            }

            //Si no ha sacado ofertas
            if (this.offersList.length === 0){
                this.generateLog('error', 'No ha salido ninguna oferta.',this.optionSelected, 'calc ( offersList.length === 0 )')
            }else{
                //Si ha salido correctamente
                this.generateLog('success', 'Se ha generado la comparativa correctamente.',this.optionSelected)
            }

        },
        calcTotal(prices, isCurrent, priceType) {
            const power = {total: 0, discount: 0};
            const energy = { total: 0, discount: 0, adjustmentService: 0 };
            const surplus = {total: 0, virtualBattery: 0};
            const taxes = {total: 0, iva: 0, electricTax: 0, socialBonus: 0, meterDevice: 0};

            //Selecciono los precios correspondientes al mes si es indexado
            if (priceType === 'indexed' && this.period === 'month') {
                const priceMonth = moment(
                    this.dates?.start?.valueOf() +
                    (this.dates?.end?.valueOf() - this.dates?.start?.valueOf()) / 2
                ).format('YYYY-MM');

                if (prices.history && prices.history[priceMonth]) {
                    const monthData = prices.history[priceMonth];
                    const historyPower = monthData.power;
                    const historyEnergy = monthData.consume;

                    prices.power = historyPower ? Object.values(historyPower) : prices.power;
                    prices.energy = historyEnergy ? Object.values(historyEnergy) : prices.energy;
                }
            }

            //Recorro todas las potencias y consumos, y los multiplico por su valor y el periodo de días
            for (let i = 0; i < 6; i++) {
                //Potencia
                let fee = prices.fees ? this.parseStringToNumber(prices.fees.power[i]) / 30 : 0;
                let price = this.parseStringToNumber(prices.power[i]) + fee;
                let consumption = this.parseStringToNumber(this.consumptionData.power[i])
                let totalPeriod = price * consumption * this.totalDays;

                if(isCurrent && prices.powerDiscount){
                    let discount = totalPeriod * prices.powerDiscount / 100;
                    power.discount -= discount;
                }


                power[`P${i + 1}`] = totalPeriod;
                power.total += totalPeriod;

                //Energía
                fee = prices.fees ? this.parseStringToNumber(prices.fees.energy[i]) / 1000 : 0;
                price = this.parseStringToNumber(prices.energy[i]) + fee;
                consumption = this.parseStringToNumber(this.consumptionData.consumption[i]);
                totalPeriod = price * consumption;

                if(isCurrent && prices.energyDiscount){
                    let discount = totalPeriod * prices.energyDiscount / 100;
                    energy.discount -= discount;
                }

                energy[`P${i + 1}`] = totalPeriod;
                energy.total += totalPeriod;
            }

            //Redondeo el total de potencia y consumo y sumo descuentos
            power.total = Number((power.total + power.discount).toFixed(2));
            energy.total = Number((energy.total + energy.discount).toFixed(2));

            // Servicio de ajuste (solo para ofertas sin servicio de ajuste incluido)
            if (prices.withoutAdjustmentServices === true) {
                const adjustment = this.parseStringToNumber(this.adjustmentServiceValue || 0);

                for (let i = 0; i < 6; i++) {
                    const periodConsumptionKwh = this.parseStringToNumber(this.consumptionData.consumption[i]);
                    const adjustmentPeriod = Number(((adjustment * periodConsumptionKwh) / 1000).toFixed(6));

                    energy[`adjustmentP${i + 1}`] = adjustmentPeriod;
                    energy[`totalP${i + 1}`] = Number((energy[`P${i + 1}`] + adjustmentPeriod).toFixed(6));
                }

                energy.adjustmentService = Number(
                    Object.keys(energy)
                        .filter(key => key.startsWith('adjustmentP'))
                        .reduce((acc, key) => acc + energy[key], 0)
                        .toFixed(2)
                );

                energy.total = Number(
                    Object.keys(energy)
                        .filter(key => /^totalP[1-6]$/.test(key))
                        .reduce((acc, key) => acc + energy[key], 0)
                        .toFixed(2)
                );
            } else {
                for (let i = 0; i < 6; i++) {
                    energy[`adjustmentP${i + 1}`] = 0;
                    energy[`totalP${i + 1}`] = Number((energy[`P${i + 1}`]).toFixed(6));
                }
            }

            //Calculo los excedentes y la batería virtual
            surplus.virtualBattery = Number((this.parseStringToNumber(prices.surplus?.virtualBattery ?? 0) / 30 * this.totalDays).toFixed(2));
            surplus.compensation = Number((this.parseStringToNumber(this.surplus?.amount ?? 0) * this.parseStringToNumber(prices?.surplus?.price ?? 0) * -1).toFixed(2));
            surplus.total = surplus.virtualBattery + surplus.compensation;

            //Calculo los conceptos extra
            let otherConcepts = 0;
            let otherConceptsElectricTax = 0
            if (this.otherConcepts.length > 0) {
                otherConcepts = this.otherConcepts.reduce((acc, concept) => {
                    if (isCurrent || concept.offers) {
                        return acc + this.parseStringToNumber(concept.value);
                    }

                    return acc;
                }, 0);

                otherConceptsElectricTax = this.otherConcepts.reduce((acc, concept) => {
                    if ((isCurrent || concept.offers) && concept.electricTax) {
                        return acc + this.parseStringToNumber(concept.value);
                    }
                    return acc;
                }, 0);
            }

            let otherConceptsDetail = [];

            if (this.otherConcepts.length > 0 && isCurrent) {
                otherConceptsDetail = this.otherConcepts.map(concept => ({
                    name: concept.name,
                    value: Number(this.parseStringToNumber(concept.value))
                }));
            }

            //Calculo impuestos si está activada la opcion
            if (this.applyTaxes) {
                taxes.socialBonus = Number((this.parseStringToNumber(this.taxes.socialBonus) * this.totalDays).toFixed(2));
                let meterDevicePrice = this.taxes.meterDevice;
                switch (this.meterDevicePricePeriod){
                    case "month":
                        meterDevicePrice = this.parseStringToNumber(meterDevicePrice) / 30;
                        break;
                    case "year":
                        meterDevicePrice = this.parseStringToNumber(meterDevicePrice) / 365;
                        break;
                }
                taxes.meterDevice = Number((this.parseStringToNumber(meterDevicePrice) * this.totalDays).toFixed(2));

                taxes.electricTax = Number((this.parseStringToNumber(this.taxes.electricTax) * (power.total + energy.total + surplus.compensation + taxes.socialBonus + otherConceptsElectricTax) / 100).toFixed(2));

                taxes.iva = Number((this.parseStringToNumber(this.taxes.iva) * (power.total + energy.total + surplus.total + otherConcepts + taxes.socialBonus + taxes.meterDevice + taxes.electricTax) / 100).toFixed(2));

                taxes.total = taxes.iva + taxes.electricTax + taxes.socialBonus + taxes.meterDevice;
            }

            return { power, energy, surplus, taxes, otherConcepts, otherConceptsDetail};
        },
        createOffer() {
            //Cancelo la edición si está activada
            this.offerToEdit = null;

            //Creo una oferta vacía y la añado a la lista de ofertas
            let newOffer = {
                marketer: null,
                fee: null,
                product: "Oferta",
                prices: {
                    power: ["0", "0", "0", "0", "0", "0"],
                    energy: ["0", "0", "0", "0", "0", "0"]
                },
                total: 0,
                subTotal: { power: {total: 0}, energy: {total: 0}, surplus: {total: 0, virtualBattery: 0}},
                savePercent: 100,
                commission: 0,
                viewPrices: true
            };

            this.offersList.push(newOffer);
        },
        createOffers() {
            //Recorro todas las comercializadoras
            this.sortedMarketers.forEach(marketer => {
                //Obtengo la tarifa a comparar
                let feeMarketer = marketer.fees.electricity.find(fee => fee.name.includes(this.fee))

                //Recorro todos los productos
                marketer.products.electricity.forEach(product => {
                    //Compruebo si este producto tiene la tarifa
                    let feeProduct = product.fees.find(fee => fee.id.$oid === feeMarketer?.id?.$oid && !fee?.archived);

                    //Si la tiene, creo una oferta
                    if (feeProduct) {
                        let offer = {
                            marketer: marketer.name,
                            marketerId: marketer._id,
                            fee: feeMarketer.name,
                            product: product.name,
                            prices: {
                                power: Object.values(feeProduct.prices.power),
                                energy: Object.values(feeProduct.prices.consume),
                                history: feeProduct.prices?.history
                            },
                            total: 0,
                            subTotal: { power: {total: 0}, energy: {total: 0}, surplus: {total: 0, virtualBattery: 0 }},
                            savePercent: 0,
                            commission: 0,
                            feeInfo: feeProduct,
                            viewPrices: false,
                            residencial: feeProduct.type.residencial,
                            pyme: feeProduct.type.pyme,
                            priceType: feeProduct.priceType,
                            minPower: feeProduct.minPower,
                            maxPower: feeProduct.maxPower,
                            minConsumption: feeProduct.minConsumption,
                            maxConsumption: feeProduct.maxConsumption,
                            surplusType: feeProduct.surplus,
                            adjustmentServices: !feeProduct.withoutAdjustmentServices,
                            withoutAdjustmentServices: feeProduct.withoutAdjustmentServices ?? false,
                            adjustmentServiceManual: 0,
                            isZocoMarketer: marketer.isZocoMarketer,
                        };

                        //Añado los precios de excedentes si es producto con excedentes
                        if (feeProduct.surplus) {
                            offer.surplus = {
                                virtualBattery: marketer.surplus?.virtualBattery,
                                price: this.surplus.virtualBattery ? marketer.surplus?.priceWithVB : marketer.surplus?.priceWithoutVB
                            }
                        }

                        //Añado los fees si es producto variable

                        /*
                        if (offer.priceType === "variable" || offer.priceType === 'indexed') {
                            offer.fees = {
                                power: {},
                                energy: {}
                            }
                        }
                        */

                        if (offer.priceType === "variable" || offer.priceType === 'indexed') {
                        const globalPowerFee = this.parseStringToNumber(this.priceFees.power);
                        const globalEnergyFee = this.parseStringToNumber(this.priceFees.energy);

                        offer.fees = {
                            power: Array(6).fill(globalPowerFee),
                            energy: Array(6).fill(globalEnergyFee)
                        };
                    }

                        //Si tiene datos de precios, lo añado como posible oferta
                        const hasPowerPrice = offer.prices.power.some(period => parseFloat(period));
                        const hasEnergyPrice = offer.prices.energy.some(period => parseFloat(period));

                        if (hasPowerPrice && hasEnergyPrice) {
                            this.offersList.push(offer);
                        }
                    }
                })
            })
        },
        calcCommission(marketer, commissions, commissionType, fees, assignedToZoco) {
            const result = calculateCommission({
                userListTop: this.basicData.userListTop,
                assignedToZoco,
                marketer,
                commissionRanges: this.basicData.enterprise.commissionRanges,
                commissionRangesZoco: this.zocoCommissionRanges,
                commissions,
                commissionType,

                energyData: {
                    annual: this.manualYearConsumption || this.totalConsumption,
                    byPeriods: this.totalConsumptionByPeriods
                },

                powerData: {
                    max: this.maxPower,
                    byPeriods: this.consumptionData.power
                },

                fees,
                manualCommissions: this.basicData?.userSubdomain?.settings?.manualCommissions
            });

            const user = this.basicData.userLogged;

            return result.breakdown.find(u => u.userId === user._id)?.commission
                ?? (user.label === 'Usuario subdominio' ? result.subdomain : 0);
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
        formatPowerPricePeriod(){
            switch(this.powerPricePeriod){
                case "day":
                    return ` € / kW día * ${this.totalDays} días`;
                case "month":
                    return ` € / kW mes * ${this.formatNumber(this.totalDays / 30)} meses`;
                case "year":
                    return ` € / kW año * ${this.formatNumber(this.totalDays / 365)} años`;
            }
        },
        formatMeterDevicePricePeriod(){
          switch(this.meterDevicePricePeriod){
              case "day":
                  return "€/día";
              case "month":
                  return "€/mes";
              case "year":
                  return "€/año";
          }
        },
        togglePeriod() {
            //Cancelo edición en caso de estar haciéndose
            this.cancelEditOffer();

            //No cambio de mes a año si se están editando los inputs
            if (this.editingInputs) return;

            const newPeriod = this.period === "month" ? "year" : "month";
            const hasCupsIntervalsData = Array.isArray(this.cupsIntervalsData) && this.cupsIntervalsData.length > 0;


            const needsCupsIntervalsData = this.optionSelected === "cups" || (this.optionSelected === "bill" && newPeriod === "year");

            if (needsCupsIntervalsData && !hasCupsIntervalsData) {
                Swal.fire({
                    icon: "warning",
                    title: "No hay datos disponibles",
                    text: "No se puede cambiar el periodo porque no hay datos de consumo anuales. Puede continuar usando los datos de la factura.",
                    confirmButtonText: "Aceptar"
                });

                return;
            }

            this.period = newPeriod;

            switch (this.optionSelected) {
                case "bill":
                    if (this.period === "month") {
                        //Si la opción de factura está seleccionada, cojo esos datos. Si no pues el intervalo seleccionado
                        if(this.cupsInterval === 'bill') {
                            this.consumptionData.consumption = this.billData.consumption;
                            this.dates.start = this.billData.start;
                            this.dates.end = this.billData.end;
                            this.totalDays = this.billData.totalDays;
                        }else{
                            this.consumptionData.consumption = this.cupsIntervalsData[this.cupsInterval].periods;
                            this.dates.start = moment(this.cupsIntervalsData[this.cupsInterval]['startDate'].replace(/[.-]/g, "/"), "DD/MM/YYYY");
                            this.dates.end = moment(this.cupsIntervalsData[this.cupsInterval]['endDate'].replace(/[.-]/g, "/"), "DD/MM/YYYY");
                            this.totalDays = this.dates.end.diff(this.dates.start, "days");
                        }
                    } else {
                        this.consumptionData.consumption = this.cupsData?.consumption;
                        this.dates.start = moment(this.cupsIntervalsData[0]['endDate'].replace(/[.-]/g, "/"), "DD/MM/YYYY").subtract(1, "years");
                        this.dates.end = moment(this.cupsIntervalsData[0]['endDate'].replace(/[.-]/g, "/"), "DD/MM/YYYY");
                        this.totalDays = this.dates.end.diff(this.dates.start, "days");
                    }
                    break;
                case "cups":
                    if (this.period === "month") {
                        this.consumptionData.consumption = this.cupsIntervalsData[this.cupsInterval].periods;
                        this.dates.start = moment(this.cupsIntervalsData[this.cupsInterval]['startDate'].replace(/[.-]/g, "/"), "DD/MM/YYYY");
                        this.dates.end = moment(this.cupsIntervalsData[this.cupsInterval]['endDate'].replace(/[.-]/g, "/"), "DD/MM/YYYY");
                        this.totalDays = this.dates.end.diff(this.dates.start, "days");
                    } else {
                        this.consumptionData.consumption = this.cupsData.consumption;
                        this.dates.start = moment(this.cupsIntervalsData[0]['endDate'].replace(/[.-]/g, "/"), "DD/MM/YYYY").subtract(1, "years");
                        this.dates.end = moment(this.cupsIntervalsData[0]['endDate'].replace(/[.-]/g, "/"), "DD/MM/YYYY");
                        this.totalDays = this.dates.end.diff(this.dates.start, "days");
                    }
                    break;
                case "manual":
                    this.totalDays = this.period === "month" ? 30 : 365;
                    break;
            }

            if(this.period === 'year' && this.fee !== '2.0TD' && this.prices.energy.some(price => !this.parseStringToNumber(price))){
                Swal.fire({
                    icon: 'warning',
                    title: 'Fallo en la comparativa',
                    text: 'Faltan precios en algunos periodos, por lo que el estudio no es exacto.',
                    confirmButtonText: 'Aceptar'
                })
            }

            //Recalculo los precios
            this.calc();
        },
        toggleIntervalConsumption(event){
            this.cupsInterval = event.target.value;

            if(this.cupsInterval === 'bill'){
                this.consumptionData.consumption = this.billData.consumption;
            }else{
                this.consumptionData.consumption = this.cupsIntervalsData[this.cupsInterval].periods
            }
        },
        resetFilters() {
            this.filters.radio.sortBy.checked = 7;
            this.filters.residencial = true;
            this.filters.pyme = true;
            this.filters.fixed = true;
            this.filters.variable = false;
            this.filters.indexed = false;
            this.filters.surplus = false;
            this.filters.marketers = this.sortedMarketers.map((marketer) => marketer.name);
        },
        resetComparator() {
            this.optionSelected = "";
            this.files = null;
            this.previewUrl = null;
            this.previewKind = null;
            this.storedComparativeSelected = null;
            this.cups = "";
            this.fee = "";
            this.cupsData = [];
            this.cupsIntervalsData = [];
            this.consumptionData = {
                power: ["", "", "", "", "", ""],
                consumption: ["", "", "", "", "", ""]
            }
            this.prices = {
                power: ["", "", "", "", "", ""],
                energy: ["", "", "", "", "", ""]
            };
            this.priceFees = {
                power: '',
                energy: ''
            };
            this.surplus = {};
            this.currentTotal = 0;
            this.manualTotal = false;
            this.manualYearConsumption = 0;
            this.currentSubtotal = {};
            this.viewInputs = true;
            this.viewOffers = false;
            this.dates = {
                start: null,
                end: null
            }
            this.totalDays = 30;
            this.period = "month";
            this.cupsInterval = 0;
            this.offersList = [];
            this.editingInputs = false;
            this.offerToEdit = null;
            this.currentMessage = null;
            this.otherConcept = {};
            this.otherConcepts = [];
            this.opportunityData = {
                name: "",
                CIF: "",
                currentMarketer: "",
                enterpriseImg: this.opportunityData.enterpriseImg,
                billingInfo: {
                    community: "",
                    province: "",
                    locality: "",
                    address: "",
                    postal: ""
                },
                order: {
                    productType: 'cl',
                    marketer: '',
                    marketerLogo: "",
                    fee: '',
                    product: '',
                    CUPS: "",
                    direc: "",
                    zip: "",
                    town: "",
                    province: "",
                },
            };
            this.inputData = {}
        },
        onPdfClosed() {
            this.viewPDFForm = false;
            this.generatePDF = false;
            this.selectedOffers = [];
            this.pdfIsLoading = false;
        },
        editOffer(index) {
            this.cancelEditOffer(); //Cancelo cualquier otra oferta que se esté editando
            this.offerToEdit = index; //Asigno el índice de la oferta que estoy editando

            if (this.filteredOffers[index].adjustmentServiceManual === undefined) {
                this.$set(this.filteredOffers[index], 'adjustmentServiceManual', 0);
            }


            this.offerDefault = JSON.parse(JSON.stringify(this.filteredOffers[index])) //Hago una copia de los datos para restaurar
            delete this.offerDefault.viewPrices; //Borro el boolean de mostrar precios, pues este es independiente de la edición
            this.offerEditingName = this.offerDefault.product;
        },
        updateOffer() {
            let offer = this.filteredOffers[this.offerToEdit]
            // offer.viewPrices = false; //Cierro la visualización de los precios

            //Reseteo las variables para controlar la edición
            this.offerToEdit = null;
            this.offerDefault = {};

            //Recalculo la oferta
            offer.subTotal = this.calcTotal({
                ...offer.prices,
                surplus: offer.surplus,
                fees: offer.fees,
                adjustmentServiceManual: offer.adjustmentServiceManual,
                withoutAdjustmentServices: offer.withoutAdjustmentServices
            });
            offer.total = Object.values(offer.subTotal).reduce((acc, value) => {
                if (typeof value === 'object' && value !== null) {
                    return acc + (value.total ?? 0)
                }
                return acc + value
            }, 0);
            offer.savePercent = this.currentTotal === 0 ? -1000 : (this.currentTotal - offer.total) / this.currentTotal * 100;
            offer.commission = this.calcCommission(offer.marketerId, offer.feeInfo.commissions, offer.feeInfo.commissionType, offer.fees, offer.isZocoMarketer);
        },
        cancelEditOffer() {
            //En caso de estar editando una oferta, devolverla a su valor por defecto
            if (this.offerToEdit !== null) {
                const indexInOriginal = this.offersList.findIndex(original => original.marketer === this.filteredOffers[this.offerToEdit].marketer && original.product === this.filteredOffers[this.offerToEdit].product);
                this.offersList[indexInOriginal] = { ...this.offersList[indexInOriginal], ...JSON.parse(JSON.stringify(this.offerDefault)) };
                this.offerToEdit = null;
                this.offerDefault = {};
            }
        },
        editInputs() {
            //Activo la edición y copio los valores a editar
            this.editingInputs = true;
            this.inputsDefault = {
                cups: this.cups,
                fee: this.fee,
                currentTotal: this.currentTotal,
                currentSubtotal: this.currentSubtotal,
                dates: { ...this.dates },
                totalDays: this.totalDays,
                consumptionData: JSON.parse(JSON.stringify(this.consumptionData)),
                prices: JSON.parse(JSON.stringify(this.prices)),
                surplus: JSON.parse(JSON.stringify(this.surplus)),
                applyTaxes: this.applyTaxes,
                taxes: JSON.parse(JSON.stringify(this.taxes)),
                cupsInterval: this.cupsInterval,
                otherConcepts: JSON.parse(JSON.stringify(this.otherConcepts)),
                powerPricePeriod: this.powerPricePeriod,
                priceFees: JSON.parse(JSON.stringify(this.priceFees)),
            }
        },
        updateInputs() {
            //Si la tarifa ha cambiado, obtener los productos de la nueva tarifa, y borrar los actuales excepto los añadidos
            if (this.fee !== this.inputsDefault.fee) {
                this.offersList = this.offersList.filter(offer => offer.marketer === null);
                this.createOffers();
            }

            //Si los fees han cambiado, actualizar los de las ofertas variables/indexadas
            if (
                this.priceFees.power !== this.inputsDefault.priceFees.power ||
                this.priceFees.energy !== this.inputsDefault.priceFees.energy
            ) {
                const powerFee = this.parseStringToNumber(this.priceFees.power);
                const energyFee = this.parseStringToNumber(this.priceFees.energy);

                this.offersList.forEach(offer => {
                    if (offer.priceType === 'variable' || offer.priceType === 'indexed') {
                        offer.fees = {
                            power: Array(6).fill(powerFee),
                            energy: Array(6).fill(energyFee)
                        };
                    }
                });
            }

            //Sí cambiamos a un intervalo, cambiar el periodo a mes y actualizar fechas
            if (this.optionSelected !== "manual" && this.cupsInterval !== this.inputsDefault.cupsInterval) {
                this.period = "month";

                if(this.cupsInterval === 'bill'){
                    this.dates = {
                        start: this.billData.start,
                        end: this.billData.end
                    }
                    this.totalDays = this.billData.totalDays;
                }else{
                    this.dates = { start: moment(this.cupsIntervalsData[this.cupsInterval].startDate, "DD/MM/YYYY"), end: moment(this.cupsIntervalsData[this.cupsInterval].endDate, "DD/MM/YYYY") }
                    this.totalDays = this.dates.end.diff(this.dates.start, "days");
                }
            }

            //Compruebo si el total actual ha sido editado a mano
            this.manualTotal = this.manualTotal || this.currentTotal !== this.inputsDefault.currentTotal;

            //Restablecemos las variables de la edición
            this.otherConcept = {};
            this.editingInputs = false;
            this.inputsDefault = {};

            //Recalculamos los precios y sus comisiones
            this.calc();
        },
        cancelEditInputs() {
            if (this.editingInputs) {
                //Restaurar valores iniciales a los guardados en inputsDefault
                this.cups = this.inputsDefault.cups;
                this.fee = this.inputsDefault.fee;
                this.currentTotal = this.inputsDefault.currentTotal;
                this.currentSubtotal = this.inputsDefault.currentSubtotal;
                this.dates = { ...this.inputsDefault.dates };
                this.totalDays = this.inputsDefault.totalDays;
                this.consumptionData = JSON.parse(JSON.stringify(this.inputsDefault.consumptionData));
                this.prices = JSON.parse(JSON.stringify(this.inputsDefault.prices));
                this.surplus = JSON.parse(JSON.stringify(this.inputsDefault.surplus));
                this.applyTaxes = this.inputsDefault.applyTaxes;
                this.taxes = JSON.parse(JSON.stringify(this.inputsDefault.taxes));
                this.cupsInterval = this.inputsDefault.cupsInterval;
                this.otherConcept = {};
                this.otherConcepts = JSON.parse(JSON.stringify(this.inputsDefault.otherConcepts));
                this.powerPricePeriod = this.inputsDefault.powerPricePeriod;
                this.priceFees = JSON.parse(JSON.stringify(this.inputsDefault.priceFees));

                this.editingInputs = false;
            }
        },
        getPrettyDate(date) {
            let dateNow = new Date(date);
            let day = String(dateNow.getDate()).padStart(2, '0'); // Asegura que el día tenga dos dígitos
            let month = String(dateNow.getMonth() + 1).padStart(2, '0'); // Asegura que el mes tenga dos dígitos y se suma 1 porque en JavaScript los meses van de 0 a 11
            let year = dateNow.getFullYear();
            return `${day}/${month}/${year}`;
        },
        getPrettyDateComparative(date) {
            return moment(date).format("D/M/YY HH:mm");
        },
        getFilesSelected(files) {
            this.files = files;

            if (this.previewUrl && this.previewUrl.startsWith("blob:")) {
                URL.revokeObjectURL(this.previewUrl);
            }

            if (!this.files.length) {
                this.previewUrl = null;
                this.previewKind = null;
                return;
            }

            // Usamos SOLO el primer archivo para la previsualización
            const file = this.files[0];
            this.previewUrl = URL.createObjectURL(file);

            if (file.type && file.type.startsWith("image/")) {
                this.previewKind = "image";
            } else if (file.type === "application/pdf") {
                this.previewKind = "pdf";
            } else {
                this.previewKind = null; // tipo no soportado
            }
        },

        createOpportunity(offer) {

            Swal.fire({
                icon: 'warning',
                title: '¿Estás seguro que quieres crear una oportunidad?',
                confirmButtonText: 'Sí',
                showConfirmButton: true,
                cancelButtonText: 'No',
                showCancelButton: true
            }).then((res) => {

                if (res.isConfirmed) {
                    this.opportunityData.order = {
                        ...this.opportunityData.order,
                        marketer: offer.marketer,
                        fee: offer.fee,
                        product: offer.product,
                        CUPS: this.cups?.substring(0, 20),
                    };
                    let temporalCreateOppCookie = JSON.stringify(this.opportunityData)

                    this.$cookies.set('temporalCreateOppCookie', temporalCreateOppCookie, '1h')

                    const routeData = this.$router.resolve({ name: 'oportunitiesRegister' });
                    window.open(routeData.href, '_blank');

                }
            })

        },
        toggleMarketer(marketerToToggle) {
            //Si no se pasa marketer (se pulsa todos)
            if (!marketerToToggle) {
                if (this.filters.marketers.length === this.sortedMarketers.length) {
                    this.filters.marketers = [];
                } else {
                    this.filters.marketers = this.sortedMarketers.map((marketer) => marketer.name);
                }
            } else {
                //Borro el marketer si ya estaba seleccionado, lo añado si no
                if (this.filters.marketers.includes(marketerToToggle)) {
                    this.filters.marketers = this.filters.marketers.filter(marketer => marketer !== marketerToToggle);
                } else {
                    this.filters.marketers.push(marketerToToggle);
                }
            }
        },
        selectNewOrderType(orderType) {
            switch (orderType) {
                case 'total':

                    if (this.filters.radio.sortBy.checked !== 0 && this.filters.radio.sortBy.checked !== 1)
                        this.filters.radio.sortBy.checked = 1
                    else if (this.filters.radio.sortBy.checked === 1)
                        this.filters.radio.sortBy.checked = 0
                    else if (this.filters.radio.sortBy.checked === 0)
                        this.filters.radio.sortBy.checked = 7

                    break;

                case 'efficiency':

                    if (this.filters.radio.sortBy.checked !== 2 && this.filters.radio.sortBy.checked !== 3)
                        this.filters.radio.sortBy.checked = 3
                    else if (this.filters.radio.sortBy.checked === 3)
                        this.filters.radio.sortBy.checked = 2
                    else if (this.filters.radio.sortBy.checked === 2)
                        this.filters.radio.sortBy.checked = 7

                    break;

                case 'commission':

                    if (this.filters.radio.sortBy.checked !== 4 && this.filters.radio.sortBy.checked !== 5)
                        this.filters.radio.sortBy.checked = 5
                    else if (this.filters.radio.sortBy.checked === 5)
                        this.filters.radio.sortBy.checked = 4
                    else if (this.filters.radio.sortBy.checked === 4)
                        this.filters.radio.sortBy.checked = 7

                    break;

                case 'save':

                    if (this.filters.radio.sortBy.checked !== 6 && this.filters.radio.sortBy.checked !== 7)
                        this.filters.radio.sortBy.checked = 7
                    else if (this.filters.radio.sortBy.checked === 7)
                        this.filters.radio.sortBy.checked = 6
                    else if (this.filters.radio.sortBy.checked === 6)
                        this.filters.radio.sortBy.checked = 7

                    break;
            }
        },
        applyMobileSort(metric, direction) {
            const map = {
                total: [0, 1],
                efficiency: [2, 3],
                commission: [4, 5],
                save: [6, 7]
            };
            const dirIndex = direction === 'asc' ? 1 : 0;
            const value = map[metric] ? map[metric][dirIndex] : 1;
            this.filters.radio.sortBy.checked = value;
            this.mobileSortMetric = metric;
            this.mobileSortDirection = direction;
        },
        addOtherConcept() {
            this.otherConcept.id = Math.random();
            this.otherConcepts.push(this.otherConcept);
            this.otherConcept = {offers: false, electricTax: false};
        },
        removeOtherConcept(index) {
            this.otherConcepts.splice(index, 1);
        },

        getTopCheapestOffers() {
            const all = this.filteredOffers
                .map(offer => {
                    const save = this.currentTotal - offer.total;
                    const savePercent = this.currentTotal === 0
                        ? 0
                        : (save / this.currentTotal) * 100;
                    return {
                        ...offer,
                        save: Number(save.toFixed(2)),
                        savePercent: Number(savePercent.toFixed(2)),
                    };
                });

            if (this.selectedOffer) {
                const idx = all.findIndex(o =>
                    o.marketer === this.selectedOffer.marketer &&
                    o.product === this.selectedOffer.product
                );
                if (idx !== -1) {
                    return all.slice(idx, idx + 5);
                }
            }

            return all.slice(0, 5);
        },


        createPDFForm(offer) {

            const save = this.currentTotal - offer.total;

            const savePercent = this.currentTotal === 0
                ? 0
                : (save / this.currentTotal) * 100;

            this.offerSelected = {
                ...offer,
                save: Number(save.toFixed(2)),
                savePercent: Number(savePercent.toFixed(2))
            };

            this.selectedOffer = this.offerSelected;

            this.opportunityData.order.marketer = offer.marketer;
            this.opportunityData.order.marketerLogo = this.getMarketerLogoUrl(offer.marketer);
            this.opportunityData.order.CUPS = this.cups;
            this.opportunityData.order.product = offer.product;

            this.topOffers = this.getTopCheapestOffers();

            // Pre-llenar datos del agente cuando se abre el formulario
            if (this.basicData?.userLogged) {
                const user = this.basicData.userLogged;

                console.log('User data (Comparator):', user); // Debug

                // Email - intenta varios nombres de propiedad
                if (!this.opportunityData.agent) {
                    this.opportunityData.agent = user.email || user.correo || user.mail || '';
                }

                // Nombre - intenta varias combinaciones
                if (!this.opportunityData.agentName) {
                    let name = '';
                    if (user.name || user.surname) {
                        name = `${user.name || ''} ${user.surname || ''}`.trim();
                    } else if (user.firstName || user.lastName) {
                        name = `${user.firstName || ''} ${user.lastName || ''}`.trim();
                    } else if (user.nombre) {
                        name = user.nombre;
                    }
                    this.opportunityData.agentName = name;
                }

                // Teléfono - intenta varios nombres de propiedad
                if (!this.opportunityData.agentPhone) {
                    this.opportunityData.agentPhone = user.phone || user.telefono || user.telephone || user.tlfn || '';
                }
            }

            this.viewPDFForm = true;
        },
        saveComparative() {
            let defaultName = '';
            if (this.storedComparativeSelected?.name) {
                defaultName = this.storedComparativeSelected.name;
            } else if (this.optionSelected === 'bill') {
                defaultName = this.opportunityData?.name || 'Comparativa factura';
            } else if (this.optionSelected === 'cups') {
                defaultName = this.cups ? `Comparativa ${this.cups}` : 'Comparativa CUPS';
            } else if (this.optionSelected === 'manual') {
                defaultName = 'Comparativa datos';
            }

            let formData = new FormData();
            const data = {
                optionSelected: this.optionSelected,
                cups: this.cups,
                cupsData: this.cupsData,
                cupsIntervalsData: this.cupsIntervalsData,
                type: 'electricity',
                cupsInterval: this.cupsInterval,
                powerPricePeriod: this.powerPricePeriod,
                opportunityData: this.opportunityData,
                fee: this.fee,
                dates: this.dates,
                totalDays: this.totalDays,
                consumptionData: this.consumptionData,
                manualYearConsumption: this.manualYearConsumption,
                period: this.period,
                prices: this.prices,
                surplus: this.surplus,
                applyTaxes: this.applyTaxes,
                taxes: this.taxes,
                otherConcepts: this.otherConcepts,
                filters: this.filters,
                createdBy: this.basicData.userLogged._id,
                createdAt: moment()
            }

            //Compruebo si existe un estudio con este cups ya guardado por el usuario, en caso afirmativo pregunto si quiere sustituirlo o crear uno nuevo.
            if (this.storedComparativeSelected !== null) {
                Swal.fire({
                    icon: 'question',
                    title: "Estudio ya guardado",
                    text: "¿Quieres actualizar el estudio cargado, o crear uno nuevo?",
                    confirmButtonText: 'Crear nuevo',
                    showConfirmButton: true,
                    cancelButtonText: 'Actualizar',
                    showCancelButton: true
                }).then((res) => {
                    if (res.isConfirmed) {
                        Swal.fire({
                            icon: "question",
                            title: "Guardar estudio",
                            text: "¿Quieres guardar el estudio?",
                            input: 'text',
                            inputValue: defaultName,
                            inputPlaceholder: 'Nombre del estudio',
                            confirmButtonText: 'Sí',
                            showConfirmButton: true,
                            cancelButtonText: 'No',
                            showCancelButton: true
                        }).then((res) => {
                            if (res.isConfirmed) {
                                data.name = res.value ? res.value : 'Estudio sin nombre';
                                formData.append('comparative', JSON.stringify(data));
                                axios.post('/api/comparatives/store', formData).then((res) => {
                                    this.fetchStoredComparatives();

                                    this.storedComparativeSelected = {
                                        _id: res.data._id,
                                        name: data.name
                                    };
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Estudio guardado',
                                        timer: 1500,
                                        timerProgressBar: true
                                    })
                                })
                            }
                        }).catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al guardar el estudio',
                            })
                        })
                    } else if (res.dismiss === Swal.DismissReason.cancel) {
                        data._id = this.storedComparativeSelected['_id'];
                        data.name = this.storedComparativeSelected['name'];
                        formData.append('comparative', JSON.stringify(data));
                        axios.post('/api/comparatives/update', formData).then(res => {
                            Swal.fire({
                                icon: 'success',
                                title: "Estudio actualizado",
                                timer: 1500,
                                timerProgressBar: true,
                            })
                        }).catch(err => {
                            console.log(err)
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al actualizar el estudio',
                            })
                        })
                    }
                })
            } else {
                Swal.fire({
                    icon: "question",
                    title: "Guardar estudio",
                    text: "¿Quieres guardar el estudio?",
                    input: 'text',
                    inputPlaceholder: 'Nombre del estudio',
                    confirmButtonText: 'Sí',
                    showConfirmButton: true,
                            inputValue: defaultName,

                    cancelButtonText: 'No',
                    showCancelButton: true
                }).then((res) => {
                    if (res.isConfirmed) {
                        data.name = res.value ? res.value : 'Estudio sin nombre';
                        formData.append('comparative', JSON.stringify(data));
                        axios.post('/api/comparatives/store', formData).then(res => {
                            this.fetchStoredComparatives();
                            Swal.fire({
                                icon: 'success',
                                title: 'Estudio guardado',
                                timer: 1500,
                                timerProgressBar: true
                            })
                        }).catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al guardar el estudio',
                            })
                        })
                    }
                });
            }

        },

        autoSaveComparative() {
            let formData = new FormData();

            let name = 'Comparativa';

            // lógica de nombre
            if (this.optionSelected === 'bill') {
                const clientName =
                    this.opportunityData?.name ||
                    this.billData?.titular ||
                    this.billData?.name ||
                    '';

                name = clientName
                    ? `${clientName}`
                    : 'Comparativa factura';

            } else if (this.optionSelected === 'cups') {
                name = this.cups ? `Comparativa ${this.cups}` : 'Comparativa CUPS';
            } else if (this.optionSelected === 'manual') {
                name = 'Comparativa datos';
            }

            const data = {
                optionSelected: this.optionSelected,
                cups: this.cups,
                cupsData: this.cupsData,
                cupsIntervalsData: this.cupsIntervalsData,
                type: 'electricity',
                cupsInterval: this.cupsInterval,
                powerPricePeriod: this.powerPricePeriod,
                opportunityData: this.opportunityData,
                fee: this.fee,
                dates: this.dates,
                totalDays: this.totalDays,
                consumptionData: this.consumptionData,
                manualYearConsumption: this.manualYearConsumption,
                period: this.period,
                prices: this.prices,
                priceFees: this.priceFees,
                surplus: this.surplus,
                applyTaxes: this.applyTaxes,
                taxes: this.taxes,
                otherConcepts: this.otherConcepts,
                filters: this.filters,
                createdBy: this.basicData.userLogged._id,
                createdAt: moment(),
                name: name
            }

            formData.append('comparative', JSON.stringify(data));

            axios.post('/api/comparatives/store', formData)
                .then(() => {
                    this.fetchStoredComparatives();

                    this.storedComparativeSelected = {
                        _id: res.data._id,
                        name: name
                    };
                })
                .catch(err => {
                    console.error('AutoSave error', err);
                });
        },

        loadComparative(comparative, index) {

            this.optionSelected = comparative.optionSelected;
            this.cups = comparative.cups;
            this.cupsData = comparative.cupsData;
            this.cupsIntervalsData = comparative.cupsIntervalsData;
            this.cupsInterval = comparative.cupsInterval;
            this.powerPricePeriod = comparative.powerPricePeriod;
            this.opportunityData = comparative.opportunityData;
            this.fee = comparative.fee;
            this.dates = {
                start: moment(comparative.dates.start),
                end: moment(comparative.dates.end)
            };
            this.totalDays = comparative.totalDays;
            this.consumptionData = comparative.consumptionData;
            this.manualYearConsumption = comparative.manualYearConsumption;
            this.period = comparative.period;
            this.prices = comparative.prices;
            this.priceFees = comparative.priceFees || { power: '', energy: '' };
            this.surplus = comparative.surplus;
            this.applyTaxes = comparative.applyTaxes;
            this.taxes = comparative.taxes;
            this.otherConcepts = comparative.otherConcepts;
            this.filters = comparative.filters || {};

            //  recalcula ofertas (precios actuales)
            this.createOffers();

            //  reconstruir marketers
            if (!this.filters.marketers || this.filters.marketers.length === 0) {
                this.filters.marketers = this.sortedMarketers.map((marketer) => marketer.name);
            }

            // asegurar surplus
            this.filters.surplus = !!this.surplus?.amount;

            this.storedComparativeSelected = this.canManageComparative(comparative) ? comparative : null;
            this.viewStoredComparatives = false;

            this.calc();

            this.viewOffers = true;
            this.viewInputs = false;
        },

        deleteComparative(comparative) {
            Swal.fire({
                icon: 'warning',
                title: 'Borrar estudio',
                text: '¿Estás seguro que quieres borrar este estudio?',
                confirmButtonText: "Borrar",
                showCancelButton: true,
                cancelButtonText: "Cancelar",
            }).then((res) => {
                if (res.isConfirmed) {
                    axios.delete(`/api/comparatives/${comparative}`).then(res => {
                        this.fetchStoredComparatives();
                        Swal.fire({
                            icon: 'success',
                            title: 'Estudio borrado',
                            timer: 1500,
                            timerProgressBar: true
                        })
                    }).catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un error al borrar el estudio'
                        })
                    })
                }
            })
        },
        openInput() {
            $('#inputEnterprise').click()
        },
        pickupFile() {
            let input = $('#inputEnterprise');

            if (input.prop('files') && input.prop('files').length > 0) {
                this.opportunityData.enterpriseImg = input.prop('files')[0];

                // Crear un blob temporal a partir del archivo de la imagen
                this.imgTemporal = URL.createObjectURL(this.opportunityData.enterpriseImg);
            }
        },
        //Métodos para animar las ofertas
        beforeEnter: function (el) {
            el.style.height = '0';
        },
        enter: function (el) {
            el.style.height = el.scrollHeight + 'px';
        },
        beforeLeave: function (el) {
            el.style.height = el.scrollHeight + 'px';
        },
        leave: function (el) {
            el.style.height = '0';
        },
        updateWidth() {
            this.windowWidth = window.innerWidth
        },
        passesPowerFilter(offer) {
            const offerPower = this.maxPower;
            if(!!offer.minPower && !!offer.maxPower) {
                return offerPower >= offer.minPower && offerPower <= offer.maxPower;
            } else if (!!offer.minPower) {
                return offerPower >= offer.minPower;
            } else if (!!offer.maxPower) {
                return offerPower <= offer.maxPower;
            } else {
                return true;
            }
        },
        passesConsumptionFilter(offer) {
            const offerConsumption = this.manualYearConsumption ? this.manualYearConsumption : this.totalConsumption;
            if(!!offer.minConsumption && !!offer.maxConsumption) {
                return offerConsumption >= offer.minConsumption && offerConsumption <= offer.maxConsumption;
            } else if (!!offer.minConsumption) {
                return offerConsumption >= offer.minConsumption;
            } else if (!!offer.maxConsumption) {
                return offerConsumption <= offer.maxConsumption;
            } else {
                return true;
            }
        },
        generateLog(status, message = null, type, codePart){

            let output = null

            //Quito el feeInfo y filtro por excedentes o no
            if (status === 'success'){
                let cleanedOffers = this.filteredOffers
                    .map(offer => {
                        const cleaned = { ...offer };
                        delete cleaned.feeInfo;
                        return cleaned;
                    });


                //Mayor ahorro
                let topTotal = [...cleanedOffers].slice(0, 5);

                //Mayor comisión
                let topCommission = [...cleanedOffers]
                    .sort((a, b) => b.commission - a.commission)
                    .slice(0, 5);

                //Mayor eficiencia
                let topEfficiency = [...cleanedOffers]
                    .filter(o => o.commission > 0)
                    .sort((a, b) => (b.total / b.commission) - (a.total / a.commission))
                    .slice(0, 5);

                output = {
                    'total': topTotal,
                    'commission': topCommission,
                    'efficiency': topEfficiency
                };
            }


            //$status, $messageError = null, $type, $codePart, $input, $output, $user

            console.log('inputData -->', this.inputData);

            axios.post('/api/logs/generateComparative', {
                'status': status,
                'messageError': message,
                'inputType': type,
                'comparativeType': 'electricity',
                'codePart': codePart,
                'inputData': this.inputData,
                output
            })
                .then((res) => {
                    console.log(res)
                })
                .catch((err) => {
                    console.log(err)
                })
        }
    },
    computed: {

            storedComparativeUsers() {
            const users = {};

            this.storedComparatives.forEach(comparative => {
                const userId = this.getComparativeUserId(comparative);

                if (!userId) return;

                users[userId] = {
                    _id: userId,
                    name: this.getComparativeUserName(comparative)
                };
            });

            return Object.values(users).sort((a, b) => {
                return a.name.localeCompare(b.name, "es", { sensitivity: "base" });
            });
        },

        getComparativeUserFilterTitle() {
            if (!this.selectedComparativeUser) {
                return 'Todos';
            }

            const user = this.storedComparativeUsers.find(user => {
                return user._id === this.selectedComparativeUser;
            });

            return user ? user.name : 'Todos';
        },

        filteredStoredComparatives() {
            if (!this.selectedComparativeUser) {
                return this.storedComparatives;
            }

            return this.storedComparatives.filter(comparative => {
                return this.getComparativeUserId(comparative) === this.selectedComparativeUser;
            });
        },


        filteredOffers() {
            let sortedOffers = [...this.offersList];

            switch (this.filters.radio.sortBy.checked) {
                case 0:
                    //Total ascendente
                    sortedOffers = sortedOffers.sort((a, b) => {
                        return a.total - b.total;
                    })
                    break;
                case 1:
                    //Total descendiente
                    sortedOffers = sortedOffers.sort((a, b) => {
                        return b.total - a.total;
                    })
                    break;
                case 2:
                    //Eficiencia ascendente
                    sortedOffers = sortedOffers.sort((a, b) => {
                        const efficiency = (b.total / b.commission) - (a.total / a.commission);
                        return efficiency;
                    })
                    break;

                case 3:
                    //Eficiencia decreciente
                    sortedOffers = sortedOffers.sort((a, b) => {
                        //En lugar de sobre el ahorro, lo hago sobre el precio de la oferta por lo que a menos mejor
                        return (a.total / a.commission) - (b.total / b.commission);
                    })
                    break;

                case 4:
                    //Comisión ascendente
                    sortedOffers = sortedOffers.sort((a, b) => {
                        return a.commission - b.commission;
                    })
                    break;
                case 5:
                    //Comisión descendente
                    sortedOffers = sortedOffers.sort((a, b) => {
                        return b.commission - a.commission;
                    })
                    break;
                case 6:
                    //Ahorro ascendente
                    sortedOffers = sortedOffers.sort((a, b) => {
                        return b.total - a.total;
                    })
                    break;
                case 7:
                    //Ahorro descendente
                    sortedOffers = sortedOffers.sort((a, b) => {
                        return a.total - b.total;
                    })
                    break;
            }

            //Filtro por comercializadora, residencial, pyme, fijo, fijo-variable e indexado
            //En caso de ser oferta creada manualmente siempre aparece
            const search = this.filters.search?.trim().toLowerCase() ?? '';

            return sortedOffers.filter((offer) => {
                    return ((this.filters.marketers.includes(offer.marketer)) &&
                        ((this.filters.residencial && offer.residencial) || (this.filters.pyme && offer.pyme)) &&
                        ((this.filters.fixed && offer.priceType === 'fixed') || (this.filters.variable && offer.priceType === 'variable') || (this.filters.indexed && offer.priceType === 'indexed')) &&
                        (((this.filters.surplus && (offer.surplusType === 'required' || offer.surplusType === 'optional')) || (!this.filters.surplus && (offer.surplusType === 'optional' || offer.surplusType === 'none'))) &&
                            (this.filters.withoutCommission || !(Number(offer.commission) === 0 && offer.priceType === 'fixed')) &&
                            (this.filters.withoutAdjustmentServices || offer.adjustmentServices) &&
                            this.passesPowerFilter(offer) && this.passesConsumptionFilter(offer)) &&
                        String(offer.product ?? '').toLowerCase().includes(search))
                    || offer.marketer === null
                }
            )
        },
        sortedMarketers() {
            //Obtengo las comercializadoras que puede ver el usuario
            let marketersVisible = [...this.basicData.userLogged.marketers];

            //Filtro por marketers visibles o creados, porque el usuario subdominio tiene los marketers de zoco en sus datos en lugar de los propios
            return this.marketers.filter(marketer => {
                const isVisible =
                    (marketersVisible.includes(marketer._id) ||
                    this.basicData.userLogged._id === marketer.createdBy) &&
                    !marketer.archived;

                const hasElectricity =
                    Array.isArray(marketer.products?.electricity) &&
                    marketer.products.electricity.length > 0;

                return isVisible && hasElectricity;
            })
            .toSorted((a, b) => {
                return a.name.localeCompare(b.name, "es", { sensitivity: 'base' })
            })
        },
        mobileSortMetric: {
            get() {
                const checked = this.filters.radio.sortBy.checked;
                if (checked === 0 || checked === 1) return 'total';
                if (checked === 2 || checked === 3) return 'efficiency';
                if (checked === 4 || checked === 5) return 'commission';
                if (checked === 6 || checked === 7) return 'save';
                return 'total';
            },
            set(value) {
                this.applyMobileSort(value, this.mobileSortDirection);
            }
        },
        mobileSortDirection: {
            get() {
                return [1, 3, 5, 7].includes(this.filters.radio.sortBy.checked) ? 'asc' : 'desc';
            },
            set(value) {
                this.applyMobileSort(this.mobileSortMetric, value);
            }
        },
        totalConsumptionByPeriods() {
            if (this.optionSelected !== "manual") {

                //Si no hay datos, no calcula nada
                if (!this.cupsIntervalsData?.length) return [];

                let intervals = [...this.cupsIntervalsData];

                const minDate = moment(intervals[0].endDate, "DD/MM/YYYY")
                    .subtract(1, "years");

                const numPeriods = intervals[0]?.periods?.length ?? 0;

                return intervals.reduce((acc, interval, index, array) => {

                    const isLast = index === array.length - 1;

                    let factor = 1;

                    if (isLast) {
                        const intervalPeriod = moment(interval.endDate, "DD/MM/YYYY")
                            .diff(moment(interval.startDate, "DD/MM/YYYY"), "days");

                        const daysInYear = moment(interval.endDate, "DD/MM/YYYY")
                            .diff(minDate, "days");

                        factor = intervalPeriod > 0 ? (daysInYear / intervalPeriod) : 0;
                    }

                    for (let i = 0; i < numPeriods; i++) {
                        const v = this.parseStringToNumber(interval.periods[i] ?? 0);
                        acc[i] += v * factor;
                    }

                    return acc;

                }, Array(numPeriods).fill(0));

            } else {

                return (this.consumptionData.consumption ?? []).map(v =>
                    (this.parseStringToNumber(v) / this.totalDays) * 365
                );
            }
        },
        totalConsumption() {
            const totalsByPeriod = this.totalConsumptionByPeriods;

            if (!totalsByPeriod?.length) return 0;

            return totalsByPeriod.reduce((acc, value) => acc + value, 0);
        },
        maxPower() {
            return Math.max(...this.consumptionData.power.map(n => this.parseStringToNumber(n)))
        },
        currentMonth(){
            return moment(this.dates?.start?.valueOf() + (this.dates?.end?.valueOf() - this.dates?.start?.valueOf()) / 2).format('YYYY-MM');
        },
        powerPeriods() {
            const arr = Array.isArray(this.consumptionData.power) ? this.consumptionData.power : [];
            const count = Math.max(6, arr.length);
            return Array.from({ length: count }, (_, index) => {
                const value = this.parseStringToNumber(arr[index]);
                return {
                    index,
                    value,
                    price: this.parseStringToNumber(this.prices.power?.[index]),
                    subtotal: this.parseStringToNumber(this.currentSubtotal.power?.[`P${index + 1}`]),
                };
            });
        },
        energyPeriods() {
            const arr = Array.isArray(this.consumptionData.consumption) ? this.consumptionData.consumption : [];
            const count = Math.max(6, arr.length);
            return Array.from({ length: count }, (_, index) => {
                const value = this.parseStringToNumber(arr[index]);
                return {
                    index,
                    value,
                    price: this.parseStringToNumber(this.prices.energy?.[index]),
                    subtotal: this.parseStringToNumber(this.currentSubtotal.energy?.[`P${index + 1}`]),
                };
            });
        },
        isDesktopView() {
            return this.windowWidth > 810
        }
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.updateWidth)
    },
}

</script>

<style scoped>
.selected-offer {
    background-color: rgb(154, 201, 240);
}

.pdf-loader {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.75);
    z-index: 9999;
}
.comparative-history-scroll {
    max-height: 85vh;
    overflow-y: auto;
    overflow-x: hidden;
    min-width: 700px;
}

.adjustment-tooltip-wrapper {
    position: relative;
    cursor: pointer;
}

.adjustment-tooltip-bubble {
    position: absolute;
    left: 50%;
    top: calc(100% + 8px);
    transform: translateX(-50%);
    background: #1f1f1f;
    color: #fff;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 12px;
    line-height: 1.2;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.18s ease, visibility 0.18s ease, transform 0.18s ease;
    z-index: 9999;
}

.adjustment-tooltip-bubble::before {
    content: "";
    position: absolute;
    top: -5px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-bottom: 5px solid #1f1f1f;
}

.adjustment-tooltip-wrapper:hover .adjustment-tooltip-bubble {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}

.comparative-user-custom-select {
    position: relative;
    z-index: 9999;
}

.comparative-user-select-content {
    display: block;
    position: absolute;
    top: calc(100% + 5px);
    right: 0;
    min-width: 180px;
    max-height: 250px;
    overflow-y: auto;
    z-index: 10000;
    pointer-events: auto;
}

.comparative-user-option {
    padding: 5px 0;
    user-select: none;
}
</style>
