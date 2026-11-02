-- Mater — dados fictícios (só desenvolvimento): tipos nos avisos, um fixado por turma e avisos gerais da coordenação

UPDATE aviso_turma SET tipo = 'prova' WHERE tipo = 'aviso' AND aviso ILIKE '%prova%';
UPDATE aviso_turma SET tipo = 'material' WHERE tipo = 'aviso' AND aviso ILIKE '%livro%';
UPDATE aviso_turma SET tipo = 'evento' WHERE tipo = 'aviso' AND aviso ILIKE '%reforço%';

UPDATE aviso_turma SET fixado = true
WHERE id_aviso_turma IN (
    SELECT DISTINCT ON (td.fk_id_turma) a.id_aviso_turma
    FROM aviso_turma a
    INNER JOIN turma_disciplina td ON (a.fk_id_turma_disciplina = td.id_turma_disciplina)
    WHERE a.tipo = 'prova'
      AND NOT EXISTS (SELECT 1 FROM aviso_turma x INNER JOIN turma_disciplina y ON (x.fk_id_turma_disciplina = y.id_turma_disciplina) WHERE x.fixado AND y.fk_id_turma = td.fk_id_turma)
    ORDER BY td.fk_id_turma, a.id_aviso_turma
);

INSERT INTO aviso_geral (aviso, tipo, fixado, fk_id_administrador)
SELECT texto, tipo, fixado, (SELECT MIN(id_administrador) FROM administrador)
FROM (VALUES
    ('Reunião de pais e mestres na próxima quarta-feira, às 19h, no auditório.', 'evento', true),
    ('A secretaria atende em horário especial nesta semana: das 8h às 12h.', 'aviso', false),
    ('Não haverá aula na sexta-feira por causa do conselho de classe.', 'urgente', false),
    ('Calendário de provas do bimestre disponível na aba Cronograma.', 'prova', false)
) AS dados (texto, tipo, fixado)
WHERE NOT EXISTS (SELECT 1 FROM aviso_geral);
