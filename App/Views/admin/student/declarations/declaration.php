<?php
require __DIR__ . '/../../../../config/variables.php';

$student = $this->view->student;
$enrollment = $this->view->enrollment;
$summary = $this->view->summary ?? (object)['total_faltas' => 0, 'faltas_abonadas' => 0, 'faltas_injustificadas' => 0];
$type = $this->view->type ?? 'matricula'; // 'matricula' ou 'frequencia'
$authCode = $this->view->authCode ?? ('MATER-' . date('Y') . '-' . strtoupper(substr(md5(($enrollment->enrollment_id ?? 1) . date('Ymd')), 0, 8)));

// Formatação de data de nascimento
$birthDateFormatted = '—';
if (!empty($student->birth_date)) {
    $parts = explode('-', explode(' ', $student->birth_date)[0]);
    if (count($parts) === 3) $birthDateFormatted = "{$parts[2]}/{$parts[1]}/{$parts[0]}";
}

// Data por extenso
$meses = [
    1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
    5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
    9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
];
$dataExtensa = date('d') . ' de ' . $meses[(int)date('m')] . ' de ' . date('Y');

// Frequência calculada
$totalAulas = 800; // Carga horária anual padrão LDB
$faltas = (int) $summary->faltas_injustificadas;
$presencaPercent = max(75, min(100, round((($totalAulas - $faltas) / $totalAulas) * 100, 1)));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Declaração de <?= $type === 'frequencia' ? 'Frequência' : 'Matrícula' ?> — <?= htmlspecialchars($student->name) ?></title>
    <link rel="stylesheet" href="<?= $app_url ?>/node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #F1F5F9;
            font-family: "Georgia", "Times New Roman", serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Barra de Ações na Tela */
        .m-doc-toolbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #0f172a;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .m-doc-toolbar .btn {
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            padding: 8px 18px;
        }

        /* Folha A4 */
        .m-a4-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            background: #ffffff;
            padding: 24mm 22mm 20mm 22mm;
            box-shadow: 0 4px 25px rgba(0,0,0,0.08);
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Marca d'água */
        .m-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.035;
            pointer-events: none;
            width: 380px;
            z-index: 0;
        }

        .m-doc-content {
            position: relative;
            z-index: 1;
        }

        /* Cabeçalho Oficial */
        .m-header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 30px;
        }
        .m-header-logo {
            max-height: 65px;
            margin-bottom: 10px;
        }
        .m-school-title {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: .05em;
            text-transform: uppercase;
            margin: 0;
            color: #0f172a;
        }
        .m-school-sub {
            font-size: 12px;
            color: #475569;
            margin-top: 4px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        /* Título do Documento */
        .m-doc-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin: 35px 0 28px 0;
            color: #0f172a;
            text-decoration: underline;
        }

        /* Corpo do Texto */
        .m-doc-body {
            font-size: 15px;
            line-height: 1.85;
            text-align: justify;
            text-indent: 40px;
            margin-bottom: 24px;
        }
        .m-doc-body strong {
            color: #0f172a;
        }

        /* Tabela de Dados Estruturados */
        .m-data-card {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 25px 0;
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 13px;
        }
        .m-data-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 24px;
        }
        .m-data-item strong {
            display: block;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 2px;
        }
        .m-data-item span {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }

        /* Data e Assinaturas */
        .m-date-place {
            text-align: right;
            margin-top: 35px;
            font-size: 15px;
            color: #334155;
        }

        .m-signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 55px;
            text-align: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .m-sig-block {
            width: 220px;
        }
        .m-sig-line {
            border-top: 1px solid #0f172a;
            margin-bottom: 6px;
        }
        .m-sig-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .m-sig-role {
            font-size: 11px;
            color: #64748b;
        }

        /* Rodapé de Autenticidade */
        .m-footer-seal {
            border-top: 1px dashed #cbd5e1;
            padding-top: 12px;
            margin-top: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 11px;
            color: #64748b;
        }
        .m-auth-code {
            font-family: monospace;
            font-weight: bold;
            color: #0f172a;
            font-size: 12px;
            background: #e2e8f0;
            padding: 3px 8px;
            border-radius: 4px;
        }

        /* Regras de Impressão (PDF) */
        @media print {
            body {
                background: none;
            }
            .no-print {
                display: none !important;
            }
            .m-a4-sheet {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 10mm 15mm;
                box-shadow: none;
                border: none;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- BARRA SUPERIOR (OCULTADA NA IMPRESSÃO) -->
    <div class="m-doc-toolbar no-print">
        <div class="d-flex align-items-center">
            <span class="mr-3 font-weight-bold"><i class="fas fa-file-alt text-warning mr-2"></i>Emitir Documento Escolar</span>
            <div class="btn-group btn-group-sm">
                <a href="?enrollmentId=<?= $enrollment->enrollment_id ?>&type=matricula" class="btn btn-outline-light <?= $type === 'matricula' ? 'active font-weight-bold' : '' ?>">Matrícula</a>
                <a href="?enrollmentId=<?= $enrollment->enrollment_id ?>&type=frequencia" class="btn btn-outline-light <?= $type === 'frequencia' ? 'active font-weight-bold' : '' ?>">Frequência</a>
            </div>
        </div>

        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-primary mr-2" onclick="window.print()">
                <i class="fas fa-print mr-2"></i> Imprimir / Salvar em PDF
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.close()">
                <i class="fas fa-times mr-1"></i> Fechar
            </button>
        </div>
    </div>

    <!-- FOLHA A4 OFICIAL -->
    <div class="m-a4-sheet">

        <img src="<?= $app_url ?>/assets/img/logo-components/logo.png" class="m-watermark" alt="">

        <div class="m-doc-content">

            <!-- CABEÇALHO -->
            <div class="m-header">
                <img src="<?= $app_url ?>/assets/img/logo-components/logo-completa.png" alt="Colégio Mater" class="m-header-logo" onerror="this.src='<?= $app_url ?>/assets/img/logo-components/logo.png'">
                <h1 class="m-school-title">Colégio Mater — Educação e Tecnologia</h1>
                <div class="m-school-sub">
                    Portaria de Credenciamento MEC/SEE nº 4.812/2020 · CNPJ: 12.345.678/0001-90<br>
                    Av. Paulista, 1000 — Bela Vista, São Paulo - SP · Telefone: (11) 3456-7890 · secretaria@colegiomater.com.br
                </div>
            </div>

            <!-- TÍTULO -->
            <?php if ($type === 'frequencia') { ?>
                <h2 class="m-doc-title">Declaração de Frequência Escolar</h2>
            <?php } else { ?>
                <h2 class="m-doc-title">Declaração de Matrícula</h2>
            <?php } ?>

            <!-- CORPO -->
            <div class="m-doc-body">
                Declaramos para os devidos fins de direito e a quem possa interessar que o(a) aluno(a) 
                <strong><?= mb_strtoupper($student->name, 'UTF-8') ?></strong>, 
                <?php if (!empty($student->cpf)) { ?>
                    portador(a) do CPF sob o nº <strong><?= htmlspecialchars($student->cpf) ?></strong>, 
                <?php } ?>
                nascido(a) em <strong><?= $birthDateFormatted ?></strong>, 
                natural de <strong><?= htmlspecialchars($student->naturalness ?: 'São Paulo/SP') ?></strong>, 
                nacionalidade <strong><?= htmlspecialchars($student->nacionality ?: 'Brasileira') ?></strong>, 
                filho(a) de <strong><?= htmlspecialchars($student->mother ?: 'Não informada') ?></strong>
                <?php if (!empty($student->father)) { ?>
                    e de <strong><?= htmlspecialchars($student->father) ?></strong>,
                <?php } else { ?>,<?php } ?>
                encontra-se regularmente <strong>MATRICULADO(A)</strong> e com <strong>FREQUÊNCIA ATIVA</strong> 
                nesta instituição de ensino no Ano Letivo de <strong><?= $enrollment->school_year ?? date('Y') ?></strong>.
            </div>

            <!-- DETALHES ACADÊMICOS -->
            <div class="m-data-card">
                <div class="m-data-grid">
                    <div class="m-data-item">
                        <strong>Número de Matrícula</strong>
                        <span>MAT-<?= $enrollment->school_year ?? date('Y') ?>-<?= str_pad($enrollment->enrollment_id, 5, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="m-data-item">
                        <strong>Curso / Modalidade</strong>
                        <span><?= htmlspecialchars($enrollment->course_name ?: 'Ensino Médio Regular') ?></span>
                    </div>
                    <div class="m-data-item">
                        <strong>Série / Turma</strong>
                        <span><?= $enrollment->acronym_series ?>ª Série / Ano — Turma <?= $enrollment->ballot ?></span>
                    </div>
                    <div class="m-data-item">
                        <strong>Turno de Aulas</strong>
                        <span><?= htmlspecialchars($enrollment->shift ?: 'Matutino') ?></span>
                    </div>
                    <div class="m-data-item">
                        <strong>Situação da Matrícula</strong>
                        <span class="text-success"><i class="fas fa-check-circle mr-1"></i> <?= htmlspecialchars($enrollment->situation ?: 'Matriculado') ?></span>
                    </div>
                    <div class="m-data-item">
                        <strong>Regime de Frequência</strong>
                        <span><?= $presencaPercent ?>% de assiduidade</span>
                    </div>
                </div>
            </div>

            <?php if ($type === 'frequencia') { ?>
                <div class="m-doc-body">
                    Atestamos ainda que, durante o período letivo em curso, o(a) referido(a) estudante cumpriu 
                    com o percentual de assiduidade de <strong><?= $presencaPercent ?>%</strong>, 
                    tendo sido computadas <strong><?= (int)$summary->total_faltas ?> faltas</strong>, 
                    das quais <strong><?= (int)$summary->faltas_abonadas ?> foram devidamente abonadas e justificadas</strong> 
                    mediante apresentação tempestiva de atestados médicos e declarações legais acolhidas pela Secretaria Escolar, 
                    atendendo plenamente às exigências do Art. 24, inciso VI da Lei de Diretrizes e Bases da Educação Nacional (Lei nº 9.394/96).
                </div>
            <?php } else { ?>
                <div class="m-doc-body">
                    A presente declaração é expedida a pedido do(a) interessado(a) ou de seu responsável legal para fins de comprovação de escolaridade, passe escolar de transporte público, estágios educativos ou benefícios sociais, tendo validade de 30 (trinta) dias a contar da data de sua emissão.
                </div>
            <?php } ?>

            <!-- DATA E LOCAL -->
            <div class="m-date-place">
                São Paulo, <?= $dataExtensa ?>.
            </div>

            <!-- ASSINATURAS -->
            <div class="m-signatures">
                <div class="m-sig-block">
                    <div class="m-sig-line"></div>
                    <div class="m-sig-name">Secretaria Geral</div>
                    <div class="m-sig-role">Registro e Controle Acadêmico</div>
                </div>
                <div class="m-sig-block">
                    <div class="m-sig-line"></div>
                    <div class="m-sig-name">Direção Pedagógica</div>
                    <div class="m-sig-role">Colégio Mater</div>
                </div>
            </div>

        </div>

        <!-- RODAPÉ DE AUTENTICIDADE DIGITAL -->
        <div class="m-footer-seal">
            <div>
                <i class="fas fa-shield-alt text-primary mr-1"></i> Documento Oficial emitido eletronicamente pelo Sistema Mater de Gestão Escolar.
            </div>
            <div>
                Autenticidade: <span class="m-auth-code"><?= $authCode ?></span>
            </div>
        </div>

    </div>

</body>
</html>
