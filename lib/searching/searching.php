<?php

function ws_index_document($doc) {
    global $indexer;

    if (!isset($indexer)) {
        $provider = config_get('searching.provider');

        switch ($provider) {
            case 'solr':
                $indexer = new SolrIndexer($cfg->solr['host'], $cfg->solr['port'], $cfg->solr['path'] . $doc->get_doc_type() . '/'); 
                break;
            case 'zend':
            default:
                return false;
        }
    }

    return $indexer->index($doc);
}

function ws_search_document($query) {
    global $searcher;

    if (!isset($searcher)) {
        $provider = config_get('searching.provider');

        switch ($provider) {
            case 'solr':
                $searcher = new SolrSearcher($cfg->solr['host'], $cfg->solr['port'], $cfg->solr['path'] . $query->doc_type . '/'); 
                break;
            case 'zend':
            default:
                throw new Exception('对不起，搜索功能暂时不可用');
        }
    }

    return $searcher->search($query);
}


/**
 * 代码几乎和Apache_Solr_Document一样
 **/
class Document implements IteratorAggregate {
    protected $doc_type;

	protected $_document_boost = false;

	protected $_fields = array();

	protected $_field_boosts = array();

    public function __construct($doc_type = false) {
        $this->doc_type = $doc_type;
    }

    public function get_doc_type() {
        return $this->doc_type;
    }

    public function set_doc_type($doc_type) {
        $this->doc_type = $doc_type;
    }

	public function clear() {
		$this->_document_boost = false;

		$this->_fields = array();
		$this->_field_boosts = array();
	}

	public function get_boost() {
		return $this->_document_boost;
	}

	/**
	 * Set document boost factor
	 *
	 * @param mixed $boost Use false for default boost, else cast to float that should be > 0 or will be treated as false
	 */
	public function set_boost($boost) {
		$boost = (float) $boost;

		if ($boost > 0.0) {
			$this->_document_boost = $boost;
		}
		else {
			$this->_document_boost = false;
		}
	}

	/**
	 * Add a value to a multi-valued field
	 *
	 * NOTE: the solr XML format allows you to specify boosts
	 * PER value even though the underlying Lucene implementation
	 * only allows a boost per field. To remedy this, the final
	 * field boost value will be the product of all specified boosts
	 * on field values - this is similar to SolrJ's functionality.
	 *
	 * <code>
	 * $doc = new Apache_Solr_Document();
	 *
	 * $doc->addField('foo', 'bar', 2.0);
	 * $doc->addField('foo', 'baz', 3.0);
	 *
	 * // resultant field boost will be 6!
	 * echo $doc->getFieldBoost('foo');
	 * </code>
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param mixed $boost Use false for default boost, else cast to float that should be > 0 or will be treated as false
	 */
	public function add_field($key, $value, $boost = false) {
		if (!isset($this->_fields[$key])) {
			// create holding array if this is the first value
			$this->_fields[$key] = array();
		}
		else if (!is_array($this->_fields[$key])) {
			// move existing value into array if it is not already an array
			$this->_fields[$key] = array($this->_fields[$key]);
		}

		if ($this->get_field_boost($key) === false) {
			// boost not already set, set it now
			$this->set_field_boost($key, $boost);
		}
		else if ((float) $boost > 0.0) {
			// multiply passed boost with current field boost - similar to SolrJ implementation
			$this->_field_boosts[$key] *= (float) $boost;
		}

		// add value to array
		$this->_fields[$key][] = $value;
	}

	/**
	 * Get field information
	 *
	 * @param string $key
	 * @return mixed associative array of info if field exists, false otherwise
	 */
	public function get_field($key) {
		if (isset($this->_fields[$key])) {
			return array(
				'name'  => $key,
				'value' => $this->_fields[$key],
				'boost' => $this->get_field_boost($key)
			);
		}

		return false;
	}

	/**
	 * Set a field value. Multi-valued fields should be set as arrays
	 * or instead use the addField(...) function which will automatically
	 * make sure the field is an array.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param mixed $boost Use false for default boost, else cast to float that should be > 0 or will be treated as false
	 */
	public function set_field($key, $value, $boost = false)
	{
		$this->_fields[$key] = $value;
		$this->set_field_boost($key, $boost);
	}

