-- Mater — dados fictícios (só desenvolvimento): planos de aula com temas reais de cada disciplina, seguindo o horário da turma
-- Troca os planos genéricos do 99i. Últimas duas semanas: a maioria com aula registrada, alguns sem registro; próximas três semanas: planejadas.

BEGIN;

DELETE FROM plano_aula WHERE titulo LIKE 'Revisão orientada%';

CREATE TEMP TABLE tema_dev (etapa text, disciplina text, ordem int, titulo text, objetivos text, recursos text, tarefa text, atividade text) ON COMMIT DROP;

INSERT INTO tema_dev VALUES
('EM', 'Matemática', 0, 'Função do 2º grau: gráfico e raízes', 'Identificar raízes e vértice no gráfico da parábola.', 'Livro cap. 4, régua', 'Lista 3, questões 1 a 5', 'teorica'),
('EM', 'Matemática', 1, 'Exercícios de função do 2º grau', 'Resolver problemas de máximo e mínimo.', 'Lista impressa', 'Terminar a lista em casa', 'pratica'),
('EM', 'Matemática', 2, 'Progressão aritmética', 'Calcular termo geral e soma de uma PA.', 'Livro cap. 5', 'Questões 6 a 10 da p. 92', 'teorica'),
('EM', 'Matemática', 3, 'Revisão para a avaliação', 'Tirar dúvidas de função e PA.', 'Quadro e simulado', 'Estudar pelo roteiro', 'revisao'),
('EM', 'Língua Portuguesa', 0, 'Crônica: leitura e características', 'Reconhecer humor e cotidiano na crônica.', 'Texto de Rubem Braga', 'Escrever uma crônica curta', 'teorica'),
('EM', 'Língua Portuguesa', 1, 'Concordância verbal', 'Aplicar as regras de concordância.', 'Livro cap. 7', 'Exercícios da p. 110', 'teorica'),
('EM', 'Língua Portuguesa', 2, 'Redação: dissertação argumentativa', 'Estruturar introdução, argumentos e conclusão.', 'Proposta de redação', 'Primeira versão do texto', 'pratica'),
('EM', 'Língua Portuguesa', 3, 'Modernismo: Semana de 22', 'Relacionar contexto histórico e obras.', 'Slides e poemas', 'Pesquisa sobre um autor', 'documentario'),
('EM', 'História', 0, 'Era Vargas: Estado Novo', 'Explicar as mudanças políticas de 1937.', 'Livro cap. 9, mapa', 'Questionário da p. 140', 'teorica'),
('EM', 'História', 1, 'Segunda Guerra Mundial', 'Compreender causas e principais fases do conflito.', 'Documentário curto', 'Linha do tempo', 'documentario'),
('EM', 'História', 2, 'Guerra Fria', 'Comparar os blocos capitalista e socialista.', 'Mapa-múndi', 'Texto de 10 linhas', 'teorica'),
('EM', 'Geografia', 0, 'Urbanização brasileira', 'Analisar causas e problemas das metrópoles.', 'Gráficos do IBGE', 'Questões da p. 75', 'teorica'),
('EM', 'Geografia', 1, 'Climas do Brasil', 'Diferenciar os climas e seus efeitos.', 'Atlas', 'Mapa de climas', 'pratica'),
('EM', 'Geografia', 2, 'Matriz energética', 'Comparar fontes renováveis e não renováveis.', 'Infográfico', 'Pesquisa sobre a região', 'questionario'),
('EM', 'Física', 0, 'Leis de Newton', 'Aplicar a 2ª lei em situações do dia a dia.', 'Carrinho e dinamômetro', 'Exercícios 1 a 6', 'teorica'),
('EM', 'Física', 1, 'Trabalho e energia', 'Calcular trabalho e energia cinética.', 'Livro cap. 6', 'Exercícios da p. 88', 'teorica'),
('EM', 'Física', 2, 'Experimento: plano inclinado', 'Medir aceleração e comparar com a teoria.', 'Laboratório', 'Relatório em dupla', 'pratica'),
('EM', 'Química', 0, 'Ligações químicas', 'Diferenciar ligação iônica e covalente.', 'Tabela periódica', 'Lista 2', 'teorica'),
('EM', 'Química', 1, 'Balanceamento de equações', 'Balancear equações por tentativa.', 'Quadro', 'Exercícios 4 a 9', 'pratica'),
('EM', 'Química', 2, 'Soluções e concentração', 'Calcular concentração em g/L.', 'Laboratório', 'Relatório da prática', 'pratica'),
('EM', 'Biologia', 0, 'Citologia: organelas', 'Relacionar organela e função.', 'Microscópio e lâminas', 'Desenho da célula', 'pratica'),
('EM', 'Biologia', 1, 'Genética: 1ª lei de Mendel', 'Resolver cruzamentos simples.', 'Livro cap. 8', 'Exercícios da p. 130', 'teorica'),
('EM', 'Biologia', 2, 'Ecologia: cadeias alimentares', 'Montar cadeias e teias alimentares.', 'Cartões impressos', 'Questionário', 'questionario'),
('EM', 'Língua Inglesa', 0, 'Simple past: regular verbs', 'Falar sobre fatos passados.', 'Livro unit 3', 'Workbook p. 22', 'teorica'),
('EM', 'Língua Inglesa', 1, 'Reading: interpretação de texto', 'Usar estratégias de leitura.', 'Texto impresso', 'Vocabulário do texto', 'questionario'),
('EM', 'Educação Física', 0, 'Vôlei: saque e recepção', 'Praticar fundamentos em duplas.', 'Quadra e bolas', '', 'pratica'),
('EM', 'Educação Física', 1, 'Corpo e saúde: frequência cardíaca', 'Medir e interpretar a frequência cardíaca.', 'Cronômetro', 'Registro de 3 dias', 'pratica'),
('EM', 'Artes', 0, 'Arte moderna brasileira', 'Reconhecer obras de Tarsila e Portinari.', 'Projetor', 'Releitura de uma obra', 'documentario'),
('EM', 'Artes', 1, 'Perspectiva no desenho', 'Desenhar com um ponto de fuga.', 'Lápis e régua', 'Terminar o desenho', 'pratica'),
('EM', 'Filosofia', 0, 'Ética: o que é agir bem?', 'Debater dilemas morais do cotidiano.', 'Texto de apoio', 'Comentário de 5 linhas', 'teorica'),
('EM', 'Filosofia', 1, 'Platão: alegoria da caverna', 'Interpretar a alegoria.', 'Vídeo curto', 'Questionário', 'documentario'),
('EM', 'Sociologia', 0, 'Desigualdade social no Brasil', 'Interpretar dados de renda.', 'Gráficos do IBGE', 'Pesquisa em dupla', 'teorica'),
('EM', 'Sociologia', 1, 'Cidadania e direitos', 'Relacionar direitos e deveres.', 'Constituição (trechos)', 'Questionário', 'questionario'),
('EF1', 'Matemática', 0, 'Tabuada do 6 com material dourado', 'Construir a tabuada do 6.', 'Material dourado', 'Tabuada no caderno', 'pratica'),
('EF1', 'Matemática', 1, 'Problemas de adição e subtração', 'Resolver problemas com duas operações.', 'Livro p. 40', 'Problemas 1 a 3', 'teorica'),
('EF1', 'Língua Portuguesa', 0, 'Leitura: fábulas', 'Identificar a moral da história.', 'Livro de fábulas', 'Ler em casa', 'teorica'),
('EF1', 'Língua Portuguesa', 1, 'Produção de texto: bilhete', 'Escrever um bilhete com remetente e destinatário.', 'Folha pautada', 'Bilhete para a família', 'pratica'),
('EF1', 'História', 0, 'Minha família e minha rua', 'Reconhecer a própria história.', 'Fotos trazidas de casa', 'Entrevista com um familiar', 'teorica'),
('EF1', 'Geografia', 0, 'Mapa do caminho até a escola', 'Representar trajetos.', 'Papel e lápis de cor', 'Desenhar o caminho', 'pratica'),
('EF1', 'Física', 0, 'Ciências: luz e sombra', 'Observar como a sombra muda.', 'Lanterna', 'Registro no caderno', 'pratica'),
('EF1', 'Química', 0, 'Ciências: misturas do dia a dia', 'Observar misturas em água.', 'Água, sal e óleo', 'Desenho da experiência', 'pratica'),
('EF1', 'Biologia', 0, 'Partes das plantas', 'Nomear raiz, caule e folha.', 'Plantas da horta', 'Plantar um feijão', 'pratica'),
('EF1', 'Língua Inglesa', 0, 'Colors and numbers', 'Falar cores e números até 20.', 'Cartões', 'Pintar a folha', 'pratica'),
('EF1', 'Educação Física', 0, 'Circuito motor', 'Desenvolver coordenação e equilíbrio.', 'Cones e cordas', '', 'pratica'),
('EF1', 'Artes', 0, 'Colagem com papel colorido', 'Criar formas com recortes.', 'Papel e cola', 'Trazer revistas', 'pratica'),
('EF1', 'Filosofia', 0, 'Roda de conversa: amizade', 'Ouvir e respeitar opiniões.', 'Roda no pátio', '', 'teorica'),
('EF1', 'Sociologia', 0, 'Regras de convivência da turma', 'Combinar regras em grupo.', 'Cartaz', '', 'teorica');

