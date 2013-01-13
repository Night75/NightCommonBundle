(function($){
	console.log("start");
	
	$.fn.nFormAjaxAjax = function(options)
	{	
		// *************** ------------------- Options par defaut
		$.fn.nFormAjax.defaults = {
			sendJson: true,
			loadingGif: false,
			loadingMessage: "Envoi en cours",
			errorUnexpected: "Un problème innatendu est survenu. Veuillez essayer de nous transmettre votre message ultérieurement.",
			errorBubbling: true
		}

		// *************** ------------------- Options chargees en parametres
		var opts = $.extend({}, $.fn.nFormAjax.defaults, options);
		
		return this.each(function(){
			
			// *************** ------------------- Options chargees a partir des attributs data
			var $form = ($(this).is("form")) ? $(this) : $(this).find("form");		 			// On charge notre form jQuery
			var o = $.extend({}, opts, $form.data());
			
				
			// *************** ------------------- Variables	
			// *************** Elements
			var $parentContainer = $form.parent();
			var $formFields = $form.find("input, textarea, select, checkbox, radio");	
			if($form.find("flash").length == 1){
				var $flashContainer = $form.find("flash")
			} else {
				var $flashContainer = $("<div class='flash'></div>");
				$form.prepend($flashContainer);
			}
			
			// *************** Chaines de texte
			var addr = ($form.attr("action") != "" || $form.attr("action") == undefined) ? $form.attr("action") : window.location.href;
			var loadingMessage = "<p class='loading'>" + o.loadingMessage;
			loadingMessage += (o.loadingGif != false) ? "<img src='" + o.loadingGif + "' />" : "";
			
			// ***************  Dimensions
			var heightForm = $form.outerHeight();

			//	 *************** -------------- Submit du formulaire
			$form.submit(function(){

				$formFields.attr("disabled", "disabled");										// Desactivation des inputs
				$flashContainer.empty().html(loadingMessage);							// Message d'attente'
				
				var form = $form.serialize();
				
				//*************** Reception de la reponse 
				$.post(addr, form, function(data) {
					$flashContainer.empty();

					//	 *************** Formulaire valide 
					if(data.responseCode == 200 ){      
						var $success = $("<p class='success'></p>")
						$form.replaceWith($success);
						$success.html(data.message);
						$success.css({"padding" : heightForm/2 + "px" + " 0"})
					}

					//	 *************** Formulaire invalide
					else if(data.responseCode == 400 ){    
						$formFields.removeAttr("disabled");
						var $errors = $("<ul class='errors'></ul>");
						$flashContainer.append($errors);
						for(error in data.errors){
							$errors.append("<li>" + data.errors[error] + "</li>"); 
						}
					}
					//	 *************** Probleme de transmisson
					else{
						var $errors = $("<p class='errors'></p>");
						$flashContainer.append($errors)
						if(data.message != undefined){
							$errors.html(data.message);
						} else {
							$errors.html(o.errorUnexpected);
						}
					}
				}) // -- Fin de $.post
				
				return false;
			})  // -- Fin de $form.submit
		})  // -- Fin de this.each
	}
})(jQuery)
