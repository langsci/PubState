<?php

/**
 * @file plugins/generic/pubState/PubStatePlugin.inc.php
 *
 * Copyright (c) 2022 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubStatePlugin
 */

import('lib.pkp.classes.plugins.GenericPlugin');

define('PUB_STATE_FORTHCOMING', 1);
define('PUB_STATE_PUBLISHED', 2);
define('PUB_STATE_SUPERSEDED', 3);

use \PKP\components\forms\FieldSelect;

class PubStatePlugin extends GenericPlugin
{
    /**
     * Register the plugin.
     * @param $category string
     * @param $path string
     * @param $mainContextId strinf
     */
    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {
                HookRegistry::register('Form::config::before', array($this, 'addFormField'));
                HookRegistry::register('Schema::get::publication', function($hookName, $args) {
                    $schema = $args[0];
                    $schema->properties->pubState = (object) [
                        'type' => 'integer',
                        'apiSummary' => true,
                        'multilingual' => false,
                        'validation' => ["in:".PUB_STATE_FORTHCOMING.",2,3"],
                        "default" => PUB_STATE_FORTHCOMING
                    ];
                    return false;
                });
                
            }
            return true;
        }
        return false;
    }

    function addFormField($hookName, $args) {
        $form = $args;

        if ($args->id == FORM_TITLE_ABSTRACT) {
            $submission = $this->getCurrentSubmission();
            $publication = $submission->getCurrentPublication();
            if (!$publication->getData('pubState')) {
                $publication = Services::get('publication')->edit($publication, ['pubState' => 1], $request);
            }

            $form->addField(new FieldSelect('pubState', [
				'label' => __('plugins.generic.pubState.fieldLabel'),
				'value' => $publication->getData('pubState'),
				'options' => [
                    ['value' => PUB_STATE_FORTHCOMING, 'label' => __('plugins.generic.pubState.label.forthcoming')],
                    ['value' => PUB_STATE_PUBLISHED, 'label' => __('plugins.generic.pubState.label.published')],
                    ['value' => PUB_STATE_SUPERSEDED, 'label' => __('plugins.generic.pubState.label.superseded')]
                ]
			]), [FIELD_POSITION_BEFORE, 'prefix']);
        }
    }

    function getPubStateLabel($submission) {

        if (!$submission) {
            $submission = $this->getCurrentSubmission();
        }
        $publication = $submission->getCurrentPublication();

        switch ($publication->getData('pubState')) {
            case PUB_STATE_FORTHCOMING:
                $pubStateLabel = __('plugins.generic.pubState.label.forthcoming');
                break;
            case PUB_STATE_SUPERSEDED:
                $pubStateLabel = __('plugins.generic.pubState.label.superseded');
                break;
            default:
                $pubStateLabel = "";
        }
        return $pubStateLabel;
    }

    function getCurrentSubmission() {
        // extract submission ID from current request object
        // we require this information to generate the request URL for our grid handler
        // previously this was provided as request variable
        // with OMP 3.2 this information is part of the URL path
        // we need to extract it ourselves (at least I didn't find a fuction to do it from the plugin scope)
        $request = Application::get()->getRequest();
        $matches = [];
        preg_match('#index/(\d+)/(\d+)#',$request->getRequestPath(),$matches);
        if (count($matches) == 3) {
            $submissionId = (int)$matches[1];
            //$stageId = (int)$matches[2];
        } else {
            return false;
        }
        return Services::get('submission')->get($submissionId);;
    }

	/**
	 * @copydoc PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.pubState.displayName');
	}

	/**
	 * @copydoc PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.pubState.description');
	}	
}
?>
