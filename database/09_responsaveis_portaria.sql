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

-- Seed de Demonstração (Aluno 1 - Heloísa Alves Souza)
INSERT INTO responsavel_aluno (
    fk_id_aluno,
    nome,
    parentesco,
    cpf,
    rg,
    telefone,
    email,
    autorizado_buscar,
    responsavel_financeiro,
    responsavel_pedagogico,
    observacao_restricao
)
SELECT
    1,
    'Ana Lúcia Alves Souza',
    'Mãe',
    '234.567.890-12',
    '32.145.678-9',
    '(11) 98765-4321',
    'ana.souza@gmail.com',
    TRUE,
    TRUE,
    TRUE,
    'Responsável legal principal. Contatar primeiro em caso de emergência.'
WHERE NOT EXISTS (
    SELECT 1 FROM responsavel_aluno WHERE fk_id_aluno = 1 AND parentesco = 'Mãe'
);

INSERT INTO responsavel_aluno (
    fk_id_aluno,
    nome,
    parentesco,
    cpf,
    rg,
    telefone,
    email,
    autorizado_buscar,
    responsavel_financeiro,
    responsavel_pedagogico,
    observacao_restricao
)
SELECT
    1,
    'Dona Eunice Alves',
    'Avó',
    '112.334.556-78',
    '14.567.890-1',
    '(11) 97654-3210',
    NULL,
    TRUE,
    FALSE,
    FALSE,
    'Autorizada a buscar na saída habitual das sextas-feiras ou quando solicitada pela mãe.'
WHERE NOT EXISTS (
    SELECT 1 FROM responsavel_aluno WHERE fk_id_aluno = 1 AND parentesco = 'Avó'
);

INSERT INTO responsavel_aluno (
    fk_id_aluno,
    nome,
    parentesco,
    cpf,
    rg,
    telefone,
    email,
    autorizado_buscar,
    responsavel_financeiro,
    responsavel_pedagogico,
    observacao_restricao
)
SELECT
    1,
    'Jorge Pereira (Tio Jorge)',
    'Motorista de Van Escolar',
    '345.678.901-23',
    '21.987.654-3',
    '(11) 91234-5678',
    'van.tiojorge@transporte.com.br',
    TRUE,
    FALSE,
    FALSE,
    'Transporte Escolar Credenciado. Veículo: Van Mercedes-Benz Sprinter Branca — Placa BRA-2E19.'
WHERE NOT EXISTS (
    SELECT 1 FROM responsavel_aluno WHERE fk_id_aluno = 1 AND parentesco = 'Motorista de Van Escolar'
);
