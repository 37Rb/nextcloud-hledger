<?php

namespace OCA\HLedger;

use OCP\IConfig;
use OCP\Files\IRootFolder;
use OCA\HLedger\HLedger;

class Configuration
{
    private $defaultSettings = [
        'hledger_folder' => 'HLedger',
        'journal_file' => 'journal.txt',
        'budget_file' => 'budget.txt'
    ];

    private $appName;
    private $userId;
    private $config;
    private $rootFolder;

    public function __construct(
        string $appName,
        string $userId,
        IConfig $config,
        IRootFolder $rootFolder
    ) {
        $this->appName = $appName;
        $this->userId = $userId;
        $this->config = $config;
        $this->rootFolder = $rootFolder;
    }

    public function getSettings()
    {
        $settings = [];
        foreach ($this->defaultSettings as $key => $value) {
            $settings[$key] = $this->getSetting($key);
        }
        return $settings;
    }

    public function saveSettings($settings)
    {
        foreach ($settings as $key => $value) {
            if (array_key_exists($key, $this->defaultSettings)) {
                $this->saveSetting($key, $value);
            }
        }
    }

    public function getSetting($key)
    {
        return $this->config->getAppValue($this->appName, $key, $this->defaultSettings[$key]);
    }

    public function saveSetting($key, $value)
    {
        $this->config->setAppValue($this->appName, $key, $value);
    }

    public function getJournalFile()
    {
        $userFolder = $this->rootFolder->getUserFolder($this->userId);
        $hledgerFolder = $userFolder->get($this->getSetting('hledger_folder'));
        return $hledgerFolder->get($this->getSetting('journal_file'));
    }

    public function getOperatingSystemPath($file)
    {
        $userFiles = $this->config->getSystemValue('datadirectory') . '/' . $this->userId . '/files';
        $filePath = $this->rootFolder->getFullPath($this->getSetting('hledger_folder') . '/' . $file);
        return realpath($userFiles . $filePath);
    }

    public function createMissingFiles()
    {
        $userFolder = $this->rootFolder->getUserFolder($this->userId);
        $hlFolderName = $this->getSetting('hledger_folder');
        if (!$userFolder->nodeExists($hlFolderName)) {
            $userFolder->newFolder($hlFolderName);
        }
        $hlFolder = $userFolder->get($hlFolderName);
        $journalFileName = $this->getSetting('journal_file');
        if (!$hlFolder->nodeExists($journalFileName)) {
            $hledger = new HLedger($this);
            $hlFolder->newFile($journalFileName, $hledger->createExampleJournal());
        }
        $budgetFileName = $this->getSetting('budget_file');
        if (!$hlFolder->nodeExists($budgetFileName)) {
            $hlFolder->newFile($budgetFileName);
        }
    }
}
