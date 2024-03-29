<?php
include_once "consts.php";
include_once "db.php";

if(basename($_SERVER["PHP_SELF"]) == "common.php" && $_SERVER["REQUEST_METHOD"] == "POST")
	call_action($_GET["action"]);

function get_profile_page()
{
	if(empty($_GET["tab"]))
		include "profile_about.php";
	else
	{
		switch($_GET["tab"])
		{
			case "tablica":
				include "profile_wall.php";
				break;
			case "znajomi":
				include "profile_friends.php";
				break;
			case "zdjecia":
				include "profile_photos.php";
				break;
			default:
				include "profile_about.php";
		}
	}
}

function register_error($errno)
{
	setcookie("register-error", $errno);
	header("Location: index.php#register");
	die();
}

function login_error($errno)
{
	setcookie("login-error", $errno);
	header("Location: index.php");
	die();
}

function get_register_alert()
{
	global $msgs;
	if(isset($_COOKIE["register-error"]))
	{
		$errno = $_COOKIE["register-error"];
		echo $msgs["register"][$errno];
	}
}

function get_login_alert()
{
	global $msgs;
	if(isset($_COOKIE["login-error"]))
	{
		$errno = $_COOKIE["login-error"];
		echo $msgs["login"][$errno];
	}
}

function get_user_info($info_col, $u_id = null)
{
	global $sqls;
	$data = null;
	
	if($u_id == null)
		$u_id = $_SESSION["user_id"];
	
	$query = str_replace("{info_col}", $info_col, $sqls["user_info"]);
	
	$db = db_connect();
	$stmt = $db -> prepare($query);
	$stmt -> bind_param("i", $u_id);
	$stmt -> execute();
	$stmt -> bind_result($data);
	$stmt -> store_result();
	$stmt -> fetch();
	$db -> close();
	
	return $data;
}

function get_user_full_name($u_id = null)
{
	if($u_id == null)
		$u_id = $_SESSION["user_id"];
	
	$fname = get_user_info("firstname", $u_id);
	$nick = get_user_info("nickname", $u_id);
	$sname = get_user_info("surname", $u_id);
	
	return "$fname " . (($nick) ? "\"$nick\" " : "") . $sname;
}

function get_user_avatar_type($u_id = null)
{
	if($u_id == null)
		$u_id = $_SESSION["user_id"];
	
	$avatar = get_user_info("avatar", $u_id);
	
	if(!$avatar)
		return AVATAR_PLACEHOLDER;
	else
	{
		
		if(file_exists(AVATARS_PATH . $u_id . DIRECTORY_SEPARATOR . $avatar))
			return AVATAR_MAIN;
		else if(file_exists(AVATARS_OLD_PATH . $u_id))
			return AVATAR_OLD;
		else
			return AVATAR_PLACEHOLDER;
	}
}

function get_user_avatar($u_id = null)
{
	if($u_id == null)
		$u_id = $_SESSION["user_id"];
	
	$default_avatar = "img/avatar_placeholder.png";
	$avatar = get_user_info("avatar", $u_id);
	$avatar_type = get_user_avatar_type($u_id);
	
	if($avatar_type == AVATAR_PLACEHOLDER)
		$avatar = $default_avatar;
	else if($avatar_type == AVATAR_MAIN)
		$avatar = "img/avatars/" . $u_id . "/" . $avatar;
	else if($avatar_type == AVATAR_OLD)
		$avatar = "img/avatars/old/" . $u_id;
	
	return $avatar;
}

function get_user_background($u_id = null)
{
	if($u_id == null)
		$u_id = $_SESSION["user_id"];
	
	$background = get_user_info("background", $u_id);
	$background_type = get_user_background_type($u_id);
	
	if($background_type == BACKGROUND_NONE)
		$background = null;
	else if($background_type == BACKGROUND_MAIN)
		$background = "img/backgrounds/" . $u_id . "/" . $background;
	else if($background_type == BACKGROUND_OLD)
		$background = "img/backgrounds/old/" . $u_id;
	
	return $background;
}

//Tylko dane niewymagające "obróbki"
function user_info($info_col, $u_id = null, $clean = false)
{
	$data = get_user_info($info_col, $u_id);
	if(!$data && !$clean)
		$data = "brak danych";
	echo $data;
}

function user_full_name($u_id = null)
{
	echo get_user_full_name($u_id);
}

function user_avatar($u_id = null)
{
	echo get_user_avatar($u_id);
}

function user_background($u_id = null)
{
	echo get_user_background($u_id);
}

function user_birthdate($u_id = null)
{
	$timestamp = get_user_info("birthdate", $u_id);
	echo date("d.m.Y", $timestamp);
}

function user_age($u_id = null)
{
	$timestamp = get_user_info("birthdate", $u_id);
	$year = date("Y", $timestamp);
	$currentYear = date("Y");
	$age = $currentYear - $year;
	
	$month = date("n", $timestamp);
	$currentMonth = date("n");
	if($currentMonth < $month)
		$age--;
	else
	{
		$day = date("j", $timestamp);
		$currentDay = date("j");
		if($currentDay < $day)
			$age--;
	}
	echo $age;
}

function user_sex($u_id = null)
{
	$sex = get_user_info("sex", $u_id);
	if($sex == "m")
		echo "Mężczyzna";
	else if($sex == "k")
		echo "Kobieta";
}

function user_www($u_id = null)
{
	$www = get_user_info("www", $u_id);
	if(!$www)
	{
		echo "brak danych";
		return;
	}
	
	$href = $www;
	if(strpos($href, "http") === false)
		$href = "https://" . $href;
	else
		$www = preg_replace("/https?:\/\//", "", $www, 1);
	
	echo '<a href="' . $href . '">' . $www . '</a>';		
}

