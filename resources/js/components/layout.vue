<template>
    <main class="app-layout">
        <aside class="sidebar">
            <div class="brand">
                <span class="brand-mark">P</span>
                <div>
                    <strong>PFA Track</strong>
                    <small>Student projects</small>
                </div>
            </div>

            <nav class="menu">
                <router-link
                    v-for="item in menuItems"
                    :key="item.key"
                    :to="item.to"
                    class="menu-item"
                >
                    {{ item.label }}
                </router-link>
            </nav>

            <button class="logout-button" @click="logout">
                Deconnexion
            </button>
        </aside>

        <section class="layout-content">
            <router-view />
        </section>
    </main>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const readStoredUser = () => {
    try {
        return JSON.parse(localStorage.getItem('user') || 'null');
    } catch {
        return null;
    }
};

const currentUser = ref(readStoredUser());

router.afterEach(() => {
    currentUser.value = readStoredUser();
});

const currentRole = computed(() => currentUser.value?.role || 'etudiant');

const menuItemsMap = {
    admin: [
        { key: 'dashboard', label: 'Dashboard', to: '/dashboard' },
        { key: 'projects', label: 'Projets', to: '/projects' },
        { key: 'tasks', label: 'Taches', to: '/tasks' },
        { key: 'deliverables', label: 'Livrables', to: '/deliverables' },
        { key: 'users', label: 'Utilisateurs', to: '/users' },
        { key: 'groups', label: 'Groupes', to: '/groups' },
    ],
    enseignant: [
        { key: 'dashboard', label: 'Dashboard', to: '/dashboard' },
        { key: 'projects', label: 'Projets', to: '/projects' },
        { key: 'tasks', label: 'Taches', to: '/tasks' },
        { key: 'deliverables', label: 'Livrables', to: '/deliverables' },
        { key: 'groups', label: 'Groupes', to: '/groups' },
    ],
    etudiant: [
        { key: 'dashboard', label: 'Dashboard', to: '/dashboard' },
        { key: 'projects', label: 'Mes projets', to: '/my-projects' },
        { key: 'tasks', label: 'Mes taches', to: '/my-tasks' },
        { key: 'deliverables', label: 'Mes livrables', to: '/my-deliverables' },
        { key: 'group', label: 'Mon groupe', to: '/groups' },
    ],
};

const menuItems = computed(() => {
    return menuItemsMap[currentRole.value] || menuItemsMap.etudiant;
});

const logout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push('/login');
};
</script>

<style scoped>
.app-layout {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 260px 1fr;
    background: #faf7fb;
    color: #2f2430;
    font-family: 'Inter', sans-serif;
}

.sidebar {
    background: #ffffff;
    border-right: 1px solid #eee5ee;
    padding: 28px 20px;
    display: flex;
    flex-direction: column;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 34px;
}

.brand-mark {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    background: #2f2430;
    color: #ffffff;
    display: grid;
    place-items: center;
    font-weight: 800;
}

.brand strong {
    display: block;
    color: #2f2933;
}

.brand small {
    color: #8a7a89;
}

.menu {
    display: grid;
    gap: 8px;
}

.menu-item {
    display: block;
    text-decoration: none;
    border: 0;
    background: transparent;
    text-align: left;
    color: #7a6a78;
    padding: 11px 12px;
    border-radius: 8px;
    font-weight: 600;
    font: inherit;
    cursor: pointer;
}

.menu-item.router-link-active,
.menu-item:hover {
    background: #f5edf5;
    color: #2f2933;
}

.logout-button {
    margin-top: auto;
    border: 0;
    border-radius: 8px;
    padding: 12px 14px;
    background: #2f2430;
    color: #ffffff;
    font-weight: 700;
    cursor: pointer;
}

.logout-button:hover {
    background: #443947;
}

.layout-content {
    padding: 40px;
}

@media (max-width: 900px) {
    .app-layout {
        grid-template-columns: 1fr;
    }

    .sidebar {
        border-right: 0;
        border-bottom: 1px solid #eee5ee;
    }

    .layout-content {
        padding: 20px;
    }
}
</style>
