<template>
    <p class="opacity-5 my-10" data-size="11">
        Adjunta un excel para cambiar automaticamente el estado de los contratos
        con CUPS dentro del excel
    </p>

    <div class="d-flex mt-30">
        <div class="custom-button" data-bg="principal" data-mode="translucent" data-size="regular" @click="openDialog">
            Adjunta excel <i class="fa fas fa-paperclip ml-5"></i>
        </div>

        <input type="file" ref="inputExcel" style="display: none" accept=".xls, .xlsx, .csv" @change="pickupFile" />

        <a class="custom-button ml-10" data-bg="principal" data-mode="translucent" data-size="regular"
            href="/assets/templates/states.xlsx" download="Plantilla_Estados_CUPS.xlsx">
            Descargar plantilla<i class="fas fa-file-arrow-down ml-4 mr-10"></i>
        </a>
    </div>
</template>

<script>
export default {
    props: ["basicData"],
    name: "StatesComponent",
    methods: {
        openDialog() {
            // Limpiamos valor previo para garantizar que @change siempre se dispare
            const input = this.$refs.inputExcel;
            input.value = null;
            input.click();
        },
        pickupFile() {
            const input = this.$refs.inputExcel;
            const files = input.files;

            if (!files || !files.length) return;
            const file = files[0];

            this.statesCUPS(file);
        },
        async statesCUPS(file) {
    const formData = new FormData();
    formData.append("file", file);
    formData.append("userLogged", JSON.stringify(this.basicData.userLogged));
    formData.append("entreprise", JSON.stringify(this.basicData.entreprise));
    formData.append("userList", JSON.stringify(this.basicData.userLogged));
    formData.append("userSubdomain", JSON.stringify(this.basicData.userSubdomain));

    try {
        const res = await axios.post("/api/tools/statesMassive", formData);

        const message  = res.data.message || "Proceso finalizado";
        const updated  = Array.isArray(res.data.updated)  ? res.data.updated  : [];
        const warnings = Array.isArray(res.data.warnings) ? res.data.warnings : [];
        const errors   = Array.isArray(res.data.errors)   ? res.data.errors   : [];

        let html = `<p>${message}</p>`;

        if (updated.length) {
            html += `
                <hr>
                <b>✔ Actualizados (${updated.length})</b>
                <ul style="text-align:left;max-height:150px;overflow:auto">
                    ${updated.map(u => `<li>${u}</li>`).join("")}
                </ul>
            `;
        }

        if (warnings.length) {
            html += `
                <hr>
                <b>⚠ Advertencias (${warnings.length})</b>
                <ul style="text-align:left;max-height:150px;overflow:auto">
                    ${warnings.map(w => `<li>${w}</li>`).join("")}
                </ul>
            `;
        }

        if (errors.length) {
            html += `
                <hr>
                <b>❌ Errores (${errors.length})</b>
                <ul style="text-align:left;max-height:150px;overflow:auto">
                    ${errors.map(e => `<li>${e}</li>`).join("")}
                </ul>
            `;
        }

        await Swal.fire({
            icon: errors.length ? "error" : warnings.length ? "warning" : "success",
            title: "CUPS procesados",
            html,
            width: 700,
            confirmButtonText: "Aceptar"
        });

    } catch (error) {
        const message = error.response?.data?.message || "Error al procesar el archivo";
        Swal.fire({
            icon: "error",
            title: "Error al procesar CUPS",
            text: message,
        });
    }
}
,

    },
};
</script>
