<?php

namespace App\Controller;

use App\Lib\FileFinder;
use Psr\Log\LoggerInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DefaultController is here to help you get started.
 *
 * You would probably put most of your actions in other more domain specific
 * controller classes.
 *
 * Controllers are completely separated from Silex, any dependencies should be
 * injected through the constructor. When used with a smart controller resolver,
 * the Request object can be automatically added as an argument if you use type
 * hinting.
 *
 * @author Gunnar Lium <gunnar@aptoma.com>
 */
class DefaultController
{

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(\Twig_Environment $twig, LoggerInterface $logger)
    {

        $this->twig = $twig;
        $this->logger = $logger;
    }

    public function indexAction(Request $request, Application $app)
    {
        $this->logger->debug('Executing DefaultController::indexAction');

        // Récupérer la liste de document
        $file_list = FileFinder::listFiles($app['securefile.path']);

        return $this->twig->render('index.twig', array('file_list' => $file_list));
    }

    public function decryptAction(Request $request, Application $app)
    {
        // Sur un appel ajax, on récupère un id de fichier et une passphrase
        // On vérifie qu'il n'y a pas eu trop d'appel
        // On essaye de décrypter les données
        // Sur réussite on les renvoit au format json, sinon on envoit une erreur
        /*
        $user = getUser($id);

        if (!$user) {
            $error = array('message' => 'The user was not found.');

            return $app->json($error, 404);
        }

        return $app->json($user);
        */
    }

    public function encryptAction(Request $request, Application $app)
    {
        // Sur un appel ajax, on récupère un id de fichier (ou pas si création), un titre, une liste d'auteur, une description, une passphrase
        // On stocke le fichier (nouveau ou pas, selon l'id)
        // On renvoit la liste à jour des documents
    }
}
