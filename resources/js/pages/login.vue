<template>
    <main class="login-page">
        <section class="login-card">
            <div class="auth-topline">
                <p class="eyebrow">{{ t('auth.platform') }}</p>
            </div>

            <h1>{{ t('auth.loginTitle') }}</h1>

            <p class="subtitle">
                {{ t('auth.loginSubtitle') }}
            </p>

            <form class="login-form" @submit.prevent="login">
                <label>
                    {{ t('auth.email') }}
                    <input v-model="form.email" type="email" required>
                </label>

                <label>
                    {{ t('auth.password') }}
                    <input v-model="form.password" type="password" required>
                </label>

                <button type="submit">
                    {{ t('auth.loginButton') }}
                </button>

                <p v-if="error" class="error-message">
                    {{ error }}
                </p>

                <div class="auth-footer">
                    <router-link to="/register" class="register-link">
                        {{ t('auth.createAccount') }}
                    </router-link>

                    <LanguageSwitcher />
                </div>
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
    email: 'admin@test.com',
    password: 'password',
});

const error = ref('');

const login = async () => {
    error.value = '';

    try {
        const response = await fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (!response.ok) {
            error.value = t('auth.invalidCredentials');
            return;
        }

        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));

        router.push('/dashboard');
    } catch (e) {
        error.value = t('auth.loginFailed');
        console.error(e);
    }
};
</script>

<style scoped>
.login-card {
    max-width: 440px;
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

.auth-footer {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 1rem;
}

.error-message {
    margin: 0;
    color: #b91c1c;
}

.auth-footer :deep(.language-switcher) {
    min-width: 5.2rem;
    gap: 0.25rem;
}

.auth-footer :deep(.language-switcher-label) {
    display: none;
}

.auth-footer :deep(select) {
    padding: 0.4rem 0.55rem;
    border-radius: 10px;
    font-size: 0.82rem;
    min-height: 2.2rem;
}
</style>
