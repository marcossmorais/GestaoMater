-- Mater — dados fictícios para desenvolvimento/testes.
-- NÃO rodar em produção (Supabase). Carregado só pelo docker-compose local.

INSERT INTO telefone (numero_telefone) VALUES ('75999990001'), ('75999990002'), ('75999990003');

INSERT INTO endereco (cep, bairro, endereco, municipio, uf) VALUES
  (48600000, 'Centro', 'Rua das Flores 10', 'Paulo Afonso', 'BA'),
  (48600001, 'Jardim', 'Av. Brasil 200', 'Paulo Afonso', 'BA'),
  (48600002, 'Centro', 'Rua Nova 33', 'Paulo Afonso', 'BA');

INSERT INTO curso (nome_curso, sigla, fk_id_modalidade_curso) VALUES ('Ensino Médio Regular', 'EMR', 1);

INSERT INTO disciplina (nome_disciplina, fk_id_modalidade_disciplina, sigla_disciplina) VALUES
  ('Matemática', 2, 'MAT'),
  ('Português', 2, 'POR');

INSERT INTO sala (fk_id_numero_sala, capacidade_alunos) VALUES (1, 40);

-- 2026 = id 6 em periodo_disponivel
INSERT INTO periodo_letivo (fk_id_ano_letivo, data_inicio, data_fim, fk_id_situacao_periodo_letivo)
  VALUES (6, '2026-02-01', '2026-12-15', 1);

INSERT INTO turma (fk_id_turno, fk_id_periodo_letivo, fk_id_curso, fk_id_serie, fk_id_sala, fk_id_cedula)
  VALUES (1, 1, 1, 1, 1, 1);

INSERT INTO professor (nome_professor, fk_id_sexo_professor, data_nascimento_professor, foto_perfil_professor,
  cpf_professor, naturalidade_professor, nacionalidade_professor, fk_id_tipo_sanguineo_professor, fk_id_pcd_professor,
  fk_id_endereco_professor, fk_id_telefone_professor, codigo_acesso, fk_id_professor_hierarquia_funcao,
  email_professor, fk_id_situacao_conta_professor)
VALUES ('Professora Ana Teste Silva', 2, '1985-03-10', 'foto-vazia.jpg', 11122233344, 'Paulo Afonso', 'Brasileira',
  1, 1, 2, 2, 111111, 3, 'professora.teste@mater.local', 1);

INSERT INTO turma_disciplina (fk_id_disciplina, fk_id_turma, fk_id_professor) VALUES (1, 1, 1), (2, 1, 1);

INSERT INTO aluno (nome_aluno, cpf_aluno, fk_id_sexo_aluno, data_nascimento_aluno, naturalidade_aluno,
  foto_perfil_aluno, nacionalidade_aluno, nome_mae, nome_pai, fk_id_tipo_sanguineo_aluno, fk_id_aluno_pcd,
  fk_id_endereco_aluno, fk_id_telefone_aluno, codigo_acesso, fk_id_aluno_hierarquia_funcao, email_aluno,
  fk_id_situacao_geral_aluno)
VALUES ('Aluno Pedro Teste Souza', 55566677788, 1, '2010-07-20', 'Paulo Afonso', 'foto-vazia.jpg', 'Brasileiro',
  'Maria Souza', 'José Souza', 1, 1, 3, 3, 222222, 4, 'aluno.teste@mater.local', 1);

INSERT INTO matricula (data_hora_matricula, fk_id_aluno, fk_id_turma_matricula, fk_id_situacao_aluno, fk_id_periodo_letivo_matricula)
  VALUES (CURRENT_TIMESTAMP, 1, 1, 1, 1);

INSERT INTO avaliacoes (descricao_avaliacao, fk_id_unidade_avaliacao, valor_avaliacao, data_realizada, fk_id_turma_disciplina_avaliacao)
  VALUES ('Prova 1', 1, 5, '2026-03-15', 1), ('Trabalho 1', 1, 5, '2026-03-20', 1), ('Prova 1', 1, 10, '2026-03-16', 2);

INSERT INTO nota_avaliacao (fk_id_matricula_aluno, fk_id_avaliacao, valor_nota) VALUES (1, 1, 4.5), (1, 2, 4), (1, 3, 7);

INSERT INTO falta_aluno (total_faltas, fk_id_turma_disciplina_falta, fk_id_unidade_falta, fk_id_matricula_falta)
  VALUES (2, 1, 1, 1);

INSERT INTO media_disciplina (fk_id_turma_disciplina, fk_id_matricula_media, nota_valor, fk_id_legenda)
  VALUES (1, 1, 8.5, 1);

INSERT INTO observacao_aluno (fk_id_turma_disciplina_observacao, fk_id_unidade, descricao, fk_id_matricula_observacao)
  VALUES (1, 1, 'Participativo nas aulas.', 1);

INSERT INTO aviso_turma (aviso, fk_id_turma_disciplina) VALUES ('Prova de matemática na próxima semana.', 1);

INSERT INTO rematricula (fk_id_rematricula_aluno, fk_id_situacao_rematricula) VALUES (1, 1);
