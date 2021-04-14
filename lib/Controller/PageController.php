<?php
namespace OCA\HLedger\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\Files\IRootFolder;

class PageController extends Controller {
	private $userId;
	private $config;
	private $rootFolder;
	private $settings;

	public function __construct($AppName, IRequest $request, $UserId, IConfig $config, IRootFolder $rootFolder){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->config = $config;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {

		$this->check_for_new_settings();
		$this->load_settings();
		$this->show_report();

		return new TemplateResponse('hledger', 'index', $this->settings);
	}

	private function check_for_new_settings() {
		if (isset($_GET['hledger_folder']))
			$this->config->setAppValue('hledger', 'hledger_folder', $_GET['hledger_folder']);
		if (isset($_GET['journal_file']))
			$this->config->setAppValue('hledger', 'journal_file', $_GET['journal_file']);
		if (isset($_GET['budget_file']))
			$this->config->setAppValue('hledger', 'budget_file', $_GET['budget_file']);
	}

	private function load_settings() {
		 $this->settings = [
			'hledger_folder' => $this->config->getAppValue('hledger', 'hledger_folder', 'HLedger'),
			'journal_file' => $this->config->getAppValue('hledger', 'journal_file', 'journal.txt'),
			'budget_file' => $this->config->getAppValue('hledger', 'budget_file', 'budget.txt')
		];
	}

	private function show_report() {
		$tab = $_GET['tab'];
		if ($tab == 'balance') {
			$this->show_csv_report($this->hledger('bs -MV -b thisyear -e thismonth -O csv'));
		} else if ($tab == 'income') {
			$this->show_csv_report($this->hledger('is -MV -b thisyear -e thismonth -O csv'));
		} else {
			$header = '"Budget last month and this month","","","",""' . "\n";
			$this->show_csv_report($header . $this->hledger('bal -MV -p lastmonth -e nextmonth --budget "not:desc:opening balances" -O csv'));
		}
	}

	private function show_csv_report($report) {
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
		$user_path = '/app/data/' . $this->userId . '/files'; // XXX need to query data path!!!
		$hledger_path = $user_path . $this->rootFolder->getFullPath($this->settings['hledger_folder']) . '/';
		$journal = $hledger_path . $this->settings['journal_file'];
		$budget = $hledger_path . $this->settings['budget_file'];
		return shell_exec("$hledger -f $journal -f $budget $args 2>&1");
	}

}
