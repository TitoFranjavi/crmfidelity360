<template>
    <div class="content-white" v-on:click="hideCustomSelects">

        <!--Estilo de movil-->
        <div class="mobile-item">

            <!--Header sticky: título + barra-->
            <div class="sticky-header-mobile">
                <!--Título-->
                <div class="d-flex justify-between align-center">
                    <div class="text my-10" data-size="22" data-weight="700">Contratos</div>
                    <div v-if="canManage('contracts.create')" class="custom-button mobile-create-btn" data-size="medium" data-bg="principal"
                         @click.stop="isSeeingCreateOrder = true; isSeeingMassiveLoad = false">
                        <i class="fas fa-plus"></i>
                    </div>
                </div>

                <!--Dropdown crear contrato mobile-->
                <div v-if="isSeeingCreateOrder" class="mobile-create-dropdown" @click.stop>
                    <div class="mobile-create-options" v-if="!typeOfCreate">
                        <p class="mobile-create-option" @click.stop="typeOfCreate = 'account'">
                            <i class="far fa-buildings mr-10"></i>Creación en cuenta
                        </p>

                        <p class="mobile-create-option"
                        v-if="showDirect"
                        @click.stop="typeOfCreate = null; isSeeingCreateOrder = false; isSeeingOrderComponent = true">
                            <i class="far fa-file-lines mr-10"></i>Creación directa
                        </p>

                        <p class="mobile-create-option" @click.stop="openInvoiceFilePicker">
                            <i class="fas fa-file-invoice mr-10"></i>Creación por factura
                        </p>

                        <input
                            type="file"
                            ref="inputInvoiceFile"
                            style="display: none"
                            multiple
                            accept=".pdf"
                            @change="pickupInvoiceFile"
                        />
                    </div>
                    <div v-if="typeOfCreate === 'account'" class="mobile-create-account-search">
                        <p class="mobile-create-search-title"><i class="far fa-buildings mr-5"></i> Selecciona una cuenta</p>
                        <div class="form-group mb-10">
                            <div class="input-group">
                                <i class="fa-regular fa-magnifying-glass"></i>
                                <input v-model="searchFilters.account" type="text" placeholder="Buscar cuenta..." />
                            </div>
                        </div>
                        <div class="mobile-create-account-list">
                            <div v-for="account in filtersFiltered.accounts" :key="account._id" class="mobile-create-account-item" @click.stop="selectAccToCreateOrder(account._id)">
                                <div class="mobile-create-account-icon"><i class="fas fa-buildings"></i></div>
                                <span>{{ account.name }}</span>
                                <i class="far fa-chevron-right mobile-create-account-arrow"></i>
                            </div>
                            <p v-if="!accountsRelated || accountsRelated.length === 0" class="text opacity-5 text-center py-20" data-size="13">¡Crea primero una cuenta!</p>
                        </div>
                        <div class="mobile-create-back" @click.stop="typeOfCreate = null">
                            <i class="far fa-arrow-left mr-5"></i> Volver
                        </div>
                    </div>
                    <div class="mobile-create-close" @click.stop="isSeeingCreateOrder = false; typeOfCreate = null">
                        <i class="fas fa-times mr-5"></i> Cerrar
                    </div>
                </div>

                <!--Barra de busqueda-->
                <div class="d-flex">
                    <div class="search-bar w-100">

                        <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                        <input type="text" data-size="14" placeholder="Buscar un contrato..." v-model="searchOrderText"
                               v-on:keyup="debouncedFetchAllOrders" @blur="saveSearchText" :disabled="!(filtersObtained.agents && filtersObtained.agents.length > 0)">
                    </div>

                    <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>
                </div>
            </div>

            <!-- Comisiones -->
            <div class="d-flex column mb-10 mt-10 py-5 px-10 round" data-round="10" data-bg="gris">
                <div class="text d-flex align-center" data-size="15" data-weight="500">
                    <div class="icon mr-10">
                        <i class="far fa-file-lines"></i>
                    </div>
                    <p class="mr-5">Contratos: </p>
                    <span data-weight="600" data-size="17">{{ totalOrders }}</span>
                </div>
                <div class="text d-flex align-center" data-size="15" data-weight="500">
                    <div class="icon mr-10">
                        <i class="far fa-lightbulb"></i>
                    </div>
                    <p class="mr-5">Cons. Total: </p>
                    <span data-weight="600" data-size="17">{{ totalConsumption }}</span>
                </div>
                <div class="text d-flex align-center" data-size="15" data-weight="500">
                    <div class="icon mr-10">
                        <i class="far fa-money-bill"></i>
                    </div>
                    <p class="mr-5">Com. Agente: </p>
                    <span data-weight="600" data-size="17">{{ Math.round(summaryData.agentCommission ??
                        0).toLocaleString("es-ES") }}
                    </span>
                    <span :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                </div>
                <div class="text d-flex align-center" data-size="15" data-weight="500" v-if="canManage('contracts.viewCommissions')">
                    <div class="icon mr-10">
                        <i class="far fa-money-bill"></i>
                    </div>
                    <p class="mr-5">Com. {{ basicData.enterprise.name }}: </p>
                    <span data-weight="600" data-size="17">{{ Math.round(summaryData.subdomainCommission ??
                        0).toLocaleString("es-ES") }}</span>
                    <span :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>

                </div>
                <div class="text d-flex align-center" data-size="15" data-weight="500" v-if="canManage('contracts.manageCommissions')">
                    <div class="icon mr-10">
                        <i class="far fa-money-bill"></i>
                    </div>
                    <p class="mr-5">Rentabilidad: </p>
                    <span data-weight="600" data-size="17">{{ profitability }} €</span>
                </div>
                <div class="text d-flex align-center" data-size="15" data-weight="500" v-if="canManage('contracts.manageCommissions')">
                    <div class="icon mr-10">
                        <i class="far fa-chart-line"></i>
                    </div>
                    <p class="mr-5">Rentabilidad %: </p>
                    <span data-weight="600" data-size="17">{{ profitabilityPercent }} %</span>
                </div>
            </div>

            <!--Paginación-->
            <div class="d-grid my-10" data-column="2" data-layout="auto1" v-if="orders && orders.length > 0">

                <div class="d-flex justify-center my-auto" data-color="principal">
                    <div class="left pointer my-auto" v-bind:class="{ 'opacity-5': currentPage === 1 }"
                         v-on:click="changePage(-1)"><i class="far fa-chevron-left"></i></div>

                    <!--Info página-->
                    <div class="cont mx-10 my-auto" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages }}</div>

                    <div class="right pointer my-auto" v-bind:class="{ 'opacity-5': currentPage === totalPages }"
                         v-on:click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></div>
                </div>

                <div class="my-auto ml-auto d-flex">

                    <p class="text my-auto mr-10" data-size="13">Por página: </p>

                    <div class="select-content my-auto">
                        <!--Usuario  a liquidar-->
                        <div class="form my-auto">
                            <div class="form-group">
                                <div class="input-group">
                                    <select v-model="perPage" v-on:change="changePageSize">
                                        <option v-for="perPage in perPageOptions">{{ perPage }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!--Filtros aplicados-->
            <div class="d-flex column mobile-filters-applied my-10"
                 v-if="(!areAllAgentsActives && filtersFiltered.agents.length > 0) || (!areAllStatusesActives && filtersFiltered.statuses.length > 0) || (filtersObtained.dates.start || filtersObtained.dates.end) || (filtersObtained.activationDates.start || filtersObtained.activationDates.end) || (!areAllProductsTypesActives && filtersFiltered.productTypes.length > 0) || (!areAllFeesActives && filtersFiltered.fees.length > 0) || (!areAllMarketersActives && filtersFiltered.marketers.length > 0) || (!areAllProductsMarketerActives && filtersFiltered.products.length > 0) || filters.radio.view.checked !== 0 || filters.radio.withoutCommision.checked === 1 || filters.radio.otherSubdomains.checked !== 0 || filtersObtained.renewalPendingDates.start || filtersObtained.renewalPendingDates.end">
                <div class="d-flex align-center justify-between">
                    <div class="d-flex align-center">
                        <i class="fa fas fa-lightbulb mr-10" data-color="rojo"></i>
                        <p class="text" data-color="rojo" data-size="14" data-weight="600">Filtros aplicados</p>
                    </div>
                    <div class="custom-button" data-size="small" data-bg="rojo" data-mode="translucent" v-on:click="resetFilters">
                        Borrar filtros
                    </div>
                </div>
                <div class="mobile-filters-info-toggle mt-10 pointer" v-if="Object.keys(filtersApplied).length > 0"
                     v-on:click="isSeeingFiltersInfo = !isSeeingFiltersInfo">
                    <i class="far mr-5" :class="isSeeingFiltersInfo ? 'fa-chevron-up' : 'fa-chevron-down'" data-size="11"></i>
                    <span class="text" data-size="13" data-weight="600">{{ isSeeingFiltersInfo ? 'Ocultar detalle' : 'Ver detalle filtros' }}</span>
                </div>
                <div class="mobile-filters-info mt-5" v-if="isSeeingFiltersInfo && Object.keys(filtersApplied).length > 0">
                    <div class="filter-detail-item" v-for="[filterName, filter] in Object.entries(filtersApplied)">
                        <p class="filter-detail-label" data-size="12" data-weight="700">{{ filterName }}</p>
                        <div class="filter-detail-values">
                            <span class="filter-chip" v-for="val in filter.split(', ')" :key="val">{{ val }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!--Filtros-->
            <div class="d-flex column my-20">

                <p class="text" data-size="13" data-weight="600" v-on:click="isSeeingFilters = !isSeeingFilters">{{
                        isSeeingFilters ? 'Ocultar filtros' : 'Mostrar filtros' }}</p>


                <div v-if="isSeeingFilters">

                    <div class="arrow-border arrow-top my-10" data-position="left"></div>


                    <!--Agentes-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Agentes</div>

                        <div v-if="filtersObtained.agents && filtersObtained.agents.length > 0" class="custom-select" >

                            <div class="ml-10" data-size="13" data-color="azul">{{ getAgentFilterTitle }}<i
                                class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div class="d-flex align-center"
                                     v-if="filtersFiltered.agents && filtersFiltered.agents > 0">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('agents')"
                                         v-bind:class="{ 'selected': areAllAgentsActives }"></div>

                                    <div class="text">Todos</div>

                                </div>

                                <div class="d-flex align-center">
                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('agents')"
                                         v-bind:class="{ 'selected': areAllAgentsActives }"></div>

                                    <div class="text" data-size="13">Todos</div>
                                </div>


                                <div v-for="agent in filtersFiltered.agents" class="d-flex align-center">
                                    <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(agent, 'agent')"
                                         v-bind:class="{ 'selected': agent.active }"></div>

                                    <div class="text" data-size="13">{{ agent.name }}</div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 agentes</div>
                    </div>

                    <!--Estados-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Estados</div>

                        <div class="custom-select"
                             v-if="filtersFiltered.statuses && filtersFiltered.statuses.length > 0">

                            <div class="ml-10" data-size="13" data-color="azul">{{ getStatusFilterTitle }}<i
                                class="fas fa-chevron-down ml-10"></i></div>


                            <div class="select-content">
                                <div class="d-flex align-center"
                                     v-if="filtersFiltered.statuses && filtersFiltered.statuses.length > 0">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('statuses')"
                                         v-bind:class="{ 'selected': areAllStatusesActives }"></div>

                                    <div class="text" data-size="13">Todos</div>

                                </div>
                                <div v-for="status in filtersFiltered.statuses" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(status, 'status')"
                                         v-bind:class="{ 'selected': status.active }"></div>

                                    <div class="text" data-size="13">{{ status.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 estados</div>
                    </div>

                    <!--fecha creación-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Fec. traspaso</div>


                        <div class="custom-select no-hover" v-on:click.stop="seeFilters('dates')"
                             v-bind:class="{ 'seeing': isSeeingFiltersPc.dates }">

                            <div class="ml-10" data-color="azul" data-size="13">{{ getPrettyDatesFilters }}<i
                                class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content form" style="width: 300px">


                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Inicial</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="filtersObtained.dates.start"
                                               v-on:change="setDate('dates.start')" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer"
                                         v-on:click.stop="deleteFilter('dates.start')">
                                        <i class="fas fa-x"></i>
                                    </div>

                                </div>

                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Final</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="filtersObtained.dates.end"
                                               v-on:change="setDate('dates.end')" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer"
                                         v-on:click.stop="deleteFilter('dates.start')">
                                        <i class="fas fa-x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--Fec. activación-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Fec. activación</div>

                        <div class="custom-select no-hover" v-on:click.stop="seeFilters('activationDates')"
                             v-bind:class="{ 'seeing': isSeeingFiltersPc.activationDate }">

                            <div class="ml-10" data-color="azul" data-size="13">{{ getPrettyActivationDatesFilters }}<i
                                class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content form" style="width: 300px">

                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Inicial</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="filtersObtained.activationDates.start"
                                               v-on:change="setDate('activationDates.start')" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer"
                                         v-on:click.stop="deleteFilter('activationDates.start')">
                                        <i class="fas fa-x"></i>
                                    </div>

                                </div>

                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Final</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="filtersObtained.activationDates.end"
                                               v-on:change="setDate('activationDates.end')" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer"
                                         v-on:click.stop="deleteFilter('activationDates.end')">
                                        <i class="fas fa-x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!--Fec. baja-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Fec. baja</div>

                        <div class="custom-select no-hover" v-on:click.stop="seeFilters('activationDates')"
                             v-bind:class="{ 'seeing': isSeeingFiltersPc.lowDate }">

                            <div class="ml-10" data-color="azul" data-size="13">{{ getPrettyLowDatesFilters }}
                                <i class="fas fa-chevron-down ml-10"></i>
                            </div>

                            <div class="select-content form" style="width: 300px">

                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Inicial</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="filtersObtained.lowDates.start"
                                               v-on:change="setDate('lowDates.start')" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer"
                                         v-on:click.stop="deleteFilter('lowDates.start')">
                                        <i class="fas fa-x"></i>
                                    </div>

                                </div>

                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Final</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="filtersObtained.lowDates.end"
                                               v-on:change="setDate('lowDates.end')" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer"
                                         v-on:click.stop="deleteFilter('lowDates.end')">
                                        <i class="fas fa-x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--Tipos de producto-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Tipos de prod.</div>

                        <div class="custom-select"
                             v-if="filtersObtained.productTypes && filtersObtained.productTypes.length > 0">

                            <div class="ml-10" data-size="13" data-color="azul">{{ getProductTypeFilterTitle }}<i
                                class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div class="d-flex align-center"
                                     v-if="filtersFiltered.productTypes && filtersFiltered.productTypes.length > 0">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('productTypes')"
                                         v-bind:class="{ 'selected': areAllProductsTypesActives }"></div>

                                    <div class="text" data-size="13">Todos</div>

                                </div>
                                <div v-for="productType in filtersFiltered.productTypes" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10"
                                         v-on:click="toggleVisibility(productType, 'productTypes')"
                                         v-bind:class="{ 'selected': productType.active }"></div>

                                    <div class="text" data-size="13">{{ productType.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 productos</div>
                    </div>

                    <!--Tarifa-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Tarifa</div>

                        <div class="custom-select"
                             v-if="filtersObtained.productTypes && filtersObtained.productTypes.length > 0">

                            <div class="ml-10" data-size="13" data-color="azul">{{ getFeeFilterTitle }}<i
                                class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div class="d-flex align-center"
                                     v-if="filtersFiltered.fees && filtersFiltered.fees.length > 0">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('fees')"
                                         v-bind:class="{ 'selected': areAllFeesActives }"></div>

                                    <div class="text" data-size="13">Todos</div>

                                </div>
                                <div v-for="fee in filtersFiltered.fees" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(fee, 'fee')"
                                         v-bind:class="{ 'selected': fee.active }"></div>

                                    <div class="text" data-size="13">{{ fee.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 tarifas</div>
                    </div>

                    <!--Comercializadora-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Comercializad.</div>

                        <div class="custom-select"
                             v-if="filtersObtained.marketers && filtersObtained.marketers.length > 0">

                            <div class="ml-10" data-size="13" data-color="azul">{{ getMarketerFilterTitle }}<i
                                class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div class="d-flex align-center"
                                     v-if="filtersFiltered.marketers && filtersFiltered.marketers.length > 0">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('marketers')"
                                         v-bind:class="{ 'selected': areAllMarketersActives }"></div>

                                    <div class="text" data-size="13">Todos</div>

                                </div>
                                <div v-for="marketer in filtersFiltered.marketers" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10"
                                         v-on:click="toggleVisibility(marketer, 'marketer')"
                                         v-bind:class="{ 'selected': marketer.active }"></div>

                                    <div class="text" data-size="13">{{ marketer.title === '' ? 'Sin comercializadora' :
                                        marketer.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 comerc.</div>
                    </div>

                    <!--Productos comercializadoras-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Productos comerc.</div>

                        <div class="custom-select"
                             v-if="filtersObtained.products && filtersObtained.products.length > 0">

                            <div class="ml-10" data-size="13" data-color="azul">{{ getProductMarketerFilterTitle }}<i
                                class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div class="d-flex align-center"
                                     v-if="filtersFiltered.products && filtersFiltered.products.length > 0">

                                    <div class="custom-checkbox mr-10"
                                         v-on:click="toggleAllVisibility('productsMarketer')"
                                         v-bind:class="{ 'selected': areAllProductsMarketerActives }"></div>

                                    <div class="text" data-size="13">Todos</div>

                                </div>
                                <div v-for="productType in filtersFiltered.products" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10"
                                         v-on:click="toggleVisibility(productType, 'product')"
                                         v-bind:class="{ 'selected': productType.active }"></div>

                                    <div class="text" data-size="13">{{ productType.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 productos</div>
                    </div>


                    <!--Comisionado, no comisionado o todos-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Vista</div>

                        <div class="custom-select">

                            <div class="ml-10" data-size="13" data-color="azul">{{ viewTypeSelected }} <i
                                class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div v-for="viewType in filters.radio.view.data" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="selectViewType(viewType.value)"
                                         v-bind:class="{ 'selected': viewType.value === filters.radio.view.checked }">
                                    </div>

                                    <div class="text" data-size="13">{{ viewType.title }}</div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!--Comisionado, no comisionado o todos-->
                    <div class="d-flex justify-between my-10" v-if="canManage('contracts.manageCommissions')">
                        <div class="text" data-size="13" data-weight="600">Sin comisión</div>

                        <div class="custom-select">

                            <div class="ml-10" data-size="13" data-color="azul">{{ withoutCommisionTypeSelected }} <i
                                class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div v-for="viewType in filters.radio.withoutCommision.data"
                                     class="d-flex align-center">

                                    <div class="custom-checkbox mr-10"
                                         v-on:click="selectWithoutCommisionType(viewType.value)"
                                         v-bind:class="{ 'selected': viewType.value === filters.radio.withoutCommision.checked }">
                                    </div>

                                    <div class="text">{{ viewType.title }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--Ordenar-->
                    <!--<div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Ordenar</div>

                        <div class="custom-select">

                            <div class="ml-10" data-size="13" data-color="azul">{{ orderTypeSelected }} <i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div v-for="orderType in filters.radio.sortBy.data" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="selectOrderType(orderType)" v-bind:class="{ 'selected': orderType.value === filters.radio.sortBy.checked }"></div>

                                    <div class="text" data-size="13">{{ orderType.title }}</div>
                                </div>
                            </div>
                        </div>
                    </div>-->

                    <div class="separator mt-10"></div>
                </div>
            </div>


            <!--Cargando-->
            <div class="loading-indicator" v-if="isLoading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <p class="text" data-size="14">Cargando contratos...</p>
            </div>

            <!--Listado de contratos-->
            <div v-if="!isLoading">

                <div class="d-flex column">
                    <div v-for="(order, orderKey) in orders" class="my-5">

                        <!--Card-->
                        <div class="d-flex align-center pointer" v-on:click="seeOrderInfo(order)">

                            <div class="mobile-order-row text" data-weight="600">
                                <!--Id-->
                                <span class="mobile-order-id opacity-5" v-if="basicData?.userSubdomain?.settings?.contractsIds === true">
                                    {{ order.identifier ? order.identifier : '-' }}
                                </span>

                                <!--Icono verificación-->
                                <i
                                    v-if="order.verifications && order.verifications.length > 0"
                                    class="mobile-order-icon fa-solid fa-lightbulb"
                                    data-color="amarillo"
                                ></i>

                                <!--Nombre-->
                                <span class="mobile-order-name">
                                    {{ order.name }}
                                </span>

                                <!--Logo comercializadora-->
                                <div class="mobile-order-logo-box">
                                    <img
                                        v-if="getMarketerLogo(order.marketer)"
                                        :src="getMarketerLogo(order.marketer)"
                                        :alt="order.marketer"
                                        class="order-marketer-logo"
                                    />
                                </div>
                            </div>

                            <div class="deploy-btn ml-10" data-round="15"
                                 v-bind:class="{ 'selected': orderSelectedToSee && (((typeof orderSelectedToSee._id === 'string') ? orderSelectedToSee._id : orderSelectedToSee._id.$oid) === ((typeof order._id === 'string') ? order._id : order._id.$oid)) }">
                                <i class="fa-solid"
                                   v-bind:class="{ 'fa-chevron-down': !(orderSelectedToSee && (((typeof orderSelectedToSee._id === 'string') ? orderSelectedToSee._id : orderSelectedToSee._id.$oid) === ((typeof order._id === 'string') ? order._id : order._id.$oid))), 'fa-chevron-up': (orderSelectedToSee && (((typeof orderSelectedToSee._id === 'string') ? orderSelectedToSee._id : orderSelectedToSee._id.$oid) === ((typeof order._id === 'string') ? order._id : order._id.$oid))) }"></i>
                            </div>
                        </div>

                        <!--Info card-->
                        <div class="d-flex column"
                             v-if="orderSelectedToSee && (((typeof orderSelectedToSee._id === 'string') ? orderSelectedToSee._id : orderSelectedToSee._id.$oid) === ((typeof order._id === 'string') ? order._id : order._id.$oid))">

                            <!--Info del contrato-->
                            <div class="my-10">
                                <!--Título-->
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Título</div>
                                    <div class="text" data-size="13">{{ order.name }}</div>
                                </div>

                                <!--Agente-->
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Agente</div>
                                    <div class="text" data-size="13">{{ order.owner }}</div>
                                </div>

                                <!--NIF/CIF-->
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">NIF/CIF</div>
                                    <div class="text" data-size="13">{{ order.accountCIF }}</div>
                                </div>

                                <!--Tarifa-->
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Tarifa</div>
                                    <div class="text" data-size="13">{{ order.fee }}</div>
                                </div>

                                <!--Producto-->
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Producto</div>
                                    <div class="text" data-size="13">{{ order.product + order.marketer }}</div>
                                </div>

                                <!--CUPS-->
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">CUPS</div>
                                    <div class="text" data-size="13">{{ order.CUPS }}</div>
                                </div>

                                <!--Estado-->
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Estado</div>
                                    <div class="custom-button w-fit-content" data-size="small" data-color="principal" :data-mode="!isHex(getStatus(order).color) ? 'translucent' : null"
                                         :data-bg="!isHex(getStatus(order).color) ? getStatus(order).color : null"
                                         :style="isHex(getStatus(order).color) ? {
                                        backgroundColor: hexToRgba(getStatus(order).color, 0.1),
                                        border: `1px solid ${getStatus(order).color}`
                                        } : {}"><p class="w-70-px-max ellipsis">{{ getStatus(order).title }}</p></div>
                                </div>

                                <!--Ult. estado-->
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Fec. activación</div>
                                    <div class="text" data-size="13">{{ getActivationDate(order) }}</div>
                                </div>

                                <!--Ult. actualización-->
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Ult. actualización</div>
                                    <div class="text" data-size="13">{{ getPrettyDateTransfer(order.lastUpdate) }}</div>
                                </div>
                            </div>

                            <!--Botones-->
                            <div class="d-flex column" data-gap="8">

                                <div v-on:click="goToContract(order)"
                                     class="custom-button w-100" data-bg="principal" data-mode="outline"
                                     data-align="center" data-size="small" data-weight="700"><i class="fas fa-eye mr-5"></i> Ver contrato
                                </div>

                                <div v-on:click="deleteOrder(order)" v-if="!isReadOnly" class="custom-button w-100" data-bg="rojo" data-mode="outline" data-align="center" data-size="small" data-weight="700"><i class="fa-regular fa-trash mr-5"></i> Eliminar</div>
                            </div>
                        </div>

                        <div class="separator my-10" v-if="orderKey < filteredOrders.length - 1"></div>
                    </div>
                </div>


                <div class="mt-20">

                    <div class="d-grid" data-column="2" data-layout="auto1">

                        <div class="d-flex justify-center my-auto" data-color="principal">
                            <div class="left pointer my-auto" v-bind:class="{ 'opacity-5': currentPage === 1 }"
                                 v-on:click="changePage(-1)"><i class="fa-solid fa-chevron-left"></i></div>

                            <!--Info página-->
                            <div class="cont mx-10 my-auto" data-size="13" data-weight="600">{{ currentPage }} DE {{
                                    totalPages }}</div>

                            <div class="right pointer my-auto"
                                 v-bind:class="{ 'opacity-5': currentPage === totalPages }" v-on:click="changePage(1)">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </div>

                        <div class="my-auto ml-auto d-flex">

                            <p class="text my-auto mr-10" data-size="13">Por página: </p>

                            <div class="select-content my-auto">
                                <!--Usuario  a liquidar-->
                                <div class="form my-auto">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <select v-model="perPage" v-on:change="changePageSize">
                                                <option v-for="perPage in perPageOptions">{{ perPage }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!--si no hay-->
                <div v-if="!isLoading && orders && orders.length === 0" class="text opacity-5" data-align="center">¡No hay ningún contrato!</div>
            </div>
        </div>


        <!--Estilo de pc-->
        <div class="desktop-item">

            <!--Header-->
            <div class="d-flex justify-between align-center">

                <!--Título-->
                <div class="d-flex">
                    <div class="text" data-size="30" data-weight="700">{{ $route.meta.title }}</div>

                    <!--Paginación-->
                    <div class="d-grid" data-column="2" v-if="orders && orders.length > 0">

                        <div class="d-flex justify-center my-auto" data-color="principal">
                            <div class="left pointer" v-bind:class="{ 'opacity-5': currentPage === 1 }"
                                 v-on:click="changePage(-1)"><i class="fa-solid fa-chevron-left"></i></div>


                            <!--Info página-->
                            <div class="cont mx-10" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages
                                }}</div>


                            <div class="right pointer" v-bind:class="{ 'opacity-5': currentPage === totalPages }"
                                 v-on:click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></div>
                        </div>

                        <div class="my-auto ml-auto d-flex">

                            <p class="text my-auto mr-15">Por página: </p>

                            <div class="select-content my-auto">
                                <!--Usuario  a liquidar-->
                                <div class="form my-auto">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <select v-model="perPage" v-on:change="changePageSize">
                                                <option v-for="perPage in perPageOptions">{{ perPage }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Botones-->
                <div class="d-flex" v-if="!isReadOnly" data-gap="15">
                    <!-- Mass Load + Export Dropdown -->
                    <div class="d-flex">
                        <div class="custom-select no-hover" @click.stop="isSeeingMassiveLoad = true"
                             :class="{ seeing: isSeeingMassiveLoad }">
                            <div class="custom-button" data-size="regular" data-bg="principal">
                                <i class="far fa-plus"></i>
                            </div>

                            <div v-if="isSeeingMassiveLoad" class="select-content form w-260-px">
                                <!--Importar excel-->
                                <div class="d-flex mt-5" v-if="canManage('contracts.massive')">
                                    <p class="text" @click="$refs.inputExcel.click()">
                                        <i class="fa-solid fa-file-excel ml-4 mr-14"></i>
                                        Carga masiva
                                    </p>
                                    <input type="file" ref="inputExcel" style="display: none" accept=".xls, .xlsx, .csv"
                                           @change="pickupDumpFile" />
                                </div>

                                <!--Descargar excel-->
                                <a v-if="canManage('contracts.massive')" class="text" :href="'/assets/templates/' + (basicData.userLogged._id === '683d658761231bd1080b4802' ? 'orders_doive.xlsx' : 'orders.xlsx')" download="Plantilla contratos">
                                    <i class="fas fa-file-arrow-down ml-4 mr-10"></i>
                                    Descargar plantilla
                                </a>

                                <!--Sacar a excel-->
                                <p class="text mt-3" @click="loadAndExportAll" style="cursor: pointer;">
                                    <i class="fas fa-file-export ml-4 mr-10"></i>
                                    Exportar a Excel
                                </p>

                                <!--Enviar correo masivo a usuarios seleccionado-->
                                <p class="text mt-3" @click="getEmailsToMassive" style="cursor: pointer;">
                                    <i class="fas fa-paper-plane-top ml-4 mr-10"></i>
                                    Enviar correo masivo
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Create Order Dropdown -->
                    <div v-if="canManage('contracts.create')"class="custom-select no-hover"
                         @click.stop="isSeeingCreateOrder = true; isSeeingMassiveLoad = false"
                         :class="{ seeing: isSeeingCreateOrder }">
                        <div class="custom-button" data-size="regular" data-bg="principal">
                            Añade un contrato
                        </div>

                        <!--Opción entre crear nuevo y meter en cuenta existente-->
                        <div v-if="isSeeingCreateOrder" class="select-content form">

                            <!--Opciones-->
                            <div class="text" v-if="!typeOfCreate">
                                <p v-on:click.stop="typeOfCreate = 'account'"><i class="far fa-buildings mr-10"></i>Creación en cuenta</p>

                                <p v-if="showDirect"
                                v-on:click.stop="typeOfCreate = null; isSeeingCreateOrder = false; isSeeingOrderComponent = true"><i class="far fa-file-lines mr-10"></i>Creación directa</p>

                                <p v-on:click.stop="openInvoiceFilePicker">
                                    <i class="fas fa-file-invoice mr-10"></i>Creación por factura
                                </p>
                                <input
                                    type="file"
                                    ref="inputInvoiceFile"
                                    style="display: none"
                                    multiple
                                    accept=".pdf"
                                    @change="pickupInvoiceFile"
                                />
                            </div>

                            <!--Crear en cuenta existente-->
                            <div v-if="typeOfCreate === 'account'">
                                <div class="form-group">
                                    <div class="input-group">
                                        <input data-size="12" v-model="searchFilters.account" type="text"
                                               placeholder="Busca tu cuenta..." />
                                    </div>
                                </div>

                                <div v-for="account in filtersFiltered.accounts" :key="account._id"
                                     class="d-flex align-center pointer">
                                    <div class="text ellipsis" data-size="13"
                                         @click.stop="selectAccToCreateOrder(account._id)">
                                        <i class="fas fa-buildings mr-10"></i>{{ account.name }}
                                    </div>
                                </div>

                                <div v-if="!accountsRelated || accountsRelated.length === 0" class="text opacity-5"
                                     data-size="10">
                                    ¡Crea primero una cuenta!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>

            <!-- Resumen -->
            <div class="d-flex justify-between f-wrap mt-10" data-gap="20">
                <!-- Contratos -->
                <div class="dashboard-card">

                    <!--icono-->
                    <div class="icon">
                        <i class="far fa-file-lines"></i>
                    </div>


                    <div class="info">

                        <p class="title">Contratos</p>

                        <p class="value">
                            {{ totalOrders }}
                        </p>
                    </div>

                </div>
                <!-- Consumo total -->
                <div class="dashboard-card">

                    <!--icono-->
                    <div class="icon">
                        <i class="far fa-lightbulb"></i>
                    </div>


                    <div class="info">

                        <p class="title">Consumo total</p>

                        <p class="value">
                            {{ totalConsumption }}
                        </p>
                    </div>

                </div>
                <!-- Comisión -->
                <div class="dashboard-card" v-if="!canManage('users.admiWhiHier')">
                    <div class="icon">
                        <i class="far fa-money-bill"></i>
                    </div>

                    <div
                        class="info"
                        :class="{ half: canManage('contracts.manageCommissions') }"
                    >
                        <!-- SIEMPRE -->
                        <div>
                            <p class="title">Comisión agente</p>
                            <p class="value">
                                {{ Math.round(summaryData.agentCommission ?? 0).toLocaleString("es-ES") }}
                                <span :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                            </p>
                        </div>

                        <!-- SOLO CON PERMISO -->
                        <div v-if="canManage('contracts.manageCommissions')">
                            <p class="title">Comisión {{ basicData.enterprise.name }}</p>
                            <p class="value">
                                {{ Math.round(summaryData.subdomainCommission ?? 0).toLocaleString("es-ES") }}
                                <span :class="{'points': basicData && basicData.userLogged.commInPoints}">{{ basicData && !basicData.userLogged.commInPoints ? '€' : 'pts.' }}</span>
                            </p>
                        </div>
                    </div>
                </div>


                <!-- Rentabilidad -->
                <div class="dashboard-card" v-if="canManage('contracts.manageCommissions')">

                    <!--icono-->
                    <div class="icon">
                        <i class="far fa-money-bill"></i>
                    </div>


                    <div class="info half">

                        <p class="title">Rentabilidad</p>

                        <p class="value">
                            {{ profitability }} €
                        </p>

                        <p class="title">Rentabilidad %</p>

                        <p class="value">
                            {{ profitabilityPercent }} %
                        </p>
                    </div>

                </div>
            </div>

            <!--Barra de busqueda-->
            <div class="mt-30 select-line" :class="{ 'fidelity-search-layout': isFidelitySubdomain }">

                <!--Agenda y archivado-->
                <!--<div class="text pointer two-divs" data-align="center" v-bind:class="{'selected': partSeeing === 0}" v-on:click="changePartSeeing(0)">En tramitación</div>

                <div class="text pointer two-divs" data-align="center" v-bind:class="{'selected': partSeeing === 1}" v-on:click="changePartSeeing(1)">Comisionados</div>-->

                <div class="d-flex filters-on relPos"
                     v-if="(!areAllAgentsActives && filtersFiltered.agents.length > 0) || (!areAllStatusesActives && filtersFiltered.statuses.length > 0) || /*fec. creación*/ (filtersObtained.dates.start || filtersObtained.dates.end) || /*fec. activación*/ (filtersObtained.activationDates.start || filtersObtained.activationDates.end) || (!areAllProductsTypesActives && filtersFiltered.productTypes.length > 0) || (!areAllFeesActives && filtersFiltered.fees.length > 0) || (!areAllMarketersActives && filtersFiltered.marketers.length > 0) || (!areAllProductsMarketerActives && filtersFiltered.products.length > 0) || filters.radio.view.checked !== 0 || /*filtro sin comision*/filters.radio.withoutCommision.checked === 1 || /*filtro subdominio*/filters.radio.otherSubdomains.checked !== 0 || filtersObtained.renewalPendingDates.start || filtersObtained.renewalPendingDates.end">
                    <i class="fa fas fa-lightbulb my-auto" data-color="rojo"></i>

                    <p class="my-auto mx-15" data-color="rojo">Filtros aplicados</p>

                    <div class="custom-button " data-size="small" data-bg="rojo" data-mode="translucent" v-on:click="resetFilters">
                        Borrar filtros</div>

                    <i class="far fa-circle-info my-auto ml-15" @mouseover="isSeeingFiltersInfo = true"
                       @mouseleave="isSeeingFiltersInfo = false" data-color="principal"></i>
                    <div v-show="isSeeingFiltersInfo" class="infoFiltersContent">
                        <p class="text opacity-7 mb-10" data-weight="600" data-size="14">Información filtros</p>
                        <template v-for="[filterName, filter] in Object.entries(filtersApplied)">
                            <div class="text">
                                <p class="line-clamp-2">
                                    <span class="opacity-7 mr-10" data-size="14">{{ filterName }}:</span>
                                    {{ filter }}
                                </p>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="before-search">
                    <div class="custom-button " data-size="small" data-bg="azul" data-mode="translucent"
                         v-on:click="isSeeingFiltersBox = !isSeeingFiltersBox">{{ isSeeingFiltersBox ? 'Ocultar' :
                        'Mostrar' }}
                        filtros</div>
                </div>

                <!--Barra de busqueda-->
                <div class="search-div d-flex" :class="{ 'with-search-field': isFidelitySubdomain }">

                    <div class="search-field-select mr-10" v-if="isFidelitySubdomain">
                        <select class="fidelity-search-select" v-model="searchOrderField" v-on:change="changeSearchField">
                            <option v-for="option in searchOrderFieldOptions" :key="option.value"
                                    :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div class="search-bar w-100">

                        <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>


                        <input type="text" placeholder="Buscar un contrato..." v-model="searchOrderText"
                               v-on:keyup="debouncedFetchAllOrders" :disabled="!(filtersObtained.agents && filtersObtained.agents.length > 0)">
                    </div>

                    <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>

                </div>

            </div>


                            <div class="fidelity-contracts-shell">
                                <div :class="{'fidelity-contracts-scroll': basicData.userSubdomain && basicData.userSubdomain._id === '6909faa9232c09035a03f3b2'}">

                            <!--Header-->
                            <div v-if="basicData.userSubdomain && basicData.userSubdomain._id === '6909faa9232c09035a03f3b2'"
                 class="contact header-card contracts-grid fidelity-contracts-grid contracts-header-align mt-30"
                :class="{'with-id' : basicData.userSubdomain && basicData.userSubdomain.settings && basicData.userSubdomain.settings.contractsIds}"
                :style="basicData.userSubdomain && basicData.userSubdomain.settings && basicData.userSubdomain.settings.contractsIds
                    ? 'grid-template-columns: 70px 220px 150px 150px 150px 130px 130px 240px 230px 120px 120px 120px 120px 80px; align-items: center; gap: 8px;'
                    : 'grid-template-columns: 220px 150px 150px 150px 130px 130px 240px 230px 120px 120px 120px 120px 80px; align-items: center; gap: 8px;'">

                <div class="d-flex" data-color="principal" v-if="basicData.userSubdomain && basicData.userSubdomain.settings && basicData.userSubdomain.settings.contractsIds">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('id')">Id
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('id')"
                            v-bind:class="this.filters.radio.sortBy.checked === 26 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 27 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('title')">Título
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('title')"
                            v-bind:class="this.filters.radio.sortBy.checked === 0 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 1 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('superUsuario')">Superusuario
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('superUsuario')"
                            v-bind:class="this.filters.radio.sortBy.checked === 22 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 23 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('usuario')">Usuario
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('usuario')"
                            v-bind:class="this.filters.radio.sortBy.checked === 24 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 25 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('comercial')">Comercial
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('comercial')"
                            v-bind:class="this.filters.radio.sortBy.checked === 20 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 21 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>





                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('nif')">NIF/CIF
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('nif')"
                            v-bind:class="this.filters.radio.sortBy.checked === 4 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 5 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('fee')">Tarifa
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('fee')"
                            v-bind:class="this.filters.radio.sortBy.checked === 6 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 7 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('product')">Producto
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('product')"
                            v-bind:class="this.filters.radio.sortBy.checked === 8 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 9 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('cups')">CUPS
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('cups')"
                            v-bind:class="this.filters.radio.sortBy.checked === 10 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 11 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('status')">Estado
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('status')"
                            v-bind:class="this.filters.radio.sortBy.checked === 12 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 13 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('createdAt')">Fec. creación
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('createdAt')"
                            v-bind:class="this.filters.radio.sortBy.checked === 16 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 17 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex ellipsis" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('activationDate')">Fec. activación
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('activationDate')"
                            v-bind:class="this.filters.radio.sortBy.checked === 18 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 19 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                 <div class="d-flex ellipsis" data-color="principal">
                        <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                        v-on:click="selectNewOrderType('transferDate')">Fec. traspaso
                        </p>
                        <i class="fas my-auto pointer"
                        v-on:click="selectNewOrderType('transferDate')"
                        v-bind:class="this.filters.radio.sortBy.checked === 28 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 29 ? 'fa-sort-up' : 'fa-sort')"></i>
                  </div>

                  <div class="d-flex" data-color="principal"></div>

            </div>

            <!-- Para el resto de subdominios -->
            <div v-else
                class="contact header-card six-no-chck mt-30"
                :class="{'with-id' : basicData.userSubdomain && basicData.userSubdomain.settings && basicData.userSubdomain.settings.contractsIds}">

                <div class="d-flex" data-color="principal" v-if="basicData.userSubdomain && basicData.userSubdomain.settings && basicData.userSubdomain.settings.contractsIds">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600">Id</p>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('title')">Título
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('title')"
                            v-bind:class="this.filters.radio.sortBy.checked === 0 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 1 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('agent')">Agente
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('agent')"
                            v-bind:class="this.filters.radio.sortBy.checked === 2 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 3 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('nif')">NIF/CIF
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('nif')"
                            v-bind:class="this.filters.radio.sortBy.checked === 4 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 5 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('fee')">Tarifa
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('fee')"
                            v-bind:class="this.filters.radio.sortBy.checked === 6 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 7 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('product')">Producto
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('product')"
                            v-bind:class="this.filters.radio.sortBy.checked === 8 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 9 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('cups')">CUPS
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('cups')"
                            v-bind:class="this.filters.radio.sortBy.checked === 10 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 11 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('status')">Estado
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('status')"
                            v-bind:class="this.filters.radio.sortBy.checked === 12 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 13 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <!--<div class="d-flex" data-color="principal" v-if="basicData && basicData.userLogged && basicData.userLogged._id !== '683d658761231bd1080b4802'">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('lastStatusAt')">Fec. estado
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('lastStatusAt')"
                            v-bind:class="this.filters.radio.sortBy.checked === 14 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 15 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>-->

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('activationDate')">Fec. activación
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('activationDate')"
                            v-bind:class="this.filters.radio.sortBy.checked === 18 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 19 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex ellipsis" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600"
                    v-on:click="selectNewOrderType('lastUpdate')">Ult. actualización
                    </p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('lastUpdate')"
                            v-bind:class="this.filters.radio.sortBy.checked === 16 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 17 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

            </div>

            <div class="separator my-10"></div>

            <!--Contenido-->
            <div v-if="isLoading">
                <div class="d-flex column" v-for="i of 10">
                    <div class="contact six-no-chck pointer">
                        <div class="loading mx-10 h-20-px" v-for="i of 9"></div>
                    </div>
                    <div class="separator my-10"></div>
                </div>
            </div>

            <div v-else-if="orders && orders.length > 0">

                <div>
                    <order-card-component v-for="order in orders" :order="order" :ordersSelected="ordersSelected"
                                          :orderSelectedToSee="orderSelectedToSee" :isReadOnly="isReadOnly" :productTypes="productTypes"
                                          :isEditing="isEditing" :isInputsDisabled="isInputsDisabled" :basicData="basicData" @selectOrderToSee="selectOrderToSee"
                                          @openOrder="openOrderWindow" @deleteOrder="deleteOrder" @toggleSelectOrder="toggleSelectOrder"
                                          @activeEditing="activeEditing"></order-card-component>
                </div>

            </div>

            <div class="opacity-5" v-else data-align="center">¡No hay ningún contrato!</div>

                                </div>

                                <div class="d-grid fidelity-contracts-pagination" data-column="3" v-if="orders && orders.length > 0 && basicData.userSubdomain && basicData.userSubdomain._id === '6909faa9232c09035a03f3b2'">

                                    <div class="fidelity-scroll-hint">
                                        <i class="fa-solid fa-circle-info"></i>
                                        <span class="fidelity-scroll-tooltip">Pon el ratón sobre la tabla y mantén pulsado Shift mientras mueves la rueda del ratón para desplazar el scroll lateral.</span>
                                    </div>

                                    <div class="d-flex justify-center mt-20" data-color="principal">
                                        <div class="left pointer" v-bind:class="{ 'opacity-5': currentPage === 1 }"
                                             v-on:click="changePage(-1)"><i class="fa-solid fa-chevron-left"></i></div>

                                        <div class="cont mx-10" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages }}</div>

                                        <div class="right pointer" v-bind:class="{ 'opacity-5': currentPage === totalPages }"
                                             v-on:click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></div>
                                    </div>

                                    <div class="my-auto ml-auto d-flex">

                                        <p class="text my-auto mr-15">Por página: </p>

                                        <div class="select-content my-auto">
                                            <div class="form my-auto">
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <select v-model="perPage" v-on:change="changePageSize">
                                                            <option v-for="perPage in perPageOptions">{{ perPage }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>


            <!--Flotante para crear pedido-->
            <Teleport to=".boxBody">
                <div class="form">
                    <order-details-item-component v-if="isSeeingOrderComponent" :order="orderToModify"
                                                  :account="accountToAddOrder" :selectValues="selectValues" :basicData="basicData" :originalOrders="ordersAll"
                                                  canCreate="true" :isCreatingOrder="isCreatingOrder" :isEditingFromOther="isEditingOrder"
                                                  :isInputsDisabled="isReadOnly" @addElement="addSelectType" @delElement="delSelectType"
                                                  @selectElement="selectElement" @createOrder="createOrder"
                                                  @closeWindow="closeWindow" @activeEditing="activeEditing" @renewalOrder="renewalOrder"></order-details-item-component>
                </div>
            </Teleport>

            <!--Flotante filtros-->
            <div class="filters-box d-flex column justify-between" v-if="isSeeingFiltersBox">

                <!--Header-->
                <div class="d-flex justify-between">

                    <p class="text opacity-7" data-size="20" data-weight="600">Filtros</p>

                    <i class="fas fa-x text my-auto pointer" data-size="20" data-weight="600"
                       v-on:click="isSeeingFiltersBox = false"></i>
                </div>


                <div class="filters scroll-y">

                    <!--Cada uno de los filtros-->
                    <div class="mt-30 ml-20">

                        <!--Agentes-->
                        <div class="d-flex my-40">
                            <div class="text">Agentes:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('agent')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.agent }"
                                 v-if="filtersObtained.agents && filtersObtained.agents.length > 0">

                                <div class="ml-10" data-color="azul">{{ getAgentFilterTitle }}<i
                                    class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">

                                    <div class="form-group ">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.agent" type="text"
                                                   placeholder="Busca tu agente...">
                                        </div>
                                    </div>

                                    <!--marcar desmarcar todos-->
                                    <div class="d-flex align-center"
                                         v-if="filtersFiltered.agents && filtersFiltered.agents.length > 0">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('agents')"
                                             v-bind:class="{ 'selected': areAllAgentsActives }"></div>

                                        <div class="text">Todos</div>
                                    </div>

                                    <div v-for="agent in filtersFiltered.agents" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(agent, 'agent')"
                                             v-bind:class="{ 'selected': agent.active }"></div>

                                        <div class="text">{{ agent.name }}</div>

                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 agentes</div>
                        </div>


                        <!--Estados-->
                        <div class="d-flex my-40">
                            <div class="text">Estados:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('status')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.status }"
                                 v-if="filtersObtained.statuses && filtersObtained.statuses.length > 0">

                                <div class="ml-10" data-color="azul">{{ getStatusFilterTitle }}<i
                                    class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">

                                    <div class="form-group ">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.status" type="text"
                                                   placeholder="Busca tu estado...">
                                        </div>
                                    </div>

                                    <div class="d-flex align-center"
                                         v-if="filtersFiltered.statuses && filtersFiltered.statuses.length > 0">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('statuses')"
                                             v-bind:class="{ 'selected': areAllStatusesActives }"></div>

                                        <div class="text">Todos</div>
                                    </div>


                                    <div v-for="status in filtersFiltered.statuses" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10"
                                             v-on:click="toggleVisibility(status, 'status')"
                                             v-bind:class="{ 'selected': status.active }"></div>

                                        <div class="text">{{ status.title }}</div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 estados</div>
                        </div>

                        <!--fecha creación-->
                        <div class="d-flex my-40">
                            <div class="text">Fec. traspaso:</div>


                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('dates')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.dates }">

                                <div class="ml-10" data-color="azul">{{ getPrettyDatesFilters }}<i
                                    class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content center form">

                                    <div class="form-group d-flex">
                                        <p class="w-20 my-auto text">Inicial</p>

                                        <div class="input-group ml-10 w-70">
                                            <input data-size="12" v-model="filtersObtained.dates.start"
                                                   v-on:change="setDate('dates.start')" type="date">
                                        </div>

                                        <div class="my-auto mx-10 text pointer"
                                             v-on:click.stop="deleteFilter('dates.start')">
                                            <i class="fas fa-x"></i>
                                        </div>

                                    </div>

                                    <div class="form-group d-flex">
                                        <p class="w-20 my-auto text">Final</p>

                                        <div class="input-group ml-10 w-70">
                                            <input data-size="12" v-model="filtersObtained.dates.end"
                                                   v-on:change="setDate('dates.end')" type="date">
                                        </div>

                                        <div class="my-auto mx-10 text pointer"
                                             v-on:click.stop="deleteFilter('dates.end')">
                                            <i class="fas fa-x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>

                        <!--fecha activación-->
                        <div class="d-flex my-40">
                            <div class="text">Fec. activación:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('activationDates')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.activationDate }">

                                <div class="ml-10" data-color="azul">{{ getPrettyActivationDatesFilters }}<i
                                    class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content center form">

                                    <div class="form-group d-flex">
                                        <p class="w-20 my-auto text">Inicial</p>

                                        <div class="input-group ml-10 w-70">
                                            <input data-size="12" v-model="filtersObtained.activationDates.start"
                                                   v-on:change="setDate('activationDates.start')" type="date">
                                        </div>

                                        <div class="my-auto mx-10 text pointer"
                                             v-on:click.stop="deleteFilter('activationDates.start')">
                                            <i class="fas fa-x"></i>
                                        </div>

                                    </div>

                                    <div class="form-group d-flex">
                                        <p class="w-20 my-auto text">Final</p>

                                        <div class="input-group ml-10 w-70">
                                            <input data-size="12" v-model="filtersObtained.activationDates.end"
                                                   v-on:change="setDate('activationDates.end')" type="date">
                                        </div>

                                        <div class="my-auto mx-10 text pointer"
                                             v-on:click.stop="deleteFilter('activationDates.end')">
                                            <i class="fas fa-x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>

                        <div class="d-flex my-40">
                            <div class="text">Fec. pend. ren:</div>

                            <div class="custom-select no-hover"
                                @click.stop="seeFilters('renewalPendingDates')"
                                :class="{ 'seeing': isSeeingFiltersPc.renewalPendingDate }">

                                <div class="ml-10" data-color="azul" data-size="13">
                                {{ getPrettyRenewalPendingDatesFilters }}
                                <i class="fas fa-chevron-down ml-10"></i>
                                </div>

                                <div class="select-content center form">
                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Inicial</p>
                                    <div class="input-group ml-10 w-70">
                                    <input data-size="12"
                                            v-model="filtersObtained.renewalPendingDates.start"
                                            type="date"
                                            @change="() => { setDate('renewalPendingDates.start'); filtersApplied = true; }">
                                    </div>
                                    <div class="my-auto mx-10 text pointer"
                                        @click.stop="deleteFilter('renewalPendingDates.start')">
                                    <i class="fas fa-x"></i>
                                    </div>
                                </div>

                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Final</p>
                                    <div class="input-group ml-10 w-70">
                                    <input data-size="12"
                                            v-model="filtersObtained.renewalPendingDates.end"
                                            type="date"
                                    @change="() => { setDate('renewalPendingDates.end'); filtersApplied = true; }">
                                    </div>
                                    <div class="my-auto mx-10 text pointer"
                                        @click.stop="deleteFilter('renewalPendingDates.end')">
                                    <i class="fas fa-x"></i>
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>





                        <!--fecha baja-->
                        <div class="d-flex my-40">
                            <div class="text">Fec. baja:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('lowDates')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.lowDate }">

                                <div class="ml-10" data-color="azul">{{ getPrettyLowDatesFilters }}<i
                                    class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content center form">

                                    <div class="form-group d-flex">
                                        <p class="w-20 my-auto text">Inicial</p>

                                        <div class="input-group ml-10 w-70">
                                            <input data-size="12" v-model="filtersObtained.lowDates.start"
                                                   v-on:change="setDate('lowDates.start')" type="date">
                                        </div>

                                        <div class="my-auto mx-10 text pointer"
                                             v-on:click.stop="deleteFilter('lowDates.start')">
                                            <i class="fas fa-x"></i>
                                        </div>

                                    </div>

                                    <div class="form-group d-flex">
                                        <p class="w-20 my-auto text">Final</p>

                                        <div class="input-group ml-10 w-70">
                                            <input data-size="12" v-model="filtersObtained.lowDates.end" v-on:change="setDate('lowDates.end')" type="date">
                                        </div>

                                        <div class="my-auto mx-10 text pointer" v-on:click.stop="deleteFilter('lowDates.end')">
                                            <i class="fas fa-x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>

                        <!--Tipo de producto-->
                        <div class="d-flex my-40">
                            <div class="text">Tipo de producto:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('productType')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.productTypes }"
                                 v-if="filtersObtained.productTypes && filtersObtained.productTypes.length > 0">

                                <div class="ml-10" data-color="azul">{{ getProductTypeFilterTitle }}<i
                                    class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content center form">

                                    <div class="form-group">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.productType" type="text"
                                                   placeholder="Busca tu tipo de producto...">
                                        </div>
                                    </div>

                                    <div class="d-flex align-center"
                                         v-if="filtersFiltered.productTypes && filtersFiltered.productTypes.length > 0">

                                        <div class="custom-checkbox mr-10"
                                             v-on:click="toggleAllVisibility('productTypes')"
                                             v-bind:class="{ 'selected': areAllProductsTypesActives }"></div>

                                        <div class="text">Todos</div>
                                    </div>

                                    <div v-for="productType in filtersFiltered.productTypes"
                                         class="d-flex align-center">

                                        <div class="custom-checkbox mr-10"
                                             v-on:click="toggleVisibility(productType, 'productTypes')"
                                             v-bind:class="{ 'selected': productType.active }"></div>

                                        <div class="text">{{ productType.title }}</div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 productos</div>
                        </div>

                        <!--Tarifa-->
                        <div class="d-flex my-40">
                            <div class="text">Tarifa:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('fees')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.fees }"
                                 v-if="filtersFiltered.fees && filtersFiltered.fees.length > 0">

                                <div class="ml-10" data-color="azul">{{ getFeeFilterTitle }}<i
                                    class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">

                                    <div class="form-group">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.fee" type="text"
                                                   placeholder="Busca tu tarifa...">
                                        </div>
                                    </div>

                                    <div class="d-flex align-center"
                                         v-if="filtersFiltered.fees && filtersFiltered.fees.length > 0">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('fees')"
                                             v-bind:class="{ 'selected': areAllFeesActives }"></div>

                                        <div class="text">Todos</div>
                                    </div>

                                    <div v-for="fee in filtersFiltered.fees" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(fee, 'fees')"
                                             v-bind:class="{ 'selected': fee.active }"></div>

                                        <div class="text">{{ fee.title }}</div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 tarifas</div>
                        </div>

                        <!--Comercializadora-->
                        <div class="d-flex my-40">
                            <div class="text">Comercializad.:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('marketer')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.marketer }"
                                 v-if="filtersFiltered.marketers && filtersFiltered.marketers.length > 0">

                                <div class="ml-10" data-color="azul">{{ getMarketerFilterTitle }}<i
                                    class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content center form">

                                    <div class="form-group ">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.marketer" type="text"
                                                   placeholder="Busca tu comercializadora...">
                                        </div>
                                    </div>

                                    <div class="d-flex align-center"
                                         v-if="filtersFiltered.marketers && filtersFiltered.marketers.length > 0">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('marketers')"
                                             v-bind:class="{ 'selected': areAllMarketersActives }"></div>

                                        <div class="text">Todos</div>
                                    </div>

                                    <div v-for="marketer in filtersFiltered.marketers" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10"
                                             v-on:click="toggleVisibility(marketer, 'marketer')"
                                             v-bind:class="{ 'selected': marketer.active }"></div>

                                        <div class="text">{{ marketer.title }}</div>
                                        <!-- === '' ? 'Sin comercializadora' : marketer.title-->

                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 comercializad.</div>
                        </div>

                        <!--Productos comercializadora-->
                        <div class="d-flex my-40">
                            <div class="text">Productos comerc.:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('productMarketer')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.productMarketer }"
                                 v-if="filtersFiltered.products && filtersFiltered.products.length > 0">

                                <div class="ml-10" data-color="azul">{{ getProductMarketerFilterTitle }}<i
                                    class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content center form">

                                    <div class="form-group ">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.productMarketer" type="text"
                                                   placeholder="Busca tu prod. de comerc. ...">
                                        </div>
                                    </div>

                                    <div class="d-flex align-center"
                                         v-if="filtersFiltered.products && filtersFiltered.products.length > 0">

                                        <div class="custom-checkbox mr-10"
                                             v-on:click="toggleAllVisibility('productsMarketer')"
                                             v-bind:class="{ 'selected': areAllProductsMarketerActives }"></div>

                                        <div class="text">Todos</div>

                                    </div>
                                    <div v-for="productType in filtersFiltered.products" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10"
                                             v-on:click="toggleVisibility(productType, 'products')"
                                             v-bind:class="{ 'selected': productType.active }"></div>

                                        <div class="text">{{ productType.title }}</div>

                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 productos</div>
                        </div>


                        <!--Comisionado, no comisionado o todos-->
                        <div class="d-flex my-40">
                            <div class="text">Vista:</div>

                            <div class="custom-select  no-hover" v-on:click.stop="seeFilters('view')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.view }">

                                <div class="ml-10" data-color="azul">{{ viewTypeSelected }} <i
                                    class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">
                                    <div v-for="viewType in filters.radio.view.data" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="selectViewType(viewType.value)"
                                             v-bind:class="{ 'selected': viewType.value === filters.radio.view.checked }">
                                        </div>

                                        <div class="text">{{ viewType.title }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!--Contratos sin comisiones-->
                        <div class="d-flex my-40" v-if="canManage('contracts.manageCommissions')">
                            <div class="text">Sin comisión:</div>

                            <div class="custom-select  no-hover" v-on:click.stop="seeFilters('withoutCommision')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.withoutCommision }">

                                <div class="ml-10" data-color="azul">{{ withoutCommisionTypeSelected }} <i
                                    class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content center form">
                                    <div v-for="viewType in filters.radio.withoutCommision.data"
                                         class="d-flex align-center">

                                        <div class="custom-checkbox mr-10"
                                             v-on:click="selectWithoutCommisionType(viewType.value)"
                                             v-bind:class="{ 'selected': viewType.value === filters.radio.withoutCommision.checked }">
                                        </div>

                                        <div class="text">{{ viewType.title }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!--Solo otros subdominios-->
                        <div class="d-flex my-40" v-if="canManage('contracs.manageCommissions')">
                            <div class="text">Otros subdominios:</div>

                            <div class="custom-select  no-hover" v-on:click.stop="seeFilters('otherSubdomains')"
                                 v-bind:class="{ 'seeing': isSeeingFiltersPc.otherSubdomains }">

                                <div class="ml-10" data-color="azul">{{
                                        filters.radio.otherSubdomains.data[this.filters.radio.otherSubdomains.checked].title
                                    }} <i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content center form">
                                    <div v-for="subDomainOpt in filters.radio.otherSubdomains.data"
                                         class="d-flex align-center">

                                        <div class="custom-checkbox mr-10"
                                             v-on:click="selectSubdomainType(subDomainOpt.value)"
                                             v-bind:class="{ 'selected': subDomainOpt.value === filters.radio.otherSubdomains.checked }">
                                        </div>

                                        <div class="text">{{ subDomainOpt.title }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--Ordenamiento-->
                        <!--<div class="d-flex my-40">
                            <div class="text">Ordenar:</div>

                            <div class="custom-select  no-hover" v-on:click.stop="seeFilters('sort')" v-bind:class="{'seeing': isSeeingFiltersPc.sort}">

                                <div class="ml-10" data-color="azul">{{ orderTypeSelected }} <i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">
                                    <div v-for="orderType in filters.radio.sortBy.data" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-if="$cookies.get('filters')" v-on:click="selectOrderType(orderType)" v-bind:class="{ 'selected': orderType.value === $cookies.get('filters')['orders']['sortBy'] }"></div>

                                        <div class="text">{{ orderType.title }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>-->
                    </div>
                </div>


                <!--Botón borrar filtros-->
                <div class="d-flex justify-end">
                    <div class="custom-button before-search" data-size="small" data-bg="rojo" data-mode="translucent"
                         v-on:click="resetFilters">Borrar filtros</div>
                </div>

            </div>
        </div>
        <div class="loader-box" v-if="isLoadingMassiveLoad">
            <div class="loader"></div>
        </div>
    </div>
</template>

<script>
import ExcelJS from 'exceljs/dist/exceljs.bare.min.js';
import { saveAs } from 'file-saver';
export default {
    name: "DesktopComponent",
    props: ['basicData'],
    data() {
        return {
            ordersAll: [],
            partSeeing: 0,
            isLoading: true,
            isSeeingMassiveLoad: false,
            isLoadingMassiveLoad: false,
            isSeeingFilters: false,
            isSeeingFiltersInfo: false,
            isSeeingFiltersBox: false,
            isSeeingFiltersPc: {
                agent: false,
                status: false,
                marketer: false,
                productMarketer: false,
                productTypes: false,
                fees: false,
                dates: false,
                activationDate: false,
                lowDate: false,
                view: false,
                withoutCommision: false,
                otherSubdomains: false,
                sort: false,
                renewalPendingDate: false,

            },
            invoiceFile: null,
            isSeeingCreateOrder: false,
            typeOfCreate: null,
            isSeeingOrderComponent: false,
            orders: '',
            totalOrders: 0,
            summaryData: [],
            searchOrderText: '',
            searchOrderField: 'all',
            searchOrderFieldOptions: [
                { value: 'all', label: 'Todo' },
                { value: 'id', label: 'Id' },
                { value: 'title', label: 'Titulo' },
                { value: 'superusuario', label: 'Superusuario' },
                { value: 'usuario', label: 'Usuario' },
                { value: 'comercial', label: 'Comercial' },
                { value: 'cif', label: 'NIF/CIF' },
                { value: 'cups', label: 'CUPS' },
                { value: 'verificationPhone', label: 'Tel. verificación' },
                { value: 'contractEmail', label: 'Correo. contratación' }
            ],
            searchFilters: {
                agent: '',
                status: '',
                marketer: '',
                productMarketer: '',
                productType: '',
                account: '',
                fee: ''
            },
            filters: {
                checkbox: {
                    productTypeAvailable: {
                        title: 'Tipo de producto',
                        data: []
                    },
                    agentAvailable: {
                        title: 'Agente',
                        data: []
                    },
                    marketerAvailable: {
                        title: 'Comercializadora',
                        data: []
                    },
                    productMarketerAvailable: {
                        title: 'Producto de comercializadora',
                        data: []
                    },
                    statusAvailable: {
                        title: 'Estado',
                        data: []
                    },
                },
                radio: {
                    view: {
                        title: 'Vista',
                        checked: 0,
                        data: [
                            {
                                title: 'Todos',
                                value: 0,
                            },
                            {
                                title: 'En tramitación',
                                value: 1,
                            },
                            {
                                title: 'Comisionados',
                                value: 2
                            }
                        ]
                    },
                    withoutCommision: {
                        title: 'Vista',
                        checked: 0,
                        data: [
                            {
                                title: 'No',
                                value: 0,
                            },
                            {
                                title: 'Si',
                                value: 1,
                            }
                        ]
                    },
                    otherSubdomains: {
                        title: 'Otros subdominios',
                        checked: 0,
                        data: [
                            {
                                title: 'Todos',
                                value: 0,
                            },
                            {
                                title: 'Si',
                                value: 1,
                            },
                            {
                                title: 'No',
                                value: 2,
                            }
                        ]
                    },
                    sortBy: {
                        title: 'Ordenar por',
                        checked: this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2' ? 26 : 17,
                        data: [
                            {
                                title: 'Título (A-Z)',
                                value: 0,
                            },
                            {
                                title: 'Título (Z-A)',
                                value: 1,
                            },
                            {
                                title: 'Agente (A-Z)',
                                value: 2,
                            },
                            {
                                title: 'Agente (Z-A)',
                                value: 3,
                            },
                            {
                                title: 'NIF/CIF (A-Z)',
                                value: 4,
                            },
                            {
                                title: 'NIF/CIF (Z-A)',
                                value: 5,
                            },
                            {
                                title: 'Tarifa (A-Z)',
                                value: 6,
                            },
                            {
                                title: 'Tarifa (Z-A)',
                                value: 7,
                            },
                            {
                                title: 'Producto (A-Z)',
                                value: 8,
                            },
                            {
                                title: 'Producto (Z-A)',
                                value: 9,
                            },
                            {
                                title: 'CUPS',
                                value: 10,
                            },
                            {
                                title: 'CUPS',
                                value: 11,
                            },
                            {
                                title: 'Estado (A-Z)',
                                value: 12,
                            },
                            {
                                title: 'Estado (A-Z)',
                                value: 13,
                            },
                            {
                                title: 'Ult. estado (reciente, antigua)',
                                value: 14,
                            },
                            {
                                title: 'Ult. estado (reciente, antigua)',
                                value: 15,
                            },
                            {
                                title: 'Ult. actualización (reciente, antigua)',
                                value: 16,
                            },
                            {
                                title: 'Ult. actualización (reciente, antigua)',
                                value: 17,
                            },
                            {
                                title: 'Fec. activación (reciente, antigua)',
                                value: 18,
                            },
                            {
                                title: 'Fec. activación (antigua, reciente)',
                                value: 19,
                            },
                            {
                                title: 'Id (reciente, antiguo)',
                                value: 26,
                            },
                            {
                                title: 'Id (antiguo, reciente)',
                                value: 27,
                            },

                            {
                                title: 'Fec. traspaso (reciente, antigua)',
                                value: 28,
                            },
                            {
                                title: 'Fec. traspaso (antigua, reciente)',
                                value: 29,
                            },
                        ]
                    }
                }
            },
            filtersObtained: {
                agents: [],
                statuses: [],
                marketers: [],
                products: [],
                productTypes: [],
                //dual
                dualFees: [],
                dualMarketers: [],
                dualMarketerProducts: [],
                //telefonía
                telephonyFees: [],
                telephonyMarketers: [],
                telephonyMarketerProducts: [],
                fees: [],
                dates: {
                    start: '',
                    end: ''
                },
                lowDates: {
                    start: '',
                    end: ''
                },
                activationDates: {
                    start: '',
                    end: ''
                },
                renewalPendingDates: {
                    start: '',
                    end: ''
                }
            },
            productTypes: [
                {
                    code: 'c',
                    title: 'Contador'
                },
                {
                    code: 'i',
                    title: 'Iluminación'
                },
                {
                    code: 'a',
                    title: 'Autoconsumo'
                },
                {
                    code: 'bc',
                    title: 'Bateria de condensadores'
                },
                {
                    code: 'ce',
                    title: 'Coche eléctrico'
                },
                {
                    code: 'cl',
                    title: 'Contrato de luz'
                },
                {
                    code: 'cg',
                    title: 'Contrato de gas'
                },
                {
                    code: 'cd',
                    title: 'Contrato dual'
                },
                {
                    code: 'ct',
                    title: 'Contrato de telefonía'
                },
                {
                    code: 'sa',
                    title: 'Servicio de alarmas'
                },
                {
                    code: 'crm',
                    title: 'Servicios CRM'
                }
            ],
            statuses: [
                {
                    code: 'r',
                    title: 'Recibido',
                    color: 'receivedStatus',
                    limitedTo: []
                },
                {
                    code: 'p',
                    title: 'Pendiente',
                    color: 'pendingStatus',
                    limitedTo: []
                },
                {
                    code: 't',
                    title: 'Tramitado',
                    color: 'processedStatus',
                    limitedTo: []
                },
                {
                    code: 'f',
                    title: 'Firmado',
                    color: 'signedStatus',
                    limitedTo: []
                },
                {
                    code: 'fc',
                    title: 'Firmado - Llamada verificada',
                    color: 'signedAndVerifiedCallStatus',
                    limitedTo: []
                },
                {
                    code: 'ac',
                    title: 'Aceptado',
                    color: 'aceptedStatus',
                    limitedTo: []
                },
                {
                    code: 'ap',
                    title: 'Pendiente de activacion',
                    color: 'activatedPendingStatus',
                    limitedTo: []
                },
                {
                    code: 'a',
                    title: 'Activado',
                    color: 'activatedStatus',
                    limitedTo: []
                },
                {
                    code: 'c',
                    title: 'Comisionado',
                    color: 'commissionedStatus',
                    limitedTo: []
                },
                {
                    code: 'i',
                    title: 'Incidencia',
                    color: 'incidenceStatus',
                    limitedTo: []
                },
                {
                    code: 's',
                    title: 'Scoring',
                    color: 'scoringStatus',
                    limitedTo: []
                },
                {
                    code: 'b',
                    title: 'Baja',
                    color: 'lowStatus',
                    limitedTo: []
                },
                {
                    code: 'bo',
                    title: 'Borrador',
                    color: 'lowStatus',
                    limitedTo: []
                },
                {
                    code: 'an',
                    title: 'Anulado',
                    color: 'morado',
                    limitedTo: []
                }
            ],
            ordersSelected: [],
            orderSelectedToSee: '',
            orderTypeSelected: '', //sort
            viewTypeSelected: '', //vista
            withoutCommisionTypeSelected: '', //vista
            accountsRelated: [],
            accountToAddOrder: '',
            accountToUpdateOrder: '',
            order: {
                name: '',
                direc: '',
                zip: '',
                town: '',
                province: '',
                source: '',
                processingDate: '',
                activationDate: '',
                liquidationStatus: 'nl',
                productType: '',
                marketer: '',
                fee: '',
                product: '',
                commissions: {
                    subdomain: null,
                    breakdown: []
                },
                CUPS: '',
                consumption: '',
                IBAN: '',
                docs: [],
                statuses: [],
                newStatus: {
                    code:
                        this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2' &&
                        !this.canManage('contracts.processor')
                            ? 'bo'
                            : 'p',
                    date: ''
                },
                errors: {},
                usersIds: [],
                createdBy: ''
            },
            orderToModify: '',
            selectValues: '',
            isCreatingOrder: false,
            isEditing: false,
            isEditingOrder: false,
            currentPage: 1,
            perPage: 50,
            totalPages: 1,
            perPageOptions: [50, 100, 200],
            debouncedFetchAllOrders: null,
            marketers: [],
            marketerProductsOthers: ['Sin excedentes', 'Con excedentes', 'Compartido'],
            queue: null,
            showCall: false
        }
    },
    created() {
        let localData = JSON.parse(sessionStorage.getItem('filters'))

        localData = (localData && localData['orders']) ? localData['orders'] : null;

        if(localData){
            this.perPage = localData['perPage']
            this.currentPage = localData['currentPage']

            if (localData && localData['withoutCommision'])
                this.filters.radio.withoutCommision.checked = localData['withoutCommision']

            if (localData && localData['searchText'])
                this.searchOrderText = localData['searchText']

            if (localData && localData['searchField'] && this.isFidelitySubdomain) {
                const hasOption = this.searchOrderFieldOptions.some(option => option.value === localData['searchField']);
                this.searchOrderField = hasOption ? localData['searchField'] : 'all';
            }

            this.putDates()
        }


        this.debouncedFetchAllOrders = this.debounce(this.fetchAllOrders, 300);
        this.fetchAllMarketers()

        // Al crear el componente, aplicamos debounce al méto fetchAllOrders
        this.debouncedFetchAllOrders = this.debounce(this.fetchAllOrders, 300);

    },
    mounted() {

        if (this.basicData.userLogged && this.basicData.userLogged._id && this.basicData.userList) {
            this.fetchAllAccounts()
            //this.getOrderTypeSelected()
            this.getViewTypeSelected()
            this.getWithoutCommisionTypeSelected()
            this.putDates()
            //Saco los valores para los selects
            this.fetchSelectValues()
        }


        this.orderToModify = JSON.parse(JSON.stringify(this.order));

        let localData = JSON.parse(sessionStorage.getItem('filters'))['orders']

        this.perPage = localData['perPage']
        this.currentPage = localData['currentPage']

        if (localData && localData['withoutCommision'])
            this.filters.radio.withoutCommision.checked = localData['withoutCommision']

        if (localData && localData['searchText'])
            this.searchOrderText = localData['searchText'];

        if (localData && localData['searchField'] && this.isFidelitySubdomain) {
            const hasOption = this.searchOrderFieldOptions.some(option => option.value === localData['searchField']);
            this.searchOrderField = hasOption ? localData['searchField'] : 'all';
        }


        if (localStorage.getItem('isContractOnly') === 'true') {
            const tmp = localStorage.getItem('temporalyCreateAcc');
            if (tmp) {
                const opp = JSON.parse(tmp);

                this.isSeeingOrderComponent = true; // abre popup creación
                this.orderToModify.name = opp.order.name || '';
                this.orderToModify.CUPS = opp.order?.CUPS || '';
                this.orderToModify.province = opp.order?.province || '';
                this.orderToModify.town = opp.order?.town || '';
                this.orderToModify.direc = opp.order?.direc || '';
                this.orderToModify.zip = opp.order?.zip || '';
                this.orderToModify.productType = opp.order?.productType || '';
                this.orderToModify.marketer = opp.order?.marketer || '';
                this.orderToModify.fee = opp.order?.fee || '';
                this.orderToModify.product = opp.order?.product || '';
                if (opp.order?.extras) this.orderToModify.extras = opp.order?.extras;
                if (opp.order?.feeSecondary) this.orderToModify.feeSecondary = opp.order?.feeSecondary;
                if (opp.order?.productSecondary) this.orderToModify.productSecondary = opp.order?.productSecondary;
                if (opp.order?.CUPSSecondary) this.orderToModify.CUPSSecondary = opp.order?.CUPSSecondary;
                if (opp.order?.consumptionSecondary) this.orderToModify.consumptionSecondary = opp.order?.consumptionSecondary;


                //Cierro to el seleccionable de creación
                this.isSeeingCreateOrder = false;
                this.typeOfCreate = null;

                // Limpio los flags
                localStorage.removeItem('temporalyCreateAcc');
                localStorage.removeItem('isContractOnly');
            }
        }

    },
    watch: {
        searchOrderText() {
            // siempre vuelve a la primera página al buscar
            this.currentPage = 1;
            // dispara la carga (con debounce si lo tienes configurado)
            if (typeof this.debouncedFetchAllOrders === 'function') {
                this.debouncedFetchAllOrders();
            } else {
                this.fetchAllOrders();
            }
        },
        "basicData.userLogged": {
            immediate: true,
            async handler(newVal) {
                if (!newVal || !this.basicData?.userSubdomain) return;

                if (this.isFidelitySubdomain && this.filters.radio.sortBy.checked === 17) {
                    this.filters.radio.sortBy.checked = 26;
                }

                this.getViewTypeSelected();
                this.getWithoutCommisionTypeSelected();
                this.putDates();

                this.fetchAllOrders(true);

                this.fetchAllOrdersWithoutPaginate();
                this.fetchAllAccounts();

                this.fetchAllAccounts();
            }
        },

        '$route.query._id': {
            immediate: true,
            handler(newId) {
                if (newId){
                    axios.get('/api/orders/' + this.$route.query._id)
                        .then((res) => {
                            this.selectOrderToSee(res.data.order)
                        })
                        .catch((err) => {
                            console.log(err)
                        })
                }else{
                    this.orderToModify = JSON.parse(JSON.stringify(this.order));
                    this.isSeeingOrderComponent = false;
                }

            }
        }
    },
    methods: {
    openInvoiceFilePicker() {
        this.isSeeingCreateOrder = false;
        this.typeOfCreate = null;

        if (this.$refs?.inputInvoiceFile) {
            this.$refs.inputInvoiceFile.value = null;
            this.$refs.inputInvoiceFile.click();
        }
    },
    pickupInvoiceFile(event) {
        const files = Array.from(event.target.files || []);
        if (!files.length) return;
        this.handleInvoiceFiles(files);
    },

    async handleInvoiceFiles(files) {
    try {
        this.isLoading = true;

        let successCount = 0;
        let errorCount = 0;
        let resultadosList = '';
        let lastAccountId = null;

        Swal.fire({
            title: 'Procesando facturas...',
            html: `<p>0 de ${files.length} completadas</p>`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        for (let i = 0; i < files.length; i++) {
            const file = files[i];

            Swal.update({
                html: `
                    <p style="margin-bottom:10px"><b>Procesando ${i + 1} de ${files.length}: ${file.name}</b></p>
                    <div style="max-height:250px; overflow-y:auto">${resultadosList}</div>
                `
            });
            Swal.showLoading();

            try {
                const formData = new FormData();
                formData.append('files[]', file);
                formData.append('userSubdomain', JSON.stringify(this.basicData.userSubdomain));
                formData.append('enterprise', JSON.stringify(this.basicData.enterprise));
                formData.append('colors', JSON.stringify(this.getAllColorVariables()));

                const res = await axios.post('/api/orders/create-from-invoice-quick', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                })

                const accountName = res.data?.account?.name || '-';
                const orderName = res.data?.order?.name || '-';
                const accountExistia = res.data?.message?.includes('existente');
                const accountId = res.data?.account?._id || null;

                if (accountId) lastAccountId = accountId;

                successCount++;
                resultadosList += `
                    <div style="text-align:left; border:1px solid #eee; border-radius:8px; padding:8px; margin-bottom:6px">
                        <p style="margin:0 0 4px 0"><b>Factura ${i + 1}:</b> ${file.name}</p>
                        <p style="margin:2px 0">Cuenta: <b>${accountName}</b> ${accountExistia ? '<span style="color:#f0a500">(ya existía, reutilizada)</span>' : '<span style="color:green">(creada)</span>'}</p>
                        <p style="margin:2px 0">Contrato creado: <b>${orderName}</b></p>
                    </div>
                `;

            } catch (err) {
                errorCount++;
                resultadosList += `
                    <div style="text-align:left; border:1px solid #ffcccc; border-radius:8px; padding:8px; margin-bottom:6px; background:#fff5f5">
                        <p style="margin:0"><b>Factura ${i + 1}:</b> ${file.name}</p>
                        <p style="margin:2px 0; color:red">Error: ${err?.response?.data?.error || err?.response?.data?.limit || 'No se pudo procesar'}</p>
                    </div>
                `;
            }
        }

        this.fetchAllOrders();

        await Swal.fire({
            icon: errorCount === 0 ? 'success' : 'warning',
            title: errorCount === 0 ? 'Proceso completado' : 'Proceso completado con errores',
            html: `
                <p style="margin-bottom:12px">${successCount} creados correctamente${errorCount > 0 ? `, ${errorCount} con error` : ''}</p>
                <div style="max-height:300px; overflow-y:auto">
                    ${resultadosList}
                </div>
            `
        });

        if (lastAccountId) {
            this.$router.push('/accounts/' + lastAccountId);
        }

    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Se produjo un error procesando las facturas.'
        });
    } finally {
        this.isLoading = false;
        if (this.$refs?.inputInvoiceFile) {
            this.$refs.inputInvoiceFile.value = null;
        }
    }
},
    async fetchAllOrdersWithoutPaginate() {
            const res = await axios.post('/api/orders/indexWithoutPaginate', {
                userLogged: this.basicData.userLogged,
                userList: this.basicData.userList
            })

            this.ordersAll = res.data.orders || []
        },
        hasDocsWithEmptyValue(docs) {
        if (!Array.isArray(docs)) return false;
        return docs.some(d => d.value === "" || d.value === null || d.value === undefined);
    },
        getPrettyDateTransfer(date) {
            return moment(date, "YYYY-MM-DD HH:mm:ss").format("DD/MM/YYYY");
        },
        fetchAllOrders(filtersFromCookies) {


            //Cancelar la anterior petición si existe
            if (this.queue) {
                this.queue.abort();
            }
            this.queue = new AbortController();
            const signal = this.queue.signal;

            this.orders = [];

            //establezco los filtros de la manera de la que se va a filtrar en la consulta
            this.setFiltersToSend()

            //console.log('isFirstTime: !!filtersFromCookies --->', filtersFromCookies === true)


            //Para ver si la primera vez que se entra tiene filtros ya guardados en la cookie
            let localData = JSON.parse(sessionStorage.getItem('filters'))['orders']

            let hasFilteredFromCookies = (localData['agents'].length > 0 || localData['statuses'].length > 0 || localData['marketers'].length > 0 || localData['productTypes'].length > 0 || localData['products'].length > 0 || (localData['dates']['start'] !== '' || localData['dates']['end'] !== '') || (localData['activationDates']['start'] !== '' || localData['activationDates']['end'] !== '') || (localData['lowDates']['start'] !== '' || localData['lowDates']['end'] !== '') || (!!localData['searchText'] && localData['searchText'] !== ''))


            //console.log('filtros antes de enviar --> ', localData)

            //Comprobar si searchOrderText es CUPS, y en caso de serlo borrar el 0F
            let searchOrderText = /^ES\d{16}[a-zA-Z]{2}0F$/i.test(this.searchOrderText) ? this.searchOrderText.slice(0, -2) : this.searchOrderText;

            //Muestro vista de carga
            this.isLoading = true;

            axios.post(`/api/orders/index`, {
                filters: localData,
                sortType: this.filters.radio.sortBy.checked,
                commisionType: this.partSeeing,
                searchOrderText: searchOrderText,
                searchOrderField: this.isFidelitySubdomain ? this.searchOrderField : 'all',
                page: this.currentPage,
                perPage: this.perPage,
                filtersFromCookies: (filtersFromCookies === true && hasFilteredFromCookies),
                onlyWithoutCommision: this.filters.radio.withoutCommision.checked,
                otherSubdomains: this.filters.radio.otherSubdomains.checked,
                view: this.filters.radio.view.checked,
                isFirstTime: filtersFromCookies === true,
                userLogged: this.basicData.userLogged,
                userSubdomain: this.basicData.userSubdomain,
                userList: this.basicData.userList,
                subdomainUserList: this.basicData.subdomainUserList
                }, {
                signal
            })
                .then((res) => {
                    this.orders = res.data.orders


                    if (res.data.filtersObtained !== null && res.data.filtersObtained[0]) {

                        this.filtersObtained.agents = res.data.filtersObtained[0].agents;
                        this.filtersObtained.marketers = res.data.filtersObtained[0].marketers;
                        this.filtersObtained.productTypes = res.data.filtersObtained[0].productTypes;
                        this.filtersObtained.products = res.data.filtersObtained[0].products;
                        this.filtersObtained.statuses = JSON.parse(JSON.stringify(res.data.filtersObtained[0].statuses));
                        this.filtersObtained.fees = res.data.filtersObtained[0].fees;
                        //dual
                        this.filtersObtained.dualFees = res.data.filtersObtained[0].dualFees;
                        this.filtersObtained.dualMarketers = res.data.filtersObtained[0].dualMarketers;
                        this.filtersObtained.dualMarketerProducts = res.data.filtersObtained[0].dualMarketerProducts;
                        //telefonía
                        this.filtersObtained.telephonyFees = res.data.filtersObtained[0].telephonyFees;
                        this.filtersObtained.telephonyMarketers = res.data.filtersObtained[0].telephonyMarketers;
                        this.filtersObtained.telephonyMarketerProducts = res.data.filtersObtained[0].telephonyMarketerProducts;
                        //alarmas
                        this.filtersObtained.alarmMarketers = res.data.filtersObtained[0].alarmMarketers;
                        this.filtersObtained.alarmMarketerProducts = res.data.filtersObtained[0].alarmMarketerProducts;
                        //placas
                        this.filtersObtained.selfSupplyFees = res.data.filtersObtained[0].selfSupplyFees;
                        this.filtersObtained.selfSupplyMarketers = res.data.filtersObtained[0].selfSupplyMarketers;
                        this.filtersObtained.selfSupplyMarketerProducts = res.data.filtersObtained[0].selfSupplyMarketerProducts;

                        //Establezco los filtros para usar
                        this.setFilters(true);
                    }

                    //Establezco el número total de páginas
                    this.totalPages = Math.ceil(res.data.summaryData.totalResults / this.perPage);

                    //Establezco el número total de pedidos
                    this.totalOrders = res.data.summaryData.totalResults;

                    //Establezco los totales del resumen
                    this.summaryData = res.data.summaryData;

                })
                .catch((error) => {
                    if (axios.isCancel(error) || error.name === "AbortError") {
                        console.log("Petición cancelada:", error.message);
                    } else {
                        console.error("Error en la búsqueda:", error);
                    }
                })
                .finally(() => {
                    //Termino el estado de carga
                    if (!signal.aborted) {
                        this.isLoading = false;
                    }

                })
        },
        fetchAllOrdersAll(filtersFromCookies, stopQueue) {
            if (this.queue && !stopQueue) {
                this.queue.abort()

                this.queue = new AbortController()
            }

            const signal = this.queue.signal

            this.ordersAll = []      // Limpiamos antes
            this.setFiltersToSend()

            const localData = JSON.parse(sessionStorage.getItem('filters'))['orders']
            const hasFilteredFromCookies =
                localData.agents.length > 0 ||
                localData.statuses.length > 0 ||
                localData.marketers.length > 0 ||
                localData.productTypes.length > 0 ||
                localData.products.length > 0 ||
                (localData.dates.start !== '' || localData.dates.end !== '') ||
                (localData.activationDates.start !== '' || localData.activationDates.end !== '') ||
                (!!localData.searchText && localData.searchText !== '')
                || (localData['renewalPendingDates'] && (localData['renewalPendingDates']['start'] !== '' || localData['renewalPendingDates']['end'] !== ''))


            const searchOrderText = /^ES\d{16}[a-zA-Z]{2}0F$/i.test(this.searchOrderText)
                ? this.searchOrderText.slice(0, -2)
                : this.searchOrderText

            this.isLoading = true

            return axios
                .post(
                    `/api/orders/indexAll`,
                    {
                       userLogged: this.basicData.userLogged,
                        userList: this.basicData.userList,
                        userListComplete: this.basicData.userListComplete,
                        filters: localData,
                        sortType: this.filters.radio.sortBy.checked,
                        commisionType: this.partSeeing,
                        searchOrderText: searchOrderText,
                        searchOrderField: this.isFidelitySubdomain ? this.searchOrderField : 'all',
                        filtersFromCookies: filtersFromCookies === true && hasFilteredFromCookies,
                        onlyWithoutCommision: this.filters.radio.withoutCommision.checked ? 1 : 0,
                        otherSubdomains: this.filters.radio.otherSubdomains.checked,
                        view: this.filters.radio.view.checked,
                        isFirstTime: false,
                        userSubdomain: this.basicData.userSubdomain,
                        subdomainUserList: this.basicData.subdomainUserList

                    },
                    { signal }
                )
                .then((res) => {
                    this.ordersAll = res.data.orders
                })
                .catch((error) => {
                    if (axios.isCancel(error) || error.name === 'AbortError') {
                        console.log('Petición cancelada:', error.message)
                    } else {
                        console.error('Error cargando todos los pedidos:', error)
                    }
                })
                .finally(() => {
                    if (!signal.aborted) {
                        this.isLoading = false
                    }
                })
        },
        async exportOrdersExcel() {
            this.isLoadingMassiveLoad = true;

            this.setFiltersToSend();

            const localData = JSON.parse(sessionStorage.getItem('filters'))['orders'];
            const filtersFromCookies = false; // puedes adaptarlo si hace falta
            const hasFilteredFromCookies =
                localData.agents.length > 0 ||
                localData.statuses.length > 0 ||
                localData.marketers.length > 0 ||
                localData.productTypes.length > 0 ||
                localData.products.length > 0 ||
                (localData.dates.start !== '' || localData.dates.end !== '') ||
                (localData.activationDates.start !== '' || localData.activationDates.end !== '') ||
                (localData.lowDates.start !== '' || localData.lowDates.end !== '') ||
                (localData.renewalPendingDates &&
                    (localData.renewalPendingDates.start !== '' ||
                    localData.renewalPendingDates.end !== '')) ||
                (!!localData.searchText && localData.searchText !== '');

            const searchOrderText = /^ES\d{16}[a-zA-Z]{2}0F$/i.test(this.searchOrderText)
                ? this.searchOrderText.slice(0, -2)
                : this.searchOrderText;

            // 🔹 Construir cuerpo de la petición
            const body = {
                userLogged: this.basicData.userLogged,
                userList: this.basicData.userList,
                userListComplete: this.basicData.userListComplete,
                filters: localData,
                sortType: this.filters.radio.sortBy.checked,
                commisionType: this.partSeeing,
                searchOrderText,
                searchOrderField: this.isFidelitySubdomain ? this.searchOrderField : 'all',
                filtersFromCookies: filtersFromCookies === true && hasFilteredFromCookies,
                onlyWithoutCommision: this.filters.radio.withoutCommision.checked ? 1 : 0,
                otherSubdomains: this.filters.radio.otherSubdomains.checked,
                view: this.filters.radio.view.checked,
                isFirstTime: false,
                userSubdomain: this.basicData.userSubdomain,
                subdomainUserList: this.basicData.subdomainUserList
            };

            try {
                const res = await axios.post('/api/orders/exportTemplate', body, { responseType: 'blob' });

                // 🔹 Descargar el archivo
                const blob = new Blob([res.data], { type: res.headers['content-type'] });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download =
                    res.headers['content-disposition']?.match(/filename="([^"]+)"/)?.[1] ||
                    'Contratos.xlsx';
                a.click();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error('❌ Error exportando Excel:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error exportando',
                    text: error?.response?.data?.error || 'No se pudo generar el Excel'
                });
            } finally {
                this.isLoadingMassiveLoad = false;
            }
        },
        loadAndExportAll() {
            this.isSeeingMassiveLoad = false;

            this.exportOrdersExcel();
        },
        resetSearch() {

            this.searchOrderText = ''

            //Quito de cookies
            let localData = JSON.parse(sessionStorage.getItem('filters'))

            localData['orders']['searchText'] = '';

            sessionStorage.setItem("filters", JSON.stringify(localData))

            this.debouncedFetchAllOrders()
        },
        changePageSize(event) {

            let localDataFilter = JSON.parse(sessionStorage.getItem('filters'))

            localDataFilter['orders']['perPage'] = event.target.value;
            localDataFilter['orders']['currentPage'] = 1;

            sessionStorage.setItem('filters', JSON.stringify(localDataFilter))

            this.fetchAllOrders()
        },
        setFilters(hasFilteredFromCookies) {
            let localData = JSON.parse(sessionStorage.getItem('filters'))['orders']

            // AGENTES
            if (this.canManage('users.admiWhiHier')) {
                const fromCookies = hasFilteredFromCookies && localData['agents'] && localData['agents'].length > 0;

                this.filtersObtained.agents = this.basicData.subdomainUserList
                    .filter(user => user && user._id)
                    .map(user => {
                        const isActive = fromCookies ? localData['agents'].includes(user._id) : true;
                        return {
                            code: user._id,
                            name: user.firstName + ' ' + user.lastName,
                            active: fromCookies ? isActive : true
                        };
                    })
                    .sort((a, b) => a.name.localeCompare(b.name));
            } else {
                this.filtersObtained.agents = this.filtersObtained.agents.map(agentId => {
                    if (!agentId) return null;

                    let user = this.basicData.userListComplete.find(user => user._id === agentId);

                    if (!user && agentId === this.basicData.userLogged._id) user = this.basicData.userLogged

                    if (!user) {
                        return {code: agentId, name: 'Usuario no existente', active: true}
                    }

                    let fromCookies = hasFilteredFromCookies && localData['agents'] && localData['agents'].length > 0;
                    let isActive = fromCookies && localData['agents'] && localData['agents'].includes(user._id);

                    return user ? {
                        code: user._id,
                        name: user.firstName + ' ' + user.lastName,
                        active: fromCookies ? isActive : true
                    } : null;
                }).filter(agent => agent !== null).sort((a, b) => a.name.localeCompare(b.name));
            }

            // Estados
            this.filtersObtained.statuses = this.filtersObtained.statuses.map(statusCode => {
                if (!statusCode) return null;
                let state = this.basicData.userSubdomain.statuses.find(state => state.code === statusCode);

                let fromCookies = hasFilteredFromCookies && localData['statuses'] && localData['statuses'].length > 0;
                let isActive = fromCookies && localData['statuses'] && localData['statuses'].includes(state.code);

                return state ? {
                    title: state.title,
                    code: state.code,
                    active: fromCookies ? isActive : true
                } : null;
            }).filter(status => status !== null).sort((a, b) => a.code.localeCompare(b.code));

            // Comercializadoras
            this.filtersObtained.marketers = this.filtersObtained.marketers.map(marketer => {

                let fromCookies = hasFilteredFromCookies && localData['marketers'] && localData['marketers'].length > 0;
                let isActive = fromCookies && localData['marketers'] && localData['marketers'].includes(marketer);

                if (marketer === '') {
                    marketer = 'Sin comercializadora';
                }

                return {
                    title: marketer,
                    code: marketer,
                    active: fromCookies ? isActive : true
                }
            }).filter(marketer => marketer !== null).sort((a, b) => a.code.localeCompare(b.code));

            // Tipo de producto
            this.filtersObtained.productTypes = this.filtersObtained.productTypes.map(productType => {

                let prodType = (productType === '' || !productType) ? { code: '', title: 'Sin tipo de producto' } : this.productTypes.find(pType => pType.code === productType);

                let fromCookies = hasFilteredFromCookies && localData['productTypes'] && localData['productTypes'].length > 0;
                let isActive = fromCookies && localData['productTypes'] && localData['productTypes'].includes(prodType.code);

                return prodType ? {
                    title: prodType.title,
                    code: prodType.code,
                    active: fromCookies ? isActive : true
                } : null;
            }).filter(productType => productType !== null).sort((a, b) => a.code.localeCompare(b.code));

            // Productos
            this.filtersObtained.products = this.filtersObtained.products.map(product => {

                let fromCookies = hasFilteredFromCookies && localData['products'] && localData['products'].length > 0;
                let isActive = fromCookies && localData['products'] && localData['products'].includes(product);

                if (product === '' || !product) product = 'Sin producto';

                return {
                    title: product,
                    code: product,
                    active: fromCookies ? isActive : true
                }
            }).filter(product => product !== null).sort((a, b) => a.code.localeCompare(b.code));

            // Tarifas
            this.filtersObtained.fees = this.filtersObtained.fees.map(fee => {

                if (fee === '' || !fee) {
                    fee = 'Sin tarifa';
                }

                let fromCookies = hasFilteredFromCookies && localData['fee'] && localData['fee'].length > 0;
                let isActive = fromCookies && localData['fee'] && localData['fee'].includes(fee);

                return {
                    title: fee,
                    code: fee,
                    active: fromCookies ? isActive : true
                }
            }).filter(fee => fee !== null).sort((a, b) => a.code.localeCompare(b.code));
        },
        setFiltersToSend() { //Filtros para enviar a controlador y sacar pedidos

            let extractActiveCodes = (items) => items.filter(item => item.active).map(item => item.code);

            let localData = JSON.parse(sessionStorage.getItem('filters'))

            if (localData){

                if (this.filtersObtained['agents'] && this.filtersObtained['agents'].length > 0)
                    localData['orders']['agents'] = extractActiveCodes(this.filtersObtained['agents']);

                if (this.filtersObtained['statuses'] && this.filtersObtained['statuses'].length > 0)
                    localData['orders']['statuses'] = extractActiveCodes(this.filtersObtained['statuses']);

                if (this.filtersObtained['marketers'] && this.filtersObtained['marketers'].length > 0) {
                    localData['orders']['marketers'] = extractActiveCodes(this.filtersObtained['marketers']);

                    // Convertir '' de vuelta a 'Sin comercializadora'
                    localData['orders']['marketers'] = localData['orders']['marketers'].map(code => code === 'Sin comercializadora' ? '' : code);
                }

                if (this.filtersObtained['products'] && this.filtersObtained['products'].length > 0) {
                    localData['orders']['products'] = extractActiveCodes(this.filtersObtained['products']);

                    // Convertir '' de vuelta a 'Sin producto'
                    localData['orders']['products'] = localData['orders']['products'].map(code => code === 'Sin producto' ? '' : code);
                }


                if (this.filtersObtained['productTypes'] && this.filtersObtained['productTypes'].length > 0) {
                    localData['orders']['productTypes'] = extractActiveCodes(this.filtersObtained['productTypes']);

                    // Convertir '' de vuelta a 'Sin tipo de producto'
                    localData['orders']['productTypes'] = localData['orders']['productTypes'].map(code => code === 'Sin tipo de producto' ? '' : code);
                }

                if (this.filtersObtained['fees'] && this.filtersObtained['fees'].length > 0) {
                    localData['orders']['fees'] = extractActiveCodes(this.filtersObtained['fees']);

                    // Convertir '' de vuelta a 'Sin tarifa'
                    localData['orders']['fees'] = localData['orders']['fees'].map(code => code === 'Sin tarifa' ? '' : code);
                }


                if (this.filtersObtained.dates.start !== '' || this.filtersObtained.dates.end !== '')
                    localData['orders']['dates'] = this.filtersObtained.dates;

                if (this.filtersObtained.activationDates.start !== '' || this.filtersObtained.activationDates.end !== '')
                    localData['orders']['activationDates'] = this.filtersObtained.activationDates;


                if (!this.filtersObtained || !this.filtersObtained.lowDates.start || !this.filtersObtained.lowDates.end || this.filtersObtained.lowDates.start !== '' || this.filtersObtained.lowDates.end !== '')
                    localData['orders']['lowDates'] = this.filtersObtained.lowDates;


                if (this.filtersObtained.renewalPendingDates.start !== '' || this.filtersObtained.renewalPendingDates.end !== '') {
                    localData['orders']['renewalPendingDates'] = this.filtersObtained.renewalPendingDates;
                } else {
                    delete localData['orders']['renewalPendingDates'];

                }


                // Comprobar si hay algún producto distinto de 'cl' o 'cg'
                //let containsOtherProductType = cookiesFilter['orders']['productTypes'].some(type => type !== 'cl' && type !== 'cg');

                // Si existe algún producto diferente de 'cl' o 'cg', añadir '' a marketers
                /*if (containsOtherProductType && (!cookiesFilter['orders']['marketers'] || !cookiesFilter['orders']['marketers'].includes(''))) {
                    cookiesFilter['orders']['marketers'] = cookiesFilter['orders']['marketers'] || [];
                    cookiesFilter['orders']['marketers'].push('');
                }*/


                sessionStorage.setItem("filters", JSON.stringify(localData))

            }



        },
        /*setFiltersOld(){

            //Borro por si ya hay establecidos
            this.filters.checkbox.productTypeAvailable.data = [];
            this.filters.checkbox.agentAvailable.data = [];

            let orders = JSON.parse(JSON.stringify(this.orders))

            //Recorro cada producto
            for (let order of orders) {

                //AGENTE
                let existsAgent = this.filters.checkbox.agentAvailable.data.some(label => label.title === order.agent)

                if(!existsAgent){

                    let productType = {
                        title: order.agent,
                        active: true
                    }

                    this.filters.checkbox.agentAvailable.data.push(productType);
                }


                //COMERCIALIZADORA
                if (order.marketer === '') order.marketer = 'Sin comercializadora'

                let existsMarketer = this.filters.checkbox.marketerAvailable.data.some((label) => label.title === order.marketer)

                if(!existsMarketer){

                    let productType = {
                        title: order.marketer,
                        active: true
                    }

                    this.filters.checkbox.marketerAvailable.data.push(productType);
                }

                let existMarketerIndex = this.filters.checkbox.marketerAvailable.data.findIndex(label => label.title === 'Sin comercializadora');

                if (existMarketerIndex === -1)
                    this.filters.checkbox.marketerAvailable.data.splice(1, 0, { title: 'Sin comercializadora', active: true });



                //TIPOS DE PRODUCTO

                    //Saco el producto
                    let product = this.productTypes.find((productType) => {
                        return productType.code === order.productType
                    })

                    let existsProduct = this.filters.checkbox.productTypeAvailable.data.some(label => label.title === product.title)

                    if(!existsProduct){

                        let productType = {
                            title: product.title,
                            active: true
                        }

                        this.filters.checkbox.productTypeAvailable.data.push(productType);
                    }


                //ESTADO

                    //Saco el estado
                    let recentStatus = order.statuses.reduce((latest, current) => {
                        return new Date(current.date) > new Date(latest.date) ? current : latest;
                    });

                    let status = this.statuses.find((status) => {
                        return status.code === recentStatus.code
                    })

                    let existsStatus = this.filters.checkbox.statusAvailable.data.some(label => label.title === status.title)

                    if(!existsStatus){

                        let productType = {
                            title: status.title,
                            active: true
                        }

                        this.filters.checkbox.statusAvailable.data.push(productType);
                    }


                //ORDENO LOS SELECTS

                    //Agentes
                    this.filters.checkbox.agentAvailable.data.sort((a, b) => a.title.localeCompare(b.title));

                    //Estados
                    this.filters.checkbox.statusAvailable.data.sort((a, b) => a.title.localeCompare(b.title));

                    //Comercializadoras
                    this.filters.checkbox.marketerAvailable.data.sort((a, b) => a.title.localeCompare(b.title));

                    //Productos
                    this.filters.checkbox.productTypeAvailable.data.sort((a, b) => a.title.localeCompare(b.title));
            }

            //Cuando ya esten cargadas todas las comercializadoras cargo los productos relacionados con las comercializadoras
            this.setComputedFiltered();

        },*/
        changePartSeeing(value) {

            this.partSeeing = value

            this.currentPage = 1;

            this.fetchAllOrders()
        },
        setDate(date) {
            let localData = JSON.parse(sessionStorage.getItem('filters'))

            //Elimino el filtro que deseo
            switch (date) {
                case 'dates.start':
                    localData['orders']['dates']['start'] = this.filtersObtained.dates.start;
                    break;

                case 'dates.end':
                    localData['orders']['dates']['end'] = this.filtersObtained.dates.end;
                    break;

                case 'activationDates.start':
                    localData['orders']['activationDates']['start'] = this.filtersObtained.activationDates.start;
                    break;

                case 'activationDates.end':
                    localData['orders']['activationDates']['end'] = this.filtersObtained.activationDates.end;
                    break;

                case 'lowDates.start':
                    localData['orders']['lowDates']['start'] = this.filtersObtained.lowDates.start;
                    break;

                case 'lowDates.end':
                    localData['orders']['lowDates']['end'] = this.filtersObtained.lowDates.end;
                    break;
                case 'renewalPendingDates.start':
                  // asegúrate de que exista el objeto
                  if (!localData['orders']['renewalPendingDates']) localData['orders']['renewalPendingDates'] = { start: '', end: '' };
                  localData['orders']['renewalPendingDates']['start'] = this.filtersObtained.renewalPendingDates.start;
                  break;

                case 'renewalPendingDates.end':
                  if (!localData['orders']['renewalPendingDates']) localData['orders']['renewalPendingDates'] = { start: '', end: '' };
                  localData['orders']['renewalPendingDates']['end'] = this.filtersObtained.renewalPendingDates.end;
                  break;
            }

            sessionStorage.setItem("filters", JSON.stringify(localData))

            //Cargo de nuevo los pedidos
            this.fetchAllOrders()
        },
        deleteFilter(filter) {

            let localData = JSON.parse(sessionStorage.getItem('filters'))

            //Elimino el filtro que deseo
            switch (filter) {
                case 'dates.start':
                    localData['orders']['dates']['start'] = '';
                    this.filtersObtained.dates.start = '';
                    break;

                case 'dates.end':
                    localData['orders']['dates']['end'] = '';
                    this.filtersObtained.dates.end = '';
                    break;

                case 'activationDates.start':
                    localData['orders']['activationDates']['start'] = '';
                    this.filtersObtained.activationDates.start = '';
                    break;

                case 'activationDates.end':
                    localData['orders']['activationDates']['end'] = '';
                    this.filtersObtained.activationDates.end = '';
                    break;

                case 'lowDates.start':
                    localData['orders']['lowDates']['start'] = '';
                    this.filtersObtained.lowDates.start = '';
                    break;

                case 'lowDates.end':
                    localData['orders']['lowDates']['end'] = '';
                    this.filtersObtained.lowDates.end = '';
                    break;
                case 'renewalPendingDates.start':
                    if (!localData['orders']['renewalPendingDates']) localData['orders']['renewalPendingDates'] = { start: '', end: '' };
                    localData['orders']['renewalPendingDates']['start'] = '';
                    this.filtersObtained.renewalPendingDates.start = '';
                break;

                case 'renewalPendingDates.end':
                    if (!localData['orders']['renewalPendingDates']) localData['orders']['renewalPendingDates'] = { start: '', end: '' };
                    localData['orders']['renewalPendingDates']['end'] = '';
                    this.filtersObtained.renewalPendingDates.end = '';
                break;
            }

            sessionStorage.setItem("filters", JSON.stringify(localData))

            //Cargo de nuevo los pedidos
            this.fetchAllOrders()
        },
        resetFilters() {

            //Pongo todos los filtros en active true

            //Agentes
            this.filtersObtained.agents.forEach(agent => agent.active = true)

            //Estados
            this.filtersObtained.statuses.forEach(status => status.active = true)

            //Tipo de producto
            this.filtersObtained.productTypes.forEach(productType => productType.active = true)

            //Tarifas
            this.filtersObtained.fees.forEach(fee => fee.active = true)

            //Comercializadoras
            this.filtersObtained.marketers.forEach(marketer => marketer.active = true)

            //Productos
            //console.log('filtros --> ', this.filtersObtained.products)
            this.filtersObtained.renewalPendingDates = { start: '', end: '' };


            this.filtersObtained.products.forEach(product => product.active = true)

            //Pongo el filtro de sin comisión en no, para que salgan todos
            this.filters.radio.withoutCommision.checked = 0;


            //Reseteo las fechas
            this.filtersObtained.dates.start = ''
            this.filtersObtained.dates.end = ''

            this.filtersObtained.activationDates.start = ''
            this.filtersObtained.activationDates.end = ''

            this.filtersObtained.lowDates.start = ''
            this.filtersObtained.lowDates.end = ''


            let localData = JSON.parse(sessionStorage.getItem('filters'))

            localData['orders']['dates'] = this.filtersObtained.dates;
            localData['orders']['activationDates'] = this.filtersObtained.activationDates;
            localData['orders']['lowDates'] = this.filtersObtained.lowDates;

            //Vista sin comisión
            localData['orders']['withoutCommision'] = 0;
            this.filters.radio.withoutCommision.checked = 0

            sessionStorage.setItem("filters", JSON.stringify(localData))

            //Vista de todos
            this.filters.radio.view.checked = 0

            //Vista de contrato subdominios
            this.filters.radio.otherSubdomains.checked = 0

            //Saco el titulo de la vista que estamos viendo
            this.getViewTypeSelected();
            this.getWithoutCommisionTypeSelected()

            this.fetchAllOrders()
        },
        saveSearchText() {
            let localData = JSON.parse(sessionStorage.getItem('filters'))

            localData['orders']['searchText'] = this.searchOrderText;

            sessionStorage.setItem("filters", JSON.stringify(localData))
        },
        saveSearchField() {
            let localData = JSON.parse(sessionStorage.getItem('filters'))

            localData['orders']['searchField'] = this.searchOrderField;

            sessionStorage.setItem("filters", JSON.stringify(localData))
        },
        changeSearchField() {
            this.saveSearchField();
            if (this.isFidelitySubdomain) {
                this.filters.radio.sortBy.checked = this.searchOrderField === 'id' ? 27 : 26;
            }
            this.debouncedFetchAllOrders();
        },
        //PAGINACIÓN
        changePage(direction) {
            const localDataFilter = JSON.parse(sessionStorage.getItem('filters')) || {};

            const newPage = this.currentPage + direction;

            // Validar límites
            if (newPage < 1 || newPage > this.totalPages) return;

            this.currentPage = newPage;

            // Asegurar estructura
            if (!localDataFilter.orders) {
                localDataFilter.orders = {};
            }

            localDataFilter.orders.currentPage = this.currentPage;

            sessionStorage.setItem('filters', JSON.stringify(localDataFilter));

            // Recargar pedidos
            this.fetchAllOrders();
        },
        getOrderTypeSelected() {
            this.orderTypeSelected = this.filters.radio.sortBy.data[this.filters.radio.sortBy.checked].title
        },
        getViewTypeSelected() {
            this.viewTypeSelected = this.filters.radio.view.data[this.filters.radio.view.checked].title
        },
        getWithoutCommisionTypeSelected() {
            this.withoutCommisionTypeSelected = this.filters.radio.withoutCommision.data[this.filters.radio.withoutCommision.checked].title
        },
        putDates() {

            let localData = JSON.parse(sessionStorage.getItem('filters'))['orders']


            this.filtersObtained.dates.start = localData['dates']['start']
            this.filtersObtained.dates.end = localData['dates']['end']
            this.filtersObtained.activationDates.start = localData['activationDates']['start']
            this.filtersObtained.activationDates.end = localData['activationDates']['end']
            this.filtersObtained.lowDates.start = localData['lowDates']['start']
            this.filtersObtained.lowDates.end = localData['lowDates']['end']
            this.filtersObtained.renewalPendingDates = localData['renewalPendingDates'] || { start: '', end: '' };


        },
        fetchAllAccounts() {
            let indexId = this.basicData.userLogged._id;

            if (this.canManage('users.admiWhiHier')) {
                indexId = this.basicData.userSubdomain._id;
            }
            axios.post(`/api/accounts/getRelatedAccounts/${indexId}`, { userList: JSON.stringify(this.basicData.userList) })
                .then((res) => {

                    this.accountsRelated = res.data.relatedAccounts;

                    //Ordeno las cuentas
                    this.accountsRelated = this.accountsRelated.sort((a, b) => a.name.localeCompare(b.name));
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async fetchAllMarketers() {
            await axios.get('/api/marketers/all')//Saco todas
                .then((res) => {
                    this.marketers = res.data.marketers
                })
                .catch(err => console.log(err))
        },
        fetchSelectValues() {

            axios.get(`/api/select`)
                .then((res) => {
                    this.selectValues = res.data.selectValues;

                    //Si no se hay todavia un registro para el usuario se crea un array temporal
                    if (!this.selectValues) {
                        this.selectValues = {
                            'acc': [],
                            'sector': [],
                            'origin': [],
                            'orderSources': [],
                            'marketerProducts': []
                        }
                    }
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        debounce(func, delay) {
            let timeoutId;
            return function (...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    func.apply(this, args);
                }, delay);
            };
        },
        async createOrder(payload) {
            const order = payload?.order || payload;
            const validate = payload?.validate || false;

            this.orderToModify = order;

            //Si no se esta creando todavia otro
            if (this.isCreatingOrder === false) {

                this.isCreatingOrder = true;

                this.orderToModify.errors = {
                    commissions: {subdomain: null, breakdown: []},
                    decommissions: {subdomain: null, breakdown: []},
                };

                //Validaciones
                let hasErrors = false;


                if (this.orderToModify.newStatus.code !== 'bo' && this.orderToModify.newStatus.code !== 'an' && this.orderToModify.newStatus.code !== 's') {

                    //Título
                    if (!this.orderToModify.name){
                        this.orderToModify.errors.name = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    //Dirección de suministro
                    if (!this.orderToModify.direc && this.basicData.userSubdomain.settings.orderAddress){
                        this.orderToModify.errors.direc = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    //Fecha activación
                    if ((this.orderToModify.newStatus.code === 'a' || this.orderToModify.newStatus.code === 'b') && !this.orderToModify.activationDate){
                        this.orderToModify.errors.activationDate = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    //Fecha baja
                    if (this.orderToModify.newStatus.code === 'b' && !this.orderToModify.lowDate){
                        this.orderToModify.errors.lowDate = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }


                    //Poblacion
                    if (!this.orderToModify.town && this.basicData.userSubdomain.settings.orderTown){
                        this.orderToModify.errors.town = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }


                    //Provincia
                    if (!this.orderToModify.province && this.basicData.userSubdomain.settings.orderProvince){
                        this.orderToModify.errors.province = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    //Código postal
                    if (!this.orderToModify.zip && this.basicData.userSubdomain.settings.orderPostal){
                        this.orderToModify.errors.zip = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    if (this.orderToModify.zip && this.orderToModify.zip.length !== 5){
                        this.orderToModify.errors.zip = 'El zip tiene que tener 5 digitos';
                        hasErrors = true;
                    }

                    if (this.orderToModify.zip && isNaN(this.orderToModify.zip)){
                        this.orderToModify.errors.zip = this.getErrorMessage('onlyNumbers');
                        hasErrors = true;
                    }

                    if(!this.orderToModify.CNAE && this.basicData.userSubdomain._id === '68d260e6bc9e8c38f8003df2'){
                        this.orderToModify.errors.CNAE = this.getErrorMessage('isEmpty');
                        hasErrors = true;
                    }


                    //Tipo de producto
                    if (!this.orderToModify.productType){
                        this.orderToModify.errors.productType = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }


                    //Producto ( si es luz o gas se selecciona toda la ramificación de abajo y sino se puede añadir)
                    if (!this.orderToModify.product){
                        this.orderToModify.errors.product = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }


                    //SI ES CONTRATO DE LUZ O DE GAS

                    //Tarifa
                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg' || this.orderToModify.productType === 'cd') && !this.orderToModify.fee){
                        this.orderToModify.errors.fee = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    //Comercializadora
                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg' || this.orderToModify.productType === 'cd') && !this.orderToModify.marketer){
                        this.orderToModify.errors.marketer = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    //IBAN
                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg' || this.orderToModify.productType === 'cd') && this.orderToModify.newStatus.code !== 'an' && !this.orderToModify.IBAN  && this.basicData.userSubdomain.settings.orderIBAN){
                        this.orderToModify.errors.IBAN = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    const ibanClean = (this.orderToModify.IBAN || '').replace(/\s/g, '').toUpperCase();

                    if (ibanClean === 'ES0000') {
                        delete this.orderToModify.errors.IBAN;
                    }
                    else if (/^ES0{4}/.test(ibanClean)) {
                        this.orderToModify.errors.IBAN = 'IBAN no válido';
                        hasErrors = true;
                    }
                    else if (this.orderToModify.IBAN && this.orderToModify.IBAN.length !== 29){
                        this.orderToModify.errors.IBAN = 'IBAN no válido';
                        hasErrors = true
                    }

                    //CUPS
                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg' || this.orderToModify.productType === 'cd') && !this.orderToModify.CUPS){
                        this.orderToModify.errors.CUPS = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    if (this.orderToModify.CUPS && this.orderToModify.CUPS.length !== 20){
                        this.orderToModify.errors.CUPS = 'CUPS no válido';
                        hasErrors = true
                    }


                    //Si las comisiones son normales, no carterizadas
                    if (!this.orderToModify.installmentCommissions){
                        // Comisiones
                        if (this.validateCommissionBlock(this.orderToModify.commissions, this.orderToModify.errors.commissions)) {
                            hasErrors = true;
                        }

                        // Comisiones de dual electricidad
                        if (this.orderToModify.commissions?.electricity) {
                            this.orderToModify.errors.commissions.electricity = {subdomain: null, breakdown: []};

                            if (this.validateCommissionBlock(this.orderToModify.commissions.electricity, this.orderToModify.errors.commissions.electricity)) {
                                hasErrors = true;
                            }
                        }

                        // Comisiones de dual gas
                        if (this.orderToModify.commissions?.gas) {
                            this.orderToModify.errors.commissions.gas = {subdomain: null, breakdown: []};

                            if (this.validateCommissionBlock(this.orderToModify.commissions.gas, this.orderToModify.errors.commissions.gas)) {
                                hasErrors = true;
                            }
                        }

                    }
                    else{ //Si son carterizadas
                        this.orderToModify.errors.installmentCommissions = [];

                        this.orderToModify.installmentCommissions.forEach((installment) => {
                            const installmentErrors = { date: null, subdomain: null, breakdown: {} };

                            // Fecha
                            if (!installment.date || installment.date === '') {
                                installmentErrors.date = 'La fecha debe estar rellena';
                                hasErrors = true;
                            }

                            // Comisiones
                            if (this.validateCommissionBlock(installment.commissions, installmentErrors)) {
                                hasErrors = true;
                            }

                            this.orderToModify.errors.installmentCommissions.push(installmentErrors);
                        });
                    }

                    //Decomisiones (solo si es uno de los estados)
                    const decommissionRequiredStatuses = [
                        'b',
                        'pendiente_de_retrocomisin',
                    ];

                    const isDecommissionRequired = decommissionRequiredStatuses.includes(this.orderToModify.newStatus?.code);

                    //Decomisiones
                    if (this.validateCommissionBlock(this.orderToModify.decommissions, this.orderToModify.errors.decommissions,isDecommissionRequired)) {
                        hasErrors = true;
                    }

                    // Decomisiones de dual electricidad
                    if (this.orderToModify.decommissions?.electricity) {
                        this.orderToModify.errors.decommissions.electricity = {subdomain: null, breakdown: []};

                        if (this.validateCommissionBlock(this.orderToModify.decommissions.electricity, this.orderToModify.errors.decommissions.electricity,isDecommissionRequired)) {
                            hasErrors = true;
                        }
                    }

                    // Decomisiones de dual gas
                    if (this.orderToModify.decommissions?.gas) {
                        this.orderToModify.errors.decommissions.gas = {subdomain: null, breakdown: []};

                        if (this.validateCommissionBlock(this.orderToModify.decommissions.gas, this.orderToModify.errors.decommissions.gas,isDecommissionRequired)) {
                            hasErrors = true;
                        }
                    }

                    //si tiene estado de verificacion cambio de potencia y no tiene puestos
                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg' || this.orderToModify.productType === 'cd') && this.orderToModify.verifications && this.orderToModify.verifications.includes('pc') && !this.orderToModify.currentPotencyVerification ){
                        this.orderToModify.errors.currentPotencyVerification = 'No puede estar vacía';
                        hasErrors = true
                    }

                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg' || this.orderToModify.productType === 'cd') && this.orderToModify.verifications && this.orderToModify.verifications.includes('pc') && !!this.orderToModify.currentPotencyVerification && isNaN(this.orderToModify.currentPotencyVerification)){
                        this.orderToModify.errors.currentPotencyVerification = 'Debe ser numerico';
                        hasErrors = true
                    }

                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg' || this.orderToModify.productType === 'cd') && this.orderToModify.verifications && this.orderToModify.verifications.includes('pc') && !this.orderToModify.requestedPotencyVerification ){
                        this.orderToModify.errors.requestedPotencyVerification = 'No puede estar vacía';
                        hasErrors = true
                    }

                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg' || this.orderToModify.productType === 'cd') && this.orderToModify.verifications && this.orderToModify.verifications.includes('pc') && !!this.orderToModify.requestedPotencyVerification && isNaN(this.orderToModify.requestedPotencyVerification)){
                        this.orderToModify.errors.requestedPotencyVerification = 'Debe ser numerico';
                        hasErrors = true
                    }


                    //Verificaciones si es alta nueva
                    if (this.orderToModify.verifications && this.orderToModify.verifications.includes('nw')){

                        if (this.orderToModify.productType === 'cl') {

                            //Si tarifa es 2.0TD
                            if (this.orderToModify.fee === 'Tarifa 2.0TD'){

                                //miro p1 y p2
                                for (let i = 1; i <= 2; i++){

                                    if (!!this.orderToModify.newRegistrationPeriods && this.orderToModify.newRegistrationPeriods['p' + i] === ''){
                                        this.orderToModify.errors['periodVerification' + i] = 'No puede estar vacía';
                                        hasErrors = true
                                    }

                                    if (!!this.orderToModify.newRegistrationPeriods && isNaN(this.orderToModify.newRegistrationPeriods['p' + i])){
                                        this.orderToModify.errors['periodVerification' + i] = 'Debe ser numerico';
                                        hasErrors = true
                                    }
                                }

                            }else{

                                //miro de p1 a p6
                                for (let i = 1; i <= 6; i++){

                                    if (!!this.orderToModify.newRegistrationPeriods && this.orderToModify.newRegistrationPeriods['p' + i] === ''){
                                        this.orderToModify.errors['periodVerification' + i] = 'No puede estar vacía';
                                        hasErrors = true
                                    }

                                    if (!!this.orderToModify.newRegistrationPeriods && isNaN(this.orderToModify.newRegistrationPeriods['p' + i])){
                                        this.orderToModify.errors['periodVerification' + i] = 'Debe ser numerico';
                                        hasErrors = true
                                    }
                                }

                            }

                        }

                    }


                    //Si el contrato es dual
                    if (this.orderToModify.productType === 'cd'){

                        //Comercializadora
                        if (!this.orderToModify.marketer){
                            this.orderToModify.errors.marketer = this.getErrorMessage('isEmpty');
                            hasErrors = true
                        }

                        //Tarifa de gas
                        if (!this.orderToModify.feeSecondary){
                            this.orderToModify.errors.feeSecondary = this.getErrorMessage('isEmpty');
                            hasErrors = true
                        }

                        //Producto de gas
                        if (!this.orderToModify.productSecondary){
                            this.orderToModify.errors.productSecondary = this.getErrorMessage('isEmpty');
                            hasErrors = true
                        }

                        //CUPS de gas
                        if (!this.orderToModify.CUPSSecondary){
                            this.orderToModify.errors.CUPSSecondary = this.getErrorMessage('isEmpty');
                            hasErrors = true
                        }

                        if (this.orderToModify.CUPSSecondary && this.orderToModify.CUPSSecondary.length !== 20){
                            this.orderToModify.errors.CUPSSecondary = 'CUPS no válido';
                            hasErrors = true
                        }
                    }

                }

                //Documentos de pedido
                this.orderToModify.docs.forEach((doc) => {
                    if (doc.title === '' && doc.defaultTitle)
                        doc.title = doc.defaultTitle
                })

                if (validate && hasErrors) {

                if (this.orderToModify.newStatus.code === 'auditoria_pte_revisar') {
                    this.orderToModify.newStatus.code = 'auditoria_ko';
                }
                else if (this.orderToModify.newStatus.code === 'incidencia_pte_revisar') {
                    this.orderToModify.newStatus.code = 'incidencia_colaborador';
                }
                else if (this.orderToModify.newStatus.code === 'p') {
                    this.orderToModify.newStatus.code = 'bo';
                }

            }




                if (!hasErrors) {


                    this.orderToModify.errors = {};
                    this.orderToModify.lastUpdate = moment().format('YYYY-MM-DD HH:mm:ss');
                    const docsFiles = [];

                    if (this.orderToModify.docs?.length > 0) {
                    this.orderToModify.docs.forEach(doc => {
                        if (doc?.fileValue) {
                        docsFiles.push(doc.fileValue);
                        }
                    });
                    }

                    let data = new FormData();

                    data.append('order', JSON.stringify(this.orderToModify))
                    data.append('userSubdomain', JSON.stringify(this.basicData.userSubdomain));
                    data.append('enterprise', JSON.stringify(this.basicData.enterprise));
                    data.append('colors', JSON.stringify(this.getAllColorVariables()));

                    if (!!this.accountToAddOrder)
                        data.append('account', JSON.stringify(this.accountToAddOrder))





                    //Si es creación
                    if (!this.orderToModify._id) {

                    await axios.post(`/api/orders`, data)
                        .then(async (res) => {

                            const createdOrder =
                                res.data?.order ||
                                res.data?.data ||
                                res.data;

                            const createdId =
                                createdOrder?._id?.$oid ||
                                createdOrder?._id ||
                                res.data?.orderId;

                            let docsUploadError = false;

                            if (createdId) {

                                const fd = new FormData();

                                this.orderToModify.docs?.forEach(doc => {
                                    if (doc?.fileValue instanceof File) {
                                        fd.append('files[]', doc.fileValue);
                                        fd.append('titles[]', doc.title || '');
                                    }
                                });

                                if (fd.has('files[]')) {
                                    try {
                                        await axios.post(
                                            `/api/orders/${createdId}/upload-document`,
                                            fd,
                                            { headers: { 'Content-Type': 'multipart/form-data' } }
                                        );
                                    } catch (e) {
                                        docsUploadError = true;
                                        console.error('Error subiendo documentos:', e);
                                    }
                                }
                            }

                            const emailError = !!res.data?.emailError;

                            this.orderToModify = JSON.parse(JSON.stringify(this.order));
                            this.accountToAddOrder = '';
                            this.isCreatingOrder = false;
                            this.fetchAllOrders();
                            this.closeWindow();

                            if (docsUploadError || emailError) {

                                let text = '';

                                if (docsUploadError && emailError) {
                                    text = 'El contrato se ha guardado, pero algunos documentos no se han adjuntado correctamente y no se ha podido enviar el correo a los agentes.';
                                } else if (docsUploadError) {
                                    text = 'El contrato se ha guardado, pero algunos documentos no se han adjuntado correctamente.';
                                } else {
                                    text = 'El contrato se ha guardado, pero no se ha podido enviar el correo a los agentes.';
                                }

                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Aviso',
                                    text,
                                });

                                return;
                            }

                            Swal.fire({
                                icon: 'success',
                                title: '¡Contrato creado!',
                                timer: 1500,
                                timerProgressBar: true
                            });

                        })
                        .catch((err) => {
                            if (err.response?.data?.error == 'El CUPS ya existe en otro contrato.') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'El CUPS ya existe en otro contrato.\nPara más información contacta con ' + (this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2' ? 'Att Colaborador' : ('soporte: ' + this.basicData.userSubdomain.email))
                                });
                            }

                            this.isCreatingOrder = false;
                        });

                } else {

                    await axios.post(`/api/orders/${this.orderToModify._id}`, data)
                        .then(async (res) => {

                            let docsUploadError = false;

                            const fd = new FormData();

                            this.orderToModify.docs?.forEach(doc => {
                                if (doc?.fileValue instanceof File) {
                                    fd.append('files[]', doc.fileValue);
                                    fd.append('titles[]', doc.title || '');
                                }
                            });

                            if (fd.has('files[]')) {
                                try {
                                    await axios.post(
                                        `/api/orders/${this.orderToModify._id}/upload-document`,
                                        fd,
                                        { headers: { 'Content-Type': 'multipart/form-data' } }
                                    );
                                } catch (e) {
                                    docsUploadError = true;
                                    console.error('Error subiendo documentos:', e);
                                }
                            }

                            const emailError = !!res.data?.emailError;

                            this.orderToModify = JSON.parse(JSON.stringify(this.order));
                            this.accountToAddOrder = '';
                            this.isCreatingOrder = false;
                            this.fetchAllOrders();
                            this.closeWindow();

                            if (docsUploadError || emailError) {

                                let text = '';

                                if (docsUploadError && emailError) {
                                    text = 'El contrato se ha guardado, pero algunos documentos no se han adjuntado correctamente y no se ha podido enviar el correo a los agentes.';
                                } else if (docsUploadError) {
                                    text = 'El contrato se ha guardado, pero algunos documentos no se han adjuntado correctamente.';
                                } else {
                                    text = 'El contrato se ha guardado, pero no se ha podido enviar el correo a los agentes.';
                                }

                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Aviso',
                                    text,
                                });

                            } else {

                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Contrato actualizado!',
                                    timer: 1500,
                                    timerProgressBar: true
                                });
                            }

                        })
                        .catch((err) => {
                            if (err.response?.data?.error == 'El CUPS ya existe en otro contrato.') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'El CUPS ya existe en otro contrato.\nPara más información contacta con ' + (this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2' ? 'Att Colaborador' : ('soporte: ' + this.basicData.userSubdomain.email))
                                });
                            }

                            this.isCreatingOrder = false;
                        });
                }

                } else {
                    this.isCreatingOrder = false;
                }
            }
        },
        validateCommissionValue(value, required = false) {
            if (value === null || value === undefined || value === '') {
                //Solo para decomisiones
                if (required) {
                    return {
                        isValid: false,
                        value: null,
                        error: 'Establece una decomisión para la baja',
                    };
                }

                return {
                    isValid: true,
                    value: null,
                    error: null,
                };
            }

            const normalizedValue = value.toString().replace(',', '.').trim();
            const parsedValue = Number(normalizedValue);

            if (Number.isNaN(parsedValue) || !Number.isFinite(parsedValue)) {
                return {
                    isValid: false,
                    value: null,
                    error: `La ${required ? 'de' : ''}comisión debe ser de tipo numérico`,
                };
            }

            if (parsedValue < 0) {
                return {
                    isValid: false,
                    value: null,
                    error: `La ${required ? 'de' : ''}comisión debe ser un número positivo`,
                };
            }

            return {
                isValid: true,
                value: parsedValue,
                error: null,
            };
        },
        validateCommissionBlock(commissionBlock, errorsBlock, required = false) {
            let blockHasErrors = false;

            if (!commissionBlock) {
                return false;
            }

            errorsBlock.subdomain = null;
            errorsBlock.breakdown = {};

            const subdomainValidation = this.validateCommissionValue(commissionBlock.subdomain, required);

            if (!subdomainValidation.isValid) {
                errorsBlock.subdomain = subdomainValidation.error;
                blockHasErrors = true;
            } else {
                commissionBlock.subdomain = subdomainValidation.value;
            }

            if (commissionBlock.breakdown?.length) {
                commissionBlock.breakdown.forEach((item, index) => {
                    const commissionValidation = this.validateCommissionValue(item.commission, required);

                    if (!commissionValidation.isValid) {
                        errorsBlock.breakdown[item.id] = commissionValidation.error;
                        blockHasErrors = true;
                        return;
                    }

                    item.commission = commissionValidation.value;
                });
            }

            return blockHasErrors;
        },
        deleteOrder(order) {
            Swal.fire({
                icon: 'warning',
                title: '¿Estas seguro?',
                text: 'Si sigues con esta acción no podras revertirla',
                confirmButtonText: 'Sí',
                showConfirmButton: true,
                cancelButtonText: 'No',
                showCancelButton: true
            }).then((res) => {
                if (res.isConfirmed) {

                    axios.delete(`/api/orders/${order['_id']}`, {
                        params: {
                            userSubdomain: this.basicData.userSubdomain._id
                        }
                    })
                        .then((res) => {

                            //Recargo los pedidos de nuevo
                            this.fetchAllOrders()

                            //Muestro mensaje
                            Swal.fire({
                                icon: 'success',
                                title: '¡Contrato borrado!',
                                timer: 1500,
                                timerProgressBar: true
                            })

                        })
                        .catch((err) => {
                            console.log(err)
                        })
                }
            })
        },
        getAllColorVariables() {
            const colorMap = {};

            for (const sheet of document.styleSheets) {
                // Evita errores con hojas externas (CORS)
                try {
                    if (!sheet.cssRules) continue;

                    for (const rule of sheet.cssRules) {
                        if (rule.selectorText === ':root') {
                            const style = rule.style;

                            for (const prop of style) {
                                if (prop.startsWith('--color-')) {
                                    const key = prop.replace('--color-', '');
                                    const value = style.getPropertyValue(prop).trim();
                                    colorMap[key] = value;
                                }
                            }
                        }
                    }
                } catch (e) {
                    // Hoja de estilos externa no accesible por CORS, la ignoramos
                    continue;
                }
            }

            return colorMap;
        },
        addSelectType(data) {

            let type = data.type;
            let value = data.value;

            axios.post(`/api/select`, { type: type, value: value })
                .then((res) => {
                    //Añado al array para tenerlo en cliente
                    this.selectValues[type].push(value)

                    //Selecciono el valor y borro
                    if (type === 'acc')
                        this.account.accType = value
                    else if (type === 'sector')
                        this.account.sector = value
                    else
                        this.account.origin = value
                })
                .catch((err) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ya existe',
                        text: 'El elemento que estas intentando crear ya existe'
                    })
                })
        },
        delSelectType(data) {

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
                                if (type === 'acc') {
                                    if (this.account.accType === value) this.account.accType = ''
                                } else if (type === 'sector') {
                                    if (this.account.sector === value) this.account.sector = ''
                                } else {
                                    if (this.account.origin === value) this.account.origin = ''
                                }


                            })
                            .catch((err) => {
                                console.log(err)
                            })
                    }
                })
            }
        },
        selectElement(data) {
            let type = data.type;
            let value = data.value;

            let arrInd = type

            if (type === 'acc') arrInd = 'accType'

            this.account[arrInd] = value
        },
        openOrderWindow(order) {
            this.orderToModify = order;

            this.accountToUpdateOrder = order.account;
        },
        closeWindow() {
            //quito toda la info de order
            this.orderToModify = JSON.parse(JSON.stringify(this.order));
            this.accountToAddOrder = ''
            this.accountToUpdateOrder = ''
            this.isSeeingOrderComponent = false;

            if (this.$route.query._id) {
                const query = { ...this.$route.query }
                delete query._id

                this.$router.replace({ query })
            }
        },
        selectOrderType(orderType) {
            //Recorro los tipos de ordenacion y los pongo todos a false menos el que se ha seleccionado
            /*let cookies = this.$cookies.get('filters');

            cookies['orders']['sortBy'] = orderType.value

            this.$cookies.set('filters', cookies);

            this.isSeeingFiltersPc.sort = false;*/

            this.filters.radio.sortBy.checked = orderType.value

            this.getOrderTypeSelected()

            //Recargo los pedidos
            this.fetchAllOrders();
        },
        selectViewType(viewType) {
            this.isSeeingFiltersPc.view = false;

            this.filters.radio.view.checked = viewType

            this.getViewTypeSelected()

            //Recargo los pedidos
            this.fetchAllOrders();
        },
        selectWithoutCommisionType(type) {
            //Recorro los tipos de ordenacion y los pongo todos a false menos el que se ha seleccionado
            let localData = JSON.parse(sessionStorage.getItem('filters'))

            //AQUI
            localData['orders']['withoutCommision'] = type

            sessionStorage.setItem("filters", JSON.stringify(localData))

            this.isSeeingFiltersPc.sort = false;

            this.isSeeingFiltersPc.withoutCommision = false;

            this.filters.radio.withoutCommision.checked = type

            this.getWithoutCommisionTypeSelected()

            //Recargo los pedidos
            this.fetchAllOrders();
        },
        selectSubdomainType(type) {
            //Recorro los tipos de ordenacion y los pongo todos a false menos el que se ha seleccionado
            let localData = JSON.parse(sessionStorage.getItem('filters'))

            //AQUI
            localData['orders']['otherSubdomains'] = type

            sessionStorage.setItem("filters", JSON.stringify(localData))

            this.isSeeingFiltersPc.sort = false;

            this.isSeeingFiltersPc.otherSubdomains = false;

            this.filters.radio.otherSubdomains.checked = type

            //Recargo los pedidos
            this.fetchAllOrders();
        },
        selectNewOrderType(orderType) {

            //Recorro los tipos de ordenacion y los pongo todos a false menos el que se ha seleccionado
            const defaultSortType = this.isFidelitySubdomain ? 26 : 17;

            switch (orderType) {

                case 'title':

                    if (this.filters.radio.sortBy.checked !== 0 && this.filters.radio.sortBy.checked !== 1)
                        this.filters.radio.sortBy.checked = 0
                    else if (this.filters.radio.sortBy.checked === 0)
                        this.filters.radio.sortBy.checked = 1
                    else if (this.filters.radio.sortBy.checked === 1)
                        this.filters.radio.sortBy.checked = defaultSortType

                    break;

                case 'agent':

                    if (this.filters.radio.sortBy.checked !== 2 && this.filters.radio.sortBy.checked !== 3)
                        this.filters.radio.sortBy.checked = 2
                    else if (this.filters.radio.sortBy.checked === 2)
                        this.filters.radio.sortBy.checked = 3
                    else if (this.filters.radio.sortBy.checked === 3)
                        this.filters.radio.sortBy.checked = defaultSortType

                    break;

                case 'nif':

                    if (this.filters.radio.sortBy.checked !== 4 && this.filters.radio.sortBy.checked !== 5)
                        this.filters.radio.sortBy.checked = 4
                    else if (this.filters.radio.sortBy.checked === 4)
                        this.filters.radio.sortBy.checked = 5
                    else if (this.filters.radio.sortBy.checked === 5)
                        this.filters.radio.sortBy.checked = defaultSortType

                    break;

                case 'fee':

                    if (this.filters.radio.sortBy.checked !== 6 && this.filters.radio.sortBy.checked !== 7)
                        this.filters.radio.sortBy.checked = 6
                    else if (this.filters.radio.sortBy.checked === 6)
                        this.filters.radio.sortBy.checked = 7
                    else if (this.filters.radio.sortBy.checked === 7)
                        this.filters.radio.sortBy.checked = defaultSortType

                    break;

                case 'product':

                    if (this.filters.radio.sortBy.checked !== 8 && this.filters.radio.sortBy.checked !== 9)
                        this.filters.radio.sortBy.checked = 8
                    else if (this.filters.radio.sortBy.checked === 8)
                        this.filters.radio.sortBy.checked = 9
                    else if (this.filters.radio.sortBy.checked === 9)
                        this.filters.radio.sortBy.checked = defaultSortType

                    break;

                case 'cups':

                    if (this.filters.radio.sortBy.checked !== 10 && this.filters.radio.sortBy.checked !== 11)
                        this.filters.radio.sortBy.checked = 10
                    else if (this.filters.radio.sortBy.checked === 10)
                        this.filters.radio.sortBy.checked = 11
                    else if (this.filters.radio.sortBy.checked === 11)
                        this.filters.radio.sortBy.checked = defaultSortType

                    break;

                case 'status':

                    if (this.filters.radio.sortBy.checked !== 12 && this.filters.radio.sortBy.checked !== 13)
                        this.filters.radio.sortBy.checked = 12
                    else if (this.filters.radio.sortBy.checked === 12)
                        this.filters.radio.sortBy.checked = 13
                    else if (this.filters.radio.sortBy.checked === 13)
                        this.filters.radio.sortBy.checked = defaultSortType

                    break;

                case 'lastStatusAt':

                    if (this.filters.radio.sortBy.checked !== 14 && this.filters.radio.sortBy.checked !== 15)
                        this.filters.radio.sortBy.checked = 14
                    else if (this.filters.radio.sortBy.checked === 14)
                        this.filters.radio.sortBy.checked = 15
                    else if (this.filters.radio.sortBy.checked === 15)
                        this.filters.radio.sortBy.checked = defaultSortType


                    break;


                case 'createdAt':
                case 'lastUpdate':

                    if (this.filters.radio.sortBy.checked !== 16 && this.filters.radio.sortBy.checked !== 17)
                        this.filters.radio.sortBy.checked = 16
                    else if (this.filters.radio.sortBy.checked === 16)
                        this.filters.radio.sortBy.checked = 17
                    else if (this.filters.radio.sortBy.checked === 17)
                        this.filters.radio.sortBy.checked = 16

                    break;

                case 'activationDate':

                    if (this.filters.radio.sortBy.checked !== 18 && this.filters.radio.sortBy.checked !== 19)
                        this.filters.radio.sortBy.checked = 18
                    else if (this.filters.radio.sortBy.checked === 18)
                        this.filters.radio.sortBy.checked = 19
                    else if (this.filters.radio.sortBy.checked === 19)
                        this.filters.radio.sortBy.checked = defaultSortType

                    break;
                
                case 'transferDate':

                    if (this.filters.radio.sortBy.checked !== 28 && this.filters.radio.sortBy.checked !== 29)
                        this.filters.radio.sortBy.checked = 28
                    else if (this.filters.radio.sortBy.checked === 28)
                        this.filters.radio.sortBy.checked = 29
                    else if (this.filters.radio.sortBy.checked === 29)
                        this.filters.radio.sortBy.checked = defaultSortType

                    break;

                case 'id':

                    if (this.filters.radio.sortBy.checked !== 26 && this.filters.radio.sortBy.checked !== 27)
                        this.filters.radio.sortBy.checked = 26
                    else if (this.filters.radio.sortBy.checked === 26)
                        this.filters.radio.sortBy.checked = 27
                    else if (this.filters.radio.sortBy.checked === 27)
                        this.filters.radio.sortBy.checked = 26

                    break;
            }

            this.isSeeingFiltersPc.sort = false;

            //this.getOrderTypeSelected()

            //Recargo los pedidos
            this.fetchAllOrders();

        },
        toggleVisibility(product, type) {

            //si es activar agente hay que activar toda su red
            if (type === 'agent' && !product.active) {
                this.activateAgentAndSubordinates(product.code);
            } else {
                product.active = !product.active
            }

            //si es comercializadora deseleccionar todos tipos de productos menos cl y cg
            /*if (type === 'marketers'){
                this.filtersFiltered.productTypes.forEach((pType) =>{
                    if (pType.code !== 'cl' && pType.code !== 'cg')
                        pType.active = false
                })
            }*/


            this.fetchAllOrders()
        },
        toggleAllVisibility(type) {

            let changeTo = false;

            switch (type) {
                case 'productsMarketer':
                    changeTo = !this.areAllProductsMarketerActives;

                    this.filtersObtained.products.forEach(prod => {
                        prod.active = changeTo;
                    });

                    break;

                case 'productTypes':
                    changeTo = !this.areAllProductsTypesActives;

                    this.filtersObtained.productTypes.forEach(prod => {
                        prod.active = changeTo;
                    });

                    break;

                case 'agents':
                    changeTo = !this.areAllAgentsActives;

                    this.filtersObtained.agents.forEach(agent => {
                        agent.active = changeTo;
                    });
                    break;

                case 'marketers':
                    changeTo = !this.areAllMarketersActives;

                    this.filtersObtained.marketers.forEach(marketer => {
                        marketer.active = changeTo;
                    });

                    //Cuando cambien las comercializadoras se estableceran los productos
                    //this.setComputedFiltered()

                    break;

                case 'statuses':
                    changeTo = !this.areAllStatusesActives;

                    this.filtersObtained.statuses.forEach(status => {
                        status.active = changeTo;
                    });
                    break;

                case 'fees':
                    changeTo = !this.areAllFeesActives;

                    this.filtersObtained.fees.forEach(fee => {
                        fee.active = changeTo;
                    });
                    break;
            }

            this.fetchAllOrders()
        },
        activateAgentAndSubordinates(agentId) {

            // Encontrar el agente en filtersFiltered.agents por _id
            let agent = this.filtersObtained.agents.find(a => a.code === agentId);

            //Si no existe en los filtros busco en la lista completa
            if (!agent)
                agent = this.basicData.userListComplete.filter(u => u._id === agentId);

            if (!!agent) {
                // Activar el agente
                if (!agent.active) agent.active = true;

                // Buscar y activar recursivamente todos los agentes que tienen como responsable al agente actual
                let subordinates = this.basicData.userListComplete.filter(u => u.responsibles.includes(agentId));

                subordinates.forEach(subordinate => {
                    this.activateAgentAndSubordinates(subordinate._id);
                });
            }
        },
        setComputedFiltered() {


            this.filters.checkbox.productMarketerAvailable.data = [];

            this.orders.forEach((order) => {

                //PARA PRODUCTOS

                //Compruebo si la comercializadora existe y esta activa
                if (order.marketer === '') order.marketer = 'Sin comercializadora'

                let existsAndActiveMarketer = this.filters.checkbox.marketerAvailable.data.some((label) => ((label.title === order.marketer) && label.active))


                //si la comercializadora existe en el array de filtros y esta activa se comprobará para poner su producto
                if (existsAndActiveMarketer) {

                    let existsProduct = this.filters.checkbox.productMarketerAvailable.data.some(label => label.title === order.product)

                    if (!existsProduct) {

                        let productType = {
                            title: order.product,
                            active: true
                        }

                        this.filters.checkbox.productMarketerAvailable.data.push(productType);
                    }
                }

                //ORDENO LOS PRODUCTOS
                this.filters.checkbox.productMarketerAvailable.data.sort((a, b) => a.title.localeCompare(b.title));
            })

        },
        toggleSelectAllOrders() {

            let orders = this.orders

            //Si estan todos seleccionado los deselecciono, sino los selecciono todos
            if (this.ordersSelected && this.ordersSelected.length === orders.length) {
                //Todos estan seleccionados asi q los deselecciono
                this.ordersSelected = [];
            } else {
                //Selecciono todos, mirando no meter otra vez los que ya estan metidos
                orders.forEach((order) => {

                    let id = order._id.$oid

                    if (this.ordersSelected.indexOf(order._id.$oid) === -1) this.ordersSelected.push(order._id.$oid);
                })
            }
        },
        toggleSelectOrder(order) {

            let id = (order._id.$oid ? order._id.$oid : order._id)

            //Compruebo si esta seleccionado o no
            if (this.ordersSelected.includes(id)) {
                let index = this.ordersSelected.indexOf(id);
                this.ordersSelected.splice(index, 1);
            } else {
                this.ordersSelected.push(id)
            }

        },
        seeOrderInfo(order) {

            //Compruebo si se esta clicando uno que ya se esta viendo
            if (this.orderSelectedToSee && (((typeof this.orderSelectedToSee._id === 'string') ? this.orderSelectedToSee._id : this.orderSelectedToSee._id.$oid) === ((typeof order._id === 'string') ? order._id : order._id.$oid))) {
                this.orderSelectedToSee = ''
            } else {
                this.orderSelectedToSee = order;
            }
        },
        /*deleteAllSelectedOrders(){
            Swal.fire({
                icon: 'warning',
                title: '¿Estas seguro?',
                text: 'Si sigues con esta acción no podras revertirla',
                confirmButtonText: 'Sí',
                showConfirmButton: true,
                cancelButtonText: 'No',
                showCancelButton: true
            }).then((res) => {
                if (res.isConfirmed){


                    //Saco los pedidos
                    let ordersToRemove = this.orders.filter(order => this.ordersSelected.includes(order._id.$oid));


                    axios.delete('/api/orders/deleteAllSelected', {
                        params:{
                            ordersToRemove
                        }
                    })
                        .then((res) => {
                            //Borro esos usuarios de cliente
                            this.orders = this.orders.filter(order => !this.ordersSelected.includes(order._id.$oid))

                            this.ordersSelected = [];

                            //Establezco los filtros de nuevo
                            this.setFilters()
                        })
                        .catch((err) => {
                            console.log(err)
                        })
                }
            })
        },*/
        getStatus(order) {


            //Saco el ultimo estado del pedido
            let recentStatus = order.statuses.reduce((latest, current) => {
                return new Date(current.date) > new Date(latest.date) ? current : latest;
            });

            return this.basicData.userSubdomain.statuses.find((status) => {
                return status.code === recentStatus.code
            })
        },
        getProductType(order) {
            return this.productTypes.find((type) => {
                return type.code === order.productType
            })
        },
        getDateFormatted(date) {
            date = moment(date)

            return date.format('DD/MM/YYYY')
        },
        getActivationDate(order) {

            if (!order.activationDate)
                return '-'

            let dateNow = new Date(order.activationDate);
            let day = String(dateNow.getDate()).padStart(2, '0'); // Asegura que el día tenga dos dígitos
            let month = String(dateNow.getMonth() + 1).padStart(2, '0'); // Asegura que el mes tenga dos dígitos y se suma 1 porque en JavaScript los meses van de 0 a 11
            let year = dateNow.getFullYear();
            return `${day}/${month}/${year}`;
        },
        getErrorMessage(type) {

            let message = '';

            switch (type) {
                case 'isEmpty':
                    message = 'Este campo no puede estar vacio'
                    break;

                case 'malformedEmail':
                    message = 'El email esta mal formado';
                    break;

                case 'malformedPhone':
                    message = 'El número de telefono esta mal formado';
                    break;

                case 'onlyNumbers':
                    message = 'Solo puede contener dígitos';
                    break;

                default:
                    message = 'Hay errores en el formulario'
                    break;
            }

            return message;
        },
        selectAccToCreateOrder(id) {

            axios.get(`api/accounts/${id}`).then(res => {
                this.accountToAddOrder = res.data.account;
                this.orderToModify = JSON.parse(JSON.stringify(this.order));

                if (this.isFidelitySubdomain) {
                    this.orderToModify.name = this.accountToAddOrder?.name || '';
                    this.orderToModify.accountCIF = this.accountToAddOrder?.CIF || '';
                }
                //Abro la ventana de creación de contrato
                this.isSeeingOrderComponent = true;

                //Cierro to el seleccionable de creación
                this.isSeeingCreateOrder = false;
                this.typeOfCreate = null;

            })
        },
        seeFilters(type) {

            //Cerrar selects
            this.hideCustomSelects()

            switch (type) {
                case 'agent':
                    this.isSeeingFiltersPc.agent = true;
                    break;

                case 'renewalPendingDates':
                    this.isSeeingFiltersPc.renewalPendingDate = true;
                    break;

                case 'status':
                    this.isSeeingFiltersPc.status = true;
                    break;

                case 'marketer':
                    this.isSeeingFiltersPc.marketer = true;
                    break;

                case 'productMarketer':
                    this.isSeeingFiltersPc.productMarketer = true;
                    break;

                case 'productType':
                    this.isSeeingFiltersPc.productTypes = true;
                    break;

                case 'dates':
                    this.isSeeingFiltersPc.dates = true;
                    break;

                case 'activationDates':
                    this.isSeeingFiltersPc.activationDate = true;
                    break;

                case 'lowDates':
                    this.isSeeingFiltersPc.lowDate = true;
                    break;

                case 'fees':
                    this.isSeeingFiltersPc.fees = true;
                    break;

                case 'view':
                    this.isSeeingFiltersPc.view = true;
                    break;

                case 'withoutCommision':
                    this.isSeeingFiltersPc.withoutCommision = true;
                    break;

                case 'otherSubdomains':
                    this.isSeeingFiltersPc.otherSubdomains = true;
                    break;

                case 'sort':
                    this.isSeeingFiltersPc.sort = true;
                    break;
            }
        },
        hideCustomSelects() {

            //Cierro el select de crear pedido y de carga masiva
            this.isSeeingCreateOrder = false;
            this.isSeeingMassiveLoad = false;
            this.typeOfCreate = null;

            //Cierro todos los filtros
            this.isSeeingFiltersPc = {
                agent: false,
                status: false,
                marketer: false,
                product: false,
                dates: false,
                sort: false
            }
        },
        removeAccents(str) {
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        },
        actionLink(route) {
            this.$router.push(route)
        },
        activeEditing() {
            this.isEditing = true
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
        pickupDumpFile(event) {
            let file = event.target.files[0];
            if (file) {
                this.dumpOrders(file)
            }
        },
        async dumpOrders(file) {
            this.hideCustomSelects();
            this.isLoadingMassiveLoad = true;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('userSubdomain', JSON.stringify(this.basicData.userSubdomain));
            formData.append('userLogged', JSON.stringify(this.basicData.userLogged));
            formData.append('userList', JSON.stringify(this.basicData.userList));
            formData.append('enterprise', JSON.stringify(this.basicData.enterprise));
            formData.append('colors', JSON.stringify(this.getAllColorVariables()));

            try {
                const res = await axios.post('/api/orders/dumpOrders', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
                responseType: 'blob',
                validateStatus: s => s < 500
                });

                const ct = (res.headers['content-type'] || '').toLowerCase();

                if (ct.includes('application/vnd.openxmlformats') || ct.includes('spreadsheetml')) {
                const blob = new Blob([res.data], { type: ct });
                const url = window.URL.createObjectURL(blob);

                const a = document.createElement('a');
                a.href = url;
                a.download = 'import_errores.xlsx';
                a.click();
                window.URL.revokeObjectURL(url);

                Swal.fire({
                    icon: "warning",
                    title: "HAY INCIDENCIAS",
                    html: `
                    <div style="text-align:left">
                        <p>La importación tiene incidencias.</p>
                        <p>Revisa el archivo descargado.</p>
                    </div>
                    `
                });

                this.fetchAllOrders();
                return;
                }

                const text = await res.data.text();
                let data = {};
                try { data = JSON.parse(text); } catch { data = {}; }

                const failed = Array.isArray(data?.failedRows) ? data.failedRows : [];

                this.fetchAllOrders();

                if (failed.length === 0) {
                Swal.fire({
                    icon: "success",
                    title: "Importación completada",
                    text: "La importación se ha realizado correctamente.",
                    timer: 1600,
                    timerProgressBar: true
                });
                return;
                }

                Swal.fire({
                icon: "warning",
                title: "HAY INCIDENCIAS",
                html: `
                    <div style="text-align:left">
                    <p>La importación tiene incidencias.</p>
                    <p>Revisa el archivo descargado.</p>
                    </div>
                `
                });

            } catch (err) {
                let msg = err?.message || 'Se produjo un error realizando la importación.';

                try {
                if (err?.response?.data) {
                    const t = await err.response.data.text();
                    const j = JSON.parse(t);
                    msg = j?.error || msg;
                }
                } catch {}

                Swal.fire({
                icon: 'error',
                title: 'Error al importar',
                text: msg,
                });

            } finally {
                this.isLoadingMassiveLoad = false;
                if (this.$refs?.inputExcel) this.$refs.inputExcel.value = null;
            }
        },
        getEmailsToMassive() {
            //Saco los correos de las cuentas sin duplicar
            this.fetchAllOrdersAll(true, true)
                .then(() => {

                    //Saco los correos de las cuentas y quito los duplicados
                    let emails = this.ordersAll
                        .flatMap(order => order.usersEmails || [])
                        .filter((email, index, self) => self.indexOf(email) === index && email !== '');

                    //Creo en el storage y lo mando
                    localStorage.setItem(
                        'emailsTemporaly',
                        JSON.stringify(emails)
                    );


                    //Redirijo a masivo de correos
                    this.$router.push('/tools?section=massiveEmail&withFilters=true')
                });
        },
        async selectOrderToSee(order) {
            //Pongo la ruta con el parámetro del nuevo
            this.$router.push({
                path: '/contracts',
                query: { _id: order._id }
            })

            this.orderToModify = order

            //Saco la cuenta para copiar los datos
            this.accountToAddOrder = order.account ? (await axios.get('/api/accounts/' + order.account)
                    .then(res => res.data.account)
                    .catch(err => {
                        console.log(err)
                    }))
                : null

            //Saco el último estado del contrato
            let recentStatus = this.orderToModify.statuses.reduce((latest, current) => {
                return new Date(current.date) > new Date(latest.date) ? current : latest;
            }, { date: '1970-01-01T00:00:00Z' });

            this.orderToModify.newStatus = {
                code: recentStatus.code,
                date: recentStatus.date
            }

            this.isSeeingOrderComponent = true;
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
        goToContract(order) {
            const id = order._id.$oid ? order._id.$oid : order._id;

            this.actionLink('/contracts?_id=' + id);

            // Espera a que cambie la vista y luego hace scroll arriba
            this.$nextTick(() => {
                setTimeout(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }, 100);
            });
        },
        renewalOrder(newOrder) {
            this.orderToModify = newOrder
            this.isEditingOrder = true
        },
        getMarketerLogo(orderMarketer) {
            if (!orderMarketer || !this.marketers || this.marketers.length === 0) {
                return null;
            }

            const normalize = (value) => {
                return String(value || "")
                    .trim()
                    .toLowerCase()
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "");
            };

            const marketerFound = this.marketers.find((marketer) => {
                return normalize(marketer.name) === normalize(orderMarketer);
            });

            if (!marketerFound || !marketerFound.logo) {
                return null;
            }

            return "/assets/marketers_logo/" + marketerFound.logo;
        },
    },
    computed: {

        isFidelitySubdomain() {
            return this.basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2';
        },

        showDirect() {
            if (this.basicData?.userSubdomain?._id !== "68d260e6bc9e8c38f8003df2" && this.basicData?.userSubdomain?._id !== "6909faa9232c09035a03f3b2") {
                return true;

            } else {
                return false;
            }
        },
        getPrettyRenewalPendingDatesFilters() {
            const r = this.filtersObtained?.renewalPendingDates || {};
            const s = r.start || '';
            const e = r.end || '';
            const fmt = d => (this.prettyDate ? this.prettyDate(d) : d);

            if (!s && !e) return 'Selecciona fechas';
            if (s && e)   return `${fmt(s)} — ${fmt(e)}`;
            if (s)        return `Desde ${fmt(s)}`;
            return `Hasta ${fmt(e)}`;
        },
        profitability() {
            let result = (this.summaryData.subdomainCommission - this.summaryData.agentCommission);
            if (isNaN(result)) result = 0;
            return Math.round(result);
        },
        profitabilityPercent() {
            let result = this.profitability / (this.summaryData.subdomainCommission) * 100;
            if (isNaN(result)) result = 0;
            return Math.round(result);
        },
        totalConsumption() {
            let consumption = this.summaryData.totalConsumption ?? 0;
            let magnitude = " kWh";
            if (consumption >= 1000000) {
                consumption /= 1000;
                magnitude = " MWh";
            }
            return Math.round(consumption).toLocaleString("es-ES") + magnitude;
        },
        filteredOrders() {

            return []

            if (!this.orders || this.orders.length === 0) return []

            let ordersFiltered = []

            let orders = JSON.parse(JSON.stringify(this.orders))


            //Filtro segun sea comisionado o no
            orders = orders.filter((order) => {
                return this.partSeeing === 0 ? (order.liquidationStatus === 'nl' || order.liquidationStatus == undefined) : (order.liquidationStatus === 'al' || order.liquidationStatus === 'cl' || order.liquidationStatus === 'tl')
            })

            //filtro
            for (let key in orders) {

                let order = orders[key];

                //Para filtrar por la busqueda saco el titulo de el estado, el producto
                let recentStatus = order.statuses.reduce((latest, current) => {
                    return new Date(current.date) > new Date(latest.date) ? current : latest;
                });

                let status = this.basicData.userSubdomain.statuses.find((status) => status.code === recentStatus.code);

                let product = this.productTypes.find((product) => product.code === order.productType);

                let OrderSearch = this.removeAccents([order.name, order.direc, order.town, order.province, status.title, order.product, order.marketer, order.phone, order.CUPS, order.CIF].join('').replaceAll(' ', '').toLowerCase());

                //Compruebo si su etiqueta esta visible o no
                let hasProductTypeVisible = false;
                let hasProductMarketerVisible = false;
                let hasAgentVisible = false;
                let hasMarketersVisible = false;
                let hasStatusVisible = false;

                //Tipo de producto
                this.filters.checkbox.productTypeAvailable.data.find((productType) => {

                    //Si es la etiqueta del usuario y esta activa
                    if ((productType.title === product.title) && productType.active)
                        hasProductTypeVisible = true;
                })

                //Agente
                this.filters.checkbox.agentAvailable.data.find((agent) => {
                    if ((agent.title === order.agent) && agent.active)
                        hasAgentVisible = true;
                })


                //Comercializadora
                if (order.marketer === '') order.marketer = 'Sin comercializadora'

                this.filters.checkbox.marketerAvailable.data.find((marketer) => {
                    if ((marketer.title === order.marketer) && marketer.active)
                        hasMarketersVisible = true;
                })

                //Producto de comercializadora
                this.filters.checkbox.productMarketerAvailable.data.find((marketerProduct) => {
                    if ((marketerProduct.title === order.product) && marketerProduct.active)
                        hasProductMarketerVisible = true;
                })


                this.filters.checkbox.statusAvailable.data.find((statusNow) => {
                    if ((statusNow.title === status.title) && statusNow.active)
                        hasStatusVisible = true;
                })


                //Por fechas ( en vez de hacer un intervalo entero lo voy a hacer dividido, desde y hasta)
                let afterFirstDate = true;
                let beforeLastDate = true;
                let orderDate = moment(order.createdAt)

                //Pedidos desde
                if (this.filtersObtained.dates.start) {
                    let startDate = moment(this.filtersObtained.dates.start)

                    if (orderDate.isBefore(startDate)) afterFirstDate = false
                }

                //Pedidos hasta
                if (this.filtersObtained.dates.end) {
                    let endDate = moment(this.filtersObtained.dates.end)

                    if (orderDate.isAfter(endDate)) beforeLastDate = false
                }


                //Por fecha de activación
                let activationAfterFirstDate = true;
                let activationBeforeLastDate = true;
                let orderActivationDate = moment(order.activationDate)

                //Pedidos desde
                if (this.filtersObtained.activationDates.start) {
                    let startDate = moment(this.filtersObtained.activationDates.start)

                    if (orderActivationDate.isBefore(startDate)) activationAfterFirstDate = false
                }

                //Pedidos hasta
                if (this.filtersObtained.activationDates.end) {
                    let endDate = moment(this.filtersObtained.activationDates.end)

                    if (orderActivationDate.isAfter(endDate)) activationBeforeLastDate = false
                }


                if (OrderSearch.includes(this.removeAccents(this.searchOrderText.replaceAll(' ', '').toLowerCase())) && hasProductTypeVisible && hasProductMarketerVisible && hasAgentVisible && hasMarketersVisible && hasStatusVisible && afterFirstDate && beforeLastDate && activationAfterFirstDate && activationBeforeLastDate) ordersFiltered.push(order)
            }


            //ordeno
            if (ordersFiltered.length > 0) {
                switch (this.filters.radio.sortBy.checked) {

                    case 0:
                        //por nombre (A-Z)
                        ordersFiltered = ordersFiltered.sort((a, b) => {

                            if (a.name < b.name) return -1;
                            if (a.name > b.name) return 1;
                            return 0;
                        });

                        break;

                    case 1:
                        // por nombre (Z-A)
                        ordersFiltered = ordersFiltered.sort((a, b) => {
                            let aFullName = a.firstName + a.lastName
                            let bFullName = b.firstName + b.lastName

                            if (a.name < b.name) return 1;
                            if (a.name > b.name) return -1;
                            return 0;
                        });

                        break;

                    case 2:
                        // más antiguo
                        ordersFiltered = ordersFiltered.sort((a, b) => {

                            let aDate = new Date(a.createdAt);
                            let bDate = new Date(b.createdAt);

                            if (aDate < bDate) return -1;
                            if (aDate > bDate) return 1;

                            return 0;
                        });

                        break;

                    default:
                        //más reciente
                        ordersFiltered = ordersFiltered.sort((a, b) => {

                            let aDate = new Date(a.createdAt);
                            let bDate = new Date(b.createdAt);

                            if (aDate < bDate) return 1;
                            if (aDate > bDate) return -1;

                            return 0;
                        });

                        break;
                }
            }

            return ordersFiltered;
        },
        getProductTypeFilterTitle() {

            let totalActives = 0;

            if (!this.filtersObtained.productTypes) return '0 productos'

            this.filtersObtained.productTypes.forEach((account) => {
                if (account.active) totalActives++;
            })

            return totalActives === this.filtersObtained.productTypes.length ? 'Todos' : (totalActives + ' productos/s')
        },
        getProductMarketerFilterTitle() {

            let totalActives = 0;

            if (!this.filtersObtained.products) return '0 productos'

            this.filtersObtained.products.forEach((account) => {
                if (account.active) totalActives++;
            })

            return totalActives === this.filtersObtained.products.length ? 'Todos' : (totalActives + ' productos/s')
        },
        getAgentFilterTitle() {

            let totalActives = 0;

            if (!this.filtersObtained.agents || (this.filtersObtained.agents && this.filtersObtained.agents.length === 0)) return '0 agentes'

            this.filtersObtained.agents.forEach((agent) => {
                if (agent.active) totalActives++;
            })

            return totalActives === this.filtersObtained.agents.length ? 'Todos' : (totalActives + ' agentes/s')
        },
        getMarketerFilterTitle() {

            let totalActives = 0;

            if (!this.filtersObtained.marketers || (this.filtersObtained.marketers && this.filtersObtained.marketers.length === 0)) return '0 comercializad.'


            this.filtersObtained.marketers.forEach((marketer) => {
                if (marketer.active) totalActives++;
            })

            return totalActives === this.filtersObtained.marketers.length ? 'Todos' : (totalActives + ' comercializad/s')
        },
        getStatusFilterTitle() {

            let totalActives = 0;

            if (!this.filtersObtained.statuses || (this.filtersObtained.statuses && this.filtersObtained.statuses.length === 0)) return '0 estados'

            this.filtersObtained.statuses.forEach((status) => {
                if (status.active) totalActives++;
            })

            return totalActives === this.filtersObtained.statuses.length ? 'Todos' : (totalActives + ' estado/s')
        },
        getFeeFilterTitle() {

            let totalActives = 0;

            if (!this.filtersObtained.fees || (this.filtersObtained.fees && this.filtersObtained.fees.length === 0)) return '0 estados'

            this.filtersObtained.fees.forEach((fee) => {
                if (fee.active) totalActives++;
            })

            return totalActives === this.filtersObtained.fees.length ? 'Todos' : (totalActives + ' tarifa/s')

        },
        areAllSelected() {
            if (this.orders) return this.ordersSelected.length === this.orders.length
        },
        isReadOnly() {
            if (!this.basicData.userLogged)
                return true
            else (this.basicData.userLogged)
            return this.basicData.userLogged.permissions.includes('READONLY')
        },
        areAllAgentsActives() {
            if (this.filtersObtained.agents && this.filtersObtained.agents.length > 0) {

                let activeAgentsCount = this.filtersObtained.agents.filter(agent => agent.active).length;

                return activeAgentsCount === this.filtersObtained.agents.length;
            } else return false
        },
        areAllProductsTypesActives() {
            if (this.filtersObtained.productTypes && this.filtersObtained.productTypes.length > 0) {

                let activeProductsCount = this.filtersObtained.productTypes.filter(prod => prod.active).length;

                return activeProductsCount === this.filtersObtained.productTypes.length;
            } else return false
        },
        areAllMarketersActives() {
            if (this.filtersObtained.marketers && this.filtersObtained.marketers.length > 0) {

                // Filtramos para eliminar 'Sin comercializadora' de la lista
                let filteredMarketers = this.filtersObtained.marketers.filter(marketer => marketer.title !== 'Sin comercializadora');

                // Contamos cuántas comercializadoras activas hay en la lista filtrada
                let activeMarketersCount = filteredMarketers.filter(marketer => marketer.active).length;

                // Comprobamos si todas las comercializadoras restantes están activas
                return activeMarketersCount === filteredMarketers.length;

            } else return false
        },
        areAllProductsMarketerActives() {
            if (this.filtersObtained.products && this.filtersObtained.products.length > 0) {

                let activeProductsCount = this.filtersObtained.products.filter(prod => prod.active).length;

                return activeProductsCount === this.filtersObtained.products.length;
            } else return false
        },
        areAllStatusesActives() {
            if (this.filtersObtained.statuses && this.filtersObtained.statuses.length > 0) {

                let activeProductsCount = this.filtersObtained.statuses.filter(status => status.active).length;

                return activeProductsCount === this.filtersObtained.statuses.length;
            } else return false
        },
        areAllFeesActives() {
            if (this.filtersObtained.fees && this.filtersObtained.fees.length > 0) {

                let activeFeesCount = this.filtersObtained.fees.filter(fees => fees.active).length;

                return activeFeesCount === this.filtersObtained.fees.length;
            } else return false
        },
        getPrettyDatesFilters() {

            let startDate = moment(this.filtersObtained.dates.start).format('DD/MM/YY');
            let endDate = moment(this.filtersObtained.dates.end).format('DD/MM/YY');

            if (!this.filtersObtained.dates.start && !this.filtersObtained.dates.end) {
                return 'Selec. fechas'
            } else {

                //si solo se ha seleccionado la fecha inicial
                if (!this.filtersObtained.dates.end) {
                    return 'Desde  ' + startDate
                } else if (!this.filtersObtained.dates.start) {//si solo se ha seleccionado la fecha final
                    return 'Hasta  ' + endDate
                } else {
                    return startDate + ' - ' + endDate
                }

            }
        },

        getPrettyActivationDatesFilters() {

            let startDate = moment(this.filtersObtained.activationDates.start).format('DD/MM/YY');
            let endDate = moment(this.filtersObtained.activationDates.end).format('DD/MM/YY');

            if (!this.filtersObtained.activationDates.start && !this.filtersObtained.activationDates.end) {
                return 'Selec. fechas'
            } else {

                //si solo se ha seleccionado la fecha inicial
                if (!this.filtersObtained.activationDates.end) {
                    return 'Desde  ' + startDate
                } else if (!this.filtersObtained.activationDates.start) {//si solo se ha seleccionado la fecha final
                    return 'Hasta  ' + endDate
                } else {
                    return startDate + ' - ' + endDate
                }

            }
        },
        getPrettyLowDatesFilters() {
            let startDate = moment(this.filtersObtained.lowDates.start).format('DD/MM/YY');
            let endDate = moment(this.filtersObtained.lowDates.end).format('DD/MM/YY');

            if (!this.filtersObtained.lowDates.start && !this.filtersObtained.lowDates.end) {
                return 'Selec. fechas'
            } else {

                //si solo se ha seleccionado la fecha inicial
                if (!this.filtersObtained.lowDates.end) {
                    return 'Desde  ' + startDate
                } else if (!this.filtersObtained.lowDates.start) {//si solo se ha seleccionado la fecha final
                    return 'Hasta  ' + endDate
                } else {
                    return startDate + ' - ' + endDate
                }
            }
        },
        filtersFiltered() {
            //let filters = [...this.filters.checkbox];

            let filteredFiltered = {
                'agents': [],
                'statuses': [],
                'marketers': [],
                'products': [],
                'productTypes': [],
                'accounts': [],
                'fees': []
            }

            //Agent
            let AgentSearch = this.searchFilters.agent.replaceAll(' ', '').toLowerCase();

            this.filtersObtained.agents.forEach((agent) => {

                let OptSearch = agent.name.replaceAll(' ', '').toLowerCase()

                if (OptSearch.includes(AgentSearch)) filteredFiltered.agents.push(agent)
            })

            //Estados
            let StatusSearch = this.searchFilters.status.replaceAll(' ', '').toLowerCase();

            this.filtersObtained.statuses.forEach((status) => {

                //console.log('status --> ', status)

                let OptSearch = status.title.replaceAll(' ', '').toLowerCase()

                if (OptSearch.includes(StatusSearch)) filteredFiltered.statuses.push(status)
            })


            //Tipos de producto
            let ProductSearch = this.searchFilters.productType.replaceAll(' ', '').toLowerCase();

            this.filtersObtained.productTypes.forEach((productType) => {

                let OptSearch = productType.title.replaceAll(' ', '').toLowerCase()

                if (OptSearch.includes(ProductSearch)) filteredFiltered.productTypes.push(productType)
            })


            let hasDatabaseProduct = this.filtersObtained.productTypes.some(item =>
                item.active && (item.code === "cl" || item.code === "cg" || item.code === "cd" || item.code === "ct" || item.code === "sa" || item.code === "a")
            );

            let hasGas = this.filtersObtained.productTypes.some(item => item.code === "cg" && item.active);
            let hasElectricity = this.filtersObtained.productTypes.some(item => item.code === "cl" && item.active);
            let hasDual = this.filtersObtained.productTypes.some(p => p.code === 'cd' && p.active);
            let hasTelephony = this.filtersObtained.productTypes.some(item => item.code === "ct" && item.active);
            let hasAlarm = this.filtersObtained.productTypes.some(item => item.code === "sa" && item.active);
            let hasSelfSupply = this.filtersObtained.productTypes.some(item => item.code === "a" && item.active);


            let hasOther = this.filtersObtained.productTypes.some(item =>
                !['cl', 'cg', 'cd', 'ct', 'sa', 'a'].includes(item.code) && item.active
            );



            //Tarifas ( solo si cl o cg esta active )
            let FeeSearch = this.searchFilters.fee.replaceAll(' ', '').toLowerCase();

            if (hasDatabaseProduct) {

                this.filtersObtained.fees.forEach((fee) => {

                    let OptSearch = fee.title.replaceAll(' ', '').toLowerCase()

                    let pushFee =
                        (fee.title.includes('TD') && hasElectricity) ||
                        (fee.title.includes('RL') && hasGas) ||
                        (this.filtersObtained.dualFees.includes(fee.code) && hasDual) ||
                        (this.filtersObtained.telephonyFees.includes(fee.code) && hasTelephony) ||
                        (this.filtersObtained.selfSupplyFees.includes(fee.code) && hasSelfSupply) ||
                        (fee.title === 'Sin tarifa' && hasAlarm) ||
                        (!fee.title.includes('TD') && !fee.title.includes('RL') && hasOther);

                    if (OptSearch.includes(FeeSearch) && pushFee) filteredFiltered.fees.push(fee)
                })

            } else {
                filteredFiltered.fees = []
            }


            //Comercializadoras ( cl o cg y tarifa )
            let MarketerSearch = this.searchFilters.marketer.replaceAll(' ', '').toLowerCase();

            if (hasDatabaseProduct) {

                this.filtersObtained.marketers.forEach((marketer) => {

                    //Compruebo si la alguna tarifa de la comercializadora esta disponible
                    let selectAvaiable = false;

                    if (marketer.code !== 'Sin comercializadora') {

                        this.marketers.forEach((marketerNow) => {

                            if (marketerNow.name === marketer.code) {

                                //Comprobar si alguna de sus tarifas de luz esta activa y cl esta activo
                                let activeElec = hasElectricity ? marketerNow.fees.electricity.some(elecFee => filteredFiltered.fees.find(feeNow => feeNow.code === elecFee.name && feeNow.active)) : false

                                //Comprobar si alguna de sus tarifas de gas esta activa y cg esta activo
                                let activeGas = hasGas ? marketerNow.fees.gas.some(gasFee => filteredFiltered.fees.find(feeNow => feeNow.code === gasFee.name && feeNow.active)) : false

                                //Comprobar la comercializadora es dual y tiene alguna de sus tarifas activa y en el array de tarifas duales
                                let activeDual = false;

                                if (hasDual && this.filtersObtained.dualMarketers.includes(marketer.code)) {

                                    activeDual =
                                        marketerNow.fees.electricity.some(elecFee =>
                                            filteredFiltered.fees.find(
                                                feeNow =>
                                                    feeNow.code === elecFee.name &&
                                                    feeNow.active &&
                                                    this.filtersObtained.dualFees.includes(feeNow.code)
                                            )
                                        ) ||
                                        marketerNow.fees.gas.some(gasFee =>
                                            filteredFiltered.fees.find(
                                                feeNow =>
                                                    feeNow.code === gasFee.name &&
                                                    feeNow.active &&
                                                    this.filtersObtained.dualFees.includes(feeNow.code)
                                            )
                                        );
                                }

                                //Telefonía
                                let activeTelephony = false;

                                if (hasTelephony && this.filtersObtained.telephonyMarketers.includes(marketer.code)) {

                                    activeTelephony = marketerNow.products.telephony.some(tel =>
                                        tel.fees.some(telFee =>
                                            filteredFiltered.fees.find(
                                                feeNow =>
                                                    feeNow.code === telFee.name &&
                                                    feeNow.active &&
                                                    this.filtersObtained.telephonyFees.includes(feeNow.code)
                                            )
                                        )
                                    )
                                }

                                //Alarmas
                                let activeAlarm = false;

                                if (hasAlarm && this.filtersObtained.alarmMarketers.includes(marketer.code)) {
                                    activeAlarm = filteredFiltered.fees.some(fee => fee.code === 'Sin tarifa' && fee.active)
                                }

                                //Autoconsumo
                                let activeSelfSupply = false;

                                if (hasSelfSupply && this.filtersObtained.selfSupplyMarketers.includes(marketer.code)) {

                                    activeSelfSupply = marketerNow.products.selfSupply.some(tel =>
                                        tel.fees.some(telFee =>
                                            filteredFiltered.fees.find(
                                                feeNow =>
                                                    feeNow.code === telFee.name &&
                                                    feeNow.active &&
                                                    this.filtersObtained.selfSupplyFees.includes(feeNow.code)
                                            )
                                        )
                                    )
                                }


                                //Compruebo si alguna de las dos están disponibles
                                if (activeElec || activeGas || activeDual || activeTelephony || activeAlarm || activeSelfSupply) selectAvaiable = true
                            }

                        })

                    }else{
                        //Compruebo si está activado la tarifa 'Sin tarifa'
                        if (!filteredFiltered.fees.some(fee => fee.code === 'Sin tarifa' && fee.active))
                            return
                    }

                    let OptSearch = marketer.title.replaceAll(' ', '').toLowerCase()

                    if (OptSearch.includes(MarketerSearch) && (marketer.code === 'Sin comercializadora' || selectAvaiable)) filteredFiltered.marketers.push(marketer)
                })

            } else {
                filteredFiltered.marketers = []
            }

            //Productos de comercializadora ( cl o cg, tarifa y comerc. )
            let ProductMarketerSearch = this.searchFilters.productMarketer.replaceAll(' ', '').toLowerCase();

            this.filtersObtained.products.forEach((productMarketer) => {

                //Compruebo los productos de los que no son cl ni cg
                let selectAvaiable = false;

                //Compruebo si es de los otros ( que este acivado algun t. prod. de esos )
                if (this.marketerProductsOthers.includes(productMarketer.title) || productMarketer.title === 'Sin producto') {
                    if (!!hasOther) selectAvaiable = true;
                } else {//Compruebo si es de luz o gas

                    //Saco la comercializadora q es ya que tiene dentro toda la info de esta
                    this.marketers.forEach((marketerNow) => {

                        //Si la comercializadora esta disponible sigo con las comprobaciones, sino directamente no
                        if (filteredFiltered.marketers.some(marketerToSearchNow => marketerToSearchNow.title === marketerNow.name && marketerToSearchNow.active)) {

                            let activeElec, activeGas, activeDual, activeTelephony, activeAlarm, activeSelfSupply = false;


                            //Compruebo primero si es de luz o de gas por si hay mas de uno con el mismo nombre --> ej. Indexado y directamente si tiene alguna tarifa active
                            if (hasElectricity) {

                                activeElec = marketerNow.products.electricity.some((electProd) => {
                                    let isProd = electProd.name === productMarketer.title;

                                    if (!isProd) return false; // Si no es el producto, no es necesario seguir

                                    // Compruebo si alguna tarifa asociada está activa
                                    return electProd.fees.some((fee) => {
                                        // Saco el nombre de la tarifa
                                        let feeObtained = marketerNow.fees.electricity.find(feeNow => feeNow.id.$oid === fee.id.$oid);

                                        if (feeObtained) {
                                            // Compruebo si la tarifa está activa
                                            return filteredFiltered.fees.some(feeNow2 =>
                                                feeObtained.name === feeNow2.title && feeNow2.active
                                            );
                                        }

                                        return false; // Si no se encuentra la tarifa, continuar con la siguiente
                                    });
                                });

                            }

                            if (hasGas) {
                                activeGas = marketerNow.products.gas.some((gasProd) => {
                                    let isProd = gasProd.name === productMarketer.title;

                                    if (!isProd) return false; // Si no es el producto, no necesita comprobar las tarifas

                                    // Compruebo si alguna tarifa asociada está activa
                                    return gasProd.fees.some((fee) => {
                                        // Saco el nombre de la tarifa
                                        let feeObtained = marketerNow.fees.gas.find(feeNow => feeNow.id.$oid === fee.id.$oid);

                                        // Compruebo si la tarifa está activa
                                        if (feeObtained) {
                                            let someActiveFee = filteredFiltered.fees.some(feeNow2 =>
                                                feeObtained.name === feeNow2.title && feeNow2.active
                                            );
                                            return someActiveFee; // Devuelve true en cuanto se encuentra una tarifa activa
                                        }

                                        return false; // Si no se encuentra la tarifa, continuar con la siguiente
                                    });
                                });
                            }

                            //dual
                            if (hasDual && this.filtersObtained.dualMarketerProducts.includes(productMarketer.code)) {

                                activeDual = marketerNow.products.dual.some((dualProd) => {

                                    let isElectricity, isGas = false

                                    //Compruebo si es de luz
                                    if (dualProd.electricity === productMarketer.code) { //comparo nombre

                                        //Compruebo si alguna de sus tarifas están activas
                                        isElectricity = dualProd.fees.some((fee) =>
                                            filteredFiltered.fees.find(feeNow =>
                                                feeNow.code === fee.electricity.name &&
                                                feeNow.active &&
                                                this.filtersObtained.dualFees.includes(feeNow.code)
                                            )
                                        )

                                    }
                                    else if (dualProd.gas === productMarketer.code) {

                                        //Compruebo si alguna de sus tarifas están activas
                                        isGas = dualProd.fees.some((fee) =>
                                            filteredFiltered.fees.find(feeNow =>
                                                feeNow.code === fee.gas.name &&
                                                feeNow.active &&
                                                this.filtersObtained.dualFees.includes(feeNow.code)
                                            )
                                        )
                                    }

                                    if (isElectricity || isGas) return true
                                });
                            }

                            //Telefonía
                            if (hasTelephony && this.filtersObtained.telephonyMarketerProducts.includes(productMarketer.code)) {
                                activeDual = marketerNow.products.telephony.some((telProd) => {

                                    let isProd = telProd.name === productMarketer.title;
                                    if (!isProd) return false; // Si no es el producto, no es necesario seguir

                                    return telProd.fees.some((fee) =>
                                        filteredFiltered.fees.find(feeNow =>
                                            feeNow.code === fee.name &&
                                            feeNow.active &&
                                            this.filtersObtained.telephonyFees.includes(feeNow.code)
                                        )
                                    )

                                });
                            }

                            //Alarmas
                            if (hasAlarm && this.filtersObtained.alarmMarketerProducts.includes(productMarketer.code)) {
                                activeAlarm = marketerNow.products.alarm.some((alarmProd) => {

                                    let isProd = alarmProd.name === productMarketer.title;
                                    if (!isProd) return false; // Si no es el producto, no es necesario seguir

                                    //Compruebo si la tarifa 'Sin tarifa está activa'
                                    return filteredFiltered.fees.some(fee => fee.code === 'Sin tarifa' && fee.active)
                                });
                            }

                            //Autoconsumo
                            if (hasSelfSupply && this.filtersObtained.selfSupplyMarketerProducts.includes(productMarketer.code)) {
                                activeSelfSupply = marketerNow.products.selfSupply.some((ssProd) => {

                                    let isProd = ssProd.name === productMarketer.title;
                                    if (!isProd) return false; // Si no es el producto, no es necesario seguir

                                    return ssProd.fees.some((fee) =>
                                        filteredFiltered.fees.find(feeNow =>
                                            feeNow.code === fee.name &&
                                            feeNow.active &&
                                            this.filtersObtained.selfSupplyFees.includes(feeNow.code)
                                        )
                                    )

                                });
                            }

                            if (activeElec || activeGas || activeDual || activeTelephony || activeAlarm || activeSelfSupply) selectAvaiable = true
                        }
                    })
                }

                let OptSearch = productMarketer.title.replaceAll(' ', '').toLowerCase()

                if (OptSearch.includes(ProductMarketerSearch) && selectAvaiable) filteredFiltered.products.push(productMarketer)
            })



            //Cuentas
            if (this.accountsRelated) {

                let AccountSearch = this.searchFilters.account.replaceAll(' ', '').toLowerCase();

                this.accountsRelated.forEach((acc) => {

                    let OptSearch = acc.name.replaceAll(' ', '').toLowerCase()

                    if (OptSearch.includes(AccountSearch)) filteredFiltered.accounts.push(acc)
                })
            }


            return filteredFiltered
        },
        isInputsDisabled() {
            if (!this.basicData.userLogged)
                return true
            else (this.basicData.userLogged)
            return this.basicData.userLogged.permissions.includes('READONLY') || !this.isEditing
        },
        filtersApplied() {
            let filters = {};

            const extractActiveItems = (items, category, title = "title") => {
                let result = items.filter(item => item.active).map(item => item[title])
                if (items.length > result.length) {
                    filters[category] = result.join(', ');
                }
            }

            //Obtener valores por cada filtro
            extractActiveItems(this.filtersObtained.agents, 'Agentes', 'name');
            extractActiveItems(this.filtersFiltered.statuses, "Estados");
            if (this.getPrettyDatesFilters !== 'Selec. fechas') filters["Fec. Traspaso"] = this.getPrettyDatesFilters
            if (this.getPrettyActivationDatesFilters !== 'Selec. fechas') filters["Fec. Activación"] = this.getPrettyActivationDatesFilters
            extractActiveItems(this.filtersFiltered.productTypes, "Tipos de producto");
            extractActiveItems(this.filtersObtained.fees, "Tarifas");
            extractActiveItems(this.filtersFiltered.marketers, "Comercializadoras");
            extractActiveItems(this.filtersFiltered.products, "Productos");
            let viewSelected = this.filters.radio.view;
            let viewCommision = this.filters.radio.withoutCommision;
            if (viewSelected.checked !== 0) filters["Vista"] = viewSelected.data[viewSelected.checked].title;
            if (viewCommision.checked !== 0) filters["Comision"] = viewCommision.data[viewCommision.checked].title;

            return filters;
        }

        /*productTypesFiltered(){

            if (!this.orders) return []

            let products = [];

            //Recorro los productos, si su comercializadora esta marcada se pondrán sus productos
            this.orders.forEach((order) => {

                //Compruebo si la comercializadora existe y esta activa
                if (order.marketer === '') order.marketer = 'Sin comercializadora'

                let existsAndActiveMarketer = this.filters.checkbox.marketerAvailable.data.some((label) => ((label.title === order.marketer) && label.active))

                //si la comercializadora existe en el array de filtros y esta activa se comprobará para poner su producto
                if (existsAndActiveMarketer){

                    let existsProduct = products.some(label => label.title === order.product)

                    if(!existsProduct){

                        let productType = {
                            title: order.product,
                            active: true
                        }

                        products.push(productType);
                    }
                }
            })

            return produ
            }*/
    },
    beforeUnmount() {
        if (this.queue) {
            this.queue.abort();
        }
    }
}
</script>

