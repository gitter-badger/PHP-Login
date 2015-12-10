<?php
// Extend this class to re-use db connection
class dbConn {
	
	public $conn;
	public function __construct(){
		
		include 'config.php';
		// Connect to server and select database.
		$this->conn = new PDO('mysql:host='.$host.';dbname='.$db_name.';charset=utf8', $username, $password);
		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
		
	 }
};

class selectEmail extends dbConn {

		public function emailPull($id) {
		
		try {
			
			$edb = new dbConn;

			$eerr = '';
					
			}
		
		catch (PDOException $e) {
			$eerr = "Error: " . $e->getMessage();
		}
		
		//Queries database
		$estmt = $edb->conn->query("SELECT email, username FROM members WHERE id = '$id'");
		
		$eresult = $estmt->fetch(PDO::FETCH_ASSOC);
			
		return $eresult;
			
	}
	
};
	
class loginForm extends dbConn {
		
	public function checkLogin($tbl_name, $myusername, $mypassword) {
		
		try {
			
			$db = new dbConn;

			$err = '';
					
			}
		
		catch (PDOException $e) {
			$err = "Error: " . $e->getMessage();
		}
		
		$stmt = $stmt = $db->conn->query("SELECT * FROM $tbl_name WHERE username='$myusername'");

		// Gets query result
		$result = $stmt->fetch(PDO::FETCH_ASSOC);

	// Checks password entered against db password hash 
		if(password_verify($mypassword, $result['password']) && $result['verified'] == '1' ){

			// Register $myusername, $mypassword and return "true"
			$success = 'true';

		}
		
		elseif(password_verify($mypassword, $result['password']) && $result['verified'] == '0' ){

			// Register $myusername, $mypassword and return "true"
			$success = "<div class=\"alert alert-danger alert-dismissable\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>Your account has been created, but you cannot log in until it has been verified</div>";

		}

		else {
			//return the error message
			$success = "<div class=\"alert alert-danger alert-dismissable\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>Wrong Username or Password</div>";
		}

		return $success;

	}
		
};

class mailSender {

	public function sendMail($email, $user, $id, $type) {
		
		require 'scripts/PHPMailer/PHPMailerAutoload.php';
		include 'config.php';
		
		$finishedtext = $active_email;

		// ADD $_SERVER['SERVER_PORT'] TO $verifyurl STRING AFTER $_SERVER['SERVER_NAME'] FOR DEV URLS USING PORTS OTHER THAN 80
		// substr() trims "createuser.php" off of the current URL and replaces with verifyuser.php
		// Can pass 1 (verified) or 0 (unverified/blocked) into url for "v" parameter	
		$verifyurl = substr($base_url . $_SERVER['PHP_SELF'],0, -14) . "verifyuser.php?v=1&uid=" . $id;

		// Create a new PHPMailer instance
		// ADD sendmail_path = "env -i /usr/sbin/sendmail -t -i" to php.ini on UNIX servers
		$mail = new PHPMailer(true);
		//Sets mail header so HTML renders
		$mail->isHTML(true);
		//Formatting options
		$mail->CharSet = "text/html; charset=UTF-8;";
		$mail->WordWrap = 80;
		// Set who the message is to be sent from
		$mail->setFrom($from_email, $from_name);
		$mail->AddReplyTo($from_email, $from_name);
		/****
		* Set who the message is to be sent to
		* CAN BE SET TO addAddress(youremail@website.com, 'Your Name') FOR PRIVATE USER APPROVAL BY MODERATOR
		* SET TO addAddress($email, $user) FOR USER SELF-VERIFICATION 
		*****/
		$mail->addAddress($email, $user);
		
	
		//Sets message body content based on type (verification or confirmation)
		if ($type == 'Verify') {
			//Set the subject line
			$mail->Subject = $user . ' Account Verification';
			//Set the body of the message
			$mail->Body = $verifymsg . '<br><a href="'.$verifyurl.'">'.$verifyurl.'</a>';
			$mail->AltBody  =  $verifymsg . $verifyurl;
		}
		elseif ($type == 'Active') {
			//Set the subject line
			$mail->Subject = $site_name . ' Account Created!';
			//Set the body of the message
			$mail->Body = $active_email . '<br><a href="'.$signin_url.'">'.$signin_url.'</a>';
			$mail->AltBody  =  $active_email . $signin_url;

		};
		
		//SMTP Settings
		$mail->SMTPAuth = true; //Set false to disable, true to enable
		$mail->Host = 'smtp.gmail.com'; //SMTP Host
		//Defaults: Non-Encrypted = 25, SSL = 465, TLS = 587
		//$mail->SMTPSecure = 'tls'; // Sets the prefix to the server
		$mail->Port = 25; //SMTP Port 
		//SMTP user auth
		$mail->Username = 'braddmagyar@gmail.com'; //SMTP Username
		$mail->Password = 'sugarw0314'; //SMTP Password
		
		
		try
			{
			   $mail->Send();   
			} 
		catch (phpmailerException $e)
			{ 
			   echo $e->errorMessage();    // Error messages from PHPMailer 
			} 
		catch (Exception $e)
			{ 
			   echo $e->getMessage();      // Something else
			} 

	}
	
};

class newUserForm extends dbConn {

	public function createUser($usr, $uid, $email, $pw) {	

		try {
			
			$db = new dbConn;

			$err = '';
			// prepare sql and bind parameters
			$stmt = $db->conn->prepare("INSERT INTO members (id, username, password, email) 
			VALUES (:id, :username, :password, :email)");
			$stmt->bindParam(':id', $uid);
			$stmt->bindParam(':username', $usr);
			$stmt->bindParam(':email', $email);
			$stmt->bindParam(':password', $pw);
			$stmt->execute();
		} 
		catch (PDOException $e) {
			$err = "Error: " . $e->getMessage();
		}

		//Determines returned value ('true' or error code)
		if ($err == '') {
			$success = 'true'; 
		}
		else {
			$success = $err; 
		};

		return $success;
	}
	
	
};

class verify extends dbConn {
	
	function verifyUser($uid, $verify) {
	
		include 'config.php';
		
		try {
		
		$vdb = new dbConn;
		
		$verr = '';

		// prepare sql and bind parameters
		$vstmt = $vdb->conn->prepare("UPDATE members SET verified = :verify WHERE id = :uid");
		$vstmt->bindParam(':uid', $uid);
		$vstmt->bindParam(':verify', $verify);
		$vstmt->execute();
		
	} catch(PDOException $v) {
		$verr = "Error: " . $v->getMessage();
	}
	
	// Connect to server and select database.

	
	//Determines returned value ('true' or error code)
	if($verr == ''){
		$vresponse = 'true'; }
	else{
		$vresponse = $verr; };
	
	return $vresponse;
	
	}
	
};

function mySqlErrors ($response) {

	//Returns custom error messages instead of MySQL errors
	switch(substr($response, 0, 22)){
		case 'Error: SQLSTATE[23000]':
			echo '<div class=\"alert alert-danger alert-dismissable\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>Username already exists</div>';
			break;
		default: 
			echo '<div class=\"alert alert-danger alert-dismissable\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>An error occurred... try again</div>';

	}
	
}

?>