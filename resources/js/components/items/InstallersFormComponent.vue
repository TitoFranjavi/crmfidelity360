<template>
  <section class="acta-page">

    <!-- NAV -->
    <nav class="a-nav">
      <a href="/" class="a-logo">
        <img src="https://segenet.es/wp-content/uploads/isotipo_2.png" alt="Segenet"
          @error="$event.target.style.display='none'" />
        <span class="a-wordmark">Segenet <em>Movilidad</em></span>
      </a>
      <a href="https://movilidad.segenet.es" class="a-back" target="_top">
        <i class="far fa-arrow-left"></i> Volver
      </a>
    </nav>

    <!-- ÉXITO -->
    <div v-if="isSuccess" class="ok-wrap">
      <div class="ok-card">
        <div class="ok-icon"><i class="far fa-circle-check"></i></div>
        <h1>Acta registrada</h1>
        <p>El acta y todos los archivos han sido guardados<br />en la oportunidad correctamente.</p>
        <div class="ok-rows">
          <div v-if="effectiveCableM > 0">
            <span><i class="fas fa-bolt"></i> Cable</span><strong>{{ effectiveCableM }} m</strong>
          </div>
          <div v-if="effectiveTuboM > 0">
            <span><i class="fas fa-ruler-horizontal"></i> Tubo</span><strong>{{ effectiveTuboM }} m</strong>
          </div>
          <div v-if="horaEntrada">
            <span><i class="far fa-clock"></i> Horario</span><strong>{{ horaEntrada }} → {{ horaSalida }}</strong>
          </div>
          <div v-if="attachments.length">
            <span><i class="fas fa-paperclip"></i> Archivos</span>
            <strong>{{ attachments.length }} adjunto{{ attachments.length > 1 ? 's' : '' }}</strong>
          </div>
          <div>
            <span><i class="fas fa-signature"></i> Firma</span>
            <strong style="color:#28a866;">Firmado ✓</strong>
          </div>
          <div>
            <span><i class="fas fa-file-zipper"></i> Paquete</span>
            <strong>ZIP adjuntado a la oportunidad</strong>
          </div>
        </div>
        <button class="ok-btn" @click="resetForm">
          <i class="far fa-plus"></i> Registrar nueva acta
        </button>
      </div>
    </div>

    <!-- LAYOUT PRINCIPAL -->
    <template v-if="!isSuccess">
      <div class="a-layout">

        <!-- FORMULARIO -->
        <div class="a-form">
          <div class="a-page-title">
            <h1>Acta de instalación</h1>
            <p>Cargador de vehículo eléctrico · Segenet Movilidad</p>
          </div>

          <!-- Card cliente (cargado desde URL ?id=) -->
          <div v-if="isLoadingOpportunity" class="a-opp-loading">
            <i class="fas fa-spinner fa-spin"></i> Cargando datos del cliente...
          </div>
          <div v-else-if="opportunityData" class="a-opp-card">
            <div class="a-opp-icon"><i class="far fa-user-check"></i></div>
            <div class="a-opp-info">
              <strong>{{ opportunityData.name }}</strong>
              <span v-if="opportunityData.phone"> · {{ opportunityData.phone }}</span>
              <span v-if="opportunityData.email"> · {{ opportunityData.email }}</span>
            </div>
          </div>

          <!-- Barra de progreso -->
          <div class="a-bar">
            <div class="a-bar-steps">
              <span v-for="(lbl, i) in stepLabels" :key="i" class="a-bar-dot"
                :class="{ active: currentStep === i+1, done: currentStep > i+1 }"
                @click="currentStep > i+1 && goToStep(i+1)">
                <span class="a-dot-num">
                  <i v-if="currentStep > i+1" class="fas fa-check"></i>
                  <span v-else>{{ i+1 }}</span>
                </span>
                <span class="a-dot-lbl">{{ lbl }}</span>
              </span>
            </div>
            <div class="a-bar-line">
              <div class="a-bar-fill" :style="{ width: barFill + '%' }"></div>
            </div>
          </div>

          <!-- PASO 1: Medidas -->
          <div v-if="currentStep === 1" class="a-step">
            <div class="a-step-head">
              <div class="a-step-num">01</div>
              <div>
                <h2>Medidas de la instalación</h2>
                <p>Metros de cable y tubo utilizados</p>
              </div>
            </div>

            <div class="a-sec-label"><i class="fas fa-bolt"></i> Cable eléctrico</div>
            <div class="a-meter-block">
              <span class="a-field-label">Metros de cable instalados</span>
              <div class="a-presets">
                <button v-for="p in meterPresets" :key="'c'+p.val" class="a-preset"
                  :class="{ active: +cableMeters === p.val }" @click="cableMeters = p.val">
                  <span class="apv">{{ p.label }}</span><span class="apd">{{ p.desc }}</span>
                </button>
              </div>
              <div class="a-custom-row">
                <label>O metros exactos:</label>
                <div class="a-custom-inp">
                  <input type="number" v-model.number="cableMeters" min="0" max="300" placeholder="0" />
                  <span>m</span>
                </div>
              </div>
            </div>

            <div class="a-sep"></div>

            <div class="a-sec-label"><i class="fas fa-ruler-horizontal"></i> Tubo protector</div>
            <div class="a-meter-block">
              <span class="a-field-label">Metros de tubo instalados</span>
              <div class="a-presets">
                <button v-for="p in meterPresets" :key="'t'+p.val" class="a-preset"
                  :class="{ active: +tuboMeters === p.val }" @click="tuboMeters = p.val">
                  <span class="apv">{{ p.label }}</span><span class="apd">{{ p.desc }}</span>
                </button>
              </div>
              <div class="a-custom-row">
                <label>O metros exactos:</label>
                <div class="a-custom-inp">
                  <input type="number" v-model.number="tuboMeters" min="0" max="300" placeholder="0" />
                  <span>m</span>
                </div>
              </div>
            </div>

            <div class="a-foot" style="justify-content:flex-end">
              <button class="a-btn-next" :disabled="!canProceedStep1" @click="canProceedStep1 && goToStep(2)">
                Siguiente <i class="far fa-arrow-right"></i>
              </button>
            </div>
          </div>

          <!-- PASO 2: Horario -->
          <div v-if="currentStep === 2" class="a-step">
            <div class="a-step-head">
              <div class="a-step-num">02</div>
              <div>
                <h2>Horario de trabajo</h2>
                <p>Hora de inicio y finalización de la instalación</p>
              </div>
            </div>
            <div class="a-sec-label"><i class="far fa-clock"></i> Registro horario</div>
            <div class="a-time-grid">
              <div class="a-time-field">
                <label>Hora de entrada</label>
                <div class="a-time-iw"><i class="fas fa-hourglass-start"></i>
                  <input type="time" v-model="horaEntrada" />
                </div>
              </div>
              <div class="a-time-field">
                <label>Hora de salida</label>
                <div class="a-time-iw"><i class="fas fa-hourglass-end"></i>
                  <input type="time" v-model="horaSalida" />
                </div>
              </div>
            </div>
            <div v-if="duration" class="a-dur-badge">
              <i class="far fa-clock"></i>
              <span>Duración: <strong>{{ duration }}</strong></span>
            </div>
            <div class="a-foot">
              <button class="a-btn-back" @click="goToStep(1)"><i class="far fa-arrow-left"></i> Atrás</button>
              <button class="a-btn-next" :disabled="!canProceedStep2" @click="canProceedStep2 && goToStep(3)">
                Siguiente <i class="far fa-arrow-right"></i>
              </button>
            </div>
          </div>

          <!-- PASO 3: Archivos -->
          <div v-if="currentStep === 3" class="a-step">
            <div class="a-step-head">
              <div class="a-step-num">03</div>
              <div>
                <h2>Archivos adjuntos</h2>
                <p>Fotos, documentos, vídeos — cualquier evidencia</p>
              </div>
            </div>
            <div class="a-sec-label">
              <i class="fas fa-paperclip"></i> Adjuntos
              <span v-if="attachments.length" class="a-count-badge">{{ attachments.length }}</span>
            </div>
            <div class="a-files-block">
              <div class="a-dropzone" :class="{ dragging: isDragging }"
                @dragover.prevent="isDragging=true" @dragleave.self="isDragging=false"
                @drop.prevent="onDrop" @click="$refs.fileInput.click()">
                <div class="adz-icon">
                  <i :class="isDragging ? 'fas fa-circle-arrow-down' : 'fas fa-cloud-arrow-up'"></i>
                </div>
                <h3>{{ isDragging ? 'Suelta los archivos' : 'Arrastra archivos aquí' }}</h3>
                <p>o <em>haz clic para seleccionar</em> desde tu dispositivo</p>
                <div class="adz-types">
                  <span>Fotos</span><span>PDF</span><span>Vídeos</span><span>Documentos</span><span>Cualquier formato</span>
                </div>
              </div>
              <input ref="fileInput" type="file" multiple style="display:none" @change="onFileChange" />

              <div v-if="attachments.length" class="a-files-toolbar">
                <div class="a-files-pill"><i class="fas fa-check-circle"></i>
                  {{ attachments.length }} archivo{{ attachments.length > 1 ? 's' : '' }} seleccionado{{ attachments.length > 1 ? 's' : '' }}
                </div>
                <button class="a-add-more-btn" @click.stop="$refs.fileInput.click()">
                  <i class="fas fa-plus"></i> Añadir más
                </button>
              </div>

              <div v-if="attachments.length" class="a-files-grid">
                <div v-for="f in attachments" :key="f.id" class="a-file-card">
                  <div v-if="f.isImage && f.url" class="a-fc-thumb">
                    <img :src="f.url" :alt="f.name" />
                  </div>
                  <div v-else class="a-fc-icon" :style="{ background: f.iconBg, color: f.iconColor }">
                    <i :class="f.icon"></i>
                    <span class="a-fc-ext" :style="{ color: f.iconColor }">{{ f.ext }}</span>
                  </div>
                  <div class="a-fc-meta">
                    <span class="a-fc-name" :title="f.name">{{ f.shortName }}</span>
                    <span class="a-fc-size">{{ f.size }}</span>
                  </div>
                  <button class="a-fc-rm" @click.stop="removeFile(f.id)" :title="'Eliminar '+f.name">
                    <i class="fas fa-xmark"></i>
                  </button>
                </div>
              </div>

              <p v-if="!attachments.length" class="a-hint">
                <i class="far fa-circle-info"></i>
                Los archivos son opcionales pero se recomienda adjuntar fotografías como evidencia.
              </p>
            </div>
            <div class="a-foot">
              <button class="a-btn-back" @click="goToStep(2)"><i class="far fa-arrow-left"></i> Atrás</button>
              <button class="a-btn-next" @click="goToStep(4)">Siguiente <i class="far fa-arrow-right"></i></button>
            </div>
          </div>

          <!-- PASO 4: Firma -->
          <div v-if="currentStep === 4" class="a-step">
            <div class="a-step-head">
              <div class="a-step-num">04</div>
              <div>
                <h2>Consentimiento y firma</h2>
                <p>El cliente firma la conformidad con la instalación</p>
              </div>
            </div>
            <div class="a-sec-label"><i class="fas fa-file-signature"></i> Texto de consentimiento</div>
            <div class="a-sig-section">
              <div class="a-consent-text">
                <p>El cliente <strong>declara haber recibido e inspeccionado la instalación</strong> del punto de carga de vehículo eléctrico realizada por <strong>Segenet Movilidad</strong>, quedando conforme con los trabajos realizados.</p>
                <p>El trabajo incluye la instalación del cargador, cableado eléctrico<template v-if="effectiveCableM > 0"> ({{ effectiveCableM }} m)</template>, tubería protectora<template v-if="effectiveTuboM > 0"> ({{ effectiveTuboM }} m)</template> y puesta en marcha del equipo, así como la comprobación de su correcto funcionamiento.</p>
                <p>El cliente autoriza a Segenet Movilidad a registrar este acta junto con la documentación adjunta como evidencia del proyecto. Esta firma constituye la aceptación formal del trabajo realizado.</p>
              </div>

              <div class="a-sig-header">
                <span class="a-field-label">Firma del cliente</span>
                <button class="a-sig-clear" @click="clearSig"><i class="far fa-trash-can"></i> Borrar</button>
              </div>
              <div class="a-sig-wrap" ref="sigWrap">
                <div v-if="!hasFirma" class="a-sig-ph">
                  <i class="fas fa-pen"></i>
                  <span>Firme aquí con el dedo o el ratón</span>
                </div>
                <canvas ref="sigCanvas" class="a-sig-canvas"
                  @mousedown="sigStart" @mousemove="sigMove" @mouseup="sigEnd" @mouseleave="sigEnd"
                  @touchstart.prevent="sigStart" @touchmove.prevent="sigMove" @touchend="sigEnd">
                </canvas>
              </div>
              <p class="a-hint"><i class="far fa-circle-info"></i> Desliza el dedo en pantalla táctil o usa el ratón en escritorio.</p>

              <div class="a-consent-row" @click="consentAccepted = !consentAccepted">
                <div class="a-check-box" :class="{ checked: consentAccepted }">
                  <i v-if="consentAccepted" class="fas fa-check"></i>
                </div>
                <span>He leído y acepto el contenido de este acta. Confirmo que los datos son correctos y doy mi conformidad con los trabajos realizados por <strong>Segenet Movilidad</strong>.</span>
              </div>

              <!-- Aviso de lo que se va a generar -->
              <div v-if="canSubmit" class="a-zip-notice">
                <i class="fas fa-file-zipper"></i>
                <div>
                  <strong>Se generará un paquete ZIP con:</strong>
                  <span>PDF del acta · Firma del cliente{{ attachments.length ? ' · ' + attachments.length + ' archivo' + (attachments.length > 1 ? 's adjuntos' : ' adjunto') : '' }}</span>
                </div>
              </div>
            </div>

            <div class="a-foot">
              <button class="a-btn-back" @click="goToStep(3)"><i class="far fa-arrow-left"></i> Atrás</button>
              <button class="a-btn-submit" :disabled="!canSubmit || isSubmitting" @click="submitForm">
                <template v-if="isSubmitting">
                  <i class="fas fa-spinner fa-spin"></i>
                  {{ submitStatus }}
                </template>
                <template v-else>
                  <i class="fas fa-file-zipper"></i> Guardar acta y adjuntar ZIP
                </template>
              </button>
            </div>
          </div>
        </div>

        <!-- RESUMEN -->
        <div class="a-receipt">
          <div class="a-receipt-inner">
            <div class="ar-head">
              <div class="ar-icon"><i class="far fa-file-lines"></i></div>
              <div><div class="ar-title">Resumen del acta</div><div class="ar-sub">Segenet Movilidad</div></div>
            </div>
            <div class="ar-items">
              <div class="ar-item" :class="{ filled: !!opportunityData }">
                <div class="ar-ico"><i class="far fa-user"></i></div>
                <div class="ar-body">
                  <span class="ar-lbl">Cliente</span>
                  <span class="ar-val" v-if="opportunityData">{{ opportunityData.name }}</span>
                  <span class="ar-val" v-else-if="isLoadingOpportunity" style="color:#8896a5;font-style:italic;">Cargando...</span>
                  <span class="ar-empty" v-else>Sin oportunidad vinculada</span>
                </div>
                <i v-if="opportunityData" class="fas fa-check ar-chk"></i>
              </div>
              <div class="ar-item" :class="{ filled: effectiveCableM > 0 }">
                <div class="ar-ico"><i class="fas fa-bolt"></i></div>
                <div class="ar-body">
                  <span class="ar-lbl">Cable eléctrico</span>
                  <span class="ar-val" v-if="effectiveCableM > 0">{{ effectiveCableM }} metros</span>
                  <span class="ar-empty" v-else>Pendiente</span>
                </div>
                <i v-if="effectiveCableM > 0" class="fas fa-check ar-chk"></i>
              </div>
              <div class="ar-item" :class="{ filled: effectiveTuboM > 0 }">
                <div class="ar-ico"><i class="fas fa-ruler-horizontal"></i></div>
                <div class="ar-body">
                  <span class="ar-lbl">Tubo protector</span>
                  <span class="ar-val" v-if="effectiveTuboM > 0">{{ effectiveTuboM }} metros</span>
                  <span class="ar-empty" v-else>Pendiente</span>
                </div>
                <i v-if="effectiveTuboM > 0" class="fas fa-check ar-chk"></i>
              </div>
              <div class="ar-item" :class="{ filled: horaEntrada && horaSalida }">
                <div class="ar-ico"><i class="far fa-clock"></i></div>
                <div class="ar-body">
                  <span class="ar-lbl">Horario</span>
                  <span class="ar-val" v-if="horaEntrada && horaSalida">{{ horaEntrada }} → {{ horaSalida }}</span>
                  <span class="ar-val-sub" v-if="duration">{{ duration }}</span>
                  <span class="ar-empty" v-else-if="!horaEntrada">Pendiente</span>
                </div>
                <i v-if="horaEntrada && horaSalida" class="fas fa-check ar-chk"></i>
              </div>
              <div class="ar-item" :class="{ filled: attachments.length > 0 }">
                <div class="ar-ico"><i class="fas fa-paperclip"></i></div>
                <div class="ar-body">
                  <span class="ar-lbl">Archivos adjuntos</span>
                  <span class="ar-val" v-if="attachments.length">{{ attachments.length }} archivo{{ attachments.length > 1 ? 's' : '' }}</span>
                  <span class="ar-empty" v-else>Sin adjuntos (opcional)</span>
                </div>
                <i v-if="attachments.length" class="fas fa-check ar-chk"></i>
              </div>
              <!-- Thumbnails -->
              <div v-if="imagePreviews.length" class="ar-thumbs">
                <div v-for="img in imagePreviews.slice(0,4)" :key="img.id" class="ar-thumb">
                  <img :src="img.url" :alt="img.name" />
                </div>
                <div v-if="imagePreviews.length > 4" class="ar-thumb ar-thumb-more">+{{ imagePreviews.length - 4 }}</div>
              </div>
              <!-- Chips non-image -->
              <div v-if="nonImageFiles.length" class="ar-file-chips">
                <div v-for="f in nonImageFiles.slice(0,3)" :key="f.id" class="ar-chip" :style="{ borderColor: f.iconColor+'40', color: f.iconColor }">
                  <i :class="f.icon" style="font-size:11px"></i>
                  <span>{{ f.shortName }}</span>
                </div>
                <div v-if="nonImageFiles.length > 3" class="ar-chip ar-chip-more">+{{ nonImageFiles.length - 3 }} más</div>
              </div>
              <div class="ar-item" :class="{ filled: hasFirma && consentAccepted }">
                <div class="ar-ico"><i class="fas fa-signature"></i></div>
                <div class="ar-body">
                  <span class="ar-lbl">Firma</span>
                  <span class="ar-val" v-if="hasFirma && consentAccepted">Firmado y aceptado</span>
                  <span class="ar-val ar-val--warn" v-else-if="hasFirma">Falta aceptar</span>
                  <span class="ar-empty" v-else>Pendiente</span>
                </div>
                <i v-if="hasFirma && consentAccepted" class="fas fa-check ar-chk"></i>
              </div>
            </div>

            <div v-if="canSubmit" class="ar-submit-area">
              <div class="ar-divider"></div>
              <div class="ar-ready-pill"><i class="fas fa-circle-check"></i> Acta lista para guardar</div>
              <button class="ar-submit-btn" :disabled="isSubmitting" @click="submitForm">
                <template v-if="isSubmitting"><i class="fas fa-spinner fa-spin"></i> {{ submitStatus }}</template>
                <template v-else><i class="fas fa-file-zipper"></i> Guardar y adjuntar ZIP</template>
              </button>
            </div>
          </div>
        </div>

      </div>
    </template>
  </section>
