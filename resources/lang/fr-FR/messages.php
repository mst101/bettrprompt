<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Messages personnalisés de l'application
    |--------------------------------------------------------------------------
    |
    | Les lignes de langue suivantes sont utilisées pour les messages
    | personnalisés de l'application, y compris les notifications
    | de succès/erreur, les sujets d'e-mail et le contenu.
    |
    */

    // Messages de succès
    'profile_updated' => 'Votre profil a été mis à jour avec succès.',
    'profile_deleted' => 'Votre profil a été supprimé.',
    'password_changed' => 'Votre mot de passe a été modifié avec succès.',
    'email_updated' => 'Votre adresse e-mail a été mise à jour.',
    'preferences_saved' => 'Vos préférences ont été enregistrées.',
    'settings_updated' => 'Vos paramètres ont été mis à jour.',

    // Messages d'erreur
    'something_went_wrong' => 'Une erreur est survenue. Veuillez réessayer.',
    'unauthorized' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
    'not_found' => 'La ressource demandée n\'a pas été trouvée.',
    'workflow_failed' => 'Le flux de travail n\'a pas pu être traité. Veuillez réessayer.',
    'rate_limited' => 'Trop de demandes. Veuillez attendre un moment avant de réessayer.',
    'server_error' => 'Une erreur serveur s\'est produite. Veuillez réessayer plus tard.',
    'invalid_request' => 'La demande est invalide.',
    'validation_failed' => 'Les données fournies n\'ont pas passé la validation.',

    // Messages de flux de travail
    'prompt_generating' => 'Génération de votre invite...',
    'prompt_generated' => 'Votre invite a été générée avec succès.',
    'prompt_generation_failed' => 'Impossible de générer l\'invite. Veuillez réessayer.',
    'analysis_in_progress' => 'Votre analyse est en cours. Veuillez patienter...',
    'analysis_complete' => 'Votre analyse est terminée.',

    // Messages d'e-mail
    'email' => [
        'password_reset_subject' => 'Réinitialiser votre mot de passe',
        'password_reset_title' => 'Réinitialiser votre mot de passe',
        'password_reset_body' => 'Vous recevez cet e-mail parce que nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.',
        'password_reset_button' => 'Réinitialiser le mot de passe',
        'password_reset_footer' => 'Ce lien de réinitialisation de mot de passe expirera dans {minutes} minutes.',
        'password_reset_footer_no_action' => 'Si vous n\'avez pas demandé de réinitialisation de mot de passe, aucune action supplémentaire n\'est requise.',

        'welcome_subject' => 'Bienvenue sur BettrPrompt',
        'welcome_title' => 'Bienvenue sur BettrPrompt',
        'welcome_body' => 'Merci de vous être inscrit à BettrPrompt ! Nous sommes ravis de vous aider à créer des invites d\'IA calibrées par la personnalité.',
        'welcome_footer' => 'Si vous avez des questions, veuillez nous contacter sans hésiter.',

        'email_verification_subject' => 'Vérifiez votre adresse e-mail',
        'email_verification_title' => 'Vérifiez votre adresse e-mail',
        'email_verification_body' => 'Veuillez vérifier votre adresse e-mail en cliquant sur le bouton ci-dessous.',
        'email_verification_button' => 'Vérifier l\'e-mail',
        'email_verification_footer' => 'Si vous n\'avez pas créé ce compte, vous pouvez ignorer cet e-mail.',

        'account_suspended_subject' => 'Votre compte a été suspendu',
        'account_suspended_title' => 'Compte suspendu',
        'account_suspended_body' => 'Votre compte a été suspendu. Veuillez contacter le support pour plus d\'informations.',

        'password_changed_subject' => 'Votre mot de passe a été modifié',
        'password_changed_title' => 'Mot de passe modifié',
        'password_changed_body' => 'Votre mot de passe a été modifié avec succès. Si vous n\'avez pas fait cette modification, veuillez contacter le support immédiatement.',

        'greeting' => 'Bonjour {name} !',
        'regards' => 'Cordialement,',
        'regards_team' => 'L\'équipe BettrPrompt',
    ],

    // Messages de validation
    'invalid_email' => 'Veuillez entrer une adresse e-mail valide.',
    'email_required' => 'Veuillez fournir votre adresse e-mail.',
    'password_required' => 'Un mot de passe est requis.',
    'password_too_short' => 'Votre mot de passe doit contenir au moins 8 caractères.',
    'passwords_do_not_match' => 'Les mots de passe ne correspondent pas.',
    'name_required' => 'Veuillez fournir votre nom.',

    // Informations de compte
    'account_created' => 'Votre compte a été créé avec succès.',
    'account_already_exists' => 'Un compte avec cette adresse e-mail existe déjà.',
    'email_not_verified' => 'Veuillez vérifier votre adresse e-mail avant de continuer.',
    'please_login' => 'Veuillez vous connecter pour continuer.',
    'session_expired' => 'Votre session a expiré. Veuillez vous reconnecter.',

    // Suppression de données
    'data_deletion_requested' => 'Votre demande de suppression de données a été soumise.',
    'data_will_be_deleted' => 'Vos données seront supprimées définitivement dans 30 jours.',
    'data_deletion_cancelled' => 'Votre demande de suppression de données a été annulée.',

    // Messages de validation du formulaire
    'form' => [
        'answer_required' => 'Veuillez fournir une réponse à la question.',
        'answer_max' => 'La réponse ne doit pas dépasser 1000 caractères.',
        'task_description_required' => 'Veuillez décrire la tâche que vous souhaitez accomplir.',
        'task_description_min' => 'La description de la tâche doit comporter au moins 10 caractères.',
        'experience_level_required' => 'Veuillez sélectionner votre niveau d\'expérience (Question 1).',
        'usefulness_required' => 'Veuillez évaluer l\'utilité de l\'application (Question 2).',
        'usage_intent_required' => 'Veuillez indiquer votre probabilité d\'utiliser l\'application à nouveau (Question 3).',
        'desired_features_required' => 'Veuillez sélectionner au moins une fonctionnalité que vous aimeriez voir.',
        'desired_features_other_required' => 'Veuillez décrire la fonctionnalité que vous avez sélectionnée sous « Autre ».',
        'password_delete_confirmation' => 'Veuillez entrer votre mot de passe pour confirmer la suppression du compte.',
        'name_required' => 'Veuillez entrer votre nom.',
        'email_required' => 'Veuillez entrer votre adresse e-mail.',
        'email_email' => 'Veuillez entrer une adresse e-mail valide.',
        'email_unique' => 'Cette adresse e-mail est déjà enregistrée.',
        'password_required' => 'Veuillez entrer un mot de passe.',
        'password_confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        'current_password' => 'mot de passe actuel',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
    ],

    // Messages du générateur d'invites
    'prompt_builder' => [
        'task_created_failed' => 'Impossible de créer la tâche. Veuillez réessayer.',
        'invalid_workflow_stage' => 'Étape de flux de travail invalide pour soumettre les réponses de pré-analyse.',
        'analysing_task' => 'Analyse de votre tâche...',
        'submit_answers_failed' => 'Impossible de soumettre les réponses. Veuillez réessayer.',
        'no_quick_queries' => 'Cette exécution d\'invite n\'a pas de questions rapides à mettre à jour.',
        'updating_answers' => 'Mise à jour de votre tâche avec les réponses...',
        'update_answers_failed' => 'Impossible de mettre à jour les réponses. Veuillez réessayer.',
        'cannot_go_back' => 'Impossible de revenir à ce stade.',
        'already_first_question' => 'Vous êtes déjà à la première question.',
        'go_back_failed' => 'Impossible de revenir. Veuillez réessayer.',
        'can_only_edit_completed' => 'Seules les exécutions d\'invite complétées peuvent être modifiées.',
        'prompt_updated' => 'Invite mise à jour avec succès.',
        'update_prompt_failed' => 'Impossible de mettre à jour l\'invite. Veuillez réessayer.',
        'visitor_limit_reached' => 'Vous avez déjà créé une invite optimisée en tant que visiteur. Veuillez créer un compte gratuit pour continuer.',
        'create_prompt_run_failed' => 'Une erreur s\'est produite lors de la création de la nouvelle exécution d\'invite. Veuillez réessayer.',
        'no_clarifying_questions' => 'L\'exécution d\'invite parent n\'a pas de questions clarificatrices.',
        'generating_optimised_prompt' => 'Génération de votre invite optimisée avec les réponses modifiées...',
        'prompt_generation_start_failed' => 'Impossible de démarrer la génération d\'invite. Veuillez réessayer.',
        'switching_framework' => 'Ré-analyse avec le cadre sélectionné...',
        'switch_framework_failed' => 'Une erreur s\'est produite lors du changement de cadre. Veuillez réessayer.',
        'only_failed_runs_can_retry' => 'Seules les exécutions échouées peuvent être relancées.',
        'retrying_pre_analysis' => 'Nouvelle tentative de pré-analyse...',
        'retrying_analysis' => 'Nouvelle tentative d\'analyse...',
        'retrying_prompt_generation' => 'Nouvelle tentative de génération d\'invite...',
        'cannot_retry_from_stage' => 'Impossible de réessayer à partir de ce stade.',
        'retry_failed' => 'Une erreur s\'est produite lors de la nouvelle tentative. Veuillez réessayer.',
        'deleted_successfully' => 'Exécution d\'invite supprimée avec succès.',
        'delete_failed' => 'Impossible de supprimer l\'exécution d\'invite. Veuillez réessayer.',
    ],

    // Étiquettes des types de personnalité
    'personality_types' => [
        'intj' => 'Architecte',
        'intp' => 'Logicien',
        'entj' => 'Commandant',
        'entp' => 'Débatteur',
        'infj' => 'Avocat',
        'infp' => 'Médiateur',
        'enfj' => 'Protagoniste',
        'enfp' => 'Campagnard',
        'istj' => 'Logisticien',
        'isfj' => 'Défenseur',
        'estj' => 'Exécutif',
        'esfj' => 'Consul',
        'istp' => 'Virtuose',
        'isfp' => 'Aventurier',
        'estp' => 'Entrepreneur',
        'esfp' => 'Animateur',
    ],

    // Messages de profil
    'profile' => [
        'account_deleted' => 'Votre compte a été supprimé.',
        'update_failed' => 'Impossible de mettre à jour le profil. Veuillez réessayer.',
        'personality_update_failed' => 'Impossible de mettre à jour le type de personnalité. Veuillez réessayer.',
        'location_update_failed' => 'Impossible de mettre à jour la localisation. Veuillez réessayer.',
        'location_detect_failed' => 'Impossible de détecter la localisation à partir de votre adresse IP. Veuillez la définir manuellement.',
        'location_detection_failed' => 'Impossible de détecter la localisation. Veuillez réessayer.',
        'location_clear_failed' => 'Impossible d\'effacer la localisation. Veuillez réessayer.',
        'professional_clear_failed' => 'Impossible d\'effacer les informations professionnelles. Veuillez réessayer.',
        'team_clear_failed' => 'Impossible d\'effacer les informations sur l\'équipe. Veuillez réessayer.',
        'budget_clear_failed' => 'Impossible d\'effacer les préférences budgétaires. Veuillez réessayer.',
        'tools_clear_failed' => 'Impossible d\'effacer les outils et technologies. Veuillez réessayer.',
        'professional_update_failed' => 'Impossible de mettre à jour le contexte professionnel. Veuillez réessayer.',
        'team_update_failed' => 'Impossible de mettre à jour le contexte de l\'équipe. Veuillez réessayer.',
        'budget_update_failed' => 'Impossible de mettre à jour les préférences budgétaires. Veuillez réessayer.',
        'tools_update_failed' => 'Impossible de mettre à jour les préférences d\'outils. Veuillez réessayer.',
        'delete_account_failed' => 'Impossible de supprimer le compte. Veuillez contacter le support.',
        'unexpected_error' => 'Une erreur inattendue s\'est produite. Veuillez contacter le support.',
    ],

    // Messages de rétroaction
    'feedback' => [
        'thank_you' => 'Merci de votre rétroaction !',
        'thank_you_update' => 'Merci d\'avoir mis à jour votre rétroaction !',
    ],

    // Messages d'abonnement
    'subscription' => [
        'invalid_plan' => 'Plan invalide sélectionné.',
        'prompt_limit_reached' => 'Vous avez atteint votre limite mensuelle d\'invites.',
        'prompt_limit_reached_upgrade' => 'Vous avez atteint votre limite mensuelle d\'invites. Passez à Pro pour des invites illimitées.',
        'welcome_pro' => 'Bienvenue sur BettrPrompt Pro !',
        'welcome_private' => 'Bienvenue sur BettrPrompt Privé ! Vos données sont désormais protégées avec une confidentialité renforcée.',
        'checkout_cancelled' => 'La validation de l\'abonnement a été annulée.',
        'cancelled_pro_until' => 'Votre abonnement a été annulé. Vous conserverez l\'accès Pro jusqu\'au {date}.',
        'resumed' => 'Votre abonnement a été repris.',
        'current_plan' => 'Forfait Actuel',
    ],

    // Messages d'authentification
    'auth' => [
        'logged_out' => 'Vous avez été déconnecté avec succès.',
        'logged_out_session' => 'Vous avez été déconnecté.',
        'admin_required' => 'Non autorisé. L\'accès administrateur est requis.',
        'google_connection_failed' => 'Impossible de se connecter à Google. Veuillez réessayer plus tard.',
        'google_account_info_failed' => 'Impossible de récupérer vos informations de compte auprès de Google. Veuillez réessayer.',
        'google_invalid_email' => 'Adresse e-mail invalide reçue de Google. Veuillez réessayer.',
        'account_creation_failed' => 'Impossible de créer votre compte. Veuillez réessayer ou contacter le support.',
        'session_expired' => 'La session d\'authentification a expiré. Veuillez vous reconnecter.',
        'google_communication_failed' => 'Impossible de communiquer avec Google. Veuillez réessayer plus tard.',
        'unexpected_error' => 'Une erreur inattendue s\'est produite. Veuillez réessayer.',
    ],

    // Réponses API
    'api' => [
        'unauthorized' => 'Non autorisé',
        'invalid_payload' => 'Charge utile invalide',
        'prompt_run_not_found' => 'Exécution d\'invite non trouvée',
        'question_rating_saved' => 'Évaluation de la question enregistrée avec succès.',
        'database_error' => 'Erreur de base de données',
        'internal_server_error' => 'Erreur interne du serveur',
    ],

    // Messages de confidentialité
    'privacy' => [
        'pro_required' => 'Vous devez être un abonné Pro pour activer le chiffrement de confidentialité.',
        'session_expired' => 'La session de configuration a expiré. Veuillez recommencer.',
        'recovery_mismatch' => 'Les mots de la phrase de récupération ne correspondent pas.',
        'enabled' => 'Le chiffrement de confidentialité a été activé. Vos données sont maintenant protégées.',
        'not_enabled' => 'La confidentialité n\'est pas activée pour ce compte.',
        'unlock_required' => 'Veuillez déverrouiller votre clé de confidentialité pour continuer.',
        'unlock_prompt' => 'Veuillez entrer votre mot de passe pour déverrouiller vos données chiffrées.',
        'key_unlocked' => 'Clé de confidentialité déverrouillée.',
        'incorrect_password' => 'Mot de passe incorrect.',
        'invalid_format' => 'Format de phrase de récupération invalide.',
        'recovered' => 'Compte récupéré avec succès. Votre mot de passe a été mis à jour.',
        'invalid_phrase' => 'Phrase de récupération invalide.',
        'key_updated' => 'Clé de confidentialité mise à jour avec le nouveau mot de passe.',
        'key_update_failed' => 'Impossible de mettre à jour la clé de confidentialité.',
        'not_enabled_disable' => 'La confidentialité n\'est pas activée.',
        'disabled' => 'Le chiffrement de confidentialité a été désactivé.',
    ],

    // Messages du service de flux de travail
    'workflow' => [
        'invalid_pre_analysis_response' => 'Réponse invalide du flux de travail de pré-analyse.',
        'missing_clarification_field' => 'Champ needs_clarification manquant.',
        'proceeding_to_analysis' => 'Passage direct à l\'analyse.',
        'analysis_failed' => 'Flux de travail d\'analyse échoué.',
        'analysis_exception' => 'Une erreur s\'est produite lors de l\'analyse de la tâche : {error}',
        'generation_failed' => 'Flux de travail de génération échoué.',
        'prompt_generation_exception' => 'Une erreur s\'est produite lors de la génération de l\'invite : {error}',
        'n8n_connection_failed' => 'Impossible de se connecter à n8n : {error}',
        'n8n_request_failed' => 'La demande n8n a échoué : {error}',
        'quick_queries_failed' => 'Une erreur s\'est produite lors de la génération des questions rapides : {error}',
        'unknown_error' => 'Erreur inconnue',
    ],

    // Messages d'administration
    'admin' => [
        'task_not_found' => 'Tâche non trouvée.',
    ],

    // Documents de référence
    'reference_documents' => [
        'not_found' => 'Document non trouvé : {filename}',
        'saved' => 'Document \'{filename}\' enregistré avec succès et intégré dans les flux de travail',
        'embedded' => 'Tous les {count} documents ont été intégrés avec succès dans les flux de travail',
        'invalid_type' => 'Type de document invalide : {type}',
    ],

    // Messages de localisation
    'location' => [
        'unknown' => 'Localisation inconnue',
    ],

    // Transcription vocale
    'voice' => [
        'transcription_failed' => 'Impossible de transcrire l\'audio. Veuillez réessayer.',
    ],

    // Métadonnées de l'application
    'app' => [
        'default_title' => 'BettrPrompt',
    ],
];
