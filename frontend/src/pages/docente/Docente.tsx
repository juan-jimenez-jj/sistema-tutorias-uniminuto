import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { CalendarCheck2, CalendarClock, ClipboardCheck, Clock4, GraduationCap, MessageSquareText, Plus, History, Star, UserCheck, UserX, Video, MapPin, Users } from 'lucide-react';
import { api } from '../../api/client';
import { useEstadisticas, useTutorias } from '../../api/hooks';
import type { EstadisticasDocente, Franja, Materia, Tutoria } from '../../api/types';
import { DIAS, rangoHora } from '../../lib/format';
import { useUsuario } from '../../lib/auth';
import { useToast } from '../../lib/toast';
import { TutoriaItem } from '../../components/TutoriaItem';
import { CancelarDialog, CerrarDialog } from '../../components/Acciones';
import { Dialog, ErrorAlert, Estrellas, PageHero, Pager, SelectInput, Skeleton, Stat, TextInput, Vacio, fieldErrors } from '../../components/ui';

function useAcciones() {
  const qc = useQueryClient();
  const toast = useToast();
  const [cerrar, setCerrar] = useState<Tutoria | null>(null);
  const [cancelar, setCancelar] = useState<Tutoria | null>(null);
  const rapido = useMutation({
    mutationFn: ({ t, asistio }: { t: Tutoria; asistio: boolean }) => api(`/tutorias/${t.id}/cierre`, { method: 'POST', body: { asistio } }),
    onSuccess: (_, { t, asistio }) => {
      qc.invalidateQueries({ queryKey: ['tutorias'] });
      toast(asistio ? `Asistencia registrada · ${t.estudiante.nombre.split(' ')[0]}` : `Inasistencia registrada · ${t.estudiante.nombre.split(' ')[0]}`);
    },
    onError: (e: Error) => toast(e.message, 'error'),
  });
  const acciones = (t: Tutoria) => t.yaInicio
    ? <>
        <button className="btn btn-success btn-sm" disabled={rapido.isPending} onClick={() => rapido.mutate({ t, asistio: true })}><UserCheck size={15} />Asistió</button>
        <button className="btn btn-ghost btn-sm" disabled={rapido.isPending} onClick={() => rapido.mutate({ t, asistio: false })}><UserX size={15} />No asistió</button>
        <button className="icon-btn" style={{ width: 34, height: 34, borderRadius: 10 }} title="Registrar con observaciones" aria-label="Registrar con observaciones" onClick={() => setCerrar(t)}><MessageSquareText size={16} /></button>
      </>
    : <button className="btn btn-danger btn-sm" onClick={() => setCancelar(t)}>Cancelar</button>;
  const dialogs = <>
    {cerrar && <CerrarDialog t={cerrar} onClose={() => setCerrar(null)} />}
    {cancelar && <CancelarDialog t={cancelar} rol="docente" onClose={() => setCancelar(null)} />}
  </>;
  return { acciones, dialogs };
}

