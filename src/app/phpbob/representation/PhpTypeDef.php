<?php
namespace phpbob\representation;

use n2n\util\StringUtils;
use phpbob\PhprepUtils;
use phpbob\Phpbob;

class PhpTypeDef {
	private $localName;
	private $typeName;
	private $typeNameChangeClosures = [];
	
	public function __construct(string $localName, string $typeName = null) {
		$this->changeName($localName, $typeName);
	}
	
	public function changeName(string $localName, string $typeName = null) {
		$this->valNameAssociationCorrect($localName, $typeName);
		
		$this->localName = $localName;
		if (null !== $this->typeName && $this->typeName !== $typeName) {
			$this->triggerTypeNameChange($this->typeName, $typeName);
		}
		
		$this->typeName = $typeName;
	}
	
	public function getLocalName() {
		return $this->localName;
	}

	public function getTypeName() {
		return $this->typeName;
	}
	
	public function hasTypeName() {
		return null !== $this->typeName;
	}
	
	public function onTypeNameChange(\Closure $typeNameChangeClosure) {
		$this->typeNameChangeClosures[] = $typeNameChangeClosure;
	}
	
	private function triggerTypeNameChange(string $oldTypeName = null, string $newTypeName = null) {
		foreach ($this->typeNameChangeClosures as $typeNameChangeClosure) {
			$typeNameChangeClosure($oldTypeName, $newTypeName);
		}
	}

	public function valNameAssociationCorrect(string $localName, string $typeName = null) {
		if (null === $typeName || $localName === $typeName) return;
		$localNameParts = PhprepUtils::extractTypeNames($localName);
		
		if (count($localNameParts) === 1) {
			if (StringUtils::endsWith($localName, $typeName)) return;
			
			throw new \InvalidArgumentException('Invalid local name ' . $localName . ' for typename ' . $typeName);
		}
		
		array_shift($localNameParts);
		
		if (StringUtils::endsWith(implode(Phpbob::NAMESPACE_SEPERATOR, $localNameParts), $typeName)) return;
		
		throw new \InvalidArgumentException('Invalid local name ' . $localName . ' for typename ' . $typeName);
	}
	
	public function determineAlias() {
		if (null === $this->typeName || $this->typeName === $this->localName) return null;
		$localNameParts = PhprepUtils::extractTypeNames($this->localName);
		if (count($localNameParts) === 1) return null;
		
		return array_shift($localNameParts);
	}
	
	public function __toString() {
		return $this->localName;
	}
}