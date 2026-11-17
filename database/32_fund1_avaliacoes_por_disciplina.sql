-- Mater — Migração 32: quantas avaliações (AVs) cada disciplina tem por bimestre, por etapa.
--
-- Regra da escola para o Fund. 1 (nov/2026): nota do bimestre = média das AVs; média 7.
--   3 AVs: Língua Portuguesa, Matemática, História, Geografia, Ciências
--   2 AVs: Artes, Educação Física, Língua Inglesa, Língua Espanhola, Filosofia
-- A regra vale pelo NOME da disciplina (sem diferenciar maiúsculas), então vale também para disciplina cadastrada
-- depois pelo admin (ex.: Língua Espanhola). Disciplina sem linha aqui segue a regra geral
-- (regra_aprovacao.avaliacoes_por_bimestre). Robótica (parecer, sem nota) fica para depois.

CREATE TABLE IF NOT EXISTS avaliacoes_disciplina_etapa (
  etapa varchar(10) NOT NULL CHECK (etapa IN ('iniciais', 'finais', 'medio')),
  disciplina varchar(80) NOT NULL CHECK (disciplina = LOWER(disciplina)),
  avaliacoes_por_bimestre smallint NOT NULL CHECK (avaliacoes_por_bimestre BETWEEN 1 AND 10),
  PRIMARY KEY (etapa, disciplina)
);

INSERT INTO avaliacoes_disciplina_etapa (etapa, disciplina, avaliacoes_por_bimestre) VALUES
  ('iniciais', 'língua portuguesa', 3), ('iniciais', 'matemática', 3), ('iniciais', 'história', 3),
  ('iniciais', 'geografia', 3), ('iniciais', 'ciências', 3),
  ('iniciais', 'artes', 2), ('iniciais', 'educação física', 2), ('iniciais', 'língua inglesa', 2),
  ('iniciais', 'língua espanhola', 2), ('iniciais', 'filosofia', 2)
ON CONFLICT (etapa, disciplina) DO NOTHING;

-- Instrumentos avaliativos do Fund. 1 (lista consolidada da escola) entram ao lado dos tipos já usados no Médio
-- (App/Models/TeacherStudent/Gradebook.php: EXAM_TYPES e EXAM_TYPES_INICIAIS)
ALTER TABLE avaliacao_tipo DROP CONSTRAINT IF EXISTS avaliacao_tipo_tipo_check;
ALTER TABLE avaliacao_tipo ADD CONSTRAINT avaliacao_tipo_tipo_check CHECK (tipo IN (
  'prova', 'teste', 'atividade', 'tarefa', 'trabalho', 'apresentacao', 'projeto', 'pesquisa', 'redacao', 'pratica', 'participacao', 'outro',
  'prova_escrita', 'oral', 'trabalho_pesquisa', 'seminario', 'simulado', 'projeto_pedagogico', 'producao_textual', 'lista_exercicios',
  'atividade_pratica', 'relatorio', 'portfolio', 'debate', 'estudo_caso', 'audiovisual', 'mapa_conceitual', 'autoavaliacao', 'atividade_sala'
));

-- Quantas AVs a turma_disciplina tem por bimestre: a da etapa + disciplina ou, sem ela, a regra geral
CREATE OR REPLACE FUNCTION avaliacoes_exigidas(p_turma_disciplina integer) RETURNS smallint AS $$
  SELECT COALESCE(
    (SELECT a.avaliacoes_por_bimestre
     FROM turma_disciplina td
     INNER JOIN turma t ON (t.id_turma = td.fk_id_turma)
     INNER JOIN curso c ON (c.id_curso = t.fk_id_curso)
     INNER JOIN disciplina d ON (d.id_disciplina = td.fk_id_disciplina)
     INNER JOIN avaliacoes_disciplina_etapa a ON (a.etapa = c.etapa AND a.disciplina = LOWER(TRIM(d.nome_disciplina)))
     WHERE td.id_turma_disciplina = p_turma_disciplina),
    (SELECT avaliacoes_por_bimestre FROM regra_aprovacao WHERE id_regra_aprovacao = 1),
    3
  )::smallint
$$ LANGUAGE sql STABLE;
