<?php

namespace accounting;

use Exception;

class Ficus{
    private $admin;
    private $user;
    private $response;
    public function __construct($user){
        $this->user=$user;
        $this->admin = in_array(1,$user->type);
    }

    public function all(): Ficus {
        $database = new FicusDatabase();
        $tables=[];
        try {
            $this->response['date']=date('Y-m-d H:i:s');
            $database->query_ex("SELECT * FROM entries".($this->admin?"":" WHERE user=".$this->user->id));
            $tables['entries'] = $database->get_array();

            $database->query_ex("SELECT * FROM `accounts`");
            $tables['accounts'] = $database->get_array();

            $database->query_ex("SELECT * FROM `subcontos`");
            $tables['subcontos'] = $database->get_array();

            $database->query_ex("SELECT * FROM `subconto_types`");
            $tables['subconto_types'] = $database->get_array();
            $this->response['tables']=$tables;

        } catch (Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
        return $this;
    }

    public function getJson(){
        return json_encode($this->response,JSON_UNESCAPED_UNICODE);
    }

    public function echo(){
        echo json_encode($this->response,JSON_UNESCAPED_UNICODE);
        exit;
    }

}