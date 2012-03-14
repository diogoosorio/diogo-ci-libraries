<?php 

/**
 * This library functions as the loader for Doctrine. It essentially sets up
 * the Doctrine environment, registers all the configuration and gets an
 * instance of the entity manager.
 * 
 * After loading it you can easilly get the entity manager inside the application
 * by issuing $this->doctrine->em;
 * 
 * @author Diogo Osório (diogo.g.osorio@gmail.com)
 * @package tnbi
 * @subpackage library
 * @version 1.0
 * @link http://www.doctrine-project.org/docs/orm/2.0/en/cookbook/integrating-with-codeigniter.html
 */
use Doctrine\Common\ClassLoader,
    Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager,
    Doctrine\Common\Cache\ArrayCache,
    Doctrine\DBAL\Logging\EchoSQLLogger;

class Doctrine
{
    public $em = null;
    
    public function __construct()
    {
        // Load dependencies
        require_once APPPATH . 'config/database.php';
        require_once APPPATH . 'libraries/Doctrine/Common/ClassLoader.php';
        
        // Load Doctrine classes
        $doctrineLoader = new \Doctrine\Common\ClassLoader('Doctrine', APPPATH . 'libraries');
        $doctrineLoader->register();
        
        // Load Symfony2 helpers
        $symfonyLoader = new \Doctrine\Common\ClassLoader('Symfony', APPPATH . 'libraries/Doctrine');
        $symfonyLoader->register();
        
        // Load entities
        $entityLoader = new \Doctrine\Common\ClassLoader('models', rtrim(APPPATH, '/'));
        $entityLoader->register();
        
        // Load proxy entities
        $proxyLoader = new \Doctrine\Common\ClassLoader('Proxies', APPPATH . 'models/Proxies');
        $proxyLoader->register();
        
        // Set configuration
        $config = new \Doctrine\Orm\Configuration;
        
        // Set cache type for Doctrine
        $cache = ENVIRONMENT == 'development' ? new \Doctrine\Common\Cache\ArrayCache : new \Doctrine\Common\Cache\ApcCache;
        
        // Set cache type
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        
        // Set proxy directory and namespace
        $config->setProxyDir(APPPATH . 'models/Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setAutoGenerateProxyClasses(ENVIRONMENT == 'development');
        
        // Set mappings folder (YAML)
        $driverImpl = $config->newDefaultAnnotationDriver(APPPATH . 'models');
        $config->setMetadataDriverImpl($driverImpl);
        
        // If in environment, let's log the queries preformed
        /*if(ENVIRONMENT == 'development')
        {
            $logger = new \Doctrine\DBAL\Logging\EchoSQLLogger;
            $config->setSQLLogger($logger);
        }*/
        
        // Connection options retrieved from database.php
        $connectionOptions = array(
            'driver'        => 'pdo_mysql',
            'user'          => $db['default']['username'],
            'password'      => $db['default']['password'],
            'host'          => $db['default']['hostname'],
            'dbname'        => $db['default']['database']
        );
        
        // Register the entity manager
        $this->em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);
    }
}