<template>
    <div class="separator"></div>
    <div class="d-flex mt-20 pb-20">
        <div v-if="viewInputs" class="dashboard-card column mb-auto w-100">
            <!--Seleccionador-->
            <form class="d-flex column justify-between form h-565-px-min"
                  @submit.prevent="handleSubmit">
                <div class="d-flex column h-520-px-min relPos">
<!--                    <div class="absPos left mr-10 pointer" data-size="20" @click="viewStoredComparatives = true"><i class="far fa-history" /></div>-->
                    <h2 class="text text-center my-20">¿Qué quieres comparar?</h2>
                    <!--Seleccionador de opción de entrada-->
                    <div class="d-flex justify-center align-center" data-gap="10">
                        <div class="text pointer" data-size="16" @click="optionSelected = 'bill'">
                            <i :class="[optionSelected === 'bill' ? 'fas' : 'far', 'fa-file-invoice-dollar mr-15']" />Factura
                        </div>
                        <div class="separator h-30-px" data-position="vertical" />
                        <div class="text pointer" data-size="16" @click="optionSelected = 'cups'">
                            <i :class="[optionSelected === 'cups' ? 'fad' : '', 'far fa-meter-bolt mr-5']" />CUPS
                        </div>
                    </div>
                    <div class="separator h-2-px w-100 mx-auto"></div>
                    <!--Factura-->
                    <div v-if="optionSelected === 'bill'" class="mt-20 d-flex column" style="flex: 1;">
                        <div class="d-flex justify-center align-center mb-20" data-gap="10">
                            <div class="d-flex justify-center align-center" data-gap="10">
                                <drag-drop-component @update:files="getFilesSelected"/>
                            </div>
                        </div>
                        <div class="text mx-15 mt-auto">
                            * Algunos datos obtenidos pueden ser erróneos. Comprueba que sean correctos, o editalos en caso de error.
                        </div>
                    </div>
                    <!--CUPS-->
                    <div v-if="optionSelected === 'cups'" class="form-group d-flex justify-center" data-gap="50">
                        <div class="d-flex justify-center column" data-gap="5">
                            <p class="text ml-10" data-size="20" data-weight="600"><i class="fal fa-meter-bolt mr-5" />CUPS</p>
                            <p class="text mx-15 w-250-px opacity-8">Introduce los CUPS de los que quieres hacer la comparativa, con un CUPS por cada línea.</p>
                            <div class="input-group w-300-px h-300-px">
                                <textarea style="resize: none" v-model.trim="cups" name="cups" class="h-290-px" placeholder="Los CUPS aquí..." />
                            </div>
                        </div>
                        <div class="separator" data-position="vertical"></div>
                        <div class="d-flex column">
                            <p class="text text-center mb-30" data-size="20" data-weight="600">Precios</p>
                            <template v-for="(feePrices, feeName) in prices">
                                <p class="text text-center mb-15" data-size="16"><i class="fal fa-utility-pole mr-5" />Tarifa {{ feeName }}</p>
                                <div class="d-flex align-center" data-gap="15">
                                    <p class="text mt-20" data-size="14"><i class="fal fa-bolt mr-5" />Potencia:</p>
                                    <div class="input-group h-30-px mt-20 mr-35">
                                        <select v-model="feePrices.powerPricePeriod">
                                            <option value="day">€ kW día</option>
                                            <option value="month">€ kW mes</option>
                                            <option value="year">€ kW año</option>
                                        </select>
                                    </div>
                                    <div class="d-flex justify-center" data-gap="5">
                                        <div v-for="(_, index) of feePrices.power" :key="index">
                                            <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                            <div class="input-group w-80-px-max">
                                                <input
                                                    :placeholder="feePrices.powerPricePeriod === 'day' ? '€ kW día' : feePrices.powerPricePeriod === 'month' ? '€ kW mes' : '€ kW año'"
                                                    v-model.trim="feePrices.power[index]" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-center mt-5">
                                    <p class="text mt-20 mr-115" data-size="14"><i class="fal fa-lightbulb mr-5" />Energía (€ kWh)</p>
                                    <div class="d-flex justify-center" data-gap="5">
                                        <div v-for="(_, index) of feePrices.energy" :key="index">
                                            <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                            <div class="input-group w-80-px-max">
                                                <input placeholder="€ kWh" v-model.trim="feePrices.energy[index]" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="separator w-80 my-25 mx-auto"></div>
                            </template>
                        </div>
                    </div>
                </div>
                <div v-if="optionSelected !== ''" class="mt-20 d-flex justify-center">
                    <button class="custom-button text-center w-100" data-size="regular">Comparar</button>
                </div>
            </form>
        </div>
        <div v-else-if="viewPreview" class="dashboard-card column mb-auto w-100">
            <h2 class="text text-center my-20">Resumen de suministros</h2>
            <div class="d-flex justify-center mx-20 my-20" data-gap="50">
                <p class="text w-200-px text-center" data-size="20" data-weight="600"><i class="fal fa-meter-bolt mr-5" />CUPS</p>
                <p class="text w-500-px text-center" data-size="20" data-weight="600"><i class="fal fa-bolt mr-5" />Potencia</p>
                <p class="text w-500-px text-center" data-size="20" data-weight="600"><i class="fal fa-lightbulb mr-5" />Consumo</p>
            </div>
            <div class="separator"/>
            <template v-for="(feeData, feeName) in cupsData">
                <h3 v-if="Object.keys(feeData.cups).length > 0" class="ml-80"><i data-size="16" class="fal fa-utility-pole mr-5" />{{feeName}}</h3>
                <div v-if="Object.keys(feeData.cups).length > 0" v-for="(data, cups) in feeData.cups" class="d-flex justify-center mx-20 form align-center" data-gap="50">
                    <p class="mt-20 w-200-px" data-size="13">{{ cups }}</p>
                    <div class="d-flex justify-center form-group" data-gap="5">
                        <div v-for="(_, index) of data.power" :key="index">
                            <p class="text ml-10">{{ `P${index + 1}` }}</p>
                            <div class="input-group w-80-px-max">
                                <input placeholder="kWh" v-model.trim="data.power[index]" />
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-center form-group" data-gap="5">
                        <div v-for="(_, index) of data.consumption" :key="index">
                            <p class="text ml-10">{{ `P${index + 1}` }}</p>
                            <div class="input-group w-80-px-max">
                                <input placeholder="kWh" v-model.trim="data.consumption[index]" />
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="Object.keys(feeData.cups).length > 0" class="separator mb-20"/>
            </template>
            <template v-if="Object.keys(cupsWithoutData.cups).length > 0">
                <h3 class="ml-80">CUPS sin datos</h3>
                <div v-for="(data, cups) in cupsWithoutData.cups" class="d-flex justify-center mx-20 mb-10 form align-center" data-gap="50">
                    <div class="w-200-px mt-20 form-group">
                        <p class="w-200-px" data-size="13">{{ cups }}</p>
                        <div class="input-group mt-5">
                            <select v-model="data.fee" data-size="10">
                                <option value="">Selecciona una tarifa</option>
                                <option value="2.0TD">Tarifa 2.0TD</option>
                                <option value="3.0TD">Tarifa 3.0TD</option>
                                <option value="6.1TD">Tarifa 6.1TD</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-center form-group" data-gap="5">
                        <div v-for="(_, index) of data.power" :key="index">
                            <p class="text ml-10">{{ `P${index + 1}` }}</p>
                            <div class="input-group w-80-px-max">
                                <input placeholder="kWh" v-model.trim="data.power[index]" />
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-center form-group" data-gap="5">
                        <div v-for="(_, index) of data.consumption" :key="index">
                            <p class="text ml-10">{{ `P${index + 1}` }}</p>
                            <div class="input-group w-80-px-max">
                                <input placeholder="kWh" v-model.trim="data.consumption[index]" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="separator mb-20"/>
            </template>
            <div class="mt-20 d-flex justify-center">
                <button class="custom-button text-center w-100" data-size="regular" @click="compare">Comparar</button>
            </div>
        </div>
        <div v-else-if="viewOffers" class="dashboard-card column mb-auto w-40 mr-30">
            <div class="d-flex justify-between">
                <div class="d-flex" data-gap="15">
                    <button class="custom-button" data-size="small" @click="resetComparator">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </button>
                    <h3 class="text ellipsis mr-5">{{ `Comparativa ${numberOfCups} CUPS` }}</h3>
                </div>
            </div>
            <div class="separator h-2-px mx-auto"></div>
            <div class="relPos form">
                <div class="absPos" style="top: 0; right: 0;z-index: 1">
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
                <template v-if="!editingInputs">
                    <p data-size="16"><span class="opacity-6"><i class="fa-light fa-sigma"></i> Total:</span> <span
                        data-weight="600">{{ formatNumber(currentTotal,2) }}€</span></p>
                    <p data-size="16"><span class="opacity-6"><i class="fa-light fa-gauge-circle-bolt"></i> Consumo
                                total anual:</span> {{totalConsumption }}
                    </p>
                    <div class="separator w-80 my-25 mx-auto"></div>
                </template>
                <p class="text opacity-6 mt-10" data-size="16">CUPS</p>
                <template v-for="(feeData, feeName) in cupsData" :key="feeName">
                    <AccordionComponent v-if=" Object.keys(feeData.cups).length" v-model="feeData.viewFee">
                        <template #header>
                            <div class="d-flex mt-15 justify-between align-center">
                                <div class="d-flex align-center" data-gap="5">
                                    <i data-size="16" class="fal fa-utility-pole" />
                                    <p data-size="16" class="text" :data-weight="feeData.viewFee ? '600' : '400'">Tarifa {{feeName}}</p>
                                </div>
                                <div class="d-flex align-center" data-gap="10">
                                    <p class="text mr-10" data-size="16">{{Object.keys(feeData.cups).length ?? 0}} CUPS</p>
                                    <p data-size="16" class="text"><i class="fal fa-lightbulb mr-3" data-size="14" />
                                        {{formatEnergyNumber(feeData.totalConsumption)}}
                                    </p>
                                    <i data-size="16" :class="[feeData.viewFee ? 'fa-chevron-up' : 'fa-chevron-down','far pointer']" />
                                </div>
                            </div>
                        </template>
                        <template #body>
                            <div class="d-flex column mt-10 ml-25" data-gap="5">
                                <template v-for="(cupsData, cups) in feeData.cups" :key="cups">
                                    <AccordionComponent v-model="cupsData.viewCups">
                                        <template #header>
                                            <div class="d-flex justify-between align-center w-100" data-gap="10">
                                                <p class="text" data-size="14" :data-weight="cupsData.viewCups ? '600' : '400'">{{cups}}</p>
                                                <div class="d-flex align-center" data-gap="10">
                                                    <p class="text" data-size="14"><i class="fal fa-bolt mr-3" data-size="12" />{{formatNumber(cupsData.maxPower, 1)}} kW</p>
                                                    <p class="text" data-size="14"><i class="fal fa-lightbulb mr-3" data-size="12" />
                                                        {{formatEnergyNumber(cupsData.totalConsumption)}}
                                                    </p>
                                                    <i :class="[cupsData.viewCups ? 'fa-chevron-up' : 'fa-chevron-down','far pointer']" />
                                                </div>
                                            </div>
                                        </template>
                                        <template #body>
                                            <div class="d-flex column ml-15" data-gap="5">
                                                <div v-if="cupsData.viewCups" class="d-flex column">
                                                    <div class="d-flex align-center form-group mb-0" data-gap="15">
                                                        <p class="text" data-size="14"><i class="fal fa-bolt mr-5" />Potencia (kW)</p>
                                                    </div>
                                                    <div class="d-flex form-group mt-3" data-gap="5">
                                                        <div v-for="(_, index) of cupsData.power" :key="index">
                                                            <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                                            <div class="input-group w-80-px-max py-3">
                                                                <input placeholder="kW" v-model.trim="cupsData.power[index]" :disabled="!editingInputs" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <p class="text mt-10" data-size="14"><i class="fal fa-lightbulb mr-5" />Energía (kWh)</p>
                                                    <div class="d-flex form-group mt-3" data-gap="5">
                                                        <div v-for="(_, index) of cupsData.consumption" :key="index">
                                                            <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                                            <div class="input-group w-80-px-max py-3">
                                                                <input placeholder="kWh" v-model.trim="cupsData.consumption[index]" :disabled="!editingInputs" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="separator w-80 my-10 mx-auto"></div>
                                        </template>
                                    </AccordionComponent>
                                </template>
                            </div>
                        </template>
                    </AccordionComponent>
                </template>
                <!--CUPS sin datos-->
                <AccordionComponent v-if=" Object.keys(cupsWithoutData.cups).length" v-model="cupsWithoutData.viewFee">
                    <template #header>
                        <div class="d-flex mt-15 justify-between align-center">
                            <div class="d-flex align-center" data-gap="5">
                                <i data-size="16" class="fal fa-utility-pole" />
                                <p data-size="16" class="text" :data-weight="cupsWithoutData.viewFee ? '600' : '400'">Sin datos</p>
                            </div>
                            <div class="d-flex align-center" data-gap="10">
                                <p class="text mr-10" data-size="16">{{Object.keys(cupsWithoutData.cups).length ?? 0}} CUPS</p>
                                <i data-size="16" :class="[cupsWithoutData.viewFee ? 'fa-chevron-up' : 'fa-chevron-down','far pointer']" />
                            </div>
                        </div>
                    </template>
                    <template #body>
                        <div class="d-flex column mt-10 ml-25" data-gap="5">
                            <template v-for="(cupsData, cups) in cupsWithoutData.cups" :key="cups">
                                <AccordionComponent v-model="cupsData.viewCups">
                                    <template #header>
                                        <div class="d-flex justify-between align-center w-100" data-gap="10">
                                            <p class="text" data-size="14" :data-weight="cupsData.viewCups ? '600' : '400'">{{cups}}</p>
                                            <div class="d-flex align-center" data-gap="10">
                                                <i :class="[cupsData.viewCups ? 'fa-chevron-up' : 'fa-chevron-down','far pointer']" />
                                            </div>
                                        </div>
                                    </template>
                                    <template #body>
                                        <div class="d-flex column ml-15" data-gap="5">
                                            <div v-if="cupsData.viewCups" class="d-flex column">
                                                <div v-if="editingInputs" class="d-flex align-center form-group mb-0" data-gap="15">
                                                    <p class="text" data-size="14">Tarifa:</p>
                                                    <div class="input-group h-30-px mr-35">
                                                        <select v-model="cupsData.fee" :disabled="!editingInputs">
                                                            <option disabled :value="undefined">Seleccione una tarifa</option>
                                                            <option value="2.0TD">Tarifa 2.0TD</option>
                                                            <option value="3.0TD">Tarifa 3.0TD</option>
                                                            <option value="6.1TD">Tarifa 6.1TD</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-center form-group mb-0" data-gap="15">
                                                    <p class="text" data-size="14"><i class="fal fa-bolt mr-5" />Potencia (kW)</p>
                                                </div>
                                                <div class="d-flex form-group mt-3" data-gap="5">
                                                    <div v-for="(_, index) of cupsData.power" :key="index">
                                                        <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                                        <div class="input-group w-80-px-max py-3">
                                                            <input placeholder="kW" v-model.trim="cupsData.power[index]" :disabled="!editingInputs" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="text mt-10" data-size="14"><i class="fal fa-lightbulb mr-5" />Energía (kWh)</p>
                                                <div class="d-flex form-group mt-3" data-gap="5">
                                                    <div v-for="(_, index) of cupsData.consumption" :key="index">
                                                        <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                                        <div class="input-group w-80-px-max py-3">
                                                            <input placeholder="kWh" v-model.trim="cupsData.consumption[index]" :disabled="!editingInputs" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="separator w-80 my-10 mx-auto"></div>
                                    </template>
                                </AccordionComponent>
                            </template>
                        </div>
                    </template>
                </AccordionComponent>
            </div>
            <div class="separator w-80 my-25 mx-auto"></div>
            <div class="form">
                <p class="text opacity-6" data-size="16">Precios</p>
                <template v-for="(feePrices, feeName) in prices" :key="feeName">
                    <div v-if="Object.keys(cupsData[feeName].cups).length" class="d-flex mt-15 justify-between align-center" @click.stop="feePrices.viewFee = !feePrices.viewFee">
                        <div class="d-flex align-center" data-gap="5">
                            <i data-size="16" class="fal fa-utility-pole" />
                            <p data-size="16" class="text" :data-weight="feePrices.viewFee ? '600' : '400'">Tarifa {{feeName}}</p>
                        </div>
                        <i data-size="16" :class="[feePrices.viewFee ? 'fa-chevron-up' : 'fa-chevron-down','far pointer']" />
                    </div>
                    <transition name="accordion" v-on:before-enter="beforeEnter" v-on:enter="enter"
                                v-on:before-leave="beforeLeave" v-on:leave="leave">
                        <div v-if="feePrices.viewFee" class="d-flex column ml-25">
                            <div class="d-flex align-center form-group mb-0" data-gap="15">
                                <p class="text" data-size="14"><i class="fal fa-bolt mr-5" />Potencia:</p>
                                <div class="input-group h-30-px mr-35">
                                    <select v-model="feePrices.powerPricePeriod" :disabled="!editingInputs">
                                        <option value="day">€ kW día</option>
                                        <option value="month">€ kW mes</option>
                                        <option value="year">€ kW año</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex form-group mt-3" data-gap="5">
                                <div v-for="(_, index) of feePrices.power" :key="index">
                                    <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                    <div class="input-group w-80-px-max py-3">
                                        <input
                                            :placeholder="feePrices.powerPricePeriod === 'day' ? '€ kW día' : feePrices.powerPricePeriod === 'month' ? '€ kW mes' : '€ kW año'"
                                            v-model.trim="feePrices.power[index]" :disabled="!editingInputs" />
                                    </div>
                                </div>
                            </div>
                            <p class="text mt-10" data-size="14"><i class="fal fa-lightbulb mr-5" />Energía (€ kWh)</p>
                            <div class="d-flex form-group mt-3" data-gap="5">
                                <div v-for="(_, index) of feePrices.energy" :key="index">
                                    <p class="text ml-10">{{ `P${index + 1}` }}</p>
                                    <div class="input-group w-80-px-max py-3">
                                        <input placeholder="€ kWh" v-model.trim="feePrices.energy[index]" :disabled="!editingInputs" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </template>
            </div>
            <div class="separator w-80 my-25 mx-auto"></div>
            <div v-if="applyTaxes || editingInputs" class="d-flex column form" data-gap="5">
                <div class="d-flex align-center" data-gap="15">
                    <p class="text opacity-6" data-size="16">Impuestos</p>
                    <input v-if="editingInputs" type="checkbox" v-model="applyTaxes" />
                </div>
                <div class="d-flex align-center justify-between mx-10 form-group my-0">
                    <p class="text" data-size="14">IVA</p>
                    <div class="d-flex align-center" data-gap="5">
                        <div class="input-group w-100-px-max">
                            <input placeholder="%" v-model.trim="taxes.iva" :disabled="!editingInputs" />
                        </div>
                        <span class="w-45-px">%</span>
                    </div>
                </div>
                <div class="d-flex align-center justify-between mx-10 form-group my-0">
                    <p class="text" data-size="14">Impuesto eléctrico</p>
                    <div class="d-flex align-center" data-gap="5">
                        <div class="input-group w-100-px-max">
                            <input placeholder="%" v-model.trim="taxes.electricTax" :disabled="!editingInputs" />
                        </div>
                        <span class="w-45-px">%</span>
                    </div>
                </div>
                <div class="d-flex align-center justify-between mx-10 form-group my-0">
                    <p class="text" data-size="14">Aportación bono social</p>
                    <div class="d-flex align-center" data-gap="5">
                        <div class="input-group w-100-px-max">
                            <input placeholder="%" v-model.trim="taxes.socialBonus" :disabled="!editingInputs" />
                        </div>
                        <span class="w-45-px">€ día</span>
                    </div>
                </div>
            </div>
        </div>
        <!--Ofertas-->
        <div v-if="viewOffers" class="w-60">
            <div class="d-flex justify-between align-center form">
                <div class="d-flex align-center" data-gap="15">
                    <h2 class="text">Ofertas</h2>
                    <div class="custom-button " data-size="small" data-bg="azul" data-mode="translucent"
                         @click="viewFilters = !viewFilters">{{ viewFilters ? 'Ocultar' : 'Mostrar' }} filtros</div>
                    <div v-if="viewFilters" class="custom-button" data-size="small" data-bg="rojo"
                         data-mode="translucent" @click="resetFilters">Restablecer filtros</div>
                </div>
                <div class="d-flex align-center" data-gap="15">
                    <div class="custom-button" data-size="regular" data-bg="amarillo" @click="createOffer">
                        Añadir oferta <i class="fa-solid fa-plus"></i>
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
                            <img :src="`/assets/marketers_logo/${marketer.logo}`" class="h-80-max w-80-max contain-img" />
                            <img v-if="marketer.isZocoMarketer" src="/assets/enterprises/zocoenergia/logos/mini-dark.png" class="absPos h-15-px" style="bottom: 10px; right: 10px" />
                        </div>
                    </template>
                </div>
            </div>
            <template v-else>
                <div class="d-flex justify-between align-center mt-20 px-16">
                    <div class="d-flex justify-between" data-gap="15">
                        <p class="text pointer" :data-weight="filters.radio.groupBy.checked === 0 ? '600' : '400'" @click="selectNewGroupType('marketer')">
                            <i class="fa-regular fa-shop"></i> Comerci.
                        </p>
                        <p class="text pointer" :data-weight="filters.radio.groupBy.checked === 1 ? '600' : '400'" @click="selectNewGroupType('fee')">
                            <i class="fal fa-utility-pole"></i> Tarifa
                        </p>
                    </div>
                    <div class="d-flex justify-between" data-gap="15">
                        <p class="text pointer" :data-weight="filters.radio.sortBy.checked === 0 ? '600' : '400'" @click="selectNewOrderType('efficiency')">
                            <i class="fa-regular fa-chart-line-up"></i> Eficiencia
                        </p>
                        <div class="d-grid grid-justify-center w-300-px" data-column="3">
                            <p class="text text-center pointer" :data-weight="filters.radio.sortBy.checked === 1 ? '600' : '400'">
                                <i :class="[{ 'opacity-6': !viewCommissions }, 'far fa-hand-holding-dollar']"
                                data-size="16" @click="viewCommissions = !viewCommissions"></i>
                                <span @click="selectNewOrderType('commission')"> Com. </span>
                            </p>
                            <p class="text w-95-px pointer" :data-weight="filters.radio.sortBy.checked === 2 ? '600' : '400'" @click="selectNewOrderType('save')">
                                <i class="far fa-piggy-bank" data-size="16"></i> Ahorro
                            </p>
                            <p class="text"><i class="far fa-toolbox" data-size="16"></i> Opc.</p>
                        </div>
                    </div>
                </div>
                <div class="separator"></div>
                <!--Ofertas-->
                <!--Ofertas seleccionadas-->
                <div class="d-flex" data-gap="10">
                    <template v-for="(offer,fee) in offersSelected">
                        <div v-if="offer" class="dashboard-card column mb-20 w-300-px">
                            <div class="d-flex justify-between">
                                <p class="text" data-size="18">{{fee}}</p>
                                <div class="d-flex align-center" data-gap="5">
                                    <p :class="[{ 'opacity-0': !viewCommissions }, 'text']" data-size="14">
                                        {{ Intl.NumberFormat("es", {
                                        minimumFractionDigits: 0, maximumFractionDigits:
                                            2
                                    }).format(offer.commission ?? 0) }}€</p>
                                    <p class="text" data-size="20" data-weight="600"
                                       :data-color="offer.savePercent > 0 ? 'success' : 'rojo'">
                                        {{ Math.round(offer.save) }}€
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex align-center" data-gap="5">
                                <img class="h-20-px-max w-40-px-max contain-img" v-if="offer.marketer !== null"
                                     :src="`/assets/marketers_logo/${marketers.find(marketer => marketer.name === offer.marketer).logo}`"
                                     alt="logo" />
                                <img v-if="offer?.marketer?.includes('(ZOCO)')" src="assets/enterprises/zocoenergia/logos/mini-dark.png" class="absPos h-6-px bottom" style="left: 10px" />
                                <p class="text" data-size="16" data-weight="600">{{offer?.product}}</p>
                            </div>
                        </div>
                    </template>
                </div>
                <div v-if="Object.values(offersSelected).some(offer => offer !== null)" class="d-flex justify-between mx-10">
                    <p class="text" data-size="20">Total</p>
                    <div class="d-flex align-center" data-gap="5">
                        <p :class="[{ 'opacity-0': !viewCommissions }, 'text']" data-size="14">
                            {{ Intl.NumberFormat("es", {
                            minimumFractionDigits: 0, maximumFractionDigits:
                                2
                        }).format(Object.values(offersSelected).reduce((total, offer) => {return total + (offer?.commission ?? 0)},0) ?? 0) }}€</p>
                        <p class="text" data-size="20" data-weight="600"
                           :data-color="Object.values(offersSelected).reduce((total, offer) => {return total + (offer?.save ?? 0)},0) > 0 ? 'success' : 'rojo'">
                            {{ Math.round(Object.values(offersSelected).reduce((total, offer) => {return total + (offer?.save ?? 0)},0))}}€
                        </p>
                        <i class="far fa-file-spreadsheet text pointer ml-10" data-size="20" @click="generateExcel"/>
                    </div>
                </div>
                <div v-if="Object.values(offersSelected).some(offer => offer !== null)" class="separator" />
                <div v-if="Object.values(filteredOffers).every(arr => arr?.length === 0)" class="text p-16" data-size="20">No hay ofertas disponibles.
                </div>
                <!--Ofertas por tarifa-->
                <template v-if="filters.radio.groupBy.checked === 1" v-for="(data, fee) in filteredOffers">
                    <template v-for="offers in data">
                        <template v-if="offers.length > 0">
                            <div class="d-flex justify-between align-center ml-15 mr-30 text">
                                <h3 data-size="24">{{fee}}</h3>
                                <i :class="['far pointer', data.viewAll ? 'fa-chevron-up' : 'fa-chevron-down']" data-size="20" @click="offersList[fee].viewAll = !data.viewAll" />
                            </div>
                            <div v-if="offers.length > 0" v-for="index in (data.viewAll ? offers.length : Math.min(5, offers.length))" class="dashboard-card column p-16 mb-10 w-100">
                                <AccordionComponent v-model="offers[index - 1].viewPrices" >
                                    <template #header>
                                        <div class="d-flex justify-between align-center">
                                            <div class="d-flex">
                                                <img class="h-25-px-max w-50-px-max contain-img" v-if="offers[index - 1].marketer !== null"
                                                     :src="`/assets/marketers_logo/${marketers.find(marketer => marketer.name === offers[index - 1].marketer).logo}`"
                                                     alt="logo" />
                                                <p class="text ml-10" data-size="16"
                                                   data-weight="600">{{ offers[index - 1].product }}</p>
                                            </div>
                                            <div class="d-grid grid-justify-center w-300-px" data-column="3">
                                                <p :class="[{ 'opacity-0': !viewCommissions }, 'text']" data-size="18">
                                                    {{ Intl.NumberFormat("es", {
                                                    minimumFractionDigits: 0, maximumFractionDigits:
                                                        2
                                                }).format(offers[index - 1].commission ?? 0) }}€</p>
                                                <div class="d-flex align-center">
                                                    <p class="text">{{ Math.round(offers[index - 1].savePercent) }}%</p>
                                                    <p class="text ml-5" data-size="20" data-weight="600"
                                                       :data-color="offers[index - 1].savePercent > 0 ? 'success' : 'rojo'">
                                                        {{ Math.round(offers[index - 1].save) }}€
                                                    </p>
                                                </div>
                                                <div class="d-flex align-center" data-gap="10">
                                                    <i class="far fa-circle-info text pointer" data-size="16"
                                                       @click.stop="offers[index - 1].viewPrices = !offers[index - 1].viewPrices"></i>
                                                    <i :class="[offersSelected[fee]?.marketer === offers[index - 1].marketer && offersSelected[fee]?.product === offers[index - 1].product ? 'fad fa-memo-circle-check' : 'fa-memo','far text pointer']" data-size="16"
                                                       @click.stop="selectOffer(offers[index - 1], fee)"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template #body>
                                        <div class="d-flex">
                                            <div class="d-flex column align-center form w-90 ml-15">
                                                <div class="d-flex justify-between align-center w-100">
                                                    <div class="d-flex w-100 form-group" data-gap="5">
                                                        <p class="text mt-15 w-140-px" data-size="16">Ahorro:</p>
                                                        <div class="d-flex column w-90-px-min">
                                                            <p class="text text-center">Potencia</p>
                                                            <p class="text text-center" data-size="14">
                                                                {{ Intl.NumberFormat('es', {
                                                                minimumFractionDigits: 0, maximumFractionDigits:
                                                                    2
                                                            }).format(this.cupsData[fee].subTotal.power - offers[index - 1].subTotal.power) }}€</p>
                                                        </div>
                                                        <div class="d-flex column w-90-px-min">
                                                            <p class="text text-center">Energía</p>
                                                            <p class="text text-center" data-size="14">
                                                                {{ Intl.NumberFormat('es', {
                                                                minimumFractionDigits: 0, maximumFractionDigits:
                                                                    2
                                                            }).format(this.cupsData[fee].subTotal.energy - offers[index - 1].subTotal.energy) }}€</p>
                                                        </div>
                                                    </div>
                                                    <div v-if="offers[index - 1].marketer === null" class="mx-auto">
                                                        <button v-if="offerToEdit !== offers[index -1].timeStamp" class="custom-button nowrap" data-size="regular"
                                                                @click.stop="editOffer(fee,offers[index - 1].timeStamp)">
                                                            <i class="far fa-pen-to-square mr-5"></i>Editar
                                                        </button>
                                                        <div v-else class="d-flex justify-center" data-gap="10">
                                                            <button class="custom-button nowrap" data-size="regular" @click.stop="updateOffer(fee)">
                                                                <i class="fa-regular fa-floppy-disk mr-5"></i>Guardar
                                                            </button>
                                                            <button class="custom-button nowrap" data-size="regular" data-bg="rojo"
                                                                    @click.stop="cancelEditOffer(fee)">
                                                                <i class="fa-regular fa-xmark mr-5"></i>Cancelar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                                                <div class="d-flex align-center w-100 form-group" data-gap="10">
                                                    <p class="text mt-15 w-140-px" data-size="16">Potencia:</p>
                                                    <div class="d-flex" data-gap="5">
                                                        <div v-for="(_, i) of offers[index - 1].prices.power" :key="i">
                                                            <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                            <div class="input-group w-90-px-max">
                                                                <input placeholder="€ kW día" v-model.trim="offers[index - 1].prices.power[i]"
                                                                       :disabled="offerToEdit !== offers[index -1].timeStamp" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-center w-100 form-group" data-gap="10">
                                                    <p class="text mt-15 w-140-px" data-size="16">Energía:</p>
                                                    <div class="d-flex" data-gap="5">
                                                        <div v-for="(_, i) of offers[index - 1].prices.energy" :key="i">
                                                            <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                            <div class="input-group w-90-px-max">
                                                                <input placeholder="€ kWh" v-model.trim="offers[index - 1].prices.energy[i]"
                                                                       :disabled="offerToEdit !== offers[index -1].timeStamp" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="separator w-80"></div>
                                                <!--CUPS-->
                                                <AccordionComponent class="w-100" v-model="offers[index - 1].viewCups">
                                                    <template #header>
                                                        <div class="w-100 d-flex items-center justify-between">
                                                            <p class="text" data-size="16">CUPS</p>
                                                            <i data-size="16" :class="[offers[index - 1].viewCups ? 'fa-chevron-up' : 'fa-chevron-down','far pointer']" />
                                                        </div>
                                                    </template>
                                                    <template #body>
                                                        <div v-for="(offerCupsData,offerCups) in offers[index - 1].cups" class="d-flex justify-between align-center ml-10">
                                                            <p class="text">{{ offerCups }}</p>
                                                            <div class="d-grid grid-justify-center w-300-px" data-column="2">
                                                                <p :class="[{ 'opacity-0': !viewCommissions }, 'text']" data-size="16">
                                                                    {{ Intl.NumberFormat("es", {
                                                                    minimumFractionDigits: 0, maximumFractionDigits:
                                                                        2
                                                                }).format(offerCupsData.commission ?? 0) }}€</p>
                                                                <div class="d-flex align-center">
                                                                    <p class="text">{{ Math.round(offerCupsData.savePercent) }}%</p>
                                                                    <p class="text ml-5" data-size="18" data-weight="600"
                                                                       :data-color="offerCupsData.savePercent > 0 ? 'success' : 'rojo'">
                                                                        {{ Math.round(offerCupsData.save) }}€
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </AccordionComponent>
                                            </div>
                                        </div>
                                    </template>
                                </AccordionComponent>
                            </div>
                        </template>
                    </template>
                </template>
                <!--Ofertas por comercializadora-->
                <template v-if="filters.radio.groupBy.checked === 0" v-for="marketer in marketerOffers">
                    <div class="dashboard-card column p-16 mb-10 w-100">
                        <div class="d-flex justify-between align-center">
                            <div class="d-flex">
                                <img class="h-25-px-max w-50-px-max contain-img" v-if="marketer.name !== null"
                                     :src="`/assets/marketers_logo/${marketers.find(originalMarketer => originalMarketer.name === marketer.name).logo}`"
                                     alt="logo" />
                                <p class="text ml-10" data-size="18"
                                   data-weight="800">{{ marketer.name }}</p>
                            </div>
                            <div class="d-grid grid-justify-center w-300-px" data-column="3">
                                <p :class="[{ 'opacity-0': !viewCommissions }, 'text']" data-size="18">
                                    {{ Intl.NumberFormat("es", {
                                    minimumFractionDigits: 0, maximumFractionDigits:
                                        2
                                }).format(marketer.commission ?? 0) }}€</p>
                                <div class="d-flex align-center">
                                    <p class="text">{{ Math.round(marketer.savePercent) }}%</p>
                                    <p class="text ml-5" data-size="20" data-weight="600"
                                       :data-color="marketer.savePercent > 0 ? 'success' : 'rojo'">
                                        {{ Math.round(marketer.save) }}€
                                    </p>
                                </div>
                                <div class="d-flex align-center" data-gap="10">
                                    <i class="far fa-memo text pointer" data-size="16"
                                       @click.stop="selectOffers(marketer)"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <template v-for="(data, fee) in marketer.fees">
                        <template v-for="offers in data">
                            <template v-if="offers.length > 0">
                                <div class="d-flex justify-between align-center ml-30 mr-30 text">
                                    <h3 data-size="24">{{fee}}</h3>
                                    <i :class="['far pointer', data.viewAll ? 'fa-chevron-up' : 'fa-chevron-down']" data-size="20" @click="marketers.find(originalMarketer => originalMarketer.name === marketer.name).viewFees[fee] = !data.viewAll" />
                                </div>
                                <div v-if="offers.length > 0" v-for="index in (data.viewAll ? offers.length : Math.min(2, offers.length))" class="dashboard-card column ml-15 p-16 mb-10 w-100">
                                    <AccordionComponent v-model="offers[index - 1].viewPrices" >
                                        <template #header>
                                            <div class="d-flex justify-between align-center">
                                                <div class="d-flex">
                                                    <img class="h-25-px-max w-50-px-max contain-img" v-if="offers[index - 1].marketer !== null"
                                                         :src="`/assets/marketers_logo/${marketers.find(marketer => marketer.name === offers[index - 1].marketer).logo}`"
                                                         alt="logo" />
                                                    <p class="text ml-10" data-size="16"
                                                       data-weight="600">{{ offers[index - 1].product }}</p>
                                                </div>
                                                <div class="d-grid grid-justify-center w-300-px" data-column="3">
                                                    <p :class="[{ 'opacity-0': !viewCommissions }, 'text']" data-size="18">
                                                        {{ Intl.NumberFormat("es", {
                                                        minimumFractionDigits: 0, maximumFractionDigits:
                                                            2
                                                    }).format(offers[index - 1].commission ?? 0) }}€</p>
                                                    <div class="d-flex align-center">
                                                        <p class="text">{{ Math.round(offers[index - 1].savePercent) }}%</p>
                                                        <p class="text ml-5" data-size="20" data-weight="600"
                                                           :data-color="offers[index - 1].savePercent > 0 ? 'success' : 'rojo'">
                                                            {{ Math.round(offers[index - 1].save) }}€
                                                        </p>
                                                    </div>
                                                    <div class="d-flex align-center" data-gap="10">
                                                        <i class="far fa-circle-info text pointer" data-size="16"
                                                           @click.stop="offers[index - 1].viewPrices = !offers[index - 1].viewPrices"></i>
                                                        <i :class="[offersSelected[fee]?.marketer === offers[index - 1].marketer && offersSelected[fee]?.product === offers[index - 1].product ? 'fad fa-memo-circle-check' : 'fa-memo','far text pointer']" data-size="16"
                                                           @click.stop="selectOffer(offers[index - 1], fee)"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                        <template #body>
                                            <div class="d-flex">
                                                <div class="d-flex column align-center form w-90 ml-15">
                                                    <div class="d-flex w-100 form-group" data-gap="5">
                                                        <p class="text mt-15 w-140-px" data-size="16">Ahorro:</p>
                                                        <div class="d-flex column w-90-px-min">
                                                            <p class="text text-center">Potencia</p>
                                                            <p class="text text-center" data-size="14">
                                                                {{ Intl.NumberFormat('es', {
                                                                minimumFractionDigits: 0, maximumFractionDigits:
                                                                    2
                                                            }).format(this.cupsData[fee].subTotal.power - offers[index - 1].subTotal.power) }}€</p>
                                                        </div>
                                                        <div class="d-flex column w-90-px-min">
                                                            <p class="text text-center">Energía</p>
                                                            <p class="text text-center" data-size="14">
                                                                {{ Intl.NumberFormat('es', {
                                                                minimumFractionDigits: 0, maximumFractionDigits:
                                                                    2
                                                            }).format(this.cupsData[fee].subTotal.energy - offers[index - 1].subTotal.energy) }}€</p>
                                                        </div>
                                                    </div>
                                                    <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                                                    <div class="d-flex align-center w-100 form-group" data-gap="10">
                                                        <p class="text mt-15 w-140-px" data-size="16">Potencia:</p>
                                                        <div class="d-flex" data-gap="5">
                                                            <div v-for="(_, i) of offers[index - 1].prices.power" :key="i">
                                                                <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                                <div class="input-group w-90-px-max">
                                                                    <input placeholder="€ kW día" v-model.trim="offers[index - 1].prices.power[i]"
                                                                           disabled />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-center w-100 form-group" data-gap="10">
                                                        <p class="text mt-15 w-140-px" data-size="16">Energía:</p>
                                                        <div class="d-flex" data-gap="5">
                                                            <div v-for="(_, i) of offers[index - 1].prices.energy" :key="i">
                                                                <p class="text ml-5">{{ `P${i + 1}` }}</p>
                                                                <div class="input-group w-90-px-max">
                                                                    <input placeholder="€ kWh" v-model.trim="offers[index - 1].prices.energy[i]"
                                                                           disabled />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="separator w-80"></div>
                                                    <!--CUPS-->
                                                    <AccordionComponent class="w-100" v-model="offers[index - 1].viewCups">
                                                        <template #header>
                                                            <div class="w-100 d-flex items-center justify-between">
                                                                <p class="text" data-size="16">CUPS</p>
                                                                <i data-size="16" :class="[offers[index - 1].viewCups ? 'fa-chevron-up' : 'fa-chevron-down','far pointer']" />
                                                            </div>
                                                        </template>
                                                        <template #body>
                                                            <div v-for="(offerCupsData,offerCups) in offers[index - 1].cups" class="d-flex justify-between align-center ml-10">
                                                                <p class="text">{{ offerCups }}</p>
                                                                <div class="d-grid grid-justify-center w-300-px" data-column="2">
                                                                    <p :class="[{ 'opacity-0': !viewCommissions }, 'text']" data-size="16">
                                                                        {{ Intl.NumberFormat("es", {
                                                                        minimumFractionDigits: 0, maximumFractionDigits:
                                                                            2
                                                                    }).format(offerCupsData.commission ?? 0) }}€</p>
                                                                    <div class="d-flex align-center">
                                                                        <p class="text">{{ Math.round(offerCupsData.savePercent) }}%</p>
                                                                        <p class="text ml-5" data-size="18" data-weight="600"
                                                                           :data-color="offerCupsData.savePercent > 0 ? 'success' : 'rojo'">
                                                                            {{ Math.round(offerCupsData.save) }}€
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </AccordionComponent>
                                                </div>
                                            </div>
                                        </template>
                                    </AccordionComponent>
                                </div>
                            </template>
                        </template>
                    </template>
                    <div class="separator h-2-px w-100 my-30-px" />
                </template>
            </template>
        </div>
    </div>
    <!--Pantalla de carga-->
    <div class="loader-box" v-if="isLoading" style="position: fixed">
        <div class="d-flex w-500-px-min column align-center p-20 round" data-gap="10" data-bg="blanco">
            <div class="progress-bar my-auto">
                <div :class="'prog-' + loadingProgress"></div>
            </div>
            <p class="text" data-size="20" data-weight="600">Leyendo CUPS</p>
        </div>
    </div>
