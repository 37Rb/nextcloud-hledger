<?php
namespace OCA\HLedger\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class PageController extends Controller {
	private $userId;
	private $config;

	public function __construct($AppName, IRequest $request, $userId, IConfig $config){
		parent::__construct($AppName, $request);
		$this->userId = $userId;
		$this->config = $config;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {

		$tab = $_GET['tab'];
		if ($tab == 'balance') {
			$this->show_csv_report($this->hledger('bs -MV -b thisyear -e thismonth -O csv'));
		} else if ($tab == 'income') {
			$this->show_csv_report($this->hledger('is -MV -b thisyear -e thismonth -O csv'));
		} else {
			$header = '"Budget last month and this month","","","",""' . "\n";
			$this->show_csv_report($header . $this->hledger('bal -MV -p lastmonth -e nextmonth --budget "not:desc:opening balances" -O csv'));
		}

		$parameters = [
			'hledger_folder' => $this->config->getAppValue('hledger', 'hledger_folder', 'HLedger')
		];

		return new TemplateResponse('hledger', 'index', $parameters);
	}

	function show_csv_report($report) {
		$this->log('<table class="hledger-data">');

		foreach(preg_split("/((\r?\n)|(\r\n?))/", $report) as $line) {
			$line = trim($line);
			if (!$line)
				continue;

			$line = str_replace('\\"\\"', '""', $line); // Work around weird HL output
			$row = str_getcsv($line);

			$outline = in_array(trim($row[0]), ['Account', 'Total:']) ? 'outline' : '';

			if ($row[0] == 'Account' || $this->is_account_name($row[0]))
				$row[0] = "&nbsp;&nbsp;&nbsp;&nbsp;" . $row[0];

			$data = '';
			for ($i = 1; $i < count($row); $i++) {
				$data .= '<td class="' . $outline . '">' . $row[$i] . '</td>';
			}

			$this->log('<tr><td class="' . $outline . '">' . $row[0] . '</td>' . $data . '</tr>');
		}
		$this->log('</table>');
	}

	private function is_account_name($s) {
		$top_level_accounts = [
			'assets',
			'liabilities',
			'equity',
			'income',
			'expenses'
		];
		foreach ($top_level_accounts as $account) {
			if (str_starts_with($s, $account . ':'))
				return true;
		}
		return false;
	}

	private function log($s) {
		global $hledgerlog;
		$hledgerlog .= "\n" . $s;
	}

	private function hledger($args) {
		$appManager = \OC::$server->get(\OCP\App\IAppManager::class);
		$hledger = $appManager->getAppPath("hledger") . "/bin/hledger";
		$journal = "/app/data/ryan/files/Finance/HLedger/journals/2021.journal";
		$budget = "/app/data/ryan/files/Finance/HLedger/budgets/budget.journal";
		return shell_exec("$hledger -f $journal -f $budget $args 2>&1");
	}

}
