<?php
/**
 * Columns to include, if TRUE then will include all columns.
 * If columns from other tables are required via an ORM relationship, they should be set out in the form table_name->column_name
 * Use an associative array if you want to define the name to show the column as something other than the name in the table
 *
 * Example
 * array
 * (
 * 		User Name => 'username',
 * 		'addresses->street_name',
 * );
 *
 * The first example will result in a column called 'User Name' containing $model->username.
 * The second example will result in a column from addresses called 'Street Name'
 *
 * @author Leo Allen
 */

$config['columns'] = TRUE;

/**
 * Define whether edit buttons appear and their name.
 * FALSE = button doesn't appear
 * Note that you must define 'edit' or 'delete' even if you don't intend to use them, just set them to FALSE
 * '
 * Example:
 * 'edit' => 'View'
 * would output an edit button with name view
 *
 * @author Leo Allen
 */
$config['action'] = array
(
        'controller'			=> 'your_controller',
        'edit'					=>	FALSE,
        'edit_uri_segment'		=> 'edit',
        'delete'				=>	FALSE,
        'delete_uri_segment'	=> 'delete',
);

/**
 * Defines default method for ordering table
 */
$config['orderby'] = array();

/**
 * Callbacks to be added. These can be defined in the datagrid_helper helper
 * If use_column_alias is set to TRUE then the columns should be the name of the column EXACTLY as they will appear at the top of the datagrid table
 * 		****(i.e. aliased names with each first letter UPPERCASE)****
 * If it is not set then the columns should be as they appear in orm (i.e. machine readable: date_created)
 * Parameters are optional
 * The callback will call datagrid_helper::{call_back_name} and pass in the parameters as arguments
 */
$config['callbacks'] = 	array
(
    'date' => array
    (
            'columns' 	        => array
            (
                    'date_created',
                    'date_modified',
            ),
            'parameters'        => 'g:i jS M y',
    )
);
/**
 * Set as FALSE to render the entire table on one page
 *
 */
$config['pagination'] =	array
(

        'items_per_page'		=> 20,
        'base_url'			=> '',
        'style'				=> 'extended',
        'directory'			=> 'pagination',
        'uri_segment'			=> 'page',
        'auto_hide'			=> TRUE,
);

/**
 * If set to TRUE shows a link to render the datagrid as an excel spreadsheet
 * If set to string this value will be passed through as the title of the spreadsheet
 *
 */
$config['spreadsheet'] = FALSE;

/**
 * If set to TRUE and there is a search query (i.e. $_GET['search']) will search by primary key
 * If set to string will search by that column name.
 * If array will search by all columns provided in array
 *
 * Will automatically redirect if only one record is found and there is a value supplied in edit
 *
 * @author Leo Allen
 */
$config['auto_search'] = FALSE;

/**
 * Some callbacks require additional columns, add them here to avoid unneccesary extra db calls
 */
$config['additional_columns'] = array();

/**
 * Options below allow for showing of other parts of the table
 * Be aware that for top_controls, if it is set to FALSE search_box, add_new, and top_total_records will not be visible, even if set to TRUE
 *
 * @author Leo Allen
 */

$config['additional_rows'] = array();

$config['row_numbers'] 	= TRUE;

$config['top_controls'] = TRUE;

$config['search_box'] = FALSE;

$config['add_new'] = FALSE;

$config['top_total_records'] = TRUE;

$config['bottom_controls'] = TRUE;

$config['bottom_total_records'] = TRUE;

$config['border'] = FALSE;

$config['cellspacing'] 	= FALSE;

$config['cellpadding'] 	= FALSE;

$config['caption'] = FALSE;

$config['id'] = FALSE;

