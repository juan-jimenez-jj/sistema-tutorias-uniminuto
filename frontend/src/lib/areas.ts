import { Atom, FlaskConical, Sigma, ChartColumnBig, type LucideIcon } from 'lucide-react';

export interface AreaInfo { clase: string; icono: LucideIcon; simbolo: string; descripcion: string }

/** Identidad visual de cada área de Ciencias Básicas. */
export const AREAS: Record<string, AreaInfo> = {
  'Matemáticas': { clase: 'area-mat', icono: Sigma, simbolo: '∫', descripcion: 'Cálculo, álgebra, ecuaciones y métodos numéricos' },
  'Física': { clase: 'area-fis', icono: Atom, simbolo: 'λ', descripcion: 'Mecánica, electricidad, ondas y termodinámica' },
  'Química': { clase: 'area-qui', icono: FlaskConical, simbolo: '⌬', descripcion: 'Química general, bioquímica y materiales' },
  'Estadística': { clase: 'area-est', icono: ChartColumnBig, simbolo: 'σ', descripcion: 'Descriptiva, inferencial y aplicada' },
};

export function area(nombre: string | undefined | null): AreaInfo {
  return AREAS[nombre ?? ''] ?? AREAS['Matemáticas'];
}
