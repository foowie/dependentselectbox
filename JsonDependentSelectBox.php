<?php

/**
 * @author Daniel Robenek
 * @license MIT
 */

namespace DependentSelectBox;

use Nette\Application\Responses\JsonResponse;
use Nette\Forms\Container as FormContainer;
use Nette\Application\UI\Presenter;
use Nette\InvalidStateException;

// \Nette\Forms\Container::extensionMethod("addJsonDependentSelectBox", "DependentSelectBox\JsonDependentSelectBox::formAddJsonDependentSelectBox");

class JsonDependentSelectBox extends DependentSelectBox
{

	public static $jsonResoponseItems = array();

	public function submitButtonHandler($button) {
		parent::submitButtonHandler($button);
		if ($this->lookup("\Nette\Application\UI\Presenter")->isAjax())
			$this->addJsonResponseItem($this);
	}

	protected function addJsonResponseItem($selectBox) {
		self::$jsonResoponseItems[] = $selectBox;
		if($selectBox instanceof DependentSelectBox)
			foreach($selectBox->childs as $child)
				$child->addJsonResponseItem($child);
	}

	public static function tryJsonResponse(Presenter $presenter) {
		if(empty(self::$jsonResoponseItems))
			return;
		
		$payload = array(
			"type" => "JsonDependentSelectBoxResponse",
			"items" => array()
		);
		foreach(self::$jsonResoponseItems as $item) {
			$payload["items"][$item->getHtmlId()] = array(
				"selected" => $item->getValue(),
				"items" => $item->getItems()
			);
		}
		$response = new JsonResponse($payload);
		$presenter->sendResponse($response);
	}


	/**
	 * @deprecated Alias for Container_prototype_addDependentSelectBox
	 */
	public static function formAddJsonDependentSelectBox($_this, $name, $label, $parents, $dataCallback) {
		return self::Container_prototype_addJsonDependentSelectBox($_this, $name, $label, $parents, $dataCallback);
	}

	public static function Container_prototype_addJsonDependentSelectBox(FormContainer $obj, $name, $label, $parents, $dataCallback) {
		return $obj[$name] = new JsonDependentSelectBox($label, $parents, $dataCallback);
	}

	public static function register($methodName = "addJsonDependentSelectBox") {
		if(NETTE_PACKAGE == 'PHP 5.2')
			FormContainer::extensionMethod("FormContainer::$methodName", array("JsonDependentSelectBox", "Container_prototype_addJsonDependentSelectBox"));
		else
			FormContainer::extensionMethod($methodName, "DependentSelectBox\JsonDependentSelectBox::Container_prototype_addJsonDependentSelectBox");
	}
	
}