function user_registerdate($u_id = null)
{
	$timestamp = get_user_info("registerdate", $u_id);
	echo date("d.m.Y", $timestamp);
}

function does_user_exist($u_id)
{
	if(get_user_info("u_id", $u_id) === null)
		return 0;
	return 1;
}

function escape_input($input)
{
	$input = htmlspecialchars($input);
	$input = trim($input);
	return $input;
}

function call_action($action)
{
	switch($action)
	{
		case "friend_send_invite":
			send_friend_invite();
			break;
		case "friend_cancel_invite":
			cancel_friend_invite();
			break;
		case "friend_accept_invite":
			accept_friend_invite();
			break;
		case "friend_reject_invite":
			reject_friend_invite();
			break;
		case "friend_delete":
			delete_friend();
			break;
		case "get_friends_list":
			json_get_friends_list($_POST["user_id"]);
			break;
		case "get_images":
			json_get_images($_POST["user_id"]);
			break;
		case "get_threads":
			json_get_threads($_POST["user_id"]);
			break;
		case "avatar_delete":
			delete_user_avatar();
			break;
		case "background_delete":
			delete_user_background();
			break;
		case "add_image":
			add_image();
			break;
		case "image_thumb":
			give_image_thumb($_POST["image_id"], $_POST["thumb"]);
			break;
		case "image_comments":
			get_image_comments($_POST["image_id"]);
			break;
		case "image_post_comment":
			post_image_comment($_POST["image_id"], $_POST["comment"]);
			break;
		case "image_delete":
			delete_image($_POST["image_id"]);
			break;
		case "image_edit":
			edit_image($_POST["image_id"], $_POST["title"], $_POST["caption"]);
			break;
		case "new_thread":
			new_thread();
			break;
		case "thread_post_comment":
			post_thread_comment($_POST["thread_id"], $_POST["comment"]);
			break;
		case "thread_delete":
			delete_thread($_POST["thread_id"]);
			break;
		case "topic_edit":
			edit_thread($_POST["thread_id"], $_POST["topic"], $_POST["msg"]);
			break;
		case "search_page":
			search_page($_POST["query"]);
			break;
	}
}

function send_friend_invite()
{
	global $sqls;
	session_start();
	
	if(is_friend_invited($_POST["friend_id"]) !== null || is_user_invited($_POST["friend_id"]) !== null)
	{
		echo '{"success": false}';
		return;
	}
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["friend_invite"]);
	$stmt -> bind_param("ii", $_SESSION["user_id"], $_POST["friend_id"]);
	$stmt -> execute();
	if($stmt -> errno)
		echo '{"success": false}';
	else
		echo '{"success": true}';
	$db -> close();
}

function cancel_friend_invite()
{
	global $sqls;
	session_start();

	if(is_friend_invited($_POST["friend_id"]) !== 1)
	{
		echo '{"success": false}';
		return;
	}
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["friend_cancel"]);
	$stmt -> bind_param("ii", $_SESSION["user_id"], $_POST["friend_id"]);
	$stmt -> execute();
	if($stmt -> errno)
		echo '{"success": false}';
	else
		echo '{"success": true}';
	$db -> close();
}

function accept_friend_invite()
{
	global $sqls;
	session_start();
	
	if(is_user_invited($_POST["friend_id"]) !== 1)
	{
		echo '{"success": false}';
		return;
	}
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["friend_accept"]);
	$stmt -> bind_param("ii", $_SESSION["user_id"], $_POST["friend_id"]);
	$stmt -> execute();
	if($stmt -> errno)
		echo '{"success": false}';
	else
		echo '{"success": true}';
	$db -> close();
}

function reject_friend_invite()
{
	global $sqls;
	session_start();
	
	if(is_user_invited($_POST["friend_id"]) !== 1)
	{
		echo '{"success": false}';
		return;
	}
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["friend_reject"]);
	$stmt -> bind_param("ii", $_SESSION["user_id"], $_POST["friend_id"]);
	$stmt -> execute();
	if($stmt -> errno)
		echo '{"success": false}';
	else
		echo '{"success": true}';
	$db -> close();
}

function delete_friend()
{
	global $sqls;
	session_start();
	$_SESSION["user_id"];
	$_POST["friend-id"];
	
	if(!(is_friend_invited($_POST["friend-id"]) === 0 || is_user_invited($_POST["friend-id"]) === 0))
	{
		echo '{"success": false}';
		return;
	}
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["friend_delete"]);
	$stmt -> bind_param("iiii", $_SESSION["user_id"], $_POST["friend-id"], $_SESSION["user_id"], $_POST["friend-id"]);
	$stmt -> execute();
	if($stmt -> errno)
		echo '{"success": false}';
	else
		echo '{"success": true}';
	$db -> close();
}

/**
 * Czy użytkownik wysłał zaproszenie
 * Zwraca null jeśli nie wysłano zaproszenia, 1 jeśli wysłano, 0 jeśli zaakceptowano (są znajomymi)
 */
function is_friend_invited($friend_id)
{
	global $sqls;
	$invited = null;
	$db = db_connect();
	$stmt = $db -> prepare($sqls["is_friend_invited"]);
	$stmt -> bind_param("ii", $_SESSION["user_id"], $friend_id);
	$stmt -> execute();
	$stmt -> bind_result($invited);
	$stmt -> store_result();
	$stmt -> fetch();
	$db -> close();
	return $invited;
}

/**
 * Czy użytkownik otrzymał zaproszenie
 * Zwraca null jeśli nie wysłano zaproszenia, 1 jeśli wysłano, 0 jeśli zaakceptowano (są znajomymi)
 */
