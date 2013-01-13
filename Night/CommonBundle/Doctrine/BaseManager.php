<?php

namespace Night\CommonBundle\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BaseManager 
{
	protected $objectManager;
	protected $class;
	protected $repository;
	protected $container;
	protected $uploadDir = "web/uploads";
	protected $files = array();
	protected $entityUtil;
	protected $previousEntity;
	protected $hasThumbs = false;
	
	/**
	 * Constructor.
	 *
	 * @param ObjectManager           $om
	 * @param string                  $class
	 */
	public function __construct(ObjectManager $om, ContainerInterface $container, $uploadDir = "") {
		$this->objectManager = $om;
		$this->container = $container;
		$this->uploadDir = $uploadDir == "" ? $this->uploadDir : $uploadDir;
		
		$this->entityUtil = $this->container->get("entity_util");
		$this->class = $this->getEntityName();
		$this->repository = $om->getRepository($this->class);
		$this->files = (!empty($this->files)) ? $this->files : $this->entityUtil->getFilesProperties($this->class);
	}
	
	/*
	 * Si une methode non existante est appelee, on cherchera a l'appel
	 */
	public function __call($method, $args) {
		if (method_exists($this->repository, $method)) {
			if (!empty($args)) {
				return call_user_func_array(array($this->repository, $method), $args);
			} else {
				return $this->repository->$method();
			}
		}
		throw new \BadMethodCallException(sprintf("La méthode %s n'existe pas pour la classe %s", $method, get_class($this)));
	}
	
	public function persist($entity){
		$this->prePersist($entity);
		$this->objectManager->persist($entity);
		$this->postUpdate($entity);
	}
	
	public function flush(){
		$this->objectManager->flush();
	}
	
	public function delete($entity) {
		$this->preRemove($entity);
		$this->objectManager->remove($entity);
		$this->objectManager->flush();
		$this->postRemove($entity);
	}

	public function find($id){
		$entity = $this->objectManager->find($id);
		$this->setPreviousEntity($entity);
	}
	
	public function getClass() {
		return $this->class;
	}

	public function getUploadDir() {
		return $this->uploadDir;
	}

	public function getUploadRootDir() {
		//var_dump(realpath( __DIR__ . '/../../../../../../' . $this->getUploadDir()));
		return __DIR__ . '/../../../../../../' . $this->getUploadDir();
	}
	
	public function getExecDeleteFile($entity, $fileGetter){
		$method =  substr($fileGetter, 0 , -3) ."Delete"; 
		return $entity->$method();
	}
	
	public function prePersist($entity, $recursive = true){
		if(method_exists($entity, "setCreatedAt")){
			$entity->setCreatedAt(new \Datetime());
		}
		$this->preUpdate($entity, $recursive);
	}
	
	public function preUpdate($entity, $recursive = true){
		if(method_exists($entity, "setUpdatedAt")){
			$entity->setUpdatedAt(new \Datetime());
		}	
		$this->handleUploadedFiles($entity);

		// ==== preUpdate des entites incluses
		if($recursive){
			$this->updateEmbeddedEntities($entity);
		}
	}
	
