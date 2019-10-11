<?php 

namespace SON\Model;

use \SON\Model;
use \SON\DB\Sql;
use \SON\Mailer;


class User extends Model{

    const SESSION = "User";
    const SECRET = "iTTurini_2019";
    const SECRET_IV = "iTTurini_2019";

	// protected $fields = [
	// 	"iduser", "idperson", "deslogin", "despassword", "inadmin", "dtergister"
    // ];
    
    public static function login($login, $password):User
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ":LOGIN"=>$login
        ));

        if(count($results) === 0){
            throw new \Exception("Não foi possível fazer login.");

        }

        $data = $results[0];

        if(password_verify($password, $data["despassword"])) {
            
            $user = new User();
			$user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();
            
            return $user;

        }else{
			throw new \Exception("Não foi possível fazer login.");
        }
    }

	public static function verifyLogin($inadmin = true)
	{

		if (
			!isset($_SESSION[User::SESSION])
			|| 
            !$_SESSION[User::SESSION]
            ||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
			||
            (bool)$_SESSION[User::SESSION]["iduser"] !== $inadmin
			
		) {
            header("Location: /admin/login");
			exit;
		}
    }

    public static function logout()
	{
		$_SESSION[User::SESSION] = NULL;
    }
    
    public static function listAll()
	{
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }

    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :nrphone, :desemail, :despassword, :inadmin)", 
        array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":nrphone"=>$this->getnrphone(),
            ":desemail"=>$this->getdesemail(),
            ":despassword"=>password_hash($this->getdespassword(), PASSWORD_DEFAULT, [
                "cost"=>12
            ]),
            ":inadmin"=>$this->getinadmin()
        ));
        $this->setData($results[0]);

        var_dump($results[0]);
    }

    public function get($iduser)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
            ":iduser"=>$iduser
        ));
         
        $this->setData($results[0]);
    }

    public function update()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser,:desperson, :deslogin, :nrphone, :desemail, :despassword, :inadmin)", 
        array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":nrphone"=>$this->getnrphone(),
            ":desemail"=>$this->getdesemail(),
            ":despassword"=>$this->getdespassword(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            "iduser"=>$this->getiduser()
        ));
    }

    public static function getForgot($email)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT *
                        FROM tb_persons a
                            INNER JOIN tb_users b USING(idperson)
                        WHERE a.desemail = :email;",
        array(
			":email"=>$email
        ));

        var_dump($results);

        if(count($results) === 0){
            throw new \Exception("Não foi possível recuperar a senha.");
        }
        else{
            $data = $results[0];
			
            $resultr = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser"=>$data['iduser'], 
                ":desip"=>$_SERVER['REMOTE_ADDR']
            ));

            if(count($resultr) === 0)
            {
                throw new \Exception("Não foi possível recuperar a senha.");
            }
            else{
                
                $dataRecovery = $resultr[0];
				
                $code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));
				$code = base64_encode($code);

                $link = "/admn/forgot/reset&code=$code";

                $mailer = new Mailer($data['desmail'],$data['desperson'],'Redefinir senha iTTurini Store', 'forgot', array(
                    "name"=>$data['desperson'],
                    "link"=>$link
                ));

                $mailer->send();

                return $data;
            }
        }
    } 

    public static function validForgotDecrypt($cod)
    {
        $code = base64_decode($code);
		$idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));
		$sql = new Sql(); 
		$results = $sql->select("
			SELECT *
			FROM tb_userspasswordsrecoveries a
			INNER JOIN tb_users b USING(iduser)
			INNER JOIN tb_persons c USING(idperson)
			WHERE
				a.idrecovery = :idrecovery
				AND
				a.dtrecovery IS NULL
				AND
				DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
		", array(
			":idrecovery"=>$idrecovery
		));
		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{
			return $results[0];
		}
    }

    public static function getPasswordHash($password)
	{
		return password_hash($password, PASSWORD_DEFAULT, [
			'cost'=>12
		]);
	}

    public static function setForgotUsed($idrecovery)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_userspasswordrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", 
        array(
            "idrecovery"=>idrecovery
        ));
    }

    public function setPassword($password)
    {
        $sql = new Sql();
        
        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", 
        array(
            ":password"=>$password,
            ":iduser"=>$this->getiduser()
        ));
    }
}