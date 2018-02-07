<?php

namespace Waynik\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Waynik\Repository\DependencyInjectionInterface;
use Zend\Diactoros\Response\JsonResponse;
use Waynik\Views\Json as JsonView;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;

class Notification implements ControllerInterface
{
	const SECRET_KEY_TO_STOP_STRANGERS = "secret";
	const FCM_AUTH_KEY = "";
	const FCM_URL = 'https://fcm.googleapis.com/fcm/send';

    private $dependencyInjectionContainer;

    public function __construct(DependencyInjectionInterface $dependencyInjector)
    {
        $this->dependencyInjectionContainer = $dependencyInjector;
    }

    /**
     * This route should only be visited by AWS SNS otherwise it will not do anything.
     * @param ServerRequestInterface $request
     * @throws \Exception
     */
    public function handle(ServerRequestInterface $request)
    {
    	$postData = $request->getParsedBody() ?? [];
    	$queryData = $request->getQueryParams() ?? [];
    	$hackToGetJsonBody = json_decode(file_get_contents('php://input'), true) ?? [];

    	$requestData = array_merge($postData, $queryData, $hackToGetJsonBody);

    	$snsMessage = new Message($requestData);

    	// Validate the message
    	$validator = new MessageValidator();
    	if (!$validator->isValid($snsMessage)) {
    		throw new \Exception("Invalid message.", 401);
    	}

    	$snsMessageData = $snsMessage->toArray();
        /**
         * This is for AWS SNS topics
         */
        if (array_key_exists('SubscribeURL', $snsMessageData)) {
        	error_log('subscribing to new topic!');
        	$this->confirmSnsSubscription($snsMessageData['SubscribeURL']);
        	$response = new JsonResponse("successful subscription");
        	$view = new JsonView($response);
        	$view->render();
        	return;
        }

        $payload = $snsMessageData['Message'];
        $payload = json_decode($payload, true);

        if (!array_key_exists('apiKey', $payload) || $payload['apiKey'] !== self::SECRET_KEY_TO_STOP_STRANGERS) {
        	throw new \Exception("please provide an access key.", 401);

        }

        if (!array_key_exists('userId', $payload)) {
        	throw new \Exception("userId is a required parameter.", 400);
        }

        if (!array_key_exists('message', $payload) && !array_key_exists('data', $payload)) {
        	throw new \Exception("message or data are required parameters.", 400);
        }

		$userId = $payload['userId'];
		$message = $payload['message'];
		$data = $payload['data'] ?? [];

        /** @var \Waynik\Models\FirebaseTokenModel $firebaseTokenModel */
        $firebaseTokenModel = $this->dependencyInjectionContainer->make('FirebaseTokenModel');
        $token = $firebaseTokenModel->get($userId);

        if (!$token) {
        	$response = new JsonResponse("no token found for user.");
        	$view = new JsonView($response);
        	$view->render();
        	return;
        }

        if ($message) {
        	$result = $this->sendNormalPushNotification($token['device_registration_token'], $message, $data);
        } else {
        	// since message or data is required, this should never fail.
        	$result = $this->sendSilentPushNotification($token['device_registration_token'], $data);
        }

        $response = new JsonResponse($result);
        $view = new JsonView($response);
        $view->render();
    }

    private function confirmSnsSubscription(string $subscriptionUrl)
    {
    	$ch = curl_init($subscriptionUrl);
    	curl_exec($ch);
    }

    /**
     * @param string $token
     * @param string $message
     * @param array $data
     */
    private function sendNormalPushNotification(string $token, string $message, array $data = [])
    {
    	$payload = ["to" => $token, "priority" => "high"];
    	$payload["notification"] = ["body" => $message];
    	if ($data) {
    		$payload["data"] = $data;
    	}
    	return $this->sendNotification($payload);
    }

    /**
     *
     * @param string $token
     * @param array $data
     * @return unknown
     */
    private function sendSilentPushNotification(string $token, array $data)
    {
    	$payload = ["to" => $token, "priority" => "high"];
    	$payload["data"] = $data;
    	// content_available is for iOs and it makes the app wake up.
    	$payload["content_available"] = true;
    	return $this->sendNotification($payload);
    }

	/**
	 * From Google's website:
     * HTTP POST request

		https://fcm.googleapis.com/fcm/send
		Content-Type:application/json
		Authorization:key=AIzaSyZ-1u...0GBYzPu7Udno5aA

		{ "data": {
		    "score": "5x1",
		    "time": "15:10"
		  },
		  "to" : "bk3RNwTe3H0:CI2k_HHwgIpoDKCIZvvDMExUdFQ3P1..."
		}

	 * @param payload
	 */
	private function sendNotification(array $payload) {
		$payload = json_encode($payload);

    	$ch = curl_init(self::FCM_URL);
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    			'Content-Type: application/json',
    			'Content-Length: ' . strlen($payload),
    			'Authorization:key=' . self::FCM_AUTH_KEY)
    			);

    	$result = curl_exec($ch);
    	return $result;
	}

}
