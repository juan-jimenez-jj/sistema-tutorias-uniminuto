import type { ReactNode } from 'react';
import { Clock, MapPin, Video, User, GraduationCap } from 'lucide-react';
import type { Tutoria } from '../api/types';
import { DIAS_CORTOS, rangoHora } from '../lib/format';
import { EstadoBadge } from './ui';
import { area } from '../lib/areas';

/** Fila de agenda reutilizada por estudiante, docente y coordinación. */
export function TutoriaItem({ t, ver, acciones, extra }: { t: Tutoria; ver: 'estudiante' | 'docente'; acciones?: ReactNode; extra?: ReactNode }) {
  const d = new Date(t.fecha + 'T12:00:00');
  const dow = ((d.getDay() + 6) % 7) + 1;
  const pasada = t.estado !== 'programada';
  return (
    <article className={`agenda-item ${area(t.materia.area).clase}`}>
      <div className={`fecha-tab ${pasada ? 'pasada' : ''}`} aria-hidden>
        <small>{DIAS_CORTOS[dow]}</small>
        <b>{d.getDate()}</b>
        <small>{new Intl.DateTimeFormat('es-CO', { month: 'short' }).format(d).replace('.', '')}</small>
      </div>
      <div className="agenda-main">
        <div style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
          <h3>{t.materia.nombre}</h3>
          <EstadoBadge estado={t.estado} />
        </div>
        <div className="agenda-meta">
          <span><Clock size={14} />{rangoHora(t.horaInicio, t.horaFin)}</span>
          <span>{t.modalidad === 'virtual' ? <Video size={14} /> : <MapPin size={14} />}{t.lugar}</span>
          {ver === 'estudiante'
            ? <span><User size={14} />{t.docente.nombre}</span>
            : <span><GraduationCap size={14} />{t.estudiante.nombre} · {t.estudiante.programa}</span>}
          <span className="muted">{t.codigo}</span>
        </div>
        <p className="agenda-tema">{t.tema}</p>
        {extra}
      </div>
      {acciones && <div className="agenda-actions">{acciones}</div>}
    </article>
  );
}
