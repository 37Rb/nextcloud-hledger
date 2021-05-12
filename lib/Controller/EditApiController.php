<?php

namespace OCA\HLedger\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;
use OCA\HLedger\Configuration;
use OCA\HLedger\HLedger;

class EditApiController extends ApiController
{
    private $config;
    private $hledger;

    public function __construct(
        $AppName,
        IRequest $request,
        $UserId,
        IConfig $config,
        IRootFolder $rootFolder
    ) {
        parent::__construct($AppName, $request);
        $this->config = new Configuration($AppName, $UserId, $config, $rootFolder);
        $this->hledger = new HLedger($this->config);
    }

    /**
     * @NoAdminRequired
     */
    public function addTransaction($settings)
    {
        $file = $this->config->getJournalFile();
        $transaction = $this->request->getParams();
        $transaction['date'] = \DateTime::createFromFormat('Y-m-d', $transaction['date']);
        $this->hledger->addTransaction($file, $transaction);
        return new JSONResponse([]);
    }
}
