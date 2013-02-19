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
	
	protected function getInfo() {
		
	}

	public function isReadable() {
		if (!parent::isReadable()) {
			return;
		}
		
		$object = $this->s3service->getObject(array('Bucket' => $this->bucket, 'Key' => $this->getId()));
		
		return $object != null;
		
	}
	
	public function urlStub() {
		return 'https://s3.amazonaws.com';
	}

	/**
	 * Get a url to this piece of content
	 * 
	 * @return string
	 */
	public function getURL() {
		return $this->urlStub() .'/' . $this->bucket .'/' . $this->getId();
	}
	
	/**
	 * Read this content as a string
	 * 
	 * @return string
	 */
	public function read() {
		
	}
}
