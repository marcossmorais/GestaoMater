-- Mater — schema Postgres (compatível com Supabase)
-- Gerado a partir do schema MySQL original. Não editar à mão sem atualizar as migrations.

SET client_encoding = 'UTF8';

CREATE TABLE administrador (
  id_administrador integer NOT NULL,
  nome_administrador varchar(120) NOT NULL,
  codigo_acesso bigint NOT NULL,
  fk_id_administrador_hierarquia_funcao integer NOT NULL,
  fk_id_telefone_administrador integer NOT NULL,
  fk_id_endereco_administrador integer NOT NULL,
  data_nascimento_administrador date NOT NULL,
  cpf_administrador bigint NOT NULL,
  naturalidade_administrador varchar(45) NOT NULL,
  nacionalidade_administrador varchar(45) NOT NULL,
  foto_perfil_administrador varchar(100) DEFAULT NULL,
  fk_id_sexo_administrador integer NOT NULL,
  fk_id_pcd_administrador integer NOT NULL,
  fk_id_tipo_sanguineo_administrador integer DEFAULT NULL,
  email_administrador varchar(100) DEFAULT NULL,
  fk_id_situacao_conta_administrador integer NOT NULL
);

CREATE TABLE aluno (
  id_aluno integer NOT NULL,
  nome_aluno varchar(100) NOT NULL,
  cpf_aluno bigint NOT NULL,
  fk_id_sexo_aluno integer NOT NULL,
  data_nascimento_aluno date NOT NULL,
  naturalidade_aluno varchar(45) NOT NULL,
  foto_perfil_aluno varchar(100) DEFAULT NULL,
  nacionalidade_aluno varchar(80) NOT NULL,
  nome_mae varchar(120) NOT NULL,
  nome_pai varchar(120) NOT NULL,
  fk_id_tipo_sanguineo_aluno integer NOT NULL,
  fk_id_aluno_pcd integer NOT NULL,
  fk_id_endereco_aluno integer NOT NULL,
  fk_id_telefone_aluno integer NOT NULL,
  codigo_acesso bigint NOT NULL,
  fk_id_aluno_hierarquia_funcao integer NOT NULL,
  email_aluno varchar(100) DEFAULT NULL,
  fk_id_situacao_geral_aluno integer NOT NULL,
  data_matricula_inicial timestamp(0) DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE avaliacoes (
  id_avaliacao integer NOT NULL,
  descricao_avaliacao varchar(180) NOT NULL,
  fk_id_unidade_avaliacao integer NOT NULL,
  valor_avaliacao real DEFAULT NULL,
  data_realizada timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fk_id_turma_disciplina_avaliacao integer NOT NULL,
  data_postagem timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE aviso_turma (
  id_aviso_turma integer NOT NULL,
  aviso text DEFAULT NULL,
  fk_id_turma_disciplina integer NOT NULL,
  data_postagem timestamp(0) DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cedula_turma (
  id_cedula_turma integer NOT NULL,
  cedula varchar(2) NOT NULL
);

CREATE TABLE configuracao (
  id_configuracao integer NOT NULL,
  fk_id_controle_unidade integer NOT NULL,
  fk_id_controle_rematricula integer NOT NULL
);

CREATE TABLE controle_rematricula (
  id_situacao_abertura_rematricula integer NOT NULL,
  situacao varchar(40) NOT NULL
);

CREATE TABLE controle_unidade (
  id_controle_unidade integer NOT NULL,
  situacao varchar(45) NOT NULL
);

CREATE TABLE curso (
  id_curso integer NOT NULL,
  nome_curso varchar(45) NOT NULL,
  sigla varchar(4) NOT NULL,
  fk_id_modalidade_curso integer DEFAULT NULL
);

CREATE TABLE disciplina (
  id_disciplina integer NOT NULL,
  nome_disciplina varchar(80) NOT NULL,
  fk_id_modalidade_disciplina integer NOT NULL,
  sigla_disciplina varchar(12) NOT NULL
);

CREATE TABLE endereco (
  id_endereco integer NOT NULL,
  cep bigint NOT NULL,
  bairro varchar(80) NOT NULL,
  endereco varchar(45) NOT NULL,
  uf varchar(2) NOT NULL,
  municipio varchar(85) NOT NULL
);

CREATE TABLE falta_aluno (
  id_falta integer NOT NULL,
  total_faltas integer NOT NULL,
  fk_id_turma_disciplina_falta integer NOT NULL,
  fk_id_unidade_falta integer NOT NULL,
  fk_id_matricula_falta integer NOT NULL,
  data_postagem timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE hierarquia_funcao (
  id_hierarquia_funcao integer NOT NULL,
  hierarquia_funcao varchar(45) NOT NULL
);

CREATE TABLE legenda (
  id_legenda integer NOT NULL,
  legenda varchar(45) NOT NULL,
  sigla varchar(5) NOT NULL
);

CREATE TABLE matricula (
  id_matricula integer NOT NULL,
  data_hora_matricula timestamp(0) NULL DEFAULT NULL,
  fk_id_aluno integer NOT NULL,
  fk_id_turma_matricula integer NOT NULL,
  fk_id_situacao_aluno integer NOT NULL,
  fk_id_periodo_letivo_matricula integer NOT NULL
);

CREATE TABLE media_disciplina (
  id_media_disciplina integer NOT NULL,
  fk_id_turma_disciplina integer NOT NULL,
  fk_id_matricula_media integer NOT NULL,
  nota_valor real NOT NULL,
  fk_id_legenda integer NOT NULL,
  data_postagem timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE modalidade_curso (
  id_modalidade_curso integer NOT NULL,
  modalidade_curso varchar(60) NOT NULL,
  modalidade_curso_sigla varchar(30) NOT NULL
);

CREATE TABLE modalidade_disciplina (
  id_modalidade_disciplina integer NOT NULL,
  modalidade_disciplina varchar(20) NOT NULL
);

CREATE TABLE nota_avaliacao (
  id_nota integer NOT NULL,
  fk_id_matricula_aluno integer NOT NULL,
  fk_id_avaliacao integer NOT NULL,
  valor_nota real NOT NULL,
  data_postagem timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE numero_sala_aula (
  id_numero_sala_aula integer NOT NULL,
  numero_sala_aula integer NOT NULL
);

CREATE TABLE observacao_aluno (
  id_observacao integer NOT NULL,
  fk_id_turma_disciplina_observacao integer NOT NULL,
  fk_id_unidade integer NOT NULL,
  descricao text NOT NULL,
  fk_id_matricula_observacao integer NOT NULL,
  data_postagem timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pcd (
  id_pcd integer NOT NULL,
  pcd varchar(10) NOT NULL
);

CREATE TABLE periodo_disponivel (
  id_periodo_disponivel integer NOT NULL,
  ano_letivo smallint NOT NULL
);

CREATE TABLE periodo_letivo (
  id_ano_letivo integer NOT NULL,
  fk_id_ano_letivo integer NOT NULL,
  data_inicio date NOT NULL,
  data_fim date NOT NULL,
  fk_id_situacao_periodo_letivo integer NOT NULL
);

CREATE TABLE professor (
  id_professor integer NOT NULL,
  nome_professor varchar(100) NOT NULL,
  data_hora_matricula timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fk_id_sexo_professor integer NOT NULL,
  data_nascimento_professor date NOT NULL,
  foto_perfil_professor varchar(100) DEFAULT NULL,
  cpf_professor bigint NOT NULL,
  naturalidade_professor varchar(45) NOT NULL,
  nacionalidade_professor varchar(45) NOT NULL,
  fk_id_tipo_sanguineo_professor integer NOT NULL,
  fk_id_pcd_professor integer NOT NULL,
  fk_id_endereco_professor integer NOT NULL,
  fk_id_telefone_professor integer NOT NULL,
  codigo_acesso bigint NOT NULL,
  fk_id_professor_hierarquia_funcao integer NOT NULL,
  email_professor varchar(100) DEFAULT NULL,
  fk_id_situacao_conta_professor integer NOT NULL
);

CREATE TABLE rematricula (
  id_rematricula integer NOT NULL,
  fk_id_rematricula_aluno integer NOT NULL,
  fk_id_situacao_rematricula integer NOT NULL
);

CREATE TABLE sala (
  id_sala integer NOT NULL,
  fk_id_numero_sala integer NOT NULL,
  capacidade_alunos integer NOT NULL
);

CREATE TABLE serie (
  id_serie integer NOT NULL,
  nome varchar(20) NOT NULL,
  sigla varchar(10) NOT NULL
);

CREATE TABLE sexo (
  id_sexo integer NOT NULL,
  sexo varchar(45) NOT NULL
);

CREATE TABLE situacao_aluno_ano_letivo (
  id_situacao_aluno integer NOT NULL,
  situacao_aluno varchar(45) NOT NULL
);

CREATE TABLE situacao_conta (
  id_situacao_conta integer NOT NULL,
  situacao_conta varchar(10) NOT NULL
);

CREATE TABLE situacao_geral_aluno (
  id_situacao_geral integer NOT NULL,
  situacao_geral varchar(45) NOT NULL
);

CREATE TABLE situacao_periodo_letivo (
  id_situacao_periodo_letivo integer NOT NULL,
  situacao_periodo_letivo varchar(20) NOT NULL
);

CREATE TABLE situacao_rematricula (
  id_situacao_rematricula integer NOT NULL,
  situacao varchar(45) NOT NULL
);

CREATE TABLE telefone (
  id_telefone integer NOT NULL,
  numero_telefone varchar(20) DEFAULT NULL
);

CREATE TABLE tipo_sanguineo (
  id_tipo_sanguineo integer NOT NULL,
  tipo_sanguineo varchar(40) NOT NULL
);

CREATE TABLE turma (
  id_turma integer NOT NULL,
  fk_id_turno integer NOT NULL,
  fk_id_periodo_letivo integer NOT NULL,
  fk_id_curso integer NOT NULL,
  fk_id_serie integer NOT NULL,
  fk_id_sala integer NOT NULL,
  fk_id_cedula integer NOT NULL
);

CREATE TABLE turma_disciplina (
  id_turma_disciplina integer NOT NULL,
  fk_id_disciplina integer NOT NULL,
  fk_id_turma integer NOT NULL,
  fk_id_professor integer NOT NULL
);

CREATE TABLE turno (
  id_turno integer NOT NULL,
  nome_turno varchar(45) NOT NULL
);

CREATE TABLE unidade (
  id_unidade integer NOT NULL,
  unidade varchar(45) NOT NULL
);

-- Chaves primárias
ALTER TABLE administrador ADD PRIMARY KEY (id_administrador);
ALTER TABLE aluno ADD PRIMARY KEY (id_aluno);
ALTER TABLE avaliacoes ADD PRIMARY KEY (id_avaliacao);
ALTER TABLE aviso_turma ADD PRIMARY KEY (id_aviso_turma);
ALTER TABLE cedula_turma ADD PRIMARY KEY (id_cedula_turma);
ALTER TABLE configuracao ADD PRIMARY KEY (id_configuracao);
ALTER TABLE controle_rematricula ADD PRIMARY KEY (id_situacao_abertura_rematricula);
ALTER TABLE controle_unidade ADD PRIMARY KEY (id_controle_unidade);
ALTER TABLE curso ADD PRIMARY KEY (id_curso);
ALTER TABLE disciplina ADD PRIMARY KEY (id_disciplina);
ALTER TABLE endereco ADD PRIMARY KEY (id_endereco);
ALTER TABLE falta_aluno ADD PRIMARY KEY (id_falta);
ALTER TABLE hierarquia_funcao ADD PRIMARY KEY (id_hierarquia_funcao);
ALTER TABLE legenda ADD PRIMARY KEY (id_legenda);
ALTER TABLE matricula ADD PRIMARY KEY (id_matricula);
ALTER TABLE media_disciplina ADD PRIMARY KEY (id_media_disciplina);
ALTER TABLE modalidade_curso ADD PRIMARY KEY (id_modalidade_curso);
ALTER TABLE modalidade_disciplina ADD PRIMARY KEY (id_modalidade_disciplina);
ALTER TABLE nota_avaliacao ADD PRIMARY KEY (id_nota);
ALTER TABLE numero_sala_aula ADD PRIMARY KEY (id_numero_sala_aula);
ALTER TABLE observacao_aluno ADD PRIMARY KEY (id_observacao);
ALTER TABLE pcd ADD PRIMARY KEY (id_pcd);
ALTER TABLE periodo_disponivel ADD PRIMARY KEY (id_periodo_disponivel);
ALTER TABLE periodo_letivo ADD PRIMARY KEY (id_ano_letivo);
ALTER TABLE professor ADD PRIMARY KEY (id_professor);
ALTER TABLE rematricula ADD PRIMARY KEY (id_rematricula);
ALTER TABLE sala ADD PRIMARY KEY (id_sala);
ALTER TABLE serie ADD PRIMARY KEY (id_serie);
ALTER TABLE sexo ADD PRIMARY KEY (id_sexo);
ALTER TABLE situacao_aluno_ano_letivo ADD PRIMARY KEY (id_situacao_aluno);
ALTER TABLE situacao_conta ADD PRIMARY KEY (id_situacao_conta);
ALTER TABLE situacao_geral_aluno ADD PRIMARY KEY (id_situacao_geral);
ALTER TABLE situacao_periodo_letivo ADD PRIMARY KEY (id_situacao_periodo_letivo);
ALTER TABLE situacao_rematricula ADD PRIMARY KEY (id_situacao_rematricula);
ALTER TABLE telefone ADD PRIMARY KEY (id_telefone);
ALTER TABLE tipo_sanguineo ADD PRIMARY KEY (id_tipo_sanguineo);
ALTER TABLE turma ADD PRIMARY KEY (id_turma);
ALTER TABLE turma_disciplina ADD PRIMARY KEY (id_turma_disciplina);
ALTER TABLE turno ADD PRIMARY KEY (id_turno);
ALTER TABLE unidade ADD PRIMARY KEY (id_unidade);

-- Auto incremento
ALTER TABLE administrador ALTER COLUMN id_administrador ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE aluno ALTER COLUMN id_aluno ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE avaliacoes ALTER COLUMN id_avaliacao ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE aviso_turma ALTER COLUMN id_aviso_turma ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE cedula_turma ALTER COLUMN id_cedula_turma ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE configuracao ALTER COLUMN id_configuracao ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE controle_rematricula ALTER COLUMN id_situacao_abertura_rematricula ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE controle_unidade ALTER COLUMN id_controle_unidade ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE curso ALTER COLUMN id_curso ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE disciplina ALTER COLUMN id_disciplina ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE endereco ALTER COLUMN id_endereco ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE falta_aluno ALTER COLUMN id_falta ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE hierarquia_funcao ALTER COLUMN id_hierarquia_funcao ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE legenda ALTER COLUMN id_legenda ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE matricula ALTER COLUMN id_matricula ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE media_disciplina ALTER COLUMN id_media_disciplina ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE modalidade_curso ALTER COLUMN id_modalidade_curso ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE modalidade_disciplina ALTER COLUMN id_modalidade_disciplina ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE nota_avaliacao ALTER COLUMN id_nota ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE numero_sala_aula ALTER COLUMN id_numero_sala_aula ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE observacao_aluno ALTER COLUMN id_observacao ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE pcd ALTER COLUMN id_pcd ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE periodo_disponivel ALTER COLUMN id_periodo_disponivel ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE periodo_letivo ALTER COLUMN id_ano_letivo ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE professor ALTER COLUMN id_professor ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE rematricula ALTER COLUMN id_rematricula ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE sala ALTER COLUMN id_sala ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE serie ALTER COLUMN id_serie ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE sexo ALTER COLUMN id_sexo ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE situacao_aluno_ano_letivo ALTER COLUMN id_situacao_aluno ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE situacao_conta ALTER COLUMN id_situacao_conta ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE situacao_geral_aluno ALTER COLUMN id_situacao_geral ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE situacao_periodo_letivo ALTER COLUMN id_situacao_periodo_letivo ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE situacao_rematricula ALTER COLUMN id_situacao_rematricula ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE telefone ALTER COLUMN id_telefone ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE tipo_sanguineo ALTER COLUMN id_tipo_sanguineo ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE turma ALTER COLUMN id_turma ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE turma_disciplina ALTER COLUMN id_turma_disciplina ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE turno ALTER COLUMN id_turno ADD GENERATED BY DEFAULT AS IDENTITY;
ALTER TABLE unidade ALTER COLUMN id_unidade ADD GENERATED BY DEFAULT AS IDENTITY;

-- Índices
CREATE INDEX administrador_fk_id_administrador_hierarquia_funcao_idx ON administrador (fk_id_administrador_hierarquia_funcao);
CREATE INDEX administrador_fk_id_telefone_administrador_idx ON administrador (fk_id_telefone_administrador);
CREATE INDEX administrador_fk_id_endereco_administrador_idx ON administrador (fk_id_endereco_administrador);
CREATE INDEX administrador_fk_id_pcd_administrador_idx ON administrador (fk_id_pcd_administrador);
CREATE INDEX administrador_fk_id_sexo_administrador_idx ON administrador (fk_id_sexo_administrador);
CREATE INDEX administrador_fk_id_tipo_sanguineo_administrador_idx ON administrador (fk_id_tipo_sanguineo_administrador);
CREATE INDEX administrador_fk_id_situacao_conta_administrador ON administrador (fk_id_situacao_conta_administrador);
CREATE INDEX aluno_fk_id_tipo_sanguineo_idx ON aluno (fk_id_tipo_sanguineo_aluno);
CREATE INDEX aluno_fk_id_pcd_idx ON aluno (fk_id_aluno_pcd);
CREATE INDEX aluno_fk_id_sexo_aluno_idx ON aluno (fk_id_sexo_aluno);
CREATE INDEX aluno_fk_id_endereco_aluno_idx ON aluno (fk_id_endereco_aluno);
CREATE INDEX aluno_fk_id_telefone_aluno_idx ON aluno (fk_id_telefone_aluno);
CREATE INDEX aluno_fk_id_aluno_hierarquia_funcao_idx ON aluno (fk_id_aluno_hierarquia_funcao);
CREATE INDEX aluno_fk_id_situacao_geral_aluno_idx ON aluno (fk_id_situacao_geral_aluno);
CREATE INDEX avaliacoes_id_unidade_idx ON avaliacoes (fk_id_unidade_avaliacao);
CREATE INDEX avaliacoes_id_turma_disciplina_idx ON avaliacoes (fk_id_turma_disciplina_avaliacao);
CREATE INDEX aviso_turma_fk_id_turma_disciplina ON aviso_turma (fk_id_turma_disciplina);
CREATE INDEX configuracao_fk_id_controle_unidade_idx ON configuracao (fk_id_controle_unidade);
CREATE INDEX configuracao_fk_id_rematricula_idx ON configuracao (fk_id_controle_rematricula);
CREATE INDEX curso_modalidade ON curso (fk_id_modalidade_curso);
CREATE INDEX disciplina_fk_id_modalidade_disciplina_idx ON disciplina (fk_id_modalidade_disciplina);
CREATE INDEX falta_aluno_id_turma_disciplina_idx ON falta_aluno (fk_id_turma_disciplina_falta);
CREATE INDEX falta_aluno_id_unidade_idx ON falta_aluno (fk_id_unidade_falta);
CREATE INDEX falta_aluno_id_matricula_falta_idx ON falta_aluno (fk_id_matricula_falta);
CREATE INDEX matricula_id_aluno_idx ON matricula (fk_id_aluno);
CREATE INDEX matricula_id_turma_idx ON matricula (fk_id_turma_matricula);
CREATE INDEX matricula_id_situacao_aluno_idx ON matricula (fk_id_situacao_aluno);
CREATE INDEX matricula_fk_id_periodo_letivo_matricula_idx ON matricula (fk_id_periodo_letivo_matricula);
CREATE INDEX media_disciplina_id_turma_disciplina_idx ON media_disciplina (fk_id_turma_disciplina);
CREATE INDEX media_disciplina_id_matricula_idx ON media_disciplina (fk_id_matricula_media);
CREATE INDEX media_disciplina_id_legenda_idx ON media_disciplina (fk_id_legenda);
CREATE INDEX nota_avaliacao_id_avaliacao_idx ON nota_avaliacao (fk_id_avaliacao);
CREATE INDEX nota_avaliacao_id_matricula_idx ON nota_avaliacao (fk_id_matricula_aluno);
CREATE INDEX observacao_aluno_id_turma_disciplina_idx ON observacao_aluno (fk_id_turma_disciplina_observacao);
CREATE INDEX observacao_aluno_id_unidade_idx ON observacao_aluno (fk_id_unidade);
CREATE INDEX observacao_aluno_id_matricula_idx ON observacao_aluno (fk_id_matricula_observacao);
CREATE INDEX periodo_letivo_fk_id_situacao_periodo_letivo_idx ON periodo_letivo (fk_id_situacao_periodo_letivo);
CREATE INDEX periodo_letivo_fk_id_ano_letivo_idx ON periodo_letivo (fk_id_ano_letivo);
CREATE INDEX professor_fk_id_pcd_idx ON professor (fk_id_pcd_professor);
CREATE INDEX professor_fk_id_sexo_funcionario_idx ON professor (fk_id_sexo_professor);
CREATE INDEX professor_fk_id_tipo_sanguineo_professor_idx ON professor (fk_id_tipo_sanguineo_professor);
CREATE INDEX professor_fk_id_endereco_professor_idx ON professor (fk_id_endereco_professor);
CREATE INDEX professor_fk_id_telefone_professor_idx ON professor (fk_id_telefone_professor);
CREATE INDEX professor_fk_id_professor_hierarquia_funcao_idx ON professor (fk_id_professor_hierarquia_funcao);
CREATE INDEX professor_fk_id_situacao_conta_professor ON professor (fk_id_situacao_conta_professor);
CREATE INDEX rematricula_fk_id_rematricula_aluno_idx ON rematricula (fk_id_rematricula_aluno);
CREATE INDEX rematricula_fk_id_situacao_rematricula_idx ON rematricula (fk_id_situacao_rematricula);
CREATE INDEX sala_fk_id_numero_sala_idx ON sala (fk_id_numero_sala);
CREATE INDEX turma_fk_id_periodo_letivo_idx ON turma (fk_id_periodo_letivo);
CREATE INDEX turma_id_turno_idx ON turma (fk_id_turno);
CREATE INDEX turma_id_curso_idx ON turma (fk_id_curso);
CREATE INDEX turma_id_serie_idx ON turma (fk_id_serie);
CREATE INDEX turma_fk_id_sala_idx ON turma (fk_id_sala);
CREATE INDEX turma_fk_id_cedula_idx ON turma (fk_id_cedula);
CREATE INDEX turma_disciplina_id_turma_idx ON turma_disciplina (fk_id_turma);
CREATE INDEX turma_disciplina_id_disciplina_idx ON turma_disciplina (fk_id_disciplina);
CREATE INDEX turma_disciplina_id_professor_idx ON turma_disciplina (fk_id_professor);

-- Chaves estrangeiras
ALTER TABLE administrador ADD CONSTRAINT fk_id_administrador_hierarquia_funcao FOREIGN KEY (fk_id_administrador_hierarquia_funcao) REFERENCES hierarquia_funcao (id_hierarquia_funcao) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE administrador ADD CONSTRAINT fk_id_endereco_administrador FOREIGN KEY (fk_id_endereco_administrador) REFERENCES endereco (id_endereco) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE administrador ADD CONSTRAINT fk_id_pcd_administrador FOREIGN KEY (fk_id_pcd_administrador) REFERENCES pcd (id_pcd) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE administrador ADD CONSTRAINT fk_id_sexo_administrador FOREIGN KEY (fk_id_sexo_administrador) REFERENCES sexo (id_sexo) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE administrador ADD CONSTRAINT fk_id_situacao_conta_administrador FOREIGN KEY (fk_id_situacao_conta_administrador) REFERENCES situacao_conta (id_situacao_conta);
ALTER TABLE administrador ADD CONSTRAINT fk_id_telefone_administrador FOREIGN KEY (fk_id_telefone_administrador) REFERENCES telefone (id_telefone) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE administrador ADD CONSTRAINT fk_id_tipo_sanguineo_administrador FOREIGN KEY (fk_id_tipo_sanguineo_administrador) REFERENCES tipo_sanguineo (id_tipo_sanguineo) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE aluno ADD CONSTRAINT fk_id_aluno_hierarquia_funcao FOREIGN KEY (fk_id_aluno_hierarquia_funcao) REFERENCES hierarquia_funcao (id_hierarquia_funcao) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE aluno ADD CONSTRAINT fk_id_endereco_aluno FOREIGN KEY (fk_id_endereco_aluno) REFERENCES endereco (id_endereco) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE aluno ADD CONSTRAINT fk_id_pcd_aluno FOREIGN KEY (fk_id_aluno_pcd) REFERENCES pcd (id_pcd) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE aluno ADD CONSTRAINT fk_id_sexo_aluno FOREIGN KEY (fk_id_sexo_aluno) REFERENCES sexo (id_sexo) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE aluno ADD CONSTRAINT fk_id_situacao_geral_aluno FOREIGN KEY (fk_id_situacao_geral_aluno) REFERENCES situacao_geral_aluno (id_situacao_geral) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE aluno ADD CONSTRAINT fk_id_telefone_aluno FOREIGN KEY (fk_id_telefone_aluno) REFERENCES telefone (id_telefone) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE aluno ADD CONSTRAINT fk_id_tipo_sanguineo_aluno FOREIGN KEY (fk_id_tipo_sanguineo_aluno) REFERENCES tipo_sanguineo (id_tipo_sanguineo) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE avaliacoes ADD CONSTRAINT fk_id_turma_disciplina_avaliacao FOREIGN KEY (fk_id_turma_disciplina_avaliacao) REFERENCES turma_disciplina (id_turma_disciplina) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE avaliacoes ADD CONSTRAINT fk_id_unidade_avaliacao FOREIGN KEY (fk_id_unidade_avaliacao) REFERENCES unidade (id_unidade) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE aviso_turma ADD CONSTRAINT aviso_turma_ibfk_1 FOREIGN KEY (fk_id_turma_disciplina) REFERENCES turma_disciplina (id_turma_disciplina);
ALTER TABLE configuracao ADD CONSTRAINT fk_id_controle_unidade FOREIGN KEY (fk_id_controle_unidade) REFERENCES controle_unidade (id_controle_unidade) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE configuracao ADD CONSTRAINT fk_id_rematricula FOREIGN KEY (fk_id_controle_rematricula) REFERENCES controle_rematricula (id_situacao_abertura_rematricula) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE curso ADD CONSTRAINT modalidade FOREIGN KEY (fk_id_modalidade_curso) REFERENCES modalidade_curso (id_modalidade_curso);
ALTER TABLE disciplina ADD CONSTRAINT fk_id_modalidade_disciplina FOREIGN KEY (fk_id_modalidade_disciplina) REFERENCES modalidade_disciplina (id_modalidade_disciplina) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE falta_aluno ADD CONSTRAINT id_matricula_falta FOREIGN KEY (fk_id_matricula_falta) REFERENCES matricula (id_matricula) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE falta_aluno ADD CONSTRAINT id_turma_disciplina_falta FOREIGN KEY (fk_id_turma_disciplina_falta) REFERENCES turma_disciplina (id_turma_disciplina) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE falta_aluno ADD CONSTRAINT id_unidade_falta FOREIGN KEY (fk_id_unidade_falta) REFERENCES unidade (id_unidade) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE matricula ADD CONSTRAINT fk_id_aluno FOREIGN KEY (fk_id_aluno) REFERENCES aluno (id_aluno) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE matricula ADD CONSTRAINT fk_id_periodo_letivo_matricula FOREIGN KEY (fk_id_periodo_letivo_matricula) REFERENCES periodo_letivo (id_ano_letivo) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE matricula ADD CONSTRAINT fk_id_situacao_aluno FOREIGN KEY (fk_id_situacao_aluno) REFERENCES situacao_aluno_ano_letivo (id_situacao_aluno) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE matricula ADD CONSTRAINT fk_id_turma_matricula FOREIGN KEY (fk_id_turma_matricula) REFERENCES turma (id_turma) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE media_disciplina ADD CONSTRAINT id_legenda FOREIGN KEY (fk_id_legenda) REFERENCES legenda (id_legenda) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE media_disciplina ADD CONSTRAINT id_matricula_media FOREIGN KEY (fk_id_matricula_media) REFERENCES matricula (id_matricula) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE media_disciplina ADD CONSTRAINT id_turma_disciplina FOREIGN KEY (fk_id_turma_disciplina) REFERENCES turma_disciplina (id_turma_disciplina) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE nota_avaliacao ADD CONSTRAINT id_avaliacao FOREIGN KEY (fk_id_avaliacao) REFERENCES avaliacoes (id_avaliacao) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE nota_avaliacao ADD CONSTRAINT id_matricula_aluno FOREIGN KEY (fk_id_matricula_aluno) REFERENCES matricula (id_matricula) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE observacao_aluno ADD CONSTRAINT id_matricula_observacao FOREIGN KEY (fk_id_matricula_observacao) REFERENCES matricula (id_matricula) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE observacao_aluno ADD CONSTRAINT id_turma_disciplina_observacao FOREIGN KEY (fk_id_turma_disciplina_observacao) REFERENCES turma_disciplina (id_turma_disciplina) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE observacao_aluno ADD CONSTRAINT id_unidade FOREIGN KEY (fk_id_unidade) REFERENCES unidade (id_unidade) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE periodo_letivo ADD CONSTRAINT fk_id_ano_letivo FOREIGN KEY (fk_id_ano_letivo) REFERENCES periodo_disponivel (id_periodo_disponivel) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE periodo_letivo ADD CONSTRAINT fk_id_situacao_periodo_letivo FOREIGN KEY (fk_id_situacao_periodo_letivo) REFERENCES situacao_periodo_letivo (id_situacao_periodo_letivo) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE professor ADD CONSTRAINT fk_id_endereco_professor FOREIGN KEY (fk_id_endereco_professor) REFERENCES endereco (id_endereco) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE professor ADD CONSTRAINT fk_id_pcd_professor FOREIGN KEY (fk_id_pcd_professor) REFERENCES pcd (id_pcd) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE professor ADD CONSTRAINT fk_id_professor_hierarquia_funcao FOREIGN KEY (fk_id_professor_hierarquia_funcao) REFERENCES hierarquia_funcao (id_hierarquia_funcao) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE professor ADD CONSTRAINT fk_id_sexo_professor FOREIGN KEY (fk_id_sexo_professor) REFERENCES sexo (id_sexo) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE professor ADD CONSTRAINT fk_id_situacao_conta_professor FOREIGN KEY (fk_id_situacao_conta_professor) REFERENCES situacao_conta (id_situacao_conta);
ALTER TABLE professor ADD CONSTRAINT fk_id_telefone_professor FOREIGN KEY (fk_id_telefone_professor) REFERENCES telefone (id_telefone) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE professor ADD CONSTRAINT fk_id_tipo_sanguineo_professor FOREIGN KEY (fk_id_tipo_sanguineo_professor) REFERENCES tipo_sanguineo (id_tipo_sanguineo) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE rematricula ADD CONSTRAINT fk_id_rematricula_aluno FOREIGN KEY (fk_id_rematricula_aluno) REFERENCES matricula (id_matricula) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE rematricula ADD CONSTRAINT fk_id_situacao_rematricula FOREIGN KEY (fk_id_situacao_rematricula) REFERENCES situacao_rematricula (id_situacao_rematricula) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE sala ADD CONSTRAINT fk_id_numero_sala FOREIGN KEY (fk_id_numero_sala) REFERENCES numero_sala_aula (id_numero_sala_aula) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE turma ADD CONSTRAINT fk_id_cedula FOREIGN KEY (fk_id_cedula) REFERENCES cedula_turma (id_cedula_turma) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE turma ADD CONSTRAINT fk_id_sala FOREIGN KEY (fk_id_sala) REFERENCES sala (id_sala) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE turma ADD CONSTRAINT id_ano_letivo FOREIGN KEY (fk_id_periodo_letivo) REFERENCES periodo_letivo (id_ano_letivo) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE turma ADD CONSTRAINT id_curso FOREIGN KEY (fk_id_curso) REFERENCES curso (id_curso) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE turma ADD CONSTRAINT id_serie FOREIGN KEY (fk_id_serie) REFERENCES serie (id_serie) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE turma ADD CONSTRAINT id_turno FOREIGN KEY (fk_id_turno) REFERENCES turno (id_turno) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE turma_disciplina ADD CONSTRAINT id_disciplina FOREIGN KEY (fk_id_disciplina) REFERENCES disciplina (id_disciplina) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE turma_disciplina ADD CONSTRAINT id_professor FOREIGN KEY (fk_id_professor) REFERENCES professor (id_professor) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE turma_disciplina ADD CONSTRAINT id_turma FOREIGN KEY (fk_id_turma) REFERENCES turma (id_turma) ON DELETE CASCADE ON UPDATE CASCADE;
