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

        $this['repository'] = new \ArrayObject();

        $this->loadConfig();
        $this->configureParameters();
        // $this->configureSpaces();
        $this->configurePdo();
        $this->configureService();
        $this->configureRepositories();
        $this->configureTemplateEngine();
        $this->configureLogging();
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

    protected function getRepositoryPath()
    {
        return sprintf('%s/src/Repository', $this->getRootPath());
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
    }

    /**
     * Configure services.
     */
    protected function configureService()
    {
        // Translations
        $this->register(new TranslationServiceProvider(), array(
            'locale' => 'en',
            'translation.class_path' => sprintf('%s/vendor/symfony/src', $this->getRootPath()),
            'translator.messages' => array(),
        ));
    }

    /**
     * Configure templates.
     */
    private function configureTemplateEngine()
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
        $this->configurePdoRepositories();
        // TODO: support other types of repositories
    }

    private function configurePdoRepositories()
    {
        if (!$this->pdo) {
            throw new RuntimeException('PDO not configured yet');
        }
        $ns = (new \ReflectionObject($this))->getNamespaceName();

        $dir = $this->getRepositoryPath();
        foreach (glob($dir.'/Pdo*Repository.php') as $filename) {
            $className = $ns.'\\Repository\\'.basename($filename, '.php');
            // only load the ones implements Radvance RepositoryInterface
            if (in_array('Radvance\\Repository\\PermissionRepositoryInterface', class_implements($className))) {
                $this->configurePermissionRepository($className);
            } elseif (in_array('Radvance\\Repository\\SpaceRepositoryInterface', class_implements($className))) {
                $this->configureSpaceRepository($className);
            } elseif (in_array('Radvance\\Repository\\RepositoryInterface', class_implements($className))) {
                $this->addRepository(new $className($this->pdo));
            }
        }
    }

    private function configureSpaceRepository($className)
    {
        $repo = new $className($this->pdo);

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
        $this->addRepository($repo);
    }
    private function configurePermissionRepository($className)
    {
        $repo = new $className($this->pdo);

        // checks the needed properties
        if (!$repo->getModelClassName() || !$repo->getSpaceTableForeignKeyName()) {
            throw new RuntimeException(
                'Space repository must contain the following properties:
                $modelClassName, $spaceTableForeignKeyName'
            );
        }

        $this['permissionRepository'] = $repo;
        $this['permissionModelClassName'] = $repo->getModelClassName();
        $this->addRepository($repo);
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
     * @param RepositoryInterface $repository
     */
    protected function addRepository(RepositoryInterface $repository)
    {
        $name = $repository->getTable();
        if ($name && !isset($this['repository'][$name])) {
            $this['repository'][$name] = $repository;
        } else {
            // var_dump($name);
        }
    }

    /**
     * @return RepositoryInterface[]
     */
    public function getRepositories()
    {
        if (!isset($this['repository'])) {
            return array();
        }

        return $this['repository'];
    }

    /**
     * @param string $name
     *
     * @return RepositoryInterface
     */
    public function getRepository($name)
    {
        if (!isset($this['repository'][$name])) {
            throw new Exception(sprintf(
                "Repository '%s' not found",
                $name
            ));
        }

        return $this['repository'][$name];
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

    
        $repository = Inflector::tableize($matchesArray[2]);

        if (!isset($this['repository'][$repository])) {
            throw new BadMethodCallException(
                sprintf(
                    'Repository %s does not exists',
                    $repository
                )
            );
        }

        switch ($matchesArray[1]) {
            case 'get':
                return $this['repository'][$repository];
        }
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
}
