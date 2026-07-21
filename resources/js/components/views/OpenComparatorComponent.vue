<template>
    <transition name="slide-up" mode="out-in">
    <div class="min-h-100-vh w-100-max d-flex justify-center align-center py-50" data-bg="blanco" :key="currentView">
        <!-- ===== LANDING ===== -->
        <div v-if="currentView === 'landing'" class="d-flex column align-center w-700-px w-90-max" data-gap="20">

            <!-- Hero principal -->
            <div class="landing-hero w-100 d-flex column align-center text-center">

                <!-- Badge -->
                <div class="landing-badge">
                    <i class="far fa-shield-check mr-6"></i> 100% gratuito · Sin compromiso
                </div>

                <!-- Título -->
                <h1 class="landing-title">
                    Ahorra en tu factura de <span class="landing-highlight">luz y gas</span><br>
                </h1>

                <p class="landing-subtitle">
                    Compara en segundos las mejores tarifas de luz y gas del mercado.<br>
                    Miles de hogares ya ahorran con nosotros.
                </p>

                <!-- Stats -->
                <div class="landing-stats">
                    <div class="landing-stat">
                        <span class="landing-stat-number">+5.000</span>
                        <span class="landing-stat-label">clientes satisfechos</span>
                    </div>
                    <div class="landing-stat-divider"></div>
                    <div class="landing-stat">
                        <span class="landing-stat-number">180€</span>
                        <span class="landing-stat-label">ahorro medio anual</span>
                    </div>
                    <div class="landing-stat-divider"></div>
                    <div class="landing-stat">
                        <span class="landing-stat-number">1 min</span>
                        <span class="landing-stat-label">para comparar</span>
                    </div>
                </div>

                <!-- Botón CTA -->
                <button class="landing-cta-btn" @click="currentView = 'form'">
                    <i class="far fa-bolt mr-10"></i>
                    Comparar tarifas ahora
                    <i class="far fa-arrow-right ml-10"></i>
                </button>

                <!-- Píldoras de confianza -->
                <div class="landing-pills">
                    <span class="landing-pill"><i class="far fa-check mr-5"></i>Sin cambiar CUPS</span>
                    <span class="landing-pill"><i class="far fa-check mr-5"></i>Las mejores ofertas</span>
                    <span class="landing-pill"><i class="far fa-check mr-5"></i>Asesoramiento gratuito</span>
                </div>
            </div>

            <!-- Otras gestiones -->
            <div class="w-100">
                <div class="landing-services-header">
                    <div class="landing-services-line"></div>
                    <span class="landing-services-label">Otros servicios</span>
                    <div class="landing-services-line"></div>
                </div>
                <div class="otras-acciones-grid">
                    <a href="/autoconsumo" class="otras-acciones-card otras-acciones-card--colored" style="--card-color:#F59E0B; --card-bg:#FFFBEB;">
                        <div class="otras-acciones-icon" style="background:#FEF3C7; color:#D97706;">
                            <i class="far fa-solar-panel"></i>
                        </div>
                        <p class="text" data-size="15" data-weight="600">Autoconsumo solar</p>
                        <p class="text opacity-6" data-size="13">Presupuesto gratuito para placas solares</p>
                    </a>
                    <div
                        v-if="!isFidelitySubdomain"
                        class="otras-acciones-card otras-acciones-card--colored otras-acciones-card--disabled"
                        style="--card-color:#10B981; --card-bg:#ECFDF5;"
                    >
                        <div class="otras-acciones-icon" style="background:#D1FAE5; color:#059669;">
                            <i class="far fa-charging-station"></i>
                        </div>
                        <p class="text" data-size="15" data-weight="600">Cargador eléctrico</p>
                        <p class="text opacity-6" data-size="13">Punto de carga en casa o negocio</p>
                    </div>
                    <a href="/comparatortelefonia" class="otras-acciones-card otras-acciones-card--colored" style="--card-color:#8B5CF6; --card-bg:#F5F3FF;">
                        <div class="otras-acciones-icon" style="background:#EDE9FE; color:#7C3AED;">
                            <i class="far fa-mobile-screen-button"></i>
                        </div>
                        <p class="text" data-size="15" data-weight="600">Comparador telefonía</p>
                        <p class="text opacity-6" data-size="13">Mejor tarifa de móvil y fibra</p>
                    </a>
                    <a href="/comparatoralarma" class="otras-acciones-card otras-acciones-card--colored" style="--card-color:#EF4444; --card-bg:#FEF2F2;">
                        <div class="otras-acciones-icon" style="background:#FEE2E2; color:#DC2626;">
                            <i class="far fa-shield-check"></i>
                        </div>
                        <p class="text" data-size="15" data-weight="600">Comparador alarmas</p>
                        <p class="text opacity-6" data-size="13">Seguridad para tu hogar o negocio</p>
                    </a>
                </div>
                <a
                    v-if="!isFidelitySubdomain"
                    href="/reclamaciones"
                    class="otras-acciones-card otras-acciones-card--full mt-15"
                >
                    <div class="otras-acciones-icon" style="background:#F1F5F9; color:#475569;">
                        <i class="far fa-file-circle-exclamation"></i>
                    </div>
                    <div>
                        <p class="text" data-size="15" data-weight="600">Reclamaciones</p>
                        <p class="text opacity-6" data-size="13">Gestiona una reclamación con tu compañía actual</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- ===== FORMULARIO ===== -->
        <div v-else-if="currentView === 'form'" class="dashboard-card w-700-px w-90-max">
            <transition name="step" mode="out-in">
                <div :key="currentStep" class="d-flex column align-center w-100">
                    <!--Pregunta sobre luz o gas-->
                    <template v-if="currentStep === 'energyType'">
                        <p class="text" data-size="20" data-weight="600">
                            ¿Qué suministro desea comparar?
                        </p>
                        <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                            <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('electricity')">
                                <i class="far fa-lightbulb mr-10" data-size="16" data-weight="400"></i>
                                <p data-size="16" data-weight="400">Luz</p>
                            </div>
                            <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('gas')">
                                <i class="far fa-fire-flame-simple mr-10" data-size="16" data-weight="400"></i>
                                <p data-size="16" data-weight="400">Gas</p>
                            </div>
                        </div>
                    </template>
                    <!--Pregunta sobre residencial o pyme-->
                    <template v-if="currentStep === 'clientType'">
                        <p class="text" data-size="20" data-weight="600">
                            ¿Para qué tipo de inmueble es el suministro?
                        </p>
                        <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                            <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('residential')">
                                <i class="far fa-house mr-10" data-size="16" data-weight="400"></i>
                                <p data-size="16" data-weight="400">Vivienda</p>
                            </div>
                            <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="nextStep('pyme')">
                                <i class="far fa-building mr-10" data-size="16" data-weight="400"></i>
                                <p data-size="16" data-weight="400">Negocio</p>
                            </div>
                        </div>
                    </template>
                    <!--Pregunta sobre factura o datos-->
                    <template v-if="currentStep === 'invoiceOrData'">
                        <p class="text" data-size="20" data-weight="600">
                            ¿Dispone de una factura del suministro?
                        </p>
                        <div class="d-flex w-100 justify-center mt-20 wrap-mobile" data-gap="20">
                            <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="inputData.invoiceOrData = 'invoice'">
                                <i class="far fa-file-invoice mr-10" data-size="16" data-weight="400"></i>
                                <p data-size="16" data-weight="400">Sí, subir factura</p>
                            </div>
                            <div class="custom-button d-flex align-center" data-size="regular" data-style="twoBlue" @click="inputData.invoiceOrData = 'data'">
                                <i class="far fa-pen-line mr-10" data-size="16" data-weight="400"></i>
                                <p data-size="16" data-weight="400">No, continuar sin factura</p>
                            </div>
                        </div>
                    </template>
                    <!--Añadir factura-->
                    <transition name="expand">
                        <div v-if="inputData.invoiceOrData === 'invoice'" class="mt-20 w-100 expand-wrapper form">
                            <div class="separator" />

                            <!-- Botón adjuntar -->
                            <div class="d-flex justify-center align-center w-100" data-gap="20">
                                <button type="button"
                                        class="custom-button"
                                        data-size="medium"
                                        data-bg="principal"
                                        data-mode="translucent"
                                        @click="$refs.invoice.click()">
                                    Adjuntar <i class="far fa-paperclip"></i>
                                </button>

                                <div v-if="invoice.filesSelected" class="text ellipsis text-center w-50-max w-auto">
                                    <i class="far fa-file mr-5" />{{ invoice.filesSelected }}
                                </div>
                            </div>

                            <input type="file" ref="invoice" multiple @change="getFilesSelected" class="d-none">

                            <!-- Preview (queda igual) -->
                            <transition name="preview">
                                <div v-if="invoice.previewUrl" class="mt-20 px-15 w-100 preview-wrapper">
                                    <img v-if="invoice.fileType === 'image'" :src="invoice.previewUrl" class="preview-content" />
                                    <embed v-else-if="invoice.fileType === 'pdf'" :src="invoice.previewUrl" class="preview-content" />
                                </div>
                            </transition>

                            <template v-if="invoice.fileType">
                                <!--Comercializadora actual-->
                                <div class="form-group w-100 no-margin mt-20">
                                    <p class="text opacity-6">Comercializadora actual</p>
                                    <div class="input-group">
                                        <select v-model="currentMarketer">
                                            <option value="" disabled>Selecciona una</option>
                                            <option v-for="marketer of marketers.toSorted((a,b) => a.name.localeCompare(b.name))">{{marketer.name}}</option>
                                            <option value="other(This never matches)">Otra comercializadora</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="custom-button mt-20 text-center w-100" data-size="regular" @click="generateComparative">
                                    Continuar
                                </div>
                            </template>

                        </div>
                    </transition>



                    <template v-if="inputData.invoiceOrData === 'data'">
                        <div class="separator" />

                        <div class="d-flex column w-100 form mt-20" data-gap="20" style="padding: 20px;">
                            <p class="text text-center" data-size="18" data-weight="600">
                                Introduzca los datos manualmente
                            </p>

                           <div class="d-flex w-100 column-mobile" data-gap="20">
                                <div class="form-group w-100 no-margin">
                                    <label>Pago actual (€)</label>
                                    <div class="input-group">
                                        <input
                                            v-model="currentTotal"
                                            type="number"
                                            step="0,01"
                                            min="0"
                                            placeholder="Ej. 87,45"
                                        >
                                    </div>
                                </div>

                                <div class="form-group w-100 no-margin">
                                    <label>Días totales de la factura</label>
                                    <div class="input-group">
                                        <input
                                            v-model="supplyData.totalDays"
                                            type="number"
                                            min="1"
                                            max="75"
                                            placeholder="Ej. 30"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex w-100 column-mobile" data-gap="20">
                                <div class="form-group w-100 no-margin">
                                    <label>{{ inputData.energyType === 'gas' ? 'Consumo de gas (kWh)' : 'kWh consumidos (por factura)' }}</label>
                                    <div class="input-group">
                                        <input
                                            v-model="supplyData.consumption[0]"
                                            type="number"
                                            step="0,01"
                                            min="0"
                                            placeholder="Ej. 245,30"
                                            @input="supplyData.consumption[0] = parseStringToNumber($event.target.value)"
                                        >
                                    </div>
                                </div>

                                <div v-if="inputData.energyType !== 'gas'" class="form-group w-100 no-margin">
                                    <label>Potencia contratada (kW)</label>
                                    <div class="input-group">
                                        <input
                                            v-model="supplyData.power[0]"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="Ej. 4.6"
                                            @input="supplyData.power[0] = parseStringToNumber($event.target.value)"
                                        >
                                    </div>
                                </div>
                            </div>


                            <!-- PERFIL GAS -->
                            <div v-if="inputData.energyType === 'gas'" class="form-group w-100 no-margin">
                                <label>¿Para qué usa el gas principalmente?</label>

                                <div class="d-flex w-100 wrap profile-mobile" data-gap="15">

                                    <div
                                        class="custom-button"
                                        data-style="twoBlue"
                                        data-size="regular"
                                        @click="consumptionProfile = 'water'"
                                        :style="{
                                        border: '1px solid',
                                        borderColor: consumptionProfile === 'water' ? '#1D4ED8' : '#D6E4FF',
                                        background: consumptionProfile === 'water' ? '#EFF6FF' : '#FFFFFF',
                                        fontWeight: consumptionProfile === 'water' ? '600' : '400'
                                        }">
                                        Solo agua caliente
                                    </div>

                                    <div
                                        class="custom-button"
                                        data-style="twoBlue"
                                        data-size="regular"
                                        @click="consumptionProfile = 'kitchen'"
                                        :style="{
                                        border: '1px solid',
                                        borderColor: consumptionProfile === 'kitchen' ? '#1D4ED8' : '#D6E4FF',
                                        background: consumptionProfile === 'kitchen' ? '#EFF6FF' : '#FFFFFF',
                                        fontWeight: consumptionProfile === 'kitchen' ? '600' : '400'
                                        }">
                                        Agua caliente + cocina
                                    </div>

                                    <div
                                        class="custom-button"
                                        data-style="twoBlue"
                                        data-size="regular"
                                        @click="consumptionProfile = 'heating'"
                                        :style="{
                                        border: '1px solid',
                                        borderColor: consumptionProfile === 'heating' ? '#1D4ED8' : '#D6E4FF',
                                        background: consumptionProfile === 'heating' ? '#EFF6FF' : '#FFFFFF',
                                        fontWeight: consumptionProfile === 'heating' ? '600' : '400'
                                        }">
                                        Calefacción + agua caliente
                                    </div>

                                </div>
                            </div>


                            <!-- PERFIL LUZ -->
                            <div v-else-if="inputData.energyType === 'electricity'" class="form-group w-100 no-margin">
                                <label>¿Cuándo consume más luz?</label>
                            <div class="d-flex w-100 wrap profile-mobile" data-gap="15">
                                <div
                                    class="custom-button"
                                    data-style="twoBlue"
                                    data-size="regular"
                                    @click="consumptionProfile = 'day'"
                                    :style="{
                                        flex: '1 1 0',
                                        minWidth: '0',
                                        minHeight: '52px',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        textAlign: 'center',
                                        boxSizing: 'border-box',
                                        border: '1px solid',
                                        borderColor: consumptionProfile === 'day' ? '#1D4ED8' : '#D6E4FF',
                                        background: consumptionProfile === 'day' ? '#EFF6FF' : '#FFFFFF',
                                        fontWeight: consumptionProfile === 'day' ? '600' : '400',
                                        transition: 'background-color .2s ease, border-color .2s ease, color .2s ease'
                                    }"
                                >
                                    Principalmente de día
                                </div>

                                <div
                                    class="custom-button"
                                    data-style="twoBlue"
                                    data-size="regular"
                                    @click="consumptionProfile = 'night'"
                                    :style="{
                                        flex: '1 1 0',
                                        minWidth: '0',
                                        minHeight: '52px',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        textAlign: 'center',
                                        boxSizing: 'border-box',
                                        border: '1px solid',
                                        borderColor: consumptionProfile === 'night' ? '#1D4ED8' : '#D6E4FF',
                                        background: consumptionProfile === 'night' ? '#EFF6FF' : '#FFFFFF',
                                        fontWeight: consumptionProfile === 'night' ? '600' : '400',
                                        transition: 'background-color .2s ease, border-color .2s ease, color .2s ease'
                                    }"
                                >
                                    Principalmente de noche
                                </div>

                                <div
                                    class="custom-button"
                                    data-style="twoBlue"
                                    data-size="regular"
                                    @click="consumptionProfile = 'balanced'"
                                    :style="{
                                        flex: '1 1 0',
                                        minWidth: '0',
                                        minHeight: '52px',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        textAlign: 'center',
                                        boxSizing: 'border-box',
                                        border: '1px solid',
                                        borderColor: consumptionProfile === 'balanced' ? '#1D4ED8' : '#D6E4FF',
                                        background: consumptionProfile === 'balanced' ? '#EFF6FF' : '#FFFFFF',
                                        fontWeight: consumptionProfile === 'balanced' ? '600' : '400',
                                        transition: 'background-color .2s ease, border-color .2s ease, color .2s ease'
                                    }"
                                >
                                    Consumo equilibrado
                                </div>
                            </div>

                            <div v-if="parseStringToNumber(supplyData.power[0]) >= 15" class="form-group w-100 no-margin mt-20">
                                <label>¿En qué mes quiere hacer la estimación?</label>
                                <div class="input-group">
                                    <select v-model="selectedMonth">
                                        <option :value="1">Enero</option>
                                        <option :value="2">Febrero</option>
                                        <option :value="3">Marzo</option>
                                        <option :value="4">Abril</option>
                                        <option :value="5">Mayo</option>
                                        <option :value="6">Junio</option>
                                        <option :value="7">Julio</option>
                                        <option :value="8">Agosto</option>
                                        <option :value="9">Septiembre</option>
                                        <option :value="10">Octubre</option>
                                        <option :value="11">Noviembre</option>
                                        <option :value="12">Diciembre</option>
                                    </select>
                                </div>
                            </div>

                            </div>

                            <!--Comercializadora actual-->
                            <div class="form-group w-100 no-margin">
                                <p class="text opacity-6">Comercializadora actual</p>
                                <div class="input-group">
                                    <select v-model="currentMarketer">
                                        <option value="" disabled>Selecciona una</option>
                                        <option v-for="marketer of marketers.toSorted((a,b) => a.name.localeCompare(b.name))">{{marketer.name}}</option>
                                        <option value="other(This never matches)">Otra comercializadora</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-center w-100">
                                <div
                                    class="custom-button text-center w-100"
                                    data-style="blue"
                                    data-size="regular"
                                    @click="generateComparative"
                                >
                                    Continuar
                                </div>
                            </div>
                        </div>
                </template>
                </div>
            </transition>
        </div>
        <!--Resultados-->
        <div v-else-if="currentView === 'results'" class="d-flex column align-center w-100" data-gap="50">
            <div class="w-700-px w-90-max">
                <button class="custom-button" data-size="small" @click="resetComparator">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </button>
                <div class="d-flex justify-between mt-20" data-size="15" data-gap="30">
                    <p class="text ellipsis noWidth">
                        {{ inputData.invoiceOrData === 'data' ? (inputData.energyType === 'gas' ? 'Comparativa gas' : 'Comparativa luz') : clientData.name }}
                    </p>
                    <p class="text ellipsis noWidth">{{clientData.marketer}}</p>
                </div>
                <div class="separator h-2-px mx-auto"></div>
                <div class="d-flex align-center column-mobile" data-gap="20">
                    <p data-size="24">
                        <span data-weight="600">{{ formatNumber(currentTotal, {minimumFractionDigits: 2}) }}€</span>
                    </p>
                    <p data-size="15">
                        <span class="opacity-6"><i class="fa-light fa-gauge-circle-bolt"></i> Consumo:</span>
                        {{ formatNumber(totalConsumption) }} kWh
                    </p>
                    <p v-if="inputData.energyType !== 'gas'" data-size="15">
                        <span class="opacity-6"><i class="fa-light fa-square-bolt"></i> Potencia:</span>
                        {{ formatNumber(maxPower) }} kW
                    </p>
                    <p data-size="15">
                        <span class="opacity-6"><i class="fa-light fa-calendar-range"></i></span>
                        {{ supplyData.totalDays }} días
                    </p>
                </div>
            </div>
            <div class="w-700-px w-90-max d-flex column" data-gap="20">

                <!--  MENSAJE SI NO HAY OFERTAS -->
                <div v-if="filteredOffers.length === 0" class="dashboard-card column justify-center align-center w-100 p-20 text-center">
                    <p class="text" data-size="18" data-weight="600">
                        No hemos encontrado ofertas disponibles
                    </p>
                    <p class="text mt-10" data-size="14">
                        Puede que los datos introducidos no encajen con ninguna tarifa disponible.
                    </p>
                    <p class="text mt-5 opacity-7" data-size="13">
                        Pruebe ajustando los valores o realizando una nueva comparativa.
                    </p>

                    <div class="custom-button mt-15" data-size="small" @click="resetComparator">
                        Hacer otra comparativa
                    </div>
                </div>


                <div v-else class="d-flex column" data-gap="20">
                    <div v-for="offer in filteredOffers" :key="offer._id" class="dashboard-card column justify-between w-100">
                        <div class="d-flex relPos">
                            <img class="h-30-px-max w-60-px-max contain-img" v-if="offer.marketer !== null"
                                :src="`/assets/marketers_logo/${marketers.find(marketer => marketer.name === offer.marketer).logo}`"
                                alt="logo" />
                            <p class="text ml-10 ellipsis" data-size="17" data-weight="600">{{ offer.product }}</p>
                        </div>
                        <div class="d-flex mt-10 offer-mobile" data-gap="30">
                            <div class="d-flex column justify-center">
                                <div class="d-flex align-end ml-20" data-gap="5">
                                    <p class="text" style="line-height: 1" data-size="28" data-weight="600">
                                        {{ Math.round(offer.total) }}€
                                    </p>
                                    <p class="text opacity-6">/ mes</p>
                                </div>
                                <p class="text mt-5 ml-20" data-size="14">{{ Math.round(offer.savePercent) }}% de ahorro</p>
                            </div>
                            <div class="d-flex column justify-center">
                                <div class="d-flex h-31-px py-5 w-300-px round hidden opacity-9" data-round="10">
                                    <div class="h-100" data-bg="principal" :style="{ width: getPercentage(offer.total) }"/>
                                    <div class="h-100" data-bg="success" :style="{ width: getPercentage(currentTotal - offer.total) }" />
                                </div>
                                <div class="d-flex align-end justify-end mt-5" data-gap="5">
                                    <p class="text" data-size="15">Ahorra</p>
                                    <p class="text" style="line-height: 1" data-size="24" data-weight="600" :data-color="offer.savePercent > 0 ? 'success' : 'rojo'">{{Math.round(currentTotal - offer.total)}}€</p>
                                    <p class="text" data-size="15">/ mes</p>
                                </div>
                            </div>
                            <div class="separator my-0" data-position="vertical" />
                            <div class="d-flex justify-center align-center column">
                                <div class="custom-button" data-size="regular" @click="offerSelected = offer">
                                Me interesa
                                </div>
                                <p class="text mt-5 pointer" @click="offerToSeeDetails = offer">Más detalles</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!--Confirmación-->
        <div v-else-if="currentView === 'confirmation'" class="d-flex column align-center w-700-px w-90-max" data-gap="10">
            <i class="fas fa-check-circle" data-size="50" data-color="success"></i>
            <p class="text" data-size="30" data-weight="600">
                Solicitud enviada correctamente
            </p>
            <p class="text mt-10" data-size="18">
                Hemos recibido su solicitud. En breve uno de nuestros gestores se pondrá en contacto con usted para finalizar la contratación.
            </p>
            <div class="custom-button mt-20" data-size="big" @click="resetComparator">Hacer otra comparativa</div>
        </div>
    </div>
    </transition>
    <!--Pantalla de carga-->
    <div class="loader-box" v-if="isLoading" style="position: fixed">
        <div class="d-flex column align-center p-20 round" data-gap="10" data-bg="blanco">
            <div class="text" data-size="20" data-weight="600">
                {{ loadingMessages[currentMessage] }}
            </div>
            <div class="loader"></div>
        </div>
    </div>
    <!--Mas detalles-->
    <div v-if="offerToSeeDetails" class="modal" @click="offerToSeeDetails = null">
        <div class="w-700-px w-90-max d-flex f-wrap round p-20 justify-center" data-gap="50" data-round="20" data-bg="blanco" data-border-color="principal" @click.stop>
            <template v-if="inputData.energyType === 'electricity'">
                <div class="d-flex column" data-gap="5">
                    <p class="text" data-size="18" data-weight="500">
                        <i class="fas fad fa-square-bolt " /> Potencia <span data-size="10" class="opacity-6" data-weight="400">€/kW día</span>
                    </p>
                    <div class="d-flex" data-gap="20">
                        <div>
                            <p class="opacity-6">Punta</p>
                            <p data-size="14">{{this.formatNumber(offerToSeeDetails.prices.power[0],{maximumFractionDigits: 6})}}</p>
                        </div>
                        <div>
                        <p class="opacity-6">Valle</p>
                            <p data-size="14">{{this.formatNumber(offerToSeeDetails.prices.power[1],{maximumFractionDigits: 6})}}</p>
                        </div>
                    </div>
                </div>
                <div class="d-flex column" data-gap="5">
                    <p class="text" data-size="18" data-weight="500">
                        <i class="fas fad fa-gauge-circle-bolt " /> Energía <span data-size="10" class="opacity-6" data-weight="400">€/kWh</span>
                    </p>
                    <div class="d-flex" data-gap="20">
                        <div>
                        <p class="opacity-6">Punta</p>
                            <p data-size="14">{{this.formatNumber(offerToSeeDetails.prices.energy[0],{maximumFractionDigits: 6})}}</p>
                        </div>
                        <div>
                            <p class="opacity-6">Llano</p>
                            <p data-size="14">{{this.formatNumber(offerToSeeDetails.prices.energy[1],{maximumFractionDigits: 6})}}</p>
                        </div>
                        <div>
                        <p class="opacity-6">Valle</p>
                            <p data-size="14">{{this.formatNumber(offerToSeeDetails.prices.energy[2],{maximumFractionDigits: 6})}}</p>
                        </div>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="d-flex w-100 justify-center" data-gap="40">

                    <!-- TÉRMINO FIJO -->
                    <div class="d-flex column" data-gap="5">
                        <p class="text" data-size="18" data-weight="500">
                            <i class="far fa-chart-gantt"></i> Término fijo
                            <span data-size="10" class="opacity-6" data-weight="400">€/día</span>
                        </p>

                        <p data-size="14">
                            {{ this.formatNumber(offerToSeeDetails.prices.fixed ?? offerToSeeDetails.prices.power?.[0], { maximumFractionDigits: 6 }) }}
                        </p>
                    </div>

                    <!-- TÉRMINO VARIABLE -->
                    <div class="d-flex column" data-gap="5">
                        <p class="text" data-size="18" data-weight="500">
                            <i class="far fa-chart-sine"></i> Término variable
                            <span data-size="10" class="opacity-6" data-weight="400">€/kWh</span>
                        </p>

                        <p data-size="14">
                            {{ this.formatNumber(offerToSeeDetails.prices.variable ?? offerToSeeDetails.prices.energy, { maximumFractionDigits: 6 }) }}
                        </p>
                    </div>

                </div>
            </template>
        </div>
    </div>

    <!--Contratar-->
    <div v-if="offerSelected" class="modal" @click="offerSelected = null">
        <div class="w-500-px w-90-max d-flex column align-center round p-20 justify-center" data-gap="10" data-round="20" data-bg="blanco" data-border-color="principal" @click.stop>
            <p class="text" data-size="20" data-weight="600">Solicitar información</p>
            <p class="text w-90-max" data-size="12">Déjenos sus datos y un gestor se pondrá en contacto con usted para finalizar la contratación.</p>
            <form class="d-flex column form w-100 w-90-max" @submit.prevent="createOrder">
                <div class="form-group no-margin">
                    <label>Nombre</label>
                    <div class="input-group">
                        <input data-size="10" v-model="clientData.name" type="text" required>
                        <span v-if="errors.name" class="error">{{ errors.name }}</span>
                    </div>
                </div>
                <div class="form-group no-margin">
                    <label>Email</label>
                    <div class="input-group">
                        <input data-size="10" v-model="clientData.email" type="email" required>
                    </div>
                </div>
                <div class="form-group no-margin">
                    <label>Teléfono</label>
                    <div class="input-group">
                        <input data-size="10" v-model="clientData.phone" type="tel" required>
                    </div>
                </div>

                <div @click="acceptedTerms = !acceptedTerms">
                    <input type="checkbox" v-model="acceptedTerms" /> Acepto las <a href="/terminos" target="_blank">condiciones de uso</a>.
                </div>
                <button type="submit" class="custom-button mt-20" data-size="medium" :disabled="isSendingOrder || !acceptedTerms" :class="{ 'disabled-button': isSendingOrder }" >
                    <span v-if="!isSendingOrder">Enviar datos</span>

                    <span v-else class="loading-dots text opacity-5">
                        Cargando<span>.</span><span>.</span><span>.</span>
                    </span>

                </button>
            </form>
            <p class="opacity-9 w-90-max" data-size="10">Si sigue adelante con el proceso será necesario un aporte de CUPS para el sorteo.</p>
        </div>

    </div>
    <FloatingContactButtons />
