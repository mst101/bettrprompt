<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Benutzerdefinierte Anwendungsmeldungen
    |--------------------------------------------------------------------------
    |
    | Die folgenden Sprachzeilen werden für benutzerdefinierte
    | Anwendungsmeldungen verwendet, einschliesslich
    | Erfolgs-/Fehlermeldungen sowie E-Mail-Betreffzeilen und -Inhalte.
    |
    */

    // Erfolgsmeldungen
    'profile_updated' => 'Ihr Profil wurde erfolgreich aktualisiert.',
    'profile_deleted' => 'Ihr Profil wurde gelöscht.',
    'password_changed' => 'Ihr Passwort wurde erfolgreich geändert.',
    'email_updated' => 'Ihre E-Mail-Adresse wurde aktualisiert.',
    'preferences_saved' => 'Ihre Einstellungen wurden gespeichert.',
    'settings_updated' => 'Ihre Einstellungen wurden aktualisiert.',

    // Fehlermeldungen
    'something_went_wrong' => 'Etwas ist schief gelaufen. Bitte versuchen Sie es erneut.',
    'unauthorized' => 'Sie sind nicht berechtigt, diese Aktion auszuführen.',
    'not_found' => 'Die angeforderte Ressource wurde nicht gefunden.',
    'workflow_failed' => 'Der Arbeitsablauf konnte nicht verarbeitet werden. Bitte versuchen Sie es erneut.',
    'rate_limited' => 'Zu viele Anfragen. Bitte warten Sie einen Moment, bevor Sie es erneut versuchen.',
    'server_error' => 'Ein Serverfehler ist aufgetreten. Bitte versuchen Sie es später erneut.',
    'invalid_request' => 'Die Anfrage ist ungültig.',
    'validation_failed' => 'Die bereitgestellten Daten haben die Validierung nicht bestanden.',

    // Arbeitsablauf-Meldungen
    'prompt_generating' => 'Generiere deine Anfrage...',
    'prompt_generated' => 'Ihre Anfrage wurde erfolgreich generiert.',
    'prompt_generation_failed' => 'Fehler beim Generieren der Anfrage. Bitte versuchen Sie es erneut.',
    'analysis_in_progress' => 'Ihre Analyse wird durchgeführt. Bitte warten Sie...',
    'analysis_complete' => 'Ihre Analyse ist abgeschlossen.',

    // E-Mail-Meldungen
    'email' => [
        'password_reset_subject' => 'Passwort zurücksetzen',
        'password_reset_title' => 'Passwort zurücksetzen',
        'password_reset_body' => 'Sie erhalten diese E-Mail, da wir eine Anfrage zum Zurücksetzen des Passworts für Ihr Konto erhalten haben.',
        'password_reset_button' => 'Passwort zurücksetzen',
        'password_reset_footer' => 'Dieser Link zum Zurücksetzen des Passworts verfällt in {minutes} Minuten.',
        'password_reset_footer_no_action' => 'Wenn Sie kein Passwort zurücksetzen angefordert haben, ist keine weitere Aktion erforderlich.',

        'welcome_subject' => 'Willkommen bei BettrPrompt',
        'welcome_title' => 'Willkommen bei BettrPrompt',
        'welcome_body' => 'Danke, dass Sie sich bei BettrPrompt angemeldet haben! Wir freuen uns, Ihnen dabei zu helfen, persönlichkeitsgerechte KI-Anfragen zu erstellen.',
        'welcome_footer' => 'Wenn Sie Fragen haben, zögern Sie nicht, uns zu kontaktieren.',

        'email_verification_subject' => 'Bestätigen Sie Ihre E-Mail-Adresse',
        'email_verification_title' => 'Bestätigen Sie Ihre E-Mail-Adresse',
        'email_verification_body' => 'Bitte bestätigen Sie Ihre E-Mail-Adresse, indem Sie auf die unten stehende Schaltfläche klicken.',
        'email_verification_button' => 'E-Mail bestätigen',
        'email_verification_footer' => 'Wenn Sie dieses Konto nicht erstellt haben, können Sie diese E-Mail ignorieren.',

        'account_suspended_subject' => 'Ihr Konto wurde gesperrt',
        'account_suspended_title' => 'Konto gesperrt',
        'account_suspended_body' => 'Ihr Konto wurde gesperrt. Bitte kontaktieren Sie den Support, um weitere Informationen zu erhalten.',

        'password_changed_subject' => 'Ihr Passwort wurde geändert',
        'password_changed_title' => 'Passwort geändert',
        'password_changed_body' => 'Ihr Passwort wurde erfolgreich geändert. Wenn Sie diese Änderung nicht vorgenommen haben, wenden Sie sich sofort an den Support.',

        'greeting' => 'Hallo {name}!',
        'regards' => 'Mit freundlichen Grüssen,',
        'regards_team' => 'Das BettrPrompt-Team',
    ],

    // Validierungsbezogene Meldungen
    'invalid_email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
    'email_required' => 'Bitte geben Sie Ihre E-Mail-Adresse an.',
    'password_required' => 'Ein Passwort ist erforderlich.',
    'password_too_short' => 'Ihr Passwort muss mindestens 8 Zeichen lang sein.',
    'passwords_do_not_match' => 'Die Passwörter stimmen nicht überein.',
    'name_required' => 'Bitte geben Sie Ihren Namen an.',

    // Kontobezogen
    'account_created' => 'Ihr Konto wurde erfolgreich erstellt.',
    'account_already_exists' => 'Ein Konto mit dieser E-Mail existiert bereits.',
    'email_not_verified' => 'Bitte bestätigen Sie Ihre E-Mail-Adresse, bevor Sie fortfahren.',
    'please_login' => 'Bitte melden Sie sich an, um fortzufahren.',
    'session_expired' => 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.',

    // Datenlöschung
    'data_deletion_requested' => 'Ihre Anfrage zur Datenlöschung wurde eingereicht.',
    'data_will_be_deleted' => 'Ihre Daten werden in 30 Tagen endgültig gelöscht.',
    'data_deletion_cancelled' => 'Ihre Anfrage zur Datenlöschung wurde storniert.',

    // Formularvalidierungsmeldungen
    'form' => [
        'answer_required' => 'Bitte geben Sie eine Antwort auf die Frage.',
        'answer_max' => 'Die Antwort darf nicht mehr als 1000 Zeichen überschreiten.',
        'task_description_required' => 'Bitte beschreiben Sie die Aufgabe, die Sie ausführen möchten.',
        'task_description_min' => 'Die Aufgabenbeschreibung muss mindestens 10 Zeichen lang sein.',
        'experience_level_required' => 'Bitte wählen Sie Ihr Erfahrungsniveau aus (Frage 1).',
        'usefulness_required' => 'Bitte bewerten Sie, wie nützlich die App war (Frage 2).',
        'usage_intent_required' => 'Bitte geben Sie die Wahrscheinlichkeit an, dass Sie die App erneut nutzen (Frage 3).',
        'desired_features_required' => 'Bitte wählen Sie mindestens eine Funktion aus, die Sie sehen möchten.',
        'desired_features_other_required' => 'Bitte beschreiben Sie die Funktion, die Sie unter „Sonstiges" ausgewählt haben.',
        'password_delete_confirmation' => 'Bitte geben Sie Ihr Passwort ein, um die Kontolöschung zu bestätigen.',
        'name_required' => 'Bitte geben Sie Ihren Namen ein.',
        'email_required' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.',
        'email_email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
        'email_unique' => 'Diese E-Mail-Adresse ist bereits registriert.',
        'password_required' => 'Bitte geben Sie ein Passwort ein.',
        'password_confirmed' => 'Die Passwortbestätigung stimmt nicht überein.',
        'current_password' => 'aktuelles Passwort',
        'password' => 'Passwort',
        'password_confirmation' => 'Passwortbestätigung',
    ],

    // Anfrage-Builder-Meldungen
    'prompt_builder' => [
        'task_created_failed' => 'Fehler beim Erstellen der Aufgabe. Bitte versuchen Sie es erneut.',
        'invalid_workflow_stage' => 'Ungültige Workflow-Stufe zum Einreichen von Vor-Analyse-Antworten.',
        'analysing_task' => 'Analysiere deine Aufgabe...',
        'submit_answers_failed' => 'Fehler beim Einreichen der Antworten. Bitte versuchen Sie es erneut.',
        'no_quick_queries' => 'Dieser Anfrage-Durchlauf hat keine Quick Queries zum Aktualisieren.',
        'updating_answers' => 'Aktualisiere deine Aufgabe mit Antworten...',
        'update_answers_failed' => 'Fehler beim Aktualisieren der Antworten. Bitte versuchen Sie es erneut.',
        'cannot_go_back' => 'Kann in dieser Phase nicht zurückgehen.',
        'already_first_question' => 'Sie sind bereits bei der ersten Frage.',
        'go_back_failed' => 'Fehler beim Zurückgehen. Bitte versuchen Sie es erneut.',
        'can_only_edit_completed' => 'Es können nur abgeschlossene Anfrage-Durchläufe bearbeitet werden.',
        'prompt_updated' => 'Anfrage erfolgreich aktualisiert.',
        'update_prompt_failed' => 'Fehler beim Aktualisieren der Anfrage. Bitte versuchen Sie es erneut.',
        'visitor_limit_reached' => 'Sie haben bereits eine optimierte Anfrage als Besucher erstellt. Bitte erstellen Sie ein kostenloses Konto, um fortzufahren.',
        'create_prompt_run_failed' => 'Beim Erstellen des neuen Anfrage-Durchlaufs ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.',
        'no_clarifying_questions' => 'Der übergeordnete Anfrage-Durchlauf hat keine Präzisierungsfragen.',
        'generating_optimised_prompt' => 'Generiere deine optimierte Anfrage mit bearbeiteten Antworten...',
        'prompt_generation_start_failed' => 'Fehler beim Starten der Anfragegenerierung. Bitte versuchen Sie es erneut.',
        'switching_framework' => 'Analysiere mit ausgewähltem Rahmenwerk erneut...',
        'switch_framework_failed' => 'Beim Wechsel des Rahmenwerks ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.',
        'only_failed_runs_can_retry' => 'Nur fehlgeschlagene Durchläufe können wiederholt werden.',
        'retrying_pre_analysis' => 'Wiederhole Vor-Analyse...',
        'retrying_analysis' => 'Wiederhole Analyse...',
        'retrying_prompt_generation' => 'Wiederhole Anfragegenerierung...',
        'cannot_retry_from_stage' => 'Kann von dieser Stufe aus nicht wiederholen.',
        'retry_failed' => 'Beim Wiederholen ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.',
        'deleted_successfully' => 'Anfrage-Durchlauf erfolgreich gelöscht.',
        'delete_failed' => 'Fehler beim Löschen des Anfrage-Durchlaufs. Bitte versuchen Sie es erneut.',
    ],

    // Persönlichkeitstyp-Bezeichnungen
    'personality_types' => [
        'intj' => 'Architekt',
        'intp' => 'Logiker',
        'entj' => 'Befehlshaber',
        'entp' => 'Debattierer',
        'infj' => 'Befürworter',
        'infp' => 'Vermittler',
        'enfj' => 'Protagonist',
        'enfp' => 'Kampagnenführer',
        'istj' => 'Logistiker',
        'isfj' => 'Verteidiger',
        'estj' => 'Geschäftsführer',
        'esfj' => 'Konsul',
        'istp' => 'Virtuose',
        'isfp' => 'Abenteurer',
        'estp' => 'Unternehmer',
        'esfp' => 'Entertainer',
    ],

    // Profilmeldungen
    'profile' => [
        'account_deleted' => 'Ihr Konto wurde gelöscht.',
        'update_failed' => 'Fehler beim Aktualisieren des Profils. Bitte versuchen Sie es erneut.',
        'personality_update_failed' => 'Fehler beim Aktualisieren des Persönlichkeitstyps. Bitte versuchen Sie es erneut.',
        'location_update_failed' => 'Fehler beim Aktualisieren des Standorts. Bitte versuchen Sie es erneut.',
        'location_detect_failed' => 'Standort konnte anhand Ihrer IP-Adresse nicht erkannt werden. Bitte legen Sie ihn manuell fest.',
        'location_detection_failed' => 'Fehler beim Erkennen des Standorts. Bitte versuchen Sie es erneut.',
        'location_clear_failed' => 'Fehler beim Löschen des Standorts. Bitte versuchen Sie es erneut.',
        'professional_clear_failed' => 'Fehler beim Löschen der Berufsangaben. Bitte versuchen Sie es erneut.',
        'team_clear_failed' => 'Fehler beim Löschen der Teaminformationen. Bitte versuchen Sie es erneut.',
        'budget_clear_failed' => 'Fehler beim Löschen der Budgeteinstellungen. Bitte versuchen Sie es erneut.',
        'tools_clear_failed' => 'Fehler beim Löschen der Tools & Technologien. Bitte versuchen Sie es erneut.',
        'professional_update_failed' => 'Fehler beim Aktualisieren des beruflichen Kontexts. Bitte versuchen Sie es erneut.',
        'team_update_failed' => 'Fehler beim Aktualisieren der Teamkontexte. Bitte versuchen Sie es erneut.',
        'budget_update_failed' => 'Fehler beim Aktualisieren der Budgeteinstellungen. Bitte versuchen Sie es erneut.',
        'tools_update_failed' => 'Fehler beim Aktualisieren der Tool-Einstellungen. Bitte versuchen Sie es erneut.',
        'delete_account_failed' => 'Fehler beim Löschen des Kontos. Bitte kontaktieren Sie den Support.',
        'unexpected_error' => 'Ein unerwarteter Fehler ist aufgetreten. Bitte kontaktieren Sie den Support.',
    ],

    // Feedback-Meldungen
    'feedback' => [
        'thank_you' => 'Danke für dein Feedback!',
        'thank_you_update' => 'Danke für die Aktualisierung deines Feedbacks!',
    ],

    // Abonnement-Meldungen
    'subscription' => [
        'invalid_plan' => 'Ungültiger Plan ausgewählt.',
        'prompt_limit_reached' => 'Sie haben Ihr monatliches Anfrage-Limit erreicht.',
        'prompt_limit_reached_upgrade' => 'Sie haben Ihr monatliches Anfrage-Limit erreicht. Aktualisieren Sie auf Pro für unbegrenzte Anfragen.',
        'welcome_pro' => 'Willkommen bei BettrPrompt Pro!',
        'welcome_private' => 'Willkommen bei BettrPrompt Privat! Ihre Daten sind jetzt mit verbesserter Privatsphäre geschützt.',
        'checkout_cancelled' => 'Abonnement-Kasse wurde storniert.',
        'cancelled_pro_until' => 'Ihr Abonnement wurde storniert. Sie haben Pro-Zugang bis {date}.',
        'resumed' => 'Ihr Abonnement wurde wiederaufgenommen.',
        'current_plan' => 'Aktueller Plan',
    ],

    // Authentifizierungsmeldungen
    'auth' => [
        'logged_out' => 'Sie wurden erfolgreich abgemeldet.',
        'logged_out_session' => 'Sie wurden abgemeldet.',
        'admin_required' => 'Nicht berechtigt. Administratorzugriff erforderlich.',
        'google_connection_failed' => 'Verbindung zu Google konnte nicht hergestellt werden. Bitte versuchen Sie es später erneut.',
        'google_account_info_failed' => 'Ihre Kontoinformationen konnten von Google nicht abgerufen werden. Bitte versuchen Sie es erneut.',
        'google_invalid_email' => 'Ungültige E-Mail-Adresse von Google erhalten. Bitte versuchen Sie es erneut.',
        'account_creation_failed' => 'Fehler beim Erstellen Ihres Kontos. Bitte versuchen Sie es erneut oder kontaktieren Sie den Support.',
        'session_expired' => 'Authentifizierungssitzung abgelaufen. Bitte melden Sie sich erneut an.',
        'google_communication_failed' => 'Kommunikation mit Google fehlgeschlagen. Bitte versuchen Sie es später erneut.',
        'unexpected_error' => 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
    ],

    // API-Antworten
    'api' => [
        'unauthorized' => 'Nicht berechtigt',
        'invalid_payload' => 'Ungültige Nutzlast',
        'prompt_run_not_found' => 'Anfrage-Durchlauf nicht gefunden',
        'question_rating_saved' => 'Fragenbewertung erfolgreich gespeichert.',
        'database_error' => 'Datenbankfehler',
        'internal_server_error' => 'Interner Serverfehler',
    ],

    // Datenschutzmeldungen
    'privacy' => [
        'pro_required' => 'Sie müssen ein Pro-Abonnent sein, um die Datenschutzverschlüsselung zu aktivieren.',
        'session_expired' => 'Setup-Sitzung abgelaufen. Bitte starten Sie erneut.',
        'recovery_mismatch' => 'Wiederherstellungsphrasenwörter stimmen nicht überein.',
        'enabled' => 'Datenschutzverschlüsselung wurde aktiviert. Ihre Daten sind jetzt geschützt.',
        'not_enabled' => 'Datenschutz ist für dieses Konto nicht aktiviert.',
        'unlock_required' => 'Bitte entsperren Sie Ihren Datenschutzschlüssel, um fortzufahren.',
        'unlock_prompt' => 'Bitte geben Sie Ihr Passwort ein, um Ihre verschlüsselten Daten zu entsperren.',
        'key_unlocked' => 'Datenschutzschlüssel entsperrt.',
        'incorrect_password' => 'Falsches Passwort.',
        'invalid_format' => 'Ungültiges Format der Wiederherstellungsphrase.',
        'recovered' => 'Konto erfolgreich wiederhergestellt. Ihr Passwort wurde aktualisiert.',
        'invalid_phrase' => 'Ungültige Wiederherstellungsphrase.',
        'key_updated' => 'Datenschutzschlüssel mit neuem Passwort aktualisiert.',
        'key_update_failed' => 'Fehler beim Aktualisieren des Datenschutzschlüssels.',
        'not_enabled_disable' => 'Datenschutz ist nicht aktiviert.',
        'disabled' => 'Datenschutzverschlüsselung wurde deaktiviert.',
    ],

    // Workflow-Service-Meldungen
    'workflow' => [
        'invalid_pre_analysis_response' => 'Ungültige Antwort von der Vor-Analyse-Workflow.',
        'missing_clarification_field' => 'Fehlendes Feld needs_clarification.',
        'proceeding_to_analysis' => 'Fahre direkt mit der Analyse fort.',
        'analysis_failed' => 'Analyse-Workflow fehlgeschlagen.',
        'analysis_exception' => 'Fehler beim Analysieren der Aufgabe: {error}',
        'generation_failed' => 'Generierungs-Workflow fehlgeschlagen.',
        'prompt_generation_exception' => 'Fehler beim Generieren der Anfrage: {error}',
        'n8n_connection_failed' => 'Verbindung zu n8n fehlgeschlagen: {error}',
        'n8n_request_failed' => 'n8n-Anfrage fehlgeschlagen: {error}',
        'quick_queries_failed' => 'Fehler beim Generieren von Quick Queries: {error}',
        'unknown_error' => 'Unbekannter Fehler',
    ],

    // Admin-Meldungen
    'admin' => [
        'task_not_found' => 'Aufgabe nicht gefunden.',
    ],

    // Referenzdokumente
    'reference_documents' => [
        'not_found' => 'Dokument nicht gefunden: {filename}',
        'saved' => 'Dokument \'{filename}\' erfolgreich gespeichert und in Workflows eingebettet',
        'embedded' => 'Alle {count} Dokumente erfolgreich in Workflows eingebettet',
        'invalid_type' => 'Ungültiger Dokumenttyp: {type}',
    ],

    // Standort-Meldungen
    'location' => [
        'unknown' => 'Unbekannter Standort',
    ],

    // Sprachtranskription
    'voice' => [
        'transcription_failed' => 'Fehler beim Transkribieren von Audio. Bitte versuchen Sie es erneut.',
    ],

    // App-Metadaten
    'app' => [
        'default_title' => 'BettrPrompt',
    ],
];
