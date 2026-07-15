<template>
    <section class="daily-signings-module">
        <div class="signing-action-card">
            <div
                v-if="getTodayWorkCalendarMessage()"
                class="work-calendar-alert"
                :class="{
                    'is-pending': getMainTodayWorkCalendarEvent()?.status === 'pending',
                    'is-approved': (getMainTodayWorkCalendarEvent()?.status || 'approved') === 'approved',
                }"
            >
                <div class="work-calendar-alert-icon">
                    <i class="fa-regular fa-calendar"></i>
                </div>

                <div class="work-calendar-alert-content">
                    <strong>{{ getTodayWorkCalendarMessage() }}</strong>

                    <span v-if="(getMainTodayWorkCalendarEvent()?.status || 'approved') === 'approved'">
                        Hoy está marcado como día no laborable o ausencia. Si necesita fichar igualmente, puede hacerlo.
                    </span>

                    <span v-else>
                        Este evento todavía no está aprobado. El fichaje sigue disponible.
                    </span>
                </div>
            </div>
            <div class="signing-action-content">
                <div>
                    <p class="module-kicker">Fichajes diarios</p>
                    <h2>Registro horario</h2>
                    <p class="module-description">
                        Fiche su entrada y salida diaria y consulte sus registros.
                    </p>
                </div>

                <button
                    class="signing-main-button"
                    :class="{ exit: !isEntrada }"
                    :disabled="isLoading || isSigning"
                    @click="toggleFichaje"
                >
                    <span class="signing-button-icon">
                        <i
                            v-if="isLoading || isSigning"
                            class="fa-solid fa-spinner fa-spin"
                        ></i>
                        <i
                            v-else-if="isEntrada"
                            class="fa-solid fa-right-to-bracket"
                        ></i>
                        <i
                            v-else
                            class="fa-solid fa-right-from-bracket"
                        ></i>
                    </span>

                    <span>
                        <template v-if="isLoading">
                            Cargando fichajes...
                        </template>

                        <template v-else-if="isSigning">
                            {{ isEntrada ? "Fichando entrada..." : "Fichando salida..." }}
                        </template>

                        <template v-else>
                            {{ isEntrada ? "Fichar entrada" : "Fichar salida" }}
                        </template>
                    </span>
                </button>

                <button
                    type="button"
                    class="monthly-summary-btn"
                    @click="openMonthlySummaryModal"
                >
                    <i class="fa-solid fa-chart-simple"></i>
                    <span>Ver mi resumen del mes</span>
                </button>

                <button
                    type="button"
                    class="forgotten-signing-btn"
                    @click="openForgottenRequestModal"
                >
                    <i class="fa-regular fa-clock"></i>
                    <span>Solicitar fichaje olvidado</span>
                </button>

                <button
                    type="button"
                    class="vacation-request-btn"
                    @click="openVacationRequestModal"
                >
                    <i class="fa-regular fa-calendar-check"></i>
                    <span>Solicitar vacaciones</span>
                </button>

                <button
                    type="button"
                    class="my-requests-btn"
                    @click="openMyRequestsModal"
                >
                    <i class="fa-solid fa-list-check"></i>
                    <span>Mis solicitudes</span>
                    <strong v-if="myPendingRequestsCount()">{{ myPendingRequestsCount() }}</strong>
                </button>

            </div>
        </div>

        <div class="zoco-card filters-card">
            <div class="filters-header">
                <div>
                    <p class="section-kicker">Búsqueda</p>
                    <h3>Filtrar registros</h3>
                    <p>Seleccione un periodo para consultar sus entradas y salidas.</p>
                </div>
            </div>

            <div class="filters-grid">
                <div class="form-field">
                    <label>Desde</label>
                    <div class="control-with-icon">
                        <input
                            type="date"
                            v-model="selectedStartDate"
                            class="zoco-control"
                        />
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>

                <div class="form-field">
                    <label>Hasta</label>
                    <div class="control-with-icon">
                        <input
                            type="date"
                            v-model="selectedEndDate"
                            class="zoco-control"
                            :min="selectedStartDate"
                        />
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>

                <div class="filters-actions">
                    <button
                        class="zoco-btn zoco-btn-primary"
                        @click="filtrarPorFechas"
                        :disabled="!selectedStartDate || !selectedEndDate || isLoading"
                    >
                        <i class="fa-solid fa-filter"></i>
                        <span>Filtrar</span>
                    </button>

                    <button
                        class="zoco-btn zoco-btn-secondary"
                        @click="limpiarFiltro"
                        :disabled="isLoading"
                    >
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Limpiar</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="zoco-card table-card">
            <div class="table-header">
                <div>
                    <h3>Registro de fichajes</h3>
                    <p>
                        {{ fichajes.length }}
                        {{ fichajes.length === 1 ? "día registrado" : "días registrados" }}
                    </p>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="zoco-table" v-if="!isLoading">
                    <thead>
                        <tr>
                            <th>Día</th>

                            <template v-for="n in maxPairsToShow" :key="'par-' + n">
                                <th>Hora de entrada</th>
                                <th>Hora de salida</th>
                            </template>
                        </tr>
                    </thead>

                    <tbody>
                        <template v-for="(registro, index) in fichajes" :key="index">
                            <tr
                                v-for="(grupo, subIndex) in splitIntoChunks(
                                    registro.pares,
                                    maxPairsToShow
                                )"
                                :key="registro.dia + '-' + subIndex"
                            >
                                <td
                                    v-if="subIndex === 0"
                                    :rowspan="Math.ceil(registro.pares.length / maxPairsToShow)"
                                >
                                    <span class="date-pill">
                                        {{ registro.dia }}
                                    </span>
                                </td>

                                <template v-for="(par, i) in grupo" :key="'par-' + i">
                                    <td>
                                        <div class="time-cell">
                                            <span>{{ par.entrada || "--:--" }}</span>

                                            <button
                                                v-if="par.ubicacionEntrada"
                                                type="button"
                                                class="icon-btn"
                                                @click="abrirMapa(par.ubicacionEntrada)"
                                                title="Ver ubicación de entrada"
                                            >
                                                <i class="fa-solid fa-location-dot"></i>
                                            </button>

                                            <label
                                                :for="'file-' + index + '-' + i"
                                                class="icon-btn upload"
                                                title="Subir orden de trabajo"
                                            >
                                                <i class="fa-solid fa-upload"></i>
                                            </label>

                                            <input
                                                :id="'file-' + index + '-' + i"
                                                type="file"
                                                class="input-file"
                                                @change="onFileChange($event, registro, par)"
                                            />

                                            <button
                                                type="button"
                                                class="icon-btn view"
                                                @click="abrirModal(par._id)"
                                                title="Ver detalle"
                                            >
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="time-cell exit-cell">
                                            <div class="exit-info">
                                                <span>
                                                    {{ par.auto_closed ? "Sin salida registrada" : (par.salida || "--:--") }}
                                                </span>

                                                <span
                                                    v-if="par.auto_closed"
                                                    class="auto-closed-badge"
                                                    title="Salida cerrada automáticamente porque no se fichó salida"
                                                >
                                                    Cierre automático
                                                </span>
                                            </div>

                                            <button
                                                v-if="par.ubicacionSalida && !par.auto_closed"
                                                type="button"
                                                class="icon-btn"
                                                @click="abrirMapa(par.ubicacionSalida)"
                                                title="Ver ubicación de salida"
                                            >
                                                <i class="fa-solid fa-location-dot"></i>
                                            </button>
                                        </div>
                                    </td>
                                </template>

                                <template
                                    v-for="n in maxPairsToShow - grupo.length"
                                    :key="'vacio-' + n"
                                >
                                    <td>--:--</td>
                                    <td>--:--</td>
                                </template>
                            </tr>
                        </template>

                        <tr v-if="fichajes.length === 0">
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fa-regular fa-clock"></i>
                                    </div>
                                    <h4>No hay fichajes registrados</h4>
                                    <p>
                                        Todavía no existen registros para el periodo seleccionado.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-else class="loading-state">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Cargando fichajes...</span>
                </div>
            </div>

            <div v-if="!isLoading" class="pagination-bar">
                <button
                    type="button"
                    class="pagination-btn"
                    @click="previousPage(currentPage)"
                    :disabled="currentPage === 1"
                >
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <span>Página {{ currentPage }} de {{ totalPages }}</span>

                <button
                    type="button"
                    class="pagination-btn"
                    @click="nextPage(currentPage)"
                    :disabled="currentPage === totalPages"
                >
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <div v-if="viewDetailsModalVisible" class="zoco-modal-backdrop">
            <div class="zoco-modal">
                <button
                    type="button"
                    class="modal-close"
                    @click="viewDetailsModalVisible = false"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="modal-header">
                    <span class="modal-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </span>

                    <div>
                        <h2>Detalles del fichaje</h2>
                        <p>Información completa del registro seleccionado.</p>
                    </div>
                </div>

                <div v-if="fichajeActual" class="details-grid">
                    <div class="detail-item">
                        <label>ID</label>
                        <span>{{ fichajeActual._id }}</span>
                    </div>

                    <div class="detail-item">
                        <label>Fecha</label>
                        <span>{{ fichajeActual.date }}</span>
                    </div>

                    <div class="detail-item">
                        <label>Hora de entrada</label>
                        <span>{{ fichajeActual.entry || "--:--" }}</span>
                    </div>

                    <div class="detail-item">
                        <label>Hora de salida</label>

                        <div class="detail-exit-info">
                            <span>
                                {{ fichajeActual.auto_closed ? "Sin salida registrada" : (fichajeActual.exit || "No registrada") }}
                            </span>

                            <span
                                v-if="fichajeActual.auto_closed"
                                class="auto-closed-badge"
                            >
                                Cierre automático
                            </span>
                        </div>
                    </div>

                    <div class="detail-item full">
                        <label>Ubicación de entrada</label>
                        <span v-if="fichajeActual.entry_location">
                            Lat: {{ fichajeActual.entry_location.lat }},
                            Lng: {{ fichajeActual.entry_location.lng }}
                            <template v-if="fichajeActual.entry_location.accuracy">
                                · Precisión: {{ Math.round(fichajeActual.entry_location.accuracy) }} m
                            </template>
                        </span>
                        <span v-else>No registrada</span>
                    </div>

                    <div class="detail-item full">
                        <label>Ubicación de salida</label>
                        <span v-if="fichajeActual.exit_location && !fichajeActual.auto_closed">
                            Lat: {{ fichajeActual.exit_location.lat }},
                            Lng: {{ fichajeActual.exit_location.lng }}
                            <template v-if="fichajeActual.exit_location.accuracy">
                                · Precisión: {{ Math.round(fichajeActual.exit_location.accuracy) }} m
                            </template>
                        </span>
                        <span v-else>No registrada</span>
                    </div>

                    <div class="detail-item full">
                        <label>Notas</label>
                        <span>{{ fichajeActual.notes || "No hay notas disponibles." }}</span>
                    </div>

                    <div class="detail-item full">
                        <label>Tramos horarios</label>

                        <ul
                            v-if="
                                fichajeActual.activity_sections &&
                                fichajeActual.activity_sections.length
                            "
                            class="sections-list"
                        >
                            <li
                                v-for="(section, index) in fichajeActual.activity_sections"
                                :key="index"
                            >
                                {{ section }}
                            </li>
                        </ul>

                        <span v-else>No hay tramos horarios disponibles.</span>
                    </div>

                    <div class="detail-item full">
                        <label>Archivos adjuntos</label>

                        <a
                            v-if="fichajeActual.work_order_file"
                            :href="`${baseUrl}/assets/work_orders/${fichajeActual.work_order_file}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="attachment-link"
                        >
                            <i class="fa-solid fa-paperclip"></i>
                            <span>Ver archivo adjunto</span>
                        </a>

                        <span v-else>No hay archivos adjuntos disponibles.</span>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="isMyRequestsModalOpen"
            class="daily-modal-backdrop"
        >
            <div class="daily-modal my-requests-modal">
                <button
                    type="button"
                    class="modal-close"
                    @click="closeMyRequestsModal"
                    title="Cerrar"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="forgotten-request-header">
                    <h2>Mis solicitudes</h2>
                    <p>Consulte el estado de sus solicitudes de fichaje y vacaciones.</p>
                </div>

                <div v-if="isLoadingMyRequests" class="my-requests-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Cargando solicitudes...</span>
                </div>

                <div
                    v-else-if="myForgottenSigningRequests.length === 0 && myVacationRequests.length === 0"
                    class="my-requests-empty"
                >
                    <div class="empty-icon">
                        <i class="fa-regular fa-clipboard"></i>
                    </div>
                    <h4>No tiene solicitudes enviadas</h4>
                    <p>Cuando solicite vacaciones o un fichaje olvidado, aparecerá aquí.</p>
                </div>

                <div v-else class="my-requests-list">
                    <article
                        v-for="request in myVacationRequests"
                        :key="`vacation-${getMyRequestId(request)}`"
                        class="my-request-card vacation"
                    >
                        <div class="my-request-main">
                            <div class="my-request-icon">
                                <i class="fa-regular fa-calendar-check"></i>
                            </div>

                            <div>
                                <strong>{{ getMyRequestTypeLabel('vacation') }}</strong>
                                <span>{{ getMyRequestDateRange(request, 'vacation') }}</span>
                                <p>{{ getMyRequestDescription(request, 'vacation') }}</p>
                            </div>
                        </div>

                        <span
                            class="my-request-status"
                            :class="`status-${request.status || 'pending'}`"
                        >
                            {{ getMyRequestStatusLabel(request.status) }}
                        </span>
                    </article>

                    <article
                        v-for="request in myForgottenSigningRequests"
                        :key="`forgotten-${getMyRequestId(request)}`"
                        class="my-request-card signing"
                    >
                        <div class="my-request-main">
                            <div class="my-request-icon">
                                <i class="fa-regular fa-clock"></i>
                            </div>

                            <div>
                                <strong>{{ getMyRequestTypeLabel('forgotten_signing') }}</strong>
                                <span>{{ getMyRequestDateRange(request, 'forgotten_signing') }}</span>
                                <p>{{ getMyRequestDescription(request, 'forgotten_signing') }}</p>
                            </div>
                        </div>

                        <span
                            class="my-request-status"
                            :class="`status-${request.status || 'pending'}`"
                        >
                            {{ getMyRequestStatusLabel(request.status) }}
                        </span>
                    </article>
                </div>

                <div class="forgotten-request-actions">
                    <button
                        type="button"
                        class="forgotten-secondary-btn"
                        @click="closeMyRequestsModal"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="isVacationRequestModalOpen"
            class="daily-modal-backdrop"
        >
            <div class="daily-modal vacation-request-modal">
                <button
                    type="button"
                    class="modal-close"
                    @click="closeVacationRequestModal"
                    title="Cerrar"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="forgotten-request-header">
                    <h2>Solicitar vacaciones</h2>
                    <p>
                        Esta solicitud será enviada al gestor y quedará pendiente de aprobación.
                    </p>
                </div>

                <div class="forgotten-request-grid vacation-request-grid">
                    <div class="forgotten-field">
                        <label>Fecha de inicio</label>
                        <input
                            v-model="vacationRequestForm.start_date"
                            type="date"
                            class="forgotten-control"
                        />
                    </div>

                    <div class="forgotten-field">
                        <label>Fecha de fin</label>
                        <input
                            v-model="vacationRequestForm.end_date"
                            type="date"
                            class="forgotten-control"
                            :min="vacationRequestForm.start_date"
                        />
                    </div>

                    <div class="forgotten-field full">
                        <label>Notas / motivo</label>
                        <textarea
                            v-model="vacationRequestForm.reason"
                            class="forgotten-textarea"
                            placeholder="Ej: vacaciones familiares, viaje, descanso..."
                        ></textarea>
                    </div>
                </div>

                <div class="forgotten-request-warning vacation-request-warning">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        Al enviar la solicitud, se avisará por correo al gestor. La solicitud no estará aprobada hasta que el gestor la revise.
                    </span>
                </div>

                <div class="forgotten-request-actions">
                    <button
                        type="button"
                        class="forgotten-secondary-btn"
                        @click="closeVacationRequestModal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="forgotten-primary-btn"
                        :disabled="isSendingVacationRequest"
                        @click="submitVacationRequest"
                    >
                        {{ isSendingVacationRequest ? "Enviando..." : "Enviar solicitud" }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="isForgottenRequestModalOpen"
            class="daily-modal-backdrop"
        >
            <div class="daily-modal forgotten-request-modal">
                <button
                    type="button"
                    class="modal-close"
                    @click="closeForgottenRequestModal"
                    title="Cerrar"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="forgotten-request-header">
                    <h2>Solicitar fichaje olvidado</h2>
                    <p>Esta solicitud será revisada por el gestor antes de crear el fichaje.</p>
                </div>

                <div class="forgotten-request-grid">
                    <div class="forgotten-field">
                        <label>Fecha</label>
                        <input
                            v-model="forgottenRequestForm.date"
                            type="date"
                            class="forgotten-control"
                        />
                    </div>

                    <div class="forgotten-field">
                        <label>Hora de entrada</label>
                        <input
                            v-model="forgottenRequestForm.entry"
                            type="time"
                            class="forgotten-control"
                        />
                    </div>

                    <div class="forgotten-field">
                        <label>Hora de salida</label>
                        <input
                            v-model="forgottenRequestForm.exit"
                            type="time"
                            class="forgotten-control"
                        />
                    </div>

                    <div class="forgotten-field full">
                        <label>Motivo</label>
                        <textarea
                            v-model="forgottenRequestForm.reason"
                            class="forgotten-textarea"
                            placeholder="Ej: se me olvidó fichar la salida"
                        ></textarea>
                    </div>
                </div>

                <div class="forgotten-request-warning">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        Esta acción no crea el fichaje directamente. El gestor deberá aprobar la solicitud.
                    </span>
                </div>

                <div class="forgotten-request-actions">
                    <button
                        type="button"
                        class="forgotten-secondary-btn"
                        @click="closeForgottenRequestModal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="forgotten-primary-btn"
                        :disabled="isSendingForgottenRequest"
                        @click="submitForgottenSigningRequest"
                    >
                        {{ isSendingForgottenRequest ? "Enviando..." : "Enviar solicitud" }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="isMonthlySummaryModalOpen"
            class="daily-modal-backdrop"
        >
            <div class="daily-modal monthly-summary-modal">
                <button
                    type="button"
                    class="modal-close"
                    @click="closeMonthlySummaryModal"
                    title="Cerrar"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="monthly-summary-header">
                    <div>
                        <h2>Mi resumen mensual</h2>
                        <p>
                            Consulte sus días trabajados, horas, vacaciones e incidencias.
                        </p>
                    </div>
                </div>

                <div class="monthly-summary-filters">
                    <div class="summary-filter">
                        <label>Año</label>

                        <input
                            v-model="monthlySummaryFilters.year"
                            type="number"
                            class="summary-control"
                        />
                    </div>

                    <div class="summary-filter">
                        <label>Mes</label>

                        <select
                            v-model="monthlySummaryFilters.month"
                            class="summary-control"
                        >
                            <option :value="1">Enero</option>
                            <option :value="2">Febrero</option>
                            <option :value="3">Marzo</option>
                            <option :value="4">Abril</option>
                            <option :value="5">Mayo</option>
                            <option :value="6">Junio</option>
                            <option :value="7">Julio</option>
                            <option :value="8">Agosto</option>
                            <option :value="9">Septiembre</option>
                            <option :value="10">Octubre</option>
                            <option :value="11">Noviembre</option>
                            <option :value="12">Diciembre</option>
                        </select>
                    </div>

                    <button
                        type="button"
                        class="summary-search-btn"
                        @click="fetchMyMonthlySummary"
                    >
                        Consultar
                    </button>
                </div>

                <div v-if="isLoadingMonthlySummary" class="monthly-summary-empty">
                    Cargando resumen...
                </div>

                <div v-else-if="!monthlySummary" class="monthly-summary-empty">
                    Seleccione mes y año para consultar su resumen.
                </div>

                <div v-else>
                    <div class="monthly-summary-title">
                        {{ getMonthName(monthlySummary.month) }} {{ monthlySummary.year }}
                    </div>

                    <div class="monthly-summary-kpis">
                        <div class="summary-kpi">
                            <span>Días trabajados</span>
                            <strong>{{ monthlySummary.worked_days }}</strong>
                        </div>

                        <div class="summary-kpi">
                            <span>Horas totales</span>
                            <strong>{{ monthlySummary.total_hours }}</strong>
                        </div>

                        <div class="summary-kpi">
                            <span>Festivos</span>
                            <strong>{{ monthlySummary.company_holidays }}</strong>
                        </div>

                        <div class="summary-kpi">
                            <span>Vacaciones</span>
                            <strong>{{ monthlySummary.vacation_days }}</strong>
                        </div>

                        <div class="summary-kpi">
                            <span>Ausencias</span>
                            <strong>{{ monthlySummary.absence_days }}</strong>
                        </div>

                        <div class="summary-kpi danger">
                            <span>Incidencias</span>
                            <strong>{{ monthlySummary.incidents_count }}</strong>
                        </div>
                    </div>

                    <div class="monthly-summary-days">
                        <div
                            v-for="day in monthlySummary.days"
                            :key="day.date"
                            class="monthly-summary-day"
                            :class="`status-${day.status}`"
                        >
                            <div class="day-main-info">
                                <strong>{{ formatSummaryDate(day.date) }}</strong>
                                <span>{{ capitalizeText(day.day_name) }}</span>
                            </div>

                            <div class="day-status">
                                {{ getMonthlyDayStatusLabel(day.status) }}
                            </div>

                            <div class="day-hours">
                                {{ day.hours }}
                            </div>

                            <div
                                v-if="day.signins && day.signins.length"
                                class="day-signins"
                            >
                                <span
                                    v-for="signin in day.signins"
                                    :key="signin._id || `${day.date}-${signin.entry}`"
                                >
                                    {{ signin.entry || "--:--" }} - {{ signin.exit || "--:--" }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import axios from "axios";

export default {
    name: "DailySigningsComponent",
    props: ["basicData"],

    data() {
        return {
            todayWorkCalendarEvents: [],
            isLoadingWorkCalendar: false,
            isEntrada: true,
            isLoading: true,
            isSigning: false,
            fichajes: [],
            maxPairsToShow: 2,
            userId: null,
            selectedStartDate: "",
            selectedEndDate: "",
            currentPage: 1,
            totalPages: 1,
            perPage: 10,
            viewDetailsModalVisible: false,
            fichajeActual: null,
            uploadStatus: {},
            baseUrl: window.location.origin,
            locationStatus: null,
            isMonthlySummaryModalOpen: false,
            isLoadingMonthlySummary: false,
            monthlySummary: null,
            monthlySummaryFilters: {
                year: new Date().getFullYear(),
                month: new Date().getMonth() + 1,
            },
            isForgottenRequestModalOpen: false,
            isSendingForgottenRequest: false,
            forgottenRequestForm: {
                date: "",
                entry: "",
                exit: "",
                reason: "",
            },
            isVacationRequestModalOpen: false,
            isSendingVacationRequest: false,
            vacationRequestForm: {
                start_date: "",
                end_date: "",
                reason: "",
            },
            isMyRequestsModalOpen: false,
            isLoadingMyRequests: false,
            myForgottenSigningRequests: [],
            myVacationRequests: [],
        };
    },

    methods: {
        splitIntoChunks(array, chunkSize) {
            const chunks = [];
            for (let i = 0; i < array.length; i += chunkSize) {
                chunks.push(array.slice(i, i + chunkSize));
            }
            return chunks;
        },

        async openMyRequestsModal() {
            this.isMyRequestsModalOpen = true;
            await this.fetchMyRequests();
        },

        closeMyRequestsModal() {
            this.isMyRequestsModalOpen = false;
        },

        async fetchMyRequests() {
            if (!this.userId) return;

            try {
                this.isLoadingMyRequests = true;

                const forgottenResponse = await axios.get("/api/signin/forgotten-requests", {
                    params: {
                        user_id: this.userId,
                    },
                });

                this.myForgottenSigningRequests = forgottenResponse.data.data || [];

                const vacationResponse = await axios.get("/api/signin/work-calendar/events", {
                    params: {
                        type: "vacation",
                        user_id: this.userId,
                    },
                });

                this.myVacationRequests = (vacationResponse.data.data || []).filter((request) => {
                    return String(request.user_id) === String(this.userId);
                });
            } catch (error) {
                console.error("Error al cargar mis solicitudes:", error);
                this.myForgottenSigningRequests = [];
                this.myVacationRequests = [];
            } finally {
                this.isLoadingMyRequests = false;
            }
        },

        getMyRequestId(request) {
            if (!request) return null;
            if (typeof request._id === "string") return request._id;
            if (request._id?.$oid) return request._id.$oid;
            if (request.id) return request.id;
            return String(request._id);
        },

        getMyRequestStatusLabel(status) {
            const labels = {
                pending: "Pendiente",
                approved: "Aprobada",
                rejected: "Rechazada",
            };

            return labels[status] || status || "Pendiente";
        },

        getMyRequestTypeLabel(type) {
            const labels = {
                forgotten_signing: "Fichaje olvidado",
                vacation: "Vacaciones",
            };

            return labels[type] || type || "Solicitud";
        },

        getMyRequestDateRange(request, type) {
            if (type === "vacation") {
                return `${this.formatSummaryDate(request.start_date)} - ${this.formatSummaryDate(request.end_date)}`;
            }

            return this.formatSummaryDate(request.date);
        },

        getMyRequestDescription(request, type) {
            if (type === "vacation") {
                return request.notes || "Sin notas";
            }

            return `${request.entry || "--:--"} - ${request.exit || "--:--"} · ${request.reason || "Sin motivo"}`;
        },

        myPendingRequestsCount() {
            return [
                ...this.myForgottenSigningRequests,
                ...this.myVacationRequests,
            ].filter((request) => (request.status || "pending") === "pending").length;
        },

        async fetchUserSignings() {
            try {
                this.isLoading = true;
                if (!this.userId) return;

                const response = await axios.get(
                    `/api/signin/user/${this.userId}`,
                    {
                        params: {
                            page: this.currentPage,
                            per_page: this.perPage,
                        },
                    }
                );
                const currentPage = response.data.current_page || 1;
                const lastPage = response.data.last_page || 1;

                this.currentPage = currentPage;
                this.totalPages = lastPage;
                const signings = Array.isArray(response.data.data)
                    ? response.data.data
                    : [];

                const grouped = {};
                signings.forEach((s) => {
                    const dia = s.date.split("-").reverse().join("/");
                    if (!grouped[dia]) grouped[dia] = [];
                    grouped[dia].push({
                        _id: s._id,
                        entrada: s.entry,
                        salida: s.exit,
                        auto_closed: s.auto_closed || false,
                        auto_closed_reason: s.auto_closed_reason || null,
                        ubicacionEntrada: s.entry_location || null,
                        ubicacionSalida: s.exit_location || null,
                        work_order_id: s.work_order_id || null,
                        work_order_file: s.work_order_file || null,
                    });
                });

                this.fichajes = Object.entries(grouped)
                    .map(([dia, pares]) => ({ dia, pares }))
                    .sort((a, b) => {
                        const dateA = new Date(a.dia.split('/').reverse().join('-'));
                        const dateB = new Date(b.dia.split('/').reverse().join('-'));
                        return dateB - dateA;
                    });

                await this.fetchLastStatus(false);
            } catch (error) {
                console.error("❌ Error al cargar fichajes:", error);
            } finally {
                this.isLoading = false;
            }
        },

        previousPage(page) {
            if (this.selectedStartDate && this.selectedEndDate && page > 1) {
                this.currentPage = page - 1;
                this.filtrarPorFechas();
            } else if (page > 1) {
                this.currentPage = page - 1;
                this.fetchUserSignings();
            }
        },

        nextPage(page) {
            if (this.selectedStartDate && this.selectedEndDate && page < this.totalPages) {
                this.currentPage = page + 1;
                this.filtrarPorFechas();
            } else if (page < this.totalPages) {
                this.currentPage = page + 1;
                this.fetchUserSignings();
            }
        },

        async fetchLastStatus(showAlert = false) {
            try {
                const res = await axios.get(
                    `/api/signin/user/${this.userId}/last`
                );

                this.isEntrada = res.data.should_sign_in;

                if (res.data.type === "auto_closed_previous_day") {
                    if (showAlert) {
                        Swal.fire({
                            icon: "warning",
                            title: "Fichaje anterior cerrado",
                            html: `
                                <p>${res.data.message}</p>
                                <p style="margin-top: 8px;">
                                    Entrada registrada el 
                                    <strong>${res.data.last_signin?.date || "-"}</strong>
                                    a las 
                                    <strong>${res.data.last_signin?.entry || "--:--"}</strong>.
                                </p>
                            `,
                            confirmButtonText: "Entendido",
                        });
                    }

                    return {
                        autoClosed: true,
                        data: res.data,
                    };
                }

                return {
                    autoClosed: false,
                    data: res.data,
                };
            } catch (error) {
                console.error(
                    "❌ Error al obtener estado del último fichaje:",
                    error
                );

                this.isEntrada = true;

                return {
                    autoClosed: false,
                    data: null,
                };
            }
        },

        async filtrarPorFechas() {
            if (!this.selectedStartDate || !this.selectedEndDate) return;
            this.isLoading = true;
            try {
                const res = await axios.get(
                    `/api/signin/user/${this.userId}/date/${this.selectedStartDate}/${this.selectedEndDate}`,
                    {
                        params: {
                            page: this.currentPage,
                            per_page: this.perPage,
                        },
                    }
                );

                const currentPage = res.data.current_page || 1;
                const lastPage = res.data.last_page || 1;

                this.currentPage = currentPage;
                this.totalPages = lastPage;

                const signings = Array.isArray(res.data.data)
                    ? res.data.data
                    : [];

                const grouped = {};
                signings.forEach((s) => {
                    const dia = new Date(s.date).toLocaleDateString("en-GB");
                    if (!grouped[dia]) grouped[dia] = [];
                    grouped[dia].push({
                        _id: s._id,
                        entrada: s.entry,
                        salida: s.exit,
                        auto_closed: s.auto_closed || false,
                        auto_closed_reason: s.auto_closed_reason || null,
                        ubicacionEntrada: s.entry_location || null,
                        ubicacionSalida: s.exit_location || null,
                        work_order_id: s.work_order_id || null,
                        work_order_file: s.work_order_file || null,
                    });
                });

                this.fichajes = Object.entries(grouped)
                    .map(([dia, pares]) => ({ dia, pares }))
                    .sort((a, b) => {
                        const dateA = new Date(a.dia.split('/').reverse().join('-'));
                        const dateB = new Date(b.dia.split('/').reverse().join('-'));
                        return dateB - dateA; // descendente (más reciente primero)
                    });

                await this.fetchLastStatus();
            } catch (error) {
                console.error("❌ Error al filtrar fichajes:", error);
            } finally {
                this.isLoading = false;
            }
        },

        limpiarFiltro() {
            this.selectedStartDate = "";
            this.selectedEndDate = "";
            this.fetchUserSignings();
        },

        abrirMapa(ubicacion) {
            if (!ubicacion?.lat || !ubicacion?.lng) {
                Swal.fire({
                    icon: "warning",
                    title: "Ubicación no disponible",
                    text: "Este fichaje no tiene coordenadas guardadas.",
                    confirmButtonText: "Entendido",
                });

                return;
            }

            const lat = encodeURIComponent(ubicacion.lat);
            const lng = encodeURIComponent(ubicacion.lng);

            window.open(
                `https://www.google.com/maps?q=${lat},${lng}`,
                "_blank",
                "noopener,noreferrer"
            );
        },

        onFileChange(event, registro, par) {
            const file = event.target.files[0];
            if (!file) return;

            console.log("📁 Archivo seleccionado:", file.name);

            // suponiendo que cada par (fichaje) tiene un _id que viene del backend
            const signinId = par._id || registro._id;

            if (!signinId) {
                console.warn("⚠️ No se encontró el ID del fichaje.");
                return;
            }

            const formData = new FormData();
            formData.append("work_order_file", file);

            axios
                .post(`/api/signin/${signinId}/uploadWorkOrder`, formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                })
                .then((response) => {
                    console.log("✅ Archivo subido con éxito:", response.data);
                    this.uploadStatus[par._id] = "success";
                    this.fetchUserSignings(); // recarga la tabla
                })
                .catch((error) => {
                    console.error("❌ Error al subir el archivo:", error);
                    this.uploadStatus[par._id] = "error";
                });
        },

        getCurrentUserId() {
            return (
                this.basicData?.userLogged?._id ||
                this.basicData?.user?._id ||
                this.basicData?.userLogged?.id ||
                this.basicData?.user?.id ||
                this.basicData?.selectedUserId ||
                null
            );
        },

        getTodayDate() {
            return new Date().toISOString().slice(0, 10);
        },

        async fetchTodayWorkCalendarStatus() {
            const userId = this.getCurrentUserId();

            if (!userId) {
                this.todayWorkCalendarEvents = [];
                return;
            }

            const today = this.getTodayDate();

            try {
                this.isLoadingWorkCalendar = true;

                const response = await axios.get("/api/signin/work-calendar/summary", {
                    params: {
                        user_id: userId,
                        start_date: today,
                        end_date: today,
                    },
                });

                this.todayWorkCalendarEvents = response.data.data || [];
            } catch (error) {
                console.error("Error al cargar calendario laboral de hoy:", error);
                this.todayWorkCalendarEvents = [];
            } finally {
                this.isLoadingWorkCalendar = false;
            }
        },

        openForgottenRequestModal() {
            this.forgottenRequestForm = {
                date: "",
                entry: "",
                exit: "",
                reason: "",
            };

            this.isForgottenRequestModalOpen = true;
        },

        openVacationRequestModal() {
            this.vacationRequestForm = {
                start_date: "",
                end_date: "",
                reason: "",
            };

            this.isVacationRequestModalOpen = true;
        },

        closeVacationRequestModal() {
            this.isVacationRequestModalOpen = false;
        },

        async submitVacationRequest() {
            if (!this.vacationRequestForm.start_date) {
                Swal.fire("Atención", "Debe indicar la fecha de inicio.", "warning");
                return;
            }

            if (!this.vacationRequestForm.end_date) {
                Swal.fire("Atención", "Debe indicar la fecha de fin.", "warning");
                return;
            }

            if (this.vacationRequestForm.end_date < this.vacationRequestForm.start_date) {
                Swal.fire("Atención", "La fecha de fin no puede ser anterior a la fecha de inicio.", "warning");
                return;
            }

            try {
                this.isSendingVacationRequest = true;

                await axios.post("/api/signin/vacation-request", {
                    start_date: this.vacationRequestForm.start_date,
                    end_date: this.vacationRequestForm.end_date,
                    reason: this.vacationRequestForm.reason || null,
                });

                this.closeVacationRequestModal();

                await this.fetchTodayWorkCalendarStatus();
                await this.fetchMyRequests();

                Swal.fire(
                    "Solicitud enviada",
                    "Su solicitud de vacaciones queda pendiente de revisión por el gestor.",
                    "success"
                );
            } catch (error) {
                console.error("Error al enviar solicitud de vacaciones:", error);

                Swal.fire(
                    "Error",
                    error.response?.data?.message || "No se pudo enviar la solicitud de vacaciones.",
                    "error"
                );
            } finally {
                this.isSendingVacationRequest = false;
            }
        },

        closeForgottenRequestModal() {
            this.isForgottenRequestModalOpen = false;
        },

        async submitForgottenSigningRequest() {
            if (!this.forgottenRequestForm.date) {
                Swal.fire("Atención", "Debe indicar la fecha.", "warning");
                return;
            }

            if (!this.forgottenRequestForm.entry) {
                Swal.fire("Atención", "Debe indicar la hora de entrada.", "warning");
                return;
            }

            if (!this.forgottenRequestForm.reason || this.forgottenRequestForm.reason.length < 5) {
                Swal.fire("Atención", "Debe indicar un motivo.", "warning");
                return;
            }

            if (
                this.forgottenRequestForm.exit &&
                this.forgottenRequestForm.exit <= this.forgottenRequestForm.entry
            ) {
                Swal.fire("Atención", "La hora de salida debe ser posterior a la entrada.", "warning");
                return;
            }

            try {
                this.isSendingForgottenRequest = true;

                await axios.post("/api/signin/forgotten-request", {
                    date: this.forgottenRequestForm.date,
                    entry: this.forgottenRequestForm.entry,
                    exit: this.forgottenRequestForm.exit || null,
                    reason: this.forgottenRequestForm.reason,
                });

                this.closeForgottenRequestModal();
                await this.fetchMyRequests();

                Swal.fire(
                    "Solicitud enviada",
                    "Su solicitud queda pendiente de revisión por el gestor.",
                    "success"
                );
            } catch (error) {
                console.error("Error al enviar solicitud de fichaje:", error);

                Swal.fire(
                    "Error",
                    error.response?.data?.message || "No se pudo enviar la solicitud.",
                    "error"
                );
            } finally {
                this.isSendingForgottenRequest = false;
            }
        },

        getWorkCalendarTypeLabel(type) {
            const labels = {
                company_holiday: "Festivo de empresa",
                vacation: "Vacaciones",
                medical_leave: "Baja médica",
                personal_day: "Asunto propio",
                justified_absence: "Ausencia justificada",
            };

            return labels[type] || type || "Evento laboral";
        },

        getWorkCalendarStatusLabel(status) {
            const labels = {
                approved: "Aprobado",
                pending: "Pendiente",
                rejected: "Rechazado",
            };

            return labels[status] || "Aprobado";
        },

        getMainTodayWorkCalendarEvent() {
            if (!this.todayWorkCalendarEvents.length) return null;

            const validEvents = this.todayWorkCalendarEvents.filter((event) => {
                return (event.status || "approved") !== "rejected";
            });

            if (!validEvents.length) return null;

            return (
                validEvents.find((event) => event.type === "company_holiday") ||
                validEvents.find((event) => event.type === "vacation") ||
                validEvents.find((event) => event.type === "medical_leave") ||
                validEvents.find((event) => event.type === "personal_day") ||
                validEvents.find((event) => event.type === "justified_absence") ||
                validEvents[0]
            );
        },

        getTodayWorkCalendarMessage() {
            const event = this.getMainTodayWorkCalendarEvent();

            if (!event) return null;

            const typeLabel = this.getWorkCalendarTypeLabel(event.type);
            const statusLabel = this.getWorkCalendarStatusLabel(event.status || "approved");

            if ((event.status || "approved") === "pending") {
                return `${typeLabel} pendiente de aprobación: ${event.title}`;
            }

            return `${typeLabel}: ${event.title}`;
        },

        shouldWarnBeforeSigningByCalendar() {
            const event = this.getMainTodayWorkCalendarEvent();

            if (!event) return false;

            return (event.status || "approved") === "approved";
        },

        async openMonthlySummaryModal() {
            this.isMonthlySummaryModalOpen = true;
            await this.fetchMyMonthlySummary();
        },

        closeMonthlySummaryModal() {
            this.isMonthlySummaryModalOpen = false;
        },

        async fetchMyMonthlySummary() {
            if (!this.userId) {
                Swal.fire("Error", "No se pudo identificar el usuario.", "error");
                return;
            }

            try {
                this.isLoadingMonthlySummary = true;
                this.monthlySummary = null;

                const response = await axios.get("/api/signin/monthly-summary", {
                    params: {
                        user_id: this.userId,
                        year: this.monthlySummaryFilters.year,
                        month: this.monthlySummaryFilters.month,
                    },
                });

                this.monthlySummary = response.data.data;
            } catch (error) {
                console.error("Error al cargar resumen mensual:", error);

                Swal.fire(
                    "Error",
                    error.response?.data?.message || "No se pudo cargar el resumen mensual.",
                    "error"
                );
            } finally {
                this.isLoadingMonthlySummary = false;
            }
        },

        getMonthlyDayStatusLabel(status) {
            const labels = {
                worked: "Trabajado",
                auto_closed: "Cierre automático",
                open_signin: "Jornada abierta",
                company_holiday: "Festivo",
                vacation: "Vacaciones",
                absence: "Ausencia",
                missing_signin: "Falta fichaje",
                no_work: "No laborable",
            };

            return labels[status] || status;
        },

        getMonthName(month) {
            const months = {
                1: "Enero",
                2: "Febrero",
                3: "Marzo",
                4: "Abril",
                5: "Mayo",
                6: "Junio",
                7: "Julio",
                8: "Agosto",
                9: "Septiembre",
                10: "Octubre",
                11: "Noviembre",
                12: "Diciembre",
            };

            return months[Number(month)] || month;
        },

        capitalizeText(text) {
            if (!text) return "";

            return text.charAt(0).toUpperCase() + text.slice(1);
        },

        formatSummaryDate(date) {
            if (!date) return "-";

            const parts = date.split("-");
            if (parts.length !== 3) return date;

            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        },

        async abrirModal(signinId) {
            // Aquí puedes implementar la lógica para abrir un modal
            try {
                const res = await axios.get(`/api/signin/${signinId}`);
                // y mostrar los detalles del fichaje con el ID proporcionado.
                console.log("Abrir modal para el fichaje con ID:", signinId);
                this.fichajeActual = res.data;
                this.viewDetailsModalVisible = true;
            } catch (error) {
                console.error(
                    "❌ Error al obtener detalles del fichaje:",
                    error
                );
                return;
            }
        },

        async obtenerUbicacionActual() {
            return new Promise((resolve) => {
                if (!("geolocation" in navigator)) {
                    resolve({
                        location: null,
                        error: {
                            code: "NOT_SUPPORTED",
                            message: "El navegador no soporta geolocalización.",
                        },
                    });
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        resolve({
                            location: {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude,
                                accuracy: position.coords.accuracy,
                                altitude: position.coords.altitude,
                                altitude_accuracy: position.coords.altitudeAccuracy,
                                heading: position.coords.heading,
                                speed: position.coords.speed,
                                source: "browser",
                                captured_at: new Date().toISOString(),
                            },
                            error: null,
                        });
                    },
                    (error) => {
                        const errors = {
                            1: {
                                code: "PERMISSION_DENIED",
                                message: "Permiso de ubicación denegado.",
                            },
                            2: {
                                code: "POSITION_UNAVAILABLE",
                                message: "La ubicación no está disponible.",
                            },
                            3: {
                                code: "TIMEOUT",
                                message: "La ubicación ha tardado demasiado en responder.",
                            },
                        };

                        resolve({
                            location: null,
                            error: errors[error.code] || {
                                code: "UNKNOWN",
                                message: "No se pudo obtener la ubicación.",
                            },
                        });
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 12000,
                        maximumAge: 30000,
                    }
                );
            });
        },


        showLocationWarning(locationError) {
            if (!locationError) return;

            const messages = {
                PERMISSION_DENIED:
                    "Ha denegado el permiso de ubicación. El fichaje se registrará sin ubicación.",
                POSITION_UNAVAILABLE:
                    "No se ha podido obtener la ubicación actual. El fichaje se registrará sin ubicación.",
                TIMEOUT:
                    "La ubicación ha tardado demasiado. El fichaje se registrará sin ubicación.",
                NOT_SUPPORTED:
                    "Su navegador no permite obtener ubicación. El fichaje se registrará sin ubicación.",
                UNKNOWN:
                    "No se pudo obtener la ubicación. El fichaje se registrará sin ubicación.",
            };

            Swal.fire({
                icon: "warning",
                title: "Ubicación no disponible",
                text:
                    messages[locationError.code] ||
                    "El fichaje se registrará sin ubicación.",
                timer: 2600,
                showConfirmButton: false,
            });
        },

        async toggleFichaje() {
            if (this.isSigning || this.isLoading) return;

            const calendarEvent = this.getMainTodayWorkCalendarEvent();

            if (this.shouldWarnBeforeSigningByCalendar() && calendarEvent) {
                const result = await Swal.fire({
                    icon: "warning",
                    title: this.getWorkCalendarTypeLabel(calendarEvent.type),
                    text: `Hoy está marcado como "${calendarEvent.title}". ¿Desea registrar el fichaje igualmente?`,
                    showCancelButton: true,
                    confirmButtonText: "Sí, fichar igualmente",
                    cancelButtonText: "Cancelar",
                });

                if (!result.isConfirmed) {
                    return;
                }
            }

            this.isSigning = true;
            this.locationStatus = "requesting";

            let location = null;
            let locationError = null;

            try {
                const result = await this.obtenerUbicacionActual();

                location = result.location;
                locationError = result.error;

                this.locationStatus = location ? "success" : "failed";

                const res = await axios.post("/api/signin/saveSignings", {
                    location,
                    location_error: locationError,
                });

                if (res.data.type === "auto_closed_previous_day") {
                    this.isEntrada = true;
                    this.currentPage = 1;

                    await this.fetchUserSignings();
                    await this.fetchTodayWorkCalendarStatus();

                    Swal.fire({
                        icon: "warning",
                        title: "Fichaje anterior cerrado",
                        text: res.data.message,
                        confirmButtonText: "Entendido",
                    });

                    return;
                }

                if (res.data.type === "entry") {
                    this.isEntrada = false;
                }

                if (res.data.type === "exit") {
                    this.isEntrada = true;
                }

                await this.fetchLastStatus(false);
                await this.fetchUserSignings();
                await this.fetchTodayWorkCalendarStatus();

                if (locationError) {
                    this.showLocationWarning(locationError);
                    return;
                }

                Swal.fire({
                    icon: "success",
                    title: "Fichaje registrado",
                    text: res.data.message || "El fichaje se ha registrado correctamente.",
                    timer: 1800,
                    showConfirmButton: false,
                });
            } catch (error) {
                console.error("❌ Error al guardar el fichaje:", error);

                const data = error.response?.data;

                Swal.fire({
                    icon: "error",
                    title: "No se pudo registrar el fichaje",
                    text:
                        data?.message ||
                        "Ha ocurrido un error al registrar el fichaje. Inténtelo de nuevo.",
                    confirmButtonText: "Entendido",
                });
            } finally {
                this.isSigning = false;
                this.locationStatus = null;
            }
        },
    },

    //:href="`${baseUrl}/assets/work_orders/${par.work_order_file}`"

    async mounted() {
        try {
            const userResponse = this.basicData.userLogged;
            this.userId = userResponse._id;

            await this.fetchTodayWorkCalendarStatus();
            await this.fetchMyRequests();

            const statusResult = await this.fetchLastStatus(true);

            this.currentPage = 1;
            await this.fetchUserSignings();

            if (statusResult.autoClosed) {
                this.isEntrada = true;
            }
        } catch (error) {
            console.error("❌ Error obteniendo el usuario:", error);
            this.isLoading = false;
        }
    },
};
</script>
<style scoped>
.daily-signings-module {
    width: 100%;
    padding: 42px 48px;
    color: #073b78;
}

.signing-action-card,
.zoco-card {
    background: #ffffff;
    border: 1px solid rgba(7, 59, 120, 0.08);
    border-radius: 26px;
    box-shadow: 0 14px 30px rgba(7, 59, 120, 0.09);
}

.signing-action-card {
    margin-bottom: 26px;
    padding: 28px;
}

.signing-action-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
}

