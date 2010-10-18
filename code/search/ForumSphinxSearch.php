<?php

/**
 * An extension to the default Forum search to use the {@link Sphinx} class instead
 * of the standard database search.
 *
 * To use Sphinx instead of the built in Search is use:
 *
 * ``ForumHolder::set_search_engine('Sphinx');``
 *
 * @todo Currently this does not index or search forum Titles...
 *
 * @package forum
 */

class ForumSphinxSearch implements ForumSearchProvider {
	
	private $search_cache = array();
	
	/**
	 * Get the results
	 *
	 * @return DataObjectSet
	 */
	public function getResults($forumHolderID, $query, $order, $offset = 0, $limit = 10) {
		
		// Work out what sorting method
		switch($order) {
			case 'date':
				$mode = 'fields';
				
				$sortarg = array('Created' => 'DESC');
				break;
			case 'title':
				$mode = 'fields';
				
				$sortarg = array('Title' => 'ASC');
				break;
			default:
				$mode = 'relevance';
				
				$sortarg = false;
				break;
		}
		
		$cachekey = $query.':'.$offset;
		if (!isset($this->search_cache[$cachekey])) {
			// Determine the classes to search. This always include
			// ForumThread and Post, since we decorated them. It also
			// includes Forum and Member if they are decorated, as
			// appropriate.
			$classes = array('ForumThread', 'Post');
			foreach (array('Forum', 'Member') as $c) {
				if (Object::has_extension($c, 'SphinxSearchable')) $classes[] = $c;
			}

			$this->search_cache[$cachekey] = SphinxSearch::search($classes, $query, array(
				'start'			=> $offset,
				'pagesize'		=> $limit,
				'sortmode'		=> $mode,
				'sortarg'		=> $sortarg,
				'field_weights'	=> array("Title" => 2, "Content" => 1)
			));
		}
		
		return $this->search_cache[$cachekey]->Matches;
	}
	
	public function load() {
		DataObject::add_extension('ForumThread', 'SphinxSearchable');
		DataObject::add_extension('Post', 'SphinxSearchable');
	}
}
