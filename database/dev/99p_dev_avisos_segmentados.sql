-- Mater — dados fictícios: avisos gerais para professores e responsáveis (#22)
INSERT INTO aviso_geral (aviso, tipo, fixado, publico, fk_id_administrador)
SELECT texto, tipo, fixado, publico, (SELECT MIN(id_administrador) FROM administrador)
FROM (VALUES
    ('Conselho de classe na sexta-feira, às 14h, na sala dos professores. Tragam as notas do bimestre fechadas.', 'evento', true, 'professores'),
    ('Prazo para lançar as notas do bimestre: até o dia 30.', 'urgente', false, 'professores'),
    ('Reunião de pais e mestres na próxima quarta-feira, às 19h, no auditório. Contamos com a presença da família.', 'evento', false, 'responsaveis')
) AS dados (texto, tipo, fixado, publico)
WHERE NOT EXISTS (SELECT 1 FROM aviso_geral WHERE publico IN ('professores', 'responsaveis'));
