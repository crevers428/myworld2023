<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 6:14 PM
 */
namespace App\Form\Input;

abstract class AbstractInput
{
    protected $name;
    protected $type;
    protected $id;
    protected $class;
    protected $classCol;
    protected $visible = true;

    protected $constraints = array();
    protected $events = array();

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     */
    public function setClassCol($classCol)
    {
        $this->classCol = $classCol;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getClassCol()
    {
        return $this->classCol;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        if (!$this->id) {
            return $this->getNameInForm();
        }
        return $this->id;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getRawName()
    {
        if (($a = strpos($this->name,'[')) !== null) {
            if (($b = strpos($this->name,']',$a)) !== null) {
                return substr($this->name,$a+1,$b-$a-1);
            }
        }
        return $this->name;
    }

    // Bootstrap name
    public function getNameInForm($affix = null)
    {
        return 'form_'.($affix?$affix.'_':'').$this->getRawname();
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    static public function renderAttr($attr,$pattern)
    {
        if ($attr) {
            return ' '.sprintf($pattern,$attr);
        }
    }

    public function addConstraint($constraint)
    {
        $this->constraints[] = $constraint;
        return $this;
    }

    public function addEvent($event)
    {
        $this->events[] = $event;
        return $this;
    }

    public function hide()
    {
        $this->visible = false;
        return $this;
    }

    public function isVisible()
    {
        return $this->visible;
    }
}