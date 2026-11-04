import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { api, setUnauthorizedHandler } from '../api/client';
import type { Rol, Usuario } from '../api/types';

interface AuthState {
  usuario: Usuario | null;
  cargando: boolean;
  login: (email: string, password: string) => Promise<Usuario>;
  logout: () => Promise<void>;
  refrescar: () => Promise<void>;
}

const AuthContext = createContext<AuthState | null>(null);

export const HOME: Record<Rol, string> = {
  estudiante: '/estudiante',
  docente: '/docente',
  administrador: '/admin',
};

export function AuthProvider({ children }: { children: ReactNode }) {
  const [usuario, setUsuario] = useState<Usuario | null>(null);
  const [cargando, setCargando] = useState(true);
  const queryClient = useQueryClient();

  const refrescar = useCallback(async () => {
    try {
      const { usuario } = await api<{ usuario: Usuario }>('/auth/yo');
      setUsuario(usuario);
    } catch {
      setUsuario(null);
    } finally {
      setCargando(false);
    }
  }, []);

  useEffect(() => {
    refrescar();
    // Si cualquier petición recibe 401 (sesión expirada) se vuelve a la pantalla de acceso.
    setUnauthorizedHandler(() => {
      setUsuario(null);
      queryClient.clear();
    });
  }, [refrescar, queryClient]);

  const login = useCallback(async (email: string, password: string) => {
    const { usuario } = await api<{ usuario: Usuario }>('/auth/login', { method: 'POST', body: { email, password } });
    queryClient.clear();
    setUsuario(usuario);
    return usuario;
  }, [queryClient]);

  const logout = useCallback(async () => {
    await api('/auth/logout', { method: 'POST' }).catch(() => undefined);
    queryClient.clear();
    setUsuario(null);
  }, [queryClient]);

  const value = useMemo(() => ({ usuario, cargando, login, logout, refrescar }), [usuario, cargando, login, logout, refrescar]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth debe usarse dentro de AuthProvider');
  return ctx;
}

export function useUsuario(): Usuario {
  const { usuario } = useAuth();
  if (!usuario) throw new Error('Sin sesión');
  return usuario;
}
