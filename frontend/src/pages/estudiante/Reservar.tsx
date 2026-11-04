import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { CalendarPlus, CalendarSearch, Video, MapPin } from 'lucide-react';
import { api, qs } from '../../api/client';
import type { DiaOferta, DocenteMateria, FranjaOferta, Materia, Ofertas, Tutoria } from '../../api/types';
import { DIAS, fechaLarga, hora12, rangoHora } from '../../lib/format';
import { useToast } from '../../lib/toast';
import { AreaIcon, Confeti, ErrorAlert, PageHero, Skeleton, TextArea, Vacio, fieldErrors } from '../../components/ui';
import { area } from '../../lib/areas';

const AREAS = ['Matemáticas', 'Estadística', 'Física', 'Química'] as const;
const areaDe = (nombre: string) => area(nombre).clase;

function ymd(d: Date) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

export function Reservar() {
  const qc = useQueryClient();
  const toast = useToast();
  const [materia, setMateria] = useState<Materia | null>(null);
  const [docente, setDocente] = useState<number | null>(null);
  const [sel, setSel] = useState<{ fecha: string; franja: FranjaOferta } | null>(null);
  const [tema, setTema] = useState('');
  const [hecha, setHecha] = useState<Tutoria | null>(null);

  const materias = useQuery({ queryKey: ['materias'], queryFn: () => api<{ items: Materia[] }>('/materias') });
  const docentes = useQuery({
    queryKey: ['materias', materia?.id, 'docentes'],
    queryFn: () => api<{ items: DocenteMateria[] }>(`/materias/${materia!.id}/docentes`),
    enabled: !!materia,
  });
  const ofertas = useQuery({
    queryKey: ['ofertas', materia?.id, docente],
    queryFn: () => api<Ofertas>('/ofertas' + qs({ materiaId: materia!.id, docenteId: docente })),
    enabled: !!materia,
  });

  const reservar = useMutation({
    mutationFn: () => api<{ tutoria: Tutoria }>('/tutorias', { method: 'POST', body: { franjaId: sel!.franja.id, fecha: sel!.fecha, tema } }),
    onSuccess: ({ tutoria }) => {
      setHecha(tutoria);
      toast(`Reserva confirmada · ${tutoria.codigo}`);
      qc.invalidateQueries({ queryKey: ['tutorias'] });
      qc.invalidateQueries({ queryKey: ['ofertas'] });
    },
    onError: () => qc.invalidateQueries({ queryKey: ['ofertas'] }),
  });

  const datos = ofertas.data;
  // Semanas lunes–sábado que cubren el rango de oferta.
  const semanas = useMemo(() => {
    if (!datos) return [];
    const porFecha = new Map(datos.dias.map((d) => [d.fecha, d]));
    const inicio = new Date(datos.desde + 'T12:00:00');
    inicio.setDate(inicio.getDate() - ((inicio.getDay() + 6) % 7));
    const fin = new Date(datos.hasta + 'T12:00:00');
    const result: { fecha: string; dia: Date; oferta?: DiaOferta; fuera: boolean }[][] = [];
    for (const d = new Date(inicio); d <= fin; d.setDate(d.getDate() + 7)) {
      const semana = [];
      for (let i = 0; i < 6; i++) {
        const dia = new Date(d);
        dia.setDate(d.getDate() + i);
        const f = ymd(dia);
        semana.push({ fecha: f, dia, oferta: porFecha.get(f), fuera: f < datos.desde || f > datos.hasta });
      }
      result.push(semana);
    }
    return result;
  }, [datos]);

  const hoy = ymd(new Date());
  const paso = !materia ? 1 : !sel ? 2 : 3;
  const errores = fieldErrors(reservar.error);

  if (hecha) {
    return (
      <>
        <Confeti />
        <section className="panel exito anim-entrar">
          <svg className="exito-check" viewBox="0 0 92 92" aria-hidden><circle cx="46" cy="46" r="46" /><path d="M28 47 l12 12 l24 -26" /></svg>
          <h1>¡Reserva confirmada!</h1>
          <p className="muted">Te enviamos el detalle a tu correo institucional y le avisamos al docente.</p>
          <span className="codigo">{hecha.codigo}</span>
          <dl>
            <dt>Materia</dt><dd>{hecha.materia.nombre}</dd>
            <dt>Cuándo</dt><dd>{fechaLarga(hecha.fecha)}, {rangoHora(hecha.horaInicio, hecha.horaFin)}</dd>
            <dt>Dónde</dt><dd>{hecha.lugar}</dd>
            <dt>Docente</dt><dd>{hecha.docente.nombre}</dd>
          </dl>
          <p className="small muted" style={{ marginBottom: 20 }}>Si no puedes asistir, cancela con al menos 3 horas de anticipación para liberar el cupo.</p>
          <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', justifyContent: 'center' }}>
            <Link to="/estudiante/tutorias" className="btn btn-dark">Ver mis tutorías</Link>
            <button className="btn btn-ghost" onClick={() => { setHecha(null); setSel(null); setTema(''); }}>Reservar otra</button>
          </div>
        </section>
      </>
    );
  }

  return (
    <>
      <PageHero icon={CalendarPlus} titulo="Reservar tutoría" texto="Elige la materia, toca un cupo amarillo del calendario y cuéntale al docente qué tema quieres trabajar." />

      <div className="pasos" aria-label="Pasos">
        {['Materia', 'Fecha y hora', 'Confirmar'].map((p, i) => (
          <span key={p} className={`paso ${paso > i + 1 ? 'hecho' : ''} ${paso === i + 1 ? 'actual' : ''}`} aria-current={paso === i + 1 ? 'step' : undefined}>
            <i>{i + 1}</i>{p}
          </span>
        ))}
      </div>

      <section className="panel" style={{ marginBottom: 20 }}>
        <div className="panel-head">
          <h2>¿En qué materia necesitas apoyo?</h2>
          {materia && <button className="btn btn-ghost btn-sm" onClick={() => { setMateria(null); setDocente(null); setSel(null); }}>Cambiar materia</button>}
        </div>
        {materias.isLoading ? <Skeleton height={60} count={2} /> : materia ? (
          <div className="materia-sel anim-entrar"><AreaIcon nombre={materia.area} size={22} /><span><b>{materia.nombre}</b><br /><span className="muted small">{materia.codigo} · {materia.area}</span></span></div>
        ) : (
          AREAS.map((area) => {
            const lista = (materias.data?.items ?? []).filter((m) => m.area === area);
            if (!lista.length) return null;
            return (
              <div className="area-grupo" key={area}>
                <h3><AreaIcon nombre={area} size={17} />{area}</h3>
                <div className="materias stagger">
                  {lista.map((m) => (
                    <button key={m.id} className={`materia-opt ${areaDe(m.area)}`} disabled={m.franjasActivas === 0} onClick={() => setMateria(m)}>
                      <b>{m.nombre}</b>
                      <small>{m.franjasActivas ? `${m.docentes} docente${m.docentes === 1 ? '' : 's'} · ${m.franjasActivas === 1 ? '1 horario semanal' : `${m.franjasActivas} horarios semanales`}` : 'Sin horarios publicados'}</small>
                    </button>
                  ))}
                </div>
              </div>
            );
          })
        )}
      </section>

      {materia && (
        <div className="grid-reserva">
          <section className="panel">
            <div className="panel-head" style={{ flexWrap: 'wrap' }}>
              <h2>Elige fecha y hora</h2>
              <select className="select" style={{ width: 'auto' }} value={docente ?? ''} onChange={(e) => { setDocente(e.target.value ? Number(e.target.value) : null); setSel(null); }} aria-label="Filtrar por docente">
                <option value="">Todos los docentes</option>
                {docentes.data?.items.map((d) => <option key={d.id} value={d.id}>{d.nombre}{d.calificacionPromedio ? ` · ★ ${d.calificacionPromedio}` : ''}</option>)}
              </select>
            </div>
            {ofertas.isLoading ? <Skeleton height={340} /> : ofertas.data?.dias.length ? (
              <>
                <div className="planilla cuadricula" role="grid" aria-label="Calendario de cupos">
                  <div className="planilla-head" role="row">{DIAS.slice(1, 7).map((d) => <div key={d} role="columnheader">{d}</div>)}</div>
                  {semanas.map((semana, i) => (
                    <div className="semana" role="row" key={i}>
                      {semana.map(({ fecha, dia, oferta, fuera }) => (
                        <div key={fecha} role="gridcell" className={`dia ${fecha === hoy ? 'hoy' : ''} ${fuera ? 'fuera' : ''}`}>
                          <span className="dia-num">{dia.getDate()} {new Intl.DateTimeFormat('es-CO', { month: 'short' }).format(dia).replace('.', '')}</span>
                          {oferta?.franjas.map((f) => {
                            const elegida = sel?.fecha === fecha && sel.franja.id === f.id;
                            const llena = f.cuposDisponibles === 0;
                            const clase = f.yaReservada ? 'mia' : llena ? 'llena' : '';
                            return (
                              <button key={f.id} className={`franja-chip ${clase}`} aria-pressed={elegida} disabled={llena || f.yaReservada}
                                onClick={() => setSel({ fecha, franja: f })}
                                aria-label={`${fechaLarga(fecha)}, ${rangoHora(f.horaInicio, f.horaFin)} con ${f.docenteNombre}. ${f.yaReservada ? 'Ya reservada por ti' : llena ? 'Sin cupos' : `${f.cuposDisponibles} cupos`}`}>
                                <b>{hora12(f.horaInicio)}</b><br />
                                {f.yaReservada ? 'Tu reserva' : llena ? 'Sin cupos' : `${f.docenteNombre.split(' ')[0]} · ${f.cuposDisponibles} cupo${f.cuposDisponibles === 1 ? '' : 's'}`}
                              </button>
                            );
                          })}
                        </div>
                      ))}
                    </div>
                  ))}
                </div>
                <div className="planilla-leyenda">
                  <span><i style={{ background: 'var(--resaltador-suave)' }} />Cupo libre</span>
                  <span><i style={{ background: 'var(--resaltador)', boxShadow: 'inset 0 0 0 2px var(--tinta)' }} />Seleccionado</span>
                  <span><i style={{ background: 'var(--azul-suave)' }} />Ya reservado por ti</span>
                  <span><i style={{ background: '#eef1f6' }} />Sin cupos</span>
                </div>
              </>
            ) : (
              <Vacio icon={CalendarSearch} titulo="No hay cupos en las próximas semanas" texto="Prueba con otro docente o vuelve a consultar más tarde: los docentes publican nuevos horarios con frecuencia." />
            )}
          </section>

          <aside className="panel resumen-reserva" aria-live="polite">
            <h2 style={{ marginBottom: 14 }}>Tu reserva</h2>
            {sel ? (
              <>
                <dl>
                  <dt>Materia</dt><dd>{materia.nombre}</dd>
                  <dt>Fecha</dt><dd>{fechaLarga(sel.fecha)}</dd>
                  <dt>Hora</dt><dd>{rangoHora(sel.franja.horaInicio, sel.franja.horaFin)}</dd>
                  <dt>Docente</dt><dd>{sel.franja.docenteNombre}</dd>
                  <dt>Lugar</dt><dd style={{ display: 'flex', gap: 5, alignItems: 'center' }}>{sel.franja.modalidad === 'virtual' ? <Video size={14} /> : <MapPin size={14} />}{sel.franja.lugar}</dd>
                </dl>
                {!errores.tema && <ErrorAlert error={reservar.error} />}
                <TextArea label="¿Qué tema quieres trabajar?" value={tema} onChange={(e) => setTema(e.target.value)} maxLength={500} error={errores.tema}
                  placeholder="Ej.: integrales por partes, ejercicios 4 a 7 del taller 3" hint="Ayuda al docente a preparar la sesión." />
                <button className="btn btn-primary btn-block" disabled={tema.trim().length < 5 || reservar.isPending} onClick={() => reservar.mutate()}>
                  {reservar.isPending ? 'Reservando…' : 'Confirmar reserva'}
                </button>
              </>
            ) : <p className="muted small">Selecciona un cupo resaltado en el calendario.</p>}
          </aside>
        </div>
      )}
    </>
  );
}
