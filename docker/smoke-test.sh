#!/bin/sh
# Smoke test: loga nos 3 portais e chama todas as rotas, procurando erros de banco/PHP.
# Uso: docker exec mater-app sh /var/www/html/docker/smoke-test.sh [--writes]
# Sem --writes só roda rotas de leitura. Use apenas no banco de desenvolvimento.

BASE=http://localhost
TMP=$(mktemp -d)
WRITES=$1

PARAMS="accessCode=1&accountState=1&acronym=TST&address=Rua+Teste&addressId=1&adminId=1&average=7&averageType=1\
&ballot=1&birthDate=2000-01-01&bloodType=1&class=1&classId=1&classRoom=1&classroomId=1&classroomNumber=2\
&controlRematrug=1&controlUnity=1&county=Paulo+Afonso&course=1&courseId=1&courseMode=1&courseName=Curso+Teste\
&cpf=12345678901&description=Teste&discipline=1&disciplineClass=1&disciplineClassAdd=1&disciplineId=1\
&disciplineName=Fisica&district=Centro&email=t@t.com&endDate=2026-12-01&enrollmentId=1&examDescription=Prova\
&examId=1&examValue=2&fatherName=Pai&hierarchyFunction=1&id=1&modality=1&modalityAdd=1&motherName=Mae\
&name=Teste&nationality=Brasileiro&naturalness=PA&newClass=1&noteId=1&noteStatus=1&noteValue=1&oldPhoto=x.jpg\
&orderBy=1&pcd=1&realizeDate=2026-03-01&rematrugSituation=1&schoolTerm=1&schoolTermId=1&schoolTermSituation=1\
&schoolTermSituationAdd=1&schoolYear=6&seekModality=0&seekName=a&serie=1&series=1&sex=1&shift=1\
&situationStudent=1&situationStudentGeneral=1&startDate=2026-02-01&studentCapacity=30&studentId=1&subtitle=1\
&teacher=1&teacherId=1&telephoneId=1&telephoneNumber=75999999999&totalLack=1&uf=BA&unity=1&zipCode=48600000"

login() { # $1 cookie, $2 url, $3 name, $4 code
  curl -s -c "$TMP/$1" -o /dev/null --data-urlencode "name=$3" --data-urlencode "accessCode=$4" "$BASE$2"
}
login admin /admin/login "Usuário Padrão" 867532
login teacher /portal-docente/login "Professora Ana Teste Silva" 111111
login student /portal-aluno/login "Aluno Pedro Teste Souza" 222222

ROUTES=$(grep -o "'route' => '[^']*'" /var/www/html/App/Route.php | sed "s/'route' => '//; s/'$//" | sort -u)
IS_WRITE='inserir|atualizar|deletar|rematricul|troca-turma|adicionar|sair|login'

fail=0; total=0
for r in $ROUTES; do
  if echo "$r" | grep -qE "$IS_WRITE"; then
    [ "$WRITES" = "--writes" ] || continue
    echo "$r" | grep -qE 'sair|login|deletar' && continue
  fi
  case "$r" in
    /portal-docente*) jar=teacher ;;
    /portal-aluno*) jar=student ;;
    *) jar=admin ;;
  esac
  total=$((total+1))
  body=$(curl -s -b "$TMP/$jar" -d "$PARAMS" "$BASE$r?$PARAMS")
  if echo "$body" | grep -qiE "SQLSTATE|Fatal error|Uncaught"; then
    fail=$((fail+1))
    echo "FALHOU $r"
    echo "$body" | grep -oiE "(SQLSTATE|Fatal error|Uncaught)[^<]{0,300}" | head -2 | sed 's/^/    /'
  fi
done
echo "Rotas testadas: $total | com erro: $fail"
rm -rf "$TMP"
