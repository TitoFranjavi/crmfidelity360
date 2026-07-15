<template>
    <section class="q-page">
        <!-- NAV -->
        <nav class="q-nav">
            <a href="/" class="q-logo">
                <img
                    src="https://segenet.es/wp-content/uploads/isotipo_2.png"
                    alt="Segenet"
                    onerror="this.style.display = 'none'"
                />
                <span class="q-wordmark">Segenet <em>Movilidad</em></span>
            </a>
            <a href="https://movilidad.segenet.es" class="q-back" target="_top">
                <i class="far fa-arrow-left"></i> Volver
            </a>
        </nav>

        <!-- PAGO EXITOSO -->
        <div v-if="isPaymentSuccessPage" class="ok-wrap">
            <div class="ok-card">
                <div class="ok-icon"><i class="far fa-circle-check"></i></div>
                <h1>Pago recibido</h1>
                <p>Te hemos enviado el recibo por email.</p>
                <div
                    class="ok-rows"
                    v-if="!isLoadingPaymentSuccess && paymentSuccessData"
                >
                    <div v-if="paymentSuccessData?.charger?.name">
                        <span>Cargador</span
                        ><strong>{{ paymentSuccessData.charger.name }}</strong>
                    </div>
                    <div v-if="paymentSuccessData?.amounts?.total">
                        <span>Total</span
                        ><strong
                            >{{
                                formatCurrency(paymentSuccessData.amounts.total)
                            }}
                            €</strong
                        >
                    </div>
                </div>
                <a
                    v-if="paymentSuccessData?.stripe?.receipt_url"
                    :href="paymentSuccessData.stripe.receipt_url"
                    target="_blank"
                    class="ok-btn-sec"
                    >Ver recibo</a
                >
                <button class="ok-btn" @click="goBackToChargerForm">
                    Volver al configurador
                </button>
            </div>
        </div>

        <template v-else>
            <div v-if="isLoadingOpportunity" class="q-loading">
                <i class="fa-regular fa-spinner-third fa-spin"></i>
                <span>Cargando...</span>
            </div>

            <div v-else class="q-layout">
                <!-- ══════ COLUMNA FORMULARIO ══════ -->
                <div class="q-form">
                    <!-- Barra de progreso -->
                    <div class="q-bar">
                        <div class="q-bar-steps">
                            <span
                                v-for="(l, i) in stepLabels"
                                :key="i"
                                class="q-bar-dot"
                                :class="{
                                    active: currentStep === i + 1,
                                    done: currentStep > i + 1,
                                }"
                                @click="currentStep > i + 1 && goToStep(i + 1)"
                            >
                                <span class="q-dot-num">
                                    <i
                                        v-if="currentStep > i + 1"
                                        class="fas fa-check"
                                    ></i>
                                    <span v-else>{{ i + 1 }}</span>
                                </span>
                                <span class="q-dot-lbl">{{ l }}</span>
                            </span>
                        </div>
                        <div class="q-bar-line">
                            <div
                                class="q-bar-fill"
                                :style="{
                                    width:
                                        ((currentStep - 1) /
                                            (stepLabels.length - 1)) *
                                            100 +
                                        '%',
                                }"
                            ></div>
                        </div>
                    </div>

                    <!-- ─── PASO 1: Tipo de instalación ─── -->
                    <div v-if="currentStep === 1" class="q-step">
                        <div class="q-step-head">
                            <div class="q-step-num">01</div>
                            <div>
                                <h2>¿Dónde vas a instalar<br />el cargador?</h2>
                                <p>Selecciona el tipo de instalación</p>
                            </div>
                        </div>

                        <div class="q-type-grid">
                            <button
                                class="q-type-card"
                                :class="{
                                    active:
                                        formData.installationType === 'house',
                                }"
                                @click="
                                    formData.installationType = 'house';
                                    goToStep(2);
                                "
                            >
                                <div class="qtc-icon-wrap">
                                    <i class="far fa-house-chimney"></i>
                                </div>
                                <div class="qtc-body">
                                    <strong>Casa / Vivienda</strong>
                                    <span
                                        >Garaje o parking privado
                                        individual</span
                                    >
                                </div>
                                <div class="qtc-check">
                                    <i class="fas fa-circle-check"></i>
                                </div>
                            </button>

                            <button
                                class="q-type-card"
                                :class="{
                                    active:
                                        formData.installationType ===
                                        'community',
                                }"
                                @click="
                                    formData.installationType = 'community';
                                    goToStep(2);
                                "
                            >
                                <div class="qtc-icon-wrap">
                                    <i class="far fa-building"></i>
                                </div>
                                <div class="qtc-body">
                                    <strong>Garaje comunitario</strong>
                                    <span>Plaza en parking de comunidad</span>
                                </div>
                                <div class="qtc-check">
                                    <i class="fas fa-circle-check"></i>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- ─── PASO 2: Cargador ─── -->
                    <div v-if="currentStep === 2" class="q-step">
                        <div class="q-step-head">
                            <div class="q-step-num">02</div>
                            <div>
                                <h2>Elige tu cargador</h2>
                                <p>Selecciona el modelo que más te convenga</p>
                            </div>
                        </div>

                        <!-- Recomendados -->
                        <div class="q-section-label">
                            <i class="fas fa-star"></i> Recomendados
                        </div>
                        <div class="q-rec-list">
                            <button
                                v-for="c in recommendedChargers"
                                :key="c.chargerModel"
                                class="q-charger-row q-charger-row--rec"
                                :class="{
                                    active:
                                        formData.selectedCharger
                                            ?.chargerModel === c.chargerModel,
                                }"
                                @click="
                                    selectCharger(c);
                                    goToStep(3);
                                "
                            >
                                <div
                                    class="qcr-brand"
                                    :style="{
                                        background: getBrandColor(c.brand),
                                    }"
                                >
                                    {{ c.brand.substring(0, 2) }}
                                </div>
                                <div class="qcr-info">
                                    <span class="qcr-name">{{ c.name }}</span>
                                    <span class="qcr-specs"
                                        >{{ c.chargerPower }} ·
                                        {{ getPhaseLabel(c.phase) }}</span
                                    >
                                </div>
                                <div class="qcr-right">
                                    <span class="qcr-price"
                                        >{{ formatCurrency(c.pvp) }} €</span
                                    >
                                    <a
                                        v-if="c.pdf"
                                        :href="c.pdf"
                                        target="_blank"
                                        @click.stop
                                        class="qcr-pdf"
                                        ><i class="far fa-file-pdf"></i
                                    ></a>
                                </div>
                                <div class="qcr-check">
                                    <i class="fas fa-circle-check"></i>
                                </div>
                            </button>
                        </div>

                        <!-- Toggle ver todos -->
                        <button
                            class="q-toggle-all"
                            @click="showAllChargers = !showAllChargers"
                        >
                            <i
                                :class="
                                    showAllChargers
                                        ? 'far fa-chevron-up'
                                        : 'far fa-chevron-down'
                                "
                            ></i>
                            {{
                                showAllChargers
                                    ? "Ocultar otros modelos"
                                    : "Ver todos los modelos (" +
                                      otherChargers.length +
                                      ")"
                            }}
                        </button>

                        <!-- Lista completa (colapsable) -->
                        <div v-if="showAllChargers" class="q-all-chargers">
                            <div class="q-brand-filter">
                                <button
                                    class="q-brand-btn"
                                    :class="{
                                        active: selectedBrandFilter === '',
                                    }"
                                    @click="selectedBrandFilter = ''"
                                >
                                    Todos
                                </button>
                                <button
                                    v-for="b in availableBrands"
                                    :key="b"
                                    class="q-brand-btn"
                                    :class="{
                                        active: selectedBrandFilter === b,
                                    }"
                                    @click="selectedBrandFilter = b"
                                >
                                    {{ b }}
                                </button>
                            </div>
                            <div class="q-all-list">
                                <button
                                    v-for="c in filteredOtherChargers"
                                    :key="c.chargerModel"
                                    class="q-charger-row"
                                    :class="{
                                        active:
                                            formData.selectedCharger
                                                ?.chargerModel ===
                                            c.chargerModel,
                                    }"
                                    @click="
                                        selectCharger(c);
                                        goToStep(3);
                                    "
                                >
                                    <div
                                        class="qcr-brand qcr-brand--sm"
                                        :style="{
                                            background: getBrandColor(c.brand),
                                        }"
                                    >
                                        {{ c.brand.substring(0, 2) }}
                                    </div>
                                    <div class="qcr-info">
                                        <span class="qcr-name">{{
                                            c.name
                                        }}</span>
                                        <span class="qcr-specs"
                                            >{{ c.chargerPower
                                            }}<template v-if="c.phase">
                                                ·
                                                {{
                                                    getPhaseLabel(c.phase)
                                                }}</template
                                            ></span
                                        >
                                    </div>
                                    <div class="qcr-right">
                                        <span class="qcr-price"
                                            >{{ formatCurrency(c.pvp) }} €</span
                                        >
                                        <a
                                            v-if="c.pdf"
                                            :href="c.pdf"
                                            target="_blank"
                                            @click.stop
                                            class="qcr-pdf"
                                            ><i class="far fa-file-pdf"></i
                                        ></a>
                                    </div>
                                    <div class="qcr-check">
                                        <i class="fas fa-circle-check"></i>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div class="q-foot" style="justify-content: flex-start">
                            <button class="q-btn-back" @click="goToStep(1)">
                                <i class="far fa-arrow-left"></i> Atrás
                            </button>
                        </div>
                    </div>

                    <!-- ─── PASO 3: Instalación ─── -->
                    <div v-if="currentStep === 3" class="q-step">
                        <div class="q-step-head">
                            <div class="q-step-num">03</div>
                            <div>
                                <h2>Detalles de la instalación</h2>
                                <p>
                                    Indica la distancia y si tienes paneles
                                    solares
                                </p>
                            </div>
                        </div>

                        <div class="q-section-label">
                            Metros de cable eléctrico
                        </div>
                        <div class="q-meters-grid">
                            <button
                                v-for="m in meterPresets"
                                :key="m.val"
                                class="q-meter-btn"
                                :class="{
                                    active:
                                        Number(formData.cableMeters) === m.val,
                                }"
                                @click="
                                    formData.cableMeters = m.val;
                                    formData.hasAutoconsumo && goToStep(4);
                                "
                            >
                                <span class="qmb-val">{{ m.label }}</span>
                                <span class="qmb-desc">{{ m.desc }}</span>
                            </button>
                        </div>
                        <div class="q-meter-custom">
                            <label>O introduce los metros exactos:</label>
                            <div class="q-meter-input-wrap">
                                <input
                                    v-model="formData.cableMeters"
                                    type="number"
                                    min="1"
                                    max="70"
                                    placeholder="0"
                                />
                                <span>metros</span>
                            </div>
                        </div>
                        <p class="q-hint">
                            <i class="far fa-circle-info"></i> Si no estás
                            seguro, elige la opción más cercana. El instalador
                            ajustará el presupuesto final.
                        </p>

                        <div class="q-section-label" style="margin-top: 24px">
                            ¿Tienes autoconsumo solar?
                        </div>
                        <div class="q-solar-grid">
                            <button
                                class="q-solar-btn"
                                :class="{
                                    active: formData.hasAutoconsumo === 'yes',
                                }"
                                @click="
                                    formData.hasAutoconsumo = 'yes';
                                    effectiveCableMeters > 0 && goToStep(4);
                                "
                            >
                                <i class="far fa-solar-panel"></i> Sí, tengo
                                paneles
                            </button>
                            <button
                                class="q-solar-btn"
                                :class="{
                                    active: formData.hasAutoconsumo === 'no',
                                }"
                                @click="
                                    formData.hasAutoconsumo = 'no';
                                    effectiveCableMeters > 0 && goToStep(4);
                                "
                            >
                                <i class="far fa-xmark"></i> No tengo
                            </button>
                            <button
                                class="q-solar-btn"
                                :class="{
                                    active:
                                        formData.hasAutoconsumo === 'unknown',
                                }"
                                @click="
                                    formData.hasAutoconsumo = 'unknown';
                                    effectiveCableMeters > 0 && goToStep(4);
                                "
                            >
                                <i class="far fa-question"></i> No lo sé
                            </button>
                        </div>

                        <div class="q-section-label" style="margin-top: 24px">
                            ¿Es tu vivienda habitual?
                        </div>
                        <div class="q-solar-grid">
                            <button
                                class="q-solar-btn q-solar-btn--iva"
                                :class="{
                                    active:
                                        formData.isHabitualResidence ===
                                        'yes',
                                }"
                                @click="formData.isHabitualResidence = 'yes'"
                            >
                                <i class="far fa-house-user"></i> Sí, vivienda
                                habitual
                            </button>
                            <button
                                class="q-solar-btn"
                                :class="{
                                    active:
                                        formData.isHabitualResidence === 'no',
                                }"
                                @click="formData.isHabitualResidence = 'no'"
                            >
                                <i class="far fa-xmark"></i> No
                            </button>
                        </div>
                        <p class="q-hint">
                            <i class="far fa-circle-info"></i> Si la
                            instalación es en tu vivienda habitual (con más de
                            2 años de antigüedad), puede aplicarse el
                            <strong>IVA reducido del 10%</strong> en lugar del
                            21% general. Lo confirmaremos contigo antes de
                            emitir el presupuesto definitivo.
                        </p>

                        <div class="q-foot">
                            <button class="q-btn-back" @click="goToStep(2)">
                                <i class="far fa-arrow-left"></i> Atrás
                            </button>
                            <button
                                class="q-btn-next"
                                :disabled="!canProceedStep3"
                                @click="canProceedStep3 && goToStep(4)"
                            >
                                Siguiente <i class="far fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ─── PASO 4: Datos ─── -->
                    <div v-if="currentStep === 4" class="q-step">
                        <div class="q-step-head">
                            <div class="q-step-num">04</div>
                            <div>
                                <h2>Tus datos de contacto</h2>
                                <p>Para enviarte el presupuesto en PDF</p>
                            </div>
                        </div>

                        <div v-if="opportunity" class="q-client-loaded">
                            <i class="far fa-user-check"></i>
                            <div>
                                <strong>{{ opportunity.name }}</strong>
                                <span v-if="opportunity.phone"
                                    >· {{ opportunity.phone }}</span
                                >
                                <span v-if="opportunity.email"
                                    >· {{ opportunity.email }}</span
                                >
                            </div>
                        </div>
                        <div v-else class="q-fields">
                            <div
                                class="q-field full"
                                :class="{ err: formErrors.clientName }"
                            >
                                <label>Nombre completo <span>*</span></label>
                                <input
                                    v-model="formData.clientName"
                                    type="text"
                                    placeholder="Tu nombre completo"
                                    @input="formErrors.clientName = ''"
                                />
                                <span
                                    v-if="formErrors.clientName"
                                    class="q-ferr"
                                    >{{ formErrors.clientName }}</span
                                >
                            </div>
                            <div
                                class="q-field"
                                :class="{ err: formErrors.clientPhone }"
                            >
                                <label>Teléfono <span>*</span></label>
                                <input
                                    v-model="formData.clientPhone"
                                    type="tel"
                                    placeholder="600 000 000"
                                    @input="formErrors.clientPhone = ''"
                                />
                                <span
                                    v-if="formErrors.clientPhone"
                                    class="q-ferr"
                                    >{{ formErrors.clientPhone }}</span
                                >
                            </div>
                            <div
                                class="q-field"
                                :class="{ err: formErrors.clientEmail }"
                            >
                                <label>Email <span>*</span></label>
                                <input
                                    v-model="formData.clientEmail"
                                    type="email"
                                    placeholder="tu@email.com"
                                    @input="formErrors.clientEmail = ''"
                                />
                                <span
                                    v-if="formErrors.clientEmail"
                                    class="q-ferr"
                                    >{{ formErrors.clientEmail }}</span
                                >
                            </div>
                            <div
                                class="q-field full"
                                :class="{ err: formErrors.clientLocation }"
                            >
                                <label
                                    >Dirección / Ubicación <span>*</span></label
                                >
                                <input
                                    v-model="formData.clientLocation"
                                    type="text"
                                    placeholder="Calle, ciudad, provincia"
                                    @input="formErrors.clientLocation = ''"
                                />
                                <span
                                    v-if="formErrors.clientLocation"
                                    class="q-ferr"
                                    >{{ formErrors.clientLocation }}</span
                                >
                            </div>
                        </div>

                        <!-- Resumen compacto + acciones -->
                        <div v-if="budgetTotals" class="q-final-block">
                            <div class="q-final-total">
                                <span>Total presupuesto</span>
                                <strong
                                    >{{
                                        formatCurrency(budgetTotals.total)
                                    }}
                                    €</strong
                                >
                            </div>

                            <div class="q-financing-panel">
                                <div class="q-financing-top">
                                    <div>
                                        <strong>¿Quieres financiarlo?</strong>
                                        <span>Calcula la cuota mensual sobre el importe total del presupuesto.</span>
                                    </div>
                                    <label class="q-switch">
                                        <input
                                            type="checkbox"
                                            v-model="formData.wantsFinancing"
                                        />
                                        <span></span>
                                    </label>
                                </div>

                                <template v-if="formData.wantsFinancing">
                                    <div class="q-financing-plans">
                                        <button
                                            v-for="plan in financingPlans"
                                            :key="plan.months"
                                            class="q-finance-plan"
                                            :class="{
                                                active:
                                                    formData.selectedFinancingMonths ===
                                                    plan.months,
                                            }"
                                            @click="
                                                formData.selectedFinancingMonths =
                                                    plan.months
                                            "
                                        >
                                            <strong>{{ plan.months }} meses</strong>
                                            <span>TIN {{ formatPercent(plan.tin) }}</span>
                                            <em>{{ formatCurrency(budgetTotals.total * plan.coefficient) }} €/mes</em>
                                        </button>
                                    </div>

                                    <div
                                        v-if="selectedFinancingPlan"
                                        class="q-financing-result"
                                    >
                                        <div>
                                            <span>Importe financiado</span>
                                            <strong>{{ formatCurrency(financingAmount) }} €</strong>
                                        </div>
                                        <div>
                                            <span>Plazo elegido</span>
                                            <strong>{{ selectedFinancingPlan.months }} meses</strong>
                                        </div>
                                        <div>
                                            <span>Cuota mensual</span>
                                            <strong>{{ formatCurrency(financingMonthlyFee) }} €/mes</strong>
                                        </div>
                                        <div>
                                            <span>Total a pagar financiado</span>
                                            <strong>{{ formatCurrency(financingTotalPaid) }} €</strong>
                                        </div>
                                        <div>
                                            <span>CAP / TIN</span>
                                            <strong>{{ formatPercent(selectedFinancingPlan.cap) }} / {{ formatPercent(selectedFinancingPlan.tin) }}</strong>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="q-final-actions">
                                <button
                                    class="q-btn-pdf"
                                    :disabled="
                                        !canGeneratePDF || isGeneratingPDF
                                    "
                                    @click="generatePDF"
                                >
                                    <template v-if="isGeneratingPDF"
                                        ><i
                                            class="fa-regular fa-spinner-third fa-spin"
                                        ></i>
                                        Generando...</template
                                    >
                                    <template v-else
                                        ><i class="far fa-file-pdf"></i>
                                        Descargar PDF</template
                                    >
                                </button>
                                <button
                                    v-if="!formData.wantsFinancing"
                                    class="q-btn-pay"
                                    :disabled="
                                        !canGeneratePDF || isRedirectingToStripe
                                    "
                                    @click="confirmInstallationAndPay"
                                >
                                    <template v-if="isRedirectingToStripe"
                                        ><i
                                            class="fa-regular fa-spinner-third fa-spin"
                                        ></i>
                                        Redirigiendo...</template
                                    >
                                    <template v-else
                                        ><i class="far fa-credit-card"></i>
                                        Aceptar y pagar</template
                                    >
                                </button>
                                <div
                                    v-else
                                    class="q-financing-action-note"
                                >
                                    <i class="far fa-circle-info"></i>
                                    Descarga el PDF para enviar la opción financiada al cliente.
                                </div>
                            </div>
                        </div>

                        <div class="q-foot">
                            <button class="q-btn-back" @click="goToStep(3)">
                                <i class="far fa-arrow-left"></i> Atrás
                            </button>
                        </div>
                    </div>
                </div>
                <!-- /q-form -->

                <!-- ══════ COLUMNA RESUMEN (RECEIPT) ══════ -->
                <div class="q-receipt">
                    <div class="q-receipt-inner">
                        <div class="qr-head">
                            <div class="qr-head-icon">
                                <i class="far fa-file-invoice"></i>
                            </div>
                            <div>
                                <div class="qr-head-title">Presupuesto</div>
                                <div class="qr-head-sub">Segenet Movilidad</div>
                            </div>
                        </div>
                        <div class="qr-selections">
                            <div
                                class="qr-sel"
                                :class="{ filled: formData.installationType }"
                            >
                                <div class="qrs-icon">
                                    <i class="far fa-house-chimney"></i>
                                </div>
                                <div class="qrs-content">
                                    <span class="qrs-label">Instalación</span>
                                    <span
                                        class="qrs-value"
                                        v-if="formData.installationType"
                                        >{{
                                            formData.installationType ===
                                            "house"
                                                ? "Casa / Vivienda"
                                                : "Garaje comunitario"
                                        }}</span
                                    >
                                    <span class="qrs-empty" v-else
                                        >Pendiente</span
                                    >
                                </div>
                                <i
                                    v-if="formData.installationType"
                                    class="fas fa-check qrs-check"
                                ></i>
                            </div>
                            <div
                                class="qr-sel"
                                :class="{ filled: formData.selectedCharger }"
                            >
                                <div
                                    class="qrs-icon"
                                    :style="
                                        formData.selectedCharger
                                            ? {
                                                  background: getBrandColor(
                                                      formData.selectedCharger
                                                          .brand,
                                                  ),
                                                  color: '#fff',
                                              }
                                            : {}
                                    "
                                >
                                    <i class="far fa-charging-station"></i>
                                </div>
                                <div class="qrs-content">
                                    <span class="qrs-label">Cargador</span>
                                    <span
                                        class="qrs-value"
                                        v-if="formData.selectedCharger"
                                        >{{
                                            formData.selectedCharger.name
                                        }}</span
                                    >
                                    <span class="qrs-empty" v-else
                                        >Pendiente</span
                                    >
                                </div>
                                <i
                                    v-if="formData.selectedCharger"
                                    class="fas fa-check qrs-check"
                                ></i>
                            </div>
                            <div
                                class="qr-sel"
                                :class="{ filled: effectiveCableMeters > 0 }"
                            >
                                <div class="qrs-icon">
                                    <i class="far fa-ruler-horizontal"></i>
                                </div>
                                <div class="qrs-content">
                                    <span class="qrs-label">Cable</span>
                                    <span
                                        class="qrs-value"
                                        v-if="effectiveCableMeters > 0"
                                        >{{ effectiveCableMeters }} metros</span
                                    >
                                    <span class="qrs-empty" v-else
                                        >Pendiente</span
                                    >
                                </div>
                                <i
                                    v-if="effectiveCableMeters > 0"
                                    class="fas fa-check qrs-check"
                                ></i>
                            </div>
                            <div
                                class="qr-sel"
                                :class="{
                                    filled: formData.hasAutoconsumo === 'yes',
                                    'qr-sel--solar':
                                        formData.hasAutoconsumo === 'yes',
                                }"
                            >
                                <div class="qrs-icon qrs-icon--solar">
                                    <i class="far fa-solar-panel"></i>
                                </div>
                                <div class="qrs-content">
                                    <span class="qrs-label">Solar</span>
                                    <span
                                        class="qrs-value"
                                        v-if="formData.hasAutoconsumo === 'yes'"
                                        >Optimización incluida</span
                                    >
                                    <span
                                        class="qrs-value"
                                        v-else-if="
                                            formData.hasAutoconsumo === 'no'
                                        "
                                        >Sin paneles</span
                                    >
                                    <span class="qrs-empty" v-else
                                        >Pendiente</span
                                    >
                                </div>
                                <i
                                    v-if="formData.hasAutoconsumo === 'yes'"
                                    class="fas fa-check qrs-check"
                                ></i>
                            </div>
                            <div
                                class="qr-sel"
                                :class="{
                                    filled:
                                        formData.isHabitualResidence ===
                                        'yes',
                                    'qr-sel--iva':
                                        formData.isHabitualResidence ===
                                        'yes',
                                }"
                            >
                                <div class="qrs-icon qrs-icon--iva">
                                    <i class="far fa-house-user"></i>
                                </div>
                                <div class="qrs-content">
                                    <span class="qrs-label">IVA</span>
                                    <span
                                        class="qrs-value"
                                        v-if="
                                            formData.isHabitualResidence ===
                                            'yes'
                                        "
                                        >Reducido 10% (vivienda habitual)</span
                                    >
                                    <span
                                        class="qrs-value"
                                        v-else-if="
                                            formData.isHabitualResidence ===
                                            'no'
                                        "
                                        >General 21%</span
                                    >
                                    <span class="qrs-empty" v-else
                                        >Pendiente</span
                                    >
                                </div>
                                <i
                                    v-if="
                                        formData.isHabitualResidence === 'yes'
                                    "
                                    class="fas fa-check qrs-check"
                                ></i>
                            </div>
                        </div>
                        <template v-if="budgetTotals">
                            <div class="qr-divider"></div>
                            <div class="qr-lines">
                                <div
                                    class="qr-line"
                                    v-if="budgetTotals.chargerSubtotal > 0"
                                >
                                    <span>Cargador</span
                                    ><span
                                        >{{
                                            formatCurrency(
                                                budgetTotals.chargerSubtotal,
                                            )
                                        }}
                                        €</span
                                    >
                                </div>
                                <div
                                    class="qr-line"
                                    v-if="budgetTotals.laborSubtotal > 0"
                                >
                                    <span>Mano de obra</span
                                    ><span
                                        >{{
                                            formatCurrency(
                                                budgetTotals.laborSubtotal,
                                            )
                                        }}
                                        €</span
                                    >
                                </div>
                                <div
                                    class="qr-line"
                                    v-if="budgetTotals.cableSubtotal > 0"
                                >
                                    <span
                                        >Cableado ({{
                                            effectiveCableMeters
                                        }}m)</span
                                    ><span
                                        >{{
                                            formatCurrency(
                                                budgetTotals.cableSubtotal,
                                            )
                                        }}
                                        €</span
                                    >
                                </div>
                                <div
                                    class="qr-line"
                                    v-if="budgetTotals.certSubtotal > 0"
                                >
                                    <span>Boletín</span
                                    ><span
                                        >{{
                                            formatCurrency(
                                                budgetTotals.certSubtotal,
                                            )
                                        }}
                                        €</span
                                    >
                                </div>
                                <div
                                    class="qr-line qr-line--solar"
                                    v-if="budgetTotals.surplusSubtotal > 0"
                                >
                                    <span
                                        ><i
                                            class="far fa-solar-panel"
                                            style="
                                                margin-right: 4px;
                                                color: #f59e0b;
                                            "
                                        ></i
                                        >Solar</span
                                    ><span
                                        >+{{
                                            formatCurrency(
                                                budgetTotals.surplusSubtotal,
                                            )
                                        }}
                                        €</span
                                    >
                                </div>
                                <div class="qr-line qr-line--vat">
                                    <span>IVA {{ budgetTotals.vatPercentage }}%</span
                                    ><span
                                        >{{
                                            formatCurrency(budgetTotals.vat)
                                        }}
                                        €</span
                                    >
                                </div>
                            </div>
                            <div class="qr-total">
                                <span>Total estimado</span
                                ><strong
                                    >{{
                                        formatCurrency(budgetTotals.total)
                                    }}
                                    €</strong
                                >
                            </div>
                            <div
                                v-if="formData.wantsFinancing && selectedFinancingPlan"
                                class="qr-financing"
                            >
                                <div class="qr-financing-title">
                                    <i class="far fa-credit-card"></i>
                                    Financiación seleccionada
                                </div>
                                <div class="qr-line">
                                    <span>{{ selectedFinancingPlan.months }} meses</span>
                                    <span>{{ formatCurrency(financingMonthlyFee) }} €/mes</span>
                                </div>
                                <div class="qr-line">
                                    <span>TIN / CAP</span>
                                    <span>{{ formatPercent(selectedFinancingPlan.tin) }} / {{ formatPercent(selectedFinancingPlan.cap) }}</span>
                                </div>
                            </div>
                        </template>
                        <div v-else class="qr-empty">
                            <i class="far fa-calculator"></i
                            ><span
                                >Configura tu instalación<br />para ver el
                                precio</span
                            >
                        </div>
                    </div>
                </div>
                <!-- /q-receipt -->
            </div>
            <!-- /q-layout -->
        </template>

        <FloatingContactButtons />
    </section>
