# S3 CDN 

## Configuration

In your local configuration, specify something like the following to configure
the content Reader/Writer pair, along with actually binding them to usable 
content stores for the CDN 

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
	  ContentService:
	    properties:
	      stores:
            S3Bucket:
              ContentReader: S3ContentReader
              ContentWriter: S3ContentWriter

Additionally, ensure you have the CDNFile extensions bound from the cdncontent
module

```yml

File:
  extensions:
    - CDNFile
Folder: 
  extensions:
    - CDNFolder

```


To change the default ACL applied on upload reconfigure the `defaultAcl` option

```
Injector
  S3ContentWriter:
    type: prototype
    properties:
      s3Service: %$S3Service
      bucket: bucket
      defaultAcl: ""
```

See the [CDN Content](https://github.com/silverstripe-australia/silverstripe-cdncontent) module
for more details
