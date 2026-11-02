-- Mater — Migração 22: atestado ou justificativa enviados pelo aluno
-- O aluno envia pela aba Frequência (status Pendente); o admin aceita e abona as faltas do período, ou recusa com motivo.
-- anexo_privado: o arquivo fica fora de public/ e só é entregue por rota com permissão (dados de saúde de menores).

ALTER TABLE atestado_medico ADD COLUMN IF NOT EXISTS tipo varchar(20) NOT NULL DEFAULT 'atestado' CHECK (tipo IN ('atestado', 'justificativa'));
ALTER TABLE atestado_medico ADD COLUMN IF NOT EXISTS enviado_pelo_aluno boolean NOT NULL DEFAULT false;
ALTER TABLE atestado_medico ADD COLUMN IF NOT EXISTS anexo_privado boolean NOT NULL DEFAULT false;
ALTER TABLE atestado_medico ADD COLUMN IF NOT EXISTS resposta varchar(255);
ALTER TABLE atestado_medico ADD COLUMN IF NOT EXISTS analisado_em timestamp(0);

CREATE INDEX IF NOT EXISTS atestado_pendente_idx ON atestado_medico (criado_em DESC) WHERE status = 'Pendente';
