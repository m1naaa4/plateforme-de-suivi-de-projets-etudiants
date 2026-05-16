<template>
    <main class="app-layout">
        <aside class="sidebar">
            <div class="brand">
                <span class="brand-mark" aria-hidden="true">
                    <span class="brand-mark-inner">P</span>
                </span>
                <div>
                    <strong>PFA Track</strong>
                    <small>Student projects</small>
                </div>
            </div>

            <div class="user-card" v-if="currentUser">
                <div class="user-meta">
                    <span class="user-welcome">{{ t('common.welcome') }}</span>
                    <strong>{{ currentUser.name || 'Utilisateur' }}</strong>
                    <small>{{ currentUser.email || roleLabel }}</small>
                </div>
            </div>

            <LanguageSwitcher />

            <nav class="menu">
                <router-link
                    v-for="item in menuItems"
                    :key="item.key"
                    :to="item.to"
                    class="menu-item"
                >
                    <span class="menu-icon" aria-hidden="true" v-html="item.icon"></span>
                    {{ t(item.labelKey) }}
                </router-link>
            </nav>

            <button class="logout-button" @click="logout">
                {{ t('common.logout') }}
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
import LanguageSwitcher from './LanguageSwitcher.vue';
import { t } from '../i18n';

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

const roleLabelMap = {
    admin: 'common.role.admin',
    enseignant: 'common.role.enseignant',
    etudiant: 'common.role.etudiant',
};

const roleLabel = computed(() => t(roleLabelMap[currentRole.value] || 'common.role.etudiant'));

const icons = {
    dashboard: `
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 11.5V7.2c0-.67.37-1.28.96-1.58l6-3.04a1.8 1.8 0 0 1 1.62 0l6 3.04c.59.3.96.91.96 1.58v4.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 11.5c0-.83.67-1.5 1.5-1.5h13c.83 0 1.5.67 1.5 1.5V18c0 1.1-.9 2-2 2H6c-1.1 0-2-.9-2-2v-6.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    `,
    projects: `
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 7.5c0-.83.67-1.5 1.5-1.5h4.2c.39 0 .77.15 1.06.43l1.24 1.17c.29.28.67.43 1.06.43H18.5c.83 0 1.5.67 1.5 1.5V17c0 .83-.67 1.5-1.5 1.5h-13c-.83 0-1.5-.67-1.5-1.5V7.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    `,
    tasks: `
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M9 6h10M9 12h10M9 18h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M4.5 6.5 5.8 7.8 8 5.5M4.5 12.5 5.8 13.8 8 11.5M4.5 18.5 5.8 19.8 8 17.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    `,
    deliverables: `
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 4.8h6l4 4V19c0 .55-.45 1-1 1H7c-.55 0-1-.45-1-1V5.8c0-.55.45-1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M13 4.8V9h4.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    `,
    users: `
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 12.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Z" stroke="currentColor" stroke-width="1.8"/>
            <path d="M5 19.2c.8-3 3.2-4.7 7-4.7s6.2 1.7 7 4.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
    `,
    groups: `
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M9 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm6 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3Z" stroke="currentColor" stroke-width="1.8"/>
            <path d="M4.8 18.2c.7-2.4 2.7-3.7 6-3.7M19.2 18.2c-.7-2.4-2.7-3.7-6-3.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
    `,
};

