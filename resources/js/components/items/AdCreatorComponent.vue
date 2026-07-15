<template>
  <section>
    <div class="form">
      <div class="d-flex justify-between align-center mb-20">
        <div>
          <p class="text" data-size="20" data-weight="700">
            <i class="fa-solid fa-rectangle-ad opacity-6"></i>
            Creador de anuncios
          </p>
          <p class="text opacity-6 mt-5" data-size="13">
            Crea un banner publicitario, descárgalo como imagen o colócalo manualmente sobre un PDF.
          </p>
        </div>

        <button
          class="custom-button desktop-item"
          data-size="medium"
          :disabled="downloading"
          @click="downloadAd"
        >
          <span v-if="!downloading">
            <i class="fa-solid fa-download"></i> Descargar PNG
          </span>
          <span v-else>
            <i class="fa-solid fa-spinner fa-spin"></i> Generando...
          </span>
        </button>
      </div>

      <div class="separator"></div>

      <div class="ad-creator-layout mt-20">
        <!-- Panel izquierdo -->
        <div class="ad-settings">
          <p class="text mb-15" data-weight="600">
            <i class="fa-solid fa-pen-to-square opacity-6"></i>
            Datos del anuncio
          </p>

          <div class="form-group">
            <label>Plantilla</label>
            <div class="input-group">
              <select v-model="form.template" @change="refreshAdPreviewIfPdfLoaded">
                <option value="horizontal">Banner horizontal</option>
                <option value="simple">Banner simple sin imagen</option>
              </select>
            </div>
          </div>

          <div class="form-group mt-10">
            <label>Título</label>
            <div class="input-group">
              <input
                type="text"
                v-model="form.title"
                maxlength="65"
                placeholder="Reduce tu factura energética"
                @input="markAdPreviewAsDirty"
              />
            </div>
            <p class="text opacity-6 mt-5" data-size="11">
              {{ form.title.length }}/65 caracteres
            </p>
          </div>

          <div class="form-group mt-10">
            <label>Descripción</label>
            <div class="input-group">
              <textarea
                v-model="form.description"
                maxlength="160"
                placeholder="Instalaciones fotovoltaicas para empresas con estudio gratuito."
                @input="markAdPreviewAsDirty"
              ></textarea>
            </div>
            <p class="text opacity-6 mt-5" data-size="11">
              {{ form.description.length }}/160 caracteres
            </p>
          </div>

          <div class="form-group mt-10">
            <label>Llamada a la acción</label>
            <div class="input-group">
              <input
                type="text"
                v-model="form.cta"
                maxlength="35"
                placeholder="Solicita información"
                @input="markAdPreviewAsDirty"
              />
            </div>
          </div>

          <div class="form-group mt-10">
            <label>Contacto / web</label>
            <div class="input-group">
              <input
                type="text"
                v-model="form.contact"
                maxlength="70"
                placeholder="cliente.com · 600 000 000"
                @input="markAdPreviewAsDirty"
              />
            </div>
          </div>

          <div class="form-group mt-10" v-if="form.template === 'horizontal'">
            <label>Imagen</label>
            <div class="input-group">
              <input
                ref="imageInput"
                type="file"
                accept="image/*"
                @change="handleImageChange"
              />
            </div>

            <button
              v-if="form.image"
              type="button"
              class="ad-link-button mt-8"
              @click="removeImage"
            >
              <i class="fa-solid fa-trash"></i> Quitar imagen
            </button>
          </div>

          <div class="separator mt-15 mb-15"></div>

          <p class="text mb-10" data-weight="600">
            <i class="fa-solid fa-palette opacity-6"></i>
            Colores
          </p>

          <div class="ad-color-grid">
            <div class="form-group">
              <label>Principal</label>
              <input
                class="color-input"
                type="color"
                v-model="form.primaryColor"
                @input="markAdPreviewAsDirty"
              />
            </div>

            <div class="form-group">
              <label>Fondo</label>
              <input
                class="color-input"
                type="color"
                v-model="form.backgroundColor"
                @input="markAdPreviewAsDirty"
              />
            </div>

            <div class="form-group">
              <label>Texto</label>
              <input
                class="color-input"
                type="color"
                v-model="form.textColor"
                @input="markAdPreviewAsDirty"
              />
            </div>
          </div>

          <button
            class="custom-button mobile-item mt-20"
            data-size="medium"
            style="width: 100%;"
            :disabled="downloading"
            @click="downloadAd"
          >
            <span v-if="!downloading">
              <i class="fa-solid fa-download"></i> Descargar PNG
            </span>
            <span v-else>
              <i class="fa-solid fa-spinner fa-spin"></i> Generando...
            </span>
          </button>

          <div class="separator mt-20 mb-15"></div>

          <p class="text mb-15" data-weight="600">
            <i class="fa-solid fa-file-pdf opacity-6"></i>
            Colocar en PDF
          </p>

          <div class="form-group">
            <label>PDF donde insertar el anuncio</label>
            <div class="input-group">
              <input
                ref="pdfInput"
                type="file"
                accept="application/pdf"
                @change="handlePdfChange"
              />
            </div>

            <p v-if="pdfFile" class="text opacity-6 mt-5" data-size="11">
              PDF seleccionado: {{ pdfFile.name }}
            </p>
          </div>

          <div class="form-group mt-10">
            <label>Página</label>
            <div class="input-group">
              <input
                type="number"
                min="1"
                v-model.number="pdfOptions.page"
                @change="onPageChange"
              />
            </div>
          </div>

          <div class="separator mt-15 mb-15"></div>

          <p class="text mb-10" data-weight="600">
            <i class="fa-solid fa-up-down-left-right opacity-6"></i>
            Posición y tamaño
          </p>

          <div class="ad-pdf-controls">
            <div class="form-group">
              <label>X</label>
              <div class="input-group">
                <input
                  type="number"
                  min="0"
                  step="1"
                  v-model.number="overlay.x"
                  @input="clampOverlayIntoPage"
                />
              </div>
            </div>

            <div class="form-group">
              <label>Y</label>
              <div class="input-group">
                <input
                  type="number"
                  min="0"
                  step="1"
                  v-model.number="overlay.y"
                  @input="clampOverlayIntoPage"
                />
              </div>
            </div>
          </div>

          <div class="ad-pdf-controls mt-10">
            <div class="form-group">
              <label>Ancho</label>
              <div class="input-group">
                <input
                  type="number"
                  min="60"
                  step="1"
                  v-model.number="overlay.width"
                  @input="onManualWidthChange"
                />
              </div>
            </div>

            <div class="form-group">
              <label>Alto</label>
              <div class="input-group">
                <input
                  type="number"
                  min="20"
                  step="1"
                  v-model.number="overlay.height"
                  @input="onManualHeightChange"
                />
              </div>
            </div>
          </div>

          <div class="form-group mt-10">
            <label>Escala del anuncio: {{ overlay.scale }}%</label>
            <input
              class="ad-range"
              type="range"
              min="20"
              max="140"
              step="1"
              v-model.number="overlay.scale"
              @input="onScaleChange"
            />
          </div>

          <div class="form-group mt-10">
            <label class="d-flex align-center" data-gap="8">
              <input
                type="checkbox"
                v-model="overlay.lockAspect"
                style="width: auto;"
              />
              Mantener proporción del banner
            </label>
          </div>

          <div class="d-flex mt-12" data-gap="8">
            <button
              type="button"
              class="custom-button"
              data-size="small"
              data-variant="outline"
              style="width: 100%;"
              :disabled="!pdfPreviewReady"
              @click="centerOverlayBottom"
            >
              Abajo
            </button>

            <button
              type="button"
              class="custom-button"
              data-size="small"
              data-variant="outline"
              style="width: 100%;"
              :disabled="!pdfPreviewReady"
              @click="centerOverlayMiddle"
            >
              Centro
            </button>

            <button
              type="button"
              class="custom-button"
              data-size="small"
              data-variant="outline"
              style="width: 100%;"
              :disabled="!pdfPreviewReady"
              @click="centerOverlayTop"
            >
              Arriba
            </button>
          </div>

          <button
            class="custom-button mt-15"
            data-size="medium"
            data-variant="outline"
            style="width: 100%;"
            :disabled="attachingPdf || !pdfFile"
            @click="downloadPdfWithManualAd"
          >
            <span v-if="!attachingPdf">
              <i class="fa-solid fa-file-pdf"></i> Descargar PDF con anuncio
            </span>
            <span v-else>
              <i class="fa-solid fa-spinner fa-spin"></i> Generando PDF...
            </span>
          </button>

          <button
            v-if="pdfFile"
            type="button"
            class="ad-link-button mt-10"
            @click="removePdf"
          >
            <i class="fa-solid fa-trash"></i> Quitar PDF
          </button>
        </div>

        <!-- Panel derecho -->
        <div class="ad-preview-panel">
          <div class="d-flex justify-between align-center mb-15">
            <div>
              <p class="text" data-weight="600">
                <i class="fa-solid fa-eye opacity-6"></i>
                Vista previa del anuncio
              </p>
              <p class="text opacity-6 mt-5" data-size="12">
                Medida base: 720 × 150 px
              </p>
            </div>

            <button
              type="button"
              class="custom-button"
              data-size="small"
              data-variant="outline"
              @click="resetForm"
            >
              <i class="fa-solid fa-rotate-left"></i> Restaurar
            </button>
          </div>

          <div class="ad-preview-wrapper">
            <div
              v-if="form.template === 'horizontal'"
              ref="adRef"
              class="report-ad-banner"
              :style="bannerStyle"
            >
              <div class="report-ad-image">
                <img
                  v-if="form.image"
                  :src="form.image"
                  alt="Imagen del anuncio"
                />

                <div v-else class="report-ad-image-placeholder">
                  <i class="fa-regular fa-image"></i>
                  <span>Imagen</span>
                </div>
              </div>

              <div class="report-ad-content">
                <h2 :style="{ color: form.primaryColor }">
                  {{ form.title || 'Título del anuncio' }}
                </h2>

                <p :style="{ color: form.textColor }">
                  {{ form.description || 'Descripción breve del anuncio.' }}
                </p>

                <div class="report-ad-footer">
                  <span
                    class="report-ad-cta"
                    :style="{ backgroundColor: form.primaryColor }"
                  >
                    {{ form.cta || 'Más información' }}
                  </span>

                  <span
                    class="report-ad-contact"
                    :style="{ color: form.textColor }"
                  >
                    {{ form.contact || 'cliente.com' }}
                  </span>
                </div>
              </div>
            </div>

            <div
              v-else
              ref="adRef"
              class="report-ad-simple"
              :style="bannerStyle"
            >
              <div class="report-ad-simple-content">
                <span
                  class="report-ad-simple-badge"
                  :style="{ backgroundColor: form.primaryColor }"
                >
                  {{ form.cta || 'Más información' }}
                </span>

                <h2 :style="{ color: form.primaryColor }">
                  {{ form.title || 'Título del anuncio' }}
                </h2>

                <p :style="{ color: form.textColor }">
                  {{ form.description || 'Descripción breve del anuncio.' }}
                </p>

                <span
                  class="report-ad-simple-contact"
                  :style="{ color: form.textColor }"
                >
                  {{ form.contact || 'cliente.com' }}
                </span>
              </div>
            </div>
          </div>

          <div class="ad-info-box mt-15">
            <i class="fa-solid fa-circle-info"></i>
            El anuncio y el PDF se procesan en su navegador. No se guardan en base de datos ni se suben al servidor.
          </div>

          <div class="separator mt-20 mb-15"></div>

          <div class="d-flex justify-between align-center mb-15">
            <div>
              <p class="text" data-weight="600">
                <i class="fa-solid fa-up-down-left-right opacity-6"></i>
                Colocación manual en PDF
              </p>
              <p class="text opacity-6 mt-5" data-size="12">
                Arrastre el banner para moverlo y tire de la esquina inferior derecha para cambiar su tamaño.
              </p>
            </div>

            <button
              v-if="pdfFile"
              type="button"
              class="custom-button"
              data-size="small"
              data-variant="outline"
              :disabled="renderingPdfPreview"
              @click="refreshPdfPreviewSize"
            >
              <span v-if="!renderingPdfPreview">
                <i class="fa-solid fa-rotate"></i> Actualizar
              </span>
              <span v-else>
                <i class="fa-solid fa-spinner fa-spin"></i>
              </span>
            </button>
          </div>

          <div v-if="!pdfFile" class="pdf-empty-state">
            <i class="fa-regular fa-file-pdf"></i>
            <p class="text mt-8" data-weight="600">Todavía no hay PDF seleccionado</p>
            <p class="text opacity-6 mt-5" data-size="12">
              Seleccione un PDF desde el panel izquierdo para poder colocar el anuncio.
            </p>
          </div>

          <div v-else class="pdf-preview-box">
            <div
              ref="pdfPreviewWrapper"
              class="pdf-preview-wrapper"
              :class="{ 'is-ready': pdfPreviewReady }"
            >
              <div ref="pdfPageStage" class="pdf-page-stage">
                <VuePdfEmbed
                  class="pdf-render-host"
                  :source="pdfObjectUrl"
                  :page="pdfOptions.page"
                  :width="pdfPreviewWidth"
                  @rendered="onPdfRendered"
                  @loading-failed="onPdfLoadError"
                  @rendering-failed="onPdfLoadError"
                />

                <div
                  v-if="pdfPreviewReady && adPreviewDataUrl"
                  class="pdf-ad-overlay"
                  :style="overlayStyle"
                  @pointerdown.prevent="startDrag"
                >
                  <img :src="adPreviewDataUrl" alt="Anuncio" />

                  <span class="pdf-ad-overlay-move">
                    <i class="fa-solid fa-up-down-left-right"></i>
                  </span>

                  <span
                    class="pdf-ad-overlay-resize"
                    @pointerdown.stop.prevent="startResize"
                  >
                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                  </span>
                </div>
              </div>

              <div v-if="renderingPdfPreview" class="pdf-loading-layer">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Cargando PDF...</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import { toPng } from 'html-to-image';
