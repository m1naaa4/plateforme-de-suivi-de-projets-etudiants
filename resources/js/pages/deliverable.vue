<template>
    <main class="deliverables-page">
        <header class="page-header">
            <div>
                <p class="eyebrow">Gestion des livrables</p>
                <h1>Livrables</h1>
                <p class="page-subtitle">
                    Consultez, validez ou refusez les livrables des etudiants.
                </p>
            </div>
        </header>

        <section class="deliverables-list">
            <div class="section-head">
                <h2>{{ pageHeading }}</h2>
                <span class="count-badge">{{ visibleDeliverables.length }}</span>
            </div>

            <p v-if="loading">Chargement...</p>

            <p v-else-if="visibleDeliverables.length === 0" class="empty-state">
                Aucun livrable trouve.
            </p>

            <div v-else class="deliverables-grid">
                <article
                    v-for="deliverable in visibleDeliverables"
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
                            <strong>Etudiant :</strong>
                            {{ deliverable.submitter ? deliverable.submitter.name : 'Non defini' }}
                        </p>

                        <p>
                            <strong>Commentaire :</strong>
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

                    <div v-if="canReviewDeliverables" class="review-form">
                        <label>
                            Statut
                            <select v-model="reviewForms[deliverable.id].status">
                                <option value="en_attente">En attente</option>
                                <option value="valide">Valide</option>
                                <option value="refuse">Refuse</option>
                            </select>
                        </label>

                        <label>
                            Commentaire enseignant
                            <textarea
                                v-model="reviewForms[deliverable.id].teacher_comment"
                                rows="4"
                                placeholder="Ajouter un commentaire"
                            ></textarea>
                        </label>

                        <button
                            type="button"
                            @click="updateDeliverable(deliverable.id)"
                            :disabled="updatingDeliverableId === deliverable.id"
                        >
                            {{
                                updatingDeliverableId === deliverable.id
                                    ? 'Mise a jour...'
                                    : 'Enregistrer'
                            }}
                        </button>
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
const deliverables = ref([]);
const loading = ref(true);
const updatingDeliverableId = ref(null);

const formError = ref('');
const formSuccess = ref('');
const reviewForms = ref({});

const currentRole = computed(() => currentUser.value?.role || 'etudiant');

const canReviewDeliverables = computed(() => {
    return currentRole.value === 'admin' || currentRole.value === 'enseignant';
});

const visibleDeliverables = computed(() => {
    return deliverables.value;
});

const pageHeading = computed(() => {
    if (currentRole.value === 'admin') {
        return 'Liste des livrables';
    }

    if (currentRole.value === 'enseignant') {
        return 'Livrables a verifier';
    }

    return 'Mes livrables';
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
        en_attente: 'En attente',
        valide: 'Valide',
        refuse: 'Refuse',
    };

    return labels[status] || status;
};

const initialiseReviewForms = () => {
    const forms = {};

    deliverables.value.forEach((deliverable) => {
        forms[deliverable.id] = {
            status: deliverable.status || 'en_attente',
            teacher_comment: deliverable.teacher_comment || '',
        };
    });

    reviewForms.value = forms;
};

const loadDeliverables = async () => {
    const response = await fetch('/api/deliverables', {
        headers: authHeaders(),
    });

    if (response.status === 401) {
        logout();
        return;
    }

    const data = await response.json();
    deliverables.value = Array.isArray(data) ? data : [];
    initialiseReviewForms();
};

const updateDeliverable = async (deliverableId) => {
    formError.value = '';
    formSuccess.value = '';
    updatingDeliverableId.value = deliverableId;

    try {
        const response = await fetch(`/api/deliverables/${deliverableId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders(),
            },
            body: JSON.stringify({
                status: reviewForms.value[deliverableId].status,
                teacher_comment: reviewForms.value[deliverableId].teacher_comment,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            formError.value = data.message || 'Impossible de mettre a jour le livrable.';
            return;
        }

        await loadDeliverables();
        formSuccess.value = 'Livrable mis a jour avec succes.';
    } catch (error) {
        formError.value = 'Impossible de mettre a jour le livrable.';
        console.error(error);
    } finally {
        updatingDeliverableId.value = null;
    }
};

onMounted(async () => {
    const token = localStorage.getItem('token');

    if (!token) {
        router.push('/login');
        return;
    }

    try {
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

.deliverables-list,
.deliverable-card {
    background: #ffffff;
    border: 1px solid #ece2f0;
    border-radius: 8px;
}

.deliverables-list {
    padding: 20px;
    margin-bottom: 24px;
}

.deliverables-list h2 {
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

.deliverables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
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
    margin-bottom: 16px;
}

.review-form {
    display: grid;
    gap: 12px;
}

.review-form label {
    display: grid;
    gap: 8px;
    font-weight: 600;
    color: #5f5360;
}

.review-form select,
.review-form textarea {
    border: 1px solid #ddd2dd;
    border-radius: 8px;
    padding: 12px;
    font: inherit;
    background: #ffffff;
}

.review-form select:focus,
.review-form textarea:focus {
    outline: 2px solid #ead7ea;
    border-color: #9b6c8f;
}

.review-form button {
    width: fit-content;
    border: 0;
    border-radius: 8px;
    padding: 12px 16px;
    background: #2f2430;
    color: white;
    font-weight: 700;
    cursor: pointer;
}

.review-form button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
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
    .deliverable-card-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
