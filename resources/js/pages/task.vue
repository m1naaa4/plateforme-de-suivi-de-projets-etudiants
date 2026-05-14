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
                        Etudiant assigne
                        <select v-model="taskForm.assigned_to">
                            <option value="">Aucun</option>
                            <option
                                v-for="student in students"
                                :key="student.id"
                                :value="student.id"
                            >
                                {{ student.name }}
                            </option>
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
const students = ref([]);
const loading = ref(true);

const formError = ref('');
const formSuccess = ref('');

const taskForm = ref({
    project_id: '',
    title: '',
    description: '',
    status: 'a_faire',
    assigned_to: '',
    deadline: '',
});

const currentRole = computed(() => currentUser.value?.role || 'etudiant');

const canManageTasks = computed(() => {
    return currentRole.value === 'admin' || currentRole.value === 'enseignant';
});

const visibleTasks = computed(() => {
    if (currentRole.value === 'admin' || currentRole.value === 'enseignant') {
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

const loadStudents = async () => {
    const response = await fetch('/api/users?role=etudiant', {
        headers: authHeaders(),
    });

    students.value = await response.json();
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
                assigned_to: taskForm.value.assigned_to || null,
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
            assigned_to: '',
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

    try {
        await loadTasks();

        if (canManageTasks.value) {
            await loadProjects();
            await loadStudents();
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
.tasks-list,
.task-card {
    background: #ffffff;
    border: 1px solid #ece2f0;
    border-radius: 8px;
}

.form-card,
.tasks-list {
    padding: 20px;
    margin-bottom: 24px;
}

.form-card h2,
.tasks-list h2 {
    margin: 0 0 16px;
    font-size: 22px;
    color: #2f2430;
}

.task-form {
    display: grid;
    gap: 14px;
}

.task-form label {
    display: grid;
    gap: 8px;
    font-weight: 600;
    color: #5f5360;
}

.task-form input,
.task-form textarea,
.task-form select {
    border: 1px solid #ddd2dd;
    border-radius: 8px;
    padding: 12px;
    font: inherit;
    background: #ffffff;
}

.task-form input:focus,
.task-form textarea:focus,
.task-form select:focus {
    outline: 2px solid #ead7ea;
    border-color: #9b6c8f;
}

.task-form button {
    width: fit-content;
    border: 0;
    border-radius: 8px;
    padding: 12px 16px;
    background: #2f2430;
    color: white;
    font-weight: 700;
    cursor: pointer;
}

.section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
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

.tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.task-card {
    padding: 20px;
}

.task-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}

.task-card h3 {
    margin: 0 0 6px;
    font-size: 20px;
    color: #2f2430;
}

.task-project,
.task-description,
.task-meta p,
.empty-state {
    margin: 0;
    color: #6d6170;
}

.task-project {
    font-weight: 600;
}

.task-description {
    margin-bottom: 14px;
    line-height: 1.6;
}

.task-meta {
    display: grid;
    gap: 8px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 6px 11px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.status-a_faire {
    background: #fef3c7;
    color: #92400e;
}

.status-en_cours {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-termine {
    background: #e9f8ef;
    color: #237a4b;
}

.error-message {
    margin: 0;
    color: #b91c1c;
}

.success-message {
    margin: 0;
    color: #15803d;
}

@media (max-width: 640px) {
    h1 {
        font-size: 32px;
    }

    .section-head,
    .task-card-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
