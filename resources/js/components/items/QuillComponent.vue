<template>
    <div class="form">

        <div class="p-30 my-0">
            <div class="form-group" v-bind:class="{ wrong: errors.subject }">

                <!--Asunto correo y botón enviar-->
                <div class="d-flex my-10">

                    <div class="input-group w-100">
                        <!--Asunto correo-->
                        <input type="text" placeholder="Asunto" v-model="email.subject" v-on:focus="delete errors['subject']">
                    </div>

                    <!--Enviar correos-->
                    <button v-if="type === 'send'" class="custom-button ml-10 d-flex align-center" data-size="medium" data-bg="azul" v-on:click="submitEmail">Enviar <i class="fa-solid fa-paper-plane-top ml-10"></i></button>
                    <button v-else class="custom-button ml-10 d-flex align-center" data-size="medium" data-bg="azul" v-on:click="saveEmail">Guardar <i class="fa-solid fa-floppy-disk ml-10"></i></button>

                </div>
            </div>

            <!--Toolbar-->
            <div id="toolbar" :style="errors.message ? { 'border-color': 'red' } : {}" >
                <!-- Fuente y alineación -->
                <span class="ql-formats">
                    <select class="ql-font w-125-px-min">
                        <option class="ql-font-sans-serif" value="sans-serif" selected>Sans Serif</option>
                        <option class="ql-font-serif" value="serif">Serif</option>
                        <option class="ql-font-monospace" value="monospace">Ancho fijo</option>
                        <option class="ql-font-wide" value="wide">Wide</option>
                        <option class="ql-font-narrow" value="narrow">Narrow</option>
                        <option class="ql-font-comic-sans-ms" value="comic-sans-ms">Comic Sans MS</option>
                        <option class="ql-font-garamond" value="garamond">Garamond</option>
                        <option class="ql-font-georgi" value="georgia">Georgia</option>
                        <option class="ql-font-tahoma" value="tahoma">Tahoma</option>
                        <option class="ql-font-trebuchet-ms" value="trebuchet-ms">Trebuchet MS</option>
                        <option class="ql-font-verdana" value="verdana">Verdana</option>
                    </select>
                </span>

                <!-- Tamaño de fuente -->
                <span class="ql-formats">
                    <select class="ql-size">
                        <option value="small">Pequeña</option>
                        <option selected></option>
                        <option value="large">Grande</option>
                        <option value="huge">Enorme</option>
                    </select>
                </span>

                <!-- Botones básicos -->
                <span class="ql-formats">
                    <button class="ql-bold"></button>
                    <button class="ql-italic"></button>
                    <button class="ql-underline"></button>
                    <button class="ql-strike"></button>
                </span>

                <!-- Enlace, imagen, vídeo, fórmula -->
                <span class="ql-formats">
                    <button class="ql-link"></button>
                    <button class="ql-image"></button>
                </span>

                <!-- Listas -->
                <span class="ql-formats">
                    <button class="ql-list" value="ordered"></button>
                </span>

                <!-- Sangría -->
                <span class="ql-formats">
                    <button class="ql-indent" value="-1"></button>
                    <button class="ql-indent" value="+1"></button>
                </span>


                <!-- Colores -->
                <span class="ql-formats">
                    <select class="ql-color"></select>
                    <select class="ql-background"></select>
                </span>

                <!-- Limpiar formato -->
                <span class="ql-formats">
                    <button class="ql-clean"></button>
                </span>
            </div>


            <!--Contenedor correo-->
            <div id="editor" class="text" :style="errors.message ? { 'border-color': 'red' } : {}"></div>
        </div>
    </div>
</template>

