import { useState } from 'react';
import { Link } from 'react-router-dom';
import { CalendarCheck2, CalendarPlus, CalendarX2, History } from 'lucide-react';
import { useTutorias } from '../../api/hooks';
import type { Tutoria } from '../../api/types';
import { horasHasta } from '../../lib/format';
import { TutoriaItem } from '../../components/TutoriaItem';
import { CalificarDialog, CancelarDialog } from '../../components/Acciones';
import { Estrellas, PageHero, Pager, Skeleton, Vacio } from '../../components/ui';

export function MisTutorias() {
  const { data, isLoading } = useTutorias({ alcance: 'proximas', pageSize: 50 });
  const [cancelar, setCancelar] = useState<Tutoria | null>(null);
  return (
    <>
      <PageHero icon={CalendarCheck2} titulo="Mis tutorías" texto="Tus tutorías programadas. Puedes cancelar en línea hasta 3 horas antes del inicio."
        acciones={<Link to="/estudiante/reservar" className="btn btn-primary"><CalendarPlus size={18} />Reservar tutoría</Link>} />
      <section className="panel">
        {isLoading ? <Skeleton count={3} /> : data?.items.length ? (
          <div className="agenda stagger">
            {data.items.map((t) => (
              <TutoriaItem key={t.id} t={t} ver="estudiante" acciones={horasHasta(t.iniciaEn) >= 3
                ? <button className="btn btn-danger btn-sm" onClick={() => setCancelar(t)}>Cancelar</button>
                : <span className="small muted">Cancelación cerrada</span>} />
            ))}
          </div>
        ) : (
          <Vacio icon={CalendarX2} titulo="No tienes tutorías programadas" texto="Reserva un cupo y aparecerá aquí con su código de confirmación."
            accion={<Link to="/estudiante/reservar" className="btn btn-primary">Buscar cupos</Link>} />
        )}
      </section>
      {cancelar && <CancelarDialog t={cancelar} rol="estudiante" onClose={() => setCancelar(null)} />}
    </>
  );
}

export function HistorialEstudiante() {
  const [estado, setEstado] = useState('');
  const [page, setPage] = useState(1);
  const { data, isLoading } = useTutorias({ alcance: 'historial', estado: estado || undefined, page, pageSize: 10 });
  const [calificar, setCalificar] = useState<Tutoria | null>(null);
  const tabs = [['', 'Todas'], ['completada', 'Completadas'], ['cancelada', 'Canceladas'], ['no_asistio', 'Inasistencias']];
  return (
    <>
      <PageHero icon={History} titulo="Historial" texto="Tutorías pasadas, observaciones de tus docentes y tus calificaciones." />
      <div className="tabs" role="tablist">
        {tabs.map(([v, l]) => <button key={v} role="tab" aria-selected={estado === v} onClick={() => { setEstado(v); setPage(1); }}>{l}</button>)}
      </div>
      <section className="panel">
        {isLoading ? <Skeleton count={4} /> : data?.items.length ? (
          <>
            <div className="agenda stagger">
              {data.items.map((t) => (
                <TutoriaItem key={t.id} t={t} ver="estudiante"
                  extra={<>
                    {t.observacionesDocente && <p className="small" style={{ marginTop: 6, color: 'var(--grafito-2)' }}><b>Observaciones del docente:</b> {t.observacionesDocente}</p>}
                    {t.motivoCancelacion && t.estado === 'cancelada' && <p className="small muted" style={{ marginTop: 6 }}>Motivo: {t.motivoCancelacion}</p>}
                  </>}
                  acciones={t.estado === 'completada' ? (t.calificacion
                    ? <Estrellas valor={t.calificacion} size={16} />
                    : <button className="btn btn-primary btn-sm" onClick={() => setCalificar(t)}>Calificar</button>) : null} />
              ))}
            </div>
            <Pager page={data.page} pageSize={data.pageSize} total={data.total} onPage={setPage} />
          </>
        ) : <Vacio icon={History} titulo="Sin tutorías en esta vista" texto="Aquí quedará el registro de cada tutoría cuando pase su fecha." />}
      </section>
      {calificar && <CalificarDialog t={calificar} onClose={() => setCalificar(null)} />}
    </>
  );
}
