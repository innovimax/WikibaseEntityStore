<?php

namespace Wikibase\EntityStore;

use Wikibase\DataModel\Entity\ItemLookup;
use Wikibase\DataModel\Entity\PropertyLookup;

/**
 * @licence GPLv2+
 * @author Thomas Pellissier Tanon
 */
abstract class EntityStore {

	/**
	 * @return EntityDocumentLookup
	 */
	public function getEntityDocumentLookup() {
		throw new FeatureNotSupportedException( 'EntityDocumentLookup not supported.' );
	}

	/**
	 * @return ItemLookup
	 */
	public function getItemLookup() {
		throw new FeatureNotSupportedException( 'ItemLookup not supported.' );
	}

	/**
	 * @return PropertyLookup
	 */
	public function getPropertyLookup() {
		throw new FeatureNotSupportedException( 'PropertyLookup not supported.' );
	}

	/**
	 * @return ItemIdForTermLookup
	 */
	public function getItemIdForTermLookup() {
		throw new FeatureNotSupportedException( 'ItemIdForTermLookup not supported.' );
	}

	/**
	 * @return PropertyIdForTermLookup
	 */
	public function getPropertyIdForTermLookup() {
		throw new FeatureNotSupportedException( 'PropertyIdForTermLookup not supported.' );
	}

	/**
	 * @return EntityDocumentSaver
	 */
	public function getEntityDocumentSaver() {
		throw new FeatureNotSupportedException( 'EntityDocumentSaver not supported.' );
	}

	/**
	 * Setup the EntityStore if it has not been done yet (create database tables, query indexes...).
	 *
	 * It should not drop data if the store is already setup.
	 */
	public function setupStore() {
	}
}