-- cada aula do horário nos próximos dias recebe o tema seguinte da disciplina
CREATE TEMP TABLE dia_dev ON COMMIT DROP AS
SELECT DISTINCT td.id_turma_disciplina AS dc, td.fk_id_professor AS professor, curso.sigla AS etapa, disciplina.nome_disciplina AS disciplina, dia::date AS data
FROM turma_disciplina td
INNER JOIN turma ON (td.fk_id_turma = turma.id_turma)
INNER JOIN curso ON (turma.fk_id_curso = curso.id_curso)
INNER JOIN disciplina ON (td.fk_id_disciplina = disciplina.id_disciplina)
INNER JOIN grade_horario gh ON (gh.fk_id_turma_disciplina = td.id_turma_disciplina)
CROSS JOIN generate_series(CURRENT_DATE - 14, CURRENT_DATE + 21, INTERVAL '1 day') AS dia
WHERE td.fk_id_professor IS NOT NULL
  AND gh.dia_semana = EXTRACT(ISODOW FROM dia)::int + 1
  AND NOT EXISTS (SELECT 1 FROM plano_aula p WHERE p.fk_id_turma_disciplina = td.id_turma_disciplina AND p.data_aula = dia::date);

CREATE TEMP TABLE plano_dev ON COMMIT DROP AS
SELECT d.*, t.titulo, t.objetivos, t.recursos, t.tarefa, t.atividade
FROM (
    SELECT dia_dev.*, ROW_NUMBER() OVER (PARTITION BY dc ORDER BY data) - 1 AS posicao,
           COUNT(*) OVER (PARTITION BY dc) AS total
    FROM dia_dev
) d
INNER JOIN LATERAL (
    SELECT tema_dev.* FROM tema_dev
    WHERE tema_dev.etapa = d.etapa AND tema_dev.disciplina = d.disciplina
    ORDER BY tema_dev.ordem
    OFFSET d.posicao % (SELECT COUNT(*) FROM tema_dev x WHERE x.etapa = d.etapa AND x.disciplina = d.disciplina) LIMIT 1
) t ON true
WHERE (d.dc + d.posicao) % 5 <> 4 OR d.data < CURRENT_DATE;  -- uma parte das aulas futuras fica sem plano, para planejar