</template>

<script>
export default {
  name: 'ActaInstalacionPage',
  props: ['basicData'],

  data() {
    return {
      // Oportunidad vinculada (cargada desde ?id=)
      opportunityId: null,
      opportunityData: null,
      isLoadingOpportunity: false,

      currentStep: 1,
      isSubmitting: false,
      isSuccess: false,
      submitStatus: 'Generando ZIP...',

      cableMeters: '',
      tuboMeters: '',
      horaEntrada: '',
      horaSalida: '',
      attachments: [],   // { id, name, shortName, size, type, ext, isImage, icon, iconColor, iconBg, url, rawFile }
      isDragging: false,
      hasFirma: false,
      consentAccepted: false,
      sigDrawing: false,
      sigLastX: 0,
      sigLastY: 0,

      meterPresets: [
        { val: 5,  label: '5m',  desc: 'Corto'  },
        { val: 10, label: '10m', desc: 'Normal'  },
        { val: 15, label: '15m', desc: 'Medio'   },
        { val: 20, label: '20m', desc: 'Largo'   },
        { val: 30, label: '30m', desc: 'Extra'   },
        { val: 50, label: '50m', desc: 'Máximo'  },
      ],
    };
  },

  computed: {
    stepLabels() { return ['Medidas', 'Horario', 'Adjuntos', 'Firma']; },
    barFill()     { return ((this.currentStep - 1) / (this.stepLabels.length - 1)) * 100; },
    effectiveCableM() { return Math.min(Math.max(0, Number(this.cableMeters) || 0), 300); },
    effectiveTuboM()  { return Math.min(Math.max(0, Number(this.tuboMeters)  || 0), 300); },
    canProceedStep1() { return this.effectiveCableM > 0 && this.effectiveTuboM > 0; },
    canProceedStep2() { return !!this.horaEntrada && !!this.horaSalida; },

    duration() {
      if (!this.horaEntrada || !this.horaSalida) return null;
      const [eh, em] = this.horaEntrada.split(':').map(Number);
      const [sh, sm] = this.horaSalida.split(':').map(Number);
      let mins = sh * 60 + sm - (eh * 60 + em);
      if (mins < 0) mins += 1440;
      const h = Math.floor(mins / 60), m = mins % 60;
      if (h > 0 && m > 0) return `${h}h ${m}min`;
      if (h > 0) return `${h} hora${h > 1 ? 's' : ''}`;
      return `${m} minutos`;
    },

    imagePreviews()  { return this.attachments.filter(f => f.isImage && f.url); },
    nonImageFiles()  { return this.attachments.filter(f => !f.isImage); },

    canSubmit() {
      return this.effectiveCableM > 0
          && this.effectiveTuboM > 0
          && !!this.horaEntrada
          && !!this.horaSalida
          && this.hasFirma
          && this.consentAccepted;
    },
  },

  watch: {
    currentStep(newVal, oldVal) {
      if (oldVal === 4) this._saveSnapshot();
      if (newVal === 4) this.$nextTick(() => this._initCanvas());
    },
  },

  created() {
    // Detectar ?id= en la URL para cargar datos de la oportunidad
    this.opportunityId = new URLSearchParams(window.location.search).get('id');
    if (this.opportunityId) this.fetchOpportunity();
  },

  mounted() {
    this._onResize = this._debounce(() => {
      if (this.currentStep === 4 && this.$refs.sigCanvas) {
        const snap = this.hasFirma ? this.$refs.sigCanvas.toDataURL() : null;
        this._setupCanvas();
        if (snap) this._paintSnapshot(snap);
      }
    }, 150);
    window.addEventListener('resize', this._onResize);
  },

  beforeDestroy() {
    window.removeEventListener('resize', this._onResize);
  },

  methods: {

    /* ── OPORTUNIDAD ────────────────────────────────── */
    async fetchOpportunity() {
      this.isLoadingOpportunity = true;
      try {
        const { data } = await axios.get(`/api/public/opportunities/${this.opportunityId}`);
        this.opportunityData = data.opportunity;
      } catch (e) {
        console.error('[ActaInstalacion] Error cargando oportunidad:', e);
      } finally {
        this.isLoadingOpportunity = false;
      }
    },

    /* ── NAVEGACIÓN ─────────────────────────────────── */
    goToStep(step) {
      if (this.currentStep === 4) this._saveSnapshot();
      this.currentStep = step;
      this.$nextTick(() => {
        if (step === 4) this._initCanvas();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    },

    /* ── ARCHIVOS ───────────────────────────────────── */
    onFileChange(e) { this._processFiles(Array.from(e.target.files)); e.target.value = ''; },
    onDrop(e)       { this.isDragging = false; this._processFiles(Array.from(e.dataTransfer.files)); },

    _processFiles(files) {
      files.forEach(file => {
        const info = this._fileInfo(file);
        if (info.isImage) {
          // Leer como DataURL para la preview
          const reader = new FileReader();
          reader.onload = ev => {
            this.attachments.push({ ...info, url: ev.target.result, rawFile: file });
          };
          reader.readAsDataURL(file);
        } else {
          // Sin preview — solo guardamos referencia al File original
          this.attachments.push({ ...info, url: null, rawFile: file });
        }
      });
    },

    _fileInfo(file) {
      const ext = (file.name.split('.').pop() || '').toLowerCase();
      const t = file.type || '';
      const isImage = t.startsWith('image/');
      let icon = 'fas fa-file', iconColor = '#64748b', iconBg = 'rgba(100,116,139,0.10)';
      if      (isImage)                                                                   { icon = 'fas fa-image';       iconColor = '#3cc97b'; iconBg = 'rgba(60,201,123,0.10)'; }
      else if (t === 'application/pdf' || ext === 'pdf')                                  { icon = 'fas fa-file-pdf';    iconColor = '#dc2626'; iconBg = 'rgba(220,38,38,0.08)'; }
      else if (t.startsWith('video/') || ['mp4','mov','avi','mkv','webm'].includes(ext))  { icon = 'fas fa-film';        iconColor = '#7c3aed'; iconBg = 'rgba(124,58,237,0.08)'; }
      else if (t.startsWith('audio/') || ['mp3','wav','aac','ogg','flac'].includes(ext))  { icon = 'fas fa-music';       iconColor = '#ea580c'; iconBg = 'rgba(234,88,12,0.08)'; }
      else if (['xls','xlsx','csv','ods'].includes(ext) || t.includes('spreadsheet'))     { icon = 'fas fa-table';       iconColor = '#16a34a'; iconBg = 'rgba(22,163,74,0.08)'; }
      else if (['zip','rar','7z','tar','gz'].includes(ext))                               { icon = 'fas fa-file-zipper'; iconColor = '#ca8a04'; iconBg = 'rgba(202,138,4,0.08)'; }
      else if (['js','ts','py','html','css','json','xml'].includes(ext) || t.startsWith('text/')) { icon = 'fas fa-code'; iconColor = '#0369a1'; iconBg = 'rgba(3,105,161,0.08)'; }
      else if (['doc','docx','odt','rtf','txt'].includes(ext) || t.includes('document')) { icon = 'fas fa-file-lines';  iconColor = '#1b2f6e'; iconBg = 'rgba(27,47,110,0.08)'; }
      return {
        id: Date.now() + Math.random(),
        name: file.name,
        shortName: file.name.length > 18 ? file.name.slice(0, 15) + '…' : file.name,
        size: this._fmtSize(file.size),
        type: t, ext: ext.toUpperCase().slice(0, 4),
        isImage, icon, iconColor, iconBg, url: null, rawFile: null,
      };
    },

    removeFile(id) {
      this.attachments = this.attachments.filter(f => f.id !== id);
    },

    _fmtSize(b) {
      if (b < 1024) return b + ' B';
      if (b < 1048576) return Math.round(b / 1024) + ' KB';
      return (b / 1048576).toFixed(1) + ' MB';
    },

    /* ── CANVAS DE FIRMA ────────────────────────────── */
    _initCanvas() {
      const canvas = this.$refs.sigCanvas, wrap = this.$refs.sigWrap;
      if (!canvas || !wrap) return;
      this._setupCanvas();
      if (this._sigSnap && this.hasFirma) this._paintSnapshot(this._sigSnap);
    },

    _setupCanvas() {
      const canvas = this.$refs.sigCanvas, wrap = this.$refs.sigWrap;
      if (!canvas) return;
      canvas.width  = wrap ? wrap.clientWidth : canvas.parentElement.clientWidth;
      canvas.height = 220;
      this._ctx = canvas.getContext('2d');
      this._ctx.strokeStyle = '#111f4d';
      this._ctx.lineWidth   = 2.5;
      this._ctx.lineCap     = 'round';
      this._ctx.lineJoin    = 'round';
    },

    _saveSnapshot() {
      const canvas = this.$refs.sigCanvas;
      if (canvas && this.hasFirma) this._sigSnap = canvas.toDataURL();
    },

    _paintSnapshot(dataUrl) {
      const canvas = this.$refs.sigCanvas;
      if (!canvas || !this._ctx) return;
      const img = new Image();
      img.onload = () => this._ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      img.src = dataUrl;
    },

    _sigPos(e) {
      const canvas = this.$refs.sigCanvas;
      if (!canvas) return { x: 0, y: 0 };
      const rect = canvas.getBoundingClientRect();
      const sx = canvas.width / rect.width, sy = canvas.height / rect.height;
      const src = e.touches ? e.touches[0] : e;
      return { x: (src.clientX - rect.left) * sx, y: (src.clientY - rect.top) * sy };
    },

    sigStart(e) {
      this.sigDrawing = true;
      const p = this._sigPos(e);
      this.sigLastX = p.x; this.sigLastY = p.y;
      if (this._ctx) {
        this._ctx.beginPath();
        this._ctx.arc(p.x, p.y, 1.2, 0, Math.PI * 2);
        this._ctx.fillStyle = '#111f4d';
        this._ctx.fill();
      }
      this.hasFirma = true;
    },

    sigMove(e) {
      if (!this.sigDrawing || !this._ctx) return;
      const p = this._sigPos(e);
      this._ctx.beginPath();
      this._ctx.moveTo(this.sigLastX, this.sigLastY);
      this._ctx.lineTo(p.x, p.y);
      this._ctx.stroke();
      this.sigLastX = p.x; this.sigLastY = p.y;
    },

    sigEnd() { this.sigDrawing = false; },

    clearSig() {
      const canvas = this.$refs.sigCanvas;
      if (canvas && this._ctx) this._ctx.clearRect(0, 0, canvas.width, canvas.height);
      this.hasFirma = false; this._sigSnap = null;
    },

    /* ── SUBMIT → FormData + ZIP en backend ─────────── */
    async submitForm() {
      if (!this.canSubmit || this.isSubmitting) return;
      this.isSubmitting = true;
      this.submitStatus = 'Preparando datos...';
      this._saveSnapshot();

      try {
        const fd = new FormData();

        // JSON con metadatos y firma (base64, es pequeña)
        const meta = {
          opportunityId:   this.opportunityId,
          clientName:      this.opportunityData?.name  || '',
          clientPhone:     this.opportunityData?.phone || '',
          clientEmail:     this.opportunityData?.email || '',
          cableMeters:     this.effectiveCableM,
          tuboMeters:      this.effectiveTuboM,
          horaEntrada:     this.horaEntrada,
          horaSalida:      this.horaSalida,
          duration:        this.duration || '',
          signature:       this._sigSnap || null,   // base64 PNG (firma)
          consentAccepted: this.consentAccepted,
          savedAt:         new Date().toISOString(),
          attachmentMeta:  this.attachments.map(a => ({ name: a.name, type: a.type || '' })),
        };
        fd.append('json', JSON.stringify(meta));

        // Archivos adjuntos como archivos reales (no base64)
        this.submitStatus = 'Subiendo archivos...';
        this.attachments.forEach((a, i) => {
          if (a.rawFile) fd.append(`files[${i}]`, a.rawFile, a.name);
        });

        this.submitStatus = 'Generando PDF y ZIP...';
        await axios.post('/api/opportunities/saveActa', fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });

        this.isSuccess = true;
        window.scrollTo({ top: 0, behaviogitr: 'smooth' });

      } catch (err) {
        console.error('[ActaInstalacion] Error:', err);
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar el acta. Inténtalo de nuevo.' });
        }
      } finally {
        this.isSubmitting = false;
        this.submitStatus = 'Generando ZIP...';
      }
    },

    /* ── RESET ──────────────────────────────────────── */
    resetForm() {
      const savedId   = this.opportunityId;
      const savedData = this.opportunityData;
      Object.assign(this.$data, this.$options.data.call(this));
      this.opportunityId   = savedId;
      this.opportunityData = savedData;
    },

    /* ── UTILS ──────────────────────────────────────── */
    _debounce(fn, delay) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), delay); }; },
  },
};
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,700&display=swap");
*,*::before,*::after { box-sizing: border-box; }