</template>

<script>
import FloatingContactButtons from "../items/FloatingContactButtons.vue";
export default {
    name: 'OpenComparatorComponent',
    components: {
        FloatingContactButtons,
    },
    props: ["basicData"],
    data(){
        return{
            referralUser: null,
            acceptedTerms: false,
            views: ['landing', 'form', 'results', 'confirmation'],
            currentView: 'landing',
            steps: ['energyType', 'clientType', 'invoiceOrData'],
            currentStep: 'energyType',
            inputData: {
                energyType: '',
                clientType: '',
                invoiceOrData: '',
            },
            invoice: {
                files: null,
                filesSelected: null,
                fileType: null,
                previewUrl: null,
            },
            supplyData: {
                cups: '',
                fee: '',
                dates: {
                    start: '',
                    end: ''
                },
                totalDays: 30,
                power: [3.7],
                powerPricePeriod: "day",
                consumption: [250],
                gasConsumption: 1000,
                prices: {
                    power: [],
                    energy: [],
                    powerDiscount: 0,
                    energyDiscount: 0,
                },
                surplus: {
                    virtualBattery: 0,
                    amount: 0,
                    price: 0,
                },
                otherConcepts: [],
            },
            taxes: {
                iva: 21,
                electricTax: '5,11269632',
                hidroTax: '0,234',
                socialBonus: '0,019121',
                meterDevice: 0,
                meterDevicePricePeriod: 'day',
            },
            currentSubtotal: { power: {total: 0}, energy: {total: 0}, surplus: {total: 0, virtualBattery: 0 }},
            currentTotal: 0,
            consumptionProfile: '',
            selectedMonth: new Date().getMonth() + 1,
            clientData: {
                name: '',
                email: '',
                phone: '',
                CIF: "",
                billingInfo: {
                    community: "",
                    province: "",
                    locality: "",
                    address: "",
                    postal: ""
                },
                order: {
                    productType: '',
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
            errors:{},
            marketers: [],
            filteredMarketers: [],
            currentMarketer: '',
            comparatorHiddenProducts: [],
            offersList: [],
            hasSurplus: false,
            offerToSeeDetails: null,
            offerSelected: null,
            isLoading: false,
            isSendingOrder: false,
            loadingMessages: [
                "Analizando tus kilovatios con precisión quirúrgica...",
                "Buscando ahorros entre watios y euros...",
                "¿Sabías que la luz viaja a 300.000 km/s? Nosotros un poco menos, pero casi.",
                "Buscando formas de que tu próxima factura te sorprenda... pero para bien."

            ],
            currentMessage: null,
        }
    },
    created(){
        const params = new URLSearchParams(window.location.search)
        this.referralUser = params.get("ref");

        this.fetchMarketers();
    },
    methods: {
        async fetchMarketers(){


            const params = {
                from: 'openComparator'
            }

            if(this.referralUser){
                params.assignContractTo = this.referralUser
            }

            await axios.get('/api/marketers', { params })
                .then((res) => {
                    this.marketers = res.data.marketers;

                    this.comparatorHiddenProducts =
                        res.data.comparatorHiddenProducts ||
                        res.data.user?.comparatorHiddenProducts ||
                        res.data.referralUser?.comparatorHiddenProducts ||
                        [];

                    console.log('RESPUESTA MARKETERS OPEN COMPARATOR', res.data)
                    console.log('PRODUCTOS OCULTOS', this.comparatorHiddenProducts)
                })
                .catch((err) => {
                    console.log(err)
                })

        },
        nextStep(value){
            this.inputData[this.currentStep] = value;
            let index = this.steps.indexOf(this.currentStep);

            this.currentStep = this.steps[index + 1];
        },
        getFilesSelected(event) {
            const files = event.target.files || [];
            this.invoice.filesSelected =
                files.length === 1
                    ? files[0].name
                    : `${files.length} archivos seleccionados`;

            if (this.invoice.previewUrl && this.invoice.previewUrl.startsWith("blob:")) {
                URL.revokeObjectURL(this.invoice.previewUrl);
            }

            if (!files.length) {
                this.invoice.previewUrl = null;
                this.invoice.fileType = null;
                return;
            }

            this.invoice.files = files;

            // Usamos SOLO el primer archivo para la previsualización
            const file = files[0];
            this.invoice.previewUrl = URL.createObjectURL(file);

            if (file.type && file.type.startsWith("image/")) {
                this.invoice.fileType = "image";
            } else if (file.type === "application/pdf") {
                this.invoice.fileType = "pdf";
            } else {
                this.invoice.fileType = null; // tipo no soportado
            }
        },

        validateManualForm(){

            const payment = this.parseStringToNumber(this.currentTotal)
            const days = this.parseStringToNumber(this.supplyData.totalDays)
            const consumption = this.inputData.energyType === 'gas'
            ? this.parseStringToNumber(this.supplyData.gasConsumption)
            : this.parseStringToNumber(this.supplyData.consumption[0])
            const power = this.parseStringToNumber(this.supplyData.power[0])

            // comprobar campos vacíos
            if(!payment || !days || !consumption || !power){
                Swal.fire({
                    icon: 'warning',
                    title: 'Debe rellenar todos los campos'
                })
                //alert("Debe rellenar todos los campos")
                return
            }

            // comprobar valores negativos
            if(payment < 0 || days < 0 || consumption < 0 || power < 0){
                alert("Los valores no pueden ser negativos")
                return
            }

            // comprobar dias
            if(days > 75){
                alert("Los días de la factura no pueden superar 75")
                return
            }

            // comprobar perfil
            if(!this.consumptionProfile){
                alert("Debe seleccionar cuándo consume más luz")
                return
            }

            // si todo está bien
            this.inputData.invoiceOrData = 'manual'
            this.generateComparative()

        },

        setDefaultManualValues(){

            if(!this.supplyData.totalDays){
                this.supplyData.totalDays = 30
            }

            if(!this.supplyData.consumption.length){
                this.supplyData.consumption = [250]
            }

            if(!this.supplyData.power.length){
                this.supplyData.power = [3.7]
            }

        },

        async generateComparative(){


            const valid =
            this.currentTotal >= 0 &&
            this.supplyData.totalDays > 0 &&
            this.supplyData.totalDays <= 75 &&
            (
                this.inputData.energyType === 'gas'
                    ? this.supplyData.gasConsumption > 0
                    : this.supplyData.consumption[0] > 0 && this.supplyData.power[0] > 0
            ) &&
            this.consumptionProfile



            this.isLoading = true;
            this.currentMessage = Math.floor(Math.random() * this.loadingMessages.length)

            this.intervalId = setInterval(() => {
                this.currentMessage = Math.floor(Math.random() * this.loadingMessages.length)
            }, 4000)
            let error = "";

            //Reseteo las comercializadoras visibles
            this.filteredMarketers = this.marketers.map((marketer) => marketer.name).filter(marketer => marketer !== this.currentMarketer)

            try{
                if(this.inputData.invoiceOrData === 'invoice'){
                    const file = this.$refs.invoice.files[0];
                    if (file) {
                        await this.getOCRData();
                        this.calc();
                        if (!this.filteredOffers.length) {
                            await Swal.fire({
                                icon: 'warning',
                                title: 'Ups, no hemos podido leer esta factura',
                                html: `
                                    <p>Puede que sea demasiado antigua o que el formato no sea compatible.</p>
                                    <p>Pruebe con otra factura más reciente o con mejor calidad.</p>
                                `,
                                confirmButtonText: 'Entendido'
                            });

                            this.isLoading = false;

                            return;
                        }
                        this.hasSurplus = !!this.supplyData.surplus?.amount;
                        this.currentView = 'results';
                    }else {
                        error = "Por favor selecciona un archivo válido."
                        // this.generateLog('error', 'Por favor selecciona un archivo válido.',this.optionSelected, 'handleSubmit (isset file)')
                    }
                }
                else if(this.inputData.invoiceOrData === 'data'){

                    this.setDefaultManualValues()
                    const payment = this.parseStringToNumber(this.currentTotal)
                    const days = this.parseStringToNumber(this.supplyData.totalDays)
                    const consumptionTotal = this.inputData.energyType === 'gas'
                    ? this.parseStringToNumber(this.supplyData.gasConsumption)
                    : this.parseStringToNumber(this.supplyData.consumption[0])
                    if(this.inputData.energyType === 'gas'){

                        if(this.consumptionProfile === 'water'){
                            this.supplyData.gasConsumption = 250
                            this.supplyData.fee = 'RL1'
                        }

                        if(this.consumptionProfile === 'kitchen'){
                            this.supplyData.gasConsumption = 600
                            this.supplyData.fee = 'RL1'
                        }

                        if(this.consumptionProfile === 'heating'){
                            this.supplyData.gasConsumption = 1400
                            this.supplyData.fee = 'RL2'
                        }

                    }else{
                        const power = this.parseStringToNumber(this.supplyData.power[0])
                        this.supplyData.fee = power >= 15 ? '3.0TD' : '2.0TD'
                    }
                    const power = this.parseStringToNumber(this.supplyData.power[0])

                    // VALIDACIÓN
                    if(this.inputData.energyType === 'gas'){

                    if(
                        payment < 0 ||
                        days <= 0 ||
                        days > 75 ||
                        consumptionTotal <= 0
                    ){
                        Swal.fire({
                            icon: 'error',
                            title: 'Datos inválidos',
                            text: 'Revise los campos introducidos.'
                        })
                        return
                    }

                }
                    else{

                    if(
                        payment < 0 ||
                        days <= 0 ||
                        days > 75 ||
                        consumptionTotal <= 0 ||
                        power <= 0 ||
                        !this.consumptionProfile
                    ){
                        Swal.fire({
                            icon: 'error',
                            title: 'Datos inválidos',
                            text: 'Revise los campos introducidos.'
                        })
                        return
                    }

                }

                    if(this.inputData.energyType !== 'gas'){

                        if(power >= 15){
                            this.supplyData.power = [power, power, power, power, power, power]
                        }else{
                            this.supplyData.power = [power, power]
                        }

                    }

                    let punta, llano, valle

                    if(this.consumptionProfile === 'day'){
                        punta = consumptionTotal * 0.5
                        llano = consumptionTotal * 0.3
                        valle = consumptionTotal * 0.2
                    }

                    if(this.consumptionProfile === 'night'){
                        punta = consumptionTotal * 0.2
                        llano = consumptionTotal * 0.3
                        valle = consumptionTotal * 0.5
                    }

                    if(this.consumptionProfile === 'balanced'){
                        punta = consumptionTotal * 0.33
                        llano = consumptionTotal * 0.33
                        valle = consumptionTotal * 0.34
                    }

                    if(this.inputData.energyType !== 'gas'){

                    if(power >= 15){

                        const month = Number(this.selectedMonth);

                        // TEMPORADA SEGÚN MES
                        let season;

                        if([6,7,8,9].includes(month)){
                            season = 'high';     // verano
                        } else if([12,1,2].includes(month)){
                            season = 'low';      // invierno bajo
                        } else {
                            season = 'medium';
                        }

                        let distribution;

                        // PERFIL + MES
                        if(this.consumptionProfile === 'day'){
                            if(season === 'high')   distribution = [0.32, 0.22, 0.16, 0.12, 0.10, 0.08];
                            if(season === 'medium') distribution = [0.28, 0.22, 0.18, 0.14, 0.10, 0.08];
                            if(season === 'low')    distribution = [0.24, 0.20, 0.18, 0.16, 0.12, 0.10];
                        }

                        if(this.consumptionProfile === 'night'){
                            if(season === 'high')   distribution = [0.08, 0.10, 0.12, 0.18, 0.22, 0.30];
                            if(season === 'medium') distribution = [0.08, 0.12, 0.14, 0.18, 0.22, 0.26];
                            if(season === 'low')    distribution = [0.10, 0.14, 0.16, 0.18, 0.20, 0.22];
                        }

                        if(this.consumptionProfile === 'balanced'){
                            distribution = [0.17, 0.17, 0.16, 0.16, 0.17, 0.17];
                        }

                        this.supplyData.consumption = distribution.map(p =>
                            Number((consumptionTotal * p).toFixed(2))
                        );
                    }else{

                            // 2.0TD
                            this.supplyData.consumption = [
                                punta,
                                llano,
                                valle
                            ];
                        }
                    }

                    //console.log("Consumo calculado:", this.supplyData.consumption)
                    this.calc()

                    this.currentView = 'results'
                }
            }catch (error){
                console.log(error)
                let errorMessage = "";
                switch (this.inputData.invoiceOrData) {
                    case "invoice":
                        errorMessage = "Hubo un error. Comprueba que la factura sea un archivo válido."
                        break;
                    case "data":
                        errorMessage = "Hubo un error. Por favor revisa que los campos estén rellenos."
                        break;
                }

                //Genero el log del error
                // this.generateLog('error', errorMessage,this.optionSelected, 'handleSubmit (try catch)')

                await Swal.fire({
                    icon: "error",
                    title: errorMessage
                })
            }finally {
                this.isLoading = false;
                clearInterval(this.intervalId)
            }
        },
        async getOCRData(){
            const formData = new FormData();
            Array.from(this.$refs.invoice.files).forEach(file => {
                formData.append("files[]", file);
            });
            const userSubdomainId = this.basicData?.userSubdomain?._id;

            if (userSubdomainId) {
                formData.append("userSubdomain", userSubdomainId);
            }
             const response = await axios.post('/api/tools/getOCRData', formData)
             const data = response.data;
            /*const data = JSON.parse(`{
                              "titular": "MARIN GARCIA, JESUS SALVADOR",
                              "cif_nif": "27339441P",
                              "direccion_titular": {
                                "direccion": "Calle JUAN BREVA 9,4-015",
                                "poblacion": "MARBELLA",
                                "codigo_postal": "29601",
                                "provincia": "Málaga",
                                "comunidad_autonoma": "Andalucía"
                              },
                              "direccion_suministro": {
                                "direccion": "Calle JUAN BREVA 9,4-015",
                                "poblacion": "MARBELLA",
                                "codigo_postal": "29601",
                                "provincia": "Málaga",
                                "comunidad_autonoma": "Andalucía"
                              },
                              "periodo_facturacion": {
                                "fecha_inicio": "08/08/2025",
                                "fecha_fin": "06/09/2025"
                              },
                              "dias_facturacion": 29,
                              "comercializadora": "Factor Energia",
                              "cups": "ES0031103006468017QM",
                              "tarifa": "2.0TD",
                              "otros": {
                                "alquiler_equipo_medida": 0.0279,
                                "kwh_excedentes": null,
                                "precio_excedentes": null,
                                "iva": 21,
                                "bono_social": 0
                              },
                              "conceptos_extra": {
                                "Emergencia hogar + plan tranquilidad": 6.69,
                                "Sum. Energía verde certificada": 1.78
                              },
                              "potencias_contratadas": {
                                "p1": 3.3,
                                "p2": 3.3,
                                "p3": null,
                                "p4": null,
                                "p5": null,
                                "p6": null
                              },
                              "precios_potencias": {
                                "p1": 2.385871,
                                "p2": 2.385871,
                                "p3": null,
                                "p4": null,
                                "p5": null,
                                "p6": null
                              },
                              "periodo_precio_potencia": "month",
                              "descuento_potencia": null,
                              "energia_consumida": {
                                "p1": 146.49,
                                "p2": 145.9,
                                "p3": 411.47,
                                "p4": null,
                                "p5": null,
                                "p6": null
                              },
                              "precios_energia": {
                                "p1": 0.179949,
                                "p2": 0.179949,
                                "p3": 0.179949,
                                "p4": null,
                                "p5": null,
                                "p6": null
                              },
                              "descuento_energia": null,
                              "total": 191.41
                        }`)*/

            //Asigno valores para calcular
            if(this.inputData.energyType === 'gas'){

            this.supplyData = {
                ...this.supplyData,
                cups: data.cups?.replace(/ /g, "") || "",
                fee: data.tarifa,
                dates: {
                    start: moment(data.periodo_facturacion.fecha_inicio, "DD/MM/YYYY"),
                    end: moment(data.periodo_facturacion.fecha_fin, "DD/MM/YYYY")
                },

                // GAS
                gasConsumption: this.parseStringToNumber(data.consumo),
                consumption: [this.parseStringToNumber(data.consumo)],

                prices: {
                    energy: [this.parseStringToNumber(data.precio_consumo)],
                    power: [this.parseStringToNumber(data.termino_fijo)],
                }
            }

        }else{

            this.supplyData = {
                ...this.supplyData,
                cups: data.cups.replace(/ /g, ""),
                fee: data.tarifa,
                dates: {
                    start: moment(data.periodo_facturacion.fecha_inicio, "DD/MM/YYYY"),
                    end: moment(data.periodo_facturacion.fecha_fin, "DD/MM/YYYY")
                },
                power: Object.values(data.potencias_contratadas),
                powerPricePeriod: data.periodo_precio_potencia ?? 'day',
                consumption: Object.values(data.energia_consumida),
                prices: {
                    power: Object.values(data.precios_potencias),
                    energy: Object.values(data.precios_energia),
                    powerDiscount: data.descuento_potencia,
                    energyDiscount: data.descuento_energia,
                }
            }

        }

            //Asigno el total
            this.currentTotal = data.total;

            //Compruebo si vienen conceptos extra:
            if(Object.keys(data.conceptos_extra).length > 0){
                Object.entries(data.conceptos_extra).forEach(([name, value]) => {
                    this.supplyData.otherConcepts.push({name, value})
                })
            }

            //Asigno valores de excedentes;
            if(this.inputData.energyType !== 'gas'){
                this.supplyData.surplus.amount = data.otros.kwh_excedentes;
                this.supplyData.surplus.price = data.otros.precio_excedentes;
            }

            //Compruebo si las fechas de facturación concuerdan con como las manejo, si no la cambio un dia antes.
            if (this.supplyData.dates.end.diff(this.supplyData.dates.start, "days") !== data.dias_facturacion) {
                this.supplyData.dates.start = this.supplyData.dates.start.subtract(1, "day");
            }
            this.supplyData.totalDays = this.supplyData.dates.end.diff(this.supplyData.dates.start, "days");

            //Asigno valores de impuestos
            this.taxes.meterDevice = data.otros.alquiler_equipo_medida;
            this.taxes.iva = data.otros.iva;
            this.taxes.socialBonus = data.otros.bono_social;



            //Asigno valores de oportunidad
            this.clientData = {
                name: this.formatName(data.titular),
                CIF: data.cif_nif,
                marketer: this.formatName(data.comercializadora),
                billingInfo: {
                    community: data.direccion_titular.comunidad_autonoma,
                    province: data.direccion_titular.provincia,
                    locality: data.direccion_titular.poblacion,
                    address: data.direccion_titular.direccion,
                    postal: data.direccion_titular.codigo_postal
                },
                order: {
                    productType: this.inputData.energyType === 'gas' ? 'cg' : 'cl',
                    direc: data.direccion_suministro.direccion,
                    zip: data.direccion_suministro.codigo_postal,
                    town: data.direccion_suministro.poblacion,
                    province: data.direccion_suministro.provincia,
                },
            }

        },
        calc(){
            //Compruebo si hay ofertas, si no las hay las creo
            if (this.offersList.length === 0) this.createOffers();

            //Calculo el total para las ofertas
            this.offersList.forEach(offer => {
                offer.subTotal = this.calcTotal({ ...offer.prices, surplus: offer.surplus, fees: offer.fees });
                offer.total = Object.values(offer.subTotal).reduce((acc, value) => {
                    if (typeof value === 'object' && value !== null) {
                        return acc + (value.total ?? 0)
                    }
                    return acc + value
                }, 0);
                offer.savePercent = this.currentTotal === 0 ? -1000 : (this.currentTotal - offer.total) / this.currentTotal * 100;
            })
        },
        calcTotal(prices, isCurrent) {

            // CASO GAS
            if (this.inputData.energyType === 'gas') {

                const fixed = { total: 0, discount: 0 }
                const variable = { total: 0, discount: 0 }
                const consumption = this.parseStringToNumber(this.supplyData.gasConsumption)
                const taxes = { total: 0, iva: 0, hidroTax: 0, meterDevice: 0 }
                const days = this.supplyData.totalDays

                fixed.total = this.parseStringToNumber(prices.fixed) * days
                variable.total = this.parseStringToNumber(prices.variable) * consumption


                // impuestos (simplificados)
                taxes.meterDevice = (this.parseStringToNumber(meterDevicePrice) * days);

                taxes.hidroTax = (this.parseStringToNumber(this.taxes.hidroTax) * consumption) / 100;

                taxes.iva =  this.parseStringToNumber(this.taxes.iva) * (fixed.total + variable.total + taxes.hidroTax) / 100


                taxes.total = taxes.iva + taxes.hidroTax

                return { fixed, variable, taxes, otherConcepts: 0 }
            }


            //  CASO ELECTRICIDAD
            const power = {total: 0, discount: 0};
            const energy = {total: 0, discount: 0};
            const surplus = {total: 0, virtualBattery: 0};
            const taxes = {total: 0, iva: 0, electricTax: 0, socialBonus: 0, meterDevice: 0};

            for (let i = 0; i < 6; i++) {

                let fee = prices.fees ? this.parseStringToNumber(prices.fees.power[i]) / 30 : 0;
                let price = this.parseStringToNumber(prices.power[i]) + fee;
                let consumption = this.parseStringToNumber(this.supplyData.power[i])
                let totalPeriod = price * consumption * this.supplyData.totalDays;

                if(isCurrent && prices.powerDiscount){
                    let discount = totalPeriod * prices.powerDiscount / 100;
                    power.discount -= discount;
                }

                power[`P${i + 1}`] = totalPeriod;
                power.total += totalPeriod;

                fee = prices.fees ? this.parseStringToNumber(prices.fees.energy[i]) / 1000 : 0;
                price = this.parseStringToNumber(prices.energy[i]) + fee;
                consumption = this.parseStringToNumber(this.supplyData.consumption[i]);
                totalPeriod = price * consumption;

                if(isCurrent && prices.energyDiscount){
                    let discount = totalPeriod * prices.energyDiscount / 100;
                    energy.discount -= discount;
                }

                energy[`P${i + 1}`] = totalPeriod;
                energy.total += totalPeriod;
            }

            power.total = Number((power.total + power.discount).toFixed(2));
            energy.total = Number((energy.total + energy.discount).toFixed(2));

            surplus.virtualBattery = Number((this.parseStringToNumber(prices.surplus?.virtualBattery ?? 0) / 30 * this.supplyData.totalDays).toFixed(2));
            surplus.compensation = Number((this.parseStringToNumber(prices.surplus?.amount ?? 0) * this.parseStringToNumber(prices?.surplus?.price ?? 0) * -1).toFixed(2));
            surplus.total = surplus.virtualBattery + surplus.compensation;

            let otherConcepts = 0;
            if (this.supplyData.otherConcepts.length > 0 && isCurrent) {
                otherConcepts = this.supplyData.otherConcepts.reduce((acc, concept) => acc + this.parseStringToNumber(concept.value), 0);
            }

            taxes.socialBonus = Number((this.parseStringToNumber(this.taxes.socialBonus) * this.supplyData.totalDays).toFixed(2));

            let meterDevicePrice = this.taxes.meterDevice;
            switch (this.taxes.meterDevicePricePeriod){
                case "month":
                    meterDevicePrice = this.parseStringToNumber(meterDevicePrice) / 30;
                    break;
                case "year":
                    meterDevicePrice = this.parseStringToNumber(meterDevicePrice) / 365;
                    break;
            }

            taxes.meterDevice = Number((this.parseStringToNumber(meterDevicePrice) * this.supplyData.totalDays).toFixed(2));

            taxes.electricTax = Number((this.parseStringToNumber(this.taxes.electricTax) * (power.total + energy.total + surplus.compensation + taxes.socialBonus) / 100).toFixed(2));

            taxes.iva = Number((this.parseStringToNumber(this.taxes.iva) * (power.total + energy.total + surplus.total + otherConcepts + taxes.socialBonus + taxes.meterDevice + taxes.electricTax) / 100).toFixed(2));

            taxes.total = taxes.iva + taxes.electricTax + taxes.socialBonus + taxes.meterDevice;

            return { power, energy, surplus, taxes, otherConcepts};
        },

        getMarketerProductsForVisibility(marketer) {

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

        getProductVisibilityId(product) {
            if (typeof product === 'string' || typeof product === 'number') {
                return product.toString()
            }

            return (
                product._id ||
                product.id ||
                product.productId ||
                product.product_id ||
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
                ''
            ).toString()
        },

        getProductVisibilityIndex(marketer, product, fallbackIndex = null) {
            const allProducts = this.getMarketerProductsForVisibility(marketer)
            const targetId = this.getProductVisibilityId(product)

            const foundIndex = allProducts.findIndex(item => {
                return this.getProductVisibilityId(item) === targetId
            })

            return foundIndex !== -1 ? foundIndex : fallbackIndex
        },

        getOfferProductKey(marketer, product, productIndex = null) {
            const marketerId = marketer._id?.toString()
            const productId = this.getProductVisibilityId(product) || 'product'
            const visibilityIndex = this.getProductVisibilityIndex(marketer, product, productIndex)

            return marketerId + '|' + productId + '|' + visibilityIndex
        },

        isProductHiddenInComparator(marketer, product, productIndex = null) {
            if (!this.comparatorHiddenProducts) {
                this.comparatorHiddenProducts = []
            }

            const key = this.getOfferProductKey(marketer, product, productIndex)

            return this.comparatorHiddenProducts
                .map(item => item.toString())
                .includes(key.toString())
        },

        createOffers() {
        const marketers = this.marketers.filter(marketer => this.filteredMarketers.includes(marketer.name))

        marketers.forEach(marketer => {

            const energyType = this.inputData.energyType

            let feeMarketer = null

            feeMarketer = marketer.fees[energyType]?.find(
                fee => fee.name.includes(this.supplyData.fee)
            )
            console.log('paso 1', feeMarketer)

            marketer.products[energyType]?.forEach((product, productIndex) => {

                if (this.isProductHiddenInComparator(marketer, product, productIndex)) {
                    return
                }

                console.log('paso 2')

                let feeProduct = product.fees.find(
                        fee => fee.id.$oid === feeMarketer?.id?.$oid && !fee?.archived
                    )

                if (feeProduct && feeProduct.priceType === 'fixed') {

                    let prices = {}

                    // ELECTRICIDAD
                    if (energyType === 'electricity') {

                        prices = {
                            power: Object.values(feeProduct.prices.power),
                            energy: Object.values(feeProduct.prices.consume),
                        }

                    }

                    //  GAS
                    if (energyType === 'gas') {

                        prices = {
                            fixed: parseFloat(feeProduct.prices?.fixed ?? 0),
                            variable: parseFloat(feeProduct.prices?.variable ?? 0)
                        }

                    }

                    let offer = {
                        _id: product._id,
                        marketer: marketer.name,
                        marketerId: marketer._id,
                        //fee: feeMarketer.name,
                        fee: feeMarketer?.name ?? '',
                        product: product.name,
                        prices: prices,
                        energyType: energyType,
                        total: 0,
                        subTotal: { power: {total: 0}, energy: {total: 0}, surplus: {total: 0, virtualBattery: 0 }},
                        savePercent: 0,
                        residencial: feeProduct.type?.residencial,
                        pyme: feeProduct.type?.pyme,
                        minPower: feeProduct.minPower,
                        maxPower: feeProduct.maxPower,
                        minConsumption: feeProduct.minConsumption,
                        maxConsumption: feeProduct.maxConsumption,
                        surplusType: feeProduct.surplus
                    }

                    // excedentes solo para electricidad
                    if (energyType === 'electricity' && feeProduct.surplus) {
                        offer.surplus = {
                            virtualBattery: marketer.surplus?.virtualBattery,
                            price: this.supplyData.surplus.virtualBattery
                                ? marketer.surplus?.priceWithVB
                                : marketer.surplus?.priceWithoutVB
                        }
                    }

                    // validar precios
                    if (energyType === 'electricity') {

                        if (parseFloat(prices.power[0]) && parseFloat(prices.energy[0])) {
                            this.offersList.push(offer)
                        }

                    }

                    if (energyType === 'gas') {

                        if (prices.fixed || prices.variable) {
                            this.offersList.push(offer)
                        }

                    }

                }

            })

        })
        console.log("OFERTAS:", this.offersList)
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
        formatNumber(number, {minimumFractionDigits = 0,maximumFractionDigits = 2} = {}) {
            return Intl.NumberFormat("es", {
                minimumFractionDigits, maximumFractionDigits
            }).format(this.parseStringToNumber(number))
        },
        formatDate(date) {
            return moment(date).format("D/M/YYYY");
        },
        formatName(str) {
            return str
                .toLowerCase()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },
        async createOrder() {
            if (this.isSendingOrder) return;

            let hasErrors = false;

            if (this.clientData.name === '') {
                this.errors.name = this.getErrorMessage('isEmpty');
                hasErrors = true;
            }

            if (hasErrors) return;

            this.isSendingOrder = true;

            const formData = new FormData();

            const cups = (this.supplyData.cups || '').replace(/ /g, '');

            const clientToSend = {
                ...this.clientData,
                CUPS: cups,
                order: {
                    ...(this.clientData.order || {}),
                    CUPS: cups,
                },
            };

            formData.append('client', JSON.stringify(clientToSend));
            formData.append('offerSelected', JSON.stringify(this.offerSelected));


            const file = this.invoice.files?.[0];

            if (this.referralUser) {
                formData.append("ref", this.referralUser);
            }

            if (file) {
                formData.append('invoice', file);
            }

            try {
                await axios.post(
                    '/api/openComparator/registerOrder',
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                );

                this.currentView = 'confirmation';
                this.offerSelected = null;

            } catch (error) {
                console.error("Error backend:", error.response?.data);

                const backendMessage = error.response?.data?.message;

                Swal.fire({
                    icon: 'error',
                    title: 'Error al enviar la solicitud',
                    text: backendMessage || 'Ha ocurrido un error inesperado. Por favor inténtelo de nuevo.',
                    confirmButtonText: 'Aceptar'
                });

            } finally {
                this.isSendingOrder = false;
            }
        },
        resetComparator(){
            this.currentStep = 'energyType';
            this.inputData = {
                energyType: '',
                clientType: '',
                invoiceOrData: '',
            }
            this.invoice = {
                files: null,
                filesSelected: null,
                fileType: null,
                previewUrl: null,
            }
            this.supplyData = {
                cups: '',
                    fee: '',
                    dates: {
                    start: '',
                        end: ''
                },
                totalDays: 30,
                    power: [3.7],
                    powerPricePeriod: "day",
                    consumption: [250],
                    gasConsumption: 1000,
                    prices: {
                    power: [],
                        energy: [],
                        powerDiscount: 0,
                        energyDiscount: 0,
                },
                surplus: {
                    virtualBattery: 0,
                        amount: 0,
                        price: 0,
                },
                otherConcepts: [],

            };
            this.clientData = {
                name: '',
                CIF: '',
                billingInfo: {
                    community: '',
                    province: '',
                    locality: '',
                    address: '',
                    postal: ''
                },
                order: {
                    productType: '',
                    direc: '',
                    zip: '',
                    town: '',
                    province: '',
                }
            };
            this.currentTotal = 0;
            this.consumptionProfile = '';
            this.currentView = 'landing';
            this.filteredMarketers = [];
            this.currentMarketer = '';
            this.offersList = [];

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

            if(this.inputData.energyType === 'gas'){

                if(this.consumptionProfile === 'water' && !offer.product?.includes("RL1")){
                    return false
                }

                if(this.consumptionProfile === 'kitchen'){
                    if(!offer.product?.includes("RL1") && !offer.product?.includes("RL2")){
                        return false
                    }
                }

                if(this.consumptionProfile === 'heating'){
                    if(!offer.product?.includes("RL2") && !offer.product?.includes("RL3")){
                        return false
                    }
                }

            }

            const offerConsumption = this.totalConsumption;
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
        getPercentage(offerTotal) {
            if (!this.currentTotal) return '0%'

            const value = (offerTotal / this.currentTotal) * 100

            // Protección por si se pasa
            const safeValue = Math.max(0, Math.min(100, value))

            return safeValue + '%'
        }
    },
    computed: {
        maxPower(){
            return Math.max(...this.supplyData.power.map(period => this.parseStringToNumber(period)))
        },
        totalConsumption(){

            if(this.inputData.energyType === 'gas'){
                return this.parseStringToNumber(this.supplyData.gasConsumption)
            }

            return Math.round(
                this.supplyData.consumption.reduce(
                    (acc, value) => acc + this.parseStringToNumber(value), 0
                )
            )
        },

        isFidelitySubdomain() {
            const enterprise = this.basicData?.enterprise || {};
            const userSubdomain = this.basicData?.userSubdomain || {};
            const host = window.location.hostname || '';

            return (
                String(enterprise.mailConfig || '').toUpperCase() === 'FIDELITY360' ||
                String(enterprise.url || '').toLowerCase().includes('fidelity') ||
                String(userSubdomain._id || '') === '68d260e6bc9e8c38f8003df2' ||
                host.toLowerCase().includes('fidelity')
            );
        },
        filteredOffers(){
            //Filtro por residencial o pyme, tiene excedentes, cumple las potencias y consumos
            let offers = this.offersList.filter((offer) => {

            let filteredOffer =
                ((offer.residencial && 'residential' === this.inputData.clientType) ||
                (offer.pyme && 'pyme' === this.inputData.clientType));

            if(this.inputData.energyType === 'electricity'){

                const isThreePointZero = this.maxPower >= 15;

                //  FILTRO POR TARIFA
                if(isThreePointZero){
                    filteredOffer = filteredOffer && offer.fee?.includes('3.0');
                }else{
                    filteredOffer = filteredOffer && offer.fee?.includes('2.0');
                }

                // resto de filtros (los que teniamos antes)
                filteredOffer = filteredOffer && (
                    (this.hasSurplus && (offer.surplusType === 'required' || offer.surplusType === 'optional')) ||
                    (!this.hasSurplus && (offer.surplusType === 'optional' || offer.surplusType === 'none'))
                ) &&
                this.passesPowerFilter(offer) &&
                this.passesConsumptionFilter(offer)
            }

            return filteredOffer;
        })

            //Filtro la mejor oferta de cada comercializadora
            return Object.values(
                offers.reduce((acc, offer) => {
                    const { marketerId, total } = offer;

                    if ( !acc[marketerId] || total < acc[marketerId].total ) {
                        acc[marketerId] = offer;
                    }

                    return acc;
                }, {})
            ).sort((a, b) => a.total - b.total);
        }
    }
}
</script>

<style scoped>
    .slide-up-enter-active,
    .slide-up-leave-active {
        transition: all 0.4s ease;
    }

    .slide-up-enter-from {
        transform: translateY(40px);
        opacity: 0;
    }

    .slide-up-enter-to {
        transform: translateY(0);
        opacity: 1;
    }

    .slide-up-leave-from {
        transform: translateY(0);
        opacity: 1;
    }

    .slide-up-leave-to {
        transform: translateY(-40px);
        opacity: 0;
    }

    .step-enter-active,
    .step-leave-active {
        transition: all 0.35s ease;
    }

    .step-enter-from {
        opacity: 0;
        transform: translateX(30px);
    }

    .step-leave-to {
        opacity: 0;
        transform: translateX(-30px);
    }

    .step-enter-to,
    .step-leave-from {
        opacity: 1;
        transform: translateX(0);
    }

    .expand-enter-active,
    .expand-leave-active {
        transition:
            max-height 0.4s ease,
            opacity 0.25s ease,
            transform 0.25s ease;
    }

    .expand-enter-from,
    .expand-leave-to {
        max-height: 0;
        opacity: 0;
        transform: translateY(-5px);
    }

    .expand-enter-to,
    .expand-leave-from {
        max-height: 800px; /* mayor que el contenido máximo */
        opacity: 1;
        transform: translateY(0);
    }

    .expand-wrapper {
        overflow: hidden;
    }

    .preview-enter-active,
    .preview-leave-active {
        transition:
            max-height 0.4s ease,
            opacity 0.3s ease,
            transform 0.3s ease;
    }

    .preview-enter-from,
    .preview-leave-to {
        max-height: 0;
        opacity: 0;
        transform: translateY(-10px);
    }

    .preview-enter-to,
    .preview-leave-from {
        max-height: 500px; /* suficiente para el PDF */
        opacity: 1;
        transform: translateY(0);
    }

    .preview-wrapper {
        overflow: hidden;
    }

    .preview-content {
        width: 100%;
        height: 420px;
        object-fit: contain;
    }

/* ========================= */
/* LANDING                   */
/* ========================= */

.landing-hero {
    background: linear-gradient(135deg, #009BE0 0%, #5B52E1 52%, #9929DD 100%);
    box-shadow: 0 20px 60px rgba(153, 41, 221, 0.28);
    border-radius: 24px;
    padding: 52px 40px 44px;
    position: relative;
    overflow: hidden;
}

/* Círculos decorativos de fondo */
.landing-hero::before {
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    pointer-events: none;
}

.landing-hero::after {
    content: '';
    position: absolute;
    bottom: -40px;
    left: -40px;
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
    pointer-events: none;
}

.landing-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 16px;
    border-radius: 99px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,0.9);
    margin-bottom: 20px;
    backdrop-filter: blur(4px);
}

.landing-title {
    font-size: 38px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.15;
    margin: 0 0 16px;
    letter-spacing: -0.5px;
}

.landing-highlight {
    color: #7DDBFF;
    position: relative;
}

.landing-subtitle {
    font-size: 16px;
    color: rgba(255,255,255,0.75);
    line-height: 1.65;
    max-width: 440px;
    margin: 0 auto 28px;
}

/* Stats row */
.landing-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 16px;
    padding: 16px 24px;
    margin-bottom: 28px;
    backdrop-filter: blur(6px);
    width: 100%;
    max-width: 460px;
}

.landing-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
}

.landing-stat-number {
    font-size: 22px;
    font-weight: 800;
    color: #7DDBFF;
    line-height: 1;
}

.landing-stat-label {
    font-size: 12px;
    color: rgba(255,255,255,0.65);
    margin-top: 4px;
    font-weight: 400;
}

.landing-stat-divider {
    width: 1px;
    height: 36px;
    background: rgba(255,255,255,0.2);
    flex-shrink: 0;
}

/* CTA Button */
.landing-cta-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 16px 36px;
    background: #FFFFFF;
    color: #072D68;
    font-size: 17px;
    font-weight: 700;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 6px 20px rgba(6, 45, 104, 0.22);
    letter-spacing: 0.1px;
    margin-bottom: 20px;
    width: 100%;
    max-width: 380px;
}

.landing-cta-btn:hover {
    background: #EAF8FF;
    color: #8F27D9;
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(0, 155, 224, 0.30);
}

.otras-acciones-card--disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
    filter: grayscale(0.2);
}

