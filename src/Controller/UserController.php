<?php

namespace App\Controller;

use App\Document\Coordinates;
use App\Document\InteractionType;
use App\Services\ConfigurationService;
use App\Form\UserProfileType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Geocoder\ProviderAggregator;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class UserController extends GoGoController
{
    public function userSpaceAction(ConfigurationService $confService)
    {
        if ($confService->isUserAllowed('add') || $confService->isUserAllowed('edit') ||
            $confService->isUserAllowed('vote') || $confService->isUserAllowed('report')) {
            return $this->render('user/user-space.html.twig');
        } else {
            return $this->redirectToRoute('gogo_user_profile');
        }

    }

    public function contributionsAction(DocumentManager $dm)
    {
        $user = $this->getUser();
        $userEmail = $user->getEmail();

        $elementsOwned = $dm->get('Element')->findElementsOwnedBy($userEmail);
        $elementsOwned = array_filter($elementsOwned->toArray(), function ($element) use ($userEmail) {
            return !$element->isPending() || $element->getCurrContribution()->getUserEmail() != $userEmail;
        });

        $allContribs = $dm->get('UserInteractionContribution')->findByUserEmail($userEmail);

        $allContribs = array_filter($allContribs, function ($interaction) {
            return in_array($interaction->getType(), [InteractionType::Add, InteractionType::Edit]);
        });
        $elementsUserHaveContributed = [];
        $pendingContribs = [];
        foreach ($allContribs as $key => $contrib) {
            if (null == $contrib->getStatus()) {
                $pendingContribs[] = $contrib;
            }

            if ($contrib->countAsValidContributionFrom($userEmail)
             && !in_array($contrib->getElement(), $elementsUserHaveContributed)
             && !in_array($contrib->getElement(), $elementsOwned)) {
                try {
                    if ($contrib->getElement()->getName()) {
                        array_push($elementsUserHaveContributed, $contrib->getElement());
                    }
                } catch (\Exception $e) {
                }
            }
        }

        usort($pendingContribs, function ($a, $b) { return $b->getTimestamp() - $a->getTimestamp(); });
        usort($allContribs, function ($a, $b) { return $b->getTimestamp() - $a->getTimestamp(); });

        return $this->render('user/contributions/my-contributions.html.twig', [
         'elementsOwned' => $elementsOwned,
         'elementsUserHaveContributed' => $elementsUserHaveContributed,
         'pendingContributions' => $pendingContribs,
         'allContributions' => $allContribs, ]);
    }

    public function votesAction(DocumentManager $dm)
    {
        $user = $this->getUser();
        $userEmail = $user->getEmail();

        $votes = $dm->get('UserInteractionVote')->findByUserEmail($userEmail);
        usort($votes, function ($a, $b) { return $b->getTimestamp() - $a->getTimestamp(); });

        return $this->render('user/contributions/votes.html.twig', ['votes' => $votes]);
    }

    public function reportsAction(DocumentManager $dm)
    {
        $user = $this->getUser();
        $userEmail = $user->getEmail();

        $reports = $dm->get('UserInteractionReport')->findByUserEmail($userEmail);
        usort($reports, function ($a, $b) { return $b->getTimestamp() - $a->getTimestamp(); });

        return $this->render('user/contributions/reports.html.twig', ['reports' => $reports]);
    }

    public function becomeOwnerAction($id, Request $request, SessionInterface $session, DocumentManager $dm, 
                                    TranslatorInterface $t)
    {
        $element = $dm->get('Element')->find($id);

        if (!$element->getUserOwnerEmail()) {
            $user = $this->getUser();
            $userEmail = $user->getEmail();
            $element->setUserOwnerEmail($userEmail);
            $session->getFlashBag()->add('success', $t->trans('element.form.success_becomeOwner', ['%name%' => $element->getName()] ));
            $dm->flush();
        } else {
            $session->getFlashBag()->add('error', $t->trans('element.form.success_becomeOwner'));
        }

        return $this->redirectToRoute('gogo_user_contributions');
    }

    public function profileAction(Request $request, SessionInterface $session, DocumentManager $dm,
                                 ProviderAggregator $geocoder, 
                                 TranslatorInterface $t)
    {
        $user = $this->getUser();
        $current_user = clone $user;
        $form = $this->get('form.factory')->create(UserProfileType::class, $user);
        $userRepo = $dm->get('User');
        $config = $dm->get('Configuration')->findConfiguration();

        if (!$user->getNewsletterRange()) {
            $user->setNewsletterRange(50);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $alreadyUsedEmail = ($current_user->getEmail() != $user->getEmail()) && count($userRepo->findByEmail($user->getEmail())) > 0;
            $alreadyUsedUserName = ($current_user->getUsername() != $user->getUsername()) && count($userRepo->findByUsername($user->getUsername())) > 0;
            $locationSetToReceiveNewsletter = $user->getNewsletterFrequency() > 0 && !$user->getLocation();
            $geocodeError = false;

            if ($user->getLocation()) {
                try {
                    $geocoded = $geocoder->using('mapbox')->geocode($user->getLocation())
                             ->first()->getCoordinates();
                    $user->setGeo(new Coordinates($geocoded->getLatitude(), $geocoded->getLongitude()));
                } catch (\Exception $error) {
                    $geocodeError = true;
                }
            }

            if ($form->isValid() /*&& !$alreadyUsedEmail */ && !$alreadyUsedUserName && !$locationSetToReceiveNewsletter && !$geocodeError) {
                $dm->persist($user);
                $dm->flush();
                $session->getFlashBag()->add('info', $t->trans('element.form.info_saved'));
            } else {
                // if ($alreadyUsedEmail) $form->get('email')->addError(new FormError('Cet email est déjà utilisé'));
                if ($alreadyUsedUserName) {
                    $form->get('username')->addError(new FormError($t->trans('element.form.error_username')));
                }
                if ($locationSetToReceiveNewsletter) {
                    $form->get('location')->addError(new FormError($t->trans('element.form.error_missing_adress')));
                }
                if ($geocodeError) {
                    $form->get('location')->addError(new FormError($t->trans('element.form.error_geocode')));
                }
            }
        }

        return $this->render('user/profile.html.twig', ['user' => $user, 'form' => $form->createView(), 'config' => $config]);
    }
}
