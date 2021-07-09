<?php
/**
 * @Author: Sebastian Castro
 * @Date:   2017-03-28 15:29:03
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-07-08 12:52:02
 */

namespace App\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Form\Type\CollectionType;
use App\Form\CategoryLiteType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Helper\GoGoHelper;

class OptionAdmin extends GoGoAbstractAdmin
{
    protected $baseRouteName = 'admin_app_option';
    protected $baseRoutePattern = 'admin_app_option';

    public function createQuery($context = 'list')
    {
        $query = parent::createQuery($context);

        return $query;
    }

    public function getTemplate($name)
    {
        switch ($name) {
         case 'edit': return 'admin/edit/edit_option_category.html.twig';
             break;
         default: return parent::getTemplate($name);
             break;
     }
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        // prevent circular reference, i.e setting a child as parent
        $dm = GoGoHelper::getDmFromAdmin($this);
        $repo = $dm->get('Category');
        $parentQuery = null;
        if ($this->subject) {
          $parentQuery = $repo->createQueryBuilder()
                          ->field('id')->notIn($this->subject->getAllSubcategoriesIds());
        }

        $formMapper
       ->tab('main')
         ->halfPanel('primary')
            ->add('name', null, ['required' => true])
            ->add('color', null, ['attr' => ['class' => 'gogo-color-picker']])
            ->add('icon', null, ['attr' => ['class' => 'gogo-icon-picker']])
            ->add('parent', ModelType::class, [
              'class' => 'App\Document\Category',
              'required' => true,
              'query' => $parentQuery,
              'mapped' => true, ], [])
         ->end()
         ->halfPanelDefault('secondary')
            ->add('useIconForMarker')
            ->add('useColorForMarker')
         ->end()
         ->halfPanelDefault('display')
            ->add('displayInMenu')
            ->add('displayInInfoBar')
            ->add('displayInForm')
         ->end()
         ->panel('subcategories', array('class' => 'col-xs-12 sub-categories-container'))
            ->add('subcategories', CollectionType::class, array(
              'by_reference' => false,
              'entry_type' => CategoryLiteType::class,
              'allow_add' => true,
              'label_attr'=> ['style'=> 'display:none']))
         ->end()
        ->end()
      ->tab('advanced')
        ->halfPanelDefault('secondary')
            ->add('nameShort')
            ->add('customId')
            ->add('softColor', null, ['attr' => ['class' => 'gogo-color-picker']])
            ->add('textHelper')
            ->add('url')
            ->add('index')
            ->add('showExpanded')
            ->add('unexpandable')
        ->end()

        ->halfPanelDefault('displayChildren')
            ->add('displayChildrenInMenu')
            ->add('displayChildrenInInfoBar')
            ->add('displayChildrenInForm')
        ->end()

        ->halfPanelDefault('osm')
            ->add('osmTags', TextType::class, ['attr' => ['class' => 'gogo-osm-tags']])
        ->end()
        
        ->halfPanelDefault('description')
            ->add('enableDescription')
            ->add('descriptionLabel')
        ->end()

      ->end()
      ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
          ->addIdentifier('name')
          ->add('_action', 'actions', [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                    'move' => [
                        'template' => '@PixSortableBehavior/Default/_sort.html.twig',
                    ],
                ],
            ]);
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('move', $this->getRouterIdParameter().'/move/{position}');
    }
}
