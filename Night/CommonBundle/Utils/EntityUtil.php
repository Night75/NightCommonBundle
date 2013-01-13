<?php

namespace Night\CommonBundle\Utils;

class EntityUtil
{
	
	/**
	 *Convertit une chaine de type camelCase en son ecriture au format standard
	 * Ex: getQuelqueChose ----> get_quelque_chose
	 * 
	 * @param string $text
	 * @return string 
	 */
	 public function camelCaseToStandard($text){
		return strtolower(preg_replace('/\p{Lu}/u', '_\0', $text));
	}
	
	/**
	 *Convertit une chaine de type standard en son ecriture au format camelCase
	 * Ex: get_quelque_chose ----> getQuelqueChose
	 * 
	 * @param string $text
	 * @return string 
	 */
	 public function  standardToCamelCase($text){
		return str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $text))));
	}
	
	/**
	 * Obtient le getter d'une propriete. Le getter doit respecter les conventions d'ecritures"
	 * Ex: ma_propriete ----> getMaPropriete
	 * 
	 * @param string $text
	 * @return string 
	 */
	 public function propToGetter($prop){
		return "get" .$this->standardToCamelCase($prop);
	}
	
	/**
	 * Obtient le setter d'une propriete. Le getter doit respecter les conventions d'ecritures"
	 * Ex: ma_propriete ----> setMaPropriete
	 * 
	 * @param string $text
	 * @return string 
	 */
	 public function propToSetter($prop){
		return "set" .$this->standardToCamelCase($prop);
	}
	
	/**
	 * Pour Doctrine! 
	 * Obtient les entites jointes a l'entite passee en parametre. Cette verification est faite 
	 * en effectuant une verification sur les proprietes.  
	 * 
	 * @param object $entity
	 * @param bool $recursive.
	 * @return array 
	 */	
	 public function getEmbeddedEntities($entity, $recursive = false){
		$rObj = new \ReflectionObject($entity);
		$rMethods = $rObj->getMethods();
		$results = array();
		
		foreach($rMethods as $rMethod){
			$method = $rMethod->getName();
			if(substr($method, 0, 3) == "get"){

				$subItem =  $entity->$method();
				if($subItem instanceof \Doctrine\Common\Collections\ArrayCollection ||
					$subItem instanceof \Doctrine\ORM\PersistentCollection
					){
					foreach($subItem as $item){
						if($this->isEntity($item)){
							$results[] = $item;
						}
					}
				} elseif($this->isEntity($subItem)){
					$results[] = $subItem;
				}
			}		
		}	
		return $results;
	}
	
	public function getEmbeddedEntitiesIds($entity){
		$children = $this->getEmbeddedEntities($entity);
		$results = array();
		foreach($children as $child){
			$results[] = $child->getId();
		}
		return $results;
	}
	/**
	 *Verifie si la classe passe en parametre est une entite
	 * 
	 * @param object $subItem
	 * @return boolean 
	 */	
	 public function isEntity($subItem){
		if(is_object($subItem)){
			$class = get_class($subItem);		// On attend une classe du type Foo/BarBundle/Entity/Article
			// === On a trouve une entite incluse dans une des proprietes
			return (strpos($class, "Entity\\") !== false);
		}
		return false;
	}
	
	/**
	 * Obtient les noms des proprietes qui serviront a stocker des instances 
	 * de Symfony\Component\HttpFoundation\File\UploadedFile. 
	 * Cette methode est fonctionnelle uniquement si l'entite a des annotations @Assert\File ou @Assert\Image
	 * 
	 * @param mixed $class
	 * @param bool $is_object
	 * @return array
	 */
	 public function getFilesProperties($class ){
		$entity = is_object($class) ? $class : new $class ;

		$filesAnnotation = array('@Assert\File', '@Assert\Image');										
		$rObj = new \ReflectionObject($entity);
		$rProps = $rObj->getProperties();
		$filesProp = array();

		foreach($rProps as $prop){
			$docComment = $prop->getDocComment();														

			foreach($filesAnnotation as $fileAnnotation){
				
				if(strpos($docComment, $fileAnnotation) !== false){
					$prop = $prop->getName();
					
					// Notre propriete doit comporter le suffixe _add, par convention. Cela permettra par exemple pour une propriete image_add, 
					// d'y associer automatiquement une propriete image (qui devra etre de meme etre cree). Celle-ci stockera le nom du fichier
					if(substr($prop, -4) !== "_add"){
						throw new \UnexpectedValueException(
							sprintf("La classe %s a un nom de propriété de fichier invalide. La propriété %s doit se terminer par _add.",
							$className,
							$prop 
						));
					}
				
					// On verifie que notre propriete "fichier_add" est associe a une propriete fichier, sinon une exception est renvoyee
					if(!$rObj->hasProperty($this->filePropToFilenameProp($prop) )){
							throw new \UnexpectedValueException(
								sprintf("La classe %s doit avoir une propriété %s associée à la propriété %s.",
								$className,
								substr($prop, 0, -4),
								$prop
							));
					}
					$filesProp[] = $prop;
				}
			}
		}
		unset($entity);
		return $filesProp;
	}
	
	/**
	 *Obtient le nom de la propriete de nom de fichier. Par convention de ce bundle
	 * la propriete qui va stocker les fichiers uploades (Symfony\Component\HttpFoundation\File\UploadedFile. )
	 * sera nommee : fichier_add . Tandis que celle qui va stocker le nom du fichier (et donc stockee en bdd) 
	 * sera nommee : fichier.	  "fichier" est un exemple, ca peut aussi bien etre image, file, etc..
	 * 
	 * @param string $prop
	 * @return type 
	 */
	public function filePropToFilenameProp($prop){
		return substr($prop, 0, -4);
	}

	/**
	 * Obtient le nom du manager de l'entite passee en parametre. Fonctionne si la convention est respectee
	 * Entite: Night/TrucBundle/Entity/QuequeChose ---> 
	 * Manager: night.truc.quelque_chose_manager
	 * 
	 * @param type $entity
	 * @return string 
	 */
	public function getManagerFromEntity($class){
	
		$fullQualifiedClass = is_object($class) ? get_class($class) : $class;
		$tab = explode('\\', $fullQualifiedClass );
		
		$manager = strtolower($tab[0]) .".";
		//$manager = strtolower(substr(preg_replace('/\p{Lu}/u', '_\0', $tab[0]), 1)) .".";											// Nom de l'application. Acme => acme
		$i = 1;
		while(!strpos($tab[$i], "Bundle") > 0){		
			++$i;
		}
		$manager .= strtolower(substr(preg_replace('/\p{Lu}/u', '_\0', $tab[$i]), 1, -7)) .".";										// Nom du bundle. SuperBonBundle => super_bon
		$manager .= strtolower(substr(preg_replace('/\p{Lu}/u', '_\0', $tab[count($tab) -1 ]), 1));								// Nom de l'entite. ImageOcean => image_ocean
		$manager .= "_manager";																													// Suffixe : _manager
		return $manager;
	}
	
	public function generateRandomFileName( \Symfony\Component\HttpFoundation\File\UploadedFile $file){
		$filename = sha1(uniqid(mt_rand(), true)); 
		$filename = $filename . '.' . $file->guessExtension();
		return $filename;
	}
}