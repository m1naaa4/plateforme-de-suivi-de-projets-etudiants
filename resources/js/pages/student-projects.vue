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
                        <button
                            type="button"
                            class="details-toggle"
                            @click="toggleProjectDetails(project.id)"
                        >
                            {{ expandedProjectId === project.id ? 'Masquer' : 'Details' }}
                        </button>
                    </div>

                    <div v-if="expandedProjectId === project.id" class="details-panel">
                        <span class="status-badge" :class="`status-${project.status}`">
                            {{ formatStatus(project.status) }}
                        </span>

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

                        <p class="project-meta">
                            <strong>Date debut :</strong>
                            {{ formatDate(project.start_date) }}
                        </p>

                        <p class="project-meta">
                            <strong>Date limite :</strong>
                            {{ formatDate(project.deadline) }}
                        </p>

                        <div class="progress-block">
                            <div class="progress-head">
                                <strong>Avancement</strong>
                                <span>{{ projectProgress(project) }}%</span>
                            </div>
                            <div class="progress-bar">
                                <span :style="{ width: `${projectProgress(project)}%` }"></span>
                            </div>
                            <p class="progress-note">
                                Calcule a partir des taches terminees.
                            </p>
                        </div>
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
const expandedProjectId = ref(null);

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

const formatDate = (date) => {
    return date || 'Non definie';
};

const toggleProjectDetails = (projectId) => {
    expandedProjectId.value = expandedProjectId.value === projectId ? null : projectId;
};

const projectTasks = (project) => {
    return tasks.value.filter((task) => Number(task.project_id ?? task.project?.id) === Number(project.id));
};

const projectProgress = (project) => {
    const list = projectTasks(project);

    if (list.length === 0) {
        return 0;
    }

    const completed = list.filter((task) => task.status === 'termine').length;
    return Math.round((completed / list.length) * 100);
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
                start_date: project.start_date || null,
                deadline: project.deadline || null,
                progress: project.progress || 0,
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
}

.project-grid {
    align-items: start;
}

.project-card h3 {
    margin: 0;
    font-size: 20px;
    color: #2f2430;
}

.project-meta {
    color: #4b3f4e;
}

.details-toggle {
    background: #f5edf5;
    color: #2f2430;
    box-shadow: none;
    min-height: 2.6rem;
}

.details-toggle:hover {
    background: #ead7ea;
}

.details-panel {
    margin-top: 1rem;
    display: grid;
    gap: 0.9rem;
}

.progress-block {
    margin-top: 14px;
}

.progress-note {
    margin: 0;
    color: #7b6b7a;
    font-size: 0.86rem;
}

@media (max-width: 640px) {
}
</style>