function is_user_invited($friend_id)
{
	global $sqls;
	$invited = null;
	$db = db_connect();
	$stmt = $db -> prepare($sqls["is_user_invited"]);
	$stmt -> bind_param("ii", $_SESSION["user_id"], $friend_id);
	$stmt -> execute();
	$stmt -> bind_result($invited);
	$stmt -> store_result();
	$stmt -> fetch();
	$db -> close();
	return $invited;
}

/**
 * Czy u1 lub u2 wysłali do siebie zaproszenie
 */
function is_user_invited_by_user($u1_id, $u2_id)
{
	global $sqls;
	$invited = null;
	$db = db_connect();
	$stmt = $db -> prepare($sqls["is_user_invited_by_user"]);
	$stmt -> bind_param("iiii", $u1_id, $u2_id, $u1_id, $u2_id);
	$stmt -> execute();
	$stmt -> bind_result($invited);
	$stmt -> store_result();
	$stmt -> fetch();
	$db -> close();
	return $invited;
}

/**
 * Czy użytkownik został zaproszony przez kogokolwiek
 * Czyli czy użytkownik ma niezatwierdzone zaproszenia od innych
 */
function is_user_invited_by_anyone()
{
	global $sqls;
	$invited = null;
	$db = db_connect();
	$stmt = $db -> prepare($sqls["is_user_invited_by_anyone"]);
	$stmt -> bind_param("i", $_SESSION["user_id"]);
	$stmt -> execute();
	$stmt -> bind_result($invited);
	$stmt -> store_result();
	$stmt -> fetch();
	$db -> close();
	return $invited;
}

/**
 * Czy użytkownik zaprosił kogokolwiek
 * Czyli czy użytkownik ma niezatwierdzone zaproszenia przez innych
 */
function is_any_friend_invited()
{
	global $sqls;
	$invited = null;
	$db = db_connect();
	$stmt = $db -> prepare($sqls["is_any_friend_invited"]);
	$stmt -> bind_param("i", $_SESSION["user_id"]);
	$stmt -> execute();
	$stmt -> bind_result($invited);
	$stmt -> store_result();
	$stmt -> fetch();
	$db -> close();
	return $invited;
}

function json_get_friends_list($user_id = null)
{
	global $sqls;
	session_start();
	
	if($user_id == null)
		$user_id = $_SESSION["user_id"];
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["select_user_friends_list"]);
	$stmt -> bind_param("i", $user_id);
	$stmt -> execute();
	$stmt -> bind_result($friend_id);
	$stmt -> store_result();
	
	$friends_list = array();
	
	while($stmt -> fetch())
	{
		$friend_list_data = array(
			"id" => $friend_id,
			"fullname" => get_user_full_name($friend_id),
			"avatar" => get_user_avatar($friend_id)
		);
		
		if($user_id != $_SESSION["user_id"])
		{
			if(is_user_invited_by_user($friend_id, $user_id))
				continue;
		}
		else
		{
			if(is_user_invited($friend_id))
				$friend_list_data["userInvited"] = true;
			if(is_friend_invited($friend_id))
				$friend_list_data["friendInvited"] = true;			
		}
		
		$friends_list[] = $friend_list_data;
	}
	$db -> close();
	echo json_encode($friends_list);
}

function json_get_images($user_id = null)
{
	global $sqls;
	session_start();
	
	if($user_id == null)
		$user_id = $_SESSION["user_id"];
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["select_user_images"]);
	$stmt -> bind_param("i", $user_id);
	$stmt -> execute();
	$stmt -> bind_result($i_id, $filename, $title, $caption);
	$stmt -> store_result();
	
	$image_list = array();
	
	while($stmt -> fetch())
	{
		if(!$title)
			$title = $filename;
		if(!$caption)
			$caption = "(brak opisu)";
		$image_list_data = array(
			"id" => $i_id,
			"userId" => $user_id,
			"filename" => $filename,
			"title" => $title, 
			"caption" => $caption,
			"thumbsUp" => get_image_thumbs_count($i_id, "up"),
			"thumbsDown" => get_image_thumbs_count($i_id, "down"),
			"thumbUpGiven" => is_image_thumb_given($i_id, "up"),
			"thumbDownGiven" => is_image_thumb_given($i_id, "down")
		);			
		$image_list["images"][] = $image_list_data;
	}
	if($user_id == $_SESSION["user_id"])
		$image_list["owner"] = true;
	$db -> close();
	echo json_encode($image_list);
}

function json_get_threads($user_id = null)
{
	global $sqls;
	session_start();
	
	if($user_id == null)
		$user_id = $_SESSION["user_id"];
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["select_user_threads"]);
	$stmt -> bind_param("i", $user_id);
	$stmt -> execute();
	$stmt -> bind_result($t_id, $author_id, $topic, $msg, $thread_date);
	$stmt -> store_result();
	
	$threads_list = array();
	
	while($stmt -> fetch())
	{
		$threads_list_data = array(
			"id" => $t_id,
			"authorId" => $author_id,
			"topic" => $topic,
			"msg" => nl2br($msg),
			"date" => date("d.m.Y H:i", $thread_date),
			"fullname" => get_user_full_name($author_id),
			"avatar" => get_user_avatar($author_id),
			"comments" => get_thread_comments($t_id),
			"owner" => false,
			"author" => false
		);			
		if($user_id == $_SESSION["user_id"])
			$threads_list_data["owner"] = true;
		if($author_id == $_SESSION["user_id"])
			$threads_list_data["author"] = true;
			
		$threads_list["threads"][] = $threads_list_data;
	}
	
	$db -> close();
	echo json_encode($threads_list);
}

