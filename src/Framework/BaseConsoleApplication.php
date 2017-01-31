<?php

namespace Radvance\Framework;

use Silex\Application as SilexApplication;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Radvance\Repository\RepositoryInterface;
use Radvance\Exception\BadMethodCallException;
use Radvance\Component\Config\ConfigLoader;
use Doctrine\Common\Inflector\Inflector;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Radvance\Translation\RecursiveYamlFileMessageLoader;
use InteroPhp\ModuleManager\ModuleManagerInterface;
use InteroPhp\ModuleManager\ModuleManager;
use Minerva\Orm\RepositoryManager;
use Aws\S3\S3Client;
use Exception;
use RuntimeException;
use PDO;

abstract class BaseConsoleApplication extends SilexApplication implements FrameworkApplicationInterface
{
    protected $pdo;
    protected $rootPath = null;

    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this->loadConfig();
        $this->configureParameters();
        // $this->configureSpaces();
        $this->configurePdo();
        $this->configureService();
        $this->configureTemplateService();
        $this->configureRepositories();
        $this->configureLogging();
        $this->configureObjectStorage();
        $this->configureModuleManager();
        $this->configureModules();
        $this->configureDispatcher();
        $this->initModules();
    }

    // abstract public function getRootPath();
    public function getRootPath()
    {
        if (null === $this->rootPath) {
            if (method_exists($this, 'setRootPath')) {
                $this->rootPath = realpath($this->setRootPath());
            } else {
                $this->rootPath = realpath(__DIR__.'/../../../../..');
            }
        }

        return $this->rootPath;
    }

    public function getTemplatesPath()
    {
        return sprintf('%s/templates', $this->getRootPath());
    }

    protected function getLogsPath()
    {
        if (isset($this['parameters']['logging']['file'])) {
            $file = $this['parameters']['logging']['file'];
            if (strpos($file, '/') !== 0) {
                $file = sprintf('%s/'.$file, $this->getRootPath());
            }
        } else {
            $file = sprintf('%s/app/logs/development.log', $this->getRootPath());
        }

        return str_replace('//', '/', $file);
    }

    protected function loadConfig()
    {
        $loader = new ConfigLoader();
        $path = $this->getRootPath().'/app/config';
        if (file_exists($path.'/config.yml')) {
            $config = $loader->load($path, 'config.yml');
        } else {
            // Legacy config mode
            $config = array();
            $config['parameters'] = $loader->load($path, 'parameters.yml');
            $config['app']['name'] = $config['parameters']['name'];
            $config['security'] = $config['parameters']['security'];
        }

        // Add the config data to the DI container
        foreach ($config as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * Configure parameters.
     */
    protected function configureParameters()
    {
        $this['debug'] = false;
        if (isset($this['parameters']['debug'])) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'on');
            $this['debug'] = (bool) $this['parameters']['debug'];
        }

        $this['locale'] = 'en_US';
        if (isset($this['parameters']['locale'])) {
            $this['locale'] = $this['parameters']['locale'];
        }
        setlocale(LC_ALL, $this['locale']);
        
        $this['timezone'] = 'UTC';
        if (isset($this['parameters']['timezone'])) {
            $this['timezone'] = $this['parameters']['timezone'];
        }
        date_default_timezone_set($this['timezone']);
    }

    /**
     * Configure PDO.
     */
    protected function configurePdo()
    {
        if (!isset($this['parameters']['pdo'])) {
            return;
        }

        $url = $this['parameters']['pdo'];

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $user = parse_url($url, PHP_URL_USER);
        $pass = parse_url($url, PHP_URL_PASS);
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $dbname = parse_url($url, PHP_URL_PATH);
        if (!$port) {
            $port = 3306;
        }

        $dsn = sprintf(
            '%s:dbname=%s;host=%s;port=%d',
            $scheme,
            substr($dbname, 1),
            $host,
            $port
        );
        //echo $dsn;exit();

        $this->pdo = new PDO($dsn, $user, $pass);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this['pdo'] = $this->pdo;
    }

    /**
     * Configure services.
     */
    protected function configureService()
    {
        // Translations
        $this['locale_fallbacks'] = array('en_US');
        $this->register(new TranslationServiceProvider());
        
        $translator = $this['translator'];
        $translator->addLoader('yaml', new RecursiveYamlFileMessageLoader());
        
        $files = glob($this->getRootPath() .'/app/l10n/*.yml');
        foreach ($files as $filename) {
            $locale = str_replace('.yml', '', basename($filename));
            $translator->addResource('yaml', $filename, $locale);
        }

    }

    /**
     * Configure templates.
     */
    protected function configureTemplateService()
    {
        $this->register(new TwigServiceProvider(), array(
            'twig.path' => array(
                $this->getTemplatesPath(),
            ),
        ));
    }

    /**
     * Configure logging.
     */
    protected function configureLogging()
    {
        if (isset($this['parameters']['logging'])) {
            $this->register(new MonologServiceProvider(), array(
                'monolog.logfile' => $this->getLogsPath(),
            ));
        }
    }

