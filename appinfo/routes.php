<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\HLedger\Controller\PageController->index()
 */
return [
    'routes' => [
	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
	   ['name' => 'configuration_api#save_settings', 'url' => '/api/1/settings', 'verb' => 'PUT'],
	   ['name' => 'report_api#balance_sheet', 'url' => '/api/1/balancesheet', 'verb' => 'GET'],
	   ['name' => 'report_api#income_statement', 'url' => '/api/1/incomestatement', 'verb' => 'GET'],
	   ['name' => 'report_api#budget_report', 'url' => '/api/1/budgetreport', 'verb' => 'GET'],
	   ['name' => 'report_api#account_register', 'url' => '/api/1/accountregister', 'verb' => 'GET'],
	   ['name' => 'report_api#custom_report', 'url' => '/api/1/custom', 'verb' => 'GET'],
	   ['name' => 'edit_api#add_transaction', 'url' => '/api/1/transaction', 'verb' => 'POST'],
	   ['name' => 'edit_api#load_ledger_contents', 'url' => '/api/1/loadledgercontents', 'verb' => 'GET'],
	   ['name' => 'edit_api#save_ledger_contents', 'url' => '/api/1/saveledgercontents', 'verb' => 'POST'],
    ]
];
