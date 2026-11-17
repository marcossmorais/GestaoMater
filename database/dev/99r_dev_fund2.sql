-- Mater — dados fictícios (só desenvolvimento): segmento Fund. 2 com uma turma vazia (6º A, vespertino), para
-- testar o cadastro de turmas e alunos do Fund. 2 pelo admin. Também alinha o Fund. 1 às séries da etapa (migração 33):
-- no banco novo o seed cria a turma do Fund. 1 depois da migração, ainda com série do Médio.

UPDATE turma t
SET fk_id_serie = f.id_serie
FROM curso c, serie s, serie f
WHERE c.id_curso = t.fk_id_curso
  AND s.id_serie = t.fk_id_serie
  AND c.etapa IN ('iniciais', 'finais')
  AND s.etapa = 'medio'
  AND f.sigla = regexp_replace(s.sigla, '\D', '', 'g') || 'º';

-- o seed do Fund. 1 (99d) cria o curso 2 com id fixo e a sequência fica para trás: acerta antes (vale também
-- para o admin cadastrar segmento pela tela no dev)
SELECT setval(pg_get_serial_sequence('curso', 'id_curso'), (SELECT MAX(id_curso) FROM curso));

INSERT INTO curso (nome_curso, sigla, fk_id_modalidade_curso, etapa)
SELECT 'Fundamental II', 'EF2', (SELECT MIN(fk_id_modalidade_curso) FROM curso), 'finais'
WHERE NOT EXISTS (SELECT 1 FROM curso WHERE etapa = 'finais');

INSERT INTO turma (fk_id_turno, fk_id_sala, fk_id_periodo_letivo, fk_id_cedula, fk_id_curso, fk_id_serie)
SELECT turno.id_turno, sala.id_sala, periodo.id_ano_letivo, cedula.id_cedula_turma, curso.id_curso, serie.id_serie
FROM curso
CROSS JOIN (SELECT id_turno FROM turno WHERE nome_turno = 'Vespertino') turno
CROSS JOIN (SELECT id_ano_letivo FROM periodo_letivo WHERE fk_id_situacao_periodo_letivo = 1 ORDER BY id_ano_letivo DESC LIMIT 1) periodo
CROSS JOIN (SELECT id_cedula_turma FROM cedula_turma WHERE cedula = 'A') cedula
CROSS JOIN (SELECT id_serie FROM serie WHERE sigla = '6º') serie
CROSS JOIN LATERAL (
  -- sala sem turma no vespertino do período
  SELECT s.id_sala FROM sala s
  WHERE NOT EXISTS (SELECT 1 FROM turma x WHERE x.fk_id_sala = s.id_sala AND x.fk_id_turno = turno.id_turno
                    AND x.fk_id_periodo_letivo = periodo.id_ano_letivo)
  ORDER BY s.id_sala LIMIT 1
) sala
WHERE curso.etapa = 'finais'
  AND NOT EXISTS (SELECT 1 FROM turma x WHERE x.fk_id_curso = curso.id_curso);
