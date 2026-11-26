<?php
/**
 * Carômetro do admin: foto, nome e faltas do bimestre de cada aluno ativo; toque abre o perfil pedagógico.
 * Espera $roster (StudentFile::classRoster) e $rosterUnity.
 */
require __DIR__ . '/../../../../config/variables.php';
?>
<?php if (!$roster) { ?>
    <div class="card m-panel m-empty">Nenhum aluno ativo nesta turma.</div>
<?php } else { ?>
    <div class="m-att-field m-att-field-grow mb-3">
        <span class="m-att-label">Buscar aluno</span>
        <div class="m-att-search">
            <i class="fas fa-search"></i>
            <input type="search" data-face-search placeholder="Digite um nome" autocomplete="off">
        </div>
    </div>
    <div class="m-att-grid m-roster" data-face-grid>
        <?php foreach ($roster as $index => $s) {
            $words = preg_split('/\s+/', trim($s->name));
            $initials = mb_strtoupper(mb_substr($words[0], 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
            $ring = min((int) $s->absences, 20) / 20 * 100;
        ?>
            <article class="m-att-student m-roster-card" data-name="<?= htmlspecialchars(mb_strtolower($s->name)) ?>">
                <a class="m-roster-link" href="/admin/observabilidade/aluno?id=<?= (int) $s->id ?>" aria-label="Abrir o perfil de <?= htmlspecialchars($s->name) ?>">
                    <span class="m-att-num"><?= $index + 1 ?></span>
                    <span class="m-att-avatar"><?php if ($s->photo) { ?><img src="<?= $app_url ?>/assets/img/studentProfilePhotos/<?= htmlspecialchars($s->photo) ?>" alt="" loading="lazy"><?php } else { ?><span style="--h:<?= crc32($s->name) % 360 ?>"><?= htmlspecialchars($initials) ?></span><?php } ?></span>
                    <span class="m-att-name"><?= htmlspecialchars($s->name) ?></span>
                    <span class="m-att-ring <?= $s->absences >= 10 ? 'is-high' : ($s->absences >= 5 ? 'is-mid' : '') ?>" style="--p:<?= $ring ?>"><strong><?= (int) $s->absences ?></strong></span>
                    <small class="m-att-ring-label">faltas no <?= (int) $rosterUnity ?>º bim.</small>
                </a>
            </article>
        <?php } ?>
    </div>
    <script>
        (function () {
            const input = document.querySelector('[data-face-search]');
            const cards = document.querySelectorAll('[data-face-grid] [data-name]');
            const plain = (t) => t.normalize('NFD').replace(/[̀-ͯ]/g, '');
            input.addEventListener('input', () => {
                const term = plain(input.value.trim().toLowerCase());
                cards.forEach((c) => { c.hidden = term !== '' && !plain(c.dataset.name).includes(term); });
            });
        })();
    </script>
<?php } ?>
