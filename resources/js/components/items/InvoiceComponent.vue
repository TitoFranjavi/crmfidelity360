<template>
    <div class="separator"></div>

    <div class="invoice-container" :class="{ expanded: !!ocrData }">
        <div
            v-if="ocrData"
            class="invoice-panel results-panel dashboard-card column"
        >
            <div class="scroll-content form">
                <!-- Cabecera -->
                <div class="d-flex justify-between align-center mb-20">
                    <div class="d-flex align-center" data-gap="15">
                        <button
                            class="custom-button"
                            data-size="small"
                            @click="resetToStart"
                        >
                            <i class="fa-solid fa-arrow-left"></i> Volver al
                            inicio
                        </button>
                        <h3
                            class="text ellipsis ml-10"
                            data-weight="600"
                            data-size="18"
                        >
                            {{ ocrData.cups }}
                        </h3>
                    </div>
                </div>

                <div class="separator h-2-px mx-auto mb-15"></div>

                <!-- Información fija -->
                <div class="d-flex column" data-gap="8">
                    <p data-size="16">
                        <span class="opacity-6"
                            ><i class="fa-light fa-user"></i> Titular:</span
                        >
                        <span data-weight="600">{{ ocrData.titular }}</span>
                    </p>
                    <p data-size="16">
                        <span class="opacity-6"
                            ><i class="fa-light fa-id-card"></i> CIF/NIF:</span
                        >
                        <span data-weight="600">{{ ocrData.cif_nif }}</span>
                    </p>
                    <p data-size="16">
                        <span class="opacity-6"
                            ><i class="fa-light fa-building"></i>
                            Comercializadora:</span
                        >
                        <span data-weight="600">{{
                            ocrData.comercializadora
                        }}</span>
                    </p>
                    <p data-size="16">
                        <span class="opacity-6"
                            ><i class="fa-light fa-utility-pole-double"></i>
                            Tarifa:</span
                        >
                        {{ ocrData.tarifa }}
                    </p>
                </div>

                <div class="separator h-2-px mx-auto my-20"></div>

                <!-- Periodo de facturación -->
                <div class="form-group">
                    <p class="text mb-10 opacity-6" data-size="16">
                        Periodo de facturación
                    </p>
                    <div
                        class="d-flex justify-center align-center"
                        data-gap="10"
                    >
                        <div class="input-group w-150-px-max">
                            <input
                                v-model="
                                    ocrData.periodo_facturacion.fecha_inicio
                                "
                                class="simple-input text-center"
                                type="date"
                            />
                        </div>
                        <span>—</span>
                        <div class="input-group w-150-px-max">
                            <input
                                v-model="ocrData.periodo_facturacion.fecha_fin"
                                class="simple-input text-center"
                                type="date"
                            />
                        </div>
                        <div class="input-group w-100-px-max ml-10">
                            <input
                                v-model="ocrData.dias_facturacion"
                                class="simple-input text-center"
                                type="number"
                            />
                        </div>
                        <span>días</span>
                    </div>
                </div>

                <div class="separator w-80 my-25 mx-auto"></div>

                <!-- Potencia contratada (editable) -->
                <div class="form-group">
                    <p class="text mb-10 opacity-6" data-size="16">
                        Potencia contratada (kW)
                    </p>
                    <div class="d-flex justify-center" data-gap="8">
                        <div
                            v-for="(v, key) in ocrData.potencias_contratadas"
                            :key="key"
                        >
                            <p class="text text-center">
                                {{ key.toUpperCase() }}
                            </p>
                            <div class="input-group w-80-px-max">
                                <input
                                    class="simple-input text-center"
                                    :class="
                                        comparisonsReady
                                            ? comparisons.potencias?.[key]
                                                ? 'ok'
                                                : 'fail'
                                            : ''
                                    "
                                    v-model.number="
                                        ocrData.potencias_contratadas[key]
                                    "
                                    type="number"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="separator w-80 my-25 mx-auto"></div>

                <!-- Energía consumida (editable) -->
                <div class="form-group">
                    <p class="text mb-10 opacity-6" data-size="16">
                        Energía consumida (kWh)
                    </p>
                    <div class="d-flex justify-center" data-gap="8">
                        <div
                            v-for="(v, key) in ocrData.energia_consumida"
                            :key="key"
                        >
                            <p class="text text-center">
                                {{ key.toUpperCase() }}
                            </p>
                            <div class="input-group w-80-px-max">
                                <input
                                    class="simple-input text-center"
                                    :class="
                                        comparisonsReady
                                            ? comparisons.consumos?.[key]
                                                ? 'ok'
                                                : 'fail'
                                            : ''
                                    "
                                    v-model.number="
                                        ocrData.energia_consumida[key]
                                    "
                                    type="number"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 💶 Precios potencia (editable) -->
                <div class="separator w-80 my-25 mx-auto"></div>

                <div class="form-group">
                    <p class="text mb-10 opacity-6" data-size="16">
                        Precio potencia (€/kW)
                    </p>
                    <div class="d-flex justify-center" data-gap="8">
                        <div
                            v-for="(v, key) in ocrData.precios_potencias"
                            :key="'precio-pot-' + key"
                        >
                            <p class="text text-center">
                                {{ key.toUpperCase() }}
                            </p>
                            <div class="input-group w-80-px-max">
                                <input
                                    class="simple-input text-center"
                                    :class="
                                        comparisons.precios_potencias
                                            ? comparisons.precios_potencias?.[
                                                  key
                                              ]
                                                ? 'ok'
                                                : 'fail'
                                            : ''
                                    "
                                    v-model.number="
                                        ocrData.precios_potencias[key]
                                    "
                                    type="number"
                                    step="0.000001"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 💶 Precios energía (editable) -->
                <div class="separator w-80 my-25 mx-auto"></div>

                <div class="form-group">
                    <p class="text mb-10 opacity-6" data-size="16">
                        Precio energía (€/kWh)
                    </p>
                    <div class="d-flex justify-center" data-gap="8">
                        <div
                            v-for="(v, key) in ocrData.precios_energia"
                            :key="'precio-ene-' + key"
                        >
                            <p class="text text-center">
                                {{ key.toUpperCase() }}
                            </p>
                            <div class="input-group w-80-px-max">
                                <input
                                    class="simple-input text-center"
                                    :class="
                                        comparisons.precios_energia
                                            ? comparisons.precios_energia?.[key]
                                                ? 'ok'
                                                : 'fail'
                                            : ''
                                    "
                                    v-model.number="
                                        ocrData.precios_energia[key]
                                    "
                                    type="number"
                                    step="0.000001"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones del detalle -->
                <div class="d-flex justify-center mt-20" data-gap="10">
                    <button
                        class="custom-button"
                        data-size="small"
                        data-bg="principal"
                        :disabled="isDetailChecking || !ocrData"
                        @click="recheckCurrent"
                    >
                        <i
                            v-if="!isDetailChecking"
                            class="fa-solid fa-rotate-right mr-5"
                        ></i>
                        <i v-else class="fas fa-spinner fa-spin mr-5"></i>
                        {{
                            isDetailChecking
                                ? "Comprobando..."
                                : "Comprobar otra vez"
                        }}
                    </button>
                </div>
            </div>
        </div>

        <div
            :class="[
                'invoice-panel upload-panel dashboard-card column',
                { wide: !ocrData },
            ]"
        >
            <div class="scroll-content form mt-20 pb-20">
                <!-- (1) SELECCIÓN INICIAL -->
                <template v-if="!ocrData && !uploadLocked">
                    <!-- Selector -->
                    <template v-if="startChoice === null">
                        <h2 class="main-title text text-center mb-10">
                            ¿QUÉ QUIERES HACER?
                        </h2>
                        <p
                            class="text text-center opacity-6 mb-25"
                            data-size="15"
                        >
                            Elige una opción para empezar.
                        </p>

                        <div class="start-grid">
                            <button
                                type="button"
                                class="start-card"
                                @click="chooseUpload"
                            >
                                <div class="start-icon">📎</div>
                                <div class="start-title">ADJUNTAR FACTURA</div>
                                <div class="start-sub">
                                    Sube PDFs o imágenes desde tu equipo
                                </div>
                            </button>

                            <button
                                type="button"
                                class="start-card"
                                @click="chooseStored"
                            >
                                <div class="start-icon">🗂️</div>
                                <div class="start-title">
                                    FACTURAS GUARDADAS
                                </div>
                                <div class="start-sub">
                                    Usa facturas ya guardadas en tu carpeta
                                </div>
                            </button>
                        </div>
                    </template>

                    <!-- ✅ ADJUNTAR -->
                    <template v-else-if="startChoice === 'upload'">
                        <div class="option-header mb-10">
                            <h2 class="section-title">ADJUNTAR FACTURA</h2>
                            <button
                                class="back-arrow-btn"
                                @click="backToChoice"
                                title="Volver"
                                aria-label="Volver"
                            >
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                        </div>

                        <div class="option-content">
                            <div
                                class="d-flex justify-center align-center"
                                data-gap="10"
                            >
                                <p class="text" data-size="14">
                                    Añade tu factura:
                                </p>
                                <div>
                                    <button
                                        type="button"
                                        class="custom-button"
                                        data-size="medium"
                                        data-bg="principal"
                                        data-mode="translucent"
                                        @click="$refs.file.click()"
                                    >
                                        Adjuntar
                                        <i class="far fa-paperclip"></i>
                                    </button>
                                    <input
                                        ref="file"
                                        type="file"
                                        accept="image/*,application/pdf"
                                        multiple
                                        @change="getFilesSelected"
                                        style="display: none"
                                    />
                                </div>
                            </div>

                            <div
                                v-if="selectedFiles.length"
                                class="selected-files-list mt-20"
                            >
                                <h4
                                    class="text mb-10"
                                    data-size="16"
                                    data-weight="600"
                                >
                                    Facturas seleccionadas ({{
                                        selectedFiles.length
                                    }})
                                </h4>

                                <ul class="files-list">
                                    <li
                                        v-for="(file, index) in selectedFiles"
                                        :key="RName(file) + '-' + index"
                                        class="file-item"
                                    >
                                        <span class="file-name">{{
                                            RName(file)
                                        }}</span>
                                        <button
                                            class="remove-file"
                                            @click="removeFile(index)"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div
                                class="d-flex justify-center mt-25"
                                data-gap="15"
                            >
                                <button
                                    v-if="firstFile"
                                    type="button"
                                    class="custom-button"
                                    data-size="regular"
                                    data-bg="principal"
                                    :disabled="isRendering"
                                    @click="sendToOCR()"
                                >
                                    <i
                                        v-if="!isRendering"
                                        class="far fa-paper-plane mr-5"
                                    ></i>
                                    <i
                                        v-else
                                        class="fas fa-spinner fa-spin mr-5"
                                    ></i>
                                    {{
                                        isRendering
                                            ? "Enviando…"
                                            : selectedFiles.length > 1
                                            ? "Enviar facturas"
                                            : "Enviar factura"
                                    }}
                                </button>

                                <button
                                    v-if="firstFile && !isRendering"
                                    type="button"
                                    class="custom-button"
                                    data-bg="rojo"
                                    data-size="regular"
                                    data-mode="translucent"
                                    @click="clearFiles"
                                >
                                    <i class="far fa-trash-alt mr-5"></i> Quitar
                                </button>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <div class="option-header mb-10">
                            <h2 class="section-title">FACTURAS GUARDADAS</h2>
                            <button
                                class="back-arrow-btn"
                                @click="backToChoice"
                                title="Volver"
                                aria-label="Volver"
                            >
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                        </div>

                        <div class="option-content">
                            <div class="stored-warning mb-15">
                                <p class="text" data-size="14">
                                    Se cargarán y adjuntarán automáticamente las
                                    facturas de tu CRM.
                                </p>
                            </div>

                            <div
                                class="d-flex justify-center align-center"
                                data-gap="10"
                            >
                                <p class="text" data-size="14">
                                    Cargar facturas:
                                </p>
                                <div>
                                    <button
                                        type="button"
                                        class="custom-button"
                                        data-size="medium"
                                        data-bg="principal"
                                        data-mode="translucent"
                                        :disabled="
                                            isLoadingStored || isAddingAllStored
                                        "
                                        @click="loadStoredInvoices"
                                    >
                                        {{
                                            isLoadingStored || isAddingAllStored
                                                ? "Cargando y adjuntando…"
                                                : "Cargar facturas guardadas"
                                        }}
                                        <i
                                            class="fa-regular fa-folder-open ml-5"
                                        ></i>
                                    </button>
                                </div>
                            </div>

                            <template v-if="storedLoaded">
                                <div
                                    v-if="isLoadingStored || isAddingAllStored"
                                    class="text-center mt-15"
                                >
                                    Cargando facturas guardadas...
                                </div>

                                <div v-else>
                                    <!-- SOLO seleccionadas -->
                                    <div
                                        v-if="selectedFiles.length"
                                        class="selected-files-list mt-20"
                                    >
                                        <h4
                                            class="text mb-10"
                                            data-size="16"
                                            data-weight="600"
                                        >
                                            Factura seleccionada
                                        </h4>

                                        <ul class="files-list">
                                            <li
                                                v-for="(
                                                    file, index
                                                ) in selectedFiles"
                                                :key="RName(file) + '-' + index"
                                                class="file-item"
                                            >
                                                <span class="file-name">{{
                                                    RName(file)
                                                }}</span>
                                                <button
                                                    class="remove-file"
                                                    @click="removeFile(index)"
                                                >
                                                    <i
                                                        class="fa-solid fa-xmark"
                                                    ></i>
                                                </button>
                                            </li>
                                        </ul>
                                    </div>

                                    <div
                                        v-else
                                        class="text-center mt-15 opacity-6"
                                    >
                                        No hay facturas seleccionadas.
                                    </div>

                                    <div
                                        class="d-flex justify-center mt-25"
                                        data-gap="15"
                                    >
                                        <button
                                            v-if="firstFile"
                                            type="button"
                                            class="custom-button"
                                            data-size="regular"
                                            data-bg="principal"
                                            :disabled="isRendering"
                                            @click="sendToOCR()"
                                        >
                                            <i
                                                v-if="!isRendering"
                                                class="far fa-paper-plane mr-5"
                                            ></i>
                                            <i
                                                v-else
                                                class="fas fa-spinner fa-spin mr-5"
                                            ></i>
                                            {{
                                                isRendering
                                                    ? "Enviando…"
                                                    : selectedFiles.length > 1
                                                    ? "Enviar facturas"
                                                    : "Enviar factura"
                                            }}
                                        </button>

                                        <button
                                            v-if="firstFile && !isRendering"
                                            type="button"
                                            class="custom-button"
                                            data-bg="rojo"
                                            data-size="regular"
                                            data-mode="translucent"
                                            @click="clearFiles"
                                        >
                                            <i
                                                class="far fa-trash-alt mr-5"
                                            ></i>
                                            Quitar
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </template>

                <template v-if="!ocrData && uploadLocked">
                    <!-- CABECERA -->
                    <div v-if="facturas.length" class="facturas-header">
                        <div class="facturas-header-left">
                            <h2>FACTURAS</h2>
                            <p class="facturas-subtitle">
                                Estado de comprobaciones
                            </p>
                        </div>

                        <div class="facturas-header-right">
                            <div class="view-switch">
    <i
        class="fa-solid fa-grip view-icon"
        :class="{ active: facturasView === 'cards' }"
        @click="facturasView = 'cards'"
        title="Vista tarjetas"
    ></i>

    <i
        class="fa-solid fa-list view-icon"
        :class="{ active: facturasView === 'list' }"
        @click="facturasView = 'list'"
        title="Vista lista"
    ></i>
