<template>
    <div>
        <h1>Gérer mon transfert</h1>
        <p>Référence : {{ reference }}</p>
        <!-- TODO: manage UI + delete -->
    </div>
</template>

<script setup>
defineProps({
    ownerToken: { type: String, required: true },
    reference: { type: String, required: true },
    status: { type: String, required: true },
    expiresAt: { type: String, required: true },
    files: { type: String, default: '[]' },
    recipients: { type: String, default: '[]' },
});
</script>
