<?php

/**
 * Datagrid_Core builds a datagrid to allow sorting, searching and display of
 * ORM models, quickly building an HTML table from a set of values obtained from the database.
 *
 * @package default
 * @author Leo Allen <leo.f.allen@gmail.com>
 */
class Datagrid_Core
{
	// Configuration
	protected $config;

	// Attributes for the table
	protected $attributes;

	// Hash of config file for identifying specifi datagrid
	protected $hash;

	// Query sp	ecifics
	protected $query_keys = array (
		'where',
		'orwhere',
		'in',
		'notin',
		'like',
		'orlike',
		'notlike',
		'having',
		'orhaving',
		'join',
		'limit',
		'groupby'
	);

	// Database configuration
	protected $db = 'default';
	protected $db_applied = array ();

	protected $input;

	// Query support
	protected $orderby;

	// Model configuration
	protected $object;

	protected $total_records;

	// Pagination
	protected $pagination;

	// The datagrid rows publicly accessible
	public $records;
	public $headers;

	protected $total_rows;
	public $rows;

	protected $row_numbers;

	protected $loaded = FALSE;

	// Current page number
	protected $page_number;

	// Jedit properties
	protected $jedit;

	// Whether or not to render spreadsheet
	protected $render_spreadsheet;

	// Auto search column if applicable
	protected $auto_search = FALSE;

	/**
	 * Factory method for producing datagrids
	 *
	 * @param ORM $model
	 * @param string $config
	 * @return void
	 */
	public static function factory($model = NULL, $config = array ())
	{
		return new Datagrid($model, $config);
	}

	public function __construct($model = NULL, $config = array ())
	{

		// Setup configuration
		$config += Kohana::config('datagrid');
		$this->config = $config;

		// Create a hash of the config
		$this->hash();

		// Get the object
		$this->object = ORM::factory($model);

		// Load a database instance
		$this->db = Database::instance();

		// Load the input library
		$this->input = Input::instance();

		// Check whether or not to load row numbers
		$this->row_numbers = (bool) $this->config['row_numbers'];

		// Check whether to set an auto search
		if ($column = $this->config['auto_search'] AND $search = $this->input->get('search'))
		{
			$this->auto_search = TRUE;

			if ($column === TRUE)
			{
				$column = $this->object->foreign_key(TRUE);
			}
			else
			{
				$column = $this->object->table_name.'.'.$column;
			}

			if ( ! ctype_digit($search))
			{
				// Only use like if this is not a numerical search
				// Add to database query using __call
				$this->like($column, $search);
			}
			else
			{
				$this->where($column, $search);
			}
		}
	}

	/**
	 * Overload __get to allow access to config values
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		if (array_key_exists($key, $this->config))
		{
			$value = $this->config[$key];
		}
		else
		{
			throw new Kohana_User_Exception('Datagrid_Core::__get()', 'Trying to get non-property : '.$key);
		}

		return $value;
	}

	/**
	 * Overload __set to allow alternative way of setting config values
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __set($key, $value)
	{
		if (isset($this->config[$key]))
		{
			// Change the config
			$this->config[$key] = $value;

			// Update the hash
			$this->hash();
		}
		else
		{
			throw new Kohana_User_Exception(__CLASS__.':'.__METHOD__.'()', 'Datagrid only allows you to set config values');
		}

	}

	public function __call($method, $args)
	{
		if (in_array($method, $this->query_keys))
		{
			$num_args = count($args);

			switch ($num_args) {
				case 0 :
					// Support for things like reset_select, reset_write, list_tables
					return $this->db->$method ();
					break;
				case 1 :
						// Don't group when counting records
						$this->db-> $method ($args[0]);

					is_array($args[0]) ? $this->db_applied[$method][] = array (
						'methodName' => $method,
						key($args[0]),
						current($args[0])
					) : $this->db_applied[$method][] = array('methodName' => $method, $args[0]);
					break;
				case 2 :
					$this->db-> $method ($args[0], $args[1]);
					$this->db_applied[$method][] = array (
						'methodName' => $method,
						$args[0],
						$args[1]
					);
					break;
				case 3 :
					$this->db-> $method ($args[0], $args[1], $args[2]);
					$this->db_applied[$method][] = array (
						'methodName' => $method,
						$args[0],
						$args[1],
						$args[2]
					);
					break;
				case 4 :
					$this->db-> $method ($args[0], $args[1], $args[2], $args[3]);
					$this->db_applied[$method][] = array (
						'methodName' => $method,
						$args[0],
						$args[1],
						$args[2],
						$args[3]
					);
					break;
				default :
					// Here comes the snail...
					call_user_func_array(array (
						$this->db,
						$method
					), $args);
					$this->db_applied['special_case'] = array (
						'methodName' => $method,
						$args
					);
					break;
			}
		} else {
			throw new Kohana_Exception("Datagrid_Core::__call(), Trying to call undefined method Datagrid_Core::\${$method}()");
		}

		return $this;
	}

	/**
	 * Allow automatic rendering of view when object is echoed
	 *
	 * @return string html view
	 */
	public function __toString()
	{
		return (string) $this->render();
	}

