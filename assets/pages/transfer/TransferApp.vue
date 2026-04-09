<template>
    <div>
        <h1>Télécharger les fichiers</h1>
        <p>Référence : {{ reference }}</p>
        <!-- TODO: file list + download button -->
    </div>
</template>

<script setup>
defineProps({
    token: { type: String, required: true },
    reference: { type: String, required: true },
    expiresAt: { type: String, required: true },
    senderName: { type: String, default: '' },
    files: { type: String, default: '[]' },
});
</script>
