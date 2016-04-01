<?php

/**
 * @author marcus
 */
class MigrateAssetsToS3 extends BuildTask {
    protected $description = 'Migrate existing File records to S3';
    
    public function run($request) {
		$cdn = $request->getVar('cdn');
        $prefix = FileNameFilter::create()->filter($request->getVar('prefix'));
        if ($prefix) {
            $prefix = trim($prefix, '/') . '/';
        }
        
		if (!$cdn) {
			$this->o("Skipping s3 file setting as cdn variable not supplied");
			return;
		}
        
        $allFolders = array();
            
        if ($folderId = $request->getVar('FolderID')) {
            $folder = Folder::get()->byID($folderId);
            if ($folder && $folder->ID) {
                $allFolders[] = $folder->ID;
                $allFolders = array_merge($allFolders, $this->childrenOf($folder, $cdn));
            }
        } else {
            $filter = array(
                'StoreInCdn' => $cdn,
            );

            $toStore = Folder::get()->filter($filter);

            foreach ($toStore as $folder) {
                $allFolders[] = $folder->ID;
                $allFolders = array_merge($allFolders, $this->childrenOf($folder, $cdn));
            }

            $default = singleton('ContentService')->getDefaultStore();
            if ($default && $default == $cdn) {
                $allFolders = array_merge($allFolders, $this->childrenOf(0, $cdn));
            }
        }
        
        sort($allFolders);
        
		$containedFiles = File::get()->filter(array(
            'ParentID' => $allFolders,
            'ClassName:not' => 'Folder',
        ));
        
        $containedFiles = $containedFiles->where('("CDNFile" IS NULL OR CDNFile NOT LIKE \'' . Convert::raw2sql($cdn) .  '%\')');
        
		foreach ($containedFiles as $file) {
			if ($file instanceof Folder) {
				continue;
			}
			if (strlen($file->CDNFile) === 0) {
				$name = $file->Filename;
                
				if (strpos($name, 'assets') === 0) {
					$name = substr($name, 7);
				}
				
				if ($file->ClassName == 'Image') {
					$file = $file->newClassInstance('CdnImage');
					$file->defineMethods();
				}

                $cdnPath = $cdn . ':||' . $prefix . $name;
				$file->CDNFile = $cdnPath;
				
                try {
                    if ($v = $file->doValidate()) {
                        if ($v->valid()) {
                            $file->write();
                        } else {
                            throw new Exception("File is invalid");
                        }
                    }
                } catch (Exception $ex) {
                    // so what
                    $this->o("Exception updating file $name - " . $ex->getMessage());
                }
				
				$this->o("Updated " . $file->Title . " to " . $file->CDNFile);
			}
		}
    }
    
    protected function childrenOf($f, $cdn) {
        if (!is_int($f)) {
            $f = $f->ID;
        }
		$childFolders = Folder::get()->filter(array(
			'ParentID'	=> $f
		));
		
		$mykids = array();
		
		if ($childFolders && $childFolders->count()) {
			foreach ($childFolders as $k) {
                if ($k->StoreInCDN == $cdn || strlen($k->StoreInCDN) == 0) {
                    $mykids[] = $k->ID;
                    $itskids = $this->childrenOf($k, $cdn);
                    $mykids = array_merge($mykids, $itskids);
                }
			}
		}
		return $mykids;
	}
    
    protected function o($txt) {
		if (PHP_SAPI == 'cli') {
			echo "$txt\n";
		} else {
			echo "$txt<br/>\n";
		}
	}
}
