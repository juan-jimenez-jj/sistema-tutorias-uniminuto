import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import { BookOpenCheck, CalendarCheck2, ChevronDown, GraduationCap, Lock, Mail, MailCheck, MapPin, MousePointerClick, Presentation, ShieldCheck, type LucideIcon } from 'lucide-react';
import { HOME, useAuth } from '../lib/auth';
import { AREAS } from '../lib/areas';
import { CountUp, ErrorAlert, SelectInput, TextInput, fieldErrors, useRevelar } from '../components/ui';
import { api } from '../api/client';
import { useToast } from '../lib/toast';

const PROGRAMAS = ['Ingeniería de Sistemas', 'Ingeniería Industrial', 'Ingeniería Civil', 'Administración de Empresas', 'Administración Financiera', 'Contaduría Pública', 'Psicología', 'Licenciatura en Matemáticas', 'Trabajo Social'];

type Perfil = 'estudiante' | 'docente' | 'coordinacion';

const PERFILES: Record<Perfil, { titulo: string; texto: string; icono: LucideIcon; clase: string; email: string; password: string }> = {
  estudiante: { titulo: 'Estudiante', texto: 'Reserva y califica tus tutorías', icono: GraduationCap, clase: 'rol-estudiante-c', email: 'estudiante@uniminuto.edu.co', password: 'Estudiante2026*' },
  docente: { titulo: 'Docente', texto: 'Publica horarios y registra asistencia', icono: Presentation, clase: 'rol-docente-c', email: 'andres.rozo@uniminuto.edu.co', password: 'Docente2026*' },
  coordinacion: { titulo: 'Coordinación', texto: 'Usuarios, materias y reportes', icono: ShieldCheck, clase: 'rol-coordinacion-c', email: 'admin@uniminuto.edu.co', password: 'Admin2026*' },
};

const SIMBOLOS: [string, string, string, string, string][] = [
  // símbolo, top, left, tamaño, retraso
  ['∫', '14%', '8%', '4.5rem', '0s'], ['π', '88%', '3%', '3.2rem', '-2s'], ['Σ', '22%', '52%', '3.6rem', '-4s'],
  ['√x', '76%', '40%', '2.6rem', '-1s'], ['⚛', '12%', '84%', '3.4rem', '-3s'], ['λ', '70%', '90%', '3rem', '-5s'],
  ['∞', '6%', '38%', '2.4rem', '-6s'], ['Δ', '84%', '70%', '2.8rem', '-2.5s'],
];

function Marca() {
  return (
    <span className="marca">
      <span className="marca-logo"><img src="/logo_md.png" alt="" /></span>
      <span className="marca-texto">
        <strong>UNIMINUTO</strong>
        <span>Corporación Universitaria Minuto de Dios</span>
      </span>
    </span>
  );
}

function Resumen() {
  const { data } = useQuery({
    queryKey: ['publico', 'resumen'],
    queryFn: () => api<{ materias: number; docentes: number; tutoriasCompletadas: number; satisfaccion: number }>('/publico/resumen'),
    staleTime: 300_000,
  });
  if (!data) return null;
  return (
    <div className="cifras-portada">
      <div><b><CountUp valor={data.tutoriasCompletadas} /></b><span>tutorías realizadas</span></div>
      <div><b><CountUp valor={data.docentes} /></b><span>docentes</span></div>
      <div><b><CountUp valor={data.materias} /></b><span>materias</span></div>
      {data.satisfaccion > 0 && <div><b><CountUp valor={data.satisfaccion} decimales={1} /></b><span>de satisfacción (1 a 5)</span></div>}
    </div>
  );
}

