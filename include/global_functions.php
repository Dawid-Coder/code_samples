<?php

session_start();

require_once('global_config.php');


function remove_folder($folder)
{
	if(!is_dir($folder))
	{
		return 0;
	}
	
	if(substr($folder, strlen($folder) - 1, 1) != '/')
	{
		$folder .= '/';
	}
	
	$files = glob($folder . '*', GLOB_MARK);
	
	foreach($files as $file)
	{
		if(is_dir($file))
		{
			deleteDir($file);
		}
		else
		{
			unlink($file);
		}
	}
	
	rmdir($folder);
	
	return 1;
}

function sql_create_table($hostname, $username, $password, $database, $table, $data)
{
	$conn = new mysqli($hostname, $username, $password, $database);

	if($conn -> connect_errno)
	{
		if($_SESSION['admin'] == 1)
			echo 'Nie udało się połączyć z bazą danych!\nTreść błędu: '.$conn -> connect_error;
		else
			echo 'Nie udało się połączyć z bazą danych!\nZgłoś błąd administratorowi strony.';

		exit();
	}
	
	
	if($res = $conn -> query(sprintf("CREATE TABLE IF NOT EXISTS `%s` (%s);", $table, $data)))
	{
		$conn -> close();

		return 1;
	}
	
	$conn -> close();
	
	return 0;
}

function sql_insert($hostname, $username, $password, $database, $table, $data)
{
	$conn = new mysqli($hostname, $username, $password, $database);

	if($conn -> connect_errno)
	{
		if($_SESSION['admin'] == 1)
			echo 'Nie udało się połączyć z bazą danych!\nTreść błędu: '.$conn -> connect_error;
		else
			echo 'Nie udało się połączyć z bazą danych!\nZgłoś błąd administratorowi strony.';

		exit();
	}
	
	
	if($res = $conn -> query(sprintf("INSERT INTO %s VALUES (%s)", $table, $data)))
	{
		$conn -> close();
		
		return 1;
	}
	
	$conn -> close();
	
	return 0;
}

function sql_select($hostname, $username, $password, $database, $table_select, $table, $other, $limit, $page)
{
	$conn = new mysqli($hostname, $username, $password, $database);

	if($conn -> connect_errno)
	{
		if($_SESSION['admin'] == 1)
			echo 'Nie udało się połączyć z bazą danych!\nTreść błędu: '.$conn -> connect_error;
		else
			echo 'Nie udało się połączyć z bazą danych!\nZgłoś błąd administratorowi strony.';

		exit();
	}

	
	if($page == 1)
		$offset = $page - 1;
	else
		$offset = $limit * ($page - 1);
	
	if($limit == -1)
		$limit_offset = '';
	else
		$limit_offset = sprintf('LIMIT %d OFFSET %d', $limit, $offset);
	
	
	if($res = $conn -> query(sprintf("SELECT %s FROM %s %s %s", $table_select, $table, $other, $limit_offset)))
	{
		$rows = $res -> num_rows;
		
		if($rows > 0)
		{
			$tab1 = array();
			$tab2 = array();
			$output = explode(',', $table_select);
				
			while($record = $res -> fetch_assoc())
			{	
				for($i = 0; $i < count($output); $i++)
				{
					$output[$i] = trim($output[$i], ' ');
					array_push($tab1, $output[$i]);
					array_push($tab2, $record[$output[$i]]);
				}
				
				$tab[] = array_combine($tab1, $tab2);
			}
			
			$conn -> close();
			
			return $tab;
		}
		
		$conn -> close();
		
		return 1;
	}
	
	$conn -> close();
	
	return 0;
}

function sql_update($hostname, $username, $password, $database, $table, $data, $other)
{
	$conn = new mysqli($hostname, $username, $password, $database);

	if($conn -> connect_errno)
	{
		if($_SESSION['admin'] == 1)
			echo 'Nie udało się połączyć z bazą danych!\nTreść błędu: '.$conn -> connect_error;
		else
			echo 'Nie udało się połączyć z bazą danych!\nZgłoś błąd administratorowi strony.';

		exit();
	}
	
	
	if($res = $conn -> query(sprintf("UPDATE %s SET %s %s", $table, $data, $other)))
	{
		$conn -> close();
		
		return 1;
	}
	
	$conn -> close();
	
	return 0;
}

function sql_delete($hostname, $username, $password, $database, $table, $data)
{
	$conn = new mysqli($hostname, $username, $password, $database);

	if($conn -> connect_errno)
	{
		if($_SESSION['admin'] == 1)
			echo 'Nie udało się połączyć z bazą danych!\nTreść błędu: '.$conn -> connect_error;
		else
			echo 'Nie udało się połączyć z bazą danych!\nZgłoś błąd administratorowi strony.';

		exit();
	}
	
	
	if($res = $conn -> query(sprintf("DELETE FROM %s WHERE %s", $table, $data)))
	{
		$conn -> close();
		
		return 1;
	}
	
	$conn -> close();
	
	return 0;
}

function paginate($limit, $max_records, $page)
{
	$pages = ceil($max_records / $limit);

	if($pages > 1)
	{
		$paginate = '<div class="d-flex justify-content-center mt-3 mb-3">';
		
		if($page != 1)
			$paginate .= '<div><a href="'.$_SERVER['SCRIPT_NAME'].'?page='.($page - 1).'" class="paginate-item"><i class="fa fa-chevron-left fa-lg me-2 text-primary" style="margin-top: 10px;" aria-hidden="true"></i></a></div>';
		
		for($i = 1; $i <= $pages; $i++)
		{
			if($page == $i)
				$paginate .= '<div class="bg-primary rounded-circle text-center fw-litebold border-black" style="width: 30px; height: 30px; margin: 2px; padding-top: 2px;">'.$i.'</div>';
			else
				$paginate .= '<div class="bg-dark rounded-circle text-center fw-litebold border-black" style="width: 30px; height: 30px; margin: 2px; padding-top: 2px;"><a href="'.$_SERVER['SCRIPT_NAME'].'?page='.$i.'" class="paginate-item">'.$i.'</a></div>';
		}
		
		if($page != ($i - 1))
			$paginate .= '<div><a href="'.$_SERVER['SCRIPT_NAME'].'?page='.($page + 1).'" class="paginate-item"><i class="fa fa-chevron-right fa-lg ms-2 text-primary" style="margin-top: 10px;" aria-hidden="true"></i></a></div>';
		
		$paginate .= '</div>';
		
		return $paginate;
	}
	
	return '';
}

?>