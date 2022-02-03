<?php

namespace OCA\HLedger;

// TODO Why isn't this autoloading???
require_once(__DIR__ . '/../vendor/hledger/php-hledger/lib/HLedger.php');

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

    public function addTransaction($file, $transaction)
    {
        $s = PHP_EOL . $this->createHLedger()->makeTransaction($transaction) . PHP_EOL;
        $f = $file->fopen('a');
        if (fwrite($f, $s) != strlen($s)) {
            throw new \Exception("Failed to write transaction");
        }
        fclose($f);
        $file->touch(); // seems to help trigger syncing
    }

    public function getLedgerContents($file)
    {
        return $file->getContent();
    }

    public function putLedgerContents($file, $contents)
    {
        return $file->putContent($contents);
    }

    public function accounts()
    {
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
            ['end', 'nextmonth']
        ]);
    }

    public function balance($query, $options)
    {
        $useDefaults = true;

        foreach ($options as $option) {
            if ($option[0] == 'daily' || $option[0] == 'weekly' || $option[0] == 'monthly' || $option[0] == 'quarterly' || $option[0] == 'yearly' ||
                $option[0] == 'historical' || $option[0] == 'market') {
                // Allowed
            } else if ($option[0] == 'begin' || $option[0] == 'end') {
                // Allowed
            } else if ($option[0] == 'file') {
                // Allowed, but check if file exists
                $list = $this->config->getListOfJournalFiles();
                if (!in_array($option[1], $list))
                {
                    // File that is being included either doesn't exist or it's not in a "safe" location. Security issue!
                    return null;
                }
                $useDefaults = false;
            } else {
                // Only allow options that have been specifically allowed
                return null;
            }
        }
        if ($useDefaults) {
            $hledger = $this->createHLedger();
        } else  {
            $hledger = $this->createBlankHLedger();
        }
        if (count($query) > 0)
        {
            // Security issue, don't allow users to pass argument files or actual options in the $query. Adding '--' to the args prevents this.
            $query = array_merge(['--'], $query);
        }
        return $hledger->balance($options, $query);
    }

    public function customReport($reportName, $includeDefaultFiles)
    {
        $report = null;
        $reportList = $this->getCustomReportList();
        if (in_array($reportName, array_map(function ($report) { return $report['fileName']; }, $reportList)))
        {
            if ($includeDefaultFiles)
            {
                $report = $this->createHLedger()->custom($reportName);
            }
            else
            {
                $report = $this->createBlankHLedger()->custom($reportName);
            }
            if (count($report) > 0 && count($report[0]) > 0 && $report[0][0] == 'txnidx')
            {
                return array_map(function ($row) {
                    return array_slice($row, 1);  // remove txnidx column
                }, $report);
            }
            else
            {
                return $report;
            }
        }
        else
        {
            return null;
        }
    }

    public function accountRegister($account)
    {
        // Security issue, don't allow users to pass argument files or actual options in the $account. Adding '--' to the args prevents this.
        $report = $this->createHLedger()->accountRegister([['market']], ['--', $account]);
        return array_map(function ($row) {
            return array_slice($row, 1);  // remove txnidx column
        }, $report);
    }

    public function getCustomReportList()
    {
        $hledgerFolder = $this->config->getHledgerFolder();
        $listing = $hledgerFolder->getDirectoryListing();
        $fileList = [];
        foreach ($listing as $file) {
            $fileName = $file->getName();
            if (str_ends_with($fileName, '.args.txt') || str_ends_with($fileName, '.report.txt') || str_ends_with($fileName, '.args') || str_ends_with($fileName, '.report'))
            {
                $includesFiles = false;
                $reportName = $fileName;
                if (str_ends_with($fileName, '.args.txt'))
                {
                    $reportName = str_replace('.args.txt', '', $fileName);
                }
                else if (str_ends_with($fileName, '.report.txt'))
                {
                    $reportName = str_replace('.report.txt', '', $fileName);
                }
                else if (str_ends_with($fileName, '.args'))
                {
                    $reportName = str_replace('.args', '', $fileName);
                }
                else if (str_ends_with($fileName, '.report'))
                {
                    $reportName = str_replace('.report', '', $fileName);
                }
                $reportName = str_replace('_', ' ', $reportName);
                $contents = preg_split("/\r\n|\n|\r/", $file->getContent());
                foreach ($contents as $fileLine)
                {
                    if ($fileLine == "--file" || $fileLine == "-f" || str_starts_with($fileLine, "--file=") || str_starts_with($fileLine, "-f="))
                    {
                        $includesFiles = true;
                        break;
                    }
                }

                $fileList[$reportName] = [ 'name' => $reportName, 'fileName' => $fileName, 'includesFiles' => $includesFiles ];
            }
        }
        return $fileList;
    }

    private function createBlankHLedger()
    {
        return new \Hledger\HLedger([] , $this->config->getOperatingSystemPathForData());
    }

    private function createHLedger()
    {
        return new \Hledger\HLedger([
            ['file', $this->config->getSetting('journal_file')],
            ['file', $this->config->getSetting('budget_file')]
        ], $this->config->getOperatingSystemPathForData());
    }

    public function createExampleJournal()
    {
        $hledger = $this->createHLedger();
        $s = '; Example HLedger journal file.' . PHP_EOL;

        $s .= PHP_EOL . $hledger->makeTransaction([
            'date' => new \DateTime(),
            'description' => 'Opening Balances',
            'postings' => [
                [
                    'account' => 'assets:Checking',
                    'amount' => '5000.00'
                ],
                [
                    'account' => 'assets:Cash',
                    'amount' => '200.00'
                ],
                [
                    'account' => 'equity:Opening Balances',
                    'amount' => '-5200.00'
                ]
            ]
        ]) . PHP_EOL;

        $s .= PHP_EOL . $hledger->makeTransaction([
            'date' => new \DateTime(),
            'description' => 'Got paid',
            'postings' => [
                [
                    'account' => 'assets:Checking',
                    'amount' => '2000.00'
                ],
                [
                    'account' => 'income:My Job',
                    'amount' => '-2000.00'
                ]
            ]
        ]) . PHP_EOL;

        $s .= PHP_EOL . $hledger->makeTransaction([
            'date' => new \DateTime(),
            'description' => 'Fill up gas tank',
            'postings' => [
                [
                    'account' => 'expenses:Gas',
                    'amount' => '39.50'
                ],
                [
                    'account' => 'liabilities:Credit Card',
                    'amount' => '-39.50'
                ]
            ]
        ]) . PHP_EOL;

        return $s;
    }
}
