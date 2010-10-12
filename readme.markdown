# Datagrid - A table module for Kohana 2.3.4

Datagrid is a library that is designed to quickly turn an ORM model containing some rows of data in it into an HTML table so that it can be viewed as a readable grid in a browser.

It was designed specifically for an administration system and works well as the R part of a CRUD scaffolding system, with the table able to provide links to creating, updating, and deleting records to complete the set.

## Configuration

Datagrid is highly configurable, but getting a table up is as simple as defining a model and the columns that you want to be viewed. However you will note that in the example below the additional feature of having all [[ORM|http://docs.kohanaphp.com/libraries/orm]] methods available allows us to limit our query to a particular user by directly calling this ORM method from the library.

	<?php
		$config = array
		(
			'columns'	=> array
			(
				'note',
				'Created By' => 'creator->name',
				'Modified By' => 'modifier->name',
				'Created On' => 'date_created',
				'Last Modified' => 'date_modified',
			)
		);
	
		echo new Datagrid('note', $config)->where('user_id', 123);
		
Other configuration features are explained in greater depth in the datagrid config file.