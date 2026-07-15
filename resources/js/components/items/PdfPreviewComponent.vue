<template>
    <canvas ref="canvas"></canvas>
</template>

<script>
import * as pdfjsLib from 'pdfjs-dist';

pdfjsLib.GlobalWorkerOptions.workerSrc = '/js/pdf.worker.mjs';

export default {
    props: ['src'],
    mounted() {
        pdfjsLib.getDocument({
            url: this.src,
            disableWorker: true // ✅ funciona bien en esta versión
        }).promise.then(pdf => {
            pdf.getPage(1).then(page => {
                const viewport = page.getViewport({ scale: 1.25 });
                const canvas = this.$refs.canvas;
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                page.render({ canvasContext: context, viewport });
            });
        }).catch(err => {
            console.error('❌ ERROR AL RENDERIZAR PDF:', err);
        });
    }
};
</script>

<style scoped>

</style>
