<template>
    <main class="groups-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">Gestion des groupes</p>
                <h1>Groupes</h1>
                <p class="page-subtitle">
                    <span v-if="canManageGroups">
                        Creer, modifier et associer les groupes aux projets.
                    </span>
                    <span v-else>
                        Consultez votre groupe, ses membres, son projet et les elements lies.
                    </span>
                </p>
            </div>
        </header>

        <section v-if="canManageGroups" class="form-card">
            <h2>{{ isEditing ? 'Modifier le groupe' : 'Creer un groupe' }}</h2>

            <form class="group-form" @submit.prevent="submitGroup">
                <label>
                    Nom du groupe
                    <input v-model="groupForm.name" type="text" required>
                </label>

                <label>
                    Projet associe
                    <select v-model="groupForm.project_id">
                        <option value="">Aucun</option>
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
                    Membres du groupe
                    <select v-model="groupForm.users" multiple size="6">
                        <option
                            v-for="student in students"
                            :key="student.id"
                            :value="student.id"
                        >
                            {{ student.name }}
                        </option>
                    </select>
                </label>

                <div class="form-actions">
                    <button type="submit">
                        {{ isEditing ? 'Enregistrer les modifications' : 'Ajouter le groupe' }}
                    </button>

                    <button
                        v-if="isEditing"
                        type="button"
                        class="secondary-button"
                        @click="resetForm"
                    >
                        Annuler
                    </button>
                </div>

                <p v-if="formError" class="error-message">
                    {{ formError }}
                </p>

                <p v-if="formSuccess" class="success-message">
                    {{ formSuccess }}
                </p>
            </form>
        </section>

        <section class="groups-list">
            <div class="section-head">
                <h2>{{ groupsHeading }}</h2>
                <span class="count-badge">{{ visibleGroups.length }}</span>
            </div>

            <p v-if="loading">Chargement...</p>

            <p v-else-if="visibleGroups.length === 0" class="empty-state">
                Aucun groupe trouve.
            </p>

            <div v-else class="groups-grid">
                <article
                    v-for="group in visibleGroups"
                    :key="group.id"
                    class="group-card"
                >
                    <div class="group-card-header">
                        <h3>{{ group.name }}</h3>
                        <span class="project-badge">
                            {{ group.project ? group.project.title : 'Sans projet' }}
                        </span>
                    </div>

                    <div class="info-block">
                        <strong>Projet associe</strong>
                        <p class="info-text">
                            {{ group.project ? group.project.title : 'Aucun projet associe.' }}
                        </p>
                    </div>

                    <div class="members-block">
                        <strong>Membres</strong>
                        <ul v-if="group.users && group.users.length > 0">
                            <li v-for="member in group.users" :key="member.id">
                                {{ member.name }}
                            </li>
                        </ul>
                        <p v-else class="empty-members">Aucun membre.</p>
                    </div>

                    <div v-if="canManageGroups" class="card-actions">
                        <button class="edit-button" @click="editGroup(group)">
                            Modifier
                        </button>

                        <button class="delete-button" @click="deleteGroup(group.id)">
                            Supprimer
                        </button>
                    </div>

                    <div v-if="!canManageGroups" class="student-details">
                        <div class="info-block">
                            <strong>Taches du groupe</strong>
                            <ul v-if="groupTasks(group).length > 0">
                                <li v-for="task in groupTasks(group)" :key="task.id">
                                    {{ task.title }} - {{ formatTaskStatus(task.status) }}
                                </li>
                            </ul>
                            <p v-else class="empty-members">Aucune tache liee.</p>
                        </div>

                        <div class="info-block">
                            <strong>Livrables du projet</strong>
                            <ul v-if="groupDeliverables(group).length > 0">
                                <li
                                    v-for="deliverable in groupDeliverables(group)"
                                    :key="deliverable.id"
                                >
                                    {{ deliverable.file_name }} - {{ formatDeliverableStatus(deliverable.status) }}
                                </li>
                            </ul>
                            <p v-else class="empty-members">Aucun livrable lie.</p>
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

const router = useRouter();

const currentUser = ref(JSON.parse(localStorage.getItem('user') || 'null'));
const groups = ref([]);
const projects = ref([]);
const students = ref([]);
const tasks = ref([]);
const deliverables = ref([]);
const loading = ref(true);

const formError = ref('');
const formSuccess = ref('');
const editingGroupId = ref(null);

const groupForm = ref({
    name: '',
    project_id: '',
    users: [],
});

const currentRole = computed(() => currentUser.value?.role || 'etudiant');

const canManageGroups = computed(() => {
    return currentRole.value === 'admin' || currentRole.value === 'enseignant';
});

const isEditing = computed(() => editingGroupId.value !== null);

const visibleGroups = computed(() => {
    if (canManageGroups.value) {
        return groups.value;
    }

    return groups.value.filter((group) =>
        group.users?.some((member) => member.id === currentUser.value?.id)
    );
});

