<?php

require_once('../include/global_functions.php');

if(!isset($hostname) && !isset($username) && !isset($password) && !isset($database))
{
	header('Location: ../installation');
	exit();
}

?>

<!DOCTYPE HTML>
<html>
	<head>
		<?php require_once('../include/head_admin.php'); ?>
		<meta name="robots" content="noindex, nofollow">
		<title>Użytkownicy - Sklep SMS - ACP</title>
	</head>
	<body>
		<div id="container">
			<?php require_once('../include/navigation_admin.php'); ?>
			<div id="right-site" class="col-xl-10 col-lg-9 col-md-8">
				<div class="p-4 header-item mb-5">Użytkownicy</div>
				
				<div class="col-10 m-auto">
					<?php
					
						if(isset($_SESSION['message']))
						{
							echo $_SESSION['message'];
							unset($_SESSION['message']);
						}
						
						$page = isset($_GET['page']) ? $_GET['page'] : 1;
						$limit = 10;
						$max_records = is_array(sql_select($hostname, $username, $password, $database, 'id', 'shopsms_users', '', -1, 1)) ? count(sql_select($hostname, $username, $password, $database, 'id', 'shopsms_users', '', -1, 1)) : 0;
						
						
						$sort = '';
						$sort_username_value = '_max';
						$sort_wallet_value = '_max';
						$sort_admin_value = '_max';
						$sort_username_icon = 'fa-sort-alpha-up-alt';
						$sort_wallet_icon = 'fa-sort-alpha-up-alt';
						$sort_admin_icon = 'fa-sort-alpha-up-alt';
						
						if(isset($_GET['sort']))
						{
							$output = explode('_', $_GET['sort']);
							
							if($_GET['sort'] == 'username_max')
							{
								$sort = 'ORDER BY '.$output[0].' ASC';
								$sort_username_icon = 'fa-sort-alpha-up-alt';
								$sort_username_value = '_min';
							}
							else if($_GET['sort'] == 'username_min')
							{
								$sort = 'ORDER BY '.$output[0].' DESC';
								$sort_username_icon = 'fa-sort-alpha-down-alt';
								$sort_username_value = '_max';
							}
							else if($_GET['sort'] == 'wallet_max')
							{
								$sort = 'ORDER BY '.$output[0].' ASC';
								$sort_wallet_icon = 'fa-sort-alpha-up-alt';
								$sort_wallet_value = '_min';
							}
							else if($_GET['sort'] == 'wallet_min')
							{
								$sort = 'ORDER BY '.$output[0].' DESC';
								$sort_wallet_icon = 'fa-sort-alpha-down-alt';
								$sort_wallet_value = '_max';
							}
							else if($_GET['sort'] == 'admin_max')
							{
								$sort = 'ORDER BY '.$output[0].' ASC';
								$sort_admin_icon = 'fa-sort-alpha-up-alt';
								$sort_admin_value = '_min';
							}
							else if($_GET['sort'] == 'admin_min')
							{
								$sort = 'ORDER BY '.$output[0].' DESC';
								$sort_admin_icon = 'fa-sort-alpha-down-alt';
								$sort_admin_value = '_max';
							}
						}
						
						if(isset($_GET['search_username']) && $_GET['search_username'] != '')
						{
							$sort = "WHERE username LIKE '%".$_GET['search_username']."%'";
						}
					
					?>
					
					<form method="get" class="col-3 d-flex mb-2">
						<input type="text" name="search_username" class="form-control me-1" placeholder="Nazwa użytkownika">
						<button type="submit" class="btn btn-primary fw-litebold"><i class="fa fa-search" aria-hidden="true"></i></button>
					</form>
					<div class="shadow p-3 d-flex bg-black">
						<div class="fw-litebold col-3">NAZWA <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?sort=username'.$sort_username_value; ?>"><i class="fas <?php echo $sort_username_icon; ?> fa-lg align-middle mb-1 text-primary"></i></a></div>
						<div class="fw-litebold col-4">PORTFEL <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?sort=wallet'.$sort_wallet_value; ?>"><i class="fas <?php echo $sort_wallet_icon; ?> fa-lg align-middle mb-1 text-primary"></i></a></div>
						<div class="fw-litebold col-3">ADMIN <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?sort=admin'.$sort_admin_value; ?>"><i class="fas <?php echo $sort_admin_icon; ?> fa-lg align-middle mb-1 text-primary"></i></a></div>
						<div class="fw-litebold col-2 d-flex justify-content-end">AKCJE</div>
					</div>
					<?php $result = sql_select($hostname, $username, $password, $database, 'id, username, wallet, admin', 'shopsms_users', $sort, $limit, $page); ?>
					<?php if(is_array($result)) : ?>
						<?php for($i = 0; $i < count($result); $i++) : ?>
							<form id="form-<?php echo $i; ?>" action="../include/global_processes.php" method="post">
								<div class="p-3 d-flex border-black">
									<div class="col-3"><?php echo $result[$i]['username']; ?></div>
									<div class="col-4">
										<span id="btn-plus10-<?php echo $i; ?>" class="badge bg-success d-none" style="font-size: 13px; cursor: pointer;" onclick="wallet_plus10(document.getElementById('wallet-<?php echo $i; ?>'), document.getElementById('wallet-result-<?php echo $i; ?>'))" onselectstart="return false;">+10</span>
										<span id="btn-minus10-<?php echo $i; ?>" class="badge bg-danger d-none" style="font-size: 13px; cursor: pointer;" onclick="wallet_minus10(document.getElementById('wallet-<?php echo $i; ?>'), document.getElementById('wallet-result-<?php echo $i; ?>'))" onselectstart="return false;">-10</span>
										<span id="wallet-result-<?php echo $i; ?>" class="badge bg-black ls-1" style="font-size: 13px;"><?php echo $result[$i]['wallet']; ?></span>
										<span id="btn-plus-<?php echo $i; ?>" class="badge bg-success d-none" style="font-size: 13px; cursor: pointer;" onclick="wallet_plus(document.getElementById('wallet-<?php echo $i; ?>'), document.getElementById('wallet-result-<?php echo $i; ?>'))" onselectstart="return false;"><i class="fa fa-plus" aria-hidden="true"></i></span>
										<span id="btn-minus-<?php echo $i; ?>" class="badge bg-danger d-none" style="font-size: 13px; cursor: pointer;" onclick="wallet_minus(document.getElementById('wallet-<?php echo $i; ?>'), document.getElementById('wallet-result-<?php echo $i; ?>'))" onselectstart="return false;"><i class="fa fa-minus" aria-hidden="true"></i></span>
									</div>
									<div class="col-3">
										<?php if($result[$i]['admin'] == 1) echo '<span id="admin-result-'.$i.'" class="badge bg-success" style="font-size: 13px;">TAK</span>'; else echo '<span id="admin-result-'.$i.'" class="badge bg-danger" style="font-size: 13px;">NIE</span>'; ?>
										<span id="btn-change-<?php echo $i; ?>" class="badge bg-primary fw-litebold d-none" style="font-size: 13px; cursor: pointer;" onclick="admin_change(document.getElementById('admin-<?php echo $i; ?>'), document.getElementById('admin-result-<?php echo $i; ?>'))" onselectstart="return false;">ZMIEŃ</span>
									</div>
									<div class="col-2 d-flex justify-content-end fa-lg">
										<button id="btn-edit-<?php echo $i; ?>" type="button" class="btn btn-primary badge fw-bold d-block" style="font-size: 13px;" onclick="edit(this, document.getElementById('btn-save-<?php echo $i; ?>'), document.getElementById('btn-remove-<?php echo $i; ?>'), document.getElementById('btn-change-<?php echo $i; ?>'), document.getElementById('btn-plus10-<?php echo $i; ?>'), document.getElementById('btn-minus10-<?php echo $i; ?>'), document.getElementById('btn-plus-<?php echo $i; ?>'), document.getElementById('btn-minus-<?php echo $i; ?>'));" onselectstart="return false;">EDYTUJ</button>
										<button id="btn-save-<?php echo $i; ?>" type="button" class="btn btn-success badge fw-bold d-none" style="font-size: 13px;" onclick="save(document.getElementById('form-<?php echo $i; ?>'))">ZAPISZ</button>
										<button id="btn-remove-<?php echo $i; ?>" type="button" class="btn btn-danger badge fw-bold d-none ms-1" style="font-size: 13px;" onclick="remove(document.getElementById('remove-<?php echo $i; ?>'), document.getElementById('form-<?php echo $i; ?>'), <?php echo $result[$i]['id']; ?>, document.getElementById('info-remove-<?php echo $i; ?>'), document.getElementById('remove-confirm-<?php echo $i; ?>'))">USUŃ</button>
									</div>
								</div>
								<div id="info-remove-<?php echo $i; ?>" class="d-none text-danger d-flex justify-content-end">Kliknij ponownie, aby usunąć użytkownika ze sklepu</div>
								
								<input type="hidden" name="users_id" value="<?php echo $result[$i]['id']; ?>">
								<input id="admin-<?php echo $i; ?>" type="hidden" name="users_admin" value="<?php echo $result[$i]['admin']; ?>">
								<input id="wallet-<?php echo $i; ?>" type="hidden" name="users_wallet" value="<?php echo $result[$i]['wallet']; ?>">
								<input id="remove-<?php echo $i; ?>" type="hidden" name="users_remove_id" value="">
								<input id="remove-confirm-<?php echo $i; ?>" type="hidden" name="users_info_remove_id" value="0">
							</form>
						<?php endfor; ?>
					<?php else : ?>
						<div class="bg-danger p-3">Brak wyników</div>
					<?php endif; ?>
					
					<?php echo paginate($limit, $max_records, $page); ?>
				</div>
			</div>
		</div>
		
		<script>
			
			function edit(id_edit, id_save, id_remove, id_btn_change, id_btn_plus10, id_btn_minus10, id_btn_plus, id_btn_minus)
			{
				id_edit.classList.remove("d-block");
				id_edit.classList.add("d-none");
				
				id_save.classList.remove("d-none");
				id_save.classList.add("d-block");
				
				id_remove.classList.remove("d-none");
				id_remove.classList.add("d-block");
				
				id_btn_change.classList.remove("d-none");
				id_btn_change.classList.add("d-inline-block");
				
				id_btn_plus10.classList.remove("d-none");
				id_btn_plus10.classList.add("d-inline-block");
				
				id_btn_minus10.classList.remove("d-none");
				id_btn_minus10.classList.add("d-inline-block");
				
				id_btn_plus.classList.remove("d-none");
				id_btn_plus.classList.add("d-inline-block");
				
				id_btn_minus.classList.remove("d-none");
				id_btn_minus.classList.add("d-inline-block");
			}
			
			function save(form_id)
			{
				form_id.submit();
			}
			
			function remove(remove_id, form_id, user_id, info_remove, remove_confirm)
			{
				if(remove_confirm.value == 1)
				{
					remove_id.value = user_id;
				
					form_id.submit();
				}
				else
				{
					remove_confirm.value = parseInt(remove_confirm.value) + 1;
					
					info_remove.classList.remove("d-none");
					info_remove.classList.add("d-block");
				}
			}
			
			function admin_change(admin_id, admin_result_id)
			{
				if(admin_id.value == 1)
				{
					admin_id.value = 0;
					admin_result_id.classList.remove("bg-success");
					admin_result_id.classList.add("bg-danger");
					admin_result_id.textContent = 'NIE';
				}
				else
				{
					admin_id.value = 1;
					admin_result_id.classList.remove("bg-danger");
					admin_result_id.classList.add("bg-success");
					admin_result_id.textContent = 'TAK';
				}
			}
			
			function wallet_plus(wallet_id, wallet_result_id)
			{
				let wartosc_portfela = parseInt(wallet_id.value);
				let wynik = wartosc_portfela + 1;
				wallet_id.value = wynik.toFixed(2);
				wallet_result_id.textContent = wynik.toFixed(2);
			}
			
			function wallet_plus10(wallet_id, wallet_result_id)
			{
				let wartosc_portfela = parseInt(wallet_id.value);
				let wynik = wartosc_portfela + 10;
				wallet_id.value = wynik.toFixed(2);
				wallet_result_id.textContent = wynik.toFixed(2);
			}
			
			function wallet_minus(wallet_id, wallet_result_id)
			{
				let wartosc_portfela = parseInt(wallet_id.value);
				
				if(wartosc_portfela > 0)
				{
					let wynik = wartosc_portfela - 1;
					wallet_id.value = wynik.toFixed(2);
					wallet_result_id.textContent = wynik.toFixed(2);
				}
			}
			
			function wallet_minus10(wallet_id, wallet_result_id)
			{
				let wartosc_portfela = parseInt(wallet_id.value);
				
				if(wartosc_portfela >= 10)
				{
					let wynik = wartosc_portfela - 10;
					wallet_id.value = wynik.toFixed(2);
					wallet_result_id.textContent = wynik.toFixed(2);
				}
			}
		
		</script>
	</body>
</html>