</div>


                            <div class="search-wrapper">
                                <i
                                    class="fa-solid fa-magnifying-glass search-icon"
                                ></i>
                                <input
                                    v-model.trim="searchCups"
                                    class="cups-search-input"
                                    type="text"
                                    inputmode="search"
                                    placeholder="Buscar por CUPS (p. ej. ES0021...)"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="
                            facturasView === 'cards' && filteredFacturas.length
                        "
                        class="facturas-container big-grid mt-10"
                    >
                        <div
                            v-for="f in filteredFacturas"
                            :key="f.id"
                            class="factura-card big-card"
                            :class="f.status"
                        >
                            <div class="card-header">
                                <h3 class="text ellipsis big-title">
                                    {{ f.name }}
                                </h3>
                                <span class="status-pill" :class="f.status">
                                    <template v-if="f.status === 'pendiente'"
                                        >Pendiente</template
                                    >
                                    <template
                                        v-else-if="f.status === 'procesando'"
                                        >Recogiendo</template
                                    >
                                    <template v-else-if="f.status === 'comprobando'"
                                        >Comprobación</template
                                    >
                                    <template v-else-if="f.status === 'diferencias'"
                                        >Con diferencias</template
                                    >
                                    <template v-else-if="f.status === 'ok'"
                                        >OK</template
                                    >
                                    <template v-else>Error</template>
                                </span>
                            </div>

                            <div
                                class="card-body"
                                @click="
                                    f.ocrReady &&
                                        f.status !== 'error' &&
                                        verDetalleFactura(f)
                                "
                            >
                                <div v-if="!f.ocrReady" class="skeleton">
                                    <div class="sk-row w-60"></div>
                                    <div class="sk-row w-50"></div>
                                    <div class="sk-row w-80"></div>
                                    <p class="mt-6">
                                        <template
                                            v-if="f.status === 'procesando'"
                                        >
                                            📄 Recogiendo datos…
                                        </template>
                                        <template
                                            v-else-if="
                                                f.status === 'comprobando'
                                            "
                                        >
                                            🔍 Comprobación…
                                        </template>
                                        <template v-else>
                                            🕓 Pendiente…
                                        </template>
                                    </p>
                                </div>

                                <div v-else class="mini-grid big-mini-grid">
                                    <div>
                                        <strong>CUPS:</strong>
                                        <span class="mono">{{
                                            f.resumen.cups || "—"
                                        }}</span>
                                    </div>
                                    <div>
                                        <strong>Periodo:</strong>
                                        {{ f.resumen.periodo || "—" }}
                                    </div>
                                    <div>
                                        <strong>Comercializ.:</strong>
                                        {{ f.resumen.comercializadora || "—" }}
                                    </div>

                                    <p
                                        v-if="f.status === 'ok'"
                                        class="ok-text big-ok"
                                    >
                                        ✅ {{ f.resumen.resultado }}
                                    </p>
                                    <p
                                        v-if="f.status === 'error'"
                                        class="error-text big-error"
                                    >
                                        ❌ {{ f.error || "Error" }}
                                    </p>
                                </div>

                               <div class="card-actions big-actions">
    <!-- Ver detalle -->
    <i
        class="fa-solid fa-eye action-icon"
        :class="{ disabled: !f.ocrReady || f.status === 'error' }"
        @click.stop="
            f.ocrReady && f.status !== 'error' && verDetalleFactura(f)
        "
        title="Ver detalle"
    ></i>

    <!-- Reintentar -->
    <i
        class="fa-solid fa-rotate-right action-icon"
        :class="{
            disabled:
                f.status === 'procesando' ||
                f.status === 'comprobando',
        }"
        @click.stop="
            !(
                f.status === 'procesando' ||
                f.status === 'comprobando'
            ) && retryFactura(findIndexById(f.id))
        "
        title="Reintentar"
    ></i>

    <!-- Eliminar -->
    <i
        class="fa-solid fa-trash action-icon danger"
        :class="{
            disabled:
                f.status === 'procesando' ||
                f.status === 'comprobando',
        }"
        @click.stop="
            !(
                f.status === 'procesando' ||
                f.status === 'comprobando'
            ) && removeFacturaCard(findIndexById(f.id))
        "
        title="Eliminar"
    ></i>
