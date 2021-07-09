<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) 2016 Sebastian Castro - 90scastro@gmail.com
 * @license    MIT License
 * @Last Modified time: 2018-07-08 16:44:57
 */

namespace App\Controller;

use App\Document\Element;
use App\Document\ElementStatus;
use App\Document\User;
use App\Form\ElementType;
use App\Services\ConfigurationService;
use App\Services\ElementActionService;
use App\Services\ElementFormService;
use App\Services\ElementSynchronizationService;
use Doctrine\ODM\MongoDB\DocumentManager;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Security\LoginManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Contracts\Translation\TranslatorInterface;


class ElementFormController extends GoGoController
{
    public function __construct(ElementSynchronizationService $synchService)
    {
        $this->synchService = $synchService;
    }
    
    public function addAction(Request $request, SessionInterface $session, DocumentManager $dm,
                              ConfigurationService $configService,
                              ElementFormService $elementFormService, UserManagerInterface $userManager,
                              ElementActionService $elementActionService, LoginManagerInterface $loginManager, 
                              TranslatorInterface $t)
    {
        return $this->renderForm(new Element(), false, $request, $session, $dm, $configService, $elementFormService, $userManager, $elementActionService, $loginManager, $t);
    }

    public function editAction($id, Request $request, SessionInterface $session, DocumentManager $dm,
                               ConfigurationService $configService,
                               ElementFormService $elementFormService, UserManagerInterface $userManager,
                               ElementActionService $elementActionService, LoginManagerInterface $loginManager, 
                               TranslatorInterface $t)
    {
        $element = $dm->get('Element')->find($id);

        if (!$element) {
            $this->addFlash('error', $t->trans('element.form.controller.error_dont_exist'));

            return $this->redirectToRoute('gogo_directory');
        } elseif ($element->getStatus() > ElementStatus::PendingAdd && $element->isEditable()
            || $configService->isUserAllowed('directModeration')
            || ($element->isPending() && $element->getRandomHash() == $request->get('hash'))) {
            return $this->renderForm($element, true, $request, $session, $dm, $configService, $elementFormService, $userManager, $elementActionService, $loginManager, $t);
        } else {
            $this->addFlash('error', $t->trans('element.form.controller.error_unauthorized'));

            return $this->redirectToRoute('gogo_directory');
        }
    }

