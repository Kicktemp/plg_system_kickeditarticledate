<?php


defined('_JEXEC') or die('Restricted access');

/**
* KickEditarticledate System Plugin
*/
class plgSystemKickEditarticledate extends JPlugin
{
    protected $app;

    public function __construct(& $subject, $config) {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }


    public function onContentPrepareForm($form, $data)
    {
        $context = $form->getName();

        if ($context == 'com_content.articles.filter')
        {
            $user  = JFactory::getUser();

            if ($user->authorise('core.edit', 'com_content'))
            {
                JToolBarHelper::custom('articles.editdate', 'calendar.png', 'calendar.png', 'Edit Date', true);
            }
        }
    }

    public function onAfterRoute()
    {
        $user   = JFactory::getUser();
        $option = $this->app->input->getCmd('option', '');
        $view = $this->app->input->getCmd('view', '');
        $task    = $this->app->input->getCmd('task', '');
        $ids    = $this->app->input->get('cid', array(), 'array');

        if ($view == 'articles' && $option == 'com_content' && $task == 'articles.editdate')
        {
            // Access checks.
            foreach ($ids as $i => $id)
            {
                if (!$user->authorise('core.edit', 'com_content.article.' . (int) $id))
                {
                    // Prune items that you can't change.
                    unset($ids[$i]);
                    JError::raiseNotice(403, JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
                }
            }

            if (empty($ids))
            {
                JError::raiseWarning(500, JText::_('JERROR_NO_ITEMS_SELECTED'));
            }
            else
            {
                $date = JFactory::getDate();
                $db = JFactory::getDbo();
                $query = $db->getQuery(true)
                    ->update('#__content')
                    ->set('publish_up = "' . $date->toSql() . '"')
                    ->where('id IN (' . implode(',', $ids) . ')');
                $db->setQuery($query);

                try
                {
                    $db->execute();
                }
                catch (JDatabaseExceptionExecuting $e)
                {
                    JError::raiseError(500, $e->getMessage());
                }

                $message = JText::plural('PLG_SYSTEM_KICKEDITARTICLEDATE_N_ITEMS_EDITDATE', count($ids));
            }

            $this->app->redirect(JRoute::_('index.php?option=com_content&view=articles', false), $message);
        }


    }
}
