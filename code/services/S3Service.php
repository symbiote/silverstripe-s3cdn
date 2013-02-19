<?php
require_once dirname(__DIR__) . '/thirdparty/aws.phar';

use Aws\Common\Aws;
use Aws\S3\Exception\S3Exception;

/**
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class S3Service {

	protected $s3;
	
	public function __construct($key, $secret, $region = 'us-east-1') {
		
		// Instantiate an S3 client
		$this->s3 = Aws::factory(array(
			'key'    => $key,
			'secret' => $secret,
			'region' => $region
		))->get('s3');
	}
	
	public function __call($name, $arguments) {
		return call_user_func_array(array($this->s3, $name), $arguments);
	}
}