function get_thread_comments($t_id)
{
	global $sqls;
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["select_thread_comments"]);
	$stmt -> bind_param("i", $t_id);
	$stmt -> execute();
	$stmt -> bind_result($u_id, $content, $comment_date);
	$stmt -> store_result();
	
	$comments_list = array();
	
	while($stmt -> fetch())
	{
		$threads_list_data = array(
			"id" => $u_id,
			"content" => $content,
			"date" => date("d.m.Y H:i", $comment_date),
			"fullname" => get_user_full_name($u_id),
			"avatar" => get_user_avatar($u_id)
		);			
		$comments_list[] = $threads_list_data;
	}
	
	$db -> close();
	return $comments_list;
}

function edit_thread($t_id, $topic, $msg)
{
	global $sqls;
	session_start();
	
	if(is_input_blank($topic) || is_input_blank($msg))
	{
		echo '{"success": false}';
		return;
	}
	
	$topic = escape_input($topic);
	$msg = escape_input($msg);
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["update_thread"]);
	$stmt -> bind_param("ssii", $topic, $msg, $t_id, $_SESSION["user_id"]);
	$stmt -> execute();
	
	if($stmt -> errno)
	{
		echo '{"success": false}';
		return;
	}
	
	$db -> close();
	
	$result = array();
	$result["success"] = true;
	$result["topic"] = $topic;
	$result["msg"] = $msg;
	
	echo json_encode($result);
}

function delete_thread($t_id)
{
	global $sqls;
	session_start();
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["delete_thread"]);
	$stmt -> bind_param("iii", $t_id, $_SESSION["user_id"], $_SESSION["user_id"]);
	$stmt -> execute();
	
	if($stmt -> errno || !$stmt -> affected_rows)
	{
		echo '{"success": false}';
		return;
	}
	
	$db -> close();
	
	echo '{"success": true}';
}

