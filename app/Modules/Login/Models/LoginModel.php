<?php
namespace App\Modules\Login\Models;

use CodeIgniter\Model;

class LoginModel extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'id_user';

    public function validateLogin($arrData)
    {
        $user = ["valid" => false];

        // Limpiar datos (aunque CI4 ya protege bastante)
        $login  = str_replace(["<",">","[","]","*","^","-","'","="],"",$arrData["login"]);
        $passwd = str_replace(["<",">","[","]","*","^","-","'","="],"",$arrData["passwd"]);

        // Encriptar password (igual que CI3)
        $passwd = md5($passwd);

        // Query con Query Builder (seguro)
        $row = $this->where('log_user', $login)
                    ->where('password', $passwd)
                    ->first();

        if ($row) {
            $user["valid"]     = true;
            $user["id"]        = $row['id_user'];
            $user["firstname"] = $row['first_name'];
            $user["lastname"]  = $row['last_name'];
            $user["logUser"]   = $row['log_user'];
            $user["movil"]     = $row['movil'];
            $user["state"]     = $row['state'];
            $user["rol"]       = $row['perfil'];
            $user["bankTime"]  = $row['bank_time'];
            $user["photo"]     = $row['photo'];
        }

        return $user;
    }
}