<template>
    <div class="content-white" v-on:click="hideCustomSelects">

        <!--Estilo de movil-->
        <div class="mobile-item">

            <!--Barra de busqueda-->
            <div class="d-flex">
                <div class="search-bar w-100">

                    <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                    <input type="text" data-size="14" ref="searchContractMobile" placeholder="Buscar un contrato..." v-model="searchOrderText" v-on:keyup="debouncedFetchAllOrders">
                </div>

                <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>
            </div>



            <!--Paginación-->
            <div class="d-grid my-10" data-column="2" data-layout="auto1" v-if="orders && orders.length > 0">

                <div class="d-flex justify-center my-auto" data-color="principal">
                    <div class="left pointer my-auto" v-bind:class="{ 'opacity-5' : currentPage === 1 }" v-on:click="changePage(-1)"><i class="fa-solid fa-chevron-left"></i></div>

                    <!--Info página-->
                    <div class="cont mx-10 my-auto" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages }}</div>

                    <div class="right pointer my-auto" v-bind:class="{ 'opacity-5' : currentPage === totalPages }" v-on:click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></div>
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

            <!--Filtros-->
            <div class="d-flex column my-20">

                <p class="text" data-size="13" data-weight="600" v-on:click="isSeeingFilters = !isSeeingFilters">{{ isSeeingFilters ? 'Ocultar filtros' : 'Mostrar filtros' }}</p>


                <div v-if="isSeeingFilters">

                    <div class="arrow-border arrow-top my-10" data-position="left"></div>


                    <!--Agentes-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Agentes</div>

                        <div class="custom-select" v-if="filtersObtained.agents && filtersObtained.agents.length > 0">

                            <div class="ml-10" data-size="13" data-color="azul">{{ getAgentFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div class="d-flex align-center" v-if="filtersFiltered.agents && filtersFiltered.agents > 0">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('agents')" v-bind:class="{ 'selected': areAllAgentsActives }"></div>

                                    <div class="text">Todos</div>

                                </div>
                                <div v-for="agent in filtersFiltered.agents" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(agent, 'agent')" v-bind:class="{ 'selected': agent.active }"></div>

                                    <div class="text" data-size="13">{{ agent.name }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 agentes</div>
                    </div>

                    <!--Estados-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Estados</div>

                        <div class="custom-select" v-if="filtersObtained.statuses && filtersObtained.statuses.length > 0">

                            <div class="ml-10" data-size="13" data-color="azul">{{ getStatusFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div class="d-flex align-center" v-if="filtersFiltered.statuses && filtersFiltered.statuses > 0">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('statuses')" v-bind:class="{ 'selected': areAllStatusesActives }"></div>

                                    <div class="text">Todos</div>

                                </div>
                                <div v-for="status in filtersFiltered.statuses" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(status, 'product')" v-bind:class="{ 'selected': status.active }"></div>

                                    <div class="text" data-size="13">{{ status.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 estados</div>
                    </div>

                    <!--fecha creación-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Fec. traspaso</div>


                        <div class="custom-select no-hover" v-on:click.stop="seeFilters('dates')" v-bind:class="{'seeing': isSeeingFiltersPc.dates}">

                            <div class="ml-10" data-color="azul" data-size="13">{{ getPrettyDatesFilters }}<i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content form" style="width: 300px">


                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Inicial</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="filtersObtained.dates.start" v-on:change="setDate('dates.start')" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer" v-on:click.stop="deleteFilter('dates.start')">
                                        <i class="fas fa-x"></i>
                                    </div>

                                </div>

                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Final</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="filtersObtained.dates.end" v-on:change="setDate('dates.end')" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer" v-on:click.stop="deleteFilter('dates.start')">
                                        <i class="fas fa-x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--Fec. activación-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Fec. activación</div>

                        <div class="custom-select no-hover" v-on:click.stop="seeFilters('activationDates')" v-bind:class="{'seeing': isSeeingFiltersPc.activationDate}">

                            <div class="ml-10" data-color="azul" data-size="13">{{ getPrettyActivationDatesFilters }}<i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content form" style="width: 300px">

                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Inicial</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="filtersObtained.activationDates.start" v-on:change="setDate('activationDates.start')" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer" v-on:click.stop="deleteFilter('activationDates.start')">
                                        <i class="fas fa-x"></i>
                                    </div>

                                </div>

                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Final</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="filtersObtained.activationDates.end" v-on:change="setDate('activationDates.end')" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer" v-on:click.stop="deleteFilter('activationDates.end')">
                                        <i class="fas fa-x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!--Tipos de producto-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Tipos de prod.</div>

                        <div class="custom-select" v-if="filtersObtained.productTypes && filtersObtained.productTypes.length > 0">

                            <div class="ml-10" data-size="13" data-color="azul">{{ getProductTypeFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div class="d-flex align-center" v-if="filtersFiltered.productTypes && filtersFiltered.productTypes > 0">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('productTypes')" v-bind:class="{ 'selected': areAllProductsTypesActives }"></div>

                                    <div class="text">Todos</div>

                                </div>
                                <div v-for="productType in filtersFiltered.productTypes" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(productType, 'product')" v-bind:class="{ 'selected': productType.active }"></div>

                                    <div class="text" data-size="13">{{ productType.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 productos</div>
                    </div>

                    <!--Tarifa-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Tarifa</div>

                        <div class="custom-select" v-if="filtersObtained.productTypes && filtersObtained.productTypes.length > 0">

                            <div class="ml-10" data-size="13" data-color="azul">{{ getFeeFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div class="d-flex align-center" v-if="filtersFiltered.fees && filtersFiltered.fees > 0">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('fees')" v-bind:class="{ 'selected': areAllFeesActives }"></div>

                                    <div class="text">Todos</div>

                                </div>
                                <div v-for="fee in filtersFiltered.fees" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(fee, 'fee')" v-bind:class="{ 'selected': fee.active }"></div>

                                    <div class="text" data-size="13">{{ fee.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 tarifas</div>
                    </div>

                    <!--Comercializadora-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Comercializad.</div>

                        <div class="custom-select" v-if="filtersObtained.marketers && filtersObtained.marketers.length > 0">

                            <div class="ml-10" data-size="13" data-color="azul">{{ getMarketerFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div class="d-flex align-center" v-if="filtersFiltered.marketers && filtersFiltered.marketers > 0">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('marketers')" v-bind:class="{ 'selected': areAllMarketersActives }"></div>

                                    <div class="text">Todos</div>

                                </div>
                                <div v-for="marketer in filtersFiltered.marketers" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(marketer, 'marketer')" v-bind:class="{ 'selected': marketer.active }"></div>

                                    <div class="text" data-size="13">{{ marketer.title === '' ? 'Sin comercializadora' : marketer.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 comerc.</div>
                    </div>

                    <!--Productos comercializadoras-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-size="13" data-weight="600">Productos comerc.</div>

                        <div class="custom-select" v-if="filtersObtained.products && filtersObtained.products.length > 0">

                            <div class="ml-10" data-size="13" data-color="azul">{{ getProductMarketerFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div class="d-flex align-center" v-if="filtersFiltered.products && filtersFiltered.products > 0">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('productsMarketer')" v-bind:class="{ 'selected': areAllProductsMarketerActives }"></div>

                                    <div class="text">Todos</div>

                                </div>
                                <div v-for="productType in filtersFiltered.products" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(productType, 'product')" v-bind:class="{ 'selected': productType.active }"></div>

                                    <div class="text" data-size="13">{{ productType.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 productos</div>
                    </div>

                    <!--Ordenar-->
                    <div class="d-flex justify-between my-10">
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
                    </div>

                    <div class="separator mt-10"></div>
                </div>
            </div>




            <!--Título-->
            <div class="d-flex justify-between">

                <div class="text my-10" data-size="22" data-weight="700">Contratos <span data-size="12" class="my-auto opacity-5"> ( {{ totalOrders }} {{ totalOrders === 1 ? 'contrato' : 'contratos'}} )</span></div>
            </div>

            <!--Listado de productos-->
            <div>

                <div class="d-flex column">
                    <div v-for="(order, orderKey) in orders" class="my-5">

                        <!--Card-->
                        <div class="d-flex align-center pointer" v-on:click="seeOrderInfo(order)">

                            <div class="text ellipsis" data-weight="600"> <i v-if="order.verifications && order.verifications.length > 0" class="fa-solid fa-lightbulb mr-5" data-color="amarillo"></i> {{ order.name }}</div>

                            <div class="deploy-btn ml-10" data-round="15" v-bind:class="{'selected': orderSelectedToSee && ( ((typeof orderSelectedToSee._id === 'string') ? orderSelectedToSee._id : orderSelectedToSee._id.$oid) === ((typeof order._id === 'string') ? order._id : order._id.$oid) )}">
                                <i class="fa-solid" v-bind:class="{'fa-chevron-down': !(orderSelectedToSee && ( ((typeof orderSelectedToSee._id === 'string') ? orderSelectedToSee._id : orderSelectedToSee._id.$oid) === ((typeof order._id === 'string') ? order._id : order._id.$oid) )), 'fa-chevron-up': (orderSelectedToSee && ( ((typeof orderSelectedToSee._id === 'string') ? orderSelectedToSee._id : orderSelectedToSee._id.$oid) === ((typeof order._id === 'string') ? order._id : order._id.$oid) ))}"></i>
                            </div>
                        </div>

                        <!--Info card-->
                        <div class="d-flex column" v-if="orderSelectedToSee && ( ((typeof orderSelectedToSee._id === 'string') ? orderSelectedToSee._id : orderSelectedToSee._id.$oid) === ((typeof order._id === 'string') ? order._id : order._id.$oid))">

                            <!--Info básica-->
                            <div class="my-10">
                                <!--Estado-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Estado</div>

                                    <div class="custom-button w-fit-content" data-size="small" data-mode="translucent" :data-bg="getStatus(order).color">{{ getStatus(order).title }}</div>
                                </div>

                                <!--Producto-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Producto</div>

                                    <div class="text" data-size="13">{{ order.product + order.marketer }}</div>
                                </div>
                            </div>

                            <!--Otra info-->
                            <div class="my-10">
                                <!--Propietario-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Propietario</div>

                                    <div class="text" data-size="13">{{ order.owner }}</div>

                                </div>

                                <!--Fecha traspaso-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Fecha de traspaso</div>

                                    <div class="text" data-size="13">{{ getPrettyDateTransfer(order.transferDate) }}</div>
                                </div>

                                <!--Fecha último estado-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Fecha de último estado</div>

                                    <div class="text" data-size="13">{{ getLastStatusDate(order) }}</div>
                                </div>
                            </div>

                            <!--Botones-->
                            <div class="d-flex">

                                <div v-on:click="actionLink('/accounts/'+ order.account + `?id=${(order._id.$oid ? order._id.$oid : order._id)}`)" class="custom-button w-30" data-bg="principal" data-mode="outline" data-align="center" data-size="small"  data-weight="700"><i class="fas fa-gear"></i></div>

                                <!--<div v-on:click="deleteOrder(order)" v-if="!isReadOnly" class="custom-button w-30" data-bg="rojo" data-mode="outline" data-align="center" data-size="small" data-weight="700"><i class="fa-regular fa-trash"></i></div>-->
                            </div>
                        </div>

                        <div class="separator my-10" v-if="orderKey < filteredOrders.length - 1"></div>
                    </div>
                </div>


                <div class="mt-20">

                    <div class="d-grid" data-column="2" data-layout="auto1">

                        <div class="d-flex justify-center my-auto" data-color="principal">
                            <div class="left pointer my-auto" v-bind:class="{ 'opacity-5' : currentPage === 1 }" v-on:click="changePage(-1)"><i class="fa-solid fa-chevron-left"></i></div>

                            <!--Info página-->
                            <div class="cont mx-10 my-auto" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages }}</div>

                            <div class="right pointer my-auto" v-bind:class="{ 'opacity-5' : currentPage === totalPages }" v-on:click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></div>
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
                <div v-if="orders && orders.length === 0" class="text opacity-5" data-align="center">¡No hay ningún contrato!</div>
            </div>
        </div>

        <!--Estilo de pc-->
        <div class="desktop-item">

            <!--Header-->
            <div class="d-flex justify-between align-center">

                <!--Título-->
                <div class="d-flex">
                    <div class="text" data-size="30" data-weight="700">{{ $route.meta.title }} <span data-size="12" class="my-auto opacity-5"> ( {{ totalOrders }} {{ totalOrders === 1 ? 'contrato' : 'contratos'}} )</span></div>

                    <!--Paginación-->
                    <div class="d-grid" data-column="2" v-if="orders && orders.length > 0">

                        <div class="d-flex justify-center my-auto" data-color="principal">
                            <div class="left pointer" v-bind:class="{ 'opacity-5' : currentPage === 1 }" v-on:click="changePage(-1)"><i class="fa-solid fa-chevron-left"></i></div>


                            <!--Info página-->
                            <div class="cont mx-10" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages }}</div>


                            <div class="right pointer" v-bind:class="{ 'opacity-5' : currentPage === totalPages }" v-on:click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></div>
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
                <div class="d-flex" v-if="!isReadOnly">
                    <div class="custom-select no-hover" v-on:click.stop="isSeeingCreateOrder = true"  v-bind:class="{'seeing': isSeeingCreateOrder}">

                        <div class="custom-button" data-size="regular" data-bg="principal">Añade un contrato</div>

                        <div class="select-content form">

                            <div class="form-group ">
                                <div class="input-group">
                                    <input data-size="12" v-model="searchFilters.account" type="text" placeholder="Busca tu cuenta...">
                                </div>
                            </div>

                            <div v-for="account in filtersFiltered.accounts" class="d-flex align-center pointer">
                                <div class="text ellipsis" data-size="13" v-on:click.stop="selectAccToCreateOrder(account._id)"><i class="fas fa-buildings mr-10"></i>{{ account.name }}</div>
                            </div>

                            <div v-if="!accountsRelated || accountsRelated.length === 0" class="text opacity-5" data-size="10">¡Crea primero una cuenta!</div>
                        </div>
                    </div>
                </div>
            </div>

            <!--Barra de busqueda-->
            <div class="mt-30 select-line">

                <!--Agenda y archivado-->
                <div class="text pointer two-divs" data-align="center" v-bind:class="{'selected': partSeeing === 0}" v-on:click="changePartSeeing(0)">En tramitación</div>

                <div class="text pointer two-divs" data-align="center" v-bind:class="{'selected': partSeeing === 1}" v-on:click="changePartSeeing(1)">Comisionados</div>

                <div class="d-flex filters-on" v-if="(!areAllAgentsActives && filtersObtained.agents.length > 0) || (!areAllStatusesActives && filtersObtained.statuses.length > 0) || /*fec. creación*/ (filtersObtained.dates.start || filtersObtained.dates.end) || /*fec. activación*/ (filtersObtained.activationDates.start || filtersObtained.activationDates.end) || (!areAllProductsTypesActives && filtersObtained.productTypes.length > 0) || (!areAllFeesActives && filtersObtained.fees.length > 0) || (!areAllMarketersActives && filtersObtained.marketers.length > 0) || (!areAllProductsMarketerActives && filtersObtained.products.length > 0)">
                    <i class="fa fas fa-lightbulb my-auto" data-color="rojo"></i>

                    <p class="my-auto mx-15" data-color="rojo">Filtros aplicados</p>

                    <div class="custom-button " data-size="small" data-bg="rojo" data-mode="translucent" v-on:click="resetFilters">Borrar filtros</div>
                </div>

                <div class="before-search">
                    <div class="custom-button " data-size="small" data-bg="azul" data-mode="translucent" v-on:click="isSeeingFiltersBox = !isSeeingFiltersBox">{{ isSeeingFiltersBox ? 'Ocultar' : 'Mostrar' }} filtros</div>
                </div>

                <!--Barra de busqueda-->
                <div class="search-div d-flex">

                    <div class="search-bar w-100">

                        <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                        <input type="text" placeholder="Buscar un contrato..." ref="searchContract" v-model="searchOrderText" v-on:keyup="debouncedFetchAllOrders">
                    </div>

                    <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>

                </div>

            </div>


            <!--Header-->
            <div class="contact header-card six-no-chck mt-30">

                <!--checkbox-->
                <!--<div class="custom-checkbox pointer" v-on:click="toggleSelectAllOrders" v-bind:class="{'selected': areAllSelected && filteredOrders.length > 0}"></div>-->

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('title')">Título</p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('title')" v-bind:class="this.$cookies.get('filters')['orders']['sortBy'] === 0 ? 'fa-sort-down' : (this.$cookies.get('filters')['orders']['sortBy'] === 1 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('agent')">Agente</p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('agent')" v-bind:class="this.$cookies.get('filters')['orders']['sortBy'] === 4 ? 'fa-sort-down' : (this.$cookies.get('filters')['orders']['sortBy'] === 5 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('nif')">NIF/CIF</p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('nif')" v-bind:class="this.$cookies.get('filters')['orders']['sortBy'] === 6 ? 'fa-sort-down' : (this.$cookies.get('filters')['orders']['sortBy'] === 7 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('fee')">Tarifa</p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('fee')" v-bind:class="this.$cookies.get('filters')['orders']['sortBy'] === 6 ? 'fa-sort-down' : (this.$cookies.get('filters')['orders']['sortBy'] === 7 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('product')">Producto</p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('product')" v-bind:class="this.$cookies.get('filters')['orders']['sortBy'] === 8 ? 'fa-sort-down' : (this.$cookies.get('filters')['orders']['sortBy'] === 9 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('cups')">CUPS</p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('cups')" v-bind:class="this.$cookies.get('filters')['orders']['sortBy'] === 10 ? 'fa-sort-down' : (this.$cookies.get('filters')['orders']['sortBy'] === 11 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex ellipsis" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('createdAt')">Fec. traspaso</p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('createdAt')" v-bind:class="this.$cookies.get('filters')['orders']['sortBy'] === 3 ? 'fa-sort-down' : (this.$cookies.get('filters')['orders']['sortBy'] === 2 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('status')">Estado</p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('status')" v-bind:class="this.$cookies.get('filters')['orders']['sortBy'] === 12 ? 'fa-sort-down' : (this.$cookies.get('filters')['orders']['sortBy'] === 13 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>

                <div class="d-flex" data-color="principal">
                    <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('lastStatusAt')">Ult. estado</p> <i class="fas my-auto pointer" v-on:click="selectNewOrderType('lastStatusAt')" v-bind:class="this.$cookies.get('filters')['orders']['sortBy'] === 14 ? 'fa-sort-down' : (this.$cookies.get('filters')['orders']['sortBy'] === 15 ? 'fa-sort-up' : 'fa-sort')"></i>
                </div>


                <!--<div class="d-flex" v-if="!isReadOnly && ordersSelected.length > 0">
                    <div class="mx-10 text pointer" data-color="rojo" v-on:click="deleteAllSelectedOrders"><i class="far fa-trash"></i></div>
                </div>-->
            </div>

            <div class="separator my-10"></div>

            <!--Contenido-->
            <div v-if="orders && orders.length > 0">

                <div>
                    <order-card-component v-for="order in orders" :order="order" :ordersSelected="ordersSelected" :orderSelectedToSee="orderSelectedToSee" :isReadOnly="isReadOnly" :productTypes="productTypes" :isEditing="isEditing" :isInputsDisabled="isInputsDisabled" @openOrder="openOrderWindow" @deleteOrder="deleteOrder" @toggleSelectOrder="toggleSelectOrder" @activeEditing="activeEditing"></order-card-component>
                </div>

                <div class="d-grid" data-column="3">

                    <div></div>

                    <div class="d-flex justify-center mt-20" data-color="principal">
                        <div class="left pointer" v-bind:class="{ 'opacity-5' : currentPage === 1 }" v-on:click="changePage(-1)"><i class="fa-solid fa-chevron-left"></i></div>


                        <!--Info página-->
                        <div class="cont mx-10" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages }}</div>


                        <div class="right pointer" v-bind:class="{ 'opacity-5' : currentPage === totalPages }" v-on:click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></div>
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

            <div class="opacity-5" v-if="orders && orders.length === 0" data-align="center">¡No hay ningún contrato!</div>


            <!--Flotante para crear pedido-->
            <div class="form">
                <order-details-item-component class="my-20" v-if="accountToAddOrder" :order="orderToModify" :account="accountToAddOrder" :selectValues="selectValues" :basicData="basicData" canCreate="true" fromCreate="true" :isCreatingOrder="isCreatingOrder" :isEditing="isEditing" :isInputsDisabled="isReadOnly" @addElement="addSelectType" @delElement="delSelectType" @selectElement="selectElement" @createOrder="createOrder" @createOrderAndSaveAcc="createOrder" @closeWindow="closeWindow" @activeEditing="activeEditing"></order-details-item-component>
            </div>

            <!--Flotante para editar pedido-->
            <!--<div class="form">
                <order-details-item-component class="my-20" v-if="accountToUpdateOrder" :order="orderToModify" :selectValues="selectValues" :basicData="basicData" canUpdate="true" @addElement="addSelectType" @delElement="delSelectType" @selectElement="selectElement" @updateOrder="updateOrder"  @closeWindow="closeWindow"></order-details-item-component>
            </div>-->

            <!--Flotante filtros-->
            <div class="filters-box d-flex column justify-between" v-if="isSeeingFiltersBox">

                <div class="filters">
                    <!--Header-->
                    <div class="d-flex justify-between">

                        <p class="text opacity-7" data-size="20" data-weight="600">Filtros</p>

                        <i class="fas fa-x text my-auto pointer" data-size="20" data-weight="600" v-on:click="isSeeingFiltersBox = false"></i>
                    </div>

                    <!--Cada uno de los filtros-->
                    <div class="mt-30 ml-20">

                        <!--Agentes-->
                        <div class="d-flex my-40">
                            <div class="text">Agentes:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('agent')" v-bind:class="{'seeing': isSeeingFiltersPc.agent}" v-if="filtersObtained.agents && filtersObtained.agents.length > 0">

                                <div class="ml-10" data-color="azul">{{ getAgentFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">

                                    <div class="form-group ">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.agent" type="text" placeholder="Busca tu agente...">
                                        </div>
                                    </div>

                                    <!--marcar desmarcar todos-->
                                    <div class="d-flex align-center" v-if="filtersFiltered.agents && filtersFiltered.agents.length > 0">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('agents')" v-bind:class="{ 'selected': areAllAgentsActives }"></div>

                                        <div class="text">Todos</div>
                                    </div>

                                    <div v-for="agent in filtersFiltered.agents" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(agent, 'agent')" v-bind:class="{ 'selected': agent.active }"></div>

                                        <div class="text">{{ agent.name }}</div>

                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 agentes</div>
                        </div>


                        <!--Estados-->
                        <div class="d-flex my-40">
                            <div class="text">Estados:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('status')" v-bind:class="{'seeing': isSeeingFiltersPc.status}" v-if="filtersObtained.statuses && filtersObtained.statuses.length > 0">

                                <div class="ml-10" data-color="azul">{{ getStatusFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">

                                    <div class="form-group ">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.status" type="text" placeholder="Busca tu estado...">
                                        </div>
                                    </div>

                                    <div class="d-flex align-center" v-if="filtersFiltered.statuses && filtersFiltered.statuses.length > 0">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('statuses')" v-bind:class="{ 'selected': areAllStatusesActives }"></div>

                                        <div class="text">Todos</div>
                                    </div>


                                    <div v-for="status in filtersFiltered.statuses" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(status, 'status')" v-bind:class="{ 'selected': status.active }"></div>

                                        <div class="text">{{ status.title }}</div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 estados</div>
                        </div>

                        <!--fecha creación-->
                        <div class="d-flex my-40">
                            <div class="text">Fec. creación:</div>


                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('dates')" v-bind:class="{'seeing': isSeeingFiltersPc.dates}">

                                <div class="ml-10" data-color="azul">{{ getPrettyDatesFilters }}<i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">

                                    <div class="form-group d-flex">
                                        <p class="w-20 my-auto text">Inicial</p>

                                        <div class="input-group ml-10 w-70">
                                            <input data-size="12" v-model="filtersObtained.dates.start" v-on:change="setDate('dates.start')" type="date">
                                        </div>

                                        <div class="my-auto mx-10 text pointer" v-on:click.stop="deleteFilter('dates.start')">
                                            <i class="fas fa-x"></i>
                                        </div>

                                    </div>

                                    <div class="form-group d-flex">
                                        <p class="w-20 my-auto text">Final</p>

                                        <div class="input-group ml-10 w-70">
                                            <input data-size="12" v-model="filtersObtained.dates.end" v-on:change="setDate('dates.end', $cookies.get('filters')['orders']['dates']['end'])" type="date">
                                        </div>

                                        <div class="my-auto mx-10 text pointer" v-on:click.stop="deleteFilter('dates.end')">
                                            <i class="fas fa-x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>

                        <!--fecha activación-->
                        <div class="d-flex my-40">
                            <div class="text">Fec. activación:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('activationDates')" v-bind:class="{'seeing': isSeeingFiltersPc.activationDate}">

                                <div class="ml-10" data-color="azul">{{ getPrettyActivationDatesFilters }}<i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">

                                    <div class="form-group d-flex">
                                        <p class="w-20 my-auto text">Inicial</p>

                                        <div class="input-group ml-10 w-70">
                                            <input data-size="12" v-model="filtersObtained.activationDates.start" v-on:change="setDate('activationDates.start')" type="date">
                                        </div>

                                        <div class="my-auto mx-10 text pointer" v-on:click.stop="deleteFilter('activationDates.start')">
                                            <i class="fas fa-x"></i>
                                        </div>

                                    </div>

                                    <div class="form-group d-flex">
                                        <p class="w-20 my-auto text">Final</p>

                                        <div class="input-group ml-10 w-70">
                                            <input data-size="12" v-model="filtersObtained.activationDates.end" v-on:change="setDate('activationDates.end')" type="date">
                                        </div>

                                        <div class="my-auto mx-10 text pointer" v-on:click.stop="deleteFilter('activationDates.end')">
                                            <i class="fas fa-x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>

                        <!--Tipo de producto-->
                        <div class="d-flex my-40">
                            <div class="text">Tipo de producto:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('productType')"  v-bind:class="{'seeing': isSeeingFiltersPc.productTypes}" v-if="filtersObtained.productTypes && filtersObtained.productTypes.length > 0">

                                <div class="ml-10" data-color="azul">{{ getProductTypeFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">

                                    <div class="form-group">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.productType" type="text" placeholder="Busca tu tipo de producto...">
                                        </div>
                                    </div>

                                    <div class="d-flex align-center" v-if="filtersFiltered.productTypes && filtersFiltered.productTypes.length > 0">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('productTypes')" v-bind:class="{ 'selected': areAllProductsTypesActives }"></div>

                                        <div class="text">Todos</div>
                                    </div>

                                    <div v-for="productType in filtersFiltered.productTypes" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(productType, 'products')" v-bind:class="{ 'selected': productType.active }"></div>

                                        <div class="text">{{ productType.title }}</div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 productos</div>
                        </div>

                        <!--Tarifa-->
                        <div class="d-flex my-40">
                            <div class="text">Tarifa:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('fees')"  v-bind:class="{'seeing': isSeeingFiltersPc.fees}" v-if="filtersFiltered.fees && filtersFiltered.fees.length > 0">

                                <div class="ml-10" data-color="azul">{{ getFeeFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">

                                    <div class="form-group">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.fee" type="text" placeholder="Busca tu tarifa...">
                                        </div>
                                    </div>

                                    <div class="d-flex align-center" v-if="filtersFiltered.fees && filtersFiltered.fees.length > 0">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('fees')" v-bind:class="{ 'selected': areAllFeesActives }"></div>

                                        <div class="text">Todos</div>
                                    </div>

                                    <div v-for="fee in filtersFiltered.fees" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(fee, 'fees')" v-bind:class="{ 'selected': fee.active }"></div>

                                        <div class="text">{{ fee.title }}</div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 tarifas</div>
                        </div>

                        <!--Comercializadora-->
                        <div class="d-flex my-40">
                            <div class="text">Comercializad.:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('marketer')" v-bind:class="{'seeing': isSeeingFiltersPc.marketer}" v-if="filtersFiltered.marketers && filtersFiltered.marketers.length > 0">

                                <div class="ml-10" data-color="azul">{{ getMarketerFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">

                                    <div class="form-group ">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.marketer" type="text" placeholder="Busca tu comercializadora...">
                                        </div>
                                    </div>

                                    <div class="d-flex align-center" v-if="filtersFiltered.marketers && filtersFiltered.marketers.length > 0">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('marketers')" v-bind:class="{ 'selected': areAllMarketersActives }"></div>

                                        <div class="text">Todos</div>
                                    </div>

                                    <div v-for="marketer in filtersFiltered.marketers" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(marketer, 'marketer')" v-bind:class="{ 'selected': marketer.active }"></div>

                                        <div class="text">{{ marketer.title === '' ? 'Sin comercializadora' : marketer.title }}</div>

                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 comercializad.</div>
                        </div>

                        <!--Productos comercializadora-->
                        <div class="d-flex my-40">
                            <div class="text">Productos comerc.:</div>

                            <div class="custom-select no-hover" v-on:click.stop="seeFilters('productMarketer')"  v-bind:class="{'seeing': isSeeingFiltersPc.productMarketer}" v-if="filtersFiltered.products && filtersFiltered.products.length > 0">

                                <div class="ml-10" data-color="azul">{{ getProductMarketerFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left form">

                                    <div class="form-group ">
                                        <div class="input-group">
                                            <input data-size="12" v-model="searchFilters.productMarketer" type="text" placeholder="Busca tu prod. de comerc. ...">
                                        </div>
                                    </div>

                                    <div class="d-flex align-center" v-if="filtersFiltered.products && filtersFiltered.products.length > 0">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('productsMarketer')" v-bind:class="{ 'selected': areAllProductsMarketerActives }"></div>

                                        <div class="text">Todos</div>

                                    </div>
                                    <div v-for="productType in filtersFiltered.products" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(productType, 'products')" v-bind:class="{ 'selected': productType.active }"></div>

                                        <div class="text">{{ productType.title }}</div>

                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 productos</div>
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
                    <div class="custom-button before-search" data-size="small" data-bg="rojo" data-mode="translucent" v-on:click="resetFilters">Borrar filtros</div>
                </div>

            </div>
        </div>
    </div>

</template>

<script>
export default {
    name: "DesktopComponent",
    props: ['basicData'],
    data() {
        return {
            partSeeing: 0,
            isSeeingFilters: false,
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
                sort: false
            },
            isSeeingCreateOrder: false,
            orders: '',
            totalOrders: 0,
            searchOrderText: '',
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
                    sortBy: {
                        title: 'Ordenar por',
                        checked: 3,
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
                                title: 'Más antiguo',
                                value: 2
                            },
                            {
                                title: 'Más reciente',
                                value: 3
                            }
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
                fees: [],
                dates: {
                    start: '',
                    end: ''
                },
                activationDates: {
                    start: '',
                    end: ''
                },

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
            ],
            statuses:[
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
                },
                /*
                {
                    code: 'al',
                    title: 'Liquidado agente',
                    color: 'agentLiquidatedStatus',
                    limitedTo: ['65cb57489c2c285441086a43', '65fc2c029e5f5622e308cea3']
                },
                {
                    code: 'cl',
                    title: 'Liquidado comercializadora',
                    color: 'marketerLiquidatedStatus',
                    limitedTo: ['65cb57489c2c285441086a43', '65fc2c029e5f5622e308cea3']
                },
                {
                    code: 'tl',
                    title: 'Total liquidado',
                    color: 'totalLiquidatedStatus',
                    limitedTo: ['65cb57489c2c285441086a43', '65fc2c029e5f5622e308cea3']
                },
            */
            ],
            ordersSelected: [],
            orderSelectedToSee: '',
            orderTypeSelected: '', //sort
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
                salesCommision: '',
                asercordCommision: '',
                CUPS: '',
                consumption: '',
                IBAN: '',
                docs: [],
                statuses: [],
                newStatus: {
                    code: 'p',
                    date: ''
                },
                errors: {}
            },
            orderToModify: '',
            selectValues: '',
            isCreatingOrder: false,
            isEditing: false,
            currentPage: 1,
            perPage: 50,
            totalPages: 1,
            perPageOptions: [50, 100, 200],
            debouncedFetchAllOrders: null,
            marketers: [],
            marketerProductsOthers: ['Sin excedentes', 'Con excedentes', 'Compartido'],
        }
    },
    created() {
        // Al crear el componente, aplicamos debounce al metodo fetchAllOrers
        this.debouncedFetchAllOrders = this.debounce(this.fetchAllOrders, 300);
    },
    mounted() {
        if (this.basicData.userLogged && this.basicData.userLogged._id && this.basicData.userList) {
            this.fetchAllAccounts()
            this.fetchAllMarketers()
            this.fetchAllOrders(true)
            //this.getOrderTypeSelected()
            this.putDates()
            //Saco los valores para los selects
            this.fetchSelectValues()
        }

        //console.log(this.$cookies.get('filters')['orders']['perPage'])

        this.orderToModify = JSON.parse(JSON.stringify(this.order));

        this.perPage = this.$cookies.get('filters')['orders']['perPage']
    },
    watch: {
        "basicData.userLogged"() {
            this.fetchAllOrders(true)
            //this.getOrderTypeSelected()
            this.putDates()

            if (this.basicData.userList) {
                this.fetchAllAccounts()
                this.fetchAllMarketers()
            }

        },
    },
    methods: {
        fetchAllOrders(filtersFromCookies) {

            this.orders = [];

            //establezco los filtros de la manera de la que se va a filtrar en la consulta
            this.setFiltersToSend()

            //console.log('isFirstTime: !!filtersFromCookies --->', filtersFromCookies === true)


            //Para ver si la primera vez que se entra tiene filtros ya guardados en la cookie
            let hasFilteredFromCookies = (this.$cookies.get('filters')['orders']['agents'].length > 0 || this.$cookies.get('filters')['orders']['statuses'].length > 0 || this.$cookies.get('filters')['orders']['marketers'].length > 0 || this.$cookies.get('filters')['orders']['productTypes'].length > 0 || this.$cookies.get('filters')['orders']['products'].length > 0 || (this.$cookies.get('filters')['orders']['dates']['start'] !== '' || this.$cookies.get('filters')['orders']['dates']['end'] !== '') || (this.$cookies.get('filters')['orders']['activationDates']['start'] !== '' || this.$cookies.get('filters')['orders']['activationDates']['end'] !== ''))


            console.log('filtros antes de enviar --> ', this.$cookies.get('filters')['orders'])

            axios.post(`/api/orders/index`, {
                userLogged: this.basicData.userLogged,
                userList: this.basicData.userList,
                filters: this.$cookies.get('filters')['orders'],
                commisionType: this.partSeeing,
                searchOrderText: this.searchOrderText,
                page: this.currentPage,
                perPage: this.perPage,
                filtersFromCookies: (filtersFromCookies === true && hasFilteredFromCookies),
                isFirstTime: filtersFromCookies === true
            })
                .then((res) => {
                    this.orders = res.data.orders

                    //console.log('filtersObtained --> ',res.data.filtersObtained)

                    //Establezco los filtros para filtrar si no estaban ya establecidos
                    if (res.data.filtersObtained !== null && res.data.filtersObtained[0]) {
                        this.filtersObtained.agents = res.data.filtersObtained[0].agents;
                        this.filtersObtained.marketers = res.data.filtersObtained[0].marketers;
                        this.filtersObtained.productTypes = res.data.filtersObtained[0].productTypes;
                        this.filtersObtained.products = res.data.filtersObtained[0].products;
                        this.filtersObtained.statuses = JSON.parse(JSON.stringify(res.data.filtersObtained[0].statuses));
                        this.filtersObtained.fees = res.data.filtersObtained[0].fees;


                        //Establezco los filtros para usar
                        this.setFilters(true);
                    }

                    //Establezco el número total de páginas
                    this.totalPages = Math.ceil(res.data.totalResults / this.perPage);

                    //Establezco el número total de pedidos
                    this.totalOrders = res.data.totalResults;

                })
                .catch((err) => {
                    console.log(err)
                })
        },
        resetSearchresetSearch(type){

            this.searchOrderText = ''

            /*if (type === 'mobile')
                this.$refs.searchContractMobile.focus();
            else
                this.$refs.searchContract.focus();*/

            //this.debouncedFetchAllOrders()
        },
        changePageSize(event) {

            let cookiesFilter = this.$cookies.get('filters');

            cookiesFilter['orders']['perPage'] = event.target.value;

            this.$cookies.set('filters', JSON.parse(JSON.stringify(cookiesFilter)))


            this.currentPage = 1;

            this.fetchAllOrders()
        },
        //FILTROS
        setFilters(hasFilteredFromCookies) {

            //ESTABLEZCO LOS FILTROS PARA USAR

            // Agentes
            this.filtersObtained.agents = this.filtersObtained.agents.map(agentId => {
                if (!agentId) return null; // Excluir null o ''
                let user = this.basicData.userList.find(user => user._id === agentId);
                if (!user && agentId === this.basicData.userLogged._id) user = this.basicData.userLogged

                let fromCookies = hasFilteredFromCookies && this.$cookies.get('filters')['orders']['agents'] && this.$cookies.get('filters')['orders']['agents'].length > 0;
                let isActive = fromCookies && this.$cookies.get('filters')['orders']['agents'] && this.$cookies.get('filters')['orders']['agents'].includes(user._id);

                return user ? {
                    code: user._id,
                    name: user.firstName + ' ' + user.lastName,
                    active: fromCookies ? isActive : true
                } : null;
            }).filter(agent => agent !== null).sort((a, b) => a.name.localeCompare(b.name));

            // Estados
            this.filtersObtained.statuses = this.filtersObtained.statuses.map(statusCode => {
                if (!statusCode) return null; // Excluir null o ''
                let state = this.statuses.find(state => state.code === statusCode);

                let fromCookies = hasFilteredFromCookies && this.$cookies.get('filters')['orders']['statuses'] && this.$cookies.get('filters')['orders']['statuses'].length > 0;
                let isActive = fromCookies && this.$cookies.get('filters')['orders']['statuses'] && this.$cookies.get('filters')['orders']['statuses'].includes(state.code);

                return state ? {
                    title: state.title,
                    code: state.code,
                    active: fromCookies ? isActive : true
                } : null;
            }).filter(status => status !== null).sort((a, b) => a.code.localeCompare(b.code));

            // Comercializadoras
            this.filtersObtained.marketers = this.filtersObtained.marketers.map(marketer => {

                if (marketer === '') {
                    marketer = 'Sin comercializadora';
                }

                let fromCookies = hasFilteredFromCookies && this.$cookies.get('filters')['orders']['marketers'] && this.$cookies.get('filters')['orders']['marketers'].length > 0;
                let isActive = fromCookies && this.$cookies.get('filters')['orders']['marketers'] && this.$cookies.get('filters')['orders']['marketers'].includes(marketer);

                return {
                    title: marketer,
                    code: marketer,
                    active: fromCookies ? isActive : true
                }
            }).filter(marketer => marketer !== null).sort((a, b) => a.code.localeCompare(b.code));

            // Tipo de producto
            this.filtersObtained.productTypes = this.filtersObtained.productTypes.map(productType => {
                if (!productType) return null; // Excluir null o ''
                let prodType = this.productTypes.find(pType => pType.code === productType);

                let fromCookies = hasFilteredFromCookies && this.$cookies.get('filters')['orders']['productTypes'] && this.$cookies.get('filters')['orders']['productTypes'].length > 0;
                let isActive = fromCookies && this.$cookies.get('filters')['orders']['productTypes'] && this.$cookies.get('filters')['orders']['productTypes'].includes(prodType.code);

                return prodType ? {
                    title: prodType.title,
                    code: prodType.code,
                    active: fromCookies ? isActive : true
                } : null;
            }).filter(productType => productType !== null).sort((a, b) => a.code.localeCompare(b.code));

            // Productos
            this.filtersObtained.products = this.filtersObtained.products.map(product => {
                if (!product) return null; // Excluir null o ''
                let fromCookies = hasFilteredFromCookies && this.$cookies.get('filters')['orders']['products'] && this.$cookies.get('filters')['orders']['products'].length > 0;
                let isActive = fromCookies && this.$cookies.get('filters')['orders']['products'] && this.$cookies.get('filters')['orders']['products'].includes(product);

                return {
                    title: product,
                    code: product,
                    active: fromCookies ? isActive : true
                }
            }).filter(product => product !== null).sort((a, b) => a.code.localeCompare(b.code));

            // Tarifas
            this.filtersObtained.fees = this.filtersObtained.fees.map(fee => {
                if (!fee) return null; // Excluir null o ''
                let fromCookies = hasFilteredFromCookies && this.$cookies.get('filters')['orders']['fee'] && this.$cookies.get('filters')['orders']['fee'].length > 0;
                let isActive = fromCookies && this.$cookies.get('filters')['orders']['fee'] && this.$cookies.get('filters')['orders']['fee'].includes(fee);

                return {
                    title: fee,
                    code: fee,
                    active: fromCookies ? isActive : true
                }
            }).filter(fee => fee !== null).sort((a, b) => a.code.localeCompare(b.code));


        }, //Filtros obtenidos por controlador
        setFiltersToSend() { //Filtros para enviar a controlador y sacar pedidos

            let extractActiveCodes = (items) => items.filter(item => item.active).map(item => item.code);

            let cookiesFilter = this.$cookies.get('filters');

            if (this.filtersFiltered['agents'] && this.filtersFiltered['agents'].length > 0)
                cookiesFilter['orders']['agents'] = extractActiveCodes(this.filtersFiltered['agents']);

            if (this.filtersFiltered['statuses'] && this.filtersFiltered['statuses'].length > 0)
                cookiesFilter['orders']['statuses'] = extractActiveCodes(this.filtersFiltered['statuses']);

            if (this.filtersFiltered['marketers'] && this.filtersFiltered['marketers'].length > 0) {
                cookiesFilter['orders']['marketers'] = extractActiveCodes(this.filtersFiltered['marketers']);

                // Convertir '' de vuelta a 'Sin comercializadora'
                cookiesFilter['orders']['marketers'] = cookiesFilter['orders']['marketers'].map(code => code === 'Sin comercializadora' ? '' : code);
            }

            if (this.filtersFiltered['products'] && this.filtersFiltered['products'].length > 0)
                cookiesFilter['orders']['products'] = extractActiveCodes(this.filtersFiltered['products']);

            if (this.filtersFiltered['productTypes'] && this.filtersFiltered['productTypes'].length > 0)
                cookiesFilter['orders']['productTypes'] = extractActiveCodes(this.filtersFiltered['productTypes']);

            if (this.filtersFiltered['fees'] && this.filtersFiltered['fees'].length > 0)
                cookiesFilter['orders']['fees'] = extractActiveCodes(this.filtersFiltered['fees']);

            if (this.filtersObtained.dates.start !== '' || this.filtersObtained.dates.end !== '')
                cookiesFilter['orders']['dates'] = this.filtersObtained.dates;

            if (this.filtersObtained.activationDates.start !== '' || this.filtersObtained.activationDates.end !== '')
                cookiesFilter['orders']['activationDates'] = this.filtersObtained.activationDates;


            // Comprobar si hay algún producto distinto de 'cl' o 'cg'
            let containsOtherProductType = cookiesFilter['orders']['productTypes'].some(type => type !== 'cl' && type !== 'cg');

            // Si existe algún producto diferente de 'cl' o 'cg', añadir '' a marketers
            if (containsOtherProductType && (!cookiesFilter['orders']['marketers'] || !cookiesFilter['orders']['marketers'].includes(''))) {
                cookiesFilter['orders']['marketers'] = cookiesFilter['orders']['marketers'] || [];
                cookiesFilter['orders']['marketers'].push('');
            }

            this.$cookies.set('filters', JSON.parse(JSON.stringify(cookiesFilter)))
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
            let cookies = this.$cookies.get('filters');

            //Elimino el filtro que deseo
            switch (date) {
                case 'dates.start':
                    cookies['orders']['dates']['start'] = this.filtersObtained.dates.start;
                    break;

                case 'dates.end':
                    cookies['orders']['dates']['end'] = this.filtersObtained.dates.end;
                    break;

                case 'activationDates.start':
                    cookies['orders']['activationDates']['start'] = this.filtersObtained.activationDates.start;
                    break;

                case 'activationDates.end':
                    cookies['orders']['activationDates']['end'] = this.filtersObtained.activationDates.end;
                    break;
            }

            this.$cookies.set('filters', cookies);

            //Cargo de nuevo los pedidos
            this.fetchAllOrders()
        },
        deleteFilter(filter) {

            let cookies = this.$cookies.get('filters');

            //Elimino el filtro que deseo
            switch (filter) {
                case 'dates.start':
                    cookies['orders']['dates']['start'] = '';
                    this.filtersObtained.dates.start = '';
                    break;

                case 'dates.end':
                    cookies['orders']['dates']['end'] = '';
                    this.filtersObtained.dates.end = '';
                    break;

                case 'activationDates.start':
                    cookies['orders']['activationDates']['start'] = '';
                    this.filtersObtained.activationDates.start = '';
                    break;

                case 'activationDates.end':
                    cookies['orders']['activationDates']['end'] = '';
                    this.filtersObtained.activationDates.end = '';
                    break;
            }

            this.$cookies.set('filters', cookies);

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
            this.filtersObtained.products.forEach(product => product.active = true)




            //Reseteo las fechas
            this.filtersObtained.dates.start = ''
            this.filtersObtained.dates.end = ''

            this.filtersObtained.activationDates.start = ''
            this.filtersObtained.activationDates.end = ''


            let cookiesFilter = this.$cookies.get('filters');

            cookiesFilter['orders']['dates'] = this.filtersObtained.dates;
            cookiesFilter['orders']['activationDates'] = this.filtersObtained.activationDates;

            this.$cookies.set('filters', JSON.parse(JSON.stringify(cookiesFilter)))

            this.fetchAllOrders()
        },
        //PAGINACIÓN
        changePage(value) {

            switch (value) {
                case -1:

                    if (this.currentPage > 1) {
                        this.currentPage--;

                        //recargo los pedidos
                        this.fetchAllOrders()
                    }

                    break;

                case 1:

                    if (this.currentPage < this.totalPages) {
                        this.currentPage++;

                        //recargo los pedidos
                        this.fetchAllOrders()
                    }

                    break;
            }
        },
        getOrderTypeSelected() {
            this.orderTypeSelected = this.filters.radio.sortBy.data[$cookies.get('filters')['orders']['sortBy']].title
        },
        putDates() {

            let cookies = this.$cookies.get('filters');

            this.filtersObtained.dates.start = cookies['orders']['dates']['start']
            this.filtersObtained.dates.end = cookies['orders']['dates']['end']
            this.filtersObtained.activationDates.start = cookies['orders']['activationDates']['start']
            this.filtersObtained.activationDates.end = cookies['orders']['activationDates']['end']
        },
        fetchAllAccounts() {
            axios.post(`/api/accounts/getRelatedAccounts/${this.basicData.userLogged._id}`, {userList: JSON.stringify(this.basicData.userList)})
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
            await axios.get('/api/marketers')
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
        createOrder(order) {

            this.orderToModify = order;

            //Si no se esta creando todavia otro
            if (this.isCreatingOrder === false) {

                this.isCreatingOrder = true;

                this.orderToModify.errors = {};


                //Validaciones
                let hasErrors = false;


                if (this.orderToModify.newStatus.code !== 'bo') {

                    //Título
                    if (!this.orderToModify.name) {
                        this.orderToModify.errors.name = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    //Dirección de suministro
                    if (!this.orderToModify.direc) {
                        this.orderToModify.errors.direc = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }


                    //Poblacion
                    if (!this.orderToModify.town) {
                        this.orderToModify.errors.town = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }


                    //Provincia
                    if (!this.orderToModify.province) {
                        this.orderToModify.errors.province = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    //Código postal
                    if (!this.orderToModify.zip) {
                        this.orderToModify.errors.zip = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    if (this.orderToModify.zip && this.orderToModify.zip.length !== 5) {
                        this.orderToModify.errors.zip = 'El zip tiene que tener 5 digitos';
                        hasErrors = true;
                    }

                    if (this.orderToModify.zip && isNaN(this.orderToModify.zip)) {
                        this.orderToModify.errors.zip = this.getErrorMessage('onlyNumbers');
                        hasErrors = true;
                    }


                    //Tipo de producto
                    if (!this.orderToModify.productType) {
                        this.orderToModify.errors.productType = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }


                    //Producto ( si es luz o gas se selecciona toda la ramificación de abajo y sino se puede añadir)
                    if (!this.orderToModify.product) {
                        this.orderToModify.errors.product = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }


                    //SI ES CONTRATO DE LUZ O DE GAS

                    //Tarifa
                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg') && !this.orderToModify.fee) {
                        this.orderToModify.errors.fee = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    //Comercializadora
                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg') && !this.orderToModify.marketer) {
                        this.orderToModify.errors.marketer = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    //IBAN
                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg') && !this.orderToModify.IBAN) {
                        this.orderToModify.errors.IBAN = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    if (this.orderToModify.IBAN && this.orderToModify.IBAN.length !== 29) {
                        this.orderToModify.errors.IBAN = 'IBAN no válido';
                        hasErrors = true
                    }

                    //CUPS
                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg') && !this.orderToModify.CUPS) {
                        this.orderToModify.errors.CUPS = this.getErrorMessage('isEmpty');
                        hasErrors = true
                    }

                    if (this.orderToModify.CUPS && this.orderToModify.CUPS.length !== 20) {
                        this.orderToModify.errors.CUPS = 'CUPS no válido';
                        hasErrors = true
                    }

                    //comisión
                    if (this.orderToModify.salesCommision !== '' && isNaN(this.orderToModify.salesCommision)) {
                        this.orderToModify.errors.salesCommision = 'La comisión debe ser típo numérico';
                        hasErrors = true
                    }


                    if (this.orderToModify.salesCommision !== '' && parseFloat(this.orderToModify.salesCommision) < 0 && this.orderToModify.newStatus.code !== 'b') {
                        this.orderToModify.errors.salesCommision = 'Establece estado Baja para una comisión negativa';
                        hasErrors = true
                    }

                    if (this.orderToModify.salesCommision !== '' && parseFloat(this.orderToModify.salesCommision) > 0 && this.orderToModify.newStatus.code === 'b') {
                        this.orderToModify.errors.salesCommision = 'Establece una comisión negativa';
                        hasErrors = true
                    }

                    //comisión asercord
                    if (!!this.orderToModify.asercordCommision && this.orderToModify.asercordCommision !== '' && isNaN(this.orderToModify.asercordCommision)) {
                        this.orderToModify.errors.asercordCommision = 'La comisión debe ser típo numérico';
                        hasErrors = true
                    }

                    //si tiene estado de verificacion cambio de potencia y no tiene puestos
                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg') && !!this.orderToModify.verifications && this.orderToModify.verifications.includes('pc') && !this.orderToModify.currentPotencyVerification) {
                        this.orderToModify.errors.currentPotencyVerification = 'No puede estar vacía';
                        hasErrors = true
                    }

                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg') && !!this.orderToModify.verifications && this.orderToModify.verifications.includes('pc') && !!this.orderToModify.currentPotencyVerification && isNaN(this.orderToModify.currentPotencyVerification)) {
                        this.orderToModify.errors.currentPotencyVerification = 'Debe ser numérico';
                        hasErrors = true
                    }


                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg') && this.orderToModify.verifications && this.orderToModify.verifications.includes('pc') && !this.orderToModify.requestedPotencyVerification) {
                        this.orderToModify.errors.requestedPotencyVerification = 'No puede estar vacía';
                        hasErrors = true
                    }

                    if ((this.orderToModify.productType === 'cl' || this.orderToModify.productType === 'cg') && this.orderToModify.verifications && this.orderToModify.verifications.includes('pc') && !!this.orderToModify.requestedPotencyVerification && isNaN(this.orderToModify.requestedPotencyVerification)) {
                        this.orderToModify.errors.requestedPotencyVerification = 'Debe ser numérico';
                        hasErrors = true
                    }

                }

                //Documentos de pedido
                this.orderToModify.docs.forEach((doc) => {
                    if (doc.title === '' && doc.defaultTitle)
                        doc.title = doc.defaultTitle
                })

                //console.log('hasErrors --> ', hasErrors, this.orderToModify.errors)

                if (!hasErrors) {

                    let data = new FormData();

                    data.append('order', JSON.stringify(this.orderToModify))
                    data.append('account', JSON.stringify(this.accountToAddOrder))

                    //Meto los documentos de los pedidos
                    //Para cada pedido compruebo si hay documentos adjuntados, si los hay los paso
                    if (this.orderToModify.docs.length > 0) {
                        this.orderToModify.docs.forEach((doc, docInd) => {
                            data.append(('docFile' + docInd), doc.fileValue);
                        })
                    }


                    axios.post(`/api/orders`, data)
                        .then((res) => {

                            //limpio el pedido
                            this.orderToModify = JSON.parse(JSON.stringify(this.order));

                            // deselecciono la cuenta
                            this.accountToAddOrder = '';

                            this.isCreatingOrder = false;

                            //Recargo los pedidos de nuevo
                            this.fetchAllOrders()

                            //Muestro mensaje
                            Swal.fire({
                                icon: 'success',
                                title: '¡Contrato creado!',
                                timer: 1500,
                                timerProgressBar: true
                            })


                        })
                        .catch((err) => {
                            console.log(err)

                            this.isCreatingOrder = false;
                        })
                } else {
                    this.isCreatingOrder = false;
                }
            }
        },
        updateOrder() {
            //Validaciones
            let hasErrors = false;

            //Título
            if (!this.orderToModify.name) {
                this.orderToModify.errors.name = this.getErrorMessage('isEmpty');
                hasErrors = true
            }

            //Fecha de tramitación
            if (!this.orderToModify.processingDate) {
                this.orderToModify.errors.processingDate = this.getErrorMessage('isEmpty');
                hasErrors = true
            }

            //Producto
            if (!this.orderToModify.product) {
                this.orderToModify.errors.product = this.getErrorMessage('isEmpty');
                hasErrors = true
            }

            //Comercializadora
            if (!this.orderToModify.marketer) {
                this.orderToModify.errors.marketer = this.getErrorMessage('isEmpty');
                hasErrors = true
            }

            //Tarifa
            if (!this.orderToModify.fee) {
                this.orderToModify.errors.fee = this.getErrorMessage('isEmpty');
                hasErrors = true
            }


            if (!hasErrors) {

                let data = new FormData();

                data.append('order', JSON.stringify(this.orderToModify))
                data.append('account', JSON.stringify(this.accountToUpdateOrder))
                data.append('userSubdomain', JSON.stringify(this.userSubdomain))

                //Meto los documentos de los pedidos
                //Para cada pedido compruebo si hay documentos adjuntados, si los hay los paso
                if (this.orderToModify.docs.length > 0) {
                    this.orderToModify.docs.forEach((doc, docInd) => {
                        data.append(('docFile' + docInd), doc.value);
                    })
                }

                axios.post(`/api/orders/update`, data)
                    .then((res) => {

                        //limpio el pedido
                        this.orderToModify = JSON.parse(JSON.stringify(this.order));

                        // deselecciono la cuenta
                        this.accountToUpdateOrder = '';

                        //Recargo los pedidos de nuevo
                        this.fetchAllOrders()

                        //Muestro mensaje
                        Swal.fire({
                            icon: 'success',
                            title: '¡Contrato creado!',
                            timer: 1500,
                            timerProgressBar: true
                        })
                    })
                    .catch((err) => {
                        console.log(err)
                    })
            }
        },
        addSelectType(data) {

            let type = data.type;
            let value = data.value;

            axios.post(`/api/select`, {type: type, value: value})
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
        },
        selectOrderType(orderType) {
            //Recorro los tipos de ordenacion y los pongo todos a false menos el que se ha seleccionado
            let cookies = this.$cookies.get('filters');

            cookies['orders']['sortBy'] = orderType.value

            this.$cookies.set('filters', cookies);

            this.isSeeingFiltersPc.sort = false;

            this.getOrderTypeSelected()

            //Recargo los pedidos
            this.fetchAllOrders();
        },
        selectNewOrderType(orderType) {

            //Recorro los tipos de ordenacion y los pongo todos a false menos el que se ha seleccionado
            let cookies = this.$cookies.get('filters');

            switch (orderType) {

                case 'title':

                    if (this.$cookies.get('filters')['orders']['sortBy'] !== 0 && this.$cookies.get('filters')['orders']['sortBy'] !== 1)
                        cookies['orders']['sortBy'] = 0
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 0)
                        cookies['orders']['sortBy'] = 1
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 1)
                        cookies['orders']['sortBy'] = 3

                    break;

                case 'agent':

                    if (this.$cookies.get('filters')['orders']['sortBy'] !== 4 && this.$cookies.get('filters')['orders']['sortBy'] !== 5)
                        cookies['orders']['sortBy'] = 4
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 4)
                        cookies['orders']['sortBy'] = 5
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 5)
                        cookies['orders']['sortBy'] = 3

                    break;

                case 'nif':

                    if (this.$cookies.get('filters')['orders']['sortBy'] !== 6 && this.$cookies.get('filters')['orders']['sortBy'] !== 7)
                        cookies['orders']['sortBy'] = 6
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 6)
                        cookies['orders']['sortBy'] = 7
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 7)
                        cookies['orders']['sortBy'] = 3

                    break;

                case 'fee':

                    if (this.$cookies.get('filters')['orders']['sortBy'] !== 16 && this.$cookies.get('filters')['orders']['sortBy'] !== 17)
                        cookies['orders']['sortBy'] = 16
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 16)
                        cookies['orders']['sortBy'] = 17
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 17)
                        cookies['orders']['sortBy'] = 3

                    break;

                case 'product':

                    if (this.$cookies.get('filters')['orders']['sortBy'] !== 8 && this.$cookies.get('filters')['orders']['sortBy'] !== 9)
                        cookies['orders']['sortBy'] = 8
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 8)
                        cookies['orders']['sortBy'] = 9
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 9)
                        cookies['orders']['sortBy'] = 3

                    break;

                case 'cups':

                    if (this.$cookies.get('filters')['orders']['sortBy'] !== 10 && this.$cookies.get('filters')['orders']['sortBy'] !== 11)
                        cookies['orders']['sortBy'] = 10
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 10)
                        cookies['orders']['sortBy'] = 11
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 11)
                        cookies['orders']['sortBy'] = 3

                    break;

                case 'status':

                    if (this.$cookies.get('filters')['orders']['sortBy'] !== 12 && this.$cookies.get('filters')['orders']['sortBy'] !== 13)
                        cookies['orders']['sortBy'] = 12
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 12)
                        cookies['orders']['sortBy'] = 13
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 13)
                        cookies['orders']['sortBy'] = 3

                    break;

                case 'lastStatusAt':

                    if (this.$cookies.get('filters')['orders']['sortBy'] !== 14 && this.$cookies.get('filters')['orders']['sortBy'] !== 15)
                        cookies['orders']['sortBy'] = 14
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 14)
                        cookies['orders']['sortBy'] = 15
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 15)
                        cookies['orders']['sortBy'] = 3

                    break;

                case 'createdAt':

                    if (this.$cookies.get('filters')['orders']['sortBy'] !== 2 && this.$cookies.get('filters')['orders']['sortBy'] !== 3)
                        cookies['orders']['sortBy'] = 2
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 2)
                        cookies['orders']['sortBy'] = 3
                    else if (this.$cookies.get('filters')['orders']['sortBy'] === 3)
                        cookies['orders']['sortBy'] = 2

                    break;
            }


            this.$cookies.set('filters', cookies);

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

            this.fetchAllOrders()
        },
        activateAgentAndSubordinates(agentId) {

            // Encontrar el agente en filtersFiltered.agents por _id
            let agent = this.filtersFiltered.agents.find(a => a.code === agentId);

            if (!!agent) {
                // Activar el agente
                if (!agent.active) agent.active = true;

                // Buscar y activar recursivamente todos los agentes que tienen como responsable al agente actual
                let subordinates = this.basicData.userList.filter(u => u.responsibles.includes(agentId));

                subordinates.forEach(subordinate => {
                    this.activateAgentAndSubordinates(subordinate._id);
                });
            }
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

                    axios.delete(`/api/orders/delete/${order['_id']}`, {
                        params: {
                            order
                        }
                    })
                        .then((res) => {

                            //Saco de cliente
                            this.orders = this.orders.filter(orderNow => orderNow._id.$oid !== order['_id']['$oid'])

                            //Establezco los filtros de nuevo
                            this.setFilters()

                        })
                        .catch((err) => {
                            console.log(err)
                        })
                }
            })
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

            let status = this.statuses.find((status) => {
                return status.code === recentStatus.code
            })

            return status
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
        getLastStatusDate(order) {

            let recentStatus = order.statuses.reduce((latest, current) => {
                return new Date(current.date) > new Date(latest.date) ? current : latest;
            });

            let dateNow = new Date(recentStatus.date);
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

            this.isSeeingCreateOrder = false;

            this.accountToAddOrder = id
        },
        seeFilters(type) {

            //Cerrar selects
            this.hideCustomSelects()

            switch (type) {
                case 'agent':
                    this.isSeeingFiltersPc.agent = true;
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

                case 'fees':
                    this.isSeeingFiltersPc.fees = true;
                    break;

                case 'sort':
                    this.isSeeingFiltersPc.sort = true;
                    break;
            }
        },
        hideCustomSelects() {

            //Cierro el select de crear pedido
            this.isSeeingCreateOrder = false;

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
        }
    },
    computed: {
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

                let status = this.statuses.find((status) => status.code === recentStatus.code);

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

            if (!this.filtersFiltered.agents || (this.filtersFiltered.agents && this.filtersFiltered.agents.length === 0)) return '0 agentes'

            this.filtersFiltered.agents.forEach((agent) => {
                if (agent.active) totalActives++;
            })

            return totalActives === this.filtersFiltered.agents.length ? 'Todos' : (totalActives + ' agentes/s')
        },
        getMarketerFilterTitle() {

            let totalActives = 0;

            if (!this.filtersFiltered.marketers || (this.filtersFiltered.marketers && this.filtersFiltered.marketers.length === 0)) return '0 comercializad.'


            this.filtersFiltered.marketers.forEach((marketer) => {
                if (marketer.active) totalActives++;
            })

            return totalActives === this.filtersFiltered.marketers.length ? 'Todos' : (totalActives + ' comercializad/s')
        },
        getStatusFilterTitle() {

            let totalActives = 0;

            if (!this.filtersFiltered.statuses || (this.filtersFiltered.statuses && this.filtersFiltered.statuses.length === 0)) return '0 estados'

            this.filtersFiltered.statuses.forEach((status) => {
                if (status.active) totalActives++;
            })

            return totalActives === this.filtersFiltered.statuses.length ? 'Todos' : (totalActives + ' estado/s')
        },
        getFeeFilterTitle() {

            let totalActives = 0;

            if (!this.filtersFiltered.fees || (this.filtersFiltered.fees && this.filtersFiltered.fees.length === 0)) return '0 estados'

            this.filtersFiltered.fees.forEach((fee) => {
                if (fee.active) totalActives++;
            })

            return totalActives === this.filtersFiltered.fees.length ? 'Todos' : (totalActives + ' tarifa/s')

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
        getPrettyDateTransfer(date){
            return date.replace(/\/(\d{2})$/, '/20$1');
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


            //console.log('agentes --> ', this.filtersObtained.agents)

            this.filtersObtained.agents.forEach((agent) => {

                //console.log('agente --> ', agent)

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

                //console.log('productType --> ', productType)

                let OptSearch = productType.title.replaceAll(' ', '').toLowerCase()

                if (OptSearch.includes(ProductSearch)) filteredFiltered.productTypes.push(productType)
            })


            let hasCgOrCl = this.filtersObtained.productTypes.some(item =>
                item.active && (item.code === "cl" || item.code === "cg")
            );

            let hasCg = this.filtersObtained.productTypes.some(item => item.code === "cg" && item.active);
            let hasCl = this.filtersObtained.productTypes.some(item => item.code === "cl" && item.active);
            let hasOther = this.filtersObtained.productTypes.some(item => (item.code !== "cl" && item.code !== "cg") && item.active);


            //Tarifas ( solo si cl o cg esta active )
            let FeeSearch = this.searchFilters.fee.replaceAll(' ', '').toLowerCase();


            if (hasCgOrCl) {

                this.filtersObtained.fees.forEach((fee) => {

                    //console.log('fee --> ', fee)

                    let OptSearch = fee.title.replaceAll(' ', '').toLowerCase()

                    let pushFee = (fee.title.includes('TD') && hasCl) || (fee.title.includes('RL') && hasCg) || (!fee.title.includes('TD') && ! fee.title.includes('RL'))

                    if (OptSearch.includes(FeeSearch) && pushFee) filteredFiltered.fees.push(fee)
                })

            } else {
                filteredFiltered.fees = []
            }


            //Comercializadoras ( cl o cg y tarifa )
            let MarketerSearch = this.searchFilters.marketer.replaceAll(' ', '').toLowerCase();

            if (hasCgOrCl) {

                this.filtersObtained.marketers.forEach((marketer) => {

                    //Compruebo si la alguna tarifa de la comercializadora esta disponible
                    let selectAvaiable = false;

                    if (marketer.code !== 'Sin comercializadora') {

                        this.marketers.forEach((marketerNow) => {

                            if (marketerNow.name === marketer.code) {

                                //Comprobar si alguna de sus tarifas de luz esta activa y cl esta activo
                                let activeElec = hasCl ? marketerNow.fees.electricity.some(elecFee => filteredFiltered.fees.find(feeNow => feeNow.code === elecFee.name && feeNow.active)) : false

                                //Comprobar si alguna de sus tarifas de gas esta activa y cg esta activo
                                let activeGas = hasCg ? marketerNow.fees.gas.some(gasFee => filteredFiltered.fees.find(feeNow => feeNow.code === gasFee.name && feeNow.active)) : false

                                //Compruebo si alguna de las dos están disponibles
                                selectAvaiable = activeElec || activeGas;
                            }

                        })

                    }

                    //console.log('marketers--> ', this.marketers, marketer, filteredFiltered.fees, selectAvaiable)


                    let OptSearch = marketer.title.replaceAll(' ', '').toLowerCase()

                    if (OptSearch.includes(MarketerSearch) && selectAvaiable) filteredFiltered.marketers.push(marketer)
                })

            } else {
                filteredFiltered.marketers = []
            }


            //Productos de comercializadora ( cl o cg, tarifa y comerc. )
            let ProductMarketerSearch = this.searchFilters.productMarketer.replaceAll(' ', '').toLowerCase();

            this.filtersObtained.products.forEach((productMarketer) => {

                //console.log('this.filtersObtained.products -->', this.filtersObtained.products)

                //Compruebo los productos de los que no son cl ni cg
                let selectAvaiable = false;

                //console.log('productMarketer -->', productMarketer, this.marketerProductsOthers.includes(productMarketer.title), hasOther)

                //Compruebo si es de los otros ( que este acivado algun t. prod. de esos )
                if (this.marketerProductsOthers.includes(productMarketer.title)) {
                    if (!!hasOther)
                        selectAvaiable = true;

                } else {//Compruebo si es de luz o gas

                    //Saco la comercializadora q es ya que tiene dentro toda la info de esta
                    this.marketers.forEach((marketerNow) => {

                        //Si la comercializadora esta disponible sigo con las comprobaciones, sino directamente no
                        if (filteredFiltered.marketers.some(marketerToSearchNow => marketerToSearchNow.title === marketerNow.name && marketerToSearchNow.active)) {


                            let activeElec, activeGas = false;

                            //Compruebo primero si es de luz o de gas por si hay mas de uno con el mismo nombre --> ej. Indexado y directamente si tiene alguna tarifa active
                            if (hasCl || hasCgOrCl) {

                                activeElec = marketerNow.products.electricity.some((electProd) => {
                                    let isProd = electProd.name === productMarketer.title

                                    //si es el producto compruebo si tiene alguna tarifa active
                                    let someActiveFee = false;

                                    if (isProd)
                                        someActiveFee = electProd.fees.some(feeNow => filteredFiltered.fees.some(feeNow2 => feeNow === feeNow2.title && feeNow2.active))


                                    return isProd && someActiveFee
                                })
                            }

                            if (hasCg || hasCgOrCl) {

                                activeGas = marketerNow.products.gas.some((gasProd) => {
                                    let isProd = gasProd.name === productMarketer.title

                                    //si es el producto compruebo si tiene alguna tarifa active
                                    let someActiveFee = false;

                                    if (isProd)
                                        someActiveFee = gasProd.fees.some(feeNow => filteredFiltered.fees.some(feeNow2 => feeNow === feeNow2.title && feeNow2.active))

                                    return isProd && someActiveFee
                                })

                            }

                            if (activeElec || activeGas) selectAvaiable = true
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

                    //console.log('acc --> ', acc)

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
    }
}
</script>