</div>

                            </div>
                        </div>
                    </div>
                    <div
                        v-else-if="
                            facturasView === 'list' && filteredFacturas.length
                        "
                        class="facturas-list mt-10"
                    >
                        <!-- CABECERA -->
                        <div class="factura-row header">
                            <span>Factura</span>
                            <span>CUPS</span>
                            <span>Comercializadora</span>
                            <span>Periodo</span>
                            <span>Estado</span>
                            <span></span>
                        </div>

                        <!-- FILAS -->
                        <div
                            v-for="f in filteredFacturas"
                            :key="f.id"
                            class="factura-row"
                            :class="f.status"
                            @click="
                                f.ocrReady &&
                                    f.status !== 'error' &&
                                    verDetalleFactura(f)
                            "
                        >
                            <!-- Nombre factura -->
                            <span
                                class="factura-name"
                                :class="{ error: f.status === 'error' }"
                            >
                                {{ f.name || "—" }}
                            </span>

                            <!-- CUPS -->
                            <span class="mono">
                                {{ f.resumen?.cups || "—" }}
                            </span>

                            <!-- Comercializadora -->
                            <span class="ellipsis">
                                {{ f.resumen?.comercializadora || "—" }}
                            </span>

                            <!-- Periodo -->
                            <span>
                                {{ f.resumen?.periodo || "—" }}
                            </span>

                            <!-- Estado -->
                            <span>
                                <span class="status-pill" :class="f.status">
                                    <template v-if="f.status === 'error'">
                                        {{ f.error || "Error desconocido" }}
                                    </template>
                                    <template
                                        v-else-if="f.status === 'pendiente'"
                                    >
                                        Pendiente
                                    </template>
                                    <template
                                        v-else-if="f.status === 'procesando'"
                                    >
                                        Recogiendo
                                    </template>
                                    <template
                                        v-else-if="f.status === 'comprobando'"
                                    >
                                        Comprobación
                                    </template>
                                    <template v-else-if="f.status === 'diferencias'">
                                        Con diferencias
                                    </template>
                                    <template v-else> OK </template>
                                </span>
                            </span>

                            <!-- Acciones -->
                            <span class="row-actions">
                                <!-- Ver detalle -->
                                <button
                                    class="icon-btn"
                                    :disabled="
                                        !f.ocrReady || f.status === 'error'
                                    "
                                    @click.stop="verDetalleFactura(f)"
                                    title="Ver detalle"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                </button>

                                <!-- Reintentar -->
                                <button
                                    class="icon-btn"
                                    :disabled="
                                        f.status === 'procesando' ||
                                        f.status === 'comprobando'
                                    "
                                    @click.stop="
                                        retryFactura(findIndexById(f.id))
                                    "
                                    title="Reintentar"
                                >
                                    <i class="fa-solid fa-rotate-right"></i>
                                </button>

                                <!-- Eliminar -->
                                <button
                                    class="icon-btn danger"
                                    :disabled="
                                        f.status === 'procesando' ||
                                        f.status === 'comprobando'
                                    "
                                    @click.stop="
                                        removeFacturaCard(findIndexById(f.id))
                                    "
                                    title="Eliminar"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- SIN RESULTADOS -->
                    <div v-else class="text-center mt-20">
                        <p class="opacity-6 mb-10">
                            No hay facturas que coincidan con la búsqueda.
                        </p>
                        <button
                            class="custom-button"
                            data-size="regular"
                            data-bg="principal"
                            @click="unlockUpload"
                        >
                            + Añadir más facturas
                        </button>
                    </div>
                </template>

                <!-- (3) RESULTADO / FACTURA -->
                <template v-if="ocrData && sipsData">
                    <div class="view-toggle mt-10">
                        <button
                            type="button"
                            class="view-toggle-button"
                            :class="{ active: rightPanelView === 'resultado' }"
                            @click="rightPanelView = 'resultado'"
                        >
                            Resultado
                        </button>
                        <button
                            type="button"
                            class="view-toggle-button"
                            :class="{ active: rightPanelView === 'factura' }"
                            @click="rightPanelView = 'factura'"
                        >
                            Factura
                        </button>
                    </div>

                    <div
                        v-if="rightPanelView === 'resultado'"
                        class="comparison-container mt-15"
                    >
                        <div class="form-group">
                            <p class="text mb-10 opacity-6" data-size="16">
                                Potencia contratada (kW)
                            </p>
                            <div class="d-flex justify-center" data-gap="8">
                                <div
                                    v-for="(
                                        val, key
                                    ) in sipsData.potencias_contratadas"
                                    :key="key"
                                >
                                    <p class="text text-center">
                                        {{ key.toUpperCase() }}
                                    </p>
                                    <div class="input-group w-80-px-max">
                                        <input
                                            class="simple-input text-center"
                                            :class="
                                                comparisonsReady
                                                    ? comparisons.potencias?.[
                                                          key
                                                      ]
                                                        ? 'ok'
                                                        : 'fail'
                                                    : ''
                                            "
                                            :value="val"
                                            readonly
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="separator w-80 my-25 mx-auto"></div>

                        <div class="form-group">
                            <p class="text mb-10 opacity-6" data-size="16">
                                Energía consumida (kWh)
                            </p>
                            <div
                                class="d-flex justify-center flex-wrap"
                                data-gap="8"
                            >
                                <div
                                    v-for="(v, key) in averageSipsConsumption"
                                    :key="'consumo-' + key"
                                >
                                    <p class="text text-center">
                                        {{ key.toUpperCase() }}
                                    </p>
                                    <div class="input-group w-80-px-max">
                                        <input
                                            class="simple-input text-center"
                                            :class="
                                                comparisonsReady
                                                    ? comparisons.consumos?.[
                                                          key
                                                      ]
                                                        ? 'ok'
                                                        : 'fail'
                                                    : ''
                                            "
                                            :value="Math.round(v)"
                                            readonly
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="currentContractPrices"
                            class="separator w-80 my-25 mx-auto"
                        ></div>

                        <div v-if="currentContractPrices" class="form-group">
                            <p class="text mb-10 opacity-6" data-size="16">
                                Precio potencia contrato (€/kW)
                            </p>
                            <div class="d-flex justify-center" data-gap="8">
                                <div
                                    v-for="(
                                        v, key
                                    ) in currentContractPrices.precios_potencias"
                                    :key="'contrato-pot-' + key"
                                >
                                    <p class="text text-center">
                                        {{ key.toUpperCase() }}
                                    </p>
                                    <div class="input-group w-80-px-max">
                                        <input
                                            class="simple-input text-center"
                                            :class="
                                                comparisons.precios_potencias
                                                    ? comparisons
                                                          .precios_potencias?.[
                                                          key
                                                      ]
                                                        ? 'ok'
                                                        : 'fail'
                                                    : ''
                                            "
                                            :value="v"
                                            readonly
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="separator w-80 my-15 mx-auto"></div>

                            <p class="text mb-10 opacity-6" data-size="16">
                                Precio energía contrato (€/kWh)
                            </p>
                            <div class="d-flex justify-center" data-gap="8">
                                <div
                                    v-for="(
                                        v, key
                                    ) in currentContractPrices.precios_energia"
                                    :key="'contrato-ene-' + key"
                                >
                                    <p class="text text-center">
                                        {{ key.toUpperCase() }}
                                    </p>
                                    <div class="input-group w-80-px-max">
                                        <input
                                            class="simple-input text-center"
                                            :class="
                                                comparisons.precios_energia
                                                    ? comparisons
                                                          .precios_energia?.[
                                                          key
                                                      ]
                                                        ? 'ok'
                                                        : 'fail'
                                                    : ''
                                            "
                                            :value="v"
                                            readonly
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="rightPanelView === 'factura'"
                        class="preview-container mt-20"
                    >
                        <template v-if="previewUrl && previewKind === 'image'">
                            <img
                                :src="previewUrl"
                                alt="Factura"
                                class="preview-image"
                            />
                        </template>
                        <template
                            v-else-if="previewUrl && previewKind === 'embed'"
                        >
                            <embed
                                :src="previewUrl"
                                type="application/pdf"
                                class="preview-pdf"
                            />
                        </template>
                        <template v-else>
                            <p class="opacity-6">
                                No hay vista previa disponible para esta
                                factura.
                            </p>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import * as pdfjsLib from "pdfjs-dist/legacy/build/pdf";