	public function updateEmbeddedEntities($entity){
		$embedEntities = $this->entityUtil->getEmbeddedEntities($entity);
		if(empty($embedEntities)){
			return;
		}
		
		// ==== Cas d'un update 
		if(!$this->isNewEntity($entity)){
			$previousIds = $this->entityUtil->getEmbeddedEntitiesIds($this->previousEntity);
			$currentIds =  $this->entityUtil->getEmbeddedEntitiesIds($entity);
			$idsToRemove = array_diff($previousIds, $currentIds); 
		}
		
		foreach($embedEntities as $sub){	
			// ==== Cas d'un update 
			if(!$this->isNewEntity($entity)){
				if(in_array($sub->getId(), $idsToRemove)){		
					$entity->removeSurveyAnswer($sub);
				}
			}	
			$manager = $this->entityUtil->getManagerFromEntity($sub);	
			if(!$sub->getId()){
				$this->container->get($manager)->prePersist($sub, false);
			} else{
				$this->container->get($manager)->preUpdate($sub, false);
			}
		}
	}
	
	
	public function handleUploadedFiles($entity) 
	{		
		foreach ($this->files as $fileProperty) {
			$fileGetter =  $this->entityUtil->propToGetter($fileProperty); 
			$fileNameGetter = $this->entityUtil->propToGetter($this->entityUtil->filePropToFilenameProp($fileProperty));
			$fileNameSetter = $this->entityUtil->propToSetter($this->entityUtil->filePropToFilenameProp($fileProperty)); 
			$previousFile = $this->getPreviousUploadedFile($entity, $fileNameGetter);
			
			// === Si un fichier a ete charge
			if($entity->$fileGetter() && is_object($entity->$fileGetter() )){
				
				// $file est une instance de Symfony\Component\HttpFoundation\File\UploadedFile
				$file = $entity->$fileGetter();
				$filename = $this->entityUtil->generateRandomFileName($file);
				$file->move($this->getUploadRootDir(), $filename);
				
				unset($file);
				$this->getPreviousUploadedFile($entity, $fileNameGetter);
				$entity->$fileNameSetter($filename); 
				$this->getPreviousUploadedFile($entity, $fileNameGetter);
				
				// Suppression de l'ancien fichier si existant
				if($previousFile){
					$this->removeUploadedFile($previousFile);
				}
			}
			// === Si aucun fichier n'a ete charge mais que l'on veut supprimer le fichier
			elseif($this->getExecDeleteFile($entity, $fileGetter)){
				$this->removeUploadedFile($entity->$fileNameGetter());
				$entity->$fileNameSetter(null);
			}
		}
	}
	
	
	public function preRemove($entity){
		
	}
	
	public function postRemove($entity){
		$this->removeUpload($entity);	
	}
	
	public function removeUpload($entity) {
		foreach ($this->files as $fileProperty) {
			$fileNameGetter =$this->entityUtil->propToGetter($this->entityUtil->filePropToFilenameProp($fileProperty)); 
			$fileName = $entity->$fileNameGetter();
			if($fileName){	
				$file = $this->getUploadRootDir() .'/'. $fileName;
				unlink($file);
			}	
		}		
	}
	
	public function getPreviousUploadedFile($entity, $fileGetter){
		// === On verfie que l''on est dans le cas d'un upgrade d'entie
		if($entity->getId()){
			$previousEntity = $this->repository->find($entity->getId());
			return $previousEntity->$fileGetter();
		}
		return null;
	}
	
	public function removeUploadedFile($file){
		$file = $this->getUploadRootDir() .'/'.$file;
		unlink($file);
	}
	
	public function containsFile($entity){
		foreach ($this->files as $fileProperty) {
			$fileGetter =  $this->entityUtil->propToGetter($fileProperty); 
			$file = $entity->$fileGetter();
			return empty($file);
		}
	}
	
	/**
	* Obtient le nom de l'entite liee au manager
	* 
	* @return string
	*/
	public function getEntityName(){
		$tab = explode('\\' , get_class($this)) ;
		$managerClass = $tab[count($tab) - 1];
		if(substr($managerClass, -7) !== "Manager"){
			throw new \UnexpectedValueException(
				sprintf("La classe %s a un nom de classManager invalide.", 
				getclass($this)
			));
		}
		$class = substr($tab[count($tab) -1],0 , -7);
		unset($tab[count($tab) - 1]);
		
		return implode("\\", $tab) .'\\'.$class;			
	}
	
	public function getPreviousEntity() {
		return $this->previousEntity;
	}

	public function setPreviousEntity($previousEntity) {
		$this->previousEntity = $previousEntity;
	}

	public function loadPreviousEntity($entity) {
		if($entity->getId()){
			$this->objectManager->clear();
			$temp = $this->repository->find($entity->getId());
			$this->previousEntity = clone $temp;	
		}
	}

	public function isNewEntity($entity){
		return !(bool)$entity->getId();
	}
}
