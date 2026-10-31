-- Dev: dados fictícios no formato do MATER — 3 avaliações de 0 a 10 por bimestre, chamadas "1ª/2ª/3ª avaliação".
-- O seed antigo tinha "Prova 1ª unidade" (6 pontos) e "Trabalho 1ª unidade" (4 pontos): as notas são reescaladas para 0 a 10.

UPDATE nota_avaliacao n
   SET valor_nota = ROUND((n.valor_nota / a.valor_avaliacao * 10)::numeric * 2) / 2
  FROM avaliacoes a
 WHERE a.id_avaliacao = n.fk_id_avaliacao AND a.valor_avaliacao IS DISTINCT FROM 10 AND a.valor_avaliacao > 0;

UPDATE avaliacoes SET valor_avaliacao = 10 WHERE valor_avaliacao IS DISTINCT FROM 10;

UPDATE avaliacoes a
   SET descricao_avaliacao = o.ordem || 'ª avaliação'
  FROM (SELECT id_avaliacao, ROW_NUMBER() OVER (PARTITION BY fk_id_turma_disciplina_avaliacao, fk_id_unidade_avaliacao
                                                ORDER BY data_realizada, id_avaliacao) AS ordem
          FROM avaliacoes WHERE NOT recuperacao) o
 WHERE a.id_avaliacao = o.id_avaliacao AND a.descricao_avaliacao IS DISTINCT FROM o.ordem || 'ª avaliação';

UPDATE avaliacoes SET descricao_avaliacao = 'Recuperação' WHERE recuperacao AND descricao_avaliacao <> 'Recuperação';