pdfjsLib.GlobalWorkerOptions.workerSrc = "/js/pdf.worker.min.js";

export default {
    name: "InvoiceComponent",
    props: ["basicData"],

    data() {
        return {
            source: null,
            datadisToken: null,
            contractsAllowed: [],
            contractPricesByCups: {},
            currentContractPrices: null,

            sipsData: null,
            comparisons: {},
            selectedFiles: [],
            previewUrl: null,
            previewKind: null,

            isRendering: false,
            isDetailChecking: false,

            ocrData: null,
            facturas: [],
            uploadLocked: false,
            selectedCardId: null,

            searchCups: "",
            _cmpTimeout: null,
            _cmpDelayMs: 250,

            rightPanelView: "resultado",
            startChoice: null, // null | upload | stored

            storedFiles: [],
            storedLoaded: false,
            isLoadingStored: false,
            isAddingAllStored: false,
            suppliesByNif: {},
            suppliesByCupsBase: {},
            facturasView: "cards", // "cards" | "list"
        };
    },

    async mounted() {
        await this.fetchDatadisToken();

        const res = await axios.get("/api/orders/ordersAllowed");

        this.contractsAllowed = res.data.map((item) =>
            this.normalizeCupsBase(item.CUPS)
        );


        this.contractPricesByCups = res.data.reduce((acc, item) => {
            const key = this.normalizeCupsBase(item.CUPS);
            acc[key] = {
                precios_energia: item.precios_energia || {},
                precios_potencias: item.precios_potencias || {},
            };
            return acc;
        }, {});
    },

    computed: {
        firstFile() {
            return this.selectedFiles[0] || null;
        },
        averageSipsConsumption() {
            return this.computeAverageSipsPeriods(this.sipsData || {});
        },
        comparisonsReady() {
            return !!(
                this.ocrData &&
                this.sipsData &&
                this.comparisons?.potencias &&
                this.comparisons?.consumos
            );
        },
        selectedCard() {
            return (
                this.facturas.find((f) => f.id === this.selectedCardId) || null
            );
        },
        filteredFacturas() {
            const q = this.normalizeCups(this.searchCups);
            if (!q) return this.facturas;
            return this.facturas.filter((f) => {
                const cups = this.normalizeCups(
                    f?.resumen?.cups || f?.ocrData?.cups || ""
                );
                if (!cups) return false;
                return cups.includes(q);
            });
        },
    },

    watch: {
        ocrData: {
            deep: true,
            handler() {
                if (!this.sipsData) return;
                clearTimeout(this._cmpTimeout);
                this._cmpTimeout = setTimeout(() => {
                    this.comparisons = this.computeComparisons(
                        this.ocrData,
                        this.sipsData,
                        this.currentContractPrices
                    );
                }, this._cmpDelayMs);
            },
        },
        sipsData(newVal) {
            if (newVal && this.ocrData) {
                this.comparisons = this.computeComparisons(
                    this.ocrData,
                    newVal,
                    this.currentContractPrices
                );
            }
        },
    },

    methods: {

        async sendAuditLog(payload) {

            try {
                const res = await axios.post("/api/tools/log/invoice", payload);


                return res.data;
            } catch (e) {
                // 🔥 DEBUG FUERTE: ver el motivo real (status + mensaje backend)
                const status = e?.response?.status;
                const data = e?.response?.data;
                console.error(
                    "❌ LOG NO guardado:",
                    status,
                    data || e?.message || e
                );

                // ❗ IMPORTANTE: si quieres que se pare el flujo cuando falle el log, descomenta:
                // throw e;

                return null;
            }
        },

        findOrderForFactura(factura) {
            const cupsFacturaBase = this.normalizeCups(factura.cups);

            return (
                this.contractsAllowed.find((order) => {
                    const cupsOrderBase = this.normalizeCups(order.CUPS);
                    return cupsOrderBase === cupsFacturaBase;
                }) || null
            );
        },

        getNifFromOrder(order) {
            return (
                order.CIF || // por ejemplo
                order.NIF
            ) // o el que tengas
                .toString()
                .toUpperCase();
        },

        async ensureSuppliesForNif(nif) {
            nif = nif.toUpperCase();

            // 1) Cache: si ya lo tengo, no llamo otra vez
            if (this.suppliesByNif[nif]) {
                return;
            }

            // 2) Aseguro token Datadis
            if (!this.datadisToken) {
                await this.fetchDatadisToken();
            }
            if (!this.datadisToken) {
                console.warn(
                    "❌ No tengo token de Datadis, no puedo pedir supplies"
                );
                return;
            }

            // 3) NIF especial Segenet
            const nifParam = nif.toLowerCase() === "b56037518" ? "" : nif;

            try {
                const res = await axios.get(
                    `https://datadis.es/api-private/api/get-supplies?authorizedNif=${encodeURIComponent(
                        nifParam
                    )}`,
                    {
                        headers: {
                            Authorization: `Bearer ${this.datadisToken}`, // ✅ AHORA SÍ
                        },
                    }
                );

                const supplies = Array.isArray(res.data) ? res.data : [];

                // Guardo por NIF
                this.suppliesByNif[nif] = supplies;

                // Además indexo por CUPS base, para acceso rápido por factura
                supplies.forEach((s) => {
                    const base = this.normalizeCups(s.cups);
                    if (!this.suppliesByCupsBase[base]) {
                        this.suppliesByCupsBase[base] = s;
                    }
                });
            } catch (e) {
                console.error("Error obteniendo supplies de Datadis:", e);

                // Si es 401, probablemente token caducado → podrías intentar refrescar una vez
                if (e.response && e.response.status === 401) {
                    console.warn("Token Datadis caducado o inválido (401).");
                    this.datadisToken = null;
                }
            }
        },
        getSupplyForFactura(factura) {
            const cupsBase = this.normalizeCups(factura.cups);

            // Primero intento en caché directa por CUPS
            if (this.suppliesByCupsBase[cupsBase]) {
                return this.suppliesByCupsBase[cupsBase];
            }

            // Si no está (raro), miro todas las listas de supplies ya cargadas
            for (const nif in this.suppliesByNif) {
                const list = this.suppliesByNif[nif] || [];
                const found = list.find(
                    (s) => this.normalizeCups(s.cups) === cupsBase
                );
                if (found) {
                    this.suppliesByCupsBase[cupsBase] = found; // lo cacheo
                    return found;
                }
            }

            return null;
        },

        async fetchDatadisToken() {
            try {
                const res = await axios.get(
                    "/api/tools/datadis/obtainDatadisTokenInvoice"
                );

                if (res.data?.ok && res.data.token) {
                    this.datadisToken = res.data.token;
                } else {
                    console.warn(
                        "⚠️ No se pudo obtener token Datadis",
                        res.data
                    );
                    this.datadisToken = null;
                }
            } catch (e) {
                console.error("Error obteniendo token Datadis:", e);
                this.datadisToken = null;
            }
        },

        async checkDatadisAvailability(cups, cif, supply, startDate, endDate) {
            try {
                if (!this.datadisToken) {
                    await this.fetchDatadisToken();
                }
                if (!this.datadisToken) {
                    console.warn("⚠️ No hay token Datadis disponible");
                    return null;
                }

                let nifToUse = cif ? String(cif).toUpperCase() : "";

                if (nifToUse === "B56037518") {
                    nifToUse = "";
                }

                const formData = new FormData();
                formData.append("token", this.datadisToken);
                formData.append("cif", nifToUse);
                formData.append("supply", JSON.stringify(supply));
                formData.append("startDate", startDate);
                formData.append("endDate", endDate);

                const res = await axios.post(
                    "/api/tools/getDatadisConsumptionData",
                    formData
                );

                if (res.data?.consumption) {
                    return res.data;
                }

                console.warn(`⚠️ Datadis no devolvió consumo para ${cups}`);
                return null;
            } catch (error) {
                console.error("❌ Error consultando Datadis:", error);
                return null;
            }
        },
        // ========= Selector inicial =========
        chooseUpload() {
            this.startChoice = "upload";
        },
        chooseStored() {
            this.startChoice = "stored";
            this.storedLoaded = false;
            this.storedFiles = [];
        },
        backToChoice() {
            this.startChoice = null;
            this.selectedFiles = [];
            this.storedLoaded = false;
            this.storedFiles = [];
            if (this.$refs.file) this.$refs.file.value = "";
        },

        // ========= Guardadas =========
        extractMongoId(val) {
            if (!val) return null;
            if (typeof val === "string") return val;
            if (val.$oid) return val.$oid;
            if (val.oid) return val.oid;
            if (val._id) return this.extractMongoId(val._id);
            return String(val);
        },

        async loadStoredInvoices() {
            this.storedLoaded = true;
            this.isLoadingStored = true;

            try {
                const enterpriseId = this.extractMongoId(
                    this.basicData?.userSubdomain?._id
                );

                const payload = {
                    enterpriseId,
                    contractsAllowed: this.contractsAllowed,
                };

                const res = await axios.post(
                    "/api/tools/storedInvoices",
                    payload
                );

                this.storedFiles = res.data || [];

                // ✅ adjuntar automáticamente todas
                await this.addAllStoredInvoices();
            } catch (e) {
                console.error(e);
                alert("Aún no existe la ruta para facturas guardadas.");
                this.storedFiles = [];
            } finally {
                this.isLoadingStored = false;
            }
        },

        isStoredInSelection(name) {
            return this.selectedFiles.some((sf) => sf?.name === name);
        },

        async addAllStoredInvoices() {
            if (!this.storedFiles?.length) return;

            this.isAddingAllStored = true;

            const toAdd = this.storedFiles.filter(
                (f) => f?.name && !this.isStoredInSelection(f.name)
            );

            for (const f of toAdd) {
                try {
                    const blob = await this.fetchAsBlob(f.url);
                    const file = new File([blob], f.name, {
                        type: blob.type || this.guessMimeType(f.name),
                    });
                    this.selectedFiles.push(file);
                } catch (e) {
                    console.error(e);
                }
            }

            this.isAddingAllStored = false;
        },

        async fetchAsBlob(url) {
            const r = await fetch(url);
            if (!r.ok) throw new Error("No se pudo descargar " + url);
            return await r.blob();
        },
        guessMimeType(name = "") {
            const n = name.toLowerCase();
            if (n.endsWith(".pdf")) return "application/pdf";
            if (n.endsWith(".png")) return "image/png";
            if (n.endsWith(".jpg") || n.endsWith(".jpeg")) return "image/jpeg";
            return "application/octet-stream";
        },

        // ===== Helpers originales =====
        normalizeTarifa(tarifa) {
            if (!tarifa) return "";
            const t = String(tarifa).toUpperCase();
            if (t.includes("2.0")) return "2.0TD";
            if (t.includes("3.0")) return "3.0TD";
            if (t.includes("6.1")) return "6.1TD";
            return tarifa;
        },
        normalizeCupsBase(cups) {
            const cleaned = String(cups || "")
                .toUpperCase()
                .replace(/\s+/g, "");
            return cleaned.slice(0, 20);
        },
        async _flushPaint() {
            await this.$nextTick();
            await new Promise(requestAnimationFrame);
        },
        normalizeDate(str) {
            if (!str) return "";
            const parts = String(str).split("/");
            if (parts.length !== 3) return str;
            return `${parts[2]}-${parts[1].padStart(
                2,
                "0"
            )}-${parts[0].padStart(2, "0")}`;
        },
        normalizeCups(v) {
            return String(v || "")
                .toUpperCase()
                .replace(/\s+/g, "")
                .trim();
        },
        mergeIntoCard(idx, patch) {
            Object.assign(this.facturas[idx], patch);
        },
        findIndexById(id) {
            return this.facturas.findIndex((f) => f.id === id);
        },
        RName(file) {
            return file?.name || "factura";
        },

        // ===== DIFERENCIAS =====
        computeAverageSipsPeriods(sipsData) {
            const out = {};
            const list = sipsData?.consumos || [];
            if (!list.length) return out;
            const sums = {};
            let count = 0;

            for (const c of list) {
                const periods = c.periods || {};
                for (const [p, v] of Object.entries(periods)) {
                    sums[p] = (sums[p] || 0) + Number(v || 0);
                }
                count++;
            }
            for (const [p, sum] of Object.entries(sums)) out[p] = sum / count;
            return out;
        },

        comparePotenciasPeriods(ocrPot = {}, sipsPot = {}, tolKw = 0.1) {
            const result = { perPeriod: {}, mismatches: 0 };
            const keys = new Set([
                ...Object.keys(ocrPot || {}),
                ...Object.keys(sipsPot || {}),
            ]);

            for (const k of keys) {
                const o = Number(ocrPot?.[k] || 0);
                const s = Number(sipsPot?.[k] || 0);
                const ok = Math.abs(o - s) <= tolKw;
                result.perPeriod[k] = ok;
                if (!ok) result.mismatches++;
            }
            return result;
        },

        compareConsumosPeriods(
            ocrEnergia = {},
            sipsAvg = {},
            tolPct = 0.05,
            minAbs = 1
        ) {
            const result = { perPeriod: {}, mismatches: 0 };
            const keys = new Set([
                ...Object.keys(ocrEnergia || {}),
                ...Object.keys(sipsAvg || {}),
            ]);

            for (const k of keys) {
                const o = Number(ocrEnergia?.[k] || 0);
                const s = Number(sipsAvg?.[k] || 0);
                const thresh = Math.max(minAbs, Math.abs(s) * tolPct);
                const ok = Math.abs(o - s) <= thresh;
                result.perPeriod[k] = ok;
                if (!ok) result.mismatches++;
            }
            return result;
        },

        comparePricePeriods(
            ocrPrices = {},
            contractPrices = {},
            tolAbs = 0.000001,
            tolRel = 0.001
        ) {
            const result = { perPeriod: {}, mismatches: 0 };
            const keys = new Set([
                ...Object.keys(ocrPrices || {}),
                ...Object.keys(contractPrices || {}),
            ]);

            for (const k of keys) {
                const rawO = ocrPrices?.[k];
                const rawC = contractPrices?.[k];

                if (
                    (rawO === null || rawO === undefined) &&
                    (rawC === null || rawC === undefined)
                ) {
                    result.perPeriod[k] = true;
                    continue;
                }

                const o = Number(rawO || 0);
                const c = Number(rawC || 0);

                const thresh = Math.max(tolAbs, Math.abs(c) * tolRel);
                const ok = Math.abs(o - c) <= thresh;

                result.perPeriod[k] = ok;
                if (!ok) result.mismatches++;
            }

            return result;
        },

        computeComparisons(o, s, contractPrices = null) {
            const pot = this.comparePotenciasPeriods(
                o?.potencias_contratadas,
                s?.potencias_contratadas,
                0.1
            );
            const avgSips = this.computeAverageSipsPeriods(s || {});
            const cons = this.compareConsumosPeriods(
                o?.energia_consumida,
                avgSips,
                0.05,
                1
            );

            let pricePot = { perPeriod: {}, mismatches: 0 };
            let priceEner = { perPeriod: {}, mismatches: 0 };

            if (contractPrices) {
                pricePot = this.comparePricePeriods(
                    o?.precios_potencias,
                    contractPrices?.precios_potencias
                );
                priceEner = this.comparePricePeriods(
                    o?.precios_energia,
                    contractPrices?.precios_energia
                );
            }

            return {
                tarifa: o?.tarifa === s?.tarifa,
                comercializadora: o?.comercializadora === s?.comercializadora,

                potencias: pot.perPeriod,
                potencias_mismatches: pot.mismatches,

                consumos: cons.perPeriod,
                consumos_mismatches: cons.mismatches,

                precios_potencias: pricePot.perPeriod,
                precios_potencias_mismatches: pricePot.mismatches,

                precios_energia: priceEner.perPeriod,
                precios_energia_mismatches: priceEner.mismatches,
            };
        },

        countDifferences(o, s, contractPrices = null) {
            const c = this.computeComparisons(o, s, contractPrices);
            const priceMismatches =
                (c.precios_potencias_mismatches || 0) +
                (c.precios_energia_mismatches || 0);

            return {
                potencias: c.potencias_mismatches,
                consumos: c.consumos_mismatches,
                precios: priceMismatches,
                total:
                    c.potencias_mismatches +
                    c.consumos_mismatches +
                    priceMismatches,
                matrix: c,
            };
        },

        async handleGuardarCambios() {
    if (!this.ocrData) return alert("No hay datos de factura cargados.");
    const { cups, periodo_facturacion } = this.ocrData;
    if (!cups || !periodo_facturacion?.fecha_inicio || !periodo_facturacion?.fecha_fin) {
        return alert("Faltan fechas o el CUPS para realizar la comprobación.");
    }

    this.isDetailChecking = true;
    try {
        const fechaInicio = periodo_facturacion.fecha_inicio;
        const fechaFin = periodo_facturacion.fecha_fin;

        // Datadis
        try {
            const order = this.findOrderForFactura({ cups });
            const nifContrato = order
                ? this.getNifFromOrder(order)
                : this.ocrData.cif_nif || "";

            if (nifContrato) {
                await this.ensureSuppliesForNif(nifContrato);
                const supply = this.getSupplyForFactura({ cups });

                if (supply) {
                    await this.checkDatadisAvailability(
                        supply.cups,
                        nifContrato,
                        supply,
                        fechaInicio,
                        fechaFin
                    );
                }
            }
        } catch (e) {
            console.warn("Datadis no disponible, sigo con SIPS:", e);
        }

        // SIPS
        const res = await axios.post("/api/tools/getAPIDataForInvoice", {
            cups,
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
        });

        if (!res.data.exists) {
            alert("❌ No hay datos en SIPS para esas fechas.");
            return;
        }

        const newSips = res.data.sips;
        this.sipsData = newSips;
        this.comparisons = this.computeComparisons(
            this.ocrData,
            newSips,
            this.currentContractPrices
        );

        // Actualizar tarjeta
        const idx = this.findIndexById(this.selectedCardId);
        if (idx !== -1) {
            const diffs = this.countDifferences(
                this.ocrData,
                newSips,
                this.currentContractPrices
            );
            this.mergeIntoCard(idx, {
                sipsData: newSips,
                status: diffs.total === 0 ? "ok" : "diferencias",
                _diffs: diffs,
                resumen: {
                    ...this.facturas[idx].resumen,
                    resultado:
                        diffs.total === 0
                            ? "Sin diferencias"
                            : `Potencias: ${diffs.potencias} | Consumos: ${diffs.consumos} | Precios: ${diffs.precios}`,
                },
            });
        }
    } catch (error) {
        console.error(error);
        alert("Error al comprobar los datos del SIPS.");
    } finally {
        this.isDetailChecking = false;
    }
},

        async recheckCurrent() {
            if (!this.ocrData) return;
            await this.handleGuardarCambios();
        },

        getFilesSelected(e) {
            const newFiles = Array.from(e.target.files || []);
            this.selectedFiles = [...this.selectedFiles, ...newFiles];
        },
        removeFile(index) {
            this.selectedFiles.splice(index, 1);
        },

        async sendToOCR() {
            if (!this.selectedFiles.length)
                return alert("Selecciona al menos una factura.");
            this.uploadLocked = true;
            this.isRendering = true;

            this.facturas = this.selectedFiles.map((file) => ({
                id: crypto.randomUUID(),
                file,
                name: file.name,
                status: "pendiente",
                ocrData: null,
                sipsData: null,
                contractPrices: null,
                ocrReady: false,
                resumen: {
                    cups: "",
                    periodo: "",
                    comercializadora: "",
                    resultado: "En progreso…",
                },
                error: null,
            }));

            await this._flushPaint();

            for (let i = 0; i < this.facturas.length; i++) {
                await this.processCardAtIndex(i);
                await this._flushPaint();
            }

            this.isRendering = false;
        },

       async processCardAtIndex(idx) {
    this.mergeIntoCard(idx, { status: "procesando" });
    await this._flushPaint();

    const card = this.facturas[idx];

    try {
        // 1️⃣ OCR
        const fd = new FormData();
        fd.append("files", card.file);
        fd.append("userSubdomain", this.basicData.userSubdomain._id);

        const ocrRes = await axios.post("/api/tools/getOCRData", fd, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        const data = { ...ocrRes.data };
        data.tarifa = this.normalizeTarifa(data.tarifa);

        if (data?.periodo_facturacion) {
            data.periodo_facturacion = {
                ...data.periodo_facturacion,
                fecha_inicio: this.normalizeDate(
                    data.periodo_facturacion.fecha_inicio
                ),
                fecha_fin: this.normalizeDate(
                    data.periodo_facturacion.fecha_fin
                ),
            };
        }

        this.mergeIntoCard(idx, {
            ocrData: data,
            ocrReady: true,
            resumen: {
                cups: data?.cups || "",
                periodo: data?.periodo_facturacion
                    ? `${data.periodo_facturacion.fecha_inicio || "?"} — ${
                          data.periodo_facturacion.fecha_fin || "?"
                      }`
                    : "",
                comercializadora: data?.comercializadora || "",
                resultado: "Comprobando datos…",
            },
        });

        await this._flushPaint();

        // 2️⃣ Validar CUPS permitido
        const normalizedCups = this.normalizeCupsBase(data.cups);
        console.log('--- DEBUG CUPS PERMITIDOS ---');
        console.log('CUPS extraído de la factura (raw):', data.cups);
        console.log('CUPS normalizado (normalizeCupsBase):', normalizedCups);
        console.log('Lista contractsAllowed:', this.contractsAllowed);
        console.log('¿Está incluido?', this.contractsAllowed.includes(normalizedCups));
        console.log('Comparación carácter a carácter:');
        this.contractsAllowed.forEach((c, i) => {
            console.log(`  [${i}] "${c}" (len:${c.length}) === "${normalizedCups}" (len:${normalizedCups.length}) →`, c === normalizedCups);
        });
        if (!this.contractsAllowed.includes(normalizedCups)) {
            await this.sendAuditLog({
                status: "error",
                cups: data.cups,
                error: "Contrato no permitido",
            });

            this.mergeIntoCard(idx, {
                status: "error",
                error: "Contrato no permitido",
                resumen: {
                    ...this.facturas[idx].resumen,
                    resultado: "Contrato no permitido",
                },
            });
            return;
        }

        // 3️⃣ Precios de contrato
        const contractPrices =
            this.contractPricesByCups[normalizedCups] || null;
        this.mergeIntoCard(idx, { contractPrices });

        this.mergeIntoCard(idx, { status: "comprobando" });
        await this._flushPaint();

        const { cups, periodo_facturacion } = data;
        if (
            !cups ||
            !periodo_facturacion?.fecha_inicio ||
            !periodo_facturacion?.fecha_fin
        ) {
            throw new Error("Datos incompletos");
        }

        const fechaInicio = periodo_facturacion.fecha_inicio;
        const fechaFin = periodo_facturacion.fecha_fin;

        // 🆕 DATADIS — intento previo, sin romper flujo
        let usadoDatadis = false;
        try {
            const order = this.findOrderForFactura({ cups });
            const nifContrato = order
                ? this.getNifFromOrder(order)
                : data.cif_nif || "";

            if (nifContrato) {
                await this.ensureSuppliesForNif(nifContrato);
                const supply = this.getSupplyForFactura({ cups });

                if (supply) {
                    const datadisData =
                        await this.checkDatadisAvailability(
                            supply.cups,
                            nifContrato,
                            supply,
                            fechaInicio,
                            fechaFin,
                        );

                    if (datadisData?.consumption) {
                        usadoDatadis = true;
                    }
                }
            }
        } catch (e) {
            console.warn("Datadis no disponible, sigo con SIPS:", e);
        }

        // 4️⃣ SIPS (agrega Datadis si existe)
        const sipsRes = await axios.post(
            "/api/tools/getAPIDataForInvoice",
            {
                cups,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin,
            }
        );

        if (!sipsRes.data?.exists) {
            throw new Error("No hay datos disponibles");
        }

        const sipsData = sipsRes.data.sips;

        const source = sipsRes.data.source || 'sips';


        // 5️⃣ Comparaciones (SOLO UI)
        const diffs = this.countDifferences(
            data,
            sipsData,
            contractPrices
        );

        // 6️⃣ LOG FINAL (FACTURA + COMPROBACIÓN)
        await this.sendAuditLog({
            status: "ok",
            cups,
            period: {
                from: fechaInicio,
                to: fechaFin,
            },

            checked_data: {
                invoice: {
                    potencias_contratadas:
                        data.potencias_contratadas || {},
                    energia_consumida:
                        data.energia_consumida || {},
                    precios_potencias:
                        data.precios_potencias || {},
                    precios_energia:
                        data.precios_energia || {},
                },

                check: {
                    potencias_contratadas:
                        sipsData?.potencias_contratadas || {},

                    energia_consumida:
                        this.computeAverageSipsPeriods(sipsData) || {},

                    precios_potencias:
                        contractPrices?.precios_potencias || {},

                    precios_energia:
                        contractPrices?.precios_energia || {},
                },
            },

            summary: {
                potencias: diffs.potencias,
                consumos: diffs.consumos,
                precios: diffs.precios,
                total: diffs.total,
            },
        });

        // 7️⃣ UI FINAL
        this.mergeIntoCard(idx, {
            sipsData,
            status: diffs.total === 0 ? "ok" : "diferencias",   // ← aquí
            _diffs: diffs,
            resumen: {
                ...this.facturas[idx].resumen,
                resultado:
                    diffs.total === 0
                        ? `Sin diferencias`
                        : `Potencias: ${diffs.potencias} | Consumos: ${diffs.consumos} | Precios: ${diffs.precios}`,
            },
        });
    } catch (e) {
        console.error(e);

        const safeError = "error desconocido";

        await this.sendAuditLog({
            status: "error",
            cups: card?.ocrData?.cups || null,
            error: safeError,
        });

        this.mergeIntoCard(idx, {
            status: "error",
            error: safeError,
            resumen: {
                ...this.facturas[idx].resumen,
                resultado: "Error",
            },
        });
    }
        }
        ,

        verDetalleFactura(f) {
            this.selectedCardId = f.id;
            this.ocrData = f.ocrData || null;
            this.sipsData = f.sipsData || null;
            this.source = f.source || 'sips';
            this.currentContractPrices = f.contractPrices || null;

            this.revokePreview();
            if (f?.file?.type?.startsWith("image/")) {
                this.previewUrl = URL.createObjectURL(f.file);
                this.previewKind = "image";
            } else if (f?.file?.type === "application/pdf") {
                this.previewUrl = URL.createObjectURL(f.file);
                this.previewKind = "embed";
            } else {
                this.previewUrl = null;
                this.previewKind = null;
            }

            if (this.ocrData && this.sipsData) {
                this.comparisons = this.computeComparisons(
                    this.ocrData,
                    this.sipsData,
                    this.currentContractPrices
                );
            } else {
                this.comparisons = {};
            }

            this.rightPanelView = "resultado";
            window.scrollTo({ top: 0, behavior: "smooth" });
        },

        removeFacturaCard(index) {
            this.facturas.splice(index, 1);
            if (this.facturas.length === 0) {
                this.uploadLocked = false;
                this.selectedFiles = [];
                this.startChoice = null;
                this.storedLoaded = false;
                this.storedFiles = [];
                if (this.$refs.file) this.$refs.file.value = "";
            }
            if (
                this.selectedCard &&
                !this.facturas.find((f) => f.id === this.selectedCardId)
            ) {
                this.selectedCardId = null;
            }
        },

        async retryFactura(idx) {
            this.mergeIntoCard(idx, {
                status: "pendiente",
                error: null,
                ocrReady: false,
                source: null,
                ocrData: null,
                sipsData: null,
                contractPrices: null,
                resumen: {
                    cups: "",
                    periodo: "",
                    comercializadora: "",
                    resultado: "En progreso…",
                },
            });
            await this._flushPaint();
            await this.processCardAtIndex(idx);
        },

        clearFiles() {
            this.selectedFiles = [];
            this.ocrData = null;
            this.sipsData = null;
            this.currentContractPrices = null;
            this.comparisons = {};
            this.facturas = [];
            this.uploadLocked = false;
            this.selectedCardId = null;
            this.rightPanelView = "resultado";
            this.startChoice = null;

            this.storedLoaded = false;
            this.storedFiles = [];

            this.revokePreview();
            if (this.$refs.file) this.$refs.file.value = "";
        },

        revokePreview() {
            if (
                typeof this.previewUrl === "string" &&
                this.previewUrl.startsWith("blob:")
            ) {
                URL.revokeObjectURL(this.previewUrl);
            }
            this.previewUrl = null;
        },

        resetToStart() {
            this.ocrData = null;
            this.sipsData = null;
            this.currentContractPrices = null;
            this.comparisons = {};
            this.selectedCardId = null;
            this.rightPanelView = "resultado";
            this.startChoice = null;

            this.storedLoaded = false;
            this.storedFiles = [];

            this.revokePreview();
            window.scrollTo({ top: 0, behavior: "smooth" });
        },

        unlockUpload() {
            this.uploadLocked = false;
            this.selectedFiles = [];
            this.startChoice = null;
            if (this.$refs.file) this.$refs.file.value = "";
        },
    },
};
</script>

<style scoped>
/* ===== Layout general ===== */
.invoice-container {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  gap: 30px;
  flex-wrap: wrap;
  transition: all 0.4s ease;
  margin-top: 20px;
  width: 100%;
  max-width: 1600px;
  margin-left: auto;
  margin-right: auto;
}
.invoice-container.expanded {
  flex-wrap: nowrap;
}

.invoice-panel {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
  flex: 1;
  min-width: 420px;
  max-width: 720px;
  overflow: hidden;
  transition: all 0.4s ease;
}

/* Panel derecho más grande cuando NO hay ocrData */
.upload-panel.wide {
  flex: 2;
  max-width: 100%;
}

.scroll-content {
  padding: 30px 40px;
  overflow-y: auto;
  height: 100%;
  scrollbar-width: thin;
}
.scroll-content::-webkit-scrollbar {
  width: 8px;
}
.scroll-content::-webkit-scrollbar-thumb {
  background-color: rgba(0, 0, 0, 0.2);
  border-radius: 6px;
}

/* ===== Títulos uppercase color fijo ===== */
.main-title {
  font-size: 26px;
  font-weight: 800;
  color: #012c68;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin: 0;
}
.section-title {
  font-size: 22px;
  font-weight: 800;
  color: #012c68;
  text-transform: uppercase;
  letter-spacing: 0.35px;
  margin: 0;
  text-align: center;
  width: 100%;
}

/* Header opción con flecha derecha */
.option-header {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
}
.back-arrow-btn {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #dc2626;
  font-size: 20px;
  cursor: pointer;
  padding: 6px 8px;
}
.back-arrow-btn:hover {
  opacity: 0.8;
}

/* Separación extra entre título y contenido */
.option-content {
  margin-top: 18px;
  padding-top: 6px;
}

/* ===== Tarjetas selector sin colorines ===== */
.start-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
  max-width: 820px;
  margin: 0 auto;
}
@media (max-width: 800px) {
  .start-grid {
    grid-template-columns: 1fr;
  }
}

