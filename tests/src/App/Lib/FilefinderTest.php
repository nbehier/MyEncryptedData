<?php

namespace App\Lib;

use App\Lib\FileFinder;

class FileFinderTest extends \PHPUnit_Framework_TestCase
{
    protected $fixturesPath = __DIR__ . '/Fixtures';

    public function testListFile()
    {
        $aFiles = FileFinder::listFiles($this->fixturesPath);

        $this->assertEquals(true, empty($aFiles));
    }
/*
    public function testGetFile()
    {

    }

    public function testSaveFile()
    {

    }

    public function testGetNewId()
    {

    }

    public function testIsFileExist()
    {

    }
*/
}