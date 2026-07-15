<template>
    <section class="signings-tool-module">
        <header class="signings-tool-header">
            <div>
                <p class="module-kicker">Administración</p>
                <h2>Gestión de fichajes</h2>
                <p class="module-description">
                    Consulte, filtre, edite y genere informes de los registros horarios de los empleados.
                </p>
            </div>

            <button
                v-if="activeSigningTab === 'signings'"
                type="button"
                class="zoco-btn zoco-btn-primary"
                @click="abrirModalGenerarInforme"
            >
                <i class="fa-solid fa-file-export"></i>
                <span>Generar informe</span>
            </button>
        </header>

        <div class="signing-tabs">
            <button
                type="button"
                class="signing-tab"
                :class="{ active: activeSigningTab === 'signings' }"
                @click="activeSigningTab = 'signings'"
            >
                Fichajes
            </button>

            <button
                type="button"
                class="signing-tab"
                :class="{ active: activeSigningTab === 'work-calendar' }"
                @click="activeSigningTab = 'work-calendar'; fetchWorkCalendarEvents(); fetchVacationCalendarEvents();"
            >
                Calendario laboral
            </button>


            <button
                type="button"
                class="signing-tab"
                :class="{ active: activeSigningTab === 'monthly-summary' }"
                @click="activeSigningTab = 'monthly-summary'"
            >
                Resumen mensual
            </button>

            <button
                type="button"
                class="signing-tab"
                :class="{ active: activeSigningTab === 'forgotten-requests' }"
                @click="activeSigningTab = 'forgotten-requests'; fetchForgottenSigningRequests();"
            >
                Solicitudes
            </button>
        </div>

        <div
            v-if="activeSigningTab === 'signings'"
            class="zoco-card filters-card"
        >
            <div class="filters-header">
                <div>
                    <p class="section-kicker">Búsqueda</p>
                    <h3>Filtrar registros</h3>
                    <p>Seleccione un empleado y un periodo para consultar sus entradas y salidas.</p>
                </div>
            </div>

            <div class="filters-grid">
                <div class="form-field employee-field">
                    <label>Empleado</label>

                    <select
                        v-model="basicData.selectedUserId"
                        class="zoco-control"
                        @change="fetchUserSignings"
                    >
                        <option :value="null" disabled>
                            Seleccione empleado
                        </option>

                        <option
                            v-for="user in sortedUsers"
                            :key="user._id"
                            :value="user._id"
                        >
                            {{ user.firstName }} {{ user.lastName }}
                        </option>
                    </select>
                </div>

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
                        type="button"
                        class="zoco-btn zoco-btn-primary"
                        @click="
                            filtrarPorFechas(
                                basicData.selectedUserId,
                                selectedStartDate,
                                selectedEndDate
                            )
                        "
                        :disabled="!selectedStartDate || !selectedEndDate || isLoading"
                    >
                        <i class="fa-solid fa-filter"></i>
                        <span>Filtrar</span>
                    </button>

                    <button
                        type="button"
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

        <div
            v-if="activeSigningTab === 'signings'"
            class="zoco-card table-card"
        >
            <div class="table-header">
                <div>
                    <h3>Historial de fichajes</h3>
                    <p>
                        {{ safeSignings.length }}
                        {{ safeSignings.length === 1 ? "registro encontrado" : "registros encontrados" }}
                    </p>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="zoco-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora de entrada</th>
                            <th>Hora de salida</th>
                            <th class="actions-column">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-if="isLoading">
                            <td colspan="4">
                                <div class="loading-state">
                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                    <span>Cargando fichajes...</span>
                                </div>
                            </td>
                        </tr>

                        <tr v-else-if="safeSignings.length === 0">
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fa-regular fa-clock"></i>
                                    </div>

                                    <h4>No hay fichajes para mostrar</h4>
                                    <p>Seleccione un empleado o ajuste el rango de fechas.</p>
                                </div>
                            </td>
                        </tr>

                        <template v-else>
                            <tr
                                v-for="(signing, index) in safeSignings"
                                :key="signing._id || index"
                            >
                                <td>
                                    <span class="date-pill">
                                        {{ signing.date }}
                                    </span>
                                </td>

                                <td>
                                    <div class="time-cell">
                                        <span>{{ signing.entry || "--:--" }}</span>

                                        <button
                                            v-if="signing.entry_location"
                                            type="button"
                                            class="icon-btn"
                                            @click="abrirMapa(signing.entry_location)"
                                            title="Ver ubicación de entrada"
                                        >
                                            <i class="fa-solid fa-location-dot"></i>
                                        </button>
                                    </div>
                                </td>

                                <td>
                                    <div class="time-cell exit-cell">
                                        <div class="exit-info">
                                            <span>
                                                {{ signing.auto_closed ? "Sin salida registrada" : (signing.exit || "--:--") }}
                                            </span>

                                            <span
                                                v-if="signing.auto_closed"
                                                class="auto-closed-badge"
                                                title="Salida cerrada automáticamente porque no se fichó salida"
                                            >
                                                Cierre automático
                                            </span>
                                        </div>

                                        <button
                                            v-if="signing.exit_location && !signing.auto_closed"
                                            type="button"
                                            class="icon-btn"
                                            @click="abrirMapa(signing.exit_location)"
                                            title="Ver ubicación de salida"
                                        >
                                            <i class="fa-solid fa-location-dot"></i>
                                        </button>
                                    </div>
                                </td>

                                <td>
                                    <div class="row-actions">
                                        <button
                                            v-if="signing.entry"
                                            type="button"
                                            class="icon-btn edit"
                                            title="Editar fichaje"
                                            @click="editarFichaje(signing._id)"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="icon-btn history"
                                            title="Ver historial"
                                            @click="openAuditModal(signing)"
                                        >
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </button>

                                        <button
                                            v-if="signing.entry"
                                            type="button"
                                            class="icon-btn delete"
                                            title="Eliminar fichaje"
                                            @click="borrarFichaje(signing._id)"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div v-if="!isLoading && totalPages > 1" class="pagination-bar">
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

        <div
            v-if="activeSigningTab === 'work-calendar'"
            class="work-calendar-page"
        >
            <div class="vacation-calendar-hero zoco-card work-calendar-hero">
                <div>
                    <span class="section-kicker">Planificación laboral</span>
                    <h3>Calendario laboral</h3>
                    <p>
                        Visualice en un único calendario festivos, vacaciones, bajas, asuntos propios y ausencias.
                    </p>
                </div>

                <div class="vacation-calendar-actions">
                    <button
                        type="button"
                        class="zoco-btn zoco-btn-secondary"
                        @click="changeVacationCalendarMonth(-1)"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <div class="vacation-calendar-current">
                        {{ vacationCalendarTitle }}
                    </div>

                    <button
                        type="button"
                        class="zoco-btn zoco-btn-secondary"
                        @click="changeVacationCalendarMonth(1)"
                    >
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>

                    <button
                        type="button"
                        class="zoco-btn zoco-btn-primary"
                        @click="openWorkCalendarModal"
                    >
                        <i class="fa-solid fa-plus"></i>
                        <span>Añadir evento</span>
                    </button>
                </div>
            </div>

            <div class="vacation-calendar-filters zoco-card work-calendar-filters">
                <div class="form-field">
                    <label>Año</label>
                    <input
                        v-model="vacationCalendarFilters.year"
                        type="number"
                        class="zoco-control"
                        @change="fetchVacationCalendarEvents"
                    />
                </div>

                <div class="form-field">
                    <label>Mes</label>
                    <select
                        v-model="vacationCalendarFilters.month"
                        class="zoco-control"
                        @change="fetchVacationCalendarEvents"
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

                <div class="form-field">
                    <label>Tipo</label>
                    <select
                        v-model="vacationCalendarFilters.type"
                        class="zoco-control"
                        @change="fetchVacationCalendarEvents"
                    >
                        <option value="">Todos</option>
                        <option value="company_holiday">Festivos empresa</option>
                        <option value="vacation">Vacaciones</option>
                        <option value="medical_leave">Baja médica</option>
                        <option value="personal_day">Asunto propio</option>
                        <option value="justified_absence">Ausencia justificada</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Estado</label>
                    <select
                        v-model="vacationCalendarFilters.status"
                        class="zoco-control"
                        @change="fetchVacationCalendarEvents"
                    >
                        <option value="">Todos</option>
                        <option value="approved">Aprobados</option>
                        <option value="pending">Pendientes</option>
                        <option value="rejected">Rechazados</option>
                    </select>
                </div>

                <button
                    type="button"
                    class="zoco-btn zoco-btn-primary"
                    @click="fetchVacationCalendarEvents"
                >
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>Actualizar</span>
                </button>
            </div>

            <div class="vacation-calendar-kpis work-calendar-kpis">
                <div class="vacation-calendar-kpi zoco-card">
                    <span>Total eventos</span>
                    <strong>{{ vacationCalendarStats.total }}</strong>
                </div>

                <div class="vacation-calendar-kpi zoco-card holiday">
                    <span>Festivos</span>
                    <strong>{{ vacationCalendarStats.company_holidays }}</strong>
                </div>

                <div class="vacation-calendar-kpi zoco-card success">
                    <span>Vacaciones</span>
                    <strong>{{ vacationCalendarStats.vacations }}</strong>
                </div>

                <div class="vacation-calendar-kpi zoco-card absence">
                    <span>Ausencias / bajas</span>
                    <strong>{{ vacationCalendarStats.absences }}</strong>
                </div>

                <div class="vacation-calendar-kpi zoco-card warning">
                    <span>Pendientes</span>
                    <strong>{{ vacationCalendarStats.pending }}</strong>
                </div>
            </div>

            <div v-if="isLoadingVacationCalendar" class="zoco-card requests-loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Cargando calendario laboral...</span>
            </div>

            <div v-else class="vacation-calendar-card zoco-card work-calendar-month-card">
                <div class="vacation-calendar-weekdays">
                    <span
                        v-for="dayName in vacationCalendarWeekDays"
                        :key="dayName"
                    >
                        {{ dayName }}
                    </span>
                </div>

                <div class="vacation-calendar-grid">
                    <div
                        v-for="(day, dayIndex) in vacationCalendarDays"
                        :key="day.date || `empty-${dayIndex}`"
                        class="vacation-calendar-day work-calendar-day"
                        :class="{ empty: !day.isCurrentMonth, 'has-events': day.events && day.events.length }"
                    >
                        <div v-if="day.isCurrentMonth" class="vacation-calendar-day-number">
                            {{ day.day }}
                        </div>

                        <div
                            v-if="day.isCurrentMonth && day.events && day.events.length"
                            class="vacation-calendar-event-list"
                        >
                            <div
                                v-for="event in day.events"
                                :key="`${day.date}-${getWorkCalendarEventId(event)}`"
                                class="vacation-calendar-event work-calendar-month-event"
                                :class="[`status-${event.status || 'approved'}`, `type-${event.type || 'event'}`]"
                                :title="`${workCalendarTypeLabels[event.type] || event.type} · ${event.user_id ? getWorkCalendarEmployeeName(event.user_id) : 'Toda la empresa'} · ${formatWorkCalendarDate(event.start_date)} - ${formatWorkCalendarDate(event.end_date)}`"
                            >
                                <span class="calendar-event-type-mini">
                                    {{ workCalendarTypeLabels[event.type] || event.type }}
                                </span>

                                <strong>
                                    {{ event.user_id ? getWorkCalendarEmployeeName(event.user_id) : 'Toda la empresa' }}
                                </strong>

                                <span>{{ getWorkCalendarStatusLabel(event.status) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="zoco-card work-calendar-list-card">
                <div class="table-header">
                    <div>
                        <h3>Listado de eventos</h3>
                        <p>Edite o elimine festivos, vacaciones y ausencias del calendario laboral.</p>
                    </div>

                    <button
                        type="button"
                        class="zoco-btn zoco-btn-secondary"
                        @click="fetchWorkCalendarEvents(); fetchVacationCalendarEvents();"
                    >
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>Actualizar listado</span>
                    </button>
                </div>

                <div class="table-wrapper">
                    <table class="zoco-table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Título</th>
                                <th>Empleado</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Estado</th>
                                <th>Notas</th>
                                <th class="actions-column">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-if="workCalendarEvents.length === 0">
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="fa-regular fa-calendar"></i>
                                        </div>

                                        <h4>No hay eventos registrados</h4>
                                        <p>Añada festivos de empresa, vacaciones o ausencias.</p>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="event in workCalendarEvents"
                                :key="getWorkCalendarEventId(event)"
                                v-else
                            >
                                <td>
                                    <span
                                        class="calendar-type-badge"
                                        :class="`type-${event.type || 'event'}`"
                                    >
                                        {{ workCalendarTypeLabels[event.type] || event.type }}
                                    </span>
                                </td>

                                <td>{{ event.title }}</td>

                                <td>
                                    {{ event.user_id ? getWorkCalendarEmployeeName(event.user_id) : "Toda la empresa" }}
                                </td>

                                <td>{{ formatWorkCalendarDate(event.start_date) }}</td>
                                <td>{{ formatWorkCalendarDate(event.end_date) }}</td>

                                <td>
                                    <span
                                        class="calendar-status-badge"
                                        :class="`status-${event.status || 'approved'}`"
                                    >
                                        {{ getWorkCalendarStatusLabel(event.status) }}
                                    </span>
                                </td>

                                <td>{{ event.notes || "-" }}</td>

                                <td>
                                    <div class="row-actions">
                                        <button
                                            type="button"
                                            class="icon-btn edit"
                                            title="Editar evento"
                                            @click="editWorkCalendarEvent(event)"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="icon-btn delete"
                                            title="Eliminar evento"
                                            @click="deleteWorkCalendarEvent(event)"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="zoco-modal-backdrop" v-if="isAuditModalOpen">
            <div class="zoco-modal audit-modal">
                <button
                    type="button"
                    class="modal-close"
                    @click="closeAuditModal"
                    title="Cerrar"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="modal-header">
                    <span class="modal-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </span>

                    <div>
                        <h2>Historial del fichaje</h2>
                        <p>Consulte los cambios realizados manualmente sobre este fichaje.</p>
                    </div>
                </div>

                <div v-if="isLoadingAuditLogs" class="audit-loading">
                    Cargando historial...
                </div>

                <div v-else-if="auditLogs.length === 0" class="audit-empty">
                    Este fichaje todavía no tiene cambios registrados.
                </div>

                <div v-else class="audit-list">
                    <div
                        v-for="log in auditLogs"
                        :key="log._id || log.created_at"
                        class="audit-item"
                    >
                        <div class="audit-item-header">
                            <strong>{{ formatAuditDate(log.edited_at || log.created_at) }}</strong>
                            <span>Editado por: {{ log.edited_by || "Sistema/Admin" }}</span>
                        </div>

                        <div v-if="log.reason" class="audit-reason">
                            <strong>Motivo:</strong> {{ log.reason }}
                        </div>

                        <div class="audit-changes">
                            <div
                                v-for="(change, field) in log.changes"
                                :key="field"
                                class="audit-change-row"
                            >
                                <div class="audit-field">
                                    {{ getAuditFieldLabel(field) }}
                                </div>

                                <div class="audit-values">
                                    <span class="old-value">
                                        {{ formatAuditValue(change.old) }}
                                    </span>

                                    <i class="fa-solid fa-arrow-right"></i>

                                    <span class="new-value">
                                        {{ formatAuditValue(change.new) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button
                        type="button"
                        class="zoco-btn zoco-btn-secondary"
                        @click="closeAuditModal"
                    >
                        <i class="fa-solid fa-xmark"></i>
                        <span>Cerrar</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="zoco-modal-backdrop" v-if="isWorkCalendarModalOpen">
            <div class="zoco-modal work-calendar-modal">
                <button
                    type="button"
                    class="modal-close"
                    @click="closeWorkCalendarModal"
                    title="Cerrar"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="modal-header">
                    <span class="modal-icon">
                        <i class="fa-regular fa-calendar"></i>
                    </span>

                    <div>
                        <h2>
                            {{ editingWorkCalendarEventId ? "Editar evento" : "Añadir evento" }}
                        </h2>
                        <p>Registre festivos de empresa, vacaciones o ausencias.</p>
                    </div>
                </div>

                <div class="modal-grid">
                    <div class="form-field">
                        <label>Tipo</label>

                        <select
                            v-model="workCalendarForm.type"
                            class="zoco-control"
                        >
                            <option value="company_holiday">Festivo empresa</option>
                            <option value="vacation">Vacaciones</option>
                            <option value="medical_leave">Baja médica</option>
                            <option value="personal_day">Asunto propio</option>
                            <option value="justified_absence">Ausencia justificada</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Título</label>

                        <input
                            type="text"
                            v-model="workCalendarForm.title"
                            class="zoco-control"
                            placeholder="Ej: Día de Andalucía"
                        />
                    </div>

                    <div
                        v-if="workCalendarForm.type !== 'company_holiday'"
                        class="form-field"
                    >
                        <label>Empleado</label>

                        <select
                            v-model="workCalendarForm.user_id"
                            class="zoco-control"
                        >
                            <option value="">Seleccione empleado</option>

                            <option
                                v-for="user in sortedUsers"
                                :key="user._id"
                                :value="user._id"
                            >
                                {{ user.firstName }} {{ user.lastName }}
                            </option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Fecha inicio</label>

                        <div class="control-with-icon">
                            <input
                                type="date"
                                v-model="workCalendarForm.start_date"
                                class="zoco-control"
                            />
                            <i class="fa-regular fa-calendar"></i>
                        </div>
                    </div>

                    <div class="form-field">
                        <label>Fecha fin</label>

                        <div class="control-with-icon">
                            <input
                                type="date"
                                v-model="workCalendarForm.end_date"
                                class="zoco-control"
                                :min="workCalendarForm.start_date"
                            />
                            <i class="fa-regular fa-calendar"></i>
                        </div>
                    </div>

                    <div class="form-field">
                        <label>Estado</label>

                        <select
                            v-model="workCalendarForm.status"
                            class="zoco-control"
                        >
                            <option value="approved">Aprobado</option>
                            <option value="pending">Pendiente</option>
                            <option value="rejected">Rechazado</option>
                        </select>
                    </div>

                    <div class="form-field full">
                        <label>Notas</label>

                        <textarea
                            v-model="workCalendarForm.notes"
                            class="zoco-textarea"
                            placeholder="Añada observaciones internas..."
                        ></textarea>
                    </div>
                </div>

                <div class="modal-actions">
                    <button
                        type="button"
                        class="zoco-btn zoco-btn-secondary"
                        @click="closeWorkCalendarModal"
                    >
                        <i class="fa-solid fa-xmark"></i>
                        <span>Cancelar</span>
                    </button>

                    <button
                        type="button"
                        class="zoco-btn zoco-btn-primary"
                        @click="saveWorkCalendarEvent"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Guardar</span>
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="activeSigningTab === 'monthly-summary'"
            class="zoco-card monthly-summary-card"
        >
            <div class="table-header">
                <div>
                    <h3>Resumen mensual por empleado</h3>
                    <p>Consulte días trabajados, horas, vacaciones, festivos e incidencias.</p>
                </div>
            </div>

            <div class="summary-filters">
                <div class="form-field">
                    <th>Empleado</th>

                    <select
                        v-model="monthlySummaryFilters.user_id"
                        class="zoco-control"
                    >
                        <option value="">Seleccione empleado</option>

                        <option
                            v-for="user in sortedUsers"
                            :key="user._id"
                            :value="user._id"
                        >
                            {{ user.firstName }} {{ user.lastName }}
                        </option>
                    </select>
                </div>

                <div class="form-field">
                    <th>Año</th>

                    <input
                        v-model="monthlySummaryFilters.year"
                        type="number"
                        class="zoco-control"
                    />
                </div>

                <div class="form-field">
                    <th>Mes</th>

                    <select
                        v-model="monthlySummaryFilters.month"
                        class="zoco-control"
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

                <div class="form-field summary-filter-action">
                    <label>&nbsp;</label>

                    <button
                        type="button"
                        class="zoco-btn zoco-btn-primary"
                        @click="fetchMonthlySummary"
                    >
                        <i class="fa-solid fa-chart-simple"></i>
                        <span>Consultar</span>
                    </button>
                </div>
            </div>

            <div v-if="isLoadingMonthlySummary" class="summary-empty">
                Cargando resumen mensual...
            </div>

            <div v-else-if="!monthlySummary" class="summary-empty">
                Seleccione un empleado, año y mes para consultar el resumen.
            </div>

            <div v-else>
                <div class="summary-kpis">
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

                <div class="table-wrapper monthly-summary-table-wrapper">
                    <table class="zoco-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Día</th>
                                <th>Estado</th>
                                <th>Fichajes</th>
                                <th>Horas</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="day in monthlySummary.days"
                                :key="day.date"
                            >
                                <td>{{ formatWorkCalendarDate(day.date) }}</td>
                                <td>{{ capitalizeText(day.day_name) }}</td>
                                <td>
                                    <span
                                        class="day-status-badge"
                                        :class="`status-${day.status}`"
                                    >
                                        {{ getMonthlyDayStatusLabel(day.status) }}
                                    </span>
                                </td>
                                <td>
                                    <div
                                        v-if="day.signins && day.signins.length"
                                        class="day-signins-list"
                                    >
                                        <span
                                            v-for="signin in day.signins"
                                            :key="signin._id || `${day.date}-${signin.entry}`"
                                        >
                                            {{ signin.entry || "--:--" }} - {{ signin.exit || "--:--" }}
                                        </span>
                                    </div>

                                    <span v-else>-</span>
                                </td>
                                <td>{{ day.hours }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            v-if="activeSigningTab === 'forgotten-requests'"
            class="requests-page"
        >
            <div class="requests-hero zoco-card">
                <div class="requests-hero-content">
                    <span class="section-kicker">Gestión de solicitudes</span>
                    <h3>Solicitudes de empleados</h3>
                    <p>
                        Revise en una misma pantalla los fichajes olvidados y las vacaciones solicitadas.
                    </p>
                </div>

                <div class="requests-hero-actions">
                    <select
                        v-model="forgottenRequestsStatusFilter"
                        class="zoco-control requests-filter"
                        @change="fetchForgottenSigningRequests"
                    >
                        <option value="pending">Pendientes</option>
                        <option value="approved">Aprobadas</option>
                        <option value="rejected">Rechazadas</option>
                        <option value="">Todas</option>
                    </select>

                    <button
                        type="button"
                        class="zoco-btn zoco-btn-secondary"
                        @click="fetchForgottenSigningRequests"
                    >
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>Actualizar</span>
                    </button>
                </div>
            </div>

            <div v-if="isLoadingForgottenRequests" class="zoco-card requests-loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Cargando solicitudes...</span>
            </div>

            <template v-else>
                <div class="requests-kpis">
                    <div class="request-kpi zoco-card">
                        <span>Total solicitudes</span>
                        <strong>{{ forgottenSigningRequests.length + vacationRequests.length }}</strong>
                    </div>

                    <div class="request-kpi zoco-card warning">
                        <span>Pendientes</span>
                        <strong>
                            {{
                                forgottenSigningRequests.filter((request) => (request.status || 'pending') === 'pending').length
                                + vacationRequests.filter((request) => (request.status || 'pending') === 'pending').length
                            }}
                        </strong>
                    </div>

                    <div class="request-kpi zoco-card">
                        <span>Fichajes olvidados</span>
                        <strong>{{ forgottenSigningRequests.length }}</strong>
                    </div>

                    <div class="request-kpi zoco-card success">
                        <span>Vacaciones</span>
                        <strong>{{ vacationRequests.length }}</strong>
                    </div>
                </div>

                <div class="requests-grid">
                    <div class="request-panel zoco-card">
                        <div class="request-panel-header">
                            <div class="request-panel-icon signing-icon">
                                <i class="fa-regular fa-clock"></i>
                            </div>

                            <div>
                                <h4>Fichajes olvidados</h4>
                                <p>Solicitudes para crear un registro horario atrasado.</p>
                            </div>
                        </div>

                        <div v-if="forgottenSigningRequests.length === 0" class="compact-empty-state">
                            <div class="empty-icon">
                                <i class="fa-regular fa-clock"></i>
                            </div>

                            <h4>No hay fichajes olvidados</h4>
                            <p>No hay solicitudes para el filtro seleccionado.</p>
                        </div>

                        <div v-else class="request-list">
                            <article
                                v-for="request in forgottenSigningRequests"
                                :key="getForgottenRequestId(request)"
                                class="request-card"
                            >
                                <div class="request-card-main">
                                    <div>
                                        <strong>{{ getForgottenRequestEmployeeName(request.user_id) }}</strong>
                                        <span>
                                            {{ formatWorkCalendarDate(request.date) }}
                                            · {{ request.entry || "--:--" }} - {{ request.exit || "--:--" }}
                                        </span>
                                    </div>

                                    <span
                                        class="forgotten-status-badge"
                                        :class="`status-${request.status || 'pending'}`"
                                    >
                                        {{ getForgottenRequestStatusLabel(request.status) }}
                                    </span>
                                </div>

                                <p class="request-card-notes">
                                    {{ request.reason || "Sin motivo indicado" }}
                                </p>

                                <div
                                    v-if="request.has_existing_signins"
                                    class="existing-signins-warning"
                                >
                                    Ya existen fichajes ese día
                                </div>

                                <div class="request-card-footer">
                                    <span>Solicitada: {{ formatAuditDate(request.created_at) }}</span>

                                    <div class="row-actions">
                                        <button
                                            v-if="request.status === 'pending'"
                                            type="button"
                                            class="icon-btn approve"
                                            title="Aprobar solicitud"
                                            @click="approveForgottenSigningRequest(request)"
                                        >
                                            <i class="fa-solid fa-check"></i>
                                        </button>

                                        <button
                                            v-if="request.status === 'pending'"
                                            type="button"
                                            class="icon-btn delete"
                                            title="Rechazar solicitud"
                                            @click="openRejectForgottenRequestModal(request)"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>

                                        <span
                                            v-if="request.status !== 'pending'"
                                            class="reviewed-info"
                                        >
                                            Revisada
                                        </span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="request-panel zoco-card">
                        <div class="request-panel-header">
                            <div class="request-panel-icon vacation-icon">
                                <i class="fa-regular fa-calendar-check"></i>
                            </div>

                            <div>
                                <h4>Vacaciones</h4>
                                <p>Solicitudes enviadas por los empleados desde Daily Signings.</p>
                            </div>
                        </div>

                        <div v-if="vacationRequests.length === 0" class="compact-empty-state">
                            <div class="empty-icon">
                                <i class="fa-regular fa-calendar-check"></i>
                            </div>

                            <h4>No hay vacaciones solicitadas</h4>
                            <p>No hay vacaciones para el filtro seleccionado.</p>
                        </div>

                        <div v-else class="request-list">
                            <article
                                v-for="request in vacationRequests"
                                :key="getWorkCalendarEventId(request)"
                                class="request-card vacation-card"
                            >
                                <div class="request-card-main">
                                    <div>
                                        <strong>{{ getForgottenRequestEmployeeName(request.user_id) }}</strong>
                                        <span>
                                            {{ formatWorkCalendarDate(request.start_date) }}
                                            → {{ formatWorkCalendarDate(request.end_date) }}
                                        </span>
                                    </div>

                                    <span
                                        class="forgotten-status-badge"
                                        :class="`status-${request.status || 'pending'}`"
                                    >
                                        {{ getForgottenRequestStatusLabel(request.status) }}
                                    </span>
                                </div>

                                <p class="request-card-notes">
                                    {{ request.notes || "Sin notas" }}
                                </p>

                                <div class="request-card-footer">
                                    <span>Solicitada: {{ formatAuditDate(request.created_at) }}</span>

                                    <div class="row-actions">
                                        <button
                                            v-if="request.status === 'pending'"
                                            type="button"
                                            class="icon-btn approve"
                                            title="Aprobar vacaciones"
                                            @click="approveVacationRequest(request)"
                                        >
                                            <i class="fa-solid fa-check"></i>
                                        </button>

                                        <button
                                            v-if="request.status === 'pending'"
                                            type="button"
                                            class="icon-btn delete"
                                            title="Rechazar vacaciones"
                                            @click="rejectVacationRequest(request)"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>

                                        <span
                                            v-if="request.status !== 'pending'"
                                            class="reviewed-info"
                                        >
                                            Revisada
                                        </span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="zoco-modal-backdrop" v-if="isRejectForgottenRequestModalOpen">
            <div class="zoco-modal reject-request-modal">
                <button
                    type="button"
                    class="modal-close"
                    @click="closeRejectForgottenRequestModal"
                    title="Cerrar"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="modal-header">
                    <span class="modal-icon danger-icon">
                        <i class="fa-solid fa-ban"></i>
                    </span>

                    <div>
                        <h2>Rechazar solicitud</h2>
                        <p>Indique el motivo por el que se rechaza la solicitud.</p>
                    </div>
                </div>

                <div class="form-field full">
                    <label>Motivo de rechazo</label>

                    <textarea
                        v-model="rejectForgottenRequestForm.rejection_reason"
                        class="zoco-textarea"
                        placeholder="Ej: ya existe un fichaje válido para ese día"
                    ></textarea>
                </div>

                <div class="modal-actions">
                    <button
                        type="button"
                        class="zoco-btn zoco-btn-secondary"
                        @click="closeRejectForgottenRequestModal"
                    >
                        <i class="fa-solid fa-xmark"></i>
                        <span>Cancelar</span>
                    </button>

                    <button
                        type="button"
                        class="zoco-btn zoco-btn-primary"
                        @click="rejectForgottenSigningRequest"
                    >
                        <i class="fa-solid fa-ban"></i>
                        <span>Rechazar</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="zoco-modal-backdrop" v-if="isModalOpen">
            <div class="zoco-modal">
                <button
                    type="button"
                    class="modal-close"
                    @click="isModalOpen = false"
                    title="Cerrar"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="modal-header">
                    <span class="modal-icon">
                        <i class="fa-solid fa-file-lines"></i>
                    </span>

                    <div>
                        <h2>Generar informe de fichajes</h2>
                        <p>Seleccione fechas y empleados para exportar el informe.</p>
                    </div>
                </div>

                <div class="modal-grid">
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
                </div>

                <div class="employee-selector">
                    <label>Empleados</label>

                    <div
                        class="employee-dropdown"
                        @click="seeFilters('employee')"
                        :class="{ active: isSeeingFiltersPc.employee }"
                        v-if="basicData.userList && basicData.userList.length > 0"
                    >
                        <div class="employee-dropdown-title">
                            <span>{{ getEmployeeFilterTitle }}</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div
                            v-if="isSeeingFiltersPc.employee"
                            class="employee-dropdown-menu"
                            @click.stop
                        >
                            <div class="employee-search">
                                <i class="fa-solid fa-magnifying-glass"></i>

                                <input
                                    v-model="searchFilters.employee"
                                    type="text"
                                    placeholder="Buscar empleado..."
                                />
                            </div>

                            <div class="employee-option" @click="toggleAllEmployees">
                                <span
                                    class="zoco-checkbox"
                                    :class="{ selected: areAllEmployeesActive }"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </span>

                                <span>Todos</span>
                            </div>

                            <div
                                v-for="user in filteredUsers"
                                :key="user._id"
                                class="employee-option"
                                @click="toggleEmployee(user)"
                            >
                                <span
                                    class="zoco-checkbox"
                                    :class="{ selected: user.active }"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </span>

                                <span>
                                    {{ user.firstName }} {{ user.lastName }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="no-employees">
                        No hay empleados disponibles.
                    </div>
                </div>

                <div class="modal-actions">
                    <button
                        type="button"
                        class="zoco-btn zoco-btn-secondary"
                        @click="generateExcel"
                    >
                        <i class="fa-solid fa-file-excel"></i>
                        <span>Excel</span>
                    </button>

                    <button
                        type="button"
                        class="zoco-btn zoco-btn-primary"
                        @click="generatePdfReport"
                    >
                        <i class="fa-solid fa-file-pdf"></i>
                        <span>PDF</span>
                    </button>
                </div>
            </div>
        </div>

                <div class="zoco-modal-backdrop" v-if="isEditModalOpen">
                    <div class="zoco-modal edit-signing-modal">
                        <button
                            type="button"
                            class="modal-close"
                            @click="closeEditSigningModal"
                            title="Cerrar"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <div class="modal-header">
                            <span class="modal-icon">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </span>

                            <div>
                                <h2>Editar fichaje</h2>
                                <p>Modifique la fecha, las horas, las notas y los tramos horarios.</p>
                            </div>
                        </div>

                        <div class="edit-form-grid">
                            <div class="form-field">
                                <label>Fecha</label>
                                <div class="control-with-icon">
                                    <input
                                        type="date"
                                        v-model="editSigningForm.date"
                                        class="zoco-control"
                                    />
                                    <i class="fa-regular fa-calendar"></i>
                                </div>
                            </div>

                            <div class="form-field">
                                <label>Hora de entrada</label>
                                <div class="control-with-icon">
                                    <input
                                        type="time"
                                        v-model="editSigningForm.entry"
                                        class="zoco-control"
                                    />
                                    <i class="fa-regular fa-clock"></i>
                                </div>
                            </div>

                            <div class="form-field">
                                <label>Hora de salida</label>
                                <div class="control-with-icon">
                                    <input
                                        type="time"
                                        v-model="editSigningForm.exit"
                                        class="zoco-control"
                                    />
                                    <i class="fa-regular fa-clock"></i>
                                </div>
                            </div>

                            <div class="form-field full">
                                <label>Notas</label>
                                <textarea
                                    v-model="editSigningForm.notes"
                                    class="zoco-textarea"
                                    placeholder="Añada observaciones sobre este fichaje..."
                                ></textarea>
                            </div>
                        </div>

                        <div class="form-field full">
                            <label>Motivo de edición</label>

                            <textarea
                                v-model="editSigningForm.audit_reason"
                                class="zoco-textarea"
                                placeholder="Ej: el empleado olvidó fichar salida"
                            ></textarea>
                        </div>

                        <div class="edit-sections-block">
                            <div class="edit-sections-header">
                                <div>
                                    <h3>Tramos horarios</h3>
                                    <p>Puede añadir actividades asociadas a intervalos de tiempo.</p>
                                </div>

                                <button
                                    type="button"
                                    class="zoco-btn zoco-btn-secondary"
                                    @click="addEditTramo"
                                >
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Añadir tramo</span>
                                </button>
                            </div>

                            <div
                                v-if="editSigningForm.activity_sections.length === 0"
                                class="edit-empty-sections"
                            >
                                No hay tramos horarios definidos.
                            </div>

                            <div
                                v-for="(tramo, index) in editSigningForm.activity_sections"
                                :key="index"
                                class="edit-tramo-row"
                            >
                                <div class="form-field">
                                    <label>Inicio</label>
                                    <input
                                        type="time"
                                        v-model="tramo.start"
                                        class="zoco-control"
                                    />
                                </div>

                                <div class="form-field">
                                    <label>Fin</label>
                                    <input
                                        type="time"
                                        v-model="tramo.end"
                                        class="zoco-control"
                                    />
                                </div>

                                <div class="form-field tramo-description">
                                    <label>Actividad</label>
                                    <input
                                        type="text"
                                        v-model="tramo.description"
                                        class="zoco-control"
                                        placeholder="Descripción"
                                    />
                                </div>

                                <button
                                    type="button"
                                    class="icon-btn delete tramo-delete"
                                    @click="removeEditTramo(index)"
                                    title="Eliminar tramo"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button
                                type="button"
                                class="zoco-btn zoco-btn-secondary"
                                @click="closeEditSigningModal"
                            >
                                <i class="fa-solid fa-xmark"></i>
                                <span>Cancelar</span>
                            </button>

                            <button
                                type="button"
                                class="zoco-btn zoco-btn-primary"
                                @click="saveEditedSigning"
                            >
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Guardar cambios</span>
                            </button>
                        </div>
                    </div>
                </div>

    </section>
</template>

<script>
import Swal from "sweetalert2";

export default {
    props: ["basicData"],
    name: "SigningsToolComponent",

    data() {
        return {
            activeSigningTab: "signings",
            workCalendarEvents: [],
            isWorkCalendarModalOpen: false,
            editingWorkCalendarEventId: null,
            monthlySummary: null,
            isLoadingMonthlySummary: false,
            monthlySummaryFilters: {
                user_id: this.basicData.selectedUserId || "",
                year: new Date().getFullYear(),
                month: new Date().getMonth() + 1,
            },
            workCalendarForm: {
                type: "company_holiday",
                title: "",
                user_id: "",
                start_date: "",
                end_date: "",
                notes: "",
                status: "approved",
            },
            forgottenSigningRequests: [],
            isLoadingForgottenRequests: false,
            forgottenRequestsStatusFilter: "pending",
            vacationRequests: [],
            vacationCalendarEvents: [],
            isLoadingVacationCalendar: false,
            vacationCalendarFilters: {
                year: new Date().getFullYear(),
                month: new Date().getMonth() + 1,
                type: "",
                status: "",
            },
            vacationCalendarWeekDays: ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"],
            isRejectForgottenRequestModalOpen: false,
            selectedForgottenRequest: null,
            rejectForgottenRequestForm: {
                rejection_reason: "",
            },
            workCalendarTypeLabels: {
                company_holiday: "Festivo empresa",
                vacation: "Vacaciones",
                medical_leave: "Baja médica",
                personal_day: "Asunto propio",
                justified_absence: "Ausencia justificada",
            },
            selectedUserId: this.basicData.selectedUserId || null,
            selectedStartDate: "",
            selectedEndDate: "",
            isLoading: false,
            currentPage: 1,
            totalPages: 1,
            perPage: 10,
            isModalOpen: false,
            isEditModalOpen: false,
            editingSigningId: null,
            isAuditModalOpen: false,
            auditLogs: [],
            isLoadingAuditLogs: false,
            selectedAuditSigning: null,
            editSigningForm: {
                date: "",
                entry: "",
                exit: "",
                notes: "",
                activity_sections: [],
                audit_reason: "",
            },
            searchFilters: {
                employee: "",
            },
            isSeeingFiltersPc: {
                employee: false,
            },
        };
    },

    computed: {
        sortedUsers() {
            return [...this.safeUsers].sort((a, b) => {
                const nameA = `${a.firstName || ""} ${a.lastName || ""}`.toLowerCase();
                const nameB = `${b.firstName || ""} ${b.lastName || ""}`.toLowerCase();

                return nameA.localeCompare(nameB);
            });
        },

        vacationCalendarTitle() {
            return `${this.getMonthName(this.vacationCalendarFilters.month)} ${this.vacationCalendarFilters.year}`;
        },

        vacationCalendarDays() {
            const year = Number(this.vacationCalendarFilters.year);
            const month = Number(this.vacationCalendarFilters.month);
            const firstDay = new Date(year, month - 1, 1);
            const lastDay = new Date(year, month, 0);
            const days = [];
            const firstWeekDay = firstDay.getDay() === 0 ? 7 : firstDay.getDay();

            for (let i = 1; i < firstWeekDay; i++) {
                days.push({ date: null, day: "", isCurrentMonth: false });
            }

            for (let day = 1; day <= lastDay.getDate(); day++) {
                const date = this.formatDateForInput(new Date(year, month - 1, day));
                days.push({
                    date,
                    day,
                    isCurrentMonth: true,
                    events: this.getVacationEventsForDay(date),
                });
            }

            while (days.length % 7 !== 0) {
                days.push({ date: null, day: "", isCurrentMonth: false });
            }

            return days;
        },

        vacationCalendarStats() {
            const approved = this.vacationCalendarEvents.filter((event) => (event.status || "approved") === "approved").length;
            const pending = this.vacationCalendarEvents.filter((event) => (event.status || "approved") === "pending").length;
            const rejected = this.vacationCalendarEvents.filter((event) => (event.status || "approved") === "rejected").length;
            const companyHolidays = this.vacationCalendarEvents.filter((event) => event.type === "company_holiday").length;
            const vacations = this.vacationCalendarEvents.filter((event) => event.type === "vacation").length;
            const absences = this.vacationCalendarEvents.filter((event) => {
                return ["medical_leave", "personal_day", "justified_absence"].includes(event.type);
            }).length;
            const employees = new Set(this.vacationCalendarEvents.map((event) => event.user_id).filter(Boolean));

            return {
                total: this.vacationCalendarEvents.length,
                approved,
                pending,
                rejected,
                company_holidays: companyHolidays,
                vacations,
                absences,
                employees: employees.size,
            };
        },

        selectedEmployees() {
            return this.basicData.userList.filter((u) => u.active);
        },

        safeUsers() {
            return Array.isArray(this.basicData?.userList)
                ? this.basicData.userList
                : [];
        },

        safeSignings() {
            return Array.isArray(this.basicData?.signings)
                ? this.basicData.signings
                : [];
        },

        // 🔹 Filtrar según búsqueda
        filteredUsers() {
            const search = this.searchFilters.employee?.toLowerCase() || "";
            return this.sortedUsers
                .map((u) => ({ ...u, active: u.active ?? false }))
                .filter(
                    (u) =>
                        u.firstName?.toLowerCase().includes(search) ||
                        u.lastName?.toLowerCase().includes(search)
                );
        },

        // 🔍 Filtra empleados según el texto del buscador
        filteredEmployees() {
            const search = this.searchFilters.employee?.toLowerCase() || "";
            return this.basicData.userList
                .map((u) => ({
                    ...u,
                    active: u.active ?? false, // asegurar que exista la propiedad
                }))
                .filter((u) => u.name?.toLowerCase().includes(search));
        },

        // 🏷️ Texto mostrado en el botón principal
        getEmployeeFilterTitle() {
            const selected = this.basicData.userList.filter((e) => e.active);
            if (selected.length === 0) return "Selecciona empleados";
            if (selected.length === this.basicData.userList.length)
                return "Todos los empleados";
            if (selected.length === 1)
                return selected[0].firstName + " " + selected[0].lastName;
            return `${selected.length} empleados seleccionados`;
        },

        // ✅ Saber si todos los empleados están seleccionados
        areAllEmployeesActive() {
            return (
                this.basicData.userList.length > 0 &&
                this.basicData.userList.every((e) => e.active)
            );
        },
    },

    methods: {
        async fetchUserSignings() {
            const userId = this.basicData.selectedUserId;
            if (!userId) {
                this.basicData.signings = [];
                return;
            }

            try {
                console.log("📡 Cargando fichajes de:", userId);
                const response = await axios.get(`/api/signin/user/${userId}`, {
                    params: {
                        page: this.currentPage,
                        per_page: this.perPage,
                    },
                });

                const currentPage = response.data.current_page || 1;
                const lastPage = response.data.last_page || 1;

                this.currentPage = currentPage;
                this.totalPages = lastPage;

                this.basicData.signings = Array.isArray(response.data.data)
                    ? response.data.data
                    : [];

                console.log("✅ Fichajes obtenidos:", this.basicData.signings);
            } catch (error) {
                console.error("❌ Error al obtener fichajes:", error);
                this.basicData.signings = [];
            }
        },

        async approveVacationRequest(request) {
            const requestId = this.getWorkCalendarEventId(request);

            const result = await Swal.fire({
                icon: "warning",
                title: "¿Aprobar vacaciones?",
                html:
                    `Se aprobarán las vacaciones de <strong>${this.getForgottenRequestEmployeeName(request.user_id)}</strong><br>` +
                    `Desde: <strong>${this.formatWorkCalendarDate(request.start_date)}</strong><br>` +
                    `Hasta: <strong>${this.formatWorkCalendarDate(request.end_date)}</strong>`,
                showCancelButton: true,
                confirmButtonText: "Sí, aprobar",
                cancelButtonText: "Cancelar",
            });

            if (!result.isConfirmed) return;

            try {
                await axios.put(`/api/signin/work-calendar/events/${requestId}`, {
                    type: "vacation",
                    title: request.title || "Vacaciones",
                    user_id: request.user_id,
                    start_date: request.start_date,
                    end_date: request.end_date,
                    notes: request.notes || "",
                    status: "approved",
                });

                await this.fetchForgottenSigningRequests();
                await this.fetchVacationCalendarEvents();

                Swal.fire(
                    "Aprobadas",
                    "Las vacaciones se han aprobado correctamente.",
                    "success"
                );
            } catch (error) {
                console.error("Error al aprobar vacaciones:", error);

                Swal.fire(
                    "Error",
                    error.response?.data?.message || "No se pudieron aprobar las vacaciones.",
                    "error"
                );
            }
        },

        async rejectVacationRequest(request) {
            const requestId = this.getWorkCalendarEventId(request);

            const result = await Swal.fire({
                icon: "warning",
                title: "Rechazar vacaciones",
                input: "textarea",
                inputLabel: "Motivo de rechazo",
                inputPlaceholder: "Ej: no es posible por carga de trabajo",
                inputValidator: (value) => {
                    if (!value || value.trim().length < 3) {
                        return "Debe indicar un motivo de rechazo.";
                    }

                    return null;
                },
                showCancelButton: true,
                confirmButtonText: "Rechazar",
                cancelButtonText: "Cancelar",
            });

            if (!result.isConfirmed) return;

            const rejectionReason = result.value;

            try {
                const previousNotes = request.notes ? `${request.notes}\n\n` : "";

                await axios.put(`/api/signin/work-calendar/events/${requestId}`, {
                    type: "vacation",
                    title: request.title || "Vacaciones",
                    user_id: request.user_id,
                    start_date: request.start_date,
                    end_date: request.end_date,
                    notes: `${previousNotes}Motivo de rechazo: ${rejectionReason}`,
                    status: "rejected",
                });

                await this.fetchForgottenSigningRequests();
                await this.fetchVacationCalendarEvents();

                Swal.fire(
                    "Rechazadas",
                    "La solicitud de vacaciones se ha rechazado correctamente.",
                    "success"
                );
            } catch (error) {
                console.error("Error al rechazar vacaciones:", error);

                Swal.fire(
                    "Error",
                    error.response?.data?.message || "No se pudieron rechazar las vacaciones.",
                    "error"
                );
            }
        },

        generatePdfReport() {
            const selectedEmployeeIds = this.selectedEmployees.map(
                (e) => e._id
            );
            if (selectedEmployeeIds.length === 0) {
                Swal.fire(
                    "Atención",
                    "Por favor, selecciona al menos un empleado para generar el informe.",
                    "warning"
                );
                return;
            }

            if (!this.selectedStartDate) {
                Swal.fire(
                    "Atención",
                    "Por favor, selecciona una fecha de inicio.",
                    "warning"
                );
                return;
            }

            const params = new URLSearchParams();
            params.append("start_date", this.selectedStartDate);
            params.append("end_date", this.selectedEndDate);
            selectedEmployeeIds.forEach((id) =>
                params.append("employee_ids[]", id)
            );

            const url = `/api/signin/report/pdf?${params.toString()}`;
            window.open(url, "_blank");
        },

        generateExcel() {
            const selectedEmployeeIds = this.selectedEmployees.map(
                (e) => e._id
            );
            if (selectedEmployeeIds.length === 0) {
                Swal.fire(
                    "Atención",
                    "Por favor, selecciona al menos un empleado para generar el informe.",
                    "warning"
                );
                return;
            }

            if (!this.selectedStartDate) {
                Swal.fire(
                    "Atención",
                    "Por favor, selecciona una fecha de inicio.",
                    "warning"
                );
                return;
            }

            const params = new URLSearchParams();
            params.append("start_date", this.selectedStartDate);
            params.append("end_date", this.selectedEndDate);
            selectedEmployeeIds.forEach((id) =>
                params.append("employee_ids[]", id)
            );

            const url = `/api/signin/report/excel?${params.toString()}`;
            window.open(url, "_blank");
        },

        async fetchMonthlySummary() {
            if (!this.monthlySummaryFilters.user_id) {
                Swal.fire("Atención", "Debe seleccionar un empleado.", "warning");
                return;
            }

            try {
                this.isLoadingMonthlySummary = true;
                this.monthlySummary = null;

                const response = await axios.get("/api/signin/monthly-summary", {
                    params: {
                        user_id: this.monthlySummaryFilters.user_id,
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

        capitalizeText(text) {
            if (!text) return "";

            return text.charAt(0).toUpperCase() + text.slice(1);
        },

        // Abre o cierra el desplegable
        seeFilters(type) {
            this.isSeeingFiltersPc[type] = !this.isSeeingFiltersPc[type];
        },

        // Alterna selección individual
        toggleEmployee(employee) {
            employee.active = !employee.active;
            console.log(this.selectedEmployees);
        },

        // Seleccionar o deseleccionar todos
        toggleAllEmployees() {
            const allActive = this.areAllEmployeesActive;
            this.basicData.userList.forEach((e) => (e.active = !allActive));
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

        limpiarFiltro() {
            this.selectedStartDate = "";
            this.selectedEndDate = "";
            this.fetchUserSignings();
        },

        async fetchForgottenSigningRequests() {
            try {
                this.isLoadingForgottenRequests = true;

                const params = {};

                if (this.forgottenRequestsStatusFilter) {
                    params.status = this.forgottenRequestsStatusFilter;
                }

                const forgottenResponse = await axios.get("/api/signin/forgotten-requests", {
                    params,
                });

                this.forgottenSigningRequests = forgottenResponse.data.data || [];

                const vacationResponse = await axios.get("/api/signin/work-calendar/events", {
                    params: {
                        type: "vacation",
                    },
                });

                let vacationRequests = vacationResponse.data.data || [];

                if (this.forgottenRequestsStatusFilter) {
                    vacationRequests = vacationRequests.filter((request) => {
                        return (request.status || "approved") === this.forgottenRequestsStatusFilter;
                    });
                }

                this.vacationRequests = vacationRequests;
            } catch (error) {
                console.error("Error al cargar solicitudes:", error);

                Swal.fire(
                    "Error",
                    error.response?.data?.message || "No se pudieron cargar las solicitudes.",
                    "error"
                );
            } finally {
                this.isLoadingForgottenRequests = false;
            }
        },

        getForgottenRequestId(request) {
            if (!request) return null;

            if (typeof request._id === "string") {
                return request._id;
            }

            if (request._id?.$oid) {
                return request._id.$oid;
            }

            if (request.id) {
                return request.id;
            }

            return String(request._id);
        },

        getForgottenRequestEmployeeName(userId) {
            const user = this.sortedUsers.find((user) => {
                return String(user._id) === String(userId);
            });

            if (!user) return "Empleado no encontrado";

            return `${user.firstName || ""} ${user.lastName || ""}`.trim();
        },

        getForgottenRequestStatusLabel(status) {
            const labels = {
                pending: "Pendiente",
                approved: "Aprobada",
                rejected: "Rechazada",
            };

            return labels[status] || status || "Pendiente";
        },

        async approveForgottenSigningRequest(request) {
            const requestId = this.getForgottenRequestId(request);

            const result = await Swal.fire({
                icon: "warning",
                title: "¿Aprobar solicitud?",
                html:
                    `Se creará un fichaje real para <strong>${this.getForgottenRequestEmployeeName(request.user_id)}</strong><br>` +
                    `Fecha: <strong>${this.formatWorkCalendarDate(request.date)}</strong><br>` +
                    `Horario: <strong>${request.entry || "--:--"} - ${request.exit || "--:--"}</strong>`,
                showCancelButton: true,
                confirmButtonText: "Sí, aprobar",
                cancelButtonText: "Cancelar",
            });

            if (!result.isConfirmed) return;

            try {
                await axios.post(`/api/signin/forgotten-requests/${requestId}/approve`);

                await this.fetchForgottenSigningRequests();

                if (
                    this.basicData.selectedUserId &&
                    String(this.basicData.selectedUserId) === String(request.user_id)
                ) {
                    await this.fetchUserSignings();
                }

                Swal.fire(
                    "Aprobada",
                    "La solicitud se ha aprobado y el fichaje se ha creado correctamente.",
                    "success"
                );
            } catch (error) {
                console.error("Error al aprobar solicitud:", error);

                Swal.fire(
                    "Error",
                    error.response?.data?.message || "No se pudo aprobar la solicitud.",
                    "error"
                );
            }
        },

        openRejectForgottenRequestModal(request) {
            this.selectedForgottenRequest = request;
            this.rejectForgottenRequestForm = {
                rejection_reason: "",
            };
            this.isRejectForgottenRequestModalOpen = true;
        },

        closeRejectForgottenRequestModal() {
            this.isRejectForgottenRequestModalOpen = false;
            this.selectedForgottenRequest = null;
            this.rejectForgottenRequestForm = {
                rejection_reason: "",
            };
        },

        async rejectForgottenSigningRequest() {
            if (!this.selectedForgottenRequest) return;

            if (
                !this.rejectForgottenRequestForm.rejection_reason ||
                this.rejectForgottenRequestForm.rejection_reason.length < 3
            ) {
                Swal.fire("Atención", "Debe indicar un motivo de rechazo.", "warning");
                return;
            }

            const requestId = this.getForgottenRequestId(this.selectedForgottenRequest);

            try {
                await axios.post(`/api/signin/forgotten-requests/${requestId}/reject`, {
                    rejection_reason: this.rejectForgottenRequestForm.rejection_reason,
                });

                this.closeRejectForgottenRequestModal();

                await this.fetchForgottenSigningRequests();

                Swal.fire(
                    "Rechazada",
                    "La solicitud se ha rechazado correctamente.",
                    "success"
                );
            } catch (error) {
                console.error("Error al rechazar solicitud:", error);

                Swal.fire(
                    "Error",
                    error.response?.data?.message || "No se pudo rechazar la solicitud.",
                    "error"
                );
            }
        },

        abrirModalGenerarInforme() {
            this.isModalOpen = true;
            console.log(this.basicData.userList.length);

            // Resetear todos los empleados a no seleccionados
            this.basicData.userList.forEach(e => {
                e.active = false;
            });

            // Activar únicamente el empleado seleccionado en el select principal
            if (this.basicData.selectedUserId) {
                const empleado = this.basicData.userList.find(e => e._id === this.basicData.selectedUserId);
                if (empleado) empleado.active = true;
            }
        },

        borrarFichaje(fichajeId) {
            Swal.fire({
                title: "¿Estás seguro?",
                text: "Esta acción eliminará el fichaje seleccionado.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar",
            }).then((result) => {
                if (result.isConfirmed) {
                    this.eliminarFichaje(fichajeId);
                }
            });
        },

        eliminarFichaje(fichajeId) {
            axios
                .delete(`/api/signin/${fichajeId}`)
                .then(() => {
                    this.basicData.signings = this.basicData.signings.filter(
                        (s) => s._id !== fichajeId
                    );
                    Swal.fire(
                        "Eliminado",
                        "El fichaje ha sido eliminado.",
                        "success"
                    );
                })
                .catch((error) => {
                    console.error("❌ Error al eliminar fichaje:", error);
                    Swal.fire(
                        "Error",
                        "Error al eliminar el fichaje. Inténtalo de nuevo.",
                        "error"
                    );
                });
        },

        /*editarFichaje(fichajeId) {
            const fichaje = this.basicData.signings.find(
                (s) => s._id === fichajeId
            );
            if (fichaje.entry && fichaje.entry.length === 7) {
                fichaje.entry = "0" + fichaje.entry;
            }
            if (fichaje.exit && fichaje.exit.length === 7) {
                fichaje.exit = "0" + fichaje.exit;
            }
            Swal.fire({
                title: "Editar fichaje",
                html: `

                    <input type="time" id="entryTime" class="swal2-input" placeholder="Hora de entrada" value="${
                        this.basicData.signings.find((s) => s._id === fichajeId)
                            .entry || "00:00:00"
                    }">
                    <input type="time" id="exitTime" class="swal2-input" placeholder="Hora de salida" value="${
                        this.basicData.signings.find((s) => s._id === fichajeId)
                            .exit || ""
                    }"><br/><br/>
                    <p>Notas:</p>
                    <textarea id="notes" class="swal2-textarea" placeholder="Notas" rows="5" cols="30">${
                        this.basicData.signings.find((s) => s._id === fichajeId)
                            .notes || ""
                    }</textarea>
                    <p>Tramos horarios (formato HH:MM-HH:MM, separados por comas):</p>
                    <textarea id="activitySections" class="swal2-textarea" placeholder="Tramos horarios" rows="5" cols="30">${
                        this.basicData.signings.find((s) => s._id === fichajeId)
                            .activity_sections
                            ? this.basicData.signings
                                  .find((s) => s._id === fichajeId)
                                  .activity_sections.join(";")
                            : ""
                    }</textarea>

                `,
                focusConfirm: false,
                showCancelButton: true,
                cancelButtonText: "Cancelar",
                confirmButtonText: "Guardar",
                preConfirm: () => {
                    const entryTime =
                        document.getElementById("entryTime").value;
                    const exitTime = document.getElementById("exitTime").value;
                    const notes = document.getElementById("notes").value;
                    const activitySections = document.getElementById(
                        "activitySections"
                    ).value;
                    return { entryTime, exitTime, notes, activitySections };
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    const { entryTime, exitTime, notes, activitySections } = result.value;
                    let activitySectionsArray = activitySections
                        .split(";")
                        .map((section) => section.trim())
                        .filter((section) => section.length > 0);
                    axios
                        .put(`/api/signin/${fichajeId}`, {
                            entry: entryTime,
                            exit: exitTime,
                            notes: notes,
                            activity_sections: activitySectionsArray
                        })
                        .then(() => {
                            this.fetchUserSignings();
                            Swal.fire(
                                "Actualizado",
                                "El fichaje ha sido actualizado.",
                                "success"
                            );
                        })
                        .catch((error) => {
                            console.error(
                                "❌ Error al actualizar fichaje:",
                                error
                            );
                            Swal.fire(
                                "Error",
                                "Error al actualizar el fichaje. Inténtalo de nuevo.",
                                "error"
                            );
                        });
                }
            });
        },*/

        editarFichaje(fichajeId) {
            const fichaje = this.basicData.signings.find(
                (s) => s._id === fichajeId
            );

            if (!fichaje) {
                Swal.fire({
                    icon: "error",
                    title: "Fichaje no encontrado",
                    text: "No se ha podido cargar el fichaje seleccionado.",
                    confirmButtonText: "Entendido",
                });
                return;
            }

            this.editingSigningId = fichajeId;

            this.editSigningForm = {
                date: fichaje.date || "",
                entry: this.normalizeTimeForInput(fichaje.entry),
                exit: this.normalizeTimeForInput(fichaje.exit),
                notes: fichaje.notes || "",
                activity_sections: this.parseActivitySections(
                    fichaje.activity_sections || []
                ),
            };

            this.isEditModalOpen = true;
        },

        normalizeTimeForInput(value) {
            if (!value) return "";

            const cleanValue = String(value).trim();

            if (/^\d{2}:\d{2}$/.test(cleanValue)) {
                return cleanValue;
            }

            if (/^\d{1}:\d{2}$/.test(cleanValue)) {
                return `0${cleanValue}`;
            }

            return cleanValue.slice(0, 5);
        },

        getSigningId(signing) {
            if (!signing) return null;

            if (typeof signing._id === "string") {
                return signing._id;
            }

            if (signing._id?.$oid) {
                return signing._id.$oid;
            }

            if (signing.id) {
                return signing.id;
            }

            return String(signing._id);
        },

        async openAuditModal(signing) {
            const signingId = this.getSigningId(signing);

            if (!signingId) {
                Swal.fire("Error", "No se pudo obtener el ID del fichaje.", "error");
                return;
            }

            this.selectedAuditSigning = signing;
            this.auditLogs = [];
            this.isAuditModalOpen = true;
            this.isLoadingAuditLogs = true;

            try {
                const response = await axios.get(`/api/signin/${signingId}/audit-logs`);

                this.auditLogs = response.data.data || [];
            } catch (error) {
                console.error("Error al cargar historial:", error);
                Swal.fire("Error", "No se pudo cargar el historial del fichaje.", "error");
            } finally {
                this.isLoadingAuditLogs = false;
            }
        },

        closeAuditModal() {
            this.isAuditModalOpen = false;
            this.auditLogs = [];
            this.selectedAuditSigning = null;
        },

        formatAuditDate(date) {
            if (!date) return "-";

            const parsedDate = new Date(date);

            if (Number.isNaN(parsedDate.getTime())) {
                return date;
            }

            return parsedDate.toLocaleString("es-ES", {
                day: "2-digit",
                month: "2-digit",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
        },

        getAuditFieldLabel(field) {
            const labels = {
                date: "Fecha",
                entry: "Hora de entrada",
                exit: "Hora de salida",
                entry_location: "Ubicación de entrada",
                exit_location: "Ubicación de salida",
                notes: "Notas",
                activity_sections: "Tramos de actividad",
                auto_closed: "Cierre automático",
                auto_closed_reason: "Motivo cierre automático",
                auto_closed_at: "Fecha cierre automático",
                auto_closed_exit: "Salida automática",
                corrected_by_admin: "Corregido por admin",
                corrected_at: "Fecha de corrección",
                corrected_by: "Corregido por",
            };

            return labels[field] || field;
        },

        formatAuditValue(value) {
            if (value === null || typeof value === "undefined" || value === "") {
                return "-";
            }

            if (typeof value === "boolean") {
                return value ? "Sí" : "No";
            }

            if (Array.isArray(value)) {
                return value.length ? value.join(", ") : "-";
            }

            if (typeof value === "object") {
                return JSON.stringify(value);
            }

            return value;
        },

        parseActivitySections(sections) {
            return sections
                .map((section) => {
                    const [timeRange, description] = String(section).split(",");
                    const [start, end] = String(timeRange || "").split("-");

                    return {
                        start: this.normalizeTimeForInput(start),
                        end: this.normalizeTimeForInput(end),
                        description: (description || "").trim(),
                    };
                })
                .filter((section) => section.start || section.end || section.description);
        },

        formatActivitySectionsForSave() {
            return this.editSigningForm.activity_sections
                .filter((section) => {
                    return section.start && section.end && section.description;
                })
                .map((section) => {
                    return `${section.start}-${section.end},${section.description}`;
                });
        },

        addEditTramo() {
            this.editSigningForm.activity_sections.push({
                start: "",
                end: "",
                description: "",
            });
        },

        removeEditTramo(index) {
            this.editSigningForm.activity_sections.splice(index, 1);
        },

        closeEditSigningModal() {
            this.isEditModalOpen = false;
            this.editingSigningId = null;
            this.editSigningForm = {
                date: "",
                entry: "",
                exit: "",
                notes: "",
                activity_sections: [],
                audit_reason: "",
            };
        },

        async saveEditedSigning() {
            if (!this.editingSigningId) return;

            if (!this.editSigningForm.date) {
                Swal.fire({
                    icon: "warning",
                    title: "Fecha obligatoria",
                    text: "Debe indicar la fecha del fichaje.",
                    confirmButtonText: "Entendido",
                });
                return;
            }

            if (!this.editSigningForm.entry) {
                Swal.fire({
                    icon: "warning",
                    title: "Entrada obligatoria",
                    text: "Debe indicar la hora de entrada.",
                    confirmButtonText: "Entendido",
                });
                return;
            }

            if (
                this.editSigningForm.exit &&
                this.editSigningForm.entry &&
                this.editSigningForm.exit < this.editSigningForm.entry
            ) {
                Swal.fire({
                    icon: "warning",
                    title: "Horario incorrecto",
                    text: "La hora de salida no puede ser anterior a la hora de entrada.",
                    confirmButtonText: "Entendido",
                });
                return;
            }

            try {
                this.isLoading = true;

                await axios.put(`/api/signin/${this.editingSigningId}`, {
                    date: this.editSigningForm.date,
                    entry: this.editSigningForm.entry,
                    exit: this.editSigningForm.exit || null,
                    notes: this.editSigningForm.notes,
                    activity_sections: this.formatActivitySectionsForSave(),
                });

                await this.fetchUserSignings();

                this.closeEditSigningModal();

                Swal.fire({
                    icon: "success",
                    title: "Fichaje actualizado",
                    text: "Los cambios se han guardado correctamente.",
                    timer: 1800,
                    showConfirmButton: false,
                });
            } catch (error) {
                console.error("Error al actualizar fichaje:", error);

                Swal.fire({
                    icon: "error",
                    title: "Error al actualizar",
                    text:
                        error.response?.data?.message ||
                        "No se ha podido actualizar el fichaje. Inténtelo de nuevo.",
                    confirmButtonText: "Entendido",
                });
            } finally {
                this.isLoading = false;
            }
        },

        formatDateForInput(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        },

        getVacationCalendarMonthRange() {
            const year = Number(this.vacationCalendarFilters.year);
            const month = Number(this.vacationCalendarFilters.month);
            const start = this.formatDateForInput(new Date(year, month - 1, 1));
            const end = this.formatDateForInput(new Date(year, month, 0));

            return { start, end };
        },

        async fetchVacationCalendarEvents() {
            try {
                this.isLoadingVacationCalendar = true;

                const range = this.getVacationCalendarMonthRange();

                const response = await axios.get("/api/signin/work-calendar/events", {
                    params: {
                        start_date: range.start,
                        end_date: range.end,
                    },
                });

                let events = response.data.data || [];

                if (this.vacationCalendarFilters.type) {
                    events = events.filter((event) => {
                        return event.type === this.vacationCalendarFilters.type;
                    });
                }

                if (this.vacationCalendarFilters.status) {
                    events = events.filter((event) => {
                        return (event.status || "approved") === this.vacationCalendarFilters.status;
                    });
                }

                this.vacationCalendarEvents = events;
            } catch (error) {
                console.error("Error al cargar calendario laboral:", error);

                Swal.fire(
                    "Error",
                    error.response?.data?.message || "No se pudo cargar el calendario laboral.",
                    "error"
                );
            } finally {
                this.isLoadingVacationCalendar = false;
            }
        },

        changeVacationCalendarMonth(step) {
            let month = Number(this.vacationCalendarFilters.month) + step;
            let year = Number(this.vacationCalendarFilters.year);

            if (month < 1) {
                month = 12;
                year -= 1;
            }

            if (month > 12) {
                month = 1;
                year += 1;
            }

            this.vacationCalendarFilters.month = month;
            this.vacationCalendarFilters.year = year;
            this.fetchVacationCalendarEvents();
        },

        getVacationEventsForDay(date) {
            if (!date) return [];

            return this.vacationCalendarEvents.filter((event) => {
                return event.start_date <= date && event.end_date >= date;
            });
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

        async fetchWorkCalendarEvents() {
            try {
                const response = await axios.get("/api/signin/work-calendar/events");

                this.workCalendarEvents = response.data.data || [];
            } catch (error) {
                console.error("Error al cargar calendario laboral:", error);
                Swal.fire("Error", "No se pudo cargar el calendario laboral.", "error");
            }
        },

        getWorkCalendarEventId(event) {
            if (!event) return null;

            if (typeof event._id === "string") {
                return event._id;
            }

            if (event._id?.$oid) {
                return event._id.$oid;
            }

            if (event.id) {
                return event.id;
            }

            return String(event._id);
        },

        openWorkCalendarModal() {
            this.editingWorkCalendarEventId = null;

            this.workCalendarForm = {
                type: "company_holiday",
                title: "",
                user_id: "",
                start_date: "",
                end_date: "",
                notes: "",
                status: "approved",
            };

            this.isWorkCalendarModalOpen = true;
        },

        editWorkCalendarEvent(event) {
            this.editingWorkCalendarEventId = this.getWorkCalendarEventId(event);

            this.workCalendarForm = {
                type: event.type || "company_holiday",
                title: event.title || "",
                user_id: event.user_id || "",
                start_date: event.start_date || "",
                end_date: event.end_date || "",
                notes: event.notes || "",
                status: event.status || "approved",
            };

            this.isWorkCalendarModalOpen = true;
        },

        closeWorkCalendarModal() {
            this.isWorkCalendarModalOpen = false;
            this.editingWorkCalendarEventId = null;
        },

        async saveWorkCalendarEvent() {
            try {
                if (!this.workCalendarForm.title) {
                    Swal.fire("Atención", "Debe indicar un título.", "warning");
                    return;
                }

                if (!this.workCalendarForm.start_date || !this.workCalendarForm.end_date) {
                    Swal.fire("Atención", "Debe indicar fecha de inicio y fin.", "warning");
                    return;
                }

                if (
                    this.workCalendarForm.type !== "company_holiday" &&
                    !this.workCalendarForm.user_id
                ) {
                    Swal.fire("Atención", "Debe seleccionar un empleado.", "warning");
                    return;
                }

                const payload = { ...this.workCalendarForm };

                if (payload.type === "company_holiday") {
                    payload.user_id = null;
                }

                if (this.editingWorkCalendarEventId) {
                    await axios.put(
                        `/api/signin/work-calendar/events/${this.editingWorkCalendarEventId}`,
                        payload
                    );
                } else {
                    await axios.post("/api/signin/work-calendar/events", payload);
                }

                this.closeWorkCalendarModal();
                await this.fetchWorkCalendarEvents();
                await this.fetchVacationCalendarEvents();

                Swal.fire("Correcto", "Evento guardado correctamente.", "success");
            } catch (error) {
                console.error("Error al guardar evento:", error);

                const message =
                    error.response?.data?.message ||
                    "No se pudo guardar el evento.";

                Swal.fire("Error", message, "error");
            }
        },

        async deleteWorkCalendarEvent(event) {
            const result = await Swal.fire({
                title: "¿Eliminar evento?",
                text: "Esta acción no se puede deshacer.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar",
            });

            if (!result.isConfirmed) return;

            try {
                const eventId = this.getWorkCalendarEventId(event);

                await axios.delete(`/api/signin/work-calendar/events/${eventId}`);

                await this.fetchWorkCalendarEvents();
                await this.fetchVacationCalendarEvents();

                Swal.fire("Eliminado", "Evento eliminado correctamente.", "success");
            } catch (error) {
                console.error("Error al eliminar evento:", error);
                Swal.fire("Error", "No se pudo eliminar el evento.", "error");
            }
        },

        getWorkCalendarEmployeeName(userId) {
            const user = this.sortedUsers.find((user) => {
                return String(user._id) === String(userId);
            });

            if (!user) return "Empleado no encontrado";

            return `${user.firstName || ""} ${user.lastName || ""}`.trim();
        },

        getWorkCalendarStatusLabel(status) {
            const labels = {
                approved: "Aprobado",
                pending: "Pendiente",
                rejected: "Rechazado",
            };

            return labels[status] || status || "Aprobado";
        },

        formatWorkCalendarDate(date) {
            if (!date) return "-";

            const parts = date.split("-");
            if (parts.length !== 3) return date;

            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        },

        async filtrarPorFechas() {
            const userId = this.basicData.selectedUserId;
            if (!userId || !this.selectedStartDate || !this.selectedEndDate)
                return;

            this.isLoading = true;
            try {
                const res = await axios.get(
                    `/api/signin/user/${userId}/date/${this.selectedStartDate}/${this.selectedEndDate}`,
                    {
                        params: {
                            page: this.currentPage,
                            per_page: this.perPage,
                        },
                    }
                );

                this.basicData.signings = Array.isArray(res.data.data)
                    ? res.data.data
                    : [];

                console.log(
                    `✅ Fichajes filtrados entre ${this.selectedStartDate} y ${this.selectedEndDate}:`,
                    this.basicData.signings
                );
            } catch (error) {
                console.error("❌ Error al filtrar fichajes:", error);
            } finally {
                this.isLoading = false;
            }
        },

        abrirMapa(ubicacion) {
            const url = `https://www.google.com/maps?q=${ubicacion.lat},${ubicacion.lng}`;
            window.open(url, "_blank");
        },
    },

    async mounted() {
        if (this.basicData.userList.length > 0) {
            this.basicData.selectedUserId = null;
            await this.fetchUserSignings();
        }
    },
};
</script>
<style scoped>
.signings-tool-module {
    width: 100%;
    padding: 42px 48px;
    color: #073b78;
}

.signings-tool-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    margin-bottom: 28px;
}

.module-kicker,
.section-kicker {
    margin: 0 0 6px;
    font-size: 13px;
    font-weight: 800;
    color: #6d84a6;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.section-kicker {
    margin-bottom: 5px;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.05em;
}

.signings-tool-header h2 {
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

.zoco-card {
    background: #ffffff;
    border: 1px solid rgba(7, 59, 120, 0.08);
    border-radius: 26px;
    box-shadow: 0 14px 30px rgba(7, 59, 120, 0.09);
}

.filters-card {
    padding: 24px;
    margin-bottom: 28px;
}

.filters-header {
    margin-bottom: 18px;
}

.filters-header h3,
.table-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 900;
    color: #073b78;
}

.filters-header p:not(.section-kicker),
.table-header p {
    margin: 5px 0 0;
    font-size: 14px;
    color: #6d84a6;
}

.filters-grid {
    display: grid;
    grid-template-columns: minmax(260px, 1.3fr) minmax(180px, 0.8fr) minmax(180px, 0.8fr) auto;
    align-items: end;
    gap: 18px;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-field label,
.employee-selector > label {
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
    transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
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

.actions-column {
    width: 150px;
    text-align: right !important;
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
    gap: 10px;
}

.exit-cell {
    align-items: flex-start;
}

.exit-info {
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

.row-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
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
    transition: transform 0.18s ease, background 0.18s ease, color 0.18s ease;
}

.icon-btn:hover {
    transform: translateY(-1px);
    background: #073b78;
    color: #ffffff;
}

.icon-btn.edit {
    background: rgba(7, 59, 120, 0.09);
}

.icon-btn.delete {
    background: rgba(198, 40, 40, 0.09);
    color: #b42318;
}

.icon-btn.delete:hover {
    background: #b42318;
    color: #ffffff;
}

.empty-state,
.loading-state {
    min-height: 190px;
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
    min-height: 120px;
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
    width: min(720px, 100%);
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

.modal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    margin-bottom: 22px;
}

.employee-selector {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.employee-dropdown {
    position: relative;
}

.employee-dropdown-title {
    min-height: 46px;
    padding: 0 15px;
    border: 1px solid rgba(7, 59, 120, 0.16);
    border-radius: 14px;
    background: #f7f9fc;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #073b78;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
}

.employee-dropdown.active .employee-dropdown-title {
    border-color: #073b78;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(7, 59, 120, 0.08);
}

.employee-dropdown-menu {
    position: absolute;
    z-index: 5;
    top: calc(100% + 10px);
    left: 0;
    right: 0;
    max-height: 280px;
    overflow-y: auto;
    padding: 12px;
    border: 1px solid rgba(7, 59, 120, 0.12);
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 18px 34px rgba(7, 59, 120, 0.14);
}

.employee-search {
    height: 42px;
    margin-bottom: 8px;
    padding: 0 12px;
    border-radius: 13px;
    background: #f7f9fc;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #6d84a6;
}

.employee-search input {
    width: 100%;
    border: 0;
    background: transparent;
    outline: none;
    color: #073b78;
    font-weight: 700;
}

.employee-option {
    min-height: 40px;
    padding: 7px 6px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #073b78;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
}

.employee-option:hover {
    background: #f7f9fc;
}

.zoco-checkbox {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(7, 59, 120, 0.25);
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: transparent;
    font-size: 11px;
}

.zoco-checkbox.selected {
    border-color: #073b78;
    background: #073b78;
    color: #ffffff;
}

.no-employees {
    padding: 14px;
    border-radius: 14px;
    background: #f7f9fc;
    color: #6d84a6;
    font-weight: 700;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 28px;
}

.edit-signing-modal {
    width: min(860px, 100%);
}

.edit-form-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.edit-form-grid .full {
    grid-column: 1 / -1;
}

.zoco-textarea {
    width: 100%;
    min-height: 120px;
    padding: 14px;
    border: 1px solid rgba(7, 59, 120, 0.16);
    border-radius: 14px;
    background: #f7f9fc;
    color: #073b78;
    font-size: 14px;
    font-weight: 600;
    outline: none;
    resize: vertical;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.requests-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.requests-hero {
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 18px;
    background:
        radial-gradient(circle at top right, rgba(16, 185, 129, 0.10), transparent 32%),
        radial-gradient(circle at top left, rgba(7, 59, 120, 0.10), transparent 36%),
        #ffffff;
}

.requests-hero-content h3 {
    margin: 6px 0 0;
    color: #073b78;
    font-size: 24px;
    font-weight: 900;
}

.requests-hero-content p {
    margin: 7px 0 0;
    color: #6d84a6;
    font-size: 14px;
}

.requests-hero-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: min(100%, 460px);
}

.requests-filter {
    min-width: 210px;
}

.requests-loading {
    min-height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #073b78;
    font-weight: 900;
}

.requests-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(130px, 1fr));
    gap: 14px;
}

.request-kpi {
    padding: 18px;
}

.request-kpi span {
    display: block;
    margin-bottom: 8px;
    color: #6d84a6;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.request-kpi strong {
    color: #073b78;
    font-size: 28px;
    font-weight: 900;
}

.request-kpi.warning strong {
    color: #b45309;
}

.request-kpi.success strong {
    color: #047857;
}

.requests-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    align-items: start;
}

.request-panel {
    padding: 22px;
    min-width: 0;
}

.request-panel-header {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 16px;
}

.request-panel-icon {
    width: 46px;
    height: 46px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 46px;
    font-size: 19px;
}

.request-panel-icon.signing-icon {
    background: rgba(7, 59, 120, 0.09);
    color: #073b78;
}

.request-panel-icon.vacation-icon {
    background: rgba(16, 185, 129, 0.11);
    color: #047857;
}

.request-panel-header h4 {
    margin: 0;
    color: #073b78;
    font-size: 18px;
    font-weight: 900;
}

.request-panel-header p {
    margin: 5px 0 0;
    color: #6d84a6;
    font-size: 13px;
    line-height: 1.35;
}

.compact-empty-state {
    min-height: 230px;
    border: 1px dashed rgba(7, 59, 120, 0.16);
    border-radius: 20px;
    background: #f7f9fc;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6d84a6;
    text-align: center;
    padding: 22px;
}

.compact-empty-state .empty-icon {
    margin-bottom: 12px;
}

.compact-empty-state h4 {
    margin: 0;
    color: #073b78;
    font-size: 16px;
    font-weight: 900;
}

.compact-empty-state p {
    margin: 6px 0 0;
    font-size: 13px;
    font-weight: 700;
}

.request-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.request-card {
    border: 1px solid rgba(7, 59, 120, 0.09);
    border-radius: 18px;
    padding: 15px;
    background: #ffffff;
    box-shadow: 0 8px 18px rgba(7, 59, 120, 0.06);
}

.request-card.vacation-card {
    border-color: rgba(16, 185, 129, 0.16);
}

.request-card-main {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.request-card-main strong {
    display: block;
    color: #073b78;
    font-size: 14px;
    font-weight: 900;
}

.request-card-main span:not(.forgotten-status-badge) {
    display: block;
    margin-top: 4px;
    color: #6d84a6;
    font-size: 12px;
    font-weight: 800;
}

.request-card-notes {
    margin: 12px 0 0;
    padding: 11px 12px;
    border-radius: 13px;
    background: #f7f9fc;
    color: #37516f;
    font-size: 13px;
    line-height: 1.4;
    font-weight: 700;
}

.request-card-footer {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(7, 59, 120, 0.08);
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
}

.request-card-footer > span {
    color: #6d84a6;
    font-size: 12px;
    font-weight: 800;
}

.existing-signins-warning {
    display: inline-flex;
    width: fit-content;
    margin-top: 10px;
    padding: 5px 9px;
    border-radius: 999px;
    background: rgba(245, 158, 11, 0.14);
    color: #92400e;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
}

.forgotten-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: fit-content;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    white-space: nowrap;
}

.forgotten-status-badge.status-pending {
    background: rgba(245, 158, 11, 0.14);
    color: #92400e;
}

.forgotten-status-badge.status-approved {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
}

.forgotten-status-badge.status-rejected {
    background: rgba(239, 68, 68, 0.13);
    color: #b91c1c;
}

.icon-btn.approve {
    color: #047857;
    background: rgba(16, 185, 129, 0.12);
}

.icon-btn.approve:hover {
    background: #047857;
    color: #ffffff;
}

.reviewed-info {
    color: #6b7280;
    font-size: 12px;
    font-weight: 800;
}

.reject-request-modal {
    max-width: 620px;
}

.danger-icon {
    background: rgba(239, 68, 68, 0.12);
    color: #b91c1c;
}

@media (max-width: 1180px) {
    .requests-grid {
        grid-template-columns: 1fr;
    }

    .requests-kpis {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .requests-hero {
        align-items: stretch;
        flex-direction: column;
    }

    .requests-hero-actions {
        width: 100%;
        min-width: 0;
        flex-direction: column;
        align-items: stretch;
    }

    .requests-filter,
    .requests-hero-actions .zoco-btn {
        width: 100%;
    }

    .request-card-main,
    .request-card-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .requests-kpis {
        grid-template-columns: 1fr;
    }
}

.vacation-calendar-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.vacation-calendar-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 24px;
}

.vacation-calendar-hero h3 {
    margin: 0;
    color: #073b78;
    font-size: 22px;
    font-weight: 900;
}

.vacation-calendar-hero p {
    margin: 6px 0 0;
    color: #6d84a6;
    font-size: 14px;
}

.vacation-calendar-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.vacation-calendar-current {
    min-width: 180px;
    text-align: center;
    padding: 11px 16px;
    border-radius: 14px;
    background: #f7f9fc;
    border: 1px solid rgba(7, 59, 120, 0.1);
    color: #073b78;
    font-weight: 900;
}

.vacation-calendar-filters {
    display: grid;
    grid-template-columns: minmax(140px, 0.7fr) minmax(160px, 0.8fr) minmax(180px, 0.9fr) auto;
    gap: 14px;
    align-items: end;
    padding: 18px;
}

.vacation-calendar-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(140px, 1fr));
    gap: 14px;
}

.vacation-calendar-kpi {
    padding: 18px;
}

.vacation-calendar-kpi span {
    display: block;
    color: #6d84a6;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.vacation-calendar-kpi strong {
    color: #073b78;
    font-size: 26px;
    font-weight: 900;
}

.vacation-calendar-kpi.success strong {
    color: #047857;
}

.vacation-calendar-kpi.warning strong {
    color: #d97706;
}

.vacation-calendar-card {
    overflow: hidden;
}

.vacation-calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #f3f6fa;
    border-bottom: 1px solid rgba(7, 59, 120, 0.08);
}

.vacation-calendar-weekdays span {
    padding: 12px;
    text-align: center;
    color: #516987;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
}

.vacation-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
}

.vacation-calendar-day {
    min-height: 138px;
    padding: 10px;
    border-right: 1px solid rgba(7, 59, 120, 0.07);
    border-bottom: 1px solid rgba(7, 59, 120, 0.07);
    background: #ffffff;
}

.vacation-calendar-day.empty {
    background: #f9fafb;
}

.vacation-calendar-day.has-events {
    background: #fbfefd;
}

.vacation-calendar-day-number {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    color: #073b78;
    font-size: 13px;
    font-weight: 900;
    background: #f3f6fa;
}

.vacation-calendar-event-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 8px;
}

.vacation-calendar-event {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 7px 8px;
    border-radius: 10px;
    border: 1px solid rgba(7, 59, 120, 0.08);
    background: rgba(37, 99, 235, 0.08);
    color: #1d4ed8;
    overflow: hidden;
}

.vacation-calendar-event strong {
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.vacation-calendar-event span {
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
}

.vacation-calendar-event.status-approved {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
}

.vacation-calendar-event.status-pending {
    background: rgba(245, 158, 11, 0.14);
    color: #92400e;
}

.vacation-calendar-event.status-rejected {
    background: rgba(239, 68, 68, 0.12);
    color: #b91c1c;
}

@media (max-width: 980px) {
    .vacation-calendar-hero,
    .vacation-calendar-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .vacation-calendar-filters,
    .vacation-calendar-kpis {
        grid-template-columns: repeat(2, 1fr);
    }

    .vacation-calendar-grid,
    .vacation-calendar-weekdays {
        min-width: 900px;
    }

    .vacation-calendar-card {
        overflow-x: auto;
    }
}

@media (max-width: 640px) {
    .vacation-calendar-filters,
    .vacation-calendar-kpis {
        grid-template-columns: 1fr;
    }
}

.zoco-textarea:focus {
    background: #ffffff;
    border-color: #073b78;
    box-shadow: 0 0 0 4px rgba(7, 59, 120, 0.08);
}

.edit-sections-block {
    margin-top: 24px;
    padding: 18px;
    border-radius: 20px;
    background: #f7f9fc;
    border: 1px solid rgba(7, 59, 120, 0.07);
}

.edit-sections-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 16px;
}

.edit-sections-header h3 {
    margin: 0;
    color: #073b78;
    font-size: 17px;
    font-weight: 900;
}

.edit-sections-header p {
    margin: 5px 0 0;
    color: #6d84a6;
    font-size: 13px;
}

.edit-empty-sections {
    padding: 14px;
    border-radius: 14px;
    background: #ffffff;
    color: #6d84a6;
    font-size: 14px;
    font-weight: 700;
}

.edit-tramo-row {
    display: grid;
    grid-template-columns: 130px 130px 1fr 38px;
    gap: 12px;
    align-items: end;
    padding: 12px;
    border-radius: 16px;
    background: #ffffff;
    border: 1px solid rgba(7, 59, 120, 0.06);
}

.edit-tramo-row + .edit-tramo-row {
    margin-top: 10px;
}

.tramo-description {
    min-width: 0;
}

.tramo-delete {
    margin-bottom: 0;
}

.signing-tabs {
    display: flex;
    gap: 10px;
    margin: 18px 0 20px;
    flex-wrap: wrap;
}

.signing-tab {
    border: 1px solid #d1d5db;
    background: #ffffff;
    color: #374151;
    border-radius: 999px;
    padding: 10px 16px;
    font-weight: 900;
    cursor: pointer;
    transition: all 0.2s ease;
}

.signing-tab.active {
    background: #111827;
    border-color: #111827;
    color: #ffffff;
}

.work-calendar-card {
    margin-top: 0;
}

.work-calendar-modal {
    max-width: 760px;
}

.calendar-type-badge,
.calendar-status-badge {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    white-space: nowrap;
}

.calendar-status-badge.status-approved {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
}

.calendar-status-badge.status-pending {
    background: rgba(245, 158, 11, 0.14);
    color: #92400e;
}

.calendar-status-badge.status-rejected {
    background: rgba(239, 68, 68, 0.13);
    color: #b91c1c;
}

.calendar-type-badge {
    background: rgba(37, 99, 235, 0.1);
    color: #1d4ed8;
}

.calendar-status-badge {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
}



.audit-modal {
    max-width: 850px;
}

.audit-loading,
.audit-empty {
    padding: 24px;
    border-radius: 16px;
    background: #f9fafb;
    color: #6b7280;
    font-weight: 700;
    text-align: center;
}

.audit-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-height: 520px;
    overflow-y: auto;
    padding-right: 4px;
}

.audit-item {
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 16px;
    background: #ffffff;
}

.audit-item-header {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
    color: #111827;
}

.audit-item-header span {
    color: #6b7280;
    font-size: 13px;
}

.audit-reason {
    margin-bottom: 12px;
    padding: 10px 12px;
    border-radius: 12px;
    background: #f9fafb;
    color: #374151;
    font-size: 13px;
}

.audit-changes {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.audit-change-row {
    display: grid;
    grid-template-columns: 180px 1fr;
    gap: 12px;
    align-items: center;
    padding: 10px 0;
    border-top: 1px solid #f3f4f6;
}

.audit-field {
    font-weight: 900;
    color: #374151;
    font-size: 13px;
}

.audit-values {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.old-value,
.new-value {
    display: inline-flex;
    max-width: 260px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding: 6px 9px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 800;
}

.old-value {
    background: rgba(239, 68, 68, 0.08);
    color: #991b1b;
}

.new-value {
    background: rgba(16, 185, 129, 0.1);
    color: #047857;
}

.icon-btn.history {
    color: #7c3aed;
}



.monthly-summary-card {
    margin-top: 0;
}

.summary-filters {
    display: grid;
    grid-template-columns: minmax(220px, 1.5fr) repeat(3, minmax(140px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}

.summary-filter-action {
    justify-content: flex-end;
}

.summary-empty {
    padding: 24px;
    border-radius: 16px;
    background: #f9fafb;
    color: #6b7280;
    font-weight: 800;
    text-align: center;
}

.summary-kpis {
    display: grid;
    grid-template-columns: repeat(6, minmax(120px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}

.summary-kpi {
    padding: 16px;
    border-radius: 16px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
}

.summary-kpi span {
    display: block;
    font-size: 12px;
    font-weight: 900;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 8px;
}

.summary-kpi strong {
    font-size: 24px;
    font-weight: 900;
    color: #111827;
}

.summary-kpi.danger strong {
    color: #dc2626;
}

.monthly-summary-table-wrapper {
    margin-top: 8px;
}

.day-signins-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.day-status-badge {
    display: inline-flex;
    width: fit-content;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    white-space: nowrap;
}

.day-status-badge.status-worked {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
}

.day-status-badge.status-company_holiday,
.day-status-badge.status-vacation,
.day-status-badge.status-absence {
    background: rgba(37, 99, 235, 0.1);
    color: #1d4ed8;
}

.day-status-badge.status-auto_closed,
.day-status-badge.status-open_signin {
    background: rgba(245, 158, 11, 0.14);
    color: #92400e;
}

.day-status-badge.status-missing_signin {
    background: rgba(239, 68, 68, 0.13);
    color: #b91c1c;
}

.day-status-badge.status-no_work {
    background: #f3f4f6;
    color: #6b7280;
}

@media (max-width: 1024px) {
    .summary-kpis {
        grid-template-columns: repeat(3, 1fr);
    }

    .summary-filters {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .summary-kpis,
    .summary-filters {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 1180px) {
    .filters-grid {
        grid-template-columns: 1fr 1fr;
    }

    .employee-field {
        grid-column: span 2;
    }

    .filters-actions {
        grid-column: span 2;
        justify-content: flex-end;
    }
}

@media (max-width: 720px) {
    .signings-tool-module {
        padding: 24px 18px;
    }

    .signings-tool-header {
        flex-direction: column;
    }

    .filters-grid,
    .modal-grid {
        grid-template-columns: 1fr;
    }

    .employee-field,
    .filters-actions {
        grid-column: auto;
    }

    .filters-actions,
    .modal-actions {
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

    .edit-form-grid {
        grid-template-columns: 1fr;
    }

    .edit-tramo-row {
        grid-template-columns: 1fr;
    }

    .edit-sections-header {
        flex-direction: column;
    }

    .tramo-delete {
        width: 100%;
    }
}


.work-calendar-page {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.work-calendar-hero {
    margin-top: 0;
}

.work-calendar-filters {
    grid-template-columns: repeat(4, minmax(150px, 1fr)) auto;
}

.work-calendar-kpis {
    grid-template-columns: repeat(5, minmax(130px, 1fr));
}

.vacation-calendar-kpi.holiday strong {
    color: #7c3aed;
}

.vacation-calendar-kpi.absence strong {
    color: #1d4ed8;
}

.work-calendar-month-card {
    overflow: hidden;
}

.work-calendar-day {
    min-height: 150px;
}

.work-calendar-month-event {
    border-left-width: 4px;
}

.work-calendar-month-event.type-company_holiday {
    background: rgba(124, 58, 237, 0.08);
    border-color: rgba(124, 58, 237, 0.36);
}

.work-calendar-month-event.type-vacation {
    background: rgba(16, 185, 129, 0.08);
    border-color: rgba(16, 185, 129, 0.36);
}

.work-calendar-month-event.type-medical_leave,
.work-calendar-month-event.type-personal_day,
.work-calendar-month-event.type-justified_absence {
    background: rgba(37, 99, 235, 0.08);
    border-color: rgba(37, 99, 235, 0.32);
}

.calendar-event-type-mini {
    display: inline-flex;
    width: fit-content;
    font-size: 10px !important;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    opacity: 0.9;
}

.work-calendar-list-card {
    margin-top: 0;
}

.calendar-type-badge.type-company_holiday {
    background: rgba(124, 58, 237, 0.1);
    color: #6d28d9;
}

.calendar-type-badge.type-vacation {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
}

.calendar-type-badge.type-medical_leave,
.calendar-type-badge.type-personal_day,
.calendar-type-badge.type-justified_absence {
    background: rgba(37, 99, 235, 0.1);
    color: #1d4ed8;
}

@media (max-width: 1180px) {
    .work-calendar-filters,
    .work-calendar-kpis {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .work-calendar-filters,
    .work-calendar-kpis {
        grid-template-columns: 1fr;
    }
}

</style>
