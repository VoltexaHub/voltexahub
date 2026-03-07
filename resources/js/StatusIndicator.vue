<template>
    <a href="/status" class="status-indicator" :title="label">
        <span class="status-dot" :class="dotClass"></span>
        <span class="status-text">{{ label }}</span>
    </a>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const overall = ref('operational');
const loading = ref(true);

const label = computed(() => {
    if (loading.value) return 'Checking status...';
    switch (overall.value) {
        case 'operational': return 'All Systems Operational';
        case 'degraded': return 'Degraded Performance';
        case 'outage': return 'Service Outage';
        default: return 'Unknown';
    }
});

const dotClass = computed(() => {
    if (loading.value) return 'dot-loading';
    return `dot-${overall.value}`;
});

onMounted(async () => {
    try {
        const res = await fetch('/api/status');
        const data = await res.json();
        overall.value = data.overall || 'operational';
    } catch {
        overall.value = 'operational';
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    color: inherit;
    font-size: 0.8rem;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.dot-operational {
    background-color: #22c55e;
}

.dot-degraded {
    background-color: #f59e0b;
}

.dot-outage {
    background-color: #ef4444;
}

.dot-loading {
    background-color: #9ca3af;
}
</style>
