<template>
    <main class="users-page">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Gestion des utilisateurs</p>
                    <h1>Utilisateurs</h1>
                    <p class="page-subtitle">
                        Creer des comptes et gerer les roles administrateur, enseignant et etudiant.
                    </p>
                </div>
            </header>

            <section v-if="!isAdmin" class="notice-card">
                <h2>Acces limite</h2>
                <p>Seul l administrateur peut gerer les utilisateurs.</p>
            </section>

            <template v-else>
                <section class="form-card">
                    <h2>Creer un utilisateur</h2>

                    <form class="user-form" @submit.prevent="createUser">
                        <label>
                            Nom complet
                            <input v-model="userForm.name" type="text" required>
                        </label>

                        <label>
                            Email
                            <input v-model="userForm.email" type="email" required>
                        </label>

                        <label>
                            Mot de passe
                            <input v-model="userForm.password" type="password" required>
                        </label>

                        <label>
                            Role
                            <select v-model="userForm.role" required>
                                <option value="admin">Administrateur</option>
                                <option value="enseignant">Enseignant</option>
                                <option value="etudiant">Etudiant</option>
                            </select>
                        </label>

                        <button type="submit">Ajouter l utilisateur</button>

                        <p v-if="formError" class="error-message">
                            {{ formError }}
                        </p>

                        <p v-if="formSuccess" class="success-message">
                            {{ formSuccess }}
                        </p>
                    </form>
                </section>

                <section class="users-list">
                    <div class="section-head">
                        <h2>Liste des utilisateurs</h2>
                        <span class="count-badge">{{ users.length }}</span>
                    </div>

                    <p v-if="loading">Chargement...</p>

                    <p v-else-if="users.length === 0" class="empty-state">
                        Aucun utilisateur trouve.
                    </p>

                    <div v-else class="users-grid">
                        <article
                            v-for="user in users"
                            :key="user.id"
                            class="user-card"
                        >
                            <div class="user-card-header">
                                <div>
                                    <h3>{{ user.name }}</h3>
                                    <p class="user-email">{{ user.email }}</p>
                                </div>

                                <span class="role-badge" :class="`role-${user.role}`">
                                    {{ formatRole(user.role) }}
                                </span>
                            </div>

                            <div class="user-actions">
                                <button
                                    class="delete-button"
                                    @click="deleteUser(user.id)"
                                >
                                    Supprimer
                                </button>
                            </div>
                        </article>
                    </div>
                </section>
            </template>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const currentUser = ref(JSON.parse(localStorage.getItem('user') || 'null'));
const users = ref([]);
const loading = ref(true);

const formError = ref('');
const formSuccess = ref('');

const userForm = ref({
    name: '',
    email: '',
    password: '',
    role: 'etudiant',
});

const isAdmin = computed(() => currentUser.value?.role === 'admin');

const authHeaders = () => {
    const token = localStorage.getItem('token');

    return {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
    };
};

const logout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push('/login');
};

const formatRole = (role) => {
    const labels = {
        admin: 'Administrateur',
        enseignant: 'Enseignant',
        etudiant: 'Etudiant',
    };

    return labels[role] || role;
};

const loadUsers = async () => {
    const response = await fetch('/api/users', {
        headers: authHeaders(),
    });

    if (response.status === 401) {
        logout();
        return;
    }

    users.value = await response.json();
};

const createUser = async () => {
    formError.value = '';
    formSuccess.value = '';

    try {
        const response = await fetch('/api/users', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders(),
            },
            body: JSON.stringify(userForm.value),
        });

        const data = await response.json();

        if (!response.ok) {
            formError.value = data.message || 'Impossible de creer l utilisateur.';
            return;
        }

        formSuccess.value = 'Utilisateur cree avec succes.';
        userForm.value = {
            name: '',
            email: '',
            password: '',
            role: 'etudiant',
        };

        await loadUsers();
    } catch (error) {
        formError.value = 'Impossible de creer l utilisateur.';
        console.error(error);
    }
};

const deleteUser = async (userId) => {
    try {
        const response = await fetch(`/api/users/${userId}`, {
            method: 'DELETE',
            headers: authHeaders(),
        });

        if (!response.ok) {
            return;
        }

        users.value = users.value.filter((user) => user.id !== userId);
    } catch (error) {
        console.error(error);
    }
};

onMounted(async () => {
    const token = localStorage.getItem('token');

    if (!token) {
        router.push('/login');
        return;
    }

    if (!isAdmin.value) {
        loading.value = false;
        return;
    }

    try {
        await loadUsers();
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.users-page {
    min-height: 100vh;
    color: #2f2430;
    font-family: 'Inter', sans-serif;
}

.page-header {
    margin-bottom: 24px;
}

.eyebrow {
    margin: 0 0 8px;
    color: #a85575;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
}

h1 {
    margin: 0;
    font-family: 'Playfair Display', serif;
    font-size: 40px;
    line-height: 1.1;
}

.page-subtitle {
    margin: 8px 0 0;
    color: #7b6b7a;
    font-size: 16px;
    line-height: 1.6;
}

.form-card,
.users-list,
.notice-card,
.user-card {
    background: #ffffff;
    border: 1px solid #ece2f0;
    border-radius: 8px;
}

.form-card,
.users-list,
.notice-card {
    padding: 20px;
    margin-bottom: 24px;
}

.form-card h2,
.users-list h2,
.notice-card h2 {
    margin: 0 0 16px;
    font-size: 22px;
    color: #2f2430;
}

.notice-card p,
.empty-state {
    margin: 0;
    color: #5f5360;
}

.user-form {
    display: grid;
    gap: 14px;
}

.user-form label {
    display: grid;
    gap: 8px;
    font-weight: 600;
    color: #5f5360;
}

.user-form input,
.user-form select {
    border: 1px solid #ddd2dd;
    border-radius: 8px;
    padding: 12px;
    font: inherit;
    background: #ffffff;
}

.user-form input:focus,
.user-form select:focus {
    outline: 2px solid #ead7ea;
    border-color: #9b6c8f;
}

.user-form button,
.delete-button {
    width: fit-content;
    border: 0;
    border-radius: 8px;
    padding: 12px 16px;
    color: #ffffff;
    font-weight: 700;
    cursor: pointer;
}

.user-form button {
    background: #2f2430;
}

.delete-button {
    background: #b83262;
}

.error-message {
    margin: 0;
    color: #b91c1c;
}

.success-message {
    margin: 0;
    color: #15803d;
}

.section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.section-head h2 {
    margin: 0;
}

.count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    border-radius: 999px;
    background: #f5edf5;
    color: #2f2430;
    font-weight: 700;
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.user-card {
    padding: 20px;
}

.user-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
}

.user-card h3 {
    margin: 0 0 6px;
    font-size: 20px;
    color: #2f2430;
}

.user-email {
    margin: 0;
    color: #6d6170;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 6px 11px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.role-admin {
    background: #fde68a;
    color: #92400e;
}

.role-enseignant {
    background: #dbeafe;
    color: #1d4ed8;
}

.role-etudiant {
    background: #e9f8ef;
    color: #237a4b;
}

.user-actions {
    display: flex;
    justify-content: flex-end;
}

@media (max-width: 640px) {
    h1 {
        font-size: 32px;
    }

    .section-head,
    .user-card-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
