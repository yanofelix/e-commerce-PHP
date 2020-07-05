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

		public static function login($login, $password){

			$sql = new Sql();

			$result = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
				":LOGIN"=>$login
			));

			if (count($result) === 0) {
				throw new \Exception ("Usuario invalido/Senha invalida", 1);
			}

			$data = $result[0];

			if (password_verify($password, $data['despassword']) === true){
				$user = new User();

				$user->setData($data);

				$_SESSION[User::SESSION] = $user->getValues();

				return $user;

			}
			else{
				throw new \Exception ("Usuario invalido/Senha invalida", 1);
			}

		}

		public static function verifyLogin($inadmin = true){
			if(
				!isset($_SESSION[User::SESSION])
				||
				!$_SESSION[User::SESSION]
				||
				!(int)$_SESSION[User::SESSION]['iduser'] > 0
				||
				(bool)$_SESSION[User::SESSION]['inadmin'] !== $inadmin
			){

				header("Location: /admin/login");
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

			$result = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
				array(
					":desperson"=>$this->getdesperson(),
					":deslogin"=>$this->getdeslogin(),
					":despassword"=>$this->getdespassword(),
					":desemail"=>$this->getdesemail(),
					":nrphone"=>$this->getnrphone(),
					":inadmin"=>$this->getinadmin()
				));
			$this->setData($result[0]);

		}

		public function getById($iduser){

			$sql = new Sql();

			$result = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING (idperson) WHERE a.iduser = :iduser", array(
				":iduser"=>$iduser
			));

			$this->setData($result[0]);

		}

		public function update(){

			$sql = new Sql();

			$result = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
				array(
					":iduser"=>$this->getiduser(),
					":desperson"=>$this->getdesperson(),
					":deslogin"=>$this->getdeslogin(),
					":despassword"=>$this->getdespassword(),
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

		public static function getPasswordHash($password)
		{

			return password_hash($password, PASSWORD_DEFAULT, [
				'cost'=>12
			]);

		}


	}

?>