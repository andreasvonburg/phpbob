<?php
namespace phpbob\analyze;

use phpbob\PhpStatement;
use phpbob\PhprepUtils;
use phpbob\StatementGroup;
use phpbob\SingleStatement;
use phpbob\Phpbob;
use phpbob\representation\PhpMethodAnno;
use phpbob\representation\PhpClassAnno;
use phpbob\representation\PhpPropertyAnno;
use phpbob\representation\PhpClass;
use n2n\reflection\annotation\AnnotationSet;
use n2n\reflection\ArgUtils;
use n2n\reflection\CastUtils;
use phpbob\representation\anno\PhpAnnoCollection;

class PhpAnnoAnalyzer {
	
	private $variableDefinitions = array();
	private $paramAnalyzer;
	private $phpClass;
	private $phpAnnotationSet;
	
	public function __construct(PhpClass $phpClass) {
		$this->paramAnalyzer = new PhpAnnoParamAnalyzer();
		$this->phpClass = $phpClass;
		$this->phpAnnotationSet = $phpClass->getPhpAnnotationSet();
	}
	
	public function analyze(PhpStatement $phpStatement, AnnotationSet $as = null) {
		if (!($phpStatement instanceof StatementGroup && PhprepUtils::isAnnotationStatement($phpStatement))) {
			throw new PhpAnnotationSourceAnalyzingException('invalid annotation-statement:' . 
					$phpStatement);
		}
		
		$this->determineVarialbeDefinitions($phpStatement);
		
		$phpAnnotationSet = $this->phpClass->getPhpAnnotationSet();
		$aiVariableName = null;
		$matches = array();
		if (preg_match('/private\s+static\s+function\s+_annos\s*\(\s*(n2n\\reflection\\annotation)?AnnoInit\s+(\$\S+)\)/',
				implode(' ', $phpStatement->getCodeLines()), $matches) && (count($matches) === 2 || count($matches) === 3)) {
			$aiVariableName = end($matches);
			$phpAnnotationSet->setAiVariableName($aiVariableName);
		} else {
			throw new \InvalidArgumentException('Invalid Annotation Mehtod signature');
		}
		
		$prependingCode = '';
		foreach ($phpStatement->getPhpStatements() as $childPhpStatement) {
			if ($this->isClassAnnotation($childPhpStatement, $aiVariableName)) {
				$this->applyPhpClassAnno($childPhpStatement, $aiVariableName, $prependingCode);
				$prependingCode = '';
				continue;
			} 
			
			if ($this->isMethodAnnotation($childPhpStatement, $aiVariableName)) {
				$this->applyPhpMethodAnno($childPhpStatement, $aiVariableName, $prependingCode);
				$prependingCode = '';
				continue;
			} 
			
			if ($this->isPropertyAnnotation($childPhpStatement, $aiVariableName)) {
				$this->applyPhpPropertyAnno($childPhpStatement, $aiVariableName, $prependingCode);
				$prependingCode = '';
				continue;
			}
			
			$prependingCode .= $childPhpStatement;
		}
// 		if ($as !== null) {
// 			$this->processPropertyAnnos($as, $phpAnnotationSet->getPropertyAnnos());
// 			$this->processMethodAnnos($as, $phpAnnotationSet->getMethodAnnos());
// 			$this->processClassAnno($as, $phpAnnotationSet->getClassAnno());
// 		}
		
//		return $phpAnnotationSet;
	}
	
// 	private function processPropertyAnnos(AnnotationSet $as, array $propertyAnnos) {
// 		ArgUtils::valArray($propertyAnnos, PhpPropertyAnno::class);
// 		$numPropertyAnnoParams = 0;
// 		foreach ($propertyAnnos as $propertyAnno) {
// 			CastUtils::assertTrue($propertyAnno instanceof PhpPropertyAnno);
			
// 			foreach ($propertyAnno->getParams() as $param) {
// 				$annotation = $as->getPropertyAnnotation($propertyAnno->getPropertyName(), $param->getTypeName());
// 				if (null === $annotation) {
// 					throw new PhpAnnotationSourceAnalyzingException('Invalid Annotation Set: Annotation ' .
// 							$param->getTypeName() . ' for Property ' . $propertyAnno->getPropertyName() . ' missing');
// 				}
// 				$param->setAnnotation($annotation);
// 				$numPropertyAnnoParams++;
// 			}
// 		}
		
// 		if ($numPropertyAnnoParams === count($as->getAllPropertyAnnotations())) return;
		
// 		throw new PhpAnnotationSourceAnalyzingException('Structure of Annotation statement invalid:
// 				number of property annotations does not match.');
// 	}
	
// 	private function processMethodAnnos(AnnotationSet $as, array $methodAnnos) {
// 		ArgUtils::valArray($methodAnnos, PhpMethodAnno::class);
// 		$numMethodAnnoParams = 0;
// 		foreach ($methodAnnos as $methodAnno) {
// 			foreach ($methodAnno->getParams() as $param) {
// 				$annotation = $as->getMethodAnnotation($methodAnno->getMethodName(), $param->getTypeName());
// 				if (null === $annotation) {
// 					throw new PhpAnnotationSourceAnalyzingException('Invalid Annotation Set: Annotation ' .
// 							$param->getTypeName() . ' for Method ' . $methodAnno->getMethodName() . ' missing');
// 				}
// 				$param->setAnnotation($annotation);
// 				$numMethodAnnoParams++;
// 			}
// 		}
		
// 		if ($numMethodAnnoParams === count($as->getAllMethodAnnotations())) return;
		
// 		throw new PhpAnnotationSourceAnalyzingException('Structure of Annotation statement invalid:
// 				number of method annotations does not match.');
// 	}
	
// 	private function processClassAnno(AnnotationSet $as, PhpClassAnno $classAnno = null) {
// 		if (null === $classAnno) {
// 			if (count($as->getClassAnnotations()) === 0) return;
// 		} else {
// 			$numClassAnnoParams = 0;
// 			foreach ($classAnno->getParams() as $param) {
// 				$annotation = $as->getClassAnnotation($param->getTypeName());
// 				if (null === $annotation) {
// 					throw new PhpAnnotationSourceAnalyzingException('Invalid Annotation Set: Annotation ' .
// 							$param->getTypeName() . ' for Class missing');
// 				}
// 				$param->setAnnotation($annotation);
// 				$numClassAnnoParams++;
// 			}
			
// 			if ($numClassAnnoParams === count($as->getClassAnnotations())) return;
// 		}
		
// 		throw new PhpAnnotationSourceAnalyzingException('Structure of Annotation statement invalid:
// 				number of class annotations does not match.');
// 	}
	
