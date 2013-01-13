<?php

namespace VS\VitrineBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Article controller.
 *
 */
class AjaxController extends Controller
{
	
	public function processAjaxAction(){
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$response->setStatusCode(200);

		sleep(2);
//		if ($request->getMethod() == 'POST') {	
//    		$form->bindRequest($request);
//    		if ($form->isValid()) {
//				$oContact = $form->getData();
//				$contactManager = $this->get("contact_manager");
//				$successSend = $contactManager->send($from, $to, $oContact);
				$successSend = true;
				

				if($successSend){		
					$response->setContent(json_encode(array(
									"responseCode" => 200,
									"message" => "Votre message a été envoyé avec succès.")
								));
				}
				$response->setContent(json_encode(array(
									"responseCode" => 409,
									"message" => "Un problème innatendu est survenu. Veuillez essayer de nous transmettre votre message ultérieurement."
								)));
//			}
			
//			$errors = array();
//			foreach($form->getErrors() as $error){
//				$errors[] = $error->getMessageTemplate();
//			}
			$errors = array("erreur 1", "erreur 2", "erreur 3");
			$response->setContent(json_encode(array(
									"responseCode" => 400,
									 "errors" => $errors, 
								)));
			
			return $response;
	}
}