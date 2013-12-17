<?php

/**
 * Read content from haylix cdn
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class S3ContentReader extends ContentReader {
	
	public $bucket = 'bucket';
	
	/**
	 * @var S3Client
	 */
	public $s3Service;
	
	
	/**
	 * The base URL to use with the s3 managed asset. Allows the use
	 * of CloudFront base urls instead. 
	 *
	 * @var string
	 */
	public $baseUrl = 'https://s3.amazonaws.com';
	
	protected function getInfo() {
		
	}

	public function isReadable() {
		if (!parent::isReadable()) {
			return;
		}
		
		$object = $this->s3service->getObject(array('Bucket' => $this->bucket, 'Key' => $this->getId()));
		
		return $object != null;
		
	}
	
	public function getBaseUrl() {
		return $this->baseUrl;
	}

	/**
	 * Get a url to this piece of content
	 * 
	 * @return string
	 */
	public function getURL() {
		return $this->getBaseUrl() .'/' . $this->bucket .'/' . $this->getId();
	}
	
	/**
	 * Read this content as a string
	 * 
	 * @return string
	 */
	public function read() {
		
	}
}
