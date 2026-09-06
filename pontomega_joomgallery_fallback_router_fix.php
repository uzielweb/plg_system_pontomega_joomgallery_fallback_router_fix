<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.pontomega_joomgallery_fallback_router_fix
 *
 * @copyright   (C) 2026 Ponto Mega. Todos os direitos reservados.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\String\StringHelper;

/**
 * Plugin to fix JoomGallery bogus router fallback to gallery view on missing segments.
 *
 * @since  1.0.0
 */
class PlgSystemPontomega_Joomgallery_Fallback_Router_Fix extends CMSPlugin
{
    /**
     * Affects constructor behavior.
     *
     * @var    boolean
     */
    protected $autoloadLanguage = true;

    /**
     * Event triggered after the framework has resolved the SEF route.
     *
     * @return  void
     * @throws  \Exception
     */
    public function onAfterRoute()
    {
        $app = Factory::getApplication();

        // Only run on frontend site
        if (!$app->isClient('site'))
        {
            return;
        }

        // Only inspect com_joomgallery requests
        if ($app->input->get('option') !== 'com_joomgallery')
        {
            return;
        }

        $view = $app->input->get('view');

        // If the view is not 'gallery', it resolved to a specific view/category/image
        if ($view !== 'gallery')
        {
            return;
        }

        $uri  = Uri::getInstance();
        $root = Uri::root(true);
        $path = trim(str_replace($root, '', $uri->getPath()), '/');

        $active    = $app->getMenu()->getActive();
        $menuRoute = $active ? trim($active->route, '/') : '';

        // Determine if the URL contained extra subsegments beyond the menu item route
        $subsegments = '';
        if ($menuRoute !== '' && strpos($path, $menuRoute) === 0)
        {
            $subsegments = trim(substr($path, strlen($menuRoute)), '/');
        }
        elseif ($menuRoute === '' || strpos($path, $menuRoute) === false)
        {
            $subsegments = $path;
        }

        // If there are no extra subsegments, it is a legitimate gallery home request
        if ($subsegments === '')
        {
            return;
        }

        // JoomGallery bogus fallback detected: URL has subsegments but resolved to generic 'gallery'!
        // 1. Check if there is a published redirect in Joomla's native redirect table
        if ($this->params->get('check_redirects', 1))
        {
            $this->checkAndRedirect($path);
        }

        // 2. If no redirect matched, trigger a legitimate 404 error
        throw new \Exception(Text::_('JGLOBAL_RESOURCE_NOT_FOUND'), 404);
    }

    /**
     * Checks Joomla's native redirect links table (#__redirect_links) for matching rules.
     *
     * @param   string  $path  Normalized request path relative to Joomla root
     *
     * @return  void
     */
    protected function checkAndRedirect($path = '')
    {
        $app = Factory::getApplication();
        $uri = Uri::getInstance();
        $db  = Factory::getDbo();

        $orgurl             = rawurldecode($uri->toString(array('scheme', 'host', 'port', 'path', 'query', 'fragment')));
        $orgurlRel          = rawurldecode($uri->toString(array('path', 'query', 'fragment')));
        $orgurlRootRel      = str_replace(Uri::root(), '', $orgurl);
        $orgurlRootRelSlash = str_replace(Uri::root(), '/', $orgurl);

        $url             = StringHelper::strtolower($orgurl);
        $urlRel          = StringHelper::strtolower($orgurlRel);
        $urlRootRel      = StringHelper::strtolower($orgurlRootRel);
        $urlRootRelSlash = StringHelper::strtolower($orgurlRootRelSlash);

        $candidates = array(
            $url,
            $urlRel,
            $urlRootRel,
            $urlRootRelSlash,
            $orgurl,
            $orgurlRel,
            $orgurlRootRel,
            $orgurlRootRelSlash,
        );

        if (!empty($path))
        {
            $pathTrimmed = trim($path, '/');
            $candidates[] = $pathTrimmed;
            $candidates[] = '/' . $pathTrimmed;
            $candidates[] = StringHelper::strtolower($pathTrimmed);
            $candidates[] = '/' . StringHelper::strtolower($pathTrimmed);
        }

        $candidates = array_unique(array_filter($candidates));

        $query = $db->getQuery(true)
            ->select($db->quoteName(array('new_url', 'header')))
            ->from($db->quoteName('#__redirect_links'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('old_url') . ' IN (' . implode(',', array_map(array($db, 'quote'), $candidates)) . ')');

        $db->setQuery($query, 0, 1);
        $redirect = $db->loadObject();

        if ($redirect && !empty($redirect->new_url))
        {
            $code = (int) ($redirect->header ?: $this->params->get('redirect_code', 301));
            if ($code < 300 || $code > 399)
            {
                $code = 301;
            }

            $app->redirect($redirect->new_url, $code);
        }
    }
}

// Aliases for Joomla class loader variations
if (!class_exists('PlgSystemPontomega_joomgallery_fallback_router_fix', false))
{
    class_alias('PlgSystemPontomega_Joomgallery_Fallback_Router_Fix', 'PlgSystemPontomega_joomgallery_fallback_router_fix');
}
if (!class_exists('PlgSystemPontomegaJoomgalleryFallbackRouterFix', false))
{
    class_alias('PlgSystemPontomega_Joomgallery_Fallback_Router_Fix', 'PlgSystemPontomegaJoomgalleryFallbackRouterFix');
}
