# S3 CDN 

## Configuration

In your local configuration, specify something like

    ---
    Name: locals3settings
    After: 
      - '#s3services'
    ---
	Injector:
	  S3Service:
	    constructor:
	      key: {your_api_key}
	      secret: {your_api_secret}
              region: {region}
	  S3ContentReader:
	    type: prototype
	    properties:
	      s3service: %$S3Service
	      bucket: {your_bucket_name}
              baseUrl: {base_url_for_bucket}
	  S3ContentWriter:
	    type: prototype
	    properties:
	      s3service: %$S3Service
	      bucket: {your_bucket_name}
              baseUrl: {base_url_for_bucket}