.module-kicker {
    margin: 0 0 6px;
    font-size: 13px;
    font-weight: 800;
    color: #6d84a6;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.signing-action-content h2 {
    margin: 0;
    font-size: 28px;
    line-height: 1.1;
    font-weight: 900;
    color: #073b78;
}

.module-description {
    margin: 8px 0 0;
    font-size: 15px;
    color: #4e6483;
}

.signing-main-button {
    min-width: 230px;
    min-height: 56px;
    padding: 0 24px;
    border: 0;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: #073b78;
    color: #ffffff;
    font-size: 15px;
    font-weight: 900;
    cursor: pointer;
    box-shadow: 0 14px 24px rgba(7, 59, 120, 0.2);
    transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
}

.signing-main-button.exit {
    background: #8f1d1d;
    box-shadow: 0 14px 24px rgba(143, 29, 29, 0.18);
}

.signing-main-button:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 18px 30px rgba(7, 59, 120, 0.24);
}

.signing-main-button:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.signing-button-icon {
    width: 32px;
    height: 32px;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.16);
}

.filters-card {
    padding: 24px;
    margin-bottom: 26px;
}

.filters-header {
    margin-bottom: 18px;
}

.section-kicker {
    margin: 0 0 5px;
    font-size: 12px;
    font-weight: 900;
    color: #6d84a6;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.filters-header h3,
.table-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 900;
    color: #073b78;
}

