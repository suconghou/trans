<?php


class trans
{

	private static $headers =
	[
		'User-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1'
	];

	private const key = '71C35ABEBD38D433050C0D472F46F216';

	private const tCache = '`kvdb`';
	private const db = 'trans.db';

	static function index()
	{
		try {
			$word = strtolower(trim(strip_tags($_GET['w'] ?? '')));
			if (preg_match('/^[a-zA-Z]{2,30}$/', $word)) {
				return self::json(self::fetch($word));
			}
			return self::json(['code' => -100, 'msg' => '参数有误']);
		} catch (Throwable $e) {
			return self::json(['code' => $e->getCode() ?: -200, 'msg' => $e->getMessage()]);
		}
	}

	private static function db(): SQLite3
	{
		static $db;
		if (!$db) {
			$db = new SQLite3(self::db);
			foreach (['PRAGMA JOURNAL_MODE=MEMORY', 'PRAGMA LOCKING_MODE=EXCLUSIVE', 'PRAGMA SYNCHRONOUS=OFF', 'PRAGMA CACHE_SIZE=8192', 'PRAGMA PAGE_SIZE=4096', 'PRAGMA TEMP_STORE=MEMORY', 'CREATE TABLE IF NOT EXISTS ' . self::tCache . ' ("k" text NOT NULL, "v" text NOT NULL, "t" integer NOT NULL, PRIMARY KEY ("k") )'] as $sql) {
				$db->exec($sql);
			}
		}
		return $db;
	}

	private static function get(string $key, $default = null)
	{
		$value = self::db()->querySingle('SELECT `v` FROM ' . self::tCache . " WHERE `k`='{$key}' and `t` > (SELECT strftime('%s', 'now')) ");
		return $value ? json_decode($value, true, 32, JSON_THROW_ON_ERROR) : $default;
	}

	private static function set(string $key, $value, int $expired = 86400000)
	{
		$db = self::db();
		$stm = $db->prepare('REPLACE INTO ' . self::tCache . " (`k`,`v`,`t`) VALUES (:key,:value,(SELECT strftime('%s', 'now')+{$expired}) )");
		$stm->bindValue(':key', $key);
		$stm->bindValue(':value', (is_string($value) || is_int($value) || is_float($value)) ? $value : json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		return $stm->execute();
	}

	private static function fetch(string $word)
	{
		$data = self::get($word);
		if ($data) {
			return $data;
		}
		$url = "http://dict-co.iciba.com/api/dictionary.php?w=" . $word . "&key=" . self::key . "&type=json";
		[$content, $code] = self::http($url);
		if (!$content || $code !== 200) {
			throw new LogicException("invalid response $code", -50);
		}
		$data = json_decode($content, true, 32, JSON_THROW_ON_ERROR);
		if (!$data) {
			throw new LogicException("invalid decode response", -51);
		}
		self::set($word, $content);
		return $data;
	}

	private static function http(string $uri, int $timeout = 8, array|string|null $data = null, array $head = []): array
	{
		$ch = curl_init($uri);
		curl_setopt_array($ch, [CURLOPT_HTTPHEADER => array_merge(static::$headers, $head), CURLOPT_FOLLOWLOCATION => 1, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => $timeout] + (is_null($data) ? [] : [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $data]));
		$content = curl_exec($ch);
		$http_code = 0;
		if ($content !== false) {
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		}
		curl_close($ch);
		return [$content, $http_code];
	}

	private static function json(array|string $data)
	{
		$expire = time() + 8640000;
		header('Expires: ' . gmdate('D, d M Y H:i:s', $expire) . ' GMT');
		header('Cache-Control: public, max-age=8640000');
		header('Access-Control-Max-Age: 86400', true);
		header("Access-Control-Allow-Origin: *", true);
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS', true);
		header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept', true);
		header('Content-Type: application/json', true);
		exit(is_string($data) ? $data : json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
}

trans::index();