<script>
import Quill from "quill";
import QuillResize from 'quill-resize-module';
import 'quill/dist/quill.snow.css';

    export default{
        props:['email', 'errors', 'type'],
        data(){
            return{
                quill: null
            }
        },
        watch:{
            'email.message'(newVal){
                if (this.quill && this.quill.root.innerHTML !== newVal) {
                    this.quill.root.innerHTML = newVal;
                }            }
        },
        mounted(){
            //Importo font format
            let Font = Quill.import('formats/font');

            //Defino las fuentes que quiero
            Font.whitelist = [
                'sans-serif', 'serif', 'monospace', 'wide', 'narrow', 'comic-sans-ms',
                'garamond', 'georgia', 'tahoma', 'trebuchet-ms', 'verdana'
            ];

            //Registro la configuración
            Quill.register(Font, true);
            Quill.register('modules/resize', QuillResize);


            //Establezco las opciones del editor de texto de email
            this.quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: 'Escribe tu mensaje...',
                modules: {
                    toolbar: '#toolbar',
                    resize: {
                        tools: [
                            {
                                text: 'S',
                                title: 'Pequeño (25%)',
                                handler(evt, button, target) {
                                    if (target) {
                                        target.style.width = '25%';
                                        setTimeout(() => {
                                            target.click();
                                            const esc = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true });
                                            document.dispatchEvent(esc);
                                        }, 10);
                                    }
                                }
                            },
                            {
                                text: 'M',
                                title: 'Mediano (50%)',
                                handler(evt, button, target) {
                                    if (target) {
                                        target.style.width = '50%';
                                        setTimeout(() => {
                                            target.click();
                                            const esc = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true });
                                            document.dispatchEvent(esc);
                                        }, 10);
                                    }
                                }
                            },
                            {
                                text: 'L',
                                title: 'Grande (75%)',
                                handler(evt, button, target) {
                                    if (target) {
                                        target.style.width = '75%';
                                        setTimeout(() => {
                                            target.click();
                                            const esc = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true });
                                            document.dispatchEvent(esc);
                                        }, 10);
                                    }
                                }
                            },
                            {
                                text: '100%',
                                title: 'Completo (100%)',
                                handler(evt, button, target) {
                                    if (target) {
                                        target.style.width = '100%';
                                        setTimeout(() => {
                                            target.click();
                                            const esc = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true });
                                            document.dispatchEvent(esc);
                                        }, 10);
                                    }
                                }
                            },
                            {
                                icon: `<svg viewBox="0 0 18 18"><path d="M6 6L12 12M6 12L12 6" stroke="#e74c3c" stroke-width="2"/></svg>`,
                                title: 'Eliminar',
                                handler(evt, button, target) {
                                    if (target && target.parentNode) {
                                        target.parentNode.removeChild(target);
                                    }
                                }
                            }
                        ]
                    }
                }
            });

            // Establecer plantilla
            /*if (this.type !== 'send' && (!this.email.message || this.email.message === '')){
                this.quill.root.innerHTML = "<p>Queremos darte la bienvenida a<strong> <em>Segenet Control SL</em></strong> y agradecerte por confiar en nosotros como tu equipo de gestión energética.</p><p><br></p><p>Hemos creado un espacio personalizado para ti en nuestra plataforma, desde donde podremos colaborar y facilitar todos los procesos relacionados con tus servicios energéticos, de forma ágil, profesional y segura.</p><p><br></p><p>Desde ahora, contarás con el respaldo de nuestro equipo técnico y humano para lo que necesites. Estamos encantados de tenerte con nosotros y esperamos ayudarte a alcanzar tus objetivos con total transparencia y eficacia.</p><p><br></p><p>Gracias por ser parte de <em>Segenet Control SL</em>✨</p><p><br></p><p>Un saludo,</p><p>El equipo de <strong>Segenet Control SL</strong></p>"

            }else{
                this.quill.root.innerHTML = this.email.message
            }

            if (!this.email.subject || this.email.subject === '')
                this.email.subject = '¡Gracias por confiar en nosotros!'

            */

            this.quill.root.innerHTML = this.email.message


            //this.quill.root.innerHTML = '<p><span class="ql-size-large">¡Hola Jaime!</span></p><p><br></p><p>Mi nombre es <strong>Fran</strong> y soy <strong><em>programador</em></strong> en <strong><em><u>Segenet Control </u><s><u>SL</u></s></em></strong>. Me gustaría aprovechar la oportunidad para presentarme y explicar brevemente en qué consisten los servicios/productos que ofrecemos.</p><p>En <strong><em><u>Segenet Control </u><s><u>SL</u></s></em></strong>, nos especializamos en:</p><p><br></p><ol><li data-list="ordered"><span class="ql-ui" contenteditable="false"></span>Contadores monitorizados</li><li data-list="ordered"><span class="ql-ui" contenteditable="false"></span>Asesoria energética</li><li data-list="ordered"><span class="ql-ui" contenteditable="false"></span>Formación de asesores</li></ol><p><br></p><p class="ql-indent-1">¿<a href="Estarías interesado en alguno de estos" rel="noopener noreferrer" target="_blank" class="ql-font-comic-sans-ms">Estarías interesado en alguno de estos</a>?</p><p class="ql-indent-1"><br></p><p>Un saludo, <span style="color: rgb(255, 255, 255); background-color: rgb(0, 0, 0);">Fran.</span></p><p><br></p><p><br></p>';

            // Evento que se lanza cada vez que el usuario escribe
            this.quill.on('text-change', () => {
                this.email.message = this.quill.root.innerHTML;

                if (this.email.message === '<p><br></p>')
                    this.email.message = ''
            });


            // Evento que se lanza cada vez que se pincha en el campo de mensaje
            this.quill.on('selection-change', () => {
                if(this.errors.message) this.errors.message = '';
            });
        },
        methods:{
            submitEmail(){
                this.$emit('submitEmail');
            },
            saveEmail(){
                this.$emit('saveEmail', this.type);
            },
        }
    }
</script>

<style scoped>

#toolbar{
    border: 1px solid rgba(0, 35, 72, 0.1);
    border-radius: 10px 10px 0 0;
    padding: 5px 10px;
}

#editor{
    border: 1px solid rgba(0, 35, 72, 0.1);
    border-top: 0;
    border-radius: 0 0 10px 10px;
}

#editor p {
    display: block;
}

#editor p:has(img[src*="quill/icons/"]),
#editor p:has(img[src*="quill-resize-module/src/"]) {
    display: none !important;
}

</style>