const groupsHeading = computed(() => {
    return currentRole.value === 'etudiant' ? 'Mon groupe' : 'Liste des groupes';
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

const loadProjects = async () => {
    const response = await fetch('/api/projects', {
        headers: authHeaders(),
    });

    if (response.status === 401) {
        logout();
        return;
    }

    projects.value = await response.json();
};

const loadStudents = async () => {
    const response = await fetch('/api/users?role=etudiant', {
        headers: authHeaders(),
    });

    if (response.status === 401) {
        logout();
        return;
    }

    students.value = await response.json();
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

const resetForm = () => {
    editingGroupId.value = null;
    groupForm.value = {
        name: '',
        project_id: '',
        users: [],
    };
    formError.value = '';
    formSuccess.value = '';
};

const editGroup = (group) => {
    editingGroupId.value = group.id;
    groupForm.value = {
        name: group.name,
        project_id: group.project?.id ?? group.project_id ?? '',
        users: group.users ? group.users.map((user) => user.id) : [],
    };
    formError.value = '';
    formSuccess.value = '';
};

const submitGroup = async () => {
    formError.value = '';
    formSuccess.value = '';

    const payload = {
        name: groupForm.value.name,
        project_id: groupForm.value.project_id || null,
        users: groupForm.value.users,
    };

    try {
        const isUpdate = isEditing.value;
        const url = isUpdate
            ? `/api/groups/${editingGroupId.value}`
            : '/api/groups';

        const response = await fetch(url, {
            method: isUpdate ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders(),
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok) {
            formError.value = data.message || 'Impossible d enregistrer le groupe.';
            return;
        }

        formSuccess.value = isUpdate
            ? 'Groupe modifie avec succes.'
            : 'Groupe cree avec succes.';

        resetForm();
        formSuccess.value = isUpdate
            ? 'Groupe modifie avec succes.'
            : 'Groupe cree avec succes.';

        await loadGroups();
    } catch (error) {
        formError.value = 'Impossible d enregistrer le groupe.';
        console.error(error);
    }
};

const deleteGroup = async (groupId) => {
    formError.value = '';
    formSuccess.value = '';

    try {
        const response = await fetch(`/api/groups/${groupId}`, {
            method: 'DELETE',
            headers: authHeaders(),
        });

        if (!response.ok) {
            formError.value = 'Impossible de supprimer le groupe.';
            return;
        }

        groups.value = groups.value.filter((group) => group.id !== groupId);

        if (editingGroupId.value === groupId) {
            resetForm();
        }

        formSuccess.value = 'Groupe supprime avec succes.';
    } catch (error) {
        formError.value = 'Impossible de supprimer le groupe.';
        console.error(error);
    }
};

const groupTasks = (group) => {
    const projectId = group.project?.id ?? group.project_id;

    return tasks.value.filter((task) => {
        const taskProjectId = task.project_id ?? task.project?.id;
        return Number(taskProjectId) === Number(projectId);
    });
};

const groupDeliverables = (group) => {
    const projectId = group.project?.id ?? group.project_id;

    return deliverables.value.filter((deliverable) => {
        const deliverableProjectId = deliverable.project_id ?? deliverable.project?.id;
        return Number(deliverableProjectId) === Number(projectId);
    });
};

const formatTaskStatus = (status) => {
    const labels = {
        a_faire: 'A faire',
        en_cours: 'En cours',
        termine: 'Termine',
    };

    return labels[status] || status;
};

const formatDeliverableStatus = (status) => {
    const labels = {
        en_attente: 'En attente',
        valide: 'Valide',
        refuse: 'Refuse',
    };

    return labels[status] || status;
};

onMounted(async () => {
    const token = localStorage.getItem('token');

    if (!token) {
        router.push('/login');
        return;
    }

    try {
        await loadGroups();

        if (canManageGroups.value) {
            await loadProjects();
            await loadStudents();
        } else {
            await loadTasks();
            await loadDeliverables();
        }
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.groups-page {
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
.groups-list,
.group-card {
    background: #ffffff;
    border: 1px solid #ece2f0;
    border-radius: 8px;
}

.form-card,
.groups-list {
    padding: 20px;
    margin-bottom: 24px;
}

.form-card h2,
.groups-list h2 {
    margin: 0 0 16px;
    font-size: 22px;
    color: #2f2430;
}

.group-form {
    display: grid;
    gap: 14px;
}

.group-form label {
    display: grid;
    gap: 8px;
    font-weight: 600;
    color: #5f5360;
}

.group-form input,
.group-form select {
    border: 1px solid #ddd2dd;
    border-radius: 8px;
    padding: 12px;
    font: inherit;
    background: #ffffff;
}

.group-form select[multiple] {
    min-height: 160px;
}

.group-form input:focus,
.group-form select:focus {
    outline: 2px solid #ead7ea;
    border-color: #9b6c8f;
}

.form-actions,
.card-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.group-form button,
.edit-button,
.delete-button,
.secondary-button {
    width: fit-content;
    border: 0;
    border-radius: 8px;
    padding: 12px 16px;
    color: white;
    font-weight: 700;
    cursor: pointer;
}

.group-form button,
.edit-button {
    background: #2f2430;
}

.secondary-button {
    background: #8a7a89;
}

.delete-button {
    background: #b83262;
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

.groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.group-card {
    padding: 20px;
}

.group-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
}

.group-card h3 {
    margin: 0;
    font-size: 20px;
    color: #2f2430;
}

.project-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 6px 11px;
    background: #f5edf5;
    color: #2f2430;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.members-block,
.info-block,
.student-details {
    display: grid;
    gap: 10px;
}

.student-details {
    margin-top: 16px;
}

.members-block strong,
.info-block strong {
    color: #2f2430;
}

.members-block ul,
.info-block ul {
    margin: 0;
    padding-left: 18px;
    color: #6d6170;
}

.info-text,
.empty-members,
.empty-state {
    margin: 0;
    color: #6d6170;
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
    .group-card-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
