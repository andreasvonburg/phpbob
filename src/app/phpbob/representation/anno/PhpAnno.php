<?php
namespace phpbob\representation\anno;

use phpbob\Phpbob;
use n2n\util\StringUtils;
use phpbob\representation\PhpAnno;
use phpbob\representation\PhpTypeDef;
use phpbob\representation\PhpAnnoCollection;

class PhpAnno {
	private $phpAnno;
	private $constructorParams = array();
	private $phpTypeDef;
	
	public function __construct(PhpAnnoCollection $phpAnnoCollection, PhpTypeDef $phpTypeDef) {
		$this->phpAnno = $phpAnnoCollection;
		$this->phpTypeDef = $phpTypeDef;
	}
	
	public function getConstructorParams() {
		return $this->constructorParams;
	}

	public function getPhpTypeDef() {
		return $this->phpTypeDef;
	}

	public function setConstructorParams(array $constructorParams, bool $escape = true) {
		$this->constructorParams = $constructorParams;
		
		if ($escape) {
			$this->escapeConstructorParams();
		}
	}
	
	public function hasConstructorParam(int $position) {
		return count($this->constructorParams) >= $position;
	} 
	
	public function setConstructorParam(int $position, $value, bool $escape = false) {
		$constructorParams = array();
		
		if (!$this->hasConstructorParam($position)) {
			throw new \InvalidArgumentException('Position ' . $position 
					. ' not Available in \"' . $this->phpTypeDef . '\"');
		}
		
		$i = 1;
		foreach ($this->constructorParams as $constructorParam) {
			if ($position === $i) {
				$constructorParams[] = ($escape) ? $this->escapeConstructorParam($value) : $value;
			} else {
				$constructorParams[] = $constructorParam;
			}
			$i++;
		}
		
		$this->constructorParams = $constructorParams;
	}
	
	public function addConstructorParam($constructorParam, $escape = false) {
		if ($escape) {
			$constructorParam = self::escapeConstructorParam($constructorParam);
		}
		
		$this->constructorParams[] = $constructorParam;
	}
 
	public function setPhpTypeDef(PhpTypeDef $phpTypeDef) {
		$this->phpTypeDef = $phpTypeDef;
	}
	
	public function __toString() {
		return Phpbob::KEYWORD_NEW . ' ' . $this->phpTypeDef . '(' . implode(', ', $this->constructorParams) . ')';
	}
	
	public function escapeConstructorParams() {
		foreach ($this->constructorParams as $key => $constructorParam) {
			$this->constructorParams[$key] = self::escapeConstructorParam($constructorParam);
		}
	}
	
	public static function escapeConstructorParam($constructorParam) {
		if (StringUtils::startsWith(Phpbob::VARIABLE_PREFIX, $constructorParam)
				|| mb_strpos($constructorParam, Phpbob::CONST_SEPERATOR) !== false
				|| StringUtils::startsWith($constructorParam, Phpbob::STRING_LITERAL_SEPERATOR)
				|| StringUtils::startsWith($constructorParam, Phpbob::STRING_LITERAL_ALTERNATIVE_SEPERATOR)) {
			return $constructorParam;
		}
			
		return Phpbob::STRING_LITERAL_SEPERATOR . $constructorParam . Phpbob::STRING_LITERAL_SEPERATOR;
	}
}