</template>

<script>
import { Workbook } from 'exceljs';
import AccordionComponent from "./AccordionComponent.vue";
import FileSaver from "file-saver";
import {calculateCommission} from "@/utils/calcCommission";

export default {
    name: 'ComparatorMultiPointComponent',
    components: {AccordionComponent},
    props: ["basicData"],
    data() {
        return{
            marketers: [],
            optionSelected: "",
            filesSelected: null,
            cups: "",
            prices: {
                "2.0TD": {
                    powerPricePeriod: "day",
                    viewFee: false,
                    power: ["", ""],
                    energy: ["", "", ""],
                },
                "3.0TD": {
                    powerPricePeriod: "day",
                    viewFee: false,
                    power: ["", "", "", "", "", ""],
                    energy: ["", "", "", "", "", ""],
                },
                "6.1TD": {
                    powerPricePeriod: "day",
                    viewFee: false,
                    power: ["", "", "", "", "", ""],
                    energy: ["", "", "", "", "", ""],
                },
            },
            cupsData: {
                "2.0TD": {
                    viewFee: false,
                    totalConsumption: 0,
                    total: 0,
                    subTotal: {energy: 0, power: 0, socialBonus: 0, electricTax: 0, iva: 0},
                    cups: {}
                },
                "3.0TD": {
                    viewFee: false,
                    totalConsumption: 0,
                    total: 0,
                    subTotal: {energy: 0, power: 0, socialBonus: 0, electricTax: 0, iva: 0},
                    cups: {},
                },
                "6.1TD": {
                    viewFee: false,
                    totalConsumption: 0,
                    total: 0,
                    subTotal: {energy: 0, power: 0, socialBonus: 0, electricTax: 0, iva: 0},
                    cups: {},
                },
            },
            cupsWithoutData: {
                viewFee: false,
                cups: {}
            },
            currentTotal: 0,
            currentSubtotal: {power: 0, energy: 0},
            applyTaxes: false,
            taxes: {
                iva: 21,
                electricTax: '5,11269632',
                meterDevice: 0,
                socialBonus: '0,019121',
            },
            editingInputs: false,
            inputsDefault: {},
            offersList: {
                "2.0TD": {viewAll: false, offers: []},
                "3.0TD": {viewAll: false, offers: []},
                "6.1TD": {viewAll: false, offers: []},
            },
            offersSelected: {
                "2.0TD": null,
                "3.0TD": null,
                "6.1TD": null,
            },
            offerToEdit: null,
            offerDefault: {},
            isLoading: false,
            viewInputs: true,
            viewPreview: false,
            viewOffers: false,
            viewFilters: false,
            viewCommissions: false,
            viewStoredComparatives: false,
            zocoCommissionRanges: null,
            filters: {
                marketers: [],
                residencial: true,
                pyme: true,
                fixed: true,
                variable: false,
                indexed: false,
                radio:{
                    sortBy: {
                        checked: 2
                    },
                    groupBy: {
                        checked: 1
                    }
                }
            },
            loadingProgress: 0,
        }
    },
    created() {
        this.fetchMarketers();
    },
    methods: {
        async fetchMarketers() {
            await axios.get('/api/marketers')
                .then((res) => {
                    this.marketers = res.data.marketers;


                    if (this.marketers[0].createdBy !== '65cb57489c2c285441086a43') {
                        this.fetchZocoMarketers();
                        this.fetchZocoCommissionRanges();
                    }

                    //Añado propiedad de ver productos por tarifa
                    this.marketers = this.marketers.map(marketer => {
                        return {...marketer, viewFees: {
                            "2.0TD":  false,
                            "3.0TD":  false,
                            "6.1TD":  false,
                        }}
                    })
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
                    const zocoMarketers = res.data.marketers.map(marketer => ({...marketer, name: `${marketer.name} (ZOCO)`, isZocoMarketer: true}));
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
            let error = "";

            try {
                if (this.optionSelected === "bill") {
                    const allCups = await this.getOCRData();
                    this.cups = [...new Set(allCups)].join('\n');
                    await this.getCupsData();
                } else if (this.optionSelected === "cups") {
                    //Compruebo que los CUPS tienen el formato correcto
                    let cupsRegex = /^ES\d{16}[a-z]{2}(?:[0-9][a-z])?$/im;
                    if (cupsRegex.test(this.cups)) {
                        //Compruebo que hay precios introducidos para alguna tarifa
                        if ((this.prices['2.0TD'].power.some(price => price) && this.prices['2.0TD'].energy.some(price => price)) ||
                            (this.prices['3.0TD'].power.some(price => price) && this.prices['3.0TD'].energy.some(price => price)) ||
                            (this.prices['6.1TD'].power.some(price => price) && this.prices['6.1TD'].energy.some(price => price))) {
                            //Obtengo los consumos de los cups
                            await this.getCupsData();
                        }else {
                            error = "Por favor rellena los precios de potencia y energía de las tarifas necesarias."
                        }
                    } else {
                        error = "Por favor comprueba que los CUPS introducidos son válidos."
                    }
                }
                if (error) {
                    await Swal.fire({
                        icon: "warning",
                        title: "Error en los datos",
                        text: error,
                    })
                } else {
                    this.viewInputs = false;
                    this.viewPreview = true;
                    if (this.filters.marketers.length === 0) {
                        this.filters.marketers = this.sortedMarketers.map((marketer) => marketer.name);
                    }
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

                await Swal.fire({
                    icon: "error",
                    title: errorMessage
                })

            } finally {
                this.isLoading = false;
                //Compruebo que no haya cups sin datos, en caso contrario aviso al usuario
                if(Object.keys(this.cupsWithoutData.cups).length > 0){
                    await Swal.fire({
                        icon: "warning",
                        title: "CUPS sin datos",
                        text: `No obtuvimos datos de ${Object.keys(this.cupsWithoutData.cups).length} CUPS, por favor ingrese los datos manualmente`
                    })
                }
            }
        },
        async getOCRData(){
            const pdfjsLib = await import('pdfjs-dist')
            pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(
                'pdfjs-dist/build/pdf.worker.mjs',
                import.meta.url
            ).toString()

            const regexCUPS = /ES\d{16}[A-Z]{2}/g

            return await Promise.all(
                this.files.map(async (file) => {
                    const arrayBuffer = await file.arrayBuffer()
                    const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise

                    let fullText = ''
                    for (let i = 1; i <= pdf.numPages; i++) {
                        const page = await pdf.getPage(i)
                        const content = await page.getTextContent()
                        fullText += content.items.map(item => item.str).join(' ') + '\n'
                    }

                    const match = fullText.match(regexCUPS)

                    return match ? match[0].replace(/\s/g, '') : null
                })
            )
        },
        async getCupsData() {
            try {
                const cupsList = this.cups.split('\n').filter(cups => cups.trim() !== '').map(cups => /^ES\d{16}[a-z]{2}[0-9][a-z]$/i.test(cups) ? cups.slice(0, -2) : cups);

                const total = cupsList.length;
                let completed = 0;
                this.progress = 0;

                for (const cups of cupsList) {
                    this.loadingProgress = Math.round(((completed + 1) / total) * 100);
                    try {
                        const response = await axios.get('/api/tools/getAPIConsumption', {
                            params: { CUPS: cups }
                        });

                        const { cupsData, fee } = response.data;

                        this.cupsData[fee].cups[cups] = {
                            viewCups: false,
                            ...cupsData,
                        };

                        this.cupsData[fee].cups[cups].consumption = this.cupsData[fee].cups[cups].consumption.map(consumption => Math.round(consumption));
                    } catch (error) {
                        console.warn(`Error en petición para ${cups}: ${error.message}`);

                        this.cupsWithoutData.cups[cups] = {
                            viewCups: false,
                            maxPower: 0,
                            totalConsumption: 0,
                            power: ["0", "0", "0", "0", "0", "0"],
                            consumption: ["0", "0", "0", "0", "0", "0"],
                            fee: ''
                        };
                    }

                    completed++;
                }

                this.calculateCupsStats();

            } catch (error) {
                console.error("Error al obtener los datos del CUPS", error);
            }
        },
        async compare(){
            //Si hay cups sin datos con tarifa, los agrego a cupsData
            if (Object.keys(this.cupsWithoutData.cups).length > 0) {
                // Recorremos una copia de las claves para poder borrar durante la iteración
                for (const cupsId of Object.keys(this.cupsWithoutData.cups)) {
                    const cupsData = this.cupsWithoutData.cups[cupsId];

                    // Si el usuario ya asignó un fee, lo movemos
                    if (cupsData.fee) {
                        const fee = cupsData.fee;

                        // Añadimos el cups al fee correspondiente
                        this.cupsData[fee].cups[cupsId] = cupsData;

                        // Eliminamos el cups de los que no tienen datos
                        delete this.cupsWithoutData.cups[cupsId];
                    }
                }
            }

            //Cálculos
            await this.calc();

            this.viewPreview = false;
            this.viewOffers = true;
        },
        async calc(){
            // Recorro las tarifas de cupsData
            for (const feeName in this.cupsData) {
                const fee = this.cupsData[feeName];
                //Reinicio el valor total
                fee.subTotal = {power: 0, energy: 0};
                fee.total = 0;

                //Reinicio todas las ofertas
                this.offersList[feeName].offers.forEach(offer => {
                    offer.subTotal = {power: 0, energy: 0, electricTax: 0, socialBonus: 0, iva: 0};
                    offer.total = 0;
                    offer.save = 0;
                    offer.commission = 0;
                });

                // Compruebo que la tarifa tiene precios
                if (this.prices[feeName].power.some(value => !!value) && this.prices[feeName].energy.some(value => !!value)) {

                    // Recorro los cups de esa tarifa
                    for (const cupsKey in fee.cups) {
                        const cups = fee.cups[cupsKey];

                        //Ajusto los precios según el periodo introducido
                        let powerPrices = this.prices[feeName].power;
                        switch (this.prices[feeName].powerPricePeriod) {
                            case "month":
                                powerPrices = powerPrices.map(price => this.parseStringToNumber(price) / 30)
                                break;
                            case "year":
                                powerPrices = powerPrices.map(price => this.parseStringToNumber(price) / 365)
                                break;
                        }

                        //Compruebo que el CUPS tiene datos
                        if (cups.consumption.length > 0) {
                            //Calculo el actual
                            const cupsSubTotal = this.calcTotal(cups, {power: powerPrices, energy:this.prices[feeName].energy});
                            fee.cups[cupsKey].subTotal = cupsSubTotal;
                            fee.cups[cupsKey].total = Object.values(cupsSubTotal).reduce((acc, val) => acc + val, 0);

                            //Lo añado al total de la tarifa
                            Object.keys(fee.subTotal).forEach(key => { fee.subTotal[key] += cupsSubTotal[key]});
                            fee.total = Object.values(fee.subTotal).reduce((acc, value) => acc + value, 0);

                            //Compruebo si hay ofertas, si no las hay las creo
                            if (this.offersList[feeName].offers.length === 0) this.createOffers(feeName);

                            //Calculo el total para las ofertas para este CUPS
                            this.offersList[feeName].offers.forEach(offer => {
                                const offerSubTotal = this.calcTotal(cups, offer.prices);
                                offer['cups'][cupsKey] = {
                                    subTotal: offerSubTotal,
                                    total: Object.values(offerSubTotal).reduce((acc, value) => acc + value, 0),
                                    save: cups.total - Object.values(offerSubTotal).reduce((acc, value) => acc + value, 0),
                                    commission: this.calcCommission(offer.marketerId, offer.feeInfo.commissions, offer.feeInfo.commissionType, cups.maxPower, cups.totalConsumption, offer.isZocoMarketer)
                                };

                                offer['cups'][cupsKey].savePercent = cups.total === 0 ? -1000 : (cups.total - offer['cups'][cupsKey].total) / cups.total * 100

                                Object.keys(offer.subTotal).forEach(key => { offer.subTotal[key] += offerSubTotal[key]});
                                offer.total += offer['cups'][cupsKey].total;
                                offer.save += fee.cups[cupsKey].total - offer['cups'][cupsKey].total;
                                offer.savePercent = fee.total === 0 ? -1000 : (fee.total - offer.total) / fee.total * 100;
                                offer.commission += this.parseStringToNumber(offer['cups'][cupsKey].commission);
                            })
                        }else{
                            await Swal.fire({
                                icon: "warning",
                                title: "No hay datos de consumo",
                                text: "No se han encontrado datos de consumo para el CUPS " + cupsKey,
                            })
                        }

                    }
                //En caso de haber cups con esta tarifa, avisar de rellenar los precios
                }else if(Object.keys(fee.cups).length > 0) {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Tarifa sin precios',
                        text: `La tarifa ${feeName} no tiene precios, pero existen CUPS con esta tarifa.`
                    })
                }
            }

            //Calculo el total a pagar
            this.currentTotal = Object.values(this.cupsData).reduce((acc, val) => acc + val.total,0)
        },
        calcTotal(cups,prices){
            let totalPower = 0, totalEnergy = 0;

            //Recorro todas las potencias y consumos, y los multiplico por su valor y el periodo de días
            for (let i = 0; i < 6; i++) {
                if (cups.power[i]) {
                    let fee = prices.fees ? this.parseStringToNumber(prices.fees.power[i]) / 30 : 0;
                    let price = this.parseStringToNumber(prices.power[i]) + fee;
                    let consumption = this.parseStringToNumber(cups.power[i])
                    totalPower += price * consumption * 365;
                }

                if (cups.consumption[i]) {
                    let fee = prices.fees ? this.parseStringToNumber(prices.fees.energy[i]) / 1000 : 0;
                    let price = this.parseStringToNumber(prices.energy[i]) + fee;
                    let consumption = this.parseStringToNumber(cups.consumption[i])
                    totalEnergy += price * consumption;
                }
            }

            //Redondeo el precio de potencia y consumo
            totalPower = Number(totalPower.toFixed(2));
            totalEnergy = Number(totalEnergy.toFixed(2));

            //Calculo impuestos si está activada la opción
            let socialBonus = 0;
            let electricTax = 0;
            let iva = 0;
            if (this.applyTaxes) {
                socialBonus = Number((this.parseStringToNumber(this.taxes.socialBonus) * 365).toFixed(2));

                electricTax = Number((this.parseStringToNumber(this.taxes.electricTax) * (totalPower + totalEnergy + socialBonus) / 100).toFixed(2));

                iva = Number((this.parseStringToNumber(this.taxes.iva) * (totalPower + totalEnergy + socialBonus + electricTax) / 100).toFixed(2));
            }

            return { power: totalPower, energy: totalEnergy, socialBonus, electricTax, iva };
        },
        calcCommission(marketer, commissions, commissionType, maxPower, totalConsumption, assignedToZoco) {
            const result = calculateCommission({
                userListTop: this.basicData.userListTop,
                assignedToZoco,
                marketer,
                commissionRanges: this.basicData.enterprise.commissionRanges,
                commissionRangesZoco: this.zocoCommissionRanges,
                commissions,
                commissionType,

                energyData: {
                    annual: totalConsumption,
                    byPeriods: this.totalConsumptionByPeriods
                },

                powerData: {
                    max: maxPower,
                    byPeriods: this.consumptionData.power
                },

                fees : [],
                manualCommissions: this.basicData?.userSubdomain?.settings?.manualCommissions
            });

            const user = this.basicData.userLogged;

            return result.breakdown.find(u => u.userId === user._id)?.commission
                ?? (user.label === 'Usuario subdominio' ? result.subdomain : 0);
        },
        createOffers(feeName){
            //Recorro todas las comercializadoras
            this.marketers.forEach(marketer => {
                //Obtengo la tarifa a comparar
                let feeMarketer = marketer.fees.electricity.find(fee => fee.name.includes(feeName))

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
                            },
                            total: 0,
                            subTotal: {power: 0, energy: 0, electricTax: 0, socialBonus: 0, iva: 0},
                            save: 0,
                            commissionType: product.commissionType,
                            commission: 0,
                            feeCommission: feeProduct,
                            viewPrices: false,
                            viewCups: false,
                            residencial: feeProduct.type.residencial,
                            pyme: feeProduct.type.pyme,
                            priceType: feeProduct.priceType,
                            isZocoMarketer: marketer.isZocoMarketer,
                            cups: {},
                        };

                        //Añado los fees si es producto variable
                        if (offer.priceType === "variable") {
                            offer.fees = {
                                power: {},
                                energy: {}
                            }
                        }

                        //Si tiene datos de precios, lo añado como posible oferta
                        if (parseFloat(offer.prices.power[0]) && parseFloat(offer.prices.energy[0])) {
                            this.offersList[feeName].offers.push(offer);
                        }
                    }
                });
            });
        },
        createOffer(fee) {
            //Cancelo la edición si está activada
            this.offerToEdit = null;

            //Creo un swal donde pregunto la tarifa
            Swal.fire({
                title: 'Selecciona una tarifa',
                input: 'select',
                inputOptions: {
                    '2.0TD': 'Tarifa 2.0TD',
                    '3.0TD': 'Tarifa 3.0TD',
                    '6.1TD': 'Tarifa 6.1TD'
                },
                inputPlaceholder: 'Selecciona una tarifa',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debes seleccionar una tarifa';
                    }
                    return null;
                },
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if(result.isConfirmed) {
                    const fee = result.value;

                    //Creo una oferta vacía y la añado a la lista de ofertas
                    let newOffer = {
                        marketer: null,
                        marketerId: null,
                        fee: null,
                        product: "Oferta",
                        prices: {
                            power: ["0", "0", "0", "0", "0", "0"],
                            energy: ["0", "0", "0", "0", "0", "0"]
                        },
                        total: 0,
                        subTotal: {power: 0, energy: 0, electricTax: 0, socialBonus: 0, iva: 0},
                        save: 0,
                        savePercent: 100,
                        commission: 0,
                        viewPrices: true,
                        timeStamp: Date.now().toString(),
                        cups: {},
                    };

                    this.offersList[fee].offers.push(newOffer);
                }
            })
        },
        editOffer(fee,timeStamp) {
            this.cancelEditOffer(); //Cancelo cualquier otra oferta que se esté editando
            this.offerToEdit = timeStamp; //Asigno el índice de la oferta que estoy editando
            this.offerDefault = JSON.parse(JSON.stringify(this.filteredOffers[fee].offers.find(offer => offer.timeStamp === timeStamp))) //Hago una copia de los datos para restaurar
            delete this.offerDefault.viewPrices; //Borro el boolean de mostrar precios, pues este es independiente de la edición
            this.offerEditingName = this.offerDefault.product;
        },
        updateOffer(feeName) {
            let offer = this.filteredOffers[feeName].offers.find(offer => offer.timeStamp === this.offerToEdit);

            //Reseteo las variables para controlar la edición
            this.offerToEdit = null;
            this.offerDefault = {};

            //Obtengo los datos de los CUPS
            const fee = this.cupsData[feeName];

            //Reseteo los valores de la oferta
            offer.subTotal = {power: 0, energy: 0, electricTax: 0, socialBonus: 0, iva: 0};
            offer.total = 0;
            offer.save = 0;
            offer.commission = 0;

            // Recorro los cups de esa tarifa
            for (const cupsKey in fee.cups) {
                const cups = fee.cups[cupsKey];

                const offerSubTotal = this.calcTotal(cups, offer.prices);
                offer['cups'][cupsKey] = {
                    subTotal: offerSubTotal,
                    total: Object.values(offerSubTotal).reduce((acc, value) => acc + value, 0),
                    save: cups.total - Object.values(offerSubTotal).reduce((acc, value) => acc + value, 0),
                };

                offer['cups'][cupsKey].savePercent = cups.total === 0 ? -1000 : (cups.total - offer['cups'][cupsKey].total) / cups.total * 100

                Object.keys(offer.subTotal).forEach(key => { offer.subTotal[key] += offerSubTotal[key]});
                offer.total += offer['cups'][cupsKey].total;
                offer.save += fee.cups[cupsKey].total - offer['cups'][cupsKey].total;
                offer.savePercent = fee.total === 0 ? -1000 : (fee.total - offer.total) / fee.total * 100;
            }
        },
        cancelEditOffer(fee) {
            //En caso de estar editando una oferta, devolverla a su valor por defecto
            if (this.offerToEdit !== null) {
                const indexInOriginal = this.offersList[fee].offers.findIndex(original => original.timeStamp === this.offerToEdit);
                this.offersList[fee].offers[indexInOriginal] = { ...this.offersList[indexInOriginal], ...JSON.parse(JSON.stringify(this.offerDefault)) };
                this.offerToEdit = null;
                this.offerDefault = {};
            }
        },
        selectOffer(offer, fee) {
            if(this.offersSelected[fee]?.product === offer.product) {
                this.offersSelected[fee] = null;
            }else{
                this.offersSelected[fee] = offer;
            }
        },
        selectOffers(marketer) {
            //Añado la primera oferta de cada tarifa
            Object.entries(marketer.fees).forEach(([fee, data]) => {
                this.offersSelected[fee] = data.offers[0];
            });
        },
        resetComparator() {
            this.optionSelected = "";
            this.filesSelected = null;
            this.cups = "";
            this.currentTotal = 0;
            this.currentSubtotal = {power: 0, energy: 0};
            this.viewInputs = true;
            this.viewPreview = false;
            this.viewOffers = false;
            this.offersList = {
                "2.0TD": {viewAll: false, offers: []},
                "3.0TD": {viewAll: false, offers: []},
                "6.1TD": {viewAll: false, offers: []},
            };
            this.offersSelected = {
                "2.0TD": null,
                    "3.0TD": null,
                    "6.1TD": null,
            };
            this.prices = {
                "2.0TD": {
                    powerPricePeriod: "day",
                    viewFee: false,
                    power: ["", ""],
                    energy: ["", "", ""],
                },
                "3.0TD": {
                    powerPricePeriod: "day",
                    viewFee: false,
                    power: ["", "", "", "", "", ""],
                    energy: ["", "", "", "", "", ""],
                },
                "6.1TD": {
                    powerPricePeriod: "day",
                    viewFee: false,
                    power: ["", "", "", "", "", ""],
                    energy: ["", "", "", "", "", ""],
                },
            };
            this.cupsData = {
                "2.0TD": {
                    viewFee: false,
                    totalConsumption: 0,
                    total: 0,
                    subTotal: {energy: 0, power: 0, socialBonus: 0, electricTax: 0, iva: 0},
                    cups: {}
                },
                "3.0TD": {
                    viewFee: false,
                    totalConsumption: 0,
                    total: 0,
                    subTotal: {energy: 0, power: 0, socialBonus: 0, electricTax: 0, iva: 0},
                    cups: {},
                },
                "6.1TD": {
                    viewFee: false,
                    totalConsumption: 0,
                    total: 0,
                    subTotal: {energy: 0, power: 0, socialBonus: 0, electricTax: 0, iva: 0},
                    cups: {},
                },
            };
            this.cupsWithoutData = {
                viewFee: false,
                    cups: {}
            };
            this.editingInputs = false;
            this.inputsDefault = {};
            this.offerToEdit = null;
            this.offerDefault = {};
            this.currentMessage = null;
        },
        async toBase64FromUrl(url){
            const response = await fetch(url);
            if (!response.ok) throw new Error(`No se pudo cargar la imagen: ${url}`);
            const blob = await response.blob();

            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result.split(",")[1]); // quitamos el prefijo
                reader.readAsDataURL(blob);
            });
        },
        async generateExcel(){
            const workbook = new Workbook();
            const sheet = workbook.addWorksheet("INFORME FINANCIERO");

            // --- CONFIGURACIÓN GENERAL ---
            sheet.properties.defaultRowHeight = 20;

            // const base64Logo = await this.toBase64FromUrl(`assets/enterprises/${this.basicData.userSubdomain._id}/logos/logo-light.png`);
            // 🔹 Cargar la imagen desde una ruta dinámica
            const response = await fetch(`assets/enterprises/${this.basicData.enterprise.asset_folder}/logos/mini-dark.png`);
            const blob = await response.blob();
            const arrayBuffer = await blob.arrayBuffer();

            // 🔹 Agregar la imagen al workbook
            const imageId = workbook.addImage({
                buffer: arrayBuffer,
                extension: 'png',
            });

            // 🔹 Colocar la imagen en el Excel (coordenadas)
            sheet.addImage(imageId, {
                tl: { col: 0, row: 0 },  // top-left (columna y fila de inicio)
                ext: { width: 205, height: 140 } // tamaño en píxeles
            });

            // --- ENCABEZADO SUPERIOR (logo + empresa) ---
            sheet.mergeCells("A1:A7"); // Hueco para logo
            const logoCell = sheet.getCell("A1");

            // --- BLOQUE DE DATOS EMPRESA / AYUNTAMIENTO (S5:V6) ---
            sheet.mergeCells("S1:V1");
            sheet.getCell("S1").value = "NOMBRE EMPRESA/AYUNTAMIENTO";
            sheet.mergeCells("S2:V2");
            sheet.getCell("S2").value = "C/ - Nº";
            sheet.mergeCells("S3:V3");
            sheet.getCell("S3").value = "C.P. - POBLACIÓN (CCAA)";
            sheet.mergeCells("S4:V4");
            sheet.getCell("S4").value = "X0000000X";

            // Bordes externos del cuadro principal (S1:V4)
            const topRow = 1;
            const bottomRow = 4;
            const leftCol = ["S"];
            const rightCol = ["V"];

            // Borde superior e inferior
            for (let colCode of ["S", "T", "U", "V"]) {
                sheet.getCell(`${colCode}${topRow}`).border = {
                    ...sheet.getCell(`${colCode}${topRow}`).border,
                    top: { style: "thin" },
                };
                sheet.getCell(`${colCode}${bottomRow}`).border = {
                    ...sheet.getCell(`${colCode}${bottomRow}`).border,
                    bottom: { style: "thin" },
                };
            }

            // Borde izquierdo y derecho
            for (let row = topRow; row <= bottomRow; row++) {
                sheet.getCell(`S${row}`).border = {
                    ...sheet.getCell(`S${row}`).border,
                    left: { style: "thin" },
                };
                sheet.getCell(`V${row}`).border = {
                    ...sheet.getCell(`V${row}`).border,
                    right: { style: "thin" },
                };
            }

            // Alineación y estilo del texto del bloque superior
            ["S1", "S2", "S3", "S4"].forEach((ref) => {
                const cell = sheet.getCell(ref);
                cell.alignment = { horizontal: "center", vertical: "middle" };
            });

            sheet.getCell("S1").font = { bold: true };

            // --- FILA INFERIOR: FECHA ESTUDIO Y N/R (S5:V5) ---
            sheet.mergeCells("S6:T6");
            sheet.getCell("S6").value = "FECHA ESTUDIO:";
            sheet.getCell("S6").alignment = { horizontal: "left", vertical: "middle" };
            sheet.getCell("S6").font = { bold: true };

            sheet.getCell("S8").value = "N/R";
            sheet.getCell("S8").alignment = { horizontal: "left", vertical: "middle" };
            sheet.getCell("S8").font = { bold: true };

            // --- TÍTULO PRINCIPAL ---
            sheet.mergeCells("A10:V10");
            const title = sheet.getCell("A10");
            title.value = "- INFORME FINANCIERO -";
            title.alignment = { horizontal: "center", vertical: "middle" };
            title.font = { bold: true, size: 16, color: { argb: "FFFFFFFF" } };
            title.fill = {
                type: "pattern",
                pattern: "solid",
                fgColor: { argb: "1f4e78" },
            };

            // --- CABECERAS DE TABLA (doble fila) ---
            const rowTop = 12;
            const rowBottom = 13;

            // Fila superior: bloques combinados
            sheet.getRow(rowTop).height = 22;
            sheet.getRow(rowBottom).height = 25;


            // Columnas individuales
            sheet.mergeCells(`A${rowTop}:A${rowBottom}`);
            sheet.getCell("A12").value = "CUPS";

            sheet.mergeCells(`B${rowTop}:B${rowBottom}`);
            sheet.getCell("B12").value = "DIRECCIÓN";

            sheet.mergeCells(`C${rowTop}:C${rowBottom}`);
            sheet.getCell("C12").value = "TARIFA\nACCESO";
            sheet.getCell(`C${rowTop}`).alignment = { horizontal: "center", vertical: "middle", wrapText: true };

            sheet.mergeCells(`D${rowTop}:D${rowBottom}`);
            sheet.getCell("D12").value = "DÍAS";

            // Bloque POTENCIA CONTRATADA (P1–P6)
            sheet.mergeCells(`E${rowTop}:J${rowTop}`);
            sheet.getCell("E12").value = "POTENCIA CONTRATADA (kW)";
            ["E", "F", "G", "H", "I", "J"].forEach((col, i) => {
                sheet.getCell(`${col}${rowBottom}`).value = `P${i + 1}`;
            });

            // Bloque POTENCIA PROPUESTA (P1–P6)
            sheet.mergeCells(`K${rowTop}:P${rowTop}`);
            sheet.getCell("K12").value = "POTENCIA PROPUESTA (kW)";
            ["K", "L", "M", "N", "O", "P"].forEach((col, i) => {
                sheet.getCell(`${col}${rowBottom}`).value = `P${i + 1}`;
            });

            // Resto de columnas
            //Nombre Doive en caso de ser Doive
            const billName = this.basicData.userSubdomain._id === '683d658761231bd1080b4802' ? '\nDOIVE' : '';

            const remainingHeaders = [
                ["Q", "CONSUMO\nANUAL\n(kWh)"],
                ["R", "COSTE\nFACTURA\nACTUAL"],
                ["S", `COSTE\nFACTURA${billName}`],
                ["T", "AHORRO\nMES\n(Estimado)"],
                ["U", "AHORRO\nANUAL\n(Estimado)"],
                ["V", "OBSERVACIONES"],
            ];

            remainingHeaders.forEach(([col, text]) => {
                sheet.mergeCells(`${col}${rowTop}:${col}${rowBottom}`);
                sheet.getCell(`${col}${rowTop}`).value = text;
            });

            // --- ESTILOS DE CABECERA ---
            const headerRange = sheet.getCell(`A${rowTop}:${remainingHeaders.at(-1)[0]}${rowBottom}`);
            for (let r = rowTop; r <= rowBottom; r++) {
                const row = sheet.getRow(r);
                row.eachCell((cell) => {
                    cell.font = { bold: true, color: { argb: "FFFFFFFF" } };
                    cell.alignment = { horizontal: "center", vertical: "middle", wrapText: true };
                    cell.fill = {
                        type: "pattern",
                        pattern: "solid",
                        fgColor: { argb: "1f4e78" },
                    };
                    cell.border = {
                        top: { style: "thin" },
                        left: { style: "thin" },
                        bottom: { style: "thin" },
                        right: { style: "thin" },
                    };
                });
            }

            let currentRow = rowBottom + 1;
            // --- DATOS ---
            for (const [tarifa, dataTarifa] of Object.entries(this.cupsData)) {
                if (!dataTarifa.cups) continue;

                for (const [cups, info] of Object.entries(dataTarifa.cups)) {
                    const offerInfo = this.offersSelected?.[tarifa]?.cups?.[cups];

                    const rowValues = [
                        cups,                     // CUPS
                        "",                       // DIRECCIÓN
                        tarifa,                   // TARIFA ACCESO
                        365,                       // DÍAS
                        ...(info.power || [0, 0, 0, 0, 0, 0]), // POTENCIA CONTRATADA (6)
                        0, 0, 0, 0, 0, 0,         // POTENCIA PROPUESTA (6)
                        info.totalConsumption || 0,            // CONSUMO ANUAL
                        info.total || 0,             // COSTE FACTURA ACTUAL
                        offerInfo.total || "" ,      //COSTE FACTURA OFERTA
                        { formula: `U${currentRow}/12` },      // AHORRO MES (T)
                        { formula: `R${currentRow}-S${currentRow}` },        // AHORRO ANUAL (U)
                        ""                           //OBSERVACIONES
                    ];

                    sheet.addRow(rowValues);
                    currentRow++;
                }
            }

            // --- ANCHOS DE COLUMNA ---
            sheet.columns = [
                { width: 30 },
                { width: 40 },
                { width: 8 },
                { width: 8 },
                { width: 6 },
                { width: 6 },
                { width: 6 },
                { width: 6 },
                { width: 6 },
                { width: 6 },
                { width: 6 },
                { width: 6 },
                { width: 6 },
                { width: 6 },
                { width: 6 },
                { width: 6 },
                { width: 13 },
                { width: 11 },
                { width: 11 },
                { width: 11 },
                { width: 11 },
                { width: 40 },
            ];

            // --- FORMATO DE TABLA ---
            const startDataRow = rowBottom + 1;
            sheet.eachRow((row, rowNumber) => {
                if (rowNumber >= startDataRow) {
                    row.eachCell((cell) => {
                        cell.border = {
                            top: { style: "thin" },
                            left: { style: "thin" },
                            bottom: { style: "thin" },
                            right: { style: "thin" },
                        };
                    });
                    // alternancia gris claro
                    if (rowNumber % 2 === 0) {
                        row.eachCell((cell) => {
                            cell.fill = {
                                type: "pattern",
                                pattern: "solid",
                                fgColor: { argb: "F2F2F2" },
                            };
                        });
                    }
                }
            });

            // --- FORMATO MONETARIO EN COLUMNAS R, S, T, U ---
            const endDataRow = currentRow - 1; // última fila de datos
            ["R", "S", "T", "U"].forEach((col) => {
                for (let r = startDataRow; r <= endDataRow; r++) {
                    const cell = sheet.getCell(`${col}${r}`);
                    cell.numFmt = '#,##0.00 "€"'; // formato de moneda
                }
            });

            // --- PIE DE PÁGINA ---
            const formulaRRange = `R${rowBottom + 1}:R${currentRow - 1}`;
            const formulaSRange = `S${rowBottom + 1}:S${currentRow - 1}`;
            const formulaURange = `U${rowBottom + 1}:U${currentRow - 1}`;
            const footerStart = sheet.lastRow.number + 3;

            // 🟥 BLOQUE IZQUIERDO (Costes actuales)
            sheet.getCell(`B${footerStart}`).value = "COSTE TOTAL ACTUAL AÑO :";
            sheet.getCell(`B${footerStart}`).font = { bold: true };
            sheet.getCell(`B${footerStart}`).alignment = { horizontal: "left", vertical: "middle" };
            sheet.getCell(`B${footerStart}`).border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };

            sheet.mergeCells(`C${footerStart}:G${footerStart}`);
            sheet.getCell(`C${footerStart}`).value = { formula: `SUM(${formulaRRange})` };
            sheet.getCell(`C${footerStart}`).numFmt = '#,##0.00 "€"';
            sheet.getCell(`C${footerStart}`).alignment = { horizontal: "center", vertical: "middle" };
            sheet.getCell(`C${footerStart}`).fill = {
                type: "pattern",
                pattern: "solid",
                fgColor: { argb: "FF0000" }, // rojo
            };
            sheet.getCell(`C${footerStart}`).font = { color: { argb: "FFFFFFFF" }, bold: true };
            sheet.getCell(`C${footerStart}`).border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };

            // Segunda fila (estimado)
            sheet.getCell(`B${footerStart + 2}`).value = "PROPUESTA ESTIMADA AÑO:";
            sheet.getCell(`B${footerStart + 2}`).font = { bold: true };
            sheet.getCell(`B${footerStart + 2}`).alignment = { horizontal: "left", vertical: "middle" };
            sheet.getCell(`B${footerStart + 2}`).border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };

            sheet.mergeCells(`C${footerStart + 2}:G${footerStart + 2}`);
            sheet.getCell(`C${footerStart + 2}`).value = { formula: `SUM(${formulaSRange})` };
            sheet.getCell(`C${footerStart + 2}`).numFmt = '#,##0.00 "€"';
            sheet.getCell(`C${footerStart + 2}`).alignment = { horizontal: "center", vertical: "middle" };
            sheet.getCell(`C${footerStart + 2}`).fill = {
                type: "pattern",
                pattern: "solid",
                fgColor: { argb: "FF0000" }, // rojo
            };
            sheet.getCell(`C${footerStart + 2}`).font = { color: { argb: "FFFFFFFF" }, bold: true };
            sheet.getCell(`C${footerStart + 2}`).border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };

            // Espacio entre bloques
            sheet.getRow(footerStart + 3).height = 30;

            // 🟦 BLOQUE CENTRAL (Diferencial año)
            sheet.mergeCells(`H${footerStart + 4}:L${footerStart + 4}`);
            sheet.getCell(`H${footerStart + 4}`).value = "DIFERENCIAL AÑO:";
            sheet.getCell(`H${footerStart + 4}`).font = { bold: true };
            sheet.getCell(`H${footerStart + 4}`).alignment = { horizontal: "right", vertical: "middle" };
            sheet.getCell(`H${footerStart + 4}`).border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };

            sheet.mergeCells(`M${footerStart + 4}:Q${footerStart + 4}`);
            sheet.getCell(`M${footerStart + 4}`).value = { formula: `SUM(${formulaURange})` };
            sheet.getCell(`M${footerStart + 4}`).numFmt = '#,##0.00 "€"';
            sheet.getCell(`M${footerStart + 4}`).alignment = { horizontal: "center", vertical: "middle" };
            sheet.getCell(`M${footerStart + 4}`).fill = {
                type: "pattern",
                pattern: "solid",
                fgColor: { argb: "BDD7EE" }, // azul claro
            };
            sheet.getCell(`M${footerStart + 4}`).font = { bold: true };
            sheet.getCell(`M${footerStart + 4}`).border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };

            // ⚪ BLOQUE DERECHO (Factura y desviación)
            sheet.mergeCells(`Q${footerStart}:S${footerStart}`);
            sheet.getCell(`Q${footerStart}`).value = "DIFERENCIAL ESTIMADO AÑO:";
            sheet.getCell(`Q${footerStart}`).font = { bold: true };
            sheet.getCell(`Q${footerStart}`).alignment = { horizontal: "right", vertical: "middle" };
            sheet.getCell(`Q${footerStart}`).border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };

            sheet.mergeCells(`T${footerStart}:U${footerStart}`);
            sheet.getCell(`T${footerStart}`).value = { formula: `SUM(${formulaURange})` };
            sheet.getCell(`T${footerStart}`).numFmt = '#,##0.00 "€"';
            sheet.getCell(`T${footerStart}`).alignment = { horizontal: "center", vertical: "middle" };
            sheet.getCell(`T${footerStart}`).border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };

            sheet.mergeCells(`Q${footerStart + 2}:S${footerStart + 2}`);
            sheet.getCell(`Q${footerStart + 2}`).value = "DESVIACIÓN %:";
            sheet.getCell(`Q${footerStart + 2}`).font = { bold: true };
            sheet.getCell(`Q${footerStart + 2}`).alignment = { horizontal: "right", vertical: "middle" };
            sheet.getCell(`Q${footerStart + 2}`).border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };

            sheet.mergeCells(`T${footerStart + 2}:U${footerStart + 2}`);
            sheet.getCell(`T${footerStart + 2}`).value = { formula: `T${footerStart} / C${footerStart}` };
            sheet.getCell(`T${footerStart + 2}`).numFmt = "0.00%";
            sheet.getCell(`T${footerStart + 2}`).alignment = { horizontal: "center", vertical: "middle" };
            sheet.getCell(`T${footerStart + 2}`).border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };

            // --- DESCARGA ---
            const buffer = await workbook.xlsx.writeBuffer();
            FileSaver.saveAs(new Blob([buffer]), "Informe_Financiero.xlsx");
        },
        editInputs() {
            //Activo la edición y copio los valores a editar
            this.editingInputs = true;
            this.inputsDefault = {
                cups: this.cups,
                currentTotal: this.currentTotal,
                cupsData: JSON.parse(JSON.stringify(this.cupsData)),
                cupsWithoutData: JSON.parse(JSON.stringify(this.cupsWithoutData)),
                prices: JSON.parse(JSON.stringify(this.prices)),
                applyTaxes: this.applyTaxes,
                taxes: JSON.parse(JSON.stringify(this.taxes)),
            }
        },
        updateInputs() {
            this.editingInputs = false;
            this.inputsDefault = {};

            //Si hay cups sin datos con tarifa, los agrego a cupsData
            if (Object.keys(this.cupsWithoutData.cups).length > 0) {
                // Recorremos una copia de las claves para poder borrar durante la iteración
                for (const cupsId of Object.keys(this.cupsWithoutData.cups)) {
                    const cupsData = this.cupsWithoutData.cups[cupsId];

                    // Si el usuario ya asignó un fee, lo movemos
                    if (cupsData.fee) {
                        const fee = cupsData.fee;

                        // Añadimos el cups al fee correspondiente
                        this.cupsData[fee].cups[cupsId] = cupsData;

                        // Eliminamos el cups de los que no tienen datos
                        delete this.cupsWithoutData.cups[cupsId];
                    }
                }
            }

            //Recalculamos las potencias máximas y consumos totales de cada CUPS
            this.calculateCupsStats();

            //Recalculamos los precios y sus comisiones
            this.calc();
        },
        cancelEditInputs() {
            if (this.editingInputs) {
                this.cups = this.inputsDefault.cups;
                this.currentTotal = this.inputsDefault.currentTotal;
                this.cupsData = JSON.parse(JSON.stringify(this.inputsDefault.cupsData));
                this.cupsWithoutData = JSON.parse(JSON.stringify(this.inputsDefault.cupsWithoutData));
                this.prices = JSON.parse(JSON.stringify(this.inputsDefault.prices));
                this.applyTaxes = this.inputsDefault.applyTaxes;
                this.taxes = JSON.parse(JSON.stringify(this.inputsDefault.taxes));

                this.editingInputs = false;
            }
        },
        resetFilters() {
            this.filters.marketers = this.sortedMarketers.map((marketer) => marketer.name);
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
        calculateCupsStats() {
            for (const [feeName, fee] of Object.entries(this.cupsData)) {
                for (const [cupsId, cupsData] of Object.entries(fee.cups)) {
                    if (cupsData.power?.length) {
                        cupsData.maxPower = Math.max(
                            ...cupsData.power.map(power => this.parseStringToNumber(power))
                        );
                    } else {
                        cupsData.maxPower = 0;
                    }

                    if (cupsData.consumption?.length) {
                        cupsData.totalConsumption = Math.round(
                            cupsData.consumption.reduce((acc, val) => acc + this.parseStringToNumber(val), 0)
                        );
                    } else {
                        cupsData.totalConsumption = 0;
                    }
                }

                // Actualizamos el total por tarifa
                fee.totalConsumption = Object.values(fee.cups).reduce(
                    (acc, val) => acc + (val.totalConsumption || 0),
                    0
                );
            }
        },
        selectNewOrderType(orderType) {
            switch (orderType) {
                case 'efficiency':
                    this.filters.radio.sortBy.checked = 0;
                    break;

                case 'commission':
                    this.filters.radio.sortBy.checked = 1;
                    break;

                case 'save':
                    this.filters.radio.sortBy.checked = 2;
                    break;
            }
        },
        selectNewGroupType(groupType) {
            switch (groupType) {
                case 'marketer':
                    this.filters.radio.groupBy.checked = 0;
                    break;

                case 'fee':
                    this.filters.radio.groupBy.checked = 1;
                    break;
            }
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
        formatNumber(number, decimals = 2) {
            return Intl.NumberFormat("es", {minimumFractionDigits: 0, maximumFractionDigits: decimals}).format(number)
        },
        formatEnergyNumber(value) {
            if (value >= 100000) {
                return `${this.formatNumber(value / 1000, 0)} MWh`;
            } else {
                return `${this.formatNumber(value)} kWh`;
            }
        },
        getFilesSelected(files) {
            this.files = files;
        },
        //Métodos para animar las ofertas
        beforeEnter: function (el) {
            el.style.height = '0';
            el.style.overflow = 'hidden';
        },
        enter: function (el) {
            // Usa requestAnimationFrame para asegurar que la transición funcione correctamente
            requestAnimationFrame(() => {
                el.style.height = el.scrollHeight + 'px';
            });
        },
        beforeLeave: function (el) {
            el.style.height = el.scrollHeight + 'px';
            el.style.overflow = 'hidden';
        },
        leave: function (el) {
            // Usa requestAnimationFrame para asegurar que la transición funcione correctamente
            requestAnimationFrame(() => {
                el.style.height = '0';
            });
        },

    },
    computed: {
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
        filteredOffers() {
            let sortedOffers = {...this.offersList};

            sortedOffers = Object.fromEntries(Object.entries(sortedOffers).map(([fee, value]) => {

                //Filtro por comercializadora, residencial, pyme, fijo, fijo-variable e indexado
                //En caso de ser oferta creada manualmente siempre aparece
                let filteredOffers = sortedOffers[fee].offers.filter((offer) => ((this.filters.marketers.includes(offer.marketer)) &&
                    ((this.filters.residencial && offer.residencial) || (this.filters.pyme && offer.pyme)) &&
                    ((this.filters.fixed && offer.priceType === 'fixed') || (this.filters.variable && offer.priceType === 'variable') || (this.filters.indexed && offer.priceType === 'indexed')))
                    || offer.marketer === null
                )

                //Ordeno
                switch (this.filters.radio.sortBy.checked) {
                    case 0:
                        //Eficiencia descendente
                        filteredOffers = filteredOffers.sort((a, b) => {
                            //En lugar de sobre el ahorro, lo hago sobre el precio de la oferta por lo que a menos mejor
                            return (a.total / a.commission) - (b.total / b.commission);
                        })
                        break;
                    case 1:
                        //Comisión descendente
                        filteredOffers = filteredOffers.sort((a, b) => {
                            return b.commission - a.commission;
                        })
                        break;
                    case 2:
                        //Ahorro descendente
                        filteredOffers = filteredOffers.sort((a, b) => {
                            return a.total - b.total;
                        })
                        break;
                }

                return [fee, {offers: filteredOffers, viewAll: sortedOffers[fee].viewAll}];
            }))

            return sortedOffers;
        },
        marketerOffers(){
          //De las ofertas filtradas, las agrupo por comercializadora, y ordeno las comercializadoras
            let marketers = {};

            // Recorro todas las tarifas (fees)
            Object.entries(this.filteredOffers).forEach(([fee, data]) => {
                // Para cada oferta en esta tarifa
                data.offers.forEach(offer => {
                    // Si la comercializadora no existe la creo
                    if (!marketers[offer.marketer]) {
                        marketers[offer.marketer] = {
                            name: offer.marketer,
                            id: offer.marketerId,
                            fees: {},
                            total: 0,
                            save: 0,
                            savePercent: 0,
                            commission: 0
                        };
                    }

                    // Si la tarifa no existe en la comercializadora, la creo
                    if (!marketers[offer.marketer].fees[fee]) {
                        marketers[offer.marketer].fees[fee] = {
                            offers: [],
                            viewAll: this.marketers.find(marketer => marketer.name === offer.marketer).viewFees[fee]
                        };
                    }

                    // Lo añadimos
                    marketers[offer.marketer].fees[fee].offers.push(offer);
                });
            });

            //Calculamos el ahorro/comisión de cada comercializadora
            Object.keys(marketers).forEach(marketerKey => {
                let marketer = marketers[marketerKey];
                let totalSum = 0;
                let commissionSum = 0;

                // Recorrer cada tarifa en fees
                Object.keys(marketer.fees).forEach(feeKey => {
                    const offersArray = marketer.fees[feeKey].offers;

                    // Si hay ofertas para esta tarifa, tomar la primera
                    if (offersArray && offersArray.length > 0) {
                        const firstOffer = offersArray[0];

                        // Sumar el total y commission de la primera oferta
                        totalSum += firstOffer.total || 0;
                        commissionSum += firstOffer.commission || 0;
                    }
                });

                // Actualizar los valores en el marketer
                marketer.total = totalSum;
                marketer.save = this.currentTotal - totalSum
                marketer.savePercent = this.currentTotal === 0 ? -1000 : (this.currentTotal - totalSum) / this.currentTotal * 100;
                marketer.commission = commissionSum;
            });

            // Ordenar los marketers según los valores calculados
            // Convertir a array y ordenar
            let marketersArray = Object.values(marketers);

            // Aplicar ordenación según el criterio seleccionado
            switch (this.filters.radio.sortBy.checked) {
                case 0: // Eficiencia (menor es mejor)
                    marketersArray.sort((a, b) => (a.total / a.commission) - (b.total / b.commission));
                    break;
                case 1: // Comisión (mayor es mejor)
                    marketersArray.sort((a, b) => b.commission - a.commission);
                    break;
                case 2: // Ahorro (menor total es mejor)
                    marketersArray.sort((a, b) => a.total - b.total);
                    break;
            }

            return marketersArray;

        },
        /*sortedMarketers() {
            //Ordeno alfabeticamente y filtro por comercializadoras que puede ver
            return this.marketers.toSorted((a, b) => {
                return a.name.localeCompare(b.name, "es", { sensitivity: 'base' })
            }).filter(marketer => this.basicData.userLogged.marketers.includes(marketer._id) || this.basicData.userLogged.label === 'Usuario subdominio');
        },*/
        totalConsumption() {
            return this.formatEnergyNumber(Object.values(this.cupsData).reduce((acc,val) => acc + val.totalConsumption, 0))
        },
        numberOfCups(){
            return this.cups.split('\n').filter(cups => cups.trim() !== '').length;
        }
    }
}
</script>
