<?php 

class User extends Model{
	public $id;
	public $email;
	public $password;
	public $name;
	public $admin;
	public $banned;
	public $image;
	public $created;
	public $last_login;
	
	/**
	 * Manager
	 * @param string $backendKey
	 * @param string $modelClassName = __CLASS__
	 */
	public static function objects($backendKey="default", $modelClassName=__CLASS__)
	{
		return parent::objects($backendKey, $modelClassName);
	}
	
	/**
	 * Table
	 * @return string
	 */
	public function table()
	{
		 return "cmless_user"; 
	}

	/**
	 * Structure
	 * @return array
	 */
	public function structure()
	{
		return array(
			'id'=>array('type'=>'pk', 'auto'),
			'email'=>array('type'=>'char', 'length'=>255, 'required'),
			'password'=>array('type'=>'char', 'length'=>255, 'required'),
			'name'=>array('type'=>'char', 'length'=>255, 'required'),
			'admin'=>array('type'=>'bool', 'default'=>'0'),
			'banned'=>array('type'=>'bool', 'default'=>'0'),
			'image'=>array('type'=>'image', 'upload_to'=>'users'),
			'created'=>array('type'=>'datetime', 'now'),
			'last_login'=>array('type'=>'datetime', 'now')
		);
	}

}

?>