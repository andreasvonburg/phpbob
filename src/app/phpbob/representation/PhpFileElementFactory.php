<?php
namespace phpbob\representation;

use n2n\util\ex\IllegalStateException;
use phpbob\representation\ex\UnknownElementException;
use n2n\util\StringUtils;

class PhpFileElementFactory {
	const FUNCTION_PREFIX = 'func-';
	const CONST_PREFIX = 'const-';
	const TYPE_PREFIX = 'type-';
	
	private $phpFile;
	private $phpNamespace;
	private $namespacesOnly = false;
	private $phpFileElements = array();
	
	public function __construct(PhpFile $phpFile, PhpNamespace $phpNamespace = null) {
		$this->phpFile = $phpFile;
		$this->phpNamespace = $phpNamespace;
	}
	
	public function hasNamespaces() {
		return $this->namespacesOnly;
	}
	
	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasPhpNamespace(string $name) {
		return $this->namespacesOnly && isset($this->phpFileElements[$name]);
	}
	
	/**
	 * @param string $name
	 * @return PhpFunction
	 */
	public function getPhpNamespace(string $name) {
		if (!$this->namespacesOnly || !isset($this->phpFileElements[$key])) {
			throw new UnknownElementException('No function with name "' . $name . '" given.');
		}
		
		return $this->phpFileElements[$name];
	}
	
	/**
	 * @return PhpFunction []
	 */
	public function getPhpNameSpaces() {
		if (!$this->namespacesOnly) return [];
		
		return $this->phpFileElements;
	}
	
	/**
	 * @param string $name
	 * @param PhpTypeDef $returnPhpTypeDef
	 * @throws IllegalStateException
	 * @return \phpbob\representation\PhpFunction
	 */
	public function createPhpNamespace(string $name) {
		if (null !== $this->phpNamespace) {
			throw new IllegalStateException('Nested namespaces are not allowed');
		}
		
		if (!$this->namespacesOnly && !empty($this->phpFileElements)) {
			throw new IllegalStateException('Namespace must be the first element in a php file');
		}
		
// 		$phpNamespace = new PhpNamespace();
		
// 		$phpFunction = new PhpFunction($this->phpFile, $name, $this->phpNamespace);
// 		$phpFunction->setReturnPhpTypeDef($returnPhpTypeDef);
		
// 		$that = $this;
// 		$phpFunction->onNameChange(function($oldName, $newName) use ($that) {
// 			$that->checkPhpFunctionName($newName);
// 			$that->changePhpFileElementsKey($that->buildFunctionKey($oldName),
// 					$that->buildFunctionKey($newName));
// 		});
			
// 			$this->phpFileElemets[$this->buildFunctionKey($name)] = $phpFunction;
// 			return $phpFunction;
	}
	
	/**
	 * @param string $name
	 */
	public function removePhpFunction(string $name) {
		unset($this->phpFileElements[$this->buildFunctionKey($name)]);
	}
	
	private function checkPhpFunctionName(string $name) {
		if ($this->hasPhpFunction($name)) {
			throw new IllegalStateException('Function with name ' . $name . ' already defined.');
		}
	}
	
	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasPhpFunction(string $name) {
		return isset($this->phpFileElements[$this->buildFunctionKey($name)]);
	}
	
	/**
	 * @param string $name
	 * @return PhpFunction
	 */
	public function getPhpFunction(string $name) {
		$key = $this->buildFunctionKey($name);
		if (!isset($this->phpFileElements[$key])) {
			throw new UnknownElementException('No function with name "' . $name . '" given.');
		}
		
		return $this->phpFileElements[$key];
	}
	
	/**
	 * @return PhpFunction []
	 */
	public function getPhpFunctions() {
		return $this->getElementsWithPrefix(self::FUNCTION_PREFIX);
	}
	
	/**
	 * @param string $name
	 * @param PhpTypeDef $returnPhpTypeDef
	 * @throws IllegalStateException
	 * @return \phpbob\representation\PhpFunction
	 */
	public function createPhpFunction(string $name, PhpTypeDef $returnPhpTypeDef = null) {
		$this->checkNamespaceOnly();
		$this->checkPhpFunctionName($name);
		
		$phpFunction = new PhpFunction($this->phpFile, $name, $this->phpNamespace);
		$phpFunction->setReturnPhpTypeDef($returnPhpTypeDef);
		
		$that = $this;
		$phpFunction->onNameChange(function($oldName, $newName) use ($that) {
			$that->checkPhpFunctionName($newName);
			$that->changePhpFileElementsKey($that->buildFunctionKey($oldName), 
					$that->buildFunctionKey($newName));
		});
		
		$this->phpFileElemets[$this->buildFunctionKey($name)] = $phpFunction;
		return $phpFunction;
	}
	
	/**
	 * @param string $name
	 */
	public function removePhpFunction(string $name) {
		unset($this->phpFileElements[$this->buildFunctionKey($name)]);
	}
	
	private function checkPhpFunctionName(string $name) {
		if ($this->hasPhpFunction($name)) {
			throw new IllegalStateException('Function with name ' . $name . ' already defined.');
		}
	}
	
	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasPhpConst(string $name) {
		return isset($this->phpFileElements[$this->buildConstKey($name)]);
	}
	
