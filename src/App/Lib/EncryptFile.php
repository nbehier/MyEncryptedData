<?php

namespace App\Lib;

use App\Lib\File;


class EncryptFile extends File
{
    protected $passphrase;
    protected $systempassphrase;
    protected $systempath;
    protected $witness;

    public function __construct(array $properties = null, $passphrase = '', $systempassphrase = '', $systempath = '')
    {
        parent::__construct($properties);

        if (is_array($properties) && array_key_exists('witness', $properties) ) {
            $this->setWitness($properties['witness'] );
        }

        $this->passphrase = $passphrase;
        $this->systempassphrase = $systempassphrase;
        $this->systempath = $systempath;
    }

    public function toArray($withPrivateFields = false)
    {
        $aEncryptedFile = parent::toArray($withPrivateFields);

        if ($withPrivateFields) {
            $aEncryptedFile['witness'] = $this->witness;
        }

        return $aEncryptedFile;
    }

    /**
     * Load file ; if passphrase given, decrypt content
     */
    public function load($sYmlContent)
    {
        $oYaml = parent::load($sYmlContent);

        $this->setWitness($oYaml['witness']);

        if (! empty($this->passphrase) ) {
            $this->decrypt($this->passphrase);
        }
    }

    /**
     * Dump encrypted file with user passphrase and system passphrase
     */
    public function dump($bForce = false)
    {
        if (empty($this->passphrase) ) { return; }

        $sContent = $this->content;
        $sWitness = $this->witness;
        $this->encrypt($this->passphrase);
        parent::dump($bForce);

        // Revert original file
        $this->setContent($sContent);
        $this->setWitness($sWitness);

        // Backup versioned filed with system passphrase
        $this->dumpSystem();
    }

    public function setPassphrase($sPassphrase)
    {
        if (is_string($sPassphrase) ) { $this->passphrase = $sPassphrase; }
    }

    public function setSystemPassphrase($sPassphrase)
    {
        if (is_string($sPassphrase) ) { $this->systempassphrase = $sPassphrase; }
    }

    public function setWitness($sWitness)
    {
        if (is_string($sWitness) ) { $this->witness = $sWitness; }
    }

    public function checkWitness($sWitness)
    {
        if (empty($this->witness) || $this->witness == $sWitness ) { return true; }
        return false;
    }

    public static function checkEncryptWitness($sWitnessToCompare, $sWitness, $sPassphrase, $sSystemPassphrase)
    {
        $hashed_password = crypt($sPassphrase, $sSystemPassphrase);
        $sEncryptWitness = openssl_encrypt($sWitness, 'AES-128-CBC', $hashed_password, 0, $sSystemPassphrase);

        if ($sWitnessToCompare == $sEncryptWitness) {
            return true;
        }

        return false;
    }

    protected function dumpSystem()
    {
        $sId = $this->id;
        $sPath = $this->path;
        $sContent = $this->content;
        $sWitness = $this->witness;

        $this->id = $this->id . '-' . date('YmdHis');
        $this->path = $this->systempath;

        $this->encrypt($this->systempassphrase);
        parent::dump(true);

        // Revert original file
        $this->setId($sId);
        $this->setPath($sPath);
        $this->setContent($sContent);
        $this->setWitness($sWitness);
    }

    protected function encrypt($sPassphrase)
    {
        $hashed_password = crypt($sPassphrase, $this->systempassphrase);
        $sContent = openssl_encrypt($this->content, 'AES-128-CBC', $hashed_password, 0, $this->systempassphrase);
        $sWitness = openssl_encrypt($this->witness, 'AES-128-CBC', $hashed_password, 0, $this->systempassphrase);

        $this->setContent($sContent);
        $this->setWitness($sWitness);
    }

    protected function decrypt($sPassphrase)
    {
        $hashed_password = crypt($sPassphrase, $this->systempassphrase);
        $sContent = openssl_decrypt($this->content, 'AES-128-CBC', $hashed_password, 0, $this->systempassphrase);
        $sWitness = openssl_decrypt($this->witness, 'AES-128-CBC', $hashed_password, 0, $this->systempassphrase);

        $this->setContent($sContent);
        $this->setWitness($sWitness);
    }
}