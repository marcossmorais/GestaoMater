-- Dev: dados de exemplo (rodam depois do 99_dev_seed)

-- 4. Dados Iniciais de Demonstração (Aluno 1 - Heloísa Alves Souza, Turma 1)
INSERT INTO atestado_medico (
    fk_id_matricula,
    fk_id_aluno,
    data_inicio,
    data_fim,
    dias,
    motivo,
    cid,
    medico_nome,
    medico_crm,
    instituicao_saude,
    status,
    faltas_abonadas,
    observacao,
    registrado_por
)
SELECT
    1,
    1,
    CURRENT_DATE - INTERVAL '2 days',
    CURRENT_DATE,
    3,
    'Infecção Aguda de Vias Aéreas Superiores (IVAS)',
    'J06.9',
    'Dra. Mariana Prado',
    'CRM/SP 142.890',
    'Hospital Infantil Darcy Vargas',
    'Aprovado',
    2,
    'Atestado entregue pela mãe na secretaria escolar no dia 21. Aluno liberado de atividades físicas e avaliações no período.',
    'Coordenação Pedagógica'
WHERE NOT EXISTS (
    SELECT 1 FROM atestado_medico WHERE fk_id_matricula = 1
);

-- Atualiza as faltas abonadas no registro da Unidade 1 da Aluna 1
UPDATE falta_aluno
SET faltas_abonadas = 2
WHERE id_falta = 1 AND total_faltas >= 2;
