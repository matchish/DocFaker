<?php
abstract class MODxAPI extends APIhelpers{
	protected $modx = null;
	protected $log = array();
	protected $field = array();
	protected $default_field=array();
    protected $id = null;
    protected $set = array();
    protected $newDoc = false;

	public function __construct($modx){
		try{
			if($modx instanceof DocumentParser){
				$this->modx = $modx;
			} else throw new Exception('MODX should be instance of DocumentParser');
		}catch(Exception $e){ die($e->getMessage()); }
	}
	
	final protected function query($SQL){
		return $this->modx->db->query($SQL);
	}
	
	final protected function invokeEvent($name,$data=array(),$flag=false){
		$flag = (isset($flag) && $flag!='') ? (bool)$flag : false;
		if($flag){
			$this->modx->invokeEvent($name,$data);
		}
		return $this;
	}
	
	final public function clearLog(){
		$this->log = array();
		return $this;
	}
	final public function list_log($flush = false){
		echo '<pre>'.print_r($this->log,true).'</pre>';
		if($flush) $this->clearLog();
		return $this;
	}
	
	final public function clearCache($fire_events = null){
		$this->modx->clearCache();

		include_once ($this->modx->getManagerPath().'/processors/cache_sync.class.processor.php');
		$sync = new synccache();
		$sync->setCachepath($this->modx->getCachePath());
		$sync->setReport(false);
		$sync->emptyCache();

		$this->invokeEvent('OnSiteRefresh',array(),$fire_events);
	}

	public function set($key,$value){
		if(is_scalar($value) && is_scalar($key) && !empty($key)){
			$this->field[$key] = $value;
		}
		return $this;
	}

    final public function getID(){
        return $this->id;
    }
	public function get($key){
		return isset($this->field[$key]) ? $this->field[$key] : null;
	}
	
	public function fromArray($data){
		if(is_array($data)){
			foreach($data as $key=>$value){
				$this->set($key,$value);
			}
		}
		return $this;
	}
	
	final protected function Uset($key,$id=''){
        $tmp = '';
		if(!isset($this->field[$key])){ 
			$tmp = "{$key}=''";
			$this->log[] =  '{$key} is empty';
		} else {
			try{
				if(is_scalar($this->field[$key])){
					$tmp= "{$key}='{$this->field[$key]}'";
				} else throw new Exception("{$key} is not scalar <pre>".print_r($this->field[$key],true)."</pre>");
			}catch(Exception $e){ die($e->getMessage()); }
		}
        if(!empty($tmp)){
            if($id==''){
                $this->set[] = $tmp;
            }else{
                $this->set[$id][] = $tmp;
            }
        }
		return $this;
	}


	final protected function cleanIDs($IDs,$sep=',',$ignore = array()) {
        $out=array();
        if(!is_array($IDs)){
			try{
				if(is_scalar($IDs)){
					$IDs=explode($sep, $IDs);
				} else {
					$IDs = array();
					throw new Exception('Invalid IDs list <pre>'.print_r($IDs,1).'</pre>');
				}
			} catch(Exception $e){ die($e->getMessage()); }
        }
        foreach($IDs as $item){
            $item = trim($item);
            if(is_int($item) && (int)$item>=0){ //Fix 0xfffffffff
				if(!empty($ignore) && in_array((int)$item, $ignore, true)){
					$this->log[] =  'Ignore id '.(int)$item;
				}else{
					$out[]=(int)$item;
				}
            }
        }
        $out = array_unique($out);
		return $out;
	}
	
	final public function fromJson($data,$callback=null){
		try{
			if(is_scalar($data) && !empty($data)){
				$json = json_decode($data);
			}else throw new Exception("json is not string with json data");
			if ($this->jsonError($json)) { 
				if(isset($callback) && is_callable($callback)){
					call_user_func_array($callback,array($json));
				}else{
					if(isset($callback)) throw new Exception("Can't call callback JSON unpack <pre>".print_r($callback,1)."</pre>");
					foreach($json as $key=>$val){
						$this->set($key,$val);
					}
				}
			} else throw new Exception('Error from JSON decode: <pre>'.print_r($data,1).'</pre>');
		}catch(Exception $e){ die($e->getMessage()); }
		return $this;
	}
	
	final public function toJson($callback=null){
		try{
			$data = $this->toArray();
			$json = json_encode($data);
			if(!$this->jsonError($data,$json)) {
				$json = false;
				throw new Exception('Error from JSON decode: <pre>'.print_r($data,1).'</pre>');
			}
		}catch(Exception $e){ die($e->getMessage()); }
		return $json;
	}
	
	final protected function jsonError($data){
		$flag = false;
		if(!function_exists('json_last_error')){
			function json_last_error(){
				return JSON_ERROR_NONE;
			}
		}
		if(json_last_error() === JSON_ERROR_NONE && is_object($data) && $data instanceof stdClass){
			$flag = true;
		}
		return $flag;
	}
	
	public function toArray(){
		return $this->field;
	}
	
	final protected function makeTable($table){
		return (isset($this->_table[$table])) ? $this->_table[$table] : $this->modx->getFullTableName($table);
	}
	
	final protected function sanitarIn($data,$sep=','){
		if(!is_array($data)){
			$data=explode($sep,$data);
		}
		$out=array();
		foreach($data as $item){
			$out[]=$this->modx->db->escape($item);
		}
		$out="'".implode("','",$out)."'";
		return $out;
	}
	protected function checkUnique($table,$field,$PK='id'){
        $val = $this->get($field);
        if($val!=''){
            $sql = $this->query("SELECT ".$this->modx->db->escape($PK)." FROM ".$this->makeTable($table)." WHERE ".$this->modx->db->escape($field)."='".$this->modx->db->escape($val)."'");
            $id = $this->modx->db->getValue($sql);
            if(is_null($id) || (!$this->newDoc && $id==$this->getID())){
                $flag = true;
            }else{
                $flag = false;
            }
        }else{
            $flag = false;
        }
        return $flag;
    }
	public function create($data=array()){
        $this->close();
        $this->fromArray($data);
        return $this;
    }

