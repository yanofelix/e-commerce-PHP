<?php
	
	namespace Hcode\Model;
	use \Hcode\DB\Sql;
	use \Hcode\Model;

	class User extends Model {
		CONST SESSION = "User";

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

				var_dump($user);
				exit;

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


	}

?>