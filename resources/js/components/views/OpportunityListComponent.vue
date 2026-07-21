<template>
    <div v-on:click="hideCustomSelects">

        <!--Estilo movil-->
        <div class="mobile-item">
            <div class="content-white">
                <div class="sticky-header-mobile">
                    <div class="d-flex justify-between align-center opportunity-title">
                        <div class="text my-10" data-size="22" data-weight="700">Oportunidades</div>
                        <div class="custom-button my-auto mobile-create-btn" data-size="medium" data-bg="principal" data-weight="600"
                             v-if="!isReadOnly" v-on:click="actionLink('/opportunities/register')">
                            <i class="fas fa-plus"></i>
                        </div>
                    </div>
                    <div class="search-div d-flex">
                        <div class="search-bar w-100">
                            <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>
                            <input type="text" data-size="14" placeholder="Buscar una oportunidad..." v-model="searchOpportunityText">
                        </div>
                        <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>
                    </div>
                </div>

                <!--Filtros aplicados-->
                <div v-if="areFiltersApplied" class="d-flex column mobile-filters-applied my-10">
                    <div class="d-flex align-center justify-between">
                        <div class="d-flex align-center">
                            <i class="fa fas fa-lightbulb mr-10" data-color="rojo"></i>
                            <p class="text" data-color="rojo" data-size="14" data-weight="600">Filtros aplicados</p>
                        </div>
                        <div class="custom-button" data-size="small" data-bg="rojo" data-mode="translucent" @click="resetFilters">Borrar</div>
                    </div>
                </div>

                <!--Filtros-->
                <div class="d-flex column my-20">
                    <p class="text" data-size="13" data-weight="600" @click="seeFiltersMenu = !seeFiltersMenu">
                        {{ seeFiltersMenu ? 'Ocultar filtros' : 'Mostrar filtros' }}
                    </p>
                    <OpportunityFiltersComponent
                        v-if="seeFiltersMenu"
                        v-model:seeFiltersMenu="seeFiltersMenu"
                        v-model:filtersApplied="filtersApplied"
                        :filters="filters || {}"
                        :basicData="basicData"
                        @resetFilters="resetFilters"
                    />
                </div>

                <!--Cargando-->
                <div class="loading-indicator" v-if="isLoading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p data-size="14">Cargando oportunidades...</p>
                </div>

                <!--Listado-->
                <div class="d-flex column opportunity-list" v-if="!isLoading">
                    <div v-if="filteredOpportunities.length === 0" class="opacity-5">
                        <p class="text" data-size="15" data-weight="500">No se encontraron oportunidades</p>
                    </div>
                    <div v-for="(opportunity, opportunityKey) in filteredOpportunities" :key="opportunity._id" class="my-5">
                        <div class="d-flex align-center pointer" v-on:click="seeOpportunityInfo(opportunity)">
                            <div class="d-flex justify-center mr-10">
                                <div class="initials" data-size="17">{{ getInitials(getDisplayName(opportunity)) }}</div>
                            </div>
                            <div class="text ellipsis" data-weight="600">{{ getDisplayName(opportunity) }}</div>
                            <i v-if="hasDocuments(opportunity)" class="fa-solid fa-paperclip ml-5 my-auto" data-color="azul"></i>
                            <div class="deploy-btn ml-10" data-round="15"
                                 v-bind:class="{ 'selected': opportunitySelectedToSee._id === opportunity._id }">
                                <i class="fa-solid"
                                   v-bind:class="{ 'fa-chevron-down': opportunitySelectedToSee._id !== opportunity._id, 'fa-chevron-up': opportunitySelectedToSee._id === opportunity._id }"></i>
                            </div>
                        </div>
                        <div class="d-flex column" v-if="opportunitySelectedToSee._id === opportunity._id">
                            <div class="my-10">
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">CIF/NIF</div>
                                    <div class="text" data-size="13">{{ opportunity.CIF || '-' }}</div>
                                </div>
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Teléfono</div>
                                    <div class="text" data-size="13">{{ opportunity.phone || '-' }}</div>
                                </div>
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Email</div>
                                    <div class="text" data-size="13">{{ opportunity.email || '-' }}</div>
                                </div>
                            </div>
                            <div class="d-flex column" data-gap="6">
                                <div v-on:click="actionLink('/opportunities/' + opportunity._id)"
                                     class="custom-button" data-bg="principal" data-mode="outline" data-align="center" data-weight="700">
                                    <i class="fas fa-gear mr-5"></i> Ver detalle
                                </div>
                                <div v-on:click="toggleArchiveOpportunity(opportunity)" v-if="!isReadOnly"
                                     class="custom-button" data-bg="principal" data-mode="outline" data-align="center" data-weight="700">
                                    <i class="fa-regular fa-box-archive mr-5"></i>
                                    {{ filtersApplied.view === 'archived' ? 'Desarchivar' : 'Archivar' }}
                                </div>
                                <div v-on:click="deleteOpportunity(opportunity)" v-if="!isReadOnly"
                                     class="custom-button" data-bg="rojo" data-mode="outline" data-align="center" data-weight="700">
                                    <i class="fa-regular fa-trash mr-5"></i> Eliminar
                                </div>
                            </div>
                        </div>
                        <div class="separator my-10" v-if="opportunityKey < filteredOpportunities.length - 1"></div>
                    </div>
                </div>
            </div>
        </div>

        <!--Estilo pc-->
        <div class="desktop-item d-flex">
            <div class="content-white" v-bind:class="{ 'contact-selected': opportunitySelectedToSee !== '' }">

                <!--Header-->
                <div class="d-flex justify-between align-center">
                    <div class="text" data-size="30" data-weight="700">{{ $route.meta.title }}</div>
                    <div class="d-flex" data-gap="15">
                        <div class="d-flex" v-if="!isReadOnly">
                            <div class="custom-select no-hover" v-on:click.stop="isSeeingMassiveLoad = !isSeeingMassiveLoad"
                                v-bind:class="{ 'seeing': isSeeingMassiveLoad }">
                                <div class="custom-button" data-size="regular" data-bg="principal"><i class="far fa-plus"></i></div>
                                <div class="select-content form w-260-px">
                                    <p class="text mt-3" @click="$refs.inputExcelOpp.click()" style="cursor: pointer;">
                                        <i class="fa-solid fa-file-excel ml-4 mr-10"></i>Carga masiva
                                    </p>
                                    <input type="file" ref="inputExcelOpp" style="display:none" accept=".xls,.xlsx,.csv" @change="pickupDumpFileOpp" />
                                    <a class="text" href="/assets/templates/opportunities.xlsx" download="Plantilla_Oportunidades.xlsx">
                                        <i class="fas fa-file-arrow-down ml-4 mr-10"></i>Descargar plantilla
                                    </a>
                                    <p class="text mt-3" @click="exportOpportunities" style="cursor: pointer;">
                                        <i class="fas fa-file-export ml-4 mr-10"></i>Exportar oportunidades
                                    </p>
                                    <p class="text mt-3" v-if="basicData.userLogged._id === '65cb57489c2c285441086a43'" @click="importarCargacar" style="cursor: pointer;">
                                        <i class="fas fa-cloud-arrow-down ml-4 mr-10"></i>Importar Cargacar
                                    </p>
                                    <p class="text mt-3" v-if="basicData.userLogged._id === '65cb57489c2c285441086a43'" @click="importarFacebook" style="cursor: pointer;">
                                        <i class="fas fa-cloud-arrow-down ml-4 mr-10"></i>Importar Facebook
                                    </p>
                                    <!-- <p class="text mt-3" @click="getEmailsToMassive" style="cursor: pointer;">
                                        <i class="fas fa-paper-plane-top ml-4 mr-10"></i>Enviar correo masivo
                                    </p> -->
                                </div>
                            </div>
                        </div>
                        <div class="custom-button" data-size="regular" data-bg="principal"
                            v-on:click="actionLink('/opportunities/register')" v-if="!isReadOnly">
                            Añadir oportunidad
                        </div>
                    </div>
                </div>

                <!-- Resumen -->
                <div class="d-flex justify-between f-wrap mt-10" data-gap="20">
                    <div class="dashboard-card">
                        <div class="icon"><i class="far fa-lightbulb"></i></div>
                        <div class="info">
                            <p class="title">Oportunidades</p>
                            <p class="value">{{ opportunitiesDashboardSummary.total }}</p>
                            <div class="separator my-10"></div>
                            <template v-if="basicData?.userSubdomain?._id === '65cb57489c2c285441086a43'">
                                <div class="d-flex justify-between">
                                    <p class="text" data-size="11" style="opacity:.55">Comisiones</p>
                                    <p class="text" data-size="11" data-weight="700">{{ formatCommissionMoney(opportunitiesDashboardSummary.totalCommissions) }} €</p>
                                </div>
                            </template>
                            <template v-if="opportunitiesDashboardSummary.totalEvCharger > 0">
                                <div class="d-flex justify-between mt-3">
                                    <p class="text" data-size="11" style="opacity:.55">Cargadores</p>
                                    <p class="text" data-size="11" data-weight="700">{{ formatCommissionMoney(opportunitiesDashboardSummary.totalEvCharger) }} €</p>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="icon"><i class="far fa-circle-check"></i></div>
                        <div class="info">
                            <p class="title">Aceptadas</p>
                            <p class="value">{{ opportunitiesDashboardSummary.accepted }}</p>
                            <div class="separator my-10"></div>
                            <template v-if="basicData?.userSubdomain?._id === '65cb57489c2c285441086a43'">
                                <div class="d-flex justify-between">
                                    <p class="text" data-size="11" style="opacity:.55">Zoco</p>
                                    <p class="text" data-size="11" data-weight="700">{{ formatCommissionMoney(opportunitiesDashboardSummary.acceptedCommissions.zoco) }} €</p>
                                </div>
                                <div class="d-flex justify-between mt-3">
                                    <p class="text" data-size="11" style="opacity:.55">Agentes</p>
                                    <p class="text" data-size="11" data-weight="700">{{ formatCommissionMoney(opportunitiesDashboardSummary.acceptedCommissions.agents) }} €</p>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="icon"><i class="far fa-clock"></i></div>
                        <div class="info">
                            <p class="title">En seguimiento</p>
                            <p class="value">{{ opportunitiesDashboardSummary.followUp }}</p>
                            <div class="separator my-10"></div>
                            <template v-if="basicData?.userSubdomain?._id === '65cb57489c2c285441086a43'">
                                <div class="d-flex justify-between">
                                    <p class="text" data-size="11" style="opacity:.55">Zoco</p>
                                    <p class="text" data-size="11" data-weight="700">{{ formatCommissionMoney(opportunitiesDashboardSummary.followUpCommissions.zoco) }} €</p>
                                </div>
                                <div class="d-flex justify-between mt-3">
                                    <p class="text" data-size="11" style="opacity:.55">Agentes</p>
                                    <p class="text" data-size="11" data-weight="700">{{ formatCommissionMoney(opportunitiesDashboardSummary.followUpCommissions.agents) }} €</p>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="icon"><i class="far fa-circle-xmark"></i></div>
                        <div class="info">
                            <p class="title">Fallidas / falsas</p>
                            <p class="value">{{ opportunitiesDashboardSummary.failed }}</p>
                            <div class="separator my-10"></div>
                            <template v-if="basicData?.userSubdomain?._id === '65cb57489c2c285441086a43'">
                                <div class="d-flex justify-between">
                                    <p class="text" data-size="11" style="opacity:.55">Zoco</p>
                                    <p class="text" data-size="11" data-weight="700">{{ formatCommissionMoney(opportunitiesDashboardSummary.failedCommissions.zoco) }} €</p>
                                </div>
                                <div class="d-flex justify-between mt-3">
                                    <p class="text" data-size="11" style="opacity:.55">Agentes</p>
                                    <p class="text" data-size="11" data-weight="700">{{ formatCommissionMoney(opportunitiesDashboardSummary.failedCommissions.agents) }} €</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!--Barra filtros + búsqueda-->
                <div class="mt-30 select-line">
                    <div v-if="areFiltersApplied" class="d-flex filters-on relPos">
                        <i class="fa fas fa-lightbulb my-auto" data-color="rojo"></i>
                        <p class="my-auto mx-15" data-color="rojo">Filtros aplicados</p>
                        <div class="custom-button" data-size="small" data-bg="rojo" data-mode="translucent" @click="resetFilters">Borrar filtros</div>
                    </div>
                    <div class="before-search">
                        <div class="custom-button" data-size="small" data-bg="azul" data-mode="translucent"
                            v-on:click="seeFiltersMenu = !seeFiltersMenu">{{ seeFiltersMenu ? 'Ocultar' : 'Mostrar' }} filtros</div>
                    </div>
                    <div class="search-div d-flex">
                        <div class="search-bar w-100">
                            <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>
                            <input type="text" placeholder="Buscar una oportunidad..." v-model="searchOpportunityText">
                        </div>
                        <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>
                    </div>
                </div>

                <!--Header tabla-->
                <div class="contact header-card mt-30"
                     style="display:grid; grid-template-columns: 28px 1.4fr 1fr 0.9fr 1fr 1.2fr 0.8fr 0.8fr 0.9fr 0.9fr 80px; align-items:center; gap:8px; padding: 0 12px;">
                    <div class="custom-checkbox pointer" @click.stop="toggleSelectAllOpportunities"
                         :class="{ 'selected': areAllSelected && filteredOpportunities.length > 0 }"></div>
                    <div class="d-flex column pointer" style="min-width:0" @click.stop="selectNewOrderType('name')">
                        <div class="d-flex text">
                            <p class="text mr-5" data-weight="600">Nombre</p>
                            <i class="fas my-auto" :class="filtersApplied.sortBy === 0 ? 'fa-sort-down' : filtersApplied.sortBy === 1 ? 'fa-sort-up' : 'fa-sort'"></i>
                        </div>
                    </div>
                    <div class="d-flex text" style="min-width:0"><p class="text" data-weight="600">Agente</p></div>
                    <div class="d-flex column" style="min-width:0"><p class="text" data-weight="600">CIF / NIF</p></div>
                    <div class="d-flex column pointer" style="min-width:0" @click.stop="selectNewOrderType('email')">
                        <div class="d-flex text">
                            <p class="text mr-5" data-weight="600">Email</p>
                            <i class="fas my-auto" :class="filtersApplied.sortBy === 2 ? 'fa-sort-down' : filtersApplied.sortBy === 3 ? 'fa-sort-up' : 'fa-sort'"></i>
                        </div>
                        <p class="text" data-size="10">Teléfono</p>
                    </div>
                    <div class="d-flex text" style="min-width:0"><p class="text" data-weight="600">CUPS / Tipo</p></div>
                    <div class="d-flex text" style="min-width:0"><p class="text" data-weight="600">Tarifa</p></div>
                    <div class="d-flex text" style="min-width:0"><p class="text" data-weight="600">Comerciali.</p></div>
                    <div class="d-flex column pointer" style="min-width:0" @click.stop="selectNewOrderType('status')">
                        <div class="d-flex text">
                            <p class="text mr-5" data-weight="600">Estado</p>
                            <i class="fas my-auto" :class="filtersApplied.sortBy === 4 ? 'fa-sort-down' : filtersApplied.sortBy === 5 ? 'fa-sort-up' : 'fa-sort'"></i>
                        </div>
                    </div>
                    <div class="d-flex column pointer" style="min-width:0" @click.stop="selectNewOrderType('createdAt')">
                        <div class="d-flex text">
                            <p class="text mr-5" data-weight="600">{{ isOnexSubdomain ? 'Fec. creación' : 'Últ. actividad' }}</p>
                            <i class="fas my-auto" :class="filtersApplied.sortBy === 6 ? 'fa-sort-down' : filtersApplied.sortBy === 7 ? 'fa-sort-up' : 'fa-sort'"></i>
                        </div>
                    </div>
                    <div class="d-flex" v-if="!isReadOnly && opportunitiesSelected.length > 0">
                        <div class="mr-10 text pointer" @click.stop="toggleArchiveSelectedOpportunities()">
                            <i class="far" :class="filtersApplied.view === 'archived' ? 'fa-box-open' : 'fa-box-archive'"></i>
                        </div>
                        <div class="text pointer" data-color="rojo" @click.stop="deleteAllSelectedOpportunities()">
                            <i class="far fa-trash"></i>
                        </div>
                    </div>
                    <div v-else></div>
                </div>

                <div class="separator my-10"></div>

                <!--Loader-->
                <div v-if="isLoading">
                    <div class="d-flex column" v-for="i of 10" :key="i">
                        <div class="contact pointer"
                             style="display:grid; grid-template-columns: 28px 1.4fr 1fr 0.9fr 1fr 1.2fr 0.8fr 0.8fr 0.9fr 0.9fr 80px; gap:8px; padding:0 12px;">
                            <div class="loading h-20-px mx-10" v-for="j of 11" :key="j"></div>
                        </div>
                        <div class="separator my-10"></div>
                    </div>
                </div>

                <!--Filas-->
                <div v-else-if="filteredOpportunities && filteredOpportunities.length > 0">
                    <div v-for="opportunity in filteredOpportunities" :key="opportunity._id">
                        <div class="contact pointer"
                             style="display:grid; grid-template-columns: 28px 1.4fr 1fr 0.9fr 1fr 1.2fr 0.8fr 0.8fr 0.9fr 0.9fr 80px; align-items:center; gap:8px; padding:0 12px;">
                            <div class="custom-checkbox pointer"
                                 @click.stop="toggleSelectOpportunity(opportunity)"
                                 :class="{ 'selected': opportunitiesSelected.includes(opportunity._id) }"></div>
                            <a class="d-flex align-center" style="min-width:0; overflow:hidden; text-decoration:none"
                               @click.prevent="goToOpportunityDetail(opportunity)"
                               :href="'/opportunities/' + opportunity._id" rel="noopener">
                                <p class="text" data-color="azul" data-weight="600"
                                   style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; min-width:0">
                                    {{ getDisplayName(opportunity) }}
                                </p>
                                <i v-if="hasDocuments(opportunity)" class="fa-solid fa-paperclip ml-5" data-color="azul" style="flex-shrink:0"></i>
                            </a>
                            <div class="d-flex column" style="min-width:0; overflow:hidden">
                                <p class="text ellipsis" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ getAgentName(opportunity) }}</p>
                            </div>
                            <div class="d-flex column" style="min-width:0; overflow:hidden">
                                <p class="text ellipsis" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ opportunity.CIF || '-' }}</p>
                            </div>
                            <div class="d-flex column" style="min-width:0; overflow:hidden">
                                <p class="text ellipsis" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ opportunity.email || '-' }}</p>
                                <p class="text ellipsis" data-size="11" style="opacity:0.6; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ opportunity.phone || '-' }}</p>
                            </div>
                            <div class="d-flex column" style="min-width:0; overflow:hidden">
                                <p class="text ellipsis" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ opportunity.order && opportunity.order.CUPS ? opportunity.order.CUPS : '-' }}</p>
                                <p class="text ellipsis" data-size="11" style="opacity:0.6; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ getProductTypeLabel(opportunity.order?.productType) }}</p>
                            </div>
                            <div class="d-flex column" style="min-width:0; overflow:hidden">
                                <p class="text ellipsis" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ opportunity.order && opportunity.order.fee ? opportunity.order.fee : '-' }}</p>
                            </div>
                            <div class="d-flex column" style="min-width:0; overflow:hidden">
                                <p class="text ellipsis" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ opportunity.order && opportunity.order.marketer ? opportunity.order.marketer : '-' }}</p>
                            </div>
                            <div class="d-flex column" style="min-width:0; overflow:hidden">
                                <div v-if="getOpportunityStatus(opportunity) !== '-'"
                                     class="custom-button w-fit-content" data-size="small" data-bg="azul" data-mode="translucent"
                                     :style="getOpportunityStatusStyle(getOpportunityStatus(opportunity))">
                                    <p class="ellipsis" style="max-width:90px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#012C68">
                                        {{ getOpportunityStatus(opportunity) }}
                                    </p>
                                </div>
                                <p v-else class="text" style="opacity:0.4">-</p>
                            </div>
                            <div class="d-flex column" style="min-width:0; overflow:hidden">
                                <p class="text ellipsis" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ isOnexSubdomain ? getOpportunityCreatedDate(opportunity) : getOpportunityTouchedDate(opportunity) }}</p>
                            </div>
                            <div class="d-flex" v-if="!isReadOnly">
                                <div class="mx-5 text pointer" @click="goToOpportunityDetail(opportunity)"><i class="far fa-eye"></i></div>
                                <div class="mx-5 text pointer" @click.stop="toggleArchiveOpportunity(opportunity)">
                                    <i class="far" :class="filtersApplied.view === 'archived' ? 'fa-box-open' : 'fa-box-archive'"></i>
                                </div>
                                <div class="mx-5 text pointer" data-color="rojo" @click.stop="deleteOpportunity(opportunity)"><i class="far fa-trash"></i></div>
                            </div>
                        </div>
                        <div class="separator my-10"></div>
                    </div>
                </div>

                <div class="opacity-5" v-else data-align="center">
                    ¡No hay ninguna oportunidad {{ filtersApplied.view === 'archived' ? 'archivada' : 'sin archivar' }}!
                </div>

                <!--Flotante filtros-->
                <OpportunityFiltersComponent
                    v-if="seeFiltersMenu"
                    v-model:seeFiltersMenu="seeFiltersMenu"
                    v-model:filtersApplied="filtersApplied"
                    :filters="filters || {}"
                    :basicData="basicData"
                    @resetFilters="resetFilters"
                />
            </div>

            <!--Info lateral-->
            <div v-if="opportunitySelectedToSee !== ''" class="info-content">
                <div class="d-flex">
                    <div class="initials" data-size="25" v-if="opportunitySelectedToSee">
                        {{ getInitials(getDisplayName(opportunitySelectedToSee)) }}
                    </div>
                    <p class="ellipsis" data-color="azul" data-weight="700" data-size="20">{{ getDisplayName(opportunitySelectedToSee) }}</p>
                </div>
                <div class="my-20">
                    <p class="text" data-size="20" data-weight="700">Datos de oportunidad</p>
                    <div class="separator my-0"></div>
                    <div class="d-flex justify-between my-10">
                        <div class="text w-50" data-weight="600">Nombre</div>
                        <div class="text w-50 ellipsis" data-align="right" data-weight="300">
                            {{ opportunitySelectedToSee.name }} {{ opportunitySelectedToSee.surname }}
                            {{ !(opportunitySelectedToSee.surname || opportunitySelectedToSee.name) ? '-' : '' }}
                        </div>
                    </div>
                    <div class="d-flex justify-between my-10">
                        <div class="text w-50" data-weight="600">Estado</div>
                        <div class="text w-50 ellipsis" data-align="right" data-weight="300">{{ getOpportunityStatus(opportunitySelectedToSee) }}</div>
                    </div>
                    <div class="d-flex justify-between my-10">
                        <div class="text w-50" data-weight="600">Sector</div>
                        <div class="text w-50 ellipsis" data-align="right" data-weight="300">
                            {{ opportunitySelectedToSee.sector?.title === 'Personalizado' ? opportunitySelectedToSee.sector?.custom : (opportunitySelectedToSee.sector?.title || '-') }}
                        </div>
                    </div>
                    <div class="d-flex justify-between my-10">
                        <div class="text w-50" data-weight="600">Fuente</div>
                        <div class="text w-50 ellipsis" data-align="right" data-weight="300">
                            {{ opportunitySelectedToSee.source?.title === 'Personalizado' ? opportunitySelectedToSee.source?.custom : (opportunitySelectedToSee.source?.title || '-') }}
                        </div>
                    </div>
                </div>
                <div v-if="opportunitySelectedToSee.createdBy">
                    <p class="text opacity-5" data-size="13" data-weight="600"><i class="fas fa-user mr-5"></i> Creador de la oportunidad</p>
                    <div class="separator my-0"></div>
                    <div class="d-flex justify-center mr-20 my-10 w-100">
                        <div class="initials verySmall mr-20 my-auto" data-style="initials"
                            v-bind:class="{ image: opportunitySelectedToSee.createdBy.profileImage }">
                            <img :src="'/assets/profile_images/' + opportunitySelectedToSee.createdBy.profileImage" class="profile-image">
                        </div>
                        <div class="d-flex column my-auto">
                            <p class="text opacity-5" data-weight="600" data-size="14">{{ opportunitySelectedToSee.createdBy.firstName }}</p>
                        </div>
                        <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="tiny"
                            v-on:click="actionLink((this.basicData.userLogged._id === opportunitySelectedToSee.createdBy._id ? '/profile' : `/users/${opportunitySelectedToSee.createdBy._id}`))">
                            <i class="fas fa-arrow-right-long"></i>
                        </div>
                    </div>
                </div>
                <div class="separator my-10"></div>
                <div class="mr-auto">
                    <div v-on:click="actionLink('/opportunities/' + opportunitySelectedToSee._id)"
                        class="custom-button mr-10" data-size="medium" data-bg="azul" data-mode="outline" data-color="azul">Editar</div>
                </div>
            </div>
        </div>

        <div class="loader-box" v-if="isLoadingMassiveLoad"><div class="loader"></div></div>
    </div>
