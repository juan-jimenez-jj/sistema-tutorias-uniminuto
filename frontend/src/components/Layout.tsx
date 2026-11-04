import { useEffect, useRef, useState } from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Bell, BellRing, CalendarCheck2, CalendarX2, MapPin, Star, UserX, CalendarPlus, CalendarDays, History, Home, LogOut, Clock4, Users, BookOpen, BarChart3, ShieldCheck, KeyRound, type LucideIcon } from 'lucide-react';
import { api } from '../api/client';
import type { Notificacion, Rol } from '../api/types';
import { useAuth, useUsuario } from '../lib/auth';
import { hace, iniciales } from '../lib/format';
import { Dialog, ErrorAlert, TextInput, fieldErrors } from './ui';
import { useToast } from '../lib/toast';

const MENU: Record<Rol, { to: string; label: string; icon: LucideIcon; end?: boolean }[]> = {
  estudiante: [
    { to: '/estudiante', label: 'Inicio', icon: Home, end: true },
    { to: '/estudiante/reservar', label: 'Reservar', icon: CalendarPlus },
    { to: '/estudiante/tutorias', label: 'Mis tutorías', icon: CalendarDays },
    { to: '/estudiante/historial', label: 'Historial', icon: History },
  ],
  docente: [
    { to: '/docente', label: 'Inicio', icon: Home, end: true },
    { to: '/docente/agenda', label: 'Agenda', icon: CalendarDays },
    { to: '/docente/franjas', label: 'Disponibilidad', icon: Clock4 },
    { to: '/docente/historial', label: 'Historial', icon: History },
  ],
  administrador: [
    { to: '/admin', label: 'Reportes', icon: BarChart3, end: true },
    { to: '/admin/usuarios', label: 'Usuarios', icon: Users },
    { to: '/admin/materias', label: 'Materias', icon: BookOpen },
    { to: '/admin/auditoria', label: 'Auditoría', icon: ShieldCheck },
  ],
};

const ROL_LABEL: Record<Rol, string> = { estudiante: 'Estudiante', docente: 'Docente', administrador: 'Coordinación' };

function Brand() {
  return (
    <span className="brand">
      <span className="brand-logo"><img src="/logo_md.png" alt="" /></span>
      <span>
        <strong>UNIMINUTO <em>Tutorías</em></strong>
        <span>Ciencias Básicas · Zipaquirá</span>
      </span>
    </span>
  );
}

export function Layout() {
  const usuario = useUsuario();
  const { logout } = useAuth();
  const navigate = useNavigate();
  const [password, setPassword] = useState(false);
  const location = useLocation();
  const menu = MENU[usuario.rol];

  return (
    <div className="shell">
      <aside className="sidebar" aria-label="Navegación principal">
        <Brand />
        <p className="nav-titulo">Menú</p>
        <nav className="nav">
          {menu.map(({ to, label, icon: Icon, end }) => (
            <NavLink key={to} to={to} end={end}>
              <Icon size={19} /> {label}
            </NavLink>
          ))}
        </nav>
        <div className="sidebar-foot">
          <span className="avatar" aria-hidden>{iniciales(usuario.nombre)}</span>
          <span className="who">
            <strong title={usuario.nombre}>{usuario.nombre}</strong>
            <span className={`rol-pill rol-${usuario.rol}`}>{ROL_LABEL[usuario.rol]}</span>
          </span>
        </div>
      </aside>

      <div className="main">
        <header className="topbar">
          <span className="mobile-brand"><Brand /></span>
          <span className="topbar-contexto"><MapPin size={15} /><b>Sede Zipaquirá</b> · {new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date())}</span>
          <Notificaciones />
          <button className="icon-btn" onClick={() => setPassword(true)} aria-label="Cambiar contraseña" title="Cambiar contraseña"><KeyRound size={18} /></button>
          <button className="icon-btn" onClick={async () => { await logout(); navigate('/login'); }} aria-label="Cerrar sesión" title="Cerrar sesión"><LogOut size={18} /></button>
        </header>
        <main className="content" id="contenido">
          <div className="pagina" key={location.pathname}><Outlet /></div>
        </main>
      </div>

      <nav className="mobile-nav" aria-label="Navegación">
        {menu.map(({ to, label, icon: Icon, end }) => (
          <NavLink key={to} to={to} end={end}><Icon size={20} />{label}</NavLink>
        ))}
      </nav>
      {password && <CambiarPassword onClose={() => setPassword(false)} />}
    </div>
  );
}

