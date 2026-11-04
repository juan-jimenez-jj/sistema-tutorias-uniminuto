export type Rol = 'estudiante' | 'docente' | 'administrador';
export type EstadoTutoria = 'programada' | 'completada' | 'cancelada' | 'no_asistio';

export interface Usuario {
  id: number;
  idInstitucional: string;
  nombre: string;
  email: string;
  rol: Rol;
  programaAcademico: string | null;
  estado: 'activo' | 'inactivo';
  ultimoAcceso: string | null;
  creadoEn: string;
}

export interface UsuarioAdmin extends Omit<Usuario, 'creadoEn'> {
  creadoEn: string;
  materias: string[];
  materiaIds: number[];
}

export interface Materia {
  id: number;
  codigo: string;
  nombre: string;
  descripcion: string | null;
  area: 'Matemáticas' | 'Física' | 'Química' | 'Estadística';
  estado: 'activa' | 'inactiva';
  docentes: number;
  franjasActivas: number;
}

export interface DocenteMateria {
  id: number;
  nombre: string;
  area: string | null;
  franjasActivas: number;
  calificacionPromedio: number | null;
  tutoriasCompletadas: number;
}

export interface FranjaOferta {
  id: number;
  docenteId: number;
  docenteNombre: string;
  diaSemana: number;
  horaInicio: string;
  horaFin: string;
  lugar: string;
  modalidad: 'presencial' | 'virtual';
  cupo: number;
  cuposDisponibles: number;
  yaReservada: boolean;
}

export interface DiaOferta {
  fecha: string;
  diaSemana: number;
  cuposTotales: number;
  franjas: FranjaOferta[];
}

export interface Ofertas {
  desde: string;
  hasta: string;
  dias: DiaOferta[];
}

export interface Tutoria {
  id: number;
  codigo: string;
  estado: EstadoTutoria;
  fecha: string;
  horaInicio: string;
  horaFin: string;
  iniciaEn: string;
  yaInicio: boolean;
  lugar: string;
  modalidad: 'presencial' | 'virtual';
  tema: string;
  materia: { id: number; nombre: string; codigo: string; area: string };
  docente: { id: number; nombre: string; email: string };
  estudiante: { id: number; nombre: string; idInstitucional: string; email: string; programa: string | null };
  canceladaPor: Rol | null;
  motivoCancelacion: string | null;
  canceladaEn: string | null;
  observacionesDocente: string | null;
  completadaEn: string | null;
  calificacion: number | null;
  comentarioEstudiante: string | null;
  creadoEn: string;
}

export interface Pagina<T> {
  items: T[];
  total: number;
  page: number;
  pageSize: number;
}

export interface EstadisticasEstudiante {
  total: number;
  completadas: number;
  proximas: number;
  canceladas: number;
  inasistencias: number;
  porCalificar: number;
  calificacionPromedio: number | null;
  horasAcompanamiento: number;
  porMateria: { materia: string; total: number; completadas: number }[];
}

export interface EstadisticasDocente {
  hoy: number;
  proximos7Dias: number;
  pendientesCierre: number;
  completadas: number;
  inasistencias: number;
  estudiantesAtendidos: number;
  calificacionPromedio: number | null;
  calificaciones: number;
  franjasActivas: number;
}

export interface Franja {
  id: number;
  materiaId: number;
  materiaNombre: string;
  materiaCodigo: string;
  diaSemana: number;
  diaNombre: string;
  horaInicio: string;
  horaFin: string;
  lugar: string;
  modalidad: 'presencial' | 'virtual';
  cupo: number;
  activa: boolean;
  reservasFuturas: number;
}

export interface Notificacion {
  id: number;
  tipo: string;
  titulo: string;
  mensaje: string;
  enlace: string | null;
  leida: boolean;
  creadoEn: string;
}

export interface Reporte {
  totales: {
    tutorias: number;
    programadas: number;
    completadas: number;
    canceladas: number;
    inasistencias: number;
    estudiantesAtendidos: number;
    docentesActivos: number;
    calificacionPromedio: number | null;
    tutoriasCalificadas: number;
    horasAcompanamiento: number;
    tasaCancelacion: number;
    tasaAsistencia: number | null;
  };
  porMes: { mes: string; completadas: number; canceladas: number; programadas: number; inasistencias: number }[];
  porMateria: { materiaId: number; materia: string; area: string; total: number; completadas: number; canceladas: number; calificacion: number | null }[];
  porDocente: { docenteId: number; docente: string; total: number; completadas: number; inasistencias: number; canceladasPorDocente: number; estudiantes: number; calificacion: number | null }[];
  porPrograma: { programa: string; estudiantes: number; tutorias: number }[];
  mapaCalor: { diaSemana: number; hora: number; tutorias: number }[];
  periodo: { desde: string; hasta: string };
}

export interface RegistroAuditoria {
  id: number;
  accion: string;
  entidad: string;
  entidadId: string | null;
  detalle: unknown;
  ip: string | null;
  usuario: string | null;
  rol: Rol | null;
  fecha: string;
}
