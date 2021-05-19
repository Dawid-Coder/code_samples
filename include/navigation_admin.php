<div id="left-site" class="col-xl-2 col-lg-3 col-md-4">
	<div id="logo" class="mb-5">
		<img src="../img/prelogo.png" alt="LOGO" class="img-fluid d-block m-auto" width="150">
	</div>
	
	<?php if(isset($_SESSION['id']) && $_SESSION['id'] > 0) : ?>
		<?php if(isset($_SESSION['admin']) && $_SESSION['admin'] == 1) : ?>
			<div class="fw-litebold lead">Sekcja administratora</div>
			<div class="ms-4 mt-2 mb-5">
				<div class="nav-item"><a href="settings.php" class="link-item"><i class="fas fa-cog text-center text-danger" style="width: 20px;" aria-hidden="true"></i> Ustawienia</a></div>
				<div class="nav-item"><a href="services.php" class="link-item"><i class="fas fa-tags text-center text-success" style="width: 20px;" aria-hidden="true"></i> Usługi</a></div>
				<div class="nav-item"><a href="servers.php" class="link-item"><i class="fas fa-server text-center text-yellow" style="width: 20px;" aria-hidden="true"></i> Serwery</a></div>
				<div class="nav-item"><a href="payments.php" class="link-item"><i class="fas fa-comment-dollar text-center text-lime" style="width: 20px;" aria-hidden="true"></i> Płatności</a></div>
				<div class="nav-item"><a href="users.php" class="link-item"><i class="fas fa-users text-center text-info" style="width: 20px;" aria-hidden="true"></i> Użytkownicy</a></div>
				<hr>
				<div class="nav-item"><i class="fas fa-flag text-center text-secondary" style="width: 20px;" aria-hidden="true"></i> Flagi graczy</div>
				<div class="nav-item"><i class="fas fa-list text-center text-secondary" style="width: 20px;" aria-hidden="true"></i> Logi</div>
			</div>
		<?php endif; ?>
		
		<div class="fw-litebold lead">Sekcja użytkownika</div>
		<div class="ms-4 mt-2 mb-5">
			<div class="nav-item"><a href="../index.php" class="link-item"><i class="fas fa-globe text-center text-info" style="width: 20px;" aria-hidden="true"></i> Strona główna</a></div>
			<div class="nav-item"><i class="fas fa-tags text-center text-success" style="width: 20px;" aria-hidden="true"></i> Usługi</div>
			<div class="nav-item"><i class="fas fa-wallet text-center text-brown" style="width: 20px;" aria-hidden="true"></i> Doładuj portfel</div>
		</div>
	<?php else : ?>
		<button type="button" class="btn btn-success fw-litebold col-12 mb-2">Zaloguj się</button>
		<div class="small text-center">Nie masz konta?</div>
		<button type="button" class="btn btn-danger fw-litebold col-12">Zarejestruj się</button>
	<?php endif; ?>
</div>