	/**
	 *  Create a hash of the config for identification purposes
	 *
	 */
	public function hash()
	{
		$this->hash = md5(serialize($this->config));
	}

	/**
	 * Allow easier loading of columns rather than passing a config array
	 *
	 * @param array $columns The columns to add as an associative array
	 * @return Return the object to allow for chaining
	 *
	 */
	public function columns($columns)
	{
		$this->config['columns'] = $columns;

		return $this;
	}

	/**
	 * Allow easier loading of callback rather than passing a config array
	 * Call the function for each callback to add
	 *
	 * @param string $callback The callback name
	 * @param string array $columns The columns to add
	 * @param mixed $parameters Parameters to pass through to callback
	 * @return Return the object to allow for chaining
	 *
	 */
	public function callbacks($callback, $columns, $parameters = NULL, $use_column_alias = NULL)
	{
		$this->config['callbacks'][$callback] = array
		(
			'columns' 			=> $columns,
			'parameters' 		=> 2,
			'use_column_alias' 	=> $use_column_alias
		);

		// Update the hash
		$this->hash();

		return $this;
	}

	/**
	 * Allow easier loading of action rather than passing through config array
	 *
	 * @param string $controller The controller name
	 * @param string $edit The value to show in the edit button
	 * @param string $edit_uri_segment The path for to link to
	 * @param string $delete The value to show in the delete button
	 * @param string $delete_uri_segment The path to link to
	 * @return Return the object to allow for chaining
	 */
	public function action($controller,  $edit = 'Edit', $edit_uri_segment = 'edit', $delete = FALSE, $delete_uri_segment = 'delete')
	{
		$this->config['action'] = array
		(
			'controller' 		=> $controller,
			'edit' 			=> $edit,
			'edit_uri_segment' 	=> $edit_uri_segment,
			'delete' 		=> $delete,
			'delete_uri_segment' 	=> $delete_uri_segment
		);

		// Update the hash
		$this->hash();

		return $this;
	}