.landing-cta-btn:active {
    transform: translateY(0);
}

/* Pills */
.landing-pills {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
}

.landing-pill {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    border-radius: 99px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,0.85);
}

/* Services divider */
.landing-services-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.landing-services-line {
    flex: 1;
    height: 1px;
    background: #E5E7EB;
}

.landing-services-label {
    font-size: 13px;
    font-weight: 600;
    color: #9CA3AF;
    text-transform: uppercase;
    letter-spacing: 1px;
    white-space: nowrap;
}

/* Card colored variant */
.otras-acciones-card--colored:hover {
    border-color: var(--card-color);
    background: var(--card-bg);
}

.landing-cta {
    min-width: 220px;
}

/* ========================= */
/* OTRAS ACCIONES            */
/* ========================= */

.otras-acciones-wrapper {
    width: 100%;
}

.otras-acciones-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.otras-acciones-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 8px;
    padding: 24px 16px;
    border-radius: 14px;
    border: 1px solid #E4D5F5;
    background: #FFFFFF;
    text-decoration: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
    cursor: pointer;
}

.otras-acciones-card:hover {
    border-color: #9929DD;
    box-shadow: 0 4px 16px rgba(153, 41, 221, 0.14);
    transform: translateY(-2px);
}

.otras-acciones-card--full {
    flex-direction: row;
    justify-content: center;
    width: 100%;
    gap: 16px;
}