.start-card {
  border: 2px solid #e5e7eb;
  background: #ffffff;
  border-radius: 18px;
  padding: 30px 26px;
  text-align: center;
  cursor: pointer;
  transition: 0.2s ease;
  min-height: 200px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 10px;
}
.start-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
}
.start-icon {
  font-size: 36px;
  margin-bottom: 2px;
}
.start-title {
  font-size: 20px;
  font-weight: 800;
  color: #012c68;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
.start-sub {
  font-size: 15px;
  color: #6b7280;
  margin-top: 6px;
}

/* Aviso centrado */
.stored-warning {
  background: #f9fafc;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px 12px;
  text-align: center;
}

/* ===== Tarjetas facturas (3 columnas + más grandes) ===== */
.facturas-container.big-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}
@media (max-width: 1400px) {
  .facturas-container.big-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 900px) {
  .facturas-container.big-grid {
    grid-template-columns: 1fr;
  }
}

.factura-card {
  border-radius: 16px;
  padding: 22px 24px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
  background: #ffffff;
  transition: all 0.25s ease;
  border-left: 5px solid #ccc;
  cursor: pointer;
}
.factura-card:hover {
  transform: translateY(-2px);
  background: #f9fafc;
}
.factura-card.pendiente {
  border-left-color: #9ca3af;
}
.factura-card.procesando {
  border-left-color: #3b82f6;
}
.factura-card.comprobando {
  border-left-color: #f59e0b;
}
.factura-card.ok {
  border-left-color: #16a34a;
}
.factura-card.error {
  border-left-color: #dc2626;
}

