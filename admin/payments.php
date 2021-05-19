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
		<title>Płatności - Sklep SMS - ACP</title>
	</head>
	<body>
		<div id="container">
			<?php require_once('../include/navigation_admin.php'); ?>
			<div id="right-site" class="col-xl-10 col-lg-9 col-md-8">
				<div class="p-4 header-item mb-5">Płatności</div>
				
				<div class="col-10 m-auto">
					<?php
					
						if(isset($_SESSION['message']))
						{
							echo $_SESSION['message'];
							unset($_SESSION['message']);
						}
						
						$page = isset($_GET['page']) ? $_GET['page'] : 1;
						$limit = 10;
						$max_records = is_array(sql_select($hostname, $username, $password, $database, 'payment_id', 'shopsms_payments', '', -1, 1)) ? count(sql_select($hostname, $username, $password, $database, 'payment_id', 'shopsms_payments', '', -1, 1)) : 0;
						
						
						$sort = '';
						$sort_name_value = '_max';
						$sort_api_value = '_max';
						$sort_name_icon = 'fa-sort-alpha-up-alt';
						$sort_api_icon = 'fa-sort-alpha-up-alt';
						
						if(isset($_GET['sort']))
						{
							$output = explode('_', $_GET['sort']);
							
							if($_GET['sort'] == 'name_max')
							{
								$sort = 'ORDER BY '.$output[0].' ASC';
								$sort_name_icon = 'fa-sort-alpha-up-alt';
								$sort_name_value = '_min';
							}
							else if($_GET['sort'] == 'name_min')
							{
								$sort = 'ORDER BY '.$output[0].' DESC';
								$sort_name_icon = 'fa-sort-alpha-down-alt';
								$sort_name_value = '_max';
							}
							else if($_GET['sort'] == 'api_max')
							{
								$sort = 'ORDER BY '.$output[0].' ASC';
								$sort_api_icon = 'fa-sort-alpha-up-alt';
								$sort_api_value = '_min';
							}
							else if($_GET['sort'] == 'api_min')
							{
								$sort = 'ORDER BY '.$output[0].' DESC';
								$sort_api_icon = 'fa-sort-alpha-down-alt';
								$sort_api_value = '_max';
							}
						}
						
						if(isset($_GET['search_name']) && $_GET['search_name'] != '')
						{
							$sort = "WHERE name LIKE '%".$_GET['search_name']."%'";
						}
					
					?>
					
					<div class="d-flex justify-content-between mb-2">
						<form method="get" class="col-3 d-flex">
							<input type="text" name="search_name" class="form-control me-1" placeholder="Nazwa">
							<button type="submit" class="btn btn-primary fw-litebold"><i class="fa fa-search" aria-hidden="true"></i></button>
						</form>
						<button type="button" class="btn btn-primary fw-litebold" onclick="add_payment(document.getElementById('form-add-payment'), document.getElementById('no-results'));">DODAJ PŁATNOŚĆ</button>
					</div>
					<div class="shadow p-3 d-flex bg-black">
						<div class="fw-litebold col-4">NAZWA <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?sort=name'.$sort_name_value; ?>"><i class="fas <?php echo $sort_name_icon; ?> fa-lg align-middle mb-1 text-primary"></i></a></div>
						<div class="fw-litebold col-2">API <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?sort=api'.$sort_api_value; ?>"><i class="fas <?php echo $sort_api_icon; ?> fa-lg align-middle mb-1 text-primary"></i></a></div>
						<div class="fw-litebold col-4">KLUCZ API</div>
						<div class="fw-litebold col-2 d-flex justify-content-end">AKCJE</div>
					</div>
					
					<form id="form-add-payment" action="../include/global_processes.php" method="post" class="d-none">
						<div class="p-1 d-flex border-black">
							<div class="col-4 p-1"><input type="text" name="payments_name" class="form-control" placeholder="Nazwa płatności" required></div>
							<div class="col-2 p-1">
								<select name="payments_api" class="form-control" required>
									<option value="0" selected disabled>Wybierz API</option>
									<option value="1">CSSetti</option>
									<option value="2">1shot1kill</option>
								</select>
							</div>
							<div class="col-4 p-1"><input type="text" name="payments_key" class="form-control" placeholder="Klucz API" required></div>
							<div class="col-2 d-flex justify-content-end">
								<div class="badge" style="font-size: 13px;"><button type="submit" class="btn btn-success fw-bold" style="font-size: 13px; margin-right: -10px;">DODAJ</button></div>
								<div class="badge" style="font-size: 13px;"><button type="button" class="btn btn-danger fw-bold" style="font-size: 13px;" onclick="cancel_payment(document.getElementById('form-add-payment'), document.getElementById('no-results'));">ANULUJ</button></div>
							</div>
						</div>
					</form>
					
					<?php $result = sql_select($hostname, $username, $password, $database, 'payment_id, name, api, api_key', 'shopsms_payments', $sort, $limit, $page); ?>
					<?php if(is_array($result)) : ?>
						<?php for($i = 0; $i < count($result); $i++) : ?>
							<form id="form-<?php echo $i; ?>" action="../include/global_processes.php" method="post">
								<div class="p-3 d-flex border-black">
									<div class="col-4">
										<span id="name-<?php echo $i; ?>"><?php echo $result[$i]['name']; ?></span>
										
										<input id="name-result-<?php echo $i; ?>" type="text" name="payments_name_action" class="form-control d-block badge bg-white ls-1 fw-litebold border-0 shadow-none text-dark text-start d-none" style="font-size: 13px; outline: none;" placeholder="Nazwa" value="<?php echo $result[$i]['name']; ?>" required>
									</div>
									<div class="col-2">
										<?php
											if($result[$i]['api'] == 1)
												$api_name = 'CSSetti';
											else if($result[$i]['api'] == 2)
												$api_name = '1shot1kill';
										?>
										
										<span id="api-<?php echo $i; ?>" class="badge bg-danger ls-1 fw-litebold" style="font-size: 13px;"><?php echo $api_name; ?></span>
										
										<select id="api-result-<?php echo $i; ?>" name="payments_api_action" class="form-control d-block badge bg-white ls-1 fw-litebold border-0 shadow-none text-dark d-none" style="height: 26px; font-size: 13px; outline: none; cursor: pointer;">
											<option value="<?php echo $result[$i]['api'] + 1; ?>" selected disabled><?php echo $api_name; ?></option>
											<option value="1">CSSetti</option>
											<option value="2">1shot1kill</option>
										</select>
									</div>
									<div class="col-4">
										<span id="key-<?php echo $i; ?>" class="badge bg-success fw-litebold" style="font-size: 13px;"><?php echo $result[$i]['api_key']; ?></span>
										
										<input id="key-result-<?php echo $i; ?>" type="text" name="payments_key_action" class="form-control d-block badge bg-white ls-1 fw-litebold border-0 shadow-none text-dark text-start d-none" style="font-size: 13px; outline: none;" placeholder="Klucz API" value="<?php echo $result[$i]['api_key']; ?>" required>
									</div>
									<div class="col-2 d-flex justify-content-end fa-lg">
										<button id="btn-edit-<?php echo $i; ?>" type="button" class="btn btn-primary badge fw-bold d-block" style="font-size: 13px;" onclick="edit(this, document.getElementById('btn-save-<?php echo $i; ?>'), document.getElementById('btn-remove-<?php echo $i; ?>'), document.getElementById('name-<?php echo $i; ?>'), document.getElementById('name-result-<?php echo $i; ?>'), document.getElementById('api-<?php echo $i; ?>'), document.getElementById('api-result-<?php echo $i; ?>'), document.getElementById('key-<?php echo $i; ?>'), document.getElementById('key-result-<?php echo $i; ?>'));" onselectstart="return false;">EDYTUJ</button>
										<button id="btn-save-<?php echo $i; ?>" type="button" class="btn btn-success badge fw-bold d-none" style="font-size: 13px;" onclick="save(document.getElementById('form-<?php echo $i; ?>'))">ZAPISZ</button>
										<button id="btn-remove-<?php echo $i; ?>" type="button" class="btn btn-danger badge fw-bold d-none ms-1" style="font-size: 13px;" onclick="remove(document.getElementById('remove-<?php echo $i; ?>'), document.getElementById('form-<?php echo $i; ?>'), <?php echo $result[$i]['payment_id']; ?>, document.getElementById('info-remove-<?php echo $i; ?>'), document.getElementById('remove-confirm-<?php echo $i; ?>'))">USUŃ</button>
									</div>
								</div>
								<div id="info-remove-<?php echo $i; ?>" class="d-none text-danger d-flex justify-content-end">Kliknij ponownie, aby usunąć płatność ze sklepu</div>
								
								<input type="hidden" name="payments_id" value="<?php echo $result[$i]['payment_id']; ?>">
								<input id="remove-<?php echo $i; ?>" type="hidden" name="payments_remove_id" value="">
								<input id="remove-confirm-<?php echo $i; ?>" type="hidden" name="payments_info_remove_id" value="0">
							</form>
						<?php endfor; ?>
					<?php else : ?>
						<div id="no-results" class="bg-danger p-3 d-block">Brak wyników</div>
					<?php endif; ?>
					
					<?php echo paginate($limit, $max_records, $page); ?>
				</div>
			</div>
		</div>
		
		<script>
			
			function add_payment(form_add_payment_id, no_results_id)
			{
				form_add_payment_id.classList.remove("d-none");
				form_add_payment_id.classList.add("d-block");
				
				no_results_id.classList.remove("d-block");
				no_results_id.classList.add("d-none");
			}
			
			function cancel_payment(form_add_payment_id, no_results_id)
			{
				form_add_payment_id.classList.remove("d-block");
				form_add_payment_id.classList.add("d-none");
				
				no_results_id.classList.remove("d-none");
				no_results_id.classList.add("d-block");
			}
			
			function edit(id_edit, id_save, id_remove, id_name, id_name_result, id_api, id_api_result, id_key, id_key_result)
			{
				id_edit.classList.remove("d-block");
				id_edit.classList.add("d-none");
				
				id_save.classList.remove("d-none");
				id_save.classList.add("d-block");
				
				id_remove.classList.remove("d-none");
				id_remove.classList.add("d-block");
				
				id_name.classList.remove("d-block");
				id_name.classList.add("d-none");
				
				id_name_result.classList.remove("d-none");
				id_name_result.classList.add("d-block");
				
				id_api.classList.remove("d-block");
				id_api.classList.add("d-none");
				
				id_api_result.classList.remove("d-none");
				id_api_result.classList.add("d-block");
				
				id_api_result.value = id_api_result.value - 1;
				
				id_key.classList.remove("d-block");
				id_key.classList.add("d-none");
				
				id_key_result.classList.remove("d-none");
				id_key_result.classList.add("d-block");
			}
			
			function save(form_id)
			{
				form_id.submit();
			}
			
			function remove(remove_id, form_id, payment_id, info_remove, remove_confirm)
			{
				if(remove_confirm.value == 1)
				{
					remove_id.value = payment_id;
				
					form_id.submit();
				}
				else
				{
					remove_confirm.value = parseInt(remove_confirm.value) + 1;
					
					info_remove.classList.remove("d-none");
					info_remove.classList.add("d-block");
				}
			}
		
		</script>
	</body>
</html>