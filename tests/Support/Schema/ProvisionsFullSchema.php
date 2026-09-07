<?php

declare(strict_types=1);

namespace App\Tests\Support\Schema;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * QUAL-3 (rétro S16/S17) — provisionne **l'intégralité** du schéma ORM pour un test fonctionnel, à
 * partir de la metadata factory. Évite la « cascade SchemaTool » : une nouvelle entité est prise en
 * compte automatiquement, sans éditer chaque test.
 *
 * Usage dans un WebTestCase / KernelTestCase :
 *   $this->provisionSchema($this->em);   // dans setUp(), après avoir récupéré l'EntityManager
 *   $this->dropSchema($this->em);        // dans tearDown()
 */
trait ProvisionsFullSchema
{
    /** @var list<ClassMetadata<object>> */
    private array $provisionedSchema = [];

    private function provisionSchema(EntityManagerInterface $em): void
    {
        $this->provisionedSchema = $em->getMetadataFactory()->getAllMetadata();
        $tool = new SchemaTool($em);
        $tool->dropSchema($this->provisionedSchema);
        $tool->createSchema($this->provisionedSchema);
    }

    private function dropSchema(EntityManagerInterface $em): void
    {
        if ([] === $this->provisionedSchema) {
            return;
        }
        new SchemaTool($em)->dropSchema($this->provisionedSchema);
    }
}