</template>

<script>
import FloatingContactButtons from "../items/FloatingContactButtons.vue";
export default {
    name: "ElectricChargerRequestPage",
    components: { FloatingContactButtons },
    props: ["basicData"],
    data() {
        return {
            currentStep: 1,
            opportunityId: new URLSearchParams(window.location.search).get(
                "id",
            ),
            opportunity: null,
            isLoadingOpportunity: false,
            isGeneratingPDF: false,
            isRedirectingToStripe: false,
            isPaymentSuccessPage: false,
            stripeSessionId: null,
            isLoadingPaymentSuccess: false,
            paymentSuccessData: null,
            showAllChargers: false,
            selectedBrandFilter: "",
            formErrors: {
                clientName: "",
                clientPhone: "",
                clientEmail: "",
                clientLocation: "",
            },
            formData: {
                clientName: "",
                clientPhone: "",
                clientEmail: "",
                clientLocation: "",
                installationType: "",
                selectedCharger: null,
                cableMeters: "",
                hasAutoconsumo: "",
                isHabitualResidence: "",
                wantsFinancing: false,
                selectedFinancingMonths: 36,
            },
            financingPlans: [
                { months: 12, cap: 2.0, tin: 5.75, coefficient: 0.08767 },
                { months: 18, cap: 2.0, tin: 5.75, coefficient: 0.05928 },
                { months: 24, cap: 2.0, tin: 5.75, coefficient: 0.04509 },
                { months: 30, cap: 2.0, tin: 6.0, coefficient: 0.03670 },
                { months: 36, cap: 2.0, tin: 6.0, coefficient: 0.03103 },
                { months: 48, cap: 2.0, tin: 6.25, coefficient: 0.02407 },
                { months: 60, cap: 2.0, tin: 6.5, coefficient: 0.01996 },
                { months: 72, cap: 2.0, tin: 6.75, coefficient: 0.01727 },
                { months: 84, cap: 2.0, tin: 6.75, coefficient: 0.01527 },
                { months: 96, cap: 2.0, tin: 6.75, coefficient: 0.01378 },
                { months: 108, cap: 2.5, tin: 6.99, coefficient: 0.01281 },
                { months: 120, cap: 2.5, tin: 6.99, coefficient: 0.01190 },
                { months: 132, cap: 2.5, tin: 6.99, coefficient: 0.01115 },
                { months: 144, cap: 2.5, tin: 6.99, coefficient: 0.01054 },
            ],
            meterPresets: [
                { val: 5, label: "5m", desc: "Muy corto" },
                { val: 10, label: "10m", desc: "Corto" },
                { val: 15, label: "15m", desc: "Normal" },
                { val: 20, label: "20m", desc: "Medio" },
                { val: 30, label: "30m", desc: "Largo" },
                { val: 50, label: "50m", desc: "Muy largo" },
                { val: 70, label: "70m", desc: "Máximo" },
            ],
            evChargerDefaultCosts: {
                vatPercentage: 21,
                certificatePrice: 150,
                cablePricePerMeter: 3,
                modulationCablePricePerMeter: 0.5,
                surplusOptimizationPrice: 95,
                paymentMethod:
                    "Transferencia bancaria 60% a la aceptación y restante a la finalización",
                installationNotes:
                    "La mano de obra incluye hasta 5 horas de trabajo.",
            },
            evChargerInstallationCostTable: [
                { meters: 5, labor: 200 },
                { meters: 10, labor: 200 },
                { meters: 15, labor: 200 },
                { meters: 20, labor: 200 },
                { meters: 25, labor: 260 },
                { meters: 30, labor: 260 },
                { meters: 35, labor: 260 },
                { meters: 40, labor: 260 },
                { meters: 45, labor: 290 },
                { meters: 50, labor: 290 },
                { meters: 55, labor: 290 },
                { meters: 60, labor: 290 },
                { meters: 65, labor: 290 },
                { meters: 70, labor: 290 },
            ],
            evChargerModels: [
                {
                    brand: "WOLTIO",
                    recommended: true,
                    name: "Woltio PRO 10m 32A monofásico",
                    chargerModel:
                        "WOLTIO - Cargador monofásico Woltio PRO 10m 32A",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 675.0,
                    pdf: "https://woltio.com/descargas/WOLTIO_PRO_FICHA%20DE%20PRODUCTO.pdf",
                },
                {
                    brand: "WOLTIO",
                    recommended: true,
                    name: "Woltio PRO 10m 40A monofásico",
                    chargerModel:
                        "WOLTIO - Cargador monofásico Woltio PRO 10m 40A",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 675.0,
                    pdf: "https://woltio.com/descargas/WOLTIO_PRO_FICHA%20DE%20PRODUCTO.pdf",
                },
                {
                    brand: "WOLTIO",
                    recommended: true,
                    name: "Woltio PLUS 22kW trifásico",
                    chargerModel:
                        "WOLTIO - Cargador trifásico Woltio PLUS sin protecciones",
                    chargerPower: "22kW",
                    phase: "trifasico",
                    pvp: 621.43,
                    pdf: "https://woltio.com/descargas/WOLTIO_PLUS_FICHA_DE_PRODUCTO.pdf",
                },
                {
                    brand: "OHME",
                    recommended: false,
                    name: "HOME PRO 7,4 / 4G / 5",
                    chargerModel: "OHME - HOME PRO 7,4 / 4G / 5",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 719.0,
                    pdf: "https://ohme-ev.com/wp-content/uploads/2024/02/Home-Pro-data-sheet.pdf",
                },
                {
                    brand: "OHME",
                    recommended: false,
                    name: "ePodS 7,4 4G T2S",
                    chargerModel: "OHME - ePodS 7,4 4G T2S",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 669.0,
                    pdf: "https://ohme-ev.com/wp-content/uploads/2024/02/ePod-data-sheet.pdf",
                },
                {
                    brand: "V2C",
                    recommended: false,
                    name: "TRYDAN 7,4kW con protecciones",
                    chargerModel: "V2C - V2C TRYDAN  7,4 KW T2 5M PROTECCIONES",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 777.9,
                    pdf: "https://v2charge.com/wp-content/uploads/2025/11/ficha-tecnica-trydan.pdf",
                },
                {
                    brand: "V2C",
                    recommended: false,
                    name: "TRYDAN 7,4kW T2 5M",
                    chargerModel: "V2C - V2C TRYDAN  7,4 KW T2 5M",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 646.6,
                    pdf: "https://v2charge.com/wp-content/uploads/2025/11/ficha-tecnica-trydan.pdf",
                },
                {
                    brand: "V2C",
                    recommended: false,
                    name: "PRO 10M 7,4kW + protecciones",
                    chargerModel:
                        "V2C - CARGADOR V2C PRO 10M 7.4KW + PROTECCIONE",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 891.97,
                    pdf: "https://v2charge.com/wp-content/uploads/2025/11/ficha-tecnica-trydan.pdf",
                },
                {
                    brand: "WALLBOX",
                    recommended: false,
                    name: "Pulsar Plus 7,4kW (32A)",
                    chargerModel: "WALLBOX - PULSAR PLUS 7,4 kW (1ph - 32A)",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 842.86,
                    pdf: "https://support.wallbox.com/wp-content/uploads/ht_kb/2025/06/Wallbox_Datasheet_PulsarPlus_Tethered_ES_b.pdf",
                },
                {
                    brand: "WALLBOX",
                    recommended: false,
                    name: "Pulsar Plus 11kW (16A)",
                    chargerModel: "WALLBOX - PULSAR PLUS 11 kW (3ph - 16A)",
                    chargerPower: "11kW",
                    phase: "trifasico",
                    pvp: 891.43,
                    pdf: "https://support.wallbox.com/wp-content/uploads/ht_kb/2025/06/Wallbox_Datasheet_PulsarPlus_Tethered_ES_b.pdf",
                },
                {
                    brand: "WALLBOX",
                    recommended: false,
                    name: "Pulsar Plus 22kW (32A)",
                    chargerModel: "WALLBOX - PULSAR PLUS 22 kW (3ph - 32A)",
                    chargerPower: "22kW",
                    phase: "trifasico",
                    pvp: 905.71,
                    pdf: "https://support.wallbox.com/wp-content/uploads/ht_kb/2025/06/Wallbox_Datasheet_PulsarPlus_Tethered_ES_b.pdf",
                },
                {
                    brand: "POLICHARGER",
                    recommended: false,
                    name: "ON-T1 7,4kW",
                    chargerModel: "POLICHARGER - Policharger ON-T1 7,4kW",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 621.76,
                    pdf: "https://policharger.com/ficha-tecnica/ON-T1.pdf",
                },
                {
                    brand: "POLICHARGER",
                    recommended: false,
                    name: "ON-T2 7,4kW",
                    chargerModel: "POLICHARGER - Policharger ON-T2 7,4kW",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 618.71,
                    pdf: "https://policharger.com/ficha-tecnica/ON-T2.pdf",
                },
                {
                    brand: "POLICHARGER",
                    recommended: false,
                    name: "NW-T1 con prot. 7,4kW",
                    chargerModel:
                        "POLICHARGER - Policharger NW-T1 con protecciones 7,4kW",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 749.56,
                    pdf: "https://bunny-wp-pullzone-1mmtkygysx.b-cdn.net/wp-content/uploads/2024/08/FICHA-TECNICA_NW-T2-.pdf",
                },
                {
                    brand: "POLICHARGER",
                    recommended: false,
                    name: "ON-T23F 22kW",
                    chargerModel: "POLICHARGER - Policharger ON-T23F 22kW",
                    chargerPower: "22kW",
                    phase: "trifasico",
                    pvp: 673.49,
                    pdf: "https://policharger.com/ficha-tecnica/ON-T23F.pdf",
                },
                {
                    brand: "POLICHARGER",
                    recommended: false,
                    name: "NW-T23F con prot. 22kW",
                    chargerModel:
                        "POLICHARGER - Policharger NW-T23F con protecciones 22kW",
                    chargerPower: "22kW",
                    phase: "trifasico",
                    pvp: 925.03,
                    pdf: "https://policharger.com/ficha-tecnica/NW-T23F.pdf",
                },
                {
                    brand: "ZAPTEC",
                    recommended: false,
                    name: "Go Asphalt Black 7,4kW",
                    chargerModel: "ZAPTEC - Zaptec Go Asphalt Black",
                    chargerPower: "7,4kW",
                    phase: "",
                    pvp: 740.0,
                    pdf: "https://assets.ctfassets.net/zq85bj8o2ot3/1QVRKIh9eJXxDyvDSZgfla/b7b12da0ee63c50e4e4e7de0fd1fe14d/en-GB_Zaptec_Go_Product_Sheet.pdf",
                },
                {
                    brand: "ZAPTEC",
                    recommended: false,
                    name: "Go 2 Asphalt Black AVANZADO",
                    chargerModel: "ZAPTEC - Zaptec Go 2 Asphalt Black AVANZADO",
                    chargerPower: "7,4kW",
                    phase: "",
                    pvp: 1004.29,
                    pdf: "https://assets.ctfassets.net/zq85bj8o2ot3/6Q5hRrgrD0F6LYLko9Xd90/eb86a990a5d6c40335fab096fa6d3516/en-GB_Zaptec_Go_2_Product_Sheet__1_.pdf",
                },
                {
                    brand: "ORBIS",
                    recommended: false,
                    name: "Viaris Isi 7,4kW + prot.",
                    chargerModel:
                        "ORBIS Viaris Isi Mono. 7,4kw+Mang.T2 5m+Protec",
                    chargerPower: "7,4kW",
                    phase: "monofasico",
                    pvp: 795.71,
                    pdf: "https://orbis.es/wp-content/uploads/fichatecnica/FT_VIARIS_ISI_230_V.pdf",
                },
            ],
        };
    },
    created() {
        this.detectPaymentSuccessPage();
        if (this.isPaymentSuccessPage) {
            this.fetchPaymentSuccessData();
            return;
        }
        if (this.opportunityId) this.fetchOpportunity();
        window.addEventListener("beforeunload", () => this.logProgress());
    },
    computed: {
        stepLabels() {
            return ["Instalación", "Cargador", "Detalles", "Tus datos"];
        },
        recommendedChargers() {
            return this.evChargerModels.filter((c) => c.recommended);
        },
        otherChargers() {
            return this.evChargerModels.filter((c) => !c.recommended);
        },
        availableBrands() {
            return [...new Set(this.otherChargers.map((c) => c.brand))];
        },
        filteredOtherChargers() {
            return this.selectedBrandFilter
                ? this.otherChargers.filter(
                      (c) => c.brand === this.selectedBrandFilter,
                  )
                : this.otherChargers;
        },
        canProceedStep2() {
            return !!this.formData.selectedCharger;
        },
        canProceedStep3() {
            return this.effectiveCableMeters > 0;
        },
        effectiveCableMeters() {
            return Math.min(Number(this.formData.cableMeters) || 0, 70);
        },
        canGeneratePDF() {
            const ok = this.opportunity
                ? !!this.formData.clientName.trim()
                : !!(
                      this.formData.clientName.trim() &&
                      this.formData.clientPhone.trim() &&
                      this.formData.clientEmail.trim() &&
                      this.formData.clientLocation.trim()
                  );
            return (
                ok &&
                !!this.formData.selectedCharger &&
                this.effectiveCableMeters > 0
            );
        },
        pdfFileName() {
            return `presupuesto_${(this.formData.clientName || "cargador").replace(/[^A-Za-z0-9]/g, "_")}.pdf`;
        },
        selectedFinancingPlan() {
            return (
                this.financingPlans.find(
                    (p) => p.months === this.formData.selectedFinancingMonths,
                ) || this.financingPlans.find((p) => p.months === 36)
            );
        },
        financingAmount() {
            return this.budgetTotals ? Number(this.budgetTotals.total) || 0 : 0;
        },
        financingMonthlyFee() {
            if (!this.formData.wantsFinancing || !this.selectedFinancingPlan)
                return 0;
            return this.roundMoney(
                this.financingAmount * this.selectedFinancingPlan.coefficient,
            );
        },
        financingTotalPaid() {
            if (!this.formData.wantsFinancing || !this.selectedFinancingPlan)
                return 0;
            return this.roundMoney(
                this.financingMonthlyFee * this.selectedFinancingPlan.months,
            );
        },
        financingCost() {
            if (!this.formData.wantsFinancing) return 0;
            return this.roundMoney(this.financingTotalPaid - this.financingAmount);
        },
        financingCapAmount() {
            if (!this.formData.wantsFinancing || !this.selectedFinancingPlan)
                return 0;
            return this.roundMoney(
                this.financingAmount * (this.selectedFinancingPlan.cap / 100),
            );
        },
        budgetTotals() {
            if (
                !this.formData.selectedCharger &&
                this.effectiveCableMeters === 0
            )
                return null;
            const pvp = this.formData.selectedCharger?.pvp || 0;
            const m = this.effectiveCableMeters;
            const rows = [...this.evChargerInstallationCostTable].sort(
                (a, b) => a.meters - b.meters,
            );
            const row =
                m > 0
                    ? rows.find((r) => m <= r.meters) || rows[rows.length - 1]
                    : null;
            const r = (v, f = 1.1) => Math.round(v * f * 100) / 100;
            const cs = r(pvp, 1);
            const ls = row ? r(row.labor) : 0;
            const ce = m > 0 ? r(150) : 0;
            const ca = r(m * 4 * 3);
            const tu = r(m * 0.5);
            const su = this.formData.hasAutoconsumo === "yes" ? r(95) : 0;
            const sub = cs + ls + ce + ca + tu + su;
            // IVA reducido del 10% para vivienda habitual; 21% general en caso contrario.
            const vatPercentage =
                this.formData.isHabitualResidence === "yes" ? 10 : 21;
            const vat = r(sub * (vatPercentage / 100), 1);
            const total = r(sub + vat, 1);
            return {
                chargerSubtotal: cs,
                laborSubtotal: ls,
                certSubtotal: ce,
                cableSubtotal: ca,
                tubeSubtotal: tu,
                surplusSubtotal: su,
                subtotal: sub,
                vatPercentage,
                vat,
                total,
            };
        },
    },
    methods: {
        roundMoney(value) {
            return Math.round((Number(value) || 0) * 100) / 100;
        },
        formatPercent(value) {
            return `${String(Number(value || 0).toFixed(2)).replace(".", ",")}%`;
        },
        goToStep(s) {
            this.currentStep = s;
            this.logProgress();
            this.$nextTick(() =>
                window.scrollTo({ top: 0, behavior: "smooth" }),
            );
        },
        validateAndSubmit() {
            this.formErrors = {
                clientName: "",
                clientPhone: "",
                clientEmail: "",
                clientLocation: "",
            };
            if (!this.opportunity) {
                if (!this.formData.clientName.trim())
                    this.formErrors.clientName = "Obligatorio";
                if (!this.formData.clientPhone.trim())
                    this.formErrors.clientPhone = "Obligatorio";
                else if (
                    !/^[0-9]{9}$/.test(
                        this.formData.clientPhone.replace(/\s/g, ""),
                    )
                )
                    this.formErrors.clientPhone = "Debe tener 9 dígitos";
                if (!this.formData.clientEmail.trim())
                    this.formErrors.clientEmail = "Obligatorio";
                else if (
                    !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
                        this.formData.clientEmail,
                    )
                )
                    this.formErrors.clientEmail = "Email inválido";
                if (!this.formData.clientLocation.trim())
                    this.formErrors.clientLocation = "Obligatorio";
                if (Object.values(this.formErrors).some((e) => e)) return;
            }
        },
        validateAndGoStep2() {
            this.validateAndSubmit();
            if (!Object.values(this.formErrors).some((e) => e))
                this.goToStep(2);
        },
        selectCharger(c) {
            this.formData.selectedCharger =
                this.formData.selectedCharger?.chargerModel === c.chargerModel
                    ? null
                    : c;
        },
        getBrandColor(b) {
            return (
                {
                    WOLTIO: "#1D4ED8",
                    OHME: "#0EA5E9",
                    V2C: "#047857",
                    WALLBOX: "#DC2626",
                    POLICHARGER: "#7C3AED",
                    ZAPTEC: "#0F766E",
                    ORBIS: "#D97706",
                }[b] || "#64748B"
            );
        },
        formatCurrency(v) {
            return (Number.isFinite(Number(v)) ? Number(v) : 0).toLocaleString(
                "es-ES",
                { minimumFractionDigits: 2, maximumFractionDigits: 2 },
            );
        },
        getPhaseLabel(p) {
            return p === "monofasico"
                ? "Monofásico"
                : p === "trifasico"
                  ? "Trifásico"
                  : p || "";
        },
        async fetchOpportunity() {
            this.isLoadingOpportunity = true;
            try {
                const r = await axios.get(
                    `/api/public/opportunities/${this.opportunityId}`,
                );
                this.opportunity = r.data.opportunity;
                if (this.opportunity) {
                    ["name", "phone", "email"].forEach((k) => {
                        if (this.opportunity[k])
                            this.formData[
                                k === "name"
                                    ? "clientName"
                                    : k === "phone"
                                      ? "clientPhone"
                                      : "clientEmail"
                            ] = this.opportunity[k];
                    });
                    const l = [
                        this.opportunity.order?.direc,
                        this.opportunity.order?.town,
                        this.opportunity.order?.province,
                    ]
                        .filter(Boolean)
                        .join(", ");
                    if (l) this.formData.clientLocation = l;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoadingOpportunity = false;
            }
        },
        buildEvChargerBudget() {
            const t = this.budgetTotals;
            const c = this.evChargerDefaultCosts;
            const sel = this.formData.selectedCharger;
            const m = this.effectiveCableMeters;

            // Coste de mano de obra según tabla de metros
            const rows = [...this.evChargerInstallationCostTable].sort(
                (a, b) => a.meters - b.meters,
            );
            const row =
                m > 0
                    ? rows.find((r) => m <= r.meters) || rows[rows.length - 1]
                    : null;

            return {
                // Info cargador
                charger: { name: sel?.name || "" },
                chargerModel: sel?.chargerModel || "",
                chargerBrand: sel?.brand || "",
                chargerPower: sel?.chargerPower || "",
                chargerInstallationPrice: sel?.pvp || 0,
                chargerInstallationDiscount: 0,
                // IVA reducido 10% si es vivienda habitual, general 21% en otro caso.
                chargerVatPercentage: t?.vatPercentage ?? c.vatPercentage,
                isHabitualResidence:
                    this.formData.isHabitualResidence === "yes",
                // Instalación
                installationType: this.formData.installationType,
                cableMeters: m,
                cablePricePerMeter: c.cablePricePerMeter,
                modulationCableMeters: m,
                modulationCablePricePerMeter: c.modulationCablePricePerMeter,
                // Mano de obra
                laborPrice: row?.labor || 0,
                // Boletín
                certificatePrice: c.certificatePrice,
                // Solar
                hasPhotovoltaic: this.formData.hasAutoconsumo === "yes",
                wantsSurplusOptimization:
                    this.formData.hasAutoconsumo === "yes",
                surplusOptimizationPrice: c.surplusOptimizationPrice,
                // Totales pre-calculados con márgenes (ya incluyen vatPercentage)
                totals: t ? { ...t } : null,
                // Financiación seleccionada por el cliente
                financing: this.formData.wantsFinancing && this.selectedFinancingPlan
                    ? {
                          enabled: true,
                          amount: this.financingAmount,
                          months: this.selectedFinancingPlan.months,
                          cap: this.selectedFinancingPlan.cap,
                          tin: this.selectedFinancingPlan.tin,
                          coefficient: this.selectedFinancingPlan.coefficient,
                          monthlyFee: this.financingMonthlyFee,
                          totalPaid: this.financingTotalPaid,
                          cost: this.financingCost,
                          capAmount: this.financingCapAmount,
                      }
                    : {
                          enabled: false,
                      },
                // Meta
                budgetType: "electric_car_charger",
                budgetSavedAt: new Date().toISOString(),
                paymentMethod: c.paymentMethod,
                installationNotes: c.installationNotes,
            };
        },
        async generatePDF() {
            if (!this.canGeneratePDF || this.isGeneratingPDF) return;
            this.isGeneratingPDF = true;
            try {
                const budget = this.buildEvChargerBudget();
                const ob = this.opportunity || {};
                const opf = {
                    ...ob,
                    name: this.formData.clientName,
                    phone: this.formData.clientPhone,
                    email: this.formData.clientEmail,
                    order: {
                        ...(ob.order || {}),
                        direc: this.formData.clientLocation,
                        evChargerBudget: budget,
                    },
                };
                const fd = new FormData();
                fd.append(
                    "payload",
                    JSON.stringify({
                        opportunity: opf,
                        saveReference: true,
                        basicData: this.basicData || {},
                        userLogged: this.basicData?.userLogged || null,
                    }),
                );
                const res = await axios.post(
                    "/api/public/opportunities/generateEvChargerPDF",
                    fd,
                    {
                        responseType: "blob",
                        headers: { "Content-Type": "multipart/form-data" },
                    },
                );
                const url = window.URL.createObjectURL(
                    new Blob([res.data], { type: "application/pdf" }),
                );
                const a = document.createElement("a");
                a.href = url;
                a.download = this.pdfFileName;
                a.click();
                window.URL.revokeObjectURL(url);
            } catch (e) {
                console.error(e);
                if (typeof Swal !== "undefined")
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudo generar el PDF.",
                    });
            } finally {
                this.isGeneratingPDF = false;
            }
        },
        async confirmInstallationAndPay() {
            if (!this.canGeneratePDF || this.isRedirectingToStripe) return;
            this.isRedirectingToStripe = true;
            try {
                const t = this.budgetTotals;
                const res = await axios.post(
                    "/api/stripe/createElectricChargerBudgetCheckout",
                    {
                        customer: {
                            name: this.formData.clientName,
                            email: this.formData.clientEmail,
                            phone: this.formData.clientPhone,
                        },
                        charger: { name: this.formData.selectedCharger.name },
                        installation: {
                            type: this.formData.installationType,
                            cableMeters: this.formData.cableMeters,
                        },
                        summary: {
                            ...t,
                            financing:
                                this.formData.wantsFinancing &&
                                this.selectedFinancingPlan
                                    ? {
                                          enabled: true,
                                          amount: this.financingAmount,
                                          months: this.selectedFinancingPlan.months,
                                          cap: this.selectedFinancingPlan.cap,
                                          tin: this.selectedFinancingPlan.tin,
                                          coefficient:
                                              this.selectedFinancingPlan.coefficient,
                                          monthlyFee: this.financingMonthlyFee,
                                          totalPaid: this.financingTotalPaid,
                                          cost: this.financingCost,
                                          capAmount: this.financingCapAmount,
                                      }
                                    : { enabled: false },
                        },
                    },
                );
                const url = res.data?.url;
                if (!url) throw new Error("Sin URL Stripe");
                window.top !== window.self
                    ? (window.top.location.href = url)
                    : (window.location.href = url);
            } catch (e) {
                console.error(e);
                if (typeof Swal !== "undefined")
                    Swal.fire({
                        icon: "error",
                        title: "Error al pagar",
                        text: "No se pudo redirigir al pago.",
                    });
            } finally {
                this.isRedirectingToStripe = false;
            }
        },
        async logProgress() {
            // Solo registramos el progreso si el formulario viene de una oportunidad.
            if (!this.opportunityId) return;
            try {
                await axios.post(
                    "/api/public/opportunities/logEvChargerProgress",
                    {
                        step: this.currentStep,
                        stepLabel: this.stepLabels[this.currentStep - 1] ?? "",
                        clientName: this.formData.clientName,
                        clientPhone: this.formData.clientPhone,
                        clientEmail: this.formData.clientEmail,
                        chargerModel:
                            this.formData.selectedCharger?.chargerModel ?? null,
                        cableMeters: this.formData.cableMeters,
                        hasAutoconsumo: this.formData.hasAutoconsumo,
                        isHabitualResidence:
                            this.formData.isHabitualResidence,
                        wantsFinancing: this.formData.wantsFinancing,
                        financingMonths: this.formData.wantsFinancing
                            ? this.formData.selectedFinancingMonths
                            : null,
                        financingMonthlyFee: this.formData.wantsFinancing
                            ? this.financingMonthlyFee
                            : null,
                        opportunityId: this.opportunityId,
                    },
                );
            } catch (e) {}
        },
        detectPaymentSuccessPage() {
            const p = new URLSearchParams(window.location.search);
            this.stripeSessionId = p.get("session_id");
            this.isPaymentSuccessPage =
                window.location.pathname.includes(
                    "/cargador-electrico/success",
                ) && !!this.stripeSessionId;
        },
        async fetchPaymentSuccessData() {
            if (!this.stripeSessionId) return;
            this.isLoadingPaymentSuccess = true;
            try {
                const r = await axios.post(
                    "/api/stripe/getElectricChargerBudgetCheckoutSession",
                    { session_id: this.stripeSessionId },
                );
                this.paymentSuccessData = r.data;
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoadingPaymentSuccess = false;
            }
        },
        goToLanding() {
            const url = "https://movilidad.segenet.es";
            window.top !== window.self
                ? (window.top.location.href = url)
                : (window.location.href = url);
        },
        goBackToChargerForm() {
            window.location.href = "/coche-electrico";
        },
    },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,700&display=swap");