.filters-header p,
.table-header p {
    margin: 5px 0 0;
    font-size: 14px;
    color: #6d84a6;
}

.filters-grid {
    display: grid;
    grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) auto;
    align-items: end;
    gap: 18px;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-field label {
    font-size: 13px;
    font-weight: 800;
    color: #073b78;
}

.zoco-control {
    width: 100%;
    min-height: 44px;
    padding: 0 14px;
    border: 1px solid rgba(7, 59, 120, 0.16);
    border-radius: 14px;
    background: #f7f9fc;
    color: #073b78;
    font-size: 14px;
    font-weight: 600;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.zoco-control:focus {
    background: #ffffff;
    border-color: #073b78;
    box-shadow: 0 0 0 4px rgba(7, 59, 120, 0.08);
}

.control-with-icon {
    position: relative;
}

.control-with-icon .zoco-control {
    padding-right: 42px;
}

.control-with-icon i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6d84a6;
    pointer-events: none;
}

.filters-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.zoco-btn {
    min-height: 44px;
    padding: 0 20px;
    border: 0;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    transition: transform 0.18s ease, opacity 0.18s ease;
    white-space: nowrap;
}

.zoco-btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.zoco-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.zoco-btn-primary {
    background: #073b78;
    color: #ffffff;
    box-shadow: 0 10px 20px rgba(7, 59, 120, 0.18);
}