/** Portada institucional compartida por inicio de sesión y registro. */
function Portada({ perfil, onPerfil, children }: { perfil?: Perfil; onPerfil?: (p: Perfil) => void; children: ReactNode }) {
  useRevelar();
  return (
    <div className="portada">
      <section className="portada-hero">
        <span className="blob blob-1" /><span className="blob blob-2" /><span className="blob blob-3" />
        <span className="malla" />
        {SIMBOLOS.map(([s, top, left, size, delay]) => (
          <span key={s} className="simbolo" aria-hidden style={{ top, left, fontSize: size, animationDelay: delay }}>{s}</span>
        ))}

        <nav className="portada-nav">
          <Marca />
          <span className="portada-sede"><MapPin size={15} />Sede Zipaquirá · Ciencias Básicas</span>
        </nav>

        <div className="portada-cuerpo">
          <div className="portada-titulo anim-entrar">
            <h1>UNIMINUTO <span className="acento">Tutorías</span></h1>
            <p>Acompañamiento académico en Ciencias Básicas: encuentra el horario de tu docente, reserva tu cupo en segundos y recibe la confirmación al instante.</p>
            {onPerfil && (
              <div className="roles stagger" role="group" aria-label="Elige tu perfil">
                {(Object.keys(PERFILES) as Perfil[]).map((p) => {
                  const { titulo, texto, icono: Icon, clase } = PERFILES[p];
                  return (
                    <button key={p} type="button" className={`rol-card ${clase}`} aria-pressed={perfil === p} onClick={() => onPerfil(p)}>
                      <span className="chip-icono"><Icon size={24} /></span>
                      <span><b>{titulo}</b><span>{texto}</span></span>
                    </button>
                  );
                })}
              </div>
            )}
            <Resumen />
          </div>
          <div className="portada-tarjeta">{children}</div>
        </div>
        <a href="#como-funciona" className="scroll-hint">Así funciona<ChevronDown size={18} /></a>
      </section>

      <section className="seccion" id="como-funciona">
        <div className="seccion-titulo revelar">
          <h2>Tu tutoría en tres pasos</h2>
          <p>Sin formularios en papel ni filas en coordinación.</p>
        </div>
        <ol className="pasos-portada">
          {[
            { icono: BookOpenCheck, color: 'azul', titulo: 'Elige la materia', texto: 'Mira qué docentes la atienden, su calificación y sus horarios de la semana.' },
            { icono: MousePointerClick, color: 'amarillo', titulo: 'Toma un cupo', texto: 'Los horarios libres de las próximas cuatro semanas aparecen en un calendario. Un clic y listo.' },
            { icono: MailCheck, color: 'verde', titulo: 'Recibe la confirmación', texto: 'Te llega un correo y una notificación con el código, el salón y el docente.' },
          ].map(({ icono: Icon, color, titulo, texto }, i) => (
            <li key={titulo} className={`revelar c-${color}`} style={{ transitionDelay: `${i * 0.1}s` }}>
              <span className="chip-icono"><Icon size={26} /></span>
              <h3>{titulo}</h3>
              <p>{texto}</p>
            </li>
          ))}
        </ol>
      </section>

      <section className="seccion" style={{ paddingTop: 0 }}>
        <div className="seccion-titulo revelar">
          <h2>Cuatro áreas, un solo lugar</h2>
          <p>Todas las materias de Ciencias Básicas de la sede.</p>
        </div>
        <div className="areas-portada">
          {Object.entries(AREAS).map(([nombre, a], i) => {
            const Icon = a.icono;
            return (
              <div key={nombre} className={`area-card ${a.clase} revelar`} style={{ transitionDelay: `${i * 0.08}s` }}>
                <span className="simbolo-area" aria-hidden>{a.simbolo}</span>
                <span className="chip-icono"><Icon size={22} /></span>
                <b>{nombre}</b>
                <span>{a.descripcion}</span>
              </div>
            );
          })}
        </div>
      </section>

      <footer className="portada-footer">
        <Marca />
        <p>Sistema de Gestión de Tutorías · Ingeniería de Sistemas, UNIMINUTO Zipaquirá. Tus datos se tratan conforme a la Ley 1581 de 2012.</p>
      </footer>
    </div>
  );
}

export function Login() {
  const { usuario, login } = useAuth();
  const navigate = useNavigate();
  const [perfil, setPerfil] = useState<Perfil>('estudiante');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const mutation = useMutation({
    mutationFn: () => login(email, password),
    onSuccess: (u) => navigate(HOME[u.rol], { replace: true }),
  });
  useEffect(() => { mutation.reset(); }, [perfil]); // eslint-disable-line react-hooks/exhaustive-deps
  if (usuario) return <Navigate to={HOME[usuario.rol]} replace />;

  const p = PERFILES[perfil];
  const Icon = p.icono;
  const demo = import.meta.env.DEV || import.meta.env.VITE_DEMO === '1';
  const submit = (e: FormEvent) => { e.preventDefault(); mutation.mutate(); };

  return (
    <Portada perfil={perfil} onPerfil={setPerfil}>
      <form className={`caja ${p.clase}`} onSubmit={submit} noValidate>
        <div className="tarjeta-rol cambio-rol" key={perfil}>
          <span className="chip-icono"><Icon size={24} /></span>
          <div>
            <h2>Hola, {p.titulo.toLowerCase()}</h2>
            <p className="sub">Ingresa con tu correo institucional.</p>
          </div>
        </div>
        <ErrorAlert error={mutation.error} />
        <div className="field">
          <label htmlFor="email">Correo institucional</label>
          <div className="input-icono"><Mail size={17} /><input id="email" className="input" type="email" autoComplete="username" placeholder="nombre.apellido@uniminuto.edu.co" value={email} onChange={(e) => setEmail(e.target.value)} required /></div>
        </div>
        <div className="field">
          <label htmlFor="password">Contraseña</label>
          <div className="input-icono"><Lock size={17} /><input id="password" className="input" type="password" autoComplete="current-password" value={password} onChange={(e) => setPassword(e.target.value)} required /></div>
        </div>
        <button className="btn btn-dark btn-block btn-lg" disabled={mutation.isPending || !email || !password}>
          {mutation.isPending ? 'Verificando…' : 'Entrar'}
        </button>
        {perfil === 'estudiante' && <p className="small muted" style={{ marginTop: 14, textAlign: 'center' }}>¿Primera vez? <Link to="/registro">Crea tu cuenta de estudiante</Link></p>}
        {demo && (
          <div className="demo">
            <p>Cuenta de demostración de {p.titulo.toLowerCase()}</p>
            <button type="button" className="btn btn-ghost btn-sm" onClick={() => { setEmail(p.email); setPassword(p.password); }}>Usar cuenta demo</button>
          </div>
        )}
      </form>
    </Portada>
  );
}

