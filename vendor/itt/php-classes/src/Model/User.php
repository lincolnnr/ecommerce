<?php 

namespace SON\Model;

use \SON\Model;
use \SON\DB\Sql;

class User extends Model{

    const SESSION = "User";
    const SECRET = "iTTurini_2019";

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
            ":despassword"=>$this->getdespassword(),
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
        var_dump($results[0]);
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

        $results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b WHERE a.desemail = :email;",
        array(
            ":email"=>$email
        ));

        if(count($results) === 0){
            throw new \Exception("Não foi possível recuperar a senha.");
        }
        else{
            $data = $results[0];

            $resultR = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser"=>$data['iduser'], 
                ":desip"=>$_SERVER['REMOTE_ADDR']
            ));

            if(count($resultR) === 0){
                throw new \Exception("Não foi possível recuperar a senha.");
            }
            else{
                $dataRecovery = $resultR[0];

                $code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128,User::SECRET,$dataRecovery['idrecovery'],MCRYPT_MODE_ECB));

                $link = "http://localhost:8000/admn/forgot/reset&code=$code";

                $mailer = new Mailer($data['desmail'],$data['desperson'],'Redefinir senha iTTurini Store', 'forgot', array(
                    "name"=>$data['desperson'],
                    "link"=>$link
                ));

                $mailer->send();

                return $data;
            }
        }
    } 

}