<template>
    <main class="deliverables-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">Gestion des livrables</p>
                <h1>Mes livrables</h1>
                <p class="page-subtitle">
                    Deposez vos livrables et consultez leur statut.
                </p>
            </div>
        </header>

        <section class="form-card">
            <h2>Deposer un livrable</h2>

            <form class="deliverable-form" @submit.prevent="createDeliverable">
                <label>
                    Projet
                    <select v-model="deliverableForm.project_id" required>
                        <option value="">Choisir un projet</option>
                        <option
                            v-for="project in availableProjects"
                            :key="project.id"
                            :value="project.id"
                        >
                            {{ project.title }}
                        </option>
                    </select>
                </label>

                <label>
                    Tache
                    <select v-model="deliverableForm.task_id">
                        <option value="">Aucune</option>
                        <option
                            v-for="task in availableTasks"
                            :key="task.id"
                            :value="task.id"
                        >
                            {{ task.title }}
                        </option>
                    </select>
                </label>

                <label>
                    Fichier
                    <input ref="fileInput" type="file" @change="handleFileChange" required>
                </label>

                <button type="submit" :disabled="submitting">
                    {{ submitting ? 'Depot en cours...' : 'Deposer le livrable' }}
                </button>

                <p v-if="formError" class="error-message">
                    {{ formError }}
                </p>

                <p v-if="formSuccess" class="success-message">
                    {{ formSuccess }}
                </p>
            </form>
        </section>

        <section class="deliverables-list">
            <div class="section-head">
                <h2>Liste de mes livrables</h2>
                <span class="count-badge">{{ studentDeliverables.length }}</span>
            </div>

            <p v-if="loading">Chargement...</p>

            <p v-else-if="studentDeliverables.length === 0" class="empty-state">
                Aucun livrable trouve.
            </p>

            <div v-else class="deliverables-grid">
                <article
                    v-for="deliverable in studentDeliverables"
                    :key="deliverable.id"
                    class="deliverable-card"
                >
                    <div class="deliverable-card-header">
                        <div>
                            <h3>{{ deliverable.file_name }}</h3>
                            <p class="deliverable-project">
                                {{ deliverable.project ? deliverable.project.title : 'Projet non defini' }}
                            </p>
                        </div>

                        <span class="status-badge" :class="`status-${deliverable.status}`">
                            {{ formatStatus(deliverable.status) }}
                        </span>
                    </div>

                    <div class="deliverable-meta">
                        <p>
                            <strong>Tache :</strong>
                            {{ deliverable.task ? deliverable.task.title : 'Aucune tache' }}
                        </p>

                        <p>
                            <strong>Commentaire enseignant :</strong>
                            {{ deliverable.teacher_comment || 'Aucun commentaire' }}
                        </p>

                        <p>
                            <strong>Fichier :</strong>
                            <a
                                :href="`/storage/${deliverable.file_path}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="file-link"
                            >
                                Ouvrir
                            </a>
                        </p>
                    </div>
                </article>
            </div>
        </section>
    </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const currentUser = ref(JSON.parse(localStorage.getItem('user') || 'null'));
const deliverables = ref([]);
const tasks = ref([]);
const loading = ref(true);
const submitting = ref(false);

const fileInput = ref(null);
const selectedFile = ref(null);
const formError = ref('');
const formSuccess = ref('');

