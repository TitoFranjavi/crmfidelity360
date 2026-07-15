<template>
    <div v-on:click="hideCustomSelects">

        <!--Estilo movil-->
        <div class="mobile-item">
            <div class="content-white">

                <!--Barra de busqueda-->
                <div class="search-div d-flex">

                    <div class="search-bar w-100">

                        <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                        <input type="text" data-size="14" placeholder="Buscar un usuario..." v-model="searchUserText">
                    </div>

                    <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>

                </div>


                <!--Filtros-->
                <div class="d-flex column my-20">

                    <p class="text" data-size="13" data-weight="600" v-on:click="isSeeingFilters = !isSeeingFilters">{{
                        isSeeingFilters ? 'Ocultar filtros' : 'Mostrar filtros' }}</p>


                    <div v-if="isSeeingFilters">

                        <div class="arrow-border arrow-top my-10" data-position="left"></div>

                        <!--Etiquetas seleccionadas-->
                        <div class="d-flex justify-between my-10">
                            <div class="text" data-size="13" data-weight="600">Tipo de usuario</div>

                            <div class="custom-select"
                                v-if="filters.checkbox.usersAvailable.data && filters.checkbox.usersAvailable.data.length > 0">

                                <div class="ml-10" data-size="13" data-color="azul">{{ getUserFilterTitle }}<i
                                        class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content" style="display:block !important; opacity:1 !important; visibility:visible !important;">
                                    <div v-for="label in filters.checkbox.usersAvailable.data"
                                        class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="label.active = !label.active"
                                            v-bind:class="{ 'selected': label.active }"></div>

                                        <div class="text">{{ label.title }}</div>

                                    </div>
                                </div>
                            </div>

                            <div v-else class="ml-10" data-size="13" data-color="azul">0 seleccionados</div>
                        </div>


                        <!--Ordenar-->
                        <div class="d-flex justify-between my-10">
                            <div class="text" data-size="13" data-weight="600">Ordenar</div>

                            <div class="custom-select">

                                <div class="ml-10" data-size="13" data-color="azul">{{ orderTypeSelected.title }} <i
                                        class="fas fa-chevron-down ml-10"></i></div>

                                <div class="select-content">
                                    <div v-for="orderType in filters.radio.sortBy.data" class="d-flex align-center">

                                        <div class="custom-checkbox mr-10" v-on:click="selectOrderType(orderType)"
                                            v-bind:class="{ 'selected': orderType.value === filters.radio.sortBy.checked }">
                                        </div>

                                        <div class="text">{{ orderType.title }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="separator mt-10"></div>
                    </div>
                </div>

                <!--Título-->
                <div class="d-flex justify-between">

                    <div class="text my-10" data-size="22" data-weight="700">Mi red</div>

                    <div v-if="canManage('users.create')" class="custom-button my-auto" data-size="small" data-bg="amarillo"
                        data-weight="600" v-on:click="actionLink('/users/register')"><i class="fas fa-plus"></i></div>
                </div>

                <!--Listado de usuarios-->
                <div class="d-flex column">

                    <div v-for="(user, userKey) in filteredUsers" class="my-5">

                        <!--Card-->
                        <div class="d-flex align-center pointer" v-on:click="seeUserInfo(user)">
                            <div class="d-flex justify-center mr-10">
                                <div class="initials" data-style="initials" v-bind:class="{ image: user.profileImage }">
                                    <img :src="'/assets/profile_images/' + user.profileImage" class="profile-image">
                                </div>
                            </div>

                            <div class="text ellipsis" data-weight="600">{{ user.firstName }}</div>

                            <div class="deploy-btn ml-10" data-round="15"
                                v-bind:class="{ 'selected': userSelectedToSee._id === user._id }">
                                <i class="fa-solid"
                                    v-bind:class="{ 'fa-chevron-down': userSelectedToSee._id !== user._id, 'fa-chevron-up': userSelectedToSee._id === user._id }"></i>
                            </div>
                        </div>

                        <!--Info card-->
                        <div class="d-flex column" v-if="userSelectedToSee._id === user._id">

                            <!--Info básica-->
                            <div class="my-10">
                                <!--Email-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Email</div>

                                    <div class="text" data-size="13">{{ user.email ? user.email : '-' }}</div>
                                </div>

                                <!--Telefono-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Teléfono</div>

                                    <div class="text" data-size="13">{{ user.phone ? user.phone : '-' }}</div>
                                </div>

                                <!--DNI-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">DNI</div>

                                    <div class="text" data-size="13" v-if="user.dni">{{ user.dni }}</div>
                                    <div class="text opacity-5" v-else>-</div>
                                </div>
                            </div>

                            <!--Otra info-->
                            <div class="my-10">
                                <!--Etiqueta-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Etiqueta</div>

                                    <div class="text" data-size="13">{{ user.label ? user.label : '-' }}</div>
                                </div>

                                <!--Género-->
                                <div class="d-flex justify-between">

                                    <div class="text" data-size="13" data-weight="600">Género</div>

                                    <div class="text" data-size="13">{{ user.gender ? user.gender : '-' }}</div>
                                </div>
                            </div>

                            <!--Botones-->
                            <div class="d-flex justify-around">
                                <div v-on:click="actionLink('/users/' + user._id)" class="custom-button w-30"
                                    data-bg="principal" data-mode="outline" data-align="center" data-size="small"
                                    data-weight="700"><i class="fas fa-gear"></i></div>

                                <div v-on:click="deleteUser(user)" class="custom-button w-30" data-bg="rojo"
                                    data-mode="outline" data-align="center" data-size="small" data-weight="700"
                                    v-if="!isReadOnly && canManage('users.delete')"><i class="fa-regular fa-trash"></i></div>
                            </div>
                        </div>

                        <div class="separator my-10" v-if="userKey < filteredUsers.length - 1"></div>
                    </div>

                    <div class="opacity-5" v-if="filteredUsers && filteredUsers.length === 0" data-align="center">¡No
                        hay ningún usuario!</div>
                </div>
            </div>
        </div>


        <!--Estilo de ordenador-->
        <div class="desktop-item d-flex">

            <!--Contenido listado-->
            <div class="content-white d-flex column mr-10 contact-selected">

                <!--Header-->
                <div class="d-flex justify-between align-center">

                    <!--Título-->
                    <div class="text" data-size="30" data-weight="700">{{ $route.meta.title }}</div>

                    <!--Botones-->
                    <div class="d-flex">

                        <!-- Añadir usuario -->
                        <div
                            v-if="canManage('users.create')"
                            class="custom-button"
                            data-size="regular"
                            data-bg="principal"
                            @click="actionLink('/users/register')"
                        >
                            Añadir usuario
                        </div>

                        <!-- Carga masiva / Excel -->
                        <div
                            v-if="canManage('users.massive')"
                            ref="massiveLoad"
                            class="custom-select no-hover"
                            @click.stop="toggleMassiveLoad"
                            :class="{ seeing: isSeeingMassiveLoad }"
                        >
                            <div
                                class="custom-button"
                                data-size="regular"
                                data-bg="principal"
                                style="margin-left: 5px;"
                            >
                                <i class="far fa-plus"></i>
                            </div>

                            <div v-if="isSeeingMassiveLoad" class="select-content form w-260-px">

                                <div class="d-flex mt-5">
                                    <p class="text" @click="$refs.inputExcel.click()">
                                        <i class="fa-solid fa-file-excel ml-4 mr-14"></i>
                                        Carga masiva
                                    </p>

                                    <input
                                        type="file"
                                        ref="inputExcel"
                                        style="display: none"
                                        accept=".xls, .xlsx, .csv"
                                        @change="handleBulkFile"
                                    />
                                </div>

                                <a
                                    class="text"
                                    href="/assets/templates/users.xlsx"
                                    download="Plantilla Usuarios"
                                >
                                    <i class="fas fa-file-arrow-down ml-4 mr-10" v-if="canManage('users.massive')"></i>
                                    Descargar plantilla
                                </a>

                                <p class="text mt-3" @click="exportUsersToExcel" style="cursor: pointer;">
                                    <i class="fas fa-file-export ml-4 mr-10"></i>
                                    Exportar a Excel
                                </p>
                            </div>
                        </div>

                    </div>




                </div>

                <!--Barra de busqueda-->
                <div class="mt-30 select-line">

                    <!--Barra de busqueda-->
                    <div class="search-div d-flex">

                        <div class="search-bar w-100">

                            <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                            <input type="text" placeholder="Buscar un usuario..." v-model="searchUserText">
                        </div>

                        <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>
                    </div>

                </div>


                <!--Seleccion de etiquetas y ordenación-->
                <div class="d-flex justify-between my-10">

                    <!--Etiquetas seleccionadas-->
                    <div class="d-flex">
                        <div class="text">Tipo de usuario:</div>

                        <div class="custom-select no-hover" v-on:click.stop="seeFilters('users')"
                            v-bind:class="{ 'seeing': isSeeingFiltersPc.users }"
                            v-if="filters.checkbox.usersAvailable.data && filters.checkbox.usersAvailable.data.length > 0">

                            <div class="ml-10" data-color="azul">{{ getUserFilterTitle }}<i
                                    class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div v-for="label in filters.checkbox.usersAvailable.data" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="label.active = !label.active"
                                        v-bind:class="{ 'selected': label.active }"></div>

                                    <div class="text">{{ label.title }}</div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="ml-10" data-size="13" data-color="azul">0 seleccionados</div>
                    </div>

                    <!--Odenación-->
                    <div class="d-flex">
                        <div class="text">Ordenar:</div>

                        <!--Contenedor cuentas-->
                        <div class="custom-select no-hover" v-on:click.stop="seeFilters('sort')"
                            v-bind:class="{ 'seeing': isSeeingFiltersPc.sort }">

                            <div class="ml-10" data-color="azul">{{ orderTypeSelected.title }} <i
                                    class="fas fa-chevron-down ml-10"></i></div>

                            <div class="select-content">
                                <div v-for="orderType in filters.radio.sortBy.data" class="d-flex align-center">

                                    <div class="custom-checkbox mr-10" v-on:click="selectOrderType(orderType)"
                                        v-bind:class="{ 'selected': orderType.value === filters.radio.sortBy.checked }">
                                    </div>

                                    <div class="text">{{ orderType.title }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>


                <!--LISTADO-->

                <!--Header-->
                <div class="contact header-card three mt-30">

                    <div class="d-flex column ml-75">
                        <p class="text" data-weight="600">Nombre</p>
                    </div>

                    <div class="d-flex column">
                        <p class="text" data-weight="600">Email</p>
                        <p class="text" data-size="10">Telefono</p>
                    </div>

                </div>

                <div class="separator my-10"></div>

                <user-card-component v-for="user in filteredUsers" :user="user" level="0"
                    :userSelectedToSee="userSelectedToSee" :usersSelected="usersSelected" :isReadOnly="isReadOnly" :basicData="basicData"
                    @deleteUser="deleteUser" @seeUserInfo="seeUserInfo"
                    @toggleSelectUser="toggleSelectUser"></user-card-component>


                <div class="opacity-5" v-if="filteredUsers && filteredUsers.length === 0" data-align="center">¡No hay
                    ningún usuario!</div>
            </div>

            <!--Info lateral-->
            <div v-if="userSelectedToSee !== ''" class="info-content">

                <!--Iniciales y nombre-->
                <div class="d-flex">
                    <div class="d-flex justify-center mr-20">
                        <div class="d-flex justify-center mr-10">
                            <div class="initials" data-style="initials"
                                v-bind:class="{ image: userSelectedToSee.profileImage }">
                                <img :src="'/assets/profile_images/' + userSelectedToSee.profileImage"
                                    class="profile-image">
                            </div>
                        </div>
                    </div>

                    <!--Nombre y apellidos-->
                    <div class="d-flex column">
                        <p data-color="azul" data-weight="700" data-size="20">{{ userSelectedToSee.firstName }} {{
                            userSelectedToSee.lastName }}</p>
                    </div>
                </div>

                <!--Datos de usuario-->
                <div class="my-20">

                    <!--Titulo-->
                    <p class="text" data-size="20" data-weight="700">Datos de usuario</p>

                    <div class="separator my-0"></div>

                    <!--Email-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-weight="600">Email</div>

                        <div class="text" data-weight="300">{{ userSelectedToSee.email ? userSelectedToSee.email : '-'
                            }}</div>
                    </div>

                    <!--Telefono-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-weight="600">Teléfono</div>

                        <div class="text" data-weight="300">{{ userSelectedToSee.phone ? userSelectedToSee.phone : '-'
                            }}</div>
                    </div>


                    <!--DNI-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-weight="600">DNI</div>

                        <div class="text" data-weight="300">{{ userSelectedToSee.dni ? userSelectedToSee.dni : '-' }}
                        </div>
                    </div>

                    <!--Género-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-weight="600">Género</div>

                        <div class="text" data-weight="300">{{ userSelectedToSee.gender ? userSelectedToSee.gender : '-'
                            }}</div>
                    </div>


                    <!--Etiqueta-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-weight="600">Etiqueta</div>

                        <div class="text" data-weight="300">{{ userSelectedToSee.label ? userSelectedToSee.label : '-'
                            }}</div>
                    </div>

                    <!--Dirección-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-weight="600">Dirección</div>

                        <div class="text" data-weight="300">{{ userSelectedToSee.address ? userSelectedToSee.address :
                            '-' }}</div>
                    </div>


                    <!--Codigo postal-->
                    <div class="d-flex justify-between my-10">
                        <div class="text" data-weight="600">Código postal</div>

                        <div class="text" data-weight="300">{{ userSelectedToSee.postal ? userSelectedToSee.postal : '-'
                            }}</div>
                    </div>

                    <!--Comisiones-->
                    <div class="d-flex justify-between my-10" v-if="userSelectedToSee.commissions">
                        <div class="text" data-weight="600">
                            Comisiones {{ userSelectedToSee.label === 'Usuario subdominio' ? 'Zoco' : '' }}
                        </div>
                    </div>

                    <div class="d-flex justify-between my-5 ml-10"
                        v-for="marketer of userSelectedToSee.commissionsFormated">
                        <div class="text d-flex max-w-150">
                            <div class="w-35-px d-flex justify-center">
                                <img :src="`/assets/marketers_logo/${marketer.logo}`"
                                    class="h-20-px-max w-30-px-max contain-img" />
                            </div>
                            <span>{{ marketer.name }}</span>
                        </div>
                        <div class="text" data-weight="300">{{ marketer.commissionType }}</div>
                    </div>
                </div>


                <!--Responsables-->
                <div class="my-20">

                    <!--Titulo-->
                    <p class="text" data-size="20" data-weight="700">Usuarios responsables</p>

                    <div v-for="responsible in userSelectedToSee.responsiblesData" class="mt-5 mb-10">

                        <div class="separator my-0"></div>

                        <div class="d-flex justify-center mr-20 my-10 w-100">
                            <div class="initials verySmall mr-20 my-auto" data-style="initials"
                                v-bind:class="{ image: responsible.profileImage }">
                                <img :src="'/assets/profile_images/' + responsible.profileImage" class="profile-image">
                            </div>

                            <div class="d-flex column my-auto">
                                <p class="text opacity-5" data-weight="600" data-size="14"
                                    v-if="this.basicData.userLogged._id !== responsible._id">{{ responsible.firstName }}
                                    {{ responsible.lastName }}</p>
                                <p class="text opacity-5" data-weight="600" data-size="14" v-else>(Tú)</p>
                            </div>

                            <div class="custom-button ml-auto my-auto" data-style="twoBlue" data-size="tiny"
                                v-on:click="actionLink((this.basicData.userLogged._id === responsible._id ? '/profile' : `/users/${responsible._id}`))">
                                <i class="fas fa-arrow-right-long"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Jerarquía-->
                <div class="my-20" v-if="userSelectedToSee.hierarchy.length > 0">


                    <p class="text" data-size="20" data-weight="700">Jerarquía</p>


                    <div class="separator mt-5 mb-10"></div>

                    <hierarchy-item v-for="user in userSelectedToSee.hierarchy" :key="user._id" :user="user"
                        :level="1"></hierarchy-item>

                </div>



                <!--Info usuario-->
                <div class="mr-auto">
                    <div v-on:click="actionLink('/users/' + userSelectedToSee._id)" class="custom-button mr-10"
                        data-size="medium" data-bg="azul" data-mode="outline" data-color="azul">Editar</div>
                </div>
            </div>

            <div v-else class="info-content">

                <!--Iniciales y nombre-->
                <div class="d-flex">
                    <div class="d-flex justify-center mr-20 loading w-20 h-70">
                        <div class="" data-size="25"></div>
                    </div>

                    <!--Nombre y apellidos-->
                    <div class="d-flex column loading w-100 h-25">
                        <p class="" data-color="azul" data-weight="700" data-size="20"></p>
                    </div>
                </div>

                <!--Datos de usuario-->
                <div class="my-20">

                    <!--Titulo-->
                    <p class="loading w-100 h-30" data-size="20" data-weight="700"></p>

                    <div class="separator loading my-10"></div>

                    <!--Correo-->
                    <div class="d-flex justify-between my-10">
                        <div class="loading w-30 h-25"></div>

                        <div class="loading w-55 h-25"></div>
                    </div>

                    <!--Telefono-->
                    <div class="d-flex justify-between my-10">
                        <div class="loading w-40 h-25"></div>

                        <div class="loading w-40 h-25"></div>
                    </div>

                    <!--DNI-->
                    <div class="d-flex justify-between my-10">
                        <div class="loading w-40 h-25"></div>

                        <div class="loading w-40 h-25"></div>
                    </div>

                    <!--Género-->
                    <div class="d-flex justify-between my-10">
                        <div class="loading w-40 h-25"></div>

                        <div class="loading w-40 h-25"></div>
                    </div>

                    <!--Etiqueta-->
                    <div class="d-flex justify-between my-10">
                        <div class="loading w-40 h-25"></div>

                        <div class="loading w-40 h-25"></div>
                    </div>

                    <!--Código postal-->
                    <div class="d-flex justify-between my-10">
                        <div class="loading w-40 h-25"></div>

                        <div class="loading w-40 h-25"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ExcelJS from 'exceljs/dist/exceljs.bare.min.js';
import { saveAs } from 'file-saver';
export default {

    name: "UserNetworkComponent",
    props: ['basicData'],
    data() {
        return {
            isSeeingMassiveLoad: false,
            isSeeingFilters: false,
            isSeeingFiltersPc: {
                users: false,
                sort: false
            },
            searchUserText: '',
            usersSelected: [],
            userSelectedToSee: '',
            filters: {
                checkbox: {
                    usersAvailable: {
                        title: 'Etiquetas',
                        data: []
                    }
                },
                radio: {
                    sortBy: {
                        title: 'Ordenar por',
                        checked: 5,
                        data: [
                            {
                                title: 'Por nombre (A-Z)',
                                value: 0
                            },
                            {
                                title: 'Por nombre (Z-A)',
                                value: 1
                            },
                            {
                                title: 'Por email (A-Z)',
                                value: 2
                            },
                            {
                                title: 'Por email (Z-A)',
                                value: 3
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
            isProcessingBulk: false,
            bulkRows: [],
            bulkErrors: [],
            bulkHeadersMap: {},
        }
    },
    watch: {
        listToUse: {
            handler() {
                this.setFilters();
            },
            immediate: true
        }
    }
    ,
    mounted() {
        if (this.basicData.userList)
            this.setFilters();

    },
    methods: {
        buildHierarchyFromList(userId, list) {
            const addSubordinates = (id) => {

                const subordinates = list
                    .filter(u => Array.isArray(u.responsibles) && u.responsibles.includes(id))
                    .map(subordinate => ({
                        ...subordinate,
                        hierarchy: addSubordinates(subordinate._id)
                    }));

                return subordinates.length > 0 ? subordinates : [];
            };

            return addSubordinates(userId);
        },

        toggleMassiveLoad() {
            this.isSeeingMassiveLoad = !this.isSeeingMassiveLoad;
        },
        onClickOutside(event) {
            const el = this.$refs.massiveLoad;
            if (el && !el.contains(event.target)) {
                this.isSeeingMassiveLoad = false;
            }
        },
        triggerBulkUpload() {
            this.$refs.bulkFileInput.click();
        },
        normalizeHeader(str) {
            let s = ("" + (str ?? "")).replace(/\*/g, "");
            s = s.trim().toLowerCase();
            return s.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        },

async handleBulkFile(event) {
    const inputEl = event.currentTarget;
    const file = inputEl.files[0];
    if (!file) return;

    const nombre = file.name.toLowerCase().trim();
    if (!nombre.endsWith(".xlsx")) {
        Swal.fire({
            icon: "error",
            title: "Formato no válido",
            text: "Solo se permiten ficheros .xlsx para carga masiva.",
            confirmButtonText: "Ok"
        });
        inputEl.value = "";
        return;
    }

    this.isProcessingBulk = true;
    this.bulkRows = [];
    this.bulkErrors = [];
    this.bulkHeadersMap = {};

    try {
        const arrayBuffer = await file.arrayBuffer();
        const workbook = new ExcelJS.Workbook();
        await workbook.xlsx.load(arrayBuffer);
        const worksheet = workbook.worksheets[0];

        // Leer cabecera (fila 2)
        const headerRowNumber = 2;
        const headerRow = worksheet.getRow(headerRowNumber);
        headerRow.eachCell((cell, colNumber) => {
            const raw = cell.text ?? "";
            const normalized = this.normalizeHeader(raw);
            this.bulkHeadersMap[normalized] = colNumber;
        });

        // Cabeceras obligatorias
        const requiredHeaders = [
            "nombre", "apellidos", "correo", "dni/cif", "genero",
            "direccion", "codigo postal", "telefono", "contrasena"
        ].map(h => this.normalizeHeader(h));
        const missingHeaders = requiredHeaders.filter(h => !(h in this.bulkHeadersMap));
        if (missingHeaders.length) {
            Swal.fire({
                icon: "error",
                title: "Cabeceras inválidas",
                html: `Faltan las siguientes cabeceras obligatorias:<br>${missingHeaders.join(", ")}`,
                confirmButtonText: "Ok"
            });
            this.isProcessingBulk = false;
            inputEl.value = "";
            return;
        }

        // Procesar filas de datos
        const firstDataRow = headerRowNumber + 1;
        worksheet.eachRow((row, rowNumber) => {
            if (rowNumber < firstDataRow) return;

            const getCellValue = headerKey => {
                const colIndex = this.bulkHeadersMap[headerKey];
                const cell = row.getCell(colIndex);
                const raw = cell.text != null
                    ? cell.text
                    : (cell.value != null ? cell.value : "");
                return String(raw).trim();
            };

            const firstName = getCellValue("nombre");
            const lastName  = getCellValue("apellidos");
            const email     = getCellValue("correo");
            const dni       = getCellValue("dni/cif");
            const gender    = getCellValue("genero");
            const address   = getCellValue("direccion");
            const postal    = getCellValue("codigo postal");
            const province  = getCellValue("provincia");
            const locality  = getCellValue("localidad");
            const phone     = getCellValue("telefono");
            const respRaw   = ("responsables" in this.bulkHeadersMap)
                              ? getCellValue("responsables")
                              : "";
            const responsibles = respRaw
                ? respRaw.split(/[;,]/).map(e => e.trim()).filter(e => e)
                : [];
            const password  = getCellValue("contrasena");

            // Saltar filas totalmente vacías
            if (!firstName && !lastName && !email && !dni && !gender
                && !address && !postal && !phone && !responsibles.length) {
                return;
            }

            const rowErrors = [];
            if (!firstName) rowErrors.push(`Fila ${rowNumber}: Falta 'nombre'.`);
            if (!lastName)  rowErrors.push(`Fila ${rowNumber}: Falta 'apellidos'.`);
            if (!email) {
                rowErrors.push(`Fila ${rowNumber}: Falta 'correo'.`);
            } else {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    rowErrors.push(`Fila ${rowNumber}: Formato de 'correo' inválido.`);
                }
            }
            if (!dni)      rowErrors.push(`Fila ${rowNumber}: Falta 'dni/cif'.`);
            if (!gender)   rowErrors.push(`Fila ${rowNumber}: Falta 'genero'.`);
            if (!address)  rowErrors.push(`Fila ${rowNumber}: Falta 'direccion'.`);
            if (!postal)   rowErrors.push(`Fila ${rowNumber}: Falta 'codigo postal'.`);
            if (!phone) {
                rowErrors.push(`Fila ${rowNumber}: Falta 'telefono'.`);
            } else {
                const phoneRegex = /^[0-9]+$/;
                if (!phoneRegex.test(phone)) {
                    rowErrors.push(`Fila ${rowNumber}: 'telefono' debe ser numérico.`);
                } else if (phone.length < 9 || phone.length > 15) {
                    rowErrors.push(`Fila ${rowNumber}: 'telefono' debe tener entre 9 y 15 dígitos.`);
                }
            }
            if (!password) rowErrors.push(`Fila ${rowNumber}: Falta 'contraseña'.`);

            responsibles.forEach((respEmail, i) => {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(respEmail)) {
                    rowErrors.push(
                        `Fila ${rowNumber}: responsable #${i + 1} ('${respEmail}') no es un email válido.`
                    );
                }
            });

            if (rowErrors.length) {
                this.bulkErrors.push(...rowErrors);
            } else {
                this.bulkRows.push({
                    firstName,
                    lastName,
                    email,
                    dni,
                    gender,
                    address,
                    postal,
                    province,
                    locality,
                    phone,
                    responsibles,
                    password
                });
            }
        });

        // Si hay errores, mostramos warning pero seguimos
        if (this.bulkErrors.length) {
            Swal.fire({
                icon: "warning",
                title: "Algunos registros no se procesaron",
                html:
                    `Se encontraron errores en ${this.bulkErrors.length} fila(s):<br>` +
                    this.bulkErrors.join("<br>") +
                    `<br><br>Los demás usuarios se importarán.`,
                confirmButtonText: "Continuar"
            });
        }

        // Enviar al backend solo las filas válidas
        if (this.bulkRows.length) {
            const res = await axios.post("/api/user/bulk", { users: this.bulkRows, enterprise: this.basicData?.enterprise?._id });
            if (res.data.errors?.length) {
                Swal.fire({
                    icon: "error",
                    title: "Errores en algunas filas del servidor",
                    html: res.data.errors.join("<br>"),
                    confirmButtonText: "Ok"
                });
            } else {
                Swal.fire({
                    icon: "success",
                    title: "Usuarios importados",
                    text: res.data.message || "Se importaron todos los usuarios correctamente.",
                    timer: 2000,
                    timerProgressBar: true
                });
                this.$emit("refreshUserList");
            }
        } else {
            Swal.fire({
                icon: "error",
                title: "No hay filas válidas",
                text: "Ninguna fila pasó las validaciones. Ajusta el fichero y vuelve a intentarlo.",
                confirmButtonText: "Ok"
            });
        }

    } catch (error) {
        console.error("Error al procesar el archivo:", error.response.data);
        Swal.fire({
            icon: "error",
            title: "Error al procesar el archivo",
            html: `<b>Mensaje:</b> ${error?.response?.data?.limit || error.message || "No disponible"}`,
            confirmButtonText: "Ok",
            width: 700
        });
    } finally {
        this.isProcessingBulk = false;
        inputEl.value = "";
    }
},



        resetSearch() {
            this.searchUserText = ''
        },
        setFilters() {
            this.filters.checkbox.usersAvailable.data = [];

            if (!this.listToUse) return;

            let users = JSON.parse(JSON.stringify(this.listToUse));

            for (let user of users) {

                // 🔥 OMITIR Usuario subdominio SIEMPRE
                if (!user.label || user.label === 'Usuario subdominio') {
                    continue;
                }

                let exists = this.filters.checkbox.usersAvailable.data
                    .some(label => label.title === user.label);

                if (!exists) {
                    this.filters.checkbox.usersAvailable.data.push({
                        title: user.label,
                        active: true
                    });
                }
            }
        },
        getSelectedUserCommissions() {

            let params = {};

            if (this.userSelectedToSee.label !== 'Usuario subdominio')
                params.user = this.userSelectedToSee;

            axios.get("/api/marketers", {
                params //Si es usuario subdominio no le paso el usuario para que así me salgan las de Zoco que son las que se van a usar para los principales
            }).then((res) => {
                let marketers = res.data.marketers;
                let dataFormatted = [];
                // for(let commission in this.userSelectedToSee.commissions){
                for (let marketer of marketers) {
                    let marketerObj = { name: marketer['name'], logo: marketer['logo'] }
                    if (this.userSelectedToSee.marketers.includes(marketer["_id"])) {

                        const commission = this.userSelectedToSee.commissions?.[marketer['_id']];

                        if (commission) {
                            //Formateo la comisión basándose en si es absoluto o por rangos
                            if (commission.type === 'percentage') {
                                marketerObj['commissionType'] = commission.value + "%";
                            } else if (commission.type === 'fixed') {
                                marketerObj['commissionType'] = commission.value + "€";
                            } else if (commission.type === 'contracts') {
                                marketerObj['commissionType'] = commission.value + " contratos";
                            } else if(commission.type === 'range') {
                                marketerObj['commissionType'] = this.basicData?.enterprise?.commissionRanges?.find(range => range.id === commission.value)?.name;
                            }
                        }


                        dataFormatted.push(marketerObj);
                    }
                }
                this.userSelectedToSee.commissionsFormated = dataFormatted.sort((a, b) => a.name.localeCompare(b.name));

            }).catch((error) => console.log(error))
        },
        seeFilters(type) {

            //Cerrar selects
            this.hideCustomSelects()

            switch (type) {
                case 'users':
                    this.isSeeingFiltersPc.users = true;
                    break;

                case 'sort':
                    this.isSeeingFiltersPc.sort = true;
                    break;
            }
        },
        hideCustomSelects() {

            //Cierro todos los filtros
            this.isSeeingFiltersPc = {
                users: false,
                sort: false
            }
        },
        selectOrderType(orderType) {
            this.filters.radio.sortBy.checked = orderType.value;
        },
        toggleSelectUser(user) {

            //Compruebo si esta seleccionado o no el usuario
            if (this.usersSelected.includes(user._id)) {
                let index = this.usersSelected.indexOf(user._id);
                this.usersSelected.splice(index, 1);
            } else {
                this.usersSelected.push(user._id)
            }

        },
        getAllSubordinateIds(user) {
            const ids = [];
            const recurse = u => {
                if (Array.isArray(u.hierarchy) && u.hierarchy.length) {
                    u.hierarchy.forEach(child => {
                        ids.push(child._id);
                        recurse(child);
                    });
                }
            };
            recurse(user);
            return ids;
        },

        deleteUser(user) {
            axios.get('user/haveAccountsOrOrder', { idToRemove: user._id })
                .then(({ data }) => {
                    const { hasAccounts, hasContracts, accountsCount = 0, contractsCount = 0 } = data;

                    if (hasAccounts || hasContracts) {
                        let mensaje = 'No se puede eliminar porque ';
                        if (hasAccounts) mensaje += `tiene ${accountsCount} cuenta(s)`;
                        if (hasContracts) mensaje += `${hasAccounts ? ' y/o ' : ''}${contractsCount} contrato(s)`;
                        return Swal.fire({
                            icon: 'warning',
                            title: 'Atención',
                            text: mensaje + '.'
                        });
                    }

                    const toDelete = [user._id, ...this.getAllSubordinateIds(user)];

                    Swal.fire({
                        icon: 'warning',
                        title: `Vas a eliminar ${toDelete.length} usuario(s)`,
                        text: '¿Estás seguro? Esta acción no se puede deshacer',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'No'
                    }).then(res => {
                        if (!res.isConfirmed) return;

                        axios.post('/api/user/deleteAllSelected', { idsToRemove: toDelete })
                            .then(() => {
                                if (this.userSelectedToSee._id === user._id) {
                                    this.userSelectedToSee = '';
                                }
                                this.basicData.userList = this.basicData.userList
                                    .filter(u => !toDelete.includes(u._id));
                                this.setFilters();
                            })
                            .catch(err => {
                                console.error(err);
                                Swal.fire('Error', 'No se pudo borrar a todos los usuarios', 'error');
                            });
                    });
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Hubo un problema al verificar las relaciones del usuario', 'error');
                });
        },

        deleteAllSelectedUsers() {
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

                    axios.post('/api/user/deleteAllSelected', { idsToRemove: this.usersSelected })
                        .then((res) => {

                            //Borro esos usuarios de cliente
                            this.basicData.userList = this.basicData.userList.filter(user => !this.usersSelected.includes(user._id))

                            this.usersSelected = [];

                            //Establezco los filtros de nuevo
                            this.setFilters()
                        })
                        .catch((err) => {
                            console.log(err)
                        })
                }
            })
        },
        seeUserInfo(user) {

            //Compruebo si se esta clicando uno que ya se esta viendo
            if (this.userSelectedToSee._id === user._id) {
                this.userSelectedToSee = ''
                return;
            } else {
                this.userSelectedToSee = user;
            }

            //Compruebo si es agente directo de Subdominio
            let isDownSubdomain = false;

            if (this.userSelectedToSee.label !== 'Usuario subdominio' && !this.userSelectedToSee.responsibles.includes('65fd4c2f05efc4aa4a050dc2') && this.userSelectedToSee._id !== '65d704c63d2a9cbfd79e549a') {
                this.userSelectedToSee.responsibles.forEach((responsible) => {

                    let nowUser = this.basicData.userListComplete.find(user => user._id === responsible)

                    if (nowUser.label === 'Usuario subdominio')
                        isDownSubdomain = true;
                })
            }

            //Saco la info de los responsables
            if (this.userSelectedToSee) {

                this.userSelectedToSee.responsiblesData = this.userSelectedToSee.responsibles
                    .map(responsibleId => {

                        if (typeof responsibleId !== 'string') return null;

                        let userFound = this.listToUse.find(u => u._id === responsibleId);

                        if (!userFound && this.basicData.userListComplete) {
                            userFound = this.basicData.userListComplete.find(u => u._id === responsibleId);
                        }

                        if (!userFound) {
                            userFound = this.basicData.userList.find(u => u._id === responsibleId);
                        }

                        //formateo las comisiones si tiene
                        if (this.userSelectedToSee.commissions) {
                            this.getSelectedUserCommissions()
                        }

                        return userFound || null;

                    })
                    .filter(Boolean);
            }


        },
        buildHierarchy(userId) {

            let addSubordinates = (userId) => {
                let users = JSON.parse(JSON.stringify(this.basicData.userList))

                let subordinates = users
                    .filter(u => u.responsibles.includes(userId))  // Buscar subalternos del usuario
                    .map(subordinate => ({
                        ...subordinate,
                        hierarchy: addSubordinates(subordinate._id)
                    }))
                    .filter(Boolean);  // Filtrar los valores null

                return subordinates.length > 0 ? subordinates : [];
                //console.log('subordinates -->', subordinates)
            };


            return addSubordinates(userId);
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
        }
        ,
        actionLink(route) {
            this.$router.push(route)
        },
        removeAccents(str) {
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        },
        async exportUsersToExcel() {
            const listaFiltrada = this.filteredUsersPc?.length
                ? this.filteredUsersPc
                : this.filteredUsers;
            if (!listaFiltrada?.length) {
                alert("No hay usuarios que cumplan los filtros.");
                return;
            }
            const allUsersMap = {};
            listaFiltrada.forEach(u => allUsersMap[u._id] = u);
            const parentMap = {};
            listaFiltrada.forEach(u => {
                if (Array.isArray(u.hierarchy)) {
                    u.hierarchy.forEach(childObj => {
                        const childId = (childObj && childObj._id) ? childObj._id : childObj;
                        parentMap[childId] = parentMap[childId] || [];
                        parentMap[childId].push(u);
                        if (!allUsersMap[childId]) allUsersMap[childId] = childObj;
                    });
                }
            });
            const dataParaExcel = [];
            const rendered = new Set();
            listaFiltrada.forEach(u => {
                const fechaCreacion = u.createdAt
                    ? new Date(u.createdAt * 1000).toLocaleDateString("es-ES")
                    : "";
                dataParaExcel.push({
                    Nombre: u.firstName || "",
                    Apellidos: u.lastName || "",
                    DNI: u.dni || "",
                    Email: u.email || "",
                    Teléfono: u.phone || "",
                    Dirección: u.address || "",
                    Localidad: u.locality || "",
                    Provincia: u.province || "",
                    "Código Postal": u.postal || "",
                    Etiqueta: u.label || "",
                    "Creado En": fechaCreacion,
                    Representante: ""
                });
                if (Array.isArray(u.hierarchy)) {
                    u.hierarchy.forEach(childObj => {
                        const childId = (childObj && childObj._id) ? childObj._id : childObj;
                        if (!rendered.has(childId)) {
                            const childData = allUsersMap[childId];
                            const fechaCH = childData.createdAt
                                ? new Date(childData.createdAt * 1000).toLocaleDateString("es-ES")
                                : "";
                            const reps = parentMap[childId]
                                .map(p => p.email || "")
                                .filter(e => e).join(", ");
                            dataParaExcel.push({
                                Nombre: (childData.firstName || "").trim(),
                                Apellidos: (childData.lastName || "").trim(),
                                DNI: childData.dni || "",
                                Email: childData.email || "",
                                Teléfono: childData.phone || "",
                                Dirección: childData.address || "",
                                Localidad: childData.locality || "",
                                Provincia: childData.province || "",
                                "Código Postal": childData.postal || "",
                                Etiqueta: childData.label || "",
                                "Creado En": fechaCH,
                                Representante: reps
                            });
                            rendered.add(childId);
                        }
                    });
                }
            });
            const folder = this.basicData.enterprise?.asset_folder;
            const enterpriseLogos = ['crmlogo2.png'];
            const defaultLogos = ['logo-dark.png', 'logo-full.png', 'logo-light.png', 'mini-dark.png', 'mini-light.png'];
            let logoResp;
            for (const n of enterpriseLogos) {
                logoResp = await fetch(`/assets/enterprises/${folder}/logos/${n}`);
                if (logoResp.ok) break;
            }
            if (!logoResp?.ok) {
                for (const n of defaultLogos) {
                    logoResp = await fetch(`/assets/logos/${n}`);
                    if (logoResp.ok) break;
                }
            }
            if (!logoResp?.ok) {
                logoResp = await fetch('/assets/enterprises/default/logo.png');
            }
            const logoBlob = await logoResp.blob();
            const logoBuffer = await new Response(logoBlob).arrayBuffer();
            const tmpUrl = URL.createObjectURL(logoBlob);
            const img = new Image();
            img.src = tmpUrl;
            await new Promise(r => img.onload = r);
            URL.revokeObjectURL(tmpUrl);
            let imgW = img.naturalWidth, imgH = img.naturalHeight;
            const maxW = 600, maxH = 120;
            if (imgW > maxW) { imgH = maxW * imgH / imgW; imgW = maxW; }
            if (imgH > maxH) { imgW = maxH * imgW / imgH; imgH = maxH; }
            const workbook = new ExcelJS.Workbook();
            const ws = workbook.addWorksheet("UsuariosConJerarquía");
            ws.addRow([]);
            ws.mergeCells("A1");
            ws.getRow(1).height = imgH * 0.75;
            const EMU = 9525;
            const colSz = [25, 25, 15, 30, 20, 35, 20, 20, 15, 20, 18, 35];
            const totalPx = colSz.reduce((s, w) => s + w * 7 + 5, 0);
            const row1Px = ws.getRow(1).height * (96 / 72);
            const offX = (totalPx - imgW) / 2;
            const offY = (row1Px - imgH) / 2;
            const imgId = workbook.addImage({ buffer: logoBuffer, extension: 'png' });
            ws.addImage(imgId, {
                tl: { col: 0, row: 0, colOff: Math.round(offX * EMU), rowOff: Math.round(offY * EMU) },
                ext: { width: imgW, height: imgH }
            });
            const encabezado = [
                "Nombre", "Apellidos", "DNI", "Email", "Teléfono", "Dirección", "Localidad",
                "Provincia", "Código Postal", "Etiqueta", "Creado En", "Representante"
            ];
            ws.addRow(encabezado);
            ws.getRow(2).height = 30;
            const headerFill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFD9D9D9" } };
            const headerFont = { name: "Calibri", color: { argb: "FF000000" }, bold: true, size: 14 };
            const borderAll = {
                top: { style: "thin", color: { argb: "FF000000" } },
                bottom: { style: "thin", color: { argb: "FF000000" } },
                left: { style: "thin", color: { argb: "FF000000" } },
                right: { style: "thin", color: { argb: "FF000000" } }
            };
            const center = { vertical: "middle", horizontal: "center" };
            ws.getRow(2).eachCell(cell => {
                cell.fill = headerFill;
                cell.font = headerFont;
                cell.border = borderAll;
                cell.alignment = center;
            });
            dataParaExcel.forEach(obj => {
                ws.addRow([
                    obj.Nombre, obj.Apellidos, obj.DNI, obj.Email, obj.Teléfono,
                    obj.Dirección, obj.Localidad, obj.Provincia, obj["Código Postal"],
                    obj.Etiqueta, obj["Creado En"], obj.Representante
                ]);
            });
            for (let r = 3; r <= dataParaExcel.length + 2; r++) {
                const row = ws.getRow(r);
                row.height = 22;
                row.eachCell(cell => {
                    cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFFFFFFF" } };
                    cell.border = borderAll;
                    cell.alignment = center;
                });
            }
            ws.columns = colSz.map(w => ({ width: w }));
            const buf = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const fecha = new Date().toISOString().slice(0, 10);
            saveAs(blob, `Usuarios_${fecha}.xlsx`);
        }



    },
    computed: {
        listToUse() {
            const hasHierPermission = this.canManage('users.admiWhiHier');
            const hasSubdomainList = this.basicData?.subdomainUserList?.length;

            if (hasHierPermission && hasSubdomainList) {
                return this.basicData.subdomainUserList;
            }

            return this.basicData.userList;
        },

        filteredUsers() {

            if (!this.basicData.userList && this.filters.checkbox.usersAvailable.data.length === 0) return []

            let usersFiltered = [];

            const hasHierPermission = this.canManage('users.admiWhiHier');
            const hasSubdomainList = this.basicData.subdomainUserList?.length;

            if (this.basicData.userLogged._id === "65cb57489c2c285441086a43") {

                usersFiltered = this.buildHierarchy("65fd4c2f05efc4aa4a050dc2");

            } else if (hasHierPermission && hasSubdomainList) {

                if (this.basicData.userSubdomain?._id === "65cb57489c2c285441086a43") {

                    usersFiltered = this.buildHierarchyFromList(
                        "65fd4c2f05efc4aa4a050dc2",
                        this.basicData.subdomainUserList
                    );

                } else {

                    usersFiltered = this.buildHierarchyFromList(
                        this.basicData.userSubdomain._id,
                        this.basicData.subdomainUserList
                    );

                }

            } else {

                usersFiltered = this.buildHierarchy(this.basicData.userLogged._id);

            }

            usersFiltered = usersFiltered.map(user => ({
                ...user,
                hierarchy: Array.isArray(user.hierarchy) ? user.hierarchy : []
            }));

            const filterUser = (user) => {

                if (Array.isArray(user.hierarchy) && user.hierarchy.length > 0) {

                    for (let i = user.hierarchy.length - 1; i >= 0; i--) {

                        let subordinate = user.hierarchy[i];
                        subordinate.hierarchy = Array.isArray(subordinate.hierarchy) ? subordinate.hierarchy : [];

                        let isValid = filterUser(subordinate);

                        if (!isValid && subordinate.hierarchy.length === 0) {
                            user.hierarchy.splice(i, 1);
                        }
                    }
                }

                let UserSearch = [
                    user.firstName,
                    user.lastName,
                    user.email,
                    user.phone
                ].join('').replace(' ', '').toLowerCase();

                let hasLabelVisible = false;

                this.filters.checkbox.usersAvailable.data.find((label) => {
                    if ((label.title === user.label) && label.active)
                        hasLabelVisible = true;
                });

                return UserSearch.includes(
                    this.searchUserText.replace(' ', '').toLowerCase()
                ) && hasLabelVisible;
            };


            for (let i = usersFiltered.length - 1; i >= 0; i--) {

                let subordinate = usersFiltered[i];
                subordinate.hierarchy = Array.isArray(subordinate.hierarchy) ? subordinate.hierarchy : [];

                let isValid = filterUser(subordinate);

                if (!isValid && subordinate.hierarchy.length === 0) {
                    usersFiltered.splice(i, 1);
                }
            }


            const sortUsers = (users) => {

                if (users.length > 0) {
                    users.forEach((subordinate) => {
                        subordinate.hierarchy = Array.isArray(subordinate.hierarchy) ? subordinate.hierarchy : [];
                        sortUsers(subordinate.hierarchy);
                    })
                }

                switch (this.filters.radio.sortBy.checked) {

                    case 0:
                        users.sort((a, b) => {
                            let aFullName = this.removeAccents(a.firstName + a.lastName);
                            let bFullName = this.removeAccents(b.firstName + b.lastName);
                            return aFullName.localeCompare(bFullName);
                        });
                        break;

                    case 1:
                        users.sort((a, b) => {
                            let aFullName = a.firstName + a.lastName;
                            let bFullName = b.firstName + b.lastName;
                            return bFullName.localeCompare(aFullName);
                        });
                        break;

                    case 2:
                        users.sort((a, b) => a.email.localeCompare(b.email));
                        break;

                    case 3:
                        users.sort((a, b) => b.email.localeCompare(a.email));
                        break;

                    case 6:
                        users.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
                        break;

                    default:
                        users.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
                        break;
                }
            };

            if (usersFiltered.length > 0) {
                sortUsers(usersFiltered);
            }

            return usersFiltered;
        },

        orderTypeSelected() {
            return this.filters.radio.sortBy.data[this.filters.radio.sortBy.checked]
        },
        getUserFilterTitle() {

            if (!this.listToUse) return '0 cuenta/s';

            const validLabels = this.filters.checkbox.usersAvailable.data
                .filter(label => label.title !== 'Usuario subdominio');

            const totalActives = validLabels.filter(label => label.active).length;

            return totalActives + ' cuenta/s';
        },



        areAllSelected() {
            if (this.basicData.userList) return this.usersSelected.length === this.basicData.userList.length
        },
        isReadOnly() {
            if (!this.basicData.userLogged)
                return true
            else (this.basicData.userLogged)
            return this.basicData.userLogged.permissions.includes('READONLY')
        },





    }
}
</script>

<style scoped></style>
