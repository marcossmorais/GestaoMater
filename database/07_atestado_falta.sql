-- ==============================================================================
-- Mater — Migração 07: Justificativa e Abono de Faltas (Atestados Médicos)
-- Compatível com PostgreSQL 17 / PHP 8.2
-- ==============================================================================

-- 1. Coluna de faltas abonadas na tabela legada falta_aluno
ALTER TABLE falta_aluno
ADD COLUMN IF NOT EXISTS faltas_abonadas INTEGER NOT NULL DEFAULT 0;

-- 2. Tabela de Atestados Médicos e Justificativas de Falta
CREATE TABLE IF NOT EXISTS atestado_medico (
    id_atestado SERIAL PRIMARY KEY,
    fk_id_matricula INTEGER NOT NULL,
    fk_id_aluno INTEGER NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    dias INTEGER NOT NULL DEFAULT 1,
    motivo VARCHAR(255) NOT NULL,
    cid VARCHAR(30),
    medico_nome VARCHAR(150),
    medico_crm VARCHAR(50),
    instituicao_saude VARCHAR(150),
    status VARCHAR(20) NOT NULL DEFAULT 'Aprovado',
    faltas_abonadas INTEGER NOT NULL DEFAULT 0,
    observacao TEXT,
    anexo_documento VARCHAR(255),
    registrado_por VARCHAR(100) DEFAULT 'Secretaria Escolar',
    criado_em TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT atestado_matricula_fkey FOREIGN KEY (fk_id_matricula) 
        REFERENCES matricula(id_matricula) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT atestado_aluno_fkey FOREIGN KEY (fk_id_aluno) 
        REFERENCES aluno(id_aluno) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT atestado_datas_check CHECK (data_fim >= data_inicio)
);

-- 3. Índices de alta performance
CREATE INDEX IF NOT EXISTS atestado_matricula_idx ON atestado_medico (fk_id_matricula);
CREATE INDEX IF NOT EXISTS atestado_aluno_idx ON atestado_medico (fk_id_aluno);
CREATE INDEX IF NOT EXISTS atestado_periodo_idx ON atestado_medico (data_inicio, data_fim);
CREATE INDEX IF NOT EXISTS atestado_status_idx ON atestado_medico (status);
