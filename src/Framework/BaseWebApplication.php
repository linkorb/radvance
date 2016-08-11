<?php

namespace Radvance\Framework;

use Silex\Provider\SecurityServiceProvider as SilexSecurityServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\SessionServiceProvider;
use WhoopsSilex\WhoopsServiceProvider;
use Whoops\Handler\PrettyPageHandler;
use Radvance\WhoopsHandler\UserWhoopsHandler;
use Radvance\WhoopsHandler\LogWhoopsHandler;
use Radvance\WhoopsHandler\WebhookWhoopsHandler;
use UserBase\Client\UserProvider as UserBaseUserProvider;
use UserBase\Client\Client as UserBaseClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Silex\Application as SilexApplication;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\ListRenderer;
use RuntimeException;
use PDO;

/**
 * Crud application using
 * routes/controllers/security/sessions/assets/themes.
 */
abstract class BaseWebApplication extends BaseConsoleApplication implements FrameworkApplicationInterface
{
    protected $pdo;
    protected $spaceMenu;

    public function __construct(array $values = array())
    {
        parent::__construct($values);

        /*
         * A note about ordering:
         * security should be configured before the routes
         * as the routes are evaluated in order (login could be pre-empted by /{something})
         */
        $this->configureDebugBar();
        $this->debugBar['time']->startMeasure('setup', 'BaseWebApplication::setup');
        $this->configureTemplateEngine();
        $this->configureSecurity();
        $this->configureRoutes();
        $this->configureUrlPreprocessor();
        $this->configureExceptionHandling();
        $this->configureSpaceMenu();
        $this->configureControllerResolver();
        $this->debugBar['time']->stopMeasure('setup');
    }

    protected $debugBar;

    public function configureDebugBar()
    {
        $this->debugBar = new \DebugBar\StandardDebugBar();
        if ($this['debug'] && isset($this['parameters']['debugbar']) && $this['parameters']['debugbar']) {
            // Wrap the pdo object in a TraceablePDO instance
            $this->debugBar['time']->startMeasure('request', 'Request');
            $this->debugBar['time']->startMeasure('wrappdo', 'Wrapping PDO');
            $pdo = $this->pdo;
            $this->pdo = new \DebugBar\DataCollector\PDO\TraceablePDO($pdo);
            $this->debugBar->addCollector(new \DebugBar\DataCollector\PDO\PDOCollector($this->pdo));
            $this->debugBar['time']->stopMeasure('wrappdo');

            $this->after(function (Request $request, Response $response) {
                $this->debugBar['messages']->error('yo');
                $body = $response->getContent();
                $renderer = $this->debugBar->getJavascriptRenderer();

                // Re-gegenerate the assets for debugbar in the webroot
                $renderer->setIncludeVendors(false);
                $path = getcwd();
                $renderer->dumpJsAssets($path.'/debugbar.js');
                $renderer->dumpCssAssets($path.'/debugbar.css');

                $this->debugBar['time']->stopMeasure('request');

                // Inject the debugBarHtml before the closing body tag
                $debugBarHtml = '';
                $debugBarHtml .= '<script type="text/javascript" src="'.$request->getBasePath().'/debugbar.js"></script>';
                $debugBarHtml .= '<link rel="stylesheet" type="text/css" href="'.$request->getBasePath().'/debugbar.css">';
                $debugBarHtml .= $renderer->render();
                $body = str_replace('</body>', $debugBarHtml.'</body>', $body);
                $response->setContent($body);
            });
        }
    }

    public function getDebugBar()
    {
        return $this->debugBar;
    }

    public function getAssetsPath()
    {
        return sprintf('%s/assets', $this->getRootPath());
    }

    public function getThemePath($global = false)
    {
        if (isset($this['parameters']['theme'])) {
            return $this['parameters']['theme'];
        }

        return sprintf(
            '%s/themes/%s',
            $global ? sprintf('%s/../..', rtrim(__DIR__)) : $this->getRootPath(),
            isset($this['parameters']['theme']) ? $this['parameters']['theme'] : 'default'
        );
    }

