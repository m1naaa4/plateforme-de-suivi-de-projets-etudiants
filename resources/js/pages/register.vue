<template>
    <main class="register-page">
        <section class="register-card">
            <p class="eyebrow">Plateforme PFA</p>

            <h1>Créer un compte</h1>

            <p class="subtitle">
                Choisissez le rôle de l'utilisateur : administrateur, enseignant ou étudiant.
            </p>

            <form class="register-form" @submit.prevent="register">
                <label>
                    Nom complet
                    <input v-model="form.name" type="text" required>
                </label>

                <label>
                    Email
                    <input v-model="form.email" type="email" required>
                </label>

                <label>
                    Mot de passe
                    <input v-model="form.password" type="password" required>
                </label>

                <label>
                    Rôle
                    <select v-model="form.role" required>
                        <option value="admin">Administrateur</option>
                        <option value="enseignant">Enseignant</option>
                        <option value="etudiant">Étudiant</option>
                    </select>
                </label>

                <button type="submit">
                    Créer le compte
                </button>

                <p v-if="error" class="error-message">
                    {{ error }}
                </p>

                <p v-if="success" class="success-message">
                    {{ success }}
                </p>
            </form>
        </section>
    </main>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const form = ref({
    name: '',
    email: '',
    password: '',
    role: 'etudiant',
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
                'Accept': 'application/json',
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (!response.ok) {
            error.value = data.message || 'Impossible de créer le compte.';
            return;
        }

        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));

        success.value = 'Compte créé avec succès.';
        router.push('/dashboard');
    } catch (e) {
        error.value = 'Impossible de créer le compte.';
        console.error(e);
    }
};
</script>

<style scoped>
.register-page {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
    background: #f7f4f8;
    font-family: 'Inter', sans-serif;
}

.register-card {
    width: 100%;
    max-width: 460px;
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

.register-form {
    display: grid;
    gap: 16px;
    margin-top: 22px;
}

.register-form label {
    display: grid;
    gap: 8px;
    color: #5f5360;
    font-weight: 600;
}

.register-form input,
.register-form select {
    border: 1px solid #ddd2dd;
    border-radius: 8px;
    padding: 12px;
    font: inherit;
    background: white;
}

.register-form input:focus,
.register-form select:focus {
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

.success-message {
    margin: 0;
    color: #15803d;
}
</style>