function process_update_user_info()
{
	global $regexps;
	global $countries;
	
	$db = db_connect();
	$db -> begin_transaction();
	
	if(empty($_POST))
		user_info_update_error(USER_INFO_UPDATE_ERROR_DEFAULT, $db);
	
	//Walidacja
	///Wypełnienie wymaganych pól
	if(empty($_POST["firstname"]))
		user_info_update_error(USER_INFO_UPDATE_ERROR_NO_FNAME, $db);
	
	if(empty($_POST["surname"]))
		user_info_update_error(USER_INFO_UPDATE_ERROR_NO_SNAME, $db);

	if(empty($_POST["email"]))
		user_info_update_error(USER_INFO_UPDATE_ERROR_NO_EMAIL, $db);

	if(empty($_POST["birthday"]) || empty($_POST["birthmonth"]) || empty($_POST["birthyear"]))
		user_info_update_error(USER_INFO_UPDATE_ERROR_NO_BIRTH, $db);

	if(empty($_POST["sex"]))
		user_info_update_error(USER_INFO_UPDATE_ERROR_NO_SEX, $db);
	
	if(empty($_POST["country"]))
		user_info_update_error(USER_INFO_UPDATE_ERROR_NO_COUNTRY, $db);	

	///Poprawność pól
	
	if(!preg_match($regexps["name"], $_POST["firstname"]) || mb_strlen($_POST["firstname"]) < 3)
		user_info_update_error(USER_INFO_UPDATE_ERROR_FNAME_FORMAT, $db);

	if(!preg_match($regexps["surname"], $_POST["surname"]) || mb_strlen($_POST["surname"]) < 3)
		user_info_update_error(USER_INFO_UPDATE_ERROR_SNAME_FORMAT, $db);

	if(!preg_match($regexps["email"], $_POST["email"]))
		user_info_update_error(USER_INFO_UPDATE_ERROR_EMAIL_FORMAT, $db);
	
	if(!is_date_correct($_POST["birthday"], $_POST["birthmonth"], $_POST["birthyear"]))
		user_info_update_error(USER_INFO_UPDATE_ERROR_DATE_FAKE, $db);

	if(!($_POST["sex"] == "m" || $_POST["sex"] == "k"))
		user_info_update_error(USER_INFO_UPDATE_ERROR_SEX_FAKE, $db);
	
	if(!isset($countries[$_POST["country"] - 1]))
		user_info_update_error(USER_INFO_UPDATE_ERROR_COUNTRY_FAKE, $db);
	
	$birthdate_timestamp = strtotime($_POST["birthday"] . "." . $_POST["birthmonth"] . "." . $_POST["birthyear"]);
	
	udpate_user_info("firstname", $_POST["firstname"], null, $db);
	udpate_user_info("surname", $_POST["surname"], null, $db);
	udpate_user_info("email", $_POST["email"], null, $db);
	udpate_user_info("birthdate", $birthdate_timestamp, null, $db);
	udpate_user_info("sex", $_POST["sex"], null, $db);
	udpate_user_info("country", $_POST["country"], null, $db);
	
	///Zmiana hasła
	if(!empty($_POST["password"]))
	{
		if(empty($_POST["rpassword"]))
			user_info_update_error(USER_INFO_UPDATE_ERROR_NO_PASSWORD, $db);
		
		if(!is_safe_password($_POST["password"]))
			user_info_update_error(USER_INFO_UPDATE_ERROR_PASSWORD_FORMAT, $db);

		if($_POST["password"] != $_POST["rpassword"])
			user_info_update_error(USER_INFO_UPDATE_ERROR_PASSWORD_NO_MATCH, $db);
		
		$hashed_password = password_hash($_POST["password"], PASSWORD_DEFAULT);
		udpate_user_info("password", $hashed_password, null, $db);
	}
	
	///Pola opcjonalne
	if(!empty($_POST["secondname"]))
		if(!preg_match($regexps["name"], $_POST["secondname"]) || mb_strlen($_POST["secondname"]) < 3)
			user_info_update_error(USER_INFO_UPDATE_ERROR_SECONDNAME_FORMAT, $db);
	udpate_user_info("secondname", $_POST["secondname"], null, $db);
	
	if(!empty($_POST["nickname"]))
		$_POST["nickname"] = escape_input($_POST["nickname"]);
	udpate_user_info("nickname", $_POST["nickname"], null, $db);
	
	if(!empty($_POST["region"]))
		$_POST["region"] = escape_input($_POST["region"]);
	udpate_user_info("region", $_POST["region"], null, $db);
	
	if(!empty($_POST["city"]))
		$_POST["city"] = escape_input($_POST["city"]);
	udpate_user_info("city", $_POST["city"], null, $db);
	
	if(!empty($_POST["phone_number"]))
		if(!preg_match($regexps["phone_number"], $_POST["phone_number"]))
			user_info_update_error(USER_INFO_UPDATE_ERROR_PHONE_FORMAT, $db);
	udpate_user_info("phone_number", $_POST["phone_number"], null, $db);
	
	if(!empty($_POST["skype"]))
		$_POST["skype"] = escape_input($_POST["skype"]);
	udpate_user_info("skype", $_POST["skype"], null, $db);
	
	if(!empty($_POST["www"]))
		if(!preg_match($regexps["www"], $_POST["www"]))
			user_info_update_error(USER_INFO_UPDATE_ERROR_WWW_FORMAT, $db);
	udpate_user_info("www", $_POST["www"], null, $db);
	
	if(!empty($_POST["bio"]))
		$_POST["bio"] = escape_input($_POST["bio"]);
	udpate_user_info("bio", $_POST["bio"], null, $db);

	//Przesyłanie zdjęcia i tła profilowego
	if(!empty($_FILES["avatar"]["name"]))
	{
		if($_FILES["avatar"]["size"] > 8 * 1024 * 1024)	//Sprawdzamy rozmiar
			user_info_update_error(USER_INFO_UPDATE_ERROR_AVATAR_BIG_SIZE, $db);
		if(strpos(mime_content_type($_FILES["avatar"]["tmp_name"]), "image/") === false) //Sprawdzamy typ
			user_info_update_error(USER_INFO_UPDATE_ERROR_AVATAR_FORMAT, $db);
			
		$image_info = getimagesize($_FILES["avatar"]["tmp_name"]);

		if($image_info[0] < 192 || $image_info[1] < 192)	//Sprawdzamy wymiary
			user_info_update_error(USER_INFO_UPDATE_ERROR_AVATAR_SMALL, $db);
		
		//Zmieniamy nazwę pliku
		if(get_user_avatar_type() == AVATAR_MAIN)
			rename(get_user_avatar_path(), AVATARS_OLD_PATH . $_SESSION["user_id"]);
		
		//Aktualizujemy bazę danych
		udpate_user_info("avatar", $_FILES["avatar"]["name"], null, $db);
		
	}
	
	if(!empty($_FILES["background"]["name"]))
	{
		if($_FILES["background"]["size"] > 8 * 1024 * 1024)	//Sprawdzamy rozmiar
			user_info_update_error(USER_INFO_UPDATE_ERROR_AVATAR_BIG_SIZE, $db);
		if(strpos(mime_content_type($_FILES["background"]["tmp_name"]), "image/") === false) //Sprawdzamy typ
			user_info_update_error(USER_INFO_UPDATE_ERROR_AVATAR_FORMAT, $db);
			
		$image_info = getimagesize($_FILES["background"]["tmp_name"]);

		if($image_info[0] < 1024 || $image_info[1] < 250)	//Sprawdzamy wymiary
			user_info_update_error(USER_INFO_UPDATE_ERROR_BACKGROUND_SMALL, $db);
			
		//Zmieniamy nazwę pliku
		if(get_user_background_type() == BACKGROUND_MAIN)
			rename(get_user_background_path(), BACKGROUNDS_OLD_PATH . $_SESSION["user_id"]);
		
		//Aktualizujemy bazę danych
		udpate_user_info("background", $_FILES["background"]["name"], null, $db);
	}
	
	$db -> commit();
	
	//Przenosimy pliki
	if(!empty($_FILES["avatar"]["name"]))
	{
		if(!file_exists(AVATARS_PATH . $_SESSION["user_id"]))
			mkdir(AVATARS_PATH . $_SESSION["user_id"]);
		move_uploaded_file($_FILES["avatar"]["tmp_name"], AVATARS_PATH . $_SESSION["user_id"] . DIRECTORY_SEPARATOR . $_FILES["avatar"]["name"]);
	}
	if(!empty($_FILES["background"]["name"]))
	{
		if(!file_exists(BACKGROUNDS_PATH . $_SESSION["user_id"]))
			mkdir(BACKGROUNDS_PATH . $_SESSION["user_id"]);
		move_uploaded_file($_FILES["background"]["tmp_name"], BACKGROUNDS_PATH . $_SESSION["user_id"] . DIRECTORY_SEPARATOR . $_FILES["background"]["name"]);
	}
	
	setcookie("user-info-update-success", 1);
	$db -> close();
}

function user_info_update_error($errno, $db)
{
	$db -> rollback();
	$db -> close();
	setcookie("user-info-update-error", $errno);
	header("Location: ustawienia.php");
	die();
}

