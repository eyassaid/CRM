// Route records only; guards live in index.js. Navigate by name, never by path.
// Users who cannot open a page (e.g. Sections for non-admins) are not stopped here: they see the backend's 403.
export default [
    { path: '/', redirect: { name: 'dashboard' } },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/auth/LoginView.vue'),
        meta: { guestOnly: true, title: 'Log in' },
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue'),
        meta: { requiresAuth: true, title: 'My work' },
    },
    {
        path: '/clients',
        meta: { requiresAuth: true },
        children: [
            { path: '', name: 'clients', component: () => import('@/views/clients/ClientListView.vue'), meta: { title: 'Clients' } },
            {
                path: ':id',
                name: 'client-detail',
                component: () => import('@/views/clients/ClientDetailView.vue'),
                props: true,
                meta: { title: 'Client' },
            },
        ],
    },
    {
        path: '/projects/:id',
        name: 'project-detail',
        component: () => import('@/views/projects/ProjectDetailView.vue'),
        props: true,
        meta: { requiresAuth: true, title: 'Project' },
    },
    {
        path: '/notifications',
        name: 'notifications',
        component: () => import('@/views/notifications/NotificationListView.vue'),
        meta: { requiresAuth: true, title: 'Notifications' },
    },
    {
        // Admins only.
        path: '/sections',
        meta: { requiresAuth: true },
        children: [
            { path: '', name: 'sections', component: () => import('@/views/sections/SectionListView.vue'), meta: { title: 'Sections' } },
            {
                path: ':id',
                name: 'section-detail',
                component: () => import('@/views/sections/SectionDetailView.vue'),
                props: true,
                meta: { title: 'Section' },
            },
        ],
    },
    {
        // Admins and team leaders.
        path: '/users',
        name: 'users',
        component: () => import('@/views/users/UserListView.vue'),
        meta: { requiresAuth: true, title: 'Users' },
    },
    {
        // Admins see every team; team leaders see the teams they lead.
        path: '/reports',
        meta: { requiresAuth: true },
        children: [
            { path: '', name: 'reports', component: () => import('@/views/reports/ReportListView.vue'), meta: { title: 'Reports' } },
            {
                path: 'teams/:id',
                name: 'team-report',
                component: () => import('@/views/reports/TeamReportView.vue'),
                props: true,
                meta: { title: 'Team report' },
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/views/NotFoundView.vue'),
        meta: { title: 'Page not found' },
    },
];
