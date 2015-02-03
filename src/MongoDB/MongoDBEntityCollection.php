<?php

namespace Wikibase\EntityStore\MongoDB;

use Doctrine\MongoDB\Collection;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityStore\EntityDocumentLookup;
use Wikibase\EntityStore\EntityDocumentSaver;
use Wikibase\EntityStore\EntityNotFoundException;

/**
 * Internal class
 *
 * @licence GPLv2+
 * @author Thomas Pellissier Tanon
 */
class MongoDBEntityCollection implements EntityDocumentLookup, EntityDocumentSaver {

	/**
	 * @var Collection
	 */
	private $collection;

	/**
	 * @var MongoDBDocumentBuilder
	 */
	private $documentBuilder;

	/**
	 * @param Collection $collection
	 * @param MongoDBDocumentBuilder $documentBuilder
	 */
	public function __construct( Collection $collection, MongoDBDocumentBuilder $documentBuilder ) {
		$this->collection = $collection;
		$this->documentBuilder = $documentBuilder;
	}

	/**
	 * @see EntityDocumentLookup::getEntityDocumentForId
	 */
	public function getEntityDocumentForId( EntityId $entityId ) {
		$document = $this->collection->findOne( $this->buildGetEntityForIdQuery( $entityId ) );

		if( $document === null ) {
			throw new EntityNotFoundException( $entityId );
		}

		return $this->documentBuilder->buildEntityForDocument( $document );
	}

	/**
	 * @see EntityDocumentLookup::getEntityDocumentsForIds
	 */
	public function getEntityDocumentsForIds( array $entityIds ) {
		$documents = $this->collection->find( $this->buildGetEntitiesForIdsQuery( $entityIds ) );

		$entities = array();

		foreach( $documents as $document ) {
			$entities[] = $this->documentBuilder->buildEntityForDocument( $document );
		}

		return $entities;
	}

	/**
	 * @see EntityDocumentSaver::saveEntityDocument
	 */
	public function saveEntityDocument( EntityDocument $entityDocument ) {
		$this->collection->upsert(
			$this->buildGetEntityForIdQuery( $entityDocument->getId() ),
			$this->documentBuilder->buildDocumentForEntity( $entityDocument )
		);
	}

	private function buildGetEntityForIdQuery( EntityId $entityId ) {
		return $this->collection->createQueryBuilder()
			->field( 'id' )->equals( $entityId->getSerialization() )
			->getQueryArray();
	}

	private function buildGetEntitiesForIdsQuery( array $entityIds ) {
		return $this->collection->createQueryBuilder()
			->field( 'id' )->in( $this->serializeEntityIds( $entityIds ) )
			->getQueryArray();
	}

	private function serializeEntityIds( array $entityIds ) {
		$serializations = array();

		foreach( $entityIds as $entityId ) {
			$serializations[] = $entityId->getSerialization();
		}


		return $serializations;
	}
}