/* ══════════════════════════════════════════════════
   SEGENET — Configurador de cargador
   Diseño: split layout, recibo derecha, pasos claros
══════════════════════════════════════════════════ */

*,
*::before,
*::after {
    box-sizing: border-box;
}

/* ── PÁGINA ── */
.q-page {
    min-height: 100vh;
    overflow-x: hidden;
    overflow-y: scroll;
    background:
        radial-gradient(
            ellipse 55% 60% at -5% -5%,
            rgba(27, 47, 110, 0.65) 0%,
            transparent 60%
        ),
        radial-gradient(
            ellipse 50% 55% at 105% 105%,
            rgba(60, 201, 123, 0.65) 0%,
            transparent 60%
        ),
        radial-gradient(
            ellipse 30% 35% at 80% 3%,
            rgba(60, 201, 123, 0.3) 0%,
            transparent 55%
        ),
        #e6eff0;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-bottom: 60px;
}

/* ── NAV ── */
.q-nav {
    width: 100%;
    max-width: 1140px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22px 24px 0;
}
.q-logo {
    display: flex;
    align-items: center;
    gap: 9px;
    text-decoration: none;
}
.q-logo img {
    height: 32px;
}
.q-wordmark {
    font-size: 20px;
    font-weight: 800;
    color: #111f4d;
    letter-spacing: -0.03em;
}
.q-wordmark em {
    color: #3cc97b;
    font-style: normal;
}
.q-back {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    border-radius: 50px;
    border: 1.5px solid rgba(27, 47, 110, 0.14);
    background: rgba(255, 255, 255, 0.55);
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #1b2f6e;
    cursor: pointer;
    transition: all 0.2s;
}
.q-back:hover {
    background: rgba(255, 255, 255, 0.88);
    border-color: #3cc97b;
    color: #28a866;
}

