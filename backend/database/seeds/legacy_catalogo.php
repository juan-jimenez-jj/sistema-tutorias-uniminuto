<?php

declare(strict_types=1);

/**
 * Datos del sistema heredado (Anexo_BaseDatos_Tutorias.sql) normalizados para el nuevo esquema.
 * Docentes, materias que dicta cada uno y franjas de disponibilidad reales de la sede.
 * Se cargan con: php bin/console db:seed
 */

return [
 'docentes' => [
  ['id' => 2, 'id_institucional' => 'DOC001', 'nombre' => 'Michael Stiven Pinzon Rodriguez', 'email' => 'michael.pinzon@uniminuto.edu.co', 'area' => 'Matemáticas'],
  ['id' => 3, 'id_institucional' => 'DOC002', 'nombre' => 'Oscar Javier Gracia Gonzalez', 'email' => 'oscar.gracia@uniminuto.edu.co', 'area' => 'Física'],
  ['id' => 4, 'id_institucional' => 'DOC003', 'nombre' => 'Leidy Katerine Estepa Avila', 'email' => 'leidy.estepa@uniminuto.edu.co', 'area' => 'Matemáticas'],
  ['id' => 5, 'id_institucional' => 'DOC004', 'nombre' => 'Patricia Torres', 'email' => 'patricia.torres@uniminuto.edu.co', 'area' => 'Química'],
  ['id' => 6, 'id_institucional' => 'DOC005', 'nombre' => 'Javier Orlando Ballen Novoa', 'email' => 'javier.ballen@uniminuto.edu.co', 'area' => 'Matemáticas'],
  ['id' => 7, 'id_institucional' => 'DOC006', 'nombre' => 'Wilson Ferney Pinzon Bernal', 'email' => 'wilson.pinzon@uniminuto.edu.co', 'area' => 'Física'],
  ['id' => 8, 'id_institucional' => 'DOC007', 'nombre' => 'Jonny Rafael Plazas Alvarado', 'email' => 'jonny.plazas@uniminuto.edu.co', 'area' => 'Matemáticas'],
  ['id' => 9, 'id_institucional' => 'DOC008', 'nombre' => 'Andres Felipe Rozo', 'email' => 'andres.rozo@uniminuto.edu.co', 'area' => 'Matemáticas'],
  ['id' => 10, 'id_institucional' => 'DOC009', 'nombre' => 'Hector Alirio Cuineme Mahecha', 'email' => 'hector.cuineme@uniminuto.edu.co', 'area' => 'Física'],
  ['id' => 11, 'id_institucional' => 'DOC010', 'nombre' => 'Francisco Ballen', 'email' => 'francisco.ballen@uniminuto.edu.co', 'area' => 'Matemáticas'],
 ],
 'docente_materia' => [
  [2, 2], [2, 6], [2, 7], [3, 2], [3, 19], [4, 5], [4, 6], [4, 7], [4, 8], [4, 9], [4, 10], [5, 4], [5, 23], [5, 24], [6, 11], [6, 12], [6, 13], [6, 14], [7, 20], [8, 1], [8, 10], [8, 15], [8, 16], [9, 1], [9, 2], [9, 6], [10, 3], [10, 19], [10, 20], [10, 21], [10, 22], [11, 17], [11, 18],
 ],
 'franjas' => [
  ['docente_id' => 2, 'materia_id' => 2, 'dia' => 3, 'inicio' => '17:30', 'fin' => '18:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 2, 'materia_id' => 6, 'dia' => 5, 'inicio' => '17:30', 'fin' => '19:15', 'lugar' => 'Salón B402'],
  ['docente_id' => 3, 'materia_id' => 2, 'dia' => 1, 'inicio' => '16:00', 'fin' => '17:30', 'lugar' => 'Salón B402'],
  ['docente_id' => 3, 'materia_id' => 2, 'dia' => 3, 'inicio' => '16:00', 'fin' => '17:30', 'lugar' => 'Salón B402'],
  ['docente_id' => 3, 'materia_id' => 19, 'dia' => 5, 'inicio' => '16:00', 'fin' => '17:30', 'lugar' => 'Salón B402'],
  ['docente_id' => 4, 'materia_id' => 5, 'dia' => 4, 'inicio' => '09:00', 'fin' => '09:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 4, 'materia_id' => 6, 'dia' => 5, 'inicio' => '08:00', 'fin' => '09:30', 'lugar' => 'Salón B402'],
  ['docente_id' => 4, 'materia_id' => 8, 'dia' => 4, 'inicio' => '09:45', 'fin' => '10:30', 'lugar' => 'Salón B402'],
  ['docente_id' => 4, 'materia_id' => 9, 'dia' => 5, 'inicio' => '09:30', 'fin' => '10:15', 'lugar' => 'Salón B402'],
  ['docente_id' => 4, 'materia_id' => 7, 'dia' => 5, 'inicio' => '10:15', 'fin' => '11:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 4, 'materia_id' => 10, 'dia' => 4, 'inicio' => '18:15', 'fin' => '19:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 5, 'materia_id' => 23, 'dia' => 5, 'inicio' => '15:15', 'fin' => '16:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 5, 'materia_id' => 4, 'dia' => 1, 'inicio' => '16:45', 'fin' => '18:15', 'lugar' => 'Salón B402'],
  ['docente_id' => 5, 'materia_id' => 24, 'dia' => 1, 'inicio' => '16:45', 'fin' => '18:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 6, 'materia_id' => 11, 'dia' => 1, 'inicio' => '20:00', 'fin' => '20:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 6, 'materia_id' => 12, 'dia' => 2, 'inicio' => '20:00', 'fin' => '21:30', 'lugar' => 'Salón B402'],
  ['docente_id' => 6, 'materia_id' => 13, 'dia' => 4, 'inicio' => '20:00', 'fin' => '20:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 6, 'materia_id' => 14, 'dia' => 4, 'inicio' => '20:00', 'fin' => '20:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 7, 'materia_id' => 20, 'dia' => 5, 'inicio' => '15:15', 'fin' => '16:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 8, 'materia_id' => 15, 'dia' => 1, 'inicio' => '18:15', 'fin' => '19:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 8, 'materia_id' => 10, 'dia' => 3, 'inicio' => '16:00', 'fin' => '17:30', 'lugar' => 'Salón B402'],
  ['docente_id' => 8, 'materia_id' => 16, 'dia' => 2, 'inicio' => '18:15', 'fin' => '19:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 8, 'materia_id' => 16, 'dia' => 2, 'inicio' => '19:00', 'fin' => '19:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 8, 'materia_id' => 1, 'dia' => 5, 'inicio' => '18:15', 'fin' => '19:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 9, 'materia_id' => 1, 'dia' => 1, 'inicio' => '14:00', 'fin' => '16:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 9, 'materia_id' => 1, 'dia' => 2, 'inicio' => '14:30', 'fin' => '17:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 9, 'materia_id' => 1, 'dia' => 3, 'inicio' => '14:00', 'fin' => '16:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 9, 'materia_id' => 2, 'dia' => 1, 'inicio' => '14:00', 'fin' => '16:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 9, 'materia_id' => 2, 'dia' => 2, 'inicio' => '14:30', 'fin' => '17:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 9, 'materia_id' => 2, 'dia' => 3, 'inicio' => '14:00', 'fin' => '16:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 9, 'materia_id' => 6, 'dia' => 1, 'inicio' => '14:00', 'fin' => '16:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 9, 'materia_id' => 6, 'dia' => 2, 'inicio' => '14:30', 'fin' => '17:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 9, 'materia_id' => 6, 'dia' => 3, 'inicio' => '14:00', 'fin' => '16:00', 'lugar' => 'Salón B402'],
  ['docente_id' => 10, 'materia_id' => 22, 'dia' => 1, 'inicio' => '17:00', 'fin' => '17:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 10, 'materia_id' => 3, 'dia' => 2, 'inicio' => '16:00', 'fin' => '17:30', 'lugar' => 'Salón B402'],
  ['docente_id' => 10, 'materia_id' => 19, 'dia' => 3, 'inicio' => '15:00', 'fin' => '15:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 10, 'materia_id' => 19, 'dia' => 4, 'inicio' => '15:00', 'fin' => '15:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 10, 'materia_id' => 20, 'dia' => 4, 'inicio' => '16:00', 'fin' => '17:30', 'lugar' => 'Salón B402'],
  ['docente_id' => 10, 'materia_id' => 21, 'dia' => 5, 'inicio' => '15:00', 'fin' => '15:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 11, 'materia_id' => 17, 'dia' => 2, 'inicio' => '10:00', 'fin' => '10:45', 'lugar' => 'Salón B402'],
  ['docente_id' => 11, 'materia_id' => 18, 'dia' => 1, 'inicio' => '16:45', 'fin' => '18:15', 'lugar' => 'Salón B402'],
 ],
];
