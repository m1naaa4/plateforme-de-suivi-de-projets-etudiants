<template>
    <main class="projects-page">
            <header class="page-header">
                <div>
                    <p class="eyebrow">Gestion des projets</p>
                    <h1>Projets</h1>
                    <p class="page-subtitle">Creer, consulter et attribuer un enseignant encadrant aux projets.</p>
                </div>
            </header>

            <section v-if="canManageProjects" class="form-card">
                <h2>Creer un projet</h2>

                <form class="project-form" @submit.prevent="createProject">
                    <label>
                        Titre
                        <input v-model="projectForm.title" type="text" required>
                    </label>

                    <label>
                        Description
                        <textarea v-model="projectForm.description" rows="4"></textarea>
                    </label>

                    <label>
                        Statut
                        <select v-model="projectForm.status">
                            <option value="en_attente">En attente</option>
                            <option value="en_cours">En cours</option>
                            <option value="termine">Termine</option>
                        </select>
                    </label>

                    <label>
                        Enseignant encadrant
                        <select v-model="projectForm.teacher_id">
                            <option value="">Aucun</option>
                            <option
                                v-for="teacher in teachers"
                                :key="teacher.id"
                                :value="teacher.id"
                            >
                                {{ teacher.name }}
                            </option>
                        </select>
                    </label>

                    <button type="submit">Ajouter le projet</button>

                    <p v-if="projectError" class="error-message">
                        {{ projectError }}
                    </p>

                    <p v-if="projectSuccess" class="success-message">
                        {{ projectSuccess }}
                    </p>
                </form>
            </section>

            <section class="projects-list">
                <h2>Liste des projets</h2>

                <p v-if="loading">Chargement...</p>

                <p v-else-if="projects.length === 0" class="empty-state">
                    Aucun projet trouve.
                </p>

                <div v-else class="project-grid">
                    <article
                        v-for="project in projects"
                        :key="project.id"
                        class="project-card"
                    >
                        <div class="project-card-header">
                            <h3>{{ project.title }}</h3>
                            <span class="status-badge" :class="`status-${project.status}`">
                                {{ formatStatus(project.status) }}
                            </span>
                        </div>

                        <p class="project-description">{{ project.description || 'Aucune description.' }}</p>

                        <p class="project-meta">
                            <strong>Enseignant :</strong>
                            {{ project.teacher ? project.teacher.name : 'Non attribue' }}
                        </p>
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
const projects = ref([]);
const teachers = ref([]);
const loading = ref(true);

const projectError = ref('');
const projectSuccess = ref('');

const projectForm = ref({
    title: '',
    description: '',
    status: 'en_attente',
    teacher_id: '',
});

const canManageProjects = computed(() => {
    return currentUser.value?.role === 'admin' || currentUser.value?.role === 'enseignant';
});

const authHeaders = () => {
    const token = localStorage.getItem('token');

    return {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
    };
};

const formatStatus = (status) => {
    const labels = {
        en_attente: 'En attente',
        en_cours: 'En cours',
        termine: 'Termine',
    };

    return labels[status] || status;
};

const loadProjects = async () => {
    const response = await fetch('/api/projects', {
        headers: authHeaders(),
    });

    projects.value = await response.json();
};

const loadTeachers = async () => {
    const response = await fetch('/api/users?role=enseignant', {
        headers: authHeaders(),
    });

    teachers.value = await response.json();
};

const createProject = async () => {
    projectError.value = '';
    projectSuccess.value = '';

    try {
        const response = await fetch('/api/projects', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders(),
            },
            body: JSON.stringify({
                title: projectForm.value.title,
                description: projectForm.value.description,
                status: projectForm.value.status,
                teacher_id: projectForm.value.teacher_id || null,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            projectError.value = data.message || 'Impossible de creer le projet.';
            return;
        }

        projectSuccess.value = 'Projet cree avec succes.';

        projectForm.value.title = '';
        projectForm.value.description = '';
        projectForm.value.status = 'en_attente';
        projectForm.value.teacher_id = '';

        await loadProjects();
    } catch (error) {
        projectError.value = 'Impossible de creer le projet.';
        console.error(error);
    }
};

onMounted(async () => {
    const token = localStorage.getItem('token');

    if (!token) {
        router.push('/login');
        return;
    }

    await loadProjects();

    if (canManageProjects.value) {
        await loadTeachers();
    }

    loading.value = false;
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

.form-card,
.projects-list,
.project-card {
    background: #ffffff;
    border: 1px solid #ece2f0;
    border-radius: 8px;
}

.form-card,
.projects-list {
    padding: 20px;
    margin-bottom: 24px;
}

.form-card h2,
.projects-list h2 {
    margin: 0 0 16px;
    font-size: 22px;
    color: #2f2430;
}

.project-form {
    display: grid;
    gap: 14px;
}

.project-form label {
    display: grid;
    gap: 8px;
    font-weight: 600;
    color: #5f5360;
}

.project-form input,
.project-form textarea,
.project-form select {
    border: 1px solid #ddd2dd;
    border-radius: 8px;
    padding: 12px;
    font: inherit;
    background: #ffffff;
}

.project-form input:focus,
.project-form textarea:focus,
.project-form select:focus {
    outline: 2px solid #ead7ea;
    border-color: #9b6c8f;
}

.project-form button {
    width: fit-content;
    border: 0;
    border-radius: 8px;
    padding: 12px 16px;
    background: #2f2430;
    color: white;
    font-weight: 700;
    cursor: pointer;
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
    margin: 0;
    color: #4b3f4e;
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

    .project-card-header {
        flex-direction: column;
    }
}
</style>