function Notificaciones() {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);
  const navigate = useNavigate();
  const qc = useQueryClient();
  const { data } = useQuery({
    queryKey: ['notificaciones'],
    queryFn: () => api<{ items: Notificacion[]; noLeidas: number }>('/notificaciones?limit=15'),
    refetchInterval: 30_000,
  });
  const leer = useMutation({
    mutationFn: (id?: number) => api(id ? `/notificaciones/${id}/lectura` : '/notificaciones/lectura', { method: 'POST' }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['notificaciones'] }),
  });

  useEffect(() => {
    if (!open) return;
    const close = (e: MouseEvent) => !ref.current?.contains(e.target as Node) && setOpen(false);
    const esc = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
    document.addEventListener('mousedown', close);
    document.addEventListener('keydown', esc);
    return () => { document.removeEventListener('mousedown', close); document.removeEventListener('keydown', esc); };
  }, [open]);

  const unread = data?.noLeidas ?? 0;
  return (
    <div style={{ position: 'relative' }} ref={ref}>
      <button className="icon-btn" onClick={() => setOpen((o) => !o)} aria-expanded={open} aria-label={`Notificaciones${unread ? `, ${unread} sin leer` : ''}`}>
        <Bell size={18} />
        {unread > 0 && <span className="dot">{unread > 9 ? '9+' : unread}</span>}
      </button>
      {open && (
        <div className="notif-panel">
          <header>
            <h3>Notificaciones</h3>
            {unread > 0 && <button className="btn btn-ghost btn-sm" onClick={() => leer.mutate(undefined)}>Marcar todas como leídas</button>}
          </header>
          <div className="notif-list">
            {data?.items.length ? data.items.map((n) => (
              <button key={n.id} className={`notif ${n.leida ? '' : 'nueva'}`} onClick={() => {
                if (!n.leida) leer.mutate(n.id);
                setOpen(false);
                if (n.enlace) navigate(n.enlace);
              }}>
                <NotifIcon tipo={n.tipo} />
                <span>
                  <b>{n.titulo}</b>
                  <p>{n.mensaje}</p>
                  <time>{hace(n.creadoEn)}</time>
                </span>
              </button>
            )) : <p className="muted small" style={{ padding: 16 }}>Aquí verás las confirmaciones y cambios de tus tutorías.</p>}
          </div>
        </div>
      )}
    </div>
  );
}

const NOTIF: Record<string, [LucideIcon, string]> = {
  reserva_confirmada: [CalendarCheck2, 'verde'],
  nueva_reserva: [CalendarPlus, 'azul'],
  tutoria_cancelada: [CalendarX2, 'rojo'],
  calificar_tutoria: [Star, 'amarillo'],
  inasistencia: [UserX, 'naranja'],
};

function NotifIcon({ tipo }: { tipo: string }) {
  const [Icon, color] = NOTIF[tipo] ?? [BellRing, 'celeste'];
  return <span className={`chip-icono c-${color}`} aria-hidden><Icon size={18} /></span>;
}

function CambiarPassword({ onClose }: { onClose: () => void }) {
  const toast = useToast();
  const [actual, setActual] = useState('');
  const [nueva, setNueva] = useState('');
  const mutation = useMutation({
    mutationFn: () => api('/auth/password', { method: 'PUT', body: { passwordActual: actual, passwordNueva: nueva } }),
    onSuccess: () => { toast('Contraseña actualizada'); onClose(); },
  });
  const errors = fieldErrors(mutation.error);
  return (
    <Dialog title="Cambiar contraseña" onClose={onClose} footer={<>
      <button className="btn btn-ghost" onClick={onClose}>Cancelar</button>
      <button className="btn btn-dark" disabled={mutation.isPending || !actual || !nueva} onClick={() => mutation.mutate()}>Guardar contraseña</button>
    </>}>
      {!Object.keys(errors).length && <ErrorAlert error={mutation.error} />}
      <TextInput label="Contraseña actual" type="password" autoComplete="current-password" value={actual} onChange={(e) => setActual(e.target.value)} error={errors.passwordActual} />
      <TextInput label="Nueva contraseña" type="password" autoComplete="new-password" value={nueva} onChange={(e) => setNueva(e.target.value)} error={errors.passwordNueva} hint="Mínimo 8 caracteres, con letras y números." />
    </Dialog>
  );
}
