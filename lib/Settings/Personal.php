<?php
namespace OCA\HLedger\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\Settings\ISettings;

class Personal implements ISettings {

  /** @var Collector */
  private $collector;

  /** @var IConfig */
  private $config;

  /** @var IL10N */
  private $l;

  /** @var IDateTimeFormatter */
  private $dateTimeFormatter;

  /** @var IJobList */
  private $jobList;

  public function __construct(Collector $collector,
                              IConfig $config,
                              IL10N $l,
                              IDateTimeFormatter $dateTimeFormatter,
                              IJobList $jobList
          ) {
                  $this->collector = $collector;
                  $this->config = $config;
                  $this->l = $l;
                  $this->dateTimeFormatter = $dateTimeFormatter;
                  $this->jobList = $jobList;
          }

  public function getForm() {

    $hledgerFolder = $this->config->getAppValue('hledger', 'hledger_folder', 'HLedger');

    $parameters = [
      'hledger_folder' => $hledgerFolder
    ];

    return new TemplateResponse('hledger', 'personal', $parameters);
  }

  public function getSection() {
    return 'sharing';
  }

  public function getSectionID() {
    return 'additional';
  }

  public function getPriority() {
    return 50;
  }

}
