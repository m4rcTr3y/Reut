<?php
declare(strict_types=1);
namespace Reut\Models;
use Reut\DB\DataBase;
use Reut\DB\Types\Varchar;
use Reut\DB\Types\Integer;



class AccountsTable extends DataBase {
    public function __construct($config) {
        parent::__construct($config, [],'accounts',true,0,['all']);

      //add columns
       $this->addColumn('id',new Integer(false,true,true));
       $this->addColumn('name',new Varchar(255,false));
    }

    



    public function saveUserAccount($params){
      //todo : generate user password from admin
        $usersid = uniqid();
        $password = password_hash($params['password'], PASSWORD_DEFAULT);
        $this->connect();



        $sql = "INSERT INTO `accounts` (`userID`,`email`,`password`) VALUES (?,?,?)";
        $stmt=$this->pdo->prepare($sql);

        if($stmt->execute([$usersid,$params['email'],$password]))
          return ['msg'=>'yes']; 



      }
   

 


        public function allusers(){
        $this->connect();
        $sql="SELECT CONCAT(st.fname,' ', st.lname) AS 'name',
        st.email,st.gender, st.photo, st.staffID,acc.username,(SELECT rls.name FROM roles as rls where rls.id = rl.role) as roles,
        acc.userID  FROM staff st LEFT JOIN accounts acc ON acc.staffID = st.staffID
        LEFT JOIN useroles rl ON rl.`userID` = acc.`userID` WHERE st.`staffID` != '' GROUP BY rl.id";

        $stmt= $this->pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if($data){

          $myarr = [];
          foreach($data as $item){
            $key = $item['userID'];
            if(!isset($myarr[$key])){
              $myarr[$key] = $item;
              $myarr[$key]['roles'] = [$item['roles']];

            }else{
              $myarr[$key]['roles'][] = $item['roles'];
            }
          }
            return $myarr ;
        }else{

          return [];
        }

      }
    

 
      public function removeAdminAccount($id){
        $this->connect();
        $sql="DELETE FROM `accounts` WHERE userID =?";
        $stmt = $this->pdo->prepare($sql);
        if($stmt->execute([$id]))
            return ['msg'=>'done'];
            else
            return ['msg'=>'failed'];

      }

   
      public function getAdminUser(String $id){
        $this->connect();

        $sql= "SELECT username,userID,email FROM accounts WHERE userID = ?";
       
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if($data){
          return $data[0];
        }
        else{
          return [];
        }

      }





}