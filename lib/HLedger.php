<?php
namespace OCA\HLedger;

use OCA\HLedger\Configuration;

class HLedger
{
    private $config;
    private $hledger;

    public function __construct(
        Configuration $config
    ) {
        $this->config = $config;
    }

    public function budgetReport()
    {
        $report = $this->createHLedger()->balance([
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

	public function accounts() {
		return $this->createHLedger()->accounts();
	}

    public function incomeStatement()
    {
        return $this->createHLedger()->incomeStatement([
            ['monthly'],
            ['market'],
            ['begin', 'thisyear'],
            ['end', 'nextmonth']
        ]);
    }

    public function balanceSheet()
    {
        return $this->createHLedger()->balanceSheet([
            ['monthly'],
            ['market'],
            ['begin', 'thisyear'],
            ['end', 'thismonth']
        ]);
    }

    public function accountRegister($account)
    {
        $report = $this->createHLedger()->accountRegister([['market']], [$account]);
        return array_map(function ($row) {
            return array_slice($row, 1);  // remove txnidx column
        }, $report);
    }

    private function createHLedger()
    {
        return new \Hledger\HLedger([
            ['file', $this->config->getFilePath($this->config->getSetting('journal_file'))],
            ['file', $this->config->getFilePath($this->config->getSetting('budget_file'))]
        ]);
    }
}
