<?php

namespace Wikibase\EntityStore\MongoDB;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use InvalidArgumentException;
use MongoBinData;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\Term;
use Wikibase\EntityStore\EntityStore;
use Wikibase\EntityStore\EntityStoreOptions;

/**
 * Internal class
 *
 * @licence GPLv2+
 * @author Thomas Pellissier Tanon
 */
class MongoDBDocumentBuilder {

	const ITEM_TYPE_INTEGER = 0;
	const PROPERTY_TYPE_INTEGER = 1;

	private static $INTEGER_FOR_TYPES = array(
		Item::ENTITY_TYPE => self::ITEM_TYPE_INTEGER,
		Property::ENTITY_TYPE => self::PROPERTY_TYPE_INTEGER
	);

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var Deserializer
	 */
	private $entityDeserializer;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityStoreOptions
	 */
	private $options;

	/**
	 * @param Serializer $entitySerializer
	 * @param Deserializer $entityDeserializer
	 * @param EntityIdParser $entityIdParser
	 * @param EntityStoreOptions $options
	 */
	public function __construct(
		Serializer $entitySerializer,
		Deserializer $entityDeserializer,
		EntityIdParser $entityIdParser,
		EntityStoreOptions $options
	) {
		$this->entitySerializer = $entitySerializer;
		$this->entityDeserializer = $entityDeserializer;
		$this->entityIdParser = $entityIdParser;
		$this->options = $options;
	}

	/**
	 * @param EntityDocument $entityDocument
	 * @return array
	 */
	public function buildDocumentForEntity( EntityDocument $entityDocument ) {
		$entityDocument = $this->filterLanguages( $entityDocument );

		return $this->addIndexedDataToSerialization(
			$entityDocument,
			$this->entitySerializer->serialize( $entityDocument )
		);
	}

	private function filterLanguages( EntityDocument $entityDocument ) {
		$languagesOption = $this->options->getOption( EntityStore::OPTION_LANGUAGES );

		if( $languagesOption === null ) {
			return $entityDocument;
		}

		if( $entityDocument instanceof FingerprintProvider ) {
			$fingerprint = $entityDocument->getFingerprint();
			$fingerprint->setLabels( $fingerprint->getLabels()->getWithLanguages( $languagesOption ) );
			$fingerprint->setDescriptions( $fingerprint->getDescriptions()->getWithLanguages( $languagesOption ) );
			$fingerprint->setAliasGroups( $fingerprint->getAliasGroups()->getWithLanguages( $languagesOption ) );
		}

		return $entityDocument;
	}

	private function addIndexedDataToSerialization( EntityDocument $entityDocument, $serialization ) {
		$serialization['_id'] = $entityDocument->getId()->getSerialization();
		$serialization['_type'] = $this->buildIntegerForType( $entityDocument->getType() );

		if( $entityDocument instanceof FingerprintProvider ) {
			$serialization['sterms'] = $this->buildSearchTermsForFingerprint( $entityDocument->getFingerprint() );
		}

		return $serialization;
	}

	public function buildIntegerForType( $type ) {
		if( !array_key_exists( $type, self::$INTEGER_FOR_TYPES) ) {
			throw new InvalidArgumentException( 'Unknown entity type: ' . $type );
		}

		return self::$INTEGER_FOR_TYPES[$type];
	}

	private function buildSearchTermsForFingerprint( Fingerprint $fingerprint ) {
		$searchTerms = array();

		/** @var Term $label */
		foreach( $fingerprint->getLabels() as $label ) {
			$searchTerms[$label->getLanguageCode()][] = $this->cleanTextForSearch( $label->getText() );
		}

		/** @var AliasGroup $aliasGroup */
		foreach( $fingerprint->getAliasGroups() as $aliasGroup ) {
			foreach( $aliasGroup->getAliases() as $alias ) {
				$searchTerms[$aliasGroup->getLanguageCode()][] = $this->cleanTextForSearch( $alias );
			}
		}

		return $searchTerms;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function cleanTextForSearch( $text ) {
		$text = mb_strtolower( $text, 'UTF-8' ); //TODO: said to be very slow
		$text = str_replace( //TODO useful? + tests
			array( '\'', '-' ),
			array( ' ', ' ' ),
			$text
		);
		$text = trim( $text );

		return new MongoBinData( $text, MongoBinData::GENERIC );
	}

	/**
	 * @param array $document
	 * @return EntityDocument|null
	 */
	public function buildEntityForDocument( array $document ) {
		try {
			return $this->entityDeserializer->deserialize( $document );
		} catch( DeserializationException $exception ) {
			return null;
		}
	}

	/**
	 * @param array $document
	 * @return EntityId
	 * @throws EntityIdParsingException
	 */
	public function buildEntityIdForDocument( array $document ) {
		if( !array_key_exists( '_id', $document ) ) {
			throw new EntityIdParsingException();
		}

		return $this->entityIdParser->parse( $document['_id'] );
	}
}