/* ── LAYOUT SPLIT ── */
.q-layout {
    width: 100%;
    max-width: 1140px;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
    padding: 32px 24px 0;
}
@media (max-width: 900px) {
    .q-layout {
        grid-template-columns: 1fr;
    }
    .q-receipt {
        position: static;
        top: auto;
    }
}

/* ══════ COLUMNA FORM ══════ */
.q-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* ── BARRA DE PROGRESO ── */
.q-bar {
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 20px;
    padding: 18px 24px;
    box-shadow: 0 2px 12px rgba(27, 47, 110, 0.07);
    position: relative;
}
.q-bar-steps {
    display: flex;
    justify-content: space-between;
    position: relative;
    z-index: 1;
}
.q-bar-line {
    position: absolute;
    top: 50%;
    left: 56px;
    right: 56px;
    height: 2px;
    background: rgba(27, 47, 110, 0.1);
    transform: translateY(-50%);
    z-index: 0;
    border-radius: 2px;
    overflow: hidden;
}
.q-bar-fill {
    height: 100%;
    background: #3cc97b;
    transition: width 0.4s ease;
    border-radius: 2px;
}
.q-bar-dot {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    cursor: default;
    flex: 1;
}
.q-bar-dot.done {
    cursor: pointer;
}
.q-dot-num {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.8);
    color: #9aa5b4;
    border: 2px solid rgba(27, 47, 110, 0.1);
    transition: all 0.25s;
    position: relative;
    z-index: 1;
    box-shadow: 0 2px 6px rgba(27, 47, 110, 0.06);
}
.q-bar-dot.active .q-dot-num {
    background: #1b2f6e;
    border-color: #1b2f6e;
    color: #fff;
    box-shadow: 0 4px 14px rgba(27, 47, 110, 0.3);
    transform: scale(1.08);
}
.q-bar-dot.done .q-dot-num {
    background: #3cc97b;
    border-color: #3cc97b;
    color: #fff;
    box-shadow: 0 4px 14px rgba(60, 201, 123, 0.28);
}
.q-dot-lbl {
    font-size: 10.5px;
    font-weight: 600;
    color: #9aa5b4;
}
.q-bar-dot.active .q-dot-lbl {
    color: #1b2f6e;
    font-weight: 700;
}
.q-bar-dot.done .q-dot-lbl {
    color: #3cc97b;
}

