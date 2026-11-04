const tz = 'America/Bogota';

export const DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
export const DIAS_CORTOS = ['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

function parseDate(ymd: string): Date {
  const [y, m, d] = ymd.slice(0, 10).split('-').map(Number);
  return new Date(Date.UTC(y, m - 1, d, 12));
}

export function fechaLarga(ymd: string): string {
  const s = new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' }).format(parseDate(ymd));
  return s.charAt(0).toUpperCase() + s.slice(1);
}

export function fechaCorta(ymd: string): string {
  return new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short', timeZone: 'UTC' }).format(parseDate(ymd)).replace('.', '');
}

export function fechaConAnio(ymd: string): string {
  return new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(parseDate(ymd)).replace('.', '');
}

export function mesCorto(ym: string): string {
  const s = new Intl.DateTimeFormat('es-CO', { month: 'short', timeZone: 'UTC' }).format(parseDate(ym + '-15')).replace('.', '');
  return s.charAt(0).toUpperCase() + s.slice(1);
}

export function hora12(hhmm: string): string {
  const [h, m] = hhmm.split(':').map(Number);
  const suffix = h >= 12 ? 'p. m.' : 'a. m.';
  return `${((h + 11) % 12) + 1}:${String(m).padStart(2, '0')} ${suffix}`;
}

export function rangoHora(inicio: string, fin: string): string {
  return `${hora12(inicio)} – ${hora12(fin)}`;
}

export function hace(fechaHora: string): string {
  const date = new Date(fechaHora.replace(' ', 'T') + (fechaHora.includes('T') ? '' : '-05:00'));
  const diff = (Date.now() - date.getTime()) / 1000;
  if (diff < 60) return 'hace un momento';
  if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
  if (diff < 86400) return `hace ${Math.floor(diff / 3600)} h`;
  if (diff < 86400 * 7) return `hace ${Math.floor(diff / 86400)} d`;
  return new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short', timeZone: tz }).format(date);
}

/** Tiempo que falta para una fecha ISO, en palabras ("en 3 días", "en 2 h"). */
export function faltan(iso: string): string {
  const diff = (new Date(iso).getTime() - Date.now()) / 1000;
  if (diff <= 0) return 'en curso';
  if (diff < 3600) return `en ${Math.ceil(diff / 60)} min`;
  if (diff < 86400) return `en ${Math.round(diff / 3600)} h`;
  const days = Math.round(diff / 86400);
  return days === 1 ? 'mañana' : `en ${days} días`;
}

export function horasHasta(iso: string): number {
  return (new Date(iso).getTime() - Date.now()) / 3_600_000;
}

export const ESTADOS: Record<string, string> = {
  programada: 'Programada',
  completada: 'Completada',
  cancelada: 'Cancelada',
  no_asistio: 'No asistió',
};

export function iniciales(nombre: string): string {
  return nombre.split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase();
}
