<?php

namespace OCA\HLedger\AppInfo;

use OCP\Util;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\Notification\IManager;

class Application extends App implements IBootstrap
{
    public function __construct()
    {
        parent::__construct('hledger');
    }

    public function register(IRegistrationContext $context): void
    {
    }

    public function boot(IBootContext $context): void
    {
        Util::addStyle('hledger', 'style');
        Util::addScript('hledger', 'script');
    }
}