/* ── PANEL DE PASO ── */
.q-step {
    background: rgba(255, 255, 255, 0.82);
    backdrop-filter: blur(28px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.65);
    border-radius: 24px;
    box-shadow:
        0 8px 36px rgba(27, 47, 110, 0.09),
        inset 0 1px 0 rgba(255, 255, 255, 0.85);
    overflow: hidden;
    position: relative;
}
/* Acento superior del paso */
.q-step::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #1b2f6e, #3cc97b);
}

/* Header del paso */
.q-step-head {
    display: flex;
    align-items: flex-start;
    gap: 18px;
    padding: 32px 32px 20px;
    border-bottom: 1px solid rgba(27, 47, 110, 0.06);
}
.q-step-num {
    font-size: 42px;
    font-weight: 800;
    line-height: 1;
    color: rgba(27, 47, 110, 0.08);
    letter-spacing: -0.05em;
    flex-shrink: 0;
    margin-top: -4px;
    font-style: italic;
}
.q-step-head h2 {
    font-size: clamp(22px, 3vw, 30px);
    font-weight: 800;
    color: #111f4d;
    letter-spacing: -0.025em;
    line-height: 1.15;
    margin: 0 0 6px;
}
.q-step-head p {
    font-size: 14px;
    color: #8896a5;
    margin: 0;
    line-height: 1.5;
}

