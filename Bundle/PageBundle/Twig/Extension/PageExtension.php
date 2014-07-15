<?php

namespace Victoire\Bundle\PageBundle\Twig\Extension;

use Victoire\Bundle\CoreBundle\Menu\MenuManager;
use Victoire\Bundle\CoreBundle\Widget\Managers\WidgetManager;
use Victoire\Bundle\CoreBundle\Template\TemplateMapper;
use Symfony\Component\Security\Core\SecurityContext;
use Victoire\Bundle\PageBundle\Entity\Page;
use Victoire\Bundle\BusinessEntityTemplateBundle\Entity\BusinessEntityTemplate;
use Victoire\Bundle\CoreBundle\Entity\Widget;
use Victoire\Bundle\CoreBundle\Form\WidgetType;
use Victoire\Bundle\CoreBundle\Helper\WidgetHelper;
use Victoire\Bundle\PageBundle\WidgetMap\WidgetMapBuilder;
use Victoire\Bundle\CoreBundle\Handler\WidgetExceptionHandler;
use Doctrine\ORM\EntityManager;
use Victoire\Bundle\BusinessEntityTemplateBundle\Helper\BusinessEntityTemplateHelper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 *
 * @author thomas
 *
 */
class PageExtension extends \Twig_Extension
{
    protected $businessEntityTemplateHelper = null;
    protected $router = null;

    /**
     * Constructor
     *
     * @param BusinessEntityTemplateHelper $businessEntityTemplateHelper
     * @param Router                       $router
     */
    public function __construct(BusinessEntityTemplateHelper $businessEntityTemplateHelper, Router $router)
    {
        $this->businessEntityTemplateHelper = $businessEntityTemplateHelper;
        $this->router = $router;
    }

    /**
     * register twig functions
     *
     * @return array The list of extensions
     */
    public function getFunctions()
    {
        return array(
            'cms_page_business_template_sitemap' => new \Twig_Function_Method($this, 'cmsPageBusinessTemplateSiteMap', array('is_safe' => array('html'))),
            'cms_page_sitemap' => new \Twig_Function_Method($this, 'cmsPageSiteMap', array('is_safe' => array('html')))
        );
    }

    /**
     * register twig filters
     *
     * @return array The list of filters
     */
    public function getFilters()
    {
        return array();
    }

    /**
     * get extension name
     *
     * @return string The name
     */
    public function getName()
    {
        return 'cms_page';
    }

    /**
     * Get the link for a page in the sitemap
     *
     * @param Page $page
     *
     * @return string The html
     */
    public function cmsPageSiteMap(Page $page)
    {
        $html = '';

        $pageId = $page->getId();
        $pageUrl = $page->getUrl();
        $pageTitle = $page->getTitle();

        $router = $this->router;

        $url = $router->generate('victoire_core_page_show', array('url' => $pageUrl));

        $html = '<li id="'.$pageId.'"><div><a href="'.$url.'" title="'.$url.'">'.$pageTitle.'</a></div>';

        return $html;
    }

    /**
     * Get the ol li for the generated page of a business Entity template
     *
     * @param Page $page
     *
     * @return string The html
     */
    public function cmsPageBusinessTemplateSiteMap(Page $page)
    {
        $html = '';

        $urls = array();

        //the template link to the page
        $businessEntityTemplate = $page;

        //
        if ($page instanceof BusinessEntityTemplate) {
            //get the list of url of the children to avoid to have it twice.
            $childrenUrls = $this->getChildrenUrls($page);

            //services
            $businessEntityTemplateHelper = $this->businessEntityTemplateHelper;

            //the items allowed for the template
            $items = $businessEntityTemplateHelper->getEntitiesAllowed($businessEntityTemplate);

            //parse entities
            foreach ($items as $item) {
                $pageEntity = clone $businessEntityTemplate;

                //update url using the entity instance
                $businessEntityTemplateHelper->updatePageUrlByEntity($pageEntity, $item);

                $url = $pageEntity->getUrl();

                //if the url does no exists in the children
                if (!in_array($url, $childrenUrls)) {
                    $generated = true;
                } else {
                    $generated = false;
                }

                $itemsToAdd[$url] = array(
                    'item'      => $item,
                    'url'       => $url,
                    'itemId'    => $url,
                    'generated' => $generated
                );

                unset($url);
            }

            //render the ol li
            $html .= '<ol>';
            foreach ($itemsToAdd as $item) {
                $itemUrl = $item['url'];
                $itemId = $item['itemId'];
                $generated = $item['generated'];

                if ($generated) {
                    $class = 'generated';
                } else {
                    $class = '';
                }

                $html .= "<li><div class='".$class."'><a href='/".$itemUrl."' title='".$itemId."'>".$itemId."</a></div>";
            }
            $html .= '</ol>';
        }

        return $html;
    }

    /**
     * Get the list of urls of the children
     *
     * @param Page $page
     *
     * @return aray of strings The list of urls
     */
    protected function getChildrenUrls(Page $page)
    {
        $urls = array();

        $children = $page->getPages();

        //parse the children
        foreach ($children as $child) {
            $url = $child->getUrl();
            $urls[] = $url;

            unset($url);
        }

        return $urls;
    }
}
