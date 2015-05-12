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
			$id = $this->getId();
			if (substr($id, -1) == '/') {
				$this->s3Info = $this->listId($id);
			} else {
				$this->s3Info = $this->s3Service->getObject(array(
					'Bucket' => $this->bucket,
					'Key' => $id
				));
			}
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

		if ($result && (isset($result['CommonPrefixes']) || isset($result['Contents']))) {
			return true;
		}

		return false;
	}
	
	protected $listedItems = null;
	
	protected function listId($id) {
		if ($this->listedItems) {
			return $this->listedItems;
		}
		$this->listedItems = $this->s3Service->listObjects(array(
			"Bucket" => $this->bucket,
			"Prefix" => $id,
			'return_prefixes' => true,
			'Delimiter'	=> '/',
		));
		
		return $this->listedItems;
	}

	/**
	 * Returns a list of content readers for a given s3 folder
	 * 
	 * @return \S3ContentReader
	 */
	public function getList() {
		if ($this->isListable()) {
			$list = array();
			$objects = $this->listId($this->getId());
			if (isset($objects['CommonPrefixes'])) {
				foreach ($objects['CommonPrefixes'] as $folder) {
					$id = $this->getSourceIdentifier() . ContentService::SEPARATOR . $folder['Prefix'];
					$new = singleton('ContentService')->getReader($id);
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

					$id = $this->getSourceIdentifier() . ContentService::SEPARATOR . $name;
					$new = singleton('ContentService')->getReader($id);
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
	 * Get a secure URL set to expire in $expires seconds time
	 * 
	 * @param int $expires
	 */
	public function getSecureURL($expires = 60) {
		return $this->s3Service->getObjectUrl($this->bucket, $this->getId(), time() + $expires);
	}

	/**
	 * Read this content as a string
	 * 
	 * @return string
	 */
	public function read() {
		try {
			$result = $this->s3Service->getObject(array(
				'Bucket' => $this->bucket,
				'Key' => $this->getId()
			));
			
			return $result['Body'];
			
		} catch (Exception $ex) {
			SS_Log::log($ex, SS_Log::WARN);
			return;
		}
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

	public function providerOfUrl($url) {
		if (strpos($url, $this->baseUrl) !== false) {
			$prefix = substr($url, strlen($this->baseUrl) + 1);
			$id = $this->getSourceIdentifier() . ContentService::SEPARATOR . $prefix;
			$new = singleton('ContentService')->getReader($id);
			return $new;
		}
		return false;
	}
}
