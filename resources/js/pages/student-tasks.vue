<template>
    <main class="tasks-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">Gestion des taches</p>
                <h1>Mes taches</h1>
                <p class="page-subtitle">
                    Consultez vos taches et mettez a jour leur statut.
                </p>
            </div>
        </header>

        <section class="tasks-list">
            <div class="section-head">
                <h2>Liste de mes taches</h2>
                <span class="count-badge">{{ studentTasks.length }}</span>
            </div>

            <p v-if="loading">Chargement...</p>

            <p v-else-if="studentTasks.length === 0" class="empty-state">
                Aucune tache trouvee.
            </p>

            <div v-else class="tasks-grid">
                <article
                    v-for="task in studentTasks"
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
                            <strong>Date limite :</strong>
                            {{ formatDeadline(task.deadline) }}
                        </p>
                    </div>

                    <div class="task-actions">
                        <label class="status-label">
                            Statut
                            <select
                                :value="task.status"
                                @change="updateTaskStatus(task, $event.target.value)"
                                :disabled="updatingTaskId === task.id"
                            >
                                <option value="a_faire">A faire</option>
                                <option value="en_cours">En cours</option>
                                <option value="termine">Termine</option>
                            </select>
                        </label>
                    </div>
                </article>
            </div>

            <p v-if="formError" class="error-message">
                {{ formError }}
            </p>

            <p v-if="formSuccess" class="success-message">
                {{ formSuccess }}
            </p>
        </section>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const currentUser = ref(JSON.parse(localStorage.getItem('user') || 'null'));
const tasks = ref([]);
const loading = ref(true);
const updatingTaskId = ref(null);

const formError = ref('');
const formSuccess = ref('');

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

const studentTasks = computed(() => {
    return tasks.value.filter((task) => {
        const assignedId = task.assigned_to ?? task.assignedUser?.id ?? task.assigned_user?.id;
        return assignedId === currentUser.value?.id;
    });
});

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

const updateTaskStatus = async (task, status) => {
    formError.value = '';
    formSuccess.value = '';
    updatingTaskId.value = task.id;

    try {
        const response = await fetch(`/api/tasks/${task.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders(),
            },
            body: JSON.stringify({
                title: task.title,
                description: task.description,
                status,
                deadline: task.deadline,
                project_id: task.project_id ?? task.project?.id,
                assigned_to: task.assigned_to ?? task.assignedUser?.id ?? task.assigned_user?.id ?? null,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            formError.value = data.message || 'Impossible de mettre a jour le statut.';
            return;
        }

        task.status = status;
        formSuccess.value = 'Statut mis a jour avec succes.';
    } catch (error) {
        formError.value = 'Impossible de mettre a jour le statut.';
        console.error(error);
    } finally {
        updatingTaskId.value = null;
    }
};

onMounted(async () => {
    const token = localStorage.getItem('token');

    if (!token) {
        router.push('/login');
        return;
    }

    if (currentUser.value?.role !== 'etudiant') {
        router.push('/tasks');
        return;
    }

    try {
        await loadTasks();
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

.tasks-list,
.task-card {
    background: #ffffff;
    border: 1px solid #ece2f0;
    border-radius: 8px;
}

.tasks-list {
    padding: 20px;
    margin-bottom: 24px;
}

.tasks-list h2 {
    margin: 0 0 16px;
    font-size: 22px;
    color: #2f2430;
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
    margin-bottom: 14px;
}

.task-actions {
    display: grid;
    gap: 10px;
}

.status-label {
    display: grid;
    gap: 8px;
    font-weight: 600;
    color: #5f5360;
}

.status-label select {
    border: 1px solid #ddd2dd;
    border-radius: 8px;
    padding: 12px;
    font: inherit;
    background: #ffffff;
}

.status-label select:focus {
    outline: 2px solid #ead7ea;
    border-color: #9b6c8f;
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
    margin-top: 16px;
    color: #b91c1c;
}

.success-message {
    margin-top: 16px;
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
