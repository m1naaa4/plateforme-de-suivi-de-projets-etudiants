<template>
    <label class="language-switcher">
        <span class="language-switcher-label">{{ t('common.language') }}</span>
        <select :value="locale" @change="setLocale($event.target.value)">
            <option
                v-for="option in localeOptions"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
    </label>
</template>

<script setup>
import { locale, localeOptions, setLocale, t } from '../i18n';
</script>

<style scoped>
.language-switcher {
    display: grid;
    gap: 0.45rem;
    min-width: 7rem;
}

.language-switcher-label {
    color: #8a7a89;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

select {
    border: 1px solid #ddd2dd;
    border-radius: 14px;
    padding: 0.6rem 0.8rem;
    background: #ffffff;
    color: #2f2430;
    font: inherit;
    font-weight: 700;
}
</style>