export function Registro() {
  const navigate = useNavigate();
  const toast = useToast();
  const [form, setForm] = useState({ idInstitucional: '', nombre: '', email: '', password: '', confirmar: '', programaAcademico: '', aceptaTratamientoDatos: false });
  const [local, setLocal] = useState<Record<string, string>>({});
  const set = (k: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [k]: e.target.value });

  const mutation = useMutation({
    mutationFn: () => api('/auth/registro', { method: 'POST', body: form }),
    onSuccess: () => { toast('Cuenta creada. Ya puedes iniciar sesión.'); navigate('/login'); },
  });
  const errors = { ...fieldErrors(mutation.error), ...local };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (form.password !== form.confirmar) { setLocal({ confirmar: 'Las contraseñas no coinciden.' }); return; }
    setLocal({});
    mutation.mutate();
  };

  return (
    <Portada>
      <form className="caja rol-estudiante-c" onSubmit={submit} noValidate>
        <div className="tarjeta-rol">
          <span className="chip-icono"><CalendarCheck2 size={24} /></span>
          <div>
            <h2>Crea tu cuenta</h2>
            <p className="sub">Para estudiantes. Los docentes reciben su cuenta de coordinación.</p>
          </div>
        </div>
        {!Object.keys(errors).length && <ErrorAlert error={mutation.error} />}
        <div className="row">
          <TextInput label="ID institucional" value={form.idInstitucional} onChange={set('idInstitucional')} error={errors.idInstitucional} autoComplete="off" />
          <SelectInput label="Programa" value={form.programaAcademico} onChange={set('programaAcademico')} error={errors.programaAcademico}>
            <option value="">Elige…</option>
            {PROGRAMAS.map((p) => <option key={p}>{p}</option>)}
          </SelectInput>
        </div>
        <TextInput label="Nombre completo" value={form.nombre} onChange={set('nombre')} error={errors.nombre} autoComplete="name" />
        <TextInput label="Correo institucional" type="email" value={form.email} onChange={set('email')} error={errors.email} autoComplete="email" placeholder="nombre.apellido@uniminuto.edu.co" />
        <div className="row">
          <TextInput label="Contraseña" type="password" value={form.password} onChange={set('password')} error={errors.password} hint="8+ caracteres, letras y números" autoComplete="new-password" />
          <TextInput label="Confirmar" type="password" value={form.confirmar} onChange={set('confirmar')} error={errors.confirmar} autoComplete="new-password" />
        </div>
        <label className="check" style={{ marginBottom: 6 }}>
          <input type="checkbox" checked={form.aceptaTratamientoDatos} onChange={(e) => setForm({ ...form, aceptaTratamientoDatos: e.target.checked })} />
          <span>Autorizo a UNIMINUTO a tratar mis datos personales para gestionar mis tutorías, conforme a la Ley 1581 de 2012.</span>
        </label>
        {errors.aceptaTratamientoDatos && <p className="small" style={{ color: 'var(--rojo)', marginBottom: 10 }}>{errors.aceptaTratamientoDatos}</p>}
        <button className="btn btn-dark btn-block btn-lg" style={{ marginTop: 14 }} disabled={mutation.isPending}>{mutation.isPending ? 'Creando cuenta…' : 'Crear cuenta'}</button>
        <p className="small muted" style={{ marginTop: 14, textAlign: 'center' }}>¿Ya tienes cuenta? <Link to="/login">Inicia sesión</Link></p>
      </form>
    </Portada>
  );
}
