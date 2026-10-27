-- Dev: dados de exemplo (rodam depois do 99_dev_seed)

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
