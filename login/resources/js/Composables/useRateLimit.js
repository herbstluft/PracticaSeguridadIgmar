import { ref, watch, onUnmounted } from 'vue';

export function useRateLimit(form, errorKey, defaultMessageGenerator) {
    const lockSeconds = ref(0);
    let lockInterval = null;

    const startLockCountdown = (seconds) => {
        lockSeconds.value = seconds;
        if (lockInterval) clearInterval(lockInterval);
        
        lockInterval = setInterval(() => {
            if (lockSeconds.value > 1) {
                lockSeconds.value--;
                form.errors[errorKey] = defaultMessageGenerator(lockSeconds.value);
            } else {
                lockSeconds.value = 0;
                form.errors[errorKey] = null;
                clearInterval(lockInterval);
                lockInterval = null;
            }
        }, 1000);
    };

    // Observa form.errors profundamente para detectar el mensaje de error de límite de tasa
    watch(
        () => form.errors,
        (newErrors) => {
            if (newErrors && newErrors[errorKey]) {
                const errorMsg = newErrors[errorKey];
                // Coincide con patrones como "intenta de nuevo en 36 segundos" o "espera 36 segundos"
                const match = errorMsg.match(/(\d+)\s+segundos/i);
                if (match && match[1]) {
                    const secs = parseInt(match[1], 10);
                    // Solo inicia la cuenta regresiva si no está corriendo actualmente o si el nuevo tiempo de bloqueo es mayor
                    if (lockSeconds.value === 0 || secs > lockSeconds.value) {
                        startLockCountdown(secs);
                    }
                }
            }
        },
        { deep: true, immediate: true }
    );

    onUnmounted(() => {
        if (lockInterval) {
            clearInterval(lockInterval);
        }
    });

    return {
        lockSeconds,
        startLockCountdown,
    };
}