-- aulas já dadas: 4 de cada 5 têm registro; as demais ficam "sem registro"
CREATE TEMP TABLE aula_dev ON COMMIT DROP AS
WITH nova AS (
    INSERT INTO aula (fk_id_turma_disciplina, fk_id_professor, data_aula, observacao)
    SELECT p.dc, p.professor, p.data,
           (ARRAY['Turma participou bem.', 'Faltou tempo para corrigir os exercícios; retomar na próxima aula.', 'Conteúdo concluído conforme o planejado.', 'Alguns alunos com dúvida; vou retomar com exemplos.'])[1 + (p.dc + p.posicao) % 4]
    FROM plano_dev p
    WHERE p.data < CURRENT_DATE AND (p.dc + p.posicao) % 5 <> 0
      AND NOT EXISTS (SELECT 1 FROM aula a WHERE a.fk_id_turma_disciplina = p.dc AND a.data_aula = p.data)
    RETURNING id_aula, fk_id_turma_disciplina AS dc, data_aula AS data
)
SELECT * FROM nova;

INSERT INTO plano_aula (fk_id_turma_disciplina, fk_id_professor, fk_id_aula, data_aula, titulo, objetivos, recursos, tarefa)
SELECT p.dc, p.professor, a.id_aula, p.data, p.titulo, p.objetivos, p.recursos, NULLIF(p.tarefa, '')
FROM plano_dev p
LEFT JOIN aula_dev a ON (a.dc = p.dc AND a.data = p.data);

INSERT INTO plano_aula_atividade (fk_id_plano, tipo)
SELECT pa.id_plano, p.atividade
FROM plano_aula pa
INNER JOIN plano_dev p ON (p.dc = pa.fk_id_turma_disciplina AND p.data = pa.data_aula AND p.titulo = pa.titulo)
ON CONFLICT DO NOTHING;

INSERT INTO aula_atividade (fk_id_aula, tipo)
SELECT a.id_aula, p.atividade
FROM aula_dev a
INNER JOIN plano_dev p ON (p.dc = a.dc AND p.data = a.data)
ON CONFLICT DO NOTHING;

-- chamadas antigas (fora da janela de pendências da home), para a frequência ter base de cálculo
INSERT INTO chamada (fk_id_turma_disciplina, fk_id_unidade, fk_id_professor, data_aula, quantidade_aulas)
SELECT td.id_turma_disciplina, 1, td.fk_id_professor, CURRENT_DATE - semana.n * 7 - 15, 2
FROM turma_disciplina td
CROSS JOIN generate_series(0, 24) AS semana (n)
WHERE td.fk_id_professor IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM chamada c WHERE c.fk_id_turma_disciplina = td.id_turma_disciplina AND c.data_aula < CURRENT_DATE - 14);

COMMIT;
