<template>
    <main class="register-page">
        <section class="register-card">
            <div class="auth-topline">
                <p class="eyebrow">{{ t('auth.platform') }}</p>
                <LanguageSwitcher />
            </div>

            <h1>{{ t('auth.registerTitle') }}</h1>

            <p class="subtitle">
                {{ t('auth.registerSubtitle') }}
            </p>

            <form class="register-form" @submit.prevent="register">
                <label>
                    {{ t('auth.fullName') }}
                    <input v-model="form.name" type="text" required>
                </label>

                <label>
                    {{ t('auth.email') }}
                    <input v-model="form.email" type="email" required>
                </label>

                <label>
                    {{ t('auth.password') }}
                    <input v-model="form.password" type="password" required>
                </label>

                <button type="submit">
                    {{ t('auth.registerButton') }}
                </button>

                <p v-if="error" class="error-message">
                    {{ error }}
                </p>

                <p v-if="success" class="success-message">
                    {{ success }}
                </p>

                <router-link to="/login" class="register-link">
                    {{ t('auth.backToLogin') }}
                </router-link>
            </form>
        </section>
    </main>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import LanguageSwitcher from '../components/LanguageSwitcher.vue';
import { t } from '../i18n';

const router = useRouter();

const form = ref({
    name: '',
    email: '',
    password: '',
});

const error = ref('');
const success = ref('');

const register = async () => {
    error.value = '';
    success.value = '';

    try {
        const response = await fetch('/api/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (!response.ok) {
            error.value = t('auth.registerFailed');
            return;
        }

        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));

        success.value = t('auth.accountCreated');
        router.push('/dashboard');
    } catch (e) {
        error.value = t('auth.registerFailed');
        console.error(e);
    }
};
</script>

<style scoped>
.register-card {
    max-width: 470px;
}

.auth-topline {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.subtitle {
    max-width: 34ch;
}

.error-message {
    margin: 0;
    color: #b91c1c;
}
</style>
