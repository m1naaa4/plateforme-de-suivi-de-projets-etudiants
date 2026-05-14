<template>
    <main class="dashboard-page">
        <header class="topbar">
            <div>
                <p class="eyebrow">PFA 2026</p>
                <h1>Plateforme de suivi des projets etudiants</h1>
                <p class="subtitle">
                    Suivi des projets, taches, groupes et livrables.
                </p>
            </div>

            <div class="topbar-actions">
                <span class="role-badge">{{ roleLabel }}</span>
                <button class="logout-button" @click="logout">
                    Deconnexion
                </button>
            </div>
        </header>
 <section class="summary-card">
            <h2>Tableau de bord</h2>
            <ul>
                <li>Visualiser l'avancement du projet</li>
                <li>Voir les taches realisees et celles restantes</li>
                <li>Suivre la progression du groupe</li>
            </ul>
        </section>
        
        <section class="stats">
            <div class="stat-card">
                <span>Projets</span>
                <strong>{{ dashboard.projects_count }}</strong>
            </div>

            <div class="stat-card">
                <span>Taches</span>
                <strong>{{ dashboard.tasks_count }}</strong>
            </div>

            <div class="stat-card">
                <span>Taches terminees</span>
                <strong>{{ dashboard.completed_tasks_count }}</strong>
            </div>

            <div class="stat-card">
                <span>Livrables</span>
                <strong>{{ dashboard.deliverables_count }}</strong>
            </div>

            <div class="stat-card">
                <span>En attente</span>
                <strong>{{ dashboard.pending_deliverables_count }}</strong>
            </div>
        </section>

        <section class="charts">
            <article class="chart-card">
                <h2>Status des taches</h2>
                <div class="chart-box">
                    <canvas ref="tasksChartRef"></canvas>
                </div>
            </article>

            <article class="chart-card">
                <h2>Status des livrables</h2>
                <div class="chart-box">
                    <canvas ref="deliverablesChartRef"></canvas>
                </div>
            </article>

            <article class="chart-card chart-card-wide">
                <h2>Status des projets</h2>
                <div class="chart-box">
                    <canvas ref="projectsChartRef"></canvas>
                </div>
            </article>
        </section>
    </main>
</template>

<script setup>
import { Chart, registerables } from 'chart.js';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

Chart.register(...registerables);

const router = useRouter();

const currentUser = ref(JSON.parse(localStorage.getItem('user') || 'null'));
const projects = ref([]);
const loading = ref(true);

const dashboard = ref({
    projects_count: 0,
    tasks_count: 0,
    completed_tasks_count: 0,
    deliverables_count: 0,
    pending_deliverables_count: 0,
});

const tasksChartRef = ref(null);
const deliverablesChartRef = ref(null);
const projectsChartRef = ref(null);

let tasksChart = null;
let deliverablesChart = null;
let projectsChart = null;

const roleLabelMap = {
    admin: 'Administrateur',
    enseignant: 'Enseignant',
    etudiant: 'Etudiant',
};

const currentRole = computed(() => currentUser.value?.role || 'etudiant');

const roleLabel = computed(() => {
    return roleLabelMap[currentRole.value] || 'Etudiant';
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
        en_cours: 'En cours',
        termine: 'Termine',
        a_faire: 'A faire',
        valide: 'Valide',
        refuse: 'Refuse',
    };

    return labels[status] || status;
};

const countByStatus = (items, statuses) => {
    return statuses.map((status) => items.filter((item) => item.status === status).length);
};

const destroyCharts = () => {
    if (tasksChart) tasksChart.destroy();
    if (deliverablesChart) deliverablesChart.destroy();
    if (projectsChart) projectsChart.destroy();
};

