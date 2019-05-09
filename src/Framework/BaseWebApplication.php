<?php

namespace Radvance\Framework;

use FlexAuth\Type\JWT\UserbaseJWTUserFactory;
use FlexAuthProvider\FlexAuthProvider;
use Silex\Provider\SecurityServiceProvider as SilexSecurityServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\SessionServiceProvider;
use WhoopsSilex\WhoopsServiceProvider;
use Whoops\Handler\PrettyPageHandler;
use Radvance\WhoopsHandler\UserWhoopsHandler;
use Radvance\WhoopsHandler\LogWhoopsHandler;
use Radvance\WhoopsHandler\WebhookWhoopsHandler;
use Radvance\WhoopsHandler\SentryWhoopsHandler;
use Registry\Client\ClientBuilder;
use Registry\Client\Store;
use Registry\Whoops\Formatter\RequestExceptionFormatter;
use Registry\Whoops\Handler\RegistryHandler;
use UserBase\Client\UserProvider as UserBaseUserProvider;
use Radvance\Security\RadvanceUserProvider;
use UserBase\Client\Client as UserBaseClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Silex\Application as SilexApplication;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Knp\Menu\MenuFactory;
use RuntimeException;
use PDO;
use DateTime;
use Stack\Builder as StackBuilder;
use Qandidate\Stack\UuidRequestIdGenerator;
use Qandidate\Stack\RequestId;
use Radvance\Middleware;
use Radvance\Event\PdoEventStoreDispatcher;

/**
 * Crud application using
 * routes/controllers/security/sessions/assets/themes.
 */
abstract class BaseWebApplication extends BaseConsoleApplication implements FrameworkApplicationInterface
{
    const FW_PATH_LOGIN = '/login';
    const FW_PATH_LOGINCHECK = '/authentication/login_check';
    const FW_PATH_LOGOUT = '/logout';

    protected $pdo;
    protected $spaceMenu;

    public function __construct(array $values = array())
    {
        parent::__construct($values);
        $this->configureDebug();
        $this->processMetaRequests();

        $this->configureStack();

        /*
         * A note about ordering:
         * security should be configured before the routes
         * as the routes are evaluated in order (login could be pre-empted by /{something})
         */

        $this->configureDebugBar();
        $this->debugBar['time']->startMeasure('setup', 'BaseWebApplication::setup');
        $this->configureTemplateEngine();
        $this->configureRoleProvider();
        $this->configureSecurity();
        $this->configureRoutes();
        $this->configureUrlPreprocessor();
        $this->configureExceptionHandling();
        $this->configureSpaceMenu();
        $this->configureControllerResolver();
        $this->debugBar['time']->stopMeasure('setup');

        $app = $this;
        $app->before(function (Request $request, SilexApplication $app) {
            $dispatcher = $this['dispatcher'];
            if ($dispatcher instanceof PdoEventStoreDispatcher) {
                $dispatcher->setRequest($request);
            }
            if (isset($app['sentry'])) {
                // adding sentry context from request
                $token = $app['security.token_storage']->getToken();
                if ($token) {
                    $user = $token->getUser();
                    if ('anon.' == $user) {
                        // anonymous user
                    } else {
                        $app['sentry']->user_context(
                            [
                                'id' => $user->getUsername(),
                                'email' => $user->getEmail(),
                            ]
                        );
                    }
                }
            }
        });
    }

    protected function configureDebug()
    {
        if (isset($this['parameters']['debug'])) {
            $request = Request::createFromGlobals();

            if (is_array($this['parameters']['debug'])) {
                $this['debug'] = in_array($request->getClientIp(), $this['parameters']['debug']);
            } else {
                if (!in_array(strtolower($this['parameters']['debug']), ['true', 'false'])) {
                    $ipArray = array_map('trim', explode(',', $this['parameters']['debug']));
                    $this['debug'] = in_array($request->getClientIp(), $ipArray);
                }
            }

            if ($this['debug']) {
                error_reporting(E_ALL);
                ini_set('display_errors', 'on');
            }
        }
    }

    protected $stack;