.acta-page {
  min-height:100vh; overflow-x:hidden;
  background:
    radial-gradient(ellipse 55% 60% at -5% -5%,rgba(27,47,110,.65) 0%,transparent 60%),
    radial-gradient(ellipse 50% 55% at 105% 105%,rgba(60,201,123,.65) 0%,transparent 60%),
    radial-gradient(ellipse 30% 35% at 80% 3%,rgba(60,201,123,.3) 0%,transparent 55%),
    #e6eff0;
  font-family:'Plus Jakarta Sans',system-ui,sans-serif;
  display:flex; flex-direction:column; align-items:center; padding-bottom:64px;
}

/* NAV */
.a-nav { width:100%; max-width:1140px; display:flex; align-items:center; justify-content:space-between; padding:22px 24px 0; }
.a-logo { display:flex; align-items:center; gap:9px; text-decoration:none; }
.a-logo img { height:32px; }
.a-wordmark { font-size:20px; font-weight:800; color:#111f4d; letter-spacing:-.03em; }
.a-wordmark em { color:#3cc97b; font-style:normal; }
.a-back { display:inline-flex; align-items:center; gap:7px; padding:9px 18px; border-radius:50px; border:1.5px solid rgba(27,47,110,.14); background:rgba(255,255,255,.55); font-family:inherit; font-size:13px; font-weight:600; color:#1b2f6e; cursor:pointer; text-decoration:none; transition:all .2s; }
.a-back:hover { background:rgba(255,255,255,.88); border-color:#3cc97b; color:#28a866; }

/* LAYOUT */
.a-layout { width:100%; max-width:1140px; display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:start; padding:28px 24px 0; }
.a-form { display:flex; flex-direction:column; gap:14px; }
.a-page-title { padding:4px 0 0; }
.a-page-title h1 { font-size:30px; font-weight:800; color:#111f4d; letter-spacing:-.035em; margin-bottom:4px; }
.a-page-title p { font-size:14px; color:#8896a5; }

/* Opportunity card */
.a-opp-loading { display:flex; align-items:center; gap:10px; padding:12px 18px; border-radius:13px; background:rgba(27,47,110,.04); font-size:13px; color:#8896a5; }
.a-opp-card { display:flex; align-items:center; gap:13px; padding:14px 18px; border-radius:14px; background:rgba(60,201,123,.08); border:1px solid rgba(60,201,123,.22); }
.a-opp-icon { font-size:22px; color:#28a866; flex-shrink:0; }
.a-opp-info strong { font-size:14.5px; font-weight:700; color:#0b3522; display:block; }
.a-opp-info span { font-size:12.5px; color:#637087; }

/* PROGRESS BAR */
.a-bar { background:rgba(255,255,255,.75); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,.6); border-radius:20px; padding:18px 24px; box-shadow:0 2px 12px rgba(27,47,110,.07); position:relative; }
.a-bar-steps { display:flex; justify-content:space-between; position:relative; z-index:1; }
.a-bar-line { position:absolute; top:50%; left:56px; right:56px; height:2px; background:rgba(27,47,110,.1); transform:translateY(-50%); z-index:0; border-radius:2px; overflow:hidden; }
.a-bar-fill { height:100%; background:#3cc97b; transition:width .45s cubic-bezier(.4,0,.2,1); border-radius:2px; }
.a-bar-dot { display:flex; flex-direction:column; align-items:center; gap:6px; cursor:default; flex:1; }
.a-bar-dot.done { cursor:pointer; }
.a-dot-num { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; background:rgba(255,255,255,.8); color:#9aa5b4; border:2px solid rgba(27,47,110,.1); transition:all .28s; position:relative; z-index:1; box-shadow:0 2px 6px rgba(27,47,110,.06); }
.a-bar-dot.active .a-dot-num { background:#1b2f6e; border-color:#1b2f6e; color:#fff; box-shadow:0 4px 14px rgba(27,47,110,.3); transform:scale(1.08); }
.a-bar-dot.done  .a-dot-num { background:#3cc97b; border-color:#3cc97b; color:#fff; box-shadow:0 4px 14px rgba(60,201,123,.28); }
.a-dot-lbl { font-size:10.5px; font-weight:600; color:#9aa5b4; }
.a-bar-dot.active .a-dot-lbl { color:#1b2f6e; font-weight:700; }
.a-bar-dot.done  .a-dot-lbl { color:#3cc97b; }

/* STEP CARD */
.a-step { background:rgba(255,255,255,.82); backdrop-filter:blur(28px) saturate(180%); border:1px solid rgba(255,255,255,.65); border-radius:24px; box-shadow:0 8px 36px rgba(27,47,110,.09),inset 0 1px 0 rgba(255,255,255,.85); overflow:hidden; position:relative; }
.a-step::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,#1b2f6e,#3cc97b); }
.a-step-head { display:flex; align-items:flex-start; gap:18px; padding:32px 32px 20px; border-bottom:1px solid rgba(27,47,110,.06); }
.a-step-num { font-size:42px; font-weight:800; color:rgba(27,47,110,.08); letter-spacing:-.05em; line-height:1; font-style:italic; flex-shrink:0; margin-top:-4px; }
.a-step-head h2 { font-size:clamp(20px,3vw,28px); font-weight:800; color:#111f4d; letter-spacing:-.025em; line-height:1.15; margin:0 0 6px; }
.a-step-head p { font-size:14px; color:#8896a5; margin:0; line-height:1.5; }
.a-sec-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#8896a5; padding:20px 32px 10px; display:flex; align-items:center; gap:6px; }
.a-sec-label i { color:#3cc97b; }
.a-count-badge { width:20px; height:20px; border-radius:50%; background:#3cc97b; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; }
.a-foot { display:flex; justify-content:space-between; align-items:center; padding:20px 32px 24px; border-top:1px solid rgba(27,47,110,.06); margin-top:8px; }

/* BUTTONS */
.a-btn-next { display:inline-flex; align-items:center; gap:8px; padding:14px 30px; border-radius:50px; border:none; background:linear-gradient(135deg,#1b2f6e,#111f4d); color:#fff; font-family:inherit; font-size:14px; font-weight:700; cursor:pointer; box-shadow:0 6px 20px rgba(27,47,110,.28); transition:all .22s; }
.a-btn-next:disabled { background:rgba(190,195,205,.7); box-shadow:none; cursor:not-allowed; }
.a-btn-next:not(:disabled):hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(27,47,110,.38); }
.a-btn-back { display:inline-flex; align-items:center; gap:7px; padding:12px 20px; border-radius:50px; border:1.5px solid rgba(27,47,110,.14); background:transparent; font-family:inherit; font-size:13px; font-weight:600; color:#637087; cursor:pointer; transition:all .2s; }
.a-btn-back:hover { border-color:#3cc97b; color:#28a866; background:rgba(60,201,123,.04); }
.a-btn-submit { display:inline-flex; align-items:center; gap:8px; padding:14px 28px; border-radius:50px; border:none; background:linear-gradient(135deg,#3cc97b,#28a866); color:#0b3522; font-family:inherit; font-size:14px; font-weight:800; cursor:pointer; box-shadow:0 6px 20px rgba(60,201,123,.3); transition:all .22s; }
.a-btn-submit:disabled { background:rgba(190,195,205,.7); color:#fff; box-shadow:none; cursor:not-allowed; }
.a-btn-submit:not(:disabled):hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(60,201,123,.42); }

/* METERS */
.a-meter-block { padding:0 32px 22px; }
.a-field-label { font-size:12.5px; font-weight:600; color:#637087; display:block; margin-bottom:9px; }
.a-presets { display:grid; grid-template-columns:repeat(6,1fr); gap:8px; margin-bottom:12px; }
.a-preset { display:flex; flex-direction:column; align-items:center; gap:2px; padding:11px 4px; border-radius:10px; border:1.5px solid rgba(27,47,110,.1); background:rgba(255,255,255,.55); cursor:pointer; font-family:inherit; transition:all .2s; }
.a-preset:hover { border-color:rgba(60,201,123,.4); background:rgba(255,255,255,.88); transform:translateY(-1px); }
.a-preset.active { border-color:#3cc97b; background:rgba(255,255,255,.95); box-shadow:0 0 0 2px rgba(60,201,123,.15); }
.apv { font-size:14px; font-weight:800; color:#111f4d; letter-spacing:-.02em; }
.a-preset.active .apv { color:#0b3522; }
.apd { font-size:9px; font-weight:500; color:#8896a5; }
.a-preset.active .apd { color:#28a866; }
.a-custom-row { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.a-custom-row label { font-size:12px; color:#8896a5; font-weight:500; }
.a-custom-inp { display:flex; align-items:center; gap:8px; }
.a-custom-inp input { width:72px; height:40px; border:1.5px solid rgba(27,47,110,.12); border-radius:10px; background:rgba(255,255,255,.8); padding:0 10px; font-size:15px; font-weight:700; color:#111f4d; text-align:center; font-family:inherit; outline:none; transition:border-color .2s,box-shadow .2s; }
.a-custom-inp input:focus { border-color:#3cc97b; box-shadow:0 0 0 3px rgba(60,201,123,.1); }
.a-custom-inp span { font-size:13px; color:#8896a5; font-weight:500; }
.a-sep { height:1px; background:rgba(27,47,110,.06); margin:4px 32px 18px; }
.a-hint { font-size:11.5px; color:#8896a5; display:flex; align-items:flex-start; gap:5px; line-height:1.55; padding:0 32px; margin-bottom:4px; }
.a-hint i { color:#3cc97b; flex-shrink:0; margin-top:1px; }

/* HORARIO */
.a-time-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; padding:0 32px 20px; }
.a-time-field label { font-size:12.5px; font-weight:600; color:#637087; display:block; margin-bottom:7px; }
.a-time-iw { position:relative; }
.a-time-iw i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa5b4; font-size:15px; pointer-events:none; }
.a-time-iw input[type="time"] { width:100%; height:50px; border:1.5px solid rgba(27,47,110,.12); border-radius:13px; background:rgba(255,255,255,.8); padding:0 14px 0 42px; font-size:16px; font-weight:600; color:#111f4d; font-family:inherit; outline:none; cursor:pointer; transition:border-color .2s,box-shadow .2s; }
.a-time-iw input[type="time"]:focus { border-color:#3cc97b; box-shadow:0 0 0 3px rgba(60,201,123,.1); background:#fff; }
.a-dur-badge { display:flex; align-items:center; justify-content:center; gap:8px; margin:0 32px 22px; padding:13px 18px; border-radius:13px; background:rgba(60,201,123,.08); border:1px solid rgba(60,201,123,.2); font-size:13.5px; color:#0b3522; }
.a-dur-badge i { color:#3cc97b; }
.a-dur-badge strong { font-weight:700; }

/* ARCHIVOS */
.a-files-block { padding:0 32px 24px; }
.a-dropzone { border:2px dashed rgba(27,47,110,.16); border-radius:18px; padding:34px 24px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; cursor:pointer; transition:all .25s; background:rgba(255,255,255,.4); text-align:center; }
.a-dropzone:hover,.a-dropzone.dragging { border-color:#3cc97b; background:rgba(60,201,123,.05); }
.adz-icon { width:52px; height:52px; border-radius:14px; background:rgba(27,47,110,.07); color:#1b2f6e; display:flex; align-items:center; justify-content:center; font-size:22px; margin-bottom:2px; transition:all .25s; }
.a-dropzone.dragging .adz-icon { background:rgba(60,201,123,.15); color:#28a866; }
.a-dropzone h3 { font-size:14px; font-weight:700; color:#111f4d; margin:0; }
.a-dropzone p  { font-size:12px; color:#8896a5; line-height:1.55; margin:0; }
.a-dropzone em { color:#3cc97b; font-style:normal; font-weight:600; }
.adz-types { display:flex; flex-wrap:wrap; gap:6px; justify-content:center; margin-top:6px; }
.adz-types span { font-size:10px; font-weight:600; color:#8896a5; background:rgba(27,47,110,.06); border-radius:50px; padding:3px 9px; }
.a-files-toolbar { display:flex; align-items:center; gap:10px; margin-top:12px; }
.a-files-pill { display:flex; align-items:center; gap:6px; font-size:12.5px; font-weight:600; color:#0b3522; background:rgba(60,201,123,.1); border:1px solid rgba(60,201,123,.2); padding:5px 12px; border-radius:50px; }
.a-files-pill i { color:#3cc97b; }
.a-add-more-btn { margin-left:auto; display:inline-flex; align-items:center; gap:5px; padding:5px 13px; border-radius:50px; border:1.5px solid rgba(27,47,110,.12); background:transparent; font-family:inherit; font-size:12px; font-weight:600; color:#1b2f6e; cursor:pointer; transition:all .2s; }
.a-add-more-btn:hover { border-color:#3cc97b; color:#28a866; background:rgba(60,201,123,.04); }
.a-files-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:10px; margin-top:12px; }
.a-file-card { position:relative; border-radius:13px; overflow:hidden; border:1.5px solid rgba(27,47,110,.1); box-shadow:0 2px 8px rgba(27,47,110,.06); background:rgba(255,255,255,.7); transition:transform .15s,box-shadow .15s; }
.a-file-card:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(27,47,110,.1); }
.a-fc-thumb { aspect-ratio:1; overflow:hidden; }
.a-fc-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
.a-fc-icon { aspect-ratio:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:7px; font-size:30px; }
.a-fc-ext { font-size:9px; font-weight:800; letter-spacing:.05em; padding:2px 5px; border-radius:4px; background:rgba(0,0,0,.07); }
.a-fc-meta { padding:7px 8px 6px; border-top:1px solid rgba(27,47,110,.07); background:rgba(255,255,255,.9); }
.a-fc-name { display:block; font-size:10.5px; font-weight:600; color:#111f4d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:1px; }
.a-fc-size { font-size:9.5px; color:#8896a5; }
.a-fc-rm { position:absolute; top:5px; right:5px; width:24px; height:24px; border-radius:50%; background:rgba(220,38,38,.88); border:none; color:#fff; font-size:11px; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 5px rgba(0,0,0,.2); opacity:0; transition:opacity .15s,transform .1s; }
.a-file-card:hover .a-fc-rm { opacity:1; }
.a-fc-rm:hover { background:#dc2626; transform:scale(1.1); }

/* FIRMA */
.a-sig-section { padding:0 32px 10px; }
.a-consent-text { background:rgba(27,47,110,.04); border:1px solid rgba(27,47,110,.1); border-radius:13px; padding:15px 17px; font-size:12.5px; color:#637087; line-height:1.65; max-height:130px; overflow-y:auto; margin-bottom:18px; }
.a-consent-text p { margin-bottom:7px; }
.a-consent-text p:last-child { margin-bottom:0; }
.a-consent-text strong { color:#1b2f6e; font-weight:700; }
.a-sig-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:9px; }
.a-field-label { font-size:12.5px; font-weight:600; color:#637087; }
.a-sig-clear { display:inline-flex; align-items:center; gap:6px; padding:7px 13px; border-radius:8px; border:1.5px solid rgba(220,38,38,.2); background:rgba(220,38,38,.05); color:#dc2626; font-size:11.5px; font-weight:600; cursor:pointer; font-family:inherit; transition:all .2s; }
.a-sig-clear:hover { background:rgba(220,38,38,.1); border-color:rgba(220,38,38,.35); }
.a-sig-wrap { border:1.5px solid rgba(27,47,110,.15); border-radius:16px; background:rgba(255,255,255,.92); overflow:hidden; position:relative; margin-bottom:12px; box-shadow:inset 0 2px 8px rgba(27,47,110,.04); }
.a-sig-ph { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:9px; pointer-events:none; font-size:13px; color:#c0c9d3; font-weight:500; }
.a-sig-ph i { font-size:26px; }
.a-sig-canvas { display:block; width:100%; height:220px; cursor:crosshair; touch-action:none; }
.a-consent-row { display:flex; align-items:flex-start; gap:13px; padding:15px 17px; border-radius:13px; background:rgba(60,201,123,.05); border:1.5px solid rgba(60,201,123,.15); cursor:pointer; transition:background .2s; margin-top:14px; }
.a-consent-row:hover { background:rgba(60,201,123,.1); }
.a-check-box { width:20px; height:20px; border-radius:6px; flex-shrink:0; margin-top:1px; border:2px solid rgba(27,47,110,.2); background:rgba(255,255,255,.7); display:flex; align-items:center; justify-content:center; transition:all .2s; font-size:11px; color:#fff; }
.a-check-box.checked { background:#3cc97b; border-color:#3cc97b; box-shadow:0 2px 8px rgba(60,201,123,.35); }
.a-consent-row span { font-size:12.5px; font-weight:500; color:#1b2f6e; line-height:1.6; }
.a-consent-row strong { font-weight:700; }

/* Aviso ZIP */
.a-zip-notice { display:flex; align-items:center; gap:12px; margin-top:14px; padding:13px 16px; border-radius:12px; background:rgba(27,47,110,.05); border:1px solid rgba(27,47,110,.1); font-size:12.5px; }
.a-zip-notice i { font-size:20px; color:#ca8a04; flex-shrink:0; }
.a-zip-notice strong { display:block; color:#111f4d; font-weight:700; margin-bottom:2px; }
.a-zip-notice span { color:#637087; }

/* RECIBO */
.a-receipt { position:sticky; top:20px; }
.a-receipt-inner { background:rgba(255,255,255,.82); backdrop-filter:blur(28px) saturate(180%); border:1px solid rgba(255,255,255,.65); border-radius:24px; box-shadow:0 8px 36px rgba(27,47,110,.09),inset 0 1px 0 rgba(255,255,255,.85); overflow:hidden; }
.ar-head { display:flex; align-items:center; gap:12px; padding:20px 22px 16px; border-bottom:1px solid rgba(27,47,110,.07); }
.ar-icon { width:40px; height:40px; border-radius:12px; background:rgba(27,47,110,.07); color:#1b2f6e; display:flex; align-items:center; justify-content:center; font-size:17px; }
.ar-title { font-size:15px; font-weight:700; color:#111f4d; }
.ar-sub { font-size:11.5px; color:#8896a5; margin-top:2px; }
.ar-items { padding:14px 18px; display:flex; flex-direction:column; gap:7px; }
.ar-item { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:12px; background:rgba(27,47,110,.04); transition:background .25s; }
.ar-item.filled { background:rgba(255,255,255,.7); }
.ar-ico { width:32px; height:32px; border-radius:9px; flex-shrink:0; background:rgba(27,47,110,.08); color:#9aa5b4; display:flex; align-items:center; justify-content:center; font-size:13px; transition:all .25s; }
.ar-item.filled .ar-ico { background:rgba(27,47,110,.1); color:#1b2f6e; }
.ar-body { flex:1; min-width:0; }
.ar-lbl { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#9aa5b4; display:block; margin-bottom:2px; }
.ar-val { font-size:12.5px; font-weight:600; color:#111f4d; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ar-val--warn { color:#d97706; }
.ar-val-sub { font-size:11px; color:#3cc97b; display:block; font-weight:600; margin-top:1px; }
.ar-empty { font-size:12px; color:#c0c9d3; display:block; }
.ar-chk { font-size:13.5px; color:#3cc97b; flex-shrink:0; }
.ar-thumbs { display:flex; gap:6px; padding:0 12px 6px; }
.ar-thumb { width:38px; height:38px; border-radius:8px; overflow:hidden; flex-shrink:0; border:1.5px solid rgba(27,47,110,.1); }
.ar-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
.ar-thumb-more { background:rgba(27,47,110,.08); color:#637087; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; }
.ar-file-chips { display:flex; flex-direction:column; gap:4px; padding:0 12px 6px; }
.ar-chip { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:600; border:1.5px solid; border-radius:6px; padding:4px 8px; }
.ar-chip span { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ar-chip-more { color:#8896a5; border-color:rgba(27,47,110,.12); }
.ar-divider { height:1px; background:rgba(27,47,110,.07); margin:0 18px; }
.ar-submit-area { padding:14px 18px 18px; }
.ar-ready-pill { display:flex; align-items:center; gap:7px; font-size:12px; font-weight:600; color:#0b3522; background:rgba(60,201,123,.1); border-radius:10px; padding:9px 13px; margin-bottom:12px; }
.ar-ready-pill i { color:#3cc97b; font-size:14px; }
.ar-submit-btn { width:100%; min-height:48px; border:none; border-radius:14px; background:linear-gradient(135deg,#3cc97b,#28a866); color:#0b3522; font-family:inherit; font-size:13.5px; font-weight:800; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 6px 18px rgba(60,201,123,.28); transition:all .22s; }
.ar-submit-btn:disabled { background:rgba(180,180,180,.6); color:#fff; box-shadow:none; cursor:not-allowed; }
.ar-submit-btn:not(:disabled):hover { transform:translateY(-1px); box-shadow:0 10px 24px rgba(60,201,123,.4); }

/* ÉXITO */
.ok-wrap { width:100%; min-height:70vh; display:flex; align-items:center; justify-content:center; padding:40px 24px; }
.ok-card { max-width:500px; width:100%; background:rgba(255,255,255,.85); backdrop-filter:blur(28px); border:1px solid rgba(255,255,255,.65); border-radius:28px; box-shadow:0 20px 50px rgba(27,47,110,.12); padding:44px 36px; text-align:center; }
.ok-icon { width:68px; height:68px; border-radius:50%; background:rgba(60,201,123,.15); color:#28a866; display:flex; align-items:center; justify-content:center; font-size:34px; margin:0 auto 20px; }
.ok-card h1 { font-size:24px; font-weight:800; color:#111f4d; letter-spacing:-.025em; margin-bottom:10px; }
.ok-card p { font-size:14px; color:#637087; margin-bottom:22px; line-height:1.6; }
.ok-rows { border:1px solid rgba(27,47,110,.1); border-radius:14px; overflow:hidden; margin-bottom:22px; text-align:left; }
.ok-rows div { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid rgba(27,47,110,.07); font-size:13px; }
.ok-rows div:last-child { border-bottom:none; }
.ok-rows span { color:#637087; display:flex; align-items:center; gap:7px; }
.ok-rows span i { color:#3cc97b; width:14px; text-align:center; }
.ok-rows strong { color:#111f4d; font-weight:700; }
.ok-btn { width:100%; min-height:50px; border:none; border-radius:14px; background:linear-gradient(135deg,#1b2f6e,#111f4d); color:#fff; font-family:inherit; font-size:15px; font-weight:700; cursor:pointer; box-shadow:0 10px 26px rgba(27,47,110,.3); display:flex; align-items:center; justify-content:center; gap:8px; transition:all .22s; }
.ok-btn:hover { transform:translateY(-2px); box-shadow:0 14px 30px rgba(27,47,110,.4); }

/* RESPONSIVE */
@media(max-width:900px) { .a-layout { grid-template-columns:1fr; } .a-receipt { position:static; } }
@media(max-width:768px) {
  .a-nav { padding:14px 14px 0; } .a-logo img { height:24px; } .a-wordmark { font-size:15px; } .a-back { padding:8px 14px; font-size:11.5px; }
  .a-layout { padding:12px 12px 0; gap:12px; } .a-form { gap:12px; } .a-page-title h1 { font-size:24px; }
  .a-bar { padding:13px 16px; border-radius:14px; } .a-bar-line { left:34px; right:34px; }
  .a-dot-num { width:28px; height:28px; font-size:11px; } .a-dot-lbl { font-size:9.5px; text-align:center; }
  .a-step { border-radius:18px; } .a-step-head { padding:18px 16px 14px; gap:12px; align-items:center; }
  .a-step-num { font-size:30px; margin-top:0; } .a-step-head h2 { font-size:19px; } .a-step-head p { font-size:12.5px; }
  .a-foot { padding:14px 16px 18px; }
  .a-btn-next,.a-btn-submit { padding:13px 18px; font-size:13px; min-height:46px; }
  .a-btn-back { padding:12px 16px; min-height:46px; }
  .a-sec-label { padding:14px 16px 8px; font-size:10.5px; }
  .a-meter-block { padding:0 16px 18px; } .a-presets { grid-template-columns:repeat(3,1fr); }
  .a-sep { margin:4px 16px 16px; } .a-hint { padding:0 16px; }
  .a-time-grid { padding:0 16px 18px; grid-template-columns:1fr; } .a-dur-badge { margin:0 16px 18px; }
  .a-files-block { padding:0 16px 18px; } .a-sig-section { padding:0 16px 10px; }
  .a-files-grid { grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); }
  .a-time-iw input[type="time"] { font-size:16px; }
  .a-zip-notice { margin:14px 0 0; }
}
@media(max-width:400px) { .a-wordmark { font-size:14px; } .a-step-num { font-size:26px; } .a-step-head h2 { font-size:17.5px; } .apd { display:none; } }
</style>