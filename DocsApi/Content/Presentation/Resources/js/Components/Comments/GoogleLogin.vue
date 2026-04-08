<template>
    <div :id="containerId" class="w-full flex justify-center"></div>
</template>

<script setup>
import { onMounted } from 'vue';

const props = defineProps({
    clientId: {
        type: String,
        required: true
    },
    containerId: {
        type: String,
        default: 'google-signin-button'
    }
});

const emit = defineEmits(['login-success', 'login-error']);

onMounted(() => {
    // Check if google global exists and script is loaded
    const initializeGoogleBtn = () => {
        if (window.google && window.google.accounts) {
            window.google.accounts.id.initialize({
                client_id: props.clientId,
                callback: (response) => {
                    emit('login-success', response);
                },
                auto_select: false,
                cancel_on_tap_outside: false
            });

            window.google.accounts.id.renderButton(
                document.getElementById(props.containerId),
                {
                    type: "standard",
                    theme: "filled_blue",
                    size: "large",
                    text: "continue_with",
                    shape: "pill",
                    logo_alignment: "left",
                    width: "320"
                }
            );
        } else {
            // Retry if script hasn't loaded (simple polling)
            setTimeout(initializeGoogleBtn, 200);
        }
    };

    initializeGoogleBtn();
});
</script>
