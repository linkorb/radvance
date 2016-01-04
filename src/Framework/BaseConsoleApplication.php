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
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use Radvance\Repository\RepositoryInterface;
use Radvance\Exception\BadMethodCallException;

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

        $this->configureParameters();
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

    protected function getParametersPath()
    {
        return sprintf('%s/app/config/parameters.yml', $this->getRootPath());
    }

    protected function getLogsPath()
    {
        return sprintf('%s/app/logs/development.log', $this->getRootPath());
    }

    protected function getRepositoryPath()
    {
        return sprintf('%s/src/Repository', $this->getRootPath());
    }

    protected function getParameters()
    {
        $parser = new YamlParser();
        if (!file_exists($this->getParametersPath())) {
            throw new RuntimeException(
                "The parameters config file was not found.
                Please copy parameters.yml.dist to parameters.yml, and tune it for your purposes."
            );
        }
        return $parser->parse(file_get_contents($this->getParametersPath()));
    }

    protected function postProcessString($string)
    {
        $language = new ExpressionLanguage();
        $language->register(
            'env',
            function ($str) {
                // This implementation is only needed if you want to compile
                // not needed when simply using the evaluator
                throw new RuntimeException("The 'env' method is not yet compilable.");
            },
            function ($arguments, $str, $required = false) {
                $res = getenv($str);

                if (!$res && $required) {
                    throw new RuntimeException("Required environment variable '$str' is not defined");
                }
                return $res;
            }
        );

        preg_match_all('~\{\{(.*?)\}\}~', $string, $matches);

        $variables = array();
        //$variables['hello']='world';

        foreach ($matches[1] as $match) {
            $out = $language->evaluate($match, $variables);
            $string = str_replace('{{' . $match . '}}', $out, $string);
        }

        return $string;
    }

    protected function postProcessParameters(&$parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_string($value)) {
                $parameters[$key] = $this->postProcessString($value);
            }
            if (is_array($value)) {
                $parameters[$key] = $this->postProcessParameters($value);
            }
        }
        return $parameters;
    }

    /**
     * Configure parameters
     */
    protected function configureParameters()
    {
        $parameters = $this->getParameters();
        $parameters = $this->postProcessParameters($parameters);
        $this['parameters'] = $parameters;

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
            return;
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
    // abstract protected function configureRepositories();
    public function configureRepositories()
    {
        $this->configurePdoRepositories();
        // TODO: support other types of repositories
    }

    private function configurePdoRepositories()
    {
        if (!$this->pdo) {
            throw new RuntimeException("PDO not configured yet");
        }
        $ns = (new \ReflectionObject($this))->getNamespaceName();

        $dir = $this->getRepositoryPath();
        foreach (glob($dir.'/Pdo*Repository.php') as $filename) {
            $className = $ns.'\\Repository\\'.basename($filename, '.php');
            // only load the ones implements Radvance RepositoryInterface
            if (in_array('Radvance\\Repository\\RepositoryInterface', class_implements($className))) {
                $this->addRepository(new $className($this->pdo));
            }
        }
    }

    /**
     * @param RepositoryInterface $repository
     */
    protected function addRepository(RepositoryInterface $repository)
    {
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
