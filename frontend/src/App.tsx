import { Suspense, lazy } from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import { ErrorBoundary } from './components/ErrorBoundary';
import { ProtectedRoute } from './components/ProtectedRoute';
import { MainLayout } from './components/Layout/MainLayout';

// Lazy load pages for better performance
const Login = lazy(() => import('./pages/Login').then(m => ({ default: m.Login })));
const Dashboard = lazy(() => import('./pages/Dashboard').then(m => ({ default: m.Dashboard })));
const KurikulumList = lazy(() => import('./pages/Kurikulum/KurikulumList').then(m => ({ default: m.KurikulumList })));
const CPLList = lazy(() => import('./pages/CPL').then(m => ({ default: m.CPLList })));
const CPMKList = lazy(() => import('./pages/CPMK').then(m => ({ default: m.CPMKList })));
const MahasiswaList = lazy(() => import('./pages/Mahasiswa').then(m => ({ default: m.MahasiswaList })));
const DosenList = lazy(() => import('./pages/Dosen').then(m => ({ default: m.DosenList })));
const RPSList = lazy(() => import('./pages/RPS').then(m => ({ default: m.RPSList })));
const PenilaianList = lazy(() => import('./pages/Penilaian').then(m => ({ default: m.PenilaianList })));
const KelasList = lazy(() => import('./pages/Kelas').then(m => ({ default: m.KelasList })));
const KRSPage = lazy(() => import('./pages/Enrollment').then(m => ({ default: m.KRSPage })));
const NotificationList = lazy(() => import('./pages/Notifications/NotificationList').then(m => ({ default: m.NotificationList })));
const Profile = lazy(() => import('./pages/Profile').then(m => ({ default: m.Profile })));
const Settings = lazy(() => import('./pages/Settings').then(m => ({ default: m.Settings })));

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
      staleTime: 5 * 60 * 1000, // 5 minutes
    },
  },
});

// Loading fallback component
const PageLoader = () => (
  <div className="flex items-center justify-center min-h-screen bg-gray-50 dark:bg-gray-900">
    <div className="text-center">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 dark:border-primary-400 mx-auto mb-4"></div>
      <p className="text-gray-600 dark:text-gray-400">Loading...</p>
    </div>
  </div>
);

function App() {
  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <ThemeProvider>
          <AuthProvider>
            <Router>
              <Suspense fallback={<PageLoader />}>
                <Routes>
                  {/* Public Routes */}
                  <Route path="/login" element={<Login />} />

                  {/* Protected Routes */}
                  <Route
                    path="/"
                    element={
                      <ProtectedRoute>
                        <MainLayout />
                      </ProtectedRoute>
                    }
                  >
                    <Route index element={<Dashboard />} />

                    {/* Kurikulum Routes */}
                    <Route
                      path="kurikulum"
                      element={
                        <ProtectedRoute allowedRoles={['admin', 'kaprodi']}>
                          <KurikulumList />
                        </ProtectedRoute>
                      }
                    />

                    {/* CPL Routes */}
                    <Route
                      path="cpl"
                      element={
                        <ProtectedRoute allowedRoles={['admin', 'kaprodi', 'dosen']}>
                          <CPLList />
                        </ProtectedRoute>
                      }
                    />

                    {/* CPMK Routes */}
                    <Route
                      path="cpmk"
                      element={
                        <ProtectedRoute allowedRoles={['admin', 'kaprodi', 'dosen']}>
                          <CPMKList />
                        </ProtectedRoute>
                      }
                    />

                    {/* RPS Routes */}
                    <Route
                      path="rps"
                      element={
                        <ProtectedRoute allowedRoles={['admin', 'kaprodi', 'dosen']}>
                          <RPSList />
                        </ProtectedRoute>
                      }
                    />

                    {/* Penilaian Routes */}
                    <Route
                      path="penilaian"
                      element={
                        <ProtectedRoute allowedRoles={['admin', 'dosen']}>
                          <PenilaianList />
                        </ProtectedRoute>
                      }
                    />

                    {/* Mahasiswa Routes */}
                    <Route
                      path="mahasiswa"
                      element={
                        <ProtectedRoute allowedRoles={['admin', 'kaprodi', 'dosen']}>
                          <MahasiswaList />
                        </ProtectedRoute>
                      }
                    />

                    {/* Dosen Routes */}
                    <Route
                      path="dosen"
                      element={
                        <ProtectedRoute allowedRoles={['admin', 'kaprodi']}>
                          <DosenList />
                        </ProtectedRoute>
                      }
                    />

                    {/* Kelas Routes */}
                    <Route
                      path="kelas"
                      element={
                        <ProtectedRoute allowedRoles={['admin', 'kaprodi', 'dosen']}>
                          <KelasList />
                        </ProtectedRoute>
                      }
                    />

                    {/* KRS/Enrollment Routes */}
                    <Route
                      path="krs"
                      element={
                        <ProtectedRoute allowedRoles={['mahasiswa', 'admin']}>
                          <KRSPage />
                        </ProtectedRoute>
                      }
                    />

                    {/* Notifications */}
                    <Route path="notifications" element={<NotificationList />} />

                    {/* User Routes */}
                    <Route path="profile" element={<Profile />} />
                    <Route path="settings" element={<Settings />} />
                  </Route>
                </Routes>
              </Suspense>
            </Router>

            <ToastContainer
              position="top-right"
              autoClose={3000}
              hideProgressBar={false}
              newestOnTop={false}
              closeOnClick
              rtl={false}
              pauseOnFocusLoss
              draggable
              pauseOnHover
              theme="colored"
            />
          </AuthProvider>
        </ThemeProvider>
      </QueryClientProvider>
    </ErrorBoundary>
  );
}

export default App;
