<?php

namespace OCA\HLedger\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCA\HLedger\Configuration;
use OCA\HLedger\HLedger;
use OC\ForbiddenException;

class ReportApiController extends ApiController
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
    public function budgetReport()
    {
        return new DataResponse($this->hledger->budgetReport());
    }

    /**
     * @NoAdminRequired
     */
    public function incomeStatement()
    {
        return new DataResponse($this->hledger->incomeStatement());
    }

    /**
     * @NoAdminRequired
     */
    public function balanceSheet()
    {
        return new DataResponse($this->hledger->balanceSheet());
    }

    /**
     * @NoAdminRequired
     */
    public function accountRegister()
    {
        $account = $this->request->getParam('account');
        return new DataResponse($this->hledger->accountRegister($account));
    }

    /**
     * @NoAdminRequired
     */
    public function customReport()
    {
        $reportName = $this->request->getParam('reportName');
        $includeDefaultFiles = $this->request->getParam('includeDefaultFiles');
        $response = $this->hledger->customReport($reportName, $includeDefaultFiles);

        /* customReport will return null if this custom report does not exist */
        if ($response !== null)
        {
            return new DataResponse($response);
        }
        else
        {
            throw new ForbiddenException("Not Allowed!");
        }
    }
}
