import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import type { Rol } from './api/types';
import { HOME, useAuth } from './lib/auth';
import { Layout } from './components/Layout';
import { Login, Registro } from './pages/Acceso';
import { EstudianteInicio } from './pages/estudiante/Inicio';
import { Reservar } from './pages/estudiante/Reservar';
import { HistorialEstudiante, MisTutorias } from './pages/estudiante/MisTutorias';
import { Agenda, DocenteInicio, Franjas, HistorialDocente } from './pages/docente/Docente';
import { Auditoria, MateriasAdmin, Reportes, UsuariosAdmin } from './pages/admin/Admin';

/** Guardia de rutas por rol (RBAC en el cliente; el backend vuelve a validar cada petición). */
function Protegida({ rol, children }: { rol: Rol; children: ReactNode }) {
  const { usuario, cargando } = useAuth();
  if (cargando) return <div style={{ padding: 40 }} className="muted">Cargando…</div>;
  if (!usuario) return <Navigate to="/login" replace />;
  if (usuario.rol !== rol) return <Navigate to={HOME[usuario.rol]} replace />;
  return <>{children}</>;
}

function Raiz() {
  const { usuario, cargando } = useAuth();
  if (cargando) return null;
  return <Navigate to={usuario ? HOME[usuario.rol] : '/login'} replace />;
}

export function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/registro" element={<Registro />} />
      <Route path="/estudiante" element={<Protegida rol="estudiante"><Layout /></Protegida>}>
        <Route index element={<EstudianteInicio />} />
        <Route path="reservar" element={<Reservar />} />
        <Route path="tutorias" element={<MisTutorias />} />
        <Route path="historial" element={<HistorialEstudiante />} />
      </Route>
      <Route path="/docente" element={<Protegida rol="docente"><Layout /></Protegida>}>
        <Route index element={<DocenteInicio />} />
        <Route path="agenda" element={<Agenda />} />
        <Route path="franjas" element={<Franjas />} />
        <Route path="historial" element={<HistorialDocente />} />
      </Route>
      <Route path="/admin" element={<Protegida rol="administrador"><Layout /></Protegida>}>
        <Route index element={<Reportes />} />
        <Route path="usuarios" element={<UsuariosAdmin />} />
        <Route path="materias" element={<MateriasAdmin />} />
        <Route path="auditoria" element={<Auditoria />} />
      </Route>
      <Route path="*" element={<Raiz />} />
    </Routes>
  );
}
