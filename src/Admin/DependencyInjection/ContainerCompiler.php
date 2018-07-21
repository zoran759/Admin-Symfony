<?php

/**
 * This file is part of the pdAdmin package.
 *
 * @package     pdAdmin
 *
 * @author      Ramazan APAYDIN <iletisim@ramazanapaydin.com>
 * @copyright   Copyright (c) 2018 pdAdmin
 * @license     LICENSE
 *
 * @link        https://github.com/rmznpydn/pd-admin
 */

namespace App\Admin\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author  Ramazan Apaydın <iletisim@ramazanapaydin.com>
 */
class ContainerCompiler
{
    /**
     * @var ContainerBuilder
     */
    private $containerBuilder;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $db;

    public function __construct(ContainerBuilder $containerBuilder)
    {
        $this->containerBuilder = $containerBuilder;

        try {
            $this->dbConnect();
            $this->initConfig();
            $this->dbClose();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add Container Parameter to Database.
     */
    protected function initConfig()
    {
        $query = $this->db->query('SELECT * from app_config');

        while (false !== $result = $query->fetchObject()) {
            // Fetch Form Data
            if ($value = @unserialize($result->value)) {
                foreach ($value as $name => $val) {
                    // Convert Bool
                    if (1 === $val || '1' === $val) {
                        $val = true;
                    } elseif (0 === $val || '0' === $val) {
                        $val = false;
                    }

                    // Set Container Parameter
                    $this->containerBuilder->setParameter($name, $val);
                }
            }
        }
    }

    /**
     * Connect Doctrine Database.
     */
    protected function dbConnect()
    {
        // Get Doctrine Config
        $configs = $this->containerBuilder->getExtensionConfig('doctrine');

        // Resolve Config
        $mergedConfig = [];
        foreach ($configs as $config) {
            $mergedConfig = array_merge($mergedConfig, $config);
        }
        $mergedConfig = $this->containerBuilder->getParameterBag()->resolveValue($mergedConfig);
        $mergedConfig['dbal']['url'] = $_SERVER['DATABASE_URL'] ?? null;

        // Connect Database
        $connection_factory = new ConnectionFactory([]);
        $this->db = $connection_factory->createConnection($mergedConfig['dbal']);
        $this->db->connect();
    }

    /**
     * Closes the database connection.
     */
    protected function dbClose()
    {
        if ($this->db->isConnected()) {
            $this->db->close();
        }
    }
}