.zoco-btn-secondary {
    background: #edf2f8;
    color: #073b78;
}

.table-card {
    overflow: hidden;
}

.table-header {
    padding: 24px 28px 18px;
    border-bottom: 1px solid rgba(7, 59, 120, 0.08);
}

.table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.zoco-table {
    width: 100%;
    border-collapse: collapse;
}

.zoco-table th {
    padding: 16px 28px;
    background: #f7f9fc;
    color: #4e6483;
    font-size: 12px;
    font-weight: 900;
    text-align: left;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.zoco-table td {
    padding: 18px 28px;
    border-top: 1px solid rgba(7, 59, 120, 0.07);
    color: #073b78;
    font-size: 14px;
    font-weight: 700;
    vertical-align: middle;
}

.zoco-table tbody tr {
    transition: background 0.18s ease;
}

.zoco-table tbody tr:hover {
    background: #fafcff;
}

.date-pill {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0 12px;
    border-radius: 999px;
    background: #edf2f8;
    color: #073b78;
    font-size: 13px;
    font-weight: 800;
    white-space: nowrap;
}

.time-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 36px;
}

.exit-cell {
    align-items: flex-start;
}

.exit-info,
.detail-exit-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: flex-start;
}

.auto-closed-badge {
    display: inline-flex;
    width: fit-content;
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(245, 158, 11, 0.13);
    color: #92400e;
    font-size: 11px;
    font-weight: 900;
    line-height: 1;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    white-space: nowrap;
}

