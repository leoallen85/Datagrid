<div <? if ($config['id'] !== FALSE) echo 'id="'.$config['id'].'"' ?> class="data_grid">

	<? if ($config['top_controls'] === TRUE) : ?>

		<div class="tableControls">
			<? if ($pagination !== FALSE) : ?>
				<?= $pagination ?>
			<? endif ?>

			<? if ($config['top_total_records'] === TRUE) : ?>
				<p class="totalRecords floatLeft">Total records : <?= number_format($total_records); ?></p>
			<? endif ?>

			<? if ($config['search_box'] !== FALSE) : ?>
				<?= form::open($config['search_box'] === TRUE ? NULL : $config['search_box'], array( 'class' => 'tableSearch', 'method' => 'get')) ?>
				<?= form::input( array('class' => 'searchBox', 'id' => 'search', 'name' => 'search', 'title' => 'search'), urldecode( Input::instance()->get('search') ) )?>
				<?= form::button(array('type' => 'submit', 'name' => 'submit', 'class' => 'searchButton'), 'Search') ?>
				<?= form::close() ?>
			<? endif; ?>

			<? if ($config['add_new'] !== FALSE) : ?>
				<p class="addNew floatRight"><?= html::anchor($config['add_new'], 'Add New', array('class'=>'action')) ?></p>
			<? endif; ?>
			<? if ($spreadsheet_link !== NULL) : ?>
				<p class="floatRight"><?= html::anchor($spreadsheet_link, 'Create Excel sheet', array('class'=>'action')) ?></p>
			<? endif ?>
		</div>
		<div class="clearFloats"></div>
	<? endif ?>

	<table	<? if ($config['border'] !== FALSE) : ?> border="<?= $config['border'] ?>" <? endif; ?>
			<? if ($config['cellspacing'] !== FALSE) : ?> cellspacing="<?= $config['cellspacing'] ?>"<? endif; ?>
			<? if ($config['cellpadding'] !== FALSE) : ?> cellpadding="<?= $config['cellpadding'] ?>"<? endif; ?>
			<? if ($config['class'] !== FALSE) : ?> class="<?= $config['class'] ?>"<? endif; ?>>

		<? if($config['caption'] !== FALSE) : ?>
			<caption>
				<?= $config['caption'] ?>
			</caption>
		<? endif; ?>

		<thead>
			<tr>
				<? foreach ($headers as $header => $data) : ?>

				<? 	// Get the column name for ordering by
					if (is_array($data))
					{
							$column = array_pop($data);
							$table = end($data);
							$data = $table.':'.$column;
					}
				?>

				<th>
				<? 	if ($orderby === $data)
					{
						$direction = $orderby_direction;
						$class = 'order_by_'.$direction;
					}
					else
					{
						$direction = 'DESC';
						$class = 'order_by';
					}
				?>

				<?= html::anchor($url['url'].'?orderby='.urlencode($data).'&direction='.$direction.$url['query_string'], $header, array('class' => $class)) ?>
				</th>
				<? endforeach; ?>

				<? if ($config['action']['edit'] OR $config['action']['delete'] ) : ?>
					<th <? if($config['action']['edit'] AND $config['action']['delete']) : ?>colspan="2"<? endif; ?>>
						Action
					</th>
				<? endif; ?>
			</tr>
		</thead>

		<tbody>
		<? if ($total_rows > 0 ) : ?>

			<? $even = FALSE; ?>

			<? foreach( $rows as $row ) : ?>
			<tr<?= $even ? ' class="even"' : ''; ?>>
				<? foreach ($row as $key => $cell) : if ( ! $cell) $cell = '<em class="empty">&mdash;</em>'; ?>
				<? if ($key === 'edit') : ?>
				<td class="numeric"><?= html::anchor($config['action']['controller'] . '/' . $config['action']['edit_uri_segment'].'/'.$row['edit'], $config['action']['edit'], array( 'class' => 'editItem') ) ?></td>
				<? elseif ($key === 'delete') : ?>
				<td class="numeric"><?= html::anchor($config['action']['controller'].'/'.$config['action']['delete_uri_segment'].'/'.$row['delete'], $config['action']['delete'], array( 'class' => 'cancel tiny button')) ?></td>
				<? elseif (is_int($key) OR $key ==='id') : ?>
				<td><?= $cell ?></td>
				<? else : ?>
<?php
					// Set up live editing options

					if (strpos($key, '.'))
					{
						list($table, $column) = explode('.', $key);

						$editable = array(
							'type' => 'select',
							'save' => url::site( "datagrid_api/save/{$row['id']}/{$table}_id/{$table_name}"),
							'loadurl' => url::site( "datagrid_api/get_list/{$table_name}/{$table}/{$column}"),
						);
					}
					elseif (strpos($key, ':'))
					{
						list($table, $column) =  explode (':', $key);

						$editable = array(
							'type' => 'select',
							'save' => url::site( "datagrid_api/save/{$row['id']}/{$column}/{$table_name}"),
							'loadurl' => url::site( "datagrid_api/get_list/{$table}/{$column}"),
						);
					}
					else
					{
						$editable = array(
							'type' => 'text',
							'save' => url::site( "datagrid_api/save/{$row['id']}/{$key}/{$table_name}"),
						);
					}

					$params = array();
					foreach ($editable as $key => $value)
					{
						$params[] = "{$key} {$value}";
					}

?>
						<td class="editable" rel="<?php echo implode(',', $params) ?>"><?= $cell ?></td>
				<? endif; ?>
				<? endforeach; ?>
				<? $even = $even ? FALSE : TRUE; ?>
			</tr>
			<? endforeach; ?>

			<? foreach ($config['additional_rows'] as $row) : ?>
			<tr<?= $even ? ' class="even"' : ''; ?>>
					<? foreach($row as $cell) : ?>
					<td><?= $cell ?></td>
					<? endforeach ?>
			</tr>
			<? endforeach ?>

			<? else : ?>
			<tr>
				<td colspan="<?= count($headers) ?>"><p class="warning">No records found<p></td>
			</tr>
			<? endif; ?>
		</tbody>
	</table>

	<? if ($config['bottom_controls'] === TRUE) : ?>
		<div class="tableControls">
			<? if ($pagination !== FALSE) : ?>
				<?= $pagination ?>
			<? endif ?>

			<? if ($config['bottom_total_records'] === TRUE) : ?>
				<p class="totalRecords floatLeft">Total records : <?= number_format($total_records); ?></p>
			<? endif ?>

		</div>
	<? endif ?>
</div>
