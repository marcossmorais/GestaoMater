-- Mater — Migração 18: roteiro de estudo estruturado por avaliação
-- Antes era um texto livre (roteiro). Agora: assuntos (um por linha) e material/páginas; "roteiro" fica para observações.
-- Assim a coordenação consegue juntar os roteiros por bimestre/avaliação num documento único.

ALTER TABLE avaliacoes ADD COLUMN IF NOT EXISTS roteiro_assuntos text;
ALTER TABLE avaliacoes ADD COLUMN IF NOT EXISTS roteiro_material varchar(500);
COMMENT ON COLUMN avaliacoes.roteiro_assuntos IS 'Assuntos da avaliação, um por linha';
COMMENT ON COLUMN avaliacoes.roteiro_material IS 'Material de estudo: livro, páginas, exercícios';
COMMENT ON COLUMN avaliacoes.roteiro IS 'Observações do roteiro de estudo';
