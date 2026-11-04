import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../api/client';
import type { Tutoria } from '../api/types';
import { fechaLarga, rangoHora } from '../lib/format';
import { useToast } from '../lib/toast';
import { Dialog, ErrorAlert, Estrellas, TextArea } from './ui';

function useTutoriaMutation(path: (id: number) => string, ok: string, onDone: () => void) {
  const qc = useQueryClient();
  const toast = useToast();
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: unknown }) => api(path(id), { method: 'POST', body }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['tutorias'] });
      qc.invalidateQueries({ queryKey: ['ofertas'] });
      qc.invalidateQueries({ queryKey: ['notificaciones'] });
      toast(ok);
      onDone();
    },
  });
}

export function CancelarDialog({ t, rol, onClose }: { t: Tutoria; rol: 'estudiante' | 'docente'; onClose: () => void }) {
  const [motivo, setMotivo] = useState('');
  const m = useTutoriaMutation((id) => `/tutorias/${id}/cancelacion`, 'Tutoría cancelada', onClose);
  const requiereMotivo = rol === 'docente';
  return (
    <Dialog title="Cancelar tutoría" onClose={onClose} footer={<>
      <button className="btn btn-ghost" onClick={onClose}>Volver</button>
      <button className="btn btn-danger" disabled={m.isPending || (requiereMotivo && motivo.trim().length < 5)} onClick={() => m.mutate({ id: t.id, body: { motivo: motivo || null } })}>Cancelar tutoría</button>
    </>}>
      <p style={{ marginBottom: 14 }}>
        {t.materia.nombre} · {fechaLarga(t.fecha)}, {rangoHora(t.horaInicio, t.horaFin)}
        {rol === 'docente' ? ` con ${t.estudiante.nombre}.` : ` con ${t.docente.nombre}.`}
      </p>
      <ErrorAlert error={m.error} />
      <TextArea label={requiereMotivo ? 'Motivo (lo verá el estudiante)' : 'Motivo (opcional)'} value={motivo} onChange={(e) => setMotivo(e.target.value)} maxLength={500}
        hint={requiereMotivo ? 'Mínimo 5 caracteres.' : 'El docente recibirá un aviso y el cupo quedará libre para otro estudiante.'} />
    </Dialog>
  );
}

export function CalificarDialog({ t, onClose }: { t: Tutoria; onClose: () => void }) {
  const [valor, setValor] = useState(0);
  const [comentario, setComentario] = useState('');
  const m = useTutoriaMutation((id) => `/tutorias/${id}/calificacion`, '¡Gracias por calificar!', onClose);
  return (
    <Dialog title="Califica tu tutoría" onClose={onClose} footer={<>
      <button className="btn btn-ghost" onClick={onClose}>Más tarde</button>
      <button className="btn btn-primary" disabled={!valor || m.isPending} onClick={() => m.mutate({ id: t.id, body: { calificacion: valor, comentario: comentario || null } })}>Enviar calificación</button>
    </>}>
      <p style={{ marginBottom: 14 }}>{t.materia.nombre} con {t.docente.nombre}, {fechaLarga(t.fecha)}.</p>
      <ErrorAlert error={m.error} />
      <div className="field">
        <span style={{ fontWeight: 600, fontSize: '.88rem' }}>¿Qué tan útil fue?</span>
        <Estrellas valor={valor} onChange={setValor} size={30} />
      </div>
      <TextArea label="Comentario para el docente (opcional)" value={comentario} onChange={(e) => setComentario(e.target.value)} maxLength={1000} />
    </Dialog>
  );
}

export function CerrarDialog({ t, onClose }: { t: Tutoria; onClose: () => void }) {
  const [asistio, setAsistio] = useState(true);
  const [obs, setObs] = useState('');
  const m = useTutoriaMutation((id) => `/tutorias/${id}/cierre`, asistio ? 'Tutoría registrada como completada' : 'Inasistencia registrada', onClose);
  return (
    <Dialog title="Registrar tutoría" onClose={onClose} footer={<>
      <button className="btn btn-ghost" onClick={onClose}>Volver</button>
      <button className="btn btn-dark" disabled={m.isPending} onClick={() => m.mutate({ id: t.id, body: { asistio, observaciones: obs || null } })}>{asistio ? 'Marcar como completada' : 'Registrar inasistencia'}</button>
    </>}>
      <p style={{ marginBottom: 14 }}>{t.estudiante.nombre} · {t.materia.nombre}, {fechaLarga(t.fecha)} {rangoHora(t.horaInicio, t.horaFin)}.</p>
      <ErrorAlert error={m.error} />
      <div className="field" role="radiogroup" aria-label="Asistencia">
        <label className="check"><input type="radio" name="asistio" checked={asistio} onChange={() => setAsistio(true)} /> <span>El estudiante asistió</span></label>
        <label className="check"><input type="radio" name="asistio" checked={!asistio} onChange={() => setAsistio(false)} /> <span>El estudiante no asistió</span></label>
      </div>
      <TextArea label="Observaciones (opcional)" value={obs} onChange={(e) => setObs(e.target.value)} maxLength={1000} hint="Temas vistos, compromisos o material recomendado. El estudiante las verá en su historial." />
    </Dialog>
  );
}
