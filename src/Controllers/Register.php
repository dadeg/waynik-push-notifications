<?php

namespace Waynik\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Waynik\Repository\DependencyInjectionInterface;
use Zend\Diactoros\Response\JsonResponse;
use Waynik\Views\Json as JsonView;
use Waynik\Models\FirebaseTokenModel;

class Register implements ControllerInterface
{
    private $dependencyInjectionContainer;

    public function __construct(DependencyInjectionInterface $dependencyInjector)
    {
        $this->dependencyInjectionContainer = $dependencyInjector;
    }

    public function handle(ServerRequestInterface $request)
    {
        $postData = $request->getParsedBody();
        $queryData = $request->getQueryParams();
        $requestData = array_merge($postData, $queryData);    
        
		$securityToken = $requestData['security_token'];
		$email = $requestData['email'];
		$deviceToken = $requestData['device_token'];
		
		/** @var \Waynik\Models\UserModel $userModel */
		$userModel = $this->dependencyInjectionContainer->make('UserModel');
		$userId = $userModel->getIdByEmailAndToken($email, $securityToken);
		
        /** @var \Waynik\Models\FirebaseTokenModel $firebaseTokenModel */
        $firebaseTokenModel = $this->dependencyInjectionContainer->make('FirebaseTokenModel');
        $firebaseTokenModel->create([FirebaseTokenModel::USER_ID => $userId, FirebaseTokenModel::TOKEN => $deviceToken]);

        $message = "success";
        
        $response = new JsonResponse($message);
        $view = new JsonView($response);
        $view->render();
    }
    
    
}