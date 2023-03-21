<?php 
  require 'cookies.php';
	require 'rb.php';
	
	R::setup( 'mysql:host=localhost;dbname=count','root', '' ); 

	if ( !R::testconnection() )
	{
			exit ('Нет соединения с базой данных');
	}

	$cookie_key = 'online-cache';
	$ip = $_SERVER['REMOTE_ADDR']; // Достается ip пользователя через суперглобальный массив SERVER
	$online = R::findOne('online', 'ip = ?', array($ip)); // Проверяет нет ли уже такой записи об этом пользователе, чтобы каждый раз её не дублировать

	if ( $online )
	{
		$do_update = false;
		// Если такой пользователь уже найден, то мы его обновляем, но это будет сильным ударом по производительности, поэтому использует куки
		if ( CookieManager::stored($cookie_key) )
			{
				$c = (array) @json_decode(CookieManager::read($cookie_key), true);
				if ( $c )
				{
					if( $c['lastvisit'] < (time() - (60 * 5)) ) //обновляем данные в базе каждые 5 минут
					{
						$do_update = true;
					}
				} else
				{
					$do_update = true;
				}

			} else{
					$do_update = true;		
			}
			if ( $do_update )
			{
					$time = time();
					$online->lastvisit = $time;
					R::store($online);
					CookieManager::store($cookie_key, json_encode(array(
						'id' => $online->id,
						'lastvisit' => $time)));
					// Сохраним в куки дату последнего обновления информации о посещении пользователя
			}

	} else{
		// Если пользователь не найден, то мы его добавим
		$time = time();
		$online = R::dispense('online');
		$online->lastvisit = $time;
		$online->ip = $ip;
		R::store($online);
		CookieManager::store($cookie_key, json_encode(array(
			'id' => $online->id,
			'lastvisit' => $time)));
		// json_encode мы делаем потому что в куки нельзя хранить структуры, в отличии от сессии, а можно хранить только строки
	}

// Выводим количество онлайн за последний час
	$online_count = R::count('online', "lastvisit > " . ( time() - (3600) ))


?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Счетчик онлайн посетителей сайта</title>
</head>
<body>

Сейчас онлайн: <?php echo $online_count ; ?>

</body>
</html>