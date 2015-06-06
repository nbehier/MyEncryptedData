<?php

namespace App\Lib;

use Symfony\Component\Finder\Finder;
use App\Lib\EncryptFile;

class FileFinder
{
    /**
     * List YAML files
     */
    public static function listFiles($sPath, $bLoadContent = true)
    {
        $aFiles = array();

        $finder = new Finder();
        $finder->files()->in($sPath)->depth('== 0')->name('*.yml')->sortByName();

        foreach ($finder as $file) {
            if ($bLoadContent) {
                $oFile = new EncryptFile();
                $oFile->setPath($sPath);
                $oFile->load($file->getContents() );

                $aFiles[] = $oFile->toArray();
            }
            else {
                $aFiles[] = $file->getRelativePathname();
            }
        }

        return $aFiles;
    }

    /**
     * Get max numeric name file and increment
     */
    public static function getNewId($sPath)
    {
        $iId = 0;
        $aList = self::listFiles($sPath, false);

        if (! empty($aList)) {
            foreach ($aList as $value) {
                if (is_numeric($value) && $value > $iId) {
                    $iId = $value;
                }
            }
        }

        return ++$iId;
    }

    /**
     * Check if file exists
     */
    public static function isFileExist($sPath, $sName)
    {
        $aList = self::listFiles($sPath, false);
        if (! empty($aList)) {
            foreach ($aList as $value) {
                if (strcmp($value, $sName) == 0) { return true; }
            }
        }

        return false;
    }
}