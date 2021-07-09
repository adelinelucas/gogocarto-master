<?php
/**
 * @Author: Sebastian Castro
 * @Date:   2017-03-28 15:29:03
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-07-08 16:42:20
 */

namespace App\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use App\Form\OptionLiteType;
use App\Helper\GoGoHelper;

class CategoryAdmin extends GoGoAbstractAdmin
{
    protected $baseRouteName = 'admin_app_category';
    protected $baseRoutePattern = 'admin_app_category';

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
        $repo = $dm->get('Option');
        $parentQuery = null;
        if ($this->subject) {
          $parentQuery = $repo->createQueryBuilder()->field('id')->notIn($this->subject->getAllOptionsIds());
        }

        $formMapper
          ->halfPanel('primary')
            ->add('name', null, ['required' => true])
            ->add('pickingOptionText', null, ['required' => true])
            ->add('parent', ModelType::class, [
                'class' => 'App\Document\Option',
                'required' => false,
                'query' => $parentQuery], ['admin_code' => 'admin.options'])
            ->add('isMandatory')
            ->add('singleOption')
            ->add('enableDescription')
            ->add('descriptionLabel')
          ->end()
          ->halfPanel('secondary')
             ->add('nameShort')
             ->add('customId')
             ->add('index')
             ->add('showExpanded')
                   ->add('unexpandable')
             ->add('displaySuboptionsInline')
          ->end()
          ->halfPanel('display', ['class' => 'col-md-6', 'box_class' => 'box'])
            ->add('displayInMenu')
            ->add('displayInInfoBar')
            ->add('displayInForm')
          ->end()
          ->panel('categories', array('class' => 'col-xs-12 sub-options-container'))
            ->add('isFixture', HiddenType::class, ['attr' => ['class' => 'gogo-sort-options'], 'label_attr' => ['style' => 'display:none']])
            ->add('options', CollectionType::class, array(
            'by_reference' => false,
            'entry_type' => OptionLiteType::class,
            'allow_add' => true,
            'label_attr'=> ['style'=> 'display:none']))
          ->end()
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
          ->add('name')
          ->add('_action', 'actions', [
               'actions' => [
                    'tree' => ['template' => 'admin/partials/list__action_tree.html.twig'],
               ],
            ]);
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('tree', $this->getRouterIdParameter().'/tree');
    }
}
