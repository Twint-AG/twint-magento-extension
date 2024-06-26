<?php
use Magento\Framework\App\Bootstrap;

require 'app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml'); // or 'adminhtml' depending on your context

$repo = $objectManager->get('Twint\Magento\Api\PairingRepositoryInterface');
$ser = $objectManager->get('Twint\Magento\Service\PairingService');
$log = $objectManager->get('Magento\Framework\Logger\Monolog');

$cronJob = $objectManager->create('Twint\Magento\Cron\UpdatePairingStatus', ['repository' => $repo, 'pairingService' => $ser, 'logger' => $log]);
$cronJob->execute();
