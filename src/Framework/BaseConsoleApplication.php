<?php

namespace Radvance\Framework;

use Silex\Application as SilexApplication;

use Silex\Provider\TwigServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\MonologServiceProvider;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Parser as YamlParser;

use Radvance\Repository\RepositoryInterface;
use Radvance\Exception\BadMethodCallException;

use Exception;
use RuntimeException;
use PDO;

abstract class BaseConsoleApplication extends SilexApplication implements FrameworkApplicationInterface
{
    protected $pdo;

    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this->configureParameters();
        $this->configurePdo();
        $this->configureService();
        $this->configureRepositories();
        $this->configureTemplateEngine();
        $this->configureLogging();
    }

    abstract function getRootPath();

    public function getTemplatesPath()
    {
        return sprintf('%s/templates', $this->getRootPath());
    }

    protected function getParametersPath()
    {
        return sprintf('%s/app/config/parameters.yml', $this->getRootPath());
    }

    protected function getLogsPath()
    {
        return sprintf('%s/app/logs/development.log', $this->getRootPath());
    }

    protected function getParameters()
    {
        $parser = new YamlParser();
        return $parser->parse(file_get_contents($this->getParametersPath()));
    }

    /**
     * Configure parameters
     */
    protected function configureParameters()
    {
        $this['parameters'] = $this->getParameters();

        $this['debug'] = false;
        if (isset($this['parameters']['debug'])) {
            $this['debug'] = !!$this['parameters']['debug'];
        }
    }

    /**
     * Configure PDO
     */
    protected function configurePdo()
    {
        if (!isset($this['parameters']['pdo'])) {
            throw new RuntimeException("Missing required PDO configuration");
        }

        $url = $this['parameters']['pdo'];

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $user = parse_url($url, PHP_URL_USER);
        $pass = parse_url($url, PHP_URL_PASS);
        $host = parse_url($url, PHP_URL_HOST);
        $dbname = parse_url($url, PHP_URL_PATH);

        $dsn = sprintf(
            '%s:dbname=%s;host=%s',
            $scheme,
            substr($dbname, 1),
            $host
        );
        //echo $dsn;exit();

        $this->pdo = new PDO($dsn, $user, $pass);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Configure services
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
     * Configure templates
     */
    private function configureTemplateEngine()
    {
        $this->register(new TwigServiceProvider(), array(
            'twig.path' => array(
                $this->getTemplatesPath()
            ),
        ));
    }

    /**
     * Configure logging
     */
    protected function configureLogging()
    {
        $this->register(new MonologServiceProvider(), array(
            'monolog.logfile' => $this->getLogsPath()
        ));
    }

    /**
     * Configure repositories
     */
    protected abstract function configureRepositories();

    /**
     * @param RepositoryInterface $repository
     */
    protected function addRepository(RepositoryInterface $repository)
    {
        if (!isset($this['repository'])) {
            $this['repository'] = new \ArrayObject();
        }
        $name = $repository->getTable();
        $this['repository'][$name] = $repository;
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
     * @param  string $name
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
     * @param  mixed $name
     * @param  mixed $arguments
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

        // CamelCase to underscored
        $repository = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($matchesArray[2])));

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
}