	/**
	 * @param string $name
	 * @throws UnknownElementException
	 * @return PhpConst
	 */
	public function getPhpConst(string $name) {
		$key = $this->buildConstKey($name);
		if (!isset($this->phpFileElements[$key])) {
			throw new UnknownElementException('No const with name "' . $name . '" given.');
		}
		
		return $this->phpFileElements[$key];
	}
	
	/**
	 * @return PhpConst []
	 */
	public function getPhpConsts() {
		return $this->getElementsWithPrefix(self::CONST_PREFIX);
	}
	
	/**
	 * @param string $name
	 * @param string $value
	 * @throws IllegalStateException
	 * @return \phpbob\representation\PhpConst
	 */
	public function createPhpConst(string $name, string $value) {
		$this->checkNamespaceOnly();
		$this->checkPhpConstName($name);
		
		$phpConst = new PhpConst($this, $name, $value);
		
		$that = $this;
		$phpConst->onNameChange(function($oldName, $newName) use ($that) {
			$that->checkPhpConstName($newName);
			$that->changePhpFileElementsKey($that->buildConstKey($oldName),
					$that->buildConstKey($newName));
		});
		
		$this->phpFileElements[$this->buildConstKey($name)] = $phpConst;
		
		return $phpConst;
	}
	
	/**
	 * @param string $name
	 * @throws IllegalStateException
	 */
	private function checkPhpConstName(string $name) {
		if ($this->hasPhpConst($name)) {
			throw new IllegalStateException('Const with name ' . $name . ' already defined.');
		}
	}
	
	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasPhpType(string $name) {
		return isset($this->phpFileElements[$this->buildTypeKey($name)]);
	}
	
	/**
	 * @param string $name
	 * @throws UnknownElementException
	 * @return PhpConst
	 */
	public function getPhpType(string $name) {
		$key = $this->buildTypeKey($name);
		if (!isset($this->phpFileElements[$key])) {
			throw new UnknownElementException('No type with name "' . $name . '" given.');
		}
		
		return $this->phpFileElements[$key];
	}
	
	/**
	 * @return PhpType[]
	 */
	public function getPhpTypes() {
		return $this->getElementsWithPrefix(self::TYPE_PREFIX);
	}
	
	/**
	 * @param string $name
	 * @throws IllegalStateException
	 * @return \phpbob\representation\PhpInterface
	 */
	public function createPhpInterface(string $name) {
		$this->checkNamespaceOnly();
		$this->checkPhpTypeName($name);
		
		$phpInterface = new PhpInterface($this->phpFile, $name, $this->phpNamespace);
		$this->applyPhpTypeOnNameChange($phpInterface);
		$this->phpFileElements[$this->buildTypeKey($name)] = $phpInterface;
		
		return $phpInterface;
	}
	
	/**
	 * @param string $name
	 * @throws IllegalStateException
	 * @return \phpbob\representation\PhpTrait
	 */
	public function createPhpTrait(string $name) {
		$this->checkNamespaceOnly();
		$this->checkPhpTypeName($name);
		
		$phpTrait = new PhpTrait($this->phpFile, $name, $this->phpNamespace);
		$this->applyPhpTypeOnNameChange($phpTrait);
		$this->phpFileElements[$this->buildTypeKey($name)] = $phpTrait;
		
		return $phpTrait;
	}
	
	public function createPhpClass(string $name) {
		$this->checkNamespaceOnly();
		$this->checkPhpTypeName($name);
		
		$phpTrait = new PhpClass($this->phpFile, $name, $this->phpNamespace);
		$this->applyPhpTypeOnNameChange($phpTrait);
		$this->phpFileElements[$this->buildTypeKey($name)] = $phpTrait;
		
		return $phpTrait;
	}
	
	public function removePhpType(string $name) {
		unset($this->phpFileElements[$this->buildTypeKey($name)]);
		
		return $this;
	}
	
	private function getElementsWithPrefix(string $prefix) {
		$phpFileElements = [];
		foreach ($this->phpFileElements as $key => $phpFileElement) {
			if (!StringUtils::startsWith($key, $prefix)) continue;
			$phpFileElements[] = $phpFileElement;
		}
			
		return $phpFileElements;
	}
	
	private function checkPhpTypeName(string $name) {
		if ($this->hasPhpType($name)) {
			throw new IllegalStateException('Type with name ' . $name . ' already defined.');
		}
	}
	
	private function applyPhpTypeOnNameChange(PhpType $phpType) {
		$that = $this;
		$phpType->onNameChange(function($oldName, $newName) use ($that) {
			$that->checkPhpTypeName($newName);
			$that->changePhpFileElementsKey($that->buildTypeKey($oldName),
					$that->buildTypeKey($newName));
		});
	}
	
	private function changePhpFileElementsKey(string $oldKey, string $newKey) {
		$tmpFileElement = $this->phpFileElements[$oldKey];
		unset($this->phpFileElements[$oldKey]);
		$this->phpFileElements[$newKey] = $tmpFileElement;
	}
	
	private function checkNamespaceOnly() {
		if (!$this->namespacesOnly) return;
		
		throw new IllegalStateException('Only namespaces are allowed in this php file.');
	}
	
	private function buildFunctionKey(string $name) {
		return self::FUNCTION_PREFIX . $name;
	}
	
	private function buildConstKey(string $name) {
		return self::CONST_PREFIX . $name;
	}
	
	private function buildTypeKey(string $name) {
		return self::TYPE_PREFIX . $name;
	}
}