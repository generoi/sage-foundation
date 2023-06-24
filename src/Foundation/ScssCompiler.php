<?php

namespace Genero\Sage\Foundation;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Type;

class ScssCompiler extends Compiler
{
    public function getVariable($name, $shouldThrow = true)
    {
        $tree = $this->get($name, $shouldThrow, $this->rootEnv);

        $value = $this->reduce($tree);
        list($type) = $value;
        switch ($type) {
            case Type::T_MAP:
                $keys = $value[1];
                $values = $value[2];
                $filtered = [];
                for ($i = 0, $s = count($keys); $i < $s; $i++) {
                    $filtered[$this->compileValue($keys[$i])] = $this->compileValue($values[$i]);
                }
                $value = $filtered;
                break;
            default:
                $value = $this->compileValue($tree);
        }

        return $value;
    }
}
