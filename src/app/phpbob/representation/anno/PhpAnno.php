<?php
namespace phpbob\representation\anno;

use phpbob\Phpbob;
use n2n\util\StringUtils;
use phpbob\representation\PhpTypeDef;

class PhpAnno {
	private $phpAnno;
	private $phpAnnoParams = array();
	private $phpTypeDef;
	
	public function __construct(PhpAnnoCollection $phpAnnoCollection, PhpTypeDef $phpTypeDef) {
		$this->phpAnno = $phpAnnoCollection;
		$this->phpTypeDef = $phpTypeDef;
	}
	
	public function getPhpAnnoParams() {
		return $this->phpAnnoParams;
	}

	public function getPhpTypeDef() {
		return $this->phpTypeDef;
	}
	
	public function createPhpAnnoParam(string $value, bool $escape = false) {
		$this->phpAnnoParams[] = new PhpAnnoParam($this, $escape ? self::escapeString($value) : $value);	
	}
	
	public function hasPhpAnnoParam(int $position) {
		return isset($this->phpAnnoParams[$position - 1]);
	}
	
	public function getNumPhpAnnoParams() {
		return count($this->phpAnnoParams);	
	}
	
	/**
	 * @param int $position
	 * @throws \InvalidArgumentException
	 * @return PhpAnnoParam
	 */
	public function getPhpAnnoParam(int $position, bool $lenient = true) {
		if (!$this->hasPhpAnnoParam($position)) {
			if ($lenient) return null;
			
			throw new \InvalidArgumentException('Position ' . $position
					. ' not Available in \"' . $this->phpTypeDef . '\"');
		}
		
		return $this->phpAnnoParams[$position];
	}
 
	public function setPhpTypeDef(PhpTypeDef $phpTypeDef) {
		$this->phpTypeDef = $phpTypeDef;
	}
	
	public function isForAnno(string $typeName) {
		return $this->phpTypeDef->determineUseTypeName() === $typeName;
	}
	
	private static function escapeString(string $str) {
		if (StringUtils::startsWith(Phpbob::VARIABLE_PREFIX, $str)
				|| mb_strpos($str, Phpbob::CONST_SEPERATOR) !== false
				|| StringUtils::startsWith($str, Phpbob::STRING_LITERAL_SEPERATOR)
				|| StringUtils::startsWith($str, Phpbob::STRING_LITERAL_ALTERNATIVE_SEPERATOR)) {
					return $str;
				}
				
				return Phpbob::STRING_LITERAL_SEPERATOR . $str . Phpbob::STRING_LITERAL_SEPERATOR;
	}
	
	public function __toString() {
		return Phpbob::KEYWORD_NEW . ' ' . $this->phpTypeDef . '(' . implode(', ', $this->phpAnnoParams) . ')';
	}
}