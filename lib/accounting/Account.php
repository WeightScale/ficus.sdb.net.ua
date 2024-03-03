<?php

namespace accounting;

use Exception;

require_once "ThrowableJson.php";
require_once "Database.php";



class Account{
    private $user;
    private $response;
    private $admin;
    //private $request;
    public function __construct($user){
        $this->user=$user;
        $this->admin = in_array(1,$this->user->type);
    }

    /**
     * @param $request
     * @return $this
     */
    public function update($request): Account {
        //$this->request=$request;
        $this->response=$request;
        $id=(int)$request['id'];
        $database = new FicusDatabase();
        try {
            if(!$id){
                throw new Exception("error id");
            }
            $request['debit_s1']= $request['debit_s1']?(int)$request['debit_s1'] : null;
            $request['debit_s2']= $request['debit_s2']?(int)$request['debit_s2'] : null;

            $request['credit_s1']= $request['credit_s1']?(int)$request['credit_s1'] : null;
            $request['credit_s2']= $request['credit_s2']?(int)$request['credit_s2'] : null;

            $database->transaction();
            $new_id=$database->query_ex("INSERT INTO `entries`(
                      `date`,
                      `debit`,debit_subconto1,debit_subconto2,
                      `credit`,credit_subconto1,credit_subconto2,
                      `sum`,counts,
                      `currency`,
                      `note`,`user`,`parent`
                      ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                $request['date'],
                (int)$request['debit'],
                $request['debit_s1'],
                $request['debit_s2'],
                (int)$request['credit'],
                $request['credit_s1'],
                $request['credit_s2'],
                (float)$request['sum'], isset($request['counts']) ? (int)$request['counts'] : 0,
                $request['currency'],
                $request['note'], $this->user->id,$id);
            $changed= date('Y-m-d H:i:s');
            if($this->admin){
                $database->query_ex("UPDATE entries SET `remove` = ?,`changed`=?, child=? WHERE id = ?",1,$changed,$new_id,(int)$id);
            }else{
                $database->query_ex("UPDATE entries SET `remove` = ?,`changed`=?, child=? WHERE id = ? AND user=?",1,$changed,$new_id,(int)$id,$this->user->id);
            }
            $database->commit();
            $database->query_ex("SELECT * FROM entries WHERE id IN (?, ?)",$new_id,(int)$id);
            $this->response['entries']=$database->get_array();
        } catch (\Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
        return $this;
    }

    public function delete($request): Account{
        $this->response=$request;
        $id=(int)$request['id'];
        $database = new FicusDatabase();
        try {
            if(!$id){
                throw new Exception("error id");
            }
            $database->transaction();
            $changed= date('Y-m-d H:i:s');
            if($this->admin){
                $database->query_ex("UPDATE entries SET `remove` = ?,`changed`=? WHERE id = ?",1,$changed,(int)$id);
            }else{
                $database->query_ex("UPDATE entries SET `remove` = ?,`changed`=? WHERE id = ? AND user=?",1,$changed,(int)$id,$this->user->id);
            }
            $database->commit();
            $database->query_ex("SELECT * FROM entries WHERE id=?",(int)$id);
            $this->response['entries']=$database->get_array();
        } catch (\Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
        return $this;
    }
    public function news($request):Account{
        $date = $request['date'];
        $database = new FicusDatabase();
        try {
            if(!$date){
                throw new Exception("error date");
            }

            $database->transaction();
            if($this->admin){
                $database->query_ex("SELECT * FROM entries WHERE (registered >=? or `changed` >=?)",$date,$date);
            }else{
                $database->query_ex("SELECT * FROM entries WHERE user=? and (registered >=? or `changed` >=?)",$this->user->id,$date,$date);
            }
            $this->response['entries']=$database->get_array();

            $database->query_ex("SELECT * FROM reports WHERE user=? and reports.update >=?",$this->user->id,$date);
            $this->response['reports'] = $database->get_array();

            $database->commit();

            //if(count($this->response['entries'])){
                $this->response['date']=date('Y-m-d H:i:s');
            //}
        } catch (\Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
        return $this;
    }
    public function add($request):Account{
        $this->response=$request;
        $database = new FicusDatabase();
        try {
            $database->transaction();
            $new_id=$database->query_ex("INSERT INTO `entries`(
                      `date`,
                      `debit`,debit_subconto1,debit_subconto2,
                      `credit`,credit_subconto1,credit_subconto2,
                      `sum`,counts,
                      `currency`,
                      `note`,`user`
                      ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                $request['date'],
                (int)$request['debit'],
                $request['debit_s1'] ? (int)$request['debit_s1'] : null,
                $request['debit_s2'] ? (int)$request['debit_s2'] : null,
                (int)$request['credit'],
                $request['credit_s1'] ? (int)$request['credit_s1'] : null,
                $request['credit_s2'] ? (int)$request['credit_s2'] : null,
                (float)$request['sum'], isset($request['counts']) ? (int)$request['counts'] : 0,
                $request['currency'],
                $request['note'], $this->user->id);
            $database->commit();
            $database->query_ex("SELECT * FROM entries WHERE id=?",$new_id);
            $this->response['entries']=$database->get_array();
        } catch (\Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
        return $this;
    }
    /**
     * @return false|string
     */
    public function getJson(){
        return json_encode($this->response,JSON_UNESCAPED_UNICODE);
    }

    public function echo(){
        echo $this->getJson();
        exit;
    }
}

class Entries{
    private $user;
    private $response;
    public function __construct($user){
        $this->user=$user;
        $database = new FicusDatabase();
        try {
            $admin = in_array(1,$this->user->type);
            $where=$admin?"":" WHERE user=".$this->user->id;
            $database->query_ex("SELECT * FROM entries".$where);
            $this->response['entries']=$database->get_array();
        } catch (\Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
    }

    /**
     * @return mixed
     */
    public function getResponse(){
        return $this->response;
    }

    public function getJson(){
        return json_encode($this->response,JSON_UNESCAPED_UNICODE);
    }
}

class AccountData{
    private $admin;
    private $response;
    private $event="";
    public function __construct($user){
        $this->admin = in_array(1,$user->type);
    }

    public function all(): AccountData {
        $database = new FicusDatabase();
        try {
            $database->query_ex("SELECT * FROM `accounts`");
            $this->response['accounts'] = $database->get_array();

            $database->query_ex("SELECT * FROM `subcontos`");
            $this->response['subcontos'] = $database->get_array();

            $database->query_ex("SELECT * FROM `subconto_types`");
            $this->response['subconto_types'] = $database->get_array();

            $database->query_ex("SELECT * FROM `reports`");
            $this->response['reports'] = $database->get_array();
        } catch (Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
        return $this;
    }

    /** Add new account
     * @param $request //data for adding
     * @return $this
     */
    public function newAccount($request): AccountData{
        $this->response=$request;
        $database = new FicusDatabase();
        try {
            if(!$this->admin){
                throw new Exception("No access");
            }
            $this->response['a-number']= isset($this->response['a-number'])?(int)$this->response['a-number']:null;
            $database->transaction();
            $database->query_ex("SELECT * FROM `accounts` WHERE number=?",$this->response['a-number']);
            if($database->next()){
                $database->query_ex("UPDATE accounts SET `name` = ?, remove=0 WHERE number = ?",$this->response['a-name'],$this->response['a-number']);
            }else{
                $database->query_ex("INSERT INTO accounts (`number`, `name`) VALUES (?,?)",$this->response['a-number'],$this->response['a-name']);
            }
            $database->query_ex("SELECT * FROM `accounts`");
            $this->response['accounts'] = $database->get_array();
            $database->commit();
        } catch (Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }

        return $this;
    }

    public function deleteAccount($request){
        $this->response=$request;
        $database = new FicusDatabase();
        try {
            if(!$this->admin){
                throw new Exception("No access");
            }
            $this->response['account']= isset($this->response['account'])?(int)$this->response['account']:null;
            $database->transaction();
            //$database->query_ex("SELECT id FROM `entries` WHERE `remove`=0 and debit=? or credit=?",$this->response['account'],$this->response['account']);
            //if($database->next()){
                $database->query_ex("UPDATE accounts SET `remove` = 1 WHERE id = ?",$this->response['account']);
                //throw new Exception("Нельзя удалить если есть проводки");
            //}//else{
                //$database->query_ex("SET FOREIGN_KEY_CHECKS=0");
                //$database->query_ex("DELETE FROM accounts WHERE `id`=?",$this->response['account']);
                //$database->query_ex("SET FOREIGN_KEY_CHECKS=1");
            //}
            $database->query_ex("SELECT * FROM `accounts` WHERE id=?",$this->response['account']);
            $this->response['accounts'] = $database->get_array();
            $this->response['index']=$this->response['account'];
            $database->commit();
        } catch (Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
        return $this;
    }

    public function updateAccount($request): AccountData {
        $this->response=$request;
        $database = new FicusDatabase();
        try {
            if(!$this->admin){
                throw new Exception("No access");
            }
            $this->response['account']= isset($this->response['account'])?(int)$this->response['account']:null;
            $database->transaction();
            $database->query_ex("SELECT id FROM `entries` WHERE `remove`=0 and debit=? or credit=?",$this->response['account'],$this->response['account']);
            if($database->next()){
                throw new Exception("Нельзя обновлять если есть проводки");
            }else{
                $s1=$this->response['s1']?(int)$this->response['s1']:null;
                $s2=$this->response['s2']?(int)$this->response['s2']:null;
                $database->query_ex("UPDATE accounts SET `subconto_type1` = ?, subconto_type2=? WHERE id = ?",$s1,$s2,$this->response['account']);
            }
            $database->query_ex("SELECT * FROM `accounts` WHERE id=?",$this->response['account']);
            $this->response['accounts'] = $database->get_array();
            $database->commit();
        } catch (Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
        return $this;
    }

    public function getAdmin(){
        return $this->admin;
    }

    public function type(){
        $this->event='type';
        return $this;
    }

    public function subconto(){
        $this->event='subconto';
        return $this;
    }

    private function newTypeSubconto(){
        $this->response=$_GET;
        $database = new FicusDatabase();
        try {
            if(!$this->admin){
                throw new Exception("No access");
            }
            //$new_id=null;
            $database->transaction();

            $database->query_ex("SELECT * FROM `subconto_types` WHERE name=?",$this->response['name']);
            if($database->next()){
                $values=$database->get_object();
                if($values->remove){
                    $database->query_ex("UPDATE subconto_types SET `remove` = ? WHERE id = ?",0,$values->id);
                    $new_id=$values->id;
                }else{
                    throw new Exception("Название ".$this->response['name']." уже есть");
                }
            }else{
                $new_id = $database->query_ex("INSERT INTO subconto_types (`name`) VALUES (?)",$this->response['name']);
            }
            $database->query_ex("SELECT * FROM `subconto_types` WHERE id=?",$new_id);
            $this->response['index']=$new_id;
            $this->response['types'] = $database->get_array();
            $database->commit();
        } catch (Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
    }

    private function updateTypeSubconto(){
        $this->response=$_GET;
        $database = new FicusDatabase();
        try {
            if(!$this->getAdmin()){
                throw new Exception("No access");
            }
            $this->response['index']= isset($this->response['index'])?(int)$this->response['index']:null;
            $database->transaction();
            /*$database->query_ex("SELECT * FROM `subconto_types` WHERE name=?",$this->response['name']);
            if($database->next()){
                throw new Exception("Название ".$this->response['name']." уже есть");
            }*/
            $database->query_ex("UPDATE subconto_types SET `name` = ? WHERE id = ?",$this->response['name'],$this->response['index']);
            $database->query_ex("SELECT * FROM `subconto_types` WHERE id=?",$this->response['index']);
            $this->response['types'] = $database->get_array();
            $database->commit();
        } catch (Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
    }

    private function deleteTypeSubconto(){
        $this->response=$_GET;
        $database = new FicusDatabase();
        try {
            if(!$this->getAdmin()){
                throw new Exception("No access");
            }
            $this->response['index']= isset($this->response['index'])?(int)$this->response['index']:null;
            $database->transaction();
            $database->query_ex("UPDATE subconto_types SET `remove` = ? WHERE id = ?",1,$this->response['index'],);
            $database->query_ex("SELECT * FROM `subconto_types`");
            $this->response['types'] = $database->get_array();
            $database->commit();
        } catch (Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
    }

    private function newSubconto(){
        $this->response=$_GET;
        $database = new FicusDatabase();
        try {
            if(!$this->admin){
                throw new Exception("No access");
            }
            $this->response['st-id']= isset($this->response['st-id'])?(int)$this->response['st-id']:null;
            $database->transaction();
            $database->query_ex("SELECT * FROM `subcontos` WHERE subconto_type=? and name=?",$this->response['st-id'],$this->response['name']);
            if($database->next()){
                throw new Exception("Название ".$this->response['name']." уже есть");
            }
            $new_id = $database->query_ex("INSERT INTO subcontos (`name`,subconto_type) VALUES (?,?)",$this->response['name'],$this->response['st-id']);

            $database->query_ex("SELECT * FROM `subcontos` WHERE id=?",$new_id);
            $this->response['subcontos'] = $database->get_array();
            $database->commit();
        } catch (Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
    }

    private function updateSubconto(){
        $this->response=$_GET;
        $database = new FicusDatabase();
        try {
            if(!$this->getAdmin()){
                throw new Exception("No access");
            }
            $this->response['index']= isset($this->response['index'])?(int)$this->response['index']:null;
            $this->response['st-id']= isset($this->response['st-id'])?(int)$this->response['st-id']:null;
            $database->transaction();
            $database->query_ex("SELECT * FROM `subcontos` WHERE name=? and subconto_type=? and id-?",$this->response['name'],$this->response['st-id'],$this->response['index']);
            if($database->next()){
                throw new Exception("Название ".$this->response['name']." уже есть");
            }
            $database->query_ex("UPDATE subcontos SET `name` = ?, subconto_type=? WHERE id = ?",$this->response['name'],$this->response['st-id'],$this->response['index']);
            $database->query_ex("SELECT * FROM `subcontos` WHERE id=?",$this->response['index']);
            $this->response['subcontos'] = $database->get_array();
            $database->commit();
        } catch (Exception $e) {
            $this->response["error"]=(new ThrowableJson($e))->httpCode(400)->getArray();
        } finally {
            $database->close();
        }
    }

    public function update(): AccountData{
        switch ($this->event){
            case 'type':
                $this->updateTypeSubconto();
                break;
            case 'subconto':
                $this->updateSubconto();
                break;
        }
        unset($this->event);
        return $this;
    }

    public function delete(): AccountData{
        switch ($this->event){
            case 'type':
                $this->deleteTypeSubconto();
                break;
        }
        unset($this->event);
        return $this;
    }

    public function new(): AccountData{
        switch ($this->event){
            case 'type':
                $this->newTypeSubconto();
                break;
            case 'subconto':
                $this->newSubconto();
                break;
        }
        unset($this->event);
        return $this;
    }

    public function getJson(){
        return json_encode($this->response,JSON_UNESCAPED_UNICODE);
    }

    public function echo(){
        echo $this->getJson();
        exit;
    }
}