/**
 * Configure repositories.
 */
    // abstract protected function configureRepositories();
    protected function configureRepositories()
    {
        $repositoryManager = new RepositoryManager();
        $this['repository-manager'] = $repositoryManager;
        
        $path = sprintf('%s/src/Repository', $this->getRootPath());

        $ns = (new \ReflectionObject($this))->getNamespaceName() . '\\Repository';
        $repositoryManager->autoloadPdoRepositories($path, $ns, $this->pdo);
        
        foreach ($repositoryManager->getRepositories() as $repository) {
            if (is_a($repository, 'Radvance\\Repository\\PermissionRepositoryInterface')) {
                $this->configurePermissionRepository($repository);
            }
            
            if (is_a($repository, 'Radvance\\Repository\\SpaceRepositoryInterface')) {
                $this->configureSpaceRepository($repository);
            }
            // TODO: support other types of repositories
        }
    }

    protected function configureSpaceRepository($repo)
    {
        // checks the needed properties
        if (!$repo->getModelClassName()
            || !$repo->getNameOfSpace()
            || !$repo->getPermissionTableName()
            || !$repo->getPermissionTableForeignKeyName()
        ) {
            throw new RuntimeException(
                'Space repository must contain the following properties:
                $modelClassName, $nameOfSpace, $permissionTableName, $permissionTableForeignKeyName'
            );
        }

        $this['spaceRepository'] = $repo;
        $this['spaceModelClassName'] = $repo->getModelClassName();
    }
    
    protected function configurePermissionRepository($repo)
    {
        // checks the needed properties
        if (!$repo->getModelClassName() || !$repo->getSpaceTableForeignKeyName()) {
            throw new RuntimeException(
                'Space repository must contain the following properties:
                $modelClassName, $spaceTableForeignKeyName'
            );
        }

        $this['permissionRepository'] = $repo;
        $this['permissionModelClassName'] = $repo->getModelClassName();
    }

    public function getSpaceRepository()
    {
        return isset($this['spaceRepository']) ? $this['spaceRepository'] : null;
    }

    public function getPermissionRepository()
    {
        return isset($this['permissionRepository']) ? $this['permissionRepository'] : null;
    }

    /**
     * @return RepositoryInterface[]
     */
    public function getRepositories()
    {
        $res = [];
        foreach ($this['repository-manager']->getRepositories() as $repository) {
            $res[$repository->getTableName()] = $repository;
        }
        return $res;
    }

    /**
     * @param string $name
     *
     * @return RepositoryInterface
     */
    public function getRepository($name)
    {
        if (!isset($this['repository-manager'])) {
            throw new RuntimeException("Repository manager not (yet) initialized");
        }
        return $this['repository-manager']->getRepositoryByTableName($name);
    }

    /**
     * Magic getXxxRepository.
     *
     * @param mixed $name
     * @param mixed $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (!preg_match('/^(get)(.+)Repository$/', $name, $matchesArray)) {
            throw new BadMethodCallException(
                sprintf(
                    'Method "%s" does not exist on "%s"',
                    $name,
                    get_class($this)
                )
            );
        }

        $name = Inflector::tableize($matchesArray[2]);
        return $this['repository-manager']->getRepositoryByTableName($name);
    }
    
    public function configureObjectStorage()
    {
        $adapterName = null;
        if (isset($this['parameters']['objectstorage_adapter'])) {
            $adapterName = $this['parameters']['objectstorage_adapter'];
        }
        if (!$adapterName) {
            // Setup a default path in /tmp during development
            $appName = strtolower(str_replace(' ', '-', $this['app']['name']));
            $filePath = $this->getRootPath() . '/app/storage/';
            if (!file_exists($filePath)) {
                mkdir($filePath);
            }
            $adapter = new \ObjectStorage\Adapter\FileAdapter($filePath);
        } else {
            switch ($adapterName) {
                case 'file':
                    $filepath = $this['parameters']['objectstorage_file_path'];
                    $adapter = new \ObjectStorage\Adapter\FileAdapter($filepath);
                    break;
                case 's3':
                    $bucketName = $this['parameters']['objectstorage_s3_bucket'];
                    $prefix = $this['parameters']['objectstorage_s3_prefix'];
                    $key = (string)$this['parameters']['objectstorage_s3_key'];
                    $secret = (string)$this['parameters']['objectstorage_s3_secret'];
                    $s3client = S3Client::factory(array(
                        'key' => $key,
                        'secret' => $secret
                    ));
                    $adapter = new \ObjectStorage\Adapter\S3Adapter($s3client, $bucketName, $prefix);
                    break;
                default:
                    throw new RuntimeException('Unsupported objectstorage adapter: ' . $adapterName);
            }
        }
        
        if (isset($this['parameters']['objectstorage_encryption_key'])) {
            // Wrap the adapter in an encryption adapter
            $key = $this['parameters']['objectstorage_encryption_key'];
            $iv = $this['parameters']['objectstorage_encryption_iv'];
            $adapter = new \ObjectStorage\Adapter\EncryptionAdapter($adapter, $key, $iv);
        }
        
        if (isset($this['parameters']['objectstorage_bzip2_level'])) {
            // Wrap the adapter in a compression adapter
            $level = $this['parameters']['objectstorage_bzip2_level'];
            $adapter = new \ObjectStorage\Adapter\Bzip2Adapter($adapter, $level);
        }
    
        $this['objectstorage'] = $adapter;
    }

    // protected $spaceConfig;
    //
    // public function configureSpaces()
    // {
    //     $spaceConfig = new SpaceConfig();
    //
    //     $this->spaceConfig = $spaceConfig->setTableName('book')
    //         ->setModelClassName('\Radvance\Model\Space')
    //         ->setRepositoryClassName('\Radvance\Repository\PdoSpaceRepository')
    //         ->setDisplayName('B00k')
    //         ->setDisplayNamePlural('B00kz')
    //         ->setPermissionToSpaceForeignKeyName('space_id');
    // }
    //
    // public function getSpaceConfig()
    // {
    //     return $this->spaceConfig;
    // }

    protected function configureModuleManager()
    {
        $m = new ModuleManager();
        $this['module-manager'] = $m;
    }
    
    protected function configureModules()
    {
        // implement this method in your main application
        // in order to register 'modules'
    }
    
    protected function initModules()
    {
        $m = $this['module-manager'];
        $repositoryManager = $this['repository-manager'];
        foreach ($m->getModules() as $module) {
            //print_r($provider);
            $ns = $module->getNamespace() . '\\Repository';
            $shortName = $module->getName();
            $modulePath = $module->getPath();
            $repositoryManager->autoloadPdoRepositories($modulePath . '/Repository', $ns, $this->pdo);
            
            $templatePath = $modulePath . '/res/views';
            if (file_exists($templatePath)) {
                $this['twig.loader.filesystem']->addPath(
                    $templatePath,
                    $shortName . 'Module'
                );
            }
        }
    }
    
    protected function configureDispatcher()
    {
        $app = $this;
        
        if (!isset($this['event_store']) || !isset($this['event_store']['table_name'])) {
            return;
        }
        
        // Wrap the standard Symfony Event Dispatcher
        $app->extend(
            'dispatcher',
            function (
                $dispatcher,
                \Silex\Application $app
            ) {
                $service = new \Radvance\Event\PdoEventStoreDispatcher(
                    $dispatcher,
                    $app['pdo']
                );
                $service->setApp($app);
                return $service;
            }
        );
    }
}
