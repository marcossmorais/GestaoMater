"""
Gera database/dev/99_dev_seed.sql (escola fictícia) e docs/USUARIOS_TESTE.md.

    docker run --rm -v "$PWD":/w -w /w python:3.12-alpine python -I database/dev/generate_dev_seed.py

Determinístico (seed fixa): rodar de novo gera exatamente os mesmos dados.
E-mails usam o sufixo +clerk_test, que no Clerk em modo desenvolvimento aceita
o código de verificação 424242 sem enviar e-mail de verdade.
"""
import random
import unicodedata
from datetime import date, timedelta

random.seed(2026)

N_TEACHERS = 14
N_STUDENTS = 300
EMAIL_DOMAIN = "example.com"
CLERK_TEST_CODE = "424242"
SUGGESTED_PASSWORD = "Mater@Teste2026"

FIRST_M = ["Miguel", "Arthur", "Gael", "Heitor", "Theo", "Davi", "Gabriel", "Bernardo", "Samuel", "João",
           "Pedro", "Lucas", "Matheus", "Rafael", "Gustavo", "Enzo", "Lorenzo", "Felipe", "Bruno", "Caio",
           "Daniel", "Eduardo", "Henrique", "Igor", "Leonardo", "Murilo", "Nicolas", "Otávio", "Vinícius", "Yuri"]
FIRST_F = ["Helena", "Alice", "Laura", "Maria", "Valentina", "Heloísa", "Cecília", "Júlia", "Sophia", "Manuela",
           "Isabela", "Lívia", "Beatriz", "Clara", "Mariana", "Ana", "Gabriela", "Larissa", "Letícia", "Camila",
           "Fernanda", "Giovanna", "Isadora", "Lara", "Luana", "Nicole", "Rebeca", "Sarah", "Vitória", "Yasmin"]
LAST = ["Silva", "Santos", "Oliveira", "Souza", "Rodrigues", "Ferreira", "Alves", "Pereira", "Lima", "Gomes",
        "Costa", "Ribeiro", "Martins", "Carvalho", "Almeida", "Lopes", "Soares", "Fernandes", "Vieira", "Barbosa",
        "Rocha", "Dias", "Nascimento", "Andrade", "Moreira", "Nunes", "Marques", "Machado", "Mendes", "Freitas",
        "Cardoso", "Ramos", "Gonçalves", "Santana", "Teixeira", "Araújo", "Cavalcanti", "Monteiro", "Moura", "Batista"]
BAIRROS = ["Centro", "Jardim Bahia", "Tancredo Neves", "Barroca", "Clériston Andrade", "General Dutra",
           "Vila Poty", "Alves de Souza", "Panorama", "Perpétuo Socorro"]
RUAS = ["Rua São Jorge", "Av. Getúlio Vargas", "Rua da Alegria", "Rua Santa Rita", "Av. Apolônio Sales",
        "Rua do Rosário", "Rua Sete de Setembro", "Rua das Palmeiras", "Rua Bahia", "Rua Pernambuco"]

# (nome, sigla, quantos professores)
DISCIPLINES = [
    ("Língua Portuguesa", "LP", 2), ("Matemática", "MAT", 2), ("História", "HIS", 1), ("Geografia", "GEO", 1),
    ("Física", "FIS", 1), ("Química", "QUI", 1), ("Biologia", "BIO", 1), ("Língua Inglesa", "ING", 1),
    ("Educação Física", "EDF", 1), ("Artes", "ART", 1), ("Filosofia", "FIL", 1), ("Sociologia", "SOC", 1),
]
assert sum(d[2] for d in DISCIPLINES) == N_TEACHERS

# 9 turmas: 1ª/2ª/3ª série x A/B/C. A e B de manhã, C à tarde
CLASSES = [(serie, cedula, 1 if cedula < 3 else 2) for serie in (1, 2, 3) for cedula in (1, 2, 3)]
CEDULA = {1: "A", 2: "B", 3: "C"}
TURNO = {1: "Matutino", 2: "Vespertino"}


def q(value):
    if value is None:
        return "NULL"
    if isinstance(value, (int, float)):
        return str(value)
    return "'" + str(value).replace("'", "''") + "'"


def slug(text):
    text = unicodedata.normalize("NFKD", text).encode("ascii", "ignore").decode()
    return "".join(c for c in text.lower() if c.isalnum() or c == ".")


def cpf():
    digits = [random.randint(0, 9) for _ in range(9)]
    for size in (9, 10):
        total = sum(d * (size + 1 - i) for i, d in enumerate(digits[:size]))
        dv = (total * 10) % 11
        digits.append(0 if dv == 10 else dv)
    return int("".join(map(str, digits)))


used_names, used_codes, used_cpfs = set(), set(), set()


