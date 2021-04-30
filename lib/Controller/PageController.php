<?php
namespace OCA\HLedger\Controller;

// TODO Why isn't this autoloading???
require_once(__DIR__ . '/../../vendor/hledger/php-hledger/lib/HLedger.php');

use OCP\IRequest;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\Files\IRootFolder;
use OCP\EventDispatcher\IEventDispatcher;
use OCA\Viewer\Event\LoadViewer;
use OCA\HLedger\Configuration;
use OCA\HLedger\HLedger;

class PageController extends Controller
{
    private $config;
    private $initialState;
    private $eventDispatcher;

    public function __construct(
        $AppName,
        IRequest $request,
        $UserId,
        IConfig $config,
        IRootFolder $rootFolder,
        IEventDispatcher $eventDispatcher,
        IInitialStateService $initialState
    ) {
        parent::__construct($AppName, $request);
        $this->config = new Configuration($AppName, $UserId, $config, $rootFolder);
        $this->eventDispatcher = $eventDispatcher;
        $this->initialState = $initialState;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index()
    {
        $this->checkForNewSettings();
        $parameters = $this->config->getSettings();

        $hledger = new HLedger($this->config);
        $parameters['report'] = $hledger->budgetReport();

        $this->initialState->provideInitialState($this->appName, 'state', $parameters);

        $this->eventDispatcher->dispatch(LoadViewer::class, new LoadViewer());

        return new TemplateResponse($this->appName, 'app', $parameters);
    }

    private function checkForNewSettings()
    {
        if (isset($_GET['hledger_folder'])) {
            $this->config->setSetting('hledger_folder', $_GET['hledger_folder']);
        }
        if (isset($_GET['journal_file'])) {
            $this->config->setSetting('journal_file', $_GET['journal_file']);
        }
        if (isset($_GET['budget_file'])) {
            $this->config->setSetting('budget_file', $_GET['budget_file']);
        }
    }
}