export function DocenteInicio() {
  const usuario = useUsuario();
  const stats = useEstadisticas<EstadisticasDocente>();
  const pendientes = useTutorias({ alcance: 'pendientes_cierre', pageSize: 5 });
  const proximas = useTutorias({ alcance: 'proximas', pageSize: 5 });
  const { acciones, dialogs } = useAcciones();
  const s = stats.data;
  return (
    <>
      <PageHero icon={GraduationCap} titulo={`Hola, profe ${usuario.nombre.split(' ')[0]}`}
        texto={`Tu agenda de tutorías de ${usuario.programaAcademico?.toLowerCase() ?? 'ciencias básicas'}.`}
        acciones={<Link to="/docente/franjas" className="btn btn-primary"><Clock4 size={18} />Gestionar disponibilidad</Link>} />
      {s && (
        <div className="stats stagger">
          <Stat icon={CalendarClock} color="azul" valor={s.hoy} etiqueta="tutorías hoy" />
          <Stat icon={CalendarCheck2} color="celeste" valor={s.proximos7Dias} etiqueta="en los próximos 7 días" />
          <Stat icon={Users} color="verde" valor={s.estudiantesAtendidos} etiqueta="estudiantes atendidos" />
          <Stat icon={Star} color="amarillo" valor={s.calificacionPromedio} decimales={1} etiqueta={`calificación · ${s.calificaciones} opiniones`} />
        </div>
      )}
      {(pendientes.data?.total ?? 0) > 0 && (
        <section className="panel anim-entrar" style={{ marginBottom: 20, borderColor: '#ffe08a', background: 'linear-gradient(180deg, #fffaeb, #fff)' }}>
          <div className="panel-head">
            <h2><span className="chip-icono" style={{ background: 'var(--amarillo-100)', color: 'var(--ambar)' }}><ClipboardCheck size={18} /></span>Por registrar asistencia · {pendientes.data!.total}</h2>
            <Link to="/docente/agenda?vista=pendientes" className="small">Ver todas</Link>
          </div>
          <p className="small muted" style={{ marginTop: -8, marginBottom: 12 }}>Tutorías que ya pasaron y siguen sin cerrar. Marca la asistencia con un clic; registrarla alimenta los reportes de coordinación.</p>
          <div className="agenda stagger">{pendientes.data!.items.map((t) => <TutoriaItem key={t.id} t={t} ver="docente" acciones={acciones(t)} />)}</div>
        </section>
      )}
      <section className="panel">
        <div className="panel-head"><h2>Próximas tutorías</h2><Link to="/docente/agenda" className="small">Agenda completa</Link></div>
        {proximas.isLoading ? <Skeleton count={3} /> : proximas.data?.items.length
          ? <div className="agenda stagger">{proximas.data.items.map((t) => <TutoriaItem key={t.id} t={t} ver="docente" acciones={acciones(t)} />)}</div>
          : <Vacio icon={CalendarCheck2} titulo="Sin reservas por ahora" texto={s?.franjasActivas ? 'Tus horarios están publicados; las reservas aparecerán aquí.' : 'Publica tus horarios para que los estudiantes puedan reservar.'}
            accion={!s?.franjasActivas ? <Link to="/docente/franjas" className="btn btn-primary">Publicar horario</Link> : undefined} />}
      </section>
      {dialogs}
    </>
  );
}

export function Agenda() {
  const params = new URLSearchParams(window.location.search);
  const [vista, setVista] = useState<'proximas' | 'pendientes_cierre'>(params.get('vista') === 'pendientes' ? 'pendientes_cierre' : 'proximas');
  const proximas = useTutorias({ alcance: 'proximas', pageSize: 100 });
  const pendientes = useTutorias({ alcance: 'pendientes_cierre', pageSize: 100 });
  const { acciones, dialogs } = useAcciones();
  const data = vista === 'proximas' ? proximas : pendientes;
  return (
    <>
      <PageHero icon={CalendarCheck2} titulo="Agenda" texto="Registra la asistencia al terminar cada tutoría. Si debes cancelar, el estudiante recibe tu motivo por correo." />
      <div className="tabs" role="tablist">
        <button role="tab" aria-selected={vista === 'proximas'} onClick={() => setVista('proximas')}>Próximas<span className="count">{proximas.data?.total ?? 0}</span></button>
        <button role="tab" aria-selected={vista === 'pendientes_cierre'} onClick={() => setVista('pendientes_cierre')}>Por registrar<span className="count">{pendientes.data?.total ?? 0}</span></button>
      </div>
      <section className="panel">
        {data.isLoading ? <Skeleton count={4} /> : data.data?.items.length
          ? <div className="agenda stagger">{data.data.items.map((t) => <TutoriaItem key={t.id} t={t} ver="docente" acciones={acciones(t)} />)}</div>
          : <Vacio icon={ClipboardCheck} titulo={vista === 'proximas' ? 'No hay tutorías programadas' : 'Todo al día'} texto={vista === 'proximas' ? 'Cuando un estudiante reserve uno de tus horarios aparecerá aquí.' : 'No tienes tutorías pendientes por registrar.'} />}
      </section>
      {dialogs}
    </>
  );
}

export function HistorialDocente() {
  const [q, setQ] = useState('');
  const [estado, setEstado] = useState('');
  const [page, setPage] = useState(1);
  const { data, isLoading } = useTutorias({ alcance: 'historial', estado: estado || undefined, q: q || undefined, page, pageSize: 12 });
  return (
    <>
      <PageHero icon={History} titulo="Historial" texto="Todas las tutorías que has atendido, con las calificaciones y comentarios de tus estudiantes." />
      <div className="toolbar">
        <input className="input grow" placeholder="Buscar por estudiante, tema o código" value={q} onChange={(e) => { setQ(e.target.value); setPage(1); }} aria-label="Buscar" />
        <select className="select" value={estado} onChange={(e) => { setEstado(e.target.value); setPage(1); }} aria-label="Estado">
          <option value="">Todos los estados</option><option value="completada">Completadas</option><option value="cancelada">Canceladas</option><option value="no_asistio">Inasistencias</option>
        </select>
      </div>
      <section className="panel">
        {isLoading ? <Skeleton count={4} /> : data?.items.length ? (
          <>
            <div className="agenda stagger">
              {data.items.map((t) => <TutoriaItem key={t.id} t={t} ver="docente"
                extra={t.comentarioEstudiante ? <p className="small" style={{ marginTop: 6, color: 'var(--grafito-2)' }}>“{t.comentarioEstudiante}”</p> : null}
                acciones={t.calificacion ? <Estrellas valor={t.calificacion} size={16} /> : null} />)}
            </div>
            <Pager page={data.page} pageSize={data.pageSize} total={data.total} onPage={setPage} />
          </>
        ) : <Vacio icon={History} titulo="Sin resultados" texto="Ajusta la búsqueda o el filtro de estado." />}
      </section>
    </>
  );
}