	/**
	 * Allow easier loading of pagination options rather than passing through config array
	 *
	 * @param integer $items_per_page If false will ensure pagination is switched off
	 * @param string $base_url
	 * @param string $style
	 * @param string $directory
	 * @param string $uri_segment
	 * @param boolean $auto_hide
	 * @return Return the object to allow for chaining
	 */
	public function pagination($items_per_page = FALSE, $base_url = '', $style = 'extended', $directory = 'pagination', $uri_segment = 'page', $auto_hide = TRUE)
	{
		if ($items_per_page === FALSE)
		{
			$this->config['pagination'] = FALSE;
		}
		else
		{
			$this->config['pagination'] = array
			(
				'base_url'			=> $base_url,
				'directory'			=> $directory,
				'style'				=> $style,
				'uri_segment'			=> $uri_segment,
				'items_per_page'		=> $items_per_page,
				'auto_hide'			=> $auto_hide,
			);
		}

		// Update the hash
		$this->hash();

		return $this;
	}
	public function load()
	{
		// See if some details can be loaded from the cache TODO
		//Load the column headers
		$this->headers = $this->_load_columns();

		// Load the total number of results if no pagination involved
		$this->total_records = $this->count_records();

		// Setup the query based on saved arguments
		$this->_setup_database_query($this->db);

		// Work out order by direction
		$this->_order_by();

		// Set up joins
		$this->_setup_join_query($this->db);

		// Setup the pagination library
		if($this->config['pagination'] !== FALSE )
		{
			// Add the total_records to pagination config
			$this->config['pagination'] += array('total_items' => $this->total_records);

			// Load pagination
			$this->pagination = Pagination::factory($this->config['pagination']);

			// Set the limit and offset
			$this->db->limit($this->pagination->items_per_page, $this->pagination->sql_offset);
		}

		//Load rows
		$this->rows = $this->db->get($this->object->table_name);

		//Calculate total rows to be loaded into table
		$this->total_rows = $this->rows->count();

		// Redirect if only one row found and this is a search query
		if ($this->total_rows == 1 AND $this->auto_search === TRUE)
		{
			// But can only redirect if an edit controller has been supplied
			if ($this->config['action']['edit'] !== FALSE)
			{
			// Get the id of the single row returned
			$id = $this->rows->current()->id;

			return url::redirect("{$this->config['action']['controller']}/{$this->config['action']['edit_uri_segment']}/{$id}");
			}
		}

		// Load table data now to make it accessible publicly
		$this->records = $this->_load_table_data();

		// Set loaded to TRUE even if there are no rows so render does not load the page twice
		$this->loaded = TRUE;

		// Return this object (for chaining)
		return $this;
	}

	/**
	 * Creates an excel spreadsheet from the generated datagrid
	 *
	 * WARNING: This will call load in order to ensure there is no pagination, so avoid calling
	 * load before this to keep processing time down
	 *
	 *  @param string $title The title of the spreadsheet
	 */
	public function excel($title = NULL)
	{
		// Clear pagination
		$this->config['pagination'] = FALSE;

		// Load even if this has already been loaded without pagination
		$this->load();

		// Get the data
		$arr = array_merge(array(array_keys($this->headers) + array('format' => 'bold')), $this->records, $this->additional_rows);

		// Strip out tags
		$arr = arr::map_recursive('strip_tags', $arr);

		// If title is null generate one
		if ($title === NULL)
		{
			$title = ucwords($this->object->table_name).' - '.date('d-m-y');
		}

		// Create the spreadsheet
		return file::array_to_xls($arr, $title);
	}

	/**
	 * Renders the data into the defined views. Method called by __toString
	 *
	 * @return bool
	 */
	public function render()
	{
		// Determine whether or not to render the spreadsheet
		$this->render_spreadsheet = ($spreadsheet = $this->input->get('spreadsheet', FALSE) AND $spreadsheet === $this->hash);
		
		// Check if this is a call to render the view as a spreadsheet instead
		if ($this->render_spreadsheet)
		{
			$this->excel($this->input->get('spreadsheet_title', NULL));
		}

		//Check if data has been loaded if not load it
		if ( ! $this->loaded ) $this->load();

		// Load the config
		$config = $this->config;

		$pagination = ($config['pagination'] !== FALSE) ? $this->pagination->render() : FALSE;

		// Work out the orderby direction
		if($orderby_direction = $this->input->get('direction', 'DESC'))
			$orderby_direction  = ($orderby_direction === 'ASC') ? 'DESC' : 'ASC';

		if ($config['auth_key'] AND $user = Auth::instance()->get_user())
			$auth_key = $user->auth_key;

		// Work out whether or not to load the spreadsheet link
		if ($config['spreadsheet'])
		{
			$sheet = url::current().'?spreadsheet='.$this->hash;

			if (is_string($config['spreadsheet']))
			{
				// Add the title if specified
				$sheet .= '&spreadsheet_title='.urlencode($config['spreadsheet']);
			}
		}

		//Load main view
		return $table_view = View::factory('datagrid/template')
				->bind('config', $config)
				->bind('spreadsheet_link', $sheet)
				->bind('pagination', $pagination)
				->bind('total_records', $this->total_records)
				->bind('headers', $this->headers)
				->bind('rows', $this->records)
				->bind('total_rows', $this->total_rows)
				->set('table_name', $this->object->table_name)
				->set('orderby', urldecode($this->input->get('orderby', FALSE)))
				->set('orderby_direction', $orderby_direction)
				->set('url', $this->_load_site_url())
				->bind('auth_key', $auth_key)
				->render();
	}

