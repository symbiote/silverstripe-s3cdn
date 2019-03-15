<?php

/*
 * SS4
 * ---
 * Please see the SS3 version for further information on how to use this.
 * ---
 */

use SilverStripe\Dev\BuildTask;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataList;
use SilverStripe\Control\Director;
use SilverStripe\Security\Permission;


class SS4MigrateAssetsFromS3 extends BuildTask
{
    public function run($request)
    {
        if (!Director::is_cli()) {
            throw new \RuntimeException("This can only be executed from the commandline");
        }

        $sourceFolder = trim($request->getVar('src'), "/");
        if (!$sourceFolder || !is_dir($sourceFolder)) {
            throw new \RuntimeException("'src' parameter must be a readable folder");
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
                if (!$file->File->exists()) {
                    echo "\tCopying $sourceFile\n";
                    $file->File->setFromLocalFile($sourceFile);
                    $file->write();
                } else {
                    echo "\tSkipped $sourceFile as the destination already exists\n";
                }
            } else {
                echo "\tNo source file found\n";
            }
        }
    }
}