import { PDFDocument } from 'pdf-lib';
import VuePdfEmbed from 'vue-pdf-embed';

export default {
  name: 'AdCreatorComponent',

  components: {
    VuePdfEmbed,
  },

  data() {
    return {
      downloading: false,
      attachingPdf: false,
      renderingPdfPreview: false,

      pdfFile: null,
      pdfObjectUrl: null,
      pdfPreviewReady: false,
      pdfPreviewWidth: 760,

      adPreviewDataUrl: null,
      adPreviewDirty: false,

      pdfPageSize: {
        width: 0,
        height: 0,
      },

      pdfOptions: {
        page: 1,
      },

      overlay: {
        x: 0,
        y: 0,

        width: 480,
        height: 100,
        scale: 82,

        minWidth: 80,
        minHeight: 24,

        lockAspect: true,
        aspectRatio: 720 / 150,

        dragging: false,
        resizing: false,

        dragOffsetX: 0,
        dragOffsetY: 0,

        resizeStartX: 0,
        resizeStartY: 0,
        resizeStartWidth: 0,
        resizeStartHeight: 0,
      },

      form: {
        template: 'horizontal',
        title: 'Reduce tu factura energética',
        description: 'Instalaciones fotovoltaicas para empresas con estudio gratuito.',
        cta: 'Solicita información',
        contact: 'cliente.com · 600 000 000',
        image: null,
        backgroundColor: '#FFF4F5',
        primaryColor: '#B00020',
        textColor: '#222222',
      },
    };
  },

  computed: {
    bannerStyle() {
      return {
        backgroundColor: this.form.backgroundColor,
        borderColor: this.hexToRgba(this.form.primaryColor, 0.22),
      };
    },

    overlayDimensions() {
      return {
        width: Number(this.overlay.width) || 0,
        height: Number(this.overlay.height) || 0,
      };
    },

    overlayStyle() {
      return {
        left: `${this.overlay.x}px`,
        top: `${this.overlay.y}px`,
        width: `${this.overlayDimensions.width}px`,
        height: `${this.overlayDimensions.height}px`,
      };
    },
  },

  methods: {
    handleImageChange(event) {
      const file = event.target.files?.[0];

      if (!file) return;

      if (!file.type.startsWith('image/')) {
        Swal.fire({
          icon: 'warning',
          title: 'Imagen no válida',
          text: 'Por favor selecciona un archivo de imagen válido.',
        });
        return;
      }

      const maxSizeMb = 5;
      const maxSizeBytes = maxSizeMb * 1024 * 1024;

      if (file.size > maxSizeBytes) {
        Swal.fire({
          icon: 'warning',
          title: 'Imagen demasiado pesada',
          text: `La imagen no puede superar los ${maxSizeMb} MB.`,
        });
        return;
      }

      const reader = new FileReader();

      reader.onload = async () => {
        this.form.image = reader.result;
        await this.refreshAdPreviewIfPdfLoaded();
      };

      reader.onerror = () => {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se pudo cargar la imagen seleccionada.',
        });
      };

      reader.readAsDataURL(file);
    },

    async removeImage() {
      this.form.image = null;

      if (this.$refs.imageInput) {
        this.$refs.imageInput.value = '';
      }

      await this.refreshAdPreviewIfPdfLoaded();
    },

    async handlePdfChange(event) {
      const file = event.target.files?.[0];

      if (!file) return;

      if (file.type !== 'application/pdf') {
        Swal.fire({
          icon: 'warning',
          title: 'PDF no válido',
          text: 'Por favor selecciona un archivo PDF.',
        });
        return;
      }

      const maxSizeMb = 25;
      const maxSizeBytes = maxSizeMb * 1024 * 1024;

      if (file.size > maxSizeBytes) {
        Swal.fire({
          icon: 'warning',
          title: 'PDF demasiado pesado',
          text: `El PDF no puede superar los ${maxSizeMb} MB.`,
        });
        return;
      }

      this.removePdfObjectUrl();

      this.pdfFile = file;
      this.pdfObjectUrl = URL.createObjectURL(file);
      this.pdfPreviewReady = false;
      this.renderingPdfPreview = true;
      this.pdfOptions.page = 1;

      await this.prepareAdPreviewImage();
    },

    removePdf() {
      this.removePdfObjectUrl();

      this.pdfFile = null;
      this.pdfPreviewReady = false;
      this.renderingPdfPreview = false;
      this.adPreviewDataUrl = null;

      this.pdfPageSize = {
        width: 0,
        height: 0,
      };

      if (this.$refs.pdfInput) {
        this.$refs.pdfInput.value = '';
      }
    },

    removePdfObjectUrl() {
      if (this.pdfObjectUrl) {
        URL.revokeObjectURL(this.pdfObjectUrl);
      }

      this.pdfObjectUrl = null;
    },

    async onPageChange() {
      this.pdfPreviewReady = false;
      this.renderingPdfPreview = true;

      await this.$nextTick();
    },

    async onPdfRendered() {
      this.renderingPdfPreview = false;

      await this.$nextTick();

      this.refreshPdfPreviewSize();

      if (!this.overlay.width || !this.overlay.height) {
        this.setDefaultOverlaySize();
      } else {
        this.clampOverlayIntoPage();
      }

      this.pdfPreviewReady = true;
    },

    async refreshPdfPreviewSize() {
      await this.$nextTick();

      const pageStage = this.$refs.pdfPageStage;

      if (!pageStage) return;

      const rect = pageStage.getBoundingClientRect();

      this.pdfPageSize = {
        width: Math.round(rect.width),
        height: Math.round(rect.height),
      };

      this.clampOverlayIntoPage();
    },

    onPdfLoadError(error) {
      console.error('[AdCreator] Error cargando PDF:', error);

      this.renderingPdfPreview = false;
      this.pdfPreviewReady = false;

      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No se pudo previsualizar el PDF.',
      });
    },

    markAdPreviewAsDirty() {
      this.adPreviewDirty = true;
    },

    async refreshAdPreviewIfPdfLoaded() {
      this.markAdPreviewAsDirty();

      if (!this.pdfFile || !this.pdfPreviewReady) return;

      await this.prepareAdPreviewImage();
    },

    async prepareAdPreviewImage() {
      await this.$nextTick();

      if (!this.$refs.adRef) return;

      this.adPreviewDataUrl = await toPng(this.$refs.adRef, {
        quality: 1,
        pixelRatio: 2,
        backgroundColor: this.form.backgroundColor,
        cacheBust: false,
      });

      this.adPreviewDirty = false;
    },

    startDrag(event) {
      if (!this.pdfPreviewReady) return;

      this.overlay.dragging = true;
      this.overlay.resizing = false;

      const wrapperRect = this.$refs.pdfPageStage.getBoundingClientRect();

      this.overlay.dragOffsetX = event.clientX - wrapperRect.left - this.overlay.x;
      this.overlay.dragOffsetY = event.clientY - wrapperRect.top - this.overlay.y;

      window.addEventListener('pointermove', this.onPointerMove);
      window.addEventListener('pointerup', this.stopPointerAction);
    },

    startResize(event) {
      if (!this.pdfPreviewReady) return;

      this.overlay.resizing = true;
      this.overlay.dragging = false;

      this.overlay.resizeStartX = event.clientX;
      this.overlay.resizeStartY = event.clientY;
      this.overlay.resizeStartWidth = this.overlay.width;
      this.overlay.resizeStartHeight = this.overlay.height;

      window.addEventListener('pointermove', this.onPointerMove);
      window.addEventListener('pointerup', this.stopPointerAction);
    },

    onPointerMove(event) {
      if (this.overlay.resizing) {
        this.resizeOverlayToClientPosition(event.clientX, event.clientY);
        return;
      }

      if (this.overlay.dragging) {
        this.moveOverlayToClientPosition(event.clientX, event.clientY);
      }
    },

    stopPointerAction() {
      this.overlay.dragging = false;
      this.overlay.resizing = false;

      window.removeEventListener('pointermove', this.onPointerMove);
      window.removeEventListener('pointerup', this.stopPointerAction);
    },

    moveOverlayToClientPosition(clientX, clientY) {
      const wrapperRect = this.$refs.pdfPageStage.getBoundingClientRect();

      const adWidth = this.overlayDimensions.width;
      const adHeight = this.overlayDimensions.height;

      let nextX = clientX - wrapperRect.left - this.overlay.dragOffsetX;
      let nextY = clientY - wrapperRect.top - this.overlay.dragOffsetY;

      nextX = Math.max(0, Math.min(nextX, this.pdfPageSize.width - adWidth));
      nextY = Math.max(0, Math.min(nextY, this.pdfPageSize.height - adHeight));

      this.overlay.x = Math.round(nextX);
      this.overlay.y = Math.round(nextY);
    },

    resizeOverlayToClientPosition(clientX, clientY) {
      const deltaX = clientX - this.overlay.resizeStartX;
      const deltaY = clientY - this.overlay.resizeStartY;

      let nextWidth = this.overlay.resizeStartWidth + deltaX;
      let nextHeight = this.overlay.resizeStartHeight + deltaY;

      if (this.overlay.lockAspect) {
        nextHeight = nextWidth / this.overlay.aspectRatio;
      }

      nextWidth = Math.max(this.overlay.minWidth, nextWidth);
      nextHeight = Math.max(this.overlay.minHeight, nextHeight);

      const maxWidth = this.pdfPageSize.width - this.overlay.x;
      const maxHeight = this.pdfPageSize.height - this.overlay.y;

      nextWidth = Math.min(nextWidth, maxWidth);
      nextHeight = Math.min(nextHeight, maxHeight);

      if (this.overlay.lockAspect) {
        nextHeight = nextWidth / this.overlay.aspectRatio;

        if (nextHeight > maxHeight) {
          nextHeight = maxHeight;
          nextWidth = nextHeight * this.overlay.aspectRatio;
        }
      }

      this.overlay.width = Math.round(nextWidth);
      this.overlay.height = Math.round(nextHeight);

      this.updateOverlayScaleFromWidth();
      this.clampOverlayIntoPage();
    },

    onScaleChange() {
      if (!this.pdfPreviewReady) return;

      const baseWidth = this.pdfPageSize.width * 0.82;
      const nextWidth = baseWidth * ((Number(this.overlay.scale) || 82) / 82);
      const nextHeight = this.overlay.lockAspect
        ? nextWidth / this.overlay.aspectRatio
        : this.overlay.height;

      this.overlay.width = Math.round(nextWidth);
      this.overlay.height = Math.round(nextHeight);

      this.clampOverlayIntoPage();
    },

    onManualWidthChange() {
      this.overlay.width = Math.max(
        this.overlay.minWidth,
        Number(this.overlay.width) || this.overlay.minWidth
      );

      if (this.overlay.lockAspect) {
        this.overlay.height = Math.round(this.overlay.width / this.overlay.aspectRatio);
      }

      this.updateOverlayScaleFromWidth();
      this.clampOverlayIntoPage();
    },

    onManualHeightChange() {
      this.overlay.height = Math.max(
        this.overlay.minHeight,
        Number(this.overlay.height) || this.overlay.minHeight
      );

      if (this.overlay.lockAspect) {
        this.overlay.width = Math.round(this.overlay.height * this.overlay.aspectRatio);
      }

      this.updateOverlayScaleFromWidth();
      this.clampOverlayIntoPage();
    },

    updateOverlayScaleFromWidth() {
      if (!this.pdfPageSize.width) return;

      const baseWidth = this.pdfPageSize.width * 0.82;
      this.overlay.scale = Math.round((this.overlay.width / baseWidth) * 82);
    },

    clampOverlayIntoPage() {
      if (!this.pdfPageSize.width || !this.pdfPageSize.height) return;

      this.overlay.width = Math.max(
        this.overlay.minWidth,
        Number(this.overlay.width) || this.overlay.minWidth
      );

      this.overlay.height = Math.max(
        this.overlay.minHeight,
        Number(this.overlay.height) || this.overlay.minHeight
      );

      this.overlay.width = Math.min(this.overlay.width, this.pdfPageSize.width);
      this.overlay.height = Math.min(this.overlay.height, this.pdfPageSize.height);

      this.overlay.x = Math.max(
        0,
        Math.min(Number(this.overlay.x) || 0, this.pdfPageSize.width - this.overlay.width)
      );

      this.overlay.y = Math.max(
        0,
        Math.min(Number(this.overlay.y) || 0, this.pdfPageSize.height - this.overlay.height)
      );

      this.overlay.x = Math.round(this.overlay.x);
      this.overlay.y = Math.round(this.overlay.y);
      this.overlay.width = Math.round(this.overlay.width);
      this.overlay.height = Math.round(this.overlay.height);
    },

    setDefaultOverlaySize() {
      if (!this.pdfPageSize.width) return;

      const width = this.pdfPageSize.width * 0.82;
      const height = width / this.overlay.aspectRatio;

      this.overlay.width = Math.round(width);
      this.overlay.height = Math.round(height);
      this.overlay.scale = 82;

      this.clampOverlayIntoPage();
    },

    centerOverlayBottom() {
      if (!this.pdfPreviewReady) return;

      this.overlay.x = Math.round((this.pdfPageSize.width - this.overlay.width) / 2);
      this.overlay.y = Math.round(this.pdfPageSize.height - this.overlay.height - 90);

      this.clampOverlayIntoPage();
    },

    centerOverlayMiddle() {
      if (!this.pdfPreviewReady) return;

      this.overlay.x = Math.round((this.pdfPageSize.width - this.overlay.width) / 2);
      this.overlay.y = Math.round((this.pdfPageSize.height - this.overlay.height) / 2);

      this.clampOverlayIntoPage();
    },

    centerOverlayTop() {
      if (!this.pdfPreviewReady) return;

      this.overlay.x = Math.round((this.pdfPageSize.width - this.overlay.width) / 2);
      this.overlay.y = 90;

      this.clampOverlayIntoPage();
    },

    async downloadAd() {
      if (!this.$refs.adRef) return;

      if (!this.form.title.trim()) {
        await Swal.fire({
          icon: 'warning',
          title: 'Falta el título',
          text: 'Introduce al menos un título para el anuncio.',
        });
        return;
      }

      this.downloading = true;

      try {
        const dataUrl = await this.generateAdDataUrl();

        const link = document.createElement('a');
        link.href = dataUrl;
        link.download = this.buildImageFileName();
        link.click();

        await Swal.fire({
          icon: 'success',
          title: 'Anuncio descargado',
          text: 'El PNG se ha generado correctamente.',
          timer: 1600,
          showConfirmButton: false,
        });
      } catch (error) {
        console.error('[AdCreator] Error generando PNG:', error);

        await Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se pudo generar la imagen del anuncio.',
        });
      } finally {
        this.downloading = false;
      }
    },

    async downloadPdfWithManualAd() {
      if (!this.pdfFile) {
        await Swal.fire({
          icon: 'warning',
          title: 'Falta el PDF',
          text: 'Selecciona un PDF donde insertar el anuncio.',
        });
        return;
      }

      if (!this.pdfPreviewReady) {
        await Swal.fire({
          icon: 'warning',
          title: 'Falta la previsualización',
          text: 'Espera a que se cargue la vista previa del PDF.',
        });
        return;
      }

      if (!this.form.title.trim()) {
        await Swal.fire({
          icon: 'warning',
          title: 'Falta el título',
          text: 'Introduce al menos un título para el anuncio.',
        });
        return;
      }

      this.attachingPdf = true;

      try {
        if (!this.adPreviewDataUrl || this.adPreviewDirty) {
          await this.prepareAdPreviewImage();
        }

        const pdfBytes = await this.pdfFile.arrayBuffer();
        const pdfDoc = await PDFDocument.load(pdfBytes);

        const pages = pdfDoc.getPages();

        const selectedPageIndex = Math.max(
          0,
          Math.min((Number(this.pdfOptions.page) || 1) - 1, pages.length - 1)
        );

        const page = pages[selectedPageIndex];

        const pageWidth = page.getWidth();
        const pageHeight = page.getHeight();

        const adImageBytes = this.dataUrlToUint8Array(this.adPreviewDataUrl);
        const adImage = await pdfDoc.embedPng(adImageBytes);

        const visualAdWidth = this.overlayDimensions.width;
        const visualAdHeight = this.overlayDimensions.height;

        const scaleX = pageWidth / this.pdfPageSize.width;
        const scaleY = pageHeight / this.pdfPageSize.height;

        const pdfX = this.overlay.x * scaleX;
        const pdfWidth = visualAdWidth * scaleX;
        const pdfHeight = visualAdHeight * scaleY;

        const pdfY = pageHeight - ((this.overlay.y + visualAdHeight) * scaleY);

        page.drawImage(adImage, {
          x: pdfX,
          y: pdfY,
          width: pdfWidth,
          height: pdfHeight,
        });

        const modifiedPdfBytes = await pdfDoc.save();

        const blob = new Blob([modifiedPdfBytes], {
          type: 'application/pdf',
        });

        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = this.buildPdfFileName();
        a.click();

        URL.revokeObjectURL(url);

        await Swal.fire({
          icon: 'success',
          title: 'PDF generado',
          text: 'El anuncio se ha insertado correctamente.',
          timer: 1600,
          showConfirmButton: false,
        });
      } catch (error) {
        console.error('[AdCreator] Error generando PDF:', error);

        await Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se pudo generar el PDF con el anuncio.',
        });
      } finally {
        this.attachingPdf = false;
      }
    },

    async generateAdDataUrl() {
      await this.$nextTick();

      return toPng(this.$refs.adRef, {
        quality: 1,
        pixelRatio: 2,
        backgroundColor: this.form.backgroundColor,
        cacheBust: false,
      });
    },

    dataUrlToUint8Array(dataUrl) {
      const base64 = dataUrl.split(',')[1];
      const binary = atob(base64);
      const length = binary.length;
      const bytes = new Uint8Array(length);

      for (let i = 0; i < length; i++) {
        bytes[i] = binary.charCodeAt(i);
      }

      return bytes;
    },

    buildImageFileName() {
      const cleanTitle = this.cleanFileText(this.form.title);
      const date = new Date().toISOString().slice(0, 10);

      return `anuncio-${cleanTitle || 'informe'}-${date}.png`;
    },

    buildPdfFileName() {
      const originalName = this.pdfFile?.name
        ? this.pdfFile.name.replace(/\.pdf$/i, '')
        : 'informe';

      const cleanName = this.cleanFileText(originalName);
      const date = new Date().toISOString().slice(0, 10);

      return `${cleanName || 'informe'}-con-anuncio-${date}.pdf`;
    },

    cleanFileText(value) {
      return value
        .toString()
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 45);
    },

    async resetForm() {
      await this.removeImage();

      this.form = {
        template: 'horizontal',
        title: 'Reduce tu factura energética',
        description: 'Instalaciones fotovoltaicas para empresas con estudio gratuito.',
        cta: 'Solicita información',
        contact: 'cliente.com · 600 000 000',
        image: null,
        backgroundColor: '#FFF4F5',
        primaryColor: '#B00020',
        textColor: '#222222',
      };

      await this.refreshAdPreviewIfPdfLoaded();
    },

    hexToRgba(hex, alpha = 1) {
      const cleanHex = hex.replace('#', '');

      if (cleanHex.length !== 6) {
        return `rgba(0, 0, 0, ${alpha})`;
      }

      const r = parseInt(cleanHex.substring(0, 2), 16);
      const g = parseInt(cleanHex.substring(2, 4), 16);
      const b = parseInt(cleanHex.substring(4, 6), 16);

      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    },
  },

  beforeUnmount() {
    this.removePdfObjectUrl();

    window.removeEventListener('pointermove', this.onPointerMove);
    window.removeEventListener('pointerup', this.stopPointerAction);
  },
};
</script>