	/**
	 * Returns the total records for this model based on query settings
	 *
	 * @return void
	 */
	public function count_records()
	{
		return $this->db->count_records($this->object->table_name);
	}


	/**
	 * Works out which column to order by
	 *
	 * @return array The column to roder by and the direction
	 */
	protected function _order_by()
	{
		$sorting = array();

		if ($column = $this->input->get('orderby'))
		{
			// Add the sorting input from the user
			$sorting += array($column => $this->input->get('direction', 'asc'));
		}

		if ( ! empty($this->config['orderby']))
		{
			$sorting += $this->config['orderby'];
		}

		if ($this->object->sorting)
		{
			// Add the default sorting
			foreach ($this->object->sorting as $column => $direction)
			{
				$sorting[$this->object->table_name.'.'.$column] = $direction;
			}
		}

		$this->orderby = $sorting;
	}

	/**
	 * Loads all of a tables columns
	 *
	 * @return array
	 */
	protected function _load_all_fields()
	{
		// Load the table fields
		$table_fields = $this->db->list_fields($this->object->table_name);

		// Setup the columns
		$columns = array();

		// Foreach field
		foreach ($table_fields as $fieldname => $attributes)
		{
			if ($pos = strpos($fieldname, '_id'))
			{
				$title = substr($fieldname, 0, $pos);

				$fieldname = $title.'->title';

				// Assign to the array object
				$columns[ucwords(inflector::humanize($title))] = $fieldname;
			}
			else
			{
				// Assign to the array object
				$columns[] = $fieldname;
			}
		}

		return $columns;
	}

	/**
	 * Loads up column data to pass into table view
	 *
	 * @return array
	 */
	protected function _load_columns()
	{
		// Setup the columns
		if ($this->config['columns'] === TRUE)
			$columns = $this->_load_all_fields();

		elseif ($this->row_numbers)
			$columns = array_merge(array('#' => 'id'), $this->config['columns']);

		else
			$columns = $this->config['columns'];

		$column_data = array();

		foreach ($columns as $key => $current_column)
		{
			//Check for whether current column contains details for ajax edit
			if (is_array($current_column))
			{
				//Load editable variables

				//Make current column equal the first value in the array
				$current_column[0];
			}
			else
			{
				//Check for whether is ORM relationship
				$is_orm = strpos($current_column, '->');
			}

			//Otherwise check if the column key is an integer
			if (is_int($key))
			{
				$column_name = $current_column;

				//Assign correct column name if is ORM relationship
				if ($is_orm)
				{
					$column_name = substr($current_column, $is_orm +2);
				}

				//Humanise column names
				$column_name = ucwords(inflector::humanize($column_name));
			}
			// if not then it has an alias
			else
			{
				$column_name = ucwords(inflector :: humanize($key));
			}

			// Assign correct data if is ORM relationship
			if ($is_orm)
			{
				//Load into an array
				$current_column = explode('->', $current_column);
			}

			//Append to array object
			$column_data[$column_name] = $current_column;
		}

		return $column_data;
	}