</template>

<script>
import { useDebounceFn } from '@vueuse/core';
import OpportunityFiltersComponent from "@/components/items/OpportunityFiltersComponent.vue";

export default {
    name: "opportunitiesListComponent",
    components: { OpportunityFiltersComponent },
    props: ['basicData'],
    data() {
        const defaultFilters = {
            agents: [], productTypes: [], statuses: [], marketers: [],
            tariffs: [], products: [], dates: { start: null, end: null },
            view: 'all', originTypes: [], sortBy: 7,
        }
        let restoredFilters = null
        const savedFilters = sessionStorage.getItem('opportunityFilters')
        if (savedFilters) { try { restoredFilters = JSON.parse(savedFilters) } catch {} }
        const savedSearch = sessionStorage.getItem('opportunitySearch')
        return {
            isLoadingMassiveLoad: false,
            fetchOpportunitiesRequestId: 0,
            isLoading: true,
            seeFiltersMenu: false,
            isSeeingMassiveLoad: false,

            opportunities: { notArchived: [], archived: [] },
            filters: null,
            searchOpportunityText: savedSearch || '',

            filtersApplied: restoredFilters || defaultFilters,

            opportunitySelectedToSee: '',
            opportunitiesSelected: [],

            zocoSubdomainUserId: '65cb57489c2c285441086a43',
            opportunityStatusOptions: [
                'Pendiente', 'Contactado', 'Contactado Whatssap', 'Mensaje enviado',
                'No contesta', 'Presupuesto Enviado', 'Pre. Bot', 'En seguimiento',
                'Aceptado', 'Fallido', 'Falso', 'Repetido', 'Sin estado', 'Checklist terminado', 'Stand-by', 'Perdidos'
            ],

            debouncedFetch: null,
        }
    },
    created() {
        this.debouncedFetch = useDebounceFn(() => this.fetchAllOpportunities(), 300)
    },
    watch: {
        'basicData.userLogged': {
            immediate: true,
            deep: true,
            handler(user) {
                if (!user) return
                this.fetchAllOpportunities()
                this.fetchOpportunityFilters()
            }
        },
        searchOpportunityText() {
            sessionStorage.setItem('opportunitySearch', this.searchOpportunityText)
            this.debouncedFetch()
        },
        filtersApplied: {
            deep: true,
            handler(val) {
                sessionStorage.setItem('opportunityFilters', JSON.stringify(val))
                this.debouncedFetch()
            }
        }
    },
    methods: {
        async fetchAllOpportunities() {
            if (!this.basicData?.userLogged?._id) return

            const requestId = ++this.fetchOpportunitiesRequestId
            this.isLoading = true

            // Convertir filtersApplied al formato del backend (solo enviar filtros no vacíos)
            const filters = {}
            if (this.filtersApplied.agents.length)       filters.agents       = this.filtersApplied.agents
            if (this.filtersApplied.statuses.length)     filters.statuses     = this.filtersApplied.statuses
            if (this.filtersApplied.marketers.length)    filters.marketers    = this.filtersApplied.marketers
            if (this.filtersApplied.tariffs.length)      filters.tariffs      = this.filtersApplied.tariffs
            if (this.filtersApplied.products.length)     filters.products     = this.filtersApplied.products
            if (this.filtersApplied.productTypes.length) filters.productTypes = this.filtersApplied.productTypes
            if (this.filtersApplied.originTypes.length)  filters.originTypes  = this.filtersApplied.originTypes
            if (this.filtersApplied.dates?.start || this.filtersApplied.dates?.end) {
                filters.dates = this.filtersApplied.dates
            }

            try {
                const response = await axios.post(
                    `/api/opportunities/index/${this.basicData.userLogged._id}`,
                    {
                        userList:              JSON.stringify(this.basicData.userList),
                        filters,
                        sortType:              this.filtersApplied.sortBy,
                        searchOpportunityText: this.searchOpportunityText,
                    }
                )

                if (requestId !== this.fetchOpportunitiesRequestId) return

                this.opportunities = response.data.opportunities || { notArchived: [], archived: [] }

            } catch (err) {
                if (requestId === this.fetchOpportunitiesRequestId) console.error(err)
            } finally {
                if (requestId === this.fetchOpportunitiesRequestId) this.isLoading = false
            }
        },

        async fetchOpportunityFilters() {
            if (!this.basicData?.userLogged?._id) return

            try {
                const res = await axios.post(
                    `/api/opportunities/indexFilters/${this.basicData.userLogged._id}`,
                    { userList: JSON.stringify(this.basicData.userList) }
                )
                this.filters = {
                    agents:       (res.data.agents       || []).filter(a => a && a.name),
                    statuses:     this.opportunityStatusOptions,
                    marketers:    (res.data.marketers    || []).filter(Boolean).sort(),
                    productTypes: (res.data.productTypes || []).filter(Boolean).sort(),
                    tariffs:      (res.data.tariffs      || []).filter(Boolean).sort(),
                    products:     (res.data.products     || []).sort(),
                    originTypes:  (res.data.originTypes  || []),
                }
            } catch (err) {
                console.error(err)
            }
        },

        resetFilters() {
            this.filtersApplied = {
                agents:       [],
                productTypes: [],
                statuses:     [],
                marketers:    [],
                tariffs:      [],
                products:     [],
                dates:        { start: null, end: null },
                view:         'all',
                originTypes:  [],
                sortBy:       7,
            }
            this.searchOpportunityText = ''
            sessionStorage.removeItem('opportunitySearch')
        },

        resetSearch() {
            this.searchOpportunityText = ''
            sessionStorage.removeItem('opportunitySearch')
            this.debouncedFetch()
        },

        selectNewOrderType(type) {
            const cur = this.filtersApplied.sortBy
            switch (type) {
                case 'name':     this.filtersApplied.sortBy = cur === 0 ? 1 : cur === 1 ? 7 : 0; break
                case 'email':    this.filtersApplied.sortBy = cur === 2 ? 3 : cur === 3 ? 7 : 2; break
                case 'status':   this.filtersApplied.sortBy = cur === 4 ? 5 : cur === 5 ? 7 : 4; break
                case 'createdAt': this.filtersApplied.sortBy = cur === 6 ? 7 : 6; break
            }
        },

        hideCustomSelects() {
            this.isSeeingMassiveLoad = false
        },

        goToOpportunityDetail(opportunity) {
            const ids = this.filteredOpportunities.map(o =>
                typeof o._id === 'object' ? (o._id.$oid || String(o._id)) : String(o._id)
            )
            sessionStorage.setItem('opportunityNavIds', JSON.stringify(ids))
            this.$router.push({
                path: '/opportunities/' + opportunity._id,
                state: { opportunityPreview: { _id: opportunity._id, name: opportunity.name || '', CIF: opportunity.CIF || '', phone: opportunity.phone || '', email: opportunity.email || '', status: opportunity.status || '', usersIds: opportunity.usersIds || [], billingInfo: opportunity.billingInfo || {}, contact: opportunity.contact || {}, observations: opportunity.observations || '', position: opportunity.position || '', website: opportunity.website || '', sector: opportunity.sector || '', source: opportunity.source || '', order: { productType: opportunity.order?.productType || '', marketer: opportunity.order?.marketer || '', fee: opportunity.order?.fee || '', product: opportunity.order?.product || '', CUPS: opportunity.order?.CUPS || '', province: opportunity.order?.province || '', town: opportunity.order?.town || '', direc: opportunity.order?.direc || '', zip: opportunity.order?.zip || '', name: opportunity.order?.name || '', extras: [] }, customFields: [], docs: [] } }
            })
        },

        getDisplayName(opp) {
            const name = (opp?.name || '').trim()
            const orderName = (opp?.order?.name || '').trim()
            if (name !== '') return name
            if (orderName !== '') return opp?.order?.CUPS ? `${orderName} - ${opp.order.CUPS.slice(-6)}` : orderName
            return 'Sin nombre'
        },

        getAgentName(opportunity) {
            if (!opportunity.usersIds || opportunity.usersIds.length === 0) return '-'
            const id = typeof opportunity.usersIds[0] === 'object'
                ? (opportunity.usersIds[0].$oid || String(opportunity.usersIds[0]))
                : String(opportunity.usersIds[0])
            const user = (this.basicData.userList || []).find(u => String(u._id) === id)
            return user ? (user.firstName + ' ' + user.lastName) : '-'
        },

        hasDocuments(opportunity) {
            return Array.isArray(opportunity?.docs) && opportunity.docs.length > 0
        },

        getOpportunityStatus(opportunity) {
            if (!opportunity.status || opportunity.status === '') return 'Sin estado'
            if (typeof opportunity.status === 'string') return opportunity.status.trim()
            return opportunity.status?.title?.trim() || 'Sin estado'
        },

        getOpportunityStatusStyle(status) {
            const styles = {
                'Pendiente':              { backgroundColor: '#FEF3C7', color: '#111', border: '1px solid #FCD34D' },
                'Contactado':             { backgroundColor: '#DBEAFE', color: '#111', border: '1px solid #93C5FD' },
                'Contactado Whatssap':    { backgroundColor: '#CCFBF1', color: '#111', border: '1px solid #5EEAD4' },
                'Contactado WhatsApp':    { backgroundColor: '#CCFBF1', color: '#111', border: '1px solid #5EEAD4' },
                'Mensaje enviado':        { backgroundColor: '#CFFAFE', color: '#111', border: '1px solid #67E8F9' },
                'No contesta':            { backgroundColor: '#FFEDD5', color: '#111', border: '1px solid #FDBA74' },
                'Presupuesto Enviado':    { backgroundColor: '#EDE9FE', color: '#111', border: '1px solid #C4B5FD' },
                'Pre. Bot':               { backgroundColor: '#ECFCCB', color: '#365314', border: '1px solid #BEF264' },
                'En seguimiento':         { backgroundColor: '#E0E7FF', color: '#111', border: '1px solid #A5B4FC' },
                'Aceptado':               { backgroundColor: '#DCFCE7', color: '#111', border: '1px solid #86EFAC' },
                'Fallido':                { backgroundColor: '#FEE2E2', color: '#111', border: '1px solid #FCA5A5' },
                'Falso':                  { backgroundColor: '#FEE2E2', color: '#111', border: '1px solid #FCA5A5' },
                'Repetido':               { backgroundColor: '#FCE7F3', color: '#111', border: '1px solid #F9A8D4' },
                'Sin estado':             { backgroundColor: '#E5E7EB', color: '#111', border: '1px solid #9CA3AF' },
                'Checklist terminado': { backgroundColor: '#D1FAE5', color: '#111', border: '1px solid #34D399' },
            }
            return styles[String(status || 'Sin estado').trim()] || { backgroundColor: '#F3F4F6', color: '#111', border: '1px solid #D1D5DB' }
        },

        getOpportunityTouchedTimestamp(opportunity) {
            const parse = value => {
                if (!value) return 0
                if (typeof value === 'object') {
                    if (value.$date) value = value.$date
                    else if (value.sec) return Number(value.sec) * 1000
                }
                const ts = new Date(value).getTime()
                return Number.isFinite(ts) ? ts : 0
            }
            return parse(opportunity?.updatedAt) || parse(opportunity?.updated_at) || parse(opportunity?.createdAt) || parse(opportunity?.created_at)
        },

        getOpportunityTouchedDate(opportunity) {
            const ts = this.getOpportunityTouchedTimestamp(opportunity)
            return ts ? new Date(ts).toLocaleDateString('es-ES') : '-'
        },

        // Fecha de creación (para onex se muestra esta en vez de la última actividad).
        getOpportunityCreatedDate(opportunity) {
            const parse = value => {
                if (!value) return 0
                if (typeof value === 'object') {
                    if (value.$date) value = value.$date
                    else if (value.sec) return Number(value.sec) * 1000
                }
                const ts = new Date(value).getTime()
                return Number.isFinite(ts) ? ts : 0
            }
            const ts = parse(opportunity?.createdAt) || parse(opportunity?.created_at)
            return ts ? new Date(ts).toLocaleDateString('es-ES') : '-'
        },

        getInitials(name) {
            if (!name) return ''
            const parts = name.split(/\s+/)
            let initials = parts[0][0] || ''
            if (parts[1]) initials += parts[1][0]
            return initials
        },

        seeOpportunityInfo(opportunity) {
            this.opportunitySelectedToSee = this.opportunitySelectedToSee._id === opportunity._id ? '' : opportunity
        },

        toggleSelectAllOpportunities() {
            const ids = this.filteredOpportunities.map(o => o._id)
            const allSelected = ids.length > 0 && ids.every(id => this.opportunitiesSelected.includes(id))
            if (allSelected) {
                this.opportunitiesSelected = this.opportunitiesSelected.filter(id => !ids.includes(id))
            } else {
                this.opportunitiesSelected = [...new Set([...this.opportunitiesSelected, ...ids])]
            }
        },

        toggleSelectOpportunity(opportunity) {
            if (this.opportunitiesSelected.includes(opportunity._id)) {
                this.opportunitiesSelected.splice(this.opportunitiesSelected.indexOf(opportunity._id), 1)
            } else {
                this.opportunitiesSelected.push(opportunity._id)
            }
        },

        async toggleArchiveOpportunity(opportunity) {
            const isArchiving = this.filtersApplied.view !== 'archived'
            await axios.post(`/api/opportunities/toggleArchiveOpportunity/${opportunity._id}`, { isForArchiving: isArchiving })
                .then(() => {
                    this.fetchAllOpportunities()
                    if (this.opportunitySelectedToSee._id === opportunity._id) this.opportunitySelectedToSee = ''
                })
                .catch(err => console.log(err))
        },

        async toggleArchiveSelectedOpportunities() {
            const isArchiving = this.filtersApplied.view !== 'archived'
            await axios.post(`/api/opportunities/toggleArchiveSelectedOpportunities`, { idsToToggle: this.opportunitiesSelected, isForArchiving: isArchiving })
                .then(() => {
                    this.opportunitiesSelected = []
                    this.fetchAllOpportunities()
                    this.opportunitySelectedToSee = ''
                })
                .catch(err => console.log(err))
        },

        deleteAllSelectedOpportunities() {
            Swal.fire({
                icon: 'warning', title: '¿Estas seguro?', text: 'Si sigues con esta acción no podras revertirla',
                confirmButtonText: 'Sí', showConfirmButton: true, cancelButtonText: 'No', showCancelButton: true
            }).then(res => {
                if (res.isConfirmed) {
                    axios.post('/api/opportunities/deleteAllSelected', { idsToRemove: this.opportunitiesSelected })
                        .then(() => { this.opportunitiesSelected = []; this.fetchAllOpportunities(); this.opportunitySelectedToSee = '' })
                        .catch(err => console.log(err))
                }
            })
        },

        deleteOpportunity(opportunity) {
            Swal.fire({
                icon: 'warning', title: '¿Estas seguro?', text: 'Si sigues con esta acción no podras revertirla',
                confirmButtonText: 'Sí', showConfirmButton: true, cancelButtonText: 'No', showCancelButton: true
            }).then(res => {
                if (res.isConfirmed) {
                    axios.delete(`/api/opportunities/${opportunity['_id']}`)
                        .then(() => {
                            this.fetchAllOpportunities()
                            if (this.opportunitySelectedToSee._id === opportunity._id) this.opportunitySelectedToSee = ''
                        })
                        .catch(err => console.log(err))
                }
            })
        },

        getProductTypeLabel(code) {
            const map = {
                'cl': 'Luz', 'cg': 'Gas', 'cd': 'Dual',
                'ct': 'Telefonía', 'sa': 'Alarmas', 'a': 'Autoconsumo',
                'bc': 'Bat. Condensadores', 'ce': 'Coche eléctrico',
                'c': 'Contador', 'i': 'Iluminación', 'sp': 'Sin tipo',
            }
            return code ? (map[code] || code) : '-'
        },

        actionLink(route) { this.$router.push(route) },

        removeAccents(str) { return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "") },

        getEmailsToMassive() {
            const all = [...(this.opportunities.notArchived || []), ...(this.opportunities.archived || [])]
            const emails = all.map(o => o.email).filter((e, i, s) => s.indexOf(e) === i && e !== '')
            localStorage.setItem('emailsTemporaly', JSON.stringify(emails))
            this.$router.push('/tools?section=massiveEmail&withFilters=true')
        },

        pickupDumpFileOpp(event) {
            const file = event.target.files[0]
            if (file) this.dumpOpportunities(file)
        },

        async dumpOpportunities(file) {
            this.hideCustomSelects()
            this.isLoadingMassiveLoad = true
            const formData = new FormData()
            formData.append('file', file)
            formData.append('userList', JSON.stringify(this.basicData.userList))
            formData.append('userSubdomain', JSON.stringify(this.basicData.userSubdomain))
            try {
                const res = await axios.post('/api/opportunities/dump', formData, { headers: { 'Content-Type': 'multipart/form-data' }, responseType: 'blob', validateStatus: s => s < 500 })
                const ct = (res.headers['content-type'] || '').toLowerCase()
                if (ct.includes('application/vnd.openxmlformats') || ct.includes('spreadsheetml')) {
                    const url = window.URL.createObjectURL(new Blob([res.data], { type: ct }))
                    const a = document.createElement('a'); a.href = url; a.download = 'opp_import_errores.xlsx'; a.click()
                    window.URL.revokeObjectURL(url)
                    Swal.fire({ icon: 'warning', title: 'Hay incidencias', html: 'La importación tiene errores.<br>Revisa el archivo descargado.' })
                    this.fetchAllOpportunities(); return
                }
                const text = await res.data.text()
                let data = {}; try { data = JSON.parse(text) } catch {}
                this.fetchAllOpportunities()
                const inserted = data?.summary?.inserted ?? 0, failed = data?.summary?.failed ?? 0
                if (failed === 0) {
                    Swal.fire({ icon: 'success', title: 'Importación completada', text: `Se han creado ${inserted} oportunidades correctamente.`, timer: 2000, timerProgressBar: true })
                } else {
                    Swal.fire({ icon: 'warning', title: 'Importación con incidencias', text: `${inserted} creadas, ${failed} con errores.` })
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error al importar', text: err?.message || 'Error desconocido.' })
            } finally {
                this.isLoadingMassiveLoad = false
                if (this.$refs?.inputExcelOpp) this.$refs.inputExcelOpp.value = null
            }
        },

        async exportOpportunities() {
            this.hideCustomSelects()
            this.isLoadingMassiveLoad = true
            try {
                const filters = {}
                if (this.filtersApplied.agents.length)       filters.agents       = this.filtersApplied.agents
                if (this.filtersApplied.statuses.length)     filters.statuses     = this.filtersApplied.statuses
                if (this.filtersApplied.marketers.length)    filters.marketers    = this.filtersApplied.marketers
                if (this.filtersApplied.tariffs.length)      filters.tariffs      = this.filtersApplied.tariffs
                if (this.filtersApplied.products.length)     filters.products     = this.filtersApplied.products
                if (this.filtersApplied.productTypes.length) filters.productTypes = this.filtersApplied.productTypes
                if (this.filtersApplied.dates?.start || this.filtersApplied.dates?.end) filters.dates = this.filtersApplied.dates

                const res = await axios.post('/api/opportunities/export', {
                    userList: JSON.stringify(this.basicData.userList),
                    filters,
                    sortType: this.filtersApplied.sortBy,
                    searchOpportunityText: this.searchOpportunityText,
                }, { responseType: 'blob' })
                const url = window.URL.createObjectURL(new Blob([res.data]))
                const a = document.createElement('a'); a.href = url
                a.download = `Oportunidades_${new Date().toISOString().slice(0,10)}.xlsx`; a.click()
                window.URL.revokeObjectURL(url)
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error al exportar', text: err?.message || 'Error desconocido.' })
            } finally {
                this.isLoadingMassiveLoad = false
            }
        },

        async importarCargacar() {
            this.hideCustomSelects(); this.isLoadingMassiveLoad = true
            try {
                const res = await axios.get('/api/scraping/confirmar')
                this.fetchAllOpportunities()
                const created = res.data.created ?? 0, skipped = res.data.skipped ?? 0
                Swal.fire({ icon: created > 0 ? 'success' : 'info', title: 'Importación completada', html: `<p><strong>${created}</strong> oportunidad${created === 1 ? '' : 'es'} creada${created === 1 ? '' : 's'}</p><p><strong>${skipped}</strong> saltada${skipped === 1 ? '' : 's'}</p>` })
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error al importar', text: err?.response?.data?.message || err?.message || 'Error desconocido' })
            } finally { this.isLoadingMassiveLoad = false }
        },

        async importarFacebook() {
            this.hideCustomSelects(); this.isLoadingMassiveLoad = true
            try {
                const res = await axios.post('/api/opportunities/meta/leads')
                await this.fetchAllOpportunities()
                const created = res.data.created ?? 0, skipped = res.data.skipped ?? 0
                Swal.fire({ icon: created > 0 ? 'success' : 'info', title: 'Importación Facebook completada', html: `<p><strong>${created}</strong> oportunidad${created === 1 ? '' : 'es'} creada${created === 1 ? '' : 's'}</p><p><strong>${skipped}</strong> lead${skipped === 1 ? '' : 's'} saltado${skipped === 1 ? '' : 's'}</p>`, width: 700 })
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error al importar Facebook', text: err?.response?.data?.message || err?.message || 'Error desconocido' })
            } finally { this.isLoadingMassiveLoad = false }
        },

        // --- Métodos de comisiones (preservados) ---
        parseCommissionNumber(value) {
            if (value === null || value === undefined || value === '') return 0
            if (typeof value === 'number') return Number.isFinite(value) ? value : 0
            const normalized = String(value).trim().replace(/\s/g, '').replace('€', '')
            if (!normalized) return 0
            const n = Number(normalized.includes(',') ? normalized.replace(/\./g, '').replace(',', '.') : normalized)
            return Number.isFinite(n) ? n : 0
        },
        getCommissionPartsFromBlock(commissions) {
            if (!commissions || Array.isArray(commissions)) return { zoco: 0, agents: 0, total: 0 }
            const zoco   = this.parseCommissionNumber(commissions.subdomain)
            const agents = Array.isArray(commissions.breakdown) ? commissions.breakdown.reduce((s, i) => s + this.parseCommissionNumber(i.commission), 0) : 0
            if (zoco > 0 || agents > 0) return { zoco, agents, total: zoco + agents }
            const el = this.getCommissionPartsFromBlock(commissions.electricity)
            const ga = this.getCommissionPartsFromBlock(commissions.gas)
            return { zoco: el.zoco + ga.zoco, agents: el.agents + ga.agents, total: el.total + ga.total }
        },
        getOpportunityCommissionParts(opportunity) { return this.getCommissionPartsFromBlock(opportunity?.order?.commissions) },
        getEvChargerBudgetTotal(opportunity) { const t = opportunity?.order?.evChargerBudget?.totals?.total; return typeof t === 'number' && isFinite(t) ? t : 0 },
        sumEvChargerBudget(opportunities) { return opportunities.reduce((s, o) => s + this.getEvChargerBudgetTotal(o), 0) },
        sumOpportunitiesCommissionParts(opportunities) {
            return opportunities.reduce((total, opp) => {
                const p = this.getOpportunityCommissionParts(opp)
                total.zoco += p.zoco; total.agents += p.agents; total.total += p.total
                return total
            }, { zoco: 0, agents: 0, total: 0 })
        },
        formatCommissionMoney(value) {
            return this.parseCommissionNumber(value).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        },
    },
    computed: {
        // Subdominios onex.
        isOnexSubdomain() {
            const ONEX_SUBDOMAIN_IDS = ['692da6aeaedb25b428042132', '6a47777b04ce48688d831e57']
            const id = this.basicData?.userSubdomain?._id
            return ONEX_SUBDOMAIN_IDS.includes(id?.$oid ?? id)
        },
        areFiltersApplied() {
            const f = this.filtersApplied
            return f.agents.length > 0 || f.productTypes.length > 0 || f.statuses.length > 0 ||
                   f.marketers.length > 0 || f.tariffs.length > 0 || f.products.length > 0 ||
                   !!(f.dates?.start || f.dates?.end) || f.view !== 'all' ||
                   f.originTypes.length > 0 || !!this.searchOpportunityText
        },

        filteredOpportunities() {
            let base = []
            if (this.filtersApplied.view === 'archived') {
                base = this.opportunities.archived || []
            } else if (this.filtersApplied.view === 'agenda') {
                base = this.opportunities.notArchived || []
            } else {
                base = [...(this.opportunities.notArchived || []), ...(this.opportunities.archived || [])]
            }

            // Ordenación por timestamp de actividad (sortBy 6 y 7)
            if (this.filtersApplied.sortBy === 6 || this.filtersApplied.sortBy === 7) {
                const desc = this.filtersApplied.sortBy === 7
                return [...base].sort((a, b) => {
                    const aTs = this.getOpportunityTouchedTimestamp(a)
                    const bTs = this.getOpportunityTouchedTimestamp(b)
                    return desc ? bTs - aTs : aTs - bTs
                })
            }

            return base
        },

        visibleOpportunities() { return this.filteredOpportunities },

        areAllSelected() {
            const ids = this.filteredOpportunities.map(o => o._id)
            return ids.length > 0 && ids.every(id => this.opportunitiesSelected.includes(id))
        },

        isReadOnly() {
            if (!this.basicData.userLogged) return true
            return this.basicData.userLogged.permissions.includes('READONLY')
        },

        opportunitiesDashboardSummary() {
            const opportunities = this.filteredOpportunities || []
            const normalizeStatus = s => this.removeAccents(String(s || '')).trim().toLowerCase()
            const followUpStatuses = ['pendiente','contactado','contactado whatssap','contactado whatsapp','mensaje enviado','no contesta','presupuesto enviado','en seguimiento','sin estado']
            const failedStatuses   = ['fallido','falso','repetido']

            const accepted  = opportunities.filter(o => normalizeStatus(this.getOpportunityStatus(o)) === 'aceptado')
            const followUp  = opportunities.filter(o => followUpStatuses.includes(normalizeStatus(this.getOpportunityStatus(o))))
            const failed    = opportunities.filter(o => failedStatuses.includes(normalizeStatus(this.getOpportunityStatus(o))))

            return {
                total:    opportunities.length,
                accepted: accepted.length,
                followUp: followUp.length,
                failed:   failed.length,
                totalCommissions:    this.sumOpportunitiesCommissionParts(opportunities),
                acceptedCommissions: this.sumOpportunitiesCommissionParts(accepted),
                followUpCommissions: this.sumOpportunitiesCommissionParts(followUp),
                failedCommissions:   this.sumOpportunitiesCommissionParts(failed),
                totalEvCharger:    this.sumEvChargerBudget(opportunities),
                acceptedEvCharger: this.sumEvChargerBudget(accepted),
                followUpEvCharger: this.sumEvChargerBudget(followUp),
                failedEvCharger:   this.sumEvChargerBudget(failed),
            }
        },
    }
}
</script>

<style scoped></style>