/* Footer del paso */
.q-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 32px 24px;
    border-top: 1px solid rgba(27, 47, 110, 0.06);
    margin-top: 8px;
}
.q-btn-next {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 30px;
    border-radius: 50px;
    border: none;
    background: linear-gradient(135deg, #1b2f6e, #111f4d);
    color: #fff;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(27, 47, 110, 0.28);
    transition: all 0.22s;
}
.q-btn-next:disabled {
    background: rgba(190, 195, 205, 0.7);
    box-shadow: none;
    cursor: not-allowed;
}
.q-btn-next:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(27, 47, 110, 0.38);
}
.q-btn-back {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 12px 20px;
    border-radius: 50px;
    border: 1.5px solid rgba(27, 47, 110, 0.14);
    background: transparent;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #637087;
    cursor: pointer;
    transition: all 0.2s;
}
.q-btn-back:hover {
    border-color: #3cc97b;
    color: #28a866;
    background: rgba(60, 201, 123, 0.04);
}

/* ── PASO 1: Tipo de instalación ── */
.q-type-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    padding: 24px 32px 32px;
}
.q-type-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 18px;
    border-radius: 18px;
    border: 2px solid rgba(27, 47, 110, 0.1);
    background: rgba(255, 255, 255, 0.55);
    cursor: pointer;
    transition: all 0.22s;
    text-align: left;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    box-shadow: 0 2px 8px rgba(27, 47, 110, 0.04);
}
.q-type-card:hover {
    border-color: rgba(60, 201, 123, 0.45);
    background: rgba(255, 255, 255, 0.88);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(27, 47, 110, 0.09);
}
.q-type-card.active {
    border-color: #3cc97b;
    background: rgba(255, 255, 255, 0.95);
    box-shadow:
        0 0 0 3px rgba(60, 201, 123, 0.12),
        0 8px 24px rgba(27, 47, 110, 0.08);
}
.qtc-icon-wrap {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    flex-shrink: 0;
    background: rgba(27, 47, 110, 0.07);
    color: #1b2f6e;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    transition: all 0.22s;
}
.q-type-card.active .qtc-icon-wrap {
    background: rgba(60, 201, 123, 0.15);
    color: #28a866;
}
.qtc-body {
    flex: 1;
    min-width: 0;
}
.qtc-body strong {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: #111f4d;
    margin-bottom: 3px;
}
.qtc-body span {
    font-size: 12px;
    color: #8896a5;
    line-height: 1.4;
}
.qtc-check {
    font-size: 18px;
    color: #3cc97b;
    opacity: 0;
    transition: opacity 0.2s;
    flex-shrink: 0;
}
.q-type-card.active .qtc-check {
    opacity: 1;
}
@media (max-width: 600px) {
    .q-type-grid {
        grid-template-columns: 1fr;
    }
}

/* ── PASO 2: Cargador ── */
.q-section-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #8896a5;
    padding: 20px 32px 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.q-section-label i {
    color: #f59e0b;
}

/* Filas de cargador — la clave del diseño */
.q-rec-list,
.q-all-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 0 32px;
}
.q-all-list {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 28px;
}
.q-all-list::-webkit-scrollbar {
    width: 4px;
}
.q-all-list::-webkit-scrollbar-thumb {
    background: rgba(27, 47, 110, 0.15);
    border-radius: 4px;
}

.q-charger-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 16px;
    border-radius: 14px;
    border: 1.5px solid rgba(27, 47, 110, 0.08);
    background: rgba(255, 255, 255, 0.55);
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
    text-align: left;
    position: relative;
}
.q-charger-row:hover {
    border-color: rgba(60, 201, 123, 0.4);
    background: rgba(255, 255, 255, 0.85);
    transform: translateX(3px);
}
.q-charger-row.active {
    border-color: #3cc97b;
    background: rgba(255, 255, 255, 0.95);
    box-shadow:
        0 0 0 2px rgba(60, 201, 123, 0.15),
        0 4px 16px rgba(27, 47, 110, 0.07);
    transform: none;
}
.q-charger-row--rec {
    padding: 16px 18px;
}

.qcr-brand {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 12px;
}
.qcr-brand--sm {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    font-size: 11px;
}
.qcr-info {
    flex: 1;
    min-width: 0;
}
.qcr-name {
    display: block;
    font-size: 13.5px;
    font-weight: 600;
    color: #111f4d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.q-charger-row.active .qcr-name {
    color: #0b3522;
}
.qcr-specs {
    font-size: 11.5px;
    color: #8896a5;
    display: block;
    margin-top: 2px;
}
.q-charger-row.active .qcr-specs {
    color: #28a866;
}
.qcr-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    flex-shrink: 0;
}
.qcr-price {
    font-size: 15px;
    font-weight: 800;
    color: #111f4d;
    letter-spacing: -0.02em;
    white-space: nowrap;
}
.q-charger-row.active .qcr-price {
    color: #0b3522;
}
.qcr-pdf {
    font-size: 11px;
    color: #1b2f6e;
    opacity: 0.4;
    transition: opacity 0.15s;
    text-decoration: none;
}
.qcr-pdf:hover {
    opacity: 1;
}
.qcr-check {
    font-size: 17px;
    color: #3cc97b;
    opacity: 0;
    transition: opacity 0.2s;
    flex-shrink: 0;
    margin-left: 4px;
}
.q-charger-row.active .qcr-check {
    opacity: 1;
}

/* Toggle ver todos */
.q-toggle-all {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 10px 32px 0;
    padding: 10px 16px;
    border-radius: 10px;
    border: 1px dashed rgba(27, 47, 110, 0.18);
    background: transparent;
    color: #637087;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    width: calc(100% - 64px);
    justify-content: center;
}
.q-toggle-all:hover {
    border-color: #3cc97b;
    color: #28a866;
    background: rgba(60, 201, 123, 0.04);
}

