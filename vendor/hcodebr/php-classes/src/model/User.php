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


	}

?>