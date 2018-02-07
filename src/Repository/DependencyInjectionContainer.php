<?php

namespace Waynik\Repository;

use Waynik\Models\FirebaseTokenModel;
use Waynik\Models\UserModel;

class DependencyInjectionContainer implements DependencyInjectionInterface
{
    private $mysqlConnectionInstance;

    private function getFirebaseTokenModel()
    {
        $dataConnection = $this->make('MysqlConnection');
        return new FirebaseTokenModel($dataConnection);
    }

    private function getUserModel()
    {
        $dataConnection = $this->make('MysqlConnection');
        return new UserModel($dataConnection);
    }

    private function getMysqlConnection()
    {
        if (!$this->mysqlConnectionInstance) {
            $this->mysqlConnectionInstance = new MysqlConnection();
        }
        return $this->mysqlConnectionInstance;
    }

    public function make($className)
    {
        switch ($className) {
            case 'FirebaseTokenModel':
                return $this->getFirebaseTokenModel();
                break;
            case 'UserModel':
                return $this->getUserModel();
                break;
            
            case 'MysqlConnection':
                return $this->getMysqlConnection();
                break;
            
            default:
                throw new \Exception('There is no class strategy for class: ' . $className);
                break;
        }

    }
}
