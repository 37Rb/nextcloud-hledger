<?php
namespace OCA\HLedger\Controller;

// TODO Why isn't this autoloading???
require_once(__DIR__ . '/../../vendor/hledger/php-hledger/lib/HLedger.php');

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use HLedger\HLedger;

class ReportApiController extends ApiController {

	public function __construct($appName, IRequest $request) {
        parent::__construct($appName, $request);
    }

	/**
	 * @CORS
	 */
	public function index() {
		return new DataResponse('Hello World API');
	}

}