.q-all-chargers {
    margin-top: 12px;
}
.q-brand-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 0 32px 10px;
}
.q-brand-btn {
    padding: 5px 13px;
    border-radius: 999px;
    border: 1px solid rgba(27, 47, 110, 0.12);
    background: rgba(255, 255, 255, 0.55);
    color: #1b2f6e;
    font-size: 11.5px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.18s;
    white-space: nowrap;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
}
.q-brand-btn:hover {
    border-color: rgba(60, 201, 123, 0.4);
    background: rgba(255, 255, 255, 0.88);
}
.q-brand-btn.active {
    background: #1b2f6e;
    border-color: #1b2f6e;
    color: #fff;
}

/* ── PASO 3: Instalación ── */
.q-meters-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    padding: 0 32px;
}
@media (max-width: 600px) {
    .q-meters-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
.q-meter-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    padding: 12px 8px;
    border-radius: 12px;
    border: 1.5px solid rgba(27, 47, 110, 0.1);
    background: rgba(255, 255, 255, 0.55);
    cursor: pointer;
    transition: all 0.2s;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
}
.q-meter-btn:hover {
    border-color: rgba(60, 201, 123, 0.4);
    background: rgba(255, 255, 255, 0.85);
    transform: translateY(-1px);
}
.q-meter-btn.active {
    border-color: #3cc97b;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 0 0 2px rgba(60, 201, 123, 0.15);
}
.qmb-val {
    font-size: 16px;
    font-weight: 800;
    color: #111f4d;
    letter-spacing: -0.02em;
}
.q-meter-btn.active .qmb-val {
    color: #0b3522;
}
.qmb-desc {
    font-size: 10px;
    font-weight: 500;
    color: #8896a5;
}
.q-meter-btn.active .qmb-desc {
    color: #28a866;
}

.q-meter-custom {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 32px 0;
}
.q-meter-custom label {
    font-size: 12px;
    color: #8896a5;
    white-space: nowrap;
    font-weight: 500;
}
.q-meter-input-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}
.q-meter-input-wrap input {
    width: 70px;
    height: 38px;
    border: 1.5px solid rgba(27, 47, 110, 0.12);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.8);
    padding: 0 10px;
    font-size: 14px;
    font-weight: 700;
    color: #111f4d;
    outline: none;
    text-align: center;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    transition: border-color 0.2s;
}
.q-meter-input-wrap input:focus {
    border-color: #3cc97b;
    box-shadow: 0 0 0 3px rgba(60, 201, 123, 0.1);
}
.q-meter-input-wrap span {
    font-size: 13px;
    color: #8896a5;
    font-weight: 500;
}

.q-hint {
    font-size: 11.5px;
    color: #8896a5;
    margin: 10px 32px 0;
    display: flex;
    align-items: flex-start;
    gap: 6px;
    line-height: 1.55;
}
.q-hint i {
    color: #3cc97b;
    flex-shrink: 0;
    margin-top: 1px;
}

.q-solar-grid {
    display: flex;
    gap: 10px;
    padding: 0 32px;
    flex-wrap: wrap;
}
.q-solar-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 20px;
    border-radius: 50px;
    border: 1.5px solid rgba(27, 47, 110, 0.1);
    background: rgba(255, 255, 255, 0.55);
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #1b2f6e;
    cursor: pointer;
    transition: all 0.2s;
}
.q-solar-btn:hover {
    border-color: rgba(60, 201, 123, 0.4);
    background: rgba(255, 255, 255, 0.88);
}
.q-solar-btn.active {
    border-color: #3cc97b;
    background: #3cc97b;
    color: #fff;
    box-shadow: 0 4px 14px rgba(60, 201, 123, 0.28);
}
.q-solar-btn--iva.active {
    border-color: #d97706;
    background: #d97706;
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.28);
}

/* ── PASO 4: Datos ── */
.q-client-loaded {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0 32px;
    padding: 14px 18px;
    border-radius: 14px;
    background: rgba(60, 201, 123, 0.08);
    border: 1px solid rgba(60, 201, 123, 0.2);
    font-size: 13.5px;
    color: #0b3522;
    font-weight: 500;
}
.q-client-loaded i {
    font-size: 20px;
    color: #28a866;
    flex-shrink: 0;
}

.q-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 20px 32px 0;
}
.q-fields .full {
    grid-column: 1/-1;
}
@media (max-width: 600px) {
    .q-fields {
        grid-template-columns: 1fr;
    }
}
.q-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.q-field label {
    font-size: 12px;
    font-weight: 600;
    color: #637087;
}
.q-field label span {
    color: #dc2626;
    margin-left: 2px;
}
.q-field input {
    height: 46px;
    border: 1.5px solid rgba(27, 47, 110, 0.12);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.8);
    padding: 0 14px;
    font-size: 14px;
    color: #111f4d;
    outline: none;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}
.q-field input:focus {
    border-color: #3cc97b;
    box-shadow: 0 0 0 3px rgba(60, 201, 123, 0.1);
    background: #fff;
}
.q-field.err input {
    border-color: #dc2626;
}
.q-ferr {
    font-size: 11px;
    color: #dc2626;
    font-weight: 500;
}

.q-final-block {
    margin: 20px 32px 0;
    padding: 20px;
    border-radius: 16px;
    background: rgba(27, 47, 110, 0.04);
    border: 1px solid rgba(27, 47, 110, 0.08);
}
.q-final-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 14px;
    border-bottom: 1px solid rgba(27, 47, 110, 0.08);
    margin-bottom: 14px;
}
.q-final-total span {
    font-size: 14px;
    font-weight: 600;
    color: #637087;
}
.q-final-total strong {
    font-size: 28px;
    font-weight: 800;
    color: #111f4d;
    letter-spacing: -0.04em;
    line-height: 1;
}
.q-final-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.q-btn-pdf {
    min-height: 46px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #1b2f6e, #111f4d);
    color: #fff;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: 0 6px 18px rgba(27, 47, 110, 0.24);
    transition: all 0.22s;
}
.q-btn-pdf:disabled {
    background: rgba(180, 180, 180, 0.6);
    box-shadow: none;
    cursor: not-allowed;
}
.q-btn-pdf:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(27, 47, 110, 0.34);
}
.q-btn-pay {
    min-height: 46px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #3cc97b, #28a866);
    color: #0b3522;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: 0 6px 18px rgba(60, 201, 123, 0.28);
    transition: all 0.22s;
}
.q-btn-pay:disabled {
    background: rgba(180, 180, 180, 0.6);
    color: #fff;
    box-shadow: none;
    cursor: not-allowed;
}
.q-btn-pay:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(60, 201, 123, 0.4);
}

/* ══════ COLUMNA RECIBO ══════ */
.q-receipt {
    position: sticky;
    top: 20px;
}
.q-receipt-inner {
    background: rgba(255, 255, 255, 0.82);
    backdrop-filter: blur(28px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.65);
    border-radius: 24px;
    box-shadow:
        0 8px 36px rgba(27, 47, 110, 0.09),
        inset 0 1px 0 rgba(255, 255, 255, 0.85);
    overflow: hidden;
}
.qr-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid rgba(27, 47, 110, 0.07);
}
.qr-head-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    flex-shrink: 0;
    background: rgba(27, 47, 110, 0.07);
    color: #1b2f6e;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
}
.qr-head-title {
    font-size: 15px;
    font-weight: 700;
    color: #111f4d;
}
.qr-head-sub {
    font-size: 11.5px;
    color: #8896a5;
    margin-top: 2px;
}

/* Selecciones */
.qr-selections {
    padding: 14px 18px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.qr-sel {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(27, 47, 110, 0.04);
    transition: background 0.25s;
}
.qr-sel.filled {
    background: rgba(255, 255, 255, 0.7);
}
.qrs-icon {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    flex-shrink: 0;
    background: rgba(27, 47, 110, 0.08);
    color: #9aa5b4;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.25s;
}
.qr-sel.filled .qrs-icon {
    background: rgba(27, 47, 110, 0.1);
    color: #1b2f6e;
}
.qrs-content {
    flex: 1;
    min-width: 0;
}
.qrs-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #9aa5b4;
    display: block;
    margin-bottom: 2px;
}
.qrs-value {
    font-size: 12.5px;
    font-weight: 600;
    color: #111f4d;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.qrs-empty {
    font-size: 12px;
    color: #c0c9d3;
    display: block;
}
.qrs-check {
    font-size: 14px;
    color: #3cc97b;
    flex-shrink: 0;
}

/* Líneas de precio */
.qr-divider {
    height: 1px;
    background: rgba(27, 47, 110, 0.07);
    margin: 0 18px;
}
.qr-lines {
    padding: 12px 18px;
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.qr-line {
    display: flex;
    justify-content: space-between;
    font-size: 12.5px;
    color: #637087;
}
.qr-line--vat {
    color: #9aa5b4;
    font-size: 12px;
}
.qr-line--solar {
    color: #d97706;
    font-weight: 600;
    background: rgba(245, 158, 11, 0.06);
    padding: 5px 8px;
    border-radius: 8px;
    margin: 2px 0;
}
.qr-line--solar span:last-child {
    color: #d97706;
    font-weight: 700;
}
.qr-sel--solar {
    background: rgba(245, 158, 11, 0.07) !important;
}
.qrs-icon--solar {
    background: rgba(245, 158, 11, 0.12) !important;
    color: #d97706 !important;
}
.qr-sel--iva {
    background: rgba(217, 119, 6, 0.07) !important;
}
.qrs-icon--iva {
    background: rgba(217, 119, 6, 0.12) !important;
    color: #d97706 !important;
}

/* Total */
.qr-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 18px;
    background: linear-gradient(
        135deg,
        rgba(27, 47, 110, 0.06),
        rgba(60, 201, 123, 0.08)
    );
    border-top: 1px solid rgba(27, 47, 110, 0.07);
}
.qr-total span {
    font-size: 13px;
    font-weight: 600;
    color: #111f4d;
}
.qr-total strong {
    font-size: 26px;
    font-weight: 800;
    color: #111f4d;
    letter-spacing: -0.04em;
    line-height: 1;
}

.qr-empty {
    padding: 30px 18px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    text-align: center;
    font-size: 13px;
    color: #9aa5b4;
    line-height: 1.6;
}
.qr-empty i {
    font-size: 28px;
    color: rgba(27, 47, 110, 0.15);
}

