<?php

namespace Waynik\Models;

use Waynik\Repository\DataConnectionInterface;

class UserModel
{
	private $storageHelper;

	private $table = "users";

	const USER_ID = 'user_id';
	const TOKEN = 'apiToken';
	const EMAIL = 'email';

	private $fields = [
			self::USER_ID,
			self::TOKEN,
			self::EMAIL
	];

	public function __construct(DataConnectionInterface $dataConnection)
	{
		$this->storageHelper = $dataConnection;
	}

	/**
	 * return user ID for a given email and token.
	 * @param string $email
	 * @param string $token
	 * @return int
	 */
	public function getIdByEmailAndToken(string $email, string $token): int
	{
		$sql = "select u.id from users u INNER JOIN user_custom_fields c ON c.user_id = u.id AND c.attribute = 'apiToken' AND c.value = ? where u.email = ? LIMIT 1;";
		$params = [$token, $email];

		$users = $this->storageHelper->query($sql, $params);
		$user = array_shift($users);
		$id = $user['id'];
		
		if (!$id) {
			throw new \Exception("user not found.");
		}
		
		return $id;
	}

}