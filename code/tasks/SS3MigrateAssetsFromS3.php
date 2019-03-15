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

        // The context for the following {src} parameter will be relative to "framework", so "src=../{src}" for example
        $sourceFolder = trim($request->getVar('src'), "/");
        if (!$sourceFolder || !is_dir($sourceFolder)) {
            throw new RuntimeException("'src' parameter must be a readable folder");
        }

        $sourceFolder .= "/";

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
                $filename = "../{$file->Filename}";
                if (!file_exists($filename)) {
                    echo "\tCopying $sourceFile\n";
                    copy($sourceFile, $filename);
                } else {
                    echo "\tSkipped $sourceFile as the destination already exists\n";
                }
            } else {
                echo "\tNo source file found\n";
            }
        }
    }
}