	private function determineVarialbeDefinitions(StatementGroup $statementGroup) {
		$this->variableDefinitions = [];
		
		foreach ($statementGroup->getPhpStatements() as $phpStatement) {
			if (!$phpStatement instanceof SingleStatement) {
				throw new PhpSourceAnalyzingException('only single statements are allowed in annotation statements. Given statement: '
						. $phpStatement->__toString());
			}
			
			$matches = array();
			if (!preg_match('/\s*(\$\S+)\s*=\s*(\s*' . preg_quote(Phpbob::KEYWORD_NEW). '\s+.*);/', 
					$phpStatement->getCode(), $matches) || count($matches) !== 3) continue;

			$this->variableDefinitions[$matches[1]] = $this->paramAnalyzer->createNewClassAnnoParam($matches[2]);
		}
	}

	private function isClassAnnotation(PhpStatement $phpStatement, $aiVariableName) {
		return $phpStatement instanceof SingleStatement
				&& preg_match('/' . preg_quote($aiVariableName) .'-\>c/', (string) $phpStatement);
	}
	
	private function isMethodAnnotation(PhpStatement $phpStatement, $aiVariableName) {
		return $phpStatement instanceof SingleStatement
				&& preg_match('/' . preg_quote($aiVariableName) .'-\>m/', (string) $phpStatement);
	}
	
	private function isPropertyAnnotation(PhpStatement $phpStatement, $aiVariableName) {
		return $phpStatement instanceof SingleStatement
				&& preg_match('/' . preg_quote($aiVariableName) .'-\>p/', (string) $phpStatement);
	}

	private function applyPhpClassAnno(PhpStatement $phpStatement, $aiVariableName, $prependingCode = null) {
		$matches = array();
		if (!preg_match('/' . preg_quote($aiVariableName) . '->c\s*\(\s*(.*)\s*\)\s*;/',
			implode(' ', $phpStatement->getCodeLines()), $matches) || count($matches) !== 2) {
			throw new PhpAnnotationSourceAnalyzingException('Invalid Class Annotation statement' . $phpStatement);
		}
		
		$this->applyAnnosFromString($this->phpAnnotationSet->getOrCreatePhpClassAnnoCollection(), 
				$matches[1], $this->createPrependingCode($phpStatement, $prependingCode));
	}
	
	private function applyPhpMethodAnno(PhpStatement $phpStatement, $aiVariableName, $prependingCode = null) {
		$matches = array();
		if (!preg_match('/' . preg_quote($aiVariableName) . '->m\s*\(\s*\'([^\']*)\'\s*,\s*(.*)\s*\)\s*;/',
				implode(' ', $phpStatement->getCodeLines()), $matches) || count($matches) !== 3) {
			throw new PhpAnnotationSourceAnalyzingException('Invalid Method Annotation statement: ' . $phpStatement);
		}
		
		$this->applyAnnosFromString($this->phpAnnotationSet->getOrCreatePhpMethodAnnoCollection($matches[1]), 
				$matches[2], $this->createPrependingCode($phpStatement, $prependingCode));
	}
	
	private function applyPhpPropertyAnno(PhpStatement $phpStatement, $aiVariableName, $prependingCode = null) {
		$matches = array();
		if (!preg_match('/' . preg_quote($aiVariableName) . '->p\s*\(\s*\'([^\']*)\'\s*,\s*(.*)\s*\)\s*;/',
				implode(' ', $phpStatement->getCodeLines()), $matches) || count($matches) !== 3) {
			throw new PhpAnnotationSourceAnalyzingException('Invalid Property Annotation statement: ' . $phpStatement);
		}
		
		$this->applyAnnosFromString($this->phpAnnotationSet->getOrCreatePhpPropertyAnnoCollection($matches[1]), 
				$matches[2], $this->createPrependingCode($phpStatement, $prependingCode));
	}
	
	private function createPrependingCode(PhpStatement $phpStatement, $additonalPrependingCode = null) {
		return $additonalPrependingCode . implode(PHP_EOL, $phpStatement->getNonCodeLines());
	}
	
	private function applyAnnosFromString(PhpAnnoCollection $phpAnnoCollection, string $paramString, 
			string $prependingCode) {
		foreach ($this->paramAnalyzer->analyze($paramString, $this->variableDefinitions) as $phpAnnoDef) {
			$localName = $phpAnnoDef->getTypeName();
			$typeName = $this->phpClass->determineTypeName($localName);
			$phpAnnoCollection->createPhpAnno($typeName, $localName)
					->setConstructorParams($phpAnnoDef->getConstructorParams());
		}
		
		$phpAnnoCollection->appendPrependingCode($prependingCode);
	}
}