.big-card {
  min-height: 185px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 8px;
}

.big-title {
  font-size: 18px;
  font-weight: 600;
}

/* Pill de estado */
.status-pill {
  font-size: 12px;
  padding: 3px 10px;
  border-radius: 999px;
  background: #eef2f7;
  color: #333;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  font-weight: 600;
    white-space: nowrap; /* ⬅️ CLAVE */

}
.status-pill.procesando {
  background: #dbeafe;
}
.status-pill.comprobando {
  background: #fde68a;
}
.status-pill.ok {
  background: #bbf7d0;
}
.status-pill.error {
  background: #fecaca;
}

.mini-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 4px;
  margin-bottom: 6px;
  font-size: 13px;
  color: #374151;
}
.big-mini-grid {
  font-size: 15px;
  margin-top: 8px;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
    "Liberation Mono", "Courier New", monospace;
}
.ok-text {
  color: #16a34a;
  font-weight: 600;
}
.error-text {
  color: #dc2626;
  font-weight: 600;
}
.big-ok,
.big-error {
  font-size: 15px;
}


@keyframes sk {
  0% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0 50%;
  }
}

/* ===== Resultado a la derecha ===== */
.comparison-container {
  background: #f9fafc;
  border-radius: 12px;
  padding: 25px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Selector Resultado / Factura */
.view-toggle {
  display: flex;
  justify-content: center;
  gap: 18px;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 6px;
}

.view-toggle-button {
  background: transparent;
  border: none;
  padding: 6px 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  position: relative;
  color: #6b7280;
}
.view-toggle-button::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  bottom: -6px;
  height: 2px;
  border-radius: 999px;
  background: transparent;
}
.view-toggle-button.active {
  color: #111827;
}
.view-toggle-button.active::after {
  background: #3b82f6;
}

