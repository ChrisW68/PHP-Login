<?php

// Helper Function

function clean($string) {

	return htmlentities($string);

}

function redirect($location) {
	return header("Location: {$location}");
}

function set_message($message) {
	if(!empty($message)) {
		$_SESSION['message'] =$message;
	}else {
		$message = "";
	}
}

function display_message() {
	if(isset($_SESSION['message'])) {
		echo $_SESSION['message'];
		unset($_SESSION['message']);
	}
}

//Form is coming from a certain page and adds security
function token_generator() {
	$token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));
	return $token;
}

//Error message function for validation of user fields
function validation_errors($error_message) {
	$error_message = <<<DELIMITER

		<div class="alert alert-danger alert-dismissible" role="alert"><strong>Warning!</strong> $error_message <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-label="Close"></span></div>
DELIMITER;
		return $error_message;
}

function email_exists($email) {
	$sql = "SELECT id FROM users WHERE email = '$email'";

	$result = query($sql);
	if(row_count($result) == 1) {
		return true;
	} else {
		return false;
	}
}

function username_exists($username) {
	$sql = "SELECT id FROM users WHERE username = '$username'";

	$result = query($sql);
	if(row_count($result) == 1) {
		return true;
	} else {
		return false;
	}
}
// Send email to new user for verification
function send_email($email, $subject, $msg, $headers) {

	return mail($email, $subject, $msg, $headers);

}
// Validation Function

function validate_user_registration() {
	$errors = [];

	$min = 3;
	$max = 15;
	$minuser = 5;
	$maxuser = 20;
	$maxemail = 40;



	if($_SERVER['REQUEST_METHOD'] == "POST") {
		$first_name =clean($_POST['first_name']);
		$last_name =clean($_POST['last_name']);
		$username =clean($_POST['username']);
		$email =clean($_POST['email']);
		$password =clean($_POST['password']);
		$confirm_password =clean($_POST['confirm_password']);

	if(strlen($first_name) < $min) {
		$errors[] = "Your name is not long name. Must be {$min} long</br>";
	}

	if(strlen($first_name) > $max) {
		$errors[] = "Your first name is too long. Must be {$max}</br>";
	}

	if(strlen($last_name) < $min) {
		$errors[] = "Your last name is not long name. Must be {$min} long</br>";
	}

	if(strlen($last_name) > $max) {
		$errors[] = "Your last name is too long. Must be {$max}</br>";
	}

	if(username_exists($username)) {
		$errors[] = "Your username already exist in the system<br>";	
	}

	if(strlen($username) < $minuser) {
		$errors[] = "Your username is not long name. Must be {$min} long</br>";
	}

	if(strlen($username) > $maxuser) {
		$errors[] = "Your username is too long. Must be {$maxuser} </br>";
	}
	if(email_exists($email)) {
			$errors[] = "Your email already exist in system<br>";	
	}
	if(strlen($email) > $maxemail) {
		
		$errors[] = "Your email is too long.<br>  Must be {$maxemail} </br>";
	}

	if(strlen($password) < $minuser) {
		$error[] = "Your password was too short {$minuser}";
	}
	//Outputs error message when the password field does not match with confirm password field
	if($password !== $confirm_password) {
		$error[] = "Your password fields are not matching.";
	}

		if(!empty($errors)) {
			foreach ($errors as $error) {
				echo validation_errors($error);
				
			}
			
		} else {
				if (register_user($first_name, $last_name, $username, $email, $password)){
					set_message("<p class='bg-success text-center'>Please check email or spam folder for activation link!</p>");
					redirect("index.php");
				}else {
					if (register_user($first_name, $last_name, $username, $email, $password)){
					set_message("<p class='bg-fail text-center'>Cannot be registered the user</p>");
					redirect("index.php");
				}

		}
	}//post request

}//function

/****** Register User ***/

function register_user($first_name, $last_name, $username, $email, $password) {

	$first_name = escape($first_name);
	$last_name = escape($last_name);
	$username = escape($username);
	$email = escape($email);
	$password = escape($password);


	if(email_exists($email)) {
		return false;

	} else if (username_exists($username)) {
		return false;

	} else {
		//function will encrypt password
		$password = md5($password);

		$validation_code = md5($username + microtime());

		//Insert new login into table
		$sql = "INSERT INTO users(first_name, last_name, username, email, password, validation_code, active)";
		$sql.= " VALUES('$first_name','$last_name','$username','$email','$password','$validation_code',0)";
		$result = query($sql);
		confirm($result);

		$subject = "Activate Account";
		$msg = " Please cick the link below to activate your Account

			http://localhost/login/activate.php?email=$email&code=$validation_code";

		$headers = "FROM: nonreply@yourwebsite.com";

		send_emai($email, $subject, $msg, $headers);

		return true;
	}
}

}

/****** Activate User ******/
function activate_user() {

	if($_SERVER['REQUEST_METHOD'] == "GET") {
		if(isset($_GET['email'])) {
			$email = clean($_GET['email']);
			$validation_code = clean($_GET['code']);

			$sql = "SELECT id FROM users WHERE email = '".escape($_GET['email'])."' AND validation_code = '".escape($_GET['code'])."' ";
			$result = query($sql);
			confirm($result);

			if(row_count($result) == 1) {

				$sql2 = "UPDATE users set active = 1, validation_code = 0 WHERE email = '".escape($email)."' AND validation_code = '".escape($validation_code)."' ";
				$result2 = query($sql2);
				confirm($result2);


				set_message("<p class='bg-success'>Your account has been activated, please login</p>");
				redirect("login.php");
			} else {
				set_message("<p class='bg-danger'>Sorry, Account cannot be activated</p>");
				redirect("login.php");
				}
		}
	}

}

/***

http://localhost/login/activate.php?email=chris.wiseman74@gmail.com&code=84475e4475acc60745b7ba88248368dc

***/
?>

