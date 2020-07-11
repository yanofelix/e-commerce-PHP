<?php
	
	namespace Hcode\Model;

	use \Hcode\DB\Sql;
	use \Hcode\Model;
	use \Hcode\Mailer;
	use \Hcode\Model\User;

	class Cart extends Model {

		const SESSION = "Cart";

		public static function getSession(){

			$cart = new Cart();

			if(isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0){

				$cart->get((int)$_SESSION[Cart::SESSION]['idcart']);

			}

			else{

				$cart->getSessionId();

				if(!(int)$cart->getidcart() > 0){

					$data = [
						'dessessionid'=>session_id()
					];

					if(User::checkLogin(false)){

						$user = User::getFromSession();

						$data['iduser'] = $user->getiduser();

					}

					$cart->setData($data);

					$cart->save();

					$cart->setSession();
					
				}

			}

		return $cart;

		}


		public function setSession(){

			$_SESSION[Cart::SESSION] = $this->getValues();

		}


		public function getSessionId(){

			$sql = new Sql();

			$result = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
				':dessessionid'=>session_id()
			]);

			if(count($result) > 0){
				$this->setData($result[0]);
			}

		}

		public function get(int $idcart){

			$sql = new Sql();

			$result = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
				':idcart'=>$idcart
			]);

			$this->setData($resul[0]);

		}

		public function save(){

			$sql = new Sql();

			$result = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)",[
				'idcart'=>$this->getidcart(),
				'dessessionid'=>$this->getdessessionid(),
				'iduser'=>$this->getiduser(),
				'deszipcode'=>$this->getdeszipcode(),
				'vlfreight'=>$this->getvlfreight(),
				'nrdays'=>$this->getnrdays()
			]);

			$this->setData($result[0]);

		}

	}

?>