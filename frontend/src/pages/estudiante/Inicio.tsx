import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { CalendarCheck2, CalendarPlus, CalendarX2, CircleCheckBig, Clock, History, Hourglass, MapPin, Sparkles, Star, Trophy, User, Video } from 'lucide-react';
import { useEstadisticas, useTutorias } from '../../api/hooks';
import type { EstadisticasEstudiante, Tutoria } from '../../api/types';
import { useUsuario } from '../../lib/auth';
import { fechaLarga, horasHasta, rangoHora } from '../../lib/format';
import { Barras } from '../../components/Charts';
import { TutoriaItem } from '../../components/TutoriaItem';
import { CalificarDialog, CancelarDialog } from '../../components/Acciones';
import { AreaIcon, PageHero, Skeleton, Stat, Vacio } from '../../components/ui';

function saludo() {
  const h = new Date().getHours();
  return h < 12 ? 'Buenos días' : h < 19 ? 'Buenas tardes' : 'Buenas noches';
}

function CuentaRegresiva({ iso }: { iso: string }) {
  const [ahora, setAhora] = useState(Date.now());
  useEffect(() => { const id = setInterval(() => setAhora(Date.now()), 30_000); return () => clearInterval(id); }, []);
  const diff = Math.max(0, new Date(iso).getTime() - ahora);
  const d = Math.floor(diff / 86_400_000);
  const h = Math.floor((diff % 86_400_000) / 3_600_000);
  const m = Math.floor((diff % 3_600_000) / 60_000);
  return (
    <div className="cuenta" aria-label={`Faltan ${d} días, ${h} horas y ${m} minutos`}>
      <div><b>{d}</b><span>días</span></div>
      <div><b>{h}</b><span>horas</span></div>
      <div><b>{m}</b><span>min</span></div>
    </div>
  );
}