    public function copy($id){
        $this->edit($id)->id=0;
        $this->newDoc = true;
        return $this;
    }
    public function close(){
        $this->newDoc = true;
        $this->id = null;
        $this->field=array();
        $this->set=array();
    }

    abstract public function edit($id);

	abstract public function save($fire_events = null,$clearCache = false);
	abstract public function delete($ids,$fire_events = null);

    final protected function sanitarTag($data){
        return parent::sanitarTag($this->modx->stripTags($data));
    }
}


class APIhelpers{
    /**
     * Email validate
     *
     * @category   validate
     * @version 	0.1
     * @license 	GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
     * @param string $email проверяемый email
     * @param boolean $dns проверять ли DNS записи
     * @return boolean Результат проверки почтового ящика
     * @author Anton Shevchuk
     */
    protected function emailValidate($email,$dns=true){
        $flag=false;
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            list($user, $domain) = explode("@", $email, 2);
            if (!$dns || ($dns && checkdnsrr($domain,"MX") && checkdnsrr($domain,"A"))) {
                $flag=$email;
            } else {
                die('Email has invalid domain name');
            }
        } else {
            die('Email is invalid');
        }
        return $flag;
    }

    /**
     * Password generate
     *
     * @category   generate
     * @version   0.1
     * @license 	GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
     * @param string $len длина пароля
     * @param string $data правила генерации пароля
     * @return string Строка с паролем
     * @author Agel_Nash <Agel_Nash@xaker.ru>
     *
     * Расшифровка значений $data
     * "A": A-Z буквы
     * "a": a-z буквы
     * "0": цифры
     * ".": все печатные символы
     *
     * @example
     * $this->genPass(10,"Aa"); //nwlTVzFdIt
     * $this->genPass(8,"0"); //71813728
     * $this->genPass(11,"A"); //VOLRTMEFAEV
     * $this->genPass(5,"a0"); //4hqi7
     * $this->genPass(5,"."); //2_Vt}
     * $this->genPass(20,"."); //AMV,>&?J)v55,(^g}Z06
     * $this->genPass(20,"aaa0aaa.A"); //rtvKja5xb0\KpdiRR1if
     */
    protected function genPass($len,$data=''){
        if($data==''){
            $data='Aa0.';
        }
        $opt=strlen($data);
        $pass=array();

        for($i=$len;$i>0;$i--){
            switch($data[rand(0,($opt-1))]){
                case 'A':{
                    $tmp=rand(65,90);
                    break;
                }
                case 'a':{
                    $tmp=rand(97,122);
                    break;
                }
                case '0':{
                    $tmp=rand(48,57);
                    break;
                }
                default:{
                $tmp=rand(33,126);
                }
            }
            $pass[]=chr($tmp);
        }
        $pass=implode("",$pass);
        return $pass;
    }

    /**
     * User IP
     *
     * @category   validate
     * @version   0.1
     * @license 	GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
     * @param string $out IP адрес который будет отдан функцией, если больше ничего не обнаружено
     * @return string IP пользователя
     * @author Agel_Nash <Agel_Nash@xaker.ru>
     *
     * @see http://stackoverflow.com/questions/5036443/php-how-to-block-proxies-from-my-site
     */
    protected function getUserIP($out='127.0.0.1'){
        //see: http://www.php.net/manual/ru/functions.anonymous.php
        $getEnv = function($data){
            $out=false;
            switch(true){
                case (isset($_SERVER[$data])):
                    $out = $_SERVER[$data]; break;
                case (isset($_ENV[$data])):
                    $out = $_ENV[$data]; break;
                case ($tmp = getenv($data)):
                    $out = $tmp; break;
                case (function_exists('apache_getenv') && $tmp=apache_getenv($data, true)):
                    $out = $tmp; break;
                default:
                    $out = false;
            }
            unset($tmp);
            return $out;
        };

        //Порядок условий зависит от приоритетов
        switch(true){
            case ($tmp = $getEnv('HTTP_COMING_FROM')):
                $out = $tmp; break;
            case ($tmp = $getEnv('HTTP_X_COMING_FROM')):
                $out = $tmp; break;
            case ($tmp = $getEnv('HTTP_VIA')):
                $out = $tmp; break;
            case ($tmp = $getEnv('HTTP_FORWARDED')):
                $out = $tmp; break;
            case ($tmp = $getEnv('HTTP_FORWARDED_FOR')):
                $out = $tmp; break;
            case ($tmp = $getEnv('HTTP_X_FORWARDED')):
                $out = $tmp; break;
            case ($tmp = $getEnv('HTTP_X_FORWARDED_FOR')):
                $out = $tmp; break;
            case (!empty($_SERVER['REMOTE_ADDR'])):
                $out=$_SERVER['REMOTE_ADDR']; break;
            default:
                $out = false;
        }
        unset($tmp);

        return (false!==$out && preg_match('|^(?:[0-9]{1,3}\.){3,3}[0-9]{1,3}$|',$out, $matches)) ? $out : false;
    }

    protected function sanitarTag($data){
        $data = htmlspecialchars($data);
        $data=str_replace(array('[', ']', '{', '}'), array('&#91;', '&#93;', '&#123;', '&#125;'),$data);
        return $data;
    }
}