    protected function getSessionsPath()
    {
        return sprintf('/tmp/%s/sessions', $this['app']['name']);
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
            'session.storage.save_path' => $this->getSessionsPath(),
        ));

        // Forms
        $this->register(new FormServiceProvider());
    }

    protected function configureExceptionHandling()
    {
        $this->register(new WhoopsServiceProvider());
        $whoops = $this['whoops'];
        $whoops->clearHandlers();
        if ($this['debug']) {
            $whoops->pushHandler(new PrettyPageHandler());
        } else {
            $whoops->pushHandler(new UserWhoopsHandler($this));
        }
        $whoops->pushHandler(new LogWhoopsHandler($this));
        if (isset($this['parameters']['exception_webhook'])) {
            $url = $this['parameters']['exception_webhook'];
            $whoops->pushHandler(new WebhookWhoopsHandler($this, $url));
        }
    }

    protected function configureRoutes()
    {
        $locator = new FileLocator(array(
            $this->getRoutesPath(),
        ));
        $loader = new YamlFileLoader($locator);
        $newCollection = $loader->load('routes.yml');
        $orgCollection = $this['routes'];
        foreach ($newCollection->all() as $name => $route) {
            //echo $name .'/' . $route->getPath();
            foreach ($orgCollection->all() as $orgName => $orgRoute) {
                if ($name == $orgName) {
                    throw new RuntimeException(
                        'Duplicate definition of route: `'.$name.'`. Please remove it from the routes.yml files'
                    );
                }
            }
        }

        $orgCollection->addCollection($newCollection);
        $this->configureSpaceAndPermissionRoutes();
    }

    protected function configureSpaceAndPermissionRoutes()
    {
        if (isset($this['spaceRepository'])) {
            $loader = new YamlFileLoader(new FileLocator([__DIR__.'/..']));
            $this['routes']->addCollection($loader->load('space-routes.yml'));
        }
        if (isset($this['permissionRepository'])) {
            $loader = new YamlFileLoader(new FileLocator([__DIR__.'/..']));
            $this['routes']->addCollection($loader->load('permission-routes.yml'));
        }
    }

    protected function configureTemplateEngine()
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

        // JF & HL: decide not to use this coz
        // 1. hardly ever useful
        // 2. breaks the loading order of routes and template engine
        // $this['twig']->addGlobal('main_menu', $this->buildMenu($this));
        $this['twig']->addGlobal('app_name', $this['app']['name']);
        // $this['twig']->addGlobal('spaceConfig', $this->getSpaceConfig());
        // Define userbaseUrl in twig templates for login + signup links
        if (isset($this['userbaseUrl'])) {
            $this['twig']->addGlobal('userbaseUrl', $this['userbaseUrl']);
        }
        if (isset($this['parameters']['userbase_url'])) {
            $this['twig']->addGlobal('userbase_url', $this['parameters']['userbase_url']);
        }

        $app = $this;
        $this['twig']->addFilter(
            new \Twig_SimpleFilter('rdate', function ($date, $forceFormat = null) use ($app) {
                return \Radvance\Framework\BaseWebApplication::rDateTime(
                    $date,
                    (($forceFormat?:$app->getFormat('date')) ?: 'Y-m-d')
                );
            })
        );
        $this['twig']->addFilter(
            new \Twig_SimpleFilter('rtime', function ($date, $forceFormat = null) use ($app) {
                return \Radvance\Framework\BaseWebApplication::rDateTime(
                    $date,
                    (($forceFormat?:$app->getFormat('time')) ?: 'H:i')
                );
            })
        );

        $this['twig']->addFilter(
            new \Twig_SimpleFilter('rdatetime', function ($date, $forceFormat = null) use ($app) {
                return \Radvance\Framework\BaseWebApplication::rDateTime(
                    $date,
                    (($forceFormat?:$app->getFormat('datetime')) ?: 'Y-m-d H:i')
                );
            })
        );


        $app = $this;
        $app->before(function (Request $request, SilexApplication $app) {
            $this['twig']->addExtension(new \Radvance\Twig\TranslateExtension($request, $app));
        });
    }

    public function getFormat($key)
    {
        return isset($this['parameters']['format'][$key]) ? $this['parameters']['format'][$key] : null;
    }

    public static function rDateTime($date, $format)
    {
        if (!$date) {
            return '-';
        }
        if (gettype($date) == 'string') {
            $date = \DateTime::createFromFormat((strpos($date, ' ')?'Y-m-d H:i:s':'Y-m-d'), $date);
        }

        return $date->format($format);
    }

    protected function configureUrlPreprocessor()
    {
        $app = $this;
        $app->before(function (Request $request, SilexApplication $app) {
            $urlGenerator = $app['url_generator'];
            $urlGeneratorContext = $urlGenerator->getContext();

            if ($request->attributes->has('accountName')) {
                $accountName = $request->attributes->get('accountName');

                $app['twig']->addGlobal('accountName', $accountName);
                $app['accountName'] = $accountName;
                $urlGeneratorContext->setParameter('accountName', $accountName);
            }
            
            $spaceName = null;
            if ($request->attributes->has('spaceName')) {
                $spaceName = $request->attributes->get('spaceName');
            }
            $spaceRepo = $this->getSpaceRepository();
            if ($spaceRepo) {
                // Figure out the Name of the SpaceName (hence: SpaceNameName)
                $spaceNameName = lcfirst($spaceRepo->getNameOfSpace()) . 'Name';
                if ($request->attributes->has($spaceNameName)) {
                    $spaceName = $request->attributes->get($spaceNameName);
                }
                
                if ($spaceName) {
                    $space = $spaceRepo->findByNameAndAccountName($spaceName, $accountName);
                    $app['twig']->addGlobal('spaceName', $spaceName);
                    $app['twig']->addGlobal($spaceNameName, $spaceName);
                    $app['spaceName'] = $spaceName;
                    $app[$spaceNameName] = $spaceName;
                    $urlGeneratorContext->setParameter('spaceName', $spaceName);
                    $urlGeneratorContext->setParameter($spaceNameName, $spaceName);
                    $app['space'] = $space;
                    $app[ucfirst($space->getName())] = $space;
                    
                    foreach ($this->getRepositories() as $repository) {
                        if ($repository instanceof \Radvance\Repository\GlobalRepositoryInterface) {
                        } else {
                            $repository->setFilter([$spaceRepo->getPermissionTableForeignKeyName() => $space->getId()]);
                        }
                    }
                }
            }
        });
    }

    // protected function buildMenu($app)
    // {
    //     if (!$app->getRepositories()) {
    //         return array();
    //     }
    //
    //     return array_map(function ($repository) use ($app) {
    //         $name = $repository->getTable();
    //
    //         return array(
    //             'href' => $app['url_generator']->generate(sprintf('%s_index', $name)),
    //             'name' => ucfirst(preg_replace('/\_/', ' ', $name))
    //         );
    //     }, $app->getRepositories()->getArrayCopy());
    // }

    protected function configureSecurity()
    {
        $this->register(new SilexSecurityServiceProvider(), array());

        $security = $this['security'];

        if (isset($security['encoder'])) {
            $digest = sprintf('\\Symfony\\Component\\Security\\Core\\Encoder\\%s', $security['encoder']);
            $this['security.encoder.digest'] = new $digest(true);
        }

        $loginPath = isset($security['paths']['login']) ? $security['paths']['login'] : '/login';
        $checkPath = isset($security['paths']['check']) ? $security['paths']['check'] : '/authentication/login_check';
        $logoutPath = isset($security['paths']['logout']) ? $security['paths']['logout'] : '/logout';

        /* Automatically register routes for login, check and logout paths */

        $collection = new RouteCollection();

        $route = new Route(
            $loginPath,
            array(
                '_controller' => 'Radvance\Controller\AuthenticationController::loginAction',
            )
        );
        $collection->add('login', $route);

        $route = new Route(
            $checkPath,
            array()
        );
        $collection->add('login_check', $route);

        $route = new Route(
            $logoutPath,
            array(
                '_controller' => 'Radvance\Controller\AuthenticationController::logoutAction',
            )
        );
        $collection->add('logout', $route);

        $this['routes']->addCollection($collection);

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
                    'login_path' => $loginPath,
                    'check_path' => $checkPath,
                ),
                'logout' => array(
                    'logout_path' => $logoutPath,
                ),
                'users' => $this->getUserSecurityProvider(),
            ),
        );

        $app = $this;
        $app->before(function (Request $request, SilexApplication $app) {
            $token = $app['security.token_storage']->getToken();
            if ($token) {
                if ($request->getRequestUri() != '/login') {
                    if ($token->getUser() == 'anon.') {
                        // visitor is not authenticated
                    } else {
                        // visitor is authenticated
                        $app['current_user'] = $token->getUser();
                        $app['twig']->addGlobal('current_user', $token->getUser());
                    }
                }
            }
        });
    }

    protected function getUserSecurityProvider()
    {
        foreach ($this['security']['providers'] as $provider => $providerConfig) {
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
                        throw new RuntimeException('Userbase URL not configured');
                    }
                    if (!$providerConfig['username']) {
                        throw new RuntimeException('Userbase username not configured');
                    }
                    if (!$providerConfig['password']) {
                        throw new RuntimeException('Userbase password not configured');
                    }

                    $client = new UserBaseClient(
                        $providerConfig['url'],
                        $providerConfig['username'],
                        $providerConfig['password']
                    );
                    $this['userbase.client'] = $client;

                    return new UserBaseUserProvider($client);

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

    public function getSpace($spaceName = 'spaceName')
    {
        if (!$this['current_user']) {
            throw new AccessDeniedException('Access denied. Please login first.');
        }

        return $this->getSpaceRepository()->findByAccountNameSpaceNameUsername(
            $this['accountName'],
            $this[$spaceName],
            $this['current_user']->getName()
        );
    }

    public function configureSpaceMenu()
    {
        $factory = new MenuFactory();
        $this->spaceMenu = $factory->createItem('Space menu');
        $this['twig']->addGlobal('space_menu', $this->spaceMenu);
    }

    public function getSpaceMenu()
    {
        return $this->spaceMenu;
    }

    public function configureControllerResolver()
    {
        $app = $this;
        $this->extend('resolver', function ($resolver, $app) {
            return new ControllerResolver($app);
        });
    }
}
