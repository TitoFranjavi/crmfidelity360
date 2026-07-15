<template>
    <div v-on:click="hideCustomSelects">

        <!--Estilo de movil-->
        <div class="mobile-item">
            <div class="content-white">

                <!--Header sticky: título + barra-->
                <div class="sticky-header-mobile">
                    <div class="d-flex justify-between align-center">
                        <div class="text my-10" data-size="22" data-weight="700">{{ $route.meta.title }} <span
                            data-size="12" class="my-auto opacity-5"> ( {{ totalAccounts }} {{ totalAccounts === 1 ?
                            'cuenta' : 'cuentas' }} )</span></div>
                        <div v-if="!isReadOnly" class="custom-button my-auto mobile-create-btn" data-size="medium" data-bg="principal"
                             data-weight="600" v-on:click="actionLink('/accounts/register')"><i class="fas fa-plus"></i>
                        </div>
                    </div>

                    <div class="search-div d-flex">
                        <div class="search-bar w-100">
                            <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>
                            <input type="text" data-size="14" placeholder="Buscar una cuenta..." v-model="searchAccountText"
                                   @input="debouncedFetchAllAccounts">
                        </div>
                        <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>
                    </div>
                </div>

                <!--Paginación-->
                <div class="d-grid my-10" data-column="2" data-layout="auto1" v-if="accounts && accounts.length > 0">
                    <div class="d-flex justify-center my-auto" data-color="principal">
                        <div class="left pointer my-auto" v-bind:class="{ 'opacity-5': currentPage === 1 }"
                             v-on:click="changePage(-1)"><i class="fa-solid fa-chevron-left"></i></div>
                        <div class="cont mx-10 my-auto" data-size="13" data-weight="600">{{ currentPage }} DE {{ totalPages }}</div>
                        <div class="right pointer my-auto" v-bind:class="{ 'opacity-5': currentPage === totalPages }"
                             v-on:click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></div>
                    </div>
                    <div class="my-auto ml-auto d-flex">
                        <p class="text my-auto mr-10" data-size="13">Por página: </p>
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

                <!--Filtros aplicados-->
                <div v-if="areFiltersApplied" class="d-flex column mobile-filters-applied my-10">
                    <div class="d-flex align-center justify-between">
                        <div class="d-flex align-center">
                            <i class="fa fas fa-lightbulb mr-10" data-color="rojo"></i>
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
                    <AccountFiltersComponent
                        v-if="seeFiltersMenu"
                        v-model:seeFiltersMenu="seeFiltersMenu"
                        v-model:filtersApplied="filtersApplied"
                        :filters="filters || { agents: [] }"
                        @resetFilters="resetFilters"
                    />
                </div>

                <!--Cargando-->
                <div class="loading-indicator" v-if="isLoading">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p class="text" data-size="14">Cargando cuentas...</p>
                </div>

                <!--Listado de cuentas-->
                <div class="d-flex column" v-if="!isLoading">
                    <div v-for="(account, accountKey) in accounts" class="my-5">
                        <div class="d-flex align-center pointer" v-on:click="seeAccountInfo(account)">
                            <div class="d-flex justify-center mr-10">
                                <div class="initials" data-size="17" v-if="!account.profileImage">{{ getInitials(account.name) }}</div>
                                <div class="initials" data-style="initials" v-bind:class="{ image: account.profileImage }" v-else>
                                    <img :src="'/assets/account_images/' + account.profileImage" class="profile-image">
                                </div>
                            </div>
                            <div class="text ellipsis" data-weight="600">{{ account.name }}</div>
                            <div class="deploy-btn ml-10" data-round="15"
                                 v-bind:class="{ 'selected': accountSelectedToSee && accountSelectedToSee._id && (((typeof accountSelectedToSee._id === 'string') ? accountSelectedToSee._id : accountSelectedToSee._id.$oid) === ((typeof account._id === 'string') ? account._id : account._id.$oid)) }">
                                <i class="fa-solid"
                                   v-bind:class="{ 'fa-chevron-down': !(accountSelectedToSee && accountSelectedToSee._id && (((typeof accountSelectedToSee._id === 'string') ? accountSelectedToSee._id : accountSelectedToSee._id.$oid) === ((typeof account._id === 'string') ? account._id : account._id.$oid))), 'fa-chevron-up': (accountSelectedToSee && accountSelectedToSee._id && (((typeof accountSelectedToSee._id === 'string') ? accountSelectedToSee._id : accountSelectedToSee._id.$oid) === ((typeof account._id === 'string') ? account._id : account._id.$oid))) }"></i>
                            </div>
                        </div>

                        <div class="d-flex column" v-if="accountSelectedToSee && accountSelectedToSee._id && (((typeof accountSelectedToSee._id === 'string') ? accountSelectedToSee._id : accountSelectedToSee._id.$oid) === ((typeof account._id === 'string') ? account._id : account._id.$oid))">
                            <div class="my-10">
                                <div class="d-flex justify-between" v-if="accountSelectedToSee.principalAcc">
                                    <div class="text" data-size="13" data-weight="600">Nombre cuenta princ.</div>
                                    <div class="text" data-size="13">{{ accountSelectedToSee.principalAcc.name ? accountSelectedToSee.principalAcc.name : '-' }}</div>
                                </div>
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Agente</div>
                                    <div class="text" data-size="13" v-if="accountSelectedToSee.createdByInfoToShow">{{ accountSelectedToSee.createdByInfoToShow.firstName + ' ' + accountSelectedToSee.createdByInfoToShow.lastName }}</div>
                                    <div class="text opacity-5" data-size="13" v-else>-</div>
                                </div>
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">CIF/NIF/Pasaporte</div>
                                    <div class="text" data-size="13" v-if="accountSelectedToSee.CIF">{{ accountSelectedToSee.CIF }}</div>
                                    <div class="text opacity-5" data-size="13" v-else>-</div>
                                </div>
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Teléfono</div>
                                    <div class="text" data-size="13" v-if="accountSelectedToSee.phone">{{ accountSelectedToSee.phone }}</div>
                                    <div class="text opacity-5" data-size="13" v-else>-</div>
                                </div>
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Email</div>
                                    <div class="text" data-size="13" v-if="accountSelectedToSee.email">{{ accountSelectedToSee.email }}</div>
                                    <div class="text opacity-5" data-size="13" v-else>-</div>
                                </div>
                                <div class="d-flex justify-between">
                                    <div class="text" data-size="13" data-weight="600">Fec. creación</div>
                                    <div class="text" data-size="13" v-if="accountSelectedToSee.createdAt">{{ new Date(accountSelectedToSee.createdAt).toLocaleDateString('es-ES') }}</div>
                                    <div class="text opacity-5" data-size="13" v-else>-</div>
                                </div>
                            </div>

                            <div class="d-flex column" data-gap="8">
                                <div v-on:click="actionLink('/accounts/' + (accountSelectedToSee._id.$oid ? accountSelectedToSee._id.$oid : accountSelectedToSee._id))"
                                     class="custom-button w-100" data-bg="principal" data-mode="outline"
                                     data-align="center" data-size="small" data-weight="700">
                                    <i class="fas fa-eye mr-5"></i> Ver cuenta
                                </div>
                                <div v-on:click="toggleArchiveAccount(account)" v-if="!isReadOnly"
                                     class="custom-button w-100" data-bg="principal" data-mode="outline"
                                     data-align="center" data-size="small" data-weight="700">
                                    <i class="fa-regular mr-5" v-bind:class="filtersApplied.view === 2 ? 'fa-box-open' : 'fa-box-archive'"></i>
                                    {{ filtersApplied.view === 2 ? 'Desarchivar' : 'Archivar' }}
                                </div>
                                <div v-on:click="deleteAccount(account)" v-if="!isReadOnly && canDelete('accounts.delete')"
                                     class="custom-button w-100" data-bg="rojo" data-mode="outline" data-align="center"
                                     data-size="small" data-weight="700">
                                    <i class="fa-regular fa-trash mr-5"></i> Eliminar
                                </div>
                            </div>
                        </div>

                        <div class="separator my-10" v-if="accountKey < accounts.length - 1"></div>
                    </div>
                </div>

                <div class="opacity-5" data-size="13" v-if="!isLoading && accounts && accounts.length === 0" data-align="center">
                    ¡No hay ningúna cuenta {{ filtersApplied.view === 2 ? 'archivada' : 'sin archivar' }}!
                </div>

                <div class="d-grid" data-column="3" v-if="accounts && accounts.length > 0">
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
        </div>

        <!--Estilo de ordenador-->
        <div class="desktop-item d-flex">
            <div class="content-white d-flex column mr-10"
                v-bind:class="{ 'contact-selected': accountSelectedToSee !== '' }">

                <!--Header-->
                <div class="d-flex justify-between align-center">
                    <div class="d-flex">
                        <div class="text" data-size="30" data-weight="700">{{ $route.meta.title }} <span data-size="12"
                                class="my-auto opacity-5"> ( {{ totalAccounts }} {{ totalAccounts === 1 ? 'cuenta' : 'cuentas' }} )</span></div>

                        <div class="d-grid" data-column="3" v-if="accounts && accounts.length > 0">
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

                    <div class="d-flex" data-gap="15">
                        <div class="d-flex">
                            <div class="custom-select no-hover" v-on:click.stop="isSeeingMassiveLoad = true"
                                v-bind:class="{ 'seeing': isSeeingMassiveLoad }">
                                <div class="custom-button" data-size="regular" data-bg="principal"><i class="far fa-plus"></i></div>
                                <div class="select-content form w-260-px">
                                    <div class="d-flex mt-5" v-if="canManage('accounts.massive')">
                                        <p class="text" @click="$refs.inputExcel.click()"><i class="fa-solid fa-file-excel ml-4 mr-14"></i>Carga masiva</p>
                                        <input type="file" ref="inputExcel" style="display: none" accept=".xls, .xlsx, .csv" @change="pickupDumpFile">
                                    </div>
                                    <a class="text" href="/assets/templates/accounts.xlsx" download="Plantilla cuentas" v-if="canManage('accounts.massive')">
                                        <i class="fas fa-file-arrow-down ml-4 mr-10"></i>Descargar plantilla
                                    </a>
                                    <p class="text mt-3" @click="exportAccountsToExcel" style="cursor: pointer;">
                                        <i class="fas fa-file-export ml-4 mr-10"></i>Exportar a Excel
                                    </p>
                                    <p class="text mt-3" @click="getEmailsToMassive" style="cursor: pointer;">
                                        <i class="fas fa-paper-plane-top ml-4 mr-10"></i>Enviar correo masivo
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="custom-button" v-if="!isReadOnly && canManage('accounts.create')" data-size="regular" data-bg="principal"
                            v-on:click="actionLink('/accounts/register')">Añadir cuenta</div>
                    </div>
                </div>

                <!--Filtros-->
                <div class="mt-30 select-line">
                    <div v-if="areFiltersApplied" class="d-flex filters-on relPos">
                        <i class="fa fas fa-lightbulb my-auto" data-color="rojo"></i>
                        <p class="my-auto mx-15" data-color="rojo">Filtros aplicados</p>
                        <div class="custom-button" data-size="small" data-bg="rojo" data-mode="translucent" @click="resetFilters">Borrar filtros</div>
                    </div>

                    <div class="before-search">
                        <div @click="seeFiltersMenu = !seeFiltersMenu" class="custom-button" data-size="small" data-bg="azul" data-mode="translucent">
                            {{ seeFiltersMenu ? 'Ocultar' : 'Mostrar' }} filtros
                        </div>
                    </div>

                    <div class="search-div d-flex">
                        <div class="search-bar w-100">
                            <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>
                            <input type="text" placeholder="Buscar una cuenta..." v-model="searchAccountText" @input="debouncedFetchAllAccounts">
                        </div>
                        <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>
                    </div>
                </div>

                <!--Header tabla-->
                <div class="contact header-card accounts mt-30">
                    <div class="custom-checkbox pointer" v-on:click="toggleSelectAllAccount"
                        v-bind:class="{ 'selected': areAllSelected && accounts.length > 0 }"></div>

                    <div class="d-flex" data-color="principal">
                        <div class="d-flex column">
                            <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('name')">Nombre</p>
                            <p class="text" data-size="10">Nombre cuenta princ.</p>
                        </div>
                        <i class="fas my-auto pointer" v-on:click="selectNewOrderType('name')"
                            v-bind:class="filtersApplied.sortBy === 0 ? 'fa-sort-down' : (filtersApplied.sortBy === 1 ? 'fa-sort-up' : 'fa-sort')"></i>
                    </div>

                    <div class="d-flex" data-color="principal">
                        <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('agent')">Agente</p>
                        <i class="fas my-auto pointer" v-on:click="selectNewOrderType('agent')"
                            v-bind:class="filtersApplied.sortBy === 2 ? 'fa-sort-down' : (filtersApplied.sortBy === 3 ? 'fa-sort-up' : 'fa-sort')"></i>
                    </div>

                    <div class="d-flex" data-color="principal">
                        <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('nif')">CIF/NIF/Pasaporte</p>
                        <i class="fas my-auto pointer" v-on:click="selectNewOrderType('nif')"
                            v-bind:class="filtersApplied.sortBy === 4 ? 'fa-sort-down' : (filtersApplied.sortBy === 5 ? 'fa-sort-up' : 'fa-sort')"></i>
                    </div>

                    <div class="d-flex" data-color="principal">
                        <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('phone')">Telefono</p>
                        <i class="fas my-auto pointer" v-on:click="selectNewOrderType('phone')"
                            v-bind:class="filtersApplied.sortBy === 6 ? 'fa-sort-down' : (filtersApplied.sortBy === 7 ? 'fa-sort-up' : 'fa-sort')"></i>
                    </div>

                    <div class="d-flex" data-color="principal">
                        <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('email')">Email</p>
                        <i class="fas my-auto pointer" v-on:click="selectNewOrderType('email')"
                            v-bind:class="filtersApplied.sortBy === 8 ? 'fa-sort-down' : (filtersApplied.sortBy === 9 ? 'fa-sort-up' : 'fa-sort')"></i>
                    </div>

                    <div class="d-flex" data-color="principal">
                        <p class="text mr-5 pointer ellipsis noWidth" data-weight="600" v-on:click="selectNewOrderType('createdAt')">Fec. creación</p>
                        <i class="fas my-auto pointer" v-on:click="selectNewOrderType('createdAt')"
                            v-bind:class="filtersApplied.sortBy === 10 ? 'fa-sort-down' : (filtersApplied.sortBy === 11 ? 'fa-sort-up' : 'fa-sort')"></i>
                    </div>

                    <div class="d-flex" v-if="!isReadOnly && accountsSelected.length > 0">
                        <div class="ml-50 mr-10 text pointer" v-on:click="toggleArchiveSelectedAccounts()"><i class="far fa-box-archive"></i></div>
                    </div>
                </div>

                <div class="separator my-10"></div>

                <!--Loader-->
                <div v-if="isLoading">
                    <div class="d-flex column" v-for="i of 10">
                        <div class="contact accounts pointer">
                            <div class="loading mx-10 h-20-px" v-for="i of 8"></div>
                        </div>
                        <div class="separator my-10"></div>
                    </div>
                </div>

                <div v-else-if="accounts && accounts.length > 0">
                    <div>
                        <account-card-component v-for="account in accounts" :account="account"
                            :accountSelectedToSee="accountSelectedToSee" :accountsSelected="accountsSelected"
                            :isSeeingArchived="filtersApplied.view === 2" :isReadOnly="isReadOnly"
                            :canDelete="canDelete('accounts.delete')" @toggleArchiveAccount="toggleArchiveAccount"
                            @deleteAccount="deleteAccount" @seeAccountInfo="seeAccountInfo"
                            @toggleSelectAccount="toggleSelectAccount">
                        </account-card-component>
                    </div>

                    <div class="d-grid" data-column="3">
                        <div></div>
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

                <div v-else class="opacity-5" data-align="center">
                    ¡No hay ningúna cuenta {{ filtersApplied.view === 2 ? 'archivada' : 'sin archivar' }}!
                </div>

                <!--Flotante filtros-->
                <AccountFiltersComponent
                    v-if="seeFiltersMenu"
                    v-model:seeFiltersMenu="seeFiltersMenu"
                    v-model:filtersApplied="filtersApplied"
                    :filters="filters || { agents: [] }"
                    @resetFilters="resetFilters"
                />
            </div>

            <!--Info cuenta seleccionada-->
            <div v-if="accountSelectedToSee !== ''" class="info-content">
                <div class="d-flex">
                    <div class="d-flex justify-center mr-20">
                        <div class="initials" data-size="25"
                            v-if="accountSelectedToSee.name && !accountSelectedToSee.profileImage">{{ getInitials(accountSelectedToSee.name) }}</div>
                        <div class="initials" data-style="initials"
                            v-bind:class="{ image: accountSelectedToSee.profileImage }" v-else>
                            <img :src="'/assets/account_images/' + accountSelectedToSee.profileImage" class="profile-image">
                        </div>
                    </div>
                    <div class="d-flex column">
                        <p data-color="azul" data-weight="700" data-size="20">{{ accountSelectedToSee.name }}</p>
                    </div>
                </div>

                <div class="my-20">
                    <p class="text" data-size="20" data-weight="700">Datos de cuenta</p>
                    <div class="separator my-0"></div>
                    <div class="d-flex justify-between my-10">
                        <div class="text w-50" data-weight="600">CIF/NIF/Pasaporte</div>
                        <div class="text w-50 ellipsis" data-align="right" data-weight="300">{{ accountSelectedToSee.CIF ? accountSelectedToSee.CIF : '-' }}</div>
                    </div>
                    <div class="d-flex justify-between my-10">
                        <div class="text w-50" data-weight="600">Teléfono</div>
                        <div class="text w-50 ellipsis" data-align="right" data-weight="300" v-if="accountSelectedToSee.phone">{{ accountSelectedToSee.phone }}</div>
                        <div class="text opacity-5" v-else>-</div>
                    </div>
                    <div class="d-flex justify-between my-10">
                        <div class="text w-50" data-weight="600">Sitio web</div>
                        <a class="text w-50 ellipsis" data-align="right" data-weight="300"
                            v-if="accountSelectedToSee.website" :href="'https://' + accountSelectedToSee.website" target="_blank">{{ accountSelectedToSee.website }}</a>
                        <div class="text opacity-5" v-else>-</div>
                    </div>
                    <div class="d-flex justify-between my-10">
                        <div class="text w-50" data-weight="600">Tipo de cuenta</div>
                        <div class="text w-50 ellipsis" data-align="right" data-weight="300" v-if="accountSelectedToSee.accType">{{ accountSelectedToSee.accType }}</div>
                        <div class="text opacity-5" v-else>-</div>
                    </div>
                    <div class="d-flex justify-between my-10">
                        <div class="text w-50" data-weight="600">Sector</div>
                        <div class="text w-50 ellipsis" data-align="right" data-weight="300" v-if="accountSelectedToSee.sector">{{ accountSelectedToSee.sector }}</div>
                        <div class="text opacity-5" v-else>-</div>
                    </div>
                </div>

                <div class="my-20" v-if="accountSelectedToSee.orders && accountSelectedToSee.orders.length > 0">
                    <p class="text" data-size="18" data-weight="700">Pedidos de la cuenta</p>
                    <div class="separator my-0"></div>
                    <div v-for="order in accountSelectedToSee.orders" class="d-flex justify-between my-10 pointer"
                        v-on:dblclick.stop="actionLink(`/accounts/${accountSelectedToSee._id}?id=${(typeof order._id === 'string') ? order._id : order._id.$oid}`)">
                        <div class="text w-50" data-weight="600">{{ order.name }}</div>
                        <div class="d-flex column" v-if="getStatus(order)">
                            <div class="custom-button w-fit-content mr-auto" data-size="small"
                                :data-bg="getStatus(order).color" data-color="principal" data-mode="translucent">{{ getStatus(order).title }}</div>
                        </div>
                    </div>
                </div>

                <div class="my-20" v-if="accountSelectedToSee.principalAcc && accountSelectedToSee.principalAcc.length > 0">
                    <p class="text" data-size="18" data-weight="600">Cuenta principal</p>
                    <div class="separator my-0"></div>
                    <div class="d-flex my-20">
                        <div class="d-flex justify-center mr-20">
                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700"
                                v-if="accountSelectedToSee.principalAcc[0] && !accountSelectedToSee.principalAcc[0].profileImage">
                                {{ getInitials(accountSelectedToSee.principalAcc[0].name) }}</div>
                            <div class="initials mr-20" data-style="initials"
                                v-bind:class="{ image: accountSelectedToSee.principalAcc[0].profileImage }" v-else>
                                <img :src="'/assets/account_images/' + accountSelectedToSee.principalAcc[0].profileImage" class="profile-image">
                            </div>
                            <div class="d-flex column my-auto">
                                <p data-color="azul" data-weight="700" data-size="15">{{ accountSelectedToSee.principalAcc[0].name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="custom-button" data-style="twoBlue" data-size="small"
                        v-on:click="actionLink(`/accounts/${accountSelectedToSee.principalAcc[0]._id['$oid']}`)">Ver detalles de la cuenta principal <i class="fas fa-arrow-right-long"></i></div>
                </div>

                <div class="my-20" v-if="accountSelectedToSee.secondaryAccounts && accountSelectedToSee.secondaryAccounts.length > 0">
                    <p class="text" data-size="18" data-weight="600">Cuentas secundarias</p>
                    <div class="separator my-0"></div>
                    <div class="d-flex my-10" v-for="secondaryAcc in accountSelectedToSee.secondaryAccounts">
                        <div class="d-flex justify-center mr-20 w-100">
                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700">{{ getInitials(secondaryAcc.name) }}</div>
                            <div class="d-flex column my-auto">
                                <p data-color="azul" data-weight="700" data-size="15">{{ secondaryAcc.name }}</p>
                            </div>
                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                v-on:click="actionLink(`/accounts/${secondaryAcc._id['$oid']}`)"><i class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>
                </div>

                <div class="my-20" v-if="accountSelectedToSee.contacts && accountSelectedToSee.contacts.length > 0">
                    <p class="text" data-size="18" data-weight="600">Contactos</p>
                    <div class="separator my-0"></div>
                    <div class="d-flex my-10" v-for="contact in accountSelectedToSee.contacts">
                        <div class="d-flex justify-center mr-20 w-100">
                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700" v-if="contact.name">{{ getInitials(contact.name.first + contact.name.first) }}</div>
                            <div class="d-flex column my-auto ellipsis">
                                <p class="ellipsis" data-color="azul" data-weight="700" data-size="15">{{ contact.name.first }} {{ contact.name.second }} {{ contact.surname.first }} {{ contact.surname.second }}</p>
                            </div>
                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                v-on:click="actionLink(`/contacts/${contact._id['$oid']}`)"><i class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>
                </div>

                <div class="my-20" v-if="accountSelectedToSee.opportunities && accountSelectedToSee.opportunities.length > 0">
                    <p class="text" data-size="18" data-weight="600">Oportunidades</p>
                    <div class="separator my-0"></div>
                    <div class="d-flex my-10" v-for="opportunity in accountSelectedToSee.opportunities">
                        <div class="d-flex justify-center mr-20 w-100">
                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700">{{ getInitials(opportunity.title) }}</div>
                            <div class="d-flex column my-auto">
                                <p data-color="azul" data-weight="700" data-size="15">{{ opportunity.title }}</p>
                            </div>
                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                v-on:click="actionLink(`/opportunities/${opportunity._id['$oid']}`)"><i class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>
                </div>

                <div class="my-20" v-if="accountSelectedToSee.tasks && accountSelectedToSee.tasks.length > 0">
                    <p class="text" data-size="18" data-weight="600">Tareas</p>
                    <div class="separator my-0"></div>
                    <div class="d-flex my-10" v-for="task in accountSelectedToSee.tasks">
                        <div class="d-flex justify-center mr-20 w-100">
                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700">{{ getInitials(task.subject) }}</div>
                            <div class="d-flex column my-auto">
                                <p data-color="azul" data-weight="700" data-size="15">{{ task.subject }}</p>
                            </div>
                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                v-on:click="actionLink(`/tasks/${task._id['$oid']}`)"><i class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>
                </div>

                <div class="my-20" v-if="accountSelectedToSee.events && accountSelectedToSee.events.length > 0">
                    <p class="text" data-size="18" data-weight="600">Eventos</p>
                    <div class="separator my-0"></div>
                    <div class="d-flex my-10" v-for="event in accountSelectedToSee.events">
                        <div class="d-flex justify-center mr-20 w-100">
                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700">{{ getInitials(event.subject) }}</div>
                            <div class="d-flex column my-auto">
                                <p data-color="azul" data-weight="700" data-size="15">{{ event.subject }}</p>
                            </div>
                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small"
                                v-on:click="actionLink(`/calendar/${event._id['$oid']}`)"><i class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>
                </div>

                <div v-if="accountSelectedToSee.createdBy">
                    <p class="text opacity-5" data-size="13" data-weight="600"><i class="fas fa-user mr-5"></i> Creador de la cuenta</p>
                    <div class="separator my-0"></div>
                    <div class="d-flex justify-center mr-20 my-10 w-100">
                        <div class="initials verySmall mr-20 my-auto" data-style="initials"
                            v-bind:class="{ image: accountSelectedToSee.createdByInfoToShow.profileImage }">
                            <img :src="'/assets/profile_images/' + accountSelectedToSee.createdByInfoToShow.profileImage" class="profile-image">
                        </div>
                        <div class="d-flex column my-auto">
                            <p class="text opacity-5" data-weight="600" data-size="14">{{ accountSelectedToSee.createdByInfoToShow.firstName }} {{ accountSelectedToSee.createdByInfoToShow.lastName }}</p>
                        </div>
                        <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="tiny"
                            v-on:click="actionLink((this.basicData.userLogged._id === accountSelectedToSee.createdByInfoToShow._id ? '/profile' : `/users/${accountSelectedToSee.createdBy._id}`))">
                            <i class="fas fa-arrow-right-long"></i>
                        </div>
                    </div>
                </div>

                <div class="separator my-10"></div>

                <div class="mr-auto">
                    <div v-on:click="actionLink('/accounts/' + accountSelectedToSee._id)" class="custom-button mr-10"
                        data-size="medium" data-bg="azul" data-mode="outline" data-color="azul">Editar</div>
                </div>
            </div>
        </div>

        <div class="loader-box" v-if="isLoadingMassiveLoad">
            <div class="loader"></div>
        </div>
    </div>
</template>

<script>
import { useDebounceFn } from "@vueuse/core"
import AccountFiltersComponent from "@/components/items/AccountFiltersComponent.vue"

export default {
    name: "AccountListComponent",
    components: { AccountFiltersComponent },
    props: ['basicData'],
    data() {
        const savedFilters = sessionStorage.getItem('accountFilters')
        const savedSearch  = sessionStorage.getItem('accountSearch')
        return {
            accounts: {},
            accountsAll: {},
            accountsSelected: [],
            accountSelectedToSee: '',
            isLoading: true,
            isLoadingMassiveLoad: false,
            isSeeingMassiveLoad: false,
            seeFiltersMenu: false,
            filtersApplied: savedFilters ? JSON.parse(savedFilters) : {
                agents: [],
                dates: { start: null, end: null },
                view: 1,
                sortBy: 11,
            },
            filters: null,
            searchAccountText: savedSearch || '',
            statuses: [
                { code: 'r',  title: 'Recibido',                    color: 'receivedStatus',              limitedTo: [] },
                { code: 'p',  title: 'Pendiente',                   color: 'pendingStatus',               limitedTo: [] },
                { code: 't',  title: 'Tramitado',                   color: 'processedStatus',             limitedTo: [] },
                { code: 'f',  title: 'Firmado',                     color: 'signedStatus',                limitedTo: [] },
                { code: 'fc', title: 'Firmado - Llamada verificada', color: 'signedAndVerifiedCallStatus', limitedTo: [] },
                { code: 'ac', title: 'Aceptado',                    color: 'aceptedStatus',               limitedTo: [] },
                { code: 'ap', title: 'Pendiente de activacion',     color: 'activatedPendingStatus',      limitedTo: [] },
                { code: 'a',  title: 'Activado',                    color: 'activatedStatus',             limitedTo: [] },
                { code: 'c',  title: 'Comisionado',                 color: 'commissionedStatus',          limitedTo: [] },
                { code: 'i',  title: 'Incidencia',                  color: 'incidenceStatus',             limitedTo: [] },
                { code: 's',  title: 'Scoring',                     color: 'scoringStatus',               limitedTo: [] },
                { code: 'b',  title: 'Baja',                        color: 'lowStatus',                   limitedTo: [] },
                { code: 'bo', title: 'Borrador',                    color: 'lowStatus',                   limitedTo: [] },
                { code: 'an', title: 'Anulado',                     color: 'morado',                      limitedTo: [] },
            ],
            currentPage: 1,
            perPage: 50,
            totalPages: 1,
            totalAccounts: 0,
            perPageOptions: [50, 100, 200],
            debouncedFetchAllAccounts: null,
        }
    },
    created() {
        this.debouncedFetchAllAccounts = useDebounceFn(() => this.fetchAllAccounts(), 300)
    },
    watch: {
        'basicData.userLogged': {
            immediate: true,
            deep: true,
            handler(user) {
                if (!user) return
                this.fetchAllAccounts()
                this.fetchAccountFilters()
            }
        },
        'searchAccountText'() {
            this.currentPage = 1
            sessionStorage.setItem('accountSearch', this.searchAccountText)
            this.debouncedFetchAllAccounts()
        },
        filtersApplied: {
            deep: true,
            handler(val) {
                sessionStorage.setItem('accountFilters', JSON.stringify(val))
                this.currentPage = 1
                this.debouncedFetchAllAccounts()
            }
        },
    },
    methods: {
        async fetchAllAccounts() {
            if (!this.basicData?.userLogged?._id) return

            this.isLoading = true

            let indexId = this.basicData.userLogged._id
            let usersList = JSON.stringify(this.basicData.userList)

            if (this.canManage('users.admiWhiHier')) {
                indexId = this.basicData.userSubdomain._id
                usersList = JSON.stringify(this.basicData.subdomainUserList)
            }

            try {
                const res = await axios.post(`/api/accounts/index/${indexId}`, {
                    userList: usersList,
                    filters: this.filtersApplied,
                    searchAccountText: this.searchAccountText,
                    page: this.currentPage,
                    perPage: this.perPage,
                })

                this.accounts = res.data.accounts
                this.totalPages = Math.ceil(res.data.totalResults / this.perPage)
                this.totalAccounts = res.data.totalResults
            } catch (err) {
                console.error(err)
            } finally {
                this.isLoading = false
            }
        },

        async fetchAccountFilters() {
            let indexId = this.basicData.userLogged._id
            let usersList = JSON.stringify(this.basicData.userList)

            if (this.canManage('users.admiWhiHier')) {
                indexId = this.basicData.userSubdomain._id
                usersList = JSON.stringify(this.basicData.subdomainUserList)
            }

            try {
                const res = await axios.post(`/api/accounts/indexFilters/${indexId}`, {
                    userList: usersList,
                })
                this.filters = res.data

                console.log(res.data, "Hola")
            } catch (err) {
                console.error(err)
            }
        },

        async fetchAllAccountsWithoutPagination() {
            this.accountsAll = []

            let indexId = this.basicData.userLogged._id
            let usersList = JSON.stringify(this.basicData.userList)

            if (this.canManage('users.admiWhiHier')) {
                indexId = this.basicData.userSubdomain._id
                usersList = JSON.stringify(this.basicData.subdomainUserList)
            }

            try {
                const res = await axios.post(`/api/accounts/index/${indexId}`, {
                    userList: usersList,
                    filters: this.filtersApplied,
                    searchAccountText: this.searchAccountText,
                    page: 1,
                    perPage: 99999,
                })
                this.accountsAll = res.data.accounts
            } catch (err) {
                console.error(err)
            }
        },

        async exportAccountsToExcel() {
            try {
                const res = await axios.post('/api/accounts/export', {
                    userId: this.basicData.userLogged._id,
                    userList: JSON.stringify(this.basicData.userList),
                    filters: this.filtersApplied,
                    searchAccountText: this.searchAccountText,
                }, { responseType: 'blob' })

                const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
                const url = URL.createObjectURL(blob)
                const a = document.createElement('a')
                a.href = url
                a.download = `Cuentas_${moment().format('YYYY-MM-DD')}.xlsx`
                document.body.appendChild(a)
                a.click()
                a.remove()
                URL.revokeObjectURL(url)
            } catch (err) {
                console.error(err)
            }
        },

        resetSearch() {
            this.searchAccountText = ''
            sessionStorage.removeItem('accountSearch')
            this.debouncedFetchAllAccounts()
        },

        changePageSize() {
            this.currentPage = 1
            this.fetchAllAccounts()
        },

        getInitials(name) {
            if (name) {
                let nameSplited = name.split(/\s+/)
                let initials = nameSplited[0][0]
                if (nameSplited[1]) initials += nameSplited[1][0]
                return initials
            }
        },

        getAccountId(account) {
            return account?._id?.$oid ?? account?._id
        },

        async toggleArchiveAccount(account) {
            const accountId = this.getAccountId(account)
            await axios.post(`/api/accounts/toggleArchiveAccount/${accountId}`, { isForArchiving: !account.archived })
                .then(() => {
                    this.fetchAllAccounts()
                    if (this.getAccountId(this.accountSelectedToSee) === accountId)
                        this.accountSelectedToSee = ''
                })
                .catch(err => console.log(err))
        },

        async toggleArchiveSelectedAccounts() {
            const accountsToToggle = this.accountsSelected.map(account => ({
                _id: this.getAccountId(account),
                archived: !!account.archived,
            }))
            await axios.post(`/api/accounts/toggleArchiveSelectedAccounts`, { accountsToToggle })
                .then(() => {
                    this.accountsSelected = []
                    this.fetchAllAccounts()
                    this.accountSelectedToSee = ''
                })
                .catch(err => console.log(err))
        },

        deleteAccount(account) {
            Swal.fire({
                icon: 'warning',
                title: '¿Estas seguro?',
                text: 'Si sigues con esta acción no podras revertirla. Además se eliminara de todo en lo que este vinculada.',
                confirmButtonText: 'Sí',
                showConfirmButton: true,
                cancelButtonText: 'No',
                showCancelButton: true
            }).then((res) => {
                if (res.isConfirmed) {
                    const accountId = this.getAccountId(account)
                    axios.delete(`/api/accounts/${accountId}`)
                        .then(() => {
                            this.fetchAllAccounts()
                            if (this.getAccountId(this.accountSelectedToSee) === accountId)
                                this.accountSelectedToSee = ''
                        })
                        .catch(err => console.log(err))
                }
            })
        },

        toggleSelectAccount(account) {
            if (this.accountsSelected.some(a => a._id === account._id)) {
                let index = this.accountsSelected.findIndex(a => a._id === account._id)
                this.accountsSelected.splice(index, 1)
            } else {
                this.accountsSelected.push(account)
            }
        },

        toggleSelectAllAccount() {
            if (this.accountsSelected && this.accounts && this.accountsSelected.length === this.accounts.length) {
                this.accountsSelected = []
            } else {
                this.accountsSelected = [...this.accounts]
            }
        },

        async seeAccountInfo(account) {
            const accountId = this.getAccountId(account)
            if (this.getAccountId(this.accountSelectedToSee) === accountId) {
                this.accountSelectedToSee = ''
            } else {
                await axios.get(`/api/accounts/${accountId}`).then((res) => {
                    this.accountSelectedToSee = res.data.account
                    this.accountSelectedToSee.createdByInfoToShow = account.agent[0]
                })
            }
        },

        getStatus(order) {
            let recentStatus = order.statuses.reduce((latest, current) => {
                return new Date(current.date) > new Date(latest.date) ? current : latest
            })
            return this.statuses.find(status => status.code === recentStatus.code)
        },

        resetFilters() {
            this.filtersApplied = {
                agents: [],
                dates: { start: null, end: null },
                view: 1,
                sortBy: 11,
            }
            this.searchAccountText = ''
            sessionStorage.removeItem('accountSearch')
        },

        selectNewOrderType(orderType) {
            switch (orderType) {
                case 'name':
                    if (this.filtersApplied.sortBy !== 0 && this.filtersApplied.sortBy !== 1) this.filtersApplied.sortBy = 0
                    else if (this.filtersApplied.sortBy === 0) this.filtersApplied.sortBy = 1
                    else this.filtersApplied.sortBy = 11
                    break
                case 'agent':
                    if (this.filtersApplied.sortBy !== 2 && this.filtersApplied.sortBy !== 3) this.filtersApplied.sortBy = 2
                    else if (this.filtersApplied.sortBy === 2) this.filtersApplied.sortBy = 3
                    else this.filtersApplied.sortBy = 11
                    break
                case 'nif':
                    if (this.filtersApplied.sortBy !== 4 && this.filtersApplied.sortBy !== 5) this.filtersApplied.sortBy = 4
                    else if (this.filtersApplied.sortBy === 4) this.filtersApplied.sortBy = 5
                    else this.filtersApplied.sortBy = 11
                    break
                case 'phone':
                    if (this.filtersApplied.sortBy !== 6 && this.filtersApplied.sortBy !== 7) this.filtersApplied.sortBy = 6
                    else if (this.filtersApplied.sortBy === 6) this.filtersApplied.sortBy = 7
                    else this.filtersApplied.sortBy = 11
                    break
                case 'email':
                    if (this.filtersApplied.sortBy !== 8 && this.filtersApplied.sortBy !== 9) this.filtersApplied.sortBy = 8
                    else if (this.filtersApplied.sortBy === 8) this.filtersApplied.sortBy = 9
                    else this.filtersApplied.sortBy = 11
                    break
                case 'createdAt':
                    if (this.filtersApplied.sortBy !== 10 && this.filtersApplied.sortBy !== 11) this.filtersApplied.sortBy = 10
                    else if (this.filtersApplied.sortBy === 10) this.filtersApplied.sortBy = 11
                    else this.filtersApplied.sortBy = 10
                    break
            }
            this.fetchAllAccounts()
        },

        hideCustomSelects() {
            this.isSeeingMassiveLoad = false
        },

        changePage(value) {
            const newPage = this.currentPage + value
            if (newPage >= 1 && newPage <= this.totalPages) {
                this.currentPage = newPage
                this.fetchAllAccounts()
            }
        },

        async getEmailsToMassive() {
            await this.fetchAllAccountsWithoutPagination()
            const emails = this.accountsAll
                .map(acc => acc.email)
                .filter((email, index, self) => self.indexOf(email) === index && email !== '')
            localStorage.setItem('emailsTemporaly', JSON.stringify(emails))
            this.$router.push('/tools?section=massiveEmail&withFilters=true')
        },

        pickupDumpFile(event) {
            let file = event.target.files[0]
            if (file) this.dumpAccounts(file)
        },

        async dumpAccounts(file) {
            this.hideCustomSelects()
            this.isLoadingMassiveLoad = true

            const formData = new FormData()
            formData.append('file', file)
            formData.append('userSubdomain', JSON.stringify(this.basicData.userSubdomain))
            formData.append('userList', JSON.stringify(this.basicData.userList))

            axios.post('/api/accounts/dumpAccounts', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then((res) => {
                const data = res?.data || {}
                const summary = data.summary || {}
                const skipped = Array.isArray(data.skippedRows) ? data.skippedRows : []

                this.fetchAllAccounts()

                if (skipped.length > 0) {
                    const reasonMap = {
                        fila_invalida: 'Fila inválida',
                        campo_obligatorio_faltante: 'Falta campo obligatorio',
                        duplicado_cif: 'Duplicado (CIF/NIF)',
                        propietarios_no_validos: 'Propietarios no válidos',
                        error_store: 'Error al guardar',
                    }

                    const escape = (v) => {
                        const s = typeof v === 'string' ? v : JSON.stringify(v ?? '')
                        return s.replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]))
                    }

                    const html = `
                        <div style="text-align:left; max-height:360px; overflow:auto;">
                            <p><b>Archivo:</b> ${escape(summary.fileName || '-')}</p>
                            <p><b>Total procesadas:</b> ${summary.totalProcessed ?? '-'}</p>
                            <p><b>Insertadas:</b> ${summary.inserted ?? '0'}</p>
                            <p><b>Saltadas:</b> ${summary.skipped ?? skipped.length}</p>
                            <hr/>
                            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                                <thead>
                                    <tr style="border-bottom:1px solid #ddd;">
                                        <th style="padding:6px; text-align:left;">Fila</th>
                                        <th style="padding:6px; text-align:left;">Motivo</th>
                                        <th style="padding:6px; text-align:left;">Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${skipped.map(s => `
                                        <tr style="border-bottom:1px solid #eee;">
                                            <td style="padding:6px; font-weight:600;">${escape(s.rowNumber ?? '-')}</td>
                                            <td style="padding:6px;">${escape(reasonMap[s.reason] || s.reason || 'Error')}</td>
                                            <td style="padding:6px; opacity:.75;">${escape(typeof s.details === 'string' ? s.details : JSON.stringify(s.details || ''))}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `

                    Swal.fire({ icon: 'warning', title: 'Importación con incidencias', html, width: 800, confirmButtonText: 'Entendido' })
                } else {
                    Swal.fire({ icon: 'success', title: 'Carga satisfactoria', text: 'Las cuentas han sido creadas correctamente', timer: 1600, timerProgressBar: true })
                }
            }).catch((err) => {
                let errorText = 'Error desconocido al importar.'
                try { errorText = JSON.parse(err?.request?.response)?.error || errorText } catch (_) {}
                Swal.fire({ icon: 'error', title: 'Carga incorrecta', text: errorText })
            }).finally(() => {
                this.isLoadingMassiveLoad = false
                if (this.$refs?.inputExcel) this.$refs.inputExcel.value = ''
            })
        },

        actionLink(route) {
            this.$router.push(route)
        },

        canManage(code) {
            const user = this.basicData?.userLogged
            const subdomain = this.basicData?.userSubdomain
            if (!user || !subdomain) return false
            const label = user.label
            const labelsPermissions = subdomain.labels_permissions
            if (!label || !labelsPermissions || !labelsPermissions[label]) return false
            if (!code || !code.includes('.')) return false
            const [module, action] = code.split('.')
            const modulePermissions = labelsPermissions[label][module]
            return Array.isArray(modulePermissions) && modulePermissions.includes(action)
        },

        canDelete(code) {
            return this.canManage(code)
        },
    },
    computed: {
        areAllSelected() {
            if (this.accounts) return this.accountsSelected.length === this.accounts.length
        },
        areFiltersApplied() {
            const f = this.filtersApplied
            return f.agents.length > 0 ||
                !!f.dates.start || !!f.dates.end ||
                f.view !== 1 ||
                f.sortBy !== 11
        },
        isReadOnly() {
            if (!this.basicData.userLogged) return true
            return this.basicData.userLogged.permissions.includes('READONLY')
        },
    },
}
</script>

<style scoped></style>