type FormFranja = { materiaId: string; diaSemana: string; horaInicio: string; horaFin: string; lugar: string; modalidad: 'presencial' | 'virtual'; cupo: string };

export function Franjas() {
  const qc = useQueryClient();
  const toast = useToast();
  const usuario = useUsuario();
  const [editar, setEditar] = useState<Franja | 'nueva' | null>(null);
  const franjas = useQuery({ queryKey: ['franjas'], queryFn: () => api<{ items: Franja[] }>('/docente/franjas') });
  const materias = useQuery({ queryKey: ['docente', 'materias'], queryFn: () => api<{ items: Materia[] }>('/docente/materias') });
  const toggle = useMutation({
    mutationFn: (f: Franja) => api(`/docente/franjas/${f.id}/estado`, { method: 'PATCH', body: { activa: !f.activa } }),
    onSuccess: (_, f) => { qc.invalidateQueries({ queryKey: ['franjas'] }); toast(f.activa ? 'Horario pausado' : 'Horario publicado'); },
    onError: (e: Error) => toast(e.message, 'error'),
  });

  const items = franjas.data?.items ?? [];
  const activas = items.filter((f) => f.activa);
  const inactivas = items.filter((f) => !f.activa);

  return (
    <>
      <PageHero icon={Clock4} titulo="Disponibilidad" texto="Tus horarios semanales de tutoría. Se repiten cada semana hasta que los pauses."
        acciones={<button className="btn btn-primary" onClick={() => setEditar('nueva')}><Plus size={18} />Publicar horario</button>} />

      <section className="panel" style={{ marginBottom: 20 }}>
        <h2 style={{ marginBottom: 14 }}>Tu semana</h2>
        {franjas.isLoading ? <Skeleton height={180} /> : (
          <div className="planilla" style={{ background: 'transparent' }}>
            <div className="planilla-head">{DIAS.slice(1, 7).map((d) => <div key={d}>{d}</div>)}</div>
            <div className="semana">
              {[1, 2, 3, 4, 5, 6].map((dia) => (
                <div className="dia" key={dia} style={{ minHeight: 120 }}>
                  <span className="dia-num sr-only">{DIAS[dia]}</span>
                  {activas.filter((f) => f.diaSemana === dia).map((f) => (
                    <button key={f.id} className="franja-chip anim-entrar" onClick={() => setEditar(f)} aria-label={`Editar ${f.materiaNombre} ${DIAS[dia]} ${rangoHora(f.horaInicio, f.horaFin)}`}>
                      <b>{f.horaInicio}–{f.horaFin}</b><br />{f.materiaNombre}
                    </button>
                  ))}
                </div>
              ))}
            </div>
          </div>
        )}
      </section>

      <section className="panel">
        <h2 style={{ marginBottom: 14 }}>Detalle</h2>
        {items.length ? (
          <div className="table-wrap" style={{ border: 0 }}>
            <table>
              <thead><tr><th>Día y hora</th><th>Materia</th><th>Lugar</th><th className="num">Cupo</th><th className="num">Reservas futuras</th><th>Estado</th><th /></tr></thead>
              <tbody>
                {[...activas, ...inactivas].map((f) => (
                  <tr key={f.id} style={f.activa ? undefined : { color: 'var(--grafito-3)' }}>
                    <td className="nowrap"><b>{f.diaNombre}</b> {rangoHora(f.horaInicio, f.horaFin)}</td>
                    <td>{f.materiaNombre}</td>
                    <td><span style={{ display: 'inline-flex', gap: 5, alignItems: 'center' }}>{f.modalidad === 'virtual' ? <Video size={14} /> : <MapPin size={14} />}{f.lugar}</span></td>
                    <td className="num"><span style={{ display: 'inline-flex', gap: 4, alignItems: 'center' }}><Users size={14} />{f.cupo}</span></td>
                    <td className="num">{f.reservasFuturas}</td>
                    <td><span className={`badge ${f.activa ? 'badge-activo' : 'badge-inactivo'}`}>{f.activa ? 'Publicado' : 'Pausado'}</span></td>
                    <td className="nowrap" style={{ textAlign: 'right' }}>
                      <button className="btn btn-ghost btn-sm" onClick={() => setEditar(f)}>Editar</button>{' '}
                      <button className="btn btn-ghost btn-sm" disabled={toggle.isPending} onClick={() => toggle.mutate(f)}>{f.activa ? 'Pausar' : 'Publicar'}</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : <Vacio icon={Clock4} titulo="Aún no publicas horarios" texto="Publica al menos un horario semanal para que los estudiantes puedan reservar contigo." />}
      </section>

      {editar && <FranjaDialog franja={editar === 'nueva' ? null : editar} materias={(materias.data?.items ?? [])} docenteId={usuario.id} onClose={() => setEditar(null)} />}
    </>
  );
}

function FranjaDialog({ franja, materias, onClose }: { franja: Franja | null; materias: Materia[]; docenteId: number; onClose: () => void }) {
  const qc = useQueryClient();
  const toast = useToast();
  const [form, setForm] = useState<FormFranja>(franja
    ? { materiaId: String(franja.materiaId), diaSemana: String(franja.diaSemana), horaInicio: franja.horaInicio, horaFin: franja.horaFin, lugar: franja.lugar, modalidad: franja.modalidad, cupo: String(franja.cupo) }
    : { materiaId: '', diaSemana: '1', horaInicio: '14:00', horaFin: '15:30', lugar: 'Salón B402', modalidad: 'presencial', cupo: '3' });
  const set = (k: keyof FormFranja) => (e: { target: { value: string } }) => setForm({ ...form, [k]: e.target.value });
  const m = useMutation({
    mutationFn: () => api(franja ? `/docente/franjas/${franja.id}` : '/docente/franjas', {
      method: franja ? 'PUT' : 'POST',
      body: { ...form, materiaId: Number(form.materiaId), diaSemana: Number(form.diaSemana), cupo: Number(form.cupo) },
    }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['franjas'] }); toast(franja ? 'Horario actualizado' : 'Horario publicado'); onClose(); },
  });
  const errors = fieldErrors(m.error);
  return (
    <Dialog title={franja ? 'Editar horario' : 'Publicar horario'} onClose={onClose} footer={<>
      <button className="btn btn-ghost" onClick={onClose}>Cancelar</button>
      <button className="btn btn-dark" disabled={m.isPending || !form.materiaId} onClick={() => m.mutate()}>{franja ? 'Guardar cambios' : 'Publicar horario'}</button>
    </>}>
      {franja && franja.reservasFuturas > 0 && <div className="alert alert-warn">Este horario tiene {franja.reservasFuturas} reserva(s) futura(s): solo puedes cambiar el lugar, la modalidad o ampliar el cupo.</div>}
      {!Object.keys(errors).length && <ErrorAlert error={m.error} />}
      <SelectInput label="Materia" value={form.materiaId} onChange={set('materiaId')} error={errors.materiaId}>
        <option value="">{materias.length ? 'Elige una materia…' : 'No tienes materias asignadas'}</option>
        {materias.map((mt) => <option key={mt.id} value={mt.id}>{mt.nombre} ({mt.codigo})</option>)}
      </SelectInput>
      <div className="row">
        <SelectInput label="Día" value={form.diaSemana} onChange={set('diaSemana')} error={errors.diaSemana}>
          {DIAS.slice(1, 7).map((d, i) => <option key={d} value={i + 1}>{d}</option>)}
        </SelectInput>
        <TextInput label="Cupo" type="number" min={1} max={30} value={form.cupo} onChange={set('cupo')} error={errors.cupo} hint="Estudiantes por sesión" />
      </div>
      <div className="row">
        <TextInput label="Hora de inicio" type="time" step={300} value={form.horaInicio} onChange={set('horaInicio')} error={errors.horaInicio} />
        <TextInput label="Hora de fin" type="time" step={300} value={form.horaFin} onChange={set('horaFin')} error={errors.horaFin} />
      </div>
      <div className="row">
        <SelectInput label="Modalidad" value={form.modalidad} onChange={set('modalidad')}>
          <option value="presencial">Presencial</option><option value="virtual">Virtual</option>
        </SelectInput>
        <TextInput label={form.modalidad === 'virtual' ? 'Enlace o plataforma' : 'Salón'} value={form.lugar} onChange={set('lugar')} error={errors.lugar} />
      </div>
    </Dialog>
  );
}
