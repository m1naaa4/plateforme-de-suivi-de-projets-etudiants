<template>
    <main class="projects-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">Gestion des projets</p>
                <h1>Mes projets</h1>
                <p class="page-subtitle">
                    Consultez vos projets et mettez a jour leur avancement.
                </p>
            </div>
        </header>

        <section class="projects-list">
            <div class="section-head">
                <h2>Liste de mes projets</h2>
                <span class="count-badge">{{ studentProjects.length }}</span>
            </div>

            <p v-if="loading">Chargement...</p>

            <p v-else-if="studentProjects.length === 0" class="empty-state">
                Aucun projet trouve.
            </p>

            <div v-else class="project-grid">
                <article
                    v-for="project in studentProjects"
                    :key="project.id"
                    class="project-card"
                >
                    <div class="project-card-header">
                        <h3>{{ project.title }}</h3>
                        <span class="status-badge" :class="`status-${project.status}`">
                            {{ formatStatus(project.status) }}
                        </span>
                    </div>

                    <p class="project-description">
                        {{ project.description || 'Aucune description.' }}
                    </p>

                    <p class="project-meta">
                        <strong>Enseignant :</strong>
                        {{ project.teacher ? project.teacher.name : 'Non attribue' }}
                    </p>

                    <p class="project-meta">
                        <strong>Groupe :</strong>
                        {{ project.group ? project.group.name : 'Aucun groupe associe' }}
                    </p>

                    <div class="project-actions">
                        <label class="status-label">
                            Avancement du projet
                            <select
                                :value="project.status"
                                @change="updateProjectStatus(project, $event.target.value)"
                                :disabled="updatingProjectId === project.id"
                            >
                                <option value="en_attente">En attente</option>
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
const groups = ref([]);
const loading = ref(true);
const updatingProjectId = ref(null);

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

const studentProjects = computed(() => {
    const map = new Map();

    tasks.value.forEach((task) => {
        const assignedId = Number(
            task.assigned_to ?? task.assignedUser?.id ?? task.assigned_user?.id ?? 0
        );

        if (assignedId === Number(currentUser.value?.id) && task.project) {
            map.set(task.project.id, {
                ...task.project,
                group: task.project.group ?? null,
            });
        }
    });

    groups.value.forEach((group) => {
        const isMember = group.users?.some(
            (member) => Number(member.id) === Number(currentUser.value?.id)
        );

        if (isMember && group.project) {
            map.set(group.project.id, {
                ...group.project,
                group: {
                    id: group.id,
                    name: group.name,
                },
            });
        }
    });

    return Array.from(map.values());
});

const formatStatus = (status) => {
    const labels = {
        en_attente: 'En attente',
        en_cours: 'En cours',
        termine: 'Termine',
    };

    return labels[status] || status;
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

const loadGroups = async () => {
    const response = await fetch('/api/groups', {
        headers: authHeaders(),
    });

    if (response.status === 401) {
        logout();
        return;
    }

    groups.value = await response.json();
};

const updateProjectStatus = async (project, status) => {
    formError.value = '';
    formSuccess.value = '';
    updatingProjectId.value = project.id;

    try {
        const response = await fetch(`/api/projects/${project.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders(),
            },
            body: JSON.stringify({
                title: project.title,
                description: project.description,
                teacher_id: project.teacher_id ?? project.teacher?.id ?? null,
                status,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            formError.value = data.message || 'Impossible de mettre a jour le projet.';
            return;
        }

        project.status = status;
        formSuccess.value = 'Statut du projet mis a jour avec succes.';
    } catch (error) {
        formError.value = 'Impossible de mettre a jour le projet.';
        console.error(error);
    } finally {
        updatingProjectId.value = null;
    }
};

onMounted(async () => {
    const token = localStorage.getItem('token');

    if (!token) {
        router.push('/login');
        return;
    }

    if (currentUser.value?.role !== 'etudiant') {
        router.push('/projects');
        return;
    }

    try {
        await loadTasks();
        await loadGroups();
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.projects-page {
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

.projects-list,
.project-card {
    background: #ffffff;
    border: 1px solid #ece2f0;
    border-radius: 8px;
}

.projects-list {
    padding: 20px;
    margin-bottom: 24px;
}

.projects-list h2 {
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

.project-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.project-card {
    padding: 20px;
}

.project-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}

.project-card h3 {
    margin: 0;
    font-size: 20px;
    color: #2f2430;
}

.project-description {
    margin: 0 0 14px;
    color: #6d6170;
    line-height: 1.6;
}

.project-meta,
.empty-state {
    margin: 0 0 10px;
    color: #4b3f4e;
}

.project-actions {
    margin-top: 14px;
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

.status-en_attente {
    background: #fff1f5;
    color: #b83262;
}

.status-en_cours {
    background: #f1e7ff;
    color: #6d3bbd;
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

    .project-card-header,
    .section-head {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