.otras-acciones-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #F3E8FC;
    color: #9929DD;
    margin-bottom: 4px;
    font-size: 22px;
    flex-shrink: 0;
}

/* ========================= */
/* RESPONSIVE MOVIL */
/* ========================= */

@media (max-width: 768px) {

    .column-mobile{
        flex-direction: column !important;
    }

    .wrap-mobile{
        flex-wrap: wrap;
    }

    .offer-mobile{
        flex-direction: column;
        gap:20px !important;
    }

    .button-mobile{
        width:100%;
        padding:16px;
        font-size:16px;
    }

    .w-95-mobile{
        width:95% !important;
    }

    .noWidth{
        max-width:100%;
    }

    /* mejorar cards en movil */
    .dashboard-card{
        padding:20px;
    }

    /* inputs más cómodos */
    .input-group input{
        padding:12px;
        font-size:16px;
    }

    /* botones comparador */
    .custom-button{
        min-height:48px;
    }

    /* resultados comparador */
    .preview-content{
        height:260px;
    }

    /* landing movil */

    .landing-hero {
        padding: 36px 22px 32px;
        border-radius: 18px;
    }

    .landing-title {
        font-size: 26px;
    }

    .landing-stats {
        padding: 12px 10px;
        max-width: 100%;
    }

    .landing-stat-number {
        font-size: 17px;
    }

    .landing-stat-label {
        font-size: 11px;
    }

    .landing-cta-btn {
        padding: 14px 20px;
        font-size: 16px;
        max-width: 100%;
    }

    .landing-pills {
        gap: 8px;
    }

    /* otras acciones movil */

    .otras-acciones-grid {
        grid-template-columns: 1fr;
    }

    .otras-acciones-card--full {
        flex-direction: column;
    }

    /* perfiles consumo movil */

    .profile-mobile{
        flex-direction:column;
    }

    .profile-mobile .custom-button{
        width:100%;
        white-space:normal;
        line-height:1.3;
        padding:14px;
    }



}
</style>