const menuItemsMap = {
    admin: [
        { key: 'dashboard', labelKey: 'nav.dashboard', to: '/dashboard', icon: icons.dashboard },
        { key: 'projects', labelKey: 'nav.projects', to: '/projects', icon: icons.projects },
        { key: 'deliverables', labelKey: 'nav.deliverables', to: '/deliverables', icon: icons.deliverables },
        { key: 'users', labelKey: 'nav.users', to: '/users', icon: icons.users },
        { key: 'groups', labelKey: 'nav.groups', to: '/groups', icon: icons.groups },
    ],
    enseignant: [
        { key: 'dashboard', labelKey: 'nav.dashboard', to: '/dashboard', icon: icons.dashboard },
        { key: 'projects', labelKey: 'nav.projects', to: '/projects', icon: icons.projects },
        { key: 'tasks', labelKey: 'nav.tasks', to: '/tasks', icon: icons.tasks },
        { key: 'deliverables', labelKey: 'nav.deliverables', to: '/deliverables', icon: icons.deliverables },
        { key: 'groups', labelKey: 'nav.groups', to: '/groups', icon: icons.groups },
    ],
    etudiant: [
        { key: 'dashboard', labelKey: 'nav.dashboard', to: '/dashboard', icon: icons.dashboard },
        { key: 'projects', labelKey: 'nav.myProjects', to: '/my-projects', icon: icons.projects },
        { key: 'tasks', labelKey: 'nav.myTasks', to: '/my-tasks', icon: icons.tasks },
        { key: 'deliverables', labelKey: 'nav.myDeliverables', to: '/my-deliverables', icon: icons.deliverables },
        { key: 'group', labelKey: 'nav.groups', to: '/groups', icon: icons.groups },
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
    grid-template-columns: minmax(240px, 280px) 1fr;
    background:
        radial-gradient(circle at top left, rgba(245, 237, 245, 0.9), transparent 28%),
        linear-gradient(180deg, #fcfafd 0%, #f8f5fb 100%);
    color: #2f2430;
    font-family: 'Inter', sans-serif;
}

.sidebar {
    position: sticky;
    top: 0;
    height: 100vh;
    background: rgba(255, 255, 255, 0.88);
    border-right: 1px solid #ece2f0;
    padding: 28px 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    backdrop-filter: blur(14px);
}

.brand {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
}

.brand-mark {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, #2f2430 0%, #57485a 100%);
    color: #ffffff;
    display: grid;
    place-items: center;
    font-weight: 800;
    box-shadow: 0 16px 30px rgba(47, 36, 48, 0.18);
}

.brand-mark-inner {
    font-size: 1rem;
    letter-spacing: 0.04em;
}

.brand strong {
    display: block;
    color: #2f2933;
    font-size: 1rem;
}

.brand small {
    color: #8a7a89;
    font-size: 0.85rem;
}
.user-card {
    display: grid;
    gap: 6px;
    padding: 14px 15px;
    border: 1px solid #ece2f0;
    border-radius: 18px;
    background: linear-gradient(180deg, #ffffff 0%, #faf7fc 100%);
    box-shadow: 0 10px 24px rgba(47, 36, 48, 0.05);
}

.user-meta {
    min-width: 0;
}

.user-welcome {
    display: block;
    color: #8a5f7d;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.user-meta strong {
    display: block;
    color: #2f2933;
    font-size: 0.96rem;
    line-height: 1.2;
}

.user-meta small {
    color: #8a7a89;
    font-size: 0.84rem;
    line-height: 1.35;
}

.sidebar :deep(.language-switcher) {
    min-width: 100%;
    gap: 0.3rem;
}

.sidebar :deep(.language-switcher-label) {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.sidebar :deep(.language-switcher-label)::before {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: #8a5f7d;
    box-shadow: 0 0 0 3px rgba(138, 95, 125, 0.12);
    flex: 0 0 auto;
}

.sidebar :deep(select) {
    width: 100%;
}

.menu {
    display: grid;
    gap: 10px;
}

.menu-item {
    display: block;
    text-decoration: none;
    border: 0;
    background: transparent;
    text-align: left;
    color: #7a6a78;
    padding: 13px 14px;
    border-radius: 16px;
    font-weight: 600;
    font: inherit;
    cursor: pointer;
    display: grid;
    grid-template-columns: 20px 1fr;
    align-items: center;
    gap: 12px;
    transition:
        background-color 160ms ease,
        color 160ms ease,
        transform 160ms ease,
        box-shadow 160ms ease;
}

.menu-icon {
    width: 20px;
    height: 20px;
    display: inline-grid;
    place-items: center;
    color: #8a7a89;
}

.menu-icon svg {
    width: 20px;
    height: 20px;
}

.menu-item.router-link-active,
.menu-item:hover {
    background: #f5edf5;
    color: #2f2933;
    transform: translateX(2px);
    box-shadow: inset 0 0 0 1px #ece2f0;
}

.menu-item.router-link-active .menu-icon,
.menu-item:hover .menu-icon {
    color: #2f2430;
}

.logout-button {
    margin-top: auto;
    width: 100%;
}

.layout-content {
    padding: clamp(20px, 3vw, 36px);
}

@media (max-width: 900px) {
    .app-layout {
        grid-template-columns: 1fr;
    }

    .sidebar {
        position: static;
        height: auto;
        border-right: 0;
        border-bottom: 1px solid #ece2f0;
        padding-bottom: 20px;
    }

    .layout-content {
        padding: 20px;
    }
}
</style>
