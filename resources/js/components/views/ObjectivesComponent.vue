<template>
    <div class="content-white objectives-page" @click="hideCustomSelects">

        <!-- MOBILE -->
        <div class="mobile-item">
            <div class="sticky-header-mobile">
                <div class="d-flex justify-between align-center">
                    <div class="text my-10" data-size="22" data-weight="700">Objetivos</div>

                    <div
                        v-if="canManage('objectives.create')"
                        class="custom-button mobile-create-btn"
                        data-size="medium"
                        data-bg="principal"
                        @click.stop="openCreateModal"
                    >
                        <i class="fas fa-plus"></i>
                    </div>
                </div>

                <div class="mobile-dates mt-10">
                    <div class="form-group mb-10">
                        <label class="text opacity-6" data-size="12">Fecha inicial</label>
                        <div class="input-group">
                            <input type="date" v-model="dates.start" @change="onDateChange" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="text opacity-6" data-size="12">Fecha final</label>
                        <div class="input-group">
                            <input type="date" v-model="dates.end" @change="onDateChange" />
                        </div>
                    </div>
                </div>

                <div class="dash-user-select mt-10" v-if="basicData && basicData.userList && selectedUser">
                    <div
                        class="custom-select no-hover"
                        @click.stop="seeFilters('user')"
                        :class="{ seeing: visibleSelects.user }"
                    >
                        <div class="d-flex align-center">
                            <div class="profile-image mr-10" v-if="selectedUser.profileImage">
                                <img
                                    class="pointer h-35-px w-35-px"
                                    :src="'/assets/profile_images/' + selectedUser.profileImage"
                                    alt="Imagen de perfil"
                                />
                            </div>

                            <div class="initials verySmall mr-10" v-else>
                                {{ getInitials(selectedUserLabel) }}
                            </div>

                            <p class="opacity-5 my-auto" data-size="13">
                                {{ selectedUserLabel }}
                            </p>
                        </div>

                        <div class="select-content form">
                            <div class="form-group mb-10" @click.stop>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        v-model="agentSearch"
                                        placeholder="Buscar agente..."
                                        @click.stop
                                    />
                                </div>
                            </div>

                            <template v-if="filteredUsers.length">
                                <div
                                    v-for="(user, userIndex) in filteredUsers"
                                    :key="user._id"
                                    @click="selectUser(user)"
                                >
                                    <div class="d-flex align-center">
                                        <div class="profile-image mr-10" v-if="user.profileImage">
                                            <img
                                                class="pointer h-20-px w-20-px"
                                                :src="'/assets/profile_images/' + user.profileImage"
                                                alt="Imagen de perfil"
                                            />
                                        </div>

                                        <div class="initials verySmall mr-10" v-else>
                                            {{ getInitials(getUserLabel(user)) }}
                                        </div>

                                        <p class="text my-5">{{ getUserLabel(user) }}</p>
                                    </div>

                                    <p class="separator my-5" v-if="userIndex < filteredUsers.length - 1"></p>
                                </div>
                            </template>

                            <p v-else class="text opacity-5 my-5" data-size="12">No se encontraron agentes.</p>
                        </div>
                    </div>
                </div>

                <div class="search-div d-flex mt-10">
                    <div class="search-bar w-100">
                        <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                        <input
                            type="text"
                            data-size="14"
                            placeholder="Buscar objetivos..."
                            v-model="objectiveSearch"
                            @click.stop
                        />
                    </div>

                    <i
                        data-color="principal"
                        class="pointer fas fa-trash my-10 ml-10"
                        @click.stop="resetObjectiveSearch"
                    ></i>
                </div>
            </div>

            <div class="loading-indicator" v-if="isLoading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <p class="text" data-size="14">Cargando objetivos...</p>
            </div>

            <template v-if="!isLoading && hasPageData">
                <div class="d-flex column my-10 py-5 px-10 round" data-round="10" data-bg="gris" v-if="summary">
                    <div class="text d-flex align-center my-5" data-size="14" data-weight="500">
                        <div class="icon mr-10"><i class="far fa-file-lines"></i></div>
                        <p class="mr-5">Contratos:</p>
                        <span data-weight="600" data-size="16">{{ summary.totalContracts }}</span>
                        <span class="opacity-5 ml-5" data-size="12">/ {{ formatCompact(summary.targetContracts) }}</span>
                    </div>

                    <div class="text d-flex align-center my-5" data-size="14" data-weight="500">
                        <div class="icon mr-10"><i class="far fa-lightbulb"></i></div>
                        <p class="mr-5">Consumo:</p>
                        <span data-weight="600" data-size="16">{{ formatConsumption(summary.totalConsumption) }}</span>
                    </div>

                    <div class="text d-flex align-center my-5" data-size="14" data-weight="500">
                        <div class="icon mr-10"><i class="far fa-chart-line"></i></div>
                        <p class="mr-5">% Global:</p>
                        <span data-weight="600" data-size="16" :data-color="getPctColor(summary.globalPct)">
                            {{ summary.globalPct }}%
                        </span>
                    </div>
                </div>

                <div v-if="objectiveSearch && !hasFilteredObjectives" class="objectives-empty-card objectives-search-empty mt-20">
                    <div class="objectives-empty-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>

                    <div class="text mt-15" data-size="17" data-weight="700">Sin resultados</div>

                    <div class="text opacity-6 mt-10" data-size="13">
                        No hay objetivos que coincidan con “{{ objectiveSearch }}”.
                    </div>
                </div>

                <div class="mobile-objectives my-15" v-if="filteredGeneralObjectives.length">
                    <div class="text mb-10" data-weight="700">Objetivos generales</div>

                    <div
                        v-for="objective in filteredGeneralObjectives"
                        :key="objective._id"
                        class="objective-list-card mb-10"
                    >
                        <div class="d-flex justify-between align-start" data-gap="10">
                            <div>
                                <div class="text" data-weight="700">
                                    {{ getObjectiveTypeLabel(objective.type) }}
                                </div>

                                <div class="text opacity-6 mt-5" data-size="12">
                                    General · Todos los agentes
                                </div>

                                <div class="text opacity-6 mt-5" data-size="12" v-if="objective.marketer">
                                    {{ objective.marketer }}
                                </div>

                                <div class="text opacity-6 mt-5" data-size="12">
                                    {{ formatDate(objective.startDate) }} - {{ formatDate(objective.endDate) }}
                                </div>
                            </div>

                            <div class="d-flex align-center" data-gap="8">
                                <div
                                    class="custom-button w-fit-content"
                                    data-size="small"
                                    data-mode="translucent"
                                    :data-bg="objective.type === 'contracts' ? 'azul' : 'amarillo'"
                                >
                                    {{
                                        objective.type === 'contracts'
                                            ? formatCompact(objective.value)
                                            : formatConsumption(objective.value)
                                    }}
                                </div>

                                <template v-if="canModifyObjectives">
                                    <i
                                        v-if="canManage('objectives.edit')"
                                        class="fas fa-pen pointer"
                                        data-color="azul"
                                        @click.stop="editObjective(objective)"
                                    ></i>

                                    <i
                                        v-if="canManage('objectives.delete')"
                                        class="fas fa-trash pointer"
                                        data-color="rojo"
                                        @click.stop="deleteObjective(objective)"
                                    ></i>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mobile-objectives my-15" v-if="filteredPersonalObjectives.length">
                    <div class="text mb-10" data-weight="700">Objetivos por usuario</div>

                    <div
                        v-for="objective in filteredPersonalObjectives"
                        :key="objective._id"
                        class="objective-list-card mb-10"
                    >
                        <div class="d-flex justify-between align-start" data-gap="10">
                            <div>
                                <div class="text" data-weight="700">
                                    {{ getObjectiveTypeLabel(objective.type) }}
                                </div>

                                <div class="text opacity-6 mt-5" data-size="12">
                                    {{ objective.scopeLabel }} · {{ objective.userName }}
                                </div>

                                <div class="text opacity-6 mt-5" data-size="12" v-if="objective.marketer">
                                    {{ objective.marketer }}
                                </div>

                                <div class="text opacity-6 mt-5" data-size="12">
                                    {{ formatDate(objective.startDate) }} - {{ formatDate(objective.endDate) }}
                                </div>
                            </div>

                            <div class="d-flex align-center" data-gap="8">
                                <div
                                    class="custom-button w-fit-content"
                                    data-size="small"
                                    data-mode="translucent"
                                    :data-bg="objective.type === 'contracts' ? 'azul' : 'amarillo'"
                                >
                                    {{
                                        objective.type === 'contracts'
                                            ? formatCompact(objective.value)
                                            : formatConsumption(objective.value)
                                    }}
                                </div>

                                <template v-if="canModifyObjectives">
                                    <i
                                        v-if="canManage('objectives.edit')"
                                        class="fas fa-pen pointer"
                                        data-color="azul"
                                        @click.stop="editObjective(objective)"
                                    ></i>

                                    <i
                                        v-if="canManage('objectives.delete')"
                                        class="fas fa-trash pointer"
                                        data-color="rojo"
                                        @click.stop="deleteObjective(objective)"
                                    ></i>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mobile-objectives my-15" v-if="userRows.length">
                    <div class="text mb-10" data-weight="700">Detalle por usuario</div>

                    <div v-for="(userRow, userIndex) in userRows" :key="userRow._id || userIndex" class="my-5">
                        <div class="d-flex align-center pointer" @click="toggleMobileUser(userIndex)">
                            <div class="text ellipsis" data-weight="600">{{ userRow.name }}</div>

                            <div class="deploy-btn ml-10" data-round="15" :class="{ selected: mobileUserOpen === userIndex }">
                                <i class="fa-solid" :class="mobileUserOpen === userIndex ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </div>
                        </div>

                        <div v-if="mobileUserOpen === userIndex" class="d-flex column mt-5">
                            <div class="d-flex justify-between">
                                <div class="text" data-size="12" data-weight="600">Contratos</div>
                                <div class="text" data-size="12">
                                    {{ userRow.contracts }} / {{ formatCompact(userRow.targetContracts) }}
                                </div>
                            </div>

                            <div class="progress-bar my-5">
                                <div :class="'prog-' + getUserPct(userRow, 'contracts')"></div>
                            </div>

                            <div class="d-flex justify-between">
                                <div class="text" data-size="12" data-weight="600">Consumo</div>
                                <div class="text" data-size="12">
                                    {{ formatConsumption(userRow.consumption) }} / {{ formatConsumption(userRow.targetConsumption) }}
                                </div>
                            </div>

                            <div class="progress-bar my-5">
                                <div :class="'prog-' + getUserPct(userRow, 'consumption')"></div>
                            </div>
                        </div>

                        <div class="separator my-10" v-if="userIndex < userRows.length - 1"></div>
                    </div>
                </div>
            </template>

            <div v-if="!isLoading && !hasPageData" class="objectives-empty-card mt-20">
                <div class="objectives-empty-icon">
                    <i class="fa-solid fa-bullseye"></i>
                </div>

                <div class="text mt-15" data-size="18" data-weight="700">No hay objetivos</div>

                <div class="text opacity-6 mt-10" data-size="13">
                    Cree un objetivo de contratos o consumo para empezar a ver resultados.
                </div>

                <div
                    v-if="canManage('objectives.create')"
                    class="custom-button mt-20"
                    data-size="regular"
                    data-bg="principal"
                    @click.stop="openCreateModal"
                >
                    <i class="fas fa-plus mr-10"></i> Crear objetivo
                </div>
            </div>
        </div>

        <!-- DESKTOP -->
        <div class="desktop-item">
            <div class="d-flex justify-between align-center">
                <div class="d-flex align-center f-wrap" data-gap="20">
                    <div class="text" data-size="30" data-weight="700">Objetivos</div>

                    <div
                        class="custom-select no-hover my-auto px-10 py-5 round"
                        data-round="20"
                        data-bg="gris"
                        @click.stop="seeFilters('dates')"
                        :class="{ seeing: visibleSelects.dates }"
                    >
                        <div data-color="azul" data-size="14">
                            {{ prettyDatesLabel }}<i class="far fa-calendar ml-10"></i>
                        </div>

                        <div class="select-content left form">
                            <div class="form-group d-flex">
                                <p class="w-20 my-auto text">Inicial</p>

                                <div class="input-group ml-10 w-70">
                                    <input data-size="12" v-model="dates.start" @change="onDateChange" type="date" />
                                </div>
                            </div>

                            <div class="form-group d-flex">
                                <p class="w-20 my-auto text">Final</p>

                                <div class="input-group ml-10 w-70">
                                    <input data-size="12" v-model="dates.end" @change="onDateChange" type="date" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-center" data-gap="15">
                    <div
                        class="custom-select no-hover"
                        @click.stop="seeFilters('user')"
                        :class="{ seeing: visibleSelects.user }"
                        v-if="basicData && basicData.userList && selectedUser"
                    >
                        <div class="d-flex align-center">
                            <p class="opacity-5 my-auto mr-15">
                                {{ selectedUserLabel }}
                            </p>

                            <div class="profile-image" v-if="selectedUser.profileImage">
                                <img
                                    class="pointer h-50-px w-50-px"
                                    :src="'/assets/profile_images/' + selectedUser.profileImage"
                                    alt="Imagen de perfil de usuario"
                                />
                            </div>

                            <div class="initials verySmall" v-else>
                                {{ getInitials(selectedUserLabel) }}
                            </div>
                        </div>

                        <div class="select-content form">
                            <div class="form-group mb-10" @click.stop>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        v-model="agentSearch"
                                        placeholder="Buscar agente..."
                                        @click.stop
                                    />
                                </div>
                            </div>

                            <template v-if="filteredUsers.length">
                                <div
                                    v-for="(user, userIndex) in filteredUsers"
                                    :key="user._id"
                                    @click="selectUser(user)"
                                >
                                    <div class="d-flex align-center">
                                        <div class="profile-image mr-10" v-if="user.profileImage">
                                            <img
                                                class="pointer h-20-px w-20-px"
                                                :src="'/assets/profile_images/' + user.profileImage"
                                                alt="Imagen de perfil de usuario"
                                            />
                                        </div>

                                        <div class="initials verySmall mr-10" v-else>
                                            {{ getInitials(getUserLabel(user)) }}
                                        </div>

                                        <p class="text my-5">
                                            {{ getUserLabel(user) }}
                                        </p>
                                    </div>

                                    <p class="separator my-5" v-if="userIndex < filteredUsers.length - 1"></p>
                                </div>
                            </template>

                            <p v-else class="text opacity-5 my-5" data-size="12">No se encontraron agentes.</p>
                        </div>
                    </div>

                    <div
                        v-if="canManage('objectives.create')"
                        class="custom-button"
                        data-size="regular"
                        data-bg="principal"
                        @click.stop="openCreateModal"
                    >
                        <i class="fas fa-plus mr-10"></i> Añadir objetivo
                    </div>
                </div>
            </div>

            <div class="search-div d-flex mt-20">
                <div class="search-bar w-100">
                    <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                    <input
                        type="text"
                        placeholder="Buscar objetivos..."
                        v-model="objectiveSearch"
                        @click.stop
                    />
                </div>

                <i
                    data-color="principal"
                    class="pointer fas fa-trash my-10 ml-10"
                    @click.stop="resetObjectiveSearch"
                ></i>
            </div>

            <div class="loading-indicator mt-30" v-if="isLoading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <p class="text" data-size="14">Cargando objetivos...</p>
            </div>

            <template v-if="!isLoading && hasPageData">
                <div class="d-flex justify-between f-wrap mt-20 mb-30" data-gap="20" v-if="summary">
                    <div class="dashboard-card">
                        <div class="icon"><i class="far fa-file-lines"></i></div>

                        <div class="info">
                            <p class="title">Contratos</p>

                            <p class="value">
                                {{ summary.totalContracts }}
                                <span class="opacity-4" style="font-size: 16px">/ {{ formatCompact(summary.targetContracts) }}</span>
                            </p>

                            <div class="progress-bar mt-10" style="width: 160px">
                                <div :class="'prog-' + Math.min(100, summary.contractsPct || 0)"></div>
                            </div>

                            <p class="mt-5" data-size="12" :data-color="getPctColor(summary.contractsPct || 0)">
                                {{ summary.contractsPct || 0 }}% completado
                            </p>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="icon"><i class="far fa-lightbulb"></i></div>

                        <div class="info">
                            <p class="title">Consumo total</p>

                            <p class="value">{{ formatConsumption(summary.totalConsumption) }}</p>

                            <div class="progress-bar mt-10" style="width: 160px">
                                <div :class="'prog-' + Math.min(100, summary.consumptionPct || 0)"></div>
                            </div>

                            <p class="mt-5" data-size="12" :data-color="getPctColor(summary.consumptionPct || 0)">
                                {{ summary.consumptionPct || 0 }}% completado
                            </p>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="icon"><i class="far fa-chart-line"></i></div>

                        <div class="info">
                            <p class="title">% Cumplimiento global</p>

                            <p class="value" :data-color="getPctColor(summary.globalPct || 0)">
                                {{ summary.globalPct || 0 }}%
                            </p>

                            <div class="progress-bar mt-10" style="width: 160px">
                                <div :class="'prog-' + Math.min(100, summary.globalPct || 0)"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="graphs-container my-30" data-gap="20" v-if="hasContractsChart || hasConsumptionChart">
                    <div class="graph-card two" v-if="hasContractsChart">
                        <div class="title">Contratos por comercializadora</div>
                        <div class="w-100 h-300-px graph" ref="contractsChart" style="width: 100%; height: 300px;"></div>
                    </div>

                    <div class="graph-card two" v-if="hasConsumptionChart">
                        <div class="title">Evolución consumo (kWh)</div>
                        <div class="w-100 h-300-px graph" ref="consumptionChart" style="width: 100%; height: 300px;"></div>
                    </div>
                </div>

                <div v-if="objectiveSearch && !hasFilteredObjectives" class="objectives-empty-card objectives-search-empty my-30">
                    <div class="objectives-empty-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>

                    <div class="text mt-15" data-size="18" data-weight="700">Sin resultados</div>

                    <div class="text opacity-6 mt-10" data-size="14">
                        No hay objetivos que coincidan con “{{ objectiveSearch }}”.
                    </div>
                </div>

                <div class="objectives-table-card my-30" v-if="filteredGeneralObjectives.length">
                    <div class="d-flex justify-between align-center mb-20">
                        <div class="title mb-0">Objetivos generales</div>
                    </div>

                    <div class="objectives-table-head">
                        <div>Tipo</div>
                        <div>Usuario</div>
                        <div>Comercializadora</div>
                        <div>Fechas</div>
                        <div>Valor</div>
                        <div>Acciones</div>
                    </div>

                    <div
                        v-for="objective in filteredGeneralObjectives"
                        :key="objective._id"
                        class="objectives-table-row"
                    >
                        <div>
                            <span
                                class="objective-badge"
                                :class="objective.type === 'contracts' ? 'contracts' : 'consumption'"
                            >
                                {{ getObjectiveTypeLabel(objective.type) }}
                            </span>
                        </div>

                        <div>
                            <div class="text" data-weight="600">General</div>
                            <div class="text opacity-5 mt-5" data-size="12">Todos los agentes</div>
                        </div>

                        <div>
                            <span class="opacity-6">{{ objective.marketer || 'Todas' }}</span>
                        </div>

                        <div>
                            <span class="opacity-6">
                                {{ formatDate(objective.startDate) }} - {{ formatDate(objective.endDate) }}
                            </span>
                        </div>

                        <div>
                            <span class="objective-value-chip">
                                {{
                                    objective.type === 'contracts'
                                        ? formatCompact(objective.value)
                                        : formatConsumption(objective.value)
                                }}
                            </span>
                        </div>

                        <div>
                            <div class="objective-row-actions" v-if="canModifyObjectives">
                                <button
                                    v-if="canManage('objectives.edit')"
                                    class="objective-action-btn edit"
                                    @click.stop="editObjective(objective)"
                                >
                                    <i class="fas fa-pen"></i>
                                </button>

                                <button
                                    v-if="canManage('objectives.delete')"
                                    class="objective-action-btn delete"
                                    @click.stop="deleteObjective(objective)"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="objectives-table-card my-30" v-if="filteredPersonalObjectives.length">
                    <div class="d-flex justify-between align-center mb-20">
                        <div class="title mb-0">Objetivos por usuario</div>
                    </div>

                    <div class="objectives-table-head">
                        <div>Tipo</div>
                        <div>Usuario</div>
                        <div>Comercializadora</div>
                        <div>Fechas</div>
                        <div>Valor</div>
                        <div>Acciones</div>
                    </div>

                    <div
                        v-for="objective in filteredPersonalObjectives"
                        :key="objective._id"
                        class="objectives-table-row"
                    >
                        <div>
                            <span
                                class="objective-badge"
                                :class="objective.type === 'contracts' ? 'contracts' : 'consumption'"
                            >
                                {{ getObjectiveTypeLabel(objective.type) }}
                            </span>
                        </div>

                        <div>
                            <div class="text" data-weight="600">{{ objective.userName }}</div>
                            <div class="text opacity-5 mt-5" data-size="12">{{ objective.scopeLabel }}</div>
                        </div>

                        <div>
                            <span class="opacity-6">{{ objective.marketer || '-' }}</span>
                        </div>

                        <div>
                            <span class="opacity-6">
                                {{ formatDate(objective.startDate) }} - {{ formatDate(objective.endDate) }}
                            </span>
                        </div>

                        <div>
                            <span class="objective-value-chip">
                                {{
                                    objective.type === 'contracts'
                                        ? formatCompact(objective.value)
                                        : formatConsumption(objective.value)
                                }}
                            </span>
                        </div>

                        <div>
                            <div class="objective-row-actions" v-if="canModifyObjectives">
                                <button
                                    v-if="canManage('objectives.edit')"
                                    class="objective-action-btn edit"
                                    @click.stop="editObjective(objective)"
                                >
                                    <i class="fas fa-pen"></i>
                                </button>

                                <button
                                    v-if="canManage('objectives.delete')"
                                    class="objective-action-btn delete"
                                    @click.stop="deleteObjective(objective)"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="graph-card two mt-30" v-if="userRows.length">
                    <div class="d-flex justify-between align-center mb-20">
                        <div class="title mb-0">Detalle por usuario</div>

                        <div class="d-flex" data-gap="0">
                            <div
                                class="custom-button"
                                data-size="small"
                                :data-bg="activeTab === 'contracts' ? 'azul' : 'ventana-lateral'"
                                :data-color="activeTab === 'contracts' ? null : 'principal'"
                                style="border-radius: 10px 0 0 10px; filter: none; margin-right: -1px"
                                @click.stop="activeTab = 'contracts'"
                            >
                                Contratos
                            </div>

                            <div
                                class="custom-button"
                                data-size="small"
                                :data-bg="activeTab === 'consumption' ? 'azul' : 'ventana-lateral'"
                                :data-color="activeTab === 'consumption' ? null : 'principal'"
                                style="border-radius: 0 10px 10px 0; filter: none"
                                @click.stop="activeTab = 'consumption'"
                            >
                                Consumo
                            </div>
                        </div>
                    </div>

                    <div class="contact header-card obj-table-grid">
                        <div class="d-flex" data-color="principal">
                            <p class="text mr-5 ellipsis noWidth" data-weight="600">Usuario</p>
                        </div>

                        <div class="d-flex" data-color="principal">
                            <p class="text mr-5 ellipsis noWidth" data-weight="600">Real</p>
                        </div>

                        <div class="d-flex" data-color="principal">
                            <p class="text mr-5 ellipsis noWidth" data-weight="600">Objetivo</p>
                        </div>

                        <div class="d-flex" data-color="principal">
                            <p class="text mr-5 ellipsis noWidth" data-weight="600">Progreso</p>
                        </div>

                        <div class="d-flex" data-color="principal">
                            <p class="text mr-5 ellipsis noWidth" data-weight="600">%</p>
                        </div>
                    </div>

                    <div class="separator my-10"></div>

                    <div v-for="(userRow, userIndex) in userRows" :key="userRow._id || userIndex">
                        <div class="contact obj-table-grid pointer">
                            <div class="text ellipsis" data-weight="600" data-size="14">
                                {{ userRow.name }}
                            </div>

                            <div class="text" data-size="14">
                                {{ activeTab === 'contracts' ? userRow.contracts : formatConsumption(userRow.consumption) }}
                            </div>

                            <div class="text opacity-6" data-size="14">
                                {{ activeTab === 'contracts' ? formatCompact(userRow.targetContracts) : formatConsumption(userRow.targetConsumption) }}
                            </div>

                            <div class="progress-bar my-auto">
                                <div :class="'prog-' + getUserPct(userRow, activeTab)"></div>
                            </div>

                            <div
                                class="custom-button w-fit-content"
                                data-size="small"
                                data-mode="translucent"
                                :data-bg="getPctColor(getUserPct(userRow, activeTab))"
                            >
                                {{ getUserPct(userRow, activeTab) }}%
                            </div>
                        </div>

                        <div class="separator my-10" v-if="userIndex < userRows.length - 1"></div>
                    </div>
                </div>
            </template>

            <div v-if="!isLoading && !hasPageData" class="objectives-empty-card objectives-empty-card-desktop">
                <div class="objectives-empty-icon">
                    <i class="fa-solid fa-bullseye"></i>
                </div>

                <div class="text mt-15" data-size="22" data-weight="700">No hay objetivos</div>

                <div class="text opacity-6 mt-10" data-size="14">
                    Cree un objetivo para empezar a visualizar resultados, usuarios y gráficas.
                </div>

                <div
                    v-if="canManage('objectives.create')"
                    class="custom-button mt-20"
                    data-size="regular"
                    data-bg="principal"
                    @click.stop="openCreateModal"
                >
                    <i class="fas fa-plus mr-10"></i> Crear objetivo
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <Teleport to=".boxBody" v-if="isModalOpen">
            <div class="floating-box" @click.self="closeModal">
                <div class="register-pos round objective-modal" data-round="30">
                    <div class="d-flex justify-between align-center mb-20">
                        <div>
                            <p class="text mb-5" data-size="18" data-weight="700">
                                {{ editingObjective ? 'Editar objetivo' : 'Nuevo objetivo' }}
                            </p>

                            <p class="opacity-5" data-size="13">
                                Configure objetivos de contratos o consumo por rango de fechas.
                            </p>
                        </div>

                        <i class="fas fa-times pointer" data-size="18" @click.stop="closeModal"></i>
                    </div>

                    <div class="separator mb-20"></div>

                    <div class="form objective-form">
                        <div class="objective-block">
                            <p class="objective-block-title">¿Qué quiere medir?</p>

                            <div class="objective-type-grid">
                                <div
                                    class="objective-type-card"
                                    :class="{ selected: form.type === 'contracts' }"
                                    @click="form.type = 'contracts'; delete formErrors.type"
                                >
                                    <div class="objective-type-icon">
                                        <i class="far fa-file-lines"></i>
                                    </div>

                                    <div>
                                        <p class="objective-type-title">Contratos</p>
                                        <p class="objective-type-desc">Número de contratos por comercializadora</p>
                                    </div>
                                </div>

                                <div
                                    class="objective-type-card"
                                    :class="{ selected: form.type === 'consumption' }"
                                    @click="form.type = 'consumption'; form.marketer = ''; delete formErrors.type"
                                >
                                    <div class="objective-type-icon">
                                        <i class="far fa-lightbulb"></i>
                                    </div>

                                    <div>
                                        <p class="objective-type-title">Consumo</p>
                                        <p class="objective-type-desc">Consumo total en kWh de contratos de luz</p>
                                    </div>
                                </div>
                            </div>

                            <span v-if="formErrors.type" class="error d-block mt-5">{{ formErrors.type }}</span>
                        </div>

                        <div class="objective-block">
                            <p class="objective-block-title">Asignación</p>

                            <div class="objective-type-grid two-cols">
                                <div
                                    class="objective-type-card small"
                                    :class="{ selected: !form.userId }"
                                    @click="form.userId = ''"
                                >
                                    <div class="objective-type-icon">
                                        <i class="far fa-users"></i>
                                    </div>

                                    <div>
                                        <p class="objective-type-title">General</p>
                                        <p class="objective-type-desc">Aplica a todos los agentes</p>
                                    </div>
                                </div>

                                <div class="objective-type-card small" :class="{ selected: !!form.userId }">
                                    <div class="w-100">
                                        <div class="d-flex align-center mb-10">
                                            <div class="objective-type-icon mr-10">
                                                <i class="far fa-user"></i>
                                            </div>

                                            <div>
                                                <p class="objective-type-title">Por usuario</p>
                                                <p class="objective-type-desc">Asignar a un agente concreto</p>
                                            </div>
                                        </div>

                                        <div class="input-group">
                                            <select v-model="form.userId">
                                                <option value="">Seleccione un usuario</option>

                                                <option
                                                    v-for="user in availableUsersForForm"
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
                        </div>

                        <div class="objective-block">
                            <p class="objective-block-title">Configuración</p>

                            <div class="objective-dates-grid">
                                <div class="form-group" :class="{ wrong: formErrors.startDate }">
                                    <label>Fecha inicio</label>

                                    <div class="input-group">
                                        <input
                                            type="date"
                                            v-model="form.startDate"
                                            @focus="delete formErrors.startDate"
                                        />
                                    </div>

                                    <span v-if="formErrors.startDate" class="error">{{ formErrors.startDate }}</span>
                                </div>

                                <div class="form-group" :class="{ wrong: formErrors.endDate }">
                                    <label>Fecha fin</label>

                                    <div class="input-group">
                                        <input
                                            type="date"
                                            v-model="form.endDate"
                                            @focus="delete formErrors.endDate"
                                        />
                                    </div>

                                    <span v-if="formErrors.endDate" class="error">{{ formErrors.endDate }}</span>
                                </div>
                            </div>

                            <div class="form-group" v-if="form.type === 'contracts'">
                                <p><label>Comercializadora</label></p>

                                <div class="input-group">
                                    <select v-model="form.marketer">
                                        <option value="">Todas</option>

                                        <template v-if="marketers.length">
                                            <option
                                                v-for="marketer in marketers"
                                                :key="marketer._id || marketer.name"
                                                :value="marketer.name"
                                            >
                                                {{ marketer.name }}
                                            </option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group" :class="{ wrong: formErrors.value }">
                                <p>
                                    <label>{{ form.type === 'contracts' ? 'Nº contratos objetivo' : 'Consumo objetivo (kWh)' }}</label>
                                    <span data-color="rojo">*</span>
                                </p>

                                <div class="input-group">
                                    <input
                                        type="number"
                                        min="0"
                                        v-model.number="form.value"
                                        :placeholder="form.type === 'contracts' ? 'Ej: 50' : 'Ej: 150000'"
                                        @focus="delete formErrors.value"
                                    />
                                </div>

                                <span v-if="formErrors.value" class="error">{{ formErrors.value }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="separator my-20"></div>

                    <div class="d-flex justify-end objective-actions" data-gap="12">
                        <div
                            class="custom-button"
                            data-size="regular"
                            data-bg="rojo"
                            data-mode="translucent"
                            @click.stop="closeModal"
                        >
                            Cancelar
                        </div>

                        <div
                            class="custom-button"
                            data-size="regular"
                            data-bg="principal"
                            @click.stop="saveObjective"
                        >
                            <i class="fa-solid fa-spinner fa-spin mr-5" v-if="isSaving"></i>
                            {{ editingObjective ? 'Actualizar' : 'Guardar' }}
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
import * as am5 from "@amcharts/amcharts5";
import * as am5xy from "@amcharts/amcharts5/xy";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";

export default {
    name: "ObjectivesComponent",
    props: ["basicData"],

    data() {
        return {
            isLoading: false,
            isSaving: false,

            dates: {
                start: "",
                end: "",
            },

            visibleSelects: {
                dates: false,
                user: false,
            },

            selectedUser: undefined,
            selectedUserHierarchy: [],

            summary: null,
            userRows: [],
            contractsChartData: [],
            consumptionChartData: [],
            generalObjectives: [],
            personalObjectives: [],

            activeTab: "contracts",
            mobileUserOpen: null,

            isModalOpen: false,
            editingObjective: null,

            form: {
                type: "",
                startDate: "",
                endDate: "",
                marketer: "",
                value: null,
                userId: "",
            },

            formErrors: {},

            contractsChartRoot: null,
            consumptionChartRoot: null,

            fetchRequestId: 0,

            marketers: [],

            objectiveSearch: "",
            agentSearch: "",
        };
    },

    created() {
        this.dates.start = moment().startOf("month").format("YYYY-MM-DD");
        this.dates.end = moment().endOf("month").format("YYYY-MM-DD");

        if (this.basicData?.userList) {
            this.initSelectedUser();
        }

        this.fetchMarketers();
    },

    watch: {
        "basicData.userList"(newValue) {
            if (newValue && !this.selectedUser) {
                this.initSelectedUser();
            }
        },
    },

    methods: {
        async fetchMarketers() {
            try {
                const res = await axios.get("/api/marketers");
                this.marketers = res.data.marketers || [];
            } catch (error) {
                console.error("Error cargando comercializadoras:", error);
                this.marketers = [];
            }
        },

        initSelectedUser() {
            if (this.canManage("users.admiWhiHier")) {
                this.selectedUser = { ...this.basicData.userSubdomain };
                this.selectedUserHierarchy = [...(this.basicData.subdomainUserList || [])];
                this.fetchObjectives();
            } else {
                this.selectedUser = { ...this.basicData.userLogged };
                this.getUserHierarchy(this.basicData.userLogged._id);
            }
        },

        selectUser(user) {
            this.selectedUser = { ...user };
            this.mobileUserOpen = null;
            this.agentSearch = "";
            this.hideCustomSelects();
            this.resetObjectiveData();
            this.getUserHierarchy(user._id);
        },

        async getUserHierarchy(userId) {
            if (userId === this.basicData.userSubdomain._id) {
                this.selectedUserHierarchy = [...(this.basicData.subdomainUserList || [])];
                this.fetchObjectives();
                return;
            }

            try {
                const res = await axios.get("/api/user/getUserHierarchy/" + userId);
                this.selectedUserHierarchy = res.data.userHierarchy || [];
            } catch (error) {
                console.error(error);
                this.selectedUserHierarchy = [{ ...this.selectedUser }];
            } finally {
                this.fetchObjectives();
            }
        },

        resetObjectiveData() {
            this.summary = null;
            this.userRows = [];
            this.contractsChartData = [];
            this.consumptionChartData = [];
            this.generalObjectives = [];
            this.personalObjectives = [];
            this.mobileUserOpen = null;

            if (this.contractsChartRoot) {
                this.contractsChartRoot.dispose();
                this.contractsChartRoot = null;
            }

            if (this.consumptionChartRoot) {
                this.consumptionChartRoot.dispose();
                this.consumptionChartRoot = null;
            }
        },

        async fetchObjectives() {
            if (!this.selectedUser) return;

            const requestId = ++this.fetchRequestId;

            this.isLoading = true;
            this.resetObjectiveData();

            const hierarchyIds = this.selectedUser._id === this.basicData.userSubdomain._id
                ? (this.basicData.subdomainUserList || []).map(user => user._id)
                : (this.selectedUserHierarchy || []).map(user => user._id);

            if (
                this.selectedUser._id !== this.basicData.userSubdomain._id &&
                !hierarchyIds.includes(this.selectedUser._id)
            ) {
                hierarchyIds.unshift(this.selectedUser._id);
            }

            const userList = this.canManage("users.admiWhiHier")
                ? (this.basicData.subdomainUserList || this.basicData.userList || [])
                : (this.basicData.userList || []);

            let shouldLoadCharts = false;

            try {
                const res = await axios.post("/api/objectives/index", {
                    dates: {
                        start: this.dates.start,
                        end: this.dates.end,
                    },
                    userList,
                    userSubdomain: this.basicData.userSubdomain,
                    usersIds: hierarchyIds,
                    userSelected: this.selectedUser,
                });

                if (requestId !== this.fetchRequestId) return;

                this.summary = res.data.summary || null;
                this.userRows = res.data.users || [];
                this.contractsChartData = res.data.contractsChart || [];
                this.consumptionChartData = res.data.consumptionChart || [];
                this.generalObjectives = res.data.objectives?.general || [];
                this.personalObjectives = res.data.objectives?.personal || [];

                shouldLoadCharts = true;
            } catch (error) {
                if (requestId === this.fetchRequestId) {
                    console.error("Error cargando objetivos:", error);
                }
            } finally {
                if (requestId === this.fetchRequestId) {
                    this.isLoading = false;

                    if (shouldLoadCharts) {
                        await this.$nextTick();
                        this.loadContractsChart();
                        this.loadConsumptionChart();
                    }
                }
            }
        },

        loadContractsChart() {
            if (this.contractsChartRoot) {
                this.contractsChartRoot.dispose();
                this.contractsChartRoot = null;
            }

            if (!this.$refs.contractsChart || !this.contractsChartData?.length) return;

            const root = am5.Root.new(this.$refs.contractsChart);
            root.setThemes([am5themes_Animated.new(root)]);

            const chart = root.container.children.push(
                am5xy.XYChart.new(root, {
                    panX: false,
                    panY: false,
                    wheelX: "none",
                    wheelY: "none",
                    paddingLeft: 0,
                    layout: root.verticalLayout,
                })
            );

            chart.set("cursor", am5xy.XYCursor.new(root, { behavior: "none" }));

            const xAxis = chart.xAxes.push(
                am5xy.CategoryAxis.new(root, {
                    categoryField: "marketer",
                    renderer: am5xy.AxisRendererX.new(root, { minGridDistance: 30 }),
                    tooltip: am5.Tooltip.new(root, {}),
                })
            );

            xAxis.get("renderer").labels.template.setAll({ fontSize: "12px" });
            xAxis.data.setAll(this.contractsChartData);

            const yAxis = chart.yAxes.push(
                am5xy.ValueAxis.new(root, {
                    min: 0,
                    renderer: am5xy.AxisRendererY.new(root, { strokeOpacity: 0.1 }),
                })
            );

            yAxis.get("renderer").labels.template.setAll({ fontSize: "12px" });

            const targetSeries = chart.series.push(
                am5xy.ColumnSeries.new(root, {
                    name: "Objetivo",
                    xAxis,
                    yAxis,
                    valueYField: "target",
                    categoryXField: "marketer",
                    tooltip: am5.Tooltip.new(root, { labelText: "Objetivo: {valueY}" }),
                })
            );

            targetSeries.columns.template.setAll({
                width: am5.percent(60),
                fill: am5.color("#012C6822"),
                stroke: am5.color("#012C68"),
                strokeWidth: 1,
                cornerRadiusTL: 6,
                cornerRadiusTR: 6,
                tooltipY: 0,
            });

            targetSeries.data.setAll(this.contractsChartData);

            const realSeries = chart.series.push(
                am5xy.ColumnSeries.new(root, {
                    name: "Real",
                    xAxis,
                    yAxis,
                    valueYField: "real",
                    categoryXField: "marketer",
                    tooltip: am5.Tooltip.new(root, { labelText: "Real: {valueY}" }),
                })
            );

            realSeries.columns.template.setAll({
                width: am5.percent(40),
                fill: am5.color("#2192FF"),
                stroke: am5.color("#2192FF"),
                cornerRadiusTL: 6,
                cornerRadiusTR: 6,
                tooltipY: 0,
            });

            realSeries.data.setAll(this.contractsChartData);

            const legend = chart.children.push(am5.Legend.new(root, { centerX: am5.p50, x: am5.p50 }));
            legend.labels.template.setAll({ fontSize: "12px" });
            legend.data.setAll(chart.series.values);

            chart.appear(1000, 100);

            if (root._logo) root._logo.dispose();

            this.contractsChartRoot = root;
        },

        loadConsumptionChart() {
            if (this.consumptionChartRoot) {
                this.consumptionChartRoot.dispose();
                this.consumptionChartRoot = null;
            }

            if (!this.$refs.consumptionChart || !this.consumptionChartData?.length) return;

            const root = am5.Root.new(this.$refs.consumptionChart);
            root.setThemes([am5themes_Animated.new(root)]);

            const chart = root.container.children.push(
                am5xy.XYChart.new(root, {
                    panX: true,
                    panY: false,
                    wheelX: "panX",
                    wheelY: "zoomX",
                    pinchZoomX: true,
                    paddingLeft: 0,
                })
            );

            const cursor = chart.set("cursor", am5xy.XYCursor.new(root, { behavior: "zoomX" }));
            cursor.lineY.set("visible", false);

            const xAxis = chart.xAxes.push(
                am5xy.CategoryAxis.new(root, {
                    categoryField: "period",
                    renderer: am5xy.AxisRendererX.new(root, { minGridDistance: 70 }),
                    tooltip: am5.Tooltip.new(root, {}),
                })
            );

            xAxis.get("renderer").labels.template.setAll({ fontSize: "12px" });
            xAxis.data.setAll(this.consumptionChartData);

            const yAxis = chart.yAxes.push(
                am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, { pan: "zoom" }),
                })
            );

            yAxis.get("renderer").labels.template.setAll({ fontSize: "12px" });

            const targetSeries = chart.series.push(
                am5xy.LineSeries.new(root, {
                    name: "Objetivo",
                    xAxis,
                    yAxis,
                    valueYField: "target",
                    categoryXField: "period",
                    stroke: am5.color("#012C68"),
                    tooltip: am5.Tooltip.new(root, { labelText: "Objetivo: {valueY}" }),
                })
            );

            targetSeries.strokes.template.setAll({ strokeWidth: 2, strokeDasharray: [6, 3] });
            targetSeries.data.setAll(this.consumptionChartData);

            const realSeries = chart.series.push(
                am5xy.LineSeries.new(root, {
                    name: "Real",
                    xAxis,
                    yAxis,
                    valueYField: "real",
                    categoryXField: "period",
                    stroke: am5.color("#ffcd38"),
                    fill: am5.color("#ffcd38"),
                    tooltip: am5.Tooltip.new(root, { labelText: "Real: {valueY}" }),
                })
            );

            realSeries.strokes.template.setAll({ strokeWidth: 3 });
            realSeries.fills.template.setAll({
                visible: true,
                fillGradient: am5.LinearGradient.new(root, {
                    stops: [
                        { color: am5.color("#ffcd38"), opacity: 0.4 },
                        { color: am5.color("#ffcd38"), opacity: 0 },
                    ],
                    rotation: 90,
                }),
            });

            realSeries.data.setAll(this.consumptionChartData);

            const legend = chart.children.push(am5.Legend.new(root, { centerX: am5.p50, x: am5.p50 }));
            legend.labels.template.setAll({ fontSize: "12px" });
            legend.data.setAll(chart.series.values);

            chart.appear(1000, 100);

            if (root._logo) root._logo.dispose();

            this.consumptionChartRoot = root;
        },

        openCreateModal() {
            this.closeModal();

            this.form.startDate = this.dates.start;
            this.form.endDate = this.dates.end;

            this.isModalOpen = true;
        },

        closeModal() {
            this.isModalOpen = false;
            this.editingObjective = null;

            this.form = {
                type: "",
                startDate: this.dates.start,
                endDate: this.dates.end,
                marketer: "",
                value: null,
                userId: "",
            };

            this.formErrors = {};
        },

        async saveObjective() {
            if (!this.validateForm()) return;

            this.isSaving = true;

            try {
                const payload = {
                    type: this.form.type,
                    startDate: this.form.startDate,
                    endDate: this.form.endDate,
                    marketer: this.form.type === "contracts" ? (this.form.marketer || null) : null,
                    value: this.form.value,
                    userId: this.form.userId || null,
                    userSubdomain: this.basicData.userSubdomain._id,
                };

                if (this.editingObjective) {
                    await axios.post(`/api/objectives/${this.editingObjective._id}`, payload);

                    Swal.fire({
                        icon: "success",
                        title: "¡Objetivo actualizado!",
                        timer: 1500,
                        timerProgressBar: true,
                    });
                } else {
                    await axios.post("/api/objectives", payload);

                    Swal.fire({
                        icon: "success",
                        title: "¡Objetivo creado!",
                        timer: 1500,
                        timerProgressBar: true,
                    });
                }

                this.closeModal();
                this.fetchObjectives();
            } catch (error) {
                console.error(error);

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No se pudo guardar el objetivo.",
                });
            } finally {
                this.isSaving = false;
            }
        },

        editObjective(objective) {
            this.editingObjective = objective;

            this.form = {
                type: objective.type || "",
                startDate: objective.startDate || this.dates.start,
                endDate: objective.endDate || this.dates.end,
                marketer: objective.marketer || "",
                value: objective.value || null,
                userId: objective.userId || "",
            };

            this.isModalOpen = true;
        },

        deleteObjective(objective) {
            if (!objective?._id) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No se ha podido obtener el ID del objetivo.",
                });

                return;
            }

            Swal.fire({
                icon: "warning",
                title: "¿Estás seguro?",
                text: "Si sigues con esta acción no podrás revertirla",
                confirmButtonText: "Sí",
                showConfirmButton: true,
                cancelButtonText: "No",
                showCancelButton: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/api/objectives/${objective._id}`)
                        .then(() => {
                            this.fetchObjectives();

                            Swal.fire({
                                icon: "success",
                                title: "¡Objetivo eliminado!",
                                timer: 1500,
                                timerProgressBar: true,
                            });
                        })
                        .catch((error) => {
                            console.error(error);

                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "No se pudo eliminar el objetivo.",
                            });
                        });
                }
            });
        },

        validateForm() {
            this.formErrors = {};
            let isValid = true;

            if (!this.form.type) {
                this.formErrors.type = "Este campo no puede estar vacío";
                isValid = false;
            }

            if (!this.form.startDate) {
                this.formErrors.startDate = "Este campo no puede estar vacío";
                isValid = false;
            }

            if (!this.form.endDate) {
                this.formErrors.endDate = "Este campo no puede estar vacío";
                isValid = false;
            }

            if (this.form.startDate && this.form.endDate && this.form.startDate > this.form.endDate) {
                this.formErrors.endDate = "La fecha final no puede ser anterior a la inicial";
                isValid = false;
            }

            if (!this.form.value || this.form.value <= 0) {
                this.formErrors.value = "Introduce un valor mayor a 0";
                isValid = false;
            }

            return isValid;
        },

        onDateChange() {
            if (this.dates.start && this.dates.end && this.dates.start > this.dates.end) {
                this.dates.end = this.dates.start;
            }

            this.fetchObjectives();
        },

        toggleMobileUser(userIndex) {
            this.mobileUserOpen = this.mobileUserOpen === userIndex ? null : userIndex;
        },

        seeFilters(type) {
            this.hideCustomSelects();

            if (type === "dates") this.visibleSelects.dates = true;
            if (type === "user") this.visibleSelects.user = true;
        },

        hideCustomSelects() {
            this.visibleSelects = {
                dates: false,
                user: false,
            };
        },

        resetObjectiveSearch() {
            this.objectiveSearch = "";
        },

        safePct(real, target) {
            const denominator = Number(target || 0);

            if (denominator <= 0) return 0;

            return Math.min(100, Math.round((Number(real || 0) / denominator) * 100));
        },

        getUserPct(userRow, type) {
            return type === "contracts"
                ? this.safePct(userRow.contracts, userRow.targetContracts)
                : this.safePct(userRow.consumption, userRow.targetConsumption);
        },

        getPctColor(pct) {
            if (pct >= 100) return "success";
            if (pct >= 70) return "azul";
            return "amarillo";
        },

        getObjectiveTypeLabel(type) {
            if (type === "contracts") return "Contratos";
            if (type === "consumption") return "Consumo";
            return "-";
        },

        formatConsumption(value) {
            const numeric = Number(value || 0);

            if (!numeric) return "0 kWh";

            if (numeric >= 1000000) {
                return Math.round(numeric / 1000).toLocaleString("es-ES") + " MWh";
            }

            return Math.round(numeric).toLocaleString("es-ES") + " kWh";
        },

        formatCompact(value) {
            const numeric = Number(value || 0);

            if (!numeric) return "0";

            return Number.isInteger(numeric)
                ? numeric.toLocaleString("es-ES")
                : numeric.toLocaleString("es-ES", { maximumFractionDigits: 2 });
        },

        formatDate(date) {
            if (!date) return "-";

            return moment(date).format("DD/MM/YYYY");
        },

        normalizeText(value) {
            return String(value || "")
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .trim();
        },

        objectiveMatchesSearch(objective) {
            const term = this.normalizeText(this.objectiveSearch);

            if (!term) return true;

            const text = [
                this.getObjectiveTypeLabel(objective.type),
                objective.userName,
                objective.scopeLabel,
                objective.marketer || "Todas",
                objective.startDate,
                objective.endDate,
                objective.value,
            ].join(" ");

            return this.normalizeText(text).includes(term);
        },

        getUserLabel(user) {
            if (!user) return "";

            if (user._id === this.basicData?.userSubdomain?._id) {
                return "Todos los agentes";
            }

            return `${user.firstName || ""} ${user.lastName || ""}`.trim();
        },

        getInitials(name) {
            if (!name) return "-";

            const parts = String(name).trim().split(/\s+/);
            let initials = parts[0]?.[0] || "";

            if (parts[1]) {
                initials += parts[1][0];
            }

            return initials.toUpperCase();
        },

        canManage(code) {
            const user = this.basicData?.userLogged;
            const subdomain = this.basicData?.userSubdomain;

            if (!user || !subdomain) return false;

            const label = user.label;
            const permissions = subdomain.labels_permissions;

            if (!label || !permissions) return false;
            if (!permissions[label]) return false;
            if (!code?.includes(".")) return false;

            const [resource, action] = code.split(".");

            if (!permissions[label][resource]) return false;

            return permissions[label][resource].includes(action);
        },
    },

    computed: {
        selectedUserLabel() {
            return this.getUserLabel(this.selectedUser);
        },

        prettyDatesLabel() {
            const start = this.dates.start ? moment(this.dates.start).format("DD/MM/YY") : "";
            const end = this.dates.end ? moment(this.dates.end).format("DD/MM/YY") : "";

            if (!this.dates.start && !this.dates.end) return "Seleccione fechas";
            if (!this.dates.end) return "Desde " + start;
            if (!this.dates.start) return "Hasta " + end;

            return start + " - " + end;
        },

        availableUsers() {
            const users = [...(this.basicData?.userList || [])];

            if (this.canManage("users.admiWhiHier") && this.basicData?.userSubdomain) {
                users.unshift(this.basicData.userSubdomain);
            }

            return users;
        },

        availableUsersForForm() {
            return this.basicData?.userList || [];
        },

        filteredUsers() {
            const term = this.normalizeText(this.agentSearch);

            return this.availableUsers
                .filter((user) => {
                    if (!term) return true;

                    return this.normalizeText(this.getUserLabel(user)).includes(term) ||
                        this.normalizeText(user.email || "").includes(term);
                })
                .sort((a, b) => {
                    if (a._id === this.basicData?.userSubdomain?._id) return -1;
                    if (b._id === this.basicData?.userSubdomain?._id) return 1;

                    return (a.firstName || "").localeCompare(b.firstName || "");
                });
        },

        filteredGeneralObjectives() {
            return (this.generalObjectives || []).filter((objective) => this.objectiveMatchesSearch(objective));
        },

        filteredPersonalObjectives() {
            return (this.personalObjectives || []).filter((objective) => this.objectiveMatchesSearch(objective));
        },

        hasFilteredObjectives() {
            return this.filteredGeneralObjectives.length > 0 || this.filteredPersonalObjectives.length > 0;
        },

        hasPageData() {
            return this.generalObjectives.length > 0 ||
                this.personalObjectives.length > 0 ||
                this.userRows.length > 0 ||
                this.hasContractsChart ||
                this.hasConsumptionChart;
        },

        hasContractsChart() {
            return Array.isArray(this.contractsChartData) && this.contractsChartData.length > 0;
        },

        hasConsumptionChart() {
            return Array.isArray(this.consumptionChartData) && this.consumptionChartData.length > 0;
        },

        canModifyObjectives() {
            return this.canManage("objectives.edit") || this.canManage("objectives.delete");
        },
    },

    beforeUnmount() {
        if (this.contractsChartRoot) this.contractsChartRoot.dispose();
        if (this.consumptionChartRoot) this.consumptionChartRoot.dispose();
    },
};
</script>

<style scoped>
.obj-table-grid {
    display: grid !important;
    grid-template-columns: 1.6fr 1fr 1fr 2fr 0.7fr !important;
    gap: 10px;
    align-items: center;
    padding: 8px 16px;
}

.loading-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 20px 0;
    justify-content: center;
    opacity: 0.6;
}

.objectives-search-empty {
    min-height: 210px;
}

.objective-modal {
    width: 620px;
    max-width: calc(100vw - 40px);
    height: auto;
    padding: 36px;
}

.objective-form {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.objective-block {
    padding: 18px;
    border: 1px solid #e9edf5;
    border-radius: 18px;
    background: #fafcff;
}

.objective-block-title {
    margin: 0 0 14px 0;
    font-size: 14px;
    font-weight: 700;
    color: #012c68;
}

.objective-type-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.objective-type-grid.two-cols {
    grid-template-columns: 1fr 1fr;
}

.objective-type-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px;
    border-radius: 16px;
    border: 1px solid #dbe5f2;
    background: #ffffff;
    cursor: pointer;
    min-height: 90px;
    transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.objective-type-card.selected {
    border-color: #2192ff;
    background: #f4f9ff;
    box-shadow: 0 0 0 3px rgba(33, 146, 255, 0.08);
}

.objective-type-card.small {
    min-height: 110px;
}

.objective-type-icon {
    min-width: 38px;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: #eef4fb;
    color: #012c68;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.objective-type-title {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 700;
    color: #1c2740;
}

.objective-type-desc {
    margin: 0;
    font-size: 12px;
    line-height: 1.45;
    color: rgba(28, 39, 64, 0.65);
}

.objective-dates-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.objective-actions {
    flex-wrap: wrap;
}

.objectives-empty-card {
    width: 100%;
    min-height: 280px;
    border-radius: 22px;
    border: 1px dashed #c9d8ea;
    background: linear-gradient(180deg, #fbfdff 0%, #f4f8fc 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    text-align: center;
    padding: 30px 24px;
}

.objectives-empty-card-desktop {
    min-height: 360px;
    margin-top: 30px;
}

.objectives-empty-icon {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eaf3ff;
    color: #0f62fe;
    font-size: 28px;
    box-shadow: inset 0 0 0 1px rgba(15, 98, 254, 0.08);
}

.objectives-table-card {
    background: #fff;
    border: 1px solid #e6edf5;
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.04);
}

.objectives-table-head,
.objectives-table-row {
    display: grid;
    grid-template-columns: 0.9fr 1.15fr 1.05fr 1.15fr 0.85fr 0.7fr;
    gap: 14px;
    align-items: center;
}

.objectives-table-head {
    padding: 0 6px 14px;
    border-bottom: 1px solid #eef3f8;
    font-size: 12px;
    font-weight: 700;
    color: #6b7a90;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.objectives-table-row {
    margin-top: 12px;
    padding: 16px 18px;
    border: 1px solid #edf2f7;
    border-radius: 18px;
    background: #fbfdff;
}

.objective-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 98px;
    padding: 7px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}

.objective-badge.contracts {
    background: rgba(15, 98, 254, 0.1);
    color: #0f62fe;
}

.objective-badge.consumption {
    background: rgba(245, 158, 11, 0.14);
    color: #b45309;
}

.objective-value-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 12px;
    border-radius: 12px;
    background: #eef5ff;
    color: #123b7a;
    font-size: 12px;
    font-weight: 700;
}

.objective-row-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.objective-action-btn {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.objective-action-btn.edit {
    background: rgba(15, 98, 254, 0.1);
    color: #0f62fe;
}

.objective-action-btn.delete {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.objective-list-card {
    background: #ffffff;
    border: 1px solid #e9eef5;
    border-radius: 16px;
    padding: 14px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
}

.mobile-dates {
    background: #f8fbff;
    border: 1px solid #e6eef8;
    border-radius: 16px;
    padding: 12px;
}

@media (max-width: 768px) {
    .objective-modal {
        width: calc(100vw - 24px);
        padding: 22px;
        border-radius: 22px;
    }

    .objective-type-grid,
    .objective-type-grid.two-cols,
    .objective-dates-grid {
        grid-template-columns: 1fr;
    }

    .objective-type-card {
        min-height: auto;
    }

    .objectives-table-head,
    .objectives-table-row {
        grid-template-columns: 1fr;
    }

    .objectives-table-head {
        display: none;
    }

    .objectives-table-card {
        padding: 16px;
    }

    .obj-table-grid {
        grid-template-columns: 1.3fr 1fr 1fr 1.4fr 0.8fr !important;
        gap: 8px;
        padding: 8px 10px;
    }
}
</style>