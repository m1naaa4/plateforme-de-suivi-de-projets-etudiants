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

                    <form class="user-form" autocomplete="off" @submit.prevent="createUser">
                        <label>
                            Nom complet
                            <input v-model="userForm.name" type="text" autocomplete="off" required>
                        </label>

                        <label>
                            Email
                            <input v-model="userForm.email" type="email" autocomplete="off" required>
                        </label>

                        <label>
                            Mot de passe
                            <input v-model="userForm.password" type="password" autocomplete="new-password" required>
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
                                <select
                                    :value="user.role"
                                    class="role-select"
                                    @change="updateUserRole(user, $event.target.value)"
                                >
                                    <option value="admin">Administrateur</option>
                                    <option value="enseignant">Enseignant</option>
                                    <option value="etudiant">Etudiant</option>
                                </select>

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

const updateUserRole = async (user, role) => {
    formError.value = '';
    formSuccess.value = '';

    try {
        const response = await fetch(`/api/users/${user.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders(),
            },
            body: JSON.stringify({
                role,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            formError.value = data.message || 'Impossible de modifier le role.';
            return;
        }

        user.role = data.role;
        formSuccess.value = 'Role modifie avec succes.';
    } catch (error) {
        formError.value = 'Impossible de modifier le role.';
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
}

.users-grid {
    align-items: start;
}

.user-card h3 {
    margin: 0 0 6px;
    font-size: 20px;
    color: #2f2430;
}

.user-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

@media (max-width: 640px) {
    .user-form button,
    .role-select,
    .delete-button {
        width: 100%;
    }
}
</style>