const buildCharts = () => {
    destroyCharts();

    const allTasks = projects.value.flatMap((project) => project.tasks || []);
    const allDeliverables = projects.value.flatMap((project) => project.deliverables || []);

    const taskCounts = countByStatus(allTasks, ['a_faire', 'en_cours', 'termine']);
    const deliverableCounts = countByStatus(allDeliverables, ['en_attente', 'valide', 'refuse']);
    const projectCounts = countByStatus(projects.value, ['en_attente', 'en_cours', 'termine']);

    if (tasksChartRef.value) {
        tasksChart = new Chart(tasksChartRef.value, {
            type: 'doughnut',
            data: {
                labels: ['A faire', 'En cours', 'Termine'],
                datasets: [{
                    data: taskCounts,
                    backgroundColor: ['#f59e0b', '#8b5cf6', '#10b981'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
            },
        });
    }

    if (deliverablesChartRef.value) {
        deliverablesChart = new Chart(deliverablesChartRef.value, {
            type: 'doughnut',
            data: {
                labels: ['En attente', 'Valide', 'Refuse'],
                datasets: [{
                    data: deliverableCounts,
                    backgroundColor: ['#fb7185', '#22c55e', '#ef4444'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
            },
        });
    }

    if (projectsChartRef.value) {
        projectsChart = new Chart(projectsChartRef.value, {
            type: 'bar',
            data: {
                labels: ['En attente', 'En cours', 'Termine'],
                datasets: [{
                    label: 'Projets',
                    data: projectCounts,
                    backgroundColor: ['#f472b6', '#8b5cf6', '#14b8a6'],
                    borderRadius: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                    },
                },
            },
        });
    }
};

onMounted(async () => {
    const token = localStorage.getItem('token');

    if (!token) {
        router.push('/login');
        return;
    }

    try {
        const projectsResponse = await fetch('/api/projects', {
            headers: authHeaders(),
        });

        if (projectsResponse.status === 401) {
            logout();
            return;
        }

        projects.value = await projectsResponse.json();

        const dashboardResponse = await fetch('/api/dashboard', {
            headers: authHeaders(),
        });

        if (dashboardResponse.status === 401) {
            logout();
            return;
        }

        dashboard.value = await dashboardResponse.json();
        currentUser.value = JSON.parse(localStorage.getItem('user') || 'null');

        await nextTick();
        buildCharts();
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
});

onBeforeUnmount(() => {
    destroyCharts();
});
</script>

<style scoped>
.dashboard-page {
    min-height: 100vh;
    padding: 32px;
    background: #f8f5fb;
    color: #2f2430;
    font-family: 'Inter', sans-serif;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    padding-bottom: 24px;
    margin-bottom: 24px;
    border-bottom: 1px solid #e9dff0;
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
    font-size: 44px;
    line-height: 1.1;
}

.subtitle {
    margin: 8px 0 0;
    color: #7b6b7a;
    font-size: 16px;
    line-height: 1.6;
}

.topbar-actions {
    display: grid;
    gap: 12px;
    justify-items: end;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 6px 12px;
    background: #f5edf5;
    color: #2f2430;
    font-size: 12px;
    font-weight: 700;
}

.logout-button {
    border: 0;
    border-radius: 8px;
    padding: 12px 18px;
    background: #2f2430;
    color: #ffffff;
    font-weight: 700;
    cursor: pointer;
}

.stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(140px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #ece2f0;
    border-radius: 8px;
    padding: 18px;
}

.stat-card span {
    display: block;
    margin-bottom: 10px;
    color: #7d7180;
    font-size: 14px;
    font-weight: 600;
}

.stat-card strong {
    font-size: 32px;
    color: #2f2430;
}

.charts {
    display: grid;
    grid-template-columns: repeat(2, minmax(280px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.chart-card,
.summary-card {
    background: #ffffff;
    border: 1px solid #ece2f0;
    border-radius: 8px;
    padding: 20px;
}

.chart-card-wide {
    grid-column: 1 / -1;
}

.chart-card h2,
.summary-card h2 {
    margin: 0 0 16px;
    font-size: 20px;
    color: #2f2430;
}

.chart-box {
    height: 280px;
}

.summary-card ul {
    margin: 0;
    padding-left: 20px;
}

.summary-card li {
    margin-bottom: 10px;
    color: #4b3f4e;
}

@media (max-width: 1100px) {
    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .charts {
        grid-template-columns: 1fr;
    }

    .topbar {
        flex-direction: column;
    }

    .topbar-actions {
        justify-items: start;
    }
}

@media (max-width: 640px) {
    .dashboard-page {
        padding: 18px;
    }

    h1 {
        font-size: 32px;
    }

    .stats {
        grid-template-columns: 1fr;
    }
}
</style>