<style scoped>
.ad-creator-layout {
  display: grid;
  grid-template-columns: 380px 1fr;
  gap: 24px;
  align-items: flex-start;
}

.ad-settings,
.ad-preview-panel {
  min-width: 0;
}

.ad-settings,
.ad-preview-panel {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 14px;
  padding: 18px;
  background: rgba(255, 255, 255, 0.55);
}

.form-group textarea {
  min-height: 84px;
  resize: vertical;
}

.form-group select {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;
  font-size: 14px;
}

.ad-color-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 11px;
}

.ad-pdf-controls {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 11px;
}

.ad-range {
  width: 100%;
  cursor: pointer;
}

.color-input {
  width: 100%;
  height: 38px;
  padding: 2px;
  border: 1.5px solid rgba(0, 0, 0, 0.12);
  border-radius: 10px;
  background: white;
  cursor: pointer;
}

.ad-link-button {
  border: none;
  background: transparent;
  color: var(--rojo, #c62828);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}

.ad-preview-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  overflow-x: auto;
  padding: 24px;
  border-radius: 14px;
  background:
    linear-gradient(45deg, rgba(0,0,0,0.035) 25%, transparent 25%),
    linear-gradient(-45deg, rgba(0,0,0,0.035) 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, rgba(0,0,0,0.035) 75%),
    linear-gradient(-45deg, transparent 75%, rgba(0,0,0,0.035) 75%);
  background-size: 22px 22px;
  background-position: 0 0, 0 11px, 11px -11px, -11px 0;
}

.report-ad-banner {
  width: 720px;
  height: 150px;
  display: flex;
  gap: 18px;
  padding: 16px;
  border-radius: 18px;
  border: 1.5px solid rgba(176, 0, 32, 0.22);
  box-sizing: border-box;
  font-family: Arial, sans-serif;
  overflow: hidden;
  flex-shrink: 0;
}

.report-ad-image {
  width: 190px;
  height: 118px;
  border-radius: 14px;
  overflow: hidden;
  flex-shrink: 0;
  background: #ffffff;
}

.report-ad-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.report-ad-image-placeholder {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  color: #999999;
  font-size: 13px;
  background: rgba(255, 255, 255, 0.85);
}

.report-ad-image-placeholder i {
  display: block;
  font-size: 24px;
  margin-bottom: 4px;
}

.report-ad-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
}

