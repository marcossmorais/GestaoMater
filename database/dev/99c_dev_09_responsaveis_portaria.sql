-- Dev: dados de exemplo (rodam depois do 99_dev_seed)

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
