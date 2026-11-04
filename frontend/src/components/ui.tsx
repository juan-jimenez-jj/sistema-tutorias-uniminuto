import { useEffect, useId, useMemo, useRef, useState, type ReactNode, type InputHTMLAttributes, type SelectHTMLAttributes, type TextareaHTMLAttributes } from 'react';
import { X, Star, AlertCircle, type LucideIcon } from 'lucide-react';
import { ESTADOS } from '../lib/format';
import { area } from '../lib/areas';
import { ApiError } from '../api/client';

export function Field({ label, error, hint, children, id }: { label: string; error?: string; hint?: string; children: (id: string) => ReactNode; id?: string }) {
  const auto = useId();
  const fieldId = id ?? auto;
  return (
    <div className="field">
      <label htmlFor={fieldId}>{label}</label>
      {children(fieldId)}
      {error ? <span className="error" role="alert">{error}</span> : hint ? <span className="hint">{hint}</span> : null}
    </div>
  );
}

export function TextInput({ label, error, hint, ...props }: { label: string; error?: string; hint?: string } & InputHTMLAttributes<HTMLInputElement>) {
  return (
    <Field label={label} error={error} hint={hint}>
      {(id) => <input id={id} className="input" aria-invalid={!!error} {...props} />}
    </Field>
  );
}

export function SelectInput({ label, error, children, ...props }: { label: string; error?: string; children: ReactNode } & SelectHTMLAttributes<HTMLSelectElement>) {
  return (
    <Field label={label} error={error}>
      {(id) => <select id={id} className="select" aria-invalid={!!error} {...props}>{children}</select>}
    </Field>
  );
}

export function TextArea({ label, error, hint, ...props }: { label: string; error?: string; hint?: string } & TextareaHTMLAttributes<HTMLTextAreaElement>) {
  return (
    <Field label={label} error={error} hint={hint}>
      {(id) => <textarea id={id} className="textarea" aria-invalid={!!error} {...props} />}
    </Field>
  );
}

export function EstadoBadge({ estado }: { estado: string }) {
  return <span className={`badge badge-${estado}`}>{ESTADOS[estado] ?? estado}</span>;
}

export function Dialog({ title, onClose, children, footer }: { title: string; onClose: () => void; children: ReactNode; footer?: ReactNode }) {
  const ref = useRef<HTMLDivElement>(null);
  const titleId = useId();
  useEffect(() => {
    const prev = document.activeElement as HTMLElement | null;
    ref.current?.querySelector<HTMLElement>('input, textarea, select, button:not([data-close])')?.focus();
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onClose();
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('keydown', onKey);
      prev?.focus();
    };
  }, [onClose]);
  return (
    <div className="overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="dialog" role="dialog" aria-modal="true" aria-labelledby={titleId} ref={ref}>
        <div className="dialog-head">
          <h2 id={titleId}>{title}</h2>
          <button className="icon-btn" data-close onClick={onClose} aria-label="Cerrar"><X size={18} /></button>
        </div>
        <div className="dialog-body">{children}</div>
        {footer && <div className="dialog-foot">{footer}</div>}
      </div>
    </div>
  );
}

export function Vacio({ icon: Icon, titulo, texto, accion }: { icon: LucideIcon; titulo: string; texto?: string; accion?: ReactNode }) {
  return (
    <div className="vacio">
      <span className="vacio-icono"><Icon size={34} strokeWidth={1.75} /></span>
      <h3>{titulo}</h3>
      {texto && <p>{texto}</p>}
      {accion}
    </div>
  );
}

export function ErrorAlert({ error }: { error: unknown }) {
  if (!error) return null;
  const message = error instanceof ApiError ? error.message : 'No se pudo completar la operación.';
  return <div className="alert alert-error" role="alert"><AlertCircle size={18} /><span>{message}</span></div>;
}

export function Skeleton({ height = 80, count = 1 }: { height?: number; count?: number }) {
  return (
    <div style={{ display: 'grid', gap: 10 }} aria-busy="true" aria-label="Cargando">
      {Array.from({ length: count }, (_, i) => <div key={i} className="skeleton" style={{ height }} />)}
    </div>
  );
}

export function Estrellas({ valor, onChange, size = 22 }: { valor: number; onChange?: (v: number) => void; size?: number }) {
  return (
    <span className="estrellas" role={onChange ? 'radiogroup' : 'img'} aria-label={`${valor} de 5 estrellas`}>
      {[1, 2, 3, 4, 5].map((n) =>
        onChange ? (
          <button type="button" key={n} className={n <= valor ? 'on' : ''} onClick={() => onChange(n)} role="radio" aria-checked={n === valor} aria-label={`${n} estrella${n > 1 ? 's' : ''}`}>
            <Star size={size} fill="currentColor" />
          </button>
        ) : (
          <Star key={n} size={size} fill="currentColor" style={{ color: n <= valor ? 'var(--resaltador)' : '#cfd6e3' }} />
        ),
      )}
    </span>
  );
}