function get_user_info_update_alert()
{
	global $msgs;
	if(isset($_COOKIE["user-info-update-error"]))
	{
		$errno = $_COOKIE["user-info-update-error"];
		echo $msgs["user_info_update"][$errno];
	}
}

function udpate_user_info($info_col, $value, $u_id = null, $db = null)
{
	global $sqls;
	$passed_db = $db;
	
	if($u_id == null)
		$u_id = $_SESSION["user_id"];
	if($db == null)
		$db = db_connect();
	
	
	$query = str_replace("{info_col}", $info_col, $sqls["update_user_info"]);
	
	$stmt = $db -> prepare($query);
	$stmt -> bind_param("si", $value, $u_id);
	$stmt -> execute();
	
	if($stmt -> errno == 1062)
		user_info_update_error(USER_INFO_UPDATE_ERROR_EXISTS, $db);
	else if($stmt -> errno)
		user_info_update_error(USER_INFO_UPDATE_ERROR_DEFAULT, $db);
	
	if($passed_db == null)
		$db -> close();
}

function is_safe_password($password)
{
	global $regexps;
	
	if(mb_strlen($password) < 8)
		return 0;

	if(!preg_match($regexps["lowercase_letters"], $password))
		return 0;

	if(!preg_match($regexps["uppercase_letters"],$password))
		return 0;

	if(!preg_match($regexps["numbers"], $password))
		return 0;

	if(!preg_match($regexps["special_characters"], $password))
		return 0;

	if(preg_match("/\s/", $password))
		return 0;
	
	return 1;
}

function is_date_correct($day, $month, $year)
{
	$current_year = date("Y");
	
	if($day < 1 || $month < 1 || $year < 1970 || $month > 12 || $year > $current_year)
		return 0;
		
	if(in_array($month, array(1, 3, 5, 7, 8, 10, 12)) && $day > 31)
		return 0;

	if(in_array($month, array(4, 6, 9, 11)) && $day > 30)
		return 0;

	if($month == 2)
		if($day > 29 || ($year % 4 && $day > 28))
			return 0;
	
	return 1;
}

function get_user_avatar_path($u_id = null)
{
	$avatar = get_user_avatar($u_id);
	$path = str_replace("/", DIRECTORY_SEPARATOR, $avatar);
	return $path;
}

function get_user_background_type($u_id = null)
{
	if($u_id == null)
		$u_id = $_SESSION["user_id"];
	
	$background = get_user_info("background", $u_id);
	
	if(!$background)
		return BACKGROUND_NONE;
	else
	{
		
		if(file_exists(BACKGROUNDS_PATH . $u_id . DIRECTORY_SEPARATOR . $background))
			return BACKGROUND_MAIN;
		else if(file_exists(BACKGROUNDS_OLD_PATH . $u_id))
			return BACKGROUND_OLD;
		else
			return BACKGROUND_NONE;
	}
}

function get_user_background_path($u_id = null)
{
	$background = get_user_background($u_id);
	$path = str_replace("/", DIRECTORY_SEPARATOR, $background);
	return $path;
}

function delete_user_avatar($u_id = null)
{
	session_start();
	
	if($u_id == null)
		$u_id = $_SESSION["user_id"];
	
	$avatar_path = get_user_avatar_path($u_id);
	$avatar_type = get_user_avatar_type($u_id);
	udpate_user_info("avatar", null, $u_id);
	if($avatar_type != AVATAR_PLACEHOLDER)
		unlink($avatar_path);
	else
	{
		echo '{"success": false}';
		return;
	}
	
	echo '{"success": true}';
}

function delete_user_background($u_id = null)
{
	session_start();
	
	if($u_id == null)
		$u_id = $_SESSION["user_id"];
	
	$background_path = get_user_background_path($u_id);
	$background_type = get_user_background_type($u_id);
	udpate_user_info("background", null, $u_id);
	if($background_type != BACKGROUND_NONE)
		unlink($background_path);
	else
	{
		echo '{"success": false}';
		return;
	}
	
	echo '{"success": true}';
}

function file_extension($file_dir, $dot = false)
{
	$extension = "";
	$path_info = pathinfo($file_dir);
	if(isset($path_info["extension"]))
		$extension = (($dot) ? "." : "") . $path_info["extension"];
	return $extension;
}

function add_image($u_id = null)
{
	global $sqls;
	session_start();
	
	if($u_id == null)
		$u_id = $_SESSION["user_id"];
	
	if(!isset($_POST["title"]) || !isset($_POST["caption"]))
		add_image_error(ADD_IMAGE_ERROR_DEFAULT, $u_id);
	if(empty($_FILES["image"]["name"]))
		add_image_error(ADD_IMAGE_ERROR_NO_FILE, $u_id);
	
	$_POST["title"] = escape_input($_POST["title"]);
	$_POST["caption"] = escape_input($_POST["caption"]);
	
	if($_FILES["image"]["size"] > 8 * 1024 * 1024)	//Sprawdzamy rozmiar
		add_image_error(ADD_IMAGE_ERROR_BIG_SIZE, $u_id);
		
	if(strpos(mime_content_type($_FILES["image"]["tmp_name"]), "image/") === false) //Sprawdzamy typ
		add_image_error(ADD_IMAGE_ERROR_FORMAT, $u_id);
	
	if(!file_exists(IMAGES_PATH . $u_id))
		mkdir(IMAGES_PATH . $u_id);
	
	$i = 0;
	$filename = $_FILES["image"]["name"];
	$extension = file_extension($_FILES["image"]["name"]);
	
	while(file_exists(IMAGES_PATH . $u_id . DIRECTORY_SEPARATOR . $_FILES["image"]["name"]))	//Zmieniamy nazwę pliku jeśli taki już istnieje
	{
		$_FILES["image"]["name"] = $filename;
		$_FILES["image"]["name"] = basename($_FILES["image"]["name"], "." . $extension) . "_" . $i . "." . $extension;
		$i++;
	}
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["add_image"]);
	$stmt -> bind_param("isss", $u_id, $_FILES["image"]["name"], $_POST["title"], $_POST["caption"]);
	$stmt -> execute();
	if($stmt -> errno)
		add_image_error(ADD_IMAGE_ERROR_DEFAULT, $u_id);
	
	move_uploaded_file($_FILES["image"]["tmp_name"], IMAGES_PATH . $u_id . DIRECTORY_SEPARATOR . $_FILES["image"]["name"]);
	
	$db -> close();
	header("Location: profil.php?id=" . $u_id . "&tab=zdjecia");
}

