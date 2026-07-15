<template>
  <div class="order-wrapper">
    <div class="order-card">

      <!-- HEADER -->
      <div class="order-header">
        <h1 class="order-title">
          {{ order.name || 'Contrato' }}
        </h1>
      </div>

      <!-- INFO -->
      <div class="order-info">
        <div class="info-box">
          <span class="info-label">Agente</span>
          <span class="info-value">
            {{ agentFullName }}
          </span>
        </div>

        <div class="info-box">
          <span class="info-label">Estado</span>
          <span class="status-badge" :class="statusClass">
            {{ statusTitle }}
          </span>
        </div>

        <div class="info-box">
          <span class="info-label">Producto</span>
          <span class="info-value">
            {{ order.product || '-' }}
          </span>
        </div>

        <div class="info-box">
          <span class="info-label">Comercializadora</span>
          <span class="info-value">
            {{ order.marketer || '-' }}
          </span>
        </div>
      </div>

      <!-- UPLOAD -->
      <div class="upload-section">
        <h2>Adjuntar documentación</h2>

        <input
          type="file"
          ref="fileInput"
          class="hidden-input"
          multiple
          @change="handleFileUpload"
        />

        <div class="upload-buttons">
          <button class="btn-primary" @click="triggerFile">
            Seleccionar archivos
          </button>

          <button
            v-if="selectedFiles.length"
            class="btn-success"
            :disabled="loading"
            @click="uploadFiles"
          >
            {{ loading ? 'Subiendo...' : 'Subir archivos' }}
          </button>
        </div>

        <!-- LISTA DE ARCHIVOS -->
        <div v-if="selectedFiles.length" class="file-list">
          <div
            v-for="(file, index) in selectedFiles"
            :key="index"
            class="file-item"
          >
            <span class="file-name">
              {{ file.name }}
            </span>

            <button
              class="remove-btn"
              @click="removeFile(index)"
              type="button"
            >
              Eliminar
            </button>
          </div>
        </div>

      </div>

    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'OrderDocumentationView',
  props: ['basicData'],

  data() {
    return {
      order: {},
      selectedFiles: [],
      loading: false
    }
  },

  computed: {

    orderId() {
      return this.$route.params.orderId
    },

    agentFullName() {
      if (!this.order.createdBy || !this.basicData?.userList) return '-'

      const user = this.basicData.userList.find(
        u => u._id === this.order.createdBy
      )

      if (!user) return '-'

      return `${user.firstName || ''} ${user.lastName || ''}`.trim()
    },

    statusTitle() {
      if (!this.order.statuses?.length) return '-'

      const last = this.order.statuses[this.order.statuses.length - 1]
      const statusCode = last.code

      const statusFromSubdomain = this.basicData?.userSubdomain?.statuses?.find(
        s => s.code === statusCode
      )

      return statusFromSubdomain?.title || statusCode || '-'
    },

    statusClass() {
      if (!this.order.statuses?.length)
        return 'status-default'

      const last = this.order.statuses[this.order.statuses.length - 1]

      const map = {
        r: 'status-blue',
        p: 'status-yellow',
        a: 'status-green',
        i: 'status-red',
        b: 'status-gray'
      }

      return map[last.code] || 'status-default'
    }
  },

  async mounted() {
    await this.fetchOrder()
  },

  methods: {

    async fetchOrder() {
      const res = await axios.get(`/api/orders/${this.orderId}`)
      this.order = res.data.order
    },

    triggerFile() {
      this.$refs.fileInput.click()
    },

    handleFileUpload(e) {
      const files = Array.from(e.target.files)
      this.selectedFiles = [...this.selectedFiles, ...files]
      e.target.value = ''
    },

    removeFile(index) {
      this.selectedFiles.splice(index, 1)
    },

    async uploadFiles() {
      if (!this.selectedFiles.length) return

      this.loading = true

      const formData = new FormData()

      this.selectedFiles.forEach(file => {
        formData.append('files', file)
      })

      await axios.post(
        `/api/orders/${this.orderId}/upload-document`,
        formData
      )

      this.loading = false
      this.selectedFiles = []
      alert('Documentos subidos correctamente')
    }
  }
}
</script>

<style scoped>

.order-wrapper {
  min-height: 100vh;
  background: #f3f4f6;
  padding: 40px 20px;
}

.order-card {
  max-width: 1000px;
  margin: auto;
  background: white;
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
  overflow: hidden;
}

.order-header {
  background: #3b82f6;
  padding: 30px;
  color: white;
}

.order-title {
  font-size: 28px;
  font-weight: 700;
}

.order-info {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 25px;
  padding: 40px;
}

.info-box {
  background: #f9fafb;
  padding: 20px;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
}

.info-label {
  font-size: 12px;
  text-transform: uppercase;
  color: #6b7280;
  margin-bottom: 8px;
}

.info-value {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.upload-section {
  border-top: 1px solid #e5e7eb;
  padding: 40px;
}

.upload-buttons {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  padding: 12px 22px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
}

.btn-success {
  background: #16a34a;
  color: white;
  padding: 12px 22px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
}

.file-list {
  margin-top: 15px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
}

.file-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 15px;
  border-bottom: 1px solid #e5e7eb;
}

.file-item:last-child {
  border-bottom: none;
}

.file-name {
  font-size: 14px;
  color: #374151;
  word-break: break-all;
}

.remove-btn {
  background: transparent;
  border: none;
  color: #dc2626;
  font-size: 13px;
  cursor: pointer;
}

.remove-btn:hover {
  text-decoration: underline;
}

.hidden-input {
  display: none;
}

.status-badge {
  padding: 6px 14px;
  border-radius: 30px;
  font-size: 13px;
  font-weight: 600;
}

.status-blue { background: #dbeafe; color: #1e40af; }
.status-yellow { background: #fef3c7; color: #92400e; }
.status-green { background: #dcfce7; color: #166534; }
.status-red { background: #fee2e2; color: #991b1b; }
.status-gray { background: #e5e7eb; color: #374151; }
.status-default { background: #f3f4f6; color: #374151; }

</style>