    // render for both Add and Edit actions
    private function renderForm($element, $editMode, $request, $session, $dm, $configService,
                                $elementFormService, $userManager, $elementActionService, $loginManager, TranslatorInterface $t)
    {
        if (null === $element) {
            throw new NotFoundHttpException($t->trans('element.form.controller.error_dont_exist'));
        }

        $addOrEditComplete = false;
        $userRoles = [];
        $addEditName = $editMode ? 'edit' : 'add';

        if ($request->get('logout')) {
            $session->remove('userEmail');
        }

        $userType = 'anonymous';
        $isEditingWithHash = $element->getRandomHash() && $element->getRandomHash() == $request->get('hash');
        $isLoggedIn = $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED');
        if ($request->request->get('input-password') && !$isLoggedIn) {
            // Create our user and set details
            $user = $userManager->createUser();
            $user->setUserName($session->get('userEmail'));
            $user->setEmail($session->get('userEmail'));
            $user->setPlainPassword($request->request->get('input-password'));
            $user->setEnabled(true);

            // Update the user
            $userManager->updateUser($user, true);
            $dm->persist($user);

            $text = $t->trans('element.form.controller.success', ['%url%' => $this->generateUrl('gogo_user_profile')] );
            $session->getFlashBag()->add('success', $text);

            $this->authenticateUser($user, $loginManager);
            $isLoggedIn = true;
        }
        
        // is user not allowed, we show the contributor-login page
        if (!$configService->isUserAllowed($addEditName, $request) && !$isEditingWithHash) {
            // creating simple form to let user enter a email address
            $loginform = $this->get('form.factory')->createNamedBuilder('user', FormType::class)
                ->add('email', EmailType::class, ['required' => false])
                ->getForm();

            $userEmail = $request->request->get('user')['email'] ?? '';
            $emailAlreadyUsed = false;
            if ($userEmail) {
                $othersUsers = $dm->get('User')->findByEmail($userEmail);
                $emailAlreadyUsed = count($othersUsers) > 0;
            }
            $loginform->handleRequest($request);
            if ($loginform->isSubmitted() && $loginform->isValid() && !$emailAlreadyUsed) {
                $session->set('userEmail', $userEmail);
                $userType = 'email';
            } else {
                return $this->render('element-form/contributor-login.html.twig', [
                    'loginForm' => $loginform->createView(),
                    'emailAlreadyUsed' => $emailAlreadyUsed,
                    'config' => $configService->getConfig(),
                    'featureConfig' => $configService->getFeatureConfig($addEditName), ]);
            }
        }
        // depending on authentification type (account or just giving email) we fill some variables
        else {
            if ($isLoggedIn) {
                $userType = 'loggued';
                $user = $this->getUser();
                $userRoles = $user->getRoles();
                $userEmail = $user->getEmail();
            } elseif ($session->has('userEmail')) {
                $userType = 'email';
                $user = $session->get('userEmail');
                $userEmail = $session->get('userEmail');
            } elseif ($isEditingWithHash) {
                $userType = 'hash';
                $user = 'Anonymous with Hash';
                $userEmail = 'Anonymous with Hash';
            } else {
                $userType = 'anonymous';
                $user = 'Anonymous';
                $userEmail = 'Anonymous';
            }
        }

        // We need to detect if the owner contribution has been validated. Because after that, the owner have direct moderation on the element
        // To check that, we check is element is Valid or element is pending but from a contribution not made by the owner
        $isUserOwnerOfValidElement = $editMode && ($element->isValid() ||
                                                $element->isPending() && $element->getCurrContribution() && $element->getCurrContribution()->getUserEmail() != $userEmail)
                                              && $element->getUserOwnerEmail() && $element->getUserOwnerEmail() == $userEmail;

        $isAllowedDirectModeration = $configService->isUserAllowed('directModeration')
                                              || (!$editMode && in_array('ROLE_DIRECTMODERATION_ADD', $userRoles))
                                              || ($editMode && in_array('ROLE_DIRECTMODERATION_EDIT_OWN_CONTRIB', $userRoles) && $element->hasValidContributionMadeBy($userEmail))
                                              || $isUserOwnerOfValidElement
                                              || ($isEditingWithHash && $element->getStatus() > ElementStatus::PendingAdd);

        $editingOwnPendingContrib = $element->isPending() && $element->getCurrContribution() && $element->getCurrContribution()->getUserEmail() == $userEmail;

        $editMode = $editMode && !($editingOwnPendingContrib && $element->isPendingAdd());

        // create the element form
        $realElement = $element;
        $element = $element->isPendingModification() ? $element->getModifiedElement() : $element;
        $originalElement = clone $element;
        $elementForm = $this->get('form.factory')->create(ElementType::class, $element);

        // when we check for duplicates, we jump to an other action, and come back to the add action
        // with the "duplicate" GET param to true. We check that in this case an 'elementWaitingForDuplicateCheck'
        // is stored in the session
        $checkDuplicateOk = $request->query->get('checkDuplicate') && $session->has('elementWaitingForDuplicateCheck');

        //  If form submitted with valid values
        $elementForm->handleRequest($request);
        if ($elementForm->isSubmitted() && $elementForm->isValid() || $checkDuplicateOk) {
            // if checkDuplicate process is done
            if ($checkDuplicateOk) {
                $element = $session->get('elementWaitingForDuplicateCheck');
                $element->resetImages(); // see comment in AbstractFile:unserialize
                $element->resetFiles();

                // serialization/unserialization using session does not restore properly the source relation
                if ($element->getSourceKey()) {
                    $element->setSource($dm->get('Import')->findOneBy(['sourceName' => $element->getSourceKey()]));
                }
                if ($osmId = $request->get('matchElementWithOsmId')) {
                    $this->synchService->updateOsmDuplicateWithNewElementData($osmId, $element);
                }

                // filling the form with the previous element created in case we want to recopy its informations (only for admins)
                $elementForm = $this->get('form.factory')->create(ElementType::class, $element);
                $isMinorModification = false;

                $session->remove('elementWaitingForDuplicateCheck');
                $session->remove('duplicatesElements');
            }
            // if we just submit the form
            else {
                // custom handling form (creating OptionValues for example)
                list($element, $isMinorModification) = $elementFormService->handleFormSubmission($element, $request, $editMode, $userEmail, $isAllowedDirectModeration, $originalElement, $dm);

                // check for duplicates in Add action
                if (!$editMode && !$editingOwnPendingContrib) {
                    $duplicates = $dm->get('Element')->findDuplicatesFor($element);
                    $osmDuplicates = $this->synchService->checkIfNewElementShouldBeAddedToOsm($element)['duplicates'];
                    if (count($osmDuplicates)) {
                        // the duplicates returnes by OSM might already exist in gogocarto, so we
                        // intersect osmduplicates with gogo duplicates
                        $osmDuplicatesIdsInGoGoDatabase = [];
                        foreach($duplicates as $duplicate) {
                            if ($duplicate->isFromOsm()) $osmDuplicatesIdsInGoGoDatabase[] = $duplicate->getOldId();
                        }
                        foreach($osmDuplicates as $osmDuplicate) {
                            $id = explode('/', $osmDuplicate['osmId'])[1]; // osmId is something like node/12345
                            if (!in_array($id, $osmDuplicatesIdsInGoGoDatabase))
                                $duplicates[] = $osmDuplicate;
                        }
                    }
                    $needToCheckDuplicates = count($duplicates) > 0;
                } else {
                    $needToCheckDuplicates = false;
                }
                
                if ($needToCheckDuplicates) {
                    // saving values in session
                    $session->set('elementWaitingForDuplicateCheck', $element);
                    $session->set('duplicatesElements', $duplicates);
                    $session->set('recopyInfo', $request->request->get('recopyInfo'));
                    $session->set('sendMail', $request->request->get('send_mail'));
                    $session->set('submitOption', $request->request->get('submit-option'));
                    // redirect to check duplicate
                    return $this->redirectToRoute('gogo_element_check_duplicate');
                }
            }

            $dm->persist($element);

            // getting the variables from POST or from session (in case of checkDuplicate process)
            $sendMail = $request->request->has('send_mail') ? $request->request->get('send_mail') : $session->get('sendMail');
            $recopyInfo = $request->request->has('recopyInfo') ? $request->request->get('recopyInfo') : $session->get('recopyInfo');
            $submitOption = $request->request->has('submit-option') ? $request->request->get('submit-option') : $session->get('submitOption');
            // clear session
            $session->remove('elementWaitingForDuplicateCheck');
            $session->remove('duplicatesElements');
            $session->remove('recopyInfo');
            $session->remove('sendMail');
            $session->remove('submitOption');

            if ($this->isRealModification($element, $request)) {
                $message = $request->get('admin-message');

                if ($isAllowedDirectModeration || $isMinorModification) {
                    if (!$editMode) {
                        $elementActionService->add($element, $sendMail, $message);
                    } else {
                        $elementActionService->edit($element, $sendMail, $message, $isUserOwnerOfValidElement, $isEditingWithHash);
                    }
                } else { // non direct moderation
                    $elementActionService->createPending($element, $editMode, $userEmail);
                }
            }

            $dm->persist($element);
            $dm->flush();

            $elementToUse = $editMode ? $realElement : $element;
            $elementShowOnMapUrl = $elementToUse->getShowUrlFromController($this->get('router'));

            
            if ($editMode) {
                $noticeText = $t->trans('element.form.controller.thankyou.edited');
            } else {
                $noticeText = $t->trans('element.form.controller.thankyou.added', ['%name%' => ucwords($configService->getConfig()->getElementDisplayNameDefinite())]);
            }

            if ($element->isPending()) {
                $noticeText .= "</br>".$t->trans('element.form.controller.pending');
            }

            if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED') || $session->has('userEmail')) {
                $noticeText .= '</br>'.$t->trans('element.form.controller.user_contributions', ['%url%' => $this->generateUrl('gogo_user_contributions')]);
            }

            $isAllowedPending = $configService->isUserAllowed('pending');

            $showResultLink = $submitOption == 'stayonform' && ($isAllowedDirectModeration || $isAllowedPending);
            if ($showResultLink) {
                $noticeText .= '</br><a href="'.$elementShowOnMapUrl.'">Voir le résultat sur la carte</a>';
            }

            $session->getFlashBag()->add('success', $noticeText);

            if ($submitOption != 'stayonform' && !$recopyInfo) {
                return $this->redirect($elementShowOnMapUrl);
            }

            if ($editMode) {
                return $this->redirectToRoute('gogo_element_add');
            }

            // Unless admin ask for recopying the informations
            if (!($isAllowedDirectModeration && $recopyInfo)) {
                // resetting form
                $elementForm = $this->get('form.factory')->create(ElementType::class, new Element());
                $element = new Element();
            }

            $addOrEditComplete = true;
        }

        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED') && !$session->has('userEmail') && !$addOrEditComplete) {
            $flashMessage = 'Vous êtes actuellement en mode "Anonyme"</br> Connectez-vous pour augmenter notre confiance dans vos contributions !';
            $session->getFlashBag()->add('notice', $flashMessage);
        }

        $mainCategories = $dm->get('Category')->findRootCategories();

        return $this->render('element-form/element-form.html.twig',
                    [
                        'editMode' => $editMode,
                        'form' => $elementForm->createView(),
                        'mainCategories' => $mainCategories,
                        'element' => $element,
                        'userEmail' => $userEmail,
                        'userType' => $userType,
                        'isAllowedDirectModeration' => $isAllowedDirectModeration,
                        'isAnonymousWithEmail' => $session->has('userEmail'),
                        'config' => $configService->getConfig(),
                        'imagesMaxFilesize' => $this->detectMaxUploadFileSize('images'),
                        'filesMaxFilesize' => $this->detectMaxUploadFileSize('files'),
                        'isOwner' => $isUserOwnerOfValidElement
                    ]);
    }

    // If user check "do not validate" on pending element, it means we just want to
    // modify some few things, but staying on the same state. So that's not a "Real" modification
    private function isRealModification($element, $request)
    {
        return !$element->isPending() || !$request->request->get('dont-validate');
    }

    // when submitting new element, check it's not yet existing
    public function checkDuplicatesAction(Request $request, SessionInterface $session, DocumentManager $dm)
    {
        // a form with just a submit button
        $checkDuplicatesForm = $this->get('form.factory')->createNamedBuilder('duplicates', FormType::class)
                                                         ->getForm();        
    
        // If Form is Submitted
        if ($request->getMethod() == 'POST') {
            // if user say that it's not a duplicate, we go back to add action with checkDuplicate to true
            $osmId = $request->get('osm') ? array_keys($request->get('osm'))[0] : null;            
            return $this->redirectToRoute('gogo_element_add', ['checkDuplicate' => true, 'matchElementWithOsmId' => $osmId]);
        }        
        elseif ($session->has('duplicatesElements') && count($session->get('duplicatesElements')) > 0) {
            // Display the check duplicates form
            $duplicates = $session->get('duplicatesElements');
            $config = $dm->get('Configuration')->findConfiguration();
            return $this->render('element-form/check-for-duplicates.html.twig', [
                'duplicateForm' => $checkDuplicatesForm->createView(),
                'duplicatesElements' => $duplicates,
                'config' => $config ]);
        }        
        else {
            return $this->redirectToRoute('gogo_element_add');
        }
    }

    protected function authenticateUser($user, $loginManager)
    {
        try {
            $loginManager->loginUser(
            $this->getParameter('fos_user.firewall_name'),
            $user, null);
        } catch (AccountStatusException $ex) {
            // We simply do not authenticate users which do not pass the user
        }
    }

    /**
     * Detects max size of file cab be uploaded to server.
     *
     * Based on php.ini parameters "upload_max_filesize", "post_max_size" &
     * "memory_limit". Valid for single file upload form. May be used
     * as MAX_FILE_SIZE hidden input or to inform user about max allowed file size.
     *
     * @return int Max file size in bytes
     */
    private function detectMaxUploadFileSize($key = null)
    {
        /**
         * Converts shorthands like "2M" or "512K" to bytes.
         *
         * @param $size
         *
         * @return mixed
         */
        $normalize = function ($size) {
            if (preg_match('/^([\d\.]+)([KMG])$/i', $size, $match)) {
                $pos = array_search($match[2], ['K', 'M', 'G']);
                if (false !== $pos) {
                    $size = $match[1] * pow(1024, $pos + 1);
                }
            }

            return $size;
        };

        $max_upload = $normalize(ini_get('upload_max_filesize'));
        $max_post = $normalize(ini_get('post_max_size'));
        $memory_limit = $normalize(ini_get('memory_limit'));
        $maxFileSize = min($max_upload, $max_post, $memory_limit);

        if ($key) {
            $appMaxsize = $this->getParameter($key.'_max_filesize');
            $maxFileSize = min($maxFileSize, $normalize($appMaxsize));
        }

        return $maxFileSize;
    }
}
