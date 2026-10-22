-- ==============================================================================
-- Mater — Migração 08: Ocorrências Disciplinares e Pedagógicas por Aluno
-- Compatível com PostgreSQL 17 / PHP 8.2
-- ==============================================================================

CREATE TABLE IF NOT EXISTS ocorrencia_disciplinar (
    id_ocorrencia SERIAL PRIMARY KEY,
    fk_id_matricula INTEGER NOT NULL,
    fk_id_aluno INTEGER NOT NULL,
    fk_id_turma_disciplina INTEGER,
    categoria VARCHAR(30) NOT NULL DEFAULT 'Disciplinar', -- 'Disciplinar', 'Pedagógica', 'Elogio', 'Saúde'
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    gravidade VARCHAR(20) NOT NULL DEFAULT 'Média', -- 'Baixa', 'Média', 'Alta', 'Gravíssima'
    medida_tomada VARCHAR(255),
    visivel_responsavel BOOLEAN NOT NULL DEFAULT TRUE,
    requer_ciencia BOOLEAN NOT NULL DEFAULT FALSE,
    ciencia_responsavel BOOLEAN NOT NULL DEFAULT FALSE,
    ciencia_em TIMESTAMP(0) WITHOUT TIME ZONE,
    registrado_por VARCHAR(100) NOT NULL DEFAULT 'Coordenação Pedagógica',
    data_ocorrencia DATE NOT NULL DEFAULT CURRENT_DATE,
    criado_em TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ocorrencia_matricula_fkey FOREIGN KEY (fk_id_matricula) 
        REFERENCES matricula(id_matricula) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT ocorrencia_aluno_fkey FOREIGN KEY (fk_id_aluno) 
        REFERENCES aluno(id_aluno) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS ocorrencia_matricula_idx ON ocorrencia_disciplinar (fk_id_matricula);
CREATE INDEX IF NOT EXISTS ocorrencia_aluno_idx ON ocorrencia_disciplinar (fk_id_aluno);
CREATE INDEX IF NOT EXISTS ocorrencia_categoria_idx ON ocorrencia_disciplinar (categoria);
CREATE INDEX IF NOT EXISTS ocorrencia_data_idx ON ocorrencia_disciplinar (data_ocorrencia DESC);

-- Seed de Demonstração (Aluno 1 - Heloísa Alves Souza)
INSERT INTO ocorrencia_disciplinar (
    fk_id_matricula,
    fk_id_aluno,
    categoria,
    titulo,
    descricao,
    gravidade,
    medida_tomada,
    visivel_responsavel,
    requer_ciencia,
    ciencia_responsavel,
    registrado_por,
    data_ocorrencia
)
SELECT
    1,
    1,
    'Elogio',
    'Destaque na Olimpíada Interna de Redação',
    'A aluna obteve o 1º lugar na primeira etapa do concurso interno de dissertação com nota 10, demonstrando excelente domínio da norma culta e repertório sociocultural.',
    'Baixa',
    'Menção honrosa entregue em sala de aula perante a turma',
    TRUE,
    FALSE,
    FALSE,
    'Prof. Pedro Gonçalves Alves',
    CURRENT_DATE - INTERVAL '5 days'
WHERE NOT EXISTS (
    SELECT 1 FROM ocorrencia_disciplinar WHERE fk_id_matricula = 1 AND categoria = 'Elogio'
);

INSERT INTO ocorrencia_disciplinar (
    fk_id_matricula,
    fk_id_aluno,
    categoria,
    titulo,
    descricao,
    gravidade,
    medida_tomada,
    visivel_responsavel,
    requer_ciencia,
    ciencia_responsavel,
    registrado_por,
    data_ocorrencia
)
SELECT
    1,
    1,
    'Pedagógica',
    'Ausência de material de proteção em aula prática',
    'A aluna compareceu ao laboratório de ciências sem o jaleco de proteção individual obrigatório, impossibilitando sua participação na atividade prática com reagentes químicos.',
    'Baixa',
    'Orientação verbal individual e reagendamento do relatório teórico',
    TRUE,
    TRUE,
    TRUE,
    'Prof. Leonardo Teixeira Fernandes',
    CURRENT_DATE - INTERVAL '1 day'
WHERE NOT EXISTS (
    SELECT 1 FROM ocorrencia_disciplinar WHERE fk_id_matricula = 1 AND categoria = 'Pedagógica'
);
