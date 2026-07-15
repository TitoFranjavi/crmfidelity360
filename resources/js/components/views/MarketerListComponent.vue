<template>
    <div @click="hideCustomSelects">

        <!--Estilo de movil-->
        <div class="mobile-item">
            Productos
        </div>

        <!--Estilo de ordenador-->
        <div class="desktop-item d-flex align-center">
            <!--Contenido listado-->
            <div class="content-white d-flex column mr-10 " v-bind:class="{ 'contact-selected': marketerSelectedToSee !== '' }">

                <!--Header-->
                <div class="d-flex justify-between align-center">

                    <!--Título-->
                    <div class="text" data-size="30" data-weight="700">{{ $route.meta.title }}</div>

                    <!--Crear/Editar-->
                    <div class="d-flex" data-gap="15">
                        <!--Botón Añadir-->
                        <div class="d-flex">
                            <div class="custom-select no-hover" v-on:click.stop="isSeeingCreateMarketer = !isSeeingCreateMarketer"  v-bind:class="{'seeing': isSeeingCreateMarketer}">

                                <div class="custom-button" v-if="canManage('products.create') && (!isEdit) && !seeZocoMarketers" data-size="regular" data-bg="principal"><i class="far fa-plus"></i></div>

                                <div class="select-content form w-260-px">
                                    <p class="text" v-on:click="actionLink('/marketers/register')"><i class="far fa-shop mr-10"></i>Crear comercializadora</p>
                                    <div class="d-flex mt-5">
                                        <p class="text" @click="seeLoadMassive = true"><i class="far fa-file-excel ml-4 mr-14"></i>Carga masiva</p>
                                    </div>
                                    <a class="text" href="/assets/templates/marketers.xlsx" download="Plantilla comercializadoras">
                                        <i class="far fa-file-arrow-down ml-4 mr-10"></i>
                                        Descargar plantilla
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div v-if="canManage('products.edit') && !seeZocoMarketers">
                            <div class="custom-button" data-size="regular" data-bg="principal" v-if="(!isEdit)" v-on:click="changeEdit()">Editar</div>
                            <div class="d-flex justify-between" data-gap="10" v-else-if="(isEdit)">
                                <div class="custom-button" data-size="regular" data-bg="rojo" v-on:click="changeEdit()">Cancelar</div>
                                <div class="custom-button" data-size="regular" v-on:click="updateMarketers()">Guardar</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Alternar productos Zoco-->
                <div class="d-flex justify-between" v-if="!isEdit && this.basicData.userLogged && this.basicData.userLogged.email !== 'soporte@zocoenergia.com' && !isDownZoco && canSeeZoco">

                    <div class="d-flex my-5">

                        <div class="custom-checkbox my-auto mr-10" v-on:click="toggleSeeMarketers">
                            <div v-bind:class="{selected: seeZocoMarketers }"></div>
                        </div>

                        <p class="my-auto mr-15 text-nowrap" data-color="principal" data-size="10">Ver comercializadoras de Zoco Energía SL</p>
                    </div>
                </div>

                <!-- Barra de Herramientas -->
                <div class="mt-30 d-flex justify-between">
                    <div class="w-100 d-flex justify-between align-center mx-10">

                        <!-- Filtro Comercializadora -->
                        <div class="d-flex">
                                <div class="ml-10 d-flex align-center pointer w-188-px" data-color="azul" v-on:click.stop="showFilterMarketer()">
                                    <span class="ellipsis noWidth">{{ activeMarketers.length > 0 && activeMarketers.length !== marketers.length ? activeMarketers.map(activeMarketer => marketers?.find(marketer => marketer._id === activeMarketer)?.name).join(', ') : 'Comercializadoras' }}</span>
                                    <i class="far fa-chevron-down ml-10"></i>
                                </div>


                                <div class="custom-select no-hover seeing z-100" v-if="marketerFilter" @click.stop>
                                    <div class="register-pos small w-50 h-auto h-98-max round" data-round="20">
                                        <div class="select-content left filterMarketers">

                                            <div class="d-flex align-center btnAll" v-on:click="filterByMarketer()">
                                                <div class="text-center pointer m-0 w-100" data-weight="600">Todas</div>
                                            </div>

                                            <div class="orderMarkers">
                                                <template v-for="marketer in filteredMarketers">
                                                    <div v-if="isEdit || !marketer.archived" class="cell" :class="activeMarketers.includes(marketer._id) ? 'selected' : ''" v-on:click="filterByMarketer(marketer)">
                                                        <img :src="'/assets/marketers_logo/'+marketer.logo" alt="Logo" class="w-80-px-max h-50-px-max contain-img">
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                        </div>

                        <!-- Filtro Tarifa -->
                        <div class="flex">
                            <!-- Filtro para Tárifas de Luz -->
                            <div class="ml-10 d-flex aling-center pointer" v-if="productToShow === 'electricity'" data-color="azul" v-on:click.stop="showFeeFilter()">{{ feeSelectedElec ? feeSelectedElec : 'Tarifas' }}<i class="far fa-chevron-down ml-10"></i></div>

                            <div class="custom-select no-hover seeing z-100" v-if="filterByFees && productToShow === 'electricity'">
                                <div class="register-pos small w-50 h-auto h-98-max round filterMarketers" data-round="20">
                                    <div class="select-content left d-flex align-center justify-center column">
                                        <div class="text-center p-10 pointer m-0 w-100" :class="{'btnAll': !feeSelectedElec}" v-on:click="filterByFee('electricity')">Todas</div>
                                        <template v-for="fee in filteredElecFees">
                                            <div class="p-5 pointer m-0" :class="{'btnAll': feeSelectedElec === fee}" v-on:click="filterByFee('electricity', fee)">{{ fee }}</div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Filtro para Tárifas de Gas -->
                            <div class="ml-10 d-flex aling-center" v-if="productToShow === 'gas'" data-color="azul" v-on:click.stop="showFeeFilter()">{{ feeSelectedGas ? feeSelectedGas : 'Tarifas' }}<i class="far fa-chevron-down ml-10"></i></div>
                            <div class="custom-select no-hover seeing z-100" v-if="filterByFees && productToShow === 'gas'">
                                <div class="register-pos small w-50 h-auto h-98-max round filterMarketers" data-round="20">
                                    <div class="select-content left d-flex align-center justify-center column">
                                        <div class="text-center p-10 pointer m-0 w-100" :class="{'btnAll': !feeSelectedGas}" v-on:click="filterByFee('gas')">Todas</div>
                                        <template v-for="fee in filteredGasFees">
                                            <div class="p-5 pointer m-0" :class="{'btnAll': feeSelectedGas === fee}" v-on:click="filterByFee('gas', fee)">{{ fee }}</div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtro Tipo -->
                        <div class="text"  v-on:click.stop="">
                            <div class="ml-10 d-flex aling-center pointer" data-color="azul" v-on:click.stop.prevent="showTypeFilter()">{{ typeSelected ? typeSelected : 'Tipo' }}<i class="far fa-chevron-down ml-10"></i></div>

                            <div class="custom-select no-hover seeing z-100" v-if="typeFilter">
                                <div class="register-pos small w-50 h-auto h-98-max round filterMarketers" data-round="20">
                                    <div class="select-content left d-flex align-center justify-center column">
                                        <div class="d-flex align-center justify-center p-10 pointer m-0" :class="{'btnAll': typeSelected === ''}">
                                            <div class="text" v-on:click="filterByType()">Ambas</div>
                                        </div>
                                        <div class="d-flex align-center justify-center p-10 pointer m-0" :class="{'btnAll': typeSelected === 'Residencial'}">
                                            <div class="text" v-on:click="filterByType('r')">Residencial</div>
                                        </div>
                                        <div class="d-flex align-center justify-center p-10 pointer m-0" :class="{'btnAll': typeSelected === 'PYME'}">
                                            <div class="text" v-on:click="filterByType('p')">PYME</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtro t. producto -->
                        <div class="text"  v-on:click.stop="">
                            <div class="ml-10 d-flex aling-center pointer" data-color="azul" v-on:click.stop.prevent="showProductTypeFilter()">{{ typeProductSelected ? typeProductSelected : 'Tipo de producto' }}<i class="far fa-chevron-down ml-10"></i></div>

                            <div class="custom-select no-hover seeing z-100" v-if="typeProductFilter">
                                <div class="register-pos small w-50 h-auto h-98-max round filterMarketers" data-round="20">
                                    <div class="select-content left d-flex align-center justify-center column">
                                        <div class="d-flex align-center justify-center p-10 pointer m-0" :class="{'btnAll' : productToShow === typeInd }" v-for="(type, typeInd) in typeFeed" v-on:click="toggleType(typeInd, type)">
                                            <div class="text d-flex justify-center align-center">

                                                <!--Icono-->
                                                <i v-if="typeInd !== 'dual'" class="far" :class="'fa-' + type.icon"></i>
                                                <div v-else class="d-flex justify-center align-center">
                                                    <i class="far fa-bolt text-center"></i>
                                                    <i class="far fa-fire-flame-simple text-center"></i>
                                                </div>

                                                <!--Nombre-->
                                                <span class="ml-10 ">{{ type.title }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!--Barra de busqueda-->
                    <div class="w-50 select-line">
                        <div class="search-div d-flex">

                            <div class="search-bar w-100">

                                <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                                <input type="text" placeholder="Buscar un producto..." v-model="searchMarketerText">
                            </div>

                            <i data-color="principal" class="pointer far fa-trash my-10 ml-10" v-on:click="resetSearch"></i>

                        </div>
                    </div>

                </div>

                <!--LISTADO-->

                    <!--Header-->
                    <div class="marketersList card mt-30 mb-10 w-100" v-bind:class="{'gas': productToShow === 'gas', 'dual': productToShow === 'dual', 'alarm': productToShow === 'alarm', 'other': (productToShow === 'telephony' || productToShow === 'selfSupply')}">

                        <!-- Espacio de las Flechas de Ordenación -->
                        <div class="cell" data-color="principal"></div>

                        <!-- Nombre Celdas -->
                        <div class="cell" data-color="principal">
                            <p class="text mr-5 pointer ellipsis" data-weight="600">Nombre</p>
                        </div>

                        <div class="cell" data-color="principal" v-if="productToShow !== 'alarm'">
                            <p class="text mr-5 pointer ellipsis" data-weight="600">Tarifa</p>
                        </div>

                        <template v-if="productToShow === 'electricity'">
                            <div>
                                <div class="text d-flex justify-center" data-color="principal" data-weight="600">Precio Potencia</div>
                                <div class="d-grid" data-column="6">
                                    <template v-for="index in 6">
                                        <div class="text ellipsis pl-10 text-center" data-weight="400">{{ `P${index}` }}</div>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <div class="text d-flex justify-center" data-color="principal" data-weight="600">Precio Consumo</div>
                                <div class="d-grid" data-column="6">
                                    <template v-for="index in 6">
                                        <div class="text ellipsis pl-10 text-center" data-weight="400">{{ `P${index}` }}</div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template v-if="productToShow === 'gas'">
                            <div  class="d-grid" data-column="2" data-color="principal">
                                <p class="text mr-5 pointer ellipsis" data-weight="600">Termino Fijo</p>
                                <p class="text mr-5 pointer ellipsis" data-weight="600">Termino variable</p>
                            </div>
                            <!--<div  class="d-flex column" data-color="principal">
                                <div class="text mr-5 pointer ellipsis text-center" data-weight="600">Comisión</div>
                                <template v-if="canManage('GESCON')">
                                    <div class="d-grid grid-justify-center" data-column="4">
                                        <div>Asercord</div>
                                        <div>VIP</div>
                                        <div>Senior</div>
                                        <div>Junior</div>
                                    </div>
                                </template>
                            </div>-->
                        </template>

                        <template v-if="productToShow === 'dual'">

                            <div>
                                <!--Electricidad-->
                                <div class="d-flex w-100">
                                    <div class="w-50">
                                        <div class="text d-flex justify-center" data-color="principal" data-weight="600">Precio Potencia</div>
                                        <div class="d-grid" data-column="6">
                                            <template v-for="index in 6">
                                                <div class="text ellipsis pl-10 text-center" data-weight="400">{{ `P${index}` }}</div>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="w-50">
                                        <div class="text d-flex justify-center" data-color="principal" data-weight="600">Precio Consumo</div>
                                        <div class="d-grid" data-column="6">
                                            <template v-for="index in 6">
                                                <div class="text ellipsis pl-10 text-center" data-weight="400">{{ `P${index}` }}</div>
                                            </template>
                                        </div>
                                    </div>
                                </div>


                                <!--Gas-->
                                <div class="d-grid w-50" data-column="2" data-color="principal">
                                    <p class="text mr-5 pointer ellipsis" data-weight="600">Termino Fijo</p>
                                    <p class="text mr-5 pointer ellipsis" data-weight="600">Termino variable</p>
                                </div>
                            </div>

                        </template>

                        <div>
                            <div class="text text-center" data-color="principal" data-weight="600">Info</div>
                            <div class="d-flex justify-center pointer" @click="toggleProducts('all')" ><i :class="['text far', seeProducts.all ? 'fa-chevron-down' : 'fa-chevron-up']"></i></div>
                        </div>

                    </div>

                    <!-- Float Añadir Producto -->
                    <Teleport to=".boxBody" v-if="seeAdd">
                        <new-product :dataMarketer="dataMarketer" :typeProduct="productToShow" :visibility="seeAdd" @refreshList="refreshList" @closeWindow="seeAdd = false" v-on:closeForm="showAddProduct()"/>
                    </Teleport>

                    <!-- Float Añadir Comisiones -->
                    <Teleport to=".boxBody" v-if="seeAddCommissions">
                        <new-commissions :dataMarketer="dataMarketer" :typeProduct="productToShow" :basicData="basicData" @closeWindow="seeAddCommissions = false" v-on:closeForm="showAddProduct()"/>
                    </Teleport>

                    <div v-if="seeLoadMassive" class="modal" @click="seeLoadMassive = null">
                        <div class="w-700-px w-90-max d-flex column round p-20 align-center" data-gap="5" data-round="20" data-bg="blanco" data-border-color="principal" @click.stop>
                            <p class="text" data-size="24" data-weight="600">Cargar productos masivamente</p>
                            <p class="text opacity-6">Selecciona las fechas de validez de este tarifario</p>

                            <div class="form d-flex" data-gap="10">
                                <div class="form-group">
                                    <p class="ml-5">Fecha de inicio:</p>
                                    <div class="input-group">
                                        <input v-model="validDates.start" type="date" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <p class="ml-5">Fecha de fin:</p>
                                    <div class="input-group">
                                        <input v-model="validDates.end" type="date" />
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex my-5 pointer" @click="archiveProductsNotListed = !archiveProductsNotListed">
                                <div class="custom-checkbox my-auto mr-10">
                                    <div v-bind:class="{selected: archiveProductsNotListed }"></div>
                                </div>

                                <p class="my-auto mr-15 text-nowrap" data-color="principal" data-size="10">Archivar productos no listados</p>
                            </div>

                            <div class="custom-button mt-5" data-bg="principal" data-mode="translucent" data-size="regular" @click="$refs.inputExcel.click()">
                                {{ fileName ? 'Archivo adjuntado' : 'Adjuntar' }}
                            </div>
                            <input type="file" style="display: none" ref="inputExcel" accept=".xls, .xlsx, .csv" @change="handleFile">

                            <div class="mt-5">
                            </div>

                            <div class="custom-button mt-20" data-size="regular" @click="uploadMarketerPrices">
                                Cargar precios
                            </div>
                        </div>
                    </div>

                    <!-- Float Comentario -->
                    <Teleport to=".boxBody" v-if="seeComment">
                        <div class="floating-box z-100">
                            <div class="register-pos w-50 h-auto h-98-max round" data-round="20" data-border-color="principal">
                                <div class="text" data-color="principal" data-weight="600" data-size="24">Observación</div>
                                <div class="text break-spaces" data-size="10">{{ activeComment }}</div>

                                <div class="mt-50 d-flex justify-end">
                                    <div class="custom-button" data-size="regular" data-bg="rojo" v-on:click="showComment()">Cerrar</div>
                                </div>
                            </div>
                        </div>
                    </Teleport>

                    <!-- Pantalla de Carga -->
                    <Teleport to=".boxBody" v-if="loading">
                        <div class="floating-box z-100">
                            <div class="d-flex column justify-center register-pos w-auto h-auto h-98-max round" data-round="20">
                                <div class="text" data-color="principal" data-weight="600" data-size="36">Guardando...</div>
                                <div class="text text-center" data-size="26"><i class="far fa-spinner-third fa-spin"></i></div>
                            </div>
                        </div>
                    </Teleport>

                    <!-- Contenido -->
                    <div v-if="marketers && marketers.length > 0" class="scroll-y">

                        <template v-for="(marketer, indMarketer) in filteredMarketers">
                            <div v-if="(isEdit || !marketer.archived)" class="mb-20">

                                <div v-show="activeMarketers.includes(marketer._id)">

                                    <!-- Cabecera de Marketer -->
                                    <div class="d-flex align-center card" @click="toggleProducts(marketer._id)">
                                        <div class="w-5 d-flex justify-center align-center">
                                            <img :src="'/assets/marketers_logo/'+marketer.logo" alt="Logo" class="h-40-px-max w-80-px-max contain-img">
                                        </div>
                                        <div class="flex-1 d-flex align-center" data-gap="20">
                                            <div class="text ml-10" data-color="azul" data-weight="600">{{ marketer.name }}</div>
                                            <div v-if="marketer.validDates?.[currentProductKey]" class="d-flex align-center" data-gap="5" data-size="11">
                                                <p class="text opacity-6" style="line-height: 1">{{ formatDate(marketer.validDates[currentProductKey].start) }}</p>
                                                <p v-if="marketer.validDates[currentProductKey].end" class="text opacity-6" style="line-height: 1"> - {{ formatDate(marketer.validDates[currentProductKey].end) }}</p>
                                            </div>
                                        </div>
                                        <div class="mr-10 pointer text" v-if="canManage('products.edit') && !seeZocoMarketers && productToShow !== 'all'" @click.stop="showAddProduct(marketer)"><i class="far fa-square-plus"></i></div>                                    <div class="mr-10 pointer text" v-if="canManage('products.edit') && !seeZocoMarketers && !(productToShow === 'all' || productToShow === 'dual' || productToShow === 'telephony' || productToShow === 'alarm')" @click.stop="showAddCommissions(marketer)"><i class="far fa-file-spreadsheet"></i></div>
                                        <div class="mr-10 pointer text" v-if="canManage('products.edit') && !seeZocoMarketers" @click.stop="actionLink(`marketers/register?id=${marketer._id}`)"><i class="far fa-pen-to-square"></i></div>
                                        <div class="mr-10 pointer text"><i :class="['text far',seeProducts[marketer._id] ? 'fa-chevron-down' : 'fa-chevron-up']"></i></div>
                                    </div>

                                    <!-- Productos de Electricidad -->
                                    <template v-if="isShowingProductType('electricity') && seeProducts[marketer._id]">
                                        <template v-if="marketer.products && marketer.products.electricity && marketer.products.electricity.length > 0">
                                            <template class="w-100" v-for="(elec, i) in marketer.products.electricity">
                                                <template v-for="(electFee, fee) in elec.fees">
                                                    <div class="form marketersList productSeparator py-5" v-if="marketers[marketers.findIndex(m => m.name === marketer.name)].products.electricity[i] && marketers[marketers.findIndex(m => m.name === marketer.name)].products.electricity[i].fees && marketers[marketers.findIndex(m => m.name === marketer.name)].products.electricity[i].fees[fee] && (!feeSelectedElec || getFee(electFee.id, marketers.findIndex(m => m.name === marketer.name), 'electricity') === feeSelectedElec) && (!typeSelected || electFee['type'][typeSelected === 'Residencial' ? 'residencial' : 'pyme']) && (isEdit || !electFee?.archived)" v-on:dblclick="showProduct(marketer._id, elec._id, electFee.id, 'electricity')">
                                                        <div class="d-flex column text" >
                                                            <template v-if="isEdit">
                                                                <div v-if="fee === 0 && i !== 0" v-on:click.stop="moveProduct(marketer, elec, 'electricity', i, -1)" v-on:dblclick.stop=""><i class="far fa-chevron-up pointer"></i></div>
                                                                <div v-if="fee === 0 && i !== marketer.products.electricity.length-1" v-on:click.stop="moveProduct(marketer, elec, 'electricity', i, 1)" v-on:dblclick.stop=""><i class="far fa-chevron-down pointer"></i></div>
                                                            </template>
                                                        </div>


                                                        <div class="text ellipsis pl-10" :class="{'product-name-with-icon': productToShow === 'all'}" data-weight="400" v-if="isEdit || !electFee?.archived">
                                                            <i v-if="productToShow === 'all'" :class="['far', productIcon('electricity'), 'product-type-icon']"></i>
                                                            <span class="ellipsis">{{ elec.name }}</span>
                                                        </div>
                                                        <div class="text ellipsis pl-10" data-weight="400">{{ getFee(electFee.id, marketers.findIndex(m => m.name === marketer.name), "electricity") }}</div>
                                                        <!-- Precios Potencia -->
                                                        <div class="d-grid form-group m-0" data-column="6">
                                                            <template v-for="index in 6">
                                                                <div class="text ellipsis pl-10" data-weight="400" v-if="isEdit">
                                                                    <div class="input-group"><input type="text" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.electricity[i].fees[fee].prices.power[`P${index}`]" :disabled="electFee.archived"></div>
                                                                </div>
                                                                <div v-else class="text ellipsis pl-10 text-center" data-weight="400">{{ electFee.prices.power?.[`P${index}`] && Number(electFee.prices.power[`P${index}`]) !== 0 ? electFee.prices.power[`P${index}`] : '' }}</div>
                                                            </template>
                                                        </div>

                                                        <div class="d-grid form-group m-0" data-column="6">
                                                        <!-- Precios Consumo -->
                                                            <template v-for="index in 6">
                                                                <div class="text ellipsis pl-10" data-weight="400" v-if="isEdit">
                                                                    <div class="input-group"><input type="text" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.electricity[i].fees[fee].prices.consume[`P${index}`]" :disabled="electFee.archived"></div>
                                                                </div>
                                                                <div v-else class="text ellipsis pl-10 text-center" data-weight="400">{{ electFee.prices.consume?.[`P${index}`] && Number(electFee.prices.consume[`P${index}`]) !== 0 ? electFee.prices.consume[`P${index}`] : '' }}</div>
                                                            </template>
                                                        </div>
                                                        <div v-if="isEdit" class="d-flex justify-center" @dblclick.stop>
                                                            <i class="far fa-trash pointer" data-color="rojo" @click="deleteProductFee(marketer._id, elec._id, electFee.id)" />
                                                        </div>
                                                        <div v-else class="text-center" v-if="electFee.comment" v-on:click="showComment(electFee.comment)"><i class="far fa-circle-info text pointer"></i></div>
                                                    </div>
                                                </template>
                                            </template>
                                        </template>
                                        <template v-else-if="productToShow !== 'all'">
                                            <div data-weight="600">No hay productos de luz</div>
                                        </template>
                                    </template>

                                    <!-- Productos de Gas -->
                                    <template v-if="isShowingProductType('gas') && seeProducts[marketer._id]">
                                        <template v-if="marketer.products.gas.length > 0">
                                            <template class="w-100" v-for="(gas, ind) in marketer.products.gas">
                                                <template v-for="(gasFee, feeInd) in gas.fees">
                                                    <template v-if="marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind] && marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees && marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd] && (!feeSelectedGas || getFee(gasFee.id, marketers.findIndex(m => m.name === marketer.name), 'gas') === feeSelectedGas) && (!typeSelected || marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd]['type'][typeSelected === 'Residencial' ? 'residencial' : 'pyme']) && (isEdit || !gasFee?.archived)">

                                                        <div class="marketersList gas productSeparator py-5 form" v-on:dblclick="showProduct(marketer._id, gas._id, gasFee.id, 'gas')">
                                                            <!-- Botones Subida y bajada -->
                                                            <div class="d-flex column text" >
                                                                <template v-if="isEdit">
                                                                    <div v-if="(feeInd === 0 && ind !== 0) || feeSelectedGas" v-on:click="moveProduct(marketer, gas, 'gas', ind, -1)"><i class="far fa-chevron-up"></i></div>
                                                                    <div v-if="(feeInd === 0 && ind !== marketer.products.gas.length-1) || feeSelectedGas" v-on:click="moveProduct(marketer, gas, 'gas', ind, 1)"><i class="far fa-chevron-down"></i></div>
                                                                </template>
                                                            </div>

                                                            <!-- Nombre y Tarifa -->
                                                            <div class="text ellipsis pl-10" :class="{'product-name-with-icon': productToShow === 'all'}" data-weight="400">
                                                                <i v-if="productToShow === 'all'" :class="['far', productIcon('gas'), 'product-type-icon']"></i>
                                                                <span class="ellipsis">{{ gas.name }}</span>
                                                            </div>
                                                            <div class="text ellipsis pl-10" data-weight="400">{{ getFee(gasFee.id, marketers.findIndex(m => m.name === marketer.name), "gas") }}</div>
                                                            <!-- Terminos -->
                                                            <div class="d-grid" data-column="2">
                                                                <div class="text ellipsis pl-10 form-group m-0" data-weight="400">
                                                                    <div class="input-group" v-if="isEdit">
                                                                        <input type="text" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].prices.fixed">
                                                                    </div>
                                                                    <div v-else class="text ellipsis pl-10" data-weight="400">{{ gasFee.prices.fixed && Number(gasFee.prices.fixed) !== 0 ? gasFee.prices.fixed : '' }}</div>
                                                                </div>

                                                                <div class="text ellipsis pl-10 form-group m-0" data-weight="400">
                                                                    <div class="input-group" v-if="isEdit">
                                                                        <input type="text" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].prices.variable">
                                                                    </div>
                                                                    <div v-else class="text ellipsis pl-10" data-weight="400">{{ gasFee.prices.variable && Number(gasFee.prices.variable) !== 0 ? gasFee.prices.variable : '' }}</div>
                                                                </div>
                                                            </div>
                                                            <!-- Comisiones -->
                                                            <!--<div class="d-grid grid-justify-center form-group m-0" data-column="4" v-if="gasFee && gasFee.consumptionBasic">
                                                                <div class="text ellipsis pl-10" data-weight="400" v-if="isEdit">
                                                                    <div class="input-group"><input type="text" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.comAs"></div>
                                                                </div>
                                                                <div v-else class="text ellipsis pl-10" data-weight="400">{{ marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.comAs && Number(marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.comAs) !== 0 ? marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.comAs : '' }}</div>

                                                                <div class="text ellipsis pl-10" data-weight="400" v-if="isEdit">
                                                                    <div class="input-group"><input type="text" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com3"></div>
                                                                </div>
                                                                <div v-else class="text ellipsis pl-10" data-weight="400">{{ marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com3 && Number(marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com3) !== 0 ? marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com3 : '' }}</div>

                                                                <div class="text ellipsis pl-10" data-weight="400" v-if="isEdit">
                                                                    <div class="input-group"><input type="text" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com2"></div>
                                                                </div>
                                                                <div v-else class="text ellipsis pl-10" data-weight="400">{{ marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com2 && Number(marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com2) !== 0 ? marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com2 : '' }}</div>

                                                                <div class="text ellipsis pl-10" data-weight="400" v-if="isEdit">
                                                                    <div class="input-group"><input type="text" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com1"></div>
                                                                </div>
                                                                <div v-else class="text ellipsis pl-10" data-weight="400">{{ marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com1 && Number(marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com1) !== 0 ? marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].consumptionBasic.com1 : '' }}</div>

                                                            </div>-->
                                                            <!-- Btn Observaciones -->
                                                            <div v-if="isEdit" class="d-flex justify-center" @dblclick.stop>
                                                                <i class="far fa-trash pointer" data-color="rojo" @click="deleteProductFee(marketer._id, gas._id, gasFee.id)" />
                                                            </div>
                                                            <div v-else class="text-center" v-if="gasFee.comment" v-on:click="showComment(gasFee.comment)"><i class="far fa-circle-info text pointer"></i></div>

                                                        </div>
                                                        <!-- Vistas solo de edicion -->

                                                        <!--<div v-if="isEdit" class="form">

                                                            <div class="d-flex">
                                                                T. prod ( residencial, pyme )
                                                                <div class="d-flex form-group">
                                                                    <p class="text my-auto">Tipo del producto:</p>
                                                                    <div class="input-group mx-10">
                                                                        <label class="mr-5" for="r">Residencial</label>
                                                                        <input type="checkbox" id="r" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].type.residencial">
                                                                    </div>
                                                                    <div class="input-group mx-10">
                                                                        <label class="mr-5" for="p">PYME</label>
                                                                        <input type="checkbox" id="p" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees[feeInd].type.pyme">
                                                                    </div>
                                                                </div>

                                                                Tarifas producto
                                                                <div class="d-flex form-group ml-30" v-if="feeInd === 0">
                                                                    <p class="text my-auto">Tarifas del producto:</p>
                                                                    <div class="custom-select no-hover my-auto" :class="{seeing : seeFees === ind}">

                                                                        <div class="text mx-10 my-auto" data-weight="400" v-on:click.stop="toggleSeeFees(ind)">
                                                                            Tarifa <i class="fas ml-5" :class="[seeFees === ind ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                                                                        </div>

                                                                        Despegable de Tarifas
                                                                        <div>
                                                                            <div class="select-content left">
                                                                                <div v-if="marketers.findIndex(m => m.name === marketer.name) !== -1" v-for="(fee, i) in marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees">
                                                                                    <div class="d-flex justify-between">
                                                                                        <div>{{ getFee(fee.id, marketers.findIndex(m => m.name === marketer.name), "gas") }}</div>
                                                                                        <div v-if="isEdit" @click="deleteGasProductFee(fee, marketers.findIndex(m => m.name === marketer.name), marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind], ind)"><i class="far fa-trash" data-color="rojo"></i></div>
                                                                                    </div>
                                                                                </div>

                                                                                botón añadir tarifa
                                                                                <div class="custom-button d-flex justify-between mt-10" data-size="small" data-bg="amarillo" v-if="isEdit" v-on:click.stop="isShowingFeeModal = true">Añadir Tarifa <i class="far fa-plus my-auto"></i></div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            Observaciones
                                                            <div class="d-flex form-group">
                                                                <div>Observaciones:</div>
                                                                <div class="input-group ml-10 w-100"><textarea v-model="gasFee.comment"></textarea></div>
                                                            </div>
                                                        </div>-->
                                                    </template>
                                                </template>
                                            </template>
                                        </template>
                                        <template v-else-if="productToShow !== 'all'">
                                            <div v-if="!filteredGasFees" class="ml-20 pt-20">No hay productos de gas</div>
                                        </template>
                                        <div class="separator"></div>
                                    </template>

                                    <!-- Productos Duales -->
                                    <template v-if="isShowingProductType('dual') && seeProducts[marketer._id]">
                                        <template v-if="marketer.products && marketer.products.dual && marketer.products.dual.length > 0">
                                            <template class="w-100" v-for="(dual, i) in marketer.products.dual">
                                                <template v-for="(dualFee, feeInd) in dual.fees">
                                                    <div class="form marketersList productSeparator py-12 dual" v-if="marketers[marketers.findIndex(m => m.name === marketer.name)].products.dual[i] && marketers[marketers.findIndex(m => m.name === marketer.name)].products.dual[i].fees && marketers[marketers.findIndex(m => m.name === marketer.name)].products.dual[i].fees[feeInd] /*(!feeSelectedElec || getFee(electFee.id, marketers.findIndex(m => m.name === marketer.name), 'elec') === feeSelectedElec)*/ && (!typeSelected || dualFee['type'][typeSelected === 'Residencial' ? 'residencial' : 'pyme']) && (isEdit || !dualFee?.archived)" v-on:dblclick="showProduct(marketer._id, dual._id, dualFee.id, 'dual')">
                                                        <div class="d-flex column text" >
                                                            <template v-if="isEdit">
                                                                <div v-if="feeInd === 0 && i !== 0" v-on:click.stop="moveProduct(marketer, dual, 'dual', i, -1)" v-on:dblclick.stop=""><i class="far fa-chevron-up pointer"></i></div>
                                                                <div v-if="feeInd === 0 && i !== marketer.products.dual.length-1" v-on:click.stop="moveProduct(marketer, dual, 'dual', i, 1)" v-on:dblclick.stop=""><i class="far fa-chevron-down pointer"></i></div>
                                                            </template>
                                                        </div>


                                                        <!--Nombre productos-->
                                                        <div v-if="isEdit || !dualFee?.archived">
                                                            <p class="text ellipsis pl-10 mb-5" :class="{'product-name-with-icon': productToShow === 'all'}" data-weight="400">
                                                                <i v-if="productToShow === 'all'" :class="['far', productIcon('electricity'), 'product-type-icon']"></i>
                                                                <span class="ellipsis">{{ dual.electricity }}</span>
                                                            </p>

                                                            <p class="text ellipsis pl-10" :class="{'product-name-with-icon': productToShow === 'all'}" data-weight="400">
                                                                <i v-if="productToShow === 'all'" :class="['far', productIcon('gas'), 'product-type-icon']"></i>
                                                                <span class="ellipsis">{{ dual.gas }}</span>
                                                            </p>
                                                        </div>

                                                        <!--Nombre tarifas-->
                                                        <div>
                                                            <p class="text ellipsis pl-10 mb-5" data-weight="400">{{ dualFee.electricity.name }}</p>
                                                            <p class="text ellipsis pl-10" data-weight="400">{{ dualFee.gas.name }}</p>
                                                        </div>


                                                        <!--Precios-->
                                                        <div class="">

                                                            <!--Precios electricidad-->
                                                            <div class="d-flex w-100 mb-5">

                                                                <!--Potencia-->
                                                                <div class="d-grid form-group m-0 w-50" data-column="6">
                                                                    <template v-for="index in 6">
                                                                        <div class="text ellipsis pl-10" data-weight="400" v-if="isEdit">
                                                                            <div class="input-group"><input type="text" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.dual[i].fees[feeInd].electricity.prices.power[`P${index}`]"></div><!--dualFee.electricity.prices.power[`P${index}`]-->
                                                                        </div>
                                                                        <div v-else class="text ellipsis pl-10 text-center" data-weight="400">{{ dualFee.electricity.prices.power[`P${index}`] && Number(dualFee.electricity.prices.power[`P${index}`]) !== 0 ? dualFee.electricity.prices.power[`P${index}`] : '' }}</div>
                                                                    </template>
                                                                </div>

                                                                <!--Consumo-->
                                                                <div class="d-grid form-group m-0 w-50" data-column="6">
                                                                    <!--Consumo-->
                                                                    <template v-for="index in 6">
                                                                        <div class="text ellipsis pl-10" data-weight="400" v-if="isEdit">
                                                                            <div class="input-group"><input type="text" :disabled="dualFee.archived" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.dual[i].fees[feeInd].electricity.prices.consume[`P${index}`]"></div>
                                                                        </div>
                                                                        <div v-else class="text ellipsis pl-10 text-center" data-weight="400">{{ dualFee.electricity.prices.consume[`P${index}`] && Number(dualFee.electricity.prices.consume[`P${index}`]) !== 0 ? dualFee.electricity.prices.consume[`P${index}`] : '' }}</div>
                                                                    </template>
                                                                </div>

                                                            </div>

                                                            <!--Términos gas-->
                                                            <div class="d-grid w-50" data-column="2">
                                                                <div class="text ellipsis pl-10 form-group m-0" data-weight="400">
                                                                    <div class="input-group" v-if="isEdit">
                                                                        <input type="text" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.dual[i].fees[feeInd].gas.prices.fixed">
                                                                    </div>
                                                                    <div v-else class="text ellipsis" data-weight="400">{{ dualFee.gas.prices.fixed && Number(dualFee.gas.prices.fixed) !== 0 ? dualFee.gas.prices.fixed : '' }}</div>
                                                                </div>

                                                                <div class="text ellipsis pl-10 form-group m-0" data-weight="400">
                                                                    <div class="input-group" v-if="isEdit">
                                                                        <input type="text" v-model="marketers[marketers.findIndex(m => m.name === marketer.name)].products.dual[i].fees[feeInd].gas.prices.variable">
                                                                    </div>
                                                                    <div v-else class="text ellipsis" data-weight="400">{{ dualFee.gas.prices.variable && Number(dualFee.gas.prices.variable) !== 0 ? dualFee.gas.prices.variable : '' }}</div>
                                                                </div>
                                                            </div>

                                                        </div>


                                                        <!--Info-->
                                                        <div class="text-center" v-if="dualFee.comment" v-on:click="showComment(dualFee.comment)"><i class="far fa-circle-info text pointer"></i></div>

                                                    </div>
                                                </template>
                                            </template>
                                        </template>
                                        <template v-else-if="productToShow !== 'all'">
                                            <div data-weight="600">No hay productos duales</div>
                                        </template>
                                    </template>

                                    <!--Productos de Telefonía-->
                                    <template v-if="isShowingProductType('telephony') && seeProducts[marketer._id]">
                                        <template v-if="marketer.products && marketer.products.telephony && marketer.products.telephony.length > 0">
                                            <template class="w-100" v-for="(tel, i) in marketer.products.telephony">
                                                <template v-if="tel" v-for="(telFee, fee) in tel.fees">
                                                    <div class="form marketersList other productSeparator py-5" v-if="marketers[marketers.findIndex(m => m.name === marketer.name)].products.telephony[i] && marketers[marketers.findIndex(m => m.name === marketer.name)].products.telephony[i].fees && marketers[marketers.findIndex(m => m.name === marketer.name)].products.telephony[i].fees[fee] && /*(!feeSelectedElec || getFee(telFee.id, marketers.findIndex(m => m.name === marketer.name), 'elec') === feeSelectedElec) &&*/ (!typeSelected || telFee['type'][typeSelected === 'Residencial' ? 'residencial' : 'pyme']) && (isEdit || !telFee?.archived)" v-on:dblclick="showProduct(marketer._id, tel._id, telFee.id, 'telephony')">
                                                        <div class="d-flex column text" >
                                                            <template v-if="isEdit">
                                                                <div v-if="fee === 0 && i !== 0" v-on:click.stop="moveProduct(marketer, tel, 'telephony', i, -1)" v-on:dblclick.stop=""><i class="far fa-chevron-up pointer"></i></div>
                                                                <div v-if="fee === 0 && i !== marketer.products.telephony.length-1" v-on:click.stop="moveProduct(marketer, tel, 'telephony', i, 1)" v-on:dblclick.stop=""><i class="far fa-chevron-down pointer"></i></div>
                                                            </template>
                                                        </div>


                                                        <!--Nombre producto-->
                                                        <div class="text ellipsis pl-10" :class="{'product-name-with-icon': productToShow === 'all'}" data-weight="400" v-if="isEdit || !telFee?.archived">
                                                            <i v-if="productToShow === 'all'" :class="['far', productIcon('telephony'), 'product-type-icon']"></i>
                                                            <span class="ellipsis">{{ tel.name }}</span>
                                                        </div>
                                                        <!--Nombre tarifa-->
                                                        <div class="text ellipsis pl-10" data-weight="400">{{ telFee.name }}</div>

                                                        <!--Comentario-->
                                                        <div v-if="isEdit" class="d-flex justify-center" @dblclick.stop>
                                                            <i class="far fa-trash pointer" data-color="rojo" @click="deleteProductFee(marketer._id, tel._id, telFee.id)" />
                                                        </div>
                                                        <div v-else class="text-center" v-if="telFee.comment" v-on:click="showComment(telFee.comment)"><i class="far fa-circle-info text pointer"></i></div>
                                                    </div>
                                                </template>
                                            </template>
                                        </template>
                                        <template v-else-if="productToShow !== 'all'">
                                            <div data-weight="600">No hay productos de telefonía</div>
                                        </template>
                                    </template>

                                    <!--Productos de Alarmas-->
                                    <template v-if="isShowingProductType('alarm') && seeProducts[marketer._id]">
                                        <template v-if="marketer.products && marketer.products.alarm && marketer.products.alarm.length > 0">
                                            <template class="w-100" v-for="(alarm, i) in marketer.products.alarm">
                                                <div class="form marketersList alarm productSeparator py-5" v-if="(marketers[marketers.findIndex(m => m.name === marketer.name)].products.alarm[i] && !typeSelected || alarm['type'][typeSelected === 'Residencial' ? 'residencial' : 'pyme']) && (isEdit || !alarm?.archived)" v-on:dblclick="showProduct(marketer._id, alarm._id, null, 'alarm')">
                                                    <div class="d-flex column text" >
                                                        <template v-if="isEdit">
                                                            <div v-if="i !== 0" v-on:click.stop="moveProduct(marketer, alarm, 'alarm', i, -1)" v-on:dblclick.stop=""><i class="far fa-chevron-up pointer"></i></div>
                                                            <div v-if="i !== marketer.products.alarm.length-1" v-on:click.stop="moveProduct(marketer, alarm, 'alarm', i, 1)" v-on:dblclick.stop=""><i class="far fa-chevron-down pointer"></i></div>
                                                        </template>
                                                    </div>


                                                    <!--Nombre producto-->
                                                    <div class="text ellipsis pl-10" :class="{'product-name-with-icon': productToShow === 'all'}" data-weight="400" v-if="isEdit || !alarm?.archived">
                                                        <i v-if="productToShow === 'all'" :class="['far', productIcon('alarm'), 'product-type-icon']"></i>
                                                        <span class="ellipsis">{{ alarm.name }}</span>
                                                    </div>

                                                    <!--Comentario-->
                                                    <div class="text-center" v-if="alarm.comment" v-on:click="showComment(alarm.comment)"><i class="far fa-circle-info text pointer"></i></div>
                                                </div>
                                            </template>
                                        </template>
                                        <template v-else-if="productToShow !== 'all'">
                                            <div data-weight="600">No hay productos de alarmas</div>
                                        </template>
                                    </template>

                                    <!--Productos de autoconsumo-->
                                    <template v-if="isShowingProductType('selfSupply') && seeProducts[marketer._id]">
                                        <template v-if="marketer.products && marketer.products.selfSupply && marketer.products.selfSupply.length > 0">
                                            <template class="w-100" v-for="(ss, i) in marketer.products.selfSupply">
                                                <template v-if="ss" v-for="(ssFee, fee) in ss.fees">
                                                    <div class="form marketersList other productSeparator py-5" v-if="marketers[marketers.findIndex(m => m.name === marketer.name)].products.selfSupply[i] && marketers[marketers.findIndex(m => m.name === marketer.name)].products.selfSupply[i].fees && marketers[marketers.findIndex(m => m.name === marketer.name)].products.selfSupply[i].fees[fee] && (!typeSelected || ssFee['type'][typeSelected === 'Residencial' ? 'residencial' : 'pyme']) && (isEdit || !ssFee?.archived)" v-on:dblclick="showProduct(marketer._id, ss._id, ssFee.id, 'selfSupply')">
                                                        <div class="d-flex column text" >
                                                            <template v-if="isEdit">
                                                                <div v-if="fee === 0 && i !== 0" v-on:click.stop="moveProduct(marketer, ss, 'selfSupply', i, -1)" v-on:dblclick.stop=""><i class="far fa-chevron-up pointer"></i></div>
                                                                <div v-if="fee === 0 && i !== marketer.products.selfSupply.length-1" v-on:click.stop="moveProduct(marketer, ss, 'selfSupply', i, 1)" v-on:dblclick.stop=""><i class="far fa-chevron-down pointer"></i></div>
                                                            </template>
                                                        </div>


                                                        <!--Nombre producto-->
                                                        <div class="text ellipsis pl-10" :class="{'product-name-with-icon': productToShow === 'all'}" data-weight="400" v-if="isEdit || !ssFee?.archived">
                                                            <i v-if="productToShow === 'all'" :class="['far', productIcon('selfSupply'), 'product-type-icon']"></i>
                                                            <span class="ellipsis">{{ ss.name }}</span>
                                                        </div>

                                                        <!--Nombre tarifa-->
                                                        <div class="text ellipsis pl-10" data-weight="400">{{ ssFee.name }}</div>

                                                        <!--Comentario-->
                                                        <div class="text-center" v-if="ssFee.comment" v-on:click="showComment(ssFee.comment)"><i class="far fa-circle-info text pointer"></i></div>
                                                    </div>
                                                </template>
                                            </template>
                                        </template>
                                        <template v-else-if="productToShow !== 'all'">
                                            <div data-weight="600">No hay productos de autoconsumo</div>
                                        </template>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="opacity-5" v-if="marketers && marketers.length === 0" data-align="center">¡No hay ningún producto!</div>
            </div>
        </div>
    </div>
