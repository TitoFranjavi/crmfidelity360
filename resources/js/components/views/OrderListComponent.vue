<template>
    <div class="content-white" @click="toggleCustomSelects">

        <!--Estilo de movil-->
        <div v-if="width < 810" class="mobile-item">
            <div class="sticky-header-mobile">
                <!--Título-->
                <div class="d-flex justify-between align-center">
                    <div class="text my-10" data-size="22" data-weight="700">Contratos</div>
                    <div v-if="canManage('contracts.create')" class="custom-button mobile-create-btn" data-size="medium" data-bg="principal" @click.stop="toggleCustomSelects('seeCreateOrder')">
                        <i class="fas fa-plus" />
                    </div>
                </div>

                <!--Dropdown crear contrato mobile-->
                <div v-if="seeCreateOrder" class="mobile-create-dropdown" @click.stop>
                    <div class="mobile-create-options" v-if="!createOrderType">
                        <p class="mobile-create-option" @click.stop="createOrderType = 'account'">
                            <i class="far fa-buildings mr-10" />Creación en cuenta
                        </p>

                        <p class="mobile-create-option" v-if="canCreateDirectOrder" @click.stop="openCreateOrderComponent()">
                            <i class="far fa-file-lines mr-10" />Creación directa
                        </p>

                        <p class="mobile-create-option" @click.stop="$refs.inputInvoiceFile.click()">
                            <i class="fas fa-file-invoice mr-10" />Creación por factura
                        </p>
                        <input type="file" ref="inputInvoiceFile" class="d-none" multiple accept=".pdf" @change="pickupInvoicesFiles" />
                    </div>
                    <div v-if="createOrderType === 'account'" class="mobile-create-account-search" @click.stop>
                        <p class="mobile-create-search-title">
                            <i class="far fa-buildings mr-5" /> Selecciona una cuenta
                        </p>
                        <div class="form-group mb-10">
                            <div class="input-group">
                                <i class="fa-regular fa-magnifying-glass"></i>
                                <input v-model="searchFilters.account" type="text" placeholder="Buscar cuenta..." />
                            </div>
                        </div>
                        <div class="mobile-create-account-list">
                            <div v-for="account in accountsFiltered" :key="account._id" class="mobile-create-account-item" @click.stop="openCreateOrderComponent(account)">
                                <div class="mobile-create-account-icon"><i class="fas fa-buildings"></i></div>
                                <span>{{ account.name }}</span>
                                <i class="far fa-chevron-right mobile-create-account-arrow"></i>
                            </div>
                            <p v-if="accounts?.length === 0" class="text opacity-5 text-center py-20" data-size="13">¡Crea primero una cuenta!</p>
                        </div>
                        <div class="mobile-create-back" @click.stop="createOrderType = null">
                            <i class="far fa-arrow-left mr-5"></i> Volver
                        </div>
                    </div>
                </div>

                <div class="d-flex">
                    <div class="search-bar w-100">
                        <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10" />
                        <input type="text" data-size="14" placeholder="Buscar un contrato..." v-model="searchFilters.search">
                    </div>

                    <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch" />
                </div>
            </div>

            <!-- Resumen -->
            <div class="d-flex column mb-10 mt-10 py-5 px-10 round" data-round="10" data-bg="gris">
                <!-- Contratos -->
                <div class="text d-flex align-center" data-size="15" data-weight="500">
                    <div class="icon mr-10"><i class="far fa-file-lines" /></div>
                    <p class="mr-5">Contratos: </p>
                    <span data-weight="600" data-size="17">{{ summaryData.totalOrders }}</span>
                </div>
                <!-- Consumo total -->
                <div class="text d-flex align-center" data-size="15" data-weight="500">
                    <div class="icon mr-10"><i class="far fa-lightbulb" /></div>
                    <p class="mr-5">Cons. Total: </p>
                    <span data-weight="600" data-size="17">{{ totalConsumption }}</span>
                </div>
                <!-- Comisión -->
                <div v-if="summaryData?.agentBelowCommission > 0" class="text d-flex align-center" data-size="15" data-weight="500">
                    <div class="icon mr-10"><i class="far fa-money-bill" /></div>
                    <p class="mr-5">Com. agentes: </p>
                    <span data-weight="600" data-size="17">{{ Math.round(summaryData.agentBelowCommission ?? 0).toLocaleString("es-ES") }}</span>
                    <span :class="{'points': basicData?.userLogged?.commInPoints}">{{ !basicData?.userLogged?.commInPoints ? '€' : 'pts.' }}</span>
                </div>
                <div class="text d-flex align-center" data-size="15" data-weight="500">
                    <div class="icon mr-10"><i class="far fa-money-bill" /></div>
                    <p class="mr-5">{{ canManage('contracts.manageCommissions') ? 'Com. agentes' : 'Mi comisión' }}</p>
                    <span data-weight="600" data-size="17">{{ Math.round(summaryData.agentCommission ?? 0).toLocaleString("es-ES") }}</span>
                    <span :class="{'points': basicData?.userLogged?.commInPoints}">{{ !basicData?.userLogged?.commInPoints ? '€' : 'pts.' }}</span>
                </div>
                <!-- Comisión subdominio SOLO CON PERMISO -->
                <div v-if="canManage('contracts.manageCommissions')" class="text d-flex align-center" data-size="15" data-weight="500">
                    <div class="icon mr-10"><i class="far fa-money-bill" /></div>
                    <p class="mr-5">Com. {{ basicData.enterprise.name }}: </p>
                    <span data-weight="600" data-size="17">{{ Math.round(summaryData.subdomainCommission ?? 0).toLocaleString("es-ES") }}</span>
                    <span :class="{'points': basicData?.userLogged?.commInPoints}">{{ !basicData?.userLogged?.commInPoints ? '€' : 'pts.' }}</span>
                </div>
                <!-- Rentabilidad -->
                <div class="text d-flex align-center" data-size="15" data-weight="500" v-if="canManage('contracts.manageCommissions')">
                    <div class="icon mr-10"><i class="far fa-money-bill" /></div>
                    <p class="mr-5">Rentabilidad: </p>
                    <span data-weight="600" data-size="17">{{ profitability }} €</span>
                </div>
                <div class="text d-flex align-center" data-size="15" data-weight="500" v-if="canManage('contracts.manageCommissions')">
                    <div class="icon mr-10"><i class="far fa-chart-line" /></div>
                    <p class="mr-5">Rentabilidad %: </p>
                    <span data-weight="600" data-size="17">{{ profitabilityPercent }} %</span>
                </div>
            </div>

            <!--Paginación-->
            <div class="d-grid my-10" data-column="2" data-layout="auto1" v-if="orders?.length > 0">

                <!--Info página-->
                <div class="d-flex justify-center my-auto" data-color="principal">
                    <div :class="['left pointer my-auto',{ 'opacity-5': currentPage === 1 }]"
                         @click="changePage(-1)"><i class="far fa-chevron-left" /></div>

                    <div class="cont mx-10 my-auto" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages }}</div>

                    <div :class="['right pointer my-auto',{ 'opacity-5': currentPage === totalPages }]"
                         @click="changePage(1)"><i class="far fa-chevron-right"></i></div>
                </div>

                <!--Selector contratos por página-->
                <div class="my-auto ml-auto d-flex">

                    <p class="text my-auto mr-10" data-size="13">Por página: </p>

                    <div class="select-content my-auto">
                        <div class="form my-auto">
                            <div class="form-group">
                                <div class="input-group">
                                    <select v-model="perPage" @change="changePageSize">
                                        <option v-for="perPage in perPageOptions">{{ perPage }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!--Filtros aplicados-->
            <div v-if="areFiltersApplied" class="d-flex column mobile-filters-applied my-10">
                <div class="d-flex align-center justify-between">
                    <div class="d-flex align-center">
                        <i class="fa fas fa-lightbulb mr-10" data-color="rojo" />
                        <p class="text" data-color="rojo" data-size="14" data-weight="600">Filtros aplicados</p>
                    </div>
                    <div class="custom-button" data-size="small" data-bg="rojo" data-mode="translucent" @click="resetFilters">
                        Borrar filtros
                    </div>
                </div>
            </div>

            <!--Filtros-->
            <div class="d-flex column my-20">
                <p class="text" data-size="13" data-weight="600" @click="seeFiltersMenu = !seeFiltersMenu">
                    {{ seeFiltersMenu ? 'Ocultar filtros' : 'Mostrar filtros' }}
                </p>
                <OrderFiltersComponent v-if="seeFiltersMenu" v-model:seeFiltersMenu="seeFiltersMenu" v-model:filtersApplied="filtersApplied" :filters="filters" @resetFilters="resetFilters" :basicData="basicData" :agentsWithSubordinates="agentsWithSubordinates"/>
            </div>

            <!--Spinner de carga-->
            <div class="loading-indicator d-flex justify-center align-center mt-50" data-gap="25" v-if="isLoading">
                <i class="fa-solid fa-spinner fa-spin text" data-size="30" />
                <p class="text" data-size="18">Cargando contratos...</p>
            </div>

            <div v-else-if="orders?.length > 0">
                <div class="d-flex column">
                    <order-card-component v-for="order in orders" :order="order" :isReadOnly="isReadOnly" :basicData="basicData" @selectOrderToSee="seeOrder" @deleteOrder="deleteOrder" @seeOrderInfo="seeOrderInfo" :orderInfoSelected="orderInfoSelected" :marketers="marketers"/>
                </div>


                <!--Paginación-->
                <div class="d-grid mt-20" data-column="2" data-layout="auto1" v-if="orders?.length > 0">

                    <!--Info página-->
                    <div class="d-flex justify-center my-auto" data-color="principal">
                        <div :class="['left pointer my-auto',{ 'opacity-5': currentPage === 1 }]"
                             @click="changePage(-1)"><i class="far fa-chevron-left" /></div>

                        <div class="cont mx-10 my-auto" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages }}</div>

                        <div :class="['right pointer my-auto',{ 'opacity-5': currentPage === totalPages }]"
                             @click="changePage(1)"><i class="far fa-chevron-right"></i></div>
                    </div>

                    <!--Selector contratos por página-->
                    <div class="my-auto ml-auto d-flex">

                        <p class="text my-auto mr-10" data-size="13">Por página: </p>

                        <div class="select-content my-auto">
                            <div class="form my-auto">
                                <div class="form-group">
                                    <div class="input-group">
                                        <select v-model="perPage" @change="changePageSize">
                                            <option v-for="perPage in perPageOptions">{{ perPage }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--si no hay-->
                <div v-else class="text opacity-5" data-align="center">¡No hay ningún contrato!</div>
            </div>
        </div>

        <!--Estilo de pc-->
        <div v-else class="desktop-item">

            <!--Header-->
            <div class="d-flex justify-between align-center">

                <!--Título-->
                <div class="d-flex">
                    <div class="text" data-size="30" data-weight="700">{{ $route.meta.title }}</div>

                    <!--Paginación-->
                    <div class="d-grid" data-column="2" v-if="orders?.length > 0">

                        <!--Info página-->
                        <div class="d-flex justify-center my-auto" data-color="principal">
                            <div :class="['left pointer',{ 'opacity-5': currentPage === 1 }]"
                                 @click="changePage(-1)"><i class="fa-solid fa-chevron-left" /></div>

                            <div class="cont mx-10" data-size="13" data-weight="600">
                                {{ currentPage }} DE {{ totalPages }}
                            </div>

                            <div :class="['right pointer',{ 'opacity-5': currentPage === totalPages }]"
                                 @click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></div>
                        </div>

                        <!--Selector contratos por página-->
                        <div class="my-auto ml-auto d-flex">

                            <p class="text my-auto mr-15">Por página: </p>

                            <div class="select-content my-auto">
                                <div class="form my-auto">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <select v-model="perPage" @change="changePageSize">
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
                    <!--Dropdown exportación y carga masiva-->
                    <div class="d-flex">
                        <div :class="['custom-select no-hover',{ seeing: seeOrderActions }]" @click.stop="toggleCustomSelects('seeOrderActions')">
                            <div class="custom-button" data-size="regular" data-bg="principal"><i class="far fa-plus" /></div>

                            <div v-if="seeOrderActions" class="select-content form w-260-px">
                                <!--Importar plantilla excel-->
                                <div class="d-flex mt-5" v-if="canManage('contracts.massive')">
                                    <p class="text" @click="$refs.inputExcel.click()">
                                        <i class="fa-solid fa-file-excel ml-4 mr-14"/> Carga masiva
                                    </p>
                                    <input type="file" ref="inputExcel" style="display: none" accept=".xls, .xlsx, .csv" @change="pickupDumpFile" />
                                </div>

                                <!--Descargar plantilla excel-->
                                <a v-if="canManage('contracts.massive')" class="text" href="/assets/templates/orders.xlsx" download="Plantilla contratos">
                                    <i class="fas fa-file-arrow-down ml-4 mr-10" /> Descargar plantilla
                                </a>

                                <!--Exportar excel-->
                                <p class="text mt-3" @click="exportOrders">
                                    <i class="fas fa-file-export ml-4 mr-10" /> Exportar a Excel
                                </p>

                                <!--Enviar correo masivo a usuarios seleccionado-->
                                <!-- <p class="text mt-3" @click="getEmailsToMassive">
                                    <i class="fas fa-paper-plane-top ml-4 mr-10" /> Enviar correo masivo
                                </p> -->
                            </div>
                        </div>
                    </div>

                    <!--Dropdown crear contrato-->
                    <div v-if="canManage('contracts.create')"
                         :class="['custom-select no-hover',{ seeing: seeCreateOrder }]"
                         @click.stop="toggleCustomSelects('seeCreateOrder')">
                        <div class="custom-button" data-size="regular" data-bg="principal">
                            Añade un contrato
                        </div>

                        <!--Opción entre crear nuevo y meter en cuenta existente-->
                        <div v-if="seeCreateOrder" class="select-content form">

                            <!--Opciones-->
                            <div class="text" v-if="!createOrderType">
                                <p @click.stop="createOrderType = 'account'"><i class="far fa-buildings mr-10" />Creación en cuenta</p>

                                <p v-if="canCreateDirectOrder"
                                   @click.stop="openCreateOrderComponent()"><i class="far fa-file-lines mr-10" />Creación directa</p>

                                <p @click.stop="$refs.inputInvoiceFile.click()"> <i class="fas fa-file-invoice mr-10" />Creación por factura</p>
                                <input type="file" ref="inputInvoiceFile" class="d-none" multiple accept=".pdf" @change="pickupInvoicesFiles" />
                            </div>

                            <!--Crear en cuenta existente-->
                            <div v-if="createOrderType === 'account'" @click.stop>
                                <div class="form-group">
                                    <div class="input-group">
                                        <input data-size="12" v-model="searchFilters.account" type="text" placeholder="Busca tu cuenta..." />
                                    </div>
                                </div>

                                <div v-for="account in accountsFiltered" :key="account._id" class="d-flex align-center pointer">
                                    <div class="text ellipsis" data-size="13" @click.stop="openCreateOrderComponent(account)">
                                        <i class="fas fa-buildings mr-10" />{{ account.name }}
                                    </div>
                                </div>

                                <div v-if="accounts?.length === 0" class="text opacity-5" data-size="10">
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
                    <div class="icon">
                        <i class="far fa-file-lines" />
                    </div>

                    <div class="info">
                        <p class="title">Contratos</p>
                        <p class="value">{{ summaryData.totalOrders }}</p>
                    </div>
                </div>

                <!-- Consumo total -->
                <div class="dashboard-card">
                    <div class="icon">
                        <i class="far fa-lightbulb" />
                    </div>

                    <div class="info">
                        <p class="title">Consumo total</p>
                        <p class="value">{{ totalConsumption }}</p>
                    </div>
                </div>

                <!-- Comisión -->
                <div class="dashboard-card" v-if="!canManage('users.admiWhiHier')">
                    <div class="icon">
                        <i class="far fa-money-bill" />
                    </div>

                    <div :class="['info',{ half: summaryData?.agentBelowCommission > 0 || canManage('contracts.manageCommissions')}]">
                        <div v-if="summaryData?.agentBelowCommission > 0">
                            <p class="title">Comisión agentes</p>
                            <p class="value">
                                {{ Math.round(summaryData.agentBelowCommission ?? 0).toLocaleString("es-ES") }}
                                <span :class="{'points': basicData?.userLogged?.commInPoints}">{{ !basicData?.userLogged?.commInPoints ? '€' : 'pts.' }}</span>
                            </p>
                        </div>
                        <div>
                            <p class="title">{{ canManage('contracts.manageCommissions') ? 'Comisión agentes' : 'Mi comisión' }}</p>
                            <p class="value">
                                {{ Math.round(summaryData.agentCommission ?? 0).toLocaleString("es-ES") }}
                                <span :class="{'points': basicData?.userLogged?.commInPoints}">{{ !basicData?.userLogged?.commInPoints ? '€' : 'pts.' }}</span>
                            </p>
                        </div>

                        <!-- SOLO CON PERMISO -->
                        <div v-if="canManage('contracts.manageCommissions')">
                            <p class="title">Comisión {{ basicData?.enterprise?.name }}</p>
                            <p class="value">
                                {{ Math.round(summaryData.subdomainCommission ?? 0).toLocaleString("es-ES") }}
                                <span :class="{'points': basicData?.userLogged?.commInPoints}">{{ !basicData?.userLogged?.commInPoints ? '€' : 'pts.' }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Rentabilidad -->
                <div class="dashboard-card" v-if="!canManage('users.admiWhiHier') && (canManage('contracts.manageCommissions') || summaryData?.agentBelowCommission > 0)">
                    <div class="icon">
                        <i class="far fa-money-bill" />
                    </div>

                    <div class="info half">
                        <p class="title">Rentabilidad</p>
                        <p class="value">{{ profitability }} €</p>

                        <p class="title">Rentabilidad %</p>
                        <p class="value">{{ profitabilityPercent }} %</p>
                    </div>
                </div>
            </div>

            <!--Header tabla-->
            <div :class="['mt-30 select-line', 'search-options-layout']">

                <div v-if="areFiltersApplied" class="d-flex filters-on relPos">
                    <i class="fa fas fa-lightbulb my-auto mr-5" data-color="rojo"></i>
                    <p class="my-auto mr-15" data-color="rojo">Filtros aplicados</p>
                    <div class="custom-button " data-size="small" data-bg="rojo" data-mode="translucent" @click="resetFilters">
                        Borrar filtros
                    </div>
                </div>

                <div class="before-search">
                    <div @click="seeFiltersMenu = !seeFiltersMenu" class="custom-button " data-size="small" data-bg="azul" data-mode="translucent">
                        {{ seeFiltersMenu ? 'Ocultar' : 'Mostrar' }} filtros
                    </div>
                </div>

                <!--Barra de búsqueda-->
                <div class="d-flex search-div with-search-field">

                    <!--Buscador por categoría-->
                    <div class="search-field-select mr-10">
                        <select v-model="searchFilters.searchOption" @change="searchFilters.search ? fetchOrders() : null">
                            <template v-for="option in searchOptions" :key="option.value">
                                <option
                                    v-if="!option.condition || subdomainSettings?.[option.condition]" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </template>
                        </select>
                    </div>

                    <!--Buscador de texto-->
                    <div class="search-bar w-100">
                        <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10" />

                        <input type="text" placeholder="Buscar un contrato..." v-model="searchFilters.search">
                    </div>
                    <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" @click="resetSearch" />
                </div>
            </div>

            <div :class="[{'fidelity-contracts-scroll': basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2'}]">
                <!--Header Tabla Fidelity-->
                <template v-if="basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2'">
                    <div :class="['fidelity-contracts-header mt-30',{'with-id' : subdomainSettings?.contractsIds}]">
                        <template v-for="col in fidelityHeaderColumns" :key="col.key">
                            <div
                                v-if="!col.condition || subdomainSettings?.[col.condition]"
                                :class="['d-flex', col.sortable && 'pointer']"
                                data-color="principal"
                                @click="col.sortable && sortOrdersBy(col.key)"
                            >
                                <p class="text mr-5 ellipsis noWidth select-none" data-weight="600">{{ col.label }}</p>
                                <i v-if="col.sortable" :class="['fas my-auto', getSortIcon(col.key)]" />
                            </div>
                        </template>
                    </div>
                </template>

                <!--Header Tabla-->
                <div v-else :class="['contact header-card six-no-check mt-30',{'with-id' : subdomainSettings?.contractsIds}]">
                    <template v-for="col in displayHeaderColumns" :key="col.key">
                        <div
                            v-if="!col.condition || subdomainSettings?.[col.condition]"
                            :class="['d-flex', col.sortable && 'pointer', col.key === 'agentCommission' && 'onex-comm-col']"
                            data-color="principal"
                            @click="col.sortable && sortOrdersBy(col.key)"
                        >
                            <p class="text mr-5 ellipsis noWidth select-none" data-weight="600">{{ col.label }}</p>
                            <i v-if="col.sortable" :class="['fas my-auto', getSortIcon(col.key)]" />
                        </div>
                    </template>
                </div>

                <!--Contenido-->
                <div class="separator my-10"></div>

                <!--Loader-->
                <template v-if="isLoading">
                    <div class="d-flex column" v-for="i of 10">
                        <div class="contact six-no-check pointer">
                            <div class="loading mx-10 h-20-px" v-for="i of 9"></div>
                        </div>
                        <div class="separator my-10"></div>
                    </div>
                </template>

                <!--Contratos-->
                <template v-else-if="orders?.length > 0">
                    <div>
                        <order-card-component v-for="order in orders" :order="order" :isReadOnly="isReadOnly" :basicData="basicData" @selectOrderToSee="seeOrder" @deleteOrder="deleteOrder" :marketers="marketers"/>
                    </div>

                    <div class="d-grid" data-column="3">

                        <div />

                        <!--Info página-->
                        <div class="d-flex justify-center my-auto" data-color="principal">
                            <div class="left pointer" :class="{ 'opacity-5': currentPage === 1 }"
                                 @click="changePage(-1)"><i class="fa-solid fa-chevron-left" /></div>

                            <div class="cont mx-10" data-size="13" data-weight="600">
                                {{ currentPage }} DE {{ totalPages }}
                            </div>

                            <div class="right pointer" :class="{ 'opacity-5': currentPage === totalPages }"
                                 @click="changePage(1)"><i class="fa-solid fa-chevron-right" /></div>
                        </div>

                        <!--Selector contratos por página-->
                        <div class="my-auto ml-auto d-flex">

                            <p class="text my-auto mr-15">Por página: </p>

                            <div class="select-content my-auto">
                                <div class="form my-auto">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <select v-model="perPage" @change="changePageSize">
                                                <option v-for="perPage in perPageOptions">{{ perPage }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="opacity-5" data-align="center">¡No hay ningún contrato!</div>
            </div>

            <!--Filtros-->
            <OrderFiltersComponent v-if="seeFiltersMenu" v-model:seeFiltersMenu="seeFiltersMenu" v-model:filtersApplied="filtersApplied" :filters="filters" @resetFilters="resetFilters" :basicData="basicData" :agentsWithSubordinates="agentsWithSubordinates"/>
        </div>
        <!--Flotante para crear/editar contrato-->
        <Teleport to=".boxBody">
            <div class="form">
                <order-details-item-component v-if="seeOrderComponent" :order="orderToModify"
                    :originalOrders="originalOrders" :account="accountToAddOrder" :selectValues="selectValues" :basicData="basicData" :isCreatingOrder="isCreatingOrder"
                    :isEditingFromOther="isEditingOrder" @createOrder="createOrder" @closeWindow="closeWindow" @renewalOrder="renewalOrder" @activeEditing="activeEditing"
                />
            </div>
        </Teleport>
        <div class="loader-box" v-if="isImportingOrders">
            <div class="loader"></div>
        </div>
    </div>
</template>

<script>
import {useDebounceFn, useWindowSize} from "@vueuse/core";
import OrderCardComponent from "@/components/items/OrderCardComponent.vue";
import OrderFiltersComponent from "@/components/items/OrderFiltersComponent.vue";

export default {
    name: "OrderListComponent",
    components: {OrderFiltersComponent, OrderCardComponent},
    props: ['basicData'],
    data() {
        return {
            orders: [],
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
            orderToModify: null,
            originalOrders: [],
            accounts: [],
            marketers: [],
            summaryData: {
                totalOrders: 0,
                totalConsumption: 0,
                agentCommission: 0,
                agentBelowCommission: 0,
                subdomainCommission: 0,
            },
            accountToAddOrder: null,
            filters: null,
            filtersApplied: {
                agents: [],
                statuses: [],
                creationDates: {start: null, end: null},
                activationDates: {start: null, end: null},
                lowDates: {start: null, end: null},
                renewalDates: {start: null, end: null},
                productTypes: [],
                fees: [],
                marketers: [],
                products: [],
                subdomains: null,
            },
            searchFilters: {
                searchOption: 'all',
                search: '',
                account: '',
            },
            searchOptions: [
                { value: 'all', label: 'Todo' },
                { value: 'id', label: 'Id', condition: 'contractsIds' },
                { value: 'name', label: 'Nombre' },
                { value: 'cif', label: 'NIF/CIF' },
                { value: 'cups', label: 'CUPS' },
                { value: 'verificationPhone', label: 'Tel. verificación', condition: 'verificationPhone' },
                { value: 'contractEmail', label: 'Correo. contratación', condition: 'contractEmail' }
            ],
            sortOrders: {
                column: null,
                direction: 'desc'
            },
            selectValues: {},
            currentPage: 1,
            perPage: 50,
            totalPages: 1,
            perPageOptions: [50, 100, 200],
            headerColumns: [
                { key: 'id', label: 'Id', sortable: true, condition: 'contractsIds' },
                { key: 'name', label: 'Nombre', sortable: false },
                { key: 'agent', label: 'Agente', sortable: false },
                { key: 'cif', label: 'NIF/CIF', sortable: false },
                { key: 'fee', label: 'Tarifa', sortable: false },
                { key: 'product', label: 'Producto', sortable: false },
                { key: 'cups', label: 'CUPS', sortable: false },
                { key: 'status', label: 'Estado', sortable: false },
                { key: 'activationDate', label: 'Fec. activación', sortable: true },
                { key: 'lastUpdate', label: 'Ult. actualización', sortable: true }
            ],
            fidelityHeaderColumns: [
                { key: 'id', label: 'Id', sortable: true, condition: 'contractsIds' },
                { key: 'name', label: 'Nombre', sortable: false },
                { key: 'superUsuario', label: 'Superusuario', sortable: false },
                { key: 'usuario', label: 'Usuario', sortable: false },
                { key: 'comercial', label: 'Comercial', sortable: false },
                { key: 'cif', label: 'NIF/CIF', sortable: false },
                { key: 'fee', label: 'Tarifa', sortable: false },
                { key: 'product', label: 'Producto', sortable: false },
                { key: 'cups', label: 'CUPS', sortable: false },
                { key: 'status', label: 'Estado', sortable: false },
                { key: 'createdAt', label: 'Fec. creación', sortable: true },
                { key: 'activationDate', label: 'Fec. activación', sortable: true },
            ],
            orderInfoSelected: null,
            seeFiltersMenu: false,
            seeOrderActions: false,
            seeCreateOrder: false,
            createOrderType: null,
            seeOrderComponent: false,
            isLoading: false,
            isImportingOrders: false,
            isCreatingOrder: false,
            isEditingOrder: false,
        }
    },
    created() {
        this.debouncedFetchOrders = useDebounceFn(() => {this.fetchOrders()}, 300)

        //Cargo los filtros y estos llaman a contratos, o llamo a contratos directamente
        const saved = sessionStorage.getItem('orderFilters')
        if (saved) this.filtersApplied = JSON.parse(saved)
        else this.fetchOrders()

        this.fetchMarketers()

        this.fetchOrderFilters()
    },
    mounted() {
        //Saco los valores para los selects
        this.fetchSelectValues()

        this.agentsWithSubordinates // Calculo la jerarquía y queda cacheada por Vue
    },
    watch: {
        '$route.query._id': {
            immediate: true,
            handler(newId) {
                //Cargo el contrato si viene un id en la ruta
                if (newId) {
                    axios.get('/api/orders/' + newId)
                        .then((res) => {
                            this.seeOrder(res.data.order, false)
                        })
                        .catch((err) => {
                            console.log(err)
                        })
                } else {
                    this.orderToModify = JSON.parse(JSON.stringify(this.order));
                    this.seeOrderComponent = false;
                }
            }
        },
        basicData: {
            immediate: true,
            deep: true,
            handler(newVal) {
                const subdomainId = newVal?.userSubdomain?._id;

                if (!subdomainId) return; // aún no hay datos, no tocamos sortOrders.column

                this.sortOrders.column = subdomainId === '6909faa9232c09035a03f3b2'
                    ? 'id'
                    : 'lastUpdate';
            }
        },
        'basicData.userLogged': {
            immediate: true,
            deep: true,
            async handler(user) {
                if(!user) return

                await this.fetchAccounts();
            }
        },
        'searchFilters.search'() {
            this.debouncedFetchOrders()
        },
        filtersApplied: {
            deep: true,
            handler(val) {
                sessionStorage.setItem('orderFilters', JSON.stringify(val))
                this.debouncedFetchOrders()
            }
        },
        'filtersApplied.marketers'() {
            const validProductIds = this.filtersApplied.marketers.length === 0
                ? []
                : this.filters?.products
                    ?.filter(p => this.filtersApplied.marketers.includes(p.marketerId))
                    ?.map(p => p.id)

            this.filtersApplied.products = this.filtersApplied.products.filter(id => validProductIds.includes(id))
        },
    },
    methods: {
        async fetchOrders() {
            //Muestro vista de carga
            this.isLoading = true;

            const result = await axios.post(`/api/orders/index`, {
                page: this.currentPage,
                perPage: this.perPage,
                search: this.searchFilters.search,
                searchOption: this.searchFilters.searchOption,
                sortBy: this.sortOrders.column,
                sortDirection: this.sortOrders.direction,
                filters: this.filtersApplied
            });

            //Asigno los valores obtenidos para contratos, paginación y tarjetas resumen
            this.orders = result.data.orders;
            this.totalPages = result.data.totalPages;
            this.summaryData.totalOrders = result.data.summary.total;
            this.summaryData.totalConsumption = result.data.summary.totalConsumption;
            this.summaryData.agentCommission = result.data.summary.agentCommission;
            this.summaryData.agentBelowCommission = result.data.summary.agentBelowCommission;
            this.summaryData.subdomainCommission = result.data.summary.subdomainCommission;


            this.isLoading = false;
        },
        async fetchOrderFilters(){
            const result = await axios.post(`/api/orders/indexFilters`);

            this.filters = result.data;
        },
        async fetchAccounts() {
            const userId = this.canManage('users.admiWhiHier') ? this.basicData.userSubdomain._id : this.basicData.userLogged._id;
            const userList = this.canManage('users.admiWhiHier') ? this.basicData.subdomainUserList : this.basicData.userList;

            axios.post(`/api/accounts/getRelatedAccounts/${userId}`, { userList: JSON.stringify(userList) })
                .then((res) => {

                    //Ordeno las cuentas
                    this.accounts = res.data?.relatedAccounts?.sort((a, b) => a.name.localeCompare(b.name, 'es', {sensitivity: 'base'}));
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async fetchMarketers(){
            axios.get('/api/marketers')
                .then((res) => {
                    this.marketers = res.data.marketers;
                })
                .catch(err => console.error(err))
        },
        pickupDumpFile(event) {
            let file = event.target.files[0];
            if (file) {
                this.dumpOrders(file)
            }
        },
        async dumpOrders(file) {
            this.toggleCustomSelects();
            this.isImportingOrders = true;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('userSubdomain', JSON.stringify(this.basicData.userSubdomain));
            formData.append('userList', JSON.stringify(this.basicData.userList));

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

                    await Swal.fire({
                        icon: "warning",
                        title: "Hay incidencias",
                        html: `
                    <div style="text-align:left">
                        <p>La importación tiene incidencias.</p>
                        <p>Revisa el archivo descargado.</p>
                    </div>
                    `
                    });

                    await this.fetchOrders();
                    return;
                }

                const text = await res.data.text();
                let data = {};
                try { data = JSON.parse(text); } catch { data = {}; }

                const failed = Array.isArray(data?.failedRows) ? data.failedRows : [];

                await this.fetchOrders();

                if (failed.length === 0) {
                    await Swal.fire({
                        icon: "success",
                        title: "Importación completada",
                        text: "La importación se ha realizado correctamente.",
                        timer: 1600,
                        timerProgressBar: true
                    });
                    return;
                }

                await Swal.fire({
                    icon: "warning",
                    title: "Hay incidencias",
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

                await Swal.fire({
                    icon: 'error',
                    title: 'Error al importar',
                    text: msg,
                });

            } finally {
                this.isImportingOrders = false;
                if (this.$refs?.inputExcel) this.$refs.inputExcel.value = null;
            }
        },
        async exportOrders() {
            const response = await axios.post(`/api/orders/exportTemplate`, {
                search: this.searchFilters.search,
                searchOption: this.searchFilters.searchOption,
                sortBy: this.sortOrders.column,
                sortDirection: this.sortOrders.direction,
                filters: this.filtersApplied,
                userSubdomain: this.basicData?.userSubdomain,
            }, {
                responseType: 'blob',
            });

            const url  = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            const disposition = response.headers['content-disposition'];
            const filename = disposition
                ? disposition.split('filename=')[1].replace(/"/g, '')
                : 'Contratos.xlsx';
            link.href  = url;
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        },
        async getEmailsToMassive(){
            this.isLoading = true;

            //Saco los correos de las cuentas sin duplicar
            const emails = await axios.post(`/api/orders/getAccountEmails`, {
                search: this.searchFilters.search,
                searchOption: this.searchFilters.searchOption,
                filters: this.filtersApplied
            });

            this.isLoading = false;

            //Creo en el storage y lo mando
            localStorage.setItem(
                'emailsTemporaly',
                JSON.stringify(emails)
            );

            //Redirijo a masivo de correos
            this.$router.push('/tools?section=massiveEmail&withFilters=true')
        },
        pickupInvoicesFiles(event) {
            const files = Array.from(event.target.files || []);
            if (!files.length) return;
            this.handleInvoiceFiles(files);
        },
        async handleInvoiceFiles(files) {
            try {
                this.isLoading = true;

                let successCount = 0;
                let errorCount = 0;
                let counter = 0;
                let resultsList = '';
                let lastAccountId = null;

                await Swal.fire({
                    title: 'Procesando facturas...',
                    html: `<p>0 de ${files.length} completadas</p>`,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                //Proceso las facturas
                for(let file of  files) {

                    Swal.update({
                        html: `
                    <p style="margin-bottom:10px"><b>Procesando ${counter + 1} de ${files.length}: ${file.name}</b></p>
                    <div style="max-height:250px; overflow-y:auto">${resultsList}</div>
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
                        });

                        const accountName = res.data?.account?.name || '-';
                        const orderName = res.data?.order?.name || '-';
                        const accountExistia = res.data?.message?.includes('existente');
                        const accountId = res.data?.account?._id || null;

                        if (accountId) lastAccountId = accountId;

                        successCount++;
                        resultsList += `
                    <div style="text-align:left; border:1px solid #eee; border-radius:8px; padding:8px; margin-bottom:6px">
                        <p style="margin:0 0 4px 0"><b>Factura ${counter + 1}:</b> ${file.name}</p>
                        <p style="margin:2px 0">Cuenta: <b>${accountName}</b> ${accountExistia ? '<span style="color:#f0a500">(ya existía, reutilizada)</span>' : '<span style="color:green">(creada)</span>'}</p>
                        <p style="margin:2px 0">Contrato creado: <b>${orderName}</b></p>
                    </div>
                `;

                    } catch (err) {
                        errorCount++;
                        resultsList += `
                    <div style="text-align:left; border:1px solid #ffcccc; border-radius:8px; padding:8px; margin-bottom:6px; background:#fff5f5">
                        <p style="margin:0"><b>Factura ${i + 1}:</b> ${file.name}</p>
                        <p style="margin:2px 0; color:red">Error: ${err?.response?.data?.error || err?.response?.data?.limit || 'No se pudo procesar'}</p>
                    </div>
                `;
                    }
                }

                //Recargo los contratos para mostrar los recién creados
                await this.fetchOrders();

                await Swal.fire({
                    icon: errorCount === 0 ? 'success' : 'warning',
                    title: errorCount === 0 ? 'Proceso completado' : 'Proceso completado con errores',
                    html: `
                <p style="margin-bottom:12px">${successCount} creados correctamente${errorCount > 0 ? `, ${errorCount} con error` : ''}</p>
                <div style="max-height:300px; overflow-y:auto">
                    ${resultsList}
                </div>
            `
                });

                if (lastAccountId) {
                    this.$router.push('/accounts/' + lastAccountId);
                }

            } catch (err) {
                await Swal.fire({
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
        sortOrdersBy(column) {
            //Si el ordenamiento es por el mismo campo, se invierte
            //Si no, se cambia a la columna que se quiere ordenar
            if (this.sortOrders.column === column) {
                this.sortOrders.direction = this.sortOrders.direction === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortOrders.column = column;
                this.sortOrders.direction = 'asc';
            }

            this.fetchOrders();
        },
        openCreateOrderComponent(account = null) {
            this.orderToModify = JSON.parse(JSON.stringify(this.order));
            this.originalOrders = [];
            this.accountToAddOrder = account;
            this.isEditingOrder = false;
            this.seeOrderComponent = true;

            this.toggleCustomSelects();
        },
        async seeOrder(order, updateRoute = true) {
            //Pongo la ruta con el parámetro del nuevo
            if(updateRoute){
                this.$router.push({
                    path: '/orders',
                    query: { _id: order._id.$oid }
                })
            }

            this.originalOrders = [JSON.parse(JSON.stringify(order))]
            this.orderToModify = order

            this.accountToAddOrder = await this.fetchAccount(order.account);

            //Saco el último estado del contrato
            let recentStatus = this.orderToModify.statuses.reduce((latest, current) => {
                return new Date(current.date) > new Date(latest.date) ? current : latest;
            }, { date: '1970-01-01T00:00:00Z' });

            this.orderToModify.newStatus = {
                code: recentStatus.code,
                date: recentStatus.date
            }

            this.seeOrderComponent = true;
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
                    const id = order['_id']['$oid'];

                    axios.delete(`/api/orders/${id}`)
                        .then((res) => {

                            //Recargo los pedidos de nuevo
                            this.fetchOrders()

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

                    if (this.orderToModify.CUPS) {
                        const cupsClean = this.orderToModify.CUPS
                            .toUpperCase()
                            .replace(/[\s.-]/g, '');

                        if (cupsClean === 'ES0000') {
                            this.orderToModify.CUPS = cupsClean;
                            delete this.orderToModify.errors.CUPS;
                        } else if (cupsClean.length !== 20 && cupsClean.length !== 22) {
                            this.orderToModify.errors.CUPS = 'CUPS no válido';
                            hasErrors = true;
                        }
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

                    if (!!this.accountToAddOrder){
                        const account = await this.fetchAccount(this.accountToAddOrder._id)
                        data.append('account', JSON.stringify(account))
                    }





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

                                this.closeWindow();
                                this.accountToAddOrder = null;
                                this.isCreatingOrder = false;
                                await this.fetchOrders();

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

                        await axios.post(`/api/orders/${this.orderToModify['_id']}`, data)
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

                                this.closeWindow();
                                this.accountToAddOrder = null;
                                this.isCreatingOrder = false;
                                await this.fetchOrders();

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
        resetFilters(){
            this.filtersApplied = {
                agents: [],
                statuses: [],
                creationDates: {start: null, end: null},
                activationDates: {start: null, end: null},
                lowDates: {start: null, end: null},
                renewalDates: {start: null, end: null},
                productTypes: [],
                fees: [],
                marketers: [],
                products: [],
                subdomains: null
            };

            this.fetchOrders();
        },
        resetSearch() {
            this.searchFilters.search = '';

            this.fetchOrders();
        },
        seeOrderInfo(order) {
            if (this.orderInfoSelected === order['_id']) {
                this.orderInfoSelected = null
            } else {
                this.orderInfoSelected = order['_id'];
            }
        },
        closeWindow() {
            //quito toda la info de order
            this.orderToModify = JSON.parse(JSON.stringify(this.order));
            this.accountToAddOrder = null;
            this.seeOrderComponent = false;

            if (this.$route.query._id) {
                const query = { ...this.$route.query }
                delete query._id

                this.$router.replace({ query })
            }
        },
        renewalOrder(newOrder) {
            this.orderToModify = newOrder
            this.isEditingOrder = true
        },
        activeEditing() {
            this.isEditing = true
        },
        async fetchAccount(id){
            try{
                const response = await axios.get(`api/accounts/${id}`);
                return response.data.account;
            }catch(err) {
                return {_id: id};
            }
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
        getSortIcon(column) {
            const { column: current, direction } = this.sortOrders;

            if (current !== column) return 'fa-sort';

            return direction === 'asc'
                ? 'fa-sort-up'
                : 'fa-sort-down';
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
                }
            }

            return colorMap;
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
        resetOrder() {
            this.orderToModify = {
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
            };
        },
        changePage(value) {
            const newPage = this.currentPage + value;

            if (newPage >= 1 && newPage <= this.totalPages) {
                this.currentPage = newPage;
                this.fetchOrders();
            }
        },
        changePageSize() {
            this.currentPage = 1;
            this.fetchOrders();
        },
        toggleCustomSelects(customSelect = null) {
            const actualValue = customSelect ? this[customSelect] : false;

            this.seeOrderActions = false;
            this.seeCreateOrder = false;
            this.createOrderType = null;

            if (customSelect) {
                this[customSelect] = !actualValue;
            }
        }
    },
    computed: {
        areFiltersApplied() {
            return Object.values(this.filtersApplied).some(value => {
                if (value === null || value === undefined) return false
                if (typeof value === 'string') return value.trim() !== ''
                if (Array.isArray(value)) return value.length > 0

                if (typeof value === 'object') {
                    return Object.values(value).some(childValue => {
                        if (childValue === null || childValue === undefined) return false
                        if (typeof childValue === 'string') return childValue.trim() !== ''
                        if (Array.isArray(childValue)) return childValue.length > 0
                        return true
                    })
                }

                return true
            })
        },
        accountsFiltered() {
            if (!this.accounts) return [];

            const normalize = (text) => text.replaceAll(' ', '').toLowerCase();

            const accountSearch = normalize(this.searchFilters.account);

            return this.accounts.filter(acc => normalize(acc.name).includes(accountSearch));
        },
        agentsWithSubordinates() {
            const users = this.basicData?.userListComplete ?? []

            // 1. Mapa de hijos directos por padre
            const childrenMap = {}
            users.forEach(u => {
                const parent = u.responsibles?.[0]
                if (parent) {
                    if (!childrenMap[parent]) childrenMap[parent] = []
                    childrenMap[parent].push(u._id)
                }
            })

            // 2. Para cada usuario, expandir su jerarquía
            const map = {}
            users.forEach(u => {
                const result = [u._id]
                const stack = [...(childrenMap[u._id] || [])]

                while (stack.length) {
                    const current = stack.pop()
                    result.push(current)
                    stack.push(...(childrenMap[current] || []))
                }

                map[u._id] = result
            })

            return map
        },
        totalConsumption() {
            let consumption = this.summaryData.totalConsumption ?? 0;

            const units = ["kWh", "MWh", "GWh"];
            const threshold = 10000;
            let unitIndex = 0;

            while (consumption >= threshold && unitIndex < units.length - 1) {
                consumption /= 1000;
                unitIndex++;
            }

            const formatted = new Intl.NumberFormat("es-ES", {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(consumption);

            return formatted + " " + units[unitIndex];
        },
        profitability() {
            const { subdomainCommission = 0, agentCommission = 0, agentBelowCommission = 0 } = this.summaryData;

            if(agentBelowCommission > 0) {
                return Math.round(agentCommission - agentBelowCommission || 0);
            }

            return Math.round(subdomainCommission - agentCommission || 0);
        },
        profitabilityPercent() {
            const { subdomainCommission = 0, agentCommission = 0, agentBelowCommission = 0 } = this.summaryData;
            const profitability = Number(this.profitability) || 0;

            const base = agentBelowCommission > 0
                ? agentCommission      // caso sin subdominio: base es su propia comisión
                : subdomainCommission; // caso con subdominio: base es la del subdominio

            if (base === 0) return 0;

            return Math.round((profitability / base) * 100);
        },
        subdomainSettings() {
            return this.basicData?.userSubdomain?.settings || null;
        },
        isOnexSubdomain() {
            const ONEX_SUBDOMAIN_IDS = ['692da6aeaedb25b428042132', '6a47777b04ce48688d831e57'];
            const id = this.basicData?.userSubdomain?._id;
            return ONEX_SUBDOMAIN_IDS.includes(id?.$oid ?? id);
        },
        // Cabeceras para onex:
        //  - "Ult. actualización" -> "Fec. traspaso"
        //  - se añade una columna extra "Comisión agente"
        displayHeaderColumns() {
            if (!this.isOnexSubdomain) return this.headerColumns;

            const cols = this.headerColumns.map(col =>
                col.key === 'lastUpdate'
                    ? { ...col, label: 'Fec. traspaso' }
                    : col
            );

            cols.push({ key: 'agentCommission', label: 'Comisión', sortable: false });

            return cols;
        },
        isReadOnly() {
            if (!this.basicData?.userLogged) return true;

            return this.basicData.userLogged.permissions?.includes('READONLY');
        },
        canCreateDirectOrder() {
            return this.basicData?.userSubdomain?._id !== "68d260e6bc9e8c38f8003df2" && this.basicData?.userSubdomain?._id !== "6909faa9232c09035a03f3b2"
        }
    },
    setup() {
        const { width } = useWindowSize()
        return { width }
    },
}

//TODO: Extraer lógica de creación directa de contratos
</script>

<style scoped>
/* Columna de comisión (solo onex): texto más pequeño para que quepa */
.onex-comm-col .text {
    font-size: 11px;
}
</style>
