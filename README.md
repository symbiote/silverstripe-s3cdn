# S3 CDN 

## Configuration

	Injector:
	  S3Service:
	    constructor:
	      - {your_api_key}
	      - {your_api_secret}
	  S3ContentReader:
	    type: prototype
	    properties:
	      s3service: %$S3Service
	      bucket: {your_bucket_name}
	  S3ContentWriter:
	    type: prototype
	    properties:
	      s3service: %$S3Service
	      bucket: {your_bucket_name}
