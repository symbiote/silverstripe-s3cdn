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
	 *
	 * @var Guzzle\Service\Resource\Model
	 */
	private $s3Info;

	/**
	 * The base URL to use with the s3 managed asset. Allows the use
	 * of CloudFront base urls instead. 
	 *
	 * @var string
	 */
	public $baseUrl = 'https://s3.amazonaws.com';

	/**
	 * 
	 * @return Guzzle\Service\Resource\Model
	 */
	public function getInfo() {
		if (!$this->s3Info) {
			$this->s3Info = $this->s3Service->getObject(array(
				'Bucket' => $this->bucket,
				'Key' => $this->getId()
			));
		}
		
		return $this->s3Info;
	}
	
	/**
	 * 
	 * Set the S3 information about this item directly if available
	 * 
	 * @param Guzzle\Service\Resource\Model $data
	 */
	public function setS3Info($data) {
		$this->s3Info = $data;
	}

	public function isReadable() {
		if (!parent::isReadable()) {
			return;
		}

		return strlen($this->getURL());
	}

	/**
	 * An S3 object is listable if its content type is a directory
	 * 
	 * @return boolean
	 */
	public function isListable() {
		$result = $this->getInfo();

		if ($result && isset($result['ContentType']) && $result['ContentType'] === 'application/x-directory') {
			return true;
		}

		return false;
	}

	/**
	 * Returns a list of content readers for a given s3 folder
	 * 
	 * @return \S3ContentReader
	 */
	public function getList() {
		if ($this->isListable()) {
			$objects = $this->s3Service->listObjects(array(
				"Bucket" => $this->bucket,
				"Prefix" => $this->getId(),
				'return_prefixes' => true,
				'Delimiter'	=> '/',
			));
			
			$list = array();
			
			if (isset($objects['CommonPrefixes'])) {
				foreach ($objects['CommonPrefixes'] as $folder) {
					$new = new S3ContentReader($folder['Prefix']);
					$new->s3Service = $this->s3Service;
					$new->bucket = $this->bucket;
					$new->baseUrl = $this->baseUrl;
					$list[] = $new;
				}
			}

			if (isset($objects['Contents'])) {
				foreach ($objects['Contents'] as $object) {
					$name = $object['Key'];
					if ($name == $this->getId()) {
						continue;
					}
					
					$new = new S3ContentReader($name);
					$new->s3Service = $this->s3Service;
					$new->bucket = $this->bucket;
					$new->baseUrl = $this->baseUrl;
					$new->setS3Info($object);
					$list[] = $new;
				}
			}
			
			return $list;
		}

		return array();
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
		return $this->getBaseUrl() . '/' . $this->getId();
	}

	/**
	 * Read this content as a string
	 * 
	 * @return string
	 */
	public function read() {
		$result = $this->s3Service->getObject(array(
			'Bucket' => $this->bucket,
			'Key' => $this->getId()
		));

		return $result['Body'];
	}

	/**
	 * Check that the object exists remotely
	 * 
	 * @return boolean
	 */
	public function exists() {
		$exists = $this->s3Service->doesObjectExist($this->bucket, $this->getId());
		return $exists;
	}

}
