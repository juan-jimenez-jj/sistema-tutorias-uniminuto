import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { BarChart3, BookOpen, CalendarCheck2, CircleCheckBig, Download, GraduationCap, Hourglass, Plus, Search, ShieldCheck, Star, Users, XCircle } from 'lucide-react';
import { api, qs } from '../../api/client';
import type { Materia, Pagina, RegistroAuditoria, Reporte, Rol, UsuarioAdmin } from '../../api/types';
import { fechaConAnio, hace, iniciales, mesCorto } from '../../lib/format';
import { useToast } from '../../lib/toast';
import { Barras, Columnas, MapaCalor } from '../../components/Charts';
import { AreaIcon, Dialog, ErrorAlert, PageHero, Pager, SelectInput, Skeleton, Stat, TextArea, TextInput, Vacio, fieldErrors } from '../../components/ui';

function isoHoy(offsetDays = 0) {
  const d = new Date();
  d.setDate(d.getDate() + offsetDays);
  return d.toISOString().slice(0, 10);
}

const PERIODOS: Record<string, [number, number]> = {
  'Últimos 30 días': [-30, 0],
  'Últimos 90 días': [-90, 0],
  'Semestre (6 meses)': [-182, 30],
};

export function Reportes() {
  const [periodo, setPeriodo] = useState('Semestre (6 meses)');
  const [desde, hasta] = PERIODOS[periodo].map((n) => isoHoy(n));
  const { data, isLoading, error } = useQuery({
    queryKey: ['reportes', desde, hasta],
    queryFn: () => api<Reporte>('/admin/reportes' + qs({ desde, hasta })),
  });
  const t = data?.totales;
  return (
    <>
      <PageHero icon={BarChart3} titulo="Reportes de tutorías" texto="Uso del servicio de acompañamiento en Ciencias Básicas por materia, docente y franja horaria."
        acciones={<>
          <select className="select" style={{ width: 'auto' }} value={periodo} onChange={(e) => setPeriodo(e.target.value)} aria-label="Periodo">
            {Object.keys(PERIODOS).map((p) => <option key={p}>{p}</option>)}
          </select>
          <a className="btn btn-primary" href={`/api/v1/admin/reportes/tutorias.csv${qs({ desde, hasta })}`}><Download size={17} />Exportar CSV</a>
        </>} />
      <ErrorAlert error={error} />
      {isLoading || !data || !t ? <Skeleton height={120} count={3} /> : (
        <>
          <div className="stats stats-6 stagger">
            <Stat icon={CalendarCheck2} color="azul" valor={t.tutorias} etiqueta="tutorías solicitadas" />
            <Stat icon={GraduationCap} color="violeta" valor={t.estudiantesAtendidos} etiqueta="estudiantes atendidos" />
            <Stat icon={Hourglass} color="celeste" valor={t.horasAcompanamiento} decimales={1} etiqueta="horas de acompañamiento" />
            <Stat icon={CircleCheckBig} color="verde" valor={t.tasaAsistencia} decimales={1} sufijo="%" etiqueta="asistencia" />
            <Stat icon={XCircle} color="rojo" valor={t.tasaCancelacion} decimales={1} sufijo="%" etiqueta="cancelación" />
            <Stat icon={Star} color="amarillo" valor={t.calificacionPromedio} decimales={2} etiqueta="satisfacción (1 a 5)" />
          </div>

          <div className="grid-2" style={{ marginBottom: 20 }}>
            <section className="panel">
              <h2 style={{ marginBottom: 12 }}>Evolución mensual</h2>
              <Columnas datos={data.porMes.map((m) => ({ label: mesCorto(m.mes), valores: [m.completadas, m.inasistencias, m.canceladas, m.programadas] }))}
                series={[{ nombre: 'Completadas', color: '#0055a4' }, { nombre: 'Inasistencias', color: '#ffc72c' }, { nombre: 'Canceladas', color: '#f87171' }, { nombre: 'Programadas', color: '#93c5fd' }]} />
            </section>
            <section className="panel">
              <h2 style={{ marginBottom: 4 }}>¿Cuándo piden tutoría?</h2>
              <p className="small muted" style={{ marginBottom: 12 }}>Tutorías por día y hora de inicio. Útil para decidir dónde abrir más horarios.</p>
              <MapaCalor datos={data.mapaCalor} />
            </section>
          </div>

          <div className="grid-2" style={{ marginBottom: 20 }}>
            <section className="panel">
              <h2 style={{ marginBottom: 12 }}>Demanda por materia</h2>
              <Barras datos={data.porMateria.slice(0, 12).map((m) => ({ label: m.materia, valores: [m.completadas, m.total - m.completadas], total: m.total }))}
                series={[{ nombre: 'Completadas', color: 'linear-gradient(90deg, #003a7a, #1a6fc4)' }, { nombre: 'Canceladas, pendientes o inasistencias', color: '#c7d7ee' }]} />
            </section>
            <section className="panel">
              <h2 style={{ marginBottom: 12 }}>Estudiantes por programa</h2>
              <Barras datos={data.porPrograma.map((p) => ({ label: p.programa, valores: [p.estudiantes] }))} series={[{ nombre: 'Estudiantes', color: 'linear-gradient(90deg, #f2b200, #ffd862)' }]} />
            </section>
          </div>

          <section className="panel" style={{ padding: 0 }}>
            <h2 style={{ padding: '20px 22px 12px' }}>Desempeño por docente</h2>
            <div className="table-wrap" style={{ border: 0, borderRadius: 0 }}>
              <table>
                <thead><tr><th>Docente</th><th className="num">Tutorías</th><th className="num">Completadas</th><th className="num">Inasistencias</th><th className="num">Canceló el docente</th><th className="num">Estudiantes</th><th className="num">Satisfacción</th></tr></thead>
                <tbody>
                  {data.porDocente.map((d) => (
                    <tr key={d.docenteId}>
                      <td><span className="persona"><span className="avatar">{iniciales(d.docente)}</span>{d.docente}</span></td><td className="num">{d.total}</td><td className="num">{d.completadas}</td><td className="num">{d.inasistencias}</td>
                      <td className="num">{d.canceladasPorDocente}</td><td className="num">{d.estudiantes}</td><td className="num">{d.calificacion?.toFixed(2) ?? '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        </>
      )}
    </>
  );
}

const ROLES: Record<Rol, string> = { estudiante: 'Estudiante', docente: 'Docente', administrador: 'Administrador' };

export function UsuariosAdmin() {
  const qc = useQueryClient();
  const toast = useToast();
  const [q, setQ] = useState('');
  const [rol, setRol] = useState('');
  const [estado, setEstado] = useState('');
  const [page, setPage] = useState(1);
  const [editar, setEditar] = useState<UsuarioAdmin | 'nuevo' | null>(null);
  const [materiasDe, setMateriasDe] = useState<UsuarioAdmin | null>(null);
  const { data, isLoading } = useQuery({
    queryKey: ['admin', 'usuarios', q, rol, estado, page],
    queryFn: () => api<Pagina<UsuarioAdmin>>('/admin/usuarios' + qs({ q, rol, estado, page, pageSize: 15 })),
    placeholderData: (prev) => prev,
  });
  const toggle = useMutation({
    mutationFn: (u: UsuarioAdmin) => api(`/admin/usuarios/${u.id}/estado`, { method: 'PATCH', body: { estado: u.estado === 'activo' ? 'inactivo' : 'activo' } }),
    onSuccess: (_, u) => { qc.invalidateQueries({ queryKey: ['admin', 'usuarios'] }); toast(u.estado === 'activo' ? `${u.nombre} ya no puede iniciar sesión` : `${u.nombre} puede iniciar sesión de nuevo`); },
    onError: (e: Error) => toast(e.message, 'error'),
  });
  return (
    <>
      <PageHero icon={Users} titulo="Usuarios" texto="Crea cuentas de docentes, asigna sus materias y gestiona accesos. Los estudiantes se registran solos con su correo institucional."
        acciones={<button className="btn btn-primary" onClick={() => setEditar('nuevo')}><Plus size={18} />Crear usuario</button>} />
      <div className="toolbar">
        <div className="grow" style={{ position: 'relative' }}>
          <Search size={16} style={{ position: 'absolute', left: 12, top: 12, color: 'var(--grafito-3)' }} />
          <input className="input" style={{ paddingLeft: 36 }} placeholder="Buscar por nombre, correo o ID" value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }} aria-label="Buscar usuarios" />
        </div>
        <select className="select" value={rol} onChange={(e) => { setRol(e.target.value); setPage(1); }} aria-label="Rol">
          <option value="">Todos los roles</option>{Object.entries(ROLES).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
        </select>
        <select className="select" value={estado} onChange={(e) => { setEstado(e.target.value); setPage(1); }} aria-label="Estado">
          <option value="">Activos e inactivos</option><option value="activo">Activos</option><option value="inactivo">Inactivos</option>
        </select>
      </div>
      {isLoading ? <Skeleton height={300} /> : data?.items.length ? (
        <>
          <div className="table-wrap">
            <table>
              <thead><tr><th>Nombre</th><th>Rol</th><th>Programa / materias</th><th>Último acceso</th><th>Estado</th><th /></tr></thead>
              <tbody>
                {data.items.map((u) => (
                  <tr key={u.id}>
                    <td><span className="persona"><span className="avatar">{iniciales(u.nombre)}</span><span><b style={{ color: 'var(--tinta)' }}>{u.nombre}</b><br /><span className="small muted">{u.email} · {u.idInstitucional}</span></span></span></td>
                    <td><span className={`badge ${u.rol === 'docente' ? 'badge-programada' : u.rol === 'administrador' ? 'badge-no_asistio' : 'badge-completada'}`}>{ROLES[u.rol]}</span></td>
                    <td className="small">{u.rol === 'docente' ? (u.materias.length ? u.materias.join(', ') : <span className="muted">Sin materias</span>) : u.programaAcademico}</td>
                    <td className="small nowrap">{u.ultimoAcceso ? hace(u.ultimoAcceso) : <span className="muted">Nunca</span>}</td>
                    <td><span className={`badge badge-${u.estado}`}>{u.estado === 'activo' ? 'Activo' : 'Inactivo'}</span></td>
                    <td className="nowrap" style={{ textAlign: 'right' }}>
                      {u.rol === 'docente' && <><button className="btn btn-ghost btn-sm" onClick={() => setMateriasDe(u)}>Materias</button>{' '}</>}
                      <button className="btn btn-ghost btn-sm" onClick={() => setEditar(u)}>Editar</button>{' '}
                      <button className="btn btn-ghost btn-sm" onClick={() => toggle.mutate(u)}>{u.estado === 'activo' ? 'Desactivar' : 'Activar'}</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pager page={data.page} pageSize={data.pageSize} total={data.total} onPage={setPage} />
        </>
      ) : <Vacio icon={Users} titulo="Ningún usuario coincide" texto="Revisa la búsqueda o los filtros." />}
      {editar && <UsuarioDialog usuario={editar === 'nuevo' ? null : editar} onClose={() => setEditar(null)} />}
      {materiasDe && <MateriasDocenteDialog docente={materiasDe} onClose={() => setMateriasDe(null)} />}
    </>
  );
}

function UsuarioDialog({ usuario, onClose }: { usuario: UsuarioAdmin | null; onClose: () => void }) {
  const qc = useQueryClient();
  const toast = useToast();
  const [form, setForm] = useState({
    idInstitucional: usuario?.idInstitucional ?? '', nombre: usuario?.nombre ?? '', email: usuario?.email ?? '',
    rol: usuario?.rol ?? 'docente', programaAcademico: usuario?.programaAcademico ?? '', password: '',
  });
  const set = (k: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [k]: e.target.value });
  const guardar = useMutation({
    mutationFn: async () => {
      if (usuario) {
        await api(`/admin/usuarios/${usuario.id}`, { method: 'PUT', body: form });
        if (form.password) await api(`/admin/usuarios/${usuario.id}/password`, { method: 'PUT', body: { password: form.password } });
      } else {
        await api('/admin/usuarios', { method: 'POST', body: form });
      }
    },
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin', 'usuarios'] }); toast(usuario ? 'Usuario actualizado' : 'Usuario creado'); onClose(); },
  });
  const errors = fieldErrors(guardar.error);
  return (
    <Dialog title={usuario ? 'Editar usuario' : 'Crear usuario'} onClose={onClose} footer={<>
      <button className="btn btn-ghost" onClick={onClose}>Cancelar</button>
      <button className="btn btn-dark" disabled={guardar.isPending} onClick={() => guardar.mutate()}>{usuario ? 'Guardar cambios' : 'Crear usuario'}</button>
    </>}>
      {!Object.keys(errors).length && <ErrorAlert error={guardar.error} />}
      <div className="row">
        <TextInput label="ID institucional" value={form.idInstitucional} onChange={set('idInstitucional')} error={errors.idInstitucional} />
        <SelectInput label="Rol" value={form.rol} onChange={set('rol')} error={errors.rol}>
          {Object.entries(ROLES).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
        </SelectInput>
      </div>
      <TextInput label="Nombre completo" value={form.nombre} onChange={set('nombre')} error={errors.nombre} />
      <TextInput label="Correo institucional" type="email" value={form.email} onChange={set('email')} error={errors.email} />
      <TextInput label={form.rol === 'docente' ? 'Área' : 'Programa académico'} value={form.programaAcademico} onChange={set('programaAcademico')} error={errors.programaAcademico} />
      <TextInput label={usuario ? 'Nueva contraseña (opcional)' : 'Contraseña inicial'} type="password" autoComplete="new-password" value={form.password} onChange={set('password')} error={errors.password}
        hint={usuario ? 'Déjala vacía para conservar la actual.' : 'Compártela por un canal seguro; el usuario puede cambiarla al entrar.'} />
    </Dialog>
  );
}

function MateriasDocenteDialog({ docente, onClose }: { docente: UsuarioAdmin; onClose: () => void }) {
  const qc = useQueryClient();
  const toast = useToast();
  const [sel, setSel] = useState<number[]>(docente.materiaIds);
  const materias = useQuery({ queryKey: ['materias', 'todas'], queryFn: () => api<{ items: Materia[] }>('/materias?incluirInactivas=1') });
  const guardar = useMutation({
    mutationFn: () => api(`/admin/docentes/${docente.id}/materias`, { method: 'PUT', body: { materiaIds: sel } }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin', 'usuarios'] }); qc.invalidateQueries({ queryKey: ['materias'] }); toast('Materias asignadas'); onClose(); },
  });
  const quitadas = docente.materiaIds.filter((id) => !sel.includes(id)).length;
  return (
    <Dialog title={`Materias de ${docente.nombre}`} onClose={onClose} footer={<>
      <button className="btn btn-ghost" onClick={onClose}>Cancelar</button>
      <button className="btn btn-dark" disabled={guardar.isPending} onClick={() => guardar.mutate()}>Guardar asignación</button>
    </>}>
      <ErrorAlert error={guardar.error} />
      {quitadas > 0 && <div className="alert alert-warn">Los horarios de las materias que quites quedarán pausados; las tutorías ya programadas se conservan.</div>}
      {materias.isLoading ? <Skeleton count={4} height={24} /> : (
        <div style={{ display: 'grid', gap: 8, maxHeight: 360, overflow: 'auto' }}>
          {materias.data?.items.map((m) => (
            <label key={m.id} className="check">
              <input type="checkbox" checked={sel.includes(m.id)} onChange={(e) => setSel(e.target.checked ? [...sel, m.id] : sel.filter((x) => x !== m.id))} />
              <span><b style={{ color: 'var(--tinta)' }}>{m.nombre}</b> <span className="muted">{m.codigo} · {m.area}</span></span>
            </label>
          ))}
        </div>
      )}
    </Dialog>
  );
}

export function MateriasAdmin() {
  const [editar, setEditar] = useState<Materia | 'nueva' | null>(null);
  const { data, isLoading } = useQuery({ queryKey: ['materias', 'todas'], queryFn: () => api<{ items: Materia[] }>('/materias?incluirInactivas=1') });
  return (
    <>
      <PageHero icon={BookOpen} titulo="Materias" texto="Catálogo de Ciencias Básicas que los estudiantes ven al reservar."
        acciones={<button className="btn btn-primary" onClick={() => setEditar('nueva')}><Plus size={18} />Crear materia</button>} />
      {isLoading ? <Skeleton height={300} /> : data?.items.length ? (
        <div className="table-wrap">
          <table>
            <thead><tr><th>Código</th><th>Materia</th><th>Área</th><th className="num">Docentes</th><th className="num">Horarios activos</th><th>Estado</th><th /></tr></thead>
            <tbody>
              {data.items.map((m) => (
                <tr key={m.id}>
                  <td className="nowrap">{m.codigo}</td>
                  <td><span className="persona"><AreaIcon nombre={m.area} size={18} /><span><b style={{ color: 'var(--tinta)' }}>{m.nombre}</b>{m.descripcion && <><br /><span className="small muted">{m.descripcion}</span></>}</span></span></td>
                  <td>{m.area}</td>
                  <td className="num">{m.docentes}</td>
                  <td className="num" style={m.franjasActivas === 0 && m.estado === 'activa' ? { color: 'var(--rojo)', fontWeight: 600 } : undefined}>{m.franjasActivas}</td>
                  <td><span className={`badge ${m.estado === 'activa' ? 'badge-activo' : 'badge-inactivo'}`}>{m.estado === 'activa' ? 'Activa' : 'Inactiva'}</span></td>
                  <td style={{ textAlign: 'right' }}><button className="btn btn-ghost btn-sm" onClick={() => setEditar(m)}>Editar</button></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : <Vacio icon={BookOpen} titulo="El catálogo está vacío" texto="Crea la primera materia para que los docentes puedan publicar horarios." />}
      {editar && <MateriaDialog materia={editar === 'nueva' ? null : editar} onClose={() => setEditar(null)} />}
    </>
  );
}

function MateriaDialog({ materia, onClose }: { materia: Materia | null; onClose: () => void }) {
  const qc = useQueryClient();
  const toast = useToast();
  const [form, setForm] = useState({ codigo: materia?.codigo ?? '', nombre: materia?.nombre ?? '', descripcion: materia?.descripcion ?? '', area: materia?.area ?? 'Matemáticas', estado: materia?.estado ?? 'activa' });
  const set = (k: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [k]: e.target.value });
  const guardar = useMutation({
    mutationFn: () => api(materia ? `/admin/materias/${materia.id}` : '/admin/materias', { method: materia ? 'PUT' : 'POST', body: form }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['materias'] }); toast(materia ? 'Materia actualizada' : 'Materia creada'); onClose(); },
  });
  const errors = fieldErrors(guardar.error);
  return (
    <Dialog title={materia ? 'Editar materia' : 'Crear materia'} onClose={onClose} footer={<>
      <button className="btn btn-ghost" onClick={onClose}>Cancelar</button>
      <button className="btn btn-dark" disabled={guardar.isPending} onClick={() => guardar.mutate()}>{materia ? 'Guardar cambios' : 'Crear materia'}</button>
    </>}>
      {!Object.keys(errors).length && <ErrorAlert error={guardar.error} />}
      <div className="row">
        <TextInput label="Código" value={form.codigo} onChange={set('codigo')} error={errors.codigo} placeholder="MAT120" maxLength={6} />
        <SelectInput label="Área" value={form.area} onChange={set('area')} error={errors.area}>
          {['Matemáticas', 'Estadística', 'Física', 'Química'].map((a) => <option key={a}>{a}</option>)}
        </SelectInput>
      </div>
      <TextInput label="Nombre" value={form.nombre} onChange={set('nombre')} error={errors.nombre} />
      <TextArea label="Descripción (opcional)" value={form.descripcion} onChange={set('descripcion')} maxLength={500} />
      {materia && (
        <SelectInput label="Estado" value={form.estado} onChange={set('estado')}>
          <option value="activa">Activa: visible para reservar</option><option value="inactiva">Inactiva: oculta para estudiantes</option>
        </SelectInput>
      )}
    </Dialog>
  );
}

export function Auditoria() {
  const [accion, setAccion] = useState('');
  const [page, setPage] = useState(1);
  const { data, isLoading } = useQuery({
    queryKey: ['auditoria', accion, page],
    queryFn: () => api<Pagina<RegistroAuditoria>>('/admin/auditoria' + qs({ accion, page, pageSize: 25 })),
    placeholderData: (prev) => prev,
  });
  return (
    <>
      <PageHero icon={ShieldCheck} titulo="Auditoría" texto="Registro de las acciones sensibles: accesos, reservas, cancelaciones y cambios administrativos." />
      <div className="toolbar">
        <select className="select" value={accion} onChange={(e) => { setAccion(e.target.value); setPage(1); }} aria-label="Tipo de acción">
          <option value="">Todas las acciones</option>
          <option value="auth.">Inicios de sesión</option>
          <option value="tutoria.">Tutorías</option>
          <option value="usuario.">Usuarios</option>
          <option value="franja.">Horarios</option>
          <option value="materia.">Materias</option>
        </select>
      </div>
      {isLoading ? <Skeleton height={300} /> : data?.items.length ? (
        <>
          <div className="table-wrap">
            <table>
              <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Recurso</th><th>IP</th></tr></thead>
              <tbody>
                {data.items.map((r) => (
                  <tr key={r.id}>
                    <td className="nowrap small">{fechaConAnio(r.fecha)} {r.fecha.slice(11, 16)}</td>
                    <td>{r.usuario ?? <span className="muted">Anónimo</span>}{r.rol && <span className="small muted"> · {r.rol}</span>}</td>
                    <td><code style={{ fontSize: '.82rem', color: r.accion.includes('fallido') ? 'var(--rojo)' : 'var(--tinta)' }}>{r.accion}</code></td>
                    <td className="small">{r.entidad}{r.entidadId ? ` #${r.entidadId}` : ''}</td>
                    <td className="small muted">{r.ip ?? '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pager page={data.page} pageSize={data.pageSize} total={data.total} onPage={setPage} />
        </>
      ) : <Vacio icon={ShieldCheck} titulo="Sin registros" texto="Las acciones aparecerán aquí a medida que se usen el sistema." />}
    </>
  );
}
