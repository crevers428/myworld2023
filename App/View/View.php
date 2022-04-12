<?php

namespace App\View;

use App\Application;
use App\Lib\Parsedown;

class Brace
{
    const VARIABLE = 0;
    const FOR_CTRL = 1;
    const END_FOR = 2;
    const IF_CTRL = 3;
    const ELSE_CTRL = 4;
    const END_IF = 5;

    const COMPARISON_EQUAL = 0;
    const COMPARISON_GREATER = 1;
    const COMPARISON_LESS = 2;
    const COMPARISON_GREATER_OR_EQUAL = 3;
    const COMPARISON_LESS_OR_EQUAL = 4;
    const COMPARISON_DISTINCT = 5;

    public $opening;
    public $closing;
    public $type;
    public $label;
    public $suffix;
    public $level;
    public $comparison;
    public $comparisonLabel;

    public function __construct($opening,$closing,$type,$label,$suffix,$comparison,$comparisonLabel,$level)
    {
        $this->opening = $opening;
        $this->closing = $closing;
        $this->type = $type;
        $this->label = $label;
        $this->suffix = $suffix;
        $this->comparison = $comparison;
        $this->comparisonLabel = $comparisonLabel;
        $this->level = $level;
    }
}

class View
{
    protected $app;
    protected $content = '';

    public function __construct($app)
    {
        $this->app = $app;
        $this->content = '{{view|raw}}';
    }

    /*
    public static function __embed(&$st,$vars = null)
    {
        foreach ($vars as $key => $value) {
            $suffix = null;
            if (($p = strpos($key,'|')) !== false) {
                $suffix = substr($key,$p+1);
                $key = substr($key,0,$p);
            }
            switch ($suffix) {
                case 'raw':
                    break;
                default:
                    $value = htmlspecialchars($value);
            }
            $st = str_replace(sprintf('{{%s}}',$key),$value,$st);
        }
    }
    */

    protected static function findAllOccurrences($haystack,$needle)
    {
        $occurrences = array();
        $offset = 0;
        while (($p = strpos($haystack,$needle,$offset))!==false) {
            $occurrences[] = $p;
            $offset = $p + strlen($needle);
        }
        return $occurrences;
    }

    protected static function evaluableVar($label,&$vars)
    {
        return (is_array($vars) && array_key_exists($label, $vars)) ||
            $label == 'for_index' || $label == 'auth_role' || $label == 'auth_username' || $label == 'null';
    }

    /*
    protected static function displaceBraces(&$braces,$from,$displacement)
    {
        while ($from < count($braces)) {
            $braces[$from]->opening += $displacement;
            $braces[$from]->closing += $displacement;
            $from++;
        }
    }
    */

    protected static function nextBraceOfLevel(&$braces,$from,$level)
    {
        while ($from < count($braces) && $braces[$from]->level > $level)
            $from++;
        if ($from == count($braces))
            die('VIEW: closing control not found');
        return $from;
    }

    /*
    protected static function extractBraces(&$braces,$from,$to,$offset)
    {
        $subBraces = array_slice($braces,$from,$to-$from+1);
        static::displaceBraces($subBraces,0,-$offset);
        return $subBraces;
    }
    */

    protected static function getVarValue(Application $app,&$vars,$label,$suffix, $for_index)
    {
        switch($label) {
            case 'link': // todo - debería ser sys_link o algo más reservado
                return $app->getWebUri('/');
            case 'file': // todo - debería ser sys_file o algo más reservado
                return $app->getFileUri();
            case 'sys_date':
                return date('Y-m-d');
            case 'for_index':
                return $for_index;
            case 'auth_role':
                return $app->getAuthRole();
            case 'auth_username':
                return htmlspecialchars($app->getAuthUsername());
            case 'null':
                return null;
            default:
                if ($vars and array_key_exists($label, $vars)) {
                    $value = $vars[$label];
                    if (is_string($value)) {
                        switch ($suffix) {
                            case 'raw':
                                break;
                            case 'md':
                                $parsedown = new Parsedown();
                                $value = $parsedown->text($value);
                                break;
                            default:
                                $value = htmlspecialchars($value);
                        }
                    }
                } else {
                    $value = null;
                }
                return $value;
        }
    }