.icon-btn {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #edf2f8;
    color: #073b78;
    cursor: pointer;
    font-size: 14px;
    transition: transform 0.18s ease, background 0.18s ease, color 0.18s ease;
}

.icon-btn:hover {
    transform: translateY(-1px);
    background: #073b78;
    color: #ffffff;
}

.icon-btn.upload {
    background: rgba(7, 59, 120, 0.08);
}

.icon-btn.view {
    background: rgba(7, 59, 120, 0.12);
}

.input-file {
    display: none;
}

.empty-state,
.loading-state {
    min-height: 210px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6d84a6;
    text-align: center;
}

.empty-icon {
    width: 58px;
    height: 58px;
    margin-bottom: 14px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #edf2f8;
    color: #073b78;
    font-size: 24px;
}

.empty-state h4 {
    margin: 0;
    font-size: 17px;
    font-weight: 900;
    color: #073b78;
}

.empty-state p {
    margin: 6px 0 0;
    font-size: 14px;
}

.loading-state {
    flex-direction: row;
    gap: 10px;
    min-height: 160px;
    font-weight: 800;
}

.pagination-bar {
    padding: 18px 28px 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    color: #073b78;
    font-weight: 800;
}

.pagination-btn {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 12px;
    background: #edf2f8;
    color: #073b78;
    cursor: pointer;
}

