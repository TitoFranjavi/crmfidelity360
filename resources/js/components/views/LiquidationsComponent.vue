<template>
    <div v-on:click="hideCustomSelects">

        <!--Estilo movil-->
        <div class="mobile-item">
            <div class="content-white">

                <!--Filtros-->
                <div class="d-flex column my-20">

                    <p class="text" data-size="13" data-weight="600" v-on:click="isSeeingFilters = !isSeeingFilters">{{ isSeeingFilters ? 'Ocultar filtros' : 'Mostrar filtros' }}</p>

                    <div v-if="isSeeingFilters">

                        <div class="arrow-border arrow-top my-10" data-position="left"></div>


                        <!--Agentes-->
                        <div class="d-flex justify-between my-10">
                            <div class="text" data-size="13" data-weight="600">Agentes</div>

                            <div class="custom-select" v-if="filters.checkbox.agentAvailable.data && filters.checkbox.agentAvailable.data.length > 0">

                                <div class="ml-10" data-size="13" data-color="azul">{{ getAgentFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content">
                                    <div class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('agents')" v-bind:class="{ 'selected': areAllAgentsActives }"></div>

                                        <div class="text">Todos</div>

                                    </div>
                                    <div v-for="agent in filters.checkbox.agentAvailable.data" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(agent, 'agent')" v-bind:class="{ 'selected': agent.active }"></div>

                                        <div class="text" data-size="13">{{ agent.title }}</div>

                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 agentes</div>
                        </div>


                        <!--Ordenar-->
                        <div class="d-flex justify-between my-10">
                            <div class="text" data-size="13" data-weight="600">Ordenar</div>

                            <div class="custom-select">

                                <div class="ml-10" data-size="13" data-color="azul">{{ sortTypeSelected.title }} <i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content">
                                    <div v-for="orderType in filters.radio.sortBy.data" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="selectOrderType('sort', orderType)" v-bind:class="{ 'selected': orderType.value === filters.radio.sortBy.checked }"></div>

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

                    <!--titulo-->
                    <div class="text my-10" data-size="22" data-weight="700">{{ $route.meta.title }}</div>


                    <!--botones liquidar-->
                    <div class="d-flex justify-content-between">

                        <!--seleccionar agente-->
                        <div class="my-auto custom-select">
                            <div class="custom-button mx-5" data-size="small" data-bg="azul"><i class="fas fa-user"></i></div>

                            <!--Usuarios-->
                            <div class="select-content">
                                <!--Usuario  a liquidar-->
                                <div class="form my-auto">
                                    <div class="form-group">
                                        <p class="my-auto"><label>Agente</label></p>
                                        <div class="input-group">
                                            <select v-model="userLiquidate.user"    :disabled="basicData.userSubdomain && basicData.userSubdomain._id === '6909faa9232c09035a03f3b2'">
                                                <option :value="basicData.userLogged"> {{ basicData.userLogged.firstName }} {{ basicData.userLogged.lastName }}</option>
                                                <option v-for="user in filteredUserToLiquidate" :value="user" > {{ user.firstName }} {{ user.lastName }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--comercializadoras-->
                        <div class="my-auto custom-select">
                            <div class="custom-button mx-5" data-size="small" data-bg="azul"><i class="fas fa-lightbulb"></i></div>

                            <!--comercializadoras-->
                            <!--estado liquidacio-->

                            <div class="select-content left form">

                                <div class="form-group ">
                                    <div class="input-group">
                                        <input data-size="12" v-model="searchFilters.marketer" type="text" placeholder="Busca tu comercializadora...">
                                    </div>
                                </div>

                                <div class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('marketers')" v-bind:class="{ 'selected': areAllMarketersActives }"></div>

                                    <div class="text">Todos</div>
                                </div>

                                <div v-for="marketer in filtersFiltered.marketers" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(marketer, 'marketer')" v-bind:class="{ 'selected': marketer.active }"></div>

                                    <div class="text">{{ marketer.name === '' ? 'Sin comercializadora' : marketer.name }}</div>

                                </div>
                            </div>

                        </div>

                        <!--seleccionar estado liquidacion-->
                        <div class="my-auto custom-select">
                            <div class="custom-button mx-5" data-size="small" data-bg="azul"><i class="fas fa-box-open-full"></i></div>

                            <!--estado liquidacio-->
                            <div class="select-content">
                                <!--estado liquidacio-->

                                <div class="form my-auto">
                                    <div class="form-group">
                                        <p class="my-auto"><label>Estado liquidación</label></p>
                                        <div class="input-group">
                                            <select v-model="userLiquidate.liquidationStatus">
                                                <option v-for="status in liquidationStatuses" :value="status.code" > {{ status.title }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--seleccionar fechas-->
                        <div class="my-auto custom-select">
                            <div class="custom-button mx-5" data-size="small" data-bg="azul">fechas </div>

                            <!--Fechas-->
                            <div class="select-content form">
                                <!--fecha inicio-->
                                <div v-bind:class="{ wrong: userLiquidate.errors && userLiquidate.errors.start}"  class="form-group my-5">
                                    <p class="my-auto"><label>Fecha inicio</label> <span data-color="rojo">*</span></p>
                                    <div class="input-group">
                                        <input data-size="12" v-on:focus="delete userLiquidate.errors.start" @input="setDate('start')" v-model="userLiquidate.start" type="date">
                                    </div>
                                </div>

                                <!--fecha final-->
                                <div v-bind:class="{ wrong: userLiquidate.errors && userLiquidate.errors.end}" class="form-group my-5">
                                    <p class="my-auto"><label>Fecha final</label> <span data-color="rojo">*</span></p>
                                    <div class="input-group">
                                        <input data-size="12" v-on:focus="delete userLiquidate.errors.end" @input="setDate('end')" v-model="userLiquidate.end" type="date">
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!--Botón liquidar-->
                        <div class="my-auto custom-select">
                            <div class="my-10 custom-button ml-auto" v-if="!isReadOnly" data-size="small" data-bg="principal" v-on:click="liquidateUser('pdf')"><i class="fas fa-file-pdf"></i></div>
                            <div class="my-10 custom-button ml-auto" v-if="!isReadOnly" data-size="small" data-bg="principal" v-on:click="liquidateUser('excel')"><i class="fas fa-file-excel"></i></div>
                        </div>
                    </div>
                </div>


                <!--Listado de liquidaciones-->
                <div class="d-flex column">

                    <div v-for="(liquidation, liquidationKey) in filteredLiquidations" class="my-5">

                        <!--Card-->
                        <div class="d-flex align-center pointer" v-on:click="seeLiquidationInfo(liquidation)">

                            <div class="text ellipsis" data-weight="600" data-size="13"><i class="fa-solid fa-calendar mr-10"></i> {{ getPrettyDate(liquidation['dates']['start']) }} - {{ getPrettyDate(liquidation['dates']['end']) }}</div>

                            <div class="deploy-btn ml-10" data-round="15" v-bind:class="{'selected': liquidationSelectedToSee._id === liquidation._id}">
                                <i class="fa-solid" v-bind:class="{'fa-chevron-down': liquidationSelectedToSee._id !== liquidation._id, 'fa-chevron-up': liquidationSelectedToSee._id === liquidation._id}"></i>
                            </div>
                        </div>

                        <!--Info card-->
                        <div class="d-flex column" v-if="liquidationSelectedToSee._id === liquidation._id">

                            <!--Info básica-->
                            <div class="my-10">

                                <!--Número de pedidos-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Nº de pedidos</div>

                                    <div class="text" data-size="13">{{ liquidation.totalOrders }}</div>
                                </div>

                                <!--Total comisionado-->
                                <div class="d-flex justify-between" v-if="!!liquidation.totalCommission">

                                    <div class="text" data-size="13" data-weight="600">Total Comisionado</div>

                                    <div class="text" data-size="13">{{ liquidation.totalCommission.toFixed(2) }}</div>
                                </div>


                                <!--Agente-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Agente</div>

                                    <div class="text" data-size="13">{{ liquidation.liquidateUser?.firstName }} {{ liquidation.liquidateUser?.lastName }}</div>
                                </div>
                            </div>

                            <!--Botones-->
                            <div class="d-flex justify-around" v-on:click.stop="">
                                <a class="custom-button" data-size="small" data-mode="outline" data-bg="azul" :href="`/assets/liquidations/${liquidation['liquidationName']}`" :download="liquidation['liquidationName']">Descargar</a>
                            </div>
                        </div>


                        <div class="separator my-10" v-if="liquidationKey < filteredLiquidations.length - 1"></div>

                    </div>
                </div>

                <div class="opacity-5" data-size="13" v-if="filteredLiquidations && filteredLiquidations.length === 0" data-align="center">¡No hay ningún informe todavia!</div>
            </div>
        </div>


        <!--Estilo pc-->
        <div class="desktop-item">
            <!--Contenido listado-->
            <div class="content-white d-flex column mr-10">
                <!--Header-->
                <div class="d-flex align-center">
                    <!--Título-->
                    <div class="text mr-20" data-size="30" data-weight="700">{{ $route.meta.title }}</div>


                    <!--Botones-->
                    <div class="d-flex">

                            <!-- USUARIOS -->
                            <div
                                class="form my-auto mx-20"
                                v-if="basicData.userSubdomain && basicData.userSubdomain._id === '6909faa9232c09035a03f3b2'"
                            >
                                <div class="form-group d-flex align-start">

                                    <!-- SUPER USUARIO -->
                                    <div class="mx-10" style="width:220px;">
                                        <p class="text mb-5">Súper usuario</p>
                                        <div class="input-group" style="width:100%;">
                                            <select
                                                style="width:100%;"
                                                v-model="selectedSuperUserId"
                                                :disabled="selectedType && selectedType !== 'Súper usuario'"
                                                @change="onChangeUser('Súper usuario')"
                                            >
                                                <option value="">-- Seleccionar --</option>
                                                <option
                                                    v-for="user in usersNoFilter.filter(u => u.label === 'Súper usuario')"
                                                    :key="user._id"
                                                    :value="user._id"
                                                >
                                                    {{ user.firstName }} {{ user.lastName }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- USUARIO -->
                                    <div class="mx-10" style="width:220px;">
                                        <p class="text mb-5">Usuario</p>
                                        <div class="input-group" style="width:100%;">
                                            <select
                                                style="width:100%;"
                                                v-model="selectedUsuarioId"
                                                :disabled="selectedType && selectedType !== 'Usuario'"
                                                @change="onChangeUser('Usuario')"
                                            >
                                                <option value="">-- Seleccionar --</option>
                                                <option
                                                    v-for="user in usersNoFilter.filter(u => u.label === 'Usuario')"
                                                    :key="user._id"
                                                    :value="user._id"
                                                >
                                                    {{ user.firstName }} {{ user.lastName }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- COMERCIAL -->
                                    <div class="mx-10" style="width:220px;">
                                        <p class="text mb-5">Comercial</p>
                                        <div class="input-group" style="width:100%;">
                                            <select
                                                style="width:100%;"
                                                v-model="selectedComercialId"
                                                :disabled="selectedType && selectedType !== 'Comercial'"
                                                @change="onChangeUser('Comercial')"
                                            >
                                                <option value="">-- Seleccionar --</option>
                                                <option
                                                    v-for="user in usersNoFilter.filter(u => u.label === 'Comercial')"
                                                    :key="user._id"
                                                    :value="user._id"
                                                >
                                                    {{ user.firstName }} {{ user.lastName }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div
                                class="form my-auto"
                                v-else
                            >
                                <div class="form-group">
                                    <p class="my-auto"><label>Agente</label></p>
                                    <div class="input-group">
                                        <select v-model="userLiquidate.user">
                                            <option :value="basicData.userLogged">
                                                {{ basicData.userLogged.firstName }} {{ basicData.userLogged.lastName }}
                                            </option>
                                            <option
                                                v-for="user in filteredUserToLiquidate"
                                                :key="user._id"
                                                :value="user"
                                            >
                                                {{ user.firstName }} {{ user.lastName }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>


                        <!--comercializadora y estado liquidacion-->
                        <div class="d-flex column">

                            <!--Comercializadora-->
                            <div class="d-flex my-15 mx-20 ">
                                <div class="text">Comercializad.:</div>

                                <div class="custom-select no-hover" v-on:click.stop="seeFilters('marketer')" v-bind:class="{'seeing': isSeeingFiltersPc.marketer}" v-if="filtersFiltered.marketers">

                                    <div class="ml-10" data-color="azul">{{ getMarketerFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                                    <div class="select-content left form">

                                        <div class="form-group ">
                                            <div class="input-group">
                                                <input data-size="12" v-model="searchFilters.marketer" type="text" placeholder="Busca tu comercializadora...">
                                            </div>
                                        </div>

                                        <div class="d-flex align-center">

                                            <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('marketers')" v-bind:class="{ 'selected': areAllMarketersActives }"></div>

                                            <div class="text">Todos</div>
                                        </div>

                                        <div v-for="marketer in filtersFiltered.marketers" class="d-flex align-center">

                                            <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(marketer, 'marketer')" v-bind:class="{ 'selected': marketer.active }"></div>

                                            <div class="text">{{ marketer.name === '' ? 'Sin comercializadora' : marketer.name }}</div>

                                        </div>
                                    </div>
                                </div>

                                <div v-else class="ml-10" data-size="13" data-color="azul">0 comercializad.</div>
                            </div>


                            <!--Estado de liquidación -->
                            <div class="d-flex my-15 mx-20" v-if="basicData && basicData.userSubdomain && basicData.userSubdomain._id !== '6909faa9232c09035a03f3b2'">
                                <div class="text">Estado de liquidación</div>

                                <div class="custom-select no-hover" v-on:click.stop="seeFilters('status')" v-bind:class="{'seeing': isSeeingFiltersPc.status}" v-if="filtersFiltered.statuses">

                                    <div class="ml-10" data-color="azul">{{ getStatusFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                                    <div class="select-content left form">

                                        <div class="form-group ">
                                            <div class="input-group">
                                                <input data-size="12" v-model="searchFilters.status" type="text" placeholder="Busca tu estado...">
                                            </div>
                                        </div>

                                        <div class="d-flex align-center">

                                            <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('statuses')" v-bind:class="{ 'selected': areAllStatusesActives }"></div>

                                            <div class="text">Todos</div>
                                        </div>

                                        <div v-for="status in filtersFiltered.statuses" class="d-flex align-center">

                                            <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(status, 'status')" v-bind:class="{ 'selected': status.active }"></div>

                                            <div class="text">{{ status.title }}</div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>


                        <!--¿Liquidar?-->
                        <!--<div class="d-flex my-auto">
                            <div class="custom-checkbox pointer mr-5 my-auto" v-on:click.stop.prevent="hasToLiquidate = !hasToLiquidate" v-bind:class="{'selected': hasToLiquidate}"></div>
                            <p class="text my-auto">¿Liquidar?</p>
                        </div>-->

                        <div class="d-flex column">
                            <!--Mostrar numeros pedido Naturgy-->
                            <div class="d-flex my-auto pointer" @click.stop.prevent="canSeeMarketerNumbers = !canSeeMarketerNumbers">
                                <div class="custom-checkbox  mr-5 my-auto" :class="{'selected': canSeeMarketerNumbers}"></div>
                                <p class="text my-auto">Mostrar números pedido</p>
                            </div>

                            <!--Calcular la comisión restando la comisión de los contratos de sus agentes-->
                            <div v-if="basicData?.userSubdomain?._id !== '6909faa9232c09035a03f3b2'" class="d-flex my-auto pointer" @click.stop.prevent="deductHierarchyCommissions = !deductHierarchyCommissions">
                                <div class="custom-checkbox mr-5 my-auto" :class="{'selected': deductHierarchyCommissions}"></div>
                                <p class="text my-auto">Descontar comisiones de jerarquía</p>
                            </div>
                        </div>



                        <!--fechas liquidacion-->
                        <div class="mx-20 d-flex">
                            <div class="custom-select no-hover my-auto " :class="{ 'seeing' : isSeeingLiquidateDates}">

                                <div class="custom-button" v-if="!isReadOnly" data-size="regular" data-bg="azul" v-on:click.stop="isSeeingLiquidateDates = !isSeeingLiquidateDates">fechas</div>

                                <!--Seleccionar fechas de liquidación-->
                                <div class="select-content form" v-on:click.stop="">

                                    <!--fecha inicio-->
                                    <div v-bind:class="{ wrong: userLiquidate.errors && userLiquidate.errors.start}"  class="form-group my-5">
                                        <p class="my-auto"><label>Fecha inicio</label> <span data-color="rojo">*</span></p>
                                        <div class="input-group">
                                            <input data-size="12" v-on:focus="delete userLiquidate.errors.start" v-model="userLiquidate.start" @input="setDate('start')" type="date">
                                        </div>
                                    </div>

                                    <!--fecha final-->
                                    <div v-bind:class="{ wrong: userLiquidate.errors && userLiquidate.errors.end}" class="form-group my-5">
                                        <p class="my-auto"><label>Fecha final</label> <span data-color="rojo">*</span></p>
                                        <div class="input-group">
                                            <input data-size="12" v-on:focus="delete userLiquidate.errors.end" v-model="userLiquidate.end" @input="setDate('end')" type="date">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!--Botón liquidar-->
                        <div class="my-auto mx-20">
                            <div class="my-10 custom-button" v-if="!isReadOnly" data-size="small" data-bg="principal" v-on:click="liquidateUser('pdf')"><i class="fas fa-file-pdf"></i> PDF</div>
                            <div class="my-10 custom-button" v-if="!isReadOnly" data-size="small" data-bg="principal" v-on:click="liquidateUser('excel')"><i class="fas fa-file-excel"></i> EXCEL</div>
                            <div v-if="basicData.userSubdomain && basicData.userSubdomain._id === '6909faa9232c09035a03f3b2'  && (selectedSuperUserId || selectedUsuarioId || selectedComercialId)" class="my-auto mx-10">
                                <div class="custom-button"
                                     data-size="small"
                                     data-bg="azul"
                                     @click="openExtrasModal">
                                    Añadir extra
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!--línea separadora-->
                <div class="mt-30 select-line"></div>


                <!--Filtros-->
                <div class="d-flex justify-between my-10">

                    <!--Agentes-->
                    <div class="d-flex">
                        <div class="text">Agentes:</div>

                        <div class="custom-select no-hover" v-on:click.stop="seeFilters('agent')"  v-bind:class="{'seeing': isSeeingFiltersPc.agent}" v-if="this.filters.checkbox.agentAvailable.data && this.filters.checkbox.agentAvailable.data.length > 0">

                            <div class="ml-10" data-color="azul">{{ getAgentFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content left form">

                                <div class="form-group ">
                                    <div class="input-group">
                                        <input data-size="12" v-model="searchFilters.agent" type="text" placeholder="Busca tu agente...">
                                    </div>
                                </div>

                                <div class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('agents')" v-bind:class="{ 'selected': areAllAgentsActives }"></div>

                                    <div class="text">Todos</div>

                                </div>
                                <div v-for="agent in this.filtersFiltered.agent" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(agent, 'agent')" v-bind:class="{ 'selected': agent.active }"></div>

                                    <div class="text">{{ agent.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 agentes</div>
                    </div>

                    <!--Fecha creación-->
                    <div class="d-flex mx-auto">
                        <div class="text">Fec. creación:</div>

                        <div class="custom-select no-hover" v-on:click.stop="seeFilters('dates')" v-bind:class="{'seeing': isSeeingFiltersPc.dates}">

                            <div class="ml-10" data-color="azul">{{ getPrettyDatesFilters }}<i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content form">

                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Inicial</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="searchFilters.dates.start" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer" v-on:click.stop="searchFilters.dates.start = ''">
                                        <i class="fas fa-x"></i>
                                    </div>

                                </div>

                                <div class="form-group d-flex">
                                    <p class="w-20 my-auto text">Final</p>

                                    <div class="input-group ml-10 w-70">
                                        <input data-size="12" v-model="searchFilters.dates.end" type="date">
                                    </div>

                                    <div class="my-auto mx-10 text pointer" v-on:click.stop="searchFilters.dates.end = ''">
                                        <i class="fas fa-x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>

                    <!--Liquidado-->
                    <div class="d-flex">
                        <div class="text">Liquidado:</div>

                        <!--Contenedor cuentas-->
                        <div class="custom-select no-hover" v-on:click.stop="seeFilters('liquidated')" v-bind:class="{'seeing': isSeeingFiltersPc.liquidated}">

                            <div class="ml-10" data-color="azul">{{ filters.radio.liquidate.data[filters.radio.liquidate.checked].title }} <i class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content w-100-px">
                                <div v-for="liquidateType in filters.radio.liquidate.data" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="selectOrderType('liquidate', liquidateType)" v-bind:class="{ 'selected': liquidateType.value === filters.radio.liquidate.checked }"></div>

                                    <div class="text">{{ liquidateType.title }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>


                <!--liquidaciones-->
                <div class="d-flex column mt-30">

                    <!--Cabecera-->
                    <div class="d-grid liquidations mt-30 mb-10 py-5 px-25 round" data-round="15" data-bg="azul-claro">

                        <div class="d-flex ml-20" data-color="principal">
                            <p class="text ellipsis noWidth mr-5" data-weight="600">
                                Agente
                            </p>
                            <i class="fas my-auto pointer" @click="selectNewOrderType('agent')" :class="this.filters.radio.sortBy.checked === 0 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 1 ? 'fa-sort-up' : 'fa-sort')"></i>
                        </div>

                        <div class="d-flex justify-center" data-color="principal">
                            <p class="text ellipsis noWidth mr-5" data-weight="600">
                                Contratos
                            </p>
                            <i class="fas my-auto pointer" @click="selectNewOrderType('contract')" :class="this.filters.radio.sortBy.checked === 2 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 3 ? 'fa-sort-up' : 'fa-sort')"></i>
                        </div>

                        <div class="d-flex justify-center" data-color="principal">
                            <p class="text ellipsis noWidth mr-5" data-weight="600">
                                Comisión
                            </p>
                            <i class="fas my-auto pointer" @click="selectNewOrderType('commission')" :class="this.filters.radio.sortBy.checked === 4 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 5 ? 'fa-sort-up' : 'fa-sort')"></i>
                        </div>

                        <div class="d-flex justify-center" data-color="principal">
                            <p class="text ellipsis noWidth mr-5" data-weight="600">
                                Fecha inicio
                            </p>
                            <i class="fas my-auto pointer" @click="selectNewOrderType('startDate')" :class="this.filters.radio.sortBy.checked === 6 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 7 ? 'fa-sort-up' : 'fa-sort')"></i>
                        </div>

                        <div class="d-flex justify-center" data-color="principal">
                            <p class="text ellipsis noWidth mr-5" data-weight="600">
                                Fecha final
                            </p>
                            <i class="fas my-auto pointer" @click="selectNewOrderType('endDate')" :class="this.filters.radio.sortBy.checked === 8 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 9 ? 'fa-sort-up' : 'fa-sort')"></i>
                        </div>

                        <div class="d-flex justify-center" data-color="principal">
                            <p class="text ellipsis noWidth mr-5" data-weight="600">
                                Fecha creación
                            </p>
                            <i class="fas my-auto pointer" @click="selectNewOrderType('createdAt')" :class="this.filters.radio.sortBy.checked === 10 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 11 ? 'fa-sort-up' : 'fa-sort')"></i>
                        </div>

                    </div>

                    <template v-for="liquidation in filteredLiquidations">
                        <div class="d-grid liquidations px-25 py-5"  v-on:click="openFile(`/assets/liquidations/${liquidation['liquidationName']}`)">

                            <div class="text d-flex align-center">
                                <i class="fa-solid fa-euro-sign mr-5" data-color="azul" data-weight="600" v-if="liquidation.liquidated"></i>
                                {{ liquidation.liquidateUser?.firstName }} {{ liquidation.liquidateUser?.lastName }}
                            </div>
                            <div class="text d-flex justify-center align-center">{{ liquidation.totalOrders }}</div>
                            <div class="text d-flex justify-center align-center">{{Math.round(liquidation.totalCommission)}}€</div>
                            <div class="text d-flex justify-center align-center">{{ getPrettyDate(liquidation['dates']['start']) }}</div>
                            <div class="text d-flex justify-center align-center">{{ getPrettyDate(liquidation['dates']['end']) }}</div>
                            <div class="text d-flex justify-center align-center">{{ getPrettyDate(liquidation["createdAt"]) }}</div>

                            <!--Botones-->
                            <div class="d-flex align-center justify-end" data-gap="10" v-on:click.stop="">

                                <!--Liquidar-->
                                <div class="pointer" v-if="(!!liquidation.cups && Object.keys(liquidation.cups).length > 0) && !liquidation.liquidated" data-color="azul" v-on:click.stop.prevent="liquidateLiquidation(liquidation['_id'])"><i class="far fa-sack-dollar"></i></div>

                                <!--Descargar-->
                                <a class="pointer text" :href="`/assets/liquidations/${liquidation['liquidationName']}`" :download="liquidation['liquidationName']"><i class="far fa-download"></i></a>

                                <!--Eliminar liquidación-->
                                <div class="pointer" data-color="rojo" v-on:click.stop.prevent="deleteLiquidation(liquidation['_id'])"><i class="far fa-trash"></i></div>
                            </div>
                        </div>
                        <div class="separator my-10"></div>
                    </template>
                </div>

                <!--si no hay liquidaciones todavia-->
                <div v-if="filteredLiquidations && filteredLiquidations.length === 0" class="text text-center  opacity-5" data-align="center">¡No hay ningún informe todavía!</div>
            </div>
        </div>

        <!--Cargando-->
        <div class="loader-box" v-if="isLoading">
            <div class="loader"></div>
        </div>

        <div class="docs"
            v-if="showExtrasModal"
            style="max-width: 1000px; width: 95%; margin: 0 auto;">

            <!-- HEADER -->
            <div class="d-flex justify-between align-center">
                <p class="text" data-size="18" data-weight="700">
                    Extras de liquidación
                </p>

                <div class="custom-button my-auto"
                    data-size="small"
                    data-bg="amarillo"
                    @click="addExtra">
                    <i class="fas fa-plus"></i>
                </div>
            </div>

            <div class="separator my-20"></div>

            <!-- CABECERA TIPO TABLA -->
            <div class="d-grid mb-10"
                data-column="5"
                data-gap="20">

                <p class="text opacity-5" data-size="11">Usuario</p>
                <p class="text opacity-5" data-size="11">Concepto</p>
                <p class="text opacity-5" data-size="11">Importe (€)</p>
                <p class="text opacity-5" data-size="11">Tipo</p>
                <div></div>
            </div>

            <div class="separator my-10"></div>

            <!-- FILAS -->
            <div class="div-content">

                <div class="d-grid mb-15 align-center"
                    data-column="5"
                    data-gap="20"
                    v-for="(extra, index) in extrasDraft"
                    :key="index">

                    <!-- USUARIO (select normal) -->
                    <div class="form-group my-0">
                        <div class="input-group">
                            <select v-model="extra.userId" data-size="10">
                                <option value="" disabled>Selecciona</option>
                                <option
                                    v-for="u in extraUserOptions"
                                    :key="u.code"
                                    :value="u.code"
                                >
                                    {{ u.title }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- CONCEPTO -->
                    <div class="form-group my-0">
                        <div class="input-group">
                            <input
                                v-model="extra.concept"
                                data-size="10"
                                type="text"
                                placeholder="Concepto">
                        </div>
                    </div>

                    <!-- IMPORTE -->
                    <div class="form-group my-0">
                        <div class="input-group">
                            <input
                                v-model.number="extra.amount"
                                data-size="10"
                                type="number"
                                step="0.01"
                                placeholder="0.00">
                        </div>
                    </div>

                    <!-- TIPO -->
                    <div class="form-group my-0">
                        <div class="input-group">
                            <select v-model="extra.type" data-size="10">
                                <option value="" disabled>Selecciona</option>
                                <option value="bono">Bono</option>
                                <option value="incentivo">Incentivo</option>
                                <option value="ajuste">Ajuste</option>
                            </select>
                        </div>
                    </div>

                    <!-- ELIMINAR -->
                    <div class="d-flex align-center justify-center">
                        <i class="fas fa-trash pointer"
                        data-size="14"
                        data-color="rojo"
                        @click="removeExtra(index)">
                        </i>
                    </div>

                </div>

            </div>

            <div class="separator"></div>

            <!-- BOTONES -->
            <div class="d-flex justify-end mt-20">
                <button class="custom-button mr-15"
                        data-size="small"
                        data-bg="rojo"
                        @click="showExtrasModal = false">
                    Cerrar
                </button>

                <button class="custom-button"
                        data-size="small"
                        data-bg="principal"
                        @click="saveExtras">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'LiquidationsComponent',
        props: ['basicData'],
        data(){
            return{
                extrasDraft: [],
                previousUserLiquidate: null,
                previousUser: null,
                extrasList: [],
                showExtrasModal: false,
                selectedType: null,
                selectedSuperUserId: '',
                selectedUsuarioId: '',
                selectedComercialId: '',
                isSeeingFilters: false,
                isSeeingFiltersPc:{
                    agent: false,
                    marketer: false,
                    status: false,
                    sort: false,
                    liquidated: false
                },
                searchFilters:{
                    agent: '',
                    marketer: '',
                    status: '',
                    dates: {
                        start: '',
                        end: ''
                    },
                },
                isSeeingLiquidateDates: false,
                liquidationSelectedToSee: '',
                liquidations: [],
                filters: {
                    checkbox: {
                        agentAvailable: {
                            title: 'Agente',
                            data: []
                        }
                    },
                    radio: {
                        liquidate: {
                            title: '¿Liquidar?',
                            checked: 1,
                            data: [
                                {
                                    title: 'Si',
                                    value: 0,
                                },
                                {
                                    title: 'No',
                                    value: 1,
                                },
                            ]
                        },
                        sortBy: {
                            title: 'Ordenar por',
                            checked: 11,
                            data: [
                                {
                                    title: 'Agente (A-Z)',
                                    value: 0,
                                },
                                {
                                    title: 'Agente (Z-A)',
                                    value: 1,
                                },
                                {
                                    title: 'Contratos (Asc)',
                                    value: 2,
                                },
                                {
                                    title: 'Contratos (Desc)',
                                    value: 3,
                                },
                                {
                                    title: 'Comisión (Asc)',
                                    value: 4,
                                },
                                {
                                    title: 'Comisión (Desc)',
                                    value: 5,
                                },
                                {
                                    title: 'Fecha inicio (Antigua)',
                                    value: 6,
                                },
                                {
                                    title: 'Fecha inicio (Reciente)',
                                    value: 7,
                                },
                                {
                                    title: 'Fecha fin (Antigua)',
                                    value: 8,
                                },
                                {
                                    title: 'Fecha fin (Reciente)',
                                    value: 9,
                                },
                                {
                                    title: 'Fecha creacion (Antigua)',
                                    value: 10,
                                },
                                {
                                    title: 'Fecha creacion (Reciente)',
                                    value: 11,
                                },
                            ]
                        }
                    }
                },
                liquidationStatuses:[
                    {
                        code: 'nl',
                        title: 'No liquidado',
                        active: true,
                        limitedTo: ['65cb57489c2c285441086a43', '65fc2c029e5f5622e308cea3', '65d704c63d2a9cbfd79e549a']
                    },
                    {
                        code: 'al',
                        title: 'Liquidado agente',
                        active: true,
                        limitedTo: ['65cb57489c2c285441086a43', '65fc2c029e5f5622e308cea3', '65d704c63d2a9cbfd79e549a']
                    },
                    {
                        code: 'cl',
                        title: 'Liquidado comerc.',
                        active: true,
                        limitedTo: ['65cb57489c2c285441086a43', '65fc2c029e5f5622e308cea3', '65d704c63d2a9cbfd79e549a']
                    },
                    {
                        code: 'tl',
                        title: 'Total liquidado',
                        active: true,
                        limitedTo: ['65cb57489c2c285441086a43', '65fc2c029e5f5622e308cea3', '65d704c63d2a9cbfd79e549a']
                    },
                    {
                        code: 'ad',
                        title: 'Decomisionado agente',
                        active: true,
                        limitedTo: ['65cb57489c2c285441086a43', '65fc2c029e5f5622e308cea3', '65d704c63d2a9cbfd79e549a']
                    },
                    {
                        code: 'md',
                        title: 'Decomisionado comercializadora',
                        active: true,
                        limitedTo: ['65cb57489c2c285441086a43', '65fc2c029e5f5622e308cea3', '65d704c63d2a9cbfd79e549a']
                    },
                    {
                        code: 'tm',
                        title: 'Total decomisionado',
                        active: true,
                        limitedTo: ['65cb57489c2c285441086a43', '65fc2c029e5f5622e308cea3', '65d704c63d2a9cbfd79e549a']
                    },
                ],
                /*marketers: [
                    {title: 'CYE', active: true},
                    {title: 'Endesa', active: true},
                    {title: 'Gana Energía', active: true},
                    {title: 'IberEléctrica', active: true},
                    {title: 'Iberdrola', active: true},
                    {title: 'Naturgy', active: true},
                    {title: 'Unieléctrica', active: true},
                    {title: 'VM', active: true},
                    {title: 'Sin comercializadora', active: true},
                ],*/
                marketers: [],
                userLiquidate: {
                    user: '',
                    marketers: [],
                    liquidationStatuses: [],
                    start: '',
                    end: '',
                    errors: {}
                },
                hasToLiquidate: false,
                canSeeMarketerNumbers: false,
                deductHierarchyCommissions: false,
                isLoading: false,
                usersAssignedToMe: []
            }
        },
        mounted() {
            this.fetchLiquidations();

            this.fetchMarketers()

            if (this.basicData.userLogged._id)
                this.userLiquidate.user = this.basicData.userLogged


            //Filtros fecha
            if (this.$cookies.get('filters')['liquidations'] && this.$cookies.get('filters')['liquidations']['dates']){

                if (this.$cookies.get('filters')['liquidations']['dates']['start'] && this.$cookies.get('filters')['liquidations']['dates']['start'] !== '')
                    this.userLiquidate.start = this.$cookies.get('filters')['liquidations']['dates']['start'];

                if (this.$cookies.get('filters')['liquidations']['dates']['end'] && this.$cookies.get('filters')['liquidations']['dates']['end'] !== '')
                    this.userLiquidate.end = this.$cookies.get('filters')['liquidations']['dates']['end'];
            }

            if (this.basicData.userLogged && this.usersAssignedToMe.length === 0)
                this.fetchUsersAssignedToMe()

        },
        watch:{

            "basicData.userLogged"(){
               this.userLiquidate.user  = this.basicData.userLogged

                //Busco si hay alguien que haya tramitado usuario con sesión iniciada
                if (this.usersAssignedToMe.length === 0)
                    this.fetchUsersAssignedToMe()

            },
            "basicData.userList"(){

                if (this.filters.checkbox.agentAvailable.data.length === 0){
                    this.liquidations.forEach((liquidation) => {

                        if (liquidation.liquidateUser === this.basicData.userLogged._id)
                            liquidation.liquidateUser = this.basicData.userLogged
                        else{
                            liquidation.liquidateUser = this.basicData.userListComplete.find(user => user._id === liquidation.liquidateUser)
                        }
                    })

                    //Establezco los filtros
                    this.setFilters()
                }
            }
        },
        methods:{
            applyUserSelection(type, user) {

                this.selectedType = type

                if (type !== 'Súper usuario') this.selectedSuperUserId = ''
                if (type !== 'Usuario') this.selectedUsuarioId = ''
                if (type !== 'Comercial') this.selectedComercialId = ''

                this.userLiquidate.user = user
            },

            resetSelect(type) {
                if (type === 'Súper usuario') this.selectedSuperUserId = ''
                if (type === 'Usuario') this.selectedUsuarioId = ''
                if (type === 'Comercial') this.selectedComercialId = ''
            },

            addExtra() {

                if (!this.userLiquidate.user?._id) return

                this.extrasDraft.push({
                    userId: this.userLiquidate.user._id,
                    concept: '',
                    amount: null,
                    type: ''
                })
            },

            removeExtra(index) {
                this.extrasDraft.splice(index, 1)
            },
            saveExtras() {



                for (let extra of this.extrasDraft) {

                    if (!extra.userId) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Usuario obligatorio'
                        })
                        return
                    }

                    if (!extra.concept || extra.concept.trim() === '') {
                        Swal.fire({
                            icon: 'error',
                            title: 'El concepto no puede estar vacío'
                        })
                        return
                    }

                    if (extra.amount === null || extra.amount === '' || isNaN(extra.amount) || extra.amount === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'El importe debe ser mayor que 0'
                        })
                        return
                    }

                    if (!extra.type) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Selecciona un tipo'
                        })
                        return
                    }
                }

                // guardar definitivamente
                this.extrasList = JSON.parse(JSON.stringify(this.extrasDraft))

                this.showExtrasModal = false

                Swal.fire({
                    icon: 'success',
                    title: 'Extras guardados correctamente',
                    timer: 1500,
                    showConfirmButton: false
                })
            },
            openExtrasModal() {
                this.extrasDraft = JSON.parse(JSON.stringify(this.extrasList))
                this.showExtrasModal = true
            },
            onChangeUser(type) {

                    let selectedId = ''

                    if (type === 'Súper usuario') selectedId = this.selectedSuperUserId
                    if (type === 'Usuario') selectedId = this.selectedUsuarioId
                    if (type === 'Comercial') selectedId = this.selectedComercialId

                    const oldUser = this.userLiquidate.user
                    const newUser = this.usersNoFilter.find(u => u._id === selectedId)

                    // Si deselecciona
                    if (!selectedId) {
                        this.selectedType = null
                        this.userLiquidate.user = this.basicData.userLogged
                        return
                    }

                    // Si hay extras → preguntar
                    if (this.extrasList.length > 0) {

                        Swal.fire({
                            icon: 'warning',
                            title: 'Cambiar usuario',
                            text: 'Si cambia el usuario se eliminarán los extras añadidos. ¿Desea continuar?',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, cambiar',
                            cancelButtonText: 'Cancelar'
                        }).then(result => {

                            if (result.isConfirmed) {

                                this.extrasList = []
                                this.applyUserSelection(type, newUser)

                            } else {

                                // 🔥 Restaurar completamente el estado anterior
                                this.userLiquidate.user = oldUser
                                this.selectedType = null

                                this.selectedSuperUserId = ''
                                this.selectedUsuarioId = ''
                                this.selectedComercialId = ''

                                if (oldUser?._id) {
                                    const old = this.usersNoFilter.find(u => u._id === oldUser._id)
                                    if (old?.label === 'Súper usuario') this.selectedSuperUserId = old._id
                                    if (old?.label === 'Usuario') this.selectedUsuarioId = old._id
                                    if (old?.label === 'Comercial') this.selectedComercialId = old._id
                                }

                            }

                        })

                    } else {

                        this.applyUserSelection(type, newUser)

                    }
                },
            fetchLiquidations(){

                this.isLoading = true;

                axios.get('/api/liquidations')
                    .then((res) => {
                        this.liquidations = res.data.liquidations;

                        //Saco los usuarios
                        if (this.basicData.userList){
                            this.liquidations.forEach((liquidation) => {

                                if (liquidation.liquidateUser === this.basicData.userLogged._id)
                                    liquidation.liquidateUser = this.basicData.userLogged
                                else{
                                    liquidation.liquidateUser = this.basicData.userListComplete.find(user => user._id === liquidation.liquidateUser)
                                }
                            })

                            //Establezco los filtros
                            this.setFilters()
                        }

                    })
                    .catch((err) => {
                        console.log(err)

                    }).finally(() => {
                        this.isLoading = false;
                })
            },
            async fetchMarketers(){
                await axios.get('/api/marketers')
                    .then((res) => {
                    // todo activo por defecto
                    this.marketers = (res.data.marketers || []).map(m => ({ ...m, active: true }));

                    // añadir "Sin comercializadora" si no existe (name === '' o lo envíen como null)
                    const hasSin = this.marketers.some(m => (m.name ?? '') === '');
                    if (!hasSin) {
                        this.marketers.push({ name: '', active: true });
                    }

                    // ordenar por nombre mostrando correctamente "Sin comercializadora"
                    this.marketers.sort((a, b) => {
                        const an = (a.name && a.name.trim() !== '') ? a.name : 'Sin comercializadora';
                        const bn = (b.name && b.name.trim() !== '') ? b.name : 'Sin comercializadora';
                        return an.localeCompare(bn, 'es', { sensitivity: 'base' });
                    });
                    })
                    .catch((err) => {
                    console.log(err)
                    })
                }
                ,
            setFilters() {
                //Borro por si ya hay establecidos
                this.filters.checkbox.agentAvailable.data = [];

                let liquidations = JSON.parse(JSON.stringify(this.liquidations))

                //Recorro cada producto
                for (let liquidation of liquidations) {

                    //AGENTE
                    let existsAgent = this.filters.checkbox.agentAvailable.data.some(label => label.title === (liquidation.liquidateUser?.firstName + ' ' + liquidation.liquidateUser?.lastName))

                    if (!existsAgent) {

                        let productType = {
                            title: (liquidation.liquidateUser?.firstName + ' ' + liquidation.liquidateUser?.lastName),
                            active: true
                        }

                        this.filters.checkbox.agentAvailable.data.push(productType);
                    }


                    //ORDENO LOS SELECTS

                    //Agentes
                    this.filters.checkbox.agentAvailable.data.sort((a, b) => a.title.localeCompare(b.title));

                }
            },
            async fetchUsersAssignedToMe(){
                await axios.get('/api/liquidations/users')
                    .then((res) => {
                        this.usersAssignedToMe = res.data.agents
                    })
                    .catch((err) => {
                        console.log(err)
                    })

            },
            selectNewOrderType(orderType){
                switch(orderType){
                    case 'agent':

                        if (this.filters.radio.sortBy.checked !== 0 && this.filters.radio.sortBy.checked !== 1)
                            this.filters.radio.sortBy.checked = 0
                        else if (this.filters.radio.sortBy.checked === 0)
                            this.filters.radio.sortBy.checked = 1
                        else if (this.filters.radio.sortBy.checked === 1)
                            this.filters.radio.sortBy.checked = 11

                        break;

                    case 'contract':

                        if (this.filters.radio.sortBy.checked !== 2 && this.filters.radio.sortBy.checked !== 3)
                            this.filters.radio.sortBy.checked = 2
                        else if (this.filters.radio.sortBy.checked === 2)
                            this.filters.radio.sortBy.checked = 3
                        else if (this.filters.radio.sortBy.checked === 3)
                            this.filters.radio.sortBy.checked = 11

                        break;

                    case 'commission':

                        if (this.filters.radio.sortBy.checked !== 4 && this.filters.radio.sortBy.checked !== 5)
                            this.filters.radio.sortBy.checked = 4
                        else if (this.filters.radio.sortBy.checked === 4)
                            this.filters.radio.sortBy.checked = 5
                        else if (this.filters.radio.sortBy.checked === 5)
                            this.filters.radio.sortBy.checked = 11

                        break;

                    case 'startDate':

                        if (this.filters.radio.sortBy.checked !== 6 && this.filters.radio.sortBy.checked !== 7)
                            this.filters.radio.sortBy.checked = 6
                        else if (this.filters.radio.sortBy.checked === 6)
                            this.filters.radio.sortBy.checked = 7
                        else if (this.filters.radio.sortBy.checked === 7)
                            this.filters.radio.sortBy.checked = 11

                        break;

                    case 'endDate':

                        if (this.filters.radio.sortBy.checked !== 8 && this.filters.radio.sortBy.checked !== 9)
                            this.filters.radio.sortBy.checked = 8
                        else if (this.filters.radio.sortBy.checked === 8)
                            this.filters.radio.sortBy.checked = 9
                        else if (this.filters.radio.sortBy.checked === 9)
                            this.filters.radio.sortBy.checked = 11

                        break;

                    case 'createdAt':

                        if (this.filters.radio.sortBy.checked !== 10 && this.filters.radio.sortBy.checked !== 11)
                            this.filters.radio.sortBy.checked = 10
                        else if (this.filters.radio.sortBy.checked === 10)
                            this.filters.radio.sortBy.checked = 11
                        else if (this.filters.radio.sortBy.checked === 11)
                            this.filters.radio.sortBy.checked = 10

                        break;
                }
            },
            selectOrderType(type, orderType){
                //Recorro los tipos de ordenacion y los pongo todos a false menos el que se ha seleccionado

                switch(type){
                    case 'sort':
                        this.filters.radio.sortBy.checked = orderType.value;
                        break;

                    case 'liquidate':
                        this.filters.radio.liquidate.checked = orderType.value;
                        break;
                }


                this.isSeeingFiltersPc.sort  = false;

                this.hideCustomSelects()
            },
            seeLiquidationInfo(liquidation){

                //Compruebo si se esta clicando uno que ya se esta viendo
                if (this.liquidationSelectedToSee._id === liquidation._id){
                    this.liquidationSelectedToSee = ''
                }else{
                    this.liquidationSelectedToSee = liquidation;
                }
            },
            async liquidateUser(typeDownload){

                this.isSeeingLiquidateDates =  false;


                //validaciones
                let hasErrors = false;
                let errorMessage = '';

                if (!this.userLiquidate.start && !this.userLiquidate.end){
                    this.userLiquidate.errors.start = 'Debes introducir al menos una fecha'
                    errorMessage += '<li>' + this.userLiquidate.errors.start + '</li>'
                    hasErrors = true;
                }

                if (this.userLiquidate.start && this.userLiquidate.end && this.userLiquidate.end < this.userLiquidate.start){
                    this.userLiquidate.errors.end = 'La fecha final no puede ser anterior a la inicial'
                    errorMessage += '<li>' + this.userLiquidate.errors.end + '</li>'
                    hasErrors = true;
                }

                //Compruebo si hay alguna comerc. activa
                let isSomeActive = this.marketers.some((marketer) => marketer.active)


                //Meto las comercializadoras
                this.userLiquidate.marketers = this.marketers
                    .filter(marketer => marketer.active)
                    .map(marketer => marketer.name);

                if (!isSomeActive){
                    this.userLiquidate.errors.marketer = 'Tienes que tener seleccionado al menos una comercializadora'
                    errorMessage += '<li>' + this.userLiquidate.errors.marketer + '</li>'
                    hasErrors = true;
                }


                //Compruebo si hay alguna comerc. activa
                let isSomeStatusActive = this.liquidationStatuses.some((status) => status.active)

                //Meto los estados
                this.userLiquidate.liquidationStatuses = this.liquidationStatuses
                    .filter(status => status.active)
                    .map(status => status.code);

                if (!isSomeStatusActive){
                    this.userLiquidate.errors.status = 'Tienes que tener seleccionado al menos un estado'
                    errorMessage += '<li>' + this.userLiquidate.errors.status + '</li>'
                    hasErrors = true;
                }

                if (!hasErrors){

                    this.isLoading = true;

                    await axios.post(`/api/liquidations/liquidateUser`, {
                        user: this.userLiquidate.user,
                        dates: {start: this.userLiquidate.start, end: this.userLiquidate.end},
                        userLogged: this.basicData.userLogged,
                        marketers: this.userLiquidate.marketers,
                        liquidationStatuses: this.userLiquidate.liquidationStatuses,
                        typeDownload: typeDownload,
                        seeMarketerNumbers: this.canSeeMarketerNumbers,
                        deductHierarchyCommissions: this.deductHierarchyCommissions,
                        enterprise: this.basicData.enterprise,
                        userSubdomain: this.basicData.userSubdomain,
                        extras: this.extrasList
                        },
                        {responseType: 'blob'})
                        .then((response) => {

                            Swal.fire({
                                icon: 'success',
                                title: 'Informe creado!',
                                timer: 1500,
                                timerProgressBar: true
                            })


                            //Si es excel
                            if (typeDownload === 'excel'){
                                // Crear un enlace de descarga
                                const url = window.URL.createObjectURL(new Blob([response.data]));
                                const link = document.createElement('a');
                                link.href = url;
                                link.setAttribute('download', 'liquidation.xlsx'); // Nombre del archivo que se descargará
                                document.body.appendChild(link);
                                link.click();
                                link.remove();
                            }

                            this.fetchLiquidations()

                            //Reseteo
                            /*this.userLiquidate = {
                                user: this.basicData.userLogged,
                                marketers: [],
                                liquidationStatus: 'nl',
                                start: '',
                                end: '',
                                errors: {}
                            }*/

                            //Reseteo dependiendo del sistema de selector
                            if (this.basicData.userSubdomain && this.basicData.userSubdomain._id === '6909faa9232c09035a03f3b2') {

                                // selector especial → reset total
                                this.userLiquidate.user = null
                                this.selectedSuperUserId = ''
                                this.selectedUsuarioId = ''
                                this.selectedComercialId = ''
                                this.selectedType = null
                                this.extrasList = []
                                this.extrasDraft = []

                            } else {

                                // selector normal → vuelve al usuario logueado
                                this.userLiquidate.user = this.basicData.userLogged

                            }

                            this.userLiquidate.errors = {}


                            //Cierro ventana
                            this.isSeeingLiquidateDates = false;
                        })
                        .catch((err) => {
                            console.log(err)
                        }).finally(() => {
                            this.isLoading = false;
                        })
                }else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Comprueba las fechas!',
                        html: errorMessage
                    })
                }
            },
            openFile(path){
                window.location.href = path
            },
            deleteLiquidation(id){

                Swal.fire({
                    icon: 'error',
                    title: '¿Estas seguro?',
                    text: 'Una vez elimines el informe no lo podrás recuperar.',
                    showConfirmButton: true,
                    confirmButtonText: 'Sí',
                    showCancelButton: true,
                    cancelButtonText: 'No'
                }).then((res) => {
                    if (res.isConfirmed){

                        axios.delete(`/api/liquidations/${id}`)
                            .then((res) => {

                                //Recargo las liquidaciones
                                this.fetchLiquidations()

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Informe eliminado',
                                    text: 'El informe ha sido eliminado correctamente!',
                                    timer: 1500
                                })
                            })
                            .catch((err) => {
                                console.log(err)
                            })
                    }
                })
            },
            seeFilters(type){

                //Cerrar selects
                this.hideCustomSelects()

                switch(type){
                    case 'agent':
                        this.isSeeingFiltersPc.agent = true;
                        break;

                    case 'marketer':
                        this.isSeeingFiltersPc.marketer = true;
                        break;

                    case 'status':
                        this.isSeeingFiltersPc.status = true;
                        break;

                    case 'dates':
                        this.isSeeingFiltersPc.dates = true;
                        break;

                    case 'sort':
                        this.isSeeingFiltersPc.sort  = true;
                        break;

                    case "liquidated":
                        this.isSeeingFiltersPc.liquidated = true;
                        break;
                }
            },
            hideCustomSelects(){

                //Cierro el select de crear pedido
                this.isSeeingLiquidateDates = false;

                //Cierro todos los filtros
                this.isSeeingFiltersPc = {
                    agent: false,
                    dates: false,
                    sort: false,
                    liquidated: false
                }
            },
            toggleVisibility(product, type){
                product.active = !product.active

                if (type === 'marketer')
                    this.canSeeMarketerNumbers = false
            },
            toggleAllVisibility(type){

                let changeTo = false;

                switch (type){

                    case 'agents':
                        changeTo = !this.areAllAgentsActives;

                        this.filters.checkbox.agentAvailable.data.forEach(agent => {
                            agent.active = changeTo;
                        });
                        break;

                    case 'marketers':
                        changeTo = !this.areAllMarketersActives;

                        this.marketers.forEach(marketer => {
                            marketer.active = changeTo;
                        });

                        this.canSeeMarketerNumbers = false

                        //Cuando cambien las comercializadoras se estableceran los productos
                        //this.setComputedFiltered()

                        break;

                    case 'statuses':
                        changeTo = !this.areAllStatusesActives;

                        this.liquidationStatuses.forEach(status => {
                            status.active = changeTo;
                        });

                        //Cuando cambien las comercializadoras se estableceran los productos
                        //this.setComputedFiltered()

                        break;
                }

            },
            getPrettyDate(date){
                let dateNow = new Date(date);
                let day = String(dateNow.getDate()).padStart(2, '0'); // Asegura que el día tenga dos dígitos
                let month = String(dateNow.getMonth() + 1).padStart(2, '0'); // Asegura que el mes tenga dos dígitos y se suma 1 porque en JavaScript los meses van de 0 a 11
                let year = dateNow.getFullYear();
                return `${day}/${month}/${year}`;
            },
            setDate(type){

                let cookies = this.$cookies.get('filters')

                if(!cookies['liquidations'])
                    cookies['liquidations'] = {
                        'dates':{
                            'start': '',
                            'end': ''
                        }
                    }

                if (type === 'start')
                    cookies['liquidations']['dates']['start'] = this.userLiquidate.start
                else
                    cookies['liquidations']['dates']['end'] = this.userLiquidate.end

                this.$cookies.set('filters', cookies);
            },
            liquidateLiquidation(id){
                Swal.fire({
                    icon: 'warning',
                    title: '¿Estás seguro?',
                    text: 'Si continuas se liquidarán todos los contratos',
                    confirmButtonText: 'Sí',
                    //confirmButtonColor: 'blue',
                    showCancelButton: true,
                    cancelButtonText: 'No',
                    cancelButtonColor: 'red'
                }).then((res) => {
                    if (res.isConfirmed)

                        axios.post('/api/liquidations/liquidateLiquidation', { id, userSubdomain: this.basicData.userSubdomain })
                            .then((res) => {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Contratos liquidados',
                                    timer: 1500,
                                    timerProgressBar: true
                                })
                                this.fetchLiquidations();
                            })
                            .catch((err) => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al liquidar contratos',
                                    text: 'Hubo un error al intentar liquidar los contratos',
                                })
                                console.log(err)
                            })
                })
            }
        },
        computed:{
            extraUserOptions() {

                if (!this.userLiquidate.user?._id) return []
                if (!this.basicData?.subdomainUserList) return []

                const users = this.basicData.subdomainUserList
                const selectedId = this.userLiquidate.user._id

                const mainUser = users.find(u => u._id === selectedId)
                if (!mainUser) return []

                const children = users.filter(u =>
                    Array.isArray(u.responsibles) &&
                    u.responsibles.includes(selectedId)
                )

                const allowed = [mainUser, ...children]

                return allowed.map(user => ({
                    title: user.firstName + ' ' + user.lastName,
                    code: user._id
                }))
            },
            usersNoFilter() {
                if (!this.basicData || !this.basicData.subdomainUserList) return []

                let users = [...this.basicData.subdomainUserList]

                users.sort((a, b) => {
                    const nameA = `${a.firstName} ${a.lastName}`
                    const nameB = `${b.firstName} ${b.lastName}`
                    return nameA.localeCompare(nameB, 'es', { sensitivity: 'base' })
                })

                return users
            },
            filteredLiquidations(){

                let liquidations = [...this.liquidations];

                let liquidationsFiltered = [];

                if (liquidations.length <= 0) return []

                //filtro
                for (let key in liquidations) {

                    let liquidation = liquidations[key];


                    //Compruebo si su etiqueta esta visible o no
                    let hasAgentVisible = false;

                    this.filters.checkbox.agentAvailable.data.find((agent) => {
                        if ((agent.title === (liquidation.liquidateUser?.firstName + ' ' + liquidation.liquidateUser?.lastName)) && agent.active)
                            hasAgentVisible = true;
                    })


                    //Por fechas ( en vez de hacer un intervalo entero lo voy a hacer dividido, desde y hasta)
                    let afterFirstDate = true;
                    let beforeLastDate = true;
                    let orderDate = moment(liquidation.createdAt)

                    //Pedidos desde
                    if (this.searchFilters.dates.start){
                        let startDate = moment(this.searchFilters.dates.start)

                        if (orderDate.isBefore(startDate)) afterFirstDate = false
                    }

                    //Pedidos hasta
                    if (this.searchFilters.dates.end){
                        let endDate = moment(this.searchFilters.dates.end)

                        if (orderDate.isAfter(endDate)) beforeLastDate = false
                    }

                    //Liquidados
                    let liquidated = true;
                    if (this.filters.radio.liquidate.checked == 0){
                        liquidated = liquidation.liquidated;
                    }

                    if (hasAgentVisible && afterFirstDate && beforeLastDate && liquidated) liquidationsFiltered.push(liquidation)
                }

                //ORDENO
                switch (this.filters.radio.sortBy.checked) {
                    case 0:
                        //Agente (A-Z)
                        liquidationsFiltered = liquidationsFiltered.sort((a,b) => {
                            let nameA = a.liquidateUser?.firstName + a.liquidateUser?.lastName;
                            let nameB = b.liquidateUser?.firstName + b.liquidateUser?.lastName;
                            return nameA.localeCompare(nameB, "es", {sensitivity: 'base'})
                        })
                        break;
                    case 1:
                        //Agente (Z-A)
                        liquidationsFiltered = liquidationsFiltered.sort((a,b) => {
                            let nameA = a.liquidateUser?.firstName + a.liquidateUser?.lastName;
                            let nameB = b.liquidateUser?.firstName + b.liquidateUser?.lastName;
                            return nameB.localeCompare(nameA, "es", {sensitivity: 'base'})
                        });
                        break;
                    case 2:
                        //Menos contratos
                        liquidationsFiltered = liquidationsFiltered.sort((a, b) => {
                            return a.totalOrders - b.totalOrders;
                        })
                        break;
                    case 3:
                        //Más contratos
                        liquidationsFiltered = liquidationsFiltered.sort((a, b) => {
                            return b.totalOrders - a.totalOrders;
                        })
                        break;
                    case 4:
                        //Comisión más alta
                        liquidationsFiltered = liquidationsFiltered.sort((a, b) => {
                            return a.totalCommission - b.totalCommission;
                        })
                        break;
                    case 5:
                        //Comisión más alta
                        liquidationsFiltered = liquidationsFiltered.sort((a, b) => {
                            return b.totalCommission - a.totalCommission;
                        })
                        break;
                    case 6:
                        //Fecha inicio más antigua
                        liquidationsFiltered = liquidationsFiltered.sort((a, b) => {
                            let aDate = new Date(a.dates.start);
                            let bDate = new Date(b.dates.start);

                            if (aDate < bDate) return -1;
                            if (aDate > bDate) return 1;
                            return 0;
                        })
                        break;
                    case 7:
                        //Fecha inicio más reciente
                        liquidationsFiltered = liquidationsFiltered.sort((a, b) => {
                            let aDate = new Date(a.dates.start);
                            let bDate = new Date(b.dates.start);

                            if (aDate < bDate) return 1;
                            if (aDate > bDate) return -1;
                            return 0;
                        })
                        break;
                    case 8:
                        //Fecha fin más antigua
                        liquidationsFiltered = liquidationsFiltered.sort((a, b) => {
                            let aDate = new Date(a.dates.end);
                            let bDate = new Date(b.dates.end);

                            if (aDate < bDate) return -1;
                            if (aDate > bDate) return 1;
                            return 0;
                        })
                        break;
                    case 9:
                        //Fecha fin más reciente
                        liquidationsFiltered = liquidationsFiltered.sort((a, b) => {
                            let aDate = new Date(a.dates.end);
                            let bDate = new Date(b.dates.end);

                            if (aDate < bDate) return 1;
                            if (aDate > bDate) return -1;
                            return 0;
                        })
                        break;
                    case 10:
                        //Creación más antigua
                        liquidationsFiltered = liquidationsFiltered.sort((a,b) => {
                            let aDate = new Date(a.createdAt);
                            let bDate = new Date(b.createdAt);

                            if (aDate < bDate) return -1;
                            if (aDate > bDate) return 1;
                            return 0;
                        })
                        break;

                    case 11:
                        //Creación más reciente
                        liquidationsFiltered = liquidationsFiltered.sort((a,b) => {
                            let aDate = new Date(a.createdAt);
                            let bDate = new Date(b.createdAt);

                            if (aDate < bDate) return 1;
                            if (aDate > bDate) return -1;
                            return 0;
                        })
                        break;
                }

                return liquidationsFiltered
            },
            filtersFiltered(){
                let filteredFiltered = {
                    'agent': [],
                    'marketers': [],
                    'statuses': []
                }

                //Agent
                let AgentSearch = this.searchFilters.agent.replaceAll(' ', '').toLowerCase();


                this.filters.checkbox.agentAvailable.data.forEach((agent) => {

                    let OptSearch = agent.title.replaceAll(' ', '').toLowerCase()

                    if (OptSearch.includes(AgentSearch)) filteredFiltered.agent.push(agent)
                })

                //Comercializadoras
                let MarketerSearch = this.searchFilters.marketer.replaceAll(' ', '').toLowerCase();

                this.marketers.forEach((marketer) => {
                const display = (marketer.name && marketer.name.trim() !== '') ? marketer.name : 'Sin comercializadora';
                let OptSearch = display.replaceAll(' ', '').toLowerCase();

                if (OptSearch.includes(MarketerSearch)) filteredFiltered.marketers.push(marketer)
                })


                //Estados de liquidación
                let StatusSearch = this.searchFilters.status.replaceAll(' ', '').toLowerCase();

                this.liquidationStatuses.forEach((status) => {

                    let SSearch = status.title.replaceAll(' ', '').toLowerCase()


                    if (SSearch.includes(StatusSearch)) filteredFiltered.statuses.push(status)
                })


                return filteredFiltered
            },
            getPrettyDatesFilters(){

                let startDate = moment(this.searchFilters.dates.start).format('DD/MM/YY');
                let endDate = moment(this.searchFilters.dates.end).format('DD/MM/YY');

                if (!this.searchFilters.dates.start && !this.searchFilters.dates.end){
                    return 'Selec. fechas'
                }else{

                    //si solo se ha seleccionado la fecha inicial
                    if (!this.searchFilters.dates.end){
                        return 'Desde  ' + startDate
                    }else if (!this.searchFilters.dates.start){//si solo se ha seleccionado la fecha final
                        return 'Hasta  ' + endDate
                    }else {
                        return startDate + ' - ' + endDate
                    }

                }
            },
            areAllAgentsActives(){
                if (this.filters.checkbox.agentAvailable.data.length > 0){

                    let activeAgentsCount = this.filters.checkbox.agentAvailable.data.filter(agent => agent.active).length;

                    return activeAgentsCount === this.filters.checkbox.agentAvailable.data.length;
                }else return false
            },
            areAllMarketersActives(){
                if (this.marketers && this.marketers.length > 0){

                    let activeMarketersCount = this.marketers.filter(marketer => marketer.active).length;

                    return activeMarketersCount === this.marketers.length;
                }else return false
            },
            areAllStatusesActives(){
                if (this.liquidationStatuses && this.liquidationStatuses.length > 0){

                    let activeStatusesCount = this.liquidationStatuses.filter(status => status.active).length;

                    return activeStatusesCount === this.liquidationStatuses.length;
                }else return false
            },
            getAgentFilterTitle(){

                let totalActives = 0;

                this.filters.checkbox.agentAvailable.data.forEach((agent) => {
                    if (agent.active) totalActives++;
                })

                if (this.liquidations) return totalActives === this.liquidations.length ? 'Todos' : (totalActives + ' agentes/s')
            },
            sortTypeSelected(){
                return this.filters.radio.sortBy.data[this.filters.radio.sortBy.checked]
            },
            isReadOnly(){
                if (!this.basicData.userLogged)
                    return true
                else (this.basicData.userLogged)
                return this.basicData.userLogged.permissions.includes('READONLY')
            },
            filteredUserToLiquidate(){

                if (!this.basicData.userList || !this.basicData.userLogged) return []

                let users = [...this.basicData.userList]

                let userSubdomainArrayToCheck = [];



                //Si se han sacado usuarios de otros subdominios se añaden
                if (this.usersAssignedToMe.length > 0)
                    this.usersAssignedToMe.forEach((otherUser) => {

                        let user = this.basicData.userListComplete.find(userNow => userNow._id === otherUser)

                        //Si el assignedTo es Zoco hay que mirar el subdominio y si no lo hay sacarlo, sino que tenga canAssignTo
                        if (this.basicData.userLogged && this.basicData.userLogged._id === '65cb57489c2c285441086a43')
                            if(user.label !== 'Usuario subdominio')
                                user = this.$utilities.obtainSubdomainUser(user._id, this.basicData.userListComplete)
                        else
                            if(!user.canAssignTo)
                                user = this.$utilities.obtainUserDownSubdomain(user._id, this.basicData.userListComplete, this.basicData.userSubdomain._id)


                        //Meto el usuario si no está ya en el array de usuarios
                        if (!users.find(u => u._id === user._id)){
                            users.push(user)
                            userSubdomainArrayToCheck.push(user._id)
                        }
                    })//Recorro los usuarios


                //ordeno los usuarios
                users.sort((a, b) => {
                    if (a.firstName === b.firstName) {
                        return a.lastName.localeCompare(b.lastName);
                    }
                    return a.firstName.localeCompare(b.firstName);
                });

                return users
            },
            getUsersNoFilter() {
                // elija cuál es su “fuente” real:
                const src =
                    (this.basicData && Array.isArray(this.basicData.userList) && this.basicData.userList.length)
                    ? this.basicData.userList
                    : (this.basicData && Array.isArray(this.basicData.userListComplete))
                        ? this.basicData.userListComplete
                        : [];

                // copia + orden
                const users = [...src].sort((a, b) => {
                    const an = `${a.firstName || ''} ${a.lastName || ''}`.trim();
                    const bn = `${b.firstName || ''} ${b.lastName || ''}`.trim();
                    return an.localeCompare(bn, 'es', { sensitivity: 'base' });
                });

                return users;
                },
            getMarketerFilterTitle(){

                let totalActives = 0;

                if (!this.filtersFiltered.marketers) return '0 comercializad.'


                this.filtersFiltered.marketers.forEach((marketer) => {
                    if (marketer.active) totalActives++;
                })

                return totalActives === this.filtersFiltered.marketers.length ? 'Todos' : (totalActives + ' comercializad/s')
            },
            getStatusFilterTitle(){
                let totalActives = 0;

                if (!this.filtersFiltered.statuses) return '0 estados.'


                this.filtersFiltered.statuses.forEach((status) => {
                    if (status.active) totalActives++;
                })

                return totalActives === this.filtersFiltered.statuses.length ? 'Todos' : (totalActives + ' estado/s')
            }
        }
    }
</script>

<style scoped>
.input-group {
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    background: #fff;
    padding: 6px 10px;
    display: flex;
    align-items: center;
}

.input-group input,
.input-group select,
.input-group textarea {
    border: none !important;
    outline: none !important;
    background: transparent;
    width: 100%;
    font-size: 13px;
    font-family: "Poppins", sans-serif;
}
</style>