    protected static function embedFragment(Application $app, $st,&$braces,$fromBrace,$toBrace,$displacement,&$vars, $for_index = null)
    {
        //echo "<p>Entro con LEVEL = {$braces[$fromBrace]->level} y ".count($braces)." BRACES</p></p>$st</p>";
        while ($fromBrace <= $toBrace) {
            unset($value); // un-assign any previous value!
            $brace = $braces[$fromBrace];
            switch($brace->type) {
                case Brace::VARIABLE:
                    $value = static::getVarValue($app,$vars,$brace->label,$brace->suffix, $for_index);
                    if ($value !== null) {
                        $lengthToDelete = $brace->closing - $brace->opening + 2;
                        $st = substr_replace($st,$value,$brace->opening + $displacement, $lengthToDelete);
                        //static::displaceBraces($braces, $fromBrace+1, strlen($value) - $lengthToDelete);
                        $displacement += strlen($value) - $lengthToDelete;
                    }
                    break;
                case Brace::FOR_CTRL:
                    //echo "<p>FOR = $fromBrace, LEVEL = {$brace->level}</p>";
                    if (!array_key_exists($brace->label, $vars)) {
                        die("VIEW: '".htmlspecialchars($brace->label)."' control var is not provided");
                    } else {
                        if (!is_array($vars[$brace->label]))
                            die('VIEW: control var for FOR must be an array');
                        $endFor = static::nextBraceOfLevel($braces,$fromBrace+1,$brace->level);
                        $fragment = substr($st,$brace->closing+2+$displacement,$braces[$endFor]->opening-$brace->closing-2);
                        $newSegment = '';
                        $for_index = 0;
                        foreach($vars[$brace->label] as &$value) {
                            $newSegment .= static::embedFragment(
                                $app,
                                $fragment,
                                $braces,
                                $fromBrace+1,
                                $endFor-1,
                                -($brace->closing+2),
                                $value,
                                $for_index
                            );
                            $for_index++;
                        }
                        $lengthToDelete = $braces[$endFor]->closing - $brace->opening + 2;
                        $st = substr_replace($st,$newSegment,$brace->opening + $displacement, $lengthToDelete);
                        $displacement += strlen($newSegment) - $lengthToDelete;
                        $fromBrace = $endFor;
                        //echo "<p>ENDFOR = $endFor</p>";
                    }
                    break;
                case Brace::IF_CTRL:
                    if (!static::evaluableVar($brace->label, $vars)) {
                        die("VIEW: '".htmlspecialchars($brace->label)."' control var is not provided");
                    } else {
                        $endIf = static::nextBraceOfLevel($braces,$fromBrace+1,$brace->level);
                        if($braces[$endIf]->type == Brace::ELSE_CTRL) {
                            $else = $endIf;
                            $endIf = static::nextBraceOfLevel($braces,$endIf+1,$brace->level);
                        } else {
                            $else = null;
                        }
                        if ($brace->comparison===null) {
                            $value = static::getVarValue($app,$vars,$brace->label,$brace->suffix,$for_index);
                            $evaluation = is_array($value) ? count($value) : $value;
                        } else {
                            $value1 = static::getVarValue($app,$vars,$brace->label,'raw',$for_index);
                            $value2 = static::getVarValue($app,$vars,$brace->comparisonLabel,'raw',$for_index);
                            if ($value2===null && $brace->comparisonLabel != 'null') {
                                $value2 = $brace->comparisonLabel;
                            }
                            //echo "<p>[$value1][$value2]</p>";
                            switch($brace->comparison) {
                                case Brace::COMPARISON_EQUAL:
                                    $evaluation = ($value1 == $value2);
                                    break;
                                case Brace::COMPARISON_GREATER:
                                    $evaluation = ($value1 > $value2);
                                    break;
                                case Brace::COMPARISON_GREATER_OR_EQUAL:
                                    $evaluation = ($value1 >= $value2);
                                    break;
                                case Brace::COMPARISON_LESS:
                                    $evaluation = ($value1 < $value2);
                                    break;
                                case Brace::COMPARISON_LESS_OR_EQUAL:
                                    $evaluation = ($value1 <= $value2);
                                    break;
                                default:
                                    $evaluation = ($value1 != $value2);
                                    break;
                            }
                            /*
                            if($brace->label=='auth_role') {
                                echo "<p>[{$brace->label}=$value1][{$brace->comparisonLabel}=$value2][{$brace->comparison}][$evaluation]</p>";
                                echo '<p>{'.null.'}</p>';
                            }
                            */
                        }
                        if($evaluation || $else) {
                            $segmentStart = $evaluation ? $fromBrace : $else;
                            $segmentEnd = $evaluation ? ($else ? $else : $endIf) : $endIf;
                            $fragment = substr($st,$braces[$segmentStart]->closing+2+$displacement,$braces[$segmentEnd]->opening-$braces[$segmentStart]->closing-2);
                            $newSegment = static::embedFragment(
                                $app,
                                $fragment,
                                $braces,
                                $segmentStart+1,
                                $segmentEnd-1,
                                -($braces[$segmentStart]->closing+2),
                                $vars
                            );
                        } else {
                            $newSegment = '';
                        }
                        $lengthToDelete = $braces[$endIf]->closing - $brace->opening + 2;
                        $st = substr_replace($st,$newSegment,$brace->opening + $displacement, $lengthToDelete);
                        $displacement += strlen($newSegment) - $lengthToDelete;
                        $fromBrace = $endIf;
                    }
                    break;
                default:
                    die("VIEW: unexpected command ({$brace->type} in brace #$fromBrace, LEVEL = {$brace->level})");
            }
            $fromBrace++;
        }
        return $st;
    }

