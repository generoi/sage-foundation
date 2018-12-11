<?php

namespace Genero\Sage\Foundation;

use Adbar\Dot;

class ScssFile
{
    public $path;
    public $rootDir;
    public $css;
    protected $scss;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function setRootDir($rootDir) {
        $this->rootDir = $rootDir;
        return $this;
    }

    public function parse()
    {
        $this->scss = new ScssCompiler();

        if ($this->rootDir) {
            $this->scss->setImportPaths($this->rootDir);
        }
        $this->css = $this->scss->compile('@import "' . $this->path . '";');
        return $this;
    }

    public function get($name, $shouldThrow = true)
    {
        if (strpos($name, '.') !== false) {
            list($prefix, $path) = explode('.', $name, 2);
            $value = $this->scss->getVariable($prefix, $shouldThrow);
            return (new Dot($value))->get($path);
        }
        return $this->scss->getVariable($name, $shouldThrow);
    }
}