.pagination-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.zoco-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 999;
    padding: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(6, 25, 50, 0.34);
    backdrop-filter: blur(4px);
}

.zoco-modal {
    position: relative;
    width: min(760px, 100%);
    max-height: calc(100vh - 64px);
    overflow-y: auto;
    padding: 32px;
    border-radius: 28px;
    background: #ffffff;
    box-shadow: 0 24px 70px rgba(6, 25, 50, 0.22);
}

.modal-close {
    position: absolute;
    top: 22px;
    right: 22px;
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 13px;
    background: #edf2f8;
    color: #073b78;
    cursor: pointer;
}

.modal-header {
    display: flex;
    gap: 16px;
    margin-bottom: 26px;
    padding-right: 48px;
}

.modal-icon {
    width: 52px;
    height: 52px;
    flex: 0 0 52px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #073b78;
    color: #ffffff;
    font-size: 21px;
}

.modal-header h2 {
    margin: 0;
    font-size: 23px;
    font-weight: 900;
    color: #073b78;
}

.modal-header p {
    margin: 6px 0 0;
    color: #6d84a6;
    font-size: 14px;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.detail-item {
    padding: 14px;
    border-radius: 16px;
    background: #f7f9fc;
    border: 1px solid rgba(7, 59, 120, 0.07);
}

.detail-item.full {
    grid-column: 1 / -1;
}

.work-calendar-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    margin-bottom: 18px;
    border-radius: 16px;
    border: 1px solid rgba(245, 158, 11, 0.28);
    background: rgba(245, 158, 11, 0.12);
    color: #92400e;
}

