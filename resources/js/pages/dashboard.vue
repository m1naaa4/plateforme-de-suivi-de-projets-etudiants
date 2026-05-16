<template>
    <main class="dashboard-page">
        <header class="topbar">
            <div class="hero-copy">
                <p class="eyebrow">{{ t('dashboard.eyebrow') }}</p>
                <h1 class="hero-title">{{ t('dashboard.titleMain') }}</h1>
                <p class="hero-subtitle">{{ t('dashboard.titleSub') }}</p>
                <p class="subtitle">{{ t('dashboard.subtitle') }}</p>
            </div>

            <div class="topbar-actions">
                <span class="role-badge">{{ roleLabel }}</span>
                <button class="logout-button" @click="logout">
                    {{ t('common.logout') }}
                </button>
            </div>
        </header>
        <section class="summary-card">
            <div class="card-header card-header-soft">
                <div>
                    <p class="card-kicker">{{ t('dashboard.summaryKicker') }}</p>
                    <h2>{{ t('dashboard.summaryTitle') }}</h2>
                </div>
                <span class="card-badge">{{ t('dashboard.summaryBadge') }}</span>
            </div>

            <ul class="summary-list">
                <li v-for="point in summaryPoints" :key="point">{{ point }}</li>
            </ul>
        </section>
        
        <section class="stats">
            <div class="stat-card stat-card-projects">
                <span class="stat-label">{{ t('dashboard.statProjects') }}</span>
                <strong>{{ dashboard.projects_count }}</strong>
            </div>

            <div class="stat-card stat-card-tasks">
                <span class="stat-label">{{ t('dashboard.statTasks') }}</span>
                <strong>{{ dashboard.tasks_count }}</strong>
            </div>

            <div class="stat-card stat-card-done">
                <span class="stat-label">{{ t('dashboard.statCompletedTasks') }}</span>
                <strong>{{ dashboard.completed_tasks_count }}</strong>
            </div>

            <div class="stat-card stat-card-deliverables">
                <span class="stat-label">{{ t('dashboard.statDeliverables') }}</span>
                <strong>{{ dashboard.deliverables_count }}</strong>
            </div>

            <div class="stat-card stat-card-pending">
                <span class="stat-label">{{ t('dashboard.statPending') }}</span>
                <strong>{{ dashboard.pending_deliverables_count }}</strong>
            </div>
        </section>

        <section class="charts">
            <article class="chart-card">
                <div class="card-header">
                    <div>
                        <p class="card-kicker">{{ t('dashboard.chartKicker') }}</p>
                        <h2>{{ t('dashboard.chartTasks') }}</h2>
                    </div>
                </div>
                <div class="chart-box">
                    <canvas ref="tasksChartRef"></canvas>
                </div>
            </article>

            <article class="chart-card">
                <div class="card-header">
                    <div>
                        <p class="card-kicker">{{ t('dashboard.chartKicker') }}</p>
                        <h2>{{ t('dashboard.chartDeliverables') }}</h2>
                    </div>
                </div>
                <div class="chart-box">
                    <canvas ref="deliverablesChartRef"></canvas>
                </div>
            </article>

            <article class="chart-card chart-card-wide">
                <div class="card-header">
                    <div>
                        <p class="card-kicker">{{ t('dashboard.chartKicker') }}</p>
                        <h2>{{ t('dashboard.chartProjects') }}</h2>
                    </div>
                </div>
                <div class="chart-box">
                    <canvas ref="projectsChartRef"></canvas>
                </div>
            </article>
        </section>
    </main>
</template>

<script setup>
import { Chart, registerables } from 'chart.js';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { locale, t } from '../i18n';

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
    return t(roleLabelMap[currentRole.value] || 'common.role.etudiant');
});

