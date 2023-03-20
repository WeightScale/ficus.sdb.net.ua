<?php

namespace accounting;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
use Exception;

require_once "Database.php";

const INTERVAL_DAY = 24 * 60 * 60;
const INTERVAL_MONTH = 30 * 24 * 60 * 60;
const INTERVAL_YEAR = 12 * 30 * 24 * 60 * 60;

class Auth{
    private $auth=false;
    private $msg="";
    public function __construct($post){
        if (isset($_COOKIE['id']) and isset($_COOKIE['hash'])){
            try {
                if(isset($_SESSION['user_data'])){
                    $userdata = $_SESSION['user_data'];
                    if(($userdata->user_hash === $_COOKIE['hash']) and ($userdata->id === intval($_COOKIE['id']))){
                        $this->auth=true;
                    } else {
                        setcookie("id", "", time() - INTERVAL_YEAR, $_SERVER['PHP_SELF']);
                        setcookie("hash", "", time() - INTERVAL_YEAR, $_SERVER['PHP_SELF'], null, null, true);
                    }
                }
            } catch (Exception $e) {
                $this->msg=$e;
            }
        }
        if (isset($post))
            $this->post($post);
    }

    public function getResult(): array {
        return ["auth"=>$this->auth,"msg"=>$this->msg];
    }

    /**
     * @return bool
     */
    public function isAuth(): bool{
        return $this->auth;
    }

    /**
     * @return Exception|string
     */
    public function getMsg():string{
        return $this->msg;
    }

    private function post($post){
        if(isset($post['out'])){
            setcookie("id", "", time() - INTERVAL_YEAR, $_SERVER['PHP_SELF']);
            setcookie("hash", "", time() - INTERVAL_YEAR, $_SERVER['PHP_SELF'],null,null,true);
            unset($_SESSION["user"]);
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }

        if(isset($post['submit'])){
            $database =new FicusDatabase();
            try {
                $database->query_ex("SELECT * FROM `users` WHERE `email` = ? AND `double_hash` = ?", $post["email"], sha1(sha1($post["password"])));
                if ($database->next()) {
                    $data = $database->get_object();
                    $data->type=json_decode($data->type);
                    $hash = md5(Auth::generateCode(10));
                    $database->query_ex("UPDATE users SET user_hash=? WHERE id=?", $hash, $data->id);
                    $NumsRowsAffected = mysqli_affected_rows($database->mysqli);
                    if($NumsRowsAffected){
                        setcookie("id", $data->id, time()+INTERVAL_DAY, $_SERVER['PHP_SELF']);
                        setcookie("hash", $hash, time()+INTERVAL_DAY, $_SERVER['PHP_SELF'], null, null, true);
                        $data->user_hash=$hash;
                        $_SESSION['user_data']=$data;
                    }
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit;
                }else{
                    $this->msg= "Вы ввели неправильный логин или пароль";
                }
            } catch (Exception $e) {
                $this->msg=$e;
            } finally{
                $database->close();
            }
        }
    }

    public static function generateCode($length=6){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHI JKLMNOPRQSTUVWXYZ0123456789";
        $code = "";
        $clen = strlen($chars) - 1;
        while (strlen($code) < $length) {
                $code .= $chars[mt_rand(0,$clen)];
        }
        return $code;
    }
}
