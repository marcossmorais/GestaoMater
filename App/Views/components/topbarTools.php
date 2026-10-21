<?php
// Ferramentas da barra superior. Espera $topbarPortal = 'Admin' | 'Teacher' | 'Student'
// (mesmo sufixo da chave nightMode<Portal> que o sistema já usa no localStorage)
?>
<div class="m-topbar-tools">
	<?php if ($topbarPortal === 'Admin') { ?>
		<form class="m-search" action="/admin/aluno/lista" method="GET" role="search">
			<i class="fas fa-search" aria-hidden="true"></i>
			<input type="search" name="busca" placeholder="Buscar aluno pelo nome…" aria-label="Buscar aluno" autocomplete="off">
		</form>
	<?php } ?>
	<button type="button" class="m-icon-btn m-theme-toggle" data-portal="<?= $topbarPortal ?>" title="Alternar modo claro/escuro" aria-label="Alternar modo claro/escuro">
		<i class="fas fa-moon"></i><i class="fas fa-sun"></i>
	</button>
</div>
