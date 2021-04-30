<?php
namespace OCA\HLedger\Controller;

// TODO Why isn't this autoloading???
require_once(__DIR__ . '/../../vendor/hledger/php-hledger/lib/HLedger.php');

use OCP\IRequest;
use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCA\HLedger\Configuration;
use OCA\HLedger\HLedger;

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

    public function budgetReport()
    {
        return new DataResponse($this->hledger->budgetReport());
    }

    public function incomeStatement()
    {
        return new DataResponse($this->hledger->incomeStatement());
    }

    public function balanceSheet()
    {
        return new DataResponse($this->hledger->balanceSheet());
    }
}
