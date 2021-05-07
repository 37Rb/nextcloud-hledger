<?php

namespace OCA\HLedger\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCA\HLedger\Configuration;

class ConfigurationApiController extends ApiController
{
    private $config;

    public function __construct(
        $AppName,
        IRequest $request,
        $UserId,
        IConfig $config,
        IRootFolder $rootFolder
    ) {
        parent::__construct($AppName, $request);
        $this->config = new Configuration($AppName, $UserId, $config, $rootFolder);
    }

    public function saveSettings($settings)
    {
        $this->config->saveSettings($this->request->getParams());
        return new DataResponse('');
    }
}
