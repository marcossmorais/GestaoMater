-- Dev: eventos de exemplo no cronograma (relativos à data de hoje)
INSERT INTO evento_escolar (titulo, descricao, tipo, data_inicio, data_fim, fk_id_turma) VALUES
  ('Reunião de pais e mestres', 'Entrega dos boletins da unidade.', 'reuniao', CURRENT_DATE + 2, NULL, NULL),
  ('Feira de Ciências', 'Apresentação dos projetos no pátio.', 'evento', CURRENT_DATE + 9, CURRENT_DATE + 10, NULL),
  ('Feriado municipal', NULL, 'feriado', CURRENT_DATE + 15, NULL, NULL),
  ('Simulado ENEM', 'Simulado geral para as turmas do Ensino Médio.', 'prova', CURRENT_DATE + 5, NULL, 1),
  ('Recesso escolar', NULL, 'recesso', CURRENT_DATE + 40, CURRENT_DATE + 47, NULL),
  ('Aula de campo', 'Visita ao museu (autorização assinada).', 'evento', CURRENT_DATE, NULL, 1);
