-- Mater — índices para as listagens de "atividades recentes" (ordenadas por data de postagem)

CREATE INDEX IF NOT EXISTS nota_avaliacao_data_postagem ON nota_avaliacao (data_postagem DESC);
CREATE INDEX IF NOT EXISTS falta_aluno_data_postagem ON falta_aluno (data_postagem DESC);
CREATE INDEX IF NOT EXISTS avaliacoes_data_postagem ON avaliacoes (data_postagem DESC);
CREATE INDEX IF NOT EXISTS observacao_aluno_data_postagem ON observacao_aluno (data_postagem DESC);
CREATE INDEX IF NOT EXISTS media_disciplina_data_postagem ON media_disciplina (data_postagem DESC);
CREATE INDEX IF NOT EXISTS aviso_turma_data_postagem ON aviso_turma (data_postagem DESC);
