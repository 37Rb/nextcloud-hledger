<?php

namespace OCA\HLedger\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataResponse;
use OCA\HLedger\Configuration;
use OCA\HLedger\HLedger;
use OC\ForbiddenException;

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

    /**
     * @NoAdminRequired
     */
    public function loadLedgerContents($settings)
    {
        $params = $this->request->getParams();
        $file = $this->config->getJournalFile($params['fileName']);
        if ($file)
        {
            return new DataResponse($this->hledger->getLedgerContents($file));
        }
        else
        {
            throw new ForbiddenException("Not Allowed!");
        }
    }

    /**
     * @NoAdminRequired
     */
    public function saveledgerContents($settings)
    {
        $params = $this->request->getParams();
        $file = $this->config->getJournalFile($params['fileName']);
        if ($file)
        {
            $this->hledger->putLedgerContents($file, $params['contents']);
            return new DataResponse();
        }
        else
        {
            throw new ForbiddenException("Not Allowed!");
        }
    }
}
