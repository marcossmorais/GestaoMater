-- ==============================================================================
-- Mater — Migração 09: Gestão de Responsáveis e Autorizados para Portaria
-- Compatível com PostgreSQL 17 / PHP 8.2
-- ==============================================================================

CREATE TABLE IF NOT EXISTS responsavel_aluno (
    id_responsavel SERIAL PRIMARY KEY,
    fk_id_aluno INTEGER NOT NULL,
    nome VARCHAR(120) NOT NULL,
    parentesco VARCHAR(50) NOT NULL, -- 'Mãe', 'Pai', 'Avô/Avó', 'Tio(a)', 'Motorista de Van', 'Outro Autorizado'
    cpf VARCHAR(20),
    rg VARCHAR(30),
    telefone VARCHAR(30) NOT NULL,
    email VARCHAR(100),
    autorizado_buscar BOOLEAN NOT NULL DEFAULT TRUE,
    responsavel_financeiro BOOLEAN NOT NULL DEFAULT FALSE,
    responsavel_pedagogico BOOLEAN NOT NULL DEFAULT FALSE,
    observacao_restricao TEXT,
    foto_documento VARCHAR(255),
    criado_em TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT responsavel_aluno_fkey FOREIGN KEY (fk_id_aluno)
        REFERENCES aluno(id_aluno) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS responsavel_aluno_idx ON responsavel_aluno (fk_id_aluno);
CREATE INDEX IF NOT EXISTS responsavel_autorizado_idx ON responsavel_aluno (autorizado_buscar);
