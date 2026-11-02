# Dados de referência

## `bncc-habilidades.tsv`

Habilidades da BNCC (Ensino Fundamental e Médio) vigentes, uma por linha: `código<TAB>texto`.
Usado pela busca de habilidade no diário de aula (`App/Models/TeacherStudent/Bncc.php`).

Fonte: [bncc.dev](https://github.com/bncc-dev/bncc-dados) (mantido pela Profy), arquivos `derivados/csv/habilidades-ef.csv` e `habilidades-em.csv`.
Licença dos dados: [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/deed.pt-br) — crédito: **bncc.dev (mantido pela Profy)**.
O texto normativo da BNCC é ato oficial e não tem proteção autoral (Lei 9.610/1998, art. 8º, IV).

Para atualizar: baixar os dois CSVs e manter só `codigo` e `texto` das linhas com `vigencia_status = vigente`.