function add_image_error($errno, $u_id)
{
	setcookie("add-image-error", $errno);
	header("Location: profil.php?id=" . $u_id . "&tab=zdjecia");
	die();
}

function get_add_image_alert()
{
	global $msgs;
	if(isset($_COOKIE["add-image-error"]))
	{
		$errno = $_COOKIE["add-image-error"];
		echo $msgs["add_image"][$errno];
	}
}

function give_image_thumb($i_id, $thumb)
{
	global $sqls;
	session_start();
	
	if($thumb === "up" || $thumb === 1)
		$thumb = 1;
	else if($thumb === "down" || $thumb === 0)
		$thumb = 0;
	else
	{
		echo '{"success": false}';
		return;
	}

	$db = db_connect();
	$stmt = $db -> prepare($sqls["give_thumb"]);
	$stmt -> bind_param("iis", $i_id, $_SESSION["user_id"], $thumb);
	
	if(is_image_thumb_given($i_id, $thumb))
	{
		if(take_image_thumb($i_id, $thumb))
			echo '{"success": true, "thumbsUp": ' . get_image_thumbs_count($i_id, "up") . ', "thumbsDown": ' . get_image_thumbs_count($i_id, "down") . '}';
		else
			echo '{"success": false}';
		return;
	}
	if(is_image_thumb_given($i_id, !$thumb))
	{
		if(!take_image_thumb($i_id, !$thumb))
		{
			echo '{"success": false}';
			return;
		}
	}
	
	$stmt -> execute();
	if($stmt -> errno)
	{
		echo '{"success": false}';
		return;
	}
	
	$db -> close();
	echo '{"success": true, "thumbsUp": ' . get_image_thumbs_count($i_id, "up") . ', "thumbsDown": ' . get_image_thumbs_count($i_id, "down") . '}';
}

function take_image_thumb($i_id, $thumb)
{
	global $sqls;
	
	if($thumb === "up" || $thumb === 1)
		$thumb = 1;
	else if($thumb === "down" || $thumb === 0)
		$thumb = 0;
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["take_thumb"]);
	$stmt -> bind_param("ii", $i_id, $_SESSION["user_id"]);
	$stmt -> execute();
	
	if($stmt -> errno)
		return 0;
	
	$db -> close();
	return 1;
}

function is_image_thumb_given($i_id, $thumb)
{
	global $sqls;
	
	if($thumb === "up" || $thumb === 1)
		$thumb = 1;
	else if($thumb === "down" || $thumb === 0)
		$thumb = 0;
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["is_thumb_given"]);
	$stmt -> bind_param("ii", $i_id, $_SESSION["user_id"]);
	$stmt -> execute();
	$stmt -> bind_result($given_thumb);
	$stmt -> store_result();
	$stmt -> fetch();
	$db -> close();
	if($thumb == $given_thumb && $given_thumb !== null)
		return 1;
	
	return 0;
}

function get_image_thumbs_count($i_id, $thumb)
{
	global $sqls;
	
	if($thumb === "up" || $thumb === 1)
		$thumb = 1;
	else if($thumb === "down" || $thumb === 0)
		$thumb = 0;
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["select_thumbs_count"]);
	$stmt -> bind_param("ii", $i_id, $thumb);
	$stmt -> execute();
	$stmt -> bind_result($count);
	$stmt -> store_result();
	$stmt -> fetch();
	
	if($stmt -> errno)
		return 0;
	
	if($count == null)
		$count = 0;
	
	$db -> close();
	
	return $count;
}

function get_image_comments($i_id)
{
	global $sqls;
	session_start();
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["select_image_comments"]);
	$stmt -> bind_param("i", $i_id);
	$stmt -> execute();
	$stmt -> bind_result($u_id, $content, $date);
	$stmt -> store_result();
	
	$comments_list["comments"] = array();
	while($stmt -> fetch())
	{
		$comments_list_item = array(
			"userId" => $u_id,
			"fullname" => get_user_full_name($u_id),
			"avatar" => get_user_avatar($u_id),
			"content" => $content,
			"date" => date("d.m.Y H:i", $date)
		);
		$comments_list["comments"][] = $comments_list_item;
	}
	
	if($stmt -> errno)
	{
		echo '{"success": false}';
		return;
	}
	
	$db -> close();
	$comments_list["success"] = true;
	
	echo json_encode($comments_list);
	
}

function post_image_comment($i_id, $comment)
{
	global $sqls;
	session_start();

	if(is_input_blank($comment)){
		echo '{"success": false}';
		return;
	}
	
	$comment = escape_input($comment);
	$timestamp = time();
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["post_image_comment"]);
	$stmt -> bind_param("iiss", $i_id, $_SESSION["user_id"], $comment, $timestamp);
	$stmt -> execute();
	
	if($stmt -> errno)
	{
		echo '{"success": false}';
		return;
	}
	
	$db -> close();
	$result = array();
	$result["success"] = true;
	$result["comment"] = array(
		"userId" => $_SESSION["user_id"],
		"fullname" => get_user_full_name($_SESSION["user_id"]),
		"avatar" => get_user_avatar($_SESSION["user_id"]),
		"content" => $comment,
		"date" => date("d.m.Y H:i")
	);
	echo json_encode($result);
}