	protected function _setup_join_query(& $db_obj)
	{
		// Get the additional columns into same format as headers column
		$additional_columns = $this->config['additional_columns'];

		foreach ($additional_columns as & $col)
		{
			// Change to array if there is an -> indicating a join relationship
			if (strpos($col, '->'))
			{
				$col = explode('->', $col);
			}
		}

		$columns = arr::array_append($this->headers, $additional_columns);

		// Set up the array to pass into the select statement
		$select = array();

		// Set up an array of columns joined so far to avoid duplicates
		$joined = array();

		// Setup the query getting the select and the join parts
		foreach ($columns as $column)
		{
			// If the column's an array it implies that we need to do a join
			if (is_array($column))
			{
				$column_name = array_pop($column);

				for ($i = 0, $size = count($column); $i < $size; ++$i)
				{
						// Load from main model or else from the table defined in the array
						$model = ($i === 0) ? ORM::factory($this->object->object_name) : ORM::factory($column[$i-1]);

						// Table to join onto
					   $on_table = ($i === 0) ? $model->table_name : $column[$i -1];

						// Get the value from belongs to
						$belongs_to = $model->belongs_to;

						// Get the actual name of the join table
						$join_table = isset($belongs_to[$column[$i]]) ? inflector::plural($belongs_to[$column[$i]]) : inflector::plural($column[$i]);

						 // We only need to perform the join if we have not yet joined this alias name
						if ( ! in_array($join_table.$column[$i], $joined))
						{
							// Join the table
							$db_obj->join($join_table.' AS '.$column[$i] , $on_table.'.'.$column[$i].'_id', $column[$i].'.id', 'LEFT' );

							// Add aliased name to array of joined tables
							$joined[] = $join_table.$column[$i];
						}

						// Check if this is the last in the array
						if( $i + 1 === $size)
						{
								// If so load into an array so that the correct table name is always accessible (this is to deal with ORM aliasing)
								$select_table = $column[$i];
						}
				}
				$select[] = $select_table.'.'.$column_name.' as '.end($column).':'.$column_name;
			}
			else
			{
					$select[] = $this->object->table_name.'.'.$column;
			}
		}

		// Add id even if not showing row numbers
		$select[] = $this->object->table_name.'.id AS row_id';

		$db_obj->select($select)
			->orderby($this->orderby);
	}
	
	protected function _setup_database_query(& $db_obj)
	{
				// Setup the query based on saved arguments
		foreach ($this->db_applied as $key => $values)
		{
			if ($key !== 'special_case')
			{
				// Discover how many values there are with this key
				if (is_array($values) )
				{
					foreach( $values as $value )
					{
						if ($value instanceof Database_Expression)
						{
							// If database expression just call the method
							$db_obj->$key($value);
						}
						elseif ( ! isset($value['methodName']) )
						{
							$db_obj-> $key ($values);
						}
						else
						{
							$method = array_shift($value);
							$num_args = count($value);

							switch ($num_args)
							{
								case 1 :
									$db_obj-> $method ($value[0]);
									break;
								case 2 :
									$db_obj-> $method ($value[0], $value[1]);
									break;
								case 3 :
									$db_obj-> $method ($value[0], $value[1], $value[2]);
																		break;
																case 4 :
									$db_obj-> $method ($value[0], $value[1], $value[2], $value[3]);
									break;
							}
						}
					}
				}
			}
			else
			{
				$method = array_shift($values);
				call_user_func_array(array (
					$db_obj,
					$method
				), $values);
			}
		}
	}

