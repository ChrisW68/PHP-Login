<?php



/****************helper functions ********************/


function clean($string) {


return htmlentities($string);


}



function redirect($location){


return header("Location: {$location}");

}


function set_message($message) {


	if(!empty($message)){


		$_SESSION['message'] = $message;

	}else {

		$message = "";

	}


}



function display_message(){


	if(isset($_SESSION['message'])) {


		echo $_SESSION['message'];

		unset($_SESSION['message']);

	}



}



function token_generator(){


$token = $_SESSION['token'] =  md5(uniqid(mt_rand(), true));

return $token;


}


function validation_errors($error_message) {

$error_message = <<<DELIMITER

<div class="alert alert-danger alert-dismissible" role="alert">
  	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  	<strong>Warning!</strong> $error_message
 </div>
DELIMITER;

return $error_message;
		




}



function email_exists($email) {

	$sql = "SELECT id FROM users WHERE email = '$email'";

	$result = query($sql);

	if(row_count($result) == 1 ) {

		return true;

	} else {


		return false;

	}



}



function username_exists($username) {

	$sql = "SELECT id FROM users WHERE username = '$username'";

	$result = query($sql);

	if(row_count($result) == 1 ) {

		return true;

	} else {


		return false;

	}



}


function send_email($email, $subject, $msg, $headers){


return mail($email, $subject, $msg, $headers);


}



/****************Validation functions ********************/



function validate_user_registration(){

	$errors = [];

	$min = 3;
	$max = 20;



	if($_SERVER['REQUEST_METHOD'] == "POST") {


		$first_name 		= clean($_POST['first_name']);
		$last_name 			= clean($_POST['last_name']);
		$username 		    = clean($_POST['username']);
		$email 				= clean($_POST['email']);
		$password			= clean($_POST['password']);
		$confirm_password	= clean($_POST['confirm_password']);



		if(strlen($first_name) < $min) {

			$errors[] = "Your first name cannot be less than {$min} characters";

		}

		if(strlen($first_name) > $max) {

			$errors[] = "Your first name cannot be more than {$max} characters";

		}




		if(strlen($last_name) < $min) {

			$errors[] = "Your Last name cannot be less than {$min} characters";

		}


		if(strlen($last_name) > $max) {

			$errors[] = "Your Last name cannot be more than {$max} characters";

		}

		if(strlen($username) < $min) {

			$errors[] = "Your Username cannot be less than {$min} characters";

		}

		if(strlen($username) > $max) {

			$errors[] = "Your Username cannot be more than {$max} characters";

		}


		if(username_exists($username)){

			$errors[] = "Sorry that username is already is taken";

		}



		if(email_exists($email)){

			$errors[] = "Sorry that email already is registered";

		}




		if(strlen($email) < $min) {

			$errors[] = "Your email cannot be more than {$max} characters";

		}

		if($password !== $confirm_password) {

			$errors[] = "Your password fields do not match";

		}



		if(!empty($errors)) {

			foreach ($errors as $error) {

			echo validation_errors($error);

			
			}


		} else {


			if(register_user($first_name, $last_name, $username, $email, $password)) {



				set_message("<p class='bg-success text-center'>Please check your email or spam folder for activation link</p>");

				redirect("index.php");


			} else {


				set_message("<p class='bg-danger text-center'>Sorry we could not register the user</p>");

				redirect("index.php");

			}



		}



	} // post request 



} // function 

/****************Register user functions ********************/

function register_user($first_name, $last_name, $username, $email, $password) {


	$first_name = escape($first_name);
	$last_name  = escape($last_name);
	$username   = escape($username);
	$email      = escape($email);
	$password   = escape($password);



	if(email_exists($email)) {


		return false;


	} else if (username_exists($username)) {

		return false;

	} else {

		$password   = md5($password);

		$validation_code = md5($username + microtime());

		$sql = "INSERT INTO users(first_name, last_name, username, email, password, validation_code, active)";
		$sql.= " VALUES('$first_name','$last_name','$username','$email','$password','$validation_code', 0)";
		$result = query($sql);
		confirm($result);


		$subject = "Activate Account";
		$msg = " Please click the link below to activate your Account
		http://edwincodecollege.com/login_app/activate.php?email=$email&code=$validation_code
		";

		$headers = "From: noreply@edwincodecollege.com";
		send_email($email, $subject, $msg, $headers);
		return true;
	}
} 


/****************Activate user functions ********************/


function activate_user() {


	if($_SERVER['REQUEST_METHOD'] == "GET") {


		if(isset($_GET['email'])) {


			$email = clean($_GET['email']);

			$validation_code = clean($_GET['code']);


			$sql = "SELECT id FROM users WHERE email = '".escape($_GET['email'])."' AND validation_code = '".escape($_GET['code'])."' ";
			$result = query($sql);
			confirm($result);

			if(row_count($result) == 1) {

			$sql2 = "UPDATE users SET active = 1, validation_code = 0 WHERE email = '".escape($email)."' AND validation_code = '".escape($validation_code)."' ";	
			$result2 = query($sql2);
			confirm($result2);

			set_message("<p class='bg-success'>Your account has been activated please login</p>");

			redirect("login.php");


		} else {

			set_message("<p class='bg-danger'>Sorry Your account could not be activated </p>");

			redirect("login.php");
			}
		} 
	}
} // function 

/****************Validate user login functions ********************/

function validate_user_login(){

	$errors = [];

	$min = 3;
	$max = 20;

	if($_SERVER['REQUEST_METHOD'] == "POST") {

		$email 		= clean($_POST['email']);
		$password	= clean($_POST['password']);
		$remember   = isset($_POST['remember']);

		if(empty($email)) {

			$errors[] = "Email field cannot be empty";

		}

		if(empty($password)) {

			$errors[] = "Password field cannot be empty";

		}

		if(!empty($errors)) {
				foreach ($errors as $error) {
				echo validation_errors($error);
				}

			} else {
				if(login_user($email, $password, $remember)) {
					redirect("admin.php");
				} else {
				echo validation_errors("Your credentials are not correct");		

				}
			}
	}
} // function 

/****************User login functions ********************/


	function login_user($email, $password, $remember) {


		$sql = "SELECT password, id FROM users WHERE email = '".escape($email)."' AND active = 1";

		$result = query($sql);

		if(row_count($result) == 1) {

			$row = fetch_array($result);
			$db_password = $row['password'];


			if(md5($password) === $db_password) {
				//If $remember variable is equal to "on" then the cookie will be set
				if($remember == "on") {
					//set the cookie with the first parameter to 'email' the second parameter to content and the third parameter to time with the time function which will make the cookie expiration time to one day, ie: 60 seconds * 60 minutes * 24 hours.
					setcookie('email', $email, time() + 86400);
				}

				$_SESSION['email'] = $email;
					return true;
			} else {
				return false;
			}
			return true;

		} else {
			return false;
		}
	} // end of function

/****************logged in function ********************/

function logged_in(){
	//Iff $_SESSION has email filled out or cookie is set to email then return true
	if(isset($_SESSION['email']) || isset($_COOKIE['email'])){
		return true;

	} else {
		return false;
	}

}	// functions