def person(sex):
    while True:
        first = random.choice(FIRST_M if sex == 1 else FIRST_F)
        middle, last = random.sample(LAST, 2)
        name = f"{first} {middle} {last}"
        if name not in used_names:
            used_names.add(name)
            return name


def access_code():
    while True:
        code = random.randint(100000, 999999)
        if code not in used_codes:
            used_codes.add(code)
            return code


def unique_cpf():
    while True:
        value = cpf()
        if value not in used_cpfs:
            used_cpfs.add(value)
            return value


def birth(min_age, max_age):
    start = date(2026 - max_age, 1, 1)
    return start + timedelta(days=random.randint(0, (max_age - min_age) * 365))


sql = [
    "-- Mater — escola fictícia para desenvolvimento/testes.",
    "-- GERADO por database/dev/generate_dev_seed.py. NÃO rodar em produção (Supabase).",
    "",
]
phones, addresses = [], []


def contact():
    phones.append(f"7598{random.randint(1000000, 9999999)}")
    addresses.append((48600000 + random.randint(0, 999), random.choice(BAIRROS),
                      f"{random.choice(RUAS)} {random.randint(1, 999)}", "Paulo Afonso", "BA"))
    return len(phones) + 1  # +1: telefone/endereço 1 já existem no seed base (admin)


# --- admin padrão ganha e-mail de teste do Clerk
admin_email = f"admin+clerk_test@{EMAIL_DOMAIN}"
sql.append(f"UPDATE administrador SET email_administrador = {q(admin_email)} WHERE id_administrador = 1;\n")

# --- estrutura da escola
sql.append("INSERT INTO curso (nome_curso, sigla, fk_id_modalidade_curso) VALUES ('Ensino Médio', 'EM', 1);")
sql.append("INSERT INTO disciplina (nome_disciplina, fk_id_modalidade_disciplina, sigla_disciplina) VALUES\n  "
           + ",\n  ".join(f"({q(n)}, 2, {q(s)})" for n, s, _ in DISCIPLINES) + ";")
sql.append("INSERT INTO sala (fk_id_numero_sala, capacidade_alunos) VALUES\n  "
           + ",\n  ".join(f"({i}, 40)" for i in range(1, len(CLASSES) + 1)) + ";")
sql.append("-- 2026 = id 6 em periodo_disponivel")
sql.append("INSERT INTO periodo_letivo (fk_id_ano_letivo, data_inicio, data_fim, fk_id_situacao_periodo_letivo) "
           "VALUES (6, '2026-02-02', '2026-12-11', 1);")
sql.append("INSERT INTO turma (fk_id_turno, fk_id_periodo_letivo, fk_id_curso, fk_id_serie, fk_id_sala, fk_id_cedula) VALUES\n  "
           + ",\n  ".join(f"({turno}, 1, 1, {serie}, {i}, {cedula})" for i, (serie, cedula, turno) in enumerate(CLASSES, 1)) + ";")
sql.append("")

# --- professores
teachers = []  # (id, nome, email, codigo, disciplina)
teacher_rows = []
discipline_teachers = {}
tid = 0
for d_index, (d_name, _, count) in enumerate(DISCIPLINES, 1):
    for _ in range(count):
        tid += 1
        sex = random.choice((1, 2))
        name = person(sex)
        first, last = name.split()[0], name.split()[-1]
        email = f"{slug(first)}.{slug(last)}{tid:02d}+clerk_test@{EMAIL_DOMAIN}"
        code = access_code()
        c = contact()
        teacher_rows.append(
            f"({q(name)}, {sex}, {q(birth(28, 60).isoformat())}, 'foto-vazia.jpg', {unique_cpf()}, 'Paulo Afonso', "
            f"{q('Brasileiro' if sex == 1 else 'Brasileira')}, {random.randint(1, 9)}, 1, {c}, {c}, {code}, 3, {q(email)}, 1)")
        teachers.append((tid, name, email, code, d_name))
        discipline_teachers.setdefault(d_index, []).append(tid)

# --- alunos
students = []  # (id, nome, email, codigo, turma)
student_rows = []
for sid in range(1, N_STUDENTS + 1):
    class_index = (sid - 1) % len(CLASSES)
    serie, cedula, turno = CLASSES[class_index]
    sex = random.choice((1, 2))
    name = person(sex)
    first, last = name.split()[0], name.split()[-1]
    email = f"{slug(first)}.{slug(last)}{sid:03d}+clerk_test@{EMAIL_DOMAIN}"
    code = access_code()
    c = contact()
    mother = f"{random.choice(FIRST_F)} {last}"
    father = f"{random.choice(FIRST_M)} {last}"
    age = 14 + serie
    student_rows.append(
        f"({q(name)}, {unique_cpf()}, {sex}, {q(birth(age, age + 1).isoformat())}, 'Paulo Afonso', 'foto-vazia.jpg', "
        f"{q('Brasileiro' if sex == 1 else 'Brasileira')}, {q(mother)}, {q(father)}, {random.randint(1, 9)}, "
        f"{2 if random.random() < 0.02 else 1}, {c}, {c}, {code}, 4, {q(email)}, 1)")
    students.append((sid, name, email, code, f"{serie}ª {CEDULA[cedula]} - {TURNO[turno]}", class_index + 1))

