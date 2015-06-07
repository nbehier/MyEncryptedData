<?php

namespace App\Lib;

use App\Lib\File;


class EncryptFile extends File
{
    protected $passphrase;
    protected $systempassphrase;
    protected $systempath;

    public function __construct(array $properties = null, $passphrase = '', $systempassphrase = '', $systempath = '')
    {
        parent::__construct($properties);
        $this->passphrase = $passphrase;
        $this->systempassphrase = $systempassphrase;
        $this->systempath = $systempath;
    }

    /**
     * Load file ; if passphrase given, decrypt content
     */
    public function load($sYmlContent)
    {
        parent::load($sYmlContent);

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
        $this->encrypt($this->passphrase);
        parent::dump($bForce);

        // Revert original file
        $this->setContent($sContent);

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

    protected function dumpSystem()
    {
        $sId = $this->id;
        $sPath = $this->path;
        $sContent = $this->content;

        $this->id = $this->id . '-' . date('YmdHis');
        $this->path = $this->systempath;

        $this->encrypt($this->systempassphrase);
        parent::dump(true);

        // Revert original file
        $this->setId($sId);
        $this->setPath($sPath);
        $this->setContent($sContent);
    }

    protected function encrypt($sPassphrase)
    {
        $hashed_password = crypt($sPassphrase, $this->systempassphrase);
        $sContent = openssl_encrypt($this->content, 'AES-128-CBC', $hashed_password, 0, $this->systempassphrase);

        $this->setContent($sContent);
    }

    protected function decrypt($sPassphrase)
    {
        $hashed_password = crypt($sPassphrase, $this->systempassphrase);
        $sContent = openssl_decrypt($this->content, 'AES-128-CBC', $hashed_password, 0, $this->systempassphrase);

        $this->setContent($sContent);
    }
}