import axios from 'axios';

export default async function checkSubscriptionStatus(force = false) {
    const now = Date.now();

    const lastCheck = parseInt(
        sessionStorage.getItem('last_subscription_check') || '0',
        10
    );

    const minutesSinceLastCheck = (now - lastCheck) / 1000 / 60;
    const cachedStatus = sessionStorage.getItem('subscription_status');

    if (!force && cachedStatus !== null && minutesSinceLastCheck <= 5) {
        return cachedStatus === 'true';
    }

    try {
        const res = await axios.get('/api/session/checkSubscriptionStatus');

        const isActive = res.data?.active === true;

        sessionStorage.setItem('subscription_status', isActive ? 'true' : 'false');
        sessionStorage.setItem('subscription_stripe_status', res.data?.status || '');
        sessionStorage.setItem('last_subscription_check', now.toString());

        return isActive;
    } catch (err) {
        console.error('Error consultando suscripción:', err);

        sessionStorage.setItem('subscription_status', 'false');
        sessionStorage.setItem('last_subscription_check', now.toString());

        return false;
    }
}
