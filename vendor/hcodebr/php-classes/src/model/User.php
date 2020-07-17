<?php
	
	namespace Hcode\Model;

	use \Hcode\DB\Sql;
	use \Hcode\Model;
	use \Hcode\Mailer;

	class User extends Model {

		const SESSION = "User";
		const SECRET = "HcodePhp7_Secret";
		const SECRET_IV = "HcodePhp7_Secret_IV";
		const ERROR = "UserError";
		const ERROR_REGISTER = "UserErrorRegister";
		const SUCCESS = "UserSucesss";

		public static function getFromSession(){

			$user = new User();

			if(isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0){

				$user->setData($_SESSION[User::SESSION]);
	
			}

			return $user;
		}

		public static function checkLogin($inadmin = true){

			if (
				!isset($_SESSION[User::SESSION])
				||
				!$_SESSION[User::SESSION]
				||
				!(int)$_SESSION[User::SESSION]["iduser"] > 0) {
			//Não está logado
			return false;

			} 
			else {

				if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {

					return true;

				} else if ($inadmin === false) {

					return true;

				} else {

					return false;

				}

			}

		}

		public static function login($login, $password){

			$sql = new Sql();

			$result = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(
			":LOGIN"=>$login
			)); 

			if (count($result) === 0){
				throw new \Exception("Usuário inexistente ou senha inválida.");
			}

			$data = $result[0];

			//essa porra só da false


			if (password_verify($password, $data["despassword"]) === true){

				$user = new User();

				$data['desperson'] = utf8_encode($data['desperson']);

				$user->setData($data);

				$_SESSION[User::SESSION] = $user->getValues();

				return $user;

			} 
			else {
				throw new \Exception("Usuário inexistente ou senha inválida.");
			}

		}

		public static function verifyLogin($inadmin = true){
			
			if (!User::checkLogin($inadmin)) {

				if ($inadmin) {
					header("Location: /admin/login");
				} else {
					header("Location: /login");
				}
				exit;

			}

		}

		public static function logout(){
			$_SESSION[User::SESSION] = NULL;
		}

		public static function listAll(){

			$sql = new Sql();

			return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

		}

		public function save(){

			$sql = new Sql();

			$result = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
				":desperson"=>utf8_decode($this->getdesperson()),
				":deslogin"=>$this->getdeslogin(),
				":despassword"=>User::getPasswordHash($this->getdespassword()),
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()
			));

			$this->setData($result[0]);

		}

		public function getById($iduser){

			$sql = new Sql();

			$result = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
				":iduser"=>$iduser
			));

			$data = $result[0];

			$data['desperson'] = utf8_encode($data['desperson']);

			$this->setData($data);

		}

		public function update(){

			$sql = new Sql();

			$result = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
				":iduser"=>$this->getiduser(),
				":desperson"=>utf8_decode($this->getdesperson()),
				":deslogin"=>$this->getdeslogin(),
				":despassword"=>User::getPasswordHash($this->getdespassword()),
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()
			));

			$this->setData($result[0]);	
		}	

		public function delete(){

			$sql = new Sql();
 
       		 $sql->query("CALL sp_users_delete(:iduser)", array(
       		 	":iduser"=>$this->getiduser()
			));

		}

		public static function getForgot($email, $inadmin = true){

			$sql = new Sql();

			$result = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING (idperson) WHERE a.desemail = :email;", array(
					":email"=>$email
				));

			if(count($result) ===0){
				throw new \Exception("Estorou aqui.");
				
			}
			else{

				$data = $result[0];

				$result2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
					":iduser"=>$data['iduser'],
					":desip"=>$_SERVER['REMOTE_ADDR']
				));

				if(count($result2)===0){
					throw new \Exception("Erro ao recuperar a senha.");
				}
				else{

					$dataRecovery = $result2[0];

					$code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

					$code = base64_encode($code);


					if ($inadmin === true) {

						$link = "https://www.alwayshigh.com.br/admin/forgot/reset?code=$code";

					} else {

						$link = "https://www.alwayshigh.com.br/forgot/reset?code=$code";
						
					}				

					$mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir Senha", "forgot",
					array(
						"name"=>$data["desperson"],
						"link"=>$link
					));

					$mailer->send();

					return $link;

				}

			}

		}


		public static function validForgotDecrypt($code){	

			$code = base64_decode($code);

			$idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

			$sql = new Sql();

			$result = $sql->select("
				SELECT * from tb_userspasswordsrecoveries a
				INNER JOIN tb_users b USING(iduser)
				INNER JOIN tb_persons c USING(idperson)
				WHERE a.idrecovery = :idrecovery AND a.dtrecovery is NULL AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()
				", array(
					":idrecovery"=>$idrecovery
			));

			if(count($result) === 0){

				throw new \Exception("Erro ao gerar recovery key");

			}
			else {

				return $result[0];
			}
		}

		public static function setForgotUsed($idrecovery){

			$sql = new Sql();

			$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery= :idrecovery", array(
				":idrecovery"=>$idrecovery
			));

		}

		public function setPassword($password){

			$sql = new Sql();	

			$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser ", array(
				":password"=>$password,
				":iduser"=>$this->getiduser()
			));

		}

		public static function getPasswordHash($password)	{

			return password_hash($password, PASSWORD_DEFAULT, [
			'cost'=>12
		]);
			
		}

		public static function setError($msg){

			$_SESSION[User::ERROR] = $msg;

		}

		public static function getError(){

			$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

			User::clearError();

			return $msg;

		}

		public static function clearError(){

			$_SESSION[User::ERROR] = NULL;

		}

		public static function setErrorRegister($msg){

			$_SESSION[User::ERROR_REGISTER] = $msg;

		}

		public static function clearErrorRegister(){

			$_SESSION[User::ERROR_REGISTER] = NULL;

		}

		public static function getErrorRegister(){

			$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

			User::clearErrorRegister();

			return $msg;

		}

		public static function checkLoginExists($login){

			$sql= new Sql();

			$result = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
				':deslogin'=>$login
			]);

			return(count($result) > 0);

		}


	}

?>