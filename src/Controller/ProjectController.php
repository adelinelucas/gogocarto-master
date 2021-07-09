<?php

namespace App\Controller;

use App\Application\Sonata\UserBundle\Form\Type\RegistrationFormType;
use App\DataFixtures\MongoDB\LoadConfiguration;
use App\Document\Category;
use App\Document\Option;
use App\Document\Project;
use App\Document\Taxonomy;
use App\Services\DocumentManagerFactory;
use App\Services\UrlService;
use App\EventListener\ConfigurationListener;
use Doctrine\ODM\MongoDB\DocumentManager;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Security\LoginManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectController extends Controller
{
    public function createAction(Request $request, DocumentManagerFactory $dmFactory, UrlService $urlService, TranslatorInterface $t)
    {
        if (!$dmFactory->isRootProject()) {
            return $this->redirectToRoute('gogo_homepage');
        }

        $dm = $dmFactory->getRootManager();
        $project = new Project();

        $projectForm = $this->createFormBuilder($project)
            ->add('name', null, ['required' => true])
            ->add('domainName', null, ['required' => true])
            ->getForm();

        $projectForm->handleRequest($request);
        if ($projectForm->isSubmitted() && $projectForm->isValid()) {

            // save project
            $dm->persist($project);
            $project->setNextUpdateAt(time());
            $dm->flush();      

            // initialize new database
            $projectDm = $dmFactory->createForDB($project->getDomainName());
            $rootDm = $dm;

            // INITALIZE CONFIGURATION (copy root project conf)
            $confLoader = new LoadConfiguration();
            $rootConf = $rootDm->get('Configuration')->findConfiguration();
            $projectConf = clone $rootConf;
            $projectConf->setAppName($project->getName());
            $projectConf->setAppBaseLine('');
            $projectConf->setDbName($project->getDomainName());
            $projectConf->setSaas(null);
            $projectConf->setCustomCSS(null);
            $projectConf->setCustomJavascript(null);
            foreach(['customCSS', 'customJavascript', 'appSlug', 'appTags', 'titleFont', 'logo', 'logoInline', 'socialShareImage', 'favicon'] as $prop) {
                $prop = 'set'.ucfirst($prop);
                $projectConf->$prop(null);
            }
            $tileLayers = $rootDm->get('TileLayer')->findAll();
            foreach ($tileLayers as $tileLayer) {
                $newTileLayer = clone $tileLayer;
                $projectDm->persist($newTileLayer);
                if ($tileLayer === $rootConf->getDefaultTileLayer()) {
                    $projectConf->setDefaultTileLayer($newTileLayer);
                }
            }            
            $confLoader->initContributionConfig($projectConf, $request->request->get('contrib'));

            $projectDm->persist($projectConf);

            // Generate basic categories
            $mainCategory = new Category();
            $mainCategory->setName($t->trans('projects.controller.main_categories'));
            $mainCategory->setPickingOptionText($t->trans('projects.controller.main_categories'));
            $projectDm->persist($mainCategory);

            $mains = [
                ['Catégorie 1', 'fa fa-recycle', '#98a100'],
                ['Catégorie 2', 'fa fa-home', '#7e3200'],
            ];

            foreach ($mains as $key => $main) {
                $new_main = new Option();
                $new_main->setName($main[0]);
                $new_main->setIcon($main[1]);
                $new_main->setColor($main[2]);
                $new_main->setIsFixture(true);
                $mainCategory->addOption($new_main);
            }

            $projectDm->flush(); // flush before taxonomy creating otherwise strange bug creating option with only DBRef

            $taxonomy = new Taxonomy();
            $projectDm->persist($taxonomy);
            $projectDm->flush();

            // REDIRECT to new project
            $url = $urlService->generateUrlFor($project, 'gogo_saas_initialize_project');
            return $this->redirect($url);
        }

        $config = $dm->get('Configuration')->findConfiguration();

        return $this->render('saas/projects/create.html.twig', ['form' => $projectForm->createView(), 'config' => $config]);
    }

    /**
     * @Route("/projects", name="gogo_saas_home")
     */
    public function homeAction(DocumentManagerFactory $dmFactory, UrlService $urlService)
    {
        if (!$dmFactory->isRootProject()) {
            return $this->redirectToRoute('gogo_homepage');
        }
        $dm = $dmFactory->getCurrentManager();
        $repository = $dm->get('Project');
        $config = $dm->get('Configuration')->findConfiguration();

        $projects = $dm->query('Project')
                        ->field('published')->equals(true)
                        ->field('dataSize')->gte(10)
                        ->sort('publishedAt', 'desc')
                        ->getArray();
        $pinnedProjects = $repository->findBy(['pinned' => true]);

        foreach ($projects as $project) {
            $project->setHomeUrl($urlService->generateUrlFor($project));
            $project->setCreatedAt($project->getCreatedAt()->getTimestamp());
            if ($project->getPublishedAt()) $project->setPublishedAt($project->getPublishedAt()->getTimestamp());
        }
        foreach ($pinnedProjects as $project) {
            $project->setHomeUrl($urlService->generateUrlFor($project));
        }

        return $this->render('saas/home.html.twig', [
            'projects' => $projects,
            'pinnedProjects' => $pinnedProjects,
            'config' => $config]);
    }

    // This route is to create an Admin User when the project is just created
    public function initializeAction(Request $request, DocumentManager $dm,
                                     UserManagerInterface $userManager,
                                     LoginManagerInterface $loginManager,
                                     ConfigurationListener $confService,
                                     TranslatorInterface $t)
    {
        // Return if already existing users
        $users = $dm->get('User')->findAll();
        if (count($users) > 0) {
            return $this->redirectToRoute('gogo_homepage');
        }

        $config = $dm->get('Configuration')->findConfiguration();

        // Init indexes
        $dm->getSchemaManager()->updateIndexes();
        $confService->manuallyUpdateIndex($dm);

        // CREATE ADMIN USER
        $user = $userManager->createUser();

        $form = $this->get('form.factory')->create(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $user->setEnabled(true);
            $user->setRoles(['ROLE_SUPER_ADMIN']);
            $userManager->updateUser($user, true);

            $this->addFlash('success', $t->trans('dashboard.welcome', [], 'admin')); 
            $response = $this->redirectToRoute('sonata_admin_dashboard');

            $this->authenticateUser($user, $response, $loginManager);

            return $response;
        }

        return $this->render('saas/projects/initialize.html.twig', ['form' => $form->createView(), 'config' => $config]);
    }

    protected function authenticateUser(UserInterface $user, Response $response,
                                        LoginManagerInterface $loginManager)
    {
        try {
            $loginManager->loginUser(
                $this->getParameter('fos_user.firewall_name'),
                $user,
                $response
            );
        } catch (AccountStatusException $ex) {
        }
    }

    // In SAAS Mode, a project is being deleted by the owner
    public function deleteCurrProjectAction(DocumentManagerFactory $dmFactory, UrlService $urlService,
                                            LoggerInterface $projectsLogger, TokenStorageInterface $securityContext)
    {
        $user = $securityContext->getToken()->getUser();
        $dm = $dmFactory->getCurrentManager();
        $dbName = $dmFactory->getCurrentDbName();

        if (!$user || !$user->hasRole('ROLE_SUPER_ADMIN')) {
            $username = $user ? 'Unknown User' : $user->getUsername();
            $projectsLogger->error("The user $username have tried to delete $dbName but is not a super admin");
        } else {
            $projectsLogger->info("Project $dbName being deleted by {$user->getUsername()}");
        }    

        $mongo = $dm->getConnection()->getMongoClient();
        $db = $mongo->selectDB($dbName);
        $db->command(['dropDatabase' => 1]);
        
        $rootDm = $dmFactory->getRootManager();
        $project = $rootDm->getRepository(Project::class)->findOneByDomainName($dbName);
        $rootDm->remove($project);
        $rootDm->flush();

        // Return to SAAS Home Page
        $url = $urlService->generateRootUrl();
        return $this->redirect($url);
    }
}