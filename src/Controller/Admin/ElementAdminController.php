<?php

namespace App\Controller\Admin;

use App\Services\ValidationType;
use App\Document\PostalAddress;
use App\Document\Coordinates;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Split this big controller into two classes
class ElementAdminController extends ElementAdminBulkController
{
    public function redirectEditAction()
    {
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        return $this->redirectToRoute('gogo_element_edit', ['id' => $object->getId()]);
    }

    public function redirectShowAction()
    {
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        return $this->redirect($object->getShowUrlFromController($this->get('router')));
    }

    public function redirectBackAction()
    {
        return $this->redirect($this->admin->generateUrl('list', ['filter' => $this->admin->getFilterParameters()]));
    }

    public function showEditAction($id = null)
    {
        $request = $this->getRequest();

        $id = $request->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id : %s', $id));
        }

        $this->admin->checkAccess('edit', $object);
        $this->admin->setSubject($object);

        /** @var $form Form */
        $form = $this->admin->getForm();
        $form->setData($object);

        $view = $form->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getRuntime(\Symfony\Component\Form\FormRenderer::class)
             ->setTheme($view, $this->admin->getFormTheme());

        return $this->render('admin/edit/edit_element.html.twig', [
            'action' => 'edit',
            'form' => $view,
            'object' => $object,
            'elements' => $this->admin->getShow(),
        ], null);
    }

    public function editAction($id = null)
    {
        $request = $this->getRequest();

        $id = $request->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id : %s', $id));
        }

        $this->admin->checkAccess('edit', $object);
        $this->admin->setSubject($object);

        /** @var $form Form */
        $form = $this->admin->getForm();
        $form->setData($object);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            //TODO: remove this check for 4.0
            if (method_exists($this->admin, 'preValidate')) {
                $this->admin->preValidate($object);
            }
            $isFormValid = $form->isValid();

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid) {
                try {
                    $this->handlesGoGoForm($object, $request);

                    $message = $request->get('custom_message') ? $request->get('custom_message') : '';
                    if ($request->get('submit_update_json')) {
                        $this->jsonGenerator->updateJsonRepresentation($object);
                    } elseif ($object->isPending() && ($request->get('submit_accept') || $request->get('submit_refuse'))) {
                        $this->elementActionService->resolve($object, $request->get('submit_accept'), ValidationType::Admin, $message);
                    } else {
                        $sendMail = $request->get('send_mail');

                        if ($request->get('submit_delete')) {
                            $this->elementActionService->delete($object, $sendMail, $message);
                        } elseif ($request->get('submit_restore')) {
                            $this->elementActionService->restore($object, $sendMail, $message);
                        } else {
                            $this->elementActionService->edit($object, $sendMail, $message);
                        }
                    }

                    $object = $this->admin->update($object);

                    $this->addFlash(
                        'sonata_flash_success',
                        $this->trans(
                            'flash_edit_success',
                            ['%name%' => $this->escapeHtml($this->admin->toString($object))],
                            'SonataAdminBundle'
                        )
                    );

                    if ($request->get('submit_redirect')) {
                        return new RedirectResponse(
                            $this->admin->generateUrl('list')
                        );
                    }
                } catch (\Sonata\AdminBundle\Exception\ModelManagerException $e) {
                    $this->handleModelManagerException($e);

                    $isFormValid = false;
                } catch (\Sonata\AdminBundle\Exception\LockException $e) {
                    $this->addFlash('sonata_flash_error', $this->trans('flash_lock_error', [
                        '%name%' => $this->escapeHtml($this->admin->toString($object)),
                        '%link_start%' => '<a href="'.$this->admin->generateObjectUrl('edit', $object).'">',
                        '%link_end%' => '</a>',
                    ], 'SonataAdminBundle'));
                }
            }

            // show an error message if the form failed validation
            if (!$isFormValid) {
                if (!$this->isXmlHttpRequest()) {
                    $this->addFlash(
                        'sonata_flash_error',
                        $this->trans(
                            'flash_edit_error',
                            ['%name%' => $this->escapeHtml($this->admin->toString($object))],
                            'SonataAdminBundle'
                        )
                    );
                }
            }
        }

        return $this->redirectToRoute('admin_app_element_showEdit', ['id' => $id]);
    }

    private function handlesGoGoForm($element, $request)
    {
        $newData = [];
        if ($request->get('data'))
            foreach($request->get('data') as $key => $value) {
                // array data is displayed with json_encode, so we decode it when saving
                $newData[slugify($key, false)] = json_decode($value) ?? $value;
            }
        $element->setCustomData($newData);
        $adr = $request->get('address');
        $address = new PostalAddress($adr['streetAddress'], $adr['addressLocality'], $adr['postalCode'], $adr['addressCountry'], $adr['customFormatedAddress']);
        $element->setAddress($address);
        $geo = new Coordinates($request->get('latitude'), $request->get('longitude'));
        $element->setGeo($geo);
    }

    public function createAction()
    {
        $request = $this->getRequest();
        // the key used to lookup the template
        $templateKey = 'edit';

        $this->admin->checkAccess('create');

        $class = new \ReflectionClass($this->admin->hasActiveSubClass() ? $this->admin->getActiveSubClass() : $this->admin->getClass());

        if ($class->isAbstract()) {
            return $this->renderWithExtraParams(
                '@SonataAdmin/CRUD/select_subclass.html.twig',
                [
                    'base_template' => $this->getBaseTemplate(),
                    'admin' => $this->admin,
                    'action' => 'create',
                ],
                null
            );
        }

        $newObject = $this->admin->getNewInstance();

        $preResponse = $this->preCreate($request, $newObject);
        if (null !== $preResponse) {
            return $preResponse;
        }

        $this->admin->setSubject($newObject);

        $form = $this->admin->getForm();

        $form->setData($newObject);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $isFormValid = $form->isValid();

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid && (!$this->isInPreviewMode() || $this->isPreviewApproved())) {
                $submittedObject = $form->getData();
                $this->admin->setSubject($submittedObject);
                $this->admin->checkAccess('create', $submittedObject);
                $this->handlesGoGoForm($submittedObject, $request);
                
                try {
                    $newObject = $this->admin->create($submittedObject);
                    // send mail after element has been persisted (we need it's ID to generate the url)
                    $this->elementActionService->add($newObject, true, "");
                    $this->admin->update($newObject); // status has been modified by the elementActionService
                    
                    if ($this->isXmlHttpRequest()) {
                        return $this->handleXmlHttpRequestSuccessResponse($request, $newObject);
                    }

                    $this->addFlash(
                        'sonata_flash_success',
                        $this->trans(
                            'flash_create_success',
                            ['%name%' => $this->escapeHtml($this->admin->toString($newObject))],
                            'SonataAdminBundle'
                        )
                    );

                    // redirect to edit mode
                    return $this->redirectTo($newObject);
                } catch (ModelManagerException $e) {
                    $this->handleModelManagerException($e);

                    $isFormValid = false;
                }
            }

            // show an error message if the form failed validation
            if (!$isFormValid) {
                if ($this->isXmlHttpRequest() && null !== ($response = $this->handleXmlHttpRequestErrorResponse($request, $form))) {
                    return $response;
                }

                $this->addFlash(
                    'sonata_flash_error',
                    $this->trans(
                        'flash_create_error',
                        ['%name%' => $this->escapeHtml($this->admin->toString($newObject))],
                        'SonataAdminBundle'
                    )
                );
            } elseif ($this->isPreviewRequested()) {
                // pick the preview template if the form was valid and preview was requested
                $templateKey = 'preview';
                $this->admin->getShow();
            }
        }

        $formView = $form->createView();
        // set the theme for the current Admin Form
        $this->get('twig')->getRuntime(\Symfony\Component\Form\FormRenderer::class)
             ->setTheme($formView, $this->admin->getFormTheme());

        // NEXT_MAJOR: Remove this line and use commented line below it instead
        $template = $this->admin->getTemplate($templateKey);
        // $template = $this->templateRegistry->getTemplate($templateKey);

        return $this->renderWithExtraParams($template, [
            'action' => 'create',
            'form' => $formView,
            'object' => $newObject,
            'objectId' => null,
        ], null);
    }
}