.work-calendar-alert.is-approved {
    border-color: rgba(245, 158, 11, 0.28);
    background: rgba(245, 158, 11, 0.12);
    color: #92400e;
}

.work-calendar-alert.is-pending {
    border-color: rgba(37, 99, 235, 0.22);
    background: rgba(37, 99, 235, 0.09);
    color: #1d4ed8;
}

.work-calendar-alert-icon {
    width: 36px;
    height: 36px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.65);
    flex-shrink: 0;
}

.work-calendar-alert-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.work-calendar-alert-content strong {
    font-size: 14px;
    font-weight: 900;
}

.work-calendar-alert-content span {
    font-size: 13px;
    line-height: 1.4;
}

.detail-item label {
    display: block;
    margin-bottom: 6px;
    color: #6d84a6;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.detail-item span {
    color: #073b78;
    font-size: 14px;
    font-weight: 700;
}

.sections-list {
    margin: 0;
    padding-left: 18px;
    color: #073b78;
    font-weight: 700;
}

.attachment-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #073b78;
    font-weight: 900;
    text-decoration: none;
}

.attachment-link:hover {
    text-decoration: underline;
}

@media (max-width: 900px) {
    .daily-signings-module {
        padding: 28px 20px;
    }

    .signing-action-content {
        flex-direction: column;
        align-items: stretch;
    }

    .signing-main-button {
        width: 100%;
    }

    .filters-grid {
        grid-template-columns: 1fr;
    }

    .filters-actions {
        justify-content: flex-end;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }
}

.monthly-summary-btn {
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid #d1d5db;
    background: #ffffff;
    color: #374151;
    border-radius: 14px;
    padding: 11px 16px;
    font-weight: 900;
    cursor: pointer;
    transition: all 0.2s ease;
}

.monthly-summary-btn:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.daily-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.daily-modal {
    position: relative;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    background: #ffffff;
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 24px 80px rgba(15, 23, 42, 0.25);
}

.monthly-summary-modal {
    max-width: 980px;
}

.modal-close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 999px;
    background: #f3f4f6;
    color: #374151;
    cursor: pointer;
}