    public function configureStack()
    {
        $generator = new UuidRequestIdGenerator();

        $this->stack = new StackBuilder();

        $this->stack->push(RequestId::class, $generator, 'X-Request-Id', 'X-Request-Id');

        if (isset($this['parameters']['replace'])) {
            $config = $this['parameters']['replace'];
            $replacements = $config['replacements'];
            $this->stack->push(Middleware\ReplaceMiddleware::class, $replacements);
        }

        if (isset($this['parameters']['piwik'])) {
            $config = $this['parameters']['piwik'];
            $url = trim($config['url'], '/').'/';
            $siteId = $config['siteId'];
            $this->stack->push(Middleware\PiwikMiddleware::class, $url, $siteId);
        }

        if (isset($this['parameters']['googleanalytics'])) {
            $config = $this['parameters']['googleanalytics'];
            $siteId = $config['siteId'];
            $this->stack->push(Middleware\GoogleAnalyticsMiddleware::class, $siteId);
        }

        if (isset($this['parameters']['maintenance'])) {
            $config = $this['parameters']['maintenance'];
            if (isset($config['enabled']) && $config['enabled']) {
                $this->stack->push(Middleware\MaintenanceMiddleware::class, true, $config['whitelist']);
            }
        }

        if (isset($this['parameters']['spotclarify'])) {
            $config = $this['parameters']['spotclarify'];
            $key = $config['key'];
            $this->stack->push(Middleware\SpotClarifyMiddleware::class, $key);
        }

        if (isset($this['parameters']['hotjar'])) {
            $config = $this['parameters']['hotjar'];
            $siteId = $config['siteId'];
            $this->stack->push(Middleware\HotjarMiddleware::class, $key);
        }

        if (isset($this['parameters']['inspectlet'])) {
            $config = $this['parameters']['inspectlet'];
            $siteId = $config['siteId'];
            $this->stack->push(Middleware\InspectletMiddleware::class, $key);
        }

        if (isset($this['parameters']['request_log'])) {
            $urls = $this['parameters']['request_log']['urls'];
            $this->stack->push(Middleware\RequestLogMiddleware::class, $urls);
        }
    }

    public function getStack()
    {
        return $this->stack;
    }

    protected function processMetaRequests()
    {
        $this->before(function (Request $request, BaseConsoleApplication $app) {
            $method = null;
            switch ($request->get('_route')) {
                case 'meta_robot':
                    $method = 'robotAction';
                    break;
                case 'meta_favicon':
                    $method = 'faviconAction';
                    break;
                default:
                    break;
            }
            if ($method) {
                return (new \Radvance\Controller\MetaController())->$method();
            }
        });
    }

    protected $debugBar;

