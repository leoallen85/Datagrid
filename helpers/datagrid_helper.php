<?php
/**
 * Helpers used as callbacks by datagrid library
 *
 * The cell should always be passed in by reference
 *
 * @author Leo Allen
 */
class datagrid_helper_Core
{
	/**
	 * To convert UNIX timestamps to date
	 * @param mixed $cell The cell, passed in by reference
	 * @param mixed $parameters Either an array or a string passed from the config
	 * @return integer The UNIX date
	 */
	public static function date($cell, $row = NULL, $column = NULL, $parameters = 'H:i:s l jS, F Y' )
	{
		if ($column === NULL)
		{
			// Externally called function, so $row will contain date parameters
			$parameters = $row;
		}
		
		// Convert the date
		if ( (int) $cell > 0 )
		{
			return date( $parameters, $cell );
		}
		else
		{
			return NULL;
		}
	}

	public static function number_format($cell, $row, $column, $parameters = '2')
	{
		return number_format($cell, $parameters);
	}

	public static function money_format($cell, $row, $column)
	{
		// Get column to get currency symbol from
		$currency_symbol = 'base_currency:currency_symbol';

		return Currency_Market::instance()->money_format($cell, $row->$currency_symbol);
	}

	public static function round($cell, $row, $column, $parameters = '2')
	{
		return round($cell, $parameters);
	}

	/**
	 * To convert 1 and 0 into yes and no
	 * @param mixed $cell The cell
	 * @return string Either yes or no
	 */
	public static function boolean($cell)
	{
		// Check if equals 1
		if( $cell == 1 )
			return 'Yes';

		elseif( $cell == 0 )
			return 'No';
		else
			return $cell;
	}
	
	public static function strip_tags( $cell )
	{
		return strip_tags( $cell );
	}

	public static function limit_words($cell, $row, $column, $limit = '20')
	{
		return text::limit_words( strip_tags( $cell ), $limit, '...' );
	}

	/**
	 * Supplies a link to see an expanded view of the cell
	 */
	public static function link($cell, $row = NULL, $column = NULL, $link_address = NULL)
	{
		if ($column === NULL)
		{
			// This means the function is called outside datagrid and second parameter is user parameter
			$link_address = $row;
		}
		
		// As this callback can be applied to more than one column, get the address for the right column
		if (is_array($link_address))
		{
			$link_address = $link_address[$column];
		}

		return html::anchor($link_address.'/'.$cell, $cell, array('target' => '_blank'));
	}

	public static function percentage($value)
	{
		return $value ? number_format($value, 0).'%' : '<em>none</em>';

}