.monthly-summary-header {
    padding-right: 48px;
    margin-bottom: 18px;
}

.monthly-summary-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 900;
    color: #111827;
}

.monthly-summary-header p {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 14px;
}

.monthly-summary-filters {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 12px;
    align-items: flex-end;
    margin-bottom: 20px;
}

.summary-filter {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.summary-filter label {
    font-size: 13px;
    font-weight: 900;
    color: #374151;
}

.summary-control {
    border: 1px solid #d1d5db;
    border-radius: 12px;
    padding: 10px 12px;
    outline: none;
}

.summary-search-btn {
    border: 0;
    border-radius: 12px;
    padding: 11px 16px;
    background: #111827;
    color: #ffffff;
    font-weight: 900;
    cursor: pointer;
}

.monthly-summary-empty {
    padding: 24px;
    border-radius: 16px;
    background: #f9fafb;
    color: #6b7280;
    text-align: center;
    font-weight: 800;
}

.monthly-summary-title {
    margin-bottom: 14px;
    font-size: 18px;
    font-weight: 900;
    color: #111827;
}

.monthly-summary-kpis {
    display: grid;
    grid-template-columns: repeat(6, minmax(110px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}

.summary-kpi {
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    border-radius: 16px;
    padding: 14px;
}

.summary-kpi span {
    display: block;
    font-size: 11px;
    font-weight: 900;
    color: #6b7280;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.summary-kpi strong {
    font-size: 22px;
    font-weight: 900;
    color: #111827;
}

.summary-kpi.danger strong {
    color: #dc2626;
}

.monthly-summary-days {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.monthly-summary-day {
    display: grid;
    grid-template-columns: 150px 150px 80px 1fr;
    gap: 12px;
    align-items: center;
    padding: 12px;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
}

.day-main-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.day-main-info strong {
    color: #111827;
    font-weight: 900;
}

.day-main-info span {
    color: #6b7280;
    font-size: 13px;
}

.day-status {
    display: inline-flex;
    width: fit-content;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
}

.day-hours {
    font-weight: 900;
    color: #111827;
}

.day-signins {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    color: #374151;
    font-size: 13px;
}

.monthly-summary-day.status-worked .day-status {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
}

.monthly-summary-day.status-company_holiday .day-status,
.monthly-summary-day.status-vacation .day-status,
.monthly-summary-day.status-absence .day-status {
    background: rgba(37, 99, 235, 0.1);
    color: #1d4ed8;
}

.monthly-summary-day.status-auto_closed .day-status,
.monthly-summary-day.status-open_signin .day-status {
    background: rgba(245, 158, 11, 0.14);
    color: #92400e;
}

.monthly-summary-day.status-missing_signin .day-status {
    background: rgba(239, 68, 68, 0.13);
    color: #b91c1c;
}

.monthly-summary-day.status-no_work .day-status {
    background: #f3f4f6;
    color: #6b7280;
}

.forgotten-signing-btn {
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid #d1d5db;
    background: #ffffff;
    color: #374151;
    border-radius: 14px;
    padding: 11px 16px;
    font-weight: 900;
    cursor: pointer;
    transition: all 0.2s ease;
}

.forgotten-signing-btn:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.forgotten-request-modal {
    max-width: 680px;
}

.forgotten-request-header {
    padding-right: 48px;
    margin-bottom: 18px;
}

.forgotten-request-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 900;
    color: #111827;
}

.forgotten-request-header p {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 14px;
}

.forgotten-request-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}

.forgotten-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.forgotten-field.full {
    grid-column: 1 / -1;
}

.forgotten-field label {
    font-size: 13px;
    font-weight: 900;
    color: #374151;
}

.forgotten-control,
.forgotten-textarea {
    border: 1px solid #d1d5db;
    border-radius: 12px;
    padding: 10px 12px;
    outline: none;
    font-size: 14px;
}

.forgotten-textarea {
    min-height: 110px;
    resize: vertical;
}

.forgotten-request-warning {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    margin-top: 16px;
    padding: 12px 14px;
    border-radius: 14px;
    background: rgba(37, 99, 235, 0.08);
    color: #1d4ed8;
    font-size: 13px;
    font-weight: 700;
}

.forgotten-request-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.forgotten-primary-btn,
.forgotten-secondary-btn {
    border: 0;
    border-radius: 12px;
    padding: 11px 16px;
    font-weight: 900;
    cursor: pointer;
}

.forgotten-primary-btn {
    background: #111827;
    color: #ffffff;
}

.forgotten-primary-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.forgotten-secondary-btn {
    background: #f3f4f6;
    color: #374151;
}

.vacation-request-btn {
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid rgba(16, 185, 129, 0.35);
    background: rgba(16, 185, 129, 0.08);
    color: #047857;
    border-radius: 14px;
    padding: 11px 16px;
    font-weight: 900;
    cursor: pointer;
    transition: all 0.2s ease;
}

.vacation-request-btn:hover {
    background: rgba(16, 185, 129, 0.13);
    border-color: rgba(16, 185, 129, 0.55);
}

.vacation-request-modal {
    max-width: 680px;
}

.vacation-request-grid {
    grid-template-columns: repeat(2, 1fr);
}

.vacation-request-warning {
    background: rgba(16, 185, 129, 0.08);
    color: #047857;
}

.my-requests-btn {
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid rgba(7, 59, 120, 0.18);
    background: #f7f9fc;
    color: #073b78;
    border-radius: 14px;
    padding: 11px 16px;
    font-weight: 900;
    cursor: pointer;
    transition: all 0.2s ease;
}

.my-requests-btn:hover {
    background: #eef4fb;
    border-color: rgba(7, 59, 120, 0.32);
}

.my-requests-btn strong {
    min-width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: #dc2626;
    color: #ffffff;
    font-size: 12px;
}

.my-requests-modal {
    max-width: 760px;
}

.my-requests-loading,
.my-requests-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 28px;
    border-radius: 18px;
    background: #f9fafb;
    color: #6b7280;
    text-align: center;
    font-weight: 800;
}

.my-requests-empty h4 {
    margin: 8px 0 0;
    color: #111827;
    font-size: 17px;
    font-weight: 900;
}

.my-requests-empty p {
    margin: 0;
    font-size: 13px;
}

.my-requests-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 520px;
    overflow-y: auto;
    padding-right: 4px;
}

.my-request-card {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    background: #ffffff;
}

.my-request-main {
    display: flex;
    gap: 12px;
    min-width: 0;
}

.my-request-icon {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: rgba(7, 59, 120, 0.08);
    color: #073b78;
}

.my-request-card.vacation .my-request-icon {
    background: rgba(16, 185, 129, 0.1);
    color: #047857;
}

.my-request-main strong {
    display: block;
    color: #111827;
    font-size: 15px;
    font-weight: 900;
}

.my-request-main span {
    display: block;
    margin-top: 3px;
    color: #6b7280;
    font-size: 13px;
    font-weight: 800;
}

.my-request-main p {
    margin: 7px 0 0;
    color: #374151;
    font-size: 13px;
    line-height: 1.4;
}

.my-request-status {
    display: inline-flex;
    width: fit-content;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    white-space: nowrap;
}

.my-request-status.status-pending {
    background: rgba(245, 158, 11, 0.14);
    color: #92400e;
}

.my-request-status.status-approved {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
}

.my-request-status.status-rejected {
    background: rgba(239, 68, 68, 0.13);
    color: #b91c1c;
}

@media (max-width: 768px) {
    .vacation-request-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .forgotten-request-grid {
        grid-template-columns: 1fr;
    }

    .forgotten-request-actions {
        flex-direction: column-reverse;
    }

    .forgotten-primary-btn,
    .forgotten-secondary-btn {
        width: 100%;
    }
}

@media (max-width: 900px) {
    .monthly-summary-kpis {
        grid-template-columns: repeat(3, 1fr);
    }

    .monthly-summary-day {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .monthly-summary-filters,
    .monthly-summary-kpis {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 620px) {
    .filters-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .zoco-btn {
        width: 100%;
    }

    .zoco-table th,
    .zoco-table td {
        padding: 14px 16px;
    }

    .zoco-modal {
        padding: 26px 20px;
    }
}
</style>