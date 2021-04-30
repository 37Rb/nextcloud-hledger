<?php
namespace OCA\HLedger;

use OCP\IConfig;
use OCP\Files\IRootFolder;

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
        return [
            'hledger_folder' => $this->getSetting('hledger_folder'),
            'journal_file' => $this->getSetting('journal_file'),
            'budget_file' => $this->getSetting('budget_file')
        ];
    }

    public function getSetting($key)
    {
        return $this->config->getAppValue($this->appName, $key, $this->defaultSettings[$key]);
    }

    public function setSetting($key, $value)
    {
        $this->config->setAppValue($this->appName, $key, $value);
    }

    public function getFilePath($file)
    {
        $userFiles = $this->config->getSystemValue('datadirectory') . '/' . $this->userId . '/files';
        $filePath = $this->rootFolder->getFullPath($this->getSetting('hledger_folder') . '/' . $file);
        return realpath($userFiles . $filePath);
    }
}
