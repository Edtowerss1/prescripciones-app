import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
    },
    {
      path: '/doctor/prescriptions',
      name: 'doctor-prescriptions',
      component: () => import('@/views/doctor/DoctorPrescriptionsView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['doctor'] },
    },
    {
      path: '/doctor/prescriptions/new',
      name: 'doctor-create-prescription',
      component: () => import('@/views/doctor/DoctorCreatePrescriptionView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['doctor'] },
    },
    {
      path: '/doctor/prescriptions/:id',
      name: 'doctor-prescription-detail',
      component: () => import('@/views/doctor/DoctorPrescriptionDetailView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['doctor'] },
    },
    {
      path: '/patient/prescriptions',
      name: 'patient-prescriptions',
      component: () => import('@/views/patient/PatientPrescriptionsView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['patient'] },
    },
    {
      path: '/patient/prescriptions/:id',
      name: 'patient-prescription-detail',
      component: () => import('@/views/patient/PatientPrescriptionDetailView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['patient'] },
    },
    {
      path: '/admin',
      name: 'admin-dashboard',
      component: () => import('@/views/admin/AdminDashboardView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['admin'] },
    },
    {
      path: '/admin/prescriptions',
      name: 'admin-prescriptions',
      component: () => import('@/views/admin/AdminPrescriptionsView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['admin'] },
    },
    {
      path: '/admin/prescriptions/:id',
      name: 'admin-prescription-detail',
      component: () => import('@/views/doctor/DoctorPrescriptionDetailView.vue'),
      meta: { requiresAuth: true, allowedRoles: ['admin'] },
    },
    {
      path: '/:catchAll(.*)',
      name: 'not-found',
      redirect: '/login',
    },
  ],
})

export default router
