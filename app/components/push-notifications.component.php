<?php
    class PushNotificationsComponent {

      public static function send($params)
      {
          $lean = Lean::getInstance();
          $notificationConfig = $lean->getConfig('push-notification');
          $apiKey = $notificationConfig['apiToken'];

          $client = new paragraph1\phpFCM\Client;
          $client->setApiKey($apiKey);
          $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false,),));
          $client->injectHttpClient($guzzleClient);

          $notification = [
              'extraParams' => [
                  'title' => $params['title'],
                  'message' => $params['message'],
                  'action' => $params['action'],
                  'type' => $params['type'],
                  'token' => $params['token']
              ]
          ];
          if (isset($params['extraParams'])) {
              $notification['extraParams'] = array_merge($notification['extraParams'], $params['extraParams']);
          }

          $messageFCM = new paragraph1\phpFCM\Message();
          $messageFCM->addRecipient(new paragraph1\phpFCM\Recipient\Device($notification['extraParams']['token']));
          $messageFCM->setData($notification['extraParams']);
          $response = $client->send($messageFCM);
          $params['success'] = $response->getStatusCode() == 200 ? true : false;
          return $params;

      }

    }
?>
