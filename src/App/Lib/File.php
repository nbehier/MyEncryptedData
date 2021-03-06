<?php

namespace App\Lib;

use App\Lib\FileFinder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Exception\DumpException;

abstract class File
{
    protected $id;
    protected $title;
    protected $desc;
    protected $authors;
    protected $content;
    protected $created_at;
    protected $updated_at;
    protected $path;

    public function __construct(array $properties = null)
    {
        if (is_array($properties) ) {
            if (array_key_exists('id', $properties) ) { $this->setId($properties['id'] ); }
            if (array_key_exists('title', $properties) ) { $this->setTitle($properties['title'] ); }
            if (array_key_exists('desc', $properties) ) { $this->setDesc($properties['desc'] ); }
            if (array_key_exists('authors', $properties) ) { $this->setAuthors($properties['authors'] ); }
            if (array_key_exists('content', $properties) ) { $this->setContent($properties['content'] ); }
            if (array_key_exists('created_at', $properties) ) { $this->setCreatedAt($properties['created_at'] ); }
            if (array_key_exists('updated_at', $properties) ) { $this->setUpdatedAt($properties['updated_at'] ); }
            if (array_key_exists('path', $properties) ) { $this->setPath($properties['path'] ); }
        }
    }

    public function load($sYmlContent)
    {
        $oYaml = array();

        try {
            $oYaml = Yaml::parse($sYmlContent);

            $this->setId($oYaml['id']);
            $this->setTitle($oYaml['title']);
            $this->setDesc($oYaml['desc']);
            $this->setAuthors($oYaml['authors']);
            $this->setContent($oYaml['content']);
            $this->setCreatedAt($oYaml['created_at']);
            $this->setUpdatedAt($oYaml['updated_at']);
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
        }

        return $oYaml;
    }

    /**
     * Dump encrypted file with user passphrase and system passphrase
     */
    public function dump($bForce = false)
    {
        if (! $bForce) {
            // Vérifier si nouveau fichier, dans ce cas, définir l'id et created_at
            $iId = $this->isNew();
            if ($iId !== false) {
                $this->setId($iId);
                $this->setCreatedAt();
            }
            else {
                $oFile = FileFinder::getFile($this->path, $this->id, '', '');
                $aFile = $oFile->toArray();
                $this->setCreatedAt($aFile['created_at']);
            }

            $this->setUpdatedAt();
        }

        $aArray = $this->toArray(true);

        try {
            $yaml = Yaml::dump($aArray, 2);

            file_put_contents($this->path . '/' . $this->id . '.yml', $yaml);
        } catch (DumpException $e) {
            printf("Unable to dump the YAML string: %s", $e->getMessage());
        }
    }

    public function toArray($withPrivateFields = false)
    {
        return array(
            'id' => $this->id,
            'title' => $this->title,
            'desc' => $this->desc,
            'authors' => $this->authors,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        );
    }

    public function setPath($path)
    {
        if (is_string($path) ) { $this->path = $path; }
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        if (is_string($content) ) { $this->content = $content; }
    }

    /**
     * Search current file, if does not already exist, get id
     */
    protected function isNew()
    {
        if (   ! empty($this->id)
            && FileFinder::isFileExist($this->path, $this->id) ) {
            return false;
        }

        return FileFinder::getNewId($this->path);
    }

    protected function setId($id)
    {
        if (is_numeric($id) ) { $this->id = $id; }
    }

    protected function setTitle($title)
    {
        if (is_string($title) ) { $this->title = $title; }
    }

    protected function setDesc($desc)
    {
        if (is_string($desc) ) { $this->desc = $desc; }
    }

    protected function setAuthors($authors)
    {
        if (is_array($authors) ) { $this->authors = $authors; }
        else {
            $aAuthors = explode(',', $authors);
            $this->authors = array_map('trim', $aAuthors);
        }
    }

    protected function setCreatedAt($created_at = '')
    {
        if (is_string($created_at) && !empty($created_at) ) { $this->created_at = $created_at; }
        else { $this->created_at = date("c"); }
    }

    protected function setUpdatedAt($updated_at = '')
    {
        if (is_string($updated_at) && !empty($updated_at) ) { $this->updated_at = $updated_at; }
        else { $this->updated_at = date("c"); }
    }
}