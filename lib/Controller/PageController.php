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
        $this->check_for_new_settings();
        $this->load_settings();
        $this->show_report();

        $this->eventDispatcher->dispatch(LoadViewer::class, new LoadViewer());

        return new TemplateResponse('hledger', 'index', $this->settings);
    }

    private function check_for_new_settings()
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

    private function load_settings()
    {
        $this->settings = [
            'hledger_folder' => $this->config->getAppValue('hledger', 'hledger_folder', 'HLedger'),
            'journal_file' => $this->config->getAppValue('hledger', 'journal_file', 'journal.txt'),
            'budget_file' => $this->config->getAppValue('hledger', 'budget_file', 'budget.txt')
        ];
    }

    private function show_report()
    {
        $hledger = $this->createHLedger();
        $tab = $_GET['tab'];
        if ($tab == 'balance') {
            $this->show_csv_report($hledger->balanceSheet([
                ['monthly'],
                ['market'],
                ['begin', 'thisyear'],
                ['end', 'thismonth']
            ]));
        } elseif ($tab == 'income') {
            $this->show_csv_report($hledger->incomeStatement([
                ['monthly'],
                ['market'],
                ['begin', 'thisyear'],
                ['end', 'nextmonth']
            ]));
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
            $this->show_csv_report($report);
        }
    }

    private function show_csv_report($report)
    {
        $this->log('<table class="hledger-data">');
        foreach ($report as $row) {
            $outline = in_array(trim($row[0]), ['Account', 'Total:']) ? 'outline' : '';

            if ($row[0] == 'Account' || $this->is_account_name($row[0])) {
                $row[0] = "&nbsp;&nbsp;&nbsp;&nbsp;" . $row[0];
            }

            $data = '';
            for ($i = 1; $i < count($row); $i++) {
                $data .= '<td class="' . $outline . '">' . $row[$i] . '</td>';
            }

            $this->log('<tr><td class="' . $outline . '">' . $row[0] . '</td>' . $data . '</tr>');
        }
        $this->log('</table>');
    }

    private function is_account_name($s)
    {
        $top_level_accounts = [
            'assets',
            'liabilities',
            'equity',
            'income',
            'expenses'
        ];
        foreach ($top_level_accounts as $account) {
            if (str_starts_with($s, $account . ':')) {
                return true;
            }
        }
        return false;
    }

    private function log($s)
    {
        global $hledgerlog;
        $hledgerlog .= "\n" . $s;
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
