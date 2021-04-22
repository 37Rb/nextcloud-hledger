<?php
namespace OCA\HLedger\Controller;

// TODO Why isn't this autoloading???
require_once(__DIR__ . '/../../vendor/hledger/php-hledger/lib/HLedger.php');

use OCP\IRequest;
use OCP\IConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\Files\IRootFolder;

use OCA\Viewer\Event\LoadViewer;
use OCP\EventDispatcher\IEventDispatcher;

use HLedger\HLedger;

class PageController extends Controller
{
    private $userId;
    private $config;
    private $rootFolder;
    private $settings;

    /** @var IEventDispatcher */
    private $eventDispatcher;

    public function __construct(
        $AppName,
        IRequest $request,
        $UserId,
        IConfig $config,
        IRootFolder $rootFolder,
        IEventDispatcher $eventDispatcher
    ) {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->config = $config;
        $this->rootFolder = $rootFolder;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index()
    {
        $this->checkForNewSettings();
        $this->loadSettings();

        $parameters = $this->settings;
        $parameters['report'] = $this->getReport();

        $this->eventDispatcher->dispatch(LoadViewer::class, new LoadViewer());

        return new TemplateResponse('hledger', 'app', $parameters);
    }

    private function checkForNewSettings()
    {
        if (isset($_GET['hledger_folder'])) {
            $this->config->setAppValue('hledger', 'hledger_folder', $_GET['hledger_folder']);
        }
        if (isset($_GET['journal_file'])) {
            $this->config->setAppValue('hledger', 'journal_file', $_GET['journal_file']);
        }
        if (isset($_GET['budget_file'])) {
            $this->config->setAppValue('hledger', 'budget_file', $_GET['budget_file']);
        }
    }

    private function loadSettings()
    {
        $this->settings = [
            'hledger_folder' => $this->config->getAppValue('hledger', 'hledger_folder', 'HLedger'),
            'journal_file' => $this->config->getAppValue('hledger', 'journal_file', 'journal.txt'),
            'budget_file' => $this->config->getAppValue('hledger', 'budget_file', 'budget.txt')
        ];
    }

    private function getReport(): array
    {
        $hledger = $this->createHLedger();
        $tab = $_GET['tab'];
        if ($tab == 'balance') {
            return $hledger->balanceSheet([
                ['monthly'],
                ['market'],
                ['begin', 'thisyear'],
                ['end', 'thismonth']
            ]);
        } elseif ($tab == 'income') {
            return $hledger->incomeStatement([
                ['monthly'],
                ['market'],
                ['begin', 'thisyear'],
                ['end', 'nextmonth']
            ]);
        } else {
            $report = $hledger->balance([
                ['monthly'],
                ['market'],
                ['begin', 'lastmonth'],
                ['end', 'nextmonth'],
                ['budget']
            ], [
                'not:desc:opening balances'
            ]);
            array_unshift($report, ["Budget last month and this month","","","",""]);
            return $report;
        }
    }

    private function createHLedger()
    {
        $user_files = $this->config->getSystemValue('datadirectory') . '/' . $this->userId . '/files';
        $hledger_files = $user_files . $this->rootFolder->getFullPath($this->settings['hledger_folder']) . '/';
        $journal = realpath($hledger_files . $this->settings['journal_file']);
        $budget = realpath($hledger_files . $this->settings['budget_file']);
        return new HLedger([
            ['file', $journal],
            ['file', $budget]
        ]);
    }
}
