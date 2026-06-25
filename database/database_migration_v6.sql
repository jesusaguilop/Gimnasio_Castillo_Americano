ALTER TABLE profesor_curso_asignatura
ADD COLUMN porcentaje INT NOT NULL DEFAULT 100
AFTER asignatura_id;

UPDATE profesor_curso_asignatura SET porcentaje = 100 WHERE porcentaje IS NULL;