const summaryPoints = computed(() => [
    t('dashboard.summaryPoints.0'),
    t('dashboard.summaryPoints.1'),
    t('dashboard.summaryPoints.2'),
]);

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
                labels: [
                    t('tasks.statusLabels.a_faire'),
                    t('tasks.statusLabels.en_cours'),
                    t('tasks.statusLabels.termine'),
                ],
                datasets: [{
                    data: taskCounts,
                    backgroundColor: ['#7d6b7a', '#b8a0ad', '#2f2430'],
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
                labels: [
                    t('deliverables.statusLabels.en_attente'),
                    t('deliverables.statusLabels.valide'),
                    t('deliverables.statusLabels.refuse'),
                ],
                datasets: [{
                    data: deliverableCounts,
                    backgroundColor: ['#7d6b7a', '#c8a2b8', '#e2d7df'],
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
                labels: [
                    t('projects.statusLabels.en_attente'),
                    t('projects.statusLabels.en_cours'),
                    t('projects.statusLabels.termine'),
                ],
                datasets: [{
                    label: t('projects.heading'),
                    data: projectCounts,
                    backgroundColor: ['#2f2430', '#7d6b7a', '#b8a0ad'],
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

watch(locale, async () => {
    if (!loading.value) {
        await nextTick();
        buildCharts();
    }
});

onBeforeUnmount(() => {
    destroyCharts();
});
</script>

<style scoped>
.dashboard-page {
    min-height: 100vh;
    display: grid;
    gap: 1.5rem;
}

.hero-copy {
    max-width: 56rem;
}

.hero-title {
    max-width: 100%;
    font-size: clamp(2.3rem, 4.3vw, 4.3rem);
    line-height: 0.98;
    white-space: nowrap;
}

.hero-subtitle {
    margin: 0.1rem 0 0.7rem;
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.85rem, 2.8vw, 3.1rem);
    line-height: 1;
    letter-spacing: -0.03em;
    color: #2f2430;
}

.subtitle {
    font-size: 1.12rem;
    max-width: 44ch;
}

.topbar-actions {
    display: grid;
    gap: 0.8rem;
    justify-items: end;
}

.role-badge {
    padding-inline: 0.95rem;
}

.stats {
    grid-template-columns: repeat(5, minmax(140px, 1fr));
}

.stat-card {
    position: relative;
    overflow: hidden;
    min-height: 8.25rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding: 1.6rem 1rem 1.2rem;
}

.stat-card::before {
    content: '';
    position: absolute;
    inset: 0 auto auto 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #2f2430 0%, #8a5f7d 100%);
}

.stat-card-projects::before {
    background: linear-gradient(90deg, #2f2430 0%, #7d6b7a 100%);
}

.stat-card-tasks::before {
    background: linear-gradient(90deg, #7d6b7a 0%, #b8a0ad 100%);
}

.stat-card-done::before {
    background: linear-gradient(90deg, #8a7a89 0%, #c8a2b8 100%);
}

.stat-card-deliverables::before {
    background: linear-gradient(90deg, #b8a0ad 0%, #e2d7df 100%);
}

.stat-card-pending::before {
    background: linear-gradient(90deg, #2f2430 0%, #8a5f7d 100%);
}

.stat-label {
    display: block;
    margin-bottom: 0.55rem;
    color: #7d7180;
    font-size: 0.88rem;
    font-weight: 600;
    line-height: 1.1;
    max-width: 100%;
    overflow-wrap: anywhere;
}

.stat-card strong {
    font-size: clamp(2.35rem, 2.8vw, 3.1rem);
    color: #2f2430;
    line-height: 0.95;
}

.charts {
    grid-template-columns: repeat(2, minmax(280px, 1fr));
}

.card-header {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.9rem;
    text-align: center;
}

.card-header-soft {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(236, 226, 240, 0.85);
}

.card-kicker {
    margin: 0 0 0.25rem;
    color: #8a5f7d;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.card-badge {
    display: inline-flex;
    align-items: center;
    min-height: 2rem;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    background: #f5edf5;
    color: #2f2430;
    font-size: 0.78rem;
    font-weight: 700;
}

.chart-card,
.summary-card {
    position: relative;
}

.chart-card h2 {
    margin: 0;
    line-height: 1.15;
    max-width: none;
    font-size: clamp(1.05rem, 1.1vw + 0.85rem, 1.35rem);
    overflow-wrap: anywhere;
}

.chart-card-wide {
    grid-column: 1 / -1;
}

.chart-box {
    height: 280px;
    padding: 0.2rem 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chart-box canvas {
    max-width: 100%;
    max-height: 100%;
}

.summary-list {
    margin: 0;
    padding-left: 1.1rem;
}

.summary-list li {
    margin-bottom: 0.8rem;
    color: #4b3f4e;
    line-height: 1.6;
}

@media (max-width: 1100px) {
    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .charts {
        grid-template-columns: 1fr;
    }

    .topbar-actions {
        justify-items: start;
    }

    .hero-title {
        max-width: none;
        white-space: normal;
    }
}

@media (max-width: 640px) {
    .stats {
        grid-template-columns: 1fr;
    }

    .chart-box {
        height: 260px;
    }

    .hero-title {
        white-space: normal;
    }

    .card-header {
        flex-direction: column;
    }

    .stat-card {
        min-height: 7.5rem;
    }
}
</style>
