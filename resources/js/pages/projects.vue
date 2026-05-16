<template>
    <main class="projects-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">{{ t('projects.eyebrow') }}</p>
                <h1>{{ t('projects.heading') }}</h1>
                <p class="page-subtitle">{{ t('projects.subtitle') }}</p>
            </div>
        </header>

        <section v-if="canManageProjects" class="form-card">
            <h2>{{ t('projects.formTitle') }}</h2>

            <form class="project-form" @submit.prevent="createProject">
                <label>
                    {{ t('projects.title') }}
                    <input v-model="projectForm.title" type="text" required>
                </label>

                <label>
                    {{ t('projects.description') }}
                    <textarea v-model="projectForm.description" rows="4"></textarea>
                </label>

                <label>
                    {{ t('projects.status') }}
                    <select v-model="projectForm.status">
                        <option value="en_attente">{{ t('projects.statusLabels.en_attente') }}</option>
                        <option value="en_cours">{{ t('projects.statusLabels.en_cours') }}</option>
                        <option value="termine">{{ t('projects.statusLabels.termine') }}</option>
                    </select>
                </label>

                <label>
                    {{ t('projects.startDate') }}
                    <input v-model="projectForm.start_date" type="date">
                </label>

                <label>
                    {{ t('projects.deadline') }}
                    <input v-model="projectForm.deadline" type="date">
                </label>

                <label v-if="isAdmin">
                    {{ t('projects.teacher') }}
                    <select v-model="projectForm.teacher_id">
                        <option value="">{{ t('common.none') }}</option>
                        <option
                            v-for="teacher in teachers"
                            :key="teacher.id"
                            :value="teacher.id"
                        >
                            {{ teacher.name }}
                        </option>
                    </select>
                </label>

                <p v-else class="project-meta">
                    {{ t('projects.teacherAuto') }}
                </p>

                <button type="submit">{{ t('projects.addButton') }}</button>

                <p v-if="projectError" class="error-message">
                    {{ projectError }}
                </p>

                <p v-if="projectSuccess" class="success-message">
                    {{ projectSuccess }}
                </p>
            </form>
            </section>

        <section class="projects-list">
            <h2>{{ t('projects.listTitle') }}</h2>

            <p v-if="loading">{{ t('common.loading') }}</p>

            <p v-else-if="projects.length === 0" class="empty-state">
                {{ t('projects.empty') }}
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

                        <p class="project-description">{{ project.description || t('projects.description') }}</p>

                        <p class="project-meta">
                            <strong>{{ t('projects.teacher') }} :</strong>
                            {{ project.teacher ? project.teacher.name : t('studentProjects.noTeacher') }}
                        </p>

                        <p class="project-meta">
                            <strong>{{ t('projects.startDate') }} :</strong>
                            {{ formatDate(project.start_date) }}
                        </p>

                        <p class="project-meta">
                            <strong>{{ t('projects.deadline') }} :</strong>
                            {{ formatDate(project.deadline) }}
                        </p>

                        <div class="progress-block">
                            <div class="progress-head">
                                <strong>{{ t('projects.progress') }}</strong>
                                <span>{{ project.progress || 0 }}%</span>
                            </div>
                            <div class="progress-bar">
                                <span :style="{ width: `${project.progress || 0}%` }"></span>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { t } from '../i18n';

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
    start_date: '',
    deadline: '',
});

const currentRole = computed(() => currentUser.value?.role || 'etudiant');

const isAdmin = computed(() => currentRole.value === 'admin');

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
    return t(`projects.statusLabels.${status}`) || status;
};

const formatDate = (date) => {
    return date || t('studentProjects.noDate');
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
                teacher_id: isAdmin.value ? projectForm.value.teacher_id || null : null,
                start_date: projectForm.value.start_date || null,
                deadline: projectForm.value.deadline || null,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            projectError.value = data.message || t('projects.failedCreate');
            return;
        }

        projectSuccess.value = t('projects.created');

        projectForm.value.title = '';
        projectForm.value.description = '';
        projectForm.value.status = 'en_attente';
        projectForm.value.teacher_id = '';
        projectForm.value.start_date = '';
        projectForm.value.deadline = '';

        await loadProjects();
    } catch (error) {
        projectError.value = t('projects.failedCreate');
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

.progress-block {
    margin-top: 14px;
}

@media (max-width: 640px) {
    .project-form button {
        width: 100%;
    }
}
</style>