export function EstudianteInicio() {
  const usuario = useUsuario();
  const stats = useEstadisticas<EstadisticasEstudiante>();
  const proximas = useTutorias({ alcance: 'proximas', pageSize: 4 });
  const porCalificar = useTutorias({ estado: 'completada', pageSize: 20 });
  const [cancelar, setCancelar] = useState<Tutoria | null>(null);
  const [calificar, setCalificar] = useState<Tutoria | null>(null);

  const [proxima, ...resto] = proximas.data?.items ?? [];
  const sinCalificar = (porCalificar.data?.items ?? []).filter((t) => t.calificacion === null);
  const s = stats.data;

  return (
    <>
      <PageHero icon={Sparkles} titulo={`${saludo()}, ${usuario.nombre.split(' ')[0]}`}
        texto={`${usuario.programaAcademico ?? 'Estudiante'} · Este es el resumen de tu acompañamiento académico.`}
        acciones={<Link to="/estudiante/reservar" className="btn btn-primary btn-lg"><CalendarPlus size={19} />Reservar tutoría</Link>} />

      {proximas.isLoading ? <Skeleton height={180} /> : proxima ? (
        <section className="proxima" aria-label="Tu próxima tutoría">
          <div>
            <span className="proxima-etiqueta"><i />Tu próxima tutoría</span>
            <h2>{proxima.materia.nombre}</h2>
            <div className="agenda-meta">
              <span><Clock size={15} />{fechaLarga(proxima.fecha)}, {rangoHora(proxima.horaInicio, proxima.horaFin)}</span>
              <span>{proxima.modalidad === 'virtual' ? <Video size={15} /> : <MapPin size={15} />}{proxima.lugar}</span>
              <span><User size={15} />{proxima.docente.nombre}</span>
            </div>
            <p className="proxima-tema">“{proxima.tema}”</p>
            <div className="proxima-actions">
              {horasHasta(proxima.iniciaEn) >= 3
                ? <button className="btn btn-ghost btn-sm" onClick={() => setCancelar(proxima)}>Cancelar</button>
                : <span className="small" style={{ color: '#b9c8e2' }}>Ya no se puede cancelar en línea (faltan menos de 3 horas).</span>}
              <span className="small" style={{ color: '#9fb4d6' }}>{proxima.codigo}</span>
            </div>
          </div>
          <CuentaRegresiva iso={proxima.iniciaEn} />
        </section>
      ) : (
        <section className="panel" style={{ marginBottom: 24 }}>
          <Vacio icon={CalendarX2} titulo="No tienes tutorías programadas" texto="Elige una materia y verás los cupos libres de las próximas cuatro semanas."
            accion={<Link to="/estudiante/reservar" className="btn btn-primary">Buscar cupos</Link>} />
        </section>
      )}

      {s && (
        <div className="stats stagger">
          <Stat icon={CircleCheckBig} color="verde" valor={s.completadas} etiqueta="tutorías completadas" />
          <Stat icon={Hourglass} color="azul" valor={s.horasAcompanamiento} decimales={1} etiqueta="horas de acompañamiento" />
          <Stat icon={CalendarCheck2} color="celeste" valor={s.proximas} etiqueta="programadas" />
          <Stat icon={Trophy} color="amarillo" valor={s.calificacionPromedio} decimales={1} etiqueta="tu calificación promedio" />
        </div>
      )}

      <div className="accesos stagger">
        <Link to="/estudiante/reservar" className="acceso-card c-amarillo"><span className="chip-icono"><CalendarPlus size={20} /></span><span><b>Reservar</b><span>Busca cupos por materia</span></span></Link>
        <Link to="/estudiante/tutorias" className="acceso-card c-azul"><span className="chip-icono"><CalendarCheck2 size={20} /></span><span><b>Mis tutorías</b><span>Programadas y cancelación</span></span></Link>
        <Link to="/estudiante/historial" className="acceso-card c-violeta"><span className="chip-icono"><History size={20} /></span><span><b>Historial</b><span>Observaciones y calificaciones</span></span></Link>
      </div>

      <div className="grid-2">
        <section className="panel">
          <div className="panel-head"><h2>Siguientes tutorías</h2><Link to="/estudiante/tutorias" className="small">Ver todas</Link></div>
          {resto.length ? (
            <div className="agenda stagger">{resto.map((t) => <TutoriaItem key={t.id} t={t} ver="estudiante" />)}</div>
          ) : <p className="muted small">{proxima ? 'No tienes más tutorías programadas después de la próxima.' : 'Cuando reserves, tus tutorías aparecerán aquí.'}</p>}
        </section>

        <div style={{ display: 'grid', gap: 20 }}>
          {sinCalificar.length > 0 && (
            <section className="panel">
              <div className="panel-head"><h2><span className="chip-icono c-amarillo" style={{ background: 'var(--amarillo-100)', color: 'var(--ambar)' }}><Star size={18} /></span>Pendientes por calificar</h2></div>
              <div className="agenda">
                {sinCalificar.slice(0, 3).map((t) => (
                  <div key={t.id} style={{ display: 'flex', justifyContent: 'space-between', gap: 10, alignItems: 'center' }}>
                    <span style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
                      <AreaIcon nombre={t.materia.area} size={18} />
                      <span><b style={{ color: 'var(--tinta)' }}>{t.materia.nombre}</b><br /><span className="small muted">{t.docente.nombre} · {fechaLarga(t.fecha)}</span></span>
                    </span>
                    <button className="btn btn-primary btn-sm" onClick={() => setCalificar(t)}><Star size={15} />Calificar</button>
                  </div>
                ))}
              </div>
            </section>
          )}
          {s && s.porMateria.length > 0 && (
            <section className="panel">
              <div className="panel-head"><h2>Tus materias</h2></div>
              <Barras datos={s.porMateria.map((m) => ({ label: m.materia, valores: [m.completadas, m.total - m.completadas], total: m.total }))}
                series={[{ nombre: 'Completadas', color: 'linear-gradient(90deg, #0055a4, #1a6fc4)' }, { nombre: 'Otras', color: '#c7d7ee' }]} />
            </section>
          )}
        </div>
      </div>

      {cancelar && <CancelarDialog t={cancelar} rol="estudiante" onClose={() => setCancelar(null)} />}
      {calificar && <CalificarDialog t={calificar} onClose={() => setCalificar(null)} />}
    </>
  );
}