/* Inputs coloreados */
.invoice-panel .simple-input.ok,
.invoice-panel .simple-input.ok:focus {
  border: 2px solid #16a34a !important;
  background: #e8f8ef !important;
}
.invoice-panel .simple-input.fail,
.invoice-panel .simple-input.fail:focus {
  border: 2px solid #dc2626 !important;
  background: #fee2e2 !important;
}

/* Preview */
.preview-container {
  text-align: center;
}
.preview-image {
  max-width: 100%;
  border-radius: 10px;
}
.preview-pdf {
  width: 100%;
  height: 70vh;
  border-radius: 10px;
  border: none;
}

/* Lista de archivos */
.selected-files-list {
  background: #f9fafc;
  border-radius: 10px;
  padding: 15px 20px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  max-width: 640px;
  margin: 0 auto;
}
.files-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.file-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 10px;
  margin-bottom: 6px;
  border-bottom: 1px solid #e5e7eb;
}
.file-name {
  flex: 1;
  word-break: break-all;
  color: #333;
  font-size: 15px;
}
.remove-file {
  background: #f3f4f6;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.remove-file:hover {
  background: #fde8e8;
  color: #c81e1e;
  transform: scale(1.1);
}

/* Botones tarjeta / acciones */
.card-actions {
  display: flex;
  gap: 14px;
  margin-top: 14px;
}
.big-actions button {
  padding: 10px 14px;
  font-size: 15px;
}
.btn {
  border: none;
  border-radius: 6px;
  padding: 6px 10px;
  cursor: pointer;
  font-weight: 600;
  background: #e5e7eb;
  color: #111827;
}
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-gray {
  background: #e5e7eb;
}
.btn-red {
  background: #ef4444;
  color: #fff;
}

