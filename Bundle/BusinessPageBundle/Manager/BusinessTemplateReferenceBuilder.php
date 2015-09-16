<?php

namespace Victoire\Bundle\BusinessPageBundle\Manager;

use Doctrine\ORM\EntityManager;
use Victoire\Bundle\BusinessEntityBundle\Helper\BusinessEntityHelper;
use Victoire\Bundle\BusinessPageBundle\Builder\BusinessPageBuilder;
use Victoire\Bundle\BusinessPageBundle\Entity\BusinessPage;
use Victoire\Bundle\BusinessPageBundle\Entity\BusinessTemplate;
use Victoire\Bundle\BusinessPageBundle\Helper\BusinessPageHelper;
use Victoire\Bundle\BusinessPageBundle\Manager\Interfaces\BusinessPageReferenceBuilderInterface;
use Victoire\Bundle\BusinessPageBundle\Manager\Interfaces\BusinessTemplateReferenceBuilderInterface;
use Victoire\Bundle\CoreBundle\Entity\View;
use Victoire\Bundle\CoreBundle\Helper\UrlBuilder;
use Victoire\Bundle\CoreBundle\Helper\ViewCacheHelper;
use Victoire\Bundle\CoreBundle\Helper\ViewReferenceHelper;
use Victoire\Bundle\CoreBundle\Manager\BaseReferenceBuilder;

/**
* BusinessTemplateReferenceBuilder
*/
class BusinessTemplateReferenceBuilder extends BaseReferenceBuilder
{
    protected $virtualBusinessPageReferenceBuilder;
    protected $businessEntityHelper;
    protected $businessEntityPageHelper;

    /**
     * @param ViewReferenceHelper $viewReferenceHelper
     * @param UrlBuilder $urlBuilder
     * @param VirtualBusinessPageReferenceBuilder $virtualBusinessPageReferenceBuilder
     * @param BusinessPageBuilder $businessEntityPageBuilder
     * @param BusinessPageHelper $businessEntityPageHelper
     */
    public function __construct(
        ViewReferenceHelper $viewReferenceHelper,
        UrlBuilder $urlBuilder,
        VirtualBusinessPageReferenceBuilder $virtualBusinessPageReferenceBuilder,
        BusinessPageBuilder $businessEntityPageBuilder,
        BusinessPageHelper $businessEntityPageHelper
    )
    {
        parent::__construct($viewReferenceHelper, $urlBuilder);
        $this->virtualBusinessPageReferenceBuilder = $virtualBusinessPageReferenceBuilder;
        $this->businessEntityPageBuilder = $businessEntityPageBuilder;
        $this->businessEntityPageHelper = $businessEntityPageHelper;
    }

    public function buildReference(View $view, EntityManager $em = null)
    {
        $viewsReferences = [];
        $entities = $this->businessEntityPageHelper->getEntitiesAllowed($view, $em);

        // for each business entity
        foreach ($entities as $entity) {
            $currentPattern = clone $view;
            $page = $this->businessEntityPageBuilder->generateEntityPageFromPattern($currentPattern, $entity);
            $this->businessEntityPageBuilder->updatePageParametersByEntity($page, $entity);

            $viewsReferences = array_merge($viewsReferences, $this->virtualBusinessPageReferenceBuilder->buildReference($page, $em));

            //I refresh this partial entity from em. If I don't do it, everytime I'll request this entity from em it'll be partially populated
            $em->refresh($entity);
        }


        return $viewsReferences;
    }
}