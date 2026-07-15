<template>
  <div class="terminos-container">
    <h1 class="titulo">Condiciones de Uso</h1>
    <p>Al utilizar nuestros servicios, aceptas cumplir con los siguientes términos y condiciones:</p>

    <div class="seccion">
      <h3>1. Aceptación de los Términos</h3>
      <p>Al acceder y utilizar este sitio web, aceptas estar sujeto a estos Términos de Uso y a todas las leyes y normativas aplicables. Si no estás de acuerdo con estos términos, no utilices nuestro servicio.</p>
    </div>

    <div class="seccion">
      <h3>2. Uso de los Servicios</h3>
      <p>El acceso y uso de nuestros servicios están destinados exclusivamente a usuarios registrados. Aceptas usar el servicio solo para fines legales y de acuerdo con nuestras políticas.</p>
    </div>

    <div class="seccion">
      <h3>3. Privacidad y Protección de Datos</h3>
      <p>Nos comprometemos a proteger tu privacidad. Todos los datos personales proporcionados serán tratados de acuerdo con nuestra 
        <a href="/politica-privacidad" target="_blank" class="enlace">Política de Privacidad</a>.
      </p>
    </div>

    <div class="seccion">
      <h3>4. Limitación de Responsabilidad</h3>
      <p>No nos hacemos responsables de los daños directos o indirectos que puedan surgir del uso de nuestros servicios. El uso de los mismos es bajo tu propio riesgo.</p>
    </div>

    <div class="seccion">
      <h3>5. Modificación de los Términos</h3>
      <p>Nos reservamos el derecho de modificar estos términos en cualquier momento. Las modificaciones serán efectivas inmediatamente después de su publicación en este sitio web. Te recomendamos revisar periódicamente los términos.</p>
    </div>

    <div class="seccion">
      <h3>6. Propiedad Intelectual</h3>
      <p>Todos los derechos sobre el contenido, diseño y funcionalidad del sitio web son propiedad exclusiva de nuestra empresa. Queda prohibida la reproducción, distribución o modificación no autorizada del contenido.</p>
    </div>

    <div class="seccion">
      <h3>7. Enlaces a Sitios Externos</h3>
      <p>Nuestros servicios pueden contener enlaces a sitios web de terceros. No somos responsables del contenido o las prácticas de privacidad de esos sitios.</p>
    </div>

    <div class="seccion">
      <h3>8. Ley Aplicable</h3>
      <p>Estos términos se rigen por las leyes de España y cualquier disputa relacionada con los mismos será resuelta ante los tribunales competentes de Córdoba.</p>
    </div>

    <div class="seccion">
      <h3>9. Aceptación de los Términos</h3>
      <p>Al marcar la casilla correspondiente en el formulario de registro, confirmas que has leído, entendido y aceptas estos términos y condiciones.</p>
    </div>
  </div>
</template>

<script>
export default {
  name: "Terminos",
};
</script>

<style scoped>
.terminos-container {
  padding: 20px;
  font-family: 'Arial', sans-serif;
  background-color: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.titulo {
  font-size: 28px;
  font-weight: bold;
  color: #333;
  margin-bottom: 20px;
}

.seccion {
  margin-bottom: 20px;
}

.seccion h3 {
  font-size: 18px;
  color: #007BFF;
  margin-bottom: 10px;
}

.seccion p {
  font-size: 16px;
  color: #555;
  line-height: 1.6;
}

.enlace {
  color: #007BFF;
  text-decoration: none;
}

.enlace:hover {
  text-decoration: underline;
}
</style>