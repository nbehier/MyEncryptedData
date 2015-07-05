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
                $oFile->setContent('');

                $aFiles[] = $oFile->toArray();
            }
            else {
                $aFiles[] = $file->getRelativePathname();
            }
        }

        return $aFiles;
    }

    /**
     * Get YAML file
     */
    public static function getFile($sPath, $sName, $sPassphrase = '', $sSystemPassphrase = '')
    {
        $finder = new Finder();
        $finder->files()->in($sPath)->depth('== 0')->name($sName . '.yml');

        if (iterator_count($finder) == 0) { return false; }

        $oFile = null;
        foreach ($finder as $file) {
            $oFile = new EncryptFile();
            $oFile->setPath($sPath);
            $oFile->setPassphrase($sPassphrase);
            $oFile->setSystemPassphrase($sSystemPassphrase);
            $oFile->load($file->getContents() );
        }

        return $oFile;
    }

    /**
     * Get a property on a file
     */
    public static function getFileProperty($sPath, $sName, $sProperty)
    {
        if ($oFile = self::getFile($sPath, $sName) ) {
            $aFile = $oFile->toArray(true);
            if (array_key_exists($sProperty, $aFile) ) {
                return $aFile[$sProperty];
            }
        }

        return NULL;
    }

    /**
     * Save YAML file
     */
    public static function saveFile($aDatas, $sPassphrase, $sSystemPath, $sSystemPassphrase)
    {
        $encryptedFile = new EncryptFile($aDatas, $sPassphrase, $sSystemPassphrase, $sSystemPath);
        $encryptedFile->dump();

        return $encryptedFile;
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
                $sBasename = basename($value, '.yml');
                if (is_numeric($sBasename) && $sBasename > $iId) {
                    $iId = $sBasename;
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
                $sBasename = basename($value, '.yml');
                if (strcmp($sBasename, $sName) == 0) { return true; }
            }
        }

        return false;
    }

    /**
     * Delete a file
     * @return bool true if success, false otherwise
     */
    public static function deleteFile($sPath, $sName)
    {
        return unlink($sPath . '/' . $sName . '.yml');
    }
}