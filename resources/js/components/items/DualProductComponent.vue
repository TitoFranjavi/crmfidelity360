<template>
    <div v-if="marketer" @click="hideCustomSelects">
        <form v-if="product.fees[feeSelect]" class="form register-pos">

            <!-- Nombre de la Comercializadora -->
            <div class="d-flex mt-10 mb-25 align-center f-wrap">

                <!--Logo y nombres-->
                <div class="d-flex align-center mr-50">
                    <img :src="'/assets/marketers_logo/' + marketer.logo" alt="Logo" class="h-50-px-max w-100-px-max contain-img"/>

                    <div class="d-flex align-center text ml-20" data-size="20" data-weight="700" v-on:click.stop="">
                        <!--Nombre comercializadora-->
                        <div>
                            <p class="ellipsis">{{ marketer.name }} -</p>
                        </div>

                        <!--Nombre producto luz-->
                        <div class="d-flex">
                            <i class="fa-regular fa-bolt ml-10 mr-5 my-auto"></i>
                            <p class="mx-5 ellipsis w-500-px-max">{{ product['electricity'] }}</p>
                            <!--<div v-else class="form-group">
                                <div class="input-group">
                                    <input type="text" v-model="product['electricity']" />
                                </div>
                            </div>-->
                        </div>


                        <span class="mx-10" data-size="18" data-weight="600">y</span>

                        <!--Nombre producto gas-->
                        <div class="d-flex">
                            <i class="fa-regular fa-fire-flame-simple ml-10 mr-5 my-auto"></i>
                            <p class="mx-5 ellipsis w-500-px-max">{{ product['gas'] }}</p>
                            <!--<div v-else class="form-group">
                                <div class="input-group">
                                    <input type="text" v-model="product['gas']" />
                                </div>
                            </div>-->
                        </div>

                    </div>
                </div>

                <!--Selector luz/gas-->
                <div class="d-flex column align-center">

                    <p class="text">Visualizando</p>

                    <div class="d-flex">
                        <i data-size="25" :data-color="dualEnergySelected === 'electricity' ? 'azul' : 'principal'" class="far fa-bolt text-center pointer mx-10" @click="dualEnergySelected = 'electricity'"></i>
                        <i data-size="25" :data-color="dualEnergySelected === 'gas' ? 'azul' : 'principal'" class="far fa-fire-flame-simple text-center pointer mx-10" @click="dualEnergySelected = 'gas'"></i>
                    </div>

                </div>

            </div>

            <!-- Datos tarifa y producto -->
            <div class="d-flex justify-between align-center">
                <div class="w-100 d-flex justify-between align-center">
                    <!--Desplegable tarifa-->
                    <div class="d-flex justify-between align-center">
                        <div class="custom-select"
                             :class="{ seeing: selectFeesActive }">
                            <div class="text mr-20"
                                 data-weight="400"
                                 v-on:click.stop="selectFees()">
                                Tarifa
                                <i class="fas ml-5" :class="[selectFeesActive? 'fa-chevron-up': 'fa-chevron-down',]"></i>
                            </div>

                            <!-- Despegable de Tarifas -->
                            <div v-if="selectFeesActive === true">
                                <div class="select-content left">
                                    <div v-for="(fee, i) in product.fees">
                                        <div class="d-flex justify-between">
                                            <div :data-color="i === feeSelect ? 'azul' : 'principal'" v-on:click.stop="changeFee(i, fee.id?.$oid)">
                                                {{ fee.electricity.name }} y {{ fee.gas.name }}
                                            </div>
                                            <div v-if="isEdit" @click="deleteProductFee(fee)">
                                                <i class="far fa-trash"
                                                   data-color="rojo"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!--botón añadir tarifa-->
                                    <div class="custom-button d-flex justify-between mt-10" data-size="small" data-bg="amarillo" v-if="isEdit" v-on:click.stop="isShowingFeeModal = true">
                                        Añadir Tarifa
                                        <i class="fa-solid fa-plus my-auto"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarifa Activa -->
                        <div class="text">
                            {{ product.fees[feeSelect].electricity.name }} y {{ product.fees[feeSelect].gas.name }}
                        </div>
                    </div>

                    <!--Modal añadir tarifa-->
                    <Teleport to=".boxBody" v-if="isShowingFeeModal">
                        <div class="floating-box z-100">
                            <div class="register-pos small w-50 h-auto h-98-max round" data-round="20" data-border-color="principal">

                                <!--Tarifas a añadir-->
                                <div v-if="electricityFeesWithState.some(f => f.active === true) || gasFeesWithState.some(f => f.active === true)">
                                    <!--Electricidad-->
                                    <div class="mt-20">
                                        <div class="text my-10 d-flex align-center" data-size="18">
                                            <i class="fa-regular fa-bolt mr-10"></i> Luz
                                        </div>

                                        <div class="d-flex justify-between align-center f-wrap">
                                            <div v-for="fee in electricityFeesWithState"
                                                 :key="fee.id"
                                                 class="pointer text"
                                                 :class="{ 'opacity-5': !fee.active }"
                                                 :data-color="fee.name === selectedElectricity ? 'azul' : 'principal'"
                                                 @click="selectElectricity(fee)">
                                                {{ fee.name }}
                                            </div>
                                        </div>
                                    </div>

                                    <!--Gas-->
                                    <div class="mt-20">
                                        <div class="text my-10 d-flex align-center" data-size="18">
                                            <i class="fa-regular fa-fire-flame-simple mr-10"></i> Gas
                                        </div>

                                        <div class="d-flex justify-between align-center f-wrap">
                                            <div v-for="fee in gasFeesWithState"
                                                 :key="fee.id"
                                                 class="pointer text"
                                                 :class="{ 'opacity-5': !fee.active }"
                                                 :data-color="fee.name === selectedGas ? 'azul' : 'principal'"
                                                 @click="selectGas(fee)">
                                                {{ fee.name }}
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="text-center opacity-5"
                                     v-else>
                                    ¡No hay más tarifas por añadir!
                                </div>

                                <!-- Botones -->
                                <div class="separator"></div>
                                <div class="d-flex justify-end" data-gap="20">
                                    <div class="custom-button"
                                         data-size="regular"
                                         data-bg="rojo"
                                         v-on:click.stop="closeFeeModal">
                                        Cancelar
                                    </div>

                                    <div v-if="selectedElectricity && selectedGas && (electricityFeesWithState.some(f => f.active === true) || gasFeesWithState.some(f => f.active === true))"
                                         class="custom-button"
                                         data-size="regular"
                                         v-on:click.stop="addProductFee">
                                        Crear
                                    </div>
                                </div>

                            </div>
                        </div>
                    </Teleport>

                    <!-- Tipo de Producto -->
                    <div class="mx-20 d-flex form-group">
                        <div class="text my-auto">Tipo de producto:</div>

                        <div v-if="isEdit" class="d-flex f-wrap" data-gap="5">
                            <div class="input-group mx-10">
                                <label class="mx-5" for="r"><i class="far fa-house mr-5"/>Residencial</label>
                                <input type="checkbox" id="r" v-model="product.fees[feeSelect].type.residencial"/>
                            </div>
                            <div class="input-group mx-10">
                                <label class="mx-5" for="p"><i class="far fa-building mr-5"/>PYME</label>
                                <input type="checkbox" id="p" v-model="product.fees[feeSelect].type.pyme"/>
                            </div>
                        </div>

                        <template v-else>
                            <div class="text mx-5 d-flex align-center" v-if="product.fees[feeSelect].type.residencial === true &&
                                    product.fees[feeSelect].type.pyme === true">
                                <i class="far fa-house-building mr-5"/>Residencial y PYME
                            </div>
                            <div class="text mx-5 d-flex align-center" v-else-if="product.fees[feeSelect].type.residencial === true">
                                <i class="far fa-house mr-5" />Residencial
                            </div>
                            <div class="text mx-5 d-flex align-center" v-else-if="product.fees[feeSelect].type.pyme === true">
                                <i class="far fa-building mr-5" />PYME
                            </div>
                        </template>
                    </div>

                    <!--Otras opciones luz o gas-->
                    <div class="d-flex" data-gap="10">

                        <!--Excedentes-->
                        <div class="d-flex justify-center align-center" v-if="dualEnergySelected === 'electricity'">
                            <div v-if="product.fees[feeSelect]?.electricity?.surplus && product.fees[feeSelect]?.electricity?.surplus !=='none' && !isEdit" class="d-flex form-group">
                                <div class="d-flex mx-20">
                                    <div class="text my-auto">Excedentes:</div>
                                    <div class="text mx-5 d-flex align-center">
                                        <i class="far fa-solar-panel mr-5 my-auto" />
                                        {{ product.fees[feeSelect]?.electricity?.surplus === "optional" ? "Opcional" : "Obligatorio" }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="isEdit" class="d-flex form-group">
                                <div class="input-group mx-10">
                                    <label class="mx-5 my-auto" for="surplus"><i class="far fa-solar-panel mr-5"/></label>
                                    <select v-model="product.fees[feeSelect].electricity.surplus">
                                        <option :value="null" disabled>Excedentes</option>
                                        <option value="none">No tiene</option>
                                        <option value="optional">Opcional</option>
                                        <option value="required">Obligatorio</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!--Tipo de precio-->
                        <div class="d-flex justify-center align-center">
                            <div v-if="product.fees[feeSelect][dualEnergySelected]?.priceType && !isEdit" class="d-flex form-group">
                                <div class="d-flex mx-20">
                                    <div class="text my-auto">
                                        Tipo de precio:
                                    </div>
                                    <div class="text mx-5 d-flex align-center">
                                        <i :class="[product.fees[feeSelect][dualEnergySelected].priceType === 'fixed'? 'fa-lock' : product.fees[feeSelect][dualEnergySelected].type === 'variable'? 'fa-chart-mixed': 'fa-chart-line','far mr-5',]"/>
                                        {{ product.fees[feeSelect][dualEnergySelected].priceType === "fixed"
                                        ? "Fijo"
                                        : product.fees[feeSelect][dualEnergySelected].priceType === "variable" ? "Fijo-variable" : "Indexado" }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="isEdit" class="d-flex form-group">
                                <div class="input-group mx-10">
                                    <label class="mx-5" for="indexed">
                                        <i :class="[product.fees[feeSelect][dualEnergySelected].priceType === 'fixed'? 'fa-lock' : product.fees[feeSelect][dualEnergySelected].priceType === 'variable'? 'fa-chart-mixed': 'fa-chart-line','far mr-5',]"/>
                                    </label>
                                    <select v-model="product.fees[feeSelect][dualEnergySelected].priceType">
                                        <option :value="null" disabled>Tipo precio</option>
                                        <option value="fixed">Fijo</option>
                                        <option value="variable">Fijo-Variable</option>
                                        <option value="indexed">Indexado</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!--Archivado-->
                        <div class="d-flex justify-center align-center">
                            <div v-if="product.fees[feeSelect]?.archived && !isEdit" class="d-flex form-group">
                                <div class="input-group mx-10">
                                    <label class="mx-5"><i class="far fa-box-archive mr-5"/>Archivado</label>
                                </div>
                            </div>
                            <div v-if="isEdit" class="d-flex form-group">
                                <div class="input-group mx-10">
                                    <label class="mx-5" for="archived"><i class="far fa-box-archive mr-5"/>Archivado</label>
                                    <input type="checkbox" id="archived" v-model="product.fees[feeSelect].archived"/>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Botones -->
                    <div v-if="canManage('products.edit') && canEdit">
                        <div class="custom-button"
                             data-size="regular"
                             v-if="!isEdit"
                             v-on:click.stop="changeEdit()">
                            Editar
                        </div>
                        <div class="d-flex justify-between"
                             data-gap="10"
                             v-else-if="isEdit">
                            <div class="custom-button"
                                 data-size="regular"
                                 data-bg="rojo"
                                 v-on:click.stop="changeEdit(true)">
                                Cancelar
                            </div>
                            <div class="custom-button"
                                 data-size="regular"
                                 v-on:click.stop="saveProd()">
                                Guardar
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="separator"></div>

            <!--Solo elec: Potencias y consumo-->
            <template v-if="isEdit && dualEnergySelected === 'electricity'">

                <div class="text ml-20 pb-10" data-size="20" data-weight="400">
                    Potencias y consumo
                </div>
                <div class="d-flex">
                    <p class="mr-10 text">Potencia Mínima (kW): </p>
                    <div class="form-group w-200-px-max mr-20">
                        <div class="input-group">
                            <input
                                type="text"
                                v-model="product.fees[this.feeSelect].electricity.minPower"
                                @input="validateNumeric('minPower')"
                            />
                        </div>
                    </div>
                    <p class="mr-10 text">Potencia Máxima (kW): </p>
                    <div class="form-group w-200-px-max mr-20">
                        <div class="input-group">
                            <input
                                type="text"
                                v-model="product.fees[this.feeSelect].electricity.maxPower"
                                @input="validateNumeric('maxPower')"
                            />
                        </div>
                    </div>
                    <p class="mr-10 text">Consumo Mínimo (kWh): </p>
                    <div class="form-group w-200-px-max mr-20">
                        <div class="input-group">
                            <input
                                type="text"
                                v-model="product.fees[this.feeSelect].electricity.minConsumption"
                                @input="validateNumeric('minConsumption')"
                            />
                        </div>
                    </div>
                    <p class="mr-10 text">Consumo Máximo (kWh): </p>
                    <div class="form-group w-200-px-max mr-20">
                        <div class="input-group">
                            <input
                                type="text"
                                v-model="product.fees[this.feeSelect].electricity.maxConsumption"
                                @input="validateNumeric('maxConsumption')"
                            />
                        </div>
                    </div>
                </div>
            </template>

            <div v-if="isEdit" class="separator"></div>

            <!-- Comentario -->
            <template v-if="product.fees[feeSelect][dualEnergySelected].comment || isEdit">
                <div class="text ml-20 pb-10" data-size="20" data-weight="400">
                    Observaciones
                </div>
                <p class="break-spaces" data-size="12" v-if="!isEdit">
                    {{ product.fees[this.feeSelect][dualEnergySelected].comment }}
                </p>
                <div class="form-group" v-else>
                    <div class="input-group">
                        <textarea v-model="product.fees[this.feeSelect][dualEnergySelected].comment"></textarea>
                    </div>
                </div>

                <div class="separator"></div>
            </template>

            <!-- Precios y gráficas -->

                <!--Electricidad-->
                <div id="prices" v-if="dualEnergySelected === 'electricity'">
                    <div class="text ml-20 pb-10" data-size="20" data-weight="400">
                        Precios
                    </div>
                    <div class="mobile-item">
                        <div class="d-flex justify-between f-wrap">
                            <!-- Potencia -->
                            <div class="w-100">
                                <!-- Tabla Potencias -->
                                <div>
                                    <div class="d-flex justify-center align-end text">
                                        Precio Potencia (€ kW día)
                                    </div>
                                    <div class="d-grid pl-50" data-gap="5" data-column="6">
                                        <div class="form-group ellipsis text" v-for="(pPower, period) in product.fees[this.feeSelect].electricity.prices.power">
                                            <div data-size="15">{{ period }}</div>
                                            <div v-if="!isEdit" class="text ellipsis" data-size="12"data-weight="400">
                                                {{ product.fees[this.feeSelect].electricity.prices.power[period] && Number(product.fees[this.feeSelect].electricity.prices.power[period]) !== 0
                                                ? product.fees[this.feeSelect].electricity.prices.power[period]
                                                : "" }}
                                            </div>
                                            <div class="input-group" v-else>
                                                <input type="text" :disabled="!isEdit" v-model=" product.fees[this.feeSelect].electricity.prices.power[period]"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="separator"></div>
                                <!-- Grafica potencia -->
                                <div v-if="!isEdit">
                                    <chart-simplebars-component :series="this.pricesPower"/>
                                </div>
                            </div>

                            <!-- Consumo -->
                            <div class="w-100">
                                <div>
                                    <div class="d-flex justify-center text">
                                        Precios Consumo (€ kWh)
                                    </div>
                                    <div class="d-grid pl-50" data-gap="5" data-column="6">
                                        <div class="form-group ellipsis text" v-for="(pConsume, period) in product.fees[this.feeSelect].electricity.prices.consume">
                                            <div data-size="15">{{ period }}</div>
                                            <div v-if="!isEdit" class="text ellipsis" data-size="12" data-weight="400">
                                                {{ product.fees[this.feeSelect].electricity.prices.consume[period] && Number(product.fees[this.feeSelect].electricity.prices.consume[period]) !== 0
                                                ? product.fees[this.feeSelect].electricity.prices.consume[period]
                                                : "" }}
                                            </div>
                                            <div class="input-group" v-else>
                                                <input type="text" :disabled="!isEdit" v-model="product.fees[this.feeSelect].electricity.prices.consume[period]"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="separator"></div>

                                <!--Grafica Consumo-->
                                <div v-if="!isEdit">
                                    <chart-simplebars-component :series="this.pricesConsume"/>
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
                                    <div class="d-flex justify-center align-end text">
                                        Precio Potencia (€ kW día)
                                    </div>
                                    <div class="d-grid pl-50" data-gap="5" data-column="6">
                                        <div class="form-group" v-for="(pPower, period) in product.fees[this.feeSelect].electricity.prices.power">
                                            <div class="text">{{ period }}</div>
                                            <div v-if="!isEdit" class="text ellipsis pl-10" data-weight="400">
                                                {{ product.fees[this.feeSelect].electricity.prices.power[period] && Number(product.fees[this.feeSelect].electricity.prices.power[period]) !== 0
                                                ? product.fees[this.feeSelect].electricity.prices.power[period]
                                                : "" }}
                                            </div>
                                            <div class="input-group" v-else>
                                                <input type="text" :disabled="!isEdit" v-model="product.fees[this.feeSelect].electricity.prices.power[period]"/>
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

                            <div class="separator m-20" data-position="vertical"></div>

                            <!-- Consumo -->
                            <div class="w-48 w-560-px-min">
                                <div>
                                    <div class="d-flex justify-center text">
                                        Precios Consumo (€ kWh)
                                    </div>
                                    <div class="d-grid pl-50" data-gap="5" data-column="6">
                                        <div class="form-group" v-for="(pConsume, period) in product.fees[this.feeSelect].electricity.prices.consume">
                                            <div>{{ period }}</div>
                                            <div v-if="!isEdit" class="text ellipsis pl-10" data-weight="400">
                                                {{ product.fees[this.feeSelect].electricity.prices.consume[period] && Number(product.fees[this.feeSelect].electricity.prices.consume[period]) !== 0
                                                ? product.fees[this.feeSelect].electricity.prices.consume[period]
                                                : ""
                                                }}
                                            </div>
                                            <div class="input-group" v-else>
                                                <input type="text" :disabled="!isEdit" v-model="product.fees[this.feeSelect].electricity.prices.consume[period]"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="separator"></div>
                                <!--Grafica Consumo-->
                                <div v-if="!isEdit">
                                    <chart-simplebars-component :series="this.pricesConsume"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Gas-->
                <div id="terms" v-else>
                    <div class="text ml-20 pb-10" data-size="20" data-weight="400">
                        Terminos
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
                                    <div class="d-grid pl-50" data-gap="5" data-column="6">
                                        <div class="form-group ellipsis" v-for="(pPower, period) in product.fees[this.feeSelect].gas.prices.power">
                                            <div data-size="15">{{ period }}</div>
                                            <div v-if="!isEdit" class="text ellipsis" data-size="12" data-weight="400">
                                                {{product.fees[this.feeSelect].gas.prices.power[period] && Number(product.fees[this.feeSelect].gas.prices.power[period]) !== 0
                                                ? product.fees[this.feeSelect].gas.prices.power[period]
                                                : "" }}
                                            </div>
                                            <div class="input-group" v-else>
                                                <input type="text" :disabled="!isEdit" v-model="product.fees[this.feeSelect].gas.prices.power[period]"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="separator"></div>
                                <!-- Grafica potencia -->
                                <div v-if="!isEdit">
                                    <chart-simplebars-component :series="productTerms"/>
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
                                    <div class="d-flex justify-center align-end">
                                        Terminos (€/kW)
                                    </div>
                                    <div class="d-grid pl-30" data-gap="5" data-column="2">
                                        <div class="form-group">
                                            <div>Fijo</div>
                                            <div v-if="!isEdit" class="text ellipsis pl-10" data-weight="400">
                                                {{ product.fees[this.feeSelect].gas.prices.fixed && Number(product.fees[this.feeSelect].gas.prices.fixed) !== 0
                                                ? product.fees[this.feeSelect].gas.prices.fixed
                                                : "" }}
                                            </div>
                                            <div class="input-group" v-else>
                                                <input type="text" :disabled="!isEdit" v-model="product.fees[this.feeSelect].gas.prices.fixed"/>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div>Variable</div>
                                            <div v-if="!isEdit" class="text ellipsis pl-10" data-weight="400">
                                                {{ product.fees[this.feeSelect].gas.prices.variable && Number(product.fees[this.feeSelect].gas.prices.variable) !== 0
                                                ? product.fees[this.feeSelect].gas.prices.variable
                                                : "" }}
                                            </div>
                                            <div class="input-group" v-else>
                                                <input type="text" :disabled="!isEdit" v-model="product.fees[this.feeSelect].gas.prices.variable"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="separator"></div>
                                <!-- Grafica potencia -->
                                <div v-if="!isEdit">
                                    <chart-simplebars-component :series="productTerms"/>
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

            </div>

            <div class="separator"></div>

            <!-- Comisiones -->
            <div id="commission">
                <CommissionsTableComponent
                    :canEdit="canManage('products.edit') && canEdit"
                    :type="type"
                    v-model:commissions="product['fees'][feeSelect][dualEnergySelected].commissions"
                    v-model:commissionType="product['fees'][feeSelect][dualEnergySelected].commissionType"
                    v-model:seeCommissionTypes="seeCommissionTypes"
                    :price-type="product['fees'][feeSelect][dualEnergySelected].priceType"
                    :marketer="marketer"
                    :product="product"
                    :isEditing="isEdit"
                    :basicData="basicData"
                    :feeSelect="feeSelect"
                    :dualEnergySelected="dualEnergySelected"
                />
            </div>
        </form>
    </div>
</template>


<script>

import CommissionsTableComponent from "@/components/items/CommissionsTableComponent.vue";

export default {
    name: "MarketerDetailsComponent",
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
            feeSelect: "",
            productId: "",
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
            dualEnergySelected: 'electricity',
            selectedElectricity: null,
            selectedGas: null,
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
                const bucket = this.type === "elec" ? "electricity" : this.type;
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
        selectFees() {
            this.selectFeesActive = !this.selectFeesActive;
        },
        async addProductFee() {
            let newFee = {
                electricity: {
                    name: this.selectedElectricity,
                    prices: {
                        consume: {
                            P1: 0,
                            P2: 0,
                            P3: 0,
                            P4: 0,
                            P5: 0,
                            P6: 0,
                        },
                        power: {
                            P1: 0,
                            P2: 0,
                            P3: 0,
                            P4: 0,
                            P5: 0,
                            P6: 0
                        }
                    },
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
                    surplus: 'none',
                    priceType: 'fixed'
                },
                gas: {
                    name: this.selectedGas,
                    prices: {
                        fixed: 0,
                        variable: 0
                    },
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
                    priceType: 'fixed'
                },
                type: {
                    pyme: true,
                    residencial: true
                }
            };

            this.marketer.products[this.type === "elec" ? "electricity" : this.type][this.productSelect].fees.push(newFee);

            //Ordeno
            this.marketer.products.dual[this.productSelect].fees.sort((a, b) => {
                const elecA = a.electricity?.name || "";
                const elecB = b.electricity?.name || "";

                const elecCompare = elecA.localeCompare(elecB);
                if (elecCompare !== 0) return elecCompare;

                const gasA = a.gas?.name || "";
                const gasB = b.gas?.name || "";

                return gasA.localeCompare(gasB);
            });


            //le vinculo en bbdd la nueva tarifa
            await axios
                .put(`/api/marketers/addProductFee`, {
                    marketer: this.marketer,
                    productInd: this.productSelect,
                    fee: newFee,
                    type: this.type === "elec" ? "electricity" : this.type,
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
        deleteProductFee(fee) {
            const bucket = this.type === "elec" ? "electricity" : this.type;
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

                    if (this.feeSelect === feeSelectedInd){
                        this.$router.push(`/marketers`);
                        return
                    }else //Sino borro la eliminada para no verla temporalmente
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
        buildDualRelations(dualProducts) {

            //Saco todos tarifas de luz y de gas, mientras no haya ninguna seleccionada estarán disponibles todas menos las que ya estén relacionadas con todas las
            //otras ej. si Tarifa 2.0TD ya está relacionada con todas las de gas saldrá como no disponible, una vez se seleccione una, sea de luz o de gas se marcarán
            //las contrarias con las que no han sido asignadas, es decir, si cojo Tarifa 2.0TD y ya está relacionada con RL1 y RL4 estas saldrán no disponibles.
            //Para ello mirare en product.fees.electricity.name y en product.fees.electricity.name


            const relations = {
                electricity: {}, // luz -> Set(gas)
                gas: {}          // gas -> Set(luz)
            };

            this.product.fees.forEach(fee => {
                const elecName = fee.electricity.name;
                const gasName = fee.gas.name;

                // luz → gas
                if (!relations.electricity[elecName]) {
                    relations.electricity[elecName] = new Set();
                }
                relations.electricity[elecName].add(gasName);

                // gas → luz
                if (!relations.gas[gasName]) {
                    relations.gas[gasName] = new Set();
                }
                relations.gas[gasName].add(elecName);
            });

            return relations;
        },
        selectElectricity(fee) {
            if (!fee.active) return;

            if (this.selectedElectricity !== fee.name)
                this.selectedElectricity = fee.name;
            else
                this.selectedElectricity = null;
        },
        selectGas(fee) {
            if (!fee.active) return;

            if (this.selectedGas !== fee.name)
                this.selectedGas = fee.name;
            else
                this.selectedGas = null;
        },
        async saveProd() {
            const bucket = this.type === "elec" ? "electricity" : this.type;

            try {
                // 🔁 SINCRONIZAR EL PRODUCTO EDITADO EN marketer.products[bucket] POR _id
                const list = this.marketer?.products?.[bucket] || [];
                const pid = this.product?._id?.$oid || this.product?._id; // productId actual
                const pIdx = list.findIndex(
                    (p) => (p?._id?.$oid || p?._id) === pid
                );

                // Reemplaza el producto dentro del array del marketer (evita refs reactivas raras)
                const productClean = JSON.parse(JSON.stringify(this.product));
                if (pIdx !== -1)
                    this.marketer.products[bucket].splice(pIdx, 1, productClean);
                else
                    this.marketer.products[bucket].push(productClean);


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

                this.dualPricesChart();


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
        changeFee(index, feeId) {
            this.feeSelect = index;

            this.dualPricesChart();

            this.$router.replace({
                query: {
                    ...this.$route.query,
                    feeId: feeId
                }
            });

            this.selectFeesActive = false;
        },
        dualPricesChart(){

            //Precios luz
            this.pricesPower = [];
            this.pricesConsume = [];

            for (let period in this.product.fees[this.feeSelect].electricity.prices.power) {
                this.pricesPower.push({
                    period: period,
                    value: parseFloat(
                        String(this.product.fees[this.feeSelect].electricity.prices.power[period]).replace(',', '.')
                    ),
                });
            }


            for (let period in this.product.fees[this.feeSelect].electricity.prices.consume) {
                this.pricesConsume.push({
                    period: period,
                    value: parseFloat(
                        String(this.product.fees[this.feeSelect].electricity.prices.consume[period]).replace(',', '.')
                    )
                });
            }


            //Precios gas
            this.productTerms = [
                {
                    period: "Fijo",
                    value: parseFloat(
                        String(this.product.fees[this.feeSelect].gas.prices.fixed).replace(',', '.')
                    ),
                },
                {
                    period: "Variable",
                    value: parseFloat(
                        String(this.product.fees[this.feeSelect].gas.prices.variable).replace(',', '.')
                    ),
                }
            ];
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
        changeEdit(cancel) {
            if (cancel) {
                this.product = JSON.parse(JSON.stringify(this.productDefault));
            }
            this.isEdit = !this.isEdit;
        },
        closeFeeModal() {
            this.isShowingFeeModal = false;
            this.selectedFeeToAdd = "";
            this.selectedElectricity = null;
            this.selectedGas = null;
            this.newFeeOptions = {
                breakdownType: "basic",
                commissionType: "c",
            };
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
        validateNumeric(field) {
            let value = this.product.fees[this.feeSelect][field];

            if (typeof value !== "string") value = value?.toString() || "";

            // SOLO permitir dígitos, comas y puntos
            value = value.replace(/[^0-9.,]/g, "");

            // Guardar ya limpiado
            this.product.fees[this.feeSelect][field] = value;
        },
        toggleSelectExtraProduct(extra) {
            if (!this.product.fees[this.feeSelect].extras) this.product.fees[this.feeSelect].extras = []

            if (this.product.fees[this.feeSelect].extras.includes(extra.id.$oid))
                this.product.fees[this.feeSelect].extras = this.product.fees[this.feeSelect].extras.filter(extraNow => extraNow !== extra.id.$oid);
            else
                this.product.fees[this.feeSelect].extras.push(extra.id.$oid);
        }
    },
    computed: {
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
        marketerProducts() {
            if (!this.marketer || !this.product) return [];

            let fees = this.marketer.fees[this.dualEnergySelected];

            const result = [];

            this.marketer.products[this.dualEnergySelected].forEach((product) => {product.fees.forEach((fee) => {
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
        userCommission() {
            const user = this.basicData.userLogged;
            if ( this.marketer.createdBy === "65cb57489c2c285441086a43" && this.basicData.userLogged._id !== "65cb57489c2c285441086a43" ) {
                return `com${user.commissions[this.marketer._id].value}`;
            } else if (
                user.label === "Usuario subdominio" ||
                user._id === "65d47559aa2d0448c308e252" ||
                user._id === "65d48ac808c6cf0254066c42" ||
                user._id === "6617a7ffc4f2475a7a010d32"
            ) {
                return "comAs";
            }
            return `com${user.commissions[this.marketer._id].value}`;
        },
        canEdit() {
            //Compruebo si el producto es de zoco y es zoco, en caso negativo devuelvo false
            if (this.marketer.createdBy === "65cb57489c2c285441086a43" && this.basicData.userLogged._id !== "65cb57489c2c285441086a43")
                return false;
            else
                return true;
        },
        dualRelations() {
            return this.buildDualRelations(this.marketer.products.dual || []);
        },
        electricityFeesWithState() {
            const relations = this.dualRelations;
            const allGasCount = this.$storage.FEES.gas.length;

            return this.$storage.FEES.electricity.map(feeName => {
                let active = true;
                let reason = null;

                // 🔸 Caso 1: hay gas seleccionado
                if (this.selectedGas) {
                    const relatedElec =
                        relations.gas[this.selectedGas] || new Set();
                    if (relatedElec.has(feeName)) {
                        active = false;
                        reason = 'Ya existe esta combinación';
                    }
                }

                // 🔸 Caso 2: no hay ninguna selección
                if (!this.selectedGas && !this.selectedElectricity) {
                    const relatedGas =
                        relations.electricity[feeName];
                    if (relatedGas && relatedGas.size >= allGasCount) {
                        active = false;
                        reason = 'Relacionada con todas las de gas';
                    }
                }

                return {
                    name: feeName,
                    active,
                    reason
                };
            });
        },
        gasFeesWithState() {
            const relations = this.dualRelations;
            const allElecCount = this.$storage.FEES.electricity.length;

            return this.$storage.FEES.gas.map(feeName => {
                let active = true;
                let reason = null;

                // 🔸 Caso 1: hay electricidad seleccionada
                if (this.selectedElectricity) {
                    const relatedGas =
                        relations.electricity[this.selectedElectricity] || new Set();
                    if (relatedGas.has(feeName)) {
                        active = false;
                        reason = 'Ya existe esta combinación';
                    }
                }

                // 🔸 Caso 2: no hay ninguna selección
                if (!this.selectedGas && !this.selectedElectricity) {
                    const relatedElec =
                        relations.gas[feeName];
                    if (relatedElec && relatedElec.size >= allElecCount) {
                        active = false;
                        reason = 'Relacionada con todas las de luz';
                    }
                }

                return {
                    name: feeName,
                    active,
                    reason
                };
            });
        },
        extraProductsToSelect() {
            if(!this.marketer?.extras) return []
            return this.marketer.extras.filter(extra => extra.productTypes.includes('cd') && extra.to.product && (!this.product.fees[this.feeSelect].extras || !this.product.fees[this.feeSelect].extras.includes(extra.id.$oid)))
        },
        extraProductsSelected() {
            if(!this.marketer?.extras) return []
            return this.marketer.extras.filter(extra => extra.productTypes.includes('cd') && extra.to.product && this.product.fees[this.feeSelect].extras && this.product.fees[this.feeSelect].extras.includes(extra.id.$oid))
        }
    }
}
</script>

<style scoped>

</style>
