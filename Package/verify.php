<?php

require_once dirname(__FILE__) . '/abstract.php';

class PackageVerify extends PackageAbstract
{
    
    /** @var array */
    protected $differences = [];
    
    
    /**
     * @return array|bool
     */
    public function differences()
    {
        if ($this->differences) {
            return $this->differences;
        }
    
        $this->initFiles();
    
        $compiledFiles = (array) $this->decrypt($this->getCurrentHash());
    
        if (empty($compiledFiles)) {
            return false;
        }
    
        $this->differences = $this->compareFiles($this->files, $compiledFiles);
        
        return $this->differences;
    }
    
    
    /**
     * @return array|bool
     */
    public function verify()
    {
        $differences = $this->differences();
        
        if (count($differences[self::DIFF_NONEXISTENT])) {
            return false;
        }
        
        if (count($differences[self::DIFF_NEW])) {
            return false;
        }
        
        if (count($differences[self::DIFF_MODIFIED])) {
            return false;
        }
        
        return true;
    }
}

$verifier = new PackageVerify();

if (!$verifier->verify()) {
    return $verifier->differences();
}
