<?php

class MigrateAmazonS3Task extends BuildTask
{
    protected $title = "Migrate Amazon S3 Task";

    protected $description = 'Setup File tables to point to S3 server';

    public function run($request)
    {
        if (!Director::is_cli() && !isset($_GET['run'])) {
            DB::alteration_message('Must add ?run=1 to execute task immediately.', 'error');
            return false;
        }
        if (!Director::is_cli()) 
        {
            // - Add UTF-8 so characters will render as they should when debugging (so you can visualize inproperly formatted characters easier)
            // - Add base_tag so that printing blocks of HTML works properly with relative links (helps with visualizing errors)
?>
            <head>
                <?php echo SSViewer::get_base_tag(''); ?>
                <meta charset="UTF-8">
            </head>
<?php
        }

        $cdn = $request->getVar('cdn');
        $prefix = $request->getVar('prefix');
        $folderID = (int)$request->getVar('FolderID');
        $this->syncFiles($folderID, $cdn, $prefix);
    }

    public function syncFiles($folderID = 0, $cdn = '', $prefix = '') {
        if (!$prefix) {
            $prefix = 'siteassets';
        }

        // Set defaults
        $defaultStore = singleton('ContentService')->getDefaultStore();
        if (!$defaultStore) {
            throw new Exception('Must configure a "defaultStore" on ContentService in YML.');
        }
        if (!$cdn) {
            // Default to configured CDN
            $cdn = $defaultStore;
        }
        $prefix = FileNameFilter::create()->filter($prefix);
        if (!$prefix) {
            throw new LogicException('Empty $prefix not allowed.');
        }
        $prefix = $prefix.'/';

        // Get folder
        $folderIDs = array();
        if ($folderID) {
            // If only updating specific FolderID
            $folder = Folder::get()->byID($folderID);
            $folderID = ($folder && $folder->exists()) ? (int)$folder->ID : 0;
            if ($folderID) {
                $folderIDs[$folderID] = ($folder && $folder->exists()) ? (int)$folder->ID : 0;
            }
        } else {
            $folders = array();
            foreach (Folder::get()->filter(array('StoreInCdn' => $cdn)) as $record) {
                $folders[$record->ID] = $record;
            }
            // All top level folders default to the 'defaultStore'
            if ($cdn && $cdn === $defaultStore) {
                foreach (Folder::get()->filter(array('ParentID' => 0)) as $record) {
                    $folders[$record->ID] = $record;
                }
                $folderIDs[0] = 0; // Get root/top level File objects
            }
            $folders = array_values($folders);
            while ($folders) {
                $folder = array_shift($folders);
                // StoreInCDN being empty = Inherit
                if (!$folder->StoreInCDN || $folder->StoreInCDN === $cdn) {
                    $folderID = (int)$folder->ID;

                    // Add this folder to list
                    $folderIDs[$folderID] = $folderID;

                    // Queue up children to iterate over in this loop
                    $childFolders = Folder::get()->filter(array('ParentID' => $folderID));
                    foreach ($childFolders as $childFolder) {
                        $folders[] = $childFolder;
                    }
                }
            }
        }
        if (!$folderIDs) {
            throw new Exception('No folders found under "'.$cdn.'" cdn.');
        }

        // Get List of files
        $list = File::get()->filter(array(
            'ClassName:not' => 'Folder',
            'ParentID' => $folderIDs,
        ));

        $folderCount = count($folderIDs); 
        // Count non-existent root folder with +1
        $folderMaxCount = Folder::get()->count() + 1;
        $fileCount = $list->count();
        $this->log('Files to process: '.$fileCount.', in '.$folderCount.'/'.$folderMaxCount.' folders.');
        foreach ($list as $file) {
            if ($file instanceof Folder) {
                continue;
            }
            if ($file->ClassName === 'Image') {
                $file = $file->newClassInstance('CdnImage');
                $file->defineMethods();
            }

            $filename = ltrim($file->Filename, ASSETS_DIR.'/');
            $file->CDNFile = $cdn . ':||' . $prefix . $filename;
            
            if (!$file->getChangedFields(true, DataObject::CHANGE_VALUE)) {
                $this->log('No changes to '.$file->ClassName.' #'.$file->ID);
                continue;
            }
            try {
                $validationResult = $file->doValidate();
                if (!$validationResult->valid()) {
                    $this->log('File #'.$file->ID.' did not validate().');
                    continue;
                }
                try {
                    $file->write();
                    $this->log('Changed "'.$file->Title.'" ('.$file->class.') #'.$file->ID);
                } catch (Exception $e) {
                    $this->log('Failed to write '.$file->ClassName.' #'.$file->ID, 'error', $e);
                }
            } catch (Exception $e) {
                $this->log('Unexpected failure on '.$file->ClassName.' #'.$file->ID, 'error', $e);
            }
        }
    }

    protected function log($message, $type = '', Exception $e = null) {
        if ($e) {
            $message .= ' - ' . $e->getMessage();
        }
        DB::alteration_message($message, $type);
        if ($type === 'error') {
            // Send out the error details to the logger for writing
            SS_Log::log(
                $e,
                SS_Log::ERR
            );
        }
    }
}