    public static function __embed(Application $app, &$st,$vars = null)
    {
        $openings = static::findAllOccurrences($st,'{{');
        $closings = static::findAllOccurrences($st,'}}');
        $count = count($openings);
        if (!$app->isProd()) { // do not check in production
            // double-braces
            if ($count != count($closings))
                die('VIEW: Number of opening double-braces and closing double-braces do not match');
            $index = 0;
            $offset = -1;
            while ($index < $count) {
                if ($openings[$index] <= $offset || $closings[$index] <= $openings[$index])
                    die ('VIEW: Nested double-braces are not allowed');
                $offset = $closings[$index];
                $index++;
            }
            // no more suffixed vars
            if ($vars) {
                $suffixed = array();
                foreach ($vars as $key => $var) {
                    if (strpos($key,'|')) $suffixed[] = $key;
                }
                if (count($suffixed))
                    die('VIEW: suffixed vars are not allowed anymore. You provided the following suffixed vars:<br>'.
                        implode('<br>',$suffixed)
                    );
            }
        }
        $braces = array();
        $currentLevel = 0;
        $index = 0;
        while ($index < $count) {
            $label = substr($st,$openings[$index]+2,$closings[$index]-$openings[$index]-2);
            $suffix = null;
            $comparison = null;
            $comparisonLabel = null;
            $coincidences = preg_match_all('/^.+\((.*)\)$/',$label,$matches);
            if (!$coincidences) {
                // variables
                if (($p = strpos($label,'|')) !== false) {
                    $suffix = substr($label,$p+1);
                    $label = substr($label,0,$p);
                }
                $type = Brace::VARIABLE;
            } else {
                // control
                if (preg_match('/^FOR\(.*\)$/',$label)) {
                    $type = Brace::FOR_CTRL;
                } elseif (preg_match('/^ENDFOR\(\)$/',$label)) {
                    $type = Brace::END_FOR;
                    $currentLevel--;
                } elseif (preg_match('/^IF\(.*\)$/',$label)) {
                    $type = Brace::IF_CTRL;
                } elseif (preg_match('/^ELSE\(.*\)$/',$label)) {
                    $type = Brace::ELSE_CTRL;
                    $currentLevel--;
                } elseif (preg_match('/^ENDIF\(\)$/',$label)) {
                    $type = Brace::END_IF;
                    $currentLevel--;
                } else {
                    die("VIEW: unrecognized command ($label)");
                }
                $label = $matches[1][0];
                if ($type == Brace::IF_CTRL && preg_match('/^.+(=|<>|<|>|<=|>=).+$/',$label)) {
                    if (preg_match('/^[^=<>]+=[^=<>]+$/',$label)) {
                        $comparison = Brace::COMPARISON_EQUAL;
                    } elseif (preg_match('/^[^=<>]+>[^=<>]+$/',$label)) {
                        $comparison = Brace::COMPARISON_GREATER;
                    } elseif (preg_match('/^[^=<>]+<[^=<>]+$/',$label)) {
                        $comparison = Brace::COMPARISON_LESS;
                    } elseif (preg_match('/^[^=<>]+<=[^=<>]+$/',$label)) {
                        $comparison = Brace::COMPARISON_LESS_OR_EQUAL;
                    } elseif (preg_match('/^[^=<>]+>=[^=<>]+$/',$label)) {
                        $comparison = Brace::COMPARISON_GREATER_OR_EQUAL;
                    } else {
                        $comparison = Brace::COMPARISON_DISTINCT;
                    }
                    $split = preg_split('/(=|<>|<|>|<=|>=)/',$label);
                    $label = $split[0];
                    $comparisonLabel = $split[1];
                }
            }
            $braces[] = new Brace($openings[$index],$closings[$index],$type,$label,$suffix,$comparison,$comparisonLabel,$currentLevel);
            if ($type == Brace::FOR_CTRL || $type == Brace::IF_CTRL || $type == Brace::ELSE_CTRL) {
                $currentLevel++;
            }
            $index++;
        }
        // if (count($braces)>20) {print_r($braces); die();}
        $st = static::embedFragment($app, $st,$braces,0,count($braces)-1,0,$vars);
    }

    public function embed($vars = null)
    {
        static::__embed($this->app, $this->content,$vars);
        return $this;
    }

    public static function __terminate(&$st)
    {
        $st = preg_replace('/\{\{.*\}\}/','',$st);
    }

    public function terminate()
    {
        static::__terminate($this->content);
        return $this->content;
    }

    public function renderView($vars = null)
    {
        return $this->embed($vars)->terminate();
    }

    public function render($vars = null)
    {
        die($this->renderView($vars));
    }
}