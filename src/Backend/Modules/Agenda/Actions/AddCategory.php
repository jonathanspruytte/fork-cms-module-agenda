<?php

namespace Backend\Modules\Agenda\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\File\File;


use Backend\Core\Engine\Base\ActionAdd as BackendBaseActionAdd;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Engine\Meta as BackendMeta;
use Backend\Core\Engine\Language as BL;
use Backend\Modules\Agenda\Engine\Model as BackendAgendaModel;
 
/**
 * This is the add action, it will display a form to create a new category.
 * @author Tim van Wolfswinkel <tim@webleads.nl>
 * @author Bram De Smyter <bram@bubblefish.be>
 */
class AddCategory extends BackendBaseActionAdd
{
	/**
	 * Execute the action
	 */
	public function execute()
	{
		// only one category allowed, so we redirect
		if(!BackendModel::getModuleSetting('agenda', 'allow_multiple_categories', true)) $this->redirect(BackendModel::createURLForAction('categories') . '&error=only-one-category-allowed');

		parent::execute();
		$this->loadForm();
		$this->validateForm();
		$this->parse();
		$this->display();
	}

	/**
	 * Load the form
	 */
	private function loadForm()
	{
		$this->frm = new BackendForm('addCategory');
		$this->frm->addText('title');
		
		$this->meta = new BackendMeta($this->frm, null, 'title', true);
	}

	/**
	 * Validate the form
	 */
	private function validateForm()
	{
		if($this->frm->isSubmitted())
		{
			$this->meta->setURLCallback('Backend\Modules\Agenda\Engine\Model', 'getURLForCategory');

			$this->frm->cleanupFields();

			// validate fields
			$this->frm->getField('title')->isFilled(BL::err('TitleIsRequired'));
							
			// validate meta
			$this->meta->validate();
			

			if($this->frm->isCorrect())
			{
				// build item
				$item['title'] = $this->frm->getField('title')->getValue();
				$item['language'] = BL::getWorkingLanguage();
				$item['meta_id'] = $this->meta->save();
				$item['sequence'] = BackendAgendaModel::getMaximumCategorySequence() + 1;
				
				
				// save the data
				$item['id'] = BackendAgendaModel::insertCategory($item);
				BackendModel::triggerEvent($this->getModule(), 'after_add_category', array('item' => $item));

				// everything is saved, so redirect to the overview
				$this->redirect(BackendModel::createURLForAction('categories') . '&report=added-category&var=' . urlencode($item['title']) . '&highlight=row-' . $item['id']);
			}
		}
	}
}