.report-ad-content h2 {
  margin: 0 0 8px;
  font-size: 24px;
  line-height: 1.1;
  font-weight: 800;
  letter-spacing: -0.3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.report-ad-content p {
  margin: 0 0 11px;
  font-size: 14px;
  line-height: 1.25;
  max-height: 36px;
  overflow: hidden;
}

.report-ad-footer {
  display: flex;
  align-items: center;
  gap: 12px;
}

.report-ad-cta {
  display: inline-block;
  padding: 7px 13px;
  border-radius: 999px;
  color: #ffffff;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.report-ad-contact {
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.report-ad-simple {
  width: 720px;
  height: 150px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px 34px;
  border-radius: 18px;
  border: 1.5px solid rgba(176, 0, 32, 0.22);
  box-sizing: border-box;
  font-family: Arial, sans-serif;
  overflow: hidden;
  flex-shrink: 0;
}

.report-ad-simple-content {
  width: 100%;
  text-align: center;
}

.report-ad-simple-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 999px;
  color: white;
  font-size: 11px;
  font-weight: 800;
  margin-bottom: 8px;
}

.report-ad-simple h2 {
  margin: 0 0 7px;
  font-size: 27px;
  line-height: 1.05;
  font-weight: 800;
}

.report-ad-simple p {
  margin: 0 0 7px;
  font-size: 14px;
  line-height: 1.25;
}

.report-ad-simple-contact {
  font-size: 12px;
  font-weight: 700;
}

.ad-info-box {
  padding: 11px 14px;
  border-radius: 10px;
  background: rgba(99, 132, 199, 0.08);
  border: 1.5px solid rgba(99, 132, 199, 0.18);
  color: #4a5f91;
  font-size: 12px;
  font-weight: 600;
  text-align: center;
}

.pdf-empty-state {
  padding: 40px 20px;
  border-radius: 14px;
  border: 1.5px dashed rgba(0, 0, 0, 0.14);
  text-align: center;
  color: #777;
}

.pdf-empty-state i {
  font-size: 40px;
  color: rgba(176, 0, 32, 0.45);
}

.pdf-preview-box {
  margin-top: 18px;
  display: flex;
  justify-content: center;
  overflow-x: auto;
  padding-bottom: 8px;
}

.pdf-preview-wrapper {
  position: relative;
  display: inline-block;
  min-height: 120px;
  border-radius: 10px;
  overflow: auto;
  border: 1.5px solid rgba(0, 0, 0, 0.12);
  background: white;
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.pdf-page-stage {
  position: relative;
  display: inline-block;
  line-height: 0;
}

.pdf-render-host {
  display: block;
}

.pdf-render-host canvas,
.pdf-render-host svg {
  display: block;
}

.pdf-ad-overlay {
  position: absolute;
  cursor: move;
  border: 2px dashed rgba(176, 0, 32, 0.9);
  border-radius: 8px;
  box-sizing: border-box;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.22);
  box-shadow: 0 8px 20px rgba(0,0,0,0.14);
  user-select: none;
  touch-action: none;
  line-height: normal;
}

.pdf-ad-overlay img {
  width: 100%;
  height: 100%;
  display: block;
  pointer-events: none;
}

.pdf-ad-overlay-move {
  position: absolute;
  right: 6px;
  top: 6px;
  width: 24px;
  height: 24px;
  border-radius: 999px;
  background: rgba(176, 0, 32, 0.92);
  color: white;
  display: grid;
  place-items: center;
  font-size: 11px;
  pointer-events: none;
}

.pdf-ad-overlay-resize {
  position: absolute;
  right: 0;
  bottom: 0;
  width: 30px;
  height: 30px;
  border-top-left-radius: 10px;
  background: rgba(176, 0, 32, 0.95);
  color: white;
  display: grid;
  place-items: center;
  font-size: 11px;
  cursor: nwse-resize;
  z-index: 2;
}

.pdf-ad-overlay-resize:hover {
  background: rgba(140, 0, 24, 1);
}

.pdf-loading-layer {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  align-content: center;
  gap: 8px;
  background: rgba(255,255,255,0.72);
  color: #333;
  font-size: 13px;
  font-weight: 700;
  z-index: 3;
}

@media (max-width: 1100px) {
  .ad-creator-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 820px) {
  .report-ad-banner,
  .report-ad-simple {
    transform: scale(0.82);
    transform-origin: center;
  }

  .ad-preview-wrapper {
    padding: 10px;
  }
}

@media (max-width: 620px) {
  .report-ad-banner,
  .report-ad-simple {
    transform: scale(0.62);
  }

  .ad-preview-wrapper {
    min-height: 130px;
  }

  .ad-color-grid,
  .ad-pdf-controls {
    grid-template-columns: 1fr;
  }
}
</style>