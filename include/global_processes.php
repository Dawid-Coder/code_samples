<?php

require_once('global_functions.php');


// ../admin/users.php
if(isset($_POST['users_remove_id']) && $_POST['users_remove_id'] > 0)
{
	if(sql_delete($hostname, $username, $password, $database, 'shopsms_users', sprintf("id = %d", $_POST['users_remove_id'])))
	{
		$_SESSION['message'] = '<div class="bg-yellow text-white p-3 mb-4">Użytkownik został usunięty</div>';
	}
	else $_SESSION['message'] = '<div class="bg-danger p-3 mb-4">Coś poszło nie tak!<br>Użytkownik nie został usunięty</div>';
	
	$location = 'admin/users.php';
}
else if(isset($_POST['users_id']) && isset($_POST['users_admin']) && isset($_POST['users_wallet']))
{
	if(sql_update($hostname, $username, $password, $database, 'shopsms_users', sprintf("admin = %d, wallet = %s", $_POST['users_admin'], $_POST['users_wallet']), sprintf("WHERE id = %d", $_POST['users_id'])))
	{
		$_SESSION['message'] = '<div class="bg-success p-3 mb-4">Zmiany zostały zapisane</div>';
	}
	else $_SESSION['message'] = '<div class="bg-danger p-3 mb-4">Coś poszło nie tak!<br>Zmiany nie zostały zapisane</div>';
	
	$location = 'admin/users.php';
}

// ../admin/payments.php
if(isset($_POST['payments_remove_id']) && $_POST['payments_remove_id'] > 0)
{
	if(sql_delete($hostname, $username, $password, $database, 'shopsms_payments', sprintf("payment_id = %d", $_POST['payments_remove_id'])))
	{
		$_SESSION['message'] = '<div class="bg-yellow text-white p-3 mb-4">Płatność została usunięta</div>';
	}
	else $_SESSION['message'] = '<div class="bg-danger p-3 mb-4">Coś poszło nie tak!<br>Płatność nie została usunięta</div>';
	
	$location = 'admin/payments.php';
}
else if(isset($_POST['payments_id']) && isset($_POST['payments_name_action']) && isset($_POST['payments_api_action']) && isset($_POST['payments_key_action']))
{
	if(strlen($_POST['payments_name_action']) >= 3 && strlen($_POST['payments_key_action']) >= 3)
	{
		if(sql_update($hostname, $username, $password, $database, 'shopsms_payments', sprintf("name = '%s', api = %d, api_key = '%s'", $_POST['payments_name_action'], $_POST['payments_api_action'], $_POST['payments_key_action']), sprintf("WHERE payment_id = %d", $_POST['payments_id'])))
		{
			$_SESSION['message'] = '<div class="bg-success p-3 mb-4">Zmiany zostały zapisane</div>';
		}
		else $_SESSION['message'] = '<div class="bg-danger p-3 mb-4">Coś poszło nie tak!<br>Zmiany nie zostały zapisane</div>';
	}
	else $_SESSION['message'] = '<div class="bg-danger p-3 mb-4">Nazwa i klucz API muszą mieć co najmniej 3 znaki!<br>Zmiany nie zostały zapisane</div>';
	
	$location = 'admin/payments.php';
}
else if(isset($_POST['payments_name']) && isset($_POST['payments_key']))
{
	if($_POST['payments_api'] != 0)
	{
		if(sql_insert($hostname, $username, $password, $database, 'shopsms_payments', sprintf("null, '%s', %d, '%s'", $_POST['payments_name'], $_POST['payments_api'], $_POST['payments_key'])))
		{
			$_SESSION['message'] = '<div class="bg-success p-3 mb-4">Płatność została dodana</div>';
		}
		else $_SESSION['message'] = '<div class="bg-danger p-3 mb-4">Coś poszło nie tak!<br>Płatność nie została dodana</div>';
	}
	else $_SESSION['message'] = '<div class="bg-danger p-3 mb-4">Musisz wybrać API!<br>Płatność nie została dodana</div>';
	
	$location = 'admin/payments.php';
}



header('Location: ../'.$location);

?>