export function Pager({ page, pageSize, total, onPage }: { page: number; pageSize: number; total: number; onPage: (p: number) => void }) {
  const pages = Math.max(1, Math.ceil(total / pageSize));
  if (total <= pageSize) return null;
  return (
    <div className="pager">
      <span>{(page - 1) * pageSize + 1}–{Math.min(page * pageSize, total)} de {total}</span>
      <button className="btn btn-ghost btn-sm" disabled={page <= 1} onClick={() => onPage(page - 1)}>Anterior</button>
      <button className="btn btn-ghost btn-sm" disabled={page >= pages} onClick={() => onPage(page + 1)}>Siguiente</button>
    </div>
  );
}

export function fieldErrors(error: unknown): Record<string, string> {
  return error instanceof ApiError ? error.fields : {};
}

/** Encabezado de página con degradado institucional. */
export function PageHero({ icon: Icon, titulo, texto, acciones }: { icon: LucideIcon; titulo: ReactNode; texto?: ReactNode; acciones?: ReactNode }) {
  return (
    <header className="hero-pagina">
      <span className="hero-icono" aria-hidden><Icon size={28} /></span>
      <div className="hero-texto">
        <h1>{titulo}</h1>
        {texto && <p>{texto}</p>}
      </div>
      {acciones && <div className="hero-acciones">{acciones}</div>}
    </header>
  );
}

/** Número que "cuenta" hasta su valor al aparecer. */
export function CountUp({ valor, decimales = 0 }: { valor: number; decimales?: number }) {
  const [actual, setActual] = useState(0);
  useEffect(() => {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { setActual(valor); return; }
    let frame = 0;
    const inicio = performance.now();
    const dur = 900;
    const tick = (t: number) => {
      const p = Math.min(1, (t - inicio) / dur);
      setActual(valor * (1 - Math.pow(1 - p, 3)));
      if (p < 1) frame = requestAnimationFrame(tick);
    };
    frame = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(frame);
  }, [valor]);
  return <>{actual.toLocaleString('es-CO', { minimumFractionDigits: decimales, maximumFractionDigits: decimales })}</>;
}

export function Stat({ icon: Icon, valor, decimales = 0, sufijo, etiqueta, color }: { icon: LucideIcon; valor: number | null; decimales?: number; sufijo?: string; etiqueta: string; color: string }) {
  return (
    <div className={`stat c-${color}`}>
      <span className="chip-icono" aria-hidden><Icon size={22} /></span>
      <span className="stat-texto">
        <b>{valor === null ? '—' : <><CountUp valor={valor} decimales={decimales} />{sufijo}</>}</b>
        <span>{etiqueta}</span>
      </span>
    </div>
  );
}

export function AreaIcon({ nombre, size = 20 }: { nombre: string | undefined | null; size?: number }) {
  const a = area(nombre);
  const Icon = a.icono;
  return <span className={`area-icono ${a.clase}`} aria-hidden><Icon size={size} /></span>;
}

/** Lluvia de confeti para celebrar una reserva (desactivada si el usuario prefiere menos movimiento). */
export function Confeti() {
  const piezas = useMemo(() => Array.from({ length: 70 }, (_, i) => ({
    left: Math.random() * 100,
    delay: Math.random() * 0.6,
    dx: `${(Math.random() - 0.5) * 240}px`,
    rot: `${Math.random() * 720 - 360}deg`,
    color: ['#ffc72c', '#1a6fc4', '#16a34a', '#0ea5e9', '#f97316', '#7c3aed'][i % 6],
  })), []);
  return (
    <div className="confeti" aria-hidden>
      {piezas.map((p, i) => (
        <i key={i} style={{ left: `${p.left}%`, background: p.color, animationDelay: `${p.delay}s`, ['--dx' as string]: p.dx, ['--rot' as string]: p.rot }} />
      ))}
    </div>
  );
}

/** Revela elementos con la clase .revelar al entrar en pantalla. */
export function useRevelar() {
  useEffect(() => {
    const els = document.querySelectorAll('.revelar');
    if (!('IntersectionObserver' in window)) { els.forEach((e) => e.classList.add('visible')); return; }
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
      if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
    }), { threshold: 0.15 });
    els.forEach((e) => io.observe(e));
    return () => io.disconnect();
  }, []);
}
