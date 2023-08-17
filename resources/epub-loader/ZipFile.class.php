<?php
/**
 * ZipFile class
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Didier Corbière <contact@atoll-digital-library.org>
 */

namespace Marsender\EPubLoader;

use Exception;
use ZipArchive;

/**
 * ZipFile class allows to open files inside a zip file with the standard php zip functions
 */
class ZipFile
{
    public const DOWNLOAD = 1;   // download (default)
    public const NOHEADER = 4;   // option to use with DOWNLOAD: no header is sent
    public const FILE = 8;       // output to file  , or add from file
    public const STRING = 32;    // output to string, or add from string
    public const MIME_TYPE = 'application/epub+zip';

    /** @var ZipArchive|null */
    private $mZip;
    /** @var array<string, mixed>|null */
    private $mEntries;
    /** @var string|null */
    private $mFileName;

    public function __construct()
    {
        $this->mZip = null;
        $this->mEntries = null;
        $this->mFileName = null;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->Close();
    }

    /**
     * Open a zip file and read it's entries
     *
     * @param string $inFileName
     * @param int|null $inFlags
     * @return boolean True if zip file has been correctly opended, else false
     */
    public function Open($inFileName, $inFlags = 0)  // ZipArchive::RDONLY)
    {
        $this->Close();

        $this->mZip = new ZipArchive();
        $result = $this->mZip->open($inFileName, $inFlags);
        if ($result !== true) {
            return false;
        }

        $this->mFileName = $inFileName;

        $this->mEntries = [];

        for ($i = 0; $i <  $this->mZip->numFiles; $i++) {
            //$fileName =  $this->mZip->getNameIndex($i);
            $entry =  $this->mZip->statIndex($i);
            $fileName = $entry['name'];
            $this->mEntries[$fileName] = $entry;
        }

        return true;
    }

    /**
     * Check if a file exist in the zip entries
     *
     * @param string $inFileName File to search
     *
     * @return boolean True if the file exist, else false
     */
    public function FileExists($inFileName)
    {
        if (!isset($this->mZip)) {
            return false;
        }

        if (!isset($this->mEntries[$inFileName])) {
            return false;
        }

        return true;
    }

    /**
     * Read the content of a file in the zip entries
     *
     * @param string $inFileName File to search
     *
     * @return mixed File content the file exist, else false
     */
    public function FileRead($inFileName)
    {
        if (!isset($this->mZip)) {
            return false;
        }

        if (!isset($this->mEntries[$inFileName])) {
            return false;
        }

        //$entry = $this->mEntries[$inFileName];
        $data = $this->mZip->getFromName($inFileName);

        return $data;
    }

    /**
     * Get a file handler to a file in the zip entries (read-only)
     *
     * @param string $inFileName File to search
     *
     * @return resource|bool File handler if the file exist, else false
     */
    public function FileStream($inFileName)
    {
        if (!isset($this->mZip)) {
            return false;
        }

        if (!isset($this->mEntries[$inFileName])) {
            return false;
        }

        return $this->mZip->getStream($inFileName);
    }

    /**
     * Summary of FileAdd
     * @param string $Name
     * @param mixed $Data
     * @return bool
     */
    public function FileAdd($Name, $Data)
    {
        if (!isset($this->mZip)) {
            return false;
        }

        if (!$this->mZip->addFromString($Name, $Data)) {
            return false;
        }
        $this->mEntries[$Name] = $this->mZip->statName($Name);
        return true;
    }

    /**
     * Summary of FileAddPath
     * @param string $Name
     * @param string $Path
     * @return mixed
     */
    public function FileAddPath($Name, $Path)
    {
        if (!isset($this->mZip)) {
            return false;
        }

        if (!$this->mZip->addFile($Path, $Name)) {
            return false;
        }
        $this->mEntries[$Name] = $this->mZip->statName($Name);
        return true;
    }

    /**
     * Summary of FileDelete
     * @param string $Name
     * @return bool
     */
    public function FileDelete($Name)
    {
        return $this->FileReplace($Name, false);
    }

    /**
     * Replace the content of a file in the zip entries
     *
     * @param string $inFileName File with content to replace
     * @param string|bool $inData Data content to replace, or false to delete
     * @return bool
     */
    public function FileReplace($inFileName, $inData)
    {
        if (!isset($this->mZip)) {
            return false;
        }

        if ($inData === false) {
            if ($this->FileExists($inFileName)) {
                if (!$this->mZip->deleteName($inFileName)) {
                    return false;
                }
                unset($this->mEntries[$inFileName]);
            }
            return true;
        }

        if (!$this->mZip->addFromString($inFileName, $inData)) {
            return false;
        }
        $this->mEntries[$inFileName] = $this->mZip->statName($inFileName);
        return true;
    }

    /**
     * Summary of FileCancelModif
     * @param string $NameOrIdx
     * @param bool $ReplacedAndDeleted
     * @return int
     */
    public function FileCancelModif($NameOrIdx, $ReplacedAndDeleted=true)
    {
        // cancel added, modified or deleted modifications on a file in the archive
        // return the number of cancels

        $nbr = 0;

        if (!$this->mZip->unchangeName($NameOrIdx)) {
            return $nbr;
        }
        $nbr += 1;

        return $nbr;
    }

    /**
     * Close the zip file
     *
     * @return void
     */
    public function Close()
    {
        if (!isset($this->mZip)) {
            return;
        }

        $this->mZip->close();
        $this->mZip = null;
    }

    /**
     * Summary of Flush
     * @param mixed $Render
     * @param mixed $File
     * @param mixed $ContentType
     * @return never
     */
    public function Flush($Render=self::DOWNLOAD, $File='', $ContentType='')
    {
        // we need to close the zip file to save all changes here - probably not what you wanted :-()
        $this->Close();

        $File = $File ?: $this->mFileName;

        $expires = 60*60*24*14;
        header('Pragma: public');
        header('Cache-Control: max-age=' . $expires);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

        header('Content-Type: ' . self::MIME_TYPE);
        header('Content-Disposition: attachment; filename="' . basename($File) . '"');

        $FilePath = realpath($this->mFileName);
        // see fetch.php for use of Config::get('x_accel_redirect')
        header('Content-Length: ' . filesize($FilePath));
        readfile($FilePath);
        //header(Config::get('x_accel_redirect') . ': ' . $FilePath);

        exit;
    }
}