/* Cabecera FACTURAS + buscador */
.facturas-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  gap: 16px;
}
.facturas-header-left h2 {
  font-size: 26px;
  font-weight: 700;
  margin: 0;
  color: #111827;
}
.facturas-subtitle {
  margin: 4px 0 0;
  font-size: 13px;
  color: #6b7280;
}

.search-wrapper {
  position: relative;
  width: 380px;
  max-width: 100%;
}
.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 16px;
  color: #9ca3af;
}
.cups-search-input {
  width: 100%;
  height: 42px;
  padding: 8px 14px 8px 40px;
  font-size: 15px;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  outline: none;
  background: #f9fafb;
  transition: all 0.2s ease;
}
.cups-search-input:focus {
  background: #ffffff;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
@media (max-width: 768px) {
  .facturas-header {
    flex-direction: column;
    align-items: stretch;
  }
  .search-wrapper {
    width: 100%;
  }
}

/* ===== Cabecera FACTURAS (derecha) ===== */
.facturas-header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

/* ===== Selector tarjetas / lista (iconos) ===== */
.view-switch {
  display: flex;
  align-items: center;
  gap: 10px;
}
.view-icon {
  font-size: 18px;
  color: #9ca3af;
  cursor: pointer;
  transition: color 0.15s ease, transform 0.15s ease;
}
.view-icon:hover {
  color: #374151;
  transform: scale(1.1);
}
.view-icon.active {
  color: #2563eb;
}

/* ===== Vista lista (líneas) ===== */
.facturas-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.factura-row {
  display: grid;
  grid-template-columns:
    2.2fr /* nombre */
    2fr /* cups */
    2fr /* comercializadora */
    2fr /* periodo */
    1.2fr /* estado */
    140px; /* acciones */
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
  border-left: 5px solid #ccc;
  cursor: pointer;
  transition: background 0.2s ease;
}

/* Cabecera */
.factura-row.header {
  background: #eef5ff;
  box-shadow: none;
  border-left: none;
  cursor: default;
  font-weight: 700;
  color: #012c68;
  text-transform: none;
}

/* Nombre factura estilo link */
.factura-name {
  font-weight: 600;
  color: #2563eb;
}
.factura-name:hover {
  text-decoration: underline;
}
.factura-name.error {
  color: #dc2626;
  font-weight: 600;
}

/* Ellipsis para textos largos */
.ellipsis {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Contenido fila */
.row-main {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
}

.row-status {
  text-align: center;
}

/* Acciones fila */
.row-actions {
  display: flex;
  justify-content: flex-end;
  gap: 6px;
}

/* Estados */
.factura-row.pendiente {
  border-left-color: #9ca3af;
}
.factura-row.procesando {
  border-left-color: #3b82f6;
}
.factura-row.comprobando {
  border-left-color: #f59e0b;
}
.factura-row.ok {
  border-left-color: #16a34a;
}
.factura-row.error {
  border-left-color: #dc2626;
}

.factura-row:hover {
  background: #f9fafc;
}

/* ===== Icon buttons (sin recuadro) ===== */
.icon-btn {
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: #4b5563;
  font-size: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: color 0.15s ease, transform 0.15s ease;
}
.icon-btn:hover {
  color: #2563eb;
  transform: scale(1.1);
}
.icon-btn.danger:hover {
  color: #dc2626;
}
.icon-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  transform: none;
}
.icon-btn i {
  pointer-events: none;
}

/* Iconos de acciones en tarjetas/lista */
.action-icon {
  font-size: 18px;
  color: #6b7280;
  cursor: pointer;
  transition: color 0.15s ease, transform 0.15s ease;
}
.action-icon:hover {
  color: #2563eb;
  transform: scale(1.15);
}
.action-icon.danger:hover {
  color: #dc2626;
}
.action-icon.disabled {
  opacity: 0.4;
  cursor: not-allowed;
  transform: none;
}

/* Mantener fondo limpio */
.factura-row.error {
  background: #ffffff;
}


/* El resto del texto en gris neutro */
.factura-row.error span:not(.factura-name):not(.status-pill) {
  color: #6b7280;
}

.factura-row .row-actions {
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.15s ease;
}

.factura-row:hover .row-actions {
  opacity: 1;
  pointer-events: auto;
}

/* Responsive lista */
@media (max-width: 1100px) {
  .factura-row {
    grid-template-columns: 1fr;
    gap: 10px;
  }
  .factura-row.header {
    display: none;
  }
  .row-actions {
    justify-content: flex-start;
  }
}
@media (max-width: 900px) {
  .factura-row {
    grid-template-columns: 1fr;
    gap: 10px;
  }
  .row-actions {
    justify-content: flex-start;
  }
}
.factura-card.diferencias { border-left-color: #f59e0b; }
.factura-row.diferencias { border-left-color: #f59e0b; }
.status-pill.diferencias {
  background: #fde68a;
  color: #92400e;
}
</style>

