<?php

namespace Radvance\Framework;

use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SecurityServiceProvider as SilexSecurityServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\MonologServiceProvider;

use UserBase\Client\UserProvider as UserBaseUserProvider;
use UserBase\Client\Client as UserBaseClient;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Parser as YamlParser;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use RuntimeException;
use PDO;

/**
 * Crud application using
 * routes/controllers/security/sessions/assets/themes
 */
abstract class BaseWebApplication extends BaseConsoleApplication implements FrameworkApplicationInterface
{
    protected $pdo;

    public function __construct(array $values = array())
    {
        parent::__construct($values);
        $this->configureRoutes();
        $this->configureTemplateEngine();
        $this->configureSecurity();
    }

    public function getAssetsPath()
    {
        return sprintf('%s/assets', $this->getRootPath());
    }

    public function getThemePath($global = false)
    {
        return sprintf(
            '%s/themes/%s',
            $global ? sprintf('%s/../..', rtrim(__DIR__)) : $this->getRootPath(),
            isset($this['theme']) ? $this['theme'] : 'default'
        );
    }

    protected function getSessionsPath()
    {
        return sprintf('/tmp/%s/sessions', $this['parameters']['name']);
    }

    protected function getRoutesPath()
    {
        return sprintf('%s/app/config', $this->getRootPath());
    }

    protected function configureService()
    {
        parent::configureService();

        // Routings
        $this->register(new RoutingServiceProvider());

        // Sessions
        $this->register(new SessionServiceProvider(), array(
            'session.storage.save_path' => $this->getSessionsPath()
        ));

        // Forms
        $this->register(new FormServiceProvider());
    }

    protected function configureRoutes()
    {
        $locator = new FileLocator(array(
            $this->getRoutesPath()
        ));
        $loader = new YamlFileLoader($locator);
        $this['routes'] = $loader->load('routes.yml');
    }

    private function configureTemplateEngine()
    {
        $this['twig.loader.filesystem']->addPath(
            $this->getThemePath(true),
            'BaseTheme'
        );

        $path = $this->getThemePath(false);
        if (file_exists($path)) {
            $this['twig.loader.filesystem']->addPath(
                $path,
                'Theme'
            );
        }

        $this['twig.loader.filesystem']->addPath(
            sprintf('%s/../../templates', __DIR__),
            'BaseTemplates'
        );

        $this['twig.loader.filesystem']->addPath(
            sprintf('%s/templates', $this->getRootPath()),
            'Templates'
        );

        $this['twig']->addGlobal('main_menu', $this->buildMenu($this));
        $this['twig']->addGlobal('app_name', $this['parameters']['name']);
    }

    protected function buildMenu($app)
    {
        if (!$app->getRepositories()) {
            return array();
        }

        return array_map(function($repository) use ($app) {
            $name = $repository->getTable();

            return array(
                'href' => $app['url_generator']->generate(sprintf('%s_index', $name)),
                'name' => ucfirst(preg_replace('/\_/', ' ', $name))
            );
        }, $app->getRepositories()->getArrayCopy());
    }

    protected function configureSecurity()
    {
        $this->register(new SilexSecurityServiceProvider(), array());

        $security = $this['parameters']['security'];

        if (isset($security['encoder'])) {
            $digest = sprintf('\\Symfony\\Component\\Security\\Core\\Encoder\\%s', $security['encoder']);
            $this['security.encoder.digest'] = new $digest(true);
        }

        $this['security.firewalls'] = array(
            'api' => array(
                'stateless' => true,
                'anonymous' => false,
                'pattern' => '^/api',
                'http' => true,
                'users' => $this->getUserSecurityProvider(),
            ),
            'default' => array(
                'anonymous' => true,
                'pattern' => '^/',
                'form' => array(
                    'login_path' => isset($security['paths']['login']) ? $security['paths']['login'] : '/login',
                    'check_path' => isset($security['paths']['check']) ? $security['paths']['check'] : '/authentication/login_check'
                ),
                'logout' => array(
                    'logout_path' => isset($security['paths']['logout']) ? $security['paths']['logout'] : '/logout'
                ),
                'users' => $this->getUserSecurityProvider(),
            ),
        );
    }

    protected function getUserSecurityProvider()
    {
        foreach ($this['parameters']['security']['providers'] as $provider => $providerConfig) {
            switch ($provider) {
                // case 'JsonFile':
                // return new \Radvance\Security\JsonFileUserProvider(__DIR__.'/../'.$providerConfig['path']);
                // case 'Pdo':
                //     $dbmanager = new DatabaseManager();
                //
                // return new \Radvance\Security\PdoUserProvider(
                //     $dbmanager->getPdo($providerConfig['database'])
                // );
                case 'UserBase':
                    // Sanity checks
                    if (!$providerConfig['url']) {
                        throw new RuntimeException("Userbase URL not configured");
                    }
                    if (!$providerConfig['username']) {
                        throw new RuntimeException("Userbase username not configured");
                    }
                    if (!$providerConfig['password']) {
                        throw new RuntimeException("Userbase password not configured");
                    }
                    return new UserBaseUserProvider(
                        new UserBaseClient(
                            $providerConfig['url'],
                            $providerConfig['username'],
                            $providerConfig['password']
                        )
                    );
                default:
                    break;
            }
        }
        throw new RuntimeException('Cannot find any security provider');
    }

    public function isGranted($attributes, $object = null)
    {
        return $this['security.authorization_checker']->isGranted($attributes, $object);
    }

    public function denyAccessUnlessGranted($attributes, $object = null, $message = 'Access Denied.')
    {
        if (!$this->isGranted($attributes, $object)) {
            throw new AccessDeniedException($message);
        }
    }

    public function addFlash($type, $message)
    {
        $this['session']->getFlashBag()->add($type, $message);
    }
}
