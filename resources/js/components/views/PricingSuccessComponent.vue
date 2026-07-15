<template>
    <div class="w-100 h-100-vh d-flex justify-center align-center relPos" data-bg="blanco">
        <div class="loader-box">
            <img :src="'/assets/enterprises/zocoenergia/logos/mini-dark.png'" class="absPos right mt-20 ml-20 w-100-px" style="top:0">
            <div class="dashboard-card w-auto">
                <div class="loader"></div>
                <p class="ml-20" data-size="25">Procesando suscripción</p>
            </div>
        </div>
    </div>
</template>

<script>
export default{
    name: "PricingSuccessComponent",
    methods: {
        async retrieveData(){
            await axios.post("/api/stripe/success", {sessionId: this.$route.query.session_id}).then(() => {
                sessionStorage.removeItem('last_subscription_check');
                window.location.href = "/profile"
            })
        }
    },
    mounted(){
        this.retrieveData();
    }
}
</script>