    public function configureDebugBar()
    {
        $this->debugBar = new \DebugBar\StandardDebugBar();
        if ($this['debug'] && isset($this['parameters']['debugbar']) && $this['parameters']['debugbar']) {
            $this->debugBar['time']->startMeasure('request', 'Request');
            // Wrap the pdo object in a TraceablePDO instance
            if ($this->pdo) {
                $this->debugBar['time']->startMeasure('wrappdo', 'Wrapping PDO');
                $pdo = $this->pdo;
                $this->pdo = new \DebugBar\DataCollector\PDO\TraceablePDO($pdo);
                $this->debugBar->addCollector(new \DebugBar\DataCollector\PDO\PDOCollector($this->pdo));
                $this->debugBar['time']->stopMeasure('wrappdo');
            }

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
        // Setup sentry client, expose for other use-cases
        if (isset($this['parameters']['sentry_url'])) {
            $sentryClient = new \Raven_Client($this['parameters']['sentry_url']);
            $this['sentry'] = $sentryClient;
        }

        $this->register(new WhoopsServiceProvider());
        $whoops = $this['whoops'];
        $whoops->clearHandlers();
        if ($this['debug']) {
            $whoops->pushHandler(new PrettyPageHandler());
        } else {
            $whoops->pushHandler(new UserWhoopsHandler($this));
        }
        if (isset($this['sentry'])) {
            $whoops->pushHandler(new SentryWhoopsHandler($this));
        }
        $whoops->pushHandler(new LogWhoopsHandler($this));
        if (isset($this['parameters']['exception_webhook'])) {
            $url = $this['parameters']['exception_webhook'];
            $whoops->pushHandler(new WebhookWhoopsHandler($this, $url));
        }
        if (isset($this['parameters']['exception_registry'])
            && isset($this['parameters']['exception_registry']['host'])
            && isset($this['parameters']['exception_registry']['username'])
            && isset($this['parameters']['exception_registry']['password'])
            && isset($this['parameters']['exception_registry']['account'])
            && isset($this['parameters']['exception_registry']['store'])
        ) {
            $config = [
                'api_host' => $this['parameters']['exception_registry']['host'],
                'auth' => [
                    $this['parameters']['exception_registry']['username'],
                    $this['parameters']['exception_registry']['password'],
                ],
            ];
            if (isset($this['parameters']['exception_registry']['secure'])) {
                $config['secure'] = $this['parameters']['exception_registry']['secure'];
            } else {
                $config['secure'] = true;
            }
            $store = new Store(
                new ClientBuilder($config),
                $this['parameters']['exception_registry']['account'],
                $this['parameters']['exception_registry']['store']
            );
            $handler = new RegistryHandler(new RequestExceptionFormatter(), $store);
            $whoops->pushHandler($handler);
        }
    }

    protected function configureRoutes()
    {
        // initialize meta routes before other routes
        // otherwise it's a new space or caught by apps routes
        $this->configureMetaRoutes();
        $locator = new FileLocator(
            [
                sprintf('%s/app/config', $this->getRootPath()),
                sprintf('%s/config', $this->getRootPath()),
            ]
        );
        $loader = new YamlFileLoader($locator);
        $this['fqdn_space'] = false;
        if (isset($this['fqdn']) && isset($this['fqdn']['default']) && isset($_SERVER['HTTP_HOST'])) {
            $fqdn = explode(':', $_SERVER['HTTP_HOST'])[0];
            $fqdnDefault = $this['fqdn']['default'];
            if ($fqdn != $fqdnDefault) {
                $spaceRepo = $this->getSpaceRepository();
                $space = $spaceRepo->findOneOrNullByFqdn($fqdn);
                $this['fqdn_space'] = $space;
                if (!$space) {
                    throw new RuntimeException('No space found with this FQDN: '.$fqdn);
                }
                $newCollection = $loader->load('routes-fqdn.yml');
            }
        }

        if (!$this['fqdn_space']) {
            // regular routing
            try {
                $newCollection = $loader->load('routes.yaml');
            } catch (FileLocatorFileNotFoundException $e) {
                $newCollection = $loader->load('routes.yml');
            }
        }

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
        $this->configureModuleRoutes();
    }

    protected function configureModuleRoutes()
    {
        $m = $this['module-manager'];
        foreach ($m->getModules() as $module) {
            $path = $module->getPath().'/../res/routes';
            if (file_exists($path)) {
                $loader = new YamlFileLoader(new FileLocator([$path]));
                $this['routes']->addCollection($loader->load('routing.yml'));
            }
        }
    }

    protected function configureSpaceAndPermissionRoutes()
    {
        if (isset($this['spaceRepository'])) {
            $loader = new YamlFileLoader(new FileLocator([__DIR__.'/..']));
            $this['routes']->addCollection($loader->load('space-routes.yaml'));
        }
        if (isset($this['permissionRepository'])) {
            $loader = new YamlFileLoader(new FileLocator([__DIR__.'/..']));
            $this['routes']->addCollection($loader->load('permission-routes.yaml'));
        }
    }

    protected function configureMetaRoutes()
    {
        $loader = new YamlFileLoader(new FileLocator([__DIR__.'/..']));
        $this['routes']->addCollection($loader->load('meta-routes.yaml'));
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

        $path = $this->getRootPath().'/themes/fqdn';
        if (file_exists($path)) {
            $this['twig.loader.filesystem']->addPath(
                $path,
                'FqdnTheme'
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

        $this['twig']->getExtension('core')->setDateFormat('Y-m-d', '%d days');
        if (!is_null($this->getFormat('date'))) {
            $this['twig']->getExtension('core')->setDateFormat($this->getFormat('date'), '%d days');
        }

        $app = $this;
        $this['twig']->addFilter(
            new \Twig_SimpleFilter('rdate', function ($date, $forceFormat = null) use ($app) {
                return \Radvance\Framework\BaseWebApplication::rDateTime(
                    $date,
                    (($forceFormat ?: $app->getFormat('date')) ?: 'Y-m-d')
                );
            })
        );
        $this['twig']->addFilter(
            new \Twig_SimpleFilter('rtime', function ($date, $forceFormat = null) use ($app) {
                return \Radvance\Framework\BaseWebApplication::rDateTime(
                    $date,
                    (($forceFormat ?: $app->getFormat('time')) ?: 'H:i')
                );
            })
        );

        $this['twig']->addFilter(
            new \Twig_SimpleFilter('rdatetime', function ($date, $forceFormat = null) use ($app) {
                return \Radvance\Framework\BaseWebApplication::rDateTime(
                    $date,
                    (($forceFormat ?: $app->getFormat('datetime')) ?: 'Y-m-d H:i')
                );
            })
        );

        $request = Request::createFromGlobals();
        $this['twig']->addFunction(new \Twig_SimpleFunction('asset', function ($asset) use ($request) {
            return $request->getBaseUrl().'/'.ltrim($asset, '/');
        }));

        $app = $this;
        $app->before(function (Request $request, SilexApplication $app) {
            $this['twig']->addExtension(new \Radvance\Twig\TranslateExtension($request, $app));
            $this['twig']->addFunction(new \Twig_SimpleFunction('asset', function ($asset) use ($request) {
                return $request->getBaseUrl().'/'.ltrim($asset, '/');
            }));
            $this['twig']->addFunction(new \Twig_SimpleFunction('is_granted', function ($role, $obj = null) use ($app, $request) {
                return $app['security.authorization_checker']->isGranted($role, $obj);
            }));
            $this['twig']->addFunction(new \Twig_SimpleFunction('encore', function ($key) use ($request) {
                $filename = 'build/manifest.json';
                if (!file_exists($filename)) {
                    throw new RuntimeException('encore manifest.json not found');
                }
                $manifest = json_decode(file_get_contents($filename), true);
                foreach ($manifest as $name => $uri) {
                    if ($name == $key) {
                        return $uri;
                    }
                }
                throw new RuntimeException('manifest.json does not contain '.$key);
                //return $request->getBaseUrl().'/'.ltrim($asset, '/');
            }));
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
        if (is_numeric($date)) {
            $dt = new DateTime();
            $dt->setTimestamp($date);
            $date = $dt;
        }

        if ('string' == gettype($date)) {
            $date = DateTime::createFromFormat((strpos($date, ' ') ? 'Y-m-d H:i:s' : 'Y-m-d'), $date);
        }
        if ($date instanceof DateTime) {
            return $date->format($format);
        }

        return '---';
    }

    protected function configureUrlPreprocessor()
    {
        $app = $this;
        $app->before(function (Request $request, SilexApplication $app) {
            $urlGenerator = $app['url_generator'];
            $urlGeneratorContext = $urlGenerator->getContext();

            $accountName = null;
            $spaceName = null;
            $spaceNameName = null;
            $spaceRepo = $this->getSpaceRepository();
            if ($spaceRepo) {
                // Figure out the Name of the SpaceName (hence: SpaceNameName)
                // for example `libraryName`, `projectName`, etc
                $spaceNameName = lcfirst($spaceRepo->getNameOfSpace()).'Name';
            }
            if ($this['fqdn_space']) {
                // resolve accountName and spaceName from fqdn
                $space = $this['fqdn_space'];
                $accountName = $space->getAccountName();
                $spaceName = $space->getName();
            } else {
                // try to resolve accountName and spaceName from url
                if ($request->attributes->has('accountName')) {
                    $accountName = $request->attributes->get('accountName');
                }
                if ($request->attributes->has('spaceName')) {
                    $spaceName = $request->attributes->get('spaceName');
                }
                if ($spaceNameName) {
                    if ($request->attributes->has($spaceNameName)) {
                        $spaceName = $request->attributes->get($spaceNameName);
                    }
                }
            }

            if ($accountName) {
                $app['twig']->addGlobal('accountName', $accountName);
                $app['accountName'] = $accountName;
                $urlGeneratorContext->setParameter('accountName', $accountName);
            }

            $space = null;
            if ($spaceName) {
                $space = $spaceRepo->findByNameAndAccountName($spaceName, $accountName);
            }
            if ($space) {
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
                        $repository->setFilter(
                            [($repository->getSpaceForeignKey() ?: $spaceRepo->getPermissionTableForeignKeyName()) => $space->getId()]
                        );
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

    //     return array_map(function ($repository) use ($app) {
    //         $name = $repository->getTable();

    //         return array(
    //             'href' => $app['url_generator']->generate(sprintf('%s_index', $name)),
    //             'name' => ucfirst(preg_replace('/\_/', ' ', $name))
    //         );
    //     }, $app->getRepositories()->getArrayCopy());
    // }

    protected function getFirewalls()
    {
        return [
            'api' => [
                'stateless' => true,
                'anonymous' => false,
                'pattern' => '^/api',
                'http' => true,
                'users' => $this['security.provider'],
                'guard' => [
                    'authenticators' => [
                        'flex_auth.type.jwt.security.authenticator'
                    ]
                ],
            ],
            'default' => [
                'anonymous' => true,
                'pattern' => '^/',
                'form' => [
                    'login_path' => $this->getFirewallsLoginPath(),
                    'check_path' => $this->getFirewallsLoginCheckPath(),
                ],
                'logout' => [
                    'logout_path' => $this->getFirewallsLogoutPath(),
                ],
                'users' => $this['security.provider'],
                'guard' => [
                    'authenticators' => [
                        'flex_auth.type.jwt.security.authenticator'
                    ]
                ],
            ],
        ];
    }

    protected function getFirewallsLoginPath()
    {
        if (!isset($security['paths']['login'])) {
            return self::FW_PATH_LOGIN;
        }

        return $security['paths']['login'];
    }

    protected function getFirewallsLoginCheckPath()
    {
        if (!isset($security['paths']['check'])) {
            return self::FW_PATH_LOGINCHECK;
        }

        return $security['paths']['check'];
    }

    protected function getFirewallsLogoutPath()
    {
        if (!isset($security['paths']['logout'])) {
            return self::FW_PATH_LOGOUT;
        }

        return $security['paths']['logout'];
    }

    protected function configureSecurity()
    {
        $this->register(new SilexSecurityServiceProvider(), array());

        $security = $this['security'];

        if (isset($security['encoder'])) {
            $digest = sprintf('\\Symfony\\Component\\Security\\Core\\Encoder\\%s', $security['encoder']);
            $this['security.encoder.digest'] = new $digest(true);
        }

        /* Automatically register routes for login, check and logout paths */

        $collection = new RouteCollection();

        $route = new Route(
            $this->getFirewallsLoginPath(),
            array(
                '_controller' => 'Radvance\Controller\AuthenticationController::loginAction',
            )
        );
        $collection->add('login', $route);

        $route = new Route(
            $this->getFirewallsLoginCheckPath(),
            array()
        );
        $collection->add('login_check', $route);

        $route = new Route(
            $this->getFirewallsLogoutPath(),
            array(
                '_controller' => 'Radvance\Controller\AuthenticationController::logoutAction',
            )
        );
        $collection->add('logout', $route);

        $this['routes']->addCollection($collection);

        $this['security.default_encoder'] = $this['security.encoder.digest'];
        $this['security.provider'] = $this->getUserProvider();
        $this['security.firewalls'] = $this->getFirewalls();

        $app = $this;
        $app->before(function (Request $request, SilexApplication $app) {
            try {
                $isLoggedIn = $app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY');
                if (!$isLoggedIn) {
                    return;
                }
            } catch (AuthenticationCredentialsNotFoundException $e) {
                return;
            }
            $app['current_user'] = $app['user'];
            $request->attributes->set('current_user', $app['user']);
            $app['twig']->addGlobal('current_user', $app['user']);
            $request->attributes->set('current_username', $app['user']->getUsername());
        });

        $this['flex_auth.type.jwt.user_factory'] = function () {
            return new UserbaseJWTUserFactory();
        };
    }

    protected function getUserProvider()
    {
        $userProvider = null;
        foreach ($this['security']['providers'] as $provider => $providerConfig) {
            switch ($provider) {
                case 'JsonFile':
                    $userProvider = new \Radvance\Component\Security\JsonFileUserProvider(
                        realpath($providerConfig['path']) ? $providerConfig['path'] : ($this->getRootPath().'/'.$providerConfig['path'])
                    );
                    break 2;
                // case 'Pdo':
                //     $dbmanager = new DatabaseManager();

                // return new \Radvance\Security\PdoUserProvider(
                //     $dbmanager->getPdo($providerConfig['database'])
                // );
                // deprecated: use flex-auth with userbase auth type
                case 'UserBase':
                    if (!empty($providerConfig['dsn'])) {
                        $client = new UserBaseClient($providerConfig['dsn'], null, null);
                    } else {
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
                    }
                    $client->setTimeDataCollector($this->debugBar['time']);
                    $client->setCache($this['cache']);
                    $this['userbase.client'] = $client;

                    $userProvider = new UserBaseUserProvider($client);
                    break 2;
                case 'FlexAuth':
                    $this->register(new FlexAuthProvider());
                    $userProvider = $this['flex_auth.security.user_provider'];
                    $this['flex_auth.jwt.redirect_login_page'] = "/login";
                    break 2;
                default:
            }
        }

        if (!$userProvider) {
            throw new RuntimeException('Cannot find a user provider');
        }

        if (isset($this['security.role_provider'])) {
            // Wrap the custom userprovider
            $userProvider = new RadvanceUserProvider($userProvider, $this['security.role_provider']);
        }

        return $userProvider;
    }

    /**
     * @deprecated Use BaseWebApplication::getUserProvider
     */
    protected function getUserSecurityProvider()
    {
        return $this->getUserProvider();
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

        $app['argument_value_resolvers'] = function ($app) {
            return array_merge(
                array(
                    new \Silex\AppArgumentValueResolver($app),
                    new ParameterArgumentValueResolver($app),
                    new TypeArgumentValueResolver($app),
                ),
                \Symfony\Component\HttpKernel\Controller\ArgumentResolver::getDefaultArgumentValueResolvers()
            );
        };
    }

    protected function configureRoleProvider()
    {
        // By default, don't do anything
    }
}
