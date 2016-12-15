<?php


class trans
{

	const key='71C35ABEBD38D433050C0D472F46F216';

	const tCache='`kvdb`';
	const db='trans.db';
	private static $db;

	static function index()
	{
		$word=isset($_GET['w'])?strtolower(trim(strip_tags($_GET['w']))):null;
		if(preg_match('/^[a-zA-Z]{2,30}$/',$word))
		{
			$data=self::fetch($word);
			return self::json($data);
		}
		else
		{
			return self::json(['code'=>-100,'msg'=>'error param']);
		}
	}

	private static function db()
	{
		if(!self::$db)
		{
			self::$db=new SQLite3(self::db);
			self::$db->exec('PRAGMA SYNCHRONOUS=OFF');
			self::$db->exec('PRAGMA CACHE_SIZE =8000');
			self::$db->exec('PRAGMA TEMP_STORE = MEMORY');
			self::$db->exec('CREATE TABLE IF NOT EXISTS '.self::tCache.' ("k" text NOT NULL, "v" text NOT NULL, "t" integer NOT NULL, PRIMARY KEY ("k") )');
		}
		return self::$db;
	}

	private static function get($key,$default=null)
	{
		$value=self::db()->querySingle('SELECT v FROM '.self::tCache." WHERE k='{$key}' and t > (SELECT strftime('%s', 'now')) ");
		return $value?json_decode($value,true):$default;
	}

	private static function set($key,$value,$expired=8640000)
	{
		$value=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
		return self::db()->exec('REPLACE INTO '.self::tCache." (k,v,t) VALUES ('{$key}','{$value}',(SELECT strftime('%s', 'now')+{$expired}) )");
	}


	private static function fetch($word)
	{
		if($data=self::get($word))
		{
			$hit='sys-hit-num';
			self::set($hit,self::get($hit,0)+1);
			return $data;
		}
		else
		{
			$url="http://dict-co.iciba.com/api/dictionary.php?w=".$word."&key=".self::key."&type=json";
			$data=json_decode(file_get_contents($url));
			if($data)
			{
				$miss='sys-miss-num';
				self::set($word,$data);
				self::set($miss,self::get($miss,0)+1);
				return $data;
			}
			else
			{
				return self::json(['code'=>-99,'msg'=>'fetch error']);
			}
		}
	}

	private static function json($data,$json=false)
	{
		$expire=time()+8640000;
		header('Expires: '.gmdate('D, d M Y H:i:s',$expire).' GMT');
		header('Cache-Control: public, max-age=8640000');
		header('Access-Control-Max-Age:3600',true);
		header("Access-Control-Allow-Origin:*",true);
		header('Access-Control-Allow-Methods:GET, POST, PUT, DELETE, OPTIONS',true);
		header('Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept',true);
		exit($json?$data:json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
	}

}

trans::index();