	/**
	 * Get the currently set field boost for a document field
	 *
	 * @param string $key
	 * @return float currently set field boost, false if one is not set
	 */
	public function get_field_boost($key) {
		return isset($this->_field_boosts[$key]) ? $this->_field_boosts[$key] : false;
	}

	/**
	 * Set the field boost for a document field
	 *
	 * @param string $key field name for the boost
	 * @param mixed $boost Use false for default boost, else cast to float that should be > 0 or will be treated as false
	 */
	public function set_field_boost($key, $boost) {
		$boost = (float) $boost;

		if ($boost > 0.0)
		{
			$this->_field_boosts[$key] = $boost;
		}
		else
		{
			$this->_field_boosts[$key] = false;
		}
	}

	/**
	 * Return current field boosts, indexed by field name
	 *
	 * @return array
	 */
	public function get_field_boosts() {
		return $this->_field_boosts;
	}

	/**
	 * Get the names of all fields in this document
	 *
	 * @return array
	 */
	public function get_field_names()
	{
		return array_keys($this->_fields);
	}

	/**
	 * Get the values of all fields in this document
	 *
	 * @return array
	 */
	public function get_field_values()
	{
		return array_values($this->_fields);
	}

	/**
	 * IteratorAggregate implementation function. Allows usage:
	 *
	 * <code>
	 * foreach ($document as $key => $value)
	 * {
	 * 	...
	 * }
	 * </code>
	 */
	public function getIterator() {
		$arrayObject = new ArrayObject($this->_fields);

		return $arrayObject->getIterator();
	}

	/**
	 * Magic get for field values
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->_fields[$key];
	}

	/**
	 * Magic set for field values. Multi-valued fields should be set as arrays
	 * or instead use the addField(...) function which will automatically
	 * make sure the field is an array.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value)
	{
		$this->set_field($key, $value);
	}

	/**
	 * Magic isset for fields values.  Do not call directly. Allows usage:
	 *
	 * <code>
	 * isset($document->some_field);
	 * </code>
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function __isset($key)
	{
		return isset($this->_fields[$key]);
	}

	/**
	 * Magic unset for field values. Do not call directly. Allows usage:
	 *
	 * <code>
	 * unset($document->some_field);
	 * </code>
	 *
	 * @param string $key
	 */
	public function __unset($key)
	{
		unset($this->_fields[$key]);
		unset($this->_field_boosts[$key]);
	}
}

class Query {
    public $doc_type;
	public $query;
	public $params;
	
	public $page;
	public $per_page;
	
	public function __construct($doc_type) {
		$this->doc_type = $doc_type;
        $this->params   = array();
	}
	
    public function add_param($key, $value) {
        $this->params[$key] = $value;   
    }
}

interface Indexer {
    public function index($doc);
}

interface Searcher {
    public function search($query);
}



class SolrIndexer implements Indexer {
    protected $solr;

    public function __construct($host, $port, $path) {
        require_once(WFM_ROOT . '/lib/searching/Solr/Service.php');    
    
        $this->solr = new Apache_Solr_Service($host, $port, $path);
    }

    public function index($doc) {
        $solr_doc = new Apache_Solr_Document();
        foreach ($doc as $key => $value) {
            $solr_doc->setField($key, $value, $doc->get_field_boost($key));
        }

        $this->solr->addDocument($solr_doc);
        $this->solr->commit();
        return true;
    }
}

class SolrSearcher implements Searcher {
    protected $solr;

    public function __construct($host, $port, $path) {
        require_once(WFM_ROOT . '/lib/searching/Solr/Service.php');    
    
        $this->solr = new Apache_Solr_Service($host, $port, $path);
    }

    public function search($query) {
        $offset = ($query->page - 1) * $query->per_page;
        $limit  = $query->per_page;

        $result = $this->solr->search($query->query, $offset, $limit, $query->params);
        $rsp    = $result->response;

        $total  = $rsp->numFound;
        $pager  = new Pager($query->page, $query->per_page, $total);

        $docs   = array();

        if ($total > 0) {
            $solr_docs = $rsp->docs;
            foreach ($solr_docs as $solr_doc) {
                $doc = new Document($query->doc_type);
                foreach ($solr_doc as $key => $value) {
                    $doc->set_field($key, $value);
                }
                $docs[] = $doc;
            }
        }
        return array('docs' => $docs, 'pager' => $pager);
    }
}
?>