/* ── CARGA / ÉXITO ── */
.q-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    gap: 12px;
    font-size: 14px;
    color: #637087;
}
.q-loading i {
    font-size: 28px;
    color: #1b2f6e;
}
.ok-wrap {
    width: 100%;
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}
.ok-card {
    max-width: 500px;
    width: 100%;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(28px);
    border: 1px solid rgba(255, 255, 255, 0.65);
    border-radius: 28px;
    box-shadow: 0 20px 50px rgba(27, 47, 110, 0.12);
    padding: 40px 32px;
    text-align: center;
}
.ok-icon {
    width: 66px;
    height: 66px;
    border-radius: 50%;
    background: rgba(60, 201, 123, 0.15);
    color: #28a866;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin: 0 auto 18px;
}
.ok-card h1 {
    font-size: 24px;
    font-weight: 800;
    color: #111f4d;
    margin-bottom: 10px;
}
.ok-card p {
    font-size: 14px;
    color: #637087;
    margin-bottom: 20px;
}
.ok-rows {
    border: 1px solid rgba(27, 47, 110, 0.1);
    border-radius: 14px;
    overflow: hidden;
    margin: 16px 0;
    text-align: left;
}
.ok-rows div {
    display: flex;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid rgba(27, 47, 110, 0.07);
    font-size: 13px;
}
.ok-rows div:last-child {
    border-bottom: none;
}
.ok-rows span {
    color: #637087;
}
.ok-rows strong {
    color: #111f4d;
}
.ok-btn {
    width: 100%;
    min-height: 50px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #1b2f6e, #111f4d);
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 10px 26px rgba(27, 47, 110, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.22s;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
}
.ok-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(27, 47, 110, 0.4);
}
.ok-btn-sec {
    width: 100%;
    min-height: 44px;
    border: 1.5px solid rgba(27, 47, 110, 0.12);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.6);
    color: #1b2f6e;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    margin-bottom: 10px;
    transition: background 0.2s;
    font-family: "Plus Jakarta Sans", system-ui, sans-serif;
}

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .q-page {
        padding-bottom: 20px;
    }

    .q-nav {
        padding: 14px 14px 0;
    }
    .q-logo img {
        height: 24px;
    }
    .q-wordmark {
        font-size: 15px;
    }
    .q-back {
        padding: 8px 14px;
        font-size: 11.5px;
        gap: 5px;
    }

    .q-layout {
        padding: 12px 12px 0;
        gap: 12px;
    }
    .q-form {
        gap: 12px;
    }

    /* Progress bar compacto */
    .q-bar {
        padding: 13px 16px;
        border-radius: 14px;
    }
    .q-bar-line {
        left: 34px;
        right: 34px;
    }
    .q-dot-num {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }
    .q-dot-lbl {
        font-size: 9.5px;
        text-align: center;
    }

    /* Panel paso compacto */
    .q-step {
        border-radius: 18px;
    }
    .q-step::before {
        height: 4px;
    }
    .q-step-head {
        padding: 18px 16px 14px;
        gap: 12px;
        align-items: center;
    }
    .q-step-num {
        font-size: 30px;
        margin-top: 0;
    }
    .q-step-head h2 {
        font-size: 19px;
        line-height: 1.2;
        margin-bottom: 3px;
    }
    .q-step-head p {
        font-size: 12px;
    }

    .q-foot {
        padding: 14px 16px 18px;
    }
    .q-btn-next {
        padding: 13px 22px;
        font-size: 14px;
        min-height: 46px;
    }
    .q-btn-back {
        padding: 12px 18px;
        font-size: 13px;
        min-height: 46px;
    }

    /* Section label */
    .q-section-label {
        padding: 14px 16px 8px;
        font-size: 10.5px;
    }

    /* Cards tipo — filas a lo ancho con subtítulo (más legibles y táctiles) */
    /* padding inferior: el paso 1 no tiene pie, así las tarjetas no quedan pegadas al recibo */
    .q-type-grid {
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 14px 16px 24px;
    }
    .q-type-card {
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
        padding: 16px;
        gap: 14px;
        border-radius: 14px;
        text-align: left;
    }
    .q-type-card:hover {
        transform: none;
    }
    .qtc-icon-wrap {
        width: 46px;
        height: 46px;
        font-size: 20px;
        border-radius: 12px;
    }
    .qtc-body strong {
        font-size: 14px;
    }
    .qtc-body span {
        display: block;
        font-size: 11.5px;
    }
    .qtc-check {
        display: block;
        font-size: 18px;
    }

    /* Cargadores */
    .q-rec-list,
    .q-all-list {
        padding: 0 16px;
        gap: 7px;
    }
    .q-charger-row {
        padding: 12px;
        gap: 11px;
        border-radius: 12px;
    }
    .q-charger-row:hover {
        transform: none;
    }
    .q-charger-row--rec {
        padding: 13px;
    }
    .qcr-brand {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        font-size: 11px;
    }
    .qcr-brand--sm {
        width: 34px;
        height: 34px;
    }
    .qcr-name {
        font-size: 13px;
        white-space: normal;
        line-height: 1.3;
    }
    .qcr-specs {
        font-size: 11px;
    }
    .qcr-price {
        font-size: 14px;
    }
    .qcr-pdf {
        font-size: 15px;
        opacity: 0.55;
        padding: 4px;
    }
    .qcr-check {
        font-size: 16px;
    }

    /* "Ver todos" + lista completa AHORA visibles en móvil */
    .q-toggle-all {
        display: flex;
        margin: 10px 16px 0;
        width: calc(100% - 32px);
        padding: 12px 16px;
        font-size: 13px;
        min-height: 46px;
        border-radius: 12px;
    }
    .q-all-chargers {
        display: block;
    }
    .q-brand-filter {
        padding: 0 16px 8px;
        gap: 7px;
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .q-brand-filter::-webkit-scrollbar {
        display: none;
    }
    .q-brand-btn {
        padding: 7px 14px;
        font-size: 12px;
        flex-shrink: 0;
        min-height: 34px;
    }
    .q-all-list {
        max-height: 300px;
        padding-right: 12px;
    }

    /* Metros */
    .q-meters-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 7px;
        padding: 0 16px;
    }
    .q-meter-btn {
        padding: 11px 4px;
        border-radius: 10px;
        min-height: 52px;
    }
    .q-meter-btn:hover {
        transform: none;
    }
    .qmb-val {
        font-size: 14px;
    }
    .qmb-desc {
        font-size: 9px;
    }
    .q-meter-custom {
        padding: 12px 16px 0;
        gap: 12px;
        flex-wrap: wrap;
    }
    .q-meter-custom label {
        font-size: 11.5px;
        white-space: normal;
    }
    .q-meter-input-wrap input {
        width: 64px;
        height: 42px;
        font-size: 16px;
    }
    .q-hint {
        margin: 9px 16px 0;
        font-size: 11px;
    }
    .q-solar-grid {
        padding: 0 16px;
        gap: 8px;
        flex-direction: column;
    }
    .q-solar-btn {
        padding: 13px 16px;
        font-size: 13px;
        justify-content: flex-start;
        min-height: 48px;
    }

    /* Datos — input a 16px evita el zoom automático de iOS al enfocar */
    .q-fields {
        grid-template-columns: 1fr;
        gap: 12px;
        padding: 16px 16px 0;
    }
    .q-field input {
        height: 48px;
        font-size: 16px;
    }
    .q-final-block {
        margin: 16px 16px 0;
        padding: 16px;
    }
    .q-final-total strong {
        font-size: 24px;
    }
    .q-final-actions {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .q-btn-pdf,
    .q-btn-pay {
        min-height: 50px;
        font-size: 14px;
    }
    .q-client-loaded {
        margin: 0 16px;
        font-size: 12.5px;
        padding: 12px 14px;
        flex-wrap: wrap;
    }

    /* ── RECIBO ahora visible (compacto) bajo el formulario ── */
    .q-receipt-inner {
        border-radius: 18px;
    }
    .qr-head {
        padding: 16px 16px 14px;
    }
    .qr-selections {
        display: none;
    } /* el desglose de precio ya resume las elecciones */
    .qr-lines {
        padding: 14px 16px;
    }
    .qr-line {
        font-size: 13px;
    }
    .qr-total {
        padding: 16px;
    }
    .qr-total span {
        font-size: 13.5px;
    }
    .qr-total strong {
        font-size: 24px;
    }
    .qr-empty {
        padding: 22px 16px;
    }

    /* ── ÉXITO DE PAGO ── */
    .ok-wrap {
        padding: 24px 16px;
        min-height: 60vh;
    }
    .ok-card {
        padding: 28px 20px;
        border-radius: 22px;
    }
    .ok-icon {
        width: 58px;
        height: 58px;
        font-size: 28px;
    }
    .ok-card h1 {
        font-size: 21px;
    }
    .ok-card p {
        font-size: 13px;
    }
}

/* Móviles muy pequeños */


/* FINANCIACIÓN */
.q-financing-panel {
    margin: 14px 0 0;
    border: 1.5px solid rgba(27, 47, 110, 0.1);
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.72);
    padding: 14px;
}
.q-financing-top {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: center;
}
.q-financing-top strong {
    display: block;
    color: #111f4d;
    font-size: 14px;
    font-weight: 800;
    margin-bottom: 3px;
}
.q-financing-top span {
    display: block;
    color: #8896a5;
    font-size: 12px;
    line-height: 1.35;
}
.q-switch {
    position: relative;
    width: 48px;
    height: 28px;
    flex-shrink: 0;
    cursor: pointer;
}
.q-switch input {
    display: none;
}
.q-switch span {
    position: absolute;
    inset: 0;
    border-radius: 999px;
    background: rgba(27, 47, 110, 0.16);
    transition: all 0.2s;
}
.q-switch span::after {
    content: "";
    position: absolute;
    top: 4px;
    left: 4px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 2px 8px rgba(27, 47, 110, 0.18);
    transition: all 0.2s;
}
.q-switch input:checked + span {
    background: #3cc97b;
}
.q-switch input:checked + span::after {
    transform: translateX(20px);
}
.q-financing-plans {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(112px, 1fr));
    gap: 8px;
    margin-top: 14px;
}
.q-finance-plan {
    border: 1.5px solid rgba(27, 47, 110, 0.1);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.78);
    padding: 10px 9px;
    text-align: left;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
}
.q-finance-plan:hover {
    border-color: rgba(60, 201, 123, 0.45);
    transform: translateY(-1px);
}
.q-finance-plan.active {
    border-color: #3cc97b;
    background: rgba(60, 201, 123, 0.08);
    box-shadow: 0 0 0 2px rgba(60, 201, 123, 0.12);
}
.q-finance-plan strong,
.q-finance-plan span,
.q-finance-plan em {
    display: block;
}
.q-finance-plan strong {
    color: #111f4d;
    font-size: 13px;
    font-weight: 800;
}
.q-finance-plan span {
    color: #8896a5;
    font-size: 10.5px;
    font-weight: 600;
    margin-top: 2px;
}
.q-finance-plan em {
    color: #28a866;
    font-size: 12px;
    font-weight: 800;
    font-style: normal;
    margin-top: 5px;
}
.q-financing-result {
    margin-top: 12px;
    border: 1px solid rgba(27, 47, 110, 0.08);
    border-radius: 12px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.65);
}
.q-financing-result div {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 9px 12px;
    border-bottom: 1px solid rgba(27, 47, 110, 0.06);
    font-size: 12px;
}
.q-financing-result div:last-child {
    border-bottom: 0;
}
.q-financing-result span {
    color: #637087;
    font-weight: 600;
}
.q-financing-result strong {
    color: #111f4d;
    font-weight: 800;
    text-align: right;
}

.q-financing-action-note {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgba(27, 47, 110, 0.06);
    color: #637087;
    font-size: 12.5px;
    font-weight: 600;
    line-height: 1.35;
}
.q-financing-action-note i {
    color: #3cc97b;
}

.qr-financing {
    margin: 10px 18px 0;
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(60, 201, 123, 0.08);
    border: 1px solid rgba(60, 201, 123, 0.18);
}
.qr-financing-title {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #0b3522;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 6px;
}
.qr-financing .qr-line {
    padding: 5px 0;
}

@media (max-width: 400px) {
    .q-wordmark {
        font-size: 14px;
    }
    .q-step-num {
        font-size: 26px;
    }
    .q-step-head h2 {
        font-size: 17.5px;
    }
    .q-meters-grid {
        gap: 6px;
    }
    .qmb-val {
        font-size: 13px;
    }
    .qmb-desc {
        font-size: 8.5px;
    }
    .qcr-brand {
        width: 34px;
        height: 34px;
    }
}
</style>