	/**
	 * Prepares table data to allow for it to be easily looped through in the view.
	 * Goes through callbacks as defined in callbacks config
	 *
	 *
	 */
	protected function _load_table_data()
	{
		$result = array();

		foreach ($this->rows as $row)
		{
			// Set up result array
			$single_row = array();

			//Loop through rest of main data
			foreach ($this->headers as $column_name => $column)
			{
				if (is_array($column))
				{
					// Get the array again
					$col_arr = $column;

					// Get the column name as the last value from the array
					$col_name = array_pop($col_arr);

					// Get the table name as the now last value from the array
					$table_name = array_pop( $col_arr);

					$col = $table_name.':'.$col_name;

					$current_cell= $row->$col;
				}
				else
				{
					$current_cell = $row->$column;
				}

				//Callback check
				$this->_callback_check($column, $current_cell, $row, $column_name );

				//Jedit check
				$key = $this->_jedit_check($column);

				if($key !== NULL)
				{
					$single_row[$key] = $current_cell;
				}
				else
				{
					$single_row[] = $current_cell;
				}

			}

			//Load edit and delete info
			if ($this->config['action']['edit'] AND ! $this->render_spreadsheet)
				$single_row['edit'] = $row->row_id;

			if ($this->config['action']['delete'] AND ! $this->render_spreadsheet)
				$single_row['delete'] = $row->row_id;

			//Load row into main result array
			$result[] = $single_row;
		}

		return $result;
	}

	/**
	 * Checks through each callback and applies function(s) if data matches
	 *
	 * @param mixed Data to work on
	 * @param Object Current row
	 * @return string or FALSE
	 */
	protected function _callback_check( $column = NULL, & $current_cell = NULL, $row = NULL, $column_alias = NULL )
	{
		//Find column name
		$column = is_array($column) ? end($column) : $column;

		//Loop through all callbacks
		foreach ($this->config['callbacks'] as $callback_name => $callback)
		{
			// Check if this is a special aliased column call back
			if (isset($callback['use_column_alias']) AND $callback['use_column_alias'] === TRUE)
			{
				$column_name = $column_alias;
			}
			else
			{
				$column_name = $column;
			}

			//Check for matches
			if (in_array($column_name, (array) $callback['columns']))
			{
				if (isset($callback['parameters']))
				{
					// Call the helper
					$current_cell = datagrid_helper::$callback_name($current_cell, $row, $column_name, $callback['parameters']);
				}
				else
				{
					$current_cell = datagrid_helper::$callback_name($current_cell, $row, $column_name);
				}
			}
		}
	}

	/**
	 * Checks for jedit options
	 *
	 * @param mixed Data to work on
	 * @return string or NULL
	 */
	protected function _jedit_check($column = NULL)
	{
		// If jedit is set to true check if this is not in the exception array
		if (($this->config['jedit'] === TRUE AND ! in_array($column, $this->config['jedit_exceptions'])) OR (is_array($this->config['jedit']) AND in_array( $column, $this->config['jedit'])))
		{
			//Find column name and check if is a relationship
			if (is_array($column))
			{
				$last = array_pop($column);
				return end($column).'.'.$last;
			}
			elseif( $pos = strpos( $column, '_id' ) )
			{
				return substr( $column, 0, $pos ).'.id';

			}
			elseif( is_array($this->config['jedit_boolean']) AND in_array( $column, $this->config['jedit_boolean']))
				return 'bool:'.$column;
			else
				return $column;
		}
		elseif ($column === 'id')
			return 'id';

		return NULL;
	}

	/**
	 * Load site url *IMPORTANT* removes pagination method, ensures when ordering that view returns to first page of table
	 *
	 * @return string URL
	 */
	protected function _load_site_url()
	{
		//Get site segments
		$segment_array = uri :: instance();
		$segment_array = $segment_array->segment_array();
		$url = $segment_array[1];

		//Remove controller element
		unset($segment_array[1]);

		//Loop through until page is found
		if (is_array($segment_array))
		{
			foreach ($segment_array as $segment)
			{
				if ($segment === 'page')
				{
					break;
				}
				
				$url .= '/' . $segment;
			}
		}

		// Find query string to add onto the end of the link
		$query_string = '';

		foreach ($this->input->get() as $key => $val)
		{
			if($key !== 'orderby' AND $key !== 'direction')
			{
				$query_string .= '&'.$key.'='.$val;
			}
		}

		return array('url' => $url, 'query_string' => $query_string);
	}
} // End