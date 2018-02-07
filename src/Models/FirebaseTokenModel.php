<?php

namespace Waynik\Models;

use Waynik\Repository\DataConnectionInterface;

class FirebaseTokenModel
{
    private $storageHelper;

    private $table = "firebase_tokens";

    const USER_ID = 'user_id';
    const TOKEN = 'device_registration_token';
    const CREATED_AT = 'created_at';
    
    private $fields = [
        self::USER_ID,
        self::TOKEN,
    	self::CREATED_AT
    ];

    public function __construct(DataConnectionInterface $dataConnection)
    {
        $this->storageHelper = $dataConnection;
    }

    public function create(array $providedFields)
    {
    	$this->validate($providedFields);
    	
    	$providedFields = $this->filter($providedFields);
    	
    	$params = [];
    	$fieldsString = "";
    	$questionMarks = "";
    	$comma = "";
    	
    	foreach ($this->fields as $field) {
    		if (array_key_exists($field, $providedFields)) {
    			$params[] = $providedFields[$field];
    			$fieldsString .= $comma . "`" . $field . "`";
    			$questionMarks .= $comma . "?";
    			$comma = ",";
    		}
    	}
    	
    	$sql = "INSERT INTO `" . $this->table . "` (" . $fieldsString . ") VALUES (" . $questionMarks . ")";
    	
    	return $this->storageHelper->create($sql, $params);
    	
    }

    private function validate(array $fields)
    {
    	if (!array_key_exists(self::USER_ID, $fields) || !$fields[self::USER_ID]) {
    		throw new \Exception(self::USER_ID . " is required.");
    	}
    	if (!array_key_exists(self::TOKEN, $fields) || !$fields[self::TOKEN]) {
            throw new \Exception(self::TOKEN . " is required.");
        }
    }

    private function filter(array $fields)
    {
        return $fields;
    }
    
    private function convertToInt($value): int {
    	$lowerCaseValue = strtolower($value);
    	if (
    		$lowerCaseValue === "false"
    		|| $lowerCaseValue === "off"
    		|| $lowerCaseValue === "no"
    	) {
    		return 0;
    	}
    	return ((bool) $value) ? 1: 0;
    }
    
    /**
     * return most recent token for the user.
     * @param int $userId
     * @return array
     */
    public function get(int $userId)
    {
    	$sql = "select * from " . $this->table . " where " . self::USER_ID . " = ? ORDER BY " . self::CREATED_AT . " desc LIMIT 1;";
    	$params = [$userId];
    
    	$tokens = $this->storageHelper->query($sql, $params);
    	return array_shift($tokens);
    }

}