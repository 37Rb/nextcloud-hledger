<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\HLedger\Controller\PageController->index()
 */
return [
    'routes' => [
	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
	   ['name' => 'report_api#balance_sheet', 'url' => '/api/1/balancesheet', 'verb' => 'GET'],
	   ['name' => 'report_api#income_statement', 'url' => '/api/1/incomestatement', 'verb' => 'GET'],
	   ['name' => 'report_api#budget_report', 'url' => '/api/1/budgetreport', 'verb' => 'GET'],
    ]
];
