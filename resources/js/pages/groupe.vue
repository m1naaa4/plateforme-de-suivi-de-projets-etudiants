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
                    <div class="members-picker">
                        <label
                            v-for="student in students"
                            :key="student.id"
                            class="member-option"
                        >
                            <input
                                v-model="groupForm.users"
                                type="checkbox"
                                :value="student.id"
                            >
                            <span>{{ student.name }}</span>
                        </label>
                    </div>
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
                        <button
                            v-if="!canManageGroups"
                            type="button"
                            class="details-toggle"
                            @click="toggleGroupDetails(group.id)"
                        >
                            {{ expandedGroupId === group.id ? 'Masquer' : 'Details' }}
                        </button>
                        <span v-else class="project-badge">
                            {{ group.project ? group.project.title : 'Sans projet' }}
                        </span>
                    </div>

                    <div v-if="!canManageGroups && expandedGroupId === group.id" class="details-panel">
                        <div class="info-block">
                            <strong>Projet associe</strong>
                            <p class="info-text">
                                {{ group.project ? group.project.title : 'Aucun projet associe.' }}
                            </p>
                        </div>

                        <div class="info-block">
                            <strong>Prof</strong>
                            <p class="info-text">
                                {{ group.project?.teacher ? group.project.teacher.name : 'Non attribue' }}
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

                    <div v-if="canManageGroups" class="card-actions">
                        <button class="edit-button" @click="editGroup(group)">
                            Modifier
                        </button>

                        <button class="delete-button" @click="deleteGroup(group.id)">
                            Supprimer
                        </button>
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
const expandedGroupId = ref(null);

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

const toggleGroupDetails = (groupId) => {
    expandedGroupId.value = expandedGroupId.value === groupId ? null : groupId;
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
}

.group-form select[multiple] {
    min-height: 160px;
}

.members-picker {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
    padding: 0.9rem;
    border: 1px solid #ddd2dd;
    border-radius: 16px;
    background: #ffffff;
    max-height: 260px;
    overflow-y: auto;
    scrollbar-gutter: stable;
}

.member-option {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.7rem 0.8rem;
    border: 1px solid #ece2f0;
    border-radius: 14px;
    background: #faf7fc;
    font-weight: 600;
    color: #5f5360;
    cursor: pointer;
    transition: background-color 160ms ease, border-color 160ms ease, transform 160ms ease;
}

.member-option:hover {
    background: #f5edf5;
    border-color: #e3d6e9;
    transform: translateY(-1px);
}

.member-option input {
    width: 1rem;
    height: 1rem;
    accent-color: #8a5f7d;
}

.members-picker::-webkit-scrollbar {
    width: 10px;
}

.members-picker::-webkit-scrollbar-track {
    background: #f5edf5;
    border-radius: 999px;
}

.members-picker::-webkit-scrollbar-thumb {
    background: #c8a2b8;
    border-radius: 999px;
}

.form-actions,
.card-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.groups-grid {
    align-items: start;
}

.group-card h3 {
    margin: 0;
    font-size: 20px;
    color: #2f2430;
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
    gap: 1rem;
}

@media (max-width: 640px) {
    .form-actions > *,
    .card-actions > * {
        width: 100%;
    }
}
</style>