<style>
.contracts-header-align {
    padding-left: 12px;
    padding-right: 12px;
    gap: 8px;
}

.contracts-header-align > .d-flex {
    justify-content: center;
    min-width: 0;
}

.contracts-header-align .text {
    text-align: center;
    white-space: nowrap;
    word-break: normal;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 13px !important;
}

.fidelity-contracts-scroll {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    padding-top: 0;
    padding-bottom: 10px;
    scrollbar-gutter: stable;
    position: relative;
    display: block;
}

.fidelity-contracts-scroll-inner {
    width: max-content;
}

.fidelity-contracts-scroll .fidelity-contracts-grid,
.fidelity-contracts-scroll .fidelity-contracts-row {
    width: max-content;
    gap: 8px !important;
    grid-template-columns: 220px 150px 150px 150px 130px 130px 240px 230px 120px 120px 120px 120px 80px !important;
}

.fidelity-contracts-scroll .fidelity-contracts-grid.with-id,
.fidelity-contracts-scroll .fidelity-contracts-row.with-id {
    gap: 8px !important;
    grid-template-columns: 70px 220px 150px 150px 150px 130px 130px 240px 230px 120px 120px 120px 120px 80px !important;
}

.fidelity-contracts-scroll .fidelity-contracts-grid .text,
.fidelity-contracts-scroll .fidelity-contracts-grid p,
.fidelity-contracts-scroll .fidelity-contracts-grid a,
.fidelity-contracts-scroll .fidelity-contracts-grid .ellipsis,
.fidelity-contracts-scroll .fidelity-contracts-row .text,
.fidelity-contracts-scroll .fidelity-contracts-row p,
.fidelity-contracts-scroll .fidelity-contracts-row a,
.fidelity-contracts-scroll .fidelity-contracts-row .ellipsis {
    white-space: normal !important;
    word-break: normal !important;
    overflow-wrap: break-word;
    overflow: visible !important;
    text-overflow: initial !important;
}


