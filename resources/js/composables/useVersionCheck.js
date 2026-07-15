import { ref, onMounted, onUnmounted } from 'vue'

export function useVersionCheck(intervalMs = 5 * 60 * 1000) {
    const newVersionAvailable = ref(false)
    const initialVersion = document
        .querySelector('meta[name="app-version"]')
        ?.getAttribute('content')

    let timer = null

    async function checkVersion() {
        if (newVersionAvailable.value || !initialVersion) return
        try {
            const res = await fetch('/api/version-check', { cache: 'no-store' })
            if (!res.ok) return
            const data = await res.json()
            if (data.version && data.version !== initialVersion) {
                newVersionAvailable.value = true
            }
        } catch (e) {
            // fallo de red, se reintenta en el siguiente ciclo
        }
    }

    function reload() {
        window.location.reload()
    }

    function onVisibilityChange() {
        if (document.visibilityState === 'visible') checkVersion()
    }

    onMounted(() => {
        checkVersion()
        timer = setInterval(checkVersion, intervalMs)
        document.addEventListener('visibilitychange', onVisibilityChange)
    })

    onUnmounted(() => {
        clearInterval(timer)
        document.removeEventListener('visibilitychange', onVisibilityChange)
    })

    return { newVersionAvailable, reload }
}
