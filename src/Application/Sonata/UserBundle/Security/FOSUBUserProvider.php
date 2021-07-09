<?php

namespace App\Application\Sonata\UserBundle\Security;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\FOSUBUserProvider as BaseClass;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class FOSUBUserProvider extends BaseClass
{
    
    public function __construct(UserManagerInterface $userManager, array $properties, FlashBagInterface $flash)
    {
        $this->userManager = $userManager;
        $this->properties = array_merge($this->properties, $properties);
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->flash = $flash;
    }
    /**
     * {@inheritdoc}
     */
    public function connect(UserInterface $user, UserResponseInterface $response)
    {
        $property = $this->getProperty($response);
        $username = $response->getUsername();
        //on connect - get the access token and the user ID
        $service = $response->getResourceOwner()->getName();
        $setter = 'set'.ucfirst($service);
        $setter_id = $setter.'Id';
        $setter_token = $setter.'AccessToken';
        //we "disconnect" previously connected users
        if (null !== $previousUser = $this->userManager->findUserBy([$property => $username])) {
            $previousUser->$setter_id(null);
            $previousUser->$setter_token(null);
            $this->userManager->updateUser($previousUser);
        }
        //we connect current user
        $user->$setter_id($username);
        $user->$setter_token($response->getAccessToken());
        $this->userManager->updateUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $username = $response->getUsername();
        // Find using communsUid, or facebookUid etc...
        $user = $this->userManager->findUserBy([$this->getProperty($response) => $username]);
        // If not exist try find existing user with same email
        if (null === $user) {
            $user = $this->userManager->findUserByEmail($response->getEmail());
        }
        $service = $response->getResourceOwner()->getName();
        // when the user is registrating
        if (null === $user) {   
            // create new user here
            $user = $this->userManager->createUser();     
            $user->setUsername($response->getNickname());
            $user->setFirstName($response->getFirstName());
            $user->setLastName($response->getLastName());
            $user->setEmail($response->getEmail());
            $user->setPassword($username);
            $user->setEnabled(true);
        }
        // Update specific service info (facebookUid, facebookName ...)
        $setter = 'set'.ucfirst($service);
        $setter_id = $setter.'Uid';
        $setter_name = $setter.'Name';
        $setter_token = $setter.'Data';        
        $user->$setter_id($username);
        $user->$setter_name($response->getNickname());
        $user->$setter_token($response->getAccessToken());
        $this->userManager->updateUser($user);        
        
        // Adds flash message
        $this->flash->add('success', "Authentification réussie via \"$service\", vous êtes maintenant connecté !"); 
        return $user;
    }
}
