<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mensajes de aplicación personalizados
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para mensajes de
    | aplicación personalizados, incluidas notificaciones de éxito/error,
    | asuntos de correo electrónico y contenido.
    |
    */

    // Mensajes de éxito
    'profile_updated' => 'Tu perfil ha sido actualizado correctamente.',
    'profile_deleted' => 'Tu perfil ha sido eliminado.',
    'password_changed' => 'Tu contraseña ha sido cambiada correctamente.',
    'email_updated' => 'Tu dirección de correo ha sido actualizada.',
    'preferences_saved' => 'Tus preferencias han sido guardadas.',
    'settings_updated' => 'Tus configuraciones han sido actualizadas.',

    // Mensajes de error
    'something_went_wrong' => 'Algo salió mal. Por favor, intenta de nuevo.',
    'unauthorized' => 'No estás autorizado para realizar esta acción.',
    'not_found' => 'El recurso solicitado no fue encontrado.',
    'workflow_failed' => 'El flujo de trabajo falló al procesar. Por favor, intenta de nuevo.',
    'rate_limited' => 'Demasiadas solicitudes. Por favor, espera un momento antes de intentar de nuevo.',
    'server_error' => 'Ocurrió un error del servidor. Por favor, intenta más tarde.',
    'invalid_request' => 'La solicitud no es válida.',
    'validation_failed' => 'Los datos proporcionados no pasaron la validación.',

    // Mensajes de flujo de trabajo
    'prompt_generating' => 'Generando tu prompt...',
    'prompt_generated' => 'Tu prompt ha sido generado correctamente.',
    'prompt_generation_failed' => 'Error al generar el prompt. Por favor, intenta de nuevo.',
    'analysis_in_progress' => 'Tu análisis está en progreso. Por favor, espera...',
    'analysis_complete' => 'Tu análisis está completo.',

    // Mensajes de correo electrónico
    'email' => [
        'password_reset_subject' => 'Reestablecer tu contraseña',
        'password_reset_title' => 'Reestablecer tu contraseña',
        'password_reset_body' => 'Recibiste este correo porque recibimos una solicitud de restablecimiento de contraseña para tu cuenta.',
        'password_reset_button' => 'Reestablecer contraseña',
        'password_reset_footer' => 'Este enlace de restablecimiento de contraseña caducará en {minutes} minutos.',
        'password_reset_footer_no_action' => 'Si no solicitaste un restablecimiento de contraseña, no se requiere más acción.',

        'welcome_subject' => 'Bienvenido a BettrPrompt',
        'welcome_title' => 'Bienvenido a BettrPrompt',
        'welcome_body' => '¡Gracias por unirte a BettrPrompt! Estamos emocionados de ayudarte a crear prompts de IA calibrados por personalidad.',
        'welcome_footer' => 'Si tienes alguna pregunta, no dudes en contactarnos.',

        'email_verification_subject' => 'Verificar tu dirección de correo',
        'email_verification_title' => 'Verificar tu dirección de correo',
        'email_verification_body' => 'Por favor, verifica tu dirección de correo haciendo clic en el botón de abajo.',
        'email_verification_button' => 'Verificar correo',
        'email_verification_footer' => 'Si no creaste esta cuenta, puedes ignorar este correo.',

        'account_suspended_subject' => 'Tu cuenta ha sido suspendida',
        'account_suspended_title' => 'Cuenta suspendida',
        'account_suspended_body' => 'Tu cuenta ha sido suspendida. Por favor, contacta con soporte para más información.',

        'password_changed_subject' => 'Tu contraseña ha sido cambiada',
        'password_changed_title' => 'Contraseña cambiada',
        'password_changed_body' => 'Tu contraseña ha sido cambiada correctamente. Si no realizaste este cambio, por favor contacta con soporte inmediatamente.',

        'greeting' => '¡Hola {name}!',
        'regards' => 'Saludos cordiales,',
        'regards_team' => 'El equipo de BettrPrompt',
    ],

    // Mensajes relacionados con validación
    'invalid_email' => 'Por favor, introduce una dirección de correo válida.',
    'email_required' => 'Por favor, proporciona tu dirección de correo.',
    'password_required' => 'Se requiere una contraseña.',
    'password_too_short' => 'Tu contraseña debe tener al menos 8 caracteres.',
    'passwords_do_not_match' => 'Las contraseñas no coinciden.',
    'name_required' => 'Por favor, proporciona tu nombre.',

    // Relacionado con cuentas
    'account_created' => 'Tu cuenta ha sido creada correctamente.',
    'account_already_exists' => 'Ya existe una cuenta con este correo.',
    'email_not_verified' => 'Por favor, verifica tu dirección de correo antes de proceder.',
    'please_login' => 'Por favor, inicia sesión para continuar.',
    'session_expired' => 'Tu sesión ha caducado. Por favor, inicia sesión de nuevo.',

    // Eliminación de datos
    'data_deletion_requested' => 'Tu solicitud de eliminación de datos ha sido enviada.',
    'data_will_be_deleted' => 'Tus datos serán eliminados permanentemente en 30 días.',
    'data_deletion_cancelled' => 'Tu solicitud de eliminación de datos ha sido cancelada.',

    // Mensajes de validación de formulario
    'form' => [
        'answer_required' => 'Por favor, proporciona una respuesta a la pregunta.',
        'answer_max' => 'La respuesta no debe exceder 1000 caracteres.',
        'task_description_required' => 'Por favor, describe la tarea que quieres lograr.',
        'task_description_min' => 'La descripción de la tarea debe tener al menos 10 caracteres.',
        'experience_level_required' => 'Por favor, selecciona tu nivel de experiencia (Pregunta 1).',
        'usefulness_required' => 'Por favor, califica qué tan útil fue la aplicación (Pregunta 2).',
        'usage_intent_required' => 'Por favor, indica tu probabilidad de usar la aplicación de nuevo (Pregunta 3).',
        'desired_features_required' => 'Por favor, selecciona al menos una característica que te gustaría ver.',
        'desired_features_other_required' => 'Por favor, describe la característica que seleccionaste en "Otro".',
        'password_delete_confirmation' => 'Por favor, introduce tu contraseña para confirmar la eliminación de cuenta.',
        'name_required' => 'Por favor, introduce tu nombre.',
        'email_required' => 'Por favor, introduce tu dirección de correo.',
        'email_email' => 'Por favor, introduce una dirección de correo válida.',
        'email_unique' => 'Esta dirección de correo ya está registrada.',
        'password_required' => 'Por favor, introduce una contraseña.',
        'password_confirmed' => 'La confirmación de contraseña no coincide.',
        'current_password' => 'contraseña actual',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
    ],

    // Mensajes del constructor de prompts
    'prompt_builder' => [
        'task_created_failed' => 'Error al crear la tarea. Por favor, intenta de nuevo.',
        'invalid_workflow_stage' => 'Etapa de flujo de trabajo no válida para enviar respuestas de pre-análisis.',
        'analysing_task' => 'Analizando tu tarea...',
        'submit_answers_failed' => 'Error al enviar las respuestas. Por favor, intenta de nuevo.',
        'no_quick_queries' => 'Esta ejecución de prompt no tiene consultas rápidas para actualizar.',
        'updating_answers' => 'Actualizando tu tarea con respuestas...',
        'update_answers_failed' => 'Error al actualizar las respuestas. Por favor, intenta de nuevo.',
        'cannot_go_back' => 'No puedes volver atrás en esta etapa.',
        'already_first_question' => 'Ya estás en la primera pregunta.',
        'go_back_failed' => 'Error al volver atrás. Por favor, intenta de nuevo.',
        'can_only_edit_completed' => 'Solo se pueden editar ejecuciones de prompts completadas.',
        'prompt_updated' => 'Prompt actualizado correctamente.',
        'update_prompt_failed' => 'Error al actualizar el prompt. Por favor, intenta de nuevo.',
        'visitor_limit_reached' => 'Ya has creado un prompt optimizado como visitante. Por favor, crea una cuenta gratuita para continuar.',
        'create_prompt_run_failed' => 'Ocurrió un error al crear la nueva ejecución de prompt. Por favor, intenta de nuevo.',
        'no_clarifying_questions' => 'La ejecución de prompt principal no tiene preguntas aclaratorias.',
        'generating_optimised_prompt' => 'Generando tu prompt optimizado con respuestas editadas...',
        'prompt_generation_start_failed' => 'Error al iniciar la generación de prompts. Por favor, intenta de nuevo.',
        'switching_framework' => 'Re-analizando con el marco seleccionado...',
        'switch_framework_failed' => 'Ocurrió un error al cambiar marcos. Por favor, intenta de nuevo.',
        'only_failed_runs_can_retry' => 'Solo se pueden reintentar las ejecuciones fallidas.',
        'retrying_pre_analysis' => 'Reintentando pre-análisis...',
        'retrying_analysis' => 'Reintentando análisis...',
        'retrying_prompt_generation' => 'Reintentando generación de prompts...',
        'cannot_retry_from_stage' => 'No se puede reintentar desde esta etapa.',
        'retry_failed' => 'Ocurrió un error al reintentar. Por favor, intenta de nuevo.',
        'deleted_successfully' => 'Ejecución de prompt eliminada correctamente.',
        'delete_failed' => 'Error al eliminar la ejecución de prompt. Por favor, intenta de nuevo.',
    ],

    // Etiquetas de tipo de personalidad
    'personality_types' => [
        'intj' => 'Arquitecto',
        'intp' => 'Lógico',
        'entj' => 'Comandante',
        'entp' => 'Debatidor',
        'infj' => 'Defensor',
        'infp' => 'Mediador',
        'enfj' => 'Protagonista',
        'enfp' => 'Activista',
        'istj' => 'Logístico',
        'isfj' => 'Defensor',
        'estj' => 'Ejecutivo',
        'esfj' => 'Cónsul',
        'istp' => 'Virtuoso',
        'isfp' => 'Aventurero',
        'estp' => 'Empresario',
        'esfp' => 'Animador',
    ],

    // Mensajes de perfil
    'profile' => [
        'account_deleted' => 'Tu cuenta ha sido eliminada.',
        'update_failed' => 'Error al actualizar el perfil. Por favor, intenta de nuevo.',
        'personality_update_failed' => 'Error al actualizar el tipo de personalidad. Por favor, intenta de nuevo.',
        'location_update_failed' => 'Error al actualizar la ubicación. Por favor, intenta de nuevo.',
        'location_detect_failed' => 'No se pudo detectar la ubicación desde tu dirección IP. Por favor, establécela manualmente.',
        'location_detection_failed' => 'Error al detectar la ubicación. Por favor, intenta de nuevo.',
        'location_clear_failed' => 'Error al limpiar la ubicación. Por favor, intenta de nuevo.',
        'professional_clear_failed' => 'Error al limpiar la información profesional. Por favor, intenta de nuevo.',
        'team_clear_failed' => 'Error al limpiar la información del equipo. Por favor, intenta de nuevo.',
        'budget_clear_failed' => 'Error al limpiar las preferencias de presupuesto. Por favor, intenta de nuevo.',
        'tools_clear_failed' => 'Error al limpiar las herramientas y tecnologías. Por favor, intenta de nuevo.',
        'professional_update_failed' => 'Error al actualizar el contexto profesional. Por favor, intenta de nuevo.',
        'team_update_failed' => 'Error al actualizar el contexto del equipo. Por favor, intenta de nuevo.',
        'budget_update_failed' => 'Error al actualizar las preferencias de presupuesto. Por favor, intenta de nuevo.',
        'tools_update_failed' => 'Error al actualizar las preferencias de herramientas. Por favor, intenta de nuevo.',
        'delete_account_failed' => 'Error al eliminar la cuenta. Por favor, contacta con soporte.',
        'unexpected_error' => 'Ocurrió un error inesperado. Por favor, contacta con soporte.',
    ],

    // Mensajes de retroalimentación
    'feedback' => [
        'thank_you' => '¡Gracias por tu retroalimentación!',
        'thank_you_update' => '¡Gracias por actualizar tu retroalimentación!',
    ],

    // Mensajes de suscripción
    'subscription' => [
        'invalid_plan' => 'Plan seleccionado no válido.',
        'prompt_limit_reached' => 'Has alcanzado tu límite mensual de prompts.',
        'prompt_limit_reached_upgrade' => 'Has alcanzado tu límite mensual de prompts. Actualiza a Pro para prompts ilimitados.',
        'welcome_pro' => '¡Bienvenido a BettrPrompt Pro!',
        'welcome_private' => '¡Bienvenido a BettrPrompt Privado! Tus datos ahora están protegidos con privacidad mejorada.',
        'checkout_cancelled' => 'El checkout de suscripción fue cancelado.',
        'cancelled_pro_until' => 'Tu suscripción ha sido cancelada. Tendrás acceso a Pro hasta {date}.',
        'resumed' => 'Tu suscripción ha sido reanudada.',
        'current_plan' => 'Plan Actual',
    ],

    // Mensajes de autenticación
    'auth' => [
        'logged_out' => 'Has cerrado sesión correctamente.',
        'logged_out_session' => 'Has cerrado sesión.',
        'admin_required' => 'No autorizado. Se requiere acceso de administrador.',
        'google_connection_failed' => 'No se pudo conectar con Google. Por favor, intenta más tarde.',
        'google_account_info_failed' => 'No se pudo recuperar tu información de cuenta de Google. Por favor, intenta de nuevo.',
        'google_invalid_email' => 'Dirección de correo no válida recibida de Google. Por favor, intenta de nuevo.',
        'account_creation_failed' => 'Error al crear tu cuenta. Por favor, intenta de nuevo o contacta con soporte.',
        'session_expired' => 'La sesión de autenticación ha caducado. Por favor, inicia sesión de nuevo.',
        'google_communication_failed' => 'Error al comunicarse con Google. Por favor, intenta más tarde.',
        'unexpected_error' => 'Ocurrió un error inesperado. Por favor, intenta de nuevo.',
    ],

    // Respuestas de API
    'api' => [
        'unauthorized' => 'No autorizado',
        'invalid_payload' => 'Carga no válida',
        'prompt_run_not_found' => 'Ejecución de prompt no encontrada',
        'database_error' => 'Error de base de datos',
        'internal_server_error' => 'Error interno del servidor',
    ],

    // Mensajes de privacidad
    'privacy' => [
        'pro_required' => 'Debes ser un suscriptor de Pro para habilitar el cifrado de privacidad.',
        'session_expired' => 'La sesión de configuración ha caducado. Por favor, comienza de nuevo.',
        'recovery_mismatch' => 'Las palabras de la frase de recuperación no coinciden.',
        'enabled' => 'El cifrado de privacidad ha sido habilitado. Tus datos están ahora protegidos.',
        'not_enabled' => 'La privacidad no está habilitada para esta cuenta.',
        'unlock_required' => 'Por favor, desbloquea tu clave de privacidad para continuar.',
        'unlock_prompt' => 'Por favor, introduce tu contraseña para desbloquear tus datos cifrados.',
        'key_unlocked' => 'Clave de privacidad desbloqueada.',
        'incorrect_password' => 'Contraseña incorrecta.',
        'invalid_format' => 'Formato no válido de frase de recuperación.',
        'recovered' => 'Cuenta recuperada correctamente. Tu contraseña ha sido actualizada.',
        'invalid_phrase' => 'Frase de recuperación no válida.',
        'key_updated' => 'Clave de privacidad actualizada con nueva contraseña.',
        'key_update_failed' => 'Error al actualizar la clave de privacidad.',
        'not_enabled_disable' => 'La privacidad no está habilitada.',
        'disabled' => 'El cifrado de privacidad ha sido deshabilitado.',
    ],

    // Mensajes de servicio de flujo de trabajo
    'workflow' => [
        'invalid_pre_analysis_response' => 'Respuesta no válida del flujo de trabajo de pre-análisis.',
        'missing_clarification_field' => 'Falta el campo needs_clarification.',
        'proceeding_to_analysis' => 'Procediendo directamente al análisis.',
        'analysis_failed' => 'El flujo de trabajo de análisis falló.',
        'analysis_exception' => 'Ocurrió un error al analizar la tarea: {error}',
        'generation_failed' => 'El flujo de trabajo de generación falló.',
        'prompt_generation_exception' => 'Ocurrió un error al generar el prompt: {error}',
        'n8n_connection_failed' => 'Error al conectar con n8n: {error}',
        'n8n_request_failed' => 'Solicitud de n8n falló: {error}',
        'quick_queries_failed' => 'Ocurrió un error al generar consultas rápidas: {error}',
        'unknown_error' => 'Error desconocido',
    ],

    // Mensajes de administrador
    'admin' => [
        'task_not_found' => 'Tarea no encontrada.',
    ],

    // Documentos de referencia
    'reference_documents' => [
        'not_found' => 'Documento no encontrado: {filename}',
        'saved' => 'Documento \'{filename}\' guardado correctamente e incrustado en flujos de trabajo',
        'embedded' => 'Todos los {count} documentos incrustados correctamente en flujos de trabajo',
        'invalid_type' => 'Tipo de documento no válido: {type}',
    ],

    // Mensajes de ubicación
    'location' => [
        'unknown' => 'Ubicación desconocida',
    ],

    // Transcripción de voz
    'voice' => [
        'transcription_failed' => 'Error al transcribir audio. Por favor, intenta de nuevo.',
    ],

    // Metadatos de la aplicación
    'app' => [
        'default_title' => 'BettrPrompt',
    ],
];
