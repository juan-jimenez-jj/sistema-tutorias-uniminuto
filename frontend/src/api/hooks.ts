import { useQuery } from '@tanstack/react-query';
import { api, qs } from './client';
import type { Pagina, Tutoria } from './types';

export interface FiltrosTutorias {
  alcance?: 'proximas' | 'historial' | 'pendientes_cierre';
  estado?: string;
  materiaId?: number;
  q?: string;
  page?: number;
  pageSize?: number;
}

export function useTutorias(filtros: FiltrosTutorias) {
  return useQuery({
    queryKey: ['tutorias', filtros],
    queryFn: () => api<Pagina<Tutoria>>('/tutorias' + qs({ ...filtros })),
  });
}

export function useEstadisticas<T>() {
  return useQuery({ queryKey: ['tutorias', 'estadisticas'], queryFn: () => api<T>('/tutorias/estadisticas') });
}