function post_thread_comment($t_id, $comment)
{
	global $sqls;
	session_start();

	if(is_input_blank($comment)) {
		echo '{"success": false}';
		return;
	}
	
	$comment = escape_input($comment);
	$timestamp = time();
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["post_thread_comment"]);
	$stmt -> bind_param("iiss", $t_id, $_SESSION["user_id"], $comment, $timestamp);
	$stmt -> execute();
	
	if($stmt -> errno)
	{
		echo '{"success": false}';
		return;
	}
	
	$db -> close();
	$result = array();
	$result["success"] = true;
	$result["comment"] = array(
		"userId" => $_SESSION["user_id"],
		"fullname" => get_user_full_name($_SESSION["user_id"]),
		"avatar" => get_user_avatar($_SESSION["user_id"]),
		"content" => $comment,
		"date" => date("d.m.Y H:i")
	);
	echo json_encode($result);
}

function get_image_filename($i_id)
{
	global $sqls;
	$db = db_connect();
	$stmt = $db -> prepare($sqls["select_image_filename"]);
	$stmt -> bind_param("i", $i_id);
	$stmt -> execute();
	$stmt -> bind_result($filename);
	$stmt -> store_result();
	$stmt -> fetch();
	return $filename;
}

function delete_image($i_id)
{
	global $sqls;
	session_start();
	
	$filename = get_image_filename($i_id);
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["delete_image"]);
	$stmt -> bind_param("ii", $i_id, $_SESSION["user_id"]);
	$stmt -> execute();
	
	if($stmt -> errno || !$stmt -> affected_rows)
	{
		echo '{"success": false}';
		return;
	}
	
	unlink(IMAGES_PATH . DIRECTORY_SEPARATOR . $_SESSION["user_id"] . DIRECTORY_SEPARATOR . $filename);
	
	$db -> close();
	
	echo '{"success": true}';
}

function edit_image($i_id, $title, $caption)
{
	global $sqls;
	session_start();
	
	if(is_blank($title))
		$title = "";
	if(is_blank($caption))
		$caption = "";
	
	$title = escape_input($title);
	$caption = escape_input($caption);
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["update_image"]);
	$stmt -> bind_param("ssii", $title, $caption, $i_id, $_SESSION["user_id"]);
	$stmt -> execute();
	
	if($stmt -> errno)
	{
		echo '{"success": false}';
		return;
	}
	
	$db -> close();
	
	if($title == "")
		$title = get_image_filename($i_id);
	if($caption == "")
		$caption = "(brak opisu)";
	
	$result = array();
	$result["success"] = true;
	$result["title"] = $title;
	$result["caption"] = $caption;
	
	echo json_encode($result);
}

function new_thread()
{
	global $sqls;
	session_start();
	
	if(is_input_blank($_POST["topic"]) || is_input_blank($_POST["msg"]))
		new_thread_error(NEW_THREAD_ERROR_CONTENT, $_GET["u_id"]);
	
	$_GET["u_id"] = escape_input($_GET["u_id"]);
	$_POST["topic"] = escape_input($_POST["topic"]);
	$_POST["msg"] = escape_input($_POST["msg"]);
	
	if(is_input_blank($_GET["u_id"]))
		$_GET["u_id"] = $_SESSION["user_id"];
	
	$timestamp = time();
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["new_thread"]);
	$stmt -> bind_param("iisss", $_GET["u_id"], $_SESSION["user_id"], $_POST["topic"], $_POST["msg"], $timestamp);
	$stmt -> execute();
	if($stmt -> errno)
		new_thread_error(NEW_THREAD_ERROR_DEFAULT, $_GET["u_id"]);
	
	$db -> close();
	header("Location: profil.php?id=" . $_GET["u_id"] . "&tab=tablica");
}

function new_thread_error($errno, $u_id)
{
	setcookie("new-thread-error", $errno);
	header("Location: profil.php?id=" . $u_id . "&tab=tablica");
	die();
}

function is_blank($value)
{
	return empty($value) && !is_numeric($value);
}

function is_input_blank($value)
{
	return is_blank(escape_input($value));
}

function get_new_thread_alert()
{
	global $msgs;
	if(isset($_COOKIE["new-thread-error"]))
	{
		$errno = $_COOKIE["new-thread-error"];
		echo $msgs["new_thread"][$errno];
	}
}

function search_page($query)
{
	global $sqls;
	
	if(is_input_blank($query))
	{
		echo '{"success": false}';
		return;
	}
	
	$query = "%" . escape_input($query) . "%";
	
	$db = db_connect();
	$stmt = $db -> prepare($sqls["search_page"]);
	$stmt -> bind_param("s", $query);
	$stmt -> execute();
	
	if($stmt -> errno)
	{
		echo '{"success": false}';
		return;
	}
	
	$stmt -> store_result();
	$stmt -> bind_result($u_id);
	
	$result = array();
	$result["items"] = array();
	while($stmt -> fetch())
	{
		$search_result_item = array(
			"id" => $u_id,
			"fullname" => get_user_full_name($u_id),
			"avatar" => get_user_avatar($u_id)
		);
		$result["items"][] = $search_result_item;
	}
	
	$db -> close();
	
	$result["success"] = true;
	echo json_encode($result);
}

?>