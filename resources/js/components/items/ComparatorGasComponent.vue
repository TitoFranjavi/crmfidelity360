<template>
    <div class="separator"></div>
    <div :class="['d-flex mt-20 pb-20', {'column': !isDesktopView}]">
        <div :class="['dashboard-card column mb-auto', {'w-40 mr-30': isDesktopView, 'w-100': !isDesktopView}]">
            <!--Seleccionador-->
            <form v-if="viewInputs" class="d-flex column justify-between form h-565-px-min" @submit.prevent="handleSubmit">
                <div class="d-flex column h-520-px-min">
                    <h2 class="text text-center my-20">¿Qué quieres comparar?</h2>
                    <!--Seleccionador de opción de entrada-->
                    <div class="d-flex justify-center align-center" data-gap="10">
                        <div class="text pointer" data-size="16" @click="optionSelected = 'bill'"><i
                            :class="[optionSelected === 'bill' ? 'fas' : 'far', 'fa-file-invoice-dollar mr-15']"></i>Factura
                        </div>
                        <div class="separator h-30-px" data-position="vertical"/>
                        <div class="text pointer" data-size="16" @click="optionSelected = 'cups'"><i
                            :class="[optionSelected === 'cups' ? 'fas' : 'far', 'fa-meter-fire mr-5']"></i>CUPS
                        </div>
                        <div class="separator h-30-px" data-position="vertical"/>
                        <div class="text pointer" data-size="16" @click="optionSelected = 'manual'"><i
                            :class="[optionSelected === 'manual' ? 'fas' : 'far', 'fa-pen mr-10']"></i>Datos
                        </div>
                    </div>
                    <div class="separator h-2-px w-100 mx-auto"></div>
                    <!--Factura-->
                    <div v-if="optionSelected === 'bill'" class="mt-20 d-flex column" style="flex: 1;">
                        <div class="d-flex justify-center align-center" data-gap="10">
                            <p class="text" data-size="14">Añade tu factura:</p>
                            <div>
                                <button type="button" class="custom-button" data-size="medium" data-bg="principal"
                                        data-mode="translucent" @click="this.$refs.bill.click()">
                                    Adjuntar <i class="far fa-paperclip"></i>
                                </button>
                                <input type="file" ref="bill" multiple @change="getFilesSelected" style="display: none">
                            </div>
                        </div>
                        <div v-if="filesSelected" class="text ellipsis text-center px-10 mx-15 mt-30">
                            <i class="far fa-file mr-5" />{{filesSelected}}
                        </div>
                        <div class="text mx-15 mt-auto">
                            * Algunos datos obtenidos pueden ser erróneos.<br/><span class="ml-12">Comprueba que sean correctos, o editalos en caso de error.</span>
                        </div>
                    </div>
                    <!--CUPS-->
                    <div v-if="optionSelected === 'cups'" class="form-group">
                        <div class="d-flex justify-center align-center" data-gap="10">
                            <p class="text" data-size="14">CUPS:</p>
                            <div class="input-group w-220-px-max">
                                <input v-model.trim="cups" name="cups-gas" placeholder="ES0000XXXXXXXXXXXXAB0F"/>
                            </div>
                            <i class="far fa-magnifying-glass pointer" @click="searchCupsDialog = true"></i>
                        </div>
                        <div class="d-flex column align-center mt-40">
                            <div class="d-flex column w-500-px-max w-100">
                                <div class="d-flex justify-between" data-gap="15">
                                    <p class="text mb-10 ml-20" data-size="16">Término fijo:</p>
                                    <div class="d-flex" data-gap="10">
                                        <div class="input-group w-120-px h-30-px">
                                            <input :placeholder="fixedPricePeriod === 'day' ? '€ día' : fixedPricePeriod === 'month' ? '€ mes' : '€ año' " v-model.trim="prices.fixed"/>
                                        </div>
                                        <div class="input-group h-30-px mr-5">
                                            <select v-model="fixedPricePeriod">
                                                <option value="day">€ día</option>
                                                <option value="month">€ mes</option>
                                                <option value="year">€ año</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-between" data-gap="15">
                                    <p class="text mb-10 ml-20" data-size="16">Término variable:</p>
                                    <div class="d-flex align-center" data-gap="10">
                                        <div class="input-group w-120-px h-30-px">
                                            <input placeholder="€ kWh" v-model.trim="prices.variable"/>
                                        </div>
                                        <p class="text ml-10 w-80-px">€ kWh</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--Datos manuales-->
                    <div v-if="optionSelected === 'manual'" class="form-group d-flex column align-center">
                        <div class="d-flex justify-center align-center" data-gap="10">
                            <p class="text" data-size="14">Tarifa:</p>
                            <div class="input-group w-220-px-max">
                                <select v-model="fee">
                                    <option value="">Selecciona una tarifa</option>
                                    <option value="RL1">Tarifa RL1</option>
                                    <option value="RL2">Tarifa RL2</option>
                                    <option value="RL3">Tarifa RL3</option>
                                    <option value="RL4">Tarifa RL4</option>
                                    <option value="RL5">Tarifa RL5</option>
                                    <option value="RL6">Tarifa RL6</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex column mt-40 w-500-px-max w-100">
                            <div class="d-flex justify-between" data-gap="15">
                                <p class="text mb-10 ml-20" data-size="16">Término fijo:</p>
                                <div class="d-flex" data-gap="10">
                                    <div class="input-group w-120-px h-30-px">
                                        <input :placeholder="fixedPricePeriod === 'day' ? '€ día' : fixedPricePeriod === 'month' ? '€ mes' : '€ año' " v-model.trim="prices.fixed"/>
                                    </div>
                                    <div class="input-group h-30-px mr-5">
                                        <select v-model="fixedPricePeriod">
                                            <option value="day">€ día</option>
                                            <option value="month">€ mes</option>
                                            <option value="year">€ año</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-between" data-gap="15">
                                <p class="text mb-10 ml-20" data-size="16">Término variable:</p>
                                <div class="d-flex align-center" data-gap="10">
                                    <div class="input-group w-120-px h-30-px">
                                        <input placeholder="€ kWh" v-model.trim="prices.variable"/>
                                    </div>
                                    <p class="text ml-10 w-80-px">€ kWh</p>
                                </div>
                            </div>
                            <div class="d-flex justify-between" data-gap="15">
                                <p class="text mb-10 ml-20" data-size="16">Energía consumida</p>
                                <div class="d-flex align-center" data-gap="10">
                                    <div class="input-group w-120-px h-30-px">
                                        <input placeholder="€ kWh" v-model.trim="consumption"/>
                                    </div>
                                    <p class="text ml-10 w-80-px">kWh</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                    <button class="custom-button text-center w-100" data-size="regular" >Comparar</button>
                </div>
            </form>
            <!--Resumen de datos introducidos-->
            <div v-if="!viewInputs" class="form">
                <div class="d-flex justify-between">
                    <div class="d-flex" data-gap="15">
                        <button class="custom-button" data-size="small" @click="resetComparator">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </button>
                        <h3 :class="['text ellipsis mr-5',{'d-none': !isDesktopView}]">{{optionSelected === 'bill' ? opportunityData.name : cups !== "" ? `${supply?.nombreTitular} ${supply?.apellido1Titular}` : `Comparativa ${fee}`}}</h3>
                    </div>
                    <button :class="[{'cursor-not-allowed': editingInputs},'custom-button']" data-bg="azul" data-mode="translucent" data-size="small" data-weight="600" @click="togglePeriod">
                        <i class="fa-light fa-calendar-range"></i> {{ period === "month" ? "Mes" : "Año" }}
                    </button>
                </div>
                <div class="separator h-2-px mx-auto"></div>
                <div class="relPos">
                    <template v-if="!editingInputs">
                        <p v-if="optionSelected !== 'manual'" data-size="16"><span class="opacity-6">CUPS:</span> <span data-weight="600">{{ this.cups }}</span></p>
                        <p data-size="16"><span class="opacity-6"><i class="fa-light fa-sigma"></i> Total:</span> <span data-weight="600">{{ this.currentTotal.toLocaleString("es-ES", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}€</span></p>
                        <p data-size="16"><span class="opacity-6"><i class="fa-light fa-utility-pole-double"></i> Tarifa:</span> {{this.fee}}</p>
                        <p v-if="optionSelected === 'manual' || totalDays !== this.dates.end.diff(this.dates.start, 'days')" data-size="16"><span class="opacity-6"><i class="fa-light fa-calendar-range"></i> Días:</span> {{this.totalDays}} días</p>
                        <p v-else data-size="16"><span class="opacity-6"><i class="fa-light fa-calendar-range"></i> Fechas:</span> {{getPrettyDate(this.dates.start)}} al {{getPrettyDate(this.dates.end)}} ({{this.totalDays}} días)</p>
                        <p data-size="16"><span class="opacity-6"><i class="fa-light fa-gauge-circle-bolt"></i> Consumo:</span> {{Math.round(parseStringToNumber(this.consumption)).toLocaleString("es-ES")}} kWh</p>
                        <p data-size="16"><span class="opacity-6"><i class="fa-light fa-gauge-circle-bolt"></i> Consumo estimado anual:</span> {{Math.round(this.totalConsumption).toLocaleString("es-ES")}} kWh</p>
                    </template>
                    <template v-else>
                        <div class="d-flex mt-15" data-gap="10">
                            <p class="opacity-6" data-size="16">Total:</p>
                            <input class="simple-input w-100-px-max text text-end" data-size="16" v-model="currentTotal" /> €
                        </div>
                        <div class="d-flex align-center text form-group" data-gap="10">
                            <p class="opacity-6" data-size="16">Tarifa:</p>
                            <div class="input-group">
                                <select v-model="fee">
                                    <option value="">Selecciona una tarifa</option>
                                    <option value="RL1">Tarifa RL1</option>
                                    <option value="RL2">Tarifa RL2</option>
                                    <option value="RL3">Tarifa RL3</option>
                                    <option value="RL4">Tarifa RL4</option>
                                    <option value="RL5">Tarifa RL5</option>
                                    <option value="RL6">Tarifa RL6</option>
                                </select>
                            </div>
                        </div>
                        <div class="text form-group">
                            <template v-if="optionSelected !== 'manual'">
                                <div class="d-flex" data-gap="10">
                                    <p class="opacity-6" data-size="16">Fechas:</p>
                                    <div class="input-group">
                                        <select v-model="cupsInterval" @change="consumption = cupsIntervalsData[cupsInterval].consumoEnWhP1">
                                            <option value="">Selecciona una fecha</option>
                                            <option :value="index" v-for="(interval, index) of cupsIntervalsData">
                                                {{ getPrettyDate(interval.fechaInicioMesConsumo) }} al {{ getPrettyDate(interval.fechaFinMesConsumo) }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </template>
                            <div class="d-flex mt-15" data-gap="10">
                                <p class="opacity-6" data-size="16">Días:</p>
                                <input class="simple-input w-100-px-max text text-end" data-size="16" v-model="totalDays" /> días
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
                <div class="d-flex column mt-20 w-500-px-max form-group">
                    <div class="d-flex justify-between" data-gap="15">
                        <p class="text mb-10 ml-20" data-size="16">Término fijo:</p>
                        <div class="d-flex" data-gap="10">
                            <div class="input-group w-120-px h-30-px">
                                <input :placeholder="fixedPricePeriod === 'day' ? '€ día' : fixedPricePeriod === 'month' ? '€ mes' : '€ año' " v-model.trim="prices.fixed" :disabled="!editingInputs"/>
                            </div>
                            <div class="input-group h-30-px mr-5">
                                <select v-model="fixedPricePeriod" :disabled="!editingInputs">
                                    <option value="day">€ día</option>
                                    <option value="month">€ mes</option>
                                    <option value="year">€ año</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-between" data-gap="15">
                        <p class="text mb-10 ml-20" data-size="16">Término variable:</p>
                        <div class="d-flex align-center" data-gap="10">
                            <div class="input-group w-120-px h-30-px">
                                <input placeholder="€ kWh" v-model.trim="prices.variable" :disabled="!editingInputs"/>
                            </div>
                            <p class="text ml-10 w-80-px">€ kWh</p>
                        </div>
                    </div>
                    <div class="d-flex justify-between" data-gap="15">
                        <p class="text mb-10 ml-20" data-size="16">Energía consumida</p>
                        <div class="d-flex align-center" data-gap="10">
                            <div class="input-group w-120-px h-30-px">
                                <input placeholder="€ kWh" v-model.trim="consumption" :disabled="!editingInputs"/>
                            </div>
                            <p class="text ml-10 w-80-px">kWh</p>
                        </div>
                    </div>
                </div>
                <!--Impuestos y otros conceptos-->
                <div v-if="applyTaxes || editingInputs" class="d-flex column w-100 form-group" data-gap="5">
                    <div class="d-flex align-center mb-10" data-gap="15">
                        <p class="text opacity-6" data-size="16">Impuestos</p>
                        <input v-if="editingInputs" type="checkbox" v-model="applyTaxes"/>
                    </div>
                    <div class="d-flex align-center justify-between mx-10">
                        <p class="text" data-size="14">IVA</p>
                        <div class="d-flex align-center" data-gap="5">
                            <div class="input-group w-100-px-max">
                                <input placeholder="%" v-model.trim="taxes.iva"/>
                            </div>
                            <span>%</span>
                        </div>
                    </div>
                    <div class="d-flex align-center justify-between mx-10">
                        <p class="text" data-size="14">Impuesto hidrocarburos</p>
                        <div class="d-flex align-center" data-gap="5">
                            <div class="input-group w-100-px-max">
                                <input placeholder="%" v-model.trim="taxes.hidroTax"/>
                            </div>
                            <span>%</span>
                        </div>
                    </div>
                    <div class="d-flex align-center justify-between mx-10">
                        <p class="text" data-size="14">Alquiler equipo medida</p>
                        <div class="d-flex align-center" data-gap="5">
                            <div class="input-group w-60-px-max">
                                <input placeholder="€ día" v-model.trim="taxes.meterDevice"
                                       :disabled="!editingInputs" />
                            </div>
                            <div class="input-group h-30-px">
                                <select v-model="meterDevicePricePeriod" :disabled="!editingInputs">
                                    <option value="day">€ día</option>
                                    <option value="month">€ mes</option>
                                    <option value="year">€ año</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                </div>
                <div class="d-flex column w-100 form-group" data-gap="5">
                    <!--Gastos extra-->
                    <p v-if="editingInputs || otherConcepts.length > 0" class="text opacity-6" data-size="16">Gastos extra</p>
                    <div v-if="editingInputs" class="form-group d-flex align-center" data-gap="15">
                        <div class="d-flex w-100" data-gap="10">
                            <div class="input-group w-75" style="background-color: white"><input placeholder="Concepto" v-model="otherConcept.name"></div>
                            <div class="d-flex items-center w-25" data-gap="5">
                                <div class="input-group" style="background-color: white"><input placeholder="Cantidad" v-model="otherConcept.value"></div>
                                <span data-size="17">€</span>
                            </div>
                        </div>
                        <button class="custom-button" data-size="medium" @click="addOtherConcept"><i class="fa-solid fa-arrow-turn-down-left"></i></button>
                    </div>
                    <!--Listado-->
                    <div v-for="(concept, index) of otherConcepts" :key="concept.name" class="d-flex align-center px-20 w-100" data-gap="30">
                        <div class="d-flex justify-between w-100" data-gap="15">
                            <p data-size="14">{{concept.name}}</p>
                            <p data-size="14">{{concept.value}}€</p>
                        </div>
                        <i v-if="editingInputs" class="fa-solid fa-xmark pointer" data-size="20" @click="removeOtherConcept(index)"/>
                    </div>
                </div>
            </div>
        </div>
        <!--Tabla de ofertas-->
        <div v-if="viewOffers" class="w-60">
            <div class="d-flex justify-between align-center form">
                <div class="d-flex align-center" data-gap="15">
                    <h2 class="text">Ofertas</h2>
                    <div class="custom-button " data-size="small" data-bg="azul" data-mode="translucent" @click="viewFilters = !viewFilters">{{viewFilters ? 'Ocultar' : 'Mostrar'}} filtros</div>
                </div>
                <div class="custom-button" data-size="regular" data-bg="amarillo" @click="createOffer" >Añadir oferta <i class="fa-solid fa-plus"></i></div>
            </div>
            <!--Grid de comercializadoras-->
            <div v-if="viewFilters" class="mt-20 px-16">
                <p class="text" data-size="16"><i class="fa-regular fa-shop"></i> Comercializadoras</p>
                <div class="separator"></div>
                <div class="d-flex f-wrap" data-gap="20">
                    <div class="custom-button d-flex justify-center align-center p-10 h-100-px w-100-px" :data-bg="filters.marketers.length === sortedMarketers.length ? 'azul' : 'ventana-lateral'" data-mode="translucent" data-size="regular" @click="toggleMarketer()">
                        <p class="text" data-weight="600">Todas</p>
                    </div>
                    <template v-for="marketer of sortedMarketers">
                        <div class="custom-button d-flex justify-center align-center p-10 h-100-px w-100-px relPos" :data-bg="filters.marketers.includes(marketer.name) ? 'azul' : 'ventana-lateral'" data-mode="translucent" data-size="regular" @click="toggleMarketer(marketer.name)">
                            <img :src="`/assets/marketers_logo/${marketer.logo}`" class="h-80-max w-80-max contain-img" />
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
                            <label class="mr-10 text" for="residencial"><i class="far fa-house mr-5" />Residencial</label>
                            <input type="checkbox" id="residencial" v-model="filters.residencial">
                        </div>
                        <div>
                            <label class="mr-10 text" for="pyme"><i class="far fa-building mr-5" />PYME</label>
                            <input type="checkbox" id="pyme" v-model="filters.pyme">
                        </div>
                    </div>
                    <!--Fijo y indexado-->
                    <div class="d-flex" data-gap="30">
                        <div>
                            <label class="mr-10 text" for="fixed"><i class="far fa-lock mr-5" />Fijo</label>
                            <input type="checkbox" id="fixed" v-model="filters.fixed">
                        </div>
                        <div>
                            <label class="mr-10 text" for="variable"><i class="far fa-chart-line mr-5" />Fijo-Variable</label>
                            <input type="checkbox" id="variable" v-model="filters.variable">
                        </div>
                        <div>
                            <label class="mr-10 text" for="indexed"><i class="far fa-chart-line mr-5" />Indexado</label>
                            <input type="checkbox" id="indexed" v-model="filters.indexed">
                        </div>
                    </div>
                </div>
            </div>
            <template v-else>
                <div class="d-flex justify-between align-center mt-20 px-16">
                    <p class="text pointer" data-size="16" @click="selectNewOrderType('offer')">
                        <i class="fa-regular fa-shop"></i> Oferta <i data-size="13" :class="['fas',filters.radio.sortBy.checked === 0 ? 'fa-sort-down' : (filters.radio.sortBy.checked === 1 ? 'fa-sort-up' : 'fa-sort')]"/>
                    </p>
                    <div class="d-flex justify-between" data-gap="25">
                        <p class="text pointer" @click="selectNewOrderType('efficiency')"><i class="fa-regular fa-chart-line-up"></i> Eficiencia <i :class="['fas',filters.radio.sortBy.checked === 2 ? 'fa-sort-down' : (filters.radio.sortBy.checked === 3 ? 'fa-sort-up' : 'fa-sort')]"/></p>
                        <div class="d-grid grid-justify-center w-300-px" data-column="3">
                            <p class="text text-center pointer"><i :class="[{'opacity-6' : !viewCommissions},'far fa-hand-holding-dollar']" data-size="16" @click="viewCommissions = !viewCommissions"></i>
                                <span @click="selectNewOrderType('commission')"> Com. <i :class="['fas',filters.radio.sortBy.checked === 4 ? 'fa-sort-down' : (filters.radio.sortBy.checked === 5 ? 'fa-sort-up' : 'fa-sort')]"/></span></p>
                            <p class="text w-95-px pointer" @click="selectNewOrderType('save')"><i class="far fa-piggy-bank" data-size="16"></i> Ahorro <i :class="['fas',filters.radio.sortBy.checked === 6 ? 'fa-sort-down' : (filters.radio.sortBy.checked === 7 ? 'fa-sort-up' : 'fa-sort')]" /></p>
                            <p class="text"><i class="far fa-toolbox" data-size="16"></i> Opc.</p>
                        </div>
                    </div>
                </div>
                <div class="separator"></div>
                <!--Ofertas-->
                <div v-if="filteredOffers.length === 0" class="text p-16" data-size="20">No hay ofertas disponibles.</div>
                <div v-for="(offer,index) of filteredOffers" class="dashboard-card column p-16 mb-10 w-100">
                    <div class="d-flex justify-between align-center">
                        <div class="d-flex">
                            <img class="h-25-px w-50-px-max contain-img" v-if="offer.marketer !== null"
                                 :src="`/assets/marketers_logo/${this.marketers.find(marketer => marketer.name === offer.marketer).logo}`"
                                 alt="logo"/>
                            <p v-if="offerToEdit !== index || offer.marketer" class="text ml-10" data-size="16" data-weight="600">{{ offer.product }}</p>
                            <input v-else class="text ml-10 simple-input w-300-px-max" data-size="16" data-weight="600" v-model="offer.product" />
                        </div>
                        <div class="d-grid grid-justify-center w-300-px" data-column="3">
                            <p :class="[{'opacity-0': !viewCommissions},'text']" data-size="18">{{offer.commission ?? 0}}
                                <span :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                            </p>
                            <div class="d-flex align-center">
                                <p class="text">{{ Math.round(offer.savePercent) }}%</p>
                                <p class="text ml-5" data-size="20" data-weight="600" :data-color="offer.savePercent > 0 ? 'success' : 'rojo'">{{ Math.round(currentTotal - offer.total) }}€</p>
                            </div>
                            <div class="d-flex align-center" data-gap="10">
                                <i class="far fa-circle-info text pointer" data-size="16" @click="offer.viewPrices = !offer.viewPrices"></i>
                                <i class="far fa-file-pdf text pointer" data-size="16" @click="createPDFForm(offer)"></i>
                                <i class="far fa-user-plus text pointer" data-size="16" @click="createOpportunity(offer)"></i>
                            </div>
                        </div>
                    </div>
                    <transition
                        name="accordion"
                        v-on:before-enter="beforeEnter" v-on:enter="enter"
                        v-on:before-leave="beforeLeave" v-on:leave="leave"
                    >
                        <div v-if="offer.viewPrices" class="d-flex mt-20 hidden">
                            <div class="d-flex column form flex-1" data-gap="10">
                                <div class="d-flex form-group" data-gap="10">
                                    <p class="text ml-20 w-200-px" data-size="16">Ahorro:</p>
                                    <div class="d-flex column w-100-px-min">
                                        <p class="text text-center">Término fijo</p>
                                        <p class="text text-center" data-size="14">{{Intl.NumberFormat('es',{minimumFractionDigits: 0, maximumFractionDigits: 2}).format(this.currentSubtotal.fixed - offer.subTotal.fixed)}}€</p>
                                    </div>
                                    <div class="d-flex column w-100-px-min">
                                        <p class="text text-center">Término variable</p>
                                        <p class="text text-center" data-size="14">{{Intl.NumberFormat('es',{minimumFractionDigits: 0, maximumFractionDigits: 2}).format(this.currentSubtotal.variable - offer.subTotal.variable)}}€</p>
                                    </div>
                                </div>
                                <div class="d-flex align-center form-group" data-gap="10">
                                    <p class="text ml-20 w-215-px" data-size="16">Término fijo:</p>
                                    <div class="input-group w-100-px-max">
                                        <input placeholder="€ día" v-model.trim="offer.prices.fixed" :disabled="offerToEdit !== index"/>
                                    </div>
                                </div>
                                <div class="d-flex align-center form-group" data-gap="10">
                                    <p class="text ml-20 w-215-px" data-size="16">Término variable:</p>
                                    <div class="input-group w-100-px-max">
                                        <input placeholder="€ kWh" v-model.trim="offer.prices.variable" :disabled="offerToEdit !== index"/>
                                    </div>
                                </div>
                                <!--Fijo-Variable-->
                                <template v-if="offer.priceType === 'variable'">
                                    <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                                    <div class="d-flex align-center form-group" data-gap="10">
                                        <p class="text ml-20 w-215-px" data-size="16">Fee término fijo:</p>
                                        <div class="input-group w-100-px-max">
                                            <input placeholder="€ día" v-model.trim="offer.fees.fixed" :disabled="offerToEdit !== index"/>
                                        </div>
                                    </div>
                                    <div class="d-flex align-center form-group" data-gap="10">
                                        <p class="text ml-20 w-215-px" data-size="16">Fee término variable:</p>
                                        <div class="input-group w-100-px-max">
                                            <input placeholder="€ MWh" v-model.trim="offer.fees.variable" :disabled="offerToEdit !== index"/>
                                        </div>
                                    </div>
                                    <div class="separator w-80 mt-25 mb-0 mx-auto"></div>
                                    <div class="d-flex form-group" data-gap="10">
                                        <p class="text ml-20 w-215-px" data-size="16">Total término fijo:</p>
                                        <div class="input-group w-90-px-max">
                                            <input
                                                :value="parseStringToNumber(offer.prices.fixed) + (parseStringToNumber(offer.fees.fixed))"
                                                disabled />
                                        </div>
                                    </div>
                                    <div class="d-flex form-group" data-gap="10">
                                        <p class="text ml-20 w-215-px" data-size="16">Total término variable:</p>
                                        <div class="input-group w-90-px-max">
                                            <input
                                                :value="parseStringToNumber(offer.prices.variable) + (parseStringToNumber(offer.fees.variable) / 1000)"
                                                disabled />
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="mx-20">
                                <button v-if="offerToEdit !== index" class="custom-button" data-size="regular" @click="editOffer(index)">
                                    <i class="far fa-pen-to-square mr-5"></i>Editar
                                </button>
                                <div v-else class="d-flex column justify-center" data-gap="10">
                                    <button class="custom-button" data-size="regular" @click="updateOffer">
                                        <i class="fa-regular fa-floppy-disk mr-5"></i>Guardar
                                    </button>
                                    <button class="custom-button" data-size="regular" data-bg="rojo" @click="cancelEditOffer">
                                        <i class="fa-regular fa-xmark mr-5"></i>Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </template>
        </div>
    </div>
    <!--Pantalla de carga-->
    <div class="loader-box" v-if="isLoading">
        <div class="d-flex column align-center p-20 round" data-gap="10" data-bg="blanco">
            <div class="text" data-size="20" data-weight="600">
                {{ loadingMessages[currentMessage] }}
            </div>
            <div class="loader"></div>
        </div>
    </div>
    <!--PDF-->
    <comparator-gas-pdf class="absPos opacity-0" v-if="generatePDF" :basicData="basicData" :pdfForm="opportunityData" :optionSelected="optionSelected" :consumption="consumption" :cupsIntervalsData="cupsIntervalsData" :prices="prices" :currentTotal="currentTotal" :dates="dates" :period="period" :offer="offerSelected" :fixedPricePeriod="fixedPricePeriod" @closeForm="onPdfClosed = false; generatePDF = false" :taxes="taxes"   @loading="pdfIsLoading = $event "
    :applyTaxes="applyTaxes"
    :otherConcepts="otherConcepts"
    :meterDevicePricePeriod="meterDevicePricePeriod"/>
    <Teleport to="body">
        <div v-if="viewPDFForm" class="floating-box">
            <div class="register-pos w-auto h-auto h-98-max p-30 round" style="overflow-y: scroll; position: relative" data-round="20" data-border-color="principal" >
                <div v-if="pdfIsLoading" class="pdf-loader">
    <div class="d-flex column align-center p-20 round" data-gap="10" data-bg="blanco">
        <div class="text" data-size="20" data-weight="600">
            Generando PDF…
        </div>
        <div class="loader"></div>
    </div>
</div>
                <div class="d-flex justify-between">

                    <p data-color="principal" data-weight="600" data-size="18" class="mb-10">Generar PDF</p>
                    <i data-size="25" class="pointer fas fa-xmark" @click="closePDFForm"></i>                </div>
                <p class="text">Por favor rellena los campos para generar el PDF</p>
                <form class="d-flex column mt-20 form" @submit.prevent="generatePDF = true">
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

                    <!--Imagen de pdf-->
                    <div class="form-group">
                        <p class="my-auto"><label>Imagen empresa:</label></p>
                        <!---->
                        <div class="d-flex">

                            <!--preview img-->
                            <div class="w-200-px-max h-100-px h-100-px-max round d-flex justify-center align-center hidden" data-border-color="principal" data-round="15">
                                <!--sin seleccionar-->
                                <div class="w-150-px text-center" v-if="!opportunityData.enterpriseImg"><i class="fal fa-camera text" data-size="40"></i></div>

                                <!--seleccionada-->
                                <img v-else class="h-100-px" :src="imgTemporal" alt="Preview img empresa">
                            </div>


                            <div class="d-flex align-end ml-20">
                                <button type="button" class="custom-button mt-10" data-size="medium" data-bg="principal" data-mode="translucent" v-on:click="openInput">Adjuntar <i class="far fa-paperclip"></i></button>
                                <input
    ref="enterpriseInput"
    type="file"
    style="display: none"
    accept=".png, .jpg, .jpeg"
    @change="pickupFile"
/>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="custom-button mt-10" data-size="medium">Generar</button>
                </form>
            </div>
        </div>
        <div v-if="searchCupsDialog" class="floating-box">
            <div class="register-pos w-auto h-auto h-98-max p-30 round" data-round="20" data-border-color="principal">
                <div class="d-flex justify-between">
                    <p data-color="principal" data-weight="600" data-size="18" class="mb-10">Buscar por dirección</p>
                    <i data-size="25" class="pointer fas fa-xmark" @click="searchCupsDialog = false"></i>
                </div>
                <form class="d-flex justify-center align-end mt-20 form" data-gap="15" @submit.prevent="searchCupsByAddress">
                    <div class="form-group w-100-px">
                        <p class="my-auto"><label>Código postal</label></p>
                        <div class="input-group">
                            <input data-size="10" v-model="searchCups.zipCode" type="text">
                        </div>
                    </div>
                    <div class="form-group w-200-px">
                        <p class="my-auto"><label>Dirección</label></p>
                        <div class="input-group">
                            <input data-size="10" v-model="searchCups.address" type="text">
                        </div>
                    </div>
                    <button type="submit" class="custom-button mb-10 h-40" data-size="small">Buscar</button>
                </form>
                <div v-if="!searchCupsLoading && viewSearchCupsList">
                    <div v-if="searchCupsList.length > 0">
                        <template v-for="cupsOption of searchCupsList.slice(0,10)">
                            <div class="d-flex align-center py-5" data-gap="10">
                                <i class="far fa-eye text pointer" data-size="11"
                                   @click="cups = cupsOption.cups; searchCupsDialog = false"></i>
                                <p class="text w-180-px" data-size="11">{{ cupsOption.cups }}</p>
                                <p class="text" data-size="11">{{ cupsOption.tipoViaPS }} <span v-html="cupsOption.viaPS"/>
                                    {{ cupsOption.numFincaPS.replace(/^0+/, "") }}, {{ cupsOption.portalPS.replace(/^0+/, "") }}
                                    {{ cupsOption.pisoPS.replace(/^0+/, "") }}{{cupsOption.pisoPS && !isNaN(cupsOption.pisoPS) ? "º" : ""}} {{ cupsOption.puertaPS.replace(/^0+/, "") }}</p>
                            </div>
                            <div class="separator my-5"/>
                        </template>
                    </div>
                    <div v-else class="text p-10" data-size="16">No se encontraron resultados.</div>
                </div>
                <div v-else-if="searchCupsLoading" class="d-flex align-center justify-center mt-10" data-gap="10">
                    <i class="fa-regular fa-spinner-third fa-spin text" data-size="20"></i>
                    <div class="text" data-size="20">Buscando</div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
import {calculateCommission} from "@/utils/calcCommission";

export default {
    name: "ComparatorGas",
    props: ['basicData'],
    data(){
        return{
            marketers: [],
            optionSelected: "",
            fixedPricePeriod: "day",
            filesSelected: null,
            opportunityData: {
                name: "",
                CIF: "",
                currentMarketer: "",
                agent: "",
                agentName: "",
                agentPhone: "",
                billingInfo: {
                    community: "",
                    province: "",
                    locality: "",
                    address: "",
                    postal: ""
                },
                order: {
                    productType: 'cg',
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
            supply: {},
            cups: "",
            fee: "",
            currentMarketer: "",
            cupsData: {},
            cupsIntervalsData: [],
            consumption: null,
            prices: {
                fixed: null,
                variable: null,
            },
            currentTotal: 0,
            billTotalPrice: 0,
            manualTotal: false,
            currentSubtotal: {fixed: 0, variable: 0},
            applyTaxes: false,
            taxes: {
                iva: 21,
                hidroTax: '0,00234',
                meterDevice: 0,
            },
            meterDevicePricePeriod: 'day',
            otherConcept: {},
            otherConcepts: [],
            dates: {
                start: null,
                end: null,
            },
            totalDays: 30,
            period: "month",
            cupsInterval: 0,
            searchCupsDialog: false,
            searchCupsLoading: false,
            searchCups: {},
            searchCupsList: [],
            offersList: [],
            editingInputs: false,
            inputsDefault: {},
            offerToEdit: null,
            offerDefault: {},
            offerSelected: {},
            isLoading: false,
            viewInputs: true,
            viewFilters: false,
            viewOffers: false,
            viewCommissions: false,
            viewPDFForm: false,
            pdfIsLoading: false,
            viewSearchCupsList: false,
            zocoCommissionRanges: null,
            filters: {
                radio: {
                    sortBy: {
                        title: "Ordenar por",
                        checked: 7,
                    }
                },
                residencial: true,
                pyme: true,
                fixed: true,
                indexed: false,
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
            inputData: {},
            generatePDF: false,
        }
    },
    created() {
        this.fetchMarketers();
        window.addEventListener('resize', this.updateWidth)
    },
    mounted() {
        // Usar nextTick para asegurar que el DOM y los datos estén completamente listos
        this.$nextTick(() => {
            if (this.basicData?.userLogged) {
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
            }
        });
    },
    methods:{
         onPdfClosed() {
            this.generatePDF = false
            this.viewPDFForm = false
            this.pdfIsLoading = false
        },
        openInput() {
            this.$refs.enterpriseInput.click();
        },
        closePDFForm() {
            this.viewPDFForm = false;
            this.generatePDF = false;

            if (this.$refs.enterpriseInput) {
                this.$refs.enterpriseInput.value = "";
            }

            this.imgTemporal = null;
            this.opportunityData.enterpriseImg = null;
        },
        pickupFile(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Guardamos el File
            this.opportunityData.enterpriseImg = file;

            // Creamos preview seguro
            this.imgTemporal = URL.createObjectURL(file);
        },
        async fetchMarketers() {
            await axios.get('/api/marketers')
                .then((res) => {
                    this.marketers = res.data.marketers;

                    if (this.marketers?.[0]?.createdBy !== '65cb57489c2c285441086a43') {
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
                    const file = this.$refs.bill.files[0];
                    if (file) {
                        await this.getOCRData();
                        await this.getCupsData();

                        //En caso de no tener el intervalo en el SIPS, lo añado
                        if (!this.cupsIntervalsData.some(item =>
                            moment(item.fechaInicioMesConsumo, "DD/MM/YYYY").isSame(this.dates.start, 'day') &&
                            moment(item.fechaFinMesConsumo, "DD/MM/YYYY").isSame(this.dates.end, 'day')
                        )) {
                            this.cupsIntervalsData.unshift(
                                {
                                    fechaInicioMesConsumo: this.dates.start.format('YYYY-MM-DD'),
                                    fechaFinMesConsumo: this.dates.end.format('YYYY-MM-DD'),
                                    consumoEnWhP1: this.consumption,
                                    consumption: 0
                                }
                            )
                        }

                        //Si no ha obtenido datos del sips, le asigno los valores de consumo de la factura
                        if(this.cupsData.consumption === undefined){
                            this.cupsData.consumption = this.cupsIntervalsData[this.cupsInterval].consumoEnWhP1 / this.totalDays * 365;
                        }

                        this.calc();


                        //Compruebo si ha salido correcta la comparativa, para así contarla como valida para los registros
                        if (this.$utilities.validateComparison(this.billTotalPrice, this.currentTotal)) {
                            axios.post('/api/comparatives/countValidBillComparative', {'userSubdomain' : this.basicData.userSubdomain._id})
                                .then((res) => {
                                })
                                .catch((err) => {
                                    console.log(err)
                                })
                        }
                    } else {
                        error = "Por favor selecciona un archivo válido."
                        this.generateLog('error', 'Por favor selecciona un archivo válido.',this.optionSelected, 'handleSubmit (isset file)')
                    }
                } else if (this.optionSelected === "cups") {
                    //Compruebo que el CUPS tiene el formato correcto
                    let cupsRegex = /^ES\d{16}[a-z]{2}(?:[0-9][a-z])?$/i;
                    if (cupsRegex.test(this.cups)) {
                        //Compruebo que hay precios introducidos
                        if (this.prices.fixed && this.prices.variable) {
                            await this.getCupsData();
                            this.consumption = this.cupsIntervalsData[0]['consumoEnWhP1'] + this.cupsIntervalsData[0]['consumoEnWhP2'];
                            this.dates.start = moment(this.cupsIntervalsData[0]['fechaInicioMesConsumo'], "YYYY-MM-DD");
                            this.dates.end = moment(this.cupsIntervalsData[0]['fechaFinMesConsumo'], "YYYY-MM-DD");
                            this.totalDays = this.dates.end.diff(this.dates.start, "days");

                            //Saco el input manual
                            this.inputData['manual'] = {
                                'prices': this.prices,
                                'surplus': this.surplus ?? [],
                                'cups': this.cups
                            }

                            this.calc();
                        } else {
                            error = "Por favor rellena los precios de potencia y energía."
                        }
                    } else {
                        error = "Por favor introduce un CUPS válido."
                    }
                } else if (this.optionSelected === "manual") {
                    //Compruebo que hay datos de consumo y precios introducidos y que tiene tarifa
                    if (this.consumption && this.prices.fixed && this.prices.variable) {
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

                Swal.fire({
                    icon: "error",
                    title: errorMessage
                })

            } finally {
                this.isLoading = false;
                clearInterval(this.intervalId)
            }
        },
        calc() {
            //Calculo el total a pagar actualmente
            //Ajusto los precios según el periodo introducido
            let fixedPrice = this.parseStringToNumber(this.prices.fixed);
            switch (this.fixedPricePeriod) {
                case "month":
                    fixedPrice = fixedPrice / 30
                    break;
                case "year":
                    fixedPrice = fixedPrice / 365
                    break;
            }
            this.currentSubtotal = this.calcTotal({ fixed: fixedPrice, variable: this.prices.variable }, true);

            if(!this.manualTotal){
                this.currentTotal = Object.values(this.currentSubtotal).reduce((acc, value) => acc + value, 0);
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
                offer.subTotal = this.calcTotal({ ...offer.prices, fees: offer.fees });
                offer.total = Object.values(offer.subTotal).reduce((acc, value) => acc + value, 0);
                offer.savePercent = this.currentTotal === 0 ? -1000 : (this.currentTotal - offer.total) / this.currentTotal * 100;
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
                'consumption': this.consumptionData?.consumption ? Math.round(this.consumptionData.consumption.reduce((acc, value) => acc + this.parseStringToNumber(value), 0)).toLocaleString("es-ES") : 0,
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
        calcTotal(prices, isCurrent) {
            let fixedFee = prices.fees ? this.parseStringToNumber(prices.fees.fixed) : 0;
            let totalFixed = (this.parseStringToNumber(prices.fixed) + fixedFee) * this.totalDays;
            let consumption = this.parseStringToNumber(this.consumption);
            let variableFee = prices.fees ? this.parseStringToNumber(prices.fees.variable) / 1000 : 0;
            let totalVariable = (this.parseStringToNumber(prices.variable) + variableFee) * consumption;
            //console.log(prices.fees)

            //Calculo los conceptos extra
            let otherConcepts = 0;
            if(this.otherConcepts.length > 0 && isCurrent){
                otherConcepts = this.otherConcepts.reduce((acc, concept) => acc + this.parseStringToNumber(concept.value), 0);
            }

            //Calculo impuestos si está activada la opcion
            let meterDevice = 0;
            let hidroTax = 0;
            let iva = 0;
            if(this.applyTaxes){
                let meterDevicePrice = this.taxes.meterDevice;
                switch (this.meterDevicePricePeriod){
                    case "month":
                        meterDevicePrice = this.parseStringToNumber(meterDevicePrice) / 30;
                        break;
                    case "year":
                        meterDevicePrice = this.parseStringToNumber(meterDevicePrice) / 365;
                        break;
                }
                meterDevice = Number((this.parseStringToNumber(meterDevicePrice) * this.totalDays).toFixed(2));

                hidroTax = Number((this.parseStringToNumber(this.taxes.hidroTax) * consumption).toFixed(2));

                iva = Number((this.parseStringToNumber(this.taxes.iva) * (totalFixed + totalVariable + otherConcepts + meterDevice + hidroTax) / 100).toFixed(2));
            }


            return {fixed: totalFixed, variable: totalVariable, meterDevice, hidroTax, iva, otherConcepts};
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
                    fixed: 0,
                    variable: 0
                },
                subTotal: {fixed: 0, variable: 0},
                total: 0,
                savePercent: 100,
                commission: 0,
                viewPrices: true
            };

            this.offersList.push(newOffer);
        },
        createOffers() {
            //Recorro todas las comercializadoras
            this.marketers.forEach(marketer => {
                //Obtengo la tarifa a comparar
                let feeMarketer = marketer.fees.gas.find(fee => fee.name.includes(this.fee))

                //Recorro todos los productos
                marketer.products.gas.forEach(product => {
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
                                fixed: feeProduct.prices.fixed,
                                variable: feeProduct.prices.variable,
                            },
                            subTotal: {fixed: 0, variable: 0},
                            total: 0,
                            savePercent: 0,
                            commission: 0,
                            feeInfo: feeProduct,
                            viewPrices: false,
                            residencial: feeProduct.type.residencial,
                            pyme: feeProduct.type.pyme,
                            priceType: feeProduct.priceType,
                            // indexed: !!feeProduct.indexed
                        };

                        //Añado los fees si es producto variable
                        if (offer.priceType === "variable") {
                            offer.fees = {
                                fixed: null,
                                variable: null
                            }
                        }

                        // if(feeProduct.indexed){
                        //     offer.indexFees = {
                        //         power: {},
                        //         energy: {}
                        //     }
                        // }


                        //Si tiene datos de precios, lo añado como posible oferta
                        if (parseFloat(offer.prices.fixed) && parseFloat(offer.prices.variable)) {
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
                    annual: this.totalConsumption,
                    byPeriods: null
                },

                powerData: {
                    max: null,
                    byPeriods: null
                },

                fees,
                manualCommissions: this.basicData?.userSubdomain?.settings?.manualCommissions
            });

            const user = this.basicData.userLogged;

            return result.breakdown.find(u => u.userId === user._id)?.commission
                ?? (user.label === 'Usuario subdominio' ? result.subdomain : 0);
        },
        togglePeriod() {
            //Cancelo edición en caso de estar haciéndose
            this.cancelEditOffer();

            //No cambio de mes a año si se están editando los inputs
            if (this.editingInputs) return;

            this.period = this.period === "month" ? "year" : "month";
            switch (this.optionSelected) {
                case "bill":
                    if (this.period === "month") {
                        this.consumption = (this.parseStringToNumber(this.cupsIntervalsData[0].consumoEnWhP1) + this.parseStringToNumber(this.cupsIntervalsData[0].consumoEnWhP2));
                        this.dates.start = moment(this.cupsIntervalsData[0]['fechaInicioMesConsumo'].replace(/[.-]/g, "/"), "YYYY-MM-DD");
                        this.dates.end = moment(this.cupsIntervalsData[0]['fechaFinMesConsumo'].replace(/[.-]/g, "/"), "YYYY-MM-DD");
                        this.totalDays = this.dates.end.diff(this.dates.start, "days");
                        this.cupsInterval = 0;
                    } else {
                        this.consumption = this.cupsData.consumption[0];
                        this.dates.start = moment(this.cupsIntervalsData[0]['fechaFinMesConsumo'].replace(/[.-]/g, "/"), "YYYY-MM-DD").subtract(1, "years").add(1, 'day');
                        this.dates.end = moment(this.cupsIntervalsData[0]['fechaFinMesConsumo'].replace(/[.-]/g, "/"), "YYYY-MM-DD");
                        this.totalDays = this.dates.end.diff(this.dates.start, "days");
                    }
                    break;
                case "cups":
                    //Si cambio de año a mes, marco el último mes del SIPS como mes seleccionado
                    if (this.period === "month") {
                        this.consumption = (this.parseStringToNumber(this.cupsIntervalsData[0].consumoEnWhP1) + this.parseStringToNumber(this.cupsIntervalsData[0].consumoEnWhP2));
                        this.dates.start = moment(this.cupsIntervalsData[0]['fechaInicioMesConsumo'].replace(/[.-]/g, "/"), "YYYY-MM-DD");
                        this.dates.end = moment(this.cupsIntervalsData[0]['fechaFinMesConsumo'].replace(/[.-]/g, "/"), "YYYY-MM-DD");
                        this.totalDays = this.dates.end.diff(this.dates.start, "days");
                        this.cupsInterval = 0;
                        //Si cambio de mes a año, muestro los datos del SIPS y pongo de fechas 1 año natural
                    } else {
                        this.consumption = this.cupsData.consumption[0];
                        this.dates.start = moment(this.cupsIntervalsData[0]['fechaFinMesConsumo'].replace(/[.-]/g, "/"), "YYYY-MM-DD").subtract(1, "years").add(1, 'day');
                        this.dates.end = moment(this.cupsIntervalsData[0]['fechaFinMesConsumo'].replace(/[.-]/g, "/"), "YYYY-MM-DD");
                        this.totalDays = this.dates.end.diff(this.dates.start, "days") + 1; //Añado uno para incluir el día de inicio
                        this.cupsInterval = "";
                    }
                    break;
                case "manual":
                    this.totalDays = this.period === "month" ? 30 : 365;
                    break;
            }

            //Recalculo los precios
            this.calc();
        },
        editInputs(){
            //Activo la edición y copio los valores a editar
            this.editingInputs = true;
            this.inputsDefault = {
                cups: this.cups,
                fee: this.fee,
                currentTotal: this.currentTotal,
                currentSubtotal: JSON.parse(JSON.stringify(this.currentSubtotal)),
                dates: { ...this.dates },
                totalDays: this.totalDays,
                consumption: this.consumption,
                prices: JSON.parse(JSON.stringify(this.prices)),
                cupsInterval: this.cupsInterval,
                fixedPricePeriod: this.fixedPricePeriod,
            }
        },
        cancelEditInputs(){
            if (this.editingInputs) {
                //Restaurar valores iniciales a los guardados en inputsDefault
                this.cups = this.inputsDefault.cups;
                this.fee = this.inputsDefault.fee;
                this.currentTotal = this.inputsDefault.currentTotal;
                this.currentSubtotal = JSON.parse(JSON.stringify(this.inputsDefault.currentSubtotal));
                this.dates = { ...this.inputsDefault.dates };
                this.totalDays = this.inputsDefault.totalDays;
                this.consumption = this.inputsDefault.consumption;
                this.prices = JSON.parse(JSON.stringify(this.inputsDefault.prices));
                this.cupsInterval = this.inputsDefault.cupsInterval;
                this.fixedPricePeriod = this.inputsDefault.fixedPricePeriod;

                this.editingInputs = false;
            }
        },
        updateInputs(){
            //Si la tarifa ha cambiado, obtener los productos de la nueva tarifa, y borrar los actuales excepto los añadidos manualmente
            if (this.fee !== this.inputsDefault.fee) {
                this.offersList = this.offersList.filter(offer => offer.marketer === null);
                this.createOffers();
            }

            //Compruebo si el total actual ha sido editado a mano
            this.manualTotal = this.manualTotal || this.currentTotal !== this.inputsDefault.currentTotal;

            //Sí cambiamos a un intervalo, cambiar el periodo a mes y actualizar fechas
            if (this.optionSelected !== "manual" && this.cupsInterval !== this.inputsDefault.cupsInterval) {
                this.period = "month";
                this.dates = { start: moment(this.cupsIntervalsData[this.cupsInterval].fechaInicioMesConsumo, "YYYY-MM-DD"), end: moment(this.cupsIntervalsData[this.cupsInterval].fechaFinMesConsumo, "YYYY-MM-DD") };
                this.totalDays = this.dates.end.diff(this.dates.start, "days");
            }

            //Restablecemos las variables de la edición
            this.editingInputs = false;
            this.inputsDefault = {};

            //Recalculamos los precios y sus comisiones
            this.calc();
        },
        editOffer(index) {
            this.cancelEditOffer(); //Cancelo cualquier otra oferta que se esté editando
            this.offerToEdit = index; //Asigno el índice de la oferta que estoy editando
            this.offerDefault = JSON.parse(JSON.stringify(this.filteredOffers[index])) //Hago una copia de los datos para restaurar
            delete this.offerDefault.viewPrices; //Borro el boolean de mostrar precios, pues este es independiente de la edición
            this.offerEditingName = this.offerDefault.product;
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
        updateOffer() {
            let offer = this.filteredOffers[this.offerToEdit]
            offer.viewPrices = false; //Cierro la visualización de los precios

            //Reseteo las variables para controlar la edición
            this.offerToEdit = null;
            this.offerDefault = {};

            //Recalculo la oferta
            offer.subTotal = this.calcTotal({ ...offer.prices, fees: offer.fees });
            offer.total = Object.values(offer.subTotal).reduce((acc, value) => acc + value, 0);
            offer.savePercent = this.currentTotal === 0 ? -1000 : (this.currentTotal - offer.total) / this.currentTotal * 100;
            if(offer.marketer){
                offer.commission = this.calcCommission(offer.marketerId, offer.feeInfo.commissions, offer.feeInfo.commissionType, offer.fees, offer.isZocoMarketer)
            }
        },
        selectNewOrderType(orderType) {
            switch (orderType) {
                case 'offer':

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
        resetComparator() {
            this.optionSelected = "";
            this.filesSelected = null;
            this.cups = "";
            this.fee = "";
            this.currentMarketer = "";
            this.cupsData = {};
            this.cupsIntervalsData = [];
            this.consumption = null;
            this.prices = {
                fixed: null,
                variable: null
            };
            this.currentTotal = 0;
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
            this.filters.radio.sortBy.checked = 7;
            this.currentMessage = null;
            this.otherConcept = {};
            this.otherConcepts = [];
            this.opportunityData = {
                name: "",
                CIF: "",
                billingInfo: {
                    community: "",
                    province: "",
                    locality: "",
                    address: "",
                    postal: ""
                },
                order: {
                    productType: 'cg',
                    marketer: '',
                    fee: '',
                    product: '',
                    CUPS: "",
                    direc: "",
                    zip: "",
                    town: "",
                    province: "",
                },
            };
        },
        async getOCRData() {
            //Mando los archivos subidos en $refs.bill.files
            //En el servidor, hago la logica para llamar a la API y recibir el texto
            const formData = new FormData();
            Array.from(this.$refs.bill.files).forEach(file => {
                formData.append("files[]", file);
            });
            formData.append("userSubdomain", this.basicData.userSubdomain._id);
            const response = await axios.post('/api/tools/getGasOCRData', formData)
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
            const regex = /```json([\s\S]*?)```/;
            const data = JSON.parse(response.data.match(regex)[1]);

            //Datos del PDF
            this.inputData['pdf'] = data;

            //Asigno valores para calcular
            this.currentMarketer = data.comercializadora;
            this.cups = data.cups.replace(/ /g, "");
            this.prices.fixed = data.termino_fijo;
            this.prices.variable = data.precio_consumo;
            this.consumption = data.consumo;
            this.dates.start = moment(data.periodo_facturacion.fecha_inicio, "DD/MM/YYYY");
            this.dates.end = moment(data.periodo_facturacion.fecha_fin, "DD/MM/YYYY");
            this.taxes.meterDevice = data.otros.alquiler_equipo_medida;
            this.billTotalPrice = data.total

            //Compruebo si las fechas de facturación concuerdan con como las manejo, si no la cambio un dia antes.
            if(this.dates.end.diff(this.dates.start, "days") !== data.dias_facturacion) {
                this.dates.start = this.dates.start.subtract(1, "day");
            }
            this.totalDays = this.dates.end.diff(this.dates.start, "days") ;

            //Activo los impuestos
            this.applyTaxes = true;

            //Asigno valores de oportunidad
            this.opportunityData = {
                name: data.titular,
                CIF: data.cif_nif,
                billingInfo: {
                    community: data.direccion_titular.comunidad_autonoma,
                    province: data.direccion_titular.provincia,
                    locality: data.direccion_titular.poblacion,
                    address: data.direccion_titular.direccion,
                    postal: data.direccion_titular.codigo_postal
                },
                order: {
                    productType: 'cg',
                    direc: data.direccion_suministro.direccion,
                    zip: data.direccion_suministro.codigo_postal,
                    town: data.direccion_suministro.poblacion,
                    province: data.direccion_suministro.provincia,
                },
            }
        },
        async getCupsData() {
            // Remover punto de frontera en caso de tenerlo
            let cups = /^ES\d{16}[a-z]{2}[0-9][a-z]$/i.test(this.cups) ? this.cups.slice(0, -2) : this.cups;

            try {
                const response = await axios.get('/api/sips/getGasConsumption', {
                    params: { CUPS: cups }
                });

                this.cupsData.consumption = response.data.consumptionData.consumption;
                this.cupsIntervalsData = response.data.consumptionData.consumptionIntervals;
                this.supply = response.data.supply;

                //Añado RL al numero de la tarifa
                this.fee = "RL" + response.data.fee.slice(-1);

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
        async searchCupsByAddress(){
            //Usando searchCups, usar el metodo en el sips
            try {
                this.searchCupsLoading = true;
                const response = await axios.get('/api/sips/getGasCupsByAddress', {params: { zipCode: this.searchCups.zipCode, address: this.searchCups.address}})

                this.searchCupsList = response.data;
            } catch (error) {
                console.error("Error al obtener los datos del CUPS", error)
            }finally {
                this.searchCupsLoading = false;
                this.viewSearchCupsList = true;
            }
        },
        addOtherConcept(){
            this.otherConcepts.push(this.otherConcept);
            this.otherConcept = {};
        },
        removeOtherConcept(index){
            this.otherConcepts.splice(index,1);
        },
        createPDFForm(offer) {
            this.offerSelected = offer;

            this.opportunityData.order.marketer = this.offerSelected.marketer;
            this.opportunityData.order.CUPS = this.cups;
            this.opportunityData.order.product = this.offerSelected.product;

            // Pre-llenar datos del agente cuando se abre el formulario
            if (this.basicData?.userLogged) {
                const user = this.basicData.userLogged;

                console.log('User data:', user); // Debug

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
                        CUPS: this.cups.substring(0, 20),
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
        getPrettyDate(date) {
            let dateNow = new Date(date);
            let day = String(dateNow.getDate()).padStart(2, '0'); // Asegura que el día tenga dos dígitos
            let month = String(dateNow.getMonth() + 1).padStart(2, '0'); // Asegura que el mes tenga dos dígitos y se suma 1 porque en JavaScript los meses van de 0 a 11
            let year = dateNow.getFullYear();
            return `${day}/${month}/${year}`;
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
        getFilesSelected(event) {
            this.filesSelected = event.target.files.length === 1 ? event.target.files[0].name : `${event.target.files.length} archivos seleccionados`
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

                console.log(this.input)
            }


            //$status, $messageError = null, $type, $codePart, $input, $output, $user

            axios.post('/api/logs/generateComparative', {
                'status': status,
                'messageError': message,
                'inputType': type,
                'comparativeType': 'gas',
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
    computed:{
        sortedMarketers() {
            //Obtengo las comercializadoras que puede ver el usuario
            let marketersVisible = [...this.basicData.userLogged.marketers, ...this.basicData.userSubdomain.marketers];

            //Filtro por marketers visibles o creados, porque el usuario subdominio tiene los marketers de zoco en sus datos en lugar de los propios
            return this.marketers.filter(marketer => {
                const isVisible =
                    (marketersVisible.includes(marketer._id) ||
                    this.basicData.userLogged._id === marketer.createdBy) &&
                    !marketer.archived;

                const hasGas =
                    Array.isArray(marketer.products?.gas) &&
                    marketer.products.gas.length > 0;

                return isVisible && hasGas;
            })
            .toSorted((a, b) => {
                return a.name.localeCompare(b.name, "es", {sensitivity: 'base'})
            });
        },
        filteredOffers() {
            let sortedOffers = [...this.offersList];

            switch (this.filters.radio.sortBy.checked) {
                case 0:
                    //Producto (A-Z)
                    sortedOffers = sortedOffers.sort((a, b) => {
                        const marketerA = a.marketer ?? " ";
                        const marketerB = b.marketer ?? " ";

                        let nameA = marketerA + a.product;
                        let nameB = marketerB + b.product;
                        return nameA.localeCompare(nameB, "es", { sensitivity: 'base' })
                    })
                    break;
                case 1:
                    //Producto (Z-A)
                    sortedOffers = sortedOffers.sort((a, b) => {
                        const marketerA = a.marketer ?? " ";
                        const marketerB = b.marketer ?? " ";

                        let nameA = marketerA + a.product;
                        let nameB = marketerB + b.product;
                        return nameB.localeCompare(nameA, "es", { sensitivity: 'base' })
                    })
                    break;
                case 2:
                    //Eficiencia ascendente
                    sortedOffers = sortedOffers.sort((a, b) => {
                        return (b.total / b.commission) - (a.total / a.commission);
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

            //Filtro por comercializadora, residencial, pyme, fijo e indexado
            //En caso de ser oferta creada manualmente siempre aparece
            return sortedOffers.filter((offer) => ((this.filters.marketers.includes(offer.marketer)) &&
                ((this.filters.residencial && offer.residencial) || (this.filters.pyme && offer.pyme)) &&
                ((this.filters.fixed && offer.priceType === 'fixed') || (this.filters.variable && offer.priceType === 'variable') || (this.filters.indexed && offer.priceType === 'indexed')))
                || offer.marketer === null
            )
        },
        totalConsumption() {
            if (this.optionSelected !== "manual") {
                //Obtengo la fecha de comienzo del año a calcular
                const minDate = moment(this.cupsIntervalsData[0].fechaFinMesConsumo, "YYYY-MM-DD").subtract(1, "years") + 1;

                //Devuelvo la suma de los consumos del mes, excepto en el último en el cual obtengo el consumo proporcional a las fechas
                return this.cupsIntervalsData.reduce((acc, interval, index, array) => {
                    if (index === array.length - 1) {
                        //Obtengo la duración de la lectura para sacar la media diaria de consumo, y el número de días válidos de esta lectura
                        const intervalPeriod = moment(interval.fechaFinMesConsumo, "YYYY-MM-DD").diff(moment(interval.fechaInicioMesConsumo, "YYYY-MM-DD"), "days");
                        const daysInYear = moment(interval.fechaFinMesConsumo, "YYYY-MM-DD").diff(minDate, "days");

                        return acc + (this.parseStringToNumber(interval.consumoEnWhP1) + this.parseStringToNumber(interval.consumoEnWhP2)) / intervalPeriod * daysInYear;
                    } else {
                        return acc + (this.parseStringToNumber(interval.consumoEnWhP1) + this.parseStringToNumber(interval.consumoEnWhP2));
                    }
                }, 0);
            } else {
                //En caso de manual, hago el cálculo en relación con el número de días seleccionado
                return this.parseStringToNumber(this.consumption) / this.totalDays * 365;
            }
        },
        isDesktopView() {
            return this.windowWidth > 810
        }
    }
}
</script>

<style scoped>

.pdf-loader {
    position: absolute;
    inset: 0;               /* top:0 right:0 bottom:0 left:0 */
    background: rgba(255, 255, 255, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

</style>

