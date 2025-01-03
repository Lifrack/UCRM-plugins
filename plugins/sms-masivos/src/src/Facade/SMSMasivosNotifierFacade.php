<?php

declare(strict_types=1);

namespace SmsNotifier\Facade;

use SmsNotifier\Data\NotificationData;
use SmsNotifier\Data\PluginData;
use SmsNotifier\Factory\MessageTextFactory;
use SmsNotifier\Service\Logger;
use SmsNotifier\Service\OptionsManager;
use SmsNotifier\Service\SmsNumberProvider;
use SmsNotifier\Service\CurlExecutor; 
use SmsNotifier\Exception\CurlException;

class SMSMasivosNotifierFacade extends AbstractMessageNotifierFacade
{
    /**
     * @var PluginData
     */
    private $pluginData;

    public function __construct(
        Logger $logger,
        MessageTextFactory $messageTextFactory,
        SmsNumberProvider $smsNumberProvider,
        OptionsManager $optionsManager
    ) {
        parent::__construct($logger, $messageTextFactory, $smsNumberProvider);
        // load config data
        $this->pluginData = $optionsManager->load();
    }

    /*
     * Send message through the SMS MASIVOS Client
     */
    protected function sendMessage(
        NotificationData $notificationData,
        string $clientSmsNumber,
        string $messageBody
    ): void {
        $this->logger->debug(sprintf('Sending: %s', $messageBody));
    
        $params = [
            'message' => $messageBody,
            'numbers' => $clientSmsNumber,
            'country_code' => $this->pluginData->smsMasivosCountryCode  // Country code
        ];
    
        $headers = [
            'apikey: ' . $this->pluginData->smsMasivosApiKey
        ];
    
        // Use CurlExecutor::Class for send Request
        $curlExecutor = new CurlExecutor();
        try {
            $response = $curlExecutor->curlCommand(
                'https://api.smsmasivos.com.mx/sms/send',
                'POST',
                $headers,
                http_build_query($params)
            );
    
            $this->logger->debug($response);
            $this->logger->info(sprintf('SMSMasivos response: %s', $response));
    
            $responseData = json_decode($response, true);
            if (isset($responseData['errorCode'])) {
                $this->logger->warning(sprintf('SMSMasivos error: %s', $responseData['errorCode']));
            }
        } catch (CurlException $e) {
            $this->logger->error('Curl error: ' . $e->getMessage());
        }
    }
    
}
