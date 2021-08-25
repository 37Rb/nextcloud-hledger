<?php

namespace OCA\HLedger\Controller;

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
        $this->config->createMissingFiles();
        $parameters = ['settings' => $this->config->getSettings()];

        $hledger = new HLedger($this->config);
        $parameters['navigation'] = [ 'reports' => [ ] ];

        $customReports = $hledger->getCustomReportList();
        if (array_key_exists('Balance Sheet', $customReports))
        {
            $parameters['navigation']['reports'][] = ['name' => 'custom', 'title' => 'Balance Sheet (custom)', 'icon' => 'icon-edit',
                                                            'includesFiles' => $customReports['Balance Sheet']['includesFiles'],
                                                            'customName' => $customReports['Balance Sheet']['fileName']];
        }
        else
        {
            $parameters['navigation']['reports'][] = ['name' => 'balancesheet', 'title' => 'Balance Sheet', 'icon' => 'icon-edit'];
        }
        if (array_key_exists('Income Statement', $customReports))
        {
            $parameters['navigation']['reports'][] = ['name' => 'custom', 'title' => 'Income Statement (custom)', 'icon' => 'icon-clippy',
                                                            'includesFiles' => $customReports['Income Statement']['includesFiles'],
                                                            'customName' => $customReports['Income Statement']['fileName']];
        }
        else
        {
            $parameters['navigation']['reports'][] = ['name' => 'incomestatement', 'title' => 'Income Statement', 'icon' => 'icon-clippy'];
        }
        if (array_key_exists('Budget', $customReports))
        {
            $parameters['navigation']['reports'][] = ['name' => 'custom', 'title' => 'Budget (custom)', 'icon' => 'icon-toggle-filelist',
                                                            'includesFiles' => $customReports['Budget']['includesFiles'],
                                                            'customName' => $customReports['Budget']['fileName']];
        }
        else
        {
            $parameters['navigation']['reports'][] = ['name' => 'budgetreport', 'title' => 'Budget', 'icon' => 'icon-toggle-filelist'];
        }

        $parameters['navigation']['reports'] = array_merge( $parameters['navigation']['reports'],
                array_map( function ($report) { return [ 'name' => 'custom', 'customName' => $report['fileName'],
                        'title' => 'Custom Report: ' . $report['name'], 'includesFiles' => $report['includesFiles'], 'icon' => 'icon-triangle-e']; },
                        array_values( array_filter( $customReports,
                        function($report) { return !in_array($report['name'], ['Balance Sheet', 'Income Statement', 'Budget']); } ) ) ) );

        $parameters['report'] = [
            'name' => 'balancesheet',
            'data' => $hledger->balanceSheet(),
            'options' => []
        ];
        $parameters['editor'] = [
            'availableledgers' => $this->config->getListOfJournalFiles(),
            'selectedledger' => $this->config->getJournalFile()->getName()
        ];
        $parameters['accounts'] = $hledger->accounts();

        $this->initialState->provideInitialState($this->appName, 'state', $parameters);

        $this->eventDispatcher->dispatch(LoadViewer::class, new LoadViewer());

        return new TemplateResponse($this->appName, 'index');
    }
}
