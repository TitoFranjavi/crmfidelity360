<template>
    <div v-on:click="hideCustomSelects">

        <!--Estilo de movil-->
        <div class="mobile-item">
            <div class="content-white">

                <!--Header sticky: título + barra-->
                <div class="sticky-header-mobile">
                    <!--Título-->
                    <div class="d-flex justify-between">
                        <div class="text my-10" data-size="22" data-weight="700">{{ $route.meta.title }} <span data-size="12" class="my-auto opacity-5"> ( {{ totalContacts }} {{ totalContacts === 1 ? 'contacto' : 'contactos'}} )</span></div>
                        <div class="custom-button my-auto mobile-create-btn" data-size="medium" data-bg="principal" data-weight="600" v-if="!isReadOnly" v-on:click="actionLink('/contacts/register')"><i class="fas fa-plus"></i></div>
                    </div>

                    <!--Barra de busqueda-->
                    <div class="d-flex">

                        <div class="search-bar w-100">
                            <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                            <input class="w-100" type="text" data-size="14" placeholder="Buscar un contacto..." v-model="searchContactText" v-on:keyup="fetchAllContacts" @blur="saveContactText">
                        </div>

                        <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>
                    </div>
                </div>



                <!--Paginación-->
                <div class="d-grid my-10" data-column="2" data-layout="auto1" v-if="contacts && contacts.length > 0">

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

                    <!--<p class="text" data-size="13" data-weight="600" v-on:click="isSeeingFilters = !isSeeingFilters">{{ isSeeingFilters ? 'Ocultar filtros' : 'Mostrar filtros' }}</p>-->


                    <div v-if="isSeeingFilters">

                        <div class="arrow-border arrow-top my-10" data-position="left"></div>

                        <!--Cuentas-->
                        <div class="d-flex justify-between my-10">
                            <div class="text" data-size="13" data-weight="600">Cuentas</div>

                            <div class="custom-select" v-if="filtersObtained.accounts && filtersObtained.accounts.length > 0">

                                <div class="ml-10" data-size="13" data-color="azul">{{ getAccountFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content left">
                                    <div v-for="account in filtersFiltered.accounts" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(account)" v-bind:class="{ 'selected': account.active }"></div>

                                        <div class="text" data-size="13">{{ account.name }}</div>

                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 cuentas</div>
                        </div>




                        <!--Ordenar-->
                        <div class="d-flex justify-between my-10">
                            <div class="text" data-size="13" data-weight="600">Ordenar</div>

                            <div class="custom-select">

                                <div class="ml-10" data-size="13" data-color="azul">{{ orderTypeSelected }} <i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content">
                                    <div v-for="orderType in filters.radio.sortBy.data" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="selectOrderType(orderType)" v-bind:class="{ 'selected': orderType.value === $cookies.get('filters')['contacts']['sortBy'] }"></div>

                                        <div class="text" data-size="13">{{ orderType.title }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--Alternancia archivado y no archivado-->
                        <div class="d-flex justify-between my-10">
                            <div class="text" data-size="13" data-weight="600">Visibilidad</div>

                            <div class="custom-select">

                                <div class="ml-10" data-size="13" data-color="azul">{{ isSeeingFilters ? 'Archivado' : 'Agenda' }} <i class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content">
                                    <div class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="changeIsSeeingArchived(false)" v-bind:class="{ 'selected': !isSeeingArchived }"></div>

                                        <div class="text" data-size="13">Agenda</div>
                                    </div>

                                    <div class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="changeIsSeeingArchived(true)" v-bind:class="{ 'selected': isSeeingArchived }"></div>

                                        <div class="text" data-size="13">Archivado</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="separator mt-10"></div>
                    </div>
                </div>


                <!--Listado de contacto-->
                <div class="d-flex column">

                    <div v-for="(contact, contactKey) in contacts" class="my-5">

                        <!--Card-->
                        <div class="d-flex align-center pointer" v-on:click="seeContactInfo(contact)">
                            <div class="d-flex justify-center mr-10">
                                <div class="initials" data-size="17" v-if="!contact.profileImage">{{ getInitials(contact.name.first) }}</div>
                                <div class="initials" data-style="initials" v-bind:class="{image: contact.profileImage}" v-else>
                                    <img :src="'/assets/contact_images/' + contact.profileImage" class="profile-image">
                                </div>
                            </div>

                            <div class="text ellipsis" data-weight="600">{{ contact.name.first }} {{ contact.name.second }}</div>

                            <div class="deploy-btn ml-10" data-round="15" v-bind:class="{'selected': contactSelectedToSee._id === contact._id}">
                                <i class="fa-solid" v-bind:class="{'fa-chevron-down': contactSelectedToSee._id !== contact._id, 'fa-chevron-up': contactSelectedToSee._id === contact._id}"></i>
                            </div>
                        </div>

                        <!--Info card-->
                        <div class="d-flex column" v-if="contactSelectedToSee._id === contact._id"><!--v-if="contactSelectedToSee._id === contact._id"-->

                            <!--Info básica-->
                            <div class="my-10">
                                <!--Email-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Email</div>

                                    <div class="text" data-size="13" v-if="contact.email">{{ contact.email }}</div>
                                    <div class="text opacity-5" v-else>-</div>
                                </div>

                                <!--Telefono-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Teléfono</div>

                                    <div class="text" data-size="13" v-if="contact.phone">{{ contact.phone }}</div>
                                    <div class="text opacity-5" v-else>-</div>
                                </div>
                            </div>

                            <!--Otra info-->
                            <div class="my-10">
                                <!--Cuenta-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Cuenta</div>

                                    <div class="text" data-size="13">{{ contact.account ? contact.account.name : '-' }}</div>
                                </div>

                                <!--Cargo-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Cargo</div>

                                    <div class="text" data-size="13">{{ contact.position ? contact.position : '-' }}</div>
                                </div>
                            </div>

                            <!--Botones-->
                            <div class="d-flex column" data-gap="8">

                                <div v-on:click="actionLink('/contacts/'+ contact._id)" class="custom-button w-100" data-bg="principal" data-mode="outline" data-align="center" data-size="small"  data-weight="700"><i class="fas fa-eye mr-5"></i> Ver contacto</div>

                                <div v-on:click="toggleArchiveContact(contact)" v-if="!isReadOnly" class="custom-button w-100" data-bg="principal" data-mode="outline" data-align="center" data-size="small"  data-weight="700"><i class="fa-regular mr-5" v-bind:class="isSeeingArchived ? 'fa-box-open' : 'fa-box-archive'"></i> {{ isSeeingArchived ? 'Desarchivar' : 'Archivar' }}</div>

                                <div v-on:click="deleteContact(contact)" v-if="!isReadOnly" class="custom-button w-100" data-bg="rojo" data-mode="outline" data-align="center" data-size="small" data-weight="700"><i class="fa-regular fa-trash mr-5"></i> Eliminar</div>
                            </div>

                        </div>


                        <div class="separator my-10" v-if="contactKey < filteredContacts.length - 1"></div>

                    </div>

                    <div class="opacity-5" data-size="13" v-if="contacts && contacts.length === 0" data-align="center">¡No hay ningún contacto {{ isSeeingArchived ? 'archivado' : 'sin archivar' }}!</div>

                </div>

                <!--Paginación-->
                <div class="d-grid my-10" data-column="2" data-layout="auto1">

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
        </div>


        <!--Estilo de ordenador-->
        <div class="desktop-item d-flex">
            <!--Contenido listado-->
            <div class="content-white d-flex column mr-10" v-bind:class="{'contact-selected': contactSelectedToSee !== ''}">

                <!--Header-->
                <div class="d-flex justify-between align-center">

                    <div class="d-flex" >
                        <!--Título-->
                        <div class="text" data-size="30" data-weight="700">{{ $route.meta.title }} <span data-size="12" class="my-auto opacity-5"> ( {{ totalContacts }} {{ totalContacts === 1 ? 'contacto' : 'contactos'}} )</span></div>

                        <div class="d-grid" data-column="2" v-if="contacts && contacts.length > 0">

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


                        <!--Botones-->
                    <div class="d-flex" data-gap="15">
                        <div class="d-flex" >
                            <div class="custom-select no-hover" v-on:click.stop="isSeeingMassiveLoad = true"
                                 v-bind:class="{ 'seeing': isSeeingMassiveLoad }">

                                <div class="custom-button" data-size="regular" data-bg="principal"><i
                                    class="far fa-plus"></i></div>

                                <div class="select-content form w-260-px">

                                    <!--Enviar correo masivo a usuarios seleccionado-->
                                    <p class="text mt-3" @click="getEmailsToMassive" style="cursor: pointer;">
                                        <i class="fas fa-paper-plane-top ml-4 mr-10"></i>
                                        Enviar correo masivo
                                    </p>

                                </div>
                            </div>
                        </div>

                        <div class="custom-button" data-size="regular" data-bg="principal" v-if="!isReadOnly" v-on:click="actionLink('/contacts/register')">Añadir contacto</div>
                    </div>
                </div>


                <!--Cambio entre contactos archivados y no archivados-->
                <div class="mt-30 select-line">

                    <div class="text pointer" data-align="center" v-bind:class="{'selected': !isSeeingArchived}" v-on:click="changeIsSeeingArchived(false)">Agenda</div>

                    <div class="text pointer" data-align="center" v-bind:class="{'selected': isSeeingArchived}" v-on:click="changeIsSeeingArchived(true)">Archivado</div>

                    <div class="before-search">
                        <!--<div class="custom-button " data-size="small" data-bg="azul" data-mode="translucent" v-on:click="isSeeingFiltersBox = !isSeeingFiltersBox">{{ isSeeingFiltersBox ? 'Ocultar' : 'Mostrar' }} filtros</div>-->
                    </div>

                    <div class="search-div d-flex">

                        <!--Barra de busqueda-->
                        <div class="search-bar w-100">

                            <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                            <input type="text" placeholder="Buscar un contacto..." v-model="searchContactText" v-on:keyup="fetchAllContacts" @blur="saveContactText">
                        </div>

                        <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>

                    </div>

                </div>


                <!--LISTADO-->

                <!--Header-->
                <div class="contact four header-card mt-30">

                    <!--checkbox-->
                    <div class="custom-checkbox pointer" v-on:click="toggleSelectAllContact" v-bind:class="{'selected': areAllSelected && contacts.length > 0}"></div>

                    <div class="d-flex column">

                        <div class="d-flex text">
                            <p class="text mr-5" data-weight="600" v-on:click="selectNewOrderType('name')">Nombre</p>

                            <i class="fas my-auto pointer" v-on:click="selectNewOrderType('name')" v-bind:class="this.filters.radio.sortBy.checked === 0 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 1 ? 'fa-sort-up' : 'fa-sort')"></i>
                        </div>

                        <p class="text" data-size="10">Apellidos</p>
                    </div>

                    <div class="d-flex column">

                        <div class="d-flex text">
                            <p class="text mr-5" data-weight="600" v-on:click="selectNewOrderType('account')">Cuenta</p>

                            <!--<i class="fas my-auto pointer" v-on:click="selectNewOrderType('account')" v-bind:class="this.$cookies.get('filters')['contacts']['sortBy'] === 2 ? 'fa-sort-down' : (this.$cookies.get('filters')['contacts']['sortBy'] === 3 ? 'fa-sort-up' : 'fa-sort')"></i>-->
                        </div>

                        <p class="text" data-size="10">Cargo</p>
                    </div>

                    <div class="d-flex column ">
                        <div class="d-flex text">
                            <p class="text mr-5" data-weight="600" v-on:click="selectNewOrderType('email')" >Email</p>

                            <i class="fas my-auto pointer" v-on:click="selectNewOrderType('email')" v-bind:class="this.filters.radio.sortBy.checked === 4 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 5 ? 'fa-sort-up' : 'fa-sort')"></i>
                        </div>

                        <p class="text" data-size="10">Teléfono</p>
                    </div>

                    <div class="d-flex column ">
                        <div class="d-flex text">
                            <p class="text mr-5" data-weight="600" v-on:click="selectNewOrderType('createdDate')" >Fec. creación</p>

                            <i class="fas my-auto pointer" v-on:click="selectNewOrderType('createdDate')" v-bind:class="this.filters.radio.sortBy.checked === 6 ? 'fa-sort-down' : (this.filters.radio.sortBy.checked === 7 ? 'fa-sort-up' : 'fa-sort')"></i>
                        </div>
                    </div>

                    <div class="d-flex" v-if="contactsSelected.length > 0">

                        <div class="ml-50 mr-10 text pointer" v-on:click="toggleArchiveSelectedContacts(contactsSelected)"><i class="far" v-bind:class="isSeeingArchived ? 'fa-box-open' : 'fa-box-archive'"></i></div>

                        <div class="mx-10 text pointer" data-color="rojo" v-on:click="deleteAllSelectedContact()"><i class="far fa-trash"></i></div>
                    </div>
                </div>

                <div class="separator my-10"></div>

                <!--Contenido-->
                <div v-if="isLoading">
                    <div class="d-flex column" v-for="i of 10">
                        <div  class="contact four pointer">
                            <div class="loading mx-10 h-20-px" v-for="i of 7"></div>
                        </div>
                        <div class="separator my-10"></div>
                    </div>
                </div>

                <div v-else-if="contacts && contacts.length > 0">

                    <div>
                        <contact-card-component v-for="contact in contacts" :contact="contact" :contactSelectedToSee="contactSelectedToSee" :contactsSelected="contactsSelected" :isSeeingArchived="isSeeingArchived" :isReadOnly="isReadOnly" @toggleArchiveContact="toggleArchiveContact" @deleteContact="deleteContact" @seeContactInfo="seeContactInfo" @toggleSelectContact="toggleSelectContact"></contact-card-component>
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



                <div v-else class="opacity-5" data-align="center">¡No hay ningún contacto {{ isSeeingArchived ? 'archivado' : 'sin archivar' }}!</div>


                <!--Flotante filtros-->
                <div class="filters-box small-pages d-flex column justify-between" v-if="isSeeingFiltersBox">

                    <div class="filters">
                        <!--Header-->
                        <div class="d-flex justify-between">

                            <p class="text opacity-7" data-size="20" data-weight="600">Filtros</p>

                            <i class="fas fa-x text my-auto pointer" data-size="20" data-weight="600" v-on:click="isSeeingFiltersBox = false"></i>
                        </div>

                        <!--Cada uno de los filtros-->
                        <div class="mt-30 ml-20">

                            <!--Cuentas seleccionadas-->
                            <div class="d-flex my-40">
                                <div class="text">Cuentas:</div>

                                <div class="custom-select no-hover" v-on:click.stop="seeFilters('acc')"  v-bind:class="{'seeing': isSeeingFiltersPc.acc}" v-if="filtersObtained.accounts && filtersObtained.accounts.length > 0">



                                    <div class="ml-10" data-color="azul">{{ getAccountFilterTitle }}<i class="fas fa-chevron-down ml-10"></i></div>




                                    <div class="select-content left form">

                                        <div class="form-group ">
                                            <div class="input-group">
                                                <input data-size="12" v-model="searchFilters.account" type="text" placeholder="Busca tu prod. de comerc. ...">
                                            </div>
                                        </div>


                                        <div class="d-flex align-center">






                                            <div class="custom-checkbox mr-10" v-on:click="toggleAllVisibility('accounts')" v-bind:class="{ 'selected': areAllAccountsActives }"></div>



                                            <div class="text">Todos</div>























                                        </div>
                                        <div v-for="account in filtersFiltered.accounts" class="d-flex align-center">

                                            <div class="custom-checkbox mr-10" v-on:click="toggleVisibility(account)" v-bind:class="{ 'selected': account.active }"></div>



                                            <div class="text">{{ account.name }}</div>




                                        </div>

                                    </div>
                                </div>

                                <div v-else class="ml-10" data-size="13" data-color="azul">0 cuentas</div>


                            </div>



                            <!--Ordenamiento-->
                            <!--<div class="d-flex my-40">
                                <div class="text">Ordenar:</div>

                                <div class="custom-select no-hover" v-on:click.stop="seeFilters('sort')" v-bind:class="{'seeing': isSeeingFiltersPc.sort}">

                                    <div class="ml-10" data-color="azul">{{ orderTypeSelected }} <i class="fas fa-chevron-down ml-10"></i></div>

                                    <div class="select-content left">
                                        <div v-for="orderType in filters.radio.sortBy.data" class="d-flex align-center">

                                            <div class="custom-checkbox mr-10" v-on:click="selectOrderType(orderType)" v-bind:class="{ 'selected': orderType.value === $cookies.get('filters')['contacts']['sortBy'] }"></div>

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

            <!--Info contacto seleccionado-->
            <div v-if="contactSelectedToSee !== ''" class="info-content">

                <!--Iniciales y nombre-->
                <div class="d-flex">
                    <div class="d-flex justify-center mr-20">
                        <div class="d-flex justify-center mr-10">
                            <div class="initials" data-size="17" v-if="!contactSelectedToSee.profileImage && contactSelectedToSee.name">{{ getInitials(contactSelectedToSee.name.first) }}</div>
                            <div class="initials" data-style="initials" v-bind:class="{image: contactSelectedToSee.profileImage}" v-else>
                                <img :src="'/assets/contact_images/' + contactSelectedToSee.profileImage" class="profile-image">
                            </div>
                        </div>
                    </div>

                    <!--Nombre y apellidos-->
                    <div class="d-flex column">
                        <p data-color="azul" data-weight="700" data-size="20">{{ contactSelectedToSee.name.first }} {{ contactSelectedToSee.name.second }}</p>
                        <p class="text" data-size="13">{{ contactSelectedToSee.surname.first }} {{ contactSelectedToSee.surname.second }}</p>
                    </div>
                </div>

                <!--Datos de contacto-->
                <div class="my-20">

                    <!--Titulo-->
                    <p class="text" data-size="20" data-weight="700">Datos de contacto</p>

                    <div class="separator my-0"></div>

                    <!--Email-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-weight="600">Email</div>

                        <div class="text" data-weight="300" v-if="contactSelectedToSee.email">{{ contactSelectedToSee.email }}</div>
                        <div class="text opacity-5" v-else>-</div>
                    </div>

                    <!--Telefono-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-weight="600">Teléfono</div>

                        <div class="text" data-weight="300" v-if="contactSelectedToSee.phone">{{ contactSelectedToSee.phone }}</div>
                        <div class="text opacity-5" v-else>-</div>
                    </div>
                </div>

                <!--Cuentas-->
                <div class="my-20" v-if="contactSelectedToSee.accounts && contactSelectedToSee.accounts.length > 0 && !(contactSelectedToSee.accounts.length === 1 && contactSelectedToSee.accounts[0] === null)">

                    <!--Titulo-->
                    <p class="text" data-size="18" data-weight="600">Cuentas</p>

                    <div class="separator my-0"></div>

                    <div class="d-flex my-20" v-for="account in contactSelectedToSee.accountsInfoToShow">

                        <div class="d-flex justify-center mr-20 w-100" v-if="account !== null">
                            <div class="initials twoBlues small mr-20" data-size="25" data-weight="700" v-if="account.name">{{ getInitials(account.name) }}</div>

                            <div class="d-flex column my-auto">
                                <p data-color="azul" data-weight="700" data-size="18">{{ account.name }}</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small" v-on:click="actionLink(`/accounts/${account._id}`)"><i class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>
                </div>

                <!--Tareas-->
                <div class="my-20" v-if="contactSelectedToSee.tasks && contactSelectedToSee.tasks.length > 0">

                    <!--Titulo-->
                    <p class="text" data-size="18" data-weight="600">Tareas</p>

                    <div class="separator my-0"></div>

                    <div class="d-flex my-10" v-for="task in contactSelectedToSee.tasks">
                        <div class="d-flex justify-center mr-20 w-100">

                            <div class="initials twoBlues small mr-20" data-size="18" data-weight="700">{{ getInitials(task.subject) }}</div>

                            <div class="d-flex column my-auto">
                                <p data-color="azul" data-weight="700" data-size="15">{{ task.subject }}</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="small" v-on:click="actionLink(`/tasks/${task._id}`)"><i class="fas fa-arrow-right-long"></i></div>
                        </div>
                    </div>
                </div>


                <!--Usuario creador-->
                <div v-if="contactSelectedToSee.createdBy">
                    <p class="text opacity-5" data-size="13" data-weight="600"><i class="fas fa-user mr-5"></i> Creador del contacto</p>

                    <div class="separator my-0"></div>

                    <div class="d-flex justify-center mr-20 my-10 w-100">
                        <div class="initials verySmall mr-20 my-auto" data-style="initials" v-bind:class="{image: contactSelectedToSee.createdByInfoToShow.profileImage}">
                            <img :src="'/assets/profile_images/' + contactSelectedToSee.createdByInfoToShow.profileImage" class="profile-image">
                        </div>

                        <div class="d-flex column my-auto">
                            <p class="text opacity-5" data-weight="600" data-size="14">{{ contactSelectedToSee.createdByInfoToShow.firstName }}</p>
                        </div>

                        <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="tiny" v-on:click="actionLink((this.basicData.userLogged._id === contactSelectedToSee.createdByInfoToShow._id ? '/profile' : `/users/${contactSelectedToSee.createdBy._id}`))"><i class="fas fa-arrow-right-long"></i></div>
                    </div>
                </div>

                <!--separador-->
                <div class="separator my-10"></div>

                <!--Info contacto-->
                <div class="mr-auto">
                    <div v-on:click="actionLink('/contacts/'+ contactSelectedToSee._id)" class="custom-button mr-10" data-size="medium" data-bg="azul" data-mode="outline" data-color="azul">Editar</div>
                </div>

            </div>

            <!--<div v-else class="info-content">

                <div class="d-flex">
                    <div class="d-flex justify-center mr-20 loading w-20 h-70">
                        <div class="" data-size="25"></div>
                    </div>

                    <div class="d-flex column loading w-100 h-25">
                        <p class=""  data-color="azul" data-weight="700" data-size="20"></p>
                    </div>
                </div>

                <div class="my-20">

                    <p class="loading w-100 h-30" data-size="20" data-weight="700"></p>

                    <div class="separator loading my-10"></div>

                    <div class="d-flex justify-between my-10">
                        <div class="loading w-30 h-25"></div>

                        <div class="loading w-55 h-25"></div>
                    </div>

                    <div class="d-flex justify-between my-10">
                        <div class="loading w-40 h-25"></div>

                        <div class="loading w-40 h-25"></div>
                    </div>

                    <div class="d-flex justify-between my-10">
                        <div class="loading w-40 h-25"></div>

                        <div class="loading w-40 h-25"></div>
                    </div>
                </div>


                <div class="my-20" >

                    <p class="loading w-100 h-30" data-size="20" data-weight="700"></p>

                    <div class="separator loading my-10"></div>

                    <div class="d-flex my-20 w-100 h-90">
                        <div class="d-flex justify-center mr-20 w-100 h-90">
                            <div class="loading w-20 h-70" data-size="25" data-weight="700"></div>

                            <div class="d-flex column my-auto ml-20 loading w-100 h-25">
                                <p class="" data-color="azul" data-weight="700" data-size="18"></p>
                            </div>
                        </div>
                    </div>

                    <div class="loading w-100 h-25" data-style="twoBlue"></div>
                </div>
            </div>-->
        </div>
    </div>
</template>

<script>

export default {
    name: "ContactListComponent",
    props:['basicData'],
    data(){
        return{
            contacts: {},
            contactsAll: {},
            contactsSelected:[],
            filteredContacts:[],
            isLoading: true,
            isSeeingArchived: false,
            isSeeingFilters: false,
            isSeeingFiltersBox: false,
            isSeeingFiltersPc:{
                acc: false,
                sort: false



            },
            contactSelectedToSee: '',
            filters: {
                checkbox: {
                    accountsAvailable: {
                        title: 'Cuentas',
                        data: []
                    }
                },
                radio: {
                    sortBy: {
                        title: 'Ordenar por',
                        checked: 7,
                        data: [
                            {
                                title: 'Nombre (A-Z)',
                                value: 0,
                            },
                            {
                                title: 'Nombre (Z-A)',
                                value: 1,
                            },
                            {
                                title: 'Email (A-Z)',
                                value: 2,
                            },
                            {
                                title: 'Email (Z-A)',
                                value: 3,
                            },
                            {
                                title: 'Cuenta (A-Z)',
                                value: 4,
                            },
                            {
                                title: 'Cuenta (Z-A)',
                                value: 5
                            },
                            {
                                title: 'Más antiguo',
                                value: 4
                            },
                            {
                                title: 'Más reciente',
                                value: 5
                            },

                        ]
                    }
                }
            },
            orderTypeSelected: '', //sort
            searchContactText: '',
            searchFilters:{
                account: '',
            },
            filtersObtained:{
                accounts: [],


            },
            accountsInfo:{},

            currentPage: 1,
            perPage: 50,
            totalPages: 1,
            perPageOptions: [ 50, 100, 200 ],
            totalContacts: 0,
            isSeeingMassiveLoad: false
        }
    },
    mounted(){

        //Saco filtros
        this.perPage = this.$cookies.get('filters')['contacts']['perPage']

        if (this.$cookies.get('filters')['contacts'] && this.$cookies.get('filters')['contacts']['searchText'] !== '')
            this.searchContactText = this.$cookies.get('filters')['contacts']['searchText']

        //Saco contactos
        if(this.basicData.userLogged && this.basicData.userLogged._id && this.basicData.userList) {
            this.fetchAllContacts(true)
            this.getOrderTypeSelected()
        }

    },
    watch:{
        "basicData.userLogged"(){
            this.fetchAllContacts(true)
            this.getOrderTypeSelected()
        },
        isSeeingArchived(){
            this.contactsSelected = [];
        }



    },
    methods:{
        async fetchAllContacts(filtersFromCookies){

            //establezco los filtros de la manera de la que se va a filtrar en la consulta
            this.setFiltersToSend()

            //Para ver si la primera vez que se entra tiene filtros ya guardados en la cookie
            let hasFilteredFromCookies = this.$cookies.get('filters')['contacts']['accounts'].length > 0;

            //Muestro vista de carga
            this.isLoading = true;

            await axios.post(`/api/contacts/index/${this.basicData.userLogged._id}`, {
                userList: JSON.stringify(this.basicData.userList),
                filters: this.$cookies.get('filters')['contacts'],
                sortType: this.filters.radio.sortBy.checked,
                searchContactText: this.searchContactText,
                page: this.currentPage,
                perPage: this.perPage,
                filtersFromCookies: (filtersFromCookies === true && hasFilteredFromCookies),
                isSeeingArchived: this.isSeeingArchived,
                isFirstTime: filtersFromCookies === true
            })
                .then((res) => {
                    this.contacts = res.data.contacts;


                    //Establezco los filtros para filtrar si no estaban ya establecidos ( primera vez )
                    if (res.data.filtersObtained !== null && res.data.filtersObtained[0]) {



                        this.filtersObtained.accounts = res.data.filtersObtained[0].accounts;

                        this.accountsInfo = res.data.filtersObtained.accountsInfo


















                        //Establezco los filtros para usar
                        this.setFilters(true);
                    }


                    //Establezco el número total de páginas
                    this.totalPages = Math.ceil(res.data.totalResults / this.perPage);

                    //Establezco el número total de pedidos
                    this.totalContacts = res.data.totalResults;
                })
                .catch((err) =>  {
                    console.log(err)
                })
                .finally(() => {
                    //Termino el estado de carga
                    this.isLoading = false;
                })
        },
        async fetchAllContactsWithoutPagination(filtersFromCookies){

            //establezco los filtros de la manera de la que se va a filtrar en la consulta
            this.setFiltersToSend()

            //Para ver si la primera vez que se entra tiene filtros ya guardados en la cookie
            let hasFilteredFromCookies = this.$cookies.get('filters')['contacts']['accounts'].length > 0;

            //Muestro vista de carga
            this.isLoading = true;

            await axios.post(`/api/contacts/index/${this.basicData.userLogged._id}`, {
                userList: JSON.stringify(this.basicData.userList),
                filters: this.$cookies.get('filters')['contacts'],
                sortType: this.filters.radio.sortBy.checked,
                searchContactText: this.searchContactText,
                page: this.currentPage,
                perPage: this.perPage,
                filtersFromCookies: (filtersFromCookies === true && hasFilteredFromCookies),
                isSeeingArchived: this.isSeeingArchived,
                isFirstTime: filtersFromCookies === true,
                paginate: false
            })
                .then((res) => {
                    this.contactsAll = res.data.contacts;
                })
                .catch((err) =>  {
                    console.log(err)
                })
                .finally(() => {
                    //Termino el estado de carga
                    this.isLoading = false;
                })
        },
        resetSearch(){

            this.searchContactText = ''

            //Quito de cookies
            let cookies = this.$cookies.get('filters');

            cookies['contacts']['searchText'] = '';

            this.$cookies.set('filters', cookies);

            this.fetchAllContacts()
        },
        changePageSize() {

            let cookiesFilter = this.$cookies.get('filters');

            cookiesFilter['contacts']['perPage'] = event.target.value;

            this.$cookies.set('filters', JSON.parse(JSON.stringify(cookiesFilter)))

            this.currentPage = 1;

            this.fetchAllContacts()
        },
        setFiltersToSend() {

            let extractActiveCodes = (items) =>
                items.filter(item => item.active).map(item => item.code);

            // Obtener la cookie o inicializarla si no existe
            let cookiesFilter = this.$cookies.get('filters') || {};
            cookiesFilter.contacts = cookiesFilter.contacts || {};

            // Verificar si hay cuentas activas y actualizarlas
            if (this.filtersObtained['accounts'] && this.filtersObtained['accounts'].length > 0) {
                cookiesFilter.contacts['accounts'] = extractActiveCodes(this.filtersObtained['accounts']);





            }

            // Guardar en la cookie
            try {
                this.$cookies.set('filters', JSON.parse(JSON.stringify(cookiesFilter)));
            } catch (error) {
                console.error('Error al guardar en cookies: ', error);
            }

            // Verificar si se guarda correctamente
            let updatedFilters = this.$cookies.get('filters');
        },
        setFilters(hasFilteredFromCookies){
            //ESTABLEZCO LOS FILTROS PARA USAR

            //Agentes
            this.filtersObtained.accounts = this.filtersObtained.accounts.map(accountId => {

                let account = this.accountsInfo.find(acc => acc._id === accountId);

                let fromCookies = hasFilteredFromCookies && this.$cookies.get('filters')['contacts']['accounts'].length > 0;
                let isActive = fromCookies && this.$cookies.get('filters')['contacts']['accounts'].includes(account._id);

                return account ? {
                    code: account._id,
                    name: account.name ,
                    active: fromCookies ? isActive : true
                } : null;
            }).filter(acc => acc !== null).sort((a, b) => a.name.localeCompare(b.name));
        },
        setFiltersOld(){ //no se usa ya

            let contacts = JSON.parse(JSON.stringify(this.contacts))

            //optimizar

            //Recorro cada contacto
            for (let contact of contacts) {

                //Si tiene alguna cuenta
                if (contact['accounts'] && contact['accounts'].length > 0){

                    //Recorro la cuenta
                    for (let account of contact['accounts']){


                        let exists = this.filters.checkbox.accountsAvailable.data.some(accountFilter => accountFilter.title === account['name'])

                        //Compruebo que no exista el email ya
                        if(!exists){

                            let status = {
                                title: account['name'],
                                active: true
                            }

                            this.filters.checkbox.accountsAvailable.data.push(status);
                        }
                    }


                }else{

                    let exists = this.filters.checkbox.accountsAvailable.data.some(contactFilter => contactFilter.title === 'Sin cuenta')

                    if (!exists){
                        let status = {
                            title: 'Sin cuenta',
                            active: true
                        }

                        this.filters.checkbox.accountsAvailable.data.push(status);
                    }
                }


                //ORDENO LAS CUENTAS

                this.filters.checkbox.accountsAvailable.data.sort((a, b) => a.title.localeCompare(b.title));

            }
        },
        resetFilters(){

            //Pongo cuentas en active true
            this.filtersObtained.accounts.forEach(acc => acc.active = true);




            this.fetchAllContacts()
        },
        getInitials(name){

            if (name){
                let nameSplited = name.split(/\s+/)

                let initials = nameSplited[0][0];

                if (nameSplited[1])
                    initials += nameSplited[1][0];

                return initials
            }
        },
        getOrderTypeSelected(){
            this.orderTypeSelected =  this.filters.radio.sortBy.data[this.filters.radio.sortBy.checked].title
        },
        selectNewOrderType(orderType){

            //Recorro los tipos de ordenacion y los pongo todos a false menos el que se ha seleccionado

            switch(orderType) {

                case 'name':

                    if (this.filters.radio.sortBy.checked !== 0 && this.filters.radio.sortBy.checked !== 1)
                        this.filters.radio.sortBy.checked = 0
                    else if (this.filters.radio.sortBy.checked === 0)
                        this.filters.radio.sortBy.checked = 1
                    else if (this.filters.radio.sortBy.checked === 1)
                        this.filters.radio.sortBy.checked = 7

                    break;

                case 'account':

                    if (this.filters.radio.sortBy.checked !== 2 && this.filters.radio.sortBy.checked !== 3)
                        this.filters.radio.sortBy.checked = 2
                    else if (this.filters.radio.sortBy.checked === 2)
                        this.filters.radio.sortBy.checked = 3
                    else if (this.filters.radio.sortBy.checked === 3)
                        this.filters.radio.sortBy.checked = 7


                    break;

                case 'email':

                    if (this.filters.radio.sortBy.checked !== 4 && this.filters.radio.sortBy.checked !== 5)
                        this.filters.radio.sortBy.checked = 4
                    else if (this.filters.radio.sortBy.checked === 4)
                        this.filters.radio.sortBy.checked = 5
                    else if (this.filters.radio.sortBy.checked === 5)
                        this.filters.radio.sortBy.checked = 7

                    break;

                case 'createdDate':

                    if (this.filters.radio.sortBy.checked !== 6 && this.filters.radio.sortBy.checked !== 7)
                        this.filters.radio.sortBy.checked = 6
                    else if (this.filters.radio.sortBy.checked === 6)
                        this.filters.radio.sortBy.checked = 7
                    else if (this.filters.radio.sortBy.checked === 7)
                        this.filters.radio.sortBy.checked = 6

                    break;
            }


            this.isSeeingFiltersPc.sort = false;

            //this.getOrderTypeSelected()

            //Recargo los contactos
            this.fetchAllContacts();

        },
        filterContacts(){

            this.filteredContacts = []

            let contacts = JSON.parse(JSON.stringify(this.contacts))

            let accountsToSee = this.filters.checkbox.accountsAvailable.data.filter((account) => account.active === true);


            for (let key in contacts) {

                let contact = contacts[key];


                let ContactSearch = this.removeAccents([contact.name.first, contact.name.second, contact.surname.first, contact.surname.second, contact.email].join('').replace(' ', '').toLowerCase());

                //Compruebo si al menos una de las cuentas del contacto estan visibles
                let hasVisibleAccounts = false

                contact.accounts.forEach((account) => {

                    this.filters.checkbox.accountsAvailable.data.find(function(acc) {
                        if ((acc.title === account.name) && acc.active === true)
                            hasVisibleAccounts = true
                    });
                })

                //Si no tiene ninguna cuenta
                if (contact.accounts.length === 0){

                    //Compruebo si en los filtros "Sin cuenta esta activo"
                    this.filters.checkbox.accountsAvailable.data.find(function(acc) {

                        if ((acc.title === 'Sin cuenta') && acc.active === true)
                            hasVisibleAccounts = true
                    });

                }

                if (ContactSearch.includes(this.removeAccents(this.searchContactText.replace(' ', '').toLowerCase())) && hasVisibleAccounts) this.filteredContacts.push(contact)

            }


            //ordeno
            if (Object.keys(this.filteredContacts).length > 0) {

                switch (this.filters.radio.sortBy.checked) {
                    case 0:
                        //por nombre (A-Z)
                        this.filteredContacts = this.filteredContacts.sort((a, b) => {
                            let aFullName = (a.name.first + a.name.second) + (a.surname.first + a.surname.second)
                            let bFullName = (b.name.first + b.name.second) + (b.surname.first + b.surname.second)

                            if (aFullName < bFullName) return -1;
                            if (aFullName > bFullName) return 1;
                            return 0;
                        });

                        break;

                    case 1:
                        // por nombre (Z-A)
                        this.filteredContacts = this.filteredContacts.sort((a, b) => {
                            let aFullName = (a.name.first + a.name.second) + (a.surname.first + a.surname.second)
                            let bFullName = (b.name.first + b.name.second) + (b.surname.first + b.surname.second)

                            if (aFullName < bFullName) return 1;
                            if (aFullName > bFullName) return -1;
                            return 0;
                        });

                        break;
                    case 2:
                        // por email (A-Z)
                        this.filteredContacts = this.filteredContacts.sort((a, b) => {
                            if (a.email < b.email) return -1;
                            if (a.email > b.email) return 1;
                            return 0;
                        });

                        break;
                    case 3:
                        // por email (Z-A)
                        this.filteredContacts = this.filteredContacts.sort((a, b) => {
                            if (a.email < b.email) return 1;
                            if (a.email > b.email) return -1;
                            return 0;
                        });

                        break;
                    case 4:
                        // por cuenta (A-Z)
                        this.filteredContacts = this.filteredContacts.sort((a, b) => {

                            if (a.account && b.account){
                                if (a.account['name'] < b.account['name']) return 1;
                                if (a.account['name'] > b.account['name']) return -1;
                            }
                            return 0;
                        });

                        break;
                    case 5:
                        // por cuenta (Z-A)
                        this.filteredContacts = this.filteredContacts.sort((a, b) => {

                            if (a.account && b.account){
                                if (a.account['name'] < b.account['name']) return -1;
                                if (a.account['name'] > b.account['name']) return 1;
                            }

                            return 0;
                        });

                        break;
                    case 6:
                        // más antiguo
                        this.filteredContacts = this.filteredContacts.sort((a, b) => {

                            let aDate = new Date(a.createdAt);
                            let bDate = new Date(b.createdAt);

                            if (aDate < bDate) return -1;
                            if (aDate > bDate) return 1;

                            return 0;
                        });

                        break;
                    default:
                        //más reciente
                        this.filteredContacts = this.filteredContacts.sort((a, b) => {

                            let aDate = new Date(a.createdAt);
                            let bDate = new Date(b.createdAt);

                            if (aDate < bDate) return 1;
                            if (aDate > bDate) return -1;

                            return 0;
                        });

                        break;
                }
            }
        },
        async toggleArchiveContact(contact){
            await axios.post(`/api/contacts/toggleArchiveContact/${contact._id}`, { isForArchiving: !this.isSeeingArchived})
                .then((res) => {
                    this.fetchAllContacts()
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        async toggleArchiveSelectedContacts(contact){
            await axios.post(`/api/contacts/toggleArchiveSelectedContacts`, { idsToToggle: this.contactsSelected, isForArchiving: !this.isSeeingArchived})
                .then((res) => {
                    this.contactsSelected = []
                    this.fetchAllContacts()
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        deleteContact(contact){

            Swal.fire({
                icon: 'warning',
                title: '¿Estas seguro?',
                text: 'Si sigues con esta acción no podras revertirla. Además se eliminara de todo en lo que este vinculada.',
                confirmButtonText: 'Sí',
                showConfirmButton: true,
                cancelButtonText: 'No',
                showCancelButton: true
            }).then((res) => {
                if (res.isConfirmed){

                    axios.delete(`/api/contacts/${contact['_id']}`)
                        .then((res) => {
                            this.fetchAllContacts();

                            if (this.contactSelectedToSee._id === contact['_id'])
                                this.contactSelectedToSee = '';
                        })
                        .catch((err) => {
                            console.log(err)
                        })
                }
            })

        },
        deleteAllSelectedContact(){
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

                    axios.post('/api/contacts/deleteAllSelected', {idsToRemove: this.contactsSelected})
                        .then((res) => {
                            this.contactsSelected = [];
                            this.fetchAllContacts();
                        })
                        .catch((err) => {
                            console.log(err)
                        })
                }
            })
        },
        toggleSelectContact(contact){

            //Compruebo si esta seleccionado o no el contacto
            if (this.contactsSelected.includes(contact._id)){
                let index = this.contactsSelected.indexOf(contact._id);
                this.contactsSelected.splice(index, 1);
            }else{
                this.contactsSelected.push(contact._id)
            }

        },
        toggleSelectAllContact(){

            let contactos = this.contacts

            //Si estan todos seleccionado los deselecciono, sino los selecciono todos
            if (this.contactsSelected && contactos &&  this.contactsSelected.length === contactos.length){
                //Todos estan seleccionados asi q los deseleccionoç
                this.contactsSelected = [];
            }else{
                //Selecciono todos, mirando no meter otra vez los que ya estan metidos
                contactos.forEach((contact) => {
                    if (this.contactsSelected.indexOf(contact._id) === -1) this.contactsSelected.push(contact._id);
                })
            }
        },
        selectOrderType(orderType){
            this.filters.radio.sortBy.checked = orderType.value

            this.getOrderTypeSelected()

            //Recargo los pedidos
            this.fetchAllContacts();
        },
        toggleVisibility(product, type){
            product.active = !product.active

            this.fetchAllContacts()
        },
        seeContactInfo(contact){

            //Compruebo si se esta clicando uno que ya se esta viendo
            if (this.contactSelectedToSee._id === contact._id){
                this.contactSelectedToSee = ''
            }else{

                //Si no tiene la info de las cuentas metidas se las meto
                if (!contact.accountsInfoToShow){
                    let accs = [];

                    //Saco la información para la cuenta de contact
                    contact.accounts.forEach(acc => {

                        //Busco su info
                        let accToSave = this.accountsInfo.find(accNow => accNow._id === acc._id)

                        accs.push(accToSave)
                    })

                    contact.accountsInfoToShow = accs;
                }


                //Si no tiene la info del creador se la meto
                if (!contact.createdByInfoToShow){

                    //Saco la info del usuario
                    if (this.basicData.userLogged._id === contact.createdBy){
                        contact.createdByInfoToShow = this.basicData.userLogged
                    }else{
                        contact.createdByInfoToShow = this.basicData.userList.find(user => user._id === contact.createdBy)
                    }

                }



                this.contactSelectedToSee = contact;
            }
        },
        seeFilters(type){

            //Cerrar selects
            this.hideCustomSelects()

            switch(type){
                case 'acc':
                    this.isSeeingFiltersPc.acc  = true;
                    break;

                case 'sort':
                    this.isSeeingFiltersPc.sort  = true;
                    break;







            }
        },
        saveContactText(){
            let cookies = this.$cookies.get('filters')

            cookies['contacts']['searchText'] = this.searchContactText

            this.$cookies.set('filters', cookies);
        },
        hideCustomSelects(){
            //Cierro todos los filtros
            this.isSeeingFiltersPc = {
                acc: false,
                order: false





            }
        },
        removeAccents(str) {
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        },
        toggleAllVisibility(type){

            let changeTo = false;

            switch (type){
                case 'accounts':
                    changeTo = !this.areAllAccountsActives;

                    this.filtersObtained.accounts.forEach(acc => {
                        acc.active = changeTo;
                    });

                    break;






            }

            this.fetchAllContacts()
        },
        changeIsSeeingArchived(to){

            this.isSeeingArchived = to;

            this.currentPage = 1;

            this.fetchAllContacts()
        },
        //PAGINACIÓN
        changePage(value){

            switch (value){
                case -1:

                    if (this.currentPage > 1){
                        this.currentPage--;

                        //recargo los pedidos
                        this.fetchAllContacts()
                    }

                    break;

                case 1:

                    if (this.currentPage < this.totalPages){
                        this.currentPage++;

                        //recargo los pedidos
                        this.fetchAllContacts()
                    }

                    break;
            }
        },
        getEmailsToMassive(){

            //Saco los correos de las cuentas sin duplicar
            this.fetchAllContactsWithoutPagination(true)
                .then(() => {
                    //Saco los correos de las cuentas y quito los duplicados
                    let emails = this.contactsAll.map(contact => contact.email).filter((email, index, self) => self.indexOf(email) === index && email !== '');

                    //Creo en el storage y lo mando
                    localStorage.setItem(
                        'emailsTemporaly',
                        JSON.stringify(emails)
                    );

                    //Redirijo a masivo de correos
                    this.$router.push('/tools?section=massiveEmail&withFilters=true')
                });

        },
        actionLink(route){
            this.$router.push(route)
        }
    },
    computed:{











        areAllSelected(){
            if (this.contacts) return this.contactsSelected.length === this.contacts.length
        },
        getAccountFilterTitle(){

            let totalActives = 0;

            this.filtersObtained.accounts.forEach((account) => {
                if (account.active) totalActives++;
            })

            if (this.contacts) return totalActives === this.filtersObtained.accounts.length ? 'Todas' : (totalActives + ' cuenta/s')
        },
        areAllAccountsActives(){
            if (this.filtersObtained.accounts && this.filtersObtained.accounts.length > 0){

                let activeAccountsCount = this.filtersObtained.accounts.filter(acc => acc.active).length;

                return activeAccountsCount === this.filtersObtained.accounts.length;
            }else return false
        },
        isReadOnly(){
            if (!this.basicData.userLogged)
                return true
            else (this.basicData.userLogged)
                return this.basicData.userLogged.permissions.includes('READONLY')
        },
        filtersFiltered(){

            let filteredFiltered = {
                'accounts': [],
            }

            //Agent
            let AccountSearch = this.searchFilters.account.replaceAll(' ', '').toLowerCase();

            this.filtersObtained.accounts.forEach((acc) => {
                let OptSearch = acc.name.replaceAll(' ', '').toLowerCase()

                if (OptSearch.includes(AccountSearch)) filteredFiltered.accounts.push(acc)
            })

            return filteredFiltered;
        }
    }
}
</script>

<style scoped>

</style>
