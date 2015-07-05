<?php

namespace App\Controller;

use App\Lib\FileFinder;
use App\Lib\EncryptFile;
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
        //$this->logger->debug('Executing DefaultController::indexAction');
        return $this->twig->render('index.twig');
    }

    public function getDocumentsAction(Request $request, Application $app)
    {
        // Récupérer la liste de document
        $file_list = FileFinder::listFiles($app['securefile.path']);
        return $app->json($file_list);
    }

    public function decryptAction(Request $request, Application $app)
    {
        // Sur un appel ajax, on récupère un id de fichier et une passphrase
        // On vérifie qu'il n'y a pas eu trop d'appel
        // On essaye de décrypter les données
        // Sur réussite on les renvoit au format json, sinon on envoit une erreur
        $sPassphrase = $request->get('passphrase');
        $sId = $request->get('id');

        if (!$sPassphrase) {
            $error = array('message' => $app['translator']->trans('DecryptMethodErrorNoPassphrase'));
            return $app->json($error, 400);
        }

        $decryptedFile = FileFinder::getFile($app['securefile.path'], $sId, $sPassphrase, $app['securefile.systempassphrase']);
        if ($decryptedFile === false) {
            $error = array('message' => $app['translator']->trans('DecryptMethodErrorNoDocument', array('%id%', $sId)));
            return $app->json($error, 404);
        }
        if (! $decryptedFile->checkWitness($app['securefile.witness'] ) ) {
            $error = array('message' => $app['translator']->trans('DecryptMethodErrorIncorrectPassphrase'));
            return $app->json($error, 403);
        }

        $aDecryptedFile = $decryptedFile->toArray(false);

        return $app->json(array(
            'content' => $decryptedFile->getContent(),
            'data' => $aDecryptedFile,
            'message' => ''
        ));
    }

    public function encryptAction(Request $request, Application $app)
    {
        // Sur un appel ajax, on récupère un id de fichier (ou pas si création), un titre, une liste d'auteur, une description, une passphrase
        // On stocke le fichier (nouveau ou pas, selon l'id)
        // On renvoit la liste à jour des documents

        $sId = $request->get('id');
        $sPassphrase = $request->get('passphrase');
        $bForceNewPassphrase = filter_var($request->get('forceNewPassphrase', false), FILTER_VALIDATE_BOOLEAN);

        $datas = array(
            'title'      => $request->get('title'),
            'authors'    => $request->get('authors'),
            'desc'       => $request->get('desc'),
            'content'    => $request->get('content')
        );
        if (!empty($sId)) {
            $datas['id'] = $sId;
        }

        if (empty($sPassphrase) ) {
            $error = array('message' => $app['translator']->trans('EncryptMethodErrorNoPassphrase'));
            return $app->json($error, 400);
        }
        if (! $bForceNewPassphrase) {
            // Si le fichier n'est pas nouveau,
            // on vérifie que la passphrase est similaire à celle précédemment utilisée
            $sEncryptWitness = FileFinder::getFileProperty($app['securefile.path'], $sId, 'witness');
            if (! empty($sEncryptWitness) ) {
                if (! EncryptFile::checkEncryptWitness($sEncryptWitness, $app['securefile.witness'], $sPassphrase, $app['securefile.systempassphrase']) ) {
                    $error = array('message' => $app['translator']->trans('EncryptMethodErrorNewPassphrase'));
                    return $app->json($error, 409);
                }
            }
        }

        if (empty($datas['title']) ) {
            $error = array('message' => $app['translator']->trans('EncryptMethodErrorNoTitle'));
            return $app->json($error, 400);
        }
        if (empty($datas['authors'])) {
            $error = array('message' => $app['translator']->trans('EncryptMethodErrorNoAuthor'));
            return $app->json($error, 400);
        }

        $datas['path'] = $app['securefile.path'];
        $datas['witness'] = $app['securefile.witness'];
        $encryptedFile = FileFinder::saveFile(
            $datas,
            $sPassphrase,
            $app['securefile.systempath'],
            $app['securefile.systempassphrase']
        );

        return $app->json(array(
            'data' => $encryptedFile->toArray(),
            'message' => $app['translator']->trans('EncryptMethodSuccess')
        ));
    }

    /**
     * @TODO Secure action with a temporary parameter,
     *       anonymous could not be delete a file with simple action call
     */
    public function deleteAction(Request $request, Application $app)
    {
        $sId = $request->get('id');
        if (empty($sId) ) {
            $error = array('message' => $app['translator']->trans('DeleteMethodErrorNoDocument'));
            return $app->json($error, 400);
        }

        $isSuccess = FileFinder::deleteFile($app['securefile.path'], $sId);

        if (! $isSuccess) {
            $app->json(array(
                'message' => $app['translator']->trans('DeleteMethodError')),
                400
            );
        }

        return $app->json(array(
            'message' => $app['translator']->trans('DeleteMethodSuccess')),
            200
        );
    }
}
