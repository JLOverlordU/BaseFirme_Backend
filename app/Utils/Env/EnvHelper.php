<?php
namespace App\Utils\Env;

class EnvHelper{
	static public function get(String $param){
		return array_key_exists($param, $_SERVER) ? $_SERVER[$param] : env($param);
	}
}
