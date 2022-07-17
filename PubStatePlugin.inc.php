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
                        'validation' => ["in:".PUB_STATE_FORTHCOMING.",".PUB_STATE_PUBLISHED.",".PUB_STATE_SUPERSEDED.""],
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

        import('lib.pkp.classes.components.forms.publication.PKPTitleAbstractForm');
        if ($args->id == FORM_TITLE_ABSTRACT) {
            $submission = $this->getCurrentSubmission($form->action);
            
            if (!$submission) return; // submission id could not be extracted

            $publication = $submission->getCurrentPublication();
            if (!$publication->getData('pubState')) {
                $publication = Services::get('publication')->edit($publication, ['pubState' => 1], Application::get()->getRequest());
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

        $publication = $submission->getCurrentPublication();

        switch ($publication->getData('pubState')) {
            case PUB_STATE_FORTHCOMING:
                $pubStateLabel = __('plugins.generic.pubState.label.forthcoming').": ";
                break;
            case PUB_STATE_SUPERSEDED:
                $pubStateLabel = __('plugins.generic.pubState.label.superseded').": ";
                break;
            default:
                $pubStateLabel = "";
        }
        return $pubStateLabel;
    }

    function getPubState($submission) {
        $publication = $submission->getCurrentPublication();
        return $publication->getData('pubState');
    }

    function loadStyleSheet($request, $templateMgr) {
        $templateMgr->addStyleSheet(
            'pubStatePluginStylesheet',
            $request->getBaseUrl() . '/plugins/generic/pubState/css/pubStatePlugin.css',
            array(
                'priority' => STYLE_SEQUENCE_LAST
            )
        );
        $templateMgr->setConstants([
			'PUB_STATE_FORTHCOMING',
			'PUB_STATE_PUBLISHED',
            'PUB_STATE_SUPERSEDED'
        ]);
    }

    function getCurrentSubmission($action) {
        // extract submission ID from the action URL
        $matches = [];
        preg_match('#submissions/(\d+)/publications#',$action,$matches);
        if (count($matches) == 2) {
            $submissionId = (int)$matches[1];
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
