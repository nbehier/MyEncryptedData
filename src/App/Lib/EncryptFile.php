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
    public function dump()
    {
        $sContent = $this->content;

        if (! empty($this->passphrase) ) {
            $this->encrypt($this->passphrase);
            parent::dump();
        }

        // Backup versioned filed with system passphrase
        $this->setContent($sContent);
        $this->dumpSystem();
    }

    protected function dumpSystem()
    {
        $this->id = $this->id . '-' . date('YmdHis');
        $this->path = $this->systempath;

        $this->decrypt($this->passphrase);
        $this->encrypt($this->systempassphrase);
        parent::dump();
    }

    protected function encrypt(string $sPassphrase)
    {
        // @todo Encrypt
        // openssl_encrypt($data, 'AES-128-CBC', $key, 0, 'fgrgfvcfghtfdrfg');
        $sContent = $this->content;

        $this->setContent($sContent);
    }

    protected function decrypt(string $sPassphrase)
    {
        // @todo Decrypt
        // openssl_decrypt($data, 'AES-128-CBC', $key, 0, 'fgrgfvcfghtfdrfg');
        $sContent = $this->content;

        $this->setContent($sContent);
    }
}