</template>

<script>
import {getType} from "@amcharts/amcharts5/.internal/core/util/Type";

export default {
    props:['basicData'],
    data() {
        return {
            marketers: [],
            marketerFilter: false,
            activeMarketers: [],
            typeFeed: {
                all: {
                    title: 'Todos',
                    icon: 'list'
                },
                electricity : {
                    title: 'Luz',
                    icon: 'bolt'
                },
                gas : {
                    title: 'Gas',
                    icon: 'fire-flame-simple'
                },
                dual: {
                    title: 'Dual',
                    icon: ''
                },
                telephony: {
                    title: 'Telefonía',
                    icon: 'phone'
                }
                ,
                alarm: {
                    title: 'Alarmas',
                    icon: 'bell-on'
                },
                selfSupply: {
                    title: 'Autoconsumo',
                    icon: 'solar-panel'
                }
            },
            productToShow : 'all',
            searchMarketerText: '',
            marketerSelectedToSee: '',
            validDates: {
                start: null,
                end: null
            },
            archiveProductsNotListed: false,
            seeAdd : false,
            seeAddCommissions: false,
            seeLoadMassive: false,
            fileName: '',
            dataMarketer : '',
            isSeeingCreateMarketer: false,
            seeZocoMarketers: false,
            filters: {
                checkbox: {
                    accountsAvailable: {
                        title: 'Productos',
                        data: []
                    }
                },
                radio: {
                    sortBy: {
                        title: 'Ordenar por',
                        checked: 0,
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
                                title: 'Más antiguo',
                                value: 2
                            },
                            {
                                title: 'Más reciente',
                                value: 3
                            },
                        ]
                    }
                }
            },
            isEdit : false,
            seeComment: false,
            activeComment: '',
            loading:'',
            filterByFees: false,
            feeSelectedElec: '',
            feeSelectedGas: '',
            typeSelected:'',
            typeProductSelected: 'Todos',
            typeFilter: false,
            typeProductFilter: false,
            seeFees:null,
            seeProducts: {all: true},
            productDeleted: false,
        }
    },
    created(){
        // Filtro por comercializadora
        if(this.$cookies.get('selectMarketer')){
            this.activeMarketers = this.$cookies.get('selectMarketer')
            if (!Array.isArray(this.activeMarketers)){ this.activeMarketers = []}
        }
        // Filtro por Tipo de producto
        if(this.$cookies.get('typeProduct')){
            this.typeSelected = this.$cookies.get('typeProduct') === 'r' ? 'Residencial' : (this.$cookies.get('typeProduct') === 'p' ? 'PYME' : '')
        }
        // Filtro por tarifa
        if(this.$cookies.get('feeElecProduct')){
            this.feeSelectedElec = this.$cookies.get('feeElecProduct')
        }
        if(this.$cookies.get('feeGasProduct')){
            this.feeSelectedGas = this.$cookies.get('feeGasProduct')
        }

        // Filtro por tarifa
        if(this.$cookies.get('productType')){
            this.typeProductSelected = this.$cookies.get('productType')
            this.productToShow = Object.entries(this.typeFeed).find(([key, value]) => value.title === this.typeProductSelected)?.[0]
        }

        this.fetchAllMarketers()
    },
    methods: {
        resetSearch(){

            this.searchMarketerText = ''

            this.fetchAllMarketers()
        },
        async fetchAllMarketers() {
            await axios.get('/api/marketers',{
                params: { assignContractTo: !!this.seeZocoMarketers ? '65cb57489c2c285441086a43' : null }
            })
                .then((res) => {
                    this.marketers = res.data.marketers;
                    for(let marketer of this.marketers){
                        this.seeProducts[marketer["_id"]] = false;
                    }

                    //Añado las comercializadoras a activeMarketers si no hay cookies
                    if(this.activeMarketers.length === 0){
                        for(let marketer of this.marketers){
                            this.activeMarketers.push(marketer["_id"])
                        }
                        this.$cookies.set('selectMarketer', this.activeMarketers)
                    }
                })
                .catch(err => console.error(err))
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
        isShowingProductType(type) {
            return this.productToShow === type || this.productToShow === 'all'
        },
        removeAccents(str) {
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        },
        productIcon(type) {
            const icons = {
                electricity: 'fa-bolt',
                gas: 'fa-fire-flame-simple',
                telephony: 'fa-phone',
                alarm: 'fa-bell-on',
                selfSupply: 'fa-solar-panel'
            }

            return icons[type] || ''
        },
         normalizeId(idLike) {
            if (!idLike) return '';
            if (typeof idLike === 'string') return idLike;
            if (idLike.$oid) return idLike.$oid;
            return String(idLike);
        },

        showProduct(marketerId, productId, feeId, productType = null) {
            const type = productType || this.productToShow;

            const mId  = this.normalizeId(marketerId);
            const pId  = this.normalizeId(productId);
            const fId  = this.normalizeId(feeId);

            let route = '/marketers/' + mId + '?type=' + type + '&productId=' + pId;

            if (type !== 'alarm') route += '&feeId=' + fId;

            this.$router.push(route);
        },
        actionLink(route){
            this.$router.push(route);
        },
        filterByMarketer(marketer) {

            //Si no se pasa una comercializadora, se añaden todas
            if(marketer === undefined){
                this.activeMarketers = this.marketers.map(m => m._id)
            //Si están todas seleccionadas y se selecciona 1, dejo solo la seleccionada
            }else if(this.activeMarketers.length === this.marketers.length) {
                this.activeMarketers = [marketer._id]
            //Si ya está seleccionada, se borra del array
            }else if(this.activeMarketers.includes(marketer._id)){
                this.activeMarketers = this.activeMarketers.filter(m => m !== marketer._id)
            //Si no está seleccionada, se añade
            }else {
                this.activeMarketers.push(marketer._id)
            }


            //Actualizo la cookie
            this.$cookies.set('selectMarketer', this.activeMarketers)
        },
        filterByFee(type, fee){
            if(type === 'electricity'){
                this.feeSelectedElec = fee;
                if(!fee){
                    this.$cookies.remove('feeElecProduct')
                }else{
                    this.$cookies.set('feeElecProduct', fee)
                }
            }else if(type === 'gas'){
                this.feeSelectedGas = fee
                if(!fee){
                    this.$cookies.remove('feeGasProduct')
                }else{
                    this.$cookies.set('feeGasProduct', fee)
                }
            }

            //console.log(`Tarifa -> ${fee}`);
            this.showFeeFilter();
        },
        filterByType(type){

            if(type === 'r'){
                this.typeSelected = "Residencial"
                this.$cookies.set('typeProduct', type)
            }
            else if(type === 'p'){
                this.typeSelected = "PYME"
                this.$cookies.set('typeProduct', type)
            }
            else{
                this.typeSelected = ''
                this.$cookies.remove('typeProduct')
            }

            this.showTypeFilter();
        },
        showFilterMarketer() {
            this.marketerFilter = !this.marketerFilter

            //Cierro otros filtros
            this.filterByFees = false;
            this.typeFilter = false;
            this.typeProductFilter = false;
        },
        showFeeFilter(){
            this.filterByFees = !this.filterByFees;

            //Cierro otros filtros
            this.marketerFilter = false;
            this.typeFilter = false;
            this.typeProductFilter = false;
        },
        showTypeFilter(){
            this.typeFilter = !this.typeFilter;

            //Cierro otros filtros
            this.marketerFilter = false;
            this.filterByFees = false;
            this.typeProductFilter = false;
        },
        showProductTypeFilter(){
            this.typeProductFilter = !this.typeProductFilter;

            //Cierro otros filtros
            this.marketerFilter = false;
            this.typeFilter = false;
            this.filterByFees = false;
        },
        hideCustomSelects(){

            this.isSeeingCreateMarketer = false;

            //Cierro los filtros
            this.marketerFilter = false;
            this.filterByFees = false;
            this.typeFilter = false;
            this.typeProductFilter = false;
        },
        toggleType(index, type){
            if(!this.isEdit){
                this.productToShow = index
                this.typeProductSelected = type.title
                this.typeProductFilter = false;

                if (index === 'all') {
                    this.feeSelectedElec = ''
                    this.feeSelectedGas = ''
                    this.filterByFees = false

                    this.$cookies.remove('productType')
                    this.$cookies.remove('feeElecProduct')
                    this.$cookies.remove('feeGasProduct')
                } else {
                    this.$cookies.set('productType', type.title)
                }
            }
        },
        getFee(id, iMarketer, type){

            let fees = '';
            let feeName = '';

            if(type === "electricity"){
                fees = this.marketers[iMarketer].fees.electricity

            }else if (type === "gas"){
                fees = this.marketers[iMarketer].fees.gas
            }

            for (let i = 0; i < fees.length; i++) {
                if(fees[i].id.$oid == id.$oid){
                    feeName = fees[i].name
                }
            }

            return(feeName)
        },
        getFullMarketer(marketer) {
            const marketerId = this.normalizeId(marketer?._id)
            const source = this.marketers.find((marketerNow) => this.normalizeId(marketerNow._id) === marketerId)

            return source ? JSON.parse(JSON.stringify(source)) : marketer
        },
        showAddProduct(marketer){
            if(marketer){
                this.dataMarketer = this.getFullMarketer(marketer)
            }

            this.seeAdd = !this.seeAdd
        },
        showAddCommissions(marketer){
            if(marketer){
                this.dataMarketer = this.getFullMarketer(marketer)
            }

            this.seeAddCommissions = !this.seeAddCommissions
        },
        changeEdit(){
            this.isEdit = !this.isEdit;
        },

        async updateMarketers(){

            const updateProducts = () => {
                this.loading = !this.loading

                axios.post('/api/marketers/generalUpdateMarketers', {marketers: this.marketers})
                    .then((res) => {

                        Swal.fire({
                            icon: "success",
                            title: "Productos Actualizados",
                            text: "Los productos han sido actualizados correctamente",
                            timer: 1500,
                            timerProgressBar: true
                        });

                        this.loading = !this.loading
                        this.isEdit = !this.isEdit
                        this.productsDeleted = false;
                    })
                    .catch((err) => {
                        console.log(err)
                        this.loading = !this.loading
                    })
            }

            if (this.productsDeleted) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Productos eliminados',
                    text: 'Los productos eliminados no podrán ser recuperados. ¿Quiere continuar?',
                    showCancelButton: true,
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Cancelar'
                }).then((res) => {
                    if(res.isConfirmed){
                      updateProducts()
                    }
                })
            } else {
                updateProducts()
            }

            /*for (let i = 0; i < this.filteredMarketers.length; i++) {
                await axios.put(`/api/marketers/${this.filteredMarketers[i]._id}`, { marketer: this.filteredMarketers[i]})
                .then((res) => {
                    console.log(res)
                    if(i == (this.filteredMarketers.length)-1){
                        Swal.fire({
                        icon: "success",
                        title: "Productos Actualizados",
                        text: "Los productos han sido actualizados correctamente"
                    });
                    this.loading = !this.loading
                    this.isEdit = !this.isEdit
                    }
                })
                .catch((err) => {
                    console.error(err)
                    Swal.fire({
                        icon: "error",
                        title: "Ha ocurrido un error",
                        text: "No se ha guardado correctamente"
                    });
                    this.loading = !this.loading
                })
            }*/
        },
        moveProduct(marketer, product, type, index, value){

            //Muevo el producto en local
            this.marketers.forEach((marketerNow) => {

                if (marketerNow.name === marketer.name){

                    console.log('extractProd -->', marketerNow['products'], type)

                    //Saco el producto
                    let extractProd = marketerNow['products'][type].splice(index ,1)[0]

                    marketerNow['products'][type].splice(index + value ,0, extractProd)
                }
            })


            //console.log('marketers --> ', this.marketers)


        },
        showComment(comment){
            this.seeComment = !this.seeComment
            this.activeComment = comment
        },
        toggleSeeFees(ind){
            this.seeFees = this.seeFees === ind ? null : ind
        },
        deleteProductFee(marketerId ,productId, feeId){
            const productType = this.productToShow;

            this.marketers.forEach((marketer) => {
                if (marketer._id === marketerId) {

                    const products = marketer.products[productType];

                    marketer.products[productType] = products.filter((product) => {
                        if (product._id.$oid === productId.$oid) {

                            const feeIndex = product.fees.findIndex(
                                fee => fee.id.$oid === feeId.$oid
                            );

                            if (feeIndex !== -1) {
                                product.fees.splice(feeIndex, 1);
                            }

                            //Si se queda sin fees → eliminar producto
                            return product.fees.length > 0;
                        }

                        return true; // mantener otros productos
                    });
                }
            });

            this.productsDeleted = true;
        },
        deleteGasProductFee(fee, marketerInd, product, productInd){


            //saco el producto
            //marketers[marketers.findIndex(m => m.name === marketer.name)].products.gas[ind].fees

            //saco el indice de la tarifa
            let feeSelectedInd = product.fees.findIndex((feeNow) => feeNow.id.$oid === fee.id.$oid)


            //Si no hay más tarifas
            if (product.fees.length === 1){

                Swal.fire({
                    icon: 'warning',
                    title: 'Se eliminará el producto',
                    text: 'Si desvinculas la tarifa se eliminara el producto ya que no hay más tarifas vinculadas',
                    confirmButtonText: 'Desvincular y eliminar',
                    confirmButtonColor: 'red',
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar',
                    focusCancel: true
                }).then((res) => {

                    if (res.isConfirmed){
                        //elimino el producto
                        this.marketers[marketerInd].products.gas.splice(productInd, 1)

                        //guardo en la bbdd
                        axios.put('/api/marketers/deleteProductFee', {marketer: this.marketers[marketerInd], productInd: productInd, feeInd: feeSelectedInd, deleteProd: true, type: 'gas'})
                            .then((res) => {

                            })
                            .catch((err) => {
                                console.log(err)
                            })

                    }
                })

            }else if (product.fees.length > 1){

                Swal.fire({
                    icon: 'warning',
                    title: '¿Estás seguro?',
                    text: 'Se desvinculara la tarifa del producto',
                    confirmButtonText: 'Desvincular',
                    confirmButtonColor: 'red',
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar',
                    focusCancel: true
                }).then((res) => {

                    if (res.isConfirmed){

                        if (feeSelectedInd !== -1)
                            this.marketers[marketerInd].products.gas[productInd].fees.splice(feeSelectedInd, 1)

                        //guardo en la bbdd
                        axios.put('/api/marketers/deleteProductFee', {marketer: this.marketers[marketerInd], productInd: productInd, feeInd: feeSelectedInd, deleteProd: false, type: 'gas'})
                            .then((res) => {

                                //establezco el nuevo índice
                                this.feeSelect = this.marketer.products.gas[productInd].fees.findIndex((feeNow) => feeNow.id.$oid === this.feeId)
                            })
                            .catch((err) => {
                                console.log(err)
                            })


                    }
                })
            }

            //cierro el select
            this.seeFees = null

        },
        toggleProducts(id){
            if(id === "all"){
                this.seeProducts.all = !this.seeProducts.all;
                //Al pulsar all, cambiar todos al estado de all
                for(let product in this.seeProducts){
                    this.seeProducts[product] = this.seeProducts.all;
                }
            }else{
                this.seeProducts[id] = !this.seeProducts[id];

                //Si alguno abierto y all a false, cambiar all a true
                if(Object.keys(this.seeProducts).slice(1).some(key => this.seeProducts[key] === true)){
                    this.seeProducts.all = true;
                }
                //Si todos cerrados y all a true, cambiar all a false
                if(Object.keys(this.seeProducts).slice(1).every(key => this.seeProducts[key] === false)){
                    this.seeProducts.all = false;
                }
            }
        },
        refreshList(){
            //recargo las comercializadoras
            this.fetchAllMarketers()
        },
        handleFile(event){
            let file = event.target.files[0];
            if (file) {
                this.fileName = file.name;
            }
        },
        async uploadMarketerPrices(){
            //Compruebo las fechas
            const { start, end } = this.validDates || {}
            const hasStart = !!start
            const hasEnd = !!end

            // Hay end pero no start
            if (hasEnd && !hasStart) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor, ingresa una fecha de inicio'
                })
                return
            }

            // End anterior a Start
            if (hasStart && hasEnd && moment(end).isBefore(moment(start))) {
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'La fecha de fin debe ser posterior a la fecha de inicio'
                })
                return
            }

            this.seeLoadMassive = false;
            this.loading = true;

            try {

                const data = await this.dumpMarketers(this.$refs.inputExcel.files[0]);

                await this.fetchAllMarketers();

                if(data.warnings?.length){
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Hubo algún problema',
                        text: 'Algunos productos tienen mal configuradas las fees. Se han guardado con fee mínimo 0'
                    })
                } else {
                    await Swal.fire({
                        icon: "success",
                        title: "Carga satisfactoria",
                        text: "Los productos han sido creados o actualizados correctamente",
                        timer: 1500,
                        timerProgressBar: true
                    })
                }

                this.validDates = {
                    start: null,
                    end: null
                };

            } catch (error){

                const errorText = error.response?.data?.error || "Error inesperado";

                await Swal.fire({
                    icon: "error",
                    title: "Carga incorrecta",
                    text: errorText
                })

                this.seeLoadMassive = true;

            } finally {

                this.loading = false;
                this.fileName = "";
                this.$refs.inputExcel.value = "";

            }
        },
        toggleSeeMarketers(){
            this.seeZocoMarketers = !this.seeZocoMarketers;
            this.fetchAllMarketers().then(() => {
                this.activeMarketers = this.filteredMarketers.map(m => m._id);
            })
        },
        async dumpMarketers(file){

            let formData = new FormData();
            formData.append('file', file);
            formData.append('dates', JSON.stringify(this.validDates));
            formData.append('archiveProductsNotListed', this.archiveProductsNotListed)

            const res = await axios.post('/api/marketers/dumpMarketers', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                }
            });

            return res.data;
        },
        formatDate(date) {
            return moment(date).format("D/M/YYYY");
        },
    },
    computed:{
        filteredMarketers() {
            let filteredMarketers = [];

            let marketers = JSON.parse(JSON.stringify(this.marketers)).filter(marketer =>
                this.basicData?.userLogged?.marketers?.includes(marketer._id) ||
                marketer.createdBy === this.basicData?.userLogged?._id
            ) || [];

            const normalizeText = (value) => {
                return this.removeAccents(
                    String(value || '').replace(/\s+/g, '').toLowerCase()
                );
            };

            const searchText = normalizeText(this.searchMarketerText);

            const selectedClientType = this.typeSelected === 'Residencial'
                ? 'residencial'
                : this.typeSelected === 'PYME'
                    ? 'pyme'
                    : null;

            const feeIsVisible = (fee) => {
                if (!fee) return false;

                if (!this.isEdit && fee.archived) {
                    return false;
                }

                if (selectedClientType && !fee?.type?.[selectedClientType]) {
                    return false;
                }

                return true;
            };

            const productMatchesSearch = (product, productType) => {
                if (!searchText) return true;

                let name = product?.name || '';

                if (productType === 'dual') {
                    name = `${product?.electricity || ''} ${product?.gas || ''}`;
                }

                return normalizeText(name).includes(searchText);
            };

            const productHasVisibleData = (product, marketer, productType) => {
                if (!productMatchesSearch(product, productType)) {
                    return false;
                }

                if (productType === 'alarm') {
                    if (!this.isEdit && product?.archived) {
                        return false;
                    }

                    if (selectedClientType && !product?.type?.[selectedClientType]) {
                        return false;
                    }

                    return true;
                }

                if (!Array.isArray(product?.fees) || product.fees.length === 0) {
                    return false;
                }

                return product.fees.some((fee) => {
                    if (!feeIsVisible(fee)) {
                        return false;
                    }

                    const marketerIndex = this.marketers.findIndex(m => m.name === marketer.name);

                    if (productType === 'electricity' && this.feeSelectedElec) {
                        return this.getFee(fee.id, marketerIndex, 'electricity') === this.feeSelectedElec;
                    }

                    if (productType === 'gas' && this.feeSelectedGas) {
                        return this.getFee(fee.id, marketerIndex, 'gas') === this.feeSelectedGas;
                    }

                    return true;
                });
            };

            for (let key in marketers) {
                let marketer = marketers[key];

                if (this.productToShow === 'all') {
                    const matchesSearch = !searchText ||
                        normalizeText(marketer.name).includes(searchText) ||
                        Object.values(marketer.products || {}).some(products =>
                            Array.isArray(products) &&
                            products.some(product =>
                                normalizeText(product?.name).includes(searchText) ||
                                normalizeText(product?.electricity).includes(searchText) ||
                                normalizeText(product?.gas).includes(searchText)
                            )
                        );

                    if (matchesSearch || this.isEdit) {
                        filteredMarketers.push(marketer);
                    }

                    continue;
                }

                if (!marketer.products) {
                    continue;
                }

                const products = marketer.products[this.productToShow];

                if (!Array.isArray(products)) {
                    continue;
                }

                marketer.products[this.productToShow] = products.filter(product =>
                    productHasVisibleData(product, marketer, this.productToShow)
                );

                if (marketer.products[this.productToShow].length > 0 || this.isEdit) {
                    filteredMarketers.push(marketer);
                }
            }

            if (Object.keys(filteredMarketers).length > 0) {
                switch (this.filters.radio.sortBy.checked) {
                    case 0:
                        filteredMarketers = filteredMarketers.sort((a, b) => {
                            if (a.name < b.name) return -1;
                            if (a.name > b.name) return 1;
                            return 0;
                        });
                        break;

                    case 1:
                        filteredMarketers = filteredMarketers.sort((a, b) => {
                            if (a.name < b.name) return 1;
                            if (a.name > b.name) return -1;
                            return 0;
                        });
                        break;

                    case 2:
                        filteredMarketers = filteredMarketers.sort((a, b) => {
                            let aDate = new Date(a.createdAt);
                            let bDate = new Date(b.createdAt);

                            if (aDate < bDate) return -1;
                            if (aDate > bDate) return 1;

                            return 0;
                        });
                        break;

                    default:
                        filteredMarketers = filteredMarketers.sort((a, b) => {
                            let aDate = new Date(a.createdAt);
                            let bDate = new Date(b.createdAt);

                            if (aDate < bDate) return 1;
                            if (aDate > bDate) return -1;

                            return 0;
                        });
                        break;
                }
            }

            return filteredMarketers;
        },
        filteredElecFees(){
            let data = [];
            for (let i = 0; i < this.marketers.length; i++) {
                for (let j = 0; j < this.marketers[i].fees.electricity.length; j++) {
                    data.push(this.marketers[i].fees.electricity[j].name);
                }
            }
            let filterData = new Set(data);
            let filteredElecFees = [...filterData];

            return filteredElecFees;
        },
        filteredGasFees(){
            let data = [];
            for (let i = 0; i < this.marketers.length; i++) {
                for (let j = 0; j < this.marketers[i].fees.gas.length; j++) {
                    data.push(this.marketers[i].fees.gas[j].name);
                }
            }
            let filterData = new Set(data);
            let filteredElecFees = [...filterData];

            return filteredElecFees;
        },

        isReadOnly(){
            if (!this.basicData.userLogged)
                return true
            else (this.basicData.userLogged)
                return this.basicData.userLogged.permissions.includes('READONLY')
        },
        isDownZoco(){
            //Compruebo si esta debajo de Zoco

            let downZoco = false;
            let userNowToSee = this.basicData.userLogged;

            do{

                if (!!userNowToSee && userNowToSee.responsibles && userNowToSee.responsibles.length > 0){

                    userNowToSee = this.basicData.userListComplete.find((user) => user._id === userNowToSee.responsibles[0])

                    if (userNowToSee.responsibles[0] === '65cb57489c2c285441086a43') downZoco = true
                }

            }while(!downZoco && !!userNowToSee.responsibles && userNowToSee.responsibles.length !== 0)

            return downZoco
        },
        canSeeZoco(){

            //Compruebo si el usuario subdominio tiene marcado que se pueda ver o no
            let canSee = true;
            let userNowToSee = this.basicData.userLogged;

            do {
                if (!!userNowToSee && userNowToSee.label !== 'Usuario subdominio' && userNowToSee.responsibles && userNowToSee.responsibles.length > 0) {
                    userNowToSee = this.basicData.userListComplete.find((user) => user._id === userNowToSee.responsibles[0])
                }

            } while (userNowToSee.label !== 'Usuario subdominio')

            if (userNowToSee.label === 'Usuario subdominio' && userNowToSee._id !== this.basicData.userLogged._id)
                canSee = userNowToSee.agentsCanSeeZoco;

            return canSee
        },
        currentProductKey() {
            return this.productToShow
        },
    }
}
</script>

<style scoped>
.product-name-with-icon {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
}

.product-type-icon {
    width: 20px;
    min-width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #eaf5ff;
    color: var(--azul, #003f7f);
    font-size: 11px;
}

.product-name-with-icon .ellipsis {
    min-width: 0;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
</style>