sql.append("INSERT INTO telefone (numero_telefone) VALUES\n  " + ",\n  ".join(f"({q(p)})" for p in phones) + ";")
sql.append("INSERT INTO endereco (cep, bairro, endereco, municipio, uf) VALUES\n  "
           + ",\n  ".join(f"({a[0]}, {q(a[1])}, {q(a[2])}, {q(a[3])}, {q(a[4])})" for a in addresses) + ";")
sql.append("")
sql.append("INSERT INTO professor (nome_professor, fk_id_sexo_professor, data_nascimento_professor, foto_perfil_professor,\n"
           "  cpf_professor, naturalidade_professor, nacionalidade_professor, fk_id_tipo_sanguineo_professor, fk_id_pcd_professor,\n"
           "  fk_id_endereco_professor, fk_id_telefone_professor, codigo_acesso, fk_id_professor_hierarquia_funcao,\n"
           "  email_professor, fk_id_situacao_conta_professor) VALUES\n  " + ",\n  ".join(teacher_rows) + ";")
sql.append("")
sql.append("INSERT INTO aluno (nome_aluno, cpf_aluno, fk_id_sexo_aluno, data_nascimento_aluno, naturalidade_aluno,\n"
           "  foto_perfil_aluno, nacionalidade_aluno, nome_mae, nome_pai, fk_id_tipo_sanguineo_aluno, fk_id_aluno_pcd,\n"
           "  fk_id_endereco_aluno, fk_id_telefone_aluno, codigo_acesso, fk_id_aluno_hierarquia_funcao, email_aluno,\n"
           "  fk_id_situacao_geral_aluno) VALUES\n  " + ",\n  ".join(student_rows) + ";")
sql.append("")

# --- matrículas
sql.append("INSERT INTO matricula (data_hora_matricula, fk_id_aluno, fk_id_turma_matricula, fk_id_situacao_aluno, fk_id_periodo_letivo_matricula) VALUES\n  "
           + ",\n  ".join(f"('2026-01-{random.randint(10, 30)} {random.randint(8, 17):02d}:{random.randint(0, 59):02d}:00', {s[0]}, {s[5]}, 1, 1)"
                          for s in students) + ";")