.fidelity-contracts-scroll .fidelity-contracts-row .fidelity-date-text,
.fidelity-contracts-scroll .fidelity-contracts-row .fidelity-date-text.text,
.fidelity-contracts-scroll .fidelity-contracts-row .fidelity-date-cell p {
    white-space: nowrap !important;
    word-break: normal !important;
    overflow-wrap: normal !important;
    overflow: visible !important;
    text-overflow: initial !important;
}

.fidelity-contracts-scroll .fidelity-contracts-grid .ellipsis,
.fidelity-contracts-scroll .fidelity-contracts-row .ellipsis {
    width: auto !important;
}

.fidelity-contracts-scroll .fidelity-contracts-row > .d-flex,
.fidelity-contracts-scroll .fidelity-contracts-row > a {
    min-width: 0;
}

.fidelity-contracts-scroll .fidelity-contracts-row .fidelity-actions {
    overflow: visible !important;
}

.fidelity-contracts-scroll .fidelity-contracts-row .fidelity-contract-title,
.fidelity-contracts-scroll .fidelity-contracts-row .fidelity-contract-title-text,
.fidelity-contracts-scroll .fidelity-contracts-row .fidelity-contract-product-text {
    width: 100% !important;
    overflow: hidden !important;
}

.fidelity-contracts-scroll .fidelity-contracts-row .fidelity-contract-title-text {
    display: -webkit-box !important;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
}

