<?php

/**
 * SS3
 * ---
 * This task will migrate your S3 assets over to the local disk (e.g. when moving to EFS).
 * Before running this, clone your S3 bucket into a local disk folder using "s3cmd sync s3://{your_bucket} {src}".
 * ---
*/

class SS3MigrateAssetsFromS3 extends BuildTask
{
    public function run($request)
    {
        if (!Director::is_cli()) {
            throw new RuntimeException("This can only be executed from the commandline");
        }

        // path can be relative or absolute
        $sourceFolder = trim($request->getVar('src'));
        if (!$sourceFolder || !is_dir($sourceFolder)) {
            throw new RuntimeException("'src' parameter must be a readable folder");
        }
        // ensure trailing slash
        if (substr($sourceFolder, -1) !== '/') {
            $sourceFolder .= '/';
        }

        // use passed in IDs for testing if desired
        $ids = $request->getVar('ids');

        $files = File::get();
        if (strlen($ids)) {
            $ids = explode(",", $ids);
            $files = $files->filter('ID', $ids);
        }

        $this->copyFiles($files, $sourceFolder);
    }

    public function copyFiles(DataList $files, $sourceFolder)
    {
        $failedDirs = [];

        foreach ($files as $file) {
            echo "Processing $file->ID : $file->CDNFile\n";
            /** @var File $file */
            $source = $file->CDNFile;
            if (!strlen($source)) {
                echo "\tSkipping $file->ID:$file->Title as it doesn't have a CDNFile source\n";
                continue;
            }
            $sourceFile = str_replace('Default:||', $sourceFolder, $source);

            if (file_exists($sourceFile) && is_readable($sourceFile)) {
                // CWD is framework/ so go up one dir
                $filename = "../{$file->Filename}";
                if (!file_exists($filename)) {
                    echo "\tCopying $sourceFile\n";
                    echo "\t -> to $filename\n";
                    // ensure dir exists
                    $destDir = dirname($filename);
                    if (!file_exists($destDir)) {
                        echo "\t -> mkdir $destDir\n";
                        mkdir($destDir, 0775, true);
                    }
                    // copy file
                    $copied = copy($sourceFile, $filename);
                    $result = $copied ? 'SUCCESS' : 'FAILED';
                    echo "\t -> $result\n";
                    // copy complete, change image classname
                    if ($copied) {
                        if ($file->ClassName == 'CdnImage') {
                            $file->ClassName = 'Image';
                            $file->write();
                        }
                    }
                    // copy failed, record dir for later output
                    else if (!$copied && !in_array($destDir, $failedDirs)) {
                        $failedDirs[] = $destDir;
                    }
                } else {
                    echo "\tSkipping as the destination already exists\n";
                }
            } else {
                echo "\tNo source file found\n";
            }
        }

        // log failed dirs
        if (count($failedDirs) > 0) {
            sort($failedDirs);
            echo "Failed to copy files to the following directories:\n";
            foreach ($failedDirs as $dir) {
                echo "\t -> $dir\n";
            }
        } else {
            echo "Completed without any failures.\n";
        }
    }
}