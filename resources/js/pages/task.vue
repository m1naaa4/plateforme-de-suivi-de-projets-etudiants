<template>
    <main class="tasks-page">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Gestion des taches</p>
                    <h1>Taches</h1>
                    <p class="page-subtitle">
                        Creer, attribuer et suivre les taches des projets.
                    </p>
                </div>
            </header>

            <section v-if="canManageTasks" class="form-card">
                <h2>Creer une tache</h2>

                <form class="task-form" @submit.prevent="createTask">
                    <label>
                        Projet
                        <select v-model="taskForm.project_id" required>
                            <option value="">Choisir un projet</option>
                            <option
                                v-for="project in projects"
                                :key="project.id"
                                :value="project.id"
                            >
                                {{ project.title }}
                            </option>
                        </select>
                    </label>

                    <label>
                        Titre
                        <input v-model="taskForm.title" type="text" required>
                    </label>

                    <label>
                        Description
                        <textarea v-model="taskForm.description" rows="4"></textarea>
                    </label>

                    <label>
                        Statut
                        <select v-model="taskForm.status">
                            <option value="a_faire">A faire</option>
                            <option value="en_cours">En cours</option>
                            <option value="termine">Termine</option>
                        </select>
                    </label>

                    <label>
                        Date limite
                        <input v-model="taskForm.deadline" type="date">
                    </label>

                    <button type="submit">Ajouter la tache</button>

                    <p v-if="formError" class="error-message">
                        {{ formError }}
                    </p>

                    <p v-if="formSuccess" class="success-message">
                        {{ formSuccess }}
                    </p>
                </form>
            </section>

            <section class="tasks-list">
                <div class="section-head">
                    <h2>{{ tasksHeading }}</h2>
                    <span class="count-badge">{{ visibleTasks.length }}</span>
                </div>

                <p v-if="loading">Chargement...</p>

                <p v-else-if="visibleTasks.length === 0" class="empty-state">
                    Aucune tache trouvee.
                </p>

                <div v-else class="tasks-grid">
                    <article
                        v-for="task in visibleTasks"
                        :key="task.id"
                        class="task-card"
                    >
                        <div class="task-card-header">
                            <div>
                                <h3>{{ task.title }}</h3>
                                <p class="task-project">
                                    {{ task.project ? task.project.title : 'Projet non defini' }}
                                </p>
                            </div>

                            <span class="status-badge" :class="`status-${task.status}`">
                                {{ formatStatus(task.status) }}
                            </span>
                        </div>

                        <p class="task-description">
                            {{ task.description || 'Aucune description.' }}
                        </p>

                        <div class="task-meta">
                            <p>
                                <strong>Assigne a :</strong>
                                {{ task.assigned_user ? task.assigned_user.name : task.assignedUser ? task.assignedUser.name : 'Non assigne' }}
                            </p>
                            <p>
                                <strong>Date limite :</strong>
                                {{ formatDeadline(task.deadline) }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const currentUser = ref(JSON.parse(localStorage.getItem('user') || 'null'));
const tasks = ref([]);
const projects = ref([]);
const loading = ref(true);

const formError = ref('');
const formSuccess = ref('');

const taskForm = ref({
    project_id: '',
    title: '',
    description: '',
    status: 'a_faire',
    deadline: '',
});

const currentRole = computed(() => currentUser.value?.role || 'etudiant');

const canManageTasks = computed(() => {
    return currentRole.value === 'enseignant';
});

const visibleTasks = computed(() => {
    if (currentRole.value === 'enseignant') {
        return tasks.value;
    }

    return tasks.value.filter((task) => {
        const assignedId = task.assigned_to ?? task.assignedUser?.id ?? task.assigned_user?.id;
        return assignedId === currentUser.value?.id;
    });
});

const tasksHeading = computed(() => {
    return currentRole.value === 'etudiant' ? 'Mes taches' : 'Liste des taches';
});

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

const formatStatus = (status) => {
    const labels = {
        a_faire: 'A faire',
        en_cours: 'En cours',
        termine: 'Termine',
    };

    return labels[status] || status;
};

const formatDeadline = (deadline) => {
    return deadline || 'Non definie';
};

const loadTasks = async () => {
    const response = await fetch('/api/tasks', {
        headers: authHeaders(),
    });

    if (response.status === 401) {
        logout();
        return;
    }

    tasks.value = await response.json();
};

const loadProjects = async () => {
    const response = await fetch('/api/projects', {
        headers: authHeaders(),
    });

    projects.value = await response.json();
};

const createTask = async () => {
    formError.value = '';
    formSuccess.value = '';

    try {
        const response = await fetch('/api/tasks', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders(),
            },
            body: JSON.stringify({
                project_id: taskForm.value.project_id,
                title: taskForm.value.title,
                description: taskForm.value.description,
                status: taskForm.value.status,
                deadline: taskForm.value.deadline || null,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            formError.value = data.message || 'Impossible de creer la tache.';
            return;
        }

        formSuccess.value = 'Tache creee avec succes.';
        taskForm.value = {
            project_id: '',
            title: '',
            description: '',
            status: 'a_faire',
            deadline: '',
        };

        await loadTasks();
    } catch (error) {
        formError.value = 'Impossible de creer la tache.';
        console.error(error);
    }
};

onMounted(async () => {
    const token = localStorage.getItem('token');

    if (!token) {
        router.push('/login');
        return;
    }

    if (currentRole.value === 'admin') {
        router.push('/dashboard');
        return;
    }

    try {
        await loadTasks();

        if (canManageTasks.value) {
            await loadProjects();
        }
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.tasks-page {
    min-height: 100vh;
}

.tasks-grid {
    align-items: start;
}

.task-card h3 {
    margin: 0 0 6px;
    font-size: 20px;
    color: #2f2430;
}

.task-project {
    font-weight: 600;
}

@media (max-width: 640px) {
    .task-form button {
        width: 100%;
    }
}
</style>
