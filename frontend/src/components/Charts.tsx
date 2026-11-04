import { Fragment } from 'react';
import { DIAS_CORTOS } from '../lib/format';

/** Barras horizontales apiladas. Gráficas propias en SVG/CSS para no añadir dependencias. */
export function Barras({ datos, series, max }: {
  datos: { label: string; valores: number[]; total?: number }[];
  series: { nombre: string; color: string }[];
  max?: number;
}) {
  const tope = max ?? Math.max(1, ...datos.map((d) => d.valores.reduce((a, b) => a + b, 0)));
  return (
    <div>
      {series.length > 1 && (
        <div className="leyenda">{series.map((s) => <span key={s.nombre}><i style={{ background: s.color }} />{s.nombre}</span>)}</div>
      )}
      <div className="barras">
        {datos.map((d) => {
          const total = d.total ?? d.valores.reduce((a, b) => a + b, 0);
          return (
            <div className="barra" key={d.label} title={`${d.label}: ${series.map((s, i) => `${s.nombre} ${d.valores[i]}`).join(', ')}`}>
              <span className="barra-label">{d.label}</span>
              <span className="barra-track">
                {d.valores.map((v, i) => <span key={i} style={{ width: `${(v / tope) * 100}%`, background: series[i].color, animationDelay: `${i * 0.15}s` }} />)}
              </span>
              <span className="barra-valor">{total}</span>
            </div>
          );
        })}
      </div>
    </div>
  );
}

/** Columnas por mes (SVG). */
export function Columnas({ datos, series }: { datos: { label: string; valores: number[] }[]; series: { nombre: string; color: string }[] }) {
  const W = 560, H = 200, pad = 26;
  const tope = Math.max(1, ...datos.map((d) => d.valores.reduce((a, b) => a + b, 0)));
  const ancho = (W - pad) / Math.max(1, datos.length);
  const pasos = 4;
  return (
    <div>
      <div className="leyenda">{series.map((s) => <span key={s.nombre}><i style={{ background: s.color }} />{s.nombre}</span>)}</div>
      <svg viewBox={`0 0 ${W} ${H + 22}`} width="100%" role="img" aria-label="Tutorías por mes">
        {Array.from({ length: pasos + 1 }, (_, i) => {
          const y = H - (H - 10) * (i / pasos);
          return <g key={i}>
            <line x1={pad} x2={W} y1={y} y2={y} stroke="#e8edf5" strokeDasharray="3 4" />
            <text x={pad - 6} y={y + 4} fontSize="10" textAnchor="end" fill="#7a8396">{Math.round((tope * i) / pasos)}</text>
          </g>;
        })}
        {datos.map((d, i) => {
          let y = H;
          const x = pad + i * ancho + ancho * 0.2;
          return (
            <g key={d.label}>
              {d.valores.map((v, j) => {
                const h = ((H - 10) * v) / tope;
                y -= h;
                return <rect key={j} className="columna" style={{ animationDelay: `${i * 0.07}s` }} x={x} y={y} width={ancho * 0.6} height={h} fill={series[j].color} rx={j === d.valores.length - 1 ? 4 : 0}>
                  <title>{`${d.label} · ${series[j].nombre}: ${v}`}</title>
                </rect>;
              })}
              <text x={x + ancho * 0.3} y={H + 16} fontSize="11" textAnchor="middle" fill="#4b5466">{d.label}</text>
            </g>
          );
        })}
      </svg>
    </div>
  );
}

/** Mapa de calor día × hora: ¿cuándo se concentra la demanda de tutorías? */
export function MapaCalor({ datos }: { datos: { diaSemana: number; hora: number; tutorias: number }[] }) {
  const horas = datos.length ? Array.from(new Set(datos.map((d) => d.hora))).sort((a, b) => a - b) : [];
  const tope = Math.max(1, ...datos.map((d) => d.tutorias));
  const get = (dia: number, hora: number) => datos.find((d) => d.diaSemana === dia && d.hora === hora)?.tutorias ?? 0;
  if (!horas.length) return <p className="muted small">Sin datos en el periodo.</p>;
  return (
    <div className="calor" style={{ gridTemplateColumns: `44px repeat(6, 1fr)` }}>
      <span />
      {[1, 2, 3, 4, 5, 6].map((d) => <span key={d} className="calor-label">{DIAS_CORTOS[d]}</span>)}
      {horas.map((h) => (
        <Fragment key={h}>
          <span className="calor-label">{`${h}:00`}</span>
          {[1, 2, 3, 4, 5, 6].map((d) => {
            const v = get(d, h);
            const a = v / tope;
            return <span key={`${d}-${h}`} className="calor-cell" title={`${DIAS_CORTOS[d]} ${h}:00 · ${v} tutorías`}
              style={{ background: v ? `rgba(0, 85, 164, ${0.1 + a * 0.85})` : '#f1f4f9', color: a > 0.5 ? '#fff' : 'var(--tinta)' }}>{v || ''}</span>;
          })}
        </Fragment>
      ))}
    </div>
  );
}
