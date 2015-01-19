<?php

use Aws\S3\Enum\CannedAcl;

/**
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class S3ContentWriter extends ContentWriter {

	public $bucket = 'bucket';
	
	/**
	 * @var S3Client
	 */
	public $s3Service;
	
	
	/**
	 * Write content to storage
	 *
	 * @param mixed $content 
	 * @param string $name
	 *				The name that is used to refer to this piece of content, 
	 *				if needed
	 */
	public function write($content = null, $fullname = '') {
		
		$reader = $this->getReaderWrapper($content);
		
		$name = basename($fullname);
		
		if (!$this->id) {
			if (!$name) {
				throw new Exception("Cannot write a file without a name");
			}
			$this->id = $this->nameToId($fullname);
		}

		$type = null;
		if (class_exists('HTTP')) {
			$type = HTTP::get_mime_type($name);
		}
		$attrs = array(
			'Bucket' => $this->bucket,
			'Key'    => $this->id,
			'Body'   => $reader->read(),
			'ACL'    => CannedAcl::PUBLIC_READ
		);
		
		if ($type) {
			$attrs['ContentType'] = $type;
		}
		
		$result = $this->s3Service->putObject($attrs);
		
		if (!$result) {
			throw new Exception("Failed uploading to S3");
		}

		// print_r($this->getHaylix()->info_container($this->publicContainer));
	}

	public function delete() {
		$result = $this->s3Service->deleteObject(array('Bucket' => $this->bucket, 'Key' => $this->getId()));
		return $result;
	}
}