const deliverableForm = ref({
    project_id: '',
    task_id: '',
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

const studentTasks = computed(() => {
    return tasks.value.filter((task) => {
        const assignedId = Number(
            task.assigned_to ?? task.assignedUser?.id ?? task.assigned_user?.id ?? 0
        );

        return assignedId === Number(currentUser.value?.id);
    });
});

const availableProjects = computed(() => {
    const map = new Map();

    studentTasks.value.forEach((task) => {
        const project = task.project;
        if (project && !map.has(project.id)) {
            map.set(project.id, project);
        }
    });

    return Array.from(map.values());
});

const availableTasks = computed(() => {
    if (!deliverableForm.value.project_id) {
        return studentTasks.value;
    }

    return studentTasks.value.filter((task) => {
        const projectId = Number(task.project_id ?? task.project?.id ?? 0);
        return projectId === Number(deliverableForm.value.project_id);
    });
});

const studentDeliverables = computed(() => {
    return deliverables.value.filter((deliverable) => {
        const submittedBy = Number(
            deliverable.submitted_by ?? deliverable.submitter?.id ?? 0
        );

        return submittedBy === Number(currentUser.value?.id);
    });
});

watch(
    () => deliverableForm.value.project_id,
    () => {
        const exists = availableTasks.value.some(
            (task) => Number(task.id) === Number(deliverableForm.value.task_id)
        );

        if (!exists) {
            deliverableForm.value.task_id = '';
        }
    }
);

const formatStatus = (status) => {
    const labels = {
        en_attente: 'En attente',
        valide: 'Valide',
        refuse: 'Refuse',
    };

    return labels[status] || status;
};

const handleFileChange = (event) => {
    selectedFile.value = event.target.files?.[0] || null;
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

const loadDeliverables = async () => {
    const response = await fetch('/api/deliverables', {
        headers: authHeaders(),
    });

    if (response.status === 401) {
        logout();
        return;
    }

    deliverables.value = await response.json();
};

const createDeliverable = async () => {
    formError.value = '';
    formSuccess.value = '';

    if (!deliverableForm.value.project_id) {
        formError.value = 'Veuillez choisir un projet.';
        return;
    }

    if (!selectedFile.value) {
        formError.value = 'Veuillez choisir un fichier.';
        return;
    }

    submitting.value = true;

    try {
        const formData = new FormData();
        formData.append('project_id', deliverableForm.value.project_id);
        formData.append('submitted_by', currentUser.value.id);
        formData.append('file', selectedFile.value);

        if (deliverableForm.value.task_id) {
            formData.append('task_id', deliverableForm.value.task_id);
        }

        const response = await fetch('/api/deliverables', {
            method: 'POST',
            headers: {
                Authorization: `Bearer ${localStorage.getItem('token')}`,
                Accept: 'application/json',
            },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            formError.value = data.message || 'Impossible de deposer le livrable.';
            return;
        }

        formSuccess.value = 'Livrable depose avec succes.';
        deliverableForm.value.project_id = '';
        deliverableForm.value.task_id = '';
        selectedFile.value = null;

        if (fileInput.value) {
            fileInput.value.value = '';
        }

        await loadDeliverables();
    } catch (error) {
        formError.value = 'Impossible de deposer le livrable.';
        console.error(error);
    } finally {
        submitting.value = false;
    }
};

onMounted(async () => {
    const token = localStorage.getItem('token');

    if (!token) {
        router.push('/login');
        return;
    }

    if (currentUser.value?.role !== 'etudiant') {
        router.push('/deliverables');
        return;
    }

    try {
        await loadTasks();
        await loadDeliverables();
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.deliverables-page {
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
.deliverables-list,
.deliverable-card {
    background: #ffffff;
    border: 1px solid #ece2f0;
    border-radius: 8px;
}

.form-card,
.deliverables-list {
    padding: 20px;
    margin-bottom: 24px;
}

.form-card h2,
.deliverables-list h2 {
    margin: 0 0 16px;
    font-size: 22px;
    color: #2f2430;
}

.deliverable-form {
    display: grid;
    gap: 14px;
}

.deliverable-form label {
    display: grid;
    gap: 8px;
    font-weight: 600;
    color: #5f5360;
}

.deliverable-form input,
.deliverable-form select {
    border: 1px solid #ddd2dd;
    border-radius: 8px;
    padding: 12px;
    font: inherit;
    background: #ffffff;
}

.deliverable-form input:focus,
.deliverable-form select:focus {
    outline: 2px solid #ead7ea;
    border-color: #9b6c8f;
}

.deliverable-form button {
    width: fit-content;
    border: 0;
    border-radius: 8px;
    padding: 12px 16px;
    background: #2f2430;
    color: white;
    font-weight: 700;
    cursor: pointer;
}

.deliverable-form button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
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

.deliverables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.deliverable-card {
    padding: 20px;
}

.deliverable-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}

.deliverable-card h3 {
    margin: 0 0 6px;
    font-size: 20px;
    color: #2f2430;
}

.deliverable-project,
.deliverable-meta p,
.empty-state {
    margin: 0;
    color: #6d6170;
}

.deliverable-project {
    font-weight: 600;
}

.deliverable-meta {
    display: grid;
    gap: 10px;
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

.status-en_attente {
    background: #fff1f5;
    color: #b83262;
}

.status-valide {
    background: #e9f8ef;
    color: #237a4b;
}

.status-refuse {
    background: #fde2e2;
    color: #b91c1c;
}

.file-link {
    color: #8a5f7d;
    text-decoration: none;
    font-weight: 600;
}

.file-link:hover {
    text-decoration: underline;
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
    .deliverable-card-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
