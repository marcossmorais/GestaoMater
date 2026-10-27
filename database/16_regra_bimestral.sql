-- Mater — Migração 16: regra de avaliação do MATER (confirmada pela direção)
-- 4 bimestres · 3 avaliações por bimestre · nota do bimestre = média aritmética (0 a 10)
-- Bimestre abaixo de 7 tem recuperação; vale a maior nota entre o bimestre e a recuperação.
-- Média do ano = soma dos 4 bimestres ÷ 4 (precisa de 7). Frequência não entra no cálculo das notas.

-- 4º bimestre (a tabela legada chama de "unidade")
INSERT INTO unidade (id_unidade, unidade) VALUES (4, '4') ON CONFLICT DO NOTHING;

-- controle de lançamento: 1..N bimestres abertos; "Todas" passa a ser 4
UPDATE controle_unidade SET situacao = 'Até a Terceira' WHERE id_controle_unidade = 3 AND situacao = 'Todas';
INSERT INTO controle_unidade (id_controle_unidade, situacao) VALUES (4, 'Todas') ON CONFLICT DO NOTHING;
SELECT setval(pg_get_serial_sequence('controle_unidade', 'id_controle_unidade'), (SELECT MAX(id_controle_unidade) FROM controle_unidade));
UPDATE configuracao SET fk_id_controle_unidade = 4 WHERE fk_id_controle_unidade = 3;

-- regra
ALTER TABLE regra_aprovacao ADD COLUMN IF NOT EXISTS avaliacoes_por_bimestre smallint NOT NULL DEFAULT 3 CHECK (avaliacoes_por_bimestre BETWEEN 1 AND 10);
ALTER TABLE regra_aprovacao ADD COLUMN IF NOT EXISTS nota_recuperacao numeric(4,1) NOT NULL DEFAULT 7;
UPDATE regra_aprovacao SET total_unidades = 4, pontos_por_unidade = 10, media_aprovacao = 7, avaliacoes_por_bimestre = 3, nota_recuperacao = 7,
       atualizado_em = CURRENT_TIMESTAMP
 WHERE id_regra_aprovacao = 1;
COMMENT ON COLUMN regra_aprovacao.frequencia_minima IS 'Não usada no cálculo de notas (frequência é acompanhada à parte).';

-- recuperação do bimestre: uma avaliação especial por turma/disciplina e bimestre
ALTER TABLE avaliacoes ADD COLUMN IF NOT EXISTS recuperacao boolean NOT NULL DEFAULT false;
CREATE UNIQUE INDEX IF NOT EXISTS avaliacoes_uma_recuperacao_idx
  ON avaliacoes (fk_id_turma_disciplina_avaliacao, fk_id_unidade_avaliacao) WHERE recuperacao;
