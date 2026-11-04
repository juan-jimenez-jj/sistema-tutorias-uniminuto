import { describe, expect, it } from 'vitest';
import { fechaLarga, hora12, iniciales, mesCorto, rangoHora } from './format';
import { qs } from '../api/client';

describe('formato de fechas y horas (es-CO)', () => {
  it('muestra horas en formato de 12 horas', () => {
    expect(hora12('14:00')).toBe('2:00 p. m.');
    expect(hora12('09:05')).toBe('9:05 a. m.');
    expect(hora12('00:30')).toBe('12:30 a. m.');
    expect(rangoHora('18:15', '19:45')).toBe('6:15 p. m. – 7:45 p. m.');
  });

  it('nombra el día de la semana sin desfase por zona horaria', () => {
    expect(fechaLarga('2027-03-01')).toMatch(/^Lunes, 1 de marzo/);
    expect(mesCorto('2026-09')).toBe('Sept');
  });

  it('obtiene iniciales para el avatar', () => {
    expect(iniciales('Valentina Rodríguez Castro')).toBe('VR');
  });
});

describe('construcción de query strings', () => {
  it('omite valores vacíos', () => {
    expect(qs({ materiaId: 3, docenteId: null, q: '' })).toBe('?materiaId=3');
    expect(qs({})).toBe('');
  });
});