# --- disciplinas por turma (cada professor pega um conjunto de turmas)
class_disciplines = []  # (id, turma, disciplina)
td_rows = []
for class_id in range(1, len(CLASSES) + 1):
    for d_index in range(1, len(DISCIPLINES) + 1):
        options = discipline_teachers[d_index]
        teacher_id = options[(class_id - 1) * len(options) // len(CLASSES)]
        td_rows.append(f"({d_index}, {class_id}, {teacher_id})")
        class_disciplines.append((len(td_rows), class_id, d_index))
sql.append("INSERT INTO turma_disciplina (fk_id_disciplina, fk_id_turma, fk_id_professor) VALUES\n  " + ",\n  ".join(td_rows) + ";")
sql.append("")

# --- 1ª unidade: prova (6) + trabalho (4) com notas e faltas
exam_rows, note_rows, lack_rows = [], [], []
students_by_class = {}
for s in students:
    students_by_class.setdefault(s[5], []).append(s[0])

skill = {s[0]: random.gauss(0.72, 0.13) for s in students}
exam_id = 0
for td_id, class_id, d_index in class_disciplines:
    for desc, value, day in (("Prova 1ª unidade", 6, random.randint(10, 25)), ("Trabalho 1ª unidade", 4, random.randint(1, 9))):
        exam_id += 1
        month = 3 if desc.startswith("Prova") else 4
        exam_rows.append(f"({q(desc)}, 1, {value}, '2026-0{month}-{day:02d}', {td_id})")
        for sid in students_by_class[class_id]:
            ratio = min(1.0, max(0.1, random.gauss(skill[sid], 0.1)))
            note_rows.append(f"({sid}, {exam_id}, {round(value * ratio * 2) / 2})")
    for sid in students_by_class[class_id]:
        lacks = max(0, int(random.expovariate(0.6)))
        if lacks:
            lack_rows.append(f"({lacks}, {td_id}, 1, {sid})")

sql.append("INSERT INTO avaliacoes (descricao_avaliacao, fk_id_unidade_avaliacao, valor_avaliacao, data_realizada, fk_id_turma_disciplina_avaliacao) VALUES\n  "
           + ",\n  ".join(exam_rows) + ";")
sql.append("INSERT INTO nota_avaliacao (fk_id_matricula_aluno, fk_id_avaliacao, valor_nota) VALUES\n  " + ",\n  ".join(note_rows) + ";")
sql.append("INSERT INTO falta_aluno (total_faltas, fk_id_turma_disciplina_falta, fk_id_unidade_falta, fk_id_matricula_falta) VALUES\n  "
           + ",\n  ".join(lack_rows) + ";")

# --- avisos e observações
notices = ["Prova da 1ª unidade remarcada para a próxima semana.", "Trazer o livro didático na próxima aula.",
           "Entrega do trabalho em grupo até sexta-feira.", "Aula de reforço na quinta, no contraturno."]
sql.append("INSERT INTO aviso_turma (aviso, fk_id_turma_disciplina) VALUES\n  "
           + ",\n  ".join(f"({q(random.choice(notices))}, {td_id})" for td_id, _, _ in random.sample(class_disciplines, 20)) + ";")
observations = ["Participativo nas aulas.", "Precisa melhorar a pontualidade na entrega das atividades.",
                "Ótimo desempenho na prova.", "Conversa muito durante as explicações."]
obs_rows = []
for sid in random.sample(range(1, N_STUDENTS + 1), 40):
    class_id = students[sid - 1][5]
    td_id = random.choice([c[0] for c in class_disciplines if c[1] == class_id])
    obs_rows.append(f"({td_id}, 1, {q(random.choice(observations))}, {sid})")
sql.append("INSERT INTO observacao_aluno (fk_id_turma_disciplina_observacao, fk_id_unidade, descricao, fk_id_matricula_observacao) VALUES\n  "
           + ",\n  ".join(obs_rows) + ";")

open("database/dev/99_dev_seed.sql", "w", encoding="utf-8").write("\n".join(sql) + "\n")

# --- documentação dos usuários
md = f"""# Usuários de teste — Mater

> Dados **fictícios**, gerados por `database/dev/generate_dev_seed.py` e carregados só no banco local (Docker).
> Escola: 1 curso (Ensino Médio), 9 turmas (1ª a 3ª série, A/B/C), 12 disciplinas, {N_TEACHERS} professores e {N_STUDENTS} alunos,
> com notas e faltas lançadas na 1ª unidade.

## Como entrar

**Com o Clerk ativo** (chaves no `.env`):

1. Abra o portal (`/admin`, `/portal-docente` ou `/portal-aluno`).
2. Digite o e-mail da tabela e clique em **Continuar**. Se a conta ainda não existe, o Clerk segue direto para o cadastro:
   escolha um nome de usuário qualquer e uma senha (sugestão `{SUGGESTED_PASSWORD}`).
3. O Clerk pede um código de verificação: use **`{CLERK_TEST_CODE}`**. E-mails com `+clerk_test` não recebem e-mail de verdade.
4. Nos próximos acessos, basta e-mail (ou usuário) e senha.

**Sem o Clerk** (`CLERK_*` vazios no `.env`): login por **nome completo + código de acesso**.

O usuário precisa entrar pelo portal certo: e-mail de professor só funciona em `/portal-docente`, de aluno em `/portal-aluno`.

## Admin

| Nome | E-mail (Clerk) | Código (sem Clerk) |
| --- | --- | --- |
| Usuário Padrão | `{admin_email}` | 867532 |

## Professores ({N_TEACHERS})

| Nome | Disciplina | E-mail (Clerk) | Código (sem Clerk) |
| --- | --- | --- | --- |
""" + "\n".join(f"| {t[1]} | {t[4]} | `{t[2]}` | {t[3]} |" for t in teachers) + f"""

## Alunos (amostra — 1 por turma; os {N_STUDENTS} estão no banco)

| Nome | Turma | E-mail (Clerk) | Código (sem Clerk) |
| --- | --- | --- | --- |
""" + "\n".join(f"| {s[1]} | {s[4]} | `{s[2]}` | {s[3]} |" for s in students[:len(CLASSES)]) + """

Para ver todos os alunos:

```bash
docker exec mater-db psql -U mater -d mater -c "SELECT nome_aluno, email_aluno, codigo_acesso FROM aluno ORDER BY nome_aluno"
```
"""
open("docs/USUARIOS_TESTE.md", "w", encoding="utf-8").write(md)
print(f"ok: {N_TEACHERS} professores, {N_STUDENTS} alunos, {len(exam_rows)} avaliações, {len(note_rows)} notas, {len(lack_rows)} faltas")