.fidelity-contracts-scroll .fidelity-contracts-row .fidelity-contract-product-text {
    display: -webkit-box !important;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.fidelity-contracts-scroll .fidelity-contracts-row .custom-button {
    max-width: 100% !important;
    white-space: normal !important;
}

.fidelity-contracts-scroll .fidelity-contracts-row .custom-button p {
    display: -webkit-box !important;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    white-space: normal !important;
    overflow: hidden !important;
    word-break: break-word !important;
}


.fidelity-contracts-pagination {
    grid-template-columns: auto 1fr auto !important;
    align-items: center;
    gap: 12px;
}

.fidelity-contracts-pagination > .fidelity-scroll-hint {
    justify-self: start;
}

.fidelity-scroll-hint {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: rgba(0, 35, 72, 0.08);
    color: #002348;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    cursor: help;
    position: relative;
    z-index: 2;
}

.fidelity-scroll-hint i {
    font-size: 13px;
}

.fidelity-scroll-tooltip {
    position: absolute;
    left: 0;
    top: calc(100% + 8px);
    display: block;
    min-width: 280px;
    max-width: 360px;
    padding: 8px 10px;
    border-radius: 10px;
    background: #002348;
    color: white;
    font-size: 12px;
    line-height: 1.35;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
    opacity: 0;
    pointer-events: none;
    transform: translateY(-4px);
    transition: opacity 0.08s ease, transform 0.08s ease;
    z-index: 4;
}


.fidelity-contracts-scroll .fidelity-date-cell {
    width: 100%;
    min-width: 0;
    overflow: visible !important;
}

.fidelity-contracts-scroll .fidelity-date-text {
    display: block;
    width: 100%;
    white-space: nowrap !important;
    word-break: normal !important;
    overflow-wrap: normal !important;
    overflow: visible !important;
    text-overflow: initial !important;
    text-align: center;
}

.fidelity-scroll-hint:hover .fidelity-scroll-tooltip {
    opacity: 1;
    transform: translateY(0);
}

.fidelity-contracts-pagination > .d-flex.justify-center {
    justify-self: center;
    margin-top: 20px;
}

.fidelity-contracts-pagination > .my-auto.ml-auto.d-flex {
    justify-self: end;
    margin-left: 0 !important;
    margin-top: 20px;
}


.mobile-order-row {
    display: flex;
    align-items: center;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    gap: 6px;
    overflow: hidden;
}

.mobile-order-id {
    flex: 0 0 auto;
    max-width: 55px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mobile-order-icon {
    flex: 0 0 auto;
}

.mobile-order-name {
    flex: 1 1 auto;
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mobile-order-logo-box {
    flex: 0 0 30px;
    width: 30px;
    min-width: 30px;
    max-width: 30px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.order-marketer-logo {
    width: 30px;
    height: 30px;
    max-width: 30px;
    max-height: 30px;
    object-fit: contain;
    display: block;
}
</style>
