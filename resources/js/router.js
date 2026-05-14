import { createRouter, createWebHistory } from 'vue-router';
import Layout from './components/Layout.vue';
import Projects from './pages/projects.vue';
import Tasks from './pages/task.vue';
import Deliverables from './pages/deliverable.vue';
import Groups from './pages/groupe.vue';
import Users from './pages/users.vue';
import Login from './pages/login.vue';
import Register from './pages/register.vue';
import Dashboard from './pages/dashboard.vue';
import StudentTasks from './pages/student-tasks.vue';
import StudentDeliverables from './pages/student-deliverables.vue';
import StudentProjects from './pages/student-projects.vue';

const routes = [
    {
        path: '/login',
        component: Login,
        meta: { public: true },
    },
    {
        path: '/register',
        component: Register,
        meta: { public: true },
    },
    {
        path: '/',
        component: Layout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                redirect: '/dashboard',
            },
            {
                path: 'dashboard',
                component: Dashboard,
            },
            {
                path: 'projects',
                component: Projects,
            },
            {
                path: 'my-projects',
                component: StudentProjects,
            },
            {
                path: 'tasks',
                component: Tasks,
            },
            {
                path: 'my-tasks',
                component: StudentTasks,
            },
            {
                path: 'deliverables',
                component: Deliverables,
            },
            {
                path: 'my-deliverables',
                component: StudentDeliverables,
            },
            {
                path: 'groups',
                component: Groups,
            },
            {
                path: 'users',
                component: Users,
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const token = localStorage.getItem('token');
    const isPublic = to.matched.some((record) => record.meta.public);

    if (to.matched.some((record) => record.meta.requiresAuth) && !token) {
        return { path: '/login' };
    }

    if (isPublic && token && (to.path === '/login' || to.path === '/register')) {
        return { path: '/dashboard' };
    }

    return true;
});

export default router;
