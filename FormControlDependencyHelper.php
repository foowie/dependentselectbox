<?php

/**
 * @author Daniel Robenek
 * @license MIT
 */

namespace DependentSelectBox;

use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl as FormControl;
use Nette\Forms\Controls\SubmitButton;
use Nette\Object;
use \InvalidArgumentException;
use \InvalidStateException;

class FormControlDependencyHelper extends Object {

// <editor-fold defaultstate="collapsed" desc="variables">

	/** Dont use directly, button allready created and position was defined in other class */
	const POSITION_UNDEFINED = 0;
	/** Put button on current position */
	const POSITION_DEFAULT = 1;
	/** Put button before given control */
	const POSITION_BEFORE_CONTROL = 2;
	/** Put button after given control */
	const POSITION_AFTER_CONTROL = 3;

	/** @var string Suffix for button name and html class */
	public static $buttonSuffix = "_submit";
	/** @var POSITION_UNDEFINED|POSITION_DEFAULT|POSITION_BEFORE_CONTROL|POSITION_AFTER_CONTROL Default position for buttons  */
	public static $defaultButtonPosition = self::POSITION_AFTER_CONTROL;

	/** @var \Nette\Forms\FormControl */
	public $control;
	/** @var String Html class of control*/
	protected $controlClass;
	/** @var int Button-s position */
	protected $buttonPosition;
	/** @var SubmitButton Created SubmitButton */
	protected $button = null;
	/** @var String SubmitButton-s label */
	protected $buttonText = "Reload";

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="constructor">

	/**
	 *
	 * @param FormControl $control Component to attach button
	 * @param string $controlClass Html class for that component
	 */
	function __construct(FormControl $control, $controlClass = "dependentControl") {
		$this->control = $control;
		$this->controlClass = $controlClass;
		$this->buttonPosition = self::$defaultButtonPosition;
		if($this->control->lookup("Nette\Forms\Container", false) === null)
			throw new InvalidArgumentException("Components should be assigned to FormContainer !");
	}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="getters & setters & add & remove">

	/**
	 * Is there any button with same name ?
	 * @return boolean
	 */
	public function isAnyButtonAttached() {
		$form = $this->control->lookup("Nette\Forms\Container");
		$buttonName = $this->formatButtonName($this->control->getName());
		$button = $form->getComponent($buttonName, false);
		return $button !== null;
	}

	/**
	 * Return button with same name
	 * @param boolean $need Throw exception when not exist?
	 * @return SubmitButton
	 */
	public function getAnyAttachedButton($need = false) {
		$form = $this->control->lookup("Nette\Forms\Container");
		$buttonName = $this->formatButtonName($this->control->getName());
		$button = $form->getComponent($buttonName, $need);
		return $button;
	}

	/**
	 * Add callback which is called when linked button is submitted
	 * @param callback $callback
	 * 'public function methodName(SubmittButton $button)'
	 */
	public function addOnChangeCallback($callback) {
		$this->createButton();
		if(!is_callable($callback))
			throw new InvalidArgumentException("Not callable !");
		$this->button->onClick[] = $callback;
	}


	public function getControl() {
		return $this->control;
	}

	public function getButton($need = false) {
		if($need == true)
			$this->createButton();
		return $this->button;
	}

	public function getButtonText() {
		return $this->buttonText;
	}

	public function getButtonPosition() {
		return $this->buttonPosition;
	}


	public function setButtonText($buttonText) {
		$this->buttonText = $buttonText;
		return $this;
	}

	public function setButtonPosition($buttonPosition) {
		$this->buttonPosition = $buttonPosition;
		return $this;
	}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="tools & helpers">

	/**
	 * Create button if no button is attached to control
	 * @return Was button created or not?
	 */
	public function createButton() {
		if($this->button !== null)
			return false;
		if($this->isAnyButtonAttached()) {
			$this->button = $this->getAnyAttachedButton(true);
			$this->buttonText = $this->button->getLabel();
			$this->buttonPosition = self::POSITION_UNDEFINED;
			return false;
		}
		// add $this->controlClass to $this->control if not added yet
		$this->updateControlClass();

		// create button
		if($this->buttonText === null)
			throw new InvalidArgumentException("Null caption of button is crappy *** !!! (ajax not working properly)");
		$container = $this->control->lookup("Nette\Forms\Container");

		$this->button = new SubmitButton($this->buttonText);
		$this->button->setValidationScope(false);
		$this->button->getControlPrototype()->class($this->controlClass.self::$buttonSuffix);

		$buttonName = $this->formatButtonName($this->control->getName());
		switch($this->buttonPosition) {
			case(self::POSITION_DEFAULT):
				$container->addComponent($this->button, $buttonName);
				break;
			case(self::POSITION_BEFORE_CONTROL):
				$container->addComponent($this->button, $buttonName, $this->control->getName());
				break;
			case(self::POSITION_AFTER_CONTROL): { // Uhm :(
				$findName = $this->control->getName();
				$nextName = null;
				$found = false;
				$components = $container->getComponents(true);
				foreach($components as $name => $component) {
					if($found === true) {
						$nextName = $name;
						break;
					}
					if($name == $findName)
						$found = true;
				}
				if($nextName === null)
					$container->addComponent($this->button, $buttonName);
				else
					$container->addComponent($this->button, $buttonName, $nextName);
			} break;
			default:
				throw new InvalidArgumentException("Ivalid position value !");
		}
		return true;
	}

	/**
	 * Update html class of control
	 */
	protected function updateControlClass() {
		if($this->controlClass !== null) {
			$classes = explode(" ", $this->control->getControlPrototype()->class);
			if(!in_array($this->controlClass, $classes))
				$this->control->getControlPrototype()->class($this->controlClass);
		}
	}

	/**
	 * Format name of button
	 * @param string $componentName Name of component
	 * @return string
	 */
	protected function formatButtonName($componentName) {
		return $componentName.self::$buttonSuffix;
	}

// </editor-fold>

}
