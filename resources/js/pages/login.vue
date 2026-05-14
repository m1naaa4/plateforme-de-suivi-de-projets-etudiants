<template>
    <main class="login-page">
        <section class="login-card">
            <p class="eyebrow">Plateforme PFA</p>

            <h1>Connexion</h1>

            <p class="subtitle">
                Accédez à votre espace de suivi des projets étudiants.
            </p>

            <form class="login-form" @submit.prevent="login">
                <label>
                    Email
                    <input v-model="form.email" type="email" required>
                </label>

                <label>
                    Mot de passe
                    <input v-model="form.password" type="password" required>
                </label>

                <button type="submit">
                    Se connecter
                </button>

                <p v-if="error" class="error-message">
                    {{ error }}
                </p>
                <router-link to="/register">Créer un compte</router-link>

            </form>
        </section>
    </main>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

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
                'Accept': 'application/json',
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (!response.ok) {
            error.value = 'Email ou mot de passe incorrect.';
            return;
        }

        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));

        router.push('/dashboard');
    } catch (e) {
        error.value = 'Impossible de se connecter.';
        console.error(e);
    }
};
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
    background: #f7f4f8;
    font-family: 'Inter', sans-serif;
}

.login-card {
    width: 100%;
    max-width: 420px;
    background: #ffffff;
    border: 1px solid #eee5ee;
    border-radius: 8px;
    padding: 32px;
}

.eyebrow {
    margin: 0 0 8px;
    color: #8a5f7d;
    font-weight: 700;
}

h1 {
    margin: 0;
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    color: #2f2933;
}

.subtitle {
    color: #746776;
    line-height: 1.5;
}

.login-form {
    display: grid;
    gap: 16px;
    margin-top: 22px;
}

.login-form label {
    display: grid;
    gap: 8px;
    color: #5f5360;
    font-weight: 600;
}

.login-form input {
    border: 1px solid #ddd2dd;
    border-radius: 8px;
    padding: 12px;
    font: inherit;
}

.login-form input:focus {
    outline: 2px solid #ead7ea;
    border-color: #9b6c8f;
}

button {
    border: 0;
    border-radius: 8px;
    padding: 13px;
    background: #2f2933;
    color: #ffffff;
    font-weight: 700;
    cursor: pointer;
}

button:hover {
    background: #443947;
}

.error-message {
    margin: 0;
    color: #b91c1c;
}
.register-link {
    color: #8a5f7d;
    text-decoration: none;
    font-weight: 600;
    text-align: center;
}

.register-link:hover {
    